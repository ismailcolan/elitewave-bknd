<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_general_helpers.php');
require_once('include/expense_gcn_pdf_builder.php');
require_once __DIR__ . '/vendor/autoload.php';

expense_require_admin();

$general_expense_id = (int) ($_GET['general_expense_id'] ?? 0);
$scope_gcn_key = trim((string) ($_GET['gcn_key'] ?? ''));
$download = !empty($_GET['download']);

$view = expense_general_view_data($conn, $general_expense_id, $scope_gcn_key);
if (!$view || strtolower((string) ($view['voucher_type'] ?? '')) !== 'gcn') {
	header('HTTP/1.1 404 Not Found');
	echo 'GCN voucher not found.';
	exit;
}

$html = expense_gcn_build_pdf_html($conn, $view);

$mpdf = new \Mpdf\Mpdf(array(
	'mode' => 'utf-8',
	'format' => 'A4',
	'default_font' => 'freesans',
	'margin_left' => 8,
	'margin_right' => 8,
	'margin_top' => 8,
	'margin_bottom' => 12,
));

$expense_no = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) ($view['expense_no'] ?? 'Voucher'));
$mpdf->SetTitle('GCN Expense — ' . ($view['expense_no'] ?? ''));
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);

$filename = 'GCN_Expense_' . $expense_no . '.pdf';
$mpdf->Output($filename, $download ? 'D' : 'I');
