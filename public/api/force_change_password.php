<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$user = app_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['error' => 'La contraseña es obligatoria.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['error' => 'La contraseña debe tener al menos 8 caracteres.']);
    exit;
}

if (!preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_])/', $password)) {
    echo json_encode(['error' => 'La contraseña debe tener mayúsculas, minúsculas, números y al menos un carácter especial.']);
    exit;
}

try {
    $pdo = db();
    
    // Hash new password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update DB to unset must_change_password
    $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?, must_change_password = 0 WHERE id = ?");
    $stmt->execute([$hash, $user['id']]);

    // Update session
    $_SESSION['user']['must_change_password'] = 0;

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error al actualizar la contraseña.']);
}
