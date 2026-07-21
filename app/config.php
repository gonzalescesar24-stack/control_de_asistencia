<?php
declare(strict_types=1);

const APP_NAME = 'Control de Asistencia';
const INSTITUTION_NAME = 'IES "VÍCTOR RAÚL HAYA DE LA TORRE"';

// Configuración de Base de Datos
// Para InfinityFree, cambia estos valores por los que te da tu panel de control (MySQL Details)
const DB_HOST = 'sql207.infinityfree.com';
const DB_NAME = 'if0_42464602_control_asistencia';
const DB_USER = 'if0_42464602';
const DB_PASS = 'FLDNal9ApnyZ';
const DB_CHARSET = 'utf8mb4';

function base_url(string $path = ''): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

