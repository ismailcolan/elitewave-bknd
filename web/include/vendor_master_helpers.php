<?php

function ew_vendor_default_type_options()
{
	return array(
		'TRANSPORTER' => 'Transporter',
		'VEHICLE_OWNER' => 'Vehicle Owner',
		'FLEET_OPERATOR' => 'Fleet Operator',
		'GTA' => 'GTA',
		'FLIGHT_CARGO' => 'Flight / Cargo Operator',
		'RAIL_CARGO' => 'Rail / Cargo Operator',
		'WAREHOUSE' => 'Warehouse',
		'LOADING_UNLOADING' => 'Loading / Unloading Contractor',
		'OTHER' => 'Other',
	);
}

function ew_vendor_type_slug($name)
{
	$slug = strtoupper(preg_replace('/[^A-Z0-9]+/', '_', trim((string) $name)));
	$slug = trim($slug, '_');
	return $slug !== '' ? $slug : 'TYPE';
}

function ew_vendor_run_sql_file($conn, $filename)
{
	$sql_file = __DIR__ . '/../setup/' . $filename;
	if (!is_readable($sql_file)) {
		return false;
	}
	$sql = trim(file_get_contents($sql_file));
	return (bool) mysqli_query($conn, $sql);
}

function ew_vendor_table_exists($conn, $table)
{
	$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
	$check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
	return ($check && mysqli_num_rows($check) > 0);
}

function ew_vendor_ensure_type_table($conn)
{
	if (ew_vendor_table_exists($conn, 'vendor_type_master')) {
		return true;
	}
	if (!ew_vendor_run_sql_file($conn, 'vendor_type_master.sql')) {
		return false;
	}
	$defaults = ew_vendor_default_type_options();
	$now = date('d-m-Y');
	foreach ($defaults as $code => $label) {
		$code_esc = mysqli_real_escape_string($conn, $code);
		$label_esc = mysqli_real_escape_string($conn, $label);
		mysqli_query($conn, "INSERT IGNORE INTO vendor_type_master (type_code, type_name, status, created_at)
			VALUES ('$code_esc', '$label_esc', 0, '$now')");
	}
	return true;
}

function ew_vendor_ensure_bank_table($conn)
{
	if (ew_vendor_table_exists($conn, 'vendor_bank_accounts')) {
		ew_vendor_ensure_bank_columns($conn);
		return true;
	}
	if (!ew_vendor_run_sql_file($conn, 'vendor_bank_accounts.sql')) {
		return false;
	}
	ew_vendor_ensure_bank_columns($conn);
	return true;
}

function ew_vendor_ensure_master_columns($conn)
{
	if (!ew_vendor_table_exists($conn, 'vendor_master')) {
		return;
	}
	$columns = array(
		'contact_designation' => "ALTER TABLE vendor_master ADD COLUMN contact_designation VARCHAR(100) DEFAULT '' AFTER contact_person",
		'website' => "ALTER TABLE vendor_master ADD COLUMN website VARCHAR(200) DEFAULT '' AFTER email_alt",
		'gst_registered' => "ALTER TABLE vendor_master ADD COLUMN gst_registered TINYINT(1) NOT NULL DEFAULT 0 AFTER contact_no2",
		'gst_exemption' => "ALTER TABLE vendor_master ADD COLUMN gst_exemption TINYINT(1) NOT NULL DEFAULT 0 AFTER gstin",
		'tds_applicable' => "ALTER TABLE vendor_master ADD COLUMN tds_applicable TINYINT(1) NOT NULL DEFAULT 0 AFTER gst_exemption",
		'tds_rate' => "ALTER TABLE vendor_master ADD COLUMN tds_rate DECIMAL(5,2) DEFAULT NULL AFTER tds_applicable",
	);
	foreach ($columns as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_master LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}
}

function ew_vendor_ensure_bank_columns($conn)
{
	if (!ew_vendor_table_exists($conn, 'vendor_bank_accounts')) {
		return;
	}
	$chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_bank_accounts LIKE 'account_type'");
	if ($chk && mysqli_num_rows($chk) === 0) {
		mysqli_query($conn, "ALTER TABLE vendor_bank_accounts ADD COLUMN account_type VARCHAR(20) DEFAULT '' AFTER bank_branch");
	}
}

function ew_vendor_ensure_table($conn)
{
	if (ew_vendor_table_ready($conn)) {
		ew_vendor_ensure_type_table($conn);
		ew_vendor_ensure_master_columns($conn);
		ew_vendor_ensure_bank_table($conn);
		return true;
	}
	if (!ew_vendor_run_sql_file($conn, 'vendor_master.sql')) {
		return false;
	}
	ew_vendor_ensure_type_table($conn);
	ew_vendor_ensure_master_columns($conn);
	ew_vendor_ensure_bank_table($conn);
	return true;
}

function ew_vendor_account_type_options()
{
	return array(
		'SAVINGS' => 'Savings',
		'CURRENT' => 'Current',
		'CC' => 'Cash Credit (CC)',
		'OD' => 'Overdraft (OD)',
	);
}

function ew_vendor_account_type_select_html($selected = '')
{
	$html = '<option value="">Select Account Type</option>';
	foreach (ew_vendor_account_type_options() as $code => $label) {
		$sel = ((string) $selected === (string) $code) ? ' selected' : '';
		$html .= '<option value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	return $html;
}

function ew_vendor_parse_tax_flags($post)
{
	$gst_registered = ((int) ($post['gst_registered'] ?? 0) === 1) ? 1 : 0;
	$gst_exemption = ((int) ($post['gst_exemption'] ?? 0) === 1) ? 1 : 0;
	$tds_applicable = ((int) ($post['tds_applicable'] ?? 0) === 1) ? 1 : 0;
	$tds_rate_raw = trim($post['tds_rate'] ?? '');
	$tds_rate = null;
	if ($tds_applicable === 1) {
		if ($tds_rate_raw === '' || !is_numeric($tds_rate_raw) || (float) $tds_rate_raw < 0) {
			return array('ok' => false, 'message' => 'Enter valid TDS rate when TDS is applicable.');
		}
		$tds_rate = round((float) $tds_rate_raw, 2);
	}
	$gstin = ew_vendor_normalize_gstin($post['gstin'] ?? '');
	if ($gst_registered === 1) {
		if ($gstin === '') {
			return array('ok' => false, 'message' => 'GSTIN is required when vendor is GST registered.');
		}
		if (!ew_vendor_validate_gstin($gstin)) {
			return array('ok' => false, 'message' => 'Invalid GSTIN. Enter 15 characters (example: 29AABCU9603R1ZM).');
		}
	} else {
		$gstin = '';
	}
	return array(
		'ok' => true,
		'gst_registered' => $gst_registered,
		'gst_exemption' => $gst_exemption,
		'tds_applicable' => $tds_applicable,
		'tds_rate' => $tds_rate,
		'gstin' => $gstin,
	);
}

function ew_vendor_type_options($conn)
{
	ew_vendor_ensure_type_table($conn);
	$options = array();
	$q = mysqli_query($conn, "SELECT type_code, type_name FROM vendor_type_master WHERE status=0 ORDER BY type_name ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$options[$row['type_code']] = $row['type_name'];
		}
	}
	if (empty($options)) {
		return ew_vendor_default_type_options();
	}
	return $options;
}

function ew_vendor_type_label($conn, $code)
{
	$code = trim((string) $code);
	if ($code === '') {
		return '';
	}
	ew_vendor_ensure_type_table($conn);
	$code_esc = mysqli_real_escape_string($conn, $code);
	$q = mysqli_query($conn, "SELECT type_name FROM vendor_type_master WHERE type_code='$code_esc' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return $row['type_name'];
	}
	$defaults = ew_vendor_default_type_options();
	$key = strtoupper($code);
	return $defaults[$key] ?? $code;
}

function ew_vendor_type_select_html($conn, $selected = '')
{
	$html = '<option value="">Select Vendor Type</option>';
	foreach (ew_vendor_type_options($conn) as $code => $label) {
		$sel = ((string) $selected === (string) $code) ? ' selected' : '';
		$html .= '<option value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	return $html;
}

function ew_vendor_add_type($conn, $type_name, $created_by = 0)
{
	ew_vendor_ensure_type_table($conn);
	$type_name = trim((string) $type_name);
	if ($type_name === '') {
		return array('ok' => false, 'message' => 'Vendor type name is required.');
	}
	$type_code = ew_vendor_type_slug($type_name);
	$code_esc = mysqli_real_escape_string($conn, $type_code);
	$dup = mysqli_query($conn, "SELECT vendor_type_id FROM vendor_type_master WHERE type_code='$code_esc' OR type_name='" . mysqli_real_escape_string($conn, $type_name) . "' LIMIT 1");
	if ($dup && mysqli_num_rows($dup) > 0) {
		return array('ok' => false, 'message' => 'This vendor type already exists.');
	}
	$now = date('d-m-Y');
	$created_by = (int) $created_by;
	$name_esc = mysqli_real_escape_string($conn, $type_name);
	$ok = mysqli_query($conn, "INSERT INTO vendor_type_master (type_code, type_name, status, created_at, created_by)
		VALUES ('$code_esc', '$name_esc', 0, '$now', '$created_by')");
	if (!$ok) {
		return array('ok' => false, 'message' => 'Could not save vendor type.');
	}
	return array(
		'ok' => true,
		'type_code' => $type_code,
		'type_name' => $type_name,
		'message' => 'Vendor type added.',
	);
}

function ew_vendor_get_bank_accounts($conn, $vendor_id)
{
	ew_vendor_ensure_bank_table($conn);
	$vendor_id = (int) $vendor_id;
	if ($vendor_id <= 0) {
		return array();
	}
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM vendor_bank_accounts WHERE vendor_id='$vendor_id' AND status=0 ORDER BY FIELD(account_role,'PRIMARY','SECONDARY','OTHER'), bank_account_id ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function ew_vendor_legacy_bank_row($row)
{
	if (empty($row['account_number']) && empty($row['bank_name']) && empty($row['ifsc'])) {
		return null;
	}
	return array(
		'bank_account_id' => 0,
		'account_holder_name' => $row['account_holder_name'] ?? '',
		'bank_name' => $row['bank_name'] ?? '',
		'account_number' => $row['account_number'] ?? '',
		'account_number_confirm' => $row['account_number'] ?? '',
		'ifsc' => $row['ifsc'] ?? '',
		'bank_branch' => $row['bank_branch'] ?? '',
		'account_type' => $row['account_type'] ?? '',
		'account_role' => 'PRIMARY',
	);
}

function ew_vendor_resolve_bank_accounts($conn, $vendor_id, $master_row = array())
{
	$accounts = ew_vendor_get_bank_accounts($conn, $vendor_id);
	if (!empty($accounts)) {
		return $accounts;
	}
	$legacy = ew_vendor_legacy_bank_row($master_row);
	return $legacy ? array($legacy) : array();
}

function ew_vendor_validate_bank_accounts($accounts)
{
	$clean = array();
	$has_primary = false;
	$has_secondary = false;
	if (empty($accounts)) {
		return array('ok' => false, 'message' => 'Add at least one bank account and mark one as Primary.');
	}
	foreach ($accounts as $idx => $acc) {
		$holder = trim($acc['account_holder_name'] ?? '');
		$bank = trim($acc['bank_name'] ?? '');
		$number = trim($acc['account_number'] ?? '');
		$confirm = trim($acc['account_number_confirm'] ?? '');
		$ifsc = strtoupper(trim($acc['ifsc'] ?? ''));
		$branch = trim($acc['bank_branch'] ?? '');
		$account_type = strtoupper(trim($acc['account_type'] ?? ''));
		$role = strtoupper(trim($acc['account_role'] ?? 'OTHER'));
		$row_no = (int) $idx + 1;

		if ($holder === '' || $bank === '' || $number === '' || $confirm === '' || $ifsc === '' || $branch === '' || $account_type === '') {
			return array('ok' => false, 'message' => 'Bank account #' . $row_no . ': fill all mandatory bank fields.');
		}
		if ($number !== $confirm) {
			return array('ok' => false, 'message' => 'Bank account #' . $row_no . ': account number and confirm account number do not match.');
		}
		if (!ew_vendor_validate_ifsc($ifsc)) {
			return array('ok' => false, 'message' => 'Invalid IFSC code in bank account #' . $row_no . ': ' . $ifsc);
		}
		if (!array_key_exists($account_type, ew_vendor_account_type_options())) {
			return array('ok' => false, 'message' => 'Bank account #' . $row_no . ': select a valid account type.');
		}
		if (!in_array($role, array('PRIMARY', 'SECONDARY', 'OTHER'), true)) {
			$role = 'OTHER';
		}
		if ($role === 'PRIMARY') {
			if ($has_primary) {
				$role = 'OTHER';
			} else {
				$has_primary = true;
			}
		}
		if ($role === 'SECONDARY') {
			if ($has_secondary) {
				$role = 'OTHER';
			} else {
				$has_secondary = true;
			}
		}
		$clean[] = array(
			'account_holder_name' => $holder,
			'bank_name' => $bank,
			'account_number' => $number,
			'ifsc' => $ifsc,
			'bank_branch' => $branch,
			'account_type' => $account_type,
			'account_role' => $role,
		);
	}
	if (!$has_primary) {
		return array('ok' => false, 'message' => 'Mark one bank account as Primary.');
	}
	return array('ok' => true, 'accounts' => $clean);
}

function ew_vendor_save_bank_accounts($conn, $vendor_id, $accounts, $user_id = 0)
{
	ew_vendor_ensure_bank_table($conn);
	$vendor_id = (int) $vendor_id;
	if ($vendor_id <= 0) {
		return false;
	}
	mysqli_query($conn, "DELETE FROM vendor_bank_accounts WHERE vendor_id='$vendor_id'");
	$now = date('d-m-Y');
	$user_id = (int) $user_id;
	foreach ($accounts as $acc) {
		$holder = mysqli_real_escape_string($conn, $acc['account_holder_name']);
		$bank = mysqli_real_escape_string($conn, $acc['bank_name']);
		$number = mysqli_real_escape_string($conn, $acc['account_number']);
		$ifsc = mysqli_real_escape_string($conn, $acc['ifsc']);
		$branch = mysqli_real_escape_string($conn, $acc['bank_branch']);
		$role = mysqli_real_escape_string($conn, $acc['account_role']);
		$account_type = mysqli_real_escape_string($conn, $acc['account_type'] ?? '');
		mysqli_query($conn, "INSERT INTO vendor_bank_accounts
			(vendor_id, account_holder_name, bank_name, account_number, ifsc, bank_branch, account_type, account_role, status, created_at, created_by)
			VALUES ('$vendor_id', '$holder', '$bank', '$number', '$ifsc', '$branch', '$account_type', '$role', 0, '$now', '$user_id')");
	}
	ew_vendor_sync_legacy_bank_fields($conn, $vendor_id, $accounts);
	return true;
}

function ew_vendor_sync_legacy_bank_fields($conn, $vendor_id, $accounts = null)
{
	$vendor_id = (int) $vendor_id;
	if ($accounts === null) {
		$accounts = ew_vendor_get_bank_accounts($conn, $vendor_id);
	}
	$primary = null;
	foreach ($accounts as $acc) {
		if (($acc['account_role'] ?? '') === 'PRIMARY') {
			$primary = $acc;
			break;
		}
	}
	if (!$primary && !empty($accounts)) {
		$primary = $accounts[0];
	}
	if (!$primary) {
		mysqli_query($conn, "UPDATE vendor_master SET account_holder_name='', bank_name='', account_number='', ifsc='', bank_branch='' WHERE vendor_id='$vendor_id'");
		return;
	}
	$holder = mysqli_real_escape_string($conn, $primary['account_holder_name'] ?? '');
	$bank = mysqli_real_escape_string($conn, $primary['bank_name'] ?? '');
	$number = mysqli_real_escape_string($conn, $primary['account_number'] ?? '');
	$ifsc = mysqli_real_escape_string($conn, $primary['ifsc'] ?? '');
	$branch = mysqli_real_escape_string($conn, $primary['bank_branch'] ?? '');
	mysqli_query($conn, "UPDATE vendor_master SET account_holder_name='$holder', bank_name='$bank', account_number='$number', ifsc='$ifsc', bank_branch='$branch' WHERE vendor_id='$vendor_id'");
}

function ew_vendor_get_payment_account($conn, $vendor_id, $role = 'PRIMARY')
{
	$vendor_id = (int) $vendor_id;
	$role = strtoupper(trim((string) $role));
	if (!in_array($role, array('PRIMARY', 'SECONDARY'), true)) {
		$role = 'PRIMARY';
	}
	ew_vendor_ensure_bank_table($conn);
	$role_esc = mysqli_real_escape_string($conn, $role);
	$q = mysqli_query($conn, "SELECT * FROM vendor_bank_accounts WHERE vendor_id='$vendor_id' AND account_role='$role_esc' AND status=0 LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return $row;
	}
	$q2 = mysqli_query($conn, "SELECT account_holder_name, bank_name, account_number, ifsc, bank_branch FROM vendor_master WHERE vendor_id='$vendor_id' LIMIT 1");
	if ($q2 && ($row = mysqli_fetch_assoc($q2)) && !empty($row['account_number'])) {
		return array_merge($row, array('account_role' => 'PRIMARY'));
	}
	return null;
}

function ew_vendor_next_code($conn)
{
	$row = mysqli_fetch_array(mysqli_query($conn, 'SELECT MAX(vendor_code_id) AS code_id FROM vendor_master'));
	$id = (int) ($row['code_id'] ?? 0) + 1;
	return array(
		'vendor_code_id' => $id,
		'vendor_code' => 'VEN' . sprintf('%05d', $id),
	);
}

function ew_vendor_table_ready($conn)
{
	return ew_vendor_table_exists($conn, 'vendor_master');
}

function ew_vendor_is_linked($conn, $vendor_id)
{
	$vendor_id = (int) $vendor_id;
	if ($vendor_id <= 0) {
		return false;
	}
	$checks = array(
		"SELECT transaction_id FROM transactions WHERE vendor_id='$vendor_id' LIMIT 1",
		"SELECT trip_id FROM trip WHERE vendor_id='$vendor_id' LIMIT 1",
	);
	foreach ($checks as $query) {
		$res = @mysqli_query($conn, $query);
		if ($res && mysqli_num_rows($res) > 0) {
			return true;
		}
	}
	return false;
}

function ew_vendor_normalize_gstin($gstin)
{
	$gstin = strtoupper(trim((string) $gstin));
	return preg_replace('/[^A-Z0-9]/', '', $gstin);
}

function ew_vendor_validate_gstin($gstin)
{
	$gstin = ew_vendor_normalize_gstin($gstin);
	if ($gstin === '') {
		return true;
	}
	if (strlen($gstin) !== 15) {
		return false;
	}
	return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][0-9A-Z]Z[0-9A-Z]$/', $gstin);
}

function ew_vendor_validate_pan($pan)
{
	$pan = strtoupper(trim((string) $pan));
	if ($pan === '') {
		return false;
	}
	return (bool) preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan);
}

function ew_vendor_validate_ifsc($ifsc)
{
	$ifsc = strtoupper(trim((string) $ifsc));
	if ($ifsc === '') {
		return true;
	}
	return (bool) preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc);
}

function ew_vendor_parse_bank_accounts_post($post)
{
	$accounts = array();
	if (!isset($post['bank_accounts']) || !is_array($post['bank_accounts'])) {
		return $accounts;
	}
	foreach ($post['bank_accounts'] as $row) {
		if (!is_array($row)) {
			continue;
		}
		$role = 'OTHER';
		if (!empty($row['is_primary'])) {
			$role = 'PRIMARY';
		} elseif (!empty($row['is_secondary'])) {
			$role = 'SECONDARY';
		}
		$accounts[] = array(
			'account_holder_name' => trim($row['account_holder_name'] ?? ''),
			'bank_name' => trim($row['bank_name'] ?? ''),
			'account_number' => trim($row['account_number'] ?? ''),
			'account_number_confirm' => trim($row['account_number_confirm'] ?? ''),
			'ifsc' => strtoupper(trim($row['ifsc'] ?? '')),
			'bank_branch' => trim($row['bank_branch'] ?? ''),
			'account_type' => strtoupper(trim($row['account_type'] ?? '')),
			'account_role' => $role,
		);
	}
	return $accounts;
}
