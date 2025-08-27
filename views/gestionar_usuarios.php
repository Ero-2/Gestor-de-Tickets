<?php
$page_title = 'Gestión de Usuarios';
?>

<!-- Controles de Filtro -->
<div class="row mb-3">
    <div class="col-md-4">
        <label for="filtroDepartamento" class="form-label">Departamento:</label>
        <select class="form-select" id="filtroDepartamento">
            <option value="">Todos los Departamentos</option>
            <!-- Opciones cargadas dinámicamente -->
        </select>
    </div>
    <div class="col-md-3">
        <label for="filtroTipo" class="form-label">Tipo de Usuario:</label>
        <select class="form-select" id="filtroTipo">
            <option value="">Todos los Tipos</option>
            <option value="1">Administrador</option>
            <option value="2">Usuario</option>
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
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="usuariosTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Departamento</th>
                            <th>Puesto</th>
                            <th>Tipo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usuariosBody">
                        <tr>
                            <td colspan="8" class="text-center">
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
</div>

<!-- Modal para Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalEditarUsuarioLabel">Editar Usuario</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalEditarUsuarioBody">
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Estado de los filtros
let filtrosActuales = {
    departamento_id: '',
    tipo_usuario: ''
};

document.addEventListener('DOMContentLoaded', function() {
    cargarOpcionesDepartamento();
    loadUsuarios();

    // Eventos de botones
    document.getElementById('btnAplicarFiltros')?.addEventListener('click', aplicarFiltros);
    document.getElementById('btnLimpiarFiltros')?.addEventListener('click', limpiarFiltros);
});

// Cargar opciones de departamentos
function cargarOpcionesDepartamento() {
    fetch('api/obtener.php')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('filtroDepartamento');
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
        .catch(err => console.error('Error al cargar departamentos:', err));
}

// Aplicar filtros (independientes)
function aplicarFiltros() {
    const departamento = document.getElementById('filtroDepartamento').value;
    const tipo = document.getElementById('filtroTipo').value;

    filtrosActuales.departamento_id = departamento || '';
    filtrosActuales.tipo_usuario = tipo || '';

    loadUsuarios(); // Recargar con filtros actuales
}

// Limpiar filtros
function limpiarFiltros() {
    document.getElementById('filtroDepartamento').value = '';
    document.getElementById('filtroTipo').value = '';
    filtrosActuales.departamento_id = '';
    filtrosActuales.tipo_usuario = '';
    loadUsuarios();
}

// Cargar usuarios con filtros aplicados
function loadUsuarios() {
    const url = new URL('api/usuarios.php', window.location.origin + window.location.pathname);

    // Solo añadir parámetros si tienen valor
    Object.entries(filtrosActuales).forEach(([key, value]) => {
        if (value !== '') {
            url.searchParams.append(key, value);
        }
    });

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('usuariosBody');
            if (data.error) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">${data.error}</td></tr>`;
                return;
            }
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay usuarios que coincidan con los filtros.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.map(usuario => {
                const tipoBadge = usuario.IdTipoDeUsuario == 1 
                    ? '<span class="badge bg-danger">Admin</span>' 
                    : '<span class="badge bg-primary">Usuario</span>';
                
                return `
                <tr data-id-usuario="${usuario.IdUsuario}">
                    <td>${usuario.IdUsuario}</td>
                    <td>${usuario.Usuario}</td>
                    <td>${usuario.nombre}</td>
                    <td>${usuario.Email || 'N/A'}</td>
                    <td>${usuario.departamento_nombre || 'N/A'}</td>
                    <td>${usuario.Puesto || 'N/A'}</td>
                    <td>${tipoBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-warning me-1" onclick="editarUsuario(${usuario.IdUsuario})" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${usuario.IdUsuario})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(error => {
            console.error('Error al cargar usuarios:', error);
            document.getElementById('usuariosBody').innerHTML = 
                `<tr><td colspan="8" class="text-center text-danger">Error al cargar los usuarios.</td></tr>`;
        });
}

// Manejar creación de usuario (tu código original)
document.getElementById('formCrearUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const usuario = document.getElementById('inputUsuario').value.trim();
    const nombre = document.getElementById('inputNombre').value.trim();
    const email = document.getElementById('inputEmail').value.trim();
    const contrasena = document.getElementById('inputContrasena').value;
    const puesto = document.getElementById('inputPuesto').value.trim();
    const id_tipo_usuario = parseInt(document.getElementById('selectTipoUsuario').value);

    if (!usuario || !nombre || !email || !contrasena) {
        alert('Por favor, completa todos los campos marcados con *.');
        return;
    }

    fetch('api/usuarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ usuario, nombre, email, contrasena, puesto, id_tipo_usuario })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalCrearUsuario')).hide();
            document.getElementById('formCrearUsuario').reset();
            loadUsuarios();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Error al crear usuario:', err);
        alert('Error al crear el usuario.');
    });
});

// Editar usuario (tu código original)
function editarUsuario(idUsuario) {
    const modalElement = document.getElementById('modalEditarUsuario');
    const modalBody = document.getElementById('modalEditarUsuarioBody');
    const modal = new bootstrap.Modal(modalElement);
    
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    modal.show();

    fetch('api/usuarios.php')
        .then(r => r.json())
        .then(data => {
            const usuario = data.find(u => u.IdUsuario == idUsuario);
            if (!usuario) throw new Error('Usuario no encontrado.');

            return fetch('api/obtener.php')
                .then(r => r.json())
                .then(d => ({ usuario, departamentos: d.departamentos || [] }))
                .catch(() => ({ usuario, departamentos: [] }));
        })
        .then(({ usuario, departamentos }) => {
            let optionsDepartamento = '<option value="">-- Seleccionar Departamento --</option>';
            departamentos.forEach(depto => {
                const selected = parseInt(usuario.IdDepartamentos) === parseInt(depto.IdDepartamentos) ? 'selected' : '';
                optionsDepartamento += `<option value="${depto.IdDepartamentos}" ${selected}>${depto.nombre}</option>`;
            });

            modalBody.innerHTML = `
                <form id="formEditarUsuarioModal">
                    <input type="hidden" id="editIdUsuario" value="${usuario.IdUsuario}">
                    <div class="mb-3">
                        <label for="editUsuario" class="form-label">Nombre de Usuario *</label>
                        <input type="text" class="form-control" id="editUsuario" value="${usuario.Usuario}" required>
                    </div>
                    <div class="mb-3">
                        <label for="editNombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="editNombre" value="${usuario.nombre}" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editEmail" value="${usuario.Email}" required>
                    </div>
                    <div class="mb-3">
                        <label for="editIdDepartamentos" class="form-label">Departamento</label>
                        <select class="form-select" id="editIdDepartamentos">${optionsDepartamento}</select>
                    </div>
                    <div class="mb-3">
                        <label for="editPuesto" class="form-label">Puesto</label>
                        <input type="text" class="form-control" id="editPuesto" value="${usuario.Puesto || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="editIdTipoUsuario" class="form-label">Tipo de Usuario</label>
                        <select class="form-select" id="editIdTipoUsuario">
                            <option value="2" ${usuario.IdTipoDeUsuario == 2 ? 'selected' : ''}>Usuario Normal</option>
                            <option value="1" ${usuario.IdTipoDeUsuario == 1 ? 'selected' : ''}>Administrador</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            `;

            document.getElementById('formEditarUsuarioModal').addEventListener('submit', function(e) {
                e.preventDefault();
                const datos = {
                    id_usuario: document.getElementById('editIdUsuario').value,
                    usuario: document.getElementById('editUsuario').value.trim(),
                    nombre: document.getElementById('editNombre').value.trim(),
                    email: document.getElementById('editEmail').value.trim(),
                    id_tipo_usuario: parseInt(document.getElementById('editIdTipoUsuario').value)
                };

                const id_departamentos = document.getElementById('editIdDepartamentos').value;
                const puesto = document.getElementById('editPuesto').value.trim();

                if (id_departamentos) datos.id_departamentos = id_departamentos;
                if (puesto) datos.puesto = puesto;

                fetch('api/usuarios.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        modal.hide();
                        loadUsuarios();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error('Error al editar:', err);
                    alert('Error al actualizar.');
                });
            });
        })
        .catch(err => {
            modalBody.innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
        });
}

// Eliminar usuario
function eliminarUsuario(idUsuario) {
    if (!confirm(`¿Eliminar usuario ID ${idUsuario}?`)) return;

    fetch('api/usuarios.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_usuario: idUsuario })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadUsuarios();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Error al eliminar:', err);
        alert('Error al eliminar.');
    });
}
</script>