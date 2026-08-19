<?php
// echo "Testing";exit;
if (session_id() == '') {
  session_start();
}
if($_SESSION['verify'] == ''){
header('location:https://elitewave360.in/');
}

include_once('user/include/user-function.php');

$output = array();
$query = isset($_GET['data']) ? $_GET['data'] : '';
parse_str($query, $output);
$unserialize = unserialize($output['aParam']);
// echo "<pre>";
// print_r(unserialize($output['aParam']['amount']));
// echo "</pre>";

$transaction_id =  isset($unserialize['transaction_id']) ? implode(',',$unserialize['transaction_id']) : '';
$name =  isset($unserialize['company_name']) ? $unserialize['company_name'] : '';
$email =  isset($unserialize['email']) ? $unserialize['email'] : '';
$phone =  isset($unserialize['phone']) ? $unserialize['phone'] : '';
$grn_date =  isset($unserialize['grn_date']) ?  implode(',',$unserialize['grn_date']) : '';
$amount_count = isset($unserialize['amount']) ? count($unserialize['amount']) : '';
$amount = isset($unserialize['amount']) ? implode(',',$unserialize['amount']) : 0;
$grn_no = isset($unserialize['grn_no']) ? implode(', ',$unserialize['grn_no']) : '';
$invoice_no = isset($unserialize['invoice_no']) ? implode(',',$unserialize['invoice_no']) : '';
$client_iden =  isset($unserialize['client_id']) ? $unserialize['client_id'] : '';

$get_amount = isset($unserialize['amount']) ? $unserialize['amount'] : '';

$total = 0;
foreach($get_amount as $amt){

$total += $amt;
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gracious Express - Payment Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<style>
    section.sect {
        height: 100vh;
    }
    
    .maindiv {
        /* margin-top: 180px; */
        height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .highlight {
        background: #a3bada9c;
        border-color: #0d6efd!important;
    }
	 @media only screen and (max-width: 767px) {
        .col-md-10 {
            min-width: 93%;
        }
    }
</style>
</head>

<body style="background-color: #eee;">
<section class="sect">
        <!-- <div class="container"> -->
        <div class="maindiv ">
            <div class="col-md-10 col-xl-4 col-sm-12">
                <div class="card rounded-3">
                    <div class="card-body mx-1 my-2">
                        <form action="secure_payment/pay.php" method="post">

                        <input type="hidden" name="transaction_id"  class="form-control" value="<?php echo $transaction_id; ?>" />
                        <input type="hidden" name="grn_no" class="form-control" value="<?php echo $grn_no; ?>" />
                        <input type="hidden" name="grn_date" class="form-control" value="<?php echo $grn_date; ?>" />
                        <input type="hidden" name="invoice_no" class="form-control"  value="<?php echo $invoice_no; ?>" />
                        <input type="hidden" name="client_id" class="form-control" value="<?php echo $client_iden; ?>" />
                        <input type="hidden" name="name" class="form-control" value="<?php echo $name; ?>" />
                        <input type="hidden" name="email" class="form-control" value="<?php echo $email; ?>" />
                        <input type="hidden" name="phone" class="form-control" value="<?php echo $phone; ?>" />
                        <input type="hidden" name="m_amount" class="form-control" value="<?php echo $amount; ?>" />
                        <input type="hidden" class="form-control" name="amount" value="<?php echo $total; ?>">

                        <div class="d-flex align-items-center">
                            <div>
                                <i class="fab fa-cc-amazon-pay fa-4x text-black pe-3"></i>
                                
                            </div>
                            <div >
                                <p class="d-flex flex-column mb-0">
                                <img src="http://localhost/graciousexpress/assets/img/logo_old.png" class="img-fluid">
                                </p>
                            </div>
                        </div>

                        <div class="pt-3">
                            <div class="d-flex flex-row pb-3 ">
                                <div class="rounded border  d-flex w-100 p-3 align-items-center target highlight">
                                    <div class="d-flex align-items-center pe-3">
                                        <input class="form-check-input  check" type="radio" name="due_amount" id="due_amount" value="1"  required/>
                                    </div>
                                    <div class="d-flex flex-column " id="total_amount" >
                                        <p class="mb-1 small text-primary">Total amount due</p>
                                        <h6 class="mb-0 text-primary">&#x20B9;<span id="amt"><?php echo $total;?></span></h6>
                                        <div id="due_field">

                                        </div>

                                    </div>
                                </div>
                            </div>
                            <?php if($amount_count > 1){ ?> 
                            <div class="d-flex flex-row pb-3">
                                <div class="rounded border  d-flex w-100 px-3 py-2 align-items-center target">
                                    <div class="d-flex align-items-center pe-3">
                                        <input class="form-check-input check" type="radio" name="due_amount" id="due_amount" value="2" required/>
                                    </div>
                                    <div class="d-flex flex-column py-1" id="typed_amount"  >
                                        <p class="mb-1 small text-primary">Other amount</p>
                                        <div class="d-flex flex-row align-items-center">
                                            <h6 class="mb-0 text-primary pe-1">&#x20B9;</h6>
                                            <!-- <input type="text" class="form-control form-control-sm " id="other_pay" name="amount" style="width: 55px;" value="" required /> -->
                                            <div id="amount_field">

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pb-1">
                            <!-- <a href="#!" class="text-muted">Go back</a> -->
                            <input type="submit" class="btn btn-primary btn-sm" name="btn-submit"  id="btn-submit" value="Pay Now" />
                        </div>
                    </form>
                    </div>
                </div>
            </div>

        </div>
        <!-- </div> -->
    </section>
  
</body>
</html>
<script>
  //   sessionStorage.removeItem("last_url")
  //   const name = sessionStorage.getItem('last_url');
  //   //console.log(name);

  //   //Counter Function
  // function counter(count) {
  //   if (count > 0) {
  //     document.getElementById("myCounter").innerHTML = count;
  //     window.setTimeout(function() {
  //       counter(count - 1)
  //     }, 1000);
  //   } else {
  //    // window.location.href = "http://localhost:8080/GraciousExpress/user/razorpay/pay.php";
  //   }
  // }
  // counter(30);

  //End



//Retry Payment Page 3 Time
  // var visit  = localStorage.getItem('redirect_page_view');
  // var retry  = sessionStorage.getItem('retry_payment');

  // console.log("visit",visit)
  // console.log("retry_payment",retry)

  // if(visit < 3){

  //       visit = Number(visit) + 1;
  //       localStorage.setItem("redirect_page_view", visit);

  //   }else if(visit == 3){

  //     sessionStorage.setItem("retry_payment",'http://localhost:8080/GraciousExpress/user/user-book-consignment.php')
  //     window.location.href = "http://localhost:8080/GraciousExpress/user/booking_list.php";

  //   }else{

  //       visit = 1;
  //       localStorage.setItem("redirect_page_view", 1);
  //   }
    // End Retry Payment Page 3 Time


    
    //Back Button turn off

    // window.history.forward();
    // window.onload = function() {
    //     window.history.forward();
    // };

    // window.onunload = function() {
    //     null;
    // };
    //End

  // var tim = setTimeout(function() {
  //           $('#btn-submit').click();
  //       }, 30000);

  $(document).ready(function() {
            $('.check').on('change', function() {
					$('input[name="' + this.name + '"]').not(this).prop('checked', false);
                    var $this = $(this);
                    $this.closest('.pt-3').find('div.highlight').removeClass('highlight');
                    $this.closest('.target').addClass('highlight');
            
                    var check = $(this).val();
                    alert(check);
                    if(check == 1){
                        var te = $("div#total_amount span#amt").text();
                        $("#amount").removeAttr("required");
                        $("#due_field").html('<input type="text" class="form-control form-control-sm amount" name="amount" id="amount" style="width: 55px;" value="'+te+'" readonly  />')
                        
                        $('.amount').val(te);
                        console.log(te);
                    }else{
                        $("#due_field").html('');      
                        $("#amount_field").html('<input type="text" class="form-control form-control-sm amount" name="amount" id="amount" style="width: 55px;" value="" required />')
                        // var te = '';
                        // $('.amount').val(te);
                    }
				});
        });
</script>