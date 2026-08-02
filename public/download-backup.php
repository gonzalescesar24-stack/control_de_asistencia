<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/session.php';
start_app_session();
require_once __DIR__ . '/../app/helpers.php';

require_login();

if ((app_user()['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo 'No autorizado';
    exit;
}

function format_backup_size(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return round($bytes / 1048576, 1) . ' MB';
}

function build_backup_sql(): string
{
    $pdo = db();
    $output = '';

    $output .= "-- Respaldo administrativo de datos no sensibles\n";
    $output .= '-- Generado: ' . date('Y-m-d H:i:s') . "\n";
    $output .= '-- Usuario: ' . app_user()['nombre'] . "\n\n";

    if (!$pdo) {
        $output .= "-- No se pudo conectar a MySQL.\n";
        return $output;
    }

    $tables = [
        'programas',
        'modulos_formativos',
        'periodos_curriculares',
        'unidades_didacticas',
        'estudiantes',
        'docentes',
        'sesiones',
        'asistencias',
        'respaldos',
    ];

    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $exists = fetch_one('SHOW TABLES LIKE ?', [$table]);
        if (!$exists) {
            continue;
        }

        $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll();
        if (!$rows) {
            continue;
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(fn($column) => '`' . str_replace('`', '``', $column) . '`', $columns));

        $output .= "-- Datos de `$table`\n";
        foreach ($rows as $row) {
            $values = array_map(function ($column) use ($row, $pdo) {
                $value = $row[$column];
                return $value === null ? 'NULL' : $pdo->quote((string) $value);
            }, $columns);

            $output .= 'INSERT INTO `' . $table . '` (' . $columnList . ') VALUES (' . implode(', ', $values) . ");\n";
        }
        $output .= "\n";
    }

    $output .= "-- Usuarios sin hashes de contrasena\n";
    $usuarios = fetch_all('SELECT id, nombre, usuario, rol, estado, correo FROM usuarios ORDER BY id');
    foreach ($usuarios as $row) {
        $columns = array_keys($row);
        $columnList = implode(', ', array_map(fn($column) => '`' . str_replace('`', '``', $column) . '`', $columns));
        $values = array_map(fn($column) => $row[$column] === null ? 'NULL' : $pdo->quote((string) $row[$column]), $columns);
        $output .= 'INSERT INTO `usuarios` (' . $columnList . ') VALUES (' . implode(', ', $values) . ");\n";
    }

    $output .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    return $output;
}

$sql = build_backup_sql();
$filename = 'control_asistencia_' . date('Ymd_His') . '.sql';

if (isset($_GET['generar'])) {
    $pdo = db();
    if ($pdo) {
        $stmt = $pdo->prepare('INSERT INTO respaldos (usuario, fecha, hora, tamanio) VALUES (?, CURDATE(), CURTIME(), ?)');
        $stmt->execute([app_user()['nombre'], format_backup_size(strlen($sql))]);
    }
}

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $sql;
