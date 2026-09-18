<?php
require_once 'include/connect.php';
require_once 'include/function.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'AD') {
	header('HTTP/1.1 403 Forbidden');
	echo 'Admin login required.';
	exit;
}

function ew_xlsx_cell_ref($ref)
{
	$col = '';
	$row = '';
	for ($i = 0; $i < strlen($ref); $i++) {
		$ch = $ref[$i];
		if (ctype_alpha($ch)) {
			$col .= $ch;
		} else {
			$row .= $ch;
		}
	}
	$n = 0;
	$col = strtoupper($col);
	for ($i = 0; $i < strlen($col); $i++) {
		$n = $n * 26 + (ord($col[$i]) - 64);
	}
	return array((int) $n, (int) $row);
}

function ew_xlsx_first_client($path)
{
	$zip = new ZipArchive();
	if ($zip->open($path) !== true) {
		return null;
	}
	$ssXml = $zip->getFromName('xl/sharedStrings.xml');
	$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
	$zip->close();
	if ($ssXml === false || $sheetXml === false) {
		return null;
	}

	$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
	$ss = simplexml_load_string($ssXml);
	$ss->registerXPathNamespace('m', $ns);
	$strings = array();
	foreach ($ss->xpath('//m:si') as $si) {
		$buf = '';
		foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
			$buf .= (string) $t;
		}
		$strings[] = $buf;
	}

	$sheet = simplexml_load_string($sheetXml);
	$sheet->registerXPathNamespace('m', $ns);
	$rows = array();
	foreach ($sheet->xpath('//m:c') as $c) {
		$ref = (string) $c['r'];
		if ($ref === '') {
			continue;
		}
		list($col, $row) = ew_xlsx_cell_ref($ref);
		$t = (string) $c['t'];
		$vNodes = $c->xpath('./*[local-name()="v"]');
		$v = ($vNodes && isset($vNodes[0])) ? (string) $vNodes[0] : '';
		if ($t === 's' && $v !== '') {
			$val = isset($strings[(int) $v]) ? $strings[(int) $v] : '';
		} else {
			$val = $v;
		}
		$rows[$row][$col] = $val;
	}
	ksort($rows);
	foreach ($rows as $rowNum => $cols) {
		if ($rowNum < 4) {
			continue;
		}
		$name = trim($cols[2] ?? '');
		if ($name === '' || strcasecmp($name, 'Name & Address') === 0) {
			continue;
		}
		return array(
			'name' => $name,
			'address' => trim($cols[3] ?? ''),
			'pan' => trim($cols[4] ?? ''),
			'gst' => trim($cols[5] ?? ''),
			'state_name' => trim($cols[6] ?? ''),
			'pincode' => preg_replace('/\D/', '', trim($cols[7] ?? '')),
			'contact_person' => trim($cols[8] ?? ''),
			'phone' => trim($cols[9] ?? ''),
			'mobile' => trim($cols[10] ?? ''),
			'email' => trim($cols[11] ?? ''),
			'email_cc' => trim($cols[12] ?? ''),
		);
	}
	return null;
}

function ew_billing_code_from_name($name)
{
	$letters = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
	$code = substr($letters, 0, 4);
	return str_pad($code, 4, 'X');
}

$path = __DIR__ . '/Sundry Debtors Address Book.xls';
$row = ew_xlsx_first_client($path);
if (!$row) {
	echo 'Could not read the first client from the Excel file.';
	exit;
}

$state_name = mysqli_real_escape_string($conn, $row['state_name']);
$state_res = mysqli_query($conn, "SELECT state_id FROM state WHERE status=0 AND state_name='" . $state_name . "' LIMIT 1");
$state = $state_res ? mysqli_fetch_assoc($state_res) : null;

$city_id = 0;
$city_name = '';
if (preg_match('/CHENNAI/i', $row['address'])) {
	$city_name = 'Chennai';
}
if ($city_name !== '' && $state) {
	$city_res = mysqli_query($conn, "SELECT city_id, city_name FROM city WHERE status=0 AND state='" . (int) $state['state_id'] . "' AND city_name='" . mysqli_real_escape_string($conn, $city_name) . "' LIMIT 1");
	$city = $city_res ? mysqli_fetch_assoc($city_res) : null;
	if ($city) {
		$city_id = (int) $city['city_id'];
		$city_name = $city['city_name'];
	}
}

$phone = $row['mobile'] !== '' ? $row['mobile'] : $row['phone'];
$contact = $row['contact_person'] !== '' ? $row['contact_person'] : $row['name'];
$code = ew_billing_code_from_name($row['name']);
$pin = $row['pincode'] !== '' ? (int) $row['pincode'] : 0;
$clean_addr = ew_clean_party_address($row['address'], $pin, $city_name, $row['state_name']);

$exists = mysqli_query($conn, "SELECT client_id FROM client WHERE billing_code='" . mysqli_real_escape_string($conn, $code) . "' LIMIT 1");
if ($exists && mysqli_num_rows($exists) > 0) {
	$ex = mysqli_fetch_assoc($exists);
	mysqli_query($conn, "UPDATE client SET address1='" . mysqli_real_escape_string($conn, $clean_addr) . "' WHERE client_id='" . (int) $ex['client_id'] . "'");
	echo 'Already inserted. client_id=' . (int) $ex['client_id'] . ' billing_code=' . htmlspecialchars($code) . '<br>Address cleaned: ' . htmlspecialchars($clean_addr);
	exit;
}

if (!$state || $city_id < 1) {
	echo 'Skipped insert: could not match state/city. State=' . htmlspecialchars($row['state_name']) . ' city guess=' . htmlspecialchars($city_name);
	exit;
}

$now = date('d-m-Y H:i:s');
$created_by = (int) $_SESSION['user_id'];
$sql = "INSERT INTO client (
	client_company_name, contact_person, address1, address2, state, city, pincode, billing_code,
	email, email1, contact_no, contact_no1, gst_no, pan_no, multiple_branches, automation,
	created_at, created_by, status, approve_status
) VALUES (
	'" . mysqli_real_escape_string($conn, $row['name']) . "',
	'" . mysqli_real_escape_string($conn, $contact) . "',
	'" . mysqli_real_escape_string($conn, $clean_addr) . "',
	'',
	'" . (int) $state['state_id'] . "',
	'" . $city_id . "',
	'" . $pin . "',
	'" . mysqli_real_escape_string($conn, $code) . "',
	'" . mysqli_real_escape_string($conn, $row['email']) . "',
	'" . mysqli_real_escape_string($conn, $row['email_cc']) . "',
	'" . mysqli_real_escape_string($conn, $phone) . "',
	'',
	'" . mysqli_real_escape_string($conn, $row['gst']) . "',
	'" . mysqli_real_escape_string($conn, $row['pan']) . "',
	'0','0',
	'" . mysqli_real_escape_string($conn, $now) . "',
	'" . $created_by . "',
	'0','0'
)";

if (!mysqli_query($conn, $sql)) {
	echo 'Insert failed: ' . htmlspecialchars(mysqli_error($conn));
	exit;
}

$id = (int) mysqli_insert_id($conn);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><title>First client insert</title></head>
<body style="font-family:sans-serif;padding:24px">
<h2>Inserted 1 test client</h2>
<table border="1" cellpadding="8" cellspacing="0">
<tr><th>client_id</th><td><?php echo $id; ?></td></tr>
<tr><th>Name</th><td><?php echo htmlspecialchars($row['name']); ?></td></tr>
<tr><th>Client code</th><td><?php echo htmlspecialchars($code); ?></td></tr>
<tr><th>Address</th><td><?php echo htmlspecialchars($row['address']); ?></td></tr>
<tr><th>State</th><td><?php echo htmlspecialchars($row['state_name']); ?> (id <?php echo (int) $state['state_id']; ?>)</td></tr>
<tr><th>City</th><td><?php echo htmlspecialchars($city_name); ?> (id <?php echo $city_id; ?>)</td></tr>
<tr><th>Pincode</th><td><?php echo $pin; ?></td></tr>
<tr><th>Phone</th><td><?php echo htmlspecialchars($phone); ?></td></tr>
<tr><th>Email</th><td><?php echo htmlspecialchars($row['email']); ?></td></tr>
<tr><th>GST</th><td><?php echo htmlspecialchars($row['gst']); ?></td></tr>
</table>
<p><a href="client_list.php">Open Client List</a></p>
</body>
</html>
