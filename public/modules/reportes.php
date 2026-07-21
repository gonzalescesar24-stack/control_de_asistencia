<?php
$estudiantes = all_estudiantes();
$programas_list = all_programas();
$unidades_list  = all_unidades();
$periodos_list  = all_periodos();

$reportTypes = [
    'general'      => 'Reporte general',
    'por_programa' => 'Reporte por programa de estudio',
    'por_unidad'   => 'Reporte por unidad didáctica',
    'por_docente'  => 'Reporte por docente',
    'riesgo'       => 'Reporte de estudiantes en riesgo',
    'inhabilitados'=> 'Reporte de estudiantes inhabilitados',
];
?>
<div class="space-y-6">
<form action="<?= e(base_url('api/reporte_pdf.php')) ?>" method="GET" target="_blank">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-end gap-3">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Tipo de reporte</span>
                    <select name="tipo" class="form-control bg-slate-50 focus:bg-white transition-colors">
                        <?php foreach ($reportTypes as $val => $label): ?>
                            <option value="<?= e($val) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Programa de estudio</span>
                    <select name="programa_de_estudio" class="form-control bg-slate-50 focus:bg-white transition-colors">
                        <option value="Todos">Todos</option>
                        <?php foreach ($programas_list as $p): ?>
                            <option value="<?= e($p['nombre']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Periodo curricular</span>
                    <select name="ciclo" class="form-control bg-slate-50 focus:bg-white transition-colors">
                        <option value="Todos">Todos</option>
                        <?php foreach ($periodos_list as $p): ?>
                            <option value="<?= e($p['nombre']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Unidad Didáctica</span>
                    <select name="unidad_didáctica" class="form-control bg-slate-50 focus:bg-white transition-colors">
                        <option value="Todos">Todos</option>
                        <?php foreach ($unidades_list as $u): ?>
                            <option value="<?= e($u['nombre']) ?>"><?= e($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 ml-auto">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-[#142d54] focus:ring-2 focus:ring-[#1a3a6b]/50">
                    <i data-lucide="file-text" class="h-4.5 w-4.5"></i>
                    Generar reporte en PDF
                </button>
                <button type="submit" formaction="<?= e(base_url('api/export_csv.php')) ?>" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-600 shadow-sm transition-all hover:border-emerald-300 hover:bg-emerald-50">
                    <i data-lucide="file-spreadsheet" class="h-4.5 w-4.5"></i>
                    Exportar Excel
                </button>
            </div>
        </div>
    </div>
</form>

    <div id="reporte-container" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 bg-slate-50/50">
            <div>
                <h2 class="text-lg font-bold text-[#0b2f63]">Vista previa — Reporte general</h2>
                <p class="mt-1 text-xs text-slate-500 font-medium">IES "VÍCTOR RAÚL HAYA DE LA TORRE" · Periodo 2026-I</p>
            </div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($estudiantes) ?> registros
            </span>
        </div>
        <div class="hidden md:block overflow-x-auto min-h-[300px]">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-th text-center w-12">#</th>
                        <th class="table-th whitespace-nowrap">Código</th>
                        <th class="table-th min-w-[200px]">Estudiante</th>
                        <th class="table-th min-w-[150px]">Programa de Estudio</th>
                        <th class="table-th whitespace-nowrap text-center">Ciclo/Sección</th>
                        <th class="table-th min-w-[180px]">Unidad Didáctica</th>
                        <th class="table-th text-center whitespace-nowrap">Sesiones</th>
                        <th class="table-th text-center whitespace-nowrap">Inasistencias</th>
                        <th class="table-th text-center whitespace-nowrap">% Inasist.</th>
                        <th class="table-th text-center w-28">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($estudiantes as $index => $e): 
                        $pct = pct($e);
                        $state = $e['estado'];
                        if ($state === 'Activo') {
                            $state = $pct >= 30 ? 'Inhabilitado' : ($pct >= 20 ? 'En riesgo' : 'Activo');
                        }
                        $pctClass = $pct >= 30 ? 'text-red-600' : ($pct >= 20 ? 'text-amber-500' : 'text-emerald-600');
                    ?>
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="table-td text-center text-slate-400 font-medium"><?= e($index + 1) ?></td>
                            <td class="table-td font-mono text-xs text-slate-500"><?= e($e['codigo']) ?></td>
                            <td class="table-td font-semibold text-slate-900"><?= e($e['nombres']) ?></td>
                            <td class="table-td text-slate-600 text-xs truncate max-w-[150px]" title="<?= e($e['programa']) ?>"><?= e($e['programa']) ?></td>
                            <td class="table-td font-medium text-center text-slate-700"><?= e($e['ciclo']) ?>-<?= e($e['seccion']) ?></td>
                            <td class="table-td text-slate-600 text-xs truncate max-w-[180px]" title="<?= e($e['unidad']) ?>"><?= e($e['unidad']) ?></td>
                            <td class="table-td text-center font-medium text-slate-700"><?= e($e['sesiones']) ?></td>
                            <td class="table-td text-center font-semibold text-red-600"><?= e($e['inasistencias']) ?></td>
                            <td class="table-td text-center font-bold <?= e($pctClass) ?>"><?= e($pct) ?>%</td>
                            <td class="table-td text-center">
                                <span class="inline-flex justify-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?= badge_class($state) ?>">
                                    <?= e($state) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="reporte-mobile-container" class="grid gap-4 md:hidden p-4 bg-slate-50/50">
            <?php foreach ($estudiantes as $index => $e): 
                $pct = pct($e);
                $state = $e['estado'];
                if ($state === 'Activo') {
                    $state = $pct >= 30 ? 'Inhabilitado' : ($pct >= 20 ? 'En riesgo' : 'Activo');
                }
                $pctClass = $pct >= 30 ? 'text-red-600' : ($pct >= 20 ? 'text-amber-500' : 'text-emerald-600');
            ?>
                <div class="bg-white rounded-xl border p-4 shadow-sm transition hover:shadow-md">
                    <div class="flex justify-between items-start border-b border-slate-50 pb-2 mb-2">
                        <div>
                            <div class="font-semibold text-slate-900"><?= e($e['nombres']) ?></div>
                            <div class="font-mono text-xs text-slate-500"><?= e($e['codigo']) ?></div>
                        </div>
                        <span class="inline-flex justify-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?= badge_class($state) ?>"><?= e($state) ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                        <div class="min-w-0"><span class="text-slate-400 block">Prog. Estudio</span><span class="font-medium text-slate-700 break-words block"><?= e($e['programa']) ?></span></div>
                        <div class="min-w-0"><span class="text-slate-400 block">Unidad</span><span class="font-medium text-slate-700 break-words block"><?= e($e['unidad']) ?></span></div>
                    </div>
                    <div class="grid grid-cols-4 gap-1 bg-slate-50 rounded-lg p-2 text-center text-[10px] sm:text-xs">
                        <div class="min-w-0"><span class="text-slate-400 block truncate">Ciclo/Sec</span><span class="font-semibold text-slate-700 truncate block"><?= e($e['ciclo']) ?>-<?= e($e['seccion']) ?></span></div>
                        <div class="min-w-0"><span class="text-slate-400 block truncate">Sesiones</span><span class="font-semibold text-slate-700 block"><?= e($e['sesiones'] ?? $e['total_sesiones'] ?? 0) ?></span></div>
                        <div class="min-w-0"><span class="text-slate-400 block truncate">Inasist.</span><span class="font-semibold text-red-600 block"><?= e($e['inasistencias']) ?></span></div>
                        <div class="min-w-0"><span class="text-slate-400 block truncate">% Inasist.</span><span class="font-bold <?= e($pctClass) ?> block"><?= e($pct) ?>%</span></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="reporte_pdf"]');
    const selects = form.querySelectorAll('select');
    const tbody = document.querySelector('#reporte-container tbody');
    const countBadge = document.querySelector('#reporte-container .bg-blue-100');
    
    function updatePreview() {
        const urlParams = new URLSearchParams(new FormData(form)).toString();
        tbody.style.opacity = '0.5';
        const mobileContainer = document.getElementById('reporte-mobile-container');
        if (mobileContainer) mobileContainer.style.opacity = '0.5';
        
        fetch('api/preview_reporte.php?' + urlParams)
            .then(res => res.json())
            .then(data => {
                countBadge.textContent = data.count + ' registros';
                tbody.innerHTML = data.html;
                tbody.style.opacity = '1';
                if (mobileContainer && data.mobileHtml) {
                    mobileContainer.innerHTML = data.mobileHtml;
                    mobileContainer.style.opacity = '1';
                }
            });
    }

    selects.forEach(sel => sel.addEventListener('change', updatePreview));
    updatePreview();
});
</script>


