<?php
// inicio.php - Plantilla principal de la aplicación

// Inicia la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica si el usuario está autenticado
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';

// Definir páginas permitidas según el tipo de usuario
$is_admin = $_SESSION['user']['IdTipoDeUsuario'] == 1;
$allowed_pages = ['dashboard', 'perfil','registro'];

if ($is_admin) {
    // Páginas permitidas para administradores
    $allowed_pages = array_merge($allowed_pages, ['tickets', 'admin', 'registro', 'resueltos', 'gestionar_usuarios']);
} else {
    // Páginas permitidas para usuarios normales
    $allowed_pages = array_merge($allowed_pages, ['tickets', 'crear-ticket']);
}

if (!in_array($page, $allowed_pages)) {
    http_response_code(404);
    $page = '404';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestión de Tickets</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="css/inicio.css">
</head>
<body>

  <?php include __DIR__ . '/includes/header.php'; ?>

  <div class="d-flex">
    <main class="flex-fill content">
      <?php
        // Incluye la vista correspondiente según la página solicitada
        $viewFile = __DIR__ . "/views/{$page}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<div class="alert alert-warning">Página no encontrada.</div>';
        }
      ?>
    </main>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/tickets.js"></script>
</body>
</html>