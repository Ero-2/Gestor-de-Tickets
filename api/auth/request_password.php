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
if (!isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el email']);
    exit;
}

try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT "IdUsuario" FROM "usuario" WHERE "Email" = ?');
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Correo no registrado']);
        exit;
    }

    $idUsuario = $user['IdUsuario'];
    $token = bin2hex(random_bytes(16));
    $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $stmt = $pdo->prepare('INSERT INTO "password_resets"("IdUsuario", token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$idUsuario, $token, $expires]);

    $resetLink = "http://localhost/GestionDeTickets/reset_password.php?token=" . $token;

    // Enviar correo
    require_once 'mail_helper.php';
    
    $subject = "Recuperar Contraseña - Sistema de Tickets";
    $body = generateResetEmailBody($resetLink);
    
    if (sendEmail($data['email'], $subject, $body)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Correo enviado correctamente. Revisa tu bandeja de entrada.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al enviar el correo. Inténtalo más tarde.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}