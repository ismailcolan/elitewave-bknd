<?php

require_once __DIR__ . '/gstr1_collect_vouchers.php';
require_once __DIR__ . '/gstr1_settings.php';
require_once __DIR__ . '/billing_receipt_functions.php';

/**
 * Classify every voucher in the period for one filer GSTIN.
 * Returns array(vouchers, by_type, counts, filing_meta, context, error?)
 */
function gstr1_classify_period($conn, $gstin, $from_date, $to_date)
{
	$period = gstr1_parse_period($from_date, $to_date);
	if (!empty($period['error'])) {
		return array('error' => $period['error']);
	}
	$ctx = gstr1_company_context($conn, $gstin);
	if (!empty($ctx['error'])) {
		return array('error' => $ctx['error']);
	}
	$threshold = gstr1_b2c_large_threshold($conn);
	$raw = gstr1_collect_vouchers($conn, $period);
	$classified = array();
	$catalog = gstr1_voucher_type_catalog();

	foreach ($raw as $v) {
		$classified[] = gstr1_classify_one($conn, $v, $ctx, $threshold);
	}

	$counts = gstr1_classification_counts($classified);
	$by_type = gstr1_statistics_matrix($classified, $catalog);
	$filing = gstr1_filing_meta($conn, $ctx['gstin'], $period['from_mysql'], $period['to_mysql']);

	return array(
		'context' => $ctx,
		'period' => $period,
		'vouchers' => $classified,
		'counts' => $counts,
		'statistics' => $by_type,
		'filing' => $filing,
		'b2c_threshold' => $threshold,
	);
}

function gstr1_classify_one($conn, array $v, array $ctx, $b2c_threshold)
{
	$type = $v['voucher_type_key'];
	$v['bucket'] = '';
	$v['bucket_label'] = '';
	$v['reason'] = '';
	$v['gstr_section'] = '';
	$v['exception_level'] = '';
	$v['exception_fields'] = array();
	$v['fix_url'] = '';
	$v['pos_state_code'] = '';
	$v['supply_type'] = '';
	$v['registration_bucket'] = '';

	if (in_array($type, array('quotation', 'proforma_invoice'), true)) {
		$v['bucket'] = 'excluded';
		$v['bucket_label'] = 'Excluded';
		$v['reason'] = 'Quotation / proforma — not counted';
		return $v;
	}

	if (in_array($type, array('booking', 'delivery_note'), true)) {
		$v['bucket'] = 'not_relevant';
		$v['bucket_label'] = 'Not relevant';
		$v['reason'] = 'inventory';
		$v['not_relevant_group'] = 'inventory';
		$v['not_relevant_subgroup'] = $type;
		$v['fix_url'] = gstr1_voucher_readonly_url($v);
		return $v;
	}

	if (in_array($type, array('purchase', 'taxable_purchase', 'rcm_purchase'), true)) {
		$v['bucket'] = 'not_relevant';
		$v['bucket_label'] = 'Not relevant';
		$v['reason'] = 'belongs_to_another_return';
		$v['not_relevant_group'] = 'other_returns';
		$v['not_relevant_subgroup'] = $type;
		$v['fix_url'] = gstr1_voucher_readonly_url($v);
		return $v;
	}

	if (in_array($type, array('receipt', 'payment'), true)) {
		$adv = gstr1_receipt_is_advance($conn, $v);
		if ($adv) {
			return gstr1_classify_advance_receipt($v, $ctx);
		}
		$v['bucket'] = 'not_relevant';
		$v['bucket_label'] = 'Not relevant';
		$v['reason'] = 'other_accounting';
		$v['not_relevant_group'] = 'accounting';
		$v['not_relevant_subgroup'] = $type === 'receipt' ? 'receipt_voucher' : 'payment_voucher';
		$v['fix_url'] = gstr1_voucher_readonly_url($v);
		return $v;
	}

	if ($type === 'journal') {
		$v['bucket'] = 'not_relevant';
		$v['bucket_label'] = 'Not relevant';
		$v['reason'] = 'other_accounting';
		$v['not_relevant_group'] = 'accounting';
		$v['not_relevant_subgroup'] = 'journal';
		if (gstr1_journal_touches_gst_ledger($conn, $v)) {
			$v['bucket'] = 'uncertain';
			$v['bucket_label'] = 'Uncertain';
			$v['reason'] = 'correction_needed';
			$v['exception_level'] = 'uncertain';
			$v['exception_fields'][] = array('field' => 'journal_gst_ledger', 'message' => 'Journal touches GST ledger');
		}
		$v['fix_url'] = gstr1_voucher_readonly_url($v);
		return $v;
	}

	if ($type === 'debit_note') {
		$v['bucket'] = 'not_relevant';
		$v['bucket_label'] = 'Not relevant';
		$v['reason'] = 'other_accounting';
		$v['not_relevant_group'] = 'accounting';
		$v['not_relevant_subgroup'] = 'debit_note';
		$v['fix_url'] = gstr1_voucher_readonly_url($v);
		return $v;
	}

	if (in_array($type, array('gta_booking_gross_receipt', 'gst_booking_gross_receipt', 'credit_note'), true)) {
		if (!empty($v['cancelled'])) {
			$v['bucket'] = 'not_relevant';
			$v['bucket_label'] = 'Not relevant';
			$v['reason'] = 'cancelled_supply';
			$v['not_relevant_group'] = 'accounting';
			$v['not_relevant_subgroup'] = 'sales';
			$v['fix_url'] = gstr1_voucher_readonly_url($v);
			return $v;
		}
		return gstr1_classify_outward_supply($conn, $v, $ctx, $b2c_threshold);
	}

	$v['bucket'] = 'not_relevant';
	$v['bucket_label'] = 'Not relevant';
	$v['reason'] = 'other_accounting';
	$v['not_relevant_group'] = 'accounting';
	$v['not_relevant_subgroup'] = 'sales';
	$v['fix_url'] = gstr1_voucher_readonly_url($v);
	return $v;
}

function gstr1_classify_outward_supply($conn, array $v, array $ctx, $b2c_threshold)
{
	$gstin = $v['party_gstin'];
	$reg = $v['registration_type'];
	$reg_bucket = gstr1_registration_bucket($reg, $gstin);
	$v['registration_bucket'] = $reg_bucket;

	$pos_code = gstr1_derive_state_code($gstin, $v['client_state_id'], $conn);
	$v['pos_state_code'] = $pos_code;
	$v['supply_type'] = gstr1_supply_type($ctx['state_code'], $pos_code);

	$exceptions = array();
	if ($reg_bucket === 'REGISTERED') {
		if ($reg === '') {
			$exceptions[] = array('field' => 'gst_registration_type', 'message' => 'Registration type not set');
		}
		if ($gstin === '' || !gst_tax_is_valid_gstin($gstin)) {
			$exceptions[] = array('field' => 'gst_no', 'message' => 'Missing or invalid GSTIN');
		}
		if ($pos_code !== '' && $gstin !== '' && substr($gstin, 0, 2) !== $pos_code) {
			$exceptions[] = array('field' => 'state', 'message' => 'State code contradicts GSTIN');
		}
	}
	if ($pos_code === '') {
		$exceptions[] = array('field' => 'place_of_supply', 'message' => 'Missing place of supply');
	}

	$sac = gstr1_sac_for_invoice_series($v['invoice_series'] ?: 'GTA');
	if (empty($sac['sac'])) {
		$exceptions[] = array('field' => 'sac', 'message' => 'Missing SAC on invoice line');
	}

	if ($v['voucher_type_key'] === 'credit_note' && (int) $v['against_invoice_id'] <= 0) {
		$exceptions[] = array('field' => 'against_invoice', 'message' => 'Credit note missing original invoice reference');
	}

	$rate = ($v['taxable'] > 0.009) ? round($v['gst'] / $v['taxable'], 4) : 0;
	$expected_split = ($v['supply_type'] === 'inter') ? 'igst' : 'cgst_sgst';
	if ($v['taxable'] > 0.009 && $v['gst'] > 0.009) {
		if ($expected_split === 'igst' && $v['igst'] <= 0.009) {
			$exceptions[] = array('field' => 'igst_amount', 'message' => 'Supply type contradicts tax split');
		}
		if ($expected_split === 'cgst_sgst' && ($v['cgst'] <= 0.009 || $v['sgst'] <= 0.009)) {
			$exceptions[] = array('field' => 'cgst_sgst', 'message' => 'Supply type contradicts tax split');
		}
		if (abs($v['cgst'] - $v['sgst']) > 0.02) {
			$exceptions[] = array('field' => 'sgst_amount', 'message' => 'CGST not equal to SGST');
		}
	}

	$nil_rated = ($v['gst'] <= 0.009 && $v['taxable'] > 0.009);
	$section = '';
	if ($nil_rated) {
		$section = '8A';
	} elseif ($reg_bucket === 'REGISTERED') {
		$section = '4A';
	} else {
		$taxable_abs = abs($v['taxable']);
		$inter = ($v['supply_type'] === 'inter');
		if ($inter && $taxable_abs >= $b2c_threshold) {
			$section = '5A';
		} else {
			$section = '7';
		}
	}

	if ($v['voucher_type_key'] === 'credit_note') {
		$section = '9B';
	}

	$v['gstr_section'] = $section;
	$sac_info = gstr1_sac_for_invoice_series($v['invoice_series'] ?: 'GTA');
	$v['sac'] = $sac_info['sac'];
	$v['sac_description'] = $sac_info['description'];
	$v['tax_rate_pct'] = round($rate * 100, 2);

	$pending = array();
	$uncertain = array();
	foreach ($exceptions as $ex) {
		if (in_array($ex['field'], array('igst_amount', 'cgst_sgst', 'sgst_amount'), true)) {
			$pending[] = $ex;
		} elseif (in_array($ex['field'], array('gst_no', 'state'), true) && strpos($ex['message'], 'contradict') !== false) {
			$uncertain[] = $ex;
		} else {
			$pending[] = $ex;
		}
	}

	$v['bucket'] = 'included';
	$v['bucket_label'] = 'Included in return';
	if (!empty($uncertain)) {
		$v['bucket'] = 'uncertain';
		$v['bucket_label'] = 'Uncertain';
		$v['reason'] = 'correction_needed';
		$v['exception_level'] = 'uncertain';
		$v['exception_fields'] = $uncertain;
	} elseif (!empty($pending)) {
		$v['included_sub'] = 'action_pending';
		$v['reason'] = 'action_pending';
		$v['exception_level'] = 'action_pending';
		$v['exception_fields'] = $pending;
	} else {
		$v['included_sub'] = 'no_action';
		$v['reason'] = 'no_action_required';
	}

	if ($v['voucher_type_key'] === 'credit_note') {
		$v['taxable'] = -1 * abs($v['taxable']);
		$v['cgst'] = -1 * abs($v['cgst']);
		$v['sgst'] = -1 * abs($v['sgst']);
		$v['igst'] = -1 * abs($v['igst']);
		$v['gst'] = -1 * abs($v['gst']);
		$v['invoice_amount'] = -1 * abs($v['invoice_amount']);
	}

	$v['fix_url'] = gstr1_voucher_readonly_url($v);
	if ($v['voucher_type_key'] === 'credit_note' && (int) $v['against_invoice_id'] > 0) {
		$v['fix_url'] = 'create_invoice.php?billing_invoice_id=' . (int) $v['against_invoice_id'];
	} elseif (in_array($v['voucher_type_key'], array('gta_booking_gross_receipt', 'gst_booking_gross_receipt'), true)) {
		$v['fix_url'] = 'create_invoice.php?billing_invoice_id=' . (int) $v['doc_id'];
	}
	return $v;
}

function gstr1_classify_advance_receipt(array $v, array $ctx)
{
	$v['bucket'] = 'included';
	$v['bucket_label'] = 'Included in return';
	$v['gstr_section'] = '11A';
	$v['included_sub'] = 'no_action';
	$v['reason'] = 'advance_receipt';
	$v['fix_url'] = gstr1_voucher_readonly_url($v);
	return $v;
}

function gstr1_receipt_is_advance($conn, array $v)
{
	if ($v['voucher_type_key'] !== 'receipt') {
		return false;
	}
	$rtype = strtolower((string) ($v['receipt_type'] ?? ''));
	if ($rtype === '' || strpos($rtype, 'advance') === false) {
		return false;
	}
	$id = (int) $v['doc_id'];
	if ($id <= 0) {
		return true;
	}
	ensure_billing_receipt_tables($conn);
	$esc = mysqli_real_escape_string($conn, (string) $id);
	$q = mysqli_query($conn, "SELECT COUNT(*) c FROM billing_receipt_lines WHERE billing_receipt_id='$esc' AND billing_invoice_id>0");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return ((int) ($row['c'] ?? 0)) === 0;
	}
	return true;
}

function gstr1_journal_touches_gst_ledger($conn, array $v)
{
	return false;
}

function gstr1_classification_counts(array $vouchers)
{
	$total = 0;
	$included = 0;
	$included_ok = 0;
	$included_pending = 0;
	$not_relevant = 0;
	$uncertain = 0;
	$excluded = 0;
	foreach ($vouchers as $v) {
		if (($v['bucket'] ?? '') === 'excluded') {
			$excluded++;
			continue;
		}
		$total++;
		$b = $v['bucket'] ?? '';
		if ($b === 'included') {
			$included++;
			if (($v['included_sub'] ?? '') === 'action_pending' || ($v['exception_level'] ?? '') === 'action_pending') {
				$included_pending++;
			} else {
				$included_ok++;
			}
		} elseif ($b === 'not_relevant') {
			$not_relevant++;
		} elseif ($b === 'uncertain') {
			$uncertain++;
		}
	}
	return array(
		'total' => $total,
		'included' => $included,
		'included_no_action' => $included_ok,
		'included_action_pending' => $included_pending,
		'not_relevant' => $not_relevant,
		'uncertain' => $uncertain,
		'excluded' => $excluded,
	);
}

function gstr1_statistics_matrix(array $vouchers, array $catalog)
{
	$matrix = array();
	foreach ($catalog as $key => $label) {
		$matrix[$key] = array(
			'voucher_type' => $key,
			'label' => $label,
			'total' => 0,
			'included_action_pending' => 0,
			'included_no_action' => 0,
			'not_relevant' => 0,
			'uncertain' => 0,
		);
	}
	foreach ($vouchers as $v) {
		if (($v['bucket'] ?? '') === 'excluded') {
			continue;
		}
		$key = $v['voucher_type_key'] ?? '';
		if (!isset($matrix[$key])) {
			$matrix[$key] = array(
				'voucher_type' => $key,
				'label' => $key,
				'total' => 0,
				'included_action_pending' => 0,
				'included_no_action' => 0,
				'not_relevant' => 0,
				'uncertain' => 0,
			);
		}
		$matrix[$key]['total']++;
		$b = $v['bucket'] ?? '';
		if ($b === 'included') {
			if (($v['included_sub'] ?? '') === 'action_pending' || ($v['exception_level'] ?? '') === 'action_pending') {
				$matrix[$key]['included_action_pending']++;
			} else {
				$matrix[$key]['included_no_action']++;
			}
		} elseif ($b === 'not_relevant') {
			$matrix[$key]['not_relevant']++;
		} elseif ($b === 'uncertain') {
			$matrix[$key]['uncertain']++;
		}
	}
	return array_values($matrix);
}