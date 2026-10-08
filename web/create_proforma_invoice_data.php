<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_proforma_functions.php');

header('Content-Type: application/json; charset=utf-8');

ensure_billing_proforma_tables($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function billing_proforma_json_out($payload)
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($cmd === 'preview_proforma_no') {
    $invoice_date = isset($_REQUEST['invoice_date']) ? trim($_REQUEST['invoice_date']) : date('d-m-Y');
    billing_proforma_json_out(array(
        'status' => 0,
        'proforma_no' => billing_proforma_preview_number($conn, $invoice_date),
    ));
}

if ($cmd === 'fetch_gcns') {
    $customers = isset($_REQUEST['customers']) ? explode(',', $_REQUEST['customers']) : array();
    $exclude = (int) ($_REQUEST['billing_proforma_id'] ?? 0);
    $billing_type = isset($_REQUEST['billing_type']) ? billing_normalize_billing_type($_REQUEST['billing_type']) : '';
    $rows = billing_proforma_fetch_delivered_gcns($conn, $customers, $exclude, $billing_type);
    billing_proforma_json_out(array('status' => 0, 'data' => $rows));
}

if ($cmd === 'fetch_gcn_details') {
    $keys = isset($_REQUEST['keys']) ? $_REQUEST['keys'] : array();
    if (!is_array($keys)) {
        $keys = explode(',', (string) $keys);
    }
    $billing_type = isset($_REQUEST['billing_type']) ? trim($_REQUEST['billing_type']) : '';
    $exclude = (int) ($_REQUEST['billing_proforma_id'] ?? 0);
    $customer_id = (int) ($_REQUEST['customer_id'] ?? 0);
    $lines = array();
    $skipped = array();
    foreach ($keys as $key) {
        $parsed = billing_parse_trans_key($key);
        if (!$parsed) {
            $skipped[] = 'Invalid GCN reference.';
            continue;
        }
        $validated = billing_proforma_validate_gcn(
            $conn,
            $parsed['trans_table'],
            $parsed['transaction_id'],
            $customer_id,
            $billing_type,
            $exclude
        );
        if (!empty($validated['ok'])) {
            $lines[] = $validated['detail'];
            continue;
        }
        if ($exclude > 0 && billing_proforma_gcn_on_current($conn, $parsed['trans_table'], $parsed['transaction_id'], $exclude)) {
            $stored = billing_proforma_get_stored_detail_row($conn, $exclude, $parsed['trans_table'], $parsed['transaction_id']);
            if ($stored) {
                $line = billing_proforma_line_from_detail_row($conn, $stored, $exclude);
                if ($line) {
                    $lines[] = $line;
                    continue;
                }
            }
        }
        $skipped[] = $validated['message'] ?? 'GCN not available.';
    }
    billing_proforma_json_out(array(
        'status' => 0,
        'lines' => $lines,
        'summary' => billing_sum_lines($lines),
        'skipped' => $skipped,
    ));
}

if ($cmd === 'load_draft') {
    $id = (int) ($_REQUEST['billing_proforma_id'] ?? 0);
    $data = billing_proforma_get($conn, $id);
    if (!$data) {
        billing_proforma_json_out(array('status' => 1, 'message' => 'Proforma not found.'));
    }
    $lines = array();
    foreach ($data['details'] as $d) {
        $detail = billing_proforma_line_from_detail_row($conn, $d, $id);
        if ($detail) {
            $lines[] = $detail;
        }
    }
    billing_proforma_json_out(array(
        'status' => 0,
        'master' => $data['master'],
        'lines' => $lines,
        'summary' => billing_sum_lines($lines),
    ));
}

billing_proforma_json_out(array('status' => 1, 'message' => 'Invalid request.'));
