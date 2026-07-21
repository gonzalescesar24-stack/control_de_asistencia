<?php
$estudiantes = all_estudiantes();
$programas   = all_programas();
$unidades    = all_unidades();
$periodos    = all_periodos();

$totalSesiones = max(array_column($estudiantes, 'sesiones') ?: [0]);
$totalInasistencias = array_sum(array_column($estudiantes, 'inasistencias'));
$totalPresentes = array_sum(array_map(fn($e) => max(0, (int)$e['sesiones'] - (int)$e['inasistencias']), $estudiantes));
$promedio = count($estudiantes) > 0 ? (int) round(array_sum(array_map(fn($e) => pct($e), $estudiantes)) / count($estudiantes)) : 0;
$enRiesgo = count(array_filter($estudiantes, fn($e) => pct($e) >= 20 && pct($e) < 30));
$inhabilitados = count(array_filter($estudiantes, fn($e) => pct($e) >= 30));
?>
<div class="space-y-4">
    <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-5 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#1a3a6b] text-white">
                <i data-lucide="calculator" class="h-5 w-5"></i>
            </div>
            <div>
                <h2 class="font-bold text-[#0b2f63]">Formula de calculo</h2>
                <p class="mt-3 inline-block rounded-lg bg-white px-4 py-2 font-mono text-sm font-bold text-[#0b2f63]">% Inasistencias = (Total de inasistencias ÷ Total de sesiones registradas) × 100</p>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-slate-600">
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-orange-500"></span>≥ 20% → En riesgo</span>
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-red-500"></span>≥ 30% → Inhabilitado</span>
                    <span>Aplica solo a estudiantes</span>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Programa de estudio</span>
                <select id="cal-filter-programa" class="form-control w-64">
                    <option value="">Todos</option>
                    <?php foreach ($programas as $p): ?>
                        <option value="<?= e($p['nombre']) ?>"><?= e($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Periodo curricular</span>
                <select id="cal-filter-ciclo" class="form-control w-40">
                    <option value="">Todos</option>
                    <?php foreach ($periodos as $p): ?>
                        <option value="<?= e($p['nombre']) ?>"><?= e($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Unidad didáctica</span>
                <select id="cal-filter-unidad" class="form-control w-64">
                    <option value="">Todas</option>
                    <?php foreach ($unidades as $u): ?>
                        <option value="<?= e($u['nombre']) ?>"><?= e($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Sección</span>
                <select id="cal-filter-seccion" class="form-control w-32">
                    <option value="">Todas</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Estado académico</span>
                <select id="cal-filter-estado" class="form-control w-48">
                    <option value="">Todos</option>
                    <option value="Activo">Activo</option>
                    <option value="En riesgo">En riesgo</option>
                    <option value="Inhabilitado">Inhabilitado</option>
                </select>
            </label>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
        <?php
        $stats = [
            ['Total Sesiones', $totalSesiones, 'text-[#1a3a6b]'],
            ['Total Presentes', $totalPresentes, 'text-emerald-600'],
            ['Total Inasistencias', $totalInasistencias, 'text-red-600'],
            ['Promedio %', $promedio . '%', 'text-orange-500'],
            ['En riesgo', $enRiesgo, 'text-orange-600'],
            ['Inhabilitados', $inhabilitados, 'text-red-600'],
        ];
        foreach ($stats as [$label, $value, $class]): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm">
                <p class="text-2xl font-bold <?= e($class) ?>"><?= e($value) ?></p>
                <p class="mt-2 text-xs text-slate-500"><?= e($label) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="overflow-x-auto overflow-y-hidden min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm hidden md:block">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 bg-slate-50/50">
            <h2 class="text-lg font-bold text-[#0b2f63]">Calculo por estudiante — Regla del 30%</h2>
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600">
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                    <?= count($estudiantes) ?> registros
                </span>
                <div class="h-4 w-px bg-slate-300 hidden sm:block"></div>
                <div class="flex flex-wrap gap-3">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Activo</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-orange-500"></span>En riesgo (≥20%)</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-red-500"></span>Inhabilitado (≥30%)</span>
                </div>
            </div>
        </div>
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-th w-16 text-center">#</th>
                    <th class="table-th">Estudiante</th>
                    <th class="table-th">Programa de Estudio</th>
                    <th class="table-th">Unidad Didactica</th>
                    <th class="table-th">Sesiones</th>
                    <th class="table-th">Asistencias</th>
                    <th class="table-th">Inasistencias</th>
                    <th class="table-th">% Inasistencias</th>
                    <th class="table-th">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($estudiantes as $index => $estudiante): ?>
                    <?php
                    $pct = pct($estudiante);
                    $tone = $pct >= 30 ? 'red' : ($pct >= 20 ? 'orange' : 'emerald');
                    $bar = ['red' => 'bg-red-500 text-red-600', 'orange' => 'bg-orange-500 text-orange-600', 'emerald' => 'bg-emerald-500 text-emerald-600'][$tone];
                    $badge = $pct >= 30 ? 'Inhabilitado' : ($pct >= 20 ? 'En riesgo' : 'Activo');
                    ?>
                    <tr class="calculo-row"
                        data-programa="<?= e($estudiante['programa'] ?? '') ?>"
                        data-ciclo="<?= e($estudiante['ciclo'] ?? '') ?>"
                        data-unidad="<?= e($estudiante['unidad'] ?? '') ?>"
                        data-seccion="<?= e($estudiante['seccion'] ?? '') ?>"
                        data-estado="<?= e($badge) ?>">
                        <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                        <td class="table-td font-semibold text-slate-900"><?= e($estudiante['nombres']) ?></td>
                        <td class="table-td"><?= e(mb_strimwidth($estudiante['programa'] ?? 'N/A', 0, 31, '...')) ?></td>
                        <td class="table-td"><?= e($estudiante['unidad'] ?? 'N/A') ?></td>
                        <td class="table-td text-center font-semibold"><?= e($estudiante['sesiones']) ?></td>
                        <td class="table-td text-center font-semibold text-emerald-600"><?= e((int) $estudiante['sesiones'] - (int) $estudiante['inasistencias']) ?></td>
                        <td class="table-td text-center font-semibold text-red-600"><?= e($estudiante['inasistencias']) ?></td>
                        <td class="table-td">
                            <div class="flex items-center gap-3">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100"><div class="h-full <?= e(strtok($bar, ' ')) ?>" style="width: <?= e(min($pct, 100)) ?>%"></div></div>
                                <span class="w-10 text-sm font-bold <?= e(substr($bar, strpos($bar, ' ') + 1)) ?>"><?= e($pct) ?>%</span>
                            </div>
                        </td>
                        <td class="table-td"><span class="rounded-full px-2 py-1 text-xs font-semibold <?= badge_class($badge) ?>"><?= e($badge) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

    <!-- Vista móvil (Cards) -->
    <div class="grid gap-4 md:hidden mt-2">
        <?php foreach ($estudiantes as $estudiante): ?>
            <?php
            $pct = pct($estudiante);
            $tone = $pct >= 30 ? 'red' : ($pct >= 20 ? 'orange' : 'emerald');
            $bar = ['red' => 'bg-red-500 text-red-600', 'orange' => 'bg-orange-500 text-orange-600', 'emerald' => 'bg-emerald-500 text-emerald-600'][$tone];
            $badge = $pct >= 30 ? 'Inhabilitado' : ($pct >= 20 ? 'En riesgo' : 'Activo');
            ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm calculo-row flex flex-col gap-3"
                data-programa="<?= e($estudiante['programa'] ?? '') ?>"
                data-ciclo="<?= e($estudiante['ciclo'] ?? '') ?>"
                data-unidad="<?= e($estudiante['unidad'] ?? '') ?>"
                data-seccion="<?= e($estudiante['seccion'] ?? '') ?>"
                data-estado="<?= e($badge) ?>">
                
                <div class="flex justify-between items-start">
                    <h3 class="font-semibold text-slate-900 leading-tight"><?= e($estudiante['nombres']) ?></h3>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold shrink-0 <?= badge_class($badge) ?>"><?= e($badge) ?></span>
                </div>
                
                <div class="grid gap-2 text-sm text-slate-600">
                    <div>
                        <span class="block text-xs text-slate-400">Programa</span>
                        <span class="font-medium block truncate" title="<?= e($estudiante['programa'] ?? 'N/A') ?>"><?= e($estudiante['programa'] ?? 'N/A') ?></span>
                    </div>
                    <div>
                        <span class="block text-xs text-slate-400">Unidad Didáctica</span>
                        <span class="font-medium block truncate" title="<?= e($estudiante['unidad'] ?? 'N/A') ?>"><?= e($estudiante['unidad'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <div class="mt-1 pt-3 border-t border-slate-100">
                    <div class="flex justify-between items-center mb-1 text-xs">
                        <span class="text-slate-500">Inasistencias (<?= e($estudiante['inasistencias']) ?>/<?= e($estudiante['sesiones']) ?>)</span>
                        <span class="font-bold <?= e(substr($bar, strpos($bar, ' ') + 1)) ?>"><?= e($pct) ?>%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full <?= e(strtok($bar, ' ')) ?>" style="width: <?= e(min($pct, 100)) ?>%"></div>
                    </div>
                    <p class="text-[11px] text-emerald-600 font-medium text-right mt-1">Asistencias: <?= e((int) $estudiante['sesiones'] - (int) $estudiante['inasistencias']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$estudiantes): ?>
            <div class="py-8 text-center text-slate-500 bg-white rounded-xl border border-slate-200">No hay datos de cálculo disponibles.</div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selPrograma = document.getElementById('cal-filter-programa');
    const selCiclo    = document.getElementById('cal-filter-ciclo');
    const selUnidad   = document.getElementById('cal-filter-unidad');
    const selSeccion  = document.getElementById('cal-filter-seccion');
    const selEstado   = document.getElementById('cal-filter-estado');
    const rows        = document.querySelectorAll('.calculo-row');

    function filterTable() {
        const prog    = selPrograma?.value   ?? '';
        const ciclo   = selCiclo?.value      ?? '';
        const unidad  = selUnidad?.value     ?? '';
        const seccion = selSeccion?.value    ?? '';
        const estado  = selEstado?.value     ?? '';
        let visible = 0;

        rows.forEach(row => {
            const matchProg    = !prog    || row.dataset.programa === prog;
            const matchCiclo   = !ciclo   || row.dataset.ciclo    === ciclo;
            const matchUnidad  = !unidad  || row.dataset.unidad   === unidad;
            const matchSeccion = !seccion || row.dataset.seccion  === seccion;
            const matchEstado  = !estado  || row.dataset.estado   === estado;

            if (matchProg && matchCiclo && matchUnidad && matchSeccion && matchEstado) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });
    }

    [selPrograma, selCiclo, selUnidad, selSeccion, selEstado].forEach(el => {
        if (el) el.addEventListener('change', filterTable);
    });
});
</script>
