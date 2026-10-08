<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_proforma_functions.php');
require_once('include/tax_invoice_pdf_builder.php');
require_once('include/ew_pdf_browser_view.php');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid proforma.');
}

$data = billing_proforma_get($conn, $id);
if (!$data || $data['master']['status'] !== 'final') {
    die('Proforma not available.');
}

$doc_style = $data['master']['doc_style'] ?? 'gst';
$title_label = ($doc_style === 'other') ? 'Proforma Invoice' : 'Proforma Tax Invoice';
ew_pdf_maybe_browser_shell($title_label . ' — ' . ($data['master']['proforma_no'] ?? ''));

require_once __DIR__ . '/vendor/autoload.php';

$html = tax_invoice_build_pdf_html($conn, $id, array('proforma' => true));
if ($html === '') {
    die('Could not build proforma PDF.');
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
$mpdf->SetWatermarkText('PROFORMA');
$mpdf->showWatermarkText = true;
$mpdf->watermarkTextAlpha = 0.12;
$proforma_no = $data['master']['proforma_no'] ?? '';
$mpdf->SetTitle($title_label . ' - ' . $proforma_no);
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);
$prefix = ($doc_style === 'other') ? 'Proforma_Invoice_' : 'Proforma_Tax_Invoice_';
$filename = $prefix . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $proforma_no) . '.pdf';
$download = isset($_GET['download']) && $_GET['download'] === '1';
$mpdf->Output($filename, $download ? 'D' : 'I');
