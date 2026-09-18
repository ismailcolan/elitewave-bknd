<?php
require_once("include/connect.php");
require_once("include/function.php"); 

$key = $_REQUEST['key'] ?? '';
if ($key === '' && empty($_GET['create'])) {
	header('Location:consigner_payment_list.php');
	exit;
}
if ($key != '') {
	$payment_check = mysqli_query($conn, "select id from consignor_payment where md5(id)='" . mysqli_real_escape_string($conn, $key) . "'");
	if (!$payment_check || mysqli_num_rows($payment_check) == 0) {
		header('Location:consigner_payment_list.php');
		exit;
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
		  <div class="col-md-12">
			<div class="ew-page-v2">
			  <div class="ew-page-head">
				<div class="ew-page-head-left">
				  <a href="consigner_payment_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
				  <h1 class="ew-page-title"><?php echo ($_REQUEST['key'] != '') ? 'Edit Charges' : 'Add Charges'; ?></h1>
				</div>
				<div class="ew-toolbar-right">
				  <a href="consigner_payment_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
				</div>
			  </div>
			  <div class="ew-card">
				<h2 class="ew-card-section-title">Client Charge Configuration</h2>
				<div class="ew-form-body">
				<form id="add_payment_info_form">
				
				<?php if($_REQUEST['key'] != ''){?>
					<input type="hidden" id="form_name" name="form_name" value="edit_payment_info_form">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $_REQUEST['key']; ?>">
					<?php } else{?>
					<input type="hidden" id="form_name" name="form_name" value="payment_info_form">
					<input type="hidden" id="edit_id" name="edit_id" value="">
			       <?php
				}?>
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="ew-form-grid">
						<?php
							$query = "select * from consignor_payment where md5(id)='".$_REQUEST['key']."'";
							$result = mysqli_query($conn,$query);
							$row = mysqli_fetch_array($result);
						?>
						<div class="ew-field">
							<label class="control-label">Client <span style="color:red;">*</span> :</label>
							<select name="consigner_id" id="consigner_id" class="form-control" required>
								<option value="">Select Client</option>
							<?php
							$query = "select * from client";
							$result = mysqli_query($conn,$query);
							while($row1 = mysqli_fetch_array($result)){?>
								<option value="<?php echo $row1['client_id'];?>"<?php if($row1['client_id'] == $row['consigner_id']) echo "selected";?>><?php echo $row1['client_company_name'];?></option>
							<?php } ?>
							</select>
							<span id="client_dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Destination <span style="color:red;">*</span> :</label>
							<select name="city" class="form-control" id="city" required>
								<option value="">Select Destination</option>
								<?php 
									$city_query ="select * from city where status=0 order by city_name";
									$city_result = mysqli_query($conn,$city_query);
									while($city_row= mysqli_fetch_array($city_result)){
								?>
								<option value="<?php echo $city_row['city_id']; ?>"<?php if($row['destination'] == $city_row['city_id']) echo "selected";?>><?php echo $city_row['city_name']; ?></option>
								<?php } ?>
							</select>
							<span id="destination_dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Loading / Unloading Charges:</label>
							<input type="text" name="loading_unloading_chrgs" id="loading_unloading_chrgs" value="<?php echo $row['loading_unloading_chrgs']; ?>" class="form-control" placeholder="Enter Loading Unloading Chrgs" autocomplete="off"/>
						</div>
						<div class="ew-field">
							<label class="control-label">Crane / Lift_Fork Charges:</label>
							<input type="text" name="crane_fork_lift_chrgs" id="crane_fork_lift_chrgs" value="<?php echo $row['crane_fork_lift_chrgs']; ?>" class="form-control" placeholder="Enter Crane / Fork Lift Chrgs" autocomplete="off" />
						</div>
						<div class="ew-field">
							<label class="control-label">Document Charges:</label>
							<input type="text" name="doc_chrgs" id="doc_chrgs" class="form-control" value="<?php echo $row['doc_chrgs']; ?>" placeholder="Enter Docs Chrgs" autocomplete="off" />
						</div>
						<div class="ew-field">
							<label class="control-label">Labour Charges:</label>
							<input type="text" name="labour_charges" id="labour_charges" class="form-control" value="<?php echo $row['labour_charges']; ?>" placeholder="Enter Labour Chrgs" autocomplete="off" />
						</div>
						<div class="ew-field">
							<label class="control-label">Other Charges:</label>
							<input type="text" name="other_chrgs" id="other_chrgs" class="form-control" value="<?php echo $row['other_chrgs']; ?>" placeholder="Enter Other Chrgs" autocomplete="off" />
							<span class="dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Air:</label>
							<input type="text" name="air" id="air" value="<?php echo $row['air']; ?>" class="form-control" placeholder="Enter Air Chrgs" autocomplete="off" />
							<span class="dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Train:</label>
							<input type="text" name="train" id="train" value="<?php echo $row['train']; ?>" class="form-control" placeholder="Enter Train Chrgs" autocomplete="off" />
							<span class="dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Surface PTL:</label>
							<input type="text" name="ptl" id="ptl" value="<?php if($row['ptl'] != '') echo $row['ptl'] ; else echo 3850; ?>" class="form-control" placeholder="Enter Part Truck Load Chrgs" autocomplete="off"/>
							<span class="dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Express:</label>
							<input type="text" name="express" id="express" value="<?php echo $row['express']; ?>" class="form-control" placeholder="Enter Express Chrgs" autocomplete="off"/>
							<span class="dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Local Delivery:</label>
							<input type="text" name="local_delivery" id="local_delivery" value="<?php echo $row['local_delivery']; ?>" class="form-control" placeholder="Enter Local Delivery Chrgs" autocomplete="off"/>
							<span class="dup-check"></span>
						</div>
				 </div>
				</form>
				</div>
				<div class="ew-form-footer">
					<a class="ew-btn-v2 ew-btn-v2-outline btn-reset" href="consigner_payment_list.php">Cancel</a>
					<?php if($_REQUEST['key']== ''){?>
						<button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="save">Submit</button>
					<?php } else { ?>
						<button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="update">Update</button>
					<?php } ?>
				</div>
			  </div>
			</div>
		  </div>

		</div>
	</div>

		<?php require_once("include/footer.php"); ?>
	</div>	

		
		<script type="text/javascript">
		$(document).ready(function(){
			$('#loading_unloading_chrgs,#crane_fork_lift_chrgs,#doc_chrgs,#labour_charges,#other_chrgs,#ptl,#surface,#express,#air,#train,#local_delivery').keypress(function (event) {
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

				//Duplication
		var dup_chk = true;
	   function duplicate_check(){
			/* var key = e.keyCode;
                     if (key >= 48 && key <= 57) {
                        e.preventDefault();
                    } */
			var edit_id = $('#edit_id').val();
		
			var client_idd = $('#consigner_id').val();
			var destination = $('#city').val();
			// alert(email_check);
		$.ajax({
				cache: false,
				url: 'check_existing.php', // url where to submit the request
				type : "POST", //type of action POST || GET
				dataType : 'json',// data type
				async: false,
				data : {cmd: "chk_consingor_destination",client_idd:client_idd, destination: destination, edit_id: edit_id}, // post data || get data
				success : function(result) {
				      $(".form-data-saving").hide();
					dup_chk = true;
					console.log(result);
					
					if(result[0] == "1"){
						
						$("#client_dup-check").html(result[1]).css("color","#f00");
						$("#destination_dup-check").html(result[1]).css("color","#f00");
						dup_chk = false;
					}
					else{
						$("#client_dup-check").html(result[1]).css("color","green");
						$("#destination_dup-check").html(result[1]).css("color","green");
					}
				},
				error: function(jqxhr) {
					console.log(jqxhr.responseText);
				}
			});

		}

		//button Save
			$(document).on('click','#save',function(){
               
				var data = $('#add_payment_info_form').serialize();
				
				if($('#add_payment_info_form').valid() == true)
				{
                    duplicate_check();
                if(dup_chk){
					$(this).attr("disabled",true);
					$.ajax({
						url:"https://elitewave360.in/php/save_details.php",
						type:"post",
						data:data,
						success:function(result){
							console.log(result);
							if($.trim(result) == "1"){
								$(".form-data-saving").hide();
								$("#alert-status").text("");
								$("#alert-message").text("Saved Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								location.href="consigner_payment_list.php";
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
		
			//UPDATE

			$(document).on('click','#update',function(){
				
				var data = $('#add_payment_info_form').serialize();
				//var edit_id = $("#edit_id").val();
				//duplicate_check();
				if($('#add_payment_info_form').valid() == true)
				{
					$(this).attr("disabled",true);
					$.ajax({
						url:"https://elitewave360.in/php/save_details.php",
						type:"post",
						data:data,
						success:function(result){
							console.log(result);
							if($.trim(result) == "1"){
								$(".form-data-saving").hide();
								$("#alert-status").text("");
								$("#alert-message").text("Data Updated Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								location.href="consigner_payment_list.php";
								});
							}
							else
							{
								$(".form-data-saving").hide();
								$("#alert-status").text("Alert !!! ");
								$("#alert-message").text("Data Update Failed");
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