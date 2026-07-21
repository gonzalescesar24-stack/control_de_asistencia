<?php
$horarios = fetch_all('SELECT h.id, h.seccion, h.dia_semana, h.hora_inicio, h.hora_fin, p.nombre as programa, ud.nombre as unidad, pc.nombre as ciclo, d.nombres as docente FROM horarios h LEFT JOIN programas p ON h.programa_id = p.id LEFT JOIN unidades_didacticas ud ON h.unidad_didactica_id = ud.id LEFT JOIN periodos_curriculares pc ON ud.periodo_curricular_id = pc.id LEFT JOIN docentes d ON h.docente_id = d.id ORDER BY h.dia_semana, h.hora_inicio');
$programas = all_programas();
$unidades = all_unidades();
$docentes = fetch_all('SELECT id, nombres, programa_id FROM docentes ORDER BY nombres');
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <label class="relative block">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input id="filter-search" class="form-control filter-input w-full shrink-0 sm:w-64 pl-9" placeholder="Buscar horario...">
            </label>
            <select id="filter-programa" class="form-control filter-select w-48">
                <option value="">Todos los programas</option>
                <?php foreach ($programas as $p): ?>
                    <option value="<?= e($p['nombre']) ?>"><?= e($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex flex-wrap gap-2 ml-auto">
            <button type="button" data-modal-target="modal-importar-horarios" class="inline-flex items-center gap-2 rounded-lg border border-[#1a3a6b] bg-white px-3 py-2 text-sm font-semibold text-[#1a3a6b] transition hover:bg-blue-50">
                <i data-lucide="upload" class="h-4 w-4"></i>
                Importar CSV
            </button>
            <button type="button" data-modal-target="modal-nuevo-horario" class="inline-flex items-center gap-2 rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Nuevo Horario
            </button>
        </div>
    </div>

    <div class="hidden md:block overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-semibold text-[#1a3a6b]">Programación de Horarios</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($horarios) ?> registros
            </span>
        </div>
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="table-th w-16 text-center whitespace-nowrap">#</th>
                        <th class="table-th whitespace-nowrap">Día</th>
                        <th class="table-th whitespace-nowrap">Horario</th>
                        <th class="table-th whitespace-nowrap">Programa</th>
                        <th class="table-th whitespace-nowrap">Unidad Didáctica</th>
                        <th class="table-th whitespace-nowrap">Docente</th>
                        <th class="table-th whitespace-nowrap text-center">Ciclo/Sección</th>
                        <th class="table-th whitespace-nowrap text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($horarios as $index => $h): ?>
                    <tr class="transition hover:bg-slate-50/50 horario-row" data-search="<?= strtolower(e($h['dia_semana'] . ' ' . $h['hora_inicio'] . ' ' . $h['hora_fin'] . ' ' . $h['unidad'] . ' ' . $h['docente'])) ?>" data-programa="<?= e($h['programa']) ?>">
                        <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                        <td class="table-td whitespace-nowrap font-medium text-slate-900"><?= e($h['dia_semana']) ?></td>
                        <td class="table-td text-slate-500"><?= e(substr((string)$h['hora_inicio'],0,5)) ?> - <?= e(substr((string)$h['hora_fin'],0,5)) ?></td>
                        <td class="table-td text-xs text-slate-500"><?= e($h['programa']) ?></td>
                        <td class="table-td max-w-40 truncate text-xs text-slate-500" title="<?= e($h['unidad']) ?>"><?= e($h['unidad']) ?></td>
                        <td class="table-td font-medium text-[#1a3a6b]"><?= e($h['docente']) ?></td>
                        <td class="table-td text-center text-slate-500"><?= e($h['ciclo']) ?>-<?= e($h['seccion']) ?></td>
                        <td class="table-td">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" onclick="deleteHorario(<?= (int)$h['id'] ?>)" class="rounded p-1.5 text-red-600 transition hover:bg-red-50" title="Eliminar">
                                    <i data-lucide="trash" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$horarios): ?>
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">No hay horarios registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Contenedor móvil -->
    <div class="md:hidden">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-[#1a3a6b]">Programación de Horarios</h2>
            <span id="mobile-table-count-badge" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($horarios) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
    <?php foreach ($horarios as $index => $h): ?>
        <div class="bg-white rounded-xl border p-4 shadow-sm horario-row" data-search="<?= strtolower(e($h['dia_semana'] . ' ' . $h['hora_inicio'] . ' ' . $h['hora_fin'] . ' ' . $h['unidad'] . ' ' . $h['docente'])) ?>" data-programa="<?= e($h['programa']) ?>">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <span class="text-slate-400 mr-2 font-bold text-sm">#<?= $index + 1 ?></span>
                    <span class="inline-block rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700"><?= e($h['dia_semana']) ?></span>
                    <span class="text-sm font-semibold text-slate-600 ml-2"><?= e(substr((string)$h['hora_inicio'],0,5)) ?> - <?= e(substr((string)$h['hora_fin'],0,5)) ?></span>
                </div>
                <button type="button" onclick="deleteHorario(<?= (int)$h['id'] ?>)" class="rounded p-1.5 text-red-600 transition hover:bg-red-50" title="Eliminar">
                    <i data-lucide="trash" class="h-4 w-4"></i>
                </button>
            </div>
            <div class="mb-1 text-sm font-bold text-[#1a3a6b]"><?= e($h['unidad']) ?></div>
            <div class="text-xs text-slate-500 mb-2">Prog: <?= e($h['programa']) ?> | Ciclo/Sec: <?= e($h['ciclo']) ?>-<?= e($h['seccion']) ?></div>
            <div class="text-sm font-medium text-slate-700"><i data-lucide="user" class="inline h-3 w-3 mr-1"></i><?= e($h['docente']) ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (!$horarios): ?>
        <div class="py-8 text-center text-slate-500 bg-white rounded-xl border">No hay horarios registrados.</div>
    <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nuevo Horario -->
<div id="modal-nuevo-horario" data-modal class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 p-4">
    <div class="mx-auto my-10 max-w-2xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Registrar nuevo horario</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form id="form-nuevo-horario" onsubmit="handleHorarioSubmit(event)">
            <div class="grid gap-4 p-6 md:grid-cols-2">
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Programa de Estudio *</span>
                    <select name="programa_id" class="form-control w-full" required onchange="filterUnidadesGlobal(this.value, 'select-unidad'); filterUnidadesGlobal(this.value, 'select-docente')">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($programas as $p): ?>
                            <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
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
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Docente *</span>
                    <select name="docente_id" id="select-docente" class="form-control w-full" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($docentes as $d): ?>
                            <option value="<?= e($d['id']) ?>" data-programa-id="<?= e($d['programa_id']) ?>"><?= e($d['nombres']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Ciclo/Sección *</span>
                    <select name="seccion" class="form-control w-full" required>
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Día de la semana *</span>
                    <select name="dia_semana" class="form-control w-full" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miercoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sabado">Sábado</option>
                    </select>
                </label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Hora Inicio *</span><input type="time" name="hora_inicio" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Hora Fin *</span><input type="time" name="hora_fin" class="form-control w-full" required></label>
            </div>
            <div class="flex gap-2 px-6 pb-6 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Asignar horario</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Importar Horarios -->
<div id="modal-importar-horarios" data-modal class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
    <div class="mx-auto mt-24 max-w-xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Importar horarios desde CSV</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <div class="p-6">
            <form id="form-importar-horarios">
                <input type="hidden" name="entidad" value="horarios">
                <div class="relative grid cursor-pointer place-items-center rounded-xl border-2 border-dashed border-slate-300 px-6 py-10 text-center transition hover:bg-slate-50">
                    <i data-lucide="upload" class="h-10 w-10 text-[#1a3a6b]/40"></i>
                    <p class="mt-3 text-sm text-slate-500">Arrastre su archivo aquí o haga clic para seleccionar</p>
                    <input type="file" name="file" accept=".csv" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" required>
                    <div class="file-name-display mt-3 hidden text-sm font-semibold text-[#1a3a6b]"></div>
                </div>
                <p class="mt-4 text-xs text-slate-500">Formato requerido: programa, unidad, docente, seccion, dia, hora_inicio, hora_fin</p>
                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Importar archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    
    // Importar horarios logic
    const formImportar = document.getElementById('form-importar-horarios');
    if (formImportar) {
        const fileInput = formImportar.querySelector('input[type="file"]');
        const fileDisplay = formImportar.querySelector('.file-name-display');
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileDisplay.textContent = this.files[0].name;
                fileDisplay.classList.remove('hidden');
            } else {
                fileDisplay.classList.add('hidden');
            }
        });
        
        formImportar.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.textContent = 'Importando...';
            btn.disabled = true;
            
            try {
                const formData = new FormData(this);
                const response = await fetch('api/importar.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: formData
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    window.showToast(result.message, 'green');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    window.showToast(result.error || 'Ocurrió un error', 'red');
                }
            } catch (error) {
                window.showToast('Error de conexión', 'red');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }
});

async function handleHorarioSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    payload.action = 'create';
    
    btn.disabled = true;
    btn.textContent = 'Procesando...';
    
    try {
        const response = await fetch('api/horarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        if (response.ok && data.success) {
            window.showToast('Horario creado exitosamente', 'green');
            setTimeout(() => location.reload(), 1500);
        } else {
            window.showToast(data.error || 'Ocurrió un error', 'red');
        }
    } catch (err) {
        window.showToast('Error de conexión', 'red');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar horario';
    }
}

async function deleteHorario(id) {
    const result = await Swal.fire({
        title: '¿Eliminar horario?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    });
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch('api/horarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const data = await response.json();
        if (response.ok && data.success) {
            window.showToast('Horario eliminado', 'green');
            setTimeout(() => location.reload(), 1500);
        } else {
            window.showToast(data.error || 'Ocurrió un error', 'red');
        }
    } catch (err) {
        window.showToast('Error de conexión', 'red');
    }
}

// Filter logic
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('filter-search');
    const programaSelect = document.getElementById('filter-programa');
    const rows = document.querySelectorAll('.horario-row');

    function filterTable() {
        const term = searchInput.value.toLowerCase();
        const prog = programaSelect.value;

        rows.forEach(row => {
            const rowSearch = row.getAttribute('data-search');
            const rowProg = row.getAttribute('data-programa');

            const matchSearch = rowSearch.includes(term);
            const matchProg = !prog || rowProg === prog;

            if (matchSearch && matchProg) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (programaSelect) programaSelect.addEventListener('change', filterTable);
});
</script>
