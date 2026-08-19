<?php
	require_once("include/connect.php");
	require_once("include/function.php"); 

	 $grn_no=trim($_REQUEST['grn_no']);
	  
	  
	?>
	<!DOCTYPE html>
	<html>
	  <head>
	  <?php include("include/title.php"); ?>
	  <?php include("include/css_js.php"); ?>
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<style>
	*, *:after, *:before {
	  margin: 0;
	  padding: 0;
	  box-sizing: border-box;

	}

	/* Form Progress */
	.status-progress {
	  width: 100%;
	  margin: 20px auto;
	  text-align: center;
	}
	.status-progress .circle,
	.status-progress .bar {
	  display: inline-block;
	  background: #fff;
	  width: 40px; height: 40px;
	  border-radius: 40px;
	  border: 1px solid #d5d5da;
	  font-family: sans-serif;
	}
	.status-progress .bar {
		position: relative;
		width: 100px;
		height: 9px;
		top: -56px;
		margin-left: 0px;
		margin-right: 0px;
		border-left: none;
		border-right: none;
		border-radius: 0;
	}
	@media screen and (max-width:1204px){
	.status-progress .bar {
		position: relative;
		width: 85px;
		height: 9px;
		top: -56px;
		margin-left: 0px;
		margin-right: 0px;
		border-left: none;
		border-right: none;
		border-radius: 0;
	}
	}
	@media screen and (max-width:1150px){
	.status-progress .bar {
		position: relative;
		width: 78px;
		height: 9px;
		top: -56px;
		margin-left: 0px;
		margin-right: 0px;
		border-left: none;
		border-right: none;
		border-radius: 0;
	}
	}
	@media screen and (max-width:1080px){
	.status-progress .bar {
		position: relative;
		width: 60px;
		height: 9px;
		top: -56px;
		margin-left: 0px;
		margin-right: 0px;
		border-left: none;
		border-right: none;
		border-radius: 0;
	}
	}
	@media screen and (max-width:825px){
	.status-progress .bar {
		position: relative;
		width: 48px;
		height: 9px;
		top: -56px;
		margin-left: 0px;
		margin-right: 0px;
		border-left: none;
		border-right: none;
		border-radius: 0;
	}
	}
	.status-progress .circle .label {
	  display: inline-block;
	  width: 32px;
	  height: 32px;
	  line-height: 24px;
	  border-radius: 32px;
	  margin-top: 3px;
	  color: #b5b5ba;
	  font-size: 17px;
	}
	.status-progress .circle .title {
	  color: #b5b5ba;
		font-size: 13px;
		line-height: 20px;
		margin-left: -19px;
		display: flex;
		margin-top: 30px;
		font-weight: 600;
		background-color: #fff !important;
	}

	/* Done / Active */
	.status-progress .bar.done,
	.status-progress .circle.done {
		font-family: sans-serif;
	  background: #8bc435;
	}
	.status-progress .bar.active {
	  background: linear-gradient(to right, #0c95be  40%, #0c95be  60%);
	}
	.status-progress .circle.done .label {
	  color: #FFF;
	  background: #8bc435;
	  margin-left:3px;
	  box-shadow: inset 0 0 2px rgba(0,0,0,.2);
	}
	.status-progress .circle.done .title {
	  color: #444;
	}
	.status-progress .circle.active .label {
	  color: #FFF;
	  background: #0c95be;
	  margin-left:3px;
	  box-shadow: inset 0 0 2px rgba(0,0,0,.2);
	}
	.status-progress .circle.active .title {
	  color: #0c95be;
	}

	.track-orderme{
		display: inline-block;
		max-width: 250px;
		height: 48px;
		
	}
	.custyle .table thead>tr>th, .table tbody>tr>th, .table tfoot>tr>th, .table thead>tr>td, .table tbody>tr>td, .table tfoot>tr>td {
		padding: 6px 4px !important;
	}
	</style>
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
			<div class="widget-container fluid-height clearfix">
			<div class="heading"> <i class="fa fa-table" ></i> Track Consignment </div>
						  <div class="widget-content padded">
					<form class="form-horizontal" id="transaction_form">
					<div id="response" class="alert alert-danger" style="display:none;">
							<div class="message" style="text-align:center"></div>
						</div>
						
					 <div class="row">
						<div class="col-sm-offset-4 col-sm-6">
						<div class="form-group">
								<div class="col-md-6">
								<label class="control-label">GRN No:</label>
								<input type="text" name="grn_no" id="grn_no" value="<?php echo $grn_no; ?>" class="form-control"  >
								</div>
								<div class="col-md-6">						
								 <button class="btn btn-primary" type="submit" style="margin-top: 18px;"  id="search"><i class="fa fa-plane" aria-hidden="true"></i> Track Now</button>
												</div>
							</div>
							
							</div>
									
							
							
					 </div>
						

			 
	  <?php 
	  require_once 'include/connect.php';
	  require_once 'include/function.php';
	  
	//   $add_on='';
	//   if($_SESSION['role']=='CL')
	//   {
	// 	 $query22 = "select  * from users  where user_id=".$_SESSION['user_id'];
	// 	$result22 = mysqli_query($conn,$query22) or die(mysqli_error($conn));
	// 	$row22 = mysqli_fetch_assoc($result22);

	// 	$add_on=' and (consignee='.$row22['company_name'].' or consigner='.$row22['company_name'].')';
				

	//   }
		
	  if($grn_no!="") 
	  {
		  //$grn_no=$grn_no;
	  $count=1;
	// $tbl=$tbl_inv='';
	// 	$query2 = "SELECT * FROM transaction_tbls";
	// 	$result2 = mysqli_query($conn,$query2) or die(mysqli_error($conn));
	// 	while($row2 = mysqli_fetch_assoc($result2))
	// 	{			
	// 		$tbl ="transaction_".$row2['table_name'];
	// 		$tbl_inv ="transaction_invoice_".$row2['table_name'];
	//  $query = "select * from $tbl where grn_no='$grn_no' $add_on";
	// 	$result = mysqli_query($conn,$query);	
	// 	$grnr=mysqli_fetch_array($result);
		
	// 	if(mysqli_num_rows($result) > 0)
		//{
			$count++;

			//echo "count--->".$count;
			echo '  <br/> <div class="mt-5 mb-5 text-center">
						<h2>Tracking Status</h2>
					</div>
					
					<br/>';
			//extract($grnr);
            
	// $from_status = 2;				
	// $from_status = 3;				
	// $from_status = 4;				
	// $from_status = 5;				
	  $status=Array();
	  array_push($status,1);
      $conn = mysqli_connect("localhost","root","",'bookconsignment');
	  $query="select * from consignment where booking_id='$grn_no'";
	  $result=mysqli_query($conn,$query);
	  while($row=mysqli_fetch_array($result))
	  {
		
	if(!in_array($from_status, $status))
	  array_push($status,$from_status);
	
	if(!in_array($row['status'], $status))
	  array_push($status,$row['status']);
		 
	  }
	   $count=1; 
	  $max=max($status);
	   //$remarks=get_cong_remarks($conn,$max,$grn_no);
	  echo '<div class="status-progress">';
	  for($i=1;$i<9;$i++)
	  {
		if(in_array($i,$status))
		{
			
			if($i!=1)
		echo '<span class="bar"></span>';
		echo '<div class="circle"><span class="label">'.$i.'</span><span class="title">'.get_trans_status($i).'</span></div>';
		$count++;
		}
		else if($i>$max)
		{
		echo '<span class="bar"></span>';
		echo '<div class="circle"><span class="label">'.$i.'</span><span class="title">'.get_trans_status($i).'</span></div>';
			
			
		}
	  }
	  echo '</div>';
	 
	// 	 $query3="select sum(no_of_pkge) as no_of_pkge from  $tbl_inv where transaction_id='$transaction_id'";
	//   $result3=mysqli_query($conn,$query3);
	//   $row3=mysqli_fetch_array($result3);	
			
	  ?>
	<br><br><br><br>


	 <div class="mt-5 mb-5 text-center">
						<h2>Consignment Details</h2>
					</div>
					
								

	  <div class="col-md-offset-3 col-md-6 custyle table-responsive">
				<table class="table table-bordered table-blue table-striped" width="80%">
						<tbody><tr>
							<td>GRN No.</td>
							<td> <?php echo $grn_no; ?></td>
						  </tr>
						<tr>
							<td>GRN Date</td>
							<td><?php echo $grn_date; ?></td>
						</tr>
						<tr>
							<td>Consignor</td>
							<td><?php echo get_client_name($conn,$consigner); ?></td>
					   </tr>
					   <tr>
							<td>Consignee</td>
							<td><?php echo get_client_name($conn,$consignee); ?></td>
					   </tr>
					   <tr>
							<td>Mode of Transport</td>
							<td><?php echo $mode=get_mode($conn,$mode_of_transportation);
								$mode_arr=explode(" ",$mode);
								
									$icon='<i class="fa fa-truck fa-flip-horizontal"></i>';
						if(in_array('AIR', $mode_arr))
							$icon='<i class="fa fa-plane"></i>';
							
						if(in_array('TRAIN', $mode_arr))
							$icon='<i class="fa fa-train"></i>';
							
						
							?></td>
					   </tr>
					   <tr>
							<td>No. of Packages</td>
							<td><?php echo $row3['no_of_pkge']; ?></td>
					   </tr>
					   <tr>
							<td>Payment Mode</td>
							<td><?php echo consignment_mode($conn,$mode_of_consignment); ?></td>
					   </tr>
					   <tr>
							<td>Status</td>
							<td><?php echo get_trans_status($max); ?></td>
					   </tr>
						 <tr>
							<td>Remarks</td>
							<td><?php echo $remarks; ?></td>
					   </tr>
				</tbody>
				</table>
				
		<br/>
		<br/>
				</div>
				
		<?php //}
		//}
		
		if($count==1)
			echo '<p class="text-center text-danger">Incorrect GRN No or Party Invoice No ... Please check and try again</p> ';
	  
		 
	  }
		?>
		
		
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
		
		/*$(document).on('keyup', '#grn_no', function(event){					
						 event.preventDefault();
							if (event.keyCode === 13) {
								$("#search").trigger("click");
							}
					});*/
			$(window).load(function() {
				$(".loading-page").hide();
			});
			});
			</script>
			
			<script>

	var i = 1;
	var j='<?php echo $count; ?>';
	$('.status-progress .circle').removeClass().addClass('circle');
	$('.status-progress .bar').removeClass().addClass('bar');
	setInterval(function() {
	if(i<=j){
	  $('.status-progress .circle:nth-of-type(' + i + ')').addClass('active');
	  $('.status-progress .circle:nth-of-type(' + i + ') .label').html('<?php echo $icon; ?>');
	  $('.status-progress .circle:nth-of-type(' + (i-1) + ')').removeClass('active').addClass('done');
	  
	  $('.status-progress .circle:nth-of-type(' + (i-1) + ') .label').html('&#10003;');
	  
	  $('.status-progress .bar:nth-of-type(' + (i-1) + ')').addClass('active');
	  
	  $('.status-progress .bar:nth-of-type(' + (i-2) + ')').removeClass('active').addClass('done');
	  
	  i++;
	}
	  
	}, 1000);



				/*$('#grn_no').keypress(function (event) {
						return isNumber(event, this)
					});*/
					
					
			   function isNumber(evt, element) {
					var charCode = (evt.which) ? evt.which : event.keyCode

					if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&      // “-” CHECK MINUS, AND ONLY ONE.
						(charCode != 46 || $(element).val().indexOf('.') != -1) &&      // “.” CHECK DOT, AND ONLY ONE.
						(charCode < 48 || charCode > 57))
						return false;
						return true;
					} 
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