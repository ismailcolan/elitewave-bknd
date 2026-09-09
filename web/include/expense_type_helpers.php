<?php
require_once __DIR__ . '/expense_schema.php';

function expense_type_column_exists($conn, $column)
{
	$column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
	$q = mysqli_query($conn, "SHOW COLUMNS FROM expense_category LIKE '$column'");
	return ($q && mysqli_num_rows($q) > 0);
}

function expense_type_ensure_schema($conn)
{
	expense_ensure_tables($conn);
	expense_drop_legacy_module_tables($conn);

	if (!expense_type_table_exists($conn, 'expense_type_label')) {
		mysqli_query($conn, "CREATE TABLE IF NOT EXISTS expense_type_label (
			type_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			type_code VARCHAR(50) NOT NULL,
			type_name VARCHAR(150) NOT NULL,
			status TINYINT(1) NOT NULL DEFAULT 0,
			created_at VARCHAR(20) DEFAULT NULL,
			created_by INT(11) DEFAULT NULL,
			UNIQUE KEY uk_expense_type_code (type_code)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}

	$columns = array(
		'expense_code_id' => "ALTER TABLE expense_category ADD COLUMN expense_code_id INT(11) NOT NULL DEFAULT 0 AFTER category_id",
		'expense_type_code' => "ALTER TABLE expense_category ADD COLUMN expense_type_code VARCHAR(50) DEFAULT '' AFTER category_name",
		'gst_applicable' => "ALTER TABLE expense_category ADD COLUMN gst_applicable TINYINT(1) NOT NULL DEFAULT 0 AFTER expense_type_code",
		'gst_percent' => "ALTER TABLE expense_category ADD COLUMN gst_percent DECIMAL(5,2) DEFAULT NULL AFTER gst_applicable",
		'tds_applicable' => "ALTER TABLE expense_category ADD COLUMN tds_applicable TINYINT(1) NOT NULL DEFAULT 0 AFTER gst_percent",
		'tds_percent' => "ALTER TABLE expense_category ADD COLUMN tds_percent DECIMAL(5,2) DEFAULT NULL AFTER tds_applicable",
	);
	foreach ($columns as $col => $sql) {
		if (!expense_type_column_exists($conn, $col)) {
			mysqli_query($conn, $sql);
		}
	}

	expense_type_seed_labels($conn);
	expense_type_migrate_legacy_codes($conn);
}

function expense_type_table_exists($conn, $table)
{
	$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
	$q = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
	return ($q && mysqli_num_rows($q) > 0);
}

function expense_type_seed_labels($conn)
{
	$cnt_q = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM expense_type_label');
	$cnt = 0;
	if ($cnt_q && ($r = mysqli_fetch_assoc($cnt_q))) {
		$cnt = (int) $r['c'];
	}
	if ($cnt > 0) {
		return;
	}
	$today = date('d-m-Y');
	$defaults = array(
		'HALTING' => 'Halting / Detention',
		'LOADING' => 'Extra Loading / Unloading',
		'DRIVER' => 'Driver Allowance / Batta',
		'TOLL' => 'Toll / Penalty (Extra)',
		'REPAIR' => 'Repair (On Trip)',
		'MISC' => 'Miscellaneous Extra',
	);
	foreach ($defaults as $code => $name) {
		$code_esc = mysqli_real_escape_string($conn, $code);
		$name_esc = mysqli_real_escape_string($conn, $name);
		mysqli_query($conn, "INSERT IGNORE INTO expense_type_label (type_code, type_name, status, created_at)
			VALUES ('$code_esc', '$name_esc', 0, '$today')");
	}
}

function expense_type_migrate_legacy_codes($conn)
{
	$q = mysqli_query($conn, "SELECT category_id, category_code, category_name, expense_code_id, expense_type_code FROM expense_category ORDER BY category_id ASC");
	if (!$q) {
		return;
	}
	$max_id = 0;
	while ($row = mysqli_fetch_assoc($q)) {
		$cid = (int) $row['category_id'];
		$updates = array();
		if ((int) ($row['expense_code_id'] ?? 0) <= 0) {
			$max_id++;
			$updates[] = "expense_code_id='$max_id'";
			if (!preg_match('/^EXP\d+$/i', $row['category_code'] ?? '')) {
				$new_code = 'EXP' . sprintf('%05d', $max_id);
				$updates[] = "category_code='" . mysqli_real_escape_string($conn, $new_code) . "'";
			}
		} else {
			$max_id = max($max_id, (int) $row['expense_code_id']);
		}
		if (trim($row['expense_type_code'] ?? '') === '' && trim($row['category_name'] ?? '') !== '') {
			$slug = expense_type_slug($row['category_name']);
			$label = expense_type_ensure_label_by_name($conn, $row['category_name'], $slug);
			if (!empty($label['type_code'])) {
				$updates[] = "expense_type_code='" . mysqli_real_escape_string($conn, $label['type_code']) . "'";
			}
		}
		if (!empty($updates)) {
			mysqli_query($conn, 'UPDATE expense_category SET ' . implode(', ', $updates) . " WHERE category_id='$cid'");
		}
	}
}

function expense_type_slug($name)
{
	$slug = strtoupper(preg_replace('/[^A-Z0-9]+/', '_', trim((string) $name)));
	$slug = trim($slug, '_');
	return $slug !== '' ? $slug : 'TYPE';
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

function expense_type_label_options($conn)
{
	expense_type_ensure_schema($conn);
	$options = array();
	$q = mysqli_query($conn, "SELECT type_code, type_name FROM expense_type_label WHERE status=0 ORDER BY type_name ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$options[$row['type_code']] = $row['type_name'];
		}
	}
	return $options;
}

function expense_type_label_name($conn, $code)
{
	$code = trim((string) $code);
	if ($code === '') {
		return '';
	}
	$code_esc = mysqli_real_escape_string($conn, $code);
	$q = mysqli_query($conn, "SELECT type_name FROM expense_type_label WHERE type_code='$code_esc' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return $row['type_name'];
	}
	return $code;
}

function expense_type_label_select_html($conn, $selected = '')
{
	$html = '<option value="">Select Expense Type</option>';
	foreach (expense_type_label_options($conn) as $code => $label) {
		$sel = ((string) $selected === (string) $code) ? ' selected' : '';
		$html .= '<option value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	return $html;
}

function expense_type_add_label($conn, $type_name, $created_by = 0)
{
	expense_type_ensure_schema($conn);
	$type_name = trim((string) $type_name);
	if ($type_name === '') {
		return array('ok' => false, 'message' => 'Expense type name is required.');
	}
	$type_code = expense_type_slug($type_name);
	$code_esc = mysqli_real_escape_string($conn, $type_code);
	$name_esc = mysqli_real_escape_string($conn, $type_name);
	$dup = mysqli_query($conn, "SELECT type_id FROM expense_type_label WHERE type_code='$code_esc' OR type_name='$name_esc' LIMIT 1");
	if ($dup && mysqli_num_rows($dup) > 0) {
		return array('ok' => false, 'message' => 'This expense type already exists.');
	}
	$now = date('d-m-Y');
	$created_by = (int) $created_by;
	$ok = mysqli_query($conn, "INSERT INTO expense_type_label (type_code, type_name, status, created_at, created_by)
		VALUES ('$code_esc', '$name_esc', 0, '$now', '$created_by')");
	if (!$ok) {
		return array('ok' => false, 'message' => 'Could not save expense type.');
	}
	return array('ok' => true, 'type_code' => $type_code, 'type_name' => $type_name);
}

function expense_type_ensure_label_by_name($conn, $type_name, $preferred_code = '')
{
	$type_name = trim((string) $type_name);
	if ($type_name === '') {
		return array('type_code' => '', 'type_name' => '');
	}
	$name_esc = mysqli_real_escape_string($conn, $type_name);
	$q = mysqli_query($conn, "SELECT type_code, type_name FROM expense_type_label WHERE type_name='$name_esc' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return $row;
	}
	$result = expense_type_add_label($conn, $type_name, 0);
	if (!empty($result['ok'])) {
		return array('type_code' => $result['type_code'], 'type_name' => $result['type_name']);
	}
	return array('type_code' => $preferred_code ?: expense_type_slug($type_name), 'type_name' => $type_name);
}

function expense_type_parse_save_payload($post)
{
	$expense_type_code = trim($post['expense_type_code'] ?? '');

	if ($expense_type_code === '') {
		return array('ok' => false, 'message' => 'Please select expense type.');
	}

	return array(
		'ok' => true,
		'expense_type_code' => $expense_type_code,
	);
}

function expense_type_quick_add_category($conn, $type_name, $created_by = 0)
{
	expense_type_ensure_schema($conn);
	$type_name = trim((string) $type_name);
	if ($type_name === '') {
		return array('ok' => false, 'message' => 'Expense type name is required.');
	}

	$type_code = expense_type_slug($type_name);
	$label_result = expense_type_add_label($conn, $type_name, $created_by);
	if (!empty($label_result['ok'])) {
		$type_code = $label_result['type_code'];
	} else {
		$name_esc = mysqli_real_escape_string($conn, $type_name);
		$lookup_label = mysqli_query($conn, "SELECT type_code FROM expense_type_label WHERE type_name='$name_esc' LIMIT 1");
		if (!$lookup_label || !($label_row = mysqli_fetch_assoc($lookup_label))) {
			return $label_result;
		}
		$type_code = $label_row['type_code'];
	}
	$type_code_esc = mysqli_real_escape_string($conn, $type_code);
	$existing = mysqli_query($conn, "SELECT category_id, category_code, category_name FROM expense_category
		WHERE expense_type_code='$type_code_esc' AND status=0 ORDER BY category_id ASC LIMIT 1");
	if ($existing && ($row = mysqli_fetch_assoc($existing))) {
		return array(
			'ok' => true,
			'category_id' => (int) $row['category_id'],
			'category_code' => $row['category_code'] ?? '',
			'category_name' => $row['category_name'] ?? $type_name,
			'label' => trim(($row['category_code'] ?? '') . ' - ' . ($row['category_name'] ?? $type_name), ' -'),
		);
	}

	$payload = array('expense_type_code' => $type_code);
	$result = expense_type_save_row($conn, $payload, '', (int) $created_by, (int) $created_by);
	if (empty($result['ok'])) {
		return $result;
	}

	$new_id = (int) mysqli_insert_id($conn);
	if ($new_id <= 0) {
		$lookup = mysqli_query($conn, "SELECT category_id, category_code, category_name FROM expense_category
			WHERE expense_type_code='$type_code_esc' ORDER BY category_id DESC LIMIT 1");
		if ($lookup && ($row = mysqli_fetch_assoc($lookup))) {
			$new_id = (int) $row['category_id'];
		}
	}
	if ($new_id <= 0) {
		return array('ok' => false, 'message' => 'Expense type saved but category could not be selected.');
	}

	$cat = mysqli_query($conn, "SELECT category_id, category_code, category_name FROM expense_category WHERE category_id='$new_id' LIMIT 1");
	$row = ($cat && ($r = mysqli_fetch_assoc($cat))) ? $r : array('category_code' => '', 'category_name' => $type_name);

	return array(
		'ok' => true,
		'category_id' => $new_id,
		'category_code' => $row['category_code'] ?? '',
		'category_name' => $row['category_name'] ?? $type_name,
		'label' => trim(($row['category_code'] ?? '') . ' - ' . ($row['category_name'] ?? $type_name), ' -'),
	);
}

function expense_type_save_row($conn, $payload, $edit_key = '', $created_by = 0, $updated_by = 0)
{
	expense_type_ensure_schema($conn);
	$parsed = expense_type_parse_save_payload($payload);
	if (empty($parsed['ok'])) {
		return $parsed;
	}

	$type_name = expense_type_label_name($conn, $parsed['expense_type_code']);
	if ($type_name === '') {
		return array('ok' => false, 'message' => 'Invalid expense type selected.');
	}

	$type_code_esc = mysqli_real_escape_string($conn, $parsed['expense_type_code']);
	$type_name_esc = mysqli_real_escape_string($conn, $type_name);
	$now = date('d-m-Y');
	$created_by = (int) $created_by;
	$updated_by = (int) $updated_by;

	if ($edit_key !== '') {
		$edit_key_esc = mysqli_real_escape_string($conn, $edit_key);
		$query = "UPDATE expense_category SET
			category_name='$type_name_esc',
			expense_type_code='$type_code_esc',
			gst_applicable='0',
			gst_percent=NULL,
			tds_applicable='0',
			tds_percent=NULL,
			updated_at='$now',
			updated_by='$updated_by'
			WHERE md5(category_id)='$edit_key_esc'";
		$result = mysqli_query($conn, $query);
		return array('ok' => (bool) $result, 'message' => $result ? 'Updated.' : 'Update failed.');
	}

	$next = expense_type_next_code($conn);
	$expense_code = mysqli_real_escape_string($conn, $next['expense_code']);
	$expense_code_id = (int) $next['expense_code_id'];
	$query = "INSERT INTO expense_category (
		expense_code_id, category_code, category_name, expense_type_code,
		gst_applicable, gst_percent, tds_applicable, tds_percent,
		created_at, created_by, status
	) VALUES (
		'$expense_code_id', '$expense_code', '$type_name_esc', '$type_code_esc',
		'0', NULL, '0', NULL,
		'$now', '$created_by', 0
	)";
	$result = mysqli_query($conn, $query);
	return array('ok' => (bool) $result, 'message' => $result ? 'Saved.' : 'Save failed.');
}

