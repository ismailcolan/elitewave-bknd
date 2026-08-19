<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
		<title>Gracious Express - Book Consignment</title>

		<link href="favicon.png" type="image/x-icon" rel="shortcut icon">
		<link href="assets/css/master.css" rel="stylesheet">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">
		<!-- book consignment css and js starts here -->
		<link rel="stylesheet" href="assets/css/book-consignment.css">
		<link rel="stylesheet" href="assets/fonts/font-awesome-4.5.0/css/font-awesome.min.css">
		<!-- book consignment css and js finished here -->
		<script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
		<script src="assets/js/modernizr.custom.js"></script>
	</head>


	<body style>
<?php
// error_reporting(1);
$con = mysqli_connect('localhost','root','','bookconsignment');
$booked = 0;
$arrivals = 0;

$Y = date('Y');
$Month = array("Jan","Feb", "Mar","Apr","May", "Jun","Jul","Aug","Sep","Oct","Nov","Dec");

$datapoints1 = array();
$datapoints2 = array();
$dataname1= "Booked";
$dataname2 = "Arrivals";
    $Dates = date('d-m-Y');
for($i=1;$i<12;$i++){
    global $con;
    $Date = sprintf('%02d',$i).'-'.$Y;
    $fulldate = "01-".$Date;
    $table_count = "SELECT count(booking_id) as booking from consignment where booking_date like '%$Date' and status = '0' ";
    $result = mysqli_query($con,$table_count);
    $row = mysqli_fetch_array($result);
    
    $count = $row['booking'];
    array_push($datapoints1,array("label"=>$Month[$i-1].'-'.$Y , "y"=>$count));  
}

for($i=1;$i<12;$i++){
    global $con;
    $Date = sprintf('%02d',$i).'-'.$Y;
    $fulldate = "01-".$Date;
    $table_count = mysqli_query($con,"SELECT count(booking_id) as booking from consignment where booking_date LIKE '%$Date' and status = '1' ");
    $row = mysqli_fetch_array($table_count);
    $count = $row['booking'];
    array_push($datapoints2,array("label"=>$Month[$i-1].'-'.$Y , "y"=>$count));  
}
?>

    <div class="user-dashboard">

		<div class="fixed-navigation">

        </div>

        <div class="dashboard-header dashboard-boxes col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="col-sm-4 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-info" href="#">
                    <h5 class="box-title">Book A Consignment</h5>
                    <i class="fa fa-newspaper-o fa-3x" aria-hidden="true"></i>
                    </ul>
                </a>
            </div>
            <div class="col-sm-4 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-success" href="#" >
                    <h5 class="box-title">Track A Consignment</h5>
                    <i class="fa fa-laptop fa-3x" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-sm-4 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-warning" href="#">
                    <h5 class="box-title">Request Pickup</h5>
                    <i class="fa fa-motorcycle fa-3x" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-sm-6 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-danger">
                    <h5 class="box-title">My Bookings</h5>
                    <i class="fa fa-list-ul fa-3x" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-sm-6 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-danger">
                    <h5 class="box-title">Summary Dashboard</h5>
                    <i class="fa fa-tachometer fa-3x" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <div class="dashboard-summary col-sm-12">
            <div class="ds-white-cover ">
                <h4 class="text-center">Summary</h4>

                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="ds-sub-box">
                        <div class="ds-icon bg-success">
                            <i class="fa fa-calendar-check-o fa-2x" aria-hidden="true"></i>
                        </div>
                        <div class="ds-msg">
                            <h6>Booking Of The Month</h6>
                            <p>16</p>
                        </div>
                    </div>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="ds-sub-box">
                        <div class="ds-icon bg-warning">
                            <i class="fa fa-globe fa-2x" aria-hidden="true"></i>
                        </div>
                        <div class="ds-msg">
                            <h6>Total Bookings</h6>
                            <p>326</p>
                        </div>
                    </div>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="ds-sub-box">
                        <div class="ds-icon bg-danger">
                            <i class="fa fa-ship fa-2x" aria-hidden="true"></i>
                        </div>
                        <div class="ds-msg">
                            <h6>Pending Deliveries</h6>
                            <p>0</p>
                        </div>
                    </div>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="ds-sub-box">
                        <div class="ds-icon bg-info" style="padding-right : 15px !important; padding-left : 15px !important;">
                            <i class="fa fa-opera fa-2x" aria-hidden="true"></i>
                        </div>
                        <div class="ds-msg">
                            <h6>Consignees Mapped</h6>
                            <p>37</p>
                        </div>
                    </div>
                </div>
              
              
            </div>
        </div>

        <div class="dashboard-charts">
            <div class="col-sm-12">
                <div class="col-sm-6 text-center">
                    <div class="dc-chart">
                    <div id="piechart" style="width: 900px; height: 350px;"></div>
                    </div>
                </div>
                <div class="col-sm-6 text-center">
                    <div class="dc-chart">
                    <div id="chartContainer" style="height: 350px; width: 100%;"></div>
                    </div>
                </div>
                
                
            </div>
        </div>
        
        <div class="pending-consignee col-sm-12">
            <div class="ds-white-cover">
                <div class="pc-white-cover">
                     <h4 class="text-center">Pending Consignee</h4>
                    <table class="table no-wrap">
                        <thead>
                            <tr>
                                <th class="border-top-0">#</th>
                                <th class="border-top-0">GRN No</th>
                                <th class="border-top-0">GRN Date</th>
                                <th class="border-top-0">Consignee</th>
                                <th class="border-top-0">Package</th>
                                <th class="border-top-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>SALE</td>
                                <td class="txt-oflo">April 18, 2021</td>
                                <td class="txt-oflo">Elite admin</td>
                                <td><span class="text-success">$24</span></td>
                                <td><span class="text-success">Delievered</span></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>EXTENDED</td>
                                <td class="txt-oflo">April 19, 2021</td>
                                <td class="txt-oflo">Real Homes WP Theme</td>
                                <td><span class="text-info">$1250</span></td>
                                <td><span class="text-success">Delievered</span></td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>EXTENDED</td>
                                <td class="txt-oflo">April 19, 2021</td>
                                <td class="txt-oflo">Ample Admin</td>
                                <td><span class="text-info">$1250</span></td>
                                <td><span class="text-success">Delievered</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>TAX</td>
                                <td class="txt-oflo">April 20, 2021</td>
                                <td class="txt-oflo">Medical Pro WP Theme</td>
                                <td><span class="text-danger">-$24</span></td>
                                <td><span class="text-success">Delievered</span></td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>SALE</td>
                                <td class="txt-oflo">April 21, 2021</td>
                                <td class="txt-oflo">Hosting press html</td>
                                <td><span class="text-success">$24</span></td>
                                <td><span class="text-success">Delievered</span></td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>SALE</td>
                                <td class="txt-oflo">April 23, 2021</td>
                                <td class="txt-oflo">Digital Agency PSD</td>
                                <td><span class="text-danger">-$14</span></td>
                                <td><span class="text-success">On Processs</span></td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>MEMBER</td>
                                <td class="txt-oflo">April 22, 2021</td>
                                <td class="txt-oflo">Helping Hands WP Theme</td>
                                <td><span class="text-success">$64</span></td>
                                <td><span class="text-success">Shipping</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

			
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          ['Task', 'Hours per Day'],
          ['Work',     11],
          ['Eat',      2],
          ['Commute',  2],
          ['Watch TV', 2],
          ['Sleep',    7]
        ]);

        var options = {
          title: 'My Daily Activities'
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart'));

        chart.draw(data, options);
      }
    </script>		
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
<script>
window.onload = function () {
 var year ='<?php echo date('Y'); ?>';
var chart = new CanvasJS.Chart("chartContainer", {
	title: {
		text:  year+" Year Summary"
	},
	theme: "light2",
	animationEnabled: true,
	toolTip:{
		shared: true,
		reversed: true
	},
	
	data: [
		{
			type: "stackedColumn",
			name: "<?php echo $dataname1;?>",
			showInLegend: true,
			//yValueFormatString: "$#,##0 K",
			dataPoints: <?php echo json_encode($datapoints1,JSON_NUMERIC_CHECK); ?>
            
		},{
			type: "stackedColumn",
			name: "<?php echo $dataname2;?>",
			showInLegend: true,
			//yValueFormatString: "$#,##0 K",
			dataPoints: <?php echo json_encode($datapoints2,JSON_NUMERIC_CHECK); ?>
		}
	]
});
 
chart.render();
 
}
</script>
     
    
 <script>
              var wow = new WOW(
  {
    boxClass:     'wow',      // animated element css class (default is wow)
    animateClass: 'animated', // animation css class (default is animated)
    offset:       0,          // distance to the element when triggering the animation (default is 0)
    mobile:       true,       // trigger animations on mobile devices (default is true)
    live:         true,       // act on asynchronously loaded content (default is true)
    callback:     function(box) {
      // the callback is fired every time an animation is started
      // the argument that is passed in is the DOM node being animated
    },
    scrollContainer: null,    // optional scroll container selector, otherwise use window,
    resetAnimation: true,     // reset animation on end (default is true)
  }
);
wow.init();
              </script>
		<!-- SCRIPTS MAIN -->

		<script src="assets/js/jquery-migrate-1.2.1.js"></script>
		<script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>
		<script src="assets/js/waypoints.min.js"></script>
		<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
		<script src="assets/js/modernizr.custom.js"></script>
		<script src="assets/js/cssua.min.js"></script>


        <script type="text/javascript">
            $(document).ready(function(){
                if ($(window).width() < 992) {
                    $('.custom-col').removeClass('col-lg-2');
                } else {
                    $('.custom-col').addClass('col-lg-2');
                }
                $(window).resize(function () {
                    if ($(window).width() < 992) {
                        $('.custom-col').removeClass('col-lg-2');
                    } else {
                        $('.custom-col').addClass('col-lg-2');
                    }
                })
                
            })
        </script>

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
		<script src="assets/js/book-consignment.js"></script>
		<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
		
        

	</body>
</html>

