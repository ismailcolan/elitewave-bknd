<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_receipt_functions.php';

function receipt_register_format_money($val)
{
	return number_format((float) $val, 2, '.', '');
}

function receipt_register_parse_filters($request)
{
	$from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
	$to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
	$customers = isset($request['customers']) ? trim($request['customers']) : '';
	$receipt_type = isset($request['receipt_type']) ? trim($request['receipt_type']) : '';

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

	$allowed_types = array('', 'against_invoice', 'advance', 'investment', 'general');
	if (!in_array($receipt_type, $allowed_types, true)) {
		$receipt_type = '';
	}

	return array(
		'from_date' => $from_date,
		'to_date' => $to_date,
		'from_mysql' => $from_dt->format('Y-m-d'),
		'to_mysql' => $to_dt->format('Y-m-d'),
		'customers' => $customers,
		'receipt_type' => $receipt_type,
	);
}

function receipt_register_empty_summary()
{
	return array(
		'total_amount' => receipt_register_format_money(0),
		'tds_total' => receipt_register_format_money(0),
		'total_settled' => receipt_register_format_money(0),
		'row_count' => 0,
	);
}

function receipt_register_invoice_nos($conn, $receipt_id)
{
	$receipt_id = (int) $receipt_id;
	$nos = array();
	$q = mysqli_query($conn, "SELECT DISTINCT invoice_no FROM billing_receipt_lines
		WHERE billing_receipt_id='$receipt_id' AND invoice_no IS NOT NULL AND invoice_no!=''
		ORDER BY invoice_no ASC");
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$no = trim((string) ($r['invoice_no'] ?? ''));
			if ($no !== '') {
				$nos[] = $no;
			}
		}
	}
	return empty($nos) ? '—' : implode(', ', $nos);
}

function receipt_register_fetch_rows($conn, $filters)
{
	if (!empty($filters['error'])) {
		return array('rows' => array(), 'summary' => receipt_register_empty_summary());
	}

	ensure_billing_tables($conn);
	ensure_billing_receipt_tables($conn);

	$from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);

	$where = "m.status='final'
		AND STR_TO_DATE(m.receipt_date, '%d-%m-%Y') >= '$from_esc'
		AND STR_TO_DATE(m.receipt_date, '%d-%m-%Y') <= '$to_esc'";

	if (!empty($filters['customers'])) {
		$ids = array_filter(array_map('intval', explode(',', $filters['customers'])));
		if (!empty($ids)) {
			$where .= ' AND m.party_id IN (' . implode(',', $ids) . ')';
		}
	}
	if (!empty($filters['receipt_type'])) {
		$type_esc = mysqli_real_escape_string($conn, $filters['receipt_type']);
		$where .= " AND m.receipt_type='$type_esc'";
	}

	$sql = "SELECT m.billing_receipt_id, m.receipt_no, m.receipt_date, m.receipt_type,
			m.party_id, m.payer_name, m.total_amount, m.tds_total, m.payment_mode, m.payment_ref,
			c.client_company_name
		FROM billing_receipt_master m
		LEFT JOIN client c ON c.client_id = m.party_id
		WHERE $where
		ORDER BY STR_TO_DATE(m.receipt_date, '%d-%m-%Y') ASC, m.billing_receipt_id ASC";

	$rows = array();
	$sum_amt = 0.0;
	$sum_tds = 0.0;
	$sum_settled = 0.0;
	$sno = 0;

	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$sno++;
			$amt = (float) ($r['total_amount'] ?? 0);
			$tds = (float) ($r['tds_total'] ?? 0);
			$settled = $amt + $tds;
			$sum_amt += $amt;
			$sum_tds += $tds;
			$sum_settled += $settled;

			$customer = trim((string) ($r['client_company_name'] ?? ''));
			if ($customer === '') {
				$customer = trim((string) ($r['payer_name'] ?? ''));
			}
			if ($customer === '') {
				$customer = '—';
			}

			$rid = (int) $r['billing_receipt_id'];
			$balance = '—';
			if (($r['receipt_type'] ?? '') === 'against_invoice') {
				$balance = billing_receipt_list_balance_after($conn, $rid);
			}

			$rows[] = array(
				's_no' => $sno,
				'receipt_no' => $r['receipt_no'] ?? '',
				'receipt_date' => $r['receipt_date'] ?? '',
				'receipt_type' => billing_receipt_type_label($r['receipt_type'] ?? ''),
				'customer' => $customer,
				'total_amount' => receipt_register_format_money($amt),
				'tds_total' => receipt_register_format_money($tds),
				'total_settled' => receipt_register_format_money($settled),
				'balance_after' => $balance,
				'payment_mode' => $r['payment_mode'] ?? '',
				'payment_ref' => $r['payment_ref'] ?? '',
				'invoices' => receipt_register_invoice_nos($conn, $rid),
			);
		}
	}

	return array(
		'rows' => $rows,
		'summary' => array(
			'total_amount' => receipt_register_format_money($sum_amt),
			'tds_total' => receipt_register_format_money($sum_tds),
			'total_settled' => receipt_register_format_money($sum_settled),
			'row_count' => count($rows),
		),
	);
}
