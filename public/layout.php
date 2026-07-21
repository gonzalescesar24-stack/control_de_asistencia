<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers.php';

require_login();

$user = app_user();
$module = $_GET['m'] ?? ($user['rol'] === 'admin' ? 'dashboard' : ($user['rol'] === 'docente' ? 'mis-sesiones' : 'mi-asistencia'));
$menu = module_menu($user['rol']);

if (!isset($menu[$module])) {
    $module = array_key_first($menu);
}

$title = page_title($module);
$notifications = app_notifications();

function notif_dot_class(string $tipo): string
{
    return match ($tipo) {
        'inhabilitado' => 'bg-red-500',
        'riesgo' => 'bg-amber-500',
        default => 'bg-blue-400',
    };
}

function render_stat(string $label, string|int $value, string $tone = 'blue'): void
{
    $tones = [
        'blue' => 'bg-blue-50 text-[#1a3a6b]',
        'gold' => 'bg-amber-50 text-amber-700',
        'green' => 'bg-emerald-50 text-emerald-700',
        'red' => 'bg-red-50 text-red-700',
    ];
    ?>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
        <p class="text-xs font-medium text-slate-500"><?= e($label) ?></p>
        <p class="mt-2 text-2xl font-bold <?= $tones[$tone] ?? $tones['blue'] ?> rounded-lg inline-block px-3 py-1 transition-colors"><?= e($value) ?></p>
    </div>
    <?php
}

function render_filters(array $filters): void
{
    foreach ($filters as $label => $options): ?>
        <label class="block">
            <span class="mb-1 block text-xs font-semibold text-slate-500"><?= e($label) ?></span>
            <select id="filter-<?= strtolower(str_replace(' ', '-', $label)) ?>" class="form-control filter-select">
                <?php foreach ($options as $option): ?>
                    <option><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endforeach;
}

function render_estudiante_header(array $est): void
{
    $name = trim($est['nombres']);
    $iniciales = strtoupper($name[0] . substr($name, (int) strrpos($name, ' ') + 1, 1));
    ?>
    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1a3a6b] text-lg font-bold text-white"><?= e($iniciales) ?></div>
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-[#1a3a6b]"><?= e($est['nombres']) ?></p>
            <p class="text-xs font-medium text-[#1a3a6b]"><?= e($est['programa']) ?></p>
            <p class="text-xs text-slate-500"><?= e($est['codigo']) ?> · Ciclo <?= e($est['ciclo']) ?> · Seccion <?= e($est['seccion']) ?> · Periodo 2026-I</p>
        </div>
        <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold <?= badge_class($est['estado']) ?>"><?= e($est['estado']) ?></span>
    </div>
    <?php
}

function render_button(string $label, string $style = 'primary'): void
{
    $class = $style === 'secondary'
        ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
        : 'bg-[#1a3a6b] text-white hover:bg-[#142d54]';
    echo '<button type="button" class="btn-animate rounded-lg px-4 py-2 text-sm font-semibold transition-all duration-200 shadow-sm hover:shadow-md ' . $class . '">' . e($label) . '</button>';
}

function render_modal_button(string $label, string $modalId, string $style = 'primary'): void
{
    $class = $style === 'secondary'
        ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
        : 'bg-[#1a3a6b] text-white hover:bg-[#142d54]';
    echo '<button type="button" data-modal-target="' . e($modalId) . '" class="btn-animate rounded-lg px-4 py-2 text-sm font-semibold transition-all duration-200 shadow-sm hover:shadow-md ' . $class . '">' . e($label) . '</button>';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    <title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= e(base_url('assets/images/logo_vrht.ico')) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= e(base_url('assets/icons.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(base_url('assets/app.css')) ?>">
    <script src="<?= e(base_url('assets/custom-select.js')) ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="flex min-h-screen bg-slate-50">
    <!-- Overlay móvil -->
    <div id="sidebar-overlay" class="hidden fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="hidden fixed inset-y-0 left-0 z-50 w-64 shrink-0 bg-[#1a3a6b] text-white transition-transform duration-300 lg:static lg:block">
        <div class="flex h-full flex-col lg:sticky lg:top-0 lg:h-screen">
            <div class="border-b border-white/10 px-5 py-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center">
                        <img src="<?= e(base_url('assets/images/logo_vrht.png')) ?>" alt="Logo VRHT" class="h-full w-full object-contain drop-shadow-md">
                    </div>
                    <div>
                        <p class="text-[11px] font-bold leading-tight uppercase">IES "VÍCTOR RAÚL<br>HAYA DE LA TORRE"</p>
                        <p class="text-[10px] mt-0.5 text-blue-200">Control de Asistencia</p>
                    </div>
                </div>
                <!-- Botón cerrar solo móvil -->
                <button type="button" class="lg:hidden text-white/70 hover:text-white" onclick="toggleSidebar()">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <?php foreach ($menu as $id => [$label, $icon]): ?>
                    <a href="<?= e(base_url('index.php?m=' . $id)) ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition <?= $module === $id ? 'bg-[#c8a84b] font-semibold text-[#1a3a6b]' : 'text-blue-100 hover:bg-white/10' ?>">
                        <i data-lucide="<?= e($icon) ?>" class="h-4 w-4"></i>
                        <span><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>

    <main class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 shrink-0 border-b border-slate-200 bg-white/95 backdrop-blur px-4 py-3 lg:px-6 z-40">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" onclick="toggleSidebar()" class="lg:hidden p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg transition-colors">
                        <i data-lucide="menu" class="h-6 w-6"></i>
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[#1a3a6b]"><?= e($title) ?></h1>
                        <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">Periodo Académico: 2026-I</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <button type="button" data-notif-toggle class="relative rounded-lg border border-slate-200 bg-white p-2 text-slate-400 transition hover:text-[#1a3a6b]">
                            <i data-lucide="bell" class="h-5 w-5"></i>
                            <?php if (count($notifications) > 0): ?>
                                <span id="notif-badge" class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white"><?= count($notifications) ?></span>
                            <?php else: ?>
                                <span id="notif-badge" class="absolute right-1 top-1 hidden h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">0</span>
                            <?php endif; ?>
                        </button>
                        <div id="notif-panel" class="absolute right-0 top-full z-40 mt-2 hidden w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <h3 class="text-sm font-semibold text-[#1a3a6b]">Notificaciones</h3>
                                <button type="button" data-notif-mark-all class="text-xs font-medium text-[#1a3a6b] hover:underline">Marcar todas como leídas</button>
                            </div>
                            <div id="notif-list" class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                                <?php foreach ($notifications as $notif): ?>
                                    <div
                                        data-notif-item="<?= (int) $notif['id'] ?>"
                                        class="cursor-pointer px-4 py-3 transition hover:bg-slate-50 <?= $notif['id'] >= 3 ? 'opacity-60' : '' ?>"
                                    >
                                        <div class="flex items-start gap-2.5">
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full <?= notif_dot_class($notif['tipo']) ?>"></span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs leading-snug <?= $notif['id'] >= 3 ? 'text-slate-500' : 'font-medium text-slate-800' ?>"><?= e($notif['msg']) ?></p>
                                                <p class="mt-1 text-[10px] text-slate-400"><?= e($notif['tiempo']) ?></p>
                                            </div>
                                            <?php if ($notif['id'] < 3): ?>
                                                <span data-notif-unread class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#1a3a6b]"></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!$notifications): ?>
                                    <div class="p-6 text-center text-xs text-slate-500">
                                        <i data-lucide="check-circle-2" class="mx-auto mb-2 h-8 w-8 text-emerald-400"></i>
                                        Todo al día. No hay alertas académicas.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="border-t border-slate-100 px-4 py-2.5">
                                <button type="button" data-notif-close class="w-full text-xs font-medium text-[#1a3a6b] hover:underline">Ver todas las alertas académicas</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 border-l border-slate-200 pl-3 relative cursor-pointer" onclick="document.getElementById('user-menu').classList.toggle('hidden')">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1a3a6b] text-xs font-bold text-white">
                            <?= e(user_initials($user['nombre'])) ?>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-medium text-[#1a3a6b]"><?= e($user['nombre']) ?></p>
                            <p class="text-[10px] text-slate-500"><?= e(role_label($user['rol'])) ?></p>
                        </div>
                        <i data-lucide="chevron-down" class="hidden h-3 w-3 text-slate-400 sm:block"></i>
                        <div id="user-menu" class="absolute right-0 top-full mt-3 w-48 bg-white border border-slate-200 rounded-lg shadow-lg hidden z-50 overflow-hidden">
                            <button type="button" data-modal-target="modal-password" class="w-full text-left block px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">Cambiar Contraseña</button>
                            <a href="<?= e(base_url('logout.php')) ?>" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 border-t border-slate-100">Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <section class="flex-1 p-4 lg:p-8 overflow-x-hidden w-full">
            <?php require __DIR__ . '/modules/' . $module . '.php'; ?>
        </section>
    </main>
</div>

<!-- Modal Cambiar Contraseña -->
<div id="modal-password" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="font-bold text-[#1a3a6b]">Cambiar Contraseña</h3>
            <button type="button" data-modal-close class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        <form onsubmit="changePassword(event)" class="p-5">
            <div class="space-y-4">
                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Contraseña Actual</span>
                    <input type="password" name="current_password" required class="form-control w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Nueva Contraseña</span>
                    <input type="password" name="new_password" required minlength="6" class="form-control w-full">
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-modal-close class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancelar</button>
                <button type="submit" class="rounded-lg bg-[#1a3a6b] px-4 py-2 text-sm font-semibold text-white hover:bg-[#142d54]">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
async function changePassword(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    
    try {
        const response = await fetch('api/change_password.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (response.ok && data.success) {
            window.showToast('Contraseña actualizada exitosamente', 'green');
            form.closest('[data-modal]').classList.add('hidden');
            form.reset();
        } else {
            window.showToast(data.error || 'Error al cambiar contraseña', 'red');
        }
    } catch (err) {
        window.showToast('Error de conexión', 'red');
    }
}
</script>
<script>
refreshAppIcons();
window.showToast = function(message, tone = 'green') {
    const toast = document.createElement('div');
    const tones = {
        green: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        blue: 'border-blue-200 bg-blue-50 text-blue-700',
        red: 'border-red-200 bg-red-50 text-red-700',
        amber: 'border-amber-200 bg-amber-50 text-amber-700'
    };
    toast.className = 'fixed bottom-4 right-4 z-[60] rounded-xl border px-5 py-3 text-sm font-semibold shadow-lg transition-opacity duration-300 ' + (tones[tone] || tones.green);
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 2800);
};

document.addEventListener('click', (event) => {
    const toaster = event.target.closest('[data-toast-message]');
    if (toaster) {
        window.showToast(toaster.dataset.toastMessage, toaster.dataset.toastTone || 'green');
    }

    const opener = event.target.closest('[data-modal-target]');
    if (opener) {
        const modal = document.getElementById(opener.dataset.modalTarget);
        if (modal) {
            modal.classList.remove('hidden');
            refreshAppIcons(modal);
        }
    }

    const closer = event.target.closest('[data-modal-close]');
    if (closer) {
        const modal = closer.closest('[data-modal]');
        if (modal) modal.classList.add('hidden');
    }

    const backdrop = event.target.matches('[data-modal]');
    if (backdrop) event.target.classList.add('hidden');

    const notifToggle = event.target.closest('[data-notif-toggle]');
    if (notifToggle) {
        document.getElementById('notif-panel')?.classList.toggle('hidden');
        return;
    }

    const notifMarkAll = event.target.closest('[data-notif-mark-all]');
    if (notifMarkAll) {
        document.querySelectorAll('[data-notif-item]').forEach((item) => {
            item.classList.add('opacity-60');
            item.querySelector('p')?.classList.remove('font-medium', 'text-slate-800');
            item.querySelector('p')?.classList.add('text-slate-500');
            item.querySelector('[data-notif-unread]')?.remove();
        });
        const badge = document.getElementById('notif-badge');
        if (badge) badge.classList.add('hidden');
        return;
    }

    const notifItem = event.target.closest('[data-notif-item]');
    if (notifItem) {
        notifItem.classList.add('opacity-60');
        notifItem.querySelector('p')?.classList.remove('font-medium', 'text-slate-800');
        notifItem.querySelector('p')?.classList.add('text-slate-500');
        notifItem.querySelector('[data-notif-unread]')?.remove();
        const unread = document.querySelectorAll('[data-notif-unread]').length;
        const badge = document.getElementById('notif-badge');
        if (badge) {
            if (unread > 0) {
                badge.textContent = String(unread);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
        return;
    }

    const notifClose = event.target.closest('[data-notif-close]');
    if (notifClose) {
        document.getElementById('notif-panel')?.classList.add('hidden');
        return;
    }

    const notifPanel = document.getElementById('notif-panel');
    if (notifPanel && !notifPanel.classList.contains('hidden') && !event.target.closest('#notif-panel') && !event.target.closest('[data-notif-toggle]')) {
        notifPanel.classList.add('hidden');
    }
});

// Helper for dynamic filtering of Unidades based on Programa
function filterUnidadesGlobal(programaId, selectElementId) {
    const unidadSelect = document.getElementById(selectElementId);
    if (!unidadSelect) return;
    
    unidadSelect.value = '';
    
    Array.from(unidadSelect.options).forEach(option => {
        if (!option.value) return; // Skip the empty option
        
        if (!programaId || option.dataset.programaId == programaId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
}
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof flatpickr !== 'undefined') {
            flatpickr('input[type="date"]', {
                locale: "es",
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                monthSelectorType: "static",
            });
            
            flatpickr('input[type="time"]', {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true
            });
        }
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('[data-notif-toggle]') && !e.target.closest('#notif-panel')) {
            const notifPanel = document.getElementById('notif-panel');
            if (notifPanel) notifPanel.classList.add('hidden');
        }
        if (!e.target.closest('.relative.cursor-pointer')) {
            const userMenu = document.getElementById('user-menu');
            if (userMenu) userMenu.classList.add('hidden');
        }
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('flex');
            overlay.classList.toggle('hidden');
        }
    }
</script>
</body>
</html>
