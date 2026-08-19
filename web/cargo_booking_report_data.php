<?php
require_once('include/connect.php');
require_once('include/function.php');

error_reporting(0);
ini_set('display_errors', 0);
@ini_set('memory_limit', '512M');
@set_time_limit(180);

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

function cargo_json($payload)
{
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags = $flags | JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $json = json_encode($payload, $flags);
    if ($json === false) {
        $json = json_encode(array(
            'status' => 1,
            'message' => 'Unable to encode report data.',
            'data' => array()
        ));
    }
    echo $json;
    exit;
}

function cargo_text($val)
{
    if ($val === null || $val === false) {
        return '';
    }
    $val = (string) $val;
    if (function_exists('mb_convert_encoding')) {
        $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
    }
    return $val;
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
            $name = @get_package_name($conn, $part);
            $names[] = $name ? $name : $part;
        } else {
            $names[] = $part;
        }
    }
    return implode(', ', array_unique($names));
}

$from_date = isset($_REQUEST['from_date']) ? $_REQUEST['from_date'] : '';
$to_date = isset($_REQUEST['to_date']) ? $_REQUEST['to_date'] : '';
$customers = isset($_REQUEST['customers']) ? $_REQUEST['customers'] : '';
$modes = isset($_REQUEST['modes']) ? $_REQUEST['modes'] : '';
$origins = isset($_REQUEST['origins']) ? $_REQUEST['origins'] : '';
$destinations = isset($_REQUEST['destinations']) ? $_REQUEST['destinations'] : '';
$payment_modes = isset($_REQUEST['payment_modes']) ? $_REQUEST['payment_modes'] : '';

if ($from_date == '' || $to_date == '') {
    cargo_json(array('status' => 1, 'message' => 'From Date and To Date are required.', 'data' => array()));
}

$from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
$to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
if (!$from_dt || !$to_dt) {
    cargo_json(array('status' => 1, 'message' => 'Invalid date format.', 'data' => array()));
}

$from_mysql = $from_dt->format('Y-m-d');
$to_mysql = $to_dt->format('Y-m-d');

$all_tables_q = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
$table_list = array();
if ($all_tables_q) {
    while ($tbl_row = mysqli_fetch_assoc($all_tables_q)) {
        $table_list[] = $tbl_row['table_name'];
    }
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
        WHERE $where";
}

if (empty($union_queries)) {
    cargo_json(array('status' => 0, 'data' => array()));
}

$final_query = implode(' UNION ALL ', $union_queries) . ' ORDER BY grn_date DESC, grn_no DESC';
$result = @mysqli_query($conn, $final_query);

$data = array();
$i = 1;
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = array(
            's_no' => $i,
            'gr_date' => cargo_text($row['grn_date']),
            'gr_no' => cargo_text($row['grn_no']),
            'trip_id' => cargo_text($row['trip_id']),
            'pkgs' => cargo_text($row['no_of_pkge']),
            'gross_wt' => cargo_text($row['gross_weight']),
            'chg_wt' => cargo_text($row['charged_weight']),
            'rate' => cargo_text($row['rate']),
            'party_name' => cargo_text(@get_client_name($conn, $row['consigner'])),
            'from_city' => cargo_text(@get_city_name($conn, $row['origin'])),
            'consignee' => cargo_text(@get_client_name($conn, $row['consignee'])),
            'to_city' => cargo_text(@get_city_name($conn, $row['destination'])),
            'mode' => cargo_text(@get_mode($conn, $row['mode_of_transportation'])),
            'type_of_packing' => cargo_text(cargo_packing_names($conn, $row['type_of_pkge'])),
            'party_invoice_no' => cargo_text($row['party_invoice_no']),
            'party_inv_date' => cargo_text($row['party_inv_date']),
            'supplier_inv_value' => cargo_text($row['supplier_invoice_value']),
            'pymt_mode' => cargo_text(@consignment_mode($conn, $row['mode_of_consignment'])),
            'eway_bill_no' => cargo_text($row['eway_bill_no']),
            'eway_bill_expiry' => cargo_text($row['eway_bill_expiry']),
            'lc_number' => cargo_text($row['lc_number']),
            'desc_of_goods' => cargo_text($row['description_of_goods']),
            'cfs' => cargo_text($row['cfs']),
            'quotation_approval' => cargo_text($row['quotation_approval']),
            'vehicle_number' => cargo_text($row['vehicle_number']),
            'freight_paid_by' => cargo_text($row['freight_paid_by']),
            'insurance_number' => cargo_text($row['insurance_number']),
            'vehicle_type' => cargo_text($row['vehicle_type']),
            'freight' => cargo_text($row['freight']),
            'dc_amt' => cargo_text($row['dc_amt']),
            'fov' => cargo_text($row['fov']),
            'hamali_amt' => cargo_text($row['hamali_amt']),
            'total_amt' => cargo_text($row['total_amt']),
            'gst_amt' => cargo_text($row['gst_amount']),
            'total' => cargo_text($row['grand_total'])
        );
        $i++;
    }
}

cargo_json(array('status' => 0, 'data' => $data));
