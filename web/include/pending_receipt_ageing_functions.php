<?php

/**
 * Pending Receipt Register / Ageing
 * Amounts still pending to be received (outstanding invoice balances), aged vs current date.
 * Reuses the same settlement + bucket logic as Pending Invoice Ageing.
 */

require_once __DIR__ . '/pending_invoice_ageing_functions.php';

if (!defined('PENDING_RECEIPT_DUE_DAYS')) {
	// Due = invoice date; days overdue / buckets use current date (same as invoice ageing).
	define('PENDING_RECEIPT_DUE_DAYS', (int) PENDING_INVOICE_DUE_DAYS);
}

function pending_receipt_ageing_format_money($val)
{
	return pending_invoice_ageing_format_money($val);
}

function pending_receipt_ageing_parse_filters($request)
{
	return pending_invoice_ageing_parse_filters($request);
}

function pending_receipt_ageing_bucket_label($key)
{
	return pending_invoice_ageing_bucket_label($key);
}

function pending_receipt_ageing_empty_bucket_totals()
{
	return pending_invoice_ageing_empty_bucket_totals();
}

function pending_receipt_ageing_empty_summary()
{
	return pending_invoice_ageing_empty_summary();
}

function pending_receipt_ageing_fetch_rows($conn, $filters)
{
	return pending_invoice_ageing_fetch_rows($conn, $filters);
}
