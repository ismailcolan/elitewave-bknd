<?php

function ensure_gst_tax_master_table($conn)
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS gst_tax_master (
        gst_tax_id INT AUTO_INCREMENT PRIMARY KEY,
        tax_code VARCHAR(30) NOT NULL,
        tax_name VARCHAR(120) NOT NULL,
        gst_rate DECIMAL(8,2) NOT NULL DEFAULT 0,
        cgst_rate DECIMAL(8,2) NOT NULL DEFAULT 0,
        sgst_rate DECIMAL(8,2) NOT NULL DEFAULT 0,
        igst_rate DECIMAL(8,2) NOT NULL DEFAULT 0,
        cess_rate DECIMAL(8,2) NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        is_deleted TINYINT(1) NOT NULL DEFAULT 0,
        created_at VARCHAR(20) DEFAULT NULL,
        created_by INT DEFAULT NULL,
        updated_at VARCHAR(20) DEFAULT NULL,
        updated_by INT DEFAULT NULL,
        UNIQUE KEY uq_gst_tax_code (tax_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    gst_tax_seed_defaults($conn);
}

function gst_tax_seed_defaults($conn)
{
    $count_q = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM gst_tax_master WHERE is_deleted=0");
    $count_row = mysqli_fetch_assoc($count_q);
    if ((int) ($count_row['cnt'] ?? 0) > 0) {
        return;
    }

    $defaults = array(
        array('GST0', 'GST 0%', 0, 0, 0, 0, 0),
        array('GST5', 'GST 5%', 5, 2.5, 2.5, 5, 0),
        array('GST12', 'GST 12%', 12, 6, 6, 12, 0),
        array('GST18', 'GST 18%', 18, 9, 9, 18, 0),
        array('GST28', 'GST 28%', 28, 14, 14, 28, 0),
    );

    $now = date('d-m-Y');
    foreach ($defaults as $row) {
        list($code, $name, $gst, $cgst, $sgst, $igst, $cess) = $row;
        $code = mysqli_real_escape_string($conn, $code);
        $name = mysqli_real_escape_string($conn, $name);
        mysqli_query($conn, "INSERT IGNORE INTO gst_tax_master
            (tax_code, tax_name, gst_rate, cgst_rate, sgst_rate, igst_rate, cess_rate, status, is_deleted, created_at)
            VALUES ('$code', '$name', '$gst', '$cgst', '$sgst', '$igst', '$cess', 1, 0, '$now')");
    }
}

function gst_tax_calc_components($gst_rate)
{
    $gst = round((float) $gst_rate, 2);
    $half = round($gst / 2, 2);

    return array(
        'gst_rate' => $gst,
        'cgst_rate' => $half,
        'sgst_rate' => $half,
        'igst_rate' => $gst,
    );
}

function gst_tax_validate_payload($gst_rate, $cgst_rate, $sgst_rate, $igst_rate, $cess_rate)
{
    $rates = array($gst_rate, $cgst_rate, $sgst_rate, $igst_rate, $cess_rate);
    foreach ($rates as $rate) {
        if (!is_numeric($rate) || (float) $rate < 0) {
            return 'Tax rates cannot be negative.';
        }
    }

    $gst = round((float) $gst_rate, 2);
    $cgst = round((float) $cgst_rate, 2);
    $sgst = round((float) $sgst_rate, 2);
    $igst = round((float) $igst_rate, 2);

    if (abs(($cgst + $sgst) - $gst) > 0.01) {
        return 'CGST + SGST/UTGST must equal GST Rate.';
    }
    if (abs($igst - $gst) > 0.01) {
        return 'IGST must equal GST Rate.';
    }

    return '';
}

function gst_tax_code_exists($conn, $tax_code, $exclude_id = 0)
{
    $tax_code = mysqli_real_escape_string($conn, strtoupper(trim($tax_code)));
    $exclude_id = (int) $exclude_id;
    $sql = "SELECT gst_tax_id FROM gst_tax_master WHERE tax_code='$tax_code' AND is_deleted=0";
    if ($exclude_id > 0) {
        $sql .= " AND gst_tax_id!='$exclude_id'";
    }
    $sql .= ' LIMIT 1';
    $q = mysqli_query($conn, $sql);

    return $q && mysqli_num_rows($q) > 0;
}

function gst_tax_fetch_list($conn, $filters = array())
{
    $where = array('1=1');
    $search = isset($filters['search']) ? trim($filters['search']) : '';
    $status = isset($filters['status']) ? trim($filters['status']) : 'all';
    $gst_filter = isset($filters['gst_rate']) ? trim($filters['gst_rate']) : 'all';
    $deleted_filter = isset($filters['deleted']) ? trim($filters['deleted']) : 'active';

    if ($deleted_filter === 'deleted') {
        $where[] = 'is_deleted=1';
    } elseif ($deleted_filter === 'all') {
        /* no deleted filter */
    } else {
        $where[] = 'is_deleted=0';
    }

    if ($status === 'active') {
        $where[] = 'status=1';
    } elseif ($status === 'inactive') {
        $where[] = 'status=0';
    }

    if ($gst_filter === '0') {
        $where[] = 'gst_rate=0';
    } elseif ($gst_filter === '5') {
        $where[] = 'gst_rate=5';
    } elseif ($gst_filter === '12') {
        $where[] = 'gst_rate=12';
    } elseif ($gst_filter === '18') {
        $where[] = 'gst_rate=18';
    } elseif ($gst_filter === '28') {
        $where[] = 'gst_rate=28';
    } elseif ($gst_filter === 'other') {
        $where[] = 'gst_rate NOT IN (0,5,12,18,28)';
    }

    if ($search !== '') {
        $s = mysqli_real_escape_string($conn, $search);
        $where[] = "(tax_code LIKE '%$s%' OR tax_name LIKE '%$s%')";
    }

    $sql = 'SELECT * FROM gst_tax_master WHERE ' . implode(' AND ', $where) . ' ORDER BY gst_rate ASC, tax_code ASC';
    $q = mysqli_query($conn, $sql);
    $rows = array();
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function gst_tax_format_rate($value)
{
    return number_format((float) $value, 2) . '%';
}

function gst_tax_get_active_profiles($conn)
{
    ensure_gst_tax_master_table($conn);

    return gst_tax_fetch_list($conn, array(
        'status' => 'active',
        'deleted' => 'active',
    ));
}

function gst_tax_fetch_profile($conn, $gst_tax_id)
{
    $gst_tax_id = (int) $gst_tax_id;
    if ($gst_tax_id <= 0) {
        return null;
    }

    $q = mysqli_query($conn, "SELECT * FROM gst_tax_master WHERE gst_tax_id='$gst_tax_id' AND is_deleted=0 LIMIT 1");
    if (!$q) {
        return null;
    }

    $row = mysqli_fetch_assoc($q);

    return $row ?: null;
}

function gst_tax_get_city_state_id($conn, $city_id)
{
    $city_id = (int) $city_id;
    if ($city_id <= 0) {
        return 0;
    }

    $q = mysqli_query($conn, "SELECT state FROM city WHERE city_id='$city_id' LIMIT 1");
    if (!$q) {
        return 0;
    }

    $row = mysqli_fetch_assoc($q);

    return (int) ($row['state'] ?? 0);
}

function gst_tax_determine_type_from_route($origin_state_id, $destination_state_id)
{
    $origin_state_id = (int) $origin_state_id;
    $destination_state_id = (int) $destination_state_id;

    if ($origin_state_id <= 0 || $destination_state_id <= 0) {
        return '';
    }

    return ($origin_state_id === $destination_state_id) ? 'intra' : 'inter';
}

function gst_tax_resolve_type($gst_type_input, $origin_state_id, $destination_state_id)
{
    $gst_type_input = strtolower(trim((string) $gst_type_input));
    if ($gst_type_input === '' || $gst_type_input === 'auto') {
        return gst_tax_determine_type_from_route($origin_state_id, $destination_state_id);
    }

    return $gst_type_input;
}

function gst_tax_calc_taxable_from_charges($charges)
{
    $fields = array(
        'frieght_amount',
        'doc_amount',
        'mamul_charge',
        'vehicle_halting_charge',
        'vehicle_loading_unloading',
        'other_amount',
        'rajdhani_charges',
    );

    $total = 0;
    foreach ($fields as $field) {
        $total += (float) ($charges[$field] ?? 0);
    }

    return round($total, 2);
}

function gst_tax_calc_breakup($taxable_value, $profile, $gst_type)
{
    $taxable = round((float) $taxable_value, 2);
    $gst_type = strtolower(trim((string) $gst_type));

    $result = array(
        'taxable_value' => $taxable,
        'gst_type' => $gst_type,
        'gst_rate' => 0,
        'cgst_rate' => 0,
        'sgst_rate' => 0,
        'igst_rate' => 0,
        'cess_rate' => 0,
        'cgst_amount' => 0,
        'sgst_amount' => 0,
        'igst_amount' => 0,
        'cess_amount' => 0,
        'gst_amount' => 0,
        'grand_total' => $taxable,
    );

    if (!$profile || in_array($gst_type, array('exempt', 'non_gst', ''), true)) {
        return $result;
    }

    $cess_rate = (float) ($profile['cess_rate'] ?? 0);
    $result['cess_rate'] = $cess_rate;
    $result['gst_rate'] = (float) ($profile['gst_rate'] ?? 0);

    if ($gst_type === 'intra') {
        $result['cgst_rate'] = (float) ($profile['cgst_rate'] ?? 0);
        $result['sgst_rate'] = (float) ($profile['sgst_rate'] ?? 0);
        $result['cgst_amount'] = round($taxable * $result['cgst_rate'] / 100, 2);
        $result['sgst_amount'] = round($taxable * $result['sgst_rate'] / 100, 2);
        $result['cess_amount'] = round($taxable * $cess_rate / 100, 2);
    } elseif ($gst_type === 'inter') {
        $result['igst_rate'] = (float) ($profile['igst_rate'] ?? 0);
        $result['igst_amount'] = round($taxable * $result['igst_rate'] / 100, 2);
        $result['cess_amount'] = round($taxable * $cess_rate / 100, 2);
    } else {
        return $result;
    }

    $result['gst_amount'] = round(
        $result['cgst_amount'] + $result['sgst_amount'] + $result['igst_amount'] + $result['cess_amount'],
        2
    );
    $result['grand_total'] = round($taxable + $result['gst_amount'], 2);

    return $result;
}

function gst_tax_build_booking_snapshot($conn, $post, $company_state_id = 0)
{
    $origin_state_id = gst_tax_get_city_state_id($conn, $post['origin'] ?? 0);
    $destination_state_id = gst_tax_get_city_state_id($conn, $post['destination'] ?? 0);
    if (!empty($post['origin_state_id'])) {
        $origin_state_id = (int) $post['origin_state_id'];
    }
    if (!empty($post['destination_state_id'])) {
        $destination_state_id = (int) $post['destination_state_id'];
    }
    $gst_type = gst_tax_resolve_type($post['gst_type'] ?? 'auto', $origin_state_id, $destination_state_id);
    $taxable_value = gst_tax_calc_taxable_from_charges($post);
    $profile = gst_tax_fetch_profile($conn, $post['gst_tax_id'] ?? 0);
    $breakup = gst_tax_calc_breakup($taxable_value, $profile, $gst_type);

    if (in_array($gst_type, array('exempt', 'non_gst'), true)) {
        $profile = gst_tax_fetch_profile_by_code($conn, 'GST0') ?: $profile;
        if ($profile) {
            $breakup = gst_tax_calc_breakup($taxable_value, $profile, $gst_type);
            $breakup['gst_type'] = $gst_type;
        }
    }

    $breakup['gst_tax_id'] = $profile ? (int) $profile['gst_tax_id'] : 0;
    $breakup['gst_tax_code'] = $profile ? ($profile['tax_code'] ?? '') : '';
    $breakup['origin_state_id'] = $origin_state_id;
    $breakup['destination_state_id'] = $destination_state_id;
    $breakup['bill_to_state_id'] = $destination_state_id;

    return $breakup;
}

function gst_tax_fetch_profile_by_code($conn, $tax_code)
{
    $tax_code = mysqli_real_escape_string($conn, strtoupper(trim($tax_code)));
    $q = mysqli_query($conn, "SELECT * FROM gst_tax_master WHERE tax_code='$tax_code' AND is_deleted=0 LIMIT 1");
    if (!$q) {
        return null;
    }

    $row = mysqli_fetch_assoc($q);

    return $row ?: null;
}

function ensure_transaction_gst_columns($conn, $table_name)
{
    $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $table_name);
    if ($table_name === '') {
        return;
    }

    $columns = array(
        'gst_type' => "VARCHAR(20) DEFAULT NULL",
        'gst_tax_id' => "INT DEFAULT NULL",
        'gst_tax_code' => "VARCHAR(30) DEFAULT NULL",
        'cgst_rate' => "DECIMAL(8,2) NOT NULL DEFAULT 0",
        'sgst_rate' => "DECIMAL(8,2) NOT NULL DEFAULT 0",
        'igst_rate' => "DECIMAL(8,2) NOT NULL DEFAULT 0",
        'cess_rate' => "DECIMAL(8,2) NOT NULL DEFAULT 0",
        'cgst_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
        'sgst_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
        'igst_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
        'cess_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
        'taxable_value' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
        'bill_to_state_id' => "INT DEFAULT NULL",
    );

    $existing = array();
    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name`");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $existing[$row['Field']] = true;
        }
    }

    foreach ($columns as $column => $definition) {
        if (!isset($existing[$column])) {
            mysqli_query($conn, "ALTER TABLE `$table_name` ADD COLUMN `$column` $definition");
        }
    }
}
