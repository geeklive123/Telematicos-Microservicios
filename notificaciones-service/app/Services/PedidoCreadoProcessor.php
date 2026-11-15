<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Notification;
use App\Models\ProcessedEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class PedidoCreadoProcessor
{
    public function process(array $payload): bool
    {
        $data = Validator::make($payload, [
            'event_id' => ['required', 'uuid'],
            'event_type' => ['required', Rule::in(['pedido.creado'])],
            'occurred_at' => ['required', 'date'],
            'pedido_id' => ['required', 'integer', 'min:1'],
            'cliente_id' => ['required', 'integer', 'min:1'],
            'total' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'min:1'],
            'detalles.*.nombre_producto' => ['required', 'string', 'max:255'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ])->validate();

        return DB::transaction(function () use ($data): bool {
            $now = now();
            $inserted = DB::table('processed_events')->insertOrIgnore([
                'event_id' => $data['event_id'], 'event_type' => $data['event_type'],
                'processed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($inserted === 0) {
                if (! ProcessedEvent::where('event_id', $data['event_id'])->exists()) {
                    throw new RuntimeException('No se pudo registrar el evento procesado.');
                }

                return false;
            }
            Notification::create([
                'event_id' => $data['event_id'], 'pedido_id' => $data['pedido_id'], 'cliente_id' => $data['cliente_id'],
                'message' => 'Pedido #'.$data['pedido_id'].' registrado correctamente.', 'status' => 'generated',
            ]);
            Invoice::create([
                'event_id' => $data['event_id'], 'pedido_id' => $data['pedido_id'], 'amount' => $data['total'],
                'reference' => 'SIM-'.$data['pedido_id'].'-'.$data['event_id'], 'status' => 'generated',
            ]);

            return true;
        });
    }
}
