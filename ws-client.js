// Detectar si estamos en localhost o en ngrok
let wsUrl;

if (window.location.hostname === "localhost") {
    // Desarrollo local
    wsUrl = "ws://localhost:8080";
} else {
    // Producción / ngrok
    wsUrl = "wss://eab369957d8c.ngrok-free.app";
}

// Crear conexión
const socket = new WebSocket(wsUrl);

// Evento: conexión abierta
socket.onopen = () => {
    console.log("[WS] Conectado al servidor:", wsUrl);

    // Registrar al usuario automáticamente (ejemplo user_id = 123)
    const registerMsg = {
        type: "register",
        user_id: 123
    };
    socket.send(JSON.stringify(registerMsg));
};

// Evento: mensaje recibido
socket.onmessage = (event) => {
    try {
        const data = JSON.parse(event.data);
        console.log("[WS] Mensaje recibido:", data);

        if (data.type === "notification") {
            // Aquí puedes mostrar notificaciones en tu frontend
            alert(`📩 Notificación: ${data.message}`);
        }
    } catch (e) {
        console.error("[WS] Error al parsear mensaje:", e);
    }
};

// Evento: error
socket.onerror = (error) => {
    console.error("[WS] Error de conexión:", error);
};

// Evento: conexión cerrada
socket.onclose = () => {
    console.warn("[WS] Conexión cerrada");
};

// 👉 Función para enviar una notificación a otro usuario
function sendNotification(targetUserId, message, ticketId = null) {
    const notif = {
        type: "notification",
        target_user_id: targetUserId,
        message: message,
        ticket_id: ticketId
    };
    socket.send(JSON.stringify(notif));
}

// 👉 Función para hacer broadcast
function sendBroadcast(payload) {
    const msg = {
        type: "broadcast",
        payload: payload
    };
    socket.send(JSON.stringify(msg));
}
