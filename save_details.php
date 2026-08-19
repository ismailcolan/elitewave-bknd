<?php
session_start();
error_reporting(1);
//$con = mysqli_connect("localhost",'root','','bookconsignment');
require_once 'swift_mailer/vendor/autoload.php';
require_once 'Twillio/vendor/autoload.php';
require_once('Twillio/constant.php');
// sms setup
// require 'plivo_sms/vendor/autoload.php';
// use Plivo\RestClient;
// $client = new RestClient("MAM2I1NDG2Y2UZODVIZW", "ZjM1MTM0MjBkM2YxNDBlMmM2NWI2ZTI3YjNlNDcz");
// sms setup end
require_once("web/appMail.php");
require_once("web/include/connect.php");
require_once("user/include/user-function.php");
$form_name = $_POST['form_name'];
$created_at = date('d-m-Y');
$updated_at = date('d-m-Y');
$created_by = $updated_by = $_SESSION['user_id'];

use Twilio\Rest\Client;
function dec_name($name= ''){
    $enc2 = base64_decode(base64_decode(base64_decode($name)));
    $exp_arry = explode(':$',$enc2); 
    $final_value = base64_decode($exp_arry[1]); 
    return $final_value; 
}

if ($form_name == "add_new_form") {

    // if(isset($_POST['sender-email'])){
    //     $consignor_email = $_POST['sender-email'];
    //      $check_email = "SELECT * FROM consignment where consignor_email = '$consignor_email'";
    //      $result_email = mysqli_query($con,$check_email);
    //      $count= mysqli_num_rows($result_email);
    //      if($count==0){
    //          $consignor_email = $_POST['sender-email'];
    $generate_pass = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
    $rand_password = substr(str_shuffle($generate_pass), 0, 8);
    $email = $_POST['sender-email'];
    $username = $_POST['sender-name'];
    $contact_no = $_POST['sender-contact-no'];
    $password = $rand_password;
    $role = "USER";
    //$conn = mysqli_connect("localhost",'root','','graciousexpress');

   $query = "INSERT INTO `users`(`email`, `password`, `user_name`,`contact_no`,`created_at`, `status`)VALUES('$email','$password','$username','$contact_no','$created_at',0)";
    // var_dump($query);
    // exit();
    $register_user = mysqli_query($conn, $query);

    if ($register_user) {
        $user_id = mysqli_insert_id($conn);
        // var_dump($user_id);
        // exit();

        $check_id = mysqli_query($conn, "SELECT *FROM user_inquiry_list");
        $count_id = mysqli_num_rows($check_id);
        if ($count_id == 0) {
            $serialno = 1;
            $clientCode = "GERP";
            $sequence = sprintf("%04d", $serialno);
            $booking_id = $clientCode . '-' . $sequence;

            $customer = $_POST['customer'];

            if ($_POST['air'] != '') {

                $shipping_mode = $_POST['air'];
            } else if ($_POST['train'] != '') {

                $shipping_mode = $_POST['train'];
            } else if ($_POST['roadsurface'] != '') {

                if ($_POST['ftl'] != '') { //*FTL Quotation Send to Admin

                    $shipping_mode = $_POST['ftl'];
                } else if ($_POST['ptl'] != '') {

                    $shipping_mode = $_POST['ptl'];
                } else {

                    $shipping_mode = $_POST['roadsurface'];
                }

                //$shipping_mode = $_POST['roadsurface'];
            } else if ($_POST['roadexpress'] != '') {

                $shipping_mode = $_POST['roadexpress'];
            } else {

                $shipping_mode = $_POST['localdelivery'];
            }


            if ($_POST['tobilled'] != '') {

                $pay_mode = $_POST['tobilled'];
            } else if ($_POST['topay'] != '') {

                $pay_mode = $_POST['topay'];
            } else {
                $pay_mode = $_POST['cod'];
            }

            $consignor_name = $_POST['sender-name'];
            $consignor_contact = $_POST['sender-contact-no'];
            $consignor_email = $_POST['sender-email'];
            $consignor_city = $_POST['sender-city'];
            $consignor_address = $_POST['sender-address'];
            $consignor_town = $_POST['sender-area'];
            $consignor_package = $_POST['no-of-package'];
            $consignor_package_type = $_POST['package_type'];
            $consignor_invoice = $_POST['package-invoice'];
            $consignor_content = $_POST['package-content'];
            $consignor_kg = $_POST['package-qty'];
            $consignor_gross_wt = $_POST['package-gross-wgt'];
            $consignor_charge_wt = $_POST['package-net-wgt'];
            $consignee_name = $_POST['reciever-name'];
            $consignee_contact = $_POST['reciever-contact-no'];
            $consignee_email = $_POST['reciever-email'];
            $consignee_city = $_POST['reciever-city'];
            $consignee_address = $_POST['reciever-address'];
            $shipping_address = $_POST['shipping_address'];
            $consignee_town = $_POST['reciever-area'];
            $consignee_docname = $_POST['doc-name'];
            $consignee_docdata = $_POST['doc-data'];
            $ftl_type = $_POST['truck_type'];
            $train_type = $_POST['train_type'];
            $charged_weight = $_POST['final_charged_weight'];

            if ($_FILES['file']['name'] != '') {
                $test = explode('.', $_FILES['file']['name']);
                $extension = end($test);
                //$randnum = rand(1111111111,9999999999);
                $name = rand(1111111111, 9999999999) . '.' . $extension;

                $location = 'web/invoice_image/' . $name;
                move_uploaded_file($_FILES['file']['tmp_name'], $location);

                // echo '<img src="'.$location.'" height="100" width="100" />';
            }
            //$consignee_attachment = $_POST['recieverarea'];
            $booked_date = date('d-m-Y');
            $status = "0";
            $query1 = "INSERT INTO `user_inquiry_list`(`user_id`, `booking_id`, `customer_details`, `shipping_mode`,`train_type`, `ftl_type`,`pay_mode`, `consignor_name`, `consignor_contact`, `consignor_email`, `consignor_city`, `consignor_address`, `consignor_town`, `no_of_package`, `type_of_package`, `invoice_no`, `contents`, `kgs`, `gross_weight`, `charged_weight`,`consignee_name`, `consignee_contact`, `consignee_email`, `consignee_city`, `consignee_address`,`shipping_address`, `consignee_town`, `document_count`, `document_data`, `attchment`, `created_by`, `created_at`, `updated_at`, `updated_by`, `status`)VALUES($user_id,'$booking_id',' $customer','$shipping_mode','$train_type','$ftl_type','$pay_mode','$consignor_name','$consignor_contact','$consignor_email','$consignor_city','$consignor_address','$consignor_town','$consignor_package','$consignor_package_type','$consignor_invoice','$consignor_content','$consignor_kg','$consignor_gross_wt','$charged_weight','$consignee_name','$consignee_contact','$consignee_email','$consignee_city','$consignee_address','$shipping_address','$consignee_town','$consignee_docname','$consignee_docdata','$name','0','$booked_date','0','0','$status')";
            $booked_query = mysqli_query($conn, $query1);
            $last_consignment_id = mysqli_insert_id($conn);
            $select_booking_id = mysqli_query($conn, "SELECT *from user_inquiry_list where id = '$last_consignment_id' ");
            $row = mysqli_fetch_assoc($select_booking_id);

            $booking_id = $row['booking_id'];

            if ($booking_id) {

                echo $booking_id;
            } else {
                echo "0";
            }
        } else {

            $check_again_id = mysqli_query($conn, "SELECT *from user_inquiry_list ORDER BY id DESC LIMIT 1");
            $get_data = mysqli_fetch_array($check_again_id);
            $last_id = $get_data['id'];
            $rest_id = substr("$last_id", -4);
            $insert_id = "$rest_id" + 1;
            $clientCode = "GERP";
            $sequence = sprintf("%04d", $insert_id);
            $booking_id = $clientCode . '-' . $sequence;
            $check_again_userid = mysqli_query($conn, "SELECT *FROM users ORDER BY `user_id` DESC LIMIT 1");
            $get_user_id = mysqli_fetch_array($check_again_userid);
            $user_id = $get_user_id['user_id'];
            //$user_id = 1;
            $customer = $_POST['customer'];

            if ($_POST['air'] != '') {

                $shipping_mode = $_POST['air'];
            } else if ($_POST['train'] != '') {

                $shipping_mode = $_POST['train'];
            } else if ($_POST['roadsurface'] != '') {

                if ($_POST['ftl'] != '') { //*FTL Quotation Send to Admin

                    $shipping_mode = $_POST['ftl'];
                } else if ($_POST['ptl'] != '') {

                    $shipping_mode = $_POST['ptl'];
                } else {

                    $shipping_mode = $_POST['roadsurface'];
                }

                //$shipping_mode = $_POST['roadsurface'];
            } else if ($_POST['roadexpress'] != '') {

                $shipping_mode = $_POST['roadexpress'];
            } else {

                $shipping_mode = $_POST['localdelivery'];
            }


            if ($_POST['tobilled'] != '') {

                $pay_mode = $_POST['tobilled'];
            } else if ($_POST['topay'] != '') {

                $pay_mode = $_POST['topay'];
            } else {
                $pay_mode = $_POST['cod'];
            }

            $consignor_name = $_POST['sender-name'];
            $consignor_contact = $_POST['sender-contact-no'];
            $consignor_email = $_POST['sender-email'];
            $consignor_city = $_POST['sender-city'];
            $consignor_address = $_POST['sender-address'];
            $consignor_town = $_POST['sender-area'];
            $consignor_package = $_POST['no-of-package'];
            $consignor_package_type = $_POST['package_type'];
            $consignor_invoice = $_POST['package-invoice'];
            $consignor_content = $_POST['package-content'];
            $consignor_kg = $_POST['package-qty'];
            $consignor_gross_wt = $_POST['package-gross-wgt'];
            $consignor_charge_wt = $_POST['package-net-wgt'];
            $consignee_name = $_POST['reciever-name'];
            $consignee_contact = $_POST['reciever-contact-no'];
            $consignee_email = $_POST['reciever-email'];
            $consignee_city = $_POST['reciever-city'];
            $consignee_address = $_POST['reciever-address'];
            $shipping_address = $_POST['shipping_address'];
            $consignee_town = $_POST['reciever-area'];
            $consignee_docname = $_POST['doc-name'];
            $consignee_docdata = $_POST['doc-data'];
            $ftl_type = $_POST['truck_type'];
            $train_type = $_POST['train_type'];
            $charged_weight = $_POST['final_charged_weight'];
            if ($_FILES['file']['name'] != '') {
                $test = explode('.', $_FILES['file']['name']);
                $extension = end($test);
                $name = rand(1111111111, 9999999999) . '.' . $extension;

                $location = 'web/invoice_image/' . $name;
                move_uploaded_file($_FILES['file']['tmp_name'], $location);
            }
            $booked_date = date('d-m-Y');
            $status = "0";
            $query2 = "INSERT INTO `user_inquiry_list`(`user_id`, `booking_id`, `customer_details`, `shipping_mode`,`train_type`,`ftl_type`, `pay_mode`, `consignor_name`, `consignor_contact`, `consignor_email`, `consignor_city`, `consignor_address`, `consignor_town`, `no_of_package`, `type_of_package`, `invoice_no`, `contents`, `kgs`, `gross_weight`, `charged_weight`,`consignee_name`, `consignee_contact`, `consignee_email`, `consignee_city`, `consignee_address`,`shipping_address`, `consignee_town`, `document_count`, `document_data`, `attchment`, `created_by`, `created_at`, `updated_at`, `updated_by`, `status`)VALUES($user_id,'$booking_id',' $customer','$shipping_mode','$train_type','$ftl_type','$pay_mode','$consignor_name','$consignor_contact','$consignor_email','$consignor_city','$consignor_address','$consignor_town','$consignor_package','$consignor_package_type','$consignor_invoice','$consignor_content','$consignor_kg','$consignor_gross_wt','$charged_weight','$consignee_name','$consignee_contact','$consignee_email','$consignee_city','$consignee_address','$shipping_address','$consignee_town','$consignee_docname','$consignee_docdata','$name','0','$booked_date','0','0','$status')";
            $booked_query = mysqli_query($conn, $query2);
            $last_consignment_id = mysqli_insert_id($conn);
            $select_booking_id = mysqli_query($conn, "SELECT *from user_inquiry_list where id = '$last_consignment_id' ");
            
            $row = mysqli_fetch_assoc($select_booking_id);

            $booking_id = $row['booking_id'];

            if ($booking_id) {

                echo $booking_id;
            } else {

                echo "0";
            }
        }
    }


    //     }
    //     else{
    //         echo "Email Alredy Exist";
    //     } 
    //}

}

if ($form_name == 'login') {
    $username = $_POST['email'];
    $password = $_POST['password'];
	// $password = enc_name($password);
    if (isset($_POST['remember'])) {
        $remember = 1;
    } else {
        $remember = 0;
    }

    $query = "select * from users where `email`= '$username' and `password`='$password' and status = '1'";

    $result = mysqli_query($conn, $query) or die(mysqli_error());

    if (mysqli_num_rows($result) == 1) {

        $row = mysqli_fetch_array($result);

        $user_id = $row['user_id'];

        $contact_no = $row['contact_no'];


        //OTP Part
        // $generate_otp = mt_rand(100000, 999999);
        // $insert_otp = "update users SET `otp`='$generate_otp' where `email` = '$username'";
        // $res_otp = mysqli_query($conn, $insert_otp);

        //Send OTP through SMS
        // if ($contact_no != '') {

        //     if (strstr($contact_no, '+91')) {
        //         $phone  =  $contact_no;
        //     } else {
        //         //echo "Text not found";
        //         $phone  =  "+91" . $contact_no;
        //     }

        //     $sid = constant("SID");

        //     $token = constant("Auth");

        //     $twilio = new Client($sid, $token);

        //     $msg = "Your Login OTP is " . $generate_otp . " \r\n Expires at 2 minutes and 15 sec. \r\n Do not share it with anyone";

        //     $message = $twilio->messages
        //         ->create(
        //             $phone, // to
        //             ["body" => $msg, "from" => "+17853776942"]
        //         );
        // }

     	// if ($contact_no != '') {
     	// if (strstr($contact_no, '+91')) {
     	// $sms_number  =  $contact_no;
     	// } else {
     	// $sms_number  =  "+91" . $contact_no;
     	// }
            // try{
                // $message_created = $client->messages->create([
                // 'src' => "GRACIX",
                // "dst" => $sms_number,
                // "text"  => "Your one-time password for login is:".$generate_otp.".This code will expire in 2 Minutes. Do not share it with anyone.\nThank you for using our service!\nGracious Express",
                // "dlt_entity_id"=>"1201168767372626314",
                // "dlt_template_id"=>"1207168907691669414",
                // "dlt_template_category"=>"service_implicit",
                // ]);
            // }catch(Exception $err){
            //     $error =  $err->getMessage();
            // }
        // }
        //End Send OTP through SMS



        //Send OTP Through Email


//         $msg = '<p style="line-height: 24px; margin-bottom:15px;">
// 		Verify Your Login <br/>				 
//         Below is your one time passcode: </p>
        
//        <h1 style="color:black;"> ' . $generate_otp . '</h1>
// 	   ';
//         $to_name = (get_user($conn, $user_id));

//         $to_email = (get_user_email($conn, $user_id));
//         //print_r($to_email);

//         $mail = sendAppMail($to_name, $to_email, 'OTP to login to your account', $msg);

        //End

        $_SESSION['LAST_ACTIVITY'] = time();
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['otp'] = $row['otp'];
        $uid = $row['user_id'];

        if ($uid != '') {
            setcookie('persistID', $uid, time() + (30 * 24 * 60 * 60), '/');
            echo 1;
        } else {
            if ($id == '') {
                echo 0;
            }
        }
    }
}

if ($form_name == "password_change") {
    $login_id = mysqli_real_escape_string($conn, $_POST['login_id']);
    $new_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
	$new_password = enc_name($new_password);
    $query = "update users set `password` = '$new_password' where user_id = '$login_id'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}


if ($form_name == "edit_user_consignment_form") {
    $out_put = array();

    $edit_id = $_POST['edit_id'];
    $grn_id = $_POST['grn_id'];
    $grn_date = $_POST['grn_date'];
    $no_of_pkg = $_POST['no_of_pkg'];
    $kgs = $_POST['kgs'];
    $Gross_charged = $_POST['Gross_charged'];
    $W_charged = $_POST['W_charged'];
    $goods_dedared_value = $_POST['goods_dedared_value'];
    $length = $_POST['length'];
    $width = $_POST['width'];
    $height = $_POST['height'];
    $amount_in_words = $_POST['amount_in_words'];
    $frieght_rate = $_POST['frieght_rate'];
    $frieght_amount = $_POST['frieght_amount'];
    $cod_rate = $_POST['cod_rate'];
    $cod_amount = $_POST['cod_amount'];
    $fov_rate = $_POST['fov_rate'];
    $fov_amount = $_POST['fov_amount'];
    $doc_rate = $_POST['doc_rate'];
    $doc_amount = $_POST['doc_amount'];
    $cartage_rate = $_POST['cartage_rate'];
    $cartage_amount = $_POST['cartage_amount'];
    $labour_rate = $_POST['labour_rate'];
    $labour_amount = $_POST['labour_amount'];
    $other_rate = $_POST['other_rate'];
    $other_amount = $_POST['other_amount'];
    $gst_rate = $_POST['gst_rate'];
    $gst_amount = $_POST['gst_amount'];
    $total = $_POST['total'];
    $vehicle_no = $_POST['vehicle_no'];
    $signature = $_POST['signature'];
    $file_receipt = $_POST['file_receipt'];

    if ($_FILES['file']['name'] != '') {
        $test = explode('.', $_FILES['file']['name']);
        $extension = end($test);
        $imagename = rand(1111111111, 9999999999) . '.' . $extension;

        $location = 'web/invoice_image/' . $imagename;
        move_uploaded_file($_FILES['file']['tmp_name'], $location);
    } else {
        $query = "select attchment from consignment where id = '$edit_id'";
        $result = mysqli_query($con, $query);
        $oldimage = mysqli_fetch_assoc($result);
        $imagename = $oldimage['attchment'];
    }
    $query = "UPDATE `consignment` SET `no_of_package`='$no_of_pkg', `length`= '$length',`width`='$width',`height`='$height',`kgs`='$kgs',`attchment`='$imagename',`goods_dedared_value`='$goods_dedared_value',`frieght_rate`='$frieght_rate',`frieght_amount`='$frieght_amount',`cod_rate`='$cod_rate',`cod_amount`='$cod_amount',`fov_rate`='$fov_rate',`fov_amount`='$fov_amount',`doc_charges`='$doc_charges',`doc_amount`='$doc_amount',`cartage_rate`='$cartage_rate',`cartage_amount`='$cartage_amount',`labour_handling_rate`='$labour_rate',`labour_handling_amount`='$labour_amount',`other_charge_rate`='$other_rate',`other_charge_amount`='$other_amount',`gst_rate`='$gst_rate',`gst_amount`='$gst_amount',`total`='$total',`total_words`='$amount_in_words',`truck`='$vehicle_no',`consigner_signature`='$signature',`created_by`='0',`updated_at`='$updated_at',`updated_by`='0' WHERE id = '$edit_id'";
    $result = mysqli_query($con, $query);
    $month = "07";
    $year = "2021";
    $url = "https://elitewave360.in/web/pdf.php?id=" . $edit_id . "";
    $path = "web/transaction_pdf/" . $month . "_" . $year . "_" . $edit_id . "transaction.pdf";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    $result_url = file_put_contents($path, $data);
    $query = "select * from consignment where id='$edit_id'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    $grn_no = $row['booking_id'];
    $grn_date = $row['booking_date'];
    $user_id = $row['user_id'];
    $consignor = $row['consignor_name'];
    $consignor_address = $row['consignor_address'];
    $consignor_contact = $row['consignor_contact'];
    $consignee = $row['consignee_name'];
    $consignee_address = $row['consignee_address'];
    $consignee_contact = $row['consignee_contact'];

    $consignor_city = $row['consignor_city'];
    $consignee_city = $row['consignee_city'];
    $no_of_packages = $row['no_of_package'];


    $msg = '<p style="line-height: 24px; margin-bottom:15px;">
						  
Thanking you for booking the consignment, Please find below the booking detais for your reference.					
<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
<tr>
<td >GRN No	</td><td >' . $grn_no . '</td>
</tr><tr>	
<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
</tr><tr>	
<td >No. of Pkgs.</td><td >' . $no_of_packages . '</td>	
</tr>
<tr><td >Booked By	</td><td >' . $consignor . ' , ' . $consignor_city . '</td>	</tr>	
<tr><td >Booked to	</td><td >	' . $consignee . ' , ' . $consignee_city . '</td>	</tr>	
<tr>		
<td >Your Invoice No	</td><td >	' . $inv . '	</td>	
</tr><tr>		
<td >Status	</td><td >Consignment Booked	</td>		
	</td>
					</tr>
				</table>	
<br>				
Please track you consignment by  <a style="border-radius: 5px !important;background-color: #223f9a;font-size: 14px;color: #ffffff;line-height: 1;padding: 10px 8px;touch-action: manipulation;cursor: pointer;border: 1px solid transparent;user-select: none;font-family: Titillium Web;font-weight: 700;text-decoration: none;" href="http://graciousexpress.com/tracking.php?grn_no=' . substr($grn_no, 4) . '&party_invoice_no=' . $party_invoice[0] . '" > TRACK CONSIGNMENT </a>					
</p>';
    $to_name = (get_user($con, $user_id));

    $to_email = (get_user_email($con, $user_id));

    //if(!empty(get_client_email($conn,$consignor)) && !empty(get_client_email($conn,$consignee))){
    //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
    // array_push($to_email,get_client_email($conn,$consignor),get_client_email($conn,$consignee));
    // array_push($to_name,get_client_name($conn,$consignor),get_client_name($conn,$consignee));

    $mail = sendAttachments($to_name, $to_email, 'Consignment Booking Notification', $path, $msg);

    //echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 

    //}

    if ($result) {
        echo "1";
    } else {
        echo "0";
    }
    //     $out_put['result'] = 1;
    //  }   else{
    //     $out_put['result'] = "0";
    //  }
    //  echo json_encode($out_put);

}

if ($form_name == "inactivate_user") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update users set status = '" . $status . "',updated_by='" . $updated_by . "' where user_id = '$id' ";
    $result = mysqli_query($conn, $query);
    if ($result) {

        echo 1;
    } else {
        echo 0;
    }
}

if ($form_name == "inactivate_req_for_pickup") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "UPDATE `user_pickup` set `status` = '" . $status . "',updated_by='" . $updated_by . "' where pickup_id = '$id' ";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}
if ($form_name == "delete_request_pickup") {
    $id = $_POST['tbl_id'];
    $query = "DELETE FROM `user_pickup` where pickup_id = '$id' ";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}
if ($form_name == "delete_draft_consignment") {
    $id = $_POST['tbl_id'];
    echo $query = "DELETE FROM `draft_consignment` where id = '$id' ";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}

if ($form_name == "consignor_to_client") {
    $user_id = $_POST['edit_id'];
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $address1 = $_POST['address1'];
    if ($_POST['address2'] != '') {
        $address2 = $_POST['address2'];
    } else {
        $address2 = "NULL";
    }
    $state = $_POST['state'];
    $city = $_POST['city'];
    $billing_code = strtoupper($_POST['billing_code']);
    if ($_POST['pincode'] != '') {
        $pincode = $_POST['pincode'];
    } else {
        $pincode = 0;
    }
    $email = $_POST['email'];
    $email_cc = $_POST['email_cc'];
    $conatct = $_POST['contact_no'];
    $gst = $_POST['gst_no'];
    $pan = $_POST['pan_no'];
    if ($_POST['multiple_branches'] != '') {
        $multiple_branches = $_POST['multiple_branches'];
    } else {
        $multiple_branches = 0;
    }
    if ($_POST['transit_automation'] != '') {
        $transit_automation = $_POST['transit_automation'];
    } else {
        $transit_automation = 0;
    }


    $query = "INSERT INTO `client`(`client_company_name`, `contact_person`, `billing_code`, `address1`, `address2`, `city`, `state`, `pincode`, `email`, `contact_no`, `gst_no`, `pan_no`,`automation`,`multiple_branches`,`created_at`, `updated_at`,`created_by`,`status`, `approve_status`,`invoice_frequency`)
     VALUES('$company_name','$contact_person','$billing_code','$address1','$address2','$city','$state',$pincode,'$email','$conatct','$gst','$pan',$multiple_branches,$transit_automation,'$created_at','$updated_at','$created_by',0,0,0)";
    $result = mysqli_query($conn, $query);
    //     if($_POST['edit_id']!=''){
    //          $select_user_details_1 = "SELECT *FROM user_inquiry_list where md5(`user_id`)='".$_POST['edit_id']."'";
    //          $select_user = mysqli_query($conn,$select_user_details_1);
    //          $row = mysqli_fetch_assoc($select_user);
    //          $total_pkg = $row['no_of_package'];
    //          $user_id = $row['user_id'];
    //          $grn_no = $row['id'];
    //          $inv = $row['booking_id'];
    //          $grn_date = $row['booking_date'];
    //          $consignor = $row['consignor_name'];
    //          $consignee = $row['consignee_name'];
    //          $consignor_city = get_city_name($conn,$row['consignor_city']);
    //          $consignee_city = get_city_name($conn,$row['consignee_city']);
    //          $grn_date = $row['created_at'];
    //          $select_user_details  ="select *from users where user_id = '$user_id'";
    //          $get_result = mysqli_query($conn,$select_user_details);
    //          $row2 = mysqli_fetch_assoc($get_result);
    //          $get_name = $row2['user_name'];  
    //          $get_username = $row2['email']; 
    //          $get_password = $row2['password']; 

    //         //  echo $get_name;
    //         //  echo $get_username;
    //         // echo $get_password;
    //         $msg='<p style="line-height: 24px; margin-bottom:15px;">

    //         Thank you for booking the consignment, Please find below the booking detais for your reference.					
    //         <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
    //         <tr>
    //         <td >REF No	</td><td >'.$inv.'</td>
    //         </tr><tr>	
    //         <td >Date:	</td><td >	'.$grn_date.'	</td>	
    //         </tr><tr>	
    //         <td >No. of Pkgs.</td><td >'.$total_pkg.'</td>	
    //         </tr>
    //         <tr><td >Booked By	</td><td >'.$consignor.' , '.$consignor_city.'</td>	</tr>	
    //         <tr><td >Booked to	</td><td >	'.$consignee.' , '.$consignee_city.'</td>	</tr>	
    //         <tr>		
    //         <td >Status	</td><td >Pending</td>		
    //             </td>
    //                             </tr>
    //                         </table>	
    //         <br>
    //         <p style="color:green">Find Your Credential Below to access User Dashboard.
    //         </p>

    //            <hr>
    //            <table>
    //            <thead>
    //            <tr>
    //            <th>Username</th>
    //            <th>Password</th>
    //            </tr>
    //            </thead>
    //            <tbody>
    //            <tr>
    //            <td style="text-align:center">'.$get_username.'</td>
    //            <td style="text-align:center">'.$get_password.'</td>
    //            </tr>
    //            </tbody>
    //         <br>	
    //         <small><b>Note:</b>Do not Share Your Credential with anyone.</small>
    //         <br>
    //         <p><b>Follow the Step Below:<b></p>
    //         <ul>
    //         <li>Click <a href="#">here</a> for User Dashboard</li>
    //         </ul>';
    //         $to_name=(get_user($conn,$user_id));

    //         $to_email=(get_user_email($conn,$user_id));
    //         //print_r($to_email);

    //         $mail = sendAppMail($to_name, $to_email,'Consignment Details With User Credential',$msg);
    //     }

    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}
if ($form_name == "consignee_to_client") {
    $user_id = $_POST['edit_id'];
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $address1 = $_POST['address1'];
    if ($_POST['address2'] != '') {
        $address2 = $_POST['address2'];
    } else {
        $address2 = "NULL";
    }
    $state = $_POST['state'];
    $city = $_POST['city'];
    $billing_code = strtoupper($_POST['billing_code']);
    if ($_POST['pincode'] != '') {
        $pincode = $_POST['pincode'];
    } else {
        $pincode = 0;
    }
    $email = $_POST['email'];
    $email_cc = $_POST['email_cc'];
    $conatct = $_POST['contact_no'];
    $gst = $_POST['gst_no'];
    $pan = $_POST['pan_no'];
    if ($_POST['multiple_branches'] != '') {
        $multiple_branches = $_POST['multiple_branches'];
    } else {
        $multiple_branches = 0;
    }
    if ($_POST['transit_automation'] != '') {
        $transit_automation = $_POST['transit_automation'];
    } else {
        $transit_automation = 0;
    }


    $query = "INSERT INTO `client`(`client_company_name`, `contact_person`, `billing_code`, `address1`, `address2`, `city`, `state`, `pincode`, `email`, `contact_no`, `gst_no`, `pan_no`,`automation`,`multiple_branches`,`created_at`, `updated_at`, `created_by`,`status`, `approve_status`,`invoice_frequency`)
     VALUES('$company_name','$contact_person','$billing_code','$address1','$address2','$city','$state',$pincode,'$email','$conatct','$gst','$pan',$multiple_branches,$transit_automation,'$created_at','$updated_at','$created_by',0,0,0)";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}

if ($form_name == 'rate_calculator') {
    $origin  = $_POST['origin'];
    $destination  = $_POST['destination'];
    $surface  = $_POST['surface'];
    $express  = $_POST['express'];
    $train  = $_POST['train'];
    $air  = $_POST['air'];
    $note  = $_POST['note'];

    $sql = "INSERT INTO `rate`(`origin`, `destination`, `surface`, `express`, `train`, `air`, `note`) VALUES(
        '$origin','$destination','$surface','$express','$train','$air','$note')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
    //echo "hello";
}


if ($form_name == 'edit_rate_calculator') {
    $edit_id = $_POST['edit_id'];
    $origin  = $_POST['origin'];
    $destination  = $_POST['destination'];
    $surface  = $_POST['surface'];
    $express  = $_POST['express'];
    $train  = $_POST['train'];
    $air  = $_POST['air'];
    $note  = $_POST['note'];

    $sql = "UPDATE `rate` SET `origin` = '$origin', `destination` = '$destination', `surface` = '$surface', `express` = '$express', `train` = '$train', `air` = '$air', `note` = '$note' where md5(id) = '$edit_id' ";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}
if ($form_name == "delete_rates") {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete from rate where id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo "1";
    } else {
        echo "0";
    }
}


if ($form_name == 'expected_delivery') {
    $origin  = $_POST['origin'];
    $destination  = $_POST['destination'];
    $surface  = $_POST['surface'];
    $express  = $_POST['express'];
    $train  = $_POST['train'];
    $air  = $_POST['air'];
    $note  = $_POST['note'];

    $sql = "INSERT INTO `expectded_delivery`(`origin`, `destination`, `surface`, `express`, `train`, `air`, `note`) VALUES(
        '$origin','$destination','$surface','$express','$train','$air','$note')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
    //echo "hello";
}

if ($form_name == 'edit_expected_delivery') {
    $edit_id = $_POST['edit_id'];
    $origin  = $_POST['origin'];
    $destination  = $_POST['destination'];
    $surface  = $_POST['surface'];
    $express  = $_POST['express'];
    $train  = $_POST['train'];
    $air  = $_POST['air'];
    $note  = $_POST['note'];

    $sql = "UPDATE `expectded_delivery` SET `origin` = '$origin', `destination` = '$destination', `surface` = '$surface', `express` = '$express', `train` = '$train', `air` = '$air', `note` = '$note' where md5(id) = '$edit_id' ";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}
if ($form_name == "delete_expected_delivery") {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete from expectded_delivery where id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo "1";
    } else {
        echo "0";
    }
}

if ($form_name == 'payment_info_form') {
    $consigner_id  = $_POST['consigner_id'];
    $destination  = $_POST['city'];
    $loading_unloading_chrgs  = $_POST['loading_unloading_chrgs'];
    $crane_fork_lift_chrgs  = $_POST['crane_fork_lift_chrgs'];
    $doc_chrgs  = $_POST['doc_chrgs'];
    $labour_charges  = $_POST['labour_charges'];
    $other_chrgs = $_POST['other_chrgs'];
    $air  = $_POST['air'];
    $train  = $_POST['train'];
    $ptl  = $_POST['ptl'];
    $express  = $_POST['express'];
    $local_delivery  = $_POST['local_delivery'];


    $sql = "INSERT INTO consignor_payment(
consigner,
consigner_id,
destination,
loading_unloading_chrgs,
crane_fork_lift_chrgs,
doc_chrgs,
labour_charges,
other_chrgs,
air,
train,
ptl,
express,
local_delivery,
created_at,
created_by,
updated_at,
updated_by
) VALUES (
'',
'$consigner_id',
'$destination',
'$loading_unloading_chrgs',
'$crane_fork_lift_chrgs',
'$doc_chrgs',
'$labour_charges',
'$other_chrgs',
'$air',
'$train',
'$ptl',
'$express',
'$local_delivery',
'$created_at',
'$created_by',
'$updated_at',
'$updated_by'
)";

    $result = mysqli_query($conn, $sql);

   if($result){
    echo 1;
}else{
    echo "MYSQL ERROR: ".mysqli_error($conn);
}
    //echo "hello";
}

if ($form_name == 'edit_payment_info_form') {
    $edit_id = $_POST['edit_id'];
    $consigner_id  = $_POST['consigner_id'];
    $destination  = $_POST['city'];
    $loading_unloading_chrgs  = $_POST['loading_unloading_chrgs'];
    $crane_fork_lift_chrgs  = $_POST['crane_fork_lift_chrgs'];
    $doc_chrgs  = $_POST['doc_chrgs'];
    $labour_charges  = $_POST['labour_charges'];
    $other_chrgs = $_POST['other_chrgs'];
    $air  = $_POST['air'];
    $train  = $_POST['train'];
    $ptl  = $_POST['ptl'];
    $express  = $_POST['express'];
    $local_delivery  = $_POST['local_delivery'];

    $sql = "UPDATE consignor_payment SET
consigner='',
consigner_id='$consigner_id',
destination='$destination',
loading_unloading_chrgs='$loading_unloading_chrgs',
crane_fork_lift_chrgs='$crane_fork_lift_chrgs',
doc_chrgs='$doc_chrgs',
labour_charges='$labour_charges',
other_chrgs='$other_chrgs',
air='$air',
train='$train',
ptl='$ptl',
express='$express',
local_delivery='$local_delivery',
updated_at='$updated_at',
updated_by='$updated_by'
WHERE md5(id)='$edit_id'";

    $result = mysqli_query($conn, $sql);

    if($result){
    echo 1;
}else{
    echo "MYSQL ERROR: ".mysqli_error($conn);
}
}
if ($form_name == "delete_consigner_charges") {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete from consignor_payment where id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if($result){
    echo 1;
}else{
    echo "MYSQL ERROR: ".mysqli_error($conn);
}
}
if ($form_name == "mail_form") {

    //echo "hello";
    $subject = $_POST['subject'];

    //$email = $_POST['to_email'];

    $content = $_POST['Content'];
    if ($subject != '') {
        //$conn1 = mysqli_connect("localhost", "root", "", "bookconsignment");

        $data = mysqli_query($conn, "select username from user where status = 0 ");
        //$result -> $conn -> query($data);
        $data1 = array();
        while ($row = mysqli_fetch_assoc($data)) {

            $data1[] = $row;
        }

        $limit = 10;
        // $keys = 1;
        $count_set = count($data1) / $limit;
        // print_r($count_set);
        // exit();
        for ($loop = 0; $loop < $count_set; $loop++) {




            //Create Login

            $credential = (new Swift_SmtpTransport('sg2plcpnl0032.prod.sin2.secureserver.net', 587, 'tls'))
                ->setUsername('no-reply@graciousexpress.com')
                ->setPassword("Admin@123");

            //login

            $mailer = new Swift_Mailer($credential);


            //Create HTML Template

            $html = $content;

            //Create Mail

            $message = (new Swift_Message($subject))
                ->setFrom(['no-reply@graciousexpress.com' => "Gracious Express"])
                //->setTo(['mohammedtouheed75@gmail.com' => "Personal"])
                ->setBody($html, 'text/html')
                //Add Attachemnt
                //->attach(Swift_Attachment::fromPath('1_2021_1transaction.pdf'))
            ;

            //*Email Send Method 1

            // set_time_limit(0);
            // $pause = 1;
            // $each = 1;
            // $conn = mysqli_connect("localhost","root","","bookconsignment");
            // $data = mysqli_query($conn,"select name,username from user where id = '$email'");
            // //$result -> $conn -> query($data);
            // // $data ->fetch_all(MYSQLI_ASSOC);
            // $count = mysqli_num_rows($data);

            // while($row = mysqli_fetch_assoc($data)){

            //     $data1 = $row['username'];

            // }

            // $mail = explode("@@",$data1);

            // var_dump($mail);
            // exit();

            // foreach($mail as $d){
            //      //var_dump($d);
            //              $message->AddTo($d,'Client_Name');
            //      }sleep($pause);

            //*End Method 1


            //* Second Method

            set_time_limit(0);

            //$conn = mysqli_connect("localhost", "root", "", "bookconsignment");
            $data = mysqli_query($conn, "select username from user where status = 0");
            //$result -> $conn -> query($data);
            $data1 = array();
            while ($row = mysqli_fetch_assoc($data)) {

                $data1[] = $row;
            }

            $keys = 1;
            $count = count($data1);
            if ($count > 0) {
                $mail_id = array();
                foreach ($data1 as $key => $val) {
                    //echo $key;
                    foreach ($val as $key2 => $val2) {
                        $keys++;
                        if ($keys <= $limit) {
                            $mail_id[] = $val2;
                            //echo "key:" . $key2 . "value:" . $val2 . "<br>";
                            $update = mysqli_query($conn, "update user set status=1 where username='$val2'");
                            //break;
                            sleep(2);
                            header("Refresh:0; url=https://elitewave360.in/web/bulkmail.php");
                        }
                        continue;
                    }
                }
            }
            // }else{
            //     echo "Mail are sent";
            //     $update = mysqli_query($conn,'update user set status=0 ');
            // }

            // echo "<pre>";
            // print_r($mail_id);
            // echo "</pre>";

            $remove_empty_mail = array_filter($mail_id);

            $message->AddTo('no-reply@graciousexpress.com', 'Gracious Express');
            $message->setBcc($remove_empty_mail);

            $send = $mailer->Send($message);
        }
        $update = mysqli_query($conn, 'update user set status=0');
        if($update){
            echo 1;
        }else{
            echo 0;
        }
    }

}

if ($form_name == 'add_new_request_pickup') {
    $request_id = $_POST['request_id'];
    $origin = $_POST['origin'];
    $consignor_id = $_POST['consignor_id'];
    $consignee_id = $_POST['consignee_id'];
    $shipping_mode = $_POST['shipping_mode'];
    $description = $_POST['description'];
    $consignee_name = $_POST['consignee'];
    $consignee_address = $_POST['consignee_address'];
    $consignee_contact = $_POST['consignee_contact'];
    $destination = $_POST['destination'];
    $package_qty = $_POST['package_qty'];
    $package_type = $_POST['package_type'];
    $weight = $_POST['weight'];

    $company_name = $_POST['consignor'];
    $consignor_name = $_POST['consignor'];
    $consignor_address = $_POST['consignor_address'];
    $consignor_contact = $_POST['consignor_contact'];

    $generate_ref_id = mysqli_query($conn, "SELECT max(pickup_id) as pickup_id FROM user_pickup");
    $last_ref_id = mysqli_fetch_assoc($generate_ref_id);
    $pickup_id = $last_ref_id['pickup_id'] + 1;
    $pickup_ref_id = "RFP/" . sprintf("%'.05d\n", $pickup_id);
    $pickup_user_id = $_SESSION['user_id'];
    //$consignor_contact = '1234567890';

    $query = "INSERT INTO `user_pickup`(`pickup_ref_id`, `user_id`,`company_name`, `consignor_name`, `consignor_address`, `consignor_contact`, `consignee_name`, `consignee_address`, `consignee_contact`, `origin`, `destination`, `mode`, `no_of_package`, `package_type`, `approx_weight`, `created_at`, `updated_at`, `description`, `status`)VALUES('$pickup_ref_id','$pickup_user_id','$company_name','$consignor_name','$consignor_address','$consignor_contact','$consignee_name','$consignee_address','$consignee_contact','$origin','$destination','$shipping_mode','$package_qty','$package_type','$weight','$created_at','$updated_at','$description','0')";

    $result = mysqli_query($conn, $query);
    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}

//Send User Credentials
if ($form_name == 'send_user_credentials') {

    $edit_id = $_POST['unique_user_id'];
    $credential_sts = $_POST['status'];

    if ($_POST['unique_user_id'] != '') {
        $select_user_details_1 = "SELECT *FROM user_inquiry_list where `user_id`='" . $_POST['unique_user_id'] . "'";
        $select_user = mysqli_query($conn, $select_user_details_1);
        $count = mysqli_num_rows($select_user);
        // if ($count > 0) {
        $row = mysqli_fetch_assoc($select_user);
        $total_pkg = $row['no_of_package'];
        $user_id = $row['user_id'];
        $grn_no = $row['id'];
        $inv = $row['booking_id'];
        $grn_date = $row['booking_date'];
        $consignor = $row['consignor_name'];
        $consignee = $row['consignee_name'];
        $consignor_city = get_city_name($conn, $row['consignor_city']);
        $consignee_city = get_city_name($conn, $row['consignee_city']);
        $grn_date = $row['created_at'];
        $select_user_details  = "select *from users where user_id = '$user_id'";
        $get_result = mysqli_query($conn, $select_user_details);
        $row2 = mysqli_fetch_assoc($get_result);
        $get_name = $row2['user_name'];
        $get_username = $row2['email'];
        $get_password = $row2['password'];
		$get_password = dec_name($row2['password']);

        //  echo $get_name;
        //  echo $get_username;
        // echo $get_password;
        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                             
           Thank you for booking the consignment, Please find below the booking details for your reference.					
           <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
           <tr>
           <td >REF No	</td><td >' . $inv . '</td>
           </tr><tr>	
           <td >Date:	</td><td >	' . $grn_date . '	</td>	
           </tr><tr>	
           <td >No. of Pkgs.</td><td >' . $total_pkg . '</td>	
           </tr>
           <tr><td >Booked By	</td><td >' . $consignor . ' , ' . $consignor_city . '</td>	</tr>	
           <tr><td >Booked to	</td><td >	' . $consignee . ' , ' . $consignee_city . '</td>	</tr>	
           <tr>		
           <td >Status	</td><td >Pending</td>		
               </td>
                               </tr>
                           </table>	
           <br>
           <p style="color:green">Please Find Your Credential Below to access User Dashboard.
           </p>
           
              <hr>
              <table>
              <thead>
              <tr>
              <th>Username</th>
              <th>Password</th>
              </tr>
              </thead>
              <tbody>
              <tr>
              <td style="text-align:center">' . $get_username . '</td>
              <td style="text-align:center">' . $get_password . '</td>
              </tr>
              </tbody>
           <br>	
           <small><b>Note:</b>Do not Share Your Credential with anyone.</small>
           <br>
           <p><b>Follow the Step Below:<b></p>
           <ul>
           <li>Click <a href="https://elitewave360.in/user/login.php">here</a> for User Dashboard</li>
           </ul>';
        $to_name = (get_user($conn, $user_id));

        $to_email = (get_user_email($conn, $user_id));
        //print_r($to_email);

        $mail = sendAppMail($to_name, $to_email, 'Consignment Details With User Credential', $msg);
        //} else {

        //         $select_user_details  = "select *from users where user_id = '$edit_id'";
        //         $get_result = mysqli_query($conn, $select_user_details);
        //         $row2 = mysqli_fetch_assoc($get_result);
        //         $get_name = $row2['user_name'];
        //         $get_username = $row2['email'];
        //         $get_password = $row2['password'];

        //         //  echo $get_name;
        //         //  echo $get_username;
        //         // echo $get_password;
        //         $msg = '<p style="line-height: 24px; margin-bottom:15px;">   					
        //    <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">

        //    <br>
        //    <p style="color:green">Please Find Your Credential Below to access User Dashboard.
        //    </p>

        // 	  <hr>
        // 	  <table>
        // 	  <thead>
        // 	  <tr>
        // 	  <th>Username</th>
        // 	  <th>Password</th>
        // 	  </tr>
        // 	  </thead>
        // 	  <tbody>
        // 	  <tr>
        // 	  <td style="text-align:center">' . $get_username . '</td>
        // 	  <td style="text-align:center">' . $get_password . '</td>
        // 	  </tr>
        // 	  </tbody>
        //    <br>	
        //    <small><b>Note:</b>Do not Share Your Credential with anyone.</small>
        //    <br>
        //    <p><b>Follow the Step Below:<b></p>
        //    <ul>
        //    <li>Click <a href="https://elitewave360.in/user/login.php">here</a> for User Dashboard</li>
        //    </ul>';
        //         $to_name = (get_user($conn, $edit_id));

        //         $to_email = (get_user_email($conn, $edit_id));
        //         //print_r($to_email);

        //         $mail = sendAppMail($to_name, $to_email, 'User Credentials', $msg);
        //}
    }
    if ($mail) {
        if ($credential_sts == '') {
            $update_credential  = mysqli_query($conn, "UPDATE users set credential_status = '1' where `user_id`= '$edit_id' ");
        }
        echo "1";
    } else {
        echo "0";
    }
}


if ($form_name == "user_registration") {
    $reg_name = trim($_POST['reg_name']);
    $reg_email = $_POST['reg_email'];
    $reg_mobile = $_POST['reg_mobile'];
    $reg_company = trim($_POST['reg_company']);
    $reg_address = trim($_POST['reg_address']);
    $reg_contact_person = trim($_POST['reg_contact_person']);
    $reg_pincode = $_POST['reg_pincode'];
    $reg_state = $_POST['reg_state'];
    $reg_city = $_POST['reg_city'];
    $password = $_POST['password'];
    $reg_gst = trim($_POST['reg_gst']);
    $reg_pan = trim($_POST['reg_pan']);

    
    $check_email_exist = mysqli_query($conn, "SELECT email FROM `user_registrations` WHERE email = '$reg_email'");
    $count_email = mysqli_num_rows($check_email_exist);
    if($count_email > 0) {
        echo 2; exit;
    }

    $check_client_email = mysqli_query($conn, "SELECT email FROM `client` WHERE email = '$reg_email'");
    $count_client_email = mysqli_num_rows($check_client_email);
    if($count_client_email > 0) {
        echo 2; exit;
    }

    $query = "INSERT INTO `user_registrations`(`name`, `email`, `mobile`, `company_name`, `contact_person`, `address`, `state`, `city`, `pincode`, `gst`, `pan`, `password`, `client_status`, `user_status`, `read_status`) VALUES ('$reg_name','$reg_email','$reg_mobile','$reg_company','$reg_contact_person','$reg_address','$reg_state','$reg_city','$reg_pincode','$reg_gst','$reg_pan','$password',0,0,0)";
    $result = mysqli_query($conn, $query);

    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}

if ($form_name == "register_user_client") {
    $register_user_id = $_POST['edit_id'];
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $address1 = $_POST['address1'];
    if ($_POST['address2'] != '') {
        $address2 = $_POST['address2'];
    } else {
        $address2 = NULL;
    }
    $state = $_POST['state'];
    $city = $_POST['city'];
    $billing_code = strtoupper($_POST['billing_code']); //upper case change
    if ($_POST['pincode'] != '') {
        $pincode = $_POST['pincode'];
    } else {
        $pincode = 0;
    }
    $email = $_POST['email'];
    $email_cc = $_POST['email_cc'];
    $conatct = $_POST['contact_no'];
    $gst = $_POST['gst_no'];
    $pan = $_POST['pan_no'];
    if ($_POST['multiple_branches'] != '') {
        $multiple_branches = $_POST['multiple_branches'];
    } else {
        $multiple_branches = 0;
    }
    if ($_POST['transit_automation'] != '') {
        $transit_automation = $_POST['transit_automation'];
    } else {
        $transit_automation = 0;
    }

    $query = "INSERT INTO `client`(`client_company_name`, `contact_person`, `billing_code`, `address1`, `address2`, `city`, `state`, `pincode`, `email`, `contact_no`, `gst_no`, `pan_no`,`automation`,`multiple_branches`,`created_at`, `updated_at`,`created_by`,`status`, `approve_status`,`invoice_frequency`)
     VALUES('$company_name','$contact_person','$billing_code','$address1','$address2','$city','$state',$pincode,'$email','$conatct','$gst','$pan',$multiple_branches,$transit_automation,'$created_at','$updated_at','$created_by',0,0,0)";
    $result = mysqli_query($conn, $query);

    $update_credential  = mysqli_query($conn, "UPDATE user_registrations SET client_status = 1 WHERE md5(id)= '$register_user_id'");

    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}

if ($form_name == "add_register_user") {
    $edit_id = $_POST['edit_id'];
    $user_name =  $_POST['user_name'];
    $role = $_POST['role'];
    $company_type = $_POST['company_type'];
    $company_name = $_POST['company_name'];
    $branch_name = $_POST['branch'];
    $contact_no = $_POST['contact_no'];

    $user_email = $_POST['user_email'];
    $password =  $_POST['password'];

    
    $query = "INSERT INTO users(company_name,branch_name,company_type,`role`,contact_no,email,`password`,user_name,created_at,created_by,`status`,credential_status)VALUES ('" . $company_name . "','" . $branch_name . "','" . $company_type . "','" . $role . "','" . $contact_no . "','" . $user_email . "','" . $password . "','" . $user_name . "','" . $created_at . "','" . $created_by . "','1','1')";
    $update_credential  = mysqli_query($conn, "UPDATE user_registrations SET user_status = 1 WHERE md5(id)= '$edit_id'");
    $result = mysqli_query($conn, $query);

    if ($result) {
        $subject = "User Credential";
        $body = '<p style="line-height: 24px; margin-bottom:15px;">Please Find Your Credential Below to access User Dashboard.</p>
        <hr>
        <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
        <tr>	
        <td >Username:	</td><td >	' . $user_email . '	</td>	
        </tr><tr>	
        <td >Password:</td><td >' . $password . '</td>	
        </tr>
        </table>
        <br>
        <br>	
        <small><b>Note:</b>Do not Share Your Credential with anyone.</small>
        <br>
        <ul>
        <li>Click <a href="https://elitewave360.in/user/login.php">here</a> for User Dashboard</li>
        </ul>';
        sendAppMail($user_name, $user_email, $subject, $body);
        echo 1;
    } else {
        echo 0;
    }
}
