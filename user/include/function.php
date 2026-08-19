<?php 

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

function get_vehicle_name($conn,$id)
{
	$query = "select * from vehicle where vehicle_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['vehicle_reg_no'];
}

function get_state_name($conn,$id)
{
	$query = "select * from state where state_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function get_user_company_name($conn,$id)
{
	
	if($id=="0")
		return "Gracious Express";
	else
	{
		$query = "select * from client where client_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['client_company_name'];
		
	}
	 
}

function get_user_branch_name($conn,$company_id,$branch_id)
{
	
	if($company_id=="0")
	{
		$query = "select * from branch where branch_id='$branch_id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['branch_name'];
	
	}
	else
	{
		$query = "select * from client_branch where client_branch_id='$branch_id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['branch_name'];
		
	}
	 
}


function get_cong_remarks($conn,$status,$grn_no)
{
	$query = "select * from transaction_status where sheet_id In (select sheet_id from transaction_status_log where grn_no='$grn_no' and to_status='$status')";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['remarks'];
	
	 
}

function get_statename($conn,$id)
{
	$query = "select * from state where state_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['state_name'];
}

function get_city_name($conn,$id)
{
	$query = "select * from city where city_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['city_name'];
}
function get_hub_name($conn,$id)
{
	$query = "select * from hub where hub_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['name'];
}
function get_mode($conn,$id)
{
	$query = "select * from mode_of_transportation where mode_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['mode_type'];
}
function get_locality_name($conn,$id)
{
	$query = "select * from tv_localities where locality_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}
function get_users($conn){
	$query = "select * from users";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function get_client_name($conn,$id){
	$query = "select * from client where client_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['client_company_name'];
}

function get_client_email($conn,$id){
	$query = "select * from client where client_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['email'];
}

function get_client($conn,$id){
	$query = "select * from client where client_id='$id' order by client_company_name asc";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}

function consignment_mode($conn,$id)
{
	$query = "select * from consignment_mode where consignment_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['consignment_mode'];
}


function get_email($conn,$id)
{
	 $query = "select * from tv_admins where company_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}
function get_multiple_email($conn,$id)
{
	$query = "select * from tv_admins where employee_id='".$id."'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}
function get_password($conn,$id)
{
	 $query = "select * from tv_admins where company_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row;
}
function get_user($conn,$id)
{
	 $query = "select * from users where user_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['user_name'];
}
function get_package_name($conn,$id)
{
	$query = "select * from package where package_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['package_code'];
}
function get_company($conn,$id)
{
	 $query = "select * from users where user_id='$id'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	return $row['company_name'];
}
function get_trans_table_name($conn,$date){
	//echo $date;
	$dates = trim($date,"`");
	$dt=(explode("-",$date));	
	$y=$dt[2];
	$m=$dt[1];	
	if($m<=3)
		$m1= 1;
	
	else if(($m>=4) && ($m<=6))
		$m1= 2;
	
	else if(($m>=7) && ($m<=9))
		$m1= 3;
	
	else
		$m1= 4;
	
		$trans_name = "transaction_".$m1."_".$y;
		$trans_image_name = "transaction_images_".$m1."_".$y;
		$trans_invoice_name = "transaction_invoice_".$m1."_".$y;
	
	$table_name = array($trans_name,$trans_image_name,$trans_invoice_name);
	$table_main = array("transaction","transaction_images","transaction_invoice");
	$trans_tbl = $m1."_".$y;
	for($i=0;$i<count($table_name);$i++){

	$val = mysqli_query($conn,'SELECT * FROM '.$table_name[$i]);
	$count = mysqli_num_rows($val);
	if($count == 0)
	{
	$db_creation = mysqli_query($conn,"create table ".$table_name[$i]." like ".$table_main[$i]);
		//	echo $i;
		if($i==0){
		    $val1 = mysqli_query($conn,'SELECT * FROM transaction_tbls where table_name="'.$trans_tbl.'"');
	        $count1 = mysqli_num_rows($val1);
	    if($count1 == 0)
		    $db_name_store = mysqli_query($conn,"insert into transaction_tbls(table_name,created_at) values ('$trans_tbl','$dates')");	
	    }

	}			
	}
	return $table_name;
}
?>
