<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_note_functions.php');
require_once('include/credit_note_pdf_builder.php');
require_once('include/ew_pdf_browser_view.php');

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
	header('HTTP/1.1 403 Forbidden');
	echo 'Session expired.';
	exit;
}

$billing_note_id = (int) ($_GET['id'] ?? $_GET['billing_note_id'] ?? 0);
$download = !empty($_GET['download']);

billing_credit_note_sync_tax_from_invoice($conn, $billing_note_id, $user_id);

$viewWrap = credit_note_pdf_fetch_view($conn, $billing_note_id);
if (empty($viewWrap['ok'])) {
	header('HTTP/1.1 404 Not Found');
	echo htmlspecialchars($viewWrap['message'] ?? 'Credit note not found.');
	exit;
}
unset($viewWrap['ok']);
$view = $viewWrap;

ew_pdf_maybe_browser_shell('Credit Note — ' . ($view['note_no'] ?? ''));

require_once __DIR__ . '/vendor/autoload.php';

$html = credit_note_build_pdf_html($conn, $view);
if ($html === '') {
	die('Could not build credit note PDF.');
}

$mpdf = new \Mpdf\Mpdf(array(
	'mode' => 'utf-8',
	'format' => 'A4',
	'default_font' => 'freesans',
	'margin_left' => 8,
	'margin_right' => 8,
	'margin_top' => 8,
	'margin_bottom' => 12,
));

$note_no = (string) ($view['note_no'] ?? '');
$mpdf->SetTitle('Credit Note — ' . $note_no);
$mpdf->SetAuthor('EliteWave360 Logistics');

$status = strtolower(trim((string) ($view['status'] ?? '')));
if ($status === 'cancelled') {
	$wm = __DIR__ . '/images/pdf/cancel2.png';
	if (is_file($wm)) {
		$mpdf->SetWatermarkImage($wm, 0.35, '', array(60, 110));
		$mpdf->showWatermarkImage = true;
	}
} elseif ($status === 'draft') {
	$mpdf->SetWatermarkText('DRAFT');
	$mpdf->showWatermarkText = true;
	$mpdf->watermarkTextAlpha = 0.12;
}

$mpdf->WriteHTML($html);

$filename = 'Credit_Note_' . preg_replace('/[^a-zA-Z0-9_\-\/]/', '_', $note_no) . '.pdf';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$mpdf->Output($filename, $download ? 'D' : 'I');