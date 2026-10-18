<?php
session_start([
    "use_strict_mode" => true,
    "cookie_httponly" => true,
    "cookie_secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "cookie_samesite" => "Lax"
]);

header("Cache-Control: no-store");

require_once __DIR__ . "/conexion.php";

function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

$categorias = ["Vinos", "Quesos", "Chocolates"];

$categoria = $_GET["categoria"] ?? "";
$categoria = is_string($categoria) ? trim($categoria) : "";

if (!in_array($categoria, $categorias, true)) {
    $categoria = "";
}

$productos = [];
$error = "";

try {
    if ($categoria !== "") {
        $consulta = $conexion->prepare(
            "SELECT id_producto, nombre, descripcion,
                    categoria, precio, inventario
             FROM PRODUCTOS
             WHERE categoria = :categoria
             ORDER BY id_producto DESC"
        );

        $consulta->execute([
            ":categoria" => $categoria
        ]);
    } else {
        $consulta = $conexion->query(
            "SELECT id_producto, nombre, descripcion,
                    categoria, precio, inventario
             FROM PRODUCTOS
             ORDER BY id_producto DESC"
        );
    }

    $productos = $consulta->fetchAll();
} catch (PDOException $excepcion) {
    $error = "No se pudieron cargar los productos. Intenta nuevamente.";
}

$sesionIniciada = isset($_SESSION["id_usuario"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catálogo | Mesa Gourmet</title>

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
            <p class="mb-0">
                Descubre nuestros vinos, quesos y chocolates
            </p>
        </div>
    </header>

    <nav class="navbar bg-white border-bottom">
        <div class="container d-flex flex-wrap gap-3">
            <a class="navbar-brand" href="index.html">
                Mesa Gourmet
            </a>

            <div class="d-flex flex-wrap gap-3">
                <a class="nav-link" href="index.html">
                    Inicio
                </a>

                <a
                    class="nav-link"
                    href="catalogo.php"
                    aria-current="page"
                >
                    Catálogo
                </a>

                <?php if ($sesionIniciada): ?>
                    <a class="nav-link" href="mi_cuenta.php">
                        Mi cuenta
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="login.html">
                        Iniciar sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <h2 class="mb-3">Nuestros productos</h2>

        <p class="text-secondary">
            Consulta los productos disponibles y filtra por categoría.
            Los productos más recientes aparecen primero.
        </p>

        <form
            action="catalogo.php"
            method="get"
            class="row g-3 align-items-end mb-4"
        >
            <div class="col-12 col-md-6">
                <label for="categoria" class="form-label">
                    Categoría
                </label>

                <select
                    class="form-select"
                    id="categoria"
                    name="categoria"
                >
                    <option value="">Todas las categorías</option>

                    <?php foreach ($categorias as $opcion): ?>
                        <option
                            value="<?= escapar($opcion) ?>"
                            <?= $categoria === $opcion ? "selected" : "" ?>
                        >
                            <?= escapar($opcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-gourmet">
                    Filtrar
                </button>

                <a
                    href="catalogo.php"
                    class="btn btn-outline-secondary"
                >
                    Ver todos
                </a>
            </div>
        </form>

        <?php if ($error !== ""): ?>

            <div class="alert alert-danger" role="alert">
                <?= escapar($error) ?>
            </div>

        <?php elseif (count($productos) === 0): ?>

            <div class="alert alert-info" role="status">
                No hay productos registrados en esta categoría.
            </div>

        <?php else: ?>

            <p class="text-secondary">
                Productos encontrados: <?= count($productos) ?>
            </p>

            <div class="row g-4">
                <?php foreach ($productos as $producto): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card h-100 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-3">
                                    <span class="badge text-bg-light">
                                        <?= escapar($producto["categoria"]) ?>
                                    </span>
                                </div>

                                <h3 class="h4 card-title">
                                    <?= escapar($producto["nombre"]) ?>
                                </h3>

                                <p class="card-text">
                                    <?= escapar($producto["descripcion"]) ?>
                                </p>

                                <p class="fs-4 fw-bold mt-auto">
                                    $<?= number_format(
                                        (float) $producto["precio"],
                                        0,
                                        ",",
                                        "."
                                    ) ?> CLP
                                </p>

                                <?php if ((int) $producto["inventario"] > 0): ?>
                                    <p class="text-success mb-0">
                                        Disponible:
                                        <?= (int) $producto["inventario"] ?>
                                        unidades
                                    </p>
                                <?php else: ?>
                                    <p class="text-danger mb-0">
                                        Sin stock
                                    </p>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

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
