<?php

require_once('web/include/set_connection.php');
require_once('secure_payment/function_phonepe.php');
session_start();
$output = array();
date_default_timezone_set('Asia/Kolkata');
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$query = (isset($_GET['data'])) ? $_GET['data'] : '';
parse_str($query, $output);
$unserialize = unserialize($output['aParam']);
$trans_id = isset($unserialize['transaction_id']) ? implode(',', $unserialize['transaction_id']) : '';

$name = isset($unserialize['company_name']) ? $unserialize['company_name'] : '';
$email = isset($unserialize['email']) ? $unserialize['email'] : '';
$phone = isset($unserialize['phone']) ? $unserialize['phone'] : '';
$amount = isset($unserialize['amount']) ? implode(',', $unserialize['amount']) : '';
$grn_no = isset($unserialize['grn_no']) ? implode("', '", $unserialize['grn_no']) : '';
$invoice_no = isset($unserialize['invoice_no']) ? implode(',', $unserialize['invoice_no']) : '';
$client_id = isset($unserialize['client_id']) ? $unserialize['client_id'] : '';
$grn_date = isset($unserialize['grn_date']) ? implode("', '", $unserialize['grn_date']) : '';

if ($grn_no != '') {
    $grn_no = enc_name($grn_no);
    $client_id = enc_name($client_id);

    $payment_table = $getDatas->query("SELECT * FROM `phonepe_payment` WHERE grn_no IN('$grn_no') AND client_id = '$client_id' ", 2);

    if (count($payment_table) > 0) {
        $original_payment_status = $payment_table[0]['payment_original_code'];
        if ($original_payment_status == 'V2') {
            // echo "Payment already done for this GRN ";
            $_SESSION['msg'] = "Payment already done for this GRN";
            $_SESSION['response'] = "failed";
            $_SESSION['paymentId'] = "";
            header('location: http://localhost/graciousexpress');exit; // need to redirect from here
        } else {
            $getDatas->action_query("UPDATE phonepe_payment SET paid='0.00',balance=amount,phonepeOrderId='',phonepePaymentId='',paymentStatus='',pay_track_status='1',payment_original_status='',payment_original_code='',verified_amount='',verified_message='',pay_atmpt_count = pay_atmpt_count+1 WHERE grn_no IN('$grn_no')");
            $_SESSION['phonepe_verify'] = 1;
            header('location: http://localhost/graciousexpress/secure_payment/phonepe_index.php?data=' . urlencode($query));
        }


    } else {

        $name = enc_name($name);
        $email = enc_name($email);
        $phone = enc_name($phone);
        $invoice_no = enc_name($invoice_no);
        $grn_date = enc_name($grn_date);
        $trans_id = enc_name($trans_id);

        $insert = $getDatas->action_query("INSERT INTO `phonepe_payment`(`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`,`pay_track_status`,`pay_atmpt_count`,`grn_date`,`trans_id`) VALUES ('$name','$email','$phone','$grn_no','$invoice_no','$client_id','$amount','0.00','$amount','','','newly_inserted','$created_at','$updated_at','1','1','$grn_date','$trans_id')", 1);
        // echo "Inserted Successfully";
        $_SESSION['phonepe_verify'] = 1;
        header('location: http://localhost/graciousexpress/secure_payment/phonepe_index.php?data=' . urlencode($query));

    }
} else {
    // echo "<script>alert('Invalid User Details');</script>";
        $_SESSION['msg'] = "Invalid User Details";
        $_SESSION['response'] = "failed";
        $_SESSION['paymentId'] = "";
        header('location: http://localhost/graciousexpress');

}


?>