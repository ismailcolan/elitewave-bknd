<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/trip_summary_helpers.php');

trip_summary_json_require_access();
header('Content-Type: application/json; charset=utf-8');
trip_summary_ensure_schema($conn);

$cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : '';

function trip_summary_json_out($payload)
{
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

if ($cmd === 'fetch_options') {
	$mode_id = (int) ($_REQUEST['mode_id'] ?? 0);
	trip_summary_json_out(array(
		'status' => 0,
		'cities' => trip_summary_city_options($conn),
		'modes' => trip_summary_mode_options($conn),
		'sources' => $mode_id > 0 ? trip_summary_source_options($conn, $mode_id) : array(),
	));
}

if ($cmd === 'fetch_sources') {
	$mode_id = (int) ($_REQUEST['mode_id'] ?? 0);
	if ($mode_id <= 0) {
		trip_summary_json_out(array('status' => 1, 'message' => 'Select mode first.'));
	}
	trip_summary_json_out(array(
		'status' => 0,
		'sources' => trip_summary_source_options($conn, $mode_id),
	));
}

if ($cmd === 'lookup_gcn') {
	$grn_no = trim((string) ($_REQUEST['grn_no'] ?? ''));
	$exclude_trip_id = (int) ($_REQUEST['trip_summary_id'] ?? 0);
	$exclude_keys = array();
	if (!empty($_REQUEST['exclude_keys'])) {
		$decoded = json_decode((string) $_REQUEST['exclude_keys'], true);
		if (is_array($decoded)) {
			$exclude_keys = $decoded;
		}
	}
	$result = trip_summary_lookup_gcn($conn, $grn_no, $exclude_trip_id, $exclude_keys);
	if (empty($result['ok'])) {
		trip_summary_json_out(array('status' => 1, 'message' => $result['message'] ?? 'GCN lookup failed.'));
	}
	trip_summary_json_out(array('status' => 0, 'gcn' => $result['gcn']));
}

if ($cmd === 'fetch') {
	$id = (int) ($_REQUEST['trip_summary_id'] ?? 0);
	$saved = trip_summary_load($conn, $id);
	if (!$saved) {
		trip_summary_json_out(array('status' => 1, 'message' => 'Trip summary not found.'));
	}
	$h = $saved['header'];
	trip_summary_json_out(array(
		'status' => 0,
		'trip_summary_id' => (int) $h['trip_summary_id'],
		'sheet_no' => $h['sheet_no'],
		'sheet_date' => $h['sheet_date'],
		'origin_id' => (int) $h['origin_id'],
		'destination_id' => (int) $h['destination_id'],
		'mode_id' => (int) $h['mode_id'],
		'mode_label' => $h['mode_label'],
		'source_value' => $h['source_value'] ?? '',
		'source_manual' => $h['source_manual'] ?? '',
		'source_display' => $h['source_display'] ?? '',
		'sheet_status' => $h['status'],
		'total_gcn' => (int) $h['total_gcn'],
		'total_packages' => (int) $h['total_packages'],
		'total_loaded' => (int) $h['total_loaded'],
		'lines' => $saved['lines'],
	));
}

if ($cmd === 'save') {
	$lines_raw = isset($_POST['lines']) ? $_POST['lines'] : (isset($_REQUEST['lines']) ? $_REQUEST['lines'] : array());
	if (is_string($lines_raw)) {
		$decoded = json_decode($lines_raw, true);
		$lines_raw = is_array($decoded) ? $decoded : array();
	}
	$result = trip_summary_save($conn, array(
		'trip_summary_id' => (int) ($_REQUEST['trip_summary_id'] ?? 0),
		'sheet_date' => $_REQUEST['sheet_date'] ?? '',
		'origin_id' => $_REQUEST['origin_id'] ?? 0,
		'destination_id' => $_REQUEST['destination_id'] ?? 0,
		'mode_id' => $_REQUEST['mode_id'] ?? 0,
		'source_value' => $_REQUEST['source_value'] ?? '',
		'source_manual' => $_REQUEST['source_manual'] ?? '',
		'lines' => $lines_raw,
	), (int) $_SESSION['user_id']);

	if (empty($result['ok'])) {
		trip_summary_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Save failed.'));
	}
	trip_summary_json_out(array(
		'status' => 0,
		'message' => $result['message'],
		'trip_summary_id' => (int) $result['trip_summary_id'],
		'sheet_no' => $result['sheet_no'],
	));
}

if ($cmd === 'cancel') {
	$id = (int) ($_REQUEST['trip_summary_id'] ?? 0);
	$result = trip_summary_cancel($conn, $id, (int) $_SESSION['user_id']);
	if (empty($result['ok'])) {
		trip_summary_json_out(array('status' => 1, 'message' => $result['message'] ?? 'Cancel failed.'));
	}
	trip_summary_json_out(array('status' => 0, 'message' => $result['message']));
}

trip_summary_json_out(array('status' => 1, 'message' => 'Unknown command.'));
