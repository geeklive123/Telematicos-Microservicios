<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use JsonException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

class RabbitMqConsumer
{
    public function __construct(private readonly PedidoCreadoProcessor $processor) {}

    public function handle(AMQPMessage $message): void
    {
        try {
            $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($payload) || array_is_list($payload)) {
                throw new JsonException('El evento debe ser un objeto JSON.');
            }
            $this->processor->process($payload);
        } catch (JsonException|ValidationException $exception) {
            Log::warning('Evento RabbitMQ invÃ¡lido rechazado.', ['error_type' => get_class($exception)]);
            $message->reject(false);

            return;
        } catch (Throwable $exception) {
            Log::error('Error al procesar evento RabbitMQ; se detiene el consumidor.', ['error_type' => get_class($exception)]);
            $message->nack(true, false);
            throw new RuntimeException('Error al procesar evento; se reencolÃ³ para revisiÃ³n.', 0, $exception);
        }

        $message->ack();
    }

    public function consume(): void
    {
        $config = config('rabbitmq');
        $connection = new AMQPStreamConnection($config['host'], (int) $config['port'], $config['user'], $config['password'], $config['vhost']);
        try {
            $channel = $connection->channel();
            try {
                $channel->exchange_declare($config['exchange'], 'topic', false, true, false);
                $channel->queue_declare($config['queue'], false, true, false, false);
                $channel->queue_bind($config['queue'], $config['exchange'], $config['routing_key']);
                $channel->basic_qos(null, 1, false);
                $channel->basic_consume($config['queue'], '', false, false, false, false, fn (AMQPMessage $message) => $this->handle($message));
                while ($channel->is_consuming()) {
                    $channel->wait();
                }
            } finally {
                $channel->close();
            }
        } finally {
            $connection->close();
        }
    }
}
