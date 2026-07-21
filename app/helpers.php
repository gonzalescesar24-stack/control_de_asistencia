<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}


function sanitize_input(array $data): array {
    $sanitized = [];
    foreach ($data as $k => $v) {
        if (is_string($v)) {
            $sanitized[$k] = htmlspecialchars(trim(str_replace(chr(0), '', $v)), ENT_QUOTES, 'UTF-8');
        } elseif (is_array($v)) {
            $sanitized[$k] = sanitize_input($v);
        } else {
            $sanitized[$k] = $v;
        }
    }
    return $sanitized;
}

function pct(array $estudiante): int
{
    $sesiones = max((int) ($estudiante['sesiones'] ?? 0), 1);
    return (int) round(((int) ($estudiante['inasistencias'] ?? 0) / $sesiones) * 100);
}

function badge_class(string $estado): string
{
    return match ($estado) {
        'Activo', 'Presente', 'Registrada' => 'bg-emerald-100 text-emerald-700',
        'En riesgo', 'Tardanza', 'Pendiente' => 'bg-amber-100 text-amber-700',
        'Inhabilitado', 'Inasistente', 'Cerrada' => 'bg-red-100 text-red-700',
        'Justificado' => 'bg-blue-100 text-blue-700',
        default => 'bg-slate-100 text-slate-600',
    };
}

function app_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!app_user()) {
        // Handle AJAX request timeout
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_SERVER['HTTP_FETCH'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Sesión expirada o no autorizada']);
            exit;
        }
        
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function verify_csrf_token(): void
{
    $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $clientToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF inválido o expirado.']);
        exit;
    }
}
function login_user(string $usuario, string $password): bool
{
    $row = fetch_one(
        'SELECT id, nombre, usuario, password_hash, rol, estado, correo FROM usuarios WHERE (usuario = ? OR correo = ?) AND estado = "Activo"',
        [$usuario, $usuario]
    );

    if (!$row || !password_verify($password, $row['password_hash'] ?? '')) {
        return false;
    }

    unset($row['password_hash']);
    $_SESSION['user'] = $row;
    return true;
}

function page_title(string $module): string
{
    $titles = [
        'dashboard' => 'Dashboard',
        'usuarios' => 'Gestion de Usuarios',
        'roles' => 'Roles y Permisos',
        'estudiantes' => 'Gestión de Estudiantes',
        'docentes' => 'Gestion de Docentes',
        'configuracion' => 'Configuracion Academica',
        'horarios' => 'Gestión de Horarios',
        'asistencia' => 'Control de Asistencia',
        'calculo' => 'Calculo de Inasistencias',
        'reportes' => 'Reportes y Exportaciones',
        'respaldo' => 'Respaldo de Base de Datos',
        'auditoria' => 'Bitácora de Auditoría',
        'mis-sesiones' => 'Mis Sesiones',
        'registrar-asistencia' => 'Registrar Asistencia',
        'consultar-asistencia' => 'Consultar Asistencia',
        'reportes-docente' => 'Reportes',
        'mi-asistencia' => 'Mi Asistencia',
        'mi-porcentaje' => 'Mi Porcentaje de Inasistencias',
        'mi-estado' => 'Mi Estado Academico',
    ];
    return $titles[$module] ?? 'Panel';
}

function module_menu(string $role): array
{
    return match ($role) {
        'docente' => [
            'mis-sesiones' => ['Mis Sesiones', 'calendar'],
            'registrar-asistencia' => ['Registrar Asistencia', 'check-square'],
            'consultar-asistencia' => ['Consultar Asistencia', 'clipboard-list'],
            'reportes-docente' => ['Reportes', 'chart-column'],
        ],
        'estudiante' => [
            'mi-asistencia' => ['Mi Asistencia', 'clipboard-list'],
            'mi-porcentaje' => ['Mi % de Inasistencias', 'calculator'],
            'mi-estado' => ['Mi Estado Academico', 'circle-check'],
        ],
        default => [
            'dashboard' => ['Dashboard', 'layout-dashboard'],
            'usuarios' => ['Usuarios', 'users'],
            'roles' => ['Roles y Permisos', 'shield-check'],
            'estudiantes' => ['Estudiantes', 'graduation-cap'],
            'docentes' => ['Docentes', 'book-open'],
            'configuracion' => ['Configuracion Academica', 'settings'],
            'horarios' => ['Horarios', 'calendar-days'],
            'asistencia' => ['Control de Asistencia', 'clipboard-list'],
            'calculo' => ['Calculo de Inasistencias', 'calculator'],
            'reportes' => ['Reportes', 'chart-column'],
            'respaldo' => ['Respaldo de BD', 'database'],
            'auditoria' => ['Auditoría', 'history'],
        ],
    };
}

function all_estudiantes(): array
{
    return fetch_all('SELECT e.id, e.codigo, e.dni, e.nombres, e.programa_id, e.periodo_curricular_id, e.unidad_didactica_id, p.nombre as programa, pc.nombre as ciclo, e.seccion, ud.nombre as unidad, e.total_sesiones sesiones, e.inasistencias, e.estado FROM estudiantes e LEFT JOIN programas p ON e.programa_id = p.id LEFT JOIN periodos_curriculares pc ON e.periodo_curricular_id = pc.id LEFT JOIN unidades_didacticas ud ON e.unidad_didactica_id = ud.id ORDER BY e.nombres');
}

function all_docentes(): array
{
    return fetch_all('SELECT d.id, d.codigo, d.nombres, d.dni, d.correo, d.programa_id, d.unidad_didactica_id, p.nombre as programa, ud.nombre as unidad, pc.nombre as ciclo, d.seccion, d.usuario, d.estado FROM docentes d LEFT JOIN programas p ON d.programa_id = p.id LEFT JOIN unidades_didacticas ud ON d.unidad_didactica_id = ud.id LEFT JOIN periodos_curriculares pc ON ud.periodo_curricular_id = pc.id ORDER BY d.nombres');
}

function all_programas(): array
{
    return fetch_all('SELECT id, codigo, nombre, estado FROM programas ORDER BY nombre');
}

function all_periodos(): array
{
    return fetch_all('SELECT id, nombre FROM periodos_curriculares ORDER BY id');
}

function all_unidades(): array
{
    return fetch_all('SELECT ud.id, ud.nombre, ud.estado, mf.programa_id FROM unidades_didacticas ud JOIN periodos_curriculares pc ON ud.periodo_curricular_id = pc.id JOIN modulos_formativos mf ON pc.modulo_id = mf.id ORDER BY ud.nombre');
}

function all_sesiones(): array
{
    return fetch_all('SELECT s.id, s.fecha, s.hora, s.programa_id, s.unidad_didactica_id, p.nombre as programa, ud.nombre as unidad, pc.nombre as ciclo, s.seccion, s.docente_id, d.nombres as docente, s.periodo, s.estado FROM sesiones s LEFT JOIN programas p ON s.programa_id = p.id LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id LEFT JOIN periodos_curriculares pc ON ud.periodo_curricular_id = pc.id LEFT JOIN docentes d ON s.docente_id = d.id ORDER BY s.fecha DESC, s.hora DESC');
}

function all_respaldos(): array
{
    return fetch_all('SELECT id, fecha, hora, usuario, tamanio FROM respaldos ORDER BY fecha DESC, hora DESC');
}

function user_initials(string $nombre): string
{
    $parts = preg_split('/\s+/', trim($nombre)) ?: [];
    if (count($parts) >= 2) {
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }

    return mb_strtoupper(mb_substr($nombre, 0, 2));
}

function role_label(string $rol): string
{
    return match ($rol) {
        'admin' => 'Administrador',
        'docente' => 'Docente',
        'estudiante' => 'Estudiante',
        default => ucfirst($rol),
    };
}

function user_display_name(string $nombre): string
{
    $parts = preg_split('/\s+/', trim($nombre)) ?: [];
    if (count($parts) >= 2) {
        return $parts[0] . ' ' . $parts[1];
    }

    return $nombre;
}

function short_programa(?string $programa): string
{
    if ($programa === null) return 'N/A';
    if (str_contains($programa, 'Desarrollo de Sistemas')) return 'Desarrollo de Sistemas';
    if (str_contains($programa, 'Enfermeria')) return 'Enfermería';
    
    $parts = preg_split('/\s+/', trim($programa)) ?: [];
    return implode(' ', array_slice($parts, 0, 2));
}

function app_notifications(): array
{
    try {
        $alertas = fetch_all("
            SELECT e.id, e.nombres, e.estado, p.nombre as programa, e.inasistencias, e.total_sesiones as sesiones 
            FROM estudiantes e 
            LEFT JOIN programas p ON e.programa_id = p.id 
            WHERE e.estado IN ('En riesgo', 'Inhabilitado') 
            ORDER BY e.inasistencias DESC 
            LIMIT 5
        ");

        $notifs = [];
        foreach ($alertas as $est) {
            $pct = pct($est);
            $tipo = $est['estado'] === 'Inhabilitado' ? 'inhabilitado' : 'riesgo';
            $programa = short_programa($est['programa']);
            $msg = "{$est['nombres']} alcanzó el {$pct}% de inasistencias en {$programa}.";
            if ($tipo === 'inhabilitado') {
                $msg = "{$est['nombres']} fue inhabilitado ({$pct}%) en {$programa}.";
            }
            $notifs[] = [
                'id' => $est['id'],
                'tipo' => $tipo,
                'msg' => $msg,
                'tiempo' => 'Alerta del sistema'
            ];
        }
        return $notifs;
    } catch (Exception $e) {
        return [];
    }
}

function estudiantes_filtrados(array $filtros = []): array {
    $sql = 'SELECT e.id, e.codigo, e.dni, e.nombres, e.programa_id, e.periodo_curricular_id, e.unidad_didactica_id, p.nombre as programa, pc.nombre as ciclo, e.seccion, ud.nombre as unidad, e.total_sesiones sesiones, e.inasistencias, e.estado FROM estudiantes e LEFT JOIN programas p ON e.programa_id = p.id LEFT JOIN periodos_curriculares pc ON e.periodo_curricular_id = pc.id LEFT JOIN unidades_didacticas ud ON e.unidad_didactica_id = ud.id WHERE 1=1';
    $params = [];
    if (!empty($filtros['programa']) && $filtros['programa'] !== 'Todos') { $sql .= ' AND p.nombre = ?'; $params[] = $filtros['programa']; }
    if (!empty($filtros['ciclo']) && $filtros['ciclo'] !== 'Todos') { $sql .= ' AND pc.nombre = ?'; $params[] = $filtros['ciclo']; }
    if (!empty($filtros['unidad']) && $filtros['unidad'] !== 'Todos') { $sql .= ' AND ud.nombre = ?'; $params[] = $filtros['unidad']; }
    $sql .= ' ORDER BY e.nombres';
    return fetch_all($sql, $params);
}


function estudiantes_filtrados2(array $filtros = []): array {
    $sql = 'SELECT e.id, e.codigo, e.dni, e.nombres, e.programa_id, e.periodo_curricular_id, e.unidad_didactica_id, p.nombre as programa, pc.nombre as ciclo, e.seccion, ud.nombre as unidad, e.total_sesiones sesiones, e.inasistencias, e.estado FROM estudiantes e LEFT JOIN programas p ON e.programa_id = p.id LEFT JOIN periodos_curriculares pc ON e.periodo_curricular_id = pc.id LEFT JOIN unidades_didacticas ud ON e.unidad_didactica_id = ud.id WHERE 1=1';
    $params = [];
    if (!empty($filtros['programa_de_estudio']) && $filtros['programa_de_estudio'] !== 'Todos') { $sql .= ' AND p.nombre = ?'; $params[] = $filtros['programa_de_estudio']; }
    if (!empty($filtros['ciclo']) && $filtros['ciclo'] !== 'Todos') { $sql .= ' AND pc.nombre = ?'; $params[] = $filtros['ciclo']; }
    if (!empty($filtros['unidad_did�ctica']) && $filtros['unidad_did�ctica'] !== 'Todos') { $sql .= ' AND ud.nombre = ?'; $params[] = $filtros['unidad_did�ctica']; }
    $sql .= ' ORDER BY e.nombres';
    return fetch_all($sql, $params);
}

