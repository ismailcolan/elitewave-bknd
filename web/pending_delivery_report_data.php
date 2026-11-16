<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/pending_delivery_report_query.php');

error_reporting(0);
ini_set('display_errors', 0);
@ini_set('memory_limit', '512M');
@set_time_limit(180);

while (ob_get_level()) {
	ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

function pdr_json($payload)
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

$from_date = isset($_REQUEST['from_date']) ? $_REQUEST['from_date'] : '';
$to_date = isset($_REQUEST['to_date']) ? $_REQUEST['to_date'] : '';
$customers = isset($_REQUEST['customers']) ? $_REQUEST['customers'] : '';
$statuses = isset($_REQUEST['statuses']) ? $_REQUEST['statuses'] : '';
$modes = isset($_REQUEST['modes']) ? $_REQUEST['modes'] : '';
$origins = isset($_REQUEST['origins']) ? $_REQUEST['origins'] : '';
$destinations = isset($_REQUEST['destinations']) ? $_REQUEST['destinations'] : '';
$payment_modes = isset($_REQUEST['payment_modes']) ? $_REQUEST['payment_modes'] : '';

if ($from_date == '' || $to_date == '') {
	pdr_json(array('status' => 1, 'message' => 'From Date and To Date are required.', 'data' => array()));
}

$from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
$to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
if (!$from_dt || !$to_dt) {
	pdr_json(array('status' => 1, 'message' => 'Invalid date format.', 'data' => array()));
}

$from_mysql = $from_dt->format('Y-m-d');
$to_mysql = $to_dt->format('Y-m-d');

$rows = pdr_fetch_rows($conn, $from_mysql, $to_mysql, $customers, $statuses, $modes, $origins, $destinations, $payment_modes);
if ($rows === false) {
	pdr_json(array('status' => 1, 'message' => 'Unable to load report data.', 'data' => array()));
}

$data = array();
$i = 1;
foreach ($rows as $row) {
	$data[] = pdr_map_row($conn, $row, $i);
	$i++;
}

pdr_json(array('status' => 0, 'data' => $data));
