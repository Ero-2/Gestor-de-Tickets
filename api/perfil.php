<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión a PostgreSQL
try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Tickets", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar a la base de datos: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetProfile($pdo);
        break;

    case 'PUT':
        handleUpdateProfile($pdo);
        break;

    case 'POST':
        handleUploadPhoto($pdo);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

function handleGetProfile($pdo) {
    try {
        // Verificar autenticación
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado']);
            exit;
        }

        $user_id = $_SESSION['user']['IdUsuario'];

        // Validar ID de usuario
        if (!is_numeric($user_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de usuario inválido']);
            exit;
        }

        // --- CORREGIDO: Obtener datos del perfil directamente de la tabla "usuario" ---
        // El nombre del usuario se toma del campo "Nombre" de la tabla "usuario"
        // Asegúrate de que el nombre del campo en la BD sea "Nombre" o "nombre" según corresponda.
        // Esta consulta asume que el campo es "Nombre" (con mayúscula). Ajústala si es diferente.
        $stmt = $pdo->prepare('
            SELECT 
                u."IdUsuario",
                u."nombre",       -- <-- Campo correcto de la tabla "usuario"
                u."Email",
                u."Telefono",
                u."Puesto",
                u."FotoPerfil",
                u."FechaCreacion",
                u."FechaModificacion",
                CASE 
                    WHEN u."IdTipoDeUsuario" = 1 THEN \'Administrador\'
                    ELSE \'Usuario\'
                END AS "TipoUsuario"
            FROM 
                "usuario" u
            WHERE 
                u."IdUsuario" = :user_id
        ');
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuario no encontrado']);
            exit;
        }

        // Obtener estadísticas de tickets
        $stmt = $pdo->prepare('
            SELECT 
                COUNT(*) as "TotalTickets",
                COUNT(CASE WHEN "EstadoTicket" = \'En espera\' THEN 1 END) as "TicketsPendientes",
                COUNT(CASE WHEN "EstadoTicket" = \'Resuelto\' THEN 1 END) as "TicketsResueltos"
            FROM 
                "Tickets"
            WHERE 
                "IdUsuarioCreador" = :user_id
        ');
        $stmt->execute(['user_id' => $user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fusionar los datos del usuario con las estadísticas
        $response = array_merge($user, $stats);

        echo json_encode($response);
    } catch (PDOException $e) {
        http_response_code(500);
        // Proporcionar más detalles del error puede ser útil para depurar, pero cuidado en producción.
        echo json_encode([
            'error' => 'Error al obtener el perfil: ' . $e->getMessage()
            // Considera omitir el mensaje detallado en producción por seguridad
        ]);
    } catch (Exception $e) {
         http_response_code(500);
         echo json_encode(['error' => 'Error inesperado: ' . $e->getMessage()]);
    }
}

function handleUpdateProfile($pdo) {
    try {
        // Verificar autenticación
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado']);
            exit;
        }

        // Obtener datos JSON del cuerpo de la solicitud
        $jsonInput = file_get_contents('php://input');
        $data = json_decode($jsonInput, true);

        // Verificar si la decodificación JSON fue exitosa
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato JSON inválido en la solicitud: ' . json_last_error_msg()]);
            exit;
        }

        $user_id = $_SESSION['user']['IdUsuario'];

        // Extraer y validar datos
        $email = $data['email'] ?? null;
        $telefono = $data['telefono'] ?? null;
        $puesto = $data['puesto'] ?? null;

        // Validar datos requeridos
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Email es requerido']);
            exit;
        }

        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de email inválido']);
            exit;
        }

        // Verificar que el email no esté en uso por otro usuario
        $stmt = $pdo->prepare('
            SELECT "IdUsuario" FROM "usuario"
            WHERE "Email" = :email AND "IdUsuario" != :user_id
        ');
        $stmt->execute(['email' => $email, 'user_id' => $user_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'El email ya está en uso por otro usuario']);
            exit;
        }

        // Actualizar perfil
        $stmt = $pdo->prepare('
            UPDATE "usuario"
            SET "Email" = :email,
                "Telefono" = :telefono,
                "Puesto" = :puesto,
                "FechaModificacion" = CURRENT_TIMESTAMP
            WHERE "IdUsuario" = :user_id
        ');

        $result = $stmt->execute([
            'email' => $email,
            'telefono' => $telefono,
            'puesto' => $puesto,
            'user_id' => $user_id
        ]);

        if (!$result) {
             throw new PDOException("Error al ejecutar la actualización del perfil.");
        }


        // Actualizar datos en la sesión si es necesario
        // (Aunque generalmente se recomienda refrescar la sesión desde la BD)
        if (isset($_SESSION['user'])) {
             $_SESSION['user']['Email'] = $email;
        }

        echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el perfil: ' . $e->getMessage()]);
    } catch (Exception $e) {
         http_response_code(500);
         echo json_encode(['error' => 'Error inesperado: ' . $e->getMessage()]);
    }
}

function handleUploadPhoto($pdo) {
    try {
        // Verificar autenticación
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado']);
            exit;
        }

        $user_id = $_SESSION['user']['IdUsuario'];

        // Verificar si es una solicitud de subida de archivo
        if (!isset($_FILES['foto'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No se encontró el archivo. Asegúrate de enviar el campo \'foto\'.']);
            exit;
        }

        $file = $_FILES['foto'];

        // Validar el archivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = match($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo es demasiado grande.',
                UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente.',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco.',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.',
                default => 'Error desconocido al subir el archivo (código ' . $file['error'] . ').'
            };
            http_response_code(400);
            echo json_encode(['error' => 'Error al subir el archivo: ' . $error_message]);
            exit;
        }

        // Validar tipo de archivo MIME
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_mime_types)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF. Tipo recibido: ' . $file['type']]);
            exit;
        }

        // Validar tamaño (máximo 5MB)
        $maxFileSize = 5 * 1024 * 1024; // 5MB en bytes
        if ($file['size'] > $maxFileSize) {
            http_response_code(400);
            echo json_encode(['error' => 'El archivo es demasiado grande. Máximo ' . ($maxFileSize / (1024*1024)) . 'MB. Tamaño recibido: ' . round($file['size'] / (1024*1024), 2) . 'MB']);
            exit;
        }

        // Generar nombre único para el archivo
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // Asegurarse de que la extensión sea una de las permitidas (aunque ya se validó el MIME)
        if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
             $fileExtension = 'jpg'; // Por defecto, aunque la validación MIME debería haber fallado antes.
        }
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $fileExtension;
        $uploadDirectory = __DIR__ . '/../uploads/profiles/'; // Ruta relativa al directorio del script actual (api/)
        $upload_path = $uploadDirectory . $filename;

        // Crear directorio si no existe
        if (!is_dir($uploadDirectory)) {
            if (!mkdir($uploadDirectory, 0755, true)) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear el directorio de subida: ' . $uploadDirectory]);
                exit;
            }
        }

        // Verificar permisos de escritura en el directorio
        if (!is_writable($uploadDirectory)) {
             http_response_code(500);
             echo json_encode(['error' => 'No se tienen permisos de escritura en el directorio de subida: ' . $uploadDirectory]);
             exit;
        }


        // Mover el archivo
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el archivo en el servidor.']);
            exit;
        }

        // Guardar la ruta relativa en la base de datos (sin 'uploads/' al inicio si ya está en la URL base)
        // Ajusta esta ruta según cómo sirvas los archivos desde tu servidor web.
        $relativePathToStore = 'uploads/profiles/' . $filename;

        // Actualizar la ruta de la foto en la base de datos
        $stmt = $pdo->prepare('
            UPDATE "usuario"
            SET "FotoPerfil" = :foto_perfil,
                "FechaModificacion" = CURRENT_TIMESTAMP
            WHERE "IdUsuario" = :user_id
        ');

        $result = $stmt->execute([
            'foto_perfil' => $relativePathToStore,
            'user_id' => $user_id
        ]);

         if (!$result) {
             // Si falla la actualización en BD, intenta borrar el archivo subido para no dejar huérfanos
             @unlink($upload_path);
             throw new PDOException("Error al actualizar la ruta de la foto en la base de datos.");
         }


        echo json_encode([
            'success' => true,
            'message' => 'Foto actualizada correctamente',
            'foto_url' => $relativePathToStore // Devuelve la ruta para que el frontend la use
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar la foto en la base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al procesar la subida de la foto: ' . $e->getMessage()]);
    }
}
?>
