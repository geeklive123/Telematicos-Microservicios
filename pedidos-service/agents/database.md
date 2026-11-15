\# Database Agent



Base:

pedidos\_db



Tablas:



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



Reglas:

\- usar DECIMAL para dinero

\- cantidad debe ser unsigned integer

\- pedido\_id debe tener Foreign Key real

\- cliente\_id no debe tener FK hacia clientes\_db

\- producto\_id no debe tener FK hacia productos\_db

\- no usar migrate:fresh

\- no borrar datos manualmente

