<?php
error_reporting(0);
$grn_no = trim($_REQUEST['grn_no']);
//$party_invoice_no=trim($_REQUEST['party_invoice_no']);


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <title>Gracious Express ~ Services</title>

    <link href="assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">

    <link href="assets/css/master.css" rel="stylesheet">

    <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">

    <style>
        *,
        *:after,
        *:before {
            margin: 0;
            padding: 0;
            box-sizing: border-box;

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
            width: 40px;
            height: 40px;
            border-radius: 40px;
            border: 1px solid #d5d5da;
            font-family: sans-serif;
        }

        .status-progress .bar {
            position: relative;
            width: 100px;
            height: 9px;
            top: -56px;
            margin-left: 0px;
            margin-right: 0px;
            border-left: none;
            border-right: none;
            border-radius: 0;
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
                height: 9px;
                top: -56px;
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
                height: 9px;
                top: -56px;
                margin-left: 0px;
                margin-right: 0px;
                border-left: none;
                border-right: none;
                border-radius: 0;
            }
        }

        .status-progress .circle .label {
            display: inline-block;
            width: 32px;
            height: 32px;
            line-height: 24px;
            border-radius: 32px;
            margin-top: 3px;
            color: #b5b5ba;
            font-size: 17px;
        }

        .status-progress .circle .title {
            color: #b5b5ba;
            font-size: 13px;
            line-height: 20px;
            margin-left: -5px;
            display: flex;
            margin-top: 30px;
            font-weight: 600;
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
            box-shadow: inset 0 0 2px rgba(0, 0, 0, .2);
        }

        .status-progress .circle.done .title {
            color: #444;
        }

        .status-progress .circle.active .label {
            color: #FFF;
            background: #0c95be;
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
       @media (min-width: 360px) and (max-width: 575.98px) { 
        .form-subscribe {
      padding-left: 55px;
     }
       .section-subscribe .form-subscribe {
    	padding-left: 5px;
        }
     .status-progress .circle .title {
      font-size: 11px;
        }
        .status-progress {
    width: 100%;
    margin: 20px auto;
    text-align: center;
    display: flex;
    flex-wrap: wrap;
    row-gap: 109px;
}
.status-progress .bar {
    position: relative;
    width: 42px;
    height: 9px;
    top: 16px;
 
}
      }
    </style>
</head>

<body>

    <?php include "includes/header.php" ?>


    <div class="section-title parallax-bg parallax-light">
        <ul class="bg-slideshow">
            <li>
                <div style="background-image:url(assets/media/bg/about-us.jpg)" class="bg-slide"></div>
            </li>
        </ul>
        <div class="section__inner">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <h1 class="ui-title-page">TRACK YOUR ORDER</h1>
                        <div class="ui-subtitle-page">Enter a tracking number, and get tracking results.</div>
                        <div class="decor-2 decor-2_mod-a decor-2_mod_white"></div>
                    </div><!-- end col -->
                </div><!-- end row -->
            </div><!-- end container -->
        </div><!-- end section__inner -->
    </div><!-- end section-title -->
    <br />
    <section>
        <div class="container-fluid">
            <div class="row">

                <div class="container-fluid">
                    <section class="section-form-request">
                        <div class="row">
                            <div class="clearfix trackcontainer">

                                <form class="form-subscribe" method="post">
                                    <input class=" form-control track-orderme" value="GEO/" type="hidden" style=" width: 60px;padding:  9px;" disabled />
                                    <input class=" form-control track-orderme" type="text" placeholder="Enter your GRN No" name="grn_no" id="grn_no" value="<?php echo $grn_no; ?>" required autocomplete="off" />

                                    <!-- <input class=" form-control track-orderme" type="text" placeholder="Party Invoice "  name= "party_invoice_no" required  value="<?php //echo $party_invoice_no; 
                                                                                                                                                                        ?>" /> -->
                                    <input class=" form-control track-orderme" type="text" placeholder="Registered Phone No " name="phone_no" required value="<?php echo isset($_POST["phone_no"]) ? $_POST["phone_no"] : ""; ?>" minlength=10  maxlength=10 onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" />

                                    <button type="submit" class="form-subscribe__btn btn btn_mod-c btn-sm btn-effect" name="submit"><span class="btn__inner">Track Now</span></button>
                                </form>

                            </div>
                        </div>
                    </section>


                    <?php
                    require_once 'web/include/connect.php';
                    require_once 'web/include/function.php';
                    if (isset($_POST['submit'])) {
                        //if($grn_no!="" &&  $party_invoice_no!="")
                           $contact_no = $_POST['phone_no'];
                          
                        if ($grn_no != "" &&  $contact_no != "") {
                            $phone_num = $_POST['phone_no'];
                            $ind_code = "+91";
                            if(strpos($contact_no,$ind_code)!==false){
                                $contact_no;
                            }else{
                            $contact_no1 = $ind_code.$phone_num;
                            }
                            $qd = "select client_id from client where contact_no ='$contact_no' or contact_no = '$contact_no1'";
                            $get_client_id = mysqli_query($conn, $qd);
                            $res_client = mysqli_fetch_assoc($get_client_id);
                            $client_identity = $res_client['client_id'];
                            // exit();
                            $grn_no = $grn_no;
                            $count = 1;
                            $tbl = $tbl_inv = '';
                            $query2 = "SELECT * FROM transaction_tbls";
                            $result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
                            while ($row2 = mysqli_fetch_assoc($result2)) {
                                $tbl = "transaction_" . $row2['table_name'];
                                $tbl_inv = "transaction_invoice_" . $row2['table_name'];
                                $tbl = rtrim($tbl, ",");
                                $tbl_inv = rtrim($tbl_inv, ",");
                                $query = "select * from $tbl where grn_no= '$grn_no' and consignee = '$client_identity' and booking_status = ''  and transaction_id IN (select transaction_id from  $tbl_inv)";
                                $result = mysqli_query($conn, $query);
                                $grnr = mysqli_fetch_array($result);

                                if (mysqli_num_rows($result) > 0) {
                                    $count++;

                                    echo ' <div class="mt-5 mb-5 text-center">
                                            <h2>Tracking Status</h2>
                                          </div>
                                          <br/>';
                                    extract($grnr);

                                    $status = array();
                                    array_push($status, 1);
                                    $query = "select * from transaction_status_log where grn_no='$grn_no'";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_array($result)) {

                                        if (!in_array($row['from_status'], $status))
                                            array_push($status, $row['from_status']);

                                        if (!in_array($row['to_status'], $status))
                                            array_push($status, $row['to_status']);
                                    }
                                    $count = 1;
                                    $max = max($status);
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

                                    $query3 = "select sum(no_of_pkge) as no_of_pkge from  $tbl_inv where transaction_id='$transaction_id'";
                                    $result3 = mysqli_query($conn, $query3);
                                    $row3 = mysqli_fetch_array($result3);

                    ?>
                                    <br><br><br><br><br><br>
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
                                                        <td><?php echo get_client_name($conn, $consigner); ?></td>
                                                        <td>Mode :</td>
                                                        <td>
                                                            <?php echo $mode = get_mode($conn, $mode_of_transportation);
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
                                                        <td><?php echo get_client_name($conn, $consignee); ?></td>
                                                        <td>No Of Pkg :</td>
                                                        <td><?php echo $row3['no_of_pkge']; ?></td>
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
                                    <!-- <div class="row col-md-5 col-md-offset-3 custyle">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <td>GRN No.</td>
                                                    <td> <?php //echo $grn_no; 
                                                            ?></td>
                                                </tr>
                                                <tr>
                                                    <td>GRN Date</td>
                                                    <td><?php //echo $grn_date; 
                                                        ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Consignor</td>
                                                    <td><?php //echo get_client_name($conn, $consigner); 
                                                        ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Consignee</td>
                                                    <td><?php //echo get_client_name($conn, $consignee); 
                                                        ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Mode of Transport</td>
                                                    <td><?php //echo get_mode($conn, $mode_of_transportation); 
                                                        ?></td>
                                                </tr>
                                                <tr>
                                                    <td>No. of Packages</td>
                                                    <td><?php //echo $row3['no_of_pkge']; 
                                                        ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Payment Mode</td>
                                                    <td><?php //echo consignment_mode($conn, $mode_of_consignment); 
                                                        ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Status</td>
                                                    <td><?php //echo get_trans_status($max); 
                                                        ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div> -->

                    <?php }
                            }
                            if ($count == 1)
                                echo '<p class="text-center text-danger">Incorrect GRN No or Contact No or Booking Cancelled. Please check and try again!</p> ';
                        }
                    }
                    ?>
                </div>
            </div>

        </div>

        </div>
    </section>
    <br />
    <br /><br />
    <br />
    <br /><br />
    <br />
    <br />

    <section class="section-bg">
        <div class="parallax-bg parallax-primary">
            <ul class="bg-slideshow">
                <li>
                    <div style="background-image:url(assets/media/bg/bg-7.jpg)" class="bg-slide"></div>
                </li>
            </ul>
        </div>
        <div class="section__inner">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="block-download clearfix">
                            <div class="block-download__inner">
                                <h2 class="block-download__title">If You Need Any Information.. We are Available For You</h2>

                            </div>
                            <div class="block-download__btn">
                                <a class="btn btn_mod-c btn-sm btn-effect" href="contact.php"><span class="btn__inner">GET A QUOTE</span></a>
                            </div>
                            <i class="block-download__icon icon"><i style="font-size: 30px;" class="fa fa-file" aria-hidden="true"></i></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="section-clients section-bg_mod-a wow">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">

                    <div class="carusel-clients slider_mod-a owl-carousel owl-theme enable-owl-carousel" data-min480="1" data-min768="4" data-min992="4" data-min1200="4" data-pagination="false" data-navigation="false" data-auto-play="4000" data-stop-on-hover="true">

                        <a class="carusel-clients__item" href="https://www.havells.com/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/Havells-India.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>
                        <a class="carusel-clients__item" href="http://www.farida.co.in/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/Farida-Group.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>
                        <a class="carusel-clients__item" href="https://arvindbrands.com/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/arvind-lifestyle.jpg" alt="logo">
                            <span class="helper-2"></span></a>
                        <a class="carusel-clients__item" href="http://www.hflgoa.com/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/Hindustan-Foods-Ltd.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>
                        <a class="carusel-clients__item" href="http://kljgroup.com/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/KLJ-Polymers.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>
                        <a class="carusel-clients__item" href="home.html" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/Mahendra-Mahendra.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>
                        <a class="carusel-clients__item" href="https://www.paragonfootwear.com/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/Paragon-Polymers.jpg" alt="logo">
                            <span class="helper-2"></span></a>
                        <a class="carusel-clients__item" href="http://ranegroup.com/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/rane.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>
                        <a class="carusel-clients__item" href="http://www.saragroup.in/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/Sara-suole.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>
                        <a class="carusel-clients__item" href="http://tatainternational.com/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/tata-international.jpg" alt="logo">
                            <span class="helper-2"></span></a>
                        <a class="carusel-clients__item" href="http://www.vivagroupindia.com/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/vivabooks.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>
                        <a class="carusel-clients__item" href="http://www.wilhelmindia.co.in/" target="_blank">
                            <img class="carusel-clients__img" src="assets/media/clients/Wilhelm-Textiles.jpg" alt="logo">
                            <span class="helper-2"></span>
                        </a>

                    </div><!-- end  -->
                </div><!-- end col -->
            </div><!-- end row -->
        </div><!-- end container -->
    </div><!-- end section-clients -->



    <?php include 'includes/footer.php'; ?>

    </div>
    <!-- end layout-theme -->


    <!-- SCRIPTS MAIN -->

    <script src="assets/js/jquery-migrate-1.2.1.js"></script>
    <script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/waypoints.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>
    <script src="assets/js/cssua.min.js"></script>


    <!--SCRIPTS THEME-->

    <!-- Home slider -->
    <script src="assets/plugins/slider-pro/dist/js/jquery.sliderPro.js"></script>
    <!-- Sliders -->
    <script src="assets/plugins/owl-carousel/owl.carousel.min.js"></script>

    <script src="assets/plugins/flexslider/jquery.flexslider.js"></script>
    <!-- Modal -->
    <script src="assets/plugins/prettyphoto/js/jquery.prettyPhoto.js"></script>
    <!-- Select customization -->
    <script src="assets/plugins/bootstrap-select/dist/js/bootstrap-select.js"></script>
    <!-- Chart -->
    <script src="assets/plugins/rendro-easy-pie-chart/dist/jquery.easypiechart.min.js"></script>
    <!-- Animation -->
    <script src="assets/plugins/scrollreveal/dist/scrollreveal.min.js"></script>
    <!-- Menu for android-->
    <script src="assets/js/doubletaptogo.js"></script>

    <!-- Custom -->
    <script src="assets/js/custom.js"></script>


    <!--<script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript"></script> -->
    <script>
        var i = 1;
        var j = '<?php echo $count; ?>';
        $('.status-progress .circle').removeClass().addClass('circle');
        $('.status-progress .bar').removeClass().addClass('bar');
        setInterval(function() {
            if (i <= j) {
                $('.status-progress .circle:nth-of-type(' + i + ')').addClass('active');
                $('.status-progress .circle:nth-of-type(' + i + ') .label').html('&#x2708;');
                $('.status-progress .circle:nth-of-type(' + (i - 1) + ')').removeClass('active').addClass('done');

                $('.status-progress .circle:nth-of-type(' + (i - 1) + ') .label').html('&#10003;');

                $('.status-progress .bar:nth-of-type(' + (i - 1) + ')').addClass('active');

                $('.status-progress .bar:nth-of-type(' + (i - 2) + ')').removeClass('active').addClass('done');

                i++;
            }

        }, 1000);




        /*	$('#grn_no').keypress(function (event) {
        			return isNumber(event, this)
        		});*/


        function isNumber(evt, element) {
            var charCode = (evt.which) ? evt.which : event.keyCode

            if ((charCode != 45 || $(element).val().indexOf('-') != -1) && // “-” CHECK MINUS, AND ONLY ONE.
                (charCode != 46 || $(element).val().indexOf('.') != -1) && // “.” CHECK DOT, AND ONLY ONE.
                (charCode < 48 || charCode > 57))
                return false;
            return true;
        }
    </script>

</body>

</html>