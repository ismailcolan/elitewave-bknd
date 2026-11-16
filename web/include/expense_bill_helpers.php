<?php
require_once __DIR__ . '/expense_schema.php';
require_once __DIR__ . '/expense_gcn_helpers.php';
require_once __DIR__ . '/vendor_master_helpers.php';
require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/company_bank_helpers.php';

/** Fixed payment terms for bill registration due date (invoice day + 15 = 16th day). */
function expense_bill_due_days()
{
	return 15;
}

function expense_bill_upload_dir()
{
	return dirname(__DIR__) . '/bill_uploads';
}

function expense_bill_upload_url_base()
{
	return 'bill_uploads/';
}

function expense_bill_ensure_upload_dir()
{
	$dir = expense_bill_upload_dir();
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	$htaccess = $dir . '/.htaccess';
	if (!is_file($htaccess)) {
		@file_put_contents($htaccess, "Options -Indexes\n");
	}
	return is_dir($dir) && is_writable($dir);
}

function expense_bill_ensure_schema($conn)
{
	expense_gcn_ensure_schema($conn);
	ew_vendor_ensure_table($conn);
	ew_company_bank_ensure_schema($conn);

	$terms_chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_master LIKE 'payment_terms'");
	if ($terms_chk && mysqli_num_rows($terms_chk) === 0) {
		mysqli_query($conn, "ALTER TABLE vendor_master ADD COLUMN payment_terms VARCHAR(150) DEFAULT ''");
	}
	$credit_chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_master LIKE 'credit_days'");
	if ($credit_chk && mysqli_num_rows($credit_chk) === 0) {
		mysqli_query($conn, "ALTER TABLE vendor_master ADD COLUMN credit_days INT(11) DEFAULT NULL");
	}

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vendor_bill (
		bill_id INT(11) NOT NULL AUTO_INCREMENT,
		bill_entry_no VARCHAR(20) NOT NULL DEFAULT '',
		bill_date VARCHAR(20) DEFAULT '',
		bill_type VARCHAR(20) NOT NULL DEFAULT 'general',
		vendor_id INT(11) NOT NULL DEFAULT 0,
		gcn_key VARCHAR(150) DEFAULT '',
		gcn_no VARCHAR(50) DEFAULT '',
		gcn_date VARCHAR(20) DEFAULT '',
		vendor_invoice_no VARCHAR(100) NOT NULL DEFAULT '',
		vendor_invoice_date VARCHAR(20) DEFAULT '',
		invoice_received_date VARCHAR(20) DEFAULT '',
		due_date VARCHAR(20) DEFAULT '',
		due_date_overridden TINYINT(1) NOT NULL DEFAULT 0,
		narration VARCHAR(1000) DEFAULT '',
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (bill_id),
		UNIQUE KEY uk_vendor_bill_entry_no (bill_entry_no),
		KEY idx_vendor_bill_vendor (vendor_id),
		KEY idx_vendor_bill_status (status)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vendor_bill_attachment (
		attachment_id INT(11) NOT NULL AUTO_INCREMENT,
		bill_id INT(11) NOT NULL,
		original_name VARCHAR(255) DEFAULT '',
		stored_name VARCHAR(255) NOT NULL DEFAULT '',
		mime_type VARCHAR(120) DEFAULT '',
		file_size INT(11) NOT NULL DEFAULT 0,
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		PRIMARY KEY (attachment_id),
		KEY idx_vendor_bill_attachment_bill (bill_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vendor_bill_gcn (
		item_id INT(11) NOT NULL AUTO_INCREMENT,
		bill_id INT(11) NOT NULL,
		sort_no INT(11) NOT NULL DEFAULT 1,
		gcn_key VARCHAR(150) NOT NULL DEFAULT '',
		gcn_no VARCHAR(50) DEFAULT '',
		gcn_date VARCHAR(20) DEFAULT '',
		PRIMARY KEY (item_id),
		KEY idx_vendor_bill_gcn_bill (bill_id),
		UNIQUE KEY uk_vendor_bill_gcn (bill_id, gcn_key)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$col_widen = array(
		'gcn_key' => "ALTER TABLE vendor_bill MODIFY COLUMN gcn_key VARCHAR(1000) DEFAULT ''",
		'gcn_no' => "ALTER TABLE vendor_bill MODIFY COLUMN gcn_no VARCHAR(1000) DEFAULT ''",
		'gcn_date' => "ALTER TABLE vendor_bill MODIFY COLUMN gcn_date VARCHAR(500) DEFAULT ''",
	);
	foreach ($col_widen as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_bill LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			mysqli_query($conn, $sql);
		}
	}

	$bill_amount_cols = array(
		'subtotal' => "ALTER TABLE vendor_bill ADD COLUMN subtotal DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER narration",
		'gst_total' => "ALTER TABLE vendor_bill ADD COLUMN gst_total DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER subtotal",
		'grand_total' => "ALTER TABLE vendor_bill ADD COLUMN grand_total DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER gst_total",
		'tds_percent' => "ALTER TABLE vendor_bill ADD COLUMN tds_percent DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER grand_total",
		'tds_amount' => "ALTER TABLE vendor_bill ADD COLUMN tds_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tds_percent",
		'net_payable' => "ALTER TABLE vendor_bill ADD COLUMN net_payable DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tds_amount",
	);
	foreach ($bill_amount_cols as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_bill LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}

	$gcn_detail_cols = array(
		'consignor' => "ALTER TABLE vendor_bill_gcn ADD COLUMN consignor VARCHAR(255) DEFAULT '' AFTER gcn_date",
		'consignee' => "ALTER TABLE vendor_bill_gcn ADD COLUMN consignee VARCHAR(255) DEFAULT '' AFTER consignor",
		'route_label' => "ALTER TABLE vendor_bill_gcn ADD COLUMN route_label VARCHAR(255) DEFAULT '' AFTER consignee",
		'freight_amount' => "ALTER TABLE vendor_bill_gcn ADD COLUMN freight_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER route_label",
		'gst_amount' => "ALTER TABLE vendor_bill_gcn ADD COLUMN gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER freight_amount",
		'total_amount' => "ALTER TABLE vendor_bill_gcn ADD COLUMN total_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER gst_amount",
		'gst_percent' => "ALTER TABLE vendor_bill_gcn ADD COLUMN gst_percent DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER freight_amount",
	);
	foreach ($gcn_detail_cols as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_bill_gcn LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}

	$payment_cols = array(
		'payment_mode' => "ALTER TABLE vendor_bill ADD COLUMN payment_mode VARCHAR(20) DEFAULT '' AFTER net_payable",
		'company_bank_id' => "ALTER TABLE vendor_bill ADD COLUMN company_bank_id INT(11) NOT NULL DEFAULT 0 AFTER payment_mode",
		'payment_bank_name' => "ALTER TABLE vendor_bill ADD COLUMN payment_bank_name VARCHAR(150) DEFAULT '' AFTER company_bank_id",
		'payment_ifsc' => "ALTER TABLE vendor_bill ADD COLUMN payment_ifsc VARCHAR(11) DEFAULT '' AFTER payment_bank_name",
		'payment_bank_branch' => "ALTER TABLE vendor_bill ADD COLUMN payment_bank_branch VARCHAR(150) DEFAULT '' AFTER payment_ifsc",
	);
	foreach ($payment_cols as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_bill LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}

	expense_bill_ensure_upload_dir();
}

function expense_bill_gcn_label($gcn_no, $gcn_date)
{
	$gcn_no = trim((string) $gcn_no);
	$gcn_date = trim((string) $gcn_date);
	if ($gcn_no === '') {
		return '';
	}
	return $gcn_date !== '' ? ($gcn_no . ' | ' . $gcn_date) : $gcn_no;
}

function expense_bill_fetch_gcns($conn, $search = '')
{
	$rows = expense_gcn_fetch_gcns($conn, $search);
	$out = array();
	foreach ($rows as $row) {
		$gcn_no = trim((string) ($row['grn_no'] ?? ''));
		$gcn_date = trim((string) ($row['grn_date'] ?? ''));
		$out[] = array(
			'key' => $row['key'] ?? '',
			'label' => expense_bill_gcn_label($gcn_no, $gcn_date),
			'grn_no' => $gcn_no,
			'grn_date' => $gcn_date,
		);
	}
	return $out;
}

function expense_bill_money($value)
{
	return round((float) $value, 2);
}

function expense_bill_format_money($value)
{
	return number_format(expense_bill_money($value), 2, '.', '');
}

/**
 * Full GCN snapshot for selected keys (table + persist).
 */
function expense_bill_gcn_detail($conn, $gcn_key)
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

	$ctx = expense_gcn_fetch_context($conn, $trans_table, $transaction_id);
	if (empty($ctx['ok'])) {
		return null;
	}

	$q = mysqli_query($conn, "SELECT gst_amount, total, taxable_value, frieght_amount
		FROM `$trans_table` WHERE transaction_id='$transaction_id' LIMIT 1");
	$row = ($q && ($r = mysqli_fetch_assoc($q))) ? $r : array();

	$freight = expense_bill_money($ctx['freight_without_gst_raw'] ?? 0);
	if ($freight <= 0) {
		$freight = expense_bill_money($row['frieght_amount'] ?? 0);
	}
	$taxable = expense_bill_money($ctx['revenue_without_gst_raw'] ?? 0);
	if ($taxable <= 0) {
		$taxable = expense_bill_money($row['taxable_value'] ?? 0);
	}
	if ($taxable <= 0) {
		$taxable = $freight;
	}
	if ($freight <= 0 && $taxable > 0) {
		$freight = $taxable;
	}

	$gst = expense_bill_money($row['gst_amount'] ?? 0);
	$total = expense_bill_money($row['total'] ?? 0);
	if ($total <= 0) {
		$total = expense_bill_money($taxable + $gst);
	}

	$route = trim((string) ($ctx['route_label'] ?? ''));
	if ($route === '') {
		$from = trim((string) ($ctx['route_from'] ?? ''));
		$to = trim((string) ($ctx['route_to'] ?? ''));
		$route = trim($from . ' - ' . $to, ' -');
	} else {
		$route = str_replace('→', '-', $route);
		$route = preg_replace('/\s*-\s*/', ' - ', $route);
		$route = trim(preg_replace('/\s+/', ' ', $route));
	}

	return array(
		'gcn_key' => $ctx['key'],
		'key' => $ctx['key'],
		'gcn_no' => trim((string) ($ctx['grn_no'] ?? '')),
		'grn_no' => trim((string) ($ctx['grn_no'] ?? '')),
		'gcn_date' => trim((string) ($ctx['grn_date'] ?? '')),
		'grn_date' => trim((string) ($ctx['grn_date'] ?? '')),
		'label' => expense_bill_gcn_label($ctx['grn_no'] ?? '', $ctx['grn_date'] ?? ''),
		'consignor' => trim((string) ($ctx['consignor'] ?? '')),
		'consignee' => trim((string) ($ctx['consignee'] ?? '')),
		'route_from' => trim((string) ($ctx['route_from'] ?? '')),
		'route_to' => trim((string) ($ctx['route_to'] ?? '')),
		'route_label' => $route,
		'freight_amount' => $freight,
		'freight_amount_fmt' => expense_bill_format_money($freight),
		'taxable_amount' => $taxable,
		'taxable_amount_fmt' => expense_bill_format_money($taxable),
		'gst_amount' => $gst,
		'gst_amount_fmt' => expense_bill_format_money($gst),
		'total_amount' => $total,
		'total_amount_fmt' => expense_bill_format_money($total),
	);
}

function expense_bill_fetch_gcn_details($conn, $keys)
{
	$keys = expense_bill_parse_gcn_keys_input($keys);
	$out = array();
	$seen = array();
	foreach ($keys as $key) {
		$detail = expense_bill_gcn_detail($conn, $key);
		if (!$detail || $detail['gcn_no'] === '') {
			continue;
		}
		if (isset($seen[$detail['gcn_key']])) {
			continue;
		}
		$seen[$detail['gcn_key']] = true;
		$out[] = $detail;
	}
	return $out;
}

function expense_bill_calc_totals_from_gcns(array $gcn_rows, $tds_percent = 0)
{
	$subtotal = 0.0;
	$gst_total = 0.0;
	$grand_total = 0.0;
	$taxable_base = 0.0;
	foreach ($gcn_rows as $gcn) {
		$freight = expense_bill_money($gcn['freight_amount'] ?? 0);
		$gst = expense_bill_money($gcn['gst_amount'] ?? 0);
		$total = expense_bill_money($gcn['total_amount'] ?? ($freight + $gst));
		$taxable = expense_bill_money($gcn['taxable_amount'] ?? $freight);
		$subtotal += $freight;
		$gst_total += $gst;
		$grand_total += $total;
		$taxable_base += $taxable;
	}
	$tds_percent = max(0, expense_bill_money($tds_percent));
	$tds_amount = ($tds_percent > 0 && $taxable_base > 0)
		? expense_bill_money($taxable_base * $tds_percent / 100)
		: 0.0;
	$net_payable = expense_bill_money($grand_total - $tds_amount);
	if ($net_payable < 0) {
		$net_payable = 0.0;
	}
	return array(
		'subtotal' => expense_bill_money($subtotal),
		'gst_total' => expense_bill_money($gst_total),
		'grand_total' => expense_bill_money($grand_total),
		'taxable_base' => expense_bill_money($taxable_base),
		'tds_percent' => $tds_percent,
		'tds_amount' => $tds_amount,
		'net_payable' => $net_payable,
	);
}

function expense_bill_tds_percent_options($conn)
{
	$opts = array(
		array('rate' => 0, 'label' => 'No TDS (0%)'),
	);
	foreach (billing_receipt_tds_percent_options($conn) as $row) {
		$rate = expense_bill_money($row['rate'] ?? 0);
		if ($rate <= 0) {
			continue;
		}
		$opts[] = array(
			'rate' => $rate,
			'label' => trim((string) ($row['label'] ?? ('TDS ' . $rate . '%'))),
		);
	}
	return $opts;
}

/** GST % choices for the per-GCN dropdown (GST tax master, fallback 0/5/12/18/28). */
function expense_bill_gst_percent_options($conn)
{
	$rates = array(0.0 => true);
	if (function_exists('ensure_gst_tax_master_table')) {
		ensure_gst_tax_master_table($conn);
	}
	$q = @mysqli_query($conn, "SELECT DISTINCT gst_rate FROM gst_tax_master
		WHERE COALESCE(is_deleted,0)=0 AND status=1 AND COALESCE(tds_rate,0)=0 ORDER BY gst_rate ASC");
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$rates[expense_bill_money($r['gst_rate'] ?? 0)] = true;
		}
	}
	if (count($rates) <= 1) {
		foreach (array(5, 12, 18, 28) as $r) {
			$rates[(float) $r] = true;
		}
	}
	$rates = array_keys($rates);
	sort($rates, SORT_NUMERIC);
	$out = array();
	foreach ($rates as $rate) {
		$rate = expense_bill_money($rate);
		$txt = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
		$out[] = array('rate' => $rate, 'label' => $rate > 0 ? ('GST ' . $txt . '%') : 'No GST (0%)');
	}
	return $out;
}

/**
 * Apply manually entered vendor amounts to resolved GCN rows.
 * $amounts = [gcn_key => ['freight' => x, 'gst_percent' => y]] — GST = freight × % ; Total = freight + GST.
 */
function expense_bill_apply_manual_amounts(array $gcn_rows, $amounts)
{
	if (is_string($amounts)) {
		$decoded = json_decode($amounts, true);
		$amounts = is_array($decoded) ? $decoded : array();
	}
	if (!is_array($amounts)) {
		$amounts = array();
	}
	foreach ($gcn_rows as $i => $gcn) {
		$key = (string) ($gcn['gcn_key'] ?? '');
		$a = isset($amounts[$key]) && is_array($amounts[$key]) ? $amounts[$key] : array();
		$freight = max(0, expense_bill_money($a['freight'] ?? 0));
		$pct = expense_bill_money($a['gst_percent'] ?? 0);
		$pct = max(0, min(100, $pct));
		$gst = expense_bill_money($freight * $pct / 100);
		$total = expense_bill_money($freight + $gst);
		$gcn_rows[$i]['freight_amount'] = $freight;
		$gcn_rows[$i]['freight_amount_fmt'] = expense_bill_format_money($freight);
		$gcn_rows[$i]['taxable_amount'] = $freight;
		$gcn_rows[$i]['taxable_amount_fmt'] = expense_bill_format_money($freight);
		$gcn_rows[$i]['gst_percent'] = $pct;
		$gcn_rows[$i]['gst_amount'] = $gst;
		$gcn_rows[$i]['gst_amount_fmt'] = expense_bill_format_money($gst);
		$gcn_rows[$i]['total_amount'] = $total;
		$gcn_rows[$i]['total_amount_fmt'] = expense_bill_format_money($total);
	}
	return $gcn_rows;
}

/** Due date = vendor invoice date + vendor credit days (blank credit days = 0 → same day). */
function expense_bill_vendor_credit_days($conn, $vendor_id)
{
	$info = expense_bill_vendor_info($conn, $vendor_id);
	return max(0, (int) ($info['credit_days'] ?? 0));
}

function expense_bill_parse_gcn_keys_input($raw)
{
	if (is_string($raw)) {
		$raw = trim($raw);
		if ($raw === '') {
			return array();
		}
		if ($raw[0] === '[') {
			$decoded = json_decode($raw, true);
			$raw = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $raw);
		} else {
			$raw = preg_split('/\s*,\s*/', $raw);
		}
	}
	if (!is_array($raw)) {
		return array();
	}
	$keys = array();
	$seen = array();
	foreach ($raw as $key) {
		$key = trim((string) $key);
		if ($key === '' || isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$keys[] = $key;
	}
	return $keys;
}

function expense_bill_gcns($conn, $bill_id)
{
	expense_bill_ensure_schema($conn);
	$bill_id = (int) $bill_id;
	$rows = array();
	if ($bill_id <= 0) {
		return $rows;
	}
	$q = mysqli_query($conn, "SELECT * FROM vendor_bill_gcn WHERE bill_id='$bill_id' ORDER BY sort_no ASC, item_id ASC");
	if ($q && mysqli_num_rows($q) > 0) {
		while ($row = mysqli_fetch_assoc($q)) {
			$gcn_no = trim((string) ($row['gcn_no'] ?? ''));
			$gcn_date = trim((string) ($row['gcn_date'] ?? ''));
			$freight = expense_bill_money($row['freight_amount'] ?? 0);
			$gst = expense_bill_money($row['gst_amount'] ?? 0);
			$total = expense_bill_money($row['total_amount'] ?? ($freight + $gst));
			$gst_pct = expense_bill_money($row['gst_percent'] ?? 0);
			if ($gst_pct <= 0 && $gst > 0 && $freight > 0) {
				$gst_pct = expense_bill_money($gst / $freight * 100);
			}
			$rows[] = array(
				'gcn_key' => $row['gcn_key'] ?? '',
				'key' => $row['gcn_key'] ?? '',
				'gcn_no' => $gcn_no,
				'grn_no' => $gcn_no,
				'gcn_date' => $gcn_date,
				'grn_date' => $gcn_date,
				'label' => expense_bill_gcn_label($gcn_no, $gcn_date),
				'consignor' => trim((string) ($row['consignor'] ?? '')),
				'consignee' => trim((string) ($row['consignee'] ?? '')),
				'route_label' => trim((string) ($row['route_label'] ?? '')),
				'freight_amount' => $freight,
				'freight_amount_fmt' => expense_bill_format_money($freight),
				'taxable_amount' => $freight,
				'taxable_amount_fmt' => expense_bill_format_money($freight),
				'gst_percent' => $gst_pct,
				'gst_amount' => $gst,
				'gst_amount_fmt' => expense_bill_format_money($gst),
				'total_amount' => $total,
				'total_amount_fmt' => expense_bill_format_money($total),
			);
		}
		return $rows;
	}

	// Legacy single-GCN fallback from vendor_bill columns.
	$hq = mysqli_query($conn, "SELECT gcn_key, gcn_no, gcn_date FROM vendor_bill WHERE bill_id='$bill_id' LIMIT 1");
	if ($hq && ($h = mysqli_fetch_assoc($hq))) {
		$gcn_key = trim((string) ($h['gcn_key'] ?? ''));
		if ($gcn_key !== '' && strpos($gcn_key, ',') === false) {
			$detail = expense_bill_gcn_detail($conn, $gcn_key);
			if ($detail) {
				$rows[] = $detail;
			} else {
				$gcn_no = trim((string) ($h['gcn_no'] ?? ''));
				$gcn_date = trim((string) ($h['gcn_date'] ?? ''));
				if ($gcn_no !== '') {
					$rows[] = array(
						'gcn_key' => $gcn_key,
						'key' => $gcn_key,
						'gcn_no' => $gcn_no,
						'grn_no' => $gcn_no,
						'gcn_date' => $gcn_date,
						'grn_date' => $gcn_date,
						'label' => expense_bill_gcn_label($gcn_no, $gcn_date),
						'consignor' => '',
						'consignee' => '',
						'route_label' => '',
						'freight_amount' => 0,
						'freight_amount_fmt' => '0.00',
						'taxable_amount' => 0,
						'taxable_amount_fmt' => '0.00',
						'gst_amount' => 0,
						'gst_amount_fmt' => '0.00',
						'total_amount' => 0,
						'total_amount_fmt' => '0.00',
					);
				}
			}
		}
	}
	return $rows;
}

function expense_bill_sync_gcns($conn, $bill_id, $gcn_rows)
{
	$bill_id = (int) $bill_id;
	mysqli_query($conn, "DELETE FROM vendor_bill_gcn WHERE bill_id='$bill_id'");
	$sort = 1;
	foreach ($gcn_rows as $gcn) {
		$key_esc = mysqli_real_escape_string($conn, (string) ($gcn['gcn_key'] ?? ''));
		$no_esc = mysqli_real_escape_string($conn, (string) ($gcn['gcn_no'] ?? ''));
		$date_esc = mysqli_real_escape_string($conn, (string) ($gcn['gcn_date'] ?? ''));
		$consignor_esc = mysqli_real_escape_string($conn, (string) ($gcn['consignor'] ?? ''));
		$consignee_esc = mysqli_real_escape_string($conn, (string) ($gcn['consignee'] ?? ''));
		$route_esc = mysqli_real_escape_string($conn, (string) ($gcn['route_label'] ?? ''));
		$freight = expense_bill_money($gcn['freight_amount'] ?? 0);
		$gst = expense_bill_money($gcn['gst_amount'] ?? 0);
		$total = expense_bill_money($gcn['total_amount'] ?? ($freight + $gst));
		$gst_pct = expense_bill_money($gcn['gst_percent'] ?? 0);
		mysqli_query($conn, "INSERT INTO vendor_bill_gcn
			(bill_id, sort_no, gcn_key, gcn_no, gcn_date, consignor, consignee, route_label, freight_amount, gst_percent, gst_amount, total_amount)
			VALUES ('$bill_id', '$sort', '$key_esc', '$no_esc', '$date_esc', '$consignor_esc', '$consignee_esc', '$route_esc',
			'$freight', '$gst_pct', '$gst', '$total')");
		$sort++;
	}
}

function expense_bill_resolve_gcn_list($conn, $keys)
{
	return expense_bill_fetch_gcn_details($conn, $keys);
}

function expense_bill_type_options()
{
	return array(
		'transaction' => 'Transaction',
		'po' => 'PO',
		'general' => 'General',
	);
}

function expense_bill_type_label($bill_type)
{
	$opts = expense_bill_type_options();
	$key = strtolower(trim((string) $bill_type));
	return $opts[$key] ?? ($key !== '' ? ucfirst($key) : '—');
}

function expense_bill_next_no($conn, $bill_id)
{
	$id = (int) $bill_id;
	return 'VBRE' . sprintf('%05d', $id);
}

function expense_bill_preview_next_no($conn)
{
	expense_bill_ensure_schema($conn);
	$next_id = 1;
	$q = mysqli_query($conn, 'SELECT MAX(bill_id) AS max_id FROM vendor_bill');
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$next_id = (int) ($row['max_id'] ?? 0) + 1;
	}
	return expense_bill_next_no($conn, $next_id);
}

function expense_bill_parse_date_dmy($value)
{
	$value = trim((string) $value);
	if ($value === '') {
		return null;
	}
	$dt = DateTime::createFromFormat('d-m-Y', $value);
	if (!$dt || $dt->format('d-m-Y') !== $value) {
		return null;
	}
	$dt->setTime(0, 0, 0);
	return $dt;
}

function expense_bill_format_date_dmy(DateTime $dt)
{
	return $dt->format('d-m-Y');
}

function expense_bill_calc_due_date($invoice_date, $credit_days)
{
	$dt = expense_bill_parse_date_dmy($invoice_date);
	if (!$dt) {
		return '';
	}
	$days = (int) $credit_days;
	if ($days < 0) {
		$days = 0;
	}
	if ($days > 0) {
		$dt->modify('+' . $days . ' days');
	}
	return expense_bill_format_date_dmy($dt);
}

function expense_bill_vendor_info($conn, $vendor_id)
{
	ew_vendor_ensure_table($conn);
	$vendor_id = (int) $vendor_id;
	$defaults = array(
		'vendor_id' => $vendor_id,
		'active' => 0,
		'vendor_code' => '',
		'vendor_name' => '',
		'label' => '',
		'payment_terms' => '',
		'credit_days' => 0,
		'tds_applicable' => 0,
		'tds_rate' => 0,
		'default_tds_percent' => 0,
	);
	if ($vendor_id <= 0) {
		return $defaults;
	}
	$q = mysqli_query($conn, "SELECT vendor_id, vendor_code, vendor_name, status, payment_terms, credit_days
		FROM vendor_master WHERE vendor_id='$vendor_id' LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return $defaults;
	}
	$code = trim((string) ($row['vendor_code'] ?? ''));
	$name = trim((string) ($row['vendor_name'] ?? ''));
	$label = trim($code . ' - ' . $name, ' -');
	$tax = expense_gcn_vendor_tax_info($conn, $vendor_id);
	$tds_rate = expense_bill_money($tax['tds_rate'] ?? 0);
	$default_tds = (!empty($tax['tds_applicable']) && $tds_rate > 0) ? $tds_rate : 0;
	return array(
		'vendor_id' => (int) $row['vendor_id'],
		'active' => ((int) ($row['status'] ?? 1) === 0) ? 1 : 0,
		'vendor_code' => $code,
		'vendor_name' => $name,
		'label' => $label,
		'payment_terms' => trim((string) ($row['payment_terms'] ?? '')),
		'credit_days' => (int) ($row['credit_days'] ?? 0),
		'tds_applicable' => (int) ($tax['tds_applicable'] ?? 0),
		'tds_rate' => $tds_rate,
		'default_tds_percent' => $default_tds,
	);
}

function expense_bill_attachments($conn, $bill_id)
{
	expense_bill_ensure_schema($conn);
	$bill_id = (int) $bill_id;
	$rows = array();
	if ($bill_id <= 0) {
		return $rows;
	}
	$q = mysqli_query($conn, "SELECT * FROM vendor_bill_attachment
		WHERE bill_id='$bill_id' AND status=0
		ORDER BY attachment_id ASC");
	if (!$q) {
		return $rows;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$stored = trim((string) ($row['stored_name'] ?? ''));
		$rows[] = array(
			'attachment_id' => (int) $row['attachment_id'],
			'original_name' => $row['original_name'] ?? '',
			'stored_name' => $stored,
			'mime_type' => $row['mime_type'] ?? '',
			'file_size' => (int) ($row['file_size'] ?? 0),
			'url' => $stored !== '' ? (expense_bill_upload_url_base() . rawurlencode($stored)) : '',
		);
	}
	return $rows;
}

function expense_bill_format_record($conn, $row)
{
	$vendor_id = (int) ($row['vendor_id'] ?? 0);
	$vendor = expense_bill_vendor_info($conn, $vendor_id);
	$bill_id = (int) ($row['bill_id'] ?? 0);
	$bill_type = strtolower(trim((string) ($row['bill_type'] ?? 'general')));
	$gcns = expense_bill_gcns($conn, $bill_id);
	$gcn_keys = array();
	$gcn_labels = array();
	$gcn_nos = array();
	$gcn_dates = array();
	foreach ($gcns as $gcn) {
		$gcn_keys[] = $gcn['gcn_key'];
		$gcn_nos[] = $gcn['gcn_no'];
		$gcn_dates[] = $gcn['gcn_date'];
		if ($gcn['label'] !== '') {
			$gcn_labels[] = $gcn['label'];
		}
	}
	$gcn_label = !empty($gcn_labels) ? implode(', ', $gcn_labels) : '—';

	$out = array(
		'bill_id' => $bill_id,
		'bill_entry_no' => $row['bill_entry_no'] ?? '',
		'bill_date' => $row['bill_date'] ?? '',
		'bill_type' => $bill_type,
		'bill_type_label' => expense_bill_type_label($bill_type),
		'vendor_id' => $vendor_id,
		'vendor_label' => $vendor['label'] ?: '—',
		'gcn_key' => !empty($gcn_keys) ? $gcn_keys[0] : '',
		'gcn_keys' => $gcn_keys,
		'gcns' => $gcns,
		'gcn_no' => implode(', ', array_filter($gcn_nos)),
		'gcn_date' => implode(', ', array_filter($gcn_dates)),
		'gcn_label' => $gcn_label,
		'vendor_invoice_no' => $row['vendor_invoice_no'] ?? '',
		'vendor_invoice_date' => $row['vendor_invoice_date'] ?? '',
		'invoice_received_date' => $row['invoice_received_date'] ?? '',
		'due_date' => $row['due_date'] ?? '',
		'due_date_overridden' => (int) ($row['due_date_overridden'] ?? 0),
		'narration' => $row['narration'] ?? '',
		'subtotal' => expense_bill_money($row['subtotal'] ?? 0),
		'subtotal_fmt' => expense_bill_format_money($row['subtotal'] ?? 0),
		'gst_total' => expense_bill_money($row['gst_total'] ?? 0),
		'gst_total_fmt' => expense_bill_format_money($row['gst_total'] ?? 0),
		'grand_total' => expense_bill_money($row['grand_total'] ?? 0),
		'grand_total_fmt' => expense_bill_format_money($row['grand_total'] ?? 0),
		'tds_percent' => expense_bill_money($row['tds_percent'] ?? 0),
		'tds_amount' => expense_bill_money($row['tds_amount'] ?? 0),
		'tds_amount_fmt' => expense_bill_format_money($row['tds_amount'] ?? 0),
		'net_payable' => expense_bill_money($row['net_payable'] ?? 0),
		'net_payable_fmt' => expense_bill_format_money($row['net_payable'] ?? 0),
		'credit_days' => (int) ($vendor['credit_days'] ?? 0),
		'payment_terms' => $vendor['payment_terms'] ?? '',
		'tds_applicable' => (int) ($vendor['tds_applicable'] ?? 0),
		'tds_rate' => expense_bill_money($vendor['tds_rate'] ?? 0),
		'default_tds_percent' => expense_bill_money($vendor['default_tds_percent'] ?? 0),
		'payment_mode' => strtoupper(trim((string) ($row['payment_mode'] ?? ''))),
		'payment_mode_label' => '',
		'company_bank_id' => (int) ($row['company_bank_id'] ?? 0),
		'payment_bank_name' => $row['payment_bank_name'] ?? '',
		'payment_ifsc' => strtoupper(trim((string) ($row['payment_ifsc'] ?? ''))),
		'payment_bank_branch' => $row['payment_bank_branch'] ?? '',
		'attachments' => expense_bill_attachments($conn, $bill_id),
		'updated_at' => $row['updated_at'] ?? ($row['created_at'] ?? ''),
	);
	$modes = ew_company_bank_payment_modes();
	$pm = $out['payment_mode'];
	$out['payment_mode_label'] = $modes[$pm] ?? ($pm !== '' ? $pm : '—');
	return $out;
}

function expense_bill_load($conn, $bill_id)
{
	expense_bill_ensure_schema($conn);
	$bill_id = (int) $bill_id;
	if ($bill_id <= 0) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT * FROM vendor_bill WHERE bill_id='$bill_id' AND status=0 LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return null;
	}
	return expense_bill_format_record($conn, $row);
}

function expense_bill_view_data($conn, $bill_id)
{
	return expense_bill_load($conn, $bill_id);
}

function expense_bill_fetch_list($conn)
{
	expense_bill_ensure_schema($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM vendor_bill WHERE status=0 ORDER BY bill_id DESC");
	if (!$q) {
		return $rows;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$rows[] = expense_bill_format_record($conn, $row);
	}
	return $rows;
}

function expense_bill_invoice_exists($conn, $vendor_id, $vendor_invoice_no, $exclude_bill_id = 0)
{
	$vendor_id = (int) $vendor_id;
	$exclude_bill_id = (int) $exclude_bill_id;
	$invoice = trim((string) $vendor_invoice_no);
	if ($vendor_id <= 0 || $invoice === '') {
		return false;
	}
	$esc = mysqli_real_escape_string($conn, $invoice);
	$sql = "SELECT bill_id FROM vendor_bill
		WHERE vendor_id='$vendor_id' AND status=0
		AND LOWER(vendor_invoice_no)=LOWER('$esc')";
	if ($exclude_bill_id > 0) {
		$sql .= " AND bill_id!='$exclude_bill_id'";
	}
	$sql .= ' LIMIT 1';
	$q = mysqli_query($conn, $sql);
	return ($q && mysqli_num_rows($q) > 0);
}

function expense_bill_resolve_gcn($conn, $gcn_key)
{
	return expense_bill_gcn_detail($conn, $gcn_key);
}

function expense_bill_allowed_mime($mime, $ext)
{
	$mime = strtolower(trim((string) $mime));
	$ext = strtolower(trim((string) $ext));
	$allowed_ext = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx');
	if (!in_array($ext, $allowed_ext, true)) {
		return false;
	}
	if ($mime === '' || $mime === 'application/octet-stream') {
		return true;
	}
	$allowed_mime = array(
		'image/jpeg', 'image/png', 'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	);
	return in_array($mime, $allowed_mime, true);
}

function expense_bill_store_uploads($conn, $bill_id, $files, $user_id)
{
	expense_bill_ensure_schema($conn);
	$bill_id = (int) $bill_id;
	$user_id = (int) $user_id;
	if ($bill_id <= 0 || empty($files) || !is_array($files)) {
		return array('ok' => true, 'saved' => 0);
	}
	if (!expense_bill_ensure_upload_dir()) {
		return array('ok' => false, 'message' => 'Attachment upload folder is not writable.');
	}

	$names = $files['name'] ?? null;
	if (!is_array($names)) {
		$names = array($files['name'] ?? '');
		$files = array(
			'name' => $names,
			'type' => array($files['type'] ?? ''),
			'tmp_name' => array($files['tmp_name'] ?? ''),
			'error' => array($files['error'] ?? UPLOAD_ERR_NO_FILE),
			'size' => array($files['size'] ?? 0),
		);
	}

	$saved = 0;
	$now = date('d-m-Y');
	$count = count($files['name']);
	for ($i = 0; $i < $count; $i++) {
		$error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
		if ($error === UPLOAD_ERR_NO_FILE) {
			continue;
		}
		if ($error !== UPLOAD_ERR_OK) {
			return array('ok' => false, 'message' => 'One or more attachments failed to upload.');
		}
		$original = trim((string) ($files['name'][$i] ?? ''));
		$tmp = (string) ($files['tmp_name'][$i] ?? '');
		$size = (int) ($files['size'][$i] ?? 0);
		$mime = (string) ($files['type'][$i] ?? '');
		if ($original === '' || $tmp === '' || !is_uploaded_file($tmp)) {
			return array('ok' => false, 'message' => 'Invalid attachment upload.');
		}
		if ($size <= 0 || $size > 10 * 1024 * 1024) {
			return array('ok' => false, 'message' => 'Each attachment must be under 10 MB.');
		}
		$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
		if (!expense_bill_allowed_mime($mime, $ext)) {
			return array('ok' => false, 'message' => 'Unsupported attachment type. Use PDF, image, Word, or Excel.');
		}
		$stored = 'bill_' . $bill_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
		$dest = expense_bill_upload_dir() . '/' . $stored;
		if (!@move_uploaded_file($tmp, $dest)) {
			return array('ok' => false, 'message' => 'Could not save attachment file.');
		}
		$orig_esc = mysqli_real_escape_string($conn, $original);
		$stored_esc = mysqli_real_escape_string($conn, $stored);
		$mime_esc = mysqli_real_escape_string($conn, $mime);
		mysqli_query($conn, "INSERT INTO vendor_bill_attachment
			(bill_id, original_name, stored_name, mime_type, file_size, status, created_at, created_by)
			VALUES ('$bill_id', '$orig_esc', '$stored_esc', '$mime_esc', '$size', 0, '$now', '$user_id')");
		$saved++;
	}
	return array('ok' => true, 'saved' => $saved);
}

function expense_bill_delete_attachment($conn, $attachment_id, $bill_id = 0)
{
	expense_bill_ensure_schema($conn);
	$attachment_id = (int) $attachment_id;
	$bill_id = (int) $bill_id;
	if ($attachment_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid attachment.');
	}
	$sql = "SELECT * FROM vendor_bill_attachment WHERE attachment_id='$attachment_id' AND status=0";
	if ($bill_id > 0) {
		$sql .= " AND bill_id='$bill_id'";
	}
	$sql .= ' LIMIT 1';
	$q = mysqli_query($conn, $sql);
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return array('ok' => false, 'message' => 'Attachment not found.');
	}
	mysqli_query($conn, "UPDATE vendor_bill_attachment SET status=1 WHERE attachment_id='$attachment_id' LIMIT 1");
	$stored = trim((string) ($row['stored_name'] ?? ''));
	if ($stored !== '') {
		$path = expense_bill_upload_dir() . '/' . $stored;
		if (is_file($path)) {
			@unlink($path);
		}
	}
	return array('ok' => true, 'message' => 'Attachment removed.');
}

function expense_bill_save($conn, $payload, $user_id, $files = null)
{
	expense_bill_ensure_schema($conn);

	$existing_id = (int) ($payload['bill_id'] ?? 0);
	$bill_date = trim((string) ($payload['bill_date'] ?? ''));
	$bill_type = strtolower(trim((string) ($payload['bill_type'] ?? 'transaction')));
	if ($bill_type === '') {
		$bill_type = 'transaction';
	}
	$vendor_id = (int) ($payload['vendor_id'] ?? 0);
	$gcn_keys_raw = $payload['gcn_keys'] ?? ($payload['gcn_key'] ?? array());
	$gcn_keys = expense_bill_parse_gcn_keys_input($gcn_keys_raw);
	$vendor_invoice_no = trim((string) ($payload['vendor_invoice_no'] ?? ''));
	$vendor_invoice_date = trim((string) ($payload['vendor_invoice_date'] ?? ''));
	$invoice_received_date = trim((string) ($payload['invoice_received_date'] ?? ''));
	$narration = trim((string) ($payload['narration'] ?? ''));
	$tds_percent = expense_bill_money($payload['tds_percent'] ?? 0);
	if ($tds_percent < 0) {
		$tds_percent = 0;
	}
	$payment_mode = strtoupper(trim((string) ($payload['payment_mode'] ?? '')));
	$company_bank_id = (int) ($payload['company_bank_id'] ?? 0);
	$payment_bank_name = trim((string) ($payload['payment_bank_name'] ?? ''));
	$payment_ifsc = strtoupper(trim((string) ($payload['payment_ifsc'] ?? '')));
	$payment_bank_branch = trim((string) ($payload['payment_bank_branch'] ?? ''));
	$user_id = (int) $user_id;
	$now = date('d-m-Y');

	$types = expense_bill_type_options();
	if ($bill_date === '' || !expense_bill_parse_date_dmy($bill_date)) {
		return array('ok' => false, 'message' => 'Please enter a valid bill date.');
	}
	if (!isset($types[$bill_type])) {
		return array('ok' => false, 'message' => 'Please select bill type.');
	}
	if ($vendor_id <= 0) {
		return array('ok' => false, 'message' => 'Please select vendor.');
	}
	$vendor = expense_bill_vendor_info($conn, $vendor_id);
	if (empty($vendor['active'])) {
		return array('ok' => false, 'message' => 'Vendor must be active.');
	}
	if ($vendor_invoice_no === '') {
		return array('ok' => false, 'message' => 'Please enter vendor invoice/memo no.');
	}
	if ($vendor_invoice_date === '' || !expense_bill_parse_date_dmy($vendor_invoice_date)) {
		return array('ok' => false, 'message' => 'Please enter a valid vendor invoice/memo date.');
	}
	$invoice_dt = expense_bill_parse_date_dmy($vendor_invoice_date);
	$today = new DateTime('today');
	if ($invoice_dt > $today) {
		return array('ok' => false, 'message' => 'Invoice date cannot be after today.');
	}
	if ($invoice_received_date === '' || !expense_bill_parse_date_dmy($invoice_received_date)) {
		return array('ok' => false, 'message' => 'Please enter a valid invoice/memo received date.');
	}
	if (expense_bill_invoice_exists($conn, $vendor_id, $vendor_invoice_no, $existing_id)) {
		return array('ok' => false, 'message' => 'This vendor invoice/memo no. already exists for the selected vendor.');
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
	} elseif ($payment_mode === 'CASH') {
		$company_bank_id = 0;
		$payment_bank_name = '';
		$payment_ifsc = '';
		$payment_bank_branch = '';
	} elseif ($company_bank_id > 0) {
		$bank = ew_company_bank_get($conn, $company_bank_id);
		if ($bank && (int) ($bank['status'] ?? 0) === 0) {
			$payment_bank_name = $bank['bank_name'] ?? $payment_bank_name;
			$payment_ifsc = strtoupper($bank['ifsc'] ?? $payment_ifsc);
			$payment_bank_branch = $bank['bank_branch'] ?? $payment_bank_branch;
		}
	}

	$resolved_gcns = expense_bill_resolve_gcn_list($conn, $gcn_keys);
	if ($bill_type === 'transaction') {
		if (empty($resolved_gcns)) {
			return array('ok' => false, 'message' => 'Please select at least one GCN no. & date.');
		}
	} elseif (count($gcn_keys) > 0 && empty($resolved_gcns)) {
		return array('ok' => false, 'message' => 'Selected GCN is invalid.');
	}

	if ($bill_type !== 'transaction') {
		$resolved_gcns = array();
		$tds_percent = 0;
	}
	// Freight / GST % are entered manually per GCN (vendor's invoice), not taken from the GCN.
	$resolved_gcns = expense_bill_apply_manual_amounts($resolved_gcns, $payload['gcn_amounts'] ?? array());
	if ($bill_type === 'transaction') {
		foreach ($resolved_gcns as $g) {
			if (expense_bill_money($g['freight_amount'] ?? 0) <= 0) {
				return array('ok' => false, 'message' => 'Please enter freight amount for GCN ' . ($g['gcn_no'] ?? '') . '.');
			}
		}
	}

	$gcn_key = '';
	$gcn_no = '';
	$gcn_date = '';
	if (!empty($resolved_gcns)) {
		$summary_keys = array();
		$summary_nos = array();
		$summary_dates = array();
		foreach ($resolved_gcns as $gcn) {
			$summary_keys[] = $gcn['gcn_key'];
			$summary_nos[] = $gcn['gcn_no'];
			$summary_dates[] = $gcn['gcn_date'];
		}
		$gcn_key = implode(',', $summary_keys);
		$gcn_no = implode(', ', $summary_nos);
		$gcn_date = implode(', ', array_filter($summary_dates));
	}

	$totals = expense_bill_calc_totals_from_gcns($resolved_gcns, $tds_percent);
	$subtotal = $totals['subtotal'];
	$gst_total = $totals['gst_total'];
	$grand_total = $totals['grand_total'];
	$tds_percent = $totals['tds_percent'];
	$tds_amount = $totals['tds_amount'];
	$net_payable = $totals['net_payable'];

	$due_date = expense_bill_calc_due_date($vendor_invoice_date, max(0, (int) ($vendor['credit_days'] ?? 0)));
	$due_date_overridden = 0;
	if ($due_date === '') {
		return array('ok' => false, 'message' => 'Please enter a valid vendor invoice/memo date for due date.');
	}

	$bill_date_esc = mysqli_real_escape_string($conn, $bill_date);
	$bill_type_esc = mysqli_real_escape_string($conn, $bill_type);
	$gcn_key_esc = mysqli_real_escape_string($conn, $gcn_key);
	$gcn_no_esc = mysqli_real_escape_string($conn, $gcn_no);
	$gcn_date_esc = mysqli_real_escape_string($conn, $gcn_date);
	$invoice_no_esc = mysqli_real_escape_string($conn, $vendor_invoice_no);
	$invoice_date_esc = mysqli_real_escape_string($conn, $vendor_invoice_date);
	$received_esc = mysqli_real_escape_string($conn, $invoice_received_date);
	$due_esc = mysqli_real_escape_string($conn, $due_date);
	if (strlen($narration) > 1000) {
		$narration = substr($narration, 0, 1000);
	}
	$narration_esc = mysqli_real_escape_string($conn, $narration);
	$payment_mode_esc = mysqli_real_escape_string($conn, $payment_mode);
	$payment_bank_name_esc = mysqli_real_escape_string($conn, $payment_bank_name);
	$payment_ifsc_esc = mysqli_real_escape_string($conn, $payment_ifsc);
	$payment_bank_branch_esc = mysqli_real_escape_string($conn, $payment_bank_branch);

	if ($existing_id > 0) {
		$chk = mysqli_query($conn, "SELECT bill_id, bill_entry_no FROM vendor_bill WHERE bill_id='$existing_id' AND status=0 LIMIT 1");
		if (!$chk || !($existing = mysqli_fetch_assoc($chk))) {
			return array('ok' => false, 'message' => 'Bill record not found.');
		}
		$bill_id = $existing_id;
		mysqli_query($conn, "UPDATE vendor_bill SET
			bill_date='$bill_date_esc',
			bill_type='$bill_type_esc',
			vendor_id='$vendor_id',
			gcn_key='$gcn_key_esc',
			gcn_no='$gcn_no_esc',
			gcn_date='$gcn_date_esc',
			vendor_invoice_no='$invoice_no_esc',
			vendor_invoice_date='$invoice_date_esc',
			invoice_received_date='$received_esc',
			due_date='$due_esc',
			due_date_overridden='$due_date_overridden',
			narration='$narration_esc',
			subtotal='$subtotal',
			gst_total='$gst_total',
			grand_total='$grand_total',
			tds_percent='$tds_percent',
			tds_amount='$tds_amount',
			net_payable='$net_payable',
			payment_mode='$payment_mode_esc',
			company_bank_id='$company_bank_id',
			payment_bank_name='$payment_bank_name_esc',
			payment_ifsc='$payment_ifsc_esc',
			payment_bank_branch='$payment_bank_branch_esc',
			updated_at='$now',
			updated_by='$user_id'
			WHERE bill_id='$bill_id'");
		$bill_entry_no = $existing['bill_entry_no'] ?? expense_bill_next_no($conn, $bill_id);
	} else {
		mysqli_query($conn, "INSERT INTO vendor_bill
			(bill_entry_no, bill_date, bill_type, vendor_id, gcn_key, gcn_no, gcn_date,
			vendor_invoice_no, vendor_invoice_date, invoice_received_date, due_date, due_date_overridden,
			narration, subtotal, gst_total, grand_total, tds_percent, tds_amount, net_payable,
			payment_mode, company_bank_id, payment_bank_name, payment_ifsc, payment_bank_branch,
			status, created_at, created_by, updated_at, updated_by)
			VALUES ('', '$bill_date_esc', '$bill_type_esc', '$vendor_id', '$gcn_key_esc', '$gcn_no_esc', '$gcn_date_esc',
			'$invoice_no_esc', '$invoice_date_esc', '$received_esc', '$due_esc', '$due_date_overridden',
			'$narration_esc', '$subtotal', '$gst_total', '$grand_total', '$tds_percent', '$tds_amount', '$net_payable',
			'$payment_mode_esc', '$company_bank_id', '$payment_bank_name_esc', '$payment_ifsc_esc', '$payment_bank_branch_esc',
			0, '$now', '$user_id', '$now', '$user_id')");
		$bill_id = (int) mysqli_insert_id($conn);
		if ($bill_id <= 0) {
			return array('ok' => false, 'message' => 'Could not save bill.');
		}
		$bill_entry_no = expense_bill_next_no($conn, $bill_id);
		$bill_entry_no_esc = mysqli_real_escape_string($conn, $bill_entry_no);
		mysqli_query($conn, "UPDATE vendor_bill SET bill_entry_no='$bill_entry_no_esc' WHERE bill_id='$bill_id'");
	}

	expense_bill_sync_gcns($conn, $bill_id, $resolved_gcns);

	if ($files !== null) {
		$upload = expense_bill_store_uploads($conn, $bill_id, $files, $user_id);
		if (empty($upload['ok'])) {
			return array(
				'ok' => false,
				'message' => $upload['message'] ?? 'Could not save attachments.',
				'bill_id' => $bill_id,
				'bill_entry_no' => $bill_entry_no,
			);
		}
	}

	$remove_ids = $payload['remove_attachment_ids'] ?? array();
	if (is_string($remove_ids) && $remove_ids !== '') {
		$remove_ids = preg_split('/\s*,\s*/', $remove_ids);
	}
	if (is_array($remove_ids)) {
		foreach ($remove_ids as $rid) {
			$rid = (int) $rid;
			if ($rid > 0) {
				expense_bill_delete_attachment($conn, $rid, $bill_id);
			}
		}
	}

	return array(
		'ok' => true,
		'message' => 'Bill registration saved successfully.',
		'bill_id' => $bill_id,
		'bill_entry_no' => $bill_entry_no,
		'record' => expense_bill_load($conn, $bill_id),
	);
}

function expense_bill_delete($conn, $bill_id)
{
	expense_bill_ensure_schema($conn);
	$bill_id = (int) $bill_id;
	if ($bill_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid bill record.');
	}
	$chk = mysqli_query($conn, "SELECT bill_id FROM vendor_bill WHERE bill_id='$bill_id' AND status=0 LIMIT 1");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		return array('ok' => false, 'message' => 'Bill record not found.');
	}
	$now = date('d-m-Y');
	mysqli_query($conn, "UPDATE vendor_bill SET status=1, updated_at='$now' WHERE bill_id='$bill_id' LIMIT 1");
	mysqli_query($conn, "UPDATE vendor_bill_attachment SET status=1 WHERE bill_id='$bill_id' AND status=0");
	mysqli_query($conn, "DELETE FROM vendor_bill_gcn WHERE bill_id='$bill_id'");
	return array('ok' => true, 'message' => 'Bill registration deleted successfully.');
}
