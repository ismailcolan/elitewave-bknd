<?php

/**
 * Elite Wave outbound mail — athar@ From, info@ default CC (GoDaddy SMTP).
 */
function ew_mail_smtp_options()
{
	return array(
		'host' => 'smtpout.secureserver.net',
		'port' => 587,
		'secure' => 'tls',
		'username' => 'athar@elitewave360.in',
		'password' => 'EliteWave@360#',
		'from' => 'athar@elitewave360.in',
		'from_name' => 'Mohammed Athar',
		'cc' => array(
			array('info@elitewave360.in', 'Elite Wave 360'),
		),
	);
}

function ew_mail_configure_phpmailer($mail, array $overrides = array())
{
	$cfg = array_merge(ew_mail_smtp_options(), $overrides);
	if (empty($mail->Mailer) || $mail->Mailer !== 'smtp') {
		$mail->isSMTP();
	}
	$mail->Host = $cfg['host'];
	$mail->SMTPAuth = true;
	$mail->Username = $cfg['username'];
	$mail->Password = $cfg['password'];
	$mail->SMTPSecure = $cfg['secure'];
	$mail->Port = (int) $cfg['port'];
	$mail->From = $cfg['from'];
	$mail->FromName = $cfg['from_name'];
	return $cfg;
}

function ew_mail_add_default_cc($mail, array $cfg = null)
{
	if ($cfg === null) {
		$cfg = ew_mail_smtp_options();
	}
	$cc_list = isset($cfg['cc']) && is_array($cfg['cc']) ? $cfg['cc'] : array();
	foreach ($cc_list as $cc_row) {
		if (!is_array($cc_row) || empty($cc_row[0])) {
			continue;
		}
		$cc_addr = trim((string) $cc_row[0]);
		if ($cc_addr === '' || !filter_var($cc_addr, FILTER_VALIDATE_EMAIL)) {
			continue;
		}
		$cc_name = isset($cc_row[1]) ? (string) $cc_row[1] : '';
		$mail->addCC($cc_addr, $cc_name);
	}
}

/**
 * Add one or many TO addresses; returns count added.
 *
 * @param mixed $to_name string|array
 * @param mixed $to_mail string|array
 */
function ew_mail_add_to_recipients($mail, $to_name, $to_mail)
{
	if (!is_array($to_mail)) {
		$to_mail = array($to_mail);
		$to_name = is_array($to_name) ? $to_name : array($to_name);
	}
	$seen = array();
	$added = 0;
	for ($i = 0; $i < count($to_mail); $i++) {
		$addr = trim((string) ($to_mail[$i] ?? ''));
		if ($addr === '' || !filter_var($addr, FILTER_VALIDATE_EMAIL)) {
			continue;
		}
		$key = strtolower($addr);
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$disp = isset($to_name[$i]) ? trim((string) $to_name[$i]) : '';
		$mail->addAddress($addr, $disp);
		$added++;
	}
	return $added;
}