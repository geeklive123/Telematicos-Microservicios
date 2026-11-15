# Docker acadÃ©mico local

Compose ejecuta cinco aplicaciones Laravel PHP 8.4, un consumidor de Notificaciones, cinco servidores MySQL 8.4 independientes y RabbitMQ Management. La red `telematicos-network` conecta los contenedores por nombre de servicio. Pedidos usa `http://productos-service` y `http://clientes-service`; AMQP usa `rabbitmq:5672`. El worker es un proceso separado y el publicador de outbox se ejecuta explÃ­citamente.

| Servicio | Puerto local | Base propia |
| --- | --- | --- |
| auth-service | 8000 | auth_db en auth-db |
| productos-service | 8001 | productos_db en productos-db |
| clientes-service | 8002 | clientes_db en clientes-db |
| pedidos-service | 8003 | pedidos_db en pedidos-db |
| notificaciones-service | 8004 | notificaciones_db en notificaciones-db |
| rabbitmq | 5672; Management 15672 | volumen propio |

Los puertos se enlazan a la interfaz local. Cada base y RabbitMQ tienen un volumen nombrado independiente. Los datos sobreviven a `docker compose down`; no ejecutar `down -v` si se quieren conservar. Los healthchecks verifican MySQL, RabbitMQ y `/api/health`; el worker se supervisa mediante su estado y logs.

## ConfiguraciÃ³n

Copiar `.env.docker.example` a `.env.docker` y completar valores privados locales. Generar cinco claves Laravel independientes con `php artisan key:generate --show` desde cualquier aplicaciÃ³n Laravel instalada y asignarlas a las cinco variables `*_APP_KEY`. Generar un `JWT_SECRET` aleatorio de al menos 32 bytes; los cuatro servicios de JWT reciben el mismo valor. Elegir credenciales locales distintas para MySQL y RabbitMQ. No subir `.env.docker` ni reutilizar estas credenciales en producciÃ³n. El usuario de MySQL no debe ser `root`.

Todas las Ã³rdenes siguientes se ejecutan desde la raÃ­z y utilizan el archivo local sin imprimirlo:

```powershell
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker build
docker compose --env-file .env.docker up -d
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs --tail=100 notificaciones-worker
```

DespuÃ©s de que las bases estÃ©n sanas, aplicar migraciones sin borrar datos:

```powershell
docker compose --env-file .env.docker exec auth-service php artisan migrate --force
docker compose --env-file .env.docker exec productos-service php artisan migrate --force
docker compose --env-file .env.docker exec clientes-service php artisan migrate --force
docker compose --env-file .env.docker exec pedidos-service php artisan migrate --force
docker compose --env-file .env.docker exec notificaciones-service php artisan migrate --force
```

Para comprobar cada servicio, sustituir el nombre en `docker compose --env-file .env.docker exec auth-service php artisan migrate:status`. No se ejecutan migraciones ni seeders automÃ¡ticamente. El seeder de Auth usa `firstOrCreate`, pero el seeder de Productos actualiza stock y no debe repetirse sobre datos histÃ³ricos. Pedidos y Notificaciones tienen seeders de ejemplo no idempotentes. Revisar el destino y el contenido antes de ejecutar cualquier seeder.

En el primer arranque validado se ejecutaron los seeders de Auth y Clientes sobre sus volÃºmenes reciÃ©n creados. El seeder de Clientes usa `updateOrCreate` y puede sobrescribir cambios de esos registros demo si se repite. Los productos demo no se cargaron: revisar expresamente el seeder y el estado de inventario antes de usar `docker compose --env-file .env.docker exec productos-service php artisan db:seed --force`.

La interfaz RabbitMQ estÃ¡ en `http://127.0.0.1:15672` y usa las credenciales locales configuradas. `docker compose --env-file .env.docker exec pedidos-service php artisan outbox:publish --help` muestra la ayuda; el comando no se programa automÃ¡ticamente. Para detener los servicios conservando volÃºmenes: `docker compose --env-file .env.docker down`.

Las imÃ¡genes instalan dependencias de `composer.lock` sin dependencias de desarrollo y excluyen `.env`, `vendor` y `node_modules` del host. El servidor incorporado de PHP es para demostraciÃ³n local. Docker Compose se usa en el entorno acadÃ©mico; SiteGround requiere otro tipo de despliegue y no se configura aquÃ­. Las vistas que requieren recursos compilados de Vite no se preparan en esta imagen.
