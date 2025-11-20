<?php
require_once('../fpdf/fpdf.php');

class PDF extends FPDF {
    public $periodo_reporte = '';
    public $title = 'Reporte Siete Veintitres'; // Título por defecto

    // Cabecera de página
    function Header() {
        // Logo
        $this->Image('../../images/logocolor_723.png', 10, 8, 33);
        
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(34, 49, 63);
        $this->Cell(0, 10, utf8_decode($this->title), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(88, 88, 91);
        $this->Cell(0, 10, utf8_decode($this->periodo_reporte), 0, 1, 'C');
        
        $this->Line(10, 35, $this->GetPageWidth() - 10, 35);
        $this->Ln(15);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Generado el ' . date('d/m/Y h:i A'), 0, 0, 'L');
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }

    // Tabla con alineaciones 
    function FancyTable($header, $data, $widths, $aligns = []) {
        $this->SetFillColor(34, 49, 63); $this->SetTextColor(255);
        $this->SetDrawColor(160, 160, 160); $this->SetLineWidth(.3);
        $this->SetFont('', 'B');

        for($i = 0; $i < count($header); $i++) {
            $this->Cell($widths[$i], 8, utf8_decode($header[$i]), 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetFillColor(240, 240, 240); $this->SetTextColor(0);
        $this->SetFont('');

        $fill = false;
        foreach($data as $row) {
            $i = 0;
            foreach($row as $col) {
                $align = $aligns[$i] ?? 'L'; 
                $this->Cell($widths[$i], 7, utf8_decode($col), 'LR', 0, $align, $fill);
                $i++;
            }
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($widths), 0, '', 'T');
    }
}
?>