<?php

error_reporting(0);
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
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

$q = trim((string) ($_GET['q'] ?? ''));
if (function_exists('mb_substr')) {
    $q = mb_substr($q, 0, 40);
} else {
    $q = substr($q, 0, 40);
}

$modes = array();
foreach (quotation_mode_transport_catalog($conn) as $mode) {
    $name = trim((string) ($mode['mode_type'] ?? ''));
    if ($name === '') {
        continue;
    }
    $modes[] = array(
        'id' => (int) $mode['mode_id'],
        'name' => $name,
    );
}

$days = array();
foreach (quotation_delivery_days_options() as $code => $label) {
    $days[] = array('code' => $code, 'label' => $label);
}

$cities = array();
if (strlen($q) >= 2) {
    $like = '%' . mysqli_real_escape_string($conn, $q) . '%';
    $sql = "SELECT c.city_id, c.city_name, s.state_name
        FROM city c
        LEFT JOIN state s ON s.state_id = c.state
        WHERE c.status=0 AND c.city_name LIKE '$like'
        ORDER BY c.city_name ASC
        LIMIT 8";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $city = trim((string) ($row['city_name'] ?? ''));
            $state = trim((string) ($row['state_name'] ?? ''));
            $cities[] = array(
                'id' => (int) $row['city_id'],
                'name' => $state !== '' ? ($city . ' — ' . $state) : $city,
            );
        }
    }
}

echo json_encode(array(
    'ok' => true,
    'modes' => $modes,
    'delivery_days' => $days,
    'cities' => $cities,
));
exit;
