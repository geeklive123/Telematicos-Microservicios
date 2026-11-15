<?php

namespace App\Services;

use App\Exceptions\PedidoCreationException;
use App\Models\OutboxEvent;
use App\Models\Pedido;
use App\Models\PedidoAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PedidoService
{
    public function __construct(
        private readonly ClienteService $clienteService,
        private readonly ProductoService $productoService,
    ) {}

    /**
     * @param  array{
     *     cliente_id: int,
     *     estado: string,
     *     metodo_pago: string,
     *     fecha: string,
     *     detalles: list<array{
     *         producto_id: int,
     *         cantidad: int
     *     }>
     * }  $data
     */
    public function create(array $data, #[\SensitiveParameter] ?string $token = null): Pedido
    {
        $quantities = [];
        foreach ($data['detalles'] as $detalle) {
            $id = (int) $detalle['producto_id'];
            $quantities[$id] = ($quantities[$id] ?? 0) + (int) $detalle['cantidad'];
        }
        ksort($quantities);
        $data['detalles'] = [];
        foreach ($quantities as $id => $quantity) {
            $data['detalles'][] = ['producto_id' => $id, 'cantidad' => $quantity];
        }

        $attempt = PedidoAttempt::create([
            'attempt_uuid' => (string) Str::uuid(), 'cliente_id' => $data['cliente_id'],
            'status' => 'processing', 'payload' => $data, 'stock_operations' => [],
        ]);

        return $this->resumeAttempt($attempt->attempt_uuid, $token);
    }

    public function resumeAttempt(string $uuid, #[\SensitiveParameter] ?string $token = null): Pedido
    {
        $attempt = PedidoAttempt::where('attempt_uuid', $uuid)->firstOrFail();
        if ($attempt->status === 'completed' && $attempt->pedido_id !== null) {
            return $attempt->pedido()->firstOrFail()->load('detalles');
        }
        $claimed = PedidoAttempt::whereKey($attempt->id)->where('status', 'processing')->where('claimed', false)
            ->update(['claimed' => true]);
        if ($claimed !== 1) {
            $attempt->refresh();
            if ($attempt->status === 'completed' && $attempt->pedido_id !== null) {
                return $attempt->pedido()->firstOrFail()->load('detalles');
            }
            throw new PedidoCreationException('El intento no está disponible para reanudarse.', 409);
        }

        $attempt->refresh();
        $commitStarted = false;
        try {
            if (collect($attempt->stock_operations ?? [])->contains(fn ($operation) => isset($operation['restore']))) {
                throw new PedidoCreationException('El intento requiere conciliación de inventario.', 503, null, true);
            }
            if ($attempt->resolved_details === null) {
                $this->clienteService->validarClienteActivo($attempt->cliente_id, $token);
                $details = [];
                foreach ($attempt->payload['detalles'] as $detalle) {
                    $producto = $this->productoService->obtenerProductoDisponible($detalle['producto_id'], $detalle['cantidad'], $token);
                    $details[] = [
                        'producto_id' => $producto['id'], 'producto_nombre' => $producto['nombre'],
                        'precio_unitario' => $producto['precio'], 'cantidad' => $detalle['cantidad'],
                        'subtotal' => round($producto['precio'] * $detalle['cantidad'], 2),
                    ];
                }
                $attempt->update(['resolved_details' => $details]);
            }
            $operations = $attempt->stock_operations ?? [];
            foreach ($attempt->resolved_details as $detalle) {
                $id = $detalle['producto_id'];
                if (($operations[$id]['discount'] ?? null) === 'confirmed') {
                    continue;
                }
                $operations[$id] = ['producto_id' => $id, 'cantidad' => $detalle['cantidad'], 'discount' => 'in_flight'];
                $attempt->update(['stock_operations' => $operations]);
                $this->productoService->descontarStock(
                    $id, $detalle['cantidad'], "pedido-attempt:{$uuid}:product:{$id}:discount", $uuid, $token,
                );
                $operations[$id]['discount'] = 'confirmed';
                $attempt->update(['stock_operations' => $operations]);
            }

            return $this->persistirPedido($attempt, $commitStarted);
        } catch (Throwable $exception) {
            $operations = $attempt->stock_operations ?? [];
            $uncertain = $commitStarted || ($exception instanceof PedidoCreationException && $exception->isUncertain());
            if (! $exception instanceof PedidoCreationException
                && collect($operations)->contains(fn ($operation) => ($operation['discount'] ?? null) === 'in_flight')) {
                $uncertain = true;
            }
            if ($commitStarted) {
                // Una respuesta perdida del COMMIT no permite decidir si existe el pedido.
                $attempt->refresh();
                if ($attempt->status === 'completed' && $attempt->pedido_id !== null) {
                    return $attempt->pedido()->firstOrFail()->load('detalles');
                }
            } elseif (! collect($operations)->contains(fn ($operation) => isset($operation['restore']))) {
                $uncertain = ! $this->compensarDescuentos($attempt, $operations, $token) || $uncertain;
            }
            $attempt->update([
                'status' => $uncertain ? 'needs_reconciliation' : 'failed',
                'error_code' => $uncertain ? 'STOCK_RESULT_UNCERTAIN' : 'CREATION_FAILED',
                'error_message' => $uncertain ? 'El resultado requiere conciliación de inventario.' : 'No se pudo crear el pedido.',
            ]);
            if ($uncertain) {
                throw new PedidoCreationException('El intento requiere conciliación de inventario.', 503, $exception);
            }
            if ($exception instanceof PedidoCreationException) {
                throw $exception;
            }
            Log::error('Falló la persistencia local de un pedido después de descontar stock.', [
                'exception' => $exception,
                'attempt_uuid' => $uuid,
            ]);
            throw new PedidoCreationException('No se pudo crear el pedido.', 500, $exception);
        } finally {
            PedidoAttempt::whereKey($attempt->id)->update(['claimed' => false]);
        }
    }

    /** @param array<string, mixed> $data */
    public function update(Pedido $pedido, array $data, #[\SensitiveParameter] ?string $token = null): Pedido
    {
        $repuestos = [];
        $productoEnCurso = null;

        try {
            return DB::transaction(function () use ($pedido, $data, $token, &$repuestos, &$productoEnCurso): Pedido {
                // El bloqueo se mantiene durante las llamadas HTTP para serializar cancelaciones.
                $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->getKey());
                $pedido->load('detalles');
                $nuevoEstado = $data['estado'] ?? $pedido->estado;

                if (! in_array($nuevoEstado, $pedido->estadosPermitidos(), true)) {
                    throw new PedidoCreationException('La transición de estado solicitada no está permitida.', 422);
                }

                if ($nuevoEstado === 'cancelado' && $pedido->estado !== 'cancelado') {
                    foreach ($pedido->detalles->groupBy('producto_id') as $productoId => $detallesProducto) {
                        $cantidad = $detallesProducto->sum('cantidad');
                        $productoEnCurso = ['producto_id' => (int) $productoId, 'cantidad' => $cantidad];
                        $this->productoService->reponerStock((int) $productoId, $cantidad, "pedido:{$pedido->id}:product:{$productoId}:cancel-restore", 'pedido-cancellation', (string) $pedido->id, $token);
                        $repuestos[] = $productoEnCurso;
                        $productoEnCurso = null;
                    }
                }

                $pedido->fill($data);
                $pedido->save();

                return $pedido;
            });
        } catch (Throwable $exception) {
            if ($productoEnCurso === null && $repuestos === []) {
                throw $exception;
            }

            Log::error('Falló la cancelación del pedido; la reposición de stock puede ser parcial.', [
                'pedido_id' => $pedido->getKey(),
                'productos_repuestos' => $repuestos,
                'producto_en_curso' => $productoEnCurso,
                'exception' => $exception,
            ]);

            if ($exception instanceof PedidoCreationException) {
                throw new PedidoCreationException(
                    'No se pudo cancelar el pedido: el servicio de productos no está disponible o rechazó la reposición. La reposición puede ser parcial; revise el stock antes de reintentar.',
                    $exception->status() === 409 ? 409 : 503,
                    $exception,
                );
            }

            throw new PedidoCreationException(
                'No se pudo cancelar el pedido. La reposición puede ser parcial; revise el stock antes de reintentar.',
                500,
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $detalles
     */
    private function persistirPedido(PedidoAttempt $attempt, bool &$commitStarted): Pedido
    {
        return DB::transaction(function () use ($attempt, &$commitStarted): Pedido {
            $attempt = PedidoAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($attempt->status === 'completed' && $attempt->pedido_id !== null) {
                return $attempt->pedido()->firstOrFail()->load('detalles');
            }
            $data = $attempt->payload;
            $detalles = $attempt->resolved_details;
            $subtotal = round(collect($detalles)->sum('subtotal'), 2);

            $pedido = Pedido::create([
                'cliente_id' => $data['cliente_id'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'estado' => $data['estado'],
                'metodo_pago' => $data['metodo_pago'],
                'fecha' => $data['fecha'],
            ]);

            $pedido->detalles()->createMany($detalles);
            $pedido->load('detalles');
            $attempt->update(['status' => 'completed', 'pedido_id' => $pedido->id]);
            $eventId = (string) Str::uuid();
            OutboxEvent::create([
                'event_id' => $eventId,
                'event_type' => 'pedido.creado',
                'payload' => [
                    'event_id' => $eventId,
                    'event_type' => 'pedido.creado',
                    'occurred_at' => now()->toISOString(),
                    'pedido_id' => $pedido->id,
                    'cliente_id' => $pedido->cliente_id,
                    'total' => $pedido->total,
                    'detalles' => $pedido->detalles->map(fn ($detalle) => [
                        'producto_id' => $detalle->producto_id,
                        'nombre_producto' => $detalle->producto_nombre,
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => $detalle->precio_unitario,
                    ])->all(),
                ],
            ]);
            $commitStarted = true;

            return $pedido;
        });
    }

    /**
     * @param  list<array{producto_id: int, cantidad: int}>  $descuentosRealizados
     */
    private function compensarDescuentos(PedidoAttempt $attempt, array $operations, #[\SensitiveParameter] ?string $token): bool
    {
        $success = true;
        foreach (array_reverse($operations, true) as $id => $descuento) {
            if (($descuento['discount'] ?? null) !== 'confirmed') {
                continue;
            }
            try {
                $operations[$id]['restore'] = 'in_flight';
                $attempt->update(['stock_operations' => $operations]);
                $this->productoService->reponerStock(
                    $descuento['producto_id'], $descuento['cantidad'],
                    "pedido-attempt:{$attempt->attempt_uuid}:product:{$id}:restore", 'pedido-compensation', $attempt->attempt_uuid, $token,
                );
                $operations[$id]['restore'] = 'confirmed';
                $attempt->update(['stock_operations' => $operations]);
            } catch (Throwable $compensationException) {
                $success = false;
                Log::error('No se pudo compensar un descuento de stock.', [
                    'attempt_uuid' => $attempt->attempt_uuid,
                    'producto_id' => $descuento['producto_id'],
                    'cantidad' => $descuento['cantidad'],
                    'exception' => $compensationException,
                ]);
            }
        }

        return $success;
    }
}
