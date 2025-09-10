<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
        if (isset($_GET['id'])) {
            // --- MODIFICADO PARA USAR LA TABLA "ACCIONES" ---
            $id_ticket = intval($_GET['id']);
            try {
                // Obtener datos del ticket principal
                $stmt = $pdo->prepare('
                    SELECT 
                        t.*,
                        u.nombre as usuario_nombre,
                        u."Puesto" as usuario_puesto,
                        u."FotoPerfil" as usuario_foto,
                        d.nombre as nombre_departamento,
                        ua.nombre as asignado_nombre
                    FROM "Tickets" t
                    LEFT JOIN "usuario" u ON t."IdUsuarioCreador" = u."IdUsuario"
                    LEFT JOIN "departamentos" d ON t."IdDepartamentoDestino" = d."IdDepartamentos"
                    LEFT JOIN "usuario" ua ON t."IdUsuarioAsignado" = ua."IdUsuario"
                    WHERE t."IdTickets" = :id
                ');
                $stmt->execute(['id' => $id_ticket]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$ticket) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Ticket no encontrado']);
                    exit;
                }
                // Obtener acciones del ticket desde la nueva tabla "Acciones"
                $stmt_acciones = $pdo->prepare('
                    SELECT 
                        a.*,
                        u.nombre as nombre_usuario
                    FROM "Acciones" a
                    LEFT JOIN "usuario" u ON a."IdUsuario" = u."IdUsuario"
                    WHERE a."IdTicket" = :id_ticket
                    ORDER BY a."FechaAccion" DESC
                ');
                $stmt_acciones->execute(['id_ticket' => $id_ticket]);
                $acciones = $stmt_acciones->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'ticket' => $ticket,
                    'acciones' => $acciones // Devolvemos las acciones en lugar del historial antiguo
                ]);
                exit;
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al obtener el ticket: ' . $e->getMessage()]);
                exit;
            }
        }
        // Endpoint para obtener administradores (CORREGIDO: usa "IdTipoDeUsuario")
        if (isset($_GET['admins'])) {
            try {
                $stmt = $pdo->prepare('
                    SELECT "IdUsuario", "nombre", "Puesto"
                    FROM "usuario"
                    WHERE "IdTipoDeUsuario" = 1 -- Corregido el nombre de la columna
                ');
                $stmt->execute();
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['admins' => $admins]);
                exit;
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al obtener administradores: ' . $e->getMessage()]);
                exit;
            }
        }
        // Lógica original para listar tickets (MODIFICADA PARA EXCLUIR ESTADO)
        try {
            $params = [];
            $where = [];
            if (!empty($_GET['estado'])) {
                $where[] = '"Tickets"."EstadoTicket" = :estado';
                $params['estado'] = $_GET['estado'];
            }
            // --- NUEVA CONDICIÓN: Excluir estado si se pasa el parámetro ---
            if (!empty($_GET['excluir_estado'])) {
                 // Evita excluir si ya se está filtrando por estado específico
                 if (empty($_GET['estado'])) {
                    $where[] = '"Tickets"."EstadoTicket" != :excluir_estado';
                    $params['excluir_estado'] = $_GET['excluir_estado'];
                 }
            }
            if (!empty($_GET['departamento_id'])) {
                $where[] = '"Tickets"."IdDepartamentoDestino" = :departamento_id';
                $params['departamento_id'] = intval($_GET['departamento_id']);
            }
            if (!empty($_GET['prioridad'])) {
                $prioridad_raw = strtolower(trim($_GET['prioridad']));
                $mapPrioridad = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];
                if (isset($mapPrioridad[$prioridad_raw])) {
                    $where[] = '"Tickets"."Prioridad" = :prioridad';
                    $params['prioridad'] = $mapPrioridad[$prioridad_raw];
                }
            }
            if (isset($_GET['user_id']) && ($user_id = intval($_GET['user_id'])) > 0) {
                $where[] = '"Tickets"."IdUsuarioCreador" = :user_id';
                $params['user_id'] = $user_id;
            }
            // --- NUEVO: Filtro para tickets asignados a un usuario específico ---
if (!empty($_GET['user_id_asignado'])) {
    $where[] = '"Tickets"."IdUsuarioAsignado" = :user_id_asignado';
    $params['user_id_asignado'] = intval($_GET['user_id_asignado']);
}
// --- FIN NUEVO ---
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;
            $offset = ($page - 1) * $limit;
            $where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
            $count_sql = '
                SELECT COUNT(*)
                FROM "Tickets"
                LEFT JOIN "usuario" ON "Tickets"."IdUsuarioCreador" = "usuario"."IdUsuario"
                ' . $where_sql;
            $stmt_count = $pdo->prepare($count_sql);
            $stmt_count->execute($params);
            $total = $stmt_count->fetchColumn();
            $sql = '
                SELECT 
                    "Tickets".*, 
                    "usuario".nombre AS usuario_nombre, 
                    "usuario"."Puesto" AS usuario_puesto,
                    "usuario"."FotoPerfil" AS usuario_foto
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
            $stmt_counts = $pdo->query('
                SELECT 
                    COUNT(*) AS total_tickets,
                    SUM(CASE WHEN "EstadoTicket" = \'En espera\' THEN 1 ELSE 0 END) AS pending_tickets,
                    SUM(CASE WHEN "EstadoTicket" = \'Resuelto\' THEN 1 ELSE 0 END) AS resolved_tickets,
                    SUM(CASE WHEN "EstadoTicket" = \'En proceso\' THEN 1 ELSE 0 END) AS process_tickets,
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
        // ... (sin cambios, tu código original)
        break;
    case 'PUT':
    $data = json_decode(file_get_contents('php://input'), true);
    $id_ticket = $data['id_ticket'] ?? null;
    $action = $data['action'] ?? null;
    $estado = $data['estado'] ?? null;
    $user_id = $data['user_id'] ?? null;
    $historial_note = $data['historial_note'] ?? null;

    // --- Acción: Asignar ticket ---
    if ($action === 'assign') {
        // ... (tu código original aquí)
    }

    // --- Acción: Actualizar estado ---
    if ($id_ticket && $estado) {
        // Validar que el estado sea uno permitido
        if (!in_array($estado, ['Resuelto', 'En espera', 'En proceso'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Estado no válido']);
            exit;
        }

        try {
            // Verificar estado actual del ticket
            $stmt_check = $pdo->prepare('SELECT "EstadoTicket", "IdUsuarioCreador" FROM "Tickets" WHERE "IdTickets" = :id_ticket');
            $stmt_check->execute(['id_ticket' => $id_ticket]);
            $ticket = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket no encontrado']);
                exit;
            }

            $cambio_estado = $ticket['EstadoTicket'] !== $estado;
            $hay_nota = !empty($historial_note);

            // Si no hay cambios y no hay nota, no hacer nada
            if (!$cambio_estado && !$hay_nota) {
                echo json_encode(['success' => true, 'message' => 'No se realizaron cambios.']);
                exit;
            }

            // Actualizar el estado del ticket (si cambió)
            if ($cambio_estado) {
                $stmt_update = $pdo->prepare('
                    UPDATE "Tickets"
                    SET "EstadoTicket" = :estado, "FechaModificar" = NOW()
                    WHERE "IdTickets" = :id_ticket
                ');
                $stmt_update->execute([
                    'id_ticket' => $id_ticket,
                    'estado' => $estado
                ]);
            }

            // Registrar acción en "Acciones"
            $accion_texto = $cambio_estado ? 'Estado cambiado a ' . $estado : 'Nota agregada';
            $nota_texto = $historial_note ?: ($cambio_estado ? 'Estado actualizado a ' . $estado : 'Nota sin cambios adicionales');

            $stmt_accion = $pdo->prepare('
                INSERT INTO "Acciones" ("IdTicket", "IdUsuario", "Accion", "Nota")
                VALUES (:id_ticket, :id_usuario, :accion, :nota)
            ');
            $stmt_accion->execute([
                'id_ticket' => $id_ticket,
                'id_usuario' => $_SESSION['user']['IdUsuario'],
                'accion' => $accion_texto,
                'nota' => $nota_texto
            ]);

            echo json_encode(['success' => true, 'message' => 'Ticket actualizado correctamente']);
            exit;

        } catch (PDOException $e) {
            error_log('Error al actualizar ticket: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el ticket']);
            exit;
        }
    }

    // Si solo hay nota sin acción o estado, insertar como 'Nota agregada'
    if ($id_ticket && $historial_note && !$action && !$estado) {
        // ... (tu código original aquí)
    }

    // Si no hay nada, error
    http_response_code(400);
    echo json_encode(['error' => 'No se detectaron cambios válidos']);
    break;
}
?>  