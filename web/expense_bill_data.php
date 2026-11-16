<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_bill_helpers.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('status' => 1, 'message' => empty($_SESSION['user_id']) ? 'Session expired.' : 'Access denied.'));
	exit;
}

header('Content-Type: application/json; charset=utf-8');
expense_bill_ensure_schema($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function expense_bill_json_out($payload)
{
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

if ($cmd === 'fetch') {
	$id = (int) ($_REQUEST['bill_id'] ?? 0);
	$record = expense_bill_load($conn, $id);
	if (!$record) {
		expense_bill_json_out(array('status' => 1, 'message' => 'Bill record not found.'));
	}
	expense_bill_json_out(array_merge(array('status' => 0), $record));
}

if ($cmd === 'view') {
	$id = (int) ($_REQUEST['bill_id'] ?? 0);
	$record = expense_bill_view_data($conn, $id);
	if (!$record) {
		expense_bill_json_out(array('status' => 1, 'message' => 'Bill record not found.'));
	}
	expense_bill_json_out(array(
		'status' => 0,
		'data' => $record,
	));
}

if ($cmd === 'next_bill_no') {
	expense_bill_json_out(array(
		'status' => 0,
		'bill_entry_no' => expense_bill_preview_next_no($conn),
	));
}

if ($cmd === 'fetch_options') {
	expense_bill_json_out(array(
		'status' => 0,
		'vendors' => expense_gcn_vendor_options($conn),
		'bill_types' => expense_bill_type_options(),
		'tds_percent_options' => expense_bill_tds_percent_options($conn),
		'due_days' => expense_bill_due_days(),
	));
}

if ($cmd === 'vendor_info') {
	$vendor_id = (int) ($_REQUEST['vendor_id'] ?? 0);
	$invoice_date = trim((string) ($_REQUEST['vendor_invoice_date'] ?? ''));
	$info = expense_bill_vendor_info($conn, $vendor_id);
	$due = '';
	if ($invoice_date !== '') {
		$due = expense_bill_calc_due_date($invoice_date, max(0, (int) ($info['credit_days'] ?? 0)));
	}
	expense_bill_json_out(array(
		'status' => 0,
		'vendor' => $info,
		'due_date' => $due,
		'due_days' => expense_bill_due_days(),
	));
}

if ($cmd === 'fetch_gcns') {
	$search = trim((string) ($_REQUEST['search'] ?? ''));
	$rows = expense_bill_fetch_gcns($conn, $search);
	expense_bill_json_out(array(
		'status' => 0,
		'gcns' => $rows,
	));
}

if ($cmd === 'fetch_gcn_details') {
	$keys = $_REQUEST['gcn_keys'] ?? ($_REQUEST['keys'] ?? array());
	$rows = expense_bill_fetch_gcn_details($conn, $keys);
	$tds_percent = expense_bill_money($_REQUEST['tds_percent'] ?? 0);
	$totals = expense_bill_calc_totals_from_gcns($rows, $tds_percent);
	expense_bill_json_out(array(
		'status' => 0,
		'gcns' => $rows,
		'totals' => $totals,
	));
}

if ($cmd === 'calc_due_date') {
	$invoice_date = trim((string) ($_REQUEST['vendor_invoice_date'] ?? ''));
	$due = expense_bill_calc_due_date($invoice_date, expense_bill_vendor_credit_days($conn, (int) ($_REQUEST['vendor_id'] ?? 0)));
	expense_bill_json_out(array(
		'status' => 0,
		'due_date' => $due,
		'due_days' => expense_bill_due_days(),
	));
}

if ($cmd === 'save') {
	$payload = array(
		'bill_id' => (int) ($_REQUEST['bill_id'] ?? 0),
		'bill_date' => $_REQUEST['bill_date'] ?? '',
		'bill_type' => $_REQUEST['bill_type'] ?? '',
		'vendor_id' => $_REQUEST['vendor_id'] ?? 0,
		'gcn_keys' => $_REQUEST['gcn_keys'] ?? ($_REQUEST['gcn_key'] ?? array()),
		'vendor_invoice_no' => $_REQUEST['vendor_invoice_no'] ?? '',
		'vendor_invoice_date' => $_REQUEST['vendor_invoice_date'] ?? '',
		'invoice_received_date' => $_REQUEST['invoice_received_date'] ?? '',
		'due_date' => $_REQUEST['due_date'] ?? '',
		'due_date_overridden' => 0,
		'tds_percent' => $_REQUEST['tds_percent'] ?? 0,
		'gcn_amounts' => $_REQUEST['gcn_amounts'] ?? '',
		'narration' => $_REQUEST['narration'] ?? '',
		'payment_mode' => $_REQUEST['payment_mode'] ?? '',
		'company_bank_id' => $_REQUEST['company_bank_id'] ?? 0,
		'payment_bank_name' => $_REQUEST['payment_bank_name'] ?? '',
		'payment_ifsc' => $_REQUEST['payment_ifsc'] ?? '',
		'payment_bank_branch' => $_REQUEST['payment_bank_branch'] ?? '',
		'remove_attachment_ids' => $_REQUEST['remove_attachment_ids'] ?? array(),
	);
	$files = null;
	if (!empty($_FILES['attachments'])) {
		$files = $_FILES['attachments'];
	}
	$result = expense_bill_save($conn, $payload, (int) ($_SESSION['user_id'] ?? 0), $files);
	if (empty($result['ok'])) {
		expense_bill_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Save failed.'));
	}
	$record = $result['record'] ?? array();
	expense_bill_json_out(array_merge(array(
		'status' => 0,
		'message' => $result['message'],
		'bill_id' => (int) ($result['bill_id'] ?? 0),
		'bill_entry_no' => $result['bill_entry_no'] ?? '',
	), is_array($record) ? $record : array()));
}

if ($cmd === 'delete_attachment') {
	$attachment_id = (int) ($_REQUEST['attachment_id'] ?? 0);
	$bill_id = (int) ($_REQUEST['bill_id'] ?? 0);
	$result = expense_bill_delete_attachment($conn, $attachment_id, $bill_id);
	if (empty($result['ok'])) {
		expense_bill_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Delete failed.'));
	}
	expense_bill_json_out(array('status' => 0, 'message' => $result['message']));
}

if ($cmd === 'delete') {
	$id = (int) ($_REQUEST['bill_id'] ?? 0);
	$result = expense_bill_delete($conn, $id);
	if (empty($result['ok'])) {
		expense_bill_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Delete failed.'));
	}
	expense_bill_json_out(array('status' => 0, 'message' => $result['message']));
}

expense_bill_json_out(array('status' => 1, 'message' => 'Invalid request.'));
