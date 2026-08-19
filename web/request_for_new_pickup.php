<?php
require_once("include/connect.php");
require_once("include/function.php"); 
$row = array();

if (isset($_GET['key']) && $_GET['key'] != "") {

    $key = mysqli_real_escape_string($conn, $_GET['key']);

    $query = "SELECT * FROM pickup WHERE MD5(pickup_id)='$key'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {

        $row = mysqli_fetch_assoc($result);
// 		echo "<pre>";
// print_r($row);
// echo "</pre>";
// exit;

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
			  <div class="heading"> <i class="fa fa-plus"></i> Request For PickUp  <span class="align-right"><i class="fa fa-plus"></i><a href="pickup_list.php">View List</a></span></div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="pickup_form">
				
					<input type="hidden"
       id="form_name"
       name="form_name"
       value="<?php echo isset($row['pickup_id']) ? 'edit_pickup' : 'request_for_new_pickup_for_existing_client'; ?>">
					<input type="hidden"
       id="edit_id"
       name="edit_id"
       value="<?php echo isset($row['pickup_id']) ? $row['pickup_id'] : ''; ?>">
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<div class="col-md-offset-1 col-md-5">
						
							<div class="form-group">
								<label class="control-label">Pickup Request Id:</label>
								<input type="text" id="pickup_ref_id" name="pickup_ref_id" value="<?php echo $row['pickup_ref_id']; ?>" class="form-control" placeholder="E.g (RFP/00001)" disabled/>
							</div>
							<div class="form-group">
								<label class="control-label">Origin <span style="color:red;">*</span> :</label>
								<select type="text" name="origin" id="origin"  class="form-control" required/>								
								<option value="">Select City</option>
										<?php 
											$city_query = "select * from city where status=0 order by city_name";
											$city_result = mysqli_query($conn,$city_query);
											while($city_row = mysqli_fetch_array($city_result)){
										?>
											<option value="<?php echo $city_row['city_id']; ?>"
<?php
if(isset($row['origin']))
{
    if($row['origin']==$city_row['city_id'] || $row['origin']==$city_row['city_name'])
        echo "selected";
}
?>>
<?php echo $city_row['city_name']; ?>
</option>
										<?php 
											}
										?>
								
								</select>
							</div>
							<div class="form-group">
								<label class="control-label">Consignee <span style="color:red;">*</span> :</label>
								<select type="text" name="consignee" id="consignee" class="form-control" required/>
								
								<option value="">Select Consignee</option>
										<?php 
											 $consignor_query = "select * from client where status=0 order by client_company_name";
											$consignor_result = mysqli_query($conn,$consignor_query);
											while($consignor_row = mysqli_fetch_array($consignor_result)){
										?>
											<option value="<?php echo $consignor_row['client_id']; ?>"
<?php
if(isset($row['consignee']))
{
    if($row['consignee']==$consignor_row['client_id'] || $row['consignee']==$consignor_row['client_company_name'])
        echo "selected";
}
?>>
<?php echo $consignor_row['client_company_name']; ?>
</option>
										<?php 
											}
										?>
										</select>
							</div>
							<div class="form-group">
								<label class="control-label">Mode Of Transport:</label>
								<select name="mode" id="mode" class="form-control" required>
								<option value="">Select Mode</option>
										<?php 
											$mode_query = "select * from mode_of_transportation where status=0";
											$mode_result = mysqli_query($conn,$mode_query);
											while($mode_row = mysqli_fetch_array($mode_result)){
										?>
											<option value="<?php echo $mode_row['mode_id']; ?>" <?php if(isset($row['mode']) && $row['mode']==$mode_row['mode_id']) echo "selected"; ?>>
												<?php echo $mode_row['mode_type']; ?>
</option>
										<?php 
											}
										?>
								</select>
							</div>
							
							<div class="form-group">
								<label class="control-label">Description:</label>
								<textarea name="description" id="description" class="form-control"><?php echo $row['description']; ?></textarea>
								</div>
						
						</div>
						<div class="col-md-5">
							
							<div class="form-group">
								<label class="control-label">Consignor <span style="color:red;">*</span> :</label>
								
								<select  name="consignor" id="consignor" class="form-control" required>
										<option value="">Select Consignor</option>
									
									</select>
							</div>
								<div class="form-group">
								<label class="control-label">Destination <span style="color:red;">*</span> :</label>
								<select  name="city" id="city" class="form-control" required>
										<option value="">Select City</option>
										
									</select>
							</div>
						<div class="form-group">
								<label class="control-label">No.of Pakages :</label>
								<input type="text" name="no_of_package" id="no_of_package" value="<?php echo $row['no_of_package']; ?>" class="form-control" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" />
								<span class="dup-check"></span>
							</div>
						
						<div class="form-group">
								<label class="control-label">Package Type:</label>
								
								<select  name="package" id="package" class="form-control" required>
										<option value="">Select package Type</option>
										<?php 
											$package_query = "select * from package where status=0";
											$package_result = mysqli_query($conn,$package_query);
											while($package_row = mysqli_fetch_array($package_result)){
										?>
											<option value="<?php echo $package_row['package_id']; ?>" <?php if($row['package']==$package_row['package_id']) echo "selected"; ?>><?php echo $package_row['package_code']." - ".$package_row['description']; ?></option>
										<?php 
											}
										?>
									</select>
									
							</div>
						
						<div class="form-group">
								<label class="control-label">Approx.Weight (kg):</label>
								<input type="text" name="approx_weight" id="approx_weight" class="form-control" value="<?php echo $row['approx_weight']; ?>" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" />
							</div>
						
						
						
						</div>
				 </div><br/>
				   <div class="row">
					<div class="col-md-12 form-action">
						<button class="btn btn-primary" type="button" id="save">Submit</button>
                        <a class="btn btn-default-outline  btn-reset" type="button" href="request_for_new_pickup.php">Cancel</a>
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

		var role='<?php echo $_SESSION['role']; ?>';
		var id='<?php echo $_SESSION['user_id']; ?>';
		if(role=="CL")
		{
				
				$.ajax({
					url:'fetch_details.php',
					type:"GET",
					dataType:"JSON",
					data:{cmd:"get_client_user_details",tbl_id:id},
					async:false,
					success:function(result){
						console.log(result);
						$("#origin").val(result['city']).attr("disabled",true);
						$("#consignee").val(result['company_id']).attr("disabled",true);
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
	
	$(document).on('change','#origin',function(e){
			var id = $(this).val();
			$.ajax({
					url:'fetch_details.php',
					type:"GET",
					data:{cmd:"get_destination",id:id},
					async:false,
					success:function(result){
						console.log(result);
						$('#city').html(result);	
						
						
					}
				});
							
				});

		
			if('<?php echo isset($row['pickup_id']) ? 1 : 0; ?>' == 1)
{
    $.ajax({
        url: 'fetch_details.php',
        type: 'GET',
        data: {
            cmd: 'get_destination',
            id: '<?php echo $row['origin']; ?>'
        },
        success: function(result){
            $('#city').html(result);
            $('#city').val('<?php echo $row['destination']; ?>');
        }
    });
}

$(document).on('change','#consignee',function(e){
    var id = $(this).val();
    $.ajax({
        url:'fetch_details.php',
        type:"GET",
        data:{cmd:"get_consignor",id:id},
        async:false,
        success:function(result){
            $('#consignor').html(result);
        }
    });
});

if('<?php echo isset($row['pickup_id']) ? 1 : 0; ?>' == 1)
{
    $.ajax({
        url: 'fetch_details.php',
        type: 'GET',
        data: {
            cmd: 'get_consignor',
            id: '<?php echo $row['consignee']; ?>'
        },
        success: function(result){
            $('#consignor').html(result);
            $('#consignor').val('<?php echo $row['consignor']; ?>');
        }
    });
}


		
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