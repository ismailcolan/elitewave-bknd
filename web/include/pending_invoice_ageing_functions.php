<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_receipt_functions.php';

if (!defined('PENDING_INVOICE_DUE_DAYS')) {
	// Due date = invoice date; ageing / days overdue use current date vs that due date.
	define('PENDING_INVOICE_DUE_DAYS', 0);
}

function pending_invoice_ageing_format_money($val)
{
	return number_format((float) $val, 2, '.', '');
}

function pending_invoice_ageing_parse_date($date_str)
{
	$date_str = trim((string) $date_str);
	if ($date_str === '') {
		return null;
	}
	$dt = DateTime::createFromFormat('d-m-Y', $date_str);
	if ($dt instanceof DateTime) {
		$dt->setTime(0, 0, 0);
		return $dt;
	}
	$dt = DateTime::createFromFormat('Y-m-d', $date_str);
	if ($dt instanceof DateTime) {
		$dt->setTime(0, 0, 0);
		return $dt;
	}
	return null;
}

function pending_invoice_ageing_due_date($invoice_date_raw)
{
	$dt = pending_invoice_ageing_parse_date($invoice_date_raw);
	if (!$dt) {
		return null;
	}
	return (clone $dt)->modify('+' . (int) PENDING_INVOICE_DUE_DAYS . ' days');
}

function pending_invoice_ageing_bucket(DateTime $today, $due_dt, $balance)
{
	if ($balance <= 0.009) {
		return null;
	}
	if (!$due_dt) {
		return '1_30';
	}
	$days = (int) $today->diff($due_dt)->format('%r%a');
	if ($days >= 0) {
		return 'not_due';
	}
	$over = abs($days);
	if ($over <= 30) {
		return '1_30';
	}
	if ($over <= 60) {
		return '31_60';
	}
	if ($over <= 90) {
		return '61_90';
	}
	if ($over <= 120) {
		return '91_120';
	}
	return '120_plus';
}

function pending_invoice_ageing_bucket_label($key)
{
	$map = array(
		'not_due' => 'Not Due',
		'1_30' => '1-30 Days',
		'31_60' => '31-60 Days',
		'61_90' => '61-90 Days',
		'91_120' => '91-120 Days',
		'120_plus' => '120+ Days',
	);
	return isset($map[$key]) ? $map[$key] : (string) $key;
}

function pending_invoice_ageing_overdue_days(DateTime $today, $due_dt)
{
	if (!$due_dt instanceof DateTime) {
		return 0;
	}
	$days = (int) $today->diff($due_dt)->format('%r%a');
	if ($days >= 0) {
		return 0;
	}
	return abs($days);
}

function pending_invoice_ageing_parse_filters($request)
{
	$from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
	$to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
	$customers = isset($request['customers']) ? trim($request['customers']) : '';
	$bucket = isset($request['bucket']) ? trim($request['bucket']) : '';

	if ($from_date === '' || $to_date === '') {
		return array('error' => 'From Date and To Date are required.');
	}

	$from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
	$to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
	if (!$from_dt || !$to_dt) {
		return array('error' => 'Invalid date format. Use dd-mm-yyyy.');
	}
	if ($from_dt > $to_dt) {
		return array('error' => 'From Date cannot be after To Date.');
	}

	$allowed_buckets = array('', 'not_due', '1_30', '31_60', '61_90', '91_120', '120_plus');
	if (!in_array($bucket, $allowed_buckets, true)) {
		$bucket = '';
	}

	return array(
		'from_date' => $from_date,
		'to_date' => $to_date,
		'from_mysql' => $from_dt->format('Y-m-d'),
		'to_mysql' => $to_dt->format('Y-m-d'),
		'customers' => $customers,
		'bucket' => $bucket,
	);
}

function pending_invoice_ageing_empty_bucket_totals()
{
	$keys = array('not_due', '1_30', '31_60', '61_90', '91_120', '120_plus');
	$out = array();
	foreach ($keys as $k) {
		$out[$k] = pending_invoice_ageing_format_money(0);
	}
	return $out;
}

function pending_invoice_ageing_empty_summary()
{
	return array(
		'invoice_value' => pending_invoice_ageing_format_money(0),
		'payment_received' => pending_invoice_ageing_format_money(0),
		'credit_note' => pending_invoice_ageing_format_money(0),
		'tds' => pending_invoice_ageing_format_money(0),
		'balance_amount' => pending_invoice_ageing_format_money(0),
		'row_count' => 0,
		'buckets' => pending_invoice_ageing_empty_bucket_totals(),
	);
}

function pending_invoice_ageing_fetch_rows($conn, $filters)
{
	if (!empty($filters['error'])) {
		return array('rows' => array(), 'summary' => pending_invoice_ageing_empty_summary());
	}

	ensure_billing_tables($conn);

	$from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);

	$where = "m.status='final'
		AND STR_TO_DATE(m.invoice_date, '%d-%m-%Y') >= '$from_esc'
		AND STR_TO_DATE(m.invoice_date, '%d-%m-%Y') <= '$to_esc'";

	if (!empty($filters['customers'])) {
		$ids = array_filter(array_map('intval', explode(',', $filters['customers'])));
		if (!empty($ids)) {
			$where .= ' AND m.customer_id IN (' . implode(',', $ids) . ')';
		}
	}

	$sql = "SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.grand_total, m.customer_id,
			c.client_company_name
		FROM billing_invoice_master m
		LEFT JOIN client c ON c.client_id = m.customer_id
		WHERE $where
		ORDER BY STR_TO_DATE(m.invoice_date, '%d-%m-%Y') ASC, m.billing_invoice_id ASC";

	$rows = array();
	$sum_inv = 0.0;
	$sum_paid = 0.0;
	$sum_cn = 0.0;
	$sum_tds = 0.0;
	$sum_bal = 0.0;
	$bucket_sums = array(
		'not_due' => 0.0,
		'1_30' => 0.0,
		'31_60' => 0.0,
		'61_90' => 0.0,
		'91_120' => 0.0,
		'120_plus' => 0.0,
	);
	$sno = 0;
	$today = new DateTime('today');
	$filter_bucket = isset($filters['bucket']) ? $filters['bucket'] : '';

	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$inv_id = (int) $r['billing_invoice_id'];
			$snap = billing_invoice_settlement_snapshot($conn, $inv_id, 0);
			if (!$snap) {
				continue;
			}

			$balance = (float) $snap['balance'];
			if ($balance <= 0.009) {
				continue; // pending only
			}

			$due_dt = pending_invoice_ageing_due_date($snap['invoice_date'] ?? '');
			$bucket = pending_invoice_ageing_bucket($today, $due_dt, $balance);
			if ($bucket === null) {
				continue;
			}
			if ($filter_bucket !== '' && $bucket !== $filter_bucket) {
				continue;
			}

			$sno++;
			$inv_val = (float) $snap['invoice_amount'];
			$paid = (float) $snap['already_received'];
			$cn = (float) $snap['credit_note'];
			$tds = (float) $snap['tds_prior'];
			$overdue_days = pending_invoice_ageing_overdue_days($today, $due_dt);

			$sum_inv += $inv_val;
			$sum_paid += $paid;
			$sum_cn += $cn;
			$sum_tds += $tds;
			$sum_bal += $balance;
			if (isset($bucket_sums[$bucket])) {
				$bucket_sums[$bucket] += $balance;
			}

			$customer = trim($r['client_company_name'] ?? '');
			if ($customer === '') {
				$customer = '—';
			}

			$rows[] = array(
				's_no' => $sno,
				'invoice_no' => $snap['invoice_no'],
				'invoice_date' => $snap['invoice_date'],
				'due_date' => $due_dt ? $due_dt->format('d-m-Y') : '—',
				'customer' => $customer,
				'invoice_value' => pending_invoice_ageing_format_money($inv_val),
				'payment_received' => pending_invoice_ageing_format_money($paid),
				'credit_note' => pending_invoice_ageing_format_money($cn),
				'tds' => pending_invoice_ageing_format_money($tds),
				'balance_amount' => pending_invoice_ageing_format_money($balance),
				'days_overdue' => $overdue_days,
				'ageing_bucket' => pending_invoice_ageing_bucket_label($bucket),
				'ageing_bucket_key' => $bucket,
			);
		}
	}

	$bucket_fmt = array();
	foreach ($bucket_sums as $k => $v) {
		$bucket_fmt[$k] = pending_invoice_ageing_format_money($v);
	}

	return array(
		'rows' => $rows,
		'summary' => array(
			'invoice_value' => pending_invoice_ageing_format_money($sum_inv),
			'payment_received' => pending_invoice_ageing_format_money($sum_paid),
			'credit_note' => pending_invoice_ageing_format_money($sum_cn),
			'tds' => pending_invoice_ageing_format_money($sum_tds),
			'balance_amount' => pending_invoice_ageing_format_money($sum_bal),
			'row_count' => count($rows),
			'buckets' => $bucket_fmt,
		),
	);
}
