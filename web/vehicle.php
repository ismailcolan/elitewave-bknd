<?php
require_once("include/connect.php");
require_once("include/function.php"); 
$key = $_REQUEST['key'];
if($key !=''){
	$vehicle_query = "select * from vehicle where md5(vehicle_id)='".$key."'";
	$vehicle_result = mysqli_query($conn,$vehicle_query);
	$vehicle_count = mysqli_num_rows($vehicle_result);
	if($vehicle_count == 0){
		header('Location:vehicle_list.php');
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
			  <div class="heading"> <i class="fa fa-plus"></i>Vehicle <span class="align-right"> <i class="fa fa-table"></i><a href="vehicle_list.php">View List</a></span></div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="vehicle_form">
				<?php 
					$vehicle_query ="select * from vehicle where md5(vehicle_id)='".$key."'";
					$vehicle_result = mysqli_query($conn,$vehicle_query);
					$vehicle_row = mysqli_fetch_array($vehicle_result);
				?> 
					<input type="hidden" id="form_name" name="form_name" value="add_vehicle">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $key; ?>">
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<div class="col-md-offset-1 col-md-5">
							<div class="form-group">
								<label class="control-label">Vehicle Number <span style="color:red;">*</span> :</label>
								<input type="text" name="vehicle_number" id="vehicle_number" class="form-control" value="<?php echo $vehicle_row['vehicle_number']?>" required>
								<span id="vehicle_error"></span>
							</div>
							<div class="form-group">
								<label class="control-label">Vehicle Type <span style="color:red;">*</span> :</label>
								<input type="text" name="vehicle_type" id="vehicle_type" class="form-control" value="<?php echo $vehicle_row['vehicle_type']?>" required>
								
							</div>
							<div class="form-group">
								<label class="control-label">Model <span style="color:red;">*</span> :</label>
								<input type="text" class="form-control" name="model" id="model" value="<?php echo $vehicle_row['model']?>" required>
							</div>
							<div class="form-group">
								<label class="control-label">Fitness <span style="color:red;">*</span> :</label>
								<?php echo ew_date_input(array('id' => 'fitness', 'name' => 'fitness', 'value' => $vehicle_row['fitness'], 'required' => true, 'class' => 'table-height final')); ?>
							</div>
							<div class="form-group">
								<label class="control-label">Insurance <span style="color:red;">*</span> :</label>
								<?php echo ew_date_input(array('id' => 'insurance', 'name' => 'insurance', 'value' => $vehicle_row['insurance'], 'required' => true, 'class' => 'table-height final')); ?>
							</div>
							<div class="form-group">
								<label class="control-label">Road Tax <span style="color:red;">*</span> :</label>
								<?php echo ew_date_input(array('id' => 'road_tax', 'name' => 'road_tax', 'value' => $vehicle_row['road_tax'], 'required' => true, 'class' => 'table-height final')); ?>
							</div>
							</div>
							<div class="col-md-5">
							<div class="form-group">
								<label class="control-label">Permit <span style="color:red;">*</span> :</label>
								<?php echo ew_date_input(array('id' => 'permit', 'name' => 'permit', 'value' => $vehicle_row['permit'], 'required' => true, 'class' => 'table-height final')); ?>
							</div>
							<div class="form-group">
								<label class="control-label">Emission <span style="color:red;">*</span> :</label>
								<?php echo ew_date_input(array('id' => 'emission', 'name' => 'emission', 'value' => $vehicle_row['emission'], 'required' => true, 'class' => 'table-height final')); ?>
							</div>
							<div class="form-group">
								<label class="control-label">Pollution Certificate <span style="color:red;">*</span> :</label>
								<input type="text" class="form-control" name="pollution_certificate" id="pollution_certificate"value="<?php echo $vehicle_row['pollution_certificate']?>"  required>
							</div>
							<div class="form-group">
								<label class="control-label">Finance:</label>
								<input type="text" class="form-control" name="finance" id="finance" value="<?php echo $vehicle_row['finance']?>" >
							</div>
						<div class="form-group">
								<label class="control-label">Vehilce Status:</label>
								<input type="text" class="form-control" name="vehicle_status" id="vehicle_status" value="<?php echo $vehicle_row['vehicle_status']?>">
							</div>
							<div class="form-group">
								<label class="control-label">Registration:</label>
								<input type="radio" name="registration" id="registration" value="y" <?php if($vehicle_row['registration']=='y') echo "checked"; ?> >Yes
								<input type="radio" name="registration" id="registration" value="n" <?php if($vehicle_row['registration']=='n') echo "checked"; ?>>No
							</div>
						
						</div>
				 </div><br/>
				   <div class="row">
					<div class="col-md-12 form-action">
                    <?php if($_REQUEST['key']== ''){?>
						<button class="btn btn-primary" type="button" id="save">Submit</button>
						<a class="btn btn-default-outline  btn-reset" type="button" href="vehicle.php">Cancel</a>
					 <?php } else{?>
						<button class="btn btn-primary" type="button" id="save">Update</button>
						<a class="btn btn-default-outline  btn-reset" type="button" href="vehicle_list.php">Cancel</a>
						<?php }?>
					</div>
				  </div>
				</form>
			  </div>
			</div>
		  </div>

		</div>
	

		<?php require_once("include/footer.php"); ?>
	</div>	

		
		<script type="text/javascript">
		$(document).ready(function(){
			var chck_key=true;
	$(document).on('keyup','#vehicle_number',function(e){
			var vehicle_number = $(this).val();
			var edit_id = $("#edit_id").val();
			
			$.ajax({
					url:'check_existing.php',
					type:"GET",
					dataType:"JSON",
					data:{cmd:"chk_vehicle_no",vehicle_number:vehicle_number,edit_id:edit_id},
					async:false,
					success:function(result){
						console.log(result);
						if(result[0]=="1")
						{
							$("#vehicle_error").html(result[1]).attr("style","color:red");
							chck_key=false;
							
						}
						else
						{
							chck_key=true;
							$("#vehicle_error").html('');
						}
							
					}
				});					
				});
		//button Save
			$(document).on('click','#save',function(){
				var data = $('#vehicle_form').serialize();
				//duplicate_check();
				if($('#vehicle_form').valid())
				{
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
							console.log(jqxhr.responseText);
						}
					});
				}
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