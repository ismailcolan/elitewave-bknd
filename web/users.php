<?php
require_once ('include/connect.php');
require_once ('include/function.php');
$key = $_REQUEST['key'];
if ($key != '') {
	$user_query = "select * from users where md5(user_id)='" . $key . "'";
	$user_result = mysqli_query($conn, $user_query);
	$user_count = mysqli_num_rows($user_result);
	if ($user_count == 0) {
		header('Location:user_list.php');
	}
}
?>
<!DOCTYPE html>
<html>
  <head>
  <?php include ('include/title.php'); ?>
  <?php include ('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<style>
    #contact_no:invalid {
  color: red;
}
</style>
  </head>
  <body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
      <!-- Navigation -->
      <div class="navbar navbar-fixed-top scroll-hide">
        <?php
		require_once ('include/header.php');
		require_once ('include/menu.php');

		?>
      
	</div>
<div class="container-fluid main-content new_dpt_bottom">
  
		<div class="row">
		  <div class="col-md-offset-1 col-md-10">
			<div class="widget-container fluid-height clearfix">
			  <div class="heading"> <i class="fa fa-plus"></i>Users <span class="align-right"> <i class="fa fa-table"></i><a href="user_list.php">View List</a></span></div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="users_form">
				<?php
				$users_query = "select * from users where md5(user_id)='" . $key . "'";
				$users_result = mysqli_query($conn, $users_query);
				$users_row = mysqli_fetch_array($users_result);
				?> 
					<input type="hidden" id="form_name" name="form_name" value="add_user">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $key; ?>">
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<div class="col-md-offset-1 col-md-5">
						<div class="form-group" style="font-size:  16px;">
								<label class="control-label">Select Company <span style="color:red;">*</span> :</label><br>
								<input type="radio"  value="GRACIOUS" id="gracious_company" name="company_type" checked /> Elite Wave 360 User
								<input type="radio"  value="OTHERS" id="other_company" name="company_type"<?php if ($users_row['company_type'] == 'OTHERS') echo 'checked'; ?>> Client Company User
							
							</div>
							
							
						<div class="form-group" id="com_div" style=" <?php if ($users_row['company_name'] == 0) echo 'display:none'; ?> ">
								<select type="text" class="form-control"  id="company_name" name="company_name" required>
								<?php
								$com_option = '<option value=""> Select company</option>';

								$com_q = mysqli_query($conn, "select * from client where status='0' order by client_company_name");
								while ($com_r = mysqli_fetch_array($com_q)) {
									$com_option .= '<option value="' . $com_r['client_id'] . '"';
									if ($users_row['company_name'] == $com_r['client_id'])
										$com_option .= 'selected';

									$com_option .= '>' . $com_r['client_company_name'] . '</option>';
								}
								echo $com_option;
								?>
								
								
								</select>
							</div>
							<div class="form-group">
								<label class="control-label">Branch:</label>
								<select type="text" class="form-control" value="<?php echo $users_row['branch_name']; ?>" id="branch" name="branch" >
								<?php
								$br_option = '<option value=""> Select Branch</option>';

								if ($users_row['company_name'] == 0) {
									$br_q = mysqli_query($conn, "select * from branch where status='0' order by branch_name");
									while ($br_r = mysqli_fetch_array($br_q)) {
										$br_option .= '<option value="' . $br_r['branch_id'] . '"';
										if ($users_row['branch_name'] == $br_r['branch_id'])
											$br_option .= 'selected';
										$br_option .= ' >' . $br_r['branch_name'] . '</option>';
									}
									echo $br_option;
								} else {
									$city_query = "select * from client_branch where status=0 and company_id='$id' order by branch_name";
									$city_result = mysqli_query($conn, $city_query);
									while ($city_row = mysqli_fetch_array($city_result)) {
										$out_put .= '<option value=' . $city_row['client_branch_id'] . '>' . $city_row['branch_name'] . '</option>';
									}
									echo $out_put;
								}

								?>
								</select>
							</div>
							
							
							<div class="form-group">
								<label class="control-label">User Name <span style="color:red;">*</span> :</label>
								<input type="text" id="user_name" name="user_name" value="<?php echo $users_row['user_name']; ?>" class="form-control" required autocomplete="off" />
							</div>
							<div class="form-group">
								<label class="control-label">Role <span style="color:red;">*</span> :</label>
								<select class="form-control" name="role" id="role" >
									<option value="">Select Role</option>
									<?php if ($users_row['company_type'] == 'OTHERS') { ?>
										<option value="CL" selected>Client</option>
									<?php } else { ?>
										<option value="AD" <?php if ($users_row['role'] == 'AD') echo 'selected'; ?>>Admin</option>
										<option value="USER" <?php if ($users_row['role'] == 'USER') echo 'selected'; ?>>User</option>
										<option value="DR" <?php if ($users_row['role'] == 'DR') echo 'selected'; ?>>Driver</option>
									<?php } ?>
								</select>
						   </div>
							<div class="form-group" id="assigned_vehicle_div" style="<?php if ($users_row['role'] != 'DR') echo 'display:none;'; ?>">
								<label class="control-label">Assigned Vehicle:</label>
								<select class="form-control" name="assigned_vehicle" id="assigned_vehicle" <?php if ($users_row['role'] == 'DR') echo 'required'; ?>>
									<option value="">Select Vehicle</option>
									<?php
									$veh_q = mysqli_query($conn, 'SELECT vehicle_number, vehicle_type FROM vehicle WHERE status = 0 ORDER BY vehicle_number');
									while ($veh_r = mysqli_fetch_array($veh_q)) {
										$selected = ($users_row['assigned_vehicle'] == $veh_r['vehicle_number']) ? 'selected' : '';
										echo '<option value="' . $veh_r['vehicle_number'] . '" ' . $selected . '>' . $veh_r['vehicle_number'] . ' (' . $veh_r['vehicle_type'] . ')</option>';
									}
									?>
								</select>
							</div>
							
						</div>
						<div class="col-md-5">
						<div class="form-group">
								<label class="control-label">User Contact <span style="color:red;">*</span> :</label>
								<input type="text" name="contact_no" id="contact_no"  pattern="\d{10}" minlength=10  maxlength=10  value="<?php echo $users_row['contact_no']; ?>" class="form-control" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off" required />
								
							</div>
						<div class="form-group">
								<label class="control-label">User Email <span style="color:red;">*</span> :</label>
								<input type="email" name="user_email" value="<?php echo $users_row['email']; ?>" id="user_email" class="form-control User_email_dup-check" autocomplete="off" required/>
								<span class="User_email_dup-check-text-status p_css"></span>
                                <input type="hidden" class="user_dup-check-status" id="user_email_val" value="" />
							</div>
							
							<div class="form-group">
								<label class="control-label">Password <span style="color:red;">*</span> :</label>
								<!-- <input type="password" id="password" name="password" value="<?php echo dec_name($users_row['password']); ?>" class="form-control" required autocomplete="off"/> -->
								 <input type="password" id="password" name="password" value="" placeholder="Leave blank to keep current password" class="form-control" autocomplete="off"/>
							</div>
							<div class="form-group">
								<label class="control-label">confirm Password:</label>
								<input type="password" name="confirm_password" id="confirm_password" value="<?php echo dec_name($users_row['password']); ?>" class="form-control" autocomplete="off"/>
								
							</div>
					
						
						</div>
				 </div><br/>
				   <div class="row">
					<div class="col-md-12 form-action">
						<button class="btn btn-primary" type="button" id="save">Submit</button>
						<a class="btn btn-default-outline  btn-reset" type="button" href="dashboard.php">Cancel</a>
					</div>
				  </div>
				</form>
			  </div>
			</div>
		  </div>

		</div>
	

		<?php require_once ('include/footer.php'); ?>
	</div>	

		
		<script type="text/javascript">
		$(document).ready(function(){

		//User Email Exist
        if($('#gracious_company').is(':checked')){                        
		$(document).on('input', '.User_email_dup-check', function () {
        var email_check = $(this).val();
		var user_email = 'UserEmail';
        //alert(chk_key);
        if (email_check != '') {
            $(".User_email_dup-check-text-status").html('<p style="color:green;"> Checking...</p>');
            $.ajax({
                url: "check_existing.php",
                type: "post",
				dataType:"JSON",
                data: {
                    cmd: "chk_email_exist",
                    email_check: email_check,
					user_email:user_email,
                },
                success: function (data) {
                    console.log(data);
                    if (data[0] == 1) {
                        $(".User_email_dup-check-text-status").html('<p style="color:red;">'+ data[1] +'</p>');
                        $(".user_dup-check-status").val("0");

                    } else {
                        $(".User_email_dup-check-text-status").html('<p style="color:green;">'+data[1]+'</p>');
                        $(".user_dup-check-status").val("1");

                    }
                },
                error: function (jqxhr) {
                    console.log(jqxhr.responseText);
                }
            });
        }
    });
   }

		//End
	 $('#gracious_company').click(function () {
        if ($(this).is(':checked')) {
        	$('#user_email').prop('readonly', false);
			$("#company_name").val('0');
            $("#com_div").hide(100);
			$("#role").html('<option value="">Select Role</option><option value="AD">Admin</option><option value="USER">User</option><option value="DR">Driver</option>');
			$('#assigned_vehicle_div').hide(100);
			$('#assigned_vehicle').val('').prop('required', false);
			$.ajax({
					url:'fetch_details.php',
					type:"GET",
					data:{cmd:"get_gracious_branch"},
					async:false,
					success:function(result){
						console.log(result);
console.log(typeof result);
console.log(JSON.stringify(result));
console.log(result.length);
						$('#branch').html(result);	
						
						
					}
				});
        }
    });

    $('#other_company').click(function () {
        if ($(this).is(':checked')) {
        	$(".user_dup-check-status").val("1");
        	$('#user_email').prop('readonly', true);
            $("#com_div").show(100);
			$("#branch").val('').trigger('change');
			$("#role").html('<option value="CL">Client</option>');
			$('#assigned_vehicle_div').hide(100);
			$('#assigned_vehicle').val('').prop('required', false);
        }
    });

	$(document).on('change', '#role', function() {
		if ($(this).val() == 'DR') {
			$('#assigned_vehicle_div').show(100);
			$('#assigned_vehicle').prop('required', true);
		} else {
			$('#assigned_vehicle_div').hide(100);
			$('#assigned_vehicle').val('').prop('required', false);
		}
	});
	
	$(document).on('change','#company_name',function(e){
			var id = $(this).val();
			$.ajax({
					url:'fetch_details.php',
					type:"GET",
					data:{cmd:"get_client_branch",id:id},
					async:false,
					success:function(result){
						console.log(result);
console.log(typeof result);
console.log(JSON.stringify(result));
console.log(result.length);
						$('#branch').html(result);	
						
						
					}
				});
    
    		  //Client Email ID 
				$.ajax({
						url: 'fetch_details.php',
						data: { cmd: "get_company_user_mail", comp_name_val: id },
						async: false,
						success: function (result) {
							console.log("fetch user mail", result);
							$('#user_email').val(result);
                          }
                     });
							
				});
		
	$("#contact_no").keypress(function (e) 
		{
			if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
				return false;
			}
		});
		$('#users_form').validate({
			rules : {
				password : {
					minlength : 8
				},
				confirm_password : {
					minlength : 8,
					equalTo : "#password"
				}
			}
		});
		//button Save
			$(document).on('click','#save',function(){
				var data = $('#users_form').serialize();
				// duplicate_check();
				var user_email_val = $("#user_email_val").val();
				if($('#users_form').valid() == true && (user_email_val == 1 || user_email_val == ''))
				{
                	$(".form-data-saving").show();
					$(this).attr("disabled",true);
					$.ajax({
						url:"https://elitewave360.in/php/web/save_details.php",
						type:"post",
						data:data,
						success:function(result){
							console.log(result);
console.log(typeof result);
console.log(JSON.stringify(result));
console.log(result.length);
							if(result.trim() === "1"){
								$(".form-data-saving").hide();
								$("#alert-status").text("");
								$("#alert-message").text("Saved Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								location.reload();
								});
							}
							else
							{
								$(".form-data-saving").hide();
								$("#alert-status").text("Alert !!! ");
								$("#alert-message").text("Data Saving Failed");
								$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-danger");
								});
							}
						},
						error:function(jqxhr)
						{
							console.log(jqxhr.responseText);
						}
					});
				}else{
					console.log('Email Address already exist');
				}
			});
		$(document).on('click','.close-popup',function(){
				$(".form-data-saving").hide();
				$("#alert-status").text("");
				$("#alert-message").text("Saved Successfully please wait until page refresh");
				$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
				$("#alert-container").hide();
				$("#alert-container").removeClass("alert-success");
				location.reload();
				});
			});
		//Button Delete
			$(document).on('click', '.btn-trash', function(ev){
				var del_id = $(this).attr("id");
				$(".btn-confirm-delete").attr("id",del_id);
			});
			$(document).on('click', '.delete-error-popup-close', function(ev){
				$(".delete-error-popup").hide();
			});
			$(document).on('click', '.btn-confirm-delete', function(ev){
				$(".form-data-saving").show();
				$.post('https://elitewave360.in/php/save_details.php', { form_name: "del_branch", tbl_id: $(this).attr("id") }, function(data,status){	
				console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Department Deleted successfully...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
							$("#alert-container").hide();
							$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					else if(data == "404-del"){
						$(".delete-error-popup").show();
						$(".form-data-saving").hide();
					}
					else{
						$(".form-data-saving").hide();
						$("#alert-status").text("Alert !!! ");
						$("#alert-message").text("Department deletion failed");
						$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
							$("#alert-container").hide();
							$("#alert-container").removeClass("alert-danger");
						});
					}
				});
			});
		//Active Inactive
			$(document).on('click', '.btn-active', function(ev){
				$(".form-data-saving").show();
				var status1='';
				var msg='';
				var status = $(this).attr('data-status');
				if(status == '1'){
					status1='0';
					msg = "Activated";
				}
				else{
					status1='1';
					msg = "In-Activated";
				}
				$.post('https://elitewave360.in/php/save_details.php', { form_name: "inacv_client_branch", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
					console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Department Is "+msg+"...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					
					else if(data == 2){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Department Is "+msg+"...");
						$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-danger");
							location.reload();
						});
					}
					else if(data == "404-del"){
						$(".delete-error-popup").show();
						$(".form-data-saving").hide();
					}
					
				});
			});
			
			
			//	Button Edit
			$(document).on('click', '.btn-edit', function(ev){
				$(".form-data-saving").show();
				var tbl_id = $(this).attr("id");
				$.ajax({
					cache: false,
					url: 'fetch_details.php', // url where to submit the request
					type : "GET", // type of action POST || GET
					dataType : 'json', // data type
					data : { cmd: "get_branch_details", tbl_id: tbl_id }, // post data || get data
					success : function(result) {
					console.log(result);
console.log(typeof result);
console.log(JSON.stringify(result));
console.log(result.length);
						$(".form-data-saving").hide();
						$("#form_name").val("edit_branch");
						$("#edit_id").val(result['branch_id']);
						$("#department_code").val(result['department_code']);
						$('#department_name').val(result['department_name']);
						
					},
					error: function(jqxhr) {
						ewToast(jqxhr.responseText, 'error');
					}
				});
			});

			
			//Button Reset
			$(document).on('click', '.btn-reset', function(ev){
				$('#form_name').val('add_branch');
				$('#edit_id').val('');
				$('#department_name').val('');
				$('#department_code').val('');
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
				    This Data Cannot Delete.Used by another record. so you can't Delete !!! <br/> &nbsp; <br/>
			    <button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br/> &nbsp; <br/>
			    </div>
			    <!--<span class="popup_close" id="popup_close">X</span>-->
			</div>
		</div>
		
  </body>
</html>