<?php
// Copia este archivo con el nombre conexion.php
// y configura las credenciales de tu instalación local.

$servidor = "localhost";
$baseDatos = "GOURMET";
$usuario = "USUARIO_MYSQL";
$contrasenaBD = "CONTRASENA_MYSQL";

$dsn = "mysql:host=$servidor;dbname=$baseDatos;charset=utf8mb4";

$opciones = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $conexion = new PDO(
        $dsn,
        $usuario,
        $contrasenaBD,
        $opciones
    );
} catch (PDOException $error) {
    http_response_code(500);

    exit(
        "No se pudo conectar con la base de datos. " .
        "Revisa el servicio MySQL y la configuración local."
    );
}
