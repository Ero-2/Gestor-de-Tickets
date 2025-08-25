<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
  <div class="card p-4 shadow" style="width: 24rem;">
    <h4 class="text-center mb-4">Nueva contraseña</h4>
    <form id="resetForm">
      <div class="mb-3">
        <label for="password" class="form-label">Nueva contraseña</label>
        <input type="password" class="form-control" id="password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Actualizar contraseña</button>
    </form>
    <div id="mensaje" class="mt-3 text-center"></div>
  </div>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get("token");

    document.getElementById('resetForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const password = document.getElementById('password').value.trim();
      const mensaje = document.getElementById('mensaje');

      try {
        const res = await fetch('/GestionDeTickets/api/auth/reset_password.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token, password })
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
          mensaje.textContent = data.error || data.message;
          mensaje.className = "text-danger";
          return;
        }

        mensaje.textContent = "Contraseña actualizada con éxito. Ya puedes iniciar sesión.";
        mensaje.className = "text-success";

        setTimeout(() => {
          window.location.href = "/GestionDeTickets/login.php";
        }, 2000);

      } catch (err) {
        mensaje.textContent = "Error de conexión con el servidor.";
        mensaje.className = "text-danger";
      }
    });
  </script>
</body>
</html>
