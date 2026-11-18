<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/statement_of_account_functions.php');

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

$filters = soa_parse_filters($_REQUEST);
if (!empty($filters['error'])) {
    echo json_encode(array(
        'status' => 1,
        'message' => $filters['error'],
        'data' => soa_empty_result($filters['error']),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$result = soa_fetch($conn, $filters);
if (!empty($result['error'])) {
    echo json_encode(array(
        'status' => 1,
        'message' => $result['error'],
        'data' => $result,
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array(
    'status' => 0,
    'data' => $result,
), JSON_UNESCAPED_UNICODE);
