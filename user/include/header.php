<?php
if(session_id() == '') {
    session_start();
}
if(isset($_SESSION['LAST_ACTIVITY'])){
	if(empty($_SESSION['user_id']) && empty($_SESSION['LAST_ACTIVITY']) or (time() - $_SESSION['LAST_ACTIVITY'] > 103600)){
		echo '<script> location.href="../index.php"; </script>';
		exit;
	}
}
else if(!isset($_SESSION['user_id'])){
	echo '<script> location.href="../index.php"; </script>';
	exit;
}
else{
	echo '<script> location.href="../index.php"; </script>';
	exit;
}
require_once('user-function.php');
?>
<style>
span.notifications {
   
    color: #084d74 !important;
}
/** Navbar Mobile Device Css */
.g_menu {
		display: flex;
		flex-direction: row;
		list-style-type: none;
		margin: 0;
		padding: 0;
	}

	.g_menu-button-container {
		display: none;
		height: 100%;
		width: 30px;
		cursor: pointer;
		flex-direction: column;
		justify-content: center;
		align-items: center;
	}

	#g_menu-toggle {
		display: none;
	}

	.g_menu-button,
	.g_menu-button::before,
	.g_menu-button::after {
		display: block;
		background-color: #335bd7;
		position: absolute;
		height: 4px;
		width: 30px;
		transition: transform 400ms cubic-bezier(0.23, 1, 0.32, 1);
		border-radius: 2px;
	}

	.g_menu-button::before {
		content: '';
		margin-top: -8px;
	}

	.g_menu-button::after {
		content: '';
		margin-top: 8px;
	}

	#g_menu-toggle:checked+.g_menu-button-container .g_menu-button::before {
		margin-top: 0px;
		transform: rotate(405deg);
	}

	#g_menu-toggle:checked+.g_menu-button-container .g_menu-button {
		background: rgba(255, 255, 255, 0);
	}

	#g_menu-toggle:checked+.g_menu-button-container .g_menu-button::after {
		margin-top: 0px;
		transform: rotate(-405deg);
	}

	@media (max-width: 700px) {
		.g_menu-button-container {
			display: flex;
			position: absolute;
			top: 0;
			left: 20px;
		}

		.g_menu {
			position: absolute;
			top: 0;
			margin-top: 53px;
			left: 0;
			flex-direction: column;
			width: 100%;
			justify-content: center;
			align-items: center;
		}

		#g_menu-toggle~.g_menu li {
			height: 0;
			margin: 0;
			padding: 0;
			border: 0;
			transition: height 400ms cubic-bezier(0.23, 1, 0.32, 1);
		}

		#g_menu-toggle:checked~.g_menu li {
			border-top: 1px solid #c3bbbb;

			height: auto;
			padding: 0.5em;
			transition: height 400ms cubic-bezier(0.23, 1, 0.32, 1);
		}

		.g_menu>li {
			display: flex;
			justify-content: flex-start;
			margin: 0;
			padding: 0.5em 0;
			width: 100%;
			color: white;
			background-color: #fff;
			flex-direction: column;
		}

		.g_menu>li:not(:last-child) {
			border-bottom: 1px solid #444;
		}

		.pull-right {
			float: none !important;
			color: #FFF;
		}

		.gra_user {
			width: 100% !important;
		}

		.g_menu>li {
			margin: 0 1rem;
			overflow: hidden;
		}

		#header_con {
			padding: 0 0px 0 0px;

		}


		#open_drop {
			display: block !important;
			position: static !important;
			box-shadow: unset !important;
			border: none;
		}

		#open_drop li {
			border: none !important;
		}

		#open_drop>li>a i {
			margin-right: 18px;
			margin-left: 16px;
			font-size: 25px;
			vertical-align: middle;

		}

		#open_drop>li>a {
			padding: 6px 0px;
			font-size: 14px;
			font-weight: 700;
			color: #000000;


		}

		#open_drop>li>a {
			cursor: pointer;
			margin: 0px 0px;
		}

		#c_aret {
			display: none;
		}

		#open_drop>li>a {
			border-bottom: 1px solid #bfbbbb;
		}

		.navbar #header_con.top-bar .nav>li.user>a {
			border-bottom: 1px solid #bfbbbb;
		}

		#open_drop {
			padding: 0px 0;
			max-width: 100% !important;
		}

		li.af {
			padding: 0px 0 !important;
		}

		.af:hover {
			background-color: #FFF;
		}

		#open_drop>li>a:hover {
			color: #ffffff;
			text-decoration: none;
			background-image: linear-gradient(to right, #1a60e2, #a0b3ec, #b9419c, #9d68a9);
		}

		.fa-sign-out {
			margin-right: 13px !important;
		}
	}
	/** End Navbar Mobile Device Css */
	
	.dropdown-menu {
		margin-top: 0;
		border-radius: 0;
		box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
		background: rgba(255, 255, 255, 0.96);
		border: 1px solid #dddddd;
		padding: 0;
		min-width: 120px;
		max-width: 300px;
	}

	.dropdown-menu>li {
		margin: 0;
	}

	.dropdown-menu>li>a {
		padding: 9px 15px 9px 2px;
		border-bottom: 1px solid #e2e2e2;
		color: #04243D  !important;
		
		font-size: 12px;
		margin-left: 14px;
	}

	.dropdown-menu>li>a:hover,
	.dropdown-menu>li>a.current {
		border-bottom-color: #007aff;
		color: #007aff;
		background: transparent;
	}

	.dropdown-menu>li>a i {
		margin-right: 10px;
		font-size: 18px;
		vertical-align: middle;
	}

	.dropdown-menu>li:last-child>a {
		border: 0;
	}
</style>
<div class="container-fluid top-bar" id="header_con">
  <div class="pull-left"><a class="logo11" href="user-dashboard.php"> <img src="images/gracious.png" class="applicatoin-logo" /> </a></div>
 
  <div class="pull-right">
        <input id="g_menu-toggle" type="checkbox" />
		<label class='g_menu-button-container' for="g_menu-toggle">
			<div class='g_menu-button'></div>
		</label>
	<ul class="g_menu nav navbar-nav pull-right">
	<?php
	    require_once("include/connect.php");

		 $username = get_user($conn,$_SESSION['user_id']);
		
		// $client_query = "select * from client where approve_status=1";
		// $client_result = mysqli_query($conn,$client_query);
		// $client_count = mysqli_num_rows($client_result);
		// if($_SESSION['role']=='AD'){
	?>

	  <li class="dropdown user gra_user">
        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
		<img width="34" height="34" src="images/no_profile.png" /><?php echo $username; ?><b class="caret" id="c_aret"></b></a>
		<ul class="dropdown-menu" id="open_drop">
		  <li class="af"><a href="change-password.php"><i class="fa fa-user"></i>Change Password</a></li>
		  <li class="af"><a href="logout.php"> <i class="fa fa-sign-out"></i>Logout</a>
		  </li>
		</ul>
	  </li>
	</ul>
  </div>
  

  <?php //if($_SESSION['role']=='CL') 
//   {
// 	   $query33 = "select * from client where client_id IN (select company_name from users where user_id='".$_SESSION['user_id']."')";
// 	$result33 = mysqli_query($conn,$query33);
// 	$row33 = mysqli_fetch_array($result33);
	
// 	echo ' <div class="applicatoin-name" style="text-transform: uppercase;">'.$row33["client_company_name"].'</div>'; 
	
//   }
// 	else
	echo ' <div class="applicatoin-name" > Gracious Express</div>';
  ?>
  
 
 
   
  <!-- <button class="navbar-toggle"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button> -->
  
</div>


<div class="loading-page"><img src="images/ajax_loader.gif" /></div>
<div class="form-data-saving" style="display:none;"><img src="images/loading.png" /></div>