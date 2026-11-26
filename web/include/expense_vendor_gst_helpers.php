<?php

require_once __DIR__ . '/gst_register_functions.php';
require_once __DIR__ . '/gst_tax_functions.php';

/**
 * Vendor purchase bill & general voucher — GST component storage (ITC / registers).
 */
function expense_vendor_gst_ensure_bill_columns($conn)
{
	$cols = array(
		'gst_percent' => "ALTER TABLE vendor_bill ADD COLUMN gst_percent DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER gst_total",
		'cgst_amount' => "ALTER TABLE vendor_bill ADD COLUMN cgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER gst_percent",
		'sgst_amount' => "ALTER TABLE vendor_bill ADD COLUMN sgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER cgst_amount",
		'igst_amount' => "ALTER TABLE vendor_bill ADD COLUMN igst_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER sgst_amount",
		'pos_state_id' => "ALTER TABLE vendor_bill ADD COLUMN pos_state_id INT(11) NOT NULL DEFAULT 0 AFTER igst_amount",
		'gst_supply_type' => "ALTER TABLE vendor_bill ADD COLUMN gst_supply_type VARCHAR(12) NOT NULL DEFAULT '' AFTER pos_state_id",
	);
	foreach ($cols as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM vendor_bill LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}
}

function expense_vendor_gst_ensure_voucher_columns($conn)
{
	$cols = array(
		'vendor_invoice_no' => "ALTER TABLE general_expense_header ADD COLUMN vendor_invoice_no VARCHAR(100) NOT NULL DEFAULT '' AFTER vendor_bill_id",
		'vendor_invoice_date' => "ALTER TABLE general_expense_header ADD COLUMN vendor_invoice_date VARCHAR(20) DEFAULT '' AFTER vendor_invoice_no",
		'pos_state_id' => "ALTER TABLE general_expense_header ADD COLUMN pos_state_id INT(11) NOT NULL DEFAULT 0 AFTER vendor_invoice_date",
		'gst_supply_type' => "ALTER TABLE general_expense_header ADD COLUMN gst_supply_type VARCHAR(12) NOT NULL DEFAULT '' AFTER pos_state_id",
		'cgst_amount' => "ALTER TABLE general_expense_header ADD COLUMN cgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER total_gst",
		'sgst_amount' => "ALTER TABLE general_expense_header ADD COLUMN sgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER cgst_amount",
		'igst_amount' => "ALTER TABLE general_expense_header ADD COLUMN igst_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER sgst_amount",
		'rcm_applicable' => "ALTER TABLE general_expense_header ADD COLUMN rcm_applicable TINYINT(1) NOT NULL DEFAULT 0 AFTER igst_amount",
		'rcm_gst_amount' => "ALTER TABLE general_expense_header ADD COLUMN rcm_gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER rcm_applicable",
	);
	foreach ($cols as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM general_expense_header LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, $sql);
		}
	}
}

function expense_vendor_gst_vendor_state_id($conn, $vendor_id)
{
	$vendor_id = (int) $vendor_id;
	if ($vendor_id <= 0) {
		return 0;
	}
	$q = mysqli_query($conn, "SELECT state FROM vendor_master WHERE vendor_id='$vendor_id' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return (int) ($row['state'] ?? 0);
	}
	return 0;
}

function expense_vendor_gst_state_options($conn)
{
	$rows = array();
	$q = mysqli_query($conn, "SELECT state_id, state_name FROM state WHERE geo_scope='domestic' ORDER BY state_name ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = array(
				'state_id' => (int) ($row['state_id'] ?? 0),
				'state_name' => trim((string) ($row['state_name'] ?? '')),
			);
		}
	}
	return $rows;
}

function expense_vendor_gst_state_label($conn, $state_id)
{
	$state_id = (int) $state_id;
	if ($state_id <= 0) {
		return '';
	}
	$esc = mysqli_real_escape_string($conn, (string) $state_id);
	$q = mysqli_query($conn, "SELECT state_name FROM state WHERE state_id='$esc' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return trim((string) ($row['state_name'] ?? ''));
	}
	return '';
}

function expense_vendor_gst_supply_type($conn, $pos_state_id)
{
	$company = gst_register_company_state($conn);
	$pos = (int) $pos_state_id;
	if ($company <= 0 || $pos <= 0) {
		return '';
	}
	return ($company === $pos) ? 'intra' : 'inter';
}

function expense_vendor_gst_supply_type_label($type)
{
	$type = strtolower(trim((string) $type));
	if ($type === 'intra') {
		return 'Intra-state (CGST + SGST)';
	}
	if ($type === 'inter') {
		return 'Inter-state (IGST)';
	}
	return 'No GST / not classified';
}

function expense_vendor_gst_split_total($gst_total, $supply_type)
{
	$gst_total = round((float) $gst_total, 2);
	$supply_type = strtolower(trim((string) $supply_type));
	if ($gst_total <= 0) {
		return array('cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0, 'gst' => 0.0);
	}
	if ($supply_type === 'inter') {
		return array('cgst' => 0.0, 'sgst' => 0.0, 'igst' => $gst_total, 'gst' => $gst_total);
	}
	$cgst = round($gst_total / 2, 2);
	$sgst = round($gst_total - $cgst, 2);
	return array('cgst' => $cgst, 'sgst' => $sgst, 'igst' => 0.0, 'gst' => round($cgst + $sgst, 2));
}

function expense_vendor_gst_resolve_pos_state_id($conn, $vendor_id, $pos_from_payload)
{
	$pos = (int) $pos_from_payload;
	if ($pos > 0) {
		return $pos;
	}
	return expense_vendor_gst_vendor_state_id($conn, $vendor_id);
}

/**
 * Build bill header GST snapshot from taxable subtotal, rate, POS, optional invoice GST override.
 */
function expense_vendor_gst_build_bill_snapshot($conn, $vendor_id, array $payload, $subtotal, $gst_total_from_lines, $grand_total)
{
	$subtotal = round((float) $subtotal, 2);
	$gst_total_from_lines = round((float) $gst_total_from_lines, 2);
	$grand_total = round((float) $grand_total, 2);
	$gst_percent = round((float) ($payload['gst_percent'] ?? $payload['bill_gst_percent'] ?? 0), 2);
	if ($gst_percent < 0) {
		$gst_percent = 0;
	}

	$pos_state_id = expense_vendor_gst_resolve_pos_state_id($conn, $vendor_id, $payload['pos_state_id'] ?? 0);
	$supply_type = expense_vendor_gst_supply_type($conn, $pos_state_id);
	if ($gst_total_from_lines <= 0 && $gst_percent <= 0) {
		$supply_type = '';
	}

	$cgst = round((float) ($payload['cgst_amount'] ?? $payload['bill_cgst'] ?? 0), 2);
	$sgst = round((float) ($payload['sgst_amount'] ?? $payload['bill_sgst'] ?? 0), 2);
	$igst = round((float) ($payload['igst_amount'] ?? $payload['bill_igst'] ?? 0), 2);
	$gst_total = round($cgst + $sgst + $igst, 2);

	if ($gst_total <= 0 && $gst_total_from_lines > 0) {
		$split = expense_vendor_gst_split_total($gst_total_from_lines, $supply_type);
		$cgst = $split['cgst'];
		$sgst = $split['sgst'];
		$igst = $split['igst'];
		$gst_total = $split['gst'];
	} elseif ($gst_total > 0 && $gst_total_from_lines > 0 && abs($gst_total - $gst_total_from_lines) > 0.05) {
		return array('ok' => false, 'message' => 'GST component total must match line GST total (' . number_format($gst_total_from_lines, 2) . ').');
	} elseif ($gst_total <= 0) {
		$gst_total = $gst_total_from_lines;
	}

	if ($gst_percent > 0 && $subtotal > 0 && $gst_total > 0) {
		$implied = round($gst_total / $subtotal * 100, 2);
		if (abs($implied - $gst_percent) > 0.25) {
			return array('ok' => false, 'message' => 'GST amount does not match the selected GST rate on taxable freight.');
		}
	}

	$expected_grand = round($subtotal + $gst_total, 2);
	if ($grand_total > 0 && abs($expected_grand - $grand_total) > 0.05) {
		return array('ok' => false, 'message' => 'Invoice total (taxable + GST) does not match grand total. Expected ' . number_format($expected_grand, 2) . '.');
	}

	return array(
		'ok' => true,
		'gst_percent' => $gst_percent,
		'cgst_amount' => $cgst,
		'sgst_amount' => $sgst,
		'igst_amount' => $igst,
		'gst_total' => $gst_total,
		'pos_state_id' => $pos_state_id,
		'gst_supply_type' => $supply_type,
		'pos_state_label' => expense_vendor_gst_state_label($conn, $pos_state_id),
		'supply_type_label' => expense_vendor_gst_supply_type_label($supply_type),
	);
}

function expense_vendor_gst_build_voucher_snapshot($conn, $vendor_id, array $payload, $taxable, $gst_total)
{
	$taxable = round((float) $taxable, 2);
	$gst_total = round((float) $gst_total, 2);
	$gst_percent = round((float) ($payload['gst_percent'] ?? 0), 2);
	$rcm = !empty($payload['rcm_applicable']) ? 1 : 0;
	$rcm_gst = round((float) ($payload['rcm_gst_amount'] ?? 0), 2);
	if ($rcm !== 1) {
		$rcm_gst = 0.0;
	}

	$pos_state_id = expense_vendor_gst_resolve_pos_state_id($conn, $vendor_id, $payload['pos_state_id'] ?? 0);
	$supply_type = expense_vendor_gst_supply_type($conn, $pos_state_id);
	if ($gst_total <= 0 && $gst_percent <= 0) {
		$supply_type = '';
	}

	$vendor_charged = $gst_total;
	if ($rcm === 1 && $rcm_gst > 0) {
		$vendor_charged = max(0, round($gst_total - $rcm_gst, 2));
	}

	$cgst = round((float) ($payload['cgst_amount'] ?? 0), 2);
	$sgst = round((float) ($payload['sgst_amount'] ?? 0), 2);
	$igst = round((float) ($payload['igst_amount'] ?? 0), 2);
	$comp = round($cgst + $sgst + $igst, 2);

	if ($comp <= 0 && $vendor_charged > 0) {
		$split = expense_vendor_gst_split_total($vendor_charged, $supply_type);
		$cgst = $split['cgst'];
		$sgst = $split['sgst'];
		$igst = $split['igst'];
		$comp = $split['gst'];
	} elseif ($comp > 0 && $vendor_charged > 0 && abs($comp - $vendor_charged) > 0.05) {
		return array('ok' => false, 'message' => 'CGST+SGST+IGST must equal vendor-charged GST.');
	}

	if ($gst_percent > 0 && $taxable > 0 && $vendor_charged > 0) {
		$implied = round($vendor_charged / $taxable * 100, 2);
		if (abs($implied - $gst_percent) > 0.25) {
			return array('ok' => false, 'message' => 'Vendor GST does not match rate on taxable amount.');
		}
	}

	return array(
		'ok' => true,
		'gst_percent' => $gst_percent,
		'cgst_amount' => $cgst,
		'sgst_amount' => $sgst,
		'igst_amount' => $igst,
		'vendor_charged_gst' => $vendor_charged,
		'pos_state_id' => $pos_state_id,
		'gst_supply_type' => $supply_type,
		'rcm_applicable' => $rcm,
		'rcm_gst_amount' => $rcm_gst,
	);
}

function expense_vendor_gst_format_components(array $row)
{
	$cgst = round((float) ($row['cgst_amount'] ?? 0), 2);
	$sgst = round((float) ($row['sgst_amount'] ?? 0), 2);
	$igst = round((float) ($row['igst_amount'] ?? 0), 2);
	$gst = round($cgst + $sgst + $igst, 2);
	if ($gst <= 0) {
		$gst = round((float) ($row['gst_total'] ?? $row['total_gst'] ?? 0), 2);
	}
	return array(
		'cgst_amount' => $cgst,
		'sgst_amount' => $sgst,
		'igst_amount' => $igst,
		'gst_amount' => $gst,
	);
}