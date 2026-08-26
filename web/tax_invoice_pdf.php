<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/tax_invoice_pdf_builder.php');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid invoice.');
}

$data = billing_get_invoice($conn, $id);
if (!$data || $data['master']['status'] !== 'final') {
    die('Invoice not available.');
}

require_once __DIR__ . '/vendor/autoload.php';

$html = tax_invoice_build_pdf_html($conn, $id);
if ($html === '') {
    die('Could not build invoice PDF.');
}

$mpdf = new \Mpdf\Mpdf(array(
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'freesans',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 5,
    'margin_bottom' => 5,
));
$mpdf->SetTitle('Tax Invoice - ' . $data['master']['invoice_no']);
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);
$filename = 'Tax_Invoice_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $data['master']['invoice_no']) . '.pdf';
$download = isset($_GET['download']) && $_GET['download'] === '1';
$mpdf->Output($filename, $download ? 'D' : 'I');
