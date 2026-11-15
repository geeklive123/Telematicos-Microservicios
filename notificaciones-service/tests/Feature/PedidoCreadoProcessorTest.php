<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Notification;
use App\Services\PedidoCreadoProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PedidoCreadoProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function event(): array
    {
        return [
            'event_id' => '11111111-1111-4111-8111-111111111111', 'event_type' => 'pedido.creado',
            'occurred_at' => '2026-11-14T12:00:00Z', 'pedido_id' => 123, 'cliente_id' => 42, 'total' => '250.50',
            'detalles' => [['producto_id' => 5, 'nombre_producto' => 'CPU', 'cantidad' => 2, 'precio_unitario' => '125.25']],
        ];
    }

    public function test_health_es_publico(): void
    {
        $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok', 'service' => 'notificaciones']);
    }

    public function test_evento_valido_genera_registros_y_reentrega_es_idempotente(): void
    {
        $event = $this->event();
        $event['Authorization'] = 'Bearer secreto';
        $event['JWT_SECRET'] = 'secreto';
        $event['detalles'][0]['password'] = 'clave';
        $processor = app(PedidoCreadoProcessor::class);
        $this->assertTrue($processor->process($event));
        $this->assertFalse($processor->process($event));
        foreach (['processed_events', 'notifications', 'invoices'] as $table) {
            $this->assertDatabaseCount($table, 1);
            $this->assertStringNotContainsString('secreto', json_encode(DB::table($table)->first()));
            $this->assertStringNotContainsString('clave', json_encode(DB::table($table)->first()));
        }
        $notification = Notification::firstOrFail();
        $this->assertSame('Pedido #123 registrado correctamente.', $notification->message);
        $this->assertSame('generated', $notification->status);
        $invoice = Invoice::firstOrFail();
        $this->assertSame('250.50', $invoice->amount);
        $this->assertSame('SIM-123-'.$event['event_id'], $invoice->reference);
        $this->assertSame('generated', $invoice->status);
    }

    public static function invalidFields(): array
    {
        return [['event_id'], ['pedido_id'], ['cliente_id'], ['total'], ['detalles'], ['occurred_at']];
    }

    #[DataProvider('invalidFields')]
    public function test_falta_campo_no_guarda_registros(string $field): void
    {
        $event = $this->event();
        unset($event[$field]);
        try {
            app(PedidoCreadoProcessor::class)->process($event);
            $this->fail('El evento invÃ¡lido debe rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        foreach (['processed_events', 'notifications', 'invoices'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_tipo_distinto_se_rechaza(): void
    {
        $event = $this->event();
        $event['event_type'] = 'pedido.cancelado';
        try {
            app(PedidoCreadoProcessor::class)->process($event);
            $this->fail('El tipo debe rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('event_type', $exception->errors());
        }
        $this->assertDatabaseCount('processed_events', 0);
    }

    public function test_excepcion_revierte_todos_los_registros_y_permite_reintento(): void
    {
        Invoice::creating(fn () => throw new RuntimeException('fallo simulado'));
        try {
            app(PedidoCreadoProcessor::class)->process($this->event());
            $this->fail('Debe fallar la factura.');
        } catch (RuntimeException $exception) {
            $this->assertSame('fallo simulado', $exception->getMessage());
        } finally {
            Invoice::flushEventListeners();
        }
        foreach (['processed_events', 'notifications', 'invoices'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertTrue(app(PedidoCreadoProcessor::class)->process($this->event()));
        $this->assertDatabaseCount('invoices', 1);
    }
}
