<?php

require_once __DIR__ . '/gst_tax_functions.php';
require_once __DIR__ . '/gst_tax_report_functions.php';

function ensure_billing_tables($conn)
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_invoice_master (
        billing_invoice_id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_no VARCHAR(50) DEFAULT NULL,
        invoice_date VARCHAR(20) NOT NULL,
        customer_id INT NOT NULL DEFAULT 0,
        billing_type VARCHAR(30) DEFAULT NULL,
        status ENUM('draft','final','cancelled') NOT NULL DEFAULT 'draft',
        total_freight DECIMAL(14,2) NOT NULL DEFAULT 0,
        total_other DECIMAL(14,2) NOT NULL DEFAULT 0,
        taxable_value DECIMAL(14,2) NOT NULL DEFAULT 0,
        cgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        sgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        igst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        cess_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        grand_total DECIMAL(14,2) NOT NULL DEFAULT 0,
        total_words TEXT,
        pdf_path VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT NULL,
        created_by INT DEFAULT NULL,
        updated_at DATETIME DEFAULT NULL,
        updated_by INT DEFAULT NULL,
        KEY idx_customer (customer_id),
        KEY idx_status (status),
        KEY idx_invoice_no (invoice_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_invoice_details (
        detail_id INT AUTO_INCREMENT PRIMARY KEY,
        billing_invoice_id INT NOT NULL,
        trans_table VARCHAR(80) NOT NULL,
        transaction_id INT NOT NULL,
        grn_no VARCHAR(50) NOT NULL,
        grn_date VARCHAR(20) DEFAULT NULL,
        consigner_id INT DEFAULT 0,
        consignee_id INT DEFAULT 0,
        packages INT DEFAULT 0,
        weight DECIMAL(12,2) DEFAULT 0,
        freight_amount DECIMAL(14,2) DEFAULT 0,
        other_charges DECIMAL(14,2) DEFAULT 0,
        taxable_value DECIMAL(14,2) DEFAULT 0,
        cgst_amount DECIMAL(14,2) DEFAULT 0,
        sgst_amount DECIMAL(14,2) DEFAULT 0,
        igst_amount DECIMAL(14,2) DEFAULT 0,
        cess_amount DECIMAL(14,2) DEFAULT 0,
        gst_amount DECIMAL(14,2) DEFAULT 0,
        total_amount DECIMAL(14,2) DEFAULT 0,
        billing_type VARCHAR(30) DEFAULT NULL,
        invoiced_amount DECIMAL(14,2) DEFAULT 0,
        created_at DATETIME DEFAULT NULL,
        KEY idx_invoice (billing_invoice_id),
        KEY idx_grn (grn_no),
        KEY idx_trans (trans_table, transaction_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function billing_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function billing_mode_label($mode_id, $conn)
{
    $mode_id = (int) $mode_id;
    $map = array(
        1 => 'TO PAY',
        2 => 'TBB',
        3 => 'PAID',
        4 => 'COD',
    );
    if (isset($map[$mode_id])) {
        return $map[$mode_id];
    }
    if ($mode_id > 0 && function_exists('consignment_mode')) {
        return consignment_mode($conn, $mode_id);
    }
    return '';
}

function billing_type_options()
{
    return array(
        'to_pay' => 'TO PAY',
        'cod' => 'COD',
        'tbb' => 'TBB',
        'paid' => 'PAID',
    );
}

function billing_type_groups()
{
    return array(
        'Receiver (Consignee)' => array('to_pay' => 'TO PAY', 'cod' => 'COD'),
        'Sender (Consignor)' => array('tbb' => 'TBB', 'paid' => 'PAID'),
    );
}

function billing_customer_role_for_type($billing_type)
{
    $billing_type = trim((string) $billing_type);
    if (in_array($billing_type, array('to_pay', 'cod'), true)) {
        return 'consignee';
    }
    if (in_array($billing_type, array('tbb', 'paid'), true)) {
        return 'consignor';
    }
    return '';
}

function billing_normalize_billing_type($billing_type)
{
    $billing_type = trim((string) $billing_type);
    $legacy = array('receiver' => 'to_pay', 'sender' => 'tbb');
    if (isset($legacy[$billing_type])) {
        return $legacy[$billing_type];
    }
    $opts = billing_type_options();
    return isset($opts[$billing_type]) ? $billing_type : 'to_pay';
}

function billing_type_from_mode($mode_id)
{
    $mode_id = (int) $mode_id;
    $map = array(1 => 'to_pay', 2 => 'tbb', 3 => 'paid', 4 => 'cod');
    return isset($map[$mode_id]) ? $map[$mode_id] : 'to_pay';
}

function billing_get_invoiced_amount($conn, $trans_table, $transaction_id, $exclude_invoice_id = 0)
{
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    $exclude_invoice_id = (int) $exclude_invoice_id;
    $sql = "SELECT COALESCE(SUM(d.invoiced_amount),0) AS amt
        FROM billing_invoice_details d
        INNER JOIN billing_invoice_master m ON m.billing_invoice_id = d.billing_invoice_id
        WHERE d.trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
        AND d.transaction_id='$transaction_id'
        AND m.status='final'";
    if ($exclude_invoice_id > 0) {
        $sql .= " AND m.billing_invoice_id!='$exclude_invoice_id'";
    }
    $q = mysqli_query($conn, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) $row['amt'];
    }
    return 0.0;
}

function billing_gcn_on_invoice($conn, $trans_table, $transaction_id, $exclude_invoice_id = 0)
{
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    $exclude_invoice_id = (int) $exclude_invoice_id;
    $sql = "SELECT COUNT(*) AS c
        FROM billing_invoice_details d
        INNER JOIN billing_invoice_master m ON m.billing_invoice_id = d.billing_invoice_id
        WHERE d.trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
        AND d.transaction_id='$transaction_id'
        AND m.status IN ('draft','final')";
    if ($exclude_invoice_id > 0) {
        $sql .= " AND m.billing_invoice_id!='$exclude_invoice_id'";
    }
    $q = mysqli_query($conn, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (int) $row['c'] > 0;
    }
    return false;
}

function billing_gcn_on_final_invoice($conn, $trans_table, $transaction_id, $exclude_invoice_id = 0)
{
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    $exclude_invoice_id = (int) $exclude_invoice_id;
    $sql = "SELECT COUNT(*) AS c
        FROM billing_invoice_details d
        INNER JOIN billing_invoice_master m ON m.billing_invoice_id = d.billing_invoice_id
        WHERE d.trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
        AND d.transaction_id='$transaction_id'
        AND m.status='final'";
    if ($exclude_invoice_id > 0) {
        $sql .= " AND m.billing_invoice_id!='$exclude_invoice_id'";
    }
    $q = mysqli_query($conn, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (int) $row['c'] > 0;
    }
    return false;
}

function booking_is_gcn_billed($conn, $trans_table, $transaction_id)
{
    return billing_gcn_on_final_invoice($conn, $trans_table, $transaction_id);
}

function billing_should_exclude_gcn($conn, $trans_table, $transaction_id, $total = 0, $exclude_invoice_id = 0)
{
    // Once a GCN is on any invoice (draft or final), do not offer it again except on that same invoice.
    return billing_gcn_on_invoice($conn, $trans_table, $transaction_id, $exclude_invoice_id);
}

function billing_preview_invoice_number($conn, $invoice_date)
{
    $parts = explode('-', $invoice_date);
    if (count($parts) !== 3) {
        $invoice_date = date('d-m-Y');
        $parts = explode('-', $invoice_date);
    }
    $year = (int) $parts[2];
    $p_y = substr((string) ($year - 1), -2);
    $c_y = substr((string) $year, -2);
    $year_insert = $p_y . '-' . $c_y;

    $invoice_table = invoice_table_function($conn, $invoice_date);
    $q = mysqli_query($conn, "SELECT invoice_no, gst_text, gst_year FROM `$invoice_table` WHERE inv_type='GST' LIMIT 1");
    $seq = 1;
    $gst_text = 'HRGST';
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $seq = (int) $row['invoice_no'] + 1;
        $gst_text = $row['gst_text'] ?: 'HRGST';
        $year_insert = $row['gst_year'] ?: $year_insert;
    }
    return $gst_text . '/' . sprintf('%05d', $seq) . '/' . $year_insert;
}

function billing_allocate_invoice_number($conn, $invoice_date, $created_by)
{
    $parts = explode('-', $invoice_date);
    if (count($parts) !== 3) {
        $invoice_date = date('d-m-Y');
        $parts = explode('-', $invoice_date);
    }
    $year = (int) $parts[2];
    $p_y = substr((string) ($year - 1), -2);
    $c_y = substr((string) $year, -2);
    $year_insert = $p_y . '-' . $c_y;
    $created_at = date('Y-m-d H:i:s');
    $created_by = (int) $created_by;

    $invoice_table = invoice_table_function($conn, $invoice_date);
    $chk = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$invoice_table`");
    $count = 0;
    if ($chk && ($r = mysqli_fetch_assoc($chk))) {
        $count = (int) $r['c'];
    }
    if ($count === 0) {
        mysqli_query($conn, "INSERT INTO `$invoice_table`
            (`invoice_no`,`gst_text`,`gst_year`,`inv_type`,`created_at`,`created_by`)
            VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),
                   ('0','HRGTA','$year_insert','GTA','$created_at','$created_by')");
    }

    $q = mysqli_query($conn, "SELECT * FROM `$invoice_table` WHERE inv_type='GST' LIMIT 1 FOR UPDATE");
    $row = mysqli_fetch_assoc($q);
    if (!$row) {
        return '';
    }
    $inv_seq = (int) $row['invoice_no'] + 1;
    $inv_text = $row['gst_text'] ?: 'HRGST';
    $inv_year = $row['gst_year'] ?: $year_insert;
    $invoice_no = $inv_text . '/' . sprintf('%05d', $inv_seq) . '/' . $inv_year;

    mysqli_query($conn, "UPDATE `$invoice_table` SET invoice_no='$inv_seq', updated_by='$created_by', updated_at='$created_at' WHERE inv_type='GST'");

    return $invoice_no;
}

function billing_parse_trans_key($key)
{
    $key = trim((string) $key);
    if ($key === '' || strpos($key, '|') === false) {
        return null;
    }
    $parts = explode('|', $key, 2);
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[0]);
    $tid = (int) $parts[1];
    if ($table === '' || $tid <= 0) {
        return null;
    }
    return array('trans_table' => $table, 'transaction_id' => $tid);
}

function billing_fetch_delivered_gcns($conn, $customer_ids = array(), $exclude_invoice_id = 0, $billing_type = '')
{
    ensure_billing_tables($conn);
    ensure_transaction_gst_columns($conn, 'transaction');
    $billing_type = billing_normalize_billing_type($billing_type);

    $customer_filter = '';
    if (!empty($customer_ids)) {
        $ids = array_filter(array_map('intval', $customer_ids));
        if (!empty($ids)) {
            $id_list = implode(',', $ids);
            // Show all delivered GCNs where customer is consignor or consignee.
            $customer_filter = " AND (t.consigner IN ($id_list) OR t.consignee IN ($id_list))";
        }
    }

    $rows = array();
    $tables_q = mysqli_query($conn, 'SELECT table_name FROM transaction_tbls ORDER BY table_name DESC');
    if (!$tables_q) {
        return $rows;
    }

    while ($tbl = mysqli_fetch_assoc($tables_q)) {
        $trans_table = 'transaction_' . preg_replace('/[^a-zA-Z0-9_]/', '', $tbl['table_name']);
        $chk = @mysqli_query($conn, "SELECT 1 FROM `$trans_table` LIMIT 1");
        if (!$chk) {
            continue;
        }
        if (!gst_tax_report_table_has_gst_columns($conn, $trans_table)) {
            ensure_transaction_gst_columns($conn, $trans_table);
        }

        $sql = "SELECT t.transaction_id, t.grn_no, t.grn_date, t.consigner, t.consignee,
            t.total, t.mode_of_consignment,
            t.frieght_amount, t.doc_amount, t.other_charge_amount, t.mamul_charge,
            t.vehicle_halting_charge, t.vehicle_loading_unloading, t.rajdhani_charges,
            t.taxable_value, t.gst_amount
            FROM `$trans_table` t
            WHERE t.status='8'
            AND (t.booking_status IS NULL OR t.booking_status='' OR t.booking_status!='1')
            $customer_filter
            ORDER BY STR_TO_DATE(t.grn_date,'%d-%m-%Y') DESC, t.grn_no DESC";

        $q = mysqli_query($conn, $sql);
        if (!$q) {
            continue;
        }

        while ($row = mysqli_fetch_assoc($q)) {
            if (billing_should_exclude_gcn($conn, $trans_table, $row['transaction_id'], 0, $exclude_invoice_id)) {
                continue;
            }
            $total = (float) ($row['total'] ?? 0);
            $remaining = $total;
            $sender = get_client_name($conn, $row['consigner']);
            $receiver = get_client_name($conn, $row['consignee']);
            $label = $row['grn_no'] . ' | ' . $row['grn_date'] . ' | ' . $sender . ' | ' . $receiver . ' | ' . billing_format_money($remaining);

            $rows[] = array(
                'key' => $trans_table . '|' . $row['transaction_id'],
                'label' => $label,
                'grn_no' => $row['grn_no'],
                'grn_date' => $row['grn_date'],
                'amount' => billing_format_money($remaining),
                'consigner_id' => (int) $row['consigner'],
                'consignee_id' => (int) $row['consignee'],
            );
        }
    }

    return $rows;
}

function billing_invoice_lines_aggregate($conn, $trans_table, $transaction_id)
{
    $inv_tbl = str_replace('transaction_', 'transaction_invoice_', preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table));
    $transaction_id = (int) $transaction_id;
    $agg = array('packages' => 0, 'weight' => 0.0, 'freight' => 0.0, 'rate' => 0.0);

    $q = @mysqli_query($conn, "SELECT qty, charged_weight, frieght_rate FROM `$inv_tbl`
        WHERE transaction_id='$transaction_id'
        AND (type_of_pkge IS NULL OR type_of_pkge='' OR type_of_pkge!='Select Package Type')");
    if (!$q) {
        return $agg;
    }

    while ($line = mysqli_fetch_assoc($q)) {
        $qty = (int) ($line['qty'] ?? 0);
        $weight = (float) ($line['charged_weight'] ?? 0);
        $rate = (float) ($line['frieght_rate'] ?? 0);
        $agg['packages'] += $qty;
        $agg['weight'] += $weight;
        $agg['freight'] += round($weight * $rate, 2);
        if ($rate > 0) {
            $agg['rate'] = $rate;
        }
    }

    return $agg;
}

function billing_freight_from_row($row, $line_agg = null)
{
    $freight = (float) ($row['frieght_amount'] ?? 0);
    if ($freight <= 0 && is_array($line_agg) && (float) ($line_agg['freight'] ?? 0) > 0) {
        return round((float) $line_agg['freight'], 2);
    }
    if ($freight <= 0) {
        $weight = (float) ($row['consignment_weight'] ?? 0);
        if ($weight <= 0 && is_array($line_agg)) {
            $weight = (float) ($line_agg['weight'] ?? 0);
        }
        $rate = (float) ($row['frieght_rate'] ?? 0);
        if ($rate <= 0 && is_array($line_agg)) {
            $rate = (float) ($line_agg['rate'] ?? 0);
        }
        if ($weight > 0 && $rate > 0) {
            $freight = round($weight * $rate, 2);
        }
    }

    return $freight;
}

function billing_extra_charges_from_row($row)
{
    $loading = (float) ($row['vehicle_loading_unloading'] ?? 0);
    if ($loading <= 0) {
        $loading = (float) ($row['loading_unloading_amount'] ?? 0);
    }

    return round(
        (float) ($row['doc_amount'] ?? 0)
        + (float) ($row['other_charge_amount'] ?? 0)
        + (float) ($row['mamul_charge'] ?? 0)
        + (float) ($row['vehicle_halting_charge'] ?? 0)
        + $loading
        + (float) ($row['rajdhani_charges'] ?? 0),
        2
    );
}

function billing_amounts_from_booking_row($row, $line_agg = null)
{
    $freight = billing_freight_from_row($row, $line_agg);
    $extra = billing_extra_charges_from_row($row);
    $taxable = round((float) ($row['taxable_value'] ?? 0), 2);
    if ($taxable <= 0) {
        $taxable = round($freight + $extra, 2);
    }
    if ($freight <= 0 && $taxable > 0) {
        if ($extra > 0) {
            $freight = max(0, round($taxable - $extra, 2));
        } else {
            $freight = $taxable;
        }
    }
    $other = max(0, round($taxable - $freight, 2));

    return array(
        'freight' => $freight,
        'other' => $other,
        'taxable' => $taxable,
    );
}

function billing_fetch_gcn_detail($conn, $trans_table, $transaction_id, $billing_type = '', $exclude_invoice_id = 0)
{
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    if ($trans_table === '' || $transaction_id <= 0) {
        return null;
    }

    $q = mysqli_query($conn, "SELECT * FROM `$trans_table` WHERE transaction_id='$transaction_id' AND status='8' LIMIT 1");
    if (!$q || !($row = mysqli_fetch_assoc($q))) {
        return null;
    }

    if (billing_should_exclude_gcn($conn, $trans_table, $transaction_id, 0, $exclude_invoice_id)) {
        return null;
    }

    $packages = (int) ($row['no_of_pkge'] ?? 0);
    $weight = (float) ($row['consignment_weight'] ?? 0);
    $line_agg = billing_invoice_lines_aggregate($conn, $trans_table, $transaction_id);
    if ($packages <= 0 && (int) ($line_agg['packages'] ?? 0) > 0) {
        $packages = (int) $line_agg['packages'];
    }
    if ($weight <= 0 && (float) ($line_agg['weight'] ?? 0) > 0) {
        $weight = (float) $line_agg['weight'];
    }
    if ($packages <= 0 || $weight <= 0) {
        $inv_tbl = str_replace('transaction_', 'transaction_invoice_', $trans_table);
        $iq = @mysqli_query($conn, "SELECT SUM(qty) AS pq, SUM(charged_weight) AS pw FROM `$inv_tbl` WHERE transaction_id='$transaction_id'");
        if ($iq && ($ir = mysqli_fetch_assoc($iq))) {
            if ($packages <= 0) {
                $packages = (int) ($ir['pq'] ?? 0);
            }
            if ($weight <= 0) {
                $weight = (float) ($ir['pw'] ?? 0);
            }
        }
    }

    $row_calc = $row;
    $row_calc['consignment_weight'] = $weight;

    $amounts = billing_amounts_from_booking_row($row_calc, $line_agg);
    $freight = $amounts['freight'];
    $other = $amounts['other'];
    $taxable = $amounts['taxable'];
    $cgst = (float) ($row['cgst_amount'] ?? 0);
    $sgst = (float) ($row['sgst_amount'] ?? 0);
    $igst = (float) ($row['igst_amount'] ?? 0);
    $cess = (float) ($row['cess_amount'] ?? 0);
    $gst = (float) ($row['gst_amount'] ?? 0);
    $total = (float) ($row['total'] ?? 0);
    $line_total = $total > 0 ? $total : ($freight + $other + $gst);

    if ($billing_type === '') {
        $billing_type = billing_type_from_mode($row['mode_of_consignment'] ?? 0);
    } else {
        $billing_type = billing_normalize_billing_type($billing_type);
    }

    $opts = billing_type_options();
    return array(
        'key' => $trans_table . '|' . $transaction_id,
        'trans_table' => $trans_table,
        'transaction_id' => $transaction_id,
        'grn_no' => $row['grn_no'],
        'grn_date' => $row['grn_date'],
        'sender' => get_client_name($conn, $row['consigner']),
        'receiver' => get_client_name($conn, $row['consignee']),
        'consigner_id' => (int) $row['consigner'],
        'consignee_id' => (int) $row['consignee'],
        'packages' => $packages,
        'weight' => billing_format_money($weight),
        'freight_amount' => billing_format_money($freight),
        'other_charges' => billing_format_money($other),
        'taxable_value' => billing_format_money($taxable),
        'cgst_amount' => billing_format_money($cgst),
        'sgst_amount' => billing_format_money($sgst),
        'igst_amount' => billing_format_money($igst),
        'cess_amount' => billing_format_money($cess),
        'gst_amount' => billing_format_money($gst),
        'total_amount' => billing_format_money($line_total),
        'billing_type' => $billing_type,
        'billing_type_label' => isset($opts[$billing_type]) ? $opts[$billing_type] : strtoupper($billing_type),
        'mode_label' => billing_mode_label($row['mode_of_consignment'] ?? 0, $conn),
        'invoiced_amount' => billing_format_money($line_total),
    );
}

function billing_sum_lines($lines)
{
    $sum = array(
        'total_freight' => 0,
        'total_other' => 0,
        'taxable_value' => 0,
        'cgst_amount' => 0,
        'sgst_amount' => 0,
        'igst_amount' => 0,
        'cess_amount' => 0,
        'gst_amount' => 0,
        'grand_total' => 0,
    );
    foreach ($lines as $line) {
        $sum['total_freight'] += (float) ($line['freight_amount'] ?? 0);
        $sum['total_other'] += (float) ($line['other_charges'] ?? 0);
        $sum['taxable_value'] += (float) ($line['taxable_value'] ?? 0);
        $sum['cgst_amount'] += (float) ($line['cgst_amount'] ?? 0);
        $sum['sgst_amount'] += (float) ($line['sgst_amount'] ?? 0);
        $sum['igst_amount'] += (float) ($line['igst_amount'] ?? 0);
        $sum['cess_amount'] += (float) ($line['cess_amount'] ?? 0);
        $sum['gst_amount'] += (float) ($line['gst_amount'] ?? 0);
        $sum['grand_total'] += (float) ($line['total_amount'] ?? 0);
    }
    foreach ($sum as $k => $v) {
        $sum[$k] = billing_format_money($v);
    }
    return $sum;
}

function billing_get_invoice($conn, $billing_invoice_id)
{
    $billing_invoice_id = (int) $billing_invoice_id;
    $q = mysqli_query($conn, "SELECT * FROM billing_invoice_master WHERE billing_invoice_id='$billing_invoice_id' LIMIT 1");
    if (!$q || !($master = mysqli_fetch_assoc($q))) {
        return null;
    }
    $details = array();
    $dq = mysqli_query($conn, "SELECT * FROM billing_invoice_details WHERE billing_invoice_id='$billing_invoice_id' ORDER BY detail_id ASC");
    if ($dq) {
        while ($d = mysqli_fetch_assoc($dq)) {
            $details[] = $d;
        }
    }
    return array('master' => $master, 'details' => $details);
}

function billing_save_invoice($conn, $payload, $user_id)
{
    ensure_billing_tables($conn);

    $invoice_date = trim($payload['invoice_date'] ?? date('d-m-Y'));
    $customer_id = (int) ($payload['customer_id'] ?? 0);
    $billing_type_raw = trim($payload['billing_type'] ?? '');
    $status = ($payload['status'] ?? 'draft') === 'final' ? 'final' : 'draft';
    $edit_id = (int) ($payload['billing_invoice_id'] ?? 0);
    $lines = isset($payload['lines']) && is_array($payload['lines']) ? $payload['lines'] : array();
    $now = date('Y-m-d H:i:s');
    $user_id = (int) $user_id;

    if ($customer_id <= 0) {
        return array('status' => 1, 'message' => 'Please select a customer.');
    }
    if (empty($lines)) {
        return array('status' => 1, 'message' => 'Please select at least one GCN.');
    }

    $parsed_lines = array();
    $seen = array();
    foreach ($lines as $line) {
        $key = isset($line['key']) ? $line['key'] : '';
        $parsed = billing_parse_trans_key($key);
        if (!$parsed) {
            continue;
        }
        if (isset($seen[$key])) {
            return array('status' => 1, 'message' => 'Duplicate GCN selected: ' . ($line['grn_no'] ?? $key));
        }
        $seen[$key] = true;
        $bt = trim($line['billing_type'] ?? $billing_type_raw);
        $detail = billing_fetch_gcn_detail($conn, $parsed['trans_table'], $parsed['transaction_id'], $bt, $edit_id);
        if (!$detail) {
            return array('status' => 1, 'message' => 'GCN not available for invoicing: ' . ($line['grn_no'] ?? $key));
        }
        if ($detail['consigner_id'] !== $customer_id && $detail['consignee_id'] !== $customer_id) {
            return array('status' => 1, 'message' => 'GCN ' . $detail['grn_no'] . ' does not belong to selected customer.');
        }
        $parsed_lines[] = $detail;
    }

    if (empty($parsed_lines)) {
        return array('status' => 1, 'message' => 'No valid GCN lines to save.');
    }

    if ($billing_type_raw === '') {
        $billing_type = mysqli_real_escape_string($conn, billing_normalize_billing_type($parsed_lines[0]['billing_type'] ?? ''));
    } else {
        $billing_type = mysqli_real_escape_string($conn, billing_normalize_billing_type($billing_type_raw));
    }

    $totals = billing_sum_lines($parsed_lines);
    $total_words = gst_tax_report_amount_in_words((float) $totals['grand_total']);

    $invoice_no = '';
    if ($status === 'final') {
        if ($edit_id > 0) {
            $existing = billing_get_invoice($conn, $edit_id);
            if ($existing && !empty($existing['master']['invoice_no']) && $existing['master']['status'] === 'final') {
                $invoice_no = $existing['master']['invoice_no'];
            }
        }
        if ($invoice_no === '') {
            $invoice_no = billing_allocate_invoice_number($conn, $invoice_date, $user_id);
        }
        if ($invoice_no === '') {
            return array('status' => 1, 'message' => 'Could not generate invoice number.');
        }
    } elseif ($edit_id > 0) {
        $existing = billing_get_invoice($conn, $edit_id);
        if ($existing && !empty($existing['master']['invoice_no'])) {
            $invoice_no = $existing['master']['invoice_no'];
        }
    }

    $esc_date = mysqli_real_escape_string($conn, $invoice_date);
    $esc_no = mysqli_real_escape_string($conn, $invoice_no);
    $esc_words = mysqli_real_escape_string($conn, $total_words);

    if ($edit_id > 0) {
        $existing = billing_get_invoice($conn, $edit_id);
        if (!$existing || $existing['master']['status'] === 'cancelled') {
            return array('status' => 1, 'message' => 'Invoice not found.');
        }
        if ($existing['master']['status'] === 'final' && $status === 'draft') {
            return array('status' => 1, 'message' => 'Final invoice cannot be moved back to draft.');
        }
        mysqli_query($conn, "UPDATE billing_invoice_master SET
            invoice_no='$esc_no',
            invoice_date='$esc_date',
            customer_id='$customer_id',
            billing_type='$billing_type',
            status='$status',
            total_freight='{$totals['total_freight']}',
            total_other='{$totals['total_other']}',
            taxable_value='{$totals['taxable_value']}',
            cgst_amount='{$totals['cgst_amount']}',
            sgst_amount='{$totals['sgst_amount']}',
            igst_amount='{$totals['igst_amount']}',
            cess_amount='{$totals['cess_amount']}',
            gst_amount='{$totals['gst_amount']}',
            grand_total='{$totals['grand_total']}',
            total_words='$esc_words',
            updated_at='$now',
            updated_by='$user_id'
            WHERE billing_invoice_id='$edit_id'");
        mysqli_query($conn, "DELETE FROM billing_invoice_details WHERE billing_invoice_id='$edit_id'");
        $billing_invoice_id = $edit_id;
    } else {
        mysqli_query($conn, "INSERT INTO billing_invoice_master
            (invoice_no, invoice_date, customer_id, billing_type, status,
             total_freight, total_other, taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, gst_amount, grand_total, total_words, created_at, created_by, updated_at, updated_by)
            VALUES
            ('$esc_no','$esc_date','$customer_id','$billing_type','$status',
             '{$totals['total_freight']}','{$totals['total_other']}','{$totals['taxable_value']}',
             '{$totals['cgst_amount']}','{$totals['sgst_amount']}','{$totals['igst_amount']}','{$totals['cess_amount']}','{$totals['gst_amount']}','{$totals['grand_total']}','$esc_words','$now','$user_id','$now','$user_id')");
        $billing_invoice_id = (int) mysqli_insert_id($conn);
    }

    foreach ($parsed_lines as $line) {
        $esc_tbl = mysqli_real_escape_string($conn, $line['trans_table']);
        $esc_grn = mysqli_real_escape_string($conn, $line['grn_no']);
        $esc_gdate = mysqli_real_escape_string($conn, $line['grn_date']);
        $esc_bt = mysqli_real_escape_string($conn, $line['billing_type']);
        mysqli_query($conn, "INSERT INTO billing_invoice_details
            (billing_invoice_id, trans_table, transaction_id, grn_no, grn_date, consigner_id, consignee_id,
             packages, weight, freight_amount, other_charges, taxable_value, cgst_amount, sgst_amount, igst_amount,
             cess_amount, gst_amount, total_amount, billing_type, invoiced_amount, created_at)
            VALUES
            ('$billing_invoice_id','$esc_tbl','{$line['transaction_id']}','$esc_grn','$esc_gdate',
             '{$line['consigner_id']}','{$line['consignee_id']}','{$line['packages']}','{$line['weight']}',
             '{$line['freight_amount']}','{$line['other_charges']}','{$line['taxable_value']}',
             '{$line['cgst_amount']}','{$line['sgst_amount']}','{$line['igst_amount']}','{$line['cess_amount']}',
             '{$line['gst_amount']}','{$line['total_amount']}','$esc_bt','{$line['invoiced_amount']}','$now')");
    }

    return array(
        'status' => 0,
        'message' => $status === 'final' ? 'Invoice generated successfully.' : 'Draft saved successfully.',
        'billing_invoice_id' => $billing_invoice_id,
        'invoice_no' => $invoice_no,
        'invoice_status' => $status,
    );
}
