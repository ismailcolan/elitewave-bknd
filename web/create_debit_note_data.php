<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_note_functions.php');

header('Content-Type: application/json; charset=utf-8');

ensure_billing_tables($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function billing_dn_json($payload)
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($cmd === 'preview_note_no') {
    $note_date = isset($_REQUEST['note_date']) ? trim($_REQUEST['note_date']) : date('d-m-Y');
    billing_dn_json(array(
        'status' => 0,
        'note_no' => billing_preview_debit_note_number($conn, $note_date),
    ));
}

if ($cmd === 'bill_snapshot') {
    $bill_id = (int) ($_REQUEST['bill_id'] ?? 0);
    $exclude = (int) ($_REQUEST['billing_note_id'] ?? 0);
    $snap = billing_debit_note_bill_snapshot($conn, $bill_id, $exclude);
    if (!$snap) {
        billing_dn_json(array('status' => 1, 'message' => 'Select an active vendor bill.'));
    }
    billing_dn_json(array('status' => 0, 'data' => $snap));
}

if ($cmd === 'compute') {
    $bill_id = (int) ($_REQUEST['bill_id'] ?? 0);
    $exclude = (int) ($_REQUEST['billing_note_id'] ?? 0);
    $taxable = (float) ($_REQUEST['taxable_value'] ?? 0);
    $snap = billing_debit_note_bill_snapshot($conn, $bill_id, $exclude);
    if (!$snap) {
        billing_dn_json(array('status' => 1, 'message' => 'Select an active vendor bill.'));
    }
    $profile = array('gst_rate' => $snap['gst_rate'], 'gst_split' => $snap['gst_split']);
    $amounts = billing_note_compute_gst($taxable, $profile);
    $dn_after = (float) $snap['debit_note_total'] + (float) $amounts['grand_total'];
    $to_pay = max(0, (float) $snap['bill_amount'] - $dn_after);
    $gst_this = (float) $amounts['gst_amount'];
    $gst_claimed_after = (float) $snap['gst_claimed_total'] + $gst_this;
    $gst_remaining_after = max(0, round((float) $snap['bill_gst_amount'] - $gst_claimed_after, 2));
    billing_dn_json(array(
        'status' => 0,
        'amounts' => $amounts,
        'to_pay' => billing_format_money($to_pay),
        'debit_note_after' => billing_format_money($dn_after),
        'gst_claim_this' => billing_format_money($gst_this),
        'gst_claimed_after' => billing_format_money($gst_claimed_after),
        'gst_claim_remaining_after' => billing_format_money($gst_remaining_after),
    ));
}

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

if ($cmd === 'view') {
    $billing_note_id = (int) ($_REQUEST['billing_note_id'] ?? 0);
    $view = billing_debit_note_fetch_view($conn, $billing_note_id);
    if (empty($view['ok'])) {
        billing_dn_json(array('status' => 1, 'message' => $view['message'] ?? 'Debit note not found.'));
    }
    unset($view['ok']);
    billing_dn_json(array('status' => 0, 'data' => $view));
}

if ($cmd === 'delete_debit_note') {
    if ($user_id <= 0) {
        billing_dn_json(array('status' => 1, 'message' => 'Session expired. Please login again.'));
    }
    $billing_note_id = (int) ($_POST['billing_note_id'] ?? $_REQUEST['billing_note_id'] ?? 0);
    billing_dn_json(billing_delete_debit_note($conn, $billing_note_id, $user_id));
}

billing_dn_json(array('status' => 1, 'message' => 'Unknown command.'));
