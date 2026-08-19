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
		<div class="col-md-offset-1 col-md-10">
		<div class="widget-container fluid-height clearfix">
		<div class="heading"> <i class="fa fa-table" ></i>Pickup Consignment</div>
					  <div class="widget-content padded">
				<form class="form-horizontal" id="transaction_form">
				
					<input type="hidden" id="form_name" name="form_name" value="transaction_form">
					<input type="hidden" id="edit_id" name="edit_id" value="">
					<input type="hidden" id="cmd" name="cmd" value="get_transaction_month_details">
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<div class="col-md-offset-4 col-md-4">
						<div class="form-group">
						<label class="control-label">Month:</label>
						<div class="input-group date  date-picker" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
						<input class="form-control" type="text" id="month" value="<?php echo date('m-Y'); ?>" name="month" required><span class="input-group-addon"><i class="fa fa-calendar"></i></span>
						</div>
						</div>
						</div>
				 </div><br/>
				   <div class="row">
					<div class="col-md-12 form-action">
						<button class="btn btn-primary" type="button" id="search">Search</button>
						
					</div>
				  </div>
				</form>
			  </div>
			  </div>
			  </div>
			</div>
		  <div class="col-md-offset-1 col-md-10">
			<div class="widget-container fluid-height clearfix">
			 	<div class="heading"> <i class="fa fa-table" ></i> List of Pending Consignments  </div>
					<div class="widget-content padded clearfix new_dept">
						<table class="table table-bordered table-striped" id="dataTable1">
							<thead>
								<th class="table-title" style="width:10%">S.No</th>								
								<th class="table-title" style="width:15%">Action</th>  
								<th class="table-title" style="width:10%">GRN NO</th>
								<th class="table-title" style="width:10%">GRN Date</th>
								<th class="table-title" style="width:10%">No.of.Pkgs</th>
								<th class="table-title" style="width:10%">Mode</th>
								<th class="table-title" style="width:10%">Origin</th>
								<th class="table-title" style="width:10%">Consignor </th>
								<th class="table-title" style="width:10%">Consignee </th>
								<th class="table-title" style="width:10%">Destination</th>
								<th class="table-title" style="width:10%">Status</th>            
							</thead>
							<tbody id="get_month_details">
							<?php 
							$date= date('d-m-Y');
							$dt=(explode("-",$date));	

							if($dt[1]<=3){
								$m=4;
								$m1= 1;
								$y=$dt[2]-1;
								$trans_name = "transaction_".$m1."_".$dt[2];
								$trans_image_name = "transaction_images_".$m1."_".$dt[2];
								$trans_invoice_name = "transaction_invoice_".$m1."_".$dt[2];
							}
							else if(($dt[1]>=4) && ($dt[1]<=6)){
								$m=1;
								$m1= 2;
								$y=$dt[2];
								$trans_name = "transaction_".$m1."_".$dt[2];
								$trans_image_name = "transaction_images_".$m1."_".$dt[2];
								$trans_invoice_name = "transaction_invoice_".$m1."_".$dt[2];
							}
							else if(($dt[1]>=7) && ($dt[1]<=9)){
								$m=2;
								$m1= 3;
								$y=$dt[2];
								$trans_name = "transaction_".$m1."_".$dt[2];
								$trans_image_name = "transaction_images_".$m1."_".$dt[2];
								$trans_invoice_name = "transaction_invoice_".$m1."_".$dt[2];
							}
							else{
								$m=3;
								$m1= 4;
								$y=$dt[2];
								$trans_name = "transaction_".$m1."_".$dt[2];
								$trans_image_name = "transaction_images_".$m1."_".$dt[2];
								$trans_invoice_name = "transaction_invoice_".$m1."_".$dt[2];
							}
								$query = "select * from transaction_".$m1."_".$dt[2]." where created_by='".$_SESSION['user_id']."'";
							
								$result = mysqli_query($conn,$query);
									$i=1;
								while($row = mysqli_fetch_array($result))
								{
									
									$pkg_q=mysqli_query($conn,"select sum(no_of_pkge) as pkge from transaction_invoice_".$m1."_".$dt[2]." where transaction_id='".$row['transaction_id']."'");
									$pkg_r=mysqli_fetch_array($pkg_q);
							?>
								<tr>
									<td class="text-center"><?php echo $i; ?></td>
									<td class="actions center-content ">
									
									
										<div class="action-buttons" style="width: 100%;">
											<a title="Edit" href="transactions.php?key=<?php echo md5($row['transaction_id']); ?>&m=<?php echo $m1; ?>&y=<?php echo $dt[2] ?>" class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-pencil"></i></a>
											
											<a class="table-actions " data-status="<?php echo $row['status']  ?>" title="View" id="<?php echo $row['transaction_id'] ?>"><i class="fa fa-print"></i></a>
											<a title="Cancel"  class="table-actions btn-edit" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-ban"></i></a>
									<a title="E-way Attachments" href="#eway_popup" class="table-actions btn-eway" data-toggle="modal" id="<?php echo $row['transaction_id']; ?>"><i class="fa fa-paperclip"></i></a>
											
										</div>
										
									</td>
									
									<td><?php echo $row['grn_no']; ?></td>
									<td><?php echo $row['grn_date']; ?></td>
									<td><?php echo $pkg_r['pkge']; ?></td>
									<td><?php echo $pkg_r['mode']; ?></td>
									<td><?php echo $pkg_r['origin']; ?></td>
									<td><?php echo get_client_name($conn,$row['consigner']); ?></td>
									<td><?php echo get_client_name($conn,$row['consignee']); ?></td>
									<td><?php echo get_city_name($conn,$row['destination']); ?></td>
									<td><?php 	 if($row['status']==0) echo "Booked"; else if($row['status']==1) echo "Cargo Received"; ?></td>
									
									
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

		$(document).on('click','#search',function(){
			
			var data = $('#transaction_form').serialize();
			//alert(data);
			if($('#transaction_form').valid()==true){
				
				$.ajax({
					url:'fetch_details.php',
					type:"GET",
					data:data,
					success:function(result){
						console.log(result);
						$('#get_month_details').html(result);
					}
				});
			}
			
		});
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
		
	$('.date-picker').on("click", function() {
				$(this).datepicker({
					changeMonth: true,
					changeYear: true,
					format: 'mm-yyyy',
					}).datepicker('show');
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
		
			$(document).on('click', '.btn-eway', function(ev){
				var id=$(this).attr('id');
				var table_name='<?php echo $trans_image_name; ?>';
				var content='<form id="eway_form"  enctype="multipart/form-data">\
				<input type="hidden" name="form_name" value="add_eway_image">\
				<input type="hidden" name="attachment_id" value="'+id+'">\
				<input type="hidden" name="table_name" value="'+table_name+'">\
				<input type="file" name="image"><br><input type="file" name="pdf">\
				<div class="modal-footer">\
				<button class="btn btn-primary btn-submit" data-dismiss="modal" type="button" id="save_eway">Submit</button></div></form>';
				$('#attachment_body').html(content);
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
				$.post('save_details.php', {form_name: "inacv_client", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
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

			
			
		$(document).on('click', '#save_eway', function(ev){
var formData = new FormData(document.getElementById("eway_form")); 
				if($('#eway_form').valid() == true)
				{
					$(this).prop("disabled",true);
					$.ajax({
							url:"save_details.php",
							type:"post",
							//dataType:"json",
							data:formData,
							processData: false,
							contentType: false,
							success:function(result){
								console.log(result);
								if(result == 1){
									$(".form-data-saving").hide();
								
								$("#attachment_body").html("Attachments Uploaded Successfully ");
									//location.reload();
									
								}
								else{
									$(".form-data-saving").hide();
									
								}
								
							}
						});
				}
				
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

<div class="modal fade popup_close" id="eway_popup"  style="display:none">
		    <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
			    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
			    <h4 class="modal-title" style="color:#fff">
			      Add Attachments 
			    </h4>
			  </div>
			    
				<div class="modal-body" id="attachment_body">
					 
				</div>
			</div>
		    </div>
                </div>
				
		
  </body>
</html>