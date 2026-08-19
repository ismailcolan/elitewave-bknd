<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include("include/title.php"); ?>
<?php include("include/connect.php"); ?>
	<?php include("include/css_js.php"); ?>
	<link href="stylesheets/main.css" media="all" rel="stylesheet" type="text/css" />
<link href="stylesheets/util.css" media="all" rel="stylesheet" type="text/css" />
	
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">

  </head>
<style>
.login_logo img {
    width: 68%;
    padding: 4%;
    margin: 0 auto;
    display: table;
}
.label-input100 {
    font-family: sans-serif !important;
}

.alert-danger {

    width: 100% !important;
    margin-left: -17% !important;
}
.input100 {
    font-family: sans-serif;
    font-size: 12px;
}
.login100-form-btn {
    font-family: sans-serif;
    font-size: 14px;
}
h2.forgot_password{
	background-color:#002472;
	color:#fff;
	padding: 6px;
}
</style>
  <body class="login1">
    <!-- Login Screen -->
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login_logo text-center">
					<h2 class="forgot_password">Forgot Password</h2>
				</div>

				
				<form  class="login100-form validate-form" method="post" id="forgot_password" >
		<input type="hidden" name="form_name" id="from_name" value="forgot_password">
		
					<div class="wrap-input100 validate-input m-b-26" data-validate="Email is required">
						<span class="label-input100 name_left">Email</span>
						

<img src="images/email.png"><input class="input100" type="email" name="email" placeholder="Enter Email" required>
						<span class="focus-input100"></span>
					</div>

					
<div class="container-login100-form-btn">
						
						<div class="form-group">
            <input class="form-control login100-form-btn" type="button" id="save" value="Get Password">

        </div>
<div id="response" class="alert" style="display:none;">
			<div class="message" style="text-align:center"></div>
		</div>
					</div>
					<div class="flex-sb-m w-full p-b-30">
						

						
					</div>

					
				</form>
			</div>
		</div>
	</div>
    <!-- End Login Screen -->
	<script type="text/javascript">
			jQuery(function($) {
				$(document).on('click submit', '#save', function(ev){
					
					if($("#forgot_password").valid() == true){
						
						//$("#save").html('<img src="images/btn-ajax-loader.gif" height="10px" width="10px"/> &nbsp; Checking..');
						var data = $("#forgot_password").serialize();
						//alert(data);
						$(this).prop("disabled",true);
							$(this).val("Loading...");
							
						$.post('save_details.php', data, function(data) {  
						
							console.log(data);
								if( data == 1) {
									$(this).val("Get Password");
									$("#response").removeClass("alert-danger");
									$("#response").addClass("alert-success");
									$("#response .message").html("<strong>Your Password Reset Link Sent to your mail id please check your mail..</strong>");
									$('#response').fadeIn('slow').delay(200000).fadeOut('slow');
									setTimeout(function(){
										//window.location.href = "index.php";
										window.location.href = "../user/login.php";
                                    

									}, 2000);

									
								}
								else if(data==2){
									$("#save").html('Login');
									$(".required").parent().addClass("has-error");
									$("#response").removeClass("alert-success");
									$("#response").addClass("alert-danger");
									$("#response .message").html("<strong>Failed to recover your password try again..</strong>");
								   $('#response').fadeIn('slow').delay(200000).fadeOut('slow');
								}
							  else{
								 console.log(data);
									$("#save").html('Login');
									$(".required").parent().addClass("has-error");
									$("#response").removeClass("alert-success");
									$("#response").addClass("alert-danger");
									$("#response .message").html("<strong>Enter Valid mail-id : </strong>");
								   $('#response').fadeIn('slow').delay(200000).fadeOut('slow');
								} 
						});
						}
				});
			}); 
		
		</script>
  </body>
  </html>
