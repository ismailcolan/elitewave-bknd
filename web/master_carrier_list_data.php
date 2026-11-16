<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/master_carrier_list_helpers.php');

header('Content-Type: application/json; charset=utf-8');

$entity = isset($_GET['entity']) ? strtolower(trim((string) $_GET['entity'])) : '';
if ($entity !== 'flight' && $entity !== 'train') {
	echo json_encode(array(
		'sEcho' => (int) ($_GET['sEcho'] ?? 0),
		'iTotalRecords' => 0,
		'iTotalDisplayRecords' => 0,
		'aaData' => array(),
		'sError' => 'Invalid entity',
	));
	exit;
}

echo json_encode(ew_master_carrier_list_datatable($conn, $entity));
