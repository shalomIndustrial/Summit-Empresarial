<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

/**
 * Envía el boleto digital por correo electrónico usando PHPMailer y Gmail SMTP
 * 
 * @param string $destinatario Correo del asistente
 * @param string $nombre_completo Nombre completo del asistente
 * @param string $codigo_boleto Código único SUMMIT-XXXXXXXX
 * @param float  $precio Monto pagado
 * @param string $url_boleto URL directa para ver el boleto digital online
 * @return array ['success' => bool, 'message' => string]
 */
function enviarBoletoPorCorreo($destinatario, $nombre_completo, $codigo_boleto, $precio, $url_boleto) {
    // Si no hay credenciales SMTP configuradas, registramos aviso y no bloqueamos el flujo
    $smtp_user = getenv("SMTP_USER") ?: "";
    $smtp_pass = getenv("SMTP_PASS") ?: "";
    $smtp_host = getenv("SMTP_HOST") ?: "smtp.gmail.com";
    $smtp_port = getenv("SMTP_PORT") ? (int)getenv("SMTP_PORT") : 587;
    $from_name = getenv("SMTP_FROM_NAME") ?: "Summit Empresarial México";

    if (empty($smtp_user) || empty($smtp_pass)) {
        error_log("Aviso: SMTP_USER o SMTP_PASS no están configurados en .env. El correo no fue enviado.");
        return [
            'success' => false,
            'message' => 'Credenciales SMTP pendientes de configurar en .env'
        ];
    }

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->CharSet    = 'UTF-8';

        if ($smtp_port == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port       = $smtp_port;

        // Remitente y Destinatario
        $mail->setFrom($smtp_user, $from_name);
        $mail->addAddress($destinatario, $nombre_completo);
        $mail->addReplyTo($smtp_user, $from_name);

        // Generar URL del código QR para el correo
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&color=0A1931&data=" . urlencode($url_boleto);

        // Contenido del Correo en HTML
        $mail->isHTML(true);
        $mail->Subject = "🎟️ Tu Boleto Digital para Summit Empresarial México (" . $codigo_boleto . ")";

        $mail->Body = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; color: #1f2937; }
                .container { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; }
                .header { background-color: #0A1931; color: #ffffff; padding: 30px 20px; text-align: center; border-bottom: 4px solid #C5A059; }
                .header h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 1px; }
                .header p { margin: 6px 0 0 0; color: #C5A059; font-size: 13px; text-transform: uppercase; font-weight: bold; letter-spacing: 2px; }
                .content { padding: 30px 25px; }
                .badge { display: inline-block; background-color: #ecfdf5; color: #047857; font-weight: bold; font-size: 12px; padding: 4px 12px; border-radius: 20px; }
                .details { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin: 20px 0; }
                .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
                .row:last-child { margin-bottom: 0; }
                .label { color: #6b7280; font-size: 12px; text-transform: uppercase; font-weight: bold; }
                .value { font-weight: bold; color: #111827; }
                .qr-box { text-align: center; margin: 25px 0 15px 0; }
                .qr-box img { border: 2px solid #C5A059; padding: 10px; border-radius: 12px; background: #ffffff; }
                .ticket-code { font-family: monospace; font-size: 18px; font-weight: bold; color: #0A1931; letter-spacing: 2px; margin-top: 10px; }
                .btn-container { text-align: center; margin: 30px 0 10px 0; }
                .btn { display: inline-block; background-color: #C5A059; color: #0A1931 !important; text-decoration: none; font-weight: bold; padding: 14px 28px; border-radius: 10px; font-size: 15px; box-shadow: 0 4px 10px rgba(197, 160, 89, 0.3); }
                .footer { background-color: #050C1A; color: #9ca3af; text-align: center; padding: 20px; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>SUMMIT EMPRESARIAL</h1>
                    <p>México · Acceso Oficial Confirmado</p>
                </div>
                <div class="content">
                    <p style="font-size: 16px;">¡Hola, <strong>' . htmlspecialchars($nombre_completo) . '</strong>!</p>
                    <p style="color: #4b5563; font-size: 14px; line-height: 1.6;">
                        Tu registro para el <strong>Summit Empresarial México</strong> ha sido completado con éxito. A continuación te compartimos los detalles de tu acceso:
                    </p>

                    <div class="details">
                        <div style="margin-bottom: 12px;">
                            <span class="label">Asistente</span>
                            <div class="value" style="font-size: 16px;">' . htmlspecialchars($nombre_completo) . '</div>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <span class="label">Pase</span>
                            <div class="value">Acceso Completo &nbsp; <span class="badge">PAGADO</span></div>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <span class="label">Total</span>
                            <div class="value">$' . number_format($precio, 2) . ' MXN</div>
                        </div>
                    </div>

                    <div class="qr-box">
                        <img src="' . htmlspecialchars($qr_url) . '" alt="Código QR de Acceso" width="180" height="180">
                        <div class="ticket-code">' . htmlspecialchars($codigo_boleto) . '</div>
                        <p style="color: #6b7280; font-size: 12px; margin-top: 6px;">
                            Presenta este código QR en tu teléfono para acceder al evento.
                        </p>
                    </div>

                    <div class="btn-container">
                        <a href="' . htmlspecialchars($url_boleto) . '" class="btn">
                            Ver y Descargar Boleto Digital
                        </a>
                    </div>
                </div>
                <div class="footer">
                    <p style="margin: 0;">© 2026 Summit Empresarial México. Todos los derechos reservados.</p>
                    <p style="margin: 6px 0 0 0; color: #6b7280;">Si tienes alguna pregunta, responde directamente a este correo.</p>
                </div>
            </div>
        </body>
        </html>
        ';

        // Versión en texto plano para clientes sin soporte HTML
        $mail->AltBody = "¡Hola, $nombre_completo!\n\nTu boleto para Summit Empresarial México está confirmado.\nCódigo: $codigo_boleto\nPase: Acceso Completo\nPrecio: $" . number_format($precio, 2) . " MXN\n\nPuedes ver y descargar tu boleto digital aquí:\n$url_boleto\n\n¡Nos vemos en el evento!";

        $mail->send();
        return [
            'success' => true,
            'message' => 'Correo enviado exitosamente a ' . $destinatario
        ];
    } catch (Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return [
            'success' => false,
            'message' => 'Error al enviar correo: ' . $mail->ErrorInfo
        ];
    }
}
?>
