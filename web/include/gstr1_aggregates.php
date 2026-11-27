<?php

require_once __DIR__ . '/gstr1_classification_service.php';

function gstr1_return_sections_template()
{
	return array(
		array('id' => '4A', 'title' => '4A — B2B invoices', 'group' => 'b2b'),
		array('id' => '4B', 'title' => '4B — B2B reverse charge', 'group' => 'b2b'),
		array('id' => '4C', 'title' => '4C — B2B amendments', 'group' => 'b2b'),
		array('id' => '6B', 'title' => '6B — B2B exports (SEZ)', 'group' => 'b2b'),
		array('id' => '6C', 'title' => '6C — B2B deemed exports', 'group' => 'b2b'),
		array('id' => '5A', 'title' => '5A — B2C large (inter-state)', 'group' => 'b2c_large'),
		array('id' => '5B', 'title' => '5B — B2C large amendments', 'group' => 'b2c_large'),
		array('id' => '6A', 'title' => '6A — Exports', 'group' => 'exports'),
		array('id' => '9B', 'title' => '9B — Credit / debit notes', 'group' => 'cdnr'),
		array('id' => '9A', 'title' => '9A — Amendments to CDNR', 'group' => 'cdnr'),
		array('id' => '9C', 'title' => '9C — Amendments to CDNUR', 'group' => 'cdnr'),
		array('id' => '10', 'title' => '10 — Amendments (consolidated)', 'group' => 'amendments'),
		array('id' => '7', 'title' => '7 — B2C others (state-wise)', 'group' => 'b2c_small'),
		array('id' => '8A', 'title' => '8A — Nil rated', 'group' => 'nil'),
		array('id' => '8B', 'title' => '8B — Exempted', 'group' => 'nil'),
		array('id' => '8C', 'title' => '8C — Non-GST', 'group' => 'nil'),
		array('id' => '8D', 'title' => '8D — UIN holders', 'group' => 'nil'),
		array('id' => '11A', 'title' => '11A — Advances received', 'group' => 'advances'),
		array('id' => '11B', 'title' => '11B — Advance adjustments', 'group' => 'advances'),
		array('id' => '12', 'title' => '12 — HSN summary', 'group' => 'hsn'),
	);
}

function gstr1_aggregate_return_view(array $vouchers)
{
	$tpl = gstr1_return_sections_template();
	$map = array();
	foreach ($tpl as $row) {
		$map[$row['id']] = array_merge($row, array(
			'voucher_count' => 0,
			'taxable' => 0.0,
			'igst' => 0.0,
			'cgst' => 0.0,
			'sgst' => 0.0,
			'cess' => 0.0,
			'tax' => 0.0,
			'invoice_amount' => 0.0,
			'drillable' => in_array($row['id'], array('4A', '12'), true),
		));
	}

	foreach ($vouchers as $v) {
		if (($v['bucket'] ?? '') !== 'included' || ($v['exception_level'] ?? '') === 'uncertain') {
			continue;
		}
		$sec = $v['gstr_section'] ?? '';
		if ($sec === '' || !isset($map[$sec])) {
			continue;
		}
		$map[$sec]['voucher_count']++;
		$map[$sec]['taxable'] += (float) $v['taxable'];
		$map[$sec]['igst'] += (float) $v['igst'];
		$map[$sec]['cgst'] += (float) $v['cgst'];
		$map[$sec]['sgst'] += (float) $v['sgst'];
		$map[$sec]['cess'] += (float) $v['cess'];
		$map[$sec]['tax'] += (float) $v['gst'];
		$map[$sec]['invoice_amount'] += (float) $v['invoice_amount'];
	}

	// HSN row mirrors outward taxable totals (sections 4A primary)
	$b2b_keys = array('4A', '4B', '6B', '6C', '5A', '9B');
	$hsn_count = 0;
	foreach ($vouchers as $v) {
		if (($v['bucket'] ?? '') !== 'included') {
			continue;
		}
		if (!in_array($v['gstr_section'] ?? '', array_merge($b2b_keys, array('7', '5A')), true)) {
			continue;
		}
		if (($v['voucher_type_key'] ?? '') === 'credit_note') {
			continue;
		}
		if (abs((float) $v['taxable']) > 0.009) {
			$hsn_count++;
		}
	}
	$map['12']['voucher_count'] = $hsn_count;
	foreach (array('taxable', 'igst', 'cgst', 'sgst', 'cess', 'tax', 'invoice_amount') as $f) {
		$map['12'][$f] = $map['4A'][$f] + $map['5A'][$f] + $map['7'][$f] + $map['9B'][$f];
	}
	$map['12']['drillable'] = true;

	$rows = array();
	$tot = array('voucher_count' => 0, 'taxable' => 0.0, 'igst' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0, 'cess' => 0.0, 'tax' => 0.0, 'invoice_amount' => 0.0);
	foreach ($tpl as $row) {
		$id = $row['id'];
		$item = $map[$id];
		$item['particulars'] = $item['title'];
		foreach (array('taxable', 'igst', 'cgst', 'sgst', 'cess', 'tax', 'invoice_amount') as $f) {
			$item[$f . '_fmt'] = gstr1_money($item[$f]);
		}
		if ($id !== '12') {
			$tot['voucher_count'] += (int) $item['voucher_count'];
			foreach (array('taxable', 'igst', 'cgst', 'sgst', 'cess', 'tax', 'invoice_amount') as $f) {
				$tot[$f] += (float) $item[$f];
			}
		}
		$rows[] = $item;
	}
	foreach (array('taxable', 'igst', 'cgst', 'sgst', 'cess', 'tax', 'invoice_amount') as $f) {
		$tot[$f . '_fmt'] = gstr1_money($tot[$f]);
	}

	return array('rows' => $rows, 'total' => $tot);
}

function gstr1_aggregate_b2b(array $vouchers)
{
	$parties = array();
	foreach ($vouchers as $v) {
		if (($v['bucket'] ?? '') !== 'included') {
			continue;
		}
		if (!in_array($v['gstr_section'] ?? '', array('4A', '4B', '6B', '6C'), true)) {
			continue;
		}
		if (($v['voucher_type_key'] ?? '') === 'credit_note') {
			continue;
		}
		$key = $v['party_gstin'] !== '' ? $v['party_gstin'] : ('id:' . ($v['doc_id'] ?? 0));
		if (!isset($parties[$key])) {
			$parties[$key] = array(
				'gstin' => $v['party_gstin'],
				'party_name' => $v['party_name'],
				'voucher_count' => 0,
				'taxable' => 0.0,
				'igst' => 0.0,
				'cgst' => 0.0,
				'sgst' => 0.0,
				'cess' => 0.0,
				'tax' => 0.0,
				'invoice_amount' => 0.0,
			);
		}
		$parties[$key]['voucher_count']++;
		foreach (array('taxable', 'igst', 'cgst', 'sgst', 'cess', 'invoice_amount') as $f) {
			$parties[$key][$f] += (float) $v[$f];
		}
		$parties[$key]['tax'] += (float) ($v['gst'] ?? 0);
	}
	$rows = array_values($parties);
	usort($rows, function ($a, $b) {
		return strcmp($a['party_name'], $b['party_name']);
	});
	$tot = array('voucher_count' => 0, 'taxable' => 0.0, 'igst' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0, 'cess' => 0.0, 'tax' => 0.0, 'invoice_amount' => 0.0);
	foreach ($rows as &$r) {
		$tot['voucher_count'] += (int) $r['voucher_count'];
		foreach (array('taxable', 'igst', 'cgst', 'sgst', 'cess', 'tax', 'invoice_amount') as $f) {
			$tot[$f] += (float) $r[$f];
			$r[$f . '_fmt'] = gstr1_money($r[$f]);
		}
	}
	unset($r);
	foreach (array('taxable', 'igst', 'cgst', 'sgst', 'cess', 'tax', 'invoice_amount') as $f) {
		$tot[$f . '_fmt'] = gstr1_money($tot[$f]);
	}
	return array('rows' => $rows, 'total' => $tot);
}

function gstr1_aggregate_hsn(array $vouchers)
{
	$groups = array('b2b' => array(), 'b2c' => array());
	foreach ($vouchers as $v) {
		if (($v['bucket'] ?? '') !== 'included') {
			continue;
		}
		$sec = $v['gstr_section'] ?? '';
		$is_b2b = in_array($sec, array('4A', '4B', '6B', '6C', '9B'), true);
		$is_b2c = in_array($sec, array('5A', '7'), true);
		if (!$is_b2b && !$is_b2c) {
			continue;
		}
		$bucket = $is_b2b ? 'b2b' : 'b2c';
		$sac = $v['sac'] ?? gstr1_sac_for_invoice_series($v['invoice_series'] ?: 'GTA')['sac'];
		$rate = (float) ($v['tax_rate_pct'] ?? 0);
		$key = $sac . '|' . $rate;
		if (!isset($groups[$bucket][$key])) {
			$info = gstr1_sac_for_invoice_series($v['invoice_series'] ?: 'GTA');
			$groups[$bucket][$key] = array(
				'sac' => $sac,
				'description' => $info['description'],
				'uqc' => $info['uqc'],
				'quantity' => 0,
				'total_amount' => 0.0,
				'tax_rate' => $rate,
				'taxable' => 0.0,
				'igst' => 0.0,
				'cgst' => 0.0,
				'sgst' => 0.0,
				'cess' => 0.0,
				'tax' => 0.0,
			);
		}
		$groups[$bucket][$key]['quantity']++;
		$groups[$bucket][$key]['taxable'] += (float) $v['taxable'];
		$groups[$bucket][$key]['total_amount'] += (float) $v['invoice_amount'];
		$groups[$bucket][$key]['igst'] += (float) $v['igst'];
		$groups[$bucket][$key]['cgst'] += (float) $v['cgst'];
		$groups[$bucket][$key]['sgst'] += (float) $v['sgst'];
		$groups[$bucket][$key]['cess'] += (float) $v['cess'];
		$groups[$bucket][$key]['tax'] += (float) $v['gst'];
	}

	$out = array();
	foreach (array('b2b' => 'B2B supplies', 'b2c' => 'B2C supplies') as $k => $title) {
		$rows = array_values($groups[$k]);
		$tot = array('quantity' => 0, 'total_amount' => 0.0, 'taxable' => 0.0, 'igst' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0, 'cess' => 0.0, 'tax' => 0.0);
		foreach ($rows as &$r) {
			$tot['quantity'] += (int) $r['quantity'];
			foreach (array('total_amount', 'taxable', 'igst', 'cgst', 'sgst', 'cess', 'tax') as $f) {
				$tot[$f] += (float) $r[$f];
				$r[$f . '_fmt'] = gstr1_money($r[$f]);
			}
			$r['tax_rate_fmt'] = $r['tax_rate'] > 0 ? gstr1_money($r['tax_rate'], false) . '%' : '';
		}
		unset($r);
		foreach (array('total_amount', 'taxable', 'igst', 'cgst', 'sgst', 'cess', 'tax') as $f) {
			$tot[$f . '_fmt'] = gstr1_money($tot[$f]);
		}
		$out[$k] = array('title' => $title, 'rows' => $rows, 'total' => $tot);
	}
	return $out;
}

function gstr1_not_relevant_summary(array $vouchers)
{
	$inv = 0;
	$acct = array('journal' => 0, 'payment_voucher' => 0, 'receipt_voucher' => 0, 'sales' => 0, 'debit_note' => 0);
	$other_ret = 0;
	foreach ($vouchers as $v) {
		if (($v['bucket'] ?? '') !== 'not_relevant') {
			continue;
		}
		$grp = $v['not_relevant_group'] ?? '';
		if ($grp === 'inventory') {
			$inv++;
		} elseif ($grp === 'other_returns') {
			$other_ret++;
		} elseif ($grp === 'accounting') {
			$sub = $v['not_relevant_subgroup'] ?? 'sales';
			if (!isset($acct[$sub])) {
				$acct[$sub] = 0;
			}
			$acct[$sub]++;
		}
	}
	$other_txn = array_sum($acct);
	return array(
		'non_gst_total' => $inv + $other_txn,
		'inventory' => $inv,
		'other_transactions' => $other_txn,
		'other_transactions_detail' => $acct,
		'other_returns' => $other_ret,
		'total' => $inv + $other_txn + $other_ret,
	);
}

function gstr1_filter_vouchers(array $vouchers, array $filters)
{
	$out = array();
	foreach ($vouchers as $v) {
		if (($v['bucket'] ?? '') === 'excluded') {
			continue;
		}
		if (!empty($filters['bucket']) && ($v['bucket'] ?? '') !== $filters['bucket']) {
			continue;
		}
		if (!empty($filters['included_sub'])) {
			if (($v['included_sub'] ?? '') !== $filters['included_sub']) {
				continue;
			}
		}
		if (!empty($filters['voucher_type']) && ($v['voucher_type_key'] ?? '') !== $filters['voucher_type']) {
			continue;
		}
		if (!empty($filters['not_relevant_group']) && ($v['not_relevant_group'] ?? '') !== $filters['not_relevant_group']) {
			continue;
		}
		if (!empty($filters['not_relevant_subgroup']) && ($v['not_relevant_subgroup'] ?? '') !== $filters['not_relevant_subgroup']) {
			continue;
		}
		if (!empty($filters['party_gstin']) && strcasecmp($v['party_gstin'] ?? '', $filters['party_gstin']) !== 0) {
			continue;
		}
		if (!empty($filters['section']) && ($v['gstr_section'] ?? '') !== $filters['section']) {
			continue;
		}
		if (!empty($filters['exception_only'])) {
			$is_pending = ($v['included_sub'] ?? '') === 'action_pending';
			$is_unc = ($v['bucket'] ?? '') === 'uncertain';
			if (!$is_pending && !$is_unc) {
				continue;
			}
		}
		$out[] = $v;
	}
	return $out;
}