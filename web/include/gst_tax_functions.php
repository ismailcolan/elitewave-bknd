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

    $tds_col = mysqli_query($conn, "SHOW COLUMNS FROM gst_tax_master LIKE 'tds_rate'");
    if ($tds_col && mysqli_num_rows($tds_col) === 0) {
        mysqli_query($conn, "ALTER TABLE gst_tax_master ADD COLUMN tds_rate DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER cess_rate");
    }

    gst_tax_seed_defaults($conn);
    gst_tax_seed_tds_defaults($conn);
}

function gst_tax_seed_tds_defaults($conn)
{
    $tds_defaults = array(
        array('TDS1', 'TDS 1%', 1),
        array('TDS2', 'TDS 2%', 2),
        array('TDS5', 'TDS 5%', 5),
        array('TDS10', 'TDS 10%', 10),
    );
    $now = date('d-m-Y');
    foreach ($tds_defaults as $row) {
        list($code, $name, $tds) = $row;
        if (gst_tax_code_exists($conn, $code)) {
            continue;
        }
        $code = mysqli_real_escape_string($conn, $code);
        $name = mysqli_real_escape_string($conn, $name);
        mysqli_query($conn, "INSERT INTO gst_tax_master
            (tax_code, tax_name, gst_rate, cgst_rate, sgst_rate, igst_rate, cess_rate, tds_rate, status, is_deleted, created_at)
            VALUES ('$code', '$name', '0', '0', '0', '0', '0', '$tds', 1, 0, '$now')");
    }
}

function gst_tax_seed_defaults($conn)
{
    $count_q = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM gst_tax_master WHERE is_deleted=0");
    $count_row = mysqli_fetch_assoc($count_q);
    if ((int) ($count_row['cnt'] ?? 0) > 0) {
        return;
    }

    $defaults = array(
        array('GST0', 'GST 0%', 0, 0, 0, 0, 0, 0),
        array('GST5', 'GST 5%', 5, 2.5, 2.5, 5, 0, 0),
        array('GST12', 'GST 12%', 12, 6, 6, 12, 0, 0),
        array('GST18', 'GST 18%', 18, 9, 9, 18, 0, 0),
        array('GST28', 'GST 28%', 28, 14, 14, 28, 0, 0),
        array('TDS1', 'TDS 1%', 0, 0, 0, 0, 0, 1),
        array('TDS2', 'TDS 2%', 0, 0, 0, 0, 0, 2),
        array('TDS5', 'TDS 5%', 0, 0, 0, 0, 0, 5),
        array('TDS10', 'TDS 10%', 0, 0, 0, 0, 0, 10),
    );

    $now = date('d-m-Y');
    foreach ($defaults as $row) {
        list($code, $name, $gst, $cgst, $sgst, $igst, $cess, $tds) = $row;
        $code = mysqli_real_escape_string($conn, $code);
        $name = mysqli_real_escape_string($conn, $name);
        mysqli_query($conn, "INSERT IGNORE INTO gst_tax_master
            (tax_code, tax_name, gst_rate, cgst_rate, sgst_rate, igst_rate, cess_rate, tds_rate, status, is_deleted, created_at)
            VALUES ('$code', '$name', '$gst', '$cgst', '$sgst', '$igst', '$cess', '$tds', 1, 0, '$now')");
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

function gst_tax_validate_payload($gst_rate, $cgst_rate, $sgst_rate, $igst_rate, $cess_rate, $tds_rate = 0)
{
    $rates = array($gst_rate, $cgst_rate, $sgst_rate, $igst_rate, $cess_rate, $tds_rate);
    foreach ($rates as $rate) {
        if (!is_numeric($rate) || (float) $rate < 0) {
            return 'Tax rates cannot be negative.';
        }
    }

    $gst = round((float) $gst_rate, 2);
    $cgst = round((float) $cgst_rate, 2);
    $sgst = round((float) $sgst_rate, 2);
    $igst = round((float) $igst_rate, 2);

    if ($gst > 0) {
        if (abs(($cgst + $sgst) - $gst) > 0.01) {
            return 'CGST + SGST/UTGST must equal GST Rate.';
        }
        if (abs($igst - $gst) > 0.01) {
            return 'IGST must equal GST Rate.';
        }
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

/**
 * GST Tax Master rows for vendor GST dropdown (excludes TDS-only profiles).
 */
function gst_tax_vendor_gst_options($conn)
{
    $rows = gst_tax_get_active_profiles($conn);
    $out = array();
    foreach ($rows as $row) {
        $gst = (float) ($row['gst_rate'] ?? 0);
        $tds = (float) ($row['tds_rate'] ?? 0);
        $code = strtoupper((string) ($row['tax_code'] ?? ''));
        if ($tds > 0 && $gst <= 0 && strpos($code, 'TDS') === 0) {
            continue;
        }
        if ($gst > 0 || strpos($code, 'GST') === 0) {
            $out[] = $row;
        }
    }
    return $out;
}

/**
 * GST Tax Master rows for vendor TDS dropdown (tds_rate > 0).
 */
function gst_tax_vendor_tds_options($conn)
{
    $rows = gst_tax_get_active_profiles($conn);
    $out = array();
    foreach ($rows as $row) {
        if ((float) ($row['tds_rate'] ?? 0) > 0) {
            $out[] = $row;
        }
    }
    return $out;
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

function gst_tax_normalize_gstin($gstin)
{
    $gstin = strtoupper(trim((string) $gstin));

    return preg_replace('/[^A-Z0-9]/', '', $gstin);
}

function gst_tax_is_valid_gstin($gstin)
{
    $gstin = gst_tax_normalize_gstin($gstin);
    if (strlen($gstin) !== 15) {
        return false;
    }

    return (bool) preg_match('/^[0-9]{2}[A-Z0-9]{13}$/', $gstin);
}

/**
 * GSTIN numeric state prefix (01–37) to canonical state name used in import/GST maps.
 */
function gst_tax_state_name_from_gstin_prefix($prefix)
{
    $prefix = str_pad(substr(preg_replace('/\D/', '', (string) $prefix), 0, 2), 2, '0', STR_PAD_LEFT);
    $map = array(
        '01' => 'Jammu&Kashmir',
        '02' => 'Himachal Pradesh',
        '03' => 'Punjab',
        '05' => 'Uttarakhand',
        '06' => 'Haryana',
        '07' => 'Delhi',
        '08' => 'Rajasthan',
        '09' => 'Uttar Pradesh',
        '10' => 'Bihar',
        '19' => 'West Bengal',
        '20' => 'Jharkhand',
        '21' => 'Orissa',
        '22' => 'Chattisgarh',
        '23' => 'Madhya Pradesh',
        '24' => 'Gujarat',
        '26' => 'Dadra&Nagar Haveli',
        '27' => 'Maharashtra',
        '29' => 'Karnataka',
        '32' => 'Kerala',
        '33' => 'Tamil Nadu',
        '34' => 'Puducherry',
        '36' => 'Telangana',
        '37' => 'Andhra Pradesh',
    );

    return $map[$prefix] ?? '';
}

function gst_tax_state_id_from_gstin_prefix($conn, $prefix)
{
    static $cache = array();
    $prefix = str_pad(substr(preg_replace('/\D/', '', (string) $prefix), 0, 2), 2, '0', STR_PAD_LEFT);
    if ($prefix === '00') {
        return 0;
    }
    if (isset($cache[$prefix])) {
        return $cache[$prefix];
    }

    $name = gst_tax_state_name_from_gstin_prefix($prefix);
    if ($name === '') {
        $cache[$prefix] = 0;

        return 0;
    }

    $esc = mysqli_real_escape_string($conn, $name);
    $q = mysqli_query($conn, "SELECT state_id FROM state WHERE geo_scope='domestic' AND (state_name='$esc' OR state_name LIKE '$esc%') LIMIT 1");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $cache[$prefix] = (int) ($row['state_id'] ?? 0);

        return $cache[$prefix];
    }

    $aliases = array(
        'Orissa' => array('Orissa', 'Odisha'),
        'Chattisgarh' => array('Chattisgarh', 'Chhattisgarh'),
        'Bihar' => array('Bihar', 'Bihar2'),
        'Delhi' => array('Delhi', 'New Delhi'),
    );
  foreach ($aliases as $canonical => $names) {
        if ($name !== $canonical) {
            continue;
        }
        foreach ($names as $alt) {
            $esc_alt = mysqli_real_escape_string($conn, $alt);
            $q2 = mysqli_query($conn, "SELECT state_id FROM state WHERE geo_scope='domestic' AND state_name='$esc_alt' LIMIT 1");
            if ($q2 && ($row = mysqli_fetch_assoc($q2))) {
                $cache[$prefix] = (int) ($row['state_id'] ?? 0);

                return $cache[$prefix];
            }
        }
    }

    $cache[$prefix] = 0;

    return 0;
}

function gst_tax_state_id_from_gstin($conn, $gstin)
{
    $gstin = gst_tax_normalize_gstin($gstin);
    if (strlen($gstin) < 2) {
        return 0;
    }

    return gst_tax_state_id_from_gstin_prefix($conn, substr($gstin, 0, 2));
}

/**
 * Issuing company state for tax invoices (supplier place of business).
 */
function gst_tax_supplier_state_id($conn)
{
    static $cached = 0;
    static $resolved = false;
    if ($resolved) {
        return $cached;
    }
    $resolved = true;

    $q = mysqli_query($conn, 'SELECT state, gst_no FROM company WHERE status=0 ORDER BY company_id ASC LIMIT 1');
    if (!$q || !($row = mysqli_fetch_assoc($q))) {
        return $cached;
    }

    $from_gstin = gst_tax_state_id_from_gstin($conn, $row['gst_no'] ?? '');
    $from_row = (int) ($row['state'] ?? 0);
    if ($from_gstin > 0 && $from_row > 0 && $from_gstin !== $from_row) {
        $cached = $from_gstin;
    } elseif ($from_gstin > 0) {
        $cached = $from_gstin;
    } else {
        $cached = $from_row;
    }

    return $cached;
}

function gst_tax_supplier_gstin($conn)
{
    static $gstin = null;
    if ($gstin !== null) {
        return $gstin;
    }
    $q = mysqli_query($conn, 'SELECT gst_no FROM company WHERE status=0 ORDER BY company_id ASC LIMIT 1');
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $gstin = gst_tax_normalize_gstin($row['gst_no'] ?? '');
    } else {
        $gstin = '';
    }

    return $gstin;
}

/**
 * Bill-to customer on a GCN from consignment mode (who is charged freight).
 */
function gst_tax_booking_billed_customer_id(array $post_or_row)
{
    $mode = (int) ($post_or_row['mode_of_consignment'] ?? 0);
    $consigner = (int) ($post_or_row['consigner'] ?? $post_or_row['consignor'] ?? 0);
    $consignee = (int) ($post_or_row['consignee'] ?? 0);

    if ($mode === 2) {
        return $consigner;
    }
    if ($mode === 1 || $mode === 7) {
        return $consignee;
    }

    return $consigner > 0 ? $consigner : $consignee;
}

function gst_tax_resolve_bill_to_branch_id(array $booking_row, $billed_customer_id)
{
    $branch_id = (int) ($booking_row['bill_to_branch_id'] ?? 0);
    if ($branch_id > 0) {
        return $branch_id;
    }

    $billed_customer_id = (int) $billed_customer_id;
    $consigner = (int) ($booking_row['consigner'] ?? 0);
    $consignee = (int) ($booking_row['consignee'] ?? 0);

    if ($billed_customer_id > 0 && $billed_customer_id === $consigner) {
        return (int) ($booking_row['consignor_branch_id'] ?? 0);
    }
    if ($billed_customer_id > 0 && $billed_customer_id === $consignee) {
        return (int) ($booking_row['consignee_branch_id'] ?? 0);
    }

    return 0;
}

/**
 * Normalize booking/POST fields for place-of-supply resolution.
 */
function gst_tax_booking_row_from_post(array $post)
{
    return array(
        'consigner' => (int) ($post['consignor'] ?? $post['consigner'] ?? 0),
        'consignee' => (int) ($post['consignee'] ?? 0),
        'mode_of_consignment' => (int) ($post['mode_of_consignment'] ?? 0),
        'bill_to_branch_id' => (int) ($post['bill_to_branch'] ?? $post['bill_to_branch_id'] ?? 0),
        'consignor_branch_id' => (int) ($post['consignor_branch'] ?? $post['consignor_branch_id'] ?? 0),
        'consignee_branch_id' => (int) ($post['consignee_branch'] ?? $post['consignee_branch_id'] ?? 0),
        'origin' => $post['origin'] ?? 0,
        'origin_state_id' => $post['origin_state_id'] ?? 0,
        'state' => $post['state'] ?? $post['consignor_state_id'] ?? 0,
        'gst_no' => $post['gst_no'] ?? '',
        'con_gst_no' => $post['con_gst'] ?? $post['con_gst_no'] ?? '',
        'gst_tax_id' => $post['gst_tax_id'] ?? 0,
        'gst_type' => $post['gst_type'] ?? 'auto',
    );
}

function gst_tax_resolve_type_for_freight($gst_type_input, $supplier_state_id, $pos_state_id)
{
    $gst_type_input = strtolower(trim((string) $gst_type_input));
    if ($gst_type_input === '' || $gst_type_input === 'auto') {
        return gst_tax_invoice_type_for_freight($supplier_state_id, $pos_state_id);
    }

    return $gst_type_input;
}

/**
 * Bill To GSTIN and place-of-supply state for freight tax invoice line.
 */
function gst_tax_invoice_bill_to_gstin($conn, $invoice_customer_id, array $booking_row)
{
    $invoice_customer_id = (int) $invoice_customer_id;
    $consigner_id = (int) ($booking_row['consigner'] ?? 0);
    $consignee_id = (int) ($booking_row['consignee'] ?? 0);
    $branch_id = gst_tax_resolve_bill_to_branch_id($booking_row, $invoice_customer_id);

    $gstin = '';
    $branch_state_id = 0;

    if ($invoice_customer_id > 0
        && $branch_id > 0
        && ($invoice_customer_id === $consigner_id || $invoice_customer_id === $consignee_id)) {
        $bq = mysqli_query($conn, "SELECT company_id, gst_no, state FROM client_branch WHERE client_branch_id='$branch_id' AND status=0 LIMIT 1");
        if ($bq && ($branch = mysqli_fetch_assoc($bq))) {
            if ((int) ($branch['company_id'] ?? 0) === $invoice_customer_id) {
                $gstin = gst_tax_normalize_gstin($branch['gst_no'] ?? '');
                $branch_state_id = (int) ($branch['state'] ?? 0);
            }
        }
    }

    if ($gstin === '' && $invoice_customer_id === $consigner_id) {
        $gstin = gst_tax_normalize_gstin($booking_row['gst_no'] ?? '');
        if ($branch_state_id <= 0 && !empty($booking_row['state'])) {
            $branch_state_id = (int) $booking_row['state'];
        }
    }
    if ($gstin === '' && $invoice_customer_id === $consignee_id) {
        $gstin = gst_tax_normalize_gstin($booking_row['con_gst_no'] ?? $booking_row['con_gst'] ?? '');
        if ($branch_state_id <= 0 && !empty($booking_row['con_state'])) {
            $branch_state_id = (int) $booking_row['con_state'];
        }
    }

    if ($gstin === '' && $invoice_customer_id > 0) {
        $cq = mysqli_query($conn, "SELECT gst_no, state FROM client WHERE client_id='$invoice_customer_id' LIMIT 1");
        if ($cq && ($client = mysqli_fetch_assoc($cq))) {
            $gstin = gst_tax_normalize_gstin($client['gst_no'] ?? '');
            if ($branch_state_id <= 0) {
                $branch_state_id = (int) ($client['state'] ?? 0);
            }
        }
    }

    return array(
        'gstin' => $gstin,
        'client_state_id' => $branch_state_id,
    );
}

function gst_tax_invoice_place_of_supply_context($conn, $invoice_customer_id, array $booking_row)
{
    $supplier_state_id = gst_tax_supplier_state_id($conn);
    $supplier_gstin = gst_tax_supplier_gstin($conn);

    $bill = gst_tax_invoice_bill_to_gstin($conn, $invoice_customer_id, $booking_row);
    $gstin = $bill['gstin'];
    $registered = gst_tax_is_valid_gstin($gstin);

    $origin_state_id = gst_tax_get_city_state_id($conn, $booking_row['origin'] ?? 0);
    if (!empty($booking_row['origin_state_id'])) {
        $origin_state_id = (int) $booking_row['origin_state_id'];
    }
    if ($origin_state_id <= 0 && !empty($booking_row['state'])) {
        $origin_state_id = (int) $booking_row['state'];
    }

    $pos_state_id = 0;
    $pos_source = '';

    if ($registered) {
        $pos_state_id = gst_tax_state_id_from_gstin($conn, $gstin);
        $pos_source = 'bill_to_gstin';
        if ($pos_state_id <= 0 && (int) ($bill['client_state_id'] ?? 0) > 0) {
            $pos_state_id = (int) $bill['client_state_id'];
            $pos_source = 'bill_to_state';
        }
    } else {
        $pos_state_id = $origin_state_id;
        $pos_source = 'handover_origin';
        if ($pos_state_id <= 0 && $invoice_customer_id > 0) {
            $pos_state_id = (int) ($bill['client_state_id'] ?? 0);
            $pos_source = 'customer_state';
        }
    }

    $gst_type = gst_tax_invoice_type_for_freight($supplier_state_id, $pos_state_id);

    return array(
        'supplier_state_id' => $supplier_state_id,
        'supplier_gstin' => $supplier_gstin,
        'bill_to_gstin' => $gstin,
        'client_state_id' => (int) ($bill['client_state_id'] ?? 0),
        'registered' => $registered ? 1 : 0,
        'pos_state_id' => $pos_state_id,
        'pos_source' => $pos_source,
        'origin_state_id' => $origin_state_id,
        'gst_type' => $gst_type,
    );
}

function gst_tax_invoice_type_for_freight($supplier_state_id, $pos_state_id)
{
    $supplier_state_id = (int) $supplier_state_id;
    $pos_state_id = (int) $pos_state_id;

    if ($supplier_state_id <= 0 || $pos_state_id <= 0) {
        return '';
    }

    return ($supplier_state_id === $pos_state_id) ? 'intra' : 'inter';
}

function gst_tax_invoice_gst_warnings(array $context)
{
    $warnings = array();

    if ((int) ($context['supplier_state_id'] ?? 0) <= 0) {
        $warnings[] = 'Supplier (company) state or GST registration is missing.';
    }
    if (trim((string) ($context['supplier_gstin'] ?? '')) === '') {
        $warnings[] = 'Supplier GSTIN is missing on company profile.';
    }

    $registered = !empty($context['registered']);
    if ($registered) {
        if (trim((string) ($context['bill_to_gstin'] ?? '')) === '') {
            $warnings[] = 'Bill To GSTIN is missing for a GST-registered customer.';
        } elseif ((int) ($context['pos_state_id'] ?? 0) <= 0) {
            $warnings[] = 'Could not determine place of supply from Bill To GSTIN.';
        }
    } elseif ((int) ($context['pos_state_id'] ?? 0) <= 0) {
        $warnings[] = 'Place of supply (handover / origin state) is missing for unregistered customer.';
    }

    return $warnings;
}

function gst_tax_invoice_gst_warnings_resolved($conn, array $context)
{
    $warnings = gst_tax_invoice_gst_warnings($context);

    $gstin = (string) ($context['bill_to_gstin'] ?? '');
    if (!empty($context['registered']) && gst_tax_is_valid_gstin($gstin)) {
        $from_gstin = gst_tax_state_id_from_gstin($conn, $gstin);
        $client_state = 0;
        if (!empty($context['client_state_id'])) {
            $client_state = (int) $context['client_state_id'];
        }
        if ($from_gstin > 0 && $client_state > 0 && $from_gstin !== $client_state) {
            $warnings[] = 'Bill To GSTIN state does not match the customer/branch state on file — please correct master data.';
        }
    }

    return $warnings;
}

/**
 * Errors that block finalizing a tax invoice (critical GST master data).
 */
function gst_tax_invoice_finalize_errors($conn, array $context)
{
    $errors = array();
    foreach (gst_tax_invoice_gst_warnings_resolved($conn, $context) as $w) {
        if (strpos($w, 'missing') !== false || strpos($w, 'Could not determine') !== false) {
            $errors[] = $w;
        }
    }
    if ((int) ($context['supplier_state_id'] ?? 0) <= 0) {
        $errors[] = 'Cannot finalize: supplier state is not configured.';
    }
    if (trim((string) ($context['gst_type'] ?? '')) === '') {
        $errors[] = 'Cannot finalize: interstate/intrastate GST type could not be determined.';
    }

    return $errors;
}

/**
 * Freight tax invoice line GST: supplier state vs Bill To place of supply (not booking route).
 */
function gst_tax_recalc_booking_for_freight_invoice($conn, array $booking_row, $invoice_customer_id, $taxable_value)
{
    $context = gst_tax_invoice_place_of_supply_context($conn, $invoice_customer_id, $booking_row);

    $booking_gst_type = strtolower(trim((string) ($booking_row['gst_type'] ?? '')));
    $gst_type = (string) ($context['gst_type'] ?? '');
    if (in_array($booking_gst_type, array('exempt', 'non_gst'), true)) {
        $gst_type = $booking_gst_type;
    } elseif ($gst_type === '') {
        $gst_type = 'inter';
    }

    $profile = gst_tax_fetch_profile($conn, $booking_row['gst_tax_id'] ?? 0);
    if (!$profile && !empty($booking_row['gst_tax_code'])) {
        $profile = gst_tax_fetch_profile_by_code($conn, $booking_row['gst_tax_code']);
    }

    if (in_array($gst_type, array('exempt', 'non_gst'), true)) {
        $profile = gst_tax_fetch_profile_by_code($conn, 'GST0') ?: $profile;
    }

    $breakup = gst_tax_calc_breakup($taxable_value, $profile, $gst_type);
    $breakup['gst_context'] = $context;
    $breakup['gst_warnings'] = gst_tax_invoice_gst_warnings_resolved($conn, $context);

    return $breakup;
}

function gst_tax_resolve_type($gst_type_input, $origin_state_id, $destination_state_id)
{
    $gst_type_input = strtolower(trim((string) $gst_type_input));
    if ($gst_type_input === '' || $gst_type_input === 'auto') {
        return gst_tax_determine_type_from_route($origin_state_id, $destination_state_id);
    }

    return $gst_type_input;
}

function gst_tax_round_rupee($amount)
{
    return (float) round((float) $amount, 0);
}

function gst_tax_normalize_booking_charges(array $data)
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

    foreach ($fields as $field) {
        if (!array_key_exists($field, $data)) {
            continue;
        }
        if ($data[$field] === '' || $data[$field] === null) {
            continue;
        }
        $data[$field] = gst_tax_round_rupee($data[$field]);
    }

    return $data;
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
        'loading_unload_chrg',
        'crane_forklift_chrg',
        'cod_amount',
        'fov_amount',
        'cartage_amount',
        'labour_amount',
    );

    $total = 0;
    foreach ($fields as $field) {
        $total += gst_tax_round_rupee($charges[$field] ?? 0);
    }

    return gst_tax_round_rupee($total);
}

function gst_tax_calc_breakup($taxable_value, $profile, $gst_type)
{
    $taxable = gst_tax_round_rupee($taxable_value);
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
        $result['cgst_amount'] = gst_tax_round_rupee($taxable * $result['cgst_rate'] / 100);
        $result['sgst_amount'] = gst_tax_round_rupee($taxable * $result['sgst_rate'] / 100);
        $result['cess_amount'] = gst_tax_round_rupee($taxable * $cess_rate / 100);
    } elseif ($gst_type === 'inter') {
        $result['igst_rate'] = (float) ($profile['igst_rate'] ?? 0);
        $result['igst_amount'] = gst_tax_round_rupee($taxable * $result['igst_rate'] / 100);
        $result['cess_amount'] = gst_tax_round_rupee($taxable * $cess_rate / 100);
    } else {
        return $result;
    }

    $result['gst_amount'] = gst_tax_round_rupee(
        $result['cgst_amount'] + $result['sgst_amount'] + $result['igst_amount'] + $result['cess_amount']
    );
    $result['grand_total'] = gst_tax_round_rupee($taxable + $result['gst_amount']);

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

    $billed_customer_id = gst_tax_booking_billed_customer_id($post);
    $booking_row = gst_tax_booking_row_from_post($post);
    $booking_row['bill_to_branch_id'] = gst_tax_resolve_bill_to_branch_id($booking_row, $billed_customer_id);
    $booking_row['origin_state_id'] = $origin_state_id;
    $booking_row['state'] = $booking_row['state'] ?: $origin_state_id;

    $ctx = gst_tax_invoice_place_of_supply_context($conn, $billed_customer_id, $booking_row);
    $supplier_state_id = (int) ($ctx['supplier_state_id'] ?? 0);
    if ($supplier_state_id <= 0) {
        $supplier_state_id = (int) $company_state_id;
    }

    $gst_type = gst_tax_resolve_type_for_freight(
        $post['gst_type'] ?? 'auto',
        $supplier_state_id,
        (int) ($ctx['pos_state_id'] ?? 0)
    );
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
    $breakup['bill_to_state_id'] = (int) ($ctx['pos_state_id'] ?? 0);
    $breakup['billed_customer_id'] = $billed_customer_id;
    $breakup['bill_to_gstin'] = $ctx['bill_to_gstin'] ?? '';

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
