<?php

function ew_vehicle_type_categories()
{
	return array(
		'TWO_WHEELER' => 'Two Wheeler',
		'THREE_WHEELER' => 'Three Wheeler',
		'LCV' => 'LCV',
		'HCV' => 'HCV',
		'MCV' => 'MCV',
		'TRUCK' => 'Truck',
		'MULTI_AXLE_TRUCK' => 'Multi-Axle Truck',
		'TRAILER' => 'Trailer',
		'CONTAINER' => 'Container',
		'TANKER' => 'Tanker',
		'SPECIAL_VEHICLE' => 'Special Vehicle',
		'OTHER' => 'Other',
	);
}

function ew_vehicle_type_body_types()
{
	return array(
		'OPEN' => 'Open',
		'CLOSED' => 'Closed',
		'CONTAINER' => 'Container',
		'FLATBED' => 'Flatbed',
		'TIPPER' => 'Tipper',
		'TANKER' => 'Tanker',
		'REFRIGERATED' => 'Refrigerated',
		'PLATFORM' => 'Platform',
		'TRAILER' => 'Trailer',
		'OPEN_JCP' => 'Open / JCP Body',
		'OPEN_TRUCK' => 'Open Body / JCP Truck',
		'OTHER' => 'Other',
	);
}

function ew_vehicle_type_uom_options()
{
	return array('KG', 'Ton', 'CBM', 'Liter', 'Nos.', 'FT');
}

function ew_vehicle_type_ensure_schema($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vehicle_type_master (
		vehicle_type_id INT(11) NOT NULL AUTO_INCREMENT,
		type_code VARCHAR(20) NOT NULL,
		type_name VARCHAR(150) NOT NULL,
		vehicle_category VARCHAR(40) NOT NULL DEFAULT '',
		description TEXT,
		body_type VARCHAR(40) DEFAULT NULL,
		dim_length DECIMAL(12,3) DEFAULT NULL,
		dim_width DECIMAL(12,3) DEFAULT NULL,
		dim_height DECIMAL(12,3) DEFAULT NULL,
		dimension_uom VARCHAR(10) DEFAULT 'FT',
		capacity DECIMAL(12,3) DEFAULT NULL,
		capacity_uom VARCHAR(10) DEFAULT 'Ton',
		volume_capacity DECIMAL(12,3) DEFAULT NULL,
		volume_uom VARCHAR(10) DEFAULT 'CBM',
		num_axles INT(11) DEFAULT NULL,
		num_wheels INT(11) DEFAULT NULL,
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (vehicle_type_id),
		UNIQUE KEY uk_vehicle_type_code (type_code)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ew_vehicle_type_list($conn, $active_only = false)
{
	ew_vehicle_type_ensure_schema($conn);
	$rows = array();
	$sql = 'SELECT * FROM vehicle_type_master';
	if ($active_only) {
		$sql .= ' WHERE status=0';
	}
	$sql .= ' ORDER BY type_name ASC, vehicle_type_id ASC';
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function ew_vehicle_type_get($conn, $vehicle_type_id)
{
	$vehicle_type_id = (int) $vehicle_type_id;
	if ($vehicle_type_id <= 0) {
		return null;
	}
	ew_vehicle_type_ensure_schema($conn);
	$q = mysqli_query($conn, "SELECT * FROM vehicle_type_master WHERE vehicle_type_id='$vehicle_type_id' LIMIT 1");
	return ($q && ($row = mysqli_fetch_assoc($q))) ? $row : null;
}

function ew_vehicle_type_get_by_key($conn, $key)
{
	$key = trim((string) $key);
	if ($key === '') {
		return null;
	}
	ew_vehicle_type_ensure_schema($conn);
	$key_esc = mysqli_real_escape_string($conn, $key);
	$q = mysqli_query($conn, "SELECT * FROM vehicle_type_master WHERE MD5(vehicle_type_id)='$key_esc' LIMIT 1");
	return ($q && ($row = mysqli_fetch_assoc($q))) ? $row : null;
}

function ew_vehicle_type_next_code($conn)
{
	ew_vehicle_type_ensure_schema($conn);
	$row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS n FROM vehicle_type_master'));
	$n = (int) ($row['n'] ?? 0) + 1;
	if ($n < 1) {
		$n = 1;
	}
	return 'VT' . sprintf('%05d', $n);
}

function ew_vehicle_type_category_label($code)
{
	$map = ew_vehicle_type_categories();
	$code = strtoupper(trim((string) $code));
	return $map[$code] ?? ($code !== '' ? $code : '—');
}

function ew_vehicle_type_body_label($code)
{
	$map = ew_vehicle_type_body_types();
	$code = strtoupper(trim((string) $code));
	return $map[$code] ?? ($code !== '' ? $code : '—');
}

function ew_vehicle_type_format_capacity($row)
{
	$cap = $row['capacity'] ?? '';
	if ($cap === '' || $cap === null) {
		return '—';
	}
	$uom = trim((string) ($row['capacity_uom'] ?? ''));
	return rtrim(rtrim(number_format((float) $cap, 3, '.', ''), '0'), '.') . ($uom !== '' ? ' ' . $uom : '');
}

function ew_vehicle_type_dim_num($value)
{
	if ($value === null || $value === '') {
		return '';
	}
	if (!is_numeric($value)) {
		return '';
	}
	$n = (float) $value;
	if (abs($n - round($n)) < 0.0001) {
		return (string) (int) round($n);
	}
	return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
}

/** Compact list display, e.g. L32 X W28 X H8 FT */
function ew_vehicle_type_format_dimensions_compact($row)
{
	$uom = strtoupper(trim((string) ($row['dimension_uom'] ?? 'FT')));
	if ($uom === '') {
		$uom = 'FT';
	}
	$l = ew_vehicle_type_dim_num($row['dim_length'] ?? null);
	$w = ew_vehicle_type_dim_num($row['dim_width'] ?? null);
	$h = ew_vehicle_type_dim_num($row['dim_height'] ?? null);
	$parts = array();
	if ($l !== '') {
		$parts[] = 'L' . $l;
	}
	if ($w !== '') {
		$parts[] = 'W' . $w;
	}
	if ($h !== '') {
		$parts[] = 'H' . $h;
	}
	if ($parts === array()) {
		return '—';
	}
	return implode(' X ', $parts) . ' ' . $uom;
}

function ew_vehicle_type_volume_cbm($row)
{
	$vol = $row['volume_capacity'] ?? null;
	if ($vol !== null && $vol !== '' && is_numeric($vol)) {
		return ew_vehicle_type_dim_num($vol);
	}
	$l = $row['dim_length'] ?? null;
	$w = $row['dim_width'] ?? null;
	$h = $row['dim_height'] ?? null;
	if (!is_numeric($l) || !is_numeric($w) || !is_numeric($h)) {
		return '';
	}
	$ft3 = (float) $l * (float) $w * (float) $h;
	if ($ft3 <= 0) {
		return '';
	}
	return ew_vehicle_type_dim_num($ft3 * 0.028316846592);
}

/** Booking / list: L20 X W8 X H8 FT / 9.5 CBM */
function ew_vehicle_type_format_dimensions_cbm_display($row)
{
	$dimPart = ew_vehicle_type_format_dimensions_compact($row);
	if ($dimPart === '—') {
		return '';
	}
	$cbm = ew_vehicle_type_volume_cbm($row);
	if ($cbm === '') {
		return $dimPart . ' / CBM';
	}
	return $dimPart . ' / ' . $cbm . ' CBM';
}

function ew_vehicle_type_booking_dims_lookup($conn)
{
	$map = array();
	foreach (ew_vehicle_type_list($conn, true) as $vt) {
		$display = ew_vehicle_type_format_dimensions_cbm_display($vt);
		$name = trim((string) ($vt['type_name'] ?? ''));
		if ($name !== '') {
			$map[$name] = $display;
		}
		$code = trim((string) ($vt['type_code'] ?? ''));
		if ($code !== '') {
			$map[$code] = $display;
		}
	}
	return $map;
}

function ew_vehicle_type_dims_display_for_booking($conn, $vehicle_type_name)
{
	$saved = trim((string) $vehicle_type_name);
	if ($saved === '') {
		return '';
	}
	foreach (ew_vehicle_type_list($conn, false) as $vt) {
		$name = trim((string) ($vt['type_name'] ?? ''));
		$code = trim((string) ($vt['type_code'] ?? ''));
		if ($saved === $name || ($code !== '' && $saved === $code)) {
			return ew_vehicle_type_format_dimensions_cbm_display($vt);
		}
	}
	return '';
}

function ew_booking_highload_mamul_total($highload_challan, $mamul_charge)
{
	$sum = 0.0;
	$mamul = trim((string) $mamul_charge);
	if ($mamul !== '' && is_numeric($mamul)) {
		$sum += (float) $mamul;
	}
	$hl = trim((string) $highload_challan);
	if ($hl !== '' && is_numeric($hl)) {
		$sum += (float) $hl;
	}
	return $sum;
}

function ew_vehicle_type_select_options_html($options, $selected = '', $placeholder = 'Select')
{
	$html = '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
	foreach ($options as $value => $label) {
		$sel = ((string) $selected === (string) $value) ? ' selected' : '';
		$html .= '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	return $html;
}

/** GCN booking: active types from master; persisted value is type_name on transaction. */
function ew_vehicle_type_transaction_select_html($conn, $saved_value = '', $extra_attrs = '')
{
	ew_vehicle_type_ensure_schema($conn);
	$saved = trim((string) $saved_value);
	$types = ew_vehicle_type_list($conn, true);
	$matched = false;
	$attrs = trim((string) $extra_attrs);
	$html = '<select name="vehicle_type" id="vehicle_type" class="form-control"' . ($attrs !== '' ? ' ' . $attrs : '') . '>';
	$html .= '<option value="">Select vehicle type</option>';
	foreach ($types as $vt) {
		$name = trim((string) ($vt['type_name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$code = trim((string) ($vt['type_code'] ?? ''));
		$sel = ($saved !== '' && ($saved === $name || ($code !== '' && $saved === $code))) ? ' selected' : '';
		if ($sel) {
			$matched = true;
		}
		$label = $name;
		$cap = ew_vehicle_type_format_capacity($vt);
		if ($cap !== '—') {
			$label .= ' · ' . $cap;
		}
		$html .= '<option value="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	if ($saved !== '' && !$matched) {
		$html .= '<option value="' . htmlspecialchars($saved, ENT_QUOTES, 'UTF-8') . '" selected>'
			. htmlspecialchars($saved, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	$html .= '</select>';
	return $html;
}

function ew_vehicle_type_uom_select_html($selected = 'FT')
{
	$html = '';
	foreach (ew_vehicle_type_uom_options() as $uom) {
		$sel = ((string) $selected === (string) $uom) ? ' selected' : '';
		$html .= '<option value="' . htmlspecialchars($uom, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
			. htmlspecialchars($uom, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	return $html;
}

function ew_vehicle_type_parse_decimal($value)
{
	$value = trim((string) $value);
	if ($value === '') {
		return null;
	}
	if (!is_numeric($value)) {
		return false;
	}
	return (float) $value;
}

function ew_vehicle_type_parse_int($value)
{
	$value = trim((string) $value);
	if ($value === '') {
		return null;
	}
	if (!ctype_digit($value) && !preg_match('/^-?\d+$/', $value)) {
		return false;
	}
	return (int) $value;
}

function ew_vehicle_type_save($conn, $payload, $edit_key = '', $user_id = 0)
{
	ew_vehicle_type_ensure_schema($conn);
	$name = trim((string) ($payload['type_name'] ?? ''));
	$category = strtoupper(trim((string) ($payload['vehicle_category'] ?? '')));
	$description = trim((string) ($payload['description'] ?? ''));
	$body_type = strtoupper(trim((string) ($payload['body_type'] ?? '')));
	$status = (int) ($payload['status'] ?? 0);
	$dimension_uom = trim((string) ($payload['dimension_uom'] ?? 'FT'));
	$capacity_uom = trim((string) ($payload['capacity_uom'] ?? 'Ton'));
	$volume_uom = trim((string) ($payload['volume_uom'] ?? 'CBM'));

	if ($name === '') {
		return array('ok' => false, 'message' => 'Vehicle type name is required.');
	}
	if ($category === '' || !isset(ew_vehicle_type_categories()[$category])) {
		return array('ok' => false, 'message' => 'Please select a valid vehicle category.');
	}
	if ($body_type !== '' && !isset(ew_vehicle_type_body_types()[$body_type])) {
		return array('ok' => false, 'message' => 'Please select a valid body type.');
	}
	$allowed_uom = ew_vehicle_type_uom_options();
	if (!in_array($dimension_uom, $allowed_uom, true)) {
		$dimension_uom = 'FT';
	}
	if (!in_array($capacity_uom, $allowed_uom, true)) {
		$capacity_uom = 'Ton';
	}
	if (!in_array($volume_uom, $allowed_uom, true)) {
		$volume_uom = 'CBM';
	}

	$dim_length = ew_vehicle_type_parse_decimal($payload['dim_length'] ?? '');
	$dim_width = ew_vehicle_type_parse_decimal($payload['dim_width'] ?? '');
	$dim_height = ew_vehicle_type_parse_decimal($payload['dim_height'] ?? '');
	$capacity = ew_vehicle_type_parse_decimal($payload['capacity'] ?? '');
	$volume_capacity = ew_vehicle_type_parse_decimal($payload['volume_capacity'] ?? '');
	$num_axles = ew_vehicle_type_parse_int($payload['num_axles'] ?? '');
	$num_wheels = ew_vehicle_type_parse_int($payload['num_wheels'] ?? '');

	foreach (array('length' => $dim_length, 'width' => $dim_width, 'height' => $dim_height, 'capacity' => $capacity, 'volume' => $volume_capacity) as $label => $val) {
		if ($val === false) {
			return array('ok' => false, 'message' => 'Invalid numeric value for ' . $label . '.');
		}
	}
	if ($num_axles === false || $num_wheels === false) {
		return array('ok' => false, 'message' => 'Axles and wheels must be whole numbers.');
	}

	$edit_row = null;
	$edit_id = 0;
	$edit_key = trim((string) $edit_key);
	if ($edit_key !== '') {
		$edit_row = ew_vehicle_type_get_by_key($conn, $edit_key);
		if (!$edit_row) {
			return array('ok' => false, 'message' => 'Vehicle type not found.');
		}
		$edit_id = (int) $edit_row['vehicle_type_id'];
	}

	$name_esc = mysqli_real_escape_string($conn, $name);
	$dup_sql = "SELECT vehicle_type_id FROM vehicle_type_master WHERE type_name='$name_esc'";
	if ($edit_id > 0) {
		$dup_sql .= " AND vehicle_type_id!='$edit_id'";
	}
	$dup_sql .= ' LIMIT 1';
	$dup = mysqli_query($conn, $dup_sql);
	if ($dup && mysqli_num_rows($dup) > 0) {
		return array('ok' => false, 'message' => 'This vehicle type name already exists.');
	}

	$now = date('d-m-Y');
	$user_id = (int) $user_id;
	$desc_esc = mysqli_real_escape_string($conn, $description);
	$cat_esc = mysqli_real_escape_string($conn, $category);
	$body_esc = mysqli_real_escape_string($conn, $body_type !== '' ? $body_type : '');
	$dim_uom_esc = mysqli_real_escape_string($conn, $dimension_uom);
	$cap_uom_esc = mysqli_real_escape_string($conn, $capacity_uom);
	$vol_uom_esc = mysqli_real_escape_string($conn, $volume_uom);

	$sql_len = $dim_length === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $dim_length) . "'";
	$sql_wid = $dim_width === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $dim_width) . "'";
	$sql_hei = $dim_height === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $dim_height) . "'";
	$sql_cap = $capacity === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $capacity) . "'";
	$sql_vol = $volume_capacity === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $volume_capacity) . "'";
	$sql_ax = $num_axles === null ? 'NULL' : "'" . (int) $num_axles . "'";
	$sql_wh = $num_wheels === null ? 'NULL' : "'" . (int) $num_wheels . "'";
	$body_sql = $body_type === '' ? 'NULL' : "'$body_esc'";

	if ($edit_id > 0) {
		$ok = mysqli_query($conn, "UPDATE vehicle_type_master SET
			type_name='$name_esc',
			vehicle_category='$cat_esc',
			description='$desc_esc',
			body_type=$body_sql,
			dim_length=$sql_len,
			dim_width=$sql_wid,
			dim_height=$sql_hei,
			dimension_uom='$dim_uom_esc',
			capacity=$sql_cap,
			capacity_uom='$cap_uom_esc',
			volume_capacity=$sql_vol,
			volume_uom='$vol_uom_esc',
			num_axles=$sql_ax,
			num_wheels=$sql_wh,
			status='$status',
			updated_at='$now',
			updated_by='$user_id'
			WHERE vehicle_type_id='$edit_id' LIMIT 1");
		return array('ok' => (bool) $ok, 'message' => $ok ? 'Updated successfully.' : 'Update failed.');
	}

	$code = mysqli_real_escape_string($conn, ew_vehicle_type_next_code($conn));
	$ok = mysqli_query($conn, "INSERT INTO vehicle_type_master (
		type_code, type_name, vehicle_category, description, body_type,
		dim_length, dim_width, dim_height, dimension_uom,
		capacity, capacity_uom, volume_capacity, volume_uom,
		num_axles, num_wheels, status, created_at, created_by
	) VALUES (
		'$code', '$name_esc', '$cat_esc', '$desc_esc', $body_sql,
		$sql_len, $sql_wid, $sql_hei, '$dim_uom_esc',
		$sql_cap, '$cap_uom_esc', $sql_vol, '$vol_uom_esc',
		$sql_ax, $sql_wh, '$status', '$now', '$user_id'
	)");
	return array('ok' => (bool) $ok, 'message' => $ok ? 'Saved successfully.' : 'Save failed.');
}

function ew_vehicle_type_set_status($conn, $vehicle_type_id, $status, $user_id = 0)
{
	ew_vehicle_type_ensure_schema($conn);
	$vehicle_type_id = (int) $vehicle_type_id;
	$status = (int) $status;
	if ($vehicle_type_id <= 0) {
		return false;
	}
	$now = date('d-m-Y');
	$user_id = (int) $user_id;
	return (bool) mysqli_query($conn, "UPDATE vehicle_type_master SET status='$status', updated_at='$now', updated_by='$user_id' WHERE vehicle_type_id='$vehicle_type_id' LIMIT 1");
}

function ew_vehicle_type_delete($conn, $vehicle_type_id)
{
	ew_vehicle_type_ensure_schema($conn);
	$vehicle_type_id = (int) $vehicle_type_id;
	$row = ew_vehicle_type_get($conn, $vehicle_type_id);
	if (!$row) {
		return array('ok' => false, 'message' => 'Vehicle type not found.');
	}
	$code_esc = mysqli_real_escape_string($conn, $row['type_code']);
	$name_esc = mysqli_real_escape_string($conn, $row['type_name']);
	$in_use = mysqli_query($conn, "SELECT vehicle_id FROM vehicle WHERE (vehicle_type='$code_esc' OR vehicle_type='$name_esc') AND status=0 LIMIT 1");
	if ($in_use && mysqli_num_rows($in_use) > 0) {
		return array('ok' => false, 'message' => 'This vehicle type is linked to existing vehicles and cannot be deleted.');
	}
	$ok = mysqli_query($conn, "DELETE FROM vehicle_type_master WHERE vehicle_type_id='$vehicle_type_id' LIMIT 1");
	return array('ok' => (bool) $ok, 'message' => $ok ? 'Deleted.' : 'Delete failed.');
}

function ew_vehicle_type_empty_row()
{
	return array(
		'type_code' => '',
		'type_name' => '',
		'vehicle_category' => '',
		'description' => '',
		'body_type' => '',
		'dim_length' => '',
		'dim_width' => '',
		'dim_height' => '',
		'dimension_uom' => 'FT',
		'capacity' => '',
		'capacity_uom' => 'Ton',
		'volume_capacity' => '',
		'volume_uom' => 'CBM',
		'num_axles' => '',
		'num_wheels' => '',
		'status' => 0,
	);
}
