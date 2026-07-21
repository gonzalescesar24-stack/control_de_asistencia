<?php
$docentes = all_docentes();
$programas = all_programas();
$unidades = all_unidades();$usuarios = fetch_all('SELECT usuario, nombre FROM usuarios WHERE rol = "docente" AND estado = "Activo"');

function split_teacher_name(string $name): array
{
    $parts = preg_split('/\s+/', trim($name));
    $count = count($parts);
    if ($count <= 2) {
        return [$name, ''];
    }
    return [implode(' ', array_slice($parts, 0, $count - 2)), implode(' ', array_slice($parts, -2))];
}
?>
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <label class="relative block">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input id="filter-search" class="form-control filter-input w-full shrink-0 sm:w-64 pl-9" placeholder="Buscar docente...">
            </label>
            <select id="filter-programa" class="form-control filter-select w-48">
                <option value="">Todos los programas</option>
                <?php foreach ($programas as $p): ?>
                    <option value="<?= e($p['nombre']) ?>"><?= e($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filter-estado" class="form-control filter-select w-48">
                <option value="">Todos los estados</option>
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
            </select>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-modal-target="modal-importar-docentes" class="inline-flex items-center gap-2 rounded-lg border border-[#1a3a6b] bg-white px-3 py-2 text-sm font-semibold text-[#1a3a6b] transition hover:bg-blue-50">
                <i data-lucide="upload" class="h-4 w-4"></i>
                Importar CSV
            </button>
            <?php render_modal_button('+  Nuevo docente', 'modal-nuevo-docente'); ?>
        </div>
    </div>

    <div class="overflow-x-auto overflow-y-hidden min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm hidden md:block">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-semibold text-[#1a3a6b]">Lista de Docentes</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($docentes) ?> registros
            </span>
        </div>
        <table class="w-full min-w-[1000px]">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-th w-16 text-center">#</th>
                    <th class="table-th w-24">Codigo</th>
                    <th class="table-th w-1/4">Docente</th>
                    <th class="table-th w-1/4">Contacto</th>
                    <th class="table-th w-1/4">Programa / Unidad</th>
                    <th class="table-th whitespace-nowrap text-center">Ciclo/Sección</th>
                    <th class="table-th text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($docentes as $index => $d): ?>
                <tr class="transition hover:bg-slate-50/50 docente-row" data-search="<?= strtolower(e($d['codigo'] . ' ' . $d['nombres'] . ' ' . ($d['dni'] ?? '') . ' ' . ($d['correo'] ?? ''))) ?>" data-programa="<?= e($d['programa'] ?? '') ?>" data-estado="<?= e($d['estado'] ?? 'Activo') ?>">
                    <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                    <td class="table-td font-mono text-xs text-[#1a3a6b]"><?= e($d['codigo']) ?></td>
                    <td class="table-td">
                        <div class="flex flex-col min-w-0">
                            <span class="truncate font-semibold text-slate-900" title="<?= e($d['nombres']) ?>"><?= e($d['nombres']) ?></span>
                            <span class="truncate text-xs text-slate-500">DNI: <?= e($d['dni']) ?></span>
                        </div>
                    </td>
                    <td class="table-td">
                        <div class="flex flex-col min-w-0">
                            <span class="truncate text-sm text-slate-700" title="<?= e($d['correo']) ?>"><?= e($d['correo']) ?></span>
                            <span class="truncate text-xs text-[#1a3a6b]">User: <?= e($d['usuario']) ?: 'N/A' ?></span>
                        </div>
                    </td>
                    <td class="table-td">
                        <div class="flex flex-col min-w-0">
                            <span class="truncate text-sm font-medium text-slate-700" title="<?= e($d['programa']) ?>"><?= e($d['programa']) ?></span>
                            <?php if (!empty($d['unidad'])): ?>
                                <span class="truncate text-xs text-slate-500" title="<?= e($d['unidad']) ?>"><?= e($d['unidad']) ?> - <?= e($d['ciclo']) ?>-<?= e($d['seccion']) ?></span>
                            <?php else: ?>
                                <span class="truncate text-xs text-slate-400 italic" title="Sin Unidad Didáctica Asignada">Sin Unidad Didáctica Asignada</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="table-td text-center"><span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= badge_class($d['estado']) ?>"><?= e($d['estado']) ?></span></td>
                    <td class="table-td">
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" class="text-blue-600 transition hover:text-blue-800" onclick="openViewModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)" title="Ver"><i data-lucide="eye" class="h-4 w-4"></i></button>
                            <button type="button" class="text-orange-500 transition hover:text-orange-700" onclick="openEditModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)" title="Editar"><i data-lucide="square-pen" class="h-4 w-4"></i></button>
                            <?php if ($d['estado'] === 'Activo'): ?>
                                <button type="button" class="text-red-500 transition hover:text-red-700" onclick="toggleStatus(<?= $d['id'] ?>, 'Inactivo')" title="Desactivar"><i data-lucide="circle-x" class="h-4 w-4"></i></button>
                            <?php else: ?>
                                <button type="button" class="text-emerald-500 transition hover:text-emerald-700" onclick="toggleStatus(<?= $d['id'] ?>, 'Activo')" title="Activar"><i data-lucide="check-circle" class="h-4 w-4"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$docentes): ?>
                <tr><td colspan="6" class="py-8 text-center text-slate-500">No hay docentes registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Vista móvil (Cards) -->
    <div class="md:hidden mt-2">
        <div class="mb-4 flex items-center justify-between px-2">
            <h2 class="font-semibold text-[#1a3a6b]">Docentes</h2>
            <span id="mobile-table-count-badge" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($docentes) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php foreach ($docentes as $index => $d): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm docente-row flex flex-col gap-3" data-search="<?= strtolower(e($d['codigo'] . ' ' . $d['nombres'] . ' ' . ($d['dni'] ?? '') . ' ' . ($d['correo'] ?? ''))) ?>" data-programa="<?= e($d['programa'] ?? '') ?>" data-estado="<?= e($d['estado'] ?? 'Activo') ?>">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-mono text-xs text-[#1a3a6b] mb-1"><span class="text-slate-400 font-bold mr-1 font-sans">#<?= $index + 1 ?></span><?= e($d['codigo']) ?></div>
                        <h3 class="font-semibold text-slate-900 leading-tight"><?= e($d['nombres']) ?></h3>
                        <p class="text-xs text-slate-500 mt-0.5">DNI: <?= e($d['dni']) ?></p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold shrink-0 <?= badge_class($d['estado']) ?>"><?= e($d['estado']) ?></span>
                </div>
                
                <div class="grid gap-2 text-sm text-slate-600">
                    <div>
                        <span class="block text-xs text-slate-400">Contacto</span>
                        <span class="font-medium block truncate" title="<?= e($d['correo']) ?>"><?= e($d['correo']) ?: 'Sin correo' ?></span>
                        <span class="text-xs text-[#1a3a6b]">User: <?= e($d['usuario']) ?: 'N/A' ?></span>
                    </div>
                    <div>
                        <span class="block text-xs text-slate-400">Programa / Unidad</span>
                        <span class="font-medium block truncate" title="<?= e($d['programa']) ?>"><?= e($d['programa']) ?: 'Sin programa' ?></span>
                        <?php if (!empty($d['unidad'])): ?>
                            <span class="text-xs text-slate-500 block truncate" title="<?= e($d['unidad']) ?>"><?= e($d['unidad']) ?> - <?= e($d['ciclo']) ?>-<?= e($d['seccion']) ?></span>
                        <?php else: ?>
                            <span class="text-xs text-slate-400 italic block">Sin Unidad Didáctica Asignada</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 mt-1">
                    <button type="button" onclick="openViewModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-blue-600 transition hover:bg-blue-50" title="Ver">
                        <i data-lucide="eye" class="h-4 w-4"></i> Ver
                    </button>
                    <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-orange-500 transition hover:bg-orange-50" title="Editar">
                        <i data-lucide="square-pen" class="h-4 w-4"></i> Editar
                    </button>
                    <?php if ($d['estado'] === 'Activo'): ?>
                        <button type="button" onclick="toggleStatus(<?= $d['id'] ?>, 'Inactivo')" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-50" title="Desactivar">
                            <i data-lucide="circle-x" class="h-4 w-4"></i> Desactivar
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="toggleStatus(<?= $d['id'] ?>, 'Activo')" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-emerald-500 transition hover:bg-emerald-50" title="Activar">
                            <i data-lucide="check-circle" class="h-4 w-4"></i> Activar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$docentes): ?>
            <div class="py-8 text-center text-slate-500 bg-white rounded-xl border border-slate-200">No hay docentes registrados.</div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nuevo Docente -->
<div id="modal-nuevo-docente" data-modal class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 p-4">
    <div class="mx-auto my-10 max-w-2xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Registrar nuevo docente</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form id="form-nuevo-docente" onsubmit="handleDocenteSubmit(event, 'create')">
            <div class="grid gap-4 p-6 md:grid-cols-2">
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Código *</span><input type="text" name="codigo" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">DNI</span><input type="text" name="dni" class="form-control w-full"></label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Nombres Completos *</span><input type="text" name="nombres" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Correo</span><input type="email" name="correo" class="form-control w-full"></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Usuario (para acceso al sistema)</span>
                    <input type="text" name="usuario" class="form-control w-full" placeholder="Ej: jvargas (Opcional)">
                </label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Programa de Estudio principal</span>
                    <select name="programa_id" class="form-control w-full" onchange="filterUnidadesGlobal(this.value, 'select-unidad')">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($programas as $p): ?>
                            <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Unidad Didáctica habitual</span>
                    <select name="unidad_didactica_id" id="select-unidad" class="form-control w-full">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($unidades as $u): ?>
                            <option value="<?= e($u['id']) ?>" data-programa-id="<?= e($u['programa_id']) ?>"><?= e($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Ciclo/Sección</span>
                    <select name="seccion" class="form-control w-full">
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                    </select>
                </label>
            </div>
            <div class="flex gap-2 px-6 pb-6 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Guardar docente</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Docente -->
<div id="modal-editar-docente" data-modal class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 p-4">
    <div class="mx-auto my-10 max-w-2xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Editar docente</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form id="form-editar-docente" onsubmit="handleDocenteSubmit(event, 'update')">
            <input type="hidden" name="id" id="edit-id">
            <div class="grid gap-4 p-6 md:grid-cols-2">
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Código *</span><input type="text" name="codigo" id="edit-codigo" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">DNI</span><input type="text" name="dni" id="edit-dni" class="form-control w-full"></label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Nombres Completos *</span><input type="text" name="nombres" id="edit-nombres" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Correo</span><input type="email" name="correo" id="edit-correo" class="form-control w-full"></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Usuario (para acceso al sistema)</span>
                    <input type="text" id="edit-usuario" name="usuario" class="form-control w-full" placeholder="Ej: jvargas (Opcional)">
                </label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Programa de Estudio principal</span>
                    <select name="programa_id" id="edit-programa" class="form-control w-full" onchange="filterUnidadesGlobal(this.value, 'edit-unidad')">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($programas as $p): ?>
                            <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Unidad Didáctica habitual</span>
                    <select name="unidad_didactica_id" id="edit-unidad" class="form-control w-full">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($unidades as $u): ?>
                            <option value="<?= e($u['id']) ?>" data-programa-id="<?= e($u['programa_id']) ?>"><?= e($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Ciclo/Sección</span>
                    <select name="seccion" id="edit-seccion" class="form-control w-full">
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                    </select>
                </label>
            </div>
            <div class="flex gap-2 px-6 pb-6 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver Docente -->
<div id="modal-ver-docente" data-modal class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
    <div class="mx-auto mt-20 max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><h2 class="font-bold text-[#1a3a6b]">Detalle del docente</h2><button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button></div>
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
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Programa</p><p id="view-programa" class="truncate font-semibold"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Unidad Didáctica</p><p id="view-unidad" class="truncate font-semibold"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Ciclo/Sección</p><p id="view-seccion" class="truncate font-semibold"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Correo</p><p id="view-correo" class="truncate font-semibold"></p></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Importar Docentes -->
<div id="modal-importar-docentes" data-modal class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
    <div class="mx-auto mt-24 max-w-xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Importar docentes desde CSV</h2>
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
                <p class="mt-4 text-xs text-slate-500">Formato requerido: nombres, dni, correo</p>
                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" id="btn-importar-csv" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Importar archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

async function apiCall(action, payload) {
    try {
        const response = await fetch('api/docentes.php', {
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

function openEditModal(docente) {
    document.getElementById('edit-id').value = docente.id;
    document.getElementById('edit-codigo').value = docente.codigo;
    document.getElementById('edit-dni').value = docente.dni;
    document.getElementById('edit-nombres').value = docente.nombres;
    document.getElementById('edit-correo').value = docente.correo;
    document.getElementById('edit-usuario').value = docente.usuario || '';
    document.getElementById('edit-programa').value = docente.programa_id || '';
    filterUnidadesGlobal(docente.programa_id, 'edit-unidad');
    document.getElementById('edit-unidad').value = docente.unidad_didactica_id || '';
    document.getElementById('edit-seccion').value = docente.seccion || '';
    
    document.getElementById('modal-editar-docente').classList.remove('hidden');
}

function openViewModal(docente) {
    // Generate initials safely
    const parts = docente.nombres.trim().split(/\s+/);
    const initials = (parts[0] ? parts[0].charAt(0) : '') + (parts[1] ? parts[1].charAt(0) : '');
    
    document.getElementById('view-initials').textContent = initials.toUpperCase();
    document.getElementById('view-nombres').textContent = docente.nombres;
    document.getElementById('view-codigo').textContent = docente.codigo;
    document.getElementById('view-dni').textContent = docente.dni || 'No registrado';
    
    const estadoBadge = document.getElementById('view-estado');
    estadoBadge.textContent = docente.estado;
    estadoBadge.className = `mt-1 inline-flex rounded-full px-2 py-1 text-xs font-semibold ${docente.estado === 'Activo' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`;
    
    document.getElementById('view-programa').textContent = docente.programa || 'No asignado';
    document.getElementById('view-unidad').textContent = docente.unidad || 'N/A';
    document.getElementById('view-seccion').textContent = (docente.ciclo ? docente.ciclo + '-' : '') + (docente.seccion || 'N/A');
    document.getElementById('view-correo').textContent = docente.correo || 'N/A';
    
    document.getElementById('modal-ver-docente').classList.remove('hidden');
}

async function handleDocenteSubmit(e, action) {
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

function toggleStatus(id, estado) {
    Swal.fire({
        title: '¿Cambiar estado?',
        text: `El docente pasará a estado ${estado}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1a3a6b',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'No, cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            apiCall('toggle_status', { id, estado });
        }
    });
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
    formData.append('entidad', 'docentes');

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
    const estadoSelect = document.getElementById('filter-estado');
    const rows = document.querySelectorAll('.docente-row');

    function filterTable() {
        const term = searchInput.value.toLowerCase();
        const prog = programaSelect.value;
        const estado = estadoSelect.value;

        rows.forEach(row => {
            const rowSearch = row.getAttribute('data-search');
            const rowProg = row.getAttribute('data-programa');
            const rowEstado = row.getAttribute('data-estado');

            const matchSearch = rowSearch.includes(term);
            const matchProg = !prog || rowProg === prog;
            const matchEstado = !estado || rowEstado === estado;

            if (matchSearch && matchProg && matchEstado) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (programaSelect) programaSelect.addEventListener('change', filterTable);
    if (estadoSelect) estadoSelect.addEventListener('change', filterTable);
});
</script>
