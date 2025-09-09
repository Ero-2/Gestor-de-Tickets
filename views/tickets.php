<?php
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
// Corrección: Usar 'IdTipoUsuario' (como en tu base de datos) en lugar de 'IdTipoDeUsuario'
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
    <style>
        /* Estilo opcional para diferenciar el botón En Proceso */
        .btn-warning-custom {
             background-color: #ffc107; /* Amarillo Bootstrap */
             border-color: #ffc107;
             color: #212529; /* Texto oscuro para contraste */
        }
        .btn-warning-custom:hover {
             background-color: #e0a800;
             border-color: #d39e00;
        }
    </style>
</head>
<body>

<div class="container-fluid">
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
        <div class="col-md-3">
            <label for="filtroEstado" class="form-label">Estado:</label>
            <select class="form-select" id="filtroEstado">
                <option value="">Todos los Estados</option>
                <option value="En espera">En espera</option>
                <option value="En proceso">En proceso</option>
                <option value="Resuelto">Resuelto</option>
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

    <!-- MODIFICADO: Modal para Detalle del Ticket -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detalle del Ticket #<span id="detailTicketId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Información básica del ticket -->
                    <div id="ticketInfoContent">
                        Cargando información...
                    </div>
                    <hr>
                    <!-- Formulario para asignación, estado y notas -->
                    <form id="ticketActionForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="adminSelectModal" class="form-label">Asignar a:</label>
                                <select class="form-select" id="adminSelectModal">
                                    <option value="">Ninguno</option>
                                    <!-- Opciones cargadas dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="estadoSelectModal" class="form-label">Estado:</label>
                                <select class="form-select" id="estadoSelectModal">
                                    <option value="En espera">En espera</option>
                                    <option value="En proceso">En proceso</option>
                                    <option value="Resuelto">Resuelto</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notaInputModal" class="form-label">Nota (opcional)</label>
                            <textarea class="form-control" id="notaInputModal" rows="2" placeholder="Agregar una nota sobre la acción..."></textarea>
                        </div>
                    </form>
                    <hr>
                    <!-- Historial de Acciones -->
                    <div class="mb-3">
                        <strong>Historial de Acciones:</strong>
                        <ul class="list-group list-group-flush" id="historialListModal">
                            <li class="list-group-item text-muted">Cargando historial...</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <?php if ($is_admin): ?>
                        <!-- Cambiamos el botón a "Aceptar" -->
                        <button type="button" class="btn btn-primary" onclick="guardarCambiosEnModal()">Aceptar</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- FIN: Modal para Detalle del Ticket -->

</div> <!-- Cierre de container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let filtrosActuales = {
    departamento_id: '',
    prioridad: '',
    estado: ''
};
let currentPage = 1;
const itemsPerPage = 8;
let currentChatTicketId = null;
let ws = null;
let reconnectInterval = 3000;
let isConnected = false;

document.addEventListener('DOMContentLoaded', function() {
    cargarOpcionesDepartamento();
    loadTickets(); // Inicialmente cargará sin tickets resueltos
    connectWebSocket();
});

// Eventos para filtros
document.getElementById('btnAplicarFiltros').addEventListener('click', aplicarFiltros);
document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);

// --- MODIFICADA: Función para cargar tickets con lógica de exclusión ---
function loadTickets(page = currentPage) {
    currentPage = page;
    const url = new URL('api/tickets.php', window.location.origin + window.location.pathname);
    if (filtrosActuales.departamento_id) url.searchParams.append('departamento_id', filtrosActuales.departamento_id);
    if (filtrosActuales.prioridad) url.searchParams.append('prioridad', filtrosActuales.prioridad);
    if (filtrosActuales.estado) {
        // Si se selecciona un estado, se usa ese filtro
        url.searchParams.append('estado', filtrosActuales.estado);
    } else {
         // Si NO se selecciona un estado, se excluyen los Resueltos por defecto
         url.searchParams.append('excluir_estado', 'Resuelto');
    }
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
                const estadoClass = getEstadoClass(ticket.EstadoTicket);
                const estadoBadge = `<span class="badge ${estadoClass}">${ticket.EstadoTicket}</span>`;
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
                row += `<td>${estadoBadge}</td>`;
                row += `<td>${formatDate(ticket.FechaCreacion)}</td>`;
                row += `<td>
                    ${isAdmin && ticket.EstadoTicket !== 'Resuelto' ?
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
    const estadoSelect = document.getElementById('filtroEstado');
    filtrosActuales.departamento_id = departamentoSelect.value;
    filtrosActuales.prioridad = prioridadSelect.value;
    filtrosActuales.estado = estadoSelect.value;
    currentPage = 1;
    loadTickets();
}

function limpiarFiltros() {
    document.getElementById('filtroDepartamento').value = '';
    document.getElementById('filtroPrioridad').value = '';
    document.getElementById('filtroEstado').value = '';
    filtrosActuales.departamento_id = '';
    filtrosActuales.prioridad = '';
    filtrosActuales.estado = '';
    currentPage = 1;
    loadTickets(); // Volverá a cargar sin Resueltos por defecto
}

function getPriorityClass(priority) {
    switch (priority.toLowerCase()) {
        case 'baja': return 'bg-success';
        case 'media': return 'bg-warning text-dark';
        case 'alta': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function getEstadoClass(estado) {
    switch (estado) {
        case 'En espera': return 'bg-secondary';
        case 'En proceso': return 'bg-warning text-dark';
        case 'Resuelto': return 'bg-success';
        default: return 'bg-secondary';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
}

// --- MODIFICADO: Función para actualizar estado desde la tabla ---
function updateTicketStatus(ticketId, status) {
    const validStatuses = ['Resuelto', 'En espera', 'En proceso'];
    if (!validStatuses.includes(status)) {
        alert('Estado no válido');
        return;
    }

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
                loadTickets(); // Recargar lista de tickets (sin resueltos por defecto)
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

// --- MODIFICADO: Función para ver detalles del ticket ---
function viewTicketDetails(ticketId) {
    const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    document.getElementById('detailTicketId').textContent = ticketId;

    // Limpiar campos del formulario del modal
    document.getElementById('adminSelectModal').innerHTML = '<option value="">Ninguno</option>';
    document.getElementById('estadoSelectModal').value = 'En espera';
    document.getElementById('notaInputModal').value = '';
    document.getElementById('historialListModal').innerHTML = '<li class="list-group-item text-muted">Cargando historial...</li>';
    document.getElementById('ticketInfoContent').innerHTML = 'Cargando información...';

    detailModal.show();

    // Cargar datos del ticket
    fetch(`api/tickets.php?id=${ticketId}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                document.getElementById('ticketInfoContent').innerHTML = `<p class="text-danger">${data.error}</p>`;
                document.getElementById('historialListModal').innerHTML = '<li class="list-group-item text-danger">Error al cargar historial.</li>';
                return;
            }

            const ticket = data.ticket;
            // Asumimos que el endpoint devuelve 'historial' (tu archivo base) o 'acciones' (nuestra discusión)
            // Ajusta esta línea según el nombre real del campo en tu respuesta JSON
            const historial_acciones = data.historial || data.acciones || [];

            // Mostrar información básica del ticket
            const isAssigned = ticket.IdUsuarioAsignado !== null;
            document.getElementById('ticketInfoContent').innerHTML = `
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Título:</strong> ${ticket.Titulo}
                    </div>
                    <div class="col-md-6">
                        <strong>Prioridad:</strong>
                        <span class="badge ${getPriorityClass(ticket.Prioridad)}">${ticket.Prioridad}</span>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Estado:</strong> <span class="badge ${getEstadoClass(ticket.EstadoTicket)}">${ticket.EstadoTicket}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Departamento:</strong> ${ticket.nombre_departamento || 'N/A'}
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Creado por:</strong> ${ticket.usuario_nombre || 'N/A'} (${ticket.usuario_puesto})
                    </div>
                    <div class="col-md-6">
                        <strong>Asignado a:</strong>
                        ${isAssigned ? ticket.asignado_nombre : '<em>Ninguno</em>'}
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Fecha creación:</strong> ${formatDate(ticket.FechaCreacion)}
                    </div>
                    <div class="col-md-6">
                        <strong>Última modificación:</strong> ${formatDate(ticket.FechaModificar)}
                    </div>
                </div>
                <div class="mb-2">
                    <strong>Descripción:</strong><br>
                    <p class="mb-0">${ticket.Descripcion}</p>
                </div>
            `;

            // Cargar lista de administradores en el select del modal
            fetch('api/tickets.php?admins')
                .then(response => response.json())
                .then(adminsData => {
                    const select = document.getElementById('adminSelectModal');
                    select.innerHTML = '<option value="">Ninguno</option>';
                    if (adminsData.admins && Array.isArray(adminsData.admins)) {
                        adminsData.admins.forEach(admin => {
                            const opt = document.createElement('option');
                            opt.value = admin.IdUsuario;
                            opt.textContent = `${admin.nombre} (${admin.Puesto})`;
                            // Seleccionar automáticamente si este es el usuario asignado
                            if (isAssigned && admin.IdUsuario == ticket.IdUsuarioAsignado) {
                                opt.selected = true;
                            }
                            select.appendChild(opt);
                        });
                    }
                })
                .catch(err => {
                    console.error('Error al cargar administradores en el modal:', err);
                    document.getElementById('adminSelectModal').innerHTML = '<option value="">Error al cargar</option>';
                });

            // Establecer valores actuales del ticket en los selects del modal
            if (isAssigned) {
                // La selección se hace arriba al crear las opciones
            }
            document.getElementById('estadoSelectModal').value = ticket.EstadoTicket;

            // Mostrar historial de acciones
            const historialList = document.getElementById('historialListModal');
            if (historial_acciones && historial_acciones.length > 0) {
                historialList.innerHTML = historial_acciones.map(a => `
                    <li class="list-group-item small">
                        <strong>${a.Accion}</strong> por ${a.nombre_usuario || 'N/A'}
                        <small class="text-muted">el ${formatDate(a.FechaAccion)}</small>
                        ${a.Nota ? `<br><em>${a.Nota}</em>` : ''}
                    </li>
                `).join('');
            } else {
                historialList.innerHTML = '<li class="list-group-item text-muted">Sin acciones registradas</li>';
            }
        })
        .catch(err => {
            console.error('Error al cargar detalles del ticket:', err);
            document.getElementById('ticketInfoContent').innerHTML = '<p class="text-danger">Error al cargar los detalles del ticket.</p>';
            document.getElementById('historialListModal').innerHTML = '<li class="list-group-item text-danger">Error al cargar historial.</li>';
        });
}


// --- NUEVA: Función para guardar cambios desde el modal ---
function guardarCambiosEnModal() {
    const ticketId = document.getElementById('detailTicketId').textContent;
    const userId = document.getElementById('adminSelectModal').value || null; // Puede ser null si se desasigna
    const estado = document.getElementById('estadoSelectModal').value;
    const nota = document.getElementById('notaInputModal').value.trim();

    if (!ticketId) {
        alert("ID de ticket no encontrado.");
        return;
    }

    // Preparar datos para enviar
    const datosEnvio = {
        id_ticket: parseInt(ticketId)
    };

    // Solo enviar 'user_id' si se seleccionó uno
    if (userId !== null && userId !== "") {
        datosEnvio.action = 'assign';
        datosEnvio.user_id = parseInt(userId);
    }

    if (estado) {
        datosEnvio.estado = estado;
    }

    if (nota) {
        datosEnvio.historial_note = nota;
    }

    if (!datosEnvio.action && !datosEnvio.estado && !datosEnvio.historial_note) {
        alert("No se detectaron cambios para guardar.");
        return;
    }

    fetch('api/tickets.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datosEnvio)
    })
    .then(r => {
        if (!r.ok) throw new Error('Error en la respuesta del servidor');
        return r.json();
    })
    .then(data => {
        if (data.success) {
            alert('Cambios guardados correctamente.');
            // Recargar la lista principal de tickets (sin resueltos por defecto) y los detalles en el modal
            loadTickets();
            viewTicketDetails(ticketId);
        } else {
            alert('Error al guardar cambios: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(err => {
        console.error('Error al guardar cambios en el modal:', err);
        alert('Error al guardar los cambios. Por favor, inténtalo de nuevo.');
    });
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
                    loadTickets(); // Recargar tickets al recibir notificación (sin resueltos por defecto)
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
</body>
</html>