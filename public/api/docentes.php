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
            $codigo = trim($data['codigo'] ?? '');
            $dni = trim($data['dni'] ?? '');
            $nombres = trim($data['nombres'] ?? '');
            $correo = trim($data['correo'] ?? '');
            $programa_id = (int) ($data['programa_id'] ?? 0);
            $unidad_didactica_id = (int) ($data['unidad_didactica_id'] ?? 0);
            $seccion = trim($data['seccion'] ?? '');
            $usuario = trim($data['usuario'] ?? '');
            $estado = trim($data['estado'] ?? 'Activo');

            if (!$codigo || !$nombres) {
                throw new Exception('Campos requeridos faltantes');
            }

            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM docentes WHERE codigo = ? OR dni = ?');
            $stmtCheck->execute([$codigo, $dni]);
            if ($stmtCheck->fetchColumn() > 0) throw new Exception('El código o DNI ya está en uso por otro docente');

            if ($usuario) {
                $stmtU = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
                $stmtU->execute([$usuario]);
                if (!$stmtU->fetch()) {
                    $hash = password_hash($dni ?: $usuario, PASSWORD_DEFAULT);
                    $stmtIns = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, correo, password_hash, rol, estado) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmtIns->execute([$nombres, $usuario, $correo, $hash, 'docente', 'Activo']);
                }
            }

            $stmt = $pdo->prepare('INSERT INTO docentes (codigo, nombres, dni, correo, programa_id, unidad_didactica_id, seccion, usuario, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$codigo, $nombres, $dni, $correo, $programa_id, $unidad_didactica_id, $seccion, $usuario, $estado]);
            
            echo json_encode(['success' => true, 'message' => 'Docente creado exitosamente']);
        })(),

        'update' => (function() use ($data, $pdo) {
            $id = (int) ($data['id'] ?? 0);
            $codigo = trim($data['codigo'] ?? '');
            $dni = trim($data['dni'] ?? '');
            $nombres = trim($data['nombres'] ?? '');
            $correo = trim($data['correo'] ?? '');
            $programa_id = (int) ($data['programa_id'] ?? 0);
            $unidad_didactica_id = (int) ($data['unidad_didactica_id'] ?? 0);
            $seccion = trim($data['seccion'] ?? '');
            $usuario = trim($data['usuario'] ?? '');

            if (!$id || !$codigo || !$nombres) {
                throw new Exception('Campos requeridos faltantes');
            }

            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM docentes WHERE (codigo = ? OR dni = ?) AND id != ?');
            $stmtCheck->execute([$codigo, $dni, $id]);
            if ($stmtCheck->fetchColumn() > 0) throw new Exception('El código o DNI ya está en uso por otro docente');

            if ($usuario) {
                $stmtU = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
                $stmtU->execute([$usuario]);
                if (!$stmtU->fetch()) {
                    $hash = password_hash($dni ?: $usuario, PASSWORD_DEFAULT);
                    $stmtIns = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, correo, password_hash, rol, estado) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmtIns->execute([$nombres, $usuario, $correo, $hash, 'docente', 'Activo']);
                }
            }

            $stmt = $pdo->prepare('UPDATE docentes SET codigo = ?, nombres = ?, dni = ?, correo = ?, programa_id = ?, unidad_didactica_id = ?, seccion = ?, usuario = ? WHERE id = ?');
            $stmt->execute([$codigo, $nombres, $dni, $correo, $programa_id, $unidad_didactica_id, $seccion, $usuario, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Docente actualizado exitosamente']);
        })(),

        'toggle_status' => (function() use ($data, $pdo) {
            $id = (int) ($data['id'] ?? 0);
            $newStatus = trim($data['estado'] ?? 'Inactivo');
            
            if (!$id) throw new Exception('ID requerido');

            $stmt = $pdo->prepare('UPDATE docentes SET estado = ? WHERE id = ?');
            $stmt->execute([$newStatus, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Estado del docente actualizado']);
        })(),

        default => throw new Exception('Acción no válida')
    };
} catch (PDOException $e) {
    http_response_code(500);
    if ($e->getCode() == '23000') {
        echo json_encode(['error' => 'El código, DNI o usuario ya está en uso.']);
    } else {
        echo json_encode(['error' => 'Error de base de datos']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
