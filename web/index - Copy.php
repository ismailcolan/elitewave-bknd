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
</style>
  <body class="login1">
    <!-- Login Screen -->
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login_logo text-center">
					<img src="images/gracious.png">
				</div>

				
				<form  class="login100-form validate-form" method="post" id="login_form" >
		<input type="hidden" name="form_name" id="from_name" value="login">
		
					<div class="wrap-input100 validate-input m-b-26" data-validate="Email is required">
						<span class="label-input100 name_left">Email</span>
						

<img src="images/email.png"><input class="input100" type="email" name="username" placeholder="Enter Email" required>
						<span class="focus-input100"></span>
					</div>
<input type="hidden" name="login" id="login" value="<?php echo $_REQUEST['login'] ?>">
					<div class="wrap-input100 validate-input m-b-18" data-validate = "Password is required">
						<span class="label-input100 name_left">Password</span>
					<img src="images/password.png">	<input class="input100" type="password" name="password" placeholder="Enter Password" required>
						<span class="focus-input100"></span>
					</div>
<div class="container-login100-form-btn">
						
						<div class="form-group">
            <input class="form-control login100-form-btn" type="button" id="save" value="Login">

        </div>
<div id="response" class="alert alert-danger" style="display:none;">
			<div class="message" style="text-align:center"></div>
		</div>
					</div>
					<div class="flex-sb-m w-full p-b-30">
						

						<div>
							<a href="forgot_password.php" class="txt1">
								Forgot Password?
							</a>
						</div>
					</div>

					
				</form>
			</div>
		</div>
	</div>
    <!-- End Login Screen -->
	<script type="text/javascript">
			jQuery(function($) {
				$(document).on('click submit', '#save', function(ev){

					if($("#login_form").valid() == true){
						//$("#save").html('<img src="images/btn-ajax-loader.gif" height="10px" width="10px"/> &nbsp; Checking..');
						
						var data = $("#login_form").serialize();
						//alert(data);
							
						$.post('save_details.php', data, function(data) {   
						 console.log(data);
							if( data == "1") {
								//console.log(data);
								$("#save").html('<img src="images/btn-ajax-loader.gif" height="10px" width="10px"/> &nbsp; Loading..');
							window.location.href='dashboard.php';
				            }
							else if(data==2){
								$("#save").html('Login');
								$(".required").parent().addClass("has-error");
                                $("#response").removeClass("alert-success");
                                $("#response").addClass("alert-success");
                                $("#response .message").html("<strong>Your Password has been already changed using this url</strong>");
                                $('#response').fadeIn('slow').delay(200000).fadeOut('slow');
							}
                          else{
							
								$("#save").html('Login');
								$(".required").parent().addClass("has-error");
                                $("#response").removeClass("alert-success");
                                $("#response").addClass("alert-success");
                                $("#response .message").html("<strong>Login Failed : </strong>Invalid Username or Password!!!");
                               $('#response').fadeIn('slow').delay(200000).fadeOut('slow');
							} 
							});
						}
				});
			}); 
		
		</script>
  </body>
  </html>
