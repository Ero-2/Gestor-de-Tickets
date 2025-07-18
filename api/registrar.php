<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener y decodificar los datos del cuerpo de la solicitud
$input = json_decode(file_get_contents('php://input'), true);

// Recoger los campos del formulario
$usuario = $input['usuario'] ?? null;
$nombre = $input['nombre'] ?? null;
$clave = $input['clave'] ?? null;
$id_departamentos = $input['IdDepartamentos'] ?? null;
$tipo_usuario = $input['tipo_usuario'] ?? null;

// Validar campos obligatorios
if (!$usuario || !$nombre || !$clave || !$id_departamentos || !$tipo_usuario) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos requeridos']);
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

// Verificar si el usuario ya existe
try {
    $stmt = $pdo->prepare('SELECT * FROM "usuario" WHERE "Usuario" = :usuario');
    $stmt->execute(['usuario' => $usuario]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'El usuario ya existe']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al verificar usuario']);
    exit;
}

// Encriptar contraseña
$hash = password_hash($clave, PASSWORD_DEFAULT);

// Insertar nuevo usuario
try {
    $stmt = $pdo->prepare('
        INSERT INTO "usuario" ("Usuario", "nombre", "Contrasena", "IdTipoDeUsuario", "IdDepartamentos")
        VALUES (:usuario, :nombre, :contrasena, :tipo_usuario, :id_departamentos)
    ');

    $stmt->execute([
        'usuario' => $usuario,
        'nombre' => $nombre,
        'contrasena' => $hash,
        'tipo_usuario' => $tipo_usuario,
        'id_departamentos' => $id_departamentos
    ]);

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Usuario registrado exitosamente'
    ]);

} catch (PDOException $e) {
    // Error al insertar
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar usuario: ' . $e->getMessage()]);
}