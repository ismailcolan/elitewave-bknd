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

/** Display label: City, State, Country (for warehouse location dropdown / lists). */
function ew_city_location_display_label($conn, $city_id)
{
	$city_id = (int) $city_id;
	if ($city_id <= 0) {
		return '';
	}
	$q = mysqli_query($conn, "SELECT c.city_name, s.state_name, s.country_name
		FROM city c
		LEFT JOIN state s ON s.state_id = c.state
		WHERE c.city_id='$city_id'
		LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return get_city_name($conn, $city_id);
	}
	$parts = array();
	$city = trim((string) ($row['city_name'] ?? ''));
	$state = trim((string) ($row['state_name'] ?? ''));
	$country = trim((string) ($row['country_name'] ?? ''));
	if ($city !== '') {
		$parts[] = $city;
	}
	if ($state !== '') {
		$parts[] = $state;
	}
	if ($country !== '') {
		$parts[] = $country;
	} elseif ($parts !== array()) {
		$parts[] = 'India';
	}
	return implode(', ', $parts);
}

function ew_hub_location_select_options_html($conn)
{
	ew_area_ensure_country_name($conn, 'state');
	$html = '';
	$sql = "SELECT c.city_id, c.city_name, s.state_name, s.country_name
		FROM city c
		LEFT JOIN state s ON s.state_id = c.state
		WHERE c.status=0
		ORDER BY s.state_name ASC, c.city_name ASC";
	$q = mysqli_query($conn, $sql);
	if (!$q) {
		return $html;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$city_id = (int) ($row['city_id'] ?? 0);
		if ($city_id <= 0) {
			continue;
		}
		$parts = array();
		$city = trim((string) ($row['city_name'] ?? ''));
		$state = trim((string) ($row['state_name'] ?? ''));
		$country = trim((string) ($row['country_name'] ?? ''));
		if ($city !== '') {
			$parts[] = $city;
		}
		if ($state !== '') {
			$parts[] = $state;
		}
		if ($country !== '') {
			$parts[] = $country;
		} elseif ($parts !== array()) {
			$parts[] = 'India';
		}
		$label_esc = htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
		$html .= '<option value="' . $city_id . '">' . $label_esc . '</option>';
	}
	return $html;
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
/** Journey hours may be 4.20, not only whole hours. */
function ew_master_ensure_journey_hours_column($conn, $table_name)
{
	$table_name = preg_replace('/[^a-z_]/', '', (string) $table_name);
	if ($table_name !== 'train' && $table_name !== 'flight') {
		return;
	}
	$q = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'journey_hours'");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return;
	}
	$type = strtolower((string) ($row['Type'] ?? ''));
	$wide_enough = (strpos($type, 'text') !== false);
	if (preg_match('/varchar\((\d+)\)/', $type, $m)) {
		$wide_enough = ((int) $m[1]) >= 100;
	}
	if (!$wide_enough) {
		mysqli_query($conn, "ALTER TABLE `$table_name` MODIFY `journey_hours` VARCHAR(100) NOT NULL DEFAULT ''");
	}
}

function ew_master_normalize_journey_hours($value)
{
	$value = trim(preg_replace('/\s+/', ' ', (string) $value));
	if ($value === '') {
		return '';
	}
	if (function_exists('mb_substr')) {
		return mb_substr($value, 0, 100);
	}
	return substr($value, 0, 100);
}

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
	$chk_ship = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'ship_to_client_id'");
	if ($chk_ship && mysqli_num_rows($chk_ship) === 0) {
		mysqli_query($conn, "ALTER TABLE `$table_name` ADD COLUMN `ship_to_client_id` INT NOT NULL DEFAULT 0 AFTER `bill_to_branch_id`");
	}
	$chk_pickup = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE 'pickup_client_id'");
	if ($chk_pickup && mysqli_num_rows($chk_pickup) === 0) {
		mysqli_query($conn, "ALTER TABLE `$table_name` ADD COLUMN `pickup_client_id` INT NOT NULL DEFAULT 0 AFTER `consignor_branch_id`");
	}
}

/**
 * 0 means ship-to is the consignee (today's branch-only behaviour).
 */
function ew_booking_normalize_ship_to_client_id($ship_to_client_id, $consignee_id)
{
	$ship_to_client_id = (int) $ship_to_client_id;
	$consignee_id = (int) $consignee_id;
	if ($ship_to_client_id <= 0 || $ship_to_client_id === $consignee_id) {
		return 0;
	}
	return $ship_to_client_id;
}

/**
 * Copy a client master address into the booking consignee address fields.
 */
function ew_booking_fill_party_address_from_client($conn, $client_id, &$address1, &$address2, &$city, &$state, &$pincode, &$phone, &$gst_no)
{
	$client_id = (int) $client_id;
	if ($client_id <= 0) {
		return;
	}
	$q = mysqli_query(
		$conn,
		"SELECT address1, address2, city, state, pincode, contact_no, gst_no
		 FROM client WHERE client_id='" . $client_id . "' LIMIT 1"
	);
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return;
	}
	$address1 = $row['address1'];
	$address2 = $row['address2'];
	$city = $row['city'];
	$state = $row['state'];
	$pincode = $row['pincode'];
	$phone = $row['contact_no'];
	$gst_no = $row['gst_no'];
}

/**
 * When ship-to is another customer, destination and GST place of supply follow that customer (or its branch).
 */
function ew_booking_apply_ship_to_route($conn, $ship_to_client_id, $branch_id, &$post)
{
	$ship_to_client_id = (int) $ship_to_client_id;
	$branch_id = (int) $branch_id;
	if ($ship_to_client_id <= 0) {
		return;
	}
	$city = 0;
	$state = 0;
	if ($branch_id > 0) {
		$q = mysqli_query($conn, "SELECT city, state FROM client_branch WHERE client_branch_id='" . $branch_id . "' LIMIT 1");
		if ($q && ($row = mysqli_fetch_assoc($q))) {
			$city = (int) ($row['city'] ?? 0);
			$state = (int) ($row['state'] ?? 0);
		}
	}
	if ($city <= 0) {
		$q = mysqli_query($conn, "SELECT city, state FROM client WHERE client_id='" . $ship_to_client_id . "' LIMIT 1");
		if ($q && ($row = mysqli_fetch_assoc($q))) {
			$city = (int) ($row['city'] ?? 0);
			$state = (int) ($row['state'] ?? 0);
		}
	}
	if ($city > 0) {
		$post['destination'] = $city;
	}
	if ($state > 0) {
		$post['destination_state_id'] = $state;
	}
}

/**
 * 0 means pickup is the consignor.
 */
function ew_booking_normalize_pickup_client_id($pickup_client_id, $consignor_id)
{
	return ew_booking_normalize_ship_to_client_id($pickup_client_id, $consignor_id);
}

/**
 * When pickup is another customer, origin follows that customer (or its branch).
 */
function ew_booking_apply_pickup_route($conn, $pickup_client_id, $branch_id, &$post)
{
	$pickup_client_id = (int) $pickup_client_id;
	$branch_id = (int) $branch_id;
	if ($pickup_client_id <= 0) {
		return;
	}
	$city = 0;
	if ($branch_id > 0) {
		$q = mysqli_query($conn, "SELECT city FROM client_branch WHERE client_branch_id='" . $branch_id . "' LIMIT 1");
		if ($q && ($row = mysqli_fetch_assoc($q))) {
			$city = (int) ($row['city'] ?? 0);
		}
	}
	if ($city <= 0) {
		$q = mysqli_query($conn, "SELECT city FROM client WHERE client_id='" . $pickup_client_id . "' LIMIT 1");
		if ($q && ($row = mysqli_fetch_assoc($q))) {
			$city = (int) ($row['city'] ?? 0);
		}
	}
	if ($city > 0) {
		$post['origin'] = $city;
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

	// Consignee street lines are copied from client.address1/address2, which are often longer than VARCHAR(100).
	foreach (array('con_address1', 'con_address2') as $col) {
		$c = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name` LIKE '$col'");
		$row = $c ? mysqli_fetch_assoc($c) : null;
		if ($row && stripos((string) $row['Type'], 'text') === false) {
			mysqli_query($conn, "ALTER TABLE `$table_name` MODIFY `$col` TEXT NULL");
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

function ew_booking_is_cancelled($booking_status)
{
	return (string) $booking_status === '1';
}

/**
 * Party invoice no on another active (non-cancelled) booking for the same consignor.
 */
function ew_consignor_has_active_party_invoice($conn, $consigner_id, $party_invoice_no, $exclude_transaction_id = 0, $exclude_trans_table = '')
{
	$consigner_id = (int) $consigner_id;
	$party_invoice_no = trim((string) $party_invoice_no);
	if ($consigner_id <= 0 || $party_invoice_no === '') {
		return false;
	}
	$exclude_transaction_id = (int) $exclude_transaction_id;
	$exclude_trans_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $exclude_trans_table);
	$inv_esc = mysqli_real_escape_string($conn, $party_invoice_no);
	$active_sql = ew_sql_not_cancelled_booking('t.booking_status');

	$tables_q = mysqli_query($conn, 'SELECT table_name FROM transaction_tbls');
	if (!$tables_q) {
		return false;
	}
	while ($tbl = mysqli_fetch_assoc($tables_q)) {
		$trans_table = 'transaction_' . preg_replace('/[^a-zA-Z0-9_]/', '', $tbl['table_name']);
		if (!preg_match('/^transaction_[1-4]_\d{4}$/', $trans_table)) {
			continue;
		}
		$inv_table = str_replace('transaction_', 'transaction_invoice_', $trans_table);
		$exclude_sql = '';
		if ($exclude_transaction_id > 0 && $exclude_trans_table !== '' && $exclude_trans_table === $trans_table) {
			$exclude_sql = " AND t.transaction_id!='$exclude_transaction_id'";
		}
		$sql = "SELECT COUNT(*) AS c FROM `$trans_table` t
			WHERE t.consigner='$consigner_id' AND $active_sql
			AND EXISTS (
				SELECT 1 FROM `$inv_table` ti
				WHERE ti.transaction_id = t.transaction_id AND ti.party_invoice_no = '$inv_esc'
			 ) $exclude_sql LIMIT 1";
		$q = @mysqli_query($conn, $sql);
		if ($q && ($row = mysqli_fetch_assoc($q)) && (int) $row['c'] > 0) {
			return true;
		}
	}
	return false;
}

/** Validate party invoice lines for a consignor; ignores cancelled bookings. */
function ew_validate_consignor_party_invoices($conn, $consigner_id, $party_invoices, $exclude_transaction_id = 0, $exclude_trans_table = '')
{
	if (!is_array($party_invoices)) {
		return true;
	}
	$seen = array();
	foreach ($party_invoices as $inv) {
		$inv = trim((string) $inv);
		if ($inv === '') {
			continue;
		}
		$key = strtolower($inv);
		if (isset($seen[$key])) {
			return false;
		}
		$seen[$key] = true;
		if (ew_consignor_has_active_party_invoice($conn, $consigner_id, $inv, $exclude_transaction_id, $exclude_trans_table)) {
			return false;
		}
	}
	return true;
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


function ew_mode_transport_row($conn, $mode_id)
{
	$mode_id = (int) $mode_id;
	static $cache = array();
	if ($mode_id <= 0) {
		return null;
	}
	if (isset($cache[$mode_id])) {
		return $cache[$mode_id];
	}
	$has_group = false;
	$chk = @mysqli_query($conn, "SHOW COLUMNS FROM mode_of_transportation LIKE 'mode_group'");
	if ($chk && mysqli_num_rows($chk) > 0) {
		$has_group = true;
	}
	$sql = $has_group
		? "SELECT mode_type, mode_group FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1"
		: "SELECT mode_type FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1";
	$q = @mysqli_query($conn, $sql);
	$row = $q ? mysqli_fetch_assoc($q) : null;
	$cache[$mode_id] = $row;

	return $row;
}

function ew_mode_is_express_delivery($conn, $mode_id)
{
	$row = ew_mode_transport_row($conn, $mode_id);
	if (!$row) {
		return false;
	}

	return strcasecmp(trim((string) ($row['mode_type'] ?? '')), 'Express Delivery') === 0;
}

function ew_mode_is_road_freight($conn, $mode_id)
{
	$mode_id = (int) $mode_id;
	if ($mode_id <= 0) {
		return false;
	}
	if (ew_mode_is_express_delivery($conn, $mode_id)) {
		return false;
	}
	$has_group = false;
	$chk = @mysqli_query($conn, "SHOW COLUMNS FROM mode_of_transportation LIKE 'mode_group'");
	if ($chk && mysqli_num_rows($chk) > 0) {
		$has_group = true;
	}
	$sql = $has_group
		? "SELECT mode_type, mode_group FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1"
		: "SELECT mode_type FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1";
	$q = @mysqli_query($conn, $sql);
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return false;
	}
	$group = strtolower(trim((string) ($row['mode_group'] ?? '')));
	$type = strtolower(trim((string) ($row['mode_type'] ?? '')));
	if ($group === 'road cargo') {
		return true;
	}
	if (strpos($type, 'road') !== false || strpos($type, 'truck') !== false || strpos($type, 'part load') !== false
		|| strpos($type, 'ftl') !== false || strpos($type, 'surface') !== false || strpos($type, 'express') !== false) {
		return true;
	}
	return false;
}

function ew_mode_is_air_freight($conn, $mode_id)
{
	$mode_id = (int) $mode_id;
	if ($mode_id <= 0 || ew_mode_is_road_freight($conn, $mode_id)) {
		return false;
	}
	$has_group = false;
	$chk = @mysqli_query($conn, "SHOW COLUMNS FROM mode_of_transportation LIKE 'mode_group'");
	if ($chk && mysqli_num_rows($chk) > 0) {
		$has_group = true;
	}
	$sql = $has_group
		? "SELECT mode_type, mode_group FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1"
		: "SELECT mode_type FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1";
	$q = @mysqli_query($conn, $sql);
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return false;
	}
	$group = strtolower(trim((string) ($row['mode_group'] ?? '')));
	$type = strtolower(trim((string) ($row['mode_type'] ?? '')));
	if (strpos($group, 'air') !== false || strpos($group, 'flight') !== false) {
		return true;
	}
	if (strpos($type, 'air') !== false || strpos($type, 'flight') !== false) {
		return true;
	}
	return false;
}

/** @return string flight, train, express, or empty */
function ew_mode_offload_kind($conn, $mode_id)
{
	$mode_id = (int) $mode_id;
	if ($mode_id <= 0 || ew_mode_is_road_freight($conn, $mode_id)) {
		return '';
	}
	if (ew_mode_is_express_delivery($conn, $mode_id)) {
		return 'express';
	}
	if (ew_mode_is_air_freight($conn, $mode_id)) {
		return 'flight';
	}
	$has_group = false;
	$chk = @mysqli_query($conn, "SHOW COLUMNS FROM mode_of_transportation LIKE 'mode_group'");
	if ($chk && mysqli_num_rows($chk) > 0) {
		$has_group = true;
	}
	$sql = $has_group
		? "SELECT mode_type, mode_group FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1"
		: "SELECT mode_type FROM mode_of_transportation WHERE mode_id='$mode_id' LIMIT 1";
	$q = @mysqli_query($conn, $sql);
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return '';
	}
	$group = strtolower(trim((string) ($row['mode_group'] ?? '')));
	$type = strtolower(trim((string) ($row['mode_type'] ?? '')));
	if (strpos($group, 'train') !== false || strpos($type, 'train') !== false || strpos($type, 'rail') !== false) {
		return 'train';
	}
	return '';
}

/** Road, air, and train use transit-hour tracking messages (entered at Picked Up). */
function ew_mode_uses_transit_hours($conn, $mode_id)
{
	if (ew_mode_is_road_freight($conn, $mode_id)) {
		return true;
	}
	return ew_mode_offload_kind($conn, $mode_id) !== '';
}

function ew_transport_carrier_option_label($name, $number)
{
	$name = trim((string) $name);
	$number = trim((string) $number);
	if ($name === '') {
		return $number;
	}
	if ($number === '') {
		return $name;
	}

	return $name . ' (' . $number . ')';
}

/** Active trains and flights for status-2 (Picked Up) carrier selection. */
function ew_status_pickup_carrier_options($conn)
{
	$out = array('train' => array(), 'flight' => array());
	$tq = @mysqli_query($conn, "SELECT train_id, train_name, train_number, journey_hours FROM train WHERE status=0 ORDER BY train_name ASC");
	if ($tq) {
		while ($tr = mysqli_fetch_assoc($tq)) {
			$out['train'][] = array(
				'id' => (int) $tr['train_id'],
				'label' => ew_transport_carrier_option_label($tr['train_name'] ?? '', $tr['train_number'] ?? ''),
				'journey_hours' => isset($tr['journey_hours']) ? (string) $tr['journey_hours'] : '',
			);
		}
	}
	$fq = @mysqli_query($conn, "SELECT flight_id, flight_name, flight_number, journey_hours FROM flight WHERE status=0 ORDER BY flight_name ASC");
	if ($fq) {
		while ($fl = mysqli_fetch_assoc($fq)) {
			$out['flight'][] = array(
				'id' => (int) $fl['flight_id'],
				'label' => ew_transport_carrier_option_label($fl['flight_name'] ?? '', $fl['flight_number'] ?? ''),
				'journey_hours' => isset($fl['journey_hours']) ? (string) $fl['journey_hours'] : '',
			);
		}
	}

	return $out;
}

/** Decode train_type column for status pickup (train:ID, flight:ID, or legacy numeric). */
function ew_status_carrier_storage_decode($stored)
{
	$stored = trim((string) $stored);
	$out = array(
		'kind' => '',
		'id' => 0,
		'train_id' => 0,
		'flight_id' => 0,
	);
	if ($stored === '') {
		return $out;
	}
	if (preg_match('/^train:(\d+)$/i', $stored, $m)) {
		$out['kind'] = 'train';
		$out['id'] = (int) $m[1];
		$out['train_id'] = (int) $m[1];

		return $out;
	}
	if (preg_match('/^flight:(\d+)$/i', $stored, $m)) {
		$out['kind'] = 'flight';
		$out['id'] = (int) $m[1];
		$out['flight_id'] = (int) $m[1];

		return $out;
	}
	if (ctype_digit($stored)) {
		$out['id'] = (int) $stored;
	}

	return $out;
}

function ew_status_carrier_storage_encode($kind, $carrier_id)
{
	$kind = trim((string) $kind);
	$carrier_id = (int) $carrier_id;
	if ($carrier_id <= 0 || ($kind !== 'train' && $kind !== 'flight')) {
		return '';
	}

	return strtolower($kind) . ':' . $carrier_id;
}

function ew_status_pickup_journey_hours($conn, $kind, $carrier_id)
{
	$carrier_id = (int) $carrier_id;
	$kind = trim((string) $kind);
	if ($carrier_id <= 0 || ($kind !== 'train' && $kind !== 'flight')) {
		return 0.0;
	}
	if ($kind === 'train') {
		$q = @mysqli_query($conn, "SELECT journey_hours FROM train WHERE train_id='$carrier_id' AND status=0 LIMIT 1");
	} else {
		$q = @mysqli_query($conn, "SELECT journey_hours FROM flight WHERE flight_id='$carrier_id' AND status=0 LIMIT 1");
	}
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row || !isset($row['journey_hours']) || !is_numeric($row['journey_hours'])) {
		return 0.0;
	}

	return (float) $row['journey_hours'];
}

function ew_status_pickup_carrier_valid($conn, $kind, $carrier_id)
{
	$carrier_id = (int) $carrier_id;
	$kind = trim((string) $kind);
	if ($carrier_id <= 0 || ($kind !== 'train' && $kind !== 'flight')) {
		return false;
	}
	$table = ($kind === 'train') ? 'train' : 'flight';
	$id_col = ($kind === 'train') ? 'train_id' : 'flight_id';
	$q = @mysqli_query($conn, "SELECT `$id_col` FROM `$table` WHERE `$id_col`='$carrier_id' AND status=0 LIMIT 1");

	return ($q && mysqli_num_rows($q) > 0);
}

/** Resolve booked train/flight label for tracking (train_type column stores train_id or flight_id). */
function ew_tracking_carrier_label($conn, $mode_id, $carrier_stored)
{
	$kind = ew_mode_offload_kind($conn, $mode_id);
	$stored = trim((string) $carrier_stored);
	if ($kind === '') {
		return array('kind' => '', 'label' => '');
	}
	if ($kind === 'express') {
		$decoded = ew_status_carrier_storage_decode($stored);
		if ($decoded['kind'] === 'train' && $decoded['train_id'] > 0) {
			$q = @mysqli_query($conn, "SELECT train_name, train_number FROM train WHERE train_id='" . (int) $decoded['train_id'] . "' LIMIT 1");
			$row = $q ? mysqli_fetch_assoc($q) : null;

			return array(
				'kind' => 'train',
				'label' => $row ? ew_transport_carrier_option_label($row['train_name'] ?? '', $row['train_number'] ?? '') : '',
			);
		}
		if ($decoded['kind'] === 'flight' && $decoded['flight_id'] > 0) {
			$q = @mysqli_query($conn, "SELECT flight_name, flight_number FROM flight WHERE flight_id='" . (int) $decoded['flight_id'] . "' LIMIT 1");
			$row = $q ? mysqli_fetch_assoc($q) : null;

			return array(
				'kind' => 'flight',
				'label' => $row ? ew_transport_carrier_option_label($row['flight_name'] ?? '', $row['flight_number'] ?? '') : '',
			);
		}

		return array('kind' => 'express', 'label' => '');
	}
	$carrier_id = (int) $stored;
	if ($carrier_id <= 0) {
		return array('kind' => $kind, 'label' => '');
	}
	if ($kind === 'train') {
		$q = @mysqli_query($conn, "SELECT train_name, train_number FROM train WHERE train_id='$carrier_id' LIMIT 1");
		$row = $q ? mysqli_fetch_assoc($q) : null;

		return array(
			'kind' => 'train',
			'label' => $row ? ew_transport_carrier_option_label($row['train_name'] ?? '', $row['train_number'] ?? '') : '',
		);
	}
	$q = @mysqli_query($conn, "SELECT flight_name, flight_number FROM flight WHERE flight_id='$carrier_id' LIMIT 1");
	$row = $q ? mysqli_fetch_assoc($q) : null;

	return array(
		'kind' => 'flight',
		'label' => $row ? ew_transport_carrier_option_label($row['flight_name'] ?? '', $row['flight_number'] ?? '') : '',
	);
}

function ew_road_transit_hours_ensure($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS consignment_road_transit_hours (
		grn_no VARCHAR(40) NOT NULL,
		total_hours DECIMAL(10,2) NOT NULL DEFAULT 0,
		updated_at DATETIME NULL,
		PRIMARY KEY (grn_no)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ew_road_transit_hours_get($conn, $grn_no)
{
	ew_road_transit_hours_ensure($conn);
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	if ($grn_no === '') {
		return 0.0;
	}
	$q = mysqli_query($conn, "SELECT total_hours FROM consignment_road_transit_hours WHERE grn_no='$grn_no' LIMIT 1");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	return $row ? (float) $row['total_hours'] : 0.0;
}

function ew_road_transit_hours_save($conn, $grn_no, $hours)
{
	ew_road_transit_hours_ensure($conn);
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	$hours = round((float) $hours, 2);
	$now = date('Y-m-d H:i:s');
	mysqli_query($conn, "INSERT INTO consignment_road_transit_hours (grn_no, total_hours, updated_at)
		VALUES ('$grn_no', '$hours', '$now')
		ON DUPLICATE KEY UPDATE total_hours='$hours', updated_at='$now'");
}

function ew_road_breakdown_ensure($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS consignment_road_breakdown (
		id INT NOT NULL AUTO_INCREMENT,
		grn_no VARCHAR(40) NOT NULL,
		is_open TINYINT NOT NULL DEFAULT 1,
		reason VARCHAR(255) NOT NULL DEFAULT '',
		status_at INT NOT NULL DEFAULT 0,
		delay_hours DECIMAL(10,2) NOT NULL DEFAULT 0,
		created_at DATETIME NULL,
		resumed_at DATETIME NULL,
		PRIMARY KEY (id),
		KEY grn_no (grn_no)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ew_road_breakdown_is_open($conn, $grn_no)
{
	ew_road_breakdown_ensure($conn);
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	if ($grn_no === '') {
		return false;
	}
	$q = mysqli_query($conn, "SELECT id FROM consignment_road_breakdown WHERE grn_no='$grn_no' AND is_open=1 LIMIT 1");
	return $q && mysqli_num_rows($q) > 0;
}

/** Delay hours already resumed, for messages at this status and later. */
function ew_road_breakdown_for_message($conn, $grn_no, $status)
{
	ew_road_breakdown_ensure($conn);
	$out = array('open' => false, 'reason' => '', 'delay' => 0.0, 'resume_delay' => 0.0, 'events' => array());
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	$status = (int) $status;
	if ($grn_no === '') {
		return $out;
	}
	$q = mysqli_query($conn, "SELECT is_open, reason, status_at, delay_hours FROM consignment_road_breakdown WHERE grn_no='$grn_no' ORDER BY id ASC");
	if (!$q) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$at = (int) ($row['status_at'] ?? 0);
		$is_open = (int) ($row['is_open'] ?? 0) === 1;
		if ($at === $status) {
			$out['events'][] = array(
				'open' => $is_open,
				'reason' => (string) ($row['reason'] ?? ''),
			);
		}
		if ($is_open && $at === $status) {
			$out['open'] = true;
			$out['reason'] = (string) ($row['reason'] ?? '');
		}
		if (!$is_open && $at <= $status) {
			$hours = (float) ($row['delay_hours'] ?? 0);
			$out['delay'] += $hours;
			if ($at === $status) {
				$out['resume_delay'] += $hours;
			}
		}
	}
	return $out;
}

function ew_road_breakdown_open($conn, $grn_no, $status, $reason)
{
	ew_road_breakdown_ensure($conn);
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	$reason = mysqli_real_escape_string($conn, substr(trim((string) $reason), 0, 200));
	$status = (int) $status;
	$now = date('Y-m-d H:i:s');
	return mysqli_query($conn, "INSERT INTO consignment_road_breakdown (grn_no, is_open, reason, status_at, delay_hours, created_at)
		VALUES ('$grn_no', 1, '$reason', '$status', 0, '$now')");
}

function ew_road_breakdown_resume($conn, $grn_no, $delay_hours)
{
	ew_road_breakdown_ensure($conn);
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	$delay_hours = round((float) $delay_hours, 2);
	$now = date('Y-m-d H:i:s');
	$q = mysqli_query($conn, "SELECT id FROM consignment_road_breakdown WHERE grn_no='$grn_no' AND is_open=1 ORDER BY id DESC LIMIT 1");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return false;
	}
	$id = (int) $row['id'];
	return mysqli_query($conn, "UPDATE consignment_road_breakdown
		SET is_open=0, delay_hours='$delay_hours', resumed_at='$now'
		WHERE id='$id'");
}

function ew_air_offload_ensure($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS consignment_air_offload (
		id INT NOT NULL AUTO_INCREMENT,
		grn_no VARCHAR(40) NOT NULL,
		is_open TINYINT NOT NULL DEFAULT 1,
		reason VARCHAR(255) NOT NULL DEFAULT '',
		status_at INT NOT NULL DEFAULT 0,
		created_at DATETIME NULL,
		cleared_at DATETIME NULL,
		PRIMARY KEY (id),
		KEY grn_no (grn_no)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ew_air_offload_is_open($conn, $grn_no)
{
	ew_air_offload_ensure($conn);
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	if ($grn_no === '') {
		return false;
	}
	$q = mysqli_query($conn, "SELECT id FROM consignment_air_offload WHERE grn_no='$grn_no' AND is_open=1 LIMIT 1");
	return $q && mysqli_num_rows($q) > 0;
}

function ew_air_offload_for_message($conn, $grn_no, $status)
{
	ew_air_offload_ensure($conn);
	$out = array('open' => false, 'reason' => '', 'cleared' => false, 'events' => array());
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	$status = (int) $status;
	if ($grn_no === '') {
		return $out;
	}
	$q = mysqli_query($conn, "SELECT is_open, reason, status_at FROM consignment_air_offload WHERE grn_no='$grn_no' ORDER BY id ASC");
	if (!$q) {
		return $out;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$at = (int) ($row['status_at'] ?? 0);
		if ($at !== $status) {
			continue;
		}
		$is_open = (int) ($row['is_open'] ?? 0) === 1;
		$out['events'][] = array(
			'open' => $is_open,
			'reason' => (string) ($row['reason'] ?? ''),
		);
		if ($is_open) {
			$out['open'] = true;
			$out['reason'] = (string) ($row['reason'] ?? '');
			$out['cleared'] = false;
		} elseif (!$out['open']) {
			$out['cleared'] = true;
			if ($out['reason'] === '') {
				$out['reason'] = (string) ($row['reason'] ?? '');
			}
		}
	}
	return $out;
}

function ew_air_offload_open($conn, $grn_no, $status, $reason)
{
	ew_air_offload_ensure($conn);
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	$reason = mysqli_real_escape_string($conn, substr(trim((string) $reason), 0, 200));
	$status = (int) $status;
	$now = date('Y-m-d H:i:s');
	return mysqli_query($conn, "INSERT INTO consignment_air_offload (grn_no, is_open, reason, status_at, created_at)
		VALUES ('$grn_no', 1, '$reason', '$status', '$now')");
}

function ew_air_offload_clear($conn, $grn_no)
{
	ew_air_offload_ensure($conn);
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	$now = date('Y-m-d H:i:s');
	$q = mysqli_query($conn, "SELECT id FROM consignment_air_offload WHERE grn_no='$grn_no' AND is_open=1 ORDER BY id DESC LIMIT 1");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return false;
	}
	$id = (int) $row['id'];
	return mysqli_query($conn, "UPDATE consignment_air_offload SET is_open=0, cleared_at='$now' WHERE id='$id'");
}

function ew_road_refresh_status_remarks($conn, $grn_no, $status, $remarks)
{
	$grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
	$status = (int) $status;
	$remarks = mysqli_real_escape_string($conn, (string) $remarks);
	$q = mysqli_query($conn, "SELECT sheet_id FROM transaction_status_log WHERE grn_no='$grn_no' AND to_status='$status' ORDER BY sheet_id DESC LIMIT 1");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	if (!$row) {
		return;
	}
	$sheet_id = (int) $row['sheet_id'];
	mysqli_query($conn, "UPDATE transaction_status SET remarks='$remarks' WHERE sheet_id='$sheet_id'");
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

	$endpoint_field = (string) ($loadingLoc['field'] ?? $destLoc['field'] ?? '');
	$data['nameEndpoints'] = in_array($endpoint_field, array('airport', 'railway_station'), true);
	$data['originPoint'] = (!empty($loadingLoc['has_mapped_endpoint']) && in_array($loadingLoc['field'] ?? '', array('airport', 'railway_station'), true))
		? rtrim(trim((string) $loadingLoc['label']), " .")
		: $origin;
	$data['destinationPoint'] = (!empty($destLoc['has_mapped_endpoint']) && in_array($destLoc['field'] ?? '', array('airport', 'railway_station'), true))
		? rtrim(trim((string) $destLoc['label']), " .")
		: $destination;

	if (ew_mode_uses_transit_hours($conn, $mode_id)) {
		$override = isset($row['road_transit_hours_override']) ? trim((string) $row['road_transit_hours_override']) : '';
		$total_hours = ($override !== '' && is_numeric($override))
			? (float) $override
			: ew_road_transit_hours_get($conn, $grn);
		$data['totalHours'] = $total_hours;
		$carrier = ew_tracking_carrier_label($conn, $mode_id, $row['train_type'] ?? 0);
		$data['carrierKind'] = $carrier['kind'];
		$data['carrierLabel'] = $carrier['label'];
		$data['isExpressDelivery'] = ew_mode_is_express_delivery($conn, $mode_id);
		if (ew_mode_is_road_freight($conn, $mode_id)) {
			$breakdown = ew_road_breakdown_for_message($conn, $grn, $status);
			$data['breakdownOpen'] = $breakdown['open'];
			$data['breakdownReason'] = $breakdown['reason'];
			$data['breakdownDelay'] = $breakdown['delay'];
			$data['breakdownResumeDelay'] = $breakdown['resume_delay'];
			$data['breakdownEvents'] = $breakdown['events'];
		} else {
			$offload = ew_air_offload_for_message($conn, $grn, $status);
			$data['breakdownOpen'] = $offload['open'];
			$data['breakdownDelay'] = 0;
			$data['breakdownResumeDelay'] = 0;
		}
		return tracking_template_road($status, $data);
	}

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
 * Code from a master menu name.
 * Drops "Master" and a leading "EW360", then takes the first letter of each word.
 * A single acronym such as CFS is kept whole so the code still identifies that master.
 * Domestic City Master → EWDC. Number is always 3 digits: EWDC001.
 */
function ew_master_code_prefix($menu_label)
{
	$label = preg_replace('/\bEW360\b/i', ' ', (string) $menu_label);
	$label = preg_replace('/\bMaster\b/i', ' ', $label);
	$label = trim(preg_replace('/\s+/', ' ', $label));
	$skip = array('of' => true, 'for' => true, 'the' => true, 'a' => true, 'an' => true, 'and' => true);
	$words = array();
	foreach (preg_split('/\s+/', $label) as $word) {
		if ($word === '' || isset($skip[strtolower($word)])) {
			continue;
		}
		$words[] = $word;
	}
	$token = '';
	if (count($words) === 1 && preg_match('/^[A-Z]{2,5}$/', $words[0])) {
		$token = $words[0];
	} else {
		foreach ($words as $word) {
			$token .= strtoupper(substr($word, 0, 1));
		}
	}
	if ($token === '') {
		$token = 'X';
	}
	if (strpos($token, 'EW') !== 0) {
		$token = 'EW' . $token;
	}
	return $token;
}

function ew_master_format_code($prefix, $number)
{
	return $prefix . sprintf('%03d', (int) $number);
}

function ew_master_max_code_number($conn, $table, $column, $prefix, $where = '')
{
	$table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
	$column = preg_replace('/[^a-z0-9_]/i', '', (string) $column);
	$prefix = (string) $prefix;
	$sql = "SELECT `$column` AS code FROM `$table` WHERE `$column` LIKE '" . mysqli_real_escape_string($conn, $prefix) . "%'";
	if ($where !== '') {
		$sql .= ' AND (' . $where . ')';
	}
	$max = 0;
	$len = strlen($prefix);
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rest = substr((string) ($row['code'] ?? ''), $len);
			if (preg_match('/^(\d+)$/', $rest, $m)) {
				$max = max($max, (int) $m[1]);
			}
		}
	}
	return $max;
}

function ew_master_assign_codes($conn, $table, $id_col, $code_col, $prefix, $where = '1=1', $order = '', $seq_col = '')
{
	$table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
	$id_col = preg_replace('/[^a-z0-9_]/i', '', (string) $id_col);
	$code_col = preg_replace('/[^a-z0-9_]/i', '', (string) $code_col);
	$seq_col = preg_replace('/[^a-z0-9_]/i', '', (string) $seq_col);
	if ($order === '') {
		$order = "`$id_col` ASC";
	}
	$q = mysqli_query($conn, "SELECT `$id_col` AS id, `$code_col` AS code FROM `$table` WHERE $where ORDER BY $order");
	if (!$q) {
		return;
	}
	$rows = array();
	while ($row = mysqli_fetch_assoc($q)) {
		$rows[] = $row;
	}
	$n = 1;
	$needs = false;
	foreach ($rows as $row) {
		if ((string) $row['code'] !== ew_master_format_code($prefix, $n)) {
			$needs = true;
			break;
		}
		$n++;
	}
	if (!$needs) {
		return;
	}
	foreach ($rows as $row) {
		$id = (int) $row['id'];
		$tmp = mysqli_real_escape_string($conn, '__T' . $id);
		mysqli_query($conn, "UPDATE `$table` SET `$code_col`='$tmp' WHERE `$id_col`='$id' LIMIT 1");
	}
	$n = 1;
	foreach ($rows as $row) {
		$id = (int) $row['id'];
		$code = mysqli_real_escape_string($conn, ew_master_format_code($prefix, $n));
		$set = "`$code_col`='$code'";
		if ($seq_col !== '') {
			$set .= ", `$seq_col`='$n'";
		}
		mysqli_query($conn, "UPDATE `$table` SET $set WHERE `$id_col`='$id' LIMIT 1");
		$n++;
	}
}

function ew_master_table_exists($conn, $table)
{
	$table = mysqli_real_escape_string($conn, preg_replace('/[^a-z0-9_]/i', '', (string) $table));
	$q = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
	return $q && mysqli_num_rows($q) > 0;
}

function ew_state_ensure_code_column($conn)
{
	if (!ew_master_table_exists($conn, 'state')) {
		return;
	}
	if (function_exists('ew_geo_scope_ensure_column')) {
		ew_geo_scope_ensure_column($conn, 'state');
	}
	$q = mysqli_query($conn, "SHOW COLUMNS FROM state LIKE 'state_code'");
	if ($q && mysqli_num_rows($q) === 0) {
		mysqli_query($conn, "ALTER TABLE state ADD COLUMN state_code VARCHAR(20) NOT NULL DEFAULT '' AFTER state_id");
	}
}

function ew_state_next_code($conn)
{
	ew_master_sync_menu_codes($conn);
	$scope = function_exists('ew_geo_scope_from_request') ? ew_geo_scope_from_request() : 'domestic';
	$prefix = ew_master_code_prefix($scope === 'international' ? 'International State Master' : 'State Master');
	$where = "geo_scope='" . ($scope === 'international' ? 'international' : 'domestic') . "'";
	return ew_master_format_code($prefix, ew_master_max_code_number($conn, 'state', 'state_code', $prefix, $where) + 1);
}

function ew_country_ensure_table($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS country (
		country_id INT(11) NOT NULL AUTO_INCREMENT,
		country_code VARCHAR(20) NOT NULL DEFAULT '',
		country_name VARCHAR(150) NOT NULL,
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(30) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(30) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (country_id),
		UNIQUE KEY uk_country_code (country_code)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ew_country_next_code($conn)
{
	ew_country_ensure_table($conn);
	ew_master_sync_menu_codes($conn);
	$prefix = ew_master_code_prefix('Country Master');
	return ew_master_format_code($prefix, ew_master_max_code_number($conn, 'country', 'country_code', $prefix) + 1);
}

function ew_area_ensure_country_name($conn, $table)
{
	$table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
	if ($table === '') {
		return;
	}
	$chk = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'country_name'");
	if ($chk && mysqli_num_rows($chk) === 0) {
		mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN country_name VARCHAR(150) NOT NULL DEFAULT ''");
	}
}

/** Apply menu-name codes on every master that stores a serial code. */
function ew_master_sync_menu_codes($conn)
{
	static $done = false;
	if ($done || !$conn) {
		return;
	}
	$done = true;

	if (ew_master_table_exists($conn, 'city')) {
		ew_master_assign_codes($conn, 'city', 'city_id', 'city_code', ew_master_code_prefix('City Master'), "geo_scope='domestic'", 'city_id ASC');
		ew_master_assign_codes($conn, 'city', 'city_id', 'city_code', ew_master_code_prefix('International City Master'), "geo_scope='international'", 'city_id ASC');
	}
	if (ew_master_table_exists($conn, 'branch')) {
		ew_master_assign_codes($conn, 'branch', 'branch_id', 'branch_code', ew_master_code_prefix('Branch Master'), "geo_scope='domestic'", 'branch_id ASC');
		ew_master_assign_codes($conn, 'branch', 'branch_id', 'branch_code', ew_master_code_prefix('EW360 International Branches'), "geo_scope='international'", 'branch_id ASC');
	}
	ew_state_ensure_code_column($conn);
	ew_country_ensure_table($conn);
	if (ew_master_table_exists($conn, 'country')) {
		ew_master_assign_codes($conn, 'country', 'country_id', 'country_code', ew_master_code_prefix('Country Master'), '1=1', 'country_id ASC');
	}
	if (ew_master_table_exists($conn, 'state')) {
		ew_master_assign_codes($conn, 'state', 'state_id', 'state_code', ew_master_code_prefix('State Master'), "geo_scope='domestic'", 'state_id ASC');
		ew_master_assign_codes($conn, 'state', 'state_id', 'state_code', ew_master_code_prefix('International State Master'), "geo_scope='international'", 'state_id ASC');
	}
	if (ew_master_table_exists($conn, 'hub')) {
		ew_master_assign_codes($conn, 'hub', 'hub_id', 'hub_code', ew_master_code_prefix('Hub Details'), '1=1', 'hub_no ASC, hub_id ASC');
	}
	if (ew_master_table_exists($conn, 'cfs_master')) {
		$cfs_code_col = mysqli_query($conn, "SHOW COLUMNS FROM cfs_master LIKE 'cfs_code'");
		if ($cfs_code_col && mysqli_num_rows($cfs_code_col) > 0) {
			ew_master_assign_codes($conn, 'cfs_master', 'cfs_id', 'cfs_code', ew_master_code_prefix('CFS Master'), '1=1', 'sort_order ASC, cfs_id ASC', 'cfs_code_id');
		}
	}
	if (ew_master_table_exists($conn, 'vehicle_type_master')) {
		ew_master_assign_codes($conn, 'vehicle_type_master', 'vehicle_type_id', 'type_code', ew_master_code_prefix('Vehicle Type Master'), '1=1', 'vehicle_type_id ASC');
	}
	if (ew_master_table_exists($conn, 'expense_group')) {
		ew_master_assign_codes($conn, 'expense_group', 'group_id', 'group_code', ew_master_code_prefix('Expense Group GCN Master'), '1=1', 'sort_no ASC, group_name ASC, group_id ASC');
		$gid_col = mysqli_query($conn, "SHOW COLUMNS FROM expense_category LIKE 'expense_group_id'");
		if ($gid_col && mysqli_num_rows($gid_col) > 0) {
			mysqli_query($conn, 'UPDATE expense_category c INNER JOIN expense_group g ON g.group_id=c.expense_group_id SET c.expense_group=g.group_code');
		}
	}
	if (ew_master_table_exists($conn, 'expense_category')) {
		ew_master_assign_codes($conn, 'expense_category', 'category_id', 'category_code', ew_master_code_prefix('Expense Type GCN Master'), '1=1', 'expense_code_id ASC, category_id ASC');
	}
	if (ew_master_table_exists($conn, 'vendor_master')) {
		ew_master_assign_codes($conn, 'vendor_master', 'vendor_id', 'vendor_code', ew_master_code_prefix('Vendor'), '1=1', 'vendor_code_id ASC, vendor_id ASC');
	}
}

/**
 * Next city code for the open Area Master screen (e.g. EWDC001).
 *
 * @return array{city_code_id:int,city_code:string}
 */
function ew_city_next_code($conn)
{
	ew_master_sync_menu_codes($conn);
	$scope = function_exists('ew_geo_scope_from_request') ? ew_geo_scope_from_request() : 'domestic';
	$prefix = ew_master_code_prefix($scope === 'international' ? 'International City Master' : 'City Master');
	$where = "geo_scope='" . ($scope === 'international' ? 'international' : 'domestic') . "'";
	$row = mysqli_fetch_array(mysqli_query($conn, 'SELECT MAX(city_code_id) AS code_id FROM city'));
	$id = (int) ($row['code_id'] ?? 0) + 1;

	return array(
		'city_code_id' => $id,
		'city_code' => ew_master_format_code($prefix, ew_master_max_code_number($conn, 'city', 'city_code', $prefix, $where) + 1),
	);
}

/**
 * Next branch code for the open branch screen (e.g. EWDB001).
 */
function ew_branch_next_code($conn)
{
	ew_master_sync_menu_codes($conn);
	$scope = function_exists('ew_geo_scope_from_request') ? ew_geo_scope_from_request() : 'domestic';
	$prefix = ew_master_code_prefix($scope === 'international' ? 'EW360 International Branches' : 'Branch Master');
	$where = "geo_scope='" . ($scope === 'international' ? 'international' : 'domestic') . "'";
	return ew_master_format_code($prefix, ew_master_max_code_number($conn, 'branch', 'branch_code', $prefix, $where) + 1);
}

function ew_hub_next_code($conn)
{
	ew_master_sync_menu_codes($conn);
	$prefix = ew_master_code_prefix('Hub Details');
	return ew_master_format_code($prefix, ew_master_max_code_number($conn, 'hub', 'hub_code', $prefix) + 1);
}

/** Shared cancellation reason lists (booking / tax invoice). */
function ew_cancellation_reasons($context = 'booking')
{
	$context = strtolower(trim((string) $context));
	if ($context === 'invoice') {
		return array(
			'Duplicate invoice',
			'Wrong customer or GCN mapped',
			'Amount or rate error',
			'Invoice raised in error',
			'Other',
		);
	}
	if ($context === 'receipt') {
		return array(
			'Duplicate receipt',
			'Wrong invoice or customer mapped',
			'Amount or allocation error',
			'Receipt entered in error',
			'Other',
		);
	}
	return array(
		'Customer request',
		'Wrong booking details',
		'Duplicate booking',
		'Operational / service issue',
		'Other',
	);
}

function ew_hub_ensure_location_city_column($conn)
{
	if (!ew_master_table_exists($conn, 'hub')) {
		return;
	}
	$chk = mysqli_query($conn, "SHOW COLUMNS FROM hub LIKE 'location_city_id'");
	if ($chk && mysqli_num_rows($chk) === 0) {
		mysqli_query($conn, 'ALTER TABLE hub ADD COLUMN location_city_id INT NOT NULL DEFAULT 0 AFTER contact_no');
	}
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

require_once __DIR__ . '/ew_geo_scope.php';

if (isset($conn) && $conn instanceof mysqli) {
	ew_master_sync_menu_codes($conn);
}

?>
