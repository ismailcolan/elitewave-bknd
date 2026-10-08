<?php
require_once("tracking_templates.php");
require_once(__DIR__ . '/encryption.php');

function get_trans_status($val)
{
	if ($val == 1)
		return 'Consignment Booked';
	if ($val == 2)
		return 'Consignment Picked Up';
	if ($val == 3)
		return 'In Transit - 1 (Consignment at Origin State)';
	if ($val == 4)
		return 'In Transit - 2 (Towards Destination State)';
	if ($val == 5)
		return 'In Transit - 3 (Towards Destination)';
	if ($val == 6)
		return 'At Destination';
	if ($val == 7)
		return 'Out for Delivery';
	if ($val == 8)
		return 'Consignment Delivered Successfully';
}

/**
 * Delivery context for list/status badges (partial vs full delivered).
 */
function ew_transaction_badge_opts_for_row($conn, $row, $total_packages = 0)
{
	$opts = array(
		'delivery_type' => '',
		'delivered_packages' => 0,
		'total_packages' => (int) $total_packages,
	);
	$grn_no = isset($row['grn_no']) ? (string) $row['grn_no'] : '';
	if ($grn_no === '') {
		return $opts;
	}
	$delivery_q = mysqli_query(
		$conn,
		"SELECT delivery_type, delivered_packages FROM transaction_status_log WHERE grn_no='"
		. mysqli_real_escape_string($conn, $grn_no)
		. "' AND to_status='8' ORDER BY sheet_id DESC LIMIT 1"
	);
	if ($delivery_q && ($delivery_r = mysqli_fetch_assoc($delivery_q))) {
		$opts['delivery_type'] = !empty($delivery_r['delivery_type']) ? (string) $delivery_r['delivery_type'] : '';
		$opts['delivered_packages'] = !empty($delivery_r['delivered_packages']) ? (int) $delivery_r['delivered_packages'] : 0;
	}
	return $opts;
}

/** List of Consignments — same labels/colors as Transaction Status (Transit-1, Transit-2, …). */
function transaction_list_status_badge($booking, $status, $opts = array())
{
	return transaction_status_badge($booking, $status, $opts);
}

/**
 * Status badge for Transaction Status page — shows step-specific labels (Transit-1, Transit-2, etc.).
 */
function transaction_status_badge($booking, $status, $opts = array())
{
	if ((string) $booking === '1') {
		return '<span class="txn-status-badge txn-status-cancelled" title="Consignment Cancelled">Cancelled</span>';
	}

	$status = (int) $status;
	$delivery_type = isset($opts['delivery_type']) ? (string) $opts['delivery_type'] : '';
	$delivered_packages = isset($opts['delivered_packages']) ? (int) $opts['delivered_packages'] : 0;
	$total_packages = isset($opts['total_packages']) ? (int) $opts['total_packages'] : 0;

	if ($delivery_type === 'partial') {
		$label = 'Partial ' . $delivered_packages . '/' . $total_packages;
		$title = 'Partially Delivered (' . $delivered_packages . ' of ' . $total_packages . ' packages)';
		return '<span class="txn-status-badge txn-status-partial" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label) . '</span>';
	}

	if ($delivery_type === 'full') {
		return '<span class="txn-status-badge txn-status-delivered" title="Delivered Successfully">Delivered</span>';
	}

	$full_label = get_trans_status($status);
	if ($full_label === null || $full_label === '') {
		$full_label = 'Unknown';
	}

	$short_map = array(
		1 => 'Booked',
		2 => 'Picked Up',
		3 => 'Transit-1',
		4 => 'Transit-2',
		5 => 'Transit-3',
		6 => 'At Destination',
		7 => 'Out for Delivery',
		8 => 'Delivered',
	);
	$class_map = array(
		1 => 'txn-status-booked',
		2 => 'txn-status-picked',
		3 => 'txn-status-transit-1',
		4 => 'txn-status-transit-2',
		5 => 'txn-status-transit-3',
		6 => 'txn-status-destination',
		7 => 'txn-status-out',
		8 => 'txn-status-delivered',
	);

	$short_label = isset($short_map[$status]) ? $short_map[$status] : $full_label;
	$class = isset($class_map[$status]) ? $class_map[$status] : 'txn-status-default';

	return '<span class="txn-status-badge ' . $class . '" title="' . htmlspecialchars($full_label, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($short_label) . '</span>';
}

function transaction_list_client_cell($conn, $client_id, $branch_id = 0)
{
	$name = get_client_name($conn, $client_id);
	$html = '<span class="txn-party-name">' . htmlspecialchars($name) . '</span>';
	if (check_invoice_restricted($conn, $client_id) == 1) {
		$html .= " <i class='fa fa-ban txn-party-icon txn-icon-restricted' title='Restricted client'></i>";
	}
	if (checkPartyWiseFrequency($conn, $client_id) == 0) {
		$html .= " <i class='fa fa-clock-o txn-party-icon txn-icon-frequency' title='Invoice frequency client'></i>";
	}
	if (checkClientCharges($conn, $client_id) > 0) {
		$html .= " <i class='fa fa-inr txn-party-icon txn-icon-charges' title='Client charges apply'></i>";
	}
	return $html;
}

function get_cons_status_sms($val)
{
	if ($val == 1)
		return 'Consignment Booked';
	if ($val == 2)
		return 'picked up';
	if ($val == 3)
		return 'Transit-1, At Origin State';
	if ($val == 4)
		return 'Transit-2, Destination state';
	if ($val == 5)
		return 'Transit-3, Towards Destination';
	if ($val == 6)
		return 'at destination';
	if ($val == 7)
		return 'out for delivery';
	if ($val == 8)
		return 'Consignment Delivered';
}

function get_vehicle_name($conn, $id)
{
	$query = "select * from vehicle where vehicle_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['vehicle_reg_no'];
}

function get_state_name($conn, $id)
{
    $query = "SELECT * FROM state WHERE state_id='$id'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    return $row['state_name'];
}

function get_user_company_name($conn, $id)
{
	if ($id == '0')
		return 'Elite Wave 360';
	else {
		$query = "select * from client where client_id='$id'";
		$result = mysqli_query($conn, $query);
		$row = mysqli_fetch_array($result);
		return $row['client_company_name'];
	}
}

function get_user_branch_name($conn, $company_id, $branch_id)
{
	if ($company_id == '0') {
		$query = "select * from branch where branch_id='$branch_id'";
		$result = mysqli_query($conn, $query);
		$row = mysqli_fetch_array($result);
		return $row['branch_name'];
	} else {
		$query = "select * from client_branch where client_branch_id='$branch_id'";
		$result = mysqli_query($conn, $query);
		$row = mysqli_fetch_array($result);
		return $row['branch_name'];
	}
}

function get_cong_remarks($conn, $status, $grn_no)
{
	$query = "select * from transaction_status where sheet_id In (select sheet_id from transaction_status_log where grn_no='$grn_no' and to_status='$status')";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['remarks'];
}

function get_statename($conn, $id)
{
	$query = "select * from state where state_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['state_name'];
}

function get_city_name($conn, $id)
{
	$id = (int) $id;
	if ($id <= 0) {
		return '';
	}
	$query = "select * from city where city_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['city_name'] ?? '';
}

function ew_city_dropdown_options_html($conn)
{
	$html = '';
	$q = mysqli_query($conn, 'SELECT city_id, city_name FROM city WHERE status=0 ORDER BY city_name ASC');
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$id = (int) ($row['city_id'] ?? 0);
			if ($id <= 0) {
				continue;
			}
			$name = htmlspecialchars((string) ($row['city_name'] ?? ''), ENT_QUOTES, 'UTF-8');
			$html .= '<option value="' . $id . '">' . $name . '</option>';
		}
	}
	return $html;
}

function ew_transport_loading_point_field_html($conn, $point_no)
{
	$point_no = (int) $point_no;
	if ($point_no < 1 || $point_no > 4) {
		return '';
	}
	static $options = null;
	if ($options === null) {
		$options = ew_city_dropdown_options_html($conn);
	}
	$label = 'Loading Point ' . $point_no . ' :';

	return '<div class="form-group">'
		. '<label class="control-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>'
		. '<select name="loading_point' . $point_no . '" id="loading_point' . $point_no . '" class="form-control">'
		. '<option value="">Select city</option>' . $options
		. '</select></div>';
}

/** Train/flight loading point from POST (city id dropdown or legacy label fields). */
function ew_loading_point_from_post($conn, $post, $point_no)
{
	$point_no = (int) $point_no;
	$key = 'loading_point' . $point_no;
	$raw = trim((string) ($post[$key] ?? ''));
	if ($raw !== '' && ctype_digit($raw) && (int) $raw > 0) {
		return (int) $raw;
	}
	return ew_resolve_loading_point_city_id($conn, '', $post[$key . '_id'] ?? '');
}

/** Train/flight loading point: city_id from hidden field or by matching city name label. */
function ew_resolve_loading_point_city_id($conn, $city_id_raw, $city_label_raw)
{
	$city_id_raw = trim((string) $city_id_raw);
	if ($city_id_raw !== '' && ctype_digit($city_id_raw) && (int) $city_id_raw > 0) {
		return (int) $city_id_raw;
	}
	$label = trim((string) $city_label_raw);
	if ($label === '') {
		return null;
	}
	$esc = mysqli_real_escape_string($conn, $label);
	$q = mysqli_query($conn, "SELECT city_id FROM city WHERE status=0 AND city_name='$esc' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q)) && (int) ($row['city_id'] ?? 0) > 0) {
		return (int) $row['city_id'];
	}
	$q = mysqli_query($conn, "SELECT city_id FROM city WHERE status=0 AND LOWER(city_name)=LOWER('$esc') LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q)) && (int) ($row['city_id'] ?? 0) > 0) {
		return (int) $row['city_id'];
	}
	$q = mysqli_query(
		$conn,
		"SELECT city_id FROM city WHERE status=0 AND city_name LIKE '$esc%' ORDER BY CHAR_LENGTH(city_name) ASC, city_name ASC LIMIT 1"
	);
	if ($q && ($row = mysqli_fetch_assoc($q)) && (int) ($row['city_id'] ?? 0) > 0) {
		return (int) $row['city_id'];
	}
	return null;
}

function ew_sql_nullable_int($value)
{
	if ($value === null || $value === '') {
		return 'NULL';
	}
	return "'" . (int) $value . "'";
}

function ew_client_branch_ensure_schema($conn)
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	$columns = array(
		'pan_no' => "VARCHAR(20) NOT NULL DEFAULT ''",
		'gst_no' => "VARCHAR(20) NOT NULL DEFAULT ''",
	);
	foreach ($columns as $col => $def) {
		$q = mysqli_query($conn, "SHOW COLUMNS FROM client_branch LIKE '" . mysqli_real_escape_string($conn, $col) . "'");
		if ($q && mysqli_num_rows($q) === 0) {
			mysqli_query($conn, "ALTER TABLE client_branch ADD COLUMN $col $def");
		}
	}
}

/**
 * When a consignor/consignee client branch is selected on booking, overlay branch address fields.
 */
function ew_transaction_ensure_party_branch_columns($conn, $table_name)
{
	$table_name = preg_replace('/[^a-z0-9_]/i', '', (string) $table_name);
	if ($table_name === '') {
		return;
	}
	static $done = array();
	if (!empty($done[$table_name])) {
		return;
	}
	$done[$table_name] = true;
	$chk = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'bill_to_branch_id'");
	if ($chk && mysqli_num_rows($chk) === 0) {
		mysqli_query($conn, "ALTER TABLE `$table_name` ADD COLUMN `bill_to_branch_id` INT NOT NULL DEFAULT 0 AFTER `consignee_branch_id`");
	}
}

/**
 * Quarter tables cloned from `transaction` can miss columns/defaults added on live partitions.
 * Align so add_new_consignment INSERT succeeds (Oct–Dec / Q4 bookings).
 */
function ew_transaction_ensure_booking_schema($conn, $table_name)
{
	$table_name = preg_replace('/[^a-z0-9_]/i', '', (string) $table_name);
	if ($table_name === '' || strpos($table_name, 'transaction_') !== 0) {
		return;
	}
	static $done = array();
	if (!empty($done[$table_name])) {
		return;
	}
	$done[$table_name] = true;

	$chk = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'booking_time'");
	if ($chk && mysqli_num_rows($chk) === 0) {
		mysqli_query($conn, "ALTER TABLE `$table_name` ADD COLUMN `booking_time` VARCHAR(20) NULL DEFAULT NULL AFTER `grn_date`");
	}

	$alters = array(
		'active_status' => 'MODIFY `active_status` INT(11) NULL DEFAULT 0',
		'booking_status' => 'MODIFY `booking_status` VARCHAR(100) NULL DEFAULT NULL',
		'frq_sent_status' => 'MODIFY `frq_sent_status` VARCHAR(100) NULL DEFAULT NULL',
	);
	foreach ($alters as $col => $sqlPart) {
		$c = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE '$col'");
		if ($c && mysqli_num_rows($c) > 0) {
			mysqli_query($conn, "ALTER TABLE `$table_name` $sqlPart");
		}
	}
}

function ew_gcn_display_or_na($value)
{
	$v = trim((string) $value);
	if ($v === '' || strcasecmp($v, 'null') === 0) {
		return 'Not Available';
	}
	return $v;
}

function ew_gcn_normalize_party_label($label)
{
	$label = trim((string) $label);
	$label = preg_replace('/\s+/', ' ', $label);
	$label = strtolower($label);
	// Ignore spacing differences: "( Noida )" vs "(Noida)"
	return preg_replace('/\s+/', '', $label);
}

/** True when branch label repeats or overlaps the client display name. */
function ew_gcn_party_labels_redundant($client_label, $branch_label)
{
	$a = ew_gcn_normalize_party_label($client_label);
	$b = ew_gcn_normalize_party_label($branch_label);
	if ($a === '' || $b === '') {
		return false;
	}
	if ($a === $b) {
		return true;
	}
	if (strpos($a, $b) !== false || strpos($b, $a) !== false) {
		return true;
	}
	return false;
}

/**
 * GCN Bill To / Ship To block from consignee client + optional branch.
 *
 * @return array{name:string,addr_html:string,gst:string,phone:string}
 */
function ew_gcn_party_block($conn, $client_id, $branch_id, $branch_name_only = false)
{
	$client_id = (int) $client_id;
	$branch_id = (int) $branch_id;
	$branch_name_only = (bool) $branch_name_only;
	$det = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM client WHERE client_id='" . $client_id . "' LIMIT 1"));
	if (!$det) {
		return array(
			'name' => '',
			'addr_html' => '',
			'gst' => '',
			'phone' => '',
		);
	}
	$name = get_client_name($conn, $client_id);
	$gst = $det['gst_no'] ?? '';
	$phone = $det['contact_no'] ?? '';
	$addr_html = ew_format_party_address_invoice_html($det, $conn);

	if ($branch_id > 0) {
		$bq = mysqli_query(
			$conn,
			"SELECT cb.*, c.city_name, s.state_name
			 FROM client_branch cb
			 LEFT JOIN city c ON c.city_id = cb.city
			 LEFT JOIN state s ON s.state_id = cb.state
			 WHERE cb.client_branch_id='" . $branch_id . "' LIMIT 1"
		);
		$branch = $bq ? mysqli_fetch_assoc($bq) : null;
		if ($branch) {
			$branch_label = trim((string) ($branch['branch_name'] ?? ''));
			if ($branch_label !== '') {
				$redundant = ew_gcn_party_labels_redundant($name, $branch_label);
				if ($branch_name_only) {
					$name = $redundant ? $name : $branch_label;
				} elseif (!$redundant) {
					$name .= ' (' . $branch_label . ')';
				}
			} elseif ($branch_name_only) {
				$name = get_client_name($conn, $client_id);
			}
			$party = array(
				'address1' => $branch['address1'],
				'address2' => $branch['address2'],
				'city' => $branch['city'],
				'pincode' => $branch['pincode'],
			);
			$addr_html = ew_format_party_address_invoice_html($party, $conn);
			if (trim((string) ($branch['gst_no'] ?? '')) !== '') {
				$gst = $branch['gst_no'];
			}
			if (trim((string) ($branch['contact_no'] ?? '')) !== '') {
				$phone = $branch['contact_no'];
			}
		}
	}

	return array(
		'name' => $name,
		'addr_html' => $addr_html,
		'gst' => $gst,
		'phone' => $phone,
	);
}

function ew_booking_apply_client_branch($conn, $branch_id, &$address1, &$address2, &$city, &$state, &$pincode, &$phone, &$gst_no)
{
	$branch_id = (int) $branch_id;
	if ($branch_id <= 0) {
		return;
	}
	$q = mysqli_query(
		$conn,
		"SELECT address1, address2, city, state, pincode, contact_no, gst_no
		 FROM client_branch
		 WHERE client_branch_id='" . $branch_id . "' AND status='0'
		 LIMIT 1"
	);
	if (!$q || mysqli_num_rows($q) === 0) {
		return;
	}
	$row = mysqli_fetch_assoc($q);
	$address1 = $row['address1'] ?? $address1;
	$address2 = $row['address2'] ?? $address2;
	if (!empty($row['city'])) {
		$city = $row['city'];
	}
	if (!empty($row['state'])) {
		$state = $row['state'];
	}
	if (isset($row['pincode']) && $row['pincode'] !== '') {
		$pincode = $row['pincode'];
	}
	if (!empty($row['contact_no'])) {
		$phone = $row['contact_no'];
	}
	if (!empty($row['gst_no'])) {
		$gst_no = $row['gst_no'];
	}
}

/**
 * Strip pincode / trailing city / trailing state from a street address so Invoice
 * and GCN do not print them twice (they already print city + pincode separately).
 *
 * CHENNAI - 600102 → CHENNAI
 * PLOT NO A-81, SECTOR - 4, NOIDA, GAUTHAMBUDDHA NAGAR - 201301
 *   → PLOT NO A-81, SECTOR - 4, NOIDA, GAUTHAMBUDDHA NAGAR
 */
function ew_clean_party_address($address, $pincode = '', $city_name = '', $state_name = '')
{
	$addr = trim((string) $address);
	if ($addr === '' || strtoupper($addr) === 'NULL') {
		return '';
	}

	$pin = preg_replace('/\D+/', '', (string) $pincode);
	if (strlen($pin) === 6) {
		$addr = preg_replace('/\bPIN(CODE)?\s*[:.\-]?\s*' . preg_quote($pin, '/') . '\b/iu', '', $addr);
		$addr = preg_replace('/\s*[-–—]\s*' . preg_quote($pin, '/') . '\b/u', '', $addr);
		$addr = preg_replace('/[,;]\s*' . preg_quote($pin, '/') . '\b/u', ',', $addr);
		$addr = preg_replace('/\b' . preg_quote($pin, '/') . '\b/u', '', $addr);
	}

	$city = trim((string) $city_name);
	$state = trim((string) $state_name);

	if ($state !== '') {
		$state_re = preg_quote($state, '/');
		$addr = preg_replace('/[,\s\-]+' . $state_re . '\s*$/iu', '', $addr);
		$addr = preg_replace('/\(\s*' . $state_re . '\s*\)/iu', '', $addr);
	}

	if ($city !== '') {
		$city_re = preg_quote($city, '/');
		$stripped = preg_replace('/[,\s\-]+' . $city_re . '\s*$/iu', '', $addr);
		if (trim($stripped) !== '') {
			$addr = $stripped;
		}
	}

	$addr = preg_replace('/\s+/', ' ', $addr);
	$addr = preg_replace('/\s*,\s*,+/', ',', $addr);
	$addr = preg_replace('/[,\s\-]+$/u', '', $addr);
	$addr = trim($addr, " \t\n\r\0\x0B,");

	return $addr;
}

function ew_party_street_address($det, $conn)
{
	$city = isset($det['city']) ? get_city_name($conn, $det['city']) : '';
	$state = isset($det['state']) ? get_statename($conn, $det['state']) : '';
	$pin = $det['pincode'] ?? '';
	$a1 = ew_clean_party_address($det['address1'] ?? '', $pin, $city, $state);
	$a2 = ew_clean_party_address($det['address2'] ?? '', $pin, $city, $state);
	$parts = array_filter(array($a1, $a2), function ($v) {
		return $v !== '' && strtoupper($v) !== 'NULL';
	});
	return trim(implode(', ', $parts), ' ,');
}

function ew_party_token_in_address($address, $token)
{
	$token = trim((string) $token);
	if ($token === '' || $address === '') {
		return false;
	}
	return (bool) preg_match('/(^|[\s,])' . preg_quote($token, '/') . '($|[\s,.\-])/iu', $address);
}

function ew_format_party_address_gcn($det, $conn)
{
	$city = isset($det['city']) ? get_city_name($conn, $det['city']) : '';
	$pin = trim((string) ($det['pincode'] ?? ''));
	$street = ew_party_street_address($det, $conn);
	$parts = array();
	if ($street !== '') {
		$parts[] = $street;
	}
	if ($city !== '' && strtoupper($city) !== 'NULL' && !ew_party_token_in_address($street, $city)) {
		$parts[] = $city;
	}
	if ($pin !== '' && strtoupper($pin) !== 'NULL' && !ew_party_token_in_address($street, $pin)) {
		$parts[] = $pin;
	}
	return implode(', ', $parts);
}

function ew_format_party_address_invoice_html($det, $conn)
{
	$city = isset($det['city']) ? get_city_name($conn, $det['city']) : '';
	$pin = trim((string) ($det['pincode'] ?? ''));
	$street = ew_party_street_address($det, $conn);
	$lines = array();
	if ($street !== '' && strcasecmp($street, $city) !== 0) {
		$lines[] = $street;
	}
	$tail = trim($city . ($pin !== '' ? '-' . $pin : ''));
	if ($tail !== '') {
		$lines[] = $tail;
	}
	return implode('<br>', $lines);
}

function get_city_state_name($conn, $city_id)
{
    $query = mysqli_query($conn,"
        SELECT c.city_name, s.state_name
        FROM city c
        LEFT JOIN state s
            ON c.state = s.state_id
        WHERE c.city_id='$city_id'
    ");

    $row = mysqli_fetch_assoc($query);

    return $row['city_name'].' - '.$row['state_name'];
}

function get_hub_name($conn, $id)
{
	$query = "select * from hub where hub_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['name'];
}

function get_mode($conn, $id)
{
	$query = "select * from mode_of_transportation where mode_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['mode_type'];
}

/** @return array<int, string> */
function ew_mode_group_options()
{
	return array(
		'Premium Train Cargo',
		'Premium Air Cargo',
		'Ocean Cargo',
		'Warehousing',
		'Road Cargo',
	);
}

function get_locality_name($conn, $id)
{
	$query = "select * from tv_localities where locality_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function get_users($conn)
{
	$query = 'select * from users';
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function get_client_name($conn, $id)
{
	$query = "select * from client where client_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return ew_client_decrypt_name($row['client_company_name'] ?? '');
}

function booking_client_name_options($conn)
{
	$rows = array();
	$q = mysqli_query($conn, 'SELECT client_id, client_company_name, city FROM client WHERE status=0 ORDER BY client_company_name ASC');
	if (!$q) {
		return $rows;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$raw = (string) ($row['client_company_name'] ?? '');
		$name = trim(ew_client_decrypt_name($raw));
		if ($name === '' && $raw !== '' && strpos($raw, 'EW1:') !== 0) {
			$name = trim($raw);
		}
		if ($name === '') {
			continue;
		}
		if (function_exists('mb_check_encoding') && !mb_check_encoding($name, 'UTF-8')) {
			$name = utf8_encode($name);
		}
		$rows[] = array(
			'id' => (int) $row['client_id'],
			'name' => $name,
			'city' => (int) ($row['city'] ?? 0),
		);
	}
	return $rows;
}

function ew_mapped_party_ids($conn, $client_id)
{
	$client_id = (int) $client_id;
	$ids = array();
	if ($client_id <= 0) {
		return $ids;
	}
	$mapping_ids = array();
	$q = mysqli_query($conn, "SELECT mapping_id FROM customer_mapping WHERE client='$client_id' AND status='0'");
	while ($q && ($r = mysqli_fetch_assoc($q))) {
		$mid = (int) ($r['mapping_id'] ?? 0);
		if ($mid > 0) {
			$mapping_ids[] = $mid;
		}
	}
	if (!empty($mapping_ids)) {
		$in = implode(',', $mapping_ids);
		$q2 = mysqli_query($conn, "SELECT client_id FROM customer_mapping_lists WHERE mapping_id IN ($in)");
		while ($q2 && ($r = mysqli_fetch_assoc($q2))) {
			$id = (int) ($r['client_id'] ?? 0);
			if ($id > 0) {
				$ids[] = $id;
			}
		}
	}
	$q3 = mysqli_query($conn, "SELECT m.client FROM customer_mapping m INNER JOIN customer_mapping_lists l ON l.mapping_id=m.mapping_id WHERE l.client_id='$client_id' AND m.status='0'");
	while ($q3 && ($r = mysqli_fetch_assoc($q3))) {
		$id = (int) ($r['client'] ?? 0);
		if ($id > 0) {
			$ids[] = $id;
		}
	}
	return array_values(array_unique($ids));
}

function ew_customer_mapping_overview($conn)
{
	$rows = array();
	$q = mysqli_query($conn, "SELECT mapping_id, client FROM customer_mapping WHERE status='0' ORDER BY mapping_id DESC");
	while ($q && ($m = mysqli_fetch_assoc($q))) {
		$customer_id = (int) ($m['client'] ?? 0);
		if ($customer_id <= 0) {
			continue;
		}
		$consignees = array();
		$lq = mysqli_query($conn, "SELECT list_id, client_id FROM customer_mapping_lists WHERE mapping_id='" . (int) $m['mapping_id'] . "' ORDER BY list_id ASC");
		while ($lq && ($l = mysqli_fetch_assoc($lq))) {
			$cid = (int) ($l['client_id'] ?? 0);
			if ($cid <= 0) {
				continue;
			}
			$consignees[] = array(
				'list_id' => (int) $l['list_id'],
				'id' => $cid,
				'name' => get_client_name($conn, $cid),
			);
		}
		if (empty($consignees)) {
			continue;
		}
		$names = array();
		foreach ($consignees as $c) {
			if ($c['name'] !== '') {
				$names[] = $c['name'];
			}
		}
		$rows[] = array(
			'mapping_id' => (int) $m['mapping_id'],
			'customer_id' => $customer_id,
			'customer_name' => get_client_name($conn, $customer_id),
			'consignees' => $consignees,
			'consignee_names' => implode(', ', $names),
		);
	}
	return $rows;
}

function get_client_contact_name($conn, $id)
{
	$query = "select contact_person from client where client_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['contact_person'];
}

function get_client_email($conn, $id)
{
	$query = "select * from client where client_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['email'];
}

function get_client($conn, $id)
{
	$query = "select * from client where client_id='$id' order by client_company_name asc";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function consignment_mode($conn, $id)
{
	$query = "select * from consignment_mode where consignment_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['consignment_mode'];
}

function get_email($conn, $id)
{
	$query = "select * from tv_admins where company_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function get_multiple_email($conn, $id)
{
	$query = "select * from tv_admins where employee_id='" . $id . "'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function get_password($conn, $id)
{
	$query = "select * from tv_admins where company_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function get_user($conn, $id)
{
	$query = "select * from users where user_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['user_name'];
}

function get_package_name($conn, $id)
{
	$query = "select * from package where package_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['package_code'];
}

/** Active consignment (booking_status 1 = cancelled). */
function ew_sql_not_cancelled_booking($column = 'booking_status')
{
	return "($column IS NULL OR $column = '' OR $column = '0' OR $column != '1')";
}

/** Whether a stored type_of_pkge value matches a package master row (id or code label). */
function ew_package_type_matches_stored($stored, $package_id, $package_code)
{
	$stored = trim((string) $stored);
	if ($stored === '') {
		return false;
	}
	$package_id = (int) $package_id;
	if ($stored === (string) $package_id || (ctype_digit($stored) && (int) $stored === $package_id)) {
		return true;
	}
	return strcasecmp($stored, trim((string) $package_code)) === 0;
}

/** Package code segment used in QR PNG filenames (from package master). */
function ew_qr_package_code_for_file($conn, $package_id)
{
	$code = trim((string) get_package_name($conn, $package_id));
	if ($code === '') {
		$id = (int) $package_id;
		return $id > 0 ? ('PK' . $id) : '';
	}
	return preg_replace('/[\\\\\\/:*?"<>|]+/', '', $code);
}

/** First matching package QR PNG for a GCN (web/qrcode/). */
function ew_resolve_qr_png_for_grn($conn, $grn_no, $package_type_id, $web_root = null)
{
	if ($web_root === null) {
		$web_root = dirname(__DIR__);
	}
	$grn_key = strtoupper(trim((string) $grn_no));
	if ($grn_key === '') {
		return '';
	}
	$pack = ew_qr_package_code_for_file($conn, $package_type_id);
	$dir = rtrim($web_root, '/') . '/qrcode/';
	$patterns = array();
	if ($pack !== '') {
		$patterns[] = $dir . $grn_key . $pack . '-*.png';
	}
	$patterns[] = $dir . $grn_key . '*-001.png';
	$patterns[] = $dir . str_replace('/', DIRECTORY_SEPARATOR, $grn_key) . '*-001.png';
	foreach ($patterns as $pattern) {
		$files = glob($pattern);
		if (!empty($files)) {
			return $files[0];
		}
	}
	return '';
}

function get_company($conn, $id)
{
	$query = "select * from users where user_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['company_name'];
}

function get_trans_table_name($conn, $date)
{
	// echo $date;
	$dates = trim($date, '`');
	$dt = (explode('-', $dates));
	$y = $dt[2];
	$m = $dt[1];
	if ($m <= 3)
		$m1 = 1;
	else if (($m >= 4) && ($m <= 6))
		$m1 = 2;
	else if (($m >= 7) && ($m <= 9))
		$m1 = 3;
	else
		$m1 = 4;

	$trans_name = 'transaction_' . $m1 . '_' . $y;
	$trans_image_name = 'transaction_images_' . $m1 . '_' . $y;
	$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $y;

	$table_name = array($trans_name, $trans_image_name, $trans_invoice_name);
	$table_main = array('transaction', 'transaction_images', 'transaction_invoice');
	$trans_tbl = $m1 . '_' . $y;
	for ($i = 0; $i < count($table_name); $i++) {
		$val = mysqli_query($conn, 'SELECT * FROM ' . $table_name[$i]);
		$count = mysqli_num_rows($val);
		if ($count == 0) {
			$db_creation = mysqli_query($conn, 'create table ' . $table_name[$i] . ' like ' . $table_main[$i]);
			//	echo $i;
			if ($i == 0) {
				ew_transaction_ensure_booking_schema($conn, $table_name[$i]);
				$val1 = mysqli_query($conn, 'SELECT * FROM transaction_tbls where table_name="' . $trans_tbl . '"');
				$count1 = mysqli_num_rows($val1);
				if ($count1 == 0)
					$db_name_store = mysqli_query($conn, "insert into transaction_tbls(table_name,created_at) values ('$trans_tbl','$dates')");
			}
		} elseif ($i == 0) {
			ew_transaction_ensure_booking_schema($conn, $table_name[$i]);
		}
	}
	return $table_name;
}

function invoice_table_function($conn, $date)
{
	$date_ex = explode('-', $date);

	$year = $date_ex[2];

	// $current_year = $year;
	// //print_r($year);

	// $previous_year =  $year - 1 ;

	// $p_y = substr($previous_year,2);
	// $c_y = substr($current_year,2);

	// $year_insert = $p_y."-".$c_y;

	$trans_invoice_tbl = 'trans_invoice_tbl' . $year;

	$table_main = 'invoice_tbl';

	$val = 'select * from ' . $trans_invoice_tbl;
	$res = mysqli_query($conn, $val);
	$count = mysqli_num_rows($res);

	if ($count == 0) {
		// echo "no table found";
		$db_creation = mysqli_query($conn, 'create table ' . $trans_invoice_tbl . ' like ' . $table_main);
	}

	return $trans_invoice_tbl;
}

function get_client_info($conn, $id)
{
	$sql = mysqli_query($conn, "select * from client where client_id = '$id'");
	$res = mysqli_fetch_assoc($sql);
	return $res;
}

function check_invoice_restricted($conn, $id)
{
	$sql = mysqli_query($conn, "select *from client where client_id = '$id' and invoice_status = '1'");
	$count = mysqli_num_rows($sql);
	return $count;
}

function SetOutStandingInfo($conn, $client_id, $amount)
{
	$client_outstanding_query = "SELECT * FROM `client_outstanding` where client_id = '$client_id' ";
	$client_outstanding_query_result = mysqli_query($conn, $client_outstanding_query);
	$outstanding_count = mysqli_num_rows($client_outstanding_query_result);
	// print_r($count);
	if ($outstanding_count > 0) {
		$result_datas = mysqli_fetch_assoc($client_outstanding_query_result);
		$c_id = $result_datas['client_id'];
		$total_amtt = $result_datas['total'];
		$amount_paid = $result_datas['amount_paid'];
		$balance = $result_datas['balance'];

		$upadate_total = (float) $amount + (float) $total_amtt;  // Add old amount with new
		$update_balance = (float) $upadate_total - (float) $amount_paid;  // Update Balance Amount

		$update_outstanding = mysqli_query($conn, "UPDATE `client_outstanding` SET `total`='$upadate_total',`amount_paid`='$amount_paid',`balance`='$update_balance' WHERE client_id = '$client_id'");
	} else {
		$insert_outstanding = mysqli_query($conn, "INSERT INTO `client_outstanding`(`client_id`, `total`, `amount_paid`, `balance`) VALUES ('$client_id','$amount','0','$amount')");
	}
}

function checkPartyWiseFrequency($conn, $id)
{
	$query = "select *from client where client_id = '$id' and invoice_frequency = '0' ";
	$sql = mysqli_query($conn, $query);
	$count = mysqli_num_rows($sql);
	return $count;

	// Count > 0 // Frequncy not Set
	// Count == 0 // Frequncy is Set
}

function checkClientCharges($conn, $id)
{
	$query = "SELECT * FROM `consignor_payment` WHERE consigner_id = '$id'";
	$sql = mysqli_query($conn, $query);
	$count = mysqli_num_rows($sql);
	return $count;
}

function monthToWeeks($y, $m)
{
	$weeks = [];
	$month = $m;
	$first_date = date("{$y}-{$m}-01");

	do {
		$last_date = date('Y-m-d', strtotime($first_date . ' +6 days'));
		$month = date('m', strtotime($last_date));

		if ($month != $m) {
			$last_date = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));

			if ($first_date > $last_date) {
				break;
			}
		}

		$weeks[] = [$first_date, $last_date];

		$first_date = date('Y-m-d', strtotime($last_date . ' +1 days'));
	} while ($month == intval($m));

	return $weeks;
}

function get_trans_table_name_only($conn, $date)
{
	// echo $date;
	$dates = trim($date, '`');
	$dt = (explode('-', $date));
	$y = $dt[2];
	$m = $dt[1];
	if ($m <= 3)
		$m1 = 1;
	else if (($m >= 4) && ($m <= 6))
		$m1 = 2;
	else if (($m >= 7) && ($m <= 9))
		$m1 = 3;
	else
		$m1 = 4;

	$trans_name = 'transaction_' . $m1 . '_' . $y;
	$trans_image_name = 'transaction_images_' . $m1 . '_' . $y;
	$trans_invoice_name = 'transaction_invoice_' . $m1 . '_' . $y;

	$table_name = array($trans_name, $trans_image_name, $trans_invoice_name);
	$table_main = array('transaction', 'transaction_images', 'transaction_invoice');
	$trans_tbl = $m1 . '_' . $y;
	for ($i = 0; $i < count($table_name); $i++) {
		$val = mysqli_query($conn, 'SELECT * FROM ' . $table_name[$i]);
		$count = mysqli_num_rows($val);
		if ($count == 0) {
			// $db_creation = mysqli_query($conn, "create table " . $table_name[$i] . " like " . $table_main[$i]);
			//	echo $i;
			if ($i == 0) {
				$val1 = mysqli_query($conn, 'SELECT * FROM transaction_tbls where table_name="' . $trans_tbl . '"');
				$count1 = mysqli_num_rows($val1);
				if ($count1 == 0)
					$db_name_store = mysqli_query($conn, "insert into transaction_tbls(table_name,created_at) values ('$trans_tbl','$dates')");
			}
		}
	}
	return $table_name;
}

function UpdateOutStandingInfo($conn, $client_id, $mode_of_consignment)
{
	$updated_at = date('Y-m-d h:i:s');
	$query2 = 'SELECT * FROM transaction_tbls';
	$total = [];
	$totals = '';
	$paid = '';
	$balance = '';
	// $mode_of_consignment = '1';
	$result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
	while ($row2 = mysqli_fetch_assoc($result2)) {
		if ($mode_of_consignment == '1') {
			$qe = 'select * from transaction_' . $row2['table_name'] . " where consignee = '$client_id' ";
			$select_table = mysqli_query($conn, $qe);
		} else {
			$qe = 'select * from transaction_' . $row2['table_name'] . " where consigner = '$client_id' ";
			//  echo "<pre>";
			//  print_r($qe);
			//  echo "</pre>";
			$select_table = mysqli_query($conn, $qe);
		}
		while ($row4 = mysqli_fetch_assoc($select_table)) {
			$total[] = $row4['total'];

			$totals += $row4['total'];
			$paid += $row4['paid_amount'];
			$balance += $row4['balance'];
		}
	}
	$q = "select * from `client_outstanding` where client_id = '$client_id'";
	$select_outstanding_pay = mysqli_query($conn, $q);
	$row6 = mysqli_fetch_assoc($select_outstanding_pay);
	$total_outstaind = $row6['total'];  // 1244
	$paid_outstaind = $row6['amount_paid'];
	$balance_outstaind = $row6['balance'];

	// print_r($total);
	// echo "<br>";

	$diff_total = (float) $totals - (float) $total_outstaind;  // Getting Diffrence Total amt  //530
	$diff_paid = (float) $paid - (float) $paid_outstaind;  // Getting Diffrence Paid amt // 100
	$diff_bal = (float) $balance - (float) $balance_outstaind;  // Getting Diffrence Bal amt // 430

	// Update all the values

	$new_total = (float) $diff_total + (float) $total_outstaind;
	$new_paid_amt = (float) $diff_paid + (float) $paid_outstaind;
	$new_bal = (float) $diff_bal + (float) $balance_outstaind;

	$q_outs = "update `client_outstanding` SET `total`='$new_total',`amount_paid`='$new_paid_amt',`balance`='$new_bal',`updated_at`='$updated_at' WHERE client_id = '$client_id' ";
	// exit();
	$update_outstanding = mysqli_query($conn, $q_outs);

	if ($update_outstanding) {
		return 1;
	} else {
		return 0;
	}
}

function enc_name($name = '123')
{
	$enc = base64_encode(base64_encode(base64_encode(base64_encode('EliteWave360') . ':$' . base64_encode($name) . ':$' . base64_encode('EliteWave360'))));
	return $enc;
}

function dec_name($name = '')
{
	$enc2 = base64_decode(base64_decode(base64_decode($name)));
	$exp_arry = explode(':$', $enc2);
	$final_value = base64_decode($exp_arry[1]);
	return $final_value;
}

// Atomically gets the next GRN sequence number and increments the counter.
// Call immediately before INSERT; use rollback_last_grn_id() if that INSERT fails.
function get_next_grn_id($conn, $seq_key)
{
	$seq_key = mysqli_real_escape_string($conn, $seq_key);
	mysqli_query($conn, "INSERT INTO grn_sequence (seq_key, last_grn_id) VALUES ('$seq_key', 1)
                          ON DUPLICATE KEY UPDATE last_grn_id = last_grn_id + 1");
	$r = mysqli_query($conn, "SELECT last_grn_id FROM grn_sequence WHERE seq_key='$seq_key'");
	$row = mysqli_fetch_assoc($r);
	return (int) $row['last_grn_id'];
}

// Undo one get_next_grn_id when booking INSERT did not succeed.
function rollback_last_grn_id($conn, $seq_key)
{
	$seq_key = mysqli_real_escape_string($conn, $seq_key);
	mysqli_query($conn, "UPDATE grn_sequence SET last_grn_id = GREATEST(last_grn_id - 1, 0) WHERE seq_key='$seq_key'");
}

function ew_grn_seq_key_for_booking($comp_grn_mode, $client_id)
{
	return ($comp_grn_mode === 'company') ? 'COMPANY' : (string) $client_id;
}

function ew_grn_no_from_id($billing_code, $id, $comp_grn_mode)
{
	$billing_code = strtoupper(trim($billing_code));
	$id = (int) $id;
	if ($comp_grn_mode === 'company') {
		return $billing_code . sprintf('%04d', $id);
	}
	return $billing_code . sprintf('%05d', $id);
}

function ew_grn_numeric_id_from_no($grn_no)
{
	if (preg_match('/(\d+)\s*$/', trim($grn_no), $m)) {
		return (int) $m[1];
	}
	return 0;
}

// After a successful manual booking, keep sequence at least as high as the GCN used.
function sync_grn_sequence_min_id($conn, $seq_key, $used_id)
{
	$used_id = (int) $used_id;
	if ($used_id <= 0) {
		return;
	}
	$seq_key = mysqli_real_escape_string($conn, $seq_key);
	mysqli_query($conn, "INSERT INTO grn_sequence (seq_key, last_grn_id) VALUES ('$seq_key', $used_id)
		ON DUPLICATE KEY UPDATE last_grn_id = GREATEST(last_grn_id, $used_id)");
}

// Read-only preview of what the next GRN number WILL be, without incrementing.
// Use this for displaying the GRN number on the form before submit.
function peek_next_grn_id($conn, $seq_key)
{
	$seq_key = mysqli_real_escape_string($conn, $seq_key);
	$r = mysqli_query($conn, "SELECT last_grn_id FROM grn_sequence WHERE seq_key='$seq_key'");
	$row = mysqli_fetch_assoc($r);
	return ($row ? (int) $row['last_grn_id'] : 0) + 1;
}

function get_pod_status($conn, $grn_no)
{
	$grn_no = strtoupper(trim($grn_no));
	$q = mysqli_query($conn, "SELECT screens FROM pod_files WHERE screens LIKE '%" . mysqli_real_escape_string($conn, $grn_no) . "%'");
	while ($row = mysqli_fetch_assoc($q)) {
		foreach (explode('@@', $row['screens']) as $f) {
			if (strpos(strtoupper($f), $grn_no) === 0) {
				return true;  // POD already exists for this GRN
			}
		}
	}
	return false;
}


// status of consignment
function get_tracking_message($conn, $row)
{
    $status = (int)$row['active_status'];

    $grn = $row['grn_no'];

    $origin = get_city_name($conn, $row['origin']);
    $destination = get_city_name($conn, $row['destination']);

    $consignor = get_client_name($conn, $row['consigner']);
    $consignee = get_client_name($conn, $row['consignee']);

    $mode = get_mode($conn, $row['mode_of_transportation']);
    $mode_id = (int) ($row['mode_of_transportation'] ?? 0);

    if (!function_exists('city_transport_loading_hub_for_booking')) {
        require_once __DIR__ . '/city_transport_endpoint_helpers.php';
    }

    $loadingLoc = city_transport_endpoint_tracking_location($conn, (int) $row['origin'], $mode_id);
    $destLoc = city_transport_endpoint_tracking_location($conn, (int) $row['destination'], $mode_id);
    $loadingHub = $loadingLoc['label'] !== '' ? $loadingLoc['label'] : $origin;
    $destinationHub = $destLoc['label'] !== '' ? $destLoc['label'] : $destination;

$data = array(

    "grn" => $grn,

    "origin" => $origin,

    "destination" => $destination,

    "consignor" => $consignor,

    "consignee" => $consignee,

    "mode" => $mode,

    "loadingHub" => $loadingHub,

    "destinationHub" => $destinationHub,

    "loadingSuffix" => $loadingLoc['suffix_html'],

    "destinationSuffix" => $destLoc['suffix_html'],

);

return tracking_template($status, $data);
}

/**
 * Shared date input with calendar icon inside the field.
 *
 * Options: id, name, value, class, required, readonly, autocomplete,
 *          format (dd-mm-yyyy), start_date, end_date ('today' or date string), attrs
 */
function ew_date_input($opts = array())
{
	$id = isset($opts['id']) ? (string) $opts['id'] : '';
	$name = isset($opts['name']) ? (string) $opts['name'] : $id;
	$value = isset($opts['value']) ? (string) $opts['value'] : '';
	$class = 'form-control ew-date-field';
	if (!empty($opts['class'])) {
		$class .= ' ' . $opts['class'];
	}

	$required = !empty($opts['required']) ? ' required' : '';
	$readonly = !empty($opts['readonly']) ? ' readonly' : '';
	$placeholder = !empty($opts['placeholder'])
		? ' placeholder="' . htmlspecialchars((string) $opts['placeholder'], ENT_QUOTES, 'UTF-8') . '"'
		: '';
	$autocomplete = isset($opts['autocomplete']) ? (string) $opts['autocomplete'] : 'off';
	$format = isset($opts['format']) ? (string) $opts['format'] : 'dd-mm-yyyy';
	$extra = isset($opts['attrs']) ? ' ' . $opts['attrs'] : '';

	$idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
	$nameAttr = $name !== '' ? ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"' : '';

	$dataAttrs = ' data-ew-datepicker="1" data-date-format="' . htmlspecialchars($format, ENT_QUOTES, 'UTF-8') . '"';
	if (!empty($opts['end_date'])) {
		$dataAttrs .= ' data-end-date="' . htmlspecialchars($opts['end_date'], ENT_QUOTES, 'UTF-8') . '"';
	}
	if (!empty($opts['start_date'])) {
		$dataAttrs .= ' data-start-date="' . htmlspecialchars($opts['start_date'], ENT_QUOTES, 'UTF-8') . '"';
	}

	return '<div class="date-input-inside">'
		. '<input type="text"' . $idAttr . $nameAttr
		. ' class="' . htmlspecialchars(trim($class), ENT_QUOTES, 'UTF-8') . '"'
		. ' value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"'
		. $required . $readonly . $placeholder
		. ' autocomplete="' . htmlspecialchars($autocomplete, ENT_QUOTES, 'UTF-8') . '"'
		. $dataAttrs . $extra . '>'
		. '<i class="fa fa-calendar date-field-icon" aria-hidden="true"></i>'
		. '</div>';
}

/**
 * Month/year picker (mm-yyyy) using the shared ew-datepicker component.
 */
function ew_month_input($opts = array())
{
	$opts['format'] = 'mm-yyyy';
	if (!isset($opts['readonly'])) {
		$opts['readonly'] = true;
	}
	if (!isset($opts['value']) || $opts['value'] === '') {
		$opts['value'] = date('m-Y');
	}
	return ew_date_input($opts);
}

function ew_format_display_date($value, $default = '-')
{
	$value = trim((string) $value);
	if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
		return $default;
	}
	if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
		return $value;
	}
	$ts = strtotime($value);
	if ($ts === false || $ts <= 0) {
		return $default;
	}
	return date('d-m-Y', $ts);
}

function ew_normalize_input_date($value)
{
	$value = trim((string) $value);
	if ($value === '') {
		return '';
	}
	if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
		return $value;
	}
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
		$parts = explode('-', $value);
		return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
	}
	$ts = strtotime($value);
	if ($ts === false || $ts <= 0) {
		return '';
	}
	return date('d-m-Y', $ts);
}

/** Convert UI date (dd-mm-yyyy or dd/mm/yyyy) to MySQL DATE (yyyy-mm-dd). */
function ew_parse_input_date_to_mysql($value)
{
	$value = trim(str_replace('/', '-', (string) $value));
	if ($value === '' || $value === '0000-00-00') {
		return '';
	}
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
		return $value;
	}
	if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
		$dt = DateTime::createFromFormat('d-m-Y', $value);
		if ($dt instanceof DateTime) {
			return $dt->format('Y-m-d');
		}
	}
	$ts = strtotime($value);
	if ($ts !== false && $ts > 0) {
		return date('Y-m-d', $ts);
	}
	return '';
}

/**
 * Replace all package/invoice lines for a booking inside a DB transaction (rollback on failure).
 *
 * @param array $rows Each row: no_of_pkge, type_of_pkge, party_invoice_no, party_invoice_date (Y-m-d or ''),
 *                    said_contents, qty, gross_weight, charged_weight
 */
function ew_replace_transaction_invoice_rows($conn, $invoice_table, $transaction_id, $rows, $meta, &$error_out = '')
{
	$invoice_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $invoice_table);
	$transaction_id = (int) $transaction_id;
	if ($invoice_table === '' || $transaction_id <= 0) {
		$error_out = 'Invalid invoice table or transaction.';
		return false;
	}

	$created_at = mysqli_real_escape_string($conn, (string) ($meta['created_at'] ?? date('d-m-Y')));
	$created_by = (int) ($meta['created_by'] ?? 0);

	mysqli_begin_transaction($conn);
	if (!mysqli_query($conn, "DELETE FROM `$invoice_table` WHERE transaction_id='$transaction_id'")) {
		$error_out = mysqli_error($conn);
		mysqli_rollback($conn);
		return false;
	}

	foreach ($rows as $row) {
		$no_of_pkge = mysqli_real_escape_string($conn, (string) ($row['no_of_pkge'] ?? ''));
		$type_of_pkge = mysqli_real_escape_string($conn, (string) ($row['type_of_pkge'] ?? ''));
		$party_invoice_no = mysqli_real_escape_string($conn, (string) ($row['party_invoice_no'] ?? ''));
		$said_contents = mysqli_real_escape_string($conn, (string) ($row['said_contents'] ?? ''));
		$qty = mysqli_real_escape_string($conn, (string) ($row['qty'] ?? ''));
		$gross_weight = mysqli_real_escape_string($conn, (string) ($row['gross_weight'] ?? ''));
		$charged_weight = mysqli_real_escape_string($conn, (string) ($row['charged_weight'] ?? ''));

		$party_date_raw = trim((string) ($row['party_invoice_date'] ?? ''));
		if ($party_date_raw !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $party_date_raw)) {
			$party_date_raw = ew_parse_input_date_to_mysql($party_date_raw);
		}
		$party_inv_date_sql = ($party_date_raw !== '')
			? "'" . mysqli_real_escape_string($conn, $party_date_raw) . "'"
			: 'NULL';

		$sql = "INSERT INTO `$invoice_table`
			(transaction_id, no_of_pkge, type_of_pkge, party_invoice_no, party_invoice_date, said_contents, qty, gross_weight, charged_weight, created_at, created_by, status)
			VALUES ('$transaction_id', '$no_of_pkge', '$type_of_pkge', '$party_invoice_no', $party_inv_date_sql, '$said_contents', '$qty', '$gross_weight', '$charged_weight', '$created_at', '$created_by', '0')";
		if (!mysqli_query($conn, $sql)) {
			$error_out = mysqli_error($conn);
			mysqli_rollback($conn);
			return false;
		}
	}

	mysqli_commit($conn);
	return true;
}

/** Standard auto-code prefix (replaces legacy GE / GEC). */
function ew_entity_code_prefix()
{
	return 'EW';
}

function ew_format_entity_code($number, $pad = 3)
{
	return ew_entity_code_prefix() . sprintf('%0' . max(1, (int) $pad) . 'd', (int) $number);
}

/**
 * Next city code from city_code_id sequence (e.g. EW001).
 *
 * @return array{city_code_id:int,city_code:string}
 */
function ew_city_next_code($conn)
{
	$row = mysqli_fetch_array(mysqli_query($conn, 'SELECT MAX(city_code_id) AS code_id FROM city'));
	$id = (int) ($row['code_id'] ?? 0) + 1;

	return array(
		'city_code_id' => $id,
		'city_code' => ew_format_entity_code($id, 3),
	);
}

/**
 * Next branch code from max numeric suffix on GE/GEC/EW codes (e.g. EW1011).
 */
function ew_branch_next_code($conn)
{
	$max = 1000;
	$res = mysqli_query($conn, 'SELECT branch_code FROM branch');
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$code = strtoupper(trim((string) ($row['branch_code'] ?? '')));
			if (preg_match('/^(?:GE|GEC|EW)(\d+)$/', $code, $m)) {
				$n = (int) $m[1];
				if ($n > $max) {
					$max = $n;
				}
			}
		}
	}

	return ew_format_entity_code($max + 1, 4);
}

/** Normalize stored codes GE/GEC### → EW### (same numeric part). */
function ew_normalize_legacy_entity_code($code)
{
	$code = strtoupper(trim((string) $code));
	if ($code === '') {
		return '';
	}
	if (preg_match('/^(?:GE|GEC|EW)(\d+)$/', $code, $m)) {
		$num = (int) $m[1];
		$pad = strlen($m[1]);
		if ($pad < 3) {
			$pad = 3;
		}
		if ($num >= 1000) {
			$pad = max(4, $pad);
		}

		return ew_format_entity_code($num, $pad);
	}

	return $code;
}

function ew_eway_attachment_web_root()
{
	return dirname(__DIR__);
}

function ew_eway_attachment_path($filename)
{
	$filename = basename((string) $filename);
	if ($filename === '') {
		return '';
	}
	$root = ew_eway_attachment_web_root();
	if (is_file($root . '/eway/' . $filename)) {
		return 'eway/' . $filename;
	}
	if (is_file($root . '/invoice_image/' . $filename)) {
		return 'invoice_image/' . $filename;
	}
	if (preg_match('/^[a-f0-9]{10,}/i', $filename)) {
		return 'invoice_image/' . $filename;
	}
	return 'eway/' . $filename;
}

function ew_eway_attachment_is_image($filename)
{
	$ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
	return in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'), true);
}

function ew_eway_attachment_icon_class($filename)
{
	$ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
	if ($ext === 'pdf') {
		return 'fa-file-pdf-o';
	}
	if (in_array($ext, array('doc', 'docx'), true)) {
		return 'fa-file-word-o';
	}
	if (in_array($ext, array('xls', 'xlsx'), true)) {
		return 'fa-file-excel-o';
	}
	return 'fa-file-o';
}

?>
