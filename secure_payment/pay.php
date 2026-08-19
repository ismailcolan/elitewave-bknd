<?php
include('../config.ini.php');
require('config.php');
require('razorpay-php/Razorpay.php');
session_start();
include('function.php');
// $conn = mysqli_connect("localhost", "root", "", "graciousexpress");
// $conn = mysqli_connect("localhost", "staging", "vySzrpsqDRupDHS", "staging");

// Create the Razorpay Order

use Razorpay\Api\Api;

$onlinePay = new Payments;
//Check Payment id to create order id
$check_payment_id = mysqli_query($conn, "select MAX(id) as PID from razorpay_payment");
$get_id = mysqli_fetch_assoc($check_payment_id);
$p_id = $get_id['PID'];
$m_OrderID = "0000" . $p_id;

$api = new Api($keyId, $keySecret);
if (isset($_POST['btn-submit'])) {
// echo "You are in";
// echo "<pre>";
// print_r($_POST);
// echo "</pre>";
    $grn_no = isset($_POST['grn_no']) ? $_POST['grn_no'] : "" ; //multiple_grns
    $m_amount = isset($_POST['m_amount']) ? $_POST['m_amount'] : "" ; //multiple_amt
    $invoice_no = isset($_POST['invoice_no']) ? $_POST['invoice_no'] : ""; //multiple_inv

    $transaction_id = $_POST['transaction_id'];
    $grn_date = $_POST['grn_date'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $amount = $_POST['amount'];
    //$grn_no = $_POST['grn_no'];
    // $invoice_no = $_POST['invoice_no'];
    $client_id = $_POST['client_id'];
    $razorpayPaymentId = "";
    $paymentStatus = "PENDING";
    $created_at = date('Y-m-d h:i:s');
    $updated_at = date('Y-m-d h:i:s');

    //Store in Session

    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['amount'] = $amount;
    $_SESSION['grn_no'] = $grn_no;
    $_SESSION['invoice_no'] = $invoice_no;
    $_SESSION['client_id'] = $client_id;
    $_SESSION['transaction_id'] = $transaction_id;
    $_SESSION['grn_date'] = $grn_date;
    //
    // We create an razorpay order using orders api
    // Docs: https://docs.razorpay.com/docs/orders

    $orderData = [
        'receipt'         => $client_id . "_receipt",
        'amount'          => $amount * 100,  // 2000 rupees in paise 
        'currency'        => 'INR',
        'payment_capture' => 1
    ];

    $razorpayOrder = $api->order->create($orderData);

    $razorpayOrderId = $razorpayOrder['id'];

    $_SESSION['razorpay_order_id'] = $razorpayOrderId;

    $displayAmount = $amount = $orderData['amount'];

    if ($displayCurrency !== 'INR') {
        $url = "https://api.fixer.io/latest?symbols=$displayCurrency&base=INR";
        $exchange = json_decode(file_get_contents($url), true);

        $displayAmount = $exchange['rates'][$displayCurrency] * $amount / 100;
    }

    $data = [
        "key"               => $keyId,
        "amount"            => $amount,
        "name"              => $name,
        "description"       => "This is Company Description",
        "image"             => "https://s29.postimg.org/r6dj1g85z/daft_punk.jpg",
        "prefill"           => [
            "name"              => $name,
            "email"             => $email,
            "contact"           => $phone,
        ],
        "notes"             => [
            "address"           => "Online Payments with Razorpay",
            "merchant_order_id" => $m_OrderID,
        ],
        "theme"             => [
            "color"             => "#F37254"
        ],
        "order_id"          => $razorpayOrderId,
    ];

    if ($displayCurrency !== 'INR') {
        $data['display_currency']  = $displayCurrency;
        $data['display_amount']    = $displayAmount;
    }


    if ($onlinePay->onlinePayment($conn, $transaction_id, $name, $email, $phone, $grn_date, $grn_no, $invoice_no, $client_id, $amount, $m_amount , $razorpayOrderId, $razorpayPaymentId, $paymentStatus, $created_at, $updated_at)) {

        $json = json_encode($data);
    } else {
        $errMsg = "Sorry Something, Went Wrong!";
    }
}
?>
<style>
    #pay_b {
        color: white;
        width: 100px;
        height: 40px;
        background: lightseagreen;
        border: 1px solid white;
        border-radius: 10px;
    }

    #pay_b:hover {
        background: blueviolet;
    }

    input.razorpay-payment-button {
        display: none;
    }

    div#modal-close {
        display: none;

    }

    .close {
        display: none !important;
    }
</style>
<form action="verify.php" method="POST">
    <script src="https://checkout.razorpay.com/v1/checkout.js" 
    data-key="<?php echo $data['key'] ?>" 
    data-amount="<?php echo $data['amount'] ?>" 
    data-currency="INR" 
    data-name="<?php echo $data['name'] ?>" 
    data-image="<?php echo $data['image'] ?>" 
    data-description="<?php echo $data['description'] ?>" 
    data-prefill.name="<?php echo $data['prefill']['name'] ?>" 
    data-prefill.email="<?php echo $data['prefill']['email'] ?>" 
    data-prefill.contact="<?php echo $data['prefill']['contact'] ?>" 
    data-notes.shopping_order_id="123" 
    data-order_id="<?php echo $data['order_id'] ?>" 
    <?php if ($displayCurrency !== 'INR') { ?> 
    data-display_amount="<?php echo $data['display_amount'] ?>" <?php } ?> 
    <?php if ($displayCurrency !== 'INR') { ?> 
    data-display_currency="<?php echo $data['display_currency'] ?>" <?php } ?>>
    </script>
    <input type="submit" value="Cancel" class="razorpay-payment-button">
    <button value="Pay Now" class="razorpay-payment-button " id="pay_b" style="display:none" ;>Pay Now</button>
    <!-- Any extra fields to be submitted with the form but not sent to Razorpay -->
    <input type="hidden" name="shopping_order_id" value="3456">
    <input type="hidden" name="callback_url" value="verify.php">
    <input type="hidden" name="cancel_url" value="verify.php">
</form>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script>
    var currpage = window.location.href;
    var lasturl = sessionStorage.getItem("last_url");
    // console.log(lasturl);
    if (lasturl == null || lasturl.length === 0 || currpage !== lasturl) {
        sessionStorage.setItem("last_url", currpage);
        //alert("New page loaded");
    } else {
    var home_url = sessionStorage.getItem("retry_payment");
        console.log("test");
        window.location.href = home_url;
    }



    //Back Button turn off

    window.history.forward();
    window.onload = function() {
        window.history.forward();
    };

    window.onunload = function() {
        null;
    };
    //End
    $(document).ready(function() {
        $('#pay_b').click();
    })
</script>