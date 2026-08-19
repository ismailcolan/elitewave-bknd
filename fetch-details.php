<?php
session_start();
require_once("web/include/connect.php");
require_once("user/include/user-function.php");
//$con = mysqli_connect("localhost",'root','','bookconsignment');
$cmd = $_REQUEST['cmd'];
if($cmd == 'get_consignee'){
    $tbl_id = $_REQUEST['tbl_id'];
    $sql = mysqli_query($conn,"SELECT *FROM client where client_id = '$tbl_id'");
    $row = mysqli_fetch_array($sql);
    echo json_encode($row);
}
if($cmd == 'get_consignor'){
    $tbl_id = $_REQUEST['tbl_id'];
    $sql = mysqli_query($conn,"SELECT *FROM client where client_id = '$tbl_id'");
    $row = mysqli_fetch_array($sql);
    echo json_encode($row);
}


if($cmd == 'get_user_email'){

    $email = $_REQUEST['email'];

    $query = "SELECT *FROM users where email = '$email'";
    $result = mysqli_query($conn,$query)  or die(mysqli_error($con));
    if(mysqli_num_rows($result)>0){
        echo 1;
    
    }else{
        echo 0;
    }


}

if($cmd == 'get_new_user_detail'){
    $tbl_id = $_REQUEST['tbl_id'];
    $sql = mysqli_query($conn,"SELECT *FROM user_inquiry_list where `user_id` = '$tbl_id' ");
    $row = mysqli_fetch_array($sql);
    echo json_encode($row);
}
if($cmd == 'get_booking_details'){
    $output = '';
    $tbl_id = $_REQUEST['tbl_id'];   
    $date = $_REQUEST['grn_date'];

    // $sql = mysqli_query($conn,"SELECT *FROM `user_inquiry_list` where `user_id` = '$tbl_id' ");
    // while($row = mysqli_fetch_array($sql)){
    //     $grn_no = $row['booking_id'];
    //     $consingee_name = $row['consignee_name'];
    //     $consingee_address = $row['consignee_address'];
    //     $consingee_city = $row['consignee_city'];
    //     $consingee_contact = $row['consignee_contact'];
    //     $shipping_mode = $row['shipping_mode'];
    //     $pay_mode = $row['pay_mode'];
    //     $no_of_package = $row['no_of_package'];
    //     $length = $row['length'];
    //     $width = $row['width'];
    //     $height = $row['height'];
    //     $kgs = $row['kgs'];
    //     $attachment = $row['attchment'];
    //     $booking_date = $row['created_at'];
   // $date = date('d-m-Y');
    $my = date('m-Y');
    $dt = (explode("-",$date));

  //   var_dump($dt); //array(3) { [0]=> string(2) "03" [1]=> string(2) "08" [2]=> string(4) "2021" }
    if($dt[1]<=3){
         $m1 =1;
         $y = $dt[2];
         $trans_name="transaction_".$m1."_".$y;
         $trans_images="transaction_images_".$m1."_".$y;
         $trans_invoice="transaction_invoice_".$m1."_".$y;
         //echo "First Quarter";

    }else if(($dt[1]>=4) && ($dt[1]<=6)){
         $m1 =2;
         $trans_name="transaction_".$m1."_".$y;
         $trans_images="transaction_images_".$m1."_".$y;
         $trans_invoice="transaction_invoice_".$m1."_".$y;   
         //echo "Second Quarter";
    }else if(($dt[1]>=7) && ($dt[1]<=9)){
         $m1 =3;
         $y = $dt[2];
         $trans_name="transaction_".$m1."_".$y;
         $trans_images="transaction_images_".$m1."_".$y;
         $trans_invoice="transaction_invoice_".$m1."_".$y;

       //   var_dump($trans_invoice);
         //echo "Third Quarter";
    }else{
          $m1 =4;
          $y = $dt[2];
          $trans_name="transaction_".$m1."_".$y;
          $trans_images="transaction_images_".$m1."_".$y;
         $trans_invoice="transaction_invoice_".$m1."_".$y;  
         //echo "Fourth Quarter";
    }
    // $conn = mysqli_connect("localhost","root","","graciousexpress");
    //  $select_user_email = mysqli_query($conn,"select *from users where user_id ='".$_SESSION['user_id']."'");
    //  $get_user_data = mysqli_fetch_assoc($select_user_email);
    //   $user_email = $get_user_data['email'];
     // var_dump($user_email);

    //  $select_client_id =  mysqli_query($conn,"select *from client where email ='$user_email'");
    //  $get_client_data = mysqli_fetch_assoc($select_client_id);
    //  $client_id = $get_client_data['client_id'];
   //var_dump($client_id);

 $query = "select *from transaction_".$m1."_".$dt[2]." where transaction_id ='$tbl_id' ";
    $query_result = mysqli_query($conn,$query);
    $i=1;
    while($user_booking = mysqli_fetch_assoc($query_result)){
        // var_dump($user_booking['transaction_id']);
        // exit();
        $query_invoice = "select sum(no_of_pkge) as package,type_of_pkge,party_invoice_no,said_contents,sum(qty) as total_qty, sum(gross_weight) as total_gross,sum(charged_weight) as total_charged from transaction_invoice_".$m1."_".$dt[2]." where transaction_id ='".$user_booking['transaction_id']."' ";
        $query_invoice_result = mysqli_query($conn,$query_invoice);
        $package_details = mysqli_fetch_assoc($query_invoice_result);
        $grn_no = $user_booking['grn_no'];
        $consingee_name = get_client_name($conn,$user_booking['consignee']);
        $consingee_address1 = $user_booking['con_address1'];
        $consingee_address2 = $user_booking['con_address2'];
        $consingee_city = get_city_name($conn,$user_booking['con_city']);
        $consingee_contact = '1234567890';
        $shipping_mode = get_mode($conn,$user_booking['mode_of_transportation']);
        $pay_mode = consignment_mode($conn,$user_booking['mode_of_consignment']);
        $no_of_package = $package_details['package'];
        // $length = $user_booking['length'];
        // $width = $user_booking['width'];
        // $height = $user_booking['height'];
        $kgs = $package_details['total_qty'];
        $g_weight = $package_details['total_gross'];
        $c_weight = $package_details['total_charged'];
        // $attachment = $row['attchment'];
        $booking_date = $user_booking['grn_date'];
      $output .='
            <div class="col-xs-12">
            <div class="row">
            <div class="col-xs-12">
            <table class="table table-borderless">
            <tr>
            <th><b>GRN No:</b></th>
            <td>'.$grn_no.'</td>
            </tr>
            <tr>
            <th><b>Booking Date:</b></th>
            <td>'.$booking_date.'</td>
            </tr>
            <tr>
            <th><b>Consignee Name:</b></th>
            <td>'.$consingee_name.'</td>
            </tr>
            <tr>
            <th><b>Consignee Address:</b></th>
            <td>'.$consingee_address1.'</td>
            </tr>
            <tr>
            <th><b>Consignee City:</b></th>
            <td>'.$consingee_city.'</td>
            </tr>
            
            <tr>
            <th><b>Shipping Mode:</b></th>
            <td>'.$shipping_mode.'</td>
            </tr>
            <tr>
            <th><b>Payment Mode:</b></th>
            <td>'.$pay_mode.'</td>
            </tr>
            </table>    
            <h5><b>Package Details:</b></h5>
            </div>';
            $output .= '
           
            </div>
            </div>
            <div class="col-xs-12">
            <div class="row">
            <div class="col-xs-12">
            <table class="table">
            <tr>
            <th>No of Packages</th>
            <th>Qty</th>
            <th>G.Weight</th>
            <th>C.Weight</th>
            
         <!--<th>Attachment</th>-->
            </tr>
            <tr>
            <td>'.$no_of_package.'</td>
            <td>'.$kgs.'</td>
            <td>'.$g_weight.'</td>
            <td>'.$c_weight.'</td>
            <td><img src="" height=32 widht=32 alt="" style="margin-left: 20px;"></img></td>
            </tr>
            <table>
            </div>
            </div>
            </div>
        ';

        echo $output;

       

    }
}


if($cmd =="get_transaction_month_details"){
	$out_put='';
	$month = $_REQUEST['month'];
    $client_id = $_REQUEST['edit_id'];
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
	
		$query = "select * from transaction_".$m1."_".$dt[1]." where consigner ='$client_id' and grn_date like '%$month' order by grn_date asc";

		$result = mysqli_query($conn,$query);
			$i=1;
            $out_put .= '<table id="employee_data" class="table table-striped table-bordered" style="width:100%">
            <thead>
                 <tr>
                      <td>S.No</td>
                      <td>Payment Status</td>
                      <td>GRN No</td>
                      <td>GRN Date</td>
                      <td>Consignee Name</td>
                      <!-- <td>Consignee Contact</td>   -->
                      <td>Destination</td>
                      <td>No Of Pkgs</td>
                      <td>Status</td>
                      <td>Action</td>

                 </tr>
            </thead>
            <tbody id="get_month_details">';
		if(mysqli_num_rows($result) > 0)
	{
		while($row = mysqli_fetch_array($result))
		{
            $booking = $row['booking_status'];
            $remarks = $row['remarks'];
            $cancelled_by = get_user($conn, $row['cancelled_by']);
            $updated_at = $row['updated_at'];
			$pkg_q=mysqli_query($conn,"select sum(no_of_pkge) as pkge from transaction_invoice_".$m1."_".$dt[1]." where transaction_id='".$row['transaction_id']."'");
			$pkg_r=mysqli_fetch_array($pkg_q);
			
			$out_put.='<tr>
			<td class="text-center">'.$i.'</td>';
            // New Code Comes here ------------
            $data = array(
                'transaction_id' => array($row['transaction_id']),
                'company_name' => get_client_name($conn, $client_id),
                'grn_date' => array($row['grn_date']),
                'email' => get_client_emails($conn, $client_id),
                'phone' => get_client_phones($conn, $client_id),
                'amount' => array($row['total']),
                'grn_no' => array($row['grn_no']),
                'invoice_no' => array($row['invoice_no']),
                'client_id' => $row['consigner']
            ); 
            $data_serialize = serialize($data);


$link_wit_data = http_build_query(array('aParam' => $data_serialize)); // need to send
$link_url = urlencode($link_wit_data);

$output_url = "http://localhost/graciousexpress/verify_paylink1.php?data=".$link_url;
$out_put.='<td>';
if ($booking == '1') {
    $out_put.='<span style="text-decoration: none;background-color: #ff5129;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;" >Cancelled</span>';
 } else {
    if(($row['paid_status'] != '1' && $row['paid_status'] == '0' && $row['mode_of_consignment'] == '3') ){
        $out_put.='<a style="text-decoration: none;background-color: #2962ff;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;" href="'.$output_url.'">Pay Now</a>';
    }else{
   if($row['paid_status'] == '2'){
    $out_put.=' <span style="text-decoration: none;background-color: #e7cb04;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;" >Partially Paid</span>';
   } else if($row['paid_status'] == '1'){
    $out_put.='<span style="text-decoration: none;background-color: #069f03;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;" >Paid</span>';
     }else{
        $out_put.='<span style="text-decoration: none;background-color: coral;padding: 4px 10px;color: white;border: none;display: flex;margin: auto;border-radius: 15px;justify-content: center;" >Other Mode</span>';
    }
   }
}
$out_put.='</td>';
            // New Code Comes here ------------
            
			$out_put.='<td>'.$row['grn_no'].'</td>
			<td>'.$row['grn_date'].'</td>
			<td>'.get_client_name($conn,$row['consignee']).'</td>
			<td>'.get_city_name($conn,$row['destination']).'</td>
            <td>'.$pkg_r['pkge'].'</td>
			<td>';
            if($booking == '1') {
                $out_put .= '<span style="color:red;">Consignment Cancelled</span>';
           } else {
            $out_put .= get_trans_status($row['status']);
           }
           $out_put .= '</td>';

           if ($booking == '1') { 
            $out_put .='<td>
            <a title="Info" href="#cancel_grn_popup" class="table-actions show_info_popup" data-toggle="modal" data-remarks="'.$remarks.'" data-createdby="'.$cancelled_by.'" data-createdat="'.$updated_at.'" id="'.$row['transaction_id'].'"><i class="fa fa-exclamation-circle"></i></a>&nbsp;&nbsp;&nbsp;
            <a href="javascript:void(0)" class="btn-views disable_action" data-id="'.$row['transaction_id'].'" data-grn="'.$row['grn_date'].'"><i class="fa fa-eye" title="View"></i></a>&nbsp;&nbsp;&nbsp;
            <a href="javascript:void(0)" class="btn-tracks disable_action" id="'.$row['transaction_id'].'"><img src="http://localhost:8080/GraciousExpress/user/images/track_icon_svg.svg" style="opacity: 0.4;"></img></a>&nbsp;&nbsp;&nbsp;';

           if ($row['status'] == '8') {
            $out_put .=' <a href="javascript:void(0)" class="btn-tracks disable_action" id="'.$row['transaction_id'].'"><i class="fa fa-image" title="View"></i> POD </a>&nbsp;&nbsp;&nbsp;';
             } 
             $out_put .=' </td>';
           }else{
            $out_put .= '<td>
    
            <a href="#myModal" data-toggle="modal" class="btn-view" data-id='.$row['transaction_id'].' data-grn='.$row['grn_date'].'><i class="fa fa-eye" title="View"></i></a>&nbsp;&nbsp;&nbsp;
             <a href="user-track-consignment.php?key='.md5($row['transaction_id']).'&grn_date='.$row['grn_date'].' " class="btn-track" id='.$row['transaction_id'].'><img src="http://localhost/graciousexpress/user/images/track_icon_svg.svg" style="opacity: 0.4;"></img></a>&nbsp;&nbsp;&nbsp;';
           }
		
        
         
         if($row['status'] == '8'){
        $out_put.= '<a href="proof_of_delivery_page.php?key='.md5($row['transaction_id']).'&grn_date='.$row['grn_date'].'" class="btn-track" id='.$row['transaction_id'].'><i class="fa fa-image" title="Proof of Delivery"></i></a>&nbsp;&nbsp;&nbsp;';
        }
        
        $output .='
        </tr>
        ';
			
	
			$i++;
		}
        $out_put .= '</tbody>

        </table>';
	
	}
	else{
        $out_put1 .= 
        '<table id="employee_data" class="table table-striped table-bordered" style="width:100%">
            <thead>
                 <tr>
                      <td>S.No</td>
                      <td>GRN No</td>
                      <td>GRN Date</td>
                      <td>Consignee Name</td>
                      <!-- <td>Consignee Contact</td>   -->
                      <td>Destination</td>
                      <td>No Of Pkgs</td>
                      <td>Status</td>
                      <td>Action</td>

                 </tr>
            </thead>
            <tbody id="get_month_details">
            <tr><td colspan="9" style="padding:10px;text-align:center;font-size:17px;"> No Booking in this Month</td></tr>

            </tbody>
            </table>';


    }
    echo $out_put;
       
}


if($cmd =="get_monthly_payment_transactions_details"){
	$out_put='';
	$month = $_REQUEST['month'];
    $client_id = $_REQUEST['edit_id'];
	$dt=explode("-",$month);	

    //$date = '06-2022';
    $timestamp = $month;
    $timestamp = DateTime::createFromFormat('m-Y', $timestamp);
    $newDate = $timestamp->format('Y-m');

	
	
	 $query = "select * from razorpay_payment where md5(client_id) ='$client_id' and created_at like '%$newDate%' order by created_at desc";

		$result = mysqli_query($conn,$query);
			$i=1;
            $out_put .= '<table id="employee_data" class="table table-striped table-bordered display" style="width:100%">
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
        <tfoot style="color:#053950">
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



if($cmd == 'get_mapped_client_details'){
    $output = '';
    $sel_id = $_REQUEST['sel_id'];
    $query = "SELECT *FROM client where `client_id` = '$sel_id'";
    $sql = mysqli_query($conn,$query);
    $row = mysqli_fetch_assoc($sql);
    //echo json_encode($row);

    $address1 = $row['address1'];
    $address2 = $row['address2'];
    $city = get_city_name($conn,$row['city']);
    $city_id = $row['city'];
    $city_drop = '<option value='.$row['city'].' >'.get_city_name($conn,$row['city']).'</option>';
    $state = get_statename($conn,$row['state']);
    $pincode = $row['pincode'];
    $gst_no = $row['gst_no'];

    $output = array(
        'address1' => $address1,
        'address2' => $address2,
        'city' => $city,
        'city_id' => $city_id,
        'state' => $state,
        'pincode' => $pincode,
        'gst_no' => $gst_no,
        'city_drop' => $city_drop
    );
    echo json_encode($output);
}

if($cmd == "get_pay_info"){

    $destination = $_REQUEST['destination'];
    $val = $_REQUEST['data'];
    $values;
    
    $client = $_REQUEST['client'];
    
    //$connn = mysqli_connect("localhost","root","","bookconsignment");

   $query = "SELECT * FROM `consignor_payment` where consigner_id = $client and destination = $destination";

    $result = mysqli_query($conn,$query);

    $data = mysqli_fetch_assoc($result);
    //var_dump($data)

    switch($val){
        case "1" :
           $values = "air";
           break; 
        case "2" :
            $values = "train";
            break; 
        case "3" :
            $values = "express";
            break;
        case "4" :
            $values = "surface";
            break;
        case "5" :
            $values = "local";
            break;
        // case "7" :
        //     $values = "ftl";
        //     break;   
        case "8" :
            $values = "ptl";
            break;             
        default:
             "NULL";
    }

    if($values == "air"){
        $rates = $data['air'];
        $doc_chrgs = $data['doc_chrgs'];
        $loading_unloading_chrgs = $data['loading_unloading_chrgs'];
        $crane_fork_chrgs = $data['crane_fork_lift_chrgs'];
        $other_chrgs = $data['other_chrgs'];
        $labour_chrgs = $data['labour_charges'];
    
    }else if($values == "train") {
        $rates = $data['train'];
        $doc_chrgs = $data['doc_chrgs'];
        $loading_unloading_chrgs = $data['loading_unloading_chrgs'];
        $crane_fork_chrgs = $data['crane_fork_lift_chrgs'];
        $other_chrgs = $data['other_chrgs'];
        $labour_chrgs = $data['labour_charges'];
    
    }else if($values == "express"){
        $rates = $data['express'];
        $doc_chrgs = $data['doc_chrgs'];
        $loading_unloading_chrgs = $data['loading_unloading_chrgs'];
        $crane_fork_chrgs = $data['crane_fork_lift_chrgs'];
        $other_chrgs = $data['other_chrgs'];
        $labour_chrgs = $data['labour_charges'];
    
    }else if($values == "surface"){
        $rates = $data['surface'];
        $doc_chrgs = $data['doc_chrgs'];
        $loading_unloading_chrgs = $data['loading_unloading_chrgs'];
        $crane_fork_chrgs = $data['crane_fork_lift_chrgs'];
        $other_chrgs = $data['other_chrgs'];
        $labour_chrgs = $data['labour_charges'];
    
    }else if($values == "ptl"){
        $rates = $data['ptl'];
        $doc_chrgs = $data['doc_chrgs'];
        $loading_unloading_chrgs = $data['loading_unloading_chrgs'];
        $crane_fork_chrgs = $data['crane_fork_lift_chrgs'];
        $other_chrgs = $data['other_chrgs'];
        $labour_chrgs = $data['labour_charges'];
    
    }else if($values == "ftl"){
        $rates = $data['ftl'];
    }else if($values == 'local'){
        $rates = $data['local_delivery'];
        $doc_chrgs = $data['doc_chrgs'];
        $loading_unloading_chrgs = $data['loading_unloading_chrgs'];
        $crane_fork_chrgs = $data['crane_fork_lift_chrgs'];
        $other_chrgs = $data['other_chrgs'];
        $labour_chrgs = $data['labour_charges'];
    
    }else{
        $rates1 = "";
    }


    $output = array(
        'rates' => $rates,
        'doc_charges' => $doc_chrgs,
        'loading_unloading_charges' => $loading_unloading_chrgs,
        'crane_fork_charges' => $crane_fork_chrgs,
        'other_charges' => $other_chrgs,
        'labour_charges' => $labour_chrgs,
        'rates1' => $rates1
    );

    echo json_encode($output); 

}

if($cmd == "get_expected_delivery_form"){
    $output1 = "";
    $date1 = $_REQUEST['date'];
    $origin = $_REQUEST['origin'];
    $destination = $_REQUEST['destination'];
    
    $get_id = "WHERE origin='$origin' AND destination='$destination'";
  
    
  $query = "select * from expectded_delivery ".$get_id ;
    
    $result = mysqli_query ($conn, $query);

	$count = mysqli_num_rows($result);
    
    $fetch_rates = mysqli_fetch_assoc($result);
    if($count > 0){
     $id = $fetch_rates['id'];
    
     $surface = $fetch_rates['surface'];
     $express = $fetch_rates['express'];
     $train = $fetch_rates['train'];
    // exit();
     $air = $fetch_rates['air'];
     $note = $fetch_rates['note'];
  
    if($surface !=''){
        $date= strtotime($surface." day" , strtotime($date1));
        $surface_date = date("d-m-Y",$date);
     }else{
     	$surface_date = "Not Available";
     }
    if($express !=''){
        $date2= strtotime($express." day" , strtotime($date1));
        $express_date = date("d-m-Y",$date2);
    }else{
    $express_date = "Not Available";
}
   		if($train !=''){
        $date3= strtotime($train." day" , strtotime($date1));
        $train_date = date("d-m-Y",$date3);
    	}else{
    	$train_date = "Not Available";
    	}
    if($air){
        $date4= strtotime($air." day" , strtotime($date1));
        $air_date = date("d-m-Y",$date4);
    }else{
    $air_date = "Not Available";
    }
    
     $output1 =array(
        "surface" => $surface_date,
        "express" => $express_date,
        "train" => $train_date,
        "air" => $air_date,
        "note" => $note
      
      ) ;
    
    }else{
    
    $output1 = 0;
   
    
    }
     
    echo json_encode($output1);
  
    // $date= strtotime("15 day" , strtotime($date1));
    // $new_date = date("d-m-Y",$date);
    // echo $new_date;
    
    }

    if($cmd == "get_rate_form"){
        $output = array();

        $origin = $_REQUEST['origin'];
        $destination = $_REQUEST['destination'];
        $kgs = $_REQUEST['kgs'];
        
        $get_id = "WHERE origin='$origin' AND destination='$destination'";
        
        $query = "select * from rate ".$get_id ;
       
        $result = mysqli_query ($conn, $query);
        
        $fetch_rates = mysqli_fetch_assoc($result);
    
        $id = $fetch_rates['id'];
        $surface = $fetch_rates['surface'] * $kgs;
        $express = $fetch_rates['express'] * $kgs;
        $train = $fetch_rates['train'] * $kgs;
        $air = $fetch_rates['air'] * $kgs;
        $note = $fetch_rates['note'];
    
        //  $output = array(
        //     "id" => $id,
        //     "surface" => $surface,
        //     "express" => $express,
        //     "train" => $train,
        //     "air" => $air,
        //     "note" => $note,
        //  );
        
        //  echo json_encode($output);
          $output =array(
            "surface" => $surface,
            "express" => $express,
            "train" => $train,
            "air" => $air,
            "note" => $note,
          ) ;
        //  echo $surface."<br>";
        //  echo $express."<br>";
        //  echo $train."<br>";
        //  echo $air."<br>";
        //  echo $note."<br>";
        
        echo json_encode($output);
        }

        if($cmd == "search_pod"){
            $booking_sts = $_POST['booking_sts'];
            $grn_no = $_POST['grn_no'];
            if($grn_no != '' && $booking_sts == ''){

            $screens =  $_POST['grn_no'];

            $ext = ".jpg";
	
            $search = $screens.$ext;
            
            //echo $search;

            $image_data = array();

            
            $images = "select screens from pod_files where screens LIKE '%$screens%' ";
            $res = mysqli_query($conn,$images);
        	$count = mysqli_num_rows($res);
        	if($count > 0){
            // $row = mysqli_fetch_assoc($res);
            while($row = mysqli_fetch_assoc($res)){
            $imagesd1[] = explode('@@',$row['screens']);
            
            }
            // echo "<pre>";
            // print_r($imagesd1);
            // echo "</pre>";

            foreach($imagesd1 as $key => $value1){
                foreach($value1 as $key2 => $value2){
                    $filtered_array[] =  $value2;
                }
            }
            // echo "<pre>";
            // print_r($filtered_array);
            // echo "</pre>";

            $filter_img = preg_grep('/^'.$screens.'.*/', $filtered_array);
            $array_unique = array_unique($filter_img);
            
            // echo "<pre>";
            // print_r($array_unique);
            // echo "</pre>";

            foreach($array_unique as $show_images){
            echo '<div class="col-md-3 col-sm-6">
               

                <img src="http://localhost/graciousexpress/pod_uploads/'.$show_images.'" class="img-thumbnail hh" alt="" />
               
                <div class="text-center">
                <a href="http://localhost/graciousexpress/pod_uploads/'.$show_images.'" class="align_btn" download>Download</a>
               
                </div>
                </div>';
                
            }
            
            }else{
            echo '<h3 class="text-center pod_text">POD Not Uploaded.</h3>';
            }

            //echo $images;
            }else{
                echo '<p class="text-center text-danger">Incorrect GRN No Or Booking Cancelled... Please check and try again!</p> ';

            }

        }
        

?>