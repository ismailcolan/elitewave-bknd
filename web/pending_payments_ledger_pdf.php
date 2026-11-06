<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';
require_once __DIR__ . '/include/pending_payments_ledger_functions.php';
require_once __DIR__ . '/include/pending_payments_ledger_pdf_builder.php';
require_once __DIR__ . '/vendor/autoload.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Session expired. Please sign in again.');
}

$filters = pending_payments_ledger_parse_filters($_GET);
if (!empty($filters['error'])) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($filters['error']);
}

$ledger = pending_payments_ledger_fetch($conn, $filters);
if (!empty($ledger['error'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($ledger['error']);
}

$html = pending_payments_ledger_build_pdf_html($conn, $filters, $ledger);

$mpdf = new \Mpdf\Mpdf(array(
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'freesans',
    'margin_left' => 8,
    'margin_right' => 8,
    'margin_top' => 8,
    'margin_bottom' => 10,
));

$client_slug = preg_replace('/[^A-Za-z0-9]+/', '_', $ledger['client']['name'] ?? 'Customer');
$client_slug = trim($client_slug, '_');
if ($client_slug === '') {
    $client_slug = 'Customer';
}
$filename = 'Ledger_' . $client_slug . '_' . str_replace('-', '', $filters['from_date']) . '_to_' . str_replace('-', '', $filters['to_date']) . '.pdf';

$mpdf->SetTitle('Client Ledger Statement');
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);

$download = !empty($_GET['download']);
$mpdf->Output($filename, $download ? 'D' : 'I');
