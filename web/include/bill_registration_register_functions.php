<?php

require_once __DIR__ . '/expense_bill_helpers.php';

function bill_registration_register_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function bill_registration_register_parse_filters($request)
{
    $from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
    $to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
    $vendors = isset($request['vendors']) ? trim($request['vendors']) : '';
    $bill_type = isset($request['bill_type']) ? strtolower(trim($request['bill_type'])) : '';

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

    $allowed_types = array_keys(expense_bill_type_options());
    if ($bill_type !== '' && !in_array($bill_type, $allowed_types, true)) {
        $bill_type = '';
    }

    return array(
        'from_date' => $from_date,
        'to_date' => $to_date,
        'from_mysql' => $from_dt->format('Y-m-d'),
        'to_mysql' => $to_dt->format('Y-m-d'),
        'vendors' => $vendors,
        'bill_type' => $bill_type,
    );
}

function bill_registration_register_empty_summary()
{
    return array(
        'subtotal' => bill_registration_register_format_money(0),
        'gst_total' => bill_registration_register_format_money(0),
        'grand_total' => bill_registration_register_format_money(0),
        'tds_amount' => bill_registration_register_format_money(0),
        'net_payable' => bill_registration_register_format_money(0),
        'row_count' => 0,
    );
}

function bill_registration_register_fetch_rows($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array('rows' => array(), 'summary' => bill_registration_register_empty_summary());
    }

    expense_bill_ensure_schema($conn);

    $from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
    $to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);

    $where = "b.status=0
        AND STR_TO_DATE(b.bill_date, '%d-%m-%Y') >= '$from_esc'
        AND STR_TO_DATE(b.bill_date, '%d-%m-%Y') <= '$to_esc'";

    if (!empty($filters['vendors'])) {
        $ids = array_filter(array_map('intval', explode(',', $filters['vendors'])));
        if (!empty($ids)) {
            $where .= ' AND b.vendor_id IN (' . implode(',', $ids) . ')';
        }
    }
    if (!empty($filters['bill_type'])) {
        $type_esc = mysqli_real_escape_string($conn, $filters['bill_type']);
        $where .= " AND LOWER(IFNULL(b.bill_type,''))='$type_esc'";
    }

    $sql = "SELECT b.*
        FROM vendor_bill b
        WHERE $where
        ORDER BY STR_TO_DATE(b.bill_date, '%d-%m-%Y') ASC, b.bill_id ASC";

    $rows = array();
    $sum_sub = 0.0;
    $sum_gst = 0.0;
    $sum_grand = 0.0;
    $sum_tds = 0.0;
    $sum_net = 0.0;
    $sno = 0;

    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($raw = mysqli_fetch_assoc($q)) {
            $rec = expense_bill_format_record($conn, $raw);
            $sno++;
            $sub = (float) ($rec['subtotal'] ?? 0);
            $gst = (float) ($rec['gst_total'] ?? 0);
            $grand = (float) ($rec['grand_total'] ?? 0);
            $tds = (float) ($rec['tds_amount'] ?? 0);
            $net = (float) ($rec['net_payable'] ?? 0);

            $sum_sub += $sub;
            $sum_gst += $gst;
            $sum_grand += $grand;
            $sum_tds += $tds;
            $sum_net += $net;

            $rows[] = array(
                's_no' => $sno,
                'bill_entry_no' => $rec['bill_entry_no'] !== '' ? $rec['bill_entry_no'] : '—',
                'bill_date' => $rec['bill_date'] !== '' ? $rec['bill_date'] : '—',
                'bill_type' => $rec['bill_type_label'] !== '' ? $rec['bill_type_label'] : '—',
                'vendor' => $rec['vendor_label'] !== '' ? $rec['vendor_label'] : '—',
                'gcn' => ($rec['gcn_label'] !== '' && $rec['gcn_label'] !== '—') ? $rec['gcn_label'] : '—',
                'vendor_invoice_no' => $rec['vendor_invoice_no'] !== '' ? $rec['vendor_invoice_no'] : '—',
                'vendor_invoice_date' => $rec['vendor_invoice_date'] !== '' ? $rec['vendor_invoice_date'] : '—',
                'due_date' => $rec['due_date'] !== '' ? $rec['due_date'] : '—',
                'subtotal' => bill_registration_register_format_money($sub),
                'gst_total' => bill_registration_register_format_money($gst),
                'grand_total' => bill_registration_register_format_money($grand),
                'tds_amount' => bill_registration_register_format_money($tds),
                'net_payable' => bill_registration_register_format_money($net),
                'payment_mode' => $rec['payment_mode_label'] !== '' ? $rec['payment_mode_label'] : '—',
            );
        }
    }

    return array(
        'rows' => $rows,
        'summary' => array(
            'subtotal' => bill_registration_register_format_money($sum_sub),
            'gst_total' => bill_registration_register_format_money($sum_gst),
            'grand_total' => bill_registration_register_format_money($sum_grand),
            'tds_amount' => bill_registration_register_format_money($sum_tds),
            'net_payable' => bill_registration_register_format_money($sum_net),
            'row_count' => count($rows),
        ),
    );
}
