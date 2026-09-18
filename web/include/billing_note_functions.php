<?php

function ensure_billing_note_tables($conn)
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_note_master (
        billing_note_id INT AUTO_INCREMENT PRIMARY KEY,
        note_no VARCHAR(50) DEFAULT NULL,
        note_date VARCHAR(20) NOT NULL,
        note_type ENUM('CN','DN') NOT NULL DEFAULT 'CN',
        party_type ENUM('client','vendor') NOT NULL DEFAULT 'client',
        party_id INT NOT NULL DEFAULT 0,
        against_invoice_id INT NOT NULL DEFAULT 0,
        reason VARCHAR(255) DEFAULT NULL,
        line_description TEXT,
        taxable_value DECIMAL(14,2) NOT NULL DEFAULT 0,
        cgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        sgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        igst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        grand_total DECIMAL(14,2) NOT NULL DEFAULT 0,
        gst_split VARCHAR(20) DEFAULT NULL,
        gst_rate DECIMAL(8,4) NOT NULL DEFAULT 0,
        total_words TEXT,
        status ENUM('draft','final','cancelled') NOT NULL DEFAULT 'draft',
        created_at DATETIME DEFAULT NULL,
        created_by INT DEFAULT NULL,
        updated_at DATETIME DEFAULT NULL,
        updated_by INT DEFAULT NULL,
        KEY idx_invoice (against_invoice_id),
        KEY idx_party (party_type, party_id),
        KEY idx_status (status),
        KEY idx_note_no (note_no),
        KEY idx_type (note_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_note_lines (
        line_id INT AUTO_INCREMENT PRIMARY KEY,
        billing_note_id INT NOT NULL,
        grn_no VARCHAR(50) DEFAULT NULL,
        description TEXT,
        taxable_value DECIMAL(14,2) DEFAULT 0,
        cgst_amount DECIMAL(14,2) DEFAULT 0,
        sgst_amount DECIMAL(14,2) DEFAULT 0,
        igst_amount DECIMAL(14,2) DEFAULT 0,
        gst_amount DECIMAL(14,2) DEFAULT 0,
        total_amount DECIMAL(14,2) DEFAULT 0,
        created_at DATETIME DEFAULT NULL,
        KEY idx_note (billing_note_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function billing_credit_note_reasons()
{
    return array(
        'Freight billed higher than agreed rate',
        'Wrong weight / extra weight billed',
        'Rate difference after client confirmation',
        'Other',
    );
}

function billing_invoice_gst_profile($master)
{
    $taxable = (float) ($master['taxable_value'] ?? 0);
    $gst = (float) ($master['gst_amount'] ?? 0);
    $igst = (float) ($master['igst_amount'] ?? 0);
    $rate = ($taxable > 0) ? round($gst / $taxable, 4) : 0.0;
    $split = ($igst > 0.009) ? 'igst' : 'cgst_sgst';
    $pct = billing_format_money($rate * 100);
    $label = $split === 'igst' ? ($pct . '% IGST') : ($pct . '% CGST+SGST');
    if ($gst <= 0.009) {
        $label = 'No GST on invoice';
    }
    return array(
        'gst_rate' => $rate,
        'gst_split' => $split,
        'gst_label' => $label,
    );
}

function billing_note_compute_gst($taxable, $profile)
{
    $taxable = round((float) $taxable, 2);
    $rate = (float) ($profile['gst_rate'] ?? 0);
    $gst = round($taxable * $rate, 2);
    if (($profile['gst_split'] ?? 'igst') === 'igst') {
        return array(
            'taxable_value' => billing_format_money($taxable),
            'cgst_amount' => billing_format_money(0),
            'sgst_amount' => billing_format_money(0),
            'igst_amount' => billing_format_money($gst),
            'gst_amount' => billing_format_money($gst),
            'grand_total' => billing_format_money($taxable + $gst),
        );
    }
    $cgst = round($gst / 2, 2);
    $sgst = round($gst - $cgst, 2);
    return array(
        'taxable_value' => billing_format_money($taxable),
        'cgst_amount' => billing_format_money($cgst),
        'sgst_amount' => billing_format_money($sgst),
        'igst_amount' => billing_format_money(0),
        'gst_amount' => billing_format_money($cgst + $sgst),
        'grand_total' => billing_format_money($taxable + $cgst + $sgst),
    );
}

function billing_invoice_note_total($conn, $invoice_id, $note_type = 'CN', $exclude_note_id = 0)
{
    $invoice_id = (int) $invoice_id;
    $exclude_note_id = (int) $exclude_note_id;
    $note_type = ($note_type === 'DN') ? 'DN' : 'CN';
    $sql = "SELECT COALESCE(SUM(grand_total),0) AS amt
        FROM billing_note_master
        WHERE against_invoice_id='$invoice_id'
        AND note_type='$note_type'
        AND party_type='client'
        AND status='final'";
    if ($exclude_note_id > 0) {
        $sql .= " AND billing_note_id!='$exclude_note_id'";
    }
    $q = mysqli_query($conn, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) $row['amt'];
    }
    return 0.0;
}

function billing_invoice_cn_remaining($conn, $master, $exclude_note_id = 0)
{
    $grand = (float) ($master['grand_total'] ?? 0);
    $used = billing_invoice_note_total($conn, (int) $master['billing_invoice_id'], 'CN', $exclude_note_id);
    return max(0, round($grand - $used, 2));
}

function billing_next_credit_note_seq($conn)
{
    $q = mysqli_query($conn, "SELECT COALESCE(MAX(CAST(SUBSTRING(note_no, 7) AS UNSIGNED)),0) AS n
        FROM billing_note_master
        WHERE note_type='CN' AND note_no LIKE 'EW-CN/%'");
    $n = 0;
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $n = (int) $row['n'];
    }
    return $n + 1;
}

function billing_format_credit_note_no($seq)
{
    return 'EW-CN/' . sprintf('%05d', (int) $seq);
}

function billing_preview_credit_note_number($conn)
{
    return billing_format_credit_note_no(billing_next_credit_note_seq($conn));
}

function billing_allocate_credit_note_number($conn)
{
    return billing_format_credit_note_no(billing_next_credit_note_seq($conn));
}

function billing_get_credit_note($conn, $billing_note_id)
{
    $billing_note_id = (int) $billing_note_id;
    $q = mysqli_query($conn, "SELECT * FROM billing_note_master WHERE billing_note_id='$billing_note_id' AND note_type='CN' LIMIT 1");
    if (!$q || !($master = mysqli_fetch_assoc($q))) {
        return null;
    }
    $lines = array();
    $dq = mysqli_query($conn, "SELECT * FROM billing_note_lines WHERE billing_note_id='$billing_note_id' ORDER BY line_id ASC");
    if ($dq) {
        while ($d = mysqli_fetch_assoc($dq)) {
            $lines[] = $d;
        }
    }
    return array('master' => $master, 'lines' => $lines);
}

function billing_credit_note_invoice_snapshot($conn, $invoice_id, $exclude_note_id = 0)
{
    $invoice = billing_get_invoice($conn, (int) $invoice_id);
    if (!$invoice) {
        return null;
    }
    $master = $invoice['master'];
    $cust_name = '';
    $cid = (int) $master['customer_id'];
    $cq = mysqli_query($conn, "SELECT client_company_name FROM client WHERE client_id='$cid' LIMIT 1");
    if ($cq && ($c = mysqli_fetch_assoc($cq))) {
        $cust_name = $c['client_company_name'];
    }
    $gcns = array();
    foreach ($invoice['details'] as $d) {
        if (!empty($d['grn_no'])) {
            $gcns[] = $d['grn_no'];
        }
    }
    $profile = billing_invoice_gst_profile($master);
    $cn_total = billing_invoice_note_total($conn, (int) $master['billing_invoice_id'], 'CN', $exclude_note_id);
    $dn_total = billing_invoice_note_total($conn, (int) $master['billing_invoice_id'], 'DN', $exclude_note_id);
    $paid = 0.0;
    $inv_amt = (float) $master['grand_total'];
    $to_pay = max(0, round($inv_amt - $paid - $cn_total + $dn_total, 2));
    $remaining = billing_invoice_cn_remaining($conn, $master, $exclude_note_id);
    return array(
        'billing_invoice_id' => (int) $master['billing_invoice_id'],
        'invoice_no' => $master['invoice_no'],
        'invoice_date' => $master['invoice_date'],
        'customer_id' => $cid,
        'customer_name' => $cust_name,
        'status' => $master['status'],
        'invoice_amount' => billing_format_money($inv_amt),
        'taxable_value' => billing_format_money($master['taxable_value']),
        'already_paid' => billing_format_money($paid),
        'credit_note_total' => billing_format_money($cn_total),
        'debit_note_total' => billing_format_money($dn_total),
        'to_pay' => billing_format_money($to_pay),
        'cn_remaining' => billing_format_money($remaining),
        'gst_rate' => $profile['gst_rate'],
        'gst_split' => $profile['gst_split'],
        'gst_label' => $profile['gst_label'],
        'gcns' => $gcns,
        'gcn_label' => implode(', ', $gcns),
    );
}

function billing_final_invoices_for_credit_note($conn)
{
    $rows = array();
    $q = mysqli_query($conn, "SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.grand_total, c.client_company_name
        FROM billing_invoice_master m
        LEFT JOIN client c ON c.client_id = m.customer_id
        WHERE m.status='final'
        ORDER BY m.billing_invoice_id DESC
        LIMIT 500");
    if (!$q) {
        return $rows;
    }
    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = $row;
    }
    return $rows;
}

function billing_save_credit_note($conn, $payload, $user_id)
{
    ensure_billing_tables($conn);

    $edit_id = (int) ($payload['billing_note_id'] ?? 0);
    $invoice_id = (int) ($payload['against_invoice_id'] ?? 0);
    $note_date = trim($payload['note_date'] ?? date('d-m-Y'));
    $reason = trim($payload['reason'] ?? '');
    $description = trim($payload['line_description'] ?? '');
    $taxable = round((float) ($payload['taxable_value'] ?? 0), 2);
    $status = ($payload['status'] ?? 'draft') === 'final' ? 'final' : 'draft';
    $now = date('Y-m-d H:i:s');
    $user_id = (int) $user_id;

    if ($invoice_id <= 0) {
        return array('status' => 1, 'message' => 'Please select a Final tax invoice.');
    }
    if ($reason === '') {
        return array('status' => 1, 'message' => 'Please select a reason.');
    }
    if ($reason === 'Other' && $description === '') {
        return array('status' => 1, 'message' => 'Please enter a description for Other reason.');
    }
    if ($taxable <= 0) {
        return array('status' => 1, 'message' => 'Enter a taxable amount greater than zero.');
    }

    $invoice = billing_get_invoice($conn, $invoice_id);
    if (!$invoice || $invoice['master']['status'] !== 'final') {
        return array('status' => 1, 'message' => 'Credit notes can be raised only against a Final tax invoice.');
    }

    $existing = null;
    if ($edit_id > 0) {
        $existing = billing_get_credit_note($conn, $edit_id);
        if (!$existing) {
            return array('status' => 1, 'message' => 'Credit note not found.');
        }
        if ($existing['master']['status'] === 'cancelled') {
            return array('status' => 1, 'message' => 'Cancelled credit note cannot be edited.');
        }
        if ($existing['master']['status'] === 'final' && $status === 'draft') {
            return array('status' => 1, 'message' => 'Final credit note cannot be moved back to draft.');
        }
        if ($existing['master']['status'] === 'final') {
            return array('status' => 1, 'message' => 'Final credit note cannot be edited.');
        }
    }

    $profile = billing_invoice_gst_profile($invoice['master']);
    $amounts = billing_note_compute_gst($taxable, $profile);
    $remaining = billing_invoice_cn_remaining($conn, $invoice['master'], $edit_id);
    if ((float) $amounts['grand_total'] - $remaining > 0.009) {
        return array('status' => 1, 'message' => 'Credit note total cannot exceed remaining invoice value of ' . billing_format_money($remaining) . '.');
    }

    $gcns = array();
    foreach ($invoice['details'] as $d) {
        if (!empty($d['grn_no'])) {
            $gcns[] = $d['grn_no'];
        }
    }
    $gcn_label = implode(', ', $gcns);
    if ($description === '') {
        $description = 'Freight reversal' . ($gcn_label !== '' ? ' — ' . $gcn_label : '');
    }

    $note_no = '';
    if ($status === 'final') {
        $note_no = billing_allocate_credit_note_number($conn);
    } elseif ($edit_id > 0 && !empty($existing['master']['note_no'])) {
        $note_no = $existing['master']['note_no'];
    }

    $esc_date = mysqli_real_escape_string($conn, $note_date);
    $esc_no = mysqli_real_escape_string($conn, $note_no);
    $esc_reason = mysqli_real_escape_string($conn, $reason);
    $esc_desc = mysqli_real_escape_string($conn, $description);
    $esc_split = mysqli_real_escape_string($conn, $profile['gst_split']);
    $gst_rate = billing_format_money($profile['gst_rate']);
    $words = '';
    if (function_exists('gst_tax_report_amount_in_words')) {
        $words = gst_tax_report_amount_in_words((float) $amounts['grand_total']);
    }
    $esc_words = mysqli_real_escape_string($conn, $words);
    $party_id = (int) $invoice['master']['customer_id'];

    if ($edit_id > 0) {
        mysqli_query($conn, "UPDATE billing_note_master SET
            note_no='$esc_no',
            note_date='$esc_date',
            party_id='$party_id',
            against_invoice_id='$invoice_id',
            reason='$esc_reason',
            line_description='$esc_desc',
            taxable_value='{$amounts['taxable_value']}',
            cgst_amount='{$amounts['cgst_amount']}',
            sgst_amount='{$amounts['sgst_amount']}',
            igst_amount='{$amounts['igst_amount']}',
            gst_amount='{$amounts['gst_amount']}',
            grand_total='{$amounts['grand_total']}',
            gst_split='$esc_split',
            gst_rate='$gst_rate',
            total_words='$esc_words',
            status='$status',
            updated_at='$now',
            updated_by='$user_id'
            WHERE billing_note_id='$edit_id'");
        mysqli_query($conn, "DELETE FROM billing_note_lines WHERE billing_note_id='$edit_id'");
        $billing_note_id = $edit_id;
    } else {
        mysqli_query($conn, "INSERT INTO billing_note_master
            (note_no, note_date, note_type, party_type, party_id, against_invoice_id, reason, line_description,
             taxable_value, cgst_amount, sgst_amount, igst_amount, gst_amount, grand_total, gst_split, gst_rate, total_words,
             status, created_at, created_by, updated_at, updated_by)
            VALUES
            ('$esc_no','$esc_date','CN','client','$party_id','$invoice_id','$esc_reason','$esc_desc',
             '{$amounts['taxable_value']}','{$amounts['cgst_amount']}','{$amounts['sgst_amount']}','{$amounts['igst_amount']}',
             '{$amounts['gst_amount']}','{$amounts['grand_total']}','$esc_split','$gst_rate','$esc_words',
             '$status','$now','$user_id','$now','$user_id')");
        $billing_note_id = (int) mysqli_insert_id($conn);
        if ($billing_note_id <= 0) {
            return array('status' => 1, 'message' => 'Could not save credit note.');
        }
    }

    $esc_grn = mysqli_real_escape_string($conn, $gcn_label);
    mysqli_query($conn, "INSERT INTO billing_note_lines
        (billing_note_id, grn_no, description, taxable_value, cgst_amount, sgst_amount, igst_amount, gst_amount, total_amount, created_at)
        VALUES
        ('$billing_note_id','$esc_grn','$esc_desc','{$amounts['taxable_value']}','{$amounts['cgst_amount']}',
         '{$amounts['sgst_amount']}','{$amounts['igst_amount']}','{$amounts['gst_amount']}','{$amounts['grand_total']}','$now')");

    return array(
        'status' => 0,
        'message' => $status === 'final' ? 'Credit note finalised.' : 'Credit note draft saved.',
        'billing_note_id' => $billing_note_id,
        'note_no' => $note_no,
        'note_status' => $status,
        'amounts' => $amounts,
    );
}
