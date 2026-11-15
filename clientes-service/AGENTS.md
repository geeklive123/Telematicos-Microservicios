# AGENTS.md

Proyecto: Tienda de Componentes de PC - Microservicio de Clientes.

Stack:
- Laravel
- PHP
- MySQL / MariaDB
- API REST
- JSON

Este repositorio corresponde únicamente a clientes-service.

Arquitectura general:
- productos-service
- clientes-service
- pedidos-service

Cada microservicio:
- es independiente;
- tiene su propia base de datos;
- se comunica por HTTP REST / JSON;
- no accede directamente a la base de datos de otro servicio.

Responsabilidad de clientes-service:
- clientes;
- datos personales básicos;
- teléfono;
- dirección;
- estado activo/inactivo.

Base de datos:
clientes_db

Tabla principal prevista:
clientes

Reglas para Codex:
- Leer este archivo antes de modificar código.
- No cambiar la arquitectura sin autorización.
- No hacer git commit ni git push.
- No ejecutar migrate:fresh.
- No borrar datos.
- No agregar funcionalidades de Productos o Pedidos aquí.
- Usar migraciones Laravel.
- Usar Controllers, Models y Form Requests.
- Mantener respuestas JSON claras.
- Ejecutar pruebas antes de dar una tarea por terminada.

Endpoints previstos:
GET    /api/health
GET    /api/clientes
GET    /api/clientes/{id}
POST   /api/clientes
PUT    /api/clientes/{id}
DELETE /api/clientes/{id}
