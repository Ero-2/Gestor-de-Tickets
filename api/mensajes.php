<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticación
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

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

// Función para obtener el otro usuario en el ticket
function getOtherUserInTicket($pdo, $ticketId, $currentUserId) {
    try {
        $stmt = $pdo->prepare('SELECT "IdUsuarioCreador" FROM "Tickets" WHERE "IdTickets" = :ticket_id');
        $stmt->execute(['ticket_id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ticket) {
            // Si el usuario actual es el creador, notificar al admin (o departamento asignado)
            if ($ticket['IdUsuarioCreador'] == $currentUserId) {
                return 1; // Admin por defecto
            } else {
                return $ticket['IdUsuarioCreador']; // Notificar al creador
            }
        }
        return 1; // Fallback: admin
    } catch (Exception $e) {
        error_log('Error en getOtherUserInTicket: ' . $e->getMessage());
        return 1;
    }
}

// Función para enviar notificación vía WebSocket
function sendWebSocketNotification($targetUserId, $message, $ticketId = null) {
    // Simulamos el envío. El servidor WebSocket real lo maneja.
    // Podrías usar un socket UDP, Redis, o simplemente loguearlo
    error_log("NOTIF: User $targetUserId | $message | Ticket $ticketId");
    
    // Opcional: puedes usar `fsockopen` para enviar al servidor WS si está en el mismo servidor
    // Pero en producción, el frontend se encarga de escuchar el WS
}

switch ($method) {
    case 'GET':
        // Obtener mensajes de un ticket
        $id_ticket = $_GET['id_ticket'] ?? null;
        if (!$id_ticket) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta id_ticket']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('
                SELECT 
                    m."IdMensaje",
                    m."IdTicket",
                    m."IdUsuario",
                    m."Mensaje",
                    m."FechaEnvio",
                    u."nombre" AS "nombre_usuario"
                FROM "Mensajes" m
                JOIN "usuario" u ON m."IdUsuario" = u."IdUsuario"
                WHERE m."IdTicket" = :id_ticket
                ORDER BY m."FechaEnvio" ASC
            ');
            $stmt->execute(['id_ticket' => $id_ticket]);
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($mensajes);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al cargar mensajes: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Enviar mensaje
        $data = json_decode(file_get_contents('php://input'), true);
        $id_ticket = $data['id_ticket'] ?? null;
        $mensaje = trim($data['mensaje'] ?? '');

        if (!$id_ticket || !$mensaje) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos: id_ticket o mensaje']);
            exit;
        }

        $id_usuario = $_SESSION['user']['IdUsuario'];

        try {
            $stmt = $pdo->prepare('
                INSERT INTO "Mensajes" ("IdTicket", "IdUsuario", "Mensaje", "FechaEnvio")
                VALUES (:id_ticket, :id_usuario, :mensaje, NOW())
            ');

            $stmt->execute([
                'id_ticket' => $id_ticket,
                'id_usuario' => $id_usuario,
                'mensaje' => $mensaje
            ]);

            // Obtener título del ticket para la notificación
            $ticketTitle = "ticket #$id_ticket";
            $stmt_title = $pdo->prepare('SELECT "Titulo" FROM "Tickets" WHERE "IdTickets" = :id_ticket');
            $stmt_title->execute(['id_ticket' => $id_ticket]);
            $ticket = $stmt_title->fetch(PDO::FETCH_ASSOC);
            if ($ticket) {
                $ticketTitle = $ticket['Titulo'];
            }

            // Enviar notificación al otro usuario
            $otherUserId = getOtherUserInTicket($pdo, $id_ticket, $id_usuario);
            if ($otherUserId && $otherUserId != $id_usuario) {
                sendWebSocketNotification(
                    $otherUserId,
                    "Nuevo mensaje de {$_SESSION['user']['Usuario']} en: $ticketTitle",
                    $id_ticket
                );
            }

            echo json_encode([
                'success' => true,
                'message' => 'Mensaje enviado',
                'id_mensaje' => $pdo->lastInsertId('"Mensajes_IdMensaje_seq"') // Ajusta si tu secuencia se llama diferente
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar mensaje: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>