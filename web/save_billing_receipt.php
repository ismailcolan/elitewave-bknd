<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

header('Content-Type: application/json; charset=utf-8');

ensure_billing_tables($conn);

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(array('status' => 1, 'message' => 'Session expired. Please login again.'));
    exit;
}

$lines = array();
if (!empty($_POST['lines'])) {
    $decoded = json_decode($_POST['lines'], true);
    if (is_array($decoded)) {
        $lines = $decoded;
    }
}

$payload = array(
    'receipt_type' => trim($_POST['receipt_type'] ?? 'against_invoice'),
    'receipt_date' => trim($_POST['receipt_date'] ?? date('d-m-Y')),
    'party_id' => (int) ($_POST['party_id'] ?? 0),
    'payer_name' => trim($_POST['payer_name'] ?? ''),
    'total_amount' => $_POST['total_amount'] ?? 0,
    'payment_mode' => trim($_POST['payment_mode'] ?? ''),
    'payment_ref' => trim($_POST['payment_ref'] ?? ''),
    'payment_ref_date' => trim($_POST['payment_ref_date'] ?? ''),
    'bank_account_id' => (int) ($_POST['bank_account_id'] ?? 0),
    'remarks' => trim($_POST['remarks'] ?? ''),
    'lines' => $lines,
);

$files = null;
if (!empty($_FILES['attachments'])) {
    $files = $_FILES['attachments'];
}

echo json_encode(billing_save_receipt($conn, $payload, $user_id, $files), JSON_UNESCAPED_UNICODE);
