<?php
$user = app_user();
$pdo = db();

$stmtEst = $pdo->prepare('SELECT e.*, p.nombre as programa, pc.nombre as ciclo FROM estudiantes e LEFT JOIN programas p ON e.programa_id = p.id LEFT JOIN periodos_curriculares pc ON e.periodo_curricular_id = pc.id WHERE e.nombres LIKE ?');
$searchTerm = '%' . str_replace(' ', '%', $user['nombre']) . '%';
$stmtEst->execute([$searchTerm]);
$est = $stmtEst->fetch();

if (!$est) {
    echo "<div class='p-4 text-red-600 bg-red-50 rounded-lg'>Estudiante no encontrado en la base de datos.</div>";
    return;
}

// Fetch stats per unidad
$stmtAsist = $pdo->prepare('
    SELECT 
        ud.nombre as unidad, 
        COUNT(a.id) as total,
        SUM(CASE WHEN a.estado = "Inasistente" THEN 1 ELSE 0 END) as inasistencias
    FROM asistencias a
    JOIN sesiones s ON a.sesion_id = s.id
    LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id
    WHERE a.estudiante_id = ?
    GROUP BY ud.nombre
');
$stmtAsist->execute([$est['id']]);
$statsPorUnidad = $stmtAsist->fetchAll();

$evaluacion = [];
$peorEstado = 'Activo';

foreach ($statsPorUnidad as $st) {
    $total = (int)$st['total'];
    $inas = (int)$st['inasistencias'];
    $pct = $total > 0 ? (int)round(($inas / $total) * 100) : 0;
    
    $estadoUnidad = 'Activo';
    $obs = "Sin inasistencias críticas.";
    
    if ($pct >= 30) {
        $estadoUnidad = 'Inhabilitado';
        $obs = "Se ha superado el límite del 30% de inasistencias.";
        $peorEstado = 'Inhabilitado';
    } elseif ($pct >= 20) {
        $estadoUnidad = 'En riesgo';
        $obs = "$inas inasistencias en $total sesiones. Cercano al límite.";
        if ($peorEstado !== 'Inhabilitado') {
            $peorEstado = 'En riesgo';
        }
    } else {
        $obs = "$inas inasistencias en $total sesiones. Asistencia regular.";
    }
    
    $evaluacion[] = [
        'unidad' => $st['unidad'],
        'pct' => $pct,
        'estado' => $estadoUnidad,
        'obs' => $obs
    ];
}

// Override student global state based on its units if needed, or just use what's in DB
// For fidelity to reality, if a unit is inhabilitado, the student is inhabilitado for that unit
// We will show the DB state of the student globally, or the calculated worst state.
$est['estado'] = $peorEstado; 

$estadoConfig = match ($est['estado']) {
    'En riesgo' => [
        'bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-700',
        'icon' => 'text-amber-600', 'big' => 'text-amber-600', 'lucide' => 'triangle-alert',
        'motivo' => 'Una o más unidades didácticas presentan un porcentaje de inasistencias igual o superior al 20%.',
        'recomendacion' => 'Tienes una unidad didáctica cercana al límite del 30%. Se recomienda mejorar tu asistencia de inmediato para evitar la inhabilitación.',
    ],
    'Inhabilitado' => [
        'bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-700',
        'icon' => 'text-red-600', 'big' => 'text-red-600', 'lucide' => 'circle-x',
        'motivo' => 'Se ha alcanzado o superado el 30% de inasistencias en una o más unidades didácticas.',
        'recomendacion' => 'Has alcanzado o superado el límite del 30% en una unidad didáctica. Consulta con coordinación académica para conocer los pasos a seguir.',
    ],
    default => [
        'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-700',
        'icon' => 'text-emerald-600', 'big' => 'text-emerald-600', 'lucide' => 'circle-check',
        'motivo' => 'El porcentaje de inasistencias se encuentra dentro del límite permitido.',
        'recomendacion' => 'Tu asistencia es satisfactoria. Continúa asistiendo regularmente para mantener tu estado activo durante todo el periodo.',
    ],
};
?>
<div class="space-y-4">
    <?php render_estudiante_header($est); ?>

    <div class="rounded-xl border-2 p-6 <?= $estadoConfig['bg'] ?> <?= $estadoConfig['border'] ?>">
        <div class="mb-4 flex items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border-2 <?= $estadoConfig['bg'] ?> <?= $estadoConfig['border'] ?>">
                <i data-lucide="<?= e($estadoConfig['lucide']) ?>" class="h-8 w-8 <?= $estadoConfig['icon'] ?>"></i>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Estado Académico Actual</p>
                <p class="text-3xl font-bold <?= $estadoConfig['big'] ?>"><?= e($est['estado']) ?></p>
            </div>
            <div class="ml-auto text-right">
                <p class="text-xs text-slate-500">Última actualización</p>
                <p class="text-sm font-medium text-slate-900"><?= date('d M Y') ?></p>
            </div>
        </div>
        <div class="rounded-lg border bg-white/60 p-3 <?= $estadoConfig['border'] ?>">
            <p class="mb-0.5 text-xs font-semibold text-slate-500">Motivo</p>
            <p class="text-sm <?= $estadoConfig['text'] ?>"><?= e($estadoConfig['motivo']) ?></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-semibold text-[#1a3a6b]">Evaluación por unidad didáctica</h3>
            <p class="mt-0.5 text-xs text-slate-500">Periodo 2026-I</p>
        </div>
        <div class="divide-y divide-slate-100">
            <?php foreach ($evaluacion as $item):
                $p = $item['pct'];
                $bar = $p >= 30 ? 'bg-red-500' : ($p >= 20 ? 'bg-amber-500' : 'bg-emerald-500');
                $pctClass = $p >= 30 ? 'text-red-600' : ($p >= 20 ? 'text-amber-600' : 'text-emerald-600');
            ?>
                <div class="px-5 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-[#1a3a6b]"><?= e($item['unidad']) ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= e($item['obs']) ?></p>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-2 max-w-48 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full <?= $bar ?> transition-all duration-500" style="width: <?= min(($p / 30) * 100, 100) ?>%"></div>
                                </div>
                                <span class="text-xs font-semibold <?= $pctClass ?>"><?= $p ?>%</span>
                            </div>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold <?= badge_class($item['estado']) ?>"><?= e($item['estado']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($evaluacion)): ?>
                <div class="px-5 py-8 text-center text-slate-500">No hay evaluaciones disponibles.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="rounded-xl border p-5 <?= $estadoConfig['bg'] ?> <?= $estadoConfig['border'] ?>">
        <div class="flex items-start gap-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border <?= $estadoConfig['bg'] ?> <?= $estadoConfig['border'] ?>">
                <i data-lucide="file-text" class="h-4 w-4 <?= $estadoConfig['icon'] ?>"></i>
            </div>
            <div>
                <p class="mb-1 text-sm font-semibold <?= $estadoConfig['text'] ?>">Recomendación académica</p>
                <p class="text-sm <?= $estadoConfig['text'] ?>"><?= e($estadoConfig['recomendacion']) ?></p>
                <?php if ($est['estado'] !== 'Activo'): ?>
                    <p class="mt-2 text-xs text-slate-500">
                        Para consultas, dirígete a la Coordinación Académica del IES "VÍCTOR RAÚL HAYA DE LA TORRE".
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
