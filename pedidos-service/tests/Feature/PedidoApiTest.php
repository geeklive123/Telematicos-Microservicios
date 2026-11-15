<?php

namespace Tests\Feature;

use App\Models\Pedido;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PedidoApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--force' => true]);
        $this->fakeSuccessfulExternalServices();
    }

    public function test_health_responds_successfully(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'service' => 'pedidos-service',
                'status' => 'ok',
            ]);
    }

    public function test_it_lists_orders_with_their_details(): void
    {
        $this->createPedidoConDetalle();

        $this->getJson('/api/pedidos')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonCount(1, '0.detalles');
    }

    public function test_it_creates_an_order_with_one_detail(): void
    {
        $response = $this->postJson('/api/pedidos', $this->payload());

        $response
            ->assertCreated()
            ->assertJsonPath('cliente_id', 1)
            ->assertJsonCount(1, 'detalles');

        $this->assertDatabaseHas('pedidos', ['cliente_id' => 1]);
        $this->assertDatabaseHas('detalle_pedidos', ['producto_id' => 10]);
    }

    public function test_vendedor_order_propagates_the_same_token_to_both_services(): void
    {
        $claims = app('tymon.jwt.payload.factory')->customClaims(['sub' => '1', 'role' => 'vendedor'])->make();
        $token = app('tymon.jwt.manager')->encode($claims)->get();

        $response = $this->withToken($token)->postJson('/api/pedidos', $this->payload())->assertCreated();
        $pedidoId = $response->json('id');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/clientes/')
            && $request->hasHeader('Authorization', 'Bearer '.$token));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/productos/')
            && $request->method() === 'GET'
            && $request->hasHeader('Authorization', 'Bearer '.$token));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/descontar-stock')
            && $request->hasHeader('Authorization', 'Bearer '.$token));
        $this->withToken($token)->putJson("/api/pedidos/{$pedidoId}", ['estado' => 'cancelado'])->assertOk();
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/reponer-stock')
            && $request->hasHeader('Authorization', 'Bearer '.$token)
            && $request['source_type'] === 'pedido-cancellation');
    }

    public function test_it_creates_an_order_with_multiple_details(): void
    {
        $payload = $this->payload();
        $payload['detalles'][] = [
            'producto_id' => 15,
            'cantidad' => 1,
        ];

        $this->postJson('/api/pedidos', $payload)
            ->assertCreated()
            ->assertJsonCount(2, 'detalles');

        $this->assertDatabaseCount('detalle_pedidos', 2);
    }

    public function test_each_detail_subtotal_is_calculated_by_the_server(): void
    {
        $this->postJson('/api/pedidos', $this->payload())
            ->assertCreated()
            ->assertJsonPath('detalles.0.subtotal', '1900.00');
    }

    public function test_order_subtotal_and_total_are_calculated_by_the_server(): void
    {
        $payload = $this->payload();
        $payload['detalles'][] = [
            'producto_id' => 15,
            'cantidad' => 1,
        ];

        $this->postJson('/api/pedidos', $payload)
            ->assertCreated()
            ->assertJsonPath('subtotal', '2250.00')
            ->assertJsonPath('total', '2250.00');
    }

    public function test_client_supplied_subtotals_and_total_are_ignored(): void
    {
        $payload = $this->payload();
        $payload['subtotal'] = 1;
        $payload['total'] = 2;
        $payload['detalles'][0]['producto_nombre'] = 'Nombre manipulado';
        $payload['detalles'][0]['precio_unitario'] = 0.01;
        $payload['detalles'][0]['subtotal'] = 3;

        $this->postJson('/api/pedidos', $payload)
            ->assertCreated()
            ->assertJsonPath('subtotal', '1900.00')
            ->assertJsonPath('total', '1900.00')
            ->assertJsonPath('detalles.0.subtotal', '1900.00');
    }

    public function test_creation_without_details_returns_unprocessable_entity(): void
    {
        $payload = $this->payload();
        unset($payload['detalles']);

        $this->postJson('/api/pedidos', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('detalles');
    }

    public function test_zero_quantity_returns_unprocessable_entity(): void
    {
        $payload = $this->payload();
        $payload['detalles'][0]['cantidad'] = 0;

        $this->postJson('/api/pedidos', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('detalles.0.cantidad');
    }

    public function test_client_supplied_price_is_ignored(): void
    {
        $payload = $this->payload();
        $payload['detalles'][0]['precio_unitario'] = -0.01;

        $this->postJson('/api/pedidos', $payload)
            ->assertCreated()
            ->assertJsonPath('detalles.0.precio_unitario', '950.00');
    }

    public function test_it_shows_an_existing_order_with_details(): void
    {
        $pedido = $this->createPedidoConDetalle();

        $this->getJson("/api/pedidos/{$pedido->id}")
            ->assertOk()
            ->assertJsonPath('id', $pedido->id)
            ->assertJsonCount(1, 'detalles');
    }

    public function test_missing_order_returns_not_found(): void
    {
        $this->getJson('/api/pedidos/999999')->assertNotFound();
    }

    public function test_it_updates_the_order_status(): void
    {
        $pedido = $this->createPedidoConDetalle();

        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'pagado'])
            ->assertOk()
            ->assertJsonPath('estado', 'pagado');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'estado' => 'pagado',
        ]);
    }

    public function test_it_updates_the_payment_method(): void
    {
        $pedido = $this->createPedidoConDetalle();

        $this->putJson("/api/pedidos/{$pedido->id}", ['metodo_pago' => 'qr'])
            ->assertOk()
            ->assertJsonPath('metodo_pago', 'qr');
    }

    public function test_it_does_not_allow_updating_cliente_id(): void
    {
        $pedido = $this->createPedidoConDetalle();

        $this->putJson("/api/pedidos/{$pedido->id}", ['cliente_id' => 99])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');

        $this->assertSame(1, $pedido->fresh()->cliente_id);
    }

    public function test_deleting_an_order_with_details_returns_conflict(): void
    {
        $pedido = $this->createPedidoConDetalle();

        $this->deleteJson("/api/pedidos/{$pedido->id}")
            ->assertConflict()
            ->assertJsonPath('message', 'No se puede eliminar un pedido que tiene detalles.');

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id]);
    }

    public function test_it_deletes_an_order_without_details(): void
    {
        $pedido = $this->createPedido();

        $this->deleteJson("/api/pedidos/{$pedido->id}")->assertNoContent();

        $this->assertDatabaseMissing('pedidos', ['id' => $pedido->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'cliente_id' => 1,
            'estado' => 'pendiente',
            'metodo_pago' => 'efectivo',
            'fecha' => '2026-10-06 10:30:00',
            'detalles' => [[
                'producto_id' => 10,
                'cantidad' => 2,
            ]],
        ];
    }

    private function fakeSuccessfulExternalServices(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if ($request->method() === 'GET' && str_contains($url, '/api/clientes/')) {
                return Http::response([
                    'id' => 1,
                    'activo' => true,
                ]);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/descontar-stock')) {
                return Http::response(['message' => 'Stock descontado.']);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/reponer-stock')) {
                return Http::response(['message' => 'Stock repuesto.']);
            }

            if ($request->method() === 'GET'
                && preg_match('~/api/productos/(\d+)$~', $url, $matches) === 1) {
                $productos = [
                    10 => ['nombre' => 'Ryzen 5 5600G', 'precio' => 950.00],
                    15 => ['nombre' => 'Kingston Fury 16GB', 'precio' => 350.00],
                ];
                $productoId = (int) $matches[1];
                $producto = $productos[$productoId] ?? [
                    'nombre' => 'Producto de prueba',
                    'precio' => 100.00,
                ];

                return Http::response([
                    'id' => $productoId,
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'stock' => 100,
                    'activo' => true,
                ]);
            }

            return Http::response([], 500);
        });
    }

    private function createPedido(): Pedido
    {
        return Pedido::create([
            'cliente_id' => 1,
            'subtotal' => 0,
            'total' => 0,
            'estado' => 'pendiente',
            'metodo_pago' => 'efectivo',
            'fecha' => '2026-10-06 10:30:00',
        ]);
    }

    private function createPedidoConDetalle(): Pedido
    {
        $pedido = $this->createPedido();
        $pedido->detalles()->create([
            'producto_id' => 10,
            'producto_nombre' => 'Ryzen 5 5600G',
            'precio_unitario' => 950,
            'cantidad' => 1,
            'subtotal' => 950,
        ]);

        return $pedido;
    }
}
