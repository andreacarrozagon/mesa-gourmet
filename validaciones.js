(() => {
  "use strict";

  // Cambiar a false cuando creemos procesar_login.php.
  const MODO_DEMOSTRACION = false;

  function iniciarValidacion() {
    const formulario = document.getElementById("formLogin");
    const correo = document.getElementById("email");
    const contrasena = document.getElementById("contrasena");

    const errorCorreo = document.getElementById("email-error");
    const errorContrasena =
      document.getElementById("contrasena-error");

    const estadoFormulario =
      document.getElementById("estadoFormulario");

    // Identifica un problema de nombres o elementos faltantes.
    if (
      !formulario ||
      !correo ||
      !contrasena ||
      !errorCorreo ||
      !errorContrasena ||
      !estadoFormulario
    ) {
      console.error(
        "Faltan elementos del formulario. " +
        "Comprueba los identificadores de login.html."
      );
      return;
    }

    // Evita registrar eventos dos veces por una carga duplicada.
    if (formulario.dataset.validacionActiva === "true") {
      return;
    }

    formulario.dataset.validacionActiva = "true";

    function mostrarResultado(campo, contenedor, mensaje) {
      const valido = mensaje === "";

      contenedor.textContent = mensaje;

      campo.classList.toggle("is-invalid", !valido);
      campo.classList.toggle("is-valid", valido);

      campo.setAttribute("aria-invalid", String(!valido));

      return valido;
    }

    function validarCorreo() {
      let mensaje = "";

      if (correo.value.trim() === "") {
        mensaje = "Ingresa tu correo electrónico.";
      } else if (correo.validity.typeMismatch) {
        mensaje =
          "Escribe un correo válido, por ejemplo: nombre@ejemplo.cl.";
      } else if (correo.value.length > 190) {
        mensaje = "El correo no puede superar los 190 caracteres.";
      }

      return mostrarResultado(
        correo,
        errorCorreo,
        mensaje
      );
    }

    function validarContrasena() {
      const valor = contrasena.value;
      const requisitos = [];

      if (valor === "") {
        return mostrarResultado(
          contrasena,
          errorContrasena,
          "Ingresa tu contraseña."
        );
      }

      if (valor.length < 10) {
        requisitos.push("10 caracteres como mínimo");
      }

      if (!/[A-Z]/.test(valor)) {
        requisitos.push("una mayúscula");
      }

      if (!/[a-z]/.test(valor)) {
        requisitos.push("una minúscula");
      }

      if (!/[0-9]/.test(valor)) {
        requisitos.push("un número");
      }

      if (!/[^A-Za-z0-9\s]/.test(valor)) {
        requisitos.push("un símbolo");
      }

      const mensaje = requisitos.length > 0
        ? "La contraseña debe incluir: " +
          requisitos.join(", ") + "."
        : "";

      return mostrarResultado(
        contrasena,
        errorContrasena,
        mensaje
      );
    }

    function limpiarEstado() {
      estadoFormulario.textContent = "";
      estadoFormulario.classList.remove(
        "text-success",
        "text-danger"
      );
    }

    correo.addEventListener("input", () => {
      limpiarEstado();
      validarCorreo();
    });

    contrasena.addEventListener("input", () => {
      limpiarEstado();
      validarContrasena();
    });

    correo.addEventListener("blur", validarCorreo);
    contrasena.addEventListener("blur", validarContrasena);

    formulario.addEventListener("submit", (evento) => {
      limpiarEstado();

      const correoValido = validarCorreo();
      const contrasenaValida = validarContrasena();

      if (!correoValido || !contrasenaValida) {
        evento.preventDefault();

        estadoFormulario.textContent =
          "Revisa los campos señalados antes de continuar.";

        estadoFormulario.classList.add("text-danger");

        if (!correoValido) {
          correo.focus();
        } else {
          contrasena.focus();
        }

        return;
      }

      if (MODO_DEMOSTRACION) {
        evento.preventDefault();

        estadoFormulario.textContent =
          "Formato válido. La verificación de la cuenta " +
          "se implementará con PHP.";

        estadoFormulario.classList.add("text-success");
      }
    });
  }

  // Funciona tanto si el HTML está cargando como si ya terminó.
  if (document.readyState === "loading") {
    document.addEventListener(
      "DOMContentLoaded",
      iniciarValidacion,
      { once: true }
    );
  } else {
    iniciarValidacion();
  }
})();

