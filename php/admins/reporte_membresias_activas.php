<?php
session_start();
date_default_timezone_set('America/Mexico_City');

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    die('Acceso denegado.');
}

require_once("../conexion.php");
require_once('../reporte_base.php'); 

// 1. OBTENER DATOS
$sql = "
    SELECT
        r.nombre_rider,
        tm.nombre AS nombre_tipo_membresia,
        m.fecha_inicio,
        m.fecha_fin,
        
        -- Cálculo de Días Restantes
        DATEDIFF(m.fecha_fin, CURDATE()) AS dias_restantes,
        
        -- Cálculo de Clases Restantes
        CASE
            WHEN tm.limite_clases IS NULL THEN 'Ilimitadas'
            ELSE (tm.limite_clases - COUNT(DISTINCT res.id_reservacion))
        END AS clases_restantes_calculadas,
        
        -- Cálculo de Estado
        CASE
            WHEN m.fecha_fin < CURDATE() THEN 'Vencida'
            WHEN DATEDIFF(m.fecha_fin, CURDATE()) <= 3 THEN 'Por Vencer'
            ELSE 'Activa'
        END AS estado
    FROM
        membresias AS m
    JOIN
        riders AS r ON m.id_rider = r.id_rider
    JOIN
        tipos_membresia AS tm ON m.id_tipo_membresia = tm.id_tipo_membresia
    LEFT JOIN
        reservaciones AS res ON m.id_rider = res.id_rider
    LEFT JOIN
        horario_clases AS h ON res.id_horario = h.id_horario
        AND h.fecha BETWEEN m.fecha_inicio AND m.fecha_fin
        AND h.fecha < CURDATE() 
    WHERE
        m.fecha_fin >= CURDATE() -- Usamos la misma lógica que el dashboard
    GROUP BY
        m.id_membresia, r.nombre_rider, tm.nombre, tm.limite_clases, m.fecha_inicio, m.fecha_fin
    ORDER BY 
        r.nombre_rider ASC -- Mantenemos tu orden
";
$result = $conn->query($sql);
$membresias = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

// 2. PREPARAR DATOS PARA LA TABLA
$data = [];
foreach($membresias as $mem) {
    $data[] = [
        $mem['nombre_rider'],
        $mem['nombre_tipo_membresia'],
        date('d/m/y', strtotime($mem['fecha_inicio'])),
        date('d/m/y', strtotime($mem['fecha_fin'])),
        $mem['clases_restantes_calculadas'], // <-- Dato calculado
        $mem['dias_restantes'],              // <-- Dato nuevo
        $mem['estado']                       // <-- Dato calculado
    ];
}

// 3. GENERAR EL PDF
$pdf = new PDF();
$pdf->AliasNbPages();

// Seteamos las variables públicas
$pdf->title = 'Reporte de Membresías Activas 723';
$pdf->periodo_reporte = 'Miembros con membresía "Activa" o "Por Vencer" (Datos al ' . date('d/m/Y') . ')';

$pdf->AddPage();

// Títulos de la tabla (Ajustados)
$header = ['Rider', 'Tipo', 'Inicio', 'Fin', 'Clases', 'Días', 'Estado'];
// Anchos de la tabla (Ajustados para que quepa la nueva columna)
$widths = [50, 40, 25, 25, 20, 15, 15];
// Alineaciones (Ajustadas)
$aligns = ['L', 'L', 'C', 'C', 'C', 'C', 'C'];

$pdf->FancyTable($header, $data, $widths, $aligns);

// Fila del Total General (Ajustada)
$pdf->Ln(2);
$pdf->SetFont('', 'B');
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(array_sum($widths) - $widths[6], 8, 'TOTAL DE MIEMBROS ACTIVOS', 1, 0, 'R', true); // Usa índice 6
$pdf->Cell($widths[6], 8, count($data), 1, 1, 'C', true); // Usa índice 6

// 4. ENVIAR PDF
$pdf->Output('D', 'Reporte_Membresias_' . date('Y-m-d') . '.pdf');
?>