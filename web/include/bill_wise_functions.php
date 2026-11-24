<?php

require_once __DIR__ . '/pending_payments_functions.php';
require_once __DIR__ . '/pending_payments_ledger_functions.php';
require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_receipt_functions.php';

function bill_wise_format_date_short($date_str)
{
    $dt = pending_payments_parse_date_dmY($date_str);
    if (!$dt) {
        return $date_str;
    }
    return $dt->format('j-M-y');
}

function bill_wise_period_label($to_date_str)
{
    $dt = pending_payments_parse_date_dmY($to_date_str);
    if (!$dt) {
        return date('F Y');
    }
    return $dt->format('F Y');
}

function bill_wise_ledger_labels($series)
{
    $series = strtoupper(trim((string) $series));
    if ($series === 'GTA') {
        return array(
            'ledger_name' => 'GTA Sales',
            'voucher_type' => 'GTA Booking Gross Receipt',
        );
    }
    return array(
        'ledger_name' => 'Sales',
        'voucher_type' => 'Tax Invoice',
    );
}

function bill_wise_dr_cr($net)
{
    $net = round((float) $net, 2);
    if ($net > 0.009) {
        return array('amount' => $net, 'dr_cr' => 'Dr');
    }
    if ($net < -0.009) {
        return array('amount' => abs($net), 'dr_cr' => 'Cr');
    }
    return array('amount' => 0.0, 'dr_cr' => 'Dr');
}

function bill_wise_invoice_payment_status($total, $balance)
{
    $total = round((float) $total, 2);
    $balance = round((float) $balance, 2);
    if ($balance <= 0.009) {
        return 'Settled';
    }
    if ($balance >= $total - 0.009) {
        return 'Pending';
    }
    return 'Part paid';
}

/**
 * Number of packages for a GCN (booking), not charged weight stored on the invoice line.
 */
function bill_wise_gcn_package_count($conn, $trans_table, $transaction_id, $trow = null, $detail_weight = 0.0)
{
    $trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $trans_table);
    $transaction_id = (int) $transaction_id;
    if ($trans_table === '' || $transaction_id <= 0) {
        return 0;
    }

    if (!is_array($trow)) {
        $tq = mysqli_query($conn, "SELECT no_of_pkge, consignment_weight FROM `$trans_table` WHERE transaction_id='$transaction_id' LIMIT 1");
        $trow = ($tq && ($r = mysqli_fetch_assoc($tq))) ? $r : null;
    }

    $packages = is_array($trow) ? (int) ($trow['no_of_pkge'] ?? 0) : 0;
    $weight = round((float) $detail_weight, 2);
    if ($weight <= 0 && is_array($trow)) {
        $weight = round((float) ($trow['consignment_weight'] ?? 0), 2);
    }

    $line_agg = billing_invoice_lines_aggregate($conn, $trans_table, $transaction_id);
    $line_pkgs = (int) ($line_agg['packages'] ?? 0);

    if ($line_pkgs > 0) {
        if ($packages <= 0 || ($weight > 0 && abs($packages - $weight) < 0.01)) {
            $packages = $line_pkgs;
        }
    } elseif ($packages <= 0) {
        $inv_tbl = str_replace('transaction_', 'transaction_invoice_', $trans_table);
        $iq = @mysqli_query($conn, "SELECT SUM(qty) AS pq FROM `$inv_tbl` WHERE transaction_id='$transaction_id'
            AND (type_of_pkge IS NULL OR type_of_pkge='' OR type_of_pkge!='Select Package Type')");
        if ($iq && ($ir = mysqli_fetch_assoc($iq))) {
            $packages = (int) ($ir['pq'] ?? 0);
        }
    }

    return max(0, $packages);
}

function bill_wise_gcn_rows($conn, $billing_invoice_id)
{
    $inv = billing_get_invoice($conn, (int) $billing_invoice_id);
    if (!$inv || empty($inv['details'])) {
        return array();
    }
    $master = $inv['master'];
    $gcns = array();
    foreach ($inv['details'] as $detail) {
        $tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $detail['trans_table'] ?? '');
        $tid = (int) ($detail['transaction_id'] ?? 0);
        $trow = null;
        if ($tbl !== '' && $tid > 0) {
            $tq = mysqli_query($conn, "SELECT * FROM `$tbl` WHERE transaction_id='$tid' LIMIT 1");
            if ($tq) {
                $trow = mysqli_fetch_assoc($tq);
            }
        }

        $weight = round((float) ($detail['weight'] ?? 0), 2);
        $qty = bill_wise_gcn_package_count($conn, $tbl, $tid, $trow, $weight);
        if ($qty <= 0) {
            $stored = (int) ($detail['packages'] ?? 0);
            if ($stored > 0 && ($weight <= 0 || abs($stored - $weight) >= 0.01)) {
                $qty = $stored;
            }
        }
        $freight = round((float) ($detail['freight_amount'] ?? 0), 2);
        $dc = round((float) ($detail['other_charges'] ?? 0), 2);
        $d_tempo = 0.0;
        $rate = 0.0;
        $type_label = '';
        $consignor = '';
        $station = '';
        $ship_to = '';
        $inv_no = '';

        if ($trow) {
            $d_tempo = round((float) ($trow['cartage_amount'] ?? 0), 2);
            $rate = round((float) ($trow['frieght_rate'] ?? 0), 2);
            if ($rate <= 0 && $weight > 0 && $freight > 0) {
                $rate = round($freight / $weight, 2);
            }
            if (function_exists('get_mode')) {
                $type_label = trim((string) get_mode($conn, $trow['mode_of_transportation'] ?? 0));
            }
            if (function_exists('get_client_name')) {
                $consignor = trim((string) get_client_name($conn, $trow['consigner'] ?? 0));
                $ship_to = trim((string) get_client_name($conn, $trow['consignee'] ?? 0));
            }
            if (function_exists('get_city_name')) {
                $station = trim((string) get_city_name($conn, $trow['destination'] ?? 0));
            }
            $inv_tbl = str_replace('transaction_', 'transaction_invoice_', $tbl);
            $iq = @mysqli_query($conn, "SELECT party_invoice_no FROM `$inv_tbl` WHERE transaction_id='$tid' AND type_of_pkge!='Select Package Type' LIMIT 1");
            if ($iq && ($ir = mysqli_fetch_assoc($iq))) {
                $inv_no = trim((string) ($ir['party_invoice_no'] ?? ''));
            }
        }

        $amount = round((float) ($detail['total_amount'] ?? 0), 2);
        if ($amount <= 0) {
            $amount = round($freight + $dc + (float) ($detail['gst_amount'] ?? 0), 2);
        }

        $gcns[] = array(
            'gr_no' => (string) ($detail['grn_no'] ?? ''),
            'gr_date' => (string) ($detail['grn_date'] ?? ''),
            'gr_date_short' => bill_wise_format_date_short($detail['grn_date'] ?? ''),
            'type' => $type_label !== '' ? $type_label : '—',
            'qty' => $qty,
            'consignor' => $consignor !== '' ? $consignor : '—',
            'station' => $station !== '' ? $station : '—',
            'ship_to' => $ship_to !== '' ? $ship_to : '—',
            'inv_no' => $inv_no !== '' ? $inv_no : '—',
            'freight' => $freight,
            'detention_tempo' => $d_tempo,
            'dc' => $dc,
            'weight' => $weight,
            'weight_label' => ($weight > 0 ? rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.') : '0') . ' Kgs',
            'rate' => $rate,
            'per' => 'Kgs',
            'amount' => $amount,
        );
    }

    $taxable = (float) ($master['taxable_value'] ?? 0);
    $cgst = (float) ($master['cgst_amount'] ?? 0);
    $sgst = (float) ($master['sgst_amount'] ?? 0);
    $grand = (float) ($master['grand_total'] ?? 0);
    $computed = $taxable + $cgst + $sgst + (float) ($master['igst_amount'] ?? 0) + (float) ($master['cess_amount'] ?? 0);
    $round_off = round($grand - $computed, 2);
    if (abs($round_off) < 0.01) {
        $round_off = 0.0;
    }

    return array(
        'gcns' => $gcns,
        'taxable' => $taxable,
        'cgst' => $cgst,
        'sgst' => $sgst,
        'round_off' => $round_off,
        'grand_total' => $grand,
    );
}

function bill_wise_fetch($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array('error' => $filters['error']);
    }

    ensure_billing_tables($conn);
    ensure_billing_receipt_tables($conn);
    ensure_billing_note_tables($conn);

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
            m.taxable_value, m.cgst_amount, m.sgst_amount, m.igst_amount, m.cess_amount,
            COALESCE(m.invoice_series, '') AS invoice_series,
            c.client_company_name
        FROM billing_invoice_master m
        LEFT JOIN client c ON c.client_id = m.customer_id
        WHERE $where
        ORDER BY m.customer_id ASC, STR_TO_DATE(m.invoice_date, '%d-%m-%Y') ASC, m.billing_invoice_id ASC";

    $by_customer = array();
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $cid = (int) ($r['customer_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            if (!isset($by_customer[$cid])) {
                $name = trim((string) ($r['client_company_name'] ?? ''));
                $by_customer[$cid] = array(
                    'id' => $cid,
                    'name' => $name !== '' ? $name : ('Customer #' . $cid),
                    'invoice_rows' => array(),
                );
            }
            $by_customer[$cid]['invoice_rows'][] = $r;
        }
    }

    $customers = array();
    foreach ($by_customer as $cid => $pack) {
        $ledger_filters = array(
            'customer_id' => $cid,
            'from_date' => $filters['from_date'],
            'to_date' => $filters['to_date'],
            'from_mysql' => $filters['from_mysql'],
            'to_mysql' => $filters['to_mysql'],
        );
        $ledger = pending_payments_ledger_fetch($conn, $ledger_filters);
        $opening = (float) ($ledger['opening_balance'] ?? 0);
        $closing = (float) ($ledger['closing_balance'] ?? 0);
        $billed = (float) str_replace(',', '', (string) ($ledger['summary']['total_debit'] ?? '0'));
        $collected = (float) str_replace(',', '', (string) ($ledger['summary']['total_credit'] ?? '0'));

        $invoices = array();
        foreach ($pack['invoice_rows'] as $r) {
            $inv_id = (int) $r['billing_invoice_id'];
            $snap = billing_invoice_settlement_snapshot($conn, $inv_id, 0);
            if (!$snap) {
                continue;
            }
            $total = round((float) ($r['grand_total'] ?? 0), 2);
            $paid = round((float) ($snap['already_received'] ?? 0), 2);
            $cn = round((float) ($snap['credit_note'] ?? 0), 2);
            $tds = round((float) ($snap['tds_prior'] ?? 0), 2);
            $received = round($paid + $cn + $tds, 2);
            $balance = round((float) ($snap['balance'] ?? 0), 2);
            $labels = bill_wise_ledger_labels($r['invoice_series'] ?? '');
            $gcn_pack = bill_wise_gcn_rows($conn, $inv_id);

            $invoices[] = array(
                'id' => $inv_id,
                'voucher_no' => (string) ($snap['invoice_no'] ?? ''),
                'date' => (string) ($snap['invoice_date'] ?? ''),
                'date_short' => bill_wise_format_date_short($snap['invoice_date'] ?? ''),
                'ledger_name' => $labels['ledger_name'],
                'voucher_type_label' => $labels['voucher_type'],
                'total' => $total,
                'received' => round($received, 2),
                'balance' => $balance,
                'payment_status' => bill_wise_invoice_payment_status($total, $balance),
                'taxable' => (float) ($gcn_pack['taxable'] ?? $r['taxable_value']),
                'cgst' => (float) ($gcn_pack['cgst'] ?? $r['cgst_amount']),
                'sgst' => (float) ($gcn_pack['sgst'] ?? $r['sgst_amount']),
                'round_off' => (float) ($gcn_pack['round_off'] ?? 0),
                'gcn_count' => count($gcn_pack['gcns']),
                'gcns' => $gcn_pack['gcns'],
            );
        }

        $customers[] = array(
            'id' => $cid,
            'name' => $pack['name'],
            'opening_balance' => $opening,
            'billing' => round($billed, 2),
            'collection' => round($collected, 2),
            'closing_balance' => bill_wise_dr_cr($closing),
            'payment_status' => ($closing > 0.009 ? 'Pending' : 'Completed'),
            'invoices' => $invoices,
        );
    }

    usort($customers, function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    return array(
        'period' => array(
            'from' => $filters['from_date'],
            'to' => $filters['to_date'],
            'month_label' => bill_wise_period_label($filters['to_date']),
        ),
        'customers' => $customers,
    );
}