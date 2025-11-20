<?php
session_start();
date_default_timezone_set('America/Mexico_City'); // <-- ¡IMPORTANTE!

$locale = 'es_ES'; // Usamos el 'locale' de español
$timezone = 'America/Mexico_City';

$hoy = new DateTime('now', new DateTimeZone($timezone));

$dateFormatter = new IntlDateFormatter(
    $locale,
    IntlDateFormatter::LONG,
    IntlDateFormatter::NONE, 
    $timezone
);

$monthFormatter = new IntlDateFormatter(
    $locale,
    IntlDateFormatter::NONE,
    IntlDateFormatter::NONE,
    $timezone,
    null, 
    'MMMM yyyy' 
);

$weekDayFormatter = new IntlDateFormatter(
    $locale,
    IntlDateFormatter::NONE,
    IntlDateFormatter::NONE,
    $timezone,
    null,
    "d 'de' MMMM" 
);


if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    die('Acceso denegado.');
}

require_once("../conexion.php");
require_once('../reporte_base.php');

// 1. OBTENER DATOS
$filtro = $_GET['filtro'] ?? 'day';
$sql = "SELECT producto, cantidad, total, vendedor, created_at FROM cafeteria WHERE";

$periodo_reporte = ''; 

switch ($filtro) {
    case 'week':
        $dias_a_restar = $hoy->format('w') - 1;

        $inicio_semana_dt = (clone $hoy)->modify("-$dias_a_restar days");
        $fin_semana_dt = (clone $inicio_semana_dt)->modify('+6 days');

        $inicio_str = $weekDayFormatter->format($inicio_semana_dt);
        $fin_str = $weekDayFormatter->format($fin_semana_dt);
        
        $sql .= " DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 2) DAY)";
        $periodo_reporte = "Semana del $inicio_str al $fin_str";
        break;

    case 'month':
        $sql .= " YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        $periodo_reporte = ucfirst($monthFormatter->format($hoy));
        break;

    case 'day':
    default:
        $sql .= " DATE(created_at) = CURDATE()";
        $periodo_reporte = ucfirst($dateFormatter->format($hoy));
        break;
}

$sql .= " ORDER BY created_at ASC";
$result = $conn->query($sql);
$ventas = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

$data = [];
$total_general = 0;
foreach($ventas as $venta) {
    $data[] = [
        date('h:i A', strtotime($venta['created_at'])), // Hora
        $venta['cantidad'] . ' x ' . $venta['producto'], // Producto
        '$' . number_format($venta['total'], 2),       // Total
        $venta['vendedor']                             // Vendedor
    ];
    $total_general += $venta['total'];
}

// 3. GENERAR EL PDF
$pdf = new PDF();
$pdf->AliasNbPages();

$pdf->title = 'Reporte de Ventas de Cafetería 723';
$pdf->periodo_reporte = $periodo_reporte; // <-- AHORA USA LA VARIABLE DINÁMICA

$pdf->AddPage();

// Títulos de la tabla
$header = ['Hora', 'Producto', 'Total', 'Vendedor'];
// Anchos de la tabla
$widths = [30, 80, 40, 40];
// Alineaciones de la tabla (Centrado, Izquierda, Derecha, Izquierda)
$aligns = ['C', 'L', 'R', 'L'];

$pdf->FancyTable($header, $data, $widths, $aligns);

// Fila del Total General
$pdf->Ln(2); // Un pequeño espacio
$pdf->SetFont('', 'B');
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($widths[0] + $widths[1], 8, 'TOTAL GENERAL', 1, 0, 'R', true);
$pdf->Cell($widths[2], 8, '$' . number_format($total_general, 2), 1, 0, 'R', true);
$pdf->Cell($widths[3], 8, '', 1, 1, 'C', true);

// 4. ENVIAR PDF
$pdf->Output('D', 'Reporte_Cafeteria_' . date('Y-m-d') . '.pdf');
?>