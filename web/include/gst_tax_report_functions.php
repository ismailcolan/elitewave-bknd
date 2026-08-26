<?php

require_once __DIR__ . '/gst_tax_functions.php';

function gst_tax_report_text($val)
{
    if ($val === null || $val === false) {
        return '';
    }
    $val = (string) $val;
    if (function_exists('mb_convert_encoding')) {
        $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
    }
    return $val;
}

function gst_tax_report_format_money($val)
{
    return number_format((float) $val, 2, '.', '');
}

function gst_tax_report_format_gst_type($gst_type)
{
    $gst_type = strtolower(trim((string) $gst_type));
    $map = array(
        'intra' => 'Intra',
        'inter' => 'Inter',
        'exempt' => 'Exempt',
        'non_gst' => 'Non-GST',
        'auto' => 'Auto',
    );
    return isset($map[$gst_type]) ? $map[$gst_type] : strtoupper($gst_type);
}

function gst_tax_report_parse_filters($request)
{
    $from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
    $to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
    $customers = isset($request['customers']) ? trim($request['customers']) : '';
    $gst_type = isset($request['gst_type']) ? trim($request['gst_type']) : '';
    $tax_code = isset($request['tax_code']) ? trim($request['tax_code']) : '';

    if ($from_date === '' || $to_date === '') {
        return array('error' => 'From Date and To Date are required.');
    }

    $from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
    $to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
    if (!$from_dt || !$to_dt) {
        return array('error' => 'Invalid date format. Use dd-mm-yyyy.');
    }

    return array(
        'from_date' => $from_date,
        'to_date' => $to_date,
        'from_mysql' => $from_dt->format('Y-m-d'),
        'to_mysql' => $to_dt->format('Y-m-d'),
        'customers' => $customers,
        'gst_type' => $gst_type,
        'tax_code' => $tax_code,
        'grn_nos' => isset($request['grn_nos']) ? trim($request['grn_nos']) : '',
    );
}

function gst_tax_report_table_has_gst_columns($conn, $table_name)
{
    $col_chk = @mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'gst_type'");
    return ($col_chk && mysqli_num_rows($col_chk) > 0);
}

function gst_tax_report_fetch_rows($conn, $filters)
{
    if (!empty($filters['error'])) {
        return array('error' => $filters['error'], 'rows' => array(), 'summary' => gst_tax_report_empty_summary());
    }

    $all_tables_q = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
    $table_list = array();
    if ($all_tables_q) {
        while ($tbl_row = mysqli_fetch_assoc($all_tables_q)) {
            $table_list[] = $tbl_row['table_name'];
        }
    }

    $union_queries = array();
    foreach ($table_list as $tbl_name) {
        $t_trans = 'transaction_' . $tbl_name;
        $chk = @mysqli_query($conn, "SELECT 1 FROM `$t_trans` LIMIT 1");
        if (!$chk) {
            continue;
        }
        if (!gst_tax_report_table_has_gst_columns($conn, $t_trans)) {
            if (function_exists('ensure_transaction_gst_columns')) {
                ensure_transaction_gst_columns($conn, $t_trans);
            }
            if (!gst_tax_report_table_has_gst_columns($conn, $t_trans)) {
                continue;
            }
        }

        $where = "STR_TO_DATE(t.grn_date, '%d-%m-%Y') >= '" . mysqli_real_escape_string($conn, $filters['from_mysql']) . "'
            AND STR_TO_DATE(t.grn_date, '%d-%m-%Y') <= '" . mysqli_real_escape_string($conn, $filters['to_mysql']) . "'
            AND (t.booking_status IS NULL OR t.booking_status = '' OR t.booking_status != '1')";

        if (!empty($filters['customers'])) {
            $cust_list = implode(',', array_map('intval', explode(',', $filters['customers'])));
            if ($cust_list !== '') {
                $where .= " AND t.consigner IN ($cust_list)";
            }
        }
        if (!empty($filters['gst_type']) && $filters['gst_type'] !== 'all') {
            $gst_type_sql = mysqli_real_escape_string($conn, strtolower($filters['gst_type']));
            $where .= " AND LOWER(IFNULL(t.gst_type,'')) = '$gst_type_sql'";
        }
        if (!empty($filters['tax_code']) && $filters['tax_code'] !== 'all') {
            $tax_code_sql = mysqli_real_escape_string($conn, strtoupper($filters['tax_code']));
            $where .= " AND UPPER(IFNULL(t.gst_tax_code,'')) = '$tax_code_sql'";
        }
        if (!empty($filters['grn_nos'])) {
            $grn_parts = array();
            foreach (explode(',', $filters['grn_nos']) as $grn) {
                $grn = trim($grn);
                if ($grn !== '') {
                    $grn_parts[] = "'" . mysqli_real_escape_string($conn, $grn) . "'";
                }
            }
            if (!empty($grn_parts)) {
                $where .= ' AND t.grn_no IN (' . implode(',', $grn_parts) . ')';
            }
        }

        $union_queries[] = "SELECT
            t.grn_date,
            t.grn_no,
            t.consigner,
            t.gst_type,
            t.gst_tax_code,
            t.taxable_value,
            t.cgst_amount,
            t.sgst_amount,
            t.igst_amount,
            t.cess_amount,
            t.gst_amount,
            t.total
        FROM `$t_trans` t
        WHERE $where";
    }

    if (empty($union_queries)) {
        return array('rows' => array(), 'summary' => gst_tax_report_empty_summary());
    }

    $final_query = implode(' UNION ALL ', $union_queries) . ' ORDER BY grn_date DESC, grn_no DESC';
    $result = @mysqli_query($conn, $final_query);

    $rows = array();
    $summary = gst_tax_report_empty_summary();
    $i = 1;

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $taxable = (float) ($row['taxable_value'] ?? 0);
            $cgst = (float) ($row['cgst_amount'] ?? 0);
            $sgst = (float) ($row['sgst_amount'] ?? 0);
            $igst = (float) ($row['igst_amount'] ?? 0);
            $cess = (float) ($row['cess_amount'] ?? 0);
            $gst_total = (float) ($row['gst_amount'] ?? 0);
            $grand = (float) ($row['total'] ?? 0);

            $formatted = array(
                's_no' => $i,
                'grn_no' => gst_tax_report_text($row['grn_no']),
                'grn_date' => gst_tax_report_text($row['grn_date']),
                'customer' => gst_tax_report_text(@get_client_name($conn, $row['consigner'])),
                'gst_type' => gst_tax_report_format_gst_type($row['gst_type'] ?? ''),
                'tax_code' => gst_tax_report_text($row['gst_tax_code'] ?? ''),
                'taxable_value' => gst_tax_report_format_money($taxable),
                'cgst_amount' => gst_tax_report_format_money($cgst),
                'sgst_amount' => gst_tax_report_format_money($sgst),
                'igst_amount' => gst_tax_report_format_money($igst),
                'cess_amount' => gst_tax_report_format_money($cess),
                'gst_amount' => gst_tax_report_format_money($gst_total),
                'grand_total' => gst_tax_report_format_money($grand),
            );

            $rows[] = $formatted;

            $summary['booking_count']++;
            $summary['taxable_value'] += $taxable;
            $summary['cgst_amount'] += $cgst;
            $summary['sgst_amount'] += $sgst;
            $summary['igst_amount'] += $igst;
            $summary['cess_amount'] += $cess;
            $summary['gst_amount'] += $gst_total;
            $summary['grand_total'] += $grand;
            $i++;
        }
    }

    foreach (array('taxable_value', 'cgst_amount', 'sgst_amount', 'igst_amount', 'cess_amount', 'gst_amount', 'grand_total') as $key) {
        $summary[$key] = gst_tax_report_format_money($summary[$key]);
    }

    return array('rows' => $rows, 'summary' => $summary);
}

function gst_tax_report_empty_summary()
{
    return array(
        'booking_count' => 0,
        'taxable_value' => '0.00',
        'cgst_amount' => '0.00',
        'sgst_amount' => '0.00',
        'igst_amount' => '0.00',
        'cess_amount' => '0.00',
        'gst_amount' => '0.00',
        'grand_total' => '0.00',
    );
}

function gst_tax_report_amount_in_words($number)
{
    $number = (float) $number;
    $no = (int) floor($number);
    $point = (int) round(($number - $no) * 100);
    $hundred = null;
    $digits_1 = strlen((string) $no);
    $i = 0;
    $str = array();
    $words = array(
        '0' => '', '1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four',
        '5' => 'five', '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine',
        '10' => 'ten', '11' => 'eleven', '12' => 'twelve', '13' => 'thirteen',
        '14' => 'fourteen', '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen',
        '18' => 'eighteen', '19' => 'nineteen', '20' => 'twenty', '30' => 'thirty',
        '40' => 'forty', '50' => 'fifty', '60' => 'sixty', '70' => 'seventy',
        '80' => 'eighty', '90' => 'ninety',
    );
    $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $chunk = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($chunk) {
            $plural = (($counter = count($str)) && $chunk > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($chunk < 21)
                ? $words[$chunk] . ' ' . $digits[$counter] . $plural . ' ' . $hundred
                : $words[floor($chunk / 10) * 10] . ' ' . $words[$chunk % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else {
            $str[] = null;
        }
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point)
        ? $words[floor($point / 10) * 10] . ' ' . $words[$point % 10]
        : '';
    if ($points != '') {
        return ucfirst(trim($result)) . 'Rupees  ' . $points . ' Paise Only';
    }
    return ucfirst(trim($result)) . 'Rupees Only';
}

function gst_tax_report_filter_summary($conn, $filters)
{
    $customer_label = 'All Customers';
    if (!empty($filters['customers'])) {
        $names = array();
        foreach (explode(',', $filters['customers']) as $cid) {
            $cid = (int) $cid;
            if ($cid > 0) {
                $names[] = get_client_name($conn, $cid);
            }
        }
        if (!empty($names)) {
            $customer_label = implode(', ', $names);
        }
    }

    $gst_type_label = 'All';
    if (!empty($filters['gst_type']) && $filters['gst_type'] !== 'all') {
        $gst_type_label = gst_tax_report_format_gst_type($filters['gst_type']);
    }

    $tax_code_label = 'All';
    if (!empty($filters['tax_code']) && $filters['tax_code'] !== 'all') {
        $tax_code_label = strtoupper($filters['tax_code']);
    }

    return array(
        'period' => $filters['from_date'] . ' to ' . $filters['to_date'],
        'customer' => $customer_label,
        'gst_type' => $gst_type_label,
        'tax_code' => $tax_code_label,
    );
}
