<?php
$estudiantes = all_estudiantes();
$programas = all_programas();
$periodos = all_periodos();
$unidades = all_unidades();

function split_student_name(string $name): array
{
    $parts = preg_split('/\s+/', trim($name));
    $count = count($parts);
    if ($count <= 2) {
        return [$name, ''];
    }
    return [implode(' ', array_slice($parts, 0, $count - 2)), implode(' ', array_slice($parts, -2))];
}

function pct_tone(int $pct): string
{
    return $pct >= 30 ? 'bg-red-500' : ($pct >= 20 ? 'bg-amber-500' : 'bg-emerald-500');
}

function pct_text_class(int $pct): string
{
    return $pct >= 30 ? 'text-red-600' : ($pct >= 20 ? 'text-orange-500' : 'text-emerald-600');
}

?>
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <label class="relative block">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input id="filter-search" class="form-control filter-input w-full shrink-0 sm:w-64 pl-9" placeholder="Buscar estudiante...">
            </label>
            <select id="filter-programa" class="form-control filter-select w-48">
                <option value="">Todos los programas</option>
                <?php foreach ($programas as $p): ?>
                    <option value="<?= e($p['nombre']) ?>"><?= e($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filter-ciclo" class="form-control filter-select w-48">
                <option value="">Todos los ciclos</option>
                <option value="I">I</option>
                <option value="II">II</option>
                <option value="III">III</option>
                <option value="IV">IV</option>
                <option value="V">V</option>
                <option value="VI">VI</option>
            </select>
            <select id="filter-estado" class="form-control filter-select w-48">
                <option value="">Todos los estados</option>
                <option value="Activo">Activo</option>
                <option value="En riesgo">En riesgo</option>
                <option value="Inhabilitado">Inhabilitado</option>
            </select>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-modal-target="modal-importar-estudiantes" class="inline-flex items-center gap-2 rounded-lg border border-[#1a3a6b] bg-white px-3 py-2 text-sm font-semibold text-[#1a3a6b] transition hover:bg-blue-50">
                <i data-lucide="upload" class="h-4 w-4"></i>
                Importar CSV
            </button>
            <button type="button" data-modal-target="modal-nuevo-estudiante" class="inline-flex items-center gap-2 rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Nuevo estudiante
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm hidden md:block">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-semibold text-[#1a3a6b]">Lista de Estudiantes</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($estudiantes) ?> registros
            </span>
        </div>
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="table-th w-16 text-center whitespace-nowrap">#</th>
                        <th class="table-th whitespace-nowrap">Código</th>
                        <th class="table-th whitespace-nowrap">Estudiante</th>
                        <th class="table-th whitespace-nowrap">Programa de Estudio</th>
                        <th class="table-th whitespace-nowrap text-center">Ciclo/Sección</th>
                        <th class="table-th whitespace-nowrap">Unidad Didáctica</th>
                        <th class="table-th whitespace-nowrap">% Inasist.</th>
                        <th class="table-th whitespace-nowrap">Estado</th>
                        <th class="table-th whitespace-nowrap text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($estudiantes as $index => $e): ?>
                    <?php
                    $pct = pct($e);
                    $pctWidth = min(($pct / 30) * 100, 100);
                    $pctClass = pct_text_class($pct);
                    $calculatedState = $e['estado'];
                    if ($calculatedState === 'Activo') {
                        $calculatedState = $pct >= 30 ? 'Inhabilitado' : ($pct >= 20 ? 'En riesgo' : 'Activo');
                    }
                    $trClass = $e['estado'] === 'Inhabilitado' ? 'bg-red-50/50' : 'transition hover:bg-slate-50/50';
                    ?>
                    <tr class="<?= $trClass ?> estudiante-row" data-search="<?= strtolower(e($e['codigo'] . ' ' . $e['nombres'] . ' ' . ($e['dni'] ?? ''))) ?>" data-programa="<?= e($e['programa']) ?>" data-ciclo="<?= e($e['ciclo']) ?>" data-estado="<?= e($calculatedState) ?>">
                        <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                        <td class="table-td font-mono text-xs text-slate-500"><?= e($e['codigo']) ?></td>
                        <td class="table-td">
                            <div class="flex flex-col min-w-0">
                                <span class="truncate font-semibold text-slate-900" title="<?= e($e['nombres']) ?>"><?= e($e['nombres']) ?></span>
                                <span class="truncate text-xs text-slate-500">DNI: <?= e($e['dni'] ?? '') ?></span>
                            </div>
                        </td>
                        <td class="table-td text-xs text-slate-500"><?= e($e['programa']) ?></td>
                        <td class="table-td text-center font-medium text-slate-600"><?= e($e['ciclo']) ?>-<?= e($e['seccion']) ?></td>
                        <td class="table-td max-w-32 truncate text-xs text-slate-500" title="<?= e($e['unidad']) ?>"><?= e($e['unidad']) ?></td>
                        <td class="table-td">
                            <div class="flex min-w-[7rem] items-center gap-2" title="<?= e($e['inasistencias']) ?>/<?= e($e['sesiones']) ?> faltas">
                                <div class="h-1.5 min-w-8 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full <?= pct_tone($pct) ?>" style="width: <?= e((string) $pctWidth) ?>%"></div>
                                </div>
                                <span class="w-8 text-right text-xs font-medium <?= e($pctClass) ?>"><?= e($pct) ?>%</span>
                            </div>
                        </td>
                        <td class="table-td">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= badge_class($calculatedState) ?>"><?= e($calculatedState) ?></span>
                        </td>
                        <td class="table-td">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" onclick="openViewModal(<?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)" class="rounded p-1.5 text-blue-600 transition hover:bg-blue-50" title="Ver detalle">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)" class="rounded p-1.5 text-amber-600 transition hover:bg-amber-50" title="Editar">
                                    <i data-lucide="square-pen" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$estudiantes): ?>
                    <tr><td colspan="8" class="py-8 text-center text-slate-500">No hay estudiantes registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Vista móvil (Cards) -->
    <div class="md:hidden mt-2">
        <div class="mb-4 flex items-center justify-between px-2">
            <h2 class="font-semibold text-[#1a3a6b]">Estudiantes</h2>
            <span id="mobile-table-count-badge" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($estudiantes) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php foreach ($estudiantes as $index => $e): ?>
            <?php
            $pct = pct($e);
            $pctWidth = min(($pct / 30) * 100, 100);
            $pctClass = pct_text_class($pct);
            $calculatedState = $e['estado'];
            if ($calculatedState === 'Activo') {
                $calculatedState = $pct >= 30 ? 'Inhabilitado' : ($pct >= 20 ? 'En riesgo' : 'Activo');
            }
            $e['estado'] = $calculatedState;
            ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm estudiante-row flex flex-col gap-3" data-search="<?= strtolower(e($e['codigo'] . ' ' . $e['nombres'] . ' ' . ($e['dni'] ?? ''))) ?>" data-programa="<?= e($e['programa']) ?>" data-ciclo="<?= e($e['ciclo']) ?>" data-estado="<?= e($calculatedState) ?>">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-mono text-xs text-slate-500 mb-1"><span class="text-slate-400 font-bold mr-1 font-sans">#<?= $index + 1 ?></span><?= e($e['codigo']) ?></div>
                        <h3 class="font-semibold text-slate-900 leading-tight"><?= e($e['nombres']) ?></h3>
                        <p class="text-xs text-slate-500 mt-0.5">DNI: <?= e($e['dni'] ?? '') ?></p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold shrink-0 <?= badge_class($calculatedState) ?>"><?= e($calculatedState) ?></span>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-sm text-slate-600">
                    <div>
                        <span class="block text-xs text-slate-400">Programa</span>
                        <span class="font-medium truncate block" title="<?= e($e['programa']) ?>"><?= e($e['programa']) ?></span>
                    </div>
                    <div>
                        <span class="block text-xs text-slate-400">Ciclo/Secc.</span>
                        <span class="font-medium"><?= e($e['ciclo']) ?>-<?= e($e['seccion']) ?></span>
                    </div>
                    <div class="col-span-2">
                        <span class="block text-xs text-slate-400">Unidad Didáctica</span>
                        <span class="font-medium truncate block" title="<?= e($e['unidad']) ?>"><?= e($e['unidad']) ?></span>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1 text-xs">
                        <span class="text-slate-500">Inasistencias (<?= e($e['inasistencias']) ?>/<?= e($e['sesiones']) ?>)</span>
                        <span class="font-medium <?= e($pctClass) ?>"><?= e($pct) ?>%</span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full <?= pct_tone($pct) ?>" style="width: <?= e((string) $pctWidth) ?>%"></div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 mt-1">
                    <button type="button" onclick="openViewModal(<?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-blue-600 transition hover:bg-blue-50" title="Ver detalle">
                        <i data-lucide="eye" class="h-4 w-4"></i> Ver
                    </button>
                    <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-amber-600 transition hover:bg-amber-50" title="Editar">
                        <i data-lucide="square-pen" class="h-4 w-4"></i> Editar
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$estudiantes): ?>
            <div class="py-8 text-center text-slate-500 bg-white rounded-xl border border-slate-200">No hay estudiantes registrados.</div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nuevo Estudiante -->
<div id="modal-nuevo-estudiante" data-modal class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 p-4">
    <div class="mx-auto my-10 max-w-2xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Registrar nuevo estudiante</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form id="form-nuevo-estudiante" onsubmit="handleEstudianteSubmit(event, 'create')">
            <div class="grid gap-4 p-6 md:grid-cols-2">
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Código *</span><input type="text" name="codigo" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">DNI</span><input type="text" name="dni" class="form-control w-full"></label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Nombres Completos *</span><input type="text" name="nombres" class="form-control w-full" required></label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Programa de Estudio *</span>
                    <select name="programa_id" class="form-control w-full" required onchange="filterUnidadesGlobal(this.value, 'select-unidad')">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($programas as $p): ?>
                            <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Ciclo</span>
                    <select name="periodo_curricular_id" class="form-control w-full">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($periodos as $p): ?>
                            <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Sección</span>
                    <select name="seccion" class="form-control w-full">
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                    </select>
                </label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Unidad Didáctica *</span>
                    <select name="unidad_didactica_id" id="select-unidad" class="form-control w-full" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($unidades as $u): ?>
                            <option value="<?= e($u['id']) ?>" data-programa-id="<?= e($u['programa_id']) ?>"><?= e($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Estado académico</span>
                    <select name="estado" class="form-control w-full">
                        <option value="Activo">Activo</option>
                        <option value="En riesgo">En riesgo</option>
                        <option value="Inhabilitado">Inhabilitado</option>
                    </select>
                </label>
            </div>
            <div class="flex gap-2 px-6 pb-6 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Guardar estudiante</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Estudiante -->
<div id="modal-editar-estudiante" data-modal class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 p-4">
    <div class="mx-auto my-10 max-w-2xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Editar estudiante</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form id="form-editar-estudiante" onsubmit="handleEstudianteSubmit(event, 'update')">
            <input type="hidden" name="id" id="edit-id">
            <div class="grid gap-4 p-6 md:grid-cols-2">
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Código *</span><input type="text" name="codigo" id="edit-codigo" class="form-control w-full" required></label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Nombres Completos *</span><input type="text" name="nombres" id="edit-nombres" class="form-control w-full" required></label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Programa de Estudio *</span>
                    <select name="programa_id" id="edit-programa" class="form-control w-full" required onchange="filterUnidadesGlobal(this.value, 'edit-unidad')">
                        <?php foreach ($programas as $p): ?>
                            <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Ciclo</span>
                    <select name="periodo_curricular_id" id="edit-ciclo" class="form-control w-full">
                        <?php foreach ($periodos as $p): ?>
                            <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Sección</span>
                    <select name="seccion" id="edit-seccion" class="form-control w-full">
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                    </select>
                </label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Unidad Didáctica *</span>
                    <select name="unidad_didactica_id" id="edit-unidad" class="form-control w-full" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($unidades as $u): ?>
                            <option value="<?= e($u['id']) ?>" data-programa-id="<?= e($u['programa_id']) ?>"><?= e($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Estado académico</span>
                    <select name="estado" id="edit-estado" class="form-control w-full">
                        <option value="Activo">Activo</option>
                        <option value="En riesgo">En riesgo</option>
                        <option value="Inhabilitado">Inhabilitado</option>
                    </select>
                </label>
            </div>
            <div class="flex gap-2 px-6 pb-6 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Importar -->
<div id="modal-importar-estudiantes" data-modal class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
    <div class="mx-auto mt-24 max-w-xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Importar estudiantes desde CSV</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <div class="p-6">
            <form id="form-importar-csv">
                <div class="relative grid cursor-pointer place-items-center rounded-xl border-2 border-dashed border-slate-300 px-6 py-10 text-center transition hover:bg-slate-50">
                    <i data-lucide="upload" class="h-10 w-10 text-[#1a3a6b]/40"></i>
                    <p class="mt-3 text-sm text-slate-500">Arrastre su archivo aquí o haga clic para seleccionar</p>
                    <input type="file" id="csv-file" accept=".csv" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" required>
                    <div id="file-name-display" class="mt-3 hidden text-sm font-semibold text-[#1a3a6b]"></div>
                </div>
                <p class="mt-4 text-xs text-slate-500">Formato requerido: codigo, DNI, nombres, apellidos, programa, ciclo, seccion, unidad didactica</p>
                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" id="btn-importar-csv" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Importar archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Estudiante -->
<div id="modal-ver-estudiante" data-modal class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
    <div class="mx-auto mt-20 max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Detalle del estudiante</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div id="view-initials" class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-[#1a3a6b] text-lg font-bold text-white"></div>
                <div class="min-w-0 flex-1">
                    <h3 id="view-nombres" class="truncate text-lg font-bold text-[#1a3a6b]"></h3>
                    <p class="truncate text-xs text-slate-500"><span id="view-codigo"></span> · DNI: <span id="view-dni"></span></p>
                    <span id="view-estado" class="mt-1 inline-flex rounded-full px-2 py-1 text-xs font-semibold"></span>
                </div>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Programa</p><p id="view-programa" class="truncate font-semibold text-slate-800"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Ciclo/Sección</p><p id="view-cicloseccion" class="truncate font-semibold text-slate-800"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Unidad Didáctica</p><p id="view-unidad" class="truncate font-semibold text-slate-800"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Asistencia</p><p id="view-asistencia-stats" class="truncate font-semibold text-slate-800"></p></div>
            </div>
            <div class="mt-4">
                <div class="mb-2 flex justify-between text-xs text-slate-500"><span>% Inasistencias</span><span id="view-pct-text"></span></div>
                <div class="h-2 rounded-full bg-slate-100"><div id="view-pct-bar" class="h-full rounded-full bg-emerald-500" style="width: 0%"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

async function apiCall(action, payload) {
    try {
        const response = await fetch('api/estudiantes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action, ...payload })
        });
        
        const data = await response.json();
        if (response.ok && data.success) {
            window.showToast(data.message, 'green');
            setTimeout(() => location.reload(), 1500);
        } else {
            window.showToast(data.error || 'Ocurrió un error procesando la solicitud.', 'red');
        }
    } catch (err) {
        window.showToast('Error de red o servidor no disponible.', 'red');
    }
}

function openEditModal(est) {
    document.getElementById('edit-id').value = est.id;
    document.getElementById('edit-codigo').value = est.codigo;
    document.getElementById('edit-nombres').value = est.nombres;
    document.getElementById('edit-programa').value = est.programa_id;
    document.getElementById('edit-ciclo').value = est.periodo_curricular_id;
    document.getElementById('edit-seccion').value = est.seccion;
    filterUnidadesGlobal(est.programa_id, 'edit-unidad');
    document.getElementById('edit-unidad').value = est.unidad_didactica_id;
    document.getElementById('edit-estado').value = est.estado;
    
    document.getElementById('modal-editar-estudiante').classList.remove('hidden');
}

function openViewModal(est) {
    const parts = est.nombres.trim().split(/\s+/);
    const initials = (parts[0] ? parts[0].charAt(0) : '') + (parts[1] ? parts[1].charAt(0) : '');
    
    document.getElementById('view-initials').textContent = initials.toUpperCase();
    document.getElementById('view-nombres').textContent = est.nombres;
    document.getElementById('view-codigo').textContent = est.codigo;
    document.getElementById('view-dni').textContent = est.dni || 'N/A';
    
    const estadoBadge = document.getElementById('view-estado');
    estadoBadge.textContent = est.estado;
    let badgeClass = 'bg-slate-100 text-slate-700';
    if(est.estado === 'Activo') badgeClass = 'bg-emerald-100 text-emerald-700';
    if(est.estado === 'En riesgo') badgeClass = 'bg-amber-100 text-amber-700';
    if(est.estado === 'Inhabilitado') badgeClass = 'bg-red-100 text-red-700';
    estadoBadge.className = `mt-1 inline-flex rounded-full px-2 py-1 text-xs font-semibold ${badgeClass}`;
    
    document.getElementById('view-programa').textContent = est.programa;
    document.getElementById('view-cicloseccion').textContent = `${est.ciclo}-${est.seccion}`;
    document.getElementById('view-unidad').textContent = est.unidad;
    document.getElementById('view-asistencia-stats').textContent = `${est.inasistencias} inasistencias de ${est.sesiones} sesiones`;
    
    // Pct logic exactly as in PHP pct() function
    const total = parseInt(est.sesiones) || 0;
    const inas = parseInt(est.inasistencias) || 0;
    const pct = total > 0 ? Math.round((inas / total) * 100) : 0;
    
    document.getElementById('view-pct-text').textContent = `${pct}% / 30% límite`;
    
    const pctBar = document.getElementById('view-pct-bar');
    pctBar.style.width = Math.min((pct / 30) * 100, 100) + '%';
    
    let toneClass = 'bg-emerald-500';
    if (pct >= 30) toneClass = 'bg-red-500';
    else if (pct >= 20) toneClass = 'bg-amber-500';
    pctBar.className = `h-full rounded-full ${toneClass}`;
    
    document.getElementById('modal-ver-estudiante').classList.remove('hidden');
}

async function handleEstudianteSubmit(e, action) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    
    btn.disabled = true;
    btn.textContent = 'Procesando...';
    await apiCall(action, payload);
    btn.disabled = false;
    btn.textContent = 'Guardar';
}

// CSV Logic
document.getElementById('csv-file')?.addEventListener('change', function(e) {
    const display = document.getElementById('file-name-display');
    if (this.files && this.files[0]) {
        display.textContent = 'Archivo seleccionado: ' + this.files[0].name;
        display.classList.remove('hidden');
    } else {
        display.classList.add('hidden');
    }
});

document.getElementById('form-importar-csv')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-importar-csv');
    const fileInput = document.getElementById('csv-file');
    
    if (!fileInput.files || !fileInput.files[0]) {
        window.showToast('Por favor seleccione un archivo.', 'amber');
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    try {
        btn.disabled = true;
        btn.textContent = 'Subiendo...';
        
        const response = await fetch('api/importar.php', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const data = await response.json();
        if (response.ok && data.success) {
            window.showToast(data.message, 'green');
            setTimeout(() => location.reload(), 1500);
        } else {
            window.showToast(data.error || 'Ocurrió un error al importar.', 'red');
        }
    } catch (error) {
        window.showToast('Error de conexión.', 'red');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Importar archivo';
    }
});
// Filter logic
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('filter-search');
    const programaSelect = document.getElementById('filter-programa');
    const cicloSelect = document.getElementById('filter-ciclo');
    const estadoSelect = document.getElementById('filter-estado');
    const rows = document.querySelectorAll('.estudiante-row');

    function filterTable() {
        const term = searchInput.value.toLowerCase();
        const prog = programaSelect.value;
        const ciclo = cicloSelect.value;
        const estado = estadoSelect.value;

        rows.forEach(row => {
            const rowSearch = row.getAttribute('data-search');
            const rowProg = row.getAttribute('data-programa');
            const rowCiclo = row.getAttribute('data-ciclo');
            const rowEstado = row.getAttribute('data-estado');

            const matchSearch = rowSearch.includes(term);
            const matchProg = !prog || rowProg === prog;
            const matchCiclo = !ciclo || rowCiclo === ciclo;
            const matchEstado = !estado || rowEstado === estado;

            if (matchSearch && matchProg && matchCiclo && matchEstado) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (programaSelect) programaSelect.addEventListener('change', filterTable);
    if (cicloSelect) cicloSelect.addEventListener('change', filterTable);
    if (estadoSelect) estadoSelect.addEventListener('change', filterTable);
});
</script>
