<?php
require_once('vendor/autoload.php');

$client = new \GuzzleHttp\Client();

if (isset($_POST['amount'])) {
    $amount = $_POST['amount'] * 100;
    $merchant_key = "6078cc82-efbb-4816-bb6a-a7b51b48c05b";
    $data = array(
        "merchantTransactionId" => "GRACIOUS344545", "merchantUserId" => "GRACIOUS11234",
        "amount" => $amount,
        "merchantId" => "GRACIOUSONLINE",
        "redirectUrl" => "http://localhost/graciousexpress/test_phonepe/payment_api_test/payment_success.php",
        "redirectMode" => "POST",
        "callbackUrl" => "http://localhost/graciousexpress/test_phonepe/payment_api_test/payment_success.php",
        "paymentInstrument" => array(
            "type" => "PAY_PAGE"
        )
    );
    // Convert the Payload to JSON and encode as Base64 SpayloadMain = base64_encode(json_encode($data));
    $payloadMain = base64_encode(json_encode($data));
    $payload = $payloadMain."/pg/v1/pay".$merchant_key;
    $checksum = hash('sha256', $payload);
    $Checksum = $checksum."###1";
    // echo $payloadMain;exit;
    // echo "<br><br>";
    // echo $Checksum;exit;

    $response = $client->request('POST', 'https://api.phonepe.com/apis/hermes/pg/v1/pay', [
        'body' => '{"request":' . $payloadMain . '}',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-VERIFY' => $Checksum,
            'accept' => 'application/json',
        ],
    ]);

    echo $response->getBody();
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