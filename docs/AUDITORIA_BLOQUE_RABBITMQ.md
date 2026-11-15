# AuditorÃ­a previa: RabbitMQ y Docker

Esta auditorÃ­a registra el estado observado antes de implementar el bloque de mensajerÃ­a. No certifica una prueba integral.

- Los cuatro servicios existentes declaran Laravel `^13.17`, PHP `^8.3` y `tymon/jwt-auth` `^2.3` en Composer.
- Los cuatro exponen `GET /api/health`. Auth expone login, refresh, me y logout; Productos, Clientes y Pedidos protegen sus rutas API con JWT y roles.
- Pedidos obtiene las URLs de Clientes y Productos de `CLIENTES_SERVICE_URL` y `PRODUCTOS_SERVICE_URL`.
- `pedido_attempts` guarda un UUID Ãºnico, estado, datos de solicitud, detalles resueltos y operaciones de stock. Pedido, detalles y estado `completed` del intento se guardan en una transacciÃ³n local.
- `stock_operations` registra una clave de idempotencia Ãºnica, tipo, cantidad y estado. Las cancelaciones usan claves estables de reposiciÃ³n.
- No se encontrÃ³ un servicio de notificaciones ni archivos Docker en la raÃ­z al iniciar esta revisiÃ³n.
- Las pruebas existentes configuran SQLite en memoria. Las cuatro suites se iniciaron en PHP 8.4 mediante Docker, pero aÃºn no se habÃ­a obtenido un resultado al cerrar esta auditorÃ­a; no se confirma aquÃ­ el nÃºmero de pruebas ni de fallos.
- `composer audit --locked` seÃ±alÃ³ avisos en los cuatro servicios, entre ellos Laravel Framework (severidad baja) y Symfony Mime (media); otros avisos carecÃ­an de severidad en la salida consultada. Deben revisarse y repetirse tras cualquier actualizaciÃ³n compatible.

El PHP de XAMPP encontrado en el equipo es 8.2.12, insuficiente para las dependencias del proyecto. Docker respondiÃ³ correctamente al acceder al motor con permisos elevados. Ninguna de estas observaciones demuestra que el entorno Docker de la aplicaciÃ³n estÃ© implementado o validado.
