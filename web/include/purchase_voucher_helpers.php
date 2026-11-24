<?php
require_once __DIR__ . '/expense_schema.php';
require_once __DIR__ . '/expense_gcn_helpers.php';
require_once __DIR__ . '/vendor_master_helpers.php';
require_once __DIR__ . '/company_bank_helpers.php';
require_once __DIR__ . '/billing_functions.php';

function purchase_voucher_upload_dir()
{
	return dirname(__DIR__) . '/purchase_voucher_uploads';
}

function purchase_voucher_upload_url_base()
{
	return 'purchase_voucher_uploads/';
}

function purchase_voucher_ensure_upload_dir()
{
	$dir = purchase_voucher_upload_dir();
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	$htaccess = $dir . '/.htaccess';
	if (!is_file($htaccess)) {
		@file_put_contents($htaccess, "Options -Indexes\n");
	}
	return is_dir($dir) && is_writable($dir);
}

function purchase_voucher_money($value)
{
	return round((float) $value, 2);
}

function purchase_voucher_ensure_schema($conn)
{
	expense_gcn_ensure_schema($conn);
	ew_vendor_ensure_table($conn);
	ew_company_bank_ensure_schema($conn);

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS purchase_voucher (
		purchase_voucher_id INT(11) NOT NULL AUTO_INCREMENT,
		voucher_no VARCHAR(20) NOT NULL DEFAULT '',
		voucher_date VARCHAR(20) DEFAULT '',
		vendor_id INT(11) NOT NULL DEFAULT 0,
		supplier_invoice_no VARCHAR(100) NOT NULL DEFAULT '',
		supplier_invoice_date VARCHAR(20) DEFAULT '',
		invoice_received_date VARCHAR(20) DEFAULT '',
		narration VARCHAR(1000) DEFAULT '',
		subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
		gst_total DECIMAL(14,2) NOT NULL DEFAULT 0,
		grand_total DECIMAL(14,2) NOT NULL DEFAULT 0,
		tds_percent DECIMAL(8,2) NOT NULL DEFAULT 0,
		tds_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		other_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
		net_payable DECIMAL(14,2) NOT NULL DEFAULT 0,
		payment_mode VARCHAR(20) DEFAULT '',
		company_bank_id INT(11) NOT NULL DEFAULT 0,
		payment_bank_name VARCHAR(150) DEFAULT '',
		payment_ifsc VARCHAR(11) DEFAULT '',
		payment_bank_branch VARCHAR(150) DEFAULT '',
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		updated_at VARCHAR(20) DEFAULT NULL,
		updated_by INT(11) DEFAULT NULL,
		PRIMARY KEY (purchase_voucher_id),
		UNIQUE KEY uk_purchase_voucher_no (voucher_no),
		KEY idx_purchase_voucher_vendor (vendor_id),
		KEY idx_purchase_voucher_status (status)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS purchase_voucher_line (
		line_id INT(11) NOT NULL AUTO_INCREMENT,
		purchase_voucher_id INT(11) NOT NULL,
		line_no INT(11) NOT NULL DEFAULT 1,
		category_id INT(11) NOT NULL DEFAULT 0,
		item_description VARCHAR(500) NOT NULL DEFAULT '',
		hsn_sac VARCHAR(20) DEFAULT '',
		qty DECIMAL(14,3) NOT NULL DEFAULT 1,
		unit VARCHAR(20) DEFAULT '',
		rate DECIMAL(14,2) NOT NULL DEFAULT 0,
		taxable_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		gst_percent DECIMAL(8,2) NOT NULL DEFAULT 0,
		gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
		line_total DECIMAL(14,2) NOT NULL DEFAULT 0,
		PRIMARY KEY (line_id),
		KEY idx_purchase_voucher_line_pv (purchase_voucher_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($conn, "CREATE TABLE IF NOT EXISTS purchase_voucher_attachment (
		attachment_id INT(11) NOT NULL AUTO_INCREMENT,
		purchase_voucher_id INT(11) NOT NULL,
		original_name VARCHAR(255) DEFAULT '',
		stored_name VARCHAR(255) NOT NULL DEFAULT '',
		mime_type VARCHAR(120) DEFAULT '',
		file_size INT(11) NOT NULL DEFAULT 0,
		status TINYINT(1) NOT NULL DEFAULT 0,
		created_at VARCHAR(20) DEFAULT NULL,
		created_by INT(11) DEFAULT NULL,
		PRIMARY KEY (attachment_id),
		KEY idx_pv_attachment_pv (purchase_voucher_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	purchase_voucher_ensure_upload_dir();
}

function purchase_voucher_next_no($conn, $purchase_voucher_id)
{
	$id = (int) $purchase_voucher_id;
	return 'EPVCH' . sprintf('%05d', $id);
}

function purchase_voucher_preview_next_no($conn)
{
	purchase_voucher_ensure_schema($conn);
	$next_id = 1;
	$q = mysqli_query($conn, 'SELECT MAX(purchase_voucher_id) AS max_id FROM purchase_voucher');
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		$next_id = (int) ($row['max_id'] ?? 0) + 1;
	}
	return purchase_voucher_next_no($conn, $next_id);
}

function purchase_voucher_gst_percent_options()
{
	return array(0, 5, 12, 18, 28);
}

function purchase_voucher_normalize_gst_percent($gst_percent)
{
	$gst_percent = purchase_voucher_money($gst_percent);
	if ($gst_percent < 0) {
		$gst_percent = 0;
	}
	if ($gst_percent > 100) {
		$gst_percent = 100;
	}
	$allowed = purchase_voucher_gst_percent_options();
	foreach ($allowed as $rate) {
		if (abs($gst_percent - (float) $rate) < 0.001) {
			return (float) $rate;
		}
	}
	return 0.0;
}

function purchase_voucher_tds_percent_options($conn)
{
	$out = array(array('rate' => '0.00', 'label' => 'No TDS (0%)'));
	foreach (billing_receipt_tds_percent_options($conn) as $opt) {
		$rate = purchase_voucher_money($opt['rate'] ?? 0);
		if ($rate <= 0) {
			continue;
		}
		$out[] = array(
			'rate' => number_format($rate, 2, '.', ''),
			'label' => $opt['label'] ?? ('TDS ' . $rate . '%'),
		);
	}
	return $out;
}

function purchase_voucher_allowed_mime($mime, $ext)
{
	$ext = strtolower((string) $ext);
	$mime = strtolower((string) $mime);
	$allowed_ext = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx');
	if (!in_array($ext, $allowed_ext, true)) {
		return false;
	}
	$allowed_mime = array(
		'image/jpeg', 'image/png', 'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	);
	return in_array($mime, $allowed_mime, true);
}

function purchase_voucher_attachments($conn, $purchase_voucher_id)
{
	purchase_voucher_ensure_schema($conn);
	$purchase_voucher_id = (int) $purchase_voucher_id;
	$out = array();
	if ($purchase_voucher_id <= 0) {
		return $out;
	}
	$q = mysqli_query($conn, "SELECT * FROM purchase_voucher_attachment
		WHERE purchase_voucher_id='$purchase_voucher_id' AND status=0
		ORDER BY attachment_id ASC");
	while ($q && ($row = mysqli_fetch_assoc($q))) {
		$stored = trim((string) ($row['stored_name'] ?? ''));
		$out[] = array(
			'attachment_id' => (int) $row['attachment_id'],
			'original_name' => $row['original_name'] ?? '',
			'stored_name' => $stored,
			'mime_type' => $row['mime_type'] ?? '',
			'file_size' => (int) ($row['file_size'] ?? 0),
			'url' => $stored !== '' ? (purchase_voucher_upload_url_base() . rawurlencode($stored)) : '',
			'is_image' => (strpos(strtolower($row['mime_type'] ?? ''), 'image/') === 0),
		);
	}
	return $out;
}

function purchase_voucher_store_uploads($conn, $purchase_voucher_id, $files, $user_id)
{
	purchase_voucher_ensure_schema($conn);
	$purchase_voucher_id = (int) $purchase_voucher_id;
	$user_id = (int) $user_id;
	if ($purchase_voucher_id <= 0 || empty($files) || !is_array($files)) {
		return array('ok' => true, 'saved' => 0);
	}
	if (!purchase_voucher_ensure_upload_dir()) {
		return array('ok' => false, 'message' => 'Attachment upload folder is not writable.');
	}
	$names = $files['name'] ?? null;
	if (!is_array($names)) {
		$names = array($files['name'] ?? '');
		$files = array(
			'name' => $names,
			'type' => array($files['type'] ?? ''),
			'tmp_name' => array($files['tmp_name'] ?? ''),
			'error' => array($files['error'] ?? UPLOAD_ERR_NO_FILE),
			'size' => array($files['size'] ?? 0),
		);
	}
	$saved = 0;
	$now = date('d-m-Y');
	$count = count($files['name']);
	for ($i = 0; $i < $count; $i++) {
		$error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
		if ($error === UPLOAD_ERR_NO_FILE) {
			continue;
		}
		if ($error !== UPLOAD_ERR_OK) {
			return array('ok' => false, 'message' => 'One or more attachments failed to upload.');
		}
		$original = trim((string) ($files['name'][$i] ?? ''));
		$tmp = (string) ($files['tmp_name'][$i] ?? '');
		$size = (int) ($files['size'][$i] ?? 0);
		$mime = (string) ($files['type'][$i] ?? '');
		if ($original === '' || $tmp === '' || !is_uploaded_file($tmp)) {
			return array('ok' => false, 'message' => 'Invalid attachment upload.');
		}
		if ($size <= 0 || $size > 10 * 1024 * 1024) {
			return array('ok' => false, 'message' => 'Each attachment must be under 10 MB.');
		}
		$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
		if (!purchase_voucher_allowed_mime($mime, $ext)) {
			return array('ok' => false, 'message' => 'Unsupported attachment type. Use PDF, image, Word, or Excel.');
		}
		$stored = 'pv_' . $purchase_voucher_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
		$dest = purchase_voucher_upload_dir() . '/' . $stored;
		if (!@move_uploaded_file($tmp, $dest)) {
			return array('ok' => false, 'message' => 'Could not save attachment file.');
		}
		$orig_esc = mysqli_real_escape_string($conn, $original);
		$stored_esc = mysqli_real_escape_string($conn, $stored);
		$mime_esc = mysqli_real_escape_string($conn, $mime);
		mysqli_query($conn, "INSERT INTO purchase_voucher_attachment
			(purchase_voucher_id, original_name, stored_name, mime_type, file_size, status, created_at, created_by)
			VALUES ('$purchase_voucher_id', '$orig_esc', '$stored_esc', '$mime_esc', '$size', 0, '$now', '$user_id')");
		$saved++;
	}
	return array('ok' => true, 'saved' => $saved);
}

function purchase_voucher_delete_attachment($conn, $attachment_id, $purchase_voucher_id = 0)
{
	purchase_voucher_ensure_schema($conn);
	$attachment_id = (int) $attachment_id;
	$purchase_voucher_id = (int) $purchase_voucher_id;
	if ($attachment_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid attachment.');
	}
	$sql = "SELECT * FROM purchase_voucher_attachment WHERE attachment_id='$attachment_id' AND status=0";
	if ($purchase_voucher_id > 0) {
		$sql .= " AND purchase_voucher_id='$purchase_voucher_id'";
	}
	$sql .= ' LIMIT 1';
	$q = mysqli_query($conn, $sql);
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return array('ok' => false, 'message' => 'Attachment not found.');
	}
	mysqli_query($conn, "UPDATE purchase_voucher_attachment SET status=1 WHERE attachment_id='$attachment_id' LIMIT 1");
	$stored = trim((string) ($row['stored_name'] ?? ''));
	if ($stored !== '') {
		$path = purchase_voucher_upload_dir() . '/' . $stored;
		if (is_file($path)) {
			@unlink($path);
		}
	}
	return array('ok' => true, 'message' => 'Attachment removed.');
}

function purchase_voucher_load_lines($conn, $purchase_voucher_id)
{
	$purchase_voucher_id = (int) $purchase_voucher_id;
	$lines = array();
	if ($purchase_voucher_id <= 0) {
		return $lines;
	}
	$q = mysqli_query($conn, "SELECT * FROM purchase_voucher_line
		WHERE purchase_voucher_id='$purchase_voucher_id'
		ORDER BY line_no ASC, line_id ASC");
	while ($q && ($row = mysqli_fetch_assoc($q))) {
		$category_id = (int) ($row['category_id'] ?? 0);
		$category_label = '';
		if ($category_id > 0) {
			$cq = mysqli_query($conn, "SELECT category_code, category_name FROM expense_category WHERE category_id='$category_id' LIMIT 1");
			if ($cq && ($c = mysqli_fetch_assoc($cq))) {
				$category_label = trim(($c['category_code'] ?? '') . ' - ' . ($c['category_name'] ?? ''), ' -');
			}
		}
		$lines[] = array(
			'line_id' => (int) $row['line_id'],
			'line_no' => (int) ($row['line_no'] ?? 0),
			'category_id' => $category_id,
			'category_label' => $category_label,
			'item_description' => $row['item_description'] ?? '',
			'hsn_sac' => $row['hsn_sac'] ?? '',
			'qty' => purchase_voucher_money($row['qty'] ?? 1),
			'unit' => $row['unit'] ?? '',
			'rate' => purchase_voucher_money($row['rate'] ?? 0),
			'taxable_amount' => purchase_voucher_money($row['taxable_amount'] ?? 0),
			'taxable_amount_fmt' => expense_gcn_format_money($row['taxable_amount'] ?? 0),
			'gst_percent' => purchase_voucher_normalize_gst_percent($row['gst_percent'] ?? 0),
			'gst_amount' => purchase_voucher_money($row['gst_amount'] ?? 0),
			'gst_amount_fmt' => expense_gcn_format_money($row['gst_amount'] ?? 0),
			'line_total' => purchase_voucher_money($row['line_total'] ?? 0),
			'line_total_fmt' => expense_gcn_format_money($row['line_total'] ?? 0),
		);
	}
	return $lines;
}

function purchase_voucher_parse_lines_input($payload)
{
	$raw = $payload['lines_json'] ?? ($payload['lines'] ?? array());
	if (is_string($raw)) {
		$decoded = json_decode($raw, true);
		$raw = is_array($decoded) ? $decoded : array();
	}
	if (!is_array($raw)) {
		return array('ok' => false, 'message' => 'Invalid line items.');
	}
	$prepared = array();
	$line_no = 1;
	foreach ($raw as $line) {
		if (!is_array($line)) {
			continue;
		}
		$category_id = (int) ($line['category_id'] ?? 0);
		$desc = trim((string) ($line['item_description'] ?? ''));
		if ($category_id <= 0) {
			return array('ok' => false, 'message' => 'Please select expense type for each line.');
		}
		if ($desc === '') {
			return array('ok' => false, 'message' => 'Please enter item / description for each line.');
		}
		$qty = purchase_voucher_money($line['qty'] ?? 1);
		if ($qty <= 0) {
			$qty = 1;
		}
		$rate = purchase_voucher_money($line['rate'] ?? 0);
		$taxable = purchase_voucher_money($line['taxable_amount'] ?? 0);
		if ($taxable <= 0 && $qty > 0 && $rate > 0) {
			$taxable = purchase_voucher_money($qty * $rate);
		}
		if ($taxable <= 0) {
			return array('ok' => false, 'message' => 'Please enter a valid taxable amount on line ' . $line_no . '.');
		}
		$gst_percent = purchase_voucher_normalize_gst_percent($line['gst_percent'] ?? 0);
		$gst_amount = purchase_voucher_money($line['gst_amount'] ?? ($taxable * $gst_percent / 100));
		$line_total = purchase_voucher_money($taxable + $gst_amount);
		$hsn = trim((string) ($line['hsn_sac'] ?? ''));
		if (strlen($hsn) > 20) {
			$hsn = substr($hsn, 0, 20);
		}
		$unit = trim((string) ($line['unit'] ?? ''));
		if (strlen($unit) > 20) {
			$unit = substr($unit, 0, 20);
		}
		if (strlen($desc) > 500) {
			$desc = substr($desc, 0, 500);
		}
		$prepared[] = array(
			'line_no' => $line_no,
			'category_id' => $category_id,
			'item_description' => $desc,
			'hsn_sac' => $hsn,
			'qty' => $qty,
			'unit' => $unit,
			'rate' => $rate,
			'taxable_amount' => $taxable,
			'gst_percent' => $gst_percent,
			'gst_amount' => $gst_amount,
			'line_total' => $line_total,
		);
		$line_no++;
	}
	if (empty($prepared)) {
		return array('ok' => false, 'message' => 'Please add at least one purchase line.');
	}
	return array('ok' => true, 'lines' => $prepared);
}

function purchase_voucher_calc_header_totals(array $lines, $tds_percent, $other_deduction)
{
	$subtotal = 0;
	$gst_total = 0;
	$grand_total = 0;
	foreach ($lines as $line) {
		$subtotal += purchase_voucher_money($line['taxable_amount'] ?? 0);
		$gst_total += purchase_voucher_money($line['gst_amount'] ?? 0);
		$grand_total += purchase_voucher_money($line['line_total'] ?? 0);
	}
	$subtotal = purchase_voucher_money($subtotal);
	$gst_total = purchase_voucher_money($gst_total);
	$grand_total = purchase_voucher_money($grand_total);
	$tds_percent = max(0, purchase_voucher_money($tds_percent));
	$tds_amount = $tds_percent > 0 ? purchase_voucher_money($subtotal * $tds_percent / 100) : 0;
	$other_deduction = max(0, purchase_voucher_money($other_deduction));
	$net_payable = purchase_voucher_money($grand_total - $tds_amount - $other_deduction);
	if ($net_payable < 0) {
		return array('ok' => false, 'message' => 'Net payable cannot be negative.');
	}
	return array(
		'ok' => true,
		'subtotal' => $subtotal,
		'gst_total' => $gst_total,
		'grand_total' => $grand_total,
		'tds_percent' => $tds_percent,
		'tds_amount' => $tds_amount,
		'other_deduction' => $other_deduction,
		'net_payable' => $net_payable,
	);
}

function purchase_voucher_format_record($conn, $row)
{
	purchase_voucher_ensure_schema($conn);
	$pv_id = (int) ($row['purchase_voucher_id'] ?? 0);
	$vendor_id = (int) ($row['vendor_id'] ?? 0);
	$vendor_label = '';
	if ($vendor_id > 0) {
		$vq = mysqli_query($conn, "SELECT vendor_code, vendor_name FROM vendor_master WHERE vendor_id='$vendor_id' LIMIT 1");
		if ($vq && ($v = mysqli_fetch_assoc($vq))) {
			$vendor_label = trim(($v['vendor_code'] ?? '') . ' - ' . ($v['vendor_name'] ?? ''), ' -');
		}
	}
	$pm = strtoupper(trim((string) ($row['payment_mode'] ?? '')));
	$modes = ew_company_bank_payment_modes();
	return array(
		'purchase_voucher_id' => $pv_id,
		'voucher_no' => $row['voucher_no'] ?? '',
		'voucher_date' => $row['voucher_date'] ?? '',
		'vendor_id' => $vendor_id,
		'vendor_label' => $vendor_label,
		'supplier_invoice_no' => $row['supplier_invoice_no'] ?? '',
		'supplier_invoice_date' => $row['supplier_invoice_date'] ?? '',
		'invoice_received_date' => $row['invoice_received_date'] ?? '',
		'narration' => $row['narration'] ?? '',
		'subtotal' => expense_gcn_format_money($row['subtotal'] ?? 0),
		'subtotal_raw' => purchase_voucher_money($row['subtotal'] ?? 0),
		'gst_total' => expense_gcn_format_money($row['gst_total'] ?? 0),
		'gst_total_raw' => purchase_voucher_money($row['gst_total'] ?? 0),
		'grand_total' => expense_gcn_format_money($row['grand_total'] ?? 0),
		'grand_total_raw' => purchase_voucher_money($row['grand_total'] ?? 0),
		'tds_percent' => purchase_voucher_money($row['tds_percent'] ?? 0),
		'tds_amount' => expense_gcn_format_money($row['tds_amount'] ?? 0),
		'tds_amount_raw' => purchase_voucher_money($row['tds_amount'] ?? 0),
		'other_deduction' => expense_gcn_format_money($row['other_deduction'] ?? 0),
		'other_deduction_raw' => purchase_voucher_money($row['other_deduction'] ?? 0),
		'net_payable' => expense_gcn_format_money($row['net_payable'] ?? 0),
		'net_payable_raw' => purchase_voucher_money($row['net_payable'] ?? 0),
		'payment_mode' => $pm,
		'payment_mode_label' => $modes[$pm] ?? ($pm !== '' ? $pm : '—'),
		'company_bank_id' => (int) ($row['company_bank_id'] ?? 0),
		'payment_bank_name' => $row['payment_bank_name'] ?? '',
		'payment_ifsc' => strtoupper($row['payment_ifsc'] ?? ''),
		'payment_bank_branch' => $row['payment_bank_branch'] ?? '',
		'lines' => purchase_voucher_load_lines($conn, $pv_id),
		'attachments' => purchase_voucher_attachments($conn, $pv_id),
		'updated_at' => $row['updated_at'] ?? ($row['created_at'] ?? ''),
	);
}

function purchase_voucher_load($conn, $purchase_voucher_id)
{
	purchase_voucher_ensure_schema($conn);
	$purchase_voucher_id = (int) $purchase_voucher_id;
	if ($purchase_voucher_id <= 0) {
		return null;
	}
	$q = mysqli_query($conn, "SELECT * FROM purchase_voucher WHERE purchase_voucher_id='$purchase_voucher_id' AND status=0 LIMIT 1");
	if (!$q || !($row = mysqli_fetch_assoc($q))) {
		return null;
	}
	return purchase_voucher_format_record($conn, $row);
}

function purchase_voucher_view_data($conn, $purchase_voucher_id)
{
	return purchase_voucher_load($conn, $purchase_voucher_id);
}

function purchase_voucher_fetch_list($conn)
{
	purchase_voucher_ensure_schema($conn);
	$rows = array();
	$q = mysqli_query($conn, "SELECT * FROM purchase_voucher WHERE status=0 ORDER BY purchase_voucher_id DESC");
	while ($q && ($row = mysqli_fetch_assoc($q))) {
		$rows[] = purchase_voucher_format_record($conn, $row);
	}
	return $rows;
}

function purchase_voucher_sync_lines($conn, $purchase_voucher_id, array $lines)
{
	$purchase_voucher_id = (int) $purchase_voucher_id;
	mysqli_query($conn, "DELETE FROM purchase_voucher_line WHERE purchase_voucher_id='$purchase_voucher_id'");
	foreach ($lines as $line) {
		$line_no = (int) ($line['line_no'] ?? 0);
		$cat = (int) ($line['category_id'] ?? 0);
		$desc_esc = mysqli_real_escape_string($conn, $line['item_description'] ?? '');
		$hsn_esc = mysqli_real_escape_string($conn, $line['hsn_sac'] ?? '');
		$unit_esc = mysqli_real_escape_string($conn, $line['unit'] ?? '');
		$qty = purchase_voucher_money($line['qty'] ?? 1);
		$rate = purchase_voucher_money($line['rate'] ?? 0);
		$taxable = purchase_voucher_money($line['taxable_amount'] ?? 0);
		$gst_percent = purchase_voucher_money($line['gst_percent'] ?? 0);
		$gst_amount = purchase_voucher_money($line['gst_amount'] ?? 0);
		$line_total = purchase_voucher_money($line['line_total'] ?? 0);
		mysqli_query($conn, "INSERT INTO purchase_voucher_line
			(purchase_voucher_id, line_no, category_id, item_description, hsn_sac, qty, unit, rate,
			taxable_amount, gst_percent, gst_amount, line_total)
			VALUES ('$purchase_voucher_id', '$line_no', '$cat', '$desc_esc', '$hsn_esc', '$qty', '$unit_esc', '$rate',
			'$taxable', '$gst_percent', '$gst_amount', '$line_total')");
	}
}

function purchase_voucher_save($conn, $payload, $user_id, $files = null)
{
	purchase_voucher_ensure_schema($conn);

	$existing_id = (int) ($payload['purchase_voucher_id'] ?? 0);
	$voucher_date = trim((string) ($payload['voucher_date'] ?? ''));
	$vendor_id = (int) ($payload['vendor_id'] ?? 0);
	$supplier_invoice_no = trim((string) ($payload['supplier_invoice_no'] ?? ''));
	$supplier_invoice_date = trim((string) ($payload['supplier_invoice_date'] ?? ''));
	$invoice_received_date = trim((string) ($payload['invoice_received_date'] ?? ''));
	$narration = trim((string) ($payload['narration'] ?? ''));
	$tds_percent = purchase_voucher_money($payload['tds_percent'] ?? 0);
	$other_deduction = purchase_voucher_money($payload['other_deduction'] ?? 0);
	$payment_mode = strtoupper(trim((string) ($payload['payment_mode'] ?? '')));
	$company_bank_id = (int) ($payload['company_bank_id'] ?? 0);
	$payment_bank_name = trim((string) ($payload['payment_bank_name'] ?? ''));
	$payment_ifsc = strtoupper(trim((string) ($payload['payment_ifsc'] ?? '')));
	$payment_bank_branch = trim((string) ($payload['payment_bank_branch'] ?? ''));

	if ($voucher_date === '') {
		return array('ok' => false, 'message' => 'Please enter voucher date.');
	}
	if ($vendor_id <= 0) {
		return array('ok' => false, 'message' => 'Please select vendor / supplier.');
	}
	if ($supplier_invoice_no === '') {
		return array('ok' => false, 'message' => 'Please enter supplier invoice no.');
	}
	if ($supplier_invoice_date === '') {
		return array('ok' => false, 'message' => 'Please enter supplier invoice date.');
	}

	$parsed = purchase_voucher_parse_lines_input($payload);
	if (empty($parsed['ok'])) {
		return array('ok' => false, 'message' => $parsed['message'] ?? 'Please complete line items.');
	}
	$lines = $parsed['lines'];
	$totals = purchase_voucher_calc_header_totals($lines, $tds_percent, $other_deduction);
	if (empty($totals['ok'])) {
		return array('ok' => false, 'message' => $totals['message'] ?? 'Invalid amounts.');
	}

	if ($payment_mode === '') {
		return array('ok' => false, 'message' => 'Please select payment mode.');
	}
	$modes = ew_company_bank_payment_modes();
	if (!isset($modes[$payment_mode])) {
		return array('ok' => false, 'message' => 'Invalid payment mode selected.');
	}
	if (ew_company_bank_mode_requires_account($payment_mode)) {
		if ($company_bank_id <= 0) {
			return array('ok' => false, 'message' => 'Please select company bank account for this payment mode.');
		}
		$bank = ew_company_bank_get($conn, $company_bank_id);
		if (!$bank || (int) ($bank['status'] ?? 0) !== 0) {
			return array('ok' => false, 'message' => 'Selected company bank account is invalid.');
		}
		$payment_bank_name = $bank['bank_name'] ?? $payment_bank_name;
		$payment_ifsc = strtoupper($bank['ifsc'] ?? $payment_ifsc);
		$payment_bank_branch = $bank['bank_branch'] ?? $payment_bank_branch;
	} elseif ($company_bank_id > 0) {
		$bank = ew_company_bank_get($conn, $company_bank_id);
		if ($bank && (int) ($bank['status'] ?? 0) === 0) {
			$payment_bank_name = $bank['bank_name'] ?? $payment_bank_name;
			$payment_ifsc = strtoupper($bank['ifsc'] ?? $payment_ifsc);
			$payment_bank_branch = $bank['bank_branch'] ?? $payment_bank_branch;
		}
	}

	$user_id = (int) $user_id;
	$now = date('d-m-Y');
	if (strlen($narration) > 1000) {
		$narration = substr($narration, 0, 1000);
	}

	$voucher_date_esc = mysqli_real_escape_string($conn, $voucher_date);
	$invoice_no_esc = mysqli_real_escape_string($conn, $supplier_invoice_no);
	$invoice_date_esc = mysqli_real_escape_string($conn, $supplier_invoice_date);
	$received_esc = mysqli_real_escape_string($conn, $invoice_received_date);
	$narration_esc = mysqli_real_escape_string($conn, $narration);
	$payment_mode_esc = mysqli_real_escape_string($conn, $payment_mode);
	$payment_bank_name_esc = mysqli_real_escape_string($conn, $payment_bank_name);
	$payment_ifsc_esc = mysqli_real_escape_string($conn, $payment_ifsc);
	$payment_bank_branch_esc = mysqli_real_escape_string($conn, $payment_bank_branch);

	$subtotal = $totals['subtotal'];
	$gst_total = $totals['gst_total'];
	$grand_total = $totals['grand_total'];
	$tds_percent = $totals['tds_percent'];
	$tds_amount = $totals['tds_amount'];
	$other_deduction = $totals['other_deduction'];
	$net_payable = $totals['net_payable'];

	if ($existing_id > 0) {
		$chk = mysqli_query($conn, "SELECT purchase_voucher_id, voucher_no FROM purchase_voucher WHERE purchase_voucher_id='$existing_id' AND status=0 LIMIT 1");
		if (!$chk || !($existing = mysqli_fetch_assoc($chk))) {
			return array('ok' => false, 'message' => 'Purchase voucher not found.');
		}
		$pv_id = $existing_id;
		mysqli_query($conn, "UPDATE purchase_voucher SET
			voucher_date='$voucher_date_esc',
			vendor_id='$vendor_id',
			supplier_invoice_no='$invoice_no_esc',
			supplier_invoice_date='$invoice_date_esc',
			invoice_received_date='$received_esc',
			narration='$narration_esc',
			subtotal='$subtotal',
			gst_total='$gst_total',
			grand_total='$grand_total',
			tds_percent='$tds_percent',
			tds_amount='$tds_amount',
			other_deduction='$other_deduction',
			net_payable='$net_payable',
			payment_mode='$payment_mode_esc',
			company_bank_id='$company_bank_id',
			payment_bank_name='$payment_bank_name_esc',
			payment_ifsc='$payment_ifsc_esc',
			payment_bank_branch='$payment_bank_branch_esc',
			updated_at='$now',
			updated_by='$user_id'
			WHERE purchase_voucher_id='$pv_id'");
		$voucher_no = $existing['voucher_no'] ?? purchase_voucher_next_no($conn, $pv_id);
	} else {
		mysqli_query($conn, "INSERT INTO purchase_voucher
			(voucher_no, voucher_date, vendor_id, supplier_invoice_no, supplier_invoice_date, invoice_received_date,
			narration, subtotal, gst_total, grand_total, tds_percent, tds_amount, other_deduction, net_payable,
			payment_mode, company_bank_id, payment_bank_name, payment_ifsc, payment_bank_branch,
			status, created_at, created_by, updated_at, updated_by)
			VALUES ('', '$voucher_date_esc', '$vendor_id', '$invoice_no_esc', '$invoice_date_esc', '$received_esc',
			'$narration_esc', '$subtotal', '$gst_total', '$grand_total', '$tds_percent', '$tds_amount', '$other_deduction', '$net_payable',
			'$payment_mode_esc', '$company_bank_id', '$payment_bank_name_esc', '$payment_ifsc_esc', '$payment_bank_branch_esc',
			0, '$now', '$user_id', '$now', '$user_id')");
		$pv_id = (int) mysqli_insert_id($conn);
		if ($pv_id <= 0) {
			return array('ok' => false, 'message' => 'Could not save purchase voucher.');
		}
		$voucher_no = purchase_voucher_next_no($conn, $pv_id);
		$voucher_no_esc = mysqli_real_escape_string($conn, $voucher_no);
		mysqli_query($conn, "UPDATE purchase_voucher SET voucher_no='$voucher_no_esc' WHERE purchase_voucher_id='$pv_id'");
	}

	purchase_voucher_sync_lines($conn, $pv_id, $lines);

	if ($files !== null) {
		$upload = purchase_voucher_store_uploads($conn, $pv_id, $files, $user_id);
		if (empty($upload['ok'])) {
			return array(
				'ok' => false,
				'message' => $upload['message'] ?? 'Could not save attachments.',
				'purchase_voucher_id' => $pv_id,
				'voucher_no' => $voucher_no,
			);
		}
	}

	$remove_ids = $payload['remove_attachment_ids'] ?? array();
	if (is_string($remove_ids) && $remove_ids !== '') {
		$remove_ids = preg_split('/\s*,\s*/', $remove_ids);
	}
	if (is_array($remove_ids)) {
		foreach ($remove_ids as $rid) {
			$rid = (int) $rid;
			if ($rid > 0) {
				purchase_voucher_delete_attachment($conn, $rid, $pv_id);
			}
		}
	}

	return array(
		'ok' => true,
		'message' => 'Purchase voucher saved successfully.',
		'purchase_voucher_id' => $pv_id,
		'voucher_no' => $voucher_no,
		'record' => purchase_voucher_load($conn, $pv_id),
	);
}

function purchase_voucher_delete($conn, $purchase_voucher_id)
{
	purchase_voucher_ensure_schema($conn);
	$purchase_voucher_id = (int) $purchase_voucher_id;
	if ($purchase_voucher_id <= 0) {
		return array('ok' => false, 'message' => 'Invalid purchase voucher.');
	}
	$chk = mysqli_query($conn, "SELECT purchase_voucher_id FROM purchase_voucher WHERE purchase_voucher_id='$purchase_voucher_id' AND status=0 LIMIT 1");
	if (!$chk || mysqli_num_rows($chk) === 0) {
		return array('ok' => false, 'message' => 'Purchase voucher not found.');
	}
	$now = date('d-m-Y');
	mysqli_query($conn, "UPDATE purchase_voucher SET status=1, updated_at='$now' WHERE purchase_voucher_id='$purchase_voucher_id' LIMIT 1");
	mysqli_query($conn, "UPDATE purchase_voucher_attachment SET status=1 WHERE purchase_voucher_id='$purchase_voucher_id' AND status=0");
	return array('ok' => true, 'message' => 'Purchase voucher deleted successfully.');
}