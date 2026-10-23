<?php
/**
 * Live payload for Consignment Bookings Dashboard (dashboard_booking.php).
 */

require_once __DIR__ . '/dashboard_ops_data.php';
require_once __DIR__ . '/transaction_list_query.php';

function dashboard_booking_parse_date($raw)
{
	$raw = trim((string) $raw);
	if ($raw === '') {
		return null;
	}
	$dt = DateTime::createFromFormat('d-m-Y', $raw);
	if (!$dt) {
		$dt = DateTime::createFromFormat('Y-m-d', $raw);
	}
	if (!$dt) {
		$ts = strtotime(str_replace('/', '-', $raw));
		if ($ts) {
			$dt = (new DateTime())->setTimestamp($ts);
		}
	}
	return $dt ?: null;
}

function dashboard_booking_default_range()
{
	$to = new DateTime('today');
	$from = (clone $to)->modify('-29 days');
	return array($from, $to);
}

function dashboard_booking_resolve_range($from_raw, $to_raw)
{
	$from_raw = trim((string) $from_raw);
	$to_raw = trim((string) $to_raw);
	if ($from_raw === '' && $to_raw === '') {
		return array(null, null, true);
	}
	$from = dashboard_booking_parse_date($from_raw);
	$to = dashboard_booking_parse_date($to_raw);
	if (!$from || !$to) {
		list($from, $to) = dashboard_booking_default_range();
	}
	if ($from > $to) {
		$t = $from;
		$from = $to;
		$to = $t;
	}
	$from->setTime(0, 0, 0);
	$to->setTime(23, 59, 59);
	return array($from, $to, false);
}

function dashboard_booking_all_tables($conn)
{
	$tables = array();
	foreach (transaction_list_booking_tables($conn, 0) as $tbl) {
		$tables[$tbl['name']] = $tbl;
	}
	if (!$tables) {
		foreach (transaction_list_booking_tables($conn, (int) date('Y')) as $tbl) {
			$tables[$tbl['name']] = $tbl;
		}
	}
	return array_values($tables);
}

function dashboard_booking_row_date($grn_date)
{
	$raw = trim((string) $grn_date);
	if ($raw === '') {
		return null;
	}
	return dashboard_booking_parse_date($raw);
}

function dashboard_booking_in_range($grn_date, DateTime $from, DateTime $to)
{
	$dt = dashboard_booking_row_date($grn_date);
	if (!$dt) {
		return false;
	}
	return $dt >= $from && $dt <= $to;
}

function dashboard_booking_is_cancelled($row)
{
	return isset($row['booking_status']) && (string) $row['booking_status'] === '1';
}

function dashboard_booking_money($n)
{
	return round((float) $n, 2);
}

function dashboard_booking_format_inr_indian($n, $decimals = 0)
{
	$n = dashboard_booking_money($n);
	$neg = $n < 0;
	$n = abs($n);
	$dec = (int) $decimals;
	$int = (string) (int) floor($n);
	if ($dec > 0) {
		$frac = number_format($n - floor($n), $dec, '.', '');
		$frac = substr($frac, 2);
	} else {
		$frac = '';
	}
	if (strlen($int) <= 3) {
		$formatted = $int;
	} else {
		$last3 = substr($int, -3);
		$rest = substr($int, 0, -3);
		$rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
		$formatted = $rest . ',' . $last3;
	}
	$out = $formatted;
	if ($dec > 0) {
		$out .= '.' . str_pad($frac, $dec, '0');
	}
	return ($neg ? '-' : '') . $out;
}

function dashboard_booking_format_inr($n)
{
	return dashboard_booking_format_inr_indian($n, 0);
}

function dashboard_booking_format_inr_short($n)
{
	$n = (float) $n;
	if (abs($n) >= 10000000) {
		return '₹ ' . rtrim(rtrim(number_format($n / 10000000, 1, '.', ''), '0'), '.') . 'Cr';
	}
	if (abs($n) >= 100000) {
		return '₹ ' . rtrim(rtrim(number_format($n / 100000, 1, '.', ''), '0'), '.') . 'L';
	}
	if (abs($n) >= 1000) {
		return '₹ ' . rtrim(rtrim(number_format($n / 1000, 1, '.', ''), '0'), '.') . 'K';
	}
	return '₹ ' . dashboard_booking_format_inr($n);
}

function dashboard_booking_status_meta($status)
{
	$st = (int) $status;
	if ($st >= 8) {
		return array('label' => 'Delivered', 'class' => 'st-delivered');
	}
	if ($st === 7) {
	 return array('label' => 'Out for Delivery', 'class' => 'st-ofd');
	}
	if ($st === 6) {
		return array('label' => 'At Destination', 'class' => 'st-dest');
	}
	if ($st >= 3 && $st <= 5) {
		return array('label' => 'In Transit', 'class' => 'st-transit');
	}
	if ($st === 2) {
		return array('label' => 'Picked Up', 'class' => 'st-pickup');
	}
	return array('label' => 'Booked', 'class' => 'st-booked');
}

function dashboard_booking_branch_options($conn)
{
	$opts = array(array('id' => 0, 'label' => 'All Branches'));
	if (!dashboard_ops_table_exists($conn, 'branch')) {
		return $opts;
	}
	$q = mysqli_query($conn, "SELECT branch_id, branch_name FROM branch WHERE status=0 ORDER BY branch_name ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$opts[] = array(
				'id' => (int) $row['branch_id'],
				'label' => $row['branch_name'],
			);
		}
	}
	return $opts;
}

function dashboard_booking_tables_for_range(DateTime $from, DateTime $to)
{
	global $conn;
	$y1 = (int) $from->format('Y');
	$y2 = (int) $to->format('Y');
	$tables = array();
	for ($y = $y1; $y <= $y2; $y++) {
		foreach (transaction_list_booking_tables($conn, $y) as $tbl) {
			$key = $tbl['name'];
			$tables[$key] = $tbl;
		}
	}
	if (!$tables) {
		foreach (transaction_list_booking_tables($conn, 0) as $tbl) {
			$tables[$tbl['name']] = $tbl;
		}
	}
	return array_values($tables);
}

function dashboard_booking_invoice_pkgs($conn, $qtr, $year, $transaction_id)
{
	$pkgs = 0;
	$charged = 0.0;
	$inv = 'transaction_invoice_' . (int) $qtr . '_' . (int) $year;
	if (!dashboard_ops_table_exists($conn, $inv)) {
		return array($pkgs, $charged);
	}
	$tid = (int) $transaction_id;
	$q = mysqli_query($conn, "SELECT SUM(COALESCE(no_of_pkge,0)) AS pkgs, SUM(COALESCE(charged_weight,0)) AS cw FROM `$inv` WHERE transaction_id='$tid'");
	if ($q && ($r = mysqli_fetch_assoc($q))) {
		$pkgs = (int) ($r['pkgs'] ?? 0);
		$charged = dashboard_booking_money($r['cw'] ?? 0);
	}
	return array($pkgs, $charged);
}

function dashboard_booking_row_amounts($row, $cols)
{
	$total = dashboard_booking_money($row['total'] ?? 0);
	$freight = isset($cols['freight']) ? dashboard_booking_money($row['freight'] ?? 0) : $total;
	if ($freight <= 0 && isset($cols['freight_charge'])) {
		$freight = dashboard_booking_money($row['freight_charge'] ?? 0);
	}
	$additional = 0;
	foreach (array('other_charges', 'other_charge', 'additional_charges', 'tempo_charges', 'documentation_charges') as $c) {
		if (isset($cols[$c])) {
			$additional += dashboard_booking_money($row[$c] ?? 0);
		}
	}
	$discount = 0;
	foreach (array('discount', 'discount_amount', 'total_discount') as $c) {
		if (isset($cols[$c])) {
			$discount += dashboard_booking_money($row[$c] ?? 0);
		}
	}
	$gst = 0;
	foreach (array('gst_amount', 'total_gst', 'igst_amount', 'cgst_amount') as $c) {
		if (isset($cols[$c])) {
			$gst += dashboard_booking_money($row[$c] ?? 0);
		}
	}
	if ($gst <= 0 && isset($cols['cgst_amount'], $cols['sgst_amount'])) {
		$gst = dashboard_booking_money($row['cgst_amount'] ?? 0) + dashboard_booking_money($row['sgst_amount'] ?? 0);
	}
	if ($total <= 0) {
		$total = $freight + $additional - $discount + $gst;
	}
	return array(
		'total' => $total,
		'freight' => $freight,
		'additional' => $additional,
		'discount' => $discount,
		'gst' => $gst,
	);
}

function dashboard_booking_load($conn, $params = array())
{
	$from_raw = $params['date_from'] ?? '';
	$to_raw = $params['date_to'] ?? '';
	$branch_id = (int) ($params['branch_id'] ?? 0);
	list($from, $to, $range_all) = dashboard_booking_resolve_range($from_raw, $to_raw);

	$today = new DateTime('today');
	$today->setTime(0, 0, 0);
	if ($range_all) {
		$trend_from = (clone $today)->modify('-29 days');
		$trend_to = clone $today;
		$trend_to->setTime(23, 59, 59);
		$tables = dashboard_booking_all_tables($conn);
	} else {
		$trend_from = clone $from;
		$trend_to = clone $to;
		$tables = dashboard_booking_tables_for_range($from, $to);
	}
	$role = isset($_SESSION['role']) ? (string) $_SESSION['role'] : '';
	$company_id = isset($_SESSION['company_id']) ? (string) $_SESSION['company_id'] : '';
	$client_sql = '';
	if ($role !== 'AD' && $company_id !== '') {
		$esc = mysqli_real_escape_string($conn, $company_id);
		$client_sql = " AND (t.consigner='$esc' OR t.consignee='$esc')";
	}

	$rows = array();

	foreach ($tables as $tbl) {
		$name = preg_replace('/[^a-zA-Z0-9_]/', '', $tbl['name']);
		if ($name === '') {
			continue;
		}
		$cols = dashboard_ops_columns($conn, $name);
		if (!isset($cols['grn_date'])) {
			continue;
		}
		$branch_sql = '';
		if ($branch_id > 0) {
			if (isset($cols['branch_id'])) {
				$branch_sql = " AND t.branch_id='" . (int) $branch_id . "'";
			} elseif (isset($cols['company_branch_id'])) {
				$branch_sql = " AND t.company_branch_id='" . (int) $branch_id . "'";
			}
		}

		$sql = "SELECT t.* FROM `$name` t WHERE TRIM(COALESCE(t.grn_no,''))!='' $client_sql $branch_sql";
		$q = mysqli_query($conn, $sql);
		if (!$q) {
			continue;
		}
		while ($row = mysqli_fetch_assoc($q)) {
			if (!$range_all && !dashboard_booking_in_range($row['grn_date'] ?? '', $from, $to)) {
				continue;
			}
			$row['_table'] = $name;
			$row['_qtr'] = (int) $tbl['qtr'];
			$row['_year'] = (int) $tbl['year'];
			$row['_cols'] = $cols;
			$rows[] = $row;
		}
	}

	$active = array();
	$cancelled = array();
	foreach ($rows as $row) {
		if (dashboard_booking_is_cancelled($row)) {
			$cancelled[] = $row;
		} else {
			$active[] = $row;
		}
	}

	$kpi = array(
		'total' => 0,
		'today' => 0,
		'pending_pickup' => 0,
		'in_transit' => 0,
		'at_destination' => 0,
		'out_for_delivery' => 0,
		'delivered' => 0,
		'cancelled' => count($cancelled),
	);
	$sum_total = 0;
	$sum_freight = 0;
	$sum_additional = 0;
	$sum_discount = 0;
	$sum_gst = 0;
	$sum_billing = 0;

	$pipeline = array(
		'booked' => array('count' => 0, 'value' => 0),
		'picked_up' => array('count' => 0, 'value' => 0),
		'origin_hub' => array('count' => 0, 'value' => 0),
		'in_transit' => array('count' => 0, 'value' => 0),
		'dest_hub' => array('count' => 0, 'value' => 0),
		'ofd' => array('count' => 0, 'value' => 0),
		'delivered' => array('count' => 0, 'value' => 0),
	);

	$trend = array();
	$cursor = clone $trend_from;
	$cursor->setTime(0, 0, 0);
	$trend_end = clone $trend_to;
	$trend_end->setTime(0, 0, 0);
	while ($cursor <= $trend_end) {
		$key = $cursor->format('Y-m-d');
		$trend[$key] = array('date' => $key, 'label' => $cursor->format('j M'), 'count' => 0, 'value' => 0);
		$cursor->modify('+1 day');
	}

	$modes = array();
	$routes = array();

	foreach ($active as $row) {
		$st = (int) ($row['status'] ?? 0);
		$amt = dashboard_booking_row_amounts($row, $row['_cols']);
		$val = $amt['total'];
		$sum_total += $val;
		$sum_freight += $amt['freight'];
		$sum_additional += $amt['additional'];
		$sum_discount += $amt['discount'];
		$sum_gst += $amt['gst'];
		if (!empty($row['invoice_no']) && trim((string) $row['invoice_no']) !== '') {
			$sum_billing += $val;
		}

		$kpi['total']++;
		$dt = dashboard_booking_row_date($row['grn_date'] ?? '');
		if ($dt && $dt->format('Y-m-d') === $today->format('Y-m-d')) {
			$kpi['today']++;
		}
		if ($st <= 1) {
			$kpi['pending_pickup']++;
		}
		if ($st >= 3 && $st <= 5) {
			$kpi['in_transit']++;
		}
		if ($st === 6) {
			$kpi['at_destination']++;
		}
		if ($st === 7) {
			$kpi['out_for_delivery']++;
		}
		if ($st >= 8) {
			$kpi['delivered']++;
		}

		$pipeline['booked']['count']++;
		$pipeline['booked']['value'] += $val;
		if ($st >= 2) {
			$pipeline['picked_up']['count']++;
			$pipeline['picked_up']['value'] += $val;
			$pipeline['origin_hub']['count']++;
			$pipeline['origin_hub']['value'] += $val;
		}
		if ($st >= 3) {
			$pipeline['in_transit']['count']++;
			$pipeline['in_transit']['value'] += $val;
		}
		if ($st >= 6) {
			$pipeline['dest_hub']['count']++;
			$pipeline['dest_hub']['value'] += $val;
		}
		if ($st >= 7) {
			$pipeline['ofd']['count']++;
			$pipeline['ofd']['value'] += $val;
		}
		if ($st >= 8) {
			$pipeline['delivered']['count']++;
			$pipeline['delivered']['value'] += $val;
		}

		if ($dt) {
			$dkey = $dt->format('Y-m-d');
			if (isset($trend[$dkey])) {
				$trend[$dkey]['count']++;
				$trend[$dkey]['value'] += $val;
			}
		}

		$mode_id = (int) ($row['mode_of_transportation'] ?? 0);
		$mode_label = 'Other';
		if ($mode_id > 0 && function_exists('get_mode')) {
			$m = trim((string) get_mode($conn, $mode_id));
			if ($m !== '') {
				$mode_label = $m;
			}
		}
		if (!isset($modes[$mode_label])) {
			$modes[$mode_label] = 0;
		}
		$modes[$mode_label]++;

		$origin = get_city_name($conn, $row['origin'] ?? '');
		$dest = get_city_name($conn, $row['destination'] ?? '');
		$route = ($origin !== '' ? $origin : '—') . ' → ' . ($dest !== '' ? $dest : '—');
		if (!isset($routes[$route])) {
			$routes[$route] = array('count' => 0, 'value' => 0);
		}
		$routes[$route]['count']++;
		$routes[$route]['value'] += $val;
	}

	if (dashboard_ops_table_exists($conn, 'billing_invoice_master')) {
		if ($range_all) {
			$bq = mysqli_query($conn, "SELECT SUM(COALESCE(grand_total,0)) AS gt FROM billing_invoice_master WHERE status='final'");
		} else {
			$from_esc = mysqli_real_escape_string($conn, $from->format('Y-m-d'));
			$to_esc = mysqli_real_escape_string($conn, $to->format('Y-m-d'));
			$dtExpr = dashboard_ops_sql_date('invoice_date');
			$bq = mysqli_query($conn, "SELECT SUM(COALESCE(grand_total,0)) AS gt FROM billing_invoice_master
				WHERE status='final' AND $dtExpr IS NOT NULL
				AND $dtExpr >= STR_TO_DATE('$from_esc','%Y-%m-%d') AND $dtExpr <= STR_TO_DATE('$to_esc','%Y-%m-%d')");
		}
		if ($bq && ($br = mysqli_fetch_assoc($bq))) {
			$bill = dashboard_booking_money($br['gt'] ?? 0);
			if ($bill > 0) {
				$sum_billing = $bill;
			}
		}
	}

	usort($active, function ($a, $b) {
		$ta = dashboard_booking_row_date($a['grn_date'] ?? '');
		$tb = dashboard_booking_row_date($b['grn_date'] ?? '');
		$ia = $ta ? $ta->getTimestamp() : 0;
		$ib = $tb ? $tb->getTimestamp() : 0;
		if ($ia === $ib) {
			return strcmp((string) ($b['grn_no'] ?? ''), (string) ($a['grn_no'] ?? ''));
		}
		return $ib - $ia;
	});

	$recent = array();
	foreach (array_slice($active, 0, 5) as $row) {
		list($pkgs, $charged) = dashboard_booking_invoice_pkgs($conn, $row['_qtr'], $row['_year'], $row['transaction_id']);
		$amt = dashboard_booking_row_amounts($row, $row['_cols']);
		$meta = dashboard_booking_status_meta($row['status'] ?? 0);
		$mode_id = (int) ($row['mode_of_transportation'] ?? 0);
		$mode_label = '—';
		if ($mode_id > 0 && function_exists('get_mode')) {
			$mode_label = trim((string) get_mode($conn, $mode_id)) ?: '—';
		}
		$recent[] = array(
			'gcn_no' => $row['grn_no'] ?? '',
			'grn_date' => $row['grn_date'] ?? '',
			'consignor' => get_client_name($conn, $row['consigner'] ?? ''),
			'consignee' => get_client_name($conn, $row['consignee'] ?? ''),
			'origin' => get_city_name($conn, $row['origin'] ?? ''),
			'destination' => get_city_name($conn, $row['destination'] ?? ''),
			'mode' => $mode_label,
			'pkgs' => $pkgs,
			'charged_weight' => $charged,
			'value' => dashboard_booking_format_inr($amt['total']),
			'value_raw' => $amt['total'],
			'expected_delivery' => trim((string) ($row['delivery_date'] ?? $row['edd'] ?? $row['expected_delivery'] ?? '—')),
			'status_label' => $meta['label'],
			'status_class' => $meta['class'],
			'edit_url' => 'transactions.php?month=' . urlencode(substr($row['grn_date'] ?? '', 3, 7)) . '&year=' . (int) $row['_year'] . '&id=' . (int) $row['transaction_id'],
		);
	}

	arsort($modes);
	$mode_slices = array();
	$mode_total = array_sum($modes) ?: 1;
	foreach ($modes as $label => $cnt) {
		$mode_slices[] = array(
			'name' => $label,
			'count' => (int) $cnt,
			'pct' => round($cnt * 100 / $mode_total, 1),
		);
	}

	uasort($routes, function ($a, $b) {
		return $b['count'] - $a['count'];
	});
	$top_routes = array();
	$i = 0;
	foreach ($routes as $route => $data) {
		$top_routes[] = array(
			'route' => $route,
			'count' => (int) $data['count'],
			'value' => dashboard_booking_format_inr($data['value']),
			'value_raw' => $data['value'],
		);
		if (++$i >= 5) {
			break;
		}
	}

	$avg_rev = $kpi['total'] > 0 ? $sum_total / $kpi['total'] : 0;

	$user_name = 'Admin';
	if (!empty($_SESSION['user_id']) && function_exists('get_user')) {
		$u = trim((string) get_user($conn, $_SESSION['user_id']));
		if ($u !== '') {
			$user_name = $u;
		}
	}
	$initials = '';
	foreach (preg_split('/\s+/', trim($user_name)) as $part) {
		if ($part !== '') {
			$initials .= strtoupper($part[0]);
		}
	}
	if ($initials === '') {
		$initials = 'AD';
	}
	$initials = substr($initials, 0, 2);

	return array(
		'ok' => true,
		'range' => array(
			'all' => $range_all,
			'from' => $range_all ? '' : $from->format('d-m-Y'),
			'to' => $range_all ? '' : $to->format('d-m-Y'),
			'from_display' => $range_all ? 'All dates' : $from->format('d M Y'),
			'to_display' => $range_all ? '' : $to->format('d M Y'),
		),
		'kpi' => $kpi,
		'revenue' => array(
			'booking_value' => $sum_total,
			'booking_value_fmt' => dashboard_booking_format_inr($sum_total),
			'billing_value' => $sum_billing,
			'billing_value_fmt' => dashboard_booking_format_inr($sum_billing),
			'freight' => $sum_freight,
			'freight_fmt' => dashboard_booking_format_inr($sum_freight),
			'additional' => $sum_additional,
			'additional_fmt' => dashboard_booking_format_inr($sum_additional),
			'discount' => $sum_discount,
			'discount_fmt' => dashboard_booking_format_inr($sum_discount),
			'gst' => $sum_gst,
			'gst_fmt' => dashboard_booking_format_inr($sum_gst),
			'avg_revenue' => $avg_rev,
			'avg_revenue_fmt' => dashboard_booking_format_inr($avg_rev),
		),
		'pipeline' => $pipeline,
		'trend' => array_values($trend),
		'modes' => $mode_slices,
		'top_routes' => $top_routes,
		'recent' => $recent,
		'branches' => dashboard_booking_branch_options($conn),
		'branch_id' => $branch_id,
		'user' => array('name' => $user_name, 'initials' => $initials),
	);
}
