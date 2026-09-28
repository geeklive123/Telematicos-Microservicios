# Arquitectura del sistema

Sistema:
Tienda de Componentes de PC

Microservicios:
1. productos-service
2. clientes-service
3. pedidos-service

Productos Service:
- catálogo
- precio
- stock
- marca
- categoría
- disponibilidad

Base:
productos_db

Clientes Service:
- clientes
- autenticación
- datos personales

Base:
clientes_db

Pedidos Service:
- pedidos
- detalle de pedidos
- estados
- totales

Base:
pedidos_db

Comunicación:
HTTP REST + JSON

No existen Foreign Keys entre bases de datos de diferentes microservicios.

Ejemplo:
detalle_pedidos.producto_id referencia lógicamente a un producto,
pero se valida mediante la API de Productos.