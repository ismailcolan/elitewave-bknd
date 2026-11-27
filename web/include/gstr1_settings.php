<?php

function gstr1_ensure_schema($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS gstr1_settings (
		setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
		setting_value VARCHAR(255) NOT NULL DEFAULT ''
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS gstr1_filing_meta (
		meta_id INT AUTO_INCREMENT PRIMARY KEY,
		gstin VARCHAR(20) NOT NULL,
		period_from DATE NOT NULL,
		period_to DATE NOT NULL,
		status VARCHAR(24) NOT NULL DEFAULT 'not_filed',
		arn VARCHAR(64) DEFAULT '',
		arn_date VARCHAR(20) DEFAULT '',
		last_online_activity VARCHAR(120) DEFAULT '',
		updated_at DATETIME DEFAULT NULL,
		UNIQUE KEY uq_period (gstin, period_from, period_to)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function gstr1_setting_get($conn, $key, $default = '')
{
	gstr1_ensure_schema($conn);
	$key = mysqli_real_escape_string($conn, (string) $key);
	$q = mysqli_query($conn, "SELECT setting_value FROM gstr1_settings WHERE setting_key='$key' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return (string) ($row['setting_value'] ?? $default);
	}
	return (string) $default;
}

function gstr1_setting_set($conn, $key, $value)
{
	gstr1_ensure_schema($conn);
	$key_esc = mysqli_real_escape_string($conn, (string) $key);
	$val_esc = mysqli_real_escape_string($conn, (string) $value);
	mysqli_query($conn, "INSERT INTO gstr1_settings (setting_key, setting_value) VALUES ('$key_esc','$val_esc')
		ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
}

function gstr1_b2c_large_threshold($conn)
{
	$raw = gstr1_setting_get($conn, 'b2c_large_threshold_inr', '250000');
	$val = round((float) str_replace(',', '', $raw), 2);
	if ($val <= 0) {
		$val = 250000.0;
	}
	return $val;
}

function gstr1_filing_meta($conn, $gstin, $from_mysql, $to_mysql)
{
	gstr1_ensure_schema($conn);
	$gstin_esc = mysqli_real_escape_string($conn, gst_tax_normalize_gstin($gstin));
	$from_esc = mysqli_real_escape_string($conn, $from_mysql);
	$to_esc = mysqli_real_escape_string($conn, $to_mysql);
	$q = mysqli_query($conn, "SELECT * FROM gstr1_filing_meta WHERE gstin='$gstin_esc' AND period_from='$from_esc' AND period_to='$to_esc' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return array(
			'status' => $row['status'] ?? 'not_filed',
			'status_label' => ($row['status'] ?? '') === 'filed' ? 'Filed' : 'Not filed',
			'arn' => trim((string) ($row['arn'] ?? '')),
			'arn_date' => trim((string) ($row['arn_date'] ?? '')),
			'last_online_activity' => trim((string) ($row['last_online_activity'] ?? '')),
		);
	}
	return array(
		'status' => 'not_filed',
		'status_label' => 'Not filed',
		'arn' => '',
		'arn_date' => '',
		'last_online_activity' => '',
	);
}