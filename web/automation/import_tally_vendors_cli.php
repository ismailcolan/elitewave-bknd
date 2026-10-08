<?php
/**
 * One-time import: Tally Sundry Creditors xlsx → vendor_master (skip duplicates).
 * CLI: php automation/import_tally_vendors_cli.php [--dry-run] [--fill-missing]
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$dry_run = in_array('--dry-run', $argv ?? array(), true);
$fill_missing = in_array('--fill-missing', $argv ?? array(), true);
$root = dirname(__DIR__);
require_once $root . '/include/connect.php';
require_once $root . '/include/vendor_master_helpers.php';

ew_vendor_ensure_table($conn);

$xlsx = $root . '/include/Tally (1).xlsx';
if (!is_readable($xlsx)) {
    fwrite(STDERR, "Missing xlsx: $xlsx\n");
    exit(1);
}

function tally_norm_name($s)
{
    $s = strtoupper(trim((string) $s));
    $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function tally_norm_gstin($s)
{
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $s));
}

function tally_name_stopwords()
{
    return array(
        'MOHAMMED', 'MD', 'MR', 'MRS', 'DR', 'SALARY', 'PETTY', 'EXPENSES', 'EXPENSE', 'ACCOUNT', 'ACC',
        'CHENNAI', 'DELHI', 'MUMBAI', 'TN', 'DL', 'UP', 'RJ', 'GJ', 'KA', 'MH', 'PB', 'HR', 'WB', 'MP',
        'A', 'C', 'AC', 'THE', 'AND', 'OF', 'FOR', 'PVT', 'LTD', 'LIMITED', 'PRIVATE', 'INDIA', 'CO',
        'COMPANY', 'SERVICES', 'SERVICE', 'LOGISTICS', 'CARGO', 'COURIER', 'INDIA', 'INDIAN',
    );
}

function tally_distinctive_tokens($name)
{
    $parts = explode(' ', tally_norm_name($name));
    $stop = array_flip(tally_name_stopwords());
    $tokens = array();
    foreach ($parts as $p) {
        if ($p === '' || strlen($p) < 2) {
            continue;
        }
        if (isset($stop[$p])) {
            continue;
        }
        $tokens[] = $p;
    }
    return array_values(array_unique($tokens));
}

function tally_names_similar($a, $b)
{
    $a = tally_norm_name($a);
    $b = tally_norm_name($b);
    if ($a === '' || $b === '') {
        return false;
    }
    if ($a === $b) {
        return true;
    }
    $short = (strlen($a) <= strlen($b)) ? $a : $b;
    $long = (strlen($a) > strlen($b)) ? $a : $b;
    if (strlen($short) >= 8 && strpos($long, $short) !== false) {
        return true;
    }

    $ta = tally_distinctive_tokens($a);
    $tb = tally_distinctive_tokens($b);
    if (!$ta || !$tb) {
        return false;
    }
    $inter = array_intersect($ta, $tb);
    if (count($inter) < 2) {
        return false;
    }
    $has_strong = false;
    foreach ($inter as $tok) {
        if (strlen($tok) >= 4) {
            $has_strong = true;
            break;
        }
    }
    if (!$has_strong) {
        return false;
    }
    return (count($inter) / max(count($ta), count($tb))) >= 0.5;
}

function tally_parse_xlsx($path)
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return array();
    }
    $shared = array();
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss !== false) {
        $xml = simplexml_load_string($ss);
        if ($xml) {
            $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($xml->xpath('//m:si') as $si) {
                $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $parts = $si->xpath('.//m:t');
                $text = '';
                foreach ($parts as $p) {
                    $text .= (string) $p;
                }
                $shared[] = $text;
            }
        }
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        return array();
    }
    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet) {
        return array();
    }
    $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $out = array();
    foreach ($sheet->xpath('//m:sheetData/m:row') as $row) {
        $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $cells = array();
        foreach ($row->xpath('m:c') as $c) {
            $t = (string) $c['t'];
            $v = isset($c->v) ? (string) $c->v : '';
            if ($v === '') {
                $cells[] = '';
            } elseif ($t === 's') {
                $cells[] = $shared[(int) $v] ?? '';
            } else {
                $cells[] = $v;
            }
        }
        if (!isset($cells[0]) || !ctype_digit(trim($cells[0]))) {
            continue;
        }
        $name = trim($cells[1] ?? '');
        if ($name === '') {
            continue;
        }
        $state_raw = trim($cells[3] ?? '');
        if (stripos($state_raw, 'Not Applicable') !== false) {
            continue;
        }
        $out[] = array(
            'name' => $name,
            'address' => trim($cells[2] ?? ''),
            'state_label' => $state_raw,
            'reg_type' => trim($cells[5] ?? ''),
            'gstin' => tally_norm_gstin($cells[6] ?? ''),
            'pan' => strtoupper(trim($cells[7] ?? '')),
        );
    }
    return $out;
}

function tally_load_state_city_maps($conn)
{
    $state_map = array();
    $q = mysqli_query($conn, 'SELECT state_id, state_name FROM state');
    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $key = strtolower(trim($r['state_name']));
        $state_map[$key] = (int) $r['state_id'];
    }
    $aliases = array(
        'delhi' => 'new delhi',
        'new delhi ncr' => 'new delhi',
        'punjab' => 'punjab',
        'punb' => 'punjab',
        'tamil nadu' => 'tamil nadu',
        'uttar pradesh' => 'uttar pradesh',
        'maharashtra' => 'maharashtra',
        'karnataka' => 'karnataka',
        'karnatka' => 'karnataka',
    );
    foreach ($aliases as $from => $to) {
        if (isset($state_map[$to]) && !isset($state_map[$from])) {
            $state_map[$from] = $state_map[$to];
        }
    }

    $city_map = array();
    $cq = mysqli_query($conn, 'SELECT state, MIN(city_id) AS city_id FROM city GROUP BY state');
    while ($cq && ($cr = mysqli_fetch_assoc($cq))) {
        $city_map[(int) $cr['state']] = (int) $cr['city_id'];
    }
    $city_map[20] = 79;
    $city_map[15] = 63;
    $city_map[14] = 58;
    $city_map[11] = 125;
    $city_map[22] = 63;

    return array($state_map, $city_map);
}

function tally_resolve_state_id($label, $state_map)
{
    $label = strtolower(trim(preg_replace('/[^a-z ]/i', ' ', $label)));
    $label = preg_replace('/\s+/', ' ', $label);
    if ($label === '') {
        return 20;
    }
    if (isset($state_map[$label])) {
        return $state_map[$label];
    }
    foreach ($state_map as $k => $id) {
        if ($k !== '' && strpos($label, $k) !== false) {
            return $id;
        }
    }
    return 20;
}

function tally_vendor_exists($row, $existing, $allow_fuzzy = true)
{
    $nn = tally_norm_name($row['name']);
    if ($nn !== '' && isset($existing['names'][$nn])) {
        return 'name';
    }
    if ($row['gstin'] !== '' && isset($existing['gstins'][$row['gstin']])) {
        return 'gstin';
    }
    if ($row['pan'] !== '' && isset($existing['pans'][$row['pan']])) {
        return 'pan';
    }
    if (!$allow_fuzzy) {
        return '';
    }
    foreach ($existing['rows'] as $ex) {
        if (tally_names_similar($row['name'], $ex['vendor_name'])) {
            return 'similar:' . $ex['vendor_name'];
        }
    }
    return '';
}

$rows = tally_parse_xlsx($xlsx);
list($state_map, $city_map) = tally_load_state_city_maps($conn);

$existing = array('names' => array(), 'gstins' => array(), 'pans' => array(), 'rows' => array());
$eq = mysqli_query($conn, 'SELECT vendor_id, vendor_name, gstin, pan_no FROM vendor_master');
while ($eq && ($er = mysqli_fetch_assoc($eq))) {
    $existing['rows'][] = $er;
    $nn = tally_norm_name($er['vendor_name']);
    if ($nn !== '') {
        $existing['names'][$nn] = true;
    }
    $g = tally_norm_gstin($er['gstin'] ?? '');
    if ($g !== '') {
        $existing['gstins'][$g] = true;
    }
    $p = strtoupper(trim($er['pan_no'] ?? ''));
    if ($p !== '') {
        $existing['pans'][$p] = true;
    }
}

$created_by = 0;
$created_at = date('d-m-Y');
$inserted = 0;
$skipped = 0;
$seen_import = array('names' => array(), 'gstins' => array());

foreach ($rows as $row) {
    $dup = tally_vendor_exists($row, $existing, !$fill_missing);
    if ($dup === '') {
        $nn = tally_norm_name($row['name']);
        if (isset($seen_import['names'][$nn])) {
            $dup = 'duplicate-in-file';
        } elseif ($row['gstin'] !== '' && isset($seen_import['gstins'][$row['gstin']])) {
            $dup = 'duplicate-gstin-in-file';
        }
    }
    if ($dup !== '') {
        $skipped++;
        continue;
    }

    $state_id = tally_resolve_state_id($row['state_label'], $state_map);
    $city_id = $city_map[$state_id] ?? 79;
    $address1 = $row['address'] !== '' ? $row['address'] : ($row['state_label'] !== '' ? $row['state_label'] : 'As per Tally');
    if (strlen($address1) > 255) {
        $address1 = substr($address1, 0, 252) . '...';
    }
    $contact_person = $row['name'];
    if (strlen($contact_person) > 100) {
        $contact_person = substr($contact_person, 0, 100);
    }
    $vendor_name = mysqli_real_escape_string($conn, $row['name']);
    $vendor_type = 'OTHER';
    $gst_registered = (stripos($row['reg_type'], 'Regular') !== false && $row['gstin'] !== '') ? 1 : 0;
    $gstin = mysqli_real_escape_string($conn, $row['gstin']);
    $pan = $row['pan'];
    if ($pan !== '' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
        $pan = '';
    }
    $pan_esc = mysqli_real_escape_string($conn, $pan);

    if ($dry_run) {
        $inserted++;
        $nn = tally_norm_name($row['name']);
        $seen_import['names'][$nn] = true;
        if ($row['gstin'] !== '') {
            $seen_import['gstins'][$row['gstin']] = true;
        }
        continue;
    }

    $next = ew_vendor_next_code($conn);
    $vendor_code = mysqli_real_escape_string($conn, $next['vendor_code']);
    $vendor_code_id = (int) $next['vendor_code_id'];
    $esc_type = mysqli_real_escape_string($conn, $vendor_type);
    $esc_cp = mysqli_real_escape_string($conn, $contact_person);
    $esc_a1 = mysqli_real_escape_string($conn, $address1);
    $contact_no = '0000000000';

    $sql = "INSERT INTO vendor_master (
        vendor_code, vendor_code_id, vendor_name, vendor_type, contact_person, contact_designation,
        address1, address2, state, city, pincode, email, email_alt, website,
        contact_no, contact_no2, gst_registered, gstin, gst_exemption, tds_applicable, tds_rate, pan_no,
        mode_of_transport, service_type, operating_from, operating_to, payment_terms, credit_days,
        account_holder_name, bank_name, account_number, ifsc, bank_branch,
        status, created_at, created_by
    ) VALUES (
        '$vendor_code', '$vendor_code_id', '$vendor_name', '$esc_type', '$esc_cp', '',
        '$esc_a1', '', '$state_id', '$city_id', '', '', '', '',
        '$contact_no', '', '$gst_registered', '$gstin', 0, 0, NULL, '$pan_esc',
        '', '', '', '', '', NULL,
        '', '', '', '', '',
        0, '$created_at', '$created_by'
    )";
    if (!mysqli_query($conn, $sql)) {
        fwrite(STDERR, 'Insert failed: ' . mysqli_error($conn) . ' — ' . $row['name'] . "\n");
        continue;
    }
    $inserted++;

    $existing['rows'][] = array('vendor_name' => $row['name'], 'gstin' => $row['gstin'], 'pan_no' => $pan);
    $nn = tally_norm_name($row['name']);
    $existing['names'][$nn] = true;
    if ($row['gstin'] !== '') {
        $existing['gstins'][$row['gstin']] = true;
    }
    if ($pan !== '') {
        $existing['pans'][$pan] = true;
    }
    $seen_import['names'][$nn] = true;
    if ($row['gstin'] !== '') {
        $seen_import['gstins'][$row['gstin']] = true;
    }
}

echo ($dry_run ? '[DRY RUN] ' : '') . ($fill_missing ? '[FILL MISSING] ' : '') . "Tally rows parsed: " . count($rows) . "\n";
echo "Inserted: $inserted\n";
echo "Skipped (duplicate / invalid): $skipped\n";
