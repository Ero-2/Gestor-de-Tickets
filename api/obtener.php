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

try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar a la base de datos']);
    exit;
}

// Obtener departamentos
try {
    $stmt = $pdo->query('SELECT "IdDepartamentos", "nombre" FROM "departamentos" ORDER BY "nombre"');
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener departamentos']);
    exit;
}

// Obtener tipos de usuario
try {
    $stmt = $pdo->query('SELECT "IdTipoDeUsuario", "Descripcion" FROM "TipoUsuario" ORDER BY "Descripcion"');
    $tipos_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener tipos de usuario']);
    exit;
}

// Devolver ambos como JSON
echo json_encode([
    'success' => true,
    'departamentos' => $departamentos,
    'tipos_usuario' => $tipos_usuario
]);