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
    echo json_encode(['error' => 'Solo los administradores pueden importar estudiantes.']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se subió ningún archivo o hubo un error en la subida.']);
    exit;
}

$entidad = $_POST['entidad'] ?? 'estudiantes';

$fileTmpPath = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileExtension !== 'csv') {
    http_response_code(400);
    echo json_encode(['error' => 'Solo se permiten archivos CSV.']);
    exit;
}

$pdo = db();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos.']);
    exit;
}

$handle = fopen($fileTmpPath, 'r');
if ($handle === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al leer el archivo.']);
    exit;
}

// Saltar la cabecera (asumimos que la primera fila es cabecera)
fgetcsv($handle, 1000, ',');

$importados = 0;
$errores = 0;

// Helper para buscar u obtener ID
$getProgramaId = function($nombre) use ($pdo) {
    if (!$nombre) return null;
    $row = fetch_one('SELECT id FROM programas WHERE nombre = ?', [$nombre]);
    return $row ? (int)$row['id'] : null;
};

$getPeriodoId = function($nombre) use ($pdo) {
    if (!$nombre) return null;
    $row = fetch_one('SELECT id FROM periodos_curriculares WHERE nombre = ?', [$nombre]);
    return $row ? (int)$row['id'] : null;
};

$getUnidadId = function($nombre) use ($pdo) {
    if (!$nombre) return null;
    $row = fetch_one('SELECT id FROM unidades_didacticas WHERE nombre = ?', [$nombre]);
    return $row ? (int)$row['id'] : null;
};

$stmtInsertEst = $pdo->prepare('INSERT INTO estudiantes (codigo, nombres, programa_id, periodo_curricular_id, seccion, unidad_didactica_id, total_sesiones, inasistencias, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nombres = VALUES(nombres), programa_id = VALUES(programa_id), periodo_curricular_id = VALUES(periodo_curricular_id), seccion = VALUES(seccion), unidad_didactica_id = VALUES(unidad_didactica_id)');
$stmtInsertDoc = $pdo->prepare('INSERT INTO docentes (nombres, dni, correo, telefono, condicion, categoria, jornada, programa_id, unidad_didactica_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE dni = VALUES(dni), condicion = VALUES(condicion), categoria = VALUES(categoria)');
$stmtInsertHorario = $pdo->prepare('INSERT INTO horarios (programa_id, unidad_didactica_id, docente_id, seccion, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?, ?)');

try {
    $pdo->beginTransaction();
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        if ($entidad === 'estudiantes') {
            if (count($data) < 7) { $errores++; continue; }
            $codigo = trim($data[0] ?? '');
            $nombresStr = trim(($data[2] ?? '') . ' ' . ($data[3] ?? ''));
            $programa = trim($data[4] ?? '');
            $ciclo = trim($data[5] ?? '');
            $seccion = trim($data[6] ?? '');
            $unidad = trim($data[7] ?? '');
            if (empty($codigo) || empty($nombresStr)) { $errores++; continue; }
            
            try {
                $stmtInsertEst->execute([$codigo, $nombresStr, $getProgramaId($programa), $getPeriodoId($ciclo), $seccion, $getUnidadId($unidad), 20, 0, 'Activo']);
                $importados++;
            } catch (PDOException $e) { $errores++; }
        } elseif ($entidad === 'docentes') {
            if (count($data) < 3) { $errores++; continue; }
            $nombres = trim($data[0] ?? '');
            $dni = trim($data[1] ?? '');
            $correo = trim($data[2] ?? '');
            if (empty($nombres)) { $errores++; continue; }
            try {
                $stmtInsertDoc->execute([$nombres, $dni, $correo, '', 'Nombrado', 'Principal', 'Tiempo Completo', null, null]);
                $importados++;
            } catch (PDOException $e) { $errores++; }
        } elseif ($entidad === 'horarios') {
            // programa, unidad, docente, seccion, dia, hora_inicio, hora_fin
            if (count($data) < 7) { $errores++; continue; }
            $programa = trim($data[0] ?? '');
            $unidad = trim($data[1] ?? '');
            $docenteNombre = trim($data[2] ?? '');
            $seccion = trim($data[3] ?? '');
            $dia = trim($data[4] ?? '');
            $hora_inicio = trim($data[5] ?? '');
            $hora_fin = trim($data[6] ?? '');
            
            if (empty($programa) || empty($unidad) || empty($docenteNombre)) { $errores++; continue; }
            
            // Get docente ID
            $docenteId = null;
            $rowDoc = fetch_one('SELECT id FROM docentes WHERE nombres = ?', [$docenteNombre]);
            if ($rowDoc) $docenteId = (int)$rowDoc['id'];
            
            $progId = $getProgramaId($programa);
            $unidId = $getUnidadId($unidad);
            
            if (!$progId || !$unidId || !$docenteId) { $errores++; continue; }
            
            try {
                $stmtInsertHorario->execute([$progId, $unidId, $docenteId, $seccion, $dia, $hora_inicio, $hora_fin]);
                $importados++;
            } catch (PDOException $e) { $errores++; }
        }
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    fclose($handle);
    http_response_code(500);
    echo json_encode(['error' => 'Error en la importación: ' . $e->getMessage()]);
    exit;
}

fclose($handle);

echo json_encode([
    'success' => true, 
    'message' => "Importación completada. $importados insertados, $errores omitidos (duplicados o incompletos)."
]);
