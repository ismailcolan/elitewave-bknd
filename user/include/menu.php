<?php
//require_once("connect.php");
//require_once("function.php"); 
?>
<div class="container-fluid main-nav clearfix">
<ul class="nav navbar-nav admin_login">
	
	  <li class="dropdown user hidden-xs login_mobile_view"><a data-toggle="dropdown" class="dropdown-toggle" href="#">
		<img width="34" height="34" src="images/no_profile.png" /><?php //echo $username; ?><b class="caret"></b></a>
		<ul class="dropdown-menu">
		  <li><a href="change_password.php"><i class="fa fa-user"></i>Change Password</a></li>
		  <li><a href="logout.php"> <i class="fa fa-sign-out"></i>Logout</a>
		  </li>
		</ul>
	  </li>
	</ul>
  <div class="nav-collapse">
	<ul class="nav">
	<li>
		<a class="" href="dashboard.php"><img src="./images/dashboard.png" class="hightop-home menu_icon1">Dashboard</a>
	</li>
	<?php 
		//if($_SESSION['role'] == "AD"){
	?>
	  <li class="dropdown"><a data-toggle="dropdown" href="#">
		<img src="./images/master2.png" class="hightop-home menu_icon">Master<b class="caret"></b></a>
		<ul class="dropdown-menu">
			<li><a href="company.php">Company</a></li>
			<li><a href="branch.php">Branch</a></li>
			<li><a href="state.php">State</a></li>
			<li><a href="city.php">City</a></li>
			<li><a href="hub.php">Hub</a></li>
			<li><a href="mode_of_transportation.php">Mode Of Transportation</a></li>
			<li><a href="client.php">Client</a></li>
			<li><a href="client_branch.php">Client Branch</a></li>
                        <!--<li><a href="role.php">Role</a></li>-->
			<li><a href="users.php">User</a></li>
			<li><a href="consignment_mode.php">Consignment Mode</a></li>
			<li><a href="package_type.php">Package Type</a></li>
			<li><a href="vehicle.php">Vehicle</a></li>
			<!--<li><a href="delivery_status.php">Delivery Status</a></li>-->
                        <li><a href="train.php">Train</a></li>
			<li><a href="flight.php">Flight</a></li>
		</ul>
	  </li>
	<?php 
		//}
		
	?>
	
	  <li class="dropdown"><a data-toggle="dropdown" href="#">
		<img src="./icons/009-truck-2.png" class="hightop-home menu_icon">Transactions<b class="caret"></b></a>
		<ul class="dropdown-menu">
			<li><a href="transactions.php">Book a Consignment</a></li>
				<li><a href="transaction_list.php">List of Consignments</a></li>
<li><a href="request_for_new_pickup.php">Request For Pickup</a></li>
<li><a href="track_consignment.php">Track Consignment</a></li>
<?php 
		//if($_SESSION['role'] == "AD" || $_SESSION['role']=="USER"){
	?>
<li><a href="status_sheet.php">Consignment Status Sheet</a></li>

<?php 
		//}
		
	?>			
		</ul>
	  </li>
	   <li class="dropdown"><a data-toggle="dropdown" href="#">
		<img src="./images/master2.png" class="hightop-home menu_icon">MIS Report<b class="caret"></b></a>
		<ul class="dropdown-menu">
		<?php 
		//	if($_SESSION['role'] == "CL"){
		?>
			<li><a href="consignment_report.php">My Booking Report</a></li>
			<li><a href="client_arrival_report.php">My Arrival Report</a></li>
		<?php
			// }
			// else{
		?>
			<li><a href="consignment_report.php">Booking Report</a></li>
		<?php 
		//}
		?>
		</ul>
	  </li>
	  
	  <?php 
		//if($_SESSION['role'] == "AD" || $_SESSION['role']=="USER"){
	?>
	  <li class="dropdown"><a data-toggle="dropdown" href="#">
		<img src="./icons/008-box-2.png" class="hightop-home menu_icon">Customer Mapping<b class="caret"></b></a>
		<ul class="dropdown-menu">
			<li><a href="customer_mapping.php">Customer Mapping</a></li>
			
		</ul>
	  </li>
	  <?php 
		//}
		?>
<label class="date-container">Today :<?php echo date("d-m-Y"); ?></label>
	</ul>
  </div>
</div>