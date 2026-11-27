<?php

require_once __DIR__ . '/client_tally_helpers.php';
require_once __DIR__ . '/gst_tax_functions.php';
require_once __DIR__ . '/gst_register_functions.php';

/**
 * Central registration-type mapping for GSTR-1 (extend here only).
 */
function gstr1_registration_bucket($registration_type, $gstin = '')
{
	$reg = trim((string) $registration_type);
	$gstin = gst_tax_normalize_gstin($gstin);
	if ($reg === '' && $gstin === '') {
		return 'UNREGISTERED';
	}
	if ($reg === '' && $gstin !== '') {
		return 'REGISTERED';
	}
	$unreg = array('Unregistered', 'Consumer');
	if (in_array($reg, $unreg, true)) {
		return 'UNREGISTERED';
	}
	if ($reg === '' || $gstin === '') {
		if (in_array($reg, $unreg, true)) {
			return 'UNREGISTERED';
		}
	}
	$registered = array('Regular', 'Composition', 'SEZ', 'Deemed Export');
	if (in_array($reg, $registered, true)) {
		return 'REGISTERED';
	}
	if ($gstin !== '' && gst_tax_is_valid_gstin($gstin)) {
		return 'REGISTERED';
	}
	if ($gstin === '') {
		return 'UNREGISTERED';
	}
	return 'REGISTERED';
}

function gstr1_parse_period($from_date, $to_date)
{
	$from_date = trim((string) $from_date);
	$to_date = trim((string) $to_date);
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
		'period_label' => $from_date . ' — ' . $to_date,
	);
}

function gstr1_company_context($conn, $gstin_filter = '')
{
	$gstin_filter = gst_tax_normalize_gstin($gstin_filter);
	$sql = "SELECT company_id, company_name, gst_no, state, city FROM company WHERE status=0 ORDER BY company_id ASC";
	if ($gstin_filter !== '') {
		$esc = mysqli_real_escape_string($conn, $gstin_filter);
		$sql = "SELECT company_id, company_name, gst_no, state, city FROM company WHERE status=0 AND REPLACE(UPPER(gst_no),' ','')='$esc' LIMIT 1";
	}
	$q = mysqli_query($conn, $sql);
	$row = ($q && mysqli_num_rows($q)) ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return array('error' => 'Company GSTIN not found.');
	}
	$gstin = gst_tax_normalize_gstin($row['gst_no'] ?? '');
	$company_state = (int) ($row['state'] ?? 0);
	$state_code = '';
	if ($gstin !== '' && strlen($gstin) >= 2) {
		$state_code = substr($gstin, 0, 2);
	} elseif ($company_state > 0) {
		$state_code = gstr1_state_gst_code_from_id($conn, $company_state);
	}
	return array(
		'company_id' => (int) ($row['company_id'] ?? 0),
		'legal_name' => trim((string) ($row['company_name'] ?? '')),
		'gstin' => $gstin,
		'state_id' => $company_state,
		'state_code' => $state_code,
		'city' => trim((string) ($row['city'] ?? '')),
	);
}

function gstr1_company_gstin_options($conn)
{
	$out = array();
	$q = mysqli_query($conn, "SELECT gst_no, company_name FROM company WHERE status=0 AND TRIM(gst_no)!='' ORDER BY company_id ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$g = gst_tax_normalize_gstin($row['gst_no'] ?? '');
			if ($g === '') {
				continue;
			}
			$out[] = array(
				'gstin' => $g,
				'label' => $g . ' — ' . trim((string) ($row['company_name'] ?? '')),
			);
		}
	}
	return $out;
}

function gstr1_state_gst_code_from_id($conn, $state_id)
{
	static $cache = array();
	$state_id = (int) $state_id;
	if ($state_id <= 0) {
		return '';
	}
	if (isset($cache[$state_id])) {
		return $cache[$state_id];
	}
	$esc = mysqli_real_escape_string($conn, (string) $state_id);
	$q = mysqli_query($conn, "SELECT state_name FROM state WHERE state_id='$esc' LIMIT 1");
	$name = ($q && ($r = mysqli_fetch_assoc($q))) ? trim((string) ($r['state_name'] ?? '')) : '';
	$map = array(
		'Tamil Nadu' => '33',
		'Maharashtra' => '27',
		'Uttar Pradesh' => '09',
		'Karnataka' => '29',
		'Gujarat' => '24',
		'Delhi' => '07',
		'Haryana' => '06',
		'Kerala' => '32',
		'West Bengal' => '19',
		'Telangana' => '36',
		'Andhra Pradesh' => '37',
	);
	foreach ($map as $n => $code) {
		if (strcasecmp($n, $name) === 0 || stripos($name, $n) !== false) {
			$cache[$state_id] = $code;
			return $code;
		}
	}
	$cache[$state_id] = '';
	return '';
}

function gstr1_derive_state_code($gstin, $client_state_id, $conn)
{
	$gstin = gst_tax_normalize_gstin($gstin);
	if ($gstin !== '' && strlen($gstin) >= 2 && ctype_digit(substr($gstin, 0, 2))) {
		return substr($gstin, 0, 2);
	}
	return gstr1_state_gst_code_from_id($conn, (int) $client_state_id);
}

function gstr1_supply_type($company_state_code, $pos_state_code)
{
	$company_state_code = str_pad(substr(preg_replace('/\D/', '', (string) $company_state_code), 0, 2), 2, '0', STR_PAD_LEFT);
	$pos_state_code = str_pad(substr(preg_replace('/\D/', '', (string) $pos_state_code), 0, 2), 2, '0', STR_PAD_LEFT);
	if ($company_state_code === '00' || $pos_state_code === '00') {
		return '';
	}
	return ($company_state_code === $pos_state_code) ? 'intra' : 'inter';
}

function gstr1_voucher_type_catalog()
{
	return array(
		'booking' => 'Booking',
		'delivery_note' => 'Delivery note',
		'gta_booking_gross_receipt' => 'GTA booking gross receipt',
		'gst_booking_gross_receipt' => 'GST booking gross receipt',
		'credit_note' => 'Credit note',
		'debit_note' => 'Debit note',
		'receipt' => 'Receipt',
		'payment' => 'Payment voucher',
		'journal' => 'Journal voucher',
		'purchase' => 'Purchase',
		'taxable_purchase' => 'Taxable purchase',
		'rcm_purchase' => 'RCM purchase',
		'quotation' => 'Quotation',
		'proforma_invoice' => 'Proforma invoice',
		'sales_no_gst' => 'Sales (no GST effect)',
	);
}

function gstr1_sac_for_invoice_series($series)
{
	$series = strtoupper(trim((string) $series));
	if ($series === 'GST') {
		return array('sac' => '996540', 'description' => 'Courier and cargo transport services', 'uqc' => 'NA');
	}
	return array('sac' => '996511', 'description' => 'Goods transport agency services', 'uqc' => 'NA');
}

function gstr1_money($val, $blank_for_zero = true)
{
	$val = round((float) $val, 2);
	if ($blank_for_zero && abs($val) < 0.005) {
		return '';
	}
	return number_format($val, 2, '.', ',');
}

function gstr1_money_raw($val)
{
	return round((float) $val, 2);
}

function gstr1_build_breadcrumb(array $crumbs)
{
	$out = array();
	foreach ($crumbs as $c) {
		$out[] = array(
			'label' => (string) ($c['label'] ?? ''),
			'href' => isset($c['href']) ? (string) $c['href'] : '',
		);
	}
	return $out;
}

function gstr1_query_params(array $extra = array())
{
	$keys = array('gstin', 'from_date', 'to_date', 'voucher_type', 'bucket', 'section', 'party_gstin', 'source');
	$out = array();
	foreach ($keys as $k) {
		if (isset($_GET[$k]) && $_GET[$k] !== '') {
			$out[$k] = (string) $_GET[$k];
		}
	}
	foreach ($extra as $k => $v) {
		if ($v !== '' && $v !== null) {
			$out[$k] = (string) $v;
		}
	}
	return $out;
}

function gstr1_url($page, array $params = array())
{
	$page = ltrim((string) $page, '/');
	return $page . '?' . http_build_query($params);
}

function gstr1_voucher_readonly_url(array $voucher)
{
	$type = (string) ($voucher['voucher_type_key'] ?? '');
	$id = (int) ($voucher['doc_id'] ?? 0);
	switch ($type) {
		case 'gta_booking_gross_receipt':
		case 'gst_booking_gross_receipt':
			return $id > 0 ? 'create_invoice.php?billing_invoice_id=' . $id : '';
		case 'credit_note':
			return $id > 0 ? 'create_credit_note.php?billing_note_id=' . $id : '';
		case 'debit_note':
			return $id > 0 ? 'create_debit_note.php?billing_note_id=' . $id : '';
		case 'receipt':
			return $id > 0 ? 'create_receipt.php?billing_receipt_id=' . $id : '';
		case 'payment':
		case 'journal':
		case 'taxable_purchase':
		case 'rcm_purchase':
			return $id > 0 ? 'expense_general.php?general_expense_id=' . $id : '';
		case 'purchase':
			return $id > 0 ? 'expense_bill.php?bill_id=' . $id : '';
		case 'booking':
		case 'delivery_note':
			$trans = (string) ($voucher['trans_table'] ?? '');
			$tid = (int) ($voucher['transaction_id'] ?? 0);
			if ($trans !== '' && $tid > 0) {
				return 'transactions.php?trans_table=' . urlencode($trans) . '&transaction_id=' . $tid;
			}
			return '';
		default:
			return (string) ($voucher['view_url'] ?? '');
	}
}