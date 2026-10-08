<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_gcn_helpers.php');
require_once('include/expense_gcn_pdf_builder.php');
require_once('include/expense_schema.php');
require_once __DIR__ . '/vendor/autoload.php';

expense_require_admin();

$gcn_expense_id = (int) ($_GET['gcn_expense_id'] ?? 0);
$download = !empty($_GET['download']);

$view = expense_gcn_fetch_view($conn, $gcn_expense_id);
if (empty($view['ok'])) {
	header('HTTP/1.1 404 Not Found');
	echo htmlspecialchars($view['message'] ?? 'Expense record not found.');
	exit;
}
unset($view['ok']);

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

$gcn_label = preg_replace('/[^a-zA-Z0-9_\-\/]/', '_', (string) ($view['grn_no'] ?? 'GCN'));
$mpdf->SetTitle('GCN Expense — ' . ($view['grn_no'] ?? ''));
$mpdf->SetAuthor('EliteWave360 Logistics');
$mpdf->WriteHTML($html);

$filename = 'GCN_Expense_' . $gcn_label . '.pdf';
$mpdf->Output($filename, $download ? 'D' : 'I');
