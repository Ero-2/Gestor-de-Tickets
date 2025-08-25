<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">
  <div class="container">
    <header class="text-center mb-4">
      <h1>GESTION DE TICKETS FASEMEX</h1>
      <nav>
        <ul class="list-inline">
          <li class="list-inline-item"><a href="#">Ayuda</a></li>
        </ul>
      </nav>
    </header>

    <div class="row">
      <div class="col-md-6 image-container">
        <img src="fotos/login.png" alt="Login Image" class="img-fluid rounded">
      </div>

      <div class="card p-4 shadow mx-auto" style="width: 24rem;">
        <h4 class="text-center mb-4">Iniciar sesión</h4>
        <form id="loginForm">
          <div class="mb-3">
            <label for="usuario" class="form-label">Usuario</label>
            <input type="text" class="form-control" id="usuario" required>
          </div>
          <div class="mb-3">
            <label for="clave" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="clave" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
        <div id="mensaje" class="mt-3 text-center"></div>

        <div class="text-center mt-3">
          <a href="registro.php">¿No tienes cuenta? Regístrate aquí</a>
        </div>

        <div class="text-center mt-3">
          <a href="recuperar.php">Recupera tu contraseña</a>
        </div>

        <!-- Mostrar API Key después del login (opcional) -->
        <div id="apiKeyInfo" class="mt-3 text-center" style="display: none;">
          <small>Tu API Key: <span id="apiKey" class="text-muted"></span></small>
        </div>
      </div>
    </div>
  </div>

  
  <script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();

      const usuario = document.getElementById('usuario').value.trim();
      const clave = document.getElementById('clave').value.trim();
      const mensaje = document.getElementById('mensaje');

      mensaje.textContent = '';
      mensaje.classList.remove('text-danger', 'text-success');

      try {
        const response = await fetch('/GestionDeTickets/api/auth/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ usuario, clave })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
          mensaje.textContent = data.error || 'Credenciales incorrectas';
          mensaje.classList.add('text-danger');
          return;
        }

        mensaje.textContent = 'Login exitoso. Redirigiendo...';
        mensaje.classList.add('text-success');

        setTimeout(() => {
          window.location.href = '/GestionDeTickets/inicio.php';
        }, 1500);

      } catch (error) {
        console.error('Fetch error:', error);
        mensaje.textContent = 'Error al conectar con el servidor';
        mensaje.classList.add('text-danger');
      }
    });
  </script>
</body>
</html>