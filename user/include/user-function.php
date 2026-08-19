<?php 

function get_user($con,$id){
	$query = "SELECT *FROM users where `user_id` = '$id'";
	$result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	return $row['user_name'];
}
function get_user_email($con,$id){
	$query = "SELECT *FROM users where `user_id` = '$id'";
	$result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	return $row['email'];
}
function get_rfp_id($con){
	$query = "SELECT max(pickup_id) as pickup_id FROM user_pickup";
	$result = mysqli_query($con,$query);
	$row = mysqli_fetch_array($result);
	return $row['pickup_id'];
}
function get_city_name($conn,$id)
{
	$query = "select * from city where city_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['city_name'];
}
function get_statename($conn,$id)
{
	$query = "select * from state where state_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['state_name'];
}

function get_mode($conn,$id)
{
	$query = "select * from mode_of_transportation where mode_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['mode_type'];
}

// function get_trans_status($val)
// {
// 	if($val==1)
// 		echo '<li class="actives">Consignment Booked</li>';
// 	if($val==2)
// 	echo '<li class="">Consignment Pickedup</li>';
// 	if($val==3)
// 	echo "<li class=''>In Transit - 1 (Consignment at Origin State)</li>";
// 	if($val==4)
// 		echo "<li class=''>In Transit - 2 (Towards Destination State)</li>";
// 	if($val==5)
// 	echo "<li class=''>In Transit - 3 (Towards Destination)</li>";
// 	if($val==6)
// 	echo "<li class=''>At Destination</li>";
// 	if($val==7)
// 	echo "<li class=''>Out for Delivery</li>";
// 	if($val==8)
// 	echo "<li class=''>Consignment Delivered Successfully</li>";

// }
// function get_trans_status1($val)
// {
// 	if($val==1)
// 		echo '<li class="actives">Consignment Booked</li>';
// 	if($val==2)
// 	echo '<li class="actives">Consignment Pickedup</li>';
// 	if($val==3)
// 	echo "<li class=''>In Transit - 1 (Consignment at Origin State)</li>";
// 	if($val==4)
// 		echo "<li class=''>In Transit - 2 (Towards Destination State)</li>";
// 	if($val==5)
// 	echo "<li class=''>In Transit - 3 (Towards Destination)</li>";
// 	if($val==6)
// 	echo "<li class=''>At Destination</li>";
// 	if($val==7)
// 	echo "<li class=''>Out for Delivery</li>";
// 	if($val==8)
// 	echo "<li class=''>Consignment Delivered Successfully</li>";

// }
// function get_trans_status2($val)
// {
// 	if($val==1)
// 		echo '<li class="actives">Consignment Booked</li>';
// 	if($val==2)
// 	echo '<li class="actives">Consignment Pickedup</li>';
// 	if($val==3)
// 	echo "<li class='actives'>In Transit - 1 (Consignment at Origin State)</li>";
// 	if($val==4)
// 		echo "<li class=''>In Transit - 2 (Towards Destination State)</li>";
// 	if($val==5)
// 	echo "<li class=''>In Transit - 3 (Towards Destination)</li>";
// 	if($val==6)
// 	echo "<li class=''>At Destination</li>";
// 	if($val==7)
// 	echo "<li class=''>Out for Delivery</li>";
// 	if($val==8)
// 	echo "<li class=''>Consignment Delivered Successfully</li>";

// }
// function get_trans_status3($val)
// {
// 	if($val==1)
// 		echo '<li class="actives">Consignment Booked</li>';
// 	if($val==2)
// 	echo '<li class="actives">Consignment Pickedup</li>';
// 	if($val==3)
// 	echo "<li class='actives'>In Transit - 1 (Consignment at Origin State)</li>";
// 	if($val==4)
// 		echo "<li class='actives'>In Transit - 2 (Towards Destination State)</li>";
// 	if($val==5)
// 	echo "<li class=''>In Transit - 3 (Towards Destination)</li>";
// 	if($val==6)
// 	echo "<li class=''>At Destination</li>";
// 	if($val==7)
// 	echo "<li class=''>Out for Delivery</li>";
// 	if($val==8)
// 	echo "<li class=''>Consignment Delivered Successfully</li>";

// }
function get_client_name($conn,$id){
	$query = "select * from client where client_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['client_company_name'];
}

function consignment_mode($conn,$id)
{
	$query = "select * from consignment_mode where consignment_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['consignment_mode'];
}
function get_trans_status($val)
{
	if($val==1)
		return "Consignment Booked";
	if($val==2)
		return "Consignment Picked Up";
	if($val==3)
		return "In Transit - 1 (Consignment at Origin State)";
	if($val==4)
		return "In Transit - 2 (Towards Destination State)";
	if($val==5)
		return "In Transit - 3 (Towards Destination)";
	if($val==6)
		return "At Destination";
	if($val==7)
		return "Out for Delivery";
	if($val==8)
		return "Consignment Delivered Successfully";

}
function get_cong_remarks($conn,$status,$grn_no)
{
	$query = "select * from transaction_status where sheet_id In (select sheet_id from transaction_status_log where grn_no='$grn_no' and to_status='$status')";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['remarks'];
	
	 
}
function get_trans_table($conn,$date){
	$dates = trim($date,"`");
	$dt = explode("-",$dates);
	$y = $dt[2];
	$m = $dt[1];
	
	if($m<=3)
		$m1 = 1;
	else if(($m>=4)	&& ($m<=6))
		$m1 = 2;
	else if(($m>=7)	&& ($m<=9))
		$m1 = 3;
	else
		$m1 = 4;
		
	$trans_name = "transaction_".$m1."_".$y;	
	$trans_image = "transaction_images_".$m1."_".$y;	
	$trans_invoice = "transaction_invoice_".$m1."_".$y;	
	
	$table_name = array($trans_name,$trans_image,$trans_invoice);
	$table_main = array("transaction","transaction_images","transaction_invoice");
	$trans_table = $m1."_".$y;
	for($i=0;$i<count($table_name);$i++){
		$check_tbl_exsist=mysqli_query($conn,'SELECT * FROM '.$table_name[$i]);
		$count_check = mysqli_num_rows($check_tbl_exsist);
		if($count_check == 0)
		{
			$db_create = mysqli_query($conn,"create table ".$table_main[$i]." like ".$table_main[$i]);

			if($i==0){
				$check_table_again_trans_tbl = mysqli_query($conn,'SELECT *FROM transaction_tbls where table_name="'.$trans_table.'"'); 
				$count_check_1=mysqli_num_rows($check_table_again_trans_tbl);
				if($count_check_1 =0){
					$db_name_save = mysqli_query($conn,"insert into transaction_tbls(table_name,created_at) values('$trans_table','$dates')");

				}
			}

		}
	}
	return $table_name;

}
function get_single_user($conn,$id){
	$query = "select * from users where user_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function get_client_id($conn,$email){
	$query = "select * from client where email='$email'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}


function get_client_emails($conn,$id){ // new 1
	$query = "select * from client where client_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['email'];
}
function get_client_phones($conn,$id){ // new 2
	$query = "select * from client where client_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['contact_no'];
}

function enc_name($name='123'){
    $enc = base64_encode(base64_encode(base64_encode(base64_encode('GraciousExpress').':$'.base64_encode($name).':$'.base64_encode('GraciousExpress'))));
    return $enc;
}
?>