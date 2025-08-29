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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $params = [];
    $where_clauses = [];

    // 1. Filtro fijo: solo tickets resueltos
    $where_clauses[] = '"EstadoTicket" = :estado';
    $params['estado'] = 'Resuelto';

    // 2. Filtro por Departamento Destino (opcional)
    $departamento_id = isset($_GET['departamento_id']) ? intval($_GET['departamento_id']) : null;
    if ($departamento_id > 0) {
        $where_clauses[] = '"IdDepartamentoDestino" = :departamento_id';
        $params['departamento_id'] = $departamento_id;
    }

    // 3. Filtro por Prioridad (opcional)
    // Usamos el mismo mapeo que en api/tickets.php
    $prioridad_raw = isset($_GET['prioridad']) ? strtolower(trim($_GET['prioridad'])) : null;
    $mapPrioridad = [
        'baja'  => 'Baja',
        'media' => 'Media',
        'alta'  => 'Alta'
    ];

    if ($prioridad_raw !== null && isset($mapPrioridad[$prioridad_raw])) {
        $where_clauses[] = '"Prioridad" = :prioridad';
        $params['prioridad'] = $mapPrioridad[$prioridad_raw]; // 'Baja', 'Media', 'Alta'
    }

    // Consulta principal
    $sql = '
        SELECT 
            t.*,
            u."nombre" AS "usuario_nombre",
            u."Puesto" AS "usuario_puesto",
            u."FotoPerfil" AS "usuario_foto",
            MAX(CASE WHEN h."Accion" = \'Resolver\' THEN h."FechaAccion" END) AS "fecha_resolucion"
        FROM "Tickets" t
        LEFT JOIN "usuario" u ON t."IdUsuarioCreador" = u."IdUsuario"
        LEFT JOIN "Historial" h ON t."IdTickets" = h."IdTicket"
    ';

    // Agregar WHERE si hay condiciones
    if (!empty($where_clauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $where_clauses);
    }

    $sql .= ' GROUP BY t."IdTickets", u."nombre", u."Puesto", u."FotoPerfil" ORDER BY "fecha_resolucion" DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tickets);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los tickets resueltos: ' . $e->getMessage()]);
}
?>