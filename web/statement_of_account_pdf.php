<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';
require_once __DIR__ . '/include/statement_of_account_functions.php';
require_once __DIR__ . '/include/statement_of_account_pdf_builder.php';
require_once __DIR__ . '/vendor/autoload.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Session expired. Please sign in again.');
}

$filters = soa_parse_filters($_GET);
if (!empty($filters['error'])) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($filters['error']);
}

$ledger = soa_fetch($conn, $filters);
if (!empty($ledger['error'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($ledger['error']);
}

$html = soa_build_pdf_html($conn, $filters, $ledger);

$mpdf = new \Mpdf\Mpdf(array(
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'freesans',
    'margin_left' => 8,
    'margin_right' => 8,
    'margin_top' => 8,
    'margin_bottom' => 10,
));

$party_slug = preg_replace('/[^A-Za-z0-9]+/', '_', $ledger['party']['name'] ?? 'Party');
$party_slug = trim($party_slug, '_');
if ($party_slug === '') {
    $party_slug = 'Party';
}
$type_slug = (($ledger['party_type'] ?? 'customer') === 'vendor') ? 'Vendor' : 'Customer';
$filename = 'SOA_' . $type_slug . '_' . $party_slug . '_' . str_replace('-', '', $filters['from_date']) . '_to_' . str_replace('-', '', $filters['to_date']) . '.pdf';

$mpdf->SetTitle('Statement of Account');
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);

$download = !empty($_GET['download']);
$mpdf->Output($filename, $download ? 'D' : 'I');
