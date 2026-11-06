<?php
require_once ('include/connect.php');
require_once ('include/function.php');
require_once ('include/dashboard_tracking_data.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], array('AD', 'USER'), true)) {
	echo json_encode(array(
		'status' => 1,
		'message' => empty($_SESSION['user_id']) ? 'Session expired.' : 'Access denied.',
	));
	exit;
}

$year = isset($_REQUEST['year']) ? (int) $_REQUEST['year'] : (int) date('Y');
$period = isset($_REQUEST['period']) ? trim((string) $_REQUEST['period']) : 'monthly';
if ($period !== 'weekly' && $period !== 'monthly') {
	$period = 'monthly';
}

echo json_encode(
	dashboard_tracking_response($conn, $year, $period),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
