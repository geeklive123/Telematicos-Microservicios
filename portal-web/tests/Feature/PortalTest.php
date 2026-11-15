<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.auth.url' => 'http://auth.test', 'services.clientes.url' => 'http://clientes.test', 'services.productos.url' => 'http://productos.test', 'services.pedidos.url' => 'http://pedidos.test']);
        Http::preventStrayRequests();
    }

    private function auth(): void
    {
        $this->withSession(['auth_token' => 'jwt-private', 'auth_user' => ['name' => 'Ana', 'role' => 'admin']]);
    }

    public function test_login_correcto_y_token_no_visible(): void
    {
        Http::fake(['http://auth.test/api/auth/login' => Http::response(['access_token' => 'jwt-private', 'user' => ['name' => 'Ana', 'role' => 'admin']])]);
        $this->post('/login', ['email' => 'ana@example.com', 'password' => 'secret'])->assertRedirect('/dashboard')->assertSessionHas('auth_token', 'jwt-private');
        Http::assertSent(fn (Request $request) => $request->url() === 'http://auth.test/api/auth/login' && $request['email'] === 'ana@example.com');
    }

    public function test_login_invalido_y_logout_limpia_sesion(): void
    {
        Http::fake(['http://auth.test/api/auth/login' => Http::response([], 401), 'http://auth.test/api/auth/logout' => Http::failedConnection()]);
        $this->post('/login', ['email' => 'ana@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email')->assertSessionMissing('auth_token');
        $this->auth();
        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('auth_token')->assertSessionMissing('auth_user');
    }

    public function test_dashboard_usa_datos_reales_y_health(): void
    {
        Http::fake(['*/api/health' => Http::response(['status' => 'ok']), 'http://clientes.test/api/clientes' => Http::response([['id' => 1]]), 'http://productos.test/api/productos' => Http::response([['id' => 1, 'activo' => true]]), 'http://pedidos.test/api/pedidos' => Http::response([['id' => 1], ['id' => 2]])]);
        $this->auth();
        $this->get('/dashboard')->assertOk()->assertSee('Panel principal')->assertDontSee('jwt-private')->assertViewHas('totalClientes', 1)->assertViewHas('totalProductos', 1)->assertViewHas('totalPedidos', 2);
        Http::assertSent(fn (Request $r) => $r->url() === 'http://pedidos.test/api/pedidos' && $r->hasHeader('Authorization', 'Bearer jwt-private'));
    }

    public function test_listados_y_formularios_usan_jwt_remoto(): void
    {
        Http::fake(['http://clientes.test/api/clientes' => Http::response([['id' => 1, 'nombre' => 'María', 'apellido' => 'López', 'activo' => true]]), 'http://productos.test/api/productos' => Http::response([['id' => 2, 'nombre' => 'CPU', 'activo' => true, 'stock' => 3, 'precio' => 10]]), 'http://pedidos.test/api/pedidos' => Http::response([])]);
        $this->auth();
        $this->get('/portal/clientes')->assertOk()->assertSee('María');
        $this->get('/portal/productos')->assertOk()->assertSee('CPU');
        $this->get('/pedidos')->assertOk()->assertSee('Pedidos');
        $this->get('/pedidos/crear')->assertOk()->assertSee('María')->assertSee('CPU');
        foreach (Http::recorded() as [$request]) {
            $this->assertTrue($request->hasHeader('Authorization', 'Bearer jwt-private'));
        }
    }

    public function test_401_remoto_invalida_sesion(): void
    {
        Http::fake(['http://clientes.test/api/clientes' => Http::response([], 401)]);
        $this->auth();
        $this->get('/portal/clientes')->assertRedirect('/login')->assertSessionMissing('auth_token')->assertSessionMissing('auth_user')->assertSessionHas('error', 'Tu sesión expiró. Inicia sesión nuevamente.');
    }

    public function test_errores_y_conexion_no_producen_500(): void
    {
        Http::fake(['http://clientes.test/api/clientes' => Http::failedConnection(), 'http://productos.test/api/productos' => Http::response([], 500), 'http://pedidos.test/api/pedidos' => Http::response([], 503), '*/api/health' => Http::failedConnection()]);
        $this->auth();
        $this->get('/dashboard')->assertOk()->assertSee('No disponible');
        $this->get('/portal/clientes')->assertOk()->assertSee('Servicio temporalmente no disponible');
        $this->get('/portal/productos')->assertOk()->assertSee('Servicio temporalmente no disponible');
        $this->get('/pedidos')->assertOk()->assertSee('Servicio temporalmente no disponible');
        $this->get('/pedidos/crear')->assertOk()->assertSee('No se pudieron cargar los clientes');
    }

    public function test_crea_cliente_producto_y_pedido_mediante_api(): void
    {
        Http::fake(['http://clientes.test/api/clientes' => Http::response(['id' => 1], 201), 'http://productos.test/api/productos' => Http::response(['id' => 2], 201), 'http://pedidos.test/api/pedidos' => Http::response(['id' => 3], 201)]);
        $this->auth();
        $this->post('/portal/clientes', ['nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@example.com', 'activo' => 1])->assertRedirect('/portal/clientes');
        $this->post('/portal/productos', ['nombre' => 'CPU', 'categoria_id' => 1, 'precio' => 10, 'stock' => 2, 'activo' => 1])->assertRedirect('/portal/productos');
        $this->post('/pedidos', ['cliente_id' => 1, 'metodo_pago' => 'efectivo', 'fecha' => '2026-11-15', 'detalles' => [['producto_id' => 2, 'cantidad' => 1]]])->assertRedirect('/pedidos/3');
        Http::assertSent(fn (Request $r) => $r->url() === 'http://pedidos.test/api/pedidos' && $r['estado'] === 'pendiente' && $r->hasHeader('Authorization', 'Bearer jwt-private'));
    }

    public function test_edita_y_cancela_pedido_remoto(): void
    {
        Http::fake(['http://pedidos.test/api/pedidos/3' => Http::response(['id' => 3, 'cliente_id' => 1, 'estado' => 'pendiente', 'total' => 10, 'metodo_pago' => 'efectivo', 'fecha' => '2026-11-15', 'detalles' => []])]);
        $this->auth();
        $this->get('/pedidos/3')->assertOk()->assertSee('Pedido #3');
        $this->get('/pedidos/3/editar')->assertOk();
        $this->put('/pedidos/3', ['estado' => 'cancelado'])->assertRedirect('/pedidos/3');
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['estado'] === 'cancelado');
    }

    public function test_timeout_en_creacion_de_pedido_no_expone_token(): void
    {
        Http::fake(['http://pedidos.test/api/pedidos' => Http::failedConnection('Connection timed out')]);
        $this->auth();
        $this->from('/pedidos/crear')->post('/pedidos', ['cliente_id' => 1, 'metodo_pago' => 'efectivo', 'fecha' => '2026-11-15', 'detalles' => [['producto_id' => 2, 'cantidad' => 1]]])
            ->assertRedirect('/pedidos/crear')->assertSessionHas('error', 'Servicio temporalmente no disponible');
    }

    public function test_editar_cliente_inactivo_y_actualizar_con_jwt(): void
    {
        Http::fake(['http://clientes.test/api/clientes/1' => Http::response(['id' => 1, 'nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@example.com', 'activo' => false])]);
        $this->auth();
        $this->get('/portal/clientes/1/editar')->assertOk()->assertSee('value="0" selected', false);
        $this->put('/portal/clientes/1', ['nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@example.com', 'activo' => 1])->assertRedirect('/portal/clientes');
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['activo'] === 1 && $r->hasHeader('Authorization', 'Bearer jwt-private'));
    }

    public function test_401_al_crear_pedido_redirige_a_login(): void
    {
        Http::fake(['http://pedidos.test/api/pedidos' => Http::response([], 401)]);
        $this->auth();
        $this->post('/pedidos', ['cliente_id' => 1, 'metodo_pago' => 'efectivo', 'fecha' => '2026-11-15', 'detalles' => [['producto_id' => 2, 'cantidad' => 1]]])->assertRedirect('/login')->assertSessionMissing('auth_token');
    }
}
