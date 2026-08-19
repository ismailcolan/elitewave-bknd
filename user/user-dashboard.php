<?php
include("../config.ini.php");
if (session_id() == '') {
    session_start();
}

include_once('include/user-function.php');

// $conn = mysqli_connect("localhost", "staging", "vySzrpsqDRupDHS", "staging");

$select_user_email = mysqli_query($conn, "select *from users where user_id ='" . $_SESSION['user_id'] . "'");
$get_user_data = mysqli_fetch_assoc($select_user_email);
$user_email = $get_user_data['email'];
// var_dump($user_email);
$ddd = "select *from client where email ='$user_email'";
// exit();
$select_client_id =  mysqli_query($conn,$ddd);
if(mysqli_num_rows($select_client_id) > 0){
$get_client_data = mysqli_fetch_assoc($select_client_id);
$client_id = $get_client_data['client_id'];
}else{
   $client_id = "-1";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <?php include("include/title.php"); ?>
    <?php include("include/css_js_forgetpassword.php"); ?>

    <!-- <title>Gracious Express - Book Consignment</title> -->
    <link href="../assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
    <link href="assets/css/master.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" />
    <link href="stylesheets/datatables.css" media="all" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">

    <!-- book consignment css and js starts here -->
    <link rel="stylesheet" href="assets/css/book-consignment.css">
    <link rel="stylesheet" href="f5/fontawesome.min.css">
    <!-- book consignment css and js finished here -->
    <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>
</head>
<style>
.pending-consignee th {
    padding: 18px 20px 10px 6px !important;
}
    #piechart {
        width: 900px;
        height: 350px;
    }

    #chartContainer {
        height: 350px;
        width: 100%;
    }

    .my-custom-scrollbar {
        position: relative;
        height: 440px;
        overflow: auto;
    }

    .table-wrapper-scroll-y {
        display: block;
    }

    .btn-viewmore {
        float: right;
    }

    div#chartContainer {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    #chartContainer canvas.canvasjs-chart-canvas {
        width: 96%;
    }

    @media only screen and (max-width: 600px) {
        .ds-white-cover {
            margin-bottom: 44px;
        }

        .graciouss-card {
            width: 92%;
        }

        .g_detail p {
            font-size: 14px;
        }

        .g_detail h2 {
            font-size: 29px;
        }

        .gra_pending {
            margin-top: 18px;
            padding-right: 22px;
        }

        a.gra_btn {

            font-size: 11px;
            padding: 5px 10px;

        }
    }

    @media (min-width: 360px) and (max-width: 576.98px) {

        div#user_piechart {
            margin-bottom: 37px;
        }

        #piechart {
            width: 900px;

            height: 196px;
        }

        svg:not(:root) {
            overflow: hidden;
            height: 232px;
        }

        .ds-white-cover {
            margin-bottom: 21px;
        }

        #chartContainer {

            width: 100%;
        }

        .gra_pending {
            padding-left: 15px;
            padding-right: 15px;

        }
    }
 @media (min-width: 320px) and (max-width:575.98px) { 
        .pending-consignee .table thead th {
    font-size: 10px;
    padding: 10px 3px !important;
}
.dash-summ{
    width: 100%;
    overflow-x: auto!important;   
}
.dash_table{
    margin: 0 auto;
    width: max-content!important;
    max-width: unset!important;
    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}
      }
</style>

<body>
    <?php
    // error_reporting(1);
    $month = date('m-Y');
    //$con = mysqli_connect('localhost','root','','bookconsignment');
    $booked = 0;
    $booking_of_month = 0;
    $pending = 0;
    $mapping = 0;
    $select_user_email = mysqli_query($conn, "select *from users where user_id ='" . $_SESSION['user_id'] . "'");
    $get_user_data = mysqli_fetch_assoc($select_user_email);
    $user_email = $get_user_data['email'];
    // var_dump($user_email);
    // echo $d = "select *from client where email ='$user_email'";
    // exit();
    // $select_client_id =  mysqli_query($conn,$d );
    // $get_client_data = mysqli_fetch_assoc($select_client_id);
    // $client_id = $get_client_data['client_id'] ? $get_client_data['client_id'] : 00;
    $query1 = mysqli_query($conn, "select *from transaction_tbls");
    while ($result_query1 = mysqli_fetch_assoc($query1)) {

        $query2 = mysqli_query($conn, "select transaction_id from transaction_" . $result_query1['table_name'] . " where consigner='$client_id' ");
        $booked += mysqli_num_rows($query2);
        $query3 = mysqli_query($conn, "select transaction_id from transaction_" . $result_query1['table_name'] . " where consigner='$client_id' and grn_date like '%$month' ");
        $booking_of_month += mysqli_num_rows($query3);
        $query4 = mysqli_query($conn, "select transaction_id from transaction_" . $result_query1['table_name'] . " where consigner='$client_id' and status != '8' ");
        $pending += mysqli_num_rows($query4);
    }
    $query_map = mysqli_query($conn, "select count(mapping_id) as mapping_count from customer_mapping_lists where mapping_id IN (select mapping_id from customer_mapping where client = '$client_id') ");
    $mapping_data = mysqli_fetch_assoc($query_map);
    $mapping += $mapping_data['mapping_count'];
    $dd2 = "SELECT * FROM `client_outstanding` where client_id ='$client_id'";
    //exit();
    $query_outstanding = mysqli_query($conn,$dd2 );
    if(mysqli_num_rows($query_outstanding) > 0){
        $outstanding_data = mysqli_fetch_assoc($query_outstanding);
        $payment_due = $outstanding_data['balance'];
    }else{
        $payment_due = 0;
    }
    $_SESSION['payment_due'] = $payment_due;
   
    $key = md5($client_id);
    ?>

    <div class="user-dashboard">
        <?php include "user-db-header.php" ?>

        <div class="dashboard-summary col-sm-12">
            <div class="ds-white-cover ">
                <h4 class="text-center">Summary</h4>
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 ">
                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="ds-sub-box">
                                <div class="ds-icon bg-success">
                                    <i class="fa fa-calendar-check-o fa-2x" aria-hidden="true"></i>
                                </div>
                                <div class="ds-msg">
                                    <h5>Booking Of The Month</h5>
                                    <p><?php echo $booking_of_month; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="ds-sub-box">
                                <div class="ds-icon bg-warning">
                                    <i class="fa fa-globe fa-2x" aria-hidden="true"></i>
                                </div>
                                <div class="ds-msg">
                                    <h5>Total Bookings&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h5>
                                    <p><?php echo $booked; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="ds-sub-box">
                                <div class="ds-icon bg-danger">
                                    <i class="fa fa-ship fa-2x" aria-hidden="true"></i>
                                </div>
                                <div class="ds-msg">
                                    <h5>Pending Deliveries</h5>
                                    <p><?php echo $pending; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="ds-sub-box">
                                <div class="ds-icon bg-info" style="padding-right : 15px !important; padding-left : 15px !important;">
                                    <i class="fa fa-opera fa-2x" aria-hidden="true"></i>
                                </div>
                                <div class="ds-msg">
                                    <h5>Consignees Mapped</h5>
                                    <p><?php echo $mapping; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="gra_ca">
                            <div class="graciouss-card">
                                <div class="gra">
                                    <div class="gra_first">
                                        <div class="g_detail">
                                            <p>Payment Due</p>
                                            <h2>&#x20b9;<?php echo $payment_due; ?></h2>
                                            <br>
                                            
                                            <!-- <p>Due date : Sep 5 2022</p> -->
                                            <!-- <a class="gra_btn btn11">Pay Now</a> -->
                                            <a class="gra_btn btn11" href="view_transactions.php?key=<?php echo $key; ?>">View Transactions</a>
                                        </div>
                                    </div>

                                    <div class="gra_sec">
                                        <img src="http://localhost/graciousexpress/user/assets/img/rupee.png">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php
        $Y = date('Y');
        $Month = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

        $datapoints1 = array();
        $datapoints2 = array();
        $dataname1 = "Delivered";
        $dataname2 = "Pending";
        $Dates = date('d-m-Y');
        for ($i = 1; $i < 12; $i++) {
            //global $con;
            $Date = sprintf('%02d', $i) . '-' . $Y;
            $fulldate = "01-" . $Date;
            $table1 = get_trans_table($conn, $fulldate);
            $username = $_SESSION['user_id'];

            $users = mysqli_query($conn, "select *from users where user_id = '$username'");
            $user_email = mysqli_fetch_assoc($users);
            $email = $user_email['email'];

            // $query_client = mysqli_query($conn, "select *from client where email = '$email'");
            // $query_client_id = mysqli_fetch_assoc($query_client);
            // $client_id = $query_client_id['client_id'];

            $query_bar_chart = "select count(grn_no) as count from $table1[0] where consigner='$client_id' and grn_date like '%$Date' and status ='8' ";
            $result = mysqli_query($conn, $query_bar_chart);
            $row = mysqli_fetch_array($result);

            $count = $row['count'];
            array_push($datapoints1, array("label" => $Month[$i - 1] . " " . $Y, "y" => $count));
        }

        for ($i = 1; $i < 12; $i++) {
            //global $con;
            $Date = sprintf('%02d', $i) . '-' . $Y;
            $fulldate = "01-" . $Date;
            $table1 = get_trans_table($conn, $fulldate);
            $query_bar_chart = "select count(grn_no) as count from $table1[0] where consigner='$client_id' and grn_date like '%$Date' and status ='1' ";

            $result = mysqli_query($conn, $query_bar_chart);
            $row = mysqli_fetch_array($result);

            $count = $row['count'];
            //var_dump($count);
            array_push($datapoints2, array("label" => $Month[$i - 1] . " " . $Y, "y" => $count));
        }

        ?>
        <div class="dashboard-charts">
            <div class="col-sm-12">
                <div class="col-sm-6 text-center">
                    <div class="dc-chart" id="user_piechart">
                        <div id="piechart"></div>
                    </div>
                </div>
                <div class="col-sm-6 text-center">
                    <div class="dc-chart">
                        <div id="chartContainer"></div>
                    </div>
                </div>


            </div>
        </div>

        <div class="pending-consignee col-sm-12 table-wrapper-scroll-y my-custom-scrollbar gra_pending">

            <div class="ds-white-cover dash-summ">
                <div class="pc-white-cover">
                    <h4 class="text-center">Pending Consignment</h4>
                    <div></div>
                    <div class=" btn-viewmore">
                        <a href="booking_list.php" class="btn btn-info btn-sm">View More</a>
                    </div>
                    <table class="table no-wrap dash_table">
                        <thead>
                            <tr>
                                <th class="border-top-0">S.No</th>
                                <th class="border-top-0">GRN No</th>
                                <th class="border-top-0">GRN Date</th>
                                <th class="border-top-0">Consignee</th>
                                <th class="border-top-0">Destination</th>
                                <th class="border-top-0">Package</th>
                                <th class="border-top-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $date = date('d-m-Y');
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

                            //var_dump($client_id);

                            $query = "select *from transaction_" . $m1 . "_" . $dt[2] . " where consigner ='$client_id' order by transaction_id desc LIMIT 10";
                            $query_result = mysqli_query($conn, $query);
                            $i = 1;

                            while ($user_booking = mysqli_fetch_assoc($query_result)) {
                                $query_invoice = "select sum(no_of_pkge) as package from transaction_invoice_" . $m1 . "_" . $dt[2] . " where transaction_id ='" . $user_booking['transaction_id'] . "' ";
                                $query_invoice_result = mysqli_query($conn, $query_invoice);
                                $package_details = mysqli_fetch_assoc($query_invoice_result);
                            ?>

                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo $user_booking['grn_no']; ?></td>
                                    <td><?php echo $user_booking['grn_date']; ?></td>
                                    <td><?php echo get_client_name($conn, $user_booking['consignee']); ?></td>
                                    <!-- <td><?php //echo $user_booking['consignee'];
                                                ?></td> -->
                                    <td><?php echo get_city_name($conn, $user_booking['destination']); ?></td>
                                    <td><?php echo $package_details['package']; ?></td>
                                    <td><?php echo get_trans_status($user_booking['status']); ?></td>
                                </tr>
                            <?php
                                $i++;
                            }
                            ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>


    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {
            'packages': ['corechart']
        });
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {

            var data = google.visualization.arrayToDataTable([
                ['Task', 'Hours per Day'],
                ['Consignee Mapped', <?php echo $mapping; ?>],
                ['Pending Deliveries', <?php echo $pending; ?>],
                ['Total Bookings', <?php echo $booked; ?>],
                //   ['Watch TV', 2],
                ['Booking of the Month', <?php echo $booking_of_month; ?>]
            ]);

            var options = {
                title: 'Over all Statistics',
                chartArea: {
                    left: "5%",
                    top: "20%",
                    right: "5%",
                    width: "100%"
                }
            };

            var chart = new google.visualization.PieChart(document.getElementById('piechart'));

            chart.draw(data, options);
        }
    </script>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <script>
        window.onload = function() {
            var year = '<?php echo date('Y'); ?>';
            var chart = new CanvasJS.Chart("chartContainer", {
                title: {
                    text: year + " Year Summary"
                },
                theme: "light2",
                animationEnabled: true,
                toolTip: {
                    shared: true,
                    reversed: true
                },
                axisX: {
                    interval: 1,
                    labelAngle: 0
                },

                data: [{
                    type: "stackedColumn",
                    name: "<?php echo $dataname1; ?>",
                    showInLegend: true,
                    //yValueFormatString: "$#,##0 K",
                    dataPoints: <?php echo json_encode($datapoints1, JSON_NUMERIC_CHECK); ?>

                }, {
                    type: "stackedColumn",
                    name: "<?php echo $dataname2; ?>",
                    showInLegend: true,
                    //yValueFormatString: "$#,##0 K",
                    dataPoints: <?php echo json_encode($datapoints2, JSON_NUMERIC_CHECK); ?>
                }]
            });

            chart.render();

        }
    </script>


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
    <!-- SCRIPTS MAIN -->




    <script type="text/javascript">
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
        $(window).load(function() {
            $(".loading-page").hide();
        });
    </script>

    <?php include_once('include/user-footer-js.php'); ?>
</body>

</html>