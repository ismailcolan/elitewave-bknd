<?php
/**
 * Rate Quotation module — live toggle (off until product owner enables).
 * To go live: set EW_QUOTATION_MODULE_ENABLED to true.
 */
if (!defined('EW_QUOTATION_MODULE_ENABLED')) {
	define('EW_QUOTATION_MODULE_ENABLED', true);
}

function ew_quotation_module_enabled()
{
	return EW_QUOTATION_MODULE_ENABLED === true;
}

function ew_quotation_module_deny_web()
{
	if (ew_quotation_module_enabled()) {
		return;
	}
	header('HTTP/1.1 404 Not Found');
	header('Location: dashboard.php');
	exit;
}
