<?php

namespace App\Services;

use App\Models\OutboxEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutboxPublisher
{
    public function __construct(private readonly RabbitMqPublisher $publisher) {}

    public function publish(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $event = OutboxEvent::query()->lockForUpdate()->findOrFail($id);
            if ($event->status === 'published') {
                return true;
            }
            $event->attempts++;
            try {
                $this->publisher->publish($event->payload);
                $event->status = 'published';
                $event->published_at = now();
                $event->last_error = null;
            } catch (Throwable $exception) {
                $event->status = 'failed';
                $event->last_error = 'PublicaciÃ³n fallida: '.get_class($exception);
                Log::error('No se pudo publicar un evento de outbox.', ['event_id' => $event->event_id, 'error_type' => get_class($exception)]);
            }
            $event->save();

            return $event->status === 'published';
        });
    }
}
