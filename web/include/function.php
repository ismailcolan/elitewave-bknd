<?php
require_once("tracking_templates.php");

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
	$query = "select * from city where city_id='$id'";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_array($result);
	return $row['city_name'];
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
	return $row['client_company_name'];
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
				$val1 = mysqli_query($conn, 'SELECT * FROM transaction_tbls where table_name="' . $trans_tbl . '"');
				$count1 = mysqli_num_rows($val1);
				if ($count1 == 0)
					$db_name_store = mysqli_query($conn, "insert into transaction_tbls(table_name,created_at) values ('$trans_tbl','$dates')");
			}
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
// Use this ONLY at the moment of actually saving a booking.
function get_next_grn_id($conn, $seq_key)
{
	$seq_key = mysqli_real_escape_string($conn, $seq_key);
	mysqli_query($conn, "INSERT INTO grn_sequence (seq_key, last_grn_id) VALUES ('$seq_key', 1)
                          ON DUPLICATE KEY UPDATE last_grn_id = last_grn_id + 1");
	$r = mysqli_query($conn, "SELECT last_grn_id FROM grn_sequence WHERE seq_key='$seq_key'");
	$row = mysqli_fetch_assoc($r);
	return (int) $row['last_grn_id'];
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

    //=============================
    // Origin City Details
    //=============================
    $originCity = mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM city WHERE city_id='".$row['origin']."'"
        )
    );

    //=============================
    // Destination City Details
    //=============================
    $destinationCity = mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM city WHERE city_id='".$row['destination']."'"
        )
    );

//=============================
// Loading Hub (Based on Transport Mode)
//=============================

$modeLower = strtolower($mode);

if (strpos($modeLower, 'air') !== false) {

    $loadingHub = !empty($originCity['airport'])
        ? $originCity['airport']
        : $origin;

}
elseif (strpos($modeLower, 'train') !== false) {

    $loadingHub = !empty($originCity['railway_station'])
        ? $originCity['railway_station']
        : $origin;

}
elseif (
    strpos($modeLower, 'road') !== false ||
    strpos($modeLower, 'surface') !== false ||
    strpos($modeLower, 'truck') !== false
) {

    $loadingHub = !empty($originCity['warehouse'])
        ? $originCity['warehouse']
        : $origin;

}
elseif (
    strpos($modeLower, 'sea') !== false ||
    strpos($modeLower, 'port') !== false
) {

    $loadingHub = !empty($originCity['port'])
        ? $originCity['port']
        : $origin;

}
else {

    $loadingHub = !empty($originCity['unloading_point'])
        ? $originCity['unloading_point']
        : $origin;

}

 //=============================
// Destination Hub (Based on Transport Mode)
//=============================

if (strpos($modeLower, 'air') !== false) {

    $destinationHub = !empty($destinationCity['airport'])
        ? $destinationCity['airport']
        : $destination;

}
elseif (strpos($modeLower, 'train') !== false) {

    $destinationHub = !empty($destinationCity['railway_station'])
        ? $destinationCity['railway_station']
        : $destination;

}
elseif (
    strpos($modeLower, 'road') !== false ||
    strpos($modeLower, 'surface') !== false ||
    strpos($modeLower, 'truck') !== false
) {

    $destinationHub = !empty($destinationCity['warehouse'])
        ? $destinationCity['warehouse']
        : $destination;

}
elseif (
    strpos($modeLower, 'sea') !== false ||
    strpos($modeLower, 'port') !== false
) {

    $destinationHub = !empty($destinationCity['port'])
        ? $destinationCity['port']
        : $destination;

}
else {

    $destinationHub = !empty($destinationCity['unloading_point'])
        ? $destinationCity['unloading_point']
        : $destination;

}

$data = array(

    "grn" => $grn,

    "origin" => $origin,

    "destination" => $destination,

    "consignor" => $consignor,

    "consignee" => $consignee,

    "mode" => $mode,

    "loadingHub" => $loadingHub,

    "destinationHub" => $destinationHub

);

return tracking_template($status, $data);
}

?>
