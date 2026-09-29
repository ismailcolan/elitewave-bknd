<?php

require_once __DIR__ . '/billing_functions.php';

function invoice_register_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function invoice_register_parse_filters($request)
{
    $from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
    $to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
    $customers = isset($request['customers']) ? trim($request['customers']) : '';

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
        'customers' => $customers,
    );
}

function invoice_register_empty_summary()
{
    return array(
        'total_freight' => invoice_register_format_money(0),
        'sgst_amount' => invoice_register_format_money(0),
        'cgst_amount' => invoice_register_format_money(0),
        'igst_amount' => invoice_register_format_money(0),
        'total_other' => invoice_register_format_money(0),
        'grand_total' => invoice_register_format_money(0),
        'row_count' => 0,
    );
}

function invoice_register_fetch_rows($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array('rows' => array(), 'summary' => invoice_register_empty_summary());
    }

    ensure_billing_tables($conn);

    $from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
    $to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);

    $where = "m.status='final'
        AND STR_TO_DATE(m.invoice_date, '%d-%m-%Y') >= '$from_esc'
        AND STR_TO_DATE(m.invoice_date, '%d-%m-%Y') <= '$to_esc'";

    if (!empty($filters['customers'])) {
        $ids = array_filter(array_map('intval', explode(',', $filters['customers'])));
        if (!empty($ids)) {
            $where .= ' AND m.customer_id IN (' . implode(',', $ids) . ')';
        }
    }

    $sql = "SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.customer_id,
            m.total_freight, m.sgst_amount, m.cgst_amount, m.igst_amount, m.total_other, m.grand_total,
            c.client_company_name
        FROM billing_invoice_master m
        LEFT JOIN client c ON c.client_id = m.customer_id
        WHERE $where
        ORDER BY STR_TO_DATE(m.invoice_date, '%d-%m-%Y') ASC, m.billing_invoice_id ASC";

    $rows = array();
    $sum_freight = 0.0;
    $sum_sgst = 0.0;
    $sum_cgst = 0.0;
    $sum_igst = 0.0;
    $sum_other = 0.0;
    $sum_grand = 0.0;
    $sno = 0;

    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $sno++;
            $freight = (float) ($r['total_freight'] ?? 0);
            $sgst = (float) ($r['sgst_amount'] ?? 0);
            $cgst = (float) ($r['cgst_amount'] ?? 0);
            $igst = (float) ($r['igst_amount'] ?? 0);
            $other = (float) ($r['total_other'] ?? 0);
            $grand = (float) ($r['grand_total'] ?? 0);

            $sum_freight += $freight;
            $sum_sgst += $sgst;
            $sum_cgst += $cgst;
            $sum_igst += $igst;
            $sum_other += $other;
            $sum_grand += $grand;

            $customer = trim($r['client_company_name'] ?? '');
            if ($customer === '') {
                $customer = '—';
            }

            $rows[] = array(
                's_no' => $sno,
                'invoice_no' => $r['invoice_no'] ?? '',
                'invoice_date' => $r['invoice_date'] ?? '',
                'customer' => $customer,
                'freight_amount' => invoice_register_format_money($freight),
                'sgst_amount' => invoice_register_format_money($sgst),
                'cgst_amount' => invoice_register_format_money($cgst),
                'igst_amount' => invoice_register_format_money($igst),
                'other_charges' => invoice_register_format_money($other),
                'invoice_value' => invoice_register_format_money($grand),
            );
        }
    }

    return array(
        'rows' => $rows,
        'summary' => array(
            'total_freight' => invoice_register_format_money($sum_freight),
            'sgst_amount' => invoice_register_format_money($sum_sgst),
            'cgst_amount' => invoice_register_format_money($sum_cgst),
            'igst_amount' => invoice_register_format_money($sum_igst),
            'total_other' => invoice_register_format_money($sum_other),
            'grand_total' => invoice_register_format_money($sum_grand),
            'row_count' => count($rows),
        ),
    );
}
