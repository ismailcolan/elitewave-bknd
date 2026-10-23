<?php

require_once __DIR__ . '/company_bank_helpers.php';

function ensure_billing_receipt_tables($conn)
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_receipt_master (
        billing_receipt_id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_no VARCHAR(50) DEFAULT NULL,
        receipt_date VARCHAR(20) NOT NULL,
        receipt_type ENUM('against_invoice','advance','investment','general') NOT NULL DEFAULT 'against_invoice',
        party_id INT NOT NULL DEFAULT 0,
        payer_name VARCHAR(255) DEFAULT NULL,
        total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        tds_total DECIMAL(14,2) NOT NULL DEFAULT 0,
        payment_mode VARCHAR(30) DEFAULT NULL,
        payment_ref VARCHAR(120) DEFAULT NULL,
        bank_account_id INT DEFAULT NULL,
        remarks TEXT,
        status ENUM('final','cancelled') NOT NULL DEFAULT 'final',
        created_at DATETIME DEFAULT NULL,
        created_by INT DEFAULT NULL,
        updated_at DATETIME DEFAULT NULL,
        updated_by INT DEFAULT NULL,
        KEY idx_receipt_no (receipt_no),
        KEY idx_type (receipt_type),
        KEY idx_party (party_id),
        KEY idx_date (receipt_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $col_q = mysqli_query($conn, "SHOW COLUMNS FROM billing_receipt_master LIKE 'payment_ref_date'");
    if ($col_q && mysqli_num_rows($col_q) === 0) {
        mysqli_query($conn, "ALTER TABLE billing_receipt_master ADD COLUMN payment_ref_date VARCHAR(20) DEFAULT NULL AFTER payment_ref");
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_receipt_lines (
        line_id INT AUTO_INCREMENT PRIMARY KEY,
        billing_receipt_id INT NOT NULL,
        billing_invoice_id INT NOT NULL DEFAULT 0,
        invoice_no VARCHAR(50) DEFAULT NULL,
        invoice_date VARCHAR(20) DEFAULT NULL,
        invoice_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        already_received DECIMAL(14,2) NOT NULL DEFAULT 0,
        credit_note_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        tds_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        balance_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        receive_now DECIMAL(14,2) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT NULL,
        KEY idx_receipt (billing_receipt_id),
        KEY idx_invoice (billing_invoice_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_receipt_attachments (
        attachment_id INT AUTO_INCREMENT PRIMARY KEY,
        billing_receipt_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT NULL,
        KEY idx_receipt (billing_receipt_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function billing_receipt_type_options()
{
    return array(
        'against_invoice' => 'Against Invoice',
        'advance' => 'Advance',
        'investment' => 'Investment',
        'general' => 'General',
    );
}

function billing_receipt_type_label($type)
{
    $opts = billing_receipt_type_options();
    return isset($opts[$type]) ? $opts[$type] : $type;
}

/** TDS % choices for payment receipts (from GST / TDS master). */
function billing_receipt_tds_percent_options($conn)
{
    if (function_exists('ensure_gst_tax_master_table')) {
        ensure_gst_tax_master_table($conn);
    }
    $out = array();
    $q = @mysqli_query($conn, "SELECT gst_tax_id, tax_code, tax_name, tds_rate FROM gst_tax_master
        WHERE COALESCE(is_deleted,0)=0 AND status=1 AND tds_rate > 0
        ORDER BY tds_rate ASC, tax_code ASC");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $rate = round((float) ($row['tds_rate'] ?? 0), 2);
            if ($rate <= 0) {
                continue;
            }
            $code = trim((string) ($row['tax_code'] ?? ''));
            $name = trim((string) ($row['tax_name'] ?? ''));
            $label = $name !== '' ? $name : ('TDS ' . $rate . '%');
            if ($code !== '') {
                $label = $code . ' — ' . $label;
            }
            $out[] = array(
                'gst_tax_id' => (int) $row['gst_tax_id'],
                'tax_code' => $code,
                'label' => $label,
                'rate' => $rate,
            );
        }
    }
    return $out;
}

function billing_invoice_received_total($conn, $invoice_id, $exclude_receipt_id = 0)
{
    $invoice_id = (int) $invoice_id;
    $exclude_receipt_id = (int) $exclude_receipt_id;
    $sql = "SELECT COALESCE(SUM(l.receive_now),0) AS amt
        FROM billing_receipt_lines l
        INNER JOIN billing_receipt_master m ON m.billing_receipt_id = l.billing_receipt_id
        WHERE l.billing_invoice_id='$invoice_id'
        AND m.status='final'
        AND m.receipt_type='against_invoice'";
    if ($exclude_receipt_id > 0) {
        $sql .= " AND m.billing_receipt_id!='$exclude_receipt_id'";
    }
    $q = mysqli_query($conn, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) $row['amt'];
    }
    return 0.0;
}

function billing_invoice_tds_total($conn, $invoice_id, $exclude_receipt_id = 0)
{
    $invoice_id = (int) $invoice_id;
    $exclude_receipt_id = (int) $exclude_receipt_id;
    $sql = "SELECT COALESCE(SUM(l.tds_amount),0) AS amt
        FROM billing_receipt_lines l
        INNER JOIN billing_receipt_master m ON m.billing_receipt_id = l.billing_receipt_id
        WHERE l.billing_invoice_id='$invoice_id'
        AND m.status='final'
        AND m.receipt_type='against_invoice'";
    if ($exclude_receipt_id > 0) {
        $sql .= " AND m.billing_receipt_id!='$exclude_receipt_id'";
    }
    $q = mysqli_query($conn, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) $row['amt'];
    }
    return 0.0;
}

function billing_invoice_settlement_snapshot($conn, $invoice_id, $exclude_receipt_id = 0)
{
    $invoice_id = (int) $invoice_id;
    $inv = billing_get_invoice($conn, $invoice_id);
    if (!$inv || $inv['master']['status'] !== 'final') {
        return null;
    }
    $m = $inv['master'];
    $inv_amt = (float) $m['grand_total'];
    $paid = billing_invoice_received_total($conn, $invoice_id, $exclude_receipt_id);
    $cn = billing_invoice_note_total($conn, $invoice_id, 'CN', 0);
    $dn = billing_invoice_note_total($conn, $invoice_id, 'DN', 0);
    $tds_prior = billing_invoice_tds_total($conn, $invoice_id, $exclude_receipt_id);
    $balance = round(max(0, $inv_amt - $paid - $cn + $dn - $tds_prior), 2);

    return array(
        'billing_invoice_id' => $invoice_id,
        'invoice_no' => $m['invoice_no'],
        'invoice_date' => $m['invoice_date'],
        'customer_id' => (int) $m['customer_id'],
        'invoice_amount' => billing_format_money($inv_amt),
        'already_received' => billing_format_money($paid),
        'credit_note' => billing_format_money($cn),
        'debit_note' => billing_format_money($dn),
        'tds_prior' => billing_format_money($tds_prior),
        'balance' => billing_format_money($balance),
    );
}

function billing_next_receipt_seq($conn)
{
    $q = mysqli_query($conn, "SELECT COALESCE(MAX(CAST(SUBSTRING(receipt_no, 8) AS UNSIGNED)),0) AS n
        FROM billing_receipt_master
        WHERE receipt_no LIKE 'EW-RCP/%'");
    $n = 0;
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $n = (int) $row['n'];
    }
    return $n + 1;
}

function billing_format_receipt_no($seq)
{
    return 'EW-RCP/' . sprintf('%05d', (int) $seq);
}

function billing_preview_receipt_number($conn)
{
    return billing_format_receipt_no(billing_next_receipt_seq($conn));
}

function billing_allocate_receipt_number($conn)
{
    return billing_format_receipt_no(billing_next_receipt_seq($conn));
}

function billing_receipt_upload_dir()
{
    $dir = dirname(__DIR__) . '/receipt_attachments';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function billing_receipt_save_attachments($conn, $billing_receipt_id, $files)
{
    $billing_receipt_id = (int) $billing_receipt_id;
    if ($billing_receipt_id <= 0 || empty($files['name']) || !is_array($files['name'])) {
        return;
    }
    $dir = billing_receipt_upload_dir();
    $now = date('Y-m-d H:i:s');
    $order = 0;
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if (empty($files['name'][$i]) || (int) ($files['error'][$i] ?? 1) !== UPLOAD_ERR_OK) {
            continue;
        }
        $orig = basename($files['name'][$i]);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $safe = 'rcp_' . $billing_receipt_id . '_' . time() . '_' . $i . ($ext ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '');
        $dest = $dir . '/' . $safe;
        if (!move_uploaded_file($files['tmp_name'][$i], $dest)) {
            continue;
        }
        $rel = 'receipt_attachments/' . $safe;
        $esc_path = mysqli_real_escape_string($conn, $rel);
        $esc_orig = mysqli_real_escape_string($conn, $orig);
        mysqli_query($conn, "INSERT INTO billing_receipt_attachments
            (billing_receipt_id, file_path, original_name, sort_order, created_at)
            VALUES ('$billing_receipt_id','$esc_path','$esc_orig','$order','$now')");
        $order++;
    }
}

function billing_get_receipt($conn, $billing_receipt_id)
{
    $billing_receipt_id = (int) $billing_receipt_id;
    $q = mysqli_query($conn, "SELECT * FROM billing_receipt_master WHERE billing_receipt_id='$billing_receipt_id' LIMIT 1");
    if (!$q || !($master = mysqli_fetch_assoc($q))) {
        return null;
    }
    $lines = array();
    $lq = mysqli_query($conn, "SELECT * FROM billing_receipt_lines WHERE billing_receipt_id='$billing_receipt_id' ORDER BY line_id ASC");
    if ($lq) {
        while ($row = mysqli_fetch_assoc($lq)) {
            $lines[] = $row;
        }
    }
    $attachments = array();
    $aq = mysqli_query($conn, "SELECT * FROM billing_receipt_attachments WHERE billing_receipt_id='$billing_receipt_id' ORDER BY sort_order ASC, attachment_id ASC");
    if ($aq) {
        while ($row = mysqli_fetch_assoc($aq)) {
            $attachments[] = $row;
        }
    }
    return array('master' => $master, 'lines' => $lines, 'attachments' => $attachments);
}

function billing_save_receipt($conn, $payload, $user_id, $files = null)
{
    ensure_billing_tables($conn);
    ensure_billing_receipt_tables($conn);
    ew_company_bank_ensure_schema($conn);

    $receipt_type = trim($payload['receipt_type'] ?? 'against_invoice');
    $opts = billing_receipt_type_options();
    if (!isset($opts[$receipt_type])) {
        $receipt_type = 'against_invoice';
    }
    $receipt_date = trim($payload['receipt_date'] ?? date('d-m-Y'));
    $party_id = (int) ($payload['party_id'] ?? 0);
    $payer_name = trim($payload['payer_name'] ?? '');
    $payment_mode = trim($payload['payment_mode'] ?? '');
    $payment_ref = trim($payload['payment_ref'] ?? '');
    $payment_ref_date = trim($payload['payment_ref_date'] ?? '');
    $bank_account_id = (int) ($payload['bank_account_id'] ?? 0);
    $remarks = trim($payload['remarks'] ?? '');
    $now = date('Y-m-d H:i:s');
    $user_id = (int) $user_id;

    if ($payment_mode === '') {
        return array('status' => 1, 'message' => 'Please select payment mode.');
    }

    $mode_upper = strtoupper($payment_mode);
    if (ew_company_bank_mode_requires_account($mode_upper)) {
        if ($payment_ref === '') {
            $ref_labels = array(
                'CHEQUE' => 'cheque number',
                'NEFT' => 'transaction number',
                'RTGS' => 'transaction number',
                'IMPS' => 'transaction number',
                'DD' => 'DD number',
            );
            $need = isset($ref_labels[$mode_upper]) ? $ref_labels[$mode_upper] : 'reference number';
            return array('status' => 1, 'message' => 'Please enter ' . $need . '.');
        }
        if ($payment_ref_date === '') {
            return array('status' => 1, 'message' => 'Please enter payment date.');
        }
        if ($bank_account_id <= 0) {
            return array('status' => 1, 'message' => 'Please select EliteWave360 bank account credited.');
        }
    } elseif ($mode_upper === 'CASH') {
        $payment_ref = '';
        $payment_ref_date = '';
        $bank_account_id = 0;
    }

    $update_id = (int) ($payload['billing_receipt_id'] ?? 0);
    if ($update_id > 0) {
        $exist_q = mysqli_query($conn, "SELECT billing_receipt_id, receipt_no, status FROM billing_receipt_master WHERE billing_receipt_id='$update_id' LIMIT 1");
        $exist = ($exist_q && ($exist_row = mysqli_fetch_assoc($exist_q))) ? $exist_row : null;
        if (!$exist || ($exist['status'] ?? '') !== 'final') {
            return array('status' => 1, 'message' => 'Receipt not found or cannot be edited.');
        }
    }

    $lines_in = isset($payload['lines']) && is_array($payload['lines']) ? $payload['lines'] : array();
    $total_amount = 0.0;
    $tds_total = 0.0;
    $parsed_lines = array();

    if ($receipt_type === 'against_invoice') {
        if ($party_id <= 0) {
            return array('status' => 1, 'message' => 'Please select customer / payer.');
        }
        if (empty($lines_in)) {
            return array('status' => 1, 'message' => 'Please select at least one invoice.');
        }
        foreach ($lines_in as $line) {
            $inv_id = (int) ($line['billing_invoice_id'] ?? 0);
            $receive = round((float) ($line['receive_now'] ?? 0), 2);
            $tds = round((float) ($line['tds_amount'] ?? 0), 2);
            if ($inv_id <= 0 || $receive <= 0) {
                continue;
            }
            $snap = billing_invoice_settlement_snapshot($conn, $inv_id, $update_id);
            if (!$snap) {
                return array('status' => 1, 'message' => 'Invalid or non-final invoice selected.');
            }
            if ((int) $snap['customer_id'] !== $party_id) {
                return array('status' => 1, 'message' => 'Invoice ' . $snap['invoice_no'] . ' does not belong to selected customer.');
            }
            $balance = (float) $snap['balance'];
            if ($receive + $tds - $balance > 0.009) {
                return array('status' => 1, 'message' => 'Receive plus TDS exceeds outstanding balance on invoice ' . $snap['invoice_no'] . '.');
            }
            $parsed_lines[] = array(
                'billing_invoice_id' => $inv_id,
                'invoice_no' => $snap['invoice_no'],
                'invoice_date' => $snap['invoice_date'],
                'invoice_amount' => (float) $snap['invoice_amount'],
                'already_received' => (float) $snap['already_received'],
                'credit_note_amount' => (float) $snap['credit_note'],
                'tds_amount' => $tds,
                'balance_amount' => $balance,
                'receive_now' => $receive,
            );
            $total_amount += $receive;
            $tds_total += $tds;
        }
        if (empty($parsed_lines)) {
            return array('status' => 1, 'message' => 'Enter receive amount on at least one invoice line.');
        }
    } else {
        $total_amount = round((float) ($payload['total_amount'] ?? 0), 2);
        if ($total_amount <= 0) {
            return array('status' => 1, 'message' => 'Enter a valid amount.');
        }
        if ($party_id <= 0 && $payer_name === '') {
            return array('status' => 1, 'message' => 'Select customer / payer or enter payer name.');
        }
    }

    $esc_date = mysqli_real_escape_string($conn, $receipt_date);
    $esc_type = mysqli_real_escape_string($conn, $receipt_type);
    $esc_payer = mysqli_real_escape_string($conn, $payer_name);
    $esc_mode = mysqli_real_escape_string($conn, $payment_mode);
    $esc_ref = mysqli_real_escape_string($conn, $payment_ref);
    $esc_ref_date = mysqli_real_escape_string($conn, $payment_ref_date);
    $esc_remarks = mysqli_real_escape_string($conn, $remarks);
    $amt = billing_format_money($total_amount);
    $tds = billing_format_money($tds_total);

    if ($update_id > 0) {
        $billing_receipt_id = $update_id;
        $receipt_no = $exist['receipt_no'];
        $bank_sql = ($bank_account_id > 0 ? "'$bank_account_id'" : 'NULL');
        $ref_date_sql = ($payment_ref_date !== '' ? "'$esc_ref_date'" : 'NULL');
        $ok = mysqli_query($conn, "UPDATE billing_receipt_master SET
            receipt_date='$esc_date',
            receipt_type='$esc_type',
            party_id='$party_id',
            payer_name='$esc_payer',
            total_amount='$amt',
            tds_total='$tds',
            payment_mode='$esc_mode',
            payment_ref='$esc_ref',
            payment_ref_date=$ref_date_sql,
            bank_account_id=$bank_sql,
            remarks='$esc_remarks',
            updated_at='$now',
            updated_by='$user_id'
            WHERE billing_receipt_id='$billing_receipt_id' LIMIT 1");
        if (!$ok) {
            return array('status' => 1, 'message' => 'Could not update receipt.');
        }
        mysqli_query($conn, "DELETE FROM billing_receipt_lines WHERE billing_receipt_id='$billing_receipt_id'");
    } else {
        $receipt_no = billing_allocate_receipt_number($conn);
        $esc_no = mysqli_real_escape_string($conn, $receipt_no);
        mysqli_query($conn, "INSERT INTO billing_receipt_master
            (receipt_no, receipt_date, receipt_type, party_id, payer_name, total_amount, tds_total,
             payment_mode, payment_ref, payment_ref_date, bank_account_id, remarks, status, created_at, created_by, updated_at, updated_by)
            VALUES
            ('$esc_no','$esc_date','$esc_type','$party_id','$esc_payer','$amt','$tds',
             '$esc_mode','$esc_ref'," . ($payment_ref_date !== '' ? "'$esc_ref_date'" : 'NULL') . ',' . ($bank_account_id > 0 ? "'$bank_account_id'" : 'NULL') . ",'$esc_remarks','final','$now','$user_id','$now','$user_id')");
        $billing_receipt_id = (int) mysqli_insert_id($conn);
        if ($billing_receipt_id <= 0) {
            return array('status' => 1, 'message' => 'Could not save receipt.');
        }
    }

    foreach ($parsed_lines as $line) {
        $esc_inv_no = mysqli_real_escape_string($conn, $line['invoice_no']);
        $esc_inv_date = mysqli_real_escape_string($conn, $line['invoice_date']);
        mysqli_query($conn, "INSERT INTO billing_receipt_lines
            (billing_receipt_id, billing_invoice_id, invoice_no, invoice_date,
             invoice_amount, already_received, credit_note_amount, tds_amount, balance_amount, receive_now, created_at)
            VALUES
            ('$billing_receipt_id','{$line['billing_invoice_id']}','$esc_inv_no','$esc_inv_date',
             '" . billing_format_money($line['invoice_amount']) . "',
             '" . billing_format_money($line['already_received']) . "',
             '" . billing_format_money($line['credit_note_amount']) . "',
             '" . billing_format_money($line['tds_amount']) . "',
             '" . billing_format_money($line['balance_amount']) . "',
             '" . billing_format_money($line['receive_now']) . "','$now')");
    }

    if ($files) {
        billing_receipt_save_attachments($conn, $billing_receipt_id, $files);
    }

    return array(
        'status' => 0,
        'message' => $update_id > 0 ? 'Receipt updated successfully.' : 'Receipt saved successfully.',
        'billing_receipt_id' => $billing_receipt_id,
        'receipt_no' => $receipt_no,
    );
}

function billing_receipt_fetch_view($conn, $billing_receipt_id)
{
    $billing_receipt_id = (int) $billing_receipt_id;
    $rec = billing_get_receipt($conn, $billing_receipt_id);
    if (!$rec || ($rec['master']['status'] ?? '') !== 'final') {
        return array('ok' => false, 'message' => 'Receipt not found.');
    }
    $m = $rec['master'];
    $party = '—';
    if ((int) ($m['party_id'] ?? 0) > 0) {
        $cq = mysqli_query($conn, "SELECT client_company_name FROM client WHERE client_id='" . (int) $m['party_id'] . "' LIMIT 1");
        if ($cq && ($cr = mysqli_fetch_assoc($cq))) {
            $party = $cr['client_company_name'];
        }
    } elseif (trim((string) ($m['payer_name'] ?? '')) !== '') {
        $party = trim($m['payer_name']);
    }

    $bank_label = '—';
    if ((int) ($m['bank_account_id'] ?? 0) > 0) {
        $bank = ew_company_bank_get($conn, (int) $m['bank_account_id']);
        if ($bank && !empty($bank['label'])) {
            $bank_label = $bank['label'];
        }
    }

    $lines = array();
    $invoice_open_sum = 0.0;
    foreach ($rec['lines'] as $ln) {
        $open = (float) ($ln['balance_amount'] ?? 0);
        $recv = (float) ($ln['receive_now'] ?? 0);
        $tds = (float) ($ln['tds_amount'] ?? 0);
        $remain = max(0, round($open - $recv - $tds, 2));
        $invoice_open_sum += $open;
        $lines[] = array(
            'invoice_no' => $ln['invoice_no'] ?? '',
            'invoice_date' => $ln['invoice_date'] ?? '',
            'invoice_amount' => billing_format_money($ln['invoice_amount'] ?? 0),
            'already_received' => billing_format_money($ln['already_received'] ?? 0),
            'credit_note_amount' => billing_format_money($ln['credit_note_amount'] ?? 0),
            'tds_amount' => billing_format_money($tds),
            'receive_now' => billing_format_money($recv),
            'balance_open' => billing_format_money($open),
            'balance_remain' => billing_format_money($remain),
        );
    }

    return array(
        'ok' => true,
        'billing_receipt_id' => $billing_receipt_id,
        'receipt_no' => $m['receipt_no'] ?? '',
        'receipt_date' => $m['receipt_date'] ?? '',
        'receipt_type' => billing_receipt_type_label($m['receipt_type'] ?? ''),
        'receipt_type_key' => $m['receipt_type'] ?? '',
        'party_label' => $party,
        'payment_mode' => $m['payment_mode'] ?? '',
        'payment_ref' => $m['payment_ref'] ?? '',
        'payment_ref_date' => $m['payment_ref_date'] ?? '',
        'bank_account_label' => $bank_label,
        'remarks' => $m['remarks'] ?? '',
        'total_amount' => billing_format_money($m['total_amount'] ?? 0),
        'tds_total' => billing_format_money($m['tds_total'] ?? 0),
        'invoice_open_total' => billing_format_money($invoice_open_sum),
        'lines' => $lines,
    );
}

function billing_receipt_list_balance_after($conn, $receipt_id)
{
    $receipt_id = (int) $receipt_id;
    $sum = 0.0;
    $q = mysqli_query($conn, "SELECT balance_amount, receive_now, tds_amount FROM billing_receipt_lines WHERE billing_receipt_id='$receipt_id'");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $remain = (float) $row['balance_amount'] - (float) $row['receive_now'] - (float) $row['tds_amount'];
            $sum += max(0, round($remain, 2));
        }
    }
    return billing_format_money($sum);
}
