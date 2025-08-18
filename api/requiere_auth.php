<?php
// api/require_auth.php
require 'vendor/autoload.php'; // Incluir Composer autoload

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Función para verificar el JWT
function requireAuth() {
    // Obtener el encabezado Authorization
    $headers = getallheaders();
    $authorization_header = $headers['Authorization'] ?? '';

    // Verificar si el encabezado existe y tiene formato correcto
    if (empty($authorization_header) || !preg_match('/^Bearer\s+(.+)$/', $authorization_header, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token de autorización faltante o mal formado']);
        exit;
    }

    $jwt_token = $matches[1]; // Extraer el token

    // 🔐 Definir la misma clave secreta usada en login.php
    $secret_key = 'TuClaveSecretaMuyLargaYAleatoriaParaProduccion'; // 🔒 Debe ser la MISMA que en login.php!

    try {
        // 🔍 Verificar y decodificar el JWT
        $decoded = JWT::decode($jwt_token, new Key($secret_key, 'HS256'));

        // 🛠️ Extraer la información del usuario del payload
        global $user_info;
        $user_info = [
            'id_usuario' => $decoded->id_usuario,
            'usuario' => $decoded->usuario,
            'tipo_id' => $decoded->tipo_id,
            'tipo' => $decoded->tipo
        ];
    } catch (\Exception $e) {
        // ❌ Si hay un error (firma inválida, expirado, etc.)
        http_response_code(401);
        echo json_encode(['error' => 'Token de autorización inválido o expirado']);
        exit;
    }
}

    // ✅ Si llegamos aquí, el token es válido. La info del usuario está disponible en $user_info.
    // Puedes usarla en el resto del script API.

?>