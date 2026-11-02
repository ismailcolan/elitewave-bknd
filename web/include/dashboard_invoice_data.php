<?php
/**
 * Live payload for Invoice Dashboard (dashboard_invoice.php).
 */

require_once __DIR__ . '/dashboard_booking_data.php';
require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_receipt_functions.php';

define('DASHBOARD_INVOICE_DUE_DAYS', 15);
define('DASHBOARD_INVOICE_RECENT_LIMIT', 5);

function dashboard_invoice_due_date($invoice_date_raw)
{
	$dt = dashboard_booking_parse_date($invoice_date_raw);
	if (!$dt) {
		return null;
	}
	return (clone $dt)->modify('+' . (int) DASHBOARD_INVOICE_DUE_DAYS . ' days');
}

function dashboard_invoice_in_date_range($invoice_date_raw, ?DateTime $from, ?DateTime $to, $range_all)
{
	if ($range_all || !$from || !$to) {
		return true;
	}
	$dt = dashboard_booking_parse_date($invoice_date_raw);
	if (!$dt) {
		return false;
	}
	$dt->setTime(12, 0, 0);
	return $dt >= $from && $dt <= $to;
}

function dashboard_invoice_paid_map($conn, array $invoice_ids)
{
	$out = array();
	if (!$invoice_ids) {
		return $out;
	}
	$ids = array_map('intval', $invoice_ids);
	$id_sql = implode(',', $ids);
	$q = mysqli_query($conn, "SELECT l.billing_invoice_id, COALESCE(SUM(l.receive_now),0) AS paid
		FROM billing_receipt_lines l
		INNER JOIN billing_receipt_master m ON m.billing_receipt_id = l.billing_receipt_id
		WHERE l.billing_invoice_id IN ($id_sql)
		AND m.status='final' AND m.receipt_type='against_invoice'
		GROUP BY l.billing_invoice_id");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$out[(int) $row['billing_invoice_id']] = (float) $row['paid'];
		}
	}
	return $out;
}

function dashboard_invoice_cn_map($conn, array $invoice_ids)
{
	$out = array();
	if (!$invoice_ids || !function_exists('billing_invoice_note_total')) {
		return $out;
	}
	foreach ($invoice_ids as $id) {
		$id = (int) $id;
		$out[$id] = (float) billing_invoice_note_total($conn, $id, 'CN', 0);
	}
	return $out;
}

function dashboard_invoice_tds_map($conn, array $invoice_ids)
{
	$out = array();
	if (!$invoice_ids) {
		return $out;
	}
	foreach ($invoice_ids as $id) {
		$id = (int) $id;
		$out[$id] = (float) billing_invoice_tds_total($conn, $id, 0);
	}
	return $out;
}

function dashboard_invoice_balance($master, $paid, $cn, $tds)
{
	if (($master['status'] ?? '') !== 'final') {
		return 0.0;
	}
	$inv_amt = (float) ($master['grand_total'] ?? 0);
	return round(max(0, $inv_amt - $paid - $cn - $tds), 2);
}

function dashboard_invoice_display_status(array $master, $balance, DateTime $today)
{
	$st = (string) ($master['status'] ?? '');
	if ($st === 'cancelled') {
		return 'cancelled';
	}
	if ($st === 'draft') {
		return 'draft';
	}
	if ($st !== 'final') {
		return 'draft';
	}
	$bal = (float) $balance;
	$total = (float) ($master['grand_total'] ?? 0);
	$paid = max(0, $total - $bal);
	if ($bal <= 0.009) {
		return 'paid';
	}
	$due = dashboard_invoice_due_date($master['invoice_date'] ?? '');
	if ($due && $today > $due) {
		return 'overdue';
	}
	if ($paid > 0.009 && $bal > 0.009) {
		return 'partial';
	}
	if (trim((string) ($master['pdf_path'] ?? '')) !== '') {
		return 'sent';
	}
	return 'generated';
}

function dashboard_invoice_status_label($key)
{
	$labels = array(
		'draft' => 'Draft',
		'generated' => 'Generated',
		'sent' => 'Sent',
		'partial' => 'Partially Paid',
		'paid' => 'Paid',
		'overdue' => 'Overdue',
		'cancelled' => 'Cancelled',
	);
	return $labels[$key] ?? ucfirst($key);
}

function dashboard_invoice_status_badge_class($key)
{
	$map = array(
		'draft' => 'inv-st-draft',
		'generated' => 'inv-st-generated',
		'sent' => 'inv-st-sent',
		'partial' => 'inv-st-partial',
		'paid' => 'inv-st-paid',
		'overdue' => 'inv-st-overdue',
		'cancelled' => 'inv-st-cancelled',
	);
	return $map[$key] ?? 'inv-st-generated';
}

function dashboard_invoice_aging_bucket(DateTime $today, $due_dt, $balance)
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

function dashboard_invoice_load($conn, $params = array())
{
	ensure_billing_tables($conn);

	$from_raw = $params['date_from'] ?? '';
	$to_raw = $params['date_to'] ?? '';
	$branch_id = (int) ($params['branch_id'] ?? 0);
	list($from, $to, $range_all) = dashboard_booking_resolve_range($from_raw, $to_raw);

	$today = new DateTime('today');
	$kpi = array(
		'total' => 0,
		'draft' => 0,
		'generated' => 0,
		'sent' => 0,
		'partial' => 0,
		'paid' => 0,
		'overdue' => 0,
		'cancelled' => 0,
	);
	$fin = array(
		'total_invoiced' => 0.0,
		'net_value' => 0.0,
		'gst' => 0.0,
		'discounts' => 0.0,
		'current_month' => 0.0,
		'prev_month' => 0.0,
	);
	$outstanding = array(
		'balance' => 0.0,
		'open_count' => 0,
		'overdue_count' => 0,
		'avg_days_overdue' => 0,
	);
	$aging = array(
		'not_due' => 0.0,
		'1_30' => 0.0,
		'31_60' => 0.0,
		'61_90' => 0.0,
		'91_120' => 0.0,
		'120_plus' => 0.0,
	);
	$status_slices = array();
	$recent = array();
	$rows_in_range = array();

	if (!dashboard_ops_table_exists($conn, 'billing_invoice_master')) {
		return array(
			'ok' => false,
			'message' => 'Billing tables not found.',
			'range' => array('all' => true, 'from' => '', 'to' => ''),
			'kpi' => $kpi,
			'financial' => $fin,
			'outstanding' => $outstanding,
			'aging' => $aging,
			'status_chart' => array(),
			'recent' => array(),
			'branches' => dashboard_booking_branch_options($conn),
			'branch_id' => $branch_id,
		);
	}

	$q = mysqli_query($conn, "SELECT m.*, c.client_company_name
		FROM billing_invoice_master m
		LEFT JOIN client c ON c.client_id = m.customer_id
		ORDER BY m.billing_invoice_id DESC");
	$all = array();
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$all[] = $row;
		}
	}

	$final_ids = array();
	foreach ($all as $row) {
		if (($row['status'] ?? '') === 'final') {
			$final_ids[] = (int) $row['billing_invoice_id'];
		}
	}
	$paid_map = dashboard_invoice_paid_map($conn, $final_ids);
	$cn_map = dashboard_invoice_cn_map($conn, $final_ids);
	$tds_map = dashboard_invoice_tds_map($conn, $final_ids);

	$cur_month_start = (clone $today)->modify('first day of this month')->setTime(0, 0, 0);
	$prev_month_start = (clone $cur_month_start)->modify('-1 month');
	$prev_month_end = (clone $cur_month_start)->modify('-1 day')->setTime(23, 59, 59);

	$overdue_days_sum = 0;
	$overdue_days_n = 0;
	$open_balance_before_cur_month = 0.0;

	foreach ($all as $row) {
		$id = (int) $row['billing_invoice_id'];
		$paid = (float) ($paid_map[$id] ?? 0);
		$cn = (float) ($cn_map[$id] ?? 0);
		$tds = (float) ($tds_map[$id] ?? 0);
		$balance = dashboard_invoice_balance($row, $paid, $cn, $tds);
		$dstatus = dashboard_invoice_display_status($row, $balance, $today);
		$in_range = dashboard_invoice_in_date_range($row['invoice_date'] ?? '', $from, $to, $range_all);

		if ($in_range) {
			$kpi['total']++;
			if (isset($kpi[$dstatus])) {
				$kpi[$dstatus]++;
			}
			if (!isset($status_slices[$dstatus])) {
				$status_slices[$dstatus] = 0;
			}
			$status_slices[$dstatus]++;

			if (($row['status'] ?? '') !== 'cancelled') {
				$gt = (float) ($row['grand_total'] ?? 0);
				$fin['total_invoiced'] += $gt;
				$fin['net_value'] += (float) ($row['taxable_value'] ?? 0);
				$fin['gst'] += (float) ($row['gst_amount'] ?? 0);
			}

			$inv_dt = dashboard_booking_parse_date($row['invoice_date'] ?? '');
			if ($inv_dt && ($row['status'] ?? '') === 'final') {
				$inv_dt->setTime(12, 0, 0);
				$gt = (float) ($row['grand_total'] ?? 0);
				if ($inv_dt >= $cur_month_start) {
					$fin['current_month'] += $gt;
				} elseif ($inv_dt >= $prev_month_start && $inv_dt <= $prev_month_end) {
					$fin['prev_month'] += $gt;
				}
			}

			$rows_in_range[] = array(
				'master' => $row,
				'paid' => $paid,
				'balance' => $balance,
				'dstatus' => $dstatus,
				'customer' => trim((string) ($row['client_company_name'] ?? '')) ?: '—',
			);
		}

		if (($row['status'] ?? '') === 'final' && $balance > 0.009) {
			$outstanding['balance'] += $balance;
			$outstanding['open_count']++;
			$inv_dt_open = dashboard_booking_parse_date($row['invoice_date'] ?? '');
			if ($inv_dt_open && $inv_dt_open < $cur_month_start) {
				$open_balance_before_cur_month += $balance;
			}
			if ($dstatus === 'overdue') {
				$outstanding['overdue_count']++;
				$due = dashboard_invoice_due_date($row['invoice_date'] ?? '');
				if ($due) {
					$overdue_days_sum += (int) $today->diff($due)->days;
					$overdue_days_n++;
				}
			}
			$due = dashboard_invoice_due_date($row['invoice_date'] ?? '');
			$bucket = dashboard_invoice_aging_bucket($today, $due, $balance);
			if ($bucket && isset($aging[$bucket])) {
				$aging[$bucket] += $balance;
			}
		}
	}

	if ($overdue_days_n > 0) {
		$outstanding['avg_days_overdue'] = (int) round($overdue_days_sum / $overdue_days_n);
	}

	$out_mom_pct = 0;
	$out_mom_up = true;
	if ($open_balance_before_cur_month > 0.009) {
		$raw_mom = (int) round(
			($outstanding['balance'] - $open_balance_before_cur_month) / $open_balance_before_cur_month * 100
		);
		$out_mom_up = $raw_mom >= 0;
		$out_mom_pct = abs($raw_mom);
	}

	$aging_chart = array(
		'not_due' => (float) $aging['not_due'],
		'1_30' => (float) $aging['1_30'],
		'31_60' => (float) $aging['31_60'],
		'61_90' => (float) $aging['61_90'],
		'90_plus' => (float) $aging['91_120'] + (float) $aging['120_plus'],
	);

	usort($rows_in_range, function ($a, $b) {
		$da = dashboard_booking_parse_date($a['master']['invoice_date'] ?? '');
		$db = dashboard_booking_parse_date($b['master']['invoice_date'] ?? '');
		$ta = $da ? $da->getTimestamp() : 0;
		$tb = $db ? $db->getTimestamp() : 0;
		if ($ta === $tb) {
			return (int) $b['master']['billing_invoice_id'] - (int) $a['master']['billing_invoice_id'];
		}
		return $tb - $ta;
	});

	foreach (array_slice($rows_in_range, 0, (int) DASHBOARD_INVOICE_RECENT_LIMIT) as $item) {
		$row = $item['master'];
		$id = (int) $row['billing_invoice_id'];
		$gcn = '—';
		$gq = mysqli_query($conn, "SELECT grn_no FROM billing_invoice_details WHERE billing_invoice_id='$id' ORDER BY detail_id ASC LIMIT 1");
		if ($gq && ($gr = mysqli_fetch_assoc($gq))) {
			$gcn = $gr['grn_no'] ?? '—';
		}
		$due = dashboard_invoice_due_date($row['invoice_date'] ?? '');
		$recent[] = array(
			'id' => $id,
			'invoice_no' => $row['invoice_no'] ?? '—',
			'invoice_date' => $row['invoice_date'] ?? '',
			'customer' => $item['customer'],
			'gcn_no' => $gcn,
			'freight' => dashboard_booking_format_inr((float) ($row['total_freight'] ?? 0)),
			'other' => dashboard_booking_format_inr((float) ($row['total_other'] ?? 0)),
			'gst' => dashboard_booking_format_inr((float) ($row['gst_amount'] ?? 0)),
			'total' => dashboard_booking_format_inr((float) ($row['grand_total'] ?? 0)),
			'paid' => dashboard_booking_format_inr($item['paid']),
			'balance' => dashboard_booking_format_inr($item['balance']),
			'due_date' => $due ? $due->format('d-m-Y') : '—',
			'status_key' => $item['dstatus'],
			'status_label' => dashboard_invoice_status_label($item['dstatus']),
			'status_class' => dashboard_invoice_status_badge_class($item['dstatus']),
			'view_url' => 'create_invoice.php?id=' . $id,
		);
	}

	$chart_total = array_sum($status_slices) ?: 1;
	$status_chart = array();
	$colors = array(
		'generated' => '#6366f1',
		'sent' => '#0891b2',
		'partial' => '#f59e0b',
		'paid' => '#22c55e',
		'overdue' => '#e11d48',
		'draft' => '#94a3b8',
		'cancelled' => '#64748b',
	);
	foreach ($status_slices as $key => $cnt) {
		if ($cnt <= 0) {
			continue;
		}
		$status_chart[] = array(
			'name' => dashboard_invoice_status_label($key),
			'key' => $key,
			'count' => (int) $cnt,
			'pct' => round($cnt * 100 / $chart_total, 1),
			'color' => $colors[$key] ?? '#94a3b8',
		);
	}
	usort($status_chart, function ($a, $b) {
		return $b['count'] - $a['count'];
	});

	$fmt_fin = function ($n) {
		return dashboard_booking_format_inr($n);
	};

	return array(
		'ok' => true,
		'range' => array(
			'all' => $range_all,
			'from' => $range_all ? '' : $from->format('d-m-Y'),
			'to' => $range_all ? '' : $to->format('d-m-Y'),
		),
		'kpi' => $kpi,
		'financial' => array(
			'total_invoiced' => $fin['total_invoiced'],
			'total_invoiced_fmt' => $fmt_fin($fin['total_invoiced']),
			'net_value' => $fin['net_value'],
			'net_value_fmt' => $fmt_fin($fin['net_value']),
			'gst' => $fin['gst'],
			'gst_fmt' => $fmt_fin($fin['gst']),
			'discounts' => $fin['discounts'],
			'discounts_fmt' => $fmt_fin($fin['discounts']),
			'current_month' => $fin['current_month'],
			'current_month_fmt' => $fmt_fin($fin['current_month']),
			'prev_month' => $fin['prev_month'],
			'prev_month_fmt' => $fmt_fin($fin['prev_month']),
		),
		'outstanding' => array(
			'balance' => $outstanding['balance'],
			'balance_fmt' => $fmt_fin($outstanding['balance']),
			'open_count' => $outstanding['open_count'],
			'overdue_count' => $outstanding['overdue_count'],
			'avg_days_overdue' => $outstanding['avg_days_overdue'],
			'mom_pct' => $out_mom_pct,
			'mom_up' => $out_mom_up,
		),
		'aging' => $aging,
		'aging_labels' => array('Not due', '1–30', '31–60', '61–90', '91–120', '120+'),
		'aging_keys' => array('not_due', '1_30', '31_60', '61_90', '91_120', '120_plus'),
		'aging_chart' => $aging_chart,
		'aging_chart_labels' => array('Not due', '1 – 30 Days', '31 – 60 Days', '61 – 90 Days', '90+ Days'),
		'aging_chart_keys' => array('not_due', '1_30', '31_60', '61_90', '90_plus'),
		'status_chart' => $status_chart,
		'recent' => $recent,
		'branches' => dashboard_booking_branch_options($conn),
		'branch_id' => $branch_id,
	);
}
