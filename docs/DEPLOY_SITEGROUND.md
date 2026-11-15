# Preparación para SiteGround — pendiente de verificación

No se ha realizado ningún despliegue. Antes de planificarlo hay que completar y validar la integración, resolver la sesión de las vistas Blade y la revocación distribuida, revisar los avisos de dependencias y comprobar las capacidades del plan SiteGround contratado.

Nombres propuestos, usando el dominio real: `auth-service.<dominio>`, `productos-service.<dominio>`, `clientes-service.<dominio>`, `pedidos-service.<dominio>` y, si se implementa y despliega, `notificaciones-service.<dominio>`. Configurar HTTPS y las URLs HTTPS entre servicios. Cada aplicación necesita PHP 8.4 compatible, dependencias/extensiones requeridas, base independiente, `.env` propio, `APP_KEY` exclusivo y `JWT_SECRET` compartido de manera segura. El document root debe apuntar a `public`, no a la raíz Laravel. Garantizar permisos de escritura apropiados para `storage` y `bootstrap/cache`.

En el entorno de destino y tras respaldar las bases: ejecutar `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force` y `php artisan optimize`, con el PHP 8.4 del servidor. Verificar `GET /api/health` de cada servicio y después el flujo autenticado. No publicar `.env`, credenciales ni las cuentas demo.

RabbitMQ y Docker son objetivos del entorno académico local que todavía no se implementaron en este proyecto. No se ha verificado que SiteGround compartido permita un broker RabbitMQ persistente o consumidores de larga duración; si no los admite, se necesitará infraestructura externa compatible y un diseño de conexión seguro. No asumir su disponibilidad.
