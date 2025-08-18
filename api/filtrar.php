<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        // Listar tickets con filtros opcionales (solo tickets 'En espera')
        try {
            // Inicializar parámetros y cláusulas WHERE
            $params = [];
            $where_clauses = [];

            // --- Filtros ---
            // 1. Filtro fijo: Solo tickets 'En espera'
            $where_clauses[] = '"EstadoTicket" = :estado';
            $params['estado'] = 'En espera';

            // 2. Filtro por Departamento Destino (opcional)
            $departamento_id = isset($_GET['departamento_id']) ? intval($_GET['departamento_id']) : null;
            if ($departamento_id !== null) {
                $where_clauses[] = '"IdDepartamentoDestino" = :departamento_id';
                $params['departamento_id'] = $departamento_id;
            }

            // 3. Filtro por Prioridad (opcional)
            $prioridad = isset($_GET['prioridad']) && in_array($_GET['prioridad'], ['baja', 'media', 'alta']) ? strtoupper($_GET['prioridad']) : null;
            if ($prioridad !== null) {
                $where_clauses[] = '"Prioridad" = :prioridad';
                $params['prioridad'] = $prioridad;
            }
            // --- Fin Filtros ---

            // Consulta base
            $sql = 'SELECT 
                        "Tickets".*, 
                        u."nombre" AS "usuario_nombre", 
                        u."Puesto" AS "usuario_puesto",
                        u."FotoPerfil" AS "usuario_foto"
                    FROM "Tickets"
                    LEFT JOIN "usuario" u ON "Tickets"."IdUsuarioCreador" = u."IdUsuario"';

            // Agregar cláusula WHERE si hay condiciones
            if (!empty($where_clauses)) {
                $sql .= ' WHERE ' . implode(' AND ', $where_clauses);
            }

            // Ordenar por fecha de creación descendente
            $sql .= ' ORDER BY "FechaCreacion" DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'tickets' => $tickets
                // Nota: Esta version no incluye 'counts'. Si los necesitas, se pueden agregar aqui.
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener los tickets: ' . $e->getMessage()]);
        }
        break;

    // ... (el resto de métodos POST, PUT, DELETE ya existentes)
}
?>