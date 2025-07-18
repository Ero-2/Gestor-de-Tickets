<?php 
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<!-- Contenido principal -->
<div class="container mt-4">
    <h2 class="mb-4">Bienvenido, <?php echo htmlspecialchars($_SESSION['user']['nombre']); ?></h2>

    <div class="row">
        <div class="col-md-8">
            <h4>Mis Tickets</h4>
            <div id="tickets-list" class="mb-4">
                <!-- Aquí se cargarán los tickets dinámicamente -->
            </div>
        </div>

        <div class="col-md-4">
            <h4>Acciones rápidas</h4>
            <ul class="list-group">
                <li class="list-group-item">
                    <a href="crear-ticket.php" class="text-decoration-none">
                        <i class="bi bi-plus-circle"></i> Crear nuevo ticket
                    </a>
                </li>
                <li class="list-group-item">
                    <a href="perfil.php" class="text-decoration-none">
                        <i class="bi bi-person"></i> Mi perfil
                    </a>
                </li>
                <?php if ($_SESSION['user']['IdTipoDeUsuario'] == 1): ?>
                    <li class="list-group-item">
                        <a href="admin.php" class="text-decoration-none">
                            <i class="bi bi-gear"></i> Panel de administración
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Cargar tickets dinámicamente -->
<script>
fetch('/GestionDeTickets/api/tickets.php')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('tickets-list');
        const usuarioId = <?php echo $_SESSION['user']['IdUsuario']; ?>;
        const isAdmin = <?php echo ($_SESSION['user']['IdTipoDeUsuario'] == 1) ? 'true' : 'false'; ?>;

        if (data.length === 0) {
            container.innerHTML = '<p class="text-muted">No tienes tickets asignados.</p>';
            return;
        }

        data.forEach(ticket => {
            // Si es usuario normal, mostrar solo sus tickets
            if (!isAdmin && ticket.IdUsuarioCreador != usuarioId) return;

            const card = document.createElement('div');
            card.className = 'card mb-3';

            const estadoBadge = {
                'Abierto': 'bg-success',
                'En proceso': 'bg-primary',
                'Cerrado': 'bg-secondary'
            }[ticket.EstadoTicket] || 'bg-warning text-dark';

            card.innerHTML = `
                <div class="card-body">
                    <h5 class="card-title">${ticket.Titulo}</h5>
                    <p class="card-text">${ticket.Descripcion}</p>
                    <p class="card-text">
                        <strong>Prioridad:</strong> ${ticket.Prioridad} <br>
                        <strong>Estado:</strong> <span class="badge ${estadoBadge}">${ticket.EstadoTicket}</span>
                    </p>
                    ${isAdmin ? `
                    <div>
                        <button class="btn btn-sm btn-success" onclick="updateStatus(${ticket.IdTickets}, 'Cerrado')">Cerrar</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTicket(${ticket.IdTickets})">Eliminar</button>
                    </div>
                    ` : ''}
                </div>
            `;

            container.appendChild(card);
        });
    })
    .catch(err => {
        console.error('Error al cargar los tickets:', err);
        document.getElementById('tickets-list').innerHTML = `
            <div class="alert alert-danger">Hubo un error al cargar tus tickets.</div>
        `;
    });

// Funciones para admin
function updateStatus(idTicket, estado) {
    fetch('/GestionDeTickets/api/tickets.php', {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id_ticket: idTicket, estado: estado })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Estado actualizado');
            location.reload();
        } else {
            alert('Error al actualizar estado: ' + (data.error || 'Desconocido'));
        }
    });
}

function deleteTicket(idTicket) {
    if (!confirm('¿Estás seguro de eliminar este ticket?')) return;

    fetch('/GestionDeTickets/api/tickets.php?id=' + idTicket, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ticket eliminado');
            location.reload();
        } else {
            alert('Error al eliminar: ' + (data.error || 'Desconocido'));
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>