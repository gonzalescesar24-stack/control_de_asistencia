<?php
$pdo = db();
$logs = $pdo->query("
    SELECT a.fecha_hora, u.nombre as usuario, u.rol, a.modulo, a.accion, a.detalles 
    FROM auditoria a 
    JOIN usuarios u ON a.usuario_id = u.id 
    UNION ALL 
    SELECT aa.created_at as fecha_hora, aa.modificado_por as usuario, 'Sistema' as rol, 'Asistencia' as modulo, 'MODIFICAR' as accion, CONCAT('Cambio de estado: ', aa.estado_anterior, ' -> ', aa.estado_nuevo, '. Motivo: ', IFNULL(aa.motivo_cambio, 'N/A')) as detalles 
    FROM auditoria_asistencias aa 
    ORDER BY fecha_hora DESC LIMIT 100
")->fetchAll();
?>
<div class="space-y-6">
    <div class="hidden md:block rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
            <h2 class="font-semibold text-[#1a3a6b]">Registros de Auditoría</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($logs) ?> registros
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-th w-16 text-center whitespace-nowrap">#</th>
                        <th class="table-th">Fecha y Hora</th>
                        <th class="table-th">Usuario</th>
                        <th class="table-th">Módulo / Tabla</th>
                        <th class="table-th">Acción</th>
                        <th class="table-th">Detalles del Cambio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($logs as $index => $log): ?>
                        <tr class="transition hover:bg-slate-50/50">
                            <td class="table-td text-center text-slate-400 text-xs font-semibold"><?= $index + 1 ?></td>
                            <td class="table-td text-slate-500 whitespace-nowrap"><?= e($log['fecha_hora']) ?></td>
                            <td class="table-td">
                                <div class="font-medium text-slate-900"><?= e($log['usuario']) ?></div>
                                <div class="text-xs text-slate-500"><?= e(role_label($log['rol'])) ?></div>
                            </td>
                            <td class="table-td font-medium text-slate-700"><?= e($log['modulo']) ?></td>
                            <td class="table-td">
                                <?php
                                    $color = 'bg-slate-100 text-slate-600';
                                    if ($log['accion'] === 'UPDATE' || $log['accion'] === 'MODIFICAR') $color = 'bg-amber-100 text-amber-700';
                                    if ($log['accion'] === 'INSERT' || $log['accion'] === 'CREAR') $color = 'bg-emerald-100 text-emerald-700';
                                    if ($log['accion'] === 'DELETE' || $log['accion'] === 'ELIMINAR') $color = 'bg-red-100 text-red-700';
                                ?>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $color ?>"><?= e($log['accion']) ?></span>
                            </td>
                            <td class="table-td text-slate-600 text-xs max-w-md">
                                <?= nl2br(e($log['detalles'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">No hay registros de auditoría.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Contenedor móvil -->
    <div class="md:hidden">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-[#1a3a6b]">Registros de Auditoría</h2>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                <?= count($logs) ?> registros
            </span>
        </div>
        <div class="grid gap-4">
        <?php if (empty($logs)): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-sm text-slate-500 shadow-sm">
                No hay registros de auditoría.
            </div>
        <?php else: ?>
            <?php foreach ($logs as $index => $log): ?>
                <?php
                    $color = 'bg-slate-100 text-slate-600';
                    if ($log['accion'] === 'UPDATE' || $log['accion'] === 'MODIFICAR') $color = 'bg-amber-100 text-amber-700';
                    if ($log['accion'] === 'INSERT' || $log['accion'] === 'CREAR') $color = 'bg-emerald-100 text-emerald-700';
                    if ($log['accion'] === 'DELETE' || $log['accion'] === 'ELIMINAR') $color = 'bg-red-100 text-red-700';
                ?>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col gap-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-[#0b2f63]"><span class="text-slate-400 mr-1">#<?= $index + 1 ?></span><?= e($log['modulo']) ?></h3>
                            <p class="text-xs text-slate-500"><?= e($log['fecha_hora']) ?></p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $color ?>"><?= e($log['accion']) ?></span>
                    </div>
                    <div class="text-sm text-slate-600 border-t border-slate-100 pt-3 mt-1">
                        <?= nl2br(e($log['detalles'])) ?>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                            <i data-lucide="user" class="h-3.5 w-3.5"></i>
                            <?= e($log['usuario']) ?>
                            <span class="font-normal text-slate-400">(<?= e(role_label($log['rol'])) ?>)</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</div>
