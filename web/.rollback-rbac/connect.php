<?php
error_reporting(0);
require_once __DIR__ . '/database_config.php';
session_start();
$conn = mysqli_connect(EW_DB_HOST, EW_DB_USER, EW_DB_PASS) or die(mysqli_error());
$db = mysqli_select_db($conn, EW_DB_NAME) or die(mysqli_error());
date_default_timezone_set('Asia/Kolkata');
/** Set true when Invoice Dashboard (dashboard_invoice.php) should be visible in menu and reachable. */
if (!defined('EW_INVOICE_DASHBOARD_LIVE')) {
	define('EW_INVOICE_DASHBOARD_LIVE', true);
}
/** Set true when Payments Dashboard (dashboard_payments.php) should be visible in menu and reachable. */
if (!defined('EW_PAYMENTS_DASHBOARD_LIVE')) {
	define('EW_PAYMENTS_DASHBOARD_LIVE', true);
}
/** Set true when Expenses & P&L Dashboard (dashboard_pl.php) should be visible in menu and reachable. */
if (!defined('EW_PL_DASHBOARD_LIVE')) {
	define('EW_PL_DASHBOARD_LIVE', true);
}
define('site_path', 'https://elitewave360.in/web/' );
define('site_paths', 'https://elitewave360.in/' );

define('login_check', ' where company_id="'.$_SESSION['company_id'].'"' );
?>