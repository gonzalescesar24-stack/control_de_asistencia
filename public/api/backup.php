<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

require_login();
$user = app_user();
if ($user['rol'] !== 'admin') {
    http_response_code(403);
    die('Acceso denegado. Solo administradores pueden realizar respaldos.');
}

// ─── Generate SQL dump via PDO (no mysqldump needed) ─────────────────────────
try {
    $pdo = db();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $dbName = 'control_asistencia';
    $date   = date('Y-m-d_H-i-s');
    $filename = "backup_{$dbName}_{$date}.sql";

    $sql  = "-- ============================================================\n";
    $sql .= "-- Respaldo de Base de Datos: {$dbName}\n";
    $sql .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Sistema: Control de Asistencia IES\n";
    $sql .= "-- ============================================================\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $sql .= "SET NAMES utf8mb4;\n\n";

    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // --- DROP + CREATE TABLE ---
        $sql .= "-- ------------------------------------------------------------\n";
        $sql .= "-- Table: `{$table}`\n";
        $sql .= "-- ------------------------------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

        $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $createRow['Create Table'] ?? '';
        $sql .= $createSql . ";\n\n";

        // --- INSERT DATA ---
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
            $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES\n";

            $valueLines = [];
            foreach ($rows as $row) {
                $vals = array_map(function ($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote((string)$val);
                }, $row);
                $valueLines[] = '(' . implode(', ', $vals) . ')';
            }

            // Chunk inserts every 500 rows to keep file readable
            foreach (array_chunk($valueLines, 500) as $i => $chunk) {
                if ($i > 0) {
                    $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES\n";
                }
                $sql .= implode(",\n", $chunk) . ";\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $sql .= "-- ============================================================\n";
    $sql .= "-- Fin del respaldo\n";
    $sql .= "-- ============================================================\n";

    $sizeBytes = strlen($sql);
    $sizeLabel = $sizeBytes > 1048576
        ? round($sizeBytes / 1048576, 1) . ' MB'
        : round($sizeBytes / 1024, 1) . ' KB';

    // Register in history
    try {
        $stmt = $pdo->prepare('INSERT INTO respaldos (fecha, hora, usuario, tamanio) VALUES (?, ?, ?, ?)');
        $stmt->execute([date('Y-m-d'), date('H:i:s'), $user['nombre'], $sizeLabel]);
    } catch (Exception $e) {
        // Non-fatal: history registration failed but backup proceeds
    }

    // Send file to browser
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . $sizeBytes);
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    echo $sql;
    exit;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error generando el respaldo: ' . $e->getMessage()]);
    exit;
}
