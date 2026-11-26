<?php

require_once __DIR__ . '/billing_functions.php';
require_once __DIR__ . '/billing_note_functions.php';
require_once __DIR__ . '/expense_bill_helpers.php';
require_once __DIR__ . '/expense_vendor_gst_helpers.php';
require_once __DIR__ . '/expense_general_helpers.php';

function gst_register_format_money($val)
{
	return number_format((float) $val, 2, '.', '');
}

function gst_register_parse_dates($request)
{
	$from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
	$to_date = isset($request['to_date']) ? trim($request['to_date']) : '';

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
	);
}

function gst_register_company_state($conn)
{
	static $cached = null;
	if ($cached !== null) {
		return $cached;
	}
	$cached = 0;
	$q = mysqli_query($conn, 'SELECT state FROM company WHERE status=0 ORDER BY company_id ASC LIMIT 1');
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$cached = (int) ($row['state'] ?? 0);
	}
	return $cached;
}

/**
 * Split a single GST total into CGST/SGST/IGST using company vs party state.
 */
function gst_register_split_by_state($conn, $gst_total, $party_state_id)
{
	$gst_total = round((float) $gst_total, 2);
	if ($gst_total <= 0) {
		return array('cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0, 'gst' => 0.0);
	}
	$company_state = gst_register_company_state($conn);
	$party_state = (int) $party_state_id;
	$intra = ($company_state > 0 && $party_state > 0 && $company_state === $party_state);
	if ($intra) {
		$cgst = round($gst_total / 2, 2);
		$sgst = round($gst_total - $cgst, 2);
		return array('cgst' => $cgst, 'sgst' => $sgst, 'igst' => 0.0, 'gst' => round($cgst + $sgst, 2));
	}
	return array('cgst' => 0.0, 'sgst' => 0.0, 'igst' => $gst_total, 'gst' => $gst_total);
}

function gst_register_empty_summary()
{
	return array(
		'taxable_value' => gst_register_format_money(0),
		'cgst_amount' => gst_register_format_money(0),
		'sgst_amount' => gst_register_format_money(0),
		'igst_amount' => gst_register_format_money(0),
		'gst_amount' => gst_register_format_money(0),
		'doc_value' => gst_register_format_money(0),
		'row_count' => 0,
	);
}

function gst_output_doc_type_options()
{
	return array(
		'' => 'All documents',
		'invoice' => 'Tax Invoice',
		'credit_note' => 'Credit Note',
	);
}

function gst_input_doc_type_options()
{
	return array(
		'' => 'All documents',
		'vendor_bill' => 'Purchase Bill Registration',
		'general_expense' => 'Payment Voucher (General)',
		'debit_note' => 'Debit Note',
	);
}

function gst_output_parse_filters($request)
{
	$base = gst_register_parse_dates($request);
	if (!empty($base['error'])) {
		return $base;
	}
	$doc_type = isset($request['doc_type']) ? strtolower(trim($request['doc_type'])) : '';
	$customers = isset($request['customers']) ? trim($request['customers']) : '';
	$allowed = array_keys(gst_output_doc_type_options());
	if (!in_array($doc_type, $allowed, true)) {
		$doc_type = '';
	}
	$base['doc_type'] = $doc_type;
	$base['customers'] = $customers;
	return $base;
}

function gst_input_parse_filters($request)
{
	$base = gst_register_parse_dates($request);
	if (!empty($base['error'])) {
		return $base;
	}
	$doc_type = isset($request['doc_type']) ? strtolower(trim($request['doc_type'])) : '';
	$vendors = isset($request['vendors']) ? trim($request['vendors']) : '';
	$allowed = array_keys(gst_input_doc_type_options());
	if (!in_array($doc_type, $allowed, true)) {
		$doc_type = '';
	}
	$base['doc_type'] = $doc_type;
	$base['vendors'] = $vendors;
	return $base;
}

function gst_register_want_doc($filters, $type)
{
	$selected = $filters['doc_type'] ?? '';
	return ($selected === '' || $selected === $type);
}

/**
 * GST Output = Tax Invoice (+) and Credit Note (−).
 */
function gst_output_fetch_rows($conn, $filters)
{
	if (!empty($filters['error'])) {
		return array('rows' => array(), 'summary' => gst_register_empty_summary());
	}

	ensure_billing_tables($conn);
	ensure_billing_note_tables($conn);

	$from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);
	$events = array();

	$customer_sql = '';
	if (!empty($filters['customers'])) {
		$ids = array_filter(array_map('intval', explode(',', $filters['customers'])));
		if (!empty($ids)) {
			$customer_sql = ' AND m.customer_id IN (' . implode(',', $ids) . ')';
			$note_customer_sql = ' AND n.party_id IN (' . implode(',', $ids) . ')';
		} else {
			$note_customer_sql = '';
		}
	} else {
		$note_customer_sql = '';
	}

	if (gst_register_want_doc($filters, 'invoice')) {
		$sql = "SELECT m.billing_invoice_id, m.invoice_no, m.invoice_date, m.customer_id,
				m.taxable_value, m.total_freight, m.cgst_amount, m.sgst_amount, m.igst_amount,
				m.gst_amount, m.grand_total,
				c.client_company_name, c.gst_no
			FROM billing_invoice_master m
			LEFT JOIN client c ON c.client_id = m.customer_id
			WHERE m.status='final' AND COALESCE(m.is_active,1)=1
				AND STR_TO_DATE(m.invoice_date, '%d-%m-%Y') >= '$from_esc'
				AND STR_TO_DATE(m.invoice_date, '%d-%m-%Y') <= '$to_esc'
				$customer_sql
			ORDER BY STR_TO_DATE(m.invoice_date, '%d-%m-%Y') ASC, m.billing_invoice_id ASC";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($r = mysqli_fetch_assoc($q)) {
				$taxable = round((float) ($r['taxable_value'] ?? 0), 2);
				if ($taxable <= 0) {
					$taxable = round((float) ($r['total_freight'] ?? 0), 2);
				}
				$cgst = round((float) ($r['cgst_amount'] ?? 0), 2);
				$sgst = round((float) ($r['sgst_amount'] ?? 0), 2);
				$igst = round((float) ($r['igst_amount'] ?? 0), 2);
				$gst = round((float) ($r['gst_amount'] ?? 0), 2);
				if ($gst <= 0) {
					$gst = round($cgst + $sgst + $igst, 2);
				}
				$grand = round((float) ($r['grand_total'] ?? 0), 2);
				$dt = DateTime::createFromFormat('d-m-Y', trim((string) ($r['invoice_date'] ?? '')));
				if (!$dt) {
					continue;
				}
				$party = trim((string) ($r['client_company_name'] ?? ''));
				$gstin = strtoupper(trim((string) ($r['gst_no'] ?? '')));
				$ref = trim((string) ($r['invoice_no'] ?? ''));
				$events[] = array(
					'sort_date' => $dt->format('Y-m-d'),
					'doc_id' => (int) $r['billing_invoice_id'],
					'doc_order' => 10,
					'doc_type' => 'invoice',
					'doc_type_label' => 'Tax Invoice',
					'doc_no' => $ref !== '' ? $ref : ('INV-' . (int) $r['billing_invoice_id']),
					'doc_date' => $dt->format('d-m-Y'),
					'party' => $party !== '' ? $party : '—',
					'gstin' => $gstin !== '' ? $gstin : '—',
					'taxable' => $taxable,
					'cgst' => $cgst,
					'sgst' => $sgst,
					'igst' => $igst,
					'gst' => $gst,
					'doc_value' => $grand,
				);
			}
		}
	}

	if (gst_register_want_doc($filters, 'credit_note')) {
		$sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.party_id,
				n.taxable_value, n.cgst_amount, n.sgst_amount, n.igst_amount, n.gst_amount, n.grand_total,
				c.client_company_name, c.gst_no, inv.invoice_no
			FROM billing_note_master n
			LEFT JOIN client c ON c.client_id = n.party_id
			LEFT JOIN billing_invoice_master inv ON inv.billing_invoice_id = n.against_invoice_id
			WHERE n.note_type='CN' AND n.party_type='client' AND n.status='final'
				AND STR_TO_DATE(n.note_date, '%d-%m-%Y') >= '$from_esc'
				AND STR_TO_DATE(n.note_date, '%d-%m-%Y') <= '$to_esc'
				$note_customer_sql
			ORDER BY STR_TO_DATE(n.note_date, '%d-%m-%Y') ASC, n.billing_note_id ASC";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($r = mysqli_fetch_assoc($q)) {
				$taxable = -1 * round((float) ($r['taxable_value'] ?? 0), 2);
				$cgst = -1 * round((float) ($r['cgst_amount'] ?? 0), 2);
				$sgst = -1 * round((float) ($r['sgst_amount'] ?? 0), 2);
				$igst = -1 * round((float) ($r['igst_amount'] ?? 0), 2);
				$gst = -1 * round((float) ($r['gst_amount'] ?? 0), 2);
				if (abs($gst) < 0.001) {
					$gst = round($cgst + $sgst + $igst, 2);
				}
				$grand = -1 * round((float) ($r['grand_total'] ?? 0), 2);
				$dt = DateTime::createFromFormat('d-m-Y', trim((string) ($r['note_date'] ?? '')));
				if (!$dt) {
					continue;
				}
				$party = trim((string) ($r['client_company_name'] ?? ''));
				$gstin = strtoupper(trim((string) ($r['gst_no'] ?? '')));
				$ref = trim((string) ($r['note_no'] ?? ''));
				$vs = trim((string) ($r['invoice_no'] ?? ''));
				$events[] = array(
					'sort_date' => $dt->format('Y-m-d'),
					'doc_id' => (int) $r['billing_note_id'],
					'doc_order' => 20,
					'doc_type' => 'credit_note',
					'doc_type_label' => 'Credit Note',
					'doc_no' => $ref !== '' ? $ref : ('CN-' . (int) $r['billing_note_id']),
					'doc_date' => $dt->format('d-m-Y'),
					'party' => $party !== '' ? $party : '—',
					'gstin' => $gstin !== '' ? $gstin : '—',
					'taxable' => $taxable,
					'cgst' => $cgst,
					'sgst' => $sgst,
					'igst' => $igst,
					'gst' => $gst,
					'doc_value' => $grand,
					'against' => $vs,
				);
			}
		}
	}

	usort($events, function ($a, $b) {
		if ($a['sort_date'] !== $b['sort_date']) {
			return strcmp($a['sort_date'], $b['sort_date']);
		}
		if ($a['doc_order'] !== $b['doc_order']) {
			return $a['doc_order'] - $b['doc_order'];
		}
		return $a['doc_id'] - $b['doc_id'];
	});

	return gst_register_finalize_rows($events);
}

/**
 * GST Input = Vendor Bill (+) and Debit Note (−).
 */
function gst_input_fetch_rows($conn, $filters)
{
	if (!empty($filters['error'])) {
		return array('rows' => array(), 'summary' => gst_register_empty_summary());
	}

	expense_bill_ensure_schema($conn);
	ensure_billing_note_tables($conn);

	$from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);
	$events = array();

	$vendor_sql = '';
	$vendor_sql_header = '';
	$note_vendor_sql = '';
	if (!empty($filters['vendors'])) {
		$ids = array_filter(array_map('intval', explode(',', $filters['vendors'])));
		if (!empty($ids)) {
			$in = implode(',', $ids);
			$vendor_sql = ' AND b.vendor_id IN (' . $in . ')';
			$vendor_sql_header = ' AND h.vendor_id IN (' . $in . ')';
			$note_vendor_sql = ' AND n.party_id IN (' . $in . ')';
		}
	}

	if (gst_register_want_doc($filters, 'vendor_bill')) {
		$sql = "SELECT b.bill_id, b.bill_entry_no, b.bill_date, b.vendor_invoice_no, b.vendor_invoice_date, b.vendor_id,
				b.subtotal, b.gst_total, b.gst_percent, b.cgst_amount, b.sgst_amount, b.igst_amount,
				b.pos_state_id, b.gst_supply_type, b.grand_total, b.net_payable,
				v.vendor_code, v.vendor_name, v.gstin, v.state AS vendor_state
			FROM vendor_bill b
			LEFT JOIN vendor_master v ON v.vendor_id = b.vendor_id
			WHERE b.status=0
				AND STR_TO_DATE(b.bill_date, '%d-%m-%Y') >= '$from_esc'
				AND STR_TO_DATE(b.bill_date, '%d-%m-%Y') <= '$to_esc'
				$vendor_sql
			ORDER BY STR_TO_DATE(b.bill_date, '%d-%m-%Y') ASC, b.bill_id ASC";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($r = mysqli_fetch_assoc($q)) {
				$taxable = round((float) ($r['subtotal'] ?? 0), 2);
				$gst_comp = expense_vendor_gst_format_components($r);
				$cgst = $gst_comp['cgst_amount'];
				$sgst = $gst_comp['sgst_amount'];
				$igst = $gst_comp['igst_amount'];
				$gst_total = $gst_comp['gst_amount'];
				if ($gst_total <= 0) {
					$gst_total = round((float) ($r['gst_total'] ?? 0), 2);
					$split = gst_register_split_by_state($conn, $gst_total, (int) ($r['pos_state_id'] ?? 0) ?: (int) ($r['vendor_state'] ?? 0));
					$cgst = $split['cgst'];
					$sgst = $split['sgst'];
					$igst = $split['igst'];
				}
				$grand = round((float) ($r['grand_total'] ?? 0), 2);
				if ($grand <= 0) {
					$grand = round((float) ($r['net_payable'] ?? 0), 2);
				}
				$dt = DateTime::createFromFormat('d-m-Y', trim((string) ($r['bill_date'] ?? '')));
				if (!$dt) {
					continue;
				}
				$party = trim((string) (($r['vendor_code'] ?? '') . ' - ' . ($r['vendor_name'] ?? '')), ' -');
				$gstin = strtoupper(trim((string) ($r['gstin'] ?? '')));
				$ref = trim((string) ($r['bill_entry_no'] ?? ''));
				$vinv = trim((string) ($r['vendor_invoice_no'] ?? ''));
				$events[] = array(
					'sort_date' => $dt->format('Y-m-d'),
					'doc_id' => (int) $r['bill_id'],
					'doc_order' => 10,
					'doc_type' => 'vendor_bill',
					'doc_type_label' => 'Bill Registration',
					'doc_no' => $ref !== '' ? $ref : ('BILL-' . (int) $r['bill_id']),
					'doc_date' => $dt->format('d-m-Y'),
					'party' => $party !== '' ? $party : '—',
					'gstin' => $gstin !== '' ? $gstin : '—',
					'taxable' => $taxable,
					'cgst' => $cgst,
					'sgst' => $sgst,
					'igst' => $igst,
					'gst' => $gst_total > 0 ? $gst_total : round($cgst + $sgst + $igst, 2),
					'doc_value' => $grand,
					'against' => $vinv,
				);
			}
		}
	}

	if (gst_register_want_doc($filters, 'general_expense')) {
		expense_general_ensure_schema($conn);
		$sql = "SELECT h.general_expense_id, h.expense_no, h.expense_date, h.vendor_invoice_no, h.vendor_invoice_date,
				h.expenses_without_gst, h.total_gst, h.cgst_amount, h.sgst_amount, h.igst_amount, h.net_payable,
				h.pos_state_id, h.gst_supply_type, h.rcm_applicable, h.rcm_gst_amount,
				v.vendor_code, v.vendor_name, v.gstin, v.state AS vendor_state
			FROM general_expense_header h
			LEFT JOIN vendor_master v ON v.vendor_id = h.vendor_id
			WHERE h.status=0
				AND LOWER(IFNULL(h.voucher_type,'')) IN ('general','general_expense')
				AND STR_TO_DATE(h.expense_date, '%d-%m-%Y') >= '$from_esc'
				AND STR_TO_DATE(h.expense_date, '%d-%m-%Y') <= '$to_esc'
				$vendor_sql_header
			ORDER BY STR_TO_DATE(h.expense_date, '%d-%m-%Y') ASC, h.general_expense_id ASC";
		$vq = mysqli_query($conn, $sql);
		if ($vq) {
			while ($r = mysqli_fetch_assoc($vq)) {
				$taxable = round((float) ($r['expenses_without_gst'] ?? 0), 2);
				$gst_comp = expense_vendor_gst_format_components($r);
				$cgst = $gst_comp['cgst_amount'];
				$sgst = $gst_comp['sgst_amount'];
				$igst = $gst_comp['igst_amount'];
				$gst_total = $gst_comp['gst_amount'];
				if ($gst_total <= 0) {
					$gst_total = round((float) ($r['total_gst'] ?? 0), 2);
					if ((int) ($r['rcm_applicable'] ?? 0) === 1) {
						$gst_total = max(0, round($gst_total - (float) ($r['rcm_gst_amount'] ?? 0), 2));
					}
					$split = gst_register_split_by_state($conn, $gst_total, (int) ($r['pos_state_id'] ?? 0) ?: (int) ($r['vendor_state'] ?? 0));
					$cgst = $split['cgst'];
					$sgst = $split['sgst'];
					$igst = $split['igst'];
				}
				$grand = round((float) ($r['net_payable'] ?? 0), 2);
				$dt = DateTime::createFromFormat('d-m-Y', trim((string) ($r['expense_date'] ?? '')));
				if (!$dt) {
					continue;
				}
				$party = trim((string) (($r['vendor_code'] ?? '') . ' - ' . ($r['vendor_name'] ?? '')), ' -');
				$gstin = strtoupper(trim((string) ($r['gstin'] ?? '')));
				$ref = trim((string) ($r['expense_no'] ?? ''));
				$vinv = trim((string) ($r['vendor_invoice_no'] ?? ''));
				$events[] = array(
					'sort_date' => $dt->format('Y-m-d'),
					'doc_id' => (int) $r['general_expense_id'],
					'doc_order' => 15,
					'doc_type' => 'general_expense',
					'doc_type_label' => 'Payment Voucher (General)',
					'doc_no' => $ref !== '' ? $ref : ('PV-' . (int) $r['general_expense_id']),
					'doc_date' => $dt->format('d-m-Y'),
					'party' => $party !== '' ? $party : '—',
					'gstin' => $gstin !== '' ? $gstin : '—',
					'taxable' => $taxable,
					'cgst' => $cgst,
					'sgst' => $sgst,
					'igst' => $igst,
					'gst' => $gst_total > 0 ? $gst_total : round($cgst + $sgst + $igst, 2),
					'doc_value' => $grand,
					'against' => $vinv,
				);
			}
		}
	}

	if (gst_register_want_doc($filters, 'debit_note')) {
		$sql = "SELECT n.billing_note_id, n.note_no, n.note_date, n.party_id,
				n.taxable_value, n.cgst_amount, n.sgst_amount, n.igst_amount, n.gst_amount, n.grand_total,
				v.vendor_code, v.vendor_name, v.gstin,
				COALESCE(NULLIF(b.vendor_invoice_no,''), b.bill_entry_no) AS bill_ref
			FROM billing_note_master n
			LEFT JOIN vendor_master v ON v.vendor_id = n.party_id
			LEFT JOIN vendor_bill b ON b.bill_id = n.against_invoice_id
			WHERE n.note_type='DN' AND n.party_type='vendor' AND n.status='final'
				AND STR_TO_DATE(n.note_date, '%d-%m-%Y') >= '$from_esc'
				AND STR_TO_DATE(n.note_date, '%d-%m-%Y') <= '$to_esc'
				$note_vendor_sql
			ORDER BY STR_TO_DATE(n.note_date, '%d-%m-%Y') ASC, n.billing_note_id ASC";
		$q = mysqli_query($conn, $sql);
		if ($q) {
			while ($r = mysqli_fetch_assoc($q)) {
				$taxable = -1 * round((float) ($r['taxable_value'] ?? 0), 2);
				$cgst = -1 * round((float) ($r['cgst_amount'] ?? 0), 2);
				$sgst = -1 * round((float) ($r['sgst_amount'] ?? 0), 2);
				$igst = -1 * round((float) ($r['igst_amount'] ?? 0), 2);
				$gst = -1 * round((float) ($r['gst_amount'] ?? 0), 2);
				if (abs($gst) < 0.001) {
					$gst = round($cgst + $sgst + $igst, 2);
				}
				$grand = -1 * round((float) ($r['grand_total'] ?? 0), 2);
				$dt = DateTime::createFromFormat('d-m-Y', trim((string) ($r['note_date'] ?? '')));
				if (!$dt) {
					continue;
				}
				$party = trim((string) (($r['vendor_code'] ?? '') . ' - ' . ($r['vendor_name'] ?? '')), ' -');
				$gstin = strtoupper(trim((string) ($r['gstin'] ?? '')));
				$ref = trim((string) ($r['note_no'] ?? ''));
				$vs = trim((string) ($r['bill_ref'] ?? ''));
				$events[] = array(
					'sort_date' => $dt->format('Y-m-d'),
					'doc_id' => (int) $r['billing_note_id'],
					'doc_order' => 20,
					'doc_type' => 'debit_note',
					'doc_type_label' => 'Debit Note',
					'doc_no' => $ref !== '' ? $ref : ('DN-' . (int) $r['billing_note_id']),
					'doc_date' => $dt->format('d-m-Y'),
					'party' => $party !== '' ? $party : '—',
					'gstin' => $gstin !== '' ? $gstin : '—',
					'taxable' => $taxable,
					'cgst' => $cgst,
					'sgst' => $sgst,
					'igst' => $igst,
					'gst' => $gst,
					'doc_value' => $grand,
					'against' => $vs,
				);
			}
		}
	}

	usort($events, function ($a, $b) {
		if ($a['sort_date'] !== $b['sort_date']) {
			return strcmp($a['sort_date'], $b['sort_date']);
		}
		if ($a['doc_order'] !== $b['doc_order']) {
			return $a['doc_order'] - $b['doc_order'];
		}
		return $a['doc_id'] - $b['doc_id'];
	});

	return gst_register_finalize_rows($events);
}

function gst_register_finalize_rows($events)
{
	$rows = array();
	$sum_taxable = 0.0;
	$sum_cgst = 0.0;
	$sum_sgst = 0.0;
	$sum_igst = 0.0;
	$sum_gst = 0.0;
	$sum_doc = 0.0;
	$sno = 0;

	foreach ($events as $e) {
		$sno++;
		$taxable = (float) $e['taxable'];
		$cgst = (float) $e['cgst'];
		$sgst = (float) $e['sgst'];
		$igst = (float) $e['igst'];
		$gst = (float) $e['gst'];
		$doc_value = (float) $e['doc_value'];

		$sum_taxable += $taxable;
		$sum_cgst += $cgst;
		$sum_sgst += $sgst;
		$sum_igst += $igst;
		$sum_gst += $gst;
		$sum_doc += $doc_value;

		$against = trim((string) ($e['against'] ?? ''));

		$rows[] = array(
			's_no' => $sno,
			'doc_type' => $e['doc_type'],
			'doc_type_label' => $e['doc_type_label'],
			'doc_no' => $e['doc_no'],
			'doc_date' => $e['doc_date'],
			'party' => $e['party'],
			'gstin' => $e['gstin'],
			'against' => $against !== '' ? $against : '—',
			'taxable_value' => gst_register_format_money($taxable),
			'cgst_amount' => gst_register_format_money($cgst),
			'sgst_amount' => gst_register_format_money($sgst),
			'igst_amount' => gst_register_format_money($igst),
			'gst_amount' => gst_register_format_money($gst),
			'doc_value' => gst_register_format_money($doc_value),
			'taxable_raw' => $taxable,
			'cgst_raw' => $cgst,
			'sgst_raw' => $sgst,
			'igst_raw' => $igst,
			'gst_raw' => $gst,
			'doc_value_raw' => $doc_value,
		);
	}

	return array(
		'rows' => $rows,
		'summary' => array(
			'taxable_value' => gst_register_format_money($sum_taxable),
			'cgst_amount' => gst_register_format_money($sum_cgst),
			'sgst_amount' => gst_register_format_money($sum_sgst),
			'igst_amount' => gst_register_format_money($sum_igst),
			'gst_amount' => gst_register_format_money($sum_gst),
			'doc_value' => gst_register_format_money($sum_doc),
			'row_count' => count($rows),
		),
	);
}
