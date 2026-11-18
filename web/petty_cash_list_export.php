<?php
require_once('PhpSpreadsheet/vendor/autoload.php');
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/petty_cash_helpers.php');
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

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
	die('Access denied.');
}

petty_cash_ensure_schema($conn);
$rows = petty_cash_fetch_list($conn, true);
$summary = petty_cash_summary($conn);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Petty Cash');
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
	'S.No', 'Voucher No', 'Date', 'Type', 'Particulars', 'Vendor', 'Paid To',
	'Amount', 'Balance', 'Ref No', 'Remarks',
);

$colCount = count($columns);
$colLetters = array();
for ($c = 1; $c <= $colCount; $c++) {
	$colLetters[] = Coordinate::stringFromColumnIndex($c);
}
$lastCol = $colLetters[$colCount - 1];
$sheetCanvas = ew_excel_apply_report_sheet_look($spreadsheet, $sheet, $lastCol, count($rows));

$sheet->mergeCells('A1:' . $lastCol . '1');
$sheet->setCellValue('A1', 'EliteWave360 Logistics');
$sheet->getRowDimension(1)->setRowHeight(28);

$sheet->mergeCells('A2:' . $lastCol . '2');
$sheet->setCellValue('A2', 'Petty Cash Vouchers');
$sheet->getRowDimension(2)->setRowHeight(22);

$sheet->mergeCells('A3:' . $lastCol . '3');
$sheet->setCellValue(
	'A3',
	'Receipts: ' . $summary['total_receipt'] .
	' | Payments: ' . $summary['total_payment'] .
	' | Balance: ' . $summary['balance'] .
	' | Exported: ' . date('d-m-Y H:i')
);
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
	'alignment' => array('horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true),
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
	$sno = 0;
	foreach ($rows as $r) {
		$sno++;
		$type = ($r['entry_type'] ?? '') === 'receipt' ? 'Received' : 'Sent';
		$amt_raw = round((float) ($r['amount_raw'] ?? 0), 2);
		$signed_amt = (($r['entry_type'] ?? '') === 'receipt') ? $amt_raw : (0 - $amt_raw);
		$values = array(
			$sno,
			$r['voucher_no'] ?? '',
			$r['voucher_date'] ?? '',
			$type,
			$r['particulars'] ?? '',
			!empty($r['vendor_label']) ? $r['vendor_label'] : '—',
			!empty($r['paid_to']) ? $r['paid_to'] : '—',
			$signed_amt,
			(float) ($r['balance_raw'] ?? 0),
			!empty($r['ref_no']) ? $r['ref_no'] : '—',
			!empty($r['remarks']) ? $r['remarks'] : '—',
		);
		foreach ($values as $valIndex => $val) {
			$sheet->setCellValue($colLetters[$valIndex] . $rowNum, $val);
		}
		$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray($dataRowStyle);
		if ($sno % 2 == 0) {
			$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)
				->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($altRowFill);
		}
		$sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		foreach (array(1, 2, 3, 4, 5, 6, 9, 10) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		}
		foreach (array(7, 8) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getNumberFormat()->setFormatCode('+#,##0.00;-#,##0.00');
		}
		if ($signed_amt >= 0) {
			$sheet->getStyle('H' . $rowNum)->getFont()->getColor()->setRGB('15803D');
		} else {
			$sheet->getStyle('H' . $rowNum)->getFont()->getColor()->setRGB('DC2626');
		}
		$sheet->getRowDimension($rowNum)->setRowHeight(20);
		$rowNum++;
	}

	$net_flow = round((float) $summary['balance_raw'], 2);
	$sheet->setCellValue('A' . $rowNum, '');
	$sheet->setCellValue('B' . $rowNum, 'Closing Balance (' . count($rows) . ')');
	$sheet->mergeCells('B' . $rowNum . ':G' . $rowNum);
	$sheet->setCellValue('H' . $rowNum, $net_flow);
	$sheet->setCellValue('I' . $rowNum, (float) $summary['balance']);
	$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray(array(
		'font' => array('bold' => true, 'size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => $navyText)),
		'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $totalFill)),
		'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
	));
	foreach (array('H', 'I') as $col) {
		$sheet->getStyle($col . $rowNum)->getNumberFormat()->setFormatCode('+#,##0.00;-#,##0.00');
		$sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
	}
	$sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

$sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . (empty($rows) ? $headerRow : (($rowNum > $headerRow + 1) ? ($rowNum - 1) : $headerRow)));
ew_excel_fill_rows_below_report($sheet, $rowNum, $sheetCanvas);

$colWidths = array(
	'A' => 8, 'B' => 14, 'C' => 12, 'D' => 12, 'E' => 28, 'F' => 22, 'G' => 16,
	'H' => 14, 'I' => 12, 'J' => 12, 'K' => 24,
);
foreach ($colWidths as $col => $width) {
	$sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

$filename = 'Petty_Cash_List_' . date('d-m-Y') . '.xlsx';

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
