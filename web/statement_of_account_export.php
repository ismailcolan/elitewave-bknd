<?php
require_once('PhpSpreadsheet/vendor/autoload.php');
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/statement_of_account_functions.php');
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

$filters = soa_parse_filters($_GET);
if (!empty($filters['error'])) {
	die($filters['error']);
}

$ledger = soa_fetch($conn, $filters);
if (!empty($ledger['error'])) {
	die($ledger['error']);
}

$party = $ledger['party'];
$summary = $ledger['summary'];
$lines = $ledger['lines'];
$is_vendor = (($ledger['party_type'] ?? 'customer') === 'vendor');
$party_label = $is_vendor ? 'Vendor' : 'Customer';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Statement of Account');
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
$openFill = 'E8EEF6';

$columns = array('Date', 'Particulars', 'Ref. No.', 'Debit', 'Credit', 'Balance');
$colCount = count($columns);
$colLetters = array();
for ($c = 1; $c <= $colCount; $c++) {
	$colLetters[] = Coordinate::stringFromColumnIndex($c);
}
$lastCol = $colLetters[$colCount - 1];
$sheetCanvas = ew_excel_apply_report_sheet_look($spreadsheet, $sheet, $lastCol, max(1, count($lines)));

$reportTitle = 'Statement of Account (' . $party_label . ') : ' . $filters['from_date'] . ' to ' . $filters['to_date'];
$filterSummary = $party_label . ': ' . ($party['name'] ?? '—')
	. ' | Opening: ' . ($summary['opening_balance'] ?? '0.00')
	. ' | Closing: ' . ($summary['closing_balance'] ?? '0.00');

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
	'alignment' => array('wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER),
));

$headerRow = 4;
foreach ($columns as $colIndex => $colName) {
	$sheet->setCellValue($colLetters[$colIndex] . $headerRow, $colName);
}
$sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => $headerText), 'name' => 'Calibri'),
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $headerFill)),
	'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $headerBorderColor))),
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER),
));
$sheet->getRowDimension($headerRow)->setRowHeight(28);

$dataRowStyle = array(
	'font' => array('size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => '1F1F1F')),
	'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
	'alignment' => array('vertical' => Alignment::VERTICAL_CENTER),
);

$rowNum = $headerRow + 1;
$txnIndex = 0;
foreach ($lines as $line) {
	$is_bold = !empty($line['is_opening']) || !empty($line['is_closing']);
	$debit = $line['debit'] === '' ? '' : (float) $line['debit'];
	$credit = $line['credit'] === '' ? '' : (float) $line['credit'];
	$balance = $line['balance'] === '' ? '' : (float) $line['balance'];

	$sheet->setCellValue('A' . $rowNum, $line['display_date']);
	$sheet->setCellValue('B' . $rowNum, $line['particulars']);
	$sheet->setCellValue('C' . $rowNum, $line['ref']);
	$sheet->setCellValue('D' . $rowNum, $debit);
	$sheet->setCellValue('E' . $rowNum, $credit);
	$sheet->setCellValue('F' . $rowNum, $balance);

	$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray($dataRowStyle);
	if ($is_bold) {
		$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->getFont()->setBold(true);
		$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)
			->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($openFill);
	} else {
		$txnIndex++;
		if ($txnIndex % 2 === 0) {
			$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)
				->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($altRowFill);
		}
	}
	$sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
	$sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
	foreach (array('D', 'E', 'F') as $col) {
		$sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
		if ($sheet->getCell($col . $rowNum)->getValue() !== '') {
			$sheet->getStyle($col . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
		}
	}
	$sheet->getRowDimension($rowNum)->setRowHeight(20);
	$rowNum++;
}

/* Period totals */
$sheet->setCellValue('A' . $rowNum, '');
$sheet->setCellValue('B' . $rowNum, 'Period totals');
$sheet->mergeCells('B' . $rowNum . ':C' . $rowNum);
$sheet->setCellValue('D' . $rowNum, (float) $summary['total_debit']);
$sheet->setCellValue('E' . $rowNum, (float) $summary['total_credit']);
$sheet->setCellValue('F' . $rowNum, (float) $summary['closing_balance']);
$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray(array(
	'font' => array('bold' => true, 'size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => $navyText)),
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $totalFill)),
	'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
));
foreach (array('D', 'E', 'F') as $col) {
	$sheet->getStyle($col . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
	$sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}
$sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . $headerRow);
ew_excel_fill_rows_below_report($sheet, $rowNum, $sheetCanvas);

$colWidths = array('A' => 12, 'B' => 48, 'C' => 16, 'D' => 14, 'E' => 14, 'F' => 14);
foreach ($colWidths as $col => $width) {
	$sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

$filename = 'SOA_' . $party_label . '_' . $filters['from_date'] . '_to_' . $filters['to_date'] . '.xlsx';

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
