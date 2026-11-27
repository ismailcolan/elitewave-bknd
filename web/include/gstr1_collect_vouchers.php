<?php

require_once __DIR__ . '/gstr1_helpers.php';
require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_receipt_functions.php';
require_once __DIR__ . '/billing_proforma_functions.php';
require_once __DIR__ . '/expense_bill_helpers.php';
require_once __DIR__ . '/expense_general_helpers.php';

function gstr1_date_between_sql($column, $from_esc, $to_esc)
{
	return "STR_TO_DATE($column, '%d-%m-%Y') >= '$from_esc' AND STR_TO_DATE($column, '%d-%m-%Y') <= '$to_esc'";
}

function gstr1_parse_dmy($date_str)
{
	$dt = DateTime::createFromFormat('d-m-Y', trim((string) $date_str));
	return ($dt instanceof DateTime) ? $dt : null;
}

/**
 * Collect raw vouchers in period (each document once, before classification).
 */
function gstr1_collect_vouchers($conn, array $period)
{
	$from_esc = mysqli_real_escape_string($conn, $period['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $period['to_mysql']);
	$list = array();

	ensure_billing_tables($conn);
	ensure_billing_note_tables($conn);
	ensure_billing_receipt_tables($conn);
	expense_bill_ensure_schema($conn);
	expense_general_ensure_schema($conn);

	$date_inv = gstr1_date_between_sql('m.invoice_date', $from_esc, $to_esc);
	$sql = "SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.invoice_series, m.status, m.is_active,
			m.taxable_value, m.total_freight, m.cgst_amount, m.sgst_amount, m.igst_amount, m.cess_amount,
			m.gst_amount, m.grand_total, m.customer_id,
			c.client_company_name, c.gst_no, c.pan_no, c.gst_registration_type, c.state AS client_state_id
		FROM billing_invoice_master m
		LEFT JOIN client c ON c.client_id = m.customer_id
		WHERE $date_inv AND (m.status='final' OR m.status='cancelled')";
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$series = strtoupper(trim((string) ($r['invoice_series'] ?? '')));
			if ($series === '') {
				$series = strpos(strtoupper((string) ($r['invoice_no'] ?? '')), 'GTA') !== false ? 'GTA' : 'GST';
			}
			$type_key = ($series === 'GST') ? 'gst_booking_gross_receipt' : 'gta_booking_gross_receipt';
			$list[] = gstr1_raw_voucher($r, $type_key, 'billing_invoice_id', 'invoice_date', 'invoice_no', $r['client_company_name']);
		}
	}

	$date_cn = gstr1_date_between_sql('n.note_date', $from_esc, $to_esc);
	$sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.status, n.note_type, n.party_type,
			n.taxable_value, n.cgst_amount, n.sgst_amount, n.igst_amount, n.gst_amount, n.grand_total,
			n.against_invoice_id, n.party_id, inv.invoice_no AS against_invoice_no, inv.invoice_date AS against_invoice_date,
			c.client_company_name, c.gst_no, c.pan_no, c.gst_registration_type, c.state AS client_state_id
		FROM billing_note_master n
		LEFT JOIN client c ON c.client_id = n.party_id AND n.party_type='client'
		LEFT JOIN billing_invoice_master inv ON inv.billing_invoice_id = n.against_invoice_id
		WHERE $date_cn AND n.status IN ('final','cancelled')";
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$nt = strtoupper((string) ($r['note_type'] ?? ''));
			if ($nt === 'CN' && strtolower((string) ($r['party_type'] ?? '')) === 'client') {
				$list[] = gstr1_raw_voucher($r, 'credit_note', 'billing_note_id', 'note_date', 'note_no', $r['client_company_name']);
			} elseif ($nt === 'DN') {
				$list[] = gstr1_raw_voucher($r, 'debit_note', 'billing_note_id', 'note_date', 'note_no', $r['client_company_name']);
			}
		}
	}

	$date_rcp = gstr1_date_between_sql('m.receipt_date', $from_esc, $to_esc);
	$sql = "SELECT m.billing_receipt_id, m.receipt_no, m.receipt_date, m.status, m.receipt_type, m.total_amount, m.party_id,
			c.client_company_name
		FROM billing_receipt_master m
		LEFT JOIN client c ON c.client_id = m.party_id
		WHERE $date_rcp AND m.status IN ('final','cancelled')";
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$list[] = gstr1_raw_voucher($r, 'receipt', 'billing_receipt_id', 'receipt_date', 'receipt_no', $r['client_company_name']);
		}
	}

	$date_bill = gstr1_date_between_sql('b.bill_date', $from_esc, $to_esc);
	$sql = "SELECT b.bill_id, b.bill_entry_no, b.bill_date, b.status, b.subtotal, b.gst_total, b.grand_total,
			b.cgst_amount, b.sgst_amount, b.igst_amount, v.vendor_name
		FROM vendor_bill b
		LEFT JOIN vendor_master v ON v.vendor_id = b.vendor_id
		WHERE $date_bill";
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$gst = round((float) ($r['gst_total'] ?? 0), 2);
			$type_key = $gst > 0.009 ? 'taxable_purchase' : 'purchase';
			$list[] = gstr1_raw_voucher($r, $type_key, 'bill_id', 'bill_date', 'bill_entry_no', $r['vendor_name']);
		}
	}

	$date_exp = gstr1_date_between_sql('h.expense_date', $from_esc, $to_esc);
	$sql = "SELECT h.general_expense_id, h.expense_no, h.expense_date, h.status, h.voucher_type, h.net_payable,
			h.total_gst, h.rcm_applicable, h.rcm_gst_amount, v.vendor_name
		FROM general_expense_header h
		LEFT JOIN vendor_master v ON v.vendor_id = h.vendor_id
		WHERE h.status=0 AND $date_exp";
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$vtype = strtolower(trim((string) ($r['voucher_type'] ?? 'general')));
			if ((int) ($r['rcm_applicable'] ?? 0) === 1) {
				$type_key = 'rcm_purchase';
			} elseif ($vtype === 'journal') {
				$type_key = 'journal';
			} else {
				$type_key = 'payment';
			}
			$list[] = gstr1_raw_voucher($r, $type_key, 'general_expense_id', 'expense_date', 'expense_no', $r['vendor_name']);
		}
	}

	if (function_exists('ensure_billing_proforma_tables')) {
		ensure_billing_proforma_tables($conn);
	}
	$tables_pf = array('billing_proforma_master');
	foreach ($tables_pf as $tbl) {
		$chk = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
		if (!$chk || !mysqli_num_rows($chk)) {
			continue;
		}
		$date_pf = gstr1_date_between_sql('p.invoice_date', $from_esc, $to_esc);
		$sql = "SELECT p.billing_proforma_id, p.proforma_no, p.invoice_date, p.status, p.grand_total, c.client_company_name
			FROM billing_proforma_master p
			LEFT JOIN client c ON c.client_id = p.customer_id
			WHERE $date_pf";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($r = mysqli_fetch_assoc($q)) {
				$list[] = gstr1_raw_voucher($r, 'proforma_invoice', 'billing_proforma_id', 'invoice_date', 'proforma_no', $r['client_company_name']);
			}
		}
	}

	$q_tables = mysqli_query($conn, "SELECT table_name FROM transaction_tbls");
	if ($q_tables) {
		while ($tbl_row = mysqli_fetch_assoc($q_tables)) {
			$trans_table = 'transaction_' . preg_replace('/[^a-zA-Z0-9_]/', '', $tbl_row['table_name']);
			$chk = mysqli_query($conn, "SHOW TABLES LIKE '$trans_table'");
			if (!$chk || !mysqli_num_rows($chk)) {
				continue;
			}
			$date_gcn = gstr1_date_between_sql('t.grn_date', $from_esc, $to_esc);
			$sql = "SELECT t.transaction_id, t.grn_no, t.grn_date, t.status, t.booking_status, t.total
				FROM `$trans_table` t WHERE $date_gcn";
			$q = mysqli_query($conn, $sql);
			if (!$q) {
				continue;
			}
			while ($r = mysqli_fetch_assoc($q)) {
				$transit_status = (int) ($r['status'] ?? 0);
				$delivered = ($transit_status >= 8);
				$type_key = $delivered ? 'delivery_note' : 'booking';
				$v = gstr1_raw_voucher($r, $type_key, 'transaction_id', 'grn_date', 'grn_no', 'GCN ' . $r['grn_no']);
				$v['trans_table'] = $trans_table;
				$v['transaction_id'] = (int) $r['transaction_id'];
				$v['qty_outward'] = 1;
				if (function_exists('ew_booking_is_cancelled') && ew_booking_is_cancelled($r['booking_status'] ?? '')) {
					$v['cancelled'] = true;
				} elseif ((string) ($r['booking_status'] ?? '') === '1') {
					$v['cancelled'] = true;
				}
				$list[] = $v;
			}
		}
	}

	usort($list, function ($a, $b) {
		$da = $a['sort_date'] ?? '';
		$db = $b['sort_date'] ?? '';
		if ($da !== $db) {
			return strcmp($da, $db);
		}
		return strcmp($a['voucher_type_key'] . $a['doc_id'], $b['voucher_type_key'] . $b['doc_id']);
	});

	return $list;
}

function gstr1_raw_voucher(array $row, $type_key, $id_field, $date_field, $no_field, $party)
{
	$dt = gstr1_parse_dmy($row[$date_field] ?? '');
	$taxable = round((float) ($row['taxable_value'] ?? $row['subtotal'] ?? $row['total_freight'] ?? $row['net_payable'] ?? $row['total_amount'] ?? $row['grand_total'] ?? $row['total'] ?? 0), 2);
	$cgst = round((float) ($row['cgst_amount'] ?? 0), 2);
	$sgst = round((float) ($row['sgst_amount'] ?? 0), 2);
	$igst = round((float) ($row['igst_amount'] ?? 0), 2);
	$cess = round((float) ($row['cess_amount'] ?? 0), 2);
	$gst = round((float) ($row['gst_amount'] ?? $row['gst_total'] ?? ($cgst + $sgst + $igst)), 2);
	$grand = round((float) ($row['grand_total'] ?? $row['net_payable'] ?? $row['total_amount'] ?? $taxable + $gst), 2);
	$doc_status = strtolower(trim((string) ($row['status'] ?? '')));
	$cancelled = ($doc_status === 'cancelled');
	if (in_array($type_key, array('booking', 'delivery_note'), true)) {
		$cancelled = (function_exists('ew_booking_is_cancelled') && ew_booking_is_cancelled($row['booking_status'] ?? ''))
			|| ((string) ($row['booking_status'] ?? '') === '1');
	} elseif (isset($row['is_active']) && (int) $row['is_active'] === 0) {
		$cancelled = true;
	}
	return array(
		'voucher_type_key' => $type_key,
		'doc_id' => (int) ($row[$id_field] ?? 0),
		'voucher_no' => trim((string) ($row[$no_field] ?? '')),
		'voucher_date' => $dt ? $dt->format('d-m-Y') : trim((string) ($row[$date_field] ?? '')),
		'sort_date' => $dt ? $dt->format('Y-m-d') : '1900-01-01',
		'particulars' => trim((string) $party),
		'party_name' => trim((string) $party),
		'party_gstin' => gst_tax_normalize_gstin($row['gst_no'] ?? ''),
		'party_pan' => trim((string) ($row['pan_no'] ?? '')),
		'registration_type' => trim((string) ($row['gst_registration_type'] ?? '')),
		'client_state_id' => (int) ($row['client_state_id'] ?? 0),
		'taxable' => $taxable,
		'cgst' => $cgst,
		'sgst' => $sgst,
		'igst' => $igst,
		'cess' => $cess,
		'gst' => $gst,
		'invoice_amount' => $grand,
		'debit' => $grand,
		'credit' => 0,
		'cancelled' => $cancelled,
		'against_invoice_id' => (int) ($row['against_invoice_id'] ?? 0),
		'against_invoice_no' => trim((string) ($row['against_invoice_no'] ?? '')),
		'against_invoice_date' => trim((string) ($row['against_invoice_date'] ?? '')),
		'receipt_type' => trim((string) ($row['receipt_type'] ?? '')),
		'invoice_series' => strtoupper(trim((string) ($row['invoice_series'] ?? ''))),
		'trans_table' => '',
		'transaction_id' => 0,
		'qty_inward' => 0,
		'qty_outward' => 0,
	);
}