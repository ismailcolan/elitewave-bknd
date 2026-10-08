<?php

/**
 * Map Mode of Transport to a City Master column (dynamic; no hardcoded city names).
 */
function city_transport_endpoint_field_for_mode($mode_type)
{
	$mode_type = trim((string) $mode_type);
	if ($mode_type === '') {
		return '';
	}

	switch ($mode_type) {
		case 'Premium Air Cargo':
			return 'airport';
		case 'Premium Train Cargo':
			return 'railway_station';
		case 'Express Delivery':
		case 'Road Freight':
		case 'Full Truck Load':
		case 'Part Load':
			return 'unloading_point';
		default:
			break;
	}

	$lower = strtolower($mode_type);
	if (strpos($lower, 'air') !== false) {
		return 'airport';
	}
	if (strpos($lower, 'train') !== false) {
		return 'railway_station';
	}
	if (strpos($lower, 'express') !== false || strpos($lower, 'road') !== false
		|| strpos($lower, 'truck') !== false || strpos($lower, 'part load') !== false
		|| strpos($lower, 'ftl') !== false || strpos($lower, 'surface') !== false) {
		return 'unloading_point';
	}
	if (strpos($lower, 'sea') !== false || strpos($lower, 'port') !== false) {
		return 'port';
	}

	return 'unloading_point';
}

function city_transport_endpoint_field_label($field)
{
	switch ($field) {
		case 'airport':
			return 'Airport';
		case 'railway_station':
			return 'Railway Station';
		case 'unloading_point':
			return 'Loading/Unloading Point';
		case 'port':
			return 'Port';
		case 'warehouse':
			return 'Warehouse';
		default:
			return 'Location';
	}
}

/** Split City Master text into selectable values (e.g. "A / B" or comma-separated). */
function city_transport_endpoint_split_values($raw)
{
	$raw = trim(str_replace(array("\r\n", "\r"), "\n", (string) $raw));
	if ($raw === '') {
		return array();
	}
	$parts = preg_split('/\s*\/\s*|\n|,|;/', $raw);
	$out = array();
	foreach ($parts as $part) {
		$part = trim($part);
		if ($part !== '' && !in_array($part, $out, true)) {
			$out[] = $part;
		}
	}
	return $out;
}

/** Prefer the most specific segment when City Master has multiple values (e.g. railway name vs city alias). */
function city_transport_endpoint_best_display_label($raw, $field)
{
	$values = city_transport_endpoint_split_values($raw);
	if ($values === array()) {
		return '';
	}
	if (count($values) === 1) {
		return $values[0];
	}

	if ($field === 'railway_station') {
		foreach ($values as $v) {
			if (preg_match('/railway|station|junction|\bNZM\b|\bBZA\b/i', $v)) {
				return $v;
			}
		}
		return $values[count($values) - 1];
	}
	if ($field === 'airport') {
		foreach ($values as $v) {
			if (stripos($v, 'airport') !== false || stripos($v, 'international') !== false) {
				return $v;
			}
		}
		return $values[count($values) - 1];
	}
	if ($field === 'unloading_point' || $field === 'warehouse') {
		foreach ($values as $v) {
			if (stripos($v, 'hub') !== false || stripos($v, 'warehouse') !== false
				|| stripos($v, 'loading') !== false || stripos($v, 'unloading') !== false) {
				return $v;
			}
		}
	}
	if ($field === 'port') {
		foreach ($values as $v) {
			if (stripos($v, 'port') !== false) {
				return $v;
			}
		}
	}

	return $values[0];
}

/**
 * Read endpoint text from City Master; if blank on this city, reuse a matching active city (same / similar name).
 */
function city_transport_endpoint_raw_for_city($conn, $city_id, $field_esc)
{
	$city_id = (int) $city_id;
	$allowed = array('airport', 'railway_station', 'unloading_point', 'warehouse', 'port');
	if (!in_array($field_esc, $allowed, true)) {
		return '';
	}

	$cq = mysqli_query(
		$conn,
		"SELECT city_id, city_name, state, via_city, `$field_esc` AS endpoint_raw FROM city WHERE city_id='$city_id' AND status=0 LIMIT 1"
	);
	if (!$cq || !($cr = mysqli_fetch_assoc($cq))) {
		return '';
	}
	$raw = trim((string) ($cr['endpoint_raw'] ?? ''));
	if ($raw !== '') {
		return $raw;
	}

	$via = trim((string) ($cr['via_city'] ?? ''));
	if ($via !== '' && ctype_digit($via)) {
		$via_id = (int) $via;
		if ($via_id > 0 && $via_id !== $city_id) {
			$vq = mysqli_query(
				$conn,
				"SELECT `$field_esc` AS endpoint_raw FROM city WHERE city_id='$via_id' AND status=0 LIMIT 1"
			);
			if ($vq && ($vr = mysqli_fetch_assoc($vq))) {
				$via_raw = trim((string) ($vr['endpoint_raw'] ?? ''));
				if ($via_raw !== '') {
					return $via_raw;
				}
			}
		}
	}

	$name = trim((string) ($cr['city_name'] ?? ''));
	$state_id = (int) ($cr['state'] ?? 0);
	if ($name === '') {
		return '';
	}
	$name_esc = mysqli_real_escape_string($conn, $name);
	$like_esc = mysqli_real_escape_string($conn, '%' . $name . '%');
	$fq = mysqli_query(
		$conn,
		"SELECT `$field_esc` AS endpoint_raw FROM city
		WHERE status=0 AND city_id != '$city_id'
		AND TRIM(COALESCE(`$field_esc`, '')) != ''
		AND (city_name = '$name_esc' OR city_name LIKE '$like_esc' OR '$name_esc' LIKE CONCAT('%', city_name, '%'))
		ORDER BY (city_name = '$name_esc') DESC, CHAR_LENGTH(city_name) DESC
		LIMIT 1"
	);
	if ($fq && ($fr = mysqli_fetch_assoc($fq))) {
		return trim((string) ($fr['endpoint_raw'] ?? ''));
	}

	if ($state_id > 0) {
		$cnt_q = mysqli_query(
			$conn,
			"SELECT COUNT(*) AS c FROM city WHERE status=0 AND state='$state_id'
			AND TRIM(COALESCE(`$field_esc`, '')) != ''"
		);
		$cnt = ($cnt_q && ($cnt_r = mysqli_fetch_assoc($cnt_q))) ? (int) $cnt_r['c'] : 0;
		if ($cnt === 1) {
			$sq = mysqli_query(
				$conn,
				"SELECT `$field_esc` AS endpoint_raw FROM city
				WHERE status=0 AND state='$state_id' AND TRIM(COALESCE(`$field_esc`, '')) != ''
				LIMIT 1"
			);
			if ($sq && ($sr = mysqli_fetch_assoc($sq))) {
				$state_raw = trim((string) ($sr['endpoint_raw'] ?? ''));
				if ($state_raw !== '') {
					return $state_raw;
				}
			}
		}
	}

	return '';
}

/** Suffix after location name in public tracking copy (Hub only for mapped loading/unloading points). */
function city_transport_tracking_place_suffix($field, $has_mapped_endpoint, $label = '')
{
	if (!$has_mapped_endpoint) {
		return '';
	}
	switch ($field) {
		case 'unloading_point':
		case 'warehouse':
			if ($label !== '' && stripos($label, 'hub') !== false) {
				return '';
			}
			return ' <strong>Hub</strong>';
		default:
			return '';
	}
}

function city_transport_endpoint_options_for_city($conn, $city_id, $mode_id)
{
	$city_id = (int) $city_id;
	$mode_id = (int) $mode_id;
	if ($city_id <= 0 || $mode_id <= 0) {
		return array(
			'field' => '',
			'field_label' => '',
			'options' => array(),
		);
	}

	$mode_type = '';
	if (function_exists('get_mode')) {
		$mode_type = get_mode($conn, $mode_id);
	}
	if ($mode_type === '') {
		$mq = mysqli_query($conn, "SELECT mode_type FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1");
		if ($mq && ($mr = mysqli_fetch_assoc($mq))) {
			$mode_type = $mr['mode_type'];
		}
	}

	$field = city_transport_endpoint_field_for_mode($mode_type);
	if ($field === '') {
		return array(
			'field' => '',
			'field_label' => '',
			'options' => array(),
		);
	}

	$field_esc = preg_replace('/[^a-z_]/', '', $field);
	$allowed = array('airport', 'railway_station', 'unloading_point', 'warehouse', 'port');
	if (!in_array($field_esc, $allowed, true)) {
		$field_esc = 'unloading_point';
	}

	$raw = city_transport_endpoint_raw_for_city($conn, $city_id, $field_esc);

	$values = city_transport_endpoint_split_values($raw);
	$options = array();
	foreach ($values as $i => $label) {
		$options[] = array(
			'value' => 'endpoint:' . $i,
			'label' => $label,
			'type' => 'endpoint',
			'index' => $i,
		);
	}

	return array(
		'field' => $field_esc,
		'field_label' => city_transport_endpoint_field_label($field_esc),
		'options' => $options,
	);
}

function city_transport_endpoint_build_select_options($options, $include_manual = true)
{
	$list = $options;
	if ($include_manual) {
		$list[] = array(
			'value' => 'manual:',
			'label' => '— Enter manually —',
			'type' => 'manual',
		);
	}
	return $list;
}

function city_transport_endpoint_resolve_selection($value, $manual, $options)
{
	$value = trim((string) $value);
	$manual = trim((string) $manual);
	if ($value === '' || $value === 'manual:') {
		if ($manual === '') {
			return null;
		}
		return array(
			'type' => 'manual',
			'label' => $manual,
			'manual' => $manual,
			'value' => 'manual:',
		);
	}
	if (strpos($value, 'endpoint:') === 0) {
		$idx = (int) substr($value, strlen('endpoint:'));
		foreach ($options as $opt) {
			if ((int) ($opt['index'] ?? -1) === $idx) {
				return array(
					'type' => 'endpoint',
					'label' => $opt['label'],
					'manual' => '',
					'value' => $value,
				);
			}
		}
	}
	return null;
}

function city_transport_endpoint_tracking_location($conn, $city_id, $mode_id)
{
	$pack = city_transport_endpoint_options_for_city($conn, $city_id, $mode_id);
	$field = $pack['field'] ?? '';
	$raw = city_transport_endpoint_raw_for_city($conn, (int) $city_id, $field);
	$label = city_transport_endpoint_best_display_label($raw, $field);
	$has_mapped = ($label !== '');
	if (!$has_mapped) {
		$label = get_city_name($conn, $city_id);
	}
	return array(
		'field' => $field,
		'label' => $label,
		'has_mapped_endpoint' => $has_mapped,
		'suffix_html' => city_transport_tracking_place_suffix($field, $has_mapped, $label),
	);
}

function city_transport_loading_hub_for_booking($conn, $origin_city_id, $mode_id)
{
	$loc = city_transport_endpoint_tracking_location($conn, $origin_city_id, $mode_id);
	return $loc['label'];
}

function city_transport_destination_hub_for_booking($conn, $destination_city_id, $mode_id)
{
	$loc = city_transport_endpoint_tracking_location($conn, $destination_city_id, $mode_id);
	return $loc['label'];
}
