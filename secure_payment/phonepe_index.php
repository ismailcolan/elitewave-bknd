<?php
require_once('./../web/include/set_connection.php');
require_once('function_phonepe.php');
if (session_id() == '') {
    session_start();
}
if ($_SESSION['phonepe_verify'] == '') {
    header('Location: http://localhost/graciousexpress/');
}

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
function generateRandomString($grn_no = '123456')
{
    global $grn_no;
    $timestamp = time();
    $additionalInfo = $grn_no;
    $uniqueKey = $additionalInfo . $timestamp;
    $hashedKey = sha1($uniqueKey);
    return $grn_no;
}

$merchantUserId = generateRandomString($grn_no);
$merchantId = 'GRACIOUSONLINE'; // sandbox or test merchantId
$apiKey = "6078cc82-efbb-4816-bb6a-a7b51b48c05b"; // sandbox or test APIKEY
$redirectUrl = 'http://localhost/graciousexpress/secure_payment/phonepe_payment-success.php';

$order_id = uniqid() . $grn_no . time();
$name = "Gracious Express Website";
$description = 'Payment for Product/Service';
$merchantTransactionId = time() . $grn_no;
$grn_no = enc_name($grn_no);
$getDatas->action_query("UPDATE phonepe_payment SET phonepeOrderId='$order_id',phonepePaymentId='$merchantTransactionId',paymentStatus = 'Before_Link',pay_track_status='2' WHERE grn_no IN('$grn_no')");
$getDatas->action_query("INSERT INTO phonepe_tracking (`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id`) SELECT `company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id` FROM phonepe_payment WHERE grn_no = '$grn_no' ");


$paymentData = array(
    'merchantId' => $merchantId,
    'merchantTransactionId' => $merchantTransactionId,
    "merchantUserId" => $merchantUserId,
    'amount' => $amount * 100,
    'redirectUrl' => $redirectUrl,
    'redirectMode' => "POST",
    'callbackUrl' => $redirectUrl,
    "merchantOrderId" => $order_id,
    "mobileNumber" => $phone,
    "message" => $description,
    "email" => $email,
    "shortName" => $name,
    "paymentInstrument" => array(
        "type" => "PAY_PAGE",
    )
);


$jsonencode = json_encode($paymentData);
$payloadMain = base64_encode($jsonencode);
$salt_index = 1; //key index 1
$payload = $payloadMain . "/pg/v1/pay" . $apiKey;
$sha256 = hash("sha256", $payload);
$final_x_header = $sha256 . '###' . $salt_index;
$request = json_encode(array('request' => $payloadMain));

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.phonepe.com/apis/hermes/pg/v1/pay",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $request,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-VERIFY: " . $final_x_header,
        "accept: application/json"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
if ($err) {
    // echo "cURL Error #:" . $err;
    $_SESSION['response'] = "failed";
    $_SESSION['msg'] = "Process Failed, Something went wrong";
    $_SESSION['paymentId'] = "";
    header('location: http://localhost/graciousexpress');
} else {
    $res = json_decode($response);
    if (isset($res->success) && $res->success == '1') {
        $paymentCode = $res->code;
        $paymentMsg = $res->message;
        $payUrl = $res->data->instrumentResponse->redirectInfo->url;
        $getDatas->action_query("UPDATE phonepe_payment SET paymentStatus = 'Link_Generated',pay_track_status='3',payment_original_status = '$paymentMsg' WHERE grn_no IN('$grn_no') AND phonepePaymentId = '$merchantTransactionId' AND phonepeOrderId='$order_id'");
        $getDatas->action_query("INSERT INTO phonepe_tracking (`company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id`) SELECT `company_name`, `email`, `phone`, `grn_no`, `invoice_no`, `client_id`, `amount`, `paid`, `balance`, `phonepeOrderId`, `phonepePaymentId`, `paymentStatus`, `created_at`, `updated_at`, `pay_track_status`, `pay_atmpt_count`, `payment_original_status`, `payment_original_code`, `verified_amount`, `verified_message`,`grn_date`,`trans_id` FROM phonepe_payment WHERE grn_no = '$grn_no' ");
       
        header('Location:' . $payUrl);
    } else {
        // echo "Could not load payment url";
        $_SESSION['response'] = "failed";
        $_SESSION['msg'] = "Could not load payment url";
        $_SESSION['paymentId'] = "";
     header('location: http://localhost/graciousexpress');
    }
}

?>