<?php
require_once('include/connect.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('HTTP/1.0 404 Not Found');
    exit;
}
$q = mysqli_query($conn, "SELECT file_path, original_name FROM billing_receipt_attachments WHERE attachment_id='$id' LIMIT 1");
if (!$q || !($row = mysqli_fetch_assoc($q))) {
    header('HTTP/1.0 404 Not Found');
    exit;
}
$path = dirname(__FILE__) . '/' . $row['file_path'];
if (!is_file($path)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}
$name = $row['original_name'] ?: basename($path);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $name) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
