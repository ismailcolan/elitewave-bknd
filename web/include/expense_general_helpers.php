<?php
require_once __DIR__ . '/expense_schema.php';
require_once __DIR__ . '/expense_gcn_helpers.php';
require_once __DIR__ . '/company_bank_helpers.php';

function expense_general_ensure_schema($conn)
{
	expense_gcn_ensure_schema($conn);
	ew_company_bank_ensure_schema($conn);

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS general_expense_header (
		general_expense_id INT(11) NOT NULL AUTO_INCREMENT,
		expense_no VARCHAR(20) NOT NULL DEFAULT '',
		expense_date VARCHAR(20) DEFAULT '',
		remarks VARCHAR(500) DEFAULT '',
		expenses_without_gst DECIMAL(14,2) NOT NULL DEFAULT 0,
		total_gst DECIMAL(14,2) NOT NULL DEFAULT 0,
		total_tds DECIMAL(14,2) NOT NULL DEFAULT 0,
		net_payable DECIMAL(14,2) NOT NULL DEFAULT 0,
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (general_expense_id),
		UNIQUE KEY uk_general_expense_no (expense_no)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS general_expense_lines (
		line_id INT(11) NOT NULL AUTO_INCREMENT,
		general_expense_id INT(11) NOT NULL,
		line_no INT(11) NOT NULL DEFAULT 1,
		vendor_id INT(11) NOT NULL DEFAULT 0,
		category_id INT(11) NOT NULL DEFAULT 0,
		expense_date VARCHAR(20) DEFAULT '',
		expense_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		tds_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		PRIMARY KEY (line_id),
		KEY idx_general_expense_lines_header (general_expense_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$header_cols = array(
		'net_amount' => "ALTER TABLE general_expense_header ADD COLUMN net_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER remarks",
		'profit_amount' => "ALTER TABLE general_expense_header ADD COLUMN profit_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER net_payable",
		'vendor_id' => "ALTER TABLE general_expense_header ADD COLUMN vendor_id INT(11) NOT NULL DEFAULT 0 AFTER expense_date",
		'category_id' => "ALTER TABLE general_expense_header ADD COLUMN category_id INT(11) NOT NULL DEFAULT 0 AFTER vendor_id",
		'expense_amount' => "ALTER TABLE general_expense_header ADD COLUMN expense_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER category_id",
		'other_deduction' => "ALTER TABLE general_expense_header ADD COLUMN other_deduction DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER total_tds",
		'description' => "ALTER TABLE general_expense_header ADD COLUMN description VARCHAR(500) DEFAULT '' AFTER other_deduction",
		'payment_mode' => "ALTER TABLE general_expense_header ADD COLUMN payment_mode VARCHAR(20) DEFAULT '' AFTER description",
		'company_bank_id' => "ALTER TABLE general_expense_header ADD COLUMN company_bank_id INT(11) NOT NULL DEFAULT 0 AFTER payment_mode",
		'payment_bank_name' => "ALTER TABLE general_expense_header ADD COLUMN payment_bank_name VARCHAR(150) DEFAULT '' AFTER company_bank_id",
		'payment_ifsc' => "ALTER TABLE general_expense_header ADD COLUMN payment_ifsc VARCHAR(11) DEFAULT '' AFTER payment_bank_name",
		'payment_bank_branch' => "ALTER TABLE general_expense_header ADD COLUMN payment_bank_branch VARCHAR(150) DEFAULT '' AFTER payment_ifsc",
	);
	foreach ($header_cols as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM general_expense_header LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}
}

function expense_general_next_no($conn, $general_expense_id)
{
	$id = (int) $general_expense_id;
	return 'GEN' . sprintf('%05d', $id);
}

function expense_general_preview_next_no($conn)
{
	expense_general_ensure_schema($conn);
	$next_id = 1;
	$q = mysqli_query($conn, 'SELECT MAX(general_expense_id) AS max_id FROM general_expense_header');
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$next_id = (int) ($row['max_id'] ?? 0) + 1;
	}
	return expense_general_next_no($conn, $next_id);
}

function expense_general_vendor_tax_info($conn, $vendor_id)
{
	ew_vendor_ensure_table($conn);
	$vendor_id = (int) $vendor_id;
	$defaults = array(
		'vendor_id' => $vendor_id,
		'gst_registered' => 0,
		'gst_exemption' => 0,
		'tds_applicable' => 0,
		'tds_rate' => 0,
		'gst_applicable' => 0,
	);
	if ($vendor_id <= 0) {
		return $defaults;
	}
	$q = mysqli_query($conn, "SELECT vendor_id, gst_registered, gst_exemption, tds_applicable, tds_rate, gstin
		FROM vendor_master WHERE vendor_id='$vendor_id' AND status=0 LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return $defaults;
	}
	$gst_registered = (int) ($row['gst_registered'] ?? 0);
	if ($gst_registered !== 1 && trim($row['gstin'] ?? '') !== '') {
		$gst_registered = 1;
	}
	$gst_exemption = (int) ($row['gst_exemption'] ?? 0);
	$gst_applicable = ($gst_registered === 1 && $gst_exemption !== 1) ? 1 : 0;
	return array(
		'vendor_id' => $vendor_id,
		'gst_registered' => $gst_registered,
		'gst_exemption' => $gst_exemption,
		'tds_applicable' => (int) ($row['tds_applicable'] ?? 0),
		'tds_rate' => round((float) ($row['tds_rate'] ?? 0), 2),
		'gst_applicable' => $gst_applicable,
	);
}

function expense_general_category_tax_info($conn, $category_id)
{
	expense_type_ensure_schema($conn);
	$category_id = (int) $category_id;
	$defaults = array(
		'category_id' => $category_id,
		'gst_applicable' => 0,
		'gst_percent' => 0,
		'tds_applicable' => 0,
		'tds_percent' => 0,
	);
	if ($category_id <= 0) {
		return $defaults;
	}
	$q = mysqli_query($conn, "SELECT category_id, gst_applicable, gst_percent, tds_applicable, tds_percent
		FROM expense_category WHERE category_id='$category_id' AND status=0 LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return $defaults;
	}
	return array(
		'category_id' => $category_id,
		'gst_applicable' => (int) ($row['gst_applicable'] ?? 0),
		'gst_percent' => round((float) ($row['gst_percent'] ?? 0), 2),
		'tds_applicable' => (int) ($row['tds_applicable'] ?? 0),
		'tds_percent' => round((float) ($row['tds_percent'] ?? 0), 2),
	);
}

function expense_general_calc_amounts($expense_amount, $gst_amount, $tds_amount, $other_deduction)
{
	$expense_amount = round((float) $expense_amount, 2);
	$gst_amount = round((float) $gst_amount, 2);
	$tds_amount = round((float) $tds_amount, 2);
	$other_deduction = round((float) $other_deduction, 2);
	$net_payable = round($expense_amount + $gst_amount - $tds_amount - $other_deduction, 2);
	return array(
		'expense_amount' => $expense_amount,
		'gst_amount' => $gst_amount,
		'tds_amount' => $tds_amount,
		'other_deduction' => $other_deduction,
		'net_payable' => $net_payable,
	);
}

function expense_general_suggest_tax($conn, $vendor_id, $category_id, $expense_amount)
{
	$vendor = expense_general_vendor_tax_info($conn, $vendor_id);
	$category = expense_general_category_tax_info($conn, $category_id);
	$expense_amount = round((float) $expense_amount, 2);

	$gst_amount = 0;
	if (!empty($vendor['gst_applicable'])) {
		$gst_percent = 0;
		if (!empty($category['gst_applicable']) && (float) $category['gst_percent'] > 0) {
			$gst_percent = (float) $category['gst_percent'];
		}
		if ($gst_percent > 0 && $expense_amount > 0) {
			$gst_amount = round($expense_amount * $gst_percent / 100, 2);
		}
	}

	$tds_amount = 0;
	$tds_rate = 0;
	if (!empty($vendor['tds_applicable']) && (float) $vendor['tds_rate'] > 0) {
		$tds_rate = (float) $vendor['tds_rate'];
	} elseif (!empty($category['tds_applicable']) && (float) $category['tds_percent'] > 0) {
		$tds_rate = (float) $category['tds_percent'];
	}
	if ($tds_rate > 0 && $expense_amount > 0) {
		$tds_amount = round($expense_amount * $tds_rate / 100, 2);
	}

	return array(
		'vendor' => $vendor,
		'category' => $category,
		'gst_amount' => $gst_amount,
		'tds_amount' => $tds_amount,
		'tds_rate' => $tds_rate,
	);
}

function expense_general_legacy_line($conn, $general_expense_id)
{
	$gid = (int) $general_expense_id;
	$q = mysqli_query($conn, "SELECT * FROM general_expense_lines WHERE general_expense_id='$gid' ORDER BY line_no ASC, line_id ASC LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return $row;
	}
	return null;
}

function expense_general_normalize_header($conn, $header)
{
	if ((int) ($header['vendor_id'] ?? 0) <= 0) {
		$legacy = expense_general_legacy_line($conn, (int) ($header['general_expense_id'] ?? 0));
		if ($legacy) {
			$header['vendor_id'] = (int) ($legacy['vendor_id'] ?? 0);
			$header['category_id'] = (int) ($legacy['category_id'] ?? 0);
			if ((float) ($header['expense_amount'] ?? 0) <= 0) {
				$header['expense_amount'] = $legacy['expense_amount'] ?? 0;
			}
			if ((float) ($header['total_gst'] ?? 0) <= 0) {
				$header['total_gst'] = $legacy['gst_amount'] ?? 0;
			}
			if ((float) ($header['total_tds'] ?? 0) <= 0) {
				$header['total_tds'] = $legacy['tds_amount'] ?? 0;
			}
			if (trim($header['expense_date'] ?? '') === '' && trim($legacy['expense_date'] ?? '') !== '') {
				$header['expense_date'] = $legacy['expense_date'];
			}
		}
	}
	if (trim($header['description'] ?? '') === '' && trim($header['remarks'] ?? '') !== '') {
		$header['description'] = $header['remarks'];
	}
	if ((float) ($header['expense_amount'] ?? 0) <= 0 && (float) ($header['expenses_without_gst'] ?? 0) > 0) {
		$header['expense_amount'] = $header['expenses_without_gst'];
	}
	return $header;
}

function expense_general_format_record($conn, $header)
{
	$header = expense_general_normalize_header($conn, $header);
	$vendor_id = (int) ($header['vendor_id'] ?? 0);
	$category_id = (int) ($header['category_id'] ?? 0);
	$vendor_label = '';
	$category_label = '';

	if ($vendor_id > 0) {
		$vq = mysqli_query($conn, "SELECT vendor_code, vendor_name FROM vendor_master WHERE vendor_id='$vendor_id' LIMIT 1");
		if ($vq && ($v = mysqli_fetch_assoc($vq))) {
			$vendor_label = trim(($v['vendor_code'] ?? '') . ' - ' . ($v['vendor_name'] ?? ''), ' -');
		}
	}
	if ($category_id > 0) {
		$cq = mysqli_query($conn, "SELECT category_code, category_name FROM expense_category WHERE category_id='$category_id' LIMIT 1");
		if ($cq && ($c = mysqli_fetch_assoc($cq))) {
			$category_label = trim(($c['category_code'] ?? '') . ' - ' . ($c['category_name'] ?? ''), ' -');
		}
	}

	$amounts = expense_general_calc_amounts(
		$header['expense_amount'] ?? 0,
		$header['total_gst'] ?? 0,
		$header['total_tds'] ?? 0,
		$header['other_deduction'] ?? 0
	);

	return array(
		'general_expense_id' => (int) ($header['general_expense_id'] ?? 0),
		'expense_no' => $header['expense_no'] ?? '',
		'expense_date' => $header['expense_date'] ?? '',
		'vendor_id' => $vendor_id,
		'category_id' => $category_id,
		'vendor_label' => $vendor_label,
		'category_label' => $category_label,
		'expense_amount' => expense_gcn_format_money($amounts['expense_amount']),
		'expense_amount_raw' => $amounts['expense_amount'],
		'gst_amount' => expense_gcn_format_money($amounts['gst_amount']),
		'gst_amount_raw' => $amounts['gst_amount'],
		'tds_amount' => expense_gcn_format_money($amounts['tds_amount']),
		'tds_amount_raw' => $amounts['tds_amount'],
		'other_deduction' => expense_gcn_format_money($amounts['other_deduction']),
		'other_deduction_raw' => $amounts['other_deduction'],
		'net_payable' => expense_gcn_format_money($amounts['net_payable']),
		'net_payable_raw' => $amounts['net_payable'],
		'description' => $header['description'] ?? ($header['remarks'] ?? ''),
		'payment_mode' => strtoupper(trim($header['payment_mode'] ?? '')),
		'company_bank_id' => (int) ($header['company_bank_id'] ?? 0),
		'payment_bank_name' => $header['payment_bank_name'] ?? '',
		'payment_ifsc' => strtoupper($header['payment_ifsc'] ?? ''),
		'payment_bank_branch' => $header['payment_bank_branch'] ?? '',
		'updated_at' => $header['updated_at'] ?? ($header['created_at'] ?? ''),
	);
}

function expense_general_load($conn, $general_expense_id)
{
	expense_general_ensure_schema($conn);
	$general_expense_id = (int) $general_expense_id;
	if ($general_expense_id <= 0) {
		return null;
	}
	$hq = mysqli_query($conn, "SELECT * FROM general_expense_header WHERE general_expense_id='$general_expense_id' LIMIT 1");
	if (!$hq || !($header = mysqli_fetch_assoc($hq))) {
		return null;
	}
	return expense_general_format_record($conn, $header);
}

function expense_general_save($conn, $payload, $user_id)
{
	expense_general_ensure_schema($conn);

	$expense_date = trim((string) ($payload['expense_date'] ?? ''));
	$vendor_id = (int) ($payload['vendor_id'] ?? 0);
	$category_id = (int) ($payload['category_id'] ?? 0);
	$expense_amount = round(expense_gcn_parse_money($payload['expense_amount'] ?? 0), 2);
	$gst_amount = round(expense_gcn_parse_money($payload['gst_amount'] ?? 0), 2);
	$tds_amount = round(expense_gcn_parse_money($payload['tds_amount'] ?? 0), 2);
	$other_deduction = round(expense_gcn_parse_money($payload['other_deduction'] ?? 0), 2);
	$description = trim((string) ($payload['description'] ?? ''));
	$payment_mode = strtoupper(trim((string) ($payload['payment_mode'] ?? '')));
	$company_bank_id = (int) ($payload['company_bank_id'] ?? 0);
	$payment_bank_name = trim((string) ($payload['payment_bank_name'] ?? ''));
	$payment_ifsc = strtoupper(trim((string) ($payload['payment_ifsc'] ?? '')));
	$payment_bank_branch = trim((string) ($payload['payment_bank_branch'] ?? ''));

	if ($expense_date === '') {
		return array('ok' => false, 'message' => 'Please enter expense date.');
	}
	if ($vendor_id <= 0) {
		return array('ok' => false, 'message' => 'Please select vendor.');
	}
	if ($category_id <= 0) {
		return array('ok' => false, 'message' => 'Please select expense type.');
	}
	if ($expense_amount <= 0) {
		return array('ok' => false, 'message' => 'Please enter expense amount.');
	}
	if ($payment_mode === '') {
		return array('ok' => false, 'message' => 'Please select payment mode.');
	}

	$modes = ew_company_bank_payment_modes();
	if (!isset($modes[$payment_mode])) {
		return array('ok' => false, 'message' => 'Invalid payment mode selected.');
	}

	$vendor_tax = expense_general_vendor_tax_info($conn, $vendor_id);
	if (empty($vendor_tax['gst_applicable'])) {
		$gst_amount = 0;
	}
	if ($gst_amount < 0 || $tds_amount < 0 || $other_deduction < 0) {
		return array('ok' => false, 'message' => 'GST, TDS and other deduction cannot be negative.');
	}

	if (ew_company_bank_mode_requires_account($payment_mode)) {
		if ($company_bank_id <= 0) {
			return array('ok' => false, 'message' => 'Please select company bank account for this payment mode.');
		}
		$bank = ew_company_bank_get($conn, $company_bank_id);
		if (!$bank || (int) ($bank['status'] ?? 0) !== 0) {
			return array('ok' => false, 'message' => 'Selected company bank account is invalid.');
		}
		$payment_bank_name = $bank['bank_name'] ?? $payment_bank_name;
		$payment_ifsc = strtoupper($bank['ifsc'] ?? $payment_ifsc);
		$payment_bank_branch = $bank['bank_branch'] ?? $payment_bank_branch;
	} elseif ($company_bank_id > 0) {
		$bank = ew_company_bank_get($conn, $company_bank_id);
		if ($bank && (int) ($bank['status'] ?? 0) === 0) {
			$payment_bank_name = $bank['bank_name'] ?? $payment_bank_name;
			$payment_ifsc = strtoupper($bank['ifsc'] ?? $payment_ifsc);
			$payment_bank_branch = $bank['bank_branch'] ?? $payment_bank_branch;
		}
	}

	$amounts = expense_general_calc_amounts($expense_amount, $gst_amount, $tds_amount, $other_deduction);
	if ($amounts['net_payable'] < 0) {
		return array('ok' => false, 'message' => 'Net payable cannot be negative.');
	}

	$user_id = (int) $user_id;
	$now = date('d-m-Y');
	$existing_id = (int) ($payload['general_expense_id'] ?? 0);
	$expense_date_esc = mysqli_real_escape_string($conn, $expense_date);
	$description_esc = mysqli_real_escape_string($conn, $description);
	$payment_mode_esc = mysqli_real_escape_string($conn, $payment_mode);
	$payment_bank_name_esc = mysqli_real_escape_string($conn, $payment_bank_name);
	$payment_ifsc_esc = mysqli_real_escape_string($conn, $payment_ifsc);
	$payment_bank_branch_esc = mysqli_real_escape_string($conn, $payment_bank_branch);

	$expenses_without_gst = $amounts['expense_amount'];
	$total_gst = $amounts['gst_amount'];
	$total_tds = $amounts['tds_amount'];
	$other_deduction_val = $amounts['other_deduction'];
	$net_payable = $amounts['net_payable'];

	if ($existing_id > 0) {
		$chk = mysqli_query($conn, "SELECT general_expense_id, expense_no FROM general_expense_header WHERE general_expense_id='$existing_id' LIMIT 1");
		if (!$chk || !($existing = mysqli_fetch_assoc($chk))) {
			return array('ok' => false, 'message' => 'Expense record not found.');
		}
		$gid = $existing_id;
		mysqli_query($conn, "UPDATE general_expense_header SET
			expense_date='$expense_date_esc',
			vendor_id='$vendor_id',
			category_id='$category_id',
			expense_amount='$expenses_without_gst',
			remarks='$description_esc',
			description='$description_esc',
			expenses_without_gst='$expenses_without_gst',
			total_gst='$total_gst',
			total_tds='$total_tds',
			other_deduction='$other_deduction_val',
			net_payable='$net_payable',
			net_amount='0',
			profit_amount='0',
			payment_mode='$payment_mode_esc',
			company_bank_id='$company_bank_id',
			payment_bank_name='$payment_bank_name_esc',
			payment_ifsc='$payment_ifsc_esc',
			payment_bank_branch='$payment_bank_branch_esc',
			updated_at='$now',
			updated_by='$user_id'
			WHERE general_expense_id='$gid'");
		$expense_no = $existing['expense_no'] ?? expense_general_next_no($conn, $gid);
	} else {
		mysqli_query($conn, "INSERT INTO general_expense_header
			(expense_no, expense_date, vendor_id, category_id, expense_amount, remarks, description,
			expenses_without_gst, total_gst, total_tds, other_deduction, net_payable, net_amount, profit_amount,
			payment_mode, company_bank_id, payment_bank_name, payment_ifsc, payment_bank_branch,
			status, created_at, created_by, updated_at, updated_by)
			VALUES ('', '$expense_date_esc', '$vendor_id', '$category_id', '$expenses_without_gst', '$description_esc', '$description_esc',
			'$expenses_without_gst', '$total_gst', '$total_tds', '$other_deduction_val', '$net_payable', '0', '0',
			'$payment_mode_esc', '$company_bank_id', '$payment_bank_name_esc', '$payment_ifsc_esc', '$payment_bank_branch_esc',
			0, '$now', '$user_id', '$now', '$user_id')");
		$gid = (int) mysqli_insert_id($conn);
		if ($gid <= 0) {
			return array('ok' => false, 'message' => 'Could not save expense.');
		}
		$expense_no = expense_general_next_no($conn, $gid);
		$expense_no_esc = mysqli_real_escape_string($conn, $expense_no);
		mysqli_query($conn, "UPDATE general_expense_header SET expense_no='$expense_no_esc' WHERE general_expense_id='$gid'");
	}

	mysqli_query($conn, "DELETE FROM general_expense_lines WHERE general_expense_id='$gid'");

	return array(
		'ok' => true,
		'message' => 'General expense saved successfully.',
		'general_expense_id' => $gid,
		'expense_no' => $expense_no,
		'record' => expense_general_format_record($conn, array(
			'general_expense_id' => $gid,
			'expense_no' => $expense_no,
			'expense_date' => $expense_date,
			'vendor_id' => $vendor_id,
			'category_id' => $category_id,
			'expense_amount' => $expenses_without_gst,
			'total_gst' => $total_gst,
			'total_tds' => $total_tds,
			'other_deduction' => $other_deduction_val,
			'net_payable' => $net_payable,
			'description' => $description,
			'payment_mode' => $payment_mode,
			'company_bank_id' => $company_bank_id,
			'payment_bank_name' => $payment_bank_name,
			'payment_ifsc' => $payment_ifsc,
			'payment_bank_branch' => $payment_bank_branch,
			'updated_at' => $now,
		)),
	);
}

function expense_general_fetch_list($conn)
{
	expense_general_ensure_schema($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT h.* FROM general_expense_header h ORDER BY h.general_expense_id DESC");
	if (!$q) {
		return $rows;
	}
	while ($h = mysqli_fetch_assoc($q)) {
		$record = expense_general_format_record($conn, $h);
		$rows[] = $record;
	}
	return $rows;
}

function expense_general_delete($conn, $general_expense_id)
{
	expense_general_ensure_schema($conn);
	$general_expense_id = (int) $general_expense_id;
	if ($general_expense_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid expense record.');
	}
	$chk = mysqli_query($conn, "SELECT general_expense_id FROM general_expense_header WHERE general_expense_id='$general_expense_id' LIMIT 1");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		return array('ok' => false, 'message' => 'Expense record not found.');
	}
	mysqli_query($conn, "DELETE FROM general_expense_lines WHERE general_expense_id='$general_expense_id'");
	mysqli_query($conn, "DELETE FROM general_expense_header WHERE general_expense_id='$general_expense_id'");
	return array('ok' => true, 'message' => 'General expense deleted successfully.');
}
