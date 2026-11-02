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
	if (in_array($i, array(0, 5, 6, 7), true)) {
		return 'right';
	}
	if (in_array($i, array(2, 8, 9), true)) {
		return 'center';
	}
	return 'left';
};

$col_extra = static function ($i) {
	if (in_array($i, array(0, 5, 6, 7), true)) {
		return 'white-space:nowrap;';
	}
	if ($i === 4) {
		return 'word-wrap:break-word;';
	}
	return '';
};

$colgroup = '<colgroup>'
	. '<col style="width:4%;">'
	. '<col style="width:10%;">'
	. '<col style="width:8%;">'
	. '<col style="width:12%;">'
	. '<col style="width:24%;">'
	. '<col style="width:10%;">'
	. '<col style="width:8%;">'
	. '<col style="width:9%;">'
	. '<col style="width:8%;">'
	. '<col style="width:7%;">'
	. '</colgroup>';

ew_list_table_export_pdf_send($conn, $payload, array(
	'title' => 'Payment Receipt List',
	'subtitle' => 'List export',
	'file_prefix' => 'receipt_list',
	'colgroup_html' => $colgroup,
	'col_align' => $col_align,
	'col_extra' => $col_extra,
));
