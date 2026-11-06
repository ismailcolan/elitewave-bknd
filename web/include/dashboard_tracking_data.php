<?php
/**
 * Live Tracking dashboard payload (admin / USER).
 * Reuses Cargo Ops year aggregates for KPIs; loads latest GCNs with status codes.
 */

require_once __DIR__ . '/dashboard_ops_data.php';
require_once __DIR__ . '/city_coords.php';

function dashboard_tracking_status_label($status)
{
	$st = (int) $status;
	if ($st >= 8) {
		return 'Delivered';
	}
	if ($st >= 7) {
		return 'Out for delivery';
	}
	if ($st >= 6) {
		return 'At destination';
	}
	if ($st >= 5) {
		return 'In Transit-3';
	}
	if ($st >= 4) {
		return 'In Transit-2';
	}
	if ($st >= 3) {
		return 'In Transit-1';
	}
	if ($st >= 2) {
		return 'Picked up';
	}
	if ($st >= 1) {
		return 'Booked';
	}
	return 'Pending';
}

function dashboard_tracking_edit_url($transaction_id, $month_num, $year, $book_manual = 0)
{
	$transaction_id = (int) $transaction_id;
	$month_num = (int) $month_num;
	$year = (int) $year;
	if ($transaction_id <= 0 || $month_num <= 0 || $year <= 0) {
		return '';
	}
	$base = ((int) $book_manual === 2) ? 'transactions_manual.php' : 'transactions.php';
	return $base . '?key=' . md5((string) $transaction_id) . '&m=' . $month_num . '&y=' . $year;
}

function dashboard_tracking_pin_progress($status)
{
	$map = array(
		2 => 0.04,
		3 => 0.30,
		4 => 0.55,
		5 => 0.80,
		6 => 0.97,
		7 => 0.99,
		8 => 1.0,
	);
	$st = (int) $status;
	if (isset($map[$st])) {
		return $map[$st];
	}
	if ($st <= 1) {
		return 0.0;
	}
	return 0.30;
}

/** Map mode label/group → air | rail | road | sea (for icons). */
function dashboard_tracking_mode_key($mode_label, $mode_group = '')
{
	$t = trim((string) $mode_label);
	$g = trim((string) $mode_group);
	if (strcasecmp($t, 'Premium Air Cargo') === 0 || preg_match('/\b(air|flight|aero)\b/i', $t . ' ' . $g)) {
		return 'air';
	}
	if (strcasecmp($t, 'Premium Train Cargo') === 0 || preg_match('/\b(rail|train|railway)\b/i', $t . ' ' . $g)) {
		return 'rail';
	}
	if (strcasecmp($t, 'Ocean Cargo') === 0 || preg_match('/\b(sea|ocean|ship|vessel|marine)\b/i', $t . ' ' . $g)) {
		return 'sea';
	}
	return 'road';
}

function dashboard_tracking_mode_group($conn, $mode_id)
{
	$mode_id = (int) $mode_id;
	if ($mode_id <= 0) {
		return '';
	}
	static $cache = null;
	if ($cache === null) {
		$cache = array();
		if (dashboard_ops_table_exists($conn, 'mode_of_transportation')) {
			$has_group = false;
			$chk = @mysqli_query($conn, "SHOW COLUMNS FROM mode_of_transportation LIKE 'mode_group'");
			if ($chk && mysqli_num_rows($chk) > 0) {
				$has_group = true;
			}
			$sql = $has_group
				? 'SELECT mode_id, mode_group FROM mode_of_transportation'
				: 'SELECT mode_id, mode_type AS mode_group FROM mode_of_transportation';
			$q = mysqli_query($conn, $sql);
			if ($q) {
				while ($row = mysqli_fetch_assoc($q)) {
					$cache[(int) $row['mode_id']] = trim((string) ($row['mode_group'] ?? ''));
				}
			}
		}
	}
	return $cache[$mode_id] ?? '';
}

/** Map mode label/group → icon key used by the tracking UI (air|rail|road|sea). */
function dashboard_tracking_mode_icon($mode_label, $mode_group = '')
{
	$t = strtolower(trim((string) $mode_label . ' ' . (string) $mode_group));
	if ($t === '') {
		return 'road';
	}
	if (strpos($t, 'air') !== false || strpos($t, 'flight') !== false || strpos($t, 'aero') !== false) {
		return 'air';
	}
	if (strpos($t, 'train') !== false || strpos($t, 'rail') !== false) {
		return 'rail';
	}
	if (strpos($t, 'sea') !== false || strpos($t, 'ocean') !== false || strpos($t, 'ship') !== false || strpos($t, 'marine') !== false) {
		return 'sea';
	}
	return 'road';
}

function dashboard_tracking_format_inr($n)
{
	$n = (float) $n;
	$abs = abs($n);
	$sign = $n < 0 ? '-' : '';
	if ($abs >= 100000) {
		return $sign . '₹' . number_format($abs / 100000, 2) . ' L';
	}
	return $sign . '₹' . number_format(round($abs), 0, '.', ',');
}

function dashboard_tracking_sum_from_months(array $months, $fromIdx)
{
	$out = array(
		'bookings' => 0,
		'delivered' => 0,
		'inTransit' => 0,
		'invoices' => 0,
		'cancelled' => 0,
		'revenue' => 0.0,
		'collected' => 0.0,
		'expense' => 0.0,
	);
	foreach ($months as $mo) {
		if ((int) ($mo['idx'] ?? -1) < $fromIdx) {
			continue;
		}
		$out['bookings'] += (int) ($mo['consignments'] ?? 0);
		$out['delivered'] += (int) ($mo['delivered'] ?? 0);
		$out['inTransit'] += (int) ($mo['inTransit'] ?? 0);
		$out['invoices'] += (int) ($mo['invoiceCount'] ?? 0);
		$out['cancelled'] += (int) ($mo['cancelled'] ?? 0);
		$out['revenue'] += (float) ($mo['invoiceAmount'] ?? 0);
		$out['collected'] += (float) ($mo['paymentAmount'] ?? 0);
		$out['expense'] += (float) ($mo['expenseAmount'] ?? 0);
	}
	return $out;
}

function dashboard_tracking_sum_weeks(array $months, $weekCount)
{
	$days = array();
	foreach ($months as $mo) {
		if ((int) ($mo['idx'] ?? -1) < 4) {
			continue;
		}
		foreach (($mo['days'] ?? array()) as $d) {
			$days[] = $d;
		}
	}
	$days = array_slice($days, -max(1, $weekCount * 7));
	$out = array(
		'bookings' => 0,
		'delivered' => 0,
		'inTransit' => 0,
		'invoices' => 0,
		'cancelled' => 0,
		'revenue' => 0.0,
		'collected' => 0.0,
		'expense' => 0.0,
	);
	foreach ($days as $d) {
		$out['bookings'] += (int) ($d['consignments'] ?? 0);
		$out['delivered'] += (int) ($d['delivered'] ?? 0);
		$out['inTransit'] += (int) ($d['inTransit'] ?? 0);
		$out['invoices'] += (int) ($d['invoiceCount'] ?? 0);
		$out['cancelled'] += (int) ($d['cancelled'] ?? 0);
		$out['revenue'] += (float) ($d['invoiceAmount'] ?? 0);
		$out['collected'] += (float) ($d['paymentAmount'] ?? 0);
		$out['expense'] += (float) ($d['expenseAmount'] ?? 0);
	}
	return $out;
}

function dashboard_tracking_latest($conn, $limit = 30)
{
	$limit = max(1, min(40, (int) $limit));
	$rows = array();
	$seen = array();
	$tq = mysqli_query($conn, 'SELECT table_name FROM transaction_tbls');
	if (!$tq) {
		return array();
	}
	while ($tbl = mysqli_fetch_assoc($tq)) {
		$table = 'transaction_' . preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($tbl['table_name'] ?? ''));
		if ($table === 'transaction_' || !dashboard_ops_table_exists($conn, $table)) {
			continue;
		}
		$cols = dashboard_ops_columns($conn, $table);
		if (!isset($cols['grn_no']) || !isset($cols['grn_date'])) {
			continue;
		}
		$dtExpr = dashboard_ops_sql_date('grn_date');
		$suffix = preg_replace('/^transaction_/', '', $table);
		$txnParts = explode('_', $suffix, 2);
		$txn_m = isset($txnParts[0]) ? (int) $txnParts[0] : 0;
		$txn_y = isset($txnParts[1]) ? (int) $txnParts[1] : 0;
		$select = array(
			'grn_no',
			'grn_date',
			isset($cols['transaction_id']) ? 'transaction_id' : '0 AS transaction_id',
			isset($cols['book_manual']) ? 'book_manual' : '0 AS book_manual',
			isset($cols['status']) ? 'status' : '0 AS status',
			isset($cols['consigner']) ? 'consigner' : "'' AS consigner",
			isset($cols['origin']) ? 'origin' : "'' AS origin",
			isset($cols['destination']) ? 'destination' : "'' AS destination",
			isset($cols['mode_of_transportation']) ? 'mode_of_transportation' : '0 AS mode_of_transportation',
			isset($cols['freight']) ? 'freight' : (isset($cols['total']) ? 'total AS freight' : '0 AS freight'),
		);
		$where = array("TRIM(COALESCE(grn_no,''))<>''");
		if (isset($cols['booking_status']) && function_exists('ew_sql_not_cancelled_booking')) {
			$where[] = ew_sql_not_cancelled_booking('booking_status');
		} elseif (isset($cols['booking_status'])) {
			$where[] = "(booking_status IS NULL OR booking_status='' OR booking_status<>'1')";
		}
		$sql = 'SELECT ' . implode(', ', $select) . " FROM `$table` WHERE " . implode(' AND ', $where)
			. " ORDER BY $dtExpr DESC LIMIT 40";
		$q = mysqli_query($conn, $sql);
		if (!$q) {
			continue;
		}
		while ($r = mysqli_fetch_assoc($q)) {
			$gcn = trim((string) ($r['grn_no'] ?? ''));
			if ($gcn === '' || isset($seen[$gcn])) {
				continue;
			}
			$seen[$gcn] = true;
			$iso = '';
			$raw = trim((string) ($r['grn_date'] ?? ''));
			$try = DateTime::createFromFormat('d-m-Y', $raw);
			if (!$try) {
				$try = date_create($raw);
			}
			if ($try) {
				$iso = $try->format('Y-m-d');
			}
			$status = (int) ($r['status'] ?? 0);
			$origin = get_city_name($conn, $r['origin'] ?? '');
			$dest = get_city_name($conn, $r['destination'] ?? '');
			$oCoords = ew_city_coords_lookup($origin);
			$dCoords = ew_city_coords_lookup($dest);
			$km = null;
			if ($oCoords && $dCoords) {
				$km = ew_haversine_km($oCoords[0], $oCoords[1], $dCoords[0], $dCoords[1]);
			}
			$freight = (float) ($r['freight'] ?? 0);
			$mode_id = (int) ($r['mode_of_transportation'] ?? 0);
			$mode_label = '';
			if ($mode_id > 0 && function_exists('get_mode')) {
				$mode_label = trim((string) get_mode($conn, $mode_id));
			}
			$mode_group = dashboard_tracking_mode_group($conn, $mode_id);
			$mode_key = dashboard_tracking_mode_key($mode_label, $mode_group);
			$transaction_id = (int) ($r['transaction_id'] ?? 0);
			$book_manual = (int) ($r['book_manual'] ?? 0);
			$rows[] = array(
				'grn_no' => $gcn,
				'edit_url' => dashboard_tracking_edit_url($transaction_id, $txn_m, $txn_y, $book_manual),
				'track_url' => 'track_consignment.php?grn_no=' . rawurlencode($gcn),
				'client' => trim((string) get_client_name($conn, $r['consigner'] ?? '')) ?: '—',
				'origin' => $origin !== '' ? $origin : '—',
				'destination' => $dest !== '' ? $dest : '—',
				'status' => $status,
				'status_label' => dashboard_tracking_status_label($status),
				'mode_id' => $mode_id,
				'mode' => $mode_label !== '' ? $mode_label : 'Road Freight',
				'mode_key' => $mode_key,
				'freight' => $freight,
				'freight_fmt' => dashboard_tracking_format_inr($freight),
				'grn_date' => $raw,
				'iso' => $iso,
				'progress' => dashboard_tracking_pin_progress($status),
				'origin_coords' => $oCoords,
				'dest_coords' => $dCoords,
				'origin_state' => ew_city_state_lookup($origin),
				'dest_state' => ew_city_state_lookup($dest),
				'km' => $km,
				'mapped' => ($oCoords && $dCoords) ? 1 : 0,
				'_sort' => $iso !== '' ? $iso : '1970-01-01',
			);
		}
	}
	usort($rows, function ($a, $b) {
		return strcmp($b['_sort'], $a['_sort']);
	});
	$rows = array_slice($rows, 0, $limit);
	foreach ($rows as &$row) {
		unset($row['_sort']);
	}
	unset($row);
	return $rows;
}

function dashboard_tracking_response($conn, $year, $period = 'monthly')
{
	$year = (int) $year;
	$period = ($period === 'weekly') ? 'weekly' : 'monthly';
	$ops = dashboard_ops_year_response($conn, $year);
	$months = isset($ops['yearData']['months']) ? $ops['yearData']['months'] : array();
	$goLive = isset($ops['goLiveMonth']) ? (int) $ops['goLiveMonth'] : 4;

	if ($period === 'weekly') {
		$sum = dashboard_tracking_sum_weeks($months, 4);
		$rangeLabel = 'Last 4 weeks';
	} else {
		$sum = dashboard_tracking_sum_from_months($months, $goLive);
		$monthNames = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$fromName = isset($monthNames[$goLive]) ? $monthNames[$goLive] : 'May';
		$rangeLabel = $fromName . ' to today';
	}

	$exceptions = isset($ops['exceptions']) ? $ops['exceptions'] : array();
	$outstanding = max(0, (float) $sum['revenue'] - (float) $sum['collected']);
	$deliveredNotInvoiced = (int) ($exceptions['unbilled'] ?? 0);

	$latest = dashboard_tracking_latest($conn, 30);

	return array(
		'status' => 0,
		'asOf' => $ops['asOf'] ?? date('d M Y'),
		'year' => (int) ($ops['year'] ?? $year),
		'years' => $ops['years'] ?? array((int) date('Y')),
		'period' => $period,
		'rangeLabel' => $rangeLabel,
		'goLiveMonth' => $goLive,
		'kpis' => array(
			'bookings' => (int) $sum['bookings'],
			'delivered' => (int) $sum['delivered'],
			'inTransit' => (int) $sum['inTransit'],
			'invoices' => (int) $sum['invoices'],
			'cancelled' => (int) ($sum['cancelled'] ?? 0),
			'revenue' => (float) $sum['revenue'],
			'revenue_fmt' => dashboard_tracking_format_inr($sum['revenue']),
			'collected' => (float) $sum['collected'],
			'collected_fmt' => dashboard_tracking_format_inr($sum['collected']),
			'outstanding' => $outstanding,
			'outstanding_fmt' => dashboard_tracking_format_inr($outstanding),
			'expense' => (float) $sum['expense'],
			'expense_fmt' => dashboard_tracking_format_inr($sum['expense']),
			'deliveredNotInvoiced' => $deliveredNotInvoiced,
		),
		'latest' => $latest,
	);
}
