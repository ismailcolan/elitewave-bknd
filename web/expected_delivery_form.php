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
			  <div class="heading"> <i class="fa fa-plus"></i>Add Expected Delivery <span class="align-right"><i class="fa fa-plus"></i><a href="expected_delivery_list.php">View List</a></span></div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="add_expected_delivery_form">
				
				<?php if($_REQUEST['key'] != ''){?>
					<input type="hidden" id="form_name" name="form_name" value="edit_expected_delivery">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $_REQUEST['key']; ?>">
					<?php } else{?>
					<input type="hidden" id="form_name" name="form_name" value="expected_delivery">
					<input type="hidden" id="edit_id" name="edit_id" value="">
			       <?php
				}?>
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				<br/>
				 <div class="row">
						<div class="col-md-offset-1 col-md-5">
						<?php
							//$conn = mysqli_connect("localhost","root","","bookconsignment");
							$query = "select * from expectded_delivery where md5(id)='".$_REQUEST['key']."'";
							$result = mysqli_query($conn,$query);
							$row = mysqli_fetch_array($result);
							
							//$unique_billing_code = sprintf("%02d",1).'-'.$billing_code;

						?>
							<div class="form-group">
								<label class="control-label">Origin <span style="color:red;">*</span> :</label>
								<input type="text" autocomplete="off" id="origin" name="origin" value="<?php echo $row['origin']; ?>" class="form-control" required placeholder="000001-Origin Name"/>
								<span id="origin_dup-check"></span>
							</div>
							<div class="form-group">
								<label class="control-label">Destination <span style="color:red;">*</span> :</label>
								<input type="text" autocomplete="off"   name="destination" id="destination" value="<?php echo $row['destination']; ?>" class="form-control" required placeholder="000001-Destination Name"/>
								<span id="destination_dup-check"></span>
							</div>
							<div class="form-group">
								<label class="control-label">Surface:</label>
								<input type="text" autocomplete="off" name="surface" id="surface" value="<?php echo $row['surface']; ?>" class="form-control" placeholder="Enter Surface Duration"/>
								
							</div>
							<div class="form-group">
								<label class="control-label">Express:</label>
								<input type="text" autocomplete="off" name="express" id="express" class="form-control"  value="<?php echo $row['express']; ?>"placeholder="Enter Express Duration"/>
							
							</div>
							
							
						</div>
						<div class="col-md-5">
							
						<div class="form-group">
								<label class="control-label">Train:</label>
								<input type="text" autocomplete="off" name="train" id="train" class="form-control" value="<?php echo $row['train']; ?>"  placeholder="Enter Train Duration"/>
								<span class="dup-check"></span>
							</div>
							
							
							<div class="form-group">
								<label class="control-label">Air:</label>
								<input type="text" autocomplete="off" name="air" id="air" value="<?php echo $row['air']; ?>" class="form-control" placeholder="Enter Air Duration"/>
								<span class="email-dup-check"></span>
							</div>
							<div class="form-group">
								<label class="control-label">Note:</label>
								<textarea name="note" id="note" value="" class="form-control" value="" placeholder="Enter Note"><?php echo $row['note']; ?></textarea>
								<span class="dup-check"></span>
							</div>
						
						
						</div>
				 </div>
				   <div class="row">
					<div class="col-md-12 form-action">
					<?php if($_REQUEST['key']== ''){?>
						<button class="btn btn-primary" type="button" id="save">Submit</button>
						<button  class="btn btn-default-outline  btn-reset" type="button" onclick="window.location.href='expected_delivery_list.php';">Cancel</button>
					</div>
					<?php 
					}else{?>
						<button class="btn btn-primary" type="button" id="update">Update</button>
						<button  class="btn btn-default-outline  btn-reset" type="button" onclick="window.location.href='expected_delivery_list.php';">Cancel</button>
					<?php }?>
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
		
			var origin = $('#origin').val();
			var destination = $('#destination').val();
			// alert(email_check);
		$.ajax({
				cache: false,
				url: 'check_existing.php', // url where to submit the request
				type : "POST", //type of action POST || GET
				dataType : 'json',// data type
				async: false,
				data : {cmd: "chk_estimated_origin_destination",origin:origin, destination: destination, edit_id: edit_id}, // post data || get data
				success : function(result) {
				      $(".form-data-saving").hide();
					dup_chk = true;
					console.log(result);
					
					if(result[0] == "1"){
						
						$("#origin_dup-check").html(result[1]).css("color","#f00");
						$("#destination_dup-check").html(result[1]).css("color","#f00");
						dup_chk = false;
					}
					else{
						$("#origin_dup-check").html(result[1]).css("color","green");
						$("#destination_dup-check").html(result[1]).css("color","green");
					}
				},
				error: function(jqxhr) {
					console.log(jqxhr.responseText);
				}
			});

		}
			$('#surface,#express,#air,#train').keypress(function (event) {
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

		//button Save
			$(document).on('click','#save',function(){
				var data = $('#add_expected_delivery_form').serialize();
				//duplicate_check();
				if($('#add_expected_delivery_form').valid() == true)
				{
                    duplicate_check();
               		 if(dup_chk){
					$(this).attr("disabled",true);
					$.ajax({
						url:"https://elitewave360.in/php/save_details.php",
						type:"post",
						data:data,
						success:function(result){
							// console.log(result);
                        // alert(url);
                        // console.log(url);
							if(result != 0){
								$(".form-data-saving").hide();
								$("#alert-status").text("");
								$("#alert-message").text("Saved Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								location.href="expected_delivery_list.php";
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
				$.post('https://elitewave360.in/php/save_details.php', { form_name: "inacv_client", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
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
			

			//UPDATE

			$(document).on('click','#update',function(){
				//alert("update");
				var data = $('#add_expected_delivery_form').serialize();
				//var edit_id = $("#edit_id").val();
				//duplicate_check();
				if($('#add_expected_delivery_form').valid() == true)
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
								$("#alert-message").text("Data Updated Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								location.href="expected_delivery_list.php";
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
				}else{
					console.log("Something Went Wrong");
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