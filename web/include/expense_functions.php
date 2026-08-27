<?php
/**
 * Extra expense module — GCN lookup, totals, expense number.
 */

function expense_fy_suffix($date_str)
{
    $parts = explode('-', trim($date_str));
    if (count($parts) !== 3) {
        $parts = explode('-', date('d-m-Y'));
    }
    $year = (int) ($parts[2] ?? date('Y'));
    $p_y = substr((string) ($year - 1), -2);
    $c_y = substr((string) $year, -2);
    return $p_y . '-' . $c_y;
}

function expense_next_number($conn, $expense_date)
{
    $fy = expense_fy_suffix($expense_date);
    $fy_esc = mysqli_real_escape_string($conn, $fy);
    $seq = 1;
    $q = mysqli_query($conn, "SELECT expense_no FROM extra_expense WHERE expense_no LIKE 'EXT/%/$fy_esc' ORDER BY expense_id DESC LIMIT 1");
    if ($q && ($row = mysqli_fetch_assoc($q)) && !empty($row['expense_no'])) {
        $parts = explode('/', $row['expense_no']);
        if (isset($parts[1]) && ctype_digit($parts[1])) {
            $seq = (int) $parts[1] + 1;
        }
    }
    return 'EXT/' . sprintf('%05d', $seq) . '/' . $fy;
}

function expense_sum_by_grn($conn, $grn_no, $exclude_expense_id = 0)
{
    $grn_esc = mysqli_real_escape_string($conn, trim($grn_no));
    $exclude = (int) $exclude_expense_id;
    $exclude_sql = $exclude > 0 ? " AND expense_id != '$exclude' " : '';
    $q = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM extra_expense WHERE grn_no='$grn_esc' AND status=0 $exclude_sql");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) $row['total'];
    }
    return 0;
}

function expense_lookup_grn($conn, $grn_no)
{
    $grn_no = trim($grn_no);
    if ($grn_no === '') {
        return array('status' => 0, 'message' => 'GCN number is required.');
    }
    $grn_esc = mysqli_real_escape_string($conn, $grn_no);
    $tbl_q = mysqli_query($conn, 'SELECT table_name FROM transaction_tbls ORDER BY table_name');
    if (!$tbl_q) {
        return array('status' => 0, 'message' => 'Unable to search GCN.');
    }

    while ($tbl_row = mysqli_fetch_assoc($tbl_q)) {
        $suffix = $tbl_row['table_name'];
        $table = 'transaction_' . $suffix;
        $chk = @mysqli_query($conn, "SELECT 1 FROM `$table` LIMIT 1");
        if (!$chk) {
            continue;
        }
        $q = mysqli_query($conn, "SELECT transaction_id, grn_no, grn_date, origin, destination, consigner, consignee, mode_of_transportation, frieght_amount, booking_status FROM `$table` WHERE grn_no='$grn_esc' LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            if ((string) ($row['booking_status'] ?? '') === '1') {
                return array('status' => 0, 'message' => 'This GCN is cancelled. Extra expense cannot be added.');
            }
            $extra_total = expense_sum_by_grn($conn, $grn_no);
            $freight = (float) ($row['frieght_amount'] ?? 0);
            return array(
                'status' => 1,
                'message' => 'GCN found.',
                'data' => array(
                    'transaction_id' => (int) $row['transaction_id'],
                    'trans_table' => $suffix,
                    'grn_no' => $row['grn_no'],
                    'grn_date' => $row['grn_date'],
                    'origin' => get_city_name($conn, $row['origin']),
                    'destination' => get_city_name($conn, $row['destination']),
                    'consignor' => get_client_name($conn, $row['consigner']),
                    'consignee' => get_client_name($conn, $row['consignee']),
                    'mode' => get_mode($conn, $row['mode_of_transportation']),
                    'estimated_freight' => round($freight),
                    'extra_paid_total' => round($extra_total),
                ),
            );
        }
    }

    return array('status' => 0, 'message' => 'GCN not found. Please check the number.');
}

function expense_format_rupee($amount)
{
    return number_format((float) $amount, 0, '.', ',');
}

function expense_category_options($conn, $selected_id = 0)
{
    $html = '<option value="">Select Category</option>';
    $q = mysqli_query($conn, 'SELECT category_id, category_name FROM expense_category WHERE status=0 ORDER BY category_name');
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $sel = ((int) $selected_id === (int) $row['category_id']) ? ' selected' : '';
            $html .= '<option value="' . (int) $row['category_id'] . '"' . $sel . '>' . htmlspecialchars($row['category_name']) . '</option>';
        }
    }
    return $html;
}

function expense_vendor_options($conn, $selected_id = 0)
{
    $html = '<option value="">Select Paid To</option>';
    $q = mysqli_query($conn, 'SELECT vendor_id, vendor_name, vendor_type FROM expense_vendor WHERE status=0 ORDER BY vendor_name');
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $sel = ((int) $selected_id === (int) $row['vendor_id']) ? ' selected' : '';
            $html .= '<option value="' . (int) $row['vendor_id'] . '"' . $sel . '>' . htmlspecialchars($row['vendor_name']) . '</option>';
        }
    }
    return $html;
}

function expense_auto_category_code($conn, $category_name)
{
    $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', $category_name));
    if ($base === '') {
        $base = 'CAT';
    }
    $base = substr($base, 0, 12);
    $code = $base;
    $n = 1;
    while (true) {
        $code_esc = mysqli_real_escape_string($conn, $code);
        $q = mysqli_query($conn, "SELECT category_id FROM expense_category WHERE category_code='$code_esc' LIMIT 1");
        if (!$q || mysqli_num_rows($q) === 0) {
            return $code;
        }
        $n++;
        $code = substr($base, 0, 10) . $n;
    }
}
