<?php
include("./config.ini.php");
session_start();
$output = array();

$query =  (isset($_GET['data'])) ? $_GET['data'] : '' ;
// echo $query;
// exit();
parse_str($query, $output);

$unserialize = unserialize($output['aParam']);

// echo "<pre>";
// print_r($unserialize);
// echo "</pre>";
// exit();
// $grn_no = implode("','",$unserialize['grn_no'][0]);
$name =  isset($unserialize['company_name']) ? $unserialize['company_name'] : '';
$email =  isset($unserialize['email']) ? $unserialize['email'] : '' ;
$phone =  isset($unserialize['phone']) ? $unserialize['phone'] : '';
$amount = isset($unserialize['amount']) ? implode(',',$unserialize['amount']) : '';
$grn_no = isset($unserialize['grn_no']) ? implode("', '",$unserialize['grn_no']) : '';
$invoice_no = isset($unserialize['invoice_no']) ? implode(',',$unserialize['invoice_no']) : '';
$client_id =  isset($unserialize['client_id']) ? $unserialize['client_id'] : '';
//exit;
// $conn = mysqli_connect("localhost",'staging','vySzrpsqDRupDHS','staging');

//Check Payment Made or Not

// $grn_no = array('Touh00061','Touh00062');

// $grn_imp = implode("', '",$grn_no);
if($grn_no != ''){
     $sql = "SELECT * FROM `razorpay_payment` where grn_no IN('$grn_no') and razorpayPaymentId != '' and client_id = '$client_id' ";
  // exit();
    $q = mysqli_query($conn,$sql);
    
    $count = mysqli_num_rows($q);
    
    if($count > 0){
       // echo "yes";
    
        
        echo "<script>alert('Link Expired / Payment Paid');</script>";
       
        header('location:http://localhost/graciousexpress/');

        exit();
       
    }else{
        $_SESSION['verify'] = 1;
        //echo 'Redirect';
        header('location:http://localhost/graciousexpress/redirect.php?data='.urlencode($query).'');
        exit();
    }
}else{
    echo "You are not allowed";
     // header('location:http://localhost:8080/dummy/razorpay/customrazorpay/paymentForm.php?data='.urlencode($query).'');
    // exit();
}


?>