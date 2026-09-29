<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/quotation_functions.php');
require_once('include/quotation_pdf_builder.php');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
	die('Invalid quotation.');
}
$row = quotation_get($conn, $id);
if (!$row) {
	die('Quotation not found.');
}
if (!in_array($row['status'], array('approved', 'sent', 'customer_confirmed', 'converted'), true)) {
	die('PDF is available after approval.');
}

require_once __DIR__ . '/vendor/autoload.php';
$mpdf = quotation_mpdf_create();
$mpdf->SetTitle('Quotation - ' . $row['quote_no']);
$mpdf->SetAuthor('EliteWave360 Logistics');
if (!quotation_mpdf_write_quotation($mpdf, $conn, $id)) {
	die('Could not build PDF.');
}
$filename = 'Quotation_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $row['quote_no']) . '.pdf';
$download = isset($_GET['download']) && $_GET['download'] === '1';
$mpdf->Output($filename, $download ? 'D' : 'I');
