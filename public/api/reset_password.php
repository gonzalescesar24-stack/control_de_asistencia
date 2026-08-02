<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
require_once __DIR__ . '/../../app/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($token) || empty($password)) {
    echo json_encode(['error' => 'Faltan datos requeridos.']);
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
    
    // Verificar token válido
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['error' => 'El enlace es inválido o ha expirado.']);
        exit;
    }

    // Actualizar contraseña y limpiar token
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
    $stmt->execute([$hash, $usuario['id']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error al actualizar la contraseña.']);
}
