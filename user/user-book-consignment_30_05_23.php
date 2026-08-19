<?php
if (session_id() == '') {
    session_start();
}
unset($_SESSION['msg']); //remove session message
unset($_SESSION['paymentId']); //remove session payment
unset($_SESSION['LastRequest']); //remove booking_list url session
$payment_due = isset($_SESSION['payment_due']) ? $_SESSION['payment_due'] : 0;
require_once("include/user-function.php");
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

    <link rel="stylesheet" href="assets/css/book-consignment.css">
    <link rel="stylesheet" href="f5/css/all.css">
    <!-- book consignment css and js finished here -->
    <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>
    <script src="assets/js/jquery.validate.min.js"></script>
    <style>
        .vlm:after {
            content: " *";
            color: red;
        }

        #inner1 {
            float: left;
        }

        #inner2 {
            float: left;
            clear: left;
        }


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
        .ribbon-pop p{
            margin:0px!important
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
            margin-bottom: 10px;
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

            .send-rcv-dtl .volumetric-input-boxes {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                border-bottom: 1px solid gray;
                margin-top: 10px;
            }

            label#width-error,
            label#length-error,
            label#quantity-error,
            label#height-error {
                display: none !important;
            }
        }
    </style>
</head>


<body style>
    <div id="ribbon">
        <div class="ribbon-pop">Your Due Payment : <p> &#x20B9; <?php echo $payment_due; ?>.00</p></div>
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
                    if($client_id != '-1'){
                        ?>
                    <div class="block" id="select-shipping" style="display : block">
                        <h5>I Prefer Mode Of Shipping Through ...</h5>
                        <div class="row">
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block" id="by-air">
                                    <span class="fa fa-plane custom-icon-size"></span>
                                    <!-- <p> &nbsp; </p> -->
                                    <h6>By Air</h6>
                                    <span id="span" style="display:none;">1</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
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
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
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

                    <div class="block" id="select-truck">
                        <h5>Select Truck Type ...</h5>
                        <div class="row">
                            <div class="dropdown">
                                <select class="dropp" role="menu" aria-labelledby="menu1" id="dropp">
                                    <option value="">Select Truck Type...</option>
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
                            <div class="col-xs-3 col-sm-3 cust-padding-margin">
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
                                                        <option>Select Consignee</option>
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
                                                        </tbody>
                                                    </table>
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
                                                        <label for="reciever-name">No Of Packages</label>
                                                        <input type="text" class="form-control  user_pack" id="package-qty" name="package-qty[]" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-contact-no" class="vlm">Type Of Package</label>
                                                        <select name="package_type[]" id="package_type" class="form-control user_pack" required>
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
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-email">Invoice No</label>
                                                        <input type="text" class="form-control user_pack" id="invoice" name="invoice[]" autocomplete="off">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-city">Contents</label>
                                                        <input type="text" class="form-control user_pack" id="contents" name="contents[]" autocomplete="off">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-address" class="vlm">Quantity </label>
                                                        <input type="text" required class="form-control user_pack" id="qty" name="qty[]" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-area">Gross Wt.(Kgs)</label>
                                                        <input type="text" class="form-control user_pack" id="gross_kg" name="gross_kg[]" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                    </div>
                                                    <div class="form-group col-sm-3 charged">
                                                        <label for="reciever-area" class="vlm">Charged Wt.(Kgs)</label>
                                                        <input type="text" class="form-control charged-weight user_pack" id="charged_kg" name="charged_kg[]" onchange="cumulative_charge_wight();" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                                        <input type="hidden" class="form-control c_weight" id="c_weight" name="c_weight[]" />
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
                                                        <input style="flex : 6 ; margin-bottom : 0;" type="text" name="declared_val" id="declared_val" class="form-control declared-value" onchange="fov_calc();" required>
                                                    </div> -->

                                                <div id="wrapper">
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
                                                        <input type="number" placeholder="length" class="form-control length" id="length" name="length[]" onchange="calculation();"><span>X</span>
                                                        <input type="number" placeholder="width" class="form-control width " id="width" name="width[]" onchange="calculation();"><span>X</span>
                                                        <input type="number" placeholder="height" class="form-control height" id="height" name="height[]" onchange="calculation();"> <span>X</span>
                                                        <input type="number" placeholder="Qty" class="form-control quantity" id="quantity" name="quantity[]" onchange="calculation();"> <span>=</span>
                                                        <input type="number" placeholder="Feet/Kgs" class="form-control weight" id="weight" name="weight[]" onchange="calculation();">
                                                        <input type="hidden" class="form-control volume_weight" id="volume_weight" name="volume_weight[]" />
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 text-right">
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
                                                                <label onclick="DelAttaDiv()" class="hide" for="imageUpload"></label>
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
                     }else{
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




        function calculation() {
            var l = $(".length").val();

            var w = $(".width").val();
            var h = $(".height").val();
            var q = $(".quantity").val();


            var toplam = 0;
            var toplam1 = 0;
            var toplam2 = 0;
            var toplam3 = 0;
            $(".length").each(function() {
                toplam = toplam + parseInt($(this).val());
                //console.log(toplam);
            })
            $(".width").each(function() {
                toplam1 = toplam1 + parseInt($(this).val());
                //console.log(toplam1);
            })
            $(".height").each(function() {
                toplam2 = toplam2 + parseInt($(this).val());
                //console.log(toplam2);
            })
            $(".quantity").each(function() {
                toplam3 = toplam3 + parseInt($(this).val());
                // console.log(toplam3);
            })


            var l_w_h_q = parseInt(toplam) * parseInt(toplam1) * parseInt(toplam2) * parseInt(toplam3);

            var de = 1000000;

            var divide = parseInt(l_w_h_q) / parseInt(de);

            //convert to feet

            var feet = divide / 2;


            //convert cms to kgs 

            var cms = parseInt(toplam) * parseInt(toplam1) * parseInt(toplam2) / 28000;

            var cms_to_6times = cms * 6;

            //convert air to kgs 

            var air_kgs = parseInt(toplam) * parseInt(toplam1) * parseInt(toplam2) / 5000;

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
                var result = air_kgs * parseInt(toplam3);
                //console.log("air"+ result);

            } else {

                var result = cms_to_6times * parseInt(toplam3);
                console.log("Train Express Local Delivery" + result);
                $('.charged').show();

            }


            console.log("Result :" + result.toFixed(0));


            if (!isNaN(result)) {
                console.log(result);

                $(".volume_weight").val(result.toFixed(0));
                $(".weight").val(result.toFixed(0));

                // document.getElementById('weight').value = result;
                ss();

            }


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

            var fov = $('#fov_charges').val();

            if (fov != '')
                var totals = parseFloat(f) + parseFloat(l) + parseFloat(cr) + parseFloat(dc) + parseFloat(lc) + parseFloat(oc) + parseFloat(fov);
            else
                var totals = parseFloat(f) + parseFloat(l) + parseFloat(cr) + parseFloat(dc) + parseFloat(lc) + parseFloat(oc);



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
                url: '<?php print_r(site_path); ?>fetch_details.php',
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


        $(document).ready(function(e) {

            //Select2
            $('.dropp').select2();

            //Getting Selected values

            $(document).on('change', '#dropp', function() {
                //alert("change");
                var sel_ids = $('#dropp :selected').text();
                //alert(sel_ids.length);

                $('#truck_type').val(sel_ids);
                $('#select-payment-mode').addClass('show')

            });

            //Get Consingee
            $(document).on('change', '#sel-consignee', function() {
                var sel_id = $(this).val();
                //alert( sel_id);

                $.ajax({
                    url: "<?php print_r(site_paths) ?>fetch-details.php",
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
                    url: "<?php print_r(site_paths) ?>fetch-details.php",
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
                var rates_available = rates;
                if (rates_available == null) {
                    //alert(rates_available)
                    $("#declared_val").attr('readonly', true);
                } else {
                    $("#declared_val").attr('readonly', false);
                }
            }

            //Hide Payments Field

            $(document).on('click', function(e) {
                //alert('re');
                if ($("div#by-surface-ftl").hasClass('active')) {
                    $("#payment-info").hide();
                    var controlsEnabled = true;
                    console.log(controlsEnabled);
                    // $("#paid").on('click', function() {
                    //     alert('e');
                    //     // controlsEnabled = !controlsEnabled;
                    //     return !controlsEnabled;
                    //     console.log(controlsEnabled);
                    // })
                    $("#paid").css('display', 'none');
                } else if ($("div#by-surface-ptl").hasClass('active')) {
                    // alert("RE");
                    $('.v h5').addClass('vlm');
                    $(".volumetric-info .length").prop('required', true);
                    $(".volumetric-info .width").prop('required', true);
                    $(".volumetric-info .height").prop('required', true);
                    $(".volumetric-info .quantity").prop('required', true);

                    $('.chng_label').text('G.S.T (12 %)');
                    $('.charged').hide();

                    $('#weight').attr('placeholder',
                        'Feet');

                    $("#paid").css('display', 'block');

                    // var controlsEnabled = false;
                    // console.log(controlsEnabled);
                    // $("#paid").on('click', function() {
                    //     console.log("click", !controlsEnabled)
                    //     // controlsEnabled = !controlsEnabled;
                    //     if ($(this).hasClass('sub-block')) {
                    //         $(this).addClass('active');
                    //         $('#select-sender-dtl').addClass('show');
                    //     }
                    //     //return !controlsEnabled;

                    // })
                } else if ($("div#by-local").hasClass('active')) {
                    $('.chng_label').text('G.S.T (12 %)')
                } else {
                    $("#payment-info").show();
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
            //Submit
            $(document).on('click', '#save', function(e) {
                //alert("test");
                e.preventDefault();
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

                if ($("div#by-surface-ftl").hasClass('active')) {
                    if ($("#userbookconsignment").valid() == true) {
                        $('.form-data-saving').show();
                        $.ajax({
                            url: "<?php print_r(site_path) ?>save_details.php",
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
                            if ($("#userbookconsignment").valid() == true) {
                                $('.form-data-saving').show();
                                $.ajax({
                                    url: "<?php print_r(site_path) ?>save_details.php",
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

                                        } else if (data.result == 0) {
                                            console.log("Booking Failed");
                                        } else {
                                            console.log("payment_data", data.result);

                                            sessionStorage.setItem("retry_payment", "../redirect.php?data=" + encodeURIComponent(data.result));

                                            window.location.href = "redirect.php?data=" + encodeURIComponent(data.result);
                                        }

                                    }
                                });
                            }

                        }

                    } else { //Other Payments Types Goes here
                        // if ($("#rate").val() == 0) { // IF Rate is 0 Booking Not Submit
                        //     swal({
                        //         title: "Attention!",
                        //         text: "Your booking is not submited because your consignee rate is 0. \n Contact Gracious Team",
                        //         icon: "warning",
                        //         buttons: "OK",
                        //     })

                        //} else {
                            if ($("#userbookconsignment").valid() == true) {
                                $('.form-data-saving').show();
                                $.ajax({
                                    url: "<?php print_r(site_path) ?>save_details.php",
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
                            }
                        //}



                    }
                }


            })

        })



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