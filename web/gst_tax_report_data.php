<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/gst_tax_report_functions.php');

error_reporting(0);
ini_set('display_errors', 0);
@ini_set('memory_limit', '512M');
@set_time_limit(180);

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

function gst_tax_report_json($payload)
{
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags = $flags | JSON_INVALID_UTF8_SUBSTITUTE;
    }
    echo json_encode($payload, $flags);
    exit;
}

$filters = gst_tax_report_parse_filters($_REQUEST);
if (!empty($filters['error'])) {
    gst_tax_report_json(array('status' => 1, 'message' => $filters['error'], 'data' => array(), 'summary' => gst_tax_report_empty_summary()));
}

$result = gst_tax_report_fetch_rows($conn, $filters);
gst_tax_report_json(array(
    'status' => 0,
    'data' => $result['rows'],
    'summary' => $result['summary'],
));
