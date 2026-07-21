<?php $sesiones = all_sesiones(); ?>
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-[#1a3a6b]"><?= e(app_user()['nombre']) ?></h2>
        <p class="mt-1 text-sm text-slate-500">Periodo Académico Actual: 2026-I</p>
        <p class="mt-3 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"><?= count($sesiones) ?> sesiones asignadas</p>
    </div>
    <div class="hidden md:block overflow-x-auto min-h-[300px] rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-semibold text-[#1a3a6b]">Lista de Sesiones</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($sesiones) ?> registros
            </span>
        </div>
        <table class="w-full"><thead class="bg-slate-50"><tr><th class="table-th w-16 text-center">#</th><th class="table-th">Fecha</th><th class="table-th">Hora</th><th class="table-th">Programa</th><th class="table-th">Unidad</th><th class="table-th text-center">Ciclo/Sección</th><th class="table-th">Periodo</th><th class="table-th">Estado</th><th class="table-th text-right">Acciones</th></tr></thead><tbody class="divide-y divide-slate-100">
            <?php foreach ($sesiones as $i => $s): $modalId = 'modal-sesion-' . $i; ?>
                <tr class="transition hover:bg-slate-50/50">
                    <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $i + 1 ?></td>
                    <td class="table-td"><?= e($s['fecha']) ?></td>
                    <td class="table-td"><?= e($s['hora']) ?></td>
                    <td class="table-td"><?= e($s['programa']) ?></td>
                    <td class="table-td"><?= e($s['unidad']) ?></td>
                    <td class="table-td text-center"><?= e($s['ciclo']) ?>-<?= e($s['seccion']) ?></td>
                    <td class="table-td"><?= e($s['periodo']) ?></td>
                    <td class="table-td"><span class="rounded-full px-2 py-1 text-xs font-semibold <?= badge_class($s['estado']) ?>"><?= e($s['estado']) ?></span></td>
                    <td class="table-td">
                        <div class="flex justify-end gap-3">
                            <button type="button" data-modal-target="<?= e($modalId) ?>" class="text-blue-600 transition hover:text-blue-800" title="Ver detalle">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>
    </div>

    <!-- Contenedor móvil -->
    <div class="md:hidden">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-[#1a3a6b]">Lista de Sesiones</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 mobile-table-count-badge">
                <?= count($sesiones) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php foreach ($sesiones as $i => $s): $modalId = 'modal-sesion-' . $i; ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-[#0b2f63]"><span class="text-slate-400 mr-1">#<?= $i + 1 ?></span><?= e($s['unidad']) ?></h3>
                        <p class="text-xs text-slate-500"><?= e($s['programa']) ?> · <?= e($s['ciclo']) ?>-<?= e($s['seccion']) ?></p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= badge_class($s['estado']) ?>"><?= e($s['estado']) ?></span>
                </div>
                <div class="flex items-center justify-between mt-2 pt-3 border-t border-slate-100">
                    <div class="text-xs text-slate-500 font-medium">
                        <i data-lucide="calendar" class="inline-block h-3.5 w-3.5 mr-1 text-slate-400"></i><?= e($s['fecha']) ?>
                        <i data-lucide="clock" class="inline-block h-3.5 w-3.5 ml-2 mr-1 text-slate-400"></i><?= e($s['hora']) ?>
                    </div>
                    <button type="button" data-modal-target="<?= e($modalId) ?>" class="text-blue-600 font-semibold text-xs hover:underline flex items-center gap-1">
                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                        Ver detalle
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<?php foreach ($sesiones as $i => $s): $modalId = 'modal-sesion-' . $i; ?>
    <div id="<?= e($modalId) ?>" data-modal class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
        <div class="mx-auto mt-20 max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-[#1a3a6b]">Detalle de sesion academica</h2>
                    <p class="mt-1 text-sm text-slate-500"><?= e($s['unidad']) ?> · <?= e($s['ciclo']) ?>-<?= e($s['seccion']) ?> · <?= e($s['periodo']) ?></p>
                </div>
                <button type="button" data-modal-close class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <dl class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold text-slate-500">Fecha</dt><dd class="font-semibold"><?= e($s['fecha']) ?></dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold text-slate-500">Hora</dt><dd class="font-semibold"><?= e($s['hora']) ?></dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold text-slate-500">Programa</dt><dd class="font-semibold"><?= e($s['programa']) ?></dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold text-slate-500">Docente</dt><dd class="font-semibold"><?= e($s['docente']) ?></dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold text-slate-500">Estado</dt><dd><span class="rounded-full px-2 py-1 text-xs font-semibold <?= badge_class($s['estado']) ?>"><?= e($s['estado']) ?></span></dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold text-slate-500">Accion sugerida</dt><dd class="font-semibold"><?= $s['estado'] === 'Pendiente' ? 'Registrar asistencia' : 'Consultar registros' ?></dd></div>
            </dl>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="<?= e(base_url('index.php?m=registrar-asistencia')) ?>" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white hover:bg-[#142d54]">Registrar asistencia</a>
                <a href="<?= e(base_url('index.php?m=consultar-asistencia')) ?>" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Consultar asistencia</a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
