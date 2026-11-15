<?php

use App\Services\RabbitMqConsumer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rabbitmq:consume', function (RabbitMqConsumer $consumer) {
    try {
        $this->info('Esperando eventos de RabbitMQ.');
        $consumer->consume();

        return 0;
    } catch (Throwable $exception) {
        Log::error('El consumidor RabbitMQ se detuvo.', ['error_type' => get_class($exception)]);
        $this->error('El consumidor se detuvo. Revise los registros y la conexiÃ³n.');

        return 1;
    }
})->purpose('Consumir eventos de pedidos de RabbitMQ');
