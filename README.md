# Tienda de componentes de PC

Proyecto universitario de microservicios Laravel 13 y PHP 8.4. Incluye autenticación JWT, outbox de Pedidos y un consumidor RabbitMQ con notificación y facturación simuladas. La integración funcional completa y la autenticación de las vistas Blade siguen pendientes.

| Servicio | Puerto previsto | Base |
| --- | --- | --- |
| Auth | 8000 | auth_db |
| Productos | 8001 | productos_db |
| Clientes | 8002 | clientes_db |
| Pedidos | 8003 | pedidos_db |
| Notificaciones | 8004 | notificaciones_db |

Cada servicio conserva sus datos en su base. Pedidos se comunica por HTTP con Clientes y Productos y registra eventos en outbox para su publicación en RabbitMQ. Los intentos de creación, descuentos y reposiciones usan identificadores de idempotencia; ante resultados inciertos se requiere conciliación.

Auth emite JWT con rol `admin` o `vendedor`; las APIs verifican firma y expiración usando un `JWT_SECRET` compartido en variables de entorno. Pedidos propaga el bearer recibido a las consultas, descuentos y reposiciones. `GET /api/health` es público. El logout y refresh invalidan el token en Auth, pero los otros servicios validan localmente y no consultan la lista negra de Auth: un token revocado puede seguir siendo aceptado allí hasta expirar. Las páginas Blade no tienen autenticación integrada, y la interfaz de Pedidos todavía no aporta el token necesario para sus llamadas a Clientes y Productos. No usar esta configuración como seguridad de producción.

En cada servicio instalar dependencias con Composer ejecutado con PHP 8.4, configurar su `.env`, `APP_KEY`, base propia y un mismo `JWT_SECRET` privado. Ejecutar `php artisan migrate` y `php artisan test`; no usar `migrate:fresh` en bases de desarrollo. El README de Auth describe las cuentas de demostración. La generación de `JWT_SECRET` se realiza en Auth con `php artisan jwt:secret` y debe compartirse de forma segura, nunca en Git.

La infraestructura local se describe en [docs/DOCKER.md](docs/DOCKER.md), la mensajería en [docs/RABBITMQ.md](docs/RABBITMQ.md) y la arquitectura en [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md). La prueba integral con servicios reales sigue pendiente. SiteGround no se configura en este bloque.
