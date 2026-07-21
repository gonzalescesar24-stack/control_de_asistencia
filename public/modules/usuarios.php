<?php
$usuarios = fetch_all('SELECT id, nombre, usuario, rol, estado, correo FROM usuarios ORDER BY nombre');
$programas = all_programas();
$unidades = all_unidades();
$periodos = all_periodos();

$roleLabel = [
    'admin' => 'Admin',
    'docente' => 'Docente',
    'estudiante' => 'Estudiante',
];
?>
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <label class="relative block">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input id="filter-search" class="form-control filter-input w-full shrink-0 sm:w-64 pl-9" placeholder="Buscar usuario...">
            </label>
            <select id="filter-rol" class="form-control filter-select w-48">
                <option value="">Todos los roles</option>
                <option value="admin">Admin</option>
                <option value="docente">Docente</option>
                <option value="estudiante">Estudiante</option>
            </select>
            <select id="filter-estado" class="form-control filter-select w-48">
                <option value="">Todos los estados</option>
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
            </select>
        </div>
        <?php render_modal_button('+  Nuevo usuario', 'modal-nuevo-usuario'); ?>
    </div>

    <div class="overflow-x-auto overflow-y-hidden min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm hidden md:block">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-semibold text-[#1a3a6b]">Lista de Usuarios</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($usuarios) ?> registros
            </span>
        </div>
        <table class="w-full min-w-[800px]">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-th w-16 text-center">#</th>
                    <th class="table-th w-1/3">Nombre</th>
                    <th class="table-th w-1/4">Usuario / Correo</th>
                    <th class="table-th">Rol</th>
                    <th class="table-th">Estado</th>
                    <th class="table-th text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($usuarios as $index => $u): ?>
                <tr class="user-row" data-rol="<?= e($u['rol']) ?>" data-estado="<?= e($u['estado']) ?>" data-search="<?= strtolower(e($u['nombre'] . ' ' . $u['usuario'] . ' ' . $u['correo'])) ?>">
                    <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-bold text-[#1a3a6b]"><?= e(user_initials($u['nombre'])) ?></span>
                            <span class="truncate font-semibold text-slate-900" title="<?= e($u['nombre']) ?>"><?= e($u['nombre']) ?></span>
                        </div>
                    </td>
                    <td class="table-td">
                        <div class="flex flex-col min-w-0">
                            <span class="truncate font-medium text-slate-700"><?= e($u['usuario']) ?></span>
                            <span class="truncate text-xs text-slate-500" title="<?= e($u['correo']) ?>"><?= e($u['correo']) ?></span>
                        </div>
                    </td>
                    <td class="table-td"><span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"><?= e($roleLabel[$u['rol']] ?? $u['rol']) ?></span></td>
                    <td class="table-td"><span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= badge_class($u['estado']) ?>"><?= e($u['estado']) ?></span></td>
                    <td class="table-td">
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" class="text-blue-600 transition hover:text-blue-800" onclick="openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)" title="Editar"><i data-lucide="square-pen" class="h-4 w-4"></i></button>
                            <button type="button" class="text-slate-500 transition hover:text-slate-700" onclick="resetPassword(<?= $u['id'] ?>)" title="Restablecer clave"><i data-lucide="refresh-cw" class="h-4 w-4"></i></button>
                            <?php if ($u['estado'] === 'Activo'): ?>
                                <button type="button" class="text-red-500 transition hover:text-red-700" onclick="toggleStatus(<?= $u['id'] ?>, 'Inactivo')" title="Desactivar"><i data-lucide="circle-x" class="h-4 w-4"></i></button>
                            <?php else: ?>
                                <button type="button" class="text-emerald-500 transition hover:text-emerald-700" onclick="toggleStatus(<?= $u['id'] ?>, 'Activo')" title="Activar"><i data-lucide="check-circle" class="h-4 w-4"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$usuarios): ?>
                <tr><td colspan="5" class="py-8 text-center text-slate-500">No hay usuarios registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Vista móvil (Cards) -->
    <div class="md:hidden mt-2">
        <div class="mb-4 flex items-center justify-between px-2">
            <h2 class="font-semibold text-[#1a3a6b]">Usuarios</h2>
            <span id="mobile-table-count-badge" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($usuarios) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php foreach ($usuarios as $index => $u): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm user-row flex flex-col gap-3" data-rol="<?= e($u['rol']) ?>" data-estado="<?= e($u['estado']) ?>" data-search="<?= strtolower(e($u['nombre'] . ' ' . $u['usuario'] . ' ' . $u['correo'])) ?>">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-bold text-[#1a3a6b]"><?= e(user_initials($u['nombre'])) ?></span>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-slate-900 leading-tight truncate" title="<?= e($u['nombre']) ?>"><span class="text-slate-400 font-bold mr-1 font-sans">#<?= $index + 1 ?></span><?= e($u['nombre']) ?></h3>
                        <p class="text-sm font-medium text-slate-700 mt-0.5 truncate"><?= e($u['usuario']) ?></p>
                        <p class="text-xs text-slate-500 truncate" title="<?= e($u['correo']) ?>"><?= e($u['correo']) ?></p>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700"><?= e($roleLabel[$u['rol']] ?? $u['rol']) ?></span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= badge_class($u['estado']) ?>"><?= e($u['estado']) ?></span>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 mt-1">
                    <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-blue-600 transition hover:bg-blue-50" title="Editar">
                        <i data-lucide="square-pen" class="h-4 w-4"></i> Editar
                    </button>
                    <button type="button" onclick="resetPassword(<?= $u['id'] ?>)" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100" title="Restablecer clave">
                        <i data-lucide="refresh-cw" class="h-4 w-4"></i> Clave
                    </button>
                    <?php if ($u['estado'] === 'Activo'): ?>
                        <button type="button" onclick="toggleStatus(<?= $u['id'] ?>, 'Inactivo')" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-50" title="Desactivar">
                            <i data-lucide="circle-x" class="h-4 w-4"></i> Desactivar
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="toggleStatus(<?= $u['id'] ?>, 'Activo')" class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-sm font-medium text-emerald-500 transition hover:bg-emerald-50" title="Activar">
                            <i data-lucide="check-circle" class="h-4 w-4"></i> Activar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$usuarios): ?>
            <div class="py-8 text-center text-sm text-slate-500 bg-white rounded-xl border border-slate-200">No hay usuarios registrados.</div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div id="modal-nuevo-usuario" data-modal class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
    <div class="mx-auto mt-24 max-w-xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Nuevo usuario</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form id="form-nuevo-usuario" onsubmit="handleUserSubmit(event, 'create')">
            <div class="grid gap-4 p-6 md:grid-cols-2">
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Nombres Completos *</span><input type="text" name="nombre" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Usuario *</span><input type="text" name="usuario" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Correo *</span><input type="email" name="correo" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Contraseña *</span><input type="password" name="password" class="form-control w-full" required minlength="6"></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Rol</span><select name="rol" onchange="toggleFields(this, 'form-nuevo-usuario')" class="form-control w-full"><option value="estudiante">Estudiante</option><option value="docente">Docente</option><option value="admin">Admin</option></select></label>
                
                <!-- Campos dinámicos -->
                <div class="campos-academicos md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                    <label class="campo-codigo"><span class="mb-1 block text-sm font-medium text-slate-700">Código Institucional *</span><input type="text" name="codigo" class="form-control w-full"></label>
                    <label class="campo-dni"><span class="mb-1 block text-sm font-medium text-slate-700">DNI *</span><input type="text" name="dni" class="form-control w-full"></label>
                    
                    <label class="campo-programa"><span class="mb-1 block text-sm font-medium text-slate-700">Programa de Estudios *</span>
                        <select name="programa_id" class="form-control w-full" onchange="filterUnidadesGlobal(this.value, 'select-unidad')">
                            <option value="">Seleccione...</option>
                            <?php foreach ($programas as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label class="campo-ciclo"><span class="mb-1 block text-sm font-medium text-slate-700">Ciclo *</span>
                        <select name="periodo_curricular_id" class="form-control w-full">
                            <option value="">Seleccione...</option>
                            <?php foreach ($periodos as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label class="campo-unidad"><span class="mb-1 block text-sm font-medium text-slate-700">Unidad Didáctica *</span>
                        <select name="unidad_didactica_id" id="select-unidad" class="form-control w-full">
                            <option value="">Seleccione...</option>
                            <?php foreach ($unidades as $u): ?><option value="<?= $u['id'] ?>" data-programa-id="<?= e($u['programa_id']) ?>"><?= e($u['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label class="campo-seccion"><span class="mb-1 block text-sm font-medium text-slate-700">Sección *</span>
                        <select name="seccion" class="form-control w-full">
                            <option value="">Seleccione...</option>
                            <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                        </select>
                    </label>
                </div>
            </div>
            <div class="flex gap-2 px-6 pb-6 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Guardar usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div id="modal-editar-usuario" data-modal class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
    <div class="mx-auto mt-24 max-w-xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="font-bold text-[#1a3a6b]">Editar usuario</h2>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form id="form-editar-usuario" onsubmit="handleUserSubmit(event, 'update')">
            <input type="hidden" name="id" id="edit-id">
            <div class="grid gap-4 p-6 md:grid-cols-2">
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">Nombres Completos *</span><input type="text" name="nombre" id="edit-nombre" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Usuario *</span><input type="text" name="usuario" id="edit-usuario" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Correo *</span><input type="email" name="correo" id="edit-correo" class="form-control w-full" required></label>
                <label><span class="mb-1 block text-sm font-medium text-slate-700">Rol</span><select name="rol" id="edit-rol" onchange="toggleFields(this, 'form-editar-usuario')" class="form-control w-full"><option value="estudiante">Estudiante</option><option value="docente">Docente</option><option value="admin">Admin</option></select></label>

                <!-- Campos dinámicos editables (Opcional por ahora) -->
                <div class="campos-academicos md:col-span-2 p-3 bg-blue-50 border border-blue-100 rounded-lg hidden">
                    <p class="text-xs text-blue-700 mb-2 font-medium">Nota: Para editar los datos académicos específicos (Código, Unidad, etc.) dirígete al módulo de Estudiantes o Docentes respectivamente. Aquí solo puedes actualizar las credenciales de acceso.</p>
                </div>
            </div>
            <div class="flex gap-2 px-6 pb-6 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

async function apiCall(action, payload) {
    try {
        const response = await fetch('api/usuarios.php', {
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

function openEditModal(user) {
    document.getElementById('edit-id').value = user.id;
    document.getElementById('edit-nombre').value = user.nombre;
    document.getElementById('edit-usuario').value = user.usuario;
    document.getElementById('edit-correo').value = user.correo;
    document.getElementById('edit-rol').value = user.rol;
    
    // Show modal manually
    const modal = document.getElementById('modal-editar-usuario');
    modal.classList.remove('hidden');
}

async function handleUserSubmit(e, action) {
    e.preventDefault();
    const form = e.target;
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const btn = form.querySelector('button[type="submit"]');
    
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    
    btn.disabled = true;
    btn.textContent = 'Procesando...';
    await apiCall(action, payload);
    btn.disabled = false;
    btn.textContent = 'Guardar';
}

function resetPassword(id) {
    Swal.fire({
        title: '¿Restablecer contraseña?',
        text: 'La contraseña volverá a ser igual al nombre de usuario.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1a3a6b',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, restablecer',
        cancelButtonText: 'No, cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            apiCall('reset_password', { id });
        }
    });
}

function toggleStatus(id, estado) {
    Swal.fire({
        title: '¿Cambiar estado?',
        text: `El usuario pasará a estado ${estado}.`,
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

function toggleFields(selectElement, formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    const academicos = form.querySelector('.campos-academicos');
    if (!academicos) return;

    const rol = selectElement.value;
    
    if (rol === 'admin') {
        academicos.classList.add('hidden');
        academicos.querySelectorAll('input, select').forEach(el => el.required = false);
    } else {
        academicos.classList.remove('hidden');
        
        const campoDni = form.querySelector('.campo-dni');
        const campoCiclo = form.querySelector('.campo-ciclo');
        
        if (rol === 'docente') {
            if (campoDni) campoDni.classList.remove('hidden');
            if (campoCiclo) campoCiclo.classList.add('hidden');
            
            form.querySelectorAll('[name="dni"]').forEach(el => el.required = true);
            form.querySelectorAll('[name="periodo_curricular_id"]').forEach(el => el.required = false);
            form.querySelectorAll('[name="codigo"], [name="programa_id"], [name="unidad_didactica_id"], [name="seccion"]').forEach(el => el.required = true);
        } else if (rol === 'estudiante') {
            if (campoDni) campoDni.classList.remove('hidden');
            if (campoCiclo) campoCiclo.classList.remove('hidden');
            
            form.querySelectorAll('[name="dni"]').forEach(el => el.required = true);
            form.querySelectorAll('[name="codigo"], [name="programa_id"], [name="periodo_curricular_id"], [name="unidad_didactica_id"], [name="seccion"]').forEach(el => el.required = true);
        }
    }
}

// Inicializar form de creacion
document.addEventListener('DOMContentLoaded', () => {
    const createRolSelect = document.querySelector('#form-nuevo-usuario [name="rol"]');
    if (createRolSelect) toggleFields(createRolSelect, 'form-nuevo-usuario');

    // Filter logic
    const searchInput = document.getElementById('filter-search');
    const rolSelect = document.getElementById('filter-rol');
    const estadoSelect = document.getElementById('filter-estado');
    const rows = document.querySelectorAll('.user-row');

    function filterTable() {
        const term = searchInput.value.toLowerCase();
        const rol = rolSelect.value;
        const estado = estadoSelect.value;

        rows.forEach(row => {
            const rowSearch = row.getAttribute('data-search');
            const rowRol = row.getAttribute('data-rol');
            const rowEstado = row.getAttribute('data-estado');

            const matchSearch = rowSearch.includes(term);
            const matchRol = !rol || rowRol === rol;
            const matchEstado = !estado || rowEstado === estado;

            if (matchSearch && matchRol && matchEstado) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (rolSelect) rolSelect.addEventListener('change', filterTable);
    if (estadoSelect) estadoSelect.addEventListener('change', filterTable);
});
</script>
