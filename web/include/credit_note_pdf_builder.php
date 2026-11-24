<?php

require_once __DIR__ . '/billing_note_functions.php';
require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/elitewave_pdf_letterhead.php';

function credit_note_pdf_esc($s)
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function credit_note_pdf_th($text, $align = 'left')
{
	$navy = EW_LH_NAVY;
	return '<th align="' . $align . '" style="padding:6px 8px;background:' . $navy . ';color:#fff;font-size:9pt;font-weight:bold;border:1px solid ' . $navy . ';">'
		. credit_note_pdf_esc($text) . '</th>';
}

function credit_note_pdf_td($text, $align = 'left', $bold = false)
{
	$fw = $bold ? 'font-weight:bold;' : '';
	return '<td align="' . $align . '" style="padding:6px 8px;border:1px solid #cbd5e1;font-size:9pt;color:#000;' . $fw . '">'
		. credit_note_pdf_esc($text) . '</td>';
}

function credit_note_pdf_meta_cell($label, $value)
{
	$navy = EW_LH_NAVY;
	return '<td style="padding:8px 10px;border:1px solid #e2e8f0;background:#fff;vertical-align:top;width:50%;">'
		. '<div style="font-size:7.5pt;font-weight:bold;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin:0 0 4px;">'
		. credit_note_pdf_esc($label) . '</div>'
		. '<div style="font-size:9.5pt;font-weight:bold;color:' . $navy . ';">'
		. credit_note_pdf_esc($value !== '' && $value !== null ? $value : '—') . '</div>'
		. '</td>';
}

function credit_note_pdf_fetch_view($conn, $billing_note_id)
{
	$billing_note_id = (int) $billing_note_id;
	$view = billing_credit_note_fetch_view($conn, $billing_note_id);
	if (empty($view['ok'])) {
		return $view;
	}
	unset($view['ok']);

	$note = billing_get_credit_note($conn, $billing_note_id);
	if (!$note) {
		return array('ok' => false, 'message' => 'Credit note not found.');
	}
	$m = $note['master'];
	$party_id = (int) ($m['party_id'] ?? 0);
	$client = $party_id > 0 ? get_client_info($conn, $party_id) : array();
	$client_name = $view['customer_name'] ?? '';
	if ($client_name === '' || $client_name === '—') {
		$client_name = function_exists('get_client_name') ? get_client_name($conn, $party_id) : '';
	}
	$client_addr = '';
	if (!empty($client) && function_exists('ew_format_party_address_invoice_html')) {
		$client_addr = strip_tags(ew_format_party_address_invoice_html($client, $conn));
		$client_addr = preg_replace('/\s+/', ' ', trim($client_addr));
	}
	$client_gst = strtoupper(trim((string) ($client['gst_no'] ?? '')));

	$invoice_date = '—';
	$invoice_id = (int) ($m['against_invoice_id'] ?? 0);
	if ($invoice_id > 0) {
		$snap = billing_credit_note_invoice_snapshot($conn, $invoice_id, $billing_note_id);
		if ($snap && !empty($snap['invoice_date'])) {
			$invoice_date = $snap['invoice_date'];
		}
	}

	$gst_pct = '';
	if ((float) ($m['gst_rate'] ?? 0) > 0) {
		$gst_pct = number_format((float) $m['gst_rate'] * 100, 2) . '%';
	}

	$view['status'] = $m['status'] ?? 'draft';
	$view['invoice_date'] = $invoice_date;
	$view['party_name'] = $client_name;
	$view['party_address'] = $client_addr;
	$view['party_gstin'] = $client_gst;
	$resolved = billing_credit_note_resolve_amounts($conn, $m);
	$view['taxable_value'] = $resolved['taxable_value'] ?? $view['taxable_value'];
	$view['taxable_value_raw'] = (float) ($resolved['taxable_value'] ?? $m['taxable_value'] ?? 0);
	$view['cgst_amount'] = $resolved['cgst_amount'] ?? billing_format_money(0);
	$view['sgst_amount'] = $resolved['sgst_amount'] ?? billing_format_money(0);
	$view['igst_amount'] = $resolved['igst_amount'] ?? billing_format_money(0);
	$view['gst_amount'] = $resolved['gst_amount'] ?? $view['gst_amount'];
	$view['grand_total'] = $resolved['grand_total'] ?? $view['grand_total'];
	$view['reverse_gst_claim'] = billing_format_reverse_gst_claim_display(
		(float) ($resolved['gst_amount'] ?? 0),
		(float) ($resolved['cgst_amount'] ?? 0),
		(float) ($resolved['sgst_amount'] ?? 0),
		(float) ($resolved['igst_amount'] ?? 0)
	);
	$invoice_profile = array();
	if ($invoice_id > 0) {
		$inv = billing_get_invoice($conn, $invoice_id);
		if ($inv) {
			$invoice_profile = billing_invoice_gst_profile($inv['master']);
			$view['gst_label'] = $invoice_profile['gst_label'] ?? $view['gst_label'];
		}
	}
	$view['gst_rate_label'] = $gst_pct;
	if (!empty($invoice_profile['gst_rate'])) {
		$view['gst_rate_label'] = number_format((float) $invoice_profile['gst_rate'] * 100, 2) . '%';
	}
	$view['gst_split'] = $invoice_profile['gst_split'] ?? ($m['gst_split'] ?? '');
	$view['grn_no'] = '';
	if (!empty($note['lines'][0]['grn_no'])) {
		$view['grn_no'] = $note['lines'][0]['grn_no'];
	}
	$view['lines'] = $note['lines'];

	return array('ok' => true) + $view;
}

function credit_note_build_pdf_html($conn, array $view)
{
	$title = 'Credit Note';
	$html = elitewave_pdf_letterhead_open($conn, $title, 'Original for Recipient', true, true);

	$status = strtolower(trim((string) ($view['status'] ?? 'draft')));
	if ($status === 'cancelled') {
		$html .= '<div style="margin:0 0 12px;padding:10px 12px;border:2px solid #b91c1c;background:#fef2f2;color:#991b1b;font-size:10pt;font-weight:bold;text-align:center;">'
			. 'CANCELLED — This credit note is void.</div>';
	} elseif ($status === 'draft') {
		$html .= '<div style="margin:0 0 12px;padding:10px 12px;border:2px dashed #d97706;background:#fffbeb;color:#92400e;font-size:10pt;font-weight:bold;text-align:center;">'
			. 'DRAFT — Not valid until finalised.</div>';
	}

	$meta = array(
		array('Credit note no', $view['note_no'] ?? '—'),
		array('Credit note date', $view['note_date'] ?? '—'),
		array('Against tax invoice', $view['against_invoice_no'] ?? '—'),
		array('Invoice date', $view['invoice_date'] ?? '—'),
		array('Customer', $view['party_name'] ?? '—'),
		array('Customer GSTIN', $view['party_gstin'] ?? '—'),
		array('Reason', $view['reason'] ?? '—'),
		array('GST (invoice)', $view['gst_label'] ?? '—'),
	);
	if (!empty($view['grn_no'])) {
		$meta[] = array('GCN no', $view['grn_no']);
	}

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 12px;">';
	$half = (int) ceil(count($meta) / 2);
	for ($i = 0; $i < $half; $i++) {
		$left = $meta[$i] ?? array('', '');
		$right = $meta[$i + $half] ?? array('', '');
		$html .= '<tr>';
		$html .= credit_note_pdf_meta_cell($left[0] ?? '', $left[1] ?? '');
		$html .= credit_note_pdf_meta_cell($right[0] ?? '', $right[1] ?? '');
		$html .= '</tr>';
	}
	$html .= '</table>';

	if (!empty($view['party_address'])) {
		$html .= '<p style="margin:0 0 12px;font-size:9pt;color:#334155;"><strong>Customer address:</strong> '
			. credit_note_pdf_esc($view['party_address']) . '</p>';
	}

	$desc = trim((string) ($view['line_description'] ?? ''));
	if ($desc !== '') {
		$html .= '<p class="ew-doc-h2" style="margin-top:0;">Description</p>';
		$html .= '<p style="margin:0 0 14px;font-size:9.5pt;line-height:1.45;">' . credit_note_pdf_esc($desc) . '</p>';
	}

	$html .= '<p class="ew-doc-h2" style="margin-top:0;">Amount details</p>';
	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
	$html .= '<tr>'
		. credit_note_pdf_th('Particulars')
		. credit_note_pdf_th('Taxable value', 'right')
		. credit_note_pdf_th('CGST', 'right')
		. credit_note_pdf_th('SGST', 'right')
		. credit_note_pdf_th('IGST', 'right')
		. credit_note_pdf_th('Total', 'right')
		. '</tr>';
	$html .= '<tr>';
	$html .= credit_note_pdf_td('Credit note adjustment');
	$html .= credit_note_pdf_td($view['taxable_value'] ?? '0.00', 'right');
	$html .= credit_note_pdf_td($view['cgst_amount'] ?? '0.00', 'right');
	$html .= credit_note_pdf_td($view['sgst_amount'] ?? '0.00', 'right');
	$html .= credit_note_pdf_td($view['igst_amount'] ?? '0.00', 'right');
	$html .= credit_note_pdf_td($view['grand_total'] ?? '0.00', 'right', true);
	$html .= '</tr>';
	$html .= '</table>';

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 14px;">';
	$html .= '<tr>';
	$html .= '<td style="width:33%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">CREDIT NOTE AMOUNT</div>'
		. '<div style="font-size:11pt;font-weight:bold;color:#021659;">' . credit_note_pdf_esc($view['taxable_value'] ?? '0.00') . '</div></td>';
	$html .= '<td style="width:33%;padding:10px;border:1px solid #cbd5e1;background:#f8fafc;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">REVERSE GST CLAIM</div>'
		. '<div style="font-size:10pt;font-weight:bold;color:#021659;line-height:1.35;">' . credit_note_pdf_esc($view['reverse_gst_claim'] ?? $view['gst_amount'] ?? '0.00') . '</div></td>';
	$html .= '<td style="width:34%;padding:10px;border:1px solid #cbd5e1;background:#ecfdf5;text-align:center;">'
		. '<div style="font-size:8pt;color:#64748b;font-weight:bold;">TOTAL CREDIT</div>'
		. '<div style="font-size:11pt;font-weight:bold;color:#15803d;">' . credit_note_pdf_esc($view['grand_total'] ?? '0.00') . '</div></td>';
	$html .= '</tr></table>';

	if (!empty($view['total_words'])) {
		$html .= '<p style="margin:0 0 8px;font-size:9.5pt;"><strong>Amount in words:</strong> '
			. credit_note_pdf_esc($view['total_words']) . '</p>';
	}

	$html .= '<p style="margin:12px 0 0;font-size:8.5pt;color:#64748b;">'
		. 'This is a computer-generated credit note issued against the tax invoice referenced above. '
		. 'GST credit is as per applicable law and your original invoice GST split.</p>';

	$html .= elitewave_pdf_letterhead_close();
	return $html;
}

/**
 * Write credit note PDF to disk (for email attachment).
 *
 * @return array{ok:bool,message?:string,path?:string,note_no?:string}
 */
function credit_note_pdf_write_temp_file($conn, $billing_note_id, $user_id = 0)
{
	$billing_note_id = (int) $billing_note_id;
	billing_credit_note_sync_tax_from_invoice($conn, $billing_note_id, (int) $user_id);
	$viewWrap = credit_note_pdf_fetch_view($conn, $billing_note_id);
	if (empty($viewWrap['ok'])) {
		return array('ok' => false, 'message' => $viewWrap['message'] ?? 'Credit note not found.');
	}
	unset($viewWrap['ok']);
	$view = $viewWrap;

	$autoload = dirname(__DIR__) . '/vendor/autoload.php';
	if (!is_file($autoload)) {
		return array('ok' => false, 'message' => 'PDF library is not available.');
	}
	require_once $autoload;

	$html = credit_note_build_pdf_html($conn, $view);
	if ($html === '') {
		return array('ok' => false, 'message' => 'Could not build credit note PDF.');
	}

	$dir = sys_get_temp_dir() . '/ew360_credit_note_pdf';
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	$note_no = (string) ($view['note_no'] ?? ('CN_' . $billing_note_id));
	$safe_no = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $note_no);
	$path = $dir . '/credit_note_' . $billing_note_id . '_' . $safe_no . '.pdf';

	try {
		$mpdf = new \Mpdf\Mpdf(array(
			'mode' => 'utf-8',
			'format' => 'A4',
			'default_font' => 'freesans',
			'margin_left' => 8,
			'margin_right' => 8,
			'margin_top' => 8,
			'margin_bottom' => 12,
		));
		$mpdf->SetTitle('Credit Note — ' . $note_no);
		$mpdf->SetAuthor('EliteWave360 Logistics');

		$status = strtolower(trim((string) ($view['status'] ?? '')));
		if ($status === 'cancelled') {
			$wm = dirname(__DIR__) . '/images/pdf/cancel2.png';
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
		$mpdf->Output($path, 'F');
	} catch (Exception $e) {
		return array('ok' => false, 'message' => 'PDF generation failed.');
	}

	if (!is_file($path)) {
		return array('ok' => false, 'message' => 'PDF file was not created.');
	}

	return array('ok' => true, 'path' => $path, 'note_no' => $note_no);
}