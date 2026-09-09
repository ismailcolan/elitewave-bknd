<?php
require_once __DIR__ . '/billing_functions.php';

function trip_summary_require_access()
{
	if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
		header('Location: index.php');
		exit;
	}
	if (!in_array($_SESSION['role'], array('AD', 'USER'), true)) {
		header('Location: dashboard.php');
		exit;
	}
}

function trip_summary_json_require_access()
{
	if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('status' => 1, 'message' => 'Session expired.'));
		exit;
	}
	if (!in_array($_SESSION['role'], array('AD', 'USER'), true)) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('status' => 1, 'message' => 'Access denied.'));
		exit;
	}
}

function trip_summary_ftl_type_options()
{
	return array(
		array('value' => 'ftl:Single Axle Vehicle: 07MT', 'label' => 'Single Axle Vehicle: 07MT', 'type' => 'ftl'),
		array('value' => 'ftl:Multi Axle Vehicle : 10MT/14MT/17MT', 'label' => 'Multi Axle Vehicle : 10MT/14MT/17MT', 'type' => 'ftl'),
		array('value' => 'ftl:22ft Vehicle : 07MT', 'label' => '22ft Vehicle : 07MT', 'type' => 'ftl'),
		array('value' => 'ftl:18ft Vehicle : 06MT', 'label' => '18ft Vehicle : 06MT', 'type' => 'ftl'),
		array('value' => 'ftl:Eicher 19 Vehicle : 7MT/8MT/9MT', 'label' => 'Eicher 19 Vehicle : 7MT/8MT/9MT', 'type' => 'ftl'),
		array('value' => 'ftl:Eicher 17 Vehicle : 5MT', 'label' => 'Eicher 17 Vehicle : 5MT', 'type' => 'ftl'),
		array('value' => 'ftl:Eicher 19 Vechicle:4MT', 'label' => 'Eicher 19 Vechicle:4MT', 'type' => 'ftl'),
	);
}

function trip_summary_train_preset_options()
{
	return array(
		array('value' => 'train_preset:1', 'label' => 'Rajdhani Express', 'type' => 'train_preset', 'id' => 1),
		array('value' => 'train_preset:2', 'label' => 'Others (enter train name)', 'type' => 'train_preset', 'id' => 2),
	);
}

function trip_summary_ensure_schema($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS trip_summary (
		trip_summary_id INT(11) NOT NULL AUTO_INCREMENT,
		sheet_no VARCHAR(30) NOT NULL DEFAULT '',
		sheet_date VARCHAR(20) DEFAULT '',
		origin_id INT(11) NOT NULL DEFAULT 0,
		destination_id INT(11) NOT NULL DEFAULT 0,
		mode_id INT(11) NOT NULL DEFAULT 0,
		source_type VARCHAR(30) DEFAULT '',
		source_id INT(11) NOT NULL DEFAULT 0,
		source_label VARCHAR(255) DEFAULT '',
		source_manual VARCHAR(255) DEFAULT '',
		status VARCHAR(30) NOT NULL DEFAULT 'Created',
		total_gcn INT(11) NOT NULL DEFAULT 0,
		total_packages INT(11) NOT NULL DEFAULT 0,
		total_loaded INT(11) NOT NULL DEFAULT 0,
		remarks TEXT,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (trip_summary_id),
		KEY idx_ts_sheet_no (sheet_no),
		KEY idx_ts_status (status)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS trip_summary_gcn (
		trip_gcn_id INT(11) NOT NULL AUTO_INCREMENT,
		trip_summary_id INT(11) NOT NULL,
		sort_no INT(11) NOT NULL DEFAULT 1,
		trans_table VARCHAR(100) NOT NULL DEFAULT '',
		transaction_id INT(11) NOT NULL DEFAULT 0,
		grn_no VARCHAR(50) DEFAULT '',
		grn_date VARCHAR(20) DEFAULT '',
		consignor_id INT(11) NOT NULL DEFAULT 0,
		consignee_id INT(11) NOT NULL DEFAULT 0,
		consignor_name VARCHAR(255) DEFAULT '',
		consignee_name VARCHAR(255) DEFAULT '',
		no_of_packages INT(11) NOT NULL DEFAULT 0,
		loaded_no INT(11) NOT NULL DEFAULT 0,
		remarks TEXT,
		created_at VARCHAR(20) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		PRIMARY KEY (trip_gcn_id),
		KEY idx_tsg_header (trip_summary_id),
		UNIQUE KEY uk_tsg_gcn (trip_summary_id, trans_table, transaction_id),
		KEY idx_tsg_grn (grn_no)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function trip_summary_city_options($conn)
{
	$rows = array();
	$q = mysqli_query($conn, 'SELECT city_id, city_name FROM city WHERE status=0 ORDER BY city_name ASC');
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = array('city_id' => (int) $row['city_id'], 'city_name' => $row['city_name']);
		}
	}
	return $rows;
}

function trip_summary_mode_options($conn)
{
	$rows = array();
	$q = mysqli_query($conn, 'SELECT mode_id, mode_type FROM mode_of_transportation WHERE status=0 ORDER BY mode_type ASC');
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = array('mode_id' => (int) $row['mode_id'], 'mode_type' => $row['mode_type']);
		}
	}
	return $rows;
}

function trip_summary_source_options($conn, $mode_id)
{
	$mode_id = (int) $mode_id;
	$options = array();

	if ($mode_id === 2) {
		foreach (trip_summary_train_preset_options() as $opt) {
			$options[] = $opt;
		}
		$tq = mysqli_query($conn, "SELECT train_id, train_name, train_number FROM train WHERE status=0 ORDER BY train_name ASC");
		if ($tq) {
			while ($t = mysqli_fetch_assoc($tq)) {
				$label = trim($t['train_name'] . ' - ' . ($t['train_number'] ?? ''), ' -');
				$options[] = array(
					'value' => 'train:' . (int) $t['train_id'],
					'label' => $label,
					'type' => 'train',
					'id' => (int) $t['train_id'],
				);
			}
		}
	} elseif ($mode_id === 1) {
		$fq = mysqli_query($conn, "SELECT flight_id, flight_name, flight_number FROM flight WHERE status=0 ORDER BY flight_name ASC");
		if ($fq) {
			while ($f = mysqli_fetch_assoc($fq)) {
				$label = trim($f['flight_name'] . ' - ' . ($f['flight_number'] ?? ''), ' -');
				$options[] = array(
					'value' => 'flight:' . (int) $f['flight_id'],
					'label' => $label,
					'type' => 'flight',
					'id' => (int) $f['flight_id'],
				);
			}
		}
	} elseif ($mode_id === 7) {
		foreach (trip_summary_ftl_type_options() as $opt) {
			$options[] = $opt;
		}
	}

	if (in_array($mode_id, array(1, 2, 3, 5, 7, 8), true)) {
		$vq = mysqli_query($conn, "SELECT vehicle_id, vehicle_number, vehicle_type FROM vehicle WHERE status=0 ORDER BY vehicle_number ASC");
		if ($vq) {
			while ($v = mysqli_fetch_assoc($vq)) {
				$label = trim($v['vehicle_number'] . ($v['vehicle_type'] ? ' (' . $v['vehicle_type'] . ')' : ''));
				$options[] = array(
					'value' => 'vehicle:' . (int) $v['vehicle_id'],
					'label' => $label,
					'type' => 'vehicle',
					'id' => (int) $v['vehicle_id'],
				);
			}
		}
	}

	$options[] = array('value' => 'manual:', 'label' => '— Enter manually —', 'type' => 'manual', 'id' => 0);
	return $options;
}

function trip_summary_parse_source($source_value, $source_manual = '')
{
	$source_value = trim((string) $source_value);
	$source_manual = trim((string) $source_manual);
	$out = array('source_type' => 'manual', 'source_id' => 0, 'source_label' => $source_manual, 'source_manual' => $source_manual);

	if ($source_value === '' || $source_value === 'manual:') {
		if ($source_manual === '') {
			return null;
		}
		return $out;
	}

	if (strpos($source_value, ':') !== false) {
		list($type, $id) = explode(':', $source_value, 2);
		$type = trim($type);
		$id = trim($id);
		if ($type === 'manual') {
			if ($source_manual === '') {
				return null;
			}
			$out['source_label'] = $source_manual;
			return $out;
		}
		if ($type === 'ftl') {
			$out['source_type'] = 'ftl';
			$out['source_label'] = $id;
			$out['source_manual'] = $id;
			return $out;
		}
		if ($type === 'train_preset') {
			$presets = trip_summary_train_preset_options();
			foreach ($presets as $p) {
				if ((string) $p['id'] === $id) {
					$out['source_type'] = 'train_preset';
					$out['source_id'] = (int) $id;
					$out['source_label'] = $p['label'];
					if ((int) $id === 2 && $source_manual !== '') {
						$out['source_label'] = $source_manual;
						$out['source_manual'] = $source_manual;
					}
					return $out;
				}
			}
		}
		if (in_array($type, array('train', 'flight', 'vehicle'), true) && (int) $id > 0) {
			$out['source_type'] = $type;
			$out['source_id'] = (int) $id;
			return $out;
		}
	}

	if ($source_manual !== '') {
		$out['source_label'] = $source_manual;
	}
	return $out;
}

function trip_summary_resolve_source_label($conn, $source_type, $source_id, $source_manual, $source_label = '')
{
	if ($source_label !== '') {
		return $source_label;
	}
	$source_id = (int) $source_id;
	if ($source_type === 'train' && $source_id > 0) {
		$q = mysqli_query($conn, "SELECT train_name, train_number FROM train WHERE train_id='$source_id' LIMIT 1");
		if ($q && ($r = mysqli_fetch_assoc($q))) {
			return trim($r['train_name'] . ' - ' . ($r['train_number'] ?? ''), ' -');
		}
	}
	if ($source_type === 'flight' && $source_id > 0) {
		$q = mysqli_query($conn, "SELECT flight_name, flight_number FROM flight WHERE flight_id='$source_id' LIMIT 1");
		if ($q && ($r = mysqli_fetch_assoc($q))) {
			return trim($r['flight_name'] . ' - ' . ($r['flight_number'] ?? ''), ' -');
		}
	}
	if ($source_type === 'vehicle' && $source_id > 0) {
		$q = mysqli_query($conn, "SELECT vehicle_number FROM vehicle WHERE vehicle_id='$source_id' LIMIT 1");
		if ($q && ($r = mysqli_fetch_assoc($q))) {
			return $r['vehicle_number'] ?? '';
		}
	}
	if ($source_type === 'train_preset' && $source_id === 1) {
		return 'Rajdhani Express';
	}
	return $source_manual;
}

function trip_summary_invoice_packages($conn, $trans_table, $transaction_id)
{
	$inv_tbl = str_replace('transaction_', 'transaction_invoice_', preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table));
	$transaction_id = (int) $transaction_id;
	$packages = 0;
	$q = @mysqli_query($conn, "SELECT SUM(no_of_pkge) AS packages FROM `$inv_tbl`
		WHERE transaction_id='$transaction_id'
		AND (type_of_pkge IS NULL OR type_of_pkge='' OR type_of_pkge!='Select Package Type')");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$packages = (int) ($row['packages'] ?? 0);
	}
	return $packages;
}

function trip_summary_find_gcn($conn, $grn_no)
{
	$grn_no = strtoupper(trim((string) $grn_no));
	if ($grn_no === '') {
		return null;
	}
	$grn_esc = mysqli_real_escape_string($conn, $grn_no);

	$tables_q = mysqli_query($conn, 'SELECT table_name FROM transaction_tbls ORDER BY table_name DESC');
	if (!$tables_q) {
		return null;
	}

	while ($tbl = mysqli_fetch_assoc($tables_q)) {
		$trans_table = 'transaction_' . preg_replace('/[^a-zA-Z0-9_]/', '', $tbl['table_name']);
		if (!@mysqli_query($conn, "SELECT 1 FROM `$trans_table` LIMIT 1")) {
			continue;
		}
		$sql = "SELECT * FROM `$trans_table`
			WHERE grn_no='$grn_esc'
			AND (booking_status IS NULL OR booking_status='' OR booking_status!='1')
			ORDER BY transaction_id DESC LIMIT 1";
		$prev = mysqli_report(MYSQLI_REPORT_OFF);
		$q = mysqli_query($conn, $sql);
		mysqli_report($prev);
		if (!$q || !($row = mysqli_fetch_assoc($q))) {
			continue;
		}

		$packages = trip_summary_invoice_packages($conn, $trans_table, $row['transaction_id']);
		return array(
			'gcn_key' => $trans_table . '|' . $row['transaction_id'],
			'trans_table' => $trans_table,
			'transaction_id' => (int) $row['transaction_id'],
			'grn_no' => $row['grn_no'],
			'grn_date' => $row['grn_date'] ?? '',
			'consignor_id' => (int) ($row['consigner'] ?? 0),
			'consignee_id' => (int) ($row['consignee'] ?? 0),
			'consignor_name' => get_client_name($conn, $row['consigner']),
			'consignee_name' => get_client_name($conn, $row['consignee']),
			'no_of_packages' => $packages,
		);
	}
	return null;
}

function trip_summary_find_active_assignment($conn, $trans_table, $transaction_id, $exclude_trip_id = 0)
{
	$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', $trans_table);
	$transaction_id = (int) $transaction_id;
	$exclude_trip_id = (int) $exclude_trip_id;
	if ($trans_table === '' || $transaction_id <= 0) {
		return null;
	}
	$exclude_sql = $exclude_trip_id > 0 ? " AND ts.trip_summary_id!='$exclude_trip_id'" : '';
	$sql = "SELECT ts.trip_summary_id, ts.sheet_no, ts.status
		FROM trip_summary_gcn tg
		INNER JOIN trip_summary ts ON ts.trip_summary_id = tg.trip_summary_id
		WHERE tg.trans_table='" . mysqli_real_escape_string($conn, $trans_table) . "'
		AND tg.transaction_id='$transaction_id'
		AND ts.status NOT IN ('Cancelled')
		$exclude_sql
		LIMIT 1";
	$q = mysqli_query($conn, $sql);
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return $row;
	}
	return null;
}

function trip_summary_next_sheet_no($conn)
{
	$max_id = 0;
	$q = mysqli_query($conn, 'SELECT MAX(trip_summary_id) AS max_id FROM trip_summary');
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$max_id = (int) ($row['max_id'] ?? 0);
	}
	return 'TS' . sprintf('%05d', $max_id + 1);
}

function trip_summary_parse_lines($conn, $lines_in, $exclude_trip_id = 0)
{
	if (!is_array($lines_in)) {
		$decoded = json_decode((string) $lines_in, true);
		$lines_in = is_array($decoded) ? $decoded : array();
	}
	if (empty($lines_in)) {
		return array('ok' => false, 'message' => 'Add at least one GCN line.');
	}

	$parsed = array();
	$seen = array();
	$sort = 0;

	foreach ($lines_in as $line) {
		if (!is_array($line)) {
			continue;
		}
		$grn_no = strtoupper(trim((string) ($line['grn_no'] ?? '')));
		$trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($line['trans_table'] ?? ''));
		$transaction_id = (int) ($line['transaction_id'] ?? 0);
		$loaded_no = (int) ($line['loaded_no'] ?? 0);
		$remarks = trim((string) ($line['remarks'] ?? ''));

		if ($trans_table === '' || $transaction_id <= 0) {
			if ($grn_no !== '') {
				$gcn = trip_summary_find_gcn($conn, $grn_no);
				if ($gcn) {
					$trans_table = $gcn['trans_table'];
					$transaction_id = $gcn['transaction_id'];
				}
			}
		}

		if ($grn_no === '' || $trans_table === '' || $transaction_id <= 0) {
			return array('ok' => false, 'message' => 'Invalid or missing GCN on one of the rows.');
		}

		$key = $trans_table . '|' . $transaction_id;
		if (isset($seen[$key])) {
			return array('ok' => false, 'message' => $grn_no . ' is already added to this Trip Summary Sheet.');
		}
		$seen[$key] = true;

		$gcn = trip_summary_find_gcn($conn, $grn_no);
		if (!$gcn || $gcn['trans_table'] !== $trans_table || (int) $gcn['transaction_id'] !== $transaction_id) {
			return array('ok' => false, 'message' => 'GCN not found or cancelled: ' . $grn_no);
		}

		if ($loaded_no <= 0) {
			return array('ok' => false, 'message' => 'Enter Loaded No. for GCN ' . $grn_no . '.');
		}
		if ($loaded_no > (int) $gcn['no_of_packages']) {
			return array('ok' => false, 'message' => 'Loaded No. cannot exceed No. of Pkgs for GCN ' . $grn_no . '.');
		}

		$assigned = trip_summary_find_active_assignment($conn, $trans_table, $transaction_id, $exclude_trip_id);
		if ($assigned) {
			return array(
				'ok' => false,
				'message' => $grn_no . ' is already assigned to Trip Summary ' . $assigned['sheet_no'] . '.',
			);
		}

		$sort++;
		$parsed[] = array(
			'sort_no' => $sort,
			'trans_table' => $trans_table,
			'transaction_id' => $transaction_id,
			'grn_no' => $gcn['grn_no'],
			'grn_date' => $gcn['grn_date'],
			'consignor_id' => $gcn['consignor_id'],
			'consignee_id' => $gcn['consignee_id'],
			'consignor_name' => $gcn['consignor_name'],
			'consignee_name' => $gcn['consignee_name'],
			'no_of_packages' => (int) $gcn['no_of_packages'],
			'loaded_no' => $loaded_no,
			'remarks' => $remarks,
		);
	}

	if (empty($parsed)) {
		return array('ok' => false, 'message' => 'Add at least one GCN line.');
	}
	return array('ok' => true, 'lines' => $parsed);
}

function trip_summary_save($conn, $data, $user_id)
{
	trip_summary_ensure_schema($conn);
	$user_id = (int) $user_id;
	$now = date('d-m-Y H:i:s');

	$trip_summary_id = (int) ($data['trip_summary_id'] ?? 0);
	$sheet_date = trim((string) ($data['sheet_date'] ?? ''));
	$origin_id = (int) ($data['origin_id'] ?? 0);
	$destination_id = (int) ($data['destination_id'] ?? 0);
	$mode_id = (int) ($data['mode_id'] ?? 0);
	$source_value = trim((string) ($data['source_value'] ?? ''));
	$source_manual = trim((string) ($data['source_manual'] ?? ''));

	if ($sheet_date === '') {
		return array('ok' => false, 'message' => 'Please enter date.');
	}
	if ($origin_id <= 0) {
		return array('ok' => false, 'message' => 'Please select origin.');
	}
	if ($destination_id <= 0) {
		return array('ok' => false, 'message' => 'Please select destination.');
	}
	if ($mode_id <= 0) {
		return array('ok' => false, 'message' => 'Please select mode.');
	}

	$source = trip_summary_parse_source($source_value, $source_manual);
	if (!$source) {
		return array('ok' => false, 'message' => 'Please select or enter transport source.');
	}
	$source_label = trip_summary_resolve_source_label($conn, $source['source_type'], $source['source_id'], $source['source_manual'], $source['source_label'] ?? '');
	if ($source_label === '') {
		return array('ok' => false, 'message' => 'Please enter transport source details.');
	}
	$source['source_label'] = $source_label;

	$parsed = trip_summary_parse_lines($conn, $data['lines'] ?? array(), $trip_summary_id);
	if (empty($parsed['ok'])) {
		return $parsed;
	}
	$lines = $parsed['lines'];

	$total_gcn = count($lines);
	$total_packages = 0;
	$total_loaded = 0;
	foreach ($lines as $line) {
		$total_packages += (int) $line['no_of_packages'];
		$total_loaded += (int) $line['loaded_no'];
	}

	$sheet_date_esc = mysqli_real_escape_string($conn, $sheet_date);
	$source_type_esc = mysqli_real_escape_string($conn, $source['source_type']);
	$source_label_esc = mysqli_real_escape_string($conn, $source['source_label']);
	$source_manual_esc = mysqli_real_escape_string($conn, $source['source_manual']);
	$source_id = (int) $source['source_id'];

	if ($trip_summary_id > 0) {
		$exist_q = mysqli_query($conn, "SELECT sheet_no, status FROM trip_summary WHERE trip_summary_id='$trip_summary_id' LIMIT 1");
		if (!$exist_q || !($exist = mysqli_fetch_assoc($exist_q))) {
			return array('ok' => false, 'message' => 'Trip summary not found.');
		}
		if (($exist['status'] ?? '') === 'Cancelled') {
			return array('ok' => false, 'message' => 'Cancelled sheet cannot be edited.');
		}
		$sheet_no = $exist['sheet_no'];

		mysqli_query($conn, "UPDATE trip_summary SET
			sheet_date='$sheet_date_esc', origin_id='$origin_id', destination_id='$destination_id',
			mode_id='$mode_id', source_type='$source_type_esc', source_id='$source_id',
			source_label='$source_label_esc', source_manual='$source_manual_esc',
			status='Created', total_gcn='$total_gcn', total_packages='$total_packages', total_loaded='$total_loaded',
			updated_at='$now', updated_by='$user_id'
			WHERE trip_summary_id='$trip_summary_id'");
		mysqli_query($conn, "DELETE FROM trip_summary_gcn WHERE trip_summary_id='$trip_summary_id'");
	} else {
		$sheet_no = trip_summary_next_sheet_no($conn);
		$sheet_no_esc = mysqli_real_escape_string($conn, $sheet_no);
		mysqli_query($conn, "INSERT INTO trip_summary
			(sheet_no, sheet_date, origin_id, destination_id, mode_id, source_type, source_id, source_label, source_manual,
			status, total_gcn, total_packages, total_loaded, created_at, created_by, updated_at, updated_by)
			VALUES
			('$sheet_no_esc', '$sheet_date_esc', '$origin_id', '$destination_id', '$mode_id', '$source_type_esc', '$source_id',
			'$source_label_esc', '$source_manual_esc', 'Created', '$total_gcn', '$total_packages', '$total_loaded',
			'$now', '$user_id', '$now', '$user_id')");
		$trip_summary_id = (int) mysqli_insert_id($conn);
		if ($trip_summary_id <= 0) {
			return array('ok' => false, 'message' => 'Could not save trip summary.');
		}
	}

	foreach ($lines as $line) {
		$sort_no = (int) $line['sort_no'];
		$trans_table_esc = mysqli_real_escape_string($conn, $line['trans_table']);
		$transaction_id = (int) $line['transaction_id'];
		$grn_no_esc = mysqli_real_escape_string($conn, $line['grn_no']);
		$grn_date_esc = mysqli_real_escape_string($conn, $line['grn_date']);
		$consignor_id = (int) $line['consignor_id'];
		$consignee_id = (int) $line['consignee_id'];
		$consignor_esc = mysqli_real_escape_string($conn, $line['consignor_name']);
		$consignee_esc = mysqli_real_escape_string($conn, $line['consignee_name']);
		$no_of_packages = (int) $line['no_of_packages'];
		$loaded_no = (int) $line['loaded_no'];
		$remarks_esc = mysqli_real_escape_string($conn, $line['remarks']);

		mysqli_query($conn, "INSERT INTO trip_summary_gcn
			(trip_summary_id, sort_no, trans_table, transaction_id, grn_no, grn_date,
			consignor_id, consignee_id, consignor_name, consignee_name, no_of_packages, loaded_no, remarks, created_at, updated_at)
			VALUES
			('$trip_summary_id', '$sort_no', '$trans_table_esc', '$transaction_id', '$grn_no_esc', '$grn_date_esc',
			'$consignor_id', '$consignee_id', '$consignor_esc', '$consignee_esc', '$no_of_packages', '$loaded_no', '$remarks_esc', '$now', '$now')");
	}

	return array(
		'ok' => true,
		'message' => 'Trip Summary Sheet created successfully.',
		'trip_summary_id' => $trip_summary_id,
		'sheet_no' => $sheet_no,
	);
}

function trip_summary_load_lines($conn, $trip_summary_id)
{
	$lines = array();
	$id = (int) $trip_summary_id;
	$q = mysqli_query($conn, "SELECT * FROM trip_summary_gcn WHERE trip_summary_id='$id' ORDER BY sort_no ASC, trip_gcn_id ASC");
	if (!$q) {
		return $lines;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$lines[] = array(
			'trip_gcn_id' => (int) $row['trip_gcn_id'],
			'sort_no' => (int) $row['sort_no'],
			'gcn_key' => $row['trans_table'] . '|' . $row['transaction_id'],
			'trans_table' => $row['trans_table'],
			'transaction_id' => (int) $row['transaction_id'],
			'grn_no' => $row['grn_no'],
			'grn_date' => $row['grn_date'],
			'consignor_id' => (int) $row['consignor_id'],
			'consignee_id' => (int) $row['consignee_id'],
			'consignor_name' => $row['consignor_name'],
			'consignee_name' => $row['consignee_name'],
			'no_of_packages' => (int) $row['no_of_packages'],
			'loaded_no' => (int) $row['loaded_no'],
			'remarks' => $row['remarks'],
			'row_saved' => true,
		);
	}
	return $lines;
}

function trip_summary_load($conn, $trip_summary_id)
{
	trip_summary_ensure_schema($conn);
	$id = (int) $trip_summary_id;
	if ($id <= 0) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT * FROM trip_summary WHERE trip_summary_id='$id' LIMIT 1");
	if (!$q || !($header = mysqli_fetch_assoc($q))) {
		return null;
	}
	$header['origin_name'] = get_city_name($conn, $header['origin_id']);
	$header['destination_name'] = get_city_name($conn, $header['destination_id']);
	$header['mode_label'] = get_mode($conn, $header['mode_id']);
	$header['source_display'] = $header['source_label'] ?: $header['source_manual'];
	if ($header['source_type'] && $header['source_id']) {
		$header['source_value'] = $header['source_type'] . ':' . $header['source_id'];
	} elseif ($header['source_type'] === 'ftl') {
		$header['source_value'] = 'ftl:' . $header['source_manual'];
	} elseif ($header['source_type'] === 'train_preset') {
		$header['source_value'] = 'train_preset:' . $header['source_id'];
	} else {
		$header['source_value'] = 'manual:';
	}
	return array('header' => $header, 'lines' => trip_summary_load_lines($conn, $id));
}

function trip_summary_fetch_list($conn)
{
	trip_summary_ensure_schema($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM trip_summary ORDER BY trip_summary_id DESC");
	if (!$q) {
		return $rows;
	}
	while ($h = mysqli_fetch_assoc($q)) {
		$h['origin_name'] = get_city_name($conn, $h['origin_id']);
		$h['destination_name'] = get_city_name($conn, $h['destination_id']);
		$h['mode_label'] = get_mode($conn, $h['mode_id']);
		$h['route_label'] = trim($h['origin_name'] . ' → ' . $h['destination_name'], ' →');
		$h['source_display'] = $h['source_label'] ?: $h['source_manual'];
		$rows[] = $h;
	}
	return $rows;
}

function trip_summary_cancel($conn, $trip_summary_id, $user_id)
{
	$id = (int) $trip_summary_id;
	$user_id = (int) $user_id;
	if ($id <= 0) {
		return array('ok' => false, 'message' => 'Invalid trip summary.');
	}
	$now = date('d-m-Y H:i:s');
	mysqli_query($conn, "UPDATE trip_summary SET status='Cancelled', updated_at='$now', updated_by='$user_id' WHERE trip_summary_id='$id'");
	return array('ok' => true, 'message' => 'Trip summary cancelled.');
}

function trip_summary_lookup_gcn($conn, $grn_no, $exclude_trip_id = 0, $exclude_keys = array())
{
	$gcn = trip_summary_find_gcn($conn, $grn_no);
	if (!$gcn) {
		return array('ok' => false, 'message' => 'GCN not found or booking cancelled.');
	}
	$key = $gcn['gcn_key'];
	if (is_array($exclude_keys) && in_array($key, $exclude_keys, true)) {
		return array('ok' => false, 'message' => $gcn['grn_no'] . ' is already added to this Trip Summary Sheet.');
	}
	$assigned = trip_summary_find_active_assignment($conn, $gcn['trans_table'], $gcn['transaction_id'], $exclude_trip_id);
	if ($assigned) {
		return array(
			'ok' => false,
			'message' => $gcn['grn_no'] . ' is already assigned to Trip Summary ' . $assigned['sheet_no'] . '.',
			'assigned_sheet' => $assigned['sheet_no'],
		);
	}
	return array('ok' => true, 'gcn' => $gcn);
}
