<?php
if(session_id() == '') {
    session_start();
}
?>
<!DOCTYPE html>
<html>
  <head>
  <?php include("include/title.php"); ?>
  <?php include("include/css_js_forgetpassword.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="../assets/fonts/font-awesome-4.5.0/css/font-awesome.min.css">
<link href="../assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
	
    <style>
		span.pass-check{
			color:red;
		}
	</style>
  </head>
  <body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
      <!-- Navigation -->
      <div class="navbar navbar-fixed-top scroll-hide">
        <?php 
			require_once("include/header.php");
			
			
		 ?>
      
	</div>
      <div class="container-fluid main-content">
		<div class="row">
		  <div class="col-md-offset-3 col-lg-6">
			<div class="widget-container fluid-height clearfix">
			  <div class="heading">
				Change Password
				
			  </div>
			  <div class="widget-content padded">
				<form action="" class="form-horizontal" id="change_password" method="post">
					<input type="hidden"  id="form_name" name="form_name" value="password_change">
					<input type="hidden"  id="login_id" name="login_id" value="<?php echo $_SESSION['user_id']; ?>"/>
					 <div id="response" class="alert alert-danger" style="display:none;">
							<div class="message" style="text-align:center"></div>
					</div>        
				  
				  <div class="form-group">
					<label class="control-label col-md-4">Old Password<span style ="color:red;">*</span> :</label>
					<div class="col-md-5">
					  <input class="form-control dup-check" placeholder="Enter Old Password" type="password" name="oldpassword" id="oldpassword" required>
						<span class="dup-check-text-status"></span>
					  <input type="hidden" class="dup-check-status" value="" />
					</div>
				  </div>
				  
				 
				  <div class="form-group">
					<label class="control-label col-md-4">New Password<span style ="color:red;">*</span> :</label>
					<div class="col-md-5">
					  <input class="form-control" placeholder="Enter New Password" type="password" name="new_password" id="new_password" required>
					  <span class="pass-check"></span>
					</div>
				  </div>
				  
				  <div class="form-group">
					<label class="control-label col-md-4">Confirm  Password<span style ="color:red;">*</span> :</label>
					<div class="col-md-5">
					  <input class="form-control" placeholder="Enter Confirm Password" type="password" name="confirm_password" id="confirm_password" required>
					  <div style="color: red; padding-left: 10px;" class="registrationFormAlert" id="divCheckPasswordMatch"></div>
					</div>
				  </div>
				  
				  <div class="row">
					<div class="col-md-12 form-action">
					  <button class="btn btn-primary" type="button" id="save">Submit</button>
					  <a class="btn btn-default-outline btn-reset" href="user-dashboard.php">Cancel</a>
					</div>
				  </div>
				  
				</form>
			  </div>
			</div>
		  </div>
		</div>

      </div>
<script>
$(document).ready(function(){
    $('#change_password').validate({
        rules :{
            password : {
                minlength : 8
            },
            confirm_password : {
                minlength : 8,
                equalTo : "#new_password"
            }
        }
    });
    var strength = true;
    $(document).on('click submit','#save',function(){
        if(($("#change_password").valid() == true) && ($('.dup-check-status').val() == 0) && (strength == true)) {
            $('.form-data-saving').show();
            var data = $("#change_password").serialize();
            $.post('../save_details.php',data , function(result){
                console.log(result);
                if(result != 0){
                    $('.form-data-saving').hide();
                    $("#alert-status").text("");
                    $("#alert-message").text("Password Successfully Changed");
                    $("#alert-container").addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                    $("#alert-container").hide();
                    $("#alert-container").removeClass('alert-success');
                        window.location.href="<?php print_r(site_paths) ?>/user/user-dashboard.php";
                    });
                }else{
                    $('.form-data-saving').hide();
                    $("#alert-status").text("Alert !!!");
                    $("#alert-message").text("Password Change Failure. Try Again!");
                    $("#alert-container").addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                    $("#alert-container").hide();
                    $("#alert-container").removeClass('alert-danger'); 
            });
                
        }
    });
 }
});

$(document).on('blur','.dup-check',function(){
var chk_key_id = $("#login_id").val();
var chk_key = $(this).val();
//alert(chk_key);
if(chk_key != ''){
    $(".dup-check-text-status").html('<p style="color:green;"><i class="fa fa-refresh fa-spin"></i> Checking...</p>');
    $.ajax({
        url : "../check_duplicate.php",
        type:"post",
        data:{cmd:"check_pass",chk_key_id: chk_key_id, chk_key: chk_key},
        success:function(data){
            console.log(data);
            if(data == 1){
                $(".dup-check-text-status").html('<p style="color:green;"> <i class="fa fa-check-circle"></i> Correct Password</p>');
                $(".dup-check-status").val("0");
            }else{
                $(".dup-check-text-status").html('<p style="color:red;"><i class="fa fa-times-circle"></i> Wrong Password, Try Again!</p>');
                $(".dup-check-status").val("1");
            }
        },
        error:function(jqxhr){
            console.log(jqxhr.responseText);
            }
         });
        }
    });
});
$(window).load(function() {
				$(".loading-page").hide();
			});
</script>

<div class="alert" id="alert-container" style="display:none;">
		<button type="button" class="close" data-dismiss="alert">x</button>
		<strong id="alert-status"></strong>
		<span id="alert-message"></span>
		</div>
        </body>
</html>