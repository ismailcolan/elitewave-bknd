<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/bill_wise_functions.php');

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

$filters = pending_payments_parse_filters($_REQUEST);
if (!empty($filters['error'])) {
    echo json_encode(array(
        'status' => 1,
        'message' => $filters['error'],
        'customers' => array(),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = bill_wise_fetch($conn, $filters);
if (!empty($payload['error'])) {
    echo json_encode(array(
        'status' => 1,
        'message' => $payload['error'],
        'customers' => array(),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array_merge(array('status' => 0), $payload), JSON_UNESCAPED_UNICODE);