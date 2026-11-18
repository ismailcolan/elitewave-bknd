<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/credit_note_report_functions.php');

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

$filters = credit_note_report_parse_filters($_REQUEST);
if (!empty($filters['error'])) {
    echo json_encode(array(
        'status' => 1,
        'message' => $filters['error'],
        'data' => array(),
        'summary' => credit_note_report_empty_summary(),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$result = credit_note_report_fetch_rows($conn, $filters);
echo json_encode(array(
    'status' => 0,
    'data' => $result['rows'],
    'summary' => $result['summary'],
), JSON_UNESCAPED_UNICODE);
