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

// value 1 = With narration → GCN detail; value 0 = Without narration → ledger statement (Date, Particulars, Dr/Cr)
$narration_val = isset($_GET['narration']) ? (string) $_GET['narration'] : '1';
if ($narration_val === '1') {
    $html = pending_payments_ledger_build_gcn_pdf_html($conn, $filters, $ledger);
    $file_prefix = 'Ledger_GCN_';
    $page_format = 'A4-L';
    $doc_title = 'Client Ledger GCN detail';
} else {
    $html = pending_payments_ledger_build_pdf_html($conn, $filters, $ledger);
    $file_prefix = 'Ledger_';
    $page_format = 'A4';
    $doc_title = 'Client Ledger Statement';
}

$mpdf = new \Mpdf\Mpdf(array(
    'mode' => 'utf-8',
    'format' => $page_format,
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
$filename = $file_prefix . $client_slug . '_' . str_replace('-', '', $filters['from_date']) . '_to_' . str_replace('-', '', $filters['to_date']) . '.pdf';

$mpdf->SetTitle($doc_title);
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);

$download = !empty($_GET['download']);
$mpdf->Output($filename, $download ? 'D' : 'I');
