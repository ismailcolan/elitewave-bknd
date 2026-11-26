<?php

require_once __DIR__ . '/expense_schema.php';
require_once __DIR__ . '/expense_type_helpers.php';
require_once __DIR__ . '/vendor_master_helpers.php';
require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/expense_gcn_helpers.php';
require_once __DIR__ . '/expense_general_helpers.php';

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

	$chk_cash = mysqli_query($conn, "SHOW COLUMNS FROM petty_cash_voucher LIKE 'cash_type'");
	if ($chk_cash && mysqli_num_rows($chk_cash) === 0) {
		mysqli_query($conn, 'DELETE FROM petty_cash_voucher');
		mysqli_query($conn, "ALTER TABLE petty_cash_voucher
			ADD COLUMN cash_type VARCHAR(24) NOT NULL DEFAULT 'general_expense' AFTER entry_type,
			ADD COLUMN narration TEXT NULL AFTER remarks,
			ADD KEY idx_petty_cash_cash_type (cash_type)");
	}

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS petty_cash_gcn (
		link_id INT(11) NOT NULL AUTO_INCREMENT,
		petty_cash_id INT(11) NOT NULL,
		trans_table VARCHAR(80) NOT NULL DEFAULT '',
		transaction_id INT(11) NOT NULL DEFAULT 0,
		grn_no VARCHAR(60) DEFAULT NULL,
		sort_no INT(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (link_id),
		KEY idx_pc_gcn_pc (petty_cash_id),
		KEY idx_pc_gcn_ref (trans_table, transaction_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS petty_cash_line (
		line_id INT(11) NOT NULL AUTO_INCREMENT,
		petty_cash_id INT(11) NOT NULL,
		line_no INT(11) NOT NULL DEFAULT 1,
		expense_date VARCHAR(20) DEFAULT '',
		category_id INT(11) NOT NULL DEFAULT 0,
		paid_to VARCHAR(150) DEFAULT '',
		amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		remarks VARCHAR(500) DEFAULT '',
		gcn_key VARCHAR(120) DEFAULT '',
		vendor_id INT(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (line_id),
		KEY idx_pc_line_pc (petty_cash_id),
		KEY idx_pc_line_vendor (vendor_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$chk_line_vendor = mysqli_query($conn, "SHOW COLUMNS FROM petty_cash_line LIKE 'vendor_id'");
	if ($chk_line_vendor && mysqli_num_rows($chk_line_vendor) === 0) {
		mysqli_query($conn, "ALTER TABLE petty_cash_line ADD COLUMN vendor_id INT(11) NOT NULL DEFAULT 0 AFTER category_id");
		mysqli_query($conn, "ALTER TABLE petty_cash_line ADD KEY idx_pc_line_vendor (vendor_id)");
	}

	$chk_pc_gst = mysqli_query($conn, "SHOW COLUMNS FROM petty_cash_voucher LIKE 'gst_percent'");
	if ($chk_pc_gst && mysqli_num_rows($chk_pc_gst) === 0) {
		mysqli_query($conn, "ALTER TABLE petty_cash_voucher
			ADD COLUMN gst_percent DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER cash_type,
			ADD COLUMN tds_percent DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER gst_percent");
	}

	$line_tax_cols = array(
		'gst_percent' => "DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER amount",
		'gst_amount' => "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER gst_percent",
		'tds_amount' => "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER gst_amount",
		'net_amount' => "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tds_amount",
	);
	foreach ($line_tax_cols as $col => $def) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM petty_cash_line LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, "ALTER TABLE petty_cash_line ADD COLUMN $col $def");
		}
	}

}

/** Resolve active petty_cash_id from voucher no (e.g. EMPC0001). */
function petty_cash_id_by_voucher_no($conn, $voucher_no)
{
	petty_cash_ensure_schema($conn);
	$voucher_no = trim((string) $voucher_no);
	if ($voucher_no === '') {
		return 0;
	}
	$esc = mysqli_real_escape_string($conn, $voucher_no);
	$q = mysqli_query($conn, "SELECT petty_cash_id FROM petty_cash_voucher WHERE status=0 AND voucher_no='$esc' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return (int) ($row['petty_cash_id'] ?? 0);
	}
	return 0;
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

function petty_cash_cash_types()
{
	return array(
		'gcn_expense' => 'GCN Expense',
		'general_expense' => 'General Expense',
	);
}

function petty_cash_normalize_cash_type($type)
{
	$type = strtolower(trim((string) $type));
	return ($type === 'gcn_expense') ? 'gcn_expense' : 'general_expense';
}

function petty_cash_cash_type_label($type)
{
	$map = petty_cash_cash_types();
	$type = petty_cash_normalize_cash_type($type);
	return $map[$type] ?? 'General Expense';
}

function petty_cash_parse_gcn_key($key)
{
	$key = trim((string) $key);
	if ($key === '' || strpos($key, '|') === false) {
		return null;
	}
	list($tbl, $id) = explode('|', $key, 2);
	$tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $tbl);
	$id = (int) $id;
	if ($tbl === '' || $id <= 0) {
		return null;
	}
	return array('trans_table' => $tbl, 'transaction_id' => $id, 'key' => $tbl . '|' . $id);
}

/** Human-readable GCN (grn_no) for internal table|id key. */
function petty_cash_grn_no_for_key($conn, $key)
{
	$key = trim((string) $key);
	if ($key === '') {
		return '';
	}
	$p = petty_cash_parse_gcn_key($key);
	if (!$p) {
		return '';
	}
	$tbl = $p['trans_table'];
	$tid = (int) $p['transaction_id'];
	$gq = @mysqli_query($conn, "SELECT grn_no FROM `$tbl` WHERE transaction_id='$tid' LIMIT 1");
	if ($gq && ($g = mysqli_fetch_assoc($gq))) {
		return trim((string) ($g['grn_no'] ?? ''));
	}
	return '';
}

/** All GCN numbers linked to a petty cash voucher (header links, then line keys). */
function petty_cash_voucher_gcn_nos($conn, $petty_cash_id)
{
	$petty_cash_id = (int) $petty_cash_id;
	$nos = array();
	$q = mysqli_query(
		$conn,
		"SELECT grn_no, trans_table, transaction_id FROM petty_cash_gcn
		WHERE petty_cash_id='$petty_cash_id'
		ORDER BY sort_no ASC, link_id ASC"
	);
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$g = trim((string) ($row['grn_no'] ?? ''));
			if ($g === '') {
				$tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $row['trans_table'] ?? '');
				$key = $tbl . '|' . (int) ($row['transaction_id'] ?? 0);
				$g = petty_cash_grn_no_for_key($conn, $key);
			}
			if ($g !== '') {
				$nos[] = $g;
			}
		}
	}
	if (!$nos) {
		$lq = mysqli_query(
			$conn,
			"SELECT DISTINCT gcn_key FROM petty_cash_line
			WHERE petty_cash_id='$petty_cash_id' AND TRIM(gcn_key) <> ''"
		);
		if ($lq) {
			while ($row = mysqli_fetch_assoc($lq)) {
				$g = petty_cash_grn_no_for_key($conn, $row['gcn_key'] ?? '');
				if ($g !== '') {
					$nos[] = $g;
				}
			}
		}
	}
	return array_values(array_unique($nos));
}

/**
 * GCN value for petty cash labels: final tax-invoice line total (incl. GST), else booking total.
 */
function petty_cash_gcn_display_amount_raw($conn, $trans_table, $transaction_id)
{
	$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
	$transaction_id = (int) $transaction_id;
	if ($trans_table === '' || $transaction_id <= 0) {
		return 0.0;
	}
	$tbl_esc = mysqli_real_escape_string($conn, $trans_table);
	$sql = "SELECT d.total_amount, d.taxable_value, d.gst_amount, m.grand_total, m.status,
			(SELECT COUNT(*) FROM billing_invoice_details dx WHERE dx.billing_invoice_id = m.billing_invoice_id) AS line_cnt
		FROM billing_invoice_details d
		INNER JOIN billing_invoice_master m ON m.billing_invoice_id = d.billing_invoice_id
		WHERE d.trans_table='$tbl_esc' AND d.transaction_id='$transaction_id'
		ORDER BY FIELD(m.status, 'final', 'draft'), m.billing_invoice_id DESC
		LIMIT 1";
	$q = mysqli_query($conn, $sql);
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$line_total = round((float) ($row['total_amount'] ?? 0), 2);
		if ($line_total > 0.009) {
			return $line_total;
		}
		$taxable = round((float) ($row['taxable_value'] ?? 0), 2);
		$gst = round((float) ($row['gst_amount'] ?? 0), 2);
		if ($taxable + $gst > 0.009) {
			return round($taxable + $gst, 2);
		}
		$grand = round((float) ($row['grand_total'] ?? 0), 2);
		$line_cnt = (int) ($row['line_cnt'] ?? 0);
		if ($grand > 0.009 && $line_cnt <= 1) {
			return $grand;
		}
	}

	$bq = mysqli_query($conn, "SELECT total, taxable_value, gst_amount FROM `$trans_table`
		WHERE transaction_id='$transaction_id'
		AND (booking_status IS NULL OR booking_status='' OR booking_status!='1')
		LIMIT 1");
	if ($bq && ($b = mysqli_fetch_assoc($bq))) {
		$total = round((float) ($b['total'] ?? 0), 2);
		if ($total > 0.009) {
			return $total;
		}
		return round((float) ($b['taxable_value'] ?? 0) + (float) ($b['gst_amount'] ?? 0), 2);
	}
	return 0.0;
}

function petty_cash_booking_amount_raw($conn, $trans_table, $transaction_id)
{
	return petty_cash_gcn_display_amount_raw($conn, $trans_table, $transaction_id);
}

/**
 * GCN options for petty cash multi-select (GCN number only).
 */
function petty_cash_fetch_gcn_options($conn, $search = '')
{
	expense_type_ensure_schema($conn);
	ensure_transaction_gst_columns($conn, 'transaction');
	$search = trim((string) $search);
	$search_filter = '';
	if ($search !== '') {
		$search_esc = mysqli_real_escape_string($conn, $search);
		$search_filter = " AND t.grn_no LIKE '%$search_esc%'";
	}
	$rows = array();
	$tables_q = mysqli_query($conn, 'SELECT table_name FROM transaction_tbls ORDER BY table_name DESC');
	if (!$tables_q) {
		return $rows;
	}
	while ($tbl = mysqli_fetch_assoc($tables_q)) {
		$trans_table = 'transaction_' . preg_replace('/[^a-zA-Z0-9_]/', '', $tbl['table_name']);
		$chk = @mysqli_query($conn, "SELECT 1 FROM `$trans_table` LIMIT 1");
		if (!$chk) {
			continue;
		}
		$sql = "SELECT t.transaction_id, t.grn_no, t.grn_date
			FROM `$trans_table` t
			WHERE (t.booking_status IS NULL OR t.booking_status='' OR t.booking_status!='1')
			AND t.grn_no IS NOT NULL AND t.grn_no!=''
			$search_filter
			ORDER BY STR_TO_DATE(t.grn_date,'%d-%m-%Y') DESC, t.grn_no DESC
			LIMIT 400";
		$prev = mysqli_report(MYSQLI_REPORT_OFF);
		$q = mysqli_query($conn, $sql);
		mysqli_report($prev);
		if (!$q) {
			continue;
		}
		while ($row = mysqli_fetch_assoc($q)) {
			$tid = (int) $row['transaction_id'];
			$key = $trans_table . '|' . $tid;
			$amt = petty_cash_booking_amount_raw($conn, $trans_table, $tid);
			$rows[] = array(
				'key' => $key,
				'label' => trim($row['grn_no']),
				'grn_no' => $row['grn_no'],
				'grn_date' => $row['grn_date'],
				'booking_amount' => petty_cash_format_money($amt),
				'booking_amount_raw' => $amt,
			);
		}
	}
	return $rows;
}

function petty_cash_gcn_context($conn, $trans_table, $transaction_id)
{
	$ctx = expense_gcn_fetch_context($conn, $trans_table, $transaction_id);
	if (empty($ctx['ok'])) {
		return $ctx;
	}
	$amt = petty_cash_gcn_display_amount_raw($conn, $trans_table, $transaction_id);
	$ctx['booking_amount'] = petty_cash_format_money($amt);
	$ctx['booking_amount_raw'] = $amt;
	$ctx['customer'] = $ctx['consignor'] ?? '—';
	$ctx['route'] = $ctx['route_label'] ?? '—';
	$ctx['freight'] = petty_cash_format_money($amt);
	$ctx['invoice_total'] = petty_cash_format_money($amt);
	$ctx['tax_invoice'] = $ctx['invoice_no'] ?? '—';
	return $ctx;
}

function petty_cash_gcn_contexts_for_keys($conn, $keys)
{
	$out = array();
	foreach ($keys as $key) {
		$p = petty_cash_parse_gcn_key($key);
		if (!$p) {
			continue;
		}
		$ctx = petty_cash_gcn_context($conn, $p['trans_table'], $p['transaction_id']);
		if (!empty($ctx['ok'])) {
			$out[] = $ctx;
		}
	}
	return $out;
}

function petty_cash_load_gcn_links($conn, $petty_cash_id)
{
	$petty_cash_id = (int) $petty_cash_id;
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM petty_cash_gcn WHERE petty_cash_id='$petty_cash_id' ORDER BY sort_no ASC, link_id ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$key = preg_replace('/[^a-zA-Z0-9_]/', '', $row['trans_table']) . '|' . (int) $row['transaction_id'];
			$rows[] = array(
				'key' => $key,
				'grn_no' => $row['grn_no'] ?? '',
				'trans_table' => $row['trans_table'],
				'transaction_id' => (int) $row['transaction_id'],
			);
		}
	}
	return $rows;
}

function petty_cash_load_lines($conn, $petty_cash_id)
{
	$petty_cash_id = (int) $petty_cash_id;
	$lines = array();
	$q = mysqli_query($conn, "SELECT l.*, c.category_name
		FROM petty_cash_line l
		LEFT JOIN expense_category c ON c.category_id = l.category_id
		WHERE l.petty_cash_id='$petty_cash_id'
		ORDER BY l.line_no ASC, l.line_id ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$lines[] = array(
				'line_id' => (int) $row['line_id'],
				'line_no' => (int) $row['line_no'],
				'expense_date' => $row['expense_date'] ?? '',
				'category_id' => (int) ($row['category_id'] ?? 0),
				'category_name' => $row['category_name'] ?? '',
				'vendor_id' => (int) ($row['vendor_id'] ?? 0),
				'paid_to' => $row['paid_to'] ?? '',
				'amount' => petty_cash_format_money($row['amount'] ?? 0),
				'amount_raw' => round((float) ($row['amount'] ?? 0), 2),
				'gst_percent' => round((float) ($row['gst_percent'] ?? 0), 2),
				'gst_amount' => petty_cash_format_money($row['gst_amount'] ?? 0),
				'gst_amount_raw' => round((float) ($row['gst_amount'] ?? 0), 2),
				'tds_amount' => petty_cash_format_money($row['tds_amount'] ?? 0),
				'tds_amount_raw' => round((float) ($row['tds_amount'] ?? 0), 2),
				'net_amount' => petty_cash_format_money($row['net_amount'] ?? 0),
				'net_amount_raw' => round((float) ($row['net_amount'] ?? 0), 2),
				'remarks' => $row['remarks'] ?? '',
				'gcn_key' => $row['gcn_key'] ?? '',
			);
		}
	}
	return $lines;
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

function petty_cash_format_voucher_no($sequence)
{
	return 'EMPC' . sprintf('%04d', max(1, (int) $sequence));
}

/** Next display number for a new voucher (active vouchers + 1). */
function petty_cash_preview_next_no($conn)
{
	$q = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM petty_cash_voucher WHERE status=0');
	$n = 0;
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$n = (int) ($row['c'] ?? 0);
	}
	return petty_cash_format_voucher_no($n + 1);
}

/** Voucher no by position among active vouchers (1-based), not auto-increment id. */
function petty_cash_next_no($conn, $petty_cash_id)
{
	$id = (int) $petty_cash_id;
	if ($id <= 0) {
		return petty_cash_preview_next_no($conn);
	}
	$seq = 1;
	$q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM petty_cash_voucher WHERE status=0 AND petty_cash_id <= $id");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$seq = max(1, (int) ($row['c'] ?? 1));
	}
	return petty_cash_format_voucher_no($seq);
}

/** Keep EMPC0001, EMPC0002, … in petty_cash_id order (skips deleted status=1). */
function petty_cash_renumber_active_vouchers($conn)
{
	$q = mysqli_query($conn, 'SELECT petty_cash_id, voucher_no FROM petty_cash_voucher WHERE status=0 ORDER BY petty_cash_id ASC');
	if (!$q) {
		return;
	}
	$seq = 0;
	while ($row = mysqli_fetch_assoc($q)) {
		$seq++;
		$want = petty_cash_format_voucher_no($seq);
		if ((string) ($row['voucher_no'] ?? '') === $want) {
			continue;
		}
		$id = (int) $row['petty_cash_id'];
		$esc = mysqli_real_escape_string($conn, $want);
		mysqli_query($conn, "UPDATE petty_cash_voucher SET voucher_no='$esc' WHERE petty_cash_id='$id'");
	}
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

/** AJAX vendor pickers — type-to-search, capped result set. */
function petty_cash_vendor_search($conn, $search)
{
	ew_vendor_ensure_table($conn);
	$search = trim((string) $search);
	$rows = array();
	$limit = 80;
	if ($search === '') {
		$q = mysqli_query($conn, "SELECT vendor_id, vendor_code, vendor_name
			FROM vendor_master
			WHERE status=0
			ORDER BY vendor_name ASC
			LIMIT $limit");
	} else {
		$esc = mysqli_real_escape_string($conn, $search);
		$q = mysqli_query($conn, "SELECT vendor_id, vendor_code, vendor_name
			FROM vendor_master
			WHERE status=0
				AND (vendor_name LIKE '%$esc%' OR vendor_code LIKE '%$esc%')
			ORDER BY vendor_name ASC
			LIMIT 60");
	}
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

			$cash_type = petty_cash_normalize_cash_type($row['cash_type'] ?? 'general_expense');
			$pc_id = (int) $row['petty_cash_id'];
			$gcn_nos = ($cash_type === 'gcn_expense') ? petty_cash_voucher_gcn_nos($conn, $pc_id) : array();
			$rows[] = array(
				'petty_cash_id' => $pc_id,
				'voucher_no' => $row['voucher_no'] ?? '',
				'voucher_date' => $row['voucher_date'] ?? '',
				'entry_type' => $entry_type,
				'entry_type_label' => petty_cash_type_badge_label($entry_type),
				'cash_type' => $cash_type,
				'cash_type_label' => petty_cash_cash_type_label($cash_type),
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
				'gcn_nos' => $gcn_nos,
				'gcn_display' => $gcn_nos ? implode(', ', $gcn_nos) : '',
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

	$cash_type = petty_cash_normalize_cash_type($row['cash_type'] ?? 'general_expense');
	$pc_id = (int) $row['petty_cash_id'];
	$gcn_links = petty_cash_load_gcn_links($conn, $pc_id);
	foreach ($gcn_links as &$glnk) {
		if (trim((string) ($glnk['grn_no'] ?? '')) === '') {
			$glnk['grn_no'] = petty_cash_grn_no_for_key($conn, $glnk['key'] ?? '');
		}
	}
	unset($glnk);
	$lines = petty_cash_load_lines($conn, $pc_id);
	foreach ($lines as &$line) {
		$line['gcn_no'] = petty_cash_grn_no_for_key($conn, $line['gcn_key'] ?? '');
	}
	unset($line);
	$gcn_nos = petty_cash_voucher_gcn_nos($conn, $pc_id);

	return array(
		'petty_cash_id' => (int) $row['petty_cash_id'],
		'voucher_no' => $row['voucher_no'] ?? '',
		'voucher_date' => $row['voucher_date'] ?? '',
		'entry_type' => $entry_type,
		'entry_type_label' => petty_cash_type_badge_label($entry_type),
		'cash_type' => $cash_type,
		'cash_type_label' => petty_cash_cash_type_label($cash_type),
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
		'narration' => $row['narration'] ?? '',
		'gcn_keys' => array_column($gcn_links, 'key'),
		'gcn_nos' => $gcn_nos,
		'gcn_display' => $gcn_nos ? implode(', ', $gcn_nos) : '',
		'gcn_links' => $gcn_links,
		'gst_percent' => round((float) ($row['gst_percent'] ?? 0), 2),
		'tds_percent' => round((float) ($row['tds_percent'] ?? 0), 2),
		'lines' => $lines,
		'updated_at' => $row['updated_at'] ?? ($row['created_at'] ?? ''),
	);
}

function petty_cash_calc_line_tax($conn, $vendor_id, $category_id, $expense_amount, $gst_percent_override = null, $tds_percent_override = null)
{
	$expense_amount = petty_cash_parse_money($expense_amount);
	$suggested = expense_general_suggest_tax($conn, $vendor_id, $category_id, $expense_amount, $gst_percent_override);
	$gst_amount = round((float) ($suggested['gst_amount'] ?? 0), 2);
	$tds_amount = round((float) ($suggested['tds_amount'] ?? 0), 2);
	if ($tds_percent_override !== null && $tds_percent_override !== '') {
		$tds_pct = round((float) $tds_percent_override, 2);
		$tds_amount = ($tds_pct > 0 && $expense_amount > 0)
			? round($expense_amount * $tds_pct / 100, 2)
			: 0;
	}
	$amounts = expense_general_calc_amounts($expense_amount, $gst_amount, $tds_amount, 0);
	return array(
		'gst_percent' => round((float) ($suggested['gst_percent'] ?? 0), 2),
		'gst_amount' => $gst_amount,
		'tds_amount' => $tds_amount,
		'net_amount' => round((float) ($amounts['net_payable'] ?? 0), 2),
		'vendor_tax' => $suggested['vendor'] ?? array(),
		'category_tax' => $suggested['category'] ?? array(),
		'suggested_tds_rate' => round((float) ($suggested['tds_rate'] ?? 0), 2),
	);
}

function petty_cash_parse_lines_payload($payload)
{
	$raw = isset($payload['lines']) ? $payload['lines'] : '';
	if (is_string($raw) && $raw !== '') {
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) {
			$raw = $decoded;
		}
	}
	if (!is_array($raw)) {
		return array();
	}
	$lines = array();
	$n = 0;
	foreach ($raw as $line) {
		if (!is_array($line)) {
			continue;
		}
		$amount = petty_cash_parse_money($line['amount'] ?? 0);
		if ($amount <= 0) {
			continue;
		}
		$n++;
		$vendor_id = (int) ($line['vendor_id'] ?? 0);
		$paid_to = trim((string) ($line['paid_to'] ?? ''));
		$gst_amount = petty_cash_parse_money($line['gst_amount'] ?? 0);
		$tds_amount = petty_cash_parse_money($line['tds_amount'] ?? 0);
		$net_amount = petty_cash_parse_money($line['net_amount'] ?? 0);
		if ($net_amount <= 0) {
			$net_amount = round($amount + $gst_amount - $tds_amount, 2);
		}
		$lines[] = array(
			'line_no' => $n,
			'expense_date' => trim((string) ($line['expense_date'] ?? '')),
			'category_id' => (int) ($line['category_id'] ?? 0),
			'vendor_id' => $vendor_id,
			'paid_to' => $paid_to,
			'amount' => $amount,
			'gst_percent' => round((float) ($line['gst_percent'] ?? 0), 2),
			'gst_amount' => $gst_amount,
			'tds_amount' => $tds_amount,
			'net_amount' => $net_amount,
			'remarks' => trim((string) ($line['remarks'] ?? '')),
			'gcn_key' => trim((string) ($line['gcn_key'] ?? '')),
		);
	}
	return $lines;
}

function petty_cash_gcn_refs_from_lines(array $lines)
{
	$refs = array();
	foreach ($lines as $line) {
		$key = trim((string) ($line['gcn_key'] ?? ''));
		if ($key === '') {
			continue;
		}
		$p = petty_cash_parse_gcn_key($key);
		if ($p) {
			$refs[$p['key']] = $p;
		}
	}
	return array_values($refs);
}

function petty_cash_parse_gcn_keys_payload($payload)
{
	$raw = isset($payload['gcn_keys']) ? $payload['gcn_keys'] : array();
	if (is_string($raw) && $raw !== '') {
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) {
			$raw = $decoded;
		} else {
			$raw = preg_split('/\s*,\s*/', $raw);
		}
	}
	if (!is_array($raw)) {
		return array();
	}
	$keys = array();
	foreach ($raw as $k) {
		$p = petty_cash_parse_gcn_key($k);
		if ($p) {
			$keys[$p['key']] = $p;
		}
	}
	return array_values($keys);
}

function petty_cash_save_gcn_links($conn, $petty_cash_id, array $gcn_refs)
{
	$petty_cash_id = (int) $petty_cash_id;
	mysqli_query($conn, "DELETE FROM petty_cash_gcn WHERE petty_cash_id='$petty_cash_id'");
	$sort = 0;
	foreach ($gcn_refs as $ref) {
		$sort++;
		$tbl_esc = mysqli_real_escape_string($conn, $ref['trans_table']);
		$tid = (int) $ref['transaction_id'];
		$grn = mysqli_real_escape_string($conn, '');
		$gq = mysqli_query($conn, "SELECT grn_no FROM `$tbl_esc` WHERE transaction_id='$tid' LIMIT 1");
		if ($gq && ($g = mysqli_fetch_assoc($gq))) {
			$grn = mysqli_real_escape_string($conn, $g['grn_no'] ?? '');
		}
		mysqli_query($conn, "INSERT INTO petty_cash_gcn (petty_cash_id, trans_table, transaction_id, grn_no, sort_no)
			VALUES ('$petty_cash_id', '$tbl_esc', '$tid', '$grn', '$sort')");
	}
}

function petty_cash_save_lines($conn, $petty_cash_id, array $lines)
{
	$petty_cash_id = (int) $petty_cash_id;
	mysqli_query($conn, "DELETE FROM petty_cash_line WHERE petty_cash_id='$petty_cash_id'");
	foreach ($lines as $line) {
		$vendor_id = (int) ($line['vendor_id'] ?? 0);
		if ($vendor_id > 0) {
			$resolved = petty_cash_vendor_label($conn, $vendor_id);
			if ($resolved !== '') {
				$line['paid_to'] = $resolved;
			}
		}
		$date_esc = mysqli_real_escape_string($conn, $line['expense_date']);
		$paid_esc = mysqli_real_escape_string($conn, $line['paid_to']);
		$remarks_esc = mysqli_real_escape_string($conn, $line['remarks']);
		$gcn_esc = mysqli_real_escape_string($conn, $line['gcn_key']);
		$cat = (int) $line['category_id'];
		$line_no = (int) $line['line_no'];
		$amt = petty_cash_format_money($line['amount']);
		$gst_pct = petty_cash_format_money($line['gst_percent'] ?? 0);
		$gst_amt = petty_cash_format_money($line['gst_amount'] ?? 0);
		$tds_amt = petty_cash_format_money($line['tds_amount'] ?? 0);
		$net_amt = petty_cash_format_money($line['net_amount'] ?? 0);
		mysqli_query($conn, "INSERT INTO petty_cash_line
			(petty_cash_id, line_no, expense_date, category_id, vendor_id, paid_to, amount,
			 gst_percent, gst_amount, tds_amount, net_amount, remarks, gcn_key)
			VALUES ('$petty_cash_id', '$line_no', '$date_esc', '$cat', '$vendor_id', '$paid_esc', '$amt',
			 '$gst_pct', '$gst_amt', '$tds_amt', '$net_amt', '$remarks_esc', '$gcn_esc')");
	}
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
	$cash_type = petty_cash_normalize_cash_type($payload['cash_type'] ?? 'general_expense');
	$narration = trim((string) ($payload['narration'] ?? ''));
	$lines = petty_cash_parse_lines_payload($payload);
	$user_id = (int) $user_id;
	$now = date('d-m-Y H:i:s');

	if (empty($lines)) {
		return array('status' => 1, 'message' => 'Add at least one expense line with amount.');
	}

	$gcn_refs = array();
	if ($cash_type === 'gcn_expense') {
		$gcn_refs = petty_cash_parse_gcn_keys_payload($payload);
		if (empty($gcn_refs)) {
			return array('status' => 1, 'message' => 'Select at least one GCN for GCN expense.');
		}
	}
	$gst_percent_hdr = round((float) ($payload['gst_percent'] ?? 0), 2);
	$tds_percent_hdr = round((float) ($payload['tds_percent'] ?? 0), 2);

	$total = 0.0;
	foreach ($lines as &$line) {
		if ($cash_type === 'general_expense') {
			$tax = petty_cash_calc_line_tax(
				$conn,
				(int) ($line['vendor_id'] ?? 0),
				(int) ($line['category_id'] ?? 0),
				(float) ($line['amount'] ?? 0),
				$gst_percent_hdr > 0 ? $gst_percent_hdr : null,
				$tds_percent_hdr > 0 ? $tds_percent_hdr : null
			);
			$line['gst_percent'] = $tax['gst_percent'];
			$line['gst_amount'] = $tax['gst_amount'];
			$line['tds_amount'] = $tax['tds_amount'];
			$line['net_amount'] = $tax['net_amount'];
			$total += (float) $line['net_amount'];
		} else {
			$line['gst_percent'] = 0;
			$line['gst_amount'] = 0;
			$line['tds_amount'] = 0;
			$line['net_amount'] = (float) $line['amount'];
			$total += (float) $line['amount'];
		}
	}
	unset($line);
	$total = round($total, 2);
	if ($total <= 0) {
		return array('status' => 1, 'message' => 'Total petty cash must be greater than zero.');
	}

	$particulars = ($cash_type === 'gcn_expense')
		? ('GCN petty cash — ' . count($gcn_refs) . ' GCN(s)')
		: 'General petty cash';
	if ($cash_type === 'gcn_expense' && count($gcn_refs) === 1) {
		$particulars = 'GCN petty cash — ' . ($gcn_refs[0]['trans_table'] ?? '');
	}
	$entry_type = 'payment';

	foreach ($lines as &$line) {
		if ($line['expense_date'] === '') {
			$line['expense_date'] = $voucher_date;
		}
		$dc = petty_cash_validate_date($line['expense_date']);
		if (empty($dc['ok'])) {
			return array('status' => 1, 'message' => 'Invalid expense date on line ' . $line['line_no'] . '.');
		}
		$line['expense_date'] = $dc['date'];
		if ($cash_type === 'general_expense') {
			$line['gcn_key'] = '';
		} elseif ($cash_type === 'gcn_expense') {
			$lk = trim((string) ($line['gcn_key'] ?? ''));
			if ($lk === '' && count($gcn_refs) === 1) {
				$line['gcn_key'] = $gcn_refs[0]['key'];
				$lk = $line['gcn_key'];
			}
			if ($lk === '') {
				return array('status' => 1, 'message' => 'Select GCN on line ' . (int) $line['line_no'] . '.');
			}
			$ok_gcn = false;
			foreach ($gcn_refs as $ref) {
				if (($ref['key'] ?? '') === $lk) {
					$ok_gcn = true;
					break;
				}
			}
			if (!$ok_gcn) {
				return array('status' => 1, 'message' => 'Invalid GCN on line ' . (int) $line['line_no'] . '.');
			}
		}
	}
	unset($line);

	$voucher_date_esc = mysqli_real_escape_string($conn, $voucher_date);
	$entry_type_esc = mysqli_real_escape_string($conn, $entry_type);
	$cash_type_esc = mysqli_real_escape_string($conn, $cash_type);
	$particulars_esc = mysqli_real_escape_string($conn, substr($particulars, 0, 500));
	$narration_esc = mysqli_real_escape_string($conn, $narration);
	$now_esc = mysqli_real_escape_string($conn, $now);
	$amount_esc = petty_cash_format_money($total);
	$gst_pct_esc = petty_cash_format_money($gst_percent_hdr);
	$tds_pct_esc = petty_cash_format_money($tds_percent_hdr);

	if ($petty_cash_id > 0) {
		$existing = petty_cash_load($conn, $petty_cash_id);
		if (!$existing) {
			return array('status' => 1, 'message' => 'Petty cash voucher not found.');
		}
		$ok = mysqli_query($conn, "UPDATE petty_cash_voucher SET
			voucher_date='$voucher_date_esc',
			entry_type='$entry_type_esc',
			cash_type='$cash_type_esc',
			gst_percent='$gst_pct_esc',
			tds_percent='$tds_pct_esc',
			amount='$amount_esc',
			particulars='$particulars_esc',
			paid_to='',
			vendor_id=0,
			category_id=0,
			ref_no='',
			remarks='',
			narration='$narration_esc',
			updated_at='$now_esc',
			updated_by='$user_id'
			WHERE petty_cash_id='$petty_cash_id' AND status=0");
		if (!$ok) {
			return array('status' => 1, 'message' => 'Could not update voucher.');
		}
		petty_cash_save_gcn_links($conn, $petty_cash_id, $gcn_refs);
		petty_cash_save_lines($conn, $petty_cash_id, $lines);
		return array(
			'status' => 0,
			'message' => 'Petty cash saved.',
			'petty_cash_id' => $petty_cash_id,
			'voucher_no' => $existing['voucher_no'],
		);
	}

	$ok = mysqli_query($conn, "INSERT INTO petty_cash_voucher
		(voucher_no, voucher_date, entry_type, cash_type, gst_percent, tds_percent, amount, particulars, paid_to, vendor_id, category_id, ref_no, remarks, narration,
		 status, created_at, created_by, updated_at, updated_by)
		VALUES ('', '$voucher_date_esc', '$entry_type_esc', '$cash_type_esc', '$gst_pct_esc', '$tds_pct_esc', '$amount_esc', '$particulars_esc',
		 '', 0, 0, '', '', '$narration_esc', 0, '$now_esc', '$user_id', '$now_esc', '$user_id')");
	if (!$ok) {
		return array('status' => 1, 'message' => 'Could not save voucher.');
	}
	$new_id = (int) mysqli_insert_id($conn);
	$voucher_no = petty_cash_next_no($conn, $new_id);
	$voucher_no_esc = mysqli_real_escape_string($conn, $voucher_no);
	mysqli_query($conn, "UPDATE petty_cash_voucher SET voucher_no='$voucher_no_esc' WHERE petty_cash_id='$new_id'");
	petty_cash_save_gcn_links($conn, $new_id, $cash_type === 'gcn_expense' ? $gcn_refs : array());
	petty_cash_save_lines($conn, $new_id, $lines);

	return array(
		'status' => 0,
		'message' => 'Petty cash saved.',
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
	petty_cash_renumber_active_vouchers($conn);
	return array(
		'status' => 0,
		'message' => 'Petty cash voucher deleted.',
		'voucher_no' => $existing['voucher_no'],
	);
}
