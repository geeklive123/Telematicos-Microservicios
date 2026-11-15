# Resultados de pruebas acadÃ©micas

## Funcionales

La prueba real previa del 14 de noviembre de 2026 estÃ¡ detallada en [PRUEBA_END_TO_END.md](PRUEBA_END_TO_END.md): login y `/me` 200, consultas autenticadas 200, pedido 201, descuento y reposiciÃ³n correctos, outbox publicado, evento procesado, notificaciÃ³n y factura simulada creadas; duplicado sin registros adicionales. TambiÃ©n se verificaron 401 sin token, 403 para vendedor en acciÃ³n administrativa, cliente inactivo 422 y producto sin stock 409.

En este bloque se comprobÃ³ ademÃ¡s login con credenciales invÃ¡lidas **401** y `POST /api/pedidos` sin token **401**. No existe una ruta pÃºblica de registro en las rutas Auth inspeccionadas; el registro de usuarios no se declara probado. Las cuentas demo provienen del seeder.

## Rendimiento y escalabilidad bÃ¡sica

En 800 solicitudes medidas, con concurrencia fija de 5, hubo 800 respuestas HTTP 200. Los lotes fueron de 10, 50 y 100 solicitudes por endpoint. El login de 100 solicitudes tuvo media de 928.22 ms y 5.27 req/s; Productos 61.71 ms y 79.48 req/s; Clientes 51.47 ms y 95.15 req/s; Pedidos 53.78 ms y 91.00 req/s; Health 23.03 ms y 211.08 req/s. Son mediciones locales acadÃ©micas, no resultados de producciÃ³n ni prueba de escalado horizontal. La metodologÃ­a, las tres cargas, mÃ¡ximos, mÃ­nimos y recursos estÃ¡n en [PRUEBAS_RENDIMIENTO.md](PRUEBAS_RENDIMIENTO.md).

## ComunicaciÃ³n y disponibilidad

Pedidos alcanzÃ³ Productos y Clientes por HTTP interno. Se confirmÃ³ el binding de RabbitMQ y la publicaciÃ³n de un evento ya procesado desde Pedidos. Con el worker detenido, el broker retuvo un mensaje; tras reiniciarlo, el mensaje se consumiÃ³ y los registros permanecieron en 1/1/1. Los cinco servicios HTTP siguieron respondiendo health 200 durante la pausa. La prueba no simula una caÃ­da del broker ni mide recuperaciÃ³n prolongada.

Al finalizar, los servicios HTTP, bases y RabbitMQ estaban saludables y el worker activo. No se repitieron todas las suites automatizadas ni se modificÃ³ lÃ³gica de aplicaciÃ³n en este bloque.
