<?php
require_once('fpdf/fpdf.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    // Decodifica los datos codificados por URL
    $data = urldecode($_POST['data']);
    // Establece el formato de descarga (por defecto 'txt')
    $format = $_POST['format'] ?? 'txt';

    // Establece la zona horaria de Madrid, España
    date_default_timezone_set('Europe/Madrid');
    // Genera un timestamp para el nombre del archivo
    $timestamp = date('Y-m-d_H-i-s');
    // Define el nombre del archivo con base en la fecha y hora
    $filename = "mission_$timestamp";

    if ($format === 'pdf') { // Si el formato es PDF
        $pdf = new FPDF();
        $pdf->AddPage();

        // TÍTULO del PDF
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(255, 60, 0);
        $pdf->Cell(0, 12, 'MARS ROVER MISSION', 0, 1, 'C'); // Centrado

        // SUBTÍTULO con la fecha de generación
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(0);
        $pdf->Cell(0, 10, "Generated on: " . date('F j, Y - H:i:s'), 0, 1, 'C'); // Fecha actual

        $pdf->Ln(5); // Salto de línea

        // CONTENIDO del PDF
        $pdf->SetFont('Courier', '', 11); // Fuente
        $lines = explode("\n", $data); // Divide los datos en líneas
        foreach ($lines as $line) {
            $cleanLine = preg_replace('/[^\x20-\x7E]/u', '', $line); // Elimina caracteres no imprimibles

            // Si la línea es un título (en negrita)
            if (preg_match('/^\*\*(.+?)\*\*$/', $cleanLine, $match)) {
                // Cambia a negrita
                $pdf->SetFont('Courier', 'B', 11);
                $pdf->MultiCell(0, 5, $match[1]); // Añade el título
                $pdf->SetFont('Courier', '', 11); // Restaura la fuente normal
            } else {
                $pdf->MultiCell(0, 5, $cleanLine); // Añade el contenido normal
            }
        }

        // Genera el PDF como un string
        $pdf_content = $pdf->Output('', 'S'); // 'S' para string
        // Define las cabeceras para el archivo PDF
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"$filename.pdf\"");
        header('Content-Length: ' . strlen($pdf_content)); // Longitud del archivo
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdf_content; // Envía el contenido al navegador
        exit;
    }
    // Se podría bajar en txt creando otro if... 
} else {
    // Si no se envían datos, muestra un error
    echo "Error: Invalid request.";
    exit;
}
