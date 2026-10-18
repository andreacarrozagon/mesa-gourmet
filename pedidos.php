<?php
session_start([
    "use_strict_mode" => true,
    "cookie_httponly" => true,
    "cookie_secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "cookie_samesite" => "Lax"
]);

header("Cache-Control: no-store");

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

// Confirma el pedido.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Solicitud no válida. Abre nuevamente la página.");
    }

    try {
        $conexion->beginTransaction();

        $consulta = $conexion->prepare(
            "SELECT direccion
             FROM USUARIOS
             WHERE id_usuario = :usuario"
        );

        $consulta->execute([":usuario" => $idUsuario]);
        $direccion = $consulta->fetchColumn();

        if (!is_string($direccion) || trim($direccion) === "") {
            throw new RuntimeException(
                "Tu cuenta no tiene una dirección de envío válida."
            );
        }

        // Bloquea las filas mientras se comprueba y actualiza el stock.
        $consulta = $conexion->prepare(
            "SELECT c.id_producto, c.cantidad,
                    p.nombre, p.precio, p.inventario
             FROM CARRITO c
             INNER JOIN PRODUCTOS p
                ON p.id_producto = c.id_producto
             WHERE c.id_usuario = :usuario
             ORDER BY p.id_producto
             FOR UPDATE"
        );

        $consulta->execute([":usuario" => $idUsuario]);
        $lineas = $consulta->fetchAll();

        if (!$lineas) {
            throw new RuntimeException("Tu carrito está vacío.");
        }

        $total = 0;

        foreach ($lineas as $linea) {
            $cantidad = (int) $linea["cantidad"];

            if (
                $cantidad < 1 ||
                $cantidad > (int) $linea["inventario"]
            ) {
                throw new RuntimeException(
                    "Revisa la cantidad de «" . $linea["nombre"] .
                    "»: no hay stock suficiente."
                );
            }

            // Los precios de la tienda son pesos enteros.
            $total += $cantidad * (int) $linea["precio"];
        }

        $consulta = $conexion->prepare(
            "INSERT INTO PEDIDOS
                (id_usuario, direccion_envio, total, estado)
             VALUES
                (:usuario, :direccion, :total, 'pendiente')"
        );

        $consulta->execute([
            ":usuario" => $idUsuario,
            ":direccion" => $direccion,
            ":total" => $total
        ]);

        $idPedido = (int) $conexion->lastInsertId();

        $insertarDetalle = $conexion->prepare(
            "INSERT INTO DETALLE_PEDIDO
                (id_pedido, id_producto, nombre_producto,
                 cantidad, precio_unitario, subtotal)
             VALUES
                (:pedido, :producto, :nombre,
                 :cantidad, :precio, :subtotal)"
        );

        $actualizarStock = $conexion->prepare(
            "UPDATE PRODUCTOS
             SET inventario = inventario - :cantidad
             WHERE id_producto = :producto
               AND inventario >= :cantidad_minima"
        );

        foreach ($lineas as $linea) {
            $cantidad = (int) $linea["cantidad"];
            $precio = (int) $linea["precio"];

            $insertarDetalle->execute([
                ":pedido" => $idPedido,
                ":producto" => $linea["id_producto"],
                ":nombre" => $linea["nombre"],
                ":cantidad" => $cantidad,
                ":precio" => $precio,
                ":subtotal" => $cantidad * $precio
            ]);

            $actualizarStock->execute([
                ":cantidad" => $cantidad,
                ":producto" => $linea["id_producto"],
                ":cantidad_minima" => $cantidad
            ]);

            if ($actualizarStock->rowCount() !== 1) {
                throw new RuntimeException(
                    "El stock cambió. Revisa tu carrito e intenta nuevamente."
                );
            }
        }

        $consulta = $conexion->prepare(
            "DELETE FROM CARRITO WHERE id_usuario = :usuario"
        );

        $consulta->execute([":usuario" => $idUsuario]);

        // Confirma todas las operaciones juntas.
        $conexion->commit();

        $_SESSION["aviso_pedido"] =
            "Pedido #" . $idPedido . " registrado correctamente.";
        $_SESSION["tipo_aviso_pedido"] = "success";
    } catch (Throwable $error) {
        // Si algo falla, no deja un pedido parcialmente guardado.
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        $_SESSION["aviso_pedido"] =
            $error instanceof RuntimeException
            && !($error instanceof PDOException)
                ? $error->getMessage()
                : "No se pudo confirmar el pedido. Intenta nuevamente.";

        $_SESSION["tipo_aviso_pedido"] = "danger";
    }

    header("Location: pedidos.php", true, 303);
    exit;
}

$aviso = $_SESSION["aviso_pedido"] ?? "";
$tipoAviso = $_SESSION["tipo_aviso_pedido"] ?? "success";

unset($_SESSION["aviso_pedido"], $_SESSION["tipo_aviso_pedido"]);

// Recupera la dirección del usuario.
$consulta = $conexion->prepare(
    "SELECT direccion FROM USUARIOS WHERE id_usuario = :usuario"
);
$consulta->execute([":usuario" => $idUsuario]);
$direccion = $consulta->fetchColumn();

// Calcula el importe usando los precios actuales de MySQL.
$consulta = $conexion->prepare(
    "SELECT COUNT(*) AS lineas,
            COALESCE(SUM(c.cantidad * p.precio), 0) AS total
     FROM CARRITO c
     INNER JOIN PRODUCTOS p ON p.id_producto = c.id_producto
     WHERE c.id_usuario = :usuario"
);
$consulta->execute([":usuario" => $idUsuario]);
$resumen = $consulta->fetch();

// Muestra únicamente los pedidos del usuario autenticado.
$consulta = $conexion->prepare(
    "SELECT id_pedido, fecha_pedido, estado, direccion_envio, total
     FROM PEDIDOS
     WHERE id_usuario = :usuario
     ORDER BY id_pedido DESC"
);
$consulta->execute([":usuario" => $idUsuario]);
$pedidos = $consulta->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mis pedidos | Mesa Gourmet</title>

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
            <p class="mb-0">Confirmación y seguimiento de pedidos</p>
        </div>
    </header>

    <nav class="navbar bg-white border-bottom">
        <div class="container d-flex flex-wrap gap-3">
            <a class="navbar-brand" href="index.html">Mesa Gourmet</a>

            <div class="d-flex flex-wrap gap-3">
                <a class="nav-link" href="catalogo.php">Catálogo</a>
                <a class="nav-link" href="carrito.php">Mi carrito</a>
                <a class="nav-link" href="mi_cuenta.php">Mi cuenta</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <h2 class="mb-4">Mis pedidos</h2>

        <?php if ($aviso !== ""): ?>
            <div
                class="alert alert-<?= escapar($tipoAviso) ?>"
                role="status"
            >
                <?= escapar($aviso) ?>
            </div>
        <?php endif; ?>

        <?php if ((int) $resumen["lineas"] > 0): ?>
            <section class="card p-4 mb-5">
                <h3 class="h5">Confirmar mi compra</h3>

                <p>
                    Dirección de envío:
                    <strong><?= escapar($direccion) ?></strong>
                </p>

                <p class="fs-4">
                    Total actual:
                    <strong><?= pesos($resumen["total"]) ?></strong>
                </p>

                <p class="text-secondary">
                    Se comprobarán los precios y el stock al confirmar.
                    El pedido quedará pendiente, sin realizar un cobro.
                </p>

                <form action="pedidos.php" method="post">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapar($_SESSION["csrf_token"]) ?>"
                    >

                    <button type="submit" class="btn btn-gourmet">
                        Confirmar pedido
                    </button>

                    <a href="carrito.php" class="btn btn-outline-secondary">
                        Revisar carrito
                    </a>
                </form>
            </section>
        <?php else: ?>
            <div class="alert alert-info">
                Tu carrito está vacío.
                <a href="carrito.php">Agrega productos para realizar un pedido.</a>
            </div>
        <?php endif; ?>

        <h3 class="h4 mb-3">Historial de pedidos</h3>

        <?php if (!$pedidos): ?>
            <p>Todavía no has realizado pedidos.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Pedido</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Dirección</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?= (int) $pedido["id_pedido"] ?></td>
                                <td><?= escapar($pedido["fecha_pedido"]) ?></td>
                                <td><?= escapar($pedido["direccion_envio"]) ?></td>
                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= escapar($pedido["estado"]) ?>
                                    </span>
                                </td>
                                <td><?= pesos($pedido["total"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <footer class="text-center py-4">
        <div class="container">
            <p class="mb-0">Mesa Gourmet · Proyecto académico</p>
        </div>
    </footer>
</body>
</html>
