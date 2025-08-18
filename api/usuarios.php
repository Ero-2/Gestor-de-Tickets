<?php
// api/usuarios.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Descomenta si es necesario
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type'); // Para PUT/DELETE

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticación y que sea admin
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado.']);
    exit;
}

if ($_SESSION['user']['IdTipoDeUsuario'] != 1) { // Asumiendo 1 es admin
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Se requieren privilegios de administrador.']);
    exit;
}

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
    // Listar todos los usuarios con el nombre de su departamento
    try {
        $stmt = $pdo->query('
            SELECT 
                u."IdUsuario", 
                u."Usuario", 
                u."nombre", 
                u."Email", 
                u."Puesto", 
                u."FotoPerfil", 
                u."IdTipoDeUsuario",
                d."nombre" AS "departamento_nombre"
            FROM "usuario" u
            LEFT JOIN "departamentos" d ON u."IdDepartamentos" = d."IdDepartamentos"
            ORDER BY u."IdUsuario"
        ');
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($usuarios);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener los usuarios: ' . $e->getMessage()]);
    }
    break;
   

    case 'POST':
        // Crear nuevo usuario
        $data = json_decode(file_get_contents('php://input'), true);

        $usuario = $data['usuario'] ?? null;
        $nombre = $data['nombre'] ?? null;
        $email = $data['email'] ?? null;
        $contrasena = $data['contrasena'] ?? null;
        $puesto = $data['puesto'] ?? null;
        $id_tipo_usuario = intval($data['id_tipo_usuario']) ?? 2; // Por defecto, tipo 2 (no admin)

        if (!$usuario || !$nombre || !$email || !$contrasena) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos: usuario, nombre, email, contrasena']);
            exit;
        }

        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de email inválido.']);
            exit;
        }

        // Validar longitud mínima de contraseña (ejemplo: 6 caracteres)
        if (strlen($contrasena) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres.']);
            exit;
        }

        try {
            // Verificar si el nombre de usuario o email ya existen
            $stmt_check = $pdo->prepare('SELECT COUNT(*) FROM "usuario" WHERE "Usuario" = :usuario OR "Email" = :email');
            $stmt_check->execute(['usuario' => $usuario, 'email' => $email]);
            if ($stmt_check->fetchColumn() > 0) {
                http_response_code(409); // Conflict
                echo json_encode(['error' => 'El nombre de usuario o email ya están en uso.']);
                exit;
            }

            // Hashear la contraseña
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('
                INSERT INTO "usuario" (
                    "Usuario", "nombre", "Email", "Contrasena", "Puesto", "IdTipoDeUsuario"
                ) VALUES (
                    :usuario, :nombre, :email, :contrasena, :puesto, :id_tipo_usuario
                )
            ');

            $stmt->execute([
                'usuario' => $usuario,
                'nombre' => $nombre,
                'email' => $email,
                'contrasena' => $contrasena_hash,
                'puesto' => $puesto,
                'id_tipo_usuario' => $id_tipo_usuario
            ]);

            $nuevo_id = $pdo->lastInsertId(); // Obtener el ID del nuevo usuario

            echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente.', 'id_usuario' => $nuevo_id]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear el usuario: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
    // Editar usuario (por ejemplo, cambiar nombre, email, puesto, tipo o departamento)
    // NOTA: Editar contraseña o nombre de usuario requiere lógica adicional más compleja.
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id_usuario = $data['id_usuario'] ?? null;
    $nombre = $data['nombre'] ?? null;
    $email = $data['email'] ?? null;
    $puesto = $data['puesto'] ?? null;
    $id_departamentos = $data['id_departamentos'] ?? null; // Nuevo campo para el departamento
    $id_tipo_usuario = $data['id_tipo_usuario'] ?? null; // Puede ser null si no se cambia

    if (!$id_usuario || (!$nombre && !$email && !$puesto && $id_departamentos === null && $id_tipo_usuario === null)) {
         http_response_code(400);
         echo json_encode(['error' => 'Se requiere ID de usuario y al menos un campo para actualizar (nombre, email, puesto, id_departamentos, id_tipo_usuario).']);
         exit;
    }
    
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato de email inválido.']);
        exit;
    }

    try {
        // Construir la consulta dinámicamente
        $updates = [];
        $params = ['id_usuario' => $id_usuario];
        
        if ($nombre !== null) {
            $updates[] = '"nombre" = :nombre';
            $params['nombre'] = $nombre;
        }
        if ($email !== null) {
            // Verificar que el nuevo email no esté en uso por otro usuario
            $stmt_check_email = $pdo->prepare('SELECT COUNT(*) FROM "usuario" WHERE "Email" = :email AND "IdUsuario" <> :id_usuario');
            $stmt_check_email->execute(['email' => $email, 'id_usuario' => $id_usuario]);
            if ($stmt_check_email->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'El email ya está en uso por otro usuario.']);
                exit;
            }
            $updates[] = '"Email" = :email';
            $params['email'] = $email;
        }
        if ($puesto !== null) {
            $updates[] = '"Puesto" = :puesto';
            $params['puesto'] = $puesto;
        }
        if ($id_departamentos !== null) {
            // Validar que el IdDepartamentos exista (puedes ajustar la consulta según tu tabla "departamentos")
            $stmt_check_departamento = $pdo->prepare('SELECT COUNT(*) FROM "departamentos" WHERE "IdDepartamentos" = :id_departamentos');
            $stmt_check_departamento->execute(['id_departamentos' => $id_departamentos]);
            if ($stmt_check_departamento->fetchColumn() == 0) {
                http_response_code(400);
                echo json_encode(['error' => 'El departamento especificado no existe.']);
                exit;
            }
            $updates[] = '"IdDepartamentos" = :id_departamentos';
            $params['id_departamentos'] = $id_departamentos;
        }
        if ($id_tipo_usuario !== null) {
            $id_tipo_usuario = intval($id_tipo_usuario);
            if ($id_tipo_usuario < 1 || $id_tipo_usuario > 2) { // Ajusta según tus tipos
                 http_response_code(400);
                 echo json_encode(['error' => 'IdTipoDeUsuario inválido.']);
                 exit;
            }
            // Evitar que un admin se quite el rol de admin a sí mismo accidentalmente
            if ($id_usuario == $_SESSION['user']['IdUsuario'] && $id_tipo_usuario != 1) {
                http_response_code(400);
                echo json_encode(['error' => 'No puedes cambiar tu propio tipo de usuario a no-administrador.']);
                exit;
            }
            $updates[] = '"IdTipoDeUsuario" = :id_tipo_usuario';
            $params['id_tipo_usuario'] = $id_tipo_usuario;
        }

        if (empty($updates)) {
             http_response_code(400);
             echo json_encode(['error' => 'No hay campos válidos para actualizar.']);
             exit;
        }

        $sql = 'UPDATE "usuario" SET ' . implode(', ', $updates) . ' WHERE "IdUsuario" = :id_usuario';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuario no encontrado.']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el usuario: ' . $e->getMessage()]);
    }
    break;

    case 'DELETE':
        // Eliminar usuario
        // NOTA: En aplicaciones reales, es mejor desactivar usuarios en lugar de eliminarlos.
        $data = json_decode(file_get_contents('php://input'), true); // Para DELETE con cuerpo
        $id_usuario = $data['id_usuario'] ?? null;
        
        // Alternativa si envías el ID por query string: $id_usuario = $_GET['id'] ?? null;

        if (!$id_usuario) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el ID del usuario.']);
            exit;
        }

        // Evitar que un admin se elimine a sí mismo
        if ($id_usuario == $_SESSION['user']['IdUsuario']) {
            http_response_code(400);
            echo json_encode(['error' => 'No puedes eliminarte a ti mismo.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM "usuario" WHERE "IdUsuario" = :id_usuario');
            $stmt->execute(['id_usuario' => $id_usuario]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuario no encontrado.']);
                exit;
            }

            echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente.']);
        } catch (PDOException $e) {
            // Capturar errores específicos, como violaciones de clave foránea
            if ($e->getCode() == '23503') { // foreign_key_violation
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'No se puede eliminar el usuario porque tiene tickets asociados.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar el usuario: ' . $e->getMessage()]);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>