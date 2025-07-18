<?php
$host = 'localhost';
$port = '5432';
$dbname = 'Tickets';      // ← esta es tu base de datos
$user = 'postgres';       // ← este es tu usuario
$password = '1234'; // ← pon tu contraseña real de postgres aquí

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión exitosa a la base de datos Tickets.";
} catch (PDOException $e) {
    echo "❌ Error en la conexión: " . $e->getMessage();
}
?>
