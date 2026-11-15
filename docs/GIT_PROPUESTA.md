# Propuesta Git

La raíz no es un repositorio Git. Productos ya tiene su propio repositorio e historial y contiene cambios previos sin confirmar. Auth, Clientes y Pedidos no se detectaron como repositorios independientes. No se ha eliminado ningún `.git` ni hecho commit o push.

Una opción es mantener Productos como submódulo y crear un repositorio raíz para los demás servicios; requiere revisar y confirmar primero los cambios existentes de Productos y conserva su historial independiente. Otra opción, tras acordar el proceso, es importar su historial con `git subtree` a un monorepo nuevo. En ambos casos, hacer copia de seguridad y revisar la configuración y los archivos ignorados antes de cualquier operación. No borrar ni reemplazar el `.git` existente. La raíz ya incluye reglas para excluir entornos, dependencias, logs y bases SQLite; verificar con `git status --ignored` y revisar secretos antes del primer commit.
