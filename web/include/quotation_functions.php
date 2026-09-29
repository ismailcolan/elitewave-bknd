<?php

require_once __DIR__ . '/vehicle_type_helpers.php';

function ensure_rate_quotation_tables($conn)
{
	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rate_quotation_master (
		quotation_id INT(11) NOT NULL AUTO_INCREMENT,
		quote_no VARCHAR(40) NOT NULL,
		quote_date VARCHAR(20) NOT NULL,
		valid_till VARCHAR(20) DEFAULT NULL,
		quote_type VARCHAR(40) NOT NULL DEFAULT 'door_to_door',
		subject VARCHAR(255) DEFAULT NULL,
		status VARCHAR(30) NOT NULL DEFAULT 'draft',
		party_id INT(11) NOT NULL DEFAULT 0,
		attn_name VARCHAR(150) DEFAULT NULL,
		party_email VARCHAR(120) DEFAULT NULL,
		party_mobile VARCHAR(30) DEFAULT NULL,
		origin_text VARCHAR(150) DEFAULT NULL,
		loading_type VARCHAR(40) DEFAULT NULL,
		destination_name VARCHAR(255) DEFAULT NULL,
		unloading_at VARCHAR(150) DEFAULT NULL,
		delivery_address TEXT,
		vehicle_type_id INT(11) DEFAULT NULL,
		vehicle_label VARCHAR(200) DEFAULT NULL,
		dim_length DECIMAL(12,3) DEFAULT NULL,
		dim_width DECIMAL(12,3) DEFAULT NULL,
		dim_height DECIMAL(12,3) DEFAULT NULL,
		dimension_uom VARCHAR(10) DEFAULT 'FT',
		taxable_value DECIMAL(14,2) NOT NULL DEFAULT 0,
		gst_rate DECIMAL(6,2) NOT NULL DEFAULT 18,
		gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		terms_notes TEXT,
		rejection_remarks VARCHAR(500) DEFAULT NULL,
		converted_ref VARCHAR(80) DEFAULT NULL,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (quotation_id),
		UNIQUE KEY uk_rate_quote_no (quote_no),
		KEY idx_rate_quote_status (status),
		KEY idx_rate_quote_party (party_id),
		KEY idx_rate_quote_date (quote_date)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rate_quotation_lines (
		line_id INT(11) NOT NULL AUTO_INCREMENT,
		quotation_id INT(11) NOT NULL,
		sort_no INT(11) NOT NULL DEFAULT 0,
		charge_label VARCHAR(120) NOT NULL,
		amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		is_taxable TINYINT(1) NOT NULL DEFAULT 1,
		remarks VARCHAR(255) DEFAULT NULL,
		PRIMARY KEY (line_id),
		KEY idx_rate_quote_line (quotation_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rate_quotation_approval_log (
		log_id INT(11) NOT NULL AUTO_INCREMENT,
		quotation_id INT(11) NOT NULL,
		from_status VARCHAR(30) DEFAULT NULL,
		to_status VARCHAR(30) NOT NULL,
		remarks VARCHAR(500) DEFAULT NULL,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		PRIMARY KEY (log_id),
		KEY idx_rate_quote_log (quotation_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	quotation_ensure_schema_columns($conn);
}

function quotation_ensure_schema_columns($conn)
{
	$add = array(
		'party_name' => "ADD COLUMN party_name VARCHAR(255) DEFAULT NULL AFTER party_id",
		'customer_mode' => "ADD COLUMN customer_mode VARCHAR(20) NOT NULL DEFAULT 'new' AFTER party_name",
		'origin_city_id' => "ADD COLUMN origin_city_id INT(11) DEFAULT NULL AFTER origin_text",
		'destination_city_id' => "ADD COLUMN destination_city_id INT(11) DEFAULT NULL AFTER destination_name",
		'cfs_port_factory' => "ADD COLUMN cfs_port_factory VARCHAR(255) DEFAULT NULL AFTER delivery_address",
		'part_number' => "ADD COLUMN part_number VARCHAR(255) DEFAULT NULL AFTER cfs_port_factory",
		'quotation_approval' => "ADD COLUMN quotation_approval VARCHAR(255) DEFAULT NULL AFTER part_number",
		'vehicle_number' => "ADD COLUMN vehicle_number VARCHAR(80) DEFAULT NULL AFTER quotation_approval",
		'freight_paid_by' => "ADD COLUMN freight_paid_by VARCHAR(120) DEFAULT NULL AFTER vehicle_number",
		'insurance_number' => "ADD COLUMN insurance_number VARCHAR(120) DEFAULT NULL AFTER freight_paid_by",
		'highload_challan' => "ADD COLUMN highload_challan VARCHAR(120) DEFAULT NULL AFTER insurance_number",
		'payment_terms' => "ADD COLUMN payment_terms VARCHAR(40) DEFAULT NULL AFTER highload_challan",
		'payment_terms_note' => "ADD COLUMN payment_terms_note VARCHAR(255) DEFAULT NULL AFTER payment_terms",
		'mode_of_transportation' => "ADD COLUMN mode_of_transportation INT(11) DEFAULT NULL AFTER loading_type",
	);
	foreach ($add as $col => $sql) {
		$chk = mysqli_query($conn, "SHOW COLUMNS FROM rate_quotation_master LIKE '$col'");
		if ($chk && mysqli_num_rows($chk) === 0) {
			mysqli_query($conn, "ALTER TABLE rate_quotation_master $sql");
		}
	}
}

function quotation_status_options()
{
	return array(
		'draft' => 'Draft',
		'pending_approval' => 'Pending approval',
		'approved' => 'Approved',
		'sent' => 'Sent to customer',
		'customer_confirmed' => 'Customer confirmed',
		'rejected' => 'Rejected',
		'expired' => 'Expired',
		'converted' => 'Converted to booking',
		'cancelled' => 'Cancelled',
	);
}

function quotation_status_label($code)
{
	$opts = quotation_status_options();
	return $opts[$code] ?? $code;
}

function quotation_quote_type_options()
{
	return array(
		'door_to_door' => 'Door-to-Door',
		'port_to_door' => 'Port-to-Door',
		'ftl_contract' => 'FTL Contract',
	);
}

function quotation_loading_type_options()
{
	return array(
		'1_point' => '1-Point Loading',
		'2_point' => '2-Point Loading',
		'multi_point' => 'Multi-Point Loading',
	);
}

function quotation_loading_label($code)
{
	$opts = quotation_loading_type_options();
	return $opts[$code] ?? $code;
}

function quotation_mode_of_transport_label($conn, $mode_id)
{
	$mode_id = (int) $mode_id;
	if ($mode_id <= 0) {
		return '';
	}
	if (function_exists('get_mode')) {
		$label = get_mode($conn, $mode_id);
		return trim((string) $label);
	}
	$q = @mysqli_query($conn, "SELECT mode_type FROM mode_of_transportation WHERE mode_id='$mode_id' AND status=0 LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return trim((string) ($row['mode_type'] ?? ''));
	}
	return '';
}

function quotation_quotation_approval_options()
{
	return array(
		'Email & Phone Call' => 'Email & Phone Call',
		'Email approval' => 'Email approval',
		'WhatsApp approval' => 'WhatsApp approval',
		'Phone call approval' => 'Phone call approval',
		'SMS approval' => 'SMS approval',
	);
}

/** Active consignment modes from master (same source as GCN Consignment Mode). */
function quotation_consignment_mode_options($conn)
{
	$out = array();
	$q = @mysqli_query($conn, 'SELECT consignment_id, consignment_mode FROM consignment_mode WHERE status=0 ORDER BY consignment_mode ASC');
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$out[(string) $row['consignment_id']] = trim((string) ($row['consignment_mode'] ?? ''));
		}
	}
	return $out;
}

function quotation_freight_paid_by_label($conn, $code)
{
	$code = trim((string) $code);
	if ($code === '') {
		return '—';
	}
	if (ctype_digit($code)) {
		$id = (int) $code;
		if ($id > 0 && function_exists('consignment_mode')) {
			$name = trim((string) consignment_mode($conn, $id));
			if ($name !== '') {
				return $name;
			}
		}
	}
	$legacy = array(
		'consignee' => 'Consignee',
		'consignor' => 'Consignor',
		'ship_to' => 'Ship To',
	);
	if (isset($legacy[$code])) {
		return $legacy[$code];
	}
	return $code;
}

function quotation_payment_terms_options()
{
	$opts = array();
	for ($days = 5; $days <= 100; $days += 5) {
		$key = $days . '_days';
		$opts[$key] = $days . ' days';
	}
	return $opts;
}

function quotation_payment_terms_label($code, $note = '')
{
	$opts = quotation_payment_terms_options();
	$code = trim((string) $code);
	if (isset($opts[$code])) {
		return $opts[$code];
	}
	$legacy = array(
		'immediate' => 'Immediate',
		'15_days' => '15 days',
		'30_days' => '30 days',
		'60_days' => '60 days',
		'other' => 'Other',
	);
	if (isset($legacy[$code])) {
		return $legacy[$code];
	}
	if (preg_match('/^(\d+)_days$/', $code, $m)) {
		return $m[1] . ' days';
	}
	return $code !== '' ? $code : '—';
}

function quotation_fy_suffix($quote_date)
{
	$quote_date = trim((string) $quote_date);
	$ts = strtotime(str_replace('/', '-', $quote_date));
	if (!$ts) {
		$ts = time();
	}
	$m = (int) date('n', $ts);
	$y = (int) date('Y', $ts);
	$start = ($m >= 4) ? $y : ($y - 1);
	return sprintf('%02d-%02d', $start % 100, ($start + 1) % 100);
}

function quotation_preview_number($conn, $quote_date = '')
{
	ensure_rate_quotation_tables($conn);
	if ($quote_date === '') {
		$quote_date = date('d-m-Y');
	}
	$fy = quotation_fy_suffix($quote_date);
	$prefix = 'QT/' . $fy . '/';
	$prefix_esc = mysqli_real_escape_string($conn, $prefix);
	$q = mysqli_query($conn, "SELECT quote_no FROM rate_quotation_master WHERE quote_no LIKE '{$prefix_esc}%' ORDER BY quotation_id DESC LIMIT 1");
	$next = 1;
	if ($q && ($row = mysqli_fetch_assoc($q)) && !empty($row['quote_no'])) {
		if (preg_match('/(\d+)$/', $row['quote_no'], $m)) {
			$next = (int) $m[1] + 1;
		}
	}
	return $prefix . sprintf('%05d', $next);
}

function quotation_allocate_number($conn, $quote_date)
{
	return quotation_preview_number($conn, $quote_date);
}

function quotation_format_money($val)
{
	return number_format((float) $val, 2, '.', '');
}

function quotation_format_money_display($val)
{
	$n = (float) $val;
	if (abs($n - round($n)) < 0.001) {
		return number_format($n, 0, '.', ',');
	}
	return number_format($n, 2, '.', ',');
}

function quotation_default_lines()
{
	return array(
		array('charge_label' => 'Freight charges', 'amount' => '', 'is_taxable' => 1, 'remarks' => ''),
		array('charge_label' => 'Document charges', 'amount' => '250', 'is_taxable' => 1, 'remarks' => ''),
		array('charge_label' => 'Mamul charges', 'amount' => '', 'is_taxable' => 1, 'remarks' => ''),
		array('charge_label' => 'Vehicle Halting Charges', 'amount' => '', 'is_taxable' => 1, 'remarks' => ''),
		array('charge_label' => 'Vehicle Loading/Unloading', 'amount' => '', 'is_taxable' => 1, 'remarks' => ''),
		array('charge_label' => 'Loading / Unloading', 'amount' => '', 'is_taxable' => 1, 'remarks' => ''),
		array('charge_label' => 'HighLoad challan', 'amount' => '', 'is_taxable' => 1, 'remarks' => ''),
		array('charge_label' => 'Insurance', 'amount' => '', 'is_taxable' => 1, 'remarks' => ''),
	);
}

function quotation_is_insurance_charge_label($label)
{
	$lab = strtolower(trim((string) $label));
	return ($lab === 'insurance' || $lab === 'vehicle insurance');
}

function quotation_insurance_number_from_lines($lines)
{
	foreach ($lines as $line) {
		if (!quotation_is_insurance_charge_label($line['charge_label'] ?? '')) {
			continue;
		}
		$rem = trim((string) ($line['remarks'] ?? ''));
		if ($rem !== '') {
			return $rem;
		}
	}
	return '';
}

function quotation_get($conn, $quotation_id)
{
	$quotation_id = (int) $quotation_id;
	if ($quotation_id <= 0) {
		return null;
	}
	ensure_rate_quotation_tables($conn);
	$q = mysqli_query($conn, "SELECT * FROM rate_quotation_master WHERE quotation_id='$quotation_id' LIMIT 1");
	return ($q && ($row = mysqli_fetch_assoc($q))) ? $row : null;
}

function quotation_get_lines($conn, $quotation_id)
{
	$quotation_id = (int) $quotation_id;
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM rate_quotation_lines WHERE quotation_id='$quotation_id' ORDER BY sort_no ASC, line_id ASC");
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function quotation_party_name($conn, $party_id)
{
	$party_id = (int) $party_id;
	if ($party_id <= 0) {
		return '';
	}
	$q = mysqli_query($conn, "SELECT client_company_name FROM client WHERE client_id='$party_id' LIMIT 1");
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		if (function_exists('ew_client_decrypt_name')) {
			return ew_client_decrypt_name($row['client_company_name'] ?? '');
		}
		return (string) ($row['client_company_name'] ?? '');
	}
	return '';
}

function quotation_recipient_name($conn, $row)
{
	$row = is_array($row) ? $row : array();
	$manual = trim((string) ($row['party_name'] ?? ''));
	if ($manual !== '') {
		return $manual;
	}
	return quotation_party_name($conn, (int) ($row['party_id'] ?? 0));
}

function quotation_city_name($conn, $city_id)
{
	$city_id = (int) $city_id;
	if ($city_id <= 0) {
		return '';
	}
	if (function_exists('get_city_name')) {
		return trim((string) get_city_name($conn, $city_id));
	}
	$q = mysqli_query($conn, "SELECT city_name FROM city WHERE city_id='$city_id' LIMIT 1");
	$r = $q ? mysqli_fetch_assoc($q) : null;
	return trim((string) ($r['city_name'] ?? ''));
}

function quotation_compute_from_lines($lines, $gst_rate)
{
	$taxable = 0.0;
	foreach ($lines as $line) {
		$amt = (float) ($line['amount'] ?? 0);
		if (!empty($line['is_taxable'])) {
			$taxable += $amt;
		}
	}
	$gst_rate = (float) $gst_rate;
	$gst = round($taxable * $gst_rate / 100, 2);
	$total = round($taxable + $gst, 2);
	return array(
		'taxable_value' => $taxable,
		'gst_amount' => $gst,
		'total_amount' => $total,
	);
}

function quotation_parse_lines_from_post($payload)
{
	$labels = $payload['charge_label'] ?? array();
	$amounts = $payload['charge_amount'] ?? array();
	$taxables = $payload['charge_taxable'] ?? array();
	$remarks = $payload['charge_remarks'] ?? array();
	if (!is_array($labels)) {
		$labels = array();
	}
	$lines = array();
	$n = max(count($labels), count($amounts));
	for ($i = 0; $i < $n; $i++) {
		$label = trim((string) ($labels[$i] ?? ''));
		if ($label === '') {
			continue;
		}
		$amt_raw = trim((string) ($amounts[$i] ?? ''));
		$amt = ($amt_raw === '') ? 0.0 : (float) $amt_raw;
		if ($amt_raw !== '' && !is_numeric($amt_raw)) {
			return array('ok' => false, 'message' => 'Invalid amount on line: ' . $label);
		}
		$lines[] = array(
			'charge_label' => $label,
			'amount' => $amt,
			'is_taxable' => !empty($taxables[$i]) ? 1 : 0,
			'remarks' => trim((string) ($remarks[$i] ?? '')),
		);
	}
	if (!$lines) {
		return array('ok' => false, 'message' => 'Add at least one charge line.');
	}
	return array('ok' => true, 'lines' => $lines);
}

function quotation_log_status($conn, $quotation_id, $from, $to, $remarks, $user_id)
{
	$quotation_id = (int) $quotation_id;
	$user_id = (int) $user_id;
	$now = date('d-m-Y');
	$from_esc = mysqli_real_escape_string($conn, (string) $from);
	$to_esc = mysqli_real_escape_string($conn, (string) $to);
	$rem_esc = mysqli_real_escape_string($conn, (string) $remarks);
	mysqli_query($conn, "INSERT INTO rate_quotation_approval_log (quotation_id, from_status, to_status, remarks, created_at, created_by)
		VALUES ('$quotation_id', '$from_esc', '$to_esc', '$rem_esc', '$now', '$user_id')");
}

function quotation_is_editable($status)
{
	return in_array($status, array('draft', 'rejected'), true);
}

function quotation_vehicle_snapshot($conn, $vehicle_type_id)
{
	$vehicle_type_id = (int) $vehicle_type_id;
	if ($vehicle_type_id <= 0) {
		return array(
			'vehicle_label' => '',
			'dim_length' => null,
			'dim_width' => null,
			'dim_height' => null,
			'dimension_uom' => 'FT',
			'dim_display' => '',
		);
	}
	$row = ew_vehicle_type_get($conn, $vehicle_type_id);
	if (!$row) {
		return array(
			'vehicle_label' => '',
			'dim_length' => null,
			'dim_width' => null,
			'dim_height' => null,
			'dimension_uom' => 'FT',
			'dim_display' => '',
		);
	}
	$uom = $row['dimension_uom'] ?: 'FT';
	$l = $row['dim_length'];
	$w = $row['dim_width'];
	$h = $row['dim_height'];
	$disp = '';
	if ($l !== null && $l !== '' && $w !== null && $w !== '' && $h !== null && $h !== '') {
		$disp = rtrim(rtrim($l, '0'), '.') . ' ' . $uom . ' (L) × '
			. rtrim(rtrim($w, '0'), '.') . ' ' . $uom . ' (W) × '
			. rtrim(rtrim($h, '0'), '.') . ' ' . $uom . ' (H)';
	}
	return array(
		'vehicle_label' => $row['type_name'],
		'dim_length' => $l,
		'dim_width' => $w,
		'dim_height' => $h,
		'dimension_uom' => $uom,
		'dim_display' => $disp,
	);
}

function quotation_save($conn, $payload, $user_id, $action = 'save_draft')
{
	ensure_rate_quotation_tables($conn);
	$quotation_id = (int) ($payload['quotation_id'] ?? 0);
	$existing = $quotation_id > 0 ? quotation_get($conn, $quotation_id) : null;
	$current_status = $existing ? $existing['status'] : 'draft';

	$workflow_actions = array('approve', 'reject', 'mark_sent', 'customer_confirmed', 'cancel', 'resend_email');
	if (in_array($action, $workflow_actions, true)) {
		return quotation_workflow($conn, $existing, $action, $payload, $user_id);
	}

	if ($existing && !quotation_is_editable($current_status)) {
		return array('ok' => false, 'message' => 'This quotation cannot be edited in status: ' . quotation_status_label($current_status));
	}

	$quote_date = trim((string) ($payload['quote_date'] ?? date('d-m-Y')));
	$valid_till = trim((string) ($payload['valid_till'] ?? ''));
	$quote_type = trim((string) ($payload['quote_type'] ?? 'door_to_door'));
	$subject = trim((string) ($payload['subject'] ?? ''));
	$customer_mode = trim((string) ($payload['customer_mode'] ?? 'new'));
	if ($customer_mode !== 'existing') {
		$customer_mode = 'new';
	}
	$party_id = (int) ($payload['party_id'] ?? 0);
	$party_name = trim((string) ($payload['party_name'] ?? ''));
	$attn = trim((string) ($payload['attn_name'] ?? ''));
	$party_email = trim((string) ($payload['party_email'] ?? ''));
	$party_mobile = trim((string) ($payload['party_mobile'] ?? ''));
	$origin_city_id = (int) ($payload['origin_city_id'] ?? 0);
	$destination_city_id = (int) ($payload['destination_city_id'] ?? 0);
	$loading_type = trim((string) ($payload['loading_type'] ?? ''));
	$mode_of_transportation = (int) ($payload['mode_of_transportation'] ?? 0);
	$destination = trim((string) ($payload['destination_name'] ?? ''));
	$unloading = trim((string) ($payload['unloading_at'] ?? ''));
	$delivery_address = trim((string) ($payload['delivery_address'] ?? ''));
	$vehicle_type_id = (int) ($payload['vehicle_type_id'] ?? 0);
	$gst_rate = (float) ($payload['gst_rate'] ?? 18);
	$terms = trim((string) ($payload['terms_notes'] ?? ''));
	$cfs_port_factory = trim((string) ($payload['cfs_port_factory'] ?? ''));
	$part_number = trim((string) ($payload['part_number'] ?? ''));
	$quotation_approval = trim((string) ($payload['quotation_approval'] ?? ''));
	$freight_paid_by = trim((string) ($payload['freight_paid_by'] ?? ''));
	$payment_terms = trim((string) ($payload['payment_terms'] ?? ''));

	if ($customer_mode === 'existing') {
		if ($party_id <= 0) {
			return array('ok' => false, 'message' => 'Please select an existing customer.');
		}
		$party_name = quotation_party_name($conn, $party_id);
	} else {
		$party_id = 0;
		if ($party_name === '') {
			return array('ok' => false, 'message' => 'Please enter customer / company name.');
		}
	}
	if ($attn === '') {
		return array('ok' => false, 'message' => 'Kind Attn. is required.');
	}
	if ($origin_city_id <= 0) {
		return array('ok' => false, 'message' => 'Please select origin city.');
	}
	if ($destination_city_id <= 0) {
		return array('ok' => false, 'message' => 'Please select destination city.');
	}
	if ($destination === '') {
		return array('ok' => false, 'message' => 'Consignee / delivery party name is required.');
	}
	if ($mode_of_transportation <= 0) {
		return array('ok' => false, 'message' => 'Please select mode of transport.');
	}

	$origin = quotation_city_name($conn, $origin_city_id);
	$dest_city = quotation_city_name($conn, $destination_city_id);
	if ($unloading === '') {
		$unloading = $dest_city;
	}

	$parsed = quotation_parse_lines_from_post($payload);
	if (empty($parsed['ok'])) {
		return $parsed;
	}
	$lines = $parsed['lines'];
	$totals = quotation_compute_from_lines($lines, $gst_rate);
	$veh = quotation_vehicle_snapshot($conn, $vehicle_type_id);

	$now = date('d-m-Y');
	$user_id = (int) $user_id;
	$quote_no = $existing ? $existing['quote_no'] : quotation_allocate_number($conn, $quote_date);

	$esc = function ($v) use ($conn) {
		return mysqli_real_escape_string($conn, (string) $v);
	};

	$status = $existing ? $current_status : 'draft';
	if ($action === 'submit' && quotation_is_editable($status)) {
		$status = 'draft';
	}

	$fields = array(
		"quote_no='" . $esc($quote_no) . "'",
		"quote_date='" . $esc($quote_date) . "'",
		"valid_till='" . $esc($valid_till) . "'",
		"quote_type='" . $esc($quote_type) . "'",
		"subject='" . $esc($subject) . "'",
		"status='" . $esc($status) . "'",
		"party_id='$party_id'",
		"party_name='" . $esc($party_name) . "'",
		"customer_mode='" . $esc($customer_mode) . "'",
		"attn_name='" . $esc($attn) . "'",
		"party_email='" . $esc($party_email) . "'",
		"party_mobile='" . $esc($party_mobile) . "'",
		"origin_text='" . $esc($origin) . "'",
		"origin_city_id='$origin_city_id'",
		"loading_type='" . $esc($loading_type) . "'",
		"mode_of_transportation='$mode_of_transportation'",
		"destination_name='" . $esc($destination) . "'",
		"destination_city_id='$destination_city_id'",
		"unloading_at='" . $esc($unloading) . "'",
		"delivery_address='" . $esc($delivery_address) . "'",
		"cfs_port_factory='" . $esc($cfs_port_factory) . "'",
		"part_number='" . $esc($part_number) . "'",
		"quotation_approval='" . $esc($quotation_approval) . "'",
		"freight_paid_by='" . $esc($freight_paid_by) . "'",
		"payment_terms='" . $esc($payment_terms) . "'",
		"payment_terms_note=''",
		"vehicle_type_id=" . ($vehicle_type_id > 0 ? "'$vehicle_type_id'" : 'NULL'),
		"vehicle_label='" . $esc($veh['vehicle_label']) . "'",
		"dim_length=" . ($veh['dim_length'] === null ? 'NULL' : "'" . $esc($veh['dim_length']) . "'"),
		"dim_width=" . ($veh['dim_width'] === null ? 'NULL' : "'" . $esc($veh['dim_width']) . "'"),
		"dim_height=" . ($veh['dim_height'] === null ? 'NULL' : "'" . $esc($veh['dim_height']) . "'"),
		"dimension_uom='" . $esc($veh['dimension_uom']) . "'",
		"taxable_value='" . $esc(quotation_format_money($totals['taxable_value'])) . "'",
		"gst_rate='" . $esc($gst_rate) . "'",
		"gst_amount='" . $esc(quotation_format_money($totals['gst_amount'])) . "'",
		"total_amount='" . $esc(quotation_format_money($totals['total_amount'])) . "'",
		"terms_notes='" . $esc($terms) . "'",
		"updated_at='$now'",
		"updated_by='$user_id'",
	);

	if ($existing) {
		$sql = 'UPDATE rate_quotation_master SET ' . implode(', ', $fields) . " WHERE quotation_id='$quotation_id' LIMIT 1";
		$ok = mysqli_query($conn, $sql);
	} else {
		$sql = "INSERT INTO rate_quotation_master SET " . implode(', ', $fields)
			. ", created_at='$now', created_by='$user_id'";
		$ok = mysqli_query($conn, $sql);
		$quotation_id = (int) mysqli_insert_id($conn);
		if ($ok) {
			quotation_log_status($conn, $quotation_id, '', 'draft', 'Created', $user_id);
		}
	}

	if (!$ok || $quotation_id <= 0) {
		return array('ok' => false, 'message' => 'Could not save quotation.');
	}

	mysqli_query($conn, "DELETE FROM rate_quotation_lines WHERE quotation_id='$quotation_id'");
	$sort = 0;
	foreach ($lines as $line) {
		$sort += 10;
		$label = $esc($line['charge_label']);
		$amt = $esc(quotation_format_money($line['amount']));
		$tax = (int) $line['is_taxable'];
		$rem = $esc($line['remarks']);
		mysqli_query($conn, "INSERT INTO rate_quotation_lines (quotation_id, sort_no, charge_label, amount, is_taxable, remarks)
			VALUES ('$quotation_id', '$sort', '$label', '$amt', '$tax', '$rem')");
	}

	if ($action === 'submit') {
		mysqli_query($conn, "UPDATE rate_quotation_master SET status='pending_approval', updated_at='$now', updated_by='$user_id' WHERE quotation_id='$quotation_id' LIMIT 1");
		quotation_log_status($conn, $quotation_id, $current_status, 'pending_approval', 'Submitted for approval', $user_id);
	}

	return array(
		'ok' => true,
		'message' => ($action === 'submit') ? 'Submitted for approval.' : 'Saved successfully.',
		'quotation_id' => $quotation_id,
		'quote_no' => $quote_no,
	);
}

function quotation_workflow($conn, $existing, $action, $payload, $user_id)
{
	if (!$existing) {
		return array('ok' => false, 'message' => 'Quotation not found.');
	}
	$quotation_id = (int) $existing['quotation_id'];
	$from = $existing['status'];
	$to = $from;
	$remarks = trim((string) ($payload['workflow_remarks'] ?? ''));

	switch ($action) {
		case 'approve':
			if ($from !== 'pending_approval') {
				return array('ok' => false, 'message' => 'Only pending quotations can be approved.');
			}
			$to = 'approved';
			break;
		case 'reject':
			if ($from !== 'pending_approval') {
				return array('ok' => false, 'message' => 'Only pending quotations can be rejected.');
			}
			if ($remarks === '') {
				return array('ok' => false, 'message' => 'Rejection remarks are required.');
			}
			$to = 'rejected';
			break;
		case 'mark_sent':
			if (!in_array($from, array('approved', 'sent'), true)) {
				return array('ok' => false, 'message' => 'Approve quotation before sending to customer.');
			}
			$to = 'sent';
			break;
		case 'customer_confirmed':
			if ($from !== 'sent') {
				return array('ok' => false, 'message' => 'Mark as sent before customer confirmation.');
			}
			$to = 'customer_confirmed';
			break;
		case 'cancel':
			if (in_array($from, array('converted', 'cancelled'), true)) {
				return array('ok' => false, 'message' => 'Cannot cancel this quotation.');
			}
			$to = 'cancelled';
			break;
		case 'resend_email':
			if (!in_array($from, array('approved', 'sent', 'customer_confirmed'), true)) {
				return array('ok' => false, 'message' => 'Email can be resent only after approval.');
			}
			require_once __DIR__ . '/quotation_email.php';
			$mail_result = quotation_send_approved_email($conn, $existing);
			if (!empty($mail_result['ok'])) {
				quotation_log_status($conn, $quotation_id, $from, $from, 'Quotation email resent to customer.', $user_id);
				return array(
					'ok' => true,
					'message' => $mail_result['message'] ?? 'Email sent.',
					'email_sent' => true,
					'quotation_id' => $quotation_id,
					'quote_no' => $existing['quote_no'],
				);
			}
			return array(
				'ok' => false,
				'message' => $mail_result['message'] ?? 'Email not sent.',
				'email_sent' => false,
				'quotation_id' => $quotation_id,
				'quote_no' => $existing['quote_no'],
			);
		default:
			return array('ok' => false, 'message' => 'Unknown action.');
	}

	$now = date('d-m-Y');
	$user_id = (int) $user_id;
	$to_esc = mysqli_real_escape_string($conn, $to);
	$rem_esc = mysqli_real_escape_string($conn, $remarks);
	$extra = ($action === 'reject') ? ", rejection_remarks='$rem_esc'" : '';
	mysqli_query($conn, "UPDATE rate_quotation_master SET status='$to_esc', updated_at='$now', updated_by='$user_id' $extra WHERE quotation_id='$quotation_id' LIMIT 1");
	quotation_log_status($conn, $quotation_id, $from, $to, $remarks, $user_id);

	$message = 'Status updated to ' . quotation_status_label($to) . '.';
	$email_sent = false;

	if ($action === 'approve') {
		require_once __DIR__ . '/quotation_email.php';
		$fresh = quotation_get($conn, $quotation_id);
		$mail_result = quotation_send_approved_email($conn, $fresh);
		if (!empty($mail_result['ok'])) {
			$email_sent = true;
			$message .= ' ' . ($mail_result['message'] ?? 'Email sent.');
			quotation_log_status($conn, $quotation_id, $to, $to, 'Quotation email sent to customer.', $user_id);
		} else {
			$message .= ' Email not sent: ' . ($mail_result['message'] ?? 'Unknown error.');
			quotation_log_status($conn, $quotation_id, $to, $to, 'Email not sent: ' . ($mail_result['message'] ?? ''), $user_id);
		}
	}

	return array(
		'ok' => true,
		'message' => $message,
		'email_sent' => $email_sent,
		'quotation_id' => $quotation_id,
		'quote_no' => $existing['quote_no'],
	);
}

function quotation_delete($conn, $quotation_id)
{
	$quotation_id = (int) $quotation_id;
	$row = quotation_get($conn, $quotation_id);
	if (!$row) {
		return array('ok' => false, 'message' => 'Quotation not found.');
	}
	if (!in_array($row['status'], array('draft', 'rejected', 'cancelled'), true)) {
		return array('ok' => false, 'message' => 'Only draft, rejected or cancelled quotations can be deleted.');
	}
	mysqli_query($conn, "DELETE FROM rate_quotation_lines WHERE quotation_id='$quotation_id'");
	mysqli_query($conn, "DELETE FROM rate_quotation_approval_log WHERE quotation_id='$quotation_id'");
	mysqli_query($conn, "DELETE FROM rate_quotation_master WHERE quotation_id='$quotation_id' LIMIT 1");
	return array('ok' => true, 'message' => 'Deleted.');
}

function quotation_list_rows($conn, $status_filter = '')
{
	ensure_rate_quotation_tables($conn);
	$sql = "SELECT q.*, c.client_company_name
		FROM rate_quotation_master q
		LEFT JOIN client c ON c.client_id = q.party_id";
	if ($status_filter !== '' && $status_filter !== 'all') {
		$sf = mysqli_real_escape_string($conn, $status_filter);
		$sql .= " WHERE q.status='$sf'";
	}
	$sql .= ' ORDER BY q.quotation_id DESC LIMIT 500';
	$rows = array();
	$q = mysqli_query($conn, $sql);
	if ($q) {
		while ($row = mysqli_fetch_assoc($q)) {
			$row['party_display'] = quotation_recipient_name($conn, $row);
			$rows[] = $row;
		}
	}
	return $rows;
}

function quotation_dim_display($row)
{
	$uom = $row['dimension_uom'] ?? 'FT';
	$l = $row['dim_length'];
	$w = $row['dim_width'];
	$h = $row['dim_height'];
	if ($l === null || $l === '' || $w === null || $w === '') {
		return '';
	}
	return rtrim(rtrim((string) $l, '0'), '.') . ' ' . $uom . ' (L) × '
		. rtrim(rtrim((string) $w, '0'), '.') . ' ' . $uom . ' (W) × '
		. rtrim(rtrim((string) ($h ?? ''), '0'), '.') . ' ' . $uom . ' (H)';
}
