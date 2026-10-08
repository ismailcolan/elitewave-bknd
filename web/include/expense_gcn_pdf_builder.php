<?php

require_once __DIR__ . '/elitewave_pdf_letterhead.php';
require_once __DIR__ . '/quotation_pdf_gcn_terms.php';

function expense_gcn_pdf_esc($s)
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function expense_gcn_pdf_th($text, $align = 'left')
{
	$navy = EW_LH_NAVY;
	return '<th align="' . $align . '" style="padding:6px 8px;background:' . $navy . ';color:#fff;font-size:9pt;font-weight:bold;border:1px solid ' . $navy . ';">'
		. expense_gcn_pdf_esc($text) . '</th>';
}

function expense_gcn_pdf_td($text, $align = 'left', $bold = false)
{
	$fw = $bold ? 'font-weight:bold;' : '';
	return '<td align="' . $align . '" style="padding:6px 8px;border:1px solid #cbd5e1;font-size:9pt;color:#000;' . $fw . '">'
		. expense_gcn_pdf_esc($text) . '</td>';
}

function expense_gcn_pdf_meta_cell($label, $value)
{
	if ($label === '' && ($value === '' || $value === null)) {
		return '<td style="padding:8px 10px;border:1px solid #e2e8f0;background:#fff;vertical-align:top;">&nbsp;</td>';
	}
	$navy = EW_LH_NAVY;

	return '<td style="padding:8px 10px;border:1px solid #e2e8f0;background:#fff;vertical-align:top;width:50%;">'
		. '<div style="font-size:7.5pt;font-weight:bold;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin:0 0 4px;line-height:1.2;">'
		. expense_gcn_pdf_esc($label) . '</div>'
		. '<div style="font-size:9.5pt;font-weight:bold;color:' . $navy . ';line-height:1.35;">'
		. expense_gcn_pdf_esc($value !== '' && $value !== null ? $value : '—') . '</div>'
		. '</td>';
}

function expense_gcn_build_pdf_html($conn, array $view)
{
	$title = 'GCN Expense Summary';
	$html = elitewave_pdf_letterhead_open($conn, $title, 'GCN Expense Copy', true, true);

	$modeLabel = $view['expense_mode'] === 'group' ? 'Group' : 'Single';
	$meta = array(
		array('Type', $modeLabel),
		array('GCN No', $view['grn_no'] ?? '—'),
		array('GCN date', $view['grn_date'] ?? '—'),
		array('Route', $view['route_label'] ?? '—'),
		array('Mode of transport', $view['mode_of_transport'] ?? '—'),
	);
	if ($view['expense_mode'] !== 'group') {
		$meta[] = array('Consignor', $view['consignor'] ?? '—');
		$meta[] = array('Consignee', $view['consignee'] ?? '—');
		if (!empty($view['invoice_no'])) {
			$meta[] = array('Invoice', $view['invoice_no']);
		}
	}

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 12px;">';
	$half = (int) ceil(count($meta) / 2);
	for ($i = 0; $i < $half; $i++) {
		$left = $meta[$i] ?? array('', '');
		$right = $meta[$i + $half] ?? array('', '');
		$html .= '<tr>';
		$html .= expense_gcn_pdf_meta_cell($left[0] ?? '', $left[1] ?? '');
		$html .= expense_gcn_pdf_meta_cell($right[0] ?? '', $right[1] ?? '');
		$html .= '</tr>';
	}
	$html .= '</table>';

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
	$html .= '<tr>';
	$html .= '<td style="width:33%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">REVENUE</div>'
		. '<div style="font-size:11pt;font-weight:bold;color:#021659;">' . expense_gcn_pdf_esc($view['revenue_without_gst'] ?? '0.00') . '</div></td>';
	$html .= '<td style="width:33%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">EXPENSES</div>'
		. '<div style="font-size:11pt;font-weight:bold;color:#021659;">' . expense_gcn_pdf_esc($view['expenses_without_gst'] ?? '0.00') . '</div></td>';
	$profit = $view['profit_amount'] ?? '0.00';
	$profit_color = ((float) ($view['profit_raw'] ?? 0) >= 0) ? '#15803d' : '#dc2626';
	$html .= '<td style="width:34%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">PROFIT</div>'
		. '<div style="font-size:11pt;font-weight:bold;color:' . $profit_color . ';">' . expense_gcn_pdf_esc($profit) . '</div></td>';
	$html .= '</tr></table>';

	$html .= '<p class="ew-doc-h2" style="margin-top:0;">Revenue breakup</p>';
	if ($view['expense_mode'] === 'group' && !empty($view['gcns'])) {
		$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
		$html .= '<tr>' . expense_gcn_pdf_th('GCN No') . expense_gcn_pdf_th('Date') . expense_gcn_pdf_th('Route')
			. expense_gcn_pdf_th('Mode') . expense_gcn_pdf_th('Revenue', 'right') . '</tr>';
		foreach ($view['gcns'] as $g) {
			$html .= '<tr>';
			$html .= expense_gcn_pdf_td($g['grn_no'] ?? '');
			$html .= expense_gcn_pdf_td($g['grn_date'] ?? '');
			$html .= expense_gcn_pdf_td($g['route_label'] ?? '');
			$html .= expense_gcn_pdf_td($g['mode_of_transport'] ?? '—');
			$html .= expense_gcn_pdf_td($g['revenue_without_gst'] ?? '', 'right');
			$html .= '</tr>';
		}
		$html .= '</table>';
	} elseif (!empty($view['revenue_breakup'])) {
		$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
		$html .= '<tr>' . expense_gcn_pdf_th('Description') . expense_gcn_pdf_th('Amount (₹)', 'right') . '</tr>';
		foreach ($view['revenue_breakup'] as $row) {
			$bold = !empty($row['is_total']);
			$html .= '<tr>' . expense_gcn_pdf_td($row['label'] ?? '', 'left', $bold)
				. expense_gcn_pdf_td($row['amount'] ?? '', 'right', $bold) . '</tr>';
		}
		$html .= '</table>';
	} else {
		$html .= '<p style="font-size:9pt;color:#64748b;">No revenue details.</p>';
	}

	$html .= '<p class="ew-doc-h2">Expense breakup</p>';
	$lines = $view['lines'] ?? array();
	if ($lines === array()) {
		$html .= '<p style="font-size:9pt;color:#64748b;">No expense lines recorded.</p>';
	} else {
		$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">';
		$html .= '<tr>' . expense_gcn_pdf_th('#') . expense_gcn_pdf_th('Date') . expense_gcn_pdf_th('Vendor')
			. expense_gcn_pdf_th('Category') . expense_gcn_pdf_th('Amount', 'right')
			. expense_gcn_pdf_th('GST', 'right') . expense_gcn_pdf_th('TDS', 'right') . '</tr>';
		foreach ($lines as $i => $ln) {
			$html .= '<tr>';
			$html .= expense_gcn_pdf_td($ln['line_no'] ?? ($i + 1));
			$html .= expense_gcn_pdf_td($ln['expense_date_display'] ?? ($ln['expense_date'] ?? ''));
			$html .= expense_gcn_pdf_td($ln['vendor_label'] ?? '');
			$html .= expense_gcn_pdf_td($ln['category_label'] ?? '');
			$html .= expense_gcn_pdf_td($ln['expense_amount'] ?? '', 'right');
			$html .= expense_gcn_pdf_td($ln['gst_amount'] ?? '', 'right');
			$html .= expense_gcn_pdf_td($ln['tds_amount'] ?? '', 'right');
			$html .= '</tr>';
		}
		$html .= '</table>';
	}

	$html .= elitewave_pdf_letterhead_close_content();
	$html .= quotation_pdf_terms_signatory_html();
	$html .= quotation_pdf_terms_brand_bar_html();
	$html .= quotation_pdf_terms_brand_footer_html();
	$html .= '</div>';
	return $html;
}
