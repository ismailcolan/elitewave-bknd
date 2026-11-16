<?php

require_once __DIR__ . '/billing_proforma_functions.php';

function proforma_invoice_register_format_money($val)
{
	return number_format((float) $val, 2, '.', '');
}

function proforma_invoice_register_parse_filters($request)
{
	$from_date = isset($request['from_date']) ? trim($request['from_date']) : '';
	$to_date = isset($request['to_date']) ? trim($request['to_date']) : '';
	$customers = isset($request['customers']) ? trim($request['customers']) : '';
	$status = isset($request['status']) ? trim($request['status']) : 'final';
	$doc_style = isset($request['doc_style']) ? trim($request['doc_style']) : '';

	if ($from_date === '' || $to_date === '') {
		return array('error' => 'From Date and To Date are required.');
	}

	$from_dt = DateTime::createFromFormat('d-m-Y', $from_date);
	$to_dt = DateTime::createFromFormat('d-m-Y', $to_date);
	if (!$from_dt || !$to_dt) {
		return array('error' => 'Invalid date format. Use dd-mm-yyyy.');
	}
	if ($from_dt > $to_dt) {
		return array('error' => 'From Date cannot be after To Date.');
	}

	$allowed_status = array('final', 'draft', 'all');
	if (!in_array($status, $allowed_status, true)) {
		$status = 'final';
	}

	$allowed_style = array('', 'gst', 'other');
	if (!in_array($doc_style, $allowed_style, true)) {
		$doc_style = '';
	}

	return array(
		'from_date' => $from_date,
		'to_date' => $to_date,
		'from_mysql' => $from_dt->format('Y-m-d'),
		'to_mysql' => $to_dt->format('Y-m-d'),
		'customers' => $customers,
		'status' => $status,
		'doc_style' => $doc_style,
	);
}

function proforma_invoice_register_empty_summary()
{
	return array(
		'total_freight' => proforma_invoice_register_format_money(0),
		'sgst_amount' => proforma_invoice_register_format_money(0),
		'cgst_amount' => proforma_invoice_register_format_money(0),
		'igst_amount' => proforma_invoice_register_format_money(0),
		'total_other' => proforma_invoice_register_format_money(0),
		'grand_total' => proforma_invoice_register_format_money(0),
		'row_count' => 0,
	);
}

function proforma_invoice_register_fetch_rows($conn, $filters)
{
	if (!empty($filters['error'])) {
		return array('rows' => array(), 'summary' => proforma_invoice_register_empty_summary());
	}

	ensure_billing_proforma_tables($conn);

	$from_esc = mysqli_real_escape_string($conn, $filters['from_mysql']);
	$to_esc = mysqli_real_escape_string($conn, $filters['to_mysql']);

	$where = "STR_TO_DATE(m.invoice_date, '%d-%m-%Y') >= '$from_esc'
		AND STR_TO_DATE(m.invoice_date, '%d-%m-%Y') <= '$to_esc'";

	$status = isset($filters['status']) ? $filters['status'] : 'final';
	if ($status === 'final') {
		$where .= " AND m.status='final'";
	} elseif ($status === 'draft') {
		$where .= " AND m.status='draft'";
	} else {
		$where .= " AND m.status IN ('final','draft')";
	}

	if (!empty($filters['customers'])) {
		$ids = array_filter(array_map('intval', explode(',', $filters['customers'])));
		if (!empty($ids)) {
			$where .= ' AND m.customer_id IN (' . implode(',', $ids) . ')';
		}
	}

	if (!empty($filters['doc_style'])) {
		$style_esc = mysqli_real_escape_string($conn, $filters['doc_style']);
		$where .= " AND m.doc_style='$style_esc'";
	}

	$type_opts = billing_type_options();

	$sql = "SELECT m.billing_proforma_id, m.proforma_no, m.invoice_date, m.customer_id,
			m.billing_type, m.doc_style, m.status,
			m.total_freight, m.sgst_amount, m.cgst_amount, m.igst_amount, m.total_other, m.grand_total,
			c.client_company_name,
			COUNT(d.detail_id) AS gcn_count,
			GROUP_CONCAT(d.grn_no ORDER BY d.detail_id SEPARATOR ', ') AS gcn_list
		FROM billing_proforma_master m
		LEFT JOIN client c ON c.client_id = m.customer_id
		LEFT JOIN billing_proforma_details d ON d.billing_proforma_id = m.billing_proforma_id
		WHERE $where
		GROUP BY m.billing_proforma_id
		ORDER BY STR_TO_DATE(m.invoice_date, '%d-%m-%Y') ASC, m.billing_proforma_id ASC";

	$rows = array();
	$sum_freight = 0.0;
	$sum_sgst = 0.0;
	$sum_cgst = 0.0;
	$sum_igst = 0.0;
	$sum_other = 0.0;
	$sum_grand = 0.0;
	$sno = 0;

	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$sno++;
			$freight = (float) ($r['total_freight'] ?? 0);
			$sgst = (float) ($r['sgst_amount'] ?? 0);
			$cgst = (float) ($r['cgst_amount'] ?? 0);
			$igst = (float) ($r['igst_amount'] ?? 0);
			$other = (float) ($r['total_other'] ?? 0);
			$grand = (float) ($r['grand_total'] ?? 0);

			$sum_freight += $freight;
			$sum_sgst += $sgst;
			$sum_cgst += $cgst;
			$sum_igst += $igst;
			$sum_other += $other;
			$sum_grand += $grand;

			$customer = trim($r['client_company_name'] ?? '');
			if ($customer === '') {
				$customer = '—';
			}

			$pno = trim((string) ($r['proforma_no'] ?? ''));
			if ($pno === '') {
				$pno = 'DRAFT-' . (int) $r['billing_proforma_id'];
			}

			$bt = (string) ($r['billing_type'] ?? '');
			$doc_style = (string) ($r['doc_style'] ?? 'gst');
			$status_val = (string) ($r['status'] ?? '');

			$rows[] = array(
				's_no' => $sno,
				'proforma_no' => $pno,
				'invoice_date' => $r['invoice_date'] ?? '',
				'customer' => $customer,
				'status' => $status_val === 'final' ? 'Final' : ($status_val === 'draft' ? 'Draft' : ucfirst($status_val)),
				'doc_style' => ($doc_style === 'other') ? 'Without GST' : 'With GST',
				'billing_type' => isset($type_opts[$bt]) ? $type_opts[$bt] : strtoupper($bt),
				'freight_amount' => proforma_invoice_register_format_money($freight),
				'sgst_amount' => proforma_invoice_register_format_money($sgst),
				'cgst_amount' => proforma_invoice_register_format_money($cgst),
				'igst_amount' => proforma_invoice_register_format_money($igst),
				'other_charges' => proforma_invoice_register_format_money($other),
				'invoice_value' => proforma_invoice_register_format_money($grand),
				'gcn_count' => (int) ($r['gcn_count'] ?? 0),
				'gcn_list' => (string) ($r['gcn_list'] ?? ''),
			);
		}
	}

	return array(
		'rows' => $rows,
		'summary' => array(
			'total_freight' => proforma_invoice_register_format_money($sum_freight),
			'sgst_amount' => proforma_invoice_register_format_money($sum_sgst),
			'cgst_amount' => proforma_invoice_register_format_money($sum_cgst),
			'igst_amount' => proforma_invoice_register_format_money($sum_igst),
			'total_other' => proforma_invoice_register_format_money($sum_other),
			'grand_total' => proforma_invoice_register_format_money($sum_grand),
			'row_count' => count($rows),
		),
	);
}
