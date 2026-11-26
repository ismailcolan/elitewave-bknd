<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/petty_cash_helpers.php');
require_once('include/expense_gcn_helpers.php');
require_once('include/expense_general_helpers.php');

while (ob_get_level()) {
	ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
	echo json_encode(array(
		'status' => 1,
		'message' => empty($_SESSION['user_id']) ? 'Session expired.' : 'Access denied.',
	), JSON_UNESCAPED_UNICODE);
	exit;
}

petty_cash_ensure_schema($conn);

function petty_cash_json_out($payload)
{
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';
$user_id = (int) ($_SESSION['user_id'] ?? 0);

if ($cmd === 'fetch') {
	$id = (int) ($_REQUEST['petty_cash_id'] ?? 0);
	if ($id <= 0) {
		$vno = trim((string) ($_REQUEST['voucher_no'] ?? ''));
		if ($vno !== '') {
			$id = petty_cash_id_by_voucher_no($conn, $vno);
		}
	}
	$record = petty_cash_load($conn, $id);
	if (!$record) {
		petty_cash_json_out(array('status' => 1, 'message' => 'Petty cash voucher not found.'));
	}
	petty_cash_json_out(array_merge(array('status' => 0), $record));
}

if ($cmd === 'view') {
	$id = (int) ($_REQUEST['petty_cash_id'] ?? 0);
	$record = petty_cash_load($conn, $id);
	if (!$record) {
		petty_cash_json_out(array('status' => 1, 'message' => 'Petty cash voucher not found.'));
	}
	$summary = petty_cash_summary($conn);
	petty_cash_json_out(array(
		'status' => 0,
		'data' => $record,
		'summary' => $summary,
	));
}

if ($cmd === 'next_voucher_no') {
	petty_cash_json_out(array(
		'status' => 0,
		'voucher_no' => petty_cash_preview_next_no($conn),
	));
}

if ($cmd === 'summary') {
	petty_cash_json_out(array(
		'status' => 0,
		'summary' => petty_cash_summary($conn),
	));
}

if ($cmd === 'fetch_options') {
	petty_cash_json_out(array(
		'status' => 0,
		'cash_types' => petty_cash_cash_types(),
		'entry_types' => petty_cash_entry_types(),
		'vendors' => petty_cash_vendor_options($conn),
		'categories' => petty_cash_category_options($conn),
		'gcn_options' => petty_cash_fetch_gcn_options($conn),
		'summary' => petty_cash_summary($conn),
	));
}

if ($cmd === 'vendor_options') {
	$search = trim((string) ($_REQUEST['q'] ?? $_REQUEST['search'] ?? ''));
	petty_cash_json_out(array(
		'status' => 0,
		'vendors' => petty_cash_vendor_search($conn, $search),
	));
}

if ($cmd === 'gcn_options') {
	$search = trim((string) ($_REQUEST['q'] ?? $_REQUEST['search'] ?? ''));
	petty_cash_json_out(array(
		'status' => 0,
		'options' => petty_cash_fetch_gcn_options($conn, $search),
	));
}

if ($cmd === 'gcn_context') {
	$keys = isset($_REQUEST['gcn_keys']) ? $_REQUEST['gcn_keys'] : array();
	if (is_string($keys) && $keys !== '') {
		$decoded = json_decode($keys, true);
		$keys = is_array($decoded) ? $decoded : explode(',', $keys);
	}
	if (!is_array($keys)) {
		$keys = array();
	}
	petty_cash_json_out(array(
		'status' => 0,
		'contexts' => petty_cash_gcn_contexts_for_keys($conn, $keys),
	));
}

if ($cmd === 'calc_amounts') {
	$vendor_id = (int) ($_REQUEST['vendor_id'] ?? 0);
	$category_id = (int) ($_REQUEST['category_id'] ?? 0);
	$expense_amount = petty_cash_parse_money($_REQUEST['expense_amount'] ?? 0);
	$gst_percent = array_key_exists('gst_percent', $_REQUEST)
		? expense_general_normalize_gst_percent($_REQUEST['gst_percent'])
		: null;
	$tds_percent = array_key_exists('tds_percent', $_REQUEST)
		? round((float) expense_gcn_parse_money($_REQUEST['tds_percent']), 2)
		: null;
	$tax = petty_cash_calc_line_tax($conn, $vendor_id, $category_id, $expense_amount, $gst_percent, $tds_percent);
	petty_cash_json_out(array(
		'status' => 0,
		'gst_percent' => $tax['gst_percent'],
		'gst_amount' => $tax['gst_amount'],
		'tds_amount' => $tax['tds_amount'],
		'net_amount' => $tax['net_amount'],
		'suggested_tds_rate' => $tax['suggested_tds_rate'],
		'vendor_tax' => $tax['vendor_tax'],
		'category_tax' => $tax['category_tax'],
	));
}

if ($cmd === 'save') {
	$result = petty_cash_save($conn, $_POST, $user_id);
	petty_cash_json_out($result);
}

if ($cmd === 'delete') {
	$id = (int) ($_REQUEST['petty_cash_id'] ?? 0);
	$result = petty_cash_delete($conn, $id, $user_id);
	petty_cash_json_out($result);
}

petty_cash_json_out(array('status' => 1, 'message' => 'Unknown command.'));
