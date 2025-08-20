<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

function generateApiKey() {
    return bin2hex(random_bytes(32));
}

$userId = $_SESSION['user']['IdUsuario'];

try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Desactivar la API Key anterior
    $updateSql = 'UPDATE apikey SET Estatus = 0 WHERE "IdUsuario" = :user_id AND Estatus = 1';
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute(['user_id' => $userId]);

    // Generar nueva API Key
    $newApiKey = generateApiKey();

    // Insertar nueva API Key
    $insertSql = 'INSERT INTO apikey ("IdUsuario", ApiKey, Estatus) VALUES (:user_id, :api_key, 1)';
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute(['user_id' => $userId, 'api_key' => $newApiKey]);

    // Actualizar sesión
    $_SESSION['user']['ApiKey'] = $newApiKey;

    echo json_encode([
        'success' => true,
        'message' => 'API Key regenerada correctamente',
        'new_api_key' => $newApiKey
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>