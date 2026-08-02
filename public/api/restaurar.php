<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

header('Content-Type: application/json; charset=utf-8');

require_login();

// Validate CSRF (from FormData POST)
$clientToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $clientToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF inválido o expirado.']);
    exit;
}

$user = app_user();
if ($user['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Solo administradores pueden restaurar respaldos.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (empty($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se subió ningún archivo válido.']);
    exit;
}

$fileName = $_FILES['backup_file']['name'];
if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'sql') {
    http_response_code(400);
    echo json_encode(['error' => 'Solo se permiten archivos .sql']);
    exit;
}

$sqlContent = file_get_contents($_FILES['backup_file']['tmp_name']);
if ($sqlContent === false || trim($sqlContent) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo SQL está vacío o no se pudo leer.']);
    exit;
}

// ─── Execute SQL dump via PDO (no mysql CLI needed) ───────────────────────────
try {
    $pdo = db();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec("SET NAMES utf8mb4");

    // Strip single-line SQL comments (-- ...) so they don't get
    // grouped with real statements and accidentally filtered out
    $cleanSql = preg_replace('/^--[^\n]*\n?/m', '', $sqlContent);
    // Also strip C-style block comments /* ... */
    $cleanSql = preg_replace('/\/\*.*?\*\//s', '', $cleanSql ?? $sqlContent);

    // Split into individual statements
    $statements = splitSqlStatements($cleanSql);

    $executed = 0;
    $errors   = [];

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;

        try {
            $pdo->exec($stmt);
            $executed++;
        } catch (PDOException $e) {
            $errors[] = substr($stmt, 0, 120) . ' → ' . $e->getMessage();
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    // Allow up to 2 minor errors (e.g. SET MODE quirks)
    if (count($errors) > 2) {
        http_response_code(500);
        echo json_encode([
            'error'   => 'Error al restaurar (' . count($errors) . ' sentencias fallaron).',
            'detalle' => array_slice($errors, 0, 5)
        ]);
        exit;
    }

    echo json_encode([
        'success'      => true,
        'ejecutadas'   => $executed,
        'advertencias' => count($errors) > 0 ? $errors : null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error durante la restauración: ' . $e->getMessage()]);
}

// ─── Helper: split SQL into statements respecting quotes ──────────────────────
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $current    = '';
    $inSingle   = false;
    $inDouble   = false;
    $len        = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if ($char === "'" && !$inDouble && $prev !== '\\') {
            $inSingle = !$inSingle;
        } elseif ($char === '"' && !$inSingle && $prev !== '\\') {
            $inDouble = !$inDouble;
        }

        if ($char === ';' && !$inSingle && !$inDouble) {
            $statements[] = trim($current);
            $current = '';
        } else {
            $current .= $char;
        }
    }

    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    return $statements;
}
