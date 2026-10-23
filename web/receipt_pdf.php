<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_receipt_pdf_builder.php');

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Session expired.';
    exit;
}

$billing_receipt_id = (int) ($_GET['id'] ?? $_GET['billing_receipt_id'] ?? 0);
$download = !empty($_GET['download']);

$view = billing_receipt_fetch_view($conn, $billing_receipt_id);
if (empty($view['ok'])) {
    header('HTTP/1.1 404 Not Found');
    echo htmlspecialchars($view['message'] ?? 'Receipt not found.');
    exit;
}
unset($view['ok']);

require_once __DIR__ . '/vendor/autoload.php';

$html = billing_receipt_build_pdf_html($conn, $view);
if ($html === '') {
    die('Could not build receipt PDF.');
}

$mpdf = new \Mpdf\Mpdf(array(
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'freesans',
    'margin_left' => 8,
    'margin_right' => 8,
    'margin_top' => 8,
    'margin_bottom' => 12,
));

$receipt_no = (string) ($view['receipt_no'] ?? '');
$mpdf->SetTitle('Receipt — ' . $receipt_no);
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);

$filename = 'Receipt_' . preg_replace('/[^a-zA-Z0-9_\-\/]/', '_', $receipt_no) . '.pdf';
$mpdf->Output($filename, $download ? 'D' : 'I');
