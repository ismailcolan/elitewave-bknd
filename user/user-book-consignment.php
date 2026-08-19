<?php
include("../config.ini.php");
if (session_id() == '') {
    session_start();
}
unset($_SESSION['msg']); //remove session message
unset($_SESSION['paymentId']); //remove session payment
unset($_SESSION['LastRequest']); //remove booking_list url session
$payment_due = isset($_SESSION['payment_due']) ? $_SESSION['payment_due'] : 0;
require_once("include/user-function.php");

// $conn = mysqli_connect("localhost", "staging", "vySzrpsqDRupDHS", "staging");
$select_user_email = mysqli_query($conn, "select email from users where user_id ='" . $_SESSION['user_id'] . "'");
$get_user_data = mysqli_fetch_assoc($select_user_email);
$user_email = $get_user_data['email'];
// var_dump($user_email);
$clients = "select * from client where email ='$user_email'";
// exit();
$select_client_id =  mysqli_query($conn,$clients);
if(mysqli_num_rows($select_client_id) > 0){
$get_client_data = mysqli_fetch_assoc($select_client_id);
$client_id = $get_client_data['client_id'];
$invoice_status = $get_client_data['invoice_status'];
$invoice_frequency = $get_client_data['invoice_frequency'];
}else{
   $client_id = "-1";
   $invoice_status = "";
   $invoice_frequency = "";
}
$dd2 = "SELECT * FROM `client_outstanding` where client_id ='$client_id'";
$query_outstanding = mysqli_query($conn,$dd2 );
if(mysqli_num_rows($query_outstanding) > 0){
    $outstanding_data = mysqli_fetch_assoc($query_outstanding);
    $payment_due = $outstanding_data['balance'];
}else{
    $payment_due = 0;
}
$_SESSION['payment_due'] = $payment_due;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <title>Gracious Express - Book Consignment</title>
    <?php include("include/css_js_forgetpassword.php"); ?>
    <link href="favicon.png" type="image/x-icon" rel="shortcut icon">
    <link href="assets/css/master.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" />  -->
    <!-- <link href="stylesheets/datatables.css" media="all" rel="stylesheet" type="text/css" /> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- book consignment css and js starts here -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="assets/css/book-consignment.css">
    <link rel="stylesheet" href="f5/css/all.css">
    <!-- book consignment css and js finished here -->
    <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>
    <script src="assets/js/jquery.validate.min.js"></script>
    <style>
.pack_required::after {
    content: "This field is required";
    color: #d9534f;
    position: absolute;
    bottom: 0;
    left: 9px;
    font-size: 13px;
}
        .invoice_exist {
				border: 1px solid #e71717 !important;
			}

			.invoice_valid {

				border: 1px solid #00CB01 !important;
			}

			.invoice_new {
				/* border:1px solid #ff4e4e !important; */
				/* #a2d660 */
				border: 1px solid #8E8D8D !important;
			}
        .vlm:after {
            content: " *";
            color: red;
        }

        #wrapper .form-group {
            margin-bottom: 3px;
        }

        div#wrapper {
            margin-top: 12px;
        }

        td.user_shipp_add {
            display: flex;
            flex-direction: row;
            column-gap: 8px;
        }

        .user_shipp_add label {
            font-size: 16px !important;
            font-weight: bold !important;
            margin-bottom: 0px !important;
        }


        /* #inner1 {
            float: left;
        }

        #inner2 {
            float: left;
            clear: left;
        } */


        .select2-container {

            width: 64% !important;
        }

        .select2-container--default .select2-selection--single {
            height: 45px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: white;
            line-height: 28px;
        }

        .select2-container--default .select2-selection--single {
            background-color: #fff;
            border: 1px solid #aaa;
            border-radius: 4px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            text-align: left;
            border-color: #1259cf6b;
            background-color: #163a85;
            color: white;
            text-indent: 12px;
            padding: 0.5em 0.5em;
            border-radius: 0.1em;
            cursor: pointer;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #fff transparent transparent transparent;
            top: 100%;

        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: #ffffff00 transparent #fff7f7 transparent;
            border-width: 0 4px 5px 4px;
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #2e589b;
            color: white;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: -4px;
        }

        .select2-container--open .select2-dropdown {
            left: 0;
            top: 9px;
        }

        .select2-container--default .select2-results>.select2-results__options {
            max-height: 253px;
            overflow-y: auto;
            font-size: 1.3rem;
        }

        .select2-results {
            max-height: 253px;
            padding: 0 0 0 4px;
            margin: 4px 4px 4px 0;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        }

        #ribbon {
            z-index: 1;
            position: absolute;
            top: 32%;
            right: 4.3%;
            width: 200px;
            display: flex;
            flex-direction: column;
            animation: zoom-in-zoom-out 2s ease-in-out;

        }

        .ribbon-pop {
            background: linear-gradient(270deg, #4f396a 5.25%, #576bb0 96.68%);
            display: flex;
            padding: 10px 40px 10px 20px;
            color: white;
            text-align: center;
            width: 243px;
            flex-wrap: wrap;
            position: relative;


        }

        .ribbon-pop p {
            margin: 0px !important
        }

        .ribbon-pop:before {
            content: "";
            position: absolute;
            width: 5px;
            height: 100%;
            background-size: 15px 10px;
            background-image: linear-gradient(-45deg, #5666a9 25%, transparent 25%, transparent), linear-gradient(-135deg, #5666a9 25%, transparent 25%, transparent), linear-gradient(-45deg, transparent 75%, #5666a9 75%), linear-gradient(-135deg, transparent 75%, #5666a9 75%);

        }

        .ribbon-pop:before {
            left: -4px;
            top: 0;
            -moz-transform: scaleX(-1);
            -webkit-transform: scaleX(-1);
            -o-transform: scaleX(-1);
            transform: scaleX(-1);
            filter: FlipH;


        }

        .ribbon-pop:after {
            height: 0;
            width: 0;
            border-top: 15px solid #381f57;
            border-right: 15px solid transparent;
            bottom: -15px;
            position: absolute;
            content: "";
            right: 0;
        }

        label#width-error,
        label#length-error,
        label#quantity-error,
        label#height-error {
            display: none !important;

        }
  .disabledbutton {
     opacity: 0.4;
    pointer-events: none;
    cursor: no-drop;
}
        @keyframes zoom-in-zoom-out {
            0% {
                transform: scale(0.5, 0.5);
            }

            50% {
                transform: scale(1, 1);
            }

            100% {
                transform: scale(1, 1);
            }
        }

        /* .no_pac:invalid {
       color: red;
      } */
        .package-info {
            width: 100%;
            display: flex;
            float: left;
            border-top: 3px solid #ccc;
            padding-top: 15px;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .user_pack {
            margin-bottom: 22px;
        }

        input#declaration-checkbox {
            margin-top: -11px !important;
        }

        @media (min-width: 360px) and (max-width: 576.98px) {
            .package-info {
                float: none;
            }

            .send-rcv-dtl .volumetric-input-boxes {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                border-bottom: 1px solid gray;
                margin-top: 10px;
            }

            .volumetric-input-boxes .form-control {
                width: 40%;
            }

            #bc_F {
                margin-top: 10px;
            }

            #ribbon {

                position: absolute;
                top: 117%;
                right: 12.3%;
            }
           .package-info {
    width: 100%;
    display: flex;
    /* float: left; */
    border-top: 3px solid #ccc;
    padding-top: 15px;
    flex-direction: column;
    /* flex-wrap: wrap; */
    width: 100%;
    float: left;
    border-top: 3px solid #ccc;
    padding-top: 15px;
}
   #consignee-address table tbody {
    display: flex;
    width: 100%;
    flex-wrap: wrap;
}


#consignee-address table tbody tr {
    width: 100%;
    display: grid;
    grid-template-columns: 2fr 3fr;
}

#consignee-address table tbody tr:last-child {
    display: flex;
    width: 100%;
    flex-direction: column;
}
                                              
        }
       @media (max-width: 320px) {
    .user-book-consignment #reciever-details .btn, .user-book-consignment #supporting-document .btn, .user-book-consignment #declaration .btn, .user-book-consignment #Attachements .btn {
    padding: 8px 3px !important;
    font-size: 10px;
}
.volumetric-input-boxes input{
    width: 36%;
}
.send-rcv-dtl .volumetric-input-boxes {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}
  .package-info {
    width: 100%;
    display: flex;
    /* float: left; */
    border-top: 3px solid #ccc;
    padding-top: 15px;
    flex-direction: column;
    /* flex-wrap: wrap; */
    width: 100%;
    float: left;
    border-top: 3px solid #ccc;
    padding-top: 15px;
}
}
@media (min-width: 320px) and (max-width:575.98px) { 
    #ribbon {
    z-index: 1;
    position: absolute;
    top: 748px;
    right: 4.3%;
    width: 235px;
    display: flex;
    flex-direction: column;
    animation: zoom-in-zoom-out 2s ease-in-out;
}
div#wrapper {
    padding-left: 0;
    padding-right: 0;
}
div#wrapper {
    padding-left: 0;
    padding-right: 0;
}
#inner1 label, #inner2 label, #inner3 label{
    margin-bottom: 2px;
    margin-top: 3px;
}
.text-right.package-info-add-del-btns , .vol-add-more  {
    display: flex;
    flex-direction: row;
justify-content: space-between;

}
#declaration .declaration-group {
    display: flex;
    justify-content: flex-start;
    align-items: flex-start;
}
#declaration .declaration-group input {
    margin-right: 8px;
    margin-top: 3px;
}
   .send-rcv-dtl {
    display: flex;
    flex-direction: column;
}
 input#reslt {
    margin-top: 12px;
}
 #select-sender-dtl .sub-block {
    padding-right: 13px;
    padding-left: 13px;
}
   #payment-info .table > tbody .freight .freight-wgt, #payment-info .table > tbody .freight .freight-rate, #payment-info .table > tbody .freight .freight-amt {
    width: 45%;
} 
 #payment-info .table > tbody > tr > td:first-child {
    text-align: left;
    padding: 12px;
    /* width: 33%; */
    width: 18%;
}
   input#declaration-checkbox {
    margin-top: 0px !important;
}
   .declaration-group p {
       margin: 0 0 0px;
   }
   #payment-info .send-rcv-dtl .table {
    width: 100%;
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
}

#payment-info .send-rcv-dtl .table tbody {
    display: flex;
    width: 100%;
    flex-direction: column;
}
#payment-info .send-rcv-dtl .table tbody tr {
    flex-flow: row;
    display: flex;
    width: 100%;
}
#payment-info .table > tbody > tr:first-child > td:first-child {
    width: 19%!important;
}
   #payment-info .table > tbody > tr > td:first-child {
    text-align: left;
    padding: 12px;

    width: 100%;
}
                                              
}            
 @media (min-width:768px) and (max-width:991.98px){
  .package-info  .form-group label {
    margin-bottom: 5px;
    font-size: 12px;
}
div#inner1 {
    margin-bottom: 45px;
}
div#inner3 {
    margin-top: 84px;
}
#payment-info .send-rcv-dtl .table {
    width: 100%;
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
}


#payment-info .send-rcv-dtl .table tbody {
    display: flex;
    width: 100%;
    flex-direction: column;
}

#payment-info .send-rcv-dtl .table tbody tr {
    flex-flow: row;
    display: flex;
    width: 100%;
}

.table > tbody > tr:first-child > td:first-child {
    width: 19%!important;
}
                                              
 }
   
    </style>
</head>


<body style>
    <div id="ribbon">
        <div class="ribbon-pop">Your Due Payment : <p> &#x20B9; <?php echo $payment_due; ?>.00</p>
        </div>
    </div>

    <div class="user-dashboard" id="user-book-consignment">

        <?php include 'user-db-header.php' ?>

        <div class="user-book-consignment col-sm-12">
            <div class="ds-white-cover ">
                <!-- <h4 class="text-center">Mode Of Consignment</h4> -->
                <div class="parent-block ubc-parent-block">
                    <div class="block send-rcv-dtl" style="display : block">
                        <h4 class="" style="margin-top :0 ; margin-bottom : 25px">Book Consignment</h4>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group row">
                                    <?php
                                    // Get User
                                    $user = get_single_user($conn, $_SESSION['user_id']);
                                    $email = $user['email'];
                                    //Get Client ID and Client City
                                    $client = get_client_id($conn, $email);
                                    $client_id = $client['client_id'] ?  $client['client_id'] : '-1';
                                    //Get City Name
                                    $client_city = get_city_name($conn, $client['city']);
                                    //Get Client Code
                                    $query_code = mysqli_query($conn, "select * from client where client_id='$client_id'");
                                    $r_code = mysqli_fetch_array($query_code);
                                    //Get Grn ID
                                    $query_max = mysqli_query($conn, "select * from transaction_log where client_id='$client_id'");
                                    $r_max = mysqli_fetch_array($query_max);
                                    $id = $r_max['grn_id'] + 1;
                                    $billing_code = $r_code['billing_code'];
                                    $grn_no = $billing_code . sprintf("%05d", $id);

                                    //Get Date
                                    $date = date('d-m-Y');
                                    ?>
                                    <label for="staticEmail" class="col-sm-5 col-form-label">GRN NO : </label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control" id="grn_no" name="grn_no" value="<?php echo $grn_no; ?>" readonly>
                                        <input type="hidden" id="id" value="<?php echo $id; ?>" name="id" class="form-control" />
                                        <input type="hidden" name="get_consignor_id" id="get_consignor_id" value="<?php echo $client_id; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group row">
                                    <label for="staticEmail" class="col-sm-5 col-form-label">ORIGIN : </label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control" id="origins" name="origins" value="<?php echo $client_city; ?>" readonly>
                                        <input type="hidden" class="form-control" id="origin" name="origin" value="<?php echo $client['city']; ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group row">
                                    <label for="staticEmail" class="col-sm-5 col-form-label">GRN DATE : </label>
                                    <div class="col-sm-7">
                                        <input type="text" id="grn_date" name="grn_date" class="form-control" value="<?php echo $date; ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group row">
                                    <label for="staticEmail" class="col-sm-5 col-form-label">DESTINATION : </label>
                                    <div class="col-sm-7">
                                        <select name="destination" id="destination" class="form-control" readonly style="pointer-events: none;">
                                            <option value="">Select Destination</option>
                                            <?php
                                            //  $row['destination'] = '3';
                                            //	if($row['destination']>0){
                                            $city_query1 = "select * from city where status=0 and city_id!='" . $row['origin'] . "' order by city_name asc";
                                            $city_result1 = mysqli_query($conn, $city_query1);
                                            while ($city_row1 = mysqli_fetch_array($city_result1)) {
                                            ?>
                                                <option value="<?php echo $city_row1['city_id']; ?>" <?php if ($city_row1['city_id'] == $row['city']) echo "selected"; ?>><?php echo $city_row1['city_name']; ?></option>
                                            <?php
                                            }
                                            //	}
                                            ?>

                                        </select>
                                        <!-- <input type="text" class="form-control"  id="destination" name="destination" value=""> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    if ($client_id != '-1') {
                    ?>
                        <div class="block" id="select-shipping" style="display : block">
                            <h5>I Prefer Mode Of Shipping Through ...</h5>
                            <div class="row">
                                <div class="col-xs-2 col-sm-2 cust-padding-margin" id="by-air">
                                    <div class="sub-block" id="by-air">
                                        <span class="fa fa-plane custom-icon-size"></span>
                                        <!-- <p> &nbsp; </p> -->
                                        <h6>By Air</h6>
                                        <span id="span" style="display:none;">1</span>
                                        <i class="fa fa-check"></i>
                                    </div>
                                </div>
                                <div class="col-xs-2 col-sm-2 cust-padding-margin" id="by-train">
                                    <div class="sub-block" id="by-train">
                                        <span class="fa fa-subway custom-icon-size"></span>
                                        <!-- <p> &nbsp; </p> -->
                                        <h6>By Train</h6>
                                        <span id="span" style="display:none;">2</span>
                                        <i class="fa fa-check"></i>
                                    </div>
                                </div>
                                <div class="col-xs-2 col-sm-2 cust-padding-margin" id="byRoad">
                                    <div class="sub-block" id="by-road-surface">
                                        <span class="fa fa-road custom-icon-size"></span>
                                        <h6>By Road</h6>
                                        <p class="text-center">Surface</p>
                                        <span id="span" style="display:none;">4</span>
                                        <i class="fa fa-check"></i>
                                    </div>
                                </div>
                                <div class="col-xs-2 col-sm-2 cust-padding-margin" id="express">
                                    <div class="sub-block" id="by-road-express">
                                        <span class="fas fa-truck-moving custom-icon-size"></span>
                                        <h6>By Express</h6>
                                        <span id="span" style="display:none;">3</span>
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                                <div class="col-xs-2 col-sm-2 cust-padding-margin" id="by-local">
                                    <div class="sub-block" id="by-local">
                                        <span class="fas fa-truck custom-icon-size"></span>
                                        <!-- <p class="text-center">&nbsp;</p> -->
                                        <h6>Local Delivery</h6>
                                        <span id="span" style="display:none;">5</span>
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="block" id="select-load">
                            <h5>Select Load Type ...</h5>
                            <div class="row">
                                <div class="col-xs-6 col-sm-6 cust-padding-margin" id="full-truck">
                                    <div class="sub-block" id="by-surface-ftl">
                                        <h6>Full Truck Load</h6>
                                        <span id="span" style="display:none;">7</span>
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-6 cust-padding-margin" id="partial-truck">
                                    <div class="sub-block" id="by-surface-ptl">
                                        <h6>Partial Truck Load</h6>
                                        <span id="span" style="display:none;">8</span>
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="block" id="select-train">
                            <h5>Select Train Type ...</h5>
                            <div class="row">
                                <div class="dropdown">
                                    <select class="sel_train" name="train_name" role="menu" aria-labelledby="menu1" id="sel_train">
                                        <option value="" selected ="true" disabled="disabled">Select Train Type...</option>
                                        <option value="1">Rajdhani Express</option>
                                        <option value="2">Others</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="block" id="select-truck">
                            <h5>Select Truck Type ...</h5>
                            <div class="row">
                                <div class="dropdown">
                                    <select class="dropp" role="menu" aria-labelledby="menu1" id="dropp">
                                        <option value="" selected ="true" disabled="disabled">Select Truck Type...</option>
                                        <option value="">Single Axle Vehicle: 07MT</option>
                                        <option value="">Multi Axle Vehicle : 10MT/14MT/17MT</option>
                                        <option value="">22ft Vehicle : 07MT</option>
                                        <option value="">18ft Vehicle : 06MT</option>
                                        <option value="">Eicher 19 Vehicle : 7MT/8MT/9MT</option>
                                        <option value="">Eicher 17 Vehicle : 5MT</option>
                                        <option value="">Eicher 19 Vechicle:4MT</option>
                                        <!-- <option value="">Eicher 19 Vechicle:4MT</option> -->
                                    </select>
                                </div>
                            </div>
                            <!-- <div class="row">
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type1">
                                    <h6>Single Axle Vehicle: 07MT</h6>
                                    <small class="text-center">32ft L * 8ft W * 9.5ft H = 65CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type2">
                                    <h6>Multi Axle Vehicle : 10MT/14MT/17MT</h6>
                                    <small class="text-center">32ft L * 8ft W * 9.5ft H = 65CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type3">
                                    <h6>22ft Vehicle : 07MT</h6>
                                    <small class="text-center">22ft L * 8ft W * 8ft H = 38CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type4">
                                    <h6>18ft Vehicle : 06MT</h6>
                                    <small class="text-center">18ft L * 8ft W * 8ft H = 31CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type5">
                                    <h6>Eicher 19 Vehicle : 7MT/8MT/9MT</h6>
                                    <small class="text-center">19ft L * 7ft W * 7ft H = 25CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type6">
                                    <h6>Eicher 17 Vehicle : 5MT</h6>
                                    <small class="text-center">17ft L * 6ft W * 7ft H = 19CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" type="type7">
                                    <h6>Eicher 14 Vehicle : 4MT</h6>
                                    <small class="text-center">14ft L * 6ft W * 6.5ft H = 19CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type8">
                                    <h6>Tata 407 Vehicle : 2.5MT</h6>
                                    <small class="text-center">9ft L * 5.5ft W * 5.5ft H = 7.35CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type9">
                                    <h6>Mahendra Bolero Vehicle : 1.5MT</h6>
                                    <small class="text-center">8ft L * 4.8ft W * 4.8ft H = 5CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-6 cust-padding-margin">
                                <div class="sub-block" id="type10">
                                    <h6>Tata Dost Vehicle : 1MT</h6>
                                    <small class="text-center">7ft L * 4.8ft W * 4.8ft H = 4CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 cust-padding-margin">
                                <div class="sub-block" id="type11">
                                    <h6>Tata Ace Vehicle : 850Kgs</h6>
                                    <small class="text-center">7ft L * 4.8ft W * 4.8ft H = 4CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div> -->
                        </div>

                        <div class="block" id="select-payment-mode">
                            <h5>I Wish To Pay By...</h5>
                            <div class="row">
                                <div class="col-xs-3 col-sm-3 cust-padding-margin">
                                    <div class="sub-block" id="to-billed">
                                        <h6>To Billed</h6>
                                        <p class="text-center">By Sender</p>
                                        <span id="span" style="display:none;">2</span>
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                                <div class="col-xs-3 col-sm-3 cust-padding-margin">
                                    <div class="sub-block" id="to-pay">
                                        <h6>To Pay</h6>
                                        <p class="text-center">By Receiver</p>
                                        <span id="span" style="display:none;">1</span>
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                                <div class="col-xs-3 col-sm-3 cust-padding-margin">
                                    <div class="sub-block" id="cod">
                                        <h6>Cash On </h6>
                                        <h6 class="text-center"><strong>Delivery</strong></h6>
                                        <span id="span" style="display:none;">4</span>
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                                <?php if($invoice_status == 1 || $invoice_frequency > 0){ $disable_payatbook = 'pointer-events: none; opacity: 0.4; cursor: no-drop;';}else{ $disable_payatbook = '';} ?>
                                <div class="col-xs-3 col-sm-3 cust-padding-margin" id="paid_parent" style="<?= $disable_payatbook;?>">
                                    <div class="sub-block" id="paid">
                                        <h6>Pay</h6>
                                        <p class="text-center">At Booking</p>
                                        <span id="span" style="display:none;">3</span>
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $consinee = "SELECT * FROM `customer_mapping_lists` where mapping_id IN(select mapping_id from customer_mapping where client='" . $client_id . "')";
                        $result = mysqli_query($conn, $consinee);
                        ?>


                        <div class="block" id="select-sender-dtl">
                            <h5>Consignee / Package Informations.</h5>
                            <div class="row">
                                <div class="col-sm-12 cust-padding-margin">
                                    <div class="sub-block" id="form_reload">
                                        <form id="userbookconsignment" enctype="multipart/form-data">
                                            <input type="hidden" name="truck_type" id="truck_type" value="" />
                                            <input type="hidden" name="train_type" id="train_type" value="" />
                                            <input type="hidden" name="form_name" id="form_name" value="add_user_consignment_form">
                                            <div id="sender-details">
                                                <div class="details-hdr">
                                                    <!-- <span class="far fa-address-card"></span> -->
                                                    <h5>Consignee Details</h5>
                                                </div>
                                                <div class="send-rcv-dtl">
                                                    <div class="form-group">

                                                        <label for="sender-name" class="vlm">Select Consignee</label>
                                                        <select class="form-control" id="sel-consignee" name="sel-consignee" required>
                                                            <option  value="" selected ="true" disabled="disabled">Select Consignee</option>
                                                            <?php
                                                            while ($get_consignee = mysqli_fetch_assoc($result)) { ?>

                                                                <option value="<?php echo $get_consignee['client_id']; ?>"><?php echo get_client_name($conn, $get_consignee['client_id']); ?></option>
                                                            <?php
                                                            }
                                                            ?>
                                                            <!-- <option value ="1">Leather B Unit</option>
                                                        <option value ="2">Forward Shoes - Gurgaon</option>
                                                        <option value ="3">Metro Exports</option>
                                                        <option value ="4">Farida & Groups</option>  //hide-->

                                                        </select>
                                                    </div>
                                                    <div class="" id="consignee-address" style="display : none">
                                                        <table class="table mb-0">
                                                            <tbody>
                                                                <tr>
                                                                    <td>Address 1</td>
                                                                    <td id="address1" name="address1"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Address 2</td>
                                                                    <td id="address2" name="address2"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>State</td>
                                                                    <td id="state" name="state"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>City</td>
                                                                    <td id="city" name="city"></td>
                                                                    <input type="hidden" id="city_id" name="city_id">
                                                                </tr>
                                                                <tr>
                                                                    <td>PinCode</td>
                                                                    <td id="pincode" name="pincode"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>GST</td>
                                                                    <td id="gst_no" name="gst_no"></td>
                                                                </tr>
                                                                <tr class="ship_addrt">
                                                                    <td class="user_shipp_add"> <input type="checkbox" id="ship_adddress" name="user_ship_address" onclick="ship()" value="">
                                                                        <label for="user_ship_address" class=""> Shipping address</label>
                                                                    </td>
                                                                    <td>
                                                                        <div id="shipadd" style="display:none"> <textarea class="form-control ship_area" rows="3" placeholder="Shipping Address" autocomplete="off" spellcheck="false" name="shipping_address" id="shipping_address"></textarea></div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <!-- <div id="userss_shipping" class="row">
                                                        <div class="col-lg-3  U_ship_address">
                                                           
                                                        </div>
                                                        <div class="col-lg-7" id="shipadd" style="display:none">
                                                            <div class="form-group">
                                                              
                                                            </div>
                                                        </div>
                                                    </div> -->
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="reciever-details" class="disabled">
                                                <div class="details-hdr">
                                                    <!-- <span class="far fa-address-card"></span> -->
                                                    <h5 class="vlm">Package Details </h5>
                                                </div>
                                                <div class="send-rcv-dtl ">
                                                    <div id="package-info1" class="package-info">
                                                        <div class="form-group col-sm-3">
                                                        <label for="reciever-name" class="vlm">No Of Packages</label>
                                                        <input type="text"  class="form-control  user_pack package-i" id="package-qty" name="package-qty[]" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off">
                                                       <span></span>
                                                        </div>
                                                        <div class="form-group col-sm-3">
                                                            <label for="reciever-contact-no" class="vlm">Type Of Package</label>
                                                            <select name="package_type[]" id="package_type" class="form-control user_pack package-i" >
                                                                <option value="">Select Package Type</option>
                                                                <?php
                                                                $package_type = mysqli_query($conn, "select * from package where status='0'");
                                                                while ($package_list = mysqli_fetch_assoc($package_type)) {
                                                                ?>
                                                                    <option value="<?php echo $package_list['package_id']; ?>"><?php echo $package_list['package_code']; ?></option>
                                                                <?php
                                                                }
                                                                ?>
                                                            </select>
                                                                 <span></span>
                                                        </div>
                                                        <div class="form-group col-sm-3">
                                                        <label for="reciever-email" class="vlm">Invoice No</label>
                                                        <input  type="text"  class="form-control user_pack package-i" id="invoice" name="invoice[]" autocomplete="off" data-id="1" onchange="party_invoice_details();" onkeydown="party_invoice_details();" onkeyup="party_invoice_details();">
                                                        <span></span>
                                                        </div>
                                                        <div class="form-group col-sm-3">
                                                            <label for="reciever-city">Contents</label>
                                                            <input type="text" class="form-control user_pack" id="contents" name="contents[]" autocomplete="off">
                                                        </div>
                                                        <div class="form-group col-sm-3">
                                                            <label for="reciever-address" class="vlm">Quantity </label>
                                                            <input type="text"  class="form-control user_pack package-i" id="qty" name="qty[]" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                       <span></span>
                                                            </div>
                                                        <div class="form-group col-sm-3">
                                                            <label for="reciever-area">Gross Wt.(Kgs)</label>
                                                            <input type="text" class="form-control user_pack" id="gross_kg" name="gross_kg[]" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                        </div>
                                                        <div class="form-group col-sm-3 charged">
                                                            <label for="reciever-area" class="vlm">Charged Wt.(Kgs)</label>
                                                            <input type="text" class="form-control charged-weight user_pack package-i" id="charged_kg" name="charged_kg[]" onchange="cumulative_charge_wight();" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                            <input type="hidden" class="form-control c_weight" id="c_weight" name="c_weight[]" />
                                                             <span></span>
                                                        </div>

                                                        <!-- <div class="form-group col-sm-3">
                                                        <label>&nbsp;</label>
                                                        <a class="btn btn-danger" onclick="DelDiv(this)"><span class="fa fa-trash" aria-hidden="true"></span></a>
                                                    </div> -->
                                                    </div>
                                                    <div class="col-sm-12 text-right package-info-add-del-btns">
                                                        <a class="btn btn-primary" onclick="CloneDiv()"> <span class="fa fa-plus" aria-hidden="true"></span> Add Row</a>
                                                        <a class="btn btn-danger disabled" onclick="DelDiv()"> <span class="fa fa-trash" aria-hidden="true"></span> Del Row</a>
                                                    </div>

                                                    <!-- <div class="form-group col-sm-6" style="display : flex; align-items : center; flex : 12">
                                                        <label style="flex : 8" for="reciever-area" class="vlm">Declared Value </label>
                                                        <input style="flex : 6 ; margin-bottom : 0;" type="text" name="declared_val" id="declared_val" class="form-control declared-value" onchange="fov_calc();" required onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" >
                                                    </div> -->

                                                    <!-- <div id="wrapper">
                                                    <div id="inner1">
                                                        <div class="form-group  row col-sm-12" style="display : flex; align-items : center; bottom:5px;">
                                                            <label style="" for="reciever-area" class="col-sm-6 col-form-label vlm">Declared Value </label>
                                                            <div class="col-sm-6">
                                                                <input style=" margin-bottom : 0;" type="text" name="declared_val" id="declared_val" class="form-control declared-value" onchange="fov_calc();" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="inner2">
                                                        <div class="form-group row  col-sm-12" style="display : flex; align-items : center; ">
                                                            <label style="" for="reciever-area" class=" col-sm-6 col-form-label vlm">E-Way Number </label>
                                                            <div class="col-sm-6">
                                                                <input style=" margin-bottom : 0;" type="text" name="eway_number" id="eway_number" class="form-control eway_number" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> -->
                                                    <div id="wrapper" class="col-sm-12 offset-lg-6 col-lg-12 col-md-12">
                                                        <div id="inner1">
                                                            <label for="reciever-area" class="col-lg-6   col-md-6 col-sm-5">Declared Value </label>
                                                            <div class="form-group col-sm-7  col-md-6 col-lg-6">
                                                                <input style=" margin-bottom : 0;" type="text" name="declared_val" id="declared_val" class="form-control declared-value col-sm-4" onchange="fov_calc();" autocomplete="off" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 48 && event.charCode <= 57 ||  event.charCode === 46 && this.value.split('.').length === 1" onpaste="return false;">
                                                            </div>
                                                        </div>
                                                        <div id="inner2">
                                                            <label for="reciever-area" class="col-lg-6   col-md-6 col-sm-5">E-Way Number </label>
                                                            <div class="form-group col-sm-7  col-md-6 col-lg-6">
                                                                <input style=" margin-bottom : 0;" type="text" name="eway_number" id="eway_number" class="form-control eway_number col-sm-4" autocomplete="off" onkeypress="allowAlphaNumericSpace(event)">
                                                            </div>
                                                        </div>
                                                        <div id="inner3">
                                                            <label for="reciever-area" class="col-lg-6   col-md-6 col-sm-5">E-Way Expiry Date </label>
                                                            <div class="form-group col-sm-7  col-md-6 col-lg-6">
                                                                <input style=" margin-bottom : 0;" type="date" name="eway_expiryDate" id="eway_expiryDate" class="form-control col-sm-4 eway_expiry_date" onchange="getd_date();">
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                            </div>

                                            <div id="supporting-document" class="disabled">
                                                <div class="details-hdr v">
                                                    <!-- <span class="far fa-address-card"></span> -->
                                                    <input class="form-check-input" type="checkbox" value="" id="volum-info">
                                                    <h5>Volumetric Information If Any (in cms)</h5>
                                                </div>
                                                <div class="send-rcv-dtl disabled">
                                                    <div id="volumetric-info1" class="volumetric-info">
                                                        <div class="volumetric-input-boxes">
                                                            <input type="text" placeholder="length" class="form-control length" id="length" name="length[]" onchange="calculation();" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off"><span>X</span>
                                                            <input type="text" placeholder="width" class="form-control width " id="width" name="width[]" onchange="calculation();" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off"><span>X</span>
                                                            <input type="text" placeholder="height" class="form-control height" id="height" name="height[]" onchange="calculation();" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off"> <span>X</span>
                                                            <input type="text" placeholder="Qty" class="form-control quantity" id="quantity" name="quantity[]" onchange="calculation();" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off"> <span>=</span>
                                                            <input type="text" placeholder="Feet/Kgs" class="form-control weight" id="weight" name="weight[]" onchange="calculation();" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" readonly>
                                                            <input type="hidden" class="form-control volume_weight" id="volume_weight" name="volume_weight[]" readonly />
                                                        </div>

                                                    </div>
                                                    <input type="text" id="reslt" class="form-control reslt" name="reslt" style="width:18%;float: right;" readonly>
                                                    <div class="col-sm-12 text-right vol-add-more" id="bc_F">
                                                        <a class="btn btn-primary" onclick="CloneVolumDiv()"> <span class="fa fa-plus" aria-hidden="true"></span> Add More</a>
                                                        <a class="btn btn-danger disabled" onclick="DelVolumDiv()"> <span class="fa fa-trash" aria-hidden="true"></span> Del Row</a>
                                                    </div>
                                                </div>

                                            </div>


                                            <div id="Attachements" class="disabled">
                                                <div class="details-hdr">
                                                    <h5>Attachements</h5>
                                                </div>
                                                <div class="send-rcv-dtl ">
                                                    <div id="image-uploader1" class="image-uploader col-sm-6">
                                                        <div class="box">
                                                            <div class="avatar-upload">
                                                                <div class="avatar-edit">
                                                                    <!-- <input type='file' id="imageUpload" class="imageUpload" accept=".png, .jpg, .jpeg" /> -->
                                                                    <input type='file' class="imageUpload" name="file_receipt[]" id="file_receipt" />
                                                                    <label onclick="DelAttaDiv(this)" id="1" class="hide" for="imageUpload"></label>
                                                                </div>
                                                                <div class="avatar-preview">
                                                                    <div id="imagePreview" class="imagePreview" style="background-image: url('images/doc.png');">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <div class="col-sm-12 text-right">
                                                        <a class="btn btn-primary" onclick="CloneAttaDiv()"> <span class="fa fa-plus" aria-hidden="true"></span> Add More</a>
                                                        <!-- <a class="btn btn-danger disabled" onclick="DelAttaDiv()"> <span class="fa fa-trash" aria-hidden="true"></span> Del Row</a> -->
                                                    </div>
                                                    <!-- <div class="col-sm-12 text-center" style="margin-top : 10px">
                                                        <a class="btn btn-primary" onclick="upload()">Upload</a>
                                                    </div> -->
                                                </div>

                                            </div>

                                            <div id="payment-info" class="disabled">
                                                <div class="details-hdr">
                                                    <h5>Payment Information</h5>
                                                </div>
                                                <div class="send-rcv-dtl ">

                                                    <table class="table mb-0">
                                                        <thead>
                                                            <th>Particulars</th>
                                                            <th class="text-center">Amount</th>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>Freight</td>
                                                                <td class="freight">
                                                                    <div class="freight-wgt"><input type="number" class="form-control mobile-verify pass text-right" name="weight1" id="weight1" placeholder="Weight" onchange="ss()" readonly value="0"></div>
                                                                    <div class="freight-span"><span>X</span></div>
                                                                    <div class="freight-rate"><input type="number" class="form-control mobile-verify pass text-right" name="rate" id="rate" placeholder="Rate" onkeyup="ss()" readonly value="0"></div>
                                                                    <div class="freight-span"><span>=</span></div>
                                                                    <div class="freight-amt"><input type="number" class="form-control text-right" name="amount" id="amount" placeholder="Amount" onkeyup="sum_payment();" readonly value="0.00"></div>
                                                                </td>

                                                            </tr>
                                                            <tr>
                                                                <td>Loading / Unloading Charges</td>
                                                                <td class="payment-info-cust-inp"><input type="number" class="form-control text-right" name="loading_unload_chrg" id="loading_unload_chrg" onkeyup="sum_payment();" readonly value="0.00"></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Crane / Fork Lift Charges</td>
                                                                <td class="payment-info-cust-inp"><input type="number" class="form-control text-right" name="crane_forklift_chrg" id="crane_forklift_chrg" onkeyup="sum_payment();" readonly value="0.00"></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Doc Charges</td>
                                                                <td class="payment-info-cust-inp"><input type="number" class="form-control text-right" name="doc_charges" id="doc_charges" onkeyup="sum_payment();" readonly value="0.00"></td>
                                                            </tr>
                                                            <tr>
                                                                <td>FOV Charges</td>
                                                                <td class="payment-info-cust-inp"><input type="number" class="form-control fov_charges text-right" name="fov_charges" id="fov_charges" readonly onkeyup="sum_payment();" readonly value="0.00"></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Labour Charges</td>
                                                                <td class="payment-info-cust-inp"><input type="number" class="form-control text-right" name="labour-charges" id="labour-charges" onkeyup="sum_payment();" readonly value="0.00"> </td>
                                                            </tr>
                                                            <tr id="rajdhani_ex" style="display: none;">
                                                                <td>Rajdhani Charges</td>
                                                                <td class="payment-info-cust-inp"><input type="number" class="form-control text-right" name="rajdhani-express-charges" id="rajdhani-express-charges" onkeyup="sum_payment();" value="0.00">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Other</td>
                                                                <td class="payment-info-cust-inp"><input type="number" class="form-control text-right" name="other-charges" id="other-charges" onkeyup="sum_payment();" readonly value="0.00"></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="chng_label">G.S.T (18 %)</td>
                                                                <td class="payment-info-cust-inp"><input type="number" class="form-control text-right" name="gst" id="gst" onkeyup="sum_payment();" readonly value="0.00"></td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Total</strong></td>
                                                                <td class="payment-info-cust-inp">
                                                                    <input type="number" class="form-control text-right" name="total_payment" id="total_payment" readonly onchange="get_total();" value="0.00">
                                                                    <input type="hidden" class="form-control text-right" name="total_payment_in_words" id="total_payment_in_words">
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div id="declaration" class="disabled">
                                                <div class="details-hdr">
                                                    <h5>Declaration</h5>
                                                </div>
                                                <div class="send-rcv-dtl ">
                                                    <div class="declaration-group">
                                                        <input class="form-check-input" type="checkbox" name="declaration" value="" id="declaration-checkbox">
                                                        <p>I hereby accept to book this consignment with Gracious Express </p>
                                                    </div>
                                                    <div class="col-sm-12 text-center submit-btn disabled">
                                                        <input type="submit" class="btn btn-primary" id="save" name="" value="Submit">
                                                        <span id="wait" style="display:none"></span>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    } else {
                    ?>
                        <p class="text-center text-danger">You are Restricted to Book Consignment! Contact Gracious Team</p>

                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script>
        var wow = new WOW({
            boxClass: 'wow', // animated element css class (default is wow)
            animateClass: 'animated', // animation css class (default is animated)
            offset: 0, // distance to the element when triggering the animation (default is 0)
            mobile: true, // trigger animations on mobile devices (default is true)
            live: true, // act on asynchronously loaded content (default is true)
            callback: function(box) {
                // the callback is fired every time an animation is started
                // the argument that is passed in is the DOM node being animated
            },
            scrollContainer: null, // optional scroll container selector, otherwise use window,
            resetAnimation: true, // reset animation on end (default is true)
        });
        wow.init();
    </script>
    <script>
        //   document.querySelectorAll('.mobile-verify.pass').forEach(el =>
        // el.onkeyup = e => {
        //   if (e.target.value) {
        //     el.nextElementSibling.focus()
        //   }
        // });
        $('.mobile-verify pass').focus();
    </script>
    <script type="text/javascript">
        $(document).on('click', 'div#by-air,div#by-train,div#by-road-surface,div#by-surface-ftl,div#by-surface-ptl,div#by-road-express,#by-local', function() {
            // alert("test");
            $("#form_reload").load(location.href + " #form_reload");


        })

        // CLICK DIV TRAIN EMPTY DROPDWON TRAIN TYPE
        $('#by-train').click(function() {
            $('#sel_train').val('');
            $("#select2-sel_train-container").empty().append($("<option/>")
                .val("0")
                .text("Select Train Type..."))
            // .val("20") //select option of select2
            // .trigger("change"); //apply to select2

        });

        // CLICK by-surface-ftl div EMPTY DROPDWON truck TYPE
        $('#by-surface-ftl').click(function() {
            $('#select2-dropp-container').empty().append($("<option/>").val("0").text("Select Truck Type..."));

        })

        function getd_date() {
            alert("adata");

            console.log("d", eway_expiry_date);
        }

        function calculation() {
            var arr = [];
            var totalprice = 0;
            $.each($(".volumetric-info .volumetric-input-boxes"), function(index, element) {
                element = $(element);
                var lengthd = parseInt(element.find('.length').val());

                var width = parseInt(element.find('.width').val());

                var height = parseInt(element.find('.height').val());
                var quantity = parseInt(element.find('.quantity').val());



                var weight1 = lengthd * width * height;
                var weight2 = lengthd * width * height * quantity;
                var quant = parseInt(element.find('.weight').val());

                totalprice += Number(quant);
                element.find('.volume_weight').val(totalprice);

                // alert(totalprice);

                var de = 1000000;

                var divide = parseInt(weight2) / parseInt(de);

                //convert to feet

                var feet = divide / 2;


                //convert cms to kgs 
                var cms = parseInt(lengthd) * parseInt(width) * parseInt(height) / 28000;

                //var cms = sum1 / 28000; 

                var cms_to_6times = cms * 6;

                //convert air to kgs 

                var air_kgs = parseInt(lengthd) * parseInt(width) * parseInt(height) / 5000;

                //var res_air_kgs = air_kgs * parseInt(toplam3) ;

                //var result1 = cms_to_6times *  parseInt(toplam3);


                //* Check Surface or Other Transport Mode    
                if ($("div#by-road-surface").hasClass('active')) {

                    if ($("div#by-surface-ftl").hasClass('active')) {
                        // var data = $('#by-surface-ftl #span').text();
                        var result = divide / 2; // CBM to Feet
                        console.log("FTL: " + result)
                        if (result > 10) {
                            result;

                        } else {
                            result = 10;
                        }

                    } else if ($("div#by-surface-ptl").hasClass('active')) {
                        $('.details-hdr h5').addClass('vlm');
                        // var data = $('#by-surface-ptl #span').text();

                        var result = divide / 2; // CBM to Feet
                        console.log("PTL: " + result)
                        if (result != '') {
                            if (result > 10) {
                                result;

                            } else {
                                result = 10;
                            }
                        }

                        // if(! isNaN(result)) {

                        //     console.log(result);
                        //     $(".volume_weight").val(result.toFixed(0));
                        //     $(".weight").val(result.toFixed(0));
                        //     // document.getElementById('weight').value = result;
                        // }

                        //console.log("yes PTL");
                    }

                } else if ($("div#by-air").hasClass('active')) {
                    //var result = air_kgs * parseInt(toplam3);
                    var result = air_kgs * Number(element.find('.quantity').val());
                    //console.log("air"+ result);

                } else {

                    var result = cms_to_6times * Number(element.find('.quantity').val());
                    console.log("Train Express Local Delivery" + result);
                    $('.charged').show();

                }


                console.log("Result :" + result.toFixed(0));


                if (!isNaN(result)) {
                    // console.log("total_wei",result);
                    arr.push(result);
                    element.find('.weight').val(result.toFixed(0));
                    get_all_total(arr);

                }

            });



        }

        function get_all_total(array_data) {

            var total_weight = 0;

            for (let i = 0; i < array_data.length; i++) {
                total_weight += array_data[i];
            }
            $(".volume_weight").val(total_weight.toFixed(0));
            $('#reslt').val(total_weight.toFixed(0));
            setTimeout(() => {
                ss();
            }, 5000)

        }
        //* Get Charged Weight
        function cumulative_charge_wight() {

            var titles = $('input[name^=charged_kg]').map(function(idx, elem) {
                return $(elem).val();
            }).get();
            var res = titles.map(function(x) {
                return parseInt(x, 10);
            });
            var total = 0;

            for (let i = 0; i < res.length; i++) {
                if (isNaN(res[i])) {
                    total = total + 0;

                } else {

                    total = total + res[i];
                }
            }
            console.log(total);


            if (!isNaN(total)) {
                console.log('charged_weight:' + total);
                $('.c_weight').val(total);
                //  $('#weight1').val(charge_add); 
                //  $('#rate').focus();
                //console.log('charged_weight:'+charge_add);

            }
            ss();


        }

        function ship() {
            // alert("f");
            $('#ship_adddress').change(function() {
                if ($(this).is(':checked')) {
                    $('div#shipadd').show();
                    // alert('d');
                } else {
                    $('div#shipadd').hide();
                }
            })
        }



        function fov_calc() {
            var total = 0;
            var fov = 0.2;
            $(".declared-value").each(function() {
                total = total + parseInt($(this).val());


            })
            fov_chrge = (fov / 100) * total;
            if (!isNaN(fov_chrge)) {
                $(".fov_charges").val(addZeroes(fov_chrge.toFixed(2)));

                ss();
            }
            // $('#rate').focus();
        }



        function ss() {

            //var charg_w = $('.charged-weight').val();
            // alert("cal");

            var vol_weight = $('.volume_weight').val();
            var charg_w = $('.c_weight').val();

            console.log('first volume:' + vol_weight);
            console.log('first weight:' + charg_w);


            //* Check Both Charged Weight and Volumetric
            if (charg_w != '' && vol_weight != '') {
                console.log('we are in: ' + charg_w + "and " + vol_weight);
                if (parseInt(charg_w) > parseInt(vol_weight)) {
                    // charg_w = Number.NaN;
                    if (!isNaN(charg_w)) {

                        console.log('charged_weight:' + charg_w);

                        //let wei = charg_w;

                        var ra = $("#rate").val();

                        var res = charg_w * parseInt(ra);

                        $('#weight1').val(charg_w);



                        // $('#rate').focus();
                    }

                } else {
                    if (!isNaN(vol_weight)) {

                        console.log('charged_weight:' + vol_weight);

                        $('#weight1').val(vol_weight);

                        var ra = $("#rate").val();

                        var res = vol_weight * parseInt(ra);

                        // let wei = vol_weight;
                        // $('#rate').focus();

                    }
                }

            } else if (charg_w != '' && vol_weight == '') {
                if (!isNaN(charg_w)) {

                    console.log('charged_weight_1:' + charg_w);

                    $('#weight1').val(charg_w);

                    var ra = $("#rate").val();

                    var res = charg_w * parseInt(ra);

                }

            } else if (vol_weight != '' && charg_w == '') {
                if (!isNaN(vol_weight)) {

                    console.log('charged_vol_1:' + vol_weight);

                    $('#weight1').val(vol_weight);

                    var ra = $("#rate").val();

                    var res = vol_weight * parseInt(ra);

                }

            }

            // var we = $('#weight1').val(s);

            // var ra = $("#rate").val();

            // var res = wei * parseInt(ra);

            if (!isNaN(res)) {
                // console.log(res);
                $("#amount").val(addZeroes(res));
                //alert(res);
                $('#rate').focus();

                sum_payment();


            }
        }

        function sum_payment() {

            var f = $('#amount').val();
            console.log(addZeroes('20'));
            var l = $('#loading_unload_chrg').val();
            var cr = $('#crane_forklift_chrg').val();
            var dc = $('#doc_charges').val();
            var lc = $('#labour-charges').val();
            var oc = $('#other-charges').val();
            var r_ch = $('#rajdhani-express-charges').val() ? $('#rajdhani-express-charges').val() : 0;

            var fov = $('#fov_charges').val();



            if (fov != '')
                var totals = parseFloat(f) + parseFloat(l) + parseFloat(cr) + parseFloat(dc) + parseFloat(lc) + parseFloat(oc) + parseFloat(r_ch) + parseFloat(fov);
            else
                var totals = parseFloat(f) + parseFloat(l) + parseFloat(cr) + parseFloat(dc) + parseFloat(lc) + parseFloat(oc) + parseFloat(r_ch);



            //* GST and GTA
            if ($("div#by-road-surface").hasClass('active')) {
                var percent = 12;

            } else if ($("div#by-local").hasClass('active')) {
                var percent = 12;
            } else {
                var percent = 18;
            }
            // var percent = 12;
            var gst = (percent / 100) * totals;

            var gst1 = $("#gst").val(addZeroes(gst.toFixed(2)));
            console.log(gst)
            //addZeroes(totals_pay.toFixed(0))

            var totals_pay = parseFloat(gst) + parseFloat(totals);


            if (!isNaN(totals_pay)) {
                console.log(totals_pay);
                $("#total_payment").val(addZeroes(totals_pay.toFixed(0)));
                get_total();
                //console.log(addZeroes(totals_pay));
            }
        }

        function addZeroes(num) {
            var num = Number(num);
            if (String(num).split(".").length < 2 || String(num).split(".")[1].length <= 2) {
                num = num.toFixed(2);
            }
            return num;
        }

        //Get Amount in Words
        function get_total() {
            let sum = $('#total_payment').val();
            //alert(sum);
            $.ajax({
                url: '../web/fetch_details.php',
                type: "post",
                data: {
                    cmd: "get_amount_words",
                    val: sum
                },
                success: function(result) {
                    console.log(result);
                    $('#total_payment_in_words').val(result);
                },
                error: function(jqxhr) {
                    //alert(jqxhr.responseText);
                }
            });
        }

        //End Words

        //Party Invoice Check
        function party_invoice_details() {
            var cmd = "check_consginor_invoice_no";
            var get_consignor_id = $("#get_consignor_id").val();
            if (get_consignor_id != "" && get_consignor_id != null) {
                var all_party_invoice = $('input[name^=invoice]').map(function(idx, elem) {
                    return $(elem).val();
                }).get();
                $.ajax({
                    url: '../web/fetch_details.php',
                    type: "GET",
                    dataType: "JSON",
                    data: {
                        conr_id: get_consignor_id,
                        all_party_invoice: all_party_invoice,
                        cmd: cmd,
                    },
                    success: function(result_data) {
                        console.log(result_data);
                        if (result_data) {
                            $.each(result_data, function(index, value) {
                                var adddd = index + 1;
                                if (value == "EMPTY") {
                                    $('input[data-id=' + adddd + ']').removeClass("invoice_exist invoice_valid").addClass("invoice_new");;
                                } else if (value == "NO") {
                                    $('input[data-id=' + adddd + ']').removeClass("invoice_exist invoice_new").addClass("invoice_valid");
                                } else {
                                    $('input[data-id=' + adddd + ']').removeClass("invoice_valid invoice_new").addClass("invoice_exist");
                                }
                            });
                        } else {
                            console.log("no data found");
                        }
                    },
                    error: function(jqxhr) {
                        alert(jqxhr.responseText);
                    }
                });
            } else {
                alert("No Consignor Found");
            }

        }
        //End
        // $('#eway_expiryDate').datepicker({
        // 		startDate: date,
        // 		format: "dd-mm-yyyy",
        // 		autoclose: true
        // 	});


        $(document).ready(function(e) {
            //Select2
            $('.dropp').select2();

            //Getting Selected values

            $(document).on('change', '#dropp', function() {
                //alert("change");
                $('#select-payment-mode .cust-padding-margin .sub-block').removeClass('active');
                var sel_ids = $('#dropp :selected').text();
                //alert(sel_ids.length);

                $('#truck_type').val(sel_ids);
                $('#select-payment-mode').addClass('show');
                $('html, body').animate({
                    scrollTop: $("#select-payment-mode").offset().top - 200,
                }, 1000);

            });

            //Get Train Type Values 
            $('.sel_train').select2();
            $(document).on('change', '#sel_train', function() {
                //alert("change");
                // $('#select-payment-mode .cust-padding-margin .sub-block').removeClass('active');
                var train_type = $('#sel_train :selected').val();
                if (train_type == 1) {
                    $("#rajdhani_ex").show();
                } else {
                    $("#rajdhani_ex").hide();
                }
                //alert(train_type);

                $('#train_type').val(train_type);
                $('#select-payment-mode').addClass('show');
                $('html, body').animate({
                    scrollTop: $("#select-payment-mode").offset().top - 200,
                }, 1000);


            });
            //Get Consingee
            $(document).on('change', '#sel-consignee', function() {
                var sel_id = $(this).val();
                //alert( sel_id);

                $.ajax({
                    url: "../fetch-details.php",
                    type: "GET",
                    dataType: "JSON",
                    data: {
                        cmd: "get_mapped_client_details",
                        sel_id: sel_id
                    },
                    async: false,
                    success: function(data) {
                        console.log(data);
                        $("#consignee-address").show();
                        $('#destination').html(data.city_drop);
                        $("#address1").text(data.address1);
                        $("#address2").text(data.address2);
                        $("#city").text(data.city);
                        $("#city_id").val(data.city_id);
                        $("#state").text(data.state);
                        $("#pincode").text(data.pincode);
                        $("#gst_no").text(data.gst_no);
                    }

                });
                var client = <?php echo $client_id; ?>;
                var destination = $("#city_id").val();
                if ($("div#by-air").hasClass('active')) {
                    var data = $('#by-air #span').text();

                } else if ($("div#by-train").hasClass('active')) {
                    var data = $('#by-train #span').text();

                } else if ($("div#by-road-surface").hasClass('active')) {
                    if ($("div#by-surface-ftl").hasClass('active')) {
                        var data = $('#by-surface-ftl #span').text();
                    } else if ($("div#by-surface-ptl").hasClass('active')) {
                        var data = $('#by-surface-ptl #span').text();

                    } else {
                        var data = $('#by-road-surface #span').text();
                    }
                } else if ($("div#by-road-express").hasClass('active')) {
                    var data = $('#by-road-express #span').text();

                } else {
                    var data = $('#by-local #span').text();
                }
                //var data = $("div#by-air").hasClass('active')
                //alert(des);
                //alert(air);
                $.ajax({
                    url: "../fetch-details.php",
                    type: "GET",
                    data: {
                        cmd: "get_pay_info",
                        data: data,
                        client: client,
                        destination: destination
                    },
                    dataType: "JSON",
                    async: false,
                    success: function(datas) {
                        console.log("rate", datas.rates);
                        $("#rate").val(addZeroes(datas.rates));
                        $("#loading_unload_chrg").val(addZeroes(datas.loading_unloading_charges));
                        $("#crane_forklift_chrg").val(addZeroes(datas.crane_fork_charges));
                        $("#doc_charges").val(addZeroes(datas.doc_charges));
                        $("#other-charges").val(addZeroes(datas.other_charges));
                        $("#labour-charges").val(addZeroes(datas.labour_charges));
                        restrict_fov(datas.rates);
                    }
                });

            });


            //Restrict FOV Charges
            function restrict_fov(rates) {
                $("#declared_val").attr('readonly', false);
            }

            //Check if file exist
            var img_avail;
            //Check Files Validation
            function filesExistCheck() {
                var file_ids = document.getElementsByName('file_receipt[]');
                console.log("filesname", file_ids[0].value);
                for (var i = 0; i < file_ids.length; i++) {
                    if (file_ids[i].value != "") {
                        //console.log("Files Available");
                        img_avail = true;
                        continue;
                    }
                    img_avail = false;
                    break;
                }
            }

            //End

            //Hide Payments Field

            $(document).on('click', function(e) {
                if ($("div#by-surface-ftl").hasClass('active')) {
                    $("#payment-info").hide();
                 $("#paid_parent").addClass("disabledbutton");
                 document.getElementById("disabledbutton").style.pointerEvents = "none";
                    // var controlsEnabled = true;
                    // console.log(controlsEnabled);
                    // $("#paid").on('click', function() {
                    //     // controlsEnabled = !controlsEnabled;
                    //     return !controlsEnabled;
                    //     console.log(controlsEnabled);
                    // })
                    // $("#paid").css('cursor', 'none');
                } else if ($("div#by-surface-ptl").hasClass('active')) {
                    // alert("RE");
                 $("#paid_parent").removeClass("disabledbutton");
                    $('.v h5').addClass('vlm');
                    $(".volumetric-info .length").prop('required', true);
                    $(".volumetric-info .width").prop('required', true);
                    $(".volumetric-info .height").prop('required', true);
                    $(".volumetric-info .quantity").prop('required', true);

                    $('.chng_label').text('G.S.T (12 %)');
                    $('.charged').hide();

                    $('#weight').attr('placeholder',
                        'Feet');

//                     $("#paid").css('cursor', 'pointer');

//                     var controlsEnabled = false;
//                     console.log(controlsEnabled);
//                     $("#paid").on('click', function() {
//                         console.log("click", !controlsEnabled)
//                         // controlsEnabled = !controlsEnabled;
//                         if ($(this).hasClass('sub-block')) {
//                             $(this).addClass('active');
//                             $('#select-sender-dtl').addClass('show');
//                         }
//                         //return !controlsEnabled;

//                     })
                } else if ($("div#by-local").hasClass('active')) {
                    $('.chng_label').text('G.S.T (12 %)')
                     $("#paid_parent").removeClass("disabledbutton");
                } else {
                    $("#payment-info").show();
                 $("#paid_parent").removeClass("disabledbutton");
                }

            });



            //Fetch Payments1
            // $("div#by-train").click(function(){
            //     var data = $('#by-train #span').text();
            //    // alert(data);
            //     var client = <?php //echo $client_id;
                                ?>;

            //     alert(destination);
            //     //var destination = 79;

            //     $.ajax({
            //     url:"http://localhost/GraciousExpress/fetch-details.php" ,
            //     type : "GET",
            //     data : {cmd:"get_pay_info",data:data,client:client,destination:destination},
            //     dataType:"JSON",
            //     async:false,
            //     success :function(datas){
            //         console.log(datas.train);
            //         $("#rate").val(datas.train);
            //          }
            //      });
            // });
            localStorage.removeItem("redirect_page_view")
            sessionStorage.removeItem("retry_payment")
            
            //Validate Package Info
            function package_info_validate() {
                var valid = true,
                    message = '';

                $('.package-info .form-group .package-i').each(function() {
                    var $this = $(this);

                    // if(!$this.val()) {
                    if (!$this.val() || $this.val() == 'Select Package Type') {
                        var inputName = $this.attr('name');
                        valid = false;
                        // message += 'Please enter your ' + inputName + '\n';
                        $(this).siblings('span').addClass('pack_required');
                        $(this).css('border-color', '#d9534f');

                        $this.keyup(function() {
                            // alert("d");
                            $(this).siblings('span').removeClass('pack_required');
                            $(this).css('border-color', '#66afe9');
                        });
                        $this.change(function() {
                            // alert("d");
                            $(this).siblings('span').removeClass('pack_required');
                            $(this).css('border-color', '#66afe9');

                        });
                    } else {
                        $(this).siblings('span').removeClass('pack_required');
                        $(this).css('border-color', '#66afe9');
                    }
                });

                if (!valid) {
                    // alert("not valied");
                    valid = false;
                }
                return valid;
            }
            //Submit
            $(document).on('click', '#save', function(e) {
                //alert("test");
                e.preventDefault();
            	
             	var pkg_validate = package_info_validate();
                // alert(pkg_validate);
                const length = $.map($('input[type=text][name="length[]"]'), function(el) {
                    return el.value;
                });
                const width = $.map($('input[type=text][name="width[]"]'), function(el) {
                    return el.value;
                });
                const height = $.map($('input[type=text][name="height[]"]'), function(el) {
                    return el.value;
                });
                const quanti = $.map($('input[type=text][name="quantity[]"]'), function(el) {
                    return el.value;
                });
                const vlm_weight = $.map($('input[type=text][name="weight[]"]'), function(el) {
                    return el.value;
                });
                //console.log("weight", vlm_weight);
                var get_exp_date = $('#eway_expiryDate').val().split("-");
                var eway_expiry_date = get_exp_date[2] + "-" + get_exp_date[1] + "-" + get_exp_date[0];

                //Select Transport 
                if ($("div#by-air").hasClass('active')) {
                    var air = $('#by-air #span').text();

                } else if ($("div#by-train").hasClass('active')) {
                    var train = $('#by-train #span').text();

                } else if ($("div#by-road-surface").hasClass('active')) {
                    var roadsurface = $('#by-road-surface #span').text();

                } else if ($("div#by-road-express").hasClass('active')) {
                    var roadexpress = $('#by-road-express #span').text();

                } else {
                    var localdelivery = $('#by-local #span').text();
                }

                //Get Surfce Type
                if ($("div#by-road-surface").hasClass('active')) {

                    if ($("div#by-surface-ftl").hasClass('active')) {
                        var ftl = $('#by-surface-ftl #span').text();
                    } else {
                        var ptl = $('#by-surface-ptl #span').text();
                    }

                }
                //Get Transport Type
                if ($("div#by-surface-ftl").hasClass('active')) {
                    if ($("div#type1").hasClass('active')) {
                        var type1 = $('#type1 h6').text();
                    } else if ($("div#type2").hasClass('active')) {
                        var type2 = $('#type2 h6').text();
                    } else if ($("div#type3").hasClass('active')) {
                        var type3 = $('#type3 h6').text();
                    } else if ($("div#type4").hasClass('active')) {
                        var type4 = $('#type4 h6').text();
                    } else if ($("div#type5").hasClass('active')) {
                        var type5 = $('#type5 h6').text();
                    } else if ($("div#type6").hasClass('active')) {
                        var type6 = $('#type6 h6').text();
                    } else if ($("div#type7").hasClass('active')) {
                        var type7 = $('#type7 h6').text();
                    } else if ($("div#type8").hasClass('active')) {
                        var type8 = $('#type8 h6').text();
                    } else if ($("div#type9").hasClass('active')) {
                        var type9 = $('#type9 h6').text();
                    } else if ($("div#type10").hasClass('active')) {
                        var type10 = $('#type10 h6').text();
                    } else {
                        var type11 = $('#type11 h6').text();
                    }
                }


                //Get Payment Mode
                if ($("div#to-billed").hasClass('active')) {
                    var tobilled = $('#to-billed #span').text();
                } else if ($("div#to-pay").hasClass('active')) {
                    var topay = $('#to-pay #span').text();
                } else if ($("div#paid").hasClass('active')) {
                    var paid = $('#paid #span').text();
                } else {
                    var cod = $('#cod #span').text();
                }

                //
                var consigner = <?php echo $client_id; ?>;

                var grn_no = $("#grn_no").val();
                var origin = $("#origin").val();
                var destination = $("#destination").val();
                var grn_date = $("#grn_date").val();
                var id = $("#id").val();
                var file_data = $("#file_receipt").prop('files')[0];
                var form_data = new FormData(document.getElementById("userbookconsignment"));
                form_data.append('consignor', consigner)
                form_data.append('file', file_data);
                form_data.append('grn_no', grn_no);
                form_data.append('id', id);
                form_data.append('grn_date', grn_date);
                form_data.append('origin', origin);
                form_data.append('destination', destination);
                form_data.append('length', length);
                form_data.append('width', width);
                form_data.append('height', height);
                form_data.append('quanti', quanti);
                form_data.append('vlm_weight', vlm_weight);
                form_data.append('eway_expiryDates', eway_expiry_date);
                //Append Transport Mode
                if ($("div#by-air").hasClass('active')) {
                    form_data.append('air', air);
                } else if ($("div#by-train").hasClass('active')) {
                    form_data.append('train', train);
                } else if ($("div#by-road-surface").hasClass('active')) {
                    form_data.append('roadsurface', roadsurface);
                } else if ($("div#by-road-express").hasClass('active')) {
                    form_data.append('roadexpress', roadexpress);
                } else {
                    form_data.append('localdelivery', localdelivery);
                }
                //Append Surface Type
                if ($("div#by-surface-ftl").hasClass('active')) {
                    form_data.append('ftl', ftl);
                } else if ($("div#by-surface-ptl").hasClass('active')) {
                    form_data.append('ptl', ptl);
                } else {
                    console.log("");
                }
                //Append Transport Type
                // if ($("div#type1").hasClass('active')) {
                //     form_data.append('type1', type1);
                // } else if ($("div#type2").hasClass('active')) {
                //     form_data.append('type2', type2);
                // } else if ($("div#type3").hasClass('active')) {
                //     form_data.append('type3', type3);
                // } else if ($("div#type4").hasClass('active')) {
                //     form_data.append('type4', type4);
                // } else if ($("div#type5").hasClass('active')) {
                //     form_data.append('type5', type5);
                // } else if ($("div#type6").hasClass('active')) {
                //     form_data.append('type6', type6);
                // } else if ($("div#type7").hasClass('active')) {
                //     form_data.append('type7', type7);
                // } else if ($("div#type8").hasClass('active')) {
                //     form_data.append('type8', type8);
                // } else if ($("div#type9").hasClass('active')) {
                //     form_data.append('type9', type9);
                // } else if ($("div#type10").hasClass('active')) {
                //     form_data.append('type10', type10);
                // } else if ($("div#type11").hasClass('active')) {
                //     form_data.append('type11', type11);
                // } else {
                //     console.log("");
                // }
                //Append Payment
                if ($("div#to-billed").hasClass('active')) {
                    form_data.append('tobilled', tobilled);
                } else if ($("div#to-pay").hasClass('active')) {
                    form_data.append('topay', topay);
                } else if ($("div#paid").hasClass('active')) {
                    form_data.append('paid', paid);
                } else {
                    form_data.append('cod', cod);
                }
            	let party_invoice_validate = $('input[name="invoice[]"].invoice_exist');
			    console.log(party_invoice_validate.length);
                if ($("div#by-surface-ftl").hasClass('active')) {
                    if ($("#userbookconsignment").valid() == true && pkg_validate == true) {
                     if (party_invoice_validate.length == 0) {
                        filesExistCheck();
                        if (img_avail == true) {
                            $('.form-data-saving').show();
                            $.ajax({
                                url: "../web/save_details.php",
                                type: "POST",
                                dataType: "json",
                                contentType: false,
                                cache: false,
                                processData: false,
                                data: form_data,
                                success: function(data) {
                                    console.log(data);
                                    $('.form-data-saving').hide();

                                    if (data.result == 1) {
                                        swal({
                                            title: "Great!",
                                            text: "Your booking: " + data.data + ", Our Executive will reach you to pick the consignment in next 2-3 Hours !",
                                            icon: "success",
                                            buttons: "OK",
                                        }).then(function(isConfirm) {
                                            if (isConfirm) {
                                                window.location.href = "booking_list.php";
                                            } else {
                                                //if no clicked => do something else
                                                location.reload();
                                            }
                                        });

                                    } else {
                                        console.log("Booking Failed");
                                    }

                                }
                            });
                        } else {
                            swal({
                                title: "Attachment is Required!",
                                icon: "warning",
                                buttons: "OK",
                            })
                        }
                     }else{
                     alert('Invoice Already Exist');
                     }
                    }
                } else {
                    if ($("div#paid").hasClass('active')) { //Check Pay at Booking 

                        if ($("#rate").val() == 0) { // IF Rate is 0 Booking Not Submit
                            swal({
                                title: "Attention!",
                                text: "Your booking is not submited because your consignee rate is 0. \n Contact Gracious Team",
                                icon: "warning",
                                buttons: "OK",
                            })

                        } else { //  Booking Submited

                            if ($("#userbookconsignment").valid() == true && pkg_validate == true) {
                             if (party_invoice_validate.length == 0) {
                                filesExistCheck();
                                if (img_avail == true) {
                                    $('.form-data-saving').show();

                                    $.ajax({
                                        url: "../web/save_details.php",
                                        type: "POST",
                                        dataType: "json",
                                        contentType: false,
                                        cache: false,
                                        processData: false,
                                        data: form_data,
                                        success: function(data) {
                                            console.log(data);
                                            $('.form-data-saving').hide();

                                            if (data.result == 1) {
                                                if((data.result_url != '') && data.result_url != '0'){
                                                    window.location.href = data.result_url;
                                                }else {
                                                    swal({
                                                        title: "Great!",
                                                        text: "Your booking: " + data.data + ", Our Executive will reach you to pick the consignment in next 2-3 Hours !",
                                                        icon: "success",
                                                        buttons: "OK",
                                                    }).then(function(isConfirm) {
                                                        if (isConfirm) {
                                                            window.location.href = "booking_list.php";
                                                        } else {
                                                            //if no clicked => do something else
                                                            location.reload();
                                                        }
                                                    });
                                                }

                                            } else {
                                                console.log("Booking Failed");
                                                //     console.log("payment_data", data.result);

                                                //     sessionStorage.setItem("retry_payment", "../redirect.php?data=" + encodeURIComponent(data.result));

                                                // window.location.href = "redirect.php?data=" + encodeURIComponent(data.result);
                                                    // window.location.href = data.result;
                                            }

                                        }
                                    });
                                } else {
                                    swal({
                                        title: "Attachment is Required!",
                                        icon: "warning",
                                        buttons: "OK",
                                    })
                                }
                             }else {
                                alert('Invoice Already Exist');
                                    }

                            }

                        }

                    } else { //Other Payments Types Goes here

                        if ($("#userbookconsignment").valid() == true && pkg_validate == true) {
                        if (party_invoice_validate.length == 0) {
                            filesExistCheck();
                            if (img_avail == true) {
                                $('.form-data-saving').show();
                                $.ajax({
                                    url: "../web/save_details.php",
                                    type: "POST",
                                    dataType: "json",
                                    contentType: false,
                                    cache: false,
                                    processData: false,
                                    data: form_data,
                                    success: function(data) {
                                        console.log(data);
                                        $('.form-data-saving').hide();

                                        if (data.result == 1) {
                                            swal({
                                                title: "Great!",
                                                text: "Your booking: " + data.data + ", Our Executive will reach you to pick the consignment in next 2-3 Hours !",
                                                icon: "success",
                                                buttons: "OK",
                                            }).then(function(isConfirm) {
                                                if (isConfirm) {
                                                    window.location.href = "booking_list.php";
                                                } else {
                                                    //if no clicked => do something else
                                                    location.reload();
                                                }
                                            });

                                        } else {
                                            console.log("Booking Failed");
                                        }

                                    }
                                });

                            } else {
                                swal({
                                    title: "Attachment is Required!",
                                    icon: "warning",
                                    buttons: "OK",
                                    customClass: 'swal-wide',
                                })
                            }
                        }else {
                           alert('Invoice Already Exist');
                           }


                        }

                    }
                }
            })
        });

        $(document).ready(function() {
            if ($(window).width() < 992) {
                $('.custom-col').removeClass('col-lg-2');
            } else {
                $('.custom-col').addClass('col-lg-2');
            }
            $(window).resize(function() {
                if ($(window).width() < 992) {
                    $('.custom-col').removeClass('col-lg-2');
                } else {
                    $('.custom-col').addClass('col-lg-2');
                }
            })
        })

        $(document).ready(function() {
            function readURL(input) {
                var fileName = $(input).val().replace(/C:\\fakepath\\/i, '');
                var extension = fileName.substr(fileName.lastIndexOf('.') + 1);
                if (input.files && input.files[0]) {
                    if (extension == 'png' || extension == 'jpeg' || extension == 'jpg') {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var idName = $(input).parent().parent().parent().parent().attr('id');
                            $('#' + idName + ' .imagePreview').css('background-image', 'url(' + e.target.result + ')');
                            $('#imagePreview').fadeIn(650);
                        }
                        reader.readAsDataURL(input.files[0]);
                    } else {
                        var firstchild_imageurl = 'images/download.png';
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var idName = $(input).parent().parent().parent().parent().attr('id');
                            $('#' + idName + ' .imagePreview').css('background-image', 'url(images/download.png)');
                            $('#imagePreview').fadeIn(650);
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }
            }
            $(document).on('change', '.imageUpload', function() {
                // alert("hi");
                readURL(this);
            })

        });
        $(window).load(function() {
            $(".loading-page").hide();
        });
    </script>

    <?php include_once('include/user-footer-js.php'); ?>
</body>

</html>