<?php
// Configuración de la cookie de sesión.
session_start([
    "use_strict_mode" => true,
    "cookie_httponly" => true,
    "cookie_secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "cookie_samesite" => "Lax"
]);

header("Cache-Control: no-store");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$email = $_POST["email"] ?? "";
$contrasena = $_POST["contrasena"] ?? "";

$email = is_string($email) ? trim($email) : "";
$contrasena = is_string($contrasena) ? $contrasena : "";

$error = "";

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 190 ||
    $contrasena === "" ||
    strlen($contrasena) > 72 ||
    strpos($contrasena, "\0") !== false
) {
    $error = "Correo electrónico o contraseña incorrectos.";
} else {
    require_once __DIR__ . "/conexion.php";

    try {
        $consulta = $conexion->prepare(
            "SELECT id_usuario, nombre, email, contrasena, rol
             FROM USUARIOS
             WHERE email = :email
             LIMIT 1"
        );

        $consulta->execute([
            ":email" => $email
        ]);

        $usuario = $consulta->fetch();

        if (
            $usuario &&
            password_verify($contrasena, $usuario["contrasena"])
        ) {
            // Renueva el identificador después de autenticar.
            session_regenerate_id(true);

            $_SESSION = [];
            $_SESSION["id_usuario"] = (int) $usuario["id_usuario"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["email"] = $usuario["email"];
            $_SESSION["rol"] = $usuario["rol"];
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            header("Location: mi_cuenta.php", true, 303);
            exit;
        }

        $error = "Correo electrónico o contraseña incorrectos.";
    } catch (PDOException $excepcion) {
        $error = "No se pudo iniciar sesión. Intenta nuevamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inicio de sesión | Mesa Gourmet</title>

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
            <p class="mb-0">Inicio de sesión</p>
        </div>
    </header>

    <main class="container py-5">
        <section class="panel-login">
            <h2 class="mb-4">Revisa tus credenciales</h2>

            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    "UTF-8"
                ) ?>
            </div>

            <a href="login.html" class="btn btn-gourmet w-100">
                Volver a intentar
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
