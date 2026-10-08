<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/gcn_gst_invoice_helpers.php';

function ensure_billing_proforma_tables($conn)
{
    ensure_billing_tables($conn);
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_proforma_master (
        billing_proforma_id INT AUTO_INCREMENT PRIMARY KEY,
        proforma_no VARCHAR(50) DEFAULT NULL,
        invoice_date VARCHAR(20) NOT NULL,
        customer_id INT NOT NULL DEFAULT 0,
        billing_type VARCHAR(30) DEFAULT NULL,
        doc_style ENUM('gst','other') NOT NULL DEFAULT 'gst',
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
        KEY idx_proforma_no (proforma_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_proforma_details (
        detail_id INT AUTO_INCREMENT PRIMARY KEY,
        billing_proforma_id INT NOT NULL,
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
        created_at DATETIME DEFAULT NULL,
        KEY idx_proforma (billing_proforma_id),
        KEY idx_grn (grn_no),
        KEY idx_trans (trans_table, transaction_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function billing_proforma_display_label($row)
{
    if (!empty($row['proforma_no'])) {
        return $row['proforma_no'];
    }
    if (!empty($row['billing_proforma_id'])) {
        return 'Proforma draft #' . (int) $row['billing_proforma_id'];
    }

    return 'another proforma';
}

function billing_proforma_gcn_on_other($conn, $trans_table, $transaction_id, $exclude_proforma_id = 0)
{
    ensure_billing_proforma_tables($conn);
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    $exclude_proforma_id = (int) $exclude_proforma_id;
    $sql = "SELECT COUNT(*) AS c
        FROM billing_proforma_details d
        INNER JOIN billing_proforma_master m ON m.billing_proforma_id = d.billing_proforma_id
        WHERE d.trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
        AND d.transaction_id='$transaction_id'
        AND m.status IN ('draft','final')";
    if ($exclude_proforma_id > 0) {
        $sql .= " AND m.billing_proforma_id!='$exclude_proforma_id'";
    }
    $q = mysqli_query($conn, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (int) $row['c'] > 0;
    }

    return false;
}

function billing_proforma_gcn_conflict($conn, $trans_table, $transaction_id, $exclude_proforma_id = 0)
{
    ensure_billing_proforma_tables($conn);
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    $exclude_proforma_id = (int) $exclude_proforma_id;
    if ($trans_table === '' || $transaction_id <= 0) {
        return null;
    }
    $sql = "SELECT m.proforma_no, m.billing_proforma_id, m.status
        FROM billing_proforma_details d
        INNER JOIN billing_proforma_master m ON m.billing_proforma_id = d.billing_proforma_id
        WHERE d.trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
        AND d.transaction_id='$transaction_id'
        AND m.status IN ('draft','final')";
    if ($exclude_proforma_id > 0) {
        $sql .= " AND m.billing_proforma_id!='$exclude_proforma_id'";
    }
    $sql .= ' ORDER BY m.billing_proforma_id DESC LIMIT 1';
    $q = mysqli_query($conn, $sql);

    return ($q && ($row = mysqli_fetch_assoc($q))) ? $row : null;
}

/** Block GCN for proforma picker: final tax invoice, or another proforma (not current edit). */
function billing_proforma_blocks_gcn_selection($conn, $trans_table, $transaction_id, $exclude_proforma_id = 0)
{
    $tax_conflict = billing_gcn_final_conflict($conn, $trans_table, $transaction_id, 0);
    if ($tax_conflict) {
        return true;
    }

    return billing_proforma_gcn_on_other($conn, $trans_table, $transaction_id, $exclude_proforma_id);
}

function billing_proforma_gcn_on_current($conn, $trans_table, $transaction_id, $billing_proforma_id)
{
    $billing_proforma_id = (int) $billing_proforma_id;
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    if ($billing_proforma_id <= 0 || $trans_table === '' || $transaction_id <= 0) {
        return false;
    }
    $sql = "SELECT COUNT(*) AS c FROM billing_proforma_details
        WHERE billing_proforma_id='$billing_proforma_id'
        AND trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
        AND transaction_id='$transaction_id'";
    $q = mysqli_query($conn, $sql);

    return ($q && ($row = mysqli_fetch_assoc($q))) ? ((int) $row['c'] > 0) : false;
}

function billing_proforma_get_stored_detail_row($conn, $billing_proforma_id, $trans_table, $transaction_id)
{
    $billing_proforma_id = (int) $billing_proforma_id;
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    if ($billing_proforma_id <= 0 || $trans_table === '' || $transaction_id <= 0) {
        return null;
    }
    $sql = "SELECT * FROM billing_proforma_details
        WHERE billing_proforma_id='$billing_proforma_id'
        AND trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
        AND transaction_id='$transaction_id'
        LIMIT 1";
    $q = mysqli_query($conn, $sql);

    return ($q && ($row = mysqli_fetch_assoc($q))) ? $row : null;
}

function billing_proforma_resolve_doc_style(array $totals)
{
    $gst = (float) ($totals['gst_amount'] ?? 0);
    $comp = (float) ($totals['cgst_amount'] ?? 0)
        + (float) ($totals['sgst_amount'] ?? 0)
        + (float) ($totals['igst_amount'] ?? 0);
    if ($gst > 0.001 || $comp > 0.001) {
        return 'gst';
    }

    return 'other';
}

function billing_proforma_preview_number($conn, $invoice_date)
{
    return billing_next_trans_invoice_number($conn, $invoice_date, 0, 'PROFORMA', false);
}

function billing_proforma_allocate_number($conn, $invoice_date, $created_by)
{
    return billing_next_trans_invoice_number($conn, $invoice_date, $created_by, 'PROFORMA', true);
}

function billing_proforma_number_exists($conn, $proforma_no, $exclude_id = 0)
{
    ensure_billing_proforma_tables($conn);
    $proforma_no = trim((string) $proforma_no);
    if ($proforma_no === '') {
        return false;
    }
    $esc = mysqli_real_escape_string($conn, $proforma_no);
    $exclude = (int) $exclude_id;
    $sql = "SELECT billing_proforma_id FROM billing_proforma_master
        WHERE proforma_no='$esc' AND status != 'cancelled'";
    if ($exclude > 0) {
        $sql .= " AND billing_proforma_id!='$exclude'";
    }
    $sql .= ' LIMIT 1';
    $q = mysqli_query($conn, $sql);

    return ($q && mysqli_num_rows($q) > 0);
}

function billing_proforma_validate_gcn($conn, $trans_table, $transaction_id, $customer_id, $billing_type, $edit_id)
{
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
    $transaction_id = (int) $transaction_id;
    $customer_id = (int) $customer_id;
    $edit_id = (int) $edit_id;

    if ($trans_table === '' || $transaction_id <= 0) {
        return array('ok' => false, 'message' => 'Invalid GCN reference.');
    }

    $q = mysqli_query($conn, "SELECT transaction_id, grn_no, status, booking_status, consigner, consignee
        FROM `$trans_table` WHERE transaction_id='$transaction_id' LIMIT 1");
    if (!$q || !($booking = mysqli_fetch_assoc($q))) {
        return array('ok' => false, 'message' => 'GCN not found.');
    }
    if ((string) ($booking['status'] ?? '') !== '8') {
        return array('ok' => false, 'message' => 'GCN ' . ($booking['grn_no'] ?? '') . ' is not delivered yet.');
    }
    if ((string) ($booking['booking_status'] ?? '') === '1') {
        return array('ok' => false, 'message' => 'GCN ' . ($booking['grn_no'] ?? '') . ' is cancelled.');
    }

    $tax_conflict = billing_gcn_final_conflict($conn, $trans_table, $transaction_id, 0);
    if ($tax_conflict) {
        return array(
            'ok' => false,
            'message' => 'GCN ' . ($booking['grn_no'] ?? '') . ' is already on tax invoice ' . billing_invoice_display_label($tax_conflict) . '.',
            'has_conflict' => 1,
            'grn_no' => $booking['grn_no'] ?? '',
        );
    }

    $proforma_conflict = billing_proforma_gcn_conflict($conn, $trans_table, $transaction_id, $edit_id);
    if ($proforma_conflict) {
        return array(
            'ok' => false,
            'message' => 'GCN ' . ($booking['grn_no'] ?? '') . ' is already on ' . billing_proforma_display_label($proforma_conflict) . '.',
            'grn_no' => $booking['grn_no'] ?? '',
        );
    }

    $detail = billing_fetch_gcn_detail($conn, $trans_table, $transaction_id, $billing_type, 0, $edit_id);
    if (!$detail) {
        return array('ok' => false, 'message' => 'GCN ' . ($booking['grn_no'] ?? '') . ' is not available.');
    }
    if ($customer_id > 0 && $detail['consigner_id'] !== $customer_id && $detail['consignee_id'] !== $customer_id) {
        return array('ok' => false, 'message' => 'GCN ' . $detail['grn_no'] . ' does not belong to the selected customer.');
    }

    return array('ok' => true, 'detail' => $detail);
}

function billing_proforma_line_from_detail_row($conn, $d, $exclude_proforma_id = 0)
{
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $d['trans_table'] ?? '');
    $transaction_id = (int) ($d['transaction_id'] ?? 0);
    if ($trans_table === '' || $transaction_id <= 0) {
        return null;
    }

    $detail = billing_fetch_gcn_detail($conn, $trans_table, $transaction_id, $d['billing_type'] ?? '', 0, $exclude_proforma_id);
    if ($detail) {
        return $detail;
    }

    $tax_conflict = billing_gcn_final_conflict($conn, $trans_table, $transaction_id, 0);
    $proforma_conflict = billing_proforma_gcn_conflict($conn, $trans_table, $transaction_id, $exclude_proforma_id);
    $bt = billing_normalize_billing_type($d['billing_type'] ?? '');
    $opts = billing_type_options();
    $sender = get_client_name($conn, $d['consigner_id'] ?? 0);
    $receiver = get_client_name($conn, $d['consignee_id'] ?? 0);
    $warning = $tax_conflict
        ? ('Already on tax invoice ' . billing_invoice_display_label($tax_conflict) . '.')
        : ($proforma_conflict
            ? ('Already on ' . billing_proforma_display_label($proforma_conflict) . '.')
            : 'GCN is not available.');

    return array(
        'key' => $trans_table . '|' . $transaction_id,
        'trans_table' => $trans_table,
        'transaction_id' => $transaction_id,
        'grn_no' => $d['grn_no'] ?? '',
        'grn_date' => $d['grn_date'] ?? '',
        'sender' => $sender,
        'receiver' => $receiver,
        'consigner_id' => (int) ($d['consigner_id'] ?? 0),
        'consignee_id' => (int) ($d['consignee_id'] ?? 0),
        'packages' => (int) ($d['packages'] ?? 0),
        'weight' => billing_format_money($d['weight'] ?? 0),
        'freight_amount' => billing_format_money($d['freight_amount'] ?? 0),
        'other_charges' => billing_format_money($d['other_charges'] ?? 0),
        'taxable_value' => billing_format_money($d['taxable_value'] ?? 0),
        'cgst_amount' => billing_format_money($d['cgst_amount'] ?? 0),
        'sgst_amount' => billing_format_money($d['sgst_amount'] ?? 0),
        'igst_amount' => billing_format_money($d['igst_amount'] ?? 0),
        'cess_amount' => billing_format_money($d['cess_amount'] ?? 0),
        'gst_amount' => billing_format_money($d['gst_amount'] ?? 0),
        'total_amount' => billing_format_money($d['total_amount'] ?? 0),
        'billing_type' => $bt,
        'billing_type_label' => isset($opts[$bt]) ? $opts[$bt] : strtoupper($bt),
        'mode_label' => '',
        'line_warning' => $warning,
        'has_conflict' => ($tax_conflict || $proforma_conflict) ? 1 : 0,
    );
}

function billing_proforma_fetch_delivered_gcns($conn, $customer_ids = array(), $exclude_proforma_id = 0, $billing_type = '')
{
    ensure_billing_proforma_tables($conn);
    ensure_transaction_gst_columns($conn, 'transaction');
    $billing_type = billing_normalize_billing_type($billing_type);

    $customer_filter = '';
    if (!empty($customer_ids)) {
        $ids = array_filter(array_map('intval', $customer_ids));
        if (!empty($ids)) {
            $id_list = implode(',', $ids);
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

        $sql = "SELECT t.transaction_id, t.grn_no, t.grn_date, t.consigner, t.consignee, t.total
            FROM `$trans_table` t
            WHERE t.status='8'
            AND (t.booking_status IS NULL OR t.booking_status='' OR t.booking_status!='1')
            $customer_filter
            ORDER BY STR_TO_DATE(t.grn_date,'%d-%m-%Y') DESC, t.grn_no DESC";

        $prev_report = mysqli_report(MYSQLI_REPORT_OFF);
        $q = mysqli_query($conn, $sql);
        mysqli_report($prev_report);
        if (!$q) {
            continue;
        }

        while ($row = mysqli_fetch_assoc($q)) {
            if (billing_proforma_blocks_gcn_selection($conn, $trans_table, $row['transaction_id'], $exclude_proforma_id)) {
                continue;
            }
            $total = (float) ($row['total'] ?? 0);
            $sender = get_client_name($conn, $row['consigner']);
            $receiver = get_client_name($conn, $row['consignee']);
            $label = $row['grn_no'] . ' | ' . $row['grn_date'] . ' | ' . $sender . ' | ' . $receiver . ' | ' . billing_format_money($total);
            $rows[] = array(
                'key' => $trans_table . '|' . $row['transaction_id'],
                'label' => $label,
                'grn_no' => $row['grn_no'],
                'grn_date' => $row['grn_date'],
                'amount' => billing_format_money($total),
                'consigner_id' => (int) $row['consigner'],
                'consignee_id' => (int) $row['consignee'],
            );
        }
    }

    if ($exclude_proforma_id > 0) {
        $existing = array();
        foreach ($rows as $row) {
            $existing[$row['key']] = true;
        }
        $dq = mysqli_query($conn, "SELECT * FROM billing_proforma_details WHERE billing_proforma_id='" . (int) $exclude_proforma_id . "' ORDER BY detail_id ASC");
        if ($dq) {
            while ($d = mysqli_fetch_assoc($dq)) {
                $key = preg_replace('/[^a-zA-Z0-9_]/', '', $d['trans_table']) . '|' . (int) $d['transaction_id'];
                if (isset($existing[$key])) {
                    continue;
                }
                $sender = get_client_name($conn, $d['consigner_id'] ?? 0);
                $receiver = get_client_name($conn, $d['consignee_id'] ?? 0);
                $tax_conflict = billing_gcn_final_conflict($conn, $d['trans_table'], $d['transaction_id'], 0);
                $suffix = $tax_conflict ? (' | Already on tax invoice') : ' | On this proforma';
                $rows[] = array(
                    'key' => $key,
                    'label' => ($d['grn_no'] ?? '') . ' | ' . ($d['grn_date'] ?? '') . ' | ' . $sender . ' | ' . $receiver . $suffix,
                    'grn_no' => $d['grn_no'] ?? '',
                    'grn_date' => $d['grn_date'] ?? '',
                    'amount' => billing_format_money($d['total_amount'] ?? 0),
                    'consigner_id' => (int) ($d['consigner_id'] ?? 0),
                    'consignee_id' => (int) ($d['consignee_id'] ?? 0),
                    'draft_line' => 1,
                    'has_conflict' => $tax_conflict ? 1 : 0,
                    'selectable' => $tax_conflict ? 0 : 1,
                );
            }
        }
    }

    return $rows;
}

function billing_proforma_get($conn, $billing_proforma_id)
{
    ensure_billing_proforma_tables($conn);
    $billing_proforma_id = (int) $billing_proforma_id;
    $q = mysqli_query($conn, "SELECT * FROM billing_proforma_master WHERE billing_proforma_id='$billing_proforma_id' LIMIT 1");
    if (!$q || !($master = mysqli_fetch_assoc($q))) {
        return null;
    }
    $details = array();
    $dq = mysqli_query($conn, "SELECT * FROM billing_proforma_details WHERE billing_proforma_id='$billing_proforma_id' ORDER BY detail_id ASC");
    if ($dq) {
        while ($d = mysqli_fetch_assoc($dq)) {
            $details[] = $d;
        }
    }

    return array('master' => $master, 'details' => $details);
}

function billing_proforma_save($conn, $payload, $user_id)
{
    ensure_billing_proforma_tables($conn);

    $invoice_date = trim($payload['invoice_date'] ?? date('d-m-Y'));
    $customer_id = (int) ($payload['customer_id'] ?? 0);
    $billing_type_raw = trim($payload['billing_type'] ?? '');
    $status = ($payload['status'] ?? 'draft') === 'final' ? 'final' : 'draft';
    $edit_id = (int) ($payload['billing_proforma_id'] ?? 0);
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
        $validated = billing_proforma_validate_gcn($conn, $parsed['trans_table'], $parsed['transaction_id'], $customer_id, $bt, $edit_id);
        if (empty($validated['ok'])) {
            return array('status' => 1, 'message' => $validated['message'] ?? ('GCN not available: ' . ($line['grn_no'] ?? $key)));
        }
        $parsed_lines[] = $validated['detail'];
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
    $doc_style = billing_proforma_resolve_doc_style($totals);
    if ($doc_style === 'other') {
        $totals['grand_total'] = billing_format_money((float) $totals['taxable_value']);
        $totals['cgst_amount'] = '0.00';
        $totals['sgst_amount'] = '0.00';
        $totals['igst_amount'] = '0.00';
        $totals['cess_amount'] = '0.00';
        $totals['gst_amount'] = '0.00';
    }
    $total_words = gst_tax_report_amount_in_words((float) $totals['grand_total']);

    $proforma_no = '';
    $existing = $edit_id > 0 ? billing_proforma_get($conn, $edit_id) : null;
    if ($existing && !empty($existing['master']['proforma_no'])) {
        $proforma_no = trim((string) $existing['master']['proforma_no']);
        if ($existing['master']['status'] === 'final' && $status === 'draft') {
            return array('status' => 1, 'message' => 'Final proforma cannot be moved back to draft.');
        }
    }
    if ($proforma_no === '' && ($status === 'final' || $status === 'draft')) {
        $proforma_no = billing_proforma_allocate_number($conn, $invoice_date, $user_id);
    }
    if ($proforma_no === '' && $status === 'final') {
        return array('status' => 1, 'message' => 'Could not generate proforma number.');
    }
    if ($proforma_no !== '' && billing_proforma_number_exists($conn, $proforma_no, $edit_id)) {
        return array('status' => 1, 'message' => 'Proforma number already exists. Refresh and try again.');
    }

    $esc_date = mysqli_real_escape_string($conn, $invoice_date);
    $esc_no = mysqli_real_escape_string($conn, $proforma_no);
    $esc_words = mysqli_real_escape_string($conn, $total_words);
    $esc_style = mysqli_real_escape_string($conn, $doc_style);

    if ($edit_id > 0) {
        if (!$existing || $existing['master']['status'] === 'cancelled') {
            return array('status' => 1, 'message' => 'Proforma not found.');
        }
        mysqli_query($conn, "UPDATE billing_proforma_master SET
            proforma_no='$esc_no',
            invoice_date='$esc_date',
            customer_id='$customer_id',
            billing_type='$billing_type',
            doc_style='$esc_style',
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
            WHERE billing_proforma_id='$edit_id'");
        mysqli_query($conn, "DELETE FROM billing_proforma_details WHERE billing_proforma_id='$edit_id'");
        $billing_proforma_id = $edit_id;
    } else {
        mysqli_query($conn, "INSERT INTO billing_proforma_master
            (proforma_no, invoice_date, customer_id, billing_type, doc_style, status,
             total_freight, total_other, taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, gst_amount, grand_total, total_words, created_at, created_by, updated_at, updated_by)
            VALUES
            ('$esc_no','$esc_date','$customer_id','$billing_type','$esc_style','$status',
             '{$totals['total_freight']}','{$totals['total_other']}','{$totals['taxable_value']}',
             '{$totals['cgst_amount']}','{$totals['sgst_amount']}','{$totals['igst_amount']}','{$totals['cess_amount']}','{$totals['gst_amount']}','{$totals['grand_total']}','$esc_words','$now','$user_id','$now','$user_id')");
        $billing_proforma_id = (int) mysqli_insert_id($conn);
    }

    foreach ($parsed_lines as $line) {
        $esc_tbl = mysqli_real_escape_string($conn, $line['trans_table']);
        $esc_grn = mysqli_real_escape_string($conn, $line['grn_no']);
        $esc_gdate = mysqli_real_escape_string($conn, $line['grn_date']);
        $esc_bt = mysqli_real_escape_string($conn, $line['billing_type']);
        mysqli_query($conn, "INSERT INTO billing_proforma_details
            (billing_proforma_id, trans_table, transaction_id, grn_no, grn_date, consigner_id, consignee_id,
             packages, weight, freight_amount, other_charges, taxable_value, cgst_amount, sgst_amount, igst_amount,
             cess_amount, gst_amount, total_amount, billing_type, created_at)
            VALUES
            ('$billing_proforma_id','$esc_tbl','{$line['transaction_id']}','$esc_grn','$esc_gdate',
             '{$line['consigner_id']}','{$line['consignee_id']}','{$line['packages']}','{$line['weight']}',
             '{$line['freight_amount']}','{$line['other_charges']}','{$line['taxable_value']}',
             '{$line['cgst_amount']}','{$line['sgst_amount']}','{$line['igst_amount']}','{$line['cess_amount']}',
             '{$line['gst_amount']}','{$line['total_amount']}','$esc_bt','$now')");
    }

    return array(
        'status' => 0,
        'message' => $status === 'final' ? 'Proforma generated successfully.' : 'Proforma draft saved.',
        'billing_proforma_id' => $billing_proforma_id,
        'proforma_no' => $proforma_no,
        'doc_style' => $doc_style,
        'proforma_status' => $status,
    );
}
