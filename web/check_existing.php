<?php
require_once('include/connect.php');
function enc_name($name='123'){
    $enc = base64_encode(base64_encode(base64_encode(base64_encode('GraciousExpress').':$'.base64_encode($name).':$'.base64_encode('GraciousExpress'))));
    return $enc;
}
//require_once('save_admin.php');
$cmd = $_REQUEST['cmd'];
if($cmd == "chk_state"){
	$state = $_REQUEST['state_name'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and state_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from tv_states where state_name='".$state."' and status=0 $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}

if($cmd == "chk_city"){
	$city_name = $_REQUEST['city_name'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and city_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from tv_cities where city_name='".$city_name."' and status=0 $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_billingcode"){
	$billing_code = $_REQUEST['billing_code'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and client_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	// $query = "select * from client where billing_code='".$billing_code."' and status=0 $edit_id_check"; // status removed because it is checking client code for only active clients it leads duplicate client code problem
	$query = "select * from client where billing_code='".$billing_code."' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}

if($cmd == "chk_email_exist"){
	$email = $_REQUEST['email_check'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and md5(client_id)!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid  details");
	$query = "select * from client where email='".$email."' and status=0 $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another Email...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_origin_destination"){
	$origin  = $_REQUEST['origin'];
	$destination  = $_REQUEST['destination'];

	if($origin && $destination !="")
	$out_put = array("0", "Combination Valid");
	
	$check_origin_desti = " origin='$origin' AND destination='$destination'";
	
	$sql = "select *from rate where ".$check_origin_desti;

	$result = mysqli_query($conn,$sql);
	if(mysqli_num_rows($result) > 0){
	
		$out_put = array("1","Combination Already Exist") ;
	}
	echo json_encode($out_put);
}


if($cmd == "chk_estimated_origin_destination"){
	$origin  = $_REQUEST['origin'];
	$destination  = $_REQUEST['destination'];

	if($origin && $destination !="")
	$out_put = array("0", "Combination Valid");
	
	$check_origin_desti = " origin='$origin' AND destination='$destination'";
	
	$sql = "select *from expectded_delivery where ".$check_origin_desti;

	$result = mysqli_query($conn,$sql);
	if(mysqli_num_rows($result) > 0){
	
		$out_put = array("1","Combination Already Exist") ;
	}
	echo json_encode($out_put);
}
if($cmd == "chk_consingor_destination"){
	$client_idd  = $_REQUEST['client_idd'];
	$destination  = $_REQUEST['destination'];

	if($client_idd && $destination !="")

	$out_put = array("0", "Combination Valid");
	
	$check_client_desti = " consigner_id='$client_idd' AND destination='$destination'";
	
	$sql = "select *from consignor_payment where ".$check_client_desti;

	$result = mysqli_query($conn,$sql);
	if(mysqli_num_rows($result) > 0){
	
		$out_put = array("1","Combination Already Exist") ;
	}
	echo json_encode($out_put);
}
if($cmd == "chk_hub"){
	$hub_name = $_REQUEST['hub_name'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and hub_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from hub where hub_name='".$hub_name."' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_consignment_mode"){
	$consignment = $_REQUEST['consignment'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and consignment_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from consignment_mode where consignment_mode='$consignment' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_locality"){
	$locality = $_REQUEST['locality'];
	$edit_id = $_REQUEST['edit_id'];
	$city_id = $_REQUEST['city_id'];
	if($edit_id != '')
		$edit_id_check = " and locality_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	 $query = "select * from tv_localities where locality_name='".$locality."' and status=0 and city_id='".$city_id."' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_password"){
	$chk_key_id = $_REQUEST['chk_key_id'];
	$chk_by_id = "";
	if($chk_key_id != "") $chk_by_id = "and admin_id = '$chk_key_id'";
	$chk_key = $_REQUEST['chk_key'];
	$chk_key = enc_name($chk_key);
	$out_put = array();
	$query = "select * from users where password='".$chk_key."' $chk_by_id ";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	
	
	if(mysqli_num_rows($result) > 0)
		$out_put["dup_status"] = 1;
	else
		$out_put["dup_status"] = 0;
	
	echo json_encode($out_put);
}
if($cmd == "chk_role"){
	$department = $_REQUEST['department'];
	$edit_id = $_REQUEST['edit_id'];
	$role = $_REQUEST['role'];
	
	if($edit_id != '')
		$edit_id_check = " and employee_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from tv_employees where role='".$role."' and status=0 and department_id='".$department."' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_department"){
	$department = $_REQUEST['department_name'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and department_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from tv_departments where department_name='".$department."' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_designation"){
	$designation = $_REQUEST['designation_name'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and designation_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from tv_designation where designation_name='".$designation."' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_mail"){
	
	$edit_id = $_REQUEST['edit_id'];
	$email = trim($_REQUEST['email_id']);
	$code = trim($_REQUEST['code']);
	
	$out_put = array("0", "Valid details", "0", "0");
	
	if($edit_id != '')
		$edit_id_check = " and employee_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	$query = "select * from tv_admins where email='$email' $edit_id_check";
	$result = mysqli_query($conn,$query) or die(mysqli_error());
	
	$query1 = "select * from tv_employees where employee_code='$code' $edit_id_check";
	$result1 = mysqli_query($conn,$query1) or die(mysqli_error());
	
	
	if(mysqli_num_rows($result)){
		
		$out_put[0] = "1";
		$out_put[1] = "Already exists. try another...";
	}
	
	if(mysqli_num_rows($result1)){

		$out_put[2] = "1";
		$out_put[1] = "Already exists. try another...";
	}
	echo json_encode($out_put);
	
}
if($cmd == "chk_review_topic"){
	$review_topic = $_REQUEST['review_topic'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and review_topic_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from tv_review_topic where review_topic_name='".$review_topic."' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}
if($cmd == "chk_sub_topic"){
	$sub_topic = $_REQUEST['sub_topic_name'];
	$review_topic = $_REQUEST['review_topic'];
	$edit_id = $_REQUEST['edit_id'];
	
	if($edit_id != '')
		$edit_id_check = " and sub_topic_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid details");
	$query = "select * from tv_sub_topic where sub_topic_name='".$sub_topic."' and review_topic_id='".$review_topic."' $edit_id_check";

	$result = mysqli_query($conn,$query) or die(mysqli_error());
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Already exists. Try another key...");
	}
	
	echo json_encode($out_put);
}


if($cmd == "chk_grn_no"){
	$grn_no = "LA/".$_REQUEST['grn_no'];
	$edit_id = $_REQUEST['grn_id'];
	$count=0;
	if($edit_id != '')
		$edit_id_check = " and grn_id!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid GRN No");
	$query2 = "SELECT * FROM transaction_tbls";
	$result2 = mysqli_query($conn,$query2) or die(mysqli_error($conn));
	while($row2 = mysqli_fetch_assoc($result2))
	{			
	
	 $query = "select * from transaction_".$row2['table_name']." where grn_no='$grn_no'$edit_id_check";
	$result = mysqli_query($conn,$query);	
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "GRN No Already exists. Try another...");
	}
	
	}

		
	echo json_encode($out_put);
}

//vehicle_number
if($cmd == "chk_vehicle_no"){
	$vehicle_number = $_REQUEST['vehicle_number'];
	$edit_id = $_REQUEST['edit_id'];
	$count=0;
	if($edit_id != '')
		$edit_id_check = " and md5(vehicle_id)!='$edit_id' ";
	else
		$edit_id_check = ' ';
	

	$out_put = array("0", "Valid Vehicle No.");
	
	 $query = "select * from vehicle where vehicle_number='$vehicle_number'$edit_id_check";
	$result = mysqli_query($conn,$query);	
	if(mysqli_num_rows($result) > 0){
		$out_put = array("1", "Vehicle No. Already exists. Try another...");
	}
	
	echo json_encode($out_put);
}



?>