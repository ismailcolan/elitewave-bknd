<?php
require_once('PhpSpreadsheet/vendor/autoload.php');
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/day_book_functions.php');
require_once('include/ew_excel_report_sheet.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

error_reporting(0);
ini_set('display_errors', 0);
@ini_set('memory_limit', '512M');
@set_time_limit(180);

$filters = day_book_parse_filters($_GET);
if (!empty($filters['error'])) {
	die($filters['error']);
}

$types = day_book_voucher_type_options();
$type_label = isset($types[$filters['voucher_type']]) ? $types[$filters['voucher_type']] : 'All types';
$filterSummary = 'Voucher Type: ' . $type_label;

$result = day_book_fetch_rows($conn, $filters);
$rows = $result['rows'];
$summary = $result['summary'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Day Book');
$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
$spreadsheet->getDefaultStyle()->getFont()->getColor()->setRGB('1F1F1F');

$titleFill = 'D6EAF8';
$headerFill = '9DC3E6';
$headerText = '1F4E79';
$altRowFill = 'DDEBF7';
$borderColor = '9BC2E6';
$headerBorderColor = '1F4E79';
$navyText = '1F4E79';
$totalFill = 'F4F7FB';

$columns = array('S.No', 'Date', 'Voucher Type', 'Voucher No', 'Party', 'Particulars', 'Debit', 'Credit');
$colCount = count($columns);
$colLetters = array();
for ($c = 1; $c <= $colCount; $c++) {
	$colLetters[] = Coordinate::stringFromColumnIndex($c);
}
$lastCol = $colLetters[$colCount - 1];
$sheetCanvas = ew_excel_apply_report_sheet_look($spreadsheet, $sheet, $lastCol, count($rows));

$reportTitle = 'Day Book : ' . $filters['from_date'] . ' to ' . $filters['to_date'];

$sheet->mergeCells('A1:' . $lastCol . '1');
$sheet->setCellValue('A1', 'EliteWave360 Logistics');
$sheet->getRowDimension(1)->setRowHeight(28);

$sheet->mergeCells('A2:' . $lastCol . '2');
$sheet->setCellValue('A2', $reportTitle);
$sheet->getRowDimension(2)->setRowHeight(22);

$sheet->mergeCells('A3:' . $lastCol . '3');
$sheet->setCellValue('A3', $filterSummary);
$sheet->getRowDimension(3)->setRowHeight(24);

$sheet->getStyle('A1:' . $lastCol . '3')->applyFromArray(array(
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $titleFill)),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER),
	'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
));
$sheet->getStyle('A1')->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 16, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
));
$sheet->getStyle('A2')->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 12, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
));
$sheet->getStyle('A3')->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
));

$headerRow = 4;
foreach ($columns as $colIndex => $colName) {
	$sheet->setCellValue($colLetters[$colIndex] . $headerRow, $colName);
}
$sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => $headerText), 'name' => 'Calibri'),
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $headerFill)),
	'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $headerBorderColor))),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true),
));
$sheet->getRowDimension($headerRow)->setRowHeight(28);

$dataRowStyle = array(
	'font' => array('size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => '1F1F1F')),
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => 'FFFFFF')),
	'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
	'alignment' => array('vertical' => Alignment::VERTICAL_CENTER),
);

$rowNum = $headerRow + 1;
if (empty($rows)) {
	$sheet->mergeCells('A' . $rowNum . ':' . $lastCol . $rowNum);
	$sheet->setCellValue('A' . $rowNum, 'No data available');
	$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray(array(
		'font' => array('bold' => true, 'size' => 12, 'name' => 'Calibri', 'color' => array('rgb' => '64748B')),
		'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $totalFill)),
		'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
		'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER),
	));
	$sheet->getRowDimension($rowNum)->setRowHeight(28);
} else {
	foreach ($rows as $r) {
		$debit = ($r['debit'] === '') ? '' : (float) $r['debit'];
		$credit = ($r['credit'] === '') ? '' : (float) $r['credit'];
		$values = array(
			(int) $r['s_no'],
			$r['entry_date'],
			$r['voucher_type'],
			$r['voucher_no'],
			$r['party'],
			$r['particulars'],
			$debit,
			$credit,
		);
		foreach ($values as $valIndex => $val) {
			$sheet->setCellValue($colLetters[$valIndex] . $rowNum, $val);
		}
		$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray($dataRowStyle);
		if (((int) $r['s_no']) % 2 == 0) {
			$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)
				->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($altRowFill);
		}
		$sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		foreach (array(1, 2, 3, 4, 5) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		}
		foreach (array(6, 7) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			if ($sheet->getCell($colLetters[$idx] . $rowNum)->getValue() !== '') {
				$sheet->getStyle($colLetters[$idx] . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
			}
		}
		$sheet->getRowDimension($rowNum)->setRowHeight(20);
		$rowNum++;
	}

	$sheet->setCellValue('A' . $rowNum, '');
	$sheet->setCellValue('B' . $rowNum, 'Total (' . (int) $summary['row_count'] . ')');
	$sheet->mergeCells('B' . $rowNum . ':F' . $rowNum);
	$sheet->setCellValue('G' . $rowNum, (float) $summary['total_debit']);
	$sheet->setCellValue('H' . $rowNum, (float) $summary['total_credit']);
	$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray(array(
		'font' => array('bold' => true, 'size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => $navyText)),
		'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $totalFill)),
		'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
	));
	foreach (array('G', 'H') as $col) {
		$sheet->getStyle($col . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
		$sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
	}
	$sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

$sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . (empty($rows) ? $headerRow : (($rowNum > $headerRow + 1) ? ($rowNum - 1) : $headerRow)));
ew_excel_fill_rows_below_report($sheet, $rowNum, $sheetCanvas);

$colWidths = array(
	'A' => 8, 'B' => 12, 'C' => 16, 'D' => 16, 'E' => 28, 'F' => 36, 'G' => 14, 'H' => 14,
);
foreach ($colWidths as $col => $width) {
	$sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

$filename = 'Day_Book_' . $filters['from_date'] . '_to_' . $filters['to_date'] . '.xlsx';

while (ob_get_level()) {
	ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
