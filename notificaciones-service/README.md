# Notificaciones Service

Servicio Laravel 13 para consumir `pedido.creado`, registrar una notificaciÃ³n local y generar una factura simulada sin validez tributaria. No envÃ­a correo. Puerto local previsto: 8004.

Con PHP 8.4 y Composer, ejecutar `composer install`, copiar `.env.example` a `.env`, configurar una base de datos propia y las variables `RABBITMQ_*`, generar `APP_KEY` con `php artisan key:generate` y ejecutar `php artisan migrate`. No usar `migrate:fresh` en bases existentes.

Ejecutar la API con `php artisan serve --port=8004` y el consumidor como proceso independiente con `php artisan rabbitmq:consume`. `GET /api/health` es pÃºblico. La cola durable `notificaciones.pedidos` recibe eventos del exchange topic `tienda.events` mediante `pedido.creado`.

El procesador valida el mensaje, usa `event_id` Ãºnico y guarda `processed_events`, `notifications` e `invoices` en una transacciÃ³n local. Un duplicado se confirma sin generar registros nuevos. Se rechaza sin reencolar un mensaje invÃ¡lido; sin DLQ se descarta. Un error de procesamiento reencola y detiene el consumidor para revisiÃ³n. MÃ¡s detalles en [docs/RABBITMQ.md](../docs/RABBITMQ.md).

Ejecutar `php artisan test` para las pruebas con SQLite en memoria, sin depender de RabbitMQ. `APP_KEY` de `phpunit.xml` es exclusiva de pruebas y no debe usarse en otro entorno.
