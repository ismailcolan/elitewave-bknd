<?php

function ew_cfs_master_ensure_schema($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cfs_master (
		cfs_id INT(11) NOT NULL AUTO_INCREMENT,
		cfs_code VARCHAR(20) DEFAULT NULL,
		cfs_code_id INT(11) NOT NULL DEFAULT 0,
		cfs_name VARCHAR(255) NOT NULL,
		status TINYINT(1) NOT NULL DEFAULT 0,
		sort_order INT(11) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (cfs_id),
		UNIQUE KEY uk_cfs_name (cfs_name)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$chk = mysqli_query($conn, "SHOW COLUMNS FROM cfs_master LIKE 'cfs_code'");
	if ($chk && mysqli_num_rows($chk) === 0) {
		mysqli_query($conn, 'ALTER TABLE cfs_master ADD COLUMN cfs_code VARCHAR(20) DEFAULT NULL AFTER cfs_id');
		mysqli_query($conn, 'ALTER TABLE cfs_master ADD COLUMN cfs_code_id INT(11) NOT NULL DEFAULT 0 AFTER cfs_code');
		$uq = mysqli_query($conn, "SHOW INDEX FROM cfs_master WHERE Key_name='uk_cfs_code'");
		if (!$uq || mysqli_num_rows($uq) === 0) {
			mysqli_query($conn, 'ALTER TABLE cfs_master ADD UNIQUE KEY uk_cfs_code (cfs_code)');
		}
	}

	ew_cfs_master_apply_default_seeds($conn);
	ew_cfs_master_sync_pdf_codes($conn);
}

/** Chennai / North Chennai CFS list (GCN PDF). */
function ew_cfs_master_default_seeds()
{
	return array(
		'Allcargo Logistics Limited — Tiruvottiyur',
		'Container Corporation of India Limited — Tiruvottiyur',
		'Sanco Container Freight Station — Ennore Express High Road, Tiruvottiyur',
		'Sical CFS — Vallur / Ennore',
		'Ennore Cargo Container Terminal — Vallur',
		'Kences Container Freight Station — Athipattu / Vallur',
		'Triway Container Freight Station — Ponneri High Road, Vallur',
		'STP Container Freight Station — Tiruvottiyur',
		'Continental CFS Chennai — Chennai',
		'Kailash Shipping Container Freight Station — Manali/Vichoor',
		'Calyx Container Terminals Private Limited — Puzhal',
		'Chandra CFS — Nallur/Minjur',
		'Sattva Hitech CFS — Manali',
		'Balmer Lawrie CFS — Manali',
		'Gateway Distriparks CFS — Chennai',
		'Hind Terminals CFS — Chennai',
		'Sudharsan Logistics CFS — Chennai',
		'MIV CFS — Chennai',
		'A.S. Shipping Agencies CFS — Numbal/Tiruvallur',
		'Adani CFS (L&T CFS) — Kattupalli',
		'Diamond CFS Park — Chennai',
		'Vilsons CFS — Chennai',
		'Viking Warehousing CFS — Chennai',
		'Supply Chain Logistics CFS — Chennai',
		'Prompt Terminals — Chennai',
		'Hari CFS — Chennai',
		'Kerry Indev / Continental Container Freight Station — Chennai',
	);
}

function ew_cfs_master_format_code($num)
{
	return 'CFS' . sprintf('%03d', (int) $num);
}

function ew_cfs_master_next_code($conn)
{
	ew_cfs_master_ensure_schema($conn);
	$row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT MAX(cfs_code_id) AS n FROM cfs_master'));
	$id = (int) ($row['n'] ?? 0) + 1;

	return array(
		'cfs_code_id' => $id,
		'cfs_code' => ew_cfs_master_format_code($id),
	);
}

function ew_cfs_master_apply_default_seeds($conn)
{
	$seeds = ew_cfs_master_default_seeds();
	$dt = date('d-m-Y');
	foreach ($seeds as $i => $name) {
		$name_esc = mysqli_real_escape_string($conn, $name);
		mysqli_query($conn, "INSERT IGNORE INTO cfs_master (cfs_name, status, sort_order, created_at) VALUES ('$name_esc', 0, '$i', '$dt')");
		mysqli_query($conn, "UPDATE cfs_master SET sort_order='$i', status=0 WHERE cfs_name='$name_esc'");
	}
}

/** Keep only the 27 PDF CFS rows (removes old placeholders / inactive extras). */
function ew_cfs_master_purge_non_pdf_rows($conn)
{
	$seeds = ew_cfs_master_default_seeds();
	if (empty($seeds)) {
		return;
	}
	$parts = array();
	foreach ($seeds as $name) {
		$parts[] = "'" . mysqli_real_escape_string($conn, $name) . "'";
	}
	mysqli_query($conn, 'DELETE FROM cfs_master WHERE cfs_name NOT IN (' . implode(',', $parts) . ')');
}

/** Assign CFS001–CFS027 to PDF list order. */
function ew_cfs_master_sync_pdf_codes($conn)
{
	$seeds = ew_cfs_master_default_seeds();
	foreach ($seeds as $i => $name) {
		$code_id = $i + 1;
		$code = ew_cfs_master_format_code($code_id);
		$name_esc = mysqli_real_escape_string($conn, $name);
		$code_esc = mysqli_real_escape_string($conn, $code);
		mysqli_query(
			$conn,
			"UPDATE cfs_master SET cfs_code_id='$code_id', cfs_code='$code_esc', sort_order='$i', status=0 WHERE cfs_name='$name_esc'"
		);
	}
}

function ew_cfs_master_list($conn, $active_only = true)
{
	ew_cfs_master_ensure_schema($conn);
	$rows = array();
	$sql = 'SELECT * FROM cfs_master';
	if ($active_only) {
		$sql .= ' WHERE status=0';
	}
	$sql .= ' ORDER BY sort_order ASC, cfs_code_id ASC, cfs_name ASC, cfs_id ASC';
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function ew_cfs_master_get($conn, $cfs_id)
{
	$cfs_id = (int) $cfs_id;
	if ($cfs_id <= 0) {
		return null;
	}
	ew_cfs_master_ensure_schema($conn);
	$q = mysqli_query($conn, "SELECT * FROM cfs_master WHERE cfs_id='$cfs_id' LIMIT 1");
	return ($q && ($row = mysqli_fetch_assoc($q))) ? $row : null;
}

function ew_cfs_master_get_by_key($conn, $key)
{
	$key = trim((string) $key);
	if ($key === '') {
		return null;
	}
	ew_cfs_master_ensure_schema($conn);
	$key_esc = mysqli_real_escape_string($conn, $key);
	$q = mysqli_query($conn, "SELECT * FROM cfs_master WHERE MD5(cfs_id)='$key_esc' LIMIT 1");
	return ($q && ($row = mysqli_fetch_assoc($q))) ? $row : null;
}

function ew_cfs_master_name_exists($conn, $name, $exclude_id = 0)
{
	$name = trim((string) $name);
	if ($name === '') {
		return false;
	}
	ew_cfs_master_ensure_schema($conn);
	$name_esc = mysqli_real_escape_string($conn, $name);
	$exclude_id = (int) $exclude_id;
	$sql = "SELECT cfs_id FROM cfs_master WHERE cfs_name='$name_esc'";
	if ($exclude_id > 0) {
		$sql .= " AND cfs_id!='$exclude_id'";
	}
	$q = mysqli_query($conn, $sql . ' LIMIT 1');
	return ($q && mysqli_num_rows($q) > 0);
}

function ew_booking_cfs_location_kind_options()
{
	return array(
		'cfs' => 'CFS',
		'port' => 'Port',
		'factory' => 'Factory',
		'warehouse' => 'Warehouse',
	);
}

function ew_booking_cfs_guess_kind($conn, $stored_value)
{
	$stored = trim((string) $stored_value);
	if ($stored === '') {
		return 'cfs';
	}
	ew_cfs_master_ensure_schema($conn);
	if (ew_cfs_master_name_exists($conn, $stored)) {
		return 'cfs';
	}
	$u = strtoupper($stored);
	if (strpos($u, 'FACTORY') !== false) {
		return 'factory';
	}
	if (preg_match('/\bPORT\b/', $u)) {
		return 'port';
	}
	if (strpos($u, 'WAREHOUSE') !== false || strpos($u, 'CFS') !== false) {
		return 'cfs';
	}
	return 'port';
}

function ew_booking_cfs_controls_html($conn, $stored_value = '', $opts = array())
{
	ew_cfs_master_ensure_schema($conn);
	$hidden_name = trim((string) ($opts['hidden_name'] ?? 'cfs'));
	if ($hidden_name === '') {
		$hidden_name = 'cfs';
	}
	$hidden_id = trim((string) ($opts['hidden_id'] ?? $hidden_name));
	if ($hidden_id === '') {
		$hidden_id = $hidden_name;
	}
	$stored = trim((string) $stored_value);
	$kind = ew_booking_cfs_guess_kind($conn, $stored);
	$kinds = ew_booking_cfs_location_kind_options();
	$cfs_rows = ew_cfs_master_list($conn, true);
	$in_master = ($stored !== '' && ew_cfs_master_name_exists($conn, $stored));

	$html = '<div class="ew-cfs-location-wrap" id="cfs_location_wrap">';
	$html .= '<div class="ew-cfs-location-row">';
	$html .= '<select id="cfs_kind" class="form-control ew-cfs-kind-select" aria-label="Location type">';
	foreach ($kinds as $code => $label) {
		$sel = ($kind === $code) ? ' selected' : '';
		$html .= '<option value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	$html .= '</select>';

	$select_style = ($kind === 'cfs') ? '' : ' style="display:none"';
	$html .= '<select id="cfs_master_select" class="form-control ew-cfs-value-field"' . $select_style . '>';
	$html .= '<option value="">Select CFS</option>';
	$matched = false;
	foreach ($cfs_rows as $cfs_row) {
		$name = (string) $cfs_row['cfs_name'];
		$sel = ($stored !== '' && strcasecmp($stored, $name) === 0) ? ' selected' : '';
		if ($sel !== '') {
			$matched = true;
		}
		$html .= '<option value="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
			. htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	if ($stored !== '' && $kind === 'cfs' && !$matched && !$in_master) {
		$html .= '<option value="' . htmlspecialchars($stored, ENT_QUOTES, 'UTF-8') . '" selected>'
			. htmlspecialchars($stored, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	$html .= '</select>';

	$text_style = ($kind === 'cfs') ? ' style="display:none"' : '';
	$text_val = ($kind === 'cfs') ? '' : $stored;
	$html .= '<input type="text" id="cfs_text_input" class="form-control ew-cfs-value-field" placeholder="Enter details"'
		. $text_style . ' value="' . htmlspecialchars($text_val, ENT_QUOTES, 'UTF-8') . '">';

	$html .= '</div>';
	$html .= '<input type="hidden" name="' . htmlspecialchars($hidden_name, ENT_QUOTES, 'UTF-8') . '" id="'
		. htmlspecialchars($hidden_id, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($stored, ENT_QUOTES, 'UTF-8') . '">';
	$html .= '</div>';

	return $html;
}

function ew_cfs_master_insert($conn, $post, $user_id)
{
	ew_cfs_master_ensure_schema($conn);
	$name = trim((string) ($post['cfs_name'] ?? ''));
	if ($name === '') {
		return array('result' => 0, 'message' => 'CFS name is required.');
	}
	if (ew_cfs_master_name_exists($conn, $name, 0)) {
		return array('result' => 0, 'message' => 'This CFS name already exists.');
	}
	$next = ew_cfs_master_next_code($conn);
	$code_id = (int) $next['cfs_code_id'];
	$code = $next['cfs_code'];
	$row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM cfs_master'));
	$sort_order = (int) ($row['n'] ?? 0);
	$name_esc = mysqli_real_escape_string($conn, $name);
	$code_esc = mysqli_real_escape_string($conn, $code);
	$dt = date('d-m-Y');
	$user_id = (int) $user_id;
	$ok = mysqli_query(
		$conn,
		"INSERT INTO cfs_master (cfs_code, cfs_code_id, cfs_name, status, sort_order, created_at, created_by)
		VALUES ('$code_esc', '$code_id', '$name_esc', 0, '$sort_order', '$dt', '$user_id')"
	);
	if (!$ok) {
		return array('result' => 0, 'message' => mysqli_error($conn));
	}
	return array('result' => 1, 'message' => 'Saved successfully.');
}

function ew_cfs_master_update($conn, $post, $user_id)
{
	ew_cfs_master_ensure_schema($conn);
	$cfs_id = (int) ($post['edit_id'] ?? 0);
	$row = ew_cfs_master_get($conn, $cfs_id);
	if (!$row) {
		return array('result' => 0, 'message' => 'CFS record not found.');
	}
	$name = trim((string) ($post['cfs_name'] ?? ''));
	if ($name === '') {
		return array('result' => 0, 'message' => 'CFS name is required.');
	}
	if (ew_cfs_master_name_exists($conn, $name, $cfs_id)) {
		return array('result' => 0, 'message' => 'This CFS name already exists.');
	}
	$name_esc = mysqli_real_escape_string($conn, $name);
	$dt = date('d-m-Y');
	$user_id = (int) $user_id;
	$ok = mysqli_query(
		$conn,
		"UPDATE cfs_master SET cfs_name='$name_esc', updated_at='$dt', updated_by='$user_id' WHERE cfs_id='$cfs_id'"
	);
	if (!$ok) {
		return array('result' => 0, 'message' => mysqli_error($conn));
	}
	return array('result' => 1, 'message' => 'Saved successfully.');
}

function ew_cfs_master_set_status($conn, $cfs_id, $status, $user_id)
{
	$cfs_id = (int) $cfs_id;
	$status = (int) $status;
	$dt = date('d-m-Y');
	$user_id = (int) $user_id;
	return mysqli_query($conn, "UPDATE cfs_master SET status='$status', updated_at='$dt', updated_by='$user_id' WHERE cfs_id='$cfs_id'");
}
