<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // ✅ ACTIVAR DEBUGGING COMPLETO (desactívalo en producción con =0)
        $mail->SMTPDebug = 3; // Nivel máximo de detalle
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug [$level]: $str");
            echo "DEBUG: $str<br>"; // Mostrar en pantalla también
        };

        // Configuración SMTP para Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Cambiado a Gmail
        $mail->SMTPAuth   = true;
        $mail->Username   = 'erickgtickets@gmail.com';  // Tu correo Gmail
        $mail->Password   = 'haor fwbo inle hzuv';  // App Password (sin espacios)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // 🛡️ Opciones SSL para depuración local (activa verificación en producción)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Remitente y destinatario
        $mail->setFrom('erickgtickets@gmail.com', 'Soporte Tickets');  // Cambiado a Gmail
        $mail->addAddress($to);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->CharSet = 'UTF-8';

        $mail->send();
        echo "✅ CORREO ENVIADO CORRECTAMENTE<br>";
        return true;
        
    } catch (Exception $e) {
        echo "❌ ERROR COMPLETO: " . $e->getMessage() . "<br>";
        echo "📧 ERROR DE PHPMAILER: " . $mail->ErrorInfo . "<br>";
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
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