<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_gcn_helpers.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('status' => 1, 'message' => empty($_SESSION['user_id']) ? 'Session expired.' : 'Access denied.'));
	exit;
}

header('Content-Type: application/json; charset=utf-8');

expense_gcn_ensure_schema($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function expense_gcn_json_out($payload)
{
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

if ($cmd === 'fetch_gcns') {
	$search = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
	$rows = expense_gcn_fetch_gcns($conn, $search);
	expense_gcn_json_out(array('status' => 0, 'data' => $rows));
}

if ($cmd === 'fetch_context') {
	$mode = trim((string) ($_REQUEST['expense_mode'] ?? 'single'));
	$group_id = (int) ($_REQUEST['group_id'] ?? 0);

	if ($group_id > 0) {
		$saved = expense_gcn_load_saved_group($conn, $group_id);
		if (!$saved) {
			expense_gcn_json_out(array('status' => 1, 'message' => 'Group expense record not found.'));
		}
		expense_gcn_json_out(array(
			'status' => 0,
			'expense_mode' => 'group',
			'gcn_expense_id' => $group_id,
			'context' => $saved['context'],
			'gcn_keys' => $saved['context']['gcn_keys'] ?? array(),
			'lines' => $saved['lines'],
		));
	}

	if ($mode === 'group') {
		$keys_raw = $_REQUEST['gcn_keys'] ?? array();
		if (is_string($keys_raw)) {
			$decoded = json_decode($keys_raw, true);
			$keys_raw = is_array($decoded) ? $decoded : $keys_raw;
		}
		$context = expense_gcn_fetch_group_context($conn, $keys_raw);
		if (empty($context['ok'])) {
			expense_gcn_json_out(array('status' => 1, 'message' => $context['message'] ?? 'Could not load group GCNs.'));
		}
		expense_gcn_json_out(array(
			'status' => 0,
			'expense_mode' => 'group',
			'gcn_expense_id' => 0,
			'context' => $context,
			'gcn_keys' => $context['gcn_keys'] ?? array(),
			'lines' => array(),
		));
	}

	$key = trim((string) ($_REQUEST['gcn_key'] ?? ''));
	$parsed = billing_parse_trans_key($key);
	if (!$parsed) {
		expense_gcn_json_out(array('status' => 1, 'message' => 'Invalid GCN reference.'));
	}
	$context = expense_gcn_fetch_context($conn, $parsed['trans_table'], $parsed['transaction_id']);
	if (empty($context['ok'])) {
		expense_gcn_json_out(array('status' => 1, 'message' => $context['message'] ?? 'GCN not found.'));
	}
	$saved = expense_gcn_load_saved($conn, $parsed['trans_table'], $parsed['transaction_id']);
	expense_gcn_json_out(array(
		'status' => 0,
		'expense_mode' => 'single',
		'gcn_expense_id' => $saved ? (int) ($saved['header']['gcn_expense_id'] ?? 0) : 0,
		'context' => $context,
		'lines' => $saved ? $saved['lines'] : array(),
	));
}

if ($cmd === 'fetch_options') {
	expense_gcn_json_out(array(
		'status' => 0,
		'vendors' => expense_gcn_vendor_options($conn),
		'categories' => expense_gcn_category_options($conn),
		'expense_types' => expense_gcn_type_options($conn),
	));
}

if ($cmd === 'save') {
	$lines_raw = isset($_POST['lines']) ? $_POST['lines'] : (isset($_REQUEST['lines']) ? $_REQUEST['lines'] : array());
	if (is_string($lines_raw)) {
		$decoded = json_decode($lines_raw, true);
		$lines_raw = is_array($decoded) ? $decoded : array();
	}

	$gcn_keys_raw = $_REQUEST['gcn_keys'] ?? array();
	if (is_string($gcn_keys_raw)) {
		$decoded_keys = json_decode($gcn_keys_raw, true);
		$gcn_keys_raw = is_array($decoded_keys) ? $decoded_keys : $gcn_keys_raw;
	}

	$payload = array(
		'expense_mode' => $_REQUEST['expense_mode'] ?? 'single',
		'gcn_key' => $_REQUEST['gcn_key'] ?? '',
		'gcn_keys' => $gcn_keys_raw,
		'gcn_expense_id' => (int) ($_REQUEST['gcn_expense_id'] ?? 0),
		'lines' => $lines_raw,
	);

	$result = expense_gcn_save($conn, $payload, (int) ($_SESSION['user_id'] ?? 0));
	if (empty($result['ok'])) {
		expense_gcn_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Save failed.'));
	}
	expense_gcn_json_out(array(
		'status' => 0,
		'message' => $result['message'],
		'gcn_expense_id' => (int) ($result['gcn_expense_id'] ?? 0),
		'revenue_without_gst' => $result['revenue_without_gst'],
		'expenses_without_gst' => $result['expenses_without_gst'],
		'profit_amount' => $result['profit_amount'],
		'profit_raw' => $result['profit_raw'],
	));
}

if ($cmd === 'delete') {
	$gcn_expense_id = (int) ($_REQUEST['gcn_expense_id'] ?? 0);
	$result = expense_gcn_delete($conn, $gcn_expense_id);
	if (empty($result['ok'])) {
		expense_gcn_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Delete failed.'));
	}
	expense_gcn_json_out(array('status' => 0, 'message' => $result['message']));
}

expense_gcn_json_out(array('status' => 1, 'message' => 'Invalid request.'));
