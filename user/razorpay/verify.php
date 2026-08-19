<?php
require('config.php');
session_start();
require('razorpay-php/Razorpay.php');
include('function.php');
include("../../config.ini.php");
// $conn = mysqli_connect("localhost", "staging", "vySzrpsqDRupDHS", "staging");

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$onlinePayUpdate =new Payments; 
$success = true;

$error = "Payment Failed";
$out_put = [];
if (empty($_POST['razorpay_payment_id']) === false)
{
    $api = new Api($keyId, $keySecret);

    try
    {
        // Please note that the razorpay order ID must
        // come from a trusted source (session here, but
        // could be database or something else)
        $attributes = array(
            'razorpay_order_id' => $_SESSION['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        );

        $api->utility->verifyPaymentSignature($attributes);
    }
    catch(SignatureVerificationError $e)
    {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }
}

if ($success === true)
{
    $razorpayOrderId  = $_SESSION['razorpay_order_id']; 
    $razorpayPaymentId = $_POST['razorpay_payment_id'];
    $name = $_SESSION['name'];
    $email = $_SESSION['email'];
    $phone = $_SESSION['phone'];
    $amount = $_SESSION['amount'];
    $grn_no = $_SESSION['grn_no'];
    $invoice_no = $_SESSION['invoice_no'];
    $client_id = $_SESSION['client_id'];
    $transaction_id = $_SESSION['transaction_id'];
    $grn_date = $_SESSION['grn_date'];
    $paymentStatus = "SUCCESS";
    $created_at=date('Y-m-d h:i:s');
    $updated_at=date('Y-m-d h:i:s');
    $q = "select *from razorpay_payment where email = '$email' and razorpayOrderId = '$razorpayOrderId' and razorpayPaymentId = '' ";
    $check_if_data_updated = mysqli_query($conn,$q);
    $count = mysqli_num_rows($check_if_data_updated);
    if($count > 0){
        if($onlinePayUpdate->updateOnlinePayment($conn,$email,$grn_date,$transaction_id,$razorpayOrderId,$razorpayPaymentId,$paymentStatus,$updated_at)){
            $onlinePayUpdate->removePendingPayment($conn,$grn_no,$invoice_no);
            $onlinePayUpdate->SetOutStandingInfo($conn,$client_id,$amount);

            $_SESSION['msg'] = 'Payment Successful';
            $_SESSION['paymentId'] = $razorpayPaymentId;
            header('Location: http://localhost/graciousexpress//user/booking_list.php');
            exit();
            
               
        }else{
            $_SESSION['msg'] = "sorry , Query could no execute...";
            
        }
    }else{
           $_SESSION['msg'] = "You have already submited.";    
    } 
    // $html = "<p>Your payment was successful</p>
    //          <p>Payment ID: {$_POST['razorpay_payment_id']}</p>";

}
else
{

    $paymentStatus = 'FAILURE';
    $updated_at=date('Y-m-d h:i:s');
    $onlinePay->UpdateupdateOnlinePayment($conn,$email,$razorOrderId,$razorpayPaymentId,$paymentStatus,$updated_at);
    $_SESSION['msg'] = "Your payment failed : ".$error;          
    header('Location: http://localhost/graciousexpress//user/booking_list.php');
    exit();
}
