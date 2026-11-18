<?php

require_once __DIR__ . '/expense_schema.php';
require_once __DIR__ . '/expense_type_helpers.php';
require_once __DIR__ . '/vendor_master_helpers.php';

function petty_cash_ensure_schema($conn)
{
	expense_ensure_tables($conn);
	ew_vendor_ensure_table($conn);

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS petty_cash_voucher (
		petty_cash_id INT(11) NOT NULL AUTO_INCREMENT,
		voucher_no VARCHAR(20) NOT NULL DEFAULT '',
		voucher_date VARCHAR(20) DEFAULT '',
		entry_type VARCHAR(20) NOT NULL DEFAULT 'payment',
		amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		particulars VARCHAR(500) DEFAULT '',
		paid_to VARCHAR(150) DEFAULT '',
		vendor_id INT(11) NOT NULL DEFAULT 0,
		category_id INT(11) NOT NULL DEFAULT 0,
		ref_no VARCHAR(50) DEFAULT '',
		remarks VARCHAR(500) DEFAULT '',
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (petty_cash_id),
		UNIQUE KEY uk_petty_cash_voucher_no (voucher_no),
		KEY idx_petty_cash_date (voucher_date),
		KEY idx_petty_cash_type (entry_type),
		KEY idx_petty_cash_status (status),
		KEY idx_petty_cash_vendor (vendor_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$chk = mysqli_query($conn, "SHOW COLUMNS FROM petty_cash_voucher LIKE 'vendor_id'");
	if ($chk && mysqli_num_rows($chk) === 0) {
		mysqli_query($conn, "ALTER TABLE petty_cash_voucher ADD COLUMN vendor_id INT(11) NOT NULL DEFAULT 0 AFTER paid_to");
		mysqli_query($conn, "ALTER TABLE petty_cash_voucher ADD KEY idx_petty_cash_vendor (vendor_id)");
	}
}

function petty_cash_format_money($val)
{
	return number_format((float) $val, 2, '.', '');
}

function petty_cash_parse_money($val)
{
	if (is_string($val)) {
		$val = str_replace(array(',', ' '), '', $val);
	}
	return round((float) $val, 2);
}

function petty_cash_entry_types()
{
	return array(
		'receipt' => 'Received (Cash In)',
		'payment' => 'Sent (Cash Out)',
	);
}

function petty_cash_normalize_entry_type($type)
{
	$type = strtolower(trim((string) $type));
	return ($type === 'receipt') ? 'receipt' : 'payment';
}

/** Short GPay-style label for lists / badges. */
function petty_cash_type_badge_label($entry_type)
{
	return (petty_cash_normalize_entry_type($entry_type) === 'receipt') ? 'Received' : 'Sent';
}

function petty_cash_next_no($conn, $petty_cash_id)
{
	$id = (int) $petty_cash_id;
	return 'PC' . sprintf('%05d', $id);
}

function petty_cash_preview_next_no($conn)
{
	petty_cash_ensure_schema($conn);
	$next_id = 1;
	$q = mysqli_query($conn, 'SELECT MAX(petty_cash_id) AS max_id FROM petty_cash_voucher');
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$next_id = (int) ($row['max_id'] ?? 0) + 1;
	}
	return petty_cash_next_no($conn, $next_id);
}

function petty_cash_validate_date($date_str)
{
	$date_str = trim((string) $date_str);
	if ($date_str === '') {
		return array('ok' => false, 'message' => 'Voucher date is required.');
	}
	$dt = DateTime::createFromFormat('d-m-Y', $date_str);
	if (!$dt || $dt->format('d-m-Y') !== $date_str) {
		return array('ok' => false, 'message' => 'Invalid date. Use dd-mm-yyyy.');
	}
	return array('ok' => true, 'date' => $date_str);
}

function petty_cash_category_options($conn)
{
	expense_type_ensure_schema($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT category_id, category_code, category_name
		FROM expense_category
		WHERE status=0
		ORDER BY category_name ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$code = trim((string) ($row['category_code'] ?? ''));
			$name = trim((string) ($row['category_name'] ?? ''));
			$label = $name;
			if ($code !== '') {
				$label = $code . ' — ' . $name;
			}
			$rows[] = array(
				'category_id' => (int) $row['category_id'],
				'category_code' => $code,
				'category_name' => $name,
				'label' => $label,
			);
		}
	}
	return $rows;
}

function petty_cash_vendor_options($conn)
{
	ew_vendor_ensure_table($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT vendor_id, vendor_code, vendor_name
		FROM vendor_master
		WHERE status=0
		ORDER BY vendor_name ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$code = trim((string) ($row['vendor_code'] ?? ''));
			$name = trim((string) ($row['vendor_name'] ?? ''));
			$label = $name;
			if ($code !== '' && $name !== '') {
				$label = $code . ' — ' . $name;
			} elseif ($code !== '') {
				$label = $code;
			}
			$rows[] = array(
				'vendor_id' => (int) $row['vendor_id'],
				'vendor_code' => $code,
				'vendor_name' => $name,
				'label' => $label,
			);
		}
	}
	return $rows;
}

function petty_cash_vendor_label($conn, $vendor_id)
{
	$vendor_id = (int) $vendor_id;
	if ($vendor_id <= 0) {
		return '';
	}
	$q = mysqli_query($conn, "SELECT vendor_code, vendor_name FROM vendor_master WHERE vendor_id='$vendor_id' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$code = trim((string) ($row['vendor_code'] ?? ''));
		$name = trim((string) ($row['vendor_name'] ?? ''));
		if ($code !== '' && $name !== '') {
			return $code . ' — ' . $name;
		}
		return $name !== '' ? $name : $code;
	}
	return '';
}

function petty_cash_summary($conn)
{
	petty_cash_ensure_schema($conn);
	$summary = array(
		'total_receipt' => 0.0,
		'total_payment' => 0.0,
		'balance' => 0.0,
		'count' => 0,
	);
	$q = mysqli_query($conn, "SELECT
			COUNT(*) AS cnt,
			COALESCE(SUM(CASE WHEN entry_type='receipt' THEN amount ELSE 0 END),0) AS total_receipt,
			COALESCE(SUM(CASE WHEN entry_type='payment' THEN amount ELSE 0 END),0) AS total_payment
		FROM petty_cash_voucher
		WHERE status=0");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$summary['count'] = (int) ($row['cnt'] ?? 0);
		$summary['total_receipt'] = round((float) ($row['total_receipt'] ?? 0), 2);
		$summary['total_payment'] = round((float) ($row['total_payment'] ?? 0), 2);
		$summary['balance'] = round($summary['total_receipt'] - $summary['total_payment'], 2);
	}
	return array(
		'total_receipt' => petty_cash_format_money($summary['total_receipt']),
		'total_payment' => petty_cash_format_money($summary['total_payment']),
		'balance' => petty_cash_format_money($summary['balance']),
		'balance_raw' => $summary['balance'],
		'count' => $summary['count'],
	);
}

/**
 * Chronological list with running balance (oldest → newest for balance math).
 * Returns newest-first for display when $newest_first is true.
 */
function petty_cash_fetch_list($conn, $newest_first = true)
{
	petty_cash_ensure_schema($conn);
	$rows = array();
	$sql = "SELECT p.*,
			c.category_code, c.category_name,
			v.vendor_code, v.vendor_name
		FROM petty_cash_voucher p
		LEFT JOIN expense_category c ON c.category_id = p.category_id
		LEFT JOIN vendor_master v ON v.vendor_id = p.vendor_id
		WHERE p.status=0
		ORDER BY STR_TO_DATE(p.voucher_date, '%d-%m-%Y') ASC, p.petty_cash_id ASC
		LIMIT 2000";
	$q = mysqli_query($conn, $sql);
	$running = 0.0;
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$entry_type = petty_cash_normalize_entry_type($row['entry_type'] ?? 'payment');
			$amount = round((float) ($row['amount'] ?? 0), 2);
			$receipt = ($entry_type === 'receipt') ? $amount : 0.0;
			$payment = ($entry_type === 'payment') ? $amount : 0.0;
			$running = round($running + $receipt - $payment, 2);

			$cat_code = trim((string) ($row['category_code'] ?? ''));
			$cat_name = trim((string) ($row['category_name'] ?? ''));
			$category_label = '';
			if ($cat_name !== '') {
				$category_label = ($cat_code !== '') ? ($cat_code . ' — ' . $cat_name) : $cat_name;
			}

			$v_code = trim((string) ($row['vendor_code'] ?? ''));
			$v_name = trim((string) ($row['vendor_name'] ?? ''));
			$vendor_label = '';
			if ($v_name !== '' || $v_code !== '') {
				$vendor_label = ($v_code !== '' && $v_name !== '') ? ($v_code . ' — ' . $v_name) : ($v_name !== '' ? $v_name : $v_code);
			}

			$rows[] = array(
				'petty_cash_id' => (int) $row['petty_cash_id'],
				'voucher_no' => $row['voucher_no'] ?? '',
				'voucher_date' => $row['voucher_date'] ?? '',
				'entry_type' => $entry_type,
				'entry_type_label' => petty_cash_type_badge_label($entry_type),
				'amount' => petty_cash_format_money($amount),
				'amount_raw' => $amount,
				'receipt' => petty_cash_format_money($receipt),
				'payment' => petty_cash_format_money($payment),
				'balance' => petty_cash_format_money($running),
				'balance_raw' => $running,
				'particulars' => $row['particulars'] ?? '',
				'paid_to' => $row['paid_to'] ?? '',
				'vendor_id' => (int) ($row['vendor_id'] ?? 0),
				'vendor_label' => $vendor_label,
				'category_id' => (int) ($row['category_id'] ?? 0),
				'category_label' => $category_label,
				'ref_no' => $row['ref_no'] ?? '',
				'remarks' => $row['remarks'] ?? '',
				'updated_at' => $row['updated_at'] ?? ($row['created_at'] ?? ''),
			);
		}
	}

	if ($newest_first) {
		$rows = array_reverse($rows);
	}
	return $rows;
}

function petty_cash_load($conn, $petty_cash_id)
{
	petty_cash_ensure_schema($conn);
	$petty_cash_id = (int) $petty_cash_id;
	if ($petty_cash_id <= 0) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT p.*, c.category_code, c.category_name,
			v.vendor_code, v.vendor_name
		FROM petty_cash_voucher p
		LEFT JOIN expense_category c ON c.category_id = p.category_id
		LEFT JOIN vendor_master v ON v.vendor_id = p.vendor_id
		WHERE p.petty_cash_id='$petty_cash_id' AND p.status=0
		LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return null;
	}

	$entry_type = petty_cash_normalize_entry_type($row['entry_type'] ?? 'payment');
	$cat_code = trim((string) ($row['category_code'] ?? ''));
	$cat_name = trim((string) ($row['category_name'] ?? ''));
	$category_label = '';
	if ($cat_name !== '') {
		$category_label = ($cat_code !== '') ? ($cat_code . ' — ' . $cat_name) : $cat_name;
	}
	$v_code = trim((string) ($row['vendor_code'] ?? ''));
	$v_name = trim((string) ($row['vendor_name'] ?? ''));
	$vendor_label = '';
	if ($v_name !== '' || $v_code !== '') {
		$vendor_label = ($v_code !== '' && $v_name !== '') ? ($v_code . ' — ' . $v_name) : ($v_name !== '' ? $v_name : $v_code);
	}

	return array(
		'petty_cash_id' => (int) $row['petty_cash_id'],
		'voucher_no' => $row['voucher_no'] ?? '',
		'voucher_date' => $row['voucher_date'] ?? '',
		'entry_type' => $entry_type,
		'entry_type_label' => petty_cash_type_badge_label($entry_type),
		'amount' => petty_cash_format_money($row['amount'] ?? 0),
		'amount_raw' => round((float) ($row['amount'] ?? 0), 2),
		'particulars' => $row['particulars'] ?? '',
		'paid_to' => $row['paid_to'] ?? '',
		'vendor_id' => (int) ($row['vendor_id'] ?? 0),
		'vendor_label' => $vendor_label,
		'category_id' => (int) ($row['category_id'] ?? 0),
		'category_label' => $category_label,
		'ref_no' => $row['ref_no'] ?? '',
		'remarks' => $row['remarks'] ?? '',
		'updated_at' => $row['updated_at'] ?? ($row['created_at'] ?? ''),
	);
}

function petty_cash_save($conn, $payload, $user_id)
{
	petty_cash_ensure_schema($conn);

	$petty_cash_id = (int) ($payload['petty_cash_id'] ?? 0);
	$date_check = petty_cash_validate_date($payload['voucher_date'] ?? '');
	if (empty($date_check['ok'])) {
		return array('status' => 1, 'message' => $date_check['message']);
	}
	$voucher_date = $date_check['date'];
	$entry_type = petty_cash_normalize_entry_type($payload['entry_type'] ?? 'payment');
	$amount = petty_cash_parse_money($payload['amount'] ?? 0);
	$particulars = trim((string) ($payload['particulars'] ?? ''));
	$paid_to = trim((string) ($payload['paid_to'] ?? ''));
	$vendor_id = (int) ($payload['vendor_id'] ?? 0);
	$category_id = (int) ($payload['category_id'] ?? 0);
	$ref_no = trim((string) ($payload['ref_no'] ?? ''));
	$remarks = trim((string) ($payload['remarks'] ?? ''));
	$user_id = (int) $user_id;
	$now = date('d-m-Y H:i:s');

	if ($amount <= 0) {
		return array('status' => 1, 'message' => 'Amount must be greater than zero.');
	}
	if ($particulars === '') {
		return array('status' => 1, 'message' => 'Particulars are required.');
	}
	if (strlen($particulars) > 500) {
		$particulars = substr($particulars, 0, 500);
	}
	if (strlen($paid_to) > 150) {
		$paid_to = substr($paid_to, 0, 150);
	}
	if (strlen($ref_no) > 50) {
		$ref_no = substr($ref_no, 0, 50);
	}
	if (strlen($remarks) > 500) {
		$remarks = substr($remarks, 0, 500);
	}
	if ($vendor_id > 0 && petty_cash_vendor_label($conn, $vendor_id) === '') {
		return array('status' => 1, 'message' => 'Selected vendor is invalid.');
	}
	if ($entry_type === 'receipt') {
		$paid_to = '';
		$category_id = 0;
	}

	/* Soft guard: payment should not push balance deeply negative on create;
	   allow edit of existing payment even if balance is low. */
	if ($entry_type === 'payment') {
		$sum = petty_cash_summary($conn);
		$available = (float) $sum['balance_raw'];
		if ($petty_cash_id > 0) {
			$existing = petty_cash_load($conn, $petty_cash_id);
			if ($existing && ($existing['entry_type'] ?? '') === 'payment') {
				$available = round($available + (float) ($existing['amount_raw'] ?? 0), 2);
			} elseif ($existing && ($existing['entry_type'] ?? '') === 'receipt') {
				$available = round($available - (float) ($existing['amount_raw'] ?? 0), 2);
			}
		}
		if ($amount > $available + 0.001) {
			return array(
				'status' => 1,
				'message' => 'Payment exceeds available petty cash balance (₹' . petty_cash_format_money($available) . '). Add a receipt (top-up) first.',
			);
		}
	}

	$voucher_date_esc = mysqli_real_escape_string($conn, $voucher_date);
	$entry_type_esc = mysqli_real_escape_string($conn, $entry_type);
	$particulars_esc = mysqli_real_escape_string($conn, $particulars);
	$paid_to_esc = mysqli_real_escape_string($conn, $paid_to);
	$ref_no_esc = mysqli_real_escape_string($conn, $ref_no);
	$remarks_esc = mysqli_real_escape_string($conn, $remarks);
	$now_esc = mysqli_real_escape_string($conn, $now);
	$amount_esc = petty_cash_format_money($amount);

	if ($petty_cash_id > 0) {
		$existing = petty_cash_load($conn, $petty_cash_id);
		if (!$existing) {
			return array('status' => 1, 'message' => 'Petty cash voucher not found.');
		}
		$ok = mysqli_query($conn, "UPDATE petty_cash_voucher SET
			voucher_date='$voucher_date_esc',
			entry_type='$entry_type_esc',
			amount='$amount_esc',
			particulars='$particulars_esc',
			paid_to='$paid_to_esc',
			vendor_id='$vendor_id',
			category_id='$category_id',
			ref_no='$ref_no_esc',
			remarks='$remarks_esc',
			updated_at='$now_esc',
			updated_by='$user_id'
			WHERE petty_cash_id='$petty_cash_id' AND status=0");
		if (!$ok) {
			return array('status' => 1, 'message' => 'Could not update voucher.');
		}
		return array(
			'status' => 0,
			'message' => 'Petty cash voucher updated.',
			'petty_cash_id' => $petty_cash_id,
			'voucher_no' => $existing['voucher_no'],
		);
	}

	$ok = mysqli_query($conn, "INSERT INTO petty_cash_voucher
		(voucher_no, voucher_date, entry_type, amount, particulars, paid_to, vendor_id, category_id, ref_no, remarks,
		 status, created_at, created_by, updated_at, updated_by)
		VALUES ('', '$voucher_date_esc', '$entry_type_esc', '$amount_esc', '$particulars_esc', '$paid_to_esc',
		 '$vendor_id', '$category_id', '$ref_no_esc', '$remarks_esc', 0, '$now_esc', '$user_id', '$now_esc', '$user_id')");
	if (!$ok) {
		return array('status' => 1, 'message' => 'Could not save voucher.');
	}
	$new_id = (int) mysqli_insert_id($conn);
	$voucher_no = petty_cash_next_no($conn, $new_id);
	$voucher_no_esc = mysqli_real_escape_string($conn, $voucher_no);
	mysqli_query($conn, "UPDATE petty_cash_voucher SET voucher_no='$voucher_no_esc' WHERE petty_cash_id='$new_id'");

	return array(
		'status' => 0,
		'message' => 'Petty cash voucher saved.',
		'petty_cash_id' => $new_id,
		'voucher_no' => $voucher_no,
	);
}

function petty_cash_delete($conn, $petty_cash_id, $user_id)
{
	petty_cash_ensure_schema($conn);
	$petty_cash_id = (int) $petty_cash_id;
	$user_id = (int) $user_id;
	if ($petty_cash_id <= 0) {
		return array('status' => 1, 'message' => 'Invalid voucher.');
	}
	$existing = petty_cash_load($conn, $petty_cash_id);
	if (!$existing) {
		return array('status' => 1, 'message' => 'Petty cash voucher not found.');
	}

	/* Deleting a receipt must not leave payments overdrawn */
	if (($existing['entry_type'] ?? '') === 'receipt') {
		$sum = petty_cash_summary($conn);
		$after = round((float) $sum['balance_raw'] - (float) ($existing['amount_raw'] ?? 0), 2);
		if ($after < -0.001) {
			return array(
				'status' => 1,
				'message' => 'Cannot delete this receipt — payments already used this cash. Delete related payments first.',
			);
		}
	}

	$now_esc = mysqli_real_escape_string($conn, date('d-m-Y H:i:s'));
	$ok = mysqli_query($conn, "UPDATE petty_cash_voucher SET
		status=1, updated_at='$now_esc', updated_by='$user_id'
		WHERE petty_cash_id='$petty_cash_id' AND status=0");
	if (!$ok) {
		return array('status' => 1, 'message' => 'Could not delete voucher.');
	}
	return array(
		'status' => 0,
		'message' => 'Petty cash voucher deleted.',
		'voucher_no' => $existing['voucher_no'],
	);
}
