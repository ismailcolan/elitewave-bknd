<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/db_backup_helpers.php';

if (!ew_db_backup_require_admin()) {
    http_response_code(403);
    exit('Access denied');
}

$all = !empty($_GET['all']);
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($all) {
    $rows = ew_db_backup_list();
    if (!$rows) {
        http_response_code(404);
        exit('No backups found.');
    }

    $dir = ew_db_backup_dir();
    $bundleName = EW_DB_NAME . '_all_' . date('Y-m-d') . '.zip';
    $tmp = tempnam(sys_get_temp_dir(), 'ew_db_bundle_');
    if ($tmp === false) {
        http_response_code(500);
        exit('Could not create archive.');
    }
    @unlink($tmp);

    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE) !== true) {
        http_response_code(500);
        exit('Could not create archive.');
    }
    foreach ($rows as $row) {
        $path = $dir . '/' . $row['file'];
        if (is_file($path)) {
            $zip->addFile($path, $row['file']);
        }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $bundleName . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

$path = ew_db_backup_path_for_date($date);
if (!$path || !is_file($path)) {
    http_response_code(404);
    exit('Backup not found.');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
