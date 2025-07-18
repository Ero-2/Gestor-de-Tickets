<?php 
session_start();
if ($_SESSION['user']['IdTipoDeUsuario'] !== 1) {
    header('Location: login.php');
    exit;
}
?>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h2>Todos los Tickets</h2>
    <div id="admin-tickets"></div>
</div>

<script>
fetch('/GestionDeTickets/api/tickets.php')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('admin-tickets');
        data.forEach(ticket => {
            const card = `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">${ticket.Titulo}</h5>
                        <p class="card-text">${ticket.Descripcion}</p>
                        <p><strong>Prioridad:</strong> ${ticket.Prioridad}</p>
                        <p><strong>Estado:</strong> ${ticket.EstadoTicket}</p>
                        <p><strong>Creador:</strong> ${ticket.Usuario}</p>
                        <button class="btn btn-sm btn-warning" onclick="updateStatus(${ticket.IdTickets}, 'Cerrado')">Cerrar</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTicket(${ticket.IdTickets})">Eliminar</button>
                    </div>
                </div>
            `;
            container.innerHTML += card;
        });
    });

function updateStatus(idTicket, status) {
    fetch('/GestionDeTickets/api/tickets.php', {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id_ticket: idTicket, estado: status})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Estado actualizado');
            location.reload();
        }
    });
}

function deleteTicket(idTicket) {
    if (confirm('¿Estás seguro de eliminar este ticket?')) {
        fetch(`/GestionDeTickets/api/tickets.php?id=${idTicket}`, {method: 'DELETE'})
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ticket eliminado');
                    location.reload();
                }
            });
    }
}
</script>