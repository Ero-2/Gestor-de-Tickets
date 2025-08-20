<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

$userId = $_SESSION['user']['IdUsuario'];

try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = 'SELECT ApiKey FROM apikey WHERE "IdUsuario" = :user_id AND Estatus = 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $apiKey = $stmt->fetchColumn();

    if ($apiKey) {
        echo json_encode([
            'success' => true,
            'api_key' => $apiKey
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API Key no encontrada']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos']);
}
?>