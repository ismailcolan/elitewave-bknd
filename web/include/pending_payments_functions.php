<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_receipt_functions.php';

function pending_payments_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function pending_payments_parse_filters($request)
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

function pending_payments_parse_date_dmY($date_str)
{
    $date_str = trim((string) $date_str);
    if ($date_str === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('d-m-Y', $date_str);
    if ($dt instanceof DateTime) {
        $dt->setTime(0, 0, 0);
        return $dt;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date_str);
    if ($dt instanceof DateTime) {
        $dt->setTime(0, 0, 0);
        return $dt;
    }
    return null;
}

function pending_payments_invoice_booking_date($conn, $invoice_id, $invoice_date_fallback = '')
{
    $invoice_id = (int) $invoice_id;
    $earliest = null;
    $q = mysqli_query($conn, "SELECT grn_date FROM billing_invoice_details
        WHERE billing_invoice_id='$invoice_id' AND grn_date IS NOT NULL AND grn_date != ''");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($row['grn_date'] ?? '');
            if ($dt && ($earliest === null || $dt < $earliest)) {
                $earliest = $dt;
            }
        }
    }
    if ($earliest === null && $invoice_date_fallback !== '') {
        $earliest = pending_payments_parse_date_dmY($invoice_date_fallback);
    }
    return $earliest;
}

function pending_payments_aging_days($booking_dt, $as_of = null)
{
    if (!$booking_dt instanceof DateTime) {
        return 0;
    }
    if ($as_of === null) {
        $as_of = new DateTime('today');
    } else {
        $as_of = clone $as_of;
        $as_of->setTime(0, 0, 0);
    }
    if ($booking_dt > $as_of) {
        return 0;
    }
    return (int) $booking_dt->diff($as_of)->days;
}

function pending_payments_format_aging($days)
{
    $days = max(0, (int) $days);
    if ($days === 0) {
        return '0 day';
    }
    if ($days === 1) {
        return '1 day';
    }
    return $days . ' days';
}

function pending_payments_empty_summary()
{
    return array(
        'invoice_value' => pending_payments_format_money(0),
        'payment_received' => pending_payments_format_money(0),
        'credit_note' => pending_payments_format_money(0),
        'tds' => pending_payments_format_money(0),
        'balance_amount' => pending_payments_format_money(0),
        'row_count' => 0,
    );
}

function pending_payments_fetch_rows($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array('rows' => array(), 'summary' => pending_payments_empty_summary());
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

    $sql = "SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.grand_total, m.customer_id,
            c.client_company_name
        FROM billing_invoice_master m
        LEFT JOIN client c ON c.client_id = m.customer_id
        WHERE $where
        ORDER BY STR_TO_DATE(m.invoice_date, '%d-%m-%Y') ASC, m.billing_invoice_id ASC";

    $rows = array();
    $sum_inv = 0.0;
    $sum_paid = 0.0;
    $sum_cn = 0.0;
    $sum_tds = 0.0;
    $sum_bal = 0.0;
    $sno = 0;
    $today = new DateTime('today');

    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $inv_id = (int) $r['billing_invoice_id'];
            $snap = billing_invoice_settlement_snapshot($conn, $inv_id, 0);
            if (!$snap) {
                continue;
            }
            $balance = (float) $snap['balance'];
            if ($balance <= 0.009) {
                continue;
            }

            $sno++;
            $inv_val = (float) $snap['invoice_amount'];
            $paid = (float) $snap['already_received'];
            $cn = (float) $snap['credit_note'];
            $tds = (float) $snap['tds_prior'];

            $sum_inv += $inv_val;
            $sum_paid += $paid;
            $sum_cn += $cn;
            $sum_tds += $tds;
            $sum_bal += $balance;

            $booking_dt = pending_payments_invoice_booking_date($conn, $inv_id, $r['invoice_date'] ?? '');
            $aging_days = pending_payments_aging_days($booking_dt, $today);
            $booking_label = $booking_dt ? $booking_dt->format('d-m-Y') : '—';

            $customer = trim($r['client_company_name'] ?? '');
            if ($customer === '') {
                $customer = '—';
            }

            $rows[] = array(
                's_no' => $sno,
                'invoice_no' => $snap['invoice_no'],
                'invoice_date' => $snap['invoice_date'],
                'customer' => $customer,
                'invoice_value' => pending_payments_format_money($inv_val),
                'payment_received' => pending_payments_format_money($paid),
                'credit_note' => pending_payments_format_money($cn),
                'tds' => pending_payments_format_money($tds),
                'balance_amount' => pending_payments_format_money($balance),
                'overdue_aging_days' => $aging_days,
                'overdue_aging' => pending_payments_format_aging($aging_days),
                'booking_date' => $booking_label,
            );
        }
    }

    return array(
        'rows' => $rows,
        'summary' => array(
            'invoice_value' => pending_payments_format_money($sum_inv),
            'payment_received' => pending_payments_format_money($sum_paid),
            'credit_note' => pending_payments_format_money($sum_cn),
            'tds' => pending_payments_format_money($sum_tds),
            'balance_amount' => pending_payments_format_money($sum_bal),
            'row_count' => count($rows),
        ),
    );
}
