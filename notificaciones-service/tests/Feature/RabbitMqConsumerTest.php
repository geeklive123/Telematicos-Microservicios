<?php

namespace Tests\Feature;

use App\Services\PedidoCreadoProcessor;
use App\Services\RabbitMqConsumer;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Tests\TestCase;

class RabbitMqConsumerTest extends TestCase
{
    public function test_exito_y_duplicado_reciben_ack(): void
    {
        $processor = $this->mock(PedidoCreadoProcessor::class);
        $processor->shouldReceive('process')->twice()->with(['event_id' => 'id'])->andReturn(true, false);
        $message = $this->mock(AMQPMessage::class);
        $message->shouldReceive('getBody')->twice()->andReturn('{"event_id":"id"}');
        $message->shouldReceive('ack')->twice();
        $consumer = app(RabbitMqConsumer::class);
        $consumer->handle($message);
        $consumer->handle($message);
    }

    public function test_json_corrupto_se_rechaza_sin_reencolar(): void
    {
        $message = $this->mock(AMQPMessage::class);
        $message->shouldReceive('getBody')->once()->andReturn('{');
        $message->shouldReceive('reject')->once()->with(false);
        app(RabbitMqConsumer::class)->handle($message);
    }

    public function test_error_temporal_reencola_y_detiene_consumidor(): void
    {
        $processor = $this->mock(PedidoCreadoProcessor::class);
        $processor->shouldReceive('process')->once()->andThrow(new RuntimeException('secreto'));
        $message = $this->mock(AMQPMessage::class);
        $message->shouldReceive('getBody')->once()->andReturn('{"event_id":"id"}');
        $message->shouldReceive('nack')->once()->with(true, false);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('se reencolÃ³');
        app(RabbitMqConsumer::class)->handle($message);
    }
}
