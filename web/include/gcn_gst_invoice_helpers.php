<?php

/**
 * GCN GST invoice PDF (HRGST/HRGTA sequence + digital_invoice storage).
 * Invoices are no longer created at booking; use payment edit with generate_gst_invoice.
 */

function gcn_gst_invoice_booking_auto_enabled()
{
    return false;
}

function gcn_gst_invoice_inv_type_from_mode($mode_of_transport)
{
    $mode = (string) $mode_of_transport;
    if ($mode === '1' || $mode === '2' || $mode === '3') {
        return 'GST';
    }

    return 'GTA';
}

/** gst = HRGST/HRGTA sequence; other = HROTH (non-GST / cash bill, no tax block on PDF). */
function gcn_gst_invoice_doc_kind_from_no($invoice_no)
{
    $invoice_no = strtoupper(trim((string) $invoice_no));
    if ($invoice_no !== '' && strpos($invoice_no, 'HROTH') === 0) {
        return 'other';
    }

    return 'gst';
}

/**
 * True when the GCN booking carries GST on freight/charges (tax-invoice path).
 * Exempt, Non-GST, or GST0 profile → false (user may choose with or without GST invoice).
 *
 * @param array<string,mixed> $row Booking row or gst fields (gst_type, gst_tax_id, gst_tax_code, gst_amount, …).
 */
function gcn_booking_includes_gst(array $row)
{
    $gst_type = strtolower(trim((string) ($row['gst_type'] ?? 'auto')));
    if (in_array($gst_type, array('exempt', 'non_gst'), true)) {
        return false;
    }
    $tax_code = strtoupper(trim((string) ($row['gst_tax_code'] ?? '')));
    if ($tax_code === 'GST0') {
        return false;
    }
    $gst_amt = (float) ($row['gst_amount'] ?? 0);
    $comp_amt = (float) ($row['cgst_amount'] ?? 0)
        + (float) ($row['sgst_amount'] ?? 0)
        + (float) ($row['igst_amount'] ?? 0);
    if ($gst_amt > 0.001 || $comp_amt > 0.001) {
        return true;
    }
    $gst_tax_id = (int) ($row['gst_tax_id'] ?? 0);
    if ($gst_tax_id > 0 && $tax_code !== '' && $tax_code !== 'GST0') {
        return true;
    }

    return false;
}

function gcn_gst_invoice_sequence_meta($doc_kind, $mode_of_transport)
{
    if ($doc_kind === 'other') {
        return array('inv_type' => 'OTHER', 'gst_text' => 'HROTH');
    }
    $type = gcn_gst_invoice_inv_type_from_mode($mode_of_transport);

    return array(
        'inv_type' => $type,
        'gst_text' => ($type === 'GST' ? 'HRGST' : 'HRGTA'),
    );
}

function gcn_gst_invoice_ensure_year_sequences($conn, $invoice_table, $year_insert, $user_id, $updated_at)
{
    $select = mysqli_query($conn, 'SELECT * FROM `' . mysqli_real_escape_string($conn, $invoice_table) . '`');
    $get_count = $select ? mysqli_num_rows($select) : 0;
    if ($get_count === 0) {
        $insert_data = 'INSERT INTO `' . $invoice_table . "`
            (`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`)
            VALUES ('0','HRGST','$year_insert','GST','$updated_at','$user_id'),
                   ('0','HRGTA','$year_insert','GTA','$updated_at','$user_id'),
                   ('0','HROTH','$year_insert','OTHER','$updated_at','$user_id')";
        mysqli_query($conn, $insert_data);
    } else {
        $chk = mysqli_query($conn, "SELECT id FROM `$invoice_table` WHERE inv_type='OTHER' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) === 0) {
            mysqli_query($conn, "INSERT INTO `$invoice_table`
                (`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`)
                VALUES ('0','HROTH','$year_insert','OTHER','$updated_at','$user_id')");
        }
    }
}

function gcn_gst_invoice_normalize_existing_no($invoice_no)
{
    $invoice_no = trim((string) $invoice_no);
    if ($invoice_no === '' || strcasecmp($invoice_no, 'NULL') === 0) {
        return '';
    }

    return $invoice_no;
}

function gcn_gst_invoice_base_url()
{
    if (defined('GCN_GST_INVOICE_BASE_URL') && GCN_GST_INVOICE_BASE_URL !== '') {
        return rtrim(GCN_GST_INVOICE_BASE_URL, '/') . '/';
    }
    $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== ''
        ? $_SERVER['HTTP_HOST']
        : 'elitewave360.in';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';

    return $scheme . '://' . $host . '/web/';
}

/**
 * @param mysqli $conn
 * @param array  $opts transaction_id, month, year, grn_date, mode_of_transport, trans_table,
 *                     user_id, updated_at (optional), existing_invoice_no (optional)
 *
 * @return array{ok:bool,invoice_no:string,message:string,pdf_path:string}
 */
function gcn_gst_invoice_generate($conn, array $opts)
{
    $opts['doc_kind'] = 'gst';

    return gcn_gst_invoice_generate_for_doc($conn, $opts);
}

function gcn_other_invoice_generate($conn, array $opts)
{
    $opts['doc_kind'] = 'other';

    return gcn_gst_invoice_generate_for_doc($conn, $opts);
}

function gcn_gst_invoice_generate_for_doc($conn, array $opts)
{
    $transaction_id = (int) ($opts['transaction_id'] ?? 0);
    $month = preg_replace('/[^0-9]/', '', (string) ($opts['month'] ?? ''));
    $year = preg_replace('/[^0-9]/', '', (string) ($opts['year'] ?? ''));
    $grn_date = trim((string) ($opts['grn_date'] ?? ''));
    $mode_of_transport = (string) ($opts['mode_of_transport'] ?? '');
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($opts['trans_table'] ?? ''));
    $user_id = (int) ($opts['user_id'] ?? 0);
    $updated_at = trim((string) ($opts['updated_at'] ?? date('d-m-Y')));
    $existing_invoice_no = gcn_gst_invoice_normalize_existing_no($opts['existing_invoice_no'] ?? '');
    $doc_kind = ($opts['doc_kind'] ?? 'gst') === 'other' ? 'other' : 'gst';

    if ($transaction_id <= 0 || $month === '' || $year === '' || $trans_table === '') {
        return array('ok' => false, 'invoice_no' => '', 'message' => 'Invalid GCN reference for invoice.', 'pdf_path' => '');
    }

    $seq_meta = gcn_gst_invoice_sequence_meta($doc_kind, $mode_of_transport);
    $inv_type = $seq_meta['inv_type'];
    $base = gcn_gst_invoice_base_url();
    $directory = 'digital_invoice/';
    $invoice_file_name = $month . '_' . $year . '_' . $transaction_id . 'invoice';
    $download_path = $directory . $invoice_file_name . '.pdf';

    $invoice_url_params = array(
        'month' => $month,
        'year' => $year,
        'id' => $transaction_id,
    );
    if ($doc_kind === 'other') {
        $invoice_url_params['invoice_doc'] = 'other';
    }

    if ($existing_invoice_no !== '') {
        $existing_kind = gcn_gst_invoice_doc_kind_from_no($existing_invoice_no);
        if ($existing_kind !== $doc_kind) {
            $existing_invoice_no = '';
        }
    }

    if ($existing_invoice_no !== '') {
        $invoice_url_params['invoice_no'] = $existing_invoice_no;
        $invoice_url = $base . 'gst_invoice_page.php?' . http_build_query($invoice_url_params);
        $saved = gcn_gst_invoice_fetch_pdf($invoice_url, $download_path);
        if (!$saved) {
            $fail_msg = ($doc_kind === 'other') ? 'Could not refresh invoice PDF.' : 'Could not refresh GST invoice PDF.';

            return array('ok' => false, 'invoice_no' => $existing_invoice_no, 'message' => $fail_msg, 'pdf_path' => '');
        }

        return array(
            'ok' => true,
            'invoice_no' => $existing_invoice_no,
            'message' => ($doc_kind === 'other') ? 'Invoice PDF updated.' : 'GST invoice PDF updated.',
            'pdf_path' => $download_path,
        );
    }

    $grn_date_expl = explode('-', $grn_date);
    if (count($grn_date_expl) !== 3) {
        $grn_date = date('d-m-Y');
        $grn_date_expl = explode('-', $grn_date);
    }
    $cur_year = (int) $grn_date_expl[2];
    $previous_year = $cur_year - 1;
    $p_y = substr((string) $previous_year, -2);
    $c_y = substr((string) $cur_year, -2);
    $year_insert = $p_y . '-' . $c_y;

    require_once __DIR__ . '/billing_functions.php';
    $unique_invoice_no = billing_allocate_trans_invoice_number($conn, $grn_date, $user_id, $inv_type);
    if ($unique_invoice_no === '') {
        return array('ok' => false, 'invoice_no' => '', 'message' => 'Could not allocate invoice number.', 'pdf_path' => '');
    }

    $invoice_url_params['invoice_no'] = $unique_invoice_no;
    $invoice_url = $base . 'gst_invoice_page.php?' . http_build_query($invoice_url_params);
    $saved = gcn_gst_invoice_fetch_pdf($invoice_url, $download_path);
    if (!$saved) {
        $fail_msg = ($doc_kind === 'other') ? 'Could not generate invoice PDF.' : 'Could not generate GST invoice PDF.';

        return array('ok' => false, 'invoice_no' => '', 'message' => $fail_msg, 'pdf_path' => '');
    }

    $esc_no = mysqli_real_escape_string($conn, $unique_invoice_no);
    mysqli_query($conn, "UPDATE `$trans_table` SET `invoice_no`='$esc_no' WHERE transaction_id='$transaction_id'");

    return array(
        'ok' => true,
        'invoice_no' => $unique_invoice_no,
        'message' => ($doc_kind === 'other') ? 'Invoice generated.' : 'GST invoice generated.',
        'pdf_path' => $download_path,
    );
}

function gcn_gst_invoice_fetch_pdf($invoice_url, $download_path)
{
    $file_inv_download = curl_init($invoice_url);
    curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($file_inv_download, CURLOPT_REFERER, $invoice_url);
    curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($file_inv_download, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($file_inv_download, CURLOPT_TIMEOUT, 120);
    $store_inv = curl_exec($file_inv_download);
    curl_close($file_inv_download);
    if ($store_inv === false || $store_inv === '') {
        return false;
    }

    return file_put_contents($download_path, $store_inv) !== false;
}
