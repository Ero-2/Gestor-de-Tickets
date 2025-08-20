<?php
session_start();
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

// Función para generar API Key
function generateApiKey() {
    return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
}

// Conexión a PostgreSQL
try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar a la base de datos: ' . $e->getMessage()]);
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

    // Verificar si ya existe una API Key activa
    $checkSql = 'SELECT "ApiKey" FROM "apikey" WHERE "IdUsuario" = :user_id AND "Estatus" = 1';
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['user_id' => $user['IdUsuario']]);
    $existingApiKey = $checkStmt->fetchColumn();

    if ($existingApiKey) {
        $apiKey = $existingApiKey;
    } else {
        // Generar nueva API Key
        $apiKey = generateApiKey();

        // Insertar nueva API Key
        $insertSql = '
            INSERT INTO "apikey" ("IdUsuario", "ApiKey", "Estatus", "FechaCreacion", "FechaModificar")
            VALUES (:user_id, :api_key, 1, NOW(), NOW())
        ';
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            'user_id' => $user['IdUsuario'],
            'api_key' => $apiKey
        ]);
    }

    // Guardar en sesión
    $_SESSION['user'] = [
        'IdUsuario' => $user['IdUsuario'],
        'Usuario' => $user['Usuario'],
        'nombre' => $user['Usuario'],
        'IdTipoDeUsuario' => $user['IdTipoDeUsuario'],
        'tipo' => $user['Descripcion'],
        'ApiKey' => $apiKey
    ];

    // ✅ Devolver la API Key en la respuesta
    echo json_encode([
        'success' => true,
        'id' => $user['IdUsuario'],
        'usuario' => $user['Usuario'],
        'tipo' => $user['Descripcion'],
        'tipo_id' => $user['IdTipoDeUsuario'],
        'api_key' => $apiKey
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    exit;
}
?>