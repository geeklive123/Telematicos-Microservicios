# Auditoría inicial — 2026-11-14

Los cuatro servicios existentes son aplicaciones Laravel independientes. Sus composer.json declaran PHP ^8.3 y Laravel ^13.17. Auth incluye tymon/jwt-auth 2.3.1 y config/jwt.php; su API inicial expone /api/user con Sanctum, sin endpoints JWT funcionales. Los servicios de Productos, Clientes y Pedidos tienen health público y sus rutas REST aún no incluyen protección JWT.

Productos contiene migración de stock_operations; Pedidos contiene migración de pedido_attempts. No se modificaron datos ni migraciones existentes durante esta auditoría.

Productos es un repositorio Git y tiene cambios previos sin confirmar, incluidos archivos de stock y routes/api.php. Auth, Clientes y Pedidos no se detectaron como repositorios Git independientes. No se encontraron Dockerfiles ni Compose propios en la raíz durante la inspección inicial.

Suites iniciales con PHP 8.4: Auth 2 pruebas y 2 aserciones; Productos 53 y 219; Clientes 31 y 97; Pedidos 71 y 304. Todas pasaron. Docker CLI está instalado; el daemon no estaba disponible. El puerto 8000 estaba ocupado por otra aplicación Laravel ajena a este proyecto.

