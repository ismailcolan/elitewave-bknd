<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_general_helpers.php');
require_once('include/expense_type_helpers.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('status' => 1, 'message' => empty($_SESSION['user_id']) ? 'Session expired.' : 'Access denied.'));
	exit;
}

header('Content-Type: application/json; charset=utf-8');
expense_general_ensure_schema($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function expense_general_json_out($payload)
{
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

if ($cmd === 'fetch') {
	$id = (int) ($_REQUEST['general_expense_id'] ?? 0);
	$record = expense_general_load($conn, $id);
	if (!$record) {
		expense_general_json_out(array('status' => 1, 'message' => 'Expense record not found.'));
	}
	expense_general_json_out(array_merge(array('status' => 0), $record));
}

if ($cmd === 'next_expense_no') {
	expense_general_json_out(array(
		'status' => 0,
		'expense_no' => expense_general_preview_next_no($conn),
	));
}

if ($cmd === 'fetch_options') {
	$banks = ew_company_bank_options($conn);
	$primary_bank_id = 0;
	foreach ($banks as $bank) {
		if (!empty($bank['is_primary'])) {
			$primary_bank_id = (int) $bank['bank_account_id'];
			break;
		}
	}
	expense_general_json_out(array(
		'status' => 0,
		'vendors' => expense_gcn_vendor_options($conn),
		'categories' => expense_gcn_category_options($conn),
		'banks' => $banks,
		'payment_modes' => ew_company_bank_payment_modes(),
		'primary_bank_id' => $primary_bank_id,
	));
}

if ($cmd === 'vendor_tax_info') {
	$vendor_id = (int) ($_REQUEST['vendor_id'] ?? 0);
	expense_general_json_out(array(
		'status' => 0,
		'tax' => expense_general_vendor_tax_info($conn, $vendor_id),
	));
}

if ($cmd === 'category_tax_info') {
	$category_id = (int) ($_REQUEST['category_id'] ?? 0);
	expense_general_json_out(array(
		'status' => 0,
		'tax' => expense_general_category_tax_info($conn, $category_id),
	));
}

if ($cmd === 'calc_amounts') {
	$vendor_id = (int) ($_REQUEST['vendor_id'] ?? 0);
	$category_id = (int) ($_REQUEST['category_id'] ?? 0);
	$expense_amount = expense_gcn_parse_money($_REQUEST['expense_amount'] ?? 0);
	$suggested = expense_general_suggest_tax($conn, $vendor_id, $category_id, $expense_amount);
	$gst_amount = round(expense_gcn_parse_money($_REQUEST['gst_amount'] ?? $suggested['gst_amount']), 2);
	$tds_amount = round(expense_gcn_parse_money($_REQUEST['tds_amount'] ?? $suggested['tds_amount']), 2);
	if (empty($suggested['vendor']['gst_applicable'])) {
		$gst_amount = 0;
	}
	$other_deduction = round(expense_gcn_parse_money($_REQUEST['other_deduction'] ?? 0), 2);
	$amounts = expense_general_calc_amounts($expense_amount, $gst_amount, $tds_amount, $other_deduction);
	expense_general_json_out(array(
		'status' => 0,
		'suggested_gst' => $suggested['gst_amount'],
		'suggested_tds' => $suggested['tds_amount'],
		'vendor_tax' => $suggested['vendor'],
		'category_tax' => $suggested['category'],
		'amounts' => $amounts,
	));
}

if ($cmd === 'add_expense_type') {
	$type_name = trim($_REQUEST['type_name'] ?? '');
	$result = expense_type_quick_add_category($conn, $type_name, (int) ($_SESSION['user_id'] ?? 0));
	if (empty($result['ok'])) {
		expense_general_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Could not add expense type.'));
	}
	expense_general_json_out(array(
		'status' => 0,
		'category_id' => (int) ($result['category_id'] ?? 0),
		'label' => $result['label'] ?? '',
		'categories' => expense_gcn_category_options($conn),
	));
}

if ($cmd === 'save') {
	$payload = array(
		'general_expense_id' => (int) ($_REQUEST['general_expense_id'] ?? 0),
		'expense_date' => $_REQUEST['expense_date'] ?? '',
		'vendor_id' => $_REQUEST['vendor_id'] ?? 0,
		'category_id' => $_REQUEST['category_id'] ?? 0,
		'expense_amount' => $_REQUEST['expense_amount'] ?? 0,
		'gst_amount' => $_REQUEST['gst_amount'] ?? 0,
		'tds_amount' => $_REQUEST['tds_amount'] ?? 0,
		'other_deduction' => $_REQUEST['other_deduction'] ?? 0,
		'description' => $_REQUEST['description'] ?? '',
		'payment_mode' => $_REQUEST['payment_mode'] ?? '',
		'company_bank_id' => $_REQUEST['company_bank_id'] ?? 0,
		'payment_bank_name' => $_REQUEST['payment_bank_name'] ?? '',
		'payment_ifsc' => $_REQUEST['payment_ifsc'] ?? '',
		'payment_bank_branch' => $_REQUEST['payment_bank_branch'] ?? '',
	);
	$result = expense_general_save($conn, $payload, (int) ($_SESSION['user_id'] ?? 0));
	if (empty($result['ok'])) {
		expense_general_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Save failed.'));
	}
	$record = $result['record'] ?? array();
	expense_general_json_out(array_merge(array(
		'status' => 0,
		'message' => $result['message'],
		'general_expense_id' => (int) ($result['general_expense_id'] ?? 0),
		'expense_no' => $result['expense_no'] ?? '',
	), $record));
}

if ($cmd === 'delete') {
	$id = (int) ($_REQUEST['general_expense_id'] ?? 0);
	$result = expense_general_delete($conn, $id);
	if (empty($result['ok'])) {
		expense_general_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Delete failed.'));
	}
	expense_general_json_out(array('status' => 0, 'message' => $result['message']));
}

expense_general_json_out(array('status' => 1, 'message' => 'Invalid request.'));
