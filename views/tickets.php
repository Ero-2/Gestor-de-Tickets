
<?php
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$is_admin = $_SESSION['user']['IdTipoDeUsuario'] == 1;
$page_title = $is_admin ? 'Todos los Tickets' : 'Mis Tickets';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>

<!-- Controles de Filtro -->
<div class="row mb-3">
    <div class="col-md-4">
        <label for="filtroDepartamento" class="form-label">Departamento:</label>
        <select class="form-select" id="filtroDepartamento">
            <option value="">Todos los Departamentos</option>
        </select>
    </div>
    <div class="col-md-3">
        <label for="filtroPrioridad" class="form-label">Prioridad:</label>
        <select class="form-select" id="filtroPrioridad">
            <option value="">Todas las Prioridades</option>
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
        </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-outline-secondary me-2" type="button" id="btnAplicarFiltros">
            <i class="bi bi-funnel"></i> Aplicar
        </button>
        <button class="btn btn-outline-secondary" type="button" id="btnLimpiarFiltros">
            <i class="bi bi-x-circle"></i> Limpiar
        </button>
    </div>
</div>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $page_title; ?></h2>
        <?php if (!$is_admin): ?>
            <a href="inicio.php?page=crear-ticket" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuevo Ticket
            </a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="ticketsTable">
                    <thead>
                        <tr>
                            <?php if ($is_admin): ?>
                                <th>Usuario</th>
                                <th>Puesto</th>
                            <?php endif; ?>
                            <th>Título</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="ticketsBody">
                        <tr>
                            <td colspan="<?php echo $is_admin ? 7 : 5; ?>" class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nav aria-label="Paginación" class="mt-3">
                <ul class="pagination justify-content-center" id="ticketsPagination"></ul>
            </nav>
        </div>
    </div>

    <!-- Modal para Chat -->
    <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="chatModalLabel">Chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="chatMessages" style="height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px;">
                        Cargando mensajes...
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="messageInput" placeholder="Escribe tu mensaje...">
                        <button class="btn btn-primary" type="button" onclick="sendMessage()">Enviar</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Detalle del Ticket -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detalle del Ticket #<span id="detailTicketId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="ticketDetailContent">
                        Cargando...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <?php if ($is_admin): ?>
                        <button type="button" class="btn btn-primary" onclick="assignTicket()">Asignar</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
let filtrosActuales = {
    departamento_id: '',
    prioridad: ''
};
let currentPage = 1;
const itemsPerPage = 8;

document.addEventListener('DOMContentLoaded', function() {
    cargarOpcionesDepartamento();
    loadTickets();

    let ws = null;
    let reconnectInterval = 3000;
    let isConnected = false;

    // Eventos para filtros
    const btnAplicar = document.getElementById('btnAplicarFiltros');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    
    if (btnAplicar) btnAplicar.addEventListener('click', aplicarFiltros);
    if (btnLimpiar) btnLimpiar.addEventListener('click', limpiarFiltros);

    // Conectar WebSocket
    connectWebSocket();
});

function loadTickets(page = currentPage) {
    currentPage = page;
    const url = new URL('api/tickets.php', window.location.origin + window.location.pathname);
    
    if (filtrosActuales.departamento_id) url.searchParams.append('departamento_id', filtrosActuales.departamento_id);
    if (filtrosActuales.prioridad) url.searchParams.append('prioridad', filtrosActuales.prioridad);
    url.searchParams.append('page', currentPage);
    url.searchParams.append('limit', itemsPerPage);

    const isAdmin = <?php echo json_encode($is_admin); ?>;
    if (!isAdmin) {
        const userId = <?php echo json_encode($_SESSION['user']['IdUsuario']); ?>;
        url.searchParams.append('user_id', userId);
    }

    fetch(url)
        .then(r => r.json())
        .then(responseData => {
            const tbody = document.getElementById('ticketsBody');
            const pagination = document.getElementById('ticketsPagination');
            const isAdmin = <?php echo json_encode($is_admin); ?>;

            if (responseData.error) {
                tbody.innerHTML = `<tr><td colspan="${isAdmin ? 7 : 5}" class="text-center text-danger">${responseData.error}</td></tr>`;
                pagination.innerHTML = '';
                return;
            }

            const data = responseData.tickets || [];

            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${isAdmin ? 7 : 5}" class="text-center">No hay tickets disponibles</td></tr>`;
                pagination.innerHTML = '';
                return;
            }

            tbody.innerHTML = data.map(ticket => {
                const priorityClass = getPriorityClass(ticket.Prioridad);
                const priorityBadge = `<span class="badge ${priorityClass}">${ticket.Prioridad}</span>`;
                let row = '<tr>';
                
                if (isAdmin) {
                    const fotoPerfilHtml = ticket.usuario_foto 
                        ? `<img src="${ticket.usuario_foto}" alt="Foto" onerror="this.src='assets/img/default-profile.png'" style="width:30px;height:30px;border-radius:50%;margin-right:8px;">`
                        : `<img src="assets/img/default-profile.png" alt="Foto" style="width:30px;height:30px;border-radius:50%;margin-right:8px;">`;
                    row += `<td>${fotoPerfilHtml} ${ticket.usuario_nombre || 'N/A'}</td>`;
                    row += `<td>${ticket.usuario_puesto || 'N/A'}</td>`;
                }

                row += `<td><strong>${ticket.Titulo}</strong><br><small class="text-muted">${ticket.Descripcion}</small></td>`;
                row += `<td>${priorityBadge}</td>`;
                row += `<td><span class="badge bg-secondary">${ticket.EstadoTicket}</span></td>`;
                row += `<td>${formatDate(ticket.FechaCreacion)}</td>`;
                row += `<td>
                    ${isAdmin ? 
                         `<button class="btn btn-sm btn-success me-1" onclick="updateTicketStatus(${ticket.IdTickets}, 'Resuelto')">
            <i class="bi bi-check-circle"></i> Resolver
        </button>` : ''}
    
    <button class="btn btn-sm btn-info me-1" onclick="viewTicketDetails(${ticket.IdTickets})">
        <i class="bi bi-eye"></i> 
    </button>
    
    <button class="btn btn-sm btn-primary" onclick="openChat(${ticket.IdTickets})" title="Abrir Chat">
        <i class="bi bi-chat-dots"></i>
    </button>
                </td>`;
                row += '</tr>';
                return row;
            }).join('');

            // Paginación
            const totalPages = Math.ceil(responseData.total / itemsPerPage);
            let paginationHtml = '';
            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadTickets(${i}); return false;">${i}</a>
                    </li>
                `;
            }
            pagination.innerHTML = paginationHtml;
        })
        .catch(err => {
            console.error('Error al cargar tickets:', err);
            document.getElementById('ticketsBody').innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error al cargar los tickets</td></tr>`;
            document.getElementById('ticketsPagination').innerHTML = '';
        });
}

function cargarOpcionesDepartamento() {
    fetch('api/obtener.php')
        .then(r => r.json())
        .then(data => {
            if (data.success && Array.isArray(data.departamentos)) {
                const select = document.getElementById('filtroDepartamento');
                select.innerHTML = '<option value="">Todos los Departamentos</option>';
                data.departamentos.forEach(dep => {
                    const opt = document.createElement('option');
                    opt.value = dep.IdDepartamentos;
                    opt.textContent = dep.nombre;
                    select.appendChild(opt);
                });
            }
        })
        .catch(err => console.error('Error al cargar departamentos:', err));
}

function aplicarFiltros() {
    const departamentoSelect = document.getElementById('filtroDepartamento');
    const prioridadSelect = document.getElementById('filtroPrioridad');
    filtrosActuales.departamento_id = departamentoSelect.value;
    filtrosActuales.prioridad = prioridadSelect.value;
    currentPage = 1;
    loadTickets();
}

function limpiarFiltros() {
    document.getElementById('filtroDepartamento').value = '';
    document.getElementById('filtroPrioridad').value = '';
    filtrosActuales.departamento_id = '';
    filtrosActuales.prioridad = '';
    currentPage = 1;
    loadTickets();
}

function getPriorityClass(priority) {
    switch (priority.toLowerCase()) {
        case 'baja': return 'bg-success';
        case 'media': return 'bg-warning text-dark';
        case 'alta': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
}

function updateTicketStatus(ticketId, status) {
    if (confirm(`¿Cambiar el estado a "${status}"?`)) {
        fetch('api/tickets.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_ticket: ticketId, estado: status })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Ticket actualizado');
                loadTickets();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al actualizar el ticket');
        });
    }
}

function viewTicketDetails(ticketId) {
    const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    document.getElementById('detailTicketId').textContent = ticketId;
    document.getElementById('ticketDetailContent').innerHTML = 'Cargando...'; // Reset to loading
    detailModal.show(); // Mostrar el modal inmediatamente

    fetch(`api/tickets.php?id=${ticketId}`)
        .then(r => r.json())
        .then(data => {
            const contentDiv = document.getElementById('ticketDetailContent');
            if (data.error) {
                contentDiv.innerHTML = `<p class="text-danger">${data.error}</p>`;
                return;
            }

            const ticket = data.ticket;
            const isAssigned = ticket.IdUsuarioAsignado !== null;

            contentDiv.innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Título:</strong> ${ticket.Titulo}
                    </div>
                    <div class="col-md-6">
                        <strong>Prioridad:</strong> 
                        <span class="badge ${getPriorityClass(ticket.Prioridad)}">${ticket.Prioridad}</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Estado:</strong> <span class="badge bg-secondary">${ticket.EstadoTicket}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Departamento:</strong> ${ticket.nombre_departamento || 'N/A'}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Creado por:</strong> ${ticket.usuario_nombre || 'N/A'} (${ticket.usuario_puesto})
                    </div>
                    <div class="col-md-6">
                        <strong>Asignado a:</strong> 
                        ${isAssigned ? ticket.asignado_nombre : '<em>Ninguno</em>'}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Fecha creación:</strong> ${formatDate(ticket.FechaCreacion)}
                    </div>
                    <div class="col-md-6">
                        <strong>Última modificación:</strong> ${formatDate(ticket.FechaModificar)}
                    </div>
                </div>
                <div class="mb-3">
                    <strong>Descripción:</strong><br>
                    <p>${ticket.Descripcion}</p>
                </div>
                <div class="mb-3">
                    <strong>Historial:</strong>
                    <ul class="list-group">
                        ${ticket.historial ? ticket.historial.map(h => `
                            <li class="list-group-item small">
                                <small>${h.Accion} por ${h.nombre_usuario} el ${formatDate(h.FechaAccion)}</small>
                            </li>
                        `).join('') : '<li class="list-group-item text-muted">Sin historial</li>'}
                    </ul>
                </div>
            `;
        })
        .catch(err => {
            console.error('Error al cargar detalles:', err);
            document.getElementById('ticketDetailContent').innerHTML = '<p class="text-danger">Error al cargar los detalles.</p>';
        });
}

function assignTicket() {
    const ticketId = document.getElementById('detailTicketId').textContent;
    const userId = prompt("ID del usuario a quien asignar el ticket:");
    if (!userId || isNaN(userId)) {
        alert("Por favor ingresa un ID válido.");
        return;
    }

    fetch('api/tickets.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_ticket: ticketId,
            action: 'assign',
            user_id: parseInt(userId)
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Ticket asignado correctamente.');
            viewTicketDetails(ticketId);
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => console.error(err));
}

function openChat(ticketId) {
    currentChatTicketId = ticketId;
    const chatModal = new bootstrap.Modal(document.getElementById('chatModal'));
    document.getElementById('chatModalLabel').textContent = `Chat para Ticket #${ticketId}`;
    loadMessages(ticketId);
    chatModal.show();
}

function loadMessages(ticketId) {
    fetch(`api/mensajes.php?id_ticket=${ticketId}`)
        .then(r => r.json())
        .then(data => {
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.innerHTML = '';
            if (data.error) {
                messagesContainer.innerHTML = `<p class="text-danger">${data.error}</p>`;
                return;
            }
            if (data.length === 0) {
                messagesContainer.innerHTML = '<p class="text-muted">No hay mensajes aún.</p>';
                return;
            }
            data.forEach(msg => {
                const messageElement = document.createElement('div');
                messageElement.classList.add('mb-2', 'p-2', 'border', 'rounded');
                const isCurrentUser = msg.IdUsuario == <?php echo json_encode($_SESSION['user']['IdUsuario']); ?>;
                messageElement.classList.add(isCurrentUser ? 'bg-light' : 'bg-info-subtle');
                messageElement.innerHTML = `
                    <strong>${msg.nombre_usuario}</strong>
                    <small class="text-muted">(${new Date(msg.FechaEnvio).toLocaleString('es-ES')})</small>
                    <p class="mb-0">${msg.Mensaje}</p>
                `;
                messagesContainer.appendChild(messageElement);
            });
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        })
        .catch(err => {
            console.error('Error al cargar mensajes:', err);
            document.getElementById('chatMessages').innerHTML = '<p class="text-danger">Error al cargar mensajes.</p>';
        });
}

function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    if (!message || !currentChatTicketId) {
        alert('Escribe un mensaje.');
        return;
    }
    fetch('api/mensajes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_ticket: currentChatTicketId, mensaje: message })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadMessages(currentChatTicketId);
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Error al enviar mensaje:', err);
        alert('Error al enviar el mensaje.');
    });
}

function connectWebSocket() {
    try {
        ws = new WebSocket('ws://localhost:8080');
        
        ws.onopen = function(event) {
            console.log('✅ Conectado al WebSocket');
            isConnected = true;
            
            const userId = <?php echo json_encode($_SESSION['user']['IdUsuario']); ?>;
            ws.send(JSON.stringify({
                type: 'register',
                user_id: userId
            }));
        };

        ws.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                if (data.type === 'notification') {
                    showNotification(data.message, data.ticket_id);
                    loadTickets();
                }
            } catch (e) {
                console.error('Error al procesar mensaje:', e);
            }
        };

        ws.onclose = function(event) {
            if (isConnected) {
                setTimeout(connectWebSocket, reconnectInterval);
            }
            isConnected = false;
        };

        ws.onerror = function(error) {
            console.error('❌ Error en WebSocket:', error);
            showNotification('Error en la conexión de notificaciones', null, 'danger');
        };

    } catch (e) {
        console.error('❌ Error al conectar WebSocket:', e);
        setTimeout(connectWebSocket, reconnectInterval);
    }
}

function showNotification(message, ticketId = null, type = 'info') {
    let container = document.getElementById('notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container);
    }

    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.style.cssText = 'min-width: 300px; margin-bottom: 10px; animation: slideInRight 0.3s;';
    notification.innerHTML = `
        <strong>${type === 'success' ? '✅' : type === 'warning' ? '⚠️' : type === 'danger' ? '❌' : '🔔'} Notificación</strong><br>
        ${message}
        ${ticketId ? `<br><small>Ticket #${ticketId}</small>` : ''}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>
