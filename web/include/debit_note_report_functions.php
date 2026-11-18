<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_note_functions.php';

function debit_note_report_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function debit_note_report_parse_filters($request)
{
    $from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
    $to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
    $vendors = isset($request['vendors']) ? trim($request['vendors']) : '';

    if ($from_date === '' || $to_date === '') {
        return array('error' => 'From Date and To Date are required.');
    }

    $from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
    $to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
    if (!$from_dt || !$to_dt) {
        return array('error' => 'Invalid date format. Use dd-mm-yyyy.');
    }
    if ($from_dt > $to_dt) {
        return array('error' => 'From Date cannot be after To Date.');
    }

    return array(
        'from_date' => $from_date,
        'to_date' => $to_date,
        'from_mysql' => $from_dt->format('Y-m-d'),
        'to_mysql' => $to_dt->format('Y-m-d'),
        'vendors' => $vendors,
    );
}

function debit_note_report_empty_summary()
{
    return array(
        'taxable_value' => debit_note_report_format_money(0),
        'sgst_amount' => debit_note_report_format_money(0),
        'cgst_amount' => debit_note_report_format_money(0),
        'igst_amount' => debit_note_report_format_money(0),
        'gst_amount' => debit_note_report_format_money(0),
        'grand_total' => debit_note_report_format_money(0),
        'row_count' => 0,
    );
}

/**
 * Debit notes issued to vendors (party_type=vendor, note_type=DN, status=final).
 */
function debit_note_report_fetch_rows($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array('rows' => array(), 'summary' => debit_note_report_empty_summary());
    }

    ensure_billing_tables($conn);
    ensure_billing_note_tables($conn);

    $from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
    $to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);

    $where = "n.note_type='DN'
        AND n.party_type='vendor'
        AND n.status='final'
        AND STR_TO_DATE(n.note_date, '%d-%m-%Y') >= '$from_esc'
        AND STR_TO_DATE(n.note_date, '%d-%m-%Y') <= '$to_esc'";

    if (!empty($filters['vendors'])) {
        $ids = array_filter(array_map('intval', explode(',', $filters['vendors'])));
        if (!empty($ids)) {
            $where .= ' AND n.party_id IN (' . implode(',', $ids) . ')';
        }
    }

    require_once __DIR__ . '/expense_bill_helpers.php';
    expense_bill_ensure_schema($conn);

    $sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.reason,
            n.taxable_value, n.sgst_amount, n.cgst_amount, n.igst_amount, n.gst_amount, n.grand_total,
            COALESCE(NULLIF(b.vendor_invoice_no,''), b.bill_entry_no) AS against_bill,
            b.bill_entry_no,
            TRIM(CONCAT(COALESCE(v.vendor_code,''), ' - ', COALESCE(v.vendor_name,''))) AS vendor_name
        FROM billing_note_master n
        LEFT JOIN vendor_bill b ON b.bill_id = n.against_invoice_id
        LEFT JOIN vendor_master v ON v.vendor_id = n.party_id
        WHERE $where
        ORDER BY STR_TO_DATE(n.note_date, '%d-%m-%Y') ASC, n.billing_note_id ASC";

    $rows = array();
    $sum_taxable = 0.0;
    $sum_sgst = 0.0;
    $sum_cgst = 0.0;
    $sum_igst = 0.0;
    $sum_gst = 0.0;
    $sum_grand = 0.0;
    $sno = 0;

    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $sno++;
            $taxable = (float) ($r['taxable_value'] ?? 0);
            $sgst = (float) ($r['sgst_amount'] ?? 0);
            $cgst = (float) ($r['cgst_amount'] ?? 0);
            $igst = (float) ($r['igst_amount'] ?? 0);
            $gst = (float) ($r['gst_amount'] ?? 0);
            $grand = (float) ($r['grand_total'] ?? 0);

            $sum_taxable += $taxable;
            $sum_sgst += $sgst;
            $sum_cgst += $cgst;
            $sum_igst += $igst;
            $sum_gst += $gst;
            $sum_grand += $grand;

            $vendor = trim($r['vendor_name'] ?? '', ' -');
            if ($vendor === '') {
                $vendor = '—';
            }
            $against_bill = trim($r['against_bill'] ?? '');
            if ($against_bill === '') {
                $against_bill = '—';
            }
            $reason = trim($r['reason'] ?? '');
            if ($reason === '') {
                $reason = '—';
            }
            $note_no = trim($r['note_no'] ?? '');
            if ($note_no === '') {
                $note_no = 'DN-' . (int) $r['billing_note_id'];
            }

            $rows[] = array(
                's_no' => $sno,
                'note_no' => $note_no,
                'note_date' => $r['note_date'] ?? '',
                'against_bill' => $against_bill,
                'vendor' => $vendor,
                'reason' => $reason,
                'taxable_value' => debit_note_report_format_money($taxable),
                'sgst_amount' => debit_note_report_format_money($sgst),
                'cgst_amount' => debit_note_report_format_money($cgst),
                'igst_amount' => debit_note_report_format_money($igst),
                'gst_amount' => debit_note_report_format_money($gst),
                'grand_total' => debit_note_report_format_money($grand),
            );
        }
    }

    return array(
        'rows' => $rows,
        'summary' => array(
            'taxable_value' => debit_note_report_format_money($sum_taxable),
            'sgst_amount' => debit_note_report_format_money($sum_sgst),
            'cgst_amount' => debit_note_report_format_money($sum_cgst),
            'igst_amount' => debit_note_report_format_money($sum_igst),
            'gst_amount' => debit_note_report_format_money($sum_gst),
            'grand_total' => debit_note_report_format_money($sum_grand),
            'row_count' => count($rows),
        ),
    );
}
