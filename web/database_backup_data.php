<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/db_backup_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!ew_db_backup_require_admin()) {
    echo json_encode(array('status' => 1, 'message' => empty($_SESSION['user_id']) ? 'Session expired.' : 'Access denied.'));
    exit;
}

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

if ($cmd === 'list') {
    $rows = ew_db_backup_list();
    echo json_encode(array(
        'status' => 0,
        'count' => count($rows),
        'retention_days' => EW_DB_BACKUP_RETENTION_DAYS,
        'data' => $rows,
    ));
    exit;
}

if ($cmd === 'run') {
    $force = !empty($_REQUEST['force']);
    $result = ew_db_backup_run($force);
    echo json_encode(array(
        'status' => $result['ok'] ? 0 : 1,
        'message' => $result['message'],
        'file' => $result['file'] ?? null,
        'size_label' => isset($result['size']) ? ew_db_backup_format_size($result['size']) : null,
    ));
    exit;
}

echo json_encode(array('status' => 1, 'message' => 'Unknown command.'));
