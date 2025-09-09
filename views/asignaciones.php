<?php

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Check if the user is an admin (IdTipoDeUsuario == 1)
$is_admin = $_SESSION['user']['IdTipoDeUsuario'] == 1;

// If not an admin, redirect or show an error
if (!$is_admin) {
    // Option 1: Redirect to the main tickets page
    header('Location: tickets.php'); // Or wherever non-admins should go
    exit;
    // Option 2: Show an error (uncomment the lines below and comment the redirect above)
    // http_response_code(403); // Forbidden
    // die('<h1>Acceso Denegado</h1><p>Esta página es solo para administradores.</p>');
}

$page_title = 'Mis Asignaciones';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Sistema de Soporte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .form-label {
            font-weight: 500;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
        }
        .table th {
            white-space: nowrap;
        }
        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
        }
        .priority-badge {
            min-width: 70px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4"><?php echo htmlspecialchars($page_title); ?></h2>

                <!-- Filtros -->
                <div class="row mb-4 g-3">
                    <div class="col-md-3">
                        <label for="filtroDepartamentoAsignados" class="form-label">Departamento:</label>
                        <select class="form-select" id="filtroDepartamentoAsignados">
                            <option value="">Todos los Departamentos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtroPrioridadAsignados" class="form-label">Prioridad:</label>
                        <select class="form-select" id="filtroPrioridadAsignados">
                            <option value="">Todas</option>
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtroEstadoAsignados" class="form-label">Estado:</label>
                        <select class="form-select" id="filtroEstadoAsignados">
                            <option value="">Todos</option>
                            <option value="En espera">En espera</option>
                            <option value="En proceso">En proceso</option>
                            <option value="Resuelto">Resuelto</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button class="btn btn-outline-primary" type="button" id="btnAplicarFiltrosAsignados">
                            <i class="bi bi-funnel"></i> Aplicar
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="btnLimpiarFiltrosAsignados">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </div>
                </div>

                <!-- Tabla de tickets asignados -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle" id="assignedTicketsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Usuario Creador</th>
                                        <th>Puesto</th>
                                        <th>Título</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="assignedTicketsBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                            <p class="mt-2 text-muted">Cargando tus asignaciones...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Paginación -->
                        <nav aria-label="Paginación de tickets asignados" class="mt-3">
                            <ul class="pagination justify-content-center mb-0" id="assignedPagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Detalle del Ticket (Reutilizado de tickets.php) -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detalle del Ticket #<span id="detailTicketId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <button type="button" class="btn btn-primary" onclick="guardarCambiosEnModal()">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Chat (Reutilizado de tickets.php) -->
    <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="chatModalLabel">Chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Estado de los filtros y paginación
        let filtrosActuales = {
            departamento_id: '',
            prioridad: '',
            estado: ''
        };
        let currentPage = 1;
        const itemsPerPage = 8;
        let currentChatTicketId = null;

        // Cargar opciones de departamento
        function cargarOpcionesDepartamento() {
            fetch('api/obtener.php')
                .then(r => {
                    if (!r.ok) throw new Error('Error de red');
                    return r.json();
                })
                .then(data => {
                    const select = document.getElementById('filtroDepartamentoAsignados');
                    if (data.success && Array.isArray(data.departamentos)) {
                        select.innerHTML = '<option value="">Todos los Departamentos</option>';
                        data.departamentos.forEach(dep => {
                            const opt = document.createElement('option');
                            opt.value = dep.IdDepartamentos;
                            opt.textContent = dep.nombre;
                            select.appendChild(opt);
                        });
                    }
                })
                .catch(err => {
                    console.error('Error al cargar departamentos:', err);
                    alert('Error al cargar los departamentos.');
                });
        }

        // Aplicar filtros (resetear a página 1)
        function aplicarFiltrosAsignados() {
            const departamento = document.getElementById('filtroDepartamentoAsignados').value.trim();
            const prioridad = document.getElementById('filtroPrioridadAsignados').value.trim();
            const estado = document.getElementById('filtroEstadoAsignados').value.trim();

            filtrosActuales.departamento_id = departamento || '';
            filtrosActuales.prioridad = prioridad || '';
            filtrosActuales.estado = estado || '';

            currentPage = 1;
            loadAssignedTickets();
        }

        // Limpiar filtros (resetear a página 1)
        function limpiarFiltrosAsignados() {
            document.getElementById('filtroDepartamentoAsignados').value = '';
            document.getElementById('filtroPrioridadAsignados').value = '';
            document.getElementById('filtroEstadoAsignados').value = '';
            filtrosActuales.departamento_id = '';
            filtrosActuales.prioridad = '';
            filtrosActuales.estado = '';
            currentPage = 1;
            loadAssignedTickets();
        }

        // Cargar tickets asignados con filtros y paginación
        function loadAssignedTickets(page = currentPage) {
            currentPage = page;
            const url = new URL('api/tickets.php', window.location.origin + window.location.pathname);

            // Agregar filtros a la URL
            Object.entries(filtrosActuales).forEach(([key, value]) => {
                if (value !== '') {
                    url.searchParams.append(key, value);
                }
            });
            // Filtro principal: Solo tickets asignados al usuario actual
            const userId = <?php echo json_encode($_SESSION['user']['IdUsuario']); ?>;
            url.searchParams.append('user_id_asignado', userId); // Asumimos que la API lo acepta

            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', itemsPerPage);

            fetch(url)
                .then(r => {
                    if (!r.ok) {
                        if(r.status === 404) {
                             return { tickets: [], total: 0 }; // Manejar 404 como "no hay tickets"
                        }
                        throw new Error('Error de red');
                    }
                    return r.json();
                })
                .then(data => {
                    const tbody = document.getElementById('assignedTicketsBody');
                    const pagination = document.getElementById('assignedPagination');

                    if (data.error) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="7" class="text-center text-danger">
                                    <i class="bi bi-exclamation-circle"></i> ${data.error}
                                </td>
                            </tr>`;
                        pagination.innerHTML = '';
                        return;
                    }

                    if (!data.tickets || data.tickets.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    No tienes tickets asignados con los filtros aplicados.
                                </td>
                            </tr>`;
                        pagination.innerHTML = '';
                        return;
                    }

                    tbody.innerHTML = data.tickets.map(ticket => {
                        const priorityClass = getPriorityClass(ticket.Prioridad);
                        const priorityBadge = `<span class="badge ${priorityClass} priority-badge">${ticket.Prioridad}</span>`;
                        const estadoClass = getEstadoClass(ticket.EstadoTicket);
                        const estadoBadge = `<span class="badge ${estadoClass}">${ticket.EstadoTicket}</span>`;

                        const foto = ticket.usuario_foto
                            ? ticket.usuario_foto
                            : 'assets/img/default-profile.png';

                        return `
                            <tr>
                                <td>
                                    <img src="${foto}" onerror="this.src='assets/img/default-profile.png';"
                                         style="width:30px;height:30px;border-radius:50%;margin-right:8px;">
                                    ${ticket.usuario_nombre || 'N/A'}
                                </td>
                                <td>${ticket.usuario_puesto || 'N/A'}</td>
                                <td>
                                    <strong>${ticket.Titulo}</strong>
                                    <br><small class="text-muted">${ticket.Descripcion || 'Sin descripción'}</small>
                                </td>
                                <td>${priorityBadge}</td>
                                <td>${estadoBadge}</td>
                                <td>${formatDate(ticket.FechaCreacion)}</td>
                                <td>
                                    <button class="btn btn-sm btn-info me-1" onclick="viewTicketDetails(${ticket.IdTickets})" title="Ver Detalles">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary me-1" onclick="openChat(${ticket.IdTickets})" title="Abrir Chat">
                                        <i class="bi bi-chat-dots"></i>
                                    </button>
                                    ${ticket.EstadoTicket !== 'Resuelto' ?
                                    `<button class="btn btn-sm btn-success" onclick="updateTicketStatus(${ticket.IdTickets}, 'Resuelto')" title="Marcar como Resuelto">
                                        <i class="bi bi-check-circle"></i>
                                    </button>` : ''}
                                </td>
                            </tr>
                        `;
                    }).join('');

                    // Generar paginación
                    const totalPages = Math.ceil(data.total / itemsPerPage);
                    let paginationHtml = '';
                    if (totalPages > 1) {
                        for (let i = 1; i <= totalPages; i++) {
                            paginationHtml += `
                                <li class="page-item ${i === currentPage ? 'active' : ''}">
                                    <a class="page-link" href="#" onclick="loadAssignedTickets(${i}); return false;">${i}</a>
                                </li>
                            `;
                        }
                    }
                    pagination.innerHTML = paginationHtml;
                })
                .catch(error => {
                    console.error('Error al cargar tickets asignados:', error);
                    document.getElementById('assignedTicketsBody').innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-danger">
                                <i class="bi bi-exclamation-triangle"></i> Error al cargar los datos. Por favor, recarga la página.
                            </td>
                        </tr>`;
                    document.getElementById('assignedPagination').innerHTML = '';
                });
        }


        // Clase por prioridad
        function getPriorityClass(priority) {
            switch ((priority || '').toLowerCase()) {
                case 'baja': return 'bg-success';
                case 'media': return 'bg-warning text-dark';
                case 'alta': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

        // Clase por estado
        function getEstadoClass(estado) {
            switch (estado) {
                case 'En espera': return 'bg-secondary';
                case 'En proceso': return 'bg-warning text-dark';
                case 'Resuelto': return 'bg-success';
                default: return 'bg-secondary';
            }
        }

        // Formatear fecha
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return isNaN(date)
                ? 'Fecha inválida'
                : date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
        }

        // --- Función para actualizar estado desde la tabla ---
        function updateTicketStatus(ticketId, status) {
            const validStatuses = ['Resuelto', 'En espera', 'En proceso'];
            if (!validStatuses.includes(status)) {
                alert('Estado no válido');
                return;
            }

            if (confirm(`¿Cambiar el estado del ticket #${ticketId} a "${status}"?`)) {
                fetch('api/tickets.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_ticket: ticketId, estado: status })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Ticket actualizado');
                        loadAssignedTickets(); // Recargar lista de tickets asignados
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

        // --- Función para ver detalles del ticket (Reutilizada de tickets.php) ---
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

        // --- Función para guardar cambios desde el modal (Reutilizada de tickets.php) ---
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
                    // Recargar la lista principal de tickets asignados y los detalles en el modal
                    loadAssignedTickets();
                    viewTicketDetails(ticketId); // O cerrar el modal si prefieres
                } else {
                    alert('Error al guardar cambios: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(err => {
                console.error('Error al guardar cambios en el modal:', err);
                alert('Error al guardar los cambios. Por favor, inténtalo de nuevo.');
            });
        }

        // --- Funciones para el Chat (Reutilizadas de tickets.php) ---
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

        // Inicialización
        document.addEventListener('DOMContentLoaded', function () {
            cargarOpcionesDepartamento();
            loadAssignedTickets(); // Carga inicial de tickets asignados

            // Eventos de filtros
            document.getElementById('btnAplicarFiltrosAsignados')?.addEventListener('click', aplicarFiltrosAsignados);
            document.getElementById('btnLimpiarFiltrosAsignados')?.addEventListener('click', limpiarFiltrosAsignados);
        });
    </script>
</body>
</html>