<?php
// Verificar autenticación
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$is_admin = $_SESSION['user']['IdTipoDeUsuario'] == 1;
$page_title = $is_admin ? 'Todos los Tickets' : 'Mis Tickets';
?>

<!-- Controles de Filtro -->
<div class="row mb-3">
    <div class="col-md-4">
        <label for="filtroDepartamento" class="form-label">Departamento:</label>
        <select class="form-select" id="filtroDepartamento">
            <option value="">Todos los Departamentos</option>
            <!-- Las opciones se cargarán dinámicamente -->
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
                        <!-- Los datos se cargarán aquí mediante AJAX -->
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
        </div>
    </div>
    <!-- Modal para el Chat -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg"> <!-- modal-lg para hacerlo más ancho -->
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="chatModalLabel">Chat</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Contenedor de mensajes -->
        <div id="chatMessages" style="height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px;">
            <!-- Los mensajes se cargarán aquí -->
            <p class="text-muted">Cargando mensajes...</p>
        </div>
        <!-- Formulario para enviar mensaje -->
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
</div>

<script>
let filtrosActuales = {
    departamento_id: '',
    prioridad: ''
};

document.addEventListener('DOMContentLoaded', function() {
    cargarOpcionesDepartamento();
    loadTickets();

    // Eventos para filtros
    const btnAplicar = document.getElementById('btnAplicarFiltros');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    
    if (btnAplicar) btnAplicar.addEventListener('click', aplicarFiltros);
    if (btnLimpiar) btnLimpiar.addEventListener('click', limpiarFiltros);
});

// --- Cargar tickets desde API ---
function loadTickets(filtros = {}) {
    const filtrosFinales = { ...filtrosActuales, ...filtros };
    const url = new URL('api/tickets.php', window.location.origin + window.location.pathname);
    
    if (filtrosFinales.departamento_id) url.searchParams.append('departamento_id', filtrosFinales.departamento_id);
    if (filtrosFinales.prioridad) url.searchParams.append('prioridad', filtrosFinales.prioridad);

    fetch(url)
        .then(r => r.json())
        .then(responseData => {
            const tbody = document.getElementById('ticketsBody');
            const isAdmin = <?php echo json_encode($is_admin); ?>;

            if (responseData.error) {
                tbody.innerHTML = `<tr><td colspan="${isAdmin ? 7 : 5}" class="text-center text-danger">${responseData.error}</td></tr>`;
                return;
            }

            const data = responseData.tickets || [];

            // Separar en espera y resueltos
            const ticketsEnEspera = data.filter(t => t.EstadoTicket !== 'Resuelto');
            const ticketsResueltos = data.filter(t => t.EstadoTicket === 'Resuelto');

            // Guardar resueltos para otra página
            localStorage.setItem('tickets_resueltos', JSON.stringify(ticketsResueltos));

            if (ticketsEnEspera.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${isAdmin ? 7 : 5}" class="text-center">No hay tickets disponibles</td></tr>`;
                if (responseData.counts && isAdmin) updateAdminCounts(responseData.counts);
                return;
            }

            // Si no es admin, mostrar solo tickets creados por el usuario
            let filteredTickets = ticketsEnEspera;
            if (!isAdmin) {
                const userId = <?php echo json_encode($_SESSION['user']['IdUsuario']); ?>;
                filteredTickets = filteredTickets.filter(ticket => ticket.IdUsuarioCreador === userId);
            }

            tbody.innerHTML = filteredTickets.map(ticket => {
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
                        `<button class="btn btn-sm btn-success me-1" onclick="updateTicketStatus(${ticket.IdTickets}, 'Resuelto')"><i class="bi bi-check-circle"></i> Resolver</button>` :
                        `<button class="btn btn-sm btn-info me-1" onclick="viewTicket(${ticket.IdTickets})"><i class="bi bi-eye"></i> Ver</button>`}
                    <button class="btn btn-sm btn-primary" onclick="openChat(${ticket.IdTickets})" title="Abrir Chat"><i class="bi bi-chat-dots"></i></button>
                </td>`;
                row += '</tr>';
                return row;
            }).join('');

            if (responseData.counts && isAdmin) updateAdminCounts(responseData.counts);
        })
        .catch(err => {
            console.error('Error al cargar tickets:', err);
            document.getElementById('ticketsBody').innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error al cargar los tickets</td></tr>`;
        });
}

// --- Filtros ---
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
    loadTickets(filtrosActuales);
}

function limpiarFiltros() {
    document.getElementById('filtroDepartamento').value = '';
    document.getElementById('filtroPrioridad').value = '';
    filtrosActuales.departamento_id = '';
    filtrosActuales.prioridad = '';
    loadTickets();
}

// --- Utilidades ---
function updateAdminCounts(counts) {
    const totalEl = document.getElementById('totalTickets');
    const pendingEl = document.getElementById('pendingTickets');
    const resolvedEl = document.getElementById('resolvedTickets');
    const urgentEl = document.getElementById('urgentTickets');
    if (totalEl) totalEl.textContent = counts.total_tickets || 0;
    if (pendingEl) pendingEl.textContent = counts.pending_tickets || 0;
    if (resolvedEl) resolvedEl.textContent = counts.resolved_tickets || 0;
    if (urgentEl) urgentEl.textContent = counts.urgent_tickets || 0;
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

// --- Acciones ---
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

function viewTicket(ticketId) {
    alert('Ver detalles del ticket ID: ' + ticketId);
}

// --- Chat ---
let currentChatTicketId = null;
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
</script>
