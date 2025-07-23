<?php
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="contact_template.xlsx"');

// Create a simple Excel file with PHPExcel or PhpSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'First Name*');
$sheet->setCellValue('B1', 'Last Name');
$sheet->setCellValue('C1', 'Phone* (10 digits)');
$sheet->setCellValue('D1', 'Email');
$sheet->setCellValue('E1', 'Company');
$sheet->setCellValue('F1', 'Address');
$sheet->setCellValue('G1', 'Notes');

// Set some example data
$sheet->setCellValue('A2', 'John');
$sheet->setCellValue('B2', 'Doe');
$sheet->setCellValue('C2', '1234567890');
$sheet->setCellValue('D2', 'john@example.com');
$sheet->setCellValue('E2', 'ABC Corp');
$sheet->setCellValue('F2', '123 Main St');
$sheet->setCellValue('G2', 'Met at conference');

// Auto-size columns
foreach(range('A','G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;