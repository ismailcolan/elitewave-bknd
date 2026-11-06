<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_receipt_functions.php';
require_once __DIR__ . '/billing_note_functions.php';
require_once __DIR__ . '/pending_payments_functions.php';

/**
 * Bank-statement style client ledger (receivables).
 * Debit = amount due to EliteWave (invoices / debit notes).
 * Credit = amount settled (payments / credit notes / TDS / advances).
 */

function pending_payments_ledger_parse_filters($request)
{
    $from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
    $to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
    $customer_id = isset($request['customer_id']) ? (int) $request['customer_id'] : 0;
    if ($customer_id <= 0 && !empty($request['customers'])) {
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request['customers']))));
        if (count($ids) === 1) {
            $customer_id = $ids[0];
        }
    }

    if ($customer_id <= 0) {
        return array('error' => 'Please select one customer for the ledger statement.');
    }
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
        'customer_id' => $customer_id,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'from_mysql' => $from_dt->format('Y-m-d'),
        'to_mysql' => $to_dt->format('Y-m-d'),
    );
}

function pending_payments_ledger_client($conn, $customer_id)
{
    $customer_id = (int) $customer_id;
    $q = mysqli_query($conn, "SELECT client_id, client_company_name, gst_no, contact_no, address1, address2, city, state, pincode
        FROM client WHERE client_id='$customer_id' LIMIT 1");
    if (!$q || !($row = mysqli_fetch_assoc($q))) {
        return null;
    }
    $name = trim((string) ($row['client_company_name'] ?? ''));
    if ($name === '') {
        $name = 'Customer #' . $customer_id;
    }
    $addr_parts = array_filter(array(
        trim((string) ($row['address1'] ?? '')),
        trim((string) ($row['address2'] ?? '')),
        trim((string) ($row['city'] ?? '')),
        trim((string) ($row['state'] ?? '')),
        trim((string) ($row['pincode'] ?? '')),
    ));
    return array(
        'client_id' => $customer_id,
        'name' => $name,
        'gst_no' => trim((string) ($row['gst_no'] ?? '')),
        'contact_no' => trim((string) ($row['contact_no'] ?? '')),
        'address' => implode(', ', $addr_parts),
    );
}

function pending_payments_ledger_date_clause($alias_col, $from_mysql, $to_mysql, $mode)
{
    /* mode: before | range | all */
    $col = "STR_TO_DATE($alias_col, '%d-%m-%Y')";
    if ($mode === 'before') {
        return "$col < '" . $from_mysql . "'";
    }
    if ($mode === 'range') {
        return "$col >= '" . $from_mysql . "' AND $col <= '" . $to_mysql . "'";
    }
    return '1=1';
}

/**
 * Collect raw ledger events for a customer.
 * @param string $mode before|range|all
 */
function pending_payments_ledger_raw_events($conn, $customer_id, $from_mysql, $to_mysql, $mode)
{
    $customer_id = (int) $customer_id;
    $from_esc = mysqli_real_escape_string($conn, $from_mysql);
    $to_esc = mysqli_real_escape_string($conn, $to_mysql);
    $events = array();

    /* Tax invoices → Debit */
    $date_inv = pending_payments_ledger_date_clause('m.invoice_date', $from_esc, $to_esc, $mode);
    $sql = "SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.grand_total
        FROM billing_invoice_master m
        WHERE m.status='final' AND m.customer_id='$customer_id' AND $date_inv";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($r['invoice_date'] ?? '');
            if (!$dt) {
                continue;
            }
            $amt = round((float) $r['grand_total'], 2);
            if ($amt <= 0) {
                continue;
            }
            $events[] = array(
                'sort_date' => $dt->format('Y-m-d'),
                'display_date' => $dt->format('d-m-Y'),
                'type' => 'invoice',
                'type_order' => 10,
                'doc_id' => (int) $r['billing_invoice_id'],
                'ref' => (string) ($r['invoice_no'] ?? ''),
                'particulars' => 'Tax Invoice ' . ($r['invoice_no'] ?? ''),
                'debit' => $amt,
                'credit' => 0.0,
            );
        }
    }

    /* Debit notes → Debit */
    $date_dn = pending_payments_ledger_date_clause('n.note_date', $from_esc, $to_esc, $mode);
    $sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.grand_total, n.against_invoice_id, inv.invoice_no
        FROM billing_note_master n
        LEFT JOIN billing_invoice_master inv ON inv.billing_invoice_id = n.against_invoice_id
        WHERE n.status='final' AND n.party_type='client' AND n.party_id='$customer_id'
        AND n.note_type='DN' AND $date_dn";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($r['note_date'] ?? '');
            if (!$dt) {
                continue;
            }
            $amt = round((float) $r['grand_total'], 2);
            if ($amt <= 0) {
                continue;
            }
            $vs = trim((string) ($r['invoice_no'] ?? ''));
            $events[] = array(
                'sort_date' => $dt->format('Y-m-d'),
                'display_date' => $dt->format('d-m-Y'),
                'type' => 'debit_note',
                'type_order' => 20,
                'doc_id' => (int) $r['billing_note_id'],
                'ref' => (string) ($r['note_no'] ?? ''),
                'particulars' => 'Debit Note ' . ($r['note_no'] ?? '') . ($vs !== '' ? ' vs ' . $vs : ''),
                'debit' => $amt,
                'credit' => 0.0,
            );
        }
    }

    /* Credit notes → Credit */
    $date_cn = pending_payments_ledger_date_clause('n.note_date', $from_esc, $to_esc, $mode);
    $sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.grand_total, n.against_invoice_id, inv.invoice_no
        FROM billing_note_master n
        LEFT JOIN billing_invoice_master inv ON inv.billing_invoice_id = n.against_invoice_id
        WHERE n.status='final' AND n.party_type='client' AND n.party_id='$customer_id'
        AND n.note_type='CN' AND $date_cn";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($r['note_date'] ?? '');
            if (!$dt) {
                continue;
            }
            $amt = round((float) $r['grand_total'], 2);
            if ($amt <= 0) {
                continue;
            }
            $vs = trim((string) ($r['invoice_no'] ?? ''));
            $events[] = array(
                'sort_date' => $dt->format('Y-m-d'),
                'display_date' => $dt->format('d-m-Y'),
                'type' => 'credit_note',
                'type_order' => 30,
                'doc_id' => (int) $r['billing_note_id'],
                'ref' => (string) ($r['note_no'] ?? ''),
                'particulars' => 'Credit Note ' . ($r['note_no'] ?? '') . ($vs !== '' ? ' vs ' . $vs : ''),
                'debit' => 0.0,
                'credit' => $amt,
            );
        }
    }

    /* Against-invoice receipts: payment + TDS as separate credits */
    $date_rcp = pending_payments_ledger_date_clause('m.receipt_date', $from_esc, $to_esc, $mode);
    $sql = "SELECT m.billing_receipt_id, m.receipt_no, m.receipt_date, m.payment_mode, m.payment_ref,
            l.billing_invoice_id, l.invoice_no, l.receive_now, l.tds_amount
        FROM billing_receipt_lines l
        INNER JOIN billing_receipt_master m ON m.billing_receipt_id = l.billing_receipt_id
        INNER JOIN billing_invoice_master inv ON inv.billing_invoice_id = l.billing_invoice_id
        WHERE m.status='final' AND m.receipt_type='against_invoice'
        AND inv.customer_id='$customer_id' AND $date_rcp";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($r['receipt_date'] ?? '');
            if (!$dt) {
                continue;
            }
            $paid = round((float) ($r['receive_now'] ?? 0), 2);
            $tds = round((float) ($r['tds_amount'] ?? 0), 2);
            $rcp = trim((string) ($r['receipt_no'] ?? ''));
            $inv_no = trim((string) ($r['invoice_no'] ?? ''));
            $mode_pay = trim((string) ($r['payment_mode'] ?? ''));
            $ref_pay = trim((string) ($r['payment_ref'] ?? ''));
            $extra = '';
            if ($mode_pay !== '') {
                $extra .= ' · ' . $mode_pay;
            }
            if ($ref_pay !== '') {
                $extra .= ' · Ref ' . $ref_pay;
            }
            if ($paid > 0) {
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type' => 'payment',
                    'type_order' => 40,
                    'doc_id' => (int) $r['billing_receipt_id'],
                    'ref' => $rcp,
                    'particulars' => 'Payment ' . $rcp . ($inv_no !== '' ? ' vs ' . $inv_no : '') . $extra,
                    'debit' => 0.0,
                    'credit' => $paid,
                );
            }
            if ($tds > 0) {
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type' => 'tds',
                    'type_order' => 50,
                    'doc_id' => (int) $r['billing_receipt_id'],
                    'ref' => $rcp,
                    'particulars' => 'TDS ' . $rcp . ($inv_no !== '' ? ' vs ' . $inv_no : ''),
                    'debit' => 0.0,
                    'credit' => $tds,
                );
            }
        }
    }

    /* Advance / general / investment receipts on party */
    $date_adv = pending_payments_ledger_date_clause('m.receipt_date', $from_esc, $to_esc, $mode);
    $sql = "SELECT m.billing_receipt_id, m.receipt_no, m.receipt_date, m.receipt_type, m.total_amount, m.payment_mode, m.payment_ref
        FROM billing_receipt_master m
        WHERE m.status='final' AND m.party_id='$customer_id'
        AND m.receipt_type IN ('advance','general','investment')
        AND $date_adv";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($r['receipt_date'] ?? '');
            if (!$dt) {
                continue;
            }
            $amt = round((float) ($r['total_amount'] ?? 0), 2);
            if ($amt <= 0) {
                continue;
            }
            $rtype = ucfirst(str_replace('_', ' ', (string) ($r['receipt_type'] ?? 'receipt')));
            $rcp = trim((string) ($r['receipt_no'] ?? ''));
            $events[] = array(
                'sort_date' => $dt->format('Y-m-d'),
                'display_date' => $dt->format('d-m-Y'),
                'type' => 'advance',
                'type_order' => 60,
                'doc_id' => (int) $r['billing_receipt_id'],
                'ref' => $rcp,
                'particulars' => $rtype . ' Receipt ' . $rcp,
                'debit' => 0.0,
                'credit' => $amt,
            );
        }
    }

    usort($events, function ($a, $b) {
        if ($a['sort_date'] !== $b['sort_date']) {
            return strcmp($a['sort_date'], $b['sort_date']);
        }
        if ($a['type_order'] !== $b['type_order']) {
            return $a['type_order'] - $b['type_order'];
        }
        return $a['doc_id'] - $b['doc_id'];
    });

    return $events;
}

function pending_payments_ledger_net_from_events($events)
{
    $net = 0.0;
    foreach ($events as $e) {
        $net += (float) $e['debit'] - (float) $e['credit'];
    }
    return round($net, 2);
}

function pending_payments_ledger_fetch($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array(
            'client' => null,
            'opening_balance' => 0.0,
            'lines' => array(),
            'summary' => array(
                'total_debit' => '0.00',
                'total_credit' => '0.00',
                'opening_balance' => '0.00',
                'closing_balance' => '0.00',
                'row_count' => 0,
            ),
        );
    }

    ensure_billing_tables($conn);
    ensure_billing_receipt_tables($conn);
    ensure_billing_note_tables($conn);

    $customer_id = (int) $filters['customer_id'];
    $client = pending_payments_ledger_client($conn, $customer_id);
    if (!$client) {
        return array(
            'error' => 'Customer not found.',
            'client' => null,
            'opening_balance' => 0.0,
            'lines' => array(),
            'summary' => array(
                'total_debit' => '0.00',
                'total_credit' => '0.00',
                'opening_balance' => '0.00',
                'closing_balance' => '0.00',
                'row_count' => 0,
            ),
        );
    }

    $before = pending_payments_ledger_raw_events(
        $conn,
        $customer_id,
        $filters['from_mysql'],
        $filters['to_mysql'],
        'before'
    );
    $opening = pending_payments_ledger_net_from_events($before);

    $period = pending_payments_ledger_raw_events(
        $conn,
        $customer_id,
        $filters['from_mysql'],
        $filters['to_mysql'],
        'range'
    );

    $running = $opening;
    $lines = array();
    $total_debit = 0.0;
    $total_credit = 0.0;

    $lines[] = array(
        'display_date' => $filters['from_date'],
        'particulars' => 'Opening balance',
        'ref' => '',
        'debit' => '',
        'credit' => '',
        'balance' => pending_payments_format_money($running),
        'is_opening' => true,
        'is_closing' => false,
    );

    foreach ($period as $e) {
        $d = (float) $e['debit'];
        $c = (float) $e['credit'];
        $running = round($running + $d - $c, 2);
        $total_debit += $d;
        $total_credit += $c;
        $lines[] = array(
            'display_date' => $e['display_date'],
            'particulars' => $e['particulars'],
            'ref' => $e['ref'],
            'debit' => $d > 0 ? pending_payments_format_money($d) : '',
            'credit' => $c > 0 ? pending_payments_format_money($c) : '',
            'balance' => pending_payments_format_money($running),
            'is_opening' => false,
            'is_closing' => false,
            'type' => $e['type'],
        );
    }

    $closing = $running;
    $lines[] = array(
        'display_date' => $filters['to_date'],
        'particulars' => 'Closing balance',
        'ref' => '',
        'debit' => '',
        'credit' => '',
        'balance' => pending_payments_format_money($closing),
        'is_opening' => false,
        'is_closing' => true,
    );

    return array(
        'client' => $client,
        'opening_balance' => $opening,
        'closing_balance' => $closing,
        'lines' => $lines,
        'summary' => array(
            'total_debit' => pending_payments_format_money($total_debit),
            'total_credit' => pending_payments_format_money($total_credit),
            'opening_balance' => pending_payments_format_money($opening),
            'closing_balance' => pending_payments_format_money($closing),
            'row_count' => count($period),
        ),
    );
}
