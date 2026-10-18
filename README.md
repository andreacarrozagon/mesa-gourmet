# Mesa Gourmet

Proyecto académico de Programación Web II: aplicación web para una tienda de vinos, quesos y chocolates.

## Tecnologías utilizadas

- HTML y CSS.
- Bootstrap 5.3.8.
- JavaScript.
- PHP con PDO.
- MySQL con tablas InnoDB.
- XAMPP como entorno local.
- GitHub para almacenar y compartir el código.

## Funcionalidades

- Registro de usuarios con validación de datos.
- Almacenamiento de contraseñas mediante hash bcrypt.
- Inicio y cierre de sesión.
- Control de acceso a páginas privadas.
- Registro de productos exclusivo para administradores.
- Catálogo con filtros por categoría.
- Carrito individual: agregar, modificar cantidades y eliminar productos.
- Confirmación de pedidos y actualización del inventario.
- Consulta del historial de pedidos del usuario.

Los pedidos se registran con estado pendiente. La aplicación no procesa pagos reales ni incluye una integración con transportistas.

## Requisitos

- PHP 8 o superior con la extensión PDO MySQL.
- MySQL o MariaDB compatible con InnoDB.
- Servidor web, por ejemplo Apache mediante XAMPP.
- Conexión a Internet para cargar Bootstrap desde su CDN.

## Instalación local

1. Descarga el repositorio desde Code → Download ZIP y descomprímelo.
2. Coloca los archivos dentro de una carpeta llamada gourmet en:

   C:\xampp\htdocs\gourmet

   El archivo index.html debe quedar directamente dentro de gourmet.

3. Inicia Apache y MySQL desde el panel de XAMPP.
4. Abre http://localhost/phpmyadmin/
5. Crea una base de datos llamada GOURMET con codificación utf8mb4.
6. Selecciona GOURMET e importa el archivo sql/gourmet.sql.
7. Copia conexion.example.php con el nombre conexion.php.
8. Edita conexion.php y configura el usuario y la contraseña de tu instalación de MySQL.
9. Abre http://localhost/gourmet/

El archivo conexion.php no se incluye en el repositorio porque contiene la configuración local de conexión.

## Creación de una cuenta

Abre:

http://localhost/gourmet/registro.php

Completa el formulario y registra tu cuenta. Luego inicia sesión desde:

http://localhost/gourmet/login.html

Los usuarios nuevos reciben el rol cliente.

La exportación SQL contiene solamente la estructura: no incluye usuarios ni productos registrados.

## Habilitar una cuenta administradora

Después de registrar una cuenta, ejecuta en phpMyAdmin la siguiente instrucción, reemplazando el correo de ejemplo por el de esa cuenta:

```sql
UPDATE USUARIOS
SET rol = 'administrador'
WHERE email = 'tu-correo@example.com';
```

Cierra la sesión y vuelve a ingresar para actualizar el rol guardado en ella.

Abre el formulario de administración:

http://localhost/gourmet/registro_producto.php

Registra productos para las categorías Vinos, Quesos y Chocolates.

## Uso de la aplicación

- Catálogo: http://localhost/gourmet/catalogo.php
- Mi cuenta: http://localhost/gourmet/mi_cuenta.php
- Carrito: http://localhost/gourmet/carrito.php
- Pedidos: http://localhost/gourmet/pedidos.php

Para realizar una compra:

1. Inicia sesión.
2. Abre el carrito y selecciona un producto y su cantidad.
3. Agrega los productos deseados.
4. Actualiza cantidades o elimina productos si corresponde.
5. Presiona Continuar con el pedido.
6. Revisa la dirección y el total.
7. Presiona Confirmar pedido.
8. Consulta la compra en el historial.

Agregar productos al carrito no reserva inventario. El stock se verifica y descuenta al confirmar el pedido.

## Base de datos

- USUARIOS: datos de las cuentas y roles.
- PRODUCTOS: catálogo, precios e inventario.
- CARRITO: productos seleccionados por cada usuario.
- PEDIDOS: datos generales de cada compra.
- DETALLE_PEDIDO: productos, cantidades y precios de cada pedido.

La confirmación utiliza una transacción para guardar el pedido, registrar su detalle, descontar inventario y vaciar el carrito. Si ocurre un error, los cambios se revierten.

## Medidas implementadas

- Validaciones en el navegador y en PHP.
- Consultas preparadas con PDO para valores recibidos.
- Contraseñas protegidas con password_hash() y verificadas con password_verify().
- Renovación del identificador de sesión después de autenticar.
- Comprobación del rol para administrar productos.
- Tokens CSRF en las operaciones de productos, carrito, pedidos y cierre de sesión.
- Escape de datos al mostrarlos en HTML.

Este proyecto está destinado a un entorno académico local. Su publicación en producción requiere configuración y revisión adicionales.

## Ejecución

GitHub almacena el código fuente. Para ejecutar PHP y MySQL se necesita un servidor compatible, como el entorno local de XAMPP descrito anteriormente.
