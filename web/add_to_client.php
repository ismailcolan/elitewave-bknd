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
			  <div class="heading"> <i class="fa fa-plus"></i>Client  <span class="align-right"><i class="fa fa-plus"></i><a href="client_list.php">View List</a></span></div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="add_client_form">
				
					<input type="hidden" id="form_name" name="form_name" value="consignor_to_client">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $_REQUEST['key']; ?>"> 
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				<br/>
				 <div class="row">
						<div class="col-md-offset-1 col-md-5">
						<?php
						//$conn = mysqli_connect("localhost","root","","bookconsignment");
							$query = "select * from user_inquiry_list where md5(user_id)='".$_REQUEST['key']."'";
							$result = mysqli_query($conn,$query);
							$row = mysqli_fetch_array($result);
							$consignor_name = $row['consignor_name'];
							$billing_code = substr($consignor_name , 0,4);
							//$unique_billing_code = sprintf("%02d",1).'-'.$billing_code;

						?>
							<div class="form-group">
								<label class="control-label">Client Company Name <span style="color:red;">*</span> :</label>
								<input type="text" id="company_name" name="company_name" value="<?php echo $row['consignor_name']; ?>" class="form-control" required/>
							</div>
							<div class="form-group">
								<label class="control-label">Contact Person <span style="color:red;">*</span> :</label>
								<input type="text" name="contact_person" id="contact_person" value="<?php echo $row['consignor_name']; ?>" class="form-control" required/>
								<span class="dup-check"></span>
							</div>
							<div class="form-group">
								<label class="control-label">Address1 <span style="color:red;">*</span> :</label>
								<input type="text" name="address1" id="address1" class="form-control" value="<?php echo $row['consignor_address']; ?>" required/>
								<span class="dup-check"></span>
							</div>
							<div class="form-group">
								<label class="control-label">Address2:</label>
								<input type="text" name="address2" id="address2" value="" class="form-control"/>
								<span class="dup-check"></span>
							</div>
							<div class="form-group">
								<label class="control-label">State <span style="color:red;">*</span> :</label>
								<select name="state" id="state" class="form-control">
								<option value="">Select State</option>
								<?php
								$state = mysqli_query($conn,"select *from state where status = 0 order by state_name");
								while($state_list = mysqli_fetch_assoc($state)){?>
							    <option value="<?php echo $state_list['state_id'];?>"><?php echo $state_list['state_name'];?></option>
								<?php
								}
								?>	
								</select>
								<!-- <input type="text" name="state" id="state" value="" class="form-control"/> -->
								<span class="dup-check"></span>
							</div>
							<div class="form-group">
								<label class="control-label">City <span style="color:red;">*</span> :</label>
								<select name="city" id="city" class="form-control">
								<option value="">Select City</option>
								<?php
								$city = mysqli_query($conn,"select *from city where status = 0 order by city_name");
								while($city_list = mysqli_fetch_assoc($city)){
								?>
								<option value="<?php echo $city_list['city_id'];?>"<?php if($row['consignor_city'] == $city_list['city_id']) echo "selected";?>><?php echo $city_list['city_name'];?></option>
								<?php
								}
								?>	

								</select>
								<!-- <input type="text" name="city" id="city" value="<?php //echo $row['consignor_city']; ?>" class="form-control"/> -->
								<span class="dup-check"></span>
							</div>
						</div>
						<div class="col-md-5">
							
							<div class="form-group">
								<label class="control-label">Client Code <span style="color:red;">*</span> :</label>
								<input type="text" style="text-transform:uppercase" maxlength="4" name="billing_code" id="billing_code" value="<?php echo strtoupper($billing_code); ?>" class="form-control" required />
								<span class="bill_dup-check"></span>
								
							</div>
							
							<div class="form-group">
								<label class="control-label">Pincode:</label>
								<input type="text" name="pincode" id="pincode" value="" class="form-control"/>
							
							</div>
							<div class="form-group">
								<label class="control-label">Email <span style="color:red;">*</span> :</label>
								<input type="email" name="email" id="email" value="<?php echo $row['consignor_email']; ?>" class="form-control" required readonly/>
								<span class="email-dup-check"></span>
							</div>
						<div class="form-group">
								<label class="control-label">Contact No <span style="color:red;">*</span> :</label>
								<input type="text" name="contact_no" id="contact_no" value="<?php echo $row['consignor_contact']; ?>" class="form-control" required/>
								<span class="dup-check"></span>
							</div>
						
						<div class="form-group">
								<label class="control-label">GST No <span style="color:red;">*</span> :</label>
								<input type="text" name="gst_no" id="gst_no" class="form-control" value="" required/>
								<span class="dup-check"></span>
							</div>
						
						<div class="form-group">
								<label class="control-label">PAN No <span style="color:red;">*</span> :</label>
								<input type="text" name="pan_no" id="pan_no" class="form-control" value="" required/>
								<span class="dup-check"></span>
							</div>
						<!---
						<div class="form-group">
								<input type="checkbox" name="multiple_branches"  id="multiple_branches"  />
							<label class="control-label">Click, If client have multiple branches:</label>
							</div>
						
						<div class="form-group">
								<input type="checkbox" name="transit_automation" id="transit_automation"/>
							<label class="control-label">In Transit Automation, Not Required.:</label>
							</div>
						-->
						</div>
				 </div>
				   <div class="row">
					<div class="col-md-12 form-action">
						<button class="btn btn-primary" type="button" id="save">Submit</button>
						<button  class="btn btn-default-outline  btn-reset" type="button" onclick="window.location.href='user-inquiry.php';">Cancel</button>
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

		//Duplication
		var dup_chk = true;
	   function duplicate_check(){
			/* var key = e.keyCode;
                    if (key >= 48 && key <= 57) {
                        e.preventDefault();
                    } */
			var edit_id = $('#edit_id').val();
		
			var billing_code = $('#billing_code').val();
			var email_check = $('#email').val();
			// alert(email_check);

			$.ajax({
				cache: false,
				url: 'check_existing.php', // url where to submit the request
				type : "GET", //type of action POST || GET
				dataType : 'json',// data type
				async: false,
				data : {cmd: "chk_email_exist",email_check:email_check, edit_id: edit_id}, // post data || get data
				success : function(result) {
				      $(".form-data-saving").hide();
					dup_chk = true;
					console.log(result);
					
					if(result[0] == 1){
						
						$(".email-dup-check").html(result[1]).css("color","#f00");
						dup_chk = false;
					}
					else{
						$(".email-dup-check").html(result[1]).css("color","green");
					}
				},
				error: function(jqxhr) {
					console.log(jqxhr.responseText);
				}
			});
			
		$.ajax({
				cache: false,
				url: 'check_existing.php', // url where to submit the request
				type : "GET", //type of action POST || GET
				dataType : 'json',// data type
				async: false,
				data : {cmd: "chk_billingcode",billing_code:billing_code, edit_id: edit_id}, // post data || get data
				success : function(result) {
				      $(".form-data-saving").hide();
					bill_chk = true;
					console.log(result);
					
					if(result[0] == 1){
						
						$(".bill_dup-check").html(result[1]).css("color","#f00");
						bill_chk = false;
					}
					else{
						$(".bill_dup-check").html(result[1]).css("color","green");
					}
				},
				error: function(jqxhr) {
					console.log(jqxhr.responseText);
				}
			});

			
		}
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
	// $(document).on('change','#state',function(){
	// 	var state_id = $(this).val();
	// 	//alert(state_id);
	// 	$.ajax({
	// 		url:'fetch_details.php',
	// 		type:"post",
	// 		data:{cmd:"get_city_name",state_id:state_id},
	// 		success:function(result){
	// 			//console.log(result);
	// 			$('#city').html(result);
	// 		}
	// 	});
	// });
	
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
				var data = $('#add_client_form').serialize();
				duplicate_check();
				if($('#add_client_form').valid() == true && dup_chk == true && bill_chk == true)
				{
					$(this).attr("disabled",true);
					$.ajax({
						url:"https://elitewave360.in/php/save_details.php",
						type:"post",
						data:data,
						success:function(result){
							console.log(result);
							if(result != 0){
								$(".form-data-saving").hide();
								$("#alert-status").text("");
								$("#alert-message").text("Saved Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								location.href="user-inquiry.php";
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