<?php

error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../../include/connect.php';
require_once __DIR__ . '/../../include/tracking_functions.php';

$expected = getenv('CHAT_INTERNAL_TOKEN');
if ($expected === false || $expected === '') {
    $expected = 'ew360-chat-internal-7f3a9c2e';
}
$given = $_SERVER['HTTP_X_CHAT_TOKEN'] ?? '';
if (!is_string($given) || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Unauthorized.'));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_REQUEST;
}

$mobile = ew_chatbot_normalize_mobile($input['mobile'] ?? '');
$tracking = ew_chatbot_normalize_mobile($input['tracking_code'] ?? '');

if (strlen($mobile) !== 10) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Enter a valid 10-digit mobile number.'));
    exit;
}

if ($tracking === '') {
    if (!ew_chatbot_customer_exists($conn, $mobile)) {
        echo json_encode(array(
            'ok' => true,
            'verified' => false,
            'message' => 'No customer found for this mobile number.',
        ));
        exit;
    }
    echo json_encode(array(
        'ok' => true,
        'verified' => true,
        'message' => 'Mobile number verified.',
    ));
    exit;
}

if (strlen($tracking) !== 12) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Tracking number must be 12 digits.'));
    exit;
}

if (!ew_chatbot_customer_exists($conn, $mobile)) {
    echo json_encode(array(
        'ok' => true,
        'authorized' => false,
        'reason' => 'customer',
        'message' => 'No customer found for this mobile number.',
    ));
    exit;
}

$authorized = ew_tracking_mobile_can_view($conn, $tracking, $mobile);
echo json_encode(array(
    'ok' => true,
    'authorized' => $authorized,
    'message' => $authorized
        ? 'Shipment authorized.'
        : 'This consignment does not belong to the verified mobile number.',
));
exit;