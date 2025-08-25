<?php   
session_start();
require_once 'send_notification.php';
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

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

// Función para obtener el otro usuario en el chat
function getOtherUserInTicket($pdo, $ticketId, $currentUserId) {
    try {
        // Obtener el creador del ticket
        $stmt = $pdo->prepare('SELECT "IdUsuarioCreador" FROM "Tickets" WHERE "IdTickets" = :ticket_id');
        $stmt->execute(['ticket_id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            // Si el actual es el creador, devolver null (o puedes devolver un admin)
            if ($ticket['IdUsuarioCreador'] == $currentUserId) {
                // Aquí podrías devolver el ID del administrador asignado
                return 1; // Ejemplo: admin con ID 1
            } else {
                return $ticket['IdUsuarioCreador'];
            }
        }
        return 1; // Por defecto, admin
    } catch (Exception $e) {
        return 1;
    }
}

switch ($method) {
    case 'GET':
        // Obtener mensajes de un ticket específico
        $id_ticket = $_GET['id_ticket'] ?? null;
        if (!$id_ticket) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el ID del ticket']);
            exit;
        }
        try {
            $stmt = $pdo->prepare('
                SELECT m.*, u.nombre AS nombre_usuario
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
            echo json_encode(['error' => 'Error al obtener los mensajes: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Crear un nuevo mensaje
        $data = json_decode(file_get_contents('php://input'), true);
        $id_ticket = $data['id_ticket'] ?? null;
        $mensaje = $data['mensaje'] ?? null;

        if (!$id_ticket || !$mensaje) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos (id_ticket, mensaje)']);
            exit;
        }

        $id_usuario = $_SESSION['user']['IdUsuario'];

        try {
            $stmt = $pdo->prepare('
                INSERT INTO "Mensajes" ("IdTicket", "IdUsuario", "Mensaje")
                VALUES (:id_ticket, :id_usuario, :mensaje)
            ');
            $stmt->execute([
                'id_ticket' => $id_ticket,
                'id_usuario' => $id_usuario,
                'mensaje' => $mensaje
            ]);
            
            //  notification: Enviar notificación al otro usuario
            $otherUserId = getOtherUserInTicket($pdo, $id_ticket, $id_usuario);
            if ($otherUserId && $otherUserId != $id_usuario) {
                $ticketTitle = "";
                // Obtener título del ticket
                $ticketStmt = $pdo->prepare('SELECT "Titulo" FROM "Tickets" WHERE "IdTickets" = :ticket_id');
                $ticketStmt->execute(['ticket_id' => $id_ticket]);
                $ticket = $ticketStmt->fetch(PDO::FETCH_ASSOC);
                $ticketTitle = $ticket ? $ticket['Titulo'] : "ticket #$id_ticket";
                
                sendWebSocketNotification(
                    $otherUserId, 
                    "Nuevo mensaje de {$_SESSION['user']['Usuario']} en: $ticketTitle", 
                    $id_ticket
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Mensaje enviado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al enviar el mensaje: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>