<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$clientToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $clientToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF inválido.']);
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
    exit;
}

$user = app_user();
$pdo = db();

$dbUser = fetch_one('SELECT password_hash FROM usuarios WHERE id = ?', [$user['id']]);

if (!$dbUser || !password_verify($current_password, $dbUser['password_hash'])) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña actual es incorrecta.']);
    exit;
}

$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
$stmt->execute([$new_hash, $user['id']]);

echo json_encode(['success' => true]);
