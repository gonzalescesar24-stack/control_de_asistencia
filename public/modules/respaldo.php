<?php
$historial = all_respaldos();
?>
<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-semibold text-[#1a3a6b]">Respaldo manual de base de datos</h2>
        <p class="mt-2 text-sm text-slate-500">
            Genera un respaldo completo de la base de datos del sistema. El archivo se descargará automáticamente.
        </p>
        <div class="mt-4 flex gap-3">
            <button
                type="button"
                id="btn-generar-respaldo"
                class="inline-flex items-center gap-2 rounded-lg bg-[#1a3a6b] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#142d54]"
            >
                <i data-lucide="database" class="h-4 w-4"></i>
                Generar respaldo
            </button>
            <button
                type="button"
                onclick="document.getElementById('file-restore').click()"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
            >
                <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                Restaurar desde archivo
            </button>
            <input type="file" id="file-restore" class="hidden" accept=".sql" onchange="restoreBackup(this)">
        </div>
    </div>

    <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-semibold text-[#1a3a6b]">Historial de respaldos</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($historial) ?> registros
            </span>
        </div>
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-th w-16 text-center">#</th>
                    <th class="table-th">Fecha</th>
                    <th class="table-th">Hora</th>
                    <th class="table-th">Usuario</th>
                    <th class="table-th">Tamaño</th>
                    <th class="table-th">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($historial)): ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-slate-500">
                            No hay respaldos generados todavía.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historial as $index => $row): ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                            <td class="table-td"><?= e($row['fecha']) ?></td>
                            <td class="table-td text-slate-500"><?= e(substr((string) $row['hora'], 0, 5)) ?></td>
                            <td class="table-td"><?= e($row['usuario']) ?></td>
                            <td class="table-td text-slate-500"><?= e($row['tamanio']) ?></td>
                            <td class="table-td">
                                <a
                                    href="<?= e(base_url('download-backup.php')) ?>"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#1a3a6b] hover:underline"
                                >
                                    <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                    Descargar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Contenedor móvil -->
    <div class="md:hidden">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-[#1a3a6b]">Historial de respaldos</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($historial) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php if (empty($historial)): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-sm text-slate-500 shadow-sm">
                No hay respaldos generados todavía.
            </div>
        <?php else: ?>
            <?php foreach ($historial as $index => $row): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-[#0b2f63]"><span class="text-slate-400 mr-1">#<?= $index + 1 ?></span><?= e($row['fecha']) ?></h3>
                            <p class="text-xs text-slate-500"><?= e(substr((string) $row['hora'], 0, 5)) ?></p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600"><?= e($row['tamanio']) ?></span>
                    </div>
                    <div class="flex items-center justify-between mt-2 pt-3 border-t border-slate-100">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <i data-lucide="user" class="h-3.5 w-3.5"></i>
                            <?= e($row['usuario']) ?>
                        </div>
                        <a href="<?= e(base_url('download-backup.php')) ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-[#1a3a6b] hover:underline bg-blue-50 px-2 py-1.5 rounded-lg">
                            <i data-lucide="download" class="h-3.5 w-3.5"></i>
                            Descargar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.getElementById('btn-generar-respaldo')?.addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="h-4 w-4 animate-spin"></i> Generando...';
    if (window.lucide) lucide.createIcons();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        // Trigger download via hidden form
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = 'api/backup.php';
        form.target = '_blank'; // open in new tab so download triggers
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        window.showToast('Generando respaldo, el archivo se descargará en breve...', 'green');
        setTimeout(() => location.reload(), 2500);
    } catch (err) {
        window.showToast('Error al generar el respaldo', 'red');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="database" class="h-4 w-4"></i> Generar respaldo';
        if (window.lucide) lucide.createIcons();
    }
});
</script>
<script>
async function restoreBackup(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    if (!file.name.endsWith('.sql')) {
        window.showToast('Solo se permiten archivos .sql', 'red');
        return;
    }
    const result = await Swal.fire({
        title: '¿Restaurar Base de Datos?',
        text: 'ATENCIÓN: Restaurar la base de datos sobrescribirá todos los datos actuales. ¿Está completamente seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, restaurar',
        cancelButtonText: 'No, cancelar',
    });
    
    if (!result.isConfirmed) {
        input.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('backup_file', file);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);

    window.showToast('Restaurando base de datos, por favor espere...', 'blue');
    try {
        const response = await fetch('api/restaurar.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        if (response.ok && data.success) {
            window.showToast('Base de datos restaurada correctamente. Recargando...', 'green');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            window.showToast(data.error || 'Error al restaurar', 'red');
        }
    } catch (err) {
        window.showToast('Error de conexión con el servidor', 'red');
    }
    input.value = '';
}
</script>
