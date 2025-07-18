<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h2>Mis Tickets</h2>
    <div id="tickets-list"></div>
</div>

<script>
fetch('/GestionDeTickets/api/tickets.php')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('tickets-list');
        data.forEach(ticket => {
            if (ticket.IdUsuarioCreador === <?php echo $_SESSION['user']['IdUsuario']; ?>) {
                const card = `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">${ticket.Titulo}</h5>
                            <p class="card-text">${ticket.Descripcion}</p>
                            <p><strong>Prioridad:</strong> ${ticket.Prioridad}</p>
                            <p><strong>Estado:</strong> ${ticket.EstadoTicket}</p>
                        </div>
                    </div>
                `;
                container.innerHTML += card;
            }
        });
    });
</script>