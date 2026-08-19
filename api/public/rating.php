<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../web/include/connect.php';

$table_name = 'feedback_ratings';
$create_query = "CREATE TABLE IF NOT EXISTS `$table_name` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tracking_code` VARCHAR(255) NOT NULL,
    `rating` TINYINT(1) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tracking_code` (`tracking_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $create_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $tracking_code = isset($input['tracking_code']) ? trim($input['tracking_code']) : '';
    $rating = isset($input['rating']) ? (int) $input['rating'] : 0;

    if (empty($tracking_code)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tracking code is required.']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1 and 5.']);
        exit;
    }

    $check = mysqli_query($conn, "SELECT id FROM `$table_name` WHERE tracking_code='$tracking_code'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE `$table_name` SET rating='$rating' WHERE tracking_code='$tracking_code'");
    } else {
        mysqli_query($conn, "INSERT INTO `$table_name` (tracking_code, rating) VALUES ('$tracking_code', '$rating')");
    }

    echo json_encode(['status' => 'success', 'message' => 'Rating submitted successfully.']);
    exit;
}

$tracking_code = isset($_GET['tracking_code']) ? trim($_GET['tracking_code']) : '';
if (empty($tracking_code)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tracking code is required.']);
    exit;
}

$result = mysqli_query($conn, "SELECT rating FROM `$table_name` WHERE tracking_code='$tracking_code' LIMIT 1");
$row = mysqli_fetch_assoc($result);

if ($row) {
    echo json_encode(['status' => 'success', 'rating' => (int) $row['rating']]);
} else {
    echo json_encode(['status' => 'success', 'rating' => null]);
}
