<?php
require 'vendor/autoload.php';
require_once 'config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="cursos.csv"');

try {
    // Fetch all courses from the database
    $sql = "SELECT * FROM cursos ORDER BY anio, trimestre, course";
    $stmt = $pdo->query($sql);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set the headers
    $headers = array_keys($cursos[0]);
    $column = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($column . '1', $header);
        $column++;
    }

    // Add the data
    $row = 2;
    foreach ($cursos as $curso) {
        $column = 'A';
        foreach ($curso as $value) {
            $sheet->setCellValue($column . $row, $value);
            $column++;
        }
        $row++;
    }

    // Create CSV writer and save to php://output
    $writer = new Csv($spreadsheet);
    $writer->setDelimiter(',');
    $writer->setEnclosure('"');
    $writer->setLineEnding("\r\n");
    $writer->setSheetIndex(0);
    $writer->save('php://output');

} catch (PDOException $e) {
    error_log("Error en download_csv.php: " . $e->getMessage());
    echo "Error al generar el archivo CSV";
}