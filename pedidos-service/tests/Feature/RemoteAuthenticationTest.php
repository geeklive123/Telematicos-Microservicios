<?php

namespace Tests\Feature;

use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RemoteAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.auth.url' => 'http://auth.test', 'services.clientes.url' => 'http://clientes.test', 'services.productos.url' => 'http://productos.test']);
        Http::preventStrayRequests();
    }

    private function sessionWeb(): void
    {
        $this->withSession(['auth_token' => 'jwt-privado', 'auth_user' => ['name' => 'Ana', 'role' => 'admin']]);
    }

    public function test_portal_cliente_rechaza_sesion_expirada(): void
    {
        Http::fake(['http://clientes.test/api/clientes' => Http::response([], 401)]);
        $this->sessionWeb();
        $this->get('/portal/clientes')->assertRedirect(route('login'))->assertSessionMissing('auth_token')->assertSessionMissing('auth_user')->assertSessionHas('error', 'Tu sesión expiró. Inicia sesión nuevamente.');
        $this->get('/login')->assertOk()->assertSee('Tu sesión expiró. Inicia sesión nuevamente.');
    }

    public function test_portal_producto_y_dashboard_rechazan_sesion_expirada(): void
    {
        Http::fake(['http://clientes.test/api/clientes' => Http::response([]), 'http://productos.test/api/productos' => Http::response([], 401)]);
        $this->sessionWeb();
        $this->get('/portal/productos')->assertRedirect(route('login'))->assertSessionMissing('auth_token');
        $this->sessionWeb();
        $this->get('/dashboard')->assertRedirect(route('login'))->assertSessionMissing('auth_user');
    }

    public function test_crear_formulario_y_guardar_rechazan_401(): void
    {
        Http::fake(['http://clientes.test/api/clientes' => Http::response([], 401), 'http://clientes.test/api/clientes/1' => Http::response([], 401)]);
        $this->sessionWeb();
        $this->get('/pedidos/crear')->assertRedirect(route('login'))->assertSessionMissing('auth_token');
        $this->sessionWeb();
        $this->post('/pedidos', $this->payload())->assertRedirect(route('login'))->assertSessionMissing('auth_token')->assertSessionMissing('auth_user');
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_cancelacion_con_401_redirige_y_conserva_estado(): void
    {
        $pedido = Pedido::create(['cliente_id' => 1, 'subtotal' => 10, 'total' => 10, 'estado' => 'pendiente', 'metodo_pago' => 'efectivo', 'fecha' => '2026-10-06']);
        $pedido->detalles()->create(['producto_id' => 10, 'producto_nombre' => 'CPU', 'precio_unitario' => 10, 'cantidad' => 1, 'subtotal' => 10]);
        Http::fake(['http://productos.test/api/productos/10/reponer-stock' => Http::response([], 401)]);
        $this->sessionWeb();
        $this->put("/pedidos/{$pedido->id}", ['estado' => 'cancelado'])->assertRedirect(route('login'))->assertSessionMissing('auth_token');
        $this->assertSame('pendiente', $pedido->fresh()->estado);
    }

    public function test_401_en_descuento_preserva_conciliacion_y_cierra_sesion(): void
    {
        Http::fake(function (Request $request) {
            if ($request->url() === 'http://clientes.test/api/clientes/1') {
                return Http::response(['id' => 1, 'activo' => true]);
            }
            if (preg_match('~/api/productos/(10|20)$~', $request->url(), $matches)) {
                return Http::response(['id' => (int) $matches[1], 'nombre' => 'CPU', 'precio' => 10, 'stock' => 5, 'activo' => true]);
            }
            if (str_contains($request->url(), '/10/descontar-stock')) {
                return Http::response([]);
            }

            return Http::response([], 401);
        });
        $this->sessionWeb();
        $payload = $this->payload();
        $payload['detalles'][] = ['producto_id' => 20, 'cantidad' => 1];
        $this->post('/pedidos', $payload)->assertRedirect(route('login'))->assertSessionMissing('auth_token');
        $this->assertDatabaseHas('pedido_attempts', ['status' => 'needs_reconciliation']);
        $this->assertDatabaseCount('pedidos', 0);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/10/reponer-stock'));
    }

    public function test_api_remoto_401_responde_json_sin_redireccion(): void
    {
        Http::fake(['http://clientes.test/api/clientes/1' => Http::response([], 401)]);
        $this->postJson('/api/pedidos', $this->payload())->assertUnauthorized()->assertJson(['message' => 'No autenticado.'])->assertHeaderMissing('Location');
    }

    public function test_auth_health_y_jwt_valido(): void
    {
        Http::fake(['http://clientes.test/api/clientes' => Http::response([]), 'http://productos.test/api/productos' => Http::response([]), 'http://auth.test/api/health' => Http::response(['status' => 'ok'])]);
        $this->sessionWeb();
        $this->get('/dashboard')->assertOk()->assertViewHas('servicios', fn ($services) => $services['Auth Service'] === true);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://auth.test/api/health');
        Http::assertSent(fn (Request $request) => $request->url() === 'http://clientes.test/api/clientes' && $request->hasHeader('Authorization', 'Bearer jwt-privado'));
        Http::assertSent(fn (Request $request) => $request->url() === 'http://productos.test/api/productos' && $request->hasHeader('Authorization', 'Bearer jwt-privado'));
    }

    private function payload(): array
    {
        return ['cliente_id' => 1, 'estado' => 'pendiente', 'metodo_pago' => 'efectivo', 'fecha' => '2026-10-06', 'detalles' => [['producto_id' => 10, 'cantidad' => 1]]];
    }
}
