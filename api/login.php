<?php
session_start();
error_reporting(E_ALL); // Para depuración
ini_set('display_errors', 1); // Para depuración

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$usuario = $input['usuario'] ?? null;
$clave = $input['clave'] ?? null;

if (!$usuario || !$clave) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña requeridos']);
    exit;
}

// Conexión a PostgreSQL
try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar a la base de datos']);
    exit;
}

// Consulta de usuario + tipo de usuario
try {
    $sql = '
        SELECT u."IdUsuario", u."Usuario", u."Contrasena", u."IdTipoDeUsuario", t."Descripcion"
        FROM "usuario" u
        JOIN "TipoUsuario" t ON u."IdTipoDeUsuario" = t."IdTipoDeUsuario"
        WHERE u."Usuario" = :usuario
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($clave, $user['Contrasena'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales inválidas']);
        exit;
    }

    // Login exitoso
    echo json_encode([
        'success' => true,
        'id' => $user['IdUsuario'],
        'usuario' => $user['Usuario'],
        'tipo' => $user['Descripcion'],
        'tipo_id' => $user['IdTipoDeUsuario']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar la base de datos']);
}
?>