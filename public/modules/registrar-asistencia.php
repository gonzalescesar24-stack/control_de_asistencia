<?php 
// Obtener todos los estudiantes (para el mockup, luego esto vendría filtrado por la sesión)
$rows = fetch_all('SELECT e.id, e.codigo, e.nombres, p.nombre as programa FROM estudiantes e LEFT JOIN programas p ON e.programa_id = p.id WHERE p.nombre = "Desarrollo de Sistemas de Información"');
// Fallback si no hay DB
if (!$rows && isset($ESTUDIANTES)) {
    $rows = array_filter($ESTUDIANTES, fn($e) => $e['programa'] === 'Desarrollo de Sistemas de Información');
    // Para el mockup asginar ID
    foreach ($rows as $k => $v) $rows[$k]['id'] = $k + 1;
}
$estudiantes = $rows;
?>
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <?php render_filters(['Periodo academico' => ['2026-I'], 'Programa' => ['Desarrollo de Sistemas de Información'], 'Unidad didactica asignada' => ['Programacion Web'], 'Seccion asignada' => ['V-B']]); ?>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-500">Sesion academica</span>
                <select class="form-control" id="sesion_id">
                    <option value="2" selected>2026-06-14 10:00 - Programacion Web</option>
                </select>
            </label>
        </div>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Edicion disponible por 24 horas.</div>
    <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-bold text-[#1a3a6b]">Registrar asistencia de estudiantes</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($estudiantes) ?> registros
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
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($estudiantes) ?> registros
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
</div>

<script>
[document.getElementById('btn-guardar-asistencia-desktop'), document.getElementById('btn-guardar-asistencia-mobile')].forEach(btn => {
    if (!btn) return;
    btn.addEventListener('click', async (e) => {
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
        
        const response = await fetch('<?= e(base_url('api/asistencia.php')) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                sesion_id: sesion_id,
                asistencias: asistencias
            })
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
});
</script>
