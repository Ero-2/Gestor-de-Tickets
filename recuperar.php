<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card {
      border: none;
      border-radius: 15px;
    }
    .btn-primary {
      background-color: #0078d4;
      border: none;
      padding: 10px;
      font-weight: 500;
    }
    .btn-primary:hover {
      background-color: #106ebe;
    }
    .form-control:focus {
      border-color: #0078d4;
      box-shadow: 0 0 0 0.25rem rgba(0, 120, 212, 0.25);
    }
  </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
  <div class="card p-4 shadow" style="width: 24rem;">
    <div class="text-center mb-4">
      <h4 class="mb-1">Recuperar contraseña</h4>
      <p class="text-muted small">Ingresa tu correo para recibir el enlace</p>
    </div>
    
    <form id="recuperarForm">
      <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control" id="email" placeholder="tu@email.com" required>
      </div>
      <button type="submit" class="btn btn-primary w-100" id="submitBtn">
        <span id="btnText">Enviar enlace de recuperación</span>
        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
      </button>
    </form>
    
    <div id="mensaje" class="mt-3 text-center"></div>
    
    <div class="text-center mt-3">
      <a href="login.php" class="text-decoration-none">
        <small>← Volver al inicio de sesión</small>
      </a>
    </div>
  </div>

  <script>
    document.getElementById('recuperarForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const email = document.getElementById('email').value.trim();
      const mensaje = document.getElementById('mensaje');
      const submitBtn = document.getElementById('submitBtn');
      const btnText = document.getElementById('btnText');
      const btnSpinner = document.getElementById('btnSpinner');
      
      // Validación básica
      if (!email) {
        mostrarMensaje('Por favor ingresa un correo electrónico', 'danger');
        return;
      }
      
      // Validar formato de email
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        mostrarMensaje('Por favor ingresa un correo válido', 'danger');
        return;
      }
      
      // Mostrar estado de carga
      submitBtn.disabled = true;
      btnText.textContent = 'Enviando...';
      btnSpinner.classList.remove('d-none');
      mensaje.textContent = '';
      
      try {
        const res = await fetch('/GestionDeTickets/api/auth/request_password.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email })
        });
        
        const data = await res.json();
        
        if (!res.ok || !data.success) {
          mostrarMensaje(data.error || data.message || 'Error desconocido', 'danger');
        } else {
          mostrarMensaje(data.message || 'Enlace enviado correctamente. Revisa tu correo.', 'success');
          document.getElementById('recuperarForm').reset();
        }
        
      } catch (err) {
        mostrarMensaje('Error de conexión con el servidor. Inténtalo más tarde.', 'danger');
      } finally {
        // Restaurar botón
        submitBtn.disabled = false;
        btnText.textContent = 'Enviar enlace de recuperación';
        btnSpinner.classList.add('d-none');
      }
    });
    
    function mostrarMensaje(texto, tipo) {
      const mensaje = document.getElementById('mensaje');
      mensaje.textContent = texto;
      mensaje.className = `text-${tipo} mt-3 text-center`;
      
      // Auto ocultar mensaje de éxito después de 5 segundos
      if (tipo === 'success') {
        setTimeout(() => {
          mensaje.textContent = '';
          mensaje.className = '';
        }, 5000);
      }
    }
  </script>
</body>
</html>