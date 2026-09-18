<?php
require_once('include/connect.php');
require_once('include/function.php');

$key = $_REQUEST['key'] ?? '';
$is_edit = ($key != '');
if (!$is_edit && empty($_GET['create'])) {
	header('Location:user_list.php');
	exit;
}

$users_row = array(
	'company_type' => 'GRACIOUS',
	'company_name' => 0,
	'branch_name' => '',
	'user_name' => '',
	'role' => '',
	'assigned_vehicle' => '',
	'contact_no' => '',
	'email' => '',
	'password' => '',
);

if ($is_edit) {
	$user_query = "select * from users where md5(user_id)='" . mysqli_real_escape_string($conn, $key) . "'";
	$user_result = mysqli_query($conn, $user_query);
	if (!$user_result || mysqli_num_rows($user_result) == 0) {
		header('Location:user_list.php');
		exit;
	}
	$users_row = mysqli_fetch_array($user_result);
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

	.ew-page-v2 .ew-form-grid--users {
		grid-template-columns: repeat(3, minmax(0, 1fr));
	}

	.ew-page-v2 .ew-form-grid--users .span-3 {
		grid-column: span 3;
	}

	@media (max-width: 991px) {
		.ew-page-v2 .ew-form-grid--users {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}

	@media (max-width: 640px) {
		.ew-page-v2 .ew-form-grid--users {
			grid-template-columns: 1fr;
		}
	}

	.user-company-type-row {
		display: flex;
		flex-wrap: wrap;
		gap: 16px 24px;
		min-height: 40px;
		align-items: center;
	}

	.user-company-type-row label {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		margin: 0;
		font-weight: 500;
		cursor: pointer;
	}

	.ew-field.user-company-field {
		display: none;
	}

	.ew-field.user-company-field.visible {
		display: block;
	}

	.ew-field.user-vehicle-field {
		display: none;
	}

	.ew-field.user-vehicle-field.visible {
		display: block;
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
			<div class="col-md-12">
				<div class="ew-page-v2">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<a href="user_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php echo $is_edit ? 'Edit User' : 'Add User'; ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<a href="user_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
						</div>
					</div>
					<div class="ew-card">
						<h2 class="ew-card-section-title">User Details</h2>
						<div class="ew-form-body">
							<form id="users_form">
								<input type="hidden" id="form_name" name="form_name" value="add_user">
								<input type="hidden" id="edit_id" name="edit_id" value="<?php echo htmlspecialchars($key); ?>">

								<div id="response" class="alert alert-danger" style="display:none;">
									<div class="message" style="text-align:center"></div>
								</div>

								<div class="ew-form-grid ew-form-grid--users">
									<div class="ew-field span-3">
										<label class="control-label">Select Company <span style="color:red;">*</span> :</label>
										<div class="user-company-type-row">
											<label><input type="radio" value="GRACIOUS" id="gracious_company" name="company_type" <?php echo ($users_row['company_type'] !== 'OTHERS') ? 'checked' : ''; ?> /> Elite Wave 360 User</label>
											<label><input type="radio" value="OTHERS" id="other_company" name="company_type" <?php echo ($users_row['company_type'] == 'OTHERS') ? 'checked' : ''; ?> /> Client Company User</label>
										</div>
									</div>

									<div class="ew-field user-company-field<?php echo ((int) $users_row['company_name'] !== 0) ? ' visible' : ''; ?>" id="com_div">
										<label class="control-label">Company <span style="color:red;">*</span> :</label>
										<select class="form-control" id="company_name" name="company_name">
											<?php
											$com_option = '<option value="">Select company</option>';
											$com_q = mysqli_query($conn, "select * from client where status='0' order by client_company_name");
											while ($com_r = mysqli_fetch_array($com_q)) {
												$com_option .= '<option value="' . $com_r['client_id'] . '"';
												if ($users_row['company_name'] == $com_r['client_id']) {
													$com_option .= ' selected';
												}
												$com_option .= '>' . htmlspecialchars($com_r['client_company_name']) . '</option>';
											}
											echo $com_option;
											?>
										</select>
									</div>

									<div class="ew-field">
										<label class="control-label">Branch :</label>
										<select class="form-control" id="branch" name="branch">
											<?php
											$br_option = '<option value="">Select Branch</option>';
											if ((int) $users_row['company_name'] === 0) {
												$br_q = mysqli_query($conn, "select * from branch where status='0' order by branch_name");
												while ($br_r = mysqli_fetch_array($br_q)) {
													$br_option .= '<option value="' . $br_r['branch_id'] . '"';
													if ($users_row['branch_name'] == $br_r['branch_id']) {
														$br_option .= ' selected';
													}
													$br_option .= '>' . htmlspecialchars($br_r['branch_name']) . '</option>';
												}
												echo $br_option;
											} else {
												$client_id = (int) $users_row['company_name'];
												$city_query = "select * from client_branch where status=0 and company_id='" . $client_id . "' order by branch_name";
												$city_result = mysqli_query($conn, $city_query);
												while ($city_row = mysqli_fetch_array($city_result)) {
													$selected = ($users_row['branch_name'] == $city_row['client_branch_id']) ? ' selected' : '';
													echo '<option value="' . $city_row['client_branch_id'] . '"' . $selected . '>' . htmlspecialchars($city_row['branch_name']) . '</option>';
												}
											}
											?>
										</select>
									</div>

									<div class="ew-field">
										<label class="control-label">User Name <span style="color:red;">*</span> :</label>
										<input type="text" id="user_name" name="user_name" value="<?php echo htmlspecialchars($users_row['user_name']); ?>" class="form-control" required autocomplete="off" />
									</div>

									<div class="ew-field">
										<label class="control-label">Role <span style="color:red;">*</span> :</label>
										<select class="form-control" name="role" id="role">
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

									<div class="ew-field user-vehicle-field<?php echo ($users_row['role'] == 'DR') ? ' visible' : ''; ?>" id="assigned_vehicle_div">
										<label class="control-label">Assigned Vehicle :</label>
										<select class="form-control" name="assigned_vehicle" id="assigned_vehicle" <?php if ($users_row['role'] == 'DR') echo 'required'; ?>>
											<option value="">Select Vehicle</option>
											<?php
											$veh_q = mysqli_query($conn, 'SELECT vehicle_number, vehicle_type FROM vehicle WHERE status = 0 ORDER BY vehicle_number');
											while ($veh_r = mysqli_fetch_array($veh_q)) {
												$selected = ($users_row['assigned_vehicle'] == $veh_r['vehicle_number']) ? 'selected' : '';
												echo '<option value="' . htmlspecialchars($veh_r['vehicle_number']) . '" ' . $selected . '>' . htmlspecialchars($veh_r['vehicle_number']) . ' (' . htmlspecialchars($veh_r['vehicle_type']) . ')</option>';
											}
											?>
										</select>
									</div>

									<div class="ew-field">
										<label class="control-label">User Contact <span style="color:red;">*</span> :</label>
										<input type="text" name="contact_no" id="contact_no" pattern="\d{10}" minlength="10" maxlength="10" value="<?php echo htmlspecialchars($users_row['contact_no']); ?>" class="form-control" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off" required />
									</div>

									<div class="ew-field">
										<label class="control-label">User Email <span style="color:red;">*</span> :</label>
										<input type="email" name="user_email" value="<?php echo htmlspecialchars($users_row['email']); ?>" id="user_email" class="form-control User_email_dup-check" autocomplete="off" required />
										<span class="User_email_dup-check-text-status ew-field-hint"></span>
										<input type="hidden" class="user_dup-check-status" id="user_email_val" value="" />
									</div>

									<div class="ew-field">
										<label class="control-label">Password <?php echo $is_edit ? '' : '<span style="color:red;">*</span>'; ?> :</label>
										<input type="password" id="password" name="password" value="" placeholder="<?php echo $is_edit ? 'Leave blank to keep current password' : 'Enter password'; ?>" class="form-control" autocomplete="off" />
									</div>

									<div class="ew-field">
										<label class="control-label">Confirm Password :</label>
										<input type="password" name="confirm_password" id="confirm_password" value="<?php echo $is_edit ? htmlspecialchars(dec_name($users_row['password'])) : ''; ?>" class="form-control" autocomplete="off" />
									</div>
								</div>
							</form>
						</div>
						<div class="ew-form-footer">
							<a class="ew-btn-v2 ew-btn-v2-outline btn-reset" href="user_list.php">Cancel</a>
							<button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="save"><i class="fa fa-save"></i> Submit</button>
						</div>
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
            $("#com_div").removeClass('visible');
			$("#role").html('<option value="">Select Role</option><option value="AD">Admin</option><option value="USER">User</option><option value="DR">Driver</option>');
			$('#assigned_vehicle_div').removeClass('visible');
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
            $("#com_div").addClass('visible');
			$("#branch").val('').trigger('change');
			$("#role").html('<option value="CL">Client</option>');
			$('#assigned_vehicle_div').removeClass('visible');
			$('#assigned_vehicle').val('').prop('required', false);
        }
    });

	$(document).on('change', '#role', function() {
		if ($(this).val() == 'DR') {
			$('#assigned_vehicle_div').addClass('visible');
			$('#assigned_vehicle').prop('required', true);
		} else {
			$('#assigned_vehicle_div').removeClass('visible');
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
								location.href = 'user_list.php';
								});
							}
							else
							{
								$(".form-data-saving").hide();
								$("#save").attr("disabled", false);
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