<?php
session_start();
require 'vendor/autoload.php'; // Incluir Composer autoload
require 'config.php'; // Incluir la configuración con la clave secreta

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // 🔐 Usar la clave secreta fija definida en config.php
    $secret_key = JWT_SECRET_KEY;
    $issued_at = time();
    $expiration_time = $issued_at + (60 * 60 * 24); // 24 horas de vida del token

    // 📦 Construir el payload del JWT
    $payload = [
        'id_usuario' => $user['IdUsuario'],
        'usuario' => $user['Usuario'],
        'tipo_id' => $user['IdTipoDeUsuario'],
        'tipo' => $user['Descripcion'],
        'iat' => $issued_at, // Tiempo de emisión
        'exp' => $expiration_time // Tiempo de expiración
    ];

    // ✅ Generar el JWT firmado con la clave fija
    $jwt = JWT::encode($payload, $secret_key, 'HS256'); // Usar HS256 o RS256

    // 🎉 Devolver el JWT al cliente
    echo json_encode([
        'success' => true,
        'jwt_token' => $jwt,
        'id' => $user['IdUsuario'],
        'usuario' => $user['Usuario'],
        'tipo' => $user['Descripcion'],
        'tipo_id' => $user['IdTipoDeUsuario']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar la base de datos']);
    exit;
}
?>