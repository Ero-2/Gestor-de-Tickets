<?php
// config.php

// --- Configuración de la Base de Datos ---
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'Tickets');
define('DB_USER', 'postgres');
define('DB_PASS', '1234');

// --- Configuración de JWT ---
// 🔐 PEGA TU CLAVE SECRETA GENERADA AQUÍ
define('JWT_SECRET_KEY', '5ec6f76ed54a65f9cb7a1c63db29d3b402ca01f2ab5554194066131dbf649aa9'); 

define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME', 60 * 60 * 24);

// ... (resto del archivo)
?>