<?php
/**
 * Live payload for Expenses & P&L Dashboard (dashboard_pl.php).
 */

require_once __DIR__ . '/dashboard_booking_data.php';
require_once __DIR__ . '/expense_gcn_helpers.php';
require_once __DIR__ . '/expense_general_helpers.php';
require_once __DIR__ . '/expense_type_helpers.php';

define('DASHBOARD_PL_RECENT_LIMIT', 5);
define('DASHBOARD_PL_TABLE_LIMIT', 6);
define('DASHBOARD_PL_MONTH_BARS', 6);

function dashboard_pl_date_in_range($date_raw, ?DateTime $from, ?DateTime $to, $range_all)
{
	if ($range_all || !$from || !$to) {
		return true;
	}
	$dt = dashboard_booking_parse_date($date_raw);
	if (!$dt) {
		return false;
	}
	return $dt >= $from && $dt <= $to;
}

function dashboard_pl_bucket_from_category($category_name, $group_name = '')
{
	$t = strtolower(trim((string) $category_name . ' ' . $group_name));
	if (preg_match('/\b(air|flight|cargo)\b/', $t)) {
		return 'Air Freight';
	}
	if (preg_match('/\b(surface|rail|ftl|ptl|freight|transport|vehicle|trailer|broker|tpc|parcel)\b/', $t)) {
		return 'Surface Transport';
	}
	if (preg_match('/\b(handling|hub|loading|unload|mamool|warehouse|storage)\b/', $t)) {
		return 'Handling & Hub';
	}
	if (preg_match('/\b(delivery|pick[\s-]?up|local)\b/', $t)) {
		return 'Delivery';
	}
	if (preg_match('/\b(salary|salaries|payroll|wage)\b/', $t)) {
		return 'Salaries';
	}
	if (preg_match('/\b(branch|office|utility|utilities|stationery|rent|admin)\b/', $t)) {
		return 'Branch Expenses';
	}
	return 'Others';
}

function dashboard_pl_breakdown_palette()
{
	return array(
		'Air Freight' => '#1d4ed8',
		'Surface Transport' => '#38bdf8',
		'Handling & Hub' => '#fb923c',
		'Delivery' => '#ec4899',
		'Branch Expenses' => '#8b5cf6',
		'Salaries' => '#64748b',
		'Others' => '#cbd5e1',
	);
}

function dashboard_pl_build_breakdown_chart(array $bucket_totals)
{
	$palette = dashboard_pl_breakdown_palette();
	$order = array_keys($palette);
	$sum = array_sum($bucket_totals);
	if ($sum <= 0.009) {
		$sum = 1;
	}
	$rows = array();
	foreach ($order as $name) {
		$amt = (float) ($bucket_totals[$name] ?? 0);
		$rows[] = array(
			'name' => $name,
			'count' => round($amt, 2),
			'pct' => $sum > 0.009 ? (int) round($amt * 100 / $sum) : 0,
			'color' => $palette[$name],
		);
	}
	return $rows;
}

function dashboard_pl_load_category_index($conn)
{
	expense_type_ensure_schema($conn);
	expense_group_ensure_schema($conn);
	$map = array();
	$q = mysqli_query($conn, 'SELECT c.category_id, c.category_name, g.group_code, g.group_name
		FROM expense_category c
		LEFT JOIN expense_group g ON g.group_id = c.expense_group_id');
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$id = (int) $row['category_id'];
			$map[$id] = array(
				'name' => $row['category_name'] ?? '—',
				'group_code' => $row['group_code'] ?? '',
				'group_name' => $row['group_name'] ?? '',
			);
		}
	}
	return $map;
}

function dashboard_pl_header_slices($conn, array $header)
{
	$mode = (($header['expense_mode'] ?? 'single') === 'group') ? 'group' : 'single';
	$expenses = round((float) ($header['expenses_without_gst'] ?? 0), 2);
	$slices = array();

	if ($mode === 'group') {
		$gid = (int) ($header['gcn_expense_id'] ?? 0);
		$items = expense_gcn_load_group_items($conn, $gid);
		$rev_total = 0.0;
		foreach ($items as $item) {
			$ctx = expense_gcn_fetch_context($conn, $item['trans_table'], (int) $item['transaction_id']);
			$rev = !empty($ctx['ok'])
				? round((float) ($ctx['revenue_without_gst_raw'] ?? 0), 2)
				: round((float) ($item['revenue_without_gst'] ?? 0), 2);
			$rev_total += $rev;
			$slices[] = array(
				'revenue' => $rev,
				'mode' => !empty($ctx['ok']) ? ($ctx['mode_of_transport'] ?? '—') : '—',
				'route' => !empty($ctx['ok']) ? ($ctx['route_label'] ?? '—') : expense_gcn_route_label($conn, $item['trans_table'], (int) $item['transaction_id']),
				'customer' => !empty($ctx['ok']) ? ($ctx['consignor'] ?? '—') : '—',
			);
		}
		if (!$slices) {
			return array(array(
				'revenue' => round((float) ($header['revenue_without_gst'] ?? 0), 2),
				'cost' => $expenses,
				'mode' => '—',
				'route' => '—',
				'customer' => '—',
			));
		}
		foreach ($slices as &$sl) {
			$share = $rev_total > 0.009 ? ($sl['revenue'] / $rev_total) : (1 / count($slices));
			$sl['cost'] = round($expenses * $share, 2);
		}
		unset($sl);
		return $slices;
	}

	$ctx = expense_gcn_fetch_context($conn, $header['trans_table'], (int) $header['transaction_id']);
	$rev = round((float) ($header['revenue_without_gst'] ?? 0), 2);
	return array(array(
		'revenue' => $rev,
		'cost' => $expenses,
		'mode' => !empty($ctx['ok']) ? ($ctx['mode_of_transport'] ?? '—') : '—',
		'route' => !empty($ctx['ok']) ? ($ctx['route_label'] ?? '—') : expense_gcn_route_label($conn, $header['trans_table'], (int) $header['transaction_id']),
		'customer' => !empty($ctx['ok']) ? ($ctx['consignor'] ?? '—') : '—',
	));
}

function dashboard_pl_agg_row_add(array &$bucket, $revenue, $cost)
{
	$key = $bucket['key'] ?? '';
	if ($key === '') {
		return;
	}
	if (!isset($bucket['rows'][$key])) {
		$bucket['rows'][$key] = array('name' => $bucket['label'], 'revenue' => 0.0, 'cost' => 0.0);
	}
	$bucket['rows'][$key]['revenue'] += round((float) $revenue, 2);
	$bucket['rows'][$key]['cost'] += round((float) $cost, 2);
}

function dashboard_pl_table_rows(array $rows, $limit, $fmt)
{
	usort($rows, function ($a, $b) {
		$pa = ($a['revenue'] ?? 0) - ($a['cost'] ?? 0);
		$pb = ($b['revenue'] ?? 0) - ($b['cost'] ?? 0);
		return $pb <=> $pa;
	});
	$out = array();
	foreach (array_slice($rows, 0, (int) $limit) as $r) {
		$rev = round((float) ($r['revenue'] ?? 0), 2);
		$cost = round((float) ($r['cost'] ?? 0), 2);
		$profit = round($rev - $cost, 2);
		$margin = $rev > 0.009 ? round($profit * 100 / $rev, 1) : 0.0;
		$out[] = array(
			'name' => $r['name'] ?? '—',
			'revenue_fmt' => $fmt($rev),
			'cost_fmt' => $fmt($cost),
			'profit_fmt' => $fmt($profit),
			'profit_raw' => $profit,
			'margin_fmt' => number_format($margin, 1) . '%',
			'margin_raw' => $margin,
		);
	}
	return $out;
}

function dashboard_pl_load($conn, $params = array())
{
	expense_gcn_ensure_schema($conn);
	expense_general_ensure_schema($conn);

	$from_raw = $params['date_from'] ?? '';
	$to_raw = $params['date_to'] ?? '';
	$branch_id = (int) ($params['branch_id'] ?? 0);
	list($from, $to, $range_all) = dashboard_booking_resolve_range($from_raw, $to_raw);

	$fmt = function ($n) {
		return dashboard_booking_format_inr((float) $n);
	};

	$cat_index = dashboard_pl_load_category_index($conn);
	$bucket_totals = array_fill_keys(array_keys(dashboard_pl_breakdown_palette()), 0.0);

	$total_revenue = 0.0;
	$direct_cost = 0.0;
	$operating = 0.0;
	$gcn_expense_count = 0;
	$general_expense_count = 0;

	$by_service = array();
	$by_route = array();
	$by_customer = array();
	$recent = array();

	$monthly = array();
	$chart_end = ($to instanceof DateTime) ? clone $to : new DateTime('today');
	$chart_end->modify('first day of this month');
	for ($i = DASHBOARD_PL_MONTH_BARS - 1; $i >= 0; $i--) {
		$m = (clone $chart_end)->modify('-' . $i . ' months');
		$key = $m->format('Y-m');
		$monthly[$key] = array(
			'label' => $m->format('M'),
			'revenue' => 0.0,
			'direct' => 0.0,
			'operating' => 0.0,
			'net' => 0.0,
		);
	}

	$month_key_for_date = function ($date_raw) use ($monthly) {
		$dt = dashboard_booking_parse_date($date_raw);
		if (!$dt) {
			return null;
		}
		$key = $dt->format('Y-m');
		return isset($monthly[$key]) ? $key : null;
	};

	$hq = mysqli_query($conn, 'SELECT * FROM gcn_expense_header WHERE status=0 ORDER BY gcn_expense_id ASC');
	if ($hq) {
		while ($header = mysqli_fetch_assoc($hq)) {
			expense_gcn_refresh_header_revenue_profit($conn, $header);
			if (!dashboard_pl_date_in_range($header['grn_date'] ?? '', $from, $to, $range_all)) {
				continue;
			}

			$rev = round((float) ($header['revenue_without_gst'] ?? 0), 2);
			$exp = round((float) ($header['expenses_without_gst'] ?? 0), 2);
			$total_revenue += $rev;
			$direct_cost += $exp;
			$gcn_expense_count++;

			$mk = $month_key_for_date($header['grn_date'] ?? '');
			if ($mk) {
				$monthly[$mk]['revenue'] += $rev;
				$monthly[$mk]['direct'] += $exp;
			}

			$gid = (int) ($header['gcn_expense_id'] ?? 0);
			$lines = expense_gcn_load_lines($conn, $gid);
			foreach ($lines as $line) {
				$line_date = $line['expense_date'] ?? ($header['grn_date'] ?? '');
				if (!dashboard_pl_date_in_range($line_date, $from, $to, $range_all)) {
					continue;
				}
				$amt = round((float) ($line['expense_amount'] ?? 0), 2);
				$cid = (int) ($line['category_id'] ?? 0);
				$cmeta = $cat_index[$cid] ?? array('name' => 'Others', 'group_name' => '');
				$bucket = dashboard_pl_bucket_from_category($cmeta['name'], $cmeta['group_name']);
				$bucket_totals[$bucket] += $amt;

				$recent[] = array(
					'sort' => dashboard_booking_parse_date($line_date) ? dashboard_booking_parse_date($line_date)->getTimestamp() : 0,
					'date' => $line_date,
					'category' => $cmeta['name'],
					'description' => trim(($header['grn_no'] ?? '') . ' · ' . expense_gcn_route_label($conn, $header['trans_table'], (int) $header['transaction_id']), ' ·'),
					'amount' => $amt,
					'type' => 'gcn',
				);
			}

			foreach (dashboard_pl_header_slices($conn, $header) as $sl) {
				$mode = trim((string) ($sl['mode'] ?? '')) ?: '—';
				$route = trim((string) ($sl['route'] ?? '')) ?: '—';
				$customer = trim((string) ($sl['customer'] ?? '')) ?: '—';

				if (!isset($by_service[$mode])) {
					$by_service[$mode] = array('name' => $mode, 'revenue' => 0.0, 'cost' => 0.0);
				}
				$by_service[$mode]['revenue'] += (float) $sl['revenue'];
				$by_service[$mode]['cost'] += (float) $sl['cost'];

				if (!isset($by_route[$route])) {
					$by_route[$route] = array('name' => $route, 'revenue' => 0.0, 'cost' => 0.0);
				}
				$by_route[$route]['revenue'] += (float) $sl['revenue'];
				$by_route[$route]['cost'] += (float) $sl['cost'];

				if (!isset($by_customer[$customer])) {
					$by_customer[$customer] = array('name' => $customer, 'revenue' => 0.0, 'cost' => 0.0);
				}
				$by_customer[$customer]['revenue'] += (float) $sl['revenue'];
				$by_customer[$customer]['cost'] += (float) $sl['cost'];
			}
		}
	}

	$gq = mysqli_query($conn, 'SELECT * FROM general_expense_header WHERE status=0 ORDER BY general_expense_id ASC');
	if ($gq) {
		while ($gh = mysqli_fetch_assoc($gq)) {
			if (!dashboard_pl_date_in_range($gh['expense_date'] ?? '', $from, $to, $range_all)) {
				continue;
			}
			$amt = round((float) ($gh['expenses_without_gst'] ?? 0), 2);
			if ($amt <= 0 && !empty($gh['expense_amount'])) {
				$amt = round((float) $gh['expense_amount'], 2);
			}
			$operating += $amt;
			$general_expense_count++;

			$mk = $month_key_for_date($gh['expense_date'] ?? '');
			if ($mk) {
				$monthly[$mk]['operating'] += $amt;
			}

			$cid = (int) ($gh['category_id'] ?? 0);
			$cmeta = $cat_index[$cid] ?? array('name' => 'General', 'group_name' => '');

			$desc = trim((string) ($gh['description'] ?? ''));
			if ($desc === '') {
				$desc = trim((string) ($gh['remarks'] ?? ''));
			}
			if ($desc === '') {
				$desc = $gh['expense_no'] ?? '—';
			}

			$recent[] = array(
				'sort' => dashboard_booking_parse_date($gh['expense_date'] ?? '') ? dashboard_booking_parse_date($gh['expense_date'])->getTimestamp() : 0,
				'date' => $gh['expense_date'] ?? '',
				'category' => $cmeta['name'],
				'description' => $desc,
				'amount' => $amt,
				'type' => 'general',
			);
		}
	}

	foreach ($monthly as &$mrow) {
		$mrow['net'] = round($mrow['revenue'] - $mrow['direct'] - $mrow['operating'], 2);
	}
	unset($mrow);

	$gross_profit = round($total_revenue - $direct_cost, 2);
	$net_profit = round($gross_profit - $operating, 2);
	$gross_margin = $total_revenue > 0.009 ? round($gross_profit * 100 / $total_revenue, 1) : 0.0;
	$net_margin = $total_revenue > 0.009 ? round($net_profit * 100 / $total_revenue, 1) : 0.0;

	usort($recent, function ($a, $b) {
		return ($b['sort'] ?? 0) <=> ($a['sort'] ?? 0);
	});
	$recent_rows = array();
	foreach (array_slice($recent, 0, (int) DASHBOARD_PL_RECENT_LIMIT) as $r) {
		$recent_rows[] = array(
			'date' => ew_format_display_date($r['date'] ?? '', '—'),
			'category' => $r['category'] ?? '—',
			'description' => $r['description'] ?? '—',
			'amount_fmt' => $fmt($r['amount'] ?? 0),
		);
	}

	$month_labels = array();
	$month_series = array(
		'revenue' => array(),
		'direct' => array(),
		'operating' => array(),
		'net' => array(),
	);
	foreach ($monthly as $mrow) {
		$month_labels[] = $mrow['label'];
		$month_series['revenue'][] = round($mrow['revenue'], 2);
		$month_series['direct'][] = round($mrow['direct'], 2);
		$month_series['operating'][] = round($mrow['operating'], 2);
		$month_series['net'][] = round($mrow['net'], 2);
	}

	$breakdown = dashboard_pl_build_breakdown_chart($bucket_totals);
	$breakdown_total = array_sum($bucket_totals);
	if ($breakdown_total <= 0.009) {
		$breakdown_total = $direct_cost;
	}

	$direct_pct = $total_revenue > 0.009 ? round($direct_cost * 100 / $total_revenue, 1) : 0.0;
	$op_pct = $total_revenue > 0.009 ? round($operating * 100 / $total_revenue, 1) : 0.0;

	$kpi_sub = array(
		'total_revenue' => array(
			'highlight' => $gcn_expense_count . ' GCN' . ($gcn_expense_count === 1 ? '' : 's'),
			'text' => 'with expense in period',
			'tone' => 'muted',
		),
		'direct_cost' => array(
			'highlight' => number_format($direct_pct, 1) . '%',
			'text' => 'of revenue',
			'tone' => 'warn',
		),
		'gross_profit' => array(
			'highlight' => number_format($gross_margin, 1) . '%',
			'text' => 'gross margin',
			'tone' => 'pos',
		),
		'gross_margin' => array(
			'highlight' => dashboard_booking_format_inr_short($gross_profit),
			'text' => 'gross profit',
			'tone' => 'pos',
		),
		'operating' => array(
			'highlight' => $general_expense_count > 0 ? ($general_expense_count . ' general') : number_format($op_pct, 1) . '%',
			'text' => $general_expense_count > 0 ? 'expense entries' : 'of revenue',
			'tone' => $general_expense_count > 0 ? 'muted' : 'muted',
		),
		'net_profit' => array(
			'highlight' => number_format($net_margin, 1) . '%',
			'text' => 'net margin',
			'tone' => $net_profit >= 0 ? 'pos' : 'neg',
		),
		'net_margin' => array(
			'highlight' => dashboard_booking_format_inr_short($net_profit),
			'text' => 'net profit',
			'tone' => $net_profit >= 0 ? 'pos' : 'neg',
		),
	);

	return array(
		'range' => array(
			'from' => $from_raw,
			'to' => $to_raw,
			'all' => $range_all,
		),
		'branch_id' => $branch_id,
		'branches' => dashboard_booking_branch_options($conn),
		'kpi' => array(
			'total_revenue_fmt' => $fmt($total_revenue),
			'direct_cost_fmt' => $fmt($direct_cost),
			'gross_profit_fmt' => $fmt($gross_profit),
			'gross_margin_fmt' => number_format($gross_margin, 1) . '%',
			'operating_fmt' => $fmt($operating),
			'net_profit_fmt' => $fmt($net_profit),
			'net_margin_fmt' => number_format($net_margin, 1) . '%',
		),
		'kpi_sub' => $kpi_sub,
		'kpi_spark' => $month_series,
		'month_labels' => $month_labels,
		'month_series' => $month_series,
		'expense_breakdown' => $breakdown,
		'expense_breakdown_short' => dashboard_booking_format_inr_short($breakdown_total),
		'by_service' => dashboard_pl_table_rows(array_values($by_service), DASHBOARD_PL_TABLE_LIMIT, $fmt),
		'by_route' => dashboard_pl_table_rows(array_values($by_route), DASHBOARD_PL_TABLE_LIMIT, $fmt),
		'by_customer' => dashboard_pl_table_rows(array_values($by_customer), DASHBOARD_PL_TABLE_LIMIT, $fmt),
		'recent_expenses' => $recent_rows,
	);
}
