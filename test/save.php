<?php
$con = mysqli_connect("localhost",'root','','bookconsignment');
// if(!$con){
//     echo "Connection Failed";
// }else{
//     echo "Connection Success";
// }

    if(isset($_POST['sendername'])){
        $user_name = str_replace(' ', '', $_POST['sendername']);
        random_username($user_name);
        exit();
    }

    function random_username($user_name){
        $new_name = $user_name.mt_rand(0,10000);
        check_username($new_name,$user_name);
    }

    function check_username($new_name,$user_name){
        global $con;
        $check_user = mysqli_query($con,"SELECT * FROM user WHERE username='$new_name' "); 

        if(mysqli_num_rows($check_user)){
        
            random_username($user_name);

        }else{
            $generate_pass = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
            $rand_password = substr(str_shuffle($generate_pass),0,8);

            //echo $new_name;
            //echo $password;

            $name = $_POST['sendername'];
            $user_name = $new_name;
            $password = $rand_password;

            $register_user = mysqli_query($con,"INSERT INTO user (name,username,password) VALUES('$name','$user_name','$password')");
            if($register_user){
                $user_id = mysqli_insert_id($con);

                $booking_id = "AWB85SFTN02";

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
                

                $consignor_contact = $_POST['sendercontact'];
                $consignor_email = $_POST['senderemail'];
                $consignor_city = $_POST['sendercity'];
                $consignor_address = $_POST['senderaddress'];
                $consignor_town = $_POST['senderarea'];
                $consignor_package = $_POST['packageqty'];
                $consignor_kg = $_POST['packagewgt'];
                $consignee_name = $_POST['recievername'];
                $consignee_contact = $_POST['recievercontact'];
                $consignee_email = $_POST['recieveremail'];
                $consignee_city = $_POST['recievercity'];
                $consignee_address = $_POST['recieveraddress'];
                $consignee_town = $_POST['recieverarea'];
                $consignee_docname = $_POST['docname'];
                $consignee_docdata = $_POST['docdata'];

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
                $booked_query = mysqli_query($con,"INSERT INTO `consignment`(`user_id`, `booking_id`, `customer_details`, `shipping_mode`, `pay_mode`, `consignor_name`, `consignor_contact`, `consignor_email`, `consignor_city`, `consignor_address`, `consignor_town`, `no_of_package`, `kgs`, `consignee_name`, `consignee_contact`, `consignee_email`, `consignee_city`, `consignee_address`, `consignee_town`, `document_count`, `document_data`, `attchment`,`booking_date`,`status`) 
                VALUES ($user_id,'$booking_id',' $customer','$shipping_mode','$pay_mode','$name','$consignor_contact','$consignor_email','$consignor_city','$consignor_address','$consignor_town','$consignor_package','$consignor_kg','$consignee_name','$consignee_contact','$consignee_email','$consignee_city','$consignee_address','$consignee_town','$consignee_docname','$consignee_docdata','$name','$booked_date','$status' ) ");
                
                $last_consignment_id = mysqli_insert_id($con);
                // var_dump($booked_query);
                
                   $update_attachment = mysqli_query($con,"UPDATE consigment SET attchment = '$name' where id = '$last_consignment_id'");
                   $select_booking_id = mysqli_query($con,"SELECT *from consignment where id = '$last_consignment_id' ");
                   $row = mysqli_fetch_assoc($select_booking_id);

                  $booking_id = $row['booking_id'];

                  if($booking_id){
                    
                   
                       echo $booking_id;

               }else{
                   echo "0";
               }
                // echo "User Registered Successfully";
                
            }
            else{
                echo "Registration Failed.Try again!";
            }
        }

    }

    
   
?>