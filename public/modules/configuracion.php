<?php
require_once __DIR__ . '/../../app/repositories/ConfiguracionRepository.php';

$configRepo = new ConfiguracionRepository(db());
$programas = $configRepo->getEstructuraAcademica();
$periodos_academicos = $configRepo->getPeriodosAcademicos();

$tab = $_GET['tab'] ?? 'periodos';
$prog = $_GET['prog'] ?? 'DSI';

$tabs = [
    'periodos' => 'Periodos Academicos',
    'programas' => 'Programas de Estudio',
    'modulos' => 'Modulos Formativos',
    'ciclos' => 'Periodos y Secciones',
    'unidades' => 'Unidades Didacticas',
    'sesiones' => 'Sesiones Academicas',
    'reglas' => 'Reglas del Sistema',
];
if (!isset($tabs[$tab])) {
    $tab = 'periodos';
}

if (!function_exists('cfg_unit_count')) {
    function cfg_unit_count(array $programa): int
    {
        $total = 0;
        foreach ($programa['modulos'] as $modulo) {
            foreach ($modulo['periodos'] as $periodo) {
                $total += count($periodo['unidades']);
            }
        }
        return $total;
    }
}

if (!function_exists('cfg_first_name')) {
    function cfg_first_name(string $value): string
    {
        return strtok($value, ' ') ?: $value;
    }
}

$activeProgram = $programas[0] ?? null;
foreach ($programas as $programa) {
    if ($programa['codigo'] === $prog) {
        $activeProgram = $programa;
    }
}

$unidades = [];
foreach ($programas as $programa) {
    foreach ($programa['modulos'] as $modulo) {
        foreach ($modulo['periodos'] as $periodo) {
            foreach ($periodo['unidades'] as $unidad) {
                $unidades[] = [
                    'id' => substr(md5($programa['codigo'] . $modulo['num'] . $periodo['nombre'] . $unidad), 0, 10),
                    'programa' => $programa,
                    'modulo' => $modulo,
                    'periodo' => $periodo,
                    'unidad' => $unidad,
                    'estado' => 'Activo',
                ];
            }
        }
    }
}

$stmtSesiones = db()->query("
    SELECT 
        s.id,
        s.periodo, 
        p.codigo AS programa,
        mf.nombre AS modulo,
        pc.nombre AS curr,
        u.nombre AS unidad, 
        s.seccion, 
        d.nombres AS docente, 
        s.fecha, 
        s.hora, 
        s.estado
    FROM sesiones s
    LEFT JOIN programas p ON s.programa_id = p.id
    LEFT JOIN unidades_didacticas u ON s.unidad_didactica_id = u.id
    LEFT JOIN periodos_curriculares pc ON u.periodo_curricular_id = pc.id
    LEFT JOIN modulos_formativos mf ON pc.modulo_id = mf.id
    LEFT JOIN docentes d ON s.docente_id = d.id
    ORDER BY s.fecha DESC, s.hora DESC
");
$sesiones = $stmtSesiones->fetchAll(PDO::FETCH_ASSOC);

$db_programas = db()->query("SELECT id, codigo, nombre FROM programas WHERE estado = 'Activo'")->fetchAll(PDO::FETCH_ASSOC);
$db_unidades = db()->query("SELECT id, nombre FROM unidades_didacticas WHERE estado = 'Activo'")->fetchAll(PDO::FETCH_ASSOC);
$db_docentes = db()->query("SELECT id, nombres FROM docentes WHERE estado = 'Activo'")->fetchAll(PDO::FETCH_ASSOC);
$base = base_url('index.php?m=configuracion');
?>
<div class="space-y-4">
    <div class="flex justify-end gap-3 mb-4">
        <div class="flex-shrink-0 self-end xl:self-auto">
            <?php if ($tab === 'periodos'): ?>
                <?php render_modal_button('+ Nuevo periodo', 'modal-periodo'); ?>
            <?php elseif ($tab === 'programas'): ?>
                <?php render_modal_button('+ Nuevo programa', 'modal-programa-nuevo'); ?>
            <?php elseif ($tab === 'ciclos'): ?>
                <?php render_modal_button('+ Nuevo periodo', 'modal-ciclo'); ?>
            <?php elseif ($tab === 'sesiones'): ?>
                <?php render_modal_button('+ Nueva sesion', 'modal-sesion'); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($tab === 'periodos'): ?>
        <div class="overflow-x-auto overflow-y-hidden min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm hidden md:block">
            <div class="flex items-center justify-between px-5 py-4">
                <h2 class="text-lg font-bold text-[#0b2f63]">Periodos academicos</h2>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 shrink-0 whitespace-nowrap">
                <?= count($periodos_academicos) ?> registro<?= count($periodos_academicos) !== 1 ? 's' : '' ?>
            </span>
            </div>
            <table class="w-full">
                <thead class="border-y border-slate-200 bg-slate-50"><tr><th class="table-th w-16 text-center">#</th><th class="table-th">Periodo Academico</th><th class="table-th">Inicio</th><th class="table-th">Fin</th><th class="table-th">Estado</th><th class="table-th">Acciones</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($periodos_academicos as $index => $pa): ?>
                    <tr>
                        <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                        <td class="table-td font-semibold"><?= e($pa['nombre']) ?></td>
                        <td class="table-td"><?= e(date('d/m/Y', strtotime($pa['fecha_inicio']))) ?></td>
                        <td class="table-td"><?= e(date('d/m/Y', strtotime($pa['fecha_fin']))) ?></td>
                        <td class="table-td">
                            <?php if ($pa['estado'] === 'Activo'): ?>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold <?= badge_class('Activo') ?>">Activo</span>
                            <?php else: ?>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">Cerrado</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-td">
                            <button type="button" onclick="deleteEntityAction('periodo_academico', <?= (int)$pa['id'] ?>)" class="text-red-500 hover:text-red-700 transition" title="Eliminar"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Vista móvil (Cards) para Periodos -->
        <div class="md:hidden">
            <div class="mb-4 flex items-center justify-between px-2">
                <h2 class="font-semibold text-[#1a3a6b]">Periodos académicos</h2>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 shrink-0 whitespace-nowrap">
                <?= count($periodos_academicos) ?> registro<?= count($periodos_academicos) !== 1 ? 's' : '' ?>
            </span>
            </div>
            <div class="grid gap-4">
            <?php foreach ($periodos_academicos as $index => $pa): ?>                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3">
                    <div class="flex items-center gap-3 w-full">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                            <i data-lucide="calendar-days" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 truncate max-w-[65%] block">#<?= $index + 1 ?></span>
                                <?php if ($pa['estado'] === 'Activo'): ?>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold shrink-0 <?= badge_class('Activo') ?>">Activo</span>
                                <?php else: ?>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold shrink-0 text-slate-600">Cerrado</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-slate-900 leading-tight mt-1 truncate"><?= e($pa['nombre']) ?></h3>
                        </div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 flex items-center justify-between border border-slate-100">
                        <div class="flex items-center gap-4 text-sm text-slate-600">
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">Inicio</p>
                                <p class="font-medium text-slate-900"><?= e(date('d/m/Y', strtotime($pa['fecha_inicio']))) ?></p>
                            </div>
                            <div class="border-l border-slate-200 pl-4">
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">Fin</p>
                                <p class="font-medium text-slate-900"><?= e(date('d/m/Y', strtotime($pa['fecha_fin']))) ?></p>
                            </div>
                        </div>
                        <button type="button" onclick="deleteEntityAction('periodo_academico', <?= (int)$pa['id'] ?>)" class="text-red-500 p-2 hover:bg-red-50 rounded-lg transition" title="Eliminar"><i data-lucide="trash-2" class="h-5 w-5"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($tab === 'programas'): ?>
        <div class="overflow-x-auto overflow-y-hidden min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm hidden md:block">
            <div class="flex items-center justify-between px-5 py-4">
                <h2 class="text-lg font-bold text-[#0b2f63]">Programas de estudio</h2>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 shrink-0 whitespace-nowrap">
                <?= count($programas) ?> registro<?= count($programas) !== 1 ? 's' : '' ?>
            </span>
            </div>
            <table class="w-full">
                <thead class="border-y border-slate-200 bg-slate-50"><tr><th class="table-th w-16 text-center">#</th><th class="table-th">Codigo</th><th class="table-th">Programa de Estudio</th><th class="table-th">Modulos</th><th class="table-th">Periodos Curr.</th><th class="table-th">Unidades Did.</th><th class="table-th">Estado</th><th class="table-th">Acciones</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($programas as $index => $programa): ?>
                        <tr>
                            <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                            <td class="table-td font-bold"><?= e($programa['codigo']) ?></td><td class="table-td font-semibold"><?= e($programa['nombre']) ?></td><td class="table-td text-center">3</td><td class="table-td text-center">6</td><td class="table-td text-center"><?= e(cfg_unit_count($programa)) ?></td><td class="table-td"><span class="rounded-full px-2 py-1 text-xs font-semibold <?= badge_class($programa['estado']) ?>"><?= e($programa['estado']) ?></span></td>
                            <td class="table-td"><div class="flex gap-3"><button type="button" data-modal-target="modal-programa-<?= e($programa['codigo']) ?>" class="text-blue-600"><i data-lucide="eye" class="h-4 w-4"></i></button><button type="button" data-modal-target="modal-programa-edit-<?= e($programa['codigo']) ?>" class="text-orange-500"><i data-lucide="square-pen" class="h-4 w-4"></i></button></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Vista móvil (Cards) para Programas -->
        <div class="md:hidden mt-2">
            <div class="mb-4 flex items-center justify-between px-2">
                <h2 class="font-semibold text-[#1a3a6b]">Programas de estudio</h2>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 shrink-0 whitespace-nowrap">
                <?= count($programas) ?> registro<?= count($programas) !== 1 ? 's' : '' ?>
            </span>
            </div>
            <div class="grid gap-4">
            <?php foreach ($programas as $index => $programa): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3">
                    <div class="flex items-center gap-3 w-full">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                            <i data-lucide="graduation-cap" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 truncate max-w-[65%] block">#<?= $index + 1 ?> <?= e($programa['codigo']) ?></span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold shrink-0 <?= badge_class($programa['estado']) ?>"><?= e($programa['estado']) ?></span>
                            </div>
                            <h3 class="font-bold text-slate-900 leading-tight mt-1"><?= e($programa['nombre']) ?></h3>
                        </div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 flex flex-col gap-3 border border-slate-100">
                        <div class="grid grid-cols-3 gap-2 text-center text-sm">
                            <div><p class="text-[10px] uppercase tracking-wider text-slate-400">Módulos</p><p class="font-bold text-[#0b2f63]">3</p></div>
                            <div class="border-l border-slate-200"><p class="text-[10px] uppercase tracking-wider text-slate-400">Periodos</p><p class="font-bold text-[#0b2f63]">6</p></div>
                            <div class="border-l border-slate-200"><p class="text-[10px] uppercase tracking-wider text-slate-400">Unidades</p><p class="font-bold text-[#0b2f63]"><?= e(cfg_unit_count($programa)) ?></p></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <button type="button" data-modal-target="modal-programa-<?= e($programa['codigo']) ?>" class="flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white py-1.5 text-xs font-semibold text-blue-600 shadow-sm transition hover:bg-blue-50">
                            <i data-lucide="eye" class="h-3.5 w-3.5"></i> Ver detalles
                        </button>
                        <button type="button" data-modal-target="modal-programa-edit-<?= e($programa['codigo']) ?>" class="flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white py-1.5 text-xs font-semibold text-orange-500 shadow-sm transition hover:bg-orange-50">
                            <i data-lucide="square-pen" class="h-3.5 w-3.5"></i> Editar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($tab === 'modulos' && $activeProgram): ?>
        <div>
            <label class="block w-full md:w-auto">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Programa de estudio</span>
                <select onchange="window.location.href='<?= e($base . '&tab=modulos&prog=') ?>' + this.value" class="form-control w-full sm:w-auto sm:min-w-[300px]">
                    <?php foreach ($programas as $programa): ?>
                        <option value="<?= e($programa['codigo']) ?>" <?= $activeProgram['codigo'] === $programa['codigo'] ? 'selected' : '' ?>><?= e($programa['codigo']) ?> - <?= e($programa['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="grid gap-4 xl:grid-cols-3">
            <?php foreach ($activeProgram['modulos'] as $modulo): ?>
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">Modulo <?= e($modulo['num']) ?></p><h3 class="font-bold text-[#0b2f63]"><?= e($modulo['nombre']) ?></h3></div>
                    <div class="space-y-3 p-4">
                        <?php foreach ($modulo['periodos'] as $periodo): ?>
                            <div><p class="text-xs font-semibold text-slate-500"><?= e($periodo['nombre']) ?> Periodo</p><ul class="mt-1 space-y-1 text-sm text-slate-600"><?php foreach ($periodo['unidades'] as $unidad): ?><li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#c8a84b]"></span><span><?= e($unidad) ?></span></li><?php endforeach; ?></ul></div>
                        <?php endforeach; ?>
                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Activo</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($tab === 'ciclos'): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-[#0b2f63]">Periodos y secciones</h2></div>
            <div class="mt-4 grid gap-3 xl:grid-cols-3">
                <?php foreach (['V' => ['A', 'B'], 'IV' => ['A', 'B', 'C'], 'III' => ['A', 'B', 'C']] as $periodo => $secciones): ?>
                    <div class="rounded-lg border border-slate-200 p-4"><h3 class="font-bold text-[#0b2f63]">Periodo <?= e($periodo) ?></h3><div class="mt-3 flex gap-2"><?php foreach ($secciones as $seccion): ?><span class="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">Seccion <?= e($seccion) ?></span><?php endforeach; ?></div></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($tab === 'unidades'): ?>
        <div class="space-y-5">
            <div class="flex flex-col md:flex-row md:flex-wrap items-start md:items-end gap-3 w-full">
                <label class="block w-full md:w-auto"><span class="mb-1 block text-xs font-semibold text-slate-600">Programa</span><select id="filter-prog" onchange="window.updateUnidadesFilters()" class="form-control w-full md:w-72"><option value="">-- Todos --</option><?php foreach ($programas as $programa): ?><option value="<?= e($programa['codigo']) ?>"><?= e($programa['nombre']) ?></option><?php endforeach; ?></select></label>
                <label class="block w-full md:w-auto"><span class="mb-1 block text-xs font-semibold text-slate-600">Modulo</span><select id="filter-mod" onchange="window.updateUnidadesFilters()" class="form-control w-full sm:w-auto md:min-w-[150px]"><option value="">-- Todos --</option><option value="1">1</option><option value="2">2</option><option value="3">3</option></select></label>
                <label class="block w-full md:w-auto"><span class="mb-1 block text-xs font-semibold text-slate-600">Periodo curricular</span><select id="filter-per" onchange="window.updateUnidadesFilters()" class="form-control w-full sm:w-auto md:min-w-[150px]"><option value="">-- Todos --</option><option value="I">I</option><option value="II">II</option><option value="III">III</option><option value="IV">IV</option><option value="V">V</option><option value="VI">VI</option></select></label>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
                    <h2 class="font-semibold text-[#1a3a6b]">Lista de Unidades Didácticas</h2>
                    <span id="unidades-count" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                        <?= count($unidades) ?> registros
                    </span>
                </div>
                <div class="overflow-x-auto hidden md:block">
                    <table class="w-full">
                        <thead class="border-y border-slate-200 bg-slate-50"><tr><th class="table-th w-16 text-center">#</th><th class="table-th">Programa</th><th class="table-th">Modulo</th><th class="table-th">Periodo Curr.</th><th class="table-th">Unidad Didactica</th><th class="table-th">Estado</th><th class="table-th">Acciones</th></tr></thead>
                        <tbody id="unidades-tbody" class="divide-y divide-slate-100">
                            <?php foreach ($unidades as $index => $unidad): ?>
                                <tr data-prog="<?= e($unidad['programa']['codigo']) ?>" data-mod="<?= e($unidad['modulo']['num']) ?>" data-per="<?= e($unidad['periodo']['nombre']) ?>"><td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td><td class="table-td"><?= e($unidad['programa']['nombre']) ?></td><td class="table-td">Modulo <?= e($unidad['modulo']['num']) ?></td><td class="table-td"><?= e($unidad['periodo']['nombre']) ?></td><td class="table-td font-semibold"><?= e($unidad['unidad']) ?></td><td class="table-td"><span class="rounded-full px-2 py-1 text-xs font-semibold <?= badge_class($unidad['estado']) ?>"><?= e($unidad['estado']) ?></span></td><td class="table-td"><div class="flex gap-3"><button type="button" data-modal-target="modal-unidad-<?= e($unidad['id']) ?>" class="text-blue-600"><i data-lucide="eye" class="h-4 w-4"></i></button><button type="button" data-modal-target="modal-unidad-edit-<?= e($unidad['id']) ?>" class="text-orange-500"><i data-lucide="square-pen" class="h-4 w-4"></i></button></div></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Vista móvil (Cards) para Unidades -->
                <div class="md:hidden border-t border-slate-100 bg-slate-50/50 p-4">
                    <div class="grid gap-4" id="unidades-mobile-container">
                        <?php foreach ($unidades as $index => $unidad): ?>
                        <div class="unidad-card bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3" 
                             data-prog="<?= e($unidad['programa']['codigo']) ?>" 
                             data-mod="<?= e($unidad['modulo']['num']) ?>" 
                             data-per="<?= e($unidad['periodo']['nombre']) ?>">
                            <div class="flex items-center gap-3 w-full">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                                    <i data-lucide="book-open" class="h-5 w-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2 w-full">
                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 truncate max-w-[65%] block">#<?= $index + 1 ?> <?= e($unidad['programa']['codigo'] ?? $unidad['programa']['nombre']) ?></span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold shrink-0 <?= badge_class($unidad['estado']) ?>"><?= e($unidad['estado']) ?></span>
                                    </div>
                                    <h3 class="font-bold text-slate-900 leading-tight mt-1"><?= e($unidad['unidad']) ?></h3>
                                </div>
                            </div>
                            
                            <div class="rounded-lg bg-slate-50 p-3 flex flex-col gap-3 border border-slate-100">
                                <div class="grid grid-cols-2 gap-2 text-center text-sm">
                                    <div><p class="text-[10px] uppercase tracking-wider text-slate-400">Módulo</p><p class="font-bold text-[#0b2f63]"><?= e($unidad['modulo']['num']) ?></p></div>
                                    <div class="border-l border-slate-200"><p class="text-[10px] uppercase tracking-wider text-slate-400">Per. Curricular</p><p class="font-bold text-[#0b2f63]"><?= e($unidad['periodo']['nombre']) ?></p></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 mt-1">
                                <button type="button" data-modal-target="modal-unidad-<?= e($unidad['id']) ?>" class="flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white py-1.5 text-xs font-semibold text-blue-600 shadow-sm transition hover:bg-blue-50">
                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i> Ver detalles
                                </button>
                                <button type="button" data-modal-target="modal-unidad-edit-<?= e($unidad['id']) ?>" class="flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white py-1.5 text-xs font-semibold text-orange-500 shadow-sm transition hover:bg-orange-50">
                                    <i data-lucide="square-pen" class="h-3.5 w-3.5"></i> Editar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php elseif ($tab === 'sesiones'): ?>
        <div class="overflow-x-auto overflow-y-hidden min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm hidden md:block">
            <div class="flex items-center justify-between px-5 py-4">
                <h2 class="text-lg font-bold text-[#0b2f63]">Sesiones academicas</h2>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 shrink-0 whitespace-nowrap">
                <?= count($sesiones) ?> registro<?= count($sesiones) !== 1 ? 's' : '' ?>
            </span>
            </div>
            <table class="w-full">
                <thead class="border-y border-slate-200 bg-slate-50"><tr><th class="table-th w-16 text-center">#</th><th class="table-th">Per. Academico</th><th class="table-th">Programa</th><th class="table-th">Modulo</th><th class="table-th">Unidad Didactica</th><th class="table-th text-center">Periodo - Sección</th><th class="table-th">Docente</th><th class="table-th">Fecha</th><th class="table-th">Hora</th><th class="table-th">Estado</th></tr></thead>
                <tbody class="divide-y divide-slate-100"><?php foreach ($sesiones as $index => $sesion): ?><?php
                $sec = trim($sesion['seccion']);
                if (preg_match('/[A-Z]$/i', $sec, $matches)) {
                    $sec = strtoupper($matches[0]);
                }
                $sec = $sesion['curr'] . ' - ' . $sec;
                ?><tr><td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td><td class="table-td"><span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700"><?= e($sesion['periodo']) ?></span></td><td class="table-td"><?= e($sesion['programa']) ?></td><td class="table-td"><?= e($sesion['modulo']) ?></td><td class="table-td font-semibold"><?= e($sesion['unidad']) ?></td><td class="table-td font-semibold text-center"><?= e($sec) ?></td><td class="table-td"><?= e($sesion['docente']) ?></td><td class="table-td"><?= e($sesion['fecha']) ?></td><td class="table-td font-semibold"><?= e($sesion['hora']) ?></td><td class="table-td"><span class="rounded-full px-2 py-1 text-xs font-semibold <?= badge_class($sesion['estado']) ?>"><?= e($sesion['estado']) ?></span></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>

        <!-- Vista móvil (Cards) para Sesiones -->
        <div class="md:hidden mt-4">
            <div class="mb-4 flex items-center justify-between px-2">
                <h2 class="font-semibold text-[#1a3a6b]">Gestión de Sesiones Académicas</h2>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 shrink-0 whitespace-nowrap">
                <?= count($sesiones) ?> registro<?= count($sesiones) !== 1 ? 's' : '' ?>
            </span>
            </div>
            <div class="grid gap-4">
            <?php foreach ($sesiones as $index => $sesion): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3">
                    <div class="flex items-center gap-3 w-full">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                            <i data-lucide="clipboard-list" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 truncate max-w-[65%] block">#<?= $index + 1 ?> <?= e($sesion['periodo']) ?></span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold shrink-0 <?= badge_class($sesion['estado']) ?>"><?= e($sesion['estado']) ?></span>
                            </div>
                            <h3 class="font-bold text-slate-900 leading-tight mt-1"><?= e($sesion['unidad']) ?></h3>
                        </div>
                    </div>
                    
                    <div class="rounded-lg bg-slate-50 p-3 flex flex-col gap-2 border border-slate-100">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i data-lucide="user" class="h-4 w-4 text-slate-400 shrink-0"></i>
                            <span class="truncate">Docente: <span class="font-medium text-[#1a3a6b]"><?= e($sesion['docente']) ?></span></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i data-lucide="map-pin" class="h-4 w-4 text-slate-400 shrink-0"></i>
                            <?php
                            $secMovil = trim($sesion['seccion']);
                            if (preg_match('/[A-Z]$/i', $secMovil, $matches)) {
                                $secMovil = strtoupper($matches[0]);
                            }
                            $secMovil = $sesion['curr'] . ' - ' . $secMovil;
                            ?>
                            <span class="truncate">Prog: <?= e($sesion['programa']) ?> <span class="text-slate-400 mx-1">&bull;</span> Sec: <?= e($secMovil) ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-600 border-t border-slate-200 pt-2 mt-1">
                            <i data-lucide="calendar" class="h-4 w-4 text-slate-400 shrink-0"></i>
                            <span class="font-medium text-slate-900"><?= e($sesion['fecha']) ?> <span class="text-slate-400 mx-1">&bull;</span> <?= e($sesion['hora']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($tab === 'reglas'): ?>
        <div class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-[#0b2f63]">Regla de inasistencias</h2>
                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800"><p class="font-semibold">Umbral de inhabilitacion</p><div class="mt-2 flex items-center gap-3"><input id="regla-inhabilitacion" class="form-control w-16 font-bold" value="<?= app_config('regla_inasistencia', 30) ?>"><span>% de inasistencias para inhabilitar</span></div></div>
                <div class="mt-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800"><p class="font-semibold">Umbral de riesgo</p><div class="mt-2 flex items-center gap-3"><input id="regla-riesgo" class="form-control w-16 font-bold" value="<?= app_config('regla_riesgo', 20) ?>"><span>% de inasistencias para "En riesgo"</span></div></div>
                <p class="mt-4 text-xs text-slate-500">Aplica unicamente a estudiantes de todos los programas de estudio.</p>
                <form onsubmit="event.preventDefault(); submitEntity(this, 'configuracion', 'update_regla_inasistencia')">
                    <input type="hidden" name="porcentaje" id="hidden-regla-inhabilitacion" value="<?= app_config('regla_inasistencia', 30) ?>">
                    <input type="hidden" name="riesgo" id="hidden-regla-riesgo" value="<?= app_config('regla_riesgo', 20) ?>">
                    <button type="submit" onclick="document.getElementById('hidden-regla-inhabilitacion').value = document.getElementById('regla-inhabilitacion').value; document.getElementById('hidden-regla-riesgo').value = document.getElementById('regla-riesgo').value" class="mt-4 rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white">Guardar configuracion</button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-[#0b2f63]">Tiempo de edicion de asistencia</h2>
                <div class="mt-4 rounded-lg border border-blue-300 bg-blue-50 p-4 text-sm text-blue-800"><p>El docente puede editar la asistencia durante:</p><div class="mt-3 flex items-center gap-3"><input id="regla-tiempo-edicion" class="form-control w-16 font-bold" value="<?= app_config('tiempo_edicion', 24) ?>"><span>horas despues del registro</span></div><p class="mt-3 text-xs">Pasado este tiempo, solo el Administrador puede modificar.</p></div>
                <form onsubmit="event.preventDefault(); submitEntity(this, 'configuracion', 'update_tiempo_edicion')">
                    <input type="hidden" name="horas" id="hidden-regla-tiempo" value="<?= app_config('tiempo_edicion', 24) ?>">
                    <button type="submit" onclick="document.getElementById('hidden-regla-tiempo').value = document.getElementById('regla-tiempo-edicion').value" class="mt-4 rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white">Guardar configuracion</button>
                </form>            </div>
        </div>
    <?php endif; ?>
</div>
<div id="modal-periodo" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm p-4 transition-opacity overflow-y-auto">
    <div class="w-full mx-auto mt-24 max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
            <h2 class="font-bold text-[#0b2f63]">Nuevo periodo académico</h2>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form onsubmit="event.preventDefault(); submitEntity(this, 'periodo_academico', 'create')">
        <div class="space-y-4 p-6">
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Código del periodo (ej: 2026-II)</span>
                <input name="nombre" class="form-control bg-slate-50 focus:bg-white transition-colors" value="2026-II" required>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Fecha de inicio</span>
                <input type="date" name="fecha_inicio" class="form-control bg-slate-50 focus:bg-white transition-colors">
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Fecha de fin</span>
                <input type="date" name="fecha_fin" class="form-control bg-slate-50 focus:bg-white transition-colors">
            </label>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
            <button type="submit" class="rounded-xl bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#142d54] transition-colors">
                Guardar periodo
            </button>
        </div>
        </form>
    </div>
</div>

<div id="modal-programa-nuevo" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm p-4 transition-opacity overflow-y-auto">
    <div class="w-full mx-auto mt-24 max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
            <h2 class="font-bold text-[#0b2f63]">Nuevo programa de estudio</h2>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form onsubmit="event.preventDefault(); submitEntity(this, 'programa', 'create')">
        <div class="space-y-4 p-6">
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Código</span>
                <input name="codigo" class="form-control bg-slate-50 focus:bg-white transition-colors" required>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Nombre</span>
                <input name="nombre" class="form-control bg-slate-50 focus:bg-white transition-colors" required>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Estado</span>
                <select name="estado" class="form-control bg-slate-50 focus:bg-white transition-colors">
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </label>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
            <button type="submit" class="rounded-xl bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#142d54] transition-colors">
                Guardar programa
            </button>
        </div>
        </form>
    </div>
</div>

<div id="modal-ciclo" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm p-4 transition-opacity overflow-y-auto">
    <div class="w-full mx-auto mt-24 max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
            <h2 class="font-bold text-[#0b2f63]">Nuevo periodo académico</h2>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="form-ciclo" onsubmit="event.preventDefault(); submitEntity(this, 'ciclo', 'create');">
        <div class="space-y-4 p-6">
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Nombre del periodo (ej: VII)</span>
                <input id="input-ciclo-nombre" oninput="updateCicloPreview()" class="form-control bg-slate-50 focus:bg-white transition-colors" name="nombre" value="VII" required>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Secciones (separadas por coma)</span>
                <input id="input-ciclo-secciones" oninput="updateCicloPreview()" class="form-control bg-slate-50 focus:bg-white transition-colors" value="A,B">
            </label>
            <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 text-xs text-blue-700">
                <span class="font-semibold block mb-2">Vista previa:</span>
                <span id="preview-ciclo-nombre">VII</span> — con secciones: 
                <span id="preview-ciclo-secciones">
                    <span class="rounded-md bg-blue-100 px-2.5 py-1 font-bold shadow-sm">A</span> <span class="rounded-md bg-blue-100 px-2.5 py-1 font-bold shadow-sm">B</span>
                </span>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
            <button type="submit" class="rounded-xl bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#142d54] transition-colors">
                Guardar periodo
            </button>
        </div>
        <script>
            function updateCicloPreview() {
                const nombre = document.getElementById('input-ciclo-nombre').value || 'Periodo';
                const seccionesStr = document.getElementById('input-ciclo-secciones').value;
                document.getElementById('preview-ciclo-nombre').textContent = nombre;
                
                const contenedor = document.getElementById('preview-ciclo-secciones');
                contenedor.innerHTML = '';
                
                if (seccionesStr.trim() !== '') {
                    const secciones = seccionesStr.split(',').map(s => s.trim()).filter(s => s !== '');
                    secciones.forEach(sec => {
                        const span = document.createElement('span');
                        span.className = 'rounded-md bg-blue-100 px-2.5 py-1 font-bold shadow-sm mr-1 inline-block mt-1';
                        span.textContent = sec;
                        contenedor.appendChild(span);
                    });
                }
            }
        </script>
        </form>
    </div>
</div>

<div id="modal-sesion" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm p-4 transition-opacity overflow-y-auto">
    <div class="w-full mx-auto mt-16 max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
            <h2 class="font-bold text-[#0b2f63]">Nueva sesión académica</h2>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form onsubmit="event.preventDefault(); submitEntity(this, 'sesion', 'create')">
        <div class="grid gap-5 p-6 md:grid-cols-2">
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Periodo académico</span>
                <select name="periodo" class="form-control bg-slate-50 focus:bg-white transition-colors" required>
                    <?php foreach ($periodos_academicos as $pa): ?>
                    <option value="<?= e($pa['nombre']) ?>"><?= e($pa['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Programa</span>
                <select name="programa_id" class="form-control bg-slate-50 focus:bg-white transition-colors" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($db_programas as $p): ?>
                    <option value="<?= e($p['id']) ?>"><?= e($p['codigo']) ?> - <?= e($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Unidad Didáctica</span>
                <select name="unidad_id" class="form-control bg-slate-50 focus:bg-white transition-colors" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($db_unidades as $u): ?>
                    <option value="<?= e($u['id']) ?>"><?= e($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Docente</span>
                <select name="docente_id" class="form-control bg-slate-50 focus:bg-white transition-colors" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($db_docentes as $d): ?>
                    <option value="<?= e($d['id']) ?>"><?= e($d['nombres']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Sección</span>
                <input name="seccion" class="form-control bg-slate-50 focus:bg-white transition-colors" placeholder="Ej: V-B" required>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Fecha</span>
                <input name="fecha" type="date" class="form-control bg-slate-50 focus:bg-white transition-colors" required>
            </label>
            <label class="block w-full md:w-auto">
                <span class="mb-1.5 block text-xs font-semibold text-slate-700">Hora</span>
                <input name="hora" type="time" class="form-control bg-slate-50 focus:bg-white transition-colors" required>
            </label>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-4 col-span-2">
            <button type="submit" class="rounded-xl bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#142d54] transition-colors">
                Guardar sesión
            </button>
        </div>
        </form>
    </div>
</div>

<?php foreach ($programas as $programa): ?>
    <div id="modal-programa-<?= e($programa['codigo']) ?>" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm p-4 transition-opacity overflow-y-auto">
        <div class="w-full mx-auto mt-24 max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <h2 class="font-bold text-[#0b2f63]"><?= e($programa['codigo']) ?> — <?= e($programa['nombre']) ?></h2>
                <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <div class="space-y-3 p-6 text-sm text-slate-600">
                <div class="flex justify-between border-b border-slate-100 pb-3"><span>Módulos Formativos</span><strong class="text-slate-900">3</strong></div>
                <div class="flex justify-between border-b border-slate-100 py-3"><span>Periodos Curriculares</span><strong class="text-slate-900">6</strong></div>
                <div class="flex justify-between border-b border-slate-100 py-3"><span>Unidades Didácticas</span><strong class="text-slate-900"><?= e(cfg_unit_count($programa)) ?></strong></div>
                <div class="flex items-center justify-between pt-3">
                    <span>Estado</span>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Activo
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div id="modal-programa-edit-<?= e($programa['codigo']) ?>" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm p-4 transition-opacity overflow-y-auto">
        <div class="w-full mx-auto mt-24 max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <h2 class="font-bold text-[#0b2f63]">Editar programa</h2>
                <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <form onsubmit="event.preventDefault(); submitEntity(this, 'programa', 'update', {id: <?= e($programa['id']) ?>})">
            <div class="space-y-4 p-6">
                <label class="block w-full md:w-auto">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Código</span>
                    <input name="codigo" class="form-control bg-slate-50 focus:bg-white transition-colors" value="<?= e($programa['codigo']) ?>" required>
                </label>
                <label class="block w-full md:w-auto">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Nombre del Programa</span>
                    <input name="nombre" class="form-control bg-slate-50 focus:bg-white transition-colors" value="<?= e($programa['nombre']) ?>" required>
                </label>
                <label class="block w-full md:w-auto">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Estado</span>
                    <select name="estado" class="form-control bg-slate-50 focus:bg-white transition-colors">
                        <option value="Activo" <?= $programa['estado'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= $programa['estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </label>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                <button type="submit" class="rounded-xl bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#142d54] transition-colors">
                    Guardar cambios
                </button>
            </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php foreach ($unidades as $unidad): ?>
    <div id="modal-unidad-<?= e($unidad['id']) ?>" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm p-4 transition-opacity overflow-y-auto">
        <div class="w-full mx-auto mt-24 max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <h2 class="font-bold text-[#0b2f63] truncate">Unidad Didáctica</h2>
                <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <div class="space-y-3 p-6 text-sm text-slate-600">
                <div class="flex justify-between border-b border-slate-100 pb-3">
                    <span>Nombre de Unidad</span>
                    <strong class="max-w-[200px] text-right truncate text-slate-900" title="<?= e($unidad['unidad']) ?>"><?= e($unidad['unidad']) ?></strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 py-3">
                    <span>Programa de Estudio</span>
                    <strong class="max-w-[200px] text-right truncate text-slate-900" title="<?= e($unidad['programa']['nombre']) ?>"><?= e($unidad['programa']['nombre']) ?></strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 py-3">
                    <span>Módulo Formativo</span>
                    <strong class="text-slate-900">Módulo <?= e($unidad['modulo']['num']) ?></strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 py-3">
                    <span>Periodo Curricular</span>
                    <strong class="text-slate-900"><?= e($unidad['periodo']['nombre']) ?> Periodo</strong>
                </div>
                <div class="flex items-center justify-between pt-3">
                    <span>Estado</span>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Activa
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div id="modal-unidad-edit-<?= e($unidad['id']) ?>" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm p-4 transition-opacity overflow-y-auto">
        <div class="w-full mx-auto mt-24 max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <h2 class="font-bold text-[#0b2f63]">Editar Unidad Didáctica</h2>
                <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <form onsubmit="event.preventDefault(); submitEntity(this, 'unidad', 'update', {id: <?= e($unidad['id']) ?>})">
            <div class="space-y-4 p-6">
                <label class="block w-full md:w-auto">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Nombre de la unidad</span>
                    <input name="nombre" class="form-control bg-slate-50 focus:bg-white transition-colors" value="<?= e($unidad['unidad']) ?>" required>
                </label>
                <label class="block w-full md:w-auto">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Programa de estudio</span>
                    <select class="form-control bg-slate-50 focus:bg-white transition-colors" disabled>
                        <option><?= e($unidad['programa']['nombre']) ?></option>
                    </select>
                </label>
                <label class="block w-full md:w-auto">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Módulo</span>
                    <select class="form-control bg-slate-50 focus:bg-white transition-colors" disabled>
                        <option>Módulo <?= e($unidad['modulo']['num']) ?></option>
                    </select>
                </label>
                <label class="block w-full md:w-auto">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Estado</span>
                    <select name="estado" class="form-control bg-slate-50 focus:bg-white transition-colors">
                        <option value="Activo" <?= $unidad['estado'] === 'Activo' ? 'selected' : '' ?>>Activa</option>
                        <option value="Inactivo" <?= $unidad['estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactiva</option>
                    </select>
                </label>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                <button type="submit" class="rounded-xl bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#142d54] transition-colors">
                    Guardar cambios
                </button>
            </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<script>
async function submitEntity(form, entity, action, extraData = {}) {
    const formData = new FormData(form);
    const data = { action, entity, ...extraData };
    formData.forEach((value, key) => data[key] = value);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch('api/configuracion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok && result.success) {
            window.showToast(result.message, 'green');
            if (form.closest('.fixed')) {
                form.closest('.fixed').classList.add('hidden');
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            window.showToast(result.error || 'Ocurrió un error', 'red');
        }
    } catch (e) {
        window.showToast('Error de conexión', 'red');
    }
}

async function deleteEntityAction(entity, id) {
    const result = await Swal.fire({
        title: '¿Eliminar registro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch('api/configuracion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'delete', entity, id })
        });
        const res = await response.json();
        if (response.ok && res.success) {
            window.showToast(res.message, 'green');
            setTimeout(() => location.reload(), 1500);
        } else {
            window.showToast(res.error || 'Ocurrió un error', 'red');
        }
    } catch (e) {
        window.showToast('Error de conexión', 'red');
    }
}
async function submitCiclo(form) {
    submitEntity(form, 'ciclo', 'create');
}

// Unidades filtering logic
window.updateUnidadesFilters = () => {
    try {
        const filterProg = document.getElementById('filter-prog');
        const filterMod = document.getElementById('filter-mod');
        const filterPer = document.getElementById('filter-per');
        const tbody = document.getElementById('unidades-tbody');
        
        if (!filterProg || !filterMod || !filterPer || !tbody) return;

        const vProg = (filterProg.value || '').toLowerCase();
        const vMod = (filterMod.value || '').toLowerCase();
        const vPer = (filterPer.value || '').toLowerCase();
        let visibleCount = 0;
        
        const rows = tbody.querySelectorAll('tr');
        const cards = document.querySelectorAll('.unidad-card');
        
        const filterItem = (item) => {
            const rProg = (item.getAttribute('data-prog') || '').toLowerCase();
            const rMod = (item.getAttribute('data-mod') || '').toLowerCase();
            const rPer = (item.getAttribute('data-per') || '').toLowerCase();
            
            const matchProg = !vProg || rProg === vProg;
            const matchMod = !vMod || rMod === vMod;
            const matchPer = !vPer || rPer === vPer;
            
            if (matchProg && matchMod && matchPer) {
                item.style.display = '';
                return true;
            } else {
                item.style.display = 'none';
                return false;
            }
        };

        for (let i = 0; i < rows.length; i++) {
            if (filterItem(rows[i])) visibleCount++;
        }
        
        if (cards) {
            for (let i = 0; i < cards.length; i++) {
                filterItem(cards[i]);
            }
        }
        
        const countEl = document.getElementById('unidades-count');
        if(countEl) {
            countEl.textContent = visibleCount + " registro" + (visibleCount !== 1 && visibleCount !== "1" ? "s" : "");
        }
    } catch (e) {
        console.error("Filter error:", e);
    }
};

setTimeout(() => { window.updateUnidadesFilters(); }, 500);

</script>
