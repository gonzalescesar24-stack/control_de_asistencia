<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

// Si no estǭ instalado FPDF por composer o carpeta, mostrar error
if (!file_exists(__DIR__ . '/../../vendor/autoload.php') && !class_exists('FPDF')) {
    // Intentar autocargar fpdf si estǭ en el root
    if (file_exists(__DIR__ . '/../../fpdf/fpdf.php')) {
        require_once __DIR__ . '/../../fpdf/fpdf.php';
    } else {
        die("Error: Librera FPDF no encontrada.");
    }
} else if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

require_login();

$user = app_user();
if (!in_array($user['rol'], ['admin', 'docente'])) {
    die("Acceso denegado.");
}

$estudiantes = estudiantes_filtrados2($_GET);

function decode_text($str) {
    return mb_convert_encoding((string)$str, 'ISO-8859-1', 'UTF-8');
}

class PDF extends FPDF {
    function Header() {
        // Logo
        $logo_path = __DIR__ . '/../assets/images/logo_vrht.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 8, 22);
        }
        
        // Arial bold 14
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(26, 58, 107); // Color institucional principal
        $this->SetXY(35, 12);
        $this->Cell(0, 8, decode_text('IES VÍCTOR RAÚL HAYA DE LA TORRE'), 0, 1, 'L');
        
        // Subtitle
        $this->SetXY(35, 20);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, decode_text('Reporte Oficial de Asistencia de Estudiantes'), 0, 1, 'L');
        
        // Date
        $this->SetXY(10, 20);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
        
        $this->Ln(12);
        
        // Table Header
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(26, 58, 107);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(26, 58, 107); // Borde del mismo color para que sea limpio
        
        // Solo bordes inferiores ('B') en vez de cuadros completos (1)
        $this->Cell(25, 10, 'Codigo', 'B', 0, 'C', true);
        $this->Cell(75, 10, 'Nombres Completos', 'B', 0, 'C', true);
        $this->Cell(60, 10, 'Programa de Estudio', 'B', 0, 'C', true);
        $this->Cell(30, 10, '% Inasistencia', 'B', 1, 'C', true);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Arial', '', 8);
$pdf->SetDrawColor(220, 220, 220); // Gris claro para las lineas separadoras
$fill = false; // Zebra striping flag

foreach ($estudiantes as $e) {
    $porcentaje = 0;
    $sesiones = isset($e['sesiones']) ? (int) $e['sesiones'] : (int) ($e['total_sesiones'] ?? 0);
    if ($sesiones > 0) {
        $porcentaje = ($e['inasistencias'] / $sesiones) * 100;
    }
    
    // Zebra striping colors
    if ($fill) {
        $pdf->SetFillColor(245, 248, 250); // Celeste muuuy claro
    } else {
        $pdf->SetFillColor(255, 255, 255); // Blanco
    }
    
    $pdf->SetTextColor(50, 50, 50); // Gris oscuro para los textos
    
    // Columna: Codigo
    $pdf->Cell(25, 9, decode_text($e['codigo']), 'B', 0, 'C', true);
    
    // Columna: Nombres
    $nombres = mb_strlen($e['nombres'], 'UTF-8') > 45 ? mb_substr($e['nombres'], 0, 42, 'UTF-8') . '...' : $e['nombres'];
    $pdf->Cell(75, 9, '  ' . decode_text($nombres), 'B', 0, 'L', true); // '  ' para padding
    
    // Columna: Programa
    $programa = $e['programa'] ? $e['programa'] : 'N/A';
    if (mb_strlen($programa, 'UTF-8') > 35) {
        $programa = mb_substr($programa, 0, 32, 'UTF-8') . '...';
    }
    $pdf->Cell(60, 9, '  ' . decode_text($programa), 'B', 0, 'L', true);
    
    // Columna: Porcentaje (Rojo y Bold si pasa el 30%)
    if ($porcentaje >= 30) {
        $pdf->SetTextColor(220, 38, 38);
        $pdf->SetFont('Arial', 'B', 8);
    } else {
        $pdf->SetFont('Arial', '', 8);
    }
    $pdf->Cell(30, 9, number_format($porcentaje, 1) . '%', 'B', 1, 'C', true);
    
    $fill = !$fill;
}

$pdf->Output('I', 'Reporte_Asistencias.pdf');
