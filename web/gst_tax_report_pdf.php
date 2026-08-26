<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/gst_tax_report_functions.php');
require_once('include/gst_tax_report_pdf_builder.php');
require_once __DIR__ . '/vendor/autoload.php';

$filters = gst_tax_report_parse_filters($_GET);
if (!empty($filters['error'])) {
    die($filters['error']);
}

$result = gst_tax_report_fetch_rows($conn, $filters);
$html = gst_tax_report_build_pdf_html($conn, $filters, $result['rows'], $result['summary']);

$mpdf = new \Mpdf\Mpdf(array(
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'freesans',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 5,
    'margin_bottom' => 5,
));

$mpdf->SetTitle('GST Tax Report');
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);

$filename = 'GST_Tax_Report_' . str_replace('-', '', $filters['from_date']) . '_to_' . str_replace('-', '', $filters['to_date']) . '.pdf';
$mpdf->Output($filename, 'I');
