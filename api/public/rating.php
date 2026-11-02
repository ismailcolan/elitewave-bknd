<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../web/include/shipment_rating_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $tracking_code = isset($input['tracking_code']) ? $input['tracking_code'] : '';
    $rating = isset($input['rating']) ? (int) $input['rating'] : 0;

    $normalized = ew_shipment_rating_normalize_code($tracking_code);
    if ($normalized === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tracking code is required.']);
        exit;
    }

    if ($rating < 1 || $rating > 10) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1 and 10.']);
        exit;
    }

    if (!ew_shipment_rating_save($tracking_code, $rating)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not save rating.']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Rating submitted successfully.', 'rating' => $rating]);
    exit;
}

$tracking_code = isset($_GET['tracking_code']) ? $_GET['tracking_code'] : '';
$normalized = ew_shipment_rating_normalize_code($tracking_code);
if ($normalized === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tracking code is required.']);
    exit;
}

$rating = ew_shipment_rating_get($tracking_code);
echo json_encode(['status' => 'success', 'rating' => $rating]);
