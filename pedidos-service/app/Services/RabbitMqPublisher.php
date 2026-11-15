<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

class RabbitMqPublisher
{
    public function publish(array $payload): void
    {
        $config = config('rabbitmq');
        $connection = new AMQPStreamConnection($config['host'], (int) $config['port'], $config['user'], $config['password'], $config['vhost']);
        try {
            $channel = $connection->channel();
            try {
                $channel->exchange_declare($config['exchange'], 'topic', false, true, false);
                $channel->queue_declare($config['queue'], false, true, false, false);
                $channel->queue_bind($config['queue'], $config['exchange'], $config['routing_key']);
                $returned = false;
                $nacked = false;
                $channel->set_return_listener(function () use (&$returned): void {
                    $returned = true;
                });
                $channel->set_nack_handler(function () use (&$nacked): void {
                    $nacked = true;
                });
                $channel->confirm_select();
                $message = new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR), ['content_type' => 'application/json', 'delivery_mode' => 2, 'message_id' => $payload['event_id']]);
                $channel->basic_publish($message, $config['exchange'], $config['routing_key'], true);
                $channel->wait_for_pending_acks_returns(10);
                if ($returned || $nacked) {
                    throw new RuntimeException('El broker rechazó o no pudo enrutar el evento.');
                }
            } finally {
                $channel->close();
            }
        } finally {
            $connection->close();
        }
    }
}
