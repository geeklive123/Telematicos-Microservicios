\# Arquitectura del sistema



Sistema:

Tienda de Componentes de PC



Microservicios:

1\. productos-service

2\. clientes-service

3\. pedidos-service



Clientes Service:

\- clientes

\- datos personales

\- telÃ©fono

\- direcciÃ³n

\- estado



Base:

clientes\_db



Productos Service:

Base:

productos\_db



Pedidos Service:

Base:

pedidos\_db



ComunicaciÃ³n:

HTTP REST + JSON



No existen Foreign Keys entre bases de datos de diferentes microservicios.



Ejemplo:

pedidos.cliente\_id referencia lÃ³gicamente a un cliente,

pero la existencia del cliente se valida mediante la API de Clientes.

