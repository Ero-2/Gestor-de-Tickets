<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

class TicketNotification implements MessageComponentInterface {
    protected $clients;
    protected $users;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        echo "Servidor de notificaciones iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nueva conexión: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) {
            echo "Mensaje inválido recibido\n";
            return;
        }

        switch ($data['type']) {
            case 'register':
                $this->users[$data['user_id']] = $from;
                echo "Usuario {$data['user_id']} registrado (conn: {$from->resourceId})\n";
                break;

            case 'notification':
                if (isset($this->users[$data['target_user_id']])) {
                    $target = $this->users[$data['target_user_id']];
                    $target->send(json_encode([
                        'type' => 'notification',
                        'message' => $data['message'],
                        'ticket_id' => $data['ticket_id'] ?? null,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]));
                    echo "Notificación enviada a usuario {$data['target_user_id']}\n";
                } else {
                    echo "Usuario {$data['target_user_id']} no encontrado\n";
                }
                break;

            case 'broadcast':
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send(json_encode($data['payload']));
                    }
                }
                echo "Mensaje broadcast enviado\n";
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Eliminar usuario del registro
        foreach ($this->users as $user_id => $connection) {
            if ($connection === $conn) {
                unset($this->users[$user_id]);
                echo "Usuario {$user_id} desconectado\n";
                break;
            }
        }
        
        echo "Conexión {$conn->resourceId} cerrada\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Iniciar servidor en puerto 8080
try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new TicketNotification()
            )
        ),
        8080
    );

    echo "Servidor WebSocket iniciado en ws://localhost:8080\n";
    $server->run();
} catch (Exception $e) {
    echo "Error al iniciar servidor: " . $e->getMessage() . "\n";
}
?>