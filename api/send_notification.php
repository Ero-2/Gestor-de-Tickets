<?php
function sendWebSocketNotification($target_user_id, $message, $ticket_id = null) {
    $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!$socket) {
        return false;
    }
    
    $result = @socket_connect($socket, '127.0.0.1', 8080);
    if (!$result) {
        socket_close($socket);
        return false;
    }
    
    $data = json_encode([
        'type' => 'notification',
        'target_user_id' => $target_user_id,
        'message' => $message,
        'ticket_id' => $ticket_id
    ]);
    
    @socket_write($socket, $data, strlen($data));
    @socket_close($socket);
    
    return true;
}
?>