<?php

namespace Tests\Feature;

use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PedidoCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.productos.url', 'http://productos.test');
        Http::preventStrayRequests();
    }

    public function test_pendiente_puede_pasar_a_pagado_sin_reponer_stock(): void
    {
        Http::fake();
        $pedido = $this->pedido('pendiente');

        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'pagado'])
            ->assertOk()->assertJsonPath('estado', 'pagado');
        Http::assertNothingSent();
    }

    public function test_cancelar_pendiente_repone_todos_los_detalles_una_sola_vez(): void
    {
        Http::fake(['http://productos.test/*' => Http::response([], 200)]);
        $pedido = $this->pedido('pendiente');

        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'cancelado'])
            ->assertOk()->assertJsonPath('estado', 'cancelado');
        $this->assertSame('cancelado', $pedido->fresh()->estado);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://productos.test/api/productos/1/reponer-stock'
            && $request->data()['cantidad'] === 2);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://productos.test/api/productos/7/reponer-stock'
            && $request->data()['cantidad'] === 3);

        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'cancelado'])->assertOk();
        Http::assertSentCount(2);
    }

    public function test_cancelar_pagado_repone_stock(): void
    {
        Http::fake(['http://productos.test/*' => Http::response([], 200)]);
        $pedido = $this->pedido('pagado');

        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'cancelado'])
            ->assertOk()->assertJsonPath('estado', 'cancelado');
        Http::assertSentCount(2);
    }

    public function test_pagado_puede_completarse_sin_reponer_stock(): void
    {
        Http::fake();
        $pedido = $this->pedido('pagado');

        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'completado'])
            ->assertOk()->assertJsonPath('estado', 'completado');
        Http::assertNothingSent();
    }

    public function test_transiciones_no_permitidas_son_rechazadas_sin_cambiar_datos(): void
    {
        Http::fake();
        foreach (['cancelado' => ['pendiente', 'pagado', 'completado'], 'completado' => ['pendiente', 'pagado', 'cancelado'], 'pendiente' => ['completado'], 'pagado' => ['pendiente']] as $actual => $destinos) {
            foreach ($destinos as $destino) {
                $pedido = $this->pedido($actual);
                $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => $destino, 'metodo_pago' => 'qr'])
                    ->assertUnprocessable()->assertExactJson(['message' => 'La transición de estado solicitada no está permitida.']);
                $this->assertSame($actual, $pedido->fresh()->estado);
                $this->assertSame('efectivo', $pedido->fresh()->metodo_pago);
            }
        }
        Http::assertNothingSent();
    }

    public function test_fallo_intermedio_conserva_estado_y_registra_reposicion_parcial(): void
    {
        Http::fake([
            'http://productos.test/api/productos/1/reponer-stock' => Http::response([], 200),
            'http://productos.test/api/productos/7/reponer-stock' => Http::failedConnection('Connection refused'),
        ]);
        $pedido = $this->pedido('pendiente');
        Log::spy();

        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'cancelado', 'metodo_pago' => 'qr'])
            ->assertServiceUnavailable()->assertJsonPath('message', 'No se pudo cancelar el pedido: el servicio de productos no está disponible o rechazó la reposición. La reposición puede ser parcial; revise el stock antes de reintentar.');
        $this->assertSame('pendiente', $pedido->fresh()->estado);
        $this->assertSame('efectivo', $pedido->fresh()->metodo_pago);
        Http::assertSentCount(2);
        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => str_contains($message, 'reposición de stock puede ser parcial')
            && $context['pedido_id'] === $pedido->id
            && $context['productos_repuestos'] === [['producto_id' => 1, 'cantidad' => 2]]
            && $context['producto_en_curso'] === ['producto_id' => 7, 'cantidad' => 3])->once();
    }

    public function test_interfaz_muestra_solo_transiciones_permitidas(): void
    {
        $this->withSession(['auth_token' => 'web-test-token']);
        foreach (['pendiente' => ['pendiente', 'pagado', 'cancelado'], 'pagado' => ['pagado', 'completado', 'cancelado'], 'completado' => ['completado'], 'cancelado' => ['cancelado']] as $actual => $permitidos) {
            $pedido = $this->pedido($actual);
            $response = $this->get("/pedidos/{$pedido->id}/editar")->assertOk();
            foreach (['pendiente', 'pagado', 'completado', 'cancelado'] as $estado) {
                $option = 'value="'.$estado.'"';
                in_array($estado, $permitidos, true) ? $response->assertSee($option, false) : $response->assertDontSee($option, false);
            }
        }
    }

    public function test_conflicto_remoto_conserva_estado_y_responde_409(): void
    {
        Http::fake(['http://productos.test/*' => Http::response([], 409)]);
        $pedido = $this->pedido('pendiente');

        $this->putJson("/api/pedidos/{$pedido->id}", ['estado' => 'cancelado'])->assertConflict();
        $this->assertSame('pendiente', $pedido->fresh()->estado);
        Http::assertSentCount(1);
    }

    public function test_cancelacion_web_y_reintento_muestran_mensajes_correctos(): void
    {
        $this->withSession(['auth_token' => 'web-test-token']);
        Http::fake(['http://productos.test/*' => Http::response([], 200)]);
        $pedido = $this->pedido('pendiente');
        $this->put("/pedidos/{$pedido->id}", ['estado' => 'cancelado'])
            ->assertRedirect(route('pedidos.show', $pedido))
            ->assertSessionHas('success', 'Pedido cancelado correctamente y stock repuesto.');
        $this->put("/pedidos/{$pedido->id}", ['estado' => 'cancelado'])
            ->assertRedirect(route('pedidos.show', $pedido))
            ->assertSessionHas('success', 'Pedido actualizado correctamente.');
        Http::assertSentCount(2);
    }

    public function test_web_informa_transicion_invalida_y_fallo_remoto(): void
    {
        $this->withSession(['auth_token' => 'web-test-token']);
        Http::fake(['http://productos.test/*' => Http::response([], 503)]);
        $finalizado = $this->pedido('completado');
        $this->from('/editar')->put("/pedidos/{$finalizado->id}", ['estado' => 'cancelado'])
            ->assertRedirect('/editar')->assertSessionHas('error', 'La transición de estado solicitada no está permitida.');
        $pendiente = $this->pedido('pendiente');
        $this->from('/editar')->put("/pedidos/{$pendiente->id}", ['estado' => 'cancelado'])
            ->assertRedirect('/editar')->assertSessionHas('error', fn ($message) => str_contains($message, 'servicio de productos no está disponible'));
        $this->assertSame('pendiente', $pendiente->fresh()->estado);
    }

    public function test_no_se_crea_un_pedido_cancelado_que_descuente_stock(): void
    {
        Http::fake();
        $this->postJson('/api/pedidos', [
            'cliente_id' => 1, 'estado' => 'cancelado', 'metodo_pago' => 'efectivo',
            'fecha' => '2026-10-06 10:30:00', 'detalles' => [['producto_id' => 1, 'cantidad' => 2]],
        ])->assertUnprocessable()->assertJsonValidationErrors('estado');
        Http::assertNothingSent();
    }

    private function pedido(string $estado): Pedido
    {
        $pedido = Pedido::create([
            'cliente_id' => 1, 'subtotal' => 5, 'total' => 5, 'estado' => $estado,
            'metodo_pago' => 'efectivo', 'fecha' => '2026-10-06 10:30:00',
        ]);
        foreach ([1 => 2, 7 => 3] as $productoId => $cantidad) {
            $pedido->detalles()->create([
                'producto_id' => $productoId, 'producto_nombre' => 'Producto', 'precio_unitario' => 1,
                'cantidad' => $cantidad, 'subtotal' => $cantidad,
            ]);
        }

        return $pedido;
    }
}
