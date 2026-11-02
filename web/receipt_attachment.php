<?php
require_once('include/connect.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$inline = !empty($_GET['inline']);
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
$safe_name = str_replace(array('"', "\r", "\n"), '', $name);
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
$mime_map = array(
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'pdf' => 'application/pdf',
);
if (isset($mime_map[$ext])) {
    $mime = $mime_map[$ext];
}
$can_inline = $inline && isset($mime_map[$ext]);
header('Content-Type: ' . ($can_inline ? $mime : 'application/octet-stream'));
header('Content-Disposition: ' . ($can_inline ? 'inline' : 'attachment') . '; filename="' . $safe_name . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
