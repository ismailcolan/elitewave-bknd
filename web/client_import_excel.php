<?php
/**
 * One-shot CLI import of Sundry Debtors Address Book.xls
 * Cleans Address (strip matching pincode / trailing city / trailing state) on insert.
 */
if (php_sapi_name() !== 'cli') {
	fwrite(STDERR, "CLI only.\n");
	exit(1);
}

require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';
error_reporting(E_ALL);
ini_set('display_errors', '1');
echo "Import starting\n";
flush();

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

function ew_xlsx_all_clients($path)
{
	$zip = new ZipArchive();
	if ($zip->open($path) !== true) {
		return array();
	}
	$ssXml = $zip->getFromName('xl/sharedStrings.xml');
	$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
	$zip->close();
	if ($ssXml === false || $sheetXml === false) {
		return array();
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
	$out = array();
	foreach ($rows as $rowNum => $cols) {
		if ($rowNum < 4) {
			continue;
		}
		$name = trim($cols[2] ?? '');
		if ($name === '' || strcasecmp($name, 'Name & Address') === 0) {
			continue;
		}
		$out[] = array(
			'name' => $name,
			'address' => trim($cols[3] ?? ''),
			'pan' => trim($cols[4] ?? ''),
			'gst' => strtoupper(preg_replace('/\s+/', '', trim($cols[5] ?? ''))),
			'state_name' => trim($cols[6] ?? ''),
			'pincode' => preg_replace('/\D/', '', trim($cols[7] ?? '')),
			'contact_person' => trim($cols[8] ?? ''),
			'phone' => trim($cols[9] ?? ''),
			'mobile' => trim($cols[10] ?? ''),
			'email' => trim($cols[11] ?? ''),
			'email_cc' => trim($cols[12] ?? ''),
		);
	}
	return $out;
}

function ew_import_billing_code($name, array &$used)
{
	$letters = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
	$base = str_pad(substr($letters, 0, 4), 4, 'X');
	if (!isset($used[$base])) {
		return $base;
	}
	for ($i = 1; $i <= 9; $i++) {
		$c = substr($base, 0, 3) . $i;
		if (!isset($used[$c])) {
			return $c;
		}
	}
	for ($i = 10; $i <= 99; $i++) {
		$c = substr($base, 0, 2) . $i;
		if (!isset($used[$c])) {
			return $c;
		}
	}
	$n = 0;
	do {
		$n++;
		$c = 'C' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
	} while (isset($used[$c]));
	return $c;
}

function ew_import_phone($mobile, $phone)
{
	$raw = $mobile !== '' ? $mobile : $phone;
	$d = preg_replace('/\D+/', '', $raw);
	if (strlen($d) >= 12 && substr($d, 0, 2) === '91') {
		$d = substr($d, -10);
	}
	if (strlen($d) > 15) {
		$d = substr($d, 0, 15);
	}
	return $d;
}

function ew_import_email($email)
{
	$email = trim(str_replace(array(';', ' '), array(',', ''), $email));
	$parts = array_filter(array_map('trim', explode(',', $email)));
	$first = $parts ? reset($parts) : '';
	return substr($first, 0, 100);
}

function ew_norm_state_name($name)
{
	$n = strtolower(trim($name));
	$n = str_replace(array('&', '  '), array('and', ' '), $n);
	$map = array(
		'delhi' => 'New Delhi',
		'new delhi' => 'New Delhi',
		'tamil nadu' => 'Tamil Nadu',
		'uttar pradesh' => 'Uttar Pradesh',
		'orissa' => 'Orissa',
		'odisha' => 'Orissa',
		'dadra and nagar haveli and daman and diu' => 'Dadra&Nagar Haveli',
		'dadra and nagar haveli' => 'Dadra&Nagar Haveli',
		'pondicherry' => 'Puducherry',
		'chhattisgarh' => 'Chattisgarh',
		'chattisgarh' => 'Chattisgarh',
	);
	return isset($map[$n]) ? $map[$n] : trim($name);
}

function ew_gst_state_name($gst)
{
	$code = substr($gst, 0, 2);
	$map = array(
		'01' => 'Jammu&Kashmir',
		'02' => 'Himachal Pradesh',
		'03' => 'Punjab',
		'05' => 'Uttarakhand',
		'06' => 'Haryana',
		'07' => 'New Delhi',
		'08' => 'Rajasthan',
		'09' => 'Uttar Pradesh',
		'10' => 'Bihar2',
		'19' => 'West Bengal',
		'20' => 'Jharkhand',
		'21' => 'Orissa',
		'22' => 'Chattisgarh',
		'23' => 'Madhya Pradesh',
		'24' => 'Gujarat',
		'26' => 'Dadra&Nagar Haveli',
		'27' => 'Maharashtra',
		'29' => 'Karnataka',
		'32' => 'Kerala',
		'33' => 'Tamil Nadu',
		'34' => 'Puducherry',
		'36' => 'Telangana',
		'37' => 'Andhra Pradesh',
	);
	return isset($map[$code]) ? $map[$code] : '';
}

$path = __DIR__ . '/Sundry Debtors Address Book.xls';
$rows = ew_xlsx_all_clients($path);
if (!$rows) {
	fwrite(STDERR, "Could not read Excel.\n");
	exit(1);
}

$states_by_name = array();
$sr = mysqli_query($conn, 'SELECT state_id, state_name FROM state WHERE status=0');
while ($s = mysqli_fetch_assoc($sr)) {
	$states_by_name[strtolower(trim($s['state_name']))] = (int) $s['state_id'];
}

$cities_by_state = array();
$cr = mysqli_query($conn, 'SELECT city_id, city_name, state FROM city WHERE status=0');
while ($c = mysqli_fetch_assoc($cr)) {
	$sid = (int) $c['state'];
	if (!isset($cities_by_state[$sid])) {
		$cities_by_state[$sid] = array();
	}
	$cities_by_state[$sid][] = $c;
}

$used_codes = array();
$ex = mysqli_query($conn, 'SELECT billing_code FROM client');
while ($e = mysqli_fetch_assoc($ex)) {
	$used_codes[strtoupper(trim($e['billing_code']))] = true;
}

$existing_names = array();
$nr = mysqli_query($conn, 'SELECT client_id, client_company_name, gst_no FROM client');
while ($n = mysqli_fetch_assoc($nr)) {
	$existing_names[strtoupper(trim($n['client_company_name']))] = (int) $n['client_id'];
}

$gst_parent = array();
$gr = mysqli_query($conn, "SELECT client_id, gst_no FROM client WHERE gst_no IS NOT NULL AND gst_no != ''");
while ($g = mysqli_fetch_assoc($gr)) {
	$key = strtoupper(preg_replace('/\s+/', '', $g['gst_no']));
	if ($key !== '' && !isset($gst_parent[$key])) {
		$gst_parent[$key] = (int) $g['client_id'];
	}
}

$cb = mysqli_query($conn, 'SELECT created_by FROM client ORDER BY client_id ASC LIMIT 1');
$created_by = 1;
if ($cb && ($cbr = mysqli_fetch_assoc($cb)) && (int) $cbr['created_by'] > 0) {
	$created_by = (int) $cbr['created_by'];
}
$now = date('d-m-Y H:i:s');

function ew_match_state_id($name, $gst, $states_by_name)
{
	$try = array();
	if ($name !== '') {
		$try[] = ew_norm_state_name($name);
		$try[] = $name;
	}
	$from_gst = ew_gst_state_name($gst);
	if ($from_gst !== '') {
		$try[] = $from_gst;
	}
	foreach ($try as $t) {
		$key = strtolower(trim($t));
		if (isset($states_by_name[$key])) {
			return $states_by_name[$key];
		}
	}
	return 0;
}

function ew_match_or_create_city($conn, $address, $state_id, &$cities_by_state, $created_by, $now)
{
	$list = isset($cities_by_state[$state_id]) ? $cities_by_state[$state_id] : array();
	usort($list, function ($a, $b) {
		return strlen($b['city_name']) - strlen($a['city_name']);
	});
	$hay = ' ' . $address . ' ';
	foreach ($list as $c) {
		$nm = trim($c['city_name']);
		if ($nm === '' || strlen($nm) < 3) {
			continue;
		}
		if (preg_match('/\b' . preg_quote($nm, '/') . '\b/iu', $hay)) {
			return array((int) $c['city_id'], $nm);
		}
	}

	$aliases = array(
		'GREATER NOIDA' => 'Noida',
		'GAUTAM BUDH NAGAR' => 'Noida',
		'GAUTHAMBUDDHA NAGAR' => 'Noida',
		'GURGAON' => 'Gurgaon',
		'GURUGRAM' => 'Gurgaon',
		'BANGALORE' => 'Banglore',
		'BENGALURU' => 'Banglore',
		'BOMBAY' => 'Mumbai',
		'CALCUTTA' => 'Kolkata',
		'MADRAS' => 'Chennai',
		'NEW DELHI' => 'New Delhi',
		'DELHI' => 'New Delhi',
	);
	foreach ($aliases as $needle => $canon) {
		if (preg_match('/\b' . preg_quote($needle, '/') . '\b/iu', $hay)) {
			foreach ($list as $c) {
				if (strcasecmp(trim($c['city_name']), $canon) === 0) {
					return array((int) $c['city_id'], trim($c['city_name']));
				}
			}
		}
	}

	$guess = '';
	if (preg_match('/,\s*([A-Za-z][A-Za-z .]{2,40}?)\s*[-,]?\s*\d{6}/', $address, $m)) {
		$guess = trim($m[1], " \t-");
	} elseif (preg_match('/([A-Za-z][A-Za-z .]{2,40})\s*-\s*\d{6}/', $address, $m)) {
		$guess = trim($m[1]);
	}
	$skip = array('SECTOR', 'PLOT', 'STREET', 'ROAD', 'FLOOR', 'OFFICE', 'PHASE', 'BLOCK', 'NA');
	if ($guess !== '' && !in_array(strtoupper($guess), $skip, true)) {
		foreach ($list as $c) {
			if (strcasecmp(trim($c['city_name']), $guess) === 0) {
				return array((int) $c['city_id'], trim($c['city_name']));
			}
		}
	}

	$prefer = array('Chennai', 'Noida', 'New Delhi', 'Banglore', 'Mumbai', 'Kolkata', 'Pune', 'Hyderabad');
	foreach ($prefer as $p) {
		foreach ($list as $c) {
			if (strcasecmp(trim($c['city_name']), $p) === 0) {
				return array((int) $c['city_id'], trim($c['city_name']));
			}
		}
	}
	if ($list) {
		return array((int) $list[0]['city_id'], trim($list[0]['city_name']));
	}
	return array(0, '');
}

$inserted = 0;
$branches = 0;
$skipped = 0;
$failed = 0;

foreach ($rows as $row) {
	$name_key = strtoupper(trim($row['name']));
	if (isset($existing_names[$name_key])) {
		$skipped++;
		continue;
	}

	$state_id = ew_match_state_id($row['state_name'], $row['gst'], $states_by_name);
	if ($state_id < 1) {
		$failed++;
		fwrite(STDERR, 'No state: ' . $row['name'] . ' [' . $row['state_name'] . "]\n");
		continue;
	}

	list($city_id, $city_name) = ew_match_or_create_city($conn, $row['address'], $state_id, $cities_by_state, $created_by, $now);
	if ($city_id < 1) {
		$failed++;
		fwrite(STDERR, 'No city: ' . $row['name'] . "\n");
		continue;
	}

	$pin = (strlen($row['pincode']) === 6) ? (int) $row['pincode'] : 0;
	$state_label = $row['state_name'] !== '' ? $row['state_name'] : ew_gst_state_name($row['gst']);
	$clean_addr = ew_clean_party_address($row['address'], $pin, $city_name, $state_label);
	$phone = ew_import_phone($row['mobile'], $row['phone']);
	$contact = $row['contact_person'] !== '' ? $row['contact_person'] : $row['name'];
	$email = ew_import_email($row['email']);
	$email_cc = ew_import_email($row['email_cc']);

	$gst = $row['gst'];
	if ($gst !== '' && isset($gst_parent[$gst])) {
		$parent_id = $gst_parent[$gst];
		$branch_name = substr($row['name'], 0, 100);
		$sql = "INSERT INTO client_branch (company_id, branch_name, branch_contact_person, contact_no, address1, address2, city, state, pincode, email, created_at, created_by, status) VALUES ('" .
			(int) $parent_id . "','" .
			mysqli_real_escape_string($conn, $branch_name) . "','" .
			mysqli_real_escape_string($conn, substr($contact, 0, 100)) . "','" .
			mysqli_real_escape_string($conn, $phone) . "','" .
			mysqli_real_escape_string($conn, $clean_addr) . "','','" .
			(int) $city_id . "','" . (int) $state_id . "','" .
			mysqli_real_escape_string($conn, (string) $pin) . "','" .
			mysqli_real_escape_string($conn, $email) . "','" .
			mysqli_real_escape_string($conn, $now) . "','" . (int) $created_by . "','0')";
		if (mysqli_query($conn, $sql)) {
			mysqli_query($conn, "UPDATE client SET multiple_branches='1' WHERE client_id='" . (int) $parent_id . "'");
			$branches++;
		} else {
			$failed++;
			fwrite(STDERR, 'Branch fail ' . $row['name'] . ': ' . mysqli_error($conn) . "\n");
		}
		continue;
	}

	$code = ew_import_billing_code($row['name'], $used_codes);
	$used_codes[$code] = true;

	$sql = "INSERT INTO client (
		client_company_name, contact_person, address1, address2, state, city, pincode, billing_code,
		email, email1, contact_no, contact_no1, gst_no, pan_no, multiple_branches, automation,
		created_at, created_by, status, approve_status
	) VALUES (
		'" . mysqli_real_escape_string($conn, substr($row['name'], 0, 100)) . "',
		'" . mysqli_real_escape_string($conn, substr($contact, 0, 100)) . "',
		'" . mysqli_real_escape_string($conn, $clean_addr) . "',
		'',
		'" . (int) $state_id . "',
		'" . (int) $city_id . "',
		'" . (int) $pin . "',
		'" . mysqli_real_escape_string($conn, $code) . "',
		'" . mysqli_real_escape_string($conn, $email) . "',
		'" . mysqli_real_escape_string($conn, $email_cc) . "',
		'" . mysqli_real_escape_string($conn, $phone) . "',
		'',
		'" . mysqli_real_escape_string($conn, $gst) . "',
		'" . mysqli_real_escape_string($conn, substr($row['pan'], 0, 100)) . "',
		'0','0',
		'" . mysqli_real_escape_string($conn, $now) . "',
		'" . (int) $created_by . "',
		'0','0'
	)";

	if (!mysqli_query($conn, $sql)) {
		$failed++;
		unset($used_codes[$code]);
		fwrite(STDERR, 'Insert fail ' . $row['name'] . ': ' . mysqli_error($conn) . "\n");
		continue;
	}
	$id = (int) mysqli_insert_id($conn);
	$existing_names[$name_key] = $id;
	if ($gst !== '') {
		$gst_parent[$gst] = $id;
	}
	$inserted++;
}

echo "Excel rows: " . count($rows) . "\n";
echo "Clients inserted: $inserted\n";
echo "Branches inserted: $branches\n";
echo "Skipped (already present): $skipped\n";
echo "Failed: $failed\n";
$cnt = mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM client'));
echo "client table count: " . $cnt[0] . "\n";
