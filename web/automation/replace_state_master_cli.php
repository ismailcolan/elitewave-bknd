<?php
/**
 * Export state master to xlsx, then optionally replace rows with a fixed snapshot.
 * CLI: php automation/replace_state_master_cli.php [--apply]
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$apply = in_array('--apply', $argv ?? array(), true);
$root = dirname(__DIR__);
require_once $root . '/include/connect.php';
require_once $root . '/PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** @var array<int, array{int,string,string,int,string|null,int|null,int}> */
$newRows = array(
    array(1, 'Andhra Pradesh', '17-12-2018', 1, '15-07-2026', 118, 0),
    array(2, 'Assam', '17-12-2018', 1, null, null, 0),
    array(3, 'Bihar2', '17-12-2018', 1, '11-09-2026', 118, 0),
    array(4, 'Chattisgarh', '17-12-2018', 1, null, null, 0),
    array(5, 'Dadra&Nagar Haveli', '17-12-2018', 1, null, null, 0),
    array(6, 'Gujarat', '17-12-2018', 1, null, null, 0),
    array(7, 'Haryana', '17-12-2018', 1, null, null, 0),
    array(8, 'Himachal Pradesh', '17-12-2018', 1, null, null, 0),
    array(9, 'Jammu&Kashmir', '17-12-2018', 1, null, null, 0),
    array(10, 'Jharkhand', '17-12-2018', 1, '17-07-2026', 118, 0),
    array(11, 'Karnataka', '17-12-2018', 1, null, null, 0),
    array(12, 'Kerala', '17-12-2018', 1, null, null, 0),
    array(13, 'Madhya Pradesh', '17-12-2018', 1, null, null, 0),
    array(14, 'Maharashtra', '17-12-2018', 1, null, null, 0),
    array(15, 'New Delhi', '17-12-2018', 1, null, null, 0),
    array(16, 'Orissa', '17-12-2018', 1, null, null, 0),
    array(17, 'Puducherry', '17-12-2018', 1, null, null, 0),
    array(18, 'Punjab', '17-12-2018', 1, null, null, 0),
    array(19, 'Rajasthan', '17-12-2018', 1, null, null, 0),
    array(20, 'Tamil Nadu', '17-12-2018', 1, null, null, 0),
    array(21, 'Telangana', '17-12-2018', 1, null, null, 0),
    array(22, 'Uttar Pradesh', '17-12-2018', 1, null, null, 0),
    array(23, 'Uttarakhand', '17-12-2018', 1, null, null, 0),
    array(24, 'West Bengal', '17-12-2018', 1, null, null, 0),
    array(25, 'Punjab', '14-01-2019', 7, null, null, 0),
    array(26, 'maharastra', '18-01-2019', 7, null, null, 0),
    array(27, 'miraj', '18-01-2019', 7, null, null, 0),
    array(28, 'miraj', '18-01-2019', 7, null, null, 0),
    array(29, 'karnatka', '24-01-2019', 17, null, null, 0),
    array(30, 'Thiruvanmayur', '15-03-2019', 24, null, null, 0),
    array(32, 'Sulurpet', '18-10-2024', 3, null, null, 0),
);

function export_state_xlsx($conn, $path)
{
    $res = mysqli_query($conn, 'SELECT state_id, state_name, created_at, created_by, updated_at, updated_by, status FROM state ORDER BY state_id');
    if (!$res) {
        throw new RuntimeException(mysqli_error($conn));
    }

    $sheet = new Spreadsheet();
    $ws = $sheet->getActiveSheet();
    $ws->setTitle('state');
    $headers = array('state_id', 'state_name', 'created_at', 'created_by', 'updated_at', 'updated_by', 'status');
    foreach ($headers as $i => $h) {
        $ws->setCellValueByColumnAndRow($i + 1, 1, $h);
    }
    $rowNum = 2;
    while ($row = mysqli_fetch_assoc($res)) {
        $col = 1;
        foreach ($headers as $h) {
            $val = $row[$h];
            $ws->setCellValueByColumnAndRow($col, $rowNum, $val === null ? '' : $val);
            $col++;
        }
        $rowNum++;
    }

    $writer = new Xlsx($sheet);
    $writer->save($path);
    return $rowNum - 2;
}

function sql_null_or_str($conn, $val)
{
    if ($val === null || $val === '') {
        return 'NULL';
    }
    return "'" . mysqli_real_escape_string($conn, (string) $val) . "'";
}

date_default_timezone_set('Asia/Kolkata');
$backupDir = $root . '/include';
if (!is_dir($backupDir)) {
    fwrite(STDERR, "Missing include dir\n");
    exit(1);
}

$stamp = date('Y-m-d_His');
$backupPath = $backupDir . '/state_backup_before_replace_' . $stamp . '.xlsx';

try {
    $count = export_state_xlsx($conn, $backupPath);
    echo "Exported {$count} row(s) to {$backupPath}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Export failed: ' . $e->getMessage() . "\n");
    exit(1);
}

if (!$apply) {
    echo "Dry run only. Re-run with --apply to DELETE all state rows and insert " . count($newRows) . " rows.\n";
    exit(0);
}

mysqli_begin_transaction($conn);
try {
    if (!mysqli_query($conn, 'DELETE FROM state')) {
        throw new RuntimeException(mysqli_error($conn));
    }

    $inserted = 0;
    foreach ($newRows as $r) {
        list($id, $name, $createdAt, $createdBy, $updatedAt, $updatedBy, $status) = $r;
        $sql = 'INSERT INTO state (state_id, state_name, created_at, created_by, updated_at, updated_by, status) VALUES ('
            . (int) $id . ', '
            . sql_null_or_str($conn, $name) . ', '
            . sql_null_or_str($conn, $createdAt) . ', '
            . (int) $createdBy . ', '
            . sql_null_or_str($conn, $updatedAt) . ', '
            . ($updatedBy === null ? 'NULL' : (int) $updatedBy) . ', '
            . (int) $status . ')';
        if (!mysqli_query($conn, $sql)) {
            throw new RuntimeException(mysqli_error($conn));
        }
        $inserted++;
    }

    $maxId = 0;
    foreach ($newRows as $r) {
        $maxId = max($maxId, (int) $r[0]);
    }
    if (!mysqli_query($conn, 'ALTER TABLE state AUTO_INCREMENT = ' . ($maxId + 1))) {
        throw new RuntimeException(mysqli_error($conn));
    }

    mysqli_commit($conn);
    echo "Replaced state table: {$inserted} row(s). AUTO_INCREMENT=" . ($maxId + 1) . "\n";
} catch (Throwable $e) {
    mysqli_rollback($conn);
    fwrite(STDERR, 'Apply failed: ' . $e->getMessage() . "\n");
    exit(1);
}
