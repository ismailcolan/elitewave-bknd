<?php
require_once('PhpSpreadsheet/vendor/autoload.php');
require_once('include/connect.php');
require_once('include/function.php');

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

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$customers = isset($_GET['customers']) ? $_GET['customers'] : '';
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

function cargo_filter_names($ids, $lookup)
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

$partyValue = cargo_filter_names($customers, function ($id) use ($conn) {
    return get_client_name($conn, $id);
});
$paymentValue = cargo_filter_names($payment_modes, function ($id) use ($conn) {
    return consignment_mode($conn, $id);
});
$originValue = cargo_filter_names($origins, function ($id) use ($conn) {
    return get_city_name($conn, $id);
});
$destinationValue = cargo_filter_names($destinations, function ($id) use ($conn) {
    return get_city_name($conn, $id);
});
$modeValue = cargo_filter_names($modes, function ($id) use ($conn) {
    return get_mode($conn, $id);
});



$filterSummary = 'Party: ' . $partyValue . ' | Payment Mode: ' . $paymentValue . ' | Origin: ' . $originValue . ' | Destination: ' . $destinationValue . ' | Mode: ' . $modeValue;

function cargo_blank_or_number($val)
{
    if ($val === null || $val === '') {
        return '';
    }
    if (is_numeric($val)) {
        return 0 + $val;
    }
    return $val;
}

function cargo_format_date($val)
{
    if ($val === null || $val === '') {
        return '';
    }
    $val = trim($val);
    $formats = array('d-m-Y', 'Y-m-d', 'd/m/Y', 'j-n-Y', 'd-m-y');
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $val);
        if ($dt) {
            return $dt->format('j-n-Y');
        }
    }
    $parts = preg_split('/\s*,\s*/', $val);
    if (count($parts) > 1) {
        $out = array();
        foreach ($parts as $part) {
            $formatted = cargo_format_date($part);
            if ($formatted !== '') {
                $out[] = $formatted;
            }
        }
        return implode(', ', $out);
    }
    return $val;
}

function cargo_supplier_value($val)
{
    if ($val === null || $val === '') {
        return '';
    }
    $str = trim((string) $val);
    if (stripos($str, 'rs') !== false) {
        return $str;
    }
    if (is_numeric(str_replace(',', '', $str))) {
        $num = (float) str_replace(',', '', $str);
        return 'Rs.' . number_format($num, 2, '.', '');
    }
    return $str;
}

function cargo_packing_names($conn, $raw)
{
    if ($raw === null || $raw === '') {
        return '';
    }
    $parts = array_map('trim', explode(',', $raw));
    $names = array();
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        if (ctype_digit($part)) {
            $name = get_package_name($conn, $part);
            $names[] = $name ? $name : $part;
        } else {
            $names[] = $part;
        }
    }
    return implode(', ', array_unique($names));
}

$all_tables_q = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
$table_list = array();
while ($tbl_row = mysqli_fetch_assoc($all_tables_q)) {
    $table_list[] = $tbl_row['table_name'];
}

$union_queries = array();
foreach ($table_list as $tbl_name) {
    $t_trans = 'transaction_' . $tbl_name;
    $t_inv = 'transaction_invoice_' . $tbl_name;

    $chk = @mysqli_query($conn, "SELECT 1 FROM $t_trans LIMIT 1");
    if (!$chk) {
        continue;
    }

    $colChk = @mysqli_query($conn, "SHOW COLUMNS FROM $t_trans LIKE 'eway_number'");
    if (!$colChk || mysqli_num_rows($colChk) == 0) {
        continue;
    }

    $where = "STR_TO_DATE(t.grn_date, '%d-%m-%Y') >= '$from_mysql'
        AND STR_TO_DATE(t.grn_date, '%d-%m-%Y') <= '$to_mysql'
        AND (t.booking_status IS NULL OR t.booking_status = '' OR t.booking_status != '1')";

    if ($customers != '') {
        $cust_list = implode(',', array_map('intval', explode(',', $customers)));
        if ($cust_list != '') {
            $where .= " AND t.consigner IN ($cust_list)";
        }
    }
    if ($modes != '') {
        $mode_list = implode(',', array_map('intval', explode(',', $modes)));
        if ($mode_list != '') {
            $where .= " AND t.mode_of_transportation IN ($mode_list)";
        }
    }
    if ($origins != '') {
        $origin_list = implode(',', array_map('intval', explode(',', $origins)));
        if ($origin_list != '') {
            $where .= " AND t.origin IN ($origin_list)";
        }
    }
    if ($destinations != '') {
        $dest_list = implode(',', array_map('intval', explode(',', $destinations)));
        if ($dest_list != '') {
            $where .= " AND t.destination IN ($dest_list)";
        }
    }
    if ($payment_modes != '') {
        $pm_list = implode(',', array_map('intval', explode(',', $payment_modes)));
        if ($pm_list != '') {
            $where .= " AND t.mode_of_consignment IN ($pm_list)";
        }
    }

    $union_queries[] = "SELECT
            t.transaction_id,
            t.grn_date,
            t.grn_no,
            t.transaction_id AS trip_id,
            agg.no_of_pkge,
            agg.gross_weight,
            agg.charged_weight,
            t.frieght_rate AS rate,
            t.consigner,
            t.origin,
            t.consignee,
            t.destination,
            t.mode_of_transportation,
            agg.type_of_pkge,
            agg.party_invoice_no,
            agg.party_inv_date,
            t.supplier_invoice_value,
            t.mode_of_consignment,
            t.eway_number AS eway_bill_no,
            t.eway_expirydate AS eway_bill_expiry,
            t.lc_number,
            t.description_of_goods,
            t.cfs,
            t.quotation_approval,
            t.truck AS vehicle_number,
            t.freight_paid_by,
            t.insurance_number,
            t.vehicle_type,
            t.frieght_amount AS freight,
            t.doc_amount AS dc_amt,
            t.fov_amount AS fov,
            t.labour_handling_amount AS hamali_amt,
            t.total AS total_amt,
            t.gst_amount,
            t.total AS grand_total
        FROM $t_trans t
        LEFT JOIN (
            SELECT
                transaction_id,
                SUM(no_of_pkge) AS no_of_pkge,
                SUM(gross_weight) AS gross_weight,
                SUM(charged_weight) AS charged_weight,
                GROUP_CONCAT(DISTINCT CASE WHEN type_of_pkge != '' THEN type_of_pkge END SEPARATOR ', ') AS type_of_pkge,
                GROUP_CONCAT(DISTINCT CASE WHEN party_invoice_no != '' THEN party_invoice_no END SEPARATOR ', ') AS party_invoice_no,
                GROUP_CONCAT(DISTINCT CASE WHEN party_invoice_date != '' THEN party_invoice_date END SEPARATOR ', ') AS party_inv_date
            FROM $t_inv
            GROUP BY transaction_id
        ) agg ON agg.transaction_id = t.transaction_id
        WHERE $where
        GROUP BY t.transaction_id";
}

if (empty($union_queries)) {
    die('No data found for the selected date range.');
}

$final_query = implode(' UNION ALL ', $union_queries) . ' ORDER BY grn_date DESC, grn_no DESC';
$result = @mysqli_query($conn, $final_query);
if (!$result) {
    die('Unable to generate report.');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Cargo Booking Report');
$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
$spreadsheet->getDefaultStyle()->getFont()->getColor()->setRGB('1F1F1F');

$titleFill = 'D6EAF8';
$headerFill = '9DC3E6';
$headerText = '1F4E79';
$altRowFill = 'DDEBF7';
$borderColor = '9BC2E6';
$navyText = '1F4E79';

$columns = array(
    'S.No',
    'GR Date',
    'GR No',
    'Trip ID',
    'Pkgs',
    'Gross Wt',
    'Chg Wt',
    'Rate',
    'Party Name',
    'From',
    'Consignee',
    'To',
    'Mode',
    'Type Of Packing',
    'Party Invoice No',
    'Party Inv Date',
    'Supplier Inv Value',
    'Pymt Mode',
    'EwayBill No',
    'EwayBill Expiry Date',
    'LC Number',
    'Description of Goods',
    'CFS',
    'Quotation Approval',
    'Vehicle Number',
    'Freight Paid By',
    'Insurance Number',
    'Vehicle Type',
    'Freight',
    'DC Amt',
    'FOV',
    'Hamali Amt',
    'Total Amt',
    'GST Amt',
    'Total'
);

$colCount = count($columns);
$colLetters = array();
for ($c = 1; $c <= $colCount; $c++) {
    $colLetters[] = Coordinate::stringFromColumnIndex($c);
}
$lastCol = $colLetters[$colCount - 1];

$reportTitle = 'Cargo Booking Report : ' . $from_date . ' to ' . $to_date;

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
        'horizontal' => Alignment::HORIZONTAL_CENTER,
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
));
$sheet->getStyle('A2')->applyFromArray(array(
    'font' => array('bold' => true, 'size' => 12, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
));
$sheet->getStyle('A3')->applyFromArray(array(
    'font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => $navyText), 'name' => 'Calibri'),
    'alignment' => array(
        'horizontal' => Alignment::HORIZONTAL_CENTER,
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
            'color' => array('rgb' => $borderColor),
        ),
    ),
    'alignment' => array(
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ),
));
$sheet->getRowDimension($headerRow)->setRowHeight(28);

$numericCols = array(0, 4, 5, 6, 7, 28, 29, 30, 31, 32, 33, 34);
$textCols = array(1, 2, 3, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);

$dataRowStyle = array(
    'font' => array('size' => 10, 'name' => 'Calibri', 'color' => array('rgb' => '1F1F1F')),
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

$rowNum = $headerRow + 1;
$i = 1;

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $values = array(
            $i,
            cargo_format_date($row['grn_date']),
            $row['grn_no'],
            $row['trip_id'],
            cargo_blank_or_number($row['no_of_pkge']),
            cargo_blank_or_number($row['gross_weight']),
            cargo_blank_or_number($row['charged_weight']),
            cargo_blank_or_number($row['rate']),
            get_client_name($conn, $row['consigner']),
            get_city_name($conn, $row['origin']),
            get_client_name($conn, $row['consignee']),
            get_city_name($conn, $row['destination']),
            get_mode($conn, $row['mode_of_transportation']),
            cargo_packing_names($conn, $row['type_of_pkge']),
            $row['party_invoice_no'],
            cargo_format_date($row['party_inv_date']),
            cargo_supplier_value($row['supplier_invoice_value']),
            consignment_mode($conn, $row['mode_of_consignment']),
            $row['eway_bill_no'],
            cargo_format_date($row['eway_bill_expiry']),
            $row['lc_number'],
            $row['description_of_goods'],
            $row['cfs'],
            $row['quotation_approval'],
            $row['vehicle_number'],
            $row['freight_paid_by'],
            $row['insurance_number'],
            $row['vehicle_type'],
            cargo_blank_or_number($row['freight']),
            cargo_blank_or_number($row['dc_amt']),
            cargo_blank_or_number($row['fov']),
            cargo_blank_or_number($row['hamali_amt']),
            cargo_blank_or_number($row['total_amt']),
            cargo_blank_or_number($row['gst_amount']),
            cargo_blank_or_number($row['grand_total']),
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

        $sheet->getRowDimension($rowNum)->setRowHeight(20);
        $rowNum++;
        $i++;
    }
}

$lastDataRow = ($rowNum > $headerRow + 1) ? ($rowNum - 1) : $headerRow;
$sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . $lastDataRow);
$sheet->freezePane('A' . ($headerRow + 1));

$colWidths = array(
    'A' => 8, 'B' => 12, 'C' => 16, 'D' => 12, 'E' => 8, 'F' => 12, 'G' => 12, 'H' => 10,
    'I' => 32, 'J' => 16, 'K' => 32, 'L' => 16, 'M' => 20, 'N' => 18, 'O' => 20, 'P' => 14,
    'Q' => 20, 'R' => 14, 'S' => 20, 'T' => 18, 'U' => 14, 'V' => 28, 'W' => 10, 'X' => 18,
    'Y' => 16, 'Z' => 18, 'AA' => 16, 'AB' => 14, 'AC' => 12, 'AD' => 12, 'AE' => 12,
    'AF' => 12, 'AG' => 12, 'AH' => 12, 'AI' => 14,
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

$filename = 'Cargo_Booking_Report_' . $from_date . '_to_' . $to_date . '.xlsx';

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
