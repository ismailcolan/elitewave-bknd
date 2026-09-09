<?php
require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/expense_type_helpers.php';
require_once __DIR__ . '/vendor_master_helpers.php';

function expense_gcn_format_money($amount)
{
	return number_format((float) $amount, 2, '.', ',');
}

function expense_gcn_parse_money($value)
{
	if ($value === null || $value === '') {
		return 0.0;
	}
	return round((float) str_replace(',', '', (string) $value), 2);
}

function expense_gcn_ensure_schema($conn)
{
	expense_type_ensure_schema($conn);
	ew_vendor_ensure_table($conn);
	ensure_billing_tables($conn);

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS gcn_expense_header (
		gcn_expense_id INT(11) NOT NULL AUTO_INCREMENT,
		expense_mode VARCHAR(20) NOT NULL DEFAULT 'single',
		trans_table VARCHAR(100) NOT NULL,
		transaction_id INT(11) NOT NULL,
		grn_no VARCHAR(50) DEFAULT '',
		grn_date VARCHAR(20) DEFAULT '',
		revenue_without_gst DECIMAL(14,2) NOT NULL DEFAULT 0,
		expenses_without_gst DECIMAL(14,2) NOT NULL DEFAULT 0,
		profit_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (gcn_expense_id),
		UNIQUE KEY uk_gcn_expense_single (trans_table, transaction_id, expense_mode)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS gcn_expense_lines (
		line_id INT(11) NOT NULL AUTO_INCREMENT,
		gcn_expense_id INT(11) NOT NULL,
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
		KEY idx_gcn_expense_lines_header (gcn_expense_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS gcn_expense_group_items (
		item_id INT(11) NOT NULL AUTO_INCREMENT,
		gcn_expense_id INT(11) NOT NULL,
		sort_no INT(11) NOT NULL DEFAULT 1,
		trans_table VARCHAR(100) NOT NULL,
		transaction_id INT(11) NOT NULL,
		grn_no VARCHAR(50) DEFAULT '',
		grn_date VARCHAR(20) DEFAULT '',
		revenue_without_gst DECIMAL(14,2) NOT NULL DEFAULT 0,
		PRIMARY KEY (item_id),
		KEY idx_gcn_expense_group_header (gcn_expense_id),
		UNIQUE KEY uk_group_item (gcn_expense_id, trans_table, transaction_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function expense_gcn_group_trans_table()
{
	return '__group__';
}

function expense_gcn_parse_keys_input($raw)
{
	if (is_array($raw)) {
		$list = $raw;
	} else {
		$raw = trim((string) $raw);
		if ($raw === '') {
			return array();
		}
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) {
			$list = $decoded;
		} else {
			$list = preg_split('/\s*,\s*/', $raw);
		}
	}
	$keys = array();
	foreach ($list as $item) {
		$key = trim((string) $item);
		if ($key !== '' && !in_array($key, $keys, true)) {
			$keys[] = $key;
		}
	}
	return $keys;
}

function expense_gcn_parse_lines_input($conn, $lines_in)
{
	if (!is_array($lines_in)) {
		$decoded = json_decode((string) $lines_in, true);
		$lines_in = is_array($decoded) ? $decoded : array();
	}

	$parsed_lines = array();
	$line_no = 0;
	foreach ($lines_in as $line) {
		if (!is_array($line)) {
			continue;
		}
		$vendor_id = (int) ($line['vendor_id'] ?? 0);
		$category_id = (int) ($line['category_id'] ?? 0);
		$expense_date = trim((string) ($line['expense_date'] ?? ''));
		$expense_amount = expense_gcn_parse_money($line['expense_amount'] ?? 0);

		if ($vendor_id <= 0 && $category_id <= 0 && $expense_amount <= 0 && $expense_date === '') {
			continue;
		}
		if ($vendor_id <= 0) {
			return array('ok' => false, 'message' => 'Please select vendor for each expense line.');
		}
		if ($category_id <= 0) {
			return array('ok' => false, 'message' => 'Please select expense type for each expense line.');
		}
		if ($expense_date === '') {
			return array('ok' => false, 'message' => 'Please enter expense date for each line.');
		}
		if ($expense_amount <= 0) {
			return array('ok' => false, 'message' => 'Expense amount must be greater than zero.');
		}

		if (!expense_gcn_get_category($conn, $category_id)) {
			return array('ok' => false, 'message' => 'Invalid expense type selected.');
		}

		$line_no++;
		$parsed_lines[] = array(
			'line_no' => $line_no,
			'vendor_id' => $vendor_id,
			'category_id' => $category_id,
			'expense_date' => $expense_date,
			'expense_amount' => round($expense_amount, 2),
			'gst_amount' => 0,
			'tds_amount' => 0,
		);
	}

	if (empty($parsed_lines)) {
		return array('ok' => false, 'message' => 'Add at least one expense line.');
	}

	return array('ok' => true, 'lines' => $parsed_lines);
}

function expense_gcn_save_lines($conn, $gcn_expense_id, $parsed_lines, $user_id, $now)
{
	$gcn_expense_id = (int) $gcn_expense_id;
	$user_id = (int) $user_id;
	mysqli_query($conn, "DELETE FROM gcn_expense_lines WHERE gcn_expense_id='$gcn_expense_id'");
	foreach ($parsed_lines as $line) {
		$line_no = (int) $line['line_no'];
		$vendor_id = (int) $line['vendor_id'];
		$category_id = (int) $line['category_id'];
		$expense_date_esc = mysqli_real_escape_string($conn, $line['expense_date']);
		$expense_amount = $line['expense_amount'];
		$gst_amount = $line['gst_amount'];
		$tds_amount = $line['tds_amount'];
		mysqli_query($conn, "INSERT INTO gcn_expense_lines
			(gcn_expense_id, line_no, vendor_id, category_id, expense_date, expense_amount, gst_amount, tds_amount, created_at, created_by)
			VALUES ('$gcn_expense_id', '$line_no', '$vendor_id', '$category_id', '$expense_date_esc', '$expense_amount', '$gst_amount', '$tds_amount', '$now', '$user_id')");
	}
}

function expense_gcn_load_lines($conn, $gcn_expense_id)
{
	$lines = array();
	$gid = (int) $gcn_expense_id;
	$lq = mysqli_query($conn, "SELECT l.*, v.vendor_name, v.vendor_code, c.category_code, c.category_name, c.expense_type_code
		FROM gcn_expense_lines l
		LEFT JOIN vendor_master v ON v.vendor_id = l.vendor_id
		LEFT JOIN expense_category c ON c.category_id = l.category_id
		WHERE l.gcn_expense_id='$gid'
		ORDER BY l.line_no ASC, l.line_id ASC");
	if ($lq) {
		while ($row = mysqli_fetch_assoc($lq)) {
			$lines[] = array(
				'line_id' => (int) $row['line_id'],
				'line_no' => (int) $row['line_no'],
				'vendor_id' => (int) $row['vendor_id'],
				'category_id' => (int) $row['category_id'],
				'expense_date' => $row['expense_date'],
				'expense_amount' => expense_gcn_format_money($row['expense_amount']),
				'expense_amount_raw' => round((float) $row['expense_amount'], 2),
				'vendor_label' => trim(($row['vendor_code'] ?? '') . ' - ' . ($row['vendor_name'] ?? ''), ' -'),
				'category_label' => trim(($row['category_code'] ?? '') . ' - ' . ($row['category_name'] ?? ''), ' -'),
				'expense_type_code' => $row['expense_type_code'] ?? '',
			);
		}
	}
	return $lines;
}

function expense_gcn_vendor_options($conn)
{
	ew_vendor_ensure_table($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT vendor_id, vendor_code, vendor_name FROM vendor_master WHERE status=0 ORDER BY vendor_name ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = array(
				'vendor_id' => (int) $row['vendor_id'],
				'vendor_code' => $row['vendor_code'],
				'vendor_name' => $row['vendor_name'],
				'label' => trim($row['vendor_code'] . ' - ' . $row['vendor_name'], ' -'),
			);
		}
	}
	return $rows;
}

function expense_gcn_category_options($conn)
{
	expense_type_ensure_schema($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT category_id, category_code, category_name, expense_type_code
		FROM expense_category WHERE status=0 ORDER BY category_code ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = array(
				'category_id' => (int) $row['category_id'],
				'category_code' => $row['category_code'],
				'category_name' => $row['category_name'],
				'expense_type_code' => $row['expense_type_code'] ?? '',
				'label' => trim($row['category_code'] . ' - ' . $row['category_name'], ' -'),
				'code_label' => trim($row['category_code'] . ' - ' . $row['category_name'], ' -'),
			);
		}
	}
	return $rows;
}

function expense_gcn_type_options($conn)
{
	expense_type_ensure_schema($conn);
	$rows = array();
	foreach (expense_type_label_options($conn) as $code => $name) {
		$rows[] = array(
			'type_code' => $code,
			'type_name' => $name,
			'label' => trim($code . ' - ' . $name, ' -'),
		);
	}
	return $rows;
}

function expense_gcn_get_category($conn, $category_id)
{
	$category_id = (int) $category_id;
	if ($category_id <= 0) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT category_id, category_code, category_name
		FROM expense_category WHERE category_id='$category_id' AND status=0 LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return $row;
	}
	return null;
}

function expense_gcn_fetch_gcns($conn, $search = '')
{
	expense_gcn_ensure_schema($conn);
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
		if (!gst_tax_report_table_has_gst_columns($conn, $trans_table)) {
			ensure_transaction_gst_columns($conn, $trans_table);
		}

		$sql = "SELECT t.transaction_id, t.grn_no, t.grn_date, t.consigner, t.consignee, t.origin, t.destination, t.status
			FROM `$trans_table` t
			WHERE (t.booking_status IS NULL OR t.booking_status='' OR t.booking_status!='1')
			AND t.grn_no IS NOT NULL AND t.grn_no!=''
			$search_filter
			ORDER BY STR_TO_DATE(t.grn_date,'%d-%m-%Y') DESC, t.grn_no DESC
			LIMIT 500";

		$prev_report = mysqli_report(MYSQLI_REPORT_OFF);
		$q = mysqli_query($conn, $sql);
		mysqli_report($prev_report);
		if (!$q) {
			continue;
		}

		while ($row = mysqli_fetch_assoc($q)) {
			$sender = get_client_name($conn, $row['consigner']);
			$receiver = get_client_name($conn, $row['consignee']);
			$origin = get_city_name($conn, $row['origin']);
			$destination = get_city_name($conn, $row['destination']);
			$rows[] = array(
				'key' => $trans_table . '|' . $row['transaction_id'],
				'label' => $row['grn_no'] . ' | ' . $row['grn_date'] . ' | ' . $origin . ' → ' . $destination,
				'grn_no' => $row['grn_no'],
				'grn_date' => $row['grn_date'],
				'sender' => $sender,
				'receiver' => $receiver,
				'status' => $row['status'],
			);
		}
	}

	return $rows;
}

function expense_gcn_fetch_delivered_gcns($conn, $search = '')
{
	return expense_gcn_fetch_gcns($conn, $search);
}

function expense_gcn_billed_taxable($conn, $trans_table, $transaction_id)
{
	$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
	$transaction_id = (int) $transaction_id;
	if ($trans_table === '' || $transaction_id <= 0) {
		return array('taxable_value' => 0.0, 'invoice_no' => '', 'invoice_status' => '');
	}

	$sql = "SELECT d.taxable_value, m.invoice_no, m.status
		FROM billing_invoice_details d
		INNER JOIN billing_invoice_master m ON m.billing_invoice_id = d.billing_invoice_id
		WHERE d.trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
		AND d.transaction_id='$transaction_id'
		ORDER BY FIELD(m.status, 'final', 'draft'), m.billing_invoice_id DESC
		LIMIT 1";
	$q = mysqli_query($conn, $sql);
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return array(
			'taxable_value' => round((float) ($row['taxable_value'] ?? 0), 2),
			'invoice_no' => $row['invoice_no'] ?? '',
			'invoice_status' => $row['status'] ?? '',
		);
	}

	return array('taxable_value' => 0.0, 'invoice_no' => '', 'invoice_status' => '');
}

function expense_gcn_fetch_context($conn, $trans_table, $transaction_id)
{
	expense_gcn_ensure_schema($conn);
	$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
	$transaction_id = (int) $transaction_id;
	if ($trans_table === '' || $transaction_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid GCN reference.');
	}

	$q = mysqli_query($conn, "SELECT * FROM `$trans_table` WHERE transaction_id='$transaction_id'
		AND (booking_status IS NULL OR booking_status='' OR booking_status!='1')
		LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return array('ok' => false, 'message' => 'GCN not found or cancelled.');
	}

	$line_agg = billing_invoice_lines_aggregate($conn, $trans_table, $transaction_id);
	$amounts = billing_amounts_from_booking_row($row, $line_agg);
	$freight_without_gst = round((float) $amounts['freight'], 2);

	$billed = expense_gcn_billed_taxable($conn, $trans_table, $transaction_id);
	$booking_taxable = round((float) $amounts['taxable'], 2);
	$revenue_without_gst = $billed['taxable_value'] > 0 ? $billed['taxable_value'] : $booking_taxable;

	$origin_name = get_city_name($conn, $row['origin']);
	$destination_name = get_city_name($conn, $row['destination']);

	return array(
		'ok' => true,
		'key' => $trans_table . '|' . $transaction_id,
		'trans_table' => $trans_table,
		'transaction_id' => $transaction_id,
		'grn_no' => $row['grn_no'],
		'grn_date' => $row['grn_date'],
		'route_from' => $origin_name,
		'route_to' => $destination_name,
		'route_label' => trim($origin_name . ' → ' . $destination_name, ' →'),
		'consignor' => get_client_name($conn, $row['consigner']),
		'consignee' => get_client_name($conn, $row['consignee']),
		'freight_without_gst' => expense_gcn_format_money($freight_without_gst),
		'freight_without_gst_raw' => $freight_without_gst,
		'billing_without_gst' => expense_gcn_format_money($revenue_without_gst),
		'billing_without_gst_raw' => $revenue_without_gst,
		'revenue_without_gst' => expense_gcn_format_money($revenue_without_gst),
		'revenue_without_gst_raw' => $revenue_without_gst,
		'invoice_no' => $billed['invoice_no'],
		'invoice_status' => $billed['invoice_status'],
		'revenue_source' => $billed['taxable_value'] > 0 ? 'billing' : 'booking',
	);
}

function expense_gcn_sum_lines($lines)
{
	$expenses = 0.0;
	foreach ($lines as $line) {
		$expenses += expense_gcn_parse_money($line['expense_amount'] ?? 0);
	}
	return array(
		'expenses_without_gst' => round($expenses, 2),
	);
}

function expense_gcn_load_saved($conn, $trans_table, $transaction_id)
{
	$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
	$transaction_id = (int) $transaction_id;
	if ($trans_table === '' || $transaction_id <= 0) {
		return null;
	}

	$hq = mysqli_query($conn, "SELECT * FROM gcn_expense_header
		WHERE trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
		AND transaction_id='$transaction_id'
		AND expense_mode='single'
		LIMIT 1");
	if (!$hq || !($header = mysqli_fetch_assoc($hq))) {
		return null;
	}

	$lines = expense_gcn_load_lines($conn, (int) $header['gcn_expense_id']);

	return array(
		'header' => $header,
		'lines' => $lines,
	);
}

function expense_gcn_fetch_group_context($conn, $keys)
{
	expense_gcn_ensure_schema($conn);
	$keys = expense_gcn_parse_keys_input($keys);
	if (count($keys) < 2) {
		return array('ok' => false, 'message' => 'Select at least 2 GCNs for group mode.');
	}

	$gcns = array();
	$revenue_total = 0.0;
	foreach ($keys as $key) {
		$parsed = billing_parse_trans_key($key);
		if (!$parsed) {
			return array('ok' => false, 'message' => 'Invalid GCN reference: ' . $key);
		}
		$ctx = expense_gcn_fetch_context($conn, $parsed['trans_table'], $parsed['transaction_id']);
		if (empty($ctx['ok'])) {
			return array('ok' => false, 'message' => ($ctx['message'] ?? 'GCN not available.') . ' (' . $key . ')');
		}
		$gcns[] = $ctx;
		$revenue_total += (float) ($ctx['revenue_without_gst_raw'] ?? 0);
	}

	$revenue_total = round($revenue_total, 2);
	$labels = array();
	foreach ($gcns as $g) {
		$labels[] = $g['grn_no'] ?? '';
	}

	return array(
		'ok' => true,
		'expense_mode' => 'group',
		'gcn_count' => count($gcns),
		'gcn_keys' => $keys,
		'gcns' => $gcns,
		'group_label' => 'Group (' . count($gcns) . ' GCNs)',
		'grn_no' => implode(', ', array_filter($labels)),
		'revenue_without_gst' => expense_gcn_format_money($revenue_total),
		'revenue_without_gst_raw' => $revenue_total,
	);
}

function expense_gcn_load_group_items($conn, $gcn_expense_id)
{
	$items = array();
	$gid = (int) $gcn_expense_id;
	$q = mysqli_query($conn, "SELECT * FROM gcn_expense_group_items WHERE gcn_expense_id='$gid' ORDER BY sort_no ASC, item_id ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$key = expense_gcn_build_key($row['trans_table'], $row['transaction_id']);
			$items[] = array(
				'key' => $key,
				'trans_table' => $row['trans_table'],
				'transaction_id' => (int) $row['transaction_id'],
				'grn_no' => $row['grn_no'] ?? '',
				'grn_date' => $row['grn_date'] ?? '',
				'revenue_without_gst_raw' => round((float) ($row['revenue_without_gst'] ?? 0), 2),
			);
		}
	}
	return $items;
}

function expense_gcn_load_saved_group($conn, $gcn_expense_id)
{
	expense_gcn_ensure_schema($conn);
	$gcn_expense_id = (int) $gcn_expense_id;
	if ($gcn_expense_id <= 0) {
		return null;
	}

	$hq = mysqli_query($conn, "SELECT * FROM gcn_expense_header
		WHERE gcn_expense_id='$gcn_expense_id' AND expense_mode='group' LIMIT 1");
	if (!$hq || !($header = mysqli_fetch_assoc($hq))) {
		return null;
	}

	$items = expense_gcn_load_group_items($conn, $gcn_expense_id);
	$keys = array();
	foreach ($items as $item) {
		if (!empty($item['key'])) {
			$keys[] = $item['key'];
		}
	}

	$context = expense_gcn_fetch_group_context($conn, $keys);
	if (empty($context['ok'])) {
		$context = array(
			'ok' => true,
			'expense_mode' => 'group',
			'gcn_count' => count($items),
			'gcn_keys' => $keys,
			'gcns' => array(),
			'group_label' => 'Group (' . count($items) . ' GCNs)',
			'grn_no' => $header['grn_no'] ?? '',
			'revenue_without_gst' => expense_gcn_format_money($header['revenue_without_gst'] ?? 0),
			'revenue_without_gst_raw' => round((float) ($header['revenue_without_gst'] ?? 0), 2),
		);
		foreach ($items as $item) {
			$ctx = expense_gcn_fetch_context($conn, $item['trans_table'], $item['transaction_id']);
			if (!empty($ctx['ok'])) {
				$context['gcns'][] = $ctx;
			}
		}
	}
	$context['gcn_expense_id'] = $gcn_expense_id;

	return array(
		'header' => $header,
		'items' => $items,
		'context' => $context,
		'lines' => expense_gcn_load_lines($conn, $gcn_expense_id),
	);
}

function expense_gcn_save_group($conn, $payload, $user_id)
{
	expense_gcn_ensure_schema($conn);

	$keys = expense_gcn_parse_keys_input($payload['gcn_keys'] ?? array());
	if (count($keys) < 2) {
		return array('ok' => false, 'message' => 'Select at least 2 GCNs for group mode.');
	}

	$group_context = expense_gcn_fetch_group_context($conn, $keys);
	if (empty($group_context['ok'])) {
		return array('ok' => false, 'message' => $group_context['message'] ?? 'Could not load group GCNs.');
	}

	$parsed_result = expense_gcn_parse_lines_input($conn, $payload['lines'] ?? array());
	if (empty($parsed_result['ok'])) {
		return $parsed_result;
	}
	$parsed_lines = $parsed_result['lines'];

	$totals = expense_gcn_sum_lines($parsed_lines);
	$revenue = round((float) ($group_context['revenue_without_gst_raw'] ?? 0), 2);
	$expenses = round((float) $totals['expenses_without_gst'], 2);
	$profit = round($revenue - $expenses, 2);

	$user_id = (int) $user_id;
	$now = date('d-m-Y');
	$group_trans = expense_gcn_group_trans_table();
	$group_trans_esc = mysqli_real_escape_string($conn, $group_trans);
	$grn_no_esc = mysqli_real_escape_string($conn, $group_context['grn_no'] ?? '');
	$grn_date_esc = mysqli_real_escape_string($conn, $now);
	$existing_id = (int) ($payload['gcn_expense_id'] ?? 0);

	if ($existing_id > 0) {
		$chk = mysqli_query($conn, "SELECT gcn_expense_id FROM gcn_expense_header
			WHERE gcn_expense_id='$existing_id' AND expense_mode='group' LIMIT 1");
		if (!$chk || mysqli_num_rows($chk) === 0) {
			return array('ok' => false, 'message' => 'Group expense record not found.');
		}
		$gid = $existing_id;
		mysqli_query($conn, "UPDATE gcn_expense_header SET
			grn_no='$grn_no_esc',
			grn_date='$grn_date_esc',
			revenue_without_gst='$revenue',
			expenses_without_gst='$expenses',
			profit_amount='$profit',
			updated_at='$now',
			updated_by='$user_id'
			WHERE gcn_expense_id='$gid'");
		mysqli_query($conn, "DELETE FROM gcn_expense_group_items WHERE gcn_expense_id='$gid'");
	} else {
		mysqli_query($conn, "INSERT INTO gcn_expense_header
			(expense_mode, trans_table, transaction_id, grn_no, grn_date, revenue_without_gst, expenses_without_gst, profit_amount, status, created_at, created_by, updated_at, updated_by)
			VALUES ('group', '$group_trans_esc', 0, '$grn_no_esc', '$grn_date_esc', '$revenue', '$expenses', '$profit', 0, '$now', '$user_id', '$now', '$user_id')");
		$gid = (int) mysqli_insert_id($conn);
		if ($gid > 0) {
			mysqli_query($conn, "UPDATE gcn_expense_header SET transaction_id='$gid' WHERE gcn_expense_id='$gid'");
		}
	}

	if ($gid <= 0) {
		return array('ok' => false, 'message' => 'Could not save group expense header.');
	}

	$sort_no = 0;
	foreach ($group_context['gcns'] as $ctx) {
		$sort_no++;
		$trans_esc = mysqli_real_escape_string($conn, $ctx['trans_table']);
		$tid = (int) $ctx['transaction_id'];
		$item_grn_esc = mysqli_real_escape_string($conn, $ctx['grn_no'] ?? '');
		$item_date_esc = mysqli_real_escape_string($conn, $ctx['grn_date'] ?? '');
		$item_rev = round((float) ($ctx['revenue_without_gst_raw'] ?? 0), 2);
		mysqli_query($conn, "INSERT INTO gcn_expense_group_items
			(gcn_expense_id, sort_no, trans_table, transaction_id, grn_no, grn_date, revenue_without_gst)
			VALUES ('$gid', '$sort_no', '$trans_esc', '$tid', '$item_grn_esc', '$item_date_esc', '$item_rev')");
	}

	expense_gcn_save_lines($conn, $gid, $parsed_lines, $user_id, $now);

	return array(
		'ok' => true,
		'message' => 'Group expenses saved successfully.',
		'gcn_expense_id' => $gid,
		'revenue_without_gst' => expense_gcn_format_money($revenue),
		'expenses_without_gst' => expense_gcn_format_money($expenses),
		'profit_amount' => expense_gcn_format_money($profit),
		'profit_raw' => $profit,
	);
}

function expense_gcn_save($conn, $payload, $user_id)
{
	$mode = trim((string) ($payload['expense_mode'] ?? 'single'));
	if ($mode === 'group') {
		return expense_gcn_save_group($conn, $payload, $user_id);
	}
	return expense_gcn_save_single($conn, $payload, $user_id);
}

function expense_gcn_save_single($conn, $payload, $user_id)
{
	expense_gcn_ensure_schema($conn);

	$key = trim((string) ($payload['gcn_key'] ?? ''));
	$parsed = billing_parse_trans_key($key);
	if (!$parsed) {
		return array('ok' => false, 'message' => 'Please select a GCN.');
	}

	$context = expense_gcn_fetch_context($conn, $parsed['trans_table'], $parsed['transaction_id']);
	if (empty($context['ok'])) {
		return array('ok' => false, 'message' => $context['message'] ?? 'GCN not available.');
	}

	$lines_in = isset($payload['lines']) ? $payload['lines'] : array();
	$parsed_result = expense_gcn_parse_lines_input($conn, $lines_in);
	if (empty($parsed_result['ok'])) {
		return $parsed_result;
	}
	$parsed_lines = $parsed_result['lines'];

	$totals = expense_gcn_sum_lines($parsed_lines);
	$revenue = round((float) ($context['revenue_without_gst_raw'] ?? 0), 2);
	$expenses = round((float) $totals['expenses_without_gst'], 2);
	$profit = round($revenue - $expenses, 2);

	$trans_table = $parsed['trans_table'];
	$transaction_id = (int) $parsed['transaction_id'];
	$user_id = (int) $user_id;
	$now = date('d-m-Y');

	$existing = expense_gcn_load_saved($conn, $trans_table, $transaction_id);
	$trans_esc = mysqli_real_escape_string($conn, $trans_table);
	$grn_no_esc = mysqli_real_escape_string($conn, $context['grn_no'] ?? '');
	$grn_date_esc = mysqli_real_escape_string($conn, $context['grn_date'] ?? '');

	if ($existing && !empty($existing['header']['gcn_expense_id'])) {
		$gid = (int) $existing['header']['gcn_expense_id'];
		mysqli_query($conn, "UPDATE gcn_expense_header SET
			grn_no='$grn_no_esc',
			grn_date='$grn_date_esc',
			revenue_without_gst='$revenue',
			expenses_without_gst='$expenses',
			profit_amount='$profit',
			updated_at='$now',
			updated_by='$user_id'
			WHERE gcn_expense_id='$gid'");
	} else {
		mysqli_query($conn, "INSERT INTO gcn_expense_header
			(expense_mode, trans_table, transaction_id, grn_no, grn_date, revenue_without_gst, expenses_without_gst, profit_amount, status, created_at, created_by, updated_at, updated_by)
			VALUES ('single', '$trans_esc', '$transaction_id', '$grn_no_esc', '$grn_date_esc', '$revenue', '$expenses', '$profit', 0, '$now', '$user_id', '$now', '$user_id')");
		$gid = (int) mysqli_insert_id($conn);
	}

	if ($gid <= 0) {
		return array('ok' => false, 'message' => 'Could not save expense header.');
	}

	expense_gcn_save_lines($conn, $gid, $parsed_lines, $user_id, $now);

	return array(
		'ok' => true,
		'message' => 'Expenses saved successfully.',
		'gcn_expense_id' => $gid,
		'revenue_without_gst' => expense_gcn_format_money($revenue),
		'expenses_without_gst' => expense_gcn_format_money($expenses),
		'profit_amount' => expense_gcn_format_money($profit),
		'profit_raw' => $profit,
	);
}

function expense_gcn_route_label($conn, $trans_table, $transaction_id)
{
	$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
	$transaction_id = (int) $transaction_id;
	if ($trans_table === '' || $transaction_id <= 0) {
		return '';
	}
	$q = mysqli_query($conn, "SELECT origin, destination FROM `$trans_table` WHERE transaction_id='$transaction_id' LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return '';
	}
	$origin = get_city_name($conn, $row['origin']);
	$destination = get_city_name($conn, $row['destination']);
	return trim($origin . ' → ' . $destination, ' →');
}

function expense_gcn_build_key($trans_table, $transaction_id)
{
	$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
	$transaction_id = (int) $transaction_id;
	if ($trans_table === '' || $transaction_id <= 0) {
		return '';
	}
	return $trans_table . '|' . $transaction_id;
}

function expense_gcn_fetch_list($conn)
{
	expense_gcn_ensure_schema($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT h.*,
		(SELECT COUNT(*) FROM gcn_expense_lines l WHERE l.gcn_expense_id = h.gcn_expense_id) AS line_count
		FROM gcn_expense_header h
		ORDER BY h.gcn_expense_id DESC");
	if (!$q) {
		return $rows;
	}
	while ($h = mysqli_fetch_assoc($q)) {
		$profit = round((float) ($h['profit_amount'] ?? 0), 2);
		$mode = $h['expense_mode'] ?? 'single';
		$gcn_expense_id = (int) $h['gcn_expense_id'];

		if ($mode === 'group') {
			$items = expense_gcn_load_group_items($conn, $gcn_expense_id);
			$item_count = count($items);
			$grn_labels = array();
			$routes = array();
			foreach ($items as $item) {
				if (!empty($item['grn_no'])) {
					$grn_labels[] = $item['grn_no'];
				}
				$route = expense_gcn_route_label($conn, $item['trans_table'], $item['transaction_id']);
				if ($route !== '') {
					$routes[] = $route;
				}
			}
			$unique_routes = array_values(array_unique($routes));
			if (count($unique_routes) === 1) {
				$route_label = $unique_routes[0];
			} elseif (count($unique_routes) > 1) {
				$route_label = count($unique_routes) . ' routes';
			} else {
				$route_label = '—';
			}
			$group_label = 'Group (' . $item_count . ' GCNs)';
			$rows[] = array(
				'expense_mode' => 'group',
				'gcn_expense_id' => $gcn_expense_id,
				'gcn_key' => '',
				'group_id' => $gcn_expense_id,
				'group_label' => $group_label,
				'gcn_count' => $item_count,
				'gcn_list' => $grn_labels,
				'grn_no' => $group_label,
				'grn_date' => $h['grn_date'] ?? '',
				'route_label' => $route_label,
				'route_list' => $unique_routes,
				'revenue_without_gst' => expense_gcn_format_money($h['revenue_without_gst'] ?? 0),
				'expenses_without_gst' => expense_gcn_format_money($h['expenses_without_gst'] ?? 0),
				'profit_amount' => expense_gcn_format_money($profit),
				'profit_raw' => $profit,
				'line_count' => (int) ($h['line_count'] ?? 0),
				'updated_at' => $h['updated_at'] ?? ($h['created_at'] ?? ''),
			);
			continue;
		}

		$rows[] = array(
			'expense_mode' => 'single',
			'gcn_expense_id' => $gcn_expense_id,
			'gcn_key' => expense_gcn_build_key($h['trans_table'], $h['transaction_id']),
			'group_id' => 0,
			'group_label' => '',
			'gcn_count' => 1,
			'gcn_list' => array($h['grn_no'] ?? ''),
			'grn_no' => $h['grn_no'] ?? '',
			'grn_date' => $h['grn_date'] ?? '',
			'route_label' => expense_gcn_route_label($conn, $h['trans_table'], $h['transaction_id']),
			'route_list' => array(),
			'revenue_without_gst' => expense_gcn_format_money($h['revenue_without_gst'] ?? 0),
			'expenses_without_gst' => expense_gcn_format_money($h['expenses_without_gst'] ?? 0),
			'profit_amount' => expense_gcn_format_money($profit),
			'profit_raw' => $profit,
			'line_count' => (int) ($h['line_count'] ?? 0),
			'updated_at' => $h['updated_at'] ?? ($h['created_at'] ?? ''),
		);
	}
	return $rows;
}

function expense_gcn_delete($conn, $gcn_expense_id)
{
	expense_gcn_ensure_schema($conn);
	$gcn_expense_id = (int) $gcn_expense_id;
	if ($gcn_expense_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid expense record.');
	}
	$chk = mysqli_query($conn, "SELECT gcn_expense_id FROM gcn_expense_header WHERE gcn_expense_id='$gcn_expense_id' LIMIT 1");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		return array('ok' => false, 'message' => 'Expense record not found.');
	}
	mysqli_query($conn, "DELETE FROM gcn_expense_lines WHERE gcn_expense_id='$gcn_expense_id'");
	mysqli_query($conn, "DELETE FROM gcn_expense_group_items WHERE gcn_expense_id='$gcn_expense_id'");
	mysqli_query($conn, "DELETE FROM gcn_expense_header WHERE gcn_expense_id='$gcn_expense_id'");
	return array('ok' => true, 'message' => 'GCN expense deleted successfully.');
}
