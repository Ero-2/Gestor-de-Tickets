<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
  <div class="container">
    <div class="card p-4 shadow mx-auto" style="width: 30rem;">
      <h4 class="text-center mb-4">Registrar usuario</h4>
      <form id="registroForm">
        <div class="mb-3">
          <label for="usuario" class="form-label">Nombre de usuario</label>
          <input type="text" class="form-control" name="usuario" required>
        </div>
        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre completo</label>
          <input type="text" class="form-control" name="nombre" required>
        </div>
        <div class="mb-3">
          <label for="clave" class="form-label">Contraseña</label>
          <input type="password" class="form-control" name="clave" required>
        </div>
        <div class="mb-3">
          <label for="departamento" class="form-label">Departamento</label>
          <select class="form-control" name="IdDepartamentos" id="departamentosSelect" required>
            <option value="">Selecciona un departamento</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="tipo_usuario" class="form-label">Tipo de usuario</label>
          <select class="form-control" name="tipo_usuario" id="tiposUsuarioSelect" required>
            <option value="">Selecciona un tipo de usuario</option>
          </select>
        </div>
        <button type="submit" class="btn btn-success w-100">Registrar</button>
      </form>
      <div class="mt-3 text-center">
        <a href="login.php">Volver al login</a>
      </div>
    </div>
  </div>

  <!-- Script para cargar departamentos y tipos de usuario -->
  <script>
  document.addEventListener('DOMContentLoaded', function () {
      // Cargar departamentos y tipos de usuario
      fetch('/GestionDeTickets/api/obtener.php')
          .then(response => {
              if (!response.ok) {
                  throw new Error(`Error en la respuesta de la API: ${response.status} ${response.statusText}`);
              }
              return response.json();
          })
          .then(data => {
              const deptoSelect = document.getElementById('departamentosSelect');
              const tipoSelect = document.getElementById('tiposUsuarioSelect');

              // Rellenar departamentos
              data.departamentos.forEach(dep => {
                  const option = document.createElement('option');
                  option.value = dep.IdDepartamentos;
                  option.textContent = dep.nombre;
                  deptoSelect.appendChild(option);
              });

              // Rellenar tipos de usuario
              data.tipos_usuario.forEach(tipo => {
                  const option = document.createElement('option');
                  option.value = tipo.IdTipoDeUsuario;
                  option.textContent = tipo.Descripcion;
                  tipoSelect.appendChild(option);
              });
          })
          .catch(error => {
              console.error('Error al cargar datos:', error);
              alert('Hubo un problema al cargar los datos. Recarga la página o inténtalo más tarde.');
          });

      // Enviar formulario como JSON
      document.getElementById('registroForm').addEventListener('submit', async function(e) {
          e.preventDefault();

          const formData = {
              usuario: document.querySelector('[name="usuario"]').value,
              nombre: document.querySelector('[name="nombre"]').value,
              clave: document.querySelector('[name="clave"]').value,
              IdDepartamentos: document.querySelector('[name="IdDepartamentos"]').value,
              tipo_usuario: document.querySelector('[name="tipo_usuario"]').value
          };

          try {
              const response = await fetch('/GestionDeTickets/api/registrar.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json'
                  },
                  body: JSON.stringify(formData)
              });

              const result = await response.json();

              if (result.success) {
                  alert('Usuario registrado exitosamente');
                  window.location.href = '/GestionDeTickets/login.php';
              } else {
                  alert(result.error);
              }
          } catch (error) {
              console.error('Error al registrar usuario:', error);
              alert('Hubo un problema al registrar el usuario. Inténtalo más tarde.');
          }
      });
  });
  </script>
</body>
</html>