<?php

namespace Tests\Feature;

use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PedidoWebTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENTES_URL = 'http://clientes.test';

    private const PRODUCTOS_URL = 'http://productos.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.clientes.url', self::CLIENTES_URL);
        config()->set('services.productos.url', self::PRODUCTOS_URL);
    }

    public function test_listado_responde_200(): void
    {
        $this->get('/pedidos')
            ->assertOk()
            ->assertViewIs('pedidos.index')
            ->assertSee('AdministraciÃ³n de Pedidos');
    }

    public function test_formulario_crear_responde_200(): void
    {
        $this->fakeSuccessfulServices();

        $this->get('/pedidos/crear')
            ->assertOk()
            ->assertViewIs('pedidos.create')
            ->assertSee('Crear pedido');
    }

    public function test_carga_solamente_clientes_activos(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes' => Http::response([
                ['id' => 1, 'nombre' => 'Ana', 'apellido' => 'PÃ©rez', 'email' => 'ana@test.com', 'activo' => true],
                ['id' => 2, 'nombre' => 'Luis', 'apellido' => 'Rojas', 'email' => 'luis@test.com', 'activo' => false],
            ]),
            self::PRODUCTOS_URL.'/api/productos' => Http::response($this->products()),
        ]);

        $this->get('/pedidos/crear')
            ->assertOk()
            ->assertSee('Ana PÃ©rez - ana@test.com')
            ->assertDontSee('Luis Rojas - luis@test.com');
    }

    public function test_carga_solamente_productos_activos(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes' => Http::response($this->clients()),
            self::PRODUCTOS_URL.'/api/productos' => Http::response([
                $this->product(10, 'Ryzen activo', 950, 20),
                ['id' => 11, 'nombre' => 'Producto inactivo', 'precio' => 100, 'stock' => 10, 'activo' => false],
            ]),
        ]);

        $this->get('/pedidos/crear')
            ->assertOk()
            ->assertSee('Ryzen activo')
            ->assertDontSee('Producto inactivo');
    }

    public function test_no_permite_seleccionar_productos_sin_stock(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes' => Http::response($this->clients()),
            self::PRODUCTOS_URL.'/api/productos' => Http::response([
                $this->product(10, 'Producto agotado', 950, 0),
            ]),
        ]);

        $this->get('/pedidos/crear')
            ->assertOk()
            ->assertSee('Producto agotado')
            ->assertSee('(sin stock)')
            ->assertSee('disabled', false);
    }

    public function test_crea_pedido_desde_web(): void
    {
        $this->fakeSuccessfulServices();

        $this->post('/pedidos', $this->payload());

        $this->assertDatabaseHas('pedidos', [
            'cliente_id' => 1,
            'estado' => 'pendiente',
            'total' => 1900,
        ]);
        $this->assertDatabaseHas('detalle_pedidos', [
            'producto_id' => 10,
            'producto_nombre' => 'Ryzen 5 5600G',
            'cantidad' => 2,
        ]);
    }

    public function test_redirige_al_detalle_despues_de_crear(): void
    {
        $this->fakeSuccessfulServices();

        $response = $this->post('/pedidos', $this->payload());
        $pedido = Pedido::query()->firstOrFail();

        $response
            ->assertRedirect(route('pedidos.show', $pedido))
            ->assertSessionHas('success', 'Pedido creado correctamente.');
    }

    public function test_muestra_detalle_del_pedido(): void
    {
        $pedido = $this->createPedidoConDetalle();

        $this->get("/pedidos/{$pedido->id}")
            ->assertOk()
            ->assertViewIs('pedidos.show')
            ->assertSee("Pedido #{$pedido->id}")
            ->assertSee('Ryzen histÃ³rico');
    }

    public function test_muestra_formulario_editar(): void
    {
        $pedido = $this->createPedidoConDetalle();

        $this->get("/pedidos/{$pedido->id}/editar")
            ->assertOk()
            ->assertViewIs('pedidos.edit')
            ->assertSee('Solo puede modificar el estado, mÃ©todo de pago y fecha.')
            ->assertDontSee('name="cliente_id"', false)
            ->assertDontSee('name="detalles', false);
    }

    public function test_actualiza_pedido(): void
    {
        $pedido = $this->createPedidoConDetalle();

        $this->put("/pedidos/{$pedido->id}", [
            'estado' => 'pagado',
            'metodo_pago' => 'qr',
            'fecha' => '2026-10-07T11:45',
        ])
            ->assertRedirect(route('pedidos.show', $pedido))
            ->assertSessionHas('success', 'Pedido actualizado correctamente.');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'estado' => 'pagado',
            'metodo_pago' => 'qr',
        ]);
    }

    public function test_error_de_clientes_service_se_muestra_correctamente(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes' => Http::failedConnection('Connection refused'),
            self::PRODUCTOS_URL.'/api/productos' => Http::response($this->products()),
        ]);

        $this->get('/pedidos/crear')
            ->assertOk()
            ->assertSee('No se pudieron cargar los clientes porque el servicio no estÃ¡ disponible.');
    }

    public function test_error_de_productos_service_se_muestra_correctamente(): void
    {
        Http::fake([
            self::CLIENTES_URL.'/api/clientes' => Http::response($this->clients()),
            self::PRODUCTOS_URL.'/api/productos' => Http::response([], 500),
        ]);

        $this->get('/pedidos/crear')
            ->assertOk()
            ->assertSee('No se pudieron cargar los productos porque el servicio no estÃ¡ disponible.');
    }

    public function test_paginacion_muestra_diez_registros_por_pagina(): void
    {
        foreach (range(1, 11) as $index) {
            $this->createPedido(clienteId: $index);
        }

        $firstPage = $this->get('/pedidos')->assertOk();
        $secondPage = $this->get('/pedidos?page=2')->assertOk();

        $this->assertCount(10, $firstPage->viewData('pedidos')->items());
        $this->assertCount(1, $secondPage->viewData('pedidos')->items());
        $this->assertSame(2, $secondPage->viewData('pedidos')->currentPage());
    }

    private function fakeSuccessfulServices(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if ($url === self::CLIENTES_URL.'/api/clientes') {
                return Http::response($this->clients());
            }

            if ($url === self::PRODUCTOS_URL.'/api/productos') {
                return Http::response($this->products());
            }

            if ($url === self::CLIENTES_URL.'/api/clientes/1') {
                return Http::response($this->clients()[0]);
            }

            if ($url === self::PRODUCTOS_URL.'/api/productos/10') {
                return Http::response($this->products()[0]);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/descontar-stock')) {
                return Http::response(['message' => 'Stock descontado.']);
            }

            return Http::response([], 500);
        });
    }

    /** @return list<array<string, mixed>> */
    private function clients(): array
    {
        return [[
            'id' => 1,
            'nombre' => 'Ana',
            'apellido' => 'PÃ©rez',
            'email' => 'ana@test.com',
            'activo' => true,
        ]];
    }

    /** @return list<array<string, mixed>> */
    private function products(): array
    {
        return [$this->product(10, 'Ryzen 5 5600G', 950, 20)];
    }

    /** @return array<string, mixed> */
    private function product(int $id, string $name, float|int $price, int $stock): array
    {
        return [
            'id' => $id,
            'nombre' => $name,
            'precio' => $price,
            'stock' => $stock,
            'activo' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'cliente_id' => 1,
            'estado' => 'pendiente',
            'metodo_pago' => 'efectivo',
            'fecha' => '2026-10-06T10:30',
            'detalles' => [[
                'producto_id' => 10,
                'cantidad' => 2,
            ]],
        ];
    }

    private function createPedido(int $clienteId = 1): Pedido
    {
        return Pedido::create([
            'cliente_id' => $clienteId,
            'subtotal' => 950,
            'total' => 950,
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
            'producto_nombre' => 'Ryzen histÃ³rico',
            'precio_unitario' => 950,
            'cantidad' => 1,
            'subtotal' => 950,
        ]);

        return $pedido;
    }
}
