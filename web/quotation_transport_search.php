<?php
/**
 * Lightweight search for train / flight source dropdowns on quotation screens.
 * GET type=train|flight, q=search text, id=exact id (optional), limit (default 10, max 50)
 * Returns {results:[{id,text,hours}]}
 */
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/quotation_multi_mode.php');

header('Content-Type: application/json; charset=utf-8');

$type = strtolower(trim((string) ($_GET['type'] ?? '')));
$q = trim((string) ($_GET['q'] ?? ''));
$limit = (int) ($_GET['limit'] ?? 10);
$limit = $limit < 1 ? 10 : min($limit, 50);
$exact_id = (int) ($_GET['id'] ?? 0);

if ($exact_id > 0) {
	$row = quotation_transport_row_by_id($conn, $type, $exact_id);
	echo json_encode(array('results' => $row ? array($row) : array()));
	exit;
}

echo json_encode(array('results' => quotation_transport_search($conn, $type, $q, $limit)));
