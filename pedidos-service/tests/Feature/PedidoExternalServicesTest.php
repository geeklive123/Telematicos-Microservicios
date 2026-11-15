<?php

namespace Tests\Feature;

use App\Models\Pedido;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PedidoExternalServicesTest extends TestCase
{
    private const CLIENTES_URL = 'http://clientes.test';

    private const PRODUCTOS_URL = 'http://productos.test';

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--force' => true]);

        config()->set('services.clientes.url', self::CLIENTES_URL);
        config()->set('services.productos.url', self::PRODUCTOS_URL);
    }

    public function test_valid_order_queries_client_and_product_over_http(): void
    {
        $this->fakeSuccessfulServices();

        $this->postJson('/api/pedidos', $this->payload())->assertCreated();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::CLIENTES_URL.'/api/clientes/1');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::PRODUCTOS_URL.'/api/productos/10');
    }

    public function test_missing_client_returns_unprocessable_and_does_not_persist(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => Http::response([], 404),
        ]);

        $this->postJson('/api/pedidos', $this->payload())
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'El cliente seleccionado no existe.']);

        $this->assertDatabaseCount('pedidos', 0);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/productos/'));
    }

    public function test_inactive_client_returns_unprocessable_and_does_not_persist(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => Http::response([
                'id' => 1,
                'activo' => false,
            ]),
        ]);

        $this->postJson('/api/pedidos', $this->payload())
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'El cliente seleccionado estÃ¡ inactivo.']);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_unavailable_client_service_returns_service_unavailable(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => Http::failedConnection('Connection refused'),
        ]);

        $this->postJson('/api/pedidos', $this->payload())
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'El servicio de clientes no estÃ¡ disponible.']);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_missing_product_returns_unprocessable_and_does_not_persist(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => $this->activeClientResponse(),
            self::PRODUCTOS_URL.'/api/productos/10' => Http::response([], 404),
        ]);

        $this->postJson('/api/pedidos', $this->payload())
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'Uno de los productos seleccionados no existe.']);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_inactive_product_returns_unprocessable_and_does_not_persist(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => $this->activeClientResponse(),
            self::PRODUCTOS_URL.'/api/productos/10' => Http::response([
                'id' => 10,
                'nombre' => 'Ryzen remoto',
                'precio' => 900,
                'stock' => 10,
                'activo' => false,
            ]),
        ]);

        $this->postJson('/api/pedidos', $this->payload())
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'Uno de los productos seleccionados estÃ¡ inactivo.']);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_insufficient_stock_returns_conflict_and_does_not_persist(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => $this->activeClientResponse(),
            self::PRODUCTOS_URL.'/api/productos/10' => Http::response([
                'id' => 10,
                'nombre' => 'Ryzen remoto',
                'precio' => 900,
                'stock' => 1,
                'activo' => true,
            ]),
        ]);

        $this->postJson('/api/pedidos', $this->payload())
            ->assertConflict()
            ->assertExactJson(['message' => 'Stock insuficiente para el producto seleccionado.']);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_unavailable_product_service_returns_service_unavailable(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => $this->activeClientResponse(),
            self::PRODUCTOS_URL.'/api/productos/10' => Http::failedConnection('Connection refused'),
        ]);

        $this->postJson('/api/pedidos', $this->payload())
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'El servicio de productos no estÃ¡ disponible.']);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_remote_product_data_controls_historical_values_and_totals(): void
    {
        $this->fakeSuccessfulServices([
            10 => $this->product(10, 'Nombre remoto confiable', 812.34),
        ]);

        $payload = $this->payload();
        $payload['subtotal'] = 1;
        $payload['total'] = 1;
        $payload['detalles'][0]['producto_nombre'] = 'Nombre manipulado';
        $payload['detalles'][0]['precio_unitario'] = 0.01;
        $payload['detalles'][0]['subtotal'] = 0.02;

        $this->postJson('/api/pedidos', $payload)
            ->assertCreated()
            ->assertJsonPath('detalles.0.producto_nombre', 'Nombre remoto confiable')
            ->assertJsonPath('detalles.0.precio_unitario', '812.34')
            ->assertJsonPath('detalles.0.subtotal', '1624.68')
            ->assertJsonPath('subtotal', '1624.68')
            ->assertJsonPath('total', '1624.68');
    }

    public function test_stock_discount_receives_the_correct_quantity(): void
    {
        $this->fakeSuccessfulServices();

        $this->postJson('/api/pedidos', $this->payload())->assertCreated();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::PRODUCTOS_URL.'/api/productos/10/descontar-stock'
            && $request->data()['cantidad'] === 2);
    }

    public function test_multiple_products_are_discounted(): void
    {
        $this->fakeSuccessfulServices([
            10 => $this->product(10, 'Ryzen remoto', 900),
            15 => $this->product(15, 'Memoria remota', 300),
        ]);
        $payload = $this->payload();
        $payload['detalles'][] = ['producto_id' => 15, 'cantidad' => 3];

        $this->postJson('/api/pedidos', $payload)->assertCreated();

        Http::assertSent(fn (Request $request): bool => $request->url()
            === self::PRODUCTOS_URL.'/api/productos/10/descontar-stock'
            && $request->data()['cantidad'] === 2);
        Http::assertSent(fn (Request $request): bool => $request->url()
            === self::PRODUCTOS_URL.'/api/productos/15/descontar-stock'
            && $request->data()['cantidad'] === 3);
    }

    public function test_order_is_not_persisted_when_stock_discount_fails(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => $this->activeClientResponse(),
            self::PRODUCTOS_URL.'/api/productos/10' => Http::response($this->product(10, 'Ryzen', 900)),
            self::PRODUCTOS_URL.'/api/productos/10/descontar-stock' => Http::response([], 409),
        ]);

        $this->postJson('/api/pedidos', $this->payload())
            ->assertConflict()
            ->assertExactJson(['message' => 'Stock insuficiente para el producto seleccionado.']);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_later_discount_failure_replenishes_previous_stock(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes/1' => $this->activeClientResponse(),
            self::PRODUCTOS_URL.'/api/productos/10' => Http::response($this->product(10, 'Ryzen', 900)),
            self::PRODUCTOS_URL.'/api/productos/15' => Http::response($this->product(15, 'Memoria', 300)),
            self::PRODUCTOS_URL.'/api/productos/10/descontar-stock' => Http::response([], 200),
            self::PRODUCTOS_URL.'/api/productos/15/descontar-stock' => Http::response([], 409),
            self::PRODUCTOS_URL.'/api/productos/10/reponer-stock' => Http::response([], 200),
        ]);
        $payload = $this->payload();
        $payload['detalles'][] = ['producto_id' => 15, 'cantidad' => 1];

        $this->postJson('/api/pedidos', $payload)->assertConflict();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::PRODUCTOS_URL.'/api/productos/10/reponer-stock'
            && $request->data()['cantidad'] === 2);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_local_persistence_failure_replenishes_all_discounted_stock(): void
    {
        $this->fakeSuccessfulServices();
        Pedido::creating(function (): never {
            throw new RuntimeException('Fallo local simulado.');
        });

        try {
            $this->postJson('/api/pedidos', $this->payload())
                ->assertInternalServerError()
                ->assertExactJson(['message' => 'No se pudo crear el pedido.']);
        } finally {
            Pedido::flushEventListeners();
        }

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::PRODUCTOS_URL.'/api/productos/10/reponer-stock'
            && $request->data()['cantidad'] === 2);
        $this->assertDatabaseCount('pedidos', 0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     */
    private function fakeSuccessfulServices(array $products = []): void
    {
        $products += [
            10 => $this->product(10, 'Ryzen remoto', 950),
            15 => $this->product(15, 'Memoria remota', 350),
        ];

        Http::fake(function (Request $request) use ($products) {
            $url = $request->url();

            if ($request->method() === 'GET' && str_contains($url, '/api/clientes/')) {
                return $this->activeClientResponse();
            }

            if ($request->method() === 'POST'
                && (str_ends_with($url, '/descontar-stock') || str_ends_with($url, '/reponer-stock'))) {
                return Http::response([], 200);
            }

            if ($request->method() === 'GET'
                && preg_match('~/api/productos/(\d+)$~', $url, $matches) === 1) {
                $productId = (int) $matches[1];

                return isset($products[$productId])
                    ? Http::response($products[$productId])
                    : Http::response([], 404);
            }

            return Http::response([], 500);
        });
    }

    private function activeClientResponse(): PromiseInterface
    {
        return Http::response(['id' => 1, 'activo' => true]);
    }

    /**
     * @return array{id: int, nombre: string, precio: float|int, stock: int, activo: bool}
     */
    private function product(int $id, string $name, float|int $price): array
    {
        return [
            'id' => $id,
            'nombre' => $name,
            'precio' => $price,
            'stock' => 100,
            'activo' => true,
        ];
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
}
