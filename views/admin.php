<?php
// Verificar que solo los administradores puedan acceder
if (!isset($_SESSION['user']) || $_SESSION['user']['IdTipoDeUsuario'] != 1) {
    header('Location: inicio.php');
    exit;
}
?>

<div class="container-fluid">
    <h2>Panel de Administración</h2>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Resumen de Tickets</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body text-center">
                                    <h6>Total Tickets</h6>
                                    <h3 id="totalTickets">-</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body text-center">
                                    <h6>Pendientes</h6>
                                    <h3 id="pendingTickets">-</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success">
                                <div class="card-body text-center">
                                    <h6>Resueltos</h6>
                                    <h3 id="resolvedTickets">-</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-danger">
                                <div class="card-body text-center">
                                    <h6>Urgentes</h6>
                                    <h3 id="urgentTickets">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Gestión de Tickets</h5>
        </div>
        <div class="card-body">
            <div id="adminTicketsContent">
                <!-- Aquí se cargará el contenido de tickets para administradores -->
                <?php include 'tickets.php'; ?>
            </div>
        </div>
    </div>
</div>
<script>
function loadTicketCounts() {
    fetch('api/tickets.php')
        .then(response => response.json())
        .then(data => {
            if (!data.error) {
                document.getElementById('totalTickets').textContent = data.total_tickets;
                document.getElementById('pendingTickets').textContent = data.pending_tickets;
                document.getElementById('resolvedTickets').textContent = data.resolved_tickets;
                document.getElementById('urgentTickets').textContent = data.urgent_tickets;
            } else {
                alert('Error al cargar los conteos de tickets: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los conteos de tickets');
        });
}

// Carga los conteos inicialmente al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    loadTickets();
    loadTicketCounts();
});
</script>