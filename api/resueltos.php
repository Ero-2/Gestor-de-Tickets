<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Parámetros de filtro
    $params = [];
    $where_clauses = [];

    // Filtro: Solo tickets resueltos (acción "Resolver" en el historial)
    $where_clauses[] = 'h."Accion" = \'Resolver\'';

    // Filtro opcional: Por departamento destino
    $departamento_id = isset($_GET['departamento_id']) ? trim($_GET['departamento_id']) : null;
    if ($departamento_id !== null && is_numeric($departamento_id) && $departamento_id > 0) {
        $where_clauses[] = 't."IdDepartamentoDestino" = :departamento_id';
        $params['departamento_id'] = (int)$departamento_id;
    }

    // Filtro opcional: Por prioridad
    $prioridad = isset($_GET['prioridad']) ? strtolower(trim($_GET['prioridad'])) : null;
    if ($prioridad !== null && in_array($prioridad, ['baja', 'media', 'alta'])) {
        $where_clauses[] = 't."Prioridad" = :prioridad';
        $params['prioridad'] = strtoupper($prioridad); // Asumimos que en BD es "Baja", "Media", "Alta"
    }

    // Consulta principal
    $sql = '
        SELECT 
            t."IdTickets",
            t."Titulo",
            t."Descripcion",
            t."Prioridad",
            t."EstadoTicket",
            t."FechaCreacion",
            t."IdDepartamentoDestino",
            h."FechaAccion" AS "fecha_resolucion",
            u."nombre" AS "usuario_nombre",
            u."Puesto" AS "usuario_puesto",
            u."FotoPerfil" AS "usuario_foto",
            resolutor."nombre" AS "resolutor_nombre"
        FROM "Historial" h
        JOIN "Tickets" t ON h."IdTicket" = t."IdTickets"
        JOIN "usuario" u ON t."IdUsuarioCreador" = u."IdUsuario"
        LEFT JOIN "usuario" resolutor ON h."IdUsuario" = resolutor."IdUsuario"
        WHERE ' . implode(' AND ', $where_clauses) . '
        AND h."FechaAccion" = (
            SELECT MAX(h2."FechaAccion")
            FROM "Historial" h2
            WHERE h2."IdTicket" = t."IdTickets"
            AND h2."Accion" = \'Resolver\'
        )
        ORDER BY h."FechaAccion" DESC, t."FechaCreacion" DESC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resolvedTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aseguramos que la respuesta sea un array (vacío si no hay resultados)
    echo json_encode(array_values($resolvedTickets));

} catch (PDOException $e) {
    error_log('Error en resueltos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los tickets resueltos: ' . $e->getMessage()]);
}
?>