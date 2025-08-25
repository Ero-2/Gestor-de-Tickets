<?php
session_start();
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
    echo json_encode(['error' => 'Error al conectar a la base de datos: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $params = [];
            $where = [];

            // Filtro fijo: Solo tickets en espera
            $where[] = '"Tickets"."EstadoTicket" = :estado';
            $params['estado'] = 'En espera';

            // Filtro por departamento
            if (!empty($_GET['departamento_id'])) {
                $where[] = '"Tickets"."IdDepartamentoDestino" = :departamento_id';
                $params['departamento_id'] = intval($_GET['departamento_id']);
            }

            // Filtro por prioridad con mapeo
            if (!empty($_GET['prioridad'])) {
                $prioridad_raw = strtolower(trim($_GET['prioridad']));
                $mapPrioridad = [
                    'baja'  => 'Baja',
                    'media' => 'Media',
                    'alta'  => 'Alta'
                ];
                if (isset($mapPrioridad[$prioridad_raw])) {
                    $where[] = '"Tickets"."Prioridad" = :prioridad';
                    $params['prioridad'] = $mapPrioridad[$prioridad_raw];
                }
            }

            // Consulta base
            $sql = '
                SELECT 
                    "Tickets".*, 
                    "usuario"."nombre" AS "usuario_nombre", 
                    "usuario"."Puesto" AS "usuario_puesto",
                    "usuario"."FotoPerfil" AS "usuario_foto"
                FROM "Tickets"
                LEFT JOIN "usuario" ON "Tickets"."IdUsuarioCreador" = "usuario"."IdUsuario"
            ';

            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY "Tickets"."FechaCreacion" DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Contadores (en espera, resueltos, urgentes)
            $stmt_counts = $pdo->query('
                SELECT 
                    COUNT(*) AS total_tickets,
                    SUM(CASE WHEN "EstadoTicket" = \'En espera\' THEN 1 ELSE 0 END) AS pending_tickets,
                    SUM(CASE WHEN "EstadoTicket" = \'Resuelto\' THEN 1 ELSE 0 END) AS resolved_tickets,
                    SUM(CASE WHEN "Prioridad" = \'Urgente\' THEN 1 ELSE 0 END) AS urgent_tickets
                FROM "Tickets"
            ');
            $counts = $stmt_counts->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'tickets' => $tickets,
                'counts' => $counts
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener los tickets: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Crear nuevo ticket
        $data = json_decode(file_get_contents('php://input'), true);

        $titulo = $data['titulo'] ?? null;
        $descripcion = $data['descripcion'] ?? null;
        $prioridad = $data['prioridad'] ?? null;
        $id_departamento_destino = intval($data['id_departamento_destino']) ?? null;

        if (!$titulo || !$descripcion || !$prioridad || !$id_departamento_destino) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos']);
            exit;
        }

        if (!isset($_SESSION['user']['IdUsuario'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO "Tickets" (
                    "Titulo", "Descripcion", "Prioridad", "EstadoTicket",
                    "IdUsuarioCreador", "IdDepartamentoDestino"
                ) VALUES (
                    :titulo, :descripcion, :prioridad, \'En espera\',
                    :id_usuario_creador, :id_departamento_destino
                ) RETURNING "IdTickets"
            ');

            $stmt->execute([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'prioridad' => $prioridad,
                'id_usuario_creador' => $_SESSION['user']['IdUsuario'],
                'id_departamento_destino' => $id_departamento_destino
            ]);

            $ticketId = $stmt->fetchColumn();

            //  notification: Enviar notificación a administradores
            // Aquí puedes obtener todos los admins o un admin específico
            $adminUserId = 1; // Ejemplo: admin con ID 1
            sendWebSocketNotification(
                $adminUserId, 
                "Nuevo ticket creado por {$_SESSION['user']['Usuario']}: $titulo", 
                $ticketId
            );

            echo json_encode([
                'success' => true, 
                'message' => 'Ticket creado',
                'ticket_id' => $ticketId
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al insertar el ticket: ' . $e->getMessage()]);
        }
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

        try {
            // Actualizar el estado del ticket
            $stmt_update = $pdo->prepare('
                UPDATE "Tickets"
                SET "EstadoTicket" = :estado, "FechaModificar" = NOW()
                WHERE "IdTickets" = :id_ticket
            ');
            $stmt_update->execute([
                'id_ticket' => $id_ticket,
                'estado' => $estado
            ]);

            // Verificar si el ticket fue actualizado correctamente
            if ($stmt_update->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket no encontrado']);
                exit;
            }

            // Insertar registro en la tabla Historial
            $stmt_insert = $pdo->prepare('
                INSERT INTO "Historial" (
                    "IdTicket", "Accion", "IdUsuario", "FechaAccion"
                ) VALUES (
                    :id_ticket, :accion, :id_usuario, NOW()
                )
            ');

            // Obtener el ID del usuario actualmente autenticado
            $id_usuario = $_SESSION['user']['IdUsuario'];

            $stmt_insert->execute([
                'id_ticket' => $id_ticket,
                'accion' => 'Resolver',
                'id_usuario' => $id_usuario
            ]);

            //  notification: Notificar al creador del ticket
            $creatorStmt = $pdo->prepare('SELECT "IdUsuarioCreador" FROM "Tickets" WHERE "IdTickets" = :ticket_id');
            $creatorStmt->execute(['ticket_id' => $id_ticket]);
            $creator = $creatorStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($creator && $creator['IdUsuarioCreador'] != $id_usuario) {
                sendWebSocketNotification(
                    $creator['IdUsuarioCreador'],
                    "Tu ticket ha sido marcado como: $estado",
                    $id_ticket
                );
            }

            echo json_encode(['success' => true, 'message' => 'Ticket actualizado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el ticket: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Eliminar ticket
        $id_ticket = $_GET['id'] ?? null;

        if (!$id_ticket) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el ID del ticket']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM "Tickets" WHERE "IdTickets" = :id_ticket');
            $stmt->execute(['id_ticket' => $id_ticket]);

            echo json_encode(['success' => true, 'message' => 'Ticket eliminado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el ticket: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>