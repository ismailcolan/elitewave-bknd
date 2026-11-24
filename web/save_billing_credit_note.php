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

$result = billing_save_credit_note($conn, $payload, $user_id);
$send_mail = !empty($_POST['send_client_email']);
$note_status = isset($result['note_status']) ? $result['note_status'] : '';
if ($send_mail && !empty($result['status']) && $result['status'] === 0 && $note_status === 'final' && !empty($result['billing_note_id'])) {
    require_once __DIR__ . '/include/credit_note_email.php';
    $mail = credit_note_send_client_email($conn, (int) $result['billing_note_id'], $user_id);
    if (!empty($mail['ok'])) {
        $result['message'] = trim((string) ($result['message'] ?? '')) . ' ' . $mail['message'];
        $result['email_sent'] = 1;
        $result['email_sent_to'] = $mail['sent_to'] ?? '';
    } else {
        $result['email_error'] = $mail['message'] ?? 'Email could not be sent.';
        $result['message'] = trim((string) ($result['message'] ?? '')) . ' ' . ($mail['message'] ?? 'Email could not be sent.');
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
