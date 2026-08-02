<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

require_login();
verify_csrf_token();

header('Content-Type: application/json; charset=utf-8');

$user = app_user();
if ($user['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if ($data) $data = sanitize_input($data);

if (!$data || empty($data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Acción no especificada']);
    exit;
}

$pdo = db();
$action = $data['action'];

try {
    match ($action) {
        'create' => (function() use ($data, $pdo) {
            $nombre = trim($data['nombre'] ?? '');
            $usuario = trim($data['usuario'] ?? '');
            $correo = trim($data['correo'] ?? '');
            $rol = trim($data['rol'] ?? 'estudiante');
            $estado = trim($data['estado'] ?? 'Activo');
            $dni = trim($data['dni'] ?? '');

            if (!$nombre || !$usuario || !$correo || !$dni) {
                throw new Exception('Campos requeridos faltantes (Nombres, Usuario, Correo, DNI son obligatorios)');
            }
            if (!preg_match('/^\d{8}$/', $dni)) {
                throw new Exception('El DNI debe contener exactamente 8 números.');
            }
            if ($rol === 'admin') {
                throw new Exception('No está permitido crear usuarios con rol administrador');
            }

            $password = $dni; // La contraseña inicial es el DNI

            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = ? OR correo = ?');
            $stmtCheck->execute([$usuario, $correo]);
            if ($stmtCheck->fetchColumn() > 0) throw new Exception('El nombre de usuario o correo ya está en uso en el sistema');

            $stmtCheckDniEst = $pdo->prepare('SELECT COUNT(*) FROM estudiantes WHERE dni = ?');
            $stmtCheckDniEst->execute([$dni]);
            if ($stmtCheckDniEst->fetchColumn() > 0) throw new Exception('El DNI ya se encuentra registrado para un estudiante');

            $stmtCheckDniDoc = $pdo->prepare('SELECT COUNT(*) FROM docentes WHERE dni = ?');
            $stmtCheckDniDoc->execute([$dni]);
            if ($stmtCheckDniDoc->fetchColumn() > 0) throw new Exception('El DNI ya se encuentra registrado para un docente');

            $codigo = trim($data['codigo'] ?? '');
            if ($rol !== 'admin' && $codigo) {
                $stmtCheckCodEst = $pdo->prepare('SELECT COUNT(*) FROM estudiantes WHERE codigo = ?');
                $stmtCheckCodEst->execute([$codigo]);
                if ($stmtCheckCodEst->fetchColumn() > 0) throw new Exception('El código institucional ya se encuentra registrado para un estudiante');

                $stmtCheckCodDoc = $pdo->prepare('SELECT COUNT(*) FROM docentes WHERE codigo = ?');
                $stmtCheckCodDoc->execute([$codigo]);
                if ($stmtCheckCodDoc->fetchColumn() > 0) throw new Exception('El código institucional ya se encuentra registrado para un docente');
            }

            try {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                // must_change_password = 1 al crear
                $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, correo, password_hash, rol, estado, must_change_password) VALUES (?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$nombre, $usuario, $correo, $hash, $rol, $estado]);
                
                if ($rol === 'estudiante') {
                    $codigo = trim($data['codigo'] ?? '');
                    $programa_id = (int) ($data['programa_id'] ?? 0);
                    $periodo_curricular_id = (int) ($data['periodo_curricular_id'] ?? 0);
                    $unidad_didactica_id = (int) ($data['unidad_didactica_id'] ?? 0);
                    $seccion = trim($data['seccion'] ?? '');
                    
                    if (!$codigo || !$programa_id || !$periodo_curricular_id || !$unidad_didactica_id || !$seccion) {
                        throw new Exception('Campos académicos incompletos para estudiante');
                    }
                    
                    $stmtEst = $pdo->prepare('INSERT INTO estudiantes (codigo, dni, nombres, programa_id, periodo_curricular_id, seccion, unidad_didactica_id, total_sesiones, inasistencias, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 20, 0, ?)');
                    $stmtEst->execute([$codigo, $dni, $nombre, $programa_id, $periodo_curricular_id, $seccion, $unidad_didactica_id, 'Activo']);
                } elseif ($rol === 'docente') {
                    $codigo = trim($data['codigo'] ?? '');
                    $programa_id = (int) ($data['programa_id'] ?? 0);
                    $unidad_didactica_id = (int) ($data['unidad_didactica_id'] ?? 0);
                    $seccion = trim($data['seccion'] ?? '');
                    
                    if (!$codigo || !$programa_id || !$unidad_didactica_id || !$seccion) {
                        throw new Exception('Campos académicos incompletos para docente');
                    }
                    
                    $stmtDoc = $pdo->prepare('INSERT INTO docentes (codigo, nombres, dni, correo, programa_id, unidad_didactica_id, seccion, usuario, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmtDoc->execute([$codigo, $nombre, $dni, $correo, $programa_id, $unidad_didactica_id, $seccion, $usuario, 'Activo']);
                }

                // Enviar correo de bienvenida
                require_once __DIR__ . '/../../app/libs/PHPMailer/Exception.php';
                require_once __DIR__ . '/../../app/libs/PHPMailer/PHPMailer.php';
                require_once __DIR__ . '/../../app/libs/PHPMailer/SMTP.php';

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = MAIL_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = MAIL_USERNAME;
                    $mail->Password   = MAIL_PASSWORD;
                    $mail->SMTPSecure = MAIL_ENCRYPTION === 'tls' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = MAIL_PORT;

                    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
                    $mail->addAddress($correo, $nombre);

                    $hostUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $hostUrl .= $_SERVER['HTTP_HOST'] ?? 'localhost';
                    
                    $script_path = str_replace('/api/usuarios.php', '', $_SERVER['SCRIPT_NAME']);
                    $base = $hostUrl . $script_path;
                    $loginUrl = $base . "/login.php";
                    $logoUrl = $base . "/assets/images/logo_vrht.png";

                    $mail->CharSet = 'UTF-8';
                    $mail->isHTML(true);
                    $mail->Subject = 'Bienvenido al Sistema de Control de Asistencia - IES VRHT';
                    
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
                            .content h1 { color: #0f172a; font-size: 24px; margin-top: 0; margin-bottom: 20px; text-align: center; }
                            .credentials { background-color: #f1f5f9; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 25px 0; }
                            .credentials p { margin: 5px 0; font-size: 16px; }
                            .btn { display: inline-block; background-color: #3b82f6; color: #ffffff !important; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: bold; margin: 20px 0; text-align: center; }
                            .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <img src='{$logoUrl}' alt='IES VRHT Logo'>
                            </div>
                            <div class='content'>
                                <h1>¡Bienvenido/a al Sistema!</h1>
                                <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                                <p>Se ha creado tu cuenta en el Sistema de Control de Asistencia de nuestra institución con éxito.</p>
                                
                                <div class='credentials'>
                                    <p>Tus credenciales de acceso son:</p>
                                    <p><strong>Usuario:</strong> " . htmlspecialchars($usuario) . "</p>
                                    <p><strong>Contraseña:</strong> " . htmlspecialchars($password) . "</p>
                                </div>
                                
                                <div style='text-align: center;'>
                                    <a href='{$loginUrl}' class='btn'>Iniciar Sesión</a>
                                </div>
                                
                                <p style='font-size: 14px; color: #64748b; margin-top: 30px; text-align: center;'>
                                    <em>Nota importante: Por seguridad, el sistema te pedirá cambiar tu contraseña la primera vez que inicies sesión.</em>
                                </p>
                            </div>
                            <div class='footer'>
                                &copy; " . date('Y') . " Instituto de Educación Superior VRHT. Todos los derechos reservados.
                            </div>
                        </div>
                    </body>
                    </html>
                    ";

                    $textMessage = "Hola " . $nombre . ",\n\n";
                    $textMessage .= "Se ha creado tu cuenta en el Sistema de Control de Asistencia.\n\n";
                    $textMessage .= "Tus credenciales de acceso son:\n";
                    $textMessage .= "Usuario: " . $usuario . "\n";
                    $textMessage .= "Contraseña: " . $password . "\n\n";
                    $textMessage .= "Por seguridad, el sistema te pedirá cambiar tu contraseña la primera vez que inicies sesión.\n\n";
                    $textMessage .= "Puedes iniciar sesión aquí: " . $loginUrl . "\n\n";
                    $textMessage .= "Saludos,\n" . MAIL_FROM_NAME;

                    $mail->Body = $htmlMessage;
                    $mail->AltBody = $textMessage;
                    $mail->send();
                } catch (\Exception $e) {
                    error_log("Error al enviar correo de bienvenida: " . $e->getMessage());
                }

                $pdo->commit();
                log_audit($pdo, 'Usuarios', 'CREAR', "Creado usuario con rol {$rol}: {$usuario} ({$correo})");
                echo json_encode(['success' => true, 'message' => 'Usuario y perfil académico creados exitosamente']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        })(),

        'update' => (function() use ($data, $pdo) {
            $id = (int) ($data['id'] ?? 0);
            $nombre = trim($data['nombre'] ?? '');
            $usuario = trim($data['usuario'] ?? '');
            $correo = trim($data['correo'] ?? '');
            $rol = trim($data['rol'] ?? '');

            if (!$id || !$nombre || !$usuario || !$correo || !$rol) {
                throw new Exception('Campos requeridos faltantes');
            }
            
            $stmtTarget = $pdo->prepare('SELECT rol FROM usuarios WHERE id = ?');
            $stmtTarget->execute([$id]);
            $targetRol = $stmtTarget->fetchColumn();
            
            if ($targetRol === 'admin') {
                throw new Exception('No está permitido editar a un administrador, ni a sí mismo.');
            }
            if ($rol === 'admin') {
                throw new Exception('No está permitido asignar el rol de administrador.');
            }

            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE (usuario = ? OR correo = ?) AND id != ?');
            $stmtCheck->execute([$usuario, $correo, $id]);
            if ($stmtCheck->fetchColumn() > 0) throw new Exception('El nombre de usuario o correo ya está en uso por otro usuario');

            $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, usuario = ?, correo = ?, rol = ? WHERE id = ?');
            $stmt->execute([$nombre, $usuario, $correo, $rol, $id]);
            
            log_audit($pdo, 'Usuarios', 'MODIFICAR', "Actualizado usuario ID {$id}: {$usuario}");
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        })(),

        'reset_password' => (function() use ($data, $pdo) {
            $id = (int) ($data['id'] ?? 0);
            if (!$id) throw new Exception('ID requerido');

            $stmtTarget = $pdo->prepare('SELECT rol FROM usuarios WHERE id = ?');
            $stmtTarget->execute([$id]);
            if ($stmtTarget->fetchColumn() === 'admin') {
                throw new Exception('No está permitido modificar a un administrador, ni a sí mismo.');
            }

            // Generar clave temporal (ej. 123456)
            $newPassword = 'password123';
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $id]);
            
            log_audit($pdo, 'Usuarios', 'MODIFICAR', "Contraseña restablecida para usuario ID {$id}");
            echo json_encode(['success' => true, 'message' => 'Contraseña restablecida a: ' . $newPassword]);
        })(),

        'toggle_status' => (function() use ($data, $pdo) {
            $id = (int) ($data['id'] ?? 0);
            $newStatus = trim($data['estado'] ?? 'Inactivo');
            
            if (!$id) throw new Exception('ID requerido');

            $stmtTarget = $pdo->prepare('SELECT rol FROM usuarios WHERE id = ?');
            $stmtTarget->execute([$id]);
            if ($stmtTarget->fetchColumn() === 'admin') {
                throw new Exception('No está permitido modificar a un administrador, ni a sí mismo.');
            }

            $stmt = $pdo->prepare('UPDATE usuarios SET estado = ? WHERE id = ?');
            $stmt->execute([$newStatus, $id]);
            
            log_audit($pdo, 'Usuarios', 'MODIFICAR', "Estado cambiado a {$newStatus} para usuario ID {$id}");
            echo json_encode(['success' => true, 'message' => 'Estado del usuario actualizado']);
        })(),

        default => throw new Exception('Acción no válida')
    };
} catch (PDOException $e) {
    http_response_code(500);
    // 23000 es el código para restricción de unicidad
    if ($e->getCode() == '23000') {
        echo json_encode(['error' => 'Registro duplicado: El usuario, correo o código ya existe en el sistema.']);
    } else {
        echo json_encode(['error' => 'Error de BD: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
