<?php

require_once __DIR__ . '/vehicle_type_helpers.php';
require_once __DIR__ . '/quotation_consignor_multi_dest.php';

define('QUOTATION_DEST_MASTER_MAX_ROWS', 200);

function quotation_dest_master_ensure_tables($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rate_quotation_dest_master (
		master_id INT(11) NOT NULL AUTO_INCREMENT,
		master_name VARCHAR(200) NOT NULL,
		notes VARCHAR(500) DEFAULT NULL,
		status TINYINT NOT NULL DEFAULT 0,
		created_at DATETIME NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at DATETIME NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (master_id),
		KEY idx_rq_dest_master_status (status)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rate_quotation_dest_master_rows (
		row_id INT(11) NOT NULL AUTO_INCREMENT,
		master_id INT(11) NOT NULL,
		sort_no INT(11) NOT NULL DEFAULT 0,
		destination_city_id INT(11) NOT NULL DEFAULT 0,
		mode_id INT(11) NOT NULL DEFAULT 0,
		vehicle_type_id INT(11) DEFAULT NULL,
		vehicle_text VARCHAR(200) DEFAULT NULL,
		delivery_days VARCHAR(40) DEFAULT NULL,
		freight_charges DECIMAL(14,2) NOT NULL DEFAULT 0,
		tempo_charges DECIMAL(14,2) NOT NULL DEFAULT 0,
		doc_charges DECIMAL(14,2) NOT NULL DEFAULT 0,
		others DECIMAL(14,2) NOT NULL DEFAULT 0,
		row_total DECIMAL(14,2) NOT NULL DEFAULT 0,
		PRIMARY KEY (row_id),
		KEY idx_rq_dest_master_row (master_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function quotation_dest_master_list($conn, $active_only = true)
{
	quotation_dest_master_ensure_tables($conn);
	$where = $active_only ? 'WHERE status=0' : '';
	$rows = array();
	$q = mysqli_query($conn, "SELECT master_id, master_name, notes, status, created_at, updated_at
		FROM rate_quotation_dest_master $where ORDER BY master_name ASC, master_id DESC");
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$mid = (int) $r['master_id'];
			$cq = mysqli_query($conn, "SELECT COUNT(*) AS c FROM rate_quotation_dest_master_rows WHERE master_id='$mid'");
			$cr = $cq ? mysqli_fetch_assoc($cq) : null;
			$r['row_count'] = (int) ($cr['c'] ?? 0);
			$rows[] = $r;
		}
	}
	return $rows;
}

function quotation_dest_master_get($conn, $master_id)
{
	quotation_dest_master_ensure_tables($conn);
	$master_id = (int) $master_id;
	if ($master_id <= 0) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT * FROM rate_quotation_dest_master WHERE master_id='$master_id' LIMIT 1");
	return $q ? mysqli_fetch_assoc($q) : null;
}

/** Single shared template — enter bulk rows once, reopen same screen to continue. */
function quotation_dest_master_get_singleton($conn)
{
	quotation_dest_master_ensure_tables($conn);
	$q = mysqli_query($conn, "SELECT * FROM rate_quotation_dest_master WHERE status=0 ORDER BY master_id ASC LIMIT 1");
	return ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;
}

function quotation_dest_master_singleton_id($conn)
{
	$row = quotation_dest_master_get_singleton($conn);
	return $row ? (int) $row['master_id'] : 0;
}

function quotation_dest_master_list_for_picker($conn)
{
	$row = quotation_dest_master_get_singleton($conn);
	if (!$row) {
		return array();
	}
	$mid = (int) $row['master_id'];
	$cq = mysqli_query($conn, "SELECT COUNT(*) AS c FROM rate_quotation_dest_master_rows WHERE master_id='$mid'");
	$cr = $cq ? mysqli_fetch_assoc($cq) : null;
	$row['row_count'] = (int) ($cr['c'] ?? 0);
	return array($row);
}

function quotation_dest_master_get_rows($conn, $master_id)
{
	quotation_dest_master_ensure_tables($conn);
	$master_id = (int) $master_id;
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM rate_quotation_dest_master_rows WHERE master_id='$master_id' ORDER BY sort_no ASC, row_id ASC");
	if ($q) {
		while ($r = mysqli_fetch_assoc($q)) {
			$rows[] = $r;
		}
	}
	return $rows;
}

function quotation_dest_master_hydrate_rows_for_form($conn, $master_id)
{
	$stored = quotation_dest_master_get_rows($conn, $master_id);
	if ($stored === array()) {
		return quotation_default_destination_rows_for_form();
	}
	$catalog = quotation_mode_transport_catalog($conn);
	$by_mode = array();
	foreach ($catalog as $m) {
		$by_mode[(int) $m['mode_id']] = $m;
	}
	$out = array();
	foreach ($stored as $row) {
		$mid = (int) ($row['mode_id'] ?? 0);
		$meta = $by_mode[$mid] ?? array('mode_group' => '');
		$out[] = array(
			'destination_city_id' => (int) ($row['destination_city_id'] ?? 0),
			'mode_id' => $mid,
			'mode_group' => trim((string) ($meta['mode_group'] ?? '')),
			'vehicle_type_id' => (int) ($row['vehicle_type_id'] ?? 0),
			'vehicle_text' => trim((string) ($row['vehicle_text'] ?? '')),
			'delivery_days' => $row['delivery_days'] ?? '',
			'freight_charges' => rtrim(rtrim(number_format((float) ($row['freight_charges'] ?? 0), 2, '.', ''), '0'), '.'),
			'tempo_charges' => rtrim(rtrim(number_format((float) ($row['tempo_charges'] ?? 0), 2, '.', ''), '0'), '.'),
			'doc_charges' => '',
			'others' => '',
			'row_total' => (float) ($row['row_total'] ?? 0),
		);
	}
	return $out;
}

function quotation_dest_master_save_rows($conn, $master_id, $rows)
{
	quotation_dest_master_ensure_tables($conn);
	$master_id = (int) $master_id;
	mysqli_query($conn, "DELETE FROM rate_quotation_dest_master_rows WHERE master_id='$master_id'");
	$esc = function ($v) use ($conn) {
		return mysqli_real_escape_string($conn, (string) $v);
	};
	$sort = 0;
	foreach ($rows as $row) {
		$sort += 10;
		$vt = (int) ($row['vehicle_type_id'] ?? 0);
		$vt_sql = $vt > 0 ? "'$vt'" : 'NULL';
		$vtxt_sql = "'" . $esc($row['vehicle_text'] ?? '') . "'";
		mysqli_query($conn, "INSERT INTO rate_quotation_dest_master_rows (
			master_id, sort_no, destination_city_id, mode_id, vehicle_type_id, vehicle_text, delivery_days,
			freight_charges, tempo_charges, doc_charges, others, row_total
		) VALUES (
			'$master_id', '$sort', '" . (int) $row['destination_city_id'] . "', '" . (int) $row['mode_id'] . "', $vt_sql, $vtxt_sql,
			'" . $esc($row['delivery_days'] ?? '') . "',
			'" . $esc($row['freight_charges'] ?? 0) . "', '" . $esc($row['tempo_charges'] ?? 0) . "',
			'" . $esc($row['doc_charges'] ?? 0) . "', '" . $esc($row['others'] ?? 0) . "',
			'" . $esc($row['row_total'] ?? 0) . "'
		)");
	}
}

function quotation_dest_master_suggest_name($conn, $rows)
{
	$labels = array();
	$catalog = quotation_city_state_catalog($conn);
	$by_city = array();
	foreach ($catalog as $c) {
		$by_city[(int) $c['city_id']] = $c['label'] ?? '';
	}
	foreach ($rows as $row) {
		$cid = (int) ($row['destination_city_id'] ?? 0);
		if ($cid <= 0) {
			continue;
		}
		$lbl = trim((string) ($by_city[$cid] ?? ''));
		if ($lbl !== '' && !in_array($lbl, $labels, true)) {
			$labels[] = $lbl;
		}
		if (count($labels) >= 3) {
			break;
		}
	}
	$n = count($rows);
	if ($labels !== array()) {
		$head = implode(', ', array_slice($labels, 0, 2));
		if ($n > 2) {
			$name = $head . ' +' . ($n - 2) . ' rows';
		} else {
			$name = $head . ' (' . $n . ' row' . ($n === 1 ? '' : 's') . ')';
		}
	} else {
		$name = 'Quotation master ' . date('d-m-Y H:i');
	}
	if (function_exists('mb_substr')) {
		return mb_substr($name, 0, 190);
	}
	return substr($name, 0, 190);
}

function quotation_dest_master_save($conn, $payload, $master_id, $user_id)
{
	quotation_dest_master_ensure_tables($conn);
	$parsed = quotation_parse_destination_rows_from_post($conn, $payload);
	if (empty($parsed['ok'])) {
		return $parsed;
	}
	$rows = $parsed['rows'];
	if (count($rows) > QUOTATION_DEST_MASTER_MAX_ROWS) {
		return array('ok' => false, 'message' => 'Maximum ' . QUOTATION_DEST_MASTER_MAX_ROWS . ' destination rows allowed per master.');
	}
	$now = date('Y-m-d H:i:s');
	$esc = function ($v) use ($conn) {
		return mysqli_real_escape_string($conn, (string) $v);
	};
	$master_id = (int) $master_id;
	$user_id = (int) $user_id;
	if ($master_id <= 0) {
		$existing_singleton = quotation_dest_master_singleton_id($conn);
		if ($existing_singleton > 0) {
			$master_id = $existing_singleton;
		}
	}
	if ($master_id > 0) {
		$existing = quotation_dest_master_get($conn, $master_id);
		if (!$existing) {
			return array('ok' => false, 'message' => 'Master not found.');
		}
		$name = quotation_dest_master_suggest_name($conn, $rows);
		$ok = mysqli_query($conn, "UPDATE rate_quotation_dest_master SET
			master_name='" . $esc($name) . "',
			updated_at='$now',
			updated_by='$user_id'
			WHERE master_id='$master_id' LIMIT 1");
		if (!$ok) {
			return array('ok' => false, 'message' => 'Could not update master. ' . mysqli_error($conn));
		}
	} else {
		$name = quotation_dest_master_suggest_name($conn, $rows);
		$ok = mysqli_query($conn, "INSERT INTO rate_quotation_dest_master (master_name, notes, status, created_at, created_by, updated_at, updated_by)
			VALUES ('" . $esc($name) . "', NULL, 0, '$now', '$user_id', '$now', '$user_id')");
		if (!$ok) {
			return array('ok' => false, 'message' => 'Could not create master. ' . mysqli_error($conn));
		}
		$master_id = (int) mysqli_insert_id($conn);
	}
	if ($master_id <= 0) {
		return array('ok' => false, 'message' => 'Could not save master.');
	}
	quotation_dest_master_save_rows($conn, $master_id, $rows);
	return array('ok' => true, 'master_id' => $master_id);
}

function quotation_dest_master_delete($conn, $master_id)
{
	quotation_dest_master_ensure_tables($conn);
	$master_id = (int) $master_id;
	if ($master_id <= 0) {
		return false;
	}
	mysqli_query($conn, "DELETE FROM rate_quotation_dest_master_rows WHERE master_id='$master_id'");
	return mysqli_query($conn, "DELETE FROM rate_quotation_dest_master WHERE master_id='$master_id' LIMIT 1");
}

function quotation_dest_master_toggle_status($conn, $master_id, $status, $user_id)
{
	quotation_dest_master_ensure_tables($conn);
	$master_id = (int) $master_id;
	$status = (int) $status;
	$user_id = (int) $user_id;
	$now = date('Y-m-d H:i:s');
	return mysqli_query($conn, "UPDATE rate_quotation_dest_master SET status='$status', updated_at='$now', updated_by='$user_id' WHERE master_id='$master_id' LIMIT 1");
}

function quotation_dest_master_rows_html($conn, $master_id, $form_editable = true)
{
	$rows = quotation_dest_master_hydrate_rows_for_form($conn, $master_id);
	$city_catalog = quotation_city_state_catalog($conn);
	$modes = quotation_mode_transport_catalog($conn);
	$vehicle_types = ew_vehicle_type_list($conn, true);
	$delivery_days_opts = quotation_delivery_days_options();
	$html = '';
	foreach ($rows as $dr) {
		$html .= quotation_render_destination_row_html($conn, $dr, $city_catalog, $modes, $vehicle_types, $delivery_days_opts, $form_editable);
	}
	return $html;
}
