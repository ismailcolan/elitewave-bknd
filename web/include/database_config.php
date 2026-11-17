<?php
/**
 * Database connection settings (shared by web app and CLI jobs).
 * Auto-selects staging vs live from host so both environments work
 * without overwriting each other.
 */
require_once __DIR__ . '/php85_compat.php';
if (!function_exists('ew_is_staging_host')) {
	function ew_is_staging_host($host)
	{
		$host = strtolower(preg_replace('/:\d+$/', '', (string) $host));
		if ($host === '') {
			return false;
		}
		return (
			strpos($host, 'colanapps.in') !== false
			|| strpos($host, 'demosrv') !== false
			|| $host === '192.168.4.42'
		);
	}
}

if (!function_exists('ew_detect_environment')) {
	function ew_detect_environment()
	{
		// Prefer the request host so the same box can serve the correct env.
		if (!empty($_SERVER['HTTP_HOST'])) {
			return ew_is_staging_host($_SERVER['HTTP_HOST']) ? 'staging' : 'live';
		}
		if (!empty($_SERVER['SERVER_NAME'])) {
			return ew_is_staging_host($_SERVER['SERVER_NAME']) ? 'staging' : 'live';
		}
		// CLI / cron: fall back to machine hostname.
		return ew_is_staging_host(gethostname()) ? 'staging' : 'live';
	}
}

if (!defined('EW_ENV')) {
	define('EW_ENV', ew_detect_environment());
}

if (EW_ENV === 'staging') {
	// Staging: https://elitewave360.colanapps.in (demosrv / 192.168.4.42)
	define('EW_DB_HOST', 'localhost');
	define('EW_DB_USER', 'elitewave360');
	define('EW_DB_PASS', 'zXmHHS0w');
	define('EW_DB_NAME', 'elitewave360web');
	define('EW_SITE_BASE', 'https://elitewave360.colanapps.in/');
	define('EW_SITE_WEB', 'https://elitewave360.colanapps.in/web/');
} else {
	// Live: https://elitewave360.in
	define('EW_DB_HOST', 'localhost');
	define('EW_DB_USER', 'elitewave360');
	define('EW_DB_PASS', 'oIWUIhhjF9Tsm0K2lT0GNHtWD');
	define('EW_DB_NAME', 'elitewave360web');
	define('EW_SITE_BASE', 'https://elitewave360.in/');
	define('EW_SITE_WEB', 'https://elitewave360.in/web/');
}
