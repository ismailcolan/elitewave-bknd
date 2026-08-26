<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

header('Content-Type: application/json; charset=utf-8');

ensure_billing_tables($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function billing_json_out($payload)
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($cmd === 'preview_invoice_no') {
    $invoice_date = isset($_REQUEST['invoice_date']) ? trim($_REQUEST['invoice_date']) : date('d-m-Y');
    billing_json_out(array(
        'status' => 0,
        'invoice_no' => billing_preview_invoice_number($conn, $invoice_date),
    ));
}

if ($cmd === 'fetch_gcns') {
    $customers = isset($_REQUEST['customers']) ? explode(',', $_REQUEST['customers']) : array();
    $exclude = (int) ($_REQUEST['billing_invoice_id'] ?? 0);
    $billing_type = isset($_REQUEST['billing_type']) ? billing_normalize_billing_type($_REQUEST['billing_type']) : '';
    $rows = billing_fetch_delivered_gcns($conn, $customers, $exclude, $billing_type);
    billing_json_out(array('status' => 0, 'data' => $rows));
}

if ($cmd === 'fetch_gcn_details') {
    $keys = isset($_REQUEST['keys']) ? $_REQUEST['keys'] : array();
    if (!is_array($keys)) {
        $keys = explode(',', (string) $keys);
    }
    $billing_type = isset($_REQUEST['billing_type']) ? trim($_REQUEST['billing_type']) : '';
    $exclude = (int) ($_REQUEST['billing_invoice_id'] ?? 0);
    $lines = array();
    foreach ($keys as $key) {
        $parsed = billing_parse_trans_key($key);
        if (!$parsed) {
            continue;
        }
        $detail = billing_fetch_gcn_detail($conn, $parsed['trans_table'], $parsed['transaction_id'], $billing_type, $exclude);
        if ($detail) {
            $lines[] = $detail;
        }
    }
    billing_json_out(array(
        'status' => 0,
        'lines' => $lines,
        'summary' => billing_sum_lines($lines),
    ));
}

if ($cmd === 'load_draft') {
    $id = (int) ($_REQUEST['billing_invoice_id'] ?? 0);
    $data = billing_get_invoice($conn, $id);
    if (!$data) {
        billing_json_out(array('status' => 1, 'message' => 'Invoice not found.'));
    }
    $lines = array();
    foreach ($data['details'] as $d) {
        $detail = billing_fetch_gcn_detail($conn, $d['trans_table'], $d['transaction_id'], $d['billing_type'], $id);
        if ($detail) {
            $detail['billing_type'] = $d['billing_type'] ?: $detail['billing_type'];
            $lines[] = $detail;
        }
    }
    billing_json_out(array(
        'status' => 0,
        'master' => $data['master'],
        'lines' => $lines,
        'summary' => billing_sum_lines($lines),
    ));
}

billing_json_out(array('status' => 1, 'message' => 'Invalid request.'));
