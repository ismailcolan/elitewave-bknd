<?php
if(session_id() == '') {
    session_start();
}
if(isset($_SESSION['otp']) != ''){
    echo '<script> location.href="verify_otp.php"; </script>';
    exit();
 }
?>
<div class="navbar navbar-fixed-top scroll-hide" style="height : 50px">
        <?php 
			require_once("include/header.php");
		 ?>
      
	</div>
        
        <div class="dashboard-header dashboard-boxes col-xs-12 col-sm-12 col-md-12 col-lg-12" style="margin-top : 65px">
            <div class="col-sm-4 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-info" href="user-book-consignment.php">
                    <h5 class="box-title">Book A Consignment</h5>
                    <i class="fa fa-newspaper-o fa-3x" aria-hidden="true"></i>
                    </ul>
                </a>
            </div>
            <div class="col-sm-4 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-success" href="user-track-consignment.php" >
                    <h5 class="box-title">Track A Consignment</h5>
                    <i class="fa fa-laptop fa-3x" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-sm-4 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-warning" href="request_pickup.php">
                    <h5 class="box-title">Request Pickup</h5>
                    <i class="fa fa-truck fa-3x" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-sm-6 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-danger" href="booking_list.php">
                    <h5 class="box-title">My Bookings</h5>
                    <i class="fa fa-list-ul fa-3x" aria-hidden="true"></i>
                </a>
            </div>
            <div class="col-sm-6 col-lg-2 custom-col">
                <a class="white-box analytics-info bg-danger" href="user-dashboard.php">
                    <h5 class="box-title">Summary Dashboard</h5>
                    <i class="fa fa-tachometer fa-3x" aria-hidden="true"></i>
                </a>
            </div>
        </div>