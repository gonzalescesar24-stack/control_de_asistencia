<?php 
$sesion_id = $_GET['sesion_id'] ?? 0;
if (!$sesion_id) {
    echo "<div class='p-4 text-red-600 bg-red-50 rounded-lg'>Debe seleccionar una sesión para registrar asistencia. <a href='" . e(base_url('index.php?m=mis-sesiones')) . "' class='underline font-bold'>Volver</a></div>";
    return;
}

$sesion = fetch_one("
    SELECT 
        s.*, 
        p.nombre as programa_nombre, 
        ud.nombre as unidad_nombre,
        pc.nombre as ciclo_nombre
    FROM sesiones s
    LEFT JOIN programas p ON s.programa_id = p.id
    LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id
    LEFT JOIN periodos_curriculares pc ON ud.periodo_curricular_id = pc.id
    WHERE s.id = ?
", [$sesion_id]);

if (!$sesion) {
    echo "<div class='p-4 text-red-600 bg-red-50 rounded-lg'>Sesión no encontrada. <a href='" . e(base_url('index.php?m=mis-sesiones')) . "' class='underline font-bold'>Volver</a></div>";
    return;
}

$estudiantes = fetch_all("
    SELECT 
        e.id, 
        e.codigo, 
        e.nombres, 
        p.nombre as programa 
    FROM estudiantes e 
    LEFT JOIN programas p ON e.programa_id = p.id 
    WHERE e.programa_id = ? 
      AND e.unidad_didactica_id = ? 
      AND e.seccion = ?
    ORDER BY e.nombres ASC
", [$sesion['programa_id'], $sesion['unidad_didactica_id'], $sesion['seccion']]);
?>
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <?php 
                render_filters([
                'Periodo' => [$sesion['periodo']], 
                'Programa' => [$sesion['programa_nombre']], 
                'Unidad' => [$sesion['unidad_nombre']], 
                'Periodo - Sección' => [$sesion['ciclo_nombre'] . ' - ' . clean_seccion($sesion['seccion'])]
            ]); ?>
            <label class="block w-full col-span-1">
                <span class="mb-1 block text-xs font-semibold text-slate-500">Sesion academica</span>
                <select class="form-control filter-select w-full" id="sesion_id_select" disabled>
                    <option value="<?= e($sesion['id']) ?>" selected><?= e($sesion['fecha']) ?> <?= e($sesion['hora']) ?> - <?= e($sesion['unidad_nombre']) ?></option>
                </select>
                <input type="hidden" id="sesion_id" value="<?= e($sesion['id']) ?>">
            </label>
        </div>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Edicion disponible por 24 horas.</div>
    <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-bold text-[#1a3a6b]">Registrar asistencia de estudiantes</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 shrink-0 whitespace-nowrap">
                <?= count($estudiantes) ?> registro<?= count($estudiantes) !== 1 ? 's' : '' ?>
            </span>
        </div>
        <table class="w-full" id="tabla-asistencia">
            <thead class="bg-slate-50">
                <tr><th class="table-th w-16 text-center">#</th><th class="table-th">Codigo</th><th class="table-th">Estudiante</th><th class="table-th w-48">Estado</th><th class="table-th">Observacion</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($estudiantes as $index => $e): ?>
                <tr data-estudiante-id="<?= e($e['id'] ?? 1) ?>">
                    <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                    <td class="table-td"><?= e($e['codigo']) ?></td>
                    <td class="table-td font-medium text-[#1a3a6b]"><?= e($e['nombres']) ?></td>
                    <td class="table-td pr-6">
                        <select class="form-control select-estado w-[140px]">
                            <option value="Presente">Presente</option>
                            <option value="Inasistente">Inasistente</option>
                            <option value="Tardanza">Tardanza</option>
                            <option value="Justificado">Justificado</option>
                        </select>
                    </td>
                    <td class="table-td pl-2"><input type="text" class="form-control input-obs" placeholder="Observacion"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="border-t border-slate-100 p-4 text-right">
            <button type="button" id="btn-guardar-asistencia-desktop" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#142d54]">Guardar asistencia</button>
        </div>
    </div>

    <!-- Contenedor móvil -->
    <div class="md:hidden">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-bold text-[#1a3a6b]">Estudiantes</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 shrink-0 whitespace-nowrap">
                <?= count($estudiantes) ?> registro<?= count($estudiantes) !== 1 ? 's' : '' ?>
            </span>
        </div>
        <div class="grid gap-4">
            <?php foreach ($estudiantes as $index => $e): ?>
                <div class="mobile-asistencia-row bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3" data-estudiante-id="<?= e($e['id'] ?? 1) ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 mb-0.5"><?= e($e['codigo']) ?></p>
                            <h3 class="font-bold text-[#0b2f63] leading-tight"><span class="text-slate-400 mr-1">#<?= $index + 1 ?></span><?= e($e['nombres']) ?></h3>
                        </div>
                    </div>
                    <div class="grid gap-3 border-t border-slate-100 pt-3">
                        <select class="form-control select-estado">
                            <option value="Presente">Presente</option>
                            <option value="Inasistente">Inasistente</option>
                            <option value="Tardanza">Tardanza</option>
                            <option value="Justificado">Justificado</option>
                        </select>
                        <input type="text" class="form-control input-obs" placeholder="Observacion (opcional)">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-right">
            <button type="button" id="btn-guardar-asistencia-mobile" class="rounded-lg bg-[#1a3a6b] w-full px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#142d54]">Guardar asistencia</button>
        </div>
    </div>

    <!-- Modal de Confirmación Docente -->
    <div id="modal-confirm-docente" class="fixed inset-0 z-50 hidden bg-black/50 p-4 backdrop-blur-sm flex items-center justify-center">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                <i data-lucide="triangle-alert" class="h-6 w-6 shrink-0 mt-0.5"></i>
                <div>
                    <h3 class="font-bold">Modificación fuera de plazo</h3>
                    <p class="mt-1 text-sm">Esta sesión se registró hace más de 24 horas. Las modificaciones requieren justificación formal y quedarán registradas en la bitácora de auditoría bajo tu responsabilidad.</p>
                </div>
            </div>
            <div class="space-y-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox" id="chk-autorizacion" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-slate-700">Confirmo que cuento con el documento o autorización de Dirección para este cambio.</span>
                </label>
                <label class="flex items-start gap-3">
                    <input type="checkbox" id="chk-responsabilidad" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-slate-700">Asumo la responsabilidad de esta modificación extemporánea.</span>
                </label>
                <div>
                    <label for="txt-motivo-cambio" class="mb-1 block text-sm font-semibold text-slate-700">Motivo de la modificación <span class="text-red-500">*</span></label>
                    <textarea id="txt-motivo-cambio" class="form-control w-full p-2 text-sm" rows="3" placeholder="Ej. El estudiante presentó certificado médico..."></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button id="btn-cancelar-modal" type="button" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                <button id="btn-confirmar-modal" type="button" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Confirmar Cambio</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --- Modal Logic ---
    const modalConfirm = document.getElementById('modal-confirm-docente');
    const chkAuth = document.getElementById('chk-autorizacion');
    const chkResp = document.getElementById('chk-responsabilidad');
    const txtMotivo = document.getElementById('txt-motivo-cambio');
    const btnConfirmModal = document.getElementById('btn-confirmar-modal');
    const btnCancelModal = document.getElementById('btn-cancelar-modal');
    let currentSaveBtn = null;

    function checkModalValidity() {
        if (chkAuth.checked && chkResp.checked && txtMotivo.value.trim().length > 5) {
            btnConfirmModal.disabled = false;
        } else {
            btnConfirmModal.disabled = true;
        }
    }

    chkAuth.addEventListener('change', checkModalValidity);
    chkResp.addEventListener('change', checkModalValidity);
    txtMotivo.addEventListener('input', checkModalValidity);

    btnCancelModal.addEventListener('click', () => {
        modalConfirm.classList.add('hidden');
    });

    btnConfirmModal.addEventListener('click', () => {
        modalConfirm.classList.add('hidden');
        doSaveAsistencia(currentSaveBtn, txtMotivo.value.trim());
    });
    // -------------------

[document.getElementById('btn-guardar-asistencia-desktop'), document.getElementById('btn-guardar-asistencia-mobile')].forEach(btn => {
    if (!btn) return;
    btn.addEventListener('click', async (e) => {
        const sesionEstado = '<?= e($sesion['estado'] ?? 'Pendiente') ?>';
        
        // Si la sesión está cerrada y es docente, mostrar modal
        if (sesionEstado === 'Cerrada') {
            chkAuth.checked = false;
            chkResp.checked = false;
            txtMotivo.value = '';
            btnConfirmModal.disabled = true;
            currentSaveBtn = btn;
            modalConfirm.classList.remove('hidden');
        } else {
            doSaveAsistencia(btn, null);
        }
    });
});

async function doSaveAsistencia(btn, motivoEspecial) {
    const sesion_id = document.getElementById('sesion_id').value;
    
    if (!sesion_id) {
        window.showToast('Debe seleccionar una sesión académica.', 'amber');
        return;
    }

    const isMobile = window.innerWidth < 768;
    const rows = isMobile ? document.querySelectorAll('.mobile-asistencia-row') : document.querySelectorAll('#tabla-asistencia tbody tr');
    const asistencias = [];
    
    rows.forEach(row => {
        const est_id = row.dataset.estudianteId;
        const estado = row.querySelector('.select-estado').value;
        const obs = row.querySelector('.input-obs').value;
        asistencias.push({ estudiante_id: est_id, estado: estado, observacion: obs });
    });

    try {
        btn.disabled = true;
        btn.textContent = 'Guardando...';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        const bodyData = { sesion_id: sesion_id, asistencias: asistencias };
        if (motivoEspecial) {
            bodyData.motivo_docente = motivoEspecial;
        }
        
        const response = await fetch('<?= e(base_url('api/asistencia.php')) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(bodyData)
        });
        
        const data = await response.json();
        if (response.ok && data.success) {
            window.showToast(data.message, 'green');
            setTimeout(() => location.reload(), 1500);
        } else {
            window.showToast(data.error || 'Ocurrió un error al guardar la asistencia.', 'red');
        }
    } catch (error) {
        window.showToast('Ocurrió un error de conexión.', 'red');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar asistencia';
    }
}
</script>
