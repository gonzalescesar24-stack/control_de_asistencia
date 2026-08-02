<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/session.php';
start_app_session();
require_once __DIR__ . '/../app/helpers.php';

if (app_user()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$token = $_GET['token'] ?? '';
$isValidToken = false;

if (!empty($token)) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_token_expires_at > NOW()");
        $stmt->execute([$token]);
        if ($stmt->fetch()) {
            $isValidToken = true;
        }
    } catch (Exception $e) {
        // Ignorar
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva Contraseña - <?= e(APP_NAME) ?></title>
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

        <div class="rounded-2xl bg-white p-8 shadow-2xl">
            <h2 class="mb-6 text-xl font-semibold text-[#1a3a6b]">Restablecer contraseña</h2>
            
            <?php if (!$isValidToken): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    El enlace es inválido o ha expirado. Por favor, solicita un nuevo enlace de recuperación.
                </div>
                <div class="mt-6 text-center">
                    <a href="<?= e(base_url('forgot_password.php')) ?>" class="inline-block rounded-lg bg-[#1a3a6b] px-4 py-2 text-white hover:bg-[#234a85]">Solicitar nuevo enlace</a>
                </div>
            <?php else: ?>
                <div id="alert-container" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm">
                    <span id="alert-message"></span>
                </div>

                <form id="reset-password-form">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    
                    <label class="mb-4 block">
                        <span class="mb-1 block text-sm font-medium text-gray-700">Nueva contraseña</span>
                        <input name="password" id="reset_new_password" type="password" minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}" title="Debe contener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial" class="form-control" placeholder="Mín. 8 caracteres, 1 mayúscula, 1 número, 1 símbolo" required oninput="checkPasswordStrength(this.value, 'reset')">
                    </label>
                    <div class="mb-4 mt-[-0.5rem] text-[11px] sm:text-xs text-slate-500 grid grid-cols-1 sm:grid-cols-2 gap-1.5" id="pwd_rules_reset">
                        <div id="reset_rule_len" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Mínimo 8 caracteres</div>
                        <div id="reset_rule_upper" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Una mayúscula</div>
                        <div id="reset_rule_lower" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Una minúscula</div>
                        <div id="reset_rule_num" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Un número</div>
                        <div id="reset_rule_sym" class="flex items-center gap-1"><i data-lucide="circle" class="w-3 h-3 shrink-0"></i> Un símbolo (@, #, !, etc.)</div>
                    </div>

                    <label class="mb-6 block">
                        <span class="mb-1 block text-sm font-medium text-gray-700">Confirmar nueva contraseña</span>
                        <input name="password_confirm" type="password" minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}" title="Debe contener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial" class="form-control" placeholder="Repite la contraseña" required>
                    </label>

                    <button type="submit" id="submit-btn" class="mb-4 w-full rounded-lg bg-[#1a3a6b] px-4 py-3 font-semibold text-white transition-colors hover:bg-[#234a85] flex justify-center items-center gap-2">
                        Actualizar contraseña
                    </button>
                </form>
                
                <div id="success-action" class="hidden mt-6 text-center">
                    <a href="<?= e(base_url('login.php')) ?>" class="inline-block w-full rounded-lg bg-green-600 px-4 py-3 font-semibold text-white hover:bg-green-700">Ir a Iniciar sesión</a>
                </div>
            <?php endif; ?>
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

const form = document.getElementById('reset-password-form');
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const pwd = form.password.value;
        const confirm = form.password_confirm.value;
        const btn = document.getElementById('submit-btn');
        const alertBox = document.getElementById('alert-container');
        const alertMsg = document.getElementById('alert-message');
        
        // Reset alert
        alertBox.classList.add('hidden');
        alertBox.classList.remove('bg-red-50', 'border-red-200', 'text-red-700', 'bg-green-50', 'border-green-200', 'text-green-700');
        
        if (pwd !== confirm) {
            alertBox.classList.remove('hidden');
            alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
            alertMsg.textContent = 'Las contraseñas no coinciden.';
            return;
        }

        // Loading state
        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Actualizando...`;

        try {
            const formData = new FormData(form);
            const response = await fetch('<?= e(base_url('api/reset_password.php')) ?>', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            alertBox.classList.remove('hidden');
            if (result.success) {
                alertBox.classList.add('bg-green-50', 'border-green-200', 'text-green-700');
                alertMsg.textContent = '¡Tu contraseña ha sido actualizada correctamente!';
                form.classList.add('hidden');
                document.getElementById('success-action').classList.remove('hidden');
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
