<?php

/**
 * Tally-style ledger / credit settings on client master (Sundry Debtors).
 */
function client_tally_ensure_schema($conn)
{
	$columns = array(
		'client_alias' => "VARCHAR(120) NOT NULL DEFAULT '' AFTER client_company_name",
		'ledger_under' => "VARCHAR(80) NOT NULL DEFAULT 'Sundry Debtors' AFTER client_alias",
		'bill_by_bill' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER ledger_under",
		'credit_period_days' => "INT(11) NOT NULL DEFAULT 0 AFTER bill_by_bill",
		'check_credit_days' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER credit_period_days",
		'ledger_start_date' => "VARCHAR(20) DEFAULT '' AFTER check_credit_days",
		'gst_registration_type' => "VARCHAR(24) NOT NULL DEFAULT 'Regular' AFTER pan_no",
		'country' => "VARCHAR(60) NOT NULL DEFAULT 'India' AFTER gst_registration_type",
		'station' => "VARCHAR(80) NOT NULL DEFAULT '' AFTER country",
		'follower_name' => "VARCHAR(120) NOT NULL DEFAULT '' AFTER station",
		'set_rate' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER follower_name",
		'set_min_weight' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER set_rate",
		'service_tax_applicable' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER set_min_weight",
		'fuel_charge_calc' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER service_tax_applicable",
		'disable_invoice_roundoff' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER fuel_charge_calc",
		'enable_doc_charges' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER disable_invoice_roundoff",
		'doc_charge_amount' => "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER enable_doc_charges",
		'enable_cartage_charges' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER doc_charge_amount",
		'enable_other_charges' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER enable_cartage_charges",
		'opening_balance' => "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER enable_other_charges",
		'opening_balance_as_on' => "VARCHAR(20) DEFAULT '' AFTER opening_balance",
	);
	foreach ($columns as $col => $def) {
		$chk = @mysqli_query($conn, "SHOW COLUMNS FROM client LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			@mysqli_query($conn, "ALTER TABLE client ADD COLUMN $col $def");
		}
	}
}

/** Columns shown on client form and persisted from POST. */
function client_tally_form_columns()
{
	return array(
		'client_alias',
		'ledger_under',
		'bill_by_bill',
		'credit_period_days',
		'check_credit_days',
		'ledger_start_date',
		'gst_registration_type',
		'country',
		'station',
		'follower_name',
		'opening_balance',
		'opening_balance_as_on',
	);
}

function client_tally_defaults()
{
	return array(
		'client_alias' => '',
		'ledger_under' => 'Sundry Debtors',
		'bill_by_bill' => 1,
		'credit_period_days' => 0,
		'check_credit_days' => 0,
		'ledger_start_date' => '',
		'gst_registration_type' => 'Regular',
		'country' => 'India',
		'station' => '',
		'follower_name' => '',
		'opening_balance' => '0.00',
		'opening_balance_as_on' => '',
	);
}

function client_tally_ledger_under_options()
{
	return array(
		'Sundry Debtors',
		'Sundry Creditors',
	);
}

function client_tally_gst_registration_options()
{
	return array('Regular', 'Composition', 'Unregistered', 'Consumer', 'SEZ', 'Deemed Export');
}

function client_tally_yesno_from_post($value, $default = 0)
{
	if ($value === null || $value === '') {
		return (int) $default ? 1 : 0;
	}
	$v = strtolower(trim((string) $value));
	if ($v === 'yes' || $v === '1' || $v === 'y' || $v === 'true') {
		return 1;
	}
	if ($v === 'no' || $v === '0' || $v === 'n' || $v === 'false') {
		return 0;
	}
	return (int) $default ? 1 : 0;
}

function client_tally_from_post(array $post)
{
	$defaults = client_tally_defaults();
	$ledger_under = trim((string) ($post['ledger_under'] ?? $defaults['ledger_under']));
	$allowed_under = client_tally_ledger_under_options();
	if (!in_array($ledger_under, $allowed_under, true)) {
		$ledger_under = $defaults['ledger_under'];
	}
	$gst_reg = trim((string) ($post['gst_registration_type'] ?? $defaults['gst_registration_type']));
	if (!in_array($gst_reg, client_tally_gst_registration_options(), true)) {
		$gst_reg = $defaults['gst_registration_type'];
	}
	$credit_days = (int) ($post['credit_period_days'] ?? 0);
	if ($credit_days < 0) {
		$credit_days = 0;
	}
	$open_bal = round((float) str_replace(',', '', (string) ($post['opening_balance'] ?? 0)), 2);
	$country = trim((string) ($post['country'] ?? ''));
	if ($country === '') {
		$country = $defaults['country'];
	}

	$all = array(
		'client_alias' => trim((string) ($post['client_alias'] ?? '')),
		'ledger_under' => $ledger_under,
		'bill_by_bill' => client_tally_yesno_from_post($post['bill_by_bill'] ?? null, (int) $defaults['bill_by_bill']),
		'credit_period_days' => $credit_days,
		'check_credit_days' => client_tally_yesno_from_post($post['check_credit_days'] ?? null, 0),
		'ledger_start_date' => trim((string) ($post['ledger_start_date'] ?? '')),
		'gst_registration_type' => $gst_reg,
		'country' => $country,
		'station' => trim((string) ($post['station'] ?? '')),
		'follower_name' => trim((string) ($post['follower_name'] ?? '')),
		'opening_balance' => number_format($open_bal, 2, '.', ''),
		'opening_balance_as_on' => trim((string) ($post['opening_balance_as_on'] ?? '')),
	);

	$out = array();
	foreach (client_tally_form_columns() as $col) {
		$out[$col] = $all[$col];
	}
	return $out;
}

function client_tally_sql_assignments($conn, array $data)
{
	$parts = array();
	foreach ($data as $col => $val) {
		$esc = mysqli_real_escape_string($conn, (string) $val);
		$parts[] = "`$col`='$esc'";
	}
	return implode(', ', $parts);
}

function client_tally_yesno_options($selected, $default_yes = false)
{
	$sel = client_tally_yesno_from_post($selected, $default_yes ? 1 : 0);
	$html = '';
	foreach (array(1 => 'Yes', 0 => 'No') as $val => $label) {
		$html .= '<option value="' . ($val ? 'Yes' : 'No') . '"' . ($sel === $val ? ' selected' : '') . '>' . $label . '</option>';
	}
	return $html;
}

function client_tally_merge_row(array $row)
{
	return array_merge(client_tally_defaults(), $row);
}