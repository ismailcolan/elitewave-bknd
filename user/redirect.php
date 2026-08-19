<?php
include("../config.ini.php");
if (session_id() == '') {
  session_start();
}
include_once('include/user-function.php');
//include_once('include/function.php');
// $conn = mysqli_connect("localhost", "root", "", "graciousexpress");
$select_user_email = mysqli_query($conn, "select *from users where user_id ='" . $_SESSION['user_id'] . "'");
$get_user_data = mysqli_fetch_assoc($select_user_email);
$user_email = $get_user_data['email'];
// var_dump($user_email);

$select_client_id =  mysqli_query($conn, "select *from client where email ='$user_email'");
$get_client_data = mysqli_fetch_assoc($select_client_id);
$client_id = $get_client_data['client_id'];


$output = array();
$query = isset($_GET['data']) ? $_GET['data'] : '';
parse_str($query, $output);
// echo "<pre>";
// print_r($output);
// echo "</pre>";

$transaction_id =  isset($output['aParam']['transaction_id']) ? $output['aParam']['transaction_id'] : '';
$name =  isset($output['aParam']['company_name']) ? $output['aParam']['company_name'] : '';
$email =  isset($output['aParam']['email']) ? $output['aParam']['email'] : '';
$phone =  isset($output['aParam']['phone']) ? $output['aParam']['phone'] : '';
$amount =  isset($output['aParam']['amount']) ? $output['aParam']['amount'] : '';
$grn_date =  isset($output['aParam']['grn_date']) ? $output['aParam']['grn_date'] : '';
$grn_no =  isset($output['aParam']['grn_no']) ? $output['aParam']['grn_no'] : '';
$invoice_no =  isset($output['aParam']['invoice_no']) ? $output['aParam']['invoice_no'] : '';
$client_iden =  isset($output['aParam']['client_id']) ? $output['aParam']['client_id'] : '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
  <title>Gracious Express - Payment Page</title>
  <?php include("include/title.php"); ?>
  <?php include("include/css_js_forgetpassword.php"); ?>
  <link href="favicon.png" type="image/x-icon" rel="shortcut icon">
  <link href="assets/css/master.css" rel="stylesheet">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
  <link href="stylesheets/datatables.css" media="all" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- book consignment css and js starts here -->
  <link rel="stylesheet" href="assets/css/book-consignment.css">
  <link rel="stylesheet" href="f5/fontawesome.min.css">
  <script src="assets/js/jquery.validate.min.js"></script>
  <script src="assets/js/modernizr.custom.js"></script>

  <style>
    .cin {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      flex-direction: column;
    }

    body {
      min-height: 100vh;
      background-color: #1d2630;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      align-items: flex-start;
    }

    .spinner-box {
      width: 100%;
      /* height: 100px; */
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: transparent;
    }

    /* ALTERNATING ORBITS */

    .circle-border {
      width: 65px;
      height: 65px;
      padding: 3px;
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50%;
      background: rgb(63, 249, 220);
      background: linear-gradient(0deg, rgba(63, 249, 220, 0.1) 33%, rgba(63, 249, 220, 1) 100%);
      animation: spin .8s linear 0s infinite;
    }

    .circle-core {
      width: 100%;
      height: 100%;
      background-color: #1d2630;
      border-radius: 50%;
    }

    .content {
      text-align: center;
    }

    .content h2 {
      font-size: 12px;
      color: white;
    }

    .cin_gra {

      text-align: center;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      row-gap: 26px;
      flex-direction: column;
    }

    /* Custom button */
    .custom-btn {
      width: 119px;
      height: 31px;
      color: #fff;
      border-radius: 3px;
      font-size: 14px;
      padding: 0px 18px;
      font-family: 'Lato', sans-serif;
      font-weight: 500;
      background: transparent;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      display: inline-block;
      box-shadow: inset 2px 2px 2px 0px rgba(255, 255, 255, .5),
        7px 7px 20px 0px rgba(0, 0, 0, .1),
        4px 4px 5px 0px rgba(0, 0, 0, .1);
      background: #148799;

      outline: none;
    }

    .btn-3 {
      background: rgb(0, 172, 238);
      background: linear-gradient(0deg, rgba(0, 172, 238, 1) 0%, rgba(2, 126, 251, 1) 100%);
      width: 130px;
      height: 40px;
      line-height: 42px;
      padding: 0;
      border: none;

    }

    .btn-3 span {
      position: relative;
      display: block;
      width: 100%;
      height: 100%;
    }

    .btn-3:before,
    .btn-3:after {
      position: absolute;
      content: "";
      right: 0;
      top: 0;
      background: rgba(2, 126, 251, 1);
      transition: all 0.3s ease;
    }

    .btn-3:before {
      height: 0%;
      width: 2px;
    }

    .btn-3:after {
      width: 0%;
      height: 2px;
    }

    .btn-3:hover {
      background: transparent;
      box-shadow: none;
    }

    .btn-3:hover:before {
      height: 100%;
    }

    .btn-3:hover:after {
      width: 100%;
    }

    .btn-3 span:hover {
      color: rgba(2, 126, 251, 1);
    }

    .btn-3 span:before,
    .btn-3 span:after {
      position: absolute;
      content: "";
      left: 0;
      bottom: 0;
      background: rgba(2, 126, 251, 1);
      transition: all 0.3s ease;
    }

    .btn-3 span:before {
      width: 2px;
      height: 0%;
    }

    .btn-3 span:after {
      width: 0%;
      height: 2px;
    }

    .btn-3 span:hover:before {
      height: 100%;
    }

    .btn-3 span:hover:after {
      width: 100%;
    }

    /* .btn-9 {
  border: none;
  transition: all 0.3s ease;
  overflow: hidden;
}
.btn-9:hover:after {
  -webkit-transform: scale(2) rotate(180deg);
  transform: scale(2) rotate(180deg);
  box-shadow:  4px 4px 6px 0 rgba(255,255,255,.5),
              -4px -4px 6px 0 rgba(116, 125, 136, .2), 
    inset -4px -4px 6px 0 rgba(255,255,255,.5),
    inset 4px 4px 6px 0 rgba(116, 125, 136, .3);
}
.btn-9:hover {
  background: transparent;
  box-shadow:  4px 4px 6px 0 rgba(255,255,255,.5),
              -4px -4px 6px 0 rgba(116, 125, 136, .2), 
    inset -4px -4px 6px 0 rgba(255,255,255,.5),
    inset 4px 4px 6px 0 rgba(116, 125, 136, .3);
  color: #fff;
} */

    @keyframes spin {
      from {
        transform: rotate(0);
      }

      to {
        transform: rotate(359deg);
      }
    }

    @keyframes spin3D {
      from {
        transform: rotate3d(.5, .5, .5, 360deg);
      }

      to {
        transform: rotate3d(0deg);
      }
    }

    @keyframes configure-clockwise {
      0% {
        transform: rotate(0);
      }

      25% {
        transform: rotate(90deg);
      }

      50% {
        transform: rotate(180deg);
      }

      75% {
        transform: rotate(270deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    @keyframes configure-xclockwise {
      0% {
        transform: rotate(45deg);
      }

      25% {
        transform: rotate(-45deg);
      }

      50% {
        transform: rotate(-135deg);
      }

      75% {
        transform: rotate(-225deg);
      }

      100% {
        transform: rotate(-315deg);
      }
    }

    @keyframes pulse {
      from {
        opacity: 1;
        transform: scale(1);
      }

      to {
        opacity: .25;
        transform: scale(.75);
      }
    }

    .dot {
      margin-top: 9px;
      color: #138798;
      width: 24px;
      height: 6px;
      --d: radial-gradient(farthest-side, currentColor 90%, #0000);
      background: var(--d), var(--d), var(--d);
      background-size: 3px 3px;
      background-repeat: no-repeat;
      animation: m 1s infinite;
      background-position: bottom;
    }

    .dot_re {
      display: flex;
      justify-content: center;
      column-gap: 8px;
    }

    @keyframes m {
      0% {
        background-position: calc(0*100%/3) 100%, calc(1*100%/3) 100%, calc(2*100%/3) 100%, calc(3*100%/3) 100%
      }

      12.5% {
        background-position: calc(0*100%/3) 0, calc(1*100%/3) 100%, calc(2*100%/3) 100%, calc(3*100%/3) 100%
      }

      25% {
        background-position: calc(0*100%/3) 0, calc(1*100%/3) 0, calc(2*100%/3) 100%, calc(3*100%/3) 100%
      }

      37.5% {
        background-position: calc(0*100%/3) 0, calc(1*100%/3) 0, calc(2*100%/3) 0, calc(3*100%/3) 100%
      }

      50% {
        background-position: calc(0*100%/3) 0, calc(1*100%/3) 0, calc(2*100%/3) 0, calc(3*100%/3) 0
      }

      62.5% {
        background-position: calc(0*100%/3) 100%, calc(1*100%/3) 0, calc(2*100%/3) 0, calc(3*100%/3) 0
      }

      75% {
        background-position: calc(0*100%/3) 100%, calc(1*100%/3) 100%, calc(2*100%/3) 0, calc(3*100%/3) 0
      }

      87.5% {
        background-position: calc(0*100%/3) 100%, calc(1*100%/3) 100%, calc(2*100%/3) 100%, calc(3*100%/3) 0
      }

      100% {
        background-position: calc(0*100%/3) 100%, calc(1*100%/3) 100%, calc(2*100%/3) 100%, calc(3*100%/3) 100%
      }
    }
  </style>
</head>

<body>
  <div class="container">

    <div class="cin">
      <div class="cin_gra">
        <div class="spinner-box">
          <div class="circle-border">
            <div class="circle-core"></div>
          </div>
        </div>
        <div class="content">
          <h2 class="dot_re">Redirecting <div class="dot"></div>
          </h2>
          <h2>You are being redirected, Please Wait !</h2>
          <h2>If you have not been redirected in <span id="myCounter"></span> seconds, Please click this button.</h2>
          <form action="razorpay/pay.php" method="post" id="payment_id">
            <div class="form-group">
              <input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>" />
              <input type="hidden" name="grn_no" value="<?php echo $grn_no; ?>" />
              <input type="hidden" name="grn_date" value="<?php echo $grn_date; ?>" />

              <input type="hidden" name="invoice_no" value="<?php echo $invoice_no; ?>" />
              <input type="hidden" name="client_id" value="<?php echo $client_iden; ?>" />
              <input type="hidden" name="name" value="<?php echo $name; ?>" />
              <input type="hidden" name="email" value="<?php echo $email; ?>" />
              <input type="hidden" name="phone" value="<?php echo $phone; ?>" />
              <input type="hidden" name="amount" value="<?php echo $amount; ?>" />
            </div>
            <button type="submit" class="custom-btn btn-3" name="btn-submit" id="btn-submit"><span>Continue</span></button>
          </form>
        </div>
      </div>
    </div>

</body>

</html>
<script>
    sessionStorage.removeItem("last_url")
    const name = sessionStorage.getItem('last_url');
    //console.log(name);

    //Counter Function
  function counter(count) {
    if (count > 0) {
      document.getElementById("myCounter").innerHTML = count;
      window.setTimeout(function() {
        counter(count - 1)
      }, 1000);
    } else {
     // window.location.href = "http://localhost:8080/GraciousExpress/user/razorpay/pay.php";
    }
  }
  counter(30);

  //End

  //Stop Back Button
  window.history.forward();
    window.onload = function() {
        window.history.forward();
    };

    window.onunload = function() {
        null;
    };
  //End

//Retry Payment Page 3 Time
  var visit  = localStorage.getItem('redirect_page_view');
  var retry  = sessionStorage.getItem('retry_payment');

  console.log("visit",visit)
  console.log("retry_payment",retry)

  if(visit < 3){

        visit = Number(visit) + 1;
        localStorage.setItem("redirect_page_view", visit);

    }else if(visit == 3){

      sessionStorage.setItem("retry_payment",'http://localhost/graciousexpress//user/user-book-consignment.php')
      window.location.href = "http://localhost/graciousexpress//user/user/booking_list.php";

    }else{

        visit = 1;
        localStorage.setItem("redirect_page_view", 1);
    }
    // End Retry Payment Page 3 Time


    
    //Back Button turn off

    window.history.forward();
    window.onload = function() {
        window.history.forward();
    };

    window.onunload = function() {
        null;
    };
    //End

  var tim = setTimeout(function() {
            $('#btn-submit').click();
        }, 30000);
</script>