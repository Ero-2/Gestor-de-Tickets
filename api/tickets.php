<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión a PostgreSQL
try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar a la base de datos']);
    exit;
}

// Manejo de rutas
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Listar tickets
        $stmt = $pdo->query('SELECT * FROM "tickets" ORDER BY "FechaCreacion" DESC');
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tickets);
        break;

    case 'POST':
        // Crear nuevo ticket
        $data = json_decode(file_get_contents('php://input'), true);
        $titulo = $data['titulo'] ?? null;
        $descripcion = $data['descripcion'] ?? null;
        $prioridad = $data['prioridad'] ?? null;
        $id_departamento_destino = $data['id_departamento_destino'] ?? null;

        if (!$titulo || !$descripcion || !$prioridad || !$id_departamento_destino) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos']);
            exit;
        }

        $stmt = $pdo->prepare('
            INSERT INTO "tickets" (
                "Titulo", "Descripcion", "Prioridad", "EstadoTicket",
                "IdUsuarioCreador", "IdDepartamentoDestino"
            ) VALUES (
                :titulo, :descripcion, :prioridad, \'Abierto\',
                :id_usuario_creador, :id_departamento_destino
            )
        ');

        $stmt->execute([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'prioridad' => $prioridad,
            'id_usuario_creador' => $_SESSION['user']['IdUsuario'],
            'id_departamento_destino' => $id_departamento_destino
        ]);

        echo json_encode(['success' => true, 'message' => 'Ticket creado']);
        break;

    case 'PUT':
        // Actualizar estado de un ticket
        $data = json_decode(file_get_contents('php://input'), true);
        $id_ticket = $data['id_ticket'] ?? null;
        $estado = $data['estado'] ?? null;

        if (!$id_ticket || !$estado) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos']);
            exit;
        }

        $stmt = $pdo->prepare('
            UPDATE "tickets"
            SET "EstadoTicket" = :estado, "FechaModificar" = NOW()
            WHERE "IdTickets" = :id_ticket
        ');

        $stmt->execute(['id_ticket' => $id_ticket, 'estado' => $estado]);

        echo json_encode(['success' => true, 'message' => 'Ticket actualizado']);
        break;

    case 'DELETE':
        // Eliminar ticket (solo para admin)
        $id_ticket = $_GET['id'] ?? null;

        if (!$id_ticket) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el ID del ticket']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM "tickets" WHERE "IdTickets" = :id_ticket');
        $stmt->execute(['id_ticket' => $id_ticket]);

        echo json_encode(['success' => true, 'message' => 'Ticket eliminado']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}