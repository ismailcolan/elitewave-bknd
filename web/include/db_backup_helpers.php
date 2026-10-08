<?php

require_once __DIR__ . '/database_config.php';

const EW_DB_BACKUP_RETENTION_DAYS = 15;

function ew_db_backup_dir()
{
    $dir = realpath(__DIR__ . '/../storage/db_backups');
    return ($dir && is_dir($dir)) ? $dir : null;
}

function ew_db_backup_format_size($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return round($bytes / 1048576, 2) . ' MB';
}

function ew_db_backup_display_date($ymd)
{
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    return $dt ? $dt->format('d-m-Y') : $ymd;
}

/** Run date (backup job day) → data coverage day (previous calendar day). */
function ew_db_backup_coverage_ymd($runYmd)
{
    $dt = DateTime::createFromFormat('Y-m-d', $runYmd);
    if (!$dt) {
        return $runYmd;
    }
    $dt->modify('-1 day');
    return $dt->format('Y-m-d');
}

/**
 * @return array<int, array{date:string,run_date:string,coverage_date:string,label:string,description:string,size:int,size_label:string,from:string,to:string,is_today:bool,is_latest:bool,file:string}>
 */
function ew_db_backup_list()
{
    $dir = ew_db_backup_dir();
    if (!$dir) {
        return array();
    }

    date_default_timezone_set('Asia/Kolkata');
    $pattern = $dir . '/' . EW_DB_NAME . '_*.zip';
    $files = glob($pattern);
    if (!$files) {
        return array();
    }

    $rows = array();
    foreach ($files as $path) {
        $base = basename($path);
        if (!preg_match('/^' . preg_quote(EW_DB_NAME, '/') . '_(\d{4}-\d{2}-\d{2})\.zip$/', $base, $m)) {
            continue;
        }
        $runDate = $m[1];
        $coverageYmd = ew_db_backup_coverage_ymd($runDate);
        $size = (int) @filesize($path);
        $rows[] = array(
            'date' => $runDate,
            'run_date' => $runDate,
            'coverage_date' => $coverageYmd,
            'label' => ew_db_backup_display_date($coverageYmd),
            'description' => 'Database Backup',
            'size' => $size,
            'size_label' => ew_db_backup_format_size($size),
            'from' => ew_db_backup_display_date($coverageYmd),
            'to' => ew_db_backup_display_date($runDate),
            'is_today' => false,
            'is_latest' => false,
            'file' => $base,
        );
    }

    usort($rows, function ($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    if (!empty($rows)) {
        $rows[0]['is_latest'] = true;
        $rows[0]['is_today'] = true;
    }

    return $rows;
}

function ew_db_backup_prune_old($dir = null, $keepDays = EW_DB_BACKUP_RETENTION_DAYS)
{
    if ($dir === null) {
        $dir = ew_db_backup_dir();
    }
    if (!$dir) {
        return;
    }

    $pattern = $dir . '/' . EW_DB_NAME . '_*.zip';
    $files = glob($pattern);
    if (!$files) {
        return;
    }

    $dated = array();
    foreach ($files as $path) {
        $base = basename($path);
        if (!preg_match('/^' . preg_quote(EW_DB_NAME, '/') . '_(\d{4}-\d{2}-\d{2})\.zip$/', $base, $m)) {
            continue;
        }
        $dated[] = array('date' => $m[1], 'path' => $path);
    }

    usort($dated, function ($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    $remove = array_slice($dated, (int) $keepDays);
    foreach ($remove as $item) {
        @unlink($item['path']);
    }
}

/**
 * @return array{ok:bool,message:string,size?:int,file?:string}
 */
function ew_db_backup_run($force = false)
{
    $backupDir = ew_db_backup_dir();
    if (!$backupDir) {
        return array('ok' => false, 'message' => 'Backup folder is not available.');
    }

    date_default_timezone_set('Asia/Kolkata');
    $today = date('Y-m-d');
    $sqlBase = EW_DB_NAME . '_' . $today;
    $sqlFile = $backupDir . '/' . $sqlBase . '.sql';
    $zipFile = $backupDir . '/' . $sqlBase . '.zip';

    if (!$force && is_file($zipFile)) {
        ew_db_backup_prune_old($backupDir);
        return array(
            'ok' => true,
            'message' => 'Backup for today already exists.',
            'file' => basename($zipFile),
            'size' => (int) filesize($zipFile),
        );
    }

    $cnfFile = tempnam($backupDir, 'mysqldump_');
    if ($cnfFile === false) {
        return array('ok' => false, 'message' => 'Could not start backup (temp file).');
    }

    $cnfBody = "[client]\n"
        . 'user=' . EW_DB_USER . "\n"
        . 'password=' . EW_DB_PASS . "\n"
        . 'host=' . EW_DB_HOST . "\n";
    file_put_contents($cnfFile, $cnfBody);
    chmod($cnfFile, 0600);

    $dumpCmd = sprintf(
        'mysqldump --defaults-extra-file=%s --single-transaction --routines --triggers --events --default-character-set=utf8mb4 %s > %s 2>&1',
        escapeshellarg($cnfFile),
        escapeshellarg(EW_DB_NAME),
        escapeshellarg($sqlFile)
    );

    exec($dumpCmd, $dumpOut, $dumpCode);
    @unlink($cnfFile);

    if ($dumpCode !== 0 || !is_file($sqlFile) || filesize($sqlFile) < 64) {
        @unlink($sqlFile);
        $detail = !empty($dumpOut) ? implode(' ', $dumpOut) : 'mysqldump failed.';
        return array('ok' => false, 'message' => $detail);
    }

    if ($force && is_file($zipFile)) {
        @unlink($zipFile);
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        @unlink($sqlFile);
        return array('ok' => false, 'message' => 'Could not create ZIP file.');
    }
    $zip->addFile($sqlFile, basename($sqlFile));
    $zip->close();
    @unlink($sqlFile);

    ew_db_backup_prune_old($backupDir);

    return array(
        'ok' => true,
        'message' => 'Backup completed.',
        'file' => basename($zipFile),
        'size' => (int) filesize($zipFile),
    );
}

/**
 * Resolve a daily backup path from YYYY-MM-DD (must exist under backup dir).
 */
function ew_db_backup_path_for_date($dateYmd)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
        return null;
    }
    $dir = ew_db_backup_dir();
    if (!$dir) {
        return null;
    }
    $path = $dir . '/' . EW_DB_NAME . '_' . $dateYmd . '.zip';
    $real = realpath($path);
    if ($real === false || strpos($real, $dir) !== 0) {
        return null;
    }
    return $real;
}

function ew_db_backup_require_admin()
{
    if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'AD')) {
        return false;
    }
    return true;
}
