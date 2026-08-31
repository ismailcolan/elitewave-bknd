<?php
require_once("include/connect.php");
require_once("include/function.php"); 

$c_date=date('d-m-Y');
$c_mY=date('m-Y');

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
		<div class="heading"> <i class="fa fa-table" ></i> Consignment Report </div>
					  <div class="widget-content padded">
				<form class="form-horizontal" id="transaction_form">
				
					<input type="hidden" id="cmd" name="cmd" value="get_pickup_report_details">
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<div class="col-md-offset-3 col-md-2">
						<div class="form-group">
						<label class="control-label" style="margin-right: 33px;"><input type="radio" name="report_type" class="report_type"  value="DAILY" checked /> DAILY</label>
						<label class="control-label"><input type="radio" class="report_type" name="report_type" value="MONTHLY"  /> MONTHY</label>
						</div>
						</div>
						<div class="col-md-3">
						<div class="form-group">
						
						<div id="picker1">
							<?php echo ew_date_input(array('id' => 'date', 'name' => 'date', 'required' => true, 'value' => $c_date, 'readonly' => true)); ?>
						</div>
						<div id="picker2" style="display:none;">
							<?php echo ew_month_input(array('id' => 'month', 'name' => 'month', 'value' => $c_mY)); ?>
						</div>
						</div>
						
						</div>
				 </div><br/>
				 <div class="row">
					<div class="col-md-offset-2 col-md-2">
					<div class="form-group">
							<label class="control-label">Mode:</label>
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
				 <div class="col-md-2">
					<div class="form-group">
							<label class="control-label"> Origin:</label>
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
						<div class="col-md-2">
					<div class="form-group">
							<label class="control-label">Destination:</label>
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
						<div class="col-md-2">
					<div class="form-group">
							<label class="control-label">Status:</label>
							<Select type="text" name="status" id="status" class="form-control" >
							<option value=""> -- Select Status -- </option>
							<option value="0">Pending</option>
							<option value="1">Picked Up</option>
							<option value="2">Cancelled</option>
							</select>
						</div>
						</div>
						<div class="col-md-2">
					<div class="form-group">
							 <button class="btn btn-primary" type="button" style="margin-top: 18px;" id="search">Search</button>
				
						</div>
						</div>
						
				 </div>
				 		
				</form>
			  </div>
			  </div>
			  </div>
			</div>
		  <div class="col-md-offset-1 col-md-10" id="table_div" style="display: none;">
			<div class="widget-container fluid-height clearfix">
			 	<div class="heading"> <i class="fa fa-table" ></i> Consignment Report  </div>
					<div class="widget-content padded clearfix new_dept">
						<table class="table table-bordered table-striped" id="dataTable1">
							<thead>
								<th class="table-title" style="width:10%">S.No</th>
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
		$(document).on('change','.report_type',function(){
			if($(this).val()=='MONTHLY')
			{
				$("#picker1").hide();
				$("#picker2").show();
			}
			else
			{
				$("#picker2").hide();
				$("#picker1").show();
			}
			
			});
		$(document).on('click','#search',function(){
			$("#table_div").show();
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