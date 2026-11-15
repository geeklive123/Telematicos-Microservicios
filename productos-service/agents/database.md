# Database Agent

Base de datos:
productos_db

Tabla principal:
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
- usar migraciones Laravel
- no usar migrate:fresh
- precio con DECIMAL
- stock no negativo
- sku único
- no crear FK hacia otros microservicios