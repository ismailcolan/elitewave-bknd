<?php

require_once __DIR__ . '/expense_gcn_helpers.php';
require_once __DIR__ . '/expense_bill_helpers.php';
require_once __DIR__ . '/expense_general_helpers.php';
require_once __DIR__ . '/petty_cash_helpers.php';

function expense_gcn_pl_money($v)
{
	return round((float) $v, 2);
}

function expense_gcn_pl_format($v)
{
	return number_format(expense_gcn_pl_money($v), 2, '.', '');
}

function expense_gcn_pl_profit_label($profit)
{
	$p = expense_gcn_pl_money($profit);
	if ($p > 0.009) {
		return 'Profit ' . expense_gcn_pl_format($p);
	}
	if ($p < -0.009) {
		return 'Loss ' . expense_gcn_pl_format(abs($p));
	}
	return 'Break-even';
}

/** List cell: signed ₹ amount, no Profit/Loss wording. */
function expense_gcn_pl_profit_display($profit)
{
	$p = expense_gcn_pl_money($profit);
	if ($p > 0.009) {
		return array(
			'class' => 'profit-positive',
			'text' => '+ ₹ ' . expense_gcn_pl_format($p),
		);
	}
	if ($p < -0.009) {
		return array(
			'class' => 'profit-negative',
			'text' => '− ₹ ' . expense_gcn_pl_format(abs($p)),
		);
	}
	return array(
		'class' => 'profit-breakeven',
		'text' => '0.00',
	);
}

function expense_gcn_pl_format_vendor_name($code, $name)
{
	$code = trim((string) $code);
	$name = trim((string) $name);
	if ($code !== '' && $name !== '') {
		return $code . ' — ' . $name;
	}
	if ($name !== '') {
		return $name;
	}
	if ($code !== '') {
		return $code;
	}
	return '';
}

function expense_gcn_pl_vendor_name_only($vendor_name)
{
	$name = trim((string) $vendor_name);
	return $name !== '' ? $name : '';
}

function expense_gcn_pl_vendor_label($conn, $gcn_key)
{
	$gcn_key = trim((string) $gcn_key);
	if ($gcn_key === '') {
		return '—';
	}
	$esc = mysqli_real_escape_string($conn, $gcn_key);
	$labels = array();
	$sql = "SELECT DISTINCT v.vendor_name
		FROM vendor_bill_gcn g
		INNER JOIN vendor_bill b ON b.bill_id = g.bill_id AND b.status = 0
		INNER JOIN vendor_master v ON v.vendor_id = b.vendor_id
		WHERE g.gcn_key = '$esc' AND TRIM(v.vendor_name) <> ''
		UNION
		SELECT DISTINCT v.vendor_name
		FROM general_expense_lines l
		INNER JOIN general_expense_header h ON h.general_expense_id = l.general_expense_id AND h.status = 0
		INNER JOIN vendor_master v ON v.vendor_id = h.vendor_id
		WHERE l.gcn_key = '$esc' AND h.vendor_id > 0 AND TRIM(v.vendor_name) <> ''
		UNION
		SELECT DISTINCT v.vendor_name
		FROM petty_cash_line l
		INNER JOIN petty_cash_voucher p ON p.petty_cash_id = l.petty_cash_id AND p.status = 0
		INNER JOIN vendor_master v ON v.vendor_id = l.vendor_id
		WHERE l.gcn_key = '$esc' AND l.vendor_id > 0 AND TRIM(v.vendor_name) <> ''";
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$lbl = expense_gcn_pl_vendor_name_only($row['vendor_name'] ?? '');
			if ($lbl !== '') {
				$labels[$lbl] = $lbl;
			}
		}
	}
	if (empty($labels)) {
		return '—';
	}
	return implode(', ', array_values($labels));
}

function expense_gcn_pl_allocate_share($bill_subtotal, $gcn_freight, $amount)
{
	$bill_subtotal = expense_gcn_pl_money($bill_subtotal);
	$gcn_freight = expense_gcn_pl_money($gcn_freight);
	$amount = expense_gcn_pl_money($amount);
	if ($amount <= 0 || $gcn_freight <= 0) {
		return 0.0;
	}
	if ($bill_subtotal <= 0) {
		return $amount;
	}
	return expense_gcn_pl_money($amount * ($gcn_freight / $bill_subtotal));
}

function expense_gcn_pl_customer_freight(array $ctx)
{
	$freight = expense_gcn_pl_money($ctx['freight_without_gst_raw'] ?? 0);
	if ($freight > 0) {
		return $freight;
	}
	return expense_gcn_pl_money($ctx['revenue_without_gst_raw'] ?? 0);
}

/**
 * Build P&L aggregates from bills, payment vouchers, legacy GCN vouchers, and petty cash.
 */
function expense_gcn_pl_load_aggregates($conn)
{
	expense_bill_ensure_schema($conn);
	expense_general_ensure_schema($conn);
	petty_cash_ensure_schema($conn);

	$bill_by_gcn = array();
	$bill_meta = array();
	$q = mysqli_query($conn, "SELECT g.gcn_key, g.freight_amount, g.bill_id,
			b.subtotal, b.net_payable, b.grand_total
		FROM vendor_bill_gcn g
		INNER JOIN vendor_bill b ON b.bill_id = g.bill_id AND b.status=0
		WHERE TRIM(g.gcn_key) <> ''");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$key = trim((string) $row['gcn_key']);
			if ($key === '') {
				continue;
			}
			$bid = (int) $row['bill_id'];
			$freight = expense_gcn_pl_money($row['freight_amount'] ?? 0);
			if ($freight <= 0) {
				$freight = expense_gcn_pl_money($row['subtotal'] ?? 0);
			}
			if (!isset($bill_by_gcn[$key])) {
				$bill_by_gcn[$key] = array('vendor_cost' => 0.0, 'bill_ids' => array());
			}
			$bill_by_gcn[$key]['vendor_cost'] += $freight;
			$bill_by_gcn[$key]['bill_ids'][$bid] = true;
			if (!isset($bill_meta[$bid])) {
				$sub = expense_gcn_pl_money($row['subtotal'] ?? 0);
				if ($sub <= 0) {
					$sub = expense_gcn_pl_money($row['grand_total'] ?? $row['net_payable'] ?? 0);
				}
				$bill_meta[$bid] = array(
					'subtotal' => $sub,
					'gcns' => array(),
				);
			}
			$bill_meta[$bid]['gcns'][$key] = expense_gcn_pl_money($row['freight_amount'] ?? 0);
		}
	}

	$bill_paid = array();
	$pq = mysqli_query($conn, "SELECT vendor_bill_id, COALESCE(SUM(
			CASE
				WHEN expenses_without_gst > 0 THEN expenses_without_gst
				WHEN expense_amount > 0 THEN expense_amount
				ELSE net_payable
			END
		),0) AS paid
		FROM general_expense_header
		WHERE status=0 AND vendor_bill_id > 0
		AND LOWER(IFNULL(voucher_type,''))='purchase_bill'
		GROUP BY vendor_bill_id");
	if ($pq) {
		while ($row = mysqli_fetch_assoc($pq)) {
			$bill_paid[(int) $row['vendor_bill_id']] = expense_gcn_pl_money($row['paid'] ?? 0);
		}
	}

	$vendor_paid_by_gcn = array();
	foreach ($bill_meta as $bid => $meta) {
		$paid = $bill_paid[$bid] ?? 0.0;
		if ($paid <= 0) {
			continue;
		}
		$sub = $meta['subtotal'];
		$gcn_count = count($meta['gcns']);
		foreach ($meta['gcns'] as $gkey => $g_freight) {
			if ($g_freight <= 0 && $sub <= 0 && $gcn_count > 0) {
				$share = expense_gcn_pl_money($paid / $gcn_count);
			} else {
				$share = expense_gcn_pl_allocate_share($sub, $g_freight, $paid);
			}
			if (!isset($vendor_paid_by_gcn[$gkey])) {
				$vendor_paid_by_gcn[$gkey] = 0.0;
			}
			$vendor_paid_by_gcn[$gkey] += $share;
		}
	}

	$direct_cost_by_gcn = array();
	$direct_paid_by_gcn = array();
	/* voucher_type=gcn rows stay on Existing Backup GCN P&L only (not new GCN P&L). */

	$petty_by_gcn = array();
	$pcq = mysqli_query($conn, "SELECT l.gcn_key,
			SUM(CASE WHEN l.net_amount > 0 THEN l.net_amount ELSE l.amount END) AS amt
		FROM petty_cash_line l
		INNER JOIN petty_cash_voucher v ON v.petty_cash_id = l.petty_cash_id AND v.status=0
		WHERE LOWER(IFNULL(v.entry_type,'payment'))='payment'
		AND TRIM(l.gcn_key) <> ''
		GROUP BY l.gcn_key");
	if ($pcq) {
		while ($row = mysqli_fetch_assoc($pcq)) {
			$key = trim((string) $row['gcn_key']);
			if ($key === '') {
				continue;
			}
			$petty_by_gcn[$key] = expense_gcn_pl_money($row['amt'] ?? 0);
		}
	}

	$pcgq = mysqli_query($conn, "SELECT CONCAT(g.trans_table,'|',g.transaction_id) AS gcn_key,
			SUM(CASE WHEN l.net_amount > 0 THEN l.net_amount ELSE l.amount END) AS amt
		FROM petty_cash_gcn g
		INNER JOIN petty_cash_voucher v ON v.petty_cash_id = g.petty_cash_id AND v.status=0
		INNER JOIN petty_cash_line l ON l.petty_cash_id = g.petty_cash_id
		WHERE LOWER(IFNULL(v.entry_type,'payment'))='payment'
		AND (TRIM(l.gcn_key) = '' OR l.gcn_key IS NULL)
		GROUP BY g.trans_table, g.transaction_id");
	if ($pcgq) {
		while ($row = mysqli_fetch_assoc($pcgq)) {
			$key = trim((string) $row['gcn_key']);
			if ($key === '' || strpos($key, '|') === false) {
				continue;
			}
			$amt = expense_gcn_pl_money($row['amt'] ?? 0);
			if ($amt <= 0) {
				continue;
			}
			if (!isset($petty_by_gcn[$key])) {
				$petty_by_gcn[$key] = 0.0;
			}
			$petty_by_gcn[$key] += $amt;
		}
	}

	return array(
		'bill_by_gcn' => $bill_by_gcn,
		'direct_cost_by_gcn' => $direct_cost_by_gcn,
		'direct_paid_by_gcn' => $direct_paid_by_gcn,
		'vendor_paid_by_gcn' => $vendor_paid_by_gcn,
		'petty_by_gcn' => $petty_by_gcn,
	);
}

function expense_gcn_pl_collect_gcn_keys($conn, array $agg)
{
	$keys = array();
	$add = function ($key) use (&$keys) {
		$key = trim((string) $key);
		if ($key !== '' && strpos($key, '|') !== false) {
			$keys[$key] = array('grn_no' => '', 'grn_date' => '');
		}
	};
	foreach (array_keys($agg['bill_by_gcn'] ?? array()) as $key) {
		$add($key);
	}
	foreach (array_keys($agg['direct_cost_by_gcn'] ?? array()) as $key) {
		$add($key);
	}
	foreach (array_keys($agg['direct_paid_by_gcn'] ?? array()) as $key) {
		$add($key);
	}
	foreach (array_keys($agg['vendor_paid_by_gcn'] ?? array()) as $key) {
		$add($key);
	}
	foreach (array_keys($agg['petty_by_gcn'] ?? array()) as $key) {
		$add($key);
	}

	if (!empty($keys)) {
		$esc = array();
		foreach (array_keys($keys) as $k) {
			$esc[] = "'" . mysqli_real_escape_string($conn, $k) . "'";
		}
		$in = implode(',', $esc);
		$gq = mysqli_query($conn, "SELECT gcn_key, gcn_no, gcn_date FROM vendor_bill_gcn WHERE gcn_key IN ($in)");
		if ($gq) {
			while ($row = mysqli_fetch_assoc($gq)) {
				$k = trim((string) ($row['gcn_key'] ?? ''));
				if ($k === '' || !isset($keys[$k])) {
					continue;
				}
				if (trim((string) ($row['gcn_no'] ?? '')) !== '') {
					$keys[$k]['grn_no'] = trim((string) $row['gcn_no']);
				}
				if (trim((string) ($row['gcn_date'] ?? '')) !== '') {
					$keys[$k]['grn_date'] = trim((string) $row['gcn_date']);
				}
			}
		}
		$lq = mysqli_query($conn, "SELECT gcn_key, gcn_no, gcn_date FROM general_expense_lines
			WHERE gcn_key IN ($in) AND TRIM(gcn_no) <> '' LIMIT 500");
		if ($lq) {
			while ($row = mysqli_fetch_assoc($lq)) {
				$k = trim((string) ($row['gcn_key'] ?? ''));
				if ($k === '' || !isset($keys[$k])) {
					continue;
				}
				if ($keys[$k]['grn_no'] === '' && trim((string) ($row['gcn_no'] ?? '')) !== '') {
					$keys[$k]['grn_no'] = trim((string) $row['gcn_no']);
				}
				if ($keys[$k]['grn_date'] === '' && trim((string) ($row['gcn_date'] ?? '')) !== '') {
					$keys[$k]['grn_date'] = trim((string) $row['gcn_date']);
				}
			}
		}
	}
	return $keys;
}

function expense_gcn_pl_row_for_key($conn, $gcn_key, array $meta, array $agg)
{
	$gcn_key = trim((string) $gcn_key);
	if ($gcn_key === '' || strpos($gcn_key, '|') === false) {
		return null;
	}
	list($trans_table, $transaction_id) = explode('|', $gcn_key, 2);
	$ctx = expense_gcn_fetch_context($conn, $trans_table, (int) $transaction_id);
	if (empty($ctx['ok'])) {
		return null;
	}

	$customer_revenue = expense_gcn_pl_customer_freight($ctx);
	$bill_cost = expense_gcn_pl_money($agg['bill_by_gcn'][$gcn_key]['vendor_cost'] ?? 0);
	$direct_cost = expense_gcn_pl_money($agg['direct_cost_by_gcn'][$gcn_key] ?? 0);
	$vendor_cost = expense_gcn_pl_money($bill_cost + $direct_cost);

	$vendor_paid = expense_gcn_pl_money(
		($agg['vendor_paid_by_gcn'][$gcn_key] ?? 0) + ($agg['direct_paid_by_gcn'][$gcn_key] ?? 0)
	);
	$vendor_balance = expense_gcn_pl_money(max(0, $vendor_cost - $vendor_paid));

	$petty = expense_gcn_pl_money($agg['petty_by_gcn'][$gcn_key] ?? 0);
	$total_cost = expense_gcn_pl_money($vendor_cost + $petty);
	$profit = expense_gcn_pl_money($customer_revenue - $total_cost);
	$profit_disp = expense_gcn_pl_profit_display($profit);

	$grn_no = trim((string) ($meta['grn_no'] ?? ''));
	if ($grn_no === '') {
		$grn_no = trim((string) ($ctx['grn_no'] ?? ''));
	}
	$grn_date = trim((string) ($meta['grn_date'] ?? ''));
	if ($grn_date === '') {
		$grn_date = trim((string) ($ctx['grn_date'] ?? ''));
	}

	return array(
		'gcn_key' => $gcn_key,
		'grn_no' => $grn_no !== '' ? $grn_no : '—',
		'grn_date' => $grn_date !== '' ? $grn_date : '—',
		'customer_label' => trim((string) ($ctx['consignor'] ?? '')) ?: '—',
		'vendor_label' => expense_gcn_pl_vendor_label($conn, $gcn_key),
		'customer_revenue' => expense_gcn_pl_format($customer_revenue),
		'revenue' => expense_gcn_pl_format($customer_revenue),
		'customer_revenue_raw' => $customer_revenue,
		'vendor_cost' => expense_gcn_pl_format($vendor_cost),
		'vendor_cost_raw' => $vendor_cost,
		'petty_cash' => expense_gcn_pl_format($petty),
		'petty_cash_raw' => $petty,
		'total_cost' => expense_gcn_pl_format($total_cost),
		'total_cost_raw' => $total_cost,
		'profit_amount' => $profit_disp['text'],
		'profit_class' => $profit_disp['class'],
		'profit_raw' => $profit,
		'vendor_paid' => expense_gcn_pl_format($vendor_paid),
		'vendor_paid_raw' => $vendor_paid,
		'vendor_balance' => expense_gcn_pl_format($vendor_balance),
		'vendor_balance_raw' => $vendor_balance,
		'route_label' => $ctx['route_label'] ?? '—',
	);
}

function expense_gcn_pl_fetch_list($conn)
{
	$agg = expense_gcn_pl_load_aggregates($conn);
	$key_map = expense_gcn_pl_collect_gcn_keys($conn, $agg);
	$rows = array();
	foreach ($key_map as $key => $meta) {
		$row = expense_gcn_pl_row_for_key($conn, $key, $meta, $agg);
		if (!$row) {
			continue;
		}
		$timeline = expense_gcn_pl_sort_payment_timeline(expense_gcn_pl_fetch_payment_rows($conn, $key));
		$row['payment_timeline'] = $timeline;
		$row['payment_count'] = count($timeline);
		$rows[] = $row;
	}
	usort($rows, function ($a, $b) {
		$da = $a['grn_date'] ?? '';
		$db = $b['grn_date'] ?? '';
		if ($da !== $db) {
			$t1 = strtotime(str_replace('/', '-', $da));
			$t2 = strtotime(str_replace('/', '-', $db));
			if ($t1 && $t2 && $t1 !== $t2) {
				return $t2 <=> $t1;
			}
			return strcmp($db, $da);
		}
		return strcmp($b['grn_no'] ?? '', $a['grn_no'] ?? '');
	});
	return $rows;
}

function expense_gcn_pl_bill_ids_for_gcn($conn, $gcn_key)
{
	$gcn_key = trim((string) $gcn_key);
	if ($gcn_key === '') {
		return array();
	}
	$esc = mysqli_real_escape_string($conn, $gcn_key);
	$ids = array();
	$q = mysqli_query($conn, "SELECT DISTINCT g.bill_id FROM vendor_bill_gcn g
		INNER JOIN vendor_bill b ON b.bill_id = g.bill_id AND b.status = 0
		WHERE g.gcn_key = '$esc'");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$bid = (int) ($row['bill_id'] ?? 0);
			if ($bid > 0) {
				$ids[$bid] = $bid;
			}
		}
	}
	return array_values($ids);
}

function expense_gcn_pl_gcn_freight_on_bill($conn, $bill_id, $gcn_key)
{
	$bill_id = (int) $bill_id;
	$gcn_key = trim((string) $gcn_key);
	if ($bill_id <= 0 || $gcn_key === '') {
		return 0.0;
	}
	$esc = mysqli_real_escape_string($conn, $gcn_key);
	$q = mysqli_query($conn, "SELECT freight_amount FROM vendor_bill_gcn
		WHERE bill_id='$bill_id' AND gcn_key='$esc' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return expense_gcn_pl_money($row['freight_amount'] ?? 0);
	}
	return 0.0;
}

function expense_gcn_pl_sort_payment_timeline(array $payment_rows)
{
	if (empty($payment_rows)) {
		return array();
	}
	usort($payment_rows, function ($a, $b) {
		$ida = (int) ($a['general_expense_id'] ?? 0);
		$idb = (int) ($b['general_expense_id'] ?? 0);
		if ($ida !== $idb) {
			return $ida <=> $idb;
		}
		return strcmp((string) ($a['voucher_date'] ?? ''), (string) ($b['voucher_date'] ?? ''));
	});
	return $payment_rows;
}

function expense_gcn_pl_fetch_payment_rows($conn, $gcn_key)
{
	expense_bill_ensure_schema($conn);
	expense_general_ensure_schema($conn);

	$gcn_key = trim((string) $gcn_key);
	$payment_rows = array();
	if ($gcn_key === '' || strpos($gcn_key, '|') === false) {
		return $payment_rows;
	}
	$esc = mysqli_real_escape_string($conn, $gcn_key);

	$bill_ids = expense_gcn_pl_bill_ids_for_gcn($conn, $gcn_key);
	if (!empty($bill_ids)) {
		$in = implode(',', array_map('intval', $bill_ids));
		$pq = mysqli_query($conn, "SELECT h.general_expense_id, h.expense_no, h.expense_date, h.net_payable,
				h.expenses_without_gst, h.expense_amount, h.vendor_bill_id, h.payment_mode, b.bill_entry_no, b.subtotal,
				v.vendor_code, v.vendor_name,
				l.expense_amount AS line_expense, c.category_name
			FROM general_expense_header h
			INNER JOIN vendor_bill b ON b.bill_id = h.vendor_bill_id AND b.status = 0
			LEFT JOIN vendor_master v ON v.vendor_id = h.vendor_id
			LEFT JOIN general_expense_lines l ON l.general_expense_id = h.general_expense_id
			LEFT JOIN expense_category c ON c.category_id = l.category_id
			WHERE h.status = 0 AND h.vendor_bill_id IN ($in)
			AND LOWER(IFNULL(h.voucher_type,'')) = 'purchase_bill'
			ORDER BY h.general_expense_id ASC, l.line_no ASC");
		if ($pq) {
			$seen_gid = array();
			while ($row = mysqli_fetch_assoc($pq)) {
				$gid = (int) ($row['general_expense_id'] ?? 0);
				if ($gid <= 0 || isset($seen_gid[$gid])) {
					continue;
				}
				$seen_gid[$gid] = true;
				$bid = (int) ($row['vendor_bill_id'] ?? 0);
				$g_freight = expense_gcn_pl_gcn_freight_on_bill($conn, $bid, $gcn_key);
				$sub = expense_gcn_pl_money($row['subtotal'] ?? 0);
				$paid = expense_gcn_pl_money($row['expenses_without_gst'] ?? 0);
				if ($paid <= 0) {
					$paid = expense_gcn_pl_money($row['line_expense'] ?? 0);
				}
				if ($paid <= 0) {
					$paid = expense_gcn_pl_money($row['expense_amount'] ?? 0);
				}
				if ($paid <= 0) {
					$paid = expense_gcn_pl_money($row['net_payable'] ?? 0);
				}
				$allocated = expense_gcn_pl_allocate_share($sub, $g_freight, $paid);
				if ($allocated <= 0 && $g_freight <= 0) {
					continue;
				}
				$cat = trim((string) ($row['category_name'] ?? ''));
				$payment_rows[] = array(
					'general_expense_id' => $gid,
					'voucher_no' => trim((string) ($row['expense_no'] ?? '')) ?: ('PV #' . $gid),
					'voucher_date' => trim((string) ($row['expense_date'] ?? '')) ?: '—',
					'vendor_label' => expense_gcn_pl_vendor_name_only($row['vendor_name'] ?? '') ?: '—',
					'reference' => trim((string) ($row['bill_entry_no'] ?? '')) ?: '—',
					'expense_type' => $cat !== '' ? $cat : 'Payment against purchase bill',
					'payment_mode' => strtoupper(trim((string) ($row['payment_mode'] ?? ''))) ?: '—',
					'amount' => expense_gcn_pl_format($allocated > 0 ? $allocated : $paid),
					'amount_raw' => $allocated > 0 ? $allocated : $paid,
				);
			}
		}
	}

	$dq = mysqli_query($conn, "SELECT h.general_expense_id, h.expense_no, h.expense_date, h.net_payable, h.payment_mode,
			v.vendor_name, l.expense_amount, c.category_name
		FROM general_expense_lines l
		INNER JOIN general_expense_header h ON h.general_expense_id = l.general_expense_id AND h.status = 0
		LEFT JOIN vendor_master v ON v.vendor_id = h.vendor_id
		LEFT JOIN expense_category c ON c.category_id = l.category_id
		WHERE l.gcn_key = '$esc'
		AND LOWER(IFNULL(h.voucher_type,'')) = 'gcn'
		AND (h.vendor_bill_id IS NULL OR h.vendor_bill_id = 0)
		ORDER BY h.general_expense_id ASC, l.line_no ASC");
	if ($dq) {
		while ($row = mysqli_fetch_assoc($dq)) {
			$gid = (int) ($row['general_expense_id'] ?? 0);
			$amt = expense_gcn_pl_money($row['expense_amount'] ?? 0);
			if ($amt <= 0) {
				$amt = expense_gcn_pl_money($row['net_payable'] ?? 0);
			}
			if ($amt <= 0) {
				continue;
			}
			$payment_rows[] = array(
				'general_expense_id' => $gid,
				'voucher_no' => trim((string) ($row['expense_no'] ?? '')) ?: '—',
				'voucher_date' => trim((string) ($row['expense_date'] ?? '')) ?: '—',
				'vendor_label' => expense_gcn_pl_vendor_name_only($row['vendor_name'] ?? '') ?: '—',
				'reference' => 'Direct GCN payment voucher',
				'expense_type' => trim((string) ($row['category_name'] ?? '')) ?: 'GCN vendor expense',
				'payment_mode' => strtoupper(trim((string) ($row['payment_mode'] ?? ''))) ?: '—',
				'amount' => expense_gcn_pl_format($amt),
				'amount_raw' => $amt,
			);
		}
	}

	return $payment_rows;
}

function expense_gcn_pl_fetch_breakdown($conn, $gcn_key)
{
	expense_bill_ensure_schema($conn);
	expense_general_ensure_schema($conn);
	petty_cash_ensure_schema($conn);

	$gcn_key = trim((string) $gcn_key);
	if ($gcn_key === '' || strpos($gcn_key, '|') === false) {
		return array(
			'revenue_rows' => array(),
			'purchase_bill_rows' => array(),
			'payment_rows' => array(),
			'petty_cash_rows' => array(),
		);
	}
	$esc = mysqli_real_escape_string($conn, $gcn_key);

	$revenue_rows = array();
	list($trans_table, $transaction_id) = explode('|', $gcn_key, 2);
	$ctx = expense_gcn_fetch_context($conn, $trans_table, (int) $transaction_id);
	if (!empty($ctx['ok'])) {
		foreach (($ctx['revenue_breakup'] ?? array()) as $rev) {
			$raw = isset($rev['amount_raw']) ? expense_gcn_pl_money($rev['amount_raw']) : expense_gcn_parse_money($rev['amount'] ?? 0);
			$revenue_rows[] = array(
				'label' => trim((string) ($rev['label'] ?? 'Revenue')),
				'amount' => expense_gcn_pl_format($raw),
				'amount_raw' => $raw,
			);
		}
		if (empty($revenue_rows)) {
			$revenue_rows[] = array(
				'label' => 'Customer freight revenue',
				'amount' => expense_gcn_pl_format(expense_gcn_pl_customer_freight($ctx)),
				'amount_raw' => expense_gcn_pl_customer_freight($ctx),
			);
		}
	}

	$purchase_bill_rows = array();
	$bq = mysqli_query($conn, "SELECT b.bill_id, b.bill_entry_no, b.bill_date, b.vendor_invoice_no, b.subtotal,
			g.freight_amount, v.vendor_code, v.vendor_name
		FROM vendor_bill_gcn g
		INNER JOIN vendor_bill b ON b.bill_id = g.bill_id AND b.status = 0
		LEFT JOIN vendor_master v ON v.vendor_id = b.vendor_id
		WHERE g.gcn_key = '$esc'
		ORDER BY b.bill_id DESC");
	if ($bq) {
		while ($row = mysqli_fetch_assoc($bq)) {
			$freight = expense_gcn_pl_money($row['freight_amount'] ?? 0);
			if ($freight <= 0) {
				$freight = expense_gcn_pl_money($row['subtotal'] ?? 0);
			}
			$purchase_bill_rows[] = array(
				'bill_no' => trim((string) ($row['bill_entry_no'] ?? '')) ?: ('Bill #' . (int) $row['bill_id']),
				'bill_date' => trim((string) ($row['bill_date'] ?? '')) ?: '—',
				'vendor_invoice' => trim((string) ($row['vendor_invoice_no'] ?? '')) ?: '—',
				'vendor_label' => expense_gcn_pl_vendor_name_only($row['vendor_name'] ?? '') ?: '—',
				'expense_type' => 'Vendor freight (purchase bill)',
				'amount' => expense_gcn_pl_format($freight),
				'amount_raw' => $freight,
			);
		}
	}

	$payment_rows = expense_gcn_pl_sort_payment_timeline(expense_gcn_pl_fetch_payment_rows($conn, $gcn_key));

	$petty_cash_rows = array();
	$pcq = mysqli_query($conn, "SELECT p.voucher_no, p.voucher_date, l.expense_date, l.amount, l.net_amount,
			l.paid_to, l.remarks, c.category_name, v.vendor_code, v.vendor_name
		FROM petty_cash_line l
		INNER JOIN petty_cash_voucher p ON p.petty_cash_id = l.petty_cash_id AND p.status = 0
		LEFT JOIN expense_category c ON c.category_id = l.category_id
		LEFT JOIN vendor_master v ON v.vendor_id = l.vendor_id
		WHERE LOWER(IFNULL(p.entry_type,'payment')) = 'payment'
		AND l.gcn_key = '$esc'
		ORDER BY p.petty_cash_id DESC, l.line_no ASC");
	if ($pcq) {
		while ($row = mysqli_fetch_assoc($pcq)) {
			$amt = expense_gcn_pl_money($row['net_amount'] ?? 0);
			if ($amt <= 0) {
				$amt = expense_gcn_pl_money($row['amount'] ?? 0);
			}
			$paid_to = trim((string) ($row['paid_to'] ?? ''));
			if ($paid_to === '') {
				$paid_to = expense_gcn_pl_format_vendor_name($row['vendor_code'] ?? '', $row['vendor_name'] ?? '');
			}
			$petty_cash_rows[] = array(
				'voucher_no' => trim((string) ($row['voucher_no'] ?? '')) ?: '—',
				'voucher_date' => trim((string) ($row['voucher_date'] ?? '')) ?: '—',
				'expense_date' => trim((string) ($row['expense_date'] ?? '')) ?: '—',
				'expense_type' => trim((string) ($row['category_name'] ?? '')) ?: 'Petty cash expense',
				'paid_to' => $paid_to !== '' ? $paid_to : '—',
				'remarks' => trim((string) ($row['remarks'] ?? '')) ?: '—',
				'amount' => expense_gcn_pl_format($amt),
				'amount_raw' => $amt,
			);
		}
	}

	return array(
		'revenue_rows' => $revenue_rows,
		'purchase_bill_rows' => $purchase_bill_rows,
		'payment_rows' => $payment_rows,
		'petty_cash_rows' => $petty_cash_rows,
	);
}

function expense_gcn_pl_detail($conn, $gcn_key)
{
	$agg = expense_gcn_pl_load_aggregates($conn);
	$row = expense_gcn_pl_row_for_key($conn, $gcn_key, array(), $agg);
	if (!$row) {
		return null;
	}
	$row['bill_vendor_cost'] = expense_gcn_pl_format($agg['bill_by_gcn'][$gcn_key]['vendor_cost'] ?? 0);
	$row['direct_vendor_cost'] = expense_gcn_pl_format($agg['direct_cost_by_gcn'][$gcn_key] ?? 0);
	$row['allocated_vendor_paid'] = expense_gcn_pl_format($agg['vendor_paid_by_gcn'][$gcn_key] ?? 0);
	$row['direct_vendor_paid'] = expense_gcn_pl_format($agg['direct_paid_by_gcn'][$gcn_key] ?? 0);
	$row['breakdown'] = expense_gcn_pl_fetch_breakdown($conn, $gcn_key);
	return $row;
}