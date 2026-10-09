<?php

require_once __DIR__ . '/elitewave_pdf_letterhead.php';
require_once __DIR__ . '/quotation_pdf_gcn_terms.php';
require_once __DIR__ . '/quotation_multi_mode.php';
require_once __DIR__ . '/quotation_consignor_multi_dest.php';

define('EW_QUOTE_PDF_NAVY', EW_LH_NAVY);
define('EW_QUOTE_PDF_LINE', EW_LH_LINE);
define('EW_QUOTE_PDF_ACCENT', EW_LH_ACCENT);

function quotation_pdf_web_root()
{
	return dirname(__DIR__);
}

function quotation_pdf_letterhead_open($conn)
{
	return elitewave_pdf_letterhead_open($conn, 'Quotation/Proforma Invoice', '', true, true);
}

function quotation_pdf_page1_compact_css()
{
	return '
<style>
.ew-quote-p1{ font-size:9pt; line-height:1.28; }
.ew-quote-p1 .ew-quote-section-title{ margin:4px 0 0; font-size:10pt; }
.ew-quote-p1 .ew-doc-label{ font-size:9pt; margin-bottom:1px; }
.ew-quote-p1 .ew-doc-value{ font-size:9pt; }
.ew-quote-p1 .ew-doc-secondary{ font-size:8.5pt; }
.ew-quote-p1 .ew-shipment-block{ page-break-inside:avoid; }
.ew-quote-p1 table.ew-quote-info-table{ page-break-inside:avoid; }
.ew-quote-p1 .ew-quote-addr{ font-size:8pt; line-height:1.12; word-wrap:break-word; }
</style>';
}

function quotation_pdf_letterhead_close($include_footer = true)
{
	return elitewave_pdf_letterhead_close($include_footer);
}

function quotation_pdf_label_cell($is_last_row = false, $extra = '')
{
	$navy = EW_QUOTE_PDF_NAVY;
	$bottom = $is_last_row ? 'border-bottom:0;' : 'border-bottom:1px solid #ffffff;';
	return 'padding:5px 10px;font-size:9pt;font-weight:bold;color:#fff;vertical-align:top;background:' . $navy . ';'
		. 'border-left:0;border-top:0;border-right:1px solid ' . $navy . ';' . $bottom . $extra;
}

function quotation_pdf_value_cell($is_last_row = false, $extra = '')
{
	$navy = EW_QUOTE_PDF_NAVY;
	$bottom = $is_last_row ? 'border-bottom:0;' : 'border-bottom:1px solid ' . $navy . ';';
	return 'padding:5px 10px;font-size:9pt;color:#000;font-weight:bold;vertical-align:top;background:#fff;'
		. 'border-left:0;border-top:0;border-right:0;' . $bottom . $extra;
}

function quotation_pdf_info_row($label, $value, $is_last_row, $label_width = '32%', $value_extra = '')
{
	return '
<tr>
	<td width="' . $label_width . '" style="' . quotation_pdf_label_cell($is_last_row) . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>
	<td style="' . quotation_pdf_value_cell($is_last_row, $value_extra) . '">' . $value . '</td>
</tr>';
}

function quotation_pdf_info_table_style()
{
	return 'border-collapse:collapse;margin-bottom:6px;border:2px solid ' . EW_QUOTE_PDF_NAVY . ';page-break-inside:avoid;';
}

/** Commercial summary row: navy description column, white amount column (matches logistics tables). */
function quotation_pdf_commercial_amount_row($label, $amount_text, $is_last_row, $label_width = '62%', $total_row = false)
{
	$fs = $total_row ? '10.5pt' : '9pt';
	$pad = $total_row ? '6px 10px' : '5px 10px';
	$navy = EW_QUOTE_PDF_NAVY;
	$bottom_l = $is_last_row ? 'border-bottom:0;' : 'border-bottom:1px solid #ffffff;';
	$bottom_v = $is_last_row ? 'border-bottom:0;' : 'border-bottom:1px solid ' . $navy . ';';
	$label_style = 'padding:' . $pad . ';font-size:' . $fs . ';font-weight:bold;color:#fff;vertical-align:middle;background:' . $navy . ';'
		. 'border-left:0;border-top:0;border-right:1px solid ' . $navy . ';' . $bottom_l;
	$value_style = 'padding:' . $pad . ';font-size:' . $fs . ';color:#000;font-weight:bold;vertical-align:middle;background:#fff;text-align:right;white-space:nowrap;'
		. 'border-left:0;border-top:0;border-right:0;' . $bottom_v;

	return '
<tr>
	<td width="' . $label_width . '" style="' . $label_style . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>
	<td style="' . $value_style . '">' . htmlspecialchars($amount_text, ENT_QUOTES, 'UTF-8') . '</td>
</tr>';
}

function quotation_pdf_commercial_table_header_row($label_width = '62%')
{
	$navy = EW_QUOTE_PDF_NAVY;
	$cell = 'padding:5px 10px;font-size:9pt;font-weight:bold;color:#fff;vertical-align:middle;background:' . $navy . ';'
		. 'border-bottom:1px solid #ffffff;';

	return '
<tr>
	<td width="' . $label_width . '" style="' . $cell . 'border-right:1px solid ' . $navy . ';">Description</td>
	<td style="' . $cell . 'text-align:right;">Amount</td>
</tr>';
}

function quotation_pdf_section_heading($title, $red_on_left = false)
{
	$navy = EW_QUOTE_PDF_NAVY;
	$t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

	return '
<div class="ew-quote-section-title" style="margin-top:8px;">
	<div style="font-size:10pt;font-weight:bold;color:' . $navy . ';margin:0 0 4px;padding:0;">' . $t . '</div>'
		. elitewave_pdf_brand_bar_html('8px', $red_on_left)
		. '
</div>';
}

function quotation_pdf_meta_cell($label, $value)
{
	return '
<td style="width:50%;vertical-align:top;border:0;padding:0 12px 0 0;">
	<div class="ew-doc-label">' . htmlspecialchars($label) . '</div>
	<div class="ew-doc-value">' . $value . '</div>
</td>';
}

/** Full delivery address for PDF (no truncation). */
function quotation_pdf_delivery_for_pdf($text)
{
	$text = trim(str_replace(array("\r\n", "\r"), "\n", (string) $text));
	if ($text === '') {
		return '—';
	}
	$lines = array_values(array_filter(array_map('trim', explode("\n", $text)), function ($l) {
		return $l !== '';
	}));
	if ($lines === array()) {
		return '—';
	}

	return '<span class="ew-quote-addr">' . nl2br(htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8')) . '</span>';
}

function quotation_pdf_addr_value_style()
{
	return 'line-height:1.12;font-size:8pt;word-wrap:break-word;padding:3px 8px;';
}

function quotation_pdf_consignor_multi_dest_table_html($conn, $quotation_id)
{
	$rows = quotation_destination_rows_for_display($conn, $quotation_id);
	if ($rows === array()) {
		return '';
	}
	$navy = EW_QUOTE_PDF_NAVY;
	$th = 'padding:4px 5px;font-size:7pt;font-weight:bold;color:#fff;background:' . $navy . ';border:1px solid ' . $navy . ';vertical-align:middle;';
	$td = 'padding:4px 5px;font-size:7pt;color:#000;border:1px solid ' . $navy . ';vertical-align:top;';
	$tdr = $td . 'text-align:right;white-space:nowrap;';
	$fmt = function ($n) {
		return 'Rs. ' . quotation_format_money_display($n) . ' /-';
	};
	$body = '<tr>'
		. '<th style="' . $th . '">Destination</th>'
		. '<th style="' . $th . '">Mode</th>'
		. '<th style="' . $th . '">Source of transport</th>'
		. '<th style="' . $th . '">Days</th>'
		. '<th style="' . $th . 'text-align:right;">Freight</th>'
		. '</tr>';
	foreach ($rows as $r) {
		$body .= '<tr>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['destination_label'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['mode_type'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['vehicle_display'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['delivery_days_label'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $tdr . '">' . htmlspecialchars($fmt($r['freight_charges'] ?? 0), ENT_QUOTES, 'UTF-8') . '</td>'
			. '</tr>';
	}
	return quotation_pdf_section_heading('Destination-wise quotation (consignor)', false)
		. '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 6px;">' . $body . '</table>';
}

function quotation_pdf_all_modes_shipment_html($q, $delivery)
{
	$consignee = htmlspecialchars($q['destination_name'] ?? '—', ENT_QUOTES, 'UTF-8');

	return '
<table width="100%" cellpadding="0" cellspacing="0" class="ew-quote-info-table" style="' . quotation_pdf_info_table_style() . '">'
		. quotation_pdf_info_row('Consignee / delivery party', $consignee, false, '28%')
		. quotation_pdf_info_row('Delivery address', $delivery, true, '28%', quotation_pdf_addr_value_style())
		. '
</table>';
}

function quotation_pdf_info_table_from_rows(array $rows, $label_width = '22%')
{
	$body = '';
	$last = count($rows) - 1;
	foreach ($rows as $i => $row) {
		$extra = isset($row[2]) ? $row[2] : '';
		$body .= quotation_pdf_info_row($row[0], $row[1], ($i === $last), $label_width, $extra);
	}

	return '
<table width="100%" cellpadding="0" cellspacing="0" class="ew-quote-info-table" style="' . quotation_pdf_info_table_style() . '">'
		. $body
		. '
</table>';
}

function quotation_pdf_shipment_details_table_html($q, $loading, $dims, $delivery, $mode_label = '')
{
	return quotation_pdf_info_table_from_rows(
		quotation_pdf_shipment_detail_rows($q, $loading, $dims, $delivery, $mode_label),
		'22%'
	);
}

function quotation_pdf_other_details_html($conn, $label_width = '22%')
{
	return quotation_pdf_section_heading('Other details', false)
		. '<div class="ew-shipment-block">'
		. quotation_pdf_info_table_from_rows(quotation_pdf_transport_detail_rows($conn), $label_width)
		. '</div>';
}

function quotation_pdf_shipment_detail_rows($q, $loading, $dims, $delivery, $mode_label = '')
{
	$origin = htmlspecialchars($q['origin_text'] ?? '—', ENT_QUOTES, 'UTF-8');
	$dest = htmlspecialchars($q['destination_name'] ?? '—', ENT_QUOTES, 'UTF-8');
	$unload = htmlspecialchars($q['unloading_at'] ?? '—', ENT_QUOTES, 'UTF-8');
	$load = htmlspecialchars($loading, ENT_QUOTES, 'UTF-8');
	$vehicle = htmlspecialchars($q['vehicle_label'] ?? '—', ENT_QUOTES, 'UTF-8');
	$dim_txt = $dims !== '' ? htmlspecialchars($dims, ENT_QUOTES, 'UTF-8') : '—';

	$route = $origin . ' → ' . $dest
		. ($unload !== '—' ? ' (Unloading at ' . $unload . ')' : '');
	$load_unload = $load . ' / ' . $unload;
	$vehicle_line = $vehicle . ($dim_txt !== '—' ? ' · ' . $dim_txt : '');

	$mode_h = htmlspecialchars(trim((string) $mode_label) !== '' ? $mode_label : '—', ENT_QUOTES, 'UTF-8');
	$detail_rows = array(
		array('Route', $route, ''),
		array('Mode / Vehicle', $mode_h . ' · ' . $vehicle_line, ''),
		array('Loading / Unloading', $load_unload, ''),
	);
	$cfs = trim((string) ($q['cfs_port_factory'] ?? ''));
	if ($cfs !== '') {
		$detail_rows[] = array('CFS / Port / Factory / Warehouse', htmlspecialchars($cfs, ENT_QUOTES, 'UTF-8'), '');
	}
	$part = trim((string) ($q['part_number'] ?? ''));
	if ($part !== '') {
		$detail_rows[] = array('Part number / article', htmlspecialchars($part, ENT_QUOTES, 'UTF-8'), '');
	}
	$detail_rows[] = array('Delivery address', $delivery, quotation_pdf_addr_value_style());

	return $detail_rows;
}

function quotation_pdf_transport_detail_rows($conn)
{
	$ids = function_exists('elitewave_letterhead_company_ids')
		? elitewave_letterhead_company_ids($conn)
		: array('gstin' => '', 'pan' => '');
	$gst = trim((string) ($ids['gstin'] ?? ''));
	if (!function_exists('ew_company_bank_options')) {
		require_once __DIR__ . '/company_bank_helpers.php';
	}
	$bank_text = '—';
	$accounts = ew_company_bank_options($conn);
	if ($accounts !== array()) {
		$bank = $accounts[0];
		$parts = array();
		$name = trim((string) ($bank['bank_name'] ?? ''));
		$ac = trim((string) ($bank['account_number'] ?? ''));
		$ifsc = trim((string) ($bank['ifsc'] ?? ''));
		$branch = trim((string) ($bank['bank_branch'] ?? ''));
		if ($name !== '') {
			$parts[] = $name;
		}
		if ($ac !== '') {
			$parts[] = 'A/c ' . $ac;
		}
		if ($ifsc !== '') {
			$parts[] = 'IFSC ' . $ifsc;
		}
		if ($branch !== '') {
			$parts[] = $branch;
		}
		if ($parts !== array()) {
			$bank_text = htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
		}
	}
	$gst_h = $gst !== '' ? htmlspecialchars($gst, ENT_QUOTES, 'UTF-8') : '—';
	$bank_style = quotation_pdf_addr_value_style();

	return array(
		array('Transporter ID', $gst_h, ''),
		array('Bank details', $bank_text, $bank_style),
	);
}

function quotation_pdf_transport_rows_html($conn, $label_width = '22%')
{
	$rows = quotation_pdf_transport_detail_rows($conn);
	$html = '';
	$last = count($rows) - 1;
	foreach ($rows as $i => $row) {
		$html .= quotation_pdf_info_row($row[0], $row[1], ($i === $last), $label_width, $row[2]);
	}

	return $html;
}

function quotation_pdf_logistics_info_html($conn, $q, $pay_terms_label, $insurance_number, $include_heading = true)
{
	$candidates = array(
		array('Quotation approval', htmlspecialchars($q['quotation_approval'] ?? '', ENT_QUOTES, 'UTF-8')),
		array('Payment terms', htmlspecialchars(trim((string) $pay_terms_label), ENT_QUOTES, 'UTF-8')),
		array('Insurance number', htmlspecialchars($insurance_number, ENT_QUOTES, 'UTF-8')),
	);
	$items = array();
	foreach ($candidates as $pair) {
		if (trim((string) $pair[1]) !== '' && $pair[1] !== '—') {
			$items[] = $pair;
		}
	}
	if ($items === array()) {
		return '';
	}
	$rows = '';
	$last = count($items) - 1;
	foreach ($items as $i => $pair) {
		$rows .= quotation_pdf_info_row($pair[0], $pair[1], ($i === $last));
	}
	$table = '<table width="100%" cellpadding="0" cellspacing="0" style="' . quotation_pdf_info_table_style() . '">' . $rows . '</table>';
	if (!$include_heading) {
		return $table;
	}
	return quotation_pdf_section_heading('Logistics reference') . $table;
}

function quotation_pdf_commercial_summary_html($charge_rows, $gst_pct, $gst_amt, $total)
{
	$gst_label = 'GST @ ' . $gst_pct . '%';
	$gst_disp = 'Rs. ' . $gst_amt . ' /-';
	$total_disp = 'Rs. ' . $total . ' /-';

	return quotation_pdf_section_heading('Commercial summary', true)
		. '<table width="100%" cellpadding="0" cellspacing="0" style="' . quotation_pdf_info_table_style() . '">'
		. quotation_pdf_commercial_table_header_row()
		. $charge_rows
		. quotation_pdf_commercial_amount_row($gst_label, $gst_disp, false)
		. quotation_pdf_commercial_amount_row('Total', $total_disp, true, '62%', true)
		. '</table>';
}

function quotation_mpdf_create()
{
	return new \Mpdf\Mpdf(array(
		'mode' => 'utf-8',
		'format' => 'A4',
		'default_font' => 'freesans',
		'margin_left' => 12,
		'margin_right' => 12,
		'margin_top' => 10,
		'margin_bottom' => 12,
		'tempDir' => sys_get_temp_dir(),
		'shrink_tables_to_fit' => 1,
	));
}

/** Page 2 signatory and brand footer are in quotation_pdf_terms_body_html(). */
function quotation_mpdf_write_page2_closing_at_bottom($mpdf, $closing_height_mm = 34)
{
	unset($closing_height_mm);
}

function quotation_mpdf_write_html($mpdf, $html)
{
	$prev = getcwd();
	@chdir(quotation_pdf_web_root());
	try {
		$mpdf->WriteHTML($html);
	} finally {
		if ($prev !== false && $prev !== '') {
			@chdir($prev);
		}
	}
}

function quotation_pdf_multi_mode_table_html($conn, $quotation_id)
{
	$rows = quotation_multi_mode_rows_for_display($conn, $quotation_id);
	if ($rows === array()) {
		return '';
	}
	$navy = EW_QUOTE_PDF_NAVY;
	$th = 'padding:4px 5px;font-size:7.5pt;font-weight:bold;color:#fff;background:' . $navy . ';border:1px solid ' . $navy . ';vertical-align:middle;';
	$td = 'padding:4px 5px;font-size:7.5pt;color:#000;border:1px solid ' . $navy . ';vertical-align:top;';
	$tdr = $td . 'text-align:right;white-space:nowrap;';
	$fmt = function ($n) {
		return 'Rs. ' . quotation_format_money_display($n) . ' /-';
	};
	$body = '<tr>'
		. '<th style="' . $th . '">Mode</th>'
		. '<th style="' . $th . '">Source of transport</th>'
		. '<th style="' . $th . '">Days</th>'
		. '<th style="' . $th . 'text-align:right;">Freight</th>'
		. '</tr>';
	foreach ($rows as $r) {
		$body .= '<tr>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['mode_type'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['vehicle_display'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['delivery_days_label'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $tdr . '">' . htmlspecialchars($fmt($r['freight_charges'] ?? 0), ENT_QUOTES, 'UTF-8') . '</td>'
			. '</tr>';
	}
	return quotation_pdf_section_heading('Mode-wise quotation (all modes)', false)
		. '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 6px;">' . $body . '</table>';
}

function quotation_build_pdf_pages($conn, $quotation_id)
{
	require_once __DIR__ . '/quotation_functions.php';
	$q = quotation_get($conn, $quotation_id);
	if (!$q) {
		return null;
	}
	$is_multi = quotation_is_multi_mode_quote_type($q['quote_type'] ?? '');
	$is_consignor_md = quotation_is_consignor_multi_dest_quote_type($q['quote_type'] ?? '');
	$lines = quotation_get_lines($conn, $quotation_id);
	$party = quotation_recipient_name($conn, $q);
	$dims = quotation_dim_display($q);
	$loading = quotation_loading_label($q['loading_type'] ?? '');
	$mode_label = quotation_mode_of_transport_label($conn, (int) ($q['mode_of_transportation'] ?? 0));

	$charge_rows = '';
	foreach ($lines as $line) {
		$amt = (float) $line['amount'];
		$disp = ($amt > 0) ? ('Rs. ' . quotation_format_money_display($amt) . ' /-') : 'Rs. 0 /-';
		$label = trim((string) ($line['charge_label'] ?? ''));
		$rem = trim((string) ($line['remarks'] ?? ''));
		if ($rem !== '' && !quotation_is_insurance_charge_label($line['charge_label'] ?? '')) {
			$label .= ' — ' . $rem;
		}
		$charge_rows .= quotation_pdf_commercial_amount_row($label, $disp, false);
	}

	$gst_pct = rtrim(rtrim(number_format((float) $q['gst_rate'], 2, '.', ''), '0'), '.');
	$gst_amt = quotation_format_money_display($q['gst_amount']);
	$total = quotation_format_money_display($q['total_amount']);
	$subject = htmlspecialchars($q['subject'] ?? '', ENT_QUOTES, 'UTF-8');
	$delivery = quotation_pdf_delivery_for_pdf($q['delivery_address'] ?? '');
	$valid_till = htmlspecialchars($q['valid_till'] ?? '', ENT_QUOTES, 'UTF-8');
	$pay_terms_raw = quotation_payment_terms_label($q['payment_terms'] ?? '', $q['payment_terms_note'] ?? '');
	$insurance_no = quotation_insurance_number_from_lines($lines);

	$party_h = htmlspecialchars($party, ENT_QUOTES, 'UTF-8');
	$attn_h = htmlspecialchars($q['attn_name'] ?? '—', ENT_QUOTES, 'UTF-8');
	$quote_h = htmlspecialchars($q['quote_no'], ENT_QUOTES, 'UTF-8');
	$date_h = htmlspecialchars($q['quote_date'], ENT_QUOTES, 'UTF-8');

	$html = quotation_pdf_letterhead_open($conn);
	$html .= quotation_pdf_page1_compact_css();
	$html .= '<div class="ew-quote-p1">';

	$html .= '
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:2px;"><tr>'
	. quotation_pdf_meta_cell('Prepared for', $party_h . ($attn_h !== '—' ? '<br><span class="ew-doc-secondary">Attn: ' . $attn_h . '</span>' : ''))
	. quotation_pdf_meta_cell('Reference', $quote_h . '<br><span class="ew-doc-secondary">Date ' . $date_h
		. ($valid_till !== '' ? ' · Valid till ' . $valid_till : '') . '</span>')
	. '</tr></table>';

	if ($subject !== '') {
		$html .= '
<p style="margin:8px 0 0;font-size:9.5pt;color:#000;line-height:1.35;"><span style="font-weight:bold;color:' . EW_QUOTE_PDF_NAVY . ';">Subject:</span> <b>' . $subject . '</b></p>';
	}

	$html .= '
<p style="margin:8px 0 8px;font-size:9.5pt;color:#000;font-weight:bold;line-height:1.38;">Dear Sir / Madam,<br>
<span style="font-weight:normal;">' . htmlspecialchars(quotation_letter_intro_text(), ENT_QUOTES, 'UTF-8') . '</span></p>';

	$html .= quotation_pdf_logistics_info_html($conn, $q, $pay_terms_raw, $insurance_no);

	if ($is_consignor_md) {
		$html .= quotation_pdf_consignor_multi_dest_table_html($conn, $quotation_id);
	} elseif ($is_multi) {
		$html .= quotation_pdf_multi_mode_table_html($conn, $quotation_id);
	} else {
		$html .= quotation_pdf_commercial_summary_html($charge_rows, $gst_pct, $gst_amt, $total);
	}

	if ($is_consignor_md) {
		$consignor_h = htmlspecialchars(trim((string) ($party ?? '')), ENT_QUOTES, 'UTF-8');
		$html .= quotation_pdf_section_heading('Consignor details', false)
			. '<table width="100%" cellpadding="0" cellspacing="0" class="ew-quote-info-table" style="' . quotation_pdf_info_table_style() . '">'
			. quotation_pdf_info_row('Consignor', $consignor_h !== '' ? $consignor_h : '—', true, '28%')
			. '</table>';
		$html .= quotation_pdf_other_details_html($conn, '28%');
	} elseif (!$is_multi) {
		$html .= quotation_pdf_section_heading('Shipment details', false)
			. '<div class="ew-shipment-block">'
			. quotation_pdf_shipment_details_table_html($q, $loading, $dims, $delivery, $mode_label)
			. '</div>';
		$html .= quotation_pdf_other_details_html($conn, '22%');
	} else {
		$html .= quotation_pdf_section_heading('Shipment details', false)
			. '<div class="ew-shipment-block">'
			. quotation_pdf_all_modes_shipment_html($q, $delivery)
			. '</div>';
		$html .= quotation_pdf_other_details_html($conn, '28%');
	}

	$html .= '</div>';
	$page1 = $html . quotation_pdf_letterhead_close(false);
	$page2_terms = elitewave_pdf_letterhead_css() . quotation_pdf_terms_body_html();

	return array('page1' => $page1, 'page2_terms' => $page2_terms);
}

function quotation_build_pdf_html($conn, $quotation_id)
{
	$pages = quotation_build_pdf_pages($conn, $quotation_id);
	if (!is_array($pages)) {
		return '';
	}
	return $pages['page1'] . '<pagebreak />' . $pages['page2_terms'];
}

/** Write quotation (page 2: terms box includes signatory; brand line below border). */
function quotation_mpdf_write_quotation($mpdf, $conn, $quotation_id)
{
	$pages = quotation_build_pdf_pages($conn, $quotation_id);
	if (!is_array($pages)) {
		return false;
	}
	quotation_mpdf_write_html($mpdf, $pages['page1']);
	$mpdf->AddPage('', '', '', '', '', 10, 10, 8, 8);
	quotation_mpdf_write_html($mpdf, $pages['page2_terms']);
	quotation_mpdf_write_page2_closing_at_bottom($mpdf);
	return true;
}
