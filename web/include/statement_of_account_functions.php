<?php

require_once __DIR__ . '/pending_payments_ledger_functions.php';
require_once __DIR__ . '/expense_bill_helpers.php';
require_once __DIR__ . '/expense_general_helpers.php';
require_once __DIR__ . '/billing_note_functions.php';

function soa_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function soa_parse_filters($request)
{
    $party_type = isset($request['party_type']) ? strtolower(trim($request['party_type'])) : 'customer';
    if ($party_type !== 'vendor') {
        $party_type = 'customer';
    }

    $from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
    $to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
    $party_id = isset($request['party_id']) ? (int) $request['party_id'] : 0;

    if ($party_id <= 0) {
        return array('error' => 'Please select one ' . ($party_type === 'vendor' ? 'vendor' : 'customer') . '.');
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
        'party_type' => $party_type,
        'party_id' => $party_id,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'from_mysql' => $from_dt->format('Y-m-d'),
        'to_mysql' => $to_dt->format('Y-m-d'),
    );
}

function soa_empty_result($error = '')
{
    $out = array(
        'party' => null,
        'party_type' => 'customer',
        'opening_balance' => 0.0,
        'closing_balance' => 0.0,
        'lines' => array(),
        'summary' => array(
            'total_debit' => '0.00',
            'total_credit' => '0.00',
            'opening_balance' => '0.00',
            'closing_balance' => '0.00',
            'row_count' => 0,
            'balance_label' => 'Receivable',
        ),
    );
    if ($error !== '') {
        $out['error'] = $error;
    }
    return $out;
}

function soa_fetch_vendor_party($conn, $vendor_id)
{
    ew_vendor_ensure_table($conn);
    $vendor_id = (int) $vendor_id;
    $q = mysqli_query($conn, "SELECT * FROM vendor_master WHERE vendor_id='$vendor_id' LIMIT 1");
    if (!$q || !($row = mysqli_fetch_assoc($q))) {
        return null;
    }
    $code = trim((string) ($row['vendor_code'] ?? ''));
    $name = trim((string) ($row['vendor_name'] ?? ''));
    $label = trim($code . ' - ' . $name, ' -');
    if ($label === '') {
        $label = 'Vendor #' . $vendor_id;
    }
    $addr_parts = array_filter(array(
        trim((string) ($row['address1'] ?? '')),
        trim((string) ($row['address2'] ?? '')),
        trim((string) ($row['city'] ?? '')),
        trim((string) ($row['state'] ?? '')),
        trim((string) ($row['pincode'] ?? '')),
    ));
    $phone = trim((string) ($row['contact_no'] ?? ''));
    if ($phone === '') {
        $phone = trim((string) ($row['contact_no2'] ?? ''));
    }
    return array(
        'party_id' => $vendor_id,
        'name' => $label,
        'gst_no' => trim((string) ($row['gstin'] ?? '')),
        'contact_no' => $phone,
        'address' => implode(', ', $addr_parts),
    );
}

function soa_date_clause($alias_col, $from_mysql, $to_mysql, $mode)
{
    return pending_payments_ledger_date_clause($alias_col, $from_mysql, $to_mysql, $mode);
}

/**
 * Vendor payable events (real-time):
 * Debit  = Bill Registration (increases payable)
 * Credit = Debit Note to vendor + Payment Voucher / Expense (reduces payable)
 * Balance = Debit − Credit (positive = we owe vendor)
 */
function soa_vendor_raw_events($conn, $vendor_id, $from_mysql, $to_mysql, $mode)
{
    $vendor_id = (int) $vendor_id;
    $from_esc = mysqli_real_escape_string($conn, $from_mysql);
    $to_esc = mysqli_real_escape_string($conn, $to_mysql);
    $events = array();

    expense_bill_ensure_schema($conn);
    expense_general_ensure_schema($conn);
    ensure_billing_note_tables($conn);

    /* Vendor bills → Debit (payable up) */
    $date_bill = soa_date_clause('b.bill_date', $from_esc, $to_esc, $mode);
    $sql = "SELECT b.bill_id, b.bill_entry_no, b.bill_date, b.vendor_invoice_no, b.grand_total, b.net_payable, b.tds_amount
        FROM vendor_bill b
        WHERE b.status=0 AND b.vendor_id='$vendor_id' AND $date_bill";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($r['bill_date'] ?? '');
            if (!$dt) {
                continue;
            }
            $grand = round((float) ($r['grand_total'] ?? 0), 2);
            $tds = round((float) ($r['tds_amount'] ?? 0), 2);
            $net = round((float) ($r['net_payable'] ?? 0), 2);
            if ($net <= 0 && $grand > 0) {
                $net = max(0, round($grand - $tds, 2));
            }
            if ($net <= 0) {
                continue;
            }
            $entry = trim((string) ($r['bill_entry_no'] ?? ''));
            $vinv = trim((string) ($r['vendor_invoice_no'] ?? ''));
            $particulars = 'Vendor Bill ' . $entry;
            if ($vinv !== '') {
                $particulars .= ' · Inv ' . $vinv;
            }
            $events[] = array(
                'sort_date' => $dt->format('Y-m-d'),
                'display_date' => $dt->format('d-m-Y'),
                'type' => 'vendor_bill',
                'type_order' => 10,
                'doc_id' => (int) $r['bill_id'],
                'ref' => $entry,
                'particulars' => $particulars,
                'debit' => $net,
                'credit' => 0.0,
            );
        }
    }

    /* Debit notes to vendor → Credit (payable down) */
    $date_dn = soa_date_clause('n.note_date', $from_esc, $to_esc, $mode);
    $sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.grand_total, n.against_invoice_id,
            COALESCE(NULLIF(b.vendor_invoice_no,''), b.bill_entry_no) AS bill_ref
        FROM billing_note_master n
        LEFT JOIN vendor_bill b ON b.bill_id = n.against_invoice_id
        WHERE n.status='final' AND n.note_type='DN' AND n.party_type='vendor'
        AND n.party_id='$vendor_id' AND $date_dn";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($r['note_date'] ?? '');
            if (!$dt) {
                continue;
            }
            $amt = round((float) ($r['grand_total'] ?? 0), 2);
            if ($amt <= 0) {
                continue;
            }
            $note_no = trim((string) ($r['note_no'] ?? ''));
            $vs = trim((string) ($r['bill_ref'] ?? ''));
            $events[] = array(
                'sort_date' => $dt->format('Y-m-d'),
                'display_date' => $dt->format('d-m-Y'),
                'type' => 'debit_note',
                'type_order' => 20,
                'doc_id' => (int) $r['billing_note_id'],
                'ref' => $note_no,
                'particulars' => 'Debit Note ' . $note_no . ($vs !== '' ? ' vs ' . $vs : ''),
                'debit' => 0.0,
                'credit' => $amt,
            );
        }
    }

    /* Payment vouchers / general expenses → Credit (payment out reduces payable) */
    $date_exp = soa_date_clause('h.expense_date', $from_esc, $to_esc, $mode);
    $sql = "SELECT h.general_expense_id, h.expense_no, h.expense_date, h.net_payable, h.payment_mode, h.voucher_type
        FROM general_expense_header h
        WHERE h.status=0 AND h.vendor_id='$vendor_id' AND $date_exp";
    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $dt = pending_payments_parse_date_dmY($r['expense_date'] ?? '');
            if (!$dt) {
                continue;
            }
            $amt = round((float) ($r['net_payable'] ?? 0), 2);
            if ($amt <= 0) {
                continue;
            }
            $eno = trim((string) ($r['expense_no'] ?? ''));
            $vtype = strtolower(trim((string) ($r['voucher_type'] ?? 'general')));
            $label = ($vtype === 'gcn') ? 'GCN Payment Voucher' : 'Payment Voucher';
            $mode_pay = trim((string) ($r['payment_mode'] ?? ''));
            $extra = $mode_pay !== '' ? (' · ' . strtoupper($mode_pay)) : '';
            $events[] = array(
                'sort_date' => $dt->format('Y-m-d'),
                'display_date' => $dt->format('d-m-Y'),
                'type' => 'payment_voucher',
                'type_order' => 40,
                'doc_id' => (int) $r['general_expense_id'],
                'ref' => $eno,
                'particulars' => $label . ' ' . $eno . $extra,
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

function soa_build_lines_from_events($events_before, $events_period, $from_date, $to_date)
{
    $opening = pending_payments_ledger_net_from_events($events_before);
    $running = $opening;
    $lines = array();
    $total_debit = 0.0;
    $total_credit = 0.0;

    $lines[] = array(
        'display_date' => $from_date,
        'particulars' => 'Opening balance',
        'ref' => '',
        'debit' => '',
        'credit' => '',
        'balance' => soa_format_money($running),
        'is_opening' => true,
        'is_closing' => false,
    );

    foreach ($events_period as $e) {
        $d = (float) $e['debit'];
        $c = (float) $e['credit'];
        $running = round($running + $d - $c, 2);
        $total_debit += $d;
        $total_credit += $c;
        $lines[] = array(
            'display_date' => $e['display_date'],
            'particulars' => $e['particulars'],
            'ref' => $e['ref'],
            'debit' => $d > 0 ? soa_format_money($d) : '',
            'credit' => $c > 0 ? soa_format_money($c) : '',
            'balance' => soa_format_money($running),
            'is_opening' => false,
            'is_closing' => false,
            'type' => $e['type'],
        );
    }

    $closing = $running;
    $lines[] = array(
        'display_date' => $to_date,
        'particulars' => 'Closing balance',
        'ref' => '',
        'debit' => '',
        'credit' => '',
        'balance' => soa_format_money($closing),
        'is_opening' => false,
        'is_closing' => true,
    );

    return array(
        'opening_balance' => $opening,
        'closing_balance' => $closing,
        'lines' => $lines,
        'summary' => array(
            'total_debit' => soa_format_money($total_debit),
            'total_credit' => soa_format_money($total_credit),
            'opening_balance' => soa_format_money($opening),
            'closing_balance' => soa_format_money($closing),
            'row_count' => count($events_period),
        ),
    );
}

function soa_fetch_customer($conn, $filters)
{
    $ledger_filters = array(
        'customer_id' => (int) $filters['party_id'],
        'from_date' => $filters['from_date'],
        'to_date' => $filters['to_date'],
        'from_mysql' => $filters['from_mysql'],
        'to_mysql' => $filters['to_mysql'],
    );
    $ledger = pending_payments_ledger_fetch($conn, $ledger_filters);
    if (!empty($ledger['error'])) {
        return soa_empty_result($ledger['error']);
    }
    $client = $ledger['client'] ?: array();
    $party = array(
        'party_id' => (int) ($client['client_id'] ?? $filters['party_id']),
        'name' => $client['name'] ?? '—',
        'gst_no' => $client['gst_no'] ?? '',
        'contact_no' => $client['contact_no'] ?? '',
        'address' => $client['address'] ?? '',
    );
    $summary = $ledger['summary'];
    $summary['balance_label'] = 'Receivable';

    return array(
        'party' => $party,
        'party_type' => 'customer',
        'opening_balance' => $ledger['opening_balance'],
        'closing_balance' => $ledger['closing_balance'],
        'lines' => $ledger['lines'],
        'summary' => $summary,
        'hint' => 'Debit = invoices / debit notes (receivable). Credit = payments / credit notes / TDS / advances (settled). Balance = running receivable.',
    );
}

function soa_fetch_vendor($conn, $filters)
{
    $vendor_id = (int) $filters['party_id'];
    $party = soa_fetch_vendor_party($conn, $vendor_id);
    if (!$party) {
        return soa_empty_result('Vendor not found.');
    }

    $before = soa_vendor_raw_events($conn, $vendor_id, $filters['from_mysql'], $filters['to_mysql'], 'before');
    $period = soa_vendor_raw_events($conn, $vendor_id, $filters['from_mysql'], $filters['to_mysql'], 'range');
    $built = soa_build_lines_from_events($before, $period, $filters['from_date'], $filters['to_date']);
    $built['summary']['balance_label'] = 'Payable';

    return array(
        'party' => $party,
        'party_type' => 'vendor',
        'opening_balance' => $built['opening_balance'],
        'closing_balance' => $built['closing_balance'],
        'lines' => $built['lines'],
        'summary' => $built['summary'],
        'hint' => 'Debit = vendor bills (payable). Credit = debit notes / payment vouchers (settled). Balance = running payable.',
    );
}

function soa_fetch($conn, $filters)
{
    if (!empty($filters['error'])) {
        $empty = soa_empty_result($filters['error']);
        $empty['party_type'] = isset($filters['party_type']) ? $filters['party_type'] : 'customer';
        return $empty;
    }
    if (($filters['party_type'] ?? 'customer') === 'vendor') {
        return soa_fetch_vendor($conn, $filters);
    }
    return soa_fetch_customer($conn, $filters);
}
