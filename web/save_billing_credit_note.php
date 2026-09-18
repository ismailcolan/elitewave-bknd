<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_note_functions.php');

header('Content-Type: application/json; charset=utf-8');

ensure_billing_tables($conn);

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(array('status' => 1, 'message' => 'Session expired. Please login again.'));
    exit;
}

$payload = array(
    'billing_note_id' => (int) ($_POST['billing_note_id'] ?? 0),
    'against_invoice_id' => (int) ($_POST['against_invoice_id'] ?? 0),
    'note_date' => trim($_POST['note_date'] ?? date('d-m-Y')),
    'reason' => trim($_POST['reason'] ?? ''),
    'line_description' => trim($_POST['line_description'] ?? ''),
    'taxable_value' => $_POST['taxable_value'] ?? 0,
    'status' => trim($_POST['status'] ?? 'draft'),
);

echo json_encode(billing_save_credit_note($conn, $payload, $user_id), JSON_UNESCAPED_UNICODE);
