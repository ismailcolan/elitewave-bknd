<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/ew_list_table_export_pdf.php';

if (empty($_SESSION['user_id'])) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	exit('Session expired.');
}

$payload = ew_list_export_pdf_read_payload();

$col_align = static function ($i) {
	if ($i === 0 || $i === 5 || $i === 6) {
		return 'right';
	}
	if ($i === 2 || $i === 4) {
		return 'center';
	}
	return 'left';
};

$col_extra = static function ($i) {
	if ($i === 0 || $i === 5 || $i === 6) {
		return 'white-space:nowrap;';
	}
	if ($i === 3) {
		return 'word-wrap:break-word;';
	}
	return '';
};

$colgroup = '<colgroup>'
	. '<col style="width:5%;">'
	. '<col style="width:13%;">'
	. '<col style="width:9%;">'
	. '<col style="width:40%;">'
	. '<col style="width:9%;">'
	. '<col style="width:14%;">'
	. '<col style="width:10%;">'
	. '</colgroup>';

ew_list_table_export_pdf_send($conn, $payload, array(
	'title' => 'Tax Invoice List',
	'subtitle' => 'List export',
	'file_prefix' => 'tax_invoice_list',
	'colgroup_html' => $colgroup,
	'col_align' => $col_align,
	'col_extra' => $col_extra,
));
