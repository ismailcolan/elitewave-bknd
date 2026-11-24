<?php

error_reporting(0);
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'message' => 'POST required.'));
    exit;
}

require_once __DIR__ . '/../../include/connect.php';
require_once __DIR__ . '/../../include/quotation_functions.php';

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
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Invalid quote request.'));
    exit;
}

$type = trim((string) ($input['quote_type'] ?? ''));
$allowed = array('door_to_door', 'consignee_consignor_all_modes', 'consignor_multi_destination');
if (!in_array($type, $allowed, true)) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Choose a quotation type.'));
    exit;
}

$customer_mode = trim((string) ($input['customer_mode'] ?? 'new'));
if ($customer_mode !== 'existing') {
    $customer_mode = 'new';
}
$party_id = (int) ($input['party_id'] ?? 0);
if ($customer_mode === 'existing' && $party_id <= 0) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Please select an existing customer.'));
    exit;
}

$payload = array(
    'quote_date' => date('d-m-Y'),
    'quote_type' => $type,
    'customer_mode' => $customer_mode,
    'party_id' => $customer_mode === 'existing' ? $party_id : 0,
    'party_name' => trim((string) ($input['party_name'] ?? '')),
    'attn_name' => trim((string) ($input['attn_name'] ?? '')),
    'party_email' => trim((string) ($input['party_email'] ?? '')),
    'party_mobile' => trim((string) ($input['party_mobile'] ?? '')),
    'gst_rate' => 18,
    'payment_terms' => '30_days',
    'subject' => 'Quotation request from EliteWave360 Assistant',
);

if ($type === 'door_to_door') {
    $payload['origin_city_id'] = (int) ($input['origin_city_id'] ?? 0);
    $payload['destination_city_id'] = (int) ($input['destination_city_id'] ?? 0);
    $payload['destination_name'] = trim((string) ($input['destination_name'] ?? ''));
    $payload['mode_of_transportation'] = (int) ($input['mode_id'] ?? 0);
    $payload['charge_label'] = array('Freight');
    $payload['charge_amount'] = array((string) ($input['freight'] ?? ''));
    $payload['charge_taxable'] = array('1');
} elseif ($type === 'consignee_consignor_all_modes') {
    $payload['destination_name'] = trim((string) ($input['destination_name'] ?? ''));
    $rows = isset($input['rows']) && is_array($input['rows']) ? $input['rows'] : array();
    $payload['mm_mode_id'] = array();
    $payload['mm_delivery_days'] = array();
    $payload['mm_weight'] = array();
    $payload['mm_freight'] = array();
    $payload['mm_tempo'] = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $payload['mm_mode_id'][] = (int) ($row['mode_id'] ?? 0);
        $payload['mm_delivery_days'][] = trim((string) ($row['delivery_days'] ?? ''));
        $payload['mm_weight'][] = trim((string) ($row['weight'] ?? ''));
        $payload['mm_freight'][] = (string) ($row['freight'] ?? '');
        $payload['mm_tempo'][] = (string) ($row['tempo'] ?? '');
    }
} else {
    $rows = isset($input['rows']) && is_array($input['rows']) ? $input['rows'] : array();
    $payload['md_origin_city_id'] = array();
    $payload['md_city_id'] = array();
    $payload['md_mode_id'] = array();
    $payload['md_delivery_days'] = array();
    $payload['md_weight'] = array();
    $payload['md_freight'] = array();
    $payload['md_tempo'] = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $payload['md_origin_city_id'][] = (int) ($row['origin_city_id'] ?? 0);
        $payload['md_city_id'][] = (int) ($row['destination_city_id'] ?? 0);
        $payload['md_mode_id'][] = (int) ($row['mode_id'] ?? 0);
        $payload['md_delivery_days'][] = trim((string) ($row['delivery_days'] ?? ''));
        $payload['md_weight'][] = trim((string) ($row['weight'] ?? ''));
        $payload['md_freight'][] = (string) ($row['freight'] ?? '');
        $payload['md_tempo'][] = (string) ($row['tempo'] ?? '');
    }
}

$result = quotation_save($conn, $payload, 0, 'save_draft');
if (empty($result['ok'])) {
    http_response_code(422);
    echo json_encode(array(
        'ok' => false,
        'message' => $result['message'] ?? 'The quote could not be saved.',
    ));
    exit;
}

echo json_encode(array(
    'ok' => true,
    'quote_no' => $result['quote_no'] ?? '',
    'quotation_id' => (int) ($result['quotation_id'] ?? 0),
    'message' => 'Quote draft saved.',
));
exit;
