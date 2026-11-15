# Arquitectura implementada

Existen cinco aplicaciones Laravel independientes: auth-service, productos-service, clientes-service, pedidos-service y notificaciones-service. Cada una utiliza su propia base. Auth emite JWT firmados con un secreto de entorno; Productos, Clientes y Pedidos comprueban firma, expiración, sujeto y rol localmente. No consultan auth_db ni usan claves foráneas entre servicios. Pedidos reenvía explícitamente el bearer de la API a Clientes y Productos durante creación, descuento, compensación y cancelación. Las rutas API de health son públicas.

La persistencia de intentos de Pedidos y las operaciones de stock mantienen sus claves idempotentes. Las pruebas con HTTP simulado cubren la propagación durante creación y cancelación. La prueba real JWT sigue pendiente. La interfaz Blade de Pedidos no tiene aún sesión JWT; las vistas Blade de los servicios tampoco están protegidas por los nuevos middleware API.

El blacklist de logout/refresh pertenece al almacenamiento de Auth y no se comparte con los otros servicios; estos aceptan un JWT firmado hasta su expiración aunque Auth lo haya revocado. Debe diseñarse una revocación compartida o documentar y aceptar explícitamente esa ventana antes de producción.

Pedidos incorpora un outbox y un comando para publicar `pedido.creado`; pedido, detalles, intento completado y evento se registran en una transacción local. El flujo es Pedidos → outbox → RabbitMQ → Notificaciones → `processed_events` → notificación y factura simulada. Notificaciones tiene base propia y el consumidor confirma después de la transacción, que usa un identificador único para evitar duplicados. Se probó el consumidor con RabbitMQ real y una base aislada, incluyendo una entrega repetida; la integración integral entre todos los servicios queda pendiente. Los detalles y limitaciones están en [RABBITMQ.md](RABBITMQ.md).

Para demostración local, Compose define cinco servicios HTTP, un worker separado, cinco bases MySQL y RabbitMQ sobre una red interna con volúmenes persistentes. Las instrucciones y limitaciones están en [DOCKER.md](DOCKER.md); no representan un despliegue en SiteGround.
