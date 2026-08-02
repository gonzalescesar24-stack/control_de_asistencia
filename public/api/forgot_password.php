<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
require_once __DIR__ . '/../../app/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$correo = trim($_POST['correo'] ?? '');

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Por favor, ingrese un correo válido.']);
    exit;
}

try {
    $pdo = db();
    
    // Verificar si el correo existe
    $stmt = $pdo->prepare("SELECT id, nombre, usuario FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Siempre devolvemos éxito incluso si no existe, por seguridad (evitar enumeración de usuarios)
    if ($usuario) {
        // Generar token seguro
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardar token
        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $usuario['id']]);

        // URL de restablecimiento
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        
        $script_path = str_replace('/api/forgot_password.php', '', $_SERVER['SCRIPT_NAME']);
        $base = $protocol . $host . $script_path;
        $resetUrl = $base . "/reset_password.php?token=" . urlencode($token);
        $logoUrl = $base . "/assets/images/logo_vrht.png";

        // Preparar correo
        $correoDestino = $correo;
        $subject = "Recuperacion de contrasena - IES VRHT";
        
        $htmlMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 0; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
                .header { background-color: #1a3a6b; padding: 30px 20px; text-align: center; }
                .header img { max-height: 80px; }
                .content { padding: 40px 30px; color: #334155; line-height: 1.6; }
                .content h1 { color: #0f172a; font-size: 24px; margin-top: 0; margin-bottom: 20px; }
                .btn { display: inline-block; background-color: #3b82f6; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; margin: 20px 0; text-align: center; }
                .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='{$logoUrl}' alt='IES VRHT Logo'>
                </div>
                <div class='content'>
                    <h1>Recuperación de Contraseña</h1>
                    <p>Hola <strong>" . htmlspecialchars($usuario['nombre']) . "</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta de usuario (<strong>" . htmlspecialchars($usuario['usuario']) . "</strong>).</p>
                    <p>Si fuiste tú, por favor haz clic en el siguiente botón para crear una nueva contraseña:</p>
                    <div style='text-align: center;'>
                        <a href='{$resetUrl}' class='btn'>Restablecer Contraseña</a>
                    </div>
                    <p style='font-size: 14px; color: #64748b; margin-top: 30px;'>
                        Este enlace es seguro y expirará en 1 hora por motivos de seguridad.<br>
                        Si no solicitaste este cambio, simplemente ignora este correo. Tu cuenta sigue estando segura.
                    </p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " Instituto de Educación Superior VRHT. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>
        ";

        $textMessage = "Hola " . $usuario['nombre'] . ",\n\n";
        $textMessage .= "Hemos recibido una solicitud para restablecer la contraseña de tu cuenta (" . $usuario['usuario'] . ").\n\n";
        $textMessage .= "Para crear una nueva contraseña, ingresa al siguiente enlace:\n";
        $textMessage .= $resetUrl . "\n\n";
        $textMessage .= "Este enlace es válido por 1 hora.\n\n";
        $textMessage .= "Saludos,\nEl equipo de IES VRHT";

        // Enviar correo con PHPMailer
        require_once __DIR__ . '/../../app/libs/PHPMailer/Exception.php';
        require_once __DIR__ . '/../../app/libs/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../../app/libs/PHPMailer/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION === 'tls' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = MAIL_PORT;

            // Remitente y destinatario
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($correoDestino, $usuario['nombre']);
            $mail->CharSet = 'UTF-8';

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlMessage;
            $mail->AltBody = $textMessage;

            $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Error al enviar correo de recuperación: {$mail->ErrorInfo}");
            // Continuamos de todas formas, o podríamos mostrar error. Mejor loguearlo y simular éxito para evitar enumeración.
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error en el servidor.']);
}
