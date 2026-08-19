<?php
require_once('include/connect.php');
require_once('include/function.php');

date_default_timezone_set('Asia/Kolkata');
$c_date=date('d-m-Y');
$date = new DateTime();
$c_time=$date->format( 'H:i:s A' );
$c_date_string=strtotime($c_date);

$cmd = $_REQUEST['cmd'];
$created_at = $updated_at = date('d-m-Y');
$updated_by = $created_by = $_SESSION['admin_id'];
date_default_timezone_set('Asia/Kolkata');
$c_date=date('d-m-Y');
$date = new DateTime();
$c_time=$date->format( 'H:i:s A' );
$c_date_string=strtotime($c_date);
$company_id=$_SESSION['company_id'];
$month = date('m');
$year = date('Y');

if($cmd == "get_customer_mapping_details"){
	$out_put = '';
	$id = $_REQUEST['id'];
$query = "select * from customer_mapping where client='".$id."'";
	$result = mysqli_query($conn,$query);
	$row = mysqli_fetch_array($result);
	
	$mapping_query = "select * from customer_mapping_lists where mapping_id='".$row['mapping_id']."'";
	$mapping_result = mysqli_query($conn,$mapping_query);
	$i=1;
	if(mysqli_num_rows($mapping_result) > 0)
	{
    $client_company_name=array();
	while($mapping_row = mysqli_fetch_array($mapping_result)){
		$client = get_client($conn,$mapping_row['client_id']);
		array_push($client_company_name,$client['client_company_name']);
		
	}
	sort($client_company_name);
	for($j=0;$j<=count($client_company_name);$j++){
	    $k=$k+1;
	 
		$out_put .='<tr>
			<td  class="text-center">'.$k.'</td>
			<td>'.$client_company_name[$j].'</td>
			<td class="text-center">
			<input type="hidden" name="mapp_client_id[]" value="'.$mapping_row['client_id'].'" /><a title="Delete" href="#" class="table-actions btn-trash"  id="'.$mapping_row['list_id'].'"><i class="fa fa-trash-o"></i></a></td>
		</tr>';
		
	}
	echo $out_put;
	}
	else
		echo '0';
	
}

if($cmd == "chck_users_email")
{
	$email = $_REQUEST['email'];
	
	$query = "select * from users where email='$email'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	if(mysqli_num_rows($result)>0)
		echo 1;
	else
		echo 0;
	
}


if($cmd == "get_branch_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from branch where branch_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if($cmd =="get_transaction_month_details"){
	$out_put='';
	$month = $_REQUEST['month'];
	$dt=explode("-",$month);	

	if($dt[0]<=3){
		$m=4;
		$m1= 1;
		$y=$dt[1]-1;
		$trans_name = "transaction_".$m1."_".$dt[1];
		$trans_image_name = "transaction_images_".$m1."_".$dt[1];
		$trans_invoice_name = "transaction_invoice_".$m1."_".$dt[1];
	}
	else if(($dt[0]>=4) && ($dt[0]<=6)){
		$m=1;
		$m1= 2;
		$y=$dt[1];
		$trans_name = "transaction_".$m1."_".$dt[1];
		$trans_image_name = "transaction_images_".$m1."_".$dt[1];
		$trans_invoice_name = "transaction_invoice_".$m1."_".$dt[1];
	}
	else if(($dt[0]>=7) && ($dt[0]<=9)){
		$m=2;
		$m1= 3;
		$y=$dt[1];
		$trans_name = "transaction_".$m1."_".$dt[1];
		$trans_image_name = "transaction_images_".$m1."_".$dt[1];
		$trans_invoice_name = "transaction_invoice_".$m1."_".$dt[1];
	}
	else{
		$m=3;
		$m1= 4;
		$y=$dt[1];
		$trans_name = "transaction_".$m1."_".$dt[1];
		$trans_image_name = "transaction_images_".$m1."_".$dt[1];
		$trans_invoice_name = "transaction_invoice_".$m1."_".$dt[1];
	}
	if($_SESSION['role'] =='AD'){
	    	 $query = "select * from transaction_".$m1."_".$dt[1]." where grn_date like '%$month' and invoice_no !='' order by grn_date desc,grn_no desc";
	
	}
	else{
		$query = "select * from transaction_".$m1."_".$dt[1]." where consigner='".$_SESSION['company_id']."' or consignee='".$_SESSION['company_id']."' and grn_date like '%$month' and invoice_no !='' order by grn_date desc,grn_no desc";
}
		$result = mysqli_query($conn,$query);
			$i=1;
		if(mysqli_num_rows($result) > 0)
		{
		while($row = mysqli_fetch_array($result))
		{
			$booking = $row['booking_status'];
            $remarks = $row['remarks'];
			$cancelled_by = get_user($conn,$row['cancelled_by']);
			$updated_at = $row['updated_at'];

			$pkg_q=mysqli_query($conn,"select sum(no_of_pkge) as pkge from transaction_invoice_".$m1."_".$dt[1]." where transaction_id='".$row['transaction_id']."'");
			$pkg_r=mysqli_fetch_array($pkg_q);
			
			$out_put.='<tr>
			<td class="text-center">'.$i.'</td>
			<td>'.$row['grn_no'].'</td>
			<td>'.$row['grn_date'].'</td>
			<td>'.$pkg_r['pkge'].'</td>
			<td>'.get_client_name($conn,$row['consigner']).'</td>
			<td>'.get_client_name($conn,$row['consignee']).'</td>
			<td>'.get_city_name($conn,$row['destination']).'</td>';
			if ($booking == '1') {
				$out_put .= '<td style="color:red;">Consignment Cancelled</td>';
			} else {
				$out_put .=
					'<td>' . get_trans_status($row['status']) . '</td>';
			}
            $out_put .= '<td>';
			$grn_no = $row['grn_no'];
			if($grn_no != ''){
				$screens =  $grn_no;
				$ext = ".jpg";
				$search = $screens.$ext;
				$image_data = array();
				$images = "select screens from pod_files where screens LIKE '%$screens%' ";
				$res = mysqli_query($conn,$images);
				while($pod_row = mysqli_fetch_assoc($res)){
				$imagesd1[] = explode('@@',$pod_row['screens']);
				}
				foreach($imagesd1 as $key => $value1){
					foreach($value1 as $key2 => $value2){
						$filtered_array[] =  $value2;
					}
				}
				$filter_img = preg_grep('/^'.$screens.'.*/', $filtered_array);
				$array_unique = array_unique($filter_img);
				$count = count($array_unique);
				// $count = 1;
			  }if($count == 1)
			$out_put .= '<a title="POD Uploaded"  class="table-actions btn-edit" id='.$row['transaction_id'].'><i class="fa fa-check-circle"></i></a>';
			elseif($count == 2)
		    $out_put .= '<a style="color:green;" title="POD Uploaded"  class="table-actions btn-edit" id='.$row['transaction_id'].'><i class="fa fa-check-circle"></i></a>';
			else
		    $out_put .= '<a title="POD Not Uploaded"  class="table-actions btn-edit" id='.$row['transaction_id'].'><i class="fa fa-times-circle-o"></i></a>';
			

			$out_put .='</td>
			
			<td class="actions center-content ">
			
			
				<div class="action-buttons" style="width: 100%;">
				
                <!--- <a title="Cancel"  class="table-actions btn-edit " id="' . $row['transaction_id'] . '"><i class="fa fa-ban"></i></a> -->';
                if ($booking == '1')
                    $out_put .= '
				    <a title="Info" href="#cancel_grn_popup" class="table-actions show_info_popup"  data-toggle="modal" data-remarks="'.$remarks.'" data-createdby="'.$cancelled_by.'" data-createdat="'.$updated_at.'" id="'.$row['transaction_id'].'" ><i class="fa fa-exclamation-circle"></i></a>
                    <a title="Edit" href="javascript:void(0)" class="table-actions btn-edits disable_action" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-pencil"></i></a>
                    <a class="table-actions disable_action"  href="javascript:void(0)" ><i class="fa fa-print"></i></a>
                    <a class="table-actions disable_action" href="javascript:void(0)" data-status="' . $row['status'] . '" title="Invoice" id="' . $row['transaction_id'] . '" readonly><i class="fa fa-file"></i></a>
                    <a class="table-actions send_invoices disable_action" href="javascript:void(0)" title="Send Invoice" id="send_invoice_d" data-month="' . $m1 . '" data-year="' . $dt[1] . '" data-id="' . $row['transaction_id'] . '" > <i class="fa fa-envelope"></i></a>
                    <a title="Cancel" href="javascript:void(0) disable_action" class="table-actions cancel_booking disable_action" id="' . $row['transaction_id'] . '" ><i  class="fa fa-ban"></i></a>
                    <a title="E-way Attachments" href="javascript:void(0) disable_action" class="table-actions btn-eways disable_action" id="' . $row['transaction_id'] . '"><i class="fa fa-paperclip"></i></a>';
                
                    else
                    $out_put .= '<a title="Edit" href="transactions.php?key=' . md5($row['transaction_id']) . '&m=' . $m1 . '&y=' . $dt[1] . '" class="table-actions btn-edit" id="' . $row['transaction_id'] . '"><i class="fa fa-pencil"></i></a>
                    <a class="table-actions " target="BLANK" href="transaction_pdf.php?month=' . $m1 . '&year=' . $dt[1] . '&id=' . $row['transaction_id'] . '" data-status="' . $row['status'] . '" title="View" id="' . $row['transaction_id'] . '"><i class="fa fa-print"></i></a>
                    <a class="table-actions " target="BLANK" href="gst_invoice_page.php?month=' . $m1 . '&year=' . $dt[1] . '&id=' . $row['transaction_id'] . '" data-status="' . $row['status'] . '" title="Invoice" id="' . $row['transaction_id'] . '"><i class="fa fa-file"></i></a>
                    <a class="table-actions send_invoice" href="#" title="Send Invoice" id="send_invoice" data-month="' . $m1 . '" data-year="' . $dt[1] . '" data-id="' . $row['transaction_id'] . '" > <i class="fa fa-envelope"></i></a>
                    <a title="Cancel" href="#cancel_grn_popup" class="table-actions cancel_booking" id="' . $row['transaction_id'] . '" data-toggle="modal" data-grnid="' . $row['grn_no'] . '" data-tabid="' . $trans_name . '"  ><i class="fa fa-ban "></i></a>
                    <a title="E-way Attachments" href="#eway_popup" class="table-actions btn-eway" data-toggle="modal" id="' . $row['transaction_id'] . '"><i class="fa fa-paperclip"></i></a>
					
				</div>
				
			</td>
		</tr>';

	
			$i++;
		}
	echo $out_put;
		}
	else	
		echo "<tr><td colspan='9' style='padding:10px;text-align:center;font-size:17px;'> No Booking in this Month</td></tr>";
	
}
if($cmd == "get_vehicle_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from vehicle where vehicle_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}

if($cmd == "get_hub_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from hub where hub_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if($cmd == "get_role_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from role where role_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}

if($cmd == "get_train_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from train where train_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
$row['city_name1']= get_city_name($conn,$row['loading_point1']);
$row['city_name2']= get_city_name($conn,$row['loading_point2']);
$row['city_name3']= get_city_name($conn,$row['loading_point3']);
$row['city_name4']= get_city_name($conn,$row['loading_point4']);
	echo json_encode($row);
}
if($cmd == "get_flight_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from flight where flight_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
$row['city_name1']= get_city_name($conn,$row['loading_point1']);
$row['city_name2']= get_city_name($conn,$row['loading_point2']);
$row['city_name3']= get_city_name($conn,$row['loading_point3']);
$row['city_name4']= get_city_name($conn,$row['loading_point4']);
	echo json_encode($row);
}
if($cmd == "get_amount_words"){
	$number = $_REQUEST['val'];
	//$number =  $_POST['rupees'];
	$no = (int)floor($number);
   $point = (int)round(($number - $no) * 100);
   $hundred = null;
   $digits_1 = strlen($no);
   $i = 0;
   $str = array();
   $words = array('0' => '', '1' => 'one', '2' => 'two',
    '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six',
    '7' => 'seven', '8' => 'eight', '9' => 'nine',
    '10' => 'ten', '11' => 'eleven', '12' => 'twelve',
    '13' => 'thirteen', '14' => 'fourteen',
    '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen',
    '18' => 'eighteen', '19' =>'nineteen', '20' => 'twenty',
    '30' => 'thirty', '40' => 'forty', '50' => 'fifty',
    '60' => 'sixty', '70' => 'seventy',
    '80' => 'eighty', '90' => 'ninety');
   $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
   while ($i < $digits_1) {
     $divider = ($i == 2) ? 10 : 100;
     $number = floor($no % $divider);
     $no = floor($no / $divider);
     $i += ($divider == 10) ? 1 : 2;


     if ($number) {
        $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
        $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
        $str [] = ($number < 21) ? $words[$number] .
            " " . $digits[$counter] . $plural . " " . $hundred
            :
            $words[floor($number / 10) * 10]
            . " " . $words[$number % 10] . " "
            . $digits[$counter] . $plural . " " . $hundred;
     } else $str[] = null;
  }
  $str = array_reverse($str);
  $result = implode('', $str);


  $points = ($point) ?
    "" . $words[floor($point / 10) * 10] . " " . 
          $words[$point = $point % 10] : ''; 

  if($points != ''){        
  echo ucfirst($result) . "Rupees  " . $points . " Paise Only";
} else {

    echo ucfirst($result) . "Rupees Only";
}
}
if($cmd == "get_delivery_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from delivery_status where delivery_status_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if($cmd == "get_city_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from city where city_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if($cmd == "get_state_details")
{
	$tbl_id = $_REQUEST['tbl_id'];
	
	$query = "select * from state where state_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
}
if($cmd == "get_city_name"){
	$state_id = $_REQUEST['state_id'];
	
	$out_put.='<option value="">Select City</option>';
	$query = "select * from city where status=0 and state='".$state_id."' order by city_name";
	$result = mysqli_query($conn,$query);
	while($row =mysqli_fetch_array($result))
	{
	$out_put .='<option value='.$row['city_id'].'>'.$row['city_name'].'</option>';
	}
	echo $out_put;
}
if($cmd == "get_mode_details"){
	$tbl_id = $_REQUEST['tbl_id'];
	$query = "select * from mode_of_transportation where mode_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
	
}

if($cmd== "get_client_user_details"){
	$tbl_id = $_REQUEST['tbl_id'];
	 /*  $query = "select  * from client_branch where client_branch_id IN (select branch_name from users where user_id='$tbl_id')"; */
	 $query = "select * from client where client_id =(select company_name from users where user_id='$tbl_id')";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_assoc($result);
	$row['state_name'] = get_statename($conn,$row['state']);
	$row['city_name'] = get_city_name($conn,$row['city']);
	echo json_encode($row);
	
}

if($cmd== "get_client_details"){
	$tbl_id = $_REQUEST['tbl_id'];
	 $query = "select * from client where client_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_assoc($result);
	echo json_encode($row);
	
}

if($cmd== "get_client_details_consignment"){
	$tbl_id = $_REQUEST['tbl_id'];
	$consignor = $_REQUEST['consignor'];
	if(!empty($consignor)){
	    if($tbl_id == '3631'){
	        $query_code=mysqli_query($conn,"select * from client where client_id='".$tbl_id."'");
			$r_code=mysqli_fetch_array($query_code);
			$query_max=mysqli_query($conn,"select * from transaction_log where client_id='".$tbl_id."'");
			$r_max=mysqli_fetch_array($query_max);
			$id=$r_max['grn_id']+1;
			$billing_code = $r_code['billing_code'];
			$grn_no=$billing_code.sprintf("%05d",$id);
			$grn_id =$id;
	    }
		else{
	        $query_code=mysqli_query($conn,"select * from client where client_id='".$tbl_id."'");
			$r_code=mysqli_fetch_array($query_code);
			$query_max=mysqli_query($conn,"select * from transaction_log where client_id='".$tbl_id."'");
			$r_max=mysqli_fetch_array($query_max);
			$id=$r_max['grn_id']+1;
			$billing_code = $r_code['billing_code'];
			$grn_no=$billing_code.sprintf("%05d",$id);
			$grn_id =$id;
	    }
	}
	else{
	    $grn_no= "";
	    $grn_id ="";
	}
	 $query = "select * from client where client_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_assoc($result);
	$row['state']=$row['state']>0 ? get_statename($conn,$row['state']) : "";
	$row['city_name']=$row['city']>0 ? get_city_name($conn,$row['city']) : "";
	$row['grn_no']=$grn_no;
	$row['grn_id']=$grn_id;
	echo json_encode($row);
	
}


if($cmd == "get_client"){
	$tbl_id = $_REQUEST['id'];
	$out_put ='<option value=""> -- Select Consignee</option>';
	
	 $query = "select * from client where client_id!='$tbl_id' and client_id NOT IN (select client_id from customer_mapping_lists where mapping_id=(select mapping_id from customer_mapping where client='$tbl_id')) order by client_company_name";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_array($result)){
		$out_put .='<option value='.$row['client_id'].'>'.$row['client_company_name'].'</option>';
	}
	echo $out_put;
}
if($cmd == "get_consignment_details"){
	$tbl_id = $_REQUEST['tbl_id'];
	$query = "select * from consignment_mode where consignment_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
	
}
if($cmd =="get_package_details"){
	$tbl_id = $_REQUEST['tbl_id'];
	$query = "select * from package where package_id='$tbl_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	echo json_encode($row);
	
}
if($cmd == "get_consignee"){
	$tbl_id = $_REQUEST['id'];
	$out_put ='<option value="">--Select Consignee--</option>';


	 $city_query ="select * from customer_mapping_lists where mapping_id IN (select mapping_id from customer_mapping where client='".$tbl_id."') and status='0' order by client_company_name asc";
	$city_result = mysqli_query($conn,$city_query);
	while($row = mysqli_fetch_array($city_result))
	{
		$client = get_client($conn,$row['client_id']);
		
		$out_put .='<option value='.$row['client_id'].'>'.$client['client_company_name'].'</option>';
	}
	echo $out_put;
}

if($cmd == "get_consignee1"){
	$tbl_id = $_REQUEST['id'];
	$destination = $_REQUEST['destination'];
	$q="";
	if($destination!="")
		$q=" and city='$destination'";
	$out_put ='<option value="">--Select Consignee--</option>';


	$city_query ="select * from client where client_id IN (select client_id from customer_mapping_lists where mapping_id IN (select mapping_id from customer_mapping where client='".$tbl_id."') and status='0') $q order by client_company_name asc";
	$city_result = mysqli_query($conn,$city_query);
	while($row = mysqli_fetch_array($city_result))
	{
				
		$out_put .='<option value='.$row['client_id'].'>'.$row['client_company_name'].'</option>';
	}
	echo $out_put;
}

if($cmd == "get_consignor"){
	$tbl_id = $_REQUEST['id'];
	$out_put ='<option value="">--Select Consignor--</option>';


	$city_query ="select * from customer_mapping where mapping_id IN (select mapping_id from customer_mapping_lists where client_id='".$tbl_id."') and status='0'";
	$city_result = mysqli_query($conn,$city_query);
	while($row = mysqli_fetch_array($city_result))
	{
		$client = get_client($conn,$row['client']);
		
		$out_put .='<option value='.$row['client'].'>'.$client['client_company_name'].'</option>';
	}
	echo $out_put;
}

if($cmd == "get_destination"){
	$tbl_id = $_REQUEST['id'];
	$out_put ='<option value="">--Select Destination--</option>';
	$city_query ="select * from city where status=0 and city_id!='$tbl_id' order by city_name";
	$city_result = mysqli_query($conn,$city_query);
	while($city_row = mysqli_fetch_array($city_result))
	{
		$out_put .='<option value='.$city_row['city_id'].'>'.$city_row['city_name'].'</option>';
	}
	echo $out_put;
}



if($cmd == "get_destination_consignor"){
	$res_val=Array();
	$tbl_id = $_REQUEST['id'];
	$out_put ='<option value="">--Select Destination--</option>';
	$city_query ="select * from city where status=0 and city_id!='$tbl_id' order by city_name asc";
	$city_result = mysqli_query($conn,$city_query);
	while($city_row = mysqli_fetch_array($city_result))
	{
		$out_put .='<option value='.$city_row['city_id'].'>'.$city_row['city_name'].'</option>';
	}
	$res_val['destination']=$out_put;
	
	$out_put ='<option value="">--Select Consignor--</option>';
	$city_query ="select * from client where status=0 and city='$tbl_id' order by client_company_name asc";
	$city_result = mysqli_query($conn,$city_query);
	while($city_row = mysqli_fetch_array($city_result))
	{
		$out_put .='<option value='.$city_row['client_id'].'>'.$city_row['client_company_name'].'</option>';
	}
	$res_val['consignor']=$out_put;
	
	$out_put ='<option value="">--Select Vehicle--</option>';
	$hub_id='';
	$q1=mysqli_query($conn,"select hub_id from hub where FIND_IN_SET('$tbl_id',covered_cities)");
	while($r1=mysqli_fetch_array($q1))
	{
		$hub_id .=$r1['hub_id'].',';
	}
	$hub_id=rtrim($hub_id,",");
	$city_query ="select * from vehicle where status=0 and FIND_IN_SET('$hub_id',branch_id) order by vehicle_reg_no";
	$city_result = mysqli_query($conn,$city_query);
	while($city_row = mysqli_fetch_array($city_result))
	{
		$out_put .='<option value='.$city_row['vehicle_id'].'>'.$city_row['vehicle_reg_no'].'</option>';
	}
	$res_val['vehicle']=$out_put;
	
	
	echo json_encode($res_val);
}


if($cmd =="get_pickup_report_details"){
	$out_put='';
	extract($_REQUEST);
	$company_id = get_company($conn,$_SESSION['user_id']);
	if($_SESSION['role'] == 'CL'){
		if($report_type=="MONTHLY")
		{
			$add_q="and grn_date LIKE '%$month'";
		$dt=explode("-",$month);	
		$y=$dt[1];
		$m=$dt[0];
		}
		else
		{
			$add_q="and grn_date='$date'";
		$dt=explode("-",$date);	
		 $y=$dt[2];
		$m=$dt[1];
			
		}
	}
	else{
		if($report_type=="MONTHLY")
		{
			$add_q="grn_date LIKE '%$month'";
		$dt=explode("-",$month);	
		$y=$dt[1];
		$m=$dt[0];
		}
		else
		{
			$add_q="grn_date='$date'";
		$dt=explode("-",$date);	
		 $y=$dt[2];
		$m=$dt[1];
			
		}
	}
	if($m<4)
		$m1= 1;
	else if(($m>3) && ($m<7))
		$m1= 2;
	else if(($m>6) && ($m<10))
		$m1= 3;		
	else
		$m1= 4;
		
	if($client_wise_report != ""){
		$add_q .= "and consigner='$client_wise_report'";
	}	
	if($mode_of_trasport!="")
		$add_q .=" and mode_of_transportation='$mode_of_trasport'";
	if($origin!="")
		$add_q .=" and origin='$origin'";
	if($destination!="")
		$add_q .=" and destination='$destination'";
	if($status!="")
		$add_q .=" and status='$status'";
		
		
		
	$i=1;
	if($_SESSION['role']=='CL'){
		 $query = "select * from transaction_".$m1."_".$y." where consigner='".$company_id."' $add_q";
	}
	else{
		 $query = "select * from transaction_".$m1."_".$y." where  $add_q";
	}
	$result = mysqli_query($conn,$query);	

	$out_put .= '<style>tr { height: 30px; }</style>
	<table class="table table-bordered table-striped" id="report_table" style="width:100%">
	<thead>
	<th class="table-title" width="60px">S.No</th>
		<th class="table-title">GRN NO</th>
		<th class="table-title" width="100px">GRN Date</th>
		<th class="table-title" width="100px">Invoice No.</th>
		<th class="table-title"  width="100px">No.of.Pkgs</th>
		<th class="table-title"  width="80px">Weight</th>
		<th class="table-title"  width="80px">Mode</th>
		<th class="table-title">Origin</th>
		<th class="table-title" >Consignor </th>
		<th class="table-title" >Consignee </th>
		<th class="table-title" width="120px">Destination</th>
		<th class="table-title" width="80px">Status</th>      
	</thead>
	<tbody id="get_month_details">';
	

	if(mysqli_num_rows($result) > 0)
	{
	while($row = mysqli_fetch_array($result))
	{
        $booking = $row['booking_status'];
		$query1 = "select sum(no_of_pkge) as no_of_pkge,party_invoice_no,gross_weight from transaction_invoice_".$m1."_".$y." where transaction_id='".$row['transaction_id']."'";
		$result1 = mysqli_query($conn,$query1);	
		$row1 = mysqli_fetch_array($result1);
				
		$out_put.='<tr>
		<td class="text-center">'.$i.'</td>
		<td>'.$row['grn_no'].'</td>
		<td>'.$row['grn_date'].'</td>
		<td>'.$row1['party_invoice_no'].'</td>
		<td>'.$row1['gross_weight'].'</td>
		<td>'.$row1['no_of_pkge'].'</td>
		<td>'.get_mode($conn,$row['mode_of_transportation']).'</td>
		<td>'.get_city_name($conn,$row['origin']).'</td>
		<td>'.get_client_name($conn,$row['consigner']).'</td>
		<td>'.get_client_name($conn,$row['consignee']).'</td>
		<td>'.get_city_name($conn,$row['destination']).'</td>';
		if ($booking == '1') {
            $out_put .= '<td style="color:red;">Consignment Cancelled</td>';
        }else{
            $out_put .= '<td>'.get_trans_status($row['status']).'</td>';
        }
       
		$out_put .= '</tr>';

		$i++;
	}
	$out_put .='</tbody>
		</table>';
		echo $out_put;
	}
	else{
		echo '<tr>
		<td class="text-center" colspan="10"> No Records Found For this Search</td></tr>';
	}
		
		
		
}

if($cmd == "get_payment_report_details"){
	
    $report_type = $_REQUEST['report_type'];
    $client_wise_report = $_REQUEST['client_wise_report'];
    $month = $_REQUEST['month'];
    $date = $_REQUEST['date'];
    if($report_type == 'MONTHLY'){

       $dates = $month;
       $timestamp = $dates;
       $timestamp = DateTime::createFromFormat('m-Y', $timestamp);
       $newDate = $timestamp->format('Y-m');
       $add_qw = "and created_at like '%$newDate%'";
    }else if($report_type == "DAILY"){
       
       $dates = $date;
       
       $timestamp = $dates;
       $timestamp = DateTime::createFromFormat('d-m-Y', $timestamp);
       $newDate = $timestamp->format('Y-m-d');
       $add_qw = "and created_at like '%$newDate%'";
    }else{
       $add_qw = '';
    }

   if ($client_wise_report != "") {
       $add_q .= "client_id='$client_wise_report'";
   }

   
   $query = "select * from razorpay_payment where client_id ='$client_wise_report' ".$add_qw." order by created_at desc";
   $result = mysqli_query($conn,$query);
           $i=1;
           $out_put .= '<table id="dataTable1" class="table table-striped table-bordered display" style="width:100%">
           <thead>
                <tr>
                     <th class="table-title" >S.No</th>
                     <th class="table-title">Payment Date</th>
                     <th class="table-title">GRN No</th>
                     <!-- <th class="table-title">Order ID</th> -->
                     <th class="table-title">Payment ID</th>
                     <th class="table-title">Invoice Amount</th>
                     <th class="table-title">Paid Amount</th>
                     <th class="table-title">Due Amount</th>
                     <th class="table-title">Status</th>

                </tr>
           </thead>
           <tbody>';
       if(mysqli_num_rows($result) > 0)
       {
       $total_invoice_amt = 0;
       $total_paid_amt = 0;
       $total_due_amt = 0;
       while($row = mysqli_fetch_array($result))
       {
           $timestamp = $row['created_at'];
           $timestamp = DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);
           $newDate = $timestamp->format('d-m-Y H:i:s');
           $total_invoice_amt += $row['amount'];
           $total_paid_amt += $row['paid'];
           $total_due_amt += $row['balance'];
           $out_put.='<tr>
           <td class="text-center">'.$i.'</td>
           <td>'.$newDate.'</td>
           <td>'.$row['grn_no'].'</td>
           <td>'.$row['razorpayPaymentId'].'</td>
           <td>&#x20b9;'.number_format($row['amount'], 2, '.', '').'</td>
           <td>&#x20b9;'.number_format($row['paid'], 2, '.', '').'</td>
           <td>&#x20b9;'.number_format($row['balance'], 2, '.', '').'</td>
           <td>'.$row['paymentStatus'].'</td>';
           
           
       
       $output .='
       </tr>
       ';
           
   
           $i++;
       }
       $out_put .= '</tbody>
       <tfoot style="color:#0A1E3D">
                             <tr>
                                  <th colspan="3"></th>
                                  <th >Total</th>
                                  <th>&#x20b9;'.number_format($total_invoice_amt, 2, '.', '').'</th>
                                  <th>&#x20b9;'.number_format($total_paid_amt, 2, '.', '').'</th>
                                  <th>&#x20b9;'.number_format($total_due_amt, 2, '.', '').'</th>
                                  <th></th>
                             </tr>
                        </tfoot>

       </table>';
   
       }
   else{
       $out_put1 .='<table id="employee_data" class="table table-striped table-bordered display" style="width:100%">
       <thead>
                <tr>
                     <th>S.No</th>
                     <th>Payment Date</th>
                     <th>GRN No</th>
                     <!-- <th>Order ID</th> -->
                     <th>Payment ID</th>
                     <th>Invoice Amount</th>
                     <th>Paid Amount</th>
                     <th>Due Amount</th>
                     <th>Status</th>

                </tr>
           </thead>
           <tbody>
       <tr><td colspan="9" style="padding:10px;text-align:center;font-size:17px;"> No Transactions in this Month</td></tr>
       </tbody>
       </table>';
   }	
       
   echo $out_put;
   
}



if($cmd =="get_grn_for_status"){
	$out_put='';
	$i=1;
	$count=1;
	$res_val=array();
	$grn_no=$_REQUEST['grn_no'];
	$status=$_REQUEST['status'];
	$slno=$_REQUEST['slno'];
	$query2 = "SELECT * FROM transaction_tbls";
	$result2 = mysqli_query($conn,$query2) or die(mysqli_error($conn));
	while($row2 = mysqli_fetch_assoc($result2))
	{			
	
	  $query = "select * from transaction_".$row2['table_name']." where grn_no='$grn_no' and status < '$status' and booking_status = '' ";
	
	$result = mysqli_query($conn,$query);	
	if(mysqli_num_rows($result) > 0)
	{
		$count++;
	while($row = mysqli_fetch_array($result))
	{
		 $query1 = "select sum(no_of_pkge) as no_of_pkge from transaction_invoice_".$row2['table_name']." where transaction_id='".$row['transaction_id']."'";
		$result1 = mysqli_query($conn,$query1);	
		$row1 = mysqli_fetch_array($result1);
				
		$out_put.='<tr>
		<td class="text-center">'.$slno.'</td>
		<td><input type="hidden" name="grn_id[]" class="grn_id" id="grn_id_'.$i.'" value="'.$row['grn_id'].'" />'.$row['grn_no'].'</td>
		<td><input type="hidden" name="client_id[]" class="client_id" id="client_id_'.$i.'" value="'.$row['client_id'].'" />'.$row['grn_date'].'</td>
		<td>'.$row1['no_of_pkge'].'</td>
		<td>'.get_mode($conn,$row['mode_of_transportation']).'</td>
		<td>'.get_client_name($conn,$row['consigner']).'-'.get_city_name($conn,$row['origin']).'</td>
		<td>'.get_client_name($conn,$row['consignee']).'-'.get_city_name($conn,$row['destination']).'</td>
		<td>'.get_trans_status($row['status']).'</td>
		<td style="text-align:  center;"><button class="btn btn-danger delete" type="button" data-id="'.$row['grn_id'].'">Remove</button></td>
		</tr>';

		$i++;
	}
	
	}
	
	}
	$res_val['data']=$out_put;
	$res_val['status']=0;
	
	if($count==1)
	{
	$res_val['data']="GRN No Not Match / Booking Cancelled";
	$res_val['status']=1;	
	}
	
	
	echo json_encode($res_val);
}

//Arrival Report
if($cmd =="get_arrival_report_details"){
	$out_put='';
	extract($_REQUEST);
	$company_id = get_company($conn,$_SESSION['user_id']);
	if($report_type=="MONTHLY")
	{
		$add_q="and grn_date LIKE '%$month'";
	$dt=explode("-",$month);	
	$y=$dt[1];
	$m=$dt[0];
	}
	else
	{
		$add_q="and grn_date='$date'";
	$dt=explode("-",$date);	
	 $y=$dt[2];
	$m=$dt[1];
		
	}
	if($m<4)
		$m1= 1;
	else if(($m>3) && ($m<7))
		$m1= 2;
	else if(($m>6) && ($m<10))
		$m1= 3;		
	else
		$m1= 4;
		
	if($mode_of_trasport!="")
		$add_q .=" and mode_of_transportation='$mode_of_trasport'";
	if($origin!="")
		$add_q .=" and origin='$origin'";
	if($destination!="")
		$add_q .=" and destination='$destination'";
	if($status!="")
		$add_q .=" and status='$status'";
		
		
		
	$i=1;
	 echo$query = "select * from transaction_".$m1."_".$y." where consignee='".$company_id."' $add_q";
	$result = mysqli_query($conn,$query);	
	if(mysqli_num_rows($result) > 0)
	{
	while($row = mysqli_fetch_array($result))
	{
		
		 $query1 = "select sum(no_of_pkge) as no_of_pkge,party_invoice_no,gross_weight from transaction_invoice_".$m1."_".$y." where transaction_id='".$row['transaction_id']."'";
		$result1 = mysqli_query($conn,$query1);	
		$row1 = mysqli_fetch_array($result1);
				
		$out_put.='<tr>
		<td class="text-center">'.$i.'</td>
		<td>'.$row['grn_no'].'</td>
		<td>'.$row['grn_date'].'</td>
		<td>'.$row1['party_invoice_no'].'</td>
		<td>'.$row1['gross_weight'].'</td>
		<td>'.$row1['no_of_pkge'].'</td>
		<td>'.get_mode($conn,$row['mode_of_transportation']).'</td>
		<td>'.get_city_name($conn,$row['origin']).'</td>
		<td>'.get_client_name($conn,$row['consigner']).'</td>
		<td>'.get_client_name($conn,$row['consignee']).'</td>
		<td>'.get_city_name($conn,$row['destination']).'</td>
		<td>'.get_trans_status($row['status']).'</td>
		</tr>';

		$i++;
	}
	echo $out_put;
	echo $query;
	}
	else
		echo '<tr>
		<td class="text-center" colspan="10"> No Records Found For this Search</td></tr>';
	
}


if($cmd == "get_gracious_branch"){
	
	$out_put ='<option value="">--Select Branch--</option>';
	$city_query ="select * from branch where status=0 order by branch_name";
	$city_result = mysqli_query($conn,$city_query);
	while($city_row = mysqli_fetch_array($city_result))
	{
		$out_put .='<option value='.$city_row['branch_id'].'>'.$city_row['branch_name'].'</option>';
	}
	echo $out_put;
}



if($cmd == "get_client_branch"){
	$id=$_REQUEST['id'];
	$out_put ='<option value="">--Select Branch--</option>';
	$city_query ="select * from client_branch where status=0 and company_id='$id' order by branch_name";
	$city_result = mysqli_query($conn,$city_query);
	while($city_row = mysqli_fetch_array($city_result))
	{
		$out_put .='<option value='.$city_row['client_branch_id'].'>'.$city_row['branch_name'].'</option>';
	}
	echo $out_put;
}


if($cmd == "get_existing_attchment"){
	$table_name = $_REQUEST['table_name'];
	$transaction_id = $_REQUEST['transaction_id'];
$out_put ='<div style="border: 1px solid;"><br>';
	
	 $query = "select * from $table_name where transaction_id='$transaction_id'";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_array($result)){
		
$out_put .='<div class="col-md-offset-1 col-md-5">
<label class="control-label">Eway Bill No '.$row['eway_bill_no'].':</label>
			  		<label class="control-label">Date Of Issue '.date('d-m-Y',strtotime($row['issue_date'])).':</label>
			  		<label class="control-label">Date Of Expire '.date('d-m-Y',strtotime($row['expire_date'])).':</label>
			  	</div>
			  	<div class="col-md-offset-1 col-md-5">
		\t  \t\t<label class=\"control-label\">E-way Attachments:</label><br>
			  		<label class="control-label"> '.$row['attachment'].' <a href="eway/'.$row['attachment'].'" target="BLANK" ><img src="images/Pdf1.png" id="eway_image_src" data-val="'.$row['attachment'].'" width="20px" /> </a> </label>  </br>
</br></br>
			  	</div>';
			  	

	}

	$out_put .='<div class="modal-footer" style="text-align: center;">
				<button class="btn btn-primary btn-new"  type="button" id="new_eway">Add New</button></div>

</div>';

if(mysqli_num_rows($result) > 0)
	echo $out_put;
else
	echo 0;
}

if($cmd == "get_existing_invoice_attchment"){
	$table_name = $_REQUEST['table_name'];
	$transaction_id = $_REQUEST['transaction_id'];
$out_put ='<div style="border: 1px solid;"><br>';
	
	 $query = "select * from $table_name where transaction_id='$transaction_id' and status=0";
	$result = mysqli_query($conn,$query) or die(mysqli_error($conn));
	while($row = mysqli_fetch_array($result)){
		
$out_put .='

			  	<div class="col-md-offset-1 col-md-5">
		\t  \t\t<label class=\"control-label\">Invoice Attachments:</label><br>
			  		<label class="control-label"> '.$row['attachment'].' <a href="invoice_image/'.$row['attachment'].'" target="BLANK" ><img src="images/Pdf1.png" id="eway_image_src" data-val="'.$row['attachment'].'" width="20px" /> </a> </label>  </br>
</br></br>
			  	</div>';
			  	

	}

	$out_put .='<div class="modal-footer" style="text-align: center;">
				</div>

</div>';


if(mysqli_num_rows($result) > 0)
	echo $out_put;
else
	echo 0;
}
if($cmd == 'get_notification'){
	//echo "hello";
	if(isset($_POST['view'])){
		if($_POST['view'] != ''){
			
			$update_inquiry_sts = mysqli_query($conn,"UPDATE user_inquiry_list set status=1 where status=0"); 
		}
		$get_inq_data = mysqli_query($conn,"select *from user_inquiry_list order by id DESC LIMIT 5");
		$output = '';
		if(mysqli_num_rows($get_inq_data)>0){
			while($row_notify = mysqli_fetch_assoc($get_inq_data))
			{
				$output .='<li>
				<a href="user-inquiry.php">
				<strong>'.$row_notify['consignor_name'].'</strong><br/>
				<small><em>'.$row_notify['booking_id'].'</em></small><br/>
				<small><em>User Inquiry Page</em></small>
				</a>
				</li>
				';

			}
			
		}else{
			$output .= '<li><a href="#" class="text-bold text-italic">No Notification
			 Found</a></li>';
		}
		$status = mysqli_query($conn,"SELECT *FROM `user_inquiry_list` where status = 0");
		$count = mysqli_num_rows($status);
		

		$data =array(
		'notification' => $output,
		'unseen_notification' => $count
		);
		echo json_encode($data);

		

	}
}
if($cmd == 'get_notification_rfp'){
	//echo "hello";
	if(isset($_POST['view'])){
	//echo "view";
        
		if($_POST['view'] != ''){
			//echo "view1";
			$update_rfp_sts = mysqli_query($conn,"UPDATE user_pickup set `status`=1 where `status`=3"); 
		}
		$get_rfp_data = mysqli_query($conn,"select *from user_pickup order by pickup_id DESC LIMIT 5");
		$output = '';
		if(mysqli_num_rows($get_rfp_data)>0){
			while($row_notify1 = mysqli_fetch_assoc($get_rfp_data))
			{
				$output .='<li>
				<a href="user-requestpickup-list.php">
				<strong>'.$row_notify1['consignor_name'].'</strong><br/>
				<small><em>'.$row_notify1['pickup_ref_id'].'</em></small><br/>
				<small><em>RFP Page</em></small>
				</a>
				</li>
				';

			}
			
		}else{
			$output .= '<li><a href="#" class="text-bold text-italic">No Notification
			 Found</a></li>';
		}
		$status = mysqli_query($conn,"SELECT *FROM `user_pickup` where status = 3");
		$count = mysqli_num_rows($status);
		

		$data =array(
		'notification' => $output,
		'unseen_notification' => $count
		);
		echo json_encode($data);

		

	}
}
?>
