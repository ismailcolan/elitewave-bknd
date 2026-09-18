<?php
require_once ('include/connect.php');
require_once ('include/function.php');
require_once ('include/dashboard_ops_data.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], array('AD', 'USER'), true)) {
	echo json_encode(array('status' => 1, 'message' => empty($_SESSION['user_id']) ? 'Session expired.' : 'Access denied.'));
	exit;
}

$year = isset($_REQUEST['year']) ? (int) $_REQUEST['year'] : (int) date('Y');
echo json_encode(dashboard_ops_year_response($conn, $year), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
