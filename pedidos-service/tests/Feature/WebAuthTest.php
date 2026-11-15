<?php

namespace Tests\Feature;

use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.auth.url' => 'http://auth.test', 'services.clientes.url' => 'http://clientes.test', 'services.productos.url' => 'http://productos.test']);
        Http::preventStrayRequests();
    }

    public function test_pedidos_sin_sesion_redirige_a_login_y_la_raiz_respeta_sesion(): void
    {
        $this->get('/pedidos')->assertRedirect(route('login'));
        $this->get('/')->assertRedirect(route('login'));
        $this->withSession(['auth_token' => 'jwt-privado'])->get('/')->assertRedirect(route('dashboard'));
        $this->get('/pedidos')->assertOk()->assertDontSee('jwt-privado');
    }

    public function test_pagina_login_muestra_campos_y_csrf(): void
    {
        $this->get('/login')->assertOk()->assertSee('Acceso al sistema')->assertSee('Correo electrónico')
            ->assertSee('Contraseña')->assertSee('name="_token"', false)->assertDontSee('Laravel');
    }

    public function test_login_guarda_token_y_usuario_sanitizado(): void
    {
        Http::fake(['http://auth.test/api/auth/login' => Http::response([
            'access_token' => 'jwt-ejemplo', 'user' => ['id' => 5, 'name' => 'Ana', 'email' => 'ana@example.com', 'role' => 'admin', 'password' => 'no-guardar'],
        ])]);
        $this->post('/login', ['email' => 'ana@example.com', 'password' => 'clave-secreta'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('auth_token', 'jwt-ejemplo')
            ->assertSessionHas('auth_user', ['id' => 5, 'name' => 'Ana', 'email' => 'ana@example.com', 'role' => 'admin']);
        $this->assertNull(session()->get('password'));
        Http::assertSent(fn (Request $request) => $request->url() === 'http://auth.test/api/auth/login'
            && $request->data() === ['email' => 'ana@example.com', 'password' => 'clave-secreta']);
        $this->get('/pedidos')->assertOk()->assertSee('Ana')->assertSee('admin')->assertSee('Cerrar sesión')->assertDontSee('jwt-ejemplo');
    }

    public function test_credenciales_invalidas_no_guardan_password_ni_token(): void
    {
        Http::fake(['http://auth.test/api/auth/login' => Http::response(['message' => 'Credenciales incorrectas.'], 401)]);
        $this->from('/login')->post('/login', ['email' => 'ana@example.com', 'password' => 'clave-secreta'])
            ->assertRedirect('/login')->assertSessionHasErrors('email')->assertSessionMissing('auth_token');
        $this->assertNull(session()->getOldInput('password'));
        $this->assertStringContainsString('Verifique sus credenciales', session('errors')->first('email'));
    }

    public function test_validacion_y_fallo_de_conexion_se_manejan_sin_guardar_password(): void
    {
        Http::fake(['http://auth.test/api/auth/login' => Http::failedConnection()]);
        $this->from('/login')->post('/login', ['email' => 'no-es-email', 'password' => 'clave'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        Http::assertNothingSent();
        $this->from('/login')->post('/login', ['email' => 'ana@example.com', 'password' => 'clave'])
            ->assertRedirect('/login')->assertSessionHasErrors('email')->assertSessionMissing('auth_token');
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_login_invalido_muestra_el_error_tras_redireccion(): void
    {
        Http::fake(['http://auth.test/api/auth/login' => Http::response([], 401)]);
        $this->followingRedirects()->from('/login')->post('/login', ['email' => 'ana@example.com', 'password' => 'clave'])
            ->assertOk()->assertSee('Verifique sus credenciales');
    }

    public function test_respuesta_auth_invalida_no_autentica(): void
    {
        Http::fake(['http://auth.test/api/auth/login' => Http::response(['access_token' => '', 'user' => []])]);
        $this->from('/login')->post('/login', ['email' => 'ana@example.com', 'password' => 'clave'])
            ->assertRedirect('/login')->assertSessionHasErrors('email')->assertSessionMissing('auth_token');
    }

    public function test_logout_notifica_auth_y_borra_la_sesion(): void
    {
        Http::fake(['http://auth.test/api/auth/logout' => Http::response(['message' => 'ok'])]);
        $this->withSession(['auth_token' => 'jwt-ejemplo', 'auth_user' => ['name' => 'Ana']])
            ->post('/logout')->assertRedirect(route('login'))->assertSessionMissing('auth_token')->assertSessionMissing('auth_user');
        Http::assertSent(fn (Request $request) => $request->url() === 'http://auth.test/api/auth/logout' && $request->hasHeader('Authorization', 'Bearer jwt-ejemplo'));
    }

    public function test_logout_limpia_sesion_aunque_auth_falle(): void
    {
        Http::fake(['http://auth.test/api/auth/logout' => Http::failedConnection()]);
        $this->withSession(['auth_token' => 'jwt-ejemplo', 'auth_user' => ['name' => 'Ana']])
            ->post('/logout')->assertRedirect(route('login'))->assertSessionMissing('auth_token')->assertSessionMissing('auth_user');
    }

    public function test_no_acepta_guardar_token_en_driver_cookie(): void
    {
        config(['session.driver' => 'cookie']);
        $this->post('/login', ['email' => 'ana@example.com', 'password' => 'clave'])->assertServiceUnavailable();
        Http::assertNothingSent();
    }

    public function test_token_se_propaga_en_formulario_creacion_y_cancelacion(): void
    {
        Http::fake(function (Request $request) {
            if ($request->url() === 'http://clientes.test/api/clientes') {
                return Http::response([['id' => 1, 'nombre' => 'Ana', 'activo' => true]]);
            }
            if ($request->url() === 'http://productos.test/api/productos') {
                return Http::response([['id' => 10, 'nombre' => 'CPU', 'precio' => 100, 'stock' => 5, 'activo' => true]]);
            }
            if ($request->url() === 'http://clientes.test/api/clientes/1') {
                return Http::response(['id' => 1, 'activo' => true]);
            }
            if ($request->url() === 'http://productos.test/api/productos/10') {
                return Http::response(['id' => 10, 'nombre' => 'CPU', 'precio' => 100, 'stock' => 5, 'activo' => true]);
            }

            return Http::response(['already_processed' => false]);
        });
        $this->withSession(['auth_token' => 'jwt-web', 'auth_user' => ['name' => 'Ana']]);
        $this->get('/pedidos/crear')->assertOk();
        $this->post('/pedidos', ['cliente_id' => 1, 'estado' => 'pendiente', 'metodo_pago' => 'efectivo', 'fecha' => '2026-10-06', 'detalles' => [['producto_id' => 10, 'cantidad' => 1]]])
            ->assertRedirect();
        $pedido = Pedido::firstOrFail();
        $this->put("/pedidos/{$pedido->id}", ['estado' => 'cancelado'])->assertRedirect();
        $this->assertSame('cancelado', $pedido->fresh()->estado);
        Http::assertSentCount(6);
        foreach (Http::recorded() as [$request]) {
            $this->assertTrue($request->hasHeader('Authorization', 'Bearer jwt-web'), $request->url());
        }
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/reponer-stock'));
    }

    public function test_api_jwt_continua_operativa_sin_sesion_web(): void
    {
        $this->getJson('/api/pedidos')->assertOk();
        $this->withHeader('Authorization', '')->getJson('/api/pedidos')->assertUnauthorized();
    }
}
