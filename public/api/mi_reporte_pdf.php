<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/session.php';
start_app_session();
require_once __DIR__ . '/../../app/helpers.php';

// Cargar FPDF
if (!file_exists(__DIR__ . '/../../vendor/autoload.php') && !class_exists('FPDF')) {
    if (file_exists(__DIR__ . '/../../fpdf/fpdf.php')) {
        require_once __DIR__ . '/../../fpdf/fpdf.php';
    } else {
        die("Error: Libreria FPDF no encontrada.");
    }
} else if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

require_login();

$user = app_user();
if ($user['rol'] !== 'estudiante') {
    die("Acceso denegado. Solo estudiantes.");
}

$pdo = db();
$stmtEst = $pdo->prepare('SELECT * FROM estudiantes WHERE nombres = ?');
$stmtEst->execute([$user['nombre']]);
$est = $stmtEst->fetch();

if (!$est) {
    die("Estudiante no encontrado.");
}

$stmtAsist = $pdo->prepare('
    SELECT 
        p.nombre as programa, ud.nombre as unidad, s.fecha, s.hora, d.nombres as docente, a.estado, a.observacion as obs
    FROM asistencias a
    JOIN sesiones s ON a.sesion_id = s.id
    LEFT JOIN programas p ON s.programa_id = p.id
    LEFT JOIN unidades_didacticas ud ON s.unidad_didactica_id = ud.id
    LEFT JOIN docentes d ON s.docente_id = d.id
    WHERE a.estudiante_id = ?
    ORDER BY s.fecha DESC, s.hora DESC
');
$stmtAsist->execute([$est['id']]);
$sesiones = $stmtAsist->fetchAll();

$total = count($sesiones);
$inasistencias = count(array_filter($sesiones, fn($s) => $s['estado'] === 'Inasistente'));
$porcentaje = $total > 0 ? ($inasistencias / $total) * 100 : 0;

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
        $this->Cell(0, 6, decode_text('Reporte Individual de Asistencia'), 0, 1, 'L');
        
        // Date
        $this->SetXY(10, 20);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
        
        $this->Ln(12);
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

// Info del estudiante
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(25, 6, 'Estudiante:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 6, decode_text($est['nombres']), 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, '% Inasistencia:', 0, 0);
$pdf->SetFont('Arial', 'B', 11);
if ($porcentaje >= 30) {
    $pdf->SetTextColor(220, 38, 38); // Rojo
} else if ($porcentaje >= 20) {
    $pdf->SetTextColor(217, 119, 6); // Ambar
} else {
    $pdf->SetTextColor(22, 163, 74); // Verde
}
$pdf->Cell(35, 6, number_format($porcentaje, 2) . '%', 0, 1);

$pdf->Ln(6);

// Table Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(26, 58, 107);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(26, 58, 107);

$pdf->Cell(28, 10, 'Fecha', 'B', 0, 'C', true);
$pdf->Cell(55, 10, decode_text('Unidad Didáctica'), 'B', 0, 'C', true);
$pdf->Cell(42, 10, 'Docente', 'B', 0, 'C', true);
$pdf->Cell(22, 10, 'Estado', 'B', 0, 'C', true);
$pdf->Cell(43, 10, decode_text('Observación'), 'B', 1, 'C', true);

// Table Body
$pdf->SetFont('Arial', '', 8);
$pdf->SetDrawColor(220, 220, 220); // Gris claro para las lineas separadoras
$fill = false; // Zebra striping flag

foreach ($sesiones as $s) {
    // Zebra striping colors
    if ($fill) {
        $pdf->SetFillColor(245, 248, 250);
    } else {
        $pdf->SetFillColor(255, 255, 255);
    }
    $pdf->SetTextColor(80, 80, 80);
    
    $fecha_fmt = date('d/m/Y', strtotime($s['fecha']));
    $fecha = $fecha_fmt . ' ' . substr($s['hora'], 0, 5);
    // Truncate long texts
    $unidad = substr(decode_text($s['unidad'] ?? ''), 0, 32);
    $docente = substr(decode_text($s['docente'] ?? ''), 0, 22);
    $obs = substr(decode_text($s['obs'] ?? ''), 0, 28);
    
    $pdf->Cell(28, 8, $fecha, 'B', 0, 'C', true);
    $pdf->Cell(55, 8, $unidad, 'B', 0, 'L', true);
    $pdf->Cell(42, 8, $docente, 'B', 0, 'L', true);
    
    // Color for Estado
    if ($s['estado'] === 'Presente') {
        $pdf->SetTextColor(22, 163, 74);
    } else if ($s['estado'] === 'Inasistente') {
        $pdf->SetTextColor(220, 38, 38);
    } else {
        $pdf->SetTextColor(217, 119, 6);
    }
    
    $pdf->Cell(22, 8, decode_text($s['estado']), 'B', 0, 'C', true);
    
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(43, 8, $obs ? $obs : '-', 'B', 1, 'C', true);
    
    $fill = !$fill;
}

if (empty($sesiones)) {
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 15, 'No hay registros de asistencia.', 'B', 1, 'C');
}

$pdf->Output('I', 'Reporte_Asistencia_' . date('Ymd_His') . '.pdf');
