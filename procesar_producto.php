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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: registro_producto.php");
    exit;
}

require_once __DIR__ . "/conexion.php";

// Verifica los permisos también al procesar el formulario.
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

// Verifica que el formulario corresponda a la sesión.
$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Solicitud no válida. Abre nuevamente el formulario.");
}

function recibirTexto($campo)
{
    $valor = $_POST[$campo] ?? "";
    return is_string($valor) ? trim($valor) : "";
}

function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

$nombre = recibirTexto("nombre");
$descripcion = recibirTexto("descripcion");
$categoria = recibirTexto("categoria");
$precioIngresado = recibirTexto("precio");
$inventarioIngresado = recibirTexto("inventario");

$errores = [];

$longitudNombre = preg_match_all('/./us', $nombre);
$longitudDescripcion = preg_match_all('/./us', $descripcion);

if (
    $nombre === "" ||
    $longitudNombre === false ||
    $longitudNombre > 100
) {
    $errores[] = "El nombre debe tener entre 1 y 100 caracteres.";
}

if (
    $descripcion === "" ||
    $longitudDescripcion === false ||
    $longitudDescripcion > 1000
) {
    $errores[] = "La descripción debe tener entre 1 y 1000 caracteres.";
}

$categoriasPermitidas = ["Vinos", "Quesos", "Chocolates"];

if (!in_array($categoria, $categoriasPermitidas, true)) {
    $errores[] = "Selecciona una categoría válida.";
}

$precio = filter_var(
    $precioIngresado,
    FILTER_VALIDATE_INT,
    [
        "options" => [
            "min_range" => 1,
            "max_range" => 99999999
        ]
    ]
);

if ($precio === false) {
    $errores[] = "El precio debe ser un entero entre 1 y 99999999.";
}

$inventario = filter_var(
    $inventarioIngresado,
    FILTER_VALIDATE_INT,
    [
        "options" => [
            "min_range" => 0,
            "max_range" => 1000000
        ]
    ]
);

if ($inventario === false) {
    $errores[] = "El inventario debe ser un entero entre 0 y 1000000.";
}

if (count($errores) === 0) {
    try {
        $insercion = $conexion->prepare(
            "INSERT INTO PRODUCTOS
                (nombre, descripcion, categoria, precio, inventario)
             VALUES
                (:nombre, :descripcion, :categoria, :precio, :inventario)"
        );

        $insercion->execute([
            ":nombre" => $nombre,
            ":descripcion" => $descripcion,
            ":categoria" => $categoria,
            ":precio" => $precio,
            ":inventario" => $inventario
        ]);

        $_SESSION["mensaje_producto"] =
            "Producto «" . $nombre . "» guardado correctamente en MySQL.";

        // Evita repetir el envío al actualizar la página de resultado.
        header("Location: registro_producto.php", true, 303);
        exit;
    } catch (PDOException $error) {
        $errores[] = "No se pudo guardar el producto. Intenta nuevamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Revisar producto | Mesa Gourmet</title>

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

    <main class="container py-5">
        <section class="panel-login">
            <h2 class="mb-4">No se pudo guardar el producto</h2>

            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escapar($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <a
                href="registro_producto.php"
                class="btn btn-gourmet w-100"
            >
                Volver al formulario
            </a>
        </section>
    </main>

    <footer class="text-center py-4">
        <div class="container">
            <p class="mb-0">Mesa Gourmet · Proyecto académico</p>
        </div>
    </footer>
</body>
</html>
