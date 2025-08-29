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

            // Filtro por usuario creador (para no admins)
            if (isset($_GET['user_id']) && ($user_id = intval($_GET['user_id'])) > 0) {
                $where[] = '"Tickets"."IdUsuarioCreador" = :user_id';
                $params['user_id'] = $user_id;
            }

            // Paginación
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;
            $offset = ($page - 1) * $limit;

            // Consulta de conteo total (filtrado)
            $where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
            $count_sql = '
                SELECT COUNT(*)
                FROM "Tickets"
                LEFT JOIN "usuario" ON "Tickets"."IdUsuarioCreador" = "usuario"."IdUsuario"
                ' . $where_sql;
            $stmt_count = $pdo->prepare($count_sql);
            $stmt_count->execute($params);
            $total = $stmt_count->fetchColumn();

            // Consulta base
            $sql = '
                SELECT 
                    "Tickets".*, 
                    "usuario"."nombre" AS "usuario_nombre", 
                    "usuario"."Puesto" AS "usuario_puesto",
                    "usuario"."FotoPerfil" AS "usuario_foto"
                FROM "Tickets"
                LEFT JOIN "usuario" ON "Tickets"."IdUsuarioCreador" = "usuario"."IdUsuario"
                ' . $where_sql . '
                ORDER BY "Tickets"."FechaCreacion" DESC
                LIMIT :limit OFFSET :offset
            ';

            $stmt = $pdo->prepare($sql);
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Contadores globales (sin filtros)
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
                'total' => $total,
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
        echo json_encode(['error' => 'Faltan campos requeridos: id_ticket, estado']);
        exit;
    }

    // Validar estado permitido
    if (!in_array($estado, ['Resuelto', 'En espera'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Estado no válido']);
        exit;
    }

    try {
        // Verificar que el ticket exista
        $stmt_check = $pdo->prepare('SELECT "EstadoTicket" FROM "Tickets" WHERE "IdTickets" = :id_ticket');
        $stmt_check->execute(['id_ticket' => $id_ticket]);
        $ticket = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['error' => 'Ticket no encontrado']);
            exit;
        }

        // Si no está en ese estado, no hacer nada
        if ($ticket['EstadoTicket'] === $estado) {
            echo json_encode(['success' => true, 'message' => 'El ticket ya está en ese estado']);
            exit;
        }

        // Actualizar el estado
        $stmt_update = $pdo->prepare('
            UPDATE "Tickets"
            SET "EstadoTicket" = :estado, "FechaModificar" = NOW()
            WHERE "IdTickets" = :id_ticket
        ');

        $stmt_update->execute([
            'id_ticket' => $id_ticket,
            'estado' => $estado
        ]);

        // Insertar en Historial
        $stmt_historial = $pdo->prepare('
            INSERT INTO "Historial" ("IdTicket", "Accion", "IdUsuario", "FechaAccion")
            VALUES (:id_ticket, :accion, :id_usuario, NOW())
        ');

        $stmt_historial->execute([
            'id_ticket' => $id_ticket,
            'accion' => 'Resolver',  // Cambiado a 'Resolver' para coincidir con la consulta en resueltos.php
            'id_usuario' => $_SESSION['user']['IdUsuario']
        ]);

        // Notificar al creador (si no es el que resolvió)
        $stmt_creator = $pdo->prepare('SELECT "IdUsuarioCreador" FROM "Tickets" WHERE "IdTickets" = :id_ticket');
        $stmt_creator->execute(['id_ticket' => $id_ticket]);
        $creator = $stmt_creator->fetch(PDO::FETCH_ASSOC);

        $currentUserId = $_SESSION['user']['IdUsuario'];
        if ($creator && $creator['IdUsuarioCreador'] != $currentUserId) {
            // Simulamos notificación (el servidor WS real la maneja)
            error_log("NOTIF: Ticket #$id_ticket actualizado a $estado. Notificar a {$creator['IdUsuarioCreador']}");
        }

        echo json_encode(['success' => true, 'message' => 'Ticket actualizado correctamente']);

    } catch (PDOException $e) {
        error_log('Error al actualizar ticket: ' . $e->getMessage());
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