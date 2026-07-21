<?php
$user = app_user();
$pdo = db();

$stmtEst = $pdo->prepare('SELECT e.*, p.nombre as programa, pc.nombre as ciclo FROM estudiantes e LEFT JOIN programas p ON e.programa_id = p.id LEFT JOIN periodos_curriculares pc ON e.periodo_curricular_id = pc.id WHERE e.nombres LIKE ?');
$searchTerm = '%' . str_replace(' ', '%', $user['nombre']) . '%';
$stmtEst->execute([$searchTerm]);
$est = $stmtEst->fetch();

if (!$est) {
    echo "<div class='p-4 text-red-600 bg-red-50 rounded-lg'>Estudiante no encontrado en la base de datos.</div>";
    return;
}

$stmtAsist = $pdo->prepare('
    SELECT 
        p.nombre as programa, ud.nombre as unidad, s.fecha, s.hora, d.nombres as docente, a.estado, a.observacion as obs
    FROM asistencias a
    JOIN sesiones s ON a.sesion_id = s.id
    LEFT JOIN programas p ON s.programa_id = p.id
    LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id
    LEFT JOIN docentes d ON s.docente_id = d.id
    WHERE a.estudiante_id = ?
    ORDER BY s.fecha DESC, s.hora DESC
');
$stmtAsist->execute([$est['id']]);
$sesiones = $stmtAsist->fetchAll();

$total = count($sesiones);
$presentes = count(array_filter($sesiones, fn($s) => $s['estado'] === 'Presente'));
$inasistentes = count(array_filter($sesiones, fn($s) => $s['estado'] === 'Inasistente'));
$porcentaje = $total > 0 ? (int) round(($inasistentes / $total) * 100) : 0;
$barTone = $porcentaje >= 30 ? 'bg-red-500' : ($porcentaje >= 20 ? 'bg-amber-500' : 'bg-emerald-500');
$pctTone = $porcentaje >= 30 ? 'text-red-600' : ($porcentaje >= 20 ? 'text-amber-600' : 'text-blue-600');
$pctCard = $porcentaje >= 30 ? 'border-red-100 bg-red-50' : ($porcentaje >= 20 ? 'border-amber-100 bg-amber-50' : 'border-blue-100 bg-blue-50');
?>
<div class="space-y-4">
    <?php render_estudiante_header($est); ?>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-[#1a3a6b]"><?= $total ?></p>
            <p class="mt-1 text-xs text-slate-500">Sesiones registradas</p>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-emerald-600"><?= $presentes ?></p>
            <p class="mt-1 text-xs text-emerald-700">Asistencias</p>
        </div>
        <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-red-600"><?= $inasistentes ?></p>
            <p class="mt-1 text-xs text-red-700">Inasistencias</p>
        </div>
        <div class="rounded-xl border p-4 text-center shadow-sm <?= $pctCard ?>">
            <p class="text-2xl font-bold <?= $pctTone ?>"><?= $porcentaje ?>%</p>
            <p class="mt-1 text-xs text-slate-600">% Inasistencias</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm filter-bar flex items-end gap-3 flex-wrap">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">Unidad didáctica</span>
            <select id="filter-unidad" class="form-control min-w-[200px]">
                <option>Todas</option>
                <?php 
                $unidades = array_unique(array_column($sesiones, 'unidad'));
                foreach ($unidades as $u): ?>
                    <option><?= e($u) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">Estado</span>
            <select id="filter-estado" class="form-control min-w-[150px]">
                <option>Todos</option>
                <option>Presente</option>
                <option>Inasistente</option>
                <option>Tardanza</option>
                <option>Justificado</option>
            </select>
        </label>
        <div class="flex-shrink-0 ml-auto">
            <a href="<?= e(base_url('api/mi_reporte_pdf.php')) ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-[#142d54] focus:ring-2 focus:ring-[#1a3a6b]/50">
                <i data-lucide="file-text" class="h-4.5 w-4.5"></i>
                Descargar en PDF
            </a>
        </div>
    </div>

    <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h3 class="font-semibold text-[#1a3a6b]">Detalle por sesión — Periodo 2026-I</h3>
            <span id="table-count-badge" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($sesiones) ?> registros
            </span>
        </div>
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-th w-16 text-center whitespace-nowrap">#</th>
                        <th class="table-th whitespace-nowrap">Programa</th>
                        <th class="table-th whitespace-nowrap">Unidad Didáctica</th>
                        <th class="table-th whitespace-nowrap">Fecha</th>
                        <th class="table-th whitespace-nowrap">Hora</th>
                        <th class="table-th whitespace-nowrap">Docente</th>
                        <th class="table-th whitespace-nowrap">Estado</th>
                        <th class="table-th whitespace-nowrap">Observación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($sesiones as $index => $s): ?>
                        <tr class="hover:bg-slate-50/50 transition asistencia-row" data-unidad="<?= e($s['unidad']) ?>" data-estado="<?= e($s['estado']) ?>">
                            <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                            <td class="table-td text-xs text-slate-500 max-w-32 truncate" title="<?= e($s['programa']) ?>"><?= e($s['programa']) ?></td>
                            <td class="table-td text-xs font-medium text-[#1a3a6b] max-w-40 truncate" title="<?= e($s['unidad']) ?>"><?= e($s['unidad']) ?></td>
                            <td class="table-td whitespace-nowrap text-slate-500"><?= e($s['fecha']) ?></td>
                            <td class="table-td text-slate-500"><?= e($s['hora']) ?></td>
                            <td class="table-td whitespace-nowrap text-xs text-slate-500"><?= e($s['docente']) ?></td>
                            <td class="table-td">
                                <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium <?= badge_class($s['estado']) ?>"><?= e($s['estado']) ?></span>
                            </td>
                            <td class="table-td text-xs italic text-slate-500 max-w-32 truncate" title="<?= e($s['obs'] ?? '') ?>"><?= !empty($s['obs']) ? e($s['obs']) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sesiones)): ?>
                        <tr><td colspan="8" class="py-8 text-center text-slate-500">No hay registros de asistencia.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Vista móvil (Cards) -->
    <div class="md:hidden">
        <div class="mb-4 flex items-center justify-between px-2">
            <h3 class="font-semibold text-[#1a3a6b]">Detalle por sesión — Periodo 2026-I</h3>
            <span id="mobile-table-count-badge" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($sesiones) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php foreach ($sesiones as $index => $s): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-2 asistencia-row" data-unidad="<?= e($s['unidad']) ?>" data-estado="<?= e($s['estado']) ?>">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700 mb-1 inline-block"><span class="text-blue-500 font-bold mr-1 font-sans">#<?= $index + 1 ?></span><?= e($s['programa']) ?></span>
                        <h3 class="font-bold text-slate-900 leading-tight"><?= e($s['unidad']) ?></h3>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold shrink-0 <?= badge_class($s['estado']) ?>"><?= e($s['estado']) ?></span>
                </div>
                <div class="grid gap-1 text-sm text-slate-600 mt-1 pt-2 border-t border-slate-100">
                    <p><span class="font-semibold text-slate-400 text-xs">Fecha:</span> <?= e($s['fecha']) ?> <span class="font-semibold text-slate-400 text-xs ml-2">Hora:</span> <?= e($s['hora']) ?></p>
                    <p class="truncate"><span class="font-semibold text-slate-400 text-xs">Docente:</span> <?= e($s['docente']) ?></p>
                    <?php if (!empty($s['obs'])): ?>
                        <p class="italic text-xs text-slate-500 mt-1">"<?= e($s['obs']) ?>"</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($sesiones)): ?>
            <div class="py-8 text-center text-sm text-slate-500 bg-white rounded-xl border border-slate-200">No hay registros de asistencia.</div>
        <?php endif; ?>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-1.5 flex justify-between text-xs text-slate-500">
            <span>Avance hacia el límite del 30% de inasistencias</span>
            <span class="font-medium"><?= $porcentaje ?>% acumulado</span>
        </div>
        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full <?= $barTone ?> transition-all duration-500" style="width: <?= min(($porcentaje / 30) * 100, 100) ?>%"></div>
        </div>
        <div class="mt-1 flex justify-between text-[10px] text-slate-500">
            <span>0%</span>
            <span class="text-amber-600">20% riesgo</span>
            <span class="text-red-600">30% inhabilitado</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const unidadSelect = document.getElementById('filter-unidad');
    const estadoSelect = document.getElementById('filter-estado');
    const rows = document.querySelectorAll('.asistencia-row');

    function filterTable() {
        const unidad = unidadSelect.value;
        const estado = estadoSelect.value;
        let count = 0;

        rows.forEach(row => {
            const rowUnidad = row.getAttribute('data-unidad');
            const rowEstado = row.getAttribute('data-estado');

            const matchUnidad = unidad === 'Todas' || !unidad || rowUnidad === unidad;
            const matchEstado = estado === 'Todos' || !estado || rowEstado === estado;

            if (matchUnidad && matchEstado) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });
        
        const countEl = document.getElementById('table-count-badge');
        if (countEl) countEl.textContent = count + ' registros';
    }

    if (unidadSelect) unidadSelect.addEventListener('change', filterTable);
    if (estadoSelect) estadoSelect.addEventListener('change', filterTable);
});
</script>
