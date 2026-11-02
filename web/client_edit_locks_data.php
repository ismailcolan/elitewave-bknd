<?php
require_once('include/connect.php');
require_once('include/ew_master_usage_helpers.php');

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) && empty($_SESSION['admin_id'])) {
	echo json_encode(array('status' => 1, 'message' => 'Unauthorized', 'locked' => array()));
	exit;
}

$ids = array();
$q = mysqli_query($conn, 'SELECT client_id FROM client');
if ($q) {
	while ($row = mysqli_fetch_assoc($q)) {
		$ids[] = (int) $row['client_id'];
	}
}

$map = ew_master_usage_map($conn, 'client', $ids);
$locked = array();
foreach ($map as $id => $in_use) {
	if ($in_use) {
		$locked[] = (int) $id;
	}
}

echo json_encode(array(
	'status' => 0,
	'locked' => $locked,
));
