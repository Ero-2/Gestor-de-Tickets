<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestión de Tickets</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons @1.10.5/font/bootstrap-icons.css">
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
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php">Gestión de Tickets</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="perfil.php"><i class="bi bi-person-circle"></i> Perfil</a>
          </li>
          <?php if ($_SESSION['user']['IdTipoDeUsuario'] == 1): ?>
            <li class="nav-item">
              <a class="nav-link" href="admin.php"><i class="bi bi-gear-fill"></i> Admin</a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Sidebar (opcional) -->
  <div class="d-flex">
    <div class="sidebar">
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="tickets.php"><i class="bi bi-ticket-detailed"></i> Mis Tickets</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="crear-ticket.php"><i class="bi bi-plus-circle"></i> Nuevo Ticket</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="perfil.php"><i class="bi bi-person"></i> Mi Perfil</a>
        </li>
        <?php if ($_SESSION['user']['IdTipoDeUsuario'] == 1): ?>
          <li class="nav-item">
            <a class="nav-link" href="admin.php"><i class="bi bi-shield-fill-gear"></i> Panel Admin</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Contenido principal -->
    <div class="content">