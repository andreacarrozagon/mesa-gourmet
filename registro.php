<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

  <title>Crear cuenta | Mesa Gourmet</title>

  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
    crossorigin="anonymous">

  <link rel="stylesheet" href="estilos.css">
</head>

<body>
  <header class="encabezado text-center">
    <div class="container">
      <p class="marca">MESA GOURMET</p>
      <p class="mb-0">Crea tu cuenta para realizar pedidos</p>
    </div>
  </header>

  <nav class="navbar bg-white border-bottom"
       aria-label="Navegación de registro">
    <div class="container">
      <a class="navbar-brand" href="index.html">
        Mesa Gourmet
      </a>

      <a class="nav-link" href="login.html">
        Iniciar sesión
      </a>
    </div>
  </nav>

  <main class="container py-5">
    <section class="panel-login"
             aria-labelledby="tituloRegistro">

      <h1 id="tituloRegistro" class="h2 text-center mb-4">
        Registro de usuarios
      </h1>

      <form action="procesar_registro.php" method="post">

        <div class="mb-3">
          <label for="nombre" class="form-label">
            Nombre completo
          </label>

          <input
            type="text"
            id="nombre"
            name="nombre"
            class="form-control"
            autocomplete="name"
            maxlength="100"
            required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">
            Correo electrónico
          </label>

          <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            autocomplete="email"
            maxlength="190"
            required>
        </div>

        <div class="mb-3">
          <label for="direccion" class="form-label">
            Dirección de envío
          </label>

          <input
            type="text"
            id="direccion"
            name="direccion"
            class="form-control"
            autocomplete="street-address"
            maxlength="255"
            required>
        </div>

        <div class="mb-3">
          <label for="telefono" class="form-label">
            Teléfono
          </label>

          <input
            type="tel"
            id="telefono"
            name="telefono"
            class="form-control"
            autocomplete="tel"
            minlength="7"
            maxlength="25"
            required>
        </div>

        <div class="mb-3">
          <label for="contrasena" class="form-label">
            Contraseña
          </label>

          <input
            type="password"
            id="contrasena"
            name="contrasena"
            class="form-control"
            autocomplete="new-password"
            minlength="10"
            aria-describedby="ayudaRegistro"
            required>

          <div id="ayudaRegistro" class="form-text">
            Usa al menos 10 caracteres, una mayúscula,
            una minúscula, un número y un símbolo.
          </div>
        </div>

        <div class="mb-4">
          <label for="confirmar" class="form-label">
            Repetir contraseña
          </label>

          <input
            type="password"
            id="confirmar"
            name="confirmar"
            class="form-control"
            autocomplete="new-password"
            minlength="10"
            required>
        </div>

        <button type="submit" class="btn btn-gourmet w-100">
          Crear cuenta
        </button>
      </form>

      <p class="text-center mt-4 mb-0">
        ¿Ya tienes una cuenta?
        <a href="login.html">Ingresa aquí</a>
      </p>
    </section>
  </main>

  <footer class="pie-pagina text-center">
    <p class="mb-0">
      Mesa Gourmet · Proyecto académico
    </p>
  </footer>
</body>
</html>

