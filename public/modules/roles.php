<?php
$roles = [
    'Administrador' => [
        'tone' => 'slate',
        'icon' => 'shield-check',
        'permisos' => [
            'Acceder al dashboard',
            'Gestionar usuarios',
            'Gestionar estudiantes',
            'Gestionar docentes',
            'Gestionar configuración académica',
            'Registrar, consultar y corregir asistencias',
            'Generar reportes',
            'Exportar reportes',
            'Realizar respaldo manual de BD',
        ],
    ],
    'Docente' => [
        'tone' => 'blue',
        'icon' => 'shield',
        'permisos' => [
            'Consultar unidades didácticas asignadas',
            'Registrar asistencia de estudiantes',
            'Modificar asistencia dentro del tiempo autorizado',
            'Consultar asistencia registrada',
            'Generar reportes',
        ],
    ],
    'Estudiante' => [
        'tone' => 'emerald',
        'icon' => 'user-round',
        'permisos' => [
            'Consultar su asistencia',
            'Consultar porcentaje de inasistencias',
            'Ver estado académico',
            'Visualizar reportes personales',
        ],
    ],
];

$allPermissions = [
    'Acceder al dashboard',
    'Gestionar usuarios',
    'Gestionar estudiantes',
    'Gestionar docentes',
    'Gestionar configuración académica',
    'Registrar, consultar y corregir asistencias',
    'Generar reportes',
    'Exportar reportes',
    'Realizar respaldo manual de BD',
    'Consultar unidades didácticas asignadas',
    'Registrar asistencia de estudiantes',
    'Modificar asistencia dentro del tiempo autorizado',
    'Consultar asistencia registrada',
    'Consultar su asistencia',
    'Consultar porcentaje de inasistencias',
    'Ver estado académico',
    'Visualizar reportes personales',
];

$tones = [
    'slate' => ['head' => 'bg-slate-50', 'border' => 'border-slate-200', 'title' => 'text-slate-900', 'icon' => 'text-[#1a3a6b]', 'btn' => 'text-[#1a3a6b] hover:bg-slate-50'],
    'blue' => ['head' => 'bg-blue-50/50', 'border' => 'border-blue-100', 'title' => 'text-blue-800', 'icon' => 'text-blue-600', 'btn' => 'text-blue-700 hover:bg-blue-50/50'],
    'emerald' => ['head' => 'bg-emerald-50/50', 'border' => 'border-emerald-100', 'title' => 'text-emerald-800', 'icon' => 'text-emerald-600', 'btn' => 'text-emerald-700 hover:bg-emerald-50/50'],
];
?>
<div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-3">
        <?php foreach ($roles as $rol => $config): 
            $tone = $tones[$config['tone']]; 
            $modalId = 'modal-permisos-' . strtolower($rol); 
        ?>
            <div class="flex flex-col overflow-hidden rounded-2xl border <?= e($tone['border']) ?> bg-white shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between border-b <?= e($tone['border']) ?> px-6 py-5 <?= e($tone['head']) ?>">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-sm border <?= e($tone['border']) ?>">
                            <i data-lucide="<?= e($config['icon']) ?>" class="h-5 w-5 <?= e($tone['icon']) ?>"></i>
                        </div>
                        <h2 class="text-lg font-bold <?= e($tone['title']) ?>"><?= e($rol) ?></h2>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Activo
                    </span>
                </div>
                
                <div class="flex flex-1 flex-col p-6">
                    <div class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Permisos Asignados (<?= count($config['permisos']) ?>)</div>
                    <ul class="flex-1 space-y-3.5 text-sm text-slate-600">
                        <?php foreach ($config['permisos'] as $permiso): ?>
                            <li class="flex items-start gap-3">
                                <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"></i>
                                <span class="leading-snug"><?= e($permiso) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/50 p-4">
        <i data-lucide="info" class="mt-0.5 h-4.5 w-4.5 shrink-0 text-blue-500"></i>
        <p class="text-sm leading-relaxed text-blue-800">
            <strong>Nota sobre seguridad:</strong> Los roles en este sistema (Administrador, Docente, Estudiante) definen un conjunto estricto y preconfigurado de accesos y permisos. Por motivos de seguridad, la configuración de estos permisos es fija y de solo lectura. Los <em>usuarios</em> heredan estos permisos según el rol que tengan asignado.
        </p>
    </div>
</div>
