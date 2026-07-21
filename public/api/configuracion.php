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
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if ($data) $data = sanitize_input($data);

if (!$data || empty($data['action']) || empty($data['entity'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Acción o entidad no especificada']);
    exit;
}

$pdo = db();
$action = $data['action'];
$entity = $data['entity'];

try {
    if ($entity === 'programa') {
        match ($action) {
            'create' => (function() use ($data, $pdo) {
                $codigo = trim($data['codigo'] ?? '');
                $nombre = trim($data['nombre'] ?? '');
                $estado = trim($data['estado'] ?? 'Activo');

                if (!$codigo || !$nombre) throw new Exception('Campos requeridos faltantes');

                $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM programas WHERE codigo = ?');
                $stmtCheck->execute([$codigo]);
                if ($stmtCheck->fetchColumn() > 0) throw new Exception('Ya existe un programa con este código');

                $stmt = $pdo->prepare('INSERT INTO programas (codigo, nombre, estado) VALUES (?, ?, ?)');
                $stmt->execute([$codigo, $nombre, $estado]);
                echo json_encode(['success' => true, 'message' => 'Programa creado']);
            })(),
            'update' => (function() use ($data, $pdo) {
                $id = (int) ($data['id'] ?? 0);
                $codigo = trim($data['codigo'] ?? '');
                $nombre = trim($data['nombre'] ?? '');
                $estado = trim($data['estado'] ?? 'Activo');

                if (!$id || !$codigo || !$nombre) throw new Exception('Campos requeridos faltantes');

                $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM programas WHERE codigo = ? AND id != ?');
                $stmtCheck->execute([$codigo, $id]);
                if ($stmtCheck->fetchColumn() > 0) throw new Exception('Ya existe otro programa con este código');

                $stmt = $pdo->prepare('UPDATE programas SET codigo = ?, nombre = ?, estado = ? WHERE id = ?');
                $stmt->execute([$codigo, $nombre, $estado, $id]);
                echo json_encode(['success' => true, 'message' => 'Programa actualizado']);
            })(),
            default => throw new Exception('Acción no válida')
        };
    } elseif ($entity === 'periodo_academico') {
        match ($action) {
            'create' => (function() use ($data, $pdo) {
                $nombre = trim($data['nombre'] ?? '');
                $fecha_inicio = trim($data['fecha_inicio'] ?? '');
                $fecha_fin = trim($data['fecha_fin'] ?? '');

                if (!$nombre || !$fecha_inicio || !$fecha_fin) throw new Exception('Campos requeridos faltantes');

                $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM periodos_academicos WHERE nombre = ?');
                $stmtCheck->execute([$nombre]);
                if ($stmtCheck->fetchColumn() > 0) {
                    throw new Exception('Ya existe un periodo con este código');
                }

                $stmt = $pdo->prepare('INSERT INTO periodos_academicos (nombre, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nombre, $fecha_inicio, $fecha_fin, 'Activo']);
                echo json_encode(['success' => true, 'message' => 'Periodo académico creado']);
            })(),
            'update' => (function() use ($data, $pdo) {
                $id = (int) ($data['id'] ?? 0);
                $estado = trim($data['estado'] ?? '');

                if (!$id || !$estado) throw new Exception('Datos incompletos');

                $stmt = $pdo->prepare('UPDATE periodos_academicos SET estado = ? WHERE id = ?');
                $stmt->execute([$estado, $id]);
                echo json_encode(['success' => true, 'message' => 'Estado del periodo actualizado']);
            })(),
            'delete' => (function() use ($data, $pdo) {
                $id = (int) ($data['id'] ?? 0);
                if (!$id) throw new Exception('Datos incompletos');
                
                // Obtener el nombre del periodo para buscar en la tabla sesiones
                $stmtNombre = $pdo->prepare('SELECT nombre FROM periodos_academicos WHERE id = ?');
                $stmtNombre->execute([$id]);
                $nombrePeriodo = $stmtNombre->fetchColumn();
                
                if ($nombrePeriodo) {
                    // Verificar si hay sesiones asociadas a este periodo (usando el nombre, ya que es varchar)
                    $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM sesiones WHERE periodo = ?');
                    $stmtCheck->execute([$nombrePeriodo]);
                    if ($stmtCheck->fetchColumn() > 0) {
                        throw new Exception('No se puede eliminar porque hay sesiones vinculadas a este periodo');
                    }
                }
                
                $stmt = $pdo->prepare('DELETE FROM periodos_academicos WHERE id = ?');
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Periodo académico eliminado']);
            })(),
            default => throw new Exception('Acción no válida')
        };
    } elseif ($entity === 'periodo') {
        match ($action) {
            'create' => (function() use ($data, $pdo) {
                // En un caso real, módulo_id debería ser dinámico. Por ahora asumimos 1 para mantener integridad.
                $modulo_id = (int) ($data['modulo_id'] ?? 1);
                $nombre = trim($data['nombre'] ?? '');

                if (!$nombre) throw new Exception('El nombre es requerido');

                $stmt = $pdo->prepare('INSERT INTO periodos_curriculares (modulo_id, nombre) VALUES (?, ?)');
                $stmt->execute([$modulo_id, $nombre]);
                echo json_encode(['success' => true, 'message' => 'Periodo creado']);
            })(),
            'update' => (function() use ($data, $pdo) {
                $id = (int) ($data['id'] ?? 0);
                $nombre = trim($data['nombre'] ?? '');

                if (!$id || !$nombre) throw new Exception('Datos incompletos');

                $stmt = $pdo->prepare('UPDATE periodos_curriculares SET nombre = ? WHERE id = ?');
                $stmt->execute([$nombre, $id]);
                echo json_encode(['success' => true, 'message' => 'Periodo actualizado']);
            })(),
            default => throw new Exception('Acción no válida')
        };
    } elseif ($entity === 'unidad') {
        match ($action) {
            'create' => (function() use ($data, $pdo) {
                $periodo_curricular_id = (int) ($data['periodo_curricular_id'] ?? 0);
                $nombre = trim($data['nombre'] ?? '');
                $estado = trim($data['estado'] ?? 'Activo');

                if (!$periodo_curricular_id || !$nombre) throw new Exception('Campos requeridos faltantes');

                $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM unidades_didacticas WHERE nombre = ? AND periodo_curricular_id = ?');
                $stmtCheck->execute([$nombre, $periodo_curricular_id]);
                if ($stmtCheck->fetchColumn() > 0) throw new Exception('Ya existe una unidad con este nombre en este periodo');

                $stmt = $pdo->prepare('INSERT INTO unidades_didacticas (periodo_curricular_id, nombre, estado) VALUES (?, ?, ?)');
                $stmt->execute([$periodo_curricular_id, $nombre, $estado]);
                echo json_encode(['success' => true, 'message' => 'Unidad creada']);
            })(),
            'update' => (function() use ($data, $pdo) {
                $id = (int) ($data['id'] ?? 0);
                $nombre = trim($data['nombre'] ?? '');
                $estado = trim($data['estado'] ?? 'Activo');

                if (!$id || !$nombre) throw new Exception('Datos incompletos');

                $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM unidades_didacticas WHERE nombre = ? AND id != ?');
                $stmtCheck->execute([$nombre, $id]);
                if ($stmtCheck->fetchColumn() > 0) throw new Exception('Ya existe otra unidad con este nombre');

                $stmt = $pdo->prepare('UPDATE unidades_didacticas SET nombre = ?, estado = ? WHERE id = ?');
                $stmt->execute([$nombre, $estado, $id]);
                echo json_encode(['success' => true, 'message' => 'Unidad actualizada']);
            })(),
            default => throw new Exception('Acción no válida')
        };
    } elseif ($entity === 'ciclo') {
        match ($action) {
            'create' => (function() use ($data, $pdo) {
                $nombre = trim($data['nombre'] ?? '');
                if (!$nombre) throw new Exception('El nombre del ciclo es requerido');
                $stmt = $pdo->prepare('INSERT INTO periodos_curriculares (modulo_id, nombre) VALUES (?, ?)');
                $stmt->execute([1, $nombre]);
                echo json_encode(['success' => true, 'message' => 'Ciclo guardado correctamente']);
            })(),
            default => throw new Exception('Acción no válida')
        };
    } elseif ($entity === 'sesion') {
        match ($action) {
            'create' => (function() use ($data, $pdo) {
                $periodo = trim($data['periodo'] ?? '');
                $unidad_id = (int) ($data['unidad_id'] ?? 0);
                $docente_id = (int) ($data['docente_id'] ?? 0);
                $fecha = trim($data['fecha'] ?? '');
                $hora = trim($data['hora'] ?? '');
                $programa_id = (int) ($data['programa_id'] ?? 0);
                $seccion = trim($data['seccion'] ?? '');
                
                if (!$periodo || !$unidad_id || !$docente_id || !$fecha || !$hora || !$programa_id || !$seccion) {
                    throw new Exception('Todos los campos son requeridos');
                }

                $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM sesiones WHERE docente_id = ? AND fecha = ? AND hora = ?');
                $stmtCheck->execute([$docente_id, $fecha, $hora]);
                if ($stmtCheck->fetchColumn() > 0) {
                    throw new Exception('El docente ya tiene una sesión registrada en esa fecha y hora');
                }

                $stmtCheck2 = $pdo->prepare('SELECT COUNT(*) FROM sesiones WHERE programa_id = ? AND unidad_didactica_id = ? AND seccion = ? AND fecha = ? AND hora = ?');
                $stmtCheck2->execute([$programa_id, $unidad_id, $seccion, $fecha, $hora]);
                if ($stmtCheck2->fetchColumn() > 0) {
                    throw new Exception('Ya existe una sesión para esta clase en esa fecha y hora');
                }

                $stmt = $pdo->prepare('INSERT INTO sesiones (fecha, hora, programa_id, unidad_didactica_id, seccion, docente_id, periodo, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$fecha, $hora, $programa_id, $unidad_id, $seccion, $docente_id, $periodo, 'Pendiente']);
                echo json_encode(['success' => true, 'message' => 'Sesión académica guardada correctamente']);
            })(),
            default => throw new Exception('Acción no válida')
        };
    } elseif ($entity === 'configuracion') {
        match ($action) {
            'update_regla_inasistencia' => (function() use ($data, $pdo) {
                $porcentaje = (int) ($data['porcentaje'] ?? 30);
                $stmt = $pdo->prepare('REPLACE INTO configuracion (clave, valor) VALUES (?, ?)');
                $stmt->execute(['regla_inasistencia', $porcentaje]);
                echo json_encode(['success' => true, 'message' => 'Regla de inasistencias actualizada']);
            })(),
            'update_tiempo_edicion' => (function() use ($data, $pdo) {
                $horas = (int) ($data['horas'] ?? 24);
                $stmt = $pdo->prepare('REPLACE INTO configuracion (clave, valor) VALUES (?, ?)');
                $stmt->execute(['tiempo_edicion', $horas]);
                echo json_encode(['success' => true, 'message' => 'Tiempo de edición actualizado']);
            })(),
            default => throw new Exception('Acción no válida')
        };
    } else {
        throw new Exception('Entidad no válida');
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos. Asegúrate que los datos no estén duplicados.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
