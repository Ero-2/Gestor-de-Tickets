<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

class TicketNotification implements MessageComponentInterface {
    protected $clients;
    protected $users; // user_id => connection

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        echo "[INFO] Servidor de notificaciones iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "[CONEXIÓN] Nueva conexión: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            echo "[ERROR] Mensaje inválido recibido\n";
            return;
        }

        switch ($data['type']) {
            case 'register':
                $userId = $data['user_id'] ?? null;
                if ($userId) {
                    $this->users[$userId] = $from;
                    echo "[REGISTRO] Usuario $userId registrado (conn: {$from->resourceId})\n";
                } else {
                    echo "[ERROR] Registro fallido: user_id faltante\n";
                }
                break;

            case 'notification':
                $targetId = $data['target_user_id'] ?? null;
                if ($targetId && isset($this->users[$targetId])) {
                    $target = $this->users[$targetId];
                    $notification = [
                        'type' => 'notification',
                        'message' => $data['message'],
                        'ticket_id' => $data['ticket_id'] ?? null,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    $target->send(json_encode($notification));
                    echo "[NOTIF] Enviada a usuario $targetId\n";
                } else {
                    echo "[NOTIF] Usuario $targetId no conectado\n";
                }
                break;

            case 'broadcast':
                $payload = $data['payload'] ?? [];
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($payload));
                    }
                }
                echo "[BROADCAST] Enviado a todos los clientes\n";
                break;

            default:
                echo "[ERROR] Tipo desconocido: {$data['type']}\n";
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Eliminar de users si existe
        foreach ($this->users as $user_id => $connection) {
            if ($connection === $conn) {
                unset($this->users[$user_id]);
                echo "[DESCONEXIÓN] Usuario $user_id desconectado\n";
                break;
            }
        }
        
        echo "[CONEXIÓN] {$conn->resourceId} cerrada\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[ERROR] {$e->getMessage()}\n";
        $conn->close();
    }
}

// Iniciar servidor
try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(new TicketNotification())
        ),
        8080
    );

    echo "[SERVIDOR] WebSocket iniciado en ws://localhost:8080\n";
    $server->run();
} catch (Exception $e) {
    echo "[FATAL] Error al iniciar servidor: " . $e->getMessage() . "\n";
}
?>