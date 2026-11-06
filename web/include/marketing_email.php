<?php

/**
 * Marketing bulk email helpers — image upload, HTML body, per-client send, send history.
 */

function marketing_bulk_email_ensure_tables($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS marketing_bulk_email_log (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		subject VARCHAR(255) NOT NULL DEFAULT '',
		message TEXT NULL,
		image_path VARCHAR(500) NOT NULL DEFAULT '',
		image_url VARCHAR(500) NOT NULL DEFAULT '',
		sent_count INT NOT NULL DEFAULT 0,
		failed_count INT NOT NULL DEFAULT 0,
		recipient_count INT NOT NULL DEFAULT 0,
		recipients_json MEDIUMTEXT NULL,
		errors_json MEDIUMTEXT NULL,
		created_by INT NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		KEY idx_mkt_bulk_created (created_at)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * @return array<int, array<string, mixed>>
 */
function marketing_bulk_email_list_rows($conn, $limit = 200)
{
	marketing_bulk_email_ensure_tables($conn);
	$limit = max(1, min(500, (int) $limit));
	$rows = array();
	$q = mysqli_query(
		$conn,
		"SELECT id, subject, message, image_path, image_url, sent_count, failed_count,
		        recipient_count, recipients_json, errors_json, created_by, created_at
		 FROM marketing_bulk_email_log
		 ORDER BY id DESC
		 LIMIT {$limit}"
	);
	if (!$q) {
		return $rows;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$rows[] = $row;
	}
	return $rows;
}

/**
 * Persist one bulk send attempt for the history list.
 */
function marketing_bulk_email_log_send($conn, $payload)
{
	marketing_bulk_email_ensure_tables($conn);

	$subject = mysqli_real_escape_string($conn, (string) ($payload['subject'] ?? ''));
	$message = mysqli_real_escape_string($conn, (string) ($payload['message'] ?? ''));
	$image_path = mysqli_real_escape_string($conn, (string) ($payload['image_path'] ?? ''));
	$image_url = mysqli_real_escape_string($conn, (string) ($payload['image_url'] ?? ''));
	$sent = (int) ($payload['sent'] ?? 0);
	$failed = (int) ($payload['failed'] ?? 0);
	$recipient_count = (int) ($payload['recipient_count'] ?? 0);
	$recipients_json = mysqli_real_escape_string($conn, (string) ($payload['recipients_json'] ?? '[]'));
	$errors_json = mysqli_real_escape_string($conn, (string) ($payload['errors_json'] ?? '[]'));
	$created_by = (int) ($payload['created_by'] ?? 0);
	$created_at = mysqli_real_escape_string($conn, date('Y-m-d H:i:s'));

	$sql = "INSERT INTO marketing_bulk_email_log
		(subject, message, image_path, image_url, sent_count, failed_count, recipient_count,
		 recipients_json, errors_json, created_by, created_at)
		VALUES
		('{$subject}', '{$message}', '{$image_path}', '{$image_url}', {$sent}, {$failed}, {$recipient_count},
		 '{$recipients_json}', '{$errors_json}', {$created_by}, '{$created_at}')";
	if (!mysqli_query($conn, $sql)) {
		return 0;
	}
	return (int) mysqli_insert_id($conn);
}

/** SMTP settings for marketing bulk emails (same mailbox as quotation customer mail). */
function marketing_default_cc_email()
{
	return 'athar@elitewave360.in';
}

/**
 * Parse a CC string (comma / semicolon separated) into PHPMailer CC rows.
 *
 * @param string $cc_raw
 * @return array<int, array{0:string,1:string}>
 */
function marketing_parse_cc_emails($cc_raw)
{
	$cc_raw = trim((string) $cc_raw);
	if ($cc_raw === '') {
		$cc_raw = marketing_default_cc_email();
	}
	$parts = preg_split('/[,;]+/', $cc_raw);
	$rows = array();
	$seen = array();
	foreach ((array) $parts as $part) {
		$email = strtolower(trim((string) $part));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			continue;
		}
		if (isset($seen[$email])) {
			continue;
		}
		$seen[$email] = true;
		$rows[] = array($email, '');
	}
	if (empty($rows)) {
		$rows[] = array(marketing_default_cc_email(), 'Mohammed Athar');
	}
	return $rows;
}

function marketing_mail_smtp_options($cc_raw = '')
{
	return array(
		'username' => 'athar@elitewave360.in',
		'password' => 'EliteWave@360#',
		'from' => 'athar@elitewave360.in',
		'from_name' => 'Mohammed Athar | EliteWave360',
		'cc' => marketing_parse_cc_emails($cc_raw),
		// Use full-width HTML shell (not the default 590px centered template).
		'full_width' => true,
	);
}

/**
 * Active clients with a usable primary email, for the compose multi-select.
 *
 * @return array<int, array{id:int,name:string,email:string}>
 */
function marketing_client_recipient_options($conn)
{
	$rows = array();
	$q = mysqli_query(
		$conn,
		"SELECT client_id, client_company_name, email
		 FROM client
		 WHERE status = 0 AND TRIM(IFNULL(email, '')) != ''
		 ORDER BY client_company_name ASC"
	);
	if (!$q) {
		return $rows;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$email = trim((string) ($row['email'] ?? ''));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			continue;
		}
		$raw = (string) ($row['client_company_name'] ?? '');
		$name = trim(ew_client_decrypt_name($raw));
		if ($name === '' && $raw !== '' && strpos($raw, 'EW1:') !== 0) {
			$name = trim($raw);
		}
		if ($name === '') {
			$name = $email;
		}
		if (function_exists('mb_check_encoding') && !mb_check_encoding($name, 'UTF-8')) {
			$name = utf8_encode($name);
		}
		$rows[] = array(
			'id' => (int) $row['client_id'],
			'name' => $name,
			'email' => $email,
		);
	}
	return $rows;
}

/**
 * Validate and store a marketing image upload under web/uploads/marketing/.
 *
 * @param array $file Typically $_FILES['marketing_image']
 * @return array{ok:bool,message?:string,path?:string,url?:string}
 */
function marketing_save_uploaded_image($file)
{
	if (!is_array($file) || empty($file['tmp_name'])) {
		return array('ok' => false, 'message' => 'Upload a marketing image.');
	}
	$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
	if ($error === UPLOAD_ERR_NO_FILE) {
		return array('ok' => false, 'message' => 'Upload a marketing image.');
	}
	if ($error !== UPLOAD_ERR_OK) {
		return array('ok' => false, 'message' => 'Image upload failed. Please try again.');
	}

	$ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
	if (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp'), true)) {
		return array('ok' => false, 'message' => 'Image must be JPG, PNG or WEBP.');
	}
	if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
		return array('ok' => false, 'message' => 'Image must be 5 MB or smaller.');
	}

	$dir = dirname(__DIR__) . '/uploads/marketing';
	if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
		return array('ok' => false, 'message' => 'Could not save the marketing image.');
	}

	$name = 'mkt_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
	$dest = $dir . '/' . $name;
	if (!@move_uploaded_file($file['tmp_name'], $dest)) {
		return array('ok' => false, 'message' => 'Could not save the marketing image.');
	}

	$rel = 'uploads/marketing/' . $name;
	$url = rtrim(site_path, '/') . '/' . $rel;

	return array(
		'ok' => true,
		'path' => $rel,
		'url' => $url,
	);
}

/**
 * Inner HTML for the marketing email (placed inside sendAppMailWithAttachment wrapper).
 */
function marketing_build_email_body($message, $image_url)
{
	$message = trim((string) $message);
	$image_url = trim((string) $image_url);
	$parts = array();

	if ($message !== '') {
		$safe = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
		$parts[] = '<div style="padding:8px 16px 16px;">'
			. '<p style="margin:0;color:#1a1a1a;font-size:16px;line-height:24px;">' . $safe . '</p>'
			. '</div>';
	}

	if ($image_url !== '') {
		$src = htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8');
		$parts[] = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">'
			. '<tr><td align="left" style="padding:0;">'
			. '<img src="' . $src . '" alt="Marketing" width="1200" border="0" '
			. 'style="display:block;width:100%;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;" />'
			. '</td></tr></table>';
	}

	return implode("\n", $parts);
}

/**
 * Resolve selected client IDs to email recipients.
 *
 * @param mysqli $conn
 * @param array $client_ids
 * @return array{recipients:array<int,array{id:int,name:string,email:string}>,errors:array<int,string>}
 */
function marketing_resolve_recipients($conn, $client_ids)
{
	$recipients = array();
	$errors = array();
	$ids = array();
	foreach ((array) $client_ids as $id) {
		$id = (int) $id;
		if ($id > 0) {
			$ids[$id] = $id;
		}
	}
	if (empty($ids)) {
		return array('recipients' => array(), 'errors' => array('Select at least one client.'));
	}

	$id_list = implode(',', array_map('intval', $ids));
	$q = mysqli_query(
		$conn,
		"SELECT client_id, client_company_name, email
		 FROM client
		 WHERE status = 0 AND client_id IN ($id_list)"
	);
	if (!$q) {
		return array('recipients' => array(), 'errors' => array('Could not load selected clients.'));
	}

	$found = array();
	while ($row = mysqli_fetch_assoc($q)) {
		$cid = (int) $row['client_id'];
		$found[$cid] = true;
		$email = trim((string) ($row['email'] ?? ''));
		$raw = (string) ($row['client_company_name'] ?? '');
		$name = trim(ew_client_decrypt_name($raw));
		if ($name === '' && $raw !== '' && strpos($raw, 'EW1:') !== 0) {
			$name = trim($raw);
		}
		if ($name === '') {
			$name = $email !== '' ? $email : ('Client #' . $cid);
		}
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = $name . ': missing or invalid email.';
			continue;
		}
		$recipients[] = array(
			'id' => $cid,
			'name' => $name,
			'email' => $email,
		);
	}

	foreach ($ids as $cid) {
		if (empty($found[$cid])) {
			$errors[] = 'Client #' . $cid . ': not found or inactive.';
		}
	}

	return array('recipients' => $recipients, 'errors' => $errors);
}

/**
 * Send marketing email to each selected client (one message per recipient).
 *
 * @param mysqli $conn
 * @param string $subject
 * @param string $message
 * @param string $image_url Absolute HTTPS URL for the inline marketing image
 * @param array $client_ids
 * @param array $meta Optional: image_path, created_by, cc
 * @return array{ok:bool,message:string,sent:int,failed:int,errors:array<int,string>,log_id?:int}
 */
function marketing_send_bulk($conn, $subject, $message, $image_url, $client_ids, $meta = array())
{
	$subject = trim((string) $subject);
	$image_url = trim((string) $image_url);
	$image_path = trim((string) ($meta['image_path'] ?? ''));
	$created_by = (int) ($meta['created_by'] ?? 0);
	$cc_raw = isset($meta['cc']) ? (string) $meta['cc'] : marketing_default_cc_email();

	if ($subject === '') {
		return array(
			'ok' => false,
			'message' => 'Subject is required.',
			'sent' => 0,
			'failed' => 0,
			'errors' => array('Subject is required.'),
		);
	}
	if ($image_url === '') {
		return array(
			'ok' => false,
			'message' => 'Marketing image is required.',
			'sent' => 0,
			'failed' => 0,
			'errors' => array('Marketing image is required.'),
		);
	}

	$resolved = marketing_resolve_recipients($conn, $client_ids);
	$recipients = $resolved['recipients'];
	$errors = $resolved['errors'];

	if (empty($recipients)) {
		return array(
			'ok' => false,
			'message' => 'No valid client emails to send to.',
			'sent' => 0,
			'failed' => count($errors),
			'errors' => $errors,
		);
	}

	if (!function_exists('sendAppMailWithAttachment')) {
		require_once dirname(__DIR__) . '/appMail.php';
	}

	$body = marketing_build_email_body($message, $image_url);
	$smtp = marketing_mail_smtp_options($cc_raw);
	$sent = 0;
	$failed = 0;
	$sent_recipients = array();

	foreach ($recipients as $r) {
		$result = sendAppMailWithAttachment(
			$r['name'],
			$r['email'],
			$subject,
			$body,
			'',
			'',
			$smtp
		);
		if (!empty($result['ok'])) {
			$sent++;
			$sent_recipients[] = array(
				'id' => (int) $r['id'],
				'name' => $r['name'],
				'email' => $r['email'],
			);
		} else {
			$failed++;
			$err = isset($result['error']) ? (string) $result['error'] : 'Mail send failed.';
			$errors[] = $r['name'] . ' (' . $r['email'] . '): ' . $err;
		}
	}

	$failed_total = $failed + count($resolved['errors']);
	$ok = $sent > 0;
	if ($ok && $failed === 0 && empty($resolved['errors'])) {
		$message_out = 'Marketing email sent to ' . $sent . ' client' . ($sent === 1 ? '' : 's') . '.';
	} elseif ($ok) {
		$message_out = 'Sent: ' . $sent . ' · Failed: ' . $failed_total;
	} else {
		$message_out = 'Could not send marketing emails.';
	}

	$log_id = marketing_bulk_email_log_send($conn, array(
		'subject' => $subject,
		'message' => $message,
		'image_path' => $image_path,
		'image_url' => $image_url,
		'sent' => $sent,
		'failed' => $failed_total,
		'recipient_count' => count($recipients),
		'recipients_json' => json_encode($sent_recipients),
		'errors_json' => json_encode(array_values($errors)),
		'created_by' => $created_by,
	));

	return array(
		'ok' => $ok,
		'message' => $message_out,
		'sent' => $sent,
		'failed' => $failed_total,
		'errors' => $errors,
		'log_id' => $log_id,
	);
}
