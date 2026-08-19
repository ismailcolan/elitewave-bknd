<?php
require_once("include/connect.php");
require_once("include/function.php"); 

$c_date=date('d-m-Y');
$c_mY=date('m-Y');
$key=$_GET['key'];

$edit_query ="select * from transaction_status where MD5(sheet_id)='$key'";
$edit_result = mysqli_query($conn,$edit_query);
$row=mysqli_fetch_array($edit_result);													
$old_status=$row['status'];												
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
		<div class="heading"> <i class="fa fa-table" ></i><?php echo $row['sheet_no']; ?> Status Sheet Edit<span class="align-right"><i class="fa fa-plus"></i><a href="status_sheet_list.php">View List</a></span> </div>
					  <div class="widget-content padded">
				<form class="form-horizontal" id="transaction_form">
				
					<input type="hidden" id="form_name" name="form_name" value="edit_change_grn_status">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $row['sheet_id']; ?>">
					<input type="hidden" id="del_ids" name="del_ids[]" value="">
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				 
				 <div class="row">
					
				 <div class="col-md-offset-1  col-md-2">
					<div class="form-group">
							<label class="control-label"> Origin:</label>
							<select name="origin" disabled id="origin" class="form-control">
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
						<div class="col-md-2">
					<div class="form-group">
							<label class="control-label">Destination:</label>
							<select name="destination" disabled id="destination" class="form-control">
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
						<div class="col-md-2">
					<div class="form-group">
							<label class="control-label">Mode:</label>
							<select name="mode" disabled id="mode" class="form-control">
												<option value="">Modeof Transport</option>
												<?php 
													$transport_query ="select * from mode_of_transportation where status=0";
													$transport_result = mysqli_query($conn,$transport_query);
													while($transport_row = mysqli_fetch_array($transport_result))
													{
												?>
												<option value="<?php echo $transport_row['mode_id']; ?>" <?php if($transport_row['mode_id']==$row['mode']) echo "selected"; ?>><?php echo $transport_row['mode_type']; ?></option>
												<?php
													}
												?>
											</select>
						</div>
						</div>
						<div class="col-md-2">
					<div class="form-group">
							<label class="control-label">Change Status To:</label>
							<Select type="text" name="status" required id="status" class="form-control" >
							<option value=""> -- Select Status -- </option>
							<option value="1">Consignment Booked</option>
							<option value="2">Consignment Picked Up</option>
							<option value="3">In Transit - 1 (Consignment at Origin State)</option>
							<option value="4">In Transit - 2 (Towards Destination State)</option>
							<option value="5">In Transit - 3 (Towards Destination)</option>
							<option value="6">At Destination</option>
							<option value="7">Out for Delivery</option>
							<option value="8">Consignment Delivered Successfully</option>
							</select>
						</div>
						</div>
						<div class="col-md-2">
					 <div class="form-group">
					<label class="control-label">Remarks:</label>
					
						<textarea name="remarks" id="remarks" class="form-control" > <?php echo $row['remarks']; ?> </textarea>
						
				  </div>
						</div>
						
				 </div><br>
				 	<br/>
					<div id="table_div"  >
					<table class="table table-bordered table-striped" >
							<thead>
								<th class="table-title" style="width:5%">S.No</th>
								<th class="table-title" style="width:7%">GRN NO</th>
								<th class="table-title" style="width:6%">GRN Date</th>
								<th class="table-title" style="width:7%">No.of.Pkgs</th>
								<th class="table-title" style="width:10%">Mode</th>
								<th class="table-title" style="width:10%">Consignor-Origin</th>
								<th class="table-title" style="width:10%">Consignee-Destination</th>
								<th class="table-title" style="width:10%">Current Status</th>
								<th class="table-title" style="width:10%">Action</th>             
							</thead>
							<tbody id="tbl_data">
	<?php 

	
	$out_put='';
	$i=1;
	$query4 = "SELECT * FROM transaction_status_log where sheet_id='".$row['sheet_id']."'";
	$result4 = mysqli_query($conn,$query4) or die(mysqli_error($conn));
	while($row4 = mysqli_fetch_assoc($result4))
	{	
	$grn_no=$row4['grn_no'];
	$query2 = "SELECT * FROM transaction_tbls";
	$result2 = mysqli_query($conn,$query2) or die(mysqli_error($conn));
	while($row2 = mysqli_fetch_assoc($result2))
	{			
	
	 $query = "select * from transaction_".$row2['table_name']." where grn_no='$grn_no'";
	$result = mysqli_query($conn,$query);	
	if(mysqli_num_rows($result) > 0)
	{
	$row = mysqli_fetch_array($result);
	
		 $query1 = "select sum(no_of_pkge) as no_of_pkge from transaction_invoice_".$row2['table_name']." where transaction_id='".$row['transaction_id']."'";
		$result1 = mysqli_query($conn,$query1);	
		$row1 = mysqli_fetch_array($result1);
				
		$out_put.='<tr>
		<td class="text-center">'.$i.'</td>
		<td><input type="hidden" name="grn_id[]" class="grn_id" id="transaction_id_'.$i.'" value="'.$row['grn_id'].'" />'.$row['grn_no'].'</td>
		<td>'.$row['grn_date'].'</td>
		<td>'.$row1['no_of_pkge'].'</td>
		<td>'.get_mode($conn,$row['mode_of_transportation']).'</td>
		<td>'.get_client_name($conn,$row['consigner']).'-'.get_city_name($conn,$row['origin']).'</td>
		<td>'.get_client_name($conn,$row['consignee']).'-'.get_city_name($conn,$row['destination']).'</td>
		<td>'.get_trans_status($row['status']).'</td>
		<td><button class="btn btn-danger delete" type="button" data-id="'.$row['grn_id'].'">Remove</button></td>
		</tr>';

		$i++;
	
	
	}
	}
	}
	echo $out_put;
	?>
							
							
							
							</tbody>
						</table>
						 <div class="row">
					<div class="col-md-12 form-action">
						<button class="btn btn-primary" type="button" id="save">Submit</button>
						<button class="btn btn-default-outline  btn-reset" type="button">Cancel</button>
					</div>
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
		var status='<?php echo $old_status; ?>'
		$("#status").val(status);
		var del_ids=[];
		
	$(document).on('click','#save',function(e){

			var data = $('#transaction_form').serialize();
			
			if($('#transaction_form').valid()==true){
				$("#table_div").show();
			$.ajax({
					url:'save_details.php',
					type:"POST",
					data:data,
					async:false,
					success:function(result){
						console.log(result);
						if(result==1){
								$(".form-data-saving").hide();
									//alert("Data: " + data + "\nStatus: " + status);
									$("#alert-status").text("");
									$("#alert-message").text("Status Updated Successfully.! Please Wait Until Page Refresh.!!");
									$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
										$("#alert-container").hide();
										$("#alert-container").removeClass("alert-success");
										location.reload();
									});		
							
							}
							else{
								$(".form-data-saving").hide();
									$("#alert-status").text("Alert !!! ");
									$("#alert-message").text("Status update Failed");
									$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
										$("#alert-container").hide();
										$("#alert-container").removeClass("alert-danger");
									});
							}
						
					}
				});
			}			
		});
	
		
		$(document).on('click','.delete',function(){
				var id=$(this).attr("data-id");
				del_ids.push(id);
				console.log(del_ids);
				$("#del_ids").val(del_ids);
				$(this).closest ('tr').remove ();
				var rowCount = $('#tbl_data tr').length;
				if(rowCount < 1)
					$("#table_div").hide();
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