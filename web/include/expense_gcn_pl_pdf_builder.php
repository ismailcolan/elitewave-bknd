<?php

require_once __DIR__ . '/expense_gcn_pdf_builder.php';
require_once __DIR__ . '/quotation_pdf_gcn_terms.php';

function expense_gcn_pl_build_pdf_html($conn, array $data)
{
	$title = 'GCN Profit & Loss';
	$html = elitewave_pdf_letterhead_open($conn, $title, 'GCN P&L Statement', true, true);

	$meta = array(
		array('GCN No.', $data['grn_no'] ?? '—'),
		array('Booking date', $data['grn_date'] ?? '—'),
		array('Customer', $data['customer_label'] ?? '—'),
		array('Vendor', $data['vendor_label'] ?? '—'),
		array('Route', $data['route_label'] ?? '—'),
		array('Revenue (₹)', $data['revenue'] ?? $data['customer_revenue'] ?? '0.00'),
		array('Total cost (₹)', $data['total_cost'] ?? '0.00'),
		array('Profit / Loss', $data['profit_amount'] ?? '0.00'),
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

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
	$html .= '<tr>';
	$html .= '<td style="width:33%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">REVENUE</div>'
		. '<div style="font-size:11pt;font-weight:bold;color:#021659;">' . expense_gcn_pdf_esc($data['revenue'] ?? $data['customer_revenue'] ?? '0.00') . '</div></td>';
	$html .= '<td style="width:33%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">TOTAL COST</div>'
		. '<div style="font-size:11pt;font-weight:bold;color:#021659;">' . expense_gcn_pdf_esc($data['total_cost'] ?? '0.00') . '</div></td>';
	$profit_raw = (float) ($data['profit_raw'] ?? 0);
	$profit_color = $profit_raw >= 0 ? '#15803d' : '#dc2626';
	$html .= '<td style="width:34%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">PROFIT / LOSS</div>'
		. '<div style="font-size:11pt;font-weight:bold;color:' . $profit_color . ';">' . expense_gcn_pdf_esc($data['profit_amount'] ?? '0.00') . '</div></td>';
	$html .= '</tr></table>';

	$bd = $data['breakdown'] ?? array();

	$html .= expense_gcn_pl_pdf_section_table(
		'Revenue breakup',
		array('Description', 'Amount (₹)'),
		$bd['revenue_rows'] ?? array(),
		array('label', 'amount')
	);

	$html .= expense_gcn_pl_pdf_section_table(
		'Purchase bills (vendor cost)',
		array('Bill No.', 'Date', 'Vendor', 'Expense / purpose', 'Amount (₹)'),
		$bd['purchase_bill_rows'] ?? array(),
		array('bill_no', 'bill_date', 'vendor_label', 'expense_type', 'amount')
	);

	$html .= expense_gcn_pl_pdf_section_table(
		'Payment vouchers (amount paid)',
		array('Voucher', 'Date', 'Vendor', 'Bill / ref.', 'Expense type', 'Mode', 'Amount (₹)'),
		$bd['payment_rows'] ?? array(),
		array('voucher_no', 'voucher_date', 'vendor_label', 'reference', 'expense_type', 'payment_mode', 'amount')
	);

	$html .= expense_gcn_pl_pdf_section_table(
		'Petty cash expenses',
		array('Voucher', 'Date', 'Expense type', 'Paid to', 'Remarks', 'Amount (₹)'),
		$bd['petty_cash_rows'] ?? array(),
		array('voucher_no', 'voucher_date', 'expense_type', 'paid_to', 'remarks', 'amount')
	);

	$html .= '<p style="margin:12px 0 6px;font-size:9pt;font-weight:bold;color:#021659;">Summary</p>';
	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">';
	$html .= '<thead><tr>' . expense_gcn_pdf_th('Particular') . expense_gcn_pdf_th('Amount (₹)', 'right') . '</tr></thead><tbody>';
	$summary = array(
		array('Vendor cost (purchase bills)', $data['bill_vendor_cost'] ?? '0.00'),
		array('Vendor cost (direct)', $data['direct_vendor_cost'] ?? '0.00'),
		array('Petty cash', $data['petty_cash'] ?? '0.00'),
		array('Total cost', $data['total_cost'] ?? '0.00'),
		array('Vendor paid', $data['vendor_paid'] ?? '0.00'),
		array('Vendor balance payable', $data['vendor_balance'] ?? '0.00'),
	);
	foreach ($summary as $idx => $row) {
		$bold = ($idx >= count($summary) - 2);
		$html .= '<tr>';
		$html .= expense_gcn_pdf_td($row[0], 'left', $bold);
		$html .= expense_gcn_pdf_td($row[1], 'right', $bold);
		$html .= '</tr>';
	}
	$html .= '</tbody></table>';

	$html .= elitewave_pdf_letterhead_finish_with_signatory();
	return $html;
}

function expense_gcn_pl_pdf_section_table($title, array $headers, array $rows, array $keys)
{
	$html = '<p style="margin:14px 0 6px;font-size:9pt;font-weight:bold;color:#021659;">' . expense_gcn_pdf_esc($title) . '</p>';
	if (empty($rows)) {
		return $html . '<p style="margin:0 0 8px;font-size:8.5pt;color:#64748b;">No entries.</p>';
	}
	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 10px;">';
	$html .= '<tr>';
	foreach ($headers as $h) {
		$html .= expense_gcn_pdf_th($h);
	}
	$html .= '</tr>';
	$last_key = end($keys);
	foreach ($rows as $row) {
		$html .= '<tr>';
		foreach ($keys as $k) {
			$align = ($k === $last_key) ? 'right' : 'left';
			$html .= expense_gcn_pdf_td($row[$k] ?? '—', $align);
		}
		$html .= '</tr>';
	}
	$html .= '</table>';
	return $html;
}