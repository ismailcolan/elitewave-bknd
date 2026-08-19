<?php
session_start();
error_reporting(1);
$con = mysqli_connect("localhost",'root','','bookconsignment');
require_once("include/connect.php");

require_once("web/appMail.php");
require_once("user/include/user-function.php");
$form_name = $_POST['form_name'];
$created_at = date('d-m-Y');
$updated_at = date('d-m-Y');
if($form_name == "add_new_form"){

    // if(isset($_POST['sender-email'])){
    //     $consignor_email = $_POST['sender-email'];
    //      $check_email = "SELECT * FROM consignment where consignor_email = '$consignor_email'";
    //      $result_email = mysqli_query($con,$check_email);
    //      $count= mysqli_num_rows($result_email);
    //      if($count==0){
    //          $consignor_email = $_POST['sender-email'];
            $generate_pass = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
            $rand_password = substr(str_shuffle($generate_pass),0,8);
            $email = $_POST['sender-email'];
            $username = $_POST['sender_name'];
            $password = $rand_password;
            $role = "USER";
            //INSERT INTO user (`name`,`username`,`password`) VALUES('$name','$email','$password')
           echo  $query = "INSERT INTO `users`(`email`, `password`, `role`, `company_name`, `company_type`, `branch_name`, `user_name`, `contact_no`, `created_by`, `updated_by`, `created_at`, `updated_at`, `status`, `password_status`)VALUES('$email','$password','$role',0,'GRACIOUS',0,'$username',0,0,0,'$created_at',0,0,0)";
            $register_user = mysqli_query($conn,$query);
            if($register_user){
                $user_id = mysqli_insert_id($conn);
                // var_dump($user_id);
                // exit();

                //$check_id = mysqli_query($con,"SELECT *FROM consignment");
                $check_id = mysqli_query($conn,"SELECT *FROM user_inquiry_list");
                $count_id = mysqli_num_rows($check_id);
                if($count_id == 0){
                    $serialno = 1;
                    $clientCode = "GERP";
                    $sequence = sprintf("%04d",$serialno);  
                    $booking_id = $clientCode.'-'.$sequence;
                   
                    $customer = $_POST['customer'];

                if($_POST['air']!=''){
                
                    $shipping_mode = $_POST['air'];

                }else if($_POST['train']!=''){
                   
                    $shipping_mode = $_POST['train'];

                }else if($_POST['roadsurface']!=''){
                   
                    $shipping_mode = $_POST['roadsurface'];

                }else if($_POST['roadexpress']!=''){
                   
                    $shipping_mode = $_POST['roadexpress'];

                }else{
                   
                    $shipping_mode = $_POST['localdelivery'];

                }

                if($_POST['tobilled']!=''){
                
                    $pay_mode = $_POST['tobilled'];

                }else if($_POST['topay']!=''){
                   
                    $pay_mode = $_POST['topay'];

                }else{
                    $pay_mode = $_POST['cod'];
                }
                
                $consignor_name = $_POST['sender_name'];
                $consignor_contact = $_POST['sender-contact-no'];
                $consignor_email = $_POST['sender-email'];   
                $consignor_city = $_POST['sender-city'];
                $consignor_address = $_POST['sender-address'];
                $consignor_town = $_POST['sender-area'];
                $consignor_package = $_POST['no-of-package'];
                $consignor_package_type= $_POST['package_type'];
                $consignor_invoice= $_POST['package-invoice'];
                $consignor_content=$_POST['package-content'];
                $consignor_kg = $_POST['package-wgt'];
                $consignor_gross_wt= $_POST['package-gross-wgt'];
                $consignor_charge_wt= $_POST['package-net-wgt'];
                $consignee_name = $_POST['reciever-name'];
                $consignee_contact = $_POST['reciever-contact-no'];
                $consignee_email = $_POST['reciever-email'];
                $consignee_city = $_POST['reciever-city'];
                $consignee_address = $_POST['reciever-address'];
                $consignee_town = $_POST['reciever-area'];
                $consignee_docname = $_POST['doc-name'];
                $consignee_docdata = $_POST['doc-data'];
                

                if($_FILES['file']['name'] != ''){
                    $test = explode('.', $_FILES['file']['name']);
                    $extension = end($test);    
                    $name = rand(100,999).'.'.$extension;
                
                    $location = 'uploads/'.$name;
                    move_uploaded_file($_FILES['file']['tmp_name'], $location);
                
                    // echo '<img src="'.$location.'" height="100" width="100" />';
                }
                //$consignee_attachment = $_POST['recieverarea'];
                $booked_date = date('d-m-Y');
                $status = 0;
                // $booked_query = mysqli_query($con,"INSERT INTO `consignment`(`user_id`, `booking_id`, `customer_details`, `shipping_mode`, `pay_mode`, `consignor_name`, `consignor_contact`, `consignor_email`, `consignor_city`, `consignor_address`, `consignor_town`, `no_of_package`, `kgs`, `consignee_name`, `consignee_contact`, `consignee_email`, `consignee_city`, `consignee_address`, `consignee_town`, `document_count`, `document_data`, `attchment`,`booking_date`,`status`) 
                // VALUES ($user_id,'$booking_id',' $customer','$shipping_mode','$pay_mode','$consignor_name','$consignor_contact','$consignor_email','$consignor_city','$consignor_address','$consignor_town','$consignor_package','$consignor_kg','$consignee_name','$consignee_contact','$consignee_email','$consignee_city','$consignee_address','$consignee_town','$consignee_docname','$consignee_docdata','$name','$booked_date','$status' ) ");
             $booked_query = mysqli_query($conn,"INSERT INTO `user_inquiry_list`(`user_id`, `booking_id`, `customer_details`, `shipping_mode`, `pay_mode`, `consignor_name`, `consignor_contact`, `consignor_email`, `consignor_city`, `consignor_address`, `consignor_town`, `no_of_package`, `type_of_package`, `invoice_no`, `contents`, `kgs`, `gross_weight`, `charged_weight`, `length`, `width`, `height`, `consignee_name`, `consignee_contact`, `consignee_email`, `consignee_city`, `consignee_address`, `consignee_town`, `document_count`, `document_data`, `attchment`, `created_by`, `created_at`, `updated_at`, `updated_by`, `status`) 
                                                                           VALUES ('$user_id,'$booking_id',' $customer','$shipping_mode','$pay_mode','$consignor_name','$consignor_contact','$consignor_email','$consignor_city','$consignor_address','$consignor_town','$consignor_package','$consignor_package_type','$consignor_invoice','$consignor_content','$consignor_kg','$consignor_gross_wt','$consignor_charge_wt','$consignee_name','$consignee_contact','$consignee_email','$consignee_city','$consignee_address','$consignee_town','$consignee_docname','$consignee_docdata','$name','0','$booked_date','0','0','$status' ) ");
                
                $last_consignment_id = mysqli_insert_id($conn);
                
                   $select_booking_id = mysqli_query($conn,"SELECT *from user_inquiry_list where id = '$last_consignment_id' ");
                   $row = mysqli_fetch_assoc($select_booking_id);

                  $booking_id = $row['booking_id'];

                  if($booking_id){
                    
                       echo $booking_id;

               }else{
                   echo "0";
               }
                    
                } else{

                $check_again_id = mysqli_query($conn,"SELECT *from user_inquiry_list ORDER BY id DESC LIMIT 1");
                $get_data = mysqli_fetch_array($check_again_id);
                $last_id = $get_data['id'];
                $rest_id = substr("$last_id", -4);
                $insert_id = "$rest_id" + 1;
                $clientCode = "GERP";
                $sequence = sprintf("%04d",$insert_id);  
                $booking_id = $clientCode.'-'.$sequence;
                $check_again_userid = mysqli_query($conn,"SELECT *FROM users ORDER BY `user_id` DESC LIMIT 1");
                $get_user_id = mysqli_fetch_array($check_again_userid);
                $user_id = $get_user_id['id'];
                //$user_id = 1;
                $customer = $_POST['customer'];

                if($_POST['air']!=''){
                
                    $shipping_mode = $_POST['air'];

                }else if($_POST['train']!=''){
                   
                    $shipping_mode = $_POST['train'];

                }else if($_POST['roadsurface']!=''){
                   
                    $shipping_mode = $_POST['roadsurface'];

                }else if($_POST['roadexpress']!=''){
                   
                    $shipping_mode = $_POST['roadexpress'];

                }else{
                   
                    $shipping_mode = $_POST['localdelivery'];

                }

                if($_POST['tobilled']!=''){
                
                    $pay_mode = $_POST['tobilled'];

                }else if($_POST['topay']!=''){
                   
                    $pay_mode = $_POST['topay'];

                }else{
                    $pay_mode = $_POST['cod'];
                }
                
                $consignor_name = $_POST['sender_name'];
                $consignor_contact = $_POST['sender-contact-no'];
                $consignor_email = $_POST['sender-email'];   
                $consignor_city = $_POST['sender-city'];
                $consignor_address = $_POST['sender-address'];
                $consignor_town = $_POST['sender-area'];
                $consignor_package = $_POST['no-of-package'];
                $consignor_package_type= $_POST['package_type'];
                $consignor_invoice= $_POST['package-invoice'];
                $consignor_content=$_POST['package-content'];
                $consignor_kg = $_POST['package-wgt'];
                $consignor_gross_wt= $_POST['package-gross-wgt'];
                $consignor_charge_wt= $_POST['package-net-wgt'];
                $consignee_name = $_POST['reciever-name'];
                $consignee_contact = $_POST['reciever-contact-no'];
                $consignee_email = $_POST['reciever-email'];
                $consignee_city = $_POST['reciever-city'];
                $consignee_address = $_POST['reciever-address'];
                $consignee_town = $_POST['reciever-area'];
                $consignee_docname = $_POST['doc-name'];
                $consignee_docdata = $_POST['doc-data'];

                if($_FILES['file']['name'] != ''){
                    $test = explode('.', $_FILES['file']['name']);
                    $extension = end($test);    
                    $name = rand(100,999).'.'.$extension;
                
                    $location = 'uploads/'.$name;
                    move_uploaded_file($_FILES['file']['tmp_name'], $location);
                }
                $booked_date = date('d-m-Y');
                $status = 0;
                
                // $booked_query = mysqli_query($con,"INSERT INTO `consignment`(`user_id`, `booking_id`, `customer_details`, `shipping_mode`, `pay_mode`, `consignor_name`, `consignor_contact`, `consignor_email`, `consignor_city`, `consignor_address`, `consignor_town`, `no_of_package`, `kgs`, `consignee_name`, `consignee_contact`, `consignee_email`, `consignee_city`, `consignee_address`, `consignee_town`, `document_count`, `document_data`, `attchment`,`booking_date`,`status`) 
                // VALUES ($user_id,'$booking_id',' $customer','$shipping_mode','$pay_mode','$consignor_name','$consignor_contact','$consignor_email','$consignor_city','$consignor_address','$consignor_town','$consignor_package','$consignor_kg','$consignee_name','$consignee_contact','$consignee_email','$consignee_city','$consignee_address','$consignee_town','$consignee_docname','$consignee_docdata','$name','$booked_date','$status' ) ");
                $booked_query = mysqli_query($conn,"INSERT INTO `user_inquiry_list`(`user_id`, `booking_id`, `customer_details`, `shipping_mode`, `pay_mode`, `consignor_name`, `consignor_contact`, `consignor_email`, `consignor_city`, `consignor_address`, `consignor_town`, `no_of_package`, `type_of_package`, `invoice_no`, `contents`, `kgs`, `gross_weight`, `charged_weight`, `length`, `width`, `height`, `consignee_name`, `consignee_contact`, `consignee_email`, `consignee_city`, `consignee_address`, `consignee_town`, `document_count`, `document_data`, `attchment`, `created_by`, `created_at`, `updated_at`, `updated_by`, `status`) 
                                                                           VALUES ('$user_id,'$booking_id',' $customer','$shipping_mode','$pay_mode','$consignor_name','$consignor_contact','$consignor_email','$consignor_city','$consignor_address','$consignor_town','$consignor_package','$consignor_package_type','$consignor_invoice','$consignor_content','$consignor_kg','$consignor_gross_wt','$consignor_charge_wt','$consignee_name','$consignee_contact','$consignee_email','$consignee_city','$consignee_address','$consignee_town','$consignee_docname','$consignee_docdata','$name','0','$booked_date','0','0','$status' ) ");
                $last_consignment_id = mysqli_insert_id($conn);
                $select_booking_id = mysqli_query($conn,"SELECT *from user_inquiry_list where id = '$last_consignment_id' ");
                $row = mysqli_fetch_assoc($select_booking_id);

                $booking_id = $row['booking_id'];

                if($booking_id){
                   
                    echo $booking_id;

               }else{

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



?>