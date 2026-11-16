<?php
require_once('PhpSpreadsheet/vendor/autoload.php');
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/pending_receipt_ageing_functions.php');
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

$filters = pending_receipt_ageing_parse_filters($_GET);
if (!empty($filters['error'])) {
	die($filters['error']);
}

function pra_filter_customer_names($conn, $ids)
{
	if ($ids === null || $ids === '') {
		return 'ALL';
	}
	$names = array();
	foreach (array_filter(array_map('intval', explode(',', $ids))) as $id) {
		$name = get_client_name($conn, $id);
		if ($name !== null && $name !== '') {
			$names[] = $name;
		}
	}
	return empty($names) ? 'ALL' : implode(', ', $names);
}

$bucket_label = $filters['bucket'] !== ''
	? pending_receipt_ageing_bucket_label($filters['bucket'])
	: 'All Pending';

$filterSummary = 'Customer: ' . pra_filter_customer_names($conn, $filters['customers'])
	. ' | Ageing Bucket: ' . $bucket_label
	. ' | Due = Invoice Date + Current Date';

$result = pending_receipt_ageing_fetch_rows($conn, $filters);
$rows = $result['rows'];
$summary = $result['summary'];
$buckets = isset($summary['buckets']) ? $summary['buckets'] : pending_receipt_ageing_empty_bucket_totals();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Pending Receipt Ageing');
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

$columns = array(
	'S.No',
	'Invoice No',
	'Invoice Date',
	'Due Date',
	'Customer',
	'Invoice Value',
	'Payment Received',
	'Credit Note',
	'TDS',
	'Balance',
	'Days Overdue',
	'Ageing Bucket',
);

$colCount = count($columns);
$colLetters = array();
for ($c = 1; $c <= $colCount; $c++) {
	$colLetters[] = Coordinate::stringFromColumnIndex($c);
}
$lastCol = $colLetters[$colCount - 1];
$sheetCanvas = ew_excel_apply_report_sheet_look($spreadsheet, $sheet, $lastCol, count($rows));

$reportTitle = 'Pending Receipt Register / Ageing : ' . $filters['from_date'] . ' to ' . $filters['to_date'];

$sheet->mergeCells('A1:' . $lastCol . '1');
$sheet->setCellValue('A1', 'EliteWave360 Logistics');
$sheet->getRowDimension(1)->setRowHeight(28);

$sheet->mergeCells('A2:' . $lastCol . '2');
$sheet->setCellValue('A2', $reportTitle);
$sheet->getRowDimension(2)->setRowHeight(22);

$sheet->mergeCells('A3:' . $lastCol . '3');
$sheet->setCellValue('A3', $filterSummary);
$sheet->getRowDimension(3)->setRowHeight(24);

$bucketSummaryLine = 'Not Due: ' . $buckets['not_due']
	. ' | 1-30: ' . $buckets['1_30']
	. ' | 31-60: ' . $buckets['31_60']
	. ' | 61-90: ' . $buckets['61_90']
	. ' | 91-120: ' . $buckets['91_120']
	. ' | 120+: ' . $buckets['120_plus']
	. ' | Outstanding: ' . $summary['balance_amount'];

$sheet->mergeCells('A4:' . $lastCol . '4');
$sheet->setCellValue('A4', $bucketSummaryLine);
$sheet->getRowDimension(4)->setRowHeight(22);

$sheet->getStyle('A1:' . $lastCol . '4')->applyFromArray(array(
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $titleFill)),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER),
	'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
));
$sheet->getStyle('A1')->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 16, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER),
));
$sheet->getStyle('A2')->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 12, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER),
));
$sheet->getStyle('A3')->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true),
));
$sheet->getStyle('A4')->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true),
));

$headerRow = 5;
foreach ($columns as $colIndex => $colName) {
	$sheet->setCellValue($colLetters[$colIndex] . $headerRow, $colName);
}
$sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => $headerText), 'name' => 'Calibri'),
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $headerFill)),
	'borders' => array(
		'allBorders' => array(
			'borderStyle' => Border::BORDER_THIN,
			'color' => array('rgb' => $headerBorderColor),
		),
	),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true),
));
$sheet->getRowDimension($headerRow)->setRowHeight(28);

$dataRowStyle = array(
	'font' => array('size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => '1F1F1F')),
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => 'FFFFFF')),
	'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
	'alignment' => array('vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => false),
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
		$values = array(
			(int) $r['s_no'],
			$r['invoice_no'],
			$r['invoice_date'],
			$r['due_date'],
			$r['customer'],
			(float) $r['invoice_value'],
			(float) $r['payment_received'],
			(float) $r['credit_note'],
			(float) $r['tds'],
			(float) $r['balance_amount'],
			(int) $r['days_overdue'],
			$r['ageing_bucket'],
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
		foreach (array(1, 2, 3, 4, 11) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		}
		foreach (array(5, 6, 7, 8, 9, 10) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
		}
		foreach (array(5, 6, 7, 8, 9) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
		}
		$days = (int) $r['days_overdue'];
		if ($days >= 90) {
			$sheet->getStyle($colLetters[10] . $rowNum)->getFont()->getColor()->setRGB('B91C1C');
			$sheet->getStyle($colLetters[10] . $rowNum)->getFont()->setBold(true);
		} elseif ($days >= 30) {
			$sheet->getStyle($colLetters[10] . $rowNum)->getFont()->getColor()->setRGB('B45309');
			$sheet->getStyle($colLetters[10] . $rowNum)->getFont()->setBold(true);
		}
		$sheet->getRowDimension($rowNum)->setRowHeight(20);
		$rowNum++;
	}

	$sheet->setCellValue('A' . $rowNum, '');
	$sheet->setCellValue('B' . $rowNum, 'Total (' . (int) $summary['row_count'] . ')');
	$sheet->mergeCells('B' . $rowNum . ':E' . $rowNum);
	$sheet->setCellValue('F' . $rowNum, (float) $summary['invoice_value']);
	$sheet->setCellValue('G' . $rowNum, (float) $summary['payment_received']);
	$sheet->setCellValue('H' . $rowNum, (float) $summary['credit_note']);
	$sheet->setCellValue('I' . $rowNum, (float) $summary['tds']);
	$sheet->setCellValue('J' . $rowNum, (float) $summary['balance_amount']);
	$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray(array(
		'font' => array('bold' => true, 'size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => $navyText)),
		'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $totalFill)),
		'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
	));
	foreach (array('F', 'G', 'H', 'I', 'J') as $col) {
		$sheet->getStyle($col . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
		$sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
	}
	$sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

$sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . (empty($rows) ? $headerRow : (($rowNum > $headerRow + 1) ? ($rowNum - 1) : $headerRow)));
// No freezePane — Excel draws a gray split line across empty columns to the right
ew_excel_fill_rows_below_report($sheet, $rowNum, $sheetCanvas);

$colWidths = array(
	'A' => 8, 'B' => 16, 'C' => 12, 'D' => 12, 'E' => 32, 'F' => 14, 'G' => 14, 'H' => 12, 'I' => 10, 'J' => 12, 'K' => 12, 'L' => 14,
);
foreach ($colWidths as $col => $width) {
	$sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

$filename = 'Pending_Receipt_Ageing_' . $filters['from_date'] . '_to_' . $filters['to_date'] . '.xlsx';

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
