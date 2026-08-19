<?php require_once("include/connect.php");

if (isset($_COOKIE['persistID'])) {

	$user_id =   $_COOKIE['persistID'];


	   $query = "select * from users where user_id='$user_id' and status = 1 ";
		$result = mysqli_query($conn,$query) or die(mysqli_error());

		if(mysqli_num_rows($result) == 1)
		{ 
			$row = mysqli_fetch_array($result);
			//$result = mysqli_query($conn,$query) or die(mysqli_error());
			$_SESSION['LAST_ACTIVITY'] = time();
			 $_SESSION['user_id'] = $row['user_id'];
			 $contact_no = $row['contact_no'];
			 $phone = substr($contact_no, 6, 10);
			 if($_SESSION['user_id'] == ''){
				echo '<script> location.href="login.php"; </script>';
			 }
			
		}

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Verify User</title>
	<?php include("include/title.php"); ?>
	<?php include("include/css_js_forgetpassword.php"); ?>
	<link href="favicon.png" type="image/x-icon" rel="shortcut icon">
	<link href="assets/css/master.css" rel="stylesheet">

	<!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" /> -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" />
	<link href="stylesheets/datatables.css" media="all" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<!-- book consignment css and js starts here -->
	<link rel="stylesheet" href="assets/css/book-consignment.css">
	<link rel="stylesheet" href="f5/fontawesome.min.css">
	<!-- book consignment css and js finished here -->
	<!-- <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script> -->
	<script src="assets/js/jquery.validate.min.js"></script>
	<script src="assets/js/modernizr.custom.js"></script>
	<style>
		body {
			background-image: url('http://staging.graciousexpress.com/web/images/back_blue.jpg');
		}

		.main_div {
			display: flex;
			justify-content: center;
			align-items: center;
			margin: auto;
			position: absolute;
			top: 0;
			left: 0;
			bottom: 0;
			right: 0;


		}

		.second_div {
			width: 34.5rem;
			padding: 10px;
			border: 1px solid blue;
			background-color: white;
			color: #1762E5;
			box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
			border-radius: 20px;

		}

		.img_src img {
			height: auto;
			padding: 10px;
			width: 33.5rem;
		}

		.form-control {
			display: block;
			width: 100%;
			height: 34px;
			padding: 6px 12px;
			font-size: 14px;
			line-height: 1.42857143;
			color: #555;
			background-color: #fff;
			background-image: none;
			border: 1px solid #739eed;
			border-radius: 4px;
		}

		/* #countdown {
			font-size: 19px;
			text-align: center;
			margin-bottom: 1rem;
		} */



		/* B5 */
		.height-100 {
			height: 100vh
		}

		.card {
			width: 400px;
			border: none;
			height: 300px;
			box-shadow: 0px 5px 20px 0px #d2dae3;
			z-index: 1;
			display: flex;
			justify-content: center;
			align-items: center;
			border-radius: 20px;
		}

		.card h6 {
			color: #20419b;
			font-size: 20px
		}

		.inputs input {
			width: 40px;
			height: 40px
		}

		input[type=number]::-webkit-inner-spin-button,
		input[type=number]::-webkit-outer-spin-button {
			-webkit-appearance: none;
			-moz-appearance: none;
			appearance: none;
			margin: 0
		}

		.card-2 {
			background-color: #fff;
			padding: 10px;
			width: 350px;
			height: 100px;
			bottom: -50px;
			left: 20px;
			position: absolute;
			border-radius: 5px
		}

		.card-2 .content {
			margin-top: 50px
		}

		.card-2 .content a {
			color: red
		}

		.form-control:focus {
			box-shadow: none;
			border: 2px solid red
		}

		.validate {
			border-radius: 20px;
			height: 40px;
			color: #fff;
			background-color: #0b5ed7;
			border-color: #0a58ca;
			width: 140px
		}

		.btn-primary:hover {
			color: #fff;
			background-color: #1d8097;
			border-color: #0a58ca;
		}

		#countdown 
        {
			font-size: 19px;
			text-align: center;
			margin-top: 1.2rem;
		}

		.topnav {
			margin: 10px;
		}

		body {
			overflow: hidden;
		}
	</style>
</head>

<body>
	<div class="topnav">
		<a href="#"><img width="250px" src="images/gracious.png" style="background-color:white"></a>
		<div class="topnav-right">

		</div>
	</div>
	<!-- <div class="main_div">

		<div class="second_div">
			<div id="response" class="alert " role="alert" style="display:none;">
				<div class="message" style="text-align:center"></div>
			</div>
			<div class="img_src">
				<img src="http://localhost/GraciousExpress/web/images/gracious.png" alt="">
			</div>
			<form action="" class="form-group" method="post" id="change_user_pass">
				<input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id; ?>">
				<div class="form-input">
					<label for="">OTP:</label>
					<input type="text" name="otp" id="otp" value="" class="form-control" maxlength="6" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');" />
					<span class="pass_check"></span>
				</div>

				 <div id="countdown">OTP Expires in : <span id="timer">04:59</span></div>
				<div class="text-center">
					<input type="submit" name="submit" value="submit" id="submit" class="btn btn-primary" />

				</div>

			</form>
		</div>


	</div> -->
	<div class="container height-100 d-flex justify-content-center align-items-center">
		<div class="position-relative">
			<div id="response" class="alert " role="alert" style="display:none;">
				<div class="message" style="text-align:center"></div>
			</div>
			<div class="card p-2 text-center" >
				<div id="verify_otp">
				<h6>Please enter the one time password <br> to verify your account</h6>
				<div> <span>A code has been sent to</span> <small>*******<?php echo $phone; ?></small> and your email.</div>
				<form action="" class="form-group" method="post" id="verify_otp">
					<input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id; ?>">
					<div id="otp" class="inputs d-flex flex-row justify-content-center mt-2">
						<input class="m-2 text-center form-control rounded otp" type="text" id="first" maxlength="1" autocomplete="off" />
						<input class="m-2 text-center form-control rounded otp" type="text" id="second" maxlength="1" autocomplete="off" />
						<input class="m-2 text-center form-control rounded otp" type="text" id="third" maxlength="1" autocomplete="off" />
						<input class="m-2 text-center form-control rounded otp" type="text" id="fourth" maxlength="1" autocomplete="off" />
						<input class="m-2 text-center form-control rounded otp" type="text" id="fifth" maxlength="1" autocomplete="off" />
						<input class="m-2 text-center form-control rounded otp" type="text" id="sixth" maxlength="1" autocomplete="off"/> 
					</div>
					<div class="mt-4">
						<input type="submit" name="submit" value="Validate" id="submit" class="btn btn-primary px-4 validate" />
						
						<!-- <button class="btn btn-danger px-4 validate">Validate</button>  -->

					</div>
					
					<div id="countdown"><small>OTP Expires in : <span id="timer">04:59</span></small></div>
					<div id="demo"></div>
				</form>
				</div>
				<a href="http://localhost/graciousexpress/user/login.php" id="go_to_login" class="btn btn-primary px-4 validate " style="display: none !important;" >Go TO Login</a>
			</div>
			
		</div>
	</div>
</body>

</html>
<script>
	if (localStorage.getItem('timers') !== null) {
		// console.log(`Email address exists`);
		document.getElementById('timer').innerHTML = localStorage.getItem("timers");

	} else {
		// console.log(`Email address not found`);
		
		document.getElementById('timer').innerHTML =
			02 + ":" + 15;
	}

	//document.getElementById("demo").innerHTML = localStorage.getItem("timers");
	

	startTimer();

	function startTimer() {
		var presentTime = document.getElementById('timer').innerHTML;
		var timeArray = presentTime.split(/[:]+/);
		var m = timeArray[0];
		var s = checkSecond((timeArray[1] - 1));
		if (s == 59) {
			m = m - 1
		}
		if (m < 0) {
			return
		}

		document.getElementById('timer').innerHTML =
			m + ":" + s;

		console.log(m)
		localStorage.setItem("timers", m + ":" + s);

		setTimeout(startTimer, 1000);

	}

	function checkSecond(sec) {
		if (sec < 10 && sec >= 0) {
			sec = "0" + sec
		}; // add zero in front of numbers < 10
		if (sec < 0) {
			sec = "59"
		};
		return sec;
	}





	$(document).ready(function() {

		//document.addEventListener("DOMContentLoaded", function(event) {

		function OTPInput() {
			const inputs = document.querySelectorAll('#otp > *[id]');
			for (let i = 0; i < inputs.length; i++) {
				inputs[i].addEventListener('keydown', function(event) {
					if (event.key === "Backspace") {
						inputs[i].value = '';
						if (i !== 0) inputs[i - 1].focus();
					} else {
						if (i === inputs.length - 1 && inputs[i].value !== '') {
							return true;
						} else if (event.keyCode > 47 && event.keyCode < 58) {
							inputs[i].value = event.key;
							if (i !== inputs.length - 1) inputs[i + 1].focus();
							event.preventDefault();
						} else if (event.keyCode > 64 && event.keyCode < 91) {
							inputs[i].value = String.fromCharCode(event.keyCode);
							if (i !== inputs.length - 1) inputs[i + 1].focus();
							event.preventDefault();
						}
					}
				});
			}
		}
		OTPInput();
		//});
		var timer = $('#countdown span#timer').text();
			console.log(timer);
			var hms = '00:0' + timer; // your input string
			console.log('sec', hms)
			var a = hms.split(':'); // split it at the colons

			// minutes are worth 60 seconds. Hours are worth 60 minutes.
			var otp_enter_time = (+a[0]) * 60 * 60 + (+a[1]) * 60 + (+a[2]);

			if (otp_enter_time < 1) {
				time = 0;
				console.log("otp Expired");
				$('#verify_otp').hide();
				$('#go_to_login').show();
			}
		$('#submit').on('click', function(e) {
			e.preventDefault();
			// alert('testt');

			var form_name = 'verify_login_otp';
			var user_id = $('#user_id').val();
			// var otp = $('#otp').val();
			var otp = '';
			$('.otp').each(function() {
				otp = otp + $(this).val();
				// console.log(otp);
			})

			var time = '';
			var timer = $('#countdown span#timer').text();
			console.log(timer);
			var hms = '00:0' + timer; // your input string
			console.log('sec', hms)
			var a = hms.split(':'); // split it at the colons

			// minutes are worth 60 seconds. Hours are worth 60 minutes.
			var otp_enter_time = (+a[0]) * 60 * 60 + (+a[1]) * 60 + (+a[2]);

			if (otp_enter_time < 1) {
				time = 0;
				console.log("otp Expired");
				

				$.ajax({
					url: '../web/save_details.php',
					type: 'post',
					data: {
						form_name: form_name,
						user_id: user_id,
						otp: otp,
						time: time
					},
					success: function(data) {
						console.log(data);
						if (data != 0) {
							$('.pass_check').hide();
							$("#response").removeClass("alert-success");
							$("#response").addClass("alert-danger");
							$('#response .message').html('OTP Expired Please Login again.');
							$('#response').fadeIn('slow').delay(200000).fadeOut('slow');
							setTimeout(function() {
								window.location.href = "../user/login.php";
							}, 2000);
						} else {
							// 	$('.pass_check').hide();
							// 	$("#response").removeClass("alert-success");
							// 	$("#response").addClass("alert-danger");
							// 	$('#response .message').html('Password Update Failure!');
							// 	$('#response').fadeIn('slow').delay(200000).fadeOut('slow');
							// }
						}
					}
				});
				$('.pass_check').html('<p style="color:red;">OTP Expired! Login Again.</p>');
				$('.pass_check').fadeIn('slow').delay(2000).fadeOut('slow');
			} else {
				console.log('otp valid');
				time = 1;
				$.ajax({
					url: '../web/save_details.php',
					type: 'post',
					data: {
						form_name: form_name,
						user_id: user_id,
						otp: otp,
						time: time
					},
					success: function(data) {
						if (data != 0) {
							$('.pass_check').hide();
							$("#response").removeClass("alert-danger");
							$("#response").addClass("alert-success");
							$('#response .message').html('OTP Verify Successful.');
							$('#response').fadeIn('slow').delay(200000).fadeOut('slow');
							setTimeout(function() {
								window.location.href = "../user/user-dashboard.php";
							}, 2000);
						} else {
							$('.pass_check').hide();
							$("#response").removeClass("alert-success");
							$("#response").addClass("alert-danger");
							$('#response .message').html('OTP Mismatched');
							$('#response').fadeIn('slow').delay(200000).fadeOut('slow');
						}
						console.log(data)
					}

				});
			}

		});
	});
</script>