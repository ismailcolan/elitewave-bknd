<?php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../web/include/connect.php';
require_once '../../web/include/function.php';
require_once '../../web/include/tracking_functions.php';

$response = array(
    'status'  => 'error',
    'message' => 'Invalid request',
);

// Read JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Fallback to POST/GET if not JSON
if (!$input) {
    $input = $_REQUEST;
}

$tracking_code = isset($input['tracking_code']) ? trim($input['tracking_code']) : '';
$grn_no = isset($input['grn_no']) ? trim($input['grn_no']) : '';

// Allow tracking-only lookup: find grn_no from transaction_log
if ($tracking_code !== '' && $grn_no === '') {
    $lookup = mysqli_query($conn, "SELECT grn_no FROM transaction_log WHERE tracking_code='$tracking_code' LIMIT 1");
    if ($lookup && mysqli_num_rows($lookup) > 0) {
        $grn_no = mysqli_fetch_assoc($lookup)['grn_no'];
    }
}

if ($grn_no !== '' && $tracking_code !== '') {
    $data = get_tracking_data($conn, $grn_no, $tracking_code);

    if ($data) {
        $response['status'] = 'success';
        $response['message'] = 'Tracking found';
        $response['data'] = $data;
    } else {
        $response['message'] = 'No tracking data found for the provided details.';
    }
} else {
    $response['message'] = 'Please provide tracking_code (and grn_no if required).';
}

echo json_encode($response);
exit();
