<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { die('Acceso denegado.'); }
require_once("../conexion.php");
require_once("pdf_template.php");

// --- 1. OBTENER PARÁMETROS Y DEFINIR FECHAS ---
$reporte_type = $_GET['reporte_type'] ?? '';
$periodo = $_GET['periodo'] ?? 'dia';

$fecha_fin = date('Y-m-d');
$periodo_texto = 'Hoy';
switch ($periodo) {
    case 'semana': $fecha_inicio = date('Y-m-d', strtotime('-6 days')); $periodo_texto = 'Esta Semana'; break;
    case 'mes': $fecha_inicio = date('Y-m-01'); $periodo_texto = 'Este Mes'; break;
    case 'dia': default: $fecha_inicio = date('Y-m-d'); break;
}

// --- 2. PREPARAR DATOS SEGÚN EL TIPO DE REPORTE ---
$titulo_reporte = '';
$headers = [];
$widths = [];
$data = [];
$filename = 'Reporte.pdf';

switch ($reporte_type) {
    case 'membresias':
        $estado = $_GET['estado'] ?? 'Activa';
        $titulo_reporte = "Reporte de Membresias {$estado}s";
        $filename = "Reporte_Membresias_{$estado}s.pdf";
        $headers = ['Rider', 'Membresia', 'Fecha Inicio', 'Fecha Fin'];
        $widths = [70, 50, 35, 35];

        $sql = "SELECT r.nombre_rider, tm.nombre, m.fecha_inicio, m.fecha_fin FROM membresias m
                JOIN riders r ON m.id_rider = r.id_rider
                JOIN tipos_membresia tm ON m.id_tipo_membresia = tm.id_tipo_membresia
                WHERE m.estado = ? AND m.fecha_inicio BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $estado, $fecha_inicio, $fecha_fin);
        break;

    case 'financiero':
        $titulo_reporte = 'Reporte Financiero';
        $filename = 'Reporte_Financiero.pdf';
        $headers = ['Fecha', 'Concepto', 'Tipo', 'Monto'];
        $widths = [45, 85, 25, 35];

        $sql = "(SELECT created_at, concepto, 'Ingreso' as tipo, monto FROM ingresos WHERE fecha BETWEEN ? AND ?)
                UNION ALL
                (SELECT created_at, concepto, 'Gasto' as tipo, monto FROM egresos WHERE fecha BETWEEN ? AND ?)
                ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin);
        break;
}

if (isset($stmt)) {
    $stmt->execute();
    $result = $stmt->get_result();
    // Formateamos los datos para la tabla
    while ($row = $result->fetch_assoc()) {
        if ($reporte_type == 'financiero') {
            $row['created_at'] = date('d/m/Y h:i A', strtotime($row['created_at']));
            $row['monto'] = '$' . number_format($row['monto'], 2);
        }
        $data[] = $row;
    }
}

// --- 3. GENERAR Y ENVIAR EL PDF ---
$pdf = new PDF();
$pdf->title = utf8_decode($titulo_reporte); // Título dinámico para el encabezado
$pdf->periodo_reporte = $periodo_texto;
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

if (count($data) > 0) {
    $pdf->FancyTable($headers, $data, $widths);
} else {
    $pdf->Cell(0, 10, 'No hay datos para mostrar con los filtros seleccionados.', 0, 1);
}

$pdf->Output('D', $filename);
?>