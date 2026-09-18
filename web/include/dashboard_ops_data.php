<?php
/**
 * Live Cargo Ops dashboard payload (same shape as the React preview).
 */

function dashboard_ops_table_exists($conn, $table)
{
	$table = preg_replace('/[^A-Za-z0-9_]/', '', (string) $table);
	if ($table === '') {
		return false;
	}
	$r = mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $table) . "'");
	return $r && mysqli_num_rows($r) > 0;
}

function dashboard_ops_columns($conn, $table)
{
	static $cache = array();
	$table = preg_replace('/[^A-Za-z0-9_]/', '', (string) $table);
	if ($table === '') {
		return array();
	}
	if (isset($cache[$table])) {
		return $cache[$table];
	}
	$cols = array();
	$r = mysqli_query($conn, 'SHOW COLUMNS FROM `' . $table . '`');
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$cols[$row['Field']] = true;
		}
	}
	$cache[$table] = $cols;
	return $cols;
}

function dashboard_ops_sql_date($col)
{
	if (!preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\.)?[A-Za-z_][A-Za-z0-9_]*$/', (string) $col)) {
		return 'NULL';
	}
	return "COALESCE(STR_TO_DATE($col,'%d-%m-%Y'), STR_TO_DATE($col,'%Y-%m-%d'), DATE($col))";
}

function dashboard_ops_empty_breakdown()
{
	return array();
}

function dashboard_ops_empty_day($year, $monthIndex, $day)
{
	$dt = new DateTime(sprintf('%04d-%02d-%02d', $year, $monthIndex + 1, $day));
	return array(
		'd' => (int) $day,
		'dow' => (int) $dt->format('w'),
		'iso' => $dt->format('Y-m-d'),
		'consignments' => 0,
		'invoiceCount' => 0,
		'invoiceAmount' => 0.0,
		'paymentCount' => 0,
		'paymentAmount' => 0.0,
		'expenseAmount' => 0.0,
		'expenseBreakdown' => dashboard_ops_empty_breakdown(),
		'delivered' => 0,
		'delayed' => 0,
		'inTransit' => 0,
		'pending' => 0,
	);
}

function dashboard_ops_blank_year($year, $monthCap, $dayCap)
{
	$months = array();
	$names = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	$numMonths = $monthCap + 1;
	for ($m = 0; $m < $numMonths; $m++) {
		$totalDays = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $m + 1)));
		$isLast = ($m === $numMonths - 1);
		$dLimit = ($isLast && $dayCap) ? (int) $dayCap : $totalDays;
		$days = array();
		for ($d = 1; $d <= $dLimit; $d++) {
			$days[] = dashboard_ops_empty_day($year, $m, $d);
		}
		$months[] = dashboard_ops_agg_days($days, $m, $names[$m]);
	}
	return array('year' => (int) $year, 'months' => $months);
}

function dashboard_ops_agg_days($days, $idx, $name)
{
	$sum = function ($key) use ($days) {
		$n = 0;
		foreach ($days as $x) {
			$n += $x[$key];
		}
		return $n;
	};
	$breakdown = array();
	foreach ($days as $x) {
		foreach ($x['expenseBreakdown'] as $cat => $amt) {
			if (!isset($breakdown[$cat])) {
				$breakdown[$cat] = 0;
			}
			$breakdown[$cat] += $amt;
		}
	}
	return array(
		'idx' => (int) $idx,
		'name' => $name,
		'days' => $days,
		'consignments' => $sum('consignments'),
		'invoiceCount' => $sum('invoiceCount'),
		'invoiceAmount' => $sum('invoiceAmount'),
		'paymentCount' => $sum('paymentCount'),
		'paymentAmount' => $sum('paymentAmount'),
		'expenseAmount' => $sum('expenseAmount'),
		'expenseBreakdown' => $breakdown,
		'delivered' => $sum('delivered'),
		'delayed' => $sum('delayed'),
		'inTransit' => $sum('inTransit'),
		'pending' => $sum('pending'),
	);
}

function dashboard_ops_touch_day(&$yearObj, $iso, $mutator)
{
	$parts = explode('-', $iso);
	if (count($parts) !== 3) {
		return;
	}
	$y = (int) $parts[0];
	$m = (int) $parts[1] - 1;
	$d = (int) $parts[2];
	if ($y !== (int) $yearObj['year'] || $m < 0 || $m >= count($yearObj['months'])) {
		return;
	}
	$days = &$yearObj['months'][$m]['days'];
	$found = false;
	foreach ($days as $i => $day) {
		if ((int) $day['d'] === $d) {
			$mutator($days[$i]);
			$found = true;
			break;
		}
	}
	if ($found) {
		$yearObj['months'][$m] = dashboard_ops_agg_days($days, $yearObj['months'][$m]['idx'], $yearObj['months'][$m]['name']);
	}
}

function dashboard_ops_add_expense(&$yearObj, $iso, $cat, $amount)
{
	$cat = $cat !== '' ? $cat : 'Miscellaneous';
	dashboard_ops_touch_day($yearObj, $iso, function (&$day) use ($cat, $amount) {
		$day['expenseAmount'] += $amount;
		if (!isset($day['expenseBreakdown'][$cat])) {
			$day['expenseBreakdown'][$cat] = 0;
		}
		$day['expenseBreakdown'][$cat] += $amount;
	});
}

function dashboard_ops_year_bounds($year, $nowYear, $nowMonth0, $nowDay)
{
	if ((int) $year < (int) $nowYear) {
		return array(11, null);
	}
	if ((int) $year > (int) $nowYear) {
		return array(-1, null);
	}
	$fullDays = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $nowMonth0 + 1)));
	$dayCap = ((int) $nowDay < $fullDays) ? (int) $nowDay : null;
	return array((int) $nowMonth0, $dayCap);
}

function dashboard_ops_trans_tables_for_year($conn, $year)
{
	$tables = array();
	for ($q = 1; $q <= 4; $q++) {
		$name = 'transaction_' . $q . '_' . (int) $year;
		if (dashboard_ops_table_exists($conn, $name)) {
			$tables[] = $name;
		}
	}
	$r = mysqli_query($conn, 'SELECT table_name FROM transaction_tbls');
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$tn = 'transaction_' . $row['table_name'];
			if (preg_match('/^transaction_[1-4]_' . (int) $year . '$/', $tn) && dashboard_ops_table_exists($conn, $tn) && !in_array($tn, $tables, true)) {
				$tables[] = $tn;
			}
		}
	}
	return $tables;
}

function dashboard_ops_load_consignments($conn, &$yearsData, $years, $cutoffIso, &$recent)
{
	$recent = array();
	foreach ($years as $year) {
		if (!isset($yearsData[$year])) {
			continue;
		}
		foreach (dashboard_ops_trans_tables_for_year($conn, $year) as $table) {
			$cols = dashboard_ops_columns($conn, $table);
			if (!isset($cols['grn_date'])) {
				continue;
			}
			$dtExpr = dashboard_ops_sql_date('grn_date');
			$where = array("$dtExpr IS NOT NULL", "YEAR($dtExpr)=" . (int) $year);
			if (isset($cols['booking_status'])) {
				$where[] = "(booking_status IS NULL OR booking_status='' OR booking_status<>'1')";
			}
			$hasPaidAmt = isset($cols['paid_amount']);
			$hasPaidSt = isset($cols['paid_status']);
			$hasInvoiceNo = isset($cols['invoice_no']);
			$hasTotal = isset($cols['total']);
			$hasMode = isset($cols['mode_of_consignment']);
			$hasStatus = isset($cols['status']);
			$hasActive = isset($cols['active_status']);

			$paidAmt = $hasPaidAmt ? 'COALESCE(paid_amount,0)' : '0';
			$isPaid = '0';
			if ($hasPaidSt) {
				$isPaid = "(paid_status='1' OR paid_status=1)";
			} elseif ($hasMode) {
				$isPaid = "(mode_of_consignment='3' OR mode_of_consignment=3)";
			}
			$invCnt = $hasInvoiceNo ? "(CASE WHEN invoice_no IS NOT NULL AND TRIM(invoice_no)<>'' THEN 1 ELSE 0 END)" : '0';
			$invAmt = ($hasInvoiceNo && $hasTotal) ? "(CASE WHEN invoice_no IS NOT NULL AND TRIM(invoice_no)<>'' THEN COALESCE(total,0) ELSE 0 END)" : '0';
			$payCnt = "($isPaid)";
			$payAmt = $hasPaidAmt ? $paidAmt : (($hasTotal) ? "(CASE WHEN $isPaid THEN COALESCE(total,0) ELSE 0 END)" : '0');

			$delivered = $hasStatus ? "(CASE WHEN status='8' OR status=8 THEN 1 ELSE 0 END)" : '0';
			$cutoffSql = "'" . mysqli_real_escape_string($conn, $cutoffIso) . "'";
			$activeBit = $hasActive ? ' AND (active_status=0 OR active_status="0")' : '';
			$delayed = $hasStatus
				? "(CASE WHEN status<>'8' AND status<>8 $activeBit AND $dtExpr <= $cutoffSql THEN 1 ELSE 0 END)"
				: '0';
			$pending = $hasStatus
				? "(CASE WHEN (status='0' OR status=0 OR status='1' OR status=1) AND $dtExpr > $cutoffSql THEN 1 ELSE 0 END)"
				: '0';
			$transit = $hasStatus
				? "(CASE WHEN status>=2 AND status<=7 AND $dtExpr > $cutoffSql THEN 1 ELSE 0 END)"
				: '0';

			$sql = "SELECT $dtExpr AS dt,
				COUNT(*) AS consignments,
				SUM($invCnt) AS invoiceCount,
				SUM($invAmt) AS invoiceAmount,
				SUM($payCnt) AS paymentCount,
				SUM($payAmt) AS paymentAmount,
				SUM($delivered) AS deliver_cnt,
				SUM($delayed) AS delay_cnt,
				SUM($transit) AS transit_cnt,
				SUM($pending) AS pending_cnt
				FROM `$table`
				WHERE " . implode(' AND ', $where) . "
				GROUP BY dt";
			$q = mysqli_query($conn, $sql);
			if ($q) {
				while ($row = mysqli_fetch_assoc($q)) {
					$iso = $row['dt'];
					if (!$iso) {
						continue;
					}
					dashboard_ops_touch_day($yearsData[$year], $iso, function (&$day) use ($row) {
						$day['consignments'] += (int) $row['consignments'];
						$day['invoiceCount'] += (int) $row['invoiceCount'];
						$day['invoiceAmount'] += (float) $row['invoiceAmount'];
						$day['paymentCount'] += (int) $row['paymentCount'];
						$day['paymentAmount'] += (float) $row['paymentAmount'];
						$day['delivered'] += (int) $row['deliver_cnt'];
						$day['delayed'] += (int) $row['delay_cnt'];
						$day['inTransit'] += (int) $row['transit_cnt'];
						$day['pending'] += (int) $row['pending_cnt'];
					});
				}
			}

			if (isset($cols['grn_no'])) {
				$recentSql = "SELECT grn_no, grn_date, " . ($hasStatus ? 'status' : '0 AS status') . ", "
					. ($hasTotal ? 'total' : '0 AS total') . ", "
					. (isset($cols['consigner']) ? 'consigner' : "'' AS consigner") . ", "
					. (isset($cols['origin']) ? 'origin' : "'' AS origin") . ", "
					. (isset($cols['destination']) ? 'destination' : "'' AS destination")
					. " FROM `$table` WHERE " . implode(' AND ', $where)
					. " ORDER BY $dtExpr DESC LIMIT 20";
				$rq = mysqli_query($conn, $recentSql);
				if ($rq) {
					while ($rr = mysqli_fetch_assoc($rq)) {
						$iso = null;
						$raw = trim((string) $rr['grn_date']);
						$try = DateTime::createFromFormat('d-m-Y', $raw);
						if (!$try) {
							$try = date_create($raw);
						}
						if ($try) {
							$iso = $try->format('Y-m-d');
						}
						$st = (int) $rr['status'];
						$isOld = $iso && $iso <= $cutoffIso;
						if ($st === 8) {
							$statusLabel = 'Delivered';
						} elseif ($isOld) {
							$statusLabel = 'Delayed';
						} elseif ($st <= 1) {
							$statusLabel = 'Pending pickup';
						} else {
							$statusLabel = 'In transit';
						}
						$recent[] = array(
							'id' => $rr['grn_no'],
							'customer' => get_client_name($conn, $rr['consigner']),
							'origin' => get_city_name($conn, $rr['origin']),
							'destination' => get_city_name($conn, $rr['destination']),
							'date' => $iso ? $iso : $raw,
							'status' => $statusLabel,
							'amount' => (float) $rr['total'],
							'_sort' => $iso ? $iso : '1970-01-01',
						);
					}
				}
			}
		}
	}
	usort($recent, function ($a, $b) {
		return strcmp($b['_sort'], $a['_sort']);
	});
	$recent = array_slice($recent, 0, 10);
	foreach ($recent as &$row) {
		unset($row['_sort']);
		$row['customer'] = $row['customer'] !== '' && $row['customer'] !== null ? $row['customer'] : '—';
		$row['origin'] = $row['origin'] !== '' && $row['origin'] !== null ? $row['origin'] : '—';
		$row['destination'] = $row['destination'] !== '' && $row['destination'] !== null ? $row['destination'] : '—';
	}
	unset($row);
}

function dashboard_ops_load_billing_invoices($conn, &$yearsData, $years)
{
	if (!dashboard_ops_table_exists($conn, 'billing_invoice_master')) {
		return;
	}
	$dtExpr = dashboard_ops_sql_date('invoice_date');
	$yearList = implode(',', array_map('intval', $years));
	$sql = "SELECT $dtExpr AS dt, COUNT(*) AS invoiceCount, SUM(COALESCE(grand_total,0)) AS invoiceAmount
		FROM billing_invoice_master
		WHERE status='final' AND $dtExpr IS NOT NULL AND YEAR($dtExpr) IN ($yearList)
		GROUP BY dt";
	$q = mysqli_query($conn, $sql);
	if (!$q) {
		return;
	}
	$hits = array();
	while ($row = mysqli_fetch_assoc($q)) {
		$iso = $row['dt'];
		if (!$iso) {
			continue;
		}
		$y = (int) substr($iso, 0, 4);
		if (!isset($yearsData[$y])) {
			continue;
		}
		$hits[$y][] = $row;
	}
	if (!$hits) {
		return;
	}
	foreach ($hits as $y => $rows) {
		foreach ($yearsData[$y]['months'] as $mi => $mo) {
			foreach ($mo['days'] as $di => $day) {
				$yearsData[$y]['months'][$mi]['days'][$di]['invoiceCount'] = 0;
				$yearsData[$y]['months'][$mi]['days'][$di]['invoiceAmount'] = 0;
			}
			$yearsData[$y]['months'][$mi] = dashboard_ops_agg_days(
				$yearsData[$y]['months'][$mi]['days'],
				$mo['idx'],
				$mo['name']
			);
		}
		foreach ($rows as $row) {
			dashboard_ops_touch_day($yearsData[$y], $row['dt'], function (&$day) use ($row) {
				$day['invoiceCount'] += (int) $row['invoiceCount'];
				$day['invoiceAmount'] += (float) $row['invoiceAmount'];
			});
		}
	}
}

function dashboard_ops_load_expenses($conn, &$yearsData, $years)
{
	$yearList = implode(',', array_map('intval', $years));
	$catJoin = dashboard_ops_table_exists($conn, 'expense_category')
		? 'LEFT JOIN expense_category c ON c.category_id = l.category_id'
		: '';
	$catName = $catJoin ? "COALESCE(NULLIF(c.category_name,''),'Miscellaneous')" : "'Miscellaneous'";

	if (dashboard_ops_table_exists($conn, 'gcn_expense_lines')) {
		$dtExpr = dashboard_ops_sql_date('l.expense_date');
		$sql = "SELECT $dtExpr AS dt, $catName AS cat, SUM(COALESCE(l.expense_amount,0)) AS amt
			FROM gcn_expense_lines l
			$catJoin
			WHERE $dtExpr IS NOT NULL AND YEAR($dtExpr) IN ($yearList)
			GROUP BY dt, cat";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($row = mysqli_fetch_assoc($q)) {
				$y = (int) substr($row['dt'], 0, 4);
				if (isset($yearsData[$y])) {
					dashboard_ops_add_expense($yearsData[$y], $row['dt'], $row['cat'], (float) $row['amt']);
				}
			}
		}
	}

	if (dashboard_ops_table_exists($conn, 'general_expense_header')) {
		$cols = dashboard_ops_columns($conn, 'general_expense_header');
		$amtCol = isset($cols['expense_amount']) ? 'COALESCE(expense_amount, expenses_without_gst, 0)' : 'COALESCE(expenses_without_gst,0)';
		$dtExpr = dashboard_ops_sql_date('expense_date');
		$catExpr = "'General'";
		if (isset($cols['category_id']) && dashboard_ops_table_exists($conn, 'expense_category')) {
			$catExpr = "COALESCE((SELECT category_name FROM expense_category WHERE category_id = general_expense_header.category_id LIMIT 1),'General')";
		}
		$sql = "SELECT $dtExpr AS dt, $catExpr AS cat, SUM($amtCol) AS amt
			FROM general_expense_header
			WHERE $dtExpr IS NOT NULL AND YEAR($dtExpr) IN ($yearList)
			GROUP BY dt, cat";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($row = mysqli_fetch_assoc($q)) {
				$y = (int) substr($row['dt'], 0, 4);
				if (isset($yearsData[$y])) {
					dashboard_ops_add_expense($yearsData[$y], $row['dt'], $row['cat'], (float) $row['amt']);
				}
			}
		}
	}
}

function dashboard_ops_available_years($nowYear = null)
{
	$nowYear = $nowYear === null ? (int) date('Y') : (int) $nowYear;
	return array($nowYear - 2, $nowYear - 1, $nowYear);
}

function dashboard_ops_slice_year($yearObj, $monthCap, $dayCap)
{
	if (empty($yearObj['months'])) {
		return array();
	}
	$months = array_slice($yearObj['months'], 0, (int) $monthCap + 1);
	foreach ($months as $i => $mo) {
		if ($i === (int) $monthCap && $dayCap !== null) {
			$days = array();
			foreach ($mo['days'] as $day) {
				if ((int) $day['d'] <= (int) $dayCap) {
					$days[] = $day;
				}
			}
			$months[$i] = dashboard_ops_agg_days($days, $mo['idx'], $mo['name']);
		}
	}
	return array_values($months);
}

function dashboard_ops_payload($conn, $years = null)
{
	$now = new DateTime('now');
	$nowYear = (int) $now->format('Y');
	$nowMonth0 = (int) $now->format('n') - 1;
	$nowDay = (int) $now->format('j');
	$available = dashboard_ops_available_years($nowYear);
	if ($years === null) {
		$years = $available;
	} else {
		$years = array_values(array_unique(array_map('intval', (array) $years)));
		$years = array_values(array_intersect($years, $available));
		if (!$years) {
			$years = array($nowYear);
		}
	}
	$cutoffIso = (clone $now)->modify('-3 days')->format('Y-m-d');

	$yearsData = array();
	foreach ($years as $year) {
		list($monthCap, $dayCap) = dashboard_ops_year_bounds($year, $nowYear, $nowMonth0, $nowDay);
		if ($monthCap < 0) {
			continue;
		}
		$yearsData[$year] = dashboard_ops_blank_year($year, $monthCap, $dayCap);
	}

	$recent = array();
	dashboard_ops_load_consignments($conn, $yearsData, $years, $cutoffIso, $recent);
	dashboard_ops_load_billing_invoices($conn, $yearsData, $years);
	dashboard_ops_load_expenses($conn, $yearsData, $years);

	return array(
		'asOf' => $now->format('d M Y'),
		'todayDay' => $nowDay,
		'currentYear' => $nowYear,
		'years' => $available,
		'requestedYears' => $years,
		'data' => $yearsData,
		'recent' => $recent,
	);
}

function dashboard_ops_year_response($conn, $year)
{
	$nowYear = (int) date('Y');
	$available = dashboard_ops_available_years($nowYear);
	$year = (int) $year;
	if (!in_array($year, $available, true)) {
		$year = $nowYear;
	}
	$need = array($year);
	if (in_array($year - 1, $available, true)) {
		$need[] = $year - 1;
	}
	$payload = dashboard_ops_payload($conn, $need);
	$yearData = isset($payload['data'][$year]) ? $payload['data'][$year] : dashboard_ops_blank_year($year, 0, 1);
	$priorMonths = null;
	if (isset($payload['data'][$year - 1]) && !empty($yearData['months'])) {
		$monthCap = count($yearData['months']) - 1;
		$last = $yearData['months'][$monthCap];
		$fullDays = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $monthCap + 1)));
		$actual = count($last['days']);
		$dayCap = $actual < $fullDays ? $actual : null;
		$priorMonths = dashboard_ops_slice_year($payload['data'][$year - 1], $monthCap, $dayCap);
	}
	$recent = ($year === $nowYear) ? $payload['recent'] : array();
	return array(
		'status' => 0,
		'year' => $year,
		'asOf' => $payload['asOf'],
		'todayDay' => $payload['todayDay'],
		'currentYear' => $payload['currentYear'],
		'years' => $payload['years'],
		'goLiveMonth' => 4,
		'yearData' => $yearData,
		'priorMonths' => $priorMonths,
		'recent' => $recent,
		'exceptions' => dashboard_ops_exceptions($conn, $year, $yearData),
	);
}

function dashboard_ops_exceptions($conn, $year, $yearData)
{
	$months = isset($yearData['months']) ? $yearData['months'] : array();
	$delayed = 0;
	$pending = 0;
	$inTransit = 0;
	foreach ($months as $mo) {
		if ((int) $mo['idx'] < 4) {
			continue;
		}
		$delayed += (int) $mo['delayed'];
		$pending += (int) $mo['pending'];
		$inTransit += (int) $mo['inTransit'];
	}

	$draft = 0;
	if (dashboard_ops_table_exists($conn, 'billing_invoice_master')) {
		$q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM billing_invoice_master WHERE status='draft'");
		if ($q && ($row = mysqli_fetch_assoc($q))) {
			$draft = (int) $row['c'];
		}
	}

	$unbilled = 0;
	if (dashboard_ops_table_exists($conn, 'billing_invoice_details')) {
		foreach (dashboard_ops_trans_tables_for_year($conn, $year) as $table) {
			$cols = dashboard_ops_columns($conn, $table);
			if (!isset($cols['status']) || !isset($cols['transaction_id'])) {
				continue;
			}
			$dtExpr = isset($cols['grn_date']) ? dashboard_ops_sql_date('grn_date') : 'NULL';
			$where = array("(status='8' OR status=8)");
			if ($dtExpr !== 'NULL') {
				$where[] = "$dtExpr IS NOT NULL";
				$where[] = 'YEAR(' . $dtExpr . ')=' . (int) $year;
				$where[] = 'MONTH(' . $dtExpr . ')>=5';
			}
			if (isset($cols['booking_status'])) {
				$where[] = "(booking_status IS NULL OR booking_status='' OR booking_status<>'1')";
			}
			$esc = mysqli_real_escape_string($conn, $table);
			$sql = "SELECT COUNT(*) AS c FROM `$table` t
				WHERE " . implode(' AND ', $where) . "
				AND NOT EXISTS (
					SELECT 1 FROM billing_invoice_details d
					INNER JOIN billing_invoice_master m ON m.billing_invoice_id = d.billing_invoice_id
					WHERE d.trans_table='$esc' AND d.transaction_id = t.transaction_id AND m.status='final'
				)";
			$q = mysqli_query($conn, $sql);
			if ($q && ($row = mysqli_fetch_assoc($q))) {
				$unbilled += (int) $row['c'];
			}
		}
	}

	return array(
		'delayed' => $delayed,
		'pendingPickup' => $pending,
		'inTransit' => $inTransit,
		'unbilled' => $unbilled,
		'draftInvoices' => $draft,
	);
}
