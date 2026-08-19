<?php
require_once 'include/connect.php';
$name = $_GET['term'];
$autocomplete = $_REQUEST['autocomplete'];
// if($autocomplete == "employee"){

// $selected_dept = !empty($_REQUEST['department'])?"and department_id='".$_REQUEST['department']."' and employee_id!='".$_SESSION['employee_id']."' and role!='SP'":"";
// 	$query = "SELECT * FROM tv_employees where (employee_name LIKE '%".$name."%') and company_id='".$_SESSION['company_id']."' and status=0 $selected_dept";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['employee_name'];
// 		$result_value['id'] = $row['employee_id'];
// 		$result_value['dept_id'] = $row['department_id'];
// 		$result_value['query'] = $query;
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }

// if($autocomplete == "department"){

// 	$query = "SELECT * FROM tv_departments where (department_name LIKE '%".$name."%') and company_id='".$_SESSION['company_id']."' and status=0";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['department_name'];
// 		$result_value['id'] = $row['department_id'];
// 		//$result_value['dept_id'] = $row['department_id'];
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }
// if($autocomplete == "sub_topic"){

// 	$selected_condition = !empty($_REQUEST['review_topic'])?"and review_topic_id='".$_REQUEST['review_topic']."'":"";
// 	$query = "SELECT * FROM tv_sub_topic where (sub_topic_name LIKE '%".$name."%') and company_id='".$_SESSION['company_id']."' and status=0 $selected_condition";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['sub_topic_name'];
// 		$result_value['id'] = $row['sub_topic_id'];
// 		$result_value['review_id'] = $row['review_topic_id'];
// 		$result_value['query'] = $query;
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }
// if($autocomplete == "review_topic"){

// 	$query = "SELECT * FROM tv_review_topic where (review_topic_name LIKE '%".$name."%') and company_id='".$_SESSION['company_id']."' and status=0";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['review_topic_name'];
// 		$result_value['id'] = $row['review_topic_id'];
// 		$result_value['query'] = $query;
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }
// if($autocomplete == "department_sub_topic"){

// 	$selected_condition = !empty($_REQUEST['review_topic'])?"and review_topic_id='".$_REQUEST['review_topic']."'":"";
// 	$query = "SELECT * FROM tv_department_sub_topic where (sub_topic_name LIKE '%".$name."%') and company_id='".$_SESSION['company_id']."' and created_by='".$_SESSION['admin_id']."' and status=0 $selected_condition";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['sub_topic_name'];
// 		$result_value['id'] = $row['sub_topic_id'];
// 		$result_value['review_id'] = $row['review_topic_id'];
// 		$result_value['query'] = $query;
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }
// if($autocomplete == "department_review_topic"){

// 	$query = "SELECT * FROM tv_department_review_topic where (review_topic_name LIKE '%".$name."%') and company_id='".$_SESSION['company_id']."' and created_by='".$_SESSION['admin_id']."' and status=0";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['review_topic_name'];
// 		$result_value['id'] = $row['review_topic_id'];
// 		$result_value['query'] = $query;
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }
// if($autocomplete == "state_autocomplete"){
// 	$query = "SELECT * FROM tv_states where (state_name LIKE '%".$name."%') and status=0";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['state_name'];
// 		$result_value['id'] = $row['state_id'];
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }
// if($autocomplete == "city_autocomplete"){
// $query = "SELECT * FROM tv_cities where (city_name LIKE '%".$name."%') and status=0";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['city_name'];
// 		$result_value['id'] = $row['city_id'];
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }
// if($autocomplete == "locality_autocomplete"){
// $query = "SELECT * FROM tv_localities where (locality_name LIKE '%".$name."%') and status=0";
// 	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
// 	while($row = mysqli_fetch_assoc($result))
// 	{
// 		$result_value['value'] = $row['locality_name'];
// 		$result_value['id'] = $row['locality_id'];
// 		$search[] = $result_value;
// 	}
// 	echo json_encode($search);
// }

if($autocomplete == "grn_list_for_status_change"){
	
	extract($_REQUEST);
	$q="";
	if(count($_REQUEST['grn_id'])>0) 
	{
		$grn_ids=implode(",",$_REQUEST['grn_id']);
		$q .=" and grn_id NOT IN ($grn_ids) ";
	}
	if($mode!="")
		$q .=" and mode_of_transportation='$mode' ";
	if($origin!="")
		$q .=" and origin='$origin' ";
	if($destination!="")
		$q .="  and destination='$destination'  ";
	
	$query1 = "SELECT * FROM transaction_tbls";
	$result1 = mysqli_query($conn,$query1) or die(mysqli_error($conn));
	while($row1 = mysqli_fetch_assoc($result1))
	{
		$query = "SELECT grn_no FROM transaction_".$row1['table_name']." where grn_no LIKE '%".$grn_no."%' and status <'$status'  $q";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_assoc($result))
	{
		$result_value['value'] = $row['grn_no'];
		$result_value['id'] = $row['grn_no'];
		$search[] = $result_value;
	}
	}
	echo json_encode($search);
}



if($autocomplete == "consignor_autocomplete"){
	
	extract($_REQUEST);
	$q="";
	if($origin>0) 
		$q .=" and city='$origin' ";
	
	$query = "select * from client where status=0 and client_company_name LIKE '".$name."%' $q order by client_company_name asc";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_assoc($result))
	{
		$result_value['value'] = $row['client_company_name'];
		$result_value['id'] = $row['client_id'];
		$search[] = $result_value;
	}
	
	echo json_encode($search);
}

if($autocomplete == "consignee_autocomplete"){
	

	$consignor = $_REQUEST['consignor'];
	$destination = $_REQUEST['destination'];

	$clients_list=array();
    $mapping_list=array();

	$clients_list[] = $consignor;
    $qr = "SELECT * FROM `customer_mapping` WHERE client = '$consignor' and status = '0'";
    $fth1 = mysqli_query($conn,$qr);
//$fth1=mysqli_query($conn,"select client_id from customer_mapping_lists where mapping_id IN (select mapping_id from customer_mapping where client='".$consignor."') and status='0'");
while($fth1_r=mysqli_fetch_assoc($fth1))
{
	array_push($clients_list, $fth1_r['client_id']);
    array_push($mapping_list, $fth1_r['mapping_id']);
}
    $mapping_id = implode(",",$mapping_list);
    $q2 = "select * from customer_mapping_lists where mapping_id IN(".$mapping_id.")";
	$fth2 = mysqli_query($conn,$q2);
//$fth2=mysqli_query($conn,"select client from customer_mapping where mapping_id IN (select mapping_id from customer_mapping_lists where client_id='".$consignor."') and status='0'");
while($fth2_r=mysqli_fetch_assoc($fth2))
{
	array_push($clients_list, $fth2_r['client_id']);
}
// print_r($clients_list);
// exit();

//array_unique($clients_list);
if(count($clients_list) > 0){
// $client_ids=implode(",", array_filter($clients_list));
$clients_list = array_unique(array_filter($clients_list));

$client_ids = implode(",", $clients_list);

	$q="";
	if($destination!="")
		$q=" and city='$destination'";
	
	$query ="select * from client where client_id IN ($client_ids) and client_company_name LIKE '".$name."%' $q order by client_company_name asc";
     $result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_assoc($result))
	{
		$result_value['value'] = $row['client_company_name'];
		$result_value['id'] = $row['client_id'];
		$search[] = $result_value;
	}
	
	echo json_encode($search);
}else{
echo "No Clients Mapped";
}
}


if($autocomplete == "mapping_client_autocomplete"){
	
	$query = "select * from client where status=0 and client_company_name LIKE '".$name."%' order by client_company_name asc";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_assoc($result))
	{
		$result_value['value'] = $row['client_company_name'];
		$result_value['id'] = $row['client_id'];
		$search[] = $result_value;
	}
	
	echo json_encode($search);
}


if($autocomplete == "mapping_client_cus_autocomplete"){
	
$client = $_REQUEST['client'];
	$query = "select * from client where client_id!='$client' and client_company_name LIKE '".$name."%' and client_id NOT IN (select client_id from customer_mapping_lists where mapping_id=(select mapping_id from customer_mapping where client='$client')) order by client_company_name";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_assoc($result))
	{
		$result_value['value'] = $row['client_company_name'];
		$result_value['id'] = $row['client_id'];
		$search[] = $result_value;
	}
	
	echo json_encode($search);
}

?>