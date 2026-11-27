<?php

header('Content-Type: application/json; charset=utf-8');

require_once 'include/connect.php';
require_once 'include/function.php';
require_once 'include/gstr1_aggregates.php';

$cmd = isset($_REQUEST['cmd']) ? trim((string) $_REQUEST['cmd']) : 'summary';
$gstin = isset($_REQUEST['gstin']) ? gst_tax_normalize_gstin($_REQUEST['gstin']) : '';
$from = isset($_REQUEST['from_date']) ? trim((string) $_REQUEST['from_date']) : '';
$to = isset($_REQUEST['to_date']) ? trim((string) $_REQUEST['to_date']) : '';

if ($gstin === '') {
	$opts = gstr1_company_gstin_options($conn);
	if (!empty($opts[0]['gstin'])) {
		$gstin = $opts[0]['gstin'];
	}
}

$pack = gstr1_classify_period($conn, $gstin, $from, $to);
if (!empty($pack['error'])) {
	echo json_encode(array('ok' => false, 'error' => $pack['error']));
	exit;
}

$vouchers = $pack['vouchers'];
$response = array(
	'ok' => true,
	'context' => $pack['context'],
	'period' => $pack['period'],
	'filing' => $pack['filing'],
	'counts' => $pack['counts'],
);

switch ($cmd) {
	case 'summary':
		$response['return_view'] = gstr1_aggregate_return_view($vouchers);
		$response['not_relevant'] = gstr1_not_relevant_summary($vouchers);
		break;
	case 'statistics':
		$response['statistics'] = $pack['statistics'];
		$response['not_relevant'] = gstr1_not_relevant_summary($vouchers);
		break;
	case 'register':
		$filters = array(
			'bucket' => $_REQUEST['bucket'] ?? '',
			'included_sub' => $_REQUEST['included_sub'] ?? '',
			'voucher_type' => $_REQUEST['voucher_type'] ?? '',
			'not_relevant_group' => $_REQUEST['not_relevant_group'] ?? '',
			'not_relevant_subgroup' => $_REQUEST['not_relevant_subgroup'] ?? '',
			'party_gstin' => gst_tax_normalize_gstin($_REQUEST['party_gstin'] ?? ''),
			'section' => $_REQUEST['section'] ?? '',
			'exception_only' => !empty($_REQUEST['exception_only']),
		);
		$rows = gstr1_filter_vouchers($vouchers, $filters);
		$response['rows'] = gstr1_register_rows_payload($rows);
		$response['filters'] = $filters;
		break;
	case 'b2b':
		$response['b2b'] = gstr1_aggregate_b2b($vouchers);
		break;
	case 'hsn':
		$response['hsn'] = gstr1_aggregate_hsn($vouchers);
		break;
	case 'not_relevant':
		$response['not_relevant'] = gstr1_not_relevant_summary($vouchers);
		break;
	default:
		$response['error'] = 'Unknown command';
		$response['ok'] = false;
}

echo json_encode($response);
exit;

function gstr1_register_rows_payload(array $rows)
{
	$catalog = gstr1_voucher_type_catalog();
	$out = array();
	$sum_debit = 0.0;
	$sum_credit = 0.0;
	foreach ($rows as $v) {
		$label = $catalog[$v['voucher_type_key'] ?? ''] ?? ($v['voucher_type_key'] ?? '');
		$debit = round((float) ($v['debit'] ?? 0), 2);
		$credit = round((float) ($v['credit'] ?? 0), 2);
		if ($debit <= 0 && $v['invoice_amount'] > 0) {
			$debit = (float) $v['invoice_amount'];
		}
		$sum_debit += $debit;
		$sum_credit += $credit;
		$row_class = !empty($v['cancelled']) ? 'gstr1-cancelled' : '';
		$voucher_no = ($v['voucher_no'] ?? '');
		if (!empty($v['cancelled'])) {
			$voucher_no .= ' (Cancelled)';
		}
		$out[] = array(
			'voucher_date' => $v['voucher_date'] ?? '',
			'particulars' => $v['particulars'] ?? '',
			'voucher_type' => $label,
			'voucher_no' => $voucher_no,
			'debit_fmt' => $debit > 0 ? gstr1_money($debit) : '',
			'credit_fmt' => $credit > 0 ? gstr1_money($credit) : '',
			'qty_inward' => (int) ($v['qty_inward'] ?? 0) ?: '',
			'qty_outward' => (int) ($v['qty_outward'] ?? 0) ?: '',
			'view_url' => gstr1_voucher_readonly_url($v),
			'row_class' => $row_class,
		);
	}
	return array(
		'rows' => $out,
		'total_debit_fmt' => gstr1_money($sum_debit),
		'total_credit_fmt' => gstr1_money($sum_credit),
		'row_count' => count($out),
	);
}