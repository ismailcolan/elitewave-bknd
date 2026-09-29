<?php

require_once __DIR__ . '/elitewave_pdf_letterhead.php';
require_once __DIR__ . '/quotation_pdf_gcn_terms.php';

define('EW_QUOTE_PDF_NAVY', EW_LH_NAVY);
define('EW_QUOTE_PDF_LINE', EW_LH_LINE);

function quotation_pdf_web_root()
{
	return dirname(__DIR__);
}

function quotation_pdf_letterhead_open($conn)
{
	return elitewave_pdf_letterhead_open($conn, 'Rate Quotation', 'Door-to-Door Transportation', true);
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
	return 'padding:5px 10px;font-size:9.5pt;color:#000;font-weight:bold;vertical-align:top;background:#fff;'
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
	return 'border-collapse:collapse;margin-bottom:6px;border:2px solid ' . EW_QUOTE_PDF_NAVY . ';';
}

function quotation_pdf_section_heading($title)
{
	return '
<div class="ew-doc-h2" style="margin-top:10px;margin-bottom:7px;font-size:10pt;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
}

function quotation_pdf_meta_cell($label, $value)
{
	return '
<td style="width:50%;vertical-align:top;border:0;padding:0 12px 0 0;">
	<div class="ew-doc-label">' . htmlspecialchars($label) . '</div>
	<div class="ew-doc-value">' . $value . '</div>
</td>';
}

function quotation_pdf_shipment_details_html($q, $loading, $dims, $delivery, $mode_label = '')
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
	$ship_rows = array(
		array('Route', $route),
		array('Mode of transport', $mode_h),
		array('Loading / Unloading', $load_unload),
		array('Vehicle', $vehicle_line),
		array('Delivery address', $delivery),
	);
	$last = count($ship_rows) - 1;
	$html = '
<table width="100%" cellpadding="0" cellspacing="0" style="' . quotation_pdf_info_table_style() . '">';
	foreach ($ship_rows as $i => $row) {
		$val_extra = ($row[0] === 'Delivery address') ? 'line-height:1.35;' : '';
		$html .= quotation_pdf_info_row($row[0], $row[1], ($i === $last), '30%', $val_extra);
	}
	$html .= '
</table>';

	return $html;
}

function quotation_pdf_logistics_info_html($q, $pay_terms_label, $insurance_number)
{
	$candidates = array(
		array('CFS / Port / Factory / Warehouse', htmlspecialchars($q['cfs_port_factory'] ?? '', ENT_QUOTES, 'UTF-8')),
		array('Part Number / Article Name / Article Number', htmlspecialchars($q['part_number'] ?? '', ENT_QUOTES, 'UTF-8')),
		array('Quotation approval', htmlspecialchars($q['quotation_approval'] ?? '', ENT_QUOTES, 'UTF-8')),
		array('Freight paid by', htmlspecialchars(quotation_freight_paid_by_label($conn, $q['freight_paid_by'] ?? ''), ENT_QUOTES, 'UTF-8')),
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
	return quotation_pdf_section_heading('Logistics reference')
		. '<table width="100%" cellpadding="0" cellspacing="0" style="' . quotation_pdf_info_table_style() . '">' . $rows . '</table>';
}

function quotation_mpdf_create()
{
	return new \Mpdf\Mpdf(array(
		'mode' => 'utf-8',
		'format' => 'A4',
		'default_font' => 'freesans',
		'margin_left' => 14,
		'margin_right' => 14,
		'margin_top' => 12,
		'margin_bottom' => 14,
		'tempDir' => sys_get_temp_dir(),
	));
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

function quotation_build_pdf_pages($conn, $quotation_id)
{
	require_once __DIR__ . '/quotation_functions.php';
	$q = quotation_get($conn, $quotation_id);
	if (!$q) {
		return null;
	}
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
		$charge_rows .= '
		<tr>
			<td style="padding:5px 0;border-bottom:1px solid ' . EW_QUOTE_PDF_LINE . ';color:#000;font-weight:bold;font-size:10pt;">' . htmlspecialchars($label) . '</td>
			<td style="padding:5px 0;border-bottom:1px solid ' . EW_QUOTE_PDF_LINE . ';text-align:right;color:#000;font-weight:bold;font-size:10pt;">' . htmlspecialchars($disp) . '</td>
		</tr>';
	}

	$gst_pct = rtrim(rtrim(number_format((float) $q['gst_rate'], 2, '.', ''), '0'), '.');
	$gst_amt = quotation_format_money_display($q['gst_amount']);
	$total = quotation_format_money_display($q['total_amount']);
	$subject = htmlspecialchars($q['subject'] ?? '', ENT_QUOTES, 'UTF-8');
	$delivery = nl2br(htmlspecialchars($q['delivery_address'] ?? '', ENT_QUOTES, 'UTF-8'));
	$valid_till = htmlspecialchars($q['valid_till'] ?? '', ENT_QUOTES, 'UTF-8');
	$pay_terms_raw = quotation_payment_terms_label($q['payment_terms'] ?? '', $q['payment_terms_note'] ?? '');
	$insurance_no = quotation_insurance_number_from_lines($lines);

	$party_h = htmlspecialchars($party, ENT_QUOTES, 'UTF-8');
	$attn_h = htmlspecialchars($q['attn_name'] ?? '—', ENT_QUOTES, 'UTF-8');
	$quote_h = htmlspecialchars($q['quote_no'], ENT_QUOTES, 'UTF-8');
	$date_h = htmlspecialchars($q['quote_date'], ENT_QUOTES, 'UTF-8');

	$html = quotation_pdf_letterhead_open($conn);

	$html .= '
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:4px;"><tr>'
	. quotation_pdf_meta_cell('Prepared for', $party_h . ($attn_h !== '—' ? '<br><span class="ew-doc-secondary">Attn: ' . $attn_h . '</span>' : ''))
	. quotation_pdf_meta_cell('Reference', $quote_h . '<br><span class="ew-doc-secondary">Date ' . $date_h
		. ($valid_till !== '' ? ' · Valid till ' . $valid_till : '') . '</span>')
	. '</tr></table>';

	if ($subject !== '') {
		$html .= '
<p style="margin:12px 0 0;font-size:10.5pt;color:#000;"><span style="font-weight:bold;color:' . EW_QUOTE_PDF_NAVY . ';">Subject:</span> <b>' . $subject . '</b></p>';
	}

	$html .= '
<p style="margin:10px 0 8px;font-size:10.5pt;color:#000;font-weight:bold;">Dear Sir / Madam,<br>
<span style="font-weight:normal;">Thank you for the opportunity to serve you. Below is our quotation for your requirement.</span></p>';

	$html .= quotation_pdf_logistics_info_html($q, $pay_terms_raw, $insurance_no);

	$html .= quotation_pdf_section_heading('Shipment details')
		. quotation_pdf_shipment_details_html($q, $loading, $dims, $delivery, $mode_label);

	$html .= '
<div class="ew-doc-h2">Commercial summary</div>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:2px;">
<tr>
	<td style="padding:6px 0;font-size:9.5pt;font-weight:bold;color:' . EW_QUOTE_PDF_NAVY . ';border-bottom:2px solid ' . EW_QUOTE_PDF_NAVY . ';">Description</td>
	<td style="padding:6px 0;font-size:9.5pt;font-weight:bold;color:' . EW_QUOTE_PDF_NAVY . ';text-align:right;border-bottom:2px solid ' . EW_QUOTE_PDF_NAVY . ';width:32%;">Amount</td>
</tr>'
	. $charge_rows
	. '
<tr>
	<td style="padding:6px 0;color:#000;font-weight:bold;font-size:10pt;">GST @ ' . htmlspecialchars($gst_pct) . '%</td>
	<td style="padding:6px 0;text-align:right;color:#000;font-weight:bold;font-size:10pt;">Rs. ' . htmlspecialchars($gst_amt) . ' /-</td>
</tr>
<tr>
	<td style="padding:8px 0 4px;font-size:12pt;font-weight:bold;color:' . EW_QUOTE_PDF_NAVY . ';">Total</td>
	<td style="padding:8px 0 4px;text-align:right;font-size:12pt;font-weight:bold;color:' . EW_QUOTE_PDF_NAVY . ';">Rs. ' . htmlspecialchars($total) . ' /-</td>
</tr>
</table>';

	$page1 = $html . quotation_pdf_letterhead_close(false);
	$page2 = elitewave_pdf_letterhead_css() . quotation_pdf_terms_page_html();

	return array('page1' => $page1, 'page2' => $page2);
}

function quotation_build_pdf_html($conn, $quotation_id)
{
	$pages = quotation_build_pdf_pages($conn, $quotation_id);
	if (!is_array($pages)) {
		return '';
	}
	return $pages['page1'] . '<pagebreak />' . $pages['page2'];
}

/** Write quotation (page 2: terms then Thanks &amp; signatory in document body). */
function quotation_mpdf_write_quotation($mpdf, $conn, $quotation_id)
{
	$pages = quotation_build_pdf_pages($conn, $quotation_id);
	if (!is_array($pages)) {
		return false;
	}
	quotation_mpdf_write_html($mpdf, $pages['page1']);
	$mpdf->AddPage('', '', '', '', '', 14, 14, 10, 14);
	quotation_mpdf_write_html($mpdf, $pages['page2']);
	return true;
}
