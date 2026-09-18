<?php
require_once('include/connect.php');
require_once('include/function.php');

header('Content-Type: application/json; charset=utf-8');

$cmd = $_REQUEST['cmd'] ?? '';
$key = $_REQUEST['key'] ?? '';

if ($cmd !== 'fetch' || $key === '') {
	echo json_encode(array('status' => 1, 'message' => 'Invalid request.'));
	exit;
}

$key = mysqli_real_escape_string($conn, $key);
$sql = "SELECT * FROM pod_files WHERE md5(id)='" . $key . "' LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
	echo json_encode(array('status' => 1, 'message' => 'POD record not found.'));
	exit;
}

$row = mysqli_fetch_assoc($res);
$screens = explode('@@', $row['screens'] ?? '');
$images = array();
foreach ($screens as $file) {
	$file = trim($file);
	if ($file === '') {
		continue;
	}
	$images[] = array(
		'file' => $file,
		'url' => '../pod_uploads/' . $file,
	);
}

echo json_encode(array(
	'status' => 0,
	'id' => (int) $row['id'],
	'edit_key' => md5($row['id']),
	'created_at' => $row['created_at'] ?? '',
	'images' => $images,
), JSON_UNESCAPED_UNICODE);
