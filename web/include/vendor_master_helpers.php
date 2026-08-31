<?php

function ew_vendor_type_options()
{
	return array(
		'TRANSPORTER' => 'Transporter',
		'VEHICLE_OWNER' => 'Vehicle Owner',
		'FLEET_OPERATOR' => 'Fleet Operator',
		'GTA' => 'GTA',
		'FLIGHT_CARGO' => 'Flight / Cargo Operator',
		'RAIL_CARGO' => 'Rail / Cargo Operator',
		'WAREHOUSE' => 'Warehouse',
		'LOADING_UNLOADING' => 'Loading / Unloading Contractor',
		'OTHER' => 'Other',
	);
}

function ew_vendor_type_label($code)
{
	$labels = ew_vendor_type_options();
	$key = strtoupper(trim((string) $code));
	return $labels[$key] ?? $code;
}

function ew_vendor_mode_options()
{
	return array(
		'ROAD' => 'Road',
		'AIR' => 'Air',
		'RAIL' => 'Rail',
		'MULTIMODAL' => 'Multimodal',
	);
}

function ew_vendor_service_options()
{
	return array(
		'FTL' => 'FTL',
		'PTL' => 'PTL',
		'PARCEL' => 'Parcel',
		'EXPRESS' => 'Express',
		'OTHER' => 'Other',
	);
}

function ew_vendor_next_code($conn)
{
	$row = mysqli_fetch_array(mysqli_query($conn, 'SELECT MAX(vendor_code_id) AS code_id FROM vendor_master'));
	$id = (int) ($row['code_id'] ?? 0) + 1;
	return array(
		'vendor_code_id' => $id,
		'vendor_code' => 'VEN' . sprintf('%05d', $id),
	);
}

function ew_vendor_table_ready($conn)
{
	$check = mysqli_query($conn, "SHOW TABLES LIKE 'vendor_master'");
	return ($check && mysqli_num_rows($check) > 0);
}

function ew_vendor_ensure_table($conn)
{
	if (ew_vendor_table_ready($conn)) {
		return true;
	}
	$sql_file = __DIR__ . '/../setup/vendor_master.sql';
	if (!is_readable($sql_file)) {
		return false;
	}
	$sql = trim(file_get_contents($sql_file));
	return (bool) mysqli_query($conn, $sql);
}

function ew_vendor_is_linked($conn, $vendor_id)
{
	$vendor_id = (int) $vendor_id;
	if ($vendor_id <= 0) {
		return false;
	}
	$checks = array(
		"SELECT transaction_id FROM transactions WHERE vendor_id='$vendor_id' LIMIT 1",
		"SELECT trip_id FROM trip WHERE vendor_id='$vendor_id' LIMIT 1",
	);
	foreach ($checks as $query) {
		$res = @mysqli_query($conn, $query);
		if ($res && mysqli_num_rows($res) > 0) {
			return true;
		}
	}
	return false;
}

function ew_vendor_normalize_gstin($gstin)
{
	$gstin = strtoupper(trim((string) $gstin));
	return preg_replace('/[^A-Z0-9]/', '', $gstin);
}

function ew_vendor_validate_gstin($gstin)
{
	$gstin = ew_vendor_normalize_gstin($gstin);
	if ($gstin === '') {
		return true;
	}
	if (strlen($gstin) !== 15) {
		return false;
	}
	// 15-char GSTIN: 2 digit state + 10 char PAN + entity + Z + checksum
	return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][0-9A-Z]Z[0-9A-Z]$/', $gstin);
}

function ew_vendor_validate_pan($pan)
{
	$pan = strtoupper(trim((string) $pan));
	if ($pan === '') {
		return false;
	}
	return (bool) preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan);
}

function ew_vendor_validate_ifsc($ifsc)
{
	$ifsc = strtoupper(trim((string) $ifsc));
	if ($ifsc === '') {
		return true;
	}
	return (bool) preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc);
}
