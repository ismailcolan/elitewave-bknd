<?php
    
    if(isset($_POST['amount'])){
        $amount= $_POST['amount'] * 100;
    $merchant_key = "099eb0cd-02cf-4e2a-8aca-3e6c6aff0399";
    $data = array(
        "merchantId" => "PGTESTPAYUAT",
        "merchantTransactionId" => "IBRA1234567890", "merchantUserId" => "IBRA1234",
        "amount" => $amount*100,
        "redirectUrl" => "http://localhost/payment_api_test/payment_success.php",
        "redirectMode" => "POST",
        "callbackUrl" => "http://localhost/payment_api_test/payment_success.php", 
        "payment Instrument" => array(
        "type" => "PAY_PAGE"
        )
    );
        // Convert the Payload to JSON and encode as Base64 SpayloadMain = base64_encode(json_encode($data));
        $payloadMain = base64_encode(json_encode($data));
        $payload= $payloadMain."/pg/v1/pay".$merchant_key;
        $checksum = hash('sha256', $payload);
        $Checksum = $checksum. "###1";
        // echo $payloadMain;
        // echo "<br><br>";
        // echo $Checksum;exit;


$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode([
    'request' => $payloadMain
  ]),
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "X-VERIFY: ".$Checksum,
    "accept: application/json"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  // $responseData = json_decode($response, true);
  // $url = $responseData['data']['instrumentResponse']['redirectInfo']['url'];
  // header('location: '.$url);
  echo $response;
}
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method="POST">
        <input type="text" name="amount">
        <input type="submit" value="Pay">
    </form>
</body>
</html>