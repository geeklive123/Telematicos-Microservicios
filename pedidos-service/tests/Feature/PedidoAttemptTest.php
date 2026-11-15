<?php

namespace Tests\Feature;

use App\Exceptions\PedidoCreationException;
use App\Models\Pedido;
use App\Models\PedidoAttempt;
use App\Services\PedidoService;
use App\Services\ProductoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PedidoAttemptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.clientes.url', 'http://clientes.test');
        config()->set('services.productos.url', 'http://productos.test');
        Http::preventStrayRequests();
    }

    public function test_attempt_es_durable_y_descuento_normaliza_productos(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/descontar-stock')) {
                $attempt = PedidoAttempt::firstOrFail();
                $this->assertSame('processing', $attempt->status);
                $this->assertSame('in_flight', $attempt->stock_operations[10]['discount']);

                return Http::response(['already_processed' => false]);
            }

            return $this->readResponse($request);
        });

        $payload = $this->payload();
        $payload['detalles'][] = ['producto_id' => 10, 'cantidad' => 3];
        $response = $this->postJson('/api/pedidos', $payload)->assertCreated()
            ->assertJsonCount(1, 'detalles')->assertJsonPath('detalles.0.cantidad', 5);
        $attempt = PedidoAttempt::firstOrFail();
        $this->assertSame('completed', $attempt->status);
        $this->assertSame($response->json('id'), $attempt->pedido_id);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/descontar-stock')
            && $request->data() === [
                'cantidad' => 5,
                'idempotency_key' => "pedido-attempt:{$attempt->attempt_uuid}:product:10:discount",
                'source_type' => 'pedido', 'source_reference' => $attempt->attempt_uuid,
            ]);
        Http::assertSentCount(3);

        $retried = app(PedidoService::class)->resumeAttempt($attempt->attempt_uuid);
        $this->assertSame($attempt->pedido_id, $retried->id);
        $this->assertDatabaseCount('pedidos', 1);
        Http::assertSentCount(3);
    }

    public function test_timeout_reintenta_una_vez_con_la_misma_clave_y_acepta_already_processed(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            if (str_ends_with($request->url(), '/descontar-stock')) {
                $sent[] = $request->data();
                if (count($sent) === 1) {
                    throw new ConnectionException('timeout');
                }

                return Http::response(['already_processed' => true]);
            }

            return $this->readResponse($request);
        });
        $this->postJson('/api/pedidos', $this->payload())->assertCreated();
        $this->assertCount(2, $sent);
        $this->assertSame($sent[0], $sent[1]);
        $this->assertSame('completed', PedidoAttempt::firstOrFail()->status);
    }

    public function test_timeout_doble_queda_en_conciliacion(): void
    {
        $attempts = 0;
        Http::fake(function (Request $request) use (&$attempts) {
            if (str_ends_with($request->url(), '/descontar-stock')) {
                $attempts++;
                throw new ConnectionException('timeout');
            }

            return $this->readResponse($request);
        });
        $this->postJson('/api/pedidos', $this->payload())->assertServiceUnavailable();
        $attempt = PedidoAttempt::firstOrFail();
        $this->assertSame('needs_reconciliation', $attempt->status);
        $this->assertSame('in_flight', $attempt->stock_operations[10]['discount']);
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertSame(2, $attempts);
    }

    public function test_fallo_local_compensa_con_clave_estable_y_marca_failed(): void
    {
        Http::fake(fn (Request $request) => str_contains($request->url(), '-stock')
            ? Http::response(['already_processed' => false]) : $this->readResponse($request));
        Pedido::creating(fn () => throw new RuntimeException('fallo local'));
        try {
            $this->postJson('/api/pedidos', $this->payload())->assertInternalServerError();
        } finally {
            Pedido::flushEventListeners();
        }
        $attempt = PedidoAttempt::firstOrFail();
        $this->assertSame('failed', $attempt->status);
        $this->assertSame('confirmed', $attempt->stock_operations[10]['restore']);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/reponer-stock')
            && $request->data() === [
                'cantidad' => 2, 'idempotency_key' => "pedido-attempt:{$attempt->attempt_uuid}:product:10:restore",
                'source_type' => 'pedido-compensation', 'source_reference' => $attempt->attempt_uuid,
            ]);
    }

    public function test_fallo_de_compensacion_requiere_conciliacion(): void
    {
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/reponer-stock')) {
                return Http::response([], 503);
            }

            return str_ends_with($request->url(), '/descontar-stock') ? Http::response([]) : $this->readResponse($request);
        });
        Pedido::creating(fn () => throw new RuntimeException('fallo local'));
        try {
            $this->postJson('/api/pedidos', $this->payload())->assertServiceUnavailable();
        } finally {
            Pedido::flushEventListeners();
        }
        $attempt = PedidoAttempt::firstOrFail();
        $this->assertSame('needs_reconciliation', $attempt->status);
        $this->assertSame('in_flight', $attempt->stock_operations[10]['restore']);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_error_antes_de_descuento_marca_failed(): void
    {
        Http::fake(['http://clientes.test/*' => Http::response(['id' => 1, 'activo' => false])]);
        $this->postJson('/api/pedidos', $this->payload())->assertUnprocessable();
        $this->assertSame('failed', PedidoAttempt::firstOrFail()->status);
        Http::assertSentCount(1);
    }

    public function test_restauracion_repetida_usa_el_mismo_contrato(): void
    {
        Http::fake(['http://productos.test/*' => Http::response(['already_processed' => true])]);
        $service = app(ProductoService::class);
        $service->reponerStock(10, 2, 'stable-key', 'pedido-compensation', 'attempt');
        $service->reponerStock(10, 2, 'stable-key', 'pedido-compensation', 'attempt');
        $sent = Http::recorded()->map(fn ($pair) => $pair[0]->data())->all();
        $this->assertCount(2, $sent);
        $this->assertSame($sent[0], $sent[1]);
    }

    public function test_reanudar_operacion_incierta_utiliza_la_clave_original(): void
    {
        $uuid = '11111111-1111-4111-8111-111111111111';
        $attempt = PedidoAttempt::create([
            'attempt_uuid' => $uuid, 'cliente_id' => 1, 'status' => 'processing', 'payload' => $this->payload(),
            'resolved_details' => [['producto_id' => 10, 'producto_nombre' => 'Producto', 'precio_unitario' => 100, 'cantidad' => 2, 'subtotal' => 200]],
            'stock_operations' => [10 => ['producto_id' => 10, 'cantidad' => 2, 'discount' => 'in_flight']],
        ]);
        Http::fake(['http://productos.test/*' => Http::response(['already_processed' => true])]);

        $pedido = app(PedidoService::class)->resumeAttempt($uuid);
        $this->assertSame('completed', $attempt->fresh()->status);
        $this->assertSame($pedido->id, $attempt->fresh()->pedido_id);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->data()['idempotency_key'] === "pedido-attempt:{$uuid}:product:10:discount");
    }

    public function test_intento_reclamado_no_se_ejecuta_en_paralelo(): void
    {
        $attempt = PedidoAttempt::create([
            'attempt_uuid' => '22222222-2222-4222-8222-222222222222', 'cliente_id' => 1,
            'status' => 'processing', 'payload' => $this->payload(), 'claimed' => true,
        ]);
        Http::fake();
        try {
            app(PedidoService::class)->resumeAttempt($attempt->attempt_uuid);
            $this->fail('El intento ocupado no debe reanudarse.');
        } catch (PedidoCreationException $exception) {
            $this->assertSame(409, $exception->status());
        }
        Http::assertNothingSent();
    }

    public function test_intento_con_compensacion_iniciada_no_crea_pedido(): void
    {
        $attempt = PedidoAttempt::create([
            'attempt_uuid' => '33333333-3333-4333-8333-333333333333', 'cliente_id' => 1,
            'status' => 'processing', 'payload' => $this->payload(),
            'stock_operations' => [10 => ['producto_id' => 10, 'cantidad' => 2, 'discount' => 'confirmed', 'restore' => 'in_flight']],
        ]);
        Http::fake();
        try {
            app(PedidoService::class)->resumeAttempt($attempt->attempt_uuid);
            $this->fail('Un intento en compensaciÃ³n requiere conciliaciÃ³n.');
        } catch (PedidoCreationException $exception) {
            $this->assertSame(503, $exception->status());
        }
        $this->assertSame('needs_reconciliation', $attempt->fresh()->status);
        $this->assertDatabaseCount('pedidos', 0);
        Http::assertNothingSent();
    }

    public function test_cancelacion_agrupa_detalles_y_reutiliza_clave(): void
    {
        Http::fake(['http://productos.test/*' => Http::response(['already_processed' => false])]);
        $pedido = Pedido::create(['cliente_id' => 1, 'subtotal' => 3, 'total' => 3, 'estado' => 'pendiente', 'metodo_pago' => 'efectivo', 'fecha' => '2026-10-06']);
        foreach ([1, 2] as $cantidad) {
            $pedido->detalles()->create(['producto_id' => 10, 'producto_nombre' => 'Producto', 'precio_unitario' => 1, 'cantidad' => $cantidad, 'subtotal' => $cantidad]);
        }
        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'cancelado'])->assertOk();
        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'cancelado'])->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->data() === [
            'cantidad' => 3, 'idempotency_key' => "pedido:{$pedido->id}:product:10:cancel-restore",
            'source_type' => 'pedido-cancellation', 'source_reference' => (string) $pedido->id,
        ]);
    }

    private function readResponse(Request $request): mixed
    {
        if (str_contains($request->url(), '/api/clientes/')) {
            return Http::response(['id' => 1, 'activo' => true]);
        }
        if (str_contains($request->url(), '/api/productos/10')) {
            return Http::response(['id' => 10, 'nombre' => 'Producto', 'precio' => 100, 'stock' => 20, 'activo' => true]);
        }
        throw new RuntimeException('Solicitud inesperada: '.$request->url());
    }

    private function payload(): array
    {
        return ['cliente_id' => 1, 'estado' => 'pendiente', 'metodo_pago' => 'efectivo', 'fecha' => '2026-10-06 10:30:00', 'detalles' => [['producto_id' => 10, 'cantidad' => 2]]];
    }
}
