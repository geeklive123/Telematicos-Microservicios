\# Arquitectura de Pedidos Service



Sistema:

Tienda de Componentes de PC



Microservicios:

1\. productos-service

2\. clientes-service

3\. pedidos-service



\## Responsabilidad de Pedidos



Pedidos Service administra:

\- cabecera del pedido;

\- detalle del pedido;

\- cliente asociado;

\- productos asociados;

\- cantidades;

\- precios histÃ³ricos;

\- subtotal;

\- total;

\- estado;

\- mÃ©todo de pago.



\## Base propia



pedidos\_db



\## Tablas previstas



pedidos



\- id

\- cliente\_id

\- subtotal

\- total

\- estado

\- metodo\_pago

\- fecha

\- timestamps



detalle\_pedidos



\- id

\- pedido\_id

\- producto\_id

\- producto\_nombre

\- precio\_unitario

\- cantidad

\- subtotal

\- timestamps



\## Relaciones



pedidos 1 ---- N detalle\_pedidos



Esta relaciÃ³n sÃ­ usa Foreign Key real:



detalle\_pedidos.pedido\_id -> pedidos.id



cliente\_id no tiene Foreign Key externa.



producto\_id no tiene Foreign Key externa.



\## ComunicaciÃ³n



Pedidos valida clientes mediante:



GET Clientes Service /api/clientes/{id}



Pedidos consulta productos mediante:



GET Productos Service /api/productos/{id}



La comunicaciÃ³n es HTTP REST + JSON.



\## Datos histÃ³ricos



detalle\_pedidos debe guardar:

\- producto\_id;

\- producto\_nombre;

\- precio\_unitario.



Esto permite conservar cÃ³mo era la venta aunque luego el producto cambie de nombre o precio.

