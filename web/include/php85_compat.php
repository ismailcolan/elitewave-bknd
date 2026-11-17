<?php
/**
 * PHP 8.5 compatibility layer for EliteWave (migrating from PHP 7.4).
 * Loaded by database_config.php / connect.php for all web + CLI entry points.
 */

/* ---- Removed / changed language features ---- */

if (!function_exists('get_magic_quotes_runtime')) {
	function get_magic_quotes_runtime()
	{
		return false;
	}
}
if (!function_exists('get_magic_quotes_gpc')) {
	function get_magic_quotes_gpc()
	{
		return false;
	}
}
if (!function_exists('set_magic_quotes_runtime')) {
	function set_magic_quotes_runtime($new_setting)
	{
		return false;
	}
}

/** each() removed in PHP 8.0 — still used by TCPDF 6.2 */
if (!function_exists('each')) {
	function each(&$array)
	{
		if (!is_array($array)) {
			return false;
		}
		$key = key($array);
		if ($key === null) {
			return false;
		}
		$value = current($array);
		next($array);
		return array(1 => $value, 'value' => $value, 0 => $key, 'key' => $key);
	}
}

/** FILTER_SANITIZE_STRING removed in PHP 8.1 */
if (!defined('FILTER_SANITIZE_STRING')) {
	define('FILTER_SANITIZE_STRING', 513);
}

/* ---- Safe helpers for legacy mysqli / array usage ---- */

if (!function_exists('ew_count')) {
	function ew_count($value)
	{
		return is_countable($value) ? count($value) : 0;
	}
}

if (!function_exists('ew_mysqli_num_rows')) {
	function ew_mysqli_num_rows($result)
	{
		return ($result instanceof mysqli_result) ? mysqli_num_rows($result) : 0;
	}
}

if (!function_exists('ew_mysqli_fetch_assoc')) {
	function ew_mysqli_fetch_assoc($result)
	{
		return ($result instanceof mysqli_result) ? mysqli_fetch_assoc($result) : null;
	}
}

if (!function_exists('ew_mysqli_fetch_array')) {
	function ew_mysqli_fetch_array($result, $mode = MYSQLI_BOTH)
	{
		return ($result instanceof mysqli_result) ? mysqli_fetch_array($result, $mode) : null;
	}
}

if (!function_exists('ew_arr')) {
	/**
	 * Safe array access: ew_arr($row, 'field', 'default')
	 */
	function ew_arr($arr, $key, $default = '')
	{
		return (is_array($arr) && array_key_exists($key, $arr)) ? $arr[$key] : $default;
	}
}

if (!function_exists('ew_req')) {
	/**
	 * Safe request value (string).
	 */
	function ew_req($key, $default = '')
	{
		if (!isset($_REQUEST[$key])) {
			return $default;
		}
		$v = $_REQUEST[$key];
		return is_array($v) ? $default : (string) $v;
	}
}

if (!function_exists('ew_files_names')) {
	/**
	 * Safe multi-upload name list from $_FILES[$field]['name'].
	 */
	function ew_files_names($field)
	{
		if (!isset($_FILES[$field]['name'])) {
			return array();
		}
		$names = $_FILES[$field]['name'];
		return is_array($names) ? $names : array($names);
	}
}
