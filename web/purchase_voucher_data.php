<?php
require_once('include/connect.php');
if (!defined('EW_PURCHASE_VOUCHER_LIVE') || !EW_PURCHASE_VOUCHER_LIVE) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('status' => 1, 'message' => 'Purchase voucher is not available.'));
	exit;
}
require_once('include/function.php');
require_once('include/purchase_voucher_helpers.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('status' => 1, 'message' => empty($_SESSION['user_id']) ? 'Session expired.' : 'Access denied.'));
	exit;
}

header('Content-Type: application/json; charset=utf-8');
purchase_voucher_ensure_schema($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function purchase_voucher_json_out($payload)
{
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

if ($cmd === 'fetch') {
	$id = (int) ($_REQUEST['purchase_voucher_id'] ?? 0);
	$record = purchase_voucher_load($conn, $id);
	if (!$record) {
		purchase_voucher_json_out(array('status' => 1, 'message' => 'Purchase voucher not found.'));
	}
	purchase_voucher_json_out(array_merge(array('status' => 0), $record));
}

if ($cmd === 'view') {
	$id = (int) ($_REQUEST['purchase_voucher_id'] ?? 0);
	$record = purchase_voucher_view_data($conn, $id);
	if (!$record) {
		purchase_voucher_json_out(array('status' => 1, 'message' => 'Purchase voucher not found.'));
	}
	purchase_voucher_json_out(array(
		'status' => 0,
		'data' => $record,
	));
}

if ($cmd === 'next_voucher_no') {
	purchase_voucher_json_out(array(
		'status' => 0,
		'voucher_no' => purchase_voucher_preview_next_no($conn),
	));
}

if ($cmd === 'save') {
	$payload = array(
		'purchase_voucher_id' => (int) ($_REQUEST['purchase_voucher_id'] ?? 0),
		'voucher_date' => $_REQUEST['voucher_date'] ?? '',
		'vendor_id' => $_REQUEST['vendor_id'] ?? 0,
		'supplier_invoice_no' => $_REQUEST['supplier_invoice_no'] ?? '',
		'supplier_invoice_date' => $_REQUEST['supplier_invoice_date'] ?? '',
		'invoice_received_date' => $_REQUEST['invoice_received_date'] ?? '',
		'narration' => $_REQUEST['narration'] ?? '',
		'tds_percent' => $_REQUEST['tds_percent'] ?? 0,
		'other_deduction' => $_REQUEST['other_deduction'] ?? 0,
		'payment_mode' => $_REQUEST['payment_mode'] ?? '',
		'company_bank_id' => $_REQUEST['company_bank_id'] ?? 0,
		'payment_bank_name' => $_REQUEST['payment_bank_name'] ?? '',
		'payment_ifsc' => $_REQUEST['payment_ifsc'] ?? '',
		'payment_bank_branch' => $_REQUEST['payment_bank_branch'] ?? '',
		'lines_json' => $_REQUEST['lines_json'] ?? '',
		'remove_attachment_ids' => $_REQUEST['remove_attachment_ids'] ?? array(),
	);
	$files = null;
	if (!empty($_FILES['attachments'])) {
		$files = $_FILES['attachments'];
	}
	$result = purchase_voucher_save($conn, $payload, (int) ($_SESSION['user_id'] ?? 0), $files);
	if (empty($result['ok'])) {
		purchase_voucher_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Save failed.'));
	}
	$record = $result['record'] ?? array();
	purchase_voucher_json_out(array_merge(array(
		'status' => 0,
		'message' => $result['message'],
		'purchase_voucher_id' => (int) ($result['purchase_voucher_id'] ?? 0),
		'voucher_no' => $result['voucher_no'] ?? '',
	), is_array($record) ? $record : array()));
}

if ($cmd === 'delete_attachment') {
	$attachment_id = (int) ($_REQUEST['attachment_id'] ?? 0);
	$pv_id = (int) ($_REQUEST['purchase_voucher_id'] ?? 0);
	$result = purchase_voucher_delete_attachment($conn, $attachment_id, $pv_id);
	if (empty($result['ok'])) {
		purchase_voucher_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Delete failed.'));
	}
	purchase_voucher_json_out(array('status' => 0, 'message' => $result['message']));
}

if ($cmd === 'delete') {
	$id = (int) ($_REQUEST['purchase_voucher_id'] ?? 0);
	$result = purchase_voucher_delete($conn, $id);
	if (empty($result['ok'])) {
		purchase_voucher_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Delete failed.'));
	}
	purchase_voucher_json_out(array('status' => 0, 'message' => $result['message']));
}

purchase_voucher_json_out(array('status' => 1, 'message' => 'Invalid request.'));