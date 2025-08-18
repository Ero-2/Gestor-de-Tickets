
<?php

$page_title = 'Gestión de Usuarios';
?>

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
                        <!-- Los datos se cargarán aquí mediante AJAX -->
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
        <!-- El contenido del formulario se cargará aquí -->
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
document.addEventListener('DOMContentLoaded', function() {
    loadUsuarios();
});

function loadUsuarios() {
    fetch('api/usuarios.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('usuariosBody');
            if (data.error) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">${data.error}</td></tr>`;
                return;
            }
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay usuarios registrados.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.map((usuario, index) => {
                // Opcional: Generar un número secuencial para mostrar (sin cambiar el ID real)
                // const numeroSecuencial = index + 1; 
                
                const tipoBadge = usuario.IdTipoDeUsuario == 1 ? 
                    '<span class="badge bg-danger">Admin</span>' : 
                    '<span class="badge bg-primary">Usuario</span>';
                
                return `<tr data-id-usuario="${usuario.IdUsuario}">
                    <td>${usuario.IdUsuario}</td> <!-- O usa 'numeroSecuencial' si prefieres mostrar una secuencia visual -->
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
                `<tr><td colspan="8" class="text-center text-danger">Error al cargar los usuarios. Ver consola.</td></tr>`;
        });
}

// Manejar el envío del formulario de creación
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
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            usuario: usuario,
            nombre: nombre,
            email: email,
            contrasena: contrasena,
            puesto: puesto,
            id_tipo_usuario: id_tipo_usuario
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Cerrar el modal
            bootstrap.Modal.getInstance(document.getElementById('modalCrearUsuario')).hide();
            // Limpiar el formulario
            document.getElementById('formCrearUsuario').reset();
            // Recargar la lista de usuarios
            loadUsuarios();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error al crear usuario:', error);
        alert('Error al crear el usuario. Ver consola.');
    });
});

function editarUsuario(idUsuario) {
    // 1. Mostrar el modal vacío con el spinner
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

    // 2. Obtener los datos del usuario a editar
    // Opción A: Si tu api/usuarios.php tiene soporte para ?id=ID
    // fetch(`api/usuarios.php?id=${idUsuario}`)
    // Opción B: Obtener todos y filtrar (como en tu código original)
    fetch('api/usuarios.php') 
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            // Buscar el usuario específico en la lista
            const usuario = data.find(u => u.IdUsuario == idUsuario);
            if (!usuario) {
                throw new Error('Usuario no encontrado.');
            }
            return usuario;
        })
        .then(usuario => {
            // 3. Obtener la lista de departamentos para el select
            return fetch('api/obtener.php') // Asumiendo que 'obtener.php' devuelve departamentos
                .then(response => response.json())
                .then(departamentosData => {
    if (departamentosData.error) {
        console.warn('No se pudieron cargar los departamentos:', departamentosData.error);
        return { usuario, departamentos: [] };
    }
    return { usuario, departamentos: departamentosData.departamentos || [] };
})
                .catch(err => {
                     console.error('Error al cargar departamentos:', err);
                     // Proceder sin departamentos
                     return { usuario, departamentos: [] };
                });
        })
        .then(({ usuario, departamentos }) => {
            // 4. Generar el HTML del formulario dentro del modal
            let optionsDepartamento = '<option value="">-- Seleccionar Departamento --</option>';
            departamentos.forEach(depto => {
                // Asegúrate de que los nombres de las propiedades coincidan con lo que devuelve tu API
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
                        <select class="form-select" id="editIdDepartamentos">
                            ${optionsDepartamento}
                        </select>
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
            
            // 5. Añadir el listener al formulario recién creado
            document.getElementById('formEditarUsuarioModal').addEventListener('submit', function(e) {
                 e.preventDefault();
                 
                 const id_usuario = document.getElementById('editIdUsuario').value;
                 const usuario = document.getElementById('editUsuario').value.trim();
                 const nombre = document.getElementById('editNombre').value.trim();
                 const email = document.getElementById('editEmail').value.trim();
                 const id_departamentos = document.getElementById('editIdDepartamentos').value || null; // Puede ser null
                 const puesto = document.getElementById('editPuesto').value.trim() || null; // Puede ser null
                 const id_tipo_usuario = parseInt(document.getElementById('editIdTipoUsuario').value);

                 if (!id_usuario || !usuario || !nombre || !email) {
                     alert('Por favor, completa todos los campos marcados con *.');
                     return;
                 }

                 // Preparar datos para enviar, incluyendo campos que pueden ser null
                 const datosActualizar = {
                     id_usuario: id_usuario,
                     usuario: usuario,
                     nombre: nombre,
                     email: email,
                     id_tipo_usuario: id_tipo_usuario
                 };
                 // Solo añadir campos al objeto si tienen valor
                 if (id_departamentos !== null && id_departamentos !== '') datosActualizar.id_departamentos = id_departamentos;
                 if (puesto !== null && puesto !== '') datosActualizar.puesto = puesto;

                 fetch('api/usuarios.php', {
                     method: 'PUT',
                     headers: {
                         'Content-Type': 'application/json',
                     },
                     body: JSON.stringify(datosActualizar)
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         alert(data.message);
                         modal.hide(); // Cerrar el modal
                         loadUsuarios(); // Recargar la lista
                     } else {
                         alert('Error: ' + data.error);
                     }
                 })
                 .catch(error => {
                     console.error('Error al editar usuario:', error);
                     alert('Error al editar el usuario. Ver consola.');
                 });
            });
        })
        .catch(error => {
            console.error('Error al cargar datos para editar usuario:', error);
            modalBody.innerHTML = `<div class="alert alert-danger">Error al cargar datos del usuario: ${error.message}</div>`;
        });
}


function eliminarUsuario(idUsuario) {
    if (!confirm(`¿Estás seguro de que quieres eliminar el usuario con ID ${idUsuario}? Esta acción no se puede deshacer.`)) {
        return;
    }

    fetch('api/usuarios.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id_usuario: idUsuario })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Recargar la lista de usuarios
            loadUsuarios();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error al eliminar usuario:', error);
        alert('Error al eliminar el usuario. Ver consola.');
    });
}
</script>
