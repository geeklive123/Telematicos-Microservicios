# Prueba funcional de extremo a extremo

**Fecha:** 14 de noviembre de 2026, hora de La Paz.
**Resultado:** aprobada en el entorno acadÃ©mico Docker Compose. No se utilizaron bases locales externas.

Se verificÃ³ que los cinco servicios HTTP, sus cinco bases MySQL y RabbitMQ estuvieran saludables; el worker de Notificaciones estaba en ejecuciÃ³n y conectado. El flujo atravesÃ³ Auth, Productos, Clientes, Pedidos, el outbox, RabbitMQ y Notificaciones, con bases independientes. La prueba no registra tokens ni credenciales.

## Datos de la prueba

| Dato | Valor |
| --- | --- |
| Producto activo | ID 1, SKU `DEMO-RYZEN-5600G`, precio 950.00 |
| Cliente activo / inactivo | ID 1 / ID 6 |
| Producto activo sin stock | ID 2, SKU `DEMO-E2E-SIN-STOCK` |
| Pedido | ID 1; total 950.00 |
| Intento | `b8070cc5-2fc1-4263-9617-9507a7a0522e`; `completed` |
| Evento | `5f1ddbd2â€¦`; `pedido.creado` |
| Stock inicial / tras compra / tras cancelaciÃ³n | 10 / 9 / 10 |

`CategorySeeder`, `BrandSeeder` y `ProductSeeder` usan `updateOrCreate`; este Ãºltimo tambiÃ©n sobrescribe stock. No se ejecutaron. Se crearon de manera controlada la categorÃ­a y la marca necesarias si no existÃ­an, y dos productos demo mediante la API sin sobrescribir productos existentes.

## LÃ­nea base y pedido

Antes del pedido, los conteos de `stock_operations`, `pedidos`, `pedido_attempts`, `outbox_events`, `processed_events`, `notifications` e `invoices` eran todos cero. El login de admin y `/api/auth/me` respondieron 200 con rol `admin`; los listados autenticados de Productos y Clientes tambiÃ©n respondieron 200.

La creaciÃ³n devolviÃ³ HTTP 201. La base de Pedidos confirmÃ³ un pedido, un detalle, el intento completado y un evento outbox `pending`. El payload correspondÃ­a al pedido y no contenÃ­a el JWT, `Authorization`, `password` ni `secret`. La operaciÃ³n de descuento registrÃ³ stock anterior 10, posterior 9 y la referencia y clave estables del intento.

`outbox:publish --limit=100` publicÃ³ el evento: estado `published`, un intento y `published_at` informado. El broker tenÃ­a el binding `tienda.events` â†’ `notificaciones.pedidos` con routing key `pedido.creado`. El consumidor creÃ³ exactamente un `processed_event`, una notificaciÃ³n y una factura: la notificaciÃ³n referencia el pedido 1 y cliente 1; la factura referencia el pedido 1, importe 950.00 y estado `generated`. La factura es simulada, no tributaria.

Se volviÃ³ a publicar el mismo payload y `event_id` mediante el publicador existente. Los conteos por evento permanecieron en 1/1/1. Tras el consumo, la cola tenÃ­a cero mensajes listos, cero sin confirmar y un consumidor activo.

## CancelaciÃ³n y pruebas negativas

La consulta del pedido respondiÃ³ 200. La cancelaciÃ³n devolviÃ³ 200, estado `cancelado` y repuso el stock de 9 a 10 con una sola operaciÃ³n `restore` y clave `pedido:1:product:1:cancel-restore`. Una segunda cancelaciÃ³n devolviÃ³ 200 y no creÃ³ otra reposiciÃ³n. La transiciÃ³n `cancelado` â†’ `pendiente` devolviÃ³ 422; el estado y el stock permanecieron sin cambios.

| Caso | Resultado |
| --- | --- |
| Listado protegido sin token | 401 |
| Vendedor intenta DELETE de producto, reservado a admin | 403; producto conservado |
| Crear pedido con cliente inactivo | 422 |
| Crear pedido con producto activo sin stock | 409 |

Antes y despuÃ©s de estas dos Ãºltimas solicitudes permanecieron constantes: 1 pedido, 2 operaciones de stock, 1 outbox, 1 evento procesado, 1 notificaciÃ³n y 1 factura. `pedido_attempts` pasÃ³ de 1 a 3 porque se registraron los dos intentos fallidos. El stock del producto principal siguiÃ³ en 10.

Los logs de Pedidos, Productos y el worker no mostraron errores inesperados. Las tres entradas `local.ERROR` de Pedidos correspondieron a las pruebas deliberadas de transiciÃ³n invÃ¡lida, cliente inactivo y stock insuficiente. Todos los contenedores permanecieron saludables al finalizar. La factura simulada generada al crear el pedido permanece registrada despuÃ©s de su cancelaciÃ³n; no se probÃ³ ni se implementÃ³ una nota de crÃ©dito.

No se modificÃ³ cÃ³digo de aplicaciÃ³n ni se ejecutaron nuevamente las suites automatizadas en este bloque. Los registros y el pedido cancelado permanecen en las bases Docker de demostraciÃ³n como evidencia.
