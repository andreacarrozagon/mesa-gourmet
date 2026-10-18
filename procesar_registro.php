<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: registro.php");
    exit;
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

function longitudTexto($texto)
{
    $cantidad = preg_match_all('/./us', $texto);
    return $cantidad === false ? -1 : $cantidad;
}

$nombre = recibirTexto("nombre");
$email = recibirTexto("email");
$direccion = recibirTexto("direccion");
$telefono = recibirTexto("telefono");

$contrasena = $_POST["contrasena"] ?? "";
$confirmar = $_POST["confirmar"] ?? "";

if (!is_string($contrasena)) {
    $contrasena = "";
}

if (!is_string($confirmar)) {
    $confirmar = "";
}

$errores = [];
$registroExitoso = false;

// Validación de los datos en el servidor.
if (
    longitudTexto($nombre) < 1 ||
    longitudTexto($nombre) > 100
) {
    $errores[] = "El nombre debe tener entre 1 y 100 caracteres.";
}

if (
    strlen($email) > 190 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    $errores[] = "Ingresa un correo electrónico válido de hasta 190 caracteres.";
}

if (
    longitudTexto($direccion) < 1 ||
    longitudTexto($direccion) > 255
) {
    $errores[] = "La dirección debe tener entre 1 y 255 caracteres.";
}

if (!preg_match('/^[0-9+() -]{7,25}$/', $telefono)) {
    $errores[] = "Ingresa un teléfono válido de entre 7 y 25 caracteres.";
}

if (longitudTexto($contrasena) < 10) {
    $errores[] = "La contraseña debe tener al menos 10 caracteres.";
}

// Bcrypt admite un máximo de 72 bytes.
if (strlen($contrasena) > 72) {
    $errores[] = "La contraseña es demasiado larga: usa un máximo de 72 bytes.";
}

if (strpos($contrasena, "\0") !== false) {
    $errores[] = "La contraseña contiene un carácter no permitido.";
}

if (!preg_match('/[A-Z]/', $contrasena)) {
    $errores[] = "La contraseña debe incluir una letra mayúscula.";
}

if (!preg_match('/[a-z]/', $contrasena)) {
    $errores[] = "La contraseña debe incluir una letra minúscula.";
}

if (!preg_match('/[0-9]/', $contrasena)) {
    $errores[] = "La contraseña debe incluir un número.";
}

if (!preg_match('/[^A-Za-z0-9\s]/u', $contrasena)) {
    $errores[] = "La contraseña debe incluir un símbolo, por ejemplo ! o #.";
}

if ($contrasena !== $confirmar) {
    $errores[] = "Las contraseñas no coinciden.";
}

// Guarda el usuario solo cuando todos los datos son válidos.
if (count($errores) === 0) {
    require_once __DIR__ . "/conexion.php";

    try {
        // Comprueba si el correo ya está registrado.
        $consulta = $conexion->prepare(
            "SELECT id_usuario
             FROM USUARIOS
             WHERE email = :email
             LIMIT 1"
        );

        $consulta->execute([
            ":email" => $email
        ]);

        if ($consulta->fetch()) {
            $errores[] = "Ya existe una cuenta con ese correo electrónico.";
        } else {
            // Genera un hash; no guarda la contraseña original.
            $contrasenaProtegida = password_hash(
                $contrasena,
                PASSWORD_BCRYPT
            );

            // El rol queda como 'cliente' por defecto en la tabla.
            $insercion = $conexion->prepare(
                "INSERT INTO USUARIOS
                    (nombre, email, contrasena, direccion, telefono)
                 VALUES
                    (:nombre, :email, :contrasena, :direccion, :telefono)"
            );

            $insercion->execute([
                ":nombre" => $nombre,
                ":email" => $email,
                ":contrasena" => $contrasenaProtegida,
                ":direccion" => $direccion,
                ":telefono" => $telefono
            ]);

            $registroExitoso = true;
        }
    } catch (PDOException $error) {
        // También controla registros simultáneos del mismo correo.
        if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
            $errores[] = "Ya existe una cuenta con ese correo electrónico.";
        } else {
            $errores[] = "No se pudo guardar el usuario. Intenta nuevamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Resultado del registro | Mesa Gourmet</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >

    <link rel="stylesheet" href="estilos.css">
</head>

<body>
    <header class="encabezado text-center py-4">
        <div class="container">
            <h1 class="marca">MESA GOURMET</h1>
            <p class="mb-0">Registro de usuarios</p>
        </div>
    </header>

    <nav class="navbar navbar-expand bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                Mesa Gourmet
            </a>

            <a class="nav-link" href="registro.php">
                Volver al registro
            </a>
        </div>
    </nav>

    <main class="container py-5">
        <section class="panel-login" aria-labelledby="tituloResultado">

            <?php if ($registroExitoso): ?>

                <h2 id="tituloResultado" class="mb-4">
                    Cuenta creada correctamente
                </h2>

                <div class="alert alert-success" role="status">
                    Tu registro se guardó en la base de datos.
                </div>

                <p>
                    Bienvenida o bienvenido,
                    <strong><?= escapar($nombre) ?></strong>.
                </p>

                <p>
                    Correo registrado:
                    <strong><?= escapar($email) ?></strong>
                </p>

                <p class="text-secondary">
                    Tu contraseña se almacenó protegida mediante un hash.
                </p>

                <a href="index.html" class="btn btn-gourmet w-100">
                    Volver al inicio
                </a>

            <?php else: ?>

                <h2 id="tituloResultado" class="mb-4">
                    No se pudo completar el registro
                </h2>

                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= escapar($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <a href="registro.php" class="btn btn-gourmet w-100">
                    Volver al formulario
                </a>

            <?php endif; ?>

        </section>
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
