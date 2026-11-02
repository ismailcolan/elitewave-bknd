<?php

/**
 * General masters — detect records referenced elsewhere; lock edit UI when in use.
 */

function ew_master_used_edit_message()
{
	return 'Sorry, this record is already in use and cannot be edited.';
}

function ew_master_transaction_suffixes($conn)
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$cache = array();
	$q = mysqli_query($conn, 'SELECT table_name FROM transaction_tbls');
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$suffix = preg_replace('/[^0-9_]/', '', (string) ($row['table_name'] ?? ''));
			if ($suffix !== '') {
				$cache[] = $suffix;
			}
		}
	}
	return $cache;
}

function ew_master_sql_table_exists($conn, $table)
{
	static $cache = array();
	$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
	if ($table === '') {
		return false;
	}
	if (array_key_exists($table, $cache)) {
		return $cache[$table];
	}
	$q = @mysqli_query($conn, "SELECT 1 FROM `$table` LIMIT 1");
	$cache[$table] = (bool) $q;
	return $cache[$table];
}

/** @return int[] ids from $candidate_ids that appear in $table.$column */
function ew_master_ids_found_in_table($conn, $table, $column, array $candidate_ids)
{
	$column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
	$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
	$candidate_ids = array_values(array_unique(array_map('intval', $candidate_ids)));
	if ($column === '' || $table === '' || $candidate_ids === array()) {
		return array();
	}
	if (!ew_master_sql_table_exists($conn, $table)) {
		return array();
	}
	$chk = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		return array();
	}
	$in = implode(',', $candidate_ids);
	$found = array();
	$q = mysqli_query($conn, "SELECT DISTINCT `$column` AS v FROM `$table` WHERE `$column` IN ($in)");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$found[] = (int) $row['v'];
		}
	}
	return $found;
}

function ew_master_mark_ids_in_map(array &$map, array $found_ids)
{
	foreach ($found_ids as $id) {
		if (isset($map[$id])) {
			$map[$id] = true;
		}
	}
}

function ew_master_scan_transaction_columns($conn, array &$map, array $columns, array $candidate_ids)
{
	foreach (ew_master_transaction_suffixes($conn) as $suffix) {
		$table = 'transaction_' . $suffix;
		foreach ($columns as $column) {
			ew_master_mark_ids_in_map($map, ew_master_ids_found_in_table($conn, $table, $column, $candidate_ids));
		}
	}
}

function ew_master_scan_transaction_invoice_package($conn, array &$map, array $candidate_ids)
{
	$candidate_ids = array_values(array_unique(array_map('intval', $candidate_ids)));
	if ($candidate_ids === array()) {
		return;
	}
	$codes = array();
	foreach ($candidate_ids as $pid) {
		$q = mysqli_query($conn, "SELECT package_code FROM package WHERE package_id='$pid' LIMIT 1");
		if ($q && ($row = mysqli_fetch_assoc($q))) {
			$codes[$pid] = mysqli_real_escape_string($conn, (string) $row['package_code']);
		}
	}
	$in_ids = implode(',', $candidate_ids);
	foreach (ew_master_transaction_suffixes($conn) as $suffix) {
		$table = 'transaction_invoice_' . $suffix;
		if (!ew_master_sql_table_exists($conn, $table)) {
			continue;
		}
		$q = mysqli_query($conn, "SELECT DISTINCT type_of_pkge AS v FROM `$table` WHERE type_of_pkge IN ($in_ids)");
		if ($q) {
			while ($row = mysqli_fetch_assoc($q)) {
				$v = (int) $row['v'];
				if (isset($map[$v])) {
					$map[$v] = true;
				}
			}
		}
		foreach ($codes as $pid => $code_esc) {
			if ($code_esc === '') {
				continue;
			}
			$q2 = mysqli_query($conn, "SELECT 1 FROM `$table` WHERE type_of_pkge='$code_esc' LIMIT 1");
			if ($q2 && mysqli_num_rows($q2) > 0 && isset($map[$pid])) {
				$map[$pid] = true;
			}
		}
	}
}

function ew_master_expense_type_in_use($conn, $category_id)
{
	require_once __DIR__ . '/expense_type_helpers.php';
	$category_id = (int) $category_id;
	if ($category_id <= 0) {
		return false;
	}
	$used = 0;
	if (expense_type_table_exists($conn, 'gcn_expense_lines')) {
		$q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM gcn_expense_lines WHERE category_id='$category_id'");
		$row = $q ? mysqli_fetch_assoc($q) : array('c' => 0);
		$used += (int) ($row['c'] ?? 0);
	}
	if (expense_type_table_exists($conn, 'general_expense_header')) {
		$q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM general_expense_header WHERE category_id='$category_id'");
		$row = $q ? mysqli_fetch_assoc($q) : array('c' => 0);
		$used += (int) ($row['c'] ?? 0);
	}
	if (expense_type_table_exists($conn, 'general_expense_lines')) {
		$q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM general_expense_lines WHERE category_id='$category_id'");
		$row = $q ? mysqli_fetch_assoc($q) : array('c' => 0);
		$used += (int) ($row['c'] ?? 0);
	}
	return $used > 0;
}

function ew_master_expense_group_in_use($conn, $group_id)
{
	require_once __DIR__ . '/expense_type_helpers.php';
	$group_id = (int) $group_id;
	if ($group_id <= 0) {
		return false;
	}
	if (!expense_type_column_exists($conn, 'expense_group_id')) {
		return false;
	}
	$q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM expense_category WHERE expense_group_id='$group_id'");
	$row = $q ? mysqli_fetch_assoc($q) : array('c' => 0);
	return (int) ($row['c'] ?? 0) > 0;
}

function ew_master_vehicle_type_in_use($conn, $vehicle_type_id)
{
	require_once __DIR__ . '/vehicle_type_helpers.php';
	$row = ew_vehicle_type_get($conn, (int) $vehicle_type_id);
	if (!$row) {
		return false;
	}
	$code_esc = mysqli_real_escape_string($conn, $row['type_code']);
	$name_esc = mysqli_real_escape_string($conn, $row['type_name']);
	$q = mysqli_query($conn, "SELECT vehicle_id FROM vehicle WHERE (vehicle_type='$code_esc' OR vehicle_type='$name_esc') LIMIT 1");
	return ($q && mysqli_num_rows($q) > 0);
}

function ew_master_gst_tax_in_use($conn, $gst_tax_id)
{
	$gst_tax_id = (int) $gst_tax_id;
	if ($gst_tax_id <= 0) {
		return false;
	}
	$map = array($gst_tax_id => false);
	ew_master_scan_transaction_columns($conn, $map, array('gst_tax_id'), array($gst_tax_id));
	return !empty($map[$gst_tax_id]);
}

function ew_client_is_linked($conn, $client_id)
{
	$client_id = (int) $client_id;
	if ($client_id <= 0) {
		return false;
	}
	$refs = array(
		array('client_branch', 'company_id'),
		array('customer_mapping', 'client'),
		array('customer_mapping_lists', 'client_id'),
		array('client_outstanding', 'client_id'),
	);
	if (ew_master_sql_table_exists($conn, 'billing_invoice_master')) {
		$refs[] = array('billing_invoice_master', 'customer_id');
	}
	if (ew_master_sql_table_exists($conn, 'billing_receipt_master')) {
		$refs[] = array('billing_receipt_master', 'party_id');
	}
	if (ew_master_sql_table_exists($conn, 'billing_note_master')) {
		$refs[] = array('billing_note_master', 'party_id');
	}
	foreach ($refs as $ref) {
		$table = $ref[0];
		$col = $ref[1];
		if (!ew_master_sql_table_exists($conn, $table)) {
			continue;
		}
		$q = mysqli_query($conn, "SELECT 1 FROM `$table` WHERE `$col`='$client_id' LIMIT 1");
		if ($q && mysqli_num_rows($q) > 0) {
			return true;
		}
	}
	$map = array($client_id => false);
	ew_master_scan_transaction_columns($conn, $map, array('consigner', 'consignee', 'client_id'), array($client_id));
	if (!empty($map[$client_id])) {
		return true;
	}
	$map = array($client_id => false);
	ew_master_scan_transaction_columns($conn, $map, array('ship_to_client'), array($client_id));
	return !empty($map[$client_id]);
}

function ew_master_vendor_in_use($conn, $vendor_id)
{
	require_once __DIR__ . '/vendor_master_helpers.php';
	return ew_vendor_is_linked($conn, (int) $vendor_id);
}

/**
 * @param string $entity state|city|branch|package|mode|consignment|train|flight|cfs|gst_tax|expense_type|expense_group|vehicle_type|client|vendor
 * @param int[] $ids
 * @return array<int,bool> id => in use
 */
function ew_master_usage_map($conn, $entity, array $ids)
{
	$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
	$map = array();
	foreach ($ids as $id) {
		$map[$id] = false;
	}
	if ($ids === array()) {
		return $map;
	}
	$in = implode(',', $ids);

	switch ($entity) {
		case 'state':
			ew_master_mark_ids_in_map($map, ew_master_ids_found_in_table($conn, 'city', 'state', $ids));
			ew_master_mark_ids_in_map($map, ew_master_ids_found_in_table($conn, 'branch', 'state', $ids));
			ew_master_scan_transaction_columns($conn, $map, array('state', 'con_state', 'bill_to_state_id'), $ids);
			break;

		case 'city':
			ew_master_mark_ids_in_map($map, ew_master_ids_found_in_table($conn, 'branch', 'city', $ids));
			if (ew_master_sql_table_exists($conn, 'hub')) {
				ew_master_mark_ids_in_map($map, ew_master_ids_found_in_table($conn, 'hub', 'location_city_id', $ids));
			}
			ew_master_scan_transaction_columns($conn, $map, array('origin', 'destination', 'city', 'con_city'), $ids);
			break;

		case 'branch':
			ew_master_scan_transaction_columns($conn, $map, array(
				'consignor_branch_id', 'bill_to_branch_id', 'consignee_branch_id',
			), $ids);
			break;

		case 'package':
			ew_master_scan_transaction_invoice_package($conn, $map, $ids);
			break;

		case 'mode':
			ew_master_scan_transaction_columns($conn, $map, array('mode_of_transportation'), $ids);
			break;

		case 'consignment':
			ew_master_scan_transaction_columns($conn, $map, array('mode_of_consignment'), $ids);
			break;

		case 'train':
			ew_master_scan_transaction_columns($conn, $map, array('train_type'), $ids);
			break;

		case 'flight':
			ew_master_scan_transaction_columns($conn, $map, array('flight_type', 'flight_name'), $ids);
			break;

		case 'cfs':
			ew_master_scan_transaction_columns($conn, $map, array('cfs'), $ids);
			break;

		case 'gst_tax':
			foreach ($ids as $id) {
				if (ew_master_gst_tax_in_use($conn, $id)) {
					$map[$id] = true;
				}
			}
			break;

		case 'expense_type':
			foreach ($ids as $id) {
				if (ew_master_expense_type_in_use($conn, $id)) {
					$map[$id] = true;
				}
			}
			break;

		case 'expense_group':
			foreach ($ids as $id) {
				if (ew_master_expense_group_in_use($conn, $id)) {
					$map[$id] = true;
				}
			}
			break;

		case 'vehicle_type':
			foreach ($ids as $id) {
				if (ew_master_vehicle_type_in_use($conn, $id)) {
					$map[$id] = true;
				}
			}
			break;

		case 'client':
			$client_refs = array(
				array('client_branch', 'company_id'),
				array('customer_mapping', 'client'),
				array('customer_mapping_lists', 'client_id'),
				array('client_outstanding', 'client_id'),
				array('billing_invoice_master', 'customer_id'),
				array('billing_receipt_master', 'party_id'),
				array('billing_note_master', 'party_id'),
			);
			foreach ($client_refs as $ref) {
				ew_master_mark_ids_in_map($map, ew_master_ids_found_in_table($conn, $ref[0], $ref[1], $ids));
			}
			ew_master_scan_transaction_columns($conn, $map, array('consigner', 'consignee', 'client_id'), $ids);
			ew_master_scan_transaction_columns($conn, $map, array('ship_to_client'), $ids);
			break;

		case 'vendor':
			$vendor_refs = array(
				array('general_expense_header', 'vendor_id'),
				array('gcn_expense_lines', 'vendor_id'),
				array('transactions', 'vendor_id'),
				array('trip', 'vendor_id'),
			);
			foreach ($vendor_refs as $ref) {
				ew_master_mark_ids_in_map($map, ew_master_ids_found_in_table($conn, $ref[0], $ref[1], $ids));
			}
			break;

		default:
			break;
	}

	return $map;
}

function ew_master_record_in_use($conn, $entity, $id)
{
	$map = ew_master_usage_map($conn, $entity, array((int) $id));
	$id = (int) $id;
	return !empty($map[$id]);
}

/**
 * Render edit control or locked icon with toast message.
 *
 * Options:
 * - in_use (bool)
 * - mode: button|link
 * - button_class, button_id (mode button)
 * - href, link_class (mode link)
 * - title_edit, message
 */
function ew_master_edit_action_html(array $opts)
{
	$in_use = !empty($opts['in_use']);
	$message = isset($opts['message']) ? (string) $opts['message'] : ew_master_used_edit_message();
	$title_edit = isset($opts['title']) ? (string) $opts['title'] : 'Edit';

	if ($in_use) {
		$msg_esc = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
		return '<span role="button" tabindex="0" class="table-actions ew-master-edit-locked" title="In use — not editable" data-message="' . $msg_esc . '"><i class="fa fa-lock"></i></span>';
	}

	$mode = isset($opts['mode']) ? $opts['mode'] : 'button';
	if ($mode === 'link') {
		$href = isset($opts['href']) ? (string) $opts['href'] : '#';
		$class = isset($opts['link_class']) ? (string) $opts['link_class'] : 'table-actions btn-edit';
		$href_esc = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
		$class_esc = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
		$title_esc = htmlspecialchars($title_edit, ENT_QUOTES, 'UTF-8');
		return '<a title="' . $title_esc . '" class="' . $class_esc . '" href="' . $href_esc . '"><i class="fa fa-pencil"></i></a>';
	}

	$class = isset($opts['button_class']) ? (string) $opts['button_class'] : 'table-actions btn-edit';
	$id = isset($opts['button_id']) ? (string) $opts['button_id'] : '';
	$class_esc = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
	$title_esc = htmlspecialchars($title_edit, ENT_QUOTES, 'UTF-8');
	$id_attr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
	return '<a title="' . $title_esc . '" class="' . $class_esc . '"' . $id_attr . '><i class="fa fa-pencil"></i></a>';
}
