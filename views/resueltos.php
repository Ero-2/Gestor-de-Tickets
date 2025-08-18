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
    <title>Gestión de Tickets</title>
    <!-- Incluye Bootstrap y otros CSS/JS necesarios -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Menú lateral -->
           
            <!-- Contenido principal -->
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


            <div class="col-md-10 p-4">
                <?php
                $page = isset($_GET['page']) ? $_GET['page'] : 'tickets';
                if ($page === 'resueltos') {
                    ?>
                    <h2>Tickets Resueltos</h2>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="resolvedTicketsTable">
                                    <thead>
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
                                            <td colspan="7" class="text-center">
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
                    <?php
                }
                // Aquí puedes agregar otras secciones como 'tickets', 'crear-usuario', etc.
                ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar tickets resueltos solo si estamos en la página correspondiente
            if (window.location.search.includes('page=resueltos')) {
                loadResolvedTickets();
            }
        });

        function loadResolvedTickets() {
            fetch('api/resueltos.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('resolvedTicketsBody');
                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${data.error}</td></tr>`;
                        return;
                    }
                    if (data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" class="text-center">No hay tickets resueltos</td></tr>`;
                        return;
                    }
                    tbody.innerHTML = data.map(ticket => {
                        const priorityClass = getPriorityClass(ticket.Prioridad);
                        const priorityBadge = `<span class="badge ${priorityClass}">${ticket.Prioridad}</span>`;
                        
                        let fotoPerfilHtml = ticket.usuario_foto 
                            ? `<img src="${ticket.usuario_foto}" alt="Foto de ${ticket.usuario_nombre || 'Usuario'}" onerror="this.onerror=null; this.src='assets/img/default-profile.png';" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 8px;">`
                            : `<img src="assets/img/default-profile.png" alt="Foto por defecto" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 8px;">`;
                        
                        return `<tr>
                            <td>${fotoPerfilHtml} ${ticket.usuario_nombre || 'N/A'}</td>
                            <td>${ticket.usuario_puesto || 'N/A'}</td>
                            <td>
                                <strong>${ticket.Titulo}</strong>
                                <br><small class="text-muted">${ticket.Descripcion}</small>
                            </td>
                            <td>${priorityBadge}</td>
                            <td><span class="badge bg-secondary">${ticket.EstadoTicket}</span></td>
                            <td>${formatDate(ticket.FechaCreacion)}</td>
                            <td>${formatDate(ticket.fecha_resolucion)}</td>
                        </tr>`;
                    }).join('');
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('resolvedTicketsBody').innerHTML = 
                        `<tr><td colspan="7" class="text-center text-danger">Error al cargar los tickets resueltos</td></tr>`;
                });
        }

        function getPriorityClass(priority) {
            switch (priority.toLowerCase()) {
                case 'baja':
                    return 'bg-success';
                case 'media':
                    return 'bg-warning';
                case 'alta':
                    return 'bg-danger';
                default:
                    return 'bg-secondary';
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
        }

        
    </script>
</body>
</html>