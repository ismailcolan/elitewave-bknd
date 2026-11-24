<?php

require_once __DIR__ . '/expense_bill_helpers.php';
require_once __DIR__ . '/expense_general_helpers.php';
require_once __DIR__ . '/billing_receipt_functions.php';

function tds_register_format_money($val)
{
	return number_format((float) $val, 2, '.', '');
}

function tds_register_format_percent($val)
{
	$val = round((float) $val, 2);
	if ($val <= 0) {
		return '—';
	}
	return rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.') . '%';
}

function tds_register_parse_dates($request)
{
	$from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
	$to_date = isset($request['to_date']) ? trim($request['to_date']) : '';

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

	return array(
		'from_date' => $from_date,
		'to_date' => $to_date,
		'from_mysql' => $from_dt->format('Y-m-d'),
		'to_mysql' => $to_dt->format('Y-m-d'),
	);
}

function tds_register_empty_summary()
{
	return array(
		'base_amount' => tds_register_format_money(0),
		'tds_amount' => tds_register_format_money(0),
		'net_amount' => tds_register_format_money(0),
		'row_count' => 0,
	);
}

function tds_output_doc_type_options()
{
	return array(
		'' => 'All documents',
		'vendor_bill' => 'Purchase Bill Registration',
		'payment_voucher' => 'Payment Voucher',
	);
}

function tds_input_doc_type_options()
{
	return array(
		'' => 'All documents',
		'receipt' => 'Payment Receipt',
	);
}

function tds_output_parse_filters($request)
{
	$base = tds_register_parse_dates($request);
	if (!empty($base['error'])) {
		return $base;
	}
	$doc_type = isset($request['doc_type']) ? strtolower(trim($request['doc_type'])) : '';
	$vendors = isset($request['vendors']) ? trim($request['vendors']) : '';
	$allowed = array_keys(tds_output_doc_type_options());
	if (!in_array($doc_type, $allowed, true)) {
		$doc_type = '';
	}
	$base['doc_type'] = $doc_type;
	$base['vendors'] = $vendors;
	return $base;
}

function tds_input_parse_filters($request)
{
	$base = tds_register_parse_dates($request);
	if (!empty($base['error'])) {
		return $base;
	}
	$doc_type = isset($request['doc_type']) ? strtolower(trim($request['doc_type'])) : '';
	$customers = isset($request['customers']) ? trim($request['customers']) : '';
	$allowed = array_keys(tds_input_doc_type_options());
	if (!in_array($doc_type, $allowed, true)) {
		$doc_type = '';
	}
	$base['doc_type'] = $doc_type;
	$base['customers'] = $customers;
	return $base;
}

function tds_register_want_doc($filters, $type)
{
	$selected = $filters['doc_type'] ?? '';
	return ($selected === '' || $selected === $type);
}

/**
 * TDS Output = TDS we deduct on vendor bills + payment vouchers.
 */
function tds_output_fetch_rows($conn, $filters)
{
	if (!empty($filters['error'])) {
		return array('rows' => array(), 'summary' => tds_register_empty_summary());
	}

	expense_bill_ensure_schema($conn);
	expense_general_ensure_schema($conn);

	$from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);
	$events = array();

	$vendor_sql = '';
	if (!empty($filters['vendors'])) {
		$ids = array_filter(array_map('intval', explode(',', $filters['vendors'])));
		if (!empty($ids)) {
			$vendor_sql = ' AND b.vendor_id IN (' . implode(',', $ids) . ')';
			$pv_vendor_sql = ' AND h.vendor_id IN (' . implode(',', $ids) . ')';
		} else {
			$pv_vendor_sql = '';
		}
	} else {
		$pv_vendor_sql = '';
	}

	if (tds_register_want_doc($filters, 'vendor_bill')) {
		$sql = "SELECT b.bill_id, b.bill_entry_no, b.bill_date, b.vendor_invoice_no, b.vendor_id,
				b.subtotal, b.tds_percent, b.tds_amount, b.net_payable, b.grand_total,
				v.vendor_code, v.vendor_name, v.pan_no
			FROM vendor_bill b
			LEFT JOIN vendor_master v ON v.vendor_id = b.vendor_id
			WHERE b.status=0
				AND COALESCE(b.tds_amount,0) > 0
				AND STR_TO_DATE(b.bill_date, '%d-%m-%Y') >= '$from_esc'
				AND STR_TO_DATE(b.bill_date, '%d-%m-%Y') <= '$to_esc'
				$vendor_sql
			ORDER BY STR_TO_DATE(b.bill_date, '%d-%m-%Y') ASC, b.bill_id ASC";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($r = mysqli_fetch_assoc($q)) {
				$dt = DateTime::createFromFormat('d-m-Y', trim((string) ($r['bill_date'] ?? '')));
				if (!$dt) {
					continue;
				}
				$base_amt = round((float) ($r['subtotal'] ?? 0), 2);
				$tds = round((float) ($r['tds_amount'] ?? 0), 2);
				$pct = round((float) ($r['tds_percent'] ?? 0), 2);
				$net = round((float) ($r['net_payable'] ?? 0), 2);
				if ($net <= 0) {
					$net = round((float) ($r['grand_total'] ?? 0) - $tds, 2);
				}
				$party = trim((string) (($r['vendor_code'] ?? '') . ' - ' . ($r['vendor_name'] ?? '')), ' -');
				$pan = strtoupper(trim((string) ($r['pan_no'] ?? '')));
				$ref = trim((string) ($r['bill_entry_no'] ?? ''));
				$vinv = trim((string) ($r['vendor_invoice_no'] ?? ''));
				$events[] = array(
					'sort_date' => $dt->format('Y-m-d'),
					'doc_id' => (int) $r['bill_id'],
					'doc_order' => 10,
					'doc_type' => 'vendor_bill',
					'doc_type_label' => 'Bill Registration',
					'doc_no' => $ref !== '' ? $ref : ('BILL-' . (int) $r['bill_id']),
					'doc_date' => $dt->format('d-m-Y'),
					'party' => $party !== '' ? $party : '—',
					'pan' => $pan !== '' ? $pan : '—',
					'base_amount' => $base_amt,
					'tds_percent' => $pct,
					'tds_amount' => $tds,
					'net_amount' => $net,
					'against' => $vinv,
				);
			}
		}
	}

	if (tds_register_want_doc($filters, 'payment_voucher')) {
		$sql = "SELECT h.general_expense_id, h.expense_no, h.expense_date, h.vendor_id,
				h.expense_amount, h.tds_percent, h.total_tds, h.net_payable, h.voucher_type,
				v.vendor_code, v.vendor_name, v.pan_no
			FROM general_expense_header h
			LEFT JOIN vendor_master v ON v.vendor_id = h.vendor_id
			WHERE h.status=0
				AND COALESCE(h.total_tds,0) > 0
				AND STR_TO_DATE(h.expense_date, '%d-%m-%Y') >= '$from_esc'
				AND STR_TO_DATE(h.expense_date, '%d-%m-%Y') <= '$to_esc'
				$pv_vendor_sql
			ORDER BY STR_TO_DATE(h.expense_date, '%d-%m-%Y') ASC, h.general_expense_id ASC";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($r = mysqli_fetch_assoc($q)) {
				$dt = DateTime::createFromFormat('d-m-Y', trim((string) ($r['expense_date'] ?? '')));
				if (!$dt) {
					continue;
				}
				$base_amt = round((float) ($r['expense_amount'] ?? 0), 2);
				$tds = round((float) ($r['total_tds'] ?? 0), 2);
				$pct = round((float) ($r['tds_percent'] ?? 0), 2);
				$net = round((float) ($r['net_payable'] ?? 0), 2);
				$party = trim((string) (($r['vendor_code'] ?? '') . ' - ' . ($r['vendor_name'] ?? '')), ' -');
				$pan = strtoupper(trim((string) ($r['pan_no'] ?? '')));
				$ref = trim((string) ($r['expense_no'] ?? ''));
				$vtype = strtolower(trim((string) ($r['voucher_type'] ?? 'general')));
				$label = ($vtype === 'gcn') ? 'GCN Payment Voucher' : 'Payment Voucher';
				$events[] = array(
					'sort_date' => $dt->format('Y-m-d'),
					'doc_id' => (int) $r['general_expense_id'],
					'doc_order' => 20,
					'doc_type' => 'payment_voucher',
					'doc_type_label' => $label,
					'doc_no' => $ref !== '' ? $ref : ('PV-' . (int) $r['general_expense_id']),
					'doc_date' => $dt->format('d-m-Y'),
					'party' => $party !== '' ? $party : '—',
					'pan' => $pan !== '' ? $pan : '—',
					'base_amount' => $base_amt,
					'tds_percent' => $pct,
					'tds_amount' => $tds,
					'net_amount' => $net,
					'against' => '',
				);
			}
		}
	}

	usort($events, function ($a, $b) {
		if ($a['sort_date'] !== $b['sort_date']) {
			return strcmp($a['sort_date'], $b['sort_date']);
		}
		if ($a['doc_order'] !== $b['doc_order']) {
			return $a['doc_order'] - $b['doc_order'];
		}
		return $a['doc_id'] - $b['doc_id'];
	});

	return tds_register_finalize_rows($events);
}

/**
 * TDS Input = TDS customers deduct on payment receipts.
 */
function tds_input_fetch_rows($conn, $filters)
{
	if (!empty($filters['error'])) {
		return array('rows' => array(), 'summary' => tds_register_empty_summary());
	}

	ensure_billing_receipt_tables($conn);

	$from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);
	$events = array();

	$customer_sql = '';
	if (!empty($filters['customers'])) {
		$ids = array_filter(array_map('intval', explode(',', $filters['customers'])));
		if (!empty($ids)) {
			$customer_sql = ' AND m.party_id IN (' . implode(',', $ids) . ')';
		}
	}

	if (tds_register_want_doc($filters, 'receipt')) {
		$sql = "SELECT m.billing_receipt_id, m.receipt_no, m.receipt_date, m.receipt_type,
				m.party_id, m.payer_name, m.total_amount, m.tds_total,
				c.client_company_name, c.pan_no
			FROM billing_receipt_master m
			LEFT JOIN client c ON c.client_id = m.party_id
			WHERE m.status='final'
				AND COALESCE(m.tds_total,0) > 0
				AND STR_TO_DATE(m.receipt_date, '%d-%m-%Y') >= '$from_esc'
				AND STR_TO_DATE(m.receipt_date, '%d-%m-%Y') <= '$to_esc'
				$customer_sql
			ORDER BY STR_TO_DATE(m.receipt_date, '%d-%m-%Y') ASC, m.billing_receipt_id ASC";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($r = mysqli_fetch_assoc($q)) {
				$dt = DateTime::createFromFormat('d-m-Y', trim((string) ($r['receipt_date'] ?? '')));
				if (!$dt) {
					continue;
				}
				$received = round((float) ($r['total_amount'] ?? 0), 2);
				$tds = round((float) ($r['tds_total'] ?? 0), 2);
				$gross = round($received + $tds, 2);
				$party = trim((string) ($r['client_company_name'] ?? ''));
				if ($party === '') {
					$party = trim((string) ($r['payer_name'] ?? ''));
				}
				$pan = strtoupper(trim((string) ($r['pan_no'] ?? '')));
				$ref = trim((string) ($r['receipt_no'] ?? ''));
				$rtype = trim((string) ($r['receipt_type'] ?? ''));
				$rtype_label = $rtype !== '' ? ucwords(str_replace('_', ' ', $rtype)) : '';

				/* Collect invoice refs from lines */
				$rid = (int) $r['billing_receipt_id'];
				$against_parts = array();
				$lq = mysqli_query($conn, "SELECT invoice_no, tds_amount FROM billing_receipt_lines
					WHERE billing_receipt_id='$rid' AND COALESCE(tds_amount,0) > 0
					ORDER BY line_id ASC");
				if ($lq) {
					while ($ln = mysqli_fetch_assoc($lq)) {
						$inv = trim((string) ($ln['invoice_no'] ?? ''));
						if ($inv !== '') {
							$against_parts[] = $inv;
						}
					}
				}
				$against = implode(', ', array_slice($against_parts, 0, 5));
				if (count($against_parts) > 5) {
					$against .= ' +' . (count($against_parts) - 5);
				}

				$events[] = array(
					'sort_date' => $dt->format('Y-m-d'),
					'doc_id' => $rid,
					'doc_order' => 10,
					'doc_type' => 'receipt',
					'doc_type_label' => 'Payment Receipt',
					'doc_no' => $ref !== '' ? $ref : ('RCP-' . $rid),
					'doc_date' => $dt->format('d-m-Y'),
					'party' => $party !== '' ? $party : '—',
					'pan' => $pan !== '' ? $pan : '—',
					'base_amount' => $gross,
					'tds_percent' => 0,
					'tds_amount' => $tds,
					'net_amount' => $received,
					'against' => $against,
					'extra_label' => $rtype_label,
				);
			}
		}
	}

	usort($events, function ($a, $b) {
		if ($a['sort_date'] !== $b['sort_date']) {
			return strcmp($a['sort_date'], $b['sort_date']);
		}
		return $a['doc_id'] - $b['doc_id'];
	});

	return tds_register_finalize_rows($events);
}

function tds_register_finalize_rows($events)
{
	$rows = array();
	$sum_base = 0.0;
	$sum_tds = 0.0;
	$sum_net = 0.0;
	$sno = 0;

	foreach ($events as $e) {
		$sno++;
		$base = (float) $e['base_amount'];
		$tds = (float) $e['tds_amount'];
		$net = (float) $e['net_amount'];
		$pct = (float) ($e['tds_percent'] ?? 0);

		$sum_base += $base;
		$sum_tds += $tds;
		$sum_net += $net;

		$against = trim((string) ($e['against'] ?? ''));

		$rows[] = array(
			's_no' => $sno,
			'doc_type' => $e['doc_type'],
			'doc_type_label' => $e['doc_type_label'],
			'doc_no' => $e['doc_no'],
			'doc_date' => $e['doc_date'],
			'party' => $e['party'],
			'pan' => $e['pan'],
			'against' => $against !== '' ? $against : '—',
			'base_amount' => tds_register_format_money($base),
			'tds_percent' => tds_register_format_percent($pct),
			'tds_percent_raw' => $pct,
			'tds_amount' => tds_register_format_money($tds),
			'net_amount' => tds_register_format_money($net),
			'base_raw' => $base,
			'tds_raw' => $tds,
			'net_raw' => $net,
		);
	}

	return array(
		'rows' => $rows,
		'summary' => array(
			'base_amount' => tds_register_format_money($sum_base),
			'tds_amount' => tds_register_format_money($sum_tds),
			'net_amount' => tds_register_format_money($sum_net),
			'row_count' => count($rows),
		),
	);
}
