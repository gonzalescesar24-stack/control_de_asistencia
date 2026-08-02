<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/session.php';
start_app_session();
require_once __DIR__ . '/../app/helpers.php';

$user = app_user();
if (!$user) {
    header('Location: ' . base_url('login.php'));
    exit;
}

if (($user['must_change_password'] ?? 0) != 1) {
    header('Location: ' . base_url('index.php'));
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambio Obligatorio de Contraseña - <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= e(base_url('assets/images/logo_vrht.ico')) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(base_url('assets/app.css')) ?>">
</head>
<body class="min-h-screen bg-cover bg-center bg-no-repeat" style="background-image: linear-gradient(rgba(26, 58, 107, 0.85), rgba(15, 35, 71, 0.95)), url('<?= e(base_url('assets/images/foto_vrht.jpeg')) ?>');">
<main class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">
        <header class="mb-8 text-center">
            <div class="mb-6 flex justify-center">
                <img src="<?= e(base_url('assets/images/logo_vrht.png')) ?>" alt="Logo IES VRHT" class="h-32 object-contain drop-shadow-xl">
            </div>
            <h1 class="text-2xl font-bold leading-tight text-white">IES "VÍCTOR RAÚL<br>HAYA DE LA TORRE"</h1>
        </header>

        <div class="rounded-2xl bg-white p-8 shadow-2xl border-t-4 border-amber-500">
            <h2 class="mb-2 text-xl font-semibold text-[#1a3a6b]">Actualiza tu contraseña</h2>
            <p class="text-sm text-slate-600 mb-6">Por motivos de seguridad, debes cambiar tu contraseña antes de continuar usando el sistema.</p>
            
            <div id="alert-container" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm">
                <span id="alert-message"></span>
            </div>

            <form id="force-change-form">
                
                <label class="mb-4 block">
                    <span class="mb-1 block text-sm font-medium text-gray-700">Nueva contraseña</span>
                    <input name="password" id="force_new_password" type="password" minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}" title="Debe contener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial" class="form-control" placeholder="Mín. 8 caracteres, 1 mayúscula, 1 número, 1 símbolo" required oninput="checkPasswordStrength(this.value, 'force')">
                </label>
                <div class="mb-4 mt-[-0.5rem] text-[11px] sm:text-xs text-slate-500 grid grid-cols-1 sm:grid-cols-2 gap-1.5" id="pwd_rules_force">
                    <div id="force_rule_len" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Mínimo 8 caracteres</div>
                    <div id="force_rule_upper" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Una mayúscula</div>
                    <div id="force_rule_lower" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Una minúscula</div>
                    <div id="force_rule_num" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Un número</div>
                    <div id="force_rule_sym" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Un símbolo (@, #, !, etc.)</div>
                </div>

                <label class="mb-6 block">
                    <span class="mb-1 block text-sm font-medium text-gray-700">Confirmar nueva contraseña</span>
                    <input name="password_confirm" type="password" minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}" title="Debe contener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial" class="form-control" placeholder="Repite la contraseña" required>
                </label>

                <button type="submit" id="submit-btn" class="mb-4 w-full rounded-lg bg-[#1a3a6b] px-4 py-3 font-semibold text-white transition-colors hover:bg-[#234a85] flex justify-center items-center gap-2">
                    Actualizar y continuar
                </button>
            </form>
            
            <div class="mt-4 text-center">
                <a href="<?= e(base_url('logout.php')) ?>" class="text-sm text-slate-500 hover:text-slate-700 hover:underline">Cerrar sesión</a>
            </div>
        </div>
    </div>
</main>

<script>
function checkPasswordStrength(pwd, prefix) {
    const rules = [
        { id: 'len', regex: /.{8,}/, text: 'Mínimo 8 caracteres' },
        { id: 'upper', regex: /[A-Z]/, text: 'Una mayúscula' },
        { id: 'lower', regex: /[a-z]/, text: 'Una minúscula' },
        { id: 'num', regex: /\d/, text: 'Un número' },
        { id: 'sym', regex: /[\W_]/, text: 'Un símbolo (@, #, !, etc.)' }
    ];

    rules.forEach(r => {
        const el = document.getElementById(`${prefix}_rule_${r.id}`);
        if (!el) return;
        
        if (r.regex.test(pwd)) {
            el.className = 'flex items-center gap-1 text-emerald-600 transition-colors';
            el.innerHTML = `<i data-lucide="check-circle-2" class="w-3 h-3 shrink-0"></i> ${r.text}`;
        } else {
            el.className = 'flex items-center gap-1 transition-colors ' + (pwd.length > 0 ? 'text-red-500' : 'text-slate-500');
            const iconName = pwd.length > 0 ? 'x-circle' : 'circle';
            el.innerHTML = `<i data-lucide="${iconName}" class="w-3 h-3 shrink-0"></i> ${r.text}`;
        }
    });
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

const form = document.getElementById('force-change-form');
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const pwd = form.password.value;
        const confirm = form.password_confirm.value;
        const btn = document.getElementById('submit-btn');
        const alertBox = document.getElementById('alert-container');
        const alertMsg = document.getElementById('alert-message');
        
        alertBox.classList.add('hidden');
        alertBox.classList.remove('bg-red-50', 'border-red-200', 'text-red-700', 'bg-green-50', 'border-green-200', 'text-green-700');
        
        if (pwd !== confirm) {
            alertBox.classList.remove('hidden');
            alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
            alertMsg.textContent = 'Las contraseñas no coinciden.';
            return;
        }

        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Actualizando...`;

        try {
            const formData = new FormData(form);
            // Agregamos un campo de accion u origen si fuera necesario
            const response = await fetch('<?= e(base_url('api/force_change_password.php')) ?>', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            alertBox.classList.remove('hidden');
            if (result.success) {
                alertBox.classList.add('bg-green-50', 'border-green-200', 'text-green-700');
                alertMsg.textContent = '¡Contraseña actualizada! Redirigiendo...';
                setTimeout(() => {
                    window.location.href = '<?= e(base_url('index.php')) ?>';
                }, 1500);
            } else {
                alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
                alertMsg.textContent = result.error || 'Ocurrió un error. Inténtalo de nuevo.';
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            }
        } catch (err) {
            alertBox.classList.remove('hidden');
            alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
            alertMsg.textContent = 'Error de conexión. Inténtalo de nuevo.';
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
        }
    });
}
</script>
</body>
</html>
