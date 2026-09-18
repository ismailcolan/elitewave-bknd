<?php
require_once 'include/connect.php';
require_once 'include/function.php';

$is_cli = (PHP_SAPI === 'cli');
if (!$is_cli && (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'AD')) {
	header('HTTP/1.1 403 Forbidden');
	echo 'Admin login required.';
	exit;
}

$updated = 0;
$res = mysqli_query($conn, 'SELECT client_id, address1, address2, city, state, pincode FROM client');
while ($row = mysqli_fetch_assoc($res)) {
	$city = get_city_name($conn, $row['city']);
	$state = get_statename($conn, $row['state']);
	$a1 = ew_clean_party_address($row['address1'], $row['pincode'], $city, $state);
	$a2 = ew_clean_party_address($row['address2'], $row['pincode'], $city, $state);
	if ($a1 === $row['address1'] && $a2 === $row['address2']) {
		continue;
	}
	$ok = mysqli_query($conn, "UPDATE client SET address1='" . mysqli_real_escape_string($conn, $a1) . "', address2='" . mysqli_real_escape_string($conn, $a2) . "' WHERE client_id='" . (int) $row['client_id'] . "'");
	if ($ok) {
		$updated++;
	}
}

$branch_updated = 0;
$bres = mysqli_query($conn, 'SELECT client_branch_id, address1, address2, city, state, pincode FROM client_branch');
if ($bres) {
	while ($row = mysqli_fetch_assoc($bres)) {
		$city = get_city_name($conn, $row['city']);
		$state = get_statename($conn, $row['state']);
		$a1 = ew_clean_party_address($row['address1'], $row['pincode'], $city, $state);
		$a2 = ew_clean_party_address($row['address2'], $row['pincode'], $city, $state);
		if ($a1 === $row['address1'] && $a2 === $row['address2']) {
			continue;
		}
		$ok = mysqli_query($conn, "UPDATE client_branch SET address1='" . mysqli_real_escape_string($conn, $a1) . "', address2='" . mysqli_real_escape_string($conn, $a2) . "' WHERE client_branch_id='" . (int) $row['client_branch_id'] . "'");
		if ($ok) {
			$branch_updated++;
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head><title>Clean client addresses</title></head>
<body style="font-family:sans-serif;padding:24px">
<p>Cleaned <?php echo (int) $updated; ?> client address(es) and <?php echo (int) $branch_updated; ?> branch address(es).</p>
<p>Pincode matching the Pin field was removed from Address. Trailing city/state that match the City/State fields were removed when other address text remained.</p>
<p><a href="client_list.php">Open Client List</a></p>
</body>
</html>
