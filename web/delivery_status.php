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
			  <div class="heading"> <i class="fa fa-plus"></i>Delivery Status</div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="delivery_form">
				
					<input type="hidden" id="form_name" name="form_name" value="add_delivery">
					<input type="hidden" id="edit_id" name="edit_id" value="">
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">Status Type <span style="color:red;">*</span> :</label>
								<input type="text" id="status_type" name="status_type" class="form-control" required/>
							</div>
							<div class="form-group">
								<label class="control-label">Sequence <span style="color:red;">*</span> :</label>
								<input type="text" name="sequence" id="sequence" class="form-control" required/>
								<span class="dup-check"></span>
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
						<div class="heading"> <i class="fa fa-table" ></i> List of Delivery </div>
					<div class="widget-content padded clearfix new_dept">
						<table class="table table-bordered table-striped" id="dataTable1">
							<thead>
								<th class="table-title" style="width:10%">S.No</th>
								<th class="table-title" style="width:10%">Staus Type</th>
								<th class="table-title" style="width:30%">Delivery</th>
								
								<th class="table-title" style="width:10%">Action</th>              
							</thead>
							<tbody>
							<?php 
								$query = "select * from delivery_status";
								$result = mysqli_query($conn,$query);
									$i=1;
								while($row = mysqli_fetch_array($result))
								{
									
									
							?>
								<tr>
									<td class="text-center"><?php echo $i; ?></td>
									<td><?php echo $row['status_type']; ?></td>
									<td><?php echo $row['sequence']; ?></td>
									
									<td class="actions center-content ">
										<div class="action-buttons">
											<a title="Edit" class="table-actions btn-edit" id="<?php echo $row['delivery_status_id']; ?>"><i class="fa fa-pencil"></i></a>
											<?php 
											if($row['status'] == 0)
											{
											?>
											<a class="table-actions btn-active" data-status="<?php echo $row['status']  ?>" title="InActive" id="<?php echo $row['delivery_status_id'] ?>"><i class="fa fa-check"></i></a>
											<?php 
											}
											else
											{
											?>
											<a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status']  ?>" title="Active" id="<?php echo $row['delivery_status_id'] ?>"><i class="fa fa-times"></i></a>
											<?php 
											}
											?>
											<a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['delivery_status_id'] ?>"><i class="fa fa-trash-o"></i></a>
											
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
		
	
		//button Save
			$(document).on('click','#save',function(){
				var data = $('#delivery_form').serialize();
				duplicate_check();
				if($('#delivery_form').valid() == true && dup_chk)
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
				$.post('save_details.php', { form_name: "del_delivery", tbl_id: $(this).attr("id") }, function(data,status){	
				console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Delivery Deleted successfully...");
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
						$("#alert-message").text("Delivery deletion failed");
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
				$.post('save_details.php', { form_name: "inacv_delivery", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
					console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Delivery Is "+msg+"...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					
					else if(data == 2){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Delivery Is "+msg+"...");
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
					data : { cmd: "get_delivery_details", tbl_id: tbl_id }, // post data || get data
					success : function(result) {
					console.log(result);
						$(".form-data-saving").hide();
						$("#form_name").val("edit_delivery");
						$("#edit_id").val(result['delivery_id']);
						$("#sequence").val(result['sequence']);
						$('#status_type').val(result['status_type']);
						
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