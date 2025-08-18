<?php
// views/perfil.php - Vista del Perfil de Usuario

// Verificar autenticación
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Determinar si el usuario es administrador
$is_admin = false;
$user_type_label = 'Usuario';
if (isset($_SESSION['user']['IdTipoDeUsuario']) && $_SESSION['user']['IdTipoDeUsuario'] == 1) {
    $is_admin = true;
    $user_type_label = 'Administrador';
}

// Obtener datos básicos de la sesión si están disponibles
$user_name_session = isset($_SESSION['user']['Nombre']) ? htmlspecialchars($_SESSION['user']['Nombre']) : 'Usuario';
$user_email_session = isset($_SESSION['user']['Email']) ? htmlspecialchars($_SESSION['user']['Email']) : '';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Perfil de Usuario</h2>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna Izquierda: Foto de Perfil e Información Básica -->
        <div class="col-lg-4">
            <!-- Tarjeta: Foto de Perfil -->
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0">Foto de Perfil</h5>
                </div>
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <!-- Imagen de perfil con fallback -->
                        <img id="profileImage"
                             src="assets/images/default-profile.png"
                             alt="Foto de Perfil"
                             class="img-fluid rounded-circle border"
                             style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <!-- Botón para abrir el modal de cambio de foto -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                        <i class="bi bi-camera"></i> Cambiar Foto
                    </button>
                    <p class="text-muted small mt-3 mb-0">
                        <small>Formatos permitidos: JPG, PNG, GIF<br>(Máx. 5MB)</small>
                    </p>
                </div>
                <!-- Tarjeta: Información Básica -->
            <div class="card shadow-sm mt-4 h-100">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0">Información Básica</h5>
                </div>
                <div class="card-body text-center py-4">
                    <h4 id="profileName" class="mb-2"><?php echo $user_name_session; ?></h4>
                    <p id="profileEmail" class="text-muted mb-3"><?php echo $user_email_session; ?></p>
                    <p id="profilePuesto" class="mb-3"><strong>Puesto:</strong> <span id="puestoDisplay">Cargando...</span></p>
                    <!-- Badge para el tipo de usuario -->
                    <span class="badge <?php echo $is_admin ? 'bg-danger' : 'bg-success'; ?> fs-6 px-3 py-2" id="profileType">
                        <?php echo $user_type_label; ?>
                    </span>
                </div>
            </div>
        </div>

            </div>

            

        <!-- Columna Derecha: Formulario de Información y Estadísticas -->
        <div class="col-lg-8">
            <!-- Tarjeta: Información Personal -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0">Información Personal</h5>
                </div>
                <div class="card-body py-4">
                    <form id="profileForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label fw-bold">Nombre Completo</label>
                                <input type="text" class="form-control form-control-lg" id="nombre" placeholder="Nombre completo" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-lg" id="email" placeholder="correo@ejemplo.com" required>
                                <div class="invalid-feedback">
                                    Por favor, ingresa un email válido.
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="telefono" class="form-label fw-bold">Teléfono</label>
                                <input type="text" class="form-control form-control-lg" id="telefono" placeholder="Teléfono">
                            </div>
                            <div class="col-md-6">
                                <label for="puesto" class="form-label fw-bold">Puesto</label>
                                <input type="text" class="form-control form-control-lg" id="puesto" placeholder="Puesto">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="fechaRegistro" class="form-label fw-bold">Fecha de Registro</label>
                                <input type="text" class="form-control form-control-lg" id="fechaRegistro" placeholder="Fecha de registro" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="fechaModificacion" class="form-label fw-bold">Última Modificación</label>
                                <input type="text" class="form-control form-control-lg" id="fechaModificacion" placeholder="Última modificación" readonly>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4 py-2">
                                <i class="bi bi-save me-2"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tarjeta: Estadísticas de Tickets -->
            <div class="card shadow-sm">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0">Estadísticas de Tickets</h5>
                </div>
                <div class="card-body py-4">
                    <div class="row text-center g-3">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white h-100 shadow">
                                <div class="card-body py-4">
                                    <h5 class="card-title display-5 fw-bold" id="totalTickets">0</h5>
                                    <p class="card-text fs-5">Total Tickets</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white h-100 shadow">
                                <div class="card-body py-4">
                                    <h5 class="card-title display-5 fw-bold" id="ticketsPendientes">0</h5>
                                    <p class="card-text fs-5">Pendientes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white h-100 shadow">
                                <div class="card-body py-4">
                                    <h5 class="card-title display-5 fw-bold" id="ticketsResueltos">0</h5>
                                    <p class="card-text fs-5">Resueltos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Cambiar Foto de Perfil -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadPhotoModalLabel">Cambiar Foto de Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="photoUploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="foto" class="form-label fw-bold">Seleccionar nueva foto</label>
                        <input type="file" class="form-control form-control-lg" id="foto" name="foto" accept="image/*" required>
                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Máximo 5MB</div>
                        <div class="invalid-feedback">
                            Por favor, selecciona un archivo de imagen válido.
                        </div>
                    </div>
                    <!-- Contenedor para la vista previa de la imagen -->
                    <div class="preview-container d-none text-center mt-3">
                        <img id="imagePreview" src="#" alt="Vista previa" class="img-fluid rounded" style="max-height: 250px;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="uploadPhotoBtn">
                    <i class="bi bi-upload me-1"></i> Subir Foto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts (se recomienda colocar al final del body en el layout principal) -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar la carga del perfil
        loadProfile();

        // Manejar el envío del formulario de perfil
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function (e) {
                e.preventDefault();
                updateProfile();
            });
        }

        // Manejar la vista previa de la imagen en el modal
        const fotoInput = document.getElementById('foto');
        if (fotoInput) {
            fotoInput.addEventListener('change', function (e) {
                const previewContainer = document.querySelector('.preview-container');
                const imagePreview = document.getElementById('imagePreview');
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (imagePreview) {
                            imagePreview.src = e.target.result;
                            if (previewContainer) {
                                previewContainer.classList.remove('d-none');
                            }
                        }
                    }
                    reader.readAsDataURL(e.target.files[0]);
                } else {
                    if (previewContainer) {
                        previewContainer.classList.add('d-none');
                    }
                }
            });
        }

        // Manejar el botón de subir foto
        const uploadBtn = document.getElementById('uploadPhotoBtn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', function () {
                uploadPhoto();
            });
        }
    });

    /**
     * Carga los datos del perfil del usuario desde la API.
     */
    function loadProfile() {
        fetch('api/perfil.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la red o servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Error de API:', data.error);
                    alert('Error al cargar el perfil: ' + data.error);
                    return;
                }

                // Actualizar campos del formulario de perfil
                const fields = ['nombre', 'email', 'telefono', 'puesto', 'fechaRegistro', 'fechaModificacion'];
                fields.forEach(fieldId => {
                    const element = document.getElementById(fieldId);
                    if (element) {
                        if (fieldId === 'fechaRegistro' || fieldId === 'fechaModificacion') {
                            element.value = data[fieldId] ? formatDate(data[fieldId]) : 'No disponible';
                        } else {
                            element.value = data[fieldId] || '';
                        }
                    }
                });

                // Actualizar información en la tarjeta de información básica
                const profileName = document.getElementById('profileName');
                if (profileName) profileName.textContent = data.Nombre || 'Usuario';

                const profileEmail = document.getElementById('profileEmail');
                if (profileEmail) profileEmail.textContent = data.Email || '';

                const puestoDisplay = document.getElementById('puestoDisplay');
                if (puestoDisplay) puestoDisplay.textContent = data.Puesto || 'No especificado';

                // Actualizar estadísticas de tickets
                const stats = {
                    'totalTickets': data.TotalTickets,
                    'ticketsPendientes': data.TicketsPendientes,
                    'ticketsResueltos': data.TicketsResueltos
                };
                for (const [id, value] of Object.entries(stats)) {
                    const element = document.getElementById(id);
                    if (element) element.textContent = value !== undefined ? value : 0;
                }

                // Actualizar foto de perfil
                const profileImage = document.getElementById('profileImage');
                if (profileImage) {
                    const fotoUrl = data.FotoPerfil;
                    if (fotoUrl) {
                        // Añadir timestamp para evitar caché
                        profileImage.src = fotoUrl + (fotoUrl.includes('?') ? '&' : '?') + 't=' + new Date().getTime();
                    } else {
                        profileImage.src = 'assets/images/default-profile.png';
                    }
                }

            })
            .catch(error => {
                console.error('Error al cargar el perfil:', error);
                alert('Error al cargar el perfil. Por favor, inténtalo de nuevo más tarde.');
            });
    }

    /**
     * Actualiza la información del perfil del usuario.
     */
    function updateProfile() {
        const emailInput = document.getElementById('email');
        const emailValue = emailInput ? emailInput.value.trim() : '';

        // Validación básica del lado del cliente
        if (!emailValue) {
            alert('El email es requerido.');
            if (emailInput) emailInput.focus();
            return;
        }

        const formData = {
            email: emailValue,
            telefono: document.getElementById('telefono') ? document.getElementById('telefono').value : '',
            puesto: document.getElementById('puesto') ? document.getElementById('puesto').value : ''
        };

        fetch('api/perfil.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito con Bootstrap Toast o Alert
                    alert('Perfil actualizado correctamente.');
                    loadProfile(); // Recargar datos para reflejar cambios
                } else {
                    console.error('Error de API al actualizar:', data.error);
                    alert('Error al actualizar el perfil: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error al actualizar el perfil:', error);
                alert('Error al actualizar el perfil. Por favor, inténtalo de nuevo más tarde.');
            });
    }

    /**
     * Sube una nueva foto de perfil.
     */
    function uploadPhoto() {
        const fileInput = document.getElementById('foto');
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            alert('Por favor, selecciona una imagen.');
            return;
        }

        const file = fileInput.files[0];
        // Validación de tipo de archivo (ya se hace en HTML5, pero por si acaso)
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF.');
            return;
        }
        // Validación de tamaño (ya se hace en HTML5, pero por si acaso)
        if (file.size > 5 * 1024 * 1024) { // 5MB
            alert('El archivo es demasiado grande. Máximo 5MB.');
            return;
        }

        const formData = new FormData();
        formData.append('foto', file);

        fetch('api/perfil.php', {
            method: 'POST',
            body: formData // No establecer Content-Type, deja que el navegador lo haga
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito con Bootstrap Toast o Alert
                    alert('Foto actualizada correctamente.');
                    // Cerrar el modal usando Bootstrap
                    const modalElement = document.getElementById('uploadPhotoModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                        modal.hide();
                    }
                    // Resetear el formulario del modal
                    const photoUploadForm = document.getElementById('photoUploadForm');
                    if (photoUploadForm) photoUploadForm.reset();
                    // Ocultar la vista previa
                    const previewContainer = document.querySelector('.preview-container');
                    if (previewContainer) previewContainer.classList.add('d-none');
                    // Recargar datos del perfil para mostrar la nueva foto
                    loadProfile();
                } else {
                    console.error('Error de API al subir foto:', data.error);
                    alert('Error al subir la foto: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error al subir la foto:', error);
                alert('Error al subir la foto. Por favor, inténtalo de nuevo más tarde.');
            });
    }

    /**
     * Formatea una fecha ISO a un formato legible en español.
     * @param {string} dateString - La fecha en formato ISO.
     * @returns {string} - La fecha formateada.
     */
    function formatDate(dateString) {
        if (!dateString) return 'No disponible';
        const date = new Date(dateString);
        // Usar toLocaleString para un formato más completo y localizado
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }
</script>