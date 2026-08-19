<?php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../web/include/connect.php';
require_once '../../web/include/function.php';
require_once '../../web/include/tracking_functions.php';

// Requires admin session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'Unauthorized. Please login first.'));
    exit;
}

$response = array('status' => 'error', 'message' => 'Invalid request');

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$grn_no = isset($input['grn_no']) ? trim($input['grn_no']) : (isset($_REQUEST['grn_no']) ? trim($_REQUEST['grn_no']) : '');
$tracking_code = isset($input['tracking_code']) ? trim($input['tracking_code']) : (isset($_REQUEST['tracking_code']) ? trim($_REQUEST['tracking_code']) : '');

if ($grn_no !== '' && $tracking_code !== '') {
    $data = get_tracking_data($conn, $grn_no, $tracking_code);

    if ($data) {
        $response['status'] = 'success';
        $response['message'] = 'Consignment found successfully';
        $response['data'] = $data;
    } else {
        $response['message'] = 'No tracking data found for the provided details.';
    }
} else {
    $response['message'] = 'Missing grn_no or tracking_code parameters. Both are required.';
}

echo json_encode($response);
exit();
?>
