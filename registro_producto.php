<?php
session_start([
    "use_strict_mode" => true,
    "cookie_httponly" => true,
    "cookie_secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "cookie_samesite" => "Lax"
]);

header("Cache-Control: no-store");

// Comprueba que el usuario haya iniciado sesión.
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

// Comprueba el rol actual directamente en la base de datos.
require_once __DIR__ . "/conexion.php";

$consulta = $conexion->prepare(
    "SELECT rol FROM USUARIOS WHERE id_usuario = :id"
);

$consulta->execute([
    ":id" => $_SESSION["id_usuario"]
]);

if ($consulta->fetchColumn() !== "administrador") {
    http_response_code(403);
    exit("Acceso restringido: se requiere una cuenta administradora.");
}

// Token para proteger el envío del formulario.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mensaje = $_SESSION["mensaje_producto"] ?? "";
unset($_SESSION["mensaje_producto"]);

function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro de productos | Mesa Gourmet</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="estilos.css">
</head>

<body>
    <header class="encabezado text-center py-4">
        <div class="container">
            <h1 class="marca">MESA GOURMET</h1>
            <p class="mb-0">Administración de productos</p>
        </div>
    </header>

    <nav class="navbar navbar-expand bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                Mesa Gourmet
            </a>

            <a class="nav-link" href="mi_cuenta.php">
                Mi cuenta
            </a>
        </div>
    </nav>

    <main class="container py-5">
        <section class="panel-login">
            <h2 class="mb-3">Registrar producto</h2>

            <?php if ($mensaje !== ""): ?>
                <div class="alert alert-success" role="status">
                    <?= escapar($mensaje) ?>
                </div>
            <?php endif; ?>

            <p class="text-secondary">
                Completa todos los campos para guardar un producto.
            </p>

            <form action="procesar_producto.php" method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($_SESSION["csrf_token"]) ?>"
                >

                <div class="mb-3">
                    <label for="nombre" class="form-label">
                        Nombre del producto
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">
                        Descripción
                    </label>

                    <textarea
                        class="form-control"
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                        maxlength="1000"
                        required
                    ></textarea>
                </div>

                <div class="mb-3">
                    <label for="categoria" class="form-label">
                        Categoría
                    </label>

                    <select
                        class="form-select"
                        id="categoria"
                        name="categoria"
                        required
                    >
                        <option value="" selected disabled>
                            Selecciona una categoría
                        </option>
                        <option value="Vinos">Vinos</option>
                        <option value="Quesos">Quesos</option>
                        <option value="Chocolates">Chocolates</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="precio" class="form-label">
                        Precio en pesos chilenos
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="precio"
                        name="precio"
                        min="1"
                        max="99999999"
                        step="1"
                        aria-describedby="ayudaPrecio"
                        required
                    >

                    <div id="ayudaPrecio" class="form-text">
                        Escribe el precio sin puntos ni comas.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="inventario" class="form-label">
                        Unidades disponibles
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="inventario"
                        name="inventario"
                        min="0"
                        max="1000000"
                        step="1"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-gourmet w-100">
                    Guardar producto
                </button>

                <button
                    type="reset"
                    class="btn btn-outline-secondary w-100 mt-2"
                >
                    Limpiar formulario
                </button>
            </form>
        </section>
    </main>

    <footer class="text-center py-4">
        <div class="container">
            <p class="mb-0">Mesa Gourmet · Proyecto académico</p>
        </div>
    </footer>
</body>
</html>
