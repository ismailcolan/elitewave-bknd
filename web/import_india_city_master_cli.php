<?php
/**
 * One-off CLI: import web/India_City_Master.xlsx into city table.
 * Skips duplicate city names (case-insensitive). Creates missing states as needed.
 * Usage: php web/import_india_city_master_cli.php
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

require_once __DIR__ . '/include/database_config.php';

$xlsx = __DIR__ . '/India_City_Master.xlsx';
$csv = sys_get_temp_dir() . '/india_city_master_import.csv';
if (!is_file($xlsx)) {
	fwrite(STDERR, "Missing Excel: $xlsx\n");
	exit(1);
}

// Parse xlsx → csv via python (inlineStr cells).
$py = <<<'PY'
import zipfile, re, csv, xml.etree.ElementTree as ET, sys
ns={'m':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
def col_to_idx(col):
    n=0
    for ch in col:
        n=n*26+(ord(ch)-64)
    return n-1
def cell_value(c):
    t=c.get('t')
    if t=='inlineStr':
        return ''.join((t.text or '') for t in c.findall('.//m:t', ns))
    v=c.find('m:v', ns)
    return '' if v is None else (v.text or '')
xlsx, out = sys.argv[1], sys.argv[2]
with zipfile.ZipFile(xlsx) as z:
    root=ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    rows=[]
    for row in root.findall('m:sheetData/m:row', ns):
        vals={}
        for c in row.findall('m:c', ns):
            ref=c.get('r'); col=re.sub(r'\d+','',ref)
            vals[col_to_idx(col)] = cell_value(c)
        if vals:
            rows.append([vals.get(i,'') for i in range(10)])
data=rows[1:]
with open(out,'w',newline='',encoding='utf-8') as f:
    w=csv.writer(f)
    w.writerow(['sno','city_code','city_name','country','state','railway','airport','hub','warehouse','port'])
    for r in data:
        w.writerow(r)
print(len(data))
PY;

$cmd = 'python3 -c ' . escapeshellarg($py) . ' ' . escapeshellarg($xlsx) . ' ' . escapeshellarg($csv);
exec($cmd, $outLines, $pyCode);
if ($pyCode !== 0 || !is_file($csv)) {
	fwrite(STDERR, "Failed to parse Excel\n");
	exit(1);
}
echo "Parsed rows: " . trim(implode("\n", $outLines)) . "\n";

$conn = mysqli_connect(EW_DB_HOST, EW_DB_USER, EW_DB_PASS, EW_DB_NAME);
if (!$conn) {
	fwrite(STDERR, 'DB connect failed: ' . mysqli_connect_error() . "\n");
	exit(1);
}
mysqli_set_charset($conn, 'utf8mb4');

function ew_norm_name($s)
{
	$s = strtolower(trim((string) $s));
	$s = preg_replace('/\s+/', ' ', $s);
	return $s;
}

/** Map Excel state labels → preferred DB state_name / create if missing. */
$stateAliases = array(
	'chhattisgarh' => 'Chattisgarh',
	'odisha' => 'Orissa',
	'dadra & nagar haveli' => 'Dadra and Nagar Haveli and Daman and Diu',
	'dadra&nagar haveli' => 'Dadra and Nagar Haveli and Daman and Diu',
);

$stateByNorm = array();
$q = mysqli_query($conn, "SELECT state_id, state_name FROM state WHERE status=0");
while ($row = mysqli_fetch_assoc($q)) {
	$stateByNorm[ew_norm_name($row['state_name'])] = (int) $row['state_id'];
}

function ew_next_state_code($conn)
{
	$prefix = 'EWS';
	$max = 0;
	$q = mysqli_query($conn, "SELECT state_code FROM state WHERE state_code LIKE '{$prefix}%'");
	while ($row = mysqli_fetch_assoc($q)) {
		if (preg_match('/(\d+)$/', $row['state_code'], $m)) {
			$max = max($max, (int) $m[1]);
		}
	}
	return sprintf('%s%03d', $prefix, $max + 1);
}

function ew_resolve_state_id($conn, $excelState, $country, &$stateByNorm, $stateAliases, &$statesCreated)
{
	$norm = ew_norm_name($excelState);
	if ($norm === '') {
		return 0;
	}
	if (isset($stateAliases[$norm])) {
		$aliasNorm = ew_norm_name($stateAliases[$norm]);
		if (isset($stateByNorm[$aliasNorm])) {
			return $stateByNorm[$aliasNorm];
		}
	}
	if (isset($stateByNorm[$norm])) {
		return $stateByNorm[$norm];
	}
	// Create missing state.
	$code = mysqli_real_escape_string($conn, ew_next_state_code($conn));
	$name = mysqli_real_escape_string($conn, trim($excelState));
	$countryEsc = mysqli_real_escape_string($conn, $country !== '' ? $country : 'India');
	$now = date('Y-m-d H:i:s');
	$ok = mysqli_query(
		$conn,
		"INSERT INTO state (state_code, state_name, country_name, geo_scope, created_at, created_by, status)
		 VALUES ('$code', '$name', '$countryEsc', 'domestic', '$now', 0, 0)"
	);
	if (!$ok) {
		fwrite(STDERR, "Failed creating state $excelState: " . mysqli_error($conn) . "\n");
		return 0;
	}
	$id = (int) mysqli_insert_id($conn);
	$stateByNorm[$norm] = $id;
	$statesCreated[] = $excelState . " ($code)";
	return $id;
}

// Existing city names for duplicate skip.
$existing = array();
$q = mysqli_query($conn, 'SELECT city_name FROM city');
while ($row = mysqli_fetch_assoc($q)) {
	$existing[ew_norm_name($row['city_name'])] = true;
}

// Next city_code_id / city_code number.
$row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT MAX(city_code_id) AS mid FROM city'));
$nextCodeId = (int) ($row['mid'] ?? 0) + 1;
$maxNum = 0;
$q = mysqli_query($conn, "SELECT city_code FROM city WHERE city_code LIKE 'EWC%'");
while ($r = mysqli_fetch_assoc($q)) {
	if (preg_match('/(\d+)$/', $r['city_code'], $m)) {
		$maxNum = max($maxNum, (int) $m[1]);
	}
}
$nextNum = $maxNum + 1;

$fh = fopen($csv, 'r');
$header = fgetcsv($fh);
$inserted = 0;
$skippedDup = 0;
$skippedBad = 0;
$failed = 0;
$statesCreated = array();
$skippedNames = array();
$now = date('Y-m-d H:i:s');

mysqli_begin_transaction($conn);

while (($r = fgetcsv($fh)) !== false) {
	if (count($r) < 5) {
		$skippedBad++;
		continue;
	}
	$cityName = trim((string) $r[2]);
	$country = trim((string) $r[3]);
	$stateName = trim((string) $r[4]);
	$railway = trim((string) ($r[5] ?? ''));
	$airport = trim((string) ($r[6] ?? ''));
	$hub = trim((string) ($r[7] ?? ''));
	$warehouse = trim((string) ($r[8] ?? ''));
	$port = trim((string) ($r[9] ?? ''));

	if ($cityName === '') {
		$skippedBad++;
		continue;
	}
	$key = ew_norm_name($cityName);
	if (isset($existing[$key])) {
		$skippedDup++;
		$skippedNames[] = $cityName;
		continue;
	}

	$stateId = ew_resolve_state_id($conn, $stateName, $country, $stateByNorm, $stateAliases, $statesCreated);
	if ($stateId <= 0) {
		fwrite(STDERR, "No state for city: $cityName / $stateName\n");
		$failed++;
		continue;
	}

	$cityCode = sprintf('EWC%03d', $nextNum);
	$codeId = $nextCodeId;

	$esc = function ($v) use ($conn) {
		return mysqli_real_escape_string($conn, $v);
	};

	// Hub in Excel ≈ unloading_point text in master; via_city stays empty (city_id FK optional).
	$sql = "INSERT INTO city (
		city_code, city_code_id, city_name, country_name, state, automation, via_city,
		railway_station, airport, unloading_point, warehouse, port, geo_scope,
		created_at, created_by, status
	) VALUES (
		'" . $esc($cityCode) . "',
		'" . (int) $codeId . "',
		'" . $esc($cityName) . "',
		'" . $esc($country !== '' ? $country : 'India') . "',
		'" . (int) $stateId . "',
		'0',
		'',
		'" . $esc($railway) . "',
		'" . $esc($airport) . "',
		'" . $esc($hub !== '' ? $hub : $cityName) . "',
		'" . $esc($warehouse !== '' ? $warehouse : $cityName) . "',
		'" . $esc($port) . "',
		'domestic',
		'" . $esc($now) . "',
		'0',
		'0'
	)";

	if (!mysqli_query($conn, $sql)) {
		fwrite(STDERR, "Insert failed $cityName: " . mysqli_error($conn) . "\n");
		$failed++;
		continue;
	}

	$existing[$key] = true;
	$nextNum++;
	$nextCodeId++;
	$inserted++;
}
fclose($fh);

if ($failed > 0) {
	mysqli_rollback($conn);
	fwrite(STDERR, "Rolled back due to failures: $failed\n");
	exit(1);
}
mysqli_commit($conn);

echo "States created: " . count($statesCreated) . "\n";
foreach ($statesCreated as $s) {
	echo "  + $s\n";
}
echo "Inserted: $inserted\n";
echo "Skipped duplicates: $skippedDup\n";
echo "Skipped bad rows: $skippedBad\n";
if ($skippedDup > 0) {
	echo "Duplicate sample (first 20):\n";
	foreach (array_slice($skippedNames, 0, 20) as $n) {
		echo "  - $n\n";
	}
}
$cnt = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS c FROM city'));
echo "City table total now: " . $cnt['c'] . "\n";
