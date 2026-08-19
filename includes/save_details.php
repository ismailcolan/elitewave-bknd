	<?php
	require_once("connect.php");
	require_once '../web/include/function.php';
	$form_name = $_POST['form_name'];
	//user login detials
	$created_at=$updated_at=date('d-m-Y');
	$out_put=Array();

	if($form_name == "quote_request"){

		$q1=mysqli_query($conn,"select max(quote_id) as id from quote");
		$r1=mysqli_fetch_array($q1);
		$id = $r1['id']+1;	
		$request_no="REQ".sprintf("%04d", $id);
		$company = $_POST['company'];
		$name = $_POST['name'];
		$email = $_POST['email'];
		$mobile = $_POST['mobile'];
		$origin = $_POST['origin'];
		$destination = $_POST['destination'];
		$mode = $_POST['mode'];
		$type = $_POST['type'];
		$weight = $_POST['weight'];
		$description = $_POST['description'];
		
		$query = "INSERT INTO `quote`(`request_no`, `company`, `contact_name`, `email`, `mobile`, `from`, `to`, `mode_id`, `package_id`, `weight`, `description`, `status`, `created_at`, `created_by`) VALUES ('$request_no','$company','$name','$email','$mobile','$origin','$destination','$mode','$type','$weight','$description','0','$created_by','0')";
		$result = mysqli_query($conn,$query) or die(mysqli_error());
		if($result)
					   $out_put['request_no']=$request_no;
			
		else
			$out_put['request_no']='0';

			echo json_encode($out_put);
	}
	if($form_name == "add_pickup"){
		
		$company = $_POST['company'];
		$contact_person = $_POST['contact_person'];
		$email = $_POST['email'];
		$contact_no = $_POST['contact_no'];
		$origin = $_POST['origin'];
		$destination = $_POST['destination'];
		$mode = $_POST['mode_of_transport'];
		$no_of_package = $_POST['no_of_package'];
		$package = $_POST['package_type'];
		$approx_weight = $_POST['approx_weight'];
		$description = $_POST['description'];
		
		
		$select_query = "select max(pickup) as id from pickup where status=0";
		$select_result = mysqli_query($conn,$select_query);
		$select_row = mysqli_fetch_array($select_result);
		$id = $select_row['id']+1;
		$id1 = sprintf("%05d",$id);
		$pickup_id = "RFP/".$id1;
		
		$query = "INSERT INTO `pickup`(`pickup_ref_id`, `pickup`, `company`, `contact_person`, `email`, `contact_no`, `origin`, `destination`, `mode`, `no_of_package`, `package`, `approx_weight`, `created_at`,`description`, `status`) VALUES ('".$pickup_id."','".$id."','".$company."','".$contact_person."','".$email."','".$contact_no."','".$origin."','".$destination."','".$mode."','".$no_of_package."','".$package."','".$approx_weight."','".$created_at."','".$description."','0')";
		$result = mysqli_query($conn,$query);
		
		
		if($result)
		{
			 $out_put['pickup_ref_id']=$pickup_id ;
		}	
		else{
			$out_put['pickup_ref_id']='0';
		}

	   echo json_encode($out_put);
			

	}


if($form_name == "track"){


		$grn_no = $_POST['grn_no'];
		$party_invoice_no = $_POST['party_invoice_no'];
	$out_put='';	
		
$tbl=$tbl_inv='';
	$query2 = "SELECT * FROM transaction_tbls";
	$result2 = mysqli_query($conn,$query2) or die(mysqli_error($conn));
	while($row2 = mysqli_fetch_assoc($result2))
	{			
		$tbl .="transaction_".$row2['table_name'].",";
		$tbl_inv .="transaction_invoice_".$row2['table_name'].",";
	}
	 $tbl=rtrim($tbl,",");
	 $tbl_inv=rtrim($tbl_inv,",");
	 $query = "select * from $tbl where grn_no='$grn_no' and transaction_id IN (select transaction_id from  $tbl_inv where party_invoice_no='$party_invoice_no')";
	$result = mysqli_query($conn,$query);	
	$grnr=mysqli_fetch_array($result);
	
	if(mysqli_num_rows($result) > 0)
	{
		$out_put .= ' <div class="mt-5 mb-5 text-center">
                    <h2>Tracking Status</h2>
                </div><br/>';
		extract($grnr);
				
  $status=Array();
  array_push($status,1);
   $query="select * from transaction_status_log where grn_no='$grn_no'";
  $result=mysqli_query($conn,$query);
  while($row=mysqli_fetch_array($result))
  {
	
if(!in_array($row['from_status'], $status))
  array_push($status,$row['from_status']);
	
if(!in_array($row['to_status'], $status))
  array_push($status,$row['to_status']);
	 
  }
   $count=1; 
  $max=max($status);
  $out_put .='<div class="status-progress">';
  for($i=1;$i<9;$i++)
  {
	if(in_array($i,$status))
	{
		
		if($i!=1)
	$out_put .='<span class="bar"></span>';
	$out_put .= '<div class="circle"><span class="label">'.$i.'</span><span class="title">'.get_trans_status($i).'</span></div>';
	$count++;
	}
	else if($i>$max)
	{
	$out_put .= '<span class="bar"></span>';
	$out_put .= '<div class="circle"><span class="label">'.$i.'</span><span class="title">'.get_trans_status($i).'</span></div>';
		
		
	}
  }
 $out_put .='</div>';
 
	 $query3="select sum(no_of_pkge) as no_of_pkge from  $tbl_inv where transaction_id='$transaction_id'";
  $result3=mysqli_query($conn,$query3);
  $row3=mysqli_fetch_array($result3);	
		
  
$out_put .= '<br><br><br><br><br><br>
<div class="row col-md-5 col-md-offset-3 custyle">
		    <table class="table table-bordered">
		            <tbody><tr>
		                <td>GRN No.</td>
		                <td>'.$grn_no.'</td>
		              </tr>
		            <tr>
		                <td>GRN Date</td>
		                <td>'.$grn_date.'</td>
		            </tr>
		            <tr>
		                <td>Consignor</td>
		                <td>'.$consigner.'</td>
		           </tr>
		           <tr>
		                <td>Consignee</td>
		                <td>'.$consignee.'</td>
		           </tr>
		           <tr>
		                <td>Mode of Transport</td>
		                <td>'.$mode_of_transportation.'</td>
		           </tr>
		           <tr>
		                <td>No. of Packages</td>
		                <td>'.$row3['no_of_pkge'].'</td>
		           </tr>
		           <tr>
		                <td>Payment Mode</td>
		                <td>'.$mode_of_consignment.'</td>
		           </tr>
		           <tr>
		                <td>Status</td>
		                <td>'.get_trans_status($max).'</td>
		           </tr>
		    </tbody>
			</table>
		    </div>';
		echo $out_put;	
	 } 
	
		
	}


	?>