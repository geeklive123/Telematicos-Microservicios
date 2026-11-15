<?php

return [
    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER'),
    'password' => env('RABBITMQ_PASSWORD'),
    'vhost' => env('RABBITMQ_VHOST', '/'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'tienda.events'),
    'queue' => env('RABBITMQ_QUEUE', 'notificaciones.pedidos'),
    'routing_key' => env('RABBITMQ_ROUTING_KEY', 'pedido.creado'),
];
