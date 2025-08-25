<?php
require_once 'mail_helper.php';  // Ajusta el path si es necesario

$to = 'tu_correo_destino@gmail.com';  // Reemplaza con un email de prueba (puede ser el mismo Gmail)
$subject = 'Prueba de Correo desde Gmail';
$body = generateResetEmailBody('https://ejemplo.com/reset');  // Usa un link de prueba

if (sendEmail($to, $subject, $body)) {
    echo "Éxito!";
} else {
    echo "Fallo.";
}
?>