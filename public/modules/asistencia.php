<?php
$programas = all_programas();
$unidades  = all_unidades();
$docentes  = all_docentes();

$tab = $_GET['tab'] ?? 'estudiantes';
if (!in_array($tab, ['estudiantes', 'docentes'], true)) $tab = 'estudiantes';
$base = base_url('index.php?m=asistencia');
?>
<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-1 shadow-sm w-full sm:w-max overflow-x-auto">
        <div class="flex flex-nowrap gap-1 min-w-max">
            <a href="<?= e($base . '&tab=estudiantes') ?>" class="flex-1 sm:flex-none flex min-h-9 items-center justify-center rounded-lg px-4 py-2 text-center text-sm font-semibold whitespace-nowrap transition-colors <?= $tab === 'estudiantes' ? 'bg-[#1a3a6b] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">Asistencia Estudiantes</a>
            <a href="<?= e($base . '&tab=docentes') ?>" class="flex-1 sm:flex-none flex min-h-9 items-center justify-center rounded-lg px-4 py-2 text-center text-sm font-semibold whitespace-nowrap transition-colors <?= $tab === 'docentes' ? 'bg-[#1a3a6b] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">Asistencia Docentes</a>
        </div>
    </div>

<?php if ($tab === 'estudiantes'): ?>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-[#0b2f63]">Registro de asistencia estudiantil</h2>
        <p class="mt-1 text-xs text-slate-500">Seleccione los filtros para cargar la sesión y sus estudiantes.</p>

        <!-- Filtros dinámicos -->
        <div class="mt-5 flex flex-wrap items-end gap-3">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Periodo académico</span>
                <select id="sel-periodo" class="form-control w-40">
                    <option value="">Todos los periodos</option>
                    <option value="2026-I" selected>2026-I</option>
                    <option value="2026-II">2026-II</option>
                    <option value="2025-II">2025-II</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Programa de estudio</span>
                <select id="sel-programa" class="form-control w-64">
                    <option value="">Todos</option>
                    <?php foreach ($programas as $p): ?>
                        <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Unidad Didáctica</span>
                <select id="sel-unidad" class="form-control w-64">
                    <option value="">Todas</option>
                    <?php foreach ($unidades as $u): ?>
                        <option value="<?= e($u['id']) ?>" data-programa-id="<?= e($u['programa_id']) ?>"><?= e($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Sección</span>
                <select id="sel-seccion" class="form-control w-32">
                    <option value="">Todas</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select>
            </label>
            <label class="block relative">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Sesión académica</span>
                <select id="sel-sesion" class="form-control w-48">
                    <option value=""></option>
                </select>
                <p id="sesion-load-msg" class="absolute -bottom-5 left-0 text-[10px] text-slate-400 hidden whitespace-nowrap">Cargando sesiones...</p>
            </label>
        </div>

        <!-- Alerta de tiempo límite (oculto por defecto) -->
        <div id="alert-tiempo-limite" class="mt-4 hidden flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <div>
                <p class="font-bold">Fuera de tiempo límite</p>
                <p class="mt-1 text-xs">Esta sesión se registró hace más de 24 horas. Solo el administrador puede modificarla.</p>
            </div>
        </div>

        <!-- Tabla dinámica de estudiantes -->
        <div id="asistencia-container" class="mt-5 hidden">
            <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200">
                <div id="asistencia-table-header" class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50 hidden">
                    <h2 class="font-semibold text-[#1a3a6b]">Lista de Estudiantes</h2>
                    <span id="asistencia-record-count" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700"></span>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="table-th w-16 text-center">#</th>
                            <th class="table-th">Estudiante</th>
                            <th class="table-th">Código</th>
                            <th class="table-th">DNI</th>
                            <th class="table-th w-40">Estado</th>
                            <th class="table-th">Observación</th>
                        </tr>
                    </thead>
                    <tbody id="asistencia-tbody" class="divide-y divide-slate-100">
                        <tr><td colspan="6" class="py-8 text-center text-slate-400">Seleccione una sesión para cargar los estudiantes.</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- Contenedor móvil -->
            <div id="asistencia-mobile-container" class="grid gap-4 md:hidden"></div>
            <div class="mt-4 flex items-center justify-between">
                <span id="asistencia-info" class="text-xs text-slate-500"></span>
                <button id="btn-guardar-asistencia" type="button"
                    class="rounded-lg bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#142d54] disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    Guardar asistencia
                </button>
            </div>
        </div>

        <div id="asistencia-empty-msg" class="mt-5 hidden rounded-lg border border-slate-200 bg-slate-50 py-10 text-center text-sm text-slate-400">
            <i data-lucide="users" class="mx-auto mb-3 h-8 w-8 text-slate-300"></i>
            <p>No hay estudiantes matriculados en esta sesión.</p>
        </div>
    </div>

<?php else: ?>
    <!-- Tab: Docentes -->
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-[#0b2f63]">Registro de asistencia docente</h2>
        <p class="mt-1 text-xs text-slate-500">Seleccione una sesión para registrar la asistencia del docente.</p>

        <div class="mt-5 flex flex-wrap items-end gap-3">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Periodo académico</span>
                <select id="doc-sel-periodo" class="form-control w-40">
                    <option value="">Todos los periodos</option>
                    <option value="2026-I" selected>2026-I</option>
                    <option value="2026-II">2026-II</option>
                    <option value="2025-II">2025-II</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Docente</span>
                <select id="doc-sel-docente" class="form-control w-64">
                    <option value="">Todos</option>
                    <?php foreach ($docentes as $d): ?>
                        <option value="<?= e($d['id']) ?>"><?= e($d['nombres']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block relative">
                <span class="mb-1 block text-xs font-semibold text-slate-600">Sesión académica</span>
                <select id="doc-sel-sesion" class="form-control w-64">
                    <option value=""></option>
                </select>
            </label>
        </div>

        <!-- Formulario de asistencia docente -->
        <div id="doc-asistencia-container" class="mt-5 hidden">
            <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="table-th">Docente</th>
                            <th class="table-th">Unidad Didáctica</th>
                            <th class="table-th text-center">Sección</th>
                            <th class="table-th">Fecha</th>
                            <th class="table-th">Hora</th>
                            <th class="table-th w-40">Estado</th>
                            <th class="table-th">Motivo de cambio</th>
                        </tr>
                    </thead>
                    <tbody id="doc-asistencia-tbody" class="divide-y divide-slate-100">
                    </tbody>
                </table>
            </div>
            <div id="doc-asistencia-mobile-container" class="grid gap-4 md:hidden"></div>
            <div class="mt-4 flex justify-end">
                <button id="btn-guardar-doc-asistencia" type="button"
                    class="rounded-lg bg-[#1a3a6b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#142d54]">
                    Guardar asistencia docente
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<script>
// ===== ASISTENCIA ESTUDIANTES =====
(function() {
    const selPeriodo   = document.getElementById('sel-periodo');
    const selPrograma  = document.getElementById('sel-programa');
    const selUnidad    = document.getElementById('sel-unidad');
    const selSeccion   = document.getElementById('sel-seccion');
    const selSesion    = document.getElementById('sel-sesion');
    const loadMsg      = document.getElementById('sesion-load-msg');
    const alertTiempo  = document.getElementById('alert-tiempo-limite');
    const container    = document.getElementById('asistencia-container');
    const emptyMsg     = document.getElementById('asistencia-empty-msg');
    const tbody        = document.getElementById('asistencia-tbody');
    const infoEl       = document.getElementById('asistencia-info');
    const btnGuardar   = document.getElementById('btn-guardar-asistencia');

    if (!selPeriodo) return; // Not on this tab

    // Filter unidades by programa
    selPrograma.addEventListener('change', () => {
        const pid = selPrograma.value;
        Array.from(selUnidad.options).forEach(opt => {
            if (!opt.value) return;
            opt.hidden = pid ? opt.dataset.programaId !== pid : false;
        });
        selUnidad.value = '';
        loadSesiones();
    });

    [selPeriodo, selUnidad, selSeccion].forEach(el => {
        if (el) el.addEventListener('change', loadSesiones);
    });

    async function loadSesiones() {
        const params = new URLSearchParams({
            action: 'sesiones',
            programa_id: selPrograma.value,
            unidad_id: selUnidad.value,
            seccion: selSeccion.value,
            periodo: selPeriodo.value
        });

        loadMsg.classList.remove('hidden');
        selSesion.innerHTML = '<option value="">Cargando...</option>';
        selSesion.disabled = true;
        container.classList.add('hidden');
        emptyMsg.classList.add('hidden');

        try {
            const res = await fetch('api/sesiones_api.php?' + params.toString());
            const data = await res.json();
            selSesion.innerHTML = '<option value="">-- Seleccione una sesión --</option>';

            if (data.sesiones && data.sesiones.length > 0) {
                data.sesiones.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    const estadoBadge = s.estado === 'Registrada' ? ' ✓' : s.estado === 'Cerrada' ? ' 🔒' : '';
                    opt.textContent = `${s.fecha} ${s.hora.substring(0,5)} — ${s.unidad ?? 'Sin unidad'} — ${s.seccion}${estadoBadge}`;
                    opt.dataset.estado = s.estado;
                    selSesion.appendChild(opt);
                });
            } else {
                selSesion.innerHTML = '<option value="">No hay sesiones con esos filtros</option>';
            }
        } catch (e) {
            selSesion.innerHTML = '<option value="">Error cargando sesiones</option>';
        } finally {
            loadMsg.classList.add('hidden');
            selSesion.disabled = false;
        }
    }

    selSesion.addEventListener('change', loadEstudiantes);

    async function loadEstudiantes() {
        const sesionId = selSesion.value;
        if (!sesionId) {
            container.classList.add('hidden');
            emptyMsg.classList.add('hidden');
            return;
        }

        tbody.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-slate-400">Cargando...</td></tr>';
        container.classList.remove('hidden');
        emptyMsg.classList.add('hidden');
        btnGuardar.disabled = true;

        // Check time limit
        const selectedOpt = selSesion.options[selSesion.selectedIndex];
        const estadoSesion = selectedOpt?.dataset.estado;
        if (estadoSesion === 'Cerrada') {
            alertTiempo.classList.remove('hidden');
        } else {
            alertTiempo.classList.add('hidden');
        }

        try {
            const res = await fetch(`api/sesiones_api.php?action=estudiantes&sesion_id=${sesionId}`);
            const data = await res.json();

            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-red-500">${data.error}</td></tr>`;
                return;
            }

            if (!data.estudiantes || data.estudiantes.length === 0) {
                container.classList.add('hidden');
                emptyMsg.classList.remove('hidden');
                return;
            }

            const isLocked = estadoSesion === 'Cerrada';
            tbody.innerHTML = '';
            const mobileContainer = document.getElementById('asistencia-mobile-container');
            mobileContainer.innerHTML = '';
            
            // Show header bar with record count
            const tableHeader = document.getElementById('asistencia-table-header');
            const recordCount = document.getElementById('asistencia-record-count');
            if (tableHeader) tableHeader.classList.remove('hidden');
            if (recordCount) recordCount.textContent = `${data.estudiantes.length} registros`;

            data.estudiantes.forEach((est, idx) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/50 transition';
                tr.dataset.estudianteId = est.id;
                tr.innerHTML = `
                    <td class="table-td text-center text-slate-400 text-xs font-semibold">${idx + 1}</td>
                    <td class="table-td font-semibold text-slate-900">${est.nombres}</td>
                    <td class="table-td font-mono text-xs text-slate-500">${est.codigo ?? '—'}</td>
                    <td class="table-td text-xs text-slate-500">${est.dni ?? '—'}</td>
                    <td class="table-td">
                        <select class="form-control select-asistencia text-xs" style="min-width:120px" ${isLocked ? 'disabled' : ''}>
                            <option value="Presente" ${est.asistencia_estado === 'Presente' ? 'selected' : ''}>Presente</option>
                            <option value="Tardanza" ${est.asistencia_estado === 'Tardanza' ? 'selected' : ''}>Tardanza</option>
                            <option value="Inasistente" ${est.asistencia_estado === 'Inasistente' ? 'selected' : ''}>Inasistente</option>
                            <option value="Justificado" ${est.asistencia_estado === 'Justificado' ? 'selected' : ''}>Justificado</option>
                        </select>
                    </td>
                    <td class="table-td">
                        <input type="text" class="form-control obs-asistencia text-xs w-full" placeholder="Observación (opcional)"
                            value="${est.observacion ?? ''}" ${isLocked ? 'disabled' : ''}>
                    </td>
                `;
                tbody.appendChild(tr);

                const card = document.createElement('div');
                card.className = 'bg-white rounded-xl border p-4 shadow-sm mobile-row';
                card.dataset.estudianteId = est.id;
                card.innerHTML = `
                    <div class="font-semibold text-slate-900 mb-1">${est.nombres}</div>
                    <div class="text-xs text-slate-500 mb-3">${est.codigo ?? '—'} | DNI: ${est.dni ?? '—'}</div>
                    <div class="space-y-3">
                        <select class="form-control select-asistencia w-full p-3 text-base h-12" ${isLocked ? 'disabled' : ''}>
                            <option value="Presente" ${est.asistencia_estado === 'Presente' ? 'selected' : ''}>Presente</option>
                            <option value="Tardanza" ${est.asistencia_estado === 'Tardanza' ? 'selected' : ''}>Tardanza</option>
                            <option value="Inasistente" ${est.asistencia_estado === 'Inasistente' ? 'selected' : ''}>Inasistente</option>
                            <option value="Justificado" ${est.asistencia_estado === 'Justificado' ? 'selected' : ''}>Justificado</option>
                        </select>
                        <input type="text" class="form-control obs-asistencia w-full p-3 text-base h-12" placeholder="Observación (opcional)" value="${est.observacion ?? ''}" ${isLocked ? 'disabled' : ''}>
                    </div>
                `;
                mobileContainer.appendChild(card);
            });

            infoEl.textContent = `${data.estudiantes.length} estudiante(s) cargado(s) — Sesión del ${data.sesion.fecha}`;
            btnGuardar.disabled = isLocked;
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-red-500">Error de conexión al cargar estudiantes.</td></tr>';
        }
    }

    btnGuardar?.addEventListener('click', async () => {
        const sesionId = selSesion.value;
        if (!sesionId) { window.showToast('Seleccione una sesión', 'red'); return; }

        const isMobile = window.innerWidth < 768;
        const rows = isMobile ? document.querySelectorAll('#asistencia-mobile-container .mobile-row') : tbody.querySelectorAll('tr[data-estudiante-id]');
        const asistencias = [];
        rows.forEach(row => {
            asistencias.push({
                estudiante_id: row.dataset.estudianteId,
                estado: row.querySelector('.select-asistencia').value,
                observacion: row.querySelector('.obs-asistencia').value
            });
        });

        if (asistencias.length === 0) { window.showToast('No hay estudiantes para guardar', 'red'); return; }

        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('api/asistencia.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ sesion_id: sesionId, asistencias })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                window.showToast(data.message, 'green');
            } else {
                window.showToast(data.error || 'Error al guardar', 'red');
            }
        } catch (e) {
            window.showToast('Error de conexión', 'red');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar asistencia';
        }
    });

    // Initial load
    loadSesiones();
})();

// ===== ASISTENCIA DOCENTES =====
(function() {
    const docSelPeriodo  = document.getElementById('doc-sel-periodo');
    const docSelDocente  = document.getElementById('doc-sel-docente');
    const docSelSesion   = document.getElementById('doc-sel-sesion');
    const docContainer   = document.getElementById('doc-asistencia-container');
    const docTbody       = document.getElementById('doc-asistencia-tbody');
    const btnDocGuardar  = document.getElementById('btn-guardar-doc-asistencia');

    if (!docSelPeriodo) return; // Not on this tab

    async function loadDocSesiones() {
        const params = new URLSearchParams({
            action: 'sesiones',
            periodo: docSelPeriodo?.value ?? '',
        });
        if (docSelDocente?.value) params.set('docente_id', docSelDocente.value);

        try {
            const res = await fetch('api/sesiones_api.php?' + params.toString());
            const data = await res.json();
            docSelSesion.innerHTML = '<option value="">-- Seleccionar sesión --</option>';
            if (data.sesiones?.length > 0) {
                data.sesiones.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = `${s.fecha} ${s.hora?.substring(0,5)} — ${s.docente ?? 'Sin docente'} — ${s.unidad ?? ''}`;
                    opt.dataset.sesion = JSON.stringify(s);
                    docSelSesion.appendChild(opt);
                });
            }
        } catch(e) {}
    }

    [docSelPeriodo, docSelDocente].forEach(el => {
        if (el) el.addEventListener('change', loadDocSesiones);
    });

    docSelSesion?.addEventListener('change', () => {
        const opt = docSelSesion.options[docSelSesion.selectedIndex];
        if (!opt.dataset.sesion) {
            docContainer.classList.add('hidden');
            return;
        }
        const s = JSON.parse(opt.dataset.sesion);
        docTbody.innerHTML = `
            <tr>
                <td class="table-td font-semibold text-slate-900">${s.docente ?? '—'}</td>
                <td class="table-td text-xs text-slate-600">${s.unidad ?? '—'}</td>
                <td class="table-td text-center">${s.seccion ?? '—'}</td>
                <td class="table-td whitespace-nowrap">${s.fecha ?? '—'}</td>
                <td class="table-td">${s.hora?.substring(0,5) ?? '—'}</td>
                <td class="table-td">
                    <select class="form-control doc-estado text-xs" style="min-width:130px">
                        <option value="Presente">Presente</option>
                        <option value="Tardanza">Tardanza</option>
                        <option value="Inasistencia">Inasistencia</option>
                        <option value="Inasistencia Justificada">Justificada</option>
                    </select>
                </td>
                <td class="table-td">
                    <input type="text" class="form-control doc-motivo text-xs w-full" placeholder="Motivo del cambio">
                </td>
            </tr>
        `;
        const docMobile = document.getElementById('doc-asistencia-mobile-container');
        if (docMobile) {
            docMobile.innerHTML = `
                <div class="bg-white rounded-xl border p-4 shadow-sm doc-mobile-row">
                    <div class="font-semibold text-slate-900 mb-1">${s.docente ?? '—'}</div>
                    <div class="text-xs text-slate-500 mb-3">${s.unidad ?? '—'} | ${s.seccion ?? '—'} | ${s.fecha ?? '—'} ${s.hora?.substring(0,5) ?? '—'}</div>
                    <div class="space-y-3">
                        <select class="form-control doc-estado w-full p-3 text-base h-12">
                            <option value="Presente">Presente</option>
                            <option value="Tardanza">Tardanza</option>
                            <option value="Inasistencia">Inasistencia</option>
                            <option value="Inasistencia Justificada">Justificada</option>
                        </select>
                        <input type="text" class="form-control doc-motivo w-full p-3 text-base h-12" placeholder="Motivo del cambio">
                    </div>
                </div>
            `;
        }
        docContainer.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    });

    btnDocGuardar?.addEventListener('click', async () => {
        const sesionId = docSelSesion?.value;
        if (!sesionId) { window.showToast('Seleccione una sesión', 'red'); return; }

        const isMobileDoc = window.innerWidth < 768;
        const containerToUse = isMobileDoc ? document.getElementById('doc-asistencia-mobile-container') : docTbody;
        const estado = containerToUse.querySelector('.doc-estado')?.value;
        const motivo = containerToUse.querySelector('.doc-motivo')?.value;

        btnDocGuardar.disabled = true;
        btnDocGuardar.textContent = 'Guardando...';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('api/asistencia_docente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ sesion_id: sesionId, estado, motivo })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                window.showToast('Asistencia docente guardada correctamente', 'green');
            } else {
                window.showToast(data.error || 'Error al guardar', 'red');
            }
        } catch (e) {
            window.showToast('Error de conexión', 'red');
        } finally {
            btnDocGuardar.disabled = false;
            btnDocGuardar.textContent = 'Guardar asistencia docente';
        }
    });

    loadDocSesiones();
})();
</script>
