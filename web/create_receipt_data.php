<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

header('Content-Type: application/json; charset=utf-8');

ensure_billing_tables($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function billing_receipt_json($payload)
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($cmd === 'preview_receipt_no') {
    billing_receipt_json(array(
        'status' => 0,
        'receipt_no' => billing_preview_receipt_number($conn),
    ));
}

if ($cmd === 'fetch_clients') {
    $rows = array();
    $q = mysqli_query($conn, "SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $rows[] = array(
                'client_id' => (int) $row['client_id'],
                'name' => $row['client_company_name'],
            );
        }
    }
    billing_receipt_json(array('status' => 0, 'data' => $rows));
}

if ($cmd === 'fetch_invoices') {
    $customer_id = (int) ($_REQUEST['customer_id'] ?? 0);
    if ($customer_id <= 0) {
        billing_receipt_json(array('status' => 0, 'data' => array()));
    }
    $rows = array();
    $q = mysqli_query($conn, "SELECT billing_invoice_id, invoice_no, invoice_date, grand_total
        FROM billing_invoice_master
        WHERE status='final' AND customer_id='$customer_id'
        ORDER BY billing_invoice_id DESC
        LIMIT 200");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $snap = billing_invoice_settlement_snapshot($conn, (int) $row['billing_invoice_id'], 0);
            if (!$snap || (float) $snap['balance'] <= 0.009) {
                continue;
            }
            $rows[] = array(
                'billing_invoice_id' => (int) $row['billing_invoice_id'],
                'invoice_no' => $row['invoice_no'],
                'invoice_date' => $row['invoice_date'],
                'grand_total' => billing_format_money($row['grand_total']),
                'balance' => $snap['balance'],
            );
        }
    }
    billing_receipt_json(array('status' => 0, 'data' => $rows));
}

if ($cmd === 'fetch_allocation') {
    $ids = isset($_REQUEST['invoice_ids']) ? $_REQUEST['invoice_ids'] : array();
    if (!is_array($ids)) {
        $ids = explode(',', (string) $ids);
    }
    $lines = array();
    foreach ($ids as $id) {
        $inv_id = (int) $id;
        if ($inv_id <= 0) {
            continue;
        }
        $snap = billing_invoice_settlement_snapshot($conn, $inv_id, 0);
        if ($snap) {
            $lines[] = $snap;
        }
    }
    billing_receipt_json(array('status' => 0, 'lines' => $lines));
}

billing_receipt_json(array('status' => 1, 'message' => 'Unknown command.'));
