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
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
    exit;
}

$action = $input['action'] ?? '';
$pdo = db();

if ($action === 'create') {
    $stmt = $pdo->prepare('INSERT INTO horarios (programa_id, unidad_didactica_id, docente_id, seccion, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?, ?)');
    try {
        $stmt->execute([
            $input['programa_id'],
            $input['unidad_didactica_id'],
            $input['docente_id'],
            $input['seccion'],
            $input['dia_semana'],
            $input['hora_inicio'],
            $input['hora_fin']
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar en base de datos.']);
    }
    exit;
}

if ($action === 'delete') {
    $stmt = $pdo->prepare('DELETE FROM horarios WHERE id = ?');
    try {
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar en base de datos.']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción no válida.']);
