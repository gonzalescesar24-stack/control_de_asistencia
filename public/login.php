<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/session.php';
start_app_session();
require_once __DIR__ . '/../app/helpers.php';

if (app_user()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (login_user($usuario, $password)) {
        header('Location: ' . base_url('index.php'));
        exit;
    }

    $error = 'Usuario o contraseña incorrectos. Verifique sus credenciales.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar - <?= e(APP_NAME) ?></title>
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

        <form method="post" class="rounded-2xl bg-white p-8 shadow-2xl">
            <h2 class="mb-6 text-xl font-semibold text-[#1a3a6b]">Iniciar sesión</h2>

            <?php if ($error): ?>
                <div class="mb-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <span aria-hidden="true">!</span>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <label class="mb-4 block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Usuario o correo</span>
                <input name="usuario" class="form-control" placeholder="Ej: admin" required autocomplete="username">
            </label>

            <label class="mb-2 block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Contraseña</span>
                <input name="password" type="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            </label>



            <button class="w-full rounded-lg bg-[#1a3a6b] px-4 py-3 font-semibold text-white transition-colors hover:bg-[#234a85]">Iniciar sesión</button>
        </form>
    </div>
</main>
</body>
</html>
