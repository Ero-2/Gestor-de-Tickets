<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['token']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos']);
    exit;
}

try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar token (cambiado 'used' a 'usado')
    $stmt = $pdo->prepare('SELECT pr."IdUsuario" FROM "password_resets" pr 
                           WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.usado = false');
    $stmt->execute([$data['token']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Token inválido o expirado']);
        exit;
    }

    $idUsuario = $row['IdUsuario'];
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);

    // Actualizar contraseña
    $stmt = $pdo->prepare('UPDATE "usuario" SET "Contrasena" = ? WHERE "IdUsuario" = ?');
    $stmt->execute([$hash, $idUsuario]);

    // Marcar token como usado (cambiado 'used' a 'usado')
    $stmt = $pdo->prepare('UPDATE "password_resets" SET usado = true WHERE token = ?');
    $stmt->execute([$data['token']]);

    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>