<?php
session_start([
    "use_strict_mode" => true,
    "cookie_httponly" => true,
    "cookie_secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "cookie_samesite" => "Lax"
]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: mi_cuenta.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Solicitud no válida. Vuelve a tu cuenta e intenta nuevamente.");
}

// Elimina los datos de la sesión.
$_SESSION = [];

// Elimina también la cookie del navegador.
if (ini_get("session.use_cookies")) {
    $parametros = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 42000,
        "path" => $parametros["path"],
        "domain" => $parametros["domain"],
        "secure" => $parametros["secure"],
        "httponly" => $parametros["httponly"],
        "samesite" => "Lax"
    ]);
}

session_destroy();

header("Location: login.html", true, 303);
exit;
