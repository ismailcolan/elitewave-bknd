<?php
require_once("include/connect.php");
require_once("include/function.php"); 

$key = $_REQUEST['key'];
if($key !=''){
	$client_query = "select * from client where md5(client_id)='".$key."'";
	$client_result = mysqli_query($conn,$client_query);
	$client_count = mysqli_num_rows($client_result);
	if($client_count == 0){
		header('Location:client_list.php');
	}
}
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
		  <div class="col-md-offset-1 col-md-10">
			<div class="widget-container fluid-height clearfix">
			  <div class="heading"> <i class="fa fa-plus"></i> Loading Sheet  <span class="align-right"><i class="fa fa-plus"></i><a href="pickup_list.php">View List</a></span></div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="pickup_form">
				
					<input type="hidden" id="form_name" name="form_name" value="request_for_new_pickup_for_existing_client">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $_REQUEST['key']; ?>">
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<fieldset class="my-fieldset">
						
								<div class="row">
								
									<div class="col-md-offset-1 col-md-4">
									<div class="form-group">
										<label class="control-label col-sm-4">Sheet No <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										<?php 
											if($_REQUEST['key']!=''){
										?>
											<input type="text" id="grn_no" value="<?php echo $row['grn_no']; ?>" name="grn_no" class="form-control" required disabled/>
										<?php 
										}
										else{
										?>
										<input type="text" id="grn_no" value="Ex.LOD/000001" name="grn_no" class="form-control" required disabled/>
										<?php 
										}
										?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Date <span style="color:red;">*</span> :</label>
										<div class="input-group date datepicker date-picker table-height" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
											<input class="form-control table-height final" type="text" name="grn_date" value="<?php if($row['grn_date']!='')
											echo $row['grn_date']; else echo date('d-m-Y'); ?>"  id="grn_date" required> <span class="input-group-addon table-height"><i class="fa fa-calendar"></i></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Loading HUB <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<select name="origin" id="origin" class="form-control">
												<option value="">Select Origin</option>
												<?php 
													$city_query ="select * from city where status=0";
													$city_result = mysqli_query($conn,$city_query);
													while($city_row = mysqli_fetch_array($city_result))
													{
												?>
												<option value="<?php echo $city_row['city_id']; ?>" <?php if($city_row['city_id']==$row['origin']) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
												<?php
													}
												?>
											</select>
										</div>
									</div>
									
								</div>
								
								<div class="col-md-4">
									<div class="form-group">
										<label class="control-label col-sm-4">Mode <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										
											<select name="mode_of_trasport" id="mode_of_trasport" class="form-control">
												<option value="">Mode of Transport</option>
												<?php 
													$transport_query ="select * from mode_of_transportation where status=0";
													$transport_result = mysqli_query($conn,$transport_query);
													while($transport_row = mysqli_fetch_array($transport_result))
													{
												?>
												<option value="<?php echo $transport_row['mode_id']; ?>" <?php if($transport_row['mode_id']==$row['mode_of_transportation']) echo "selected"; ?>><?php echo $transport_row['mode_type']; ?></option>
												<?php
													}
												?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Unloading HUB <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<select name="destination" id="destination" class="form-control">
												<option value="">Select Destination</option>
												<?php 
													$city_query ="select * from city where status=0";
													$city_result = mysqli_query($conn,$city_query);
													while($city_row = mysqli_fetch_array($city_result))
													{
												?>
												<option value="<?php echo $city_row['city_id']; ?>" <?php if($city_row['city_id']==$row['destination']) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
												<?php
													}
												?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Source <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<select name="mode_of_consignment" id="mode_of_consignment" class="form-control">
												<option value="">Select Source </option>
												
											</select>
										</div>
									</div>
								</div>
							  <div class="col-md-2" style="text-align:center;">
							  Total Packages<br><span id="total_package" style="color: #0A1E3D;font-size:  50px;">0</span>
							  
								</div>
					 </div>
					 </fieldset>
					
					<fieldset class="my-fieldset">
							<legend>GRN Information</legend>
								<div class="row">
								
									<div class="col-md-5">
									<div class="form-group">
										<label class="control-label col-sm-4">GRN.No <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										<?php 
											if($_REQUEST['key']!=''){
										?>
											<input type="text" id="grn_no" value="<?php echo $row['grn_no']; ?>" name="grn_no" class="form-control" required />
										<?php 
										}
										else{
										?>
										<input type="text" id="grn_no" value="Ex.GEO/000001" name="grn_no" class="form-control" required />
										<?php 
										}
										?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">GRN.Date <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										<input type="text" id="grn_no" value="Ex.GEO/000001" name="grn_no" class="form-control" required />
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Mode of Transportation <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										
											<select name="mode_of_trasport" id="mode_of_trasport" class="form-control">
												<option value="">Modeof Transport</option>
												<?php 
													$transport_query ="select * from mode_of_transportation where status=0";
													$transport_result = mysqli_query($conn,$transport_query);
													while($transport_row = mysqli_fetch_array($transport_result))
													{
												?>
												<option value="<?php echo $transport_row['mode_id']; ?>" <?php if($transport_row['mode_id']==$row['mode_of_transportation']) echo "selected"; ?>><?php echo $transport_row['mode_type']; ?></option>
												<?php
													}
												?>
											</select>
										</div>
									</div>
								</div>
								
								<div class="col-md-5">
									<div class="form-group">
										<label class="control-label col-sm-4">ORIGIN <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<select name="origin" id="origin" class="form-control">
												<option value="">Select Origin</option>
												<?php 
													$city_query ="select * from city where status=0";
													$city_result = mysqli_query($conn,$city_query);
													while($city_row = mysqli_fetch_array($city_result))
													{
												?>
												<option value="<?php echo $city_row['city_id']; ?>" <?php if($city_row['city_id']==$row['origin']) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
												<?php
													}
												?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Destination <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<select name="destination" id="destination" class="form-control">
												<option value="">Select Destination</option>
												<?php 
													$city_query ="select * from city where status=0";
													$city_result = mysqli_query($conn,$city_query);
													while($city_row = mysqli_fetch_array($city_result))
													{
												?>
												<option value="<?php echo $city_row['city_id']; ?>" <?php if($city_row['city_id']==$row['destination']) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
												<?php
													}
												?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Mode of Consignment <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<select name="mode_of_consignment" id="mode_of_consignment" class="form-control">
												<option value="">Select Consignment</option>
												<?php 
													$consignment_query ="select * from consignment_mode where status=0";
													$consignment_result = mysqli_query($conn,$consignment_query);
													while($consignment_row = mysqli_fetch_array($consignment_result))
													{
												?>
												<option value="<?php echo $consignment_row['consignment_id']; ?>" <?php if($consignment_row['consignment_id']==$row['mode_of_consignment']) echo "selected"; ?>><?php echo $consignment_row['consignment_mode']; ?></option>
												<?php
													}
												?>
											</select>
										</div>
									</div>
								</div>
							  
					 </div>
					 </fieldset>
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

		</div>
	</div>

		<?php require_once("include/footer.php"); ?>
	</div>	

		
		<script type="text/javascript">
		$(document).ready(function(){

			$('#pincode,#contact_no').keypress(function (event) {
					return isNumber(event, this)
				});
				
			function isNumber(evt, element) {
				var charCode = (evt.which) ? evt.which : event.keyCode

				if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&      // “-” CHECK MINUS, AND ONLY ONE.
					(charCode != 46 || $(element).val().indexOf('.') != -1) &&      // “.” CHECK DOT, AND ONLY ONE.
					(charCode < 48 || charCode > 57))
					return false;
					return true;
			} 
	$(document).on('change','#state',function(){
		var state_id = $(this).val();
		//alert(state_id);
		$.ajax({
			url:'fetch_details.php',
			type:"post",
			data:{cmd:"get_city_name",state_id:state_id},
			success:function(result){
				console.log(result);
				$('#city').html(result);
			}
		});
	});
	
		$(document).on('change','#transit_automation',function(){
				if($(this).is(':checked')){
					$(this).val('1');
				}
				else{
					$(this).val('0');
				}
		});
		$(document).on('change','#multiple_branches',function(){
				if($(this).is(':checked')){
					$(this).val('1');
				}
				else{
					$(this).val('0');
				}
		});
		//button Save
			$(document).on('click','#save',function(){
				var data = $('#pickup_form').serialize();
				if($('#pickup_form').valid() == true)
				{
					ewToast(data, 'info')
					$(this).attr("disabled",true);
					$.ajax({
						url:"save_details.php",
						type:"post",
						data:data,
						success:function(result){
							console.log(result);
							if(result == 1){
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
				$.post('save_details.php', { form_name: "del_client", tbl_id: $(this).attr("id") }, function(data,status){	
				console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Client Deleted successfully...");
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
						$("#alert-message").text("Client deletion failed");
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
				$.post('save_details.php', { form_name: "inacv_client", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
					console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Client Is "+msg+"...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					
					else if(data == 2){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Client Is "+msg+"...");
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
					data : { cmd: "get_client_details", tbl_id: tbl_id }, // post data || get data
					success : function(result) {
					console.log(result);
						$(".form-data-saving").hide();
						$("#form_name").val("edit_client");
						$("#edit_id").val(result['client_id']);
						$("#company_name").val(result['client_company_name']);
						$('#contact_person').val(result['contact_person']);
						$("#address1").val(result['address1']);
						$('#address2').val(result['address2']);
						$("#state").val(result['state']);
						$('#city').val(result['city']);
						$("#pincode").val(result['pincode']);
						$('#email').val(result['email']);
						$('#contact_no').val(result['contact_no']);
						$('#gst_no').val(result['gst_no']);
						$('#pan_no').val(result['pan_no']);
						$('#transit_automation').val(result['automation']);
						$('#multiple_branches').val(result['multiple_branches']);
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