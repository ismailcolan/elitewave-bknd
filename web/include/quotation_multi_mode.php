<?php

require_once __DIR__ . '/vehicle_type_helpers.php';

function quotation_multi_mode_quote_types()
{
	return array(
		'consignee_consignor_all_modes' => 'Consignee / Consignor — All Modes',
		'consignor_consignee_all_modes' => 'Consignor / Consignee — All Modes',
	);
}

function quotation_is_multi_mode_quote_type($quote_type)
{
	$quote_type = trim((string) $quote_type);
	return isset(quotation_multi_mode_quote_types()[$quote_type]);
}

function quotation_delivery_days_options()
{
	return array(
		'same_day' => 'Same day',
		'next_day' => 'Next day',
		'1_day' => '1 day',
		'1_2_days' => '1 - 2 days',
		'2_3_days' => '2 - 3 days',
		'3_4_days' => '3 - 4 days',
		'4_5_days' => '4 - 5 days',
		'5_6_days' => '5 - 6 days',
		'6_7_days' => '6 - 7 days',
		'7_8_days' => '7 - 8 days',
		'8_9_days' => '8 - 9 days',
		'9_10_days' => '9 - 10 days',
	);
}

function quotation_delivery_days_label($code)
{
	$code = trim((string) $code);
	$opts = quotation_delivery_days_options();
	return $opts[$code] ?? ($code !== '' ? $code : '—');
}

function quotation_mode_row_total($freight, $doc = 0, $loading = 0, $others = 0, $tempo = 0)
{
	return round((float) $freight + (float) $tempo, 2);
}

function quotation_mode_source_kind($mode_group)
{
	$g = strtolower(trim((string) $mode_group));
	if ($g === 'road cargo') {
		return 'road';
	}
	if (strpos($g, 'train') !== false) {
		return 'train';
	}
	if (strpos($g, 'air') !== false || strpos($g, 'flight') !== false) {
		return 'flight';
	}
	return '';
}

function quotation_source_ids_from_stored($row)
{
	$txt = trim((string) ($row['vehicle_text'] ?? ''));
	$train_id = 0;
	$flight_id = 0;
	if (preg_match('/^train:(\d+)$/', $txt, $m)) {
		$train_id = (int) $m[1];
	} elseif (preg_match('/^flight:(\d+)$/', $txt, $m)) {
		$flight_id = (int) $m[1];
	}
	return array($train_id, $flight_id);
}

function quotation_active_trains($conn)
{
	$rows = array();
	$q = @mysqli_query($conn, 'SELECT train_id, train_name, train_number, journey_hours FROM train WHERE status=0 ORDER BY train_name ASC, train_number ASC');
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$rows[] = $r;
		}
	}
	return $rows;
}

function quotation_active_flights($conn)
{
	$rows = array();
	$q = @mysqli_query($conn, 'SELECT flight_id, flight_name, flight_number, journey_hours FROM flight WHERE status=0 ORDER BY flight_name ASC, flight_number ASC');
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$rows[] = $r;
		}
	}
	return $rows;
}

function quotation_transport_option_label($name, $number)
{
	$name = trim((string) $name);
	$number = trim((string) $number);
	if ($name !== '' && $number !== '') {
		return $name . ' — ' . $number;
	}
	return $name !== '' ? $name : $number;
}

function quotation_train_label_by_id($conn, $train_id)
{
	$train_id = (int) $train_id;
	if ($train_id <= 0) {
		return '—';
	}
	$q = @mysqli_query($conn, "SELECT train_name, train_number FROM train WHERE train_id='$train_id' LIMIT 1");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return '—';
	}
	$lbl = quotation_transport_option_label($row['train_name'] ?? '', $row['train_number'] ?? '');
	return $lbl !== '' ? $lbl : '—';
}

function quotation_flight_label_by_id($conn, $flight_id)
{
	$flight_id = (int) $flight_id;
	if ($flight_id <= 0) {
		return '—';
	}
	$q = @mysqli_query($conn, "SELECT flight_name, flight_number FROM flight WHERE flight_id='$flight_id' LIMIT 1");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return '—';
	}
	$lbl = quotation_transport_option_label($row['flight_name'] ?? '', $row['flight_number'] ?? '');
	return $lbl !== '' ? $lbl : '—';
}

/** Single train/flight row (id, label, hours) or null. */
function quotation_transport_row_by_id($conn, $kind, $id)
{
	$id = (int) $id;
	if ($id <= 0 || ($kind !== 'train' && $kind !== 'flight')) {
		return null;
	}
	$sql = $kind === 'train'
		? "SELECT train_id AS id, train_name AS name, train_number AS num, journey_hours FROM train WHERE train_id='$id' LIMIT 1"
		: "SELECT flight_id AS id, flight_name AS name, flight_number AS num, journey_hours FROM flight WHERE flight_id='$id' LIMIT 1";
	$q = @mysqli_query($conn, $sql);
	$r = $q ? mysqli_fetch_assoc($q) : null;
	if (!$r) {
		return null;
	}
	return array(
		'id' => (int) $r['id'],
		'text' => quotation_transport_option_label($r['name'] ?? '', $r['num'] ?? ''),
		'hours' => trim((string) ($r['journey_hours'] ?? '')),
	);
}

/** Server-side search used by the remote train/flight dropdown (top N matches). */
function quotation_transport_search($conn, $kind, $term, $limit = 10)
{
	if ($kind !== 'train' && $kind !== 'flight') {
		return array();
	}
	$limit = max(1, min(50, (int) $limit));
	$tbl = $kind;
	$idc = $kind . '_id';
	$nc = $kind . '_name';
	$numc = $kind . '_number';
	$where = 'status=0';
	$order = "$nc ASC, $numc ASC";
	$term = trim((string) $term);
	// Word-based search: "chennai delhi", "chennai to delhi", "agartala agra" all match names like
	// "Chennai Central To NDLS New Delhi" / "Agartala–Agra Superfast Express" regardless of separators.
	$words = preg_split('/[\s,\/()\[\]\-–—→>:;|]+/u', mb_strtolower($term, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
	$stop = array('to', 'via', 'and', 'from', 'express', 'exp', 'train', 'flight');
	$filtered = array_values(array_filter($words, function ($w) use ($stop) {
		return !in_array($w, $stop, true);
	}));
	if ($filtered !== array()) {
		$words = $filtered;
	}
	$words = array_slice(array_values(array_unique($words)), 0, 6);
	if ($words !== array()) {
		$esc = function ($s) use ($conn) {
			return mysqli_real_escape_string($conn, addcslashes($s, '%_\\'));
		};
		$hay = "CONCAT_WS(' ', $nc, $numc, loading_point1, loading_point2, loading_point3, loading_point4)";
		$conds = array();
		foreach ($words as $w) {
			$conds[] = "$hay LIKE '%" . $esc($w) . "%'";
		}
		$where .= ' AND ' . implode(' AND ', $conds);
		$full = $esc($term);
		$first = $esc($words[0]);
		$rank = array(
			"($numc = '" . mysqli_real_escape_string($conn, $term) . "') DESC",
			"($numc LIKE '$full%') DESC",
			"($nc LIKE '%$full%') DESC",
		);
		if (count($words) > 1) {
			// Keep the typed order first: "chennai delhi" → Chennai…Delhi before Delhi…Chennai.
			$last = $esc($words[count($words) - 1]);
			$p1 = "LOCATE('$first', LOWER($nc))";
			$p2 = "LOCATE('$last', LOWER($nc))";
			$rank[] = "($p1 > 0 AND $p1 < $p2) DESC";
			// …and closest together: "Chennai to Delhi" before "Chandigarh to Chennai via Delhi".
			$rank[] = "IF($p1 > 0 AND $p1 < $p2, $p2 - $p1, 9999) ASC";
		}
		$rank[] = "($nc LIKE '$first%') DESC";
		$order = implode(', ', $rank) . ', ' . $order;
	}
	$out = array();
	$q = @mysqli_query($conn, "SELECT $idc AS id, $nc AS name, $numc AS num, journey_hours FROM $tbl WHERE $where ORDER BY $order LIMIT $limit");
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$out[] = array(
				'id' => (int) $r['id'],
				'text' => quotation_transport_option_label($r['name'] ?? '', $r['num'] ?? ''),
				'hours' => trim((string) ($r['journey_hours'] ?? '')),
			);
		}
	}
	return $out;
}

/**
 * Train / flight <select> that only carries the placeholder + the currently selected option.
 * Options are searched on demand via quotation_transport_search.php (javascripts/ew-remote-source.js).
 */
function quotation_remote_source_select_html($conn, $kind, $attrs, $selected_id, $placeholder, $disabled = false)
{
	$html = '<select ' . $attrs . ' data-remote-source="' . $kind . '"' . ($disabled ? ' disabled' : '') . '>'
		. '<option value="" data-hours="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
	$row = quotation_transport_row_by_id($conn, $kind, $selected_id);
	if ($row) {
		$html .= '<option value="' . $row['id'] . '" data-hours="' . htmlspecialchars($row['hours'], ENT_QUOTES, 'UTF-8') . '" selected>'
			. htmlspecialchars($row['text'], ENT_QUOTES, 'UTF-8') . '</option>';
	}
	return $html . '</select>';
}

function quotation_source_selects_html($conn, $prefix, $row, $vehicle_types, $trains, $flights, $form_editable, $mode_group)
{
	$prefix = ($prefix === 'md') ? 'md' : 'mm';
	$kind = quotation_mode_source_kind($mode_group);
	list($train_id, $flight_id) = quotation_source_ids_from_stored($row);
	$vt_id = (int) ($row['vehicle_type_id'] ?? 0);
	$dis = $form_editable ? '' : ' disabled';
	$style = function ($which) use ($kind) {
		return $kind === $which ? '' : 'display:none;';
	};
	$empty_style = $kind === '' ? '' : 'display:none;';
	$html = '<select class="form-control ' . $prefix . '-source-empty" style="' . $empty_style . '" disabled>'
		. '<option value="">Select source</option></select>';
	$html .= '<select name="' . $prefix . '_vehicle_type_id[]" class="form-control ' . $prefix . '-source ' . $prefix . '-source-road" data-remote-source="vehicle" data-search-local="1" style="' . $style('road') . '"' . $dis . '>'
		. '<option value="">Select vehicle</option>';
	foreach ($vehicle_types as $vt) {
		$vid = (int) $vt['vehicle_type_id'];
		$sel = ($vt_id === $vid) ? ' selected' : '';
		$opt_lbl = function_exists('quotation_vehicle_type_dropdown_label')
			? quotation_vehicle_type_dropdown_label($conn, $vid, $vt['type_name'] ?? '')
			: trim((string) ($vt['type_name'] ?? ''));
		$html .= '<option value="' . $vid . '"' . $sel . '>' . htmlspecialchars($opt_lbl, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	$html .= '</select>';
	// Trains / flights are large tables (10k+ rows) — never render the full list per row.
	$html .= quotation_remote_source_select_html($conn, 'train',
		'name="' . $prefix . '_train_id[]" class="form-control ' . $prefix . '-source ' . $prefix . '-source-train" style="' . $style('train') . '"',
		$train_id, 'Select train', !$form_editable);
	$html .= quotation_remote_source_select_html($conn, 'flight',
		'name="' . $prefix . '_flight_id[]" class="form-control ' . $prefix . '-source ' . $prefix . '-source-flight" style="' . $style('flight') . '"',
		$flight_id, 'Select flight', !$form_editable);
	return $html;
}

function quotation_ensure_mode_rows_table($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rate_quotation_mode_rows (
		row_id INT(11) NOT NULL AUTO_INCREMENT,
		quotation_id INT(11) NOT NULL,
		sort_no INT(11) NOT NULL DEFAULT 0,
		mode_id INT(11) NOT NULL DEFAULT 0,
		vehicle_type_id INT(11) DEFAULT NULL,
		vehicle_text VARCHAR(200) DEFAULT NULL,
		delivery_days VARCHAR(40) DEFAULT NULL,
		freight_charges DECIMAL(14,2) NOT NULL DEFAULT 0,
		doc_charges DECIMAL(14,2) NOT NULL DEFAULT 0,
		loading_unloading_charges DECIMAL(14,2) NOT NULL DEFAULT 0,
		others DECIMAL(14,2) NOT NULL DEFAULT 0,
		row_total DECIMAL(14,2) NOT NULL DEFAULT 0,
		PRIMARY KEY (row_id),
		KEY idx_rq_mode_row (quotation_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$chk = @mysqli_query($conn, "SHOW COLUMNS FROM rate_quotation_mode_rows LIKE 'tempo_charges'");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		@mysqli_query($conn, 'ALTER TABLE rate_quotation_mode_rows ADD COLUMN tempo_charges DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER freight_charges');
	}
}

function quotation_mode_is_road_cargo_group($mode_group)
{
	return strcasecmp(trim((string) $mode_group), 'Road Cargo') === 0;
}

/** Active modes for multi-mode table (includes mode_group when column exists). */
function quotation_mode_transport_catalog($conn)
{
	$rows = array();
	$has_group = false;
	$chk = @mysqli_query($conn, "SHOW COLUMNS FROM mode_of_transportation LIKE 'mode_group'");
	if ($chk && mysqli_num_rows($chk) > 0) {
		$has_group = true;
	}
	$sql = $has_group
		? 'SELECT mode_id, mode_type, mode_group FROM mode_of_transportation WHERE status=0 ORDER BY mode_type ASC'
		: 'SELECT mode_id, mode_type FROM mode_of_transportation WHERE status=0 ORDER BY mode_type ASC';
	$q = @mysqli_query($conn, $sql);
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$rows[] = array(
				'mode_id' => (int) $r['mode_id'],
				'mode_type' => trim((string) ($r['mode_type'] ?? '')),
				'mode_group' => $has_group ? trim((string) ($r['mode_group'] ?? '')) : '',
			);
		}
	}
	return $rows;
}

function quotation_get_mode_rows($conn, $quotation_id)
{
	quotation_ensure_mode_rows_table($conn);
	$quotation_id = (int) $quotation_id;
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM rate_quotation_mode_rows WHERE quotation_id='$quotation_id' ORDER BY sort_no ASC, row_id ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function quotation_default_mode_rows_for_form()
{
	return array(
		array(
			'row_id' => 0,
			'mode_id' => 0,
			'mode_type' => '',
			'mode_group' => '',
			'vehicle_type_id' => 0,
			'vehicle_text' => '',
			'delivery_days' => '',
			'freight_charges' => '',
			'tempo_charges' => '',
			'doc_charges' => '',
			'loading_unloading_charges' => '',
			'others' => '',
			'row_total' => 0,
		),
	);
}

function quotation_hydrate_mode_rows_for_form($conn, $quotation_id)
{
	$stored = quotation_get_mode_rows($conn, $quotation_id);
	if ($stored !== array()) {
		$catalog = quotation_mode_transport_catalog($conn);
		$by_mode = array();
		foreach ($catalog as $m) {
			$by_mode[(int) $m['mode_id']] = $m;
		}
		$out = array();
		foreach ($stored as $row) {
			$mid = (int) $row['mode_id'];
			$meta = $by_mode[$mid] ?? array('mode_type' => '', 'mode_group' => '');
			$out[] = array(
				'row_id' => (int) $row['row_id'],
				'mode_id' => $mid,
				'mode_type' => $meta['mode_type'],
				'mode_group' => $meta['mode_group'],
				'vehicle_type_id' => (int) ($row['vehicle_type_id'] ?? 0),
				'vehicle_text' => trim((string) ($row['vehicle_text'] ?? '')),
				'delivery_days' => trim((string) ($row['delivery_days'] ?? '')),
				'freight_charges' => rtrim(rtrim(number_format((float) $row['freight_charges'], 2, '.', ''), '0'), '.'),
				'tempo_charges' => rtrim(rtrim(number_format((float) ($row['tempo_charges'] ?? 0), 2, '.', ''), '0'), '.'),
				'doc_charges' => rtrim(rtrim(number_format((float) $row['doc_charges'], 2, '.', ''), '0'), '.'),
				'loading_unloading_charges' => rtrim(rtrim(number_format((float) $row['loading_unloading_charges'], 2, '.', ''), '0'), '.'),
				'others' => rtrim(rtrim(number_format((float) $row['others'], 2, '.', ''), '0'), '.'),
				'row_total' => (float) $row['row_total'],
			);
		}
		return $out;
	}
	return quotation_default_mode_rows_for_form();
}

function quotation_parse_money_field($v)
{
	$v = str_replace(',', '', trim((string) $v));
	if ($v === '') {
		return 0.0;
	}
	return (float) $v;
}

function quotation_parse_mode_rows_from_post($conn, $payload)
{
	$mode_ids = $payload['mm_mode_id'] ?? array();
	if (!is_array($mode_ids) || count($mode_ids) === 0) {
		return array('ok' => false, 'message' => 'Mode-wise quotation rows are missing.');
	}
	$vehicle_type_ids = $payload['mm_vehicle_type_id'] ?? array();
	$train_ids = $payload['mm_train_id'] ?? array();
	$flight_ids = $payload['mm_flight_id'] ?? array();
	$delivery_days = $payload['mm_delivery_days'] ?? array();
	$freight = $payload['mm_freight'] ?? array();
	$tempo = $payload['mm_tempo'] ?? array();
	$mode_groups = array();
	foreach (quotation_mode_transport_catalog($conn) as $m) {
		$mode_groups[(int) $m['mode_id']] = trim((string) ($m['mode_group'] ?? ''));
	}

	$rows = array();
	$n = count($mode_ids);
	for ($i = 0; $i < $n; $i++) {
		$mode_id = (int) ($mode_ids[$i] ?? 0);
		if ($mode_id <= 0) {
			continue;
		}
		$f = quotation_parse_money_field($freight[$i] ?? 0);
		$tempo_amt = quotation_parse_money_field($tempo[$i] ?? 0);
		$kind = quotation_mode_source_kind($mode_groups[$mode_id] ?? '');
		$vt = 0;
		$vtxt = '';
		if ($kind === 'road') {
			$vt = (int) ($vehicle_type_ids[$i] ?? 0);
		} elseif ($kind === 'train') {
			$tid = (int) ($train_ids[$i] ?? 0);
			$vtxt = $tid > 0 ? ('train:' . $tid) : '';
		} elseif ($kind === 'flight') {
			$fid = (int) ($flight_ids[$i] ?? 0);
			$vtxt = $fid > 0 ? ('flight:' . $fid) : '';
		}
		$rows[] = array(
			'mode_id' => $mode_id,
			'vehicle_type_id' => $vt > 0 ? $vt : 0,
			'vehicle_text' => $vtxt,
			'delivery_days' => trim((string) ($delivery_days[$i] ?? '')),
			'freight_charges' => $f,
			'tempo_charges' => $tempo_amt,
			'doc_charges' => 0,
			'loading_unloading_charges' => 0,
			'others' => 0,
			'row_total' => quotation_mode_row_total($f, 0, 0, 0, $tempo_amt),
		);
	}
	if ($rows === array()) {
		return array('ok' => false, 'message' => 'Add at least one mode row with a valid mode.');
	}
	$has_charge = false;
	foreach ($rows as $row) {
		if ((float) ($row['row_total'] ?? 0) > 0) {
			$has_charge = true;
			break;
		}
	}
	if (!$has_charge) {
		return array('ok' => false, 'message' => 'Enter freight charges for at least one mode of transport.');
	}
	return array('ok' => true, 'rows' => $rows);
}

function quotation_save_mode_rows($conn, $quotation_id, $rows)
{
	quotation_ensure_mode_rows_table($conn);
	$quotation_id = (int) $quotation_id;
	mysqli_query($conn, "DELETE FROM rate_quotation_mode_rows WHERE quotation_id='$quotation_id'");
	$esc = function ($v) use ($conn) {
		return mysqli_real_escape_string($conn, (string) $v);
	};
	$sort = 0;
	foreach ($rows as $row) {
		$sort += 10;
		$vt = (int) ($row['vehicle_type_id'] ?? 0);
		$vt_sql = $vt > 0 ? "'$vt'" : 'NULL';
		mysqli_query($conn, "INSERT INTO rate_quotation_mode_rows (
			quotation_id, sort_no, mode_id, vehicle_type_id, vehicle_text, delivery_days,
			freight_charges, tempo_charges, doc_charges, loading_unloading_charges, others, row_total
		) VALUES (
			'$quotation_id', '$sort', '" . (int) $row['mode_id'] . "', $vt_sql,
			'" . $esc($row['vehicle_text'] ?? '') . "',
			'" . $esc($row['delivery_days'] ?? '') . "',
			'" . $esc(number_format((float) $row['freight_charges'], 2, '.', '')) . "',
			'" . $esc(number_format((float) ($row['tempo_charges'] ?? 0), 2, '.', '')) . "',
			'" . $esc(number_format((float) $row['doc_charges'], 2, '.', '')) . "',
			'" . $esc(number_format((float) $row['loading_unloading_charges'], 2, '.', '')) . "',
			'" . $esc(number_format((float) $row['others'], 2, '.', '')) . "',
			'" . $esc(number_format((float) $row['row_total'], 2, '.', '')) . "'
		)");
	}
}

function quotation_mode_row_vehicle_display($conn, $row)
{
	list($train_id, $flight_id) = quotation_source_ids_from_stored($row);
	if ($train_id > 0) {
		return quotation_train_label_by_id($conn, $train_id);
	}
	if ($flight_id > 0) {
		return quotation_flight_label_by_id($conn, $flight_id);
	}
	$vt = (int) ($row['vehicle_type_id'] ?? 0);
	if ($vt > 0) {
		$snap = quotation_vehicle_snapshot($conn, $vt);
		$lbl = trim((string) ($snap['vehicle_label'] ?? ''));
		$dim = trim((string) ($snap['dim_display'] ?? ''));
		if ($lbl !== '') {
			return $dim !== '' ? ($lbl . ' - ' . $dim) : $lbl;
		}
	}
	$txt = trim((string) ($row['vehicle_text'] ?? ''));
	if ($txt === '' || strpos($txt, 'train:') === 0 || strpos($txt, 'flight:') === 0) {
		return '—';
	}
	return $txt;
}

function quotation_multi_mode_rows_for_display($conn, $quotation_id)
{
	$stored = quotation_get_mode_rows($conn, $quotation_id);
	$catalog = quotation_mode_transport_catalog($conn);
	$names = array();
	foreach ($catalog as $m) {
		$names[(int) $m['mode_id']] = $m;
	}
	$out = array();
	foreach ($stored as $row) {
		if ((float) ($row['row_total'] ?? 0) <= 0) {
			continue;
		}
		$mid = (int) $row['mode_id'];
		$meta = $names[$mid] ?? array('mode_type' => '', 'mode_group' => '');
		$row['mode_type'] = $meta['mode_type'];
		$row['mode_group'] = $meta['mode_group'];
		$row['vehicle_display'] = quotation_mode_row_vehicle_display($conn, $row);
		$row['delivery_days_label'] = quotation_delivery_days_label($row['delivery_days'] ?? '');
		$out[] = $row;
	}
	return $out;
}

function quotation_multi_mode_taxable_total($rows)
{
	$sum = 0.0;
	foreach ($rows as $row) {
		$sum += (float) ($row['row_total'] ?? 0);
	}
	return round($sum, 2);
}

function quotation_multi_mode_email_table_html($conn, $quotation_id)
{
	$rows = quotation_multi_mode_rows_for_display($conn, $quotation_id);
	if ($rows === array()) {
		return '';
	}
	$th = 'padding:8px 10px;background:#021659;color:#fff;font-size:13px;font-weight:bold;border:1px solid #021659;text-align:left;';
	$td = 'padding:7px 10px;border:1px solid #cbd5e1;font-size:13px;color:#1a1a1a;vertical-align:top;';
	$html = '<table cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:12px 0 16px;">'
		. '<tr>'
		. '<th style="' . $th . '">Mode</th>'
		. '<th style="' . $th . '">Source of transport</th>'
		. '<th style="' . $th . '">Delivery days</th>'
		. '<th style="' . $th . 'text-align:right;">Freight</th>'
		. '<th style="' . $th . 'text-align:right;">Pickup &amp; Delivery</th>'
		. '</tr>';
	foreach ($rows as $r) {
		$fmt = function ($n) {
			return '&#8377; ' . htmlspecialchars(quotation_format_money_display($n), ENT_QUOTES, 'UTF-8') . ' /-';
		};
		$html .= '<tr>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['mode_type'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['vehicle_display'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . '">' . htmlspecialchars($r['delivery_days_label'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="' . $td . 'text-align:right;">' . $fmt($r['freight_charges'] ?? 0) . '</td>'
			. '<td style="' . $td . 'text-align:right;">' . $fmt($r['tempo_charges'] ?? 0) . '</td>'
			. '</tr>';
	}
	$html .= '</table>';
	return $html;
}

function quotation_render_mode_row_html($conn, $row, $mode_catalog, $vehicle_types, $delivery_days_opts, $form_editable, $trains = array(), $flights = array())
{
	$mode_id = (int) ($row['mode_id'] ?? 0);
	$mode_group = trim((string) ($row['mode_group'] ?? ''));
	ob_start();
	?>
	<tr class="mm-row" data-mode-group="<?php echo htmlspecialchars($mode_group, ENT_QUOTES, 'UTF-8'); ?>">
		<td>
			<select name="mm_mode_id[]" class="form-control mm-mode-select" <?php echo $form_editable ? '' : 'disabled'; ?>>
				<option value="">Select mode</option>
				<?php foreach ($mode_catalog as $m) {
					$mid = (int) $m['mode_id'];
					$sel = ($mode_id === $mid) ? ' selected' : '';
					$grp = htmlspecialchars($m['mode_group'] ?? '', ENT_QUOTES, 'UTF-8');
					echo '<option value="' . $mid . '" data-mode-group="' . $grp . '"' . $sel . '>' . htmlspecialchars($m['mode_type']) . '</option>';
				} ?>
			</select>
		</td>
		<td class="mm-vehicle-cell">
			<?php echo quotation_source_selects_html($conn, 'mm', $row, $vehicle_types, $trains, $flights, $form_editable, $mode_group); ?>
		</td>
		<td>
			<select name="mm_delivery_days[]" class="form-control mm-delivery-days" <?php echo $form_editable ? '' : 'disabled'; ?>>
				<option value="">Select</option>
				<?php foreach ($delivery_days_opts as $dk => $dl) {
					$sel = (($row['delivery_days'] ?? '') === $dk) ? ' selected' : '';
					echo '<option value="' . htmlspecialchars($dk, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($dl) . '</option>';
				} ?>
			</select>
		</td>
		<td><input type="text" name="mm_freight[]" class="form-control mm-amt" value="<?php echo htmlspecialchars($row['freight_charges'] ?? ''); ?>" onpaste="return ewNumericPaste(event,this);" <?php echo $form_editable ? '' : 'readonly'; ?> /></td>
		<td><input type="text" name="mm_tempo[]" class="form-control mm-amt mm-tempo" value="<?php echo htmlspecialchars($row['tempo_charges'] ?? ''); ?>" onpaste="return ewNumericPaste(event,this);" <?php echo $form_editable ? '' : 'readonly'; ?> /></td>
		<?php if ($form_editable) { ?>
			<td class="mm-row-actions text-center">
				<div class="mm-row-action">
					<button type="button" class="mm-row-btn is-add btn-mm-add-row" title="Add mode row"><i class="fa fa-plus"></i></button>
					<button type="button" class="mm-row-btn is-remove btn-mm-remove-row" title="Remove row"><i class="fa fa-minus"></i></button>
				</div>
			</td>
		<?php } ?>
	</tr>
	<?php
	return ob_get_clean();
}
