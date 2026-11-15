# AGENTS.md

Proyecto: Tienda de Componentes de PC - Microservicio de Pedidos.

Stack:
- Laravel
- PHP
- MySQL / MariaDB
- API REST
- JSON

Este repositorio corresponde únicamente a pedidos-service.

Arquitectura general:
- productos-service
- clientes-service
- pedidos-service

Cada microservicio:
- es independiente;
- tiene su propia base de datos;
- se comunica por HTTP REST / JSON;
- no accede directamente a la base de datos de otro servicio.

Responsabilidad de pedidos-service:
- pedidos;
- detalle de pedidos;
- totales;
- estados;
- método de pago;
- consulta de clientes;
- consulta de productos;
- coordinación básica del proceso de compra.

Base de datos:
pedidos_db

Tablas principales previstas:
- pedidos
- detalle_pedidos

Reglas importantes:
- pedido_id sí puede tener Foreign Key real hacia pedidos.id porque ambas tablas pertenecen a pedidos_db.
- cliente_id NO debe tener Foreign Key hacia clientes_db.
- producto_id NO debe tener Foreign Key hacia productos_db.
- cliente_id y producto_id son referencias lógicas a otros microservicios.
- La existencia del cliente debe validarse mediante Clientes Service.
- La existencia, precio y stock del producto deben consultarse mediante Productos Service.

Servicios externos locales:
- Productos: http://127.0.0.1:8001
- Clientes: http://127.0.0.1:8002
- Pedidos: http://127.0.0.1:8003

Reglas para Codex:
- Leer este archivo antes de modificar código.
- No cambiar arquitectura sin autorización.
- No acceder directamente a productos_db ni clientes_db.
- No hacer git commit ni git push.
- No ejecutar migrate:fresh.
- No borrar datos.
- No instalar paquetes sin autorización.
- Mantener Controllers delgados.
- Usar Models, Form Requests y Services cuando corresponda.
- Ejecutar pruebas antes de dar una tarea por terminada.