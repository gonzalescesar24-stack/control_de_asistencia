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
    echo json_encode(['error' => 'No tienes permisos para registrar asistencia.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if ($data) $data = sanitize_input($data);

if (!$data || empty($data['sesion_id']) || empty($data['asistencias'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
    exit;
}

$sesion_id = (int) $data['sesion_id'];
$asistencias = $data['asistencias'];
$pdo = db();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify session exists
    $sesion = fetch_one('SELECT id FROM sesiones WHERE id = ?', [$sesion_id]);
    if (!$sesion) {
        throw new Exception('La sesión indicada no existe.');
    }

    foreach ($asistencias as $asistencia) {
        $estudiante_id = (int) $asistencia['estudiante_id'];
        $estado = $asistencia['estado'];
        $observacion = $asistencia['observacion'] ?? null;
        
        // Fase 4: Auditoría y Bloqueo
        $registroPrevio = fetch_one('SELECT id, estado, created_at FROM asistencias WHERE estudiante_id = ? AND sesion_id = ?', [$estudiante_id, $sesion_id]);
        
        if ($registroPrevio) {
            if ($registroPrevio['estado'] !== $estado) {
                // Verificar bloqueo de 24 horas (86400 segundos)
                if (time() - strtotime($registroPrevio['created_at']) > 86400) {
                    throw new Exception("Bloqueo de seguridad: No se puede modificar la asistencia después de 24 horas (Estudiante ID: $estudiante_id).");
                }
                
                // Registrar auditoría antes de cambiar
                $stmtAudit = $pdo->prepare('INSERT INTO auditoria_asistencias (asistencia_id, estudiante_id, sesion_id, estado_anterior, estado_nuevo, modificado_por, motivo_cambio) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmtAudit->execute([
                    $registroPrevio['id'],
                    $estudiante_id,
                    $sesion_id,
                    $registroPrevio['estado'],
                    $estado,
                    $user['nombre'],
                    'Modificación manual'
                ]);

                // Update asistencia
                $stmtUpdate = $pdo->prepare('UPDATE asistencias SET estado = ?, observacion = ?, registrado_por = ? WHERE id = ?');
                $stmtUpdate->execute([$estado, $observacion, $user['nombre'], $registroPrevio['id']]);
            }
        } else {
            // Fase 3: Registro nuevo
            $stmtInsert = $pdo->prepare('INSERT INTO asistencias (estudiante_id, sesion_id, estado, observacion, registrado_por) VALUES (?, ?, ?, ?, ?)');
            $stmtInsert->execute([$estudiante_id, $sesion_id, $estado, $observacion, $user['nombre']]);
        }

        // Fase 3: Regla del 30%
        // Recalcular inasistencias totales para este estudiante
        $totalInasistencias = (int) fetch_one("SELECT COUNT(*) AS total FROM asistencias WHERE estudiante_id = ? AND estado = 'Inasistente'", [$estudiante_id])['total'];
        
        $estudianteInfo = fetch_one('SELECT total_sesiones FROM estudiantes WHERE id = ?', [$estudiante_id]);
        if ($estudianteInfo && $estudianteInfo['total_sesiones'] > 0) {
            $porcentaje = ($totalInasistencias / $estudianteInfo['total_sesiones']) * 100;
            
            $nuevoEstadoAcademico = 'Activo';
            if ($porcentaje >= 30) {
                $nuevoEstadoAcademico = 'Inhabilitado';
            } elseif ($porcentaje >= 20) {
                $nuevoEstadoAcademico = 'En riesgo';
            }

            // Actualizar tabla estudiantes con el nuevo recuento y estado
            $stmtUpdateEst = $pdo->prepare('UPDATE estudiantes SET inasistencias = ?, estado = ? WHERE id = ?');
            $stmtUpdateEst->execute([$totalInasistencias, $nuevoEstadoAcademico, $estudiante_id]);
        }
    }
    
    // Update session state to 'Registrada'
    $pdo->prepare("UPDATE sesiones SET estado = 'Registrada' WHERE id = ? AND estado = 'Pendiente'")->execute([$sesion_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Asistencia registrada exitosamente.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
