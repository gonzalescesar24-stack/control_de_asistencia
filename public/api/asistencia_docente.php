<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

require_login();
verify_csrf_token();

header('Content-Type: application/json; charset=utf-8');

$user = app_user();
if ($user['rol'] !== 'docente') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Solo docentes pueden registrar su asistencia.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if ($data) $data = sanitize_input($data);

if (!$data || empty($data['sesion_id']) || empty($data['estado'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos o incompletos.']);
    exit;
}

$sesion_id = (int) $data['sesion_id'];
$estado = trim($data['estado']);
$docente_id = (int) $user['id'];

// Validar estado
if (!in_array($estado, ['Presente', 'Tardanza', 'Inasistencia', 'Inasistencia Justificada'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Estado de asistencia inválido.']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // Verificar que la sesión existe y pertenece al docente
    $sesion = fetch_one('SELECT id FROM sesiones WHERE id = ? AND docente_id = ?', [$sesion_id, $docente_id]);
    if (!$sesion) {
        throw new Exception('La sesión indicada no existe o no te pertenece.');
    }

    // Verificar si ya existe registro
    $registroPrevio = fetch_one('SELECT id FROM asistencia_docentes WHERE docente_id = ? AND sesion_id = ?', [$docente_id, $sesion_id]);

    if ($registroPrevio) {
        throw new Exception('Ya has registrado tu asistencia para esta sesión.');
    }

    // Registrar asistencia (CURRENT_TIME se maneja implícitamente o pasamos date)
    $hora_ingreso = date('H:i:s');
    
    $stmt = $pdo->prepare('INSERT INTO asistencia_docentes (docente_id, sesion_id, hora_ingreso, estado) VALUES (?, ?, ?, ?)');
    $stmt->execute([$docente_id, $sesion_id, $hora_ingreso, $estado]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Asistencia registrada correctamente.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
