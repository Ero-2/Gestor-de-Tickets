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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query('
            SELECT 
                "Tickets".*, 
                "usuario"."nombre" AS "usuario_nombre", 
                "usuario"."Puesto" AS "usuario_puesto",
                "usuario"."FotoPerfil" AS "usuario_foto",
                "Historial"."FechaAccion" AS "fecha_resolucion",
                "Historial"."IdUsuario" AS "id_usuario_resolutor"
            FROM "Historial"
            JOIN "Tickets" ON "Historial"."IdTicket" = "Tickets"."IdTickets"
            JOIN "usuario" ON "Tickets"."IdUsuarioCreador" = "usuario"."IdUsuario"
            WHERE "Historial"."Accion" = \'Resolver\'
            AND "Historial"."FechaAccion" = (
                SELECT MAX("FechaAccion")
                FROM "Historial" h2
                WHERE h2."IdTicket" = "Historial"."IdTicket"
                AND h2."Accion" = \'Resolver\'
            )
            ORDER BY "Historial"."FechaAccion" DESC
        ');
        $resolvedTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resolvedTickets);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener los tickets resueltos: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>