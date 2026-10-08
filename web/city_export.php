<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/city_export_helpers.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Session expired.');
}

try {
    $result = ew_city_master_export_xlsx($conn, null);
    $path = $result['path'];
    $filename = ew_city_master_export_filename();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-store');

    readfile($path);
    @unlink($path);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Export failed.');
}
