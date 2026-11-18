<?php
require_once('PhpSpreadsheet/vendor/autoload.php');
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/gst_register_functions.php');
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

$filters = gst_input_parse_filters($_GET);
if (!empty($filters['error'])) {
	die($filters['error']);
}

function gst_in_filter_vendor_names($conn, $ids)
{
	if ($ids === null || $ids === '') {
		return 'ALL';
	}
	$names = array();
	foreach (array_filter(array_map('intval', explode(',', $ids))) as $id) {
		$id = (int) $id;
		$q = mysqli_query($conn, "SELECT vendor_code, vendor_name FROM vendor_master WHERE vendor_id='$id' LIMIT 1");
		if ($q && ($r = mysqli_fetch_assoc($q))) {
			$label = trim(($r['vendor_code'] ?? '') . ' - ' . ($r['vendor_name'] ?? ''), ' -');
			if ($label !== '') {
				$names[] = $label;
			}
		}
	}
	return empty($names) ? 'ALL' : implode(', ', $names);
}

$docOpts = gst_input_doc_type_options();
$docLabel = isset($docOpts[$filters['doc_type']]) ? $docOpts[$filters['doc_type']] : 'All documents';
$filterSummary = 'Doc Type: ' . $docLabel . ' | Vendor: ' . gst_in_filter_vendor_names($conn, $filters['vendors']);

$result = gst_input_fetch_rows($conn, $filters);
$rows = $result['rows'];
$summary = $result['summary'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('GST Input Register');
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
	'S.No', 'Doc Type', 'Doc No', 'Date', 'Vendor', 'GSTIN', 'Against',
	'Taxable', 'CGST', 'SGST', 'IGST', 'Total GST', 'Doc Value',
);

$colCount = count($columns);
$colLetters = array();
for ($c = 1; $c <= $colCount; $c++) {
	$colLetters[] = Coordinate::stringFromColumnIndex($c);
}
$lastCol = $colLetters[$colCount - 1];
$sheetCanvas = ew_excel_apply_report_sheet_look($spreadsheet, $sheet, $lastCol, count($rows));

$reportTitle = 'GST Input Register : ' . $filters['from_date'] . ' to ' . $filters['to_date'];

$sheet->mergeCells('A1:' . $lastCol . '1');
$sheet->setCellValue('A1', 'EliteWave360 Logistics');
$sheet->getRowDimension(1)->setRowHeight(28);

$sheet->mergeCells('A2:' . $lastCol . '2');
$sheet->setCellValue('A2', $reportTitle);
$sheet->getRowDimension(2)->setRowHeight(22);

$sheet->mergeCells('A3:' . $lastCol . '3');
$sheet->setCellValue(
	'A3',
	$filterSummary .
	' | Taxable: ' . $summary['taxable_value'] .
	' | CGST: ' . $summary['cgst_amount'] .
	' | SGST: ' . $summary['sgst_amount'] .
	' | IGST: ' . $summary['igst_amount'] .
	' | Total GST: ' . $summary['gst_amount']
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
	foreach ($rows as $r) {
		$values = array(
			(int) $r['s_no'],
			$r['doc_type_label'],
			$r['doc_no'],
			$r['doc_date'],
			$r['party'],
			$r['gstin'],
			$r['against'],
			(float) $r['taxable_value'],
			(float) $r['cgst_amount'],
			(float) $r['sgst_amount'],
			(float) $r['igst_amount'],
			(float) $r['gst_amount'],
			(float) $r['doc_value'],
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
		foreach (array(1, 2, 3, 4, 5, 6) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		}
		foreach (array(7, 8, 9, 10, 11, 12) as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
		}
		$sheet->getRowDimension($rowNum)->setRowHeight(20);
		$rowNum++;
	}

	$sheet->setCellValue('A' . $rowNum, '');
	$sheet->setCellValue('B' . $rowNum, 'Total (' . (int) $summary['row_count'] . ')');
	$sheet->mergeCells('B' . $rowNum . ':G' . $rowNum);
	$sheet->setCellValue('H' . $rowNum, (float) $summary['taxable_value']);
	$sheet->setCellValue('I' . $rowNum, (float) $summary['cgst_amount']);
	$sheet->setCellValue('J' . $rowNum, (float) $summary['sgst_amount']);
	$sheet->setCellValue('K' . $rowNum, (float) $summary['igst_amount']);
	$sheet->setCellValue('L' . $rowNum, (float) $summary['gst_amount']);
	$sheet->setCellValue('M' . $rowNum, (float) $summary['doc_value']);
	$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray(array(
		'font' => array('bold' => true, 'size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => $navyText)),
		'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => $totalFill)),
		'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
	));
	foreach (array('H', 'I', 'J', 'K', 'L', 'M') as $col) {
		$sheet->getStyle($col . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
		$sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
	}
	$sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

$sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . (empty($rows) ? $headerRow : (($rowNum > $headerRow + 1) ? ($rowNum - 1) : $headerRow)));
ew_excel_fill_rows_below_report($sheet, $rowNum, $sheetCanvas);

$colWidths = array(
	'A' => 8, 'B' => 14, 'C' => 14, 'D' => 12, 'E' => 28, 'F' => 18, 'G' => 14,
	'H' => 12, 'I' => 11, 'J' => 11, 'K' => 11, 'L' => 12, 'M' => 12,
);
foreach ($colWidths as $col => $width) {
	$sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

$filename = 'GST_Input_Register_' . $filters['from_date'] . '_to_' . $filters['to_date'] . '.xlsx';

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
