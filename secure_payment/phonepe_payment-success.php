<?php
require_once('./../web/include/set_connection.php');
require_once('function_phonepe.php');
$response_code = $_POST['code'];
$marchant_id = $_POST['merchantId'];
$merchantOrderId = $_POST['merchantOrderId'];
$transaction_id = $_POST['transactionId'];
$checksum = $_POST['checksum'];
if ($response_code == 'PAYMENT_PENDING') {
    $payment_original_code = '1';
} else if ($response_code == 'PAYMENT_SUCCESS') {
    $payment_original_code = '2';
} else {
    $payment_original_code = '3';
}

$getDatas->action_query("UPDATE phonepe_payment SET paymentStatus = 'Link_Responsed',pay_track_status='4',payment_original_status='$response_code',payment_original_code='$payment_original_code' WHERE  phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId'");

$getDatas->action_query("INSERT INTO phonepe_tracking (`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id`) SELECT `company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id` FROM phonepe_payment WHERE  phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId' ");
$saltKey = '6078cc82-efbb-4816-bb6a-a7b51b48c05b'; //key index 1
$saltIndex = 1;
$payload = "/pg/v1/status/" . $marchant_id . "/" . $transaction_id . $saltKey;
$sha256 = hash('sha256', $payload);
$sha2562 = $sha256 . "###" . $saltIndex;
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.phonepe.com/apis/hermes/pg/v1/status/" . $marchant_id . "/" . $transaction_id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "accept: application/json",
        "X-MERCHANT-ID: " . $marchant_id,
        "X-VERIFY: " . $sha2562,
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    
    $verified_code = 'error';
    $verified_message = 'error';
    $verified_merchantTransactionId = 'error';
    $verified_state = 'error';
    $verified_amount = 'error';
    $getDatas->action_query("UPDATE phonepe_payment SET paymentStatus = 'Link_Verified_Failed',pay_track_status='6',payment_original_status='$verified_code',payment_original_code='$verified_state',verified_amount='$verified_amount',verified_message='$verified_message' WHERE phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId'");
    $getDatas->action_query("INSERT INTO phonepe_tracking (`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id`) SELECT `company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id` FROM phonepe_payment WHERE phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId' ");
    // echo "cURL Error #:" . $err;
    $_SESSION['response'] = "failed";
    $_SESSION['msg'] = "Process Failed, Something went wrong";
    $_SESSION['paymentId'] = "";
    header('location: http://localhost/graciousexpress');

} else {

    $response = json_decode($response);
    $verified_code = $response->code;
    $verified_message = $response->message;
    $verified_merchantTransactionId = $response->data->merchantTransactionId;
    $verified_state = $response->data->state;
    if ($verified_state == 'PENDING') {
        $verified_state1 = 'V1';
        // $verified_state1 = 'V2';
    } else if ($verified_state == 'COMPLETED') {
        $verified_state1 = 'V2';
    } else {
        $verified_state1 = 'V3';
    }
    $verified_amount = $response->data->amount / 100;
    if ($verified_state1 == 'V2') {
        $getDatas->action_query("UPDATE phonepe_payment SET paymentStatus = 'Link_Verified',pay_track_status='5',payment_original_status='$verified_code',payment_original_code='$verified_state1',verified_amount='$verified_amount',verified_message='$verified_message',paid=CONCAT('$verified_amount','.00'),balance= CONCAT((balance-'$verified_amount'),'.00') WHERE phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId'");
        $getDatas->action_query("INSERT INTO phonepe_tracking (`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id`) SELECT `company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id` FROM phonepe_payment WHERE phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId' ");


        $gettrasData = $getDatas->query("SELECT trans_id,grn_date,grn_no,invoice_no,client_id FROM phonepe_payment WHERE phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId'", 2);

        if (count($gettrasData)) {

            $set_grn_date = dec_name($gettrasData[0]['grn_date']);
            $get_table = get_trans_table_name($set_grn_date);
            if ($get_table) {
                $get_table_name = $get_table[0];
            } else {
                $get_table_name = '';
            }
            if ($get_table_name != '') {
                $set_trans_id = dec_name($gettrasData[0]['trans_id']);
                $set_grn_no = dec_name($gettrasData[0]['grn_no']);
                $set_invoice_no = dec_name($gettrasData[0]['invoice_no']);
                $set_client_id = dec_name($gettrasData[0]['client_id']);
                $set_updates = $getDatas->action_query("UPDATE $get_table_name SET `paid_amount` = CONCAT((paid_amount+'$verified_amount'),'.00'), `balance` = CONCAT((balance-'$verified_amount'),'.00'),`paid_status` = '1' WHERE `transaction_id` ='$set_trans_id' AND `grn_no` = '$set_grn_no' AND `invoice_no` = '$set_invoice_no' ");

                if ($set_updates) {
                    $check_clt_exist = $getDatas->query("SELECT * FROM `client_outstanding` WHERE client_id = '$set_client_id' ", 2);
                    if (count($check_clt_exist) > 0) {
                        $c_id = $check_clt_exist[0]['client_id'];
                        $c_total_amt = $check_clt_exist[0]['total'];
                        $c_amount_paid = $check_clt_exist[0]['amount_paid'];
                        $c_balance = $check_clt_exist[0]['balance'];
                        $upadate_amount_paid = (float) $verified_amount + (float) $c_amount_paid;
                        $update_balance = (float) $c_total_amt - (float) $upadate_amount_paid;
                        $update_outstanding = $getDatas->action_query("UPDATE `client_outstanding` SET `total`='$c_total_amt',`amount_paid`='$upadate_amount_paid',`balance`='$update_balance' WHERE client_id = '$c_id'");
                        $_SESSION['response'] = "success";
                        $_SESSION['msg'] = "Payment Successful";
                        $_SESSION['paymentId'] = "$transaction_id";
                        header('location: http://localhost/graciousexpress');
                        // echo "All Updated Successfully".$transaction_id;
                    } else {
                        $insert_outstanding = $getDatas->action_query("INSERT INTO `client_outstanding`(`client_id`, `total`, `amount_paid`, `balance`) VALUES ('$set_client_id','$verified_amount','0','$verified_amount')");
                    }

                } else {
                    // echo 'Could Not Update GEN Data 2';
                    $_SESSION['response'] = "failed";
                    $_SESSION['msg'] = "Process Failed, Something went wrong";
                    $_SESSION['paymentId'] = "";
                    header('location: http://localhost/graciousexpress');
                }
            } else {
                // echo 'no Table Found';
                $_SESSION['response'] = "failed";
                $_SESSION['msg'] = "Process Failed, Something went wrong";
                $_SESSION['paymentId'] = "";
                header('location: http://localhost/graciousexpress');
            }

        } else {
            // echo "Cound not update data on GRN row table 1";
            $_SESSION['response'] = "failed";
            $_SESSION['msg'] = "Process Failed, Something went wrong";
            $_SESSION['paymentId'] = "";
            header('location: http://localhost/graciousexpress');
        }

    } else {
        $getDatas->action_query("UPDATE phonepe_payment SET paymentStatus = 'Link_Verified',pay_track_status='5',payment_original_status='$verified_code',payment_original_code='$verified_state1',verified_amount='$verified_amount',verified_message='$verified_message' WHERE phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId'");
        $getDatas->action_query("INSERT INTO phonepe_tracking (`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id`) SELECT `company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id` FROM phonepe_payment WHERE phonepePaymentId = '$transaction_id' AND phonepeOrderId='$merchantOrderId' ");
    }

}
?>