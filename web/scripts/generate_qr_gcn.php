<?php
/**
 * Generate per-package QR PNGs (same rules as save_details.php booking flow).
 * CLI: php scripts/generate_qr_gcn.php EW360/0201 [EW360/0202 ...]
 *      php scripts/generate_qr_gcn.php --range 201 207
 */
chdir(dirname(__DIR__));
require_once __DIR__ . '/../include/connect.php';
include __DIR__ . '/../libs/phpqrcode/qrlib.php';

function qr_package_code($type_id)
{
    switch ((string) $type_id) {
        case '1': return 'CBX';
        case '2': return 'PBG';
        case '3': return 'ROL';
        case '5': return 'SHT';
        case '6': return 'BDL';
        case '7': return 'CVR';
        case '8': return 'PBL';
        case '9': return 'CAN';
        case '10': return 'BOX';
        case '11': return 'BAG';
        case '12': return 'MLD';
        case '13': return 'PKT';
        case '14': return 'CES';
        case '15': return 'CAT';
        case '16': return 'GRL';
        case '17': return 'P.B';
        case '18': return 'PRL';
        default: return 'BOX';
    }
}

function generate_qr_for_booking($grn_no, $grn_date, array $lines)
{
    $tempDir = 'qrcode/';
    $productData = strtoupper($grn_no);
    $qrParent = dirname($tempDir . $productData . 'x.png');
    if (!is_dir($qrParent)) {
        mkdir($qrParent, 0755, true);
    }

    $totals = array();
    foreach ($lines as $line) {
        $type = (string) $line['type_of_pkge'];
        $qty = (int) $line['no_of_pkge'];
        if ($qty <= 0) {
            continue;
        }
        if (!isset($totals[$type])) {
            $totals[$type] = 0;
        }
        $totals[$type] += $qty;
    }

    $created = 0;
    foreach ($totals as $type_id => $get_qty) {
        $pack_name = qr_package_code($type_id);
        for ($i = 0; $i < $get_qty; $i++) {
            $idx = $i + 1;
            $names = $productData . $pack_name . '-00' . $idx;
            $contents = 'https://elitewave360.in/web/testqrcode.php?grn_no=' . $grn_no . '&grn_date=' . $grn_date;
            $path = $tempDir . $names . '.png';
            QRcode::png($contents, $path, QR_ECLEVEL_L, 5);
            $created++;
        }
    }
    return $created;
}

function gcn_suffix($grn_no)
{
    if (preg_match('/\/(\d+)\s*$/', $grn_no, $m)) {
        return $m[1];
    }
    return '';
}

function remove_existing_qr_for_gcn($grn_no)
{
    $suffix = gcn_suffix($grn_no);
    if ($suffix === '') {
        return 0;
    }
    $dir = 'qrcode/EW360/';
    if (!is_dir($dir)) {
        return 0;
    }
    $removed = 0;
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (preg_match('/^' . preg_quote($suffix, '/') . '[A-Z.\d-]+.*\.png$/i', $file)) {
            unlink($dir . $file);
            $removed++;
        }
    }
    return $removed;
}

function load_booking_lines($conn, $grn_no)
{
    $esc = mysqli_real_escape_string($conn, $grn_no);
    $tables = array('transaction_3_2026', 'transaction_2_2026', 'transaction_1_2026', 'transaction_4_2026');
    foreach ($tables as $t) {
        $chk = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            continue;
        }
        $q = "SELECT t.grn_no, t.grn_date, i.no_of_pkge, i.type_of_pkge
            FROM `$t` t
            INNER JOIN `" . str_replace('transaction_', 'transaction_invoice_', $t) . "` i ON i.transaction_id = t.transaction_id
            WHERE t.grn_no = '$esc'";
        $r = mysqli_query($conn, $q);
        if (!$r || mysqli_num_rows($r) === 0) {
            continue;
        }
        $lines = array();
        $grn_date = '';
        while ($row = mysqli_fetch_assoc($r)) {
            $grn_date = $row['grn_date'];
            $lines[] = $row;
        }
        return array($grn_no, $grn_date, $lines);
    }
    return null;
}

$grns = array();
if (isset($argv[1]) && $argv[1] === '--range' && isset($argv[2], $argv[3])) {
    for ($n = (int) $argv[2]; $n <= (int) $argv[3]; $n++) {
        $grns[] = sprintf('EW360/%04d', $n);
    }
} else {
    for ($i = 1; $i < $argc; $i++) {
        if ($argv[$i] !== '--range') {
            $grns[] = $argv[$i];
        }
    }
}

if (!$grns) {
    $grns = array();
    for ($n = 201; $n <= 207; $n++) {
        $grns[] = sprintf('EW360/%04d', $n);
    }
}

foreach ($grns as $grn_no) {
    $loaded = load_booking_lines($conn, $grn_no);
    if (!$loaded) {
        echo "$grn_no: not found\n";
        continue;
    }
    list(, $grn_date, $lines) = $loaded;
    $removed = remove_existing_qr_for_gcn($grn_no);
    $created = generate_qr_for_booking($grn_no, $grn_date, $lines);
    echo "$grn_no: removed $removed old PNG(s), created $created QR label(s)\n";
}
