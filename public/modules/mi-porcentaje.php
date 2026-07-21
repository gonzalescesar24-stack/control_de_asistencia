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
        ud.nombre as unidad, 
        p.nombre as programa,
        COUNT(a.id) as sesiones,
        SUM(CASE WHEN a.estado = "Inasistente" THEN 1 ELSE 0 END) as inasistencias
    FROM asistencias a
    JOIN sesiones s ON a.sesion_id = s.id
    LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id
    LEFT JOIN programas p ON s.programa_id = p.id
    WHERE a.estudiante_id = ?
    GROUP BY ud.nombre, p.nombre
');
$stmtAsist->execute([$est['id']]);
$unidadesData = $stmtAsist->fetchAll();

$unidades = [];
$totalSesiones = 0;
$totalInasist = 0;
$peorEstado = 'Activo';

foreach ($unidadesData as $u) {
    $sesiones = (int)$u['sesiones'];
    $inas = (int)$u['inasistencias'];
    
    $totalSesiones += $sesiones;
    $totalInasist += $inas;
    
    $pct = $sesiones > 0 ? (int)round(($inas / $sesiones) * 100) : 0;
    
    $estado = 'Activo';
    if ($pct >= 30) {
        $estado = 'Inhabilitado';
        $peorEstado = 'Inhabilitado';
    } elseif ($pct >= 20) {
        $estado = 'En riesgo';
        if ($peorEstado !== 'Inhabilitado') $peorEstado = 'En riesgo';
    }
    
    $unidades[] = [
        'programa' => $u['programa'],
        'unidad' => $u['unidad'],
        'sesiones' => $sesiones,
        'inasistencias' => $inas,
        'estado' => $estado,
        'pct' => $pct
    ];
}

$est['estado'] = $peorEstado;
$porcentajeGlobal = $totalSesiones > 0 ? (int) round(($totalInasist / $totalSesiones) * 100) : 0;
$barTone = $porcentajeGlobal >= 30 ? 'bg-red-500' : ($porcentajeGlobal >= 20 ? 'bg-amber-500' : 'bg-emerald-500');
$pctTone = $porcentajeGlobal >= 30 ? 'text-red-600' : ($porcentajeGlobal >= 20 ? 'text-amber-600' : 'text-emerald-600');
?>
<div class="space-y-4">
    <?php render_estudiante_header($est); ?>

    <div class="rounded-xl border border-[#1a3a6b]/20 bg-[#1a3a6b]/5 p-5">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#1a3a6b]">
                <i data-lucide="calculator" class="h-4 w-4 text-white"></i>
            </div>
            <div>
                <p class="mb-1 text-sm font-semibold text-[#1a3a6b]">Fórmula de cálculo</p>
                <div class="inline-block rounded-lg border border-[#1a3a6b]/15 bg-white px-4 py-3 shadow-sm">
                    <p class="font-mono text-sm font-semibold text-[#1a3a6b]">% Inasistencias = (Inasistencias ÷ Total de sesiones) × 100</p>
                </div>
                <p class="mt-2 text-xs text-[#1a3a6b]/70">
                    Cuando el porcentaje acumulado alcanza o supera el <strong>30%</strong>, el estudiante pasa a estado <strong>Inhabilitado</strong> en la unidad didáctica correspondiente.
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-semibold text-[#1a3a6b]">Porcentaje acumulado global</h3>
            <span class="text-2xl font-bold <?= $pctTone ?>"><?= $porcentajeGlobal ?>%</span>
        </div>

        <div class="relative mb-6">
            <div class="h-5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full <?= $barTone ?> transition-all duration-500" style="width: <?= min(($porcentajeGlobal / 30) * 100, 100) ?>%"></div>
            </div>
            <div class="absolute top-0 flex h-5 items-center" style="left: <?= (20 / 30) * 100 ?>%">
                <div class="h-full w-0.5 bg-amber-500"></div>
            </div>
            <div class="absolute top-0 right-0 flex h-5 items-center">
                <div class="h-full w-0.5 bg-red-500"></div>
            </div>
            <div class="relative mt-1">
                <span class="absolute left-0 text-[10px] text-emerald-600">0%</span>
                <span class="absolute -translate-x-1/2 text-[10px] text-amber-600" style="left: <?= (20 / 30) * 100 ?>%">20% · Riesgo</span>
                <span class="absolute right-0 text-[10px] text-red-600">30% · Inhabilitado</span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-3 gap-3">
            <div class="rounded-lg bg-slate-50 p-3 text-center border border-slate-100">
                <p class="text-lg font-bold text-[#1a3a6b]"><?= $totalSesiones ?></p>
                <p class="mt-0.5 text-xs text-slate-500">Sesiones totales</p>
            </div>
            <div class="rounded-lg bg-emerald-50 p-3 text-center border border-emerald-100">
                <p class="text-lg font-bold text-emerald-600"><?= $totalSesiones - $totalInasist ?></p>
                <p class="mt-0.5 text-xs text-emerald-700">Asistencias</p>
            </div>
            <div class="rounded-lg bg-red-50 p-3 text-center border border-red-100">
                <p class="text-lg font-bold text-red-600"><?= $totalInasist ?></p>
                <p class="mt-0.5 text-xs text-red-700">Inasistencias</p>
            </div>
        </div>
    </div>

    <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <div>
                <h3 class="font-semibold text-[#1a3a6b]">Cálculo por unidad didáctica</h3>
                <p class="mt-0.5 text-xs text-slate-500">Desglose del porcentaje de inasistencias por cada unidad del periodo 2026-I</p>
            </div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($unidades) ?> registros
            </span>
        </div>
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-th w-16 text-center whitespace-nowrap">#</th>
                        <th class="table-th whitespace-nowrap">Programa</th>
                        <th class="table-th whitespace-nowrap">Unidad Didáctica</th>
                        <th class="table-th text-center whitespace-nowrap">Sesiones</th>
                        <th class="table-th text-center whitespace-nowrap">Asistencias</th>
                        <th class="table-th text-center whitespace-nowrap">Inasistencias</th>
                        <th class="table-th whitespace-nowrap">% Acumulado</th>
                        <th class="table-th whitespace-nowrap">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($unidades as $index => $u):
                        $p = $u['pct'];
                        $uBar = $p >= 30 ? 'bg-red-500' : ($p >= 20 ? 'bg-amber-500' : 'bg-emerald-500');
                        $uPct = $p >= 30 ? 'text-red-600' : ($p >= 20 ? 'text-amber-600' : 'text-emerald-600');
                    ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                            <td class="table-td text-xs text-slate-500"><?= e($u['programa']) ?></td>
                            <td class="table-td font-medium text-[#1a3a6b] max-w-48 truncate" title="<?= e($u['unidad']) ?>"><?= e($u['unidad']) ?></td>
                            <td class="table-td text-center text-slate-500"><?= $u['sesiones'] ?></td>
                            <td class="table-td text-center font-medium text-emerald-600"><?= $u['sesiones'] - $u['inasistencias'] ?></td>
                            <td class="table-td text-center font-medium text-red-600"><?= $u['inasistencias'] ?></td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100 min-w-16">
                                        <div class="h-full rounded-full <?= $uBar ?> transition-all duration-500" style="width: <?= min(($p / 30) * 100, 100) ?>%"></div>
                                    </div>
                                    <span class="w-8 text-right text-xs font-bold <?= $uPct ?>"><?= $p ?>%</span>
                                </div>
                            </td>
                            <td class="table-td">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium <?= badge_class($u['estado']) ?>"><?= e($u['estado']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($unidades)): ?>
                        <tr><td colspan="8" class="py-8 text-center text-slate-500">No se encontraron registros de evaluación.</td></tr>
                    <?php endif; ?>
                    <tr class="border-t-2 border-slate-200 bg-slate-50">
                        <td colspan="3" class="table-td font-semibold text-[#1a3a6b] text-right">Total acumulado</td>
                        <td class="table-td text-center font-semibold"><?= $totalSesiones ?></td>
                        <td class="table-td text-center font-semibold text-emerald-600"><?= $totalSesiones - $totalInasist ?></td>
                        <td class="table-td text-center font-semibold text-red-600"><?= $totalInasist ?></td>
                        <td class="table-td">
                            <span class="text-sm font-bold <?= $pctTone ?>"><?= $porcentajeGlobal ?>%</span>
                        </td>
                        <td class="table-td">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium <?= badge_class($peorEstado) ?>"><?= e($peorEstado) ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            </table>
        </div>
    </div>

    <!-- Vista móvil (Cards) -->
    <div class="md:hidden">
        <div class="mb-4 flex items-center justify-between px-2">
            <div>
                <h3 class="font-semibold text-[#1a3a6b]">Cálculo por unidad didáctica</h3>
            </div>
            <span id="mobile-table-count-badge" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($unidades) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php foreach ($unidades as $index => $u):
            $p = $u['pct'];
            $uBar = $p >= 30 ? 'bg-red-500' : ($p >= 20 ? 'bg-amber-500' : 'bg-emerald-500');
            $uPct = $p >= 30 ? 'text-red-600' : ($p >= 20 ? 'text-amber-600' : 'text-emerald-600');
        ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-2">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700 mb-1 inline-block"><span class="text-blue-500 font-bold mr-1 font-sans">#<?= $index + 1 ?></span><?= e($u['programa']) ?></span>
                        <h3 class="font-bold text-slate-900 leading-tight"><?= e($u['unidad']) ?></h3>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold shrink-0 <?= badge_class($u['estado']) ?>"><?= e($u['estado']) ?></span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-sm border-t border-slate-100 pt-3 mt-1">
                    <div><p class="text-[10px] uppercase tracking-wider text-slate-400">Total</p><p class="font-bold text-[#0b2f63]"><?= $u['sesiones'] ?></p></div>
                    <div><p class="text-[10px] uppercase tracking-wider text-slate-400">Asist.</p><p class="font-bold text-emerald-600"><?= $u['sesiones'] - $u['inasistencias'] ?></p></div>
                    <div><p class="text-[10px] uppercase tracking-wider text-slate-400">Faltas</p><p class="font-bold text-red-600"><?= $u['inasistencias'] ?></p></div>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center gap-3">
                    <span class="text-xs font-semibold text-slate-500">% Acumulado:</span>
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100 min-w-16">
                        <div class="h-full rounded-full <?= $uBar ?> transition-all duration-500" style="width: <?= min(($p / 30) * 100, 100) ?>%"></div>
                    </div>
                    <span class="w-8 text-right text-xs font-bold <?= $uPct ?>"><?= $p ?>%</span>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($unidades)): ?>
            <div class="py-8 text-center text-sm text-slate-500 bg-white rounded-xl border border-slate-200">No se encontraron registros de evaluación.</div>
        <?php endif; ?>
        
        <!-- Total acumulado móvil -->
        <?php if (!empty($unidades)): ?>
            <div class="bg-slate-50 rounded-xl border-2 border-slate-200 p-4 shadow-sm flex flex-col gap-2 mt-2">
                <div class="flex justify-between items-start mb-1">
                    <h3 class="font-bold text-[#1a3a6b]">Total Acumulado</h3>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= badge_class($peorEstado) ?>"><?= e($peorEstado) ?></span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-sm border-t border-slate-200 pt-3 mt-1">
                    <div><p class="text-[10px] uppercase tracking-wider text-slate-400">Total</p><p class="font-bold text-[#0b2f63]"><?= $totalSesiones ?></p></div>
                    <div><p class="text-[10px] uppercase tracking-wider text-slate-400">Asist.</p><p class="font-bold text-emerald-600"><?= $totalSesiones - $totalInasist ?></p></div>
                    <div><p class="text-[10px] uppercase tracking-wider text-slate-400">Faltas</p><p class="font-bold text-red-600"><?= $totalInasist ?></p></div>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-200 flex justify-between items-center text-sm">
                    <span class="font-semibold text-slate-600">Porcentaje global:</span>
                    <span class="font-bold <?= $pctTone ?>"><?= $porcentajeGlobal ?>%</span>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
        <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600"></i>
        <p class="text-xs text-amber-700">
            <strong>Regla del 30%:</strong> Si el porcentaje de inasistencias en cualquier unidad didáctica es igual o superior al 30%, el estudiante cambia automáticamente a estado <strong>Inhabilitado</strong> en esa unidad. Con ≥20% pasa a <strong>En riesgo</strong>.
        </p>
    </div>
</div>
