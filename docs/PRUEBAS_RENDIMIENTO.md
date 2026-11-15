# Pruebas de rendimiento y comunicaciÃ³n

**Fecha y zona:** 14 de noviembre de 2026, 22:59:13â€“22:59:50, UTCâˆ’04:00 (La Paz). Prueba acadÃ©mica en entorno local Docker, no benchmark de producciÃ³n.

El equipo visible tiene Intel Core i5-13450HX, 10 nÃºcleos y 16 procesadores lÃ³gicos. Docker Engine 29.6.1 informÃ³ 16 CPU y 8.172.228.608 bytes de memoria disponibles. Se usÃ³ Python 3.14.5 y Docker Compose con cinco aplicaciones Laravel, cinco bases MySQL y RabbitMQ. El servidor PHP integrado es para desarrollo; el tamaÃ±o de los datos era pequeÃ±o. No hubo reconstrucciones durante la mediciÃ³n.

## MetodologÃ­a

Se comprobÃ³ previamente que los servicios, bases y broker estuvieran saludables y que el worker consumiera. El script [measure.py](../tools/performance/measure.py) usa solo la biblioteca estÃ¡ndar de Python. Obtuvo un JWT con la cuenta demo, hizo una solicitud de calentamiento por endpoint y ejecutÃ³ tres lotes por endpoint, con **concurrencia fija de 5** y timeout de 30 segundos. Los casos se ejecutaron secuencialmente por endpoint y nivel. Se enviaron 800 solicitudes medidas: 160 por cada uno de los cinco endpoints; el calentamiento y el login inicial no estÃ¡n incluidos.

Los tiempos son latencias observadas desde el cliente local, en milisegundos; incluyen espera en cola y tiempo de red local. Solicitudes por segundo es solicitudes completadas divididas entre la duraciÃ³n de cada lote. Se cuentan como Ã©xito los HTTP 2xx; HTTP no 2xx, errores de conexiÃ³n y timeout cuentan como fallos. Los datos crudos, incluidos inicio y fin de cada lote, cÃ³digos y duraciÃ³n, estÃ¡n en [resultados-locales.json](../tools/performance/resultados-locales.json). No contienen credenciales ni JWT.

Para repetir, con el entorno Docker iniciado, ejecutar desde la raÃ­z `python tools/performance/measure.py --concurrency 5 --output tools/performance/otro-resultado.json`. El script solicita la contraseÃ±a demo sin mostrarla, o acepta `PERF_DEMO_PASSWORD` en el entorno del proceso. No guardar esa variable en archivos versionados.

## Tabla general

La columna Â«SolicitudesÂ» es por endpoint; el total es cinco veces ese valor. El promedio resume las solicitudes de los cinco endpoints con igual nÃºmero de solicitudes en cada caso; el mÃ¡ximo es la mayor latencia observada. Como mezcla login y consultas, usar la tabla detallada para comparar operaciones.

| Carga | Solicitudes | Tiempo promedio | Tiempo mÃ¡ximo | Errores | ObservaciÃ³n |
| --- | ---: | ---: | ---: | ---: | --- |
| Baja | 10 | 183.01 ms | 977.24 ms | 0/50 (0 %) | Login domina el promedio |
| Media | 50 | 221.73 ms | 1072.33 ms | 0/250 (0 %) | Producto presenta su mÃ¡ximo mÃ¡s alto |
| Alta | 100 | 223.64 ms | 1097.65 ms | 0/500 (0 %) | Login se mantiene cerca de 5.27 req/s |

## Resultados detallados

Los endpoints son `POST :8000/api/auth/login`, `GET :8001/api/productos`, `GET :8002/api/clientes`, `GET :8003/api/pedidos` y `GET :8000/api/health`. Todas las respuestas medidas fueron HTTP 200.

| Endpoint | Carga | Total | Ã‰xito | Fallos | Media ms | MÃ­n. ms | MÃ¡x. ms | Req/s | Error % | Inicioâ€“fin local |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| Login | Baja | 10 | 10 | 0 | 762.52 | 189.99 | 977.24 | 5.24 | 0 | 22:59:13â€“22:59:15 |
| Login | Media | 50 | 50 | 0 | 906.54 | 195.73 | 1072.33 | 5.28 | 0 | 22:59:15â€“22:59:24 |
| Login | Alta | 100 | 100 | 0 | 928.22 | 188.06 | 1097.65 | 5.27 | 0 | 22:59:24â€“22:59:43 |
| Productos | Baja | 10 | 10 | 0 | 46.09 | 14.54 | 59.35 | 87.46 | 0 | 22:59:43â€“22:59:44 |
| Productos | Media | 50 | 50 | 0 | 72.93 | 12.32 | 132.53 | 66.20 | 0 | 22:59:44â€“22:59:44 |
| Productos | Alta | 100 | 100 | 0 | 61.71 | 14.82 | 83.57 | 79.48 | 0 | 22:59:44â€“22:59:46 |
| Clientes | Baja | 10 | 10 | 0 | 40.20 | 12.33 | 52.27 | 96.77 | 0 | 22:59:46â€“22:59:46 |
| Clientes | Media | 50 | 50 | 0 | 53.68 | 13.99 | 66.99 | 89.23 | 0 | 22:59:46â€“22:59:46 |
| Clientes | Alta | 100 | 100 | 0 | 51.47 | 11.58 | 63.68 | 95.15 | 0 | 22:59:46â€“22:59:47 |
| Pedidos | Baja | 10 | 10 | 0 | 44.73 | 14.82 | 57.03 | 90.20 | 0 | 22:59:47â€“22:59:47 |
| Pedidos | Media | 50 | 50 | 0 | 54.00 | 12.47 | 64.98 | 88.14 | 0 | 22:59:47â€“22:59:48 |
| Pedidos | Alta | 100 | 100 | 0 | 53.78 | 11.85 | 67.75 | 91.00 | 0 | 22:59:48â€“22:59:49 |
| Health Auth | Baja | 10 | 10 | 0 | 21.50 | 9.78 | 34.94 | 177.57 | 0 | 22:59:49â€“22:59:49 |
| Health Auth | Media | 50 | 50 | 0 | 21.52 | 8.20 | 34.56 | 221.38 | 0 | 22:59:49â€“22:59:49 |
| Health Auth | Alta | 100 | 100 | 0 | 23.03 | 6.23 | 38.38 | 211.08 | 0 | 22:59:49â€“22:59:50 |

No se observaron respuestas 429 ni otros errores durante los lotes de login. Un Ãºnico lote por caso no permite concluir que no exista limitaciÃ³n en otras condiciones.

## CPU y memoria

Una muestra de `docker stats --no-stream` durante el lote de login registrÃ³ Auth en **98.55 % CPU y 62.03 MiB / 7.611 GiB**. Es una muestra puntual, no un promedio ni un mÃ¡ximo garantizado. La tabla siguiente es otra muestra tomada despuÃ©s de las pruebas y de reiniciar el worker; el lÃ­mite mostrado es el lÃ­mite visible por contenedor, no memoria reservada. Datos crudos: [docker-stats-final.txt](../tools/performance/docker-stats-final.txt).

| Contenedor | CPU | Memoria / lÃ­mite |
| --- | ---: | --- |
| Auth | 0.02 % | 61.74 MiB / 7.611 GiB |
| Productos | 0.03 % | 61.45 MiB / 7.611 GiB |
| Clientes | 0.03 % | 58.33 MiB / 7.611 GiB |
| Pedidos | 0.03 % | 68.19 MiB / 7.611 GiB |
| Notificaciones HTTP | 0.02 % | 56.58 MiB / 7.611 GiB |
| Worker | 0.00 % | 34.39 MiB / 7.611 GiB |
| RabbitMQ | 0.57 % | 128.5 MiB / 7.611 GiB |
| Auth DB | 0.78 % | 450.3 MiB / 7.611 GiB |
| Productos DB | 0.69 % | 458.4 MiB / 7.611 GiB |
| Clientes DB | 0.75 % | 457.1 MiB / 7.611 GiB |
| Pedidos DB | 0.69 % | 452.9 MiB / 7.611 GiB |
| Notificaciones DB | 0.91 % | 473.2 MiB / 7.611 GiB |

## Escalabilidad observada y lÃ­mites

El login aumentÃ³ de 762.52 a 928.22 ms de media entre los lotes de 10 y 100, mientras el throughput permaneciÃ³ entre 5.24 y 5.28 req/s. Las lecturas de Clientes y Pedidos permanecieron alrededor de 89â€“97 y 88â€“91 req/s, respectivamente; Productos variÃ³ entre 66 y 87 req/s y su mayor latencia ocurriÃ³ en la carga media. Health estuvo entre 178 y 221 req/s. NingÃºn lote produjo errores.

Las cargas representan **distinto nÃºmero total de solicitudes, no distintos niveles de concurrencia**, que permaneciÃ³ en 5. No se aumentaron rÃ©plicas, no se midiÃ³ carga sostenida, percentiles, red externa ni concurrencia mayor, y solo hubo una ejecuciÃ³n por caso. Por ello los resultados describen este entorno y sus lotes; no prueban escalado horizontal ni capacidad de producciÃ³n. La muestra de CPU en Auth sugiere presiÃ³n de cÃ³mputo durante login, pero no identifica por sÃ­ sola la causa.

## ComunicaciÃ³n y disponibilidad

Pedidos consultÃ³ por DNS interno los healthchecks de Productos y Clientes: ambos HTTP 200. RabbitMQ mostrÃ³ el binding `tienda.events` â†’ `notificaciones.pedidos`, routing key `pedido.creado`. AdemÃ¡s, se republicÃ³ desde Pedidos el evento ya procesado del pedido 1; el broker lo confirmÃ³ y el consumidor lo recibiÃ³ sin crear nuevos registros. La prueba E2E previa estÃ¡ en [PRUEBA_END_TO_END.md](PRUEBA_END_TO_END.md).

Entre 23:00:48 y 23:01:08 se detuvo temporalmente `notificaciones-worker`. Los cinco healthchecks HTTP permanecieron en 200. Al publicar el mismo evento, RabbitMQ mostrÃ³ 1 mensaje listo, 0 sin confirmar y 0 consumidores. Tras reiniciar el worker mostrÃ³ 0 listos, 0 sin confirmar y 1 consumidor; `processed_events`, `notifications` e `invoices` permanecieron en 1 cada uno para ese evento. No se crearon pedidos ni se cambiÃ³ stock. Todos los contenedores quedaron activos y los once con healthcheck, saludables. La observaciÃ³n cubre una interrupciÃ³n corta del worker, no una caÃ­da del broker o de su volumen.
