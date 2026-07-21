<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/session.php';
start_app_session();
session_destroy();
header('Location: login.php');
exit;
