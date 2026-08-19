<?php
require_once("include/connect.php");
require_once("include/function.php"); 
?>
<!DOCTYPE html>
<html>
  <head>
  <?php include("include/title.php"); ?>
  <?php include("include/css_js.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
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
			require_once("include/menu.php"); 
			
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
					<label class="control-label col-md-4">Old Password <span style ="color:red;">*</span> :</label>
					<div class="col-md-5">
					  <input class="form-control dup-check" placeholder="old password" type="password" name="oldpassword" id="oldpassword" required>
						<span class="dup-check-text-status"></span>
					  <input type="hidden" class="dup-check-status" value="" />
					</div>
				  </div>
				  
				 
				  <div class="form-group">
					<label class="control-label col-md-4">New Password <span style ="color:red;">*</span> :</label>
					<div class="col-md-5">
					  <input class="form-control" placeholder="new password" type="password" name="new_password" id="new_password" required>
					  <span class="pass-check"></span>
					</div>
				  </div>
				  
				  <div class="form-group">
					<label class="control-label col-md-4">Confirm Password <span style ="color:red;">*</span> :</label>
					<div class="col-md-5">
					  <input class="form-control" placeholder="confirm password" type="password" name="confirm_password" id="confirm_password" required>
					  <div style="color: red; padding-left: 10px;" class="registrationFormAlert" id="divCheckPasswordMatch"></div>
					</div>
				  </div>
				  
				  <div class="row">
					<div class="col-md-12 form-action">
					  <button class="btn btn-primary" type="button" id="save">Submit</button>
					  <button class="btn btn-default-outline btn-reset" type="button" >Cancel</button>
					</div>
				  </div>
				  
				</form>
			  </div>
			</div>
		  </div>
		</div>

      </div>

		<?php require_once("include/footer.php"); ?>
	<script>
		$(document).ready(function(){
			$('#change_password').validate({
				rules : {
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
			/* function CheckPassword(password)   
			{  
				var passw = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/;  
				if(password.value.match(passw))   
				{
					strength = true;
					$('.pass-check').html('');
				}	
				else{ 
					strength = false;
					$('.pass-check').html('Atleast eight characters, contains capital letters, digits, and special characters');
				}	
			}   */
			$(document).on('click submit', '#save', function(ev){
				//var password = $('#password').val();
				//CheckPassword(password);
				if(($("#change_password").valid() == true) && ($(".dup-check-status").val() == 0 ) && (strength==true)) {
					$(".form-data-saving").show();
					var data = $("#change_password").serialize();	
					$.post('save_details.php', data, function(data) { 	
						if( data != 0)
						{
							console.log(data);
							$(".form-data-saving").hide();
							
							$("#alert-status").text("");
							$("#alert-message").text("Saved Successfully");
							$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								window.location.href='dashboard.php';
							});
						}
						else{
							console.log(data);
							$(".form-data-saving").hide();
							
							$("#alert-status").text("Alert !!! ");
							$("#alert-message").text("Data Saving Failed");
							$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-danger");
							});
						}
					});
				}
			});
				$(document).on('blur', '.dup-check', function(ev){
					
					var chk_key_id = $("#login-id").val();
					var chk_key = $(this).val();
					if(chk_key != ""){
						$(".dup-check-text-status").html('<p style="color:green;"><i class="fa fa-refresh fa-spin"></i> Checking</p>');
						$.ajax({
						  cache: false,
						  url: 'check_existing.php', // url where to submit the request
						  type : "GET", // type of action POST || GET
						  dataType : 'json', // data type
						  data : { cmd: "chk_password", chk_key: chk_key, chk_key_id: chk_key_id }, // post data || get data
						  success : function(result) {
							  console.log(result);
								if(result['dup_status'] == 1){
									$(".dup-check-text-status").html('<p style="color:green;"><i class="fa fa-check-circle"></i> Correct  Password</p>');
									$(".dup-check-status").val("0");
								}
								else{
									$(".dup-check-text-status").html('<p style="color:red;"><i class="fa fa-times-circle"></i> Wrong Password Type Correct...</p>');
									$(".dup-check-status").val("1");
								}
						  },
						  error: function(jqxhr) {
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
        
     
	    <div class="modal fade popup_close" id="myModal">
		    <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
			    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
			    <h4 class="modal-title" style="color:#fff">
			      Alert!
			    </h4>
			  </div>
			    
				<div class="modal-body">
				    <h5 text-align="center">
				     Do you want to Delete This Record ?
				    </h5>
					<div class="modal-footer">
					    <button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button>
					    <button class="btn btn-default-outline" data-dismiss="modal" type="button" id="">No</button>
					</div>
				</div>
			</div>
		    </div>
                </div>
				
		<div class="delete-error-popup" >
		    <div class="popup_overlay" id="popup_overlay"></div>
			<div class="popup" id="popup">
			    <div class="popup_message">
			    <h5 class="popup-title">Alert ! </h5>
				    This User Cannot Delete.Used by another record. so you can't Delete !!! <br/> &nbsp; <br/>
			    <button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br/> &nbsp; <br/>
			    </div>
			    <!--<span class="popup_close" id="popup_close">X</span>-->
			</div>
		</div>
		
  </body>
</html>