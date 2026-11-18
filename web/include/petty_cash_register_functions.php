<?php

require_once __DIR__ . '/petty_cash_helpers.php';

function petty_cash_register_format_money($val)
{
	return number_format((float) $val, 2, '.', '');
}

function petty_cash_register_entry_type_options()
{
	return array(
		'' => 'All types',
		'receipt' => 'Received (Cash In)',
		'payment' => 'Sent (Cash Out)',
	);
}

function petty_cash_register_parse_filters($request)
{
	$from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
	$to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
	$entry_type = isset($request['entry_type']) ? strtolower(trim($request['entry_type'])) : '';

	if ($from_date === '' || $to_date === '') {
		return array('error' => 'From Date and To Date are required.');
	}

	$from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
	$to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
	if (!$from_dt || !$to_dt || $from_dt->format('d-m-Y') !== $from_date || $to_dt->format('d-m-Y') !== $to_date) {
		return array('error' => 'Invalid date format. Use dd-mm-yyyy.');
	}
	if ($from_dt > $to_dt) {
		return array('error' => 'From Date cannot be after To Date.');
	}

	$allowed = array_keys(petty_cash_register_entry_type_options());
	if (!in_array($entry_type, $allowed, true)) {
		$entry_type = '';
	}

	return array(
		'from_date' => $from_date,
		'to_date' => $to_date,
		'from_mysql' => $from_dt->format('Y-m-d'),
		'to_mysql' => $to_dt->format('Y-m-d'),
		'entry_type' => $entry_type,
	);
}

function petty_cash_register_empty_summary()
{
	return array(
		'opening_balance' => petty_cash_register_format_money(0),
		'total_receipt' => petty_cash_register_format_money(0),
		'total_payment' => petty_cash_register_format_money(0),
		'closing_balance' => petty_cash_register_format_money(0),
		'row_count' => 0,
	);
}

/**
 * Balance of all active vouchers strictly before from_mysql.
 */
function petty_cash_register_opening_balance($conn, $from_mysql)
{
	$from_esc = mysqli_real_escape_string($conn, $from_mysql);
	$q = mysqli_query($conn, "SELECT
			COALESCE(SUM(CASE WHEN entry_type='receipt' THEN amount ELSE 0 END),0) AS total_receipt,
			COALESCE(SUM(CASE WHEN entry_type='payment' THEN amount ELSE 0 END),0) AS total_payment
		FROM petty_cash_voucher
		WHERE status=0
		AND STR_TO_DATE(voucher_date, '%d-%m-%Y') < '$from_esc'");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$receipt = round((float) ($row['total_receipt'] ?? 0), 2);
		$payment = round((float) ($row['total_payment'] ?? 0), 2);
		return round($receipt - $payment, 2);
	}
	return 0.0;
}

function petty_cash_register_fetch_rows($conn, $filters)
{
	if (!empty($filters['error'])) {
		return array('rows' => array(), 'summary' => petty_cash_register_empty_summary());
	}

	petty_cash_ensure_schema($conn);

	$from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);
	$opening = petty_cash_register_opening_balance($conn, $filters['from_mysql']);

	$where = "p.status=0
		AND STR_TO_DATE(p.voucher_date, '%d-%m-%Y') >= '$from_esc'
		AND STR_TO_DATE(p.voucher_date, '%d-%m-%Y') <= '$to_esc'";

	if (!empty($filters['entry_type'])) {
		$type_esc = mysqli_real_escape_string($conn, $filters['entry_type']);
		$where .= " AND p.entry_type='$type_esc'";
	}

	$sql = "SELECT p.*,
			c.category_code, c.category_name,
			v.vendor_code, v.vendor_name
		FROM petty_cash_voucher p
		LEFT JOIN expense_category c ON c.category_id = p.category_id
		LEFT JOIN vendor_master v ON v.vendor_id = p.vendor_id
		WHERE $where
		ORDER BY STR_TO_DATE(p.voucher_date, '%d-%m-%Y') ASC, p.petty_cash_id ASC";

	$rows = array();
	$sum_receipt = 0.0;
	$sum_payment = 0.0;
	$running = $opening;
	$sno = 0;
	$types = petty_cash_entry_types();

	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$entry_type = petty_cash_normalize_entry_type($row['entry_type'] ?? 'payment');
			$amount = round((float) ($row['amount'] ?? 0), 2);
			$receipt = ($entry_type === 'receipt') ? $amount : 0.0;
			$payment = ($entry_type === 'payment') ? $amount : 0.0;
			$running = round($running + $receipt - $payment, 2);
			$sum_receipt = round($sum_receipt + $receipt, 2);
			$sum_payment = round($sum_payment + $payment, 2);
			$sno++;

			$cat_code = trim((string) ($row['category_code'] ?? ''));
			$cat_name = trim((string) ($row['category_name'] ?? ''));
			$category_label = '—';
			if ($cat_name !== '') {
				$category_label = ($cat_code !== '') ? ($cat_code . ' — ' . $cat_name) : $cat_name;
			}

			$particulars = trim((string) ($row['particulars'] ?? ''));
			$paid_to = trim((string) ($row['paid_to'] ?? ''));
			$ref_no = trim((string) ($row['ref_no'] ?? ''));
			$voucher_no = trim((string) ($row['voucher_no'] ?? ''));
			$v_code = trim((string) ($row['vendor_code'] ?? ''));
			$v_name = trim((string) ($row['vendor_name'] ?? ''));
			$vendor_label = '—';
			if ($v_name !== '' || $v_code !== '') {
				$vendor_label = ($v_code !== '' && $v_name !== '') ? ($v_code . ' — ' . $v_name) : ($v_name !== '' ? $v_name : $v_code);
			}

			$rows[] = array(
				's_no' => $sno,
				'voucher_no' => $voucher_no !== '' ? $voucher_no : '—',
				'voucher_date' => trim((string) ($row['voucher_date'] ?? '')) ?: '—',
				'entry_type' => $entry_type,
				'entry_type_label' => petty_cash_type_badge_label($entry_type),
				'particulars' => $particulars !== '' ? $particulars : '—',
				'vendor' => $vendor_label,
				'paid_to' => $paid_to !== '' ? $paid_to : '—',
				'category' => $category_label,
				'ref_no' => $ref_no !== '' ? $ref_no : '—',
				'receipt' => $receipt > 0 ? petty_cash_register_format_money($receipt) : '',
				'payment' => $payment > 0 ? petty_cash_register_format_money($payment) : '',
				'balance' => petty_cash_register_format_money($running),
				'receipt_raw' => $receipt,
				'payment_raw' => $payment,
				'balance_raw' => $running,
			);
		}
	}

	$closing = round($opening + $sum_receipt - $sum_payment, 2);

	return array(
		'rows' => $rows,
		'summary' => array(
			'opening_balance' => petty_cash_register_format_money($opening),
			'total_receipt' => petty_cash_register_format_money($sum_receipt),
			'total_payment' => petty_cash_register_format_money($sum_payment),
			'closing_balance' => petty_cash_register_format_money($closing),
			'row_count' => count($rows),
		),
	);
}
