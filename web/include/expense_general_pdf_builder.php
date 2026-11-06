<?php

require_once __DIR__ . '/elitewave_pdf_letterhead.php';
require_once __DIR__ . '/expense_gcn_pdf_builder.php';
require_once __DIR__ . '/quotation_pdf_gcn_terms.php';

function expense_general_build_pdf_html($conn, array $view)
{
	$title = 'General Expense';
	$html = elitewave_pdf_letterhead_open($conn, $title, 'General Expense Voucher', true, true);

	$meta = array(
		array('Expense No', $view['expense_no'] ?? '—'),
		array('Expense date', $view['expense_date'] ?? '—'),
		array('Vendor', $view['vendor_label'] ?? '—'),
		array('Expense type', $view['category_label'] ?? '—'),
		array('Payment mode', $view['payment_mode_label'] ?? ($view['payment_mode'] ?? '—')),
		array('Last updated', $view['updated_at'] ?? '—'),
	);

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

	if (!empty($view['description'])) {
		$html .= '<div style="margin:0 0 12px;padding:10px 12px;border:1px solid #e2e8f0;background:#f8fafc;">'
			. '<div style="font-size:7.5pt;font-weight:bold;color:#64748b;text-transform:uppercase;margin:0 0 6px;">Description</div>'
			. '<div style="font-size:9.5pt;color:#0f172a;line-height:1.45;">' . expense_gcn_pdf_esc($view['description']) . '</div>'
			. '</div>';
	}

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
	$html .= '<thead><tr>';
	$html .= expense_gcn_pdf_th('Particular', 'left');
	$html .= expense_gcn_pdf_th('Amount (₹)', 'right');
	$html .= '</tr></thead><tbody>';
	$rows = array(
		array('Expense amount', $view['expense_amount'] ?? '0.00'),
		array('GST (' . ($view['gst_percent_display'] ?? '0%') . ')', $view['gst_amount'] ?? '0.00'),
		array('TDS', $view['tds_amount'] ?? '0.00'),
		array('Other deduction', $view['other_deduction'] ?? '0.00'),
		array('Net payable', $view['net_payable'] ?? '0.00'),
	);
	foreach ($rows as $idx => $row) {
		$bold = ($idx === count($rows) - 1);
		$html .= '<tr>';
		$html .= expense_gcn_pdf_td($row[0], 'left', $bold);
		$html .= expense_gcn_pdf_td($row[1], 'right', $bold);
		$html .= '</tr>';
	}
	$html .= '</tbody></table>';

	$bank_name = trim((string) ($view['payment_bank_name'] ?? ''));
	$ifsc = trim((string) ($view['payment_ifsc'] ?? ''));
	$branch = trim((string) ($view['payment_bank_branch'] ?? ''));
	if ($bank_name !== '' || $ifsc !== '' || $branch !== '') {
		$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">';
		$html .= '<tr>';
		$html .= expense_gcn_pdf_meta_cell('Bank name', $bank_name !== '' ? $bank_name : '—');
		$html .= expense_gcn_pdf_meta_cell('IFSC', $ifsc !== '' ? $ifsc : '—');
		$html .= '</tr><tr>';
		$html .= expense_gcn_pdf_meta_cell('Branch', $branch !== '' ? $branch : '—');
		$html .= expense_gcn_pdf_meta_cell('', '');
		$html .= '</tr></table>';
	}

	$html .= elitewave_pdf_letterhead_close_content();
	$html .= quotation_pdf_terms_signatory_html();
	$html .= quotation_pdf_terms_brand_bar_html();
	$html .= quotation_pdf_terms_brand_footer_html();
	$html .= '</div>';
	return $html;
}
