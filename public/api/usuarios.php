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
            $password = trim($data['password'] ?? '');
            $rol = trim($data['rol'] ?? 'estudiante');
            $estado = trim($data['estado'] ?? 'Activo');

            if (!$nombre || !$usuario || !$correo || !$password) {
                throw new Exception('Campos requeridos faltantes');
            }

            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = ? OR correo = ?');
            $stmtCheck->execute([$usuario, $correo]);
            if ($stmtCheck->fetchColumn() > 0) throw new Exception('El nombre de usuario o correo ya está en uso');

            try {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, correo, password_hash, rol, estado) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$nombre, $usuario, $correo, $hash, $rol, $estado]);
                
                if ($rol === 'estudiante') {
                    $codigo = trim($data['codigo'] ?? '');
                    $dni = trim($data['dni'] ?? '');
                    $programa_id = (int) ($data['programa_id'] ?? 0);
                    $periodo_curricular_id = (int) ($data['periodo_curricular_id'] ?? 0);
                    $unidad_didactica_id = (int) ($data['unidad_didactica_id'] ?? 0);
                    $seccion = trim($data['seccion'] ?? '');
                    
                    if (!$codigo || !$dni || !$programa_id || !$periodo_curricular_id || !$unidad_didactica_id || !$seccion) {
                        throw new Exception('Campos académicos incompletos para estudiante');
                    }
                    
                    $stmtEst = $pdo->prepare('INSERT INTO estudiantes (codigo, dni, nombres, programa_id, periodo_curricular_id, seccion, unidad_didactica_id, total_sesiones, inasistencias, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 20, 0, ?)');
                    $stmtEst->execute([$codigo, $dni, $nombre, $programa_id, $periodo_curricular_id, $seccion, $unidad_didactica_id, 'Activo']);
                } elseif ($rol === 'docente') {
                    $codigo = trim($data['codigo'] ?? '');
                    $dni = trim($data['dni'] ?? '');
                    $programa_id = (int) ($data['programa_id'] ?? 0);
                    $unidad_didactica_id = (int) ($data['unidad_didactica_id'] ?? 0);
                    $seccion = trim($data['seccion'] ?? '');
                    
                    if (!$codigo || !$dni || !$programa_id || !$unidad_didactica_id || !$seccion) {
                        throw new Exception('Campos académicos incompletos para docente');
                    }
                    
                    $stmtDoc = $pdo->prepare('INSERT INTO docentes (codigo, nombres, dni, correo, programa_id, unidad_didactica_id, seccion, usuario, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmtDoc->execute([$codigo, $nombre, $dni, $correo, $programa_id, $unidad_didactica_id, $seccion, $usuario, 'Activo']);
                }

                $pdo->commit();
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

            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE (usuario = ? OR correo = ?) AND id != ?');
            $stmtCheck->execute([$usuario, $correo, $id]);
            if ($stmtCheck->fetchColumn() > 0) throw new Exception('El nombre de usuario o correo ya está en uso por otro usuario');

            $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, usuario = ?, correo = ?, rol = ? WHERE id = ?');
            $stmt->execute([$nombre, $usuario, $correo, $rol, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        })(),

        'reset_password' => (function() use ($data, $pdo) {
            $id = (int) ($data['id'] ?? 0);
            if (!$id) throw new Exception('ID requerido');

            // Generar clave temporal (ej. 123456)
            $newPassword = 'password123';
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Contraseña restablecida a: ' . $newPassword]);
        })(),

        'toggle_status' => (function() use ($data, $pdo) {
            $id = (int) ($data['id'] ?? 0);
            $newStatus = trim($data['estado'] ?? 'Inactivo');
            
            if (!$id) throw new Exception('ID requerido');

            $stmt = $pdo->prepare('UPDATE usuarios SET estado = ? WHERE id = ?');
            $stmt->execute([$newStatus, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Estado del usuario actualizado']);
        })(),

        default => throw new Exception('Acción no válida')
    };
} catch (PDOException $e) {
    http_response_code(500);
    // 23000 es el código para restricción de unicidad (ej. correo duplicado)
    if ($e->getCode() == '23000') {
        echo json_encode(['error' => 'El usuario o correo ya existe en el sistema.']);
    } else {
        echo json_encode(['error' => 'Error de base de datos']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
