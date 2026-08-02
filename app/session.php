<?php
declare(strict_types=1);

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }



    // Set secure session parameters before starting
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');

    session_start();

    // 1. Session Timeout (20 minutes)
    $timeout_duration = 1200; // 20 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        session_start(); // Start a new session after destroying the old one
    }
    $_SESSION['last_activity'] = time();

    // 2. Session Regeneration (every 10 minutes)
    $regeneration_time = 600;
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif ((time() - $_SESSION['last_regeneration']) > $regeneration_time) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    // 3. CSRF Token Generation
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
