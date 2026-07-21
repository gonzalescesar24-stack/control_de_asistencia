<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';
require_login();

header('Content-Type: application/json');

$estudiantes = estudiantes_filtrados2($_GET);
$html = '';
$mobileHtml = '';

foreach ($estudiantes as $index => $e) {
    $pct = pct($e);
    $state = $e['estado'];
    if ($state === 'Activo') {
        $state = $pct >= 30 ? 'Inhabilitado' : ($pct >= 20 ? 'En riesgo' : 'Activo');
    }
    $pctClass = $pct >= 30 ? 'text-red-600' : ($pct >= 20 ? 'text-amber-500' : 'text-emerald-600');
    
    $html .= '<tr class="transition hover:bg-slate-50/70">';
    $html .= '<td class="table-td text-center text-slate-400 font-medium">' . e((string)($index + 1)) . '</td>';
    $html .= '<td class="table-td font-mono text-xs text-slate-500">' . e($e['codigo']) . '</td>';
    $html .= '<td class="table-td font-semibold text-slate-900">' . e($e['nombres']) . '</td>';
    $html .= '<td class="table-td text-slate-600 text-xs truncate max-w-[150px]" title="' . e($e['programa']) . '">' . e($e['programa']) . '</td>';
    $html .= '<td class="table-td font-medium text-center text-slate-700">' . e($e['ciclo']) . '-' . e($e['seccion']) . '</td>';
    $html .= '<td class="table-td text-slate-600 text-xs truncate max-w-[180px]" title="' . e($e['unidad']) . '">' . e($e['unidad']) . '</td>';
    $html .= '<td class="table-td text-center font-medium text-slate-700">' . e((string)($e['sesiones'] ?? $e['total_sesiones'] ?? 0)) . '</td>';
    $html .= '<td class="table-td text-center font-semibold text-red-600">' . e((string)$e['inasistencias']) . '</td>';
    $html .= '<td class="table-td text-center font-bold ' . $pctClass . '">' . e((string)$pct) . '%</td>';
    $html .= '<td class="table-td text-center">';
    $html .= '<span class="inline-flex justify-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . badge_class($state) . '">';
    $html .= e($state);
    $html .= '</span></td></tr>';

    $mobileHtml .= '<div class="bg-white rounded-xl border p-4 shadow-sm transition hover:shadow-md">';
    $mobileHtml .= '<div class="flex justify-between items-start border-b border-slate-50 pb-2 mb-2">';
    $mobileHtml .= '<div>';
    $mobileHtml .= '<div class="font-semibold text-slate-900">' . e($e['nombres']) . '</div>';
    $mobileHtml .= '<div class="font-mono text-xs text-slate-500">' . e($e['codigo']) . '</div>';
    $mobileHtml .= '</div>';
    $mobileHtml .= '<span class="inline-flex justify-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . badge_class($state) . '">' . e($state) . '</span>';
    $mobileHtml .= '</div>';
    $mobileHtml .= '<div class="grid grid-cols-2 gap-2 text-xs mb-3">';
    $mobileHtml .= '<div class="min-w-0"><span class="text-slate-400 block">Prog. Estudio</span><span class="font-medium text-slate-700 break-words block">' . e($e['programa']) . '</span></div>';
    $mobileHtml .= '<div class="min-w-0"><span class="text-slate-400 block">Unidad</span><span class="font-medium text-slate-700 break-words block">' . e($e['unidad']) . '</span></div>';
    $mobileHtml .= '</div>';
    $mobileHtml .= '<div class="grid grid-cols-4 gap-1 bg-slate-50 rounded-lg p-2 text-center text-[10px] sm:text-xs">';
    $mobileHtml .= '<div class="min-w-0"><span class="text-slate-400 block truncate">Ciclo/Sec</span><span class="font-semibold text-slate-700 truncate block">' . e($e['ciclo']) . '-' . e($e['seccion']) . '</span></div>';
    $mobileHtml .= '<div class="min-w-0"><span class="text-slate-400 block truncate">Sesiones</span><span class="font-semibold text-slate-700 block">' . e((string)($e['sesiones'] ?? $e['total_sesiones'] ?? 0)) . '</span></div>';
    $mobileHtml .= '<div class="min-w-0"><span class="text-slate-400 block truncate">Inasist.</span><span class="font-semibold text-red-600 block">' . e((string)$e['inasistencias']) . '</span></div>';
    $mobileHtml .= '<div class="min-w-0"><span class="text-slate-400 block truncate">% Inasist.</span><span class="font-bold ' . $pctClass . ' block">' . e((string)$pct) . '%</span></div>';
    $mobileHtml .= '</div>';
    $mobileHtml .= '</div>';
}

if (count($estudiantes) === 0) {
    $html = '<tr><td colspan="10" class="py-10 text-center text-slate-500">No se encontraron estudiantes con esos filtros.</td></tr>';
    $mobileHtml = '<div class="py-10 text-center text-slate-500 bg-white rounded-xl border">No se encontraron estudiantes con esos filtros.</div>';
}

echo json_encode([
    'count' => count($estudiantes),
    'html' => $html,
    'mobileHtml' => $mobileHtml
]);
