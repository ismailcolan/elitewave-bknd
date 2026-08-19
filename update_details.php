<?php
error_reporting(1);
require_once("web/include/connect.php");
//$con = mysqli_connect("localhost",'root','','bookconsignment');
    if($_POST['blog_id']==""){
                $consignor_name = $_POST['sendername'];
                $consignor_contact = $_POST['sendercontact'];
                $consignor_email = $_POST['senderemail'];   
                $consignor_city = $_POST['sendercity'];  
                $consignor_address = $_POST['senderaddress']; 
                $consignor_area = $_POST['senderarea']; 
                $package_qty = $_POST['packageqty']; 
                $package_weight = $_POST['packagewgt']; 
                $consignee_name = $_POST['recievername']; 
                $consignee_contact = $_POST['recievercontact']; 
                $consignee_email = $_POST['recieveremail']; 
                $consignee_city = $_POST['recievercity']; 
                $consignee_address = $_POST['recieveraddress']; 
                $consignee_area = $_POST['recieverarea']; 
                
                $booked_date = date('d-m-Y');
                $status = 0;
            
                $booked_query = mysqli_query($conn,"INSERT INTO `draft_consignment`(`consignor_name`, `consignor_contact`, `consignor_email`,`consignor_city`,`consignor_address`,`consignor_town`,`no_of_package`,`kgs`,`consignee_name`,`consignee_contact`, `consignee_email`, `consignee_city`, `consignee_address`, `consignee_town`,`created_at`,`published`,`status`) 
                VALUES ('$consignor_name','$consignor_contact','$consignor_email','$consignor_city','$consignor_address','$consignor_area','$package_qty','$package_weight','$consignee_name','$consignee_contact','$consignee_email','$consignee_city','$consignee_address','$consignee_area','$booked_date','Draft','$status' ) ");
                
                $last_consignment_id = mysqli_insert_id($conn);
                echo  $last_consignment_id;
                }
                else{
                    
                    $consignor_name = $_POST['sendername'];
                    $consignor_contact = $_POST['sendercontact'];
                    $consignor_email = $_POST['senderemail']; 
                    $consignor_city = $_POST['sendercity'];  
                    $consignor_address = $_POST['senderaddress']; 
                    $consignor_area = $_POST['senderarea']; 
                    $package_qty = $_POST['packageqty']; 
                    $package_weight = $_POST['packagewgt']; 
                    $consignee_name = $_POST['recievername']; 
                    $consignee_contact = $_POST['recievercontact']; 
                    $consignee_email = $_POST['recieveremail']; 
                    $consignee_city = $_POST['recievercity']; 
                    $consignee_address = $_POST['recieveraddress']; 
                    $consignee_area = $_POST['recieverarea']; 
                    $blog_id = $_POST['blog_id'];
                    $update = mysqli_query($conn,"UPDATE `draft_consignment` SET `consignor_name` = '$consignor_name', 
                    `consignor_contact` = '$consignor_contact',`consignor_email` = '$consignor_email',`consignor_city` = '$consignor_city',`consignor_address` = '$consignor_address', `consignor_town` = '$consignor_area',`no_of_package` = '$package_qty' ,`kgs` = '$package_weight',`consignee_name` = '$consignee_name',`consignee_contact` = '$consignee_contact',`consignee_email` = '$consignee_email',`consignee_city` = '$consignee_city',`consignee_address` = '$consignee_address',`consignee_town` = '$consignee_area',`status` = '1' WHERE `id` = '$blog_id' ");

                    }
?>