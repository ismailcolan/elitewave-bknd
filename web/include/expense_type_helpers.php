<?php
require_once __DIR__ . '/expense_schema.php';

function expense_type_column_exists($conn, $column)
{
	$column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
	$q = mysqli_query($conn, "SHOW COLUMNS FROM expense_category LIKE '$column'");
	return ($q && mysqli_num_rows($q) > 0);
}

function expense_type_table_exists($conn, $table)
{
	$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
	$q = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
	return ($q && mysqli_num_rows($q) > 0);
}

function expense_type_master_names()
{
	return array(
		'Freight Charges',
		'Broker/Commission',
		'Loading',
		'Unloading',
		'Mamool (loading)',
		'Mamool (unloading)',
		'Pick up Charges',
		'Delivery Charges',
		'Railway Gatepass',
		'TPC',
		'Parcel Office',
		'Document Charges',
		'Other Expenses',
		'Stationery Expenses',
	);
}

function expense_group_default_rows()
{
	return array(
		array('direct_trip', 'Direct / Trip', 10),
		array('indirect_office', 'Indirect / Office', 20),
		array('other', 'Other', 30),
	);
}

function expense_group_ensure_schema($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS expense_group (
		group_id INT(11) NOT NULL AUTO_INCREMENT,
		group_code VARCHAR(50) NOT NULL,
		group_name VARCHAR(150) NOT NULL,
		sort_no INT(11) NOT NULL DEFAULT 0,
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (group_id),
		UNIQUE KEY uk_expense_group_code (group_code)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	if (!expense_type_column_exists($conn, 'expense_group_id')) {
		mysqli_query($conn, "ALTER TABLE expense_category ADD COLUMN expense_group_id INT(11) NOT NULL DEFAULT 0 AFTER category_name");
	}

	$cnt_q = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM expense_group');
	$cnt_row = $cnt_q ? mysqli_fetch_assoc($cnt_q) : array();
	if ((int) ($cnt_row['cnt'] ?? 0) === 0) {
		$today = date('d-m-Y');
		$n = 1;
		foreach (expense_group_default_rows() as $row) {
			$code = mysqli_real_escape_string($conn, 'GRP' . sprintf('%05d', $n));
			$name = mysqli_real_escape_string($conn, $row[1]);
			$sort = (int) $row[2];
			mysqli_query($conn, "INSERT INTO expense_group (group_code, group_name, sort_no, status, created_at)
				VALUES ('$code', '$name', '$sort', 0, '$today')");
			$n++;
		}
	}

	expense_group_resequence_codes($conn);

	if (expense_type_column_exists($conn, 'expense_group')) {
		$q = mysqli_query($conn, "SELECT category_id, expense_group, expense_group_id FROM expense_category
			WHERE expense_group_id=0 OR expense_group_id IS NULL");
		if ($q) {
			while ($row = mysqli_fetch_assoc($q)) {
				$id = (int) $row['category_id'];
				$gid = expense_group_id_from_code($conn, $row['expense_group'] ?? '');
				if ($gid <= 0) {
					$gid = expense_group_id_from_code($conn, 'other');
				}
				mysqli_query($conn, "UPDATE expense_category SET expense_group_id='$gid' WHERE category_id='$id' LIMIT 1");
			}
		}
	}
}

function expense_group_list($conn, $active_only = false)
{
	expense_group_ensure_schema($conn);
	$rows = array();
	$sql = 'SELECT * FROM expense_group';
	if ($active_only) {
		$sql .= ' WHERE status=0';
	}
	$sql .= ' ORDER BY sort_no ASC, group_name ASC, group_id ASC';
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function expense_group_get($conn, $group_id)
{
	$group_id = (int) $group_id;
	if ($group_id <= 0) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT * FROM expense_group WHERE group_id='$group_id' LIMIT 1");
	return ($q && ($row = mysqli_fetch_assoc($q))) ? $row : null;
}

function expense_group_id_from_code($conn, $code)
{
	$code = strtolower(trim((string) $code));
	if ($code === '') {
		return 0;
	}
	if (ctype_digit($code)) {
		$g = expense_group_get($conn, (int) $code);
		return $g ? (int) $g['group_id'] : 0;
	}
	$code_esc = mysqli_real_escape_string($conn, $code);
	$q = mysqli_query($conn, "SELECT group_id FROM expense_group WHERE LOWER(group_code)='$code_esc' LIMIT 1");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	$gid = (int) ($row['group_id'] ?? 0);
	if ($gid > 0) {
		return $gid;
	}
	foreach (expense_group_default_rows() as $def) {
		if ($def[0] !== $code) {
			continue;
		}
		$name_esc = mysqli_real_escape_string($conn, $def[1]);
		$nq = mysqli_query($conn, "SELECT group_id FROM expense_group WHERE LOWER(group_name)=LOWER('$name_esc') ORDER BY group_id ASC LIMIT 1");
		$nrow = $nq ? mysqli_fetch_assoc($nq) : null;
		return (int) ($nrow['group_id'] ?? 0);
	}
	return 0;
}

function expense_group_next_code($conn)
{
	$row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS n FROM expense_group'));
	$n = (int) ($row['n'] ?? 0) + 1;
	if ($n < 1) {
		$n = 1;
	}
	return 'GRP' . sprintf('%05d', $n);
}

function expense_group_resequence_codes($conn)
{
	$q = mysqli_query($conn, 'SELECT group_id, group_code FROM expense_group ORDER BY sort_no ASC, group_name ASC, group_id ASC');
	if (!$q) {
		return;
	}
	$rows = array();
	while ($row = mysqli_fetch_assoc($q)) {
		$rows[] = $row;
	}
	$n = 1;
	$needs = false;
	foreach ($rows as $row) {
		$expected = 'GRP' . sprintf('%05d', $n);
		if (($row['group_code'] ?? '') !== $expected) {
			$needs = true;
			break;
		}
		$n++;
	}
	if (!$needs) {
		return;
	}
	foreach ($rows as $row) {
		$id = (int) $row['group_id'];
		$tmp = mysqli_real_escape_string($conn, '__TMP_GRP_' . $id);
		mysqli_query($conn, "UPDATE expense_group SET group_code='$tmp' WHERE group_id='$id' LIMIT 1");
	}
	$n = 1;
	foreach ($rows as $row) {
		$id = (int) $row['group_id'];
		$code = 'GRP' . sprintf('%05d', $n);
		$code_esc = mysqli_real_escape_string($conn, $code);
		mysqli_query($conn, "UPDATE expense_group SET group_code='$code_esc' WHERE group_id='$id' LIMIT 1");
		if (expense_type_column_exists($conn, 'expense_group')) {
			mysqli_query($conn, "UPDATE expense_category SET expense_group='$code_esc' WHERE expense_group_id='$id'");
		}
		$n++;
	}
}

function expense_group_save($conn, $payload, $edit_id = 0, $user_id = 0)
{
	expense_group_ensure_schema($conn);
	$name = trim((string) ($payload['group_name'] ?? ''));
	if ($name === '') {
		return array('ok' => false, 'message' => 'Please enter expense group name.');
	}
	$edit_id = (int) $edit_id;
	$user_id = (int) $user_id;
	$now = date('d-m-Y');
	$name_esc = mysqli_real_escape_string($conn, $name);
	$dup = "SELECT group_id FROM expense_group WHERE LOWER(group_name)=LOWER('$name_esc')";
	if ($edit_id > 0) {
		$dup .= " AND group_id!='$edit_id'";
	}
	$dup .= ' LIMIT 1';
	$chk = mysqli_query($conn, $dup);
	if ($chk && mysqli_num_rows($chk) > 0) {
		return array('ok' => false, 'message' => 'This expense group already exists.');
	}

	if ($edit_id > 0) {
		$exists = expense_group_get($conn, $edit_id);
		if (!$exists) {
			return array('ok' => false, 'message' => 'Expense group not found.');
		}
		$ok = mysqli_query($conn, "UPDATE expense_group SET
			group_name='$name_esc',
			updated_at='$now',
			updated_by='$user_id'
			WHERE group_id='$edit_id'");
		return array('ok' => (bool) $ok, 'message' => $ok ? 'Updated.' : 'Update failed.');
	}

	$code = mysqli_real_escape_string($conn, expense_group_next_code($conn));
	$sort_row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT MAX(sort_no) AS n FROM expense_group'));
	$sort = (int) ($sort_row['n'] ?? 0) + 10;
	$ok = mysqli_query($conn, "INSERT INTO expense_group (group_code, group_name, sort_no, status, created_at, created_by)
		VALUES ('$code', '$name_esc', '$sort', 0, '$now', '$user_id')");
	if ($ok) {
		expense_group_resequence_codes($conn);
	}
	return array('ok' => (bool) $ok, 'message' => $ok ? 'Saved.' : 'Save failed.');
}

function expense_group_delete($conn, $group_id)
{
	expense_group_ensure_schema($conn);
	$group_id = (int) $group_id;
	$g = expense_group_get($conn, $group_id);
	if (!$g) {
		return array('ok' => false, 'message' => 'Expense group not found.');
	}
	$used = 0;
	if (expense_type_column_exists($conn, 'expense_group_id')) {
		$q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM expense_category WHERE expense_group_id='$group_id'");
		$row = $q ? mysqli_fetch_assoc($q) : array('c' => 0);
		$used += (int) ($row['c'] ?? 0);
	}
	if ($used > 0) {
		return array('ok' => false, 'message' => 'Cannot delete. This group is used by expense types.');
	}
	$ok = mysqli_query($conn, "DELETE FROM expense_group WHERE group_id='$group_id' LIMIT 1");
	if ($ok) {
		expense_group_resequence_codes($conn);
	}
	return array('ok' => (bool) $ok, 'message' => $ok ? 'Deleted.' : 'Delete failed.');
}

function expense_type_groups($conn = null)
{
	if ($conn) {
		$out = array();
		foreach (expense_group_list($conn, true) as $g) {
			$out[$g['group_id']] = $g['group_name'];
		}
		if ($out) {
			return $out;
		}
	}
	$out = array();
	foreach (expense_group_default_rows() as $row) {
		$out[$row[0]] = $row[1];
	}
	return $out;
}

function expense_type_group_label($code, $conn = null)
{
	if ($conn && (int) $code > 0 && strlen((string) $code) < 12) {
		$g = expense_group_get($conn, (int) $code);
		if ($g) {
			return $g['group_name'];
		}
	}
	if ($conn) {
		$gid = expense_group_id_from_code($conn, $code);
		$g = $gid ? expense_group_get($conn, $gid) : null;
		if ($g) {
			return $g['group_name'];
		}
	}
	foreach (expense_group_default_rows() as $row) {
		if ($row[0] === strtolower(trim((string) $code))) {
			return $row[1];
		}
	}
	return trim((string) $code) !== '' ? (string) $code : 'Other';
}

function expense_type_normalize_group($code)
{
	$code = strtolower(trim((string) $code));
	foreach (expense_group_default_rows() as $row) {
		if ($row[0] === $code) {
			return $code;
		}
	}
	return $code !== '' ? $code : 'other';
}

function expense_type_guess_group($name)
{
	$key = strtolower(trim((string) $name));
	$office = array('stationery', 'office rent', 'office', 'salary', 'admin', 'rent');
	foreach ($office as $needle) {
		if ($needle !== '' && strpos($key, $needle) !== false) {
			return 'indirect_office';
		}
	}
	$trip = array(
		'freight', 'broker', 'commission', 'loading', 'unloading', 'mamool',
		'pick up', 'pickup', 'delivery', 'gatepass', 'tpc', 'parcel', 'document',
	);
	foreach ($trip as $needle) {
		if (strpos($key, $needle) !== false) {
			return 'direct_trip';
		}
	}
	return 'other';
}

function expense_type_ensure_schema($conn)
{
	expense_ensure_tables($conn);
	expense_drop_legacy_module_tables($conn);
	mysqli_query($conn, 'DROP TABLE IF EXISTS expense_type_label');

	$columns = array(
		'expense_code_id' => "ALTER TABLE expense_category ADD COLUMN expense_code_id INT(11) NOT NULL DEFAULT 0 AFTER category_id",
		'expense_type_code' => "ALTER TABLE expense_category ADD COLUMN expense_type_code VARCHAR(50) DEFAULT '' AFTER category_name",
		'sac_code' => "ALTER TABLE expense_category ADD COLUMN sac_code VARCHAR(20) DEFAULT '' AFTER category_name",
		'gst_applicable' => "ALTER TABLE expense_category ADD COLUMN gst_applicable TINYINT(1) NOT NULL DEFAULT 0 AFTER expense_type_code",
		'gst_percent' => "ALTER TABLE expense_category ADD COLUMN gst_percent DECIMAL(5,2) DEFAULT NULL AFTER gst_applicable",
		'tds_applicable' => "ALTER TABLE expense_category ADD COLUMN tds_applicable TINYINT(1) NOT NULL DEFAULT 0 AFTER gst_percent",
		'tds_percent' => "ALTER TABLE expense_category ADD COLUMN tds_percent DECIMAL(5,2) DEFAULT NULL AFTER tds_applicable",
		'expense_group' => "ALTER TABLE expense_category ADD COLUMN expense_group VARCHAR(32) NOT NULL DEFAULT '' AFTER category_name",
		'expense_group_id' => "ALTER TABLE expense_category ADD COLUMN expense_group_id INT(11) NOT NULL DEFAULT 0 AFTER expense_group",
		'default_amount' => "ALTER TABLE expense_category ADD COLUMN default_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER sac_code",
	);
	foreach ($columns as $col => $sql) {
		if (!expense_type_column_exists($conn, $col)) {
			mysqli_query($conn, $sql);
		}
	}

	expense_group_ensure_schema($conn);
	expense_type_backfill_groups($conn);

	$count_q = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM expense_category');
	$count_row = $count_q ? mysqli_fetch_assoc($count_q) : array();
	if ((int) ($count_row['cnt'] ?? 0) === 0) {
		expense_type_seed_defaults($conn);
	}
}

function expense_type_slug($name)
{
	$slug = strtoupper(preg_replace('/[^A-Z0-9]+/', '_', trim((string) $name)));
	$slug = trim($slug, '_');
	return $slug !== '' ? substr($slug, 0, 50) : 'TYPE';
}

function expense_type_backfill_groups($conn)
{
	if (!expense_type_column_exists($conn, 'expense_group')) {
		return;
	}
	$q = mysqli_query($conn, "SELECT category_id, category_name FROM expense_category
		WHERE expense_group IS NULL OR expense_group=''");
	if (!$q) {
		return;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$id = (int) $row['category_id'];
		$g = mysqli_real_escape_string($conn, expense_type_guess_group($row['category_name'] ?? ''));
		$gid = (int) expense_group_id_from_code($conn, expense_type_guess_group($row['category_name'] ?? ''));
		mysqli_query($conn, "UPDATE expense_category SET expense_group='$g', expense_group_id='$gid' WHERE category_id='$id' LIMIT 1");
	}
}

function expense_type_next_code($conn)
{
	$row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT MAX(expense_code_id) AS code_id FROM expense_category'));
	$id = (int) ($row['code_id'] ?? 0) + 1;
	return array(
		'expense_code_id' => $id,
		'expense_code' => 'EXP' . sprintf('%05d', $id),
	);
}

function expense_type_seed_defaults($conn)
{
	$today = date('d-m-Y');
	$names = expense_type_master_names();
	foreach ($names as $name) {
		$name_esc = mysqli_real_escape_string($conn, $name);
		$dup = mysqli_query($conn, "SELECT category_id FROM expense_category WHERE LOWER(category_name)=LOWER('$name_esc') LIMIT 1");
		if ($dup && mysqli_num_rows($dup) > 0) {
			continue;
		}
		$next = expense_type_next_code($conn);
		$code = mysqli_real_escape_string($conn, $next['expense_code']);
		$code_id = (int) $next['expense_code_id'];
		$slug = mysqli_real_escape_string($conn, expense_type_slug($name));
		$group = mysqli_real_escape_string($conn, expense_type_guess_group($name));
		mysqli_query($conn, "INSERT INTO expense_category
			(expense_code_id, category_code, category_name, expense_group, sac_code, expense_type_code, gst_applicable, gst_percent, tds_applicable, tds_percent, created_at, status)
			VALUES ('$code_id', '$code', '$name_esc', '$group', '', '$slug', '0', NULL, '0', NULL, '$today', 0)");
	}

	$legacy_names = array(
		'f_c' => true,
		'extra loading / unloading' => true,
		'halting / detention' => true,
		'driver allowance / batta' => true,
		'toll / penalty (extra)' => true,
		'repair (on trip)' => true,
		'miscellaneous extra' => true,
	);
	$q = mysqli_query($conn, 'SELECT category_id, category_name FROM expense_category');
	if (!$q) {
		return;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$key = strtolower(trim($row['category_name'] ?? ''));
		if (!isset($legacy_names[$key])) {
			continue;
		}
		expense_type_delete_row($conn, (int) $row['category_id'], false);
	}
}

function expense_type_parse_save_payload($post)
{
	$category_name = trim($post['category_name'] ?? $post['expense_name'] ?? '');
	$sac_code = strtoupper(trim($post['sac_code'] ?? ''));
	if ($category_name === '') {
		return array('ok' => false, 'message' => 'Please enter expense name.');
	}
	$group_in = trim((string) ($post['expense_group_id'] ?? $post['expense_group'] ?? ''));
	if ($group_in === '') {
		return array('ok' => false, 'message' => 'Please select expense group.');
	}
	$default_raw = str_replace(',', '', trim((string) ($post['default_amount'] ?? '')));
	$default_amount = $default_raw === '' ? 0 : round((float) $default_raw, 2);
	if ($default_amount < 0) {
		return array('ok' => false, 'message' => 'Default amount cannot be negative.');
	}
	return array(
		'ok' => true,
		'category_name' => $category_name,
		'sac_code' => $sac_code,
		'default_amount' => $default_amount,
		'expense_group_key' => $group_in,
		'expense_type_code' => expense_type_slug($category_name),
	);
}

function expense_type_quick_add_category($conn, $type_name, $created_by = 0)
{
	expense_type_ensure_schema($conn);
	$type_name = trim((string) $type_name);
	if ($type_name === '') {
		return array('ok' => false, 'message' => 'Expense name is required.');
	}
	$name_esc = mysqli_real_escape_string($conn, $type_name);
	$existing = mysqli_query($conn, "SELECT category_id, category_code, category_name FROM expense_category
		WHERE LOWER(category_name)=LOWER('$name_esc') AND status=0 ORDER BY category_id ASC LIMIT 1");
	if ($existing && ($row = mysqli_fetch_assoc($existing))) {
		return array(
			'ok' => true,
			'category_id' => (int) $row['category_id'],
			'category_code' => $row['category_code'] ?? '',
			'category_name' => $row['category_name'] ?? $type_name,
			'label' => trim((string) ($row['category_name'] ?? $type_name)),
		);
	}

	$result = expense_type_save_row($conn, array(
		'category_name' => $type_name,
		'sac_code' => '',
		'expense_group_id' => expense_group_id_from_code($conn, expense_type_guess_group($type_name)),
	), '', (int) $created_by, (int) $created_by);
	if (empty($result['ok'])) {
		return $result;
	}

	$lookup = mysqli_query($conn, "SELECT category_id, category_code, category_name FROM expense_category
		WHERE LOWER(category_name)=LOWER('$name_esc') ORDER BY category_id DESC LIMIT 1");
	$row = ($lookup && ($r = mysqli_fetch_assoc($lookup))) ? $r : array();
	$new_id = (int) ($row['category_id'] ?? 0);
	if ($new_id <= 0) {
		return array('ok' => false, 'message' => 'Expense type saved but could not be selected.');
	}

	return array(
		'ok' => true,
		'category_id' => $new_id,
		'category_code' => $row['category_code'] ?? '',
		'category_name' => $row['category_name'] ?? $type_name,
		'label' => trim((string) ($row['category_name'] ?? $type_name)),
	);
}

function expense_type_save_row($conn, $payload, $edit_key = '', $created_by = 0, $updated_by = 0)
{
	expense_type_ensure_schema($conn);
	$parsed = expense_type_parse_save_payload($payload);
	if (empty($parsed['ok'])) {
		return $parsed;
	}

	$name_esc = mysqli_real_escape_string($conn, $parsed['category_name']);
	$sac_esc = mysqli_real_escape_string($conn, $parsed['sac_code']);
	$default_amount = number_format((float) $parsed['default_amount'], 2, '.', '');
	$slug_esc = mysqli_real_escape_string($conn, $parsed['expense_type_code']);
	$group_row = null;
	$gid = (int) $parsed['expense_group_key'];
	if ($gid > 0) {
		$group_row = expense_group_get($conn, $gid);
	}
	if (!$group_row) {
		$gid = expense_group_id_from_code($conn, $parsed['expense_group_key']);
		$group_row = $gid ? expense_group_get($conn, $gid) : null;
	}
	if (!$group_row) {
		return array('ok' => false, 'message' => 'Please select a valid expense group.');
	}
	$gid = (int) $group_row['group_id'];
	$group_esc = mysqli_real_escape_string($conn, $group_row['group_code']);
	$now = date('d-m-Y');
	$created_by = (int) $created_by;
	$updated_by = (int) $updated_by;

	$edit_id = (int) $edit_key;
	if ($edit_id <= 0) {
		$edit_id = (int) ($payload['edit_id'] ?? $payload['category_id'] ?? 0);
	}
	if ($edit_id <= 0 && is_string($edit_key) && $edit_key !== '' && preg_match('/^[a-f0-9]{32}$/i', $edit_key)) {
		$key_esc = mysqli_real_escape_string($conn, $edit_key);
		$find = mysqli_query($conn, "SELECT category_id FROM expense_category WHERE md5(category_id)='$key_esc' LIMIT 1");
		$found = $find ? mysqli_fetch_assoc($find) : null;
		$edit_id = (int) ($found['category_id'] ?? 0);
	}

	$dup_sql = "SELECT category_id FROM expense_category WHERE LOWER(category_name)=LOWER('$name_esc')";
	if ($edit_id > 0) {
		$dup_sql .= " AND category_id!='$edit_id'";
	}
	$dup_sql .= ' LIMIT 1';
	$dup = mysqli_query($conn, $dup_sql);
	if ($dup && mysqli_num_rows($dup) > 0) {
		return array('ok' => false, 'message' => 'This expense name already exists.');
	}

	if ($edit_id > 0) {
		$exists = mysqli_query($conn, "SELECT category_id FROM expense_category WHERE category_id='$edit_id' LIMIT 1");
		if (!$exists || mysqli_num_rows($exists) === 0) {
			return array('ok' => false, 'message' => 'Expense type not found.');
		}
		$query = "UPDATE expense_category SET
			category_name='$name_esc',
			sac_code='$sac_esc',
			default_amount='$default_amount',
			expense_group='$group_esc',
			expense_group_id='$gid',
			expense_type_code='$slug_esc',
			updated_at='$now',
			updated_by='$updated_by'
			WHERE category_id='$edit_id'";
		$result = mysqli_query($conn, $query);
		return array('ok' => (bool) $result, 'message' => $result ? 'Updated.' : 'Update failed.');
	}

	$next = expense_type_next_code($conn);
	$expense_code = mysqli_real_escape_string($conn, $next['expense_code']);
	$expense_code_id = (int) $next['expense_code_id'];
	$query = "INSERT INTO expense_category (
		expense_code_id, category_code, category_name, expense_group, expense_group_id, sac_code, default_amount, expense_type_code,
		gst_applicable, gst_percent, tds_applicable, tds_percent,
		created_at, created_by, status
	) VALUES (
		'$expense_code_id', '$expense_code', '$name_esc', '$group_esc', '$gid', '$sac_esc', '$default_amount', '$slug_esc',
		'0', NULL, '0', NULL,
		'$now', '$created_by', 0
	)";
	$result = mysqli_query($conn, $query);
	return array('ok' => (bool) $result, 'message' => $result ? 'Saved.' : 'Save failed.');
}

function expense_type_delete_row($conn, $category_id, $ensure_schema = true)
{
	if ($ensure_schema) {
		expense_type_ensure_schema($conn);
	}
	$category_id = (int) $category_id;
	if ($category_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid expense type.');
	}

	$exists = mysqli_query($conn, "SELECT category_id FROM expense_category WHERE category_id='$category_id' LIMIT 1");
	if (!$exists || mysqli_num_rows($exists) === 0) {
		return array('ok' => false, 'message' => 'Expense type not found.');
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
	if ($used > 0) {
		return array('ok' => false, 'message' => 'Cannot delete. This expense type is used in expense entries.');
	}

	$ok = mysqli_query($conn, "DELETE FROM expense_category WHERE category_id='$category_id' LIMIT 1");
	return array('ok' => (bool) $ok, 'message' => $ok ? 'Deleted.' : 'Delete failed.');
}
