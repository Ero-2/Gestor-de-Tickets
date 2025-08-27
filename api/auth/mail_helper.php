<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Desactiva el debugging en pantalla para producción (usa logs en su lugar)
        $mail->SMTPDebug = 3; // Mantén el nivel, pero redirige la salida
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug [$level]: $str"); // Solo logs, sin echo
        };

        // Configuración SMTP para Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'erickgtickets@gmail.com';
        $mail->Password   = 'haor fwbo inle hzuv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Opciones SSL para depuración local (activa verificación en producción)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Remitente y destinatario
        $mail->setFrom('erickgtickets@gmail.com', 'Soporte Tickets');
        $mail->addAddress($to);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->CharSet = 'UTF-8';

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo); // Solo log, sin echo
        return false;
    }
}

function generateResetEmailBody($resetLink) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #0078d4;'>Recuperación de Contraseña</h2>
            <p>Hola,</p>
            <p>Has solicitado restablecer tu contraseña. Haz clic en el botón de abajo para crear una nueva contraseña:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='$resetLink' 
                   style='background-color: #0078d4; color: white; padding: 12px 24px; 
                          text-decoration: none; border-radius: 4px; display: inline-block;
                          font-weight: bold;'>
                    Restablecer Contraseña
                </a>
            </div>
            <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
            <p>Este enlace expirará en 1 hora.</p>
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
            <p style='font-size: 12px; color: #666;'>
                Este es un correo automático, por favor no respondas a este mensaje.
            </p>
        </div>
    </body>
    </html>
    ";
}