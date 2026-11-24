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
		'gst_percent' => "ALTER TABLE general_expense_header ADD COLUMN gst_percent DECIMAL(6,2) NOT NULL DEFAULT 0 AFTER expense_amount",
		'other_deduction' => "ALTER TABLE general_expense_header ADD COLUMN other_deduction DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER total_tds",
		'description' => "ALTER TABLE general_expense_header ADD COLUMN description VARCHAR(500) DEFAULT '' AFTER other_deduction",
		'payment_mode' => "ALTER TABLE general_expense_header ADD COLUMN payment_mode VARCHAR(20) DEFAULT '' AFTER description",
		'company_bank_id' => "ALTER TABLE general_expense_header ADD COLUMN company_bank_id INT(11) NOT NULL DEFAULT 0 AFTER payment_mode",
		'payment_bank_name' => "ALTER TABLE general_expense_header ADD COLUMN payment_bank_name VARCHAR(150) DEFAULT '' AFTER company_bank_id",
		'payment_ifsc' => "ALTER TABLE general_expense_header ADD COLUMN payment_ifsc VARCHAR(11) DEFAULT '' AFTER payment_bank_name",
		'payment_bank_branch' => "ALTER TABLE general_expense_header ADD COLUMN payment_bank_branch VARCHAR(150) DEFAULT '' AFTER payment_ifsc",
		'voucher_type' => "ALTER TABLE general_expense_header ADD COLUMN voucher_type VARCHAR(40) NOT NULL DEFAULT 'general' AFTER expense_date",
		'tds_percent' => "ALTER TABLE general_expense_header ADD COLUMN tds_percent DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER gst_percent",
		'vendor_bill_id' => "ALTER TABLE general_expense_header ADD COLUMN vendor_bill_id INT(11) NOT NULL DEFAULT 0 AFTER vendor_id",
	);
	foreach ($header_cols as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM general_expense_header LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}

	$line_cols = array(
		'gst_percent' => "ALTER TABLE general_expense_lines ADD COLUMN gst_percent DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER expense_amount",
		'tds_percent' => "ALTER TABLE general_expense_lines ADD COLUMN tds_percent DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER gst_amount",
		'other_deduction' => "ALTER TABLE general_expense_lines ADD COLUMN other_deduction DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tds_amount",
		'net_amount' => "ALTER TABLE general_expense_lines ADD COLUMN net_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER other_deduction",
		'gcn_key' => "ALTER TABLE general_expense_lines ADD COLUMN gcn_key VARCHAR(150) DEFAULT '' AFTER category_id",
		'gcn_no' => "ALTER TABLE general_expense_lines ADD COLUMN gcn_no VARCHAR(50) DEFAULT '' AFTER gcn_key",
		'gcn_date' => "ALTER TABLE general_expense_lines ADD COLUMN gcn_date VARCHAR(20) DEFAULT '' AFTER gcn_no",
		'freight_amount' => "ALTER TABLE general_expense_lines ADD COLUMN freight_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER expense_amount",
	);
	foreach ($line_cols as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM general_expense_lines LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}
}

function expense_general_next_no($conn, $general_expense_id)
{
	$id = (int) $general_expense_id;
	return 'EMPV' . sprintf('%05d', $id);
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

function expense_general_gst_percent_options()
{
	return array(0, 5, 12, 18, 28);
}

function expense_general_normalize_gst_percent($gst_percent)
{
	$gst_percent = round((float) $gst_percent, 2);
	if ($gst_percent < 0) {
		$gst_percent = 0;
	}
	if ($gst_percent > 100) {
		$gst_percent = 100;
	}
	$allowed = expense_general_gst_percent_options();
	foreach ($allowed as $rate) {
		if (abs($gst_percent - (float) $rate) < 0.001) {
			return (float) $rate;
		}
	}
	$best = 0.0;
	$best_diff = null;
	foreach ($allowed as $rate) {
		$diff = abs($gst_percent - (float) $rate);
		if ($best_diff === null || $diff < $best_diff) {
			$best_diff = $diff;
			$best = (float) $rate;
		}
	}
	return $best;
}

function expense_general_gst_amount_from_percent($vendor, $expense_amount, $gst_percent)
{
	if (empty($vendor['gst_applicable'])) {
		return 0;
	}
	$gst_percent = expense_general_normalize_gst_percent($gst_percent);
	$expense_amount = round((float) $expense_amount, 2);
	if ($gst_percent <= 0 || $expense_amount <= 0) {
		return 0;
	}
	return round($expense_amount * $gst_percent / 100, 2);
}

function expense_general_suggest_tax($conn, $vendor_id, $category_id, $expense_amount, $gst_percent_override = null)
{
	$vendor = expense_general_vendor_tax_info($conn, $vendor_id);
	$category = expense_general_category_tax_info($conn, $category_id);
	$expense_amount = round((float) $expense_amount, 2);

	$gst_percent = 0;
	if ($gst_percent_override !== null && $gst_percent_override !== '') {
		$gst_percent = expense_general_normalize_gst_percent($gst_percent_override);
	} elseif (!empty($vendor['gst_applicable']) && !empty($category['gst_applicable']) && (float) $category['gst_percent'] > 0) {
		$gst_percent = expense_general_normalize_gst_percent($category['gst_percent']);
	}
	$gst_amount = expense_general_gst_amount_from_percent($vendor, $expense_amount, $gst_percent);

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
		'gst_percent' => $gst_percent,
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

	$gst_percent = expense_general_normalize_gst_percent($header['gst_percent'] ?? 0);
	if ($gst_percent <= 0 && $amounts['gst_amount'] > 0 && $amounts['expense_amount'] > 0) {
		$gst_percent = expense_general_normalize_gst_percent($amounts['gst_amount'] / $amounts['expense_amount'] * 100);
	}

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
		'gst_percent' => $gst_percent,
		'gst_percent_raw' => $gst_percent,
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
		'voucher_type' => strtolower(trim((string) ($header['voucher_type'] ?? 'general'))) ?: 'general',
		'tds_percent' => round((float) ($header['tds_percent'] ?? 0), 2),
		'vendor_bill_id' => (int) ($header['vendor_bill_id'] ?? 0),
		'lines' => expense_general_load_lines($conn, (int) ($header['general_expense_id'] ?? 0), $header),
		'updated_at' => $header['updated_at'] ?? ($header['created_at'] ?? ''),
	);
}

function expense_general_tax_profiles($conn)
{
	require_once __DIR__ . '/gst_tax_functions.php';
	$rows = gst_tax_get_active_profiles($conn);
	$out = array();
	foreach ($rows as $row) {
		$out[] = array(
			'gst_tax_id' => (int) ($row['gst_tax_id'] ?? 0),
			'tax_code' => $row['tax_code'] ?? '',
			'tax_name' => $row['tax_name'] ?? '',
			'gst_rate' => round((float) ($row['gst_rate'] ?? 0), 2),
			'tds_rate' => round((float) ($row['tds_rate'] ?? 0), 2),
		);
	}
	return $out;
}

function expense_general_resolve_gcn($conn, $gcn_key)
{
	$gcn_key = trim((string) $gcn_key);
	if ($gcn_key === '' || strpos($gcn_key, '|') === false) {
		return null;
	}
	list($trans_table, $transaction_id) = explode('|', $gcn_key, 2);
	$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
	$transaction_id = (int) $transaction_id;
	if ($trans_table === '' || $transaction_id <= 0) {
		return null;
	}
	$chk = @mysqli_query($conn, "SELECT 1 FROM `$trans_table` LIMIT 1");
	if (!$chk) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT transaction_id, grn_no, grn_date FROM `$trans_table` WHERE transaction_id='$transaction_id' LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return null;
	}
	$gcn_no = trim((string) ($row['grn_no'] ?? ''));
	$gcn_date = trim((string) ($row['grn_date'] ?? ''));
	return array(
		'gcn_key' => $trans_table . '|' . $transaction_id,
		'gcn_no' => $gcn_no,
		'gcn_date' => $gcn_date,
		'label' => $gcn_date !== '' ? ($gcn_no . ' | ' . $gcn_date) : $gcn_no,
	);
}

function expense_general_gcn_options($conn)
{
	if (!function_exists('expense_bill_fetch_gcns')) {
		require_once __DIR__ . '/expense_bill_helpers.php';
	}
	$rows = expense_bill_fetch_gcns($conn);
	foreach ($rows as $i => $row) {
		$gcn_no = trim((string) ($row['grn_no'] ?? ''));
		if ($gcn_no === '') {
			continue;
		}
		$rows[$i]['label'] = $gcn_no;
	}
	return $rows;
}

function expense_general_load_lines($conn, $general_expense_id, $header = null)
{
	$gid = (int) $general_expense_id;
	$lines = array();
	if ($gid > 0) {
		$q = mysqli_query($conn, "SELECT * FROM general_expense_lines WHERE general_expense_id='$gid' ORDER BY line_no ASC, line_id ASC");
		if ($q) {
			while ($row = mysqli_fetch_assoc($q)) {
				$amount = round((float) ($row['expense_amount'] ?? 0), 2);
				$gst = round((float) ($row['gst_amount'] ?? 0), 2);
				$tds = round((float) ($row['tds_amount'] ?? 0), 2);
				$other = round((float) ($row['other_deduction'] ?? 0), 2);
				$net = round((float) ($row['net_amount'] ?? 0), 2);
				if ($net <= 0 && ($amount > 0 || $gst > 0 || $tds > 0 || $other > 0)) {
					$net = round($amount + $gst - $tds - $other, 2);
				}
				$gcn_no = trim((string) ($row['gcn_no'] ?? ''));
				$gcn_date = trim((string) ($row['gcn_date'] ?? ''));
				$freight = round((float) ($row['freight_amount'] ?? 0), 2);
				$lines[] = array(
					'line_id' => (int) ($row['line_id'] ?? 0),
					'line_no' => (int) ($row['line_no'] ?? 0),
					'category_id' => (int) ($row['category_id'] ?? 0),
					'expense_date' => $row['expense_date'] ?? '',
					'gcn_key' => $row['gcn_key'] ?? '',
					'gcn_no' => $gcn_no,
					'gcn_date' => $gcn_date,
					'gcn_label' => $gcn_no,
					'freight_amount' => expense_gcn_format_money($freight),
					'freight_amount_raw' => $freight,
					'expense_amount' => expense_gcn_format_money($amount),
					'expense_amount_raw' => $amount,
					'gst_amount' => expense_gcn_format_money($gst),
					'gst_amount_raw' => $gst,
					'tds_amount' => expense_gcn_format_money($tds),
					'tds_amount_raw' => $tds,
					'other_deduction' => expense_gcn_format_money($other),
					'other_deduction_raw' => $other,
					'net_amount' => expense_gcn_format_money($net),
					'net_amount_raw' => $net,
				);
			}
		}
	}
	if (!empty($lines) || !is_array($header)) {
		return $lines;
	}
	$amount = round((float) ($header['expense_amount'] ?? 0), 2);
	if ($amount <= 0) {
		return $lines;
	}
	$gst = round((float) ($header['total_gst'] ?? 0), 2);
	$tds = round((float) ($header['total_tds'] ?? 0), 2);
	$other = round((float) ($header['other_deduction'] ?? 0), 2);
	$net = round($amount + $gst - $tds - $other, 2);
	$lines[] = array(
		'line_id' => 0,
		'category_id' => (int) ($header['category_id'] ?? 0),
		'gcn_key' => '',
		'gcn_no' => '',
		'gcn_date' => '',
		'gcn_label' => '',
		'expense_amount' => expense_gcn_format_money($amount),
		'expense_amount_raw' => $amount,
		'gst_amount' => expense_gcn_format_money($gst),
		'gst_amount_raw' => $gst,
		'tds_amount' => expense_gcn_format_money($tds),
		'tds_amount_raw' => $tds,
		'other_deduction' => expense_gcn_format_money($other),
		'other_deduction_raw' => $other,
		'net_amount' => expense_gcn_format_money($net),
		'net_amount_raw' => $net,
	);
	return $lines;
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

function expense_general_gcn_profit_pack($conn, $record)
{
	$lines = $record['lines'] ?? array();
	$expenses = 0.0;
	$keys = array();
	$category_ids = array();
	foreach ($lines as $line) {
		$expenses += (float) ($line['expense_amount_raw'] ?? 0);
		$key = trim((string) ($line['gcn_key'] ?? ''));
		if ($key !== '') {
			$keys[$key] = true;
		}
		$cid = (int) ($line['category_id'] ?? 0);
		if ($cid > 0) {
			$category_ids[$cid] = true;
		}
	}
	$expenses = round($expenses, 2);

	$category_labels = array();
	if (!empty($category_ids)) {
		$id_sql = implode(',', array_map('intval', array_keys($category_ids)));
		$cq = mysqli_query($conn, "SELECT category_id, category_name FROM expense_category WHERE category_id IN ($id_sql)");
		if ($cq) {
			while ($c = mysqli_fetch_assoc($cq)) {
				$category_labels[(int) $c['category_id']] = $c['category_name'] ?? '';
			}
		}
	}

	$contexts = array();
	$revenue = 0.0;
	foreach (array_keys($keys) as $key) {
		if (strpos($key, '|') === false) {
			continue;
		}
		list($trans_table, $transaction_id) = explode('|', $key, 2);
		$ctx = expense_gcn_fetch_context($conn, $trans_table, (int) $transaction_id);
		if (empty($ctx['ok'])) {
			continue;
		}
		$contexts[] = $ctx;
		$revenue += (float) ($ctx['revenue_without_gst_raw'] ?? 0);
	}
	$revenue = round($revenue, 2);
	$profit = round($revenue - $expenses, 2);
	$is_group = count($contexts) > 1;
	$first = $contexts[0] ?? array();

	$gcns = array();
	$routes = array();
	$modes = array();
	foreach ($contexts as $ctx) {
		$route = $ctx['route_label'] ?? '—';
		if ($route !== '' && $route !== '—') {
			$routes[$route] = true;
		}
		$mode_lbl = $ctx['mode_of_transport'] ?? '—';
		if ($mode_lbl !== '' && $mode_lbl !== '—') {
			$modes[$mode_lbl] = true;
		}
		$gcns[] = array(
			'grn_no' => $ctx['grn_no'] ?? '',
			'grn_date' => $ctx['grn_date'] ?? '',
			'route_label' => $route,
			'consignor' => $ctx['consignor'] ?? '',
			'consignee' => $ctx['consignee'] ?? '',
			'mode_of_transport' => $mode_lbl,
			'revenue_without_gst' => $ctx['revenue_without_gst'] ?? expense_gcn_format_money(0),
		);
	}
	$route_keys = array_keys($routes);
	if (count($route_keys) === 1) {
		$route_label = $route_keys[0];
	} elseif (count($route_keys) > 1) {
		$route_label = count($route_keys) . ' routes';
	} else {
		$route_label = $first['route_label'] ?? '—';
	}
	$mode_keys = array_keys($modes);
	if (count($mode_keys) === 1) {
		$mode_label = $mode_keys[0];
	} elseif (count($mode_keys) > 1) {
		$mode_label = implode(', ', $mode_keys);
	} else {
		$mode_label = $first['mode_of_transport'] ?? '—';
	}

	$vendor_label = $record['vendor_label'] ?? '—';
	$view_lines = array();
	$n = 1;
	foreach ($lines as $line) {
		$cid = (int) ($line['category_id'] ?? 0);
		$view_lines[] = array(
			'line_no' => (int) ($line['line_no'] ?? $n),
			'expense_date' => $line['expense_date'] ?? ($record['expense_date'] ?? ''),
			'vendor_label' => $vendor_label,
			'category_label' => $category_labels[$cid] ?? '—',
			'expense_amount' => $line['expense_amount'] ?? '0.00',
			'gst_amount' => $line['gst_amount'] ?? '0.00',
			'tds_amount' => $line['tds_amount'] ?? '0.00',
			'gcn_no' => $line['gcn_no'] ?? '',
		);
		$n++;
	}

	$grn_nos = array();
	$grn_dates = array();
	foreach ($gcns as $g) {
		if (!empty($g['grn_no'])) {
			$grn_nos[] = $g['grn_no'];
		}
		if (!empty($g['grn_date'])) {
			$grn_dates[] = $g['grn_date'];
		}
	}

	return array(
		'expense_mode' => $is_group ? 'group' : 'single',
		'grn_no' => !empty($grn_nos) ? implode(', ', $grn_nos) : '—',
		'grn_date' => !empty($grn_dates) ? implode(', ', array_unique($grn_dates)) : '—',
		'route_label' => $route_label !== '' ? $route_label : '—',
		'consignor' => $is_group ? '' : ($first['consignor'] ?? ''),
		'consignee' => $is_group ? '' : ($first['consignee'] ?? ''),
		'invoice_no' => $is_group ? '' : ($first['invoice_no'] ?? ''),
		'mode_of_transport' => $mode_label,
		'revenue_without_gst' => expense_gcn_format_money($revenue),
		'revenue_raw' => $revenue,
		'expenses_without_gst' => expense_gcn_format_money($expenses),
		'expenses_raw' => $expenses,
		'profit_amount' => expense_gcn_format_profit_display($profit),
		'profit_raw' => $profit,
		'revenue_breakup' => $is_group ? array() : ($first['revenue_breakup'] ?? array()),
		'gcns' => $gcns,
		'profit_lines' => $view_lines,
	);
}

function expense_general_view_data($conn, $general_expense_id, $scope_gcn_key = '')
{
	$record = expense_general_load($conn, $general_expense_id);
	if (!$record) {
		return null;
	}
	if (strtolower((string) ($record['voucher_type'] ?? '')) === 'gcn') {
		$scope_gcn_key = trim((string) $scope_gcn_key);
		if ($scope_gcn_key !== '') {
			$scoped_lines = array();
			foreach (($record['lines'] ?? array()) as $line) {
				if (trim((string) ($line['gcn_key'] ?? '')) === $scope_gcn_key) {
					$scoped_lines[] = $line;
				}
			}
			if (!empty($scoped_lines)) {
				$record['lines'] = $scoped_lines;
				$record['view_scope_gcn_key'] = $scope_gcn_key;
			}
		}
		$record = array_merge($record, expense_general_gcn_profit_pack($conn, $record));
	}
	$modes = ew_company_bank_payment_modes();
	$pm = strtoupper(trim((string) ($record['payment_mode'] ?? '')));
	$record['payment_mode_label'] = $modes[$pm] ?? ($pm !== '' ? $pm : '—');
	$gp = (float) ($record['gst_percent_raw'] ?? 0);
	if ($gp <= 0) {
		$record['gst_percent_display'] = '0%';
	} else {
		$record['gst_percent_display'] = rtrim(rtrim(number_format($gp, 2, '.', ''), '0'), '.') . '%';
	}
	return $record;
}

function expense_general_voucher_type_options()
{
	return array(
		'general' => 'General',
		'gcn' => 'GCN',
		'purchase_bill' => 'Against Purchase Bill',
	);
}

function expense_general_vendor_bill_paid_total($conn, $bill_id, $exclude_expense_id = 0)
{
	expense_general_ensure_schema($conn);
	$bill_id = (int) $bill_id;
	$exclude_expense_id = (int) $exclude_expense_id;
	if ($bill_id <= 0) {
		return 0.0;
	}
	$sql = "SELECT COALESCE(SUM(net_payable),0) AS amt FROM general_expense_header
		WHERE vendor_bill_id='$bill_id' AND status=0
		AND LOWER(IFNULL(voucher_type,''))='purchase_bill'";
	if ($exclude_expense_id > 0) {
		$sql .= " AND general_expense_id!='$exclude_expense_id'";
	}
	$q = mysqli_query($conn, $sql);
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return round((float) ($row['amt'] ?? 0), 2);
	}
	return 0.0;
}

function expense_general_purchase_bill_options($conn, $vendor_id)
{
	require_once __DIR__ . '/expense_bill_helpers.php';
	expense_bill_ensure_schema($conn);
	$vendor_id = (int) $vendor_id;
	$rows = array();
	if ($vendor_id <= 0) {
		return $rows;
	}
	$q = mysqli_query($conn, "SELECT bill_id, bill_entry_no, net_payable, grand_total
		FROM vendor_bill
		WHERE vendor_id='$vendor_id' AND status=0
		ORDER BY bill_id DESC
		LIMIT 500");
	if (!$q) {
		return $rows;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$amt = expense_bill_money($row['net_payable'] ?? $row['grand_total'] ?? 0);
		$no = trim((string) ($row['bill_entry_no'] ?? ''));
		if ($no === '') {
			$no = 'Bill #' . (int) $row['bill_id'];
		}
		$rows[] = array(
			'bill_id' => (int) $row['bill_id'],
			'bill_entry_no' => $no,
			'net_payable' => $amt,
			'net_payable_fmt' => expense_bill_format_money($amt),
			'label' => $no . ' – ₹' . expense_bill_format_money($amt),
		);
	}
	return $rows;
}

function expense_general_purchase_bill_snapshot($conn, $bill_id, $exclude_expense_id = 0)
{
	require_once __DIR__ . '/expense_bill_helpers.php';
	require_once __DIR__ . '/billing_note_functions.php';
	$bill = expense_bill_load($conn, (int) $bill_id);
	if (!$bill) {
		return null;
	}
	$bill_total = expense_bill_money($bill['net_payable'] ?? 0);
	if ($bill_total <= 0) {
		$bill_total = expense_bill_money($bill['grand_total'] ?? 0);
	}
	$already_paid = expense_general_vendor_bill_paid_total($conn, (int) $bill['bill_id'], $exclude_expense_id);
	$debit_note_adj = billing_vendor_bill_note_total($conn, (int) $bill['bill_id'], 0);
	$outstanding = max(0, round($bill_total - $already_paid - $debit_note_adj, 2));
	$subtotal = expense_bill_money($bill['subtotal'] ?? 0);
	$gst_total = expense_bill_money($bill['gst_total'] ?? 0);
	$tds_amount = expense_bill_money($bill['tds_amount'] ?? 0);
	$tds_percent = expense_bill_money($bill['tds_percent'] ?? 0);
	$gst_percent = 0.0;
	if ($subtotal > 0 && $gst_total > 0) {
		$gst_percent = round($gst_total / $subtotal * 100, 2);
	}
	$gcns = $bill['gcns'] ?? array();
	if ($gst_percent <= 0 && !empty($gcns)) {
		$gst_percent = expense_bill_money($gcns[0]['gst_percent'] ?? 0);
	}
	return array(
		'bill_id' => (int) $bill['bill_id'],
		'bill_entry_no' => $bill['bill_entry_no'] ?? '',
		'vendor_id' => (int) ($bill['vendor_id'] ?? 0),
		'bill_total' => $bill_total,
		'bill_total_fmt' => expense_bill_format_money($bill_total),
		'already_paid' => $already_paid,
		'already_paid_fmt' => expense_bill_format_money($already_paid),
		'debit_note_adjustment' => $debit_note_adj,
		'debit_note_adjustment_fmt' => expense_bill_format_money($debit_note_adj),
		'outstanding' => $outstanding,
		'outstanding_fmt' => expense_bill_format_money($outstanding),
		'subtotal' => $subtotal,
		'subtotal_fmt' => expense_bill_format_money($subtotal),
		'gst_total' => $gst_total,
		'gst_total_fmt' => expense_bill_format_money($gst_total),
		'tds_amount' => $tds_amount,
		'tds_amount_fmt' => expense_bill_format_money($tds_amount),
		'gst_percent' => $gst_percent,
		'tds_percent' => $tds_percent,
	);
}

function expense_general_prepare_purchase_bill_payment($conn, $payload, $exclude_expense_id = 0)
{
	$vendor_bill_id = (int) ($payload['vendor_bill_id'] ?? 0);
	$vendor_id = (int) ($payload['vendor_id'] ?? 0);
	if ($vendor_bill_id <= 0) {
		return array('ok' => false, 'message' => 'Please select a purchase bill.');
	}
	$snap = expense_general_purchase_bill_snapshot($conn, $vendor_bill_id, $exclude_expense_id);
	if (!$snap) {
		return array('ok' => false, 'message' => 'Selected purchase bill is invalid or inactive.');
	}
	if ($vendor_id > 0 && (int) $snap['vendor_id'] !== $vendor_id) {
		return array('ok' => false, 'message' => 'Selected purchase bill does not belong to this vendor.');
	}
	$raw = $payload['lines'] ?? ($payload['lines_json'] ?? array());
	if (is_string($raw)) {
		$decoded = json_decode($raw, true);
		$raw = is_array($decoded) ? $decoded : array();
	}
	$payment = 0.0;
	$other = 0.0;
	$category_id = 0;
	foreach ($raw as $line) {
		if (!is_array($line)) {
			continue;
		}
		$payment += expense_gcn_parse_money($line['expense_amount'] ?? 0);
		$other += expense_gcn_parse_money($line['other_deduction'] ?? 0);
		$cid = (int) ($line['category_id'] ?? 0);
		if ($cid > 0) {
			$category_id = $cid;
		}
	}
	if ($category_id <= 0) {
		return array('ok' => false, 'message' => 'Please select expense type.');
	}
	$payment = round($payment, 2);
	$other = round($other, 2);
	if ($payment <= 0) {
		return array('ok' => false, 'message' => 'Please enter current payment amount greater than zero.');
	}
	if ($other < 0) {
		return array('ok' => false, 'message' => 'Other deduction cannot be negative.');
	}
	if ($payment - (float) $snap['outstanding'] > 0.009) {
		return array('ok' => false, 'message' => 'Payment amount exceeds outstanding balance on ' . ($snap['bill_entry_no'] ?? 'purchase bill') . '.');
	}
	$net = round($payment - $other, 2);
	if ($net < 0) {
		return array('ok' => false, 'message' => 'Net payable cannot be negative.');
	}
	$freight = (float) $snap['subtotal'];
	$line = array(
		'category_id' => $category_id,
		'gcn_key' => '',
		'gcn_no' => '',
		'gcn_date' => '',
		'expense_amount' => $payment,
		'freight_amount' => $freight,
		'gst_percent' => (float) $snap['gst_percent'],
		'gst_amount' => (float) $snap['gst_total'],
		'tds_percent' => (float) $snap['tds_percent'],
		'tds_amount' => (float) $snap['tds_amount'],
		'other_deduction' => $other,
		'net_amount' => $net,
	);
	return array(
		'ok' => true,
		'vendor_bill_id' => $vendor_bill_id,
		'gst_percent' => (float) $snap['gst_percent'],
		'tds_percent' => (float) $snap['tds_percent'],
		'snapshot' => $snap,
		'lines' => array($line),
	);
}

function expense_general_parse_rate($value)
{
	$value = str_replace('%', '', trim((string) $value));
	$rate = round((float) $value, 2);
	if ($rate < 0) {
		$rate = 0;
	}
	if ($rate > 100) {
		$rate = 100;
	}
	return $rate;
}

function expense_general_prepare_lines($conn, $payload, $voucher_type, $gst_percent, $tds_percent)
{
	$raw = $payload['lines'] ?? ($payload['lines_json'] ?? array());
	if (is_string($raw)) {
		$decoded = json_decode($raw, true);
		$raw = is_array($decoded) ? $decoded : array();
	}
	if (!is_array($raw)) {
		$raw = array();
	}
	$prepared = array();
	foreach ($raw as $line) {
		if (!is_array($line)) {
			continue;
		}
		$category_id = (int) ($line['category_id'] ?? 0);
		$amount = round(expense_gcn_parse_money($line['expense_amount'] ?? 0), 2);
		$other = round(expense_gcn_parse_money($line['other_deduction'] ?? 0), 2);
		$gcn_key = trim((string) ($line['gcn_key'] ?? ''));
		if ($category_id <= 0 && $amount <= 0 && $other <= 0 && $gcn_key === '') {
			continue;
		}
		if ($category_id <= 0) {
			return array('ok' => false, 'message' => 'Please select expense type on every line.');
		}
		if ($amount <= 0) {
			return array('ok' => false, 'message' => 'Please enter expense amount on every line.');
		}
		if ($other < 0) {
			return array('ok' => false, 'message' => 'Other deduction cannot be negative.');
		}
		$gcn_no = '';
		$gcn_date = '';
		if ($voucher_type === 'gcn') {
			if ($gcn_key === '') {
				return array('ok' => false, 'message' => 'Please select GCN no. on every line.');
			}
			$gcn = expense_general_resolve_gcn($conn, $gcn_key);
			if (!$gcn || $gcn['gcn_no'] === '') {
				return array('ok' => false, 'message' => 'Selected GCN is invalid.');
			}
			$gcn_key = $gcn['gcn_key'];
			$gcn_no = $gcn['gcn_no'];
			$gcn_date = $gcn['gcn_date'];
		} else {
			$gcn_key = '';
		}
		$gst_amount = round($amount * $gst_percent / 100, 2);
		$tds_amount = round($amount * $tds_percent / 100, 2);
		$net = round($amount + $gst_amount - $tds_amount - $other, 2);
		if ($net < 0) {
			return array('ok' => false, 'message' => 'Net amount cannot be negative.');
		}
		$prepared[] = array(
			'category_id' => $category_id,
			'gcn_key' => $gcn_key,
			'gcn_no' => $gcn_no,
			'gcn_date' => $gcn_date,
			'expense_amount' => $amount,
			'gst_percent' => $gst_percent,
			'gst_amount' => $gst_amount,
			'tds_percent' => $tds_percent,
			'tds_amount' => $tds_amount,
			'other_deduction' => $other,
			'net_amount' => $net,
		);
	}
	if (empty($prepared)) {
		return array('ok' => false, 'message' => 'Please add at least one line.');
	}
	return array('ok' => true, 'lines' => $prepared);
}

function expense_general_save($conn, $payload, $user_id)
{
	expense_general_ensure_schema($conn);

	$expense_date = trim((string) ($payload['expense_date'] ?? ''));
	$vendor_id = (int) ($payload['vendor_id'] ?? 0);
	$voucher_type = strtolower(trim((string) ($payload['voucher_type'] ?? 'general')));
	$type_opts = expense_general_voucher_type_options();
	if (!isset($type_opts[$voucher_type])) {
		$voucher_type = 'general';
	}
	$vendor_bill_id = (int) ($payload['vendor_bill_id'] ?? 0);
	$gst_percent = expense_general_parse_rate($payload['gst_percent'] ?? 0);
	$tds_percent = expense_general_parse_rate($payload['tds_percent'] ?? 0);
	$description = trim((string) ($payload['description'] ?? ''));
	$payment_mode = strtoupper(trim((string) ($payload['payment_mode'] ?? '')));
	$company_bank_id = (int) ($payload['company_bank_id'] ?? 0);
	$payment_bank_name = trim((string) ($payload['payment_bank_name'] ?? ''));
	$payment_ifsc = strtoupper(trim((string) ($payload['payment_ifsc'] ?? '')));
	$payment_bank_branch = trim((string) ($payload['payment_bank_branch'] ?? ''));

	if ($expense_date === '') {
		return array('ok' => false, 'message' => 'Please enter voucher date.');
	}
	$existing_id = (int) ($payload['general_expense_id'] ?? 0);
	if ($voucher_type === 'purchase_bill') {
		if ($vendor_id <= 0) {
			return array('ok' => false, 'message' => 'Please select vendor.');
		}
		$prepared = expense_general_prepare_purchase_bill_payment($conn, $payload, $existing_id);
		if (empty($prepared['ok'])) {
			return array('ok' => false, 'message' => $prepared['message'] ?? 'Please complete purchase bill payment.');
		}
		$lines = $prepared['lines'];
		$vendor_bill_id = (int) ($prepared['vendor_bill_id'] ?? $vendor_bill_id);
		$gst_percent = round((float) ($prepared['gst_percent'] ?? 0), 2);
		$tds_percent = round((float) ($prepared['tds_percent'] ?? 0), 2);
	} else {
		$vendor_bill_id = 0;
		$prepared = expense_general_prepare_lines($conn, $payload, $voucher_type, $gst_percent, $tds_percent);
		if (empty($prepared['ok'])) {
			return array('ok' => false, 'message' => $prepared['message'] ?? 'Please complete the lines.');
		}
		$lines = $prepared['lines'];
	}
	$category_id = (int) $lines[0]['category_id'];
	$expense_amount = 0;
	$gst_amount = 0;
	$tds_amount = 0;
	$other_deduction = 0;
	foreach ($lines as $line) {
		$expense_amount += $line['expense_amount'];
		$gst_amount += $line['gst_amount'];
		$tds_amount += $line['tds_amount'];
		$other_deduction += $line['other_deduction'];
	}
	$expense_amount = round($expense_amount, 2);
	$gst_amount = round($gst_amount, 2);
	$tds_amount = round($tds_amount, 2);
	$other_deduction = round($other_deduction, 2);
	$net_line_total = 0.0;
	foreach ($lines as $line) {
		$net_line_total += (float) ($line['net_amount'] ?? 0);
	}
	$net_line_total = round($net_line_total, 2);
	if ($voucher_type === 'purchase_bill') {
		$gst_amount = 0.0;
		$tds_amount = 0.0;
		$net_payable = $net_line_total;
		$expenses_without_gst = $expense_amount;
	} else {
		$amounts = expense_general_calc_amounts($expense_amount, $gst_amount, $tds_amount, $other_deduction);
		if ($amounts['net_payable'] < 0) {
			return array('ok' => false, 'message' => 'Net payable cannot be negative.');
		}
		$net_payable = $amounts['net_payable'];
		$expenses_without_gst = $amounts['expense_amount'];
		$gst_amount = $amounts['gst_amount'];
		$tds_amount = $amounts['tds_amount'];
		$other_deduction = $amounts['other_deduction'];
	}
	if ($payment_mode === '') {
		return array('ok' => false, 'message' => 'Please select payment mode.');
	}

	$modes = ew_company_bank_payment_modes();
	if (!isset($modes[$payment_mode])) {
		return array('ok' => false, 'message' => 'Invalid payment mode selected.');
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

	$user_id = (int) $user_id;
	$now = date('d-m-Y');
	$expense_date_esc = mysqli_real_escape_string($conn, $expense_date);
	$voucher_type_esc = mysqli_real_escape_string($conn, $voucher_type);
	$description_esc = mysqli_real_escape_string($conn, $description);
	$payment_mode_esc = mysqli_real_escape_string($conn, $payment_mode);
	$payment_bank_name_esc = mysqli_real_escape_string($conn, $payment_bank_name);
	$payment_ifsc_esc = mysqli_real_escape_string($conn, $payment_ifsc);
	$payment_bank_branch_esc = mysqli_real_escape_string($conn, $payment_bank_branch);

	$total_gst = $gst_amount;
	$total_tds = $tds_amount;
	$other_deduction_val = $other_deduction;

	if ($existing_id > 0) {
		$chk = mysqli_query($conn, "SELECT general_expense_id, expense_no FROM general_expense_header WHERE general_expense_id='$existing_id' LIMIT 1");
		if (!$chk || !($existing = mysqli_fetch_assoc($chk))) {
			return array('ok' => false, 'message' => 'Expense record not found.');
		}
		$gid = $existing_id;
		mysqli_query($conn, "UPDATE general_expense_header SET
			expense_date='$expense_date_esc',
			voucher_type='$voucher_type_esc',
			vendor_id='$vendor_id',
			vendor_bill_id='$vendor_bill_id',
			category_id='$category_id',
			expense_amount='$expenses_without_gst',
			gst_percent='$gst_percent',
			tds_percent='$tds_percent',
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
			(expense_no, expense_date, voucher_type, vendor_id, vendor_bill_id, category_id, expense_amount, gst_percent, tds_percent, remarks, description,
			expenses_without_gst, total_gst, total_tds, other_deduction, net_payable, net_amount, profit_amount,
			payment_mode, company_bank_id, payment_bank_name, payment_ifsc, payment_bank_branch,
			status, created_at, created_by, updated_at, updated_by)
			VALUES ('', '$expense_date_esc', '$voucher_type_esc', '$vendor_id', '$vendor_bill_id', '$category_id', '$expenses_without_gst', '$gst_percent', '$tds_percent', '$description_esc', '$description_esc',
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
	$line_no = 1;
	foreach ($lines as $line) {
		$gcn_key_esc = mysqli_real_escape_string($conn, $line['gcn_key']);
		$gcn_no_esc = mysqli_real_escape_string($conn, $line['gcn_no']);
		$gcn_date_esc = mysqli_real_escape_string($conn, $line['gcn_date']);
		$cat = (int) $line['category_id'];
		$freight_amt = round((float) ($line['freight_amount'] ?? 0), 2);
		mysqli_query($conn, "INSERT INTO general_expense_lines
			(general_expense_id, line_no, vendor_id, category_id, gcn_key, gcn_no, gcn_date, expense_date,
			expense_amount, freight_amount, gst_percent, gst_amount, tds_percent, tds_amount, other_deduction, net_amount, created_at, created_by)
			VALUES ('$gid', '$line_no', '$vendor_id', '$cat', '$gcn_key_esc', '$gcn_no_esc', '$gcn_date_esc', '$expense_date_esc',
			'{$line['expense_amount']}', '$freight_amt', '{$line['gst_percent']}', '{$line['gst_amount']}', '{$line['tds_percent']}', '{$line['tds_amount']}',
			'{$line['other_deduction']}', '{$line['net_amount']}', '$now', '$user_id')");
		$line_no++;
	}

	return array(
		'ok' => true,
		'message' => 'Payment voucher saved successfully.',
		'general_expense_id' => $gid,
		'expense_no' => $expense_no,
		'record' => expense_general_format_record($conn, array(
			'general_expense_id' => $gid,
			'expense_no' => $expense_no,
			'expense_date' => $expense_date,
			'vendor_id' => $vendor_id,
			'vendor_bill_id' => $vendor_bill_id,
			'category_id' => $category_id,
			'expense_amount' => $expenses_without_gst,
			'gst_percent' => $gst_percent,
			'tds_percent' => $tds_percent,
			'voucher_type' => $voucher_type,
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

/**
 * One list row per GCN line (profit tracked per consignment, not per voucher group).
 */
function expense_general_gcn_list_per_consignment($conn, $list_rows)
{
	$expanded = array();
	if (!is_array($list_rows)) {
		return $expanded;
	}
	foreach ($list_rows as $row) {
		$lines = $row['lines'] ?? array();
		$gcn_lines = array();
		foreach ($lines as $line) {
			$key = trim((string) ($line['gcn_key'] ?? ''));
			if ($key !== '') {
				$gcn_lines[] = $line;
			}
		}
		if (empty($gcn_lines)) {
			$expanded[] = array_merge($row, array('expense_mode' => 'single'));
			continue;
		}
		foreach ($gcn_lines as $line) {
			$key = trim((string) ($line['gcn_key'] ?? ''));
			$revenue = 0.0;
			$grn_no = trim((string) ($line['gcn_no'] ?? ''));
			$grn_date = trim((string) ($line['gcn_date'] ?? ''));
			if ($key !== '' && strpos($key, '|') !== false) {
				list($trans_table, $transaction_id) = explode('|', $key, 2);
				$ctx = expense_gcn_fetch_context($conn, $trans_table, (int) $transaction_id);
				if (!empty($ctx['ok'])) {
					$revenue = round((float) ($ctx['revenue_without_gst_raw'] ?? 0), 2);
					if ($grn_no === '') {
						$grn_no = trim((string) ($ctx['grn_no'] ?? ''));
					}
					if ($grn_date === '') {
						$grn_date = trim((string) ($ctx['grn_date'] ?? ''));
					}
				}
			}
			$expense = round((float) ($line['expense_amount_raw'] ?? 0), 2);
			$profit = round($revenue - $expense, 2);
			$expanded[] = array_merge($row, array(
				'list_gcn_key' => $key,
				'expense_mode' => 'single',
				'grn_no' => $grn_no !== '' ? $grn_no : '—',
				'grn_date' => $grn_date !== '' ? $grn_date : '—',
				'revenue_without_gst' => expense_gcn_format_money($revenue),
				'revenue_raw' => $revenue,
				'expenses_without_gst' => expense_gcn_format_money($expense),
				'expenses_raw' => $expense,
				'profit_amount' => expense_gcn_format_profit_display($profit),
				'profit_raw' => $profit,
			));
		}
	}
	return $expanded;
}

function expense_general_fetch_list($conn, $voucher_type = '')
{
	expense_general_ensure_schema($conn);
	$rows = array();
	$where = '1=1';
	$voucher_type = strtolower(trim((string) $voucher_type));
	if ($voucher_type === 'gcn') {
		$where = "LOWER(IFNULL(h.voucher_type,'general'))='gcn'";
	} elseif ($voucher_type === 'general') {
		$where = "LOWER(IFNULL(h.voucher_type,'general'))<>'gcn'";
	}
	$q = mysqli_query($conn, "SELECT h.* FROM general_expense_header h WHERE $where ORDER BY h.general_expense_id DESC");
	if (!$q) {
		return $rows;
	}
	while ($h = mysqli_fetch_assoc($q)) {
		$record = expense_general_format_record($conn, $h);
		if ($voucher_type === 'gcn') {
			$record = array_merge($record, expense_general_gcn_profit_pack($conn, $record));
		}
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
