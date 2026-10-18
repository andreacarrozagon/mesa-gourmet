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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mi cuenta | Mesa Gourmet</title>

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
            <p class="mb-0">Mi cuenta</p>
        </div>
    </header>

    <nav class="navbar navbar-expand bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                Mesa Gourmet
            </a>

            <a class="nav-link" href="index.html">
                Inicio
            </a>
        </div>
    </nav>

    <main class="container py-5">
        <section class="panel-login">
            <h2 class="mb-4">
                Hola, <?= escapar($_SESSION["nombre"]) ?>
            </h2>

            <div class="alert alert-success" role="status">
                Iniciaste sesión correctamente.
            </div>

            <dl>
                <dt>Correo electrónico</dt>
                <dd><?= escapar($_SESSION["email"]) ?></dd>

                <dt>Tipo de cuenta</dt>
                <dd><?= escapar($_SESSION["rol"]) ?></dd>
            </dl>

            <p class="text-secondary">
                Esta página solo está disponible para usuarios
                que hayan iniciado sesión.
            </p>

            <a href="index.html" class="btn btn-gourmet w-100 mb-3">
                Volver a la tienda
            </a>

            <form action="cerrar_sesion.php" method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($_SESSION["csrf_token"]) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-outline-secondary w-100"
                >
                    Cerrar sesión
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
