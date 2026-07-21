<?php
$pdo = db();

// Filters from GET request could be applied here in a real scenario
$whereClause = "WHERE 1=1";
$params = [];

$query = "
    SELECT 
        a.id, 
        a.estado, 
        a.registrado_por, 
        s.fecha, 
        s.hora, 
        ud.nombre as unidad, 
        s.seccion, 
        e.nombres as estudiante,
        pc.nombre as ciclo
    FROM asistencias a
    JOIN sesiones s ON a.sesion_id = s.id
    JOIN estudiantes e ON a.estudiante_id = e.id
    LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id
    LEFT JOIN periodos_curriculares pc ON e.periodo_curricular_id = pc.id
    $whereClause
    ORDER BY s.fecha DESC, s.hora DESC, e.nombres ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$asistencias = $stmt->fetchAll();

// Calculate stats
$stats = [
    'Total' => count($asistencias),
    'Presentes' => 0,
    'Inasistentes' => 0,
    'Tardanzas' => 0,
    'Justificados' => 0
];
foreach ($asistencias as $a) {
    if ($a['estado'] === 'Presente') $stats['Presentes']++;
    elseif ($a['estado'] === 'Inasistente') $stats['Inasistentes']++;
    elseif ($a['estado'] === 'Tardanza') $stats['Tardanzas']++;
    elseif ($a['estado'] === 'Justificado') $stats['Justificados']++;
}
?>
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <?php render_filters([
                'Periodo' => ['2026-I', '2025-II'], 
                'Fecha' => ['Todas'], 
                'Unidad' => ['Todas', 'Programacion Web', 'Base de Datos'], 
                'Seccion' => ['Todas', 'V-B', 'IV-A'], 
                'Estado' => ['Todos', 'Presente', 'Inasistente', 'Tardanza', 'Justificado']
            ]); ?>
        </div>
    </div>
    
    <div class="grid gap-4 md:grid-cols-5">
        <?php render_stat('Total', $stats['Total']); ?>
        <?php render_stat('Presentes', $stats['Presentes'], 'green'); ?>
        <?php render_stat('Inasistentes', $stats['Inasistentes'], 'red'); ?>
        <?php render_stat('Tardanzas', $stats['Tardanzas'], 'gold'); ?>
        <?php render_stat('Justificados', $stats['Justificados']); ?>
    </div>
    
    <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-semibold text-[#1a3a6b]">Registros de Asistencia</h2>
            <span id="table-count-badge" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($asistencias) ?> registros
            </span>
        </div>
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-th w-16 text-center whitespace-nowrap">#</th>
                        <th class="table-th whitespace-nowrap">Fecha / Hora</th>
                        <th class="table-th whitespace-nowrap">Unidad</th>
                        <th class="table-th whitespace-nowrap text-center">Ciclo/Sección</th>
                        <th class="table-th whitespace-nowrap">Estudiante</th>
                        <th class="table-th whitespace-nowrap">Estado</th>
                        <th class="table-th whitespace-nowrap">Registrado por</th>
                        <th class="table-th whitespace-nowrap text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($asistencias as $index => $a): ?>
                    <tr class="transition hover:bg-slate-50/50 asistencia-row" data-unidad="<?= e($a['unidad']) ?>" data-seccion="<?= e($a['seccion']) ?>" data-estado="<?= e($a['estado']) ?>">
                        <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                        <td class="table-td whitespace-nowrap text-slate-500">
                            <div class="font-medium text-slate-700"><?= e($a['fecha']) ?></div>
                            <div class="text-xs"><?= e($a['hora']) ?></div>
                        </td>
                        <td class="table-td max-w-32 truncate text-slate-500" title="<?= e($a['unidad']) ?>"><?= e($a['unidad']) ?></td>
                        <td class="table-td text-center font-medium text-slate-600"><?= e($a['ciclo']) ?>-<?= e($a['seccion']) ?></td>
                        <td class="table-td font-medium text-slate-900"><?= e($a['estudiante']) ?></td>
                        <td class="table-td">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= badge_class($a['estado']) ?>"><?= e($a['estado']) ?></span>
                        </td>
                        <td class="table-td text-xs text-slate-500"><?= e($a['registrado_por']) ?></td>
                        <td class="table-td text-right">
                            <button type="button" class="rounded p-1.5 text-blue-600 transition hover:bg-blue-50" title="Ver detalle">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($asistencias)): ?>
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">No se encontraron registros de asistencia.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Contenedor móvil -->
    <div class="md:hidden">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-[#1a3a6b]">Registros de Asistencia</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 mobile-table-count-badge">
                <?= count($asistencias) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php foreach ($asistencias as $index => $a): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3 asistencia-row" data-unidad="<?= e($a['unidad']) ?>" data-seccion="<?= e($a['seccion']) ?>" data-estado="<?= e($a['estado']) ?>">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-[#0b2f63] leading-tight"><span class="text-slate-400 mr-1">#<?= $index + 1 ?></span><?= e($a['estudiante']) ?></h3>
                        <p class="text-xs text-slate-500"><?= e($a['unidad']) ?> · <?= e($a['ciclo']) ?>-<?= e($a['seccion']) ?></p>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold <?= badge_class($a['estado']) ?>"><?= e($a['estado']) ?></span>
                </div>
                <div class="flex items-center justify-between mt-2 pt-3 border-t border-slate-100">
                    <div class="text-xs text-slate-500 font-medium">
                        <i data-lucide="calendar" class="inline-block h-3.5 w-3.5 mr-1 text-slate-400"></i><?= e($a['fecha']) ?>
                        <i data-lucide="clock" class="inline-block h-3.5 w-3.5 ml-2 mr-1 text-slate-400"></i><?= e($a['hora']) ?>
                    </div>
                    <button type="button" class="rounded p-1.5 text-blue-600 transition hover:bg-blue-50" title="Ver detalle">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($asistencias)): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-sm text-slate-500 shadow-sm">
                No se encontraron registros de asistencia.
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const unidadSelect = document.getElementById('filter-unidad');
    const seccionSelect = document.getElementById('filter-seccion');
    const estadoSelect = document.getElementById('filter-estado');
    const rows = document.querySelectorAll('.asistencia-row');

    function filterTable() {
        const unidad = unidadSelect ? unidadSelect.value : 'Todas';
        const seccion = seccionSelect ? seccionSelect.value : 'Todas';
        const estado = estadoSelect ? estadoSelect.value : 'Todos';
        
        let count = 0;
        let present = 0, inasistente = 0, tardanza = 0, justificado = 0;

        rows.forEach(row => {
            const rowUnidad = row.getAttribute('data-unidad');
            const rowSeccion = row.getAttribute('data-seccion');
            const rowEstado = row.getAttribute('data-estado');

            const matchUnidad = (unidad === 'Todas' || !unidad || rowUnidad === unidad);
            const matchSeccion = (seccion === 'Todas' || !seccion || rowSeccion === seccion);
            const matchEstado = (estado === 'Todos' || !estado || rowEstado === estado);

            if (matchUnidad && matchSeccion && matchEstado) {
                row.style.display = '';
                count++;
                
                if (rowEstado === 'Presente') present++;
                else if (rowEstado === 'Inasistente') inasistente++;
                else if (rowEstado === 'Tardanza') tardanza++;
                else if (rowEstado === 'Justificado') justificado++;
                
            } else {
                row.style.display = 'none';
            }
        });
        
        const countEl = document.getElementById('table-count-badge');
        if (countEl) countEl.textContent = count + ' registros';
        
        // Update stats
        const statTotal = document.querySelector('.grid .rounded-xl:nth-child(1) .text-2xl');
        const statPresentes = document.querySelector('.grid .rounded-xl:nth-child(2) .text-2xl');
        const statInasistentes = document.querySelector('.grid .rounded-xl:nth-child(3) .text-2xl');
        const statTardanzas = document.querySelector('.grid .rounded-xl:nth-child(4) .text-2xl');
        const statJustificados = document.querySelector('.grid .rounded-xl:nth-child(5) .text-2xl');
        
        if (statTotal) statTotal.textContent = count;
        if (statPresentes) statPresentes.textContent = present;
        if (statInasistentes) statInasistentes.textContent = inasistente;
        if (statTardanzas) statTardanzas.textContent = tardanza;
        if (statJustificados) statJustificados.textContent = justificado;
    }

    if (unidadSelect) unidadSelect.addEventListener('change', filterTable);
    if (seccionSelect) seccionSelect.addEventListener('change', filterTable);
    if (estadoSelect) estadoSelect.addEventListener('change', filterTable);
});
</script>
