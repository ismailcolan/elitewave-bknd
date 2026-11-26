<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_gcn_pl_helpers.php');

expense_require_admin();

header('Content-Type: application/json; charset=utf-8');

$cmd = isset($_REQUEST['cmd']) ? trim((string) $_REQUEST['cmd']) : '';
if ($cmd !== 'detail') {
	echo json_encode(array('ok' => false, 'message' => 'Unknown command.'));
	exit;
}

$gcn_key = isset($_REQUEST['gcn_key']) ? trim((string) $_REQUEST['gcn_key']) : '';
if ($gcn_key === '') {
	echo json_encode(array('ok' => false, 'message' => 'GCN key required.'));
	exit;
}

$data = expense_gcn_pl_detail($conn, $gcn_key);
if (!$data) {
	echo json_encode(array('ok' => false, 'message' => 'GCN not found or could not calculate P&L.'));
	exit;
}

echo json_encode(array('ok' => true, 'data' => $data));