<?php
if (session_id() == '') {
    session_start();
}

error_reporting(1);
include_once('include/user-function.php');
require_once('include/connect.php');

if ($_REQUEST['key'] != '') {
    $tbl_id = $_REQUEST['key'];
    $date = $_REQUEST['grn_date'];
    //$date = date('d-m-Y');
    $my = date('m-Y');
    $dt = (explode("-", $date));

    //   var_dump($dt); //array(3) { [0]=> string(2) "03" [1]=> string(2) "08" [2]=> string(4) "2021" }
    if ($dt[1] <= 3) {
        $m1 = 1;
        $y = $dt[2];
        $trans_name = "transaction_" . $m1 . "_" . $y;
        $trans_images = "transaction_images_" . $m1 . "_" . $y;
        $trans_invoice = "transaction_invoice_" . $m1 . "_" . $y;
        //echo "First Quarter";

    } else if (($dt[1] >= 4) && ($dt[1] <= 6)) {
        $m1 = 2;
        $trans_name = "transaction_" . $m1 . "_" . $y;
        $trans_images = "transaction_images_" . $m1 . "_" . $y;
        $trans_invoice = "transaction_invoice_" . $m1 . "_" . $y;
        //echo "Second Quarter";
    } else if (($dt[1] >= 7) && ($dt[1] <= 9)) {
        $m1 = 3;
        $y = $dt[2];
        $trans_name = "transaction_" . $m1 . "_" . $y;
        $trans_images = "transaction_images_" . $m1 . "_" . $y;
        $trans_invoice = "transaction_invoice_" . $m1 . "_" . $y;

        //   var_dump($trans_invoice);
        //echo "Third Quarter";
    } else {
        $m1 = 4;
        $y = $dt[2];
        $trans_name = "transaction_" . $m1 . "_" . $y;
        $trans_images = "transaction_images_" . $m1 . "_" . $y;
        $trans_invoice = "transaction_invoice_" . $m1 . "_" . $y;
        //echo "Fourth Quarter";
    }


    $query = "select *from transaction_" . $m1 . "_" . $dt[2] . " where md5(transaction_id) ='$tbl_id' ";
    $query_result = mysqli_query($conn, $query);

    $user_booking = mysqli_fetch_assoc($query_result);

    $grn_no = $user_booking['grn_no'];
} else {
    $grn_no = trim($_REQUEST['grn_no']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <title>Gracious Express - Book Consignment</title>
    <?php include("include/title.php"); ?>
    <?php include("include/css_js_forgetpassword.php"); ?>
    <link href="../assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
    <link href="assets/css/master.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script> -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" />
    <!-- book consignment css and js starts here -->
    <link rel="stylesheet" href="assets/css/book-consignment.css">
    <link rel="stylesheet" href="f5/fontawesome.min.css">
    <!-- book consignment css and js finished here -->
    <!-- <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script> -->
    <script src="assets/js/jquery.validate.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>
    <style>
        *,
        *:after,
        *:before {
            margin: 0;
            padding: 0;
            box-sizing: border-box;

        }

        .status-progress .circle.active .label {
            position: relative;
        }

        .active i {
            left: 50%;
            top: 50%;
            right: unset;
            transform: translate(-50%, -50%);
        }

        /* Form Progress */
        .status-progress {
            width: 100%;
            margin: 20px auto;
            text-align: center;
        }

        .status-progress .circle,
        .status-progress .bar {
            display: inline-block;
            background: #fff;
            width: 30px;
            height: 30px;
            border-radius: 30px;
            border: 1px solid #d5d5da;
            font-family: sans-serif;

        }

        .status-progress .bar {
            position: relative;
            width: 100px;
            height: 5px;
            top: -52px;
            margin-left: 0px;
            margin-right: 0px;
            border-left: none;
            border-right: none;
            border-radius: 0;
            /* width:0; */
            /* transition: width 100ms ease-in-out; */
        }

        @media screen and (max-width:1204px) {
            .status-progress .bar {
                position: relative;
                width: 85px;
                height: 9px;
                top: -56px;
                margin-left: 0px;
                margin-right: 0px;
                border-left: none;
                border-right: none;
                border-radius: 0;
            }
        }

        @media screen and (max-width:1150px) {
            .status-progress .bar {
                position: relative;
                width: 78px;
                height: 9px;
                top: -56px;
                margin-left: 0px;
                margin-right: 0px;
                border-left: none;
                border-right: none;
                border-radius: 0;
            }
        }

        @media screen and (max-width:1080px) {
            .status-progress .bar {
                position: relative;
                width: 60px;
                height: 5px;
                top: -52px;
                margin-left: 0px;
                margin-right: 0px;
                border-left: none;
                border-right: none;
                border-radius: 0;
            }
        }

        @media screen and (max-width:825px) {
            .status-progress .bar {
                position: relative;
                width: 48px;
                height: 5px;
                top: -52px;
                margin-left: 0px;
                margin-right: 0px;
                border-left: none;
                border-right: none;
                border-radius: 0;

            }

        }

        .status-progress .circle .label {
            display: inline-block;
            width: 22px;
            height: 22px;
            line-height: 18px;
            border-radius: 32px;
            margin-top: 3px;
            color: #b5b5ba;
            font-size: 12px;
        }

        .status-progress .circle .title {
            color: #b5b5ba;
            font-size: 12px;
            line-height: 20px;
            margin-left: -19px;
            display: flex;
            margin-top: 30px;
            font-weight: 600;
            background-color: #fff !important;
        }

        /* Done / Active */
        .status-progress .bar.done,
        .status-progress .circle.done {
            font-family: sans-serif;
            background: #8bc435;

        }

        .status-progress .bar.active {
            background: linear-gradient(to right, #0c95be 40%, #0c95be 60%);

        }

        .status-progress .circle.done .label {
            color: #FFF;
            background: #8bc435;
            margin-left: 3px;
            box-shadow: inset 0 0 2px rgba(0, 0, 0, .2);
        }

        .status-progress .circle.done .title {
            color: #444;
        }

        .status-progress .circle.active .label {
            color: #FFF;
            background: #0c95be;
            margin-left: 3px;
            box-shadow: inset 0 0 2px rgba(0, 0, 0, .2);
        }

        .status-progress .circle.active .title {
            color: #0c95be;
        }

        .track-orderme {
            display: inline-block;
            max-width: 250px;
            height: 48px;

        }

        .custyle .table thead>tr>th,
        .table tbody>tr>th,
        .table tfoot>tr>th,
        .table thead>tr>td,
        .table tbody>tr>td,
        .table tfoot>tr>td {
            padding: 6px 4px !important;
        }

        .table-info {
            background: #86cfda;
        }

        .user-track-consignment-table {
            width: 90%;
            margin: 0 auto;
        }

        .primary_heading {
            background: #eee;
        }

        .secondary_heading {
            /* background : #17a2b852; */
            background: #94d99a52;
        }

        .primary_heading th,
        .secondary_heading th {
            /* color :#17a2b8 !important; */
            color: #519c4bc2 !important;
            text-align: center;
        }

        .user-track-consignment-table .status-table tbody tr td:first-child {
            width: 30% !important;
        }

        /* .user-track-consignment-table .table tbody tr td:nth-child(2){
      width : 20% !important
    } */
        .user-track-consignment-table .status-table tbody tr td {
            width: 17.5%;
            padding: 12px 8px !important;
        }

        .user-track-consignment-table .status-table>tbody>tr:nth-child(2n)>td,
        .table-striped>tbody>tr:nth-child(2n)>th {
            background-color: #f0fbfbc4;
        }

        .grn-dtls-table tbody tr td {
            width: 16.66%;
            padding: 12px 8px !important;
        }

        @media (min-width: 360px) and (max-width: 576.98px) { 
    .dashboard-header, .dashboard-charts {
    width: 100%;
    float:none!important;
}
   }

        @media (max-width: 575.98px) {
            .status-progress .circle {
                margin-top: 70px !important;
            }
        }
    </style>

</head>

<body>

    <?php include "user-db-header.php" ?>
    <div class="container-fluid">
        <div class="row">

            <div class="col-md-12">
                <h4 class="text-center"><i class="fa fa-table"></i> Track Consignment</h4>
                <form id="track_form">
                    <div class="row">
                        <div class="col-sm-offset-4 col-sm-6">
                            <div class="form-group">
                                <div class="col-md-6">
                                    <label for="">GRN No:</label>
                                    <input type="text" name="grn_no" id="grn_no" value="<?php echo $grn_no; ?>" class="form-control" autocomplete="off" onkeypress="allowAlphaNumericSpace(event);"/>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success" id="search" style="margin-top:23px">Track Now <i class="fa fa-plane"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php


                    if ($grn_no != '') {
                        $count = 1;
                        $tbl = $tbl_inv = '';
                        $query1 = "select *from transaction_tbls";
                        $query_res = mysqli_query($conn, $query1);
                        while ($row1 = mysqli_fetch_assoc($query_res)) {
                            $tbl = "transaction_" . $row1['table_name'];
                            $tbl_inv = "transaction_invoice_" . $row1['table_name'];
                            $query_status = "select *from $tbl where grn_no='$grn_no' and booking_status = '' ";
                            $result_status = mysqli_query($conn, $query_status);
                            $consignment_details = mysqli_fetch_assoc($result_status);
                            if (mysqli_num_rows($result_status) > 0) {

                                $count++;
                                echo '</br> <div class="mt-5 mb-5 text-center">
                                         <h2>Tracking Status</h2>
                                         </div>
                                         <br/>';
                                extract($consignment_details);


                                $status = array();
                                array_push($status, 1);
                                $query_status1 = "select *from transaction_status_log where grn_no='$grn_no' ";
                                $result_status1 = mysqli_query($conn, $query_status1);
                                while ($row = mysqli_fetch_assoc($result_status1)) {

                                    if (!in_array($row['from_status'], $status))
                                        array_push($status, $row['from_status']);

                                    if (!in_array($row['to_status'], $status))
                                        array_push($status, $row['to_status']);
                                }
                                $count = 1;
                                $max = max($status);
                                $remarks = get_cong_remarks($conn, $max, $grn_no);
                                echo '<div class="status-progress">';
                                for ($i = 1; $i < 9; $i++) {
                                    if (in_array($i, $status)) {
                                        if ($i != 1)
                                            echo '<span class="bar"></span>';
                                        echo '<div class="circle"><span class="label">' . $i . '</span><span class="title">' . get_trans_status($i) . '</span></div>';
                                        $count++;
                                    } else if ($i > $max) {
                                        echo '<span class="bar"></span>';
                                        echo '<div class="circle"><span class="label">' . $i . '</span><span class="title">' . get_trans_status($i) . '</span></div>';
                                    }
                                }
                                echo '</div>';

                                $query_inv = mysqli_query($conn, "select sum(no_of_pkge) as no_of_pkge from $tbl_inv where transaction_id='$transaction_id'");
                                $pkge_row = mysqli_fetch_assoc($query_inv);

                    ?>
                                <br>
                                <br>
                                <br>
                                <?php

                                $trans_status = "SELECT * FROM `transaction_status` WHERE sheet_id IN(select sheet_id from transaction_status_log where grn_no='$grn_no')";
                                $res = mysqli_query($conn, $trans_status);
                                ?>
                                <div class="user-track-consignment-table">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-responsive grn-dtls-table">
                                            <thead>
                                                <tr class="secondary_heading">
                                                    <th class="text-center" colspan="6">Consignment Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>GRN No :</td>
                                                    <td><?php echo $grn_no; ?></td>
                                                    <td>Consignor :</td>
                                                    <td><?php echo get_client_name($conn, $consignment_details['consigner']); ?></td>
                                                    <td>Mode :</td>
                                                    <td>
                                                        <?php echo $mode = get_mode($conn, $consignment_details['mode_of_transportation']);
                                                        $mode_array = explode(" ", $mode);
                                                        //   var_dump($mode_array);
                                                        //   exit();
                                                        $icon = '<i class="fa fa-truck fa-flip-horizontal"></i>';
                                                        if (in_array('AIR', $mode_array))
                                                            $icon = '<i class="fa fa-plane"></i>';

                                                        if (in_array('TRAIN', $mode_array))
                                                            $icon = '<i class="fa fa-train"></i>';

                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>GRN Date :</td>
                                                    <td><?php echo $grn_date; ?></td>
                                                    <td>Consignee :</td>
                                                    <td><?php echo get_client_name($conn, $consignment_details['consignee']); ?></td>
                                                    <td>No Of Pkg :</td>
                                                    <td><?php echo $pkge_row['no_of_pkge']; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-responsive status-table ">
                                            <thead>
                                                <tr class="primary_heading">
                                                    <th colspan="5" class="text-center">Status & Scans</th>
                                                </tr>
                                                <tr class="secondary_heading">
                                                    <th>Status</th>
                                                    <!--- <th>Location</th> -->
                                                    <th>Details</th>
                                                    <th>Date </th>
                                                    <th>Time </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                while ($result_status = mysqli_fetch_assoc($res)) {
                                                    $re = $result_status['status'];
                                                    $date_data = $result_status['created_at'];
                                                    $remarks = $result_status['remarks'];
                                                    $origin = $result_status['origin'];
                                                    //$destination = $result_status['destination'];
                                                    // echo strlen($date_data);

                                                    if (strlen($date_data) == 20) {
                                                        $date = substr($date_data, 0, -9);
                                                        $t = substr($date_data, 11);
                                                        $time = date('h:i:s A', strtotime($t));
                                                    } else {
                                                        $date = $date_data;
                                                        $time = substr($date_data, 11);
                                                        //echo $t = date('h:i:s A', strtotime($time));
                                                    }

                                                ?>
                                                    <tr>

                                                        <td><?php echo get_trans_status($re); ?></td>
                                                        <!--<td><?php //echo get_city_name($conn,$destination);
                                                                ?></td>-->
                                                        <td><?php echo $remarks; ?></td>
                                                        <td><?php echo $date; ?></td>
                                                        <td><?php echo $time; ?></td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                        </table>
                                    </div>
                                </div>
                                <!-- <div class="mt-5 mb-5 text-center">
                                  <h3 class="text-success">Consignment Details</h3>
                                  <div class="col-md-offset-3 col-md-6">
                                  <table class="table table-bordered table-vilot">
                                      <tbody>
                                          <tr>
                                              <td>GRN No:</td>
                                              <td><?php //echo $grn_no; ?></td>
                                            </tr>
                                            <tr>
                                              <td>GRN Date:</td>
                                              <td><?php //echo $grn_date; ?></td>
                                            </tr>
                                            <tr>
                                              <td>Consignor Name:</td>
                                              <td><?php //echo get_client_name($conn, $consignment_details['consigner']); ?></td>
                                            </tr>
                                            <tr>
                                              <td>Consignee Name:</td>
                                              <td><?php //echo get_client_name($conn, $consignment_details['consignee']); ?></td>
                                            </tr>
                                            <tr>
                                              <td>Mode of Transport:</td>
                                              <td><?php //echo $mode=get_mode($conn,$consignment_details['mode_of_transportation']);
                                                    //   $mode_array= explode(" ",$mode);
                                                    // //   var_dump($mode_array);
                                                    // //   exit();
                                                    //   $icon = '<i class="fa fa-truck fa-flip-horizontal"></i>';
                                                    //   if(in_array('AIR',$mode_array))
                                                    //   $icon = '<i class="fa fa-plane"></i>';

                                                    //   if(in_array('TRAIN',$mode_array))
                                                    //   $icon = '<i class="fa fa-train"></i>';

                                                    ?></td>
                                            </tr>
                                            <tr>
                                              <td>No. of Packages:</td>
                                              <td><?php //echo $pkge_row['no_of_pkge']; ?></td>
                                            </tr>
                                            <tr>
                                              <td>Payment Mode:</td>
                                              <td><?php //echo consignment_mode($conn, $consignment_details['mode_of_consignment']); ?></td>
                                            </tr>
                                            <tr>
                                              <td>Status:</td>
                                              <td><?php //echo get_trans_status($max); ?></td>
                                            </tr>
                                            <tr>
                                              <td>Remarks:</td>
                                              <td><?php //echo $remarks; ?></td>
                                            </tr>
                                      </tbody>
                                  </table>
                                  </div>
                                 
                              </div> -->
                    <?php
                            }
                        }
                        if ($count == 1)
                        echo '<p class="text-center text-danger">Incorrect GRN No Or Booking Cancelled... Please check and try again!</p> ';

                    }

                    ?>

                </form>
            </div>


            <!-- <div class="stepper-horizontal" id="stepper1">
              <div class="step editing">
                  <div class="step-circle"><span>1</span></div>
                  <div class="step-title">Consignment Booked</div>
                  <div class="step-bar-left"></div>
                  <div class="step-bar-right"></div>
              </div>
              <div class="step">
                  <div class="step-circle"><span>2</span></div>
                  <div class="step-title">Consignment Picked Up</div>
                  <div class="step-bar-left"></div>
                  <div class="step-bar-right"></div>
              </div>
              <div class="step">
                  <div class="step-circle"><span>3</span></div>
                  <div class="step-title">In Transit -1 (At origin state)</div>
                  <div class="step-bar-left"></div>
                  <div class="step-bar-right"></div>
              </div>
              <div class="step">
                  <div class="step-circle"><span>4</span></div>
                  <div class="step-title">In Transit -2 (Towards destination state)</div>
                  <div class="step-bar-left"></div>
                  <div class="step-bar-right"></div>
              </div>
              <div class="step">
                  <div class="step-circle"><span>5</span></div>
                  <div class="step-title">In Transit -3 (Towards destination)</div>
                  <div class="step-bar-left"></div>
                  <div class="step-bar-right"></div>
              </div>
              <div class="step">
                  <div class="step-circle"><span>6</span></div>
                  <div class="step-title">At Destination</div>
                  <div class="step-bar-left"></div>
                  <div class="step-bar-right"></div>
              </div>
              <div class="step">
                  <div class="step-circle"><span>7</span></div>
                  <div class="step-title">Out for Delievery</div>
                  <div class="step-bar-left"></div>
                  <div class="step-bar-right"></div>
              </div>
              <div class="step">
                  <div class="step-circle"><span>8</span></div>
                  <div class="step-title">Consignment Delievered Successfully</div>
                  <div class="step-bar-left"></div>
                  <div class="step-bar-right"></div>
              </div>
        </div>
        </div>
          
  </div> -->

            <script>
                var i = 1;
                var j = '<?php echo $count; ?>'
                //alert(j); status =3
                $('.status-progress .circle').removeClass().addClass('circle');
                $('.status-progress .bar').removeClass().addClass('bar');
                setInterval(function() {
                    if (i <= j) {
                        $('.status-progress .circle:nth-of-type(' + i + ')').addClass('active');
                        $('.status-progress .circle:nth-of-type(' + i + ') .label').html('<?php echo $icon; ?>');
                        $('.status-progress .circle:nth-of-type(' + (i - 1) + ')').removeClass('active').addClass('done');
                        $('.status-progress .circle:nth-of-type(' + (i - 1) + ') .label').html('&#10003;');
                        $('.status-progress .bar:nth-of-type(' + (i - 1) + ')').addClass('active');
                        $('.status-progress .bar:nth-of-type(' + (i - 2) + ')').removeClass('active').addClass('done');

                        i++;
                    }
                }, 2000);
                $('.status-progress .circle').css('margin-top', '70px ! important');
            </script>
            <script>
 function allowAlphaNumericSpace(e) {
  var code = ('charCode' in e) ? e.charCode : e.keyCode;
  if (!(code == 32) && // space
    !(code > 47 && code < 58) && // numeric (0-9)
    !(code > 64 && code < 91) && // upper alpha (A-Z)
    !(code > 96 && code < 123)) { // lower alpha (a-z)
    e.preventDefault();
  }
}
                $(window).load(function() {
                    $(".loading-page").hide();
                });



                var currentStep = 0;
                var numSteps = 8;

                // function nextStep() {
                // currentStep++;
                // if (currentStep > numSteps) {
                // currentStep = 1;
                // }
                // var stepper = document.getElementById('stepper1');
                // var steps = stepper.getElementsByClassName('step');

                // Array.from(steps).forEach((step, index) => {
                //     let stepNum = index + 1;
                // if (stepNum === currentStep) {
                // addClass(step, 'editing');
                // } else {
                // removeClass(step, 'editing');
                // }    
                // if (stepNum < currentStep) {
                // addClass(step, 'done');
                // } else {
                // removeClass(step, 'done');
                // }
                // })  
                // }
                <?php $new = 1; ?>
                const shipStage = () => {
                    let stepper = document.getElementById('stepper1');
                    let steps = stepper.getElementsByClassName('step');
                    let shipStageVal = <?php echo $new ?>;

                    const doSetTimeOut = (i) => {
                        setTimeout(() => {
                            steps[i].classList.add('done');
                        }, i * 1400);
                    }

                    for (i = 0; i < steps.length; i++) {
                        if (i <= shipStageVal) {
                            doSetTimeOut(i);
                        }
                    }

                }
            </script>

</body>

</html>