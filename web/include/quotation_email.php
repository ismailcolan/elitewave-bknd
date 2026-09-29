<?php

require_once __DIR__ . '/quotation_functions.php';
require_once __DIR__ . '/quotation_pdf_builder.php';

function quotation_pdf_write_temp_file($conn, $quotation_id)
{
	$quotation_id = (int) $quotation_id;
	$row = quotation_get($conn, $quotation_id);
	if (!$row) {
		return array('ok' => false, 'message' => 'Quotation not found.');
	}

	$autoload = dirname(__DIR__) . '/vendor/autoload.php';
	if (!is_file($autoload)) {
		return array('ok' => false, 'message' => 'PDF library is not available.');
	}
	require_once $autoload;

	$dir = sys_get_temp_dir() . '/ew360_quotation_pdf';
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	$safe_no = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $row['quote_no']);
	$path = $dir . '/quotation_' . $quotation_id . '_' . $safe_no . '.pdf';

	try {
		$mpdf = quotation_mpdf_create();
		if (!quotation_mpdf_write_quotation($mpdf, $conn, $quotation_id)) {
			return array('ok' => false, 'message' => 'Could not build quotation PDF.');
		}
		$mpdf->Output($path, 'F');
	} catch (Exception $e) {
		return array('ok' => false, 'message' => 'PDF generation failed.');
	}

	if (!is_file($path)) {
		return array('ok' => false, 'message' => 'PDF file was not created.');
	}

	return array('ok' => true, 'path' => $path, 'quote_no' => $row['quote_no']);
}

function quotation_build_approval_email_body($conn, $row)
{
	$name = quotation_recipient_name($conn, $row);
	$attn = trim((string) ($row['attn_name'] ?? ''));
	$greet = $attn !== '' ? htmlspecialchars($attn, ENT_QUOTES, 'UTF-8') : 'Sir / Madam';
	$origin = htmlspecialchars($row['origin_text'] ?? '', ENT_QUOTES, 'UTF-8');
	$unload = htmlspecialchars($row['unloading_at'] ?? '', ENT_QUOTES, 'UTF-8');
	$mode = htmlspecialchars(quotation_mode_of_transport_label($conn, (int) ($row['mode_of_transportation'] ?? 0)), ENT_QUOTES, 'UTF-8');
	$total = quotation_format_money_display($row['total_amount'] ?? 0);
	$quote_no = htmlspecialchars($row['quote_no'] ?? '', ENT_QUOTES, 'UTF-8');
	$quote_date = htmlspecialchars($row['quote_date'] ?? '', ENT_QUOTES, 'UTF-8');
	$valid = htmlspecialchars($row['valid_till'] ?? '', ENT_QUOTES, 'UTF-8');
	$company = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

	$text = 'color:#1a1a1a;font-size:16px;line-height:24px;';
	$route = $origin . ' &rarr; ' . $unload;
	$valid_phrase = $valid !== '' ? ', valid till <b>' . $valid . '</b>' : '';

	$summary_rows = ''
		. '<tr><td style="padding:6px 12px 6px 0;color:#1a1a1a;font-weight:bold;vertical-align:top;">Route</td>'
		. '<td style="padding:6px 0;color:#1a1a1a;vertical-align:top;">' . $route . '</td></tr>';
	if ($mode !== '') {
		$summary_rows .= '<tr><td style="padding:6px 12px 6px 0;color:#1a1a1a;font-weight:bold;vertical-align:top;">Mode of transport</td>'
			. '<td style="padding:6px 0;color:#1a1a1a;vertical-align:top;">' . $mode . '</td></tr>';
	}
	$summary_rows .= '<tr><td style="padding:6px 12px 6px 0;color:#1a1a1a;font-weight:bold;vertical-align:top;">Total amount</td>'
		. '<td style="padding:6px 0;color:#1a1a1a;vertical-align:top;">&#8377; ' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . ' /- (inclusive of applicable taxes as per attachment)</td></tr>';

	return '<p style="' . $text . 'margin:0 0 14px;"><b>Dear ' . $greet . ',</b></p>'
		. '<p style="' . $text . 'margin:0 0 14px;">Thank you for considering <b>EliteWave360 Logistics</b> for your transportation requirement.</p>'
		. '<p style="' . $text . 'margin:0 0 14px;">Please find attached our <b>Door-to-Door Rate Quotation</b> <b>' . $quote_no . '</b>, dated <b>' . $quote_date . '</b>'
		. $valid_phrase . ', prepared for <b>' . $company . '</b>.</p>'
		. '<p style="' . $text . 'margin:0 0 8px;font-weight:bold;">Quotation summary</p>'
		. '<table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;font-size:16px;line-height:24px;">' . $summary_rows . '</table>'
		. '<p style="' . $text . 'margin:0 0 14px;">The attached PDF contains full shipment details, commercial terms, and standard conditions. We request you to review the quotation and share your confirmation at your earliest convenience. If you need any revision to route, vehicle type, or charges, we will be glad to assist.</p>'
		. '<p style="' . $text . 'margin:0 0 14px;">For any queries regarding this quotation, please reply to this email.</p>'
		. '<p style="' . $text . 'margin:0;">Thank you for your business.</p>';
}

function quotation_send_approved_email($conn, $row)
{
	if (!is_array($row) || empty($row['quotation_id'])) {
		return array('ok' => false, 'message' => 'Invalid quotation.');
	}

	$email = trim((string) ($row['party_email'] ?? ''));
	if ($email === '') {
		return array('ok' => false, 'message' => 'No email on quotation.');
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return array('ok' => false, 'message' => 'Email address is not valid.');
	}

	$pdf = quotation_pdf_write_temp_file($conn, (int) $row['quotation_id']);
	if (empty($pdf['ok'])) {
		return array('ok' => false, 'message' => $pdf['message'] ?? 'PDF failed.');
	}

	require_once dirname(__DIR__) . '/appMail.php';

	$to_name = quotation_recipient_name($conn, $row);
	$subject = 'Rate Quotation ' . ($row['quote_no'] ?? '') . ' | EliteWave360 Logistics';
	$body = quotation_build_approval_email_body($conn, $row);

	$attach_name = 'Rate_Quotation_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $row['quote_no'] ?? 'quote') . '.pdf';
	$sent = sendAppMailWithAttachment($to_name, $email, $subject, $body, $pdf['path'], $attach_name);
	@unlink($pdf['path']);

	if (empty($sent['ok'])) {
		return array('ok' => false, 'message' => $sent['error'] ?? 'Mail send failed.');
	}

	return array('ok' => true, 'message' => 'Email sent to ' . $email . '.');
}
