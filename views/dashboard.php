<?php
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$is_admin = $_SESSION['user']['IdTipoDeUsuario'] == 1;
$welcome_message = $is_admin ? 'Bienvenido, Administrador' : 'Bienvenido al Sistema de Tickets';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1><?php echo $welcome_message; ?></h1>
            <p>Hola, <?php echo isset($_SESSION['user']['Nombre']) ? htmlspecialchars($_SESSION['user']['Nombre']) : 'Usuario'; ?>!</p>
            
            <?php if ($is_admin): ?>
                <div class="alert alert-info">
                    <h4>Panel de Administración</h4>
                    <p>Como administrador, puedes gestionar todos los tickets del sistema.</p>
                    <a href="inicio.php?page=tickets" class="btn btn-primary">Ver Todos los Tickets</a>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <h4>Mi Panel de Usuario</h4>
                    <p>Gestiona tus tickets y crea nuevos cuando lo necesites.</p>
                    <a href="inicio.php?page=tickets" class="btn btn-primary">Ver Mis Tickets</a>
                    <a href="inicio.php?page=crear-ticket" class="btn btn-success">Crear Nuevo Ticket</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    body {
      padding-top: 56px; /* Espacio para la barra de navegación fija */
    }
    .sidebar {
      position: fixed;
      top: 56px; /* Debajo de la barra de navegación */
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
    .nav-link.active {
      background-color: #007bff;
      color: white;
    }
</style>