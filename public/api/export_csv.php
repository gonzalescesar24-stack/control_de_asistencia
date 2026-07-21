<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/db.php';
require_login();

$estudiantes = estudiantes_filtrados2($_GET);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reporte_asistencia_' . date('Y-m-d') . '.csv');

// Crear puntero de archivo hacia stdout
$output = fopen('php://output', 'w');

// Añadir BOM (Byte Order Mark) para que Excel reconozca correctamente los caracteres UTF-8 (acentos y ñ)
fputs($output, "\xEF\xBB\xBF");

// Encabezados de las columnas del CSV
fputcsv($output, ['Estudiante', 'DNI/Codigo', 'Programa de Estudio', 'Ciclo', 'Clases Asistidas', 'Inasistencias', '% Inasistencia', 'Estado Academico']);

foreach ($estudiantes as $e) {
    $sesiones = isset($e['sesiones']) ? (int) $e['sesiones'] : (int) ($e['total_sesiones'] ?? 0);
    $faltas = (int) $e['inasistencias'];
    $presentes = max(0, $sesiones - $faltas);
    $total = $sesiones;
    $pct = $total > 0 ? round(($faltas / $total) * 100, 1) : 0;
    
    $state = 'Activo';
    if ($pct >= 30) {
        $state = 'Inhabilitado';
    } elseif ($pct >= 20) {
        $state = 'En riesgo';
    }

    fputcsv($output, [
        $e['nombres'],
        $e['codigo'],
        $e['programa'] ?? 'N/A',
        $e['ciclo'] ?? 'N/A',
        $presentes,
        $faltas,
        $pct . '%',
        $state
    ]);
}

fclose($output);
exit;
