<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_receipt_functions.php';
require_once __DIR__ . '/billing_note_functions.php';
require_once __DIR__ . '/expense_bill_helpers.php';
require_once __DIR__ . '/expense_general_helpers.php';
require_once __DIR__ . '/petty_cash_helpers.php';

function day_book_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function day_book_voucher_type_options()
{
    return array(
        '' => 'All types',
        'invoice' => 'Tax Invoice',
        'receipt' => 'Payment Receipt',
        'credit_note' => 'Credit Note',
        'debit_note' => 'Debit Note',
        'vendor_bill' => 'Bill Registration',
        'payment_voucher' => 'Payment Voucher',
        'petty_cash' => 'Petty Cash',
    );
}

function day_book_parse_filters($request)
{
    $from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
    $to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
    $voucher_type = isset($request['voucher_type']) ? trim($request['voucher_type']) : '';

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

    $allowed = array_keys(day_book_voucher_type_options());
    if (!in_array($voucher_type, $allowed, true)) {
        $voucher_type = '';
    }

    return array(
        'from_date' => $from_date,
        'to_date' => $to_date,
        'from_mysql' => $from_dt->format('Y-m-d'),
        'to_mysql' => $to_dt->format('Y-m-d'),
        'voucher_type' => $voucher_type,
    );
}

function day_book_empty_summary()
{
    return array(
        'total_debit' => day_book_format_money(0),
        'total_credit' => day_book_format_money(0),
        'row_count' => 0,
    );
}

function day_book_date_between($alias_col, $from_esc, $to_esc)
{
    return "STR_TO_DATE($alias_col, '%d-%m-%Y') >= '$from_esc'
        AND STR_TO_DATE($alias_col, '%d-%m-%Y') <= '$to_esc'";
}

function day_book_parse_dmy($date_str)
{
    $dt = DateTime::createFromFormat('d-m-Y', trim((string) $date_str));
    return ($dt instanceof DateTime) ? $dt : null;
}

function day_book_want_type($filters, $type)
{
    $selected = $filters['voucher_type'] ?? '';
    return ($selected === '' || $selected === $type);
}

/**
 * Live Day Book rows from existing documents (no separate save).
 */
function day_book_fetch_rows($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array('rows' => array(), 'summary' => day_book_empty_summary());
    }

    ensure_billing_tables($conn);
    ensure_billing_receipt_tables($conn);
    ensure_billing_note_tables($conn);
    expense_bill_ensure_schema($conn);
    expense_general_ensure_schema($conn);
    petty_cash_ensure_schema($conn);

    $from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
    $to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);
    $events = array();

    /* Tax Invoice → Debit */
    if (day_book_want_type($filters, 'invoice')) {
        $date_sql = day_book_date_between('m.invoice_date', $from_esc, $to_esc);
        $sql = "SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.grand_total, c.client_company_name
            FROM billing_invoice_master m
            LEFT JOIN client c ON c.client_id = m.customer_id
            WHERE m.status='final' AND COALESCE(m.is_active,1)=1 AND $date_sql";
        $q = mysqli_query($conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $dt = day_book_parse_dmy($r['invoice_date'] ?? '');
                if (!$dt) {
                    continue;
                }
                $amt = round((float) ($r['grand_total'] ?? 0), 2);
                if ($amt <= 0) {
                    continue;
                }
                $party = trim((string) ($r['client_company_name'] ?? ''));
                $ref = trim((string) ($r['invoice_no'] ?? ''));
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type_key' => 'invoice',
                    'type_order' => 10,
                    'doc_id' => (int) $r['billing_invoice_id'],
                    'voucher_type' => 'Tax Invoice',
                    'voucher_no' => $ref !== '' ? $ref : ('INV-' . (int) $r['billing_invoice_id']),
                    'party' => $party !== '' ? $party : '—',
                    'particulars' => 'Tax Invoice ' . ($ref !== '' ? $ref : ''),
                    'debit' => $amt,
                    'credit' => 0.0,
                );
            }
        }
    }

    /* Payment Receipt → Credit */
    if (day_book_want_type($filters, 'receipt')) {
        $date_sql = day_book_date_between('m.receipt_date', $from_esc, $to_esc);
        $sql = "SELECT m.billing_receipt_id, m.receipt_no, m.receipt_date, m.receipt_type, m.total_amount,
                c.client_company_name
            FROM billing_receipt_master m
            LEFT JOIN client c ON c.client_id = m.party_id
            WHERE m.status='final' AND $date_sql";
        $q = mysqli_query($conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $dt = day_book_parse_dmy($r['receipt_date'] ?? '');
                if (!$dt) {
                    continue;
                }
                $amt = round((float) ($r['total_amount'] ?? 0), 2);
                if ($amt <= 0) {
                    continue;
                }
                $party = trim((string) ($r['client_company_name'] ?? ''));
                $ref = trim((string) ($r['receipt_no'] ?? ''));
                $rtype = trim((string) ($r['receipt_type'] ?? ''));
                $rtype_label = $rtype !== '' ? ucwords(str_replace('_', ' ', $rtype)) : 'Receipt';
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type_key' => 'receipt',
                    'type_order' => 20,
                    'doc_id' => (int) $r['billing_receipt_id'],
                    'voucher_type' => 'Payment Receipt',
                    'voucher_no' => $ref !== '' ? $ref : ('RCP-' . (int) $r['billing_receipt_id']),
                    'party' => $party !== '' ? $party : '—',
                    'particulars' => $rtype_label . ' Receipt ' . $ref,
                    'debit' => 0.0,
                    'credit' => $amt,
                );
            }
        }
    }

    /* Credit Note (customer) → Credit */
    if (day_book_want_type($filters, 'credit_note')) {
        $date_sql = day_book_date_between('n.note_date', $from_esc, $to_esc);
        $sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.grand_total, c.client_company_name, inv.invoice_no
            FROM billing_note_master n
            LEFT JOIN client c ON c.client_id = n.party_id
            LEFT JOIN billing_invoice_master inv ON inv.billing_invoice_id = n.against_invoice_id
            WHERE n.status='final' AND n.note_type='CN' AND n.party_type='client' AND $date_sql";
        $q = mysqli_query($conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $dt = day_book_parse_dmy($r['note_date'] ?? '');
                if (!$dt) {
                    continue;
                }
                $amt = round((float) ($r['grand_total'] ?? 0), 2);
                if ($amt <= 0) {
                    continue;
                }
                $party = trim((string) ($r['client_company_name'] ?? ''));
                $ref = trim((string) ($r['note_no'] ?? ''));
                $vs = trim((string) ($r['invoice_no'] ?? ''));
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type_key' => 'credit_note',
                    'type_order' => 30,
                    'doc_id' => (int) $r['billing_note_id'],
                    'voucher_type' => 'Credit Note',
                    'voucher_no' => $ref !== '' ? $ref : ('CN-' . (int) $r['billing_note_id']),
                    'party' => $party !== '' ? $party : '—',
                    'particulars' => 'Credit Note ' . $ref . ($vs !== '' ? ' vs ' . $vs : ''),
                    'debit' => 0.0,
                    'credit' => $amt,
                );
            }
        }
    }

    /* Debit Note (vendor) → Credit (reduces payable) */
    if (day_book_want_type($filters, 'debit_note')) {
        $date_sql = day_book_date_between('n.note_date', $from_esc, $to_esc);
        $sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.grand_total,
                TRIM(CONCAT(COALESCE(v.vendor_code,''), ' - ', COALESCE(v.vendor_name,''))) AS vendor_label,
                COALESCE(NULLIF(b.vendor_invoice_no,''), b.bill_entry_no) AS bill_ref
            FROM billing_note_master n
            LEFT JOIN vendor_master v ON v.vendor_id = n.party_id
            LEFT JOIN vendor_bill b ON b.bill_id = n.against_invoice_id
            WHERE n.status='final' AND n.note_type='DN' AND n.party_type='vendor' AND $date_sql";
        $q = mysqli_query($conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $dt = day_book_parse_dmy($r['note_date'] ?? '');
                if (!$dt) {
                    continue;
                }
                $amt = round((float) ($r['grand_total'] ?? 0), 2);
                if ($amt <= 0) {
                    continue;
                }
                $party = trim((string) ($r['vendor_label'] ?? ''), ' -');
                $ref = trim((string) ($r['note_no'] ?? ''));
                $vs = trim((string) ($r['bill_ref'] ?? ''));
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type_key' => 'debit_note',
                    'type_order' => 40,
                    'doc_id' => (int) $r['billing_note_id'],
                    'voucher_type' => 'Debit Note',
                    'voucher_no' => $ref !== '' ? $ref : ('DN-' . (int) $r['billing_note_id']),
                    'party' => $party !== '' ? $party : '—',
                    'particulars' => 'Debit Note ' . $ref . ($vs !== '' ? ' vs ' . $vs : ''),
                    'debit' => 0.0,
                    'credit' => $amt,
                );
            }
        }
    }

    /* Bill Registration → Debit */
    if (day_book_want_type($filters, 'vendor_bill')) {
        $date_sql = day_book_date_between('b.bill_date', $from_esc, $to_esc);
        $sql = "SELECT b.bill_id, b.bill_entry_no, b.bill_date, b.vendor_invoice_no, b.net_payable, b.grand_total,
                TRIM(CONCAT(COALESCE(v.vendor_code,''), ' - ', COALESCE(v.vendor_name,''))) AS vendor_label
            FROM vendor_bill b
            LEFT JOIN vendor_master v ON v.vendor_id = b.vendor_id
            WHERE b.status=0 AND $date_sql";
        $q = mysqli_query($conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $dt = day_book_parse_dmy($r['bill_date'] ?? '');
                if (!$dt) {
                    continue;
                }
                $amt = round((float) ($r['net_payable'] ?? 0), 2);
                if ($amt <= 0) {
                    $amt = round((float) ($r['grand_total'] ?? 0), 2);
                }
                if ($amt <= 0) {
                    continue;
                }
                $party = trim((string) ($r['vendor_label'] ?? ''), ' -');
                $ref = trim((string) ($r['bill_entry_no'] ?? ''));
                $vinv = trim((string) ($r['vendor_invoice_no'] ?? ''));
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type_key' => 'vendor_bill',
                    'type_order' => 50,
                    'doc_id' => (int) $r['bill_id'],
                    'voucher_type' => 'Bill Registration',
                    'voucher_no' => $ref !== '' ? $ref : ('BILL-' . (int) $r['bill_id']),
                    'party' => $party !== '' ? $party : '—',
                    'particulars' => 'Vendor Bill ' . $ref . ($vinv !== '' ? ' · Inv ' . $vinv : ''),
                    'debit' => $amt,
                    'credit' => 0.0,
                );
            }
        }
    }

    /* Payment Voucher → Credit */
    if (day_book_want_type($filters, 'payment_voucher')) {
        $date_sql = day_book_date_between('h.expense_date', $from_esc, $to_esc);
        $sql = "SELECT h.general_expense_id, h.expense_no, h.expense_date, h.net_payable, h.voucher_type, h.payment_mode,
                TRIM(CONCAT(COALESCE(v.vendor_code,''), ' - ', COALESCE(v.vendor_name,''))) AS vendor_label
            FROM general_expense_header h
            LEFT JOIN vendor_master v ON v.vendor_id = h.vendor_id
            WHERE h.status=0 AND $date_sql";
        $q = mysqli_query($conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $dt = day_book_parse_dmy($r['expense_date'] ?? '');
                if (!$dt) {
                    continue;
                }
                $amt = round((float) ($r['net_payable'] ?? 0), 2);
                if ($amt <= 0) {
                    continue;
                }
                $party = trim((string) ($r['vendor_label'] ?? ''), ' -');
                $ref = trim((string) ($r['expense_no'] ?? ''));
                $vtype = strtolower(trim((string) ($r['voucher_type'] ?? 'general')));
                $label = ($vtype === 'gcn') ? 'GCN Payment Voucher' : 'Payment Voucher';
                $mode = strtoupper(trim((string) ($r['payment_mode'] ?? '')));
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type_key' => 'payment_voucher',
                    'type_order' => 60,
                    'doc_id' => (int) $r['general_expense_id'],
                    'voucher_type' => 'Payment Voucher',
                    'voucher_no' => $ref !== '' ? $ref : ('PV-' . (int) $r['general_expense_id']),
                    'party' => $party !== '' ? $party : '—',
                    'particulars' => $label . ' ' . $ref . ($mode !== '' ? ' · ' . $mode : ''),
                    'debit' => 0.0,
                    'credit' => $amt,
                );
            }
        }
    }

    /* Petty Cash — Receipt = Debit (cash in), Payment = Credit (cash out) */
    if (day_book_want_type($filters, 'petty_cash')) {
        $date_sql = day_book_date_between('p.voucher_date', $from_esc, $to_esc);
        $sql = "SELECT p.petty_cash_id, p.voucher_no, p.voucher_date, p.entry_type, p.amount,
                p.particulars, p.paid_to
            FROM petty_cash_voucher p
            WHERE p.status=0 AND $date_sql";
        $q = mysqli_query($conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $dt = day_book_parse_dmy($r['voucher_date'] ?? '');
                if (!$dt) {
                    continue;
                }
                $amt = round((float) ($r['amount'] ?? 0), 2);
                if ($amt <= 0) {
                    continue;
                }
                $etype = strtolower(trim((string) ($r['entry_type'] ?? 'payment')));
                $is_receipt = ($etype === 'receipt');
                $ref = trim((string) ($r['voucher_no'] ?? ''));
                $party = trim((string) ($r['paid_to'] ?? ''));
                $part = trim((string) ($r['particulars'] ?? ''));
                $type_label = $is_receipt ? 'Petty Cash Receipt' : 'Petty Cash Payment';
                $events[] = array(
                    'sort_date' => $dt->format('Y-m-d'),
                    'display_date' => $dt->format('d-m-Y'),
                    'type_key' => 'petty_cash',
                    'type_order' => 70,
                    'doc_id' => (int) $r['petty_cash_id'],
                    'voucher_type' => 'Petty Cash',
                    'voucher_no' => $ref !== '' ? $ref : ('PC-' . (int) $r['petty_cash_id']),
                    'party' => $party !== '' ? $party : '—',
                    'particulars' => $type_label . ($part !== '' ? ' · ' . $part : ''),
                    'debit' => $is_receipt ? $amt : 0.0,
                    'credit' => $is_receipt ? 0.0 : $amt,
                );
            }
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

    $rows = array();
    $sum_debit = 0.0;
    $sum_credit = 0.0;
    $sno = 0;
    foreach ($events as $e) {
        $sno++;
        $d = (float) $e['debit'];
        $c = (float) $e['credit'];
        $sum_debit += $d;
        $sum_credit += $c;
        $rows[] = array(
            's_no' => $sno,
            'entry_date' => $e['display_date'],
            'voucher_type' => $e['voucher_type'],
            'voucher_no' => $e['voucher_no'],
            'party' => $e['party'],
            'particulars' => $e['particulars'],
            'debit' => $d > 0 ? day_book_format_money($d) : '',
            'credit' => $c > 0 ? day_book_format_money($c) : '',
            'type_key' => $e['type_key'],
        );
    }

    return array(
        'rows' => $rows,
        'summary' => array(
            'total_debit' => day_book_format_money($sum_debit),
            'total_credit' => day_book_format_money($sum_credit),
            'row_count' => count($rows),
        ),
    );
}
