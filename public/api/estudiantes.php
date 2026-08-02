<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

require_login();
verify_csrf_token();

header('Content-Type: application/json; charset=utf-8');

$user = app_user();
if (!in_array($user['rol'], ['admin', 'docente'])) {
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
        'create' => (function() use ($data, $pdo, $user) {
            if ($user['rol'] !== 'admin') throw new Exception('Solo admin puede crear');

            $codigo = trim($data['codigo'] ?? '');
            $nombres = trim($data['nombres'] ?? '');
            $programa_id = (int) ($data['programa_id'] ?? 0);
            $periodo_curricular_id = (int) ($data['periodo_curricular_id'] ?? 0);
            $seccion = trim($data['seccion'] ?? '');
            $unidad_didactica_id = (int) ($data['unidad_didactica_id'] ?? 0);
            $estado = trim($data['estado'] ?? 'Activo');

            if (!$codigo || !$nombres || !$programa_id) {
                throw new Exception('Campos requeridos faltantes');
            }

            $stmtCheckEst = $pdo->prepare('SELECT COUNT(*) FROM estudiantes WHERE codigo = ?');
            $stmtCheckEst->execute([$codigo]);
            if ($stmtCheckEst->fetchColumn() > 0) throw new Exception('El código institucional ya se encuentra registrado para un estudiante');

            $stmtCheckDoc = $pdo->prepare('SELECT COUNT(*) FROM docentes WHERE codigo = ?');
            $stmtCheckDoc->execute([$codigo]);
            if ($stmtCheckDoc->fetchColumn() > 0) throw new Exception('El código institucional ya se encuentra registrado para un docente');

            $stmt = $pdo->prepare('INSERT INTO estudiantes (codigo, nombres, programa_id, periodo_curricular_id, seccion, unidad_didactica_id, total_sesiones, inasistencias, estado) VALUES (?, ?, ?, ?, ?, ?, 20, 0, ?)');
            $stmt->execute([$codigo, $nombres, $programa_id, $periodo_curricular_id, $seccion, $unidad_didactica_id, $estado]);
            
            log_audit($pdo, 'Estudiantes', 'CREAR', "Registrado estudiante: {$nombres} ({$codigo})");
            echo json_encode(['success' => true, 'message' => 'Estudiante creado exitosamente']);
        })(),

        'update' => (function() use ($data, $pdo, $user) {
            if ($user['rol'] !== 'admin') throw new Exception('Solo admin puede editar');

            $id = (int) ($data['id'] ?? 0);
            $codigo = trim($data['codigo'] ?? '');
            $nombres = trim($data['nombres'] ?? '');
            $programa_id = (int) ($data['programa_id'] ?? 0);
            $periodo_curricular_id = (int) ($data['periodo_curricular_id'] ?? 0);
            $seccion = trim($data['seccion'] ?? '');
            $unidad_didactica_id = (int) ($data['unidad_didactica_id'] ?? 0);
            $estado = trim($data['estado'] ?? 'Activo');

            if (!$id || !$codigo || !$nombres || !$programa_id) {
                throw new Exception('Campos requeridos faltantes');
            }

            $stmtCheckEst = $pdo->prepare('SELECT COUNT(*) FROM estudiantes WHERE codigo = ? AND id != ?');
            $stmtCheckEst->execute([$codigo, $id]);
            if ($stmtCheckEst->fetchColumn() > 0) throw new Exception('El código institucional ya se encuentra registrado para otro estudiante');

            $stmtCheckDoc = $pdo->prepare('SELECT COUNT(*) FROM docentes WHERE codigo = ?');
            $stmtCheckDoc->execute([$codigo]);
            if ($stmtCheckDoc->fetchColumn() > 0) throw new Exception('El código institucional ya se encuentra registrado para un docente');

            $stmt = $pdo->prepare('UPDATE estudiantes SET codigo = ?, nombres = ?, programa_id = ?, periodo_curricular_id = ?, seccion = ?, unidad_didactica_id = ?, estado = ? WHERE id = ?');
            $stmt->execute([$codigo, $nombres, $programa_id, $periodo_curricular_id, $seccion, $unidad_didactica_id, $estado, $id]);
            
            log_audit($pdo, 'Estudiantes', 'MODIFICAR', "Actualizado estudiante ID {$id}: {$nombres}");
            echo json_encode(['success' => true, 'message' => 'Estudiante actualizado exitosamente']);
        })(),

        default => throw new Exception('Acción no válida')
    };
} catch (PDOException $e) {
    http_response_code(500);
    if ($e->getCode() == '23000') {
        echo json_encode(['error' => 'El código de estudiante ya existe.']);
    } else {
        echo json_encode(['error' => 'Error de base de datos']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
