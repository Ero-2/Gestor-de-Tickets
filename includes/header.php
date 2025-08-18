<?php
// Verificar si la sesión ya está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$is_admin = $_SESSION['user']['IdTipoDeUsuario'] == 1;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="../css/inicio.css">
  
     <style>
    body {
      padding-top: 56px;
    }
    .sidebar {
      position: fixed;
      top: 56px;
      bottom: 0;
      left: 0;
      z-index: 100;
      padding: 20px;
      width: 250px;
      color: #333
      background-color: #f8f9fa;
      border-right: 1px solid #ddd;
    }
    .content {
      margin-left: 250px;
      padding: 20px;
    }

   


  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="inicio.php">GESTION DE TICKETS FASEMEX</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="inicio.php?page=perfil"><i class="bi bi-person-circle"></i> Perfil</a>
          </li>
          <?php if ($is_admin): ?>
            <li class="nav-item">
              <a class="nav-link" href="inicio.php?page=admin"><i class="bi bi-gear-fill"></i> Admin</a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
  <div class="d-flex">
   <div class="sidebar">
  <ul class="nav flex-column">
    <?php if ($is_admin): ?>
      <li class="nav-item">
        <a class="nav-link" href="inicio.php?page=tickets"><i class="bi bi-ticket-detailed"></i> Tickets</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="inicio.php?page=resueltos"><i class="bi bi-ticket-detailed"></i> Tickets Resueltos</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="inicio.php?page=registro"><i class="bi bi-ticket-detailed"></i>Crear Usuario</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="inicio.php?page=gestionar_usuarios"><i class="bi bi-ticket-detailed"></i>Gestionar Usuarios</a>
      </li>
    <?php else: ?>
      <li class="nav-item">
        <a class="nav-link" href="inicio.php?page=tickets"><i class="bi bi-ticket-detailed"></i> Mis Tickets</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="inicio.php?page=crear-ticket"><i class="bi bi-plus-circle"></i> Nuevo Ticket</a>
      </li>
    <?php endif; ?>
    <li class="nav-item">
      <a class="nav-link" href="inicio.php?page=perfil"><i class="bi bi-person"></i> Mi Perfil</a>
    </li>
  </ul>
</div>