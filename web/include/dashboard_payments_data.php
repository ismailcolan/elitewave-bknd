<?php
/**
 * Live payload for Payments Dashboard (dashboard_payments.php).
 */

require_once __DIR__ . '/dashboard_invoice_data.php';
require_once __DIR__ . '/billing_receipt_functions.php';
require_once __DIR__ . '/company_bank_helpers.php';

define('DASHBOARD_PAYMENTS_RECENT_LIMIT', 10);
define('DASHBOARD_PAYMENTS_TOP_CUSTOMERS', 5);
define('DASHBOARD_PAYMENTS_OUTSTANDING_LIMIT', 8);
define('DASHBOARD_PAYMENTS_ALERTS_LIMIT', 6);

function dashboard_payments_receipt_in_range($receipt_date_raw, ?DateTime $from, ?DateTime $to, $range_all)
{
	if ($range_all || !$from || !$to) {
		return true;
	}
	$dt = dashboard_booking_parse_date($receipt_date_raw);
	if (!$dt) {
		return false;
	}
	$dt->setTime(12, 0, 0);
	return $dt >= $from && $dt <= $to;
}

function dashboard_payments_receipt_status($payment_mode)
{
	$mode = strtoupper(trim((string) $payment_mode));
	if ($mode === 'CHEQUE') {
		return array('key' => 'pending', 'label' => 'Pending', 'class' => 'pay-st-pending');
	}
	return array('key' => 'cleared', 'label' => 'Cleared', 'class' => 'pay-st-cleared');
}

function dashboard_payments_mode_label($mode)
{
	$mode = strtoupper(trim((string) $mode));
	$modes = ew_company_bank_payment_modes();
	return $modes[$mode] ?? ($mode !== '' ? $mode : '—');
}

function dashboard_payments_mode_color($mode)
{
	$map = array(
		'RTGS' => '#1d4ed8',
		'NEFT' => '#3b82f6',
		'IMPS' => '#06b6d4',
		'CHEQUE' => '#8b5cf6',
		'UPI' => '#0891b2',
		'CASH' => '#f97316',
		'DD' => '#6366f1',
	);
	$key = strtoupper(trim((string) $mode));
	return $map[$key] ?? '#94a3b8';
}

/** Fixed-order rows for Payment Mode (Collections) card — legend always lists bank modes; chart omits zero slices. */
function dashboard_payments_mode_chart_rows(array $mode_totals)
{
	$core = array('RTGS', 'NEFT', 'IMPS', 'CHEQUE');
	$sum = array_sum($mode_totals);
	$rows = array();
	$listed = array();

	foreach ($core as $mode) {
		$listed[$mode] = true;
		$total = (float) ($mode_totals[$mode] ?? 0);
		$pct = $sum > 0.009 ? (int) round($total * 100 / $sum) : 0;
		$rows[] = array(
			'name' => dashboard_payments_mode_label($mode),
			'key' => $mode,
			'count' => round($total, 2),
			'pct' => $pct,
			'color' => dashboard_payments_mode_color($mode),
		);
	}

	foreach ($mode_totals as $mode => $total) {
		$mode = strtoupper(trim((string) $mode));
		if ($mode === '' || !empty($listed[$mode]) || $total <= 0.009) {
			continue;
		}
		$rows[] = array(
			'name' => dashboard_payments_mode_label($mode),
			'key' => $mode,
			'count' => round($total, 2),
			'pct' => $sum > 0.009 ? (int) round($total * 100 / $sum) : 0,
			'color' => dashboard_payments_mode_color($mode),
		);
	}

	return $rows;
}

function dashboard_payments_load($conn, $params = array())
{
	ensure_billing_tables($conn);
	ensure_billing_receipt_tables($conn);

	$from_raw = $params['date_from'] ?? '';
	$to_raw = $params['date_to'] ?? '';
	$branch_id = (int) ($params['branch_id'] ?? 0);
	list($from, $to, $range_all) = dashboard_booking_resolve_range($from_raw, $to_raw);

	$today = new DateTime('today');
	$today_str = $today->format('d-m-Y');
	$month_start = (clone $today)->modify('first day of this month')->setTime(0, 0, 0);
	$days90_start = (clone $today)->modify('-90 days')->setTime(0, 0, 0);

	$fmt = function ($n) {
		return dashboard_booking_format_inr($n);
	};

	$kpi = array(
		'total_receivables' => 0.0,
		'todays_collections' => 0.0,
		'month_collections' => 0.0,
		'pending_collections' => 0.0,
		'overdue_amount' => 0.0,
		'advance_received' => 0.0,
		'unallocated_payments' => 0.0,
	);

	$collection_bars = array(
		'invoiced' => 0.0,
		'due' => 0.0,
		'collected' => 0.0,
		'outstanding' => 0.0,
	);
	$mode_totals = array();
	$customer_collections = array();
	$recent = array();
	$customer_out = array();
	$alerts = array();

	$invoice_rows = array();
	if (dashboard_ops_table_exists($conn, 'billing_invoice_master')) {
		$q = mysqli_query($conn, "SELECT m.*, c.client_company_name
			FROM billing_invoice_master m
			LEFT JOIN client c ON c.client_id = m.customer_id
			WHERE m.status='final'");
		if ($q) {
			while ($row = mysqli_fetch_assoc($q)) {
				$invoice_rows[] = $row;
			}
		}
	}

	$final_ids = array_map(function ($r) {
		return (int) $r['billing_invoice_id'];
	}, $invoice_rows);
	$paid_map = dashboard_invoice_paid_map($conn, $final_ids);
	$cn_map = dashboard_invoice_cn_map($conn, $final_ids);
	$tds_map = dashboard_invoice_tds_map($conn, $final_ids);

	$cust_billing = array();
	$cust_paid = array();
	$cust_outstanding = array();
	$cust_overdue = array();

	foreach ($invoice_rows as $row) {
		$id = (int) $row['billing_invoice_id'];
		$cid = (int) ($row['customer_id'] ?? 0);
		$paid = (float) ($paid_map[$id] ?? 0);
		$cn = (float) ($cn_map[$id] ?? 0);
		$tds = (float) ($tds_map[$id] ?? 0);
		$balance = dashboard_invoice_balance($row, $paid, $cn, $tds);
		$dstatus = dashboard_invoice_display_status($row, $balance, $today);
		$gt = (float) ($row['grand_total'] ?? 0);
		$in_range = dashboard_invoice_in_date_range($row['invoice_date'] ?? '', $from, $to, $range_all);

		if ($in_range) {
			$collection_bars['invoiced'] += $gt;
			$collection_bars['due'] += $gt;
		}

		if ($balance > 0.009) {
			$kpi['total_receivables'] += $balance;
			$collection_bars['outstanding'] += $balance;
			if ($dstatus === 'overdue') {
				$kpi['overdue_amount'] += $balance;
			} else {
				$kpi['pending_collections'] += $balance;
			}
		}

		$name = trim((string) ($row['client_company_name'] ?? '')) ?: '—';
		$key = $cid > 0 ? 'c' . $cid : 'n' . md5($name);
		if (!isset($cust_billing[$key])) {
			$cust_billing[$key] = array('name' => $name, 'billing' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'overdue' => 0.0);
		}
		$cust_billing[$key]['billing'] += $gt;
		$cust_billing[$key]['paid'] += min($gt, $paid + $cn + $tds);
		$cust_billing[$key]['outstanding'] += $balance;
		if ($dstatus === 'overdue') {
			$cust_billing[$key]['overdue'] += $balance;
		}

		if ($dstatus === 'overdue' && $balance > 0.009) {
			$due = dashboard_invoice_due_date($row['invoice_date'] ?? '');
			$days = $due ? (int) $today->diff($due)->days : 0;
			$alerts[] = array(
				'type' => 'overdue',
				'title' => 'Invoice ' . ($row['invoice_no'] ?? '') . ' is overdue',
				'detail' => $name . ' · ₹ ' . $fmt($balance) . ' · ' . $days . ' days',
				'date' => $row['invoice_date'] ?? '',
				'sort' => $days,
			);
		}
	}

	$receipts = array();
	$rq = mysqli_query($conn, "SELECT m.*, c.client_company_name
		FROM billing_receipt_master m
		LEFT JOIN client c ON c.client_id = m.party_id
		WHERE m.status='final'
		ORDER BY m.billing_receipt_id DESC");
	if ($rq) {
		while ($row = mysqli_fetch_assoc($rq)) {
			$receipts[] = $row;
		}
	}

	$bank_cache = array();
	$get_bank = function ($bank_id) use ($conn, &$bank_cache) {
		$bank_id = (int) $bank_id;
		if ($bank_id <= 0) {
			return '—';
		}
		if (!isset($bank_cache[$bank_id])) {
			$b = ew_company_bank_get($conn, $bank_id);
			$bank_cache[$bank_id] = $b && !empty($b['label']) ? $b['label'] : '—';
		}
		return $bank_cache[$bank_id];
	};

	$collected_90 = 0.0;
	$invoiced_90 = 0.0;
	foreach ($invoice_rows as $row) {
		$inv_dt = dashboard_booking_parse_date($row['invoice_date'] ?? '');
		if ($inv_dt && $inv_dt >= $days90_start) {
			$invoiced_90 += (float) ($row['grand_total'] ?? 0);
		}
	}

	$receipts_in_range = array();
	foreach ($receipts as $row) {
		$rid = (int) $row['billing_receipt_id'];
		$amt = (float) ($row['total_amount'] ?? 0);
		$rdate = $row['receipt_date'] ?? '';
		$rdt = dashboard_booking_parse_date($rdate);
		$in_range = dashboard_payments_receipt_in_range($rdate, $from, $to, $range_all);

		if ($rdate === $today_str) {
			$kpi['todays_collections'] += $amt;
		}
		if ($rdt && $rdt >= $month_start) {
			$kpi['month_collections'] += $amt;
		}
		if ($rdt && $rdt >= $days90_start) {
			$collected_90 += $amt;
		}
		$rtype = (string) ($row['receipt_type'] ?? '');
		if ($in_range && $rtype === 'advance') {
			$kpi['advance_received'] += $amt;
		}
		if ($in_range && $rtype !== 'against_invoice') {
			$kpi['unallocated_payments'] += $amt;
		}

		if ($in_range) {
			$collection_bars['collected'] += $amt;
			$mode = strtoupper(trim((string) ($row['payment_mode'] ?? '')));
			if ($mode === '') {
				$mode = 'OTHER';
			}
			if (!isset($mode_totals[$mode])) {
				$mode_totals[$mode] = 0.0;
			}
			$mode_totals[$mode] += $amt;

			$cname = trim((string) ($row['client_company_name'] ?? '')) ?: trim((string) ($row['payer_name'] ?? '')) ?: '—';
			$ck = (int) ($row['party_id'] ?? 0) > 0 ? 'c' . (int) $row['party_id'] : 'n' . md5($cname);
			if (!isset($customer_collections[$ck])) {
				$customer_collections[$ck] = array('name' => $cname, 'amount' => 0.0);
			}
			$customer_collections[$ck]['amount'] += $amt;

			$receipts_in_range[] = $row;
		}

		$mode = strtoupper(trim((string) ($row['payment_mode'] ?? '')));
		if ($mode === 'CHEQUE' && $rdt && $rdt >= (clone $today)->modify('-45 days')) {
			$st = dashboard_payments_receipt_status($mode);
			if ($st['key'] === 'pending') {
				$alerts[] = array(
					'type' => 'cheque',
					'title' => 'Cheque pending clearance',
					'detail' => ($row['receipt_no'] ?? '') . ' · ₹ ' . $fmt($amt),
					'date' => $rdate,
					'sort' => $rdt ? $rdt->getTimestamp() : 0,
				);
			}
		}
	}

	usort($receipts_in_range, function ($a, $b) {
		$da = dashboard_booking_parse_date($a['receipt_date'] ?? '');
		$db = dashboard_booking_parse_date($b['receipt_date'] ?? '');
		$ta = $da ? $da->getTimestamp() : 0;
		$tb = $db ? $db->getTimestamp() : 0;
		if ($ta === $tb) {
			return (int) $b['billing_receipt_id'] - (int) $a['billing_receipt_id'];
		}
		return $tb - $ta;
	});

	foreach (array_slice($receipts_in_range, 0, (int) DASHBOARD_PAYMENTS_RECENT_LIMIT) as $row) {
		$rid = (int) $row['billing_receipt_id'];
		$invoice_no = '—';
		$gcn_no = '—';
		$unalloc = '—';
		$lq = mysqli_query($conn, "SELECT invoice_no, billing_invoice_id FROM billing_receipt_lines WHERE billing_receipt_id='$rid' ORDER BY line_id ASC LIMIT 1");
		if ($lq && ($ln = mysqli_fetch_assoc($lq))) {
			$invoice_no = $ln['invoice_no'] ?? '—';
			$iid = (int) ($ln['billing_invoice_id'] ?? 0);
			if ($iid > 0) {
				$gq = mysqli_query($conn, "SELECT grn_no FROM billing_invoice_details WHERE billing_invoice_id='$iid' ORDER BY detail_id ASC LIMIT 1");
				if ($gq && ($gr = mysqli_fetch_assoc($gq))) {
					$gcn_no = $gr['grn_no'] ?? '—';
				}
			}
		}
		if (($row['receipt_type'] ?? '') !== 'against_invoice') {
			$unalloc = dashboard_booking_format_inr((float) ($row['total_amount'] ?? 0));
		}
		$st = dashboard_payments_receipt_status($row['payment_mode'] ?? '');
		$cname = trim((string) ($row['client_company_name'] ?? '')) ?: trim((string) ($row['payer_name'] ?? '')) ?: '—';
		$recent[] = array(
			'receipt_no' => $row['receipt_no'] ?? '—',
			'receipt_date' => $row['receipt_date'] ?? '',
			'customer' => $cname,
			'invoice_no' => $invoice_no,
			'gcn_no' => $gcn_no,
			'amount' => dashboard_booking_format_inr((float) ($row['total_amount'] ?? 0)),
			'mode' => dashboard_payments_mode_label($row['payment_mode'] ?? ''),
			'ref' => trim((string) ($row['payment_ref'] ?? '')) ?: '—',
			'bank' => $get_bank($row['bank_account_id'] ?? 0),
			'tds' => dashboard_booking_format_inr((float) ($row['tds_total'] ?? 0)),
			'unallocated' => $unalloc,
			'status_label' => $st['label'],
			'status_class' => $st['class'],
			'view_url' => 'create_receipt.php?id=' . $rid,
		);
	}

	usort($customer_collections, function ($a, $b) {
		return $b['amount'] <=> $a['amount'];
	});
	$top_customers = array();
	foreach (array_slice($customer_collections, 0, (int) DASHBOARD_PAYMENTS_TOP_CUSTOMERS) as $c) {
		$top_customers[] = array(
			'name' => $c['name'],
			'amount' => $c['amount'],
			'amount_fmt' => $fmt($c['amount']),
		);
	}

	$mode_chart = dashboard_payments_mode_chart_rows($mode_totals);

	$cust_list = array_values($cust_billing);
	usort($cust_list, function ($a, $b) {
		return $b['outstanding'] <=> $a['outstanding'];
	});
	foreach (array_slice($cust_list, 0, (int) DASHBOARD_PAYMENTS_OUTSTANDING_LIMIT) as $c) {
		if ($c['outstanding'] <= 0.009 && $c['billing'] <= 0.009) {
			continue;
		}
		$credit = 'Good';
		$credit_class = 'pay-credit-good';
		if ($c['overdue'] > 0.009) {
			$credit = 'Overdue';
			$credit_class = 'pay-credit-overdue';
		} elseif ($c['outstanding'] > $c['billing'] * 0.5 && $c['outstanding'] > 100000) {
			$credit = 'Watch';
			$credit_class = 'pay-credit-watch';
		}
		$customer_out[] = array(
			'name' => $c['name'],
			'billing_fmt' => $fmt($c['billing']),
			'paid_fmt' => $fmt($c['paid']),
			'outstanding_fmt' => $fmt($c['outstanding']),
			'overdue_fmt' => $fmt($c['overdue']),
			'credit' => $credit,
			'credit_class' => $credit_class,
		);
	}

	usort($alerts, function ($a, $b) {
		return ($b['sort'] ?? 0) <=> ($a['sort'] ?? 0);
	});
	$alerts = array_slice($alerts, 0, (int) DASHBOARD_PAYMENTS_ALERTS_LIMIT);

	$efficiency = 0.0;
	if ($invoiced_90 > 0.009) {
		$efficiency = min(100, round($collected_90 / $invoiced_90 * 100, 1));
	}

	$bar_max = max(
		$collection_bars['invoiced'],
		$collection_bars['due'],
		$collection_bars['collected'],
		$collection_bars['outstanding'],
		1
	);

	return array(
		'ok' => true,
		'range' => array(
			'all' => $range_all,
			'from' => $range_all ? '' : $from->format('d-m-Y'),
			'to' => $range_all ? '' : $to->format('d-m-Y'),
		),
		'branch_id' => $branch_id,
		'branches' => dashboard_booking_branch_options($conn),
		'kpi' => array(
			'total_receivables' => $kpi['total_receivables'],
			'total_receivables_fmt' => $fmt($kpi['total_receivables']),
			'todays_collections' => $kpi['todays_collections'],
			'todays_collections_fmt' => $fmt($kpi['todays_collections']),
			'month_collections' => $kpi['month_collections'],
			'month_collections_fmt' => $fmt($kpi['month_collections']),
			'pending_collections' => $kpi['pending_collections'],
			'pending_collections_fmt' => $fmt($kpi['pending_collections']),
			'overdue_amount' => $kpi['overdue_amount'],
			'overdue_amount_fmt' => $fmt($kpi['overdue_amount']),
			'advance_received' => $kpi['advance_received'],
			'advance_received_fmt' => $fmt($kpi['advance_received']),
			'unallocated_payments' => $kpi['unallocated_payments'],
			'unallocated_payments_fmt' => $fmt($kpi['unallocated_payments']),
		),
		'collection_bars' => $collection_bars,
		'collection_bar_labels' => array('Invoiced', 'Due', 'Collected', 'Outstanding'),
		'collection_bar_fmt' => array(
			'invoiced' => $fmt($collection_bars['invoiced']),
			'due' => $fmt($collection_bars['due']),
			'collected' => $fmt($collection_bars['collected']),
			'outstanding' => $fmt($collection_bars['outstanding']),
		),
		'bar_max' => $bar_max,
		'efficiency_pct' => $efficiency,
		'mode_chart' => $mode_chart,
		'mode_collected_fmt' => $fmt($collection_bars['collected']),
		'mode_collected_short' => dashboard_booking_format_inr_short($collection_bars['collected']),
		'top_customers' => $top_customers,
		'recent' => $recent,
		'customer_outstanding' => $customer_out,
		'alerts' => $alerts,
	);
}
