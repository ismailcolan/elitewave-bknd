<?php
/**
 * Export city master to xlsx (no ID columns).
 * CLI: php automation/export_city_master_cli.php
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$root = dirname(__DIR__);
require_once $root . '/include/connect.php';
require_once $root . '/include/city_export_helpers.php';

date_default_timezone_set('Asia/Kolkata');
$dir = $root . '/include';
$path = $dir . '/city_master_backup_' . date('Y-m-d_His') . '.xlsx';

try {
    $result = ew_city_master_export_xlsx($conn, $path);
    echo 'Exported ' . $result['count'] . " row(s) to {$path}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Export failed: ' . $e->getMessage() . "\n");
    exit(1);
}
