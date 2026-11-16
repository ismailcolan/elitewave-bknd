<?php

require_once __DIR__ . '/ew_master_usage_helpers.php';
require_once __DIR__ . '/ew_geo_scope.php';

/**
 * Server-side DataTables (1.9) for flight / train masters.
 * Avoids rendering 10k+ HTML rows on first paint.
 */
function ew_master_carrier_list_ensure_indexes($conn, $entity)
{
	static $done = array();
	if (!empty($done[$entity])) {
		return;
	}
	$done[$entity] = true;

	if ($entity === 'flight') {
		ew_geo_scope_ensure_column($conn, 'flight');
		$chk = @mysqli_query($conn, "SHOW INDEX FROM flight WHERE Key_name='idx_flight_list'");
		if (!$chk || mysqli_num_rows($chk) === 0) {
			@mysqli_query($conn, 'ALTER TABLE flight ADD INDEX idx_flight_list (geo_scope, flight_id)');
		}
		$chk2 = @mysqli_query($conn, "SHOW INDEX FROM flight WHERE Key_name='idx_flight_number'");
		if (!$chk2 || mysqli_num_rows($chk2) === 0) {
			@mysqli_query($conn, 'ALTER TABLE flight ADD INDEX idx_flight_number (flight_number)');
		}
		return;
	}

	if ($entity === 'train') {
		ew_geo_scope_ensure_column($conn, 'train');
		$chk = @mysqli_query($conn, "SHOW INDEX FROM train WHERE Key_name='idx_train_list'");
		if (!$chk || mysqli_num_rows($chk) === 0) {
			@mysqli_query($conn, 'ALTER TABLE train ADD INDEX idx_train_list (train_id)');
		}
		$chk2 = @mysqli_query($conn, "SHOW INDEX FROM train WHERE Key_name='idx_train_number'");
		if (!$chk2 || mysqli_num_rows($chk2) === 0) {
			@mysqli_query($conn, 'ALTER TABLE train ADD INDEX idx_train_number (train_number)');
		}
	}
}

function ew_master_carrier_list_action_html($entity, $row, $in_use)
{
	$id = (int) ($entity === 'flight' ? ($row['flight_id'] ?? 0) : ($row['train_id'] ?? 0));
	$status = (int) ($row['status'] ?? 0);
	$edit = ew_master_edit_action_html(array(
		'in_use' => $in_use,
		'button_id' => $id,
		'button_class' => 'table-actions btn-edit',
	));
	if ($status === 0) {
		$active = '<a class="table-actions btn-active" data-status="0" title="InActive" id="' . $id . '"><i class="fa fa-check"></i></a>';
	} else {
		$active = '<a class="table-actions btn-active" style="color:red;" data-status="1" title="Active" id="' . $id . '"><i class="fa fa-times"></i></a>';
	}
	$del = '<a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="' . $id . '"><i class="fa fa-trash-o"></i></a>';
	return '<div class="action-buttons">' . $edit . $active . $del . '</div>';
}

function ew_master_carrier_list_datatable($conn, $entity)
{
	$entity = ($entity === 'train') ? 'train' : 'flight';
	ew_master_carrier_list_ensure_indexes($conn, $entity);

	$id_col = $entity === 'flight' ? 'flight_id' : 'train_id';
	$name_col = $entity === 'flight' ? 'flight_name' : 'train_name';
	$number_col = $entity === 'flight' ? 'flight_number' : 'train_number';
	$table = $entity;

	$echo = isset($_GET['sEcho']) ? (int) $_GET['sEcho'] : 0;
	$start = isset($_GET['iDisplayStart']) ? max(0, (int) $_GET['iDisplayStart']) : 0;
	$length = isset($_GET['iDisplayLength']) ? (int) $_GET['iDisplayLength'] : 10;
	if ($length < 1 || $length > 100) {
		$length = 10;
	}
	$search = isset($_GET['sSearch']) ? trim((string) $_GET['sSearch']) : '';
	$sort_col = isset($_GET['iSortCol_0']) ? (int) $_GET['iSortCol_0'] : 1;
	$sort_dir = (isset($_GET['sSortDir_0']) && strtolower((string) $_GET['sSortDir_0']) === 'desc') ? 'DESC' : 'ASC';

	$order_map = array(
		0 => $id_col,
		1 => $name_col,
		2 => $number_col,
		3 => 'journey_hours',
	);
	$order_by = isset($order_map[$sort_col]) ? $order_map[$sort_col] : $name_col;

	$where = array('1=1');
	if ($entity === 'flight') {
		$where[] = ew_geo_scope_where($conn);
	}

	if ($search !== '') {
		$esc = mysqli_real_escape_string($conn, $search);
		$where[] = "($name_col LIKE '%$esc%' OR $number_col LIKE '%$esc%' OR journey_hours LIKE '%$esc%')";
	}
	$where_sql = implode(' AND ', $where);

	$total = 0;
	$total_where = ($entity === 'flight') ? ('WHERE ' . ew_geo_scope_where($conn)) : '';
	$tq = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$table` $total_where");
	if ($tq && ($tr = mysqli_fetch_assoc($tq))) {
		$total = (int) $tr['c'];
	}

	$filtered = $total;
	if ($search !== '' || ($entity === 'flight' && $total_where !== '')) {
		$fq = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$table` WHERE $where_sql");
		if ($fq && ($fr = mysqli_fetch_assoc($fq))) {
			$filtered = (int) $fr['c'];
		}
	}

	$aaData = array();
	$sql = "SELECT `$id_col`, `$name_col`, `$number_col`, journey_hours, status
		FROM `$table`
		WHERE $where_sql
		ORDER BY `$order_by` $sort_dir
		LIMIT $start, $length";
	$q = mysqli_query($conn, $sql);
	$rows = array();
	$ids = array();
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
			$ids[] = (int) $row[$id_col];
		}
	}
	$usage = ew_master_usage_map($conn, $entity, $ids);
	$sno = $start + 1;
	foreach ($rows as $row) {
		$id = (int) $row[$id_col];
		$aaData[] = array(
			'<span class="text-center">' . $sno . '</span>',
			htmlspecialchars((string) $row[$name_col], ENT_QUOTES, 'UTF-8'),
			htmlspecialchars((string) ($row[$number_col] ?? ''), ENT_QUOTES, 'UTF-8'),
			htmlspecialchars((string) ($row['journey_hours'] ?? ''), ENT_QUOTES, 'UTF-8'),
			ew_master_carrier_list_action_html($entity, $row, !empty($usage[$id])),
		);
		$sno++;
	}

	return array(
		'sEcho' => $echo,
		'iTotalRecords' => $total,
		'iTotalDisplayRecords' => $filtered,
		'aaData' => $aaData,
	);
}
