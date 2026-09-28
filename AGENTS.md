
# AGENTS.md

## Proyecto

Tienda de Componentes de PC - Microservicio de Productos.

Este repositorio forma parte de una arquitectura de microservicios desarrollada como proyecto universitario.

Stack principal:

- Laravel
- PHP
- MySQL / MariaDB
- API REST
- JSON

Este repositorio corresponde únicamente al:

PRODUCTOS SERVICE

No contiene Clientes ni Pedidos.

---

## Arquitectura general

El sistema completo tendrá como mínimo:

1. productos-service
2. clientes-service
3. pedidos-service

Cada microservicio:

- es una aplicación Laravel independiente;
- posee su propia base de datos;
- expone una API REST;
- no accede directamente a la base de datos de otro microservicio.

Comunicación entre servicios:

HTTP REST + JSON.

---

## Responsabilidad de este servicio

productos-service administra:

- productos;
- componentes de PC;
- precios;
- stock;
- SKU;
- marca;
- categoría;
- disponibilidad.

Base de datos:

productos_db

Tabla principal:

products

---

## Reglas obligatorias para Codex

Antes de modificar código:

1. Leer completamente:
   - AGENTS.md
   - .codex/project.md
   - docs/ARCHITECTURE.md
   - agents/backend.md
   - agents/database.md
   - agents/testing.md

2. Inspeccionar el estado real del proyecto.

3. No asumir archivos, tablas o endpoints que no existan.

---

## Restricciones

Codex NO debe:

- cambiar la arquitectura sin autorización;
- convertir el proyecto en monolito;
- acceder directamente a bases de datos de otros microservicios;
- añadir paquetes innecesarios;
- ejecutar migrate:fresh;
- borrar datos;
- hacer git commit;
- hacer git push;
- modificar otros repositorios;
- implementar funcionalidades de Clientes o Pedidos dentro de este servicio.

---

## Convenciones Laravel

Preferir:

- Controllers delgados;
- Form Requests para validación;
- Models para Eloquent;
- Services/Actions cuando exista lógica de negocio;
- API Resources cuando sea útil;
- respuestas JSON consistentes;
- rutas API en routes/api.php.

No colocar lógica compleja dentro de routes/api.php.

---

## Base de datos

Base:

productos_db

Entidad principal:

products

Campos previstos:

- id
- nombre
- descripcion
- categoria
- marca
- precio
- stock
- sku
- activo
- created_at
- updated_at

Reglas:

- precio >= 0;
- stock >= 0;
- sku único cuando exista;
- no usar float para precios;
- usar decimal.

---

## API prevista

Base:

/api

Endpoints iniciales:

GET    /api/health
GET    /api/productos
GET    /api/productos/{id}
POST   /api/productos
PUT    /api/productos/{id}
DELETE /api/productos/{id}

Los endpoints pueden evolucionar durante los sprints, pero no deben cambiarse arbitrariamente.

---

## Validación antes de terminar una tarea

Ejecutar cuando corresponda:

php artisan about
php artisan migrate:status
php artisan test

Si Pint está disponible:

vendor\bin\pint --test

No afirmar que una funcionalidad funciona si no fue comprobada.

---

## Forma de trabajo

Trabajar por sprints pequeños.

Antes de implementar:

- explicar brevemente qué se cambiará;
- revisar archivos existentes.

Después:

- indicar archivos modificados;
- indicar comandos ejecutados;
- mostrar resultados de validación;
- indicar cualquier pendiente o riesgo.
