<?php
if (!defined('EW_DATA_KEY')) {
	$key_file = __DIR__ . '/data_key.php';
	if (is_readable($key_file)) {
		require_once $key_file;
	}
}

/**
 * Encrypt a plaintext string for database storage (AES-256-GCM).
 * Already-encrypted values (EW1: prefix) are returned unchanged.
 */
function ew_data_encrypt($plaintext)
{
	if ($plaintext === null || $plaintext === '') {
		return $plaintext;
	}
	if (!defined('EW_DATA_KEY') || EW_DATA_KEY === '') {
		return $plaintext;
	}
	if (strpos($plaintext, 'EW1:') === 0) {
		return $plaintext;
	}
	$key = hex2bin(EW_DATA_KEY);
	if ($key === false || strlen($key) !== 32) {
		return $plaintext;
	}
	$iv = random_bytes(12);
	$tag = '';
	$ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
	if ($ciphertext === false) {
		return $plaintext;
	}
	return 'EW1:' . base64_encode($iv . $tag . $ciphertext);
}

/**
 * Decrypt a stored value. Plaintext legacy rows are returned as-is.
 */
function ew_data_decrypt($stored)
{
	if ($stored === null || $stored === '') {
		return $stored;
	}
	if (strpos($stored, 'EW1:') !== 0) {
		return $stored;
	}
	if (!defined('EW_DATA_KEY') || EW_DATA_KEY === '') {
		return '';
	}
	$key = hex2bin(EW_DATA_KEY);
	if ($key === false || strlen($key) !== 32) {
		return '';
	}
	$raw = base64_decode(substr($stored, 4), true);
	if ($raw === false || strlen($raw) < 28) {
		return $stored;
	}
	$iv = substr($raw, 0, 12);
	$tag = substr($raw, 12, 16);
	$ciphertext = substr($raw, 28);
	$plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
	return ($plaintext === false) ? '' : $plaintext;
}

function ew_client_encrypt_name($name)
{
	return ew_data_encrypt($name);
}

function ew_client_decrypt_name($name)
{
	return ew_data_decrypt($name);
}

/**
 * Fetch client rows and decrypt company names, sorted alphabetically.
 */
function ew_client_fetch_sorted($conn, $query)
{
	$result = mysqli_query($conn, $query);
	if (!$result) {
		return array();
	}
	$rows = array();
	while ($row = mysqli_fetch_assoc($result)) {
		if (isset($row['client_company_name'])) {
			$row['client_company_name'] = ew_client_decrypt_name($row['client_company_name']);
		}
		$rows[] = $row;
	}
	usort($rows, function ($a, $b) {
		return strcasecmp($a['client_company_name'] ?? '', $b['client_company_name'] ?? '');
	});
	return $rows;
}

/**
 * Client name autocomplete — decrypts in PHP because names are encrypted in DB.
 */
function ew_client_autocomplete_search($conn, $term, $extra_where = '')
{
	$term = strtolower(trim($term));
	$query = "SELECT client_id, client_company_name FROM client WHERE 1=1 $extra_where";
	$result = mysqli_query($conn, $query);
	if (!$result) {
		return array();
	}
	$matches = array();
	while ($row = mysqli_fetch_assoc($result)) {
		$name = ew_client_decrypt_name($row['client_company_name']);
		if ($term === '' || stripos($name, $term) === 0) {
			$matches[] = array(
				'id' => $row['client_id'],
				'value' => $name,
			);
		}
	}
	usort($matches, function ($a, $b) {
		return strcasecmp($a['value'], $b['value']);
	});
	return $matches;
}
