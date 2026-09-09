<?php

function ew_company_bank_default_accounts()
{
	return array(
		array(
			'account_label' => 'EliteWave360 Logistics - Axis Bank',
			'bank_name' => 'Axis Bank',
			'account_number' => '926020021424035',
			'ifsc' => 'UTIB0001885',
			'bank_branch' => 'Vepery Chennai - 600007 Tamil Nadu',
			'is_primary' => 1,
		),
	);
}

function ew_company_bank_seed_defaults($conn)
{
	$cnt_q = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM company_bank_account');
	$cnt = 0;
	if ($cnt_q && ($r = mysqli_fetch_assoc($cnt_q))) {
		$cnt = (int) $r['c'];
	}
	if ($cnt > 0) {
		return;
	}

	$now = date('d-m-Y');
	foreach (ew_company_bank_default_accounts() as $acc) {
		$label_esc = mysqli_real_escape_string($conn, $acc['account_label']);
		$bank_esc = mysqli_real_escape_string($conn, $acc['bank_name']);
		$ifsc_esc = mysqli_real_escape_string($conn, strtoupper($acc['ifsc']));
		$branch_esc = mysqli_real_escape_string($conn, $acc['bank_branch']);
		$number_esc = mysqli_real_escape_string($conn, $acc['account_number']);
		$is_primary = !empty($acc['is_primary']) ? 1 : 0;
		mysqli_query($conn, "INSERT INTO company_bank_account
			(account_label, bank_name, ifsc, bank_branch, account_number, is_primary, status, created_at, created_by, updated_at, updated_by)
			VALUES ('$label_esc', '$bank_esc', '$ifsc_esc', '$branch_esc', '$number_esc', '$is_primary', 0, '$now', 0, '$now', 0)");
	}
}

function ew_company_bank_ensure_schema($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS company_bank_account (
		bank_account_id INT(11) NOT NULL AUTO_INCREMENT,
		account_label VARCHAR(150) NOT NULL DEFAULT '',
		bank_name VARCHAR(150) NOT NULL DEFAULT '',
		ifsc VARCHAR(11) NOT NULL DEFAULT '',
		bank_branch VARCHAR(150) NOT NULL DEFAULT '',
		account_number VARCHAR(30) DEFAULT '',
		is_primary TINYINT(1) NOT NULL DEFAULT 0,
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (bank_account_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	ew_company_bank_seed_defaults($conn);
}

function ew_company_bank_payment_modes()
{
	return array(
		'CASH' => 'Cash',
		'CHEQUE' => 'Cheque',
		'UPI' => 'UPI',
		'NEFT' => 'NEFT',
		'RTGS' => 'RTGS',
		'IMPS' => 'IMPS',
		'DD' => 'DD',
	);
}

function ew_company_bank_mode_requires_account($mode)
{
	$mode = strtoupper(trim((string) $mode));
	return in_array($mode, array('CHEQUE', 'NEFT', 'RTGS', 'IMPS', 'DD'), true);
}

function ew_company_bank_options($conn, $include_inactive = false)
{
	ew_company_bank_ensure_schema($conn);
	$rows = array();
	$status_filter = $include_inactive ? '' : 'WHERE status=0';
	$q = mysqli_query($conn, "SELECT bank_account_id, account_label, bank_name, ifsc, bank_branch, account_number, is_primary
		FROM company_bank_account $status_filter
		ORDER BY is_primary DESC, account_label ASC, bank_name ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$label = trim($row['account_label'] ?? '');
			if ($label === '') {
				$label = trim(($row['bank_name'] ?? '') . ' - ' . ($row['account_number'] ?? ''), ' -');
			}
			$rows[] = array(
				'bank_account_id' => (int) $row['bank_account_id'],
				'account_label' => $row['account_label'] ?? '',
				'bank_name' => $row['bank_name'] ?? '',
				'ifsc' => strtoupper($row['ifsc'] ?? ''),
				'bank_branch' => $row['bank_branch'] ?? '',
				'account_number' => $row['account_number'] ?? '',
				'is_primary' => (int) ($row['is_primary'] ?? 0),
				'label' => $label,
			);
		}
	}
	return $rows;
}

function ew_company_bank_get($conn, $bank_account_id)
{
	ew_company_bank_ensure_schema($conn);
	$bank_account_id = (int) $bank_account_id;
	if ($bank_account_id <= 0) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT * FROM company_bank_account WHERE bank_account_id='$bank_account_id' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return $row;
	}
	return null;
}

function ew_company_bank_select_html($conn, $selected = 0)
{
	$html = '<option value="">Select Bank Account</option>';
	foreach (ew_company_bank_options($conn) as $row) {
		$id = (int) $row['bank_account_id'];
		$sel = ((int) $selected === $id) ? ' selected' : '';
		$html .= '<option value="' . $id . '"' . $sel . '>'
			. htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') . '</option>';
	}
	return $html;
}

function ew_company_bank_validate_ifsc($ifsc)
{
	$ifsc = strtoupper(trim((string) $ifsc));
	if ($ifsc === '') {
		return false;
	}
	return (bool) preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc);
}

function ew_company_bank_save($conn, $payload, $user_id)
{
	ew_company_bank_ensure_schema($conn);

	$bank_account_id = (int) ($payload['bank_account_id'] ?? 0);
	$account_label = trim((string) ($payload['account_label'] ?? ''));
	$bank_name = trim((string) ($payload['bank_name'] ?? ''));
	$ifsc = strtoupper(trim((string) ($payload['ifsc'] ?? '')));
	$bank_branch = trim((string) ($payload['bank_branch'] ?? ''));
	$account_number = trim((string) ($payload['account_number'] ?? ''));
	$is_primary = ((int) ($payload['is_primary'] ?? 0) === 1) ? 1 : 0;
	$status = ((int) ($payload['status'] ?? 0) === 1) ? 1 : 0;

	if ($bank_name === '') {
		return array('ok' => false, 'message' => 'Bank name is required.');
	}
	if (!ew_company_bank_validate_ifsc($ifsc)) {
		return array('ok' => false, 'message' => 'Valid IFSC code is required.');
	}
	if ($bank_branch === '') {
		return array('ok' => false, 'message' => 'Branch is required.');
	}

	$user_id = (int) $user_id;
	$now = date('d-m-Y');
	$label_esc = mysqli_real_escape_string($conn, $account_label);
	$bank_esc = mysqli_real_escape_string($conn, $bank_name);
	$ifsc_esc = mysqli_real_escape_string($conn, $ifsc);
	$branch_esc = mysqli_real_escape_string($conn, $bank_branch);
	$number_esc = mysqli_real_escape_string($conn, $account_number);

	if ($is_primary === 1) {
		mysqli_query($conn, 'UPDATE company_bank_account SET is_primary=0');
	}

	if ($bank_account_id > 0) {
		mysqli_query($conn, "UPDATE company_bank_account SET
			account_label='$label_esc',
			bank_name='$bank_esc',
			ifsc='$ifsc_esc',
			bank_branch='$branch_esc',
			account_number='$number_esc',
			is_primary='$is_primary',
			status='$status',
			updated_at='$now',
			updated_by='$user_id'
			WHERE bank_account_id='$bank_account_id'");
		return array('ok' => true, 'message' => 'Bank account updated.', 'bank_account_id' => $bank_account_id);
	}

	mysqli_query($conn, "INSERT INTO company_bank_account
		(account_label, bank_name, ifsc, bank_branch, account_number, is_primary, status, created_at, created_by, updated_at, updated_by)
		VALUES ('$label_esc', '$bank_esc', '$ifsc_esc', '$branch_esc', '$number_esc', '$is_primary', '$status', '$now', '$user_id', '$now', '$user_id')");
	$new_id = (int) mysqli_insert_id($conn);
	if ($new_id <= 0) {
		return array('ok' => false, 'message' => 'Could not save bank account.');
	}
	return array('ok' => true, 'message' => 'Bank account saved.', 'bank_account_id' => $new_id);
}

function ew_company_bank_fetch_list($conn)
{
	ew_company_bank_ensure_schema($conn);
	$rows = array();
	$q = mysqli_query($conn, 'SELECT * FROM company_bank_account ORDER BY is_primary DESC, account_label ASC, bank_name ASC');
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function ew_company_bank_delete($conn, $bank_account_id)
{
	ew_company_bank_ensure_schema($conn);
	$bank_account_id = (int) $bank_account_id;
	if ($bank_account_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid bank account.');
	}
	mysqli_query($conn, "DELETE FROM company_bank_account WHERE bank_account_id='$bank_account_id'");
	return array('ok' => true, 'message' => 'Bank account deleted.');
}
