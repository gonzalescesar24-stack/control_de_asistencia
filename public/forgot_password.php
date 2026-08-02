<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/session.php';
start_app_session();
require_once __DIR__ . '/../app/helpers.php';

if (app_user()) {
    header('Location: ' . base_url('index.php'));
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar Contraseña - <?= e(APP_NAME) ?></title>
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
            <p class="mt-1 text-sm text-blue-200">Sistema Web de Control de Asistencia</p>
        </header>

        <div class="rounded-2xl bg-white p-8 shadow-2xl">
            <h2 class="mb-2 text-xl font-semibold text-[#1a3a6b]">Recuperar contraseña</h2>
            <p class="mb-6 text-sm text-gray-600">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

            <div id="alert-container" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm">
                <span id="alert-message"></span>
            </div>

            <form id="forgot-password-form">
                <label class="mb-6 block">
                    <span class="mb-1 block text-sm font-medium text-gray-700">Correo electrónico</span>
                    <input name="correo" type="email" class="form-control" placeholder="ejemplo@institucion.edu.pe" required autocomplete="email">
                </label>

                <button type="submit" id="submit-btn" class="mb-4 w-full rounded-lg bg-[#1a3a6b] px-4 py-3 font-semibold text-white transition-colors hover:bg-[#234a85] flex justify-center items-center gap-2">
                    Enviar enlace de recuperación
                </button>
                
                <div class="text-center">
                    <a href="<?= e(base_url('login.php')) ?>" class="text-sm font-medium text-gray-600 hover:text-[#1a3a6b] hover:underline">
                        Volver al inicio de sesión
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.getElementById('forgot-password-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('submit-btn');
    const alertBox = document.getElementById('alert-container');
    const alertMsg = document.getElementById('alert-message');
    
    // Reset alert
    alertBox.classList.add('hidden');
    alertBox.classList.remove('bg-red-50', 'border-red-200', 'text-red-700', 'bg-green-50', 'border-green-200', 'text-green-700');
    
    // Loading state
    const originalBtnText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Procesando...`;

    try {
        const formData = new FormData(form);
        const response = await fetch('<?= e(base_url('api/forgot_password.php')) ?>', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        alertBox.classList.remove('hidden');
        if (result.success) {
            alertBox.classList.add('bg-green-50', 'border-green-200', 'text-green-700');
            alertMsg.textContent = 'Si el correo existe en nuestro sistema, hemos enviado un enlace de recuperación. Revisa tu bandeja de entrada o spam.';
            form.reset();
        } else {
            alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
            alertMsg.textContent = result.error || 'Ocurrió un error. Inténtalo de nuevo.';
        }
    } catch (err) {
        alertBox.classList.remove('hidden');
        alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
        alertMsg.textContent = 'Error de conexión. Inténtalo de nuevo.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalBtnText;
    }
});
</script>
</body>
</html>
