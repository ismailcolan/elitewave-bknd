<?php

require_once __DIR__ . '/credit_note_pdf_builder.php';
require_once __DIR__ . '/ew_mail_config.php';

/**
 * @return array<int, array{email:string,name:string}>
 */
function credit_note_client_recipients($conn, $party_id)
{
	$party_id = (int) $party_id;
	if ($party_id <= 0) {
		return array();
	}
	$q = mysqli_query($conn, "SELECT client_company_name, contact_person, email, email1 FROM client WHERE client_id='$party_id' LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return array();
	}
	$name = trim((string) ($row['client_company_name'] ?? ''));
	if ($name === '') {
		$name = trim((string) ($row['contact_person'] ?? ''));
	}
	if ($name === '') {
		$name = 'Customer';
	}
	$out = array();
	$seen = array();
	foreach (array('email', 'email1') as $col) {
		$addr = trim((string) ($row[$col] ?? ''));
		if ($addr === '' || !filter_var($addr, FILTER_VALIDATE_EMAIL)) {
			continue;
		}
		$key = strtolower($addr);
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$out[] = array('email' => $addr, 'name' => $name);
	}
	return $out;
}

function credit_note_build_client_email_body(array $view)
{
	$text = 'color:#1a1a1a;font-size:16px;line-height:24px;';
	$customer = htmlspecialchars((string) ($view['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
	if ($customer === '' || $customer === '—') {
		$customer = 'Customer';
	}
	$note_no = htmlspecialchars((string) ($view['note_no'] ?? ''), ENT_QUOTES, 'UTF-8');
	$note_date = htmlspecialchars((string) ($view['note_date'] ?? ''), ENT_QUOTES, 'UTF-8');
	$invoice_no = htmlspecialchars((string) ($view['against_invoice_no'] ?? '—'), ENT_QUOTES, 'UTF-8');
	$taxable = htmlspecialchars((string) ($view['taxable_value'] ?? '0.00'), ENT_QUOTES, 'UTF-8');
	$gst = htmlspecialchars((string) ($view['gst_amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8');
	$reverse_gst = htmlspecialchars((string) ($view['reverse_gst_claim'] ?? $view['gst_amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8');
	$total = htmlspecialchars((string) ($view['grand_total'] ?? '0.00'), ENT_QUOTES, 'UTF-8');
	$reason = htmlspecialchars((string) ($view['reason'] ?? '—'), ENT_QUOTES, 'UTF-8');

	return '<p style="' . $text . 'margin:0 0 14px;"><b>Dear ' . $customer . ',</b></p>'
		. '<p style="' . $text . 'margin:0 0 14px;">Please find attached credit note <b>' . $note_no . '</b> dated <b>' . $note_date . '</b>, issued against tax invoice <b>' . $invoice_no . '</b>.</p>'
		. '<table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;font-size:16px;line-height:24px;">'
		. '<tr><td style="padding:6px 12px 6px 0;font-weight:bold;vertical-align:top;">Reason</td><td style="padding:6px 0;vertical-align:top;">' . $reason . '</td></tr>'
		. '<tr><td style="padding:6px 12px 6px 0;font-weight:bold;vertical-align:top;">Credit note amount</td><td style="padding:6px 0;vertical-align:top;">&#8377; ' . $taxable . '</td></tr>'
		. '<tr><td style="padding:6px 12px 6px 0;font-weight:bold;vertical-align:top;">Reverse GST claim</td><td style="padding:6px 0;vertical-align:top;">&#8377; ' . $reverse_gst . '</td></tr>'
		. '<tr><td style="padding:6px 12px 6px 0;font-weight:bold;vertical-align:top;">Total credit</td><td style="padding:6px 0;vertical-align:top;">&#8377; ' . $total . '</td></tr>'
		. '</table>'
		. '<p style="' . $text . 'margin:0 0 14px;">Kindly acknowledge receipt. For any clarification, reply to this email.</p>'
		. '<p style="' . $text . 'margin:0;">Thank you for your business.</p>';
}

/**
 * @return array{ok:bool,message:string,sent_to?:string}
 */
function credit_note_send_client_email($conn, $billing_note_id, $user_id = 0)
{
	ensure_billing_note_tables($conn);
	$billing_note_id = (int) $billing_note_id;
	if ($billing_note_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid credit note.');
	}

	$note = billing_get_credit_note($conn, $billing_note_id);
	if (!$note) {
		return array('ok' => false, 'message' => 'Credit note not found.');
	}
	$st = strtolower(trim((string) ($note['master']['status'] ?? '')));
	if ($st === 'cancelled') {
		return array('ok' => false, 'message' => 'Cancelled credit note cannot be emailed.');
	}
	if ($st !== 'final') {
		return array('ok' => false, 'message' => 'Finalise the credit note before emailing the customer.');
	}

	$party_id = (int) ($note['master']['party_id'] ?? 0);
	$recipients = credit_note_client_recipients($conn, $party_id);
	if (!$recipients) {
		return array('ok' => false, 'message' => 'No valid email on the customer master. Update the client profile and try again.');
	}

	$view = billing_credit_note_fetch_view($conn, $billing_note_id);
	if (isset($view['ok']) && !$view['ok']) {
		return array('ok' => false, 'message' => $view['message'] ?? 'Credit note not found.');
	}
	if (isset($view['ok'])) {
		unset($view['ok']);
	}
	if (empty($view['note_no'])) {
		return array('ok' => false, 'message' => 'Could not load credit note details.');
	}

	$pdf = credit_note_pdf_write_temp_file($conn, $billing_note_id, $user_id);
	if (empty($pdf['ok'])) {
		return array('ok' => false, 'message' => $pdf['message'] ?? 'PDF failed.');
	}

	require_once dirname(__DIR__) . '/appMail.php';

	$subject = 'Credit Note ' . ($view['note_no'] ?? '') . ' | EliteWave360 Logistics';
	$body_inner = credit_note_build_client_email_body($view);
	$attach_name = 'Credit_Note_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $view['note_no'] ?? 'credit_note') . '.pdf';

	$mail_cfg = ew_mail_smtp_options();
	$sent_addrs = array();
	$last_error = '';

	foreach ($recipients as $rcp) {
		$sent_one = sendAppMailWithAttachment(
			$rcp['name'],
			$rcp['email'],
			$subject,
			$body_inner,
			$pdf['path'],
			$attach_name,
			$mail_cfg
		);
		if (!empty($sent_one['ok'])) {
			$sent_addrs[] = $rcp['email'];
		} else {
			$last_error = $sent_one['error'] ?? 'Mail send failed.';
		}
	}

	@unlink($pdf['path']);

	if (!$sent_addrs) {
		return array('ok' => false, 'message' => $last_error !== '' ? $last_error : 'Mail could not be sent.');
	}

	$now = date('Y-m-d H:i:s');
	$sent_to = implode(', ', $sent_addrs);
	$esc_to = mysqli_real_escape_string($conn, $sent_to);
	$user_id = (int) $user_id;
	mysqli_query($conn, "UPDATE billing_note_master SET
		client_email_sent_at='$now',
		client_email_sent_to='$esc_to',
		updated_at='$now',
		updated_by='$user_id'
		WHERE billing_note_id='$billing_note_id' LIMIT 1");

	return array(
		'ok' => true,
		'message' => 'Email sent to ' . $sent_to . '.',
		'sent_to' => $sent_to,
		'sent_at' => $now,
	);
}