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
<div class="container-fluid main-content new_dpt_bottom">
  
		<div class="row">
		  <div class="col-md-3 master_left">
			<div class="widget-container fluid-height clearfix">
			  <div class="heading"> <i class="fa fa-plus"></i>Company</div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="company_form">
				
					<input type="hidden" id="form_name" name="form_name" value="add_company">
					<input type="hidden" id="edit_id" name="edit_id" value="">
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">Department Code <span style="color:red;">*</span> :</label>
								<input type="text" id="department_code" name="department_code" class="form-control" required/>
							</div>
							<div class="form-group">
								<label class="control-label">Department Name <span style="color:red;">*</span> :</label>
								<input type="text" name="department_name" id="department_name" class="form-control" required/>
								<span class="dup-check"></span>
							</div>
						
						<br/>
							<div class="form-group">
								<label class="control-label">Department Head Name / Email / Mobile.No:</label>
								<select id="department_head_name" name="department_head_name" class="form-control" />
									<option value=''>Select Department Head</option>
									
									
								</select>
							</div>
							<!--<div class="form-group">
								<label class="control-label">Employee Code:</label>
								<input type="text" name="dept_employee_code" id="dept_employee_code" class="form-control" />
								<span class="dup-check"></span>
							</div>-->
							<div class="form-group">
								
								<input type="text" id="dept_email" name="dept_email" class="form-control" />
							</div>
							<div class="form-group">
								
								<input type="text" name="dept_mobile_no" id="dept_mobile_no" class="form-control" />
								
							</div> 
						
						<br/>
							<div class="form-group">
								<label class="control-label">Superior Head Name / Email / Mobile.No:</label>
								
								
									<option value=''>Select Superior Head</option>
									<?php 
									 	$sup_query = "select * from tv_employees ".login_check." and status=0 and role='SP'";
										$sup_result = mysqli_query($conn,$sup_query);
										$sup_row = mysqli_fetch_array($sup_result);
										?>
									<input type="text" value="<?php echo $sup_row['employee_name']; ?>" class="form-control" disabled>
									
							</div>
							<!--<div class="form-group">
								<label class="control-label">Employee Code:</label>
								<input type="text" name="sup_employee_code" id="sup_employee_code" class="form-control" />
								<span class="dup-check"></span>
							</div>-->
								<?php
								$sup_head = get_password($conn,$sup_row['employee_id']);
								
								?>
							<div class="form-group">
								<input type="text" id="sup_email" name="sup_email" value="<?php echo $sup_row['email']; ?>" class="form-control" disabled/>
							</div>
							<div class="form-group">
								
								<input type="text" name="sup_mobile_no" id="sup_mobile_no" value="<?php echo $sup_row['mobile_no'] ?>" disabled class="form-control"  />
								
							</div> 
						</div>
				 </div><br/>
				   <div class="row">
					<div class="col-md-12 form-action">
						<button class="btn btn-primary" type="button" id="save">Submit</button>
						<button class="btn btn-default-outline  btn-reset" type="button">Cancel</button>
					</div>
				  </div>
				</form>
			  </div>
			</div>
		  </div>
		  <div class=" col-md-9 master_right">
					<div class="widget-container fluid-height clearfix">
						<div class="heading"> <i class="fa fa-table" ></i> List of Departments </div>
					<div class="widget-content padded clearfix new_dept">
						<table class="table table-bordered table-striped" id="dataTable1">
							<thead>
								<th class="table-title" style="width:10%">S.No</th>
								<th class="table-title" style="width:10%">Department Code</th>
								<th class="table-title" style="width:30%">Department Name</th>
								<th class="table-title" style="width:20%">Department Head</th>
								<th class="table-title" style="width:20%">Superior Head</th>
								<th class="table-title" style="width:10%">Action</th>              
							</thead>
							<tbody>
							<?php 
								$query = "select * from tv_departments ".login_check."";
								$result = mysqli_query($conn,$query);
									$i=1;
								while($row = mysqli_fetch_array($result))
								{
									$dept_head = get_dept_head($conn,$row['department_id']);
									$sup_head = get_sup_head($conn,$_SESSION['company_id']);
									
							?>
								<tr>
									<td class="text-center"><?php echo $i; ?></td>
									<td><?php echo $row['department_code']; ?></td>
									<td><?php echo $row['department_name']; ?></td>
									<td><?php echo $dept_head['employee_name'] ?></td>
									<td><?php echo $sup_head['employee_name']; ?></td>
									<td class="actions center-content ">
										<div class="action-buttons">
											<a title="Edit" class="table-actions btn-edit" id="<?php echo $row['department_id']; ?>"><i class="fa fa-pencil"></i></a>
											<?php 
											if($row['status'] == 0)
											{
											?>
											<a class="table-actions btn-active" data-status="<?php echo $row['status']  ?>" title="InActive" id="<?php echo $row['department_id'] ?>"><i class="fa fa-check"></i></a>
											<?php 
											}
											else
											{
											?>
											<a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status']  ?>" title="Active" id="<?php echo $row['department_id'] ?>"><i class="fa fa-times"></i></a>
											<?php 
											}
											?>
											<a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['department_id'] ?>"><i class="fa fa-trash-o"></i></a>
											
										</div>
									</td>
								</tr>
							<?php 
									$i++;
								}	
							?>
	
							</tbody>
						</table>
				
					</div>
					</div>
				</div>
		</div>
	

		<?php require_once("include/footer.php"); ?>
	</div>	

		
		<script type="text/javascript">
		$(document).ready(function(){
			$('#department_head_name,#superior_head_name,#dept_employee_code,#dept_mobile_no,#dept_email,#sup_employee_code,#sup_mobile_no,#sup_email').prop("disabled",true)
		//Duplication
		var dup_chk = true;
		function duplicate_check(){
			var department_name = $("#department_name").val();
			var edit_id = $("#edit_id").val();
			$.ajax({
				cache: false,
				url: 'check_existing.php', // url where to submit the request
				type : "GET", //type of action POST || GET
				dataType : 'json',// data type
				async: false,
				data : {cmd: "chk_department",department_name:department_name, edit_id: edit_id}, // post data || get data
				success : function(result) {
				      $(".form-data-saving").hide();
					dup_chk = true;
					console.log(result);
					if(result[0] == 1){
						$(".dup-check").html(result[1]).css("color","#f00");
						dup_chk = false;
					}
					else{
						$(".dup-check").html(result[1]).css("color","green");
					}
				},
				error: function(jqxhr) {
					console.log(jqxhr.responseText);
				}
			});
		}
		
		//Onchange
		$(document).on('change','#department_head_name',function(){
			var head_id = $(this).val();
			$.ajax({
				url:"fetch_details.php",
				type:"post",
				dataType:"json",
				data:{cmd:"get_department_head_details",head_id:head_id},
				success:function(result){
					console.log(result);
					$('#dept_employee_code').val(result['employee_code']);
					$('#dept_mobile_no').val(result['mobile_no']);
					$('#dept_email').val(result['head_email']);
				}
			});
			
		});
	/*	$(document).on('change','#superior_head_name',function(){
			var head_id = $(this).val();
			$.ajax({
				url:"fetch_details.php",
				type:"post",
				dataType:"json",
				data:{cmd:"get_superior_head_details",head_id:head_id},
				success:function(result){
					console.log(result);
					$('#sup_employee_code').val(result['employee_code']);
					$('#sup_mobile_no').val(result['mobile_no']);
					$('#sup_email').val(result['head_email']);
				}
			});
			
		});*/
		//button Save
			$(document).on('click','#save',function(){
				var data = $('#department_form').serialize();
				duplicate_check();
				if($('#department_form').valid() == true && dup_chk)
				{
					$(this).attr("disabled",true);
					$.ajax({
						url:"save_details.php",
						type:"post",
						dataType:"json",
						data:data,
						success:function(result){
							console.log(result);
							if(result['dept_name'] != "" && result['sup_name'] != ""){
								$('.sup_head_dept_head').show();
								$('.head_details').html(result['details']);
							}
							else if(result['dept_name'] != ""){
								
								$('.dept_head').show();
								$('.dept_emp_name').text(result['dept_emp_name']);
								$('.dept_name').text(result['dept_name']);
							}
							else if(result['sup_name'] != ""){

								$('.sup_head').show();
								$('.sup_emp_name').text(result['sup_emp_name']);
								$('.sup_name').text(result['sup_name']);
							}
							
							else if(result['result'] == 1){
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
							ewToast(jqxhr.responseText, 'error');
						}
					});
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
				$.post('save_details.php', { form_name: "del_department", tbl_id: $(this).attr("id") }, function(data,status){	
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
				$.post('save_details.php', { form_name: "inacv_department", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
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
					data : { cmd: "get_department_details", tbl_id: tbl_id }, // post data || get data
					success : function(result) {
					console.log(result);
						$(".form-data-saving").hide();
						$("#form_name").val("edit_department");
						$("#edit_id").val(result['department_id']);
						$("#department_code").val(result['department_code']);
						$('#department_name').val(result['department_name']);
						$('#department_head_name').html(result['dept_head_name']).prop("disabled",false);
						$('#department_head_name').val(result['dept_head_id']).prop("disabled",false);
						$('#dept_employee_code').val(result['dept_head_employee_code']).prop("disabled",false);
						$('#dept_mobile_no').val(result['dept_head_mobile']).prop("disabled",false);
						$('#dept_email').val(result['dept_head_email']).prop("disabled",false);
						$('#superior_head_name').html(result['dept_head_name']).prop("disabled",false);
						$('#superior_head_name').val(result['sup_head_name']).prop("disabled",false);
						$('#sup_employee_code').val(result['sup_head_employee_code']).prop("disabled",false);
						$('#sup_mobile_no').val(result['sup_head_mobile']).prop("disabled",false);
						$('#sup_email').val(result['sup_head_email']).prop("disabled",false);
					},
					error: function(jqxhr) {
						ewToast(jqxhr.responseText, 'error');
					}
				});
			});

			
			//Button Reset
			$(document).on('click', '.btn-reset', function(ev){
				$('#form_name').val('add_city');
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
		<div class="dept_head" style="display:none">
		    <div class="popup_overlay" id="popup_overlay"></div>
			<div class="popup" id="popup">
			    <div class="popup_message">
			    <h5 class="popup-title">Notification:</h5>
				   Please note that "Department Head" is changed from <span class="dept_emp_name"></span> to <span class="dept_name"></span> Now  <span class="dept_emp_name"></span> will remain as employee  <br/> &nbsp; <br/>
			    <button class="btn btn-sm btn-danger close-popup" id="">Close</button> <br/> &nbsp; <br/>
			    </div>
			    <!--<span class="popup_close" id="popup_close">X</span>-->
			</div>
		</div>
			<div class="sup_head" style="display:none">
		    <div class="popup_overlay" id="popup_overlay"></div>
			<div class="popup" id="popup">
			    <div class="popup_message">
			      <h5 class="popup-title">Notification:</h5>
				   Please note that "Superior Head" is changed from <span class="sup_emp_name"></span> to <span class="sup_name"></span> Now  <span class="sup_emp_name"></span> will remain as employee  <br/> &nbsp; <br/>
			    <button class="btn btn-sm btn-danger close-popup" id="">Close</button> <br/> &nbsp; <br/>
			    </div>
			    <!--<span class="popup_close" id="popup_close">X</span>-->
			</div>
		</div>
			<div class="sup_head_dept_head" style="display:none">
		    <div class="popup_overlay" id="popup_overlay"></div>
			<div class="popup" id="popup">
			    <div class="popup_message">
			    <h5 class="popup-title">Notification:</h5>
				  Department & Superior head changed, details are as follows <br/> &nbsp; <br/>
				  <table class="table table-bordered">
					<thead>
						<tr>
							<td class="table-title">Previous Head</td>
							<td class="table-title">Now Head</td>
						</tr>
					</thead>
					<tbody class="head_details">
						
					<tbody>
				  </table>
			    <button class="btn btn-sm btn-danger close-popup" id="">Close</button> <br/> &nbsp; <br/>
			    </div>
			    <!--<span class="popup_close" id="popup_close">X</span>-->
			</div>
		</div>
  </body>
</html>