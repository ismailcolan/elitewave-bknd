<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_gcn_pl_helpers.php');
require_once('include/expense_gcn_pl_pdf_builder.php');
require_once __DIR__ . '/vendor/autoload.php';

expense_require_admin();

$gcn_key = trim((string) ($_GET['gcn_key'] ?? ''));
$download = !empty($_GET['download']);

if ($gcn_key === '') {
	header('HTTP/1.1 400 Bad Request');
	echo 'GCN key required.';
	exit;
}

$data = expense_gcn_pl_detail($conn, $gcn_key);
if (!$data) {
	header('HTTP/1.1 404 Not Found');
	echo 'GCN not found.';
	exit;
}

$html = expense_gcn_pl_build_pdf_html($conn, $data);

$mpdf = new \Mpdf\Mpdf(array(
	'mode' => 'utf-8',
	'format' => 'A4',
	'default_font' => 'freesans',
	'margin_left' => 8,
	'margin_right' => 8,
	'margin_top' => 8,
	'margin_bottom' => 12,
));

$gcn_no = preg_replace('/[^a-zA-Z0-9_\-\/]/', '_', (string) ($data['grn_no'] ?? 'GCN'));
$mpdf->SetTitle('GCN P&L — ' . ($data['grn_no'] ?? ''));
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);

$filename = 'GCN_PL_' . $gcn_no . '.pdf';
$mpdf->Output($filename, $download ? 'D' : 'I');