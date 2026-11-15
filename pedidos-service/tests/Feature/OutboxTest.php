<?php

namespace Tests\Feature;

use App\Exceptions\PedidoCreationException;
use App\Models\OutboxEvent;
use App\Models\PedidoAttempt;
use App\Services\OutboxPublisher;
use App\Services\PedidoService;
use App\Services\RabbitMqPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class OutboxTest extends TestCase
{
    use RefreshDatabase;

    private function createCompletedAttempt(): PedidoAttempt
    {
        $attempt = PedidoAttempt::create([
            'attempt_uuid' => (string) Str::uuid(), 'cliente_id' => 7, 'status' => 'processing',
            'payload' => ['cliente_id' => 7, 'estado' => 'pendiente', 'metodo_pago' => 'efectivo', 'fecha' => '2026-10-06', 'detalles' => [['producto_id' => 10, 'cantidad' => 2]]],
            'resolved_details' => [['producto_id' => 10, 'producto_nombre' => 'CPU', 'precio_unitario' => '12.50', 'cantidad' => 2, 'subtotal' => 25]],
            'stock_operations' => [10 => ['producto_id' => 10, 'cantidad' => 2, 'discount' => 'confirmed']],
        ]);
        app(PedidoService::class)->resumeAttempt($attempt->attempt_uuid, 'token-privado');

        return $attempt->fresh();
    }

    public function test_pedido_crea_evento_sin_secretos_y_reanudar_no_duplica(): void
    {
        $attempt = $this->createCompletedAttempt();
        $event = OutboxEvent::firstOrFail();
        $this->assertSame('completed', $attempt->status);
        $this->assertTrue(Str::isUuid($event->event_id));
        $this->assertSame('pedido.creado', $event->event_type);
        $this->assertSame($event->event_id, $event->payload['event_id']);
        $this->assertSame($attempt->pedido_id, $event->payload['pedido_id']);
        $this->assertSame(7, $event->payload['cliente_id']);
        $this->assertEquals(25, $event->payload['total']);
        $this->assertSame('CPU', $event->payload['detalles'][0]['nombre_producto']);
        $this->assertStringNotContainsString('token-privado', json_encode($event->payload));
        $this->assertStringNotContainsString('Authorization', json_encode($event->payload));
        app(PedidoService::class)->resumeAttempt($attempt->attempt_uuid);
        $this->assertDatabaseCount('outbox_events', 1);
        $this->createCompletedAttempt();
        $this->assertNotSame($event->event_id, OutboxEvent::orderByDesc('id')->firstOrFail()->event_id);
    }

    public function test_fallo_previo_no_crea_evento(): void
    {
        config()->set('services.clientes.url', 'http://clientes.test');
        Http::fake(['http://clientes.test/*' => Http::response(['id' => 1, 'activo' => false])]);
        try {
            app(PedidoService::class)->create(['cliente_id' => 1, 'estado' => 'pendiente', 'metodo_pago' => 'efectivo', 'fecha' => '2026-10-06', 'detalles' => [['producto_id' => 10, 'cantidad' => 1]]]);
            $this->fail('El cliente inactivo debe rechazarse.');
        } catch (PedidoCreationException $exception) {
            $this->assertSame(422, $exception->status());
        }
        $this->assertDatabaseCount('outbox_events', 0);
    }

    public function test_fallo_de_outbox_revierte_pedido_y_completa_compensacion(): void
    {
        config()->set('services.productos.url', 'http://productos.test');
        Http::fake(['http://productos.test/*' => Http::response(['already_processed' => false])]);
        OutboxEvent::creating(fn () => throw new RuntimeException('fallo al guardar outbox'));
        try {
            $this->createCompletedAttempt();
            $this->fail('Debe propagarse el fallo de persistencia.');
        } catch (PedidoCreationException $exception) {
            $this->assertSame(500, $exception->status());
        } finally {
            OutboxEvent::flushEventListeners();
        }
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('detalle_pedidos', 0);
        $this->assertDatabaseCount('outbox_events', 0);
        $this->assertSame('failed', PedidoAttempt::firstOrFail()->status);
        Http::assertSentCount(1);
    }

    public function test_fallo_y_reintento_publican_el_mismo_evento(): void
    {
        $this->createCompletedAttempt();
        $event = OutboxEvent::firstOrFail();
        $broker = $this->mock(RabbitMqPublisher::class);
        $broker->shouldReceive('publish')->once()->with($event->payload)->andThrow(new RuntimeException('secreto no registrar'));
        $this->assertFalse(app(OutboxPublisher::class)->publish($event->id));
        $this->assertSame('failed', $event->fresh()->status);
        $this->assertStringNotContainsString('secreto', $event->fresh()->last_error);
        $broker->shouldReceive('publish')->once()->with($event->payload)->andReturnNull();
        $this->assertTrue(app(OutboxPublisher::class)->publish($event->id));
        $this->assertSame('published', $event->fresh()->status);
        $this->assertSame(2, $event->fresh()->attempts);
        $this->assertNotNull($event->fresh()->published_at);
        $this->assertSame($event->event_id, $event->fresh()->event_id);
        $this->assertDatabaseCount('outbox_events', 1);
    }
}
