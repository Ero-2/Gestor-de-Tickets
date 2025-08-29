<!-- views/crear-ticket.php -->
<h2>Crear Ticket</h2>

<form id="ticketForm">
    <div class="mb-3">
        <label for="titulo" class="form-label">Título</label>
        <input type="text" class="form-control" id="titulo" name="titulo" required>
    </div>

    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
    </div>

    <div class="mb-3">
        <label for="prioridad" class="form-label">Prioridad</label>
        <select class="form-control" id="prioridad" name="prioridad" required>
            <option value="Alta">Alta</option>
            <option value="Media">Media</option>
            <option value="Baja">Baja</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="departamento" class="form-label">Departamento destino</label>
        <select class="form-control" id="departamento" name="id_departamento_destino" required>
            <!-- Las opciones se cargarán con JavaScript -->
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Crear Ticket</button>
</form>

<script>
// Cargar departamentos desde la API
fetch('api/obtener.php')
    .then(response => response.json())
    .then(data => {
        const deptoSelect = document.getElementById('departamento');
        data.departamentos.forEach(dep => {
            const option = document.createElement('option');
            option.value = dep.IdDepartamentos;
            option.textContent = dep.nombre;
            deptoSelect.appendChild(option);
        });
    })
    .catch(error => console.error('Error al cargar departamentos:', error));

// Enviar formulario
document.getElementById('ticketForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        titulo: document.getElementById('titulo').value,
        descripcion: document.getElementById('descripcion').value,
        prioridad: document.getElementById('prioridad').value,
        id_departamento_destino: parseInt(document.getElementById('departamento').value),
    };

    try {
        const response = await fetch('api/tickets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            alert('Ticket creado exitosamente');
            window.location.href = 'inicio.php?page=dashboard';
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Error al enviar el ticket:', error);
        if (error.message.includes('Failed to fetch')) {
            alert('Error: No se pudo conectar con el servidor.');
        } else if (error.message.includes('400')) {
            alert('Error: Datos inválidos. Verifica los campos.');
        } else {
            alert('Hubo un problema al enviar el ticket.');
        }
    }
});
</script>