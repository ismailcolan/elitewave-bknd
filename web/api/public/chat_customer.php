<?php

error_reporting(0);
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../include/connect.php';
require_once __DIR__ . '/../../include/encryption.php';

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

$digits = preg_replace('/\D+/', '', (string) ($_GET['mobile'] ?? ''));
if (strlen($digits) !== 10) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Enter a valid 10-digit mobile number.'));
    exit;
}

$mobile = mysqli_real_escape_string($conn, $digits);
$sql = "SELECT client_id, client_company_name, contact_person, email
    FROM client
    WHERE status = 0
      AND (contact_no = '$mobile' OR contact_no1 = '$mobile')
    ORDER BY client_id DESC
    LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(array(
        'ok' => true,
        'found' => false,
    ));
    exit;
}

$row = mysqli_fetch_assoc($result);
$company = function_exists('ew_client_decrypt_name')
    ? trim(ew_client_decrypt_name($row['client_company_name'] ?? ''))
    : trim((string) ($row['client_company_name'] ?? ''));

echo json_encode(array(
    'ok' => true,
    'found' => true,
    'client_id' => (int) ($row['client_id'] ?? 0),
    'party_name' => $company,
    'contact_person' => trim((string) ($row['contact_person'] ?? '')),
    'email' => trim((string) ($row['email'] ?? '')),
));
exit;