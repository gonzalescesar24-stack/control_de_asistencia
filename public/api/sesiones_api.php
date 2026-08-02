<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

require_login();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'sesiones';

if ($action === 'sesiones') {
    // Get sessions filtered by programa/unidad/seccion
    $sql = "SELECT s.id, s.fecha, s.hora, s.periodo, s.seccion, s.estado,
                   p.nombre as programa, ud.nombre as unidad, pc.nombre as ciclo,
                   d.nombres as docente
            FROM sesiones s
            LEFT JOIN programas p ON s.programa_id = p.id
            LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id
            LEFT JOIN periodos_curriculares pc ON ud.periodo_curricular_id = pc.id
            LEFT JOIN docentes d ON s.docente_id = d.id
            WHERE 1=1";
    $params = [];

    if (!empty($_GET['programa_id'])) {
        $sql .= ' AND s.programa_id = ?';
        $params[] = (int)$_GET['programa_id'];
    }
    if (!empty($_GET['unidad_id'])) {
        $sql .= ' AND s.unidad_didactica_id = ?';
        $params[] = (int)$_GET['unidad_id'];
    }
    if (!empty($_GET['seccion'])) {
        $sql .= ' AND s.seccion = ?';
        $params[] = $_GET['seccion'];
    }
    if (!empty($_GET['periodo'])) {
        $sql .= ' AND s.periodo = ?';
        $params[] = $_GET['periodo'];
    }
    if (!empty($_GET['docente_id'])) {
        $sql .= ' AND s.docente_id = ?';
        $params[] = (int)$_GET['docente_id'];
    }

    $sql .= ' ORDER BY s.fecha DESC, s.hora DESC LIMIT 50';

    $sesiones = fetch_all($sql, $params);
    echo json_encode(['success' => true, 'sesiones' => $sesiones]);

} elseif ($action === 'estudiantes') {
    // Get students for a specific session
    $sesion_id = (int)($_GET['sesion_id'] ?? 0);
    if (!$sesion_id) {
        echo json_encode(['error' => 'sesion_id requerido']);
        exit;
    }

    // Get session details
    $sesion = fetch_one(
        "SELECT s.*, p.nombre as programa, ud.nombre as unidad, pc.nombre as ciclo
         FROM sesiones s
         LEFT JOIN programas p ON s.programa_id = p.id
         LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id
         LEFT JOIN periodos_curriculares pc ON ud.periodo_curricular_id = pc.id
         WHERE s.id = ?",
        [$sesion_id]
    );

    if (!$sesion) {
        echo json_encode(['error' => 'Sesión no encontrada']);
        exit;
    }

    // Get students enrolled in this session's program/unidad/seccion
    $estudiantes = fetch_all(
        "SELECT e.id, e.nombres, e.codigo, e.dni,
                COALESCE(a.estado, 'Presente') as asistencia_estado,
                a.observacion
         FROM estudiantes e
         LEFT JOIN asistencias a ON a.estudiante_id = e.id AND a.sesion_id = ?
         WHERE e.programa_id = ? AND e.unidad_didactica_id = ? AND e.seccion = ?
         ORDER BY e.nombres",
        [$sesion_id, $sesion['programa_id'], $sesion['unidad_didactica_id'], $sesion['seccion']]
    );

    echo json_encode([
        'success' => true,
        'sesion' => $sesion,
        'estudiantes' => $estudiantes
    ]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida']);
}
