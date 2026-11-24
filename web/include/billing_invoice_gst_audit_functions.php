<?php

require_once __DIR__ . '/billing_functions.php';

/**
 * Final tax invoices that show IGST but supplier state matches place-of-supply (likely route-based error).
 */
function billing_invoice_gst_audit_mismatch_rows($conn, $filters = array())
{
    ensure_billing_tables($conn);

    $from = trim($filters['from_date'] ?? '');
    $to = trim($filters['to_date'] ?? '');
    $invoice_no = trim($filters['invoice_no'] ?? '');

    $where = array("m.status='final'", 'COALESCE(m.is_active,1)=1', 'm.igst_amount > 0');
    if ($from !== '') {
        $esc = mysqli_real_escape_string($conn, $from);
        $where[] = "STR_TO_DATE(m.invoice_date, '%d-%m-%Y') >= STR_TO_DATE('$esc', '%d-%m-%Y')";
    }
    if ($to !== '') {
        $esc = mysqli_real_escape_string($conn, $to);
        $where[] = "STR_TO_DATE(m.invoice_date, '%d-%m-%Y') <= STR_TO_DATE('$esc', '%d-%m-%Y')";
    }
    if ($invoice_no !== '') {
        $esc = mysqli_real_escape_string($conn, $invoice_no);
        $where[] = "m.invoice_no='$esc'";
    }

    $supplier_state = gst_tax_supplier_state_id($conn);
    $rows = array();

    $sql = 'SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.customer_id,
            m.taxable_value, m.cgst_amount, m.sgst_amount, m.igst_amount, m.grand_total
        FROM billing_invoice_master m
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY STR_TO_DATE(m.invoice_date, "%d-%m-%Y") DESC, m.billing_invoice_id DESC';
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return $rows;
    }

    while ($inv = mysqli_fetch_assoc($q)) {
        $customer_id = (int) ($inv['customer_id'] ?? 0);
        $billing_invoice_id = (int) ($inv['billing_invoice_id'] ?? 0);

        $dq = mysqli_query($conn, "SELECT trans_table, transaction_id FROM billing_invoice_details
            WHERE billing_invoice_id='$billing_invoice_id' LIMIT 1");
        if (!$dq || !($d = mysqli_fetch_assoc($dq))) {
            continue;
        }
        $tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $d['trans_table'] ?? '');
        $tid = (int) ($d['transaction_id'] ?? 0);
        if ($tbl === '' || $tid <= 0) {
            continue;
        }
        $bq = mysqli_query($conn, "SELECT * FROM `$tbl` WHERE transaction_id='$tid' LIMIT 1");
        if (!$bq || !($booking = mysqli_fetch_assoc($bq))) {
            continue;
        }

        $ctx = gst_tax_invoice_place_of_supply_context($conn, $customer_id, $booking);
        $pos = (int) ($ctx['pos_state_id'] ?? 0);
        if ($supplier_state <= 0 || $pos <= 0) {
            continue;
        }
        if ($supplier_state !== $pos) {
            continue;
        }

        $rows[] = array(
            'billing_invoice_id' => $billing_invoice_id,
            'invoice_no' => $inv['invoice_no'] ?? '',
            'invoice_date' => $inv['invoice_date'] ?? '',
            'customer_id' => $customer_id,
            'grn_no' => $booking['grn_no'] ?? '',
            'taxable_value' => (float) ($inv['taxable_value'] ?? 0),
            'cgst_amount' => (float) ($inv['cgst_amount'] ?? 0),
            'sgst_amount' => (float) ($inv['sgst_amount'] ?? 0),
            'igst_amount' => (float) ($inv['igst_amount'] ?? 0),
            'grand_total' => (float) ($inv['grand_total'] ?? 0),
            'supplier_state_id' => $supplier_state,
            'pos_state_id' => $pos,
            'bill_to_gstin' => $ctx['bill_to_gstin'] ?? '',
            'expected_gst_type' => 'intra',
        );
    }

    return $rows;
}

function billing_invoice_gst_audit_to_csv(array $rows)
{
    $out = "invoice_no,invoice_date,grn_no,taxable_value,cgst,sgst,igst,grand_total,bill_to_gstin,expected_gst_type\n";
    foreach ($rows as $r) {
        $out .= sprintf(
            "%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%.2f,%s,%s\n",
            str_replace(',', ' ', $r['invoice_no'] ?? ''),
            $r['invoice_date'] ?? '',
            $r['grn_no'] ?? '',
            (float) ($r['taxable_value'] ?? 0),
            (float) ($r['cgst_amount'] ?? 0),
            (float) ($r['sgst_amount'] ?? 0),
            (float) ($r['igst_amount'] ?? 0),
            (float) ($r['grand_total'] ?? 0),
            $r['bill_to_gstin'] ?? '',
            $r['expected_gst_type'] ?? ''
        );
    }

    return $out;
}