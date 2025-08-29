<?php
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Resueltos - Sistema de Soporte</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 p-4">
                <?php
                $page = isset($_GET['page']) ? $_GET['page'] : 'tickets';

                if ($page === 'resueltos') {
                ?>
                    <h2 class="mb-4">Tickets Resueltos</h2>

                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="filtroDepartamentoResueltos" class="form-label">Departamento:</label>
                            <select class="form-select" id="filtroDepartamentoResueltos">
                                <option value="">Todos los Departamentos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroPrioridadResueltos" class="form-label">Prioridad:</label>
                            <select class="form-select" id="filtroPrioridadResueltos">
                                <option value="">Todas las Prioridades</option>
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button class="btn btn-outline-primary" type="button" id="btnAplicarFiltrosResueltos">
                                <i class="bi bi-funnel"></i> Aplicar
                            </button>
                            <button class="btn btn-outline-secondary" type="button" id="btnLimpiarFiltrosResueltos">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de tickets resueltos -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="resolvedTicketsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Usuario Creador</th>
                                            <th>Puesto</th>
                                            <th>Título</th>
                                            <th>Prioridad</th>
                                            <th>Estado</th>
                                            <th>Fecha Creación</th>
                                            <th>Fecha Resolución</th>
                                        </tr>
                                    </thead>
                                    <tbody id="resolvedTicketsBody">
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                                <p class="mt-2 text-muted">Cargando tickets resueltos...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Paginación -->
                            <nav aria-label="Paginación de tickets resueltos" class="mt-3">
                                <ul class="pagination justify-content-center" id="resolvedPagination"></ul>
                            </nav>
                        </div>
                    </div>
                <?php
                } else {
                    echo '<div class="alert alert-info">Página no encontrada.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Estado de los filtros y paginación
        let filtrosActuales = {
            departamento_id: '',
            prioridad: ''
        };
        let currentPage = 1;
        const itemsPerPage = 8;

        // Cargar opciones de departamento
        function cargarOpcionesDepartamento() {
            fetch('api/obtener.php')
                .then(r => r.json())
                .then(data => {
                    const select = document.getElementById('filtroDepartamentoResueltos');
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
                });
        }

        // Aplicar filtros (resetear a página 1)
        function aplicarFiltrosResueltos() {
            const departamento = document.getElementById('filtroDepartamentoResueltos').value.trim();
            const prioridad = document.getElementById('filtroPrioridadResueltos').value.trim();

            filtrosActuales.departamento_id = departamento || '';
            filtrosActuales.prioridad = prioridad || '';

            currentPage = 1;
            loadResolvedTickets();
        }

        // Limpiar filtros (resetear a página 1)
        function limpiarFiltrosResueltos() {
            document.getElementById('filtroDepartamentoResueltos').value = '';
            document.getElementById('filtroPrioridadResueltos').value = '';
            filtrosActuales.departamento_id = '';
            filtrosActuales.prioridad = '';
            currentPage = 1;
            loadResolvedTickets();
        }

        // Cargar tickets resueltos con filtros y paginación
        function loadResolvedTickets(page = currentPage) {
            currentPage = page;
            const url = new URL('api/resueltos.php', window.location.origin + window.location.pathname);

            Object.entries(filtrosActuales).forEach(([key, value]) => {
                if (value !== '') {
                    url.searchParams.append(key, value);
                }
            });
            url.searchParams.append('page', currentPage);
            url.searchParams.append('limit', itemsPerPage);

            fetch(url)
                .then(r => {
                    if (!r.ok) throw new Error('Error de red');
                    return r.json();
                })
                .then(data => {
                    const tbody = document.getElementById('resolvedTicketsBody');
                    const pagination = document.getElementById('resolvedPagination');

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
                                    No hay tickets resueltos con los filtros aplicados.
                                </td>
                            </tr>`;
                        pagination.innerHTML = '';
                        return;
                    }

                    tbody.innerHTML = data.tickets.map(ticket => {
                        const priorityClass = getPriorityClass(ticket.Prioridad);
                        const priorityBadge = `<span class="badge ${priorityClass}">${ticket.Prioridad}</span>`;
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
                                <td><span class="badge bg-secondary">${ticket.EstadoTicket}</span></td>
                                <td>${formatDate(ticket.FechaCreacion)}</td>
                                <td>${formatDate(ticket.fecha_resolucion)}</td>
                            </tr>
                        `;
                    }).join('');

                    // Generar paginación
                    const totalPages = Math.ceil(data.total / itemsPerPage);
                    let paginationHtml = '';
                    for (let i = 1; i <= totalPages; i++) {
                        paginationHtml += `
                            <li class="page-item ${i === currentPage ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadResolvedTickets(${i}); return false;">${i}</a>
                            </li>
                        `;
                    }
                    pagination.innerHTML = paginationHtml;
                })
                .catch(error => {
                    console.error('Error al cargar tickets resueltos:', error);
                    document.getElementById('resolvedTicketsBody').innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-danger">
                                <i class="bi bi-exclamation-triangle"></i> Error al cargar los datos.
                            </td>
                        </tr>`;
                    document.getElementById('resolvedPagination').innerHTML = '';
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

        // Formatear fecha
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return isNaN(date) 
                ? 'Fecha inválida' 
                : date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', function () {
            const page = new URLSearchParams(window.location.search).get('page');

            if (page === 'resueltos') {
                cargarOpcionesDepartamento();
                loadResolvedTickets();

                // Eventos de filtros
                document.getElementById('btnAplicarFiltrosResueltos')?.addEventListener('click', aplicarFiltrosResueltos);
                document.getElementById('btnLimpiarFiltrosResueltos')?.addEventListener('click', limpiarFiltrosResueltos);
            }
        });
    </script>
</body>
</html>