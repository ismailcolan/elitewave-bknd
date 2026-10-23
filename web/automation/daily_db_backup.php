<?php
/**
 * Daily MySQL backup → ZIP in web/storage/db_backups (keeps last 15 days).
 *
 * Cron — 11:30 AM IST (this host uses UTC; 06:00 UTC = 11:30 IST):
 *   0 6 * * * /usr/bin/php .../automation/daily_db_backup.php >> .../storage/db_backups/backup.log 2>&1
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

ini_set('max_execution_time', '0');
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../include/db_backup_helpers.php';

$result = ew_db_backup_run(false);
if (!$result['ok']) {
    fwrite(STDERR, date('c') . ' ' . $result['message'] . "\n");
    exit(1);
}

$sizeMb = isset($result['size']) ? round($result['size'] / 1048576, 2) : 0;
echo date('c') . ' ' . $result['message'] . ' ' . ($result['file'] ?? '') . " ({$sizeMb} MB).\n";

if (($result['message'] ?? '') === 'Backup completed.') {
    $zipPath = $result['path'] ?? '';
    if ($zipPath === '' && !empty($result['file'])) {
        $zipPath = ew_db_backup_dir() . '/' . $result['file'];
    }
    $mail = ew_db_backup_email_zip($zipPath);
    echo date('c') . ' ' . $mail['message'] . "\n";
    if (empty($mail['ok'])) {
        exit(1);
    }
}

exit(0);
