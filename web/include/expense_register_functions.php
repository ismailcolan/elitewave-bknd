<?php

require_once __DIR__ . '/expense_general_helpers.php';

function expense_register_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function expense_register_parse_filters($request)
{
    $from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
    $to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
    $vendors = isset($request['vendors']) ? trim($request['vendors']) : '';
    $categories = isset($request['categories']) ? trim($request['categories']) : '';

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
        'categories' => $categories,
    );
}

function expense_register_empty_summary()
{
    return array(
        'expense_amount' => expense_register_format_money(0),
        'gst_amount' => expense_register_format_money(0),
        'tds_amount' => expense_register_format_money(0),
        'other_deduction' => expense_register_format_money(0),
        'net_payable' => expense_register_format_money(0),
        'row_count' => 0,
    );
}

function expense_register_fetch_rows($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array('rows' => array(), 'summary' => expense_register_empty_summary());
    }

    expense_general_ensure_schema($conn);

    $from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
    $to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);

    $where = "h.status=0
        AND STR_TO_DATE(h.expense_date, '%d-%m-%Y') >= '$from_esc'
        AND STR_TO_DATE(h.expense_date, '%d-%m-%Y') <= '$to_esc'";

    if (!empty($filters['vendors'])) {
        $ids = array_filter(array_map('intval', explode(',', $filters['vendors'])));
        if (!empty($ids)) {
            $where .= ' AND h.vendor_id IN (' . implode(',', $ids) . ')';
        }
    }
    if (!empty($filters['categories'])) {
        $ids = array_filter(array_map('intval', explode(',', $filters['categories'])));
        if (!empty($ids)) {
            $where .= ' AND h.category_id IN (' . implode(',', $ids) . ')';
        }
    }

    $sql = "SELECT h.*
        FROM general_expense_header h
        WHERE $where
        ORDER BY STR_TO_DATE(h.expense_date, '%d-%m-%Y') ASC, h.general_expense_id ASC";

    $rows = array();
    $sum_amt = 0.0;
    $sum_gst = 0.0;
    $sum_tds = 0.0;
    $sum_other = 0.0;
    $sum_net = 0.0;
    $sno = 0;
    $modes = function_exists('ew_company_bank_payment_modes') ? ew_company_bank_payment_modes() : array();

    $q = mysqli_query($conn, $sql);
    if ($q) {
        while ($raw = mysqli_fetch_assoc($q)) {
            $rec = expense_general_format_record($conn, $raw);
            $sno++;
            $amt = (float) ($rec['expense_amount_raw'] ?? 0);
            $gst = (float) ($rec['gst_amount_raw'] ?? 0);
            $tds = (float) ($rec['tds_amount_raw'] ?? 0);
            $other = (float) ($rec['other_deduction_raw'] ?? 0);
            $net = (float) ($rec['net_payable_raw'] ?? 0);

            $sum_amt += $amt;
            $sum_gst += $gst;
            $sum_tds += $tds;
            $sum_other += $other;
            $sum_net += $net;

            $pm = strtoupper(trim((string) ($rec['payment_mode'] ?? '')));
            $pm_label = $modes[$pm] ?? ($pm !== '' ? $pm : '—');
            $desc = trim((string) ($rec['description'] ?? ''));
            if ($desc === '') {
                $desc = '—';
            }

            $rows[] = array(
                's_no' => $sno,
                'expense_no' => $rec['expense_no'] !== '' ? $rec['expense_no'] : '—',
                'expense_date' => $rec['expense_date'] !== '' ? $rec['expense_date'] : '—',
                'vendor' => $rec['vendor_label'] !== '' ? $rec['vendor_label'] : '—',
                'category' => $rec['category_label'] !== '' ? $rec['category_label'] : '—',
                'expense_amount' => expense_register_format_money($amt),
                'gst_amount' => expense_register_format_money($gst),
                'tds_amount' => expense_register_format_money($tds),
                'other_deduction' => expense_register_format_money($other),
                'net_payable' => expense_register_format_money($net),
                'payment_mode' => $pm_label,
                'description' => $desc,
            );
        }
    }

    return array(
        'rows' => $rows,
        'summary' => array(
            'expense_amount' => expense_register_format_money($sum_amt),
            'gst_amount' => expense_register_format_money($sum_gst),
            'tds_amount' => expense_register_format_money($sum_tds),
            'other_deduction' => expense_register_format_money($sum_other),
            'net_payable' => expense_register_format_money($sum_net),
            'row_count' => count($rows),
        ),
    );
}
