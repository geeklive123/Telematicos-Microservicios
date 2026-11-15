<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.auth.url' => 'http://auth.test', 'services.clientes.url' => 'http://clientes.test', 'services.productos.url' => 'http://productos.test']);
        Http::preventStrayRequests();
    }

    public function test_portal_requiere_sesion(): void
    {
        foreach (['/dashboard', '/portal/clientes', '/portal/productos'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_dashboard_y_modulos_muestran_datos_reales_y_transmiten_jwt(): void
    {
        Http::fake([
            'http://auth.test/up' => Http::response('ok'),
            'http://clientes.test/api/clientes' => Http::response(['data' => [['nombre' => 'Ana', 'apellido' => 'Pérez', 'telefono' => '123', 'direccion' => 'Calle 1', 'activo' => true]]]),
            'http://productos.test/api/productos' => Http::response(['data' => [['nombre' => 'CPU', 'precio' => 50, 'stock' => 2, 'activo' => true], ['nombre' => 'Otro', 'activo' => false]]]),
        ]);
        $this->withSession(['auth_token' => 'jwt-secreto', 'auth_user' => ['name' => 'Ana', 'role' => 'admin']]);
        $this->get('/dashboard')->assertOk()->assertSee('Panel principal')->assertSee('Ana')->assertSee('admin')->assertDontSee('jwt-secreto')->assertViewHas('totalClientes', 1)->assertViewHas('productosActivos', 1)->assertViewHas('pedidosRecientes', 0);
        $this->get('/portal/clientes')->assertOk()->assertSee('Pérez')->assertSee('Teléfono')->assertSee('Dirección');
        $this->get('/portal/productos')->assertOk()->assertSee('CPU')->assertSee('Bajo stock')->assertSee('Inactivo');
        Http::assertSent(fn (Request $request) => $request->url() === 'http://clientes.test/api/clientes' && $request->hasHeader('Authorization', 'Bearer jwt-secreto'));
        Http::assertSent(fn (Request $request) => $request->url() === 'http://productos.test/api/productos' && $request->hasHeader('Authorization', 'Bearer jwt-secreto'));
    }

    public function test_falla_parcial_no_interrumpe_portal_ni_pedidos(): void
    {
        Http::fake(['http://auth.test/up' => Http::failedConnection(), 'http://clientes.test/api/clientes' => Http::response([], 503), 'http://productos.test/api/productos' => Http::response([['id' => 1, 'nombre' => 'CPU', 'precio' => 10, 'stock' => 9, 'activo' => true]])]);
        $this->withSession(['auth_token' => 'jwt', 'auth_user' => ['name' => 'Ana']]);
        $this->get('/dashboard')->assertOk()->assertSee('No disponible')->assertViewHas('totalClientes', null)->assertViewHas('productosActivos', 1);
        $this->get('/portal/clientes')->assertOk()->assertSee('El servicio de clientes no está disponible temporalmente.');
        $this->get('/portal/productos')->assertOk()->assertSee('CPU');
        $this->get('/pedidos')->assertOk();
        $this->get('/pedidos/crear')->assertOk()->assertSee('No se pudieron cargar los clientes');
    }
    public function test_html_usa_utf8_y_conserva_nombres_unicode(): void
    {
        Http::fake([
            'http://clientes.test/api/clientes' => Http::response([['nombre' => 'María', 'apellido' => 'López', 'activo' => true], ['nombre' => 'Juan', 'apellido' => 'Pérez', 'activo' => true]]),
        ]);
        $this->withSession(['auth_token' => 'jwt', 'auth_user' => ['name' => 'Administrador', 'role' => 'admin']]);
        $this->get('/portal/clientes')->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('<meta charset="UTF-8">', false)
            ->assertSee('Administración de Ventas')->assertSee('Teléfono')->assertSee('Dirección')
            ->assertSee('María')->assertSee('López')->assertSee('Pérez')
            ->assertDontSee('AdministraciÃ³n');
    }

    public function test_401_remoto_y_fallo_productos_se_controlan(): void
    {
        Http::fake([
            'http://auth.test/up' => Http::response('ok'),
            'http://clientes.test/api/clientes' => Http::response([], 401),
            'http://productos.test/api/productos' => Http::response([], 401),
        ]);
        $this->withSession(['auth_token' => 'jwt', 'auth_user' => ['name' => 'Ana']]);
        $this->get('/dashboard')->assertOk()->assertSee('No disponible');
        $this->get('/portal/clientes')->assertOk()->assertSee('El servicio de clientes no está disponible temporalmente.');
        $this->get('/portal/productos')->assertOk()->assertSee('El servicio de productos no está disponible temporalmente.');
        $this->get('/pedidos/crear')->assertOk()->assertSee('No se pudieron cargar los productos');
        $this->get('/pedidos')->assertOk();
    }
}
