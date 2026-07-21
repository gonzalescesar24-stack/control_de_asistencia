<?php
declare(strict_types=1);

const APP_NAME = 'Control de Asistencia';
const INSTITUTION_NAME = 'IES "VÍCTOR RAÚL HAYA DE LA TORRE"';

// Configuración de Base de Datos
// Para InfinityFree, cambia estos valores por los que te da tu panel de control (MySQL Details)
const DB_HOST = '127.0.0.1'; // Ej: sql100.infinityfree.com
const DB_NAME = 'control_asistencia'; // Ej: if0_34567890_control
const DB_USER = 'root'; // Ej: if0_34567890
const DB_PASS = ''; // Tu contraseña de InfinityFree (vPanel Password)
const DB_CHARSET = 'utf8mb4';

function base_url(string $path = ''): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

