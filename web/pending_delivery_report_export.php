<?php
require_once('PhpSpreadsheet/vendor/autoload.php');
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/pending_delivery_report_query.php');
require_once('include/ew_excel_report_sheet.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

error_reporting(0);
ini_set('display_errors', 0);
ini_set('output_buffering', 0);
ini_set('zlib.output_compression', 0);
@ini_set('memory_limit', '512M');
@set_time_limit(180);

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$customers = isset($_GET['customers']) ? $_GET['customers'] : '';
$statuses = isset($_GET['statuses']) ? $_GET['statuses'] : '';
$modes = isset($_GET['modes']) ? $_GET['modes'] : '';
$origins = isset($_GET['origins']) ? $_GET['origins'] : '';
$destinations = isset($_GET['destinations']) ? $_GET['destinations'] : '';
$payment_modes = isset($_GET['payment_modes']) ? $_GET['payment_modes'] : '';

if ($from_date == '' || $to_date == '') {
	die('From Date and To Date are required.');
}

$from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
$to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
if (!$from_dt || !$to_dt) {
	die('Invalid date format.');
}

$from_mysql = $from_dt->format('Y-m-d');
$to_mysql = $to_dt->format('Y-m-d');

function pdr_filter_names($ids, $lookup)
{
	if ($ids === null || $ids === '') {
		return 'ALL';
	}
	$names = array();
	foreach (array_map('intval', explode(',', $ids)) as $id) {
		if ($id <= 0) {
			continue;
		}
		$name = $lookup($id);
		if ($name !== null && $name !== '') {
			$names[] = $name;
		}
	}
	return empty($names) ? 'ALL' : implode(', ', $names);
}

function pdr_status_filter_labels($statuses)
{
	if ($statuses === null || $statuses === '') {
		return 'ALL PENDING';
	}
	$map = array(
		'1' => 'Booked',
		'2' => 'Picked Up',
		'3' => 'Transit-1',
		'4' => 'Transit-2',
		'5' => 'Transit-3',
		'6' => 'At Destination',
		'7' => 'Out for Delivery',
		'partial' => 'Partially Delivered',
	);
	$labels = array();
	foreach (array_filter(array_map('trim', explode(',', $statuses)), 'strlen') as $part) {
		if (isset($map[$part])) {
			$labels[] = $map[$part];
		}
	}
	return empty($labels) ? 'ALL PENDING' : implode(', ', $labels);
}

$partyValue = pdr_filter_names($customers, function ($id) use ($conn) {
	return get_client_name($conn, $id);
});
$paymentValue = pdr_filter_names($payment_modes, function ($id) use ($conn) {
	return consignment_mode($conn, $id);
});
$originValue = pdr_filter_names($origins, function ($id) use ($conn) {
	return get_city_name($conn, $id);
});
$destinationValue = pdr_filter_names($destinations, function ($id) use ($conn) {
	return get_city_name($conn, $id);
});
$modeValue = pdr_filter_names($modes, function ($id) use ($conn) {
	return get_mode($conn, $id);
});
$statusValue = pdr_status_filter_labels($statuses);

$filterSummary = 'Party: ' . $partyValue
	. ' | Status: ' . $statusValue
	. ' | Payment Mode: ' . $paymentValue
	. ' | Origin: ' . $originValue
	. ' | Destination: ' . $destinationValue
	. ' | Mode: ' . $modeValue;

$rows = pdr_fetch_rows($conn, $from_mysql, $to_mysql, $customers, $statuses, $modes, $origins, $destinations, $payment_modes);
if ($rows === false) {
	die('Unable to generate report.');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Pending Delivery');
$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
$spreadsheet->getDefaultStyle()->getFont()->getColor()->setRGB('1F1F1F');

$titleFill = 'D6EAF8';
$headerFill = '9DC3E6';
$headerText = '1F4E79';
$altRowFill = 'DDEBF7';
$borderColor = '9BC2E6';
$headerBorderColor = '1F4E79';
$navyText = '1F4E79';

$columns = array(
	'S.No',
	'GR Date',
	'GR No',
	'Status',
	'Days Pending',
	'Pkgs',
	'Delivered Pkgs',
	'Party Name',
	'From',
	'Consignee',
	'To',
	'Mode',
	'Pymt Mode',
	'Vehicle Number',
	'EwayBill No',
	'EwayBill Expiry',
	'Description of Goods',
);

$colCount = count($columns);
$colLetters = array();
for ($c = 1; $c <= $colCount; $c++) {
	$colLetters[] = Coordinate::stringFromColumnIndex($c);
}
$lastCol = $colLetters[$colCount - 1];
$sheetCanvas = ew_excel_apply_report_sheet_look($spreadsheet, $sheet, $lastCol, is_array($rows) ? count($rows) : 0);

$reportTitle = 'Pending Delivery Report : ' . $from_date . ' to ' . $to_date;

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
	'fill' => array(
		'fillType' => Fill::FILL_SOLID,
		'color' => array('rgb' => $titleFill),
	),
	'alignment' => array(
		'horizontal' => Alignment::HORIZONTAL_LEFT,
		'vertical' => Alignment::VERTICAL_CENTER,
	),
	'borders' => array(
		'allBorders' => array(
			'borderStyle' => Border::BORDER_THIN,
			'color' => array('rgb' => $borderColor),
		),
	),
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
	'alignment' => array(
		'horizontal' => Alignment::HORIZONTAL_LEFT,
		'vertical' => Alignment::VERTICAL_CENTER,
		'wrapText' => true,
	),
));

$headerRow = 4;
foreach ($columns as $colIndex => $colName) {
	$sheet->setCellValue($colLetters[$colIndex] . $headerRow, $colName);
}

$sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray(array(
	'font' => array(
		'bold' => true,
		'size' => 10,
		'color' => array('rgb' => $headerText),
		'name' => 'Calibri',
	),
	'fill' => array(
		'fillType' => Fill::FILL_SOLID,
		'color' => array('rgb' => $headerFill),
	),
	'borders' => array(
		'allBorders' => array(
			'borderStyle' => Border::BORDER_THIN,
			'color' => array('rgb' => $headerBorderColor),
		),
	),
	'alignment' => array(
		'horizontal' => Alignment::HORIZONTAL_CENTER,
		'vertical' => Alignment::VERTICAL_CENTER,
		'wrapText' => true,
	),
));
$sheet->getRowDimension($headerRow)->setRowHeight(28);

$dataRowStyle = array(
	'font' => array('size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => '1F1F1F')),
	'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => 'FFFFFF')),
	'borders' => array(
		'allBorders' => array(
			'borderStyle' => Border::BORDER_THIN,
			'color' => array('rgb' => $borderColor),
		),
	),
	'alignment' => array(
		'vertical' => Alignment::VERTICAL_CENTER,
		'wrapText' => false,
	),
);

$numericCols = array(0, 4, 5, 6);
$textCols = array(1, 2, 3, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16);

$rowNum = $headerRow + 1;
$i = 1;

if (empty($rows)) {
	$sheet->mergeCells('A' . $rowNum . ':' . $lastCol . $rowNum);
	$sheet->setCellValue('A' . $rowNum, 'No data available');
	$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray(array(
		'font' => array('bold' => true, 'size' => 12, 'name' => 'Calibri', 'color' => array('rgb' => '64748B')),
		'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => 'F4F7FB')),
		'borders' => array('allBorders' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('rgb' => $borderColor))),
		'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER),
	));
	$sheet->getRowDimension($rowNum)->setRowHeight(28);
} else {
	foreach ($rows as $raw) {
		$mapped = pdr_map_row($conn, $raw, $i);
		$values = array(
			$mapped['s_no'],
			$mapped['gr_date'],
			$mapped['gr_no'],
			$mapped['status_label'],
			$mapped['days_pending'],
			$mapped['pkgs'] === '' ? '' : (0 + $mapped['pkgs']),
			$mapped['delivered_pkgs'] === '' ? '' : (0 + $mapped['delivered_pkgs']),
			$mapped['party_name'],
			$mapped['from_city'],
			$mapped['consignee'],
			$mapped['to_city'],
			$mapped['mode'],
			$mapped['pymt_mode'],
			$mapped['vehicle_number'],
			$mapped['eway_bill_no'],
			$mapped['eway_bill_expiry'],
			$mapped['desc_of_goods'],
		);

		foreach ($values as $valIndex => $val) {
			$sheet->setCellValue($colLetters[$valIndex] . $rowNum, $val);
		}

		$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray($dataRowStyle);

		if ($i % 2 == 0) {
			$sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)
				->getFill()
				->setFillType(Fill::FILL_SOLID)
				->getStartColor()
				->setRGB($altRowFill);
		}

		foreach ($numericCols as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
		}
		foreach ($textCols as $idx) {
			$sheet->getStyle($colLetters[$idx] . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		}
		$sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

		// Highlight aging
		$days = (int) $mapped['days_pending'];
		if ($days >= 7) {
			$sheet->getStyle($colLetters[4] . $rowNum)->getFont()->getColor()->setRGB('B91C1C');
			$sheet->getStyle($colLetters[4] . $rowNum)->getFont()->setBold(true);
		} elseif ($days >= 3) {
			$sheet->getStyle($colLetters[4] . $rowNum)->getFont()->getColor()->setRGB('B45309');
			$sheet->getStyle($colLetters[4] . $rowNum)->getFont()->setBold(true);
		}

		$sheet->getRowDimension($rowNum)->setRowHeight(20);
		$rowNum++;
		$i++;
	}
}

$lastDataRow = empty($rows) ? $headerRow : (($rowNum > $headerRow + 1) ? ($rowNum - 1) : $headerRow);
$sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . $lastDataRow);
ew_excel_fill_rows_below_report($sheet, empty($rows) ? $headerRow : ($rowNum - 1), $sheetCanvas);

$colWidths = array(
	'A' => 8, 'B' => 12, 'C' => 16, 'D' => 36, 'E' => 12, 'F' => 8, 'G' => 14,
	'H' => 32, 'I' => 16, 'J' => 32, 'K' => 16, 'L' => 18, 'M' => 14, 'N' => 16,
	'O' => 18, 'P' => 16, 'Q' => 28,
);
foreach ($colWidths as $col => $width) {
	$sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 4);
$sheet->getPageMargins()->setTop(0.4);
$sheet->getPageMargins()->setBottom(0.4);
$sheet->getPageMargins()->setLeft(0.3);
$sheet->getPageMargins()->setRight(0.3);

$filename = 'Pending_Delivery_Report_' . $from_date . '_to_' . $to_date . '.xlsx';

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
