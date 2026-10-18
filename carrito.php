<?php
session_start([
    "use_strict_mode" => true,
    "cookie_httponly" => true,
    "cookie_secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "cookie_samesite" => "Lax"
]);

header("Cache-Control: no-store");

// El carrito requiere una sesión iniciada.
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . "/conexion.php";

$idUsuario = (int) $_SESSION["id_usuario"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

function pesos($valor)
{
    return "$" . number_format((float) $valor, 0, ",", ".") . " CLP";
}

// Procesa agregar, actualizar y eliminar productos.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Solicitud no válida. Abre nuevamente el carrito.");
    }

    $accion = $_POST["accion"] ?? "";

    $idProducto = filter_var(
        $_POST["id_producto"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    $cantidad = filter_var(
        $_POST["cantidad"] ?? null,
        FILTER_VALIDATE_INT,
        [
            "options" => [
                "min_range" => 1,
                "max_range" => 1000000
            ]
        ]
    );

    try {
        if (
            !$idProducto ||
            !in_array(
                $accion,
                ["agregar", "actualizar", "eliminar"],
                true
            )
        ) {
            throw new RuntimeException("La operación no es válida.");
        }

        if ($accion !== "eliminar" && $cantidad === false) {
            throw new RuntimeException(
                "La cantidad debe ser un número entero mayor que cero."
            );
        }

        $conexion->beginTransaction();

        // Consulta y bloquea el producto durante esta operación.
        $consulta = $conexion->prepare(
            "SELECT precio, inventario
             FROM PRODUCTOS
             WHERE id_producto = :producto
             FOR UPDATE"
        );

        $consulta->execute([
            ":producto" => $idProducto
        ]);

        $producto = $consulta->fetch();

        if (!$producto) {
            throw new RuntimeException("El producto no existe.");
        }

        // Consulta solo el carrito del usuario autenticado.
        $consulta = $conexion->prepare(
            "SELECT cantidad
             FROM CARRITO
             WHERE id_usuario = :usuario
               AND id_producto = :producto
             FOR UPDATE"
        );

        $consulta->execute([
            ":usuario" => $idUsuario,
            ":producto" => $idProducto
        ]);

        $linea = $consulta->fetch();

        if ($accion === "eliminar") {
            $consulta = $conexion->prepare(
                "DELETE FROM CARRITO
                 WHERE id_usuario = :usuario
                   AND id_producto = :producto"
            );

            $consulta->execute([
                ":usuario" => $idUsuario,
                ":producto" => $idProducto
            ]);

            $mensaje = "Producto eliminado del carrito.";
        } else {
            if ($accion === "actualizar" && !$linea) {
                throw new RuntimeException(
                    "Ese producto ya no está en tu carrito."
                );
            }

            $nuevaCantidad = $cantidad;

            if ($accion === "agregar" && $linea) {
                $nuevaCantidad += (int) $linea["cantidad"];
            }

            if ($nuevaCantidad > (int) $producto["inventario"]) {
                throw new RuntimeException(
                    "No hay stock suficiente para esa cantidad."
                );
            }

            if ($linea) {
                $consulta = $conexion->prepare(
                    "UPDATE CARRITO
                     SET cantidad = :cantidad,
                         monto_total = :precio * :unidades
                     WHERE id_usuario = :usuario
                       AND id_producto = :producto"
                );
            } else {
                $consulta = $conexion->prepare(
                    "INSERT INTO CARRITO
                        (id_usuario, id_producto, cantidad, monto_total)
                     VALUES
                        (:usuario, :producto, :cantidad, :precio * :unidades)"
                );
            }

            $consulta->execute([
                ":cantidad" => $nuevaCantidad,
                ":precio" => $producto["precio"],
                ":unidades" => $nuevaCantidad,
                ":usuario" => $idUsuario,
                ":producto" => $idProducto
            ]);

            $mensaje = $accion === "agregar"
                ? "Producto agregado al carrito."
                : "Cantidad actualizada.";
        }

        $conexion->commit();

        $_SESSION["aviso_carrito"] = $mensaje;
        $_SESSION["tipo_aviso_carrito"] = "success";
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        $_SESSION["aviso_carrito"] =
            $error instanceof RuntimeException
            && !($error instanceof PDOException)
                ? $error->getMessage()
                : "No se pudo modificar el carrito. Intenta nuevamente.";

        $_SESSION["tipo_aviso_carrito"] = "danger";
    }

    // Evita repetir el envío al actualizar la página.
    header("Location: carrito.php", true, 303);
    exit;
}

$aviso = $_SESSION["aviso_carrito"] ?? "";
$tipoAviso = $_SESSION["tipo_aviso_carrito"] ?? "success";

unset(
    $_SESSION["aviso_carrito"],
    $_SESSION["tipo_aviso_carrito"]
);

// Productos con stock que se pueden agregar.
$productos = $conexion->query(
    "SELECT id_producto, nombre, precio, inventario
     FROM PRODUCTOS
     WHERE inventario > 0
     ORDER BY nombre"
)->fetchAll();

// Actualiza subtotales según los precios actuales.
$consulta = $conexion->prepare(
    "UPDATE CARRITO c
     INNER JOIN PRODUCTOS p ON p.id_producto = c.id_producto
     SET c.monto_total = c.cantidad * p.precio
     WHERE c.id_usuario = :usuario"
);

$consulta->execute([
    ":usuario" => $idUsuario
]);

// Recupera los productos del carrito de este usuario.
$consulta = $conexion->prepare(
    "SELECT c.id_producto, c.cantidad, c.monto_total,
            p.nombre, p.precio, p.inventario
     FROM CARRITO c
     INNER JOIN PRODUCTOS p ON p.id_producto = c.id_producto
     WHERE c.id_usuario = :usuario
     ORDER BY c.id_carrito"
);

$consulta->execute([
    ":usuario" => $idUsuario
]);

$carrito = $consulta->fetchAll();

$total = 0;

foreach ($carrito as $linea) {
    $total += (float) $linea["monto_total"];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mi carrito | Mesa Gourmet</title>

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
            <p class="mb-0">Mi carrito de compras</p>
        </div>
    </header>

    <nav class="navbar bg-white border-bottom">
        <div class="container d-flex flex-wrap gap-3">
            <a class="navbar-brand" href="index.html">
                Mesa Gourmet
            </a>

            <div class="d-flex flex-wrap gap-3">
                <a class="nav-link" href="catalogo.php">
                    Catálogo
                </a>

                <a class="nav-link" href="pedidos.php">
                    Mis pedidos
                </a>

                <a class="nav-link" href="mi_cuenta.php">
                    Mi cuenta
                </a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <h2 class="mb-4">
            Carrito de <?= escapar($_SESSION["nombre"]) ?>
        </h2>

        <?php if ($aviso !== ""): ?>
            <div
                class="alert alert-<?= escapar($tipoAviso) ?>"
                role="status"
            >
                <?= escapar($aviso) ?>
            </div>
        <?php endif; ?>

        <section class="card p-4 mb-4">
            <h3 class="h5 mb-3">Agregar un producto</h3>

            <?php if (count($productos) > 0): ?>
                <form
                    method="post"
                    action="carrito.php"
                    class="row g-3"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapar($_SESSION["csrf_token"]) ?>"
                    >

                    <input
                        type="hidden"
                        name="accion"
                        value="agregar"
                    >

                    <div class="col-12 col-md-7">
                        <label for="producto" class="form-label">
                            Producto
                        </label>

                        <select
                            class="form-select"
                            id="producto"
                            name="id_producto"
                            required
                        >
                            <?php foreach ($productos as $producto): ?>
                                <option
                                    value="<?= (int) $producto["id_producto"] ?>"
                                >
                                    <?= escapar($producto["nombre"]) ?>
                                    — <?= pesos($producto["precio"]) ?>
                                    — Stock:
                                    <?= (int) $producto["inventario"] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label for="cantidad" class="form-label">
                            Cantidad
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="cantidad"
                            name="cantidad"
                            min="1"
                            max="1000000"
                            step="1"
                            value="1"
                            required
                        >
                    </div>

                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <button
                            class="btn btn-gourmet w-100"
                            type="submit"
                        >
                            Agregar al carrito
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <p class="mb-0">
                    No hay productos con stock disponible.
                </p>
            <?php endif; ?>
        </section>

        <?php if (count($carrito) === 0): ?>

            <div class="alert alert-info" role="status">
                Tu carrito está vacío.
            </div>

            <a href="catalogo.php" class="btn btn-gourmet">
                Ver catálogo
            </a>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table align-middle">
                    <caption>
                        Productos de tu carrito y sus subtotales.
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">Producto</th>
                            <th scope="col">Precio</th>
                            <th scope="col">Cantidad</th>
                            <th scope="col">Subtotal</th>
                            <th scope="col">Eliminar</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($carrito as $linea): ?>
                            <tr>
                                <td>
                                    <?= escapar($linea["nombre"]) ?>

                                    <?php if (
                                        (int) $linea["cantidad"]
                                        > (int) $linea["inventario"]
                                    ): ?>
                                        <p class="text-danger small mb-0">
                                            Stock insuficiente.
                                            Reduce la cantidad o
                                            elimina el producto.
                                        </p>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= pesos($linea["precio"]) ?>
                                </td>

                                <td>
                                    <form
                                        method="post"
                                        action="carrito.php"
                                        class="d-flex gap-2"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= escapar($_SESSION["csrf_token"]) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="actualizar"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_producto"
                                            value="<?= (int) $linea["id_producto"] ?>"
                                        >

                                        <input
                                            type="number"
                                            class="form-control"
                                            name="cantidad"
                                            value="<?= (int) $linea["cantidad"] ?>"
                                            min="1"
                                            max="1000000"
                                            step="1"
                                            aria-label="Cantidad de <?= escapar($linea["nombre"]) ?>"
                                            style="min-width: 85px;"
                                            required
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-outline-secondary btn-sm"
                                        >
                                            Actualizar
                                        </button>
                                    </form>
                                </td>

                                <td>
                                    <?= pesos($linea["monto_total"]) ?>
                                </td>

                                <td>
                                    <form
                                        method="post"
                                        action="carrito.php"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= escapar($_SESSION["csrf_token"]) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="eliminar"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_producto"
                                            value="<?= (int) $linea["id_producto"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-outline-danger btn-sm"
                                            aria-label="Eliminar <?= escapar($linea["nombre"]) ?>"
                                        >
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="fs-4 fw-bold text-end">
                Total: <?= pesos($total) ?>
            </p>

            <div class="text-end mb-3">
                <a href="pedidos.php" class="btn btn-gourmet">
                    Continuar con el pedido
                </a>
            </div>

            <p class="text-secondary">
                Los precios corresponden a los valores actuales.
                Agregar productos al carrito no reserva stock.
            </p>

        <?php endif; ?>
    </main>

    <footer class="text-center py-4">
        <div class="container">
            <p class="mb-0">
                Mesa Gourmet · Proyecto académico
            </p>
        </div>
    </footer>
</body>
</html>

