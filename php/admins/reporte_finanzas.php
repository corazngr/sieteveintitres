<?php
session_start();
date_default_timezone_set('America/Mexico_City'); 

// 1. Seguridad y Acceso
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    die('Acceso denegado.');
}

// 2. Dependencias
require_once("../conexion.php");
require_once('../reporte_base.php'); // Requerimos la plantilla de PDF

// 3. Obtener Filtros de la URL (enviados por JS)
$mes = (int)($_GET['month'] ?? date('m'));
$ano = (int)($_GET['year'] ?? date('Y'));
$tipo_filtro = $_GET['type'] ?? 'all'; // 'all', 'income', 'expense'

// 4. Preparar Título del Período
$formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'America/Mexico_City', null, 'MMMM yyyy');
$periodo_reporte = ucfirst($formatter->format(new DateTime("$ano-$mes-01")));

// 5. Preparar Consulta SQL basada en los filtros
$where_mes_ano = "WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?";
$params_sql = [$mes, $ano];
$tipos_sql = 'ii';
$sql_union = [];

$titulo_tipo = "Ingresos y Gastos";

// (Consulta de Ingresos)
if ($tipo_filtro === 'income' || $tipo_filtro === 'all') {
    $sql_union[] = "(SELECT created_at AS fecha_completa, concepto AS descripcion, 'Ingreso' AS tipo, tipo_ingreso AS categoria, monto
                    FROM ingresos $where_mes_ano)";
    if ($tipo_filtro === 'income') $titulo_tipo = "Solo Ingresos";
}

// (Consulta de Egresos/Gastos)
if ($tipo_filtro === 'expense' || $tipo_filtro === 'all') {
    $sql_union[] = "(SELECT created_at AS fecha_completa, concepto AS descripcion, 'Gasto' AS tipo, tipo_egreso AS categoria, monto
                    FROM egresos $where_mes_ano)";
    if ($tipo_filtro === 'expense') $titulo_tipo = "Solo Gastos";
}

$periodo_reporte .= " ($titulo_tipo)"; // ej: "Octubre 2025 (Solo Ingresos)"

// Unimos las consultas SQL
$sql_transacciones = implode(" UNION ALL ", $sql_union) . " ORDER BY fecha_completa ASC";

// 6. Ejecutar Consulta
$stmt_trans = $conn->prepare($sql_transacciones);

// Ajustamos los parámetros a bindiar según el filtro
if ($tipo_filtro === 'all') {
    $stmt_trans->bind_param('iiii', $mes, $ano, $mes, $ano); // 2 de ingresos, 2 de egresos
} else {
    $stmt_trans->bind_param('ii', $mes, $ano); // Solo 2 (para ingresos o egresos)
}

$stmt_trans->execute();
$transacciones = $stmt_trans->get_result()->fetch_all(MYSQLI_ASSOC);

// 7. Procesar Datos para la Tabla y Calcular Totales
$data = [];
$total_ingresos = 0;
$total_gastos = 0;

foreach($transacciones as $tx) {
    $es_ingreso = ($tx['tipo'] === 'Ingreso');
    
    if ($es_ingreso) {
        $total_ingresos += $tx['monto'];
        $monto_formateado = '+$' . number_format($tx['monto'], 2);
    } else {
        $total_gastos += $tx['monto'];
        $monto_formateado = '-$' . number_format($tx['monto'], 2);
    }

    $data[] = [
        'fecha'     => date('d/m/Y h:i A', strtotime($tx['fecha_completa'])),
        'desc'      => $tx['descripcion'],
        'cat'       => $tx['categoria'],
        'monto_str' => $monto_formateado,
        'tipo'      => $tx['tipo'] // <-- Guardamos el tipo ('Ingreso' o 'Gasto')
    ];
}
$saldo_neto = $total_ingresos - $total_gastos;
$conn->close();

// 8. Generar el PDF
$pdf = new PDF();
$pdf->AliasNbPages();

// Seteamos los títulos
$pdf->title = 'Reporte Financiero SIE7E 23';
$pdf->periodo_reporte = $periodo_reporte; 

$pdf->AddPage('L'); // Página Horizontal

// Configuración de la tabla
// --- ⬇️ CAMBIO 1: Decodificar encabezados ⬇️ ---
$header = ['Fecha y Hora', utf8_decode('Descripción'), utf8_decode('Categoría'), 'Monto'];
$widths = [45, 157, 40, 35]; // Anchos de columnas
$aligns = ['C', 'L', 'L', 'R']; // Alineaciones


// --- REEMPLAZO DE FANCYTABLE ---

// 1. DIBUJAR EL ENCABEZADO MANUALMENTE
$pdf->SetFont('', 'B');
$pdf->SetFillColor(220, 220, 220); // Gris claro
$pdf->SetTextColor(0); // Texto negro
for($i = 0; $i < count($header); $i++) {
    // Ya no se necesita decode aquí porque lo hicimos en el array $header
    $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// 2. DIBUJAR LAS FILAS DE DATOS CON COLORES
$pdf->SetFont('', ''); // Fuente normal para los datos
foreach($data as $row) {
    
    // Columna 1: Fecha (Negro)
    $pdf->SetTextColor(0);
    $pdf->Cell($widths[0], 7, $row['fecha'], 1, 0, $aligns[0]);
    
    // --- ⬇️ CAMBIO 2: Decodificar descripción ⬇️ ---
    $pdf->Cell($widths[1], 7, utf8_decode($row['desc']), 1, 0, $aligns[1]);
    
    // --- ⬇️ CAMBIO 3: Decodificar categoría ⬇️ ---
    $pdf->Cell($widths[2], 7, utf8_decode($row['cat']), 1, 0, $aligns[2]);
    
    // Columna 4: Monto (¡AQUÍ VA LA LÓGICA DE COLOR!)
    if ($row['tipo'] === 'Ingreso') {
        $pdf->SetTextColor(20, 100, 20); // Verde
    } else {
        $pdf->SetTextColor(180, 20, 20); // Rojo
    }
    $pdf->Cell($widths[3], 7, $row['monto_str'], 1, 0, $aligns[3]);
    
    // Fin de la fila
    $pdf->Ln();
}

// Resetear el color de texto a negro para los totales
$pdf->SetTextColor(0);

// 9. Mostrar Filas de Totales
$pdf->Ln(5);
$pdf->SetFont('', 'B');
$pdf->SetFillColor(220, 220, 220);
$total_label_width = $widths[0] + $widths[1] + $widths[2]; // Ancho de las 3 primeras columnas

// Fila Total Ingresos (Solo si aplica)
if ($tipo_filtro === 'income' || $tipo_filtro === 'all') {
    $pdf->Cell($total_label_width, 8, 'TOTAL INGRESOS', 1, 0, 'R', true);
    $pdf->SetTextColor(20, 100, 20); // Verde
    $pdf->Cell($widths[3], 8, '$' . number_format($total_ingresos, 2), 1, 1, 'R', true);
    $pdf->SetTextColor(0); // Reset color
    $pdf->SetFont('', 'B');
}

// Fila Total Gastos (Solo si aplica)
if ($tipo_filtro === 'expense' || $tipo_filtro === 'all') {
    $pdf->Cell($total_label_width, 8, 'TOTAL GASTOS', 1, 0, 'R', true);
    $pdf->SetTextColor(180, 20, 20); // Rojo
    $pdf->Cell($widths[3], 8, '-$' . number_format($total_gastos, 2), 1, 1, 'R', true);
    $pdf->SetTextColor(0); // Reset color
    $pdf->SetFont('', 'B');
}

// Fila Saldo Neto (Solo si el filtro es 'all')
if ($tipo_filtro === 'all') {
    $pdf->SetFillColor(50, 50, 50);
    $pdf->SetTextColor(255);
    $pdf->Cell($total_label_width, 8, 'SALDO NETO (Ingresos - Gastos)', 1, 0, 'R', true);
    $pdf->Cell($widths[3], 8, '$' . number_format($saldo_neto, 2), 1, 1, 'R', true);
}

// 10. Enviar PDF al Navegador
$pdf->Output('D', 'Reporte_Finanzas_' . $ano . '-' . $mes . '.pdf');
?>