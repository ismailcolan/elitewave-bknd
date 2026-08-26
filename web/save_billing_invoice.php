<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/tax_invoice_pdf_builder.php');

header('Content-Type: application/json; charset=utf-8');

ensure_billing_tables($conn);

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(array('status' => 1, 'message' => 'Session expired. Please login again.'));
    exit;
}

$payload = array(
    'billing_invoice_id' => (int) ($_POST['billing_invoice_id'] ?? 0),
    'invoice_date' => trim($_POST['invoice_date'] ?? date('d-m-Y')),
    'customer_id' => (int) ($_POST['customer_id'] ?? 0),
    'billing_type' => trim($_POST['billing_type'] ?? ''),
    'status' => trim($_POST['status'] ?? 'draft'),
    'lines' => isset($_POST['lines']) ? json_decode($_POST['lines'], true) : array(),
);

if (!is_array($payload['lines'])) {
    $payload['lines'] = array();
}

$result = billing_save_invoice($conn, $payload, $user_id);

if ($result['status'] === 0 && ($payload['status'] === 'final' || $result['invoice_status'] === 'final')) {
    tax_invoice_save_pdf($conn, $result['billing_invoice_id']);
    $result['pdf_url'] = 'tax_invoice_pdf.php?id=' . (int) $result['billing_invoice_id'];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
