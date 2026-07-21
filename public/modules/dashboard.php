<?php
$programaFiltro = $_GET['programa'] ?? 'Todos';
$programaMap = [
    'DSI' => 'Desarrollo de Sistemas de Informacion',
    'CON' => 'Contabilidad',
    'ENF' => 'Enfermeria Tecnica',
];

$filtroNombre = $programaFiltro === 'Todos' ? null : ($programaMap[$programaFiltro] ?? $programaFiltro);

// Base queries with optional filtering
$whereEstudiantes = $filtroNombre ? 'WHERE p.nombre = ?' : '';
$paramsEstudiantes = $filtroNombre ? [$filtroNombre] : [];

$totales = fetch_one("
    SELECT 
        COUNT(e.id) as total,
        SUM(CASE WHEN (e.inasistencias / NULLIF(e.total_sesiones, 0)) < 0.2 OR e.total_sesiones IS NULL OR e.total_sesiones = 0 THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN (e.inasistencias / NULLIF(e.total_sesiones, 0)) >= 0.2 AND (e.inasistencias / NULLIF(e.total_sesiones, 0)) < 0.3 THEN 1 ELSE 0 END) as riesgo,
        SUM(CASE WHEN (e.inasistencias / NULLIF(e.total_sesiones, 0)) >= 0.3 THEN 1 ELSE 0 END) as inhabilitados
    FROM estudiantes e
    LEFT JOIN programas p ON e.programa_id = p.id 
    $whereEstudiantes
", $paramsEstudiantes);

$totalDocentes = fetch_one('SELECT COUNT(*) as total FROM docentes')['total'] ?? 0;
$totalProgramas = fetch_one('SELECT COUNT(*) as total FROM programas')['total'] ?? 0;
$totalUsuarios = fetch_one('SELECT COUNT(*) as total FROM usuarios')['total'] ?? 0;

$alertas = fetch_all("
    SELECT e.nombres, p.nombre as programa, ud.nombre as unidad, 
        CASE 
            WHEN (e.inasistencias / NULLIF(e.total_sesiones, 0)) >= 0.3 THEN 'Inhabilitado'
            ELSE 'En riesgo'
        END as estado,
        e.total_sesiones as sesiones, e.inasistencias 
    FROM estudiantes e 
    LEFT JOIN programas p ON e.programa_id = p.id 
    LEFT JOIN unidades_didacticas ud ON e.unidad_didactica_id = ud.id 
    WHERE (e.inasistencias / NULLIF(e.total_sesiones, 0)) >= 0.2 " . ($filtroNombre ? "AND p.nombre = ?" : "") . " 
    ORDER BY (e.inasistencias / NULLIF(e.total_sesiones, 0)) DESC LIMIT 10
", $paramsEstudiantes);

$cards = [
    ['Programas de Estudio', $totalProgramas, '#0891b2', 'Especialidades registradas'],
    ['Total Estudiantes', $totales['total'] ?? 0, '#1a3a6b', 'Periodo actual'],
    ['Total Docentes', $totalDocentes, '#2563eb', 'Plantilla activa'],
    ['Total Usuarios', $totalUsuarios, '#7c3aed', 'Cuentas con acceso'],
    ['Estudiantes En Riesgo', $totales['riesgo'] ?? 0, '#ea580c', 'Cercanos al 30%'],
    ['Inhabilitados', $totales['inhabilitados'] ?? 0, '#dc2626', 'Superaron 30%'],
];

$chartData = [
    'estado' => [
        'labels' => ['Activo', 'En riesgo', 'Inhabilitado'],
        'values' => [$totales['activos'] ?? 0, $totales['riesgo'] ?? 0, $totales['inhabilitados'] ?? 0],
    ]
];

function render_dashboard_card(string $label, string|int $value, string $color, string $sub): void
{
    ?>
    <div class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-medium text-slate-500" title="<?= e($label) ?>"><?= e($label) ?></p>
                <p class="mt-2 text-3xl font-bold" style="color: <?= e($color) ?>"><?= e($value) ?></p>
            </div>
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" style="background-color: <?= e($color) ?>15">
                <div class="h-3 w-3 rounded-full" style="background-color: <?= e($color) ?>"></div>
            </div>
        </div>
        <p class="mt-4 truncate text-xs text-slate-400" title="<?= e($sub) ?>"><?= e($sub) ?></p>
    </div>
    <?php
}

?>
<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm font-semibold text-[#1a3a6b]">Filtrar por programa:</span>
        <div class="flex flex-wrap items-center gap-1 rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
            <?php foreach (['Todos', 'DSI', 'CON', 'ENF'] as $codigo): ?>
                <a href="<?= e(base_url('index.php?m=dashboard&programa=' . $codigo)) ?>" class="inline-flex items-center justify-center rounded-md px-4 py-1.5 text-xs font-semibold transition <?= $programaFiltro === $codigo ? 'bg-[#1a3a6b] text-white' : 'text-slate-600 hover:bg-slate-50' ?>"><?= e($codigo) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex flex-col sm:grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <?php foreach ($cards as [$label, $value, $color, $sub]): ?>
            <?php render_dashboard_card($label, $value, $color, $sub); ?>
        <?php endforeach; ?>
    </div>

    <div class="flex flex-col gap-6 xl:grid xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-[#1a3a6b]">Estado Academico</h2>
            <div class="relative mx-auto mt-6 h-[220px] max-w-[220px]">
                <canvas id="chartEstado"></canvas>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-bold text-[#1a3a6b]">Alertas Criticas</h2>
                    <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-600"><?= count($alertas) ?> detectadas</span>
                </div>
                <div class="space-y-3">
                    <?php foreach ($alertas as $est):
                        $iconTone = $est['estado'] === 'Inhabilitado' ? 'text-red-500' : 'text-amber-500';
                    ?>
                        <div class="flex items-start gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 transition hover:border-slate-200 hover:bg-slate-100">
                            <i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0 <?= $iconTone ?>"></i>
                            <div class="min-w-0 flex-1">
                                <p class="break-words text-sm font-bold text-slate-800" title="<?= e($est['nombres']) ?>"><?= e($est['nombres']) ?></p>
                                <p class="break-words text-xs font-medium text-slate-500" title="<?= e($est['programa']) ?>"><?= e($est['programa']) ?></p>
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    <p class="min-w-0 flex-1 break-words text-[11px] text-slate-400" title="<?= e($est['unidad']) ?>"><?= pct($est) ?>% faltas · <?= e($est['unidad']) ?></p>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= badge_class($est['estado']) ?>"><?= e($est['estado']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$alertas): ?>
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><i data-lucide="shield-check" class="h-6 w-6"></i></div>
                            <p class="mt-3 text-sm font-semibold text-slate-700">Sistema Saludable</p>
                            <p class="text-xs text-slate-500">No hay estudiantes en riesgo.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
window.dashboardChartData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(base_url('assets/dashboard-charts.js')) ?>"></script>
