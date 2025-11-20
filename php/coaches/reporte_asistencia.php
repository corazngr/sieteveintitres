<?php
session_start();

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'coach') {
    die('Acceso denegado.');
}

require_once("../conexion.php");
require_once("../fpdf/fpdf.php"); 

$id_horario = filter_input(INPUT_GET, 'id_horario', FILTER_VALIDATE_INT);
$id_coach_sesion = $_SESSION['id_usuario'];

if (!$id_horario) {
    die('ID de horario no válido.');
}

// ===================================================================
// 1. CLASE PDF AVANZADA (DE TU PLANTILLA)
// ===================================================================
class PDF extends FPDF {
    public $title = 'Reporte Siete Veintitres';
    public $periodo_reporte = '';

    function Header() {
        $this->Image('../../images/logocolor_723.png', 10, 8, 33);
        
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(34, 49, 63);
        $this->Cell(80); 
        $this->Cell(30, 10, utf8_decode($this->title), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(88, 88, 91);
        $this->Cell(80);
        $this->Cell(30, 7, utf8_decode($this->periodo_reporte), 0, 1, 'C');
        
        $this->Line(10, 35, $this->GetPageWidth() - 10, 35);
        $this->Ln(15);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Generado el ' . date('d/m/Y h:i A'), 0, 0, 'L');
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
}

// ===================================================================
// 2. LÓGICA DEL REPORTE
// ===================================================================

try {
    // 1. Obtener datos de la clase y verificar permiso
    $stmt_clase = $conn->prepare("SELECT hc.nombre_clase_especifico, hc.fecha, hc.hora_inicio, c.nombre_coach 
                                  FROM horario_clases hc 
                                  JOIN coaches c ON hc.id_coach = c.id_coach 
                                  WHERE hc.id_horario = ? AND hc.id_coach = ?");
    $stmt_clase->bind_param("ii", $id_horario, $id_coach_sesion);
    $stmt_clase->execute();
    $clase_details = $stmt_clase->get_result()->fetch_assoc();

    if (!$clase_details) {
        die("Clase no encontrada o no autorizado.");
    }

    // 2. Obtener lista de riders
    // ===================================================================
    // -- CAMBIO 1: Pedimos 'a.numero_bici' en la consulta
    // ===================================================================
    $stmt_riders = $conn->prepare("SELECT r.nombre_rider, a.estatus_asistencia, a.numero_bici 
                                   FROM asistencias a 
                                   JOIN riders r ON a.id_rider = r.id_rider 
                                   WHERE a.id_horario = ?
                                   ORDER BY r.nombre_rider ASC");
    $stmt_riders->bind_param("i", $id_horario);
    $stmt_riders->execute();
    $riders = $stmt_riders->get_result()->fetch_all(MYSQLI_ASSOC);

    // --- Creación del PDF ---
    $pdf = new PDF();

    // 4. Asignar los valores para el Header
    $pdf->title = 'Reporte de Asistencia';
    $fecha_hora = date("d/m/Y", strtotime($clase_details['fecha'])) . ' ' . date("g:i A", strtotime($clase_details['hora_inicio']));
    $pdf->periodo_reporte = 'Clase: ' . $clase_details['nombre_clase_especifico'] . ' | ' . $fecha_hora;

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);

    // 5. Agregar el nombre del Coach
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(34, 49, 63);
    $pdf->Cell(20, 7, 'Coach:', 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(88, 88, 91);
    $pdf->Cell(0, 7, utf8_decode($clase_details['nombre_coach']), 0, 1);
    $pdf->Ln(5); 
    

    // 6. Tabla de asistencia
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(34, 49, 63); 
    $pdf->SetTextColor(255);
    $pdf->SetDrawColor(160, 160, 160);
    $pdf->SetLineWidth(.3);
    
    // ===================================================================
    // -- CAMBIO 2: Ajustamos anchos y añadimos columna "Bici"
    // (130 -> 100), (Nueva 30), (50 se queda) -> Total 180
    // ===================================================================
    $pdf->Cell(100, 10, 'Nombre del Rider', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Bici', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Estatus', 1, 1, 'C', true);

    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0); 
    $fill = false; 

    foreach ($riders as $rider) {
        // --- 1. Color de fila (blanco o gris) ---
        $fillColor = $fill ? [240, 240, 240] : [255, 255, 255];
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        
        // ===================================================================
        // -- CAMBIO 3: Añadimos la celda de la bici
        // ===================================================================

        // --- Celda del Nombre ---
        $pdf->Cell(100, 8, utf8_decode($rider['nombre_rider']), 'LR', 0, 'L', true);

        // --- Celda de la Bici (NUEVA) ---
        // Si numero_bici es NULL o 0, mostramos '-', si no, el número
        $bici_num = (!empty($rider['numero_bici'])) ? $rider['numero_bici'] : '-';
        $pdf->Cell(30, 8, $bici_num, 'LR', 0, 'C', true);

        // --- Celda de Estatus (con colores) ---
        $estatus = ucfirst($rider['estatus_asistencia']);
        
        if ($estatus == 'Presente') {
            $pdf->SetFillColor(210, 255, 210); // Verde
        } elseif ($estatus == 'Ausente') {
            $pdf->SetFillColor(255, 210, 210); // Rojo
        } else {
            // 'Pendiente', usar el color normal de la fila
            $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        }
        
        $pdf->Cell(50, 8, $estatus, 'LR', 1, 'C', true);
        
        $fill = !$fill;
    }
    // Línea inferior de la tabla
    $pdf->Cell(180, 0, '', 'T');


    $stmt_clase->close();
    $stmt_riders->close();
    $conn->close();

    $pdf->Output('D', 'Asistencia_' . $clase_details['nombre_clase_especifico'] . '.pdf');
    
} catch (Exception $e) {
    die('Error al generar el PDF: ' . $e->getMessage());
}
?>