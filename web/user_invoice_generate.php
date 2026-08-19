<?php
	require_once("include/connect.php");
	require_once("include/function.php"); 
	require_once("include/user-function.php"); 
	?>
	<!DOCTYPE html>
	<html>
	  <head>
	  <?php include("include/title.php"); ?>
	  <?php include("include/css_js.php"); ?>
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
		<style>
		.image_preview{
			width: 145px;
			height: 73px;
		}
		.remove-image {
			margin-left: 90px;
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
			  <div class="col-md-offset-1 col-md-10">
				<div class="widget-container fluid-height clearfix">
				  <div class="heading"> <i class="fa fa-plus"></i>Book a Consignment: <!--<span class="align-right"><i class="fa fa-plus" ></i><a href="transaction_list.php">View List</a></span>--></div>
				  
				  <div class="widget-content padded">
					
						<div id="response" class="alert alert-danger" style="display:none;">
							<div class="message" style="text-align:center"></div>
						</div>
						<?php 
				
                            $conn = mysqli_connect("localhost","root","","bookconsignment");
                            $query = "select *from consignment where md5(id) = '".$_REQUEST['key']."' ";
							$result = mysqli_query($conn,$query);
							$row = mysqli_fetch_assoc($result);
							$image = $row['attchment'];
							//print_r($row);
							$transaction_id = $row['id'];
							if($row['id'] > 0) $form_name="edit_user_consignment_form";
							else $form_name="add_user_consignment_form";
						?>
						<form id="user_grn_details"  class="form-horizontal"  enctype="multipart/form-data">
								<input type="hidden" name="form_name"  value="<?php echo $form_name; ?>">
								<input type="hidden" name="edit_id" id="edit_id"  value="<?php echo $row['id']; ?>">
								<input type="hidden" name="grn_id" id="grn_id"  value="<?php echo $row['booking_id']; ?>">
								
							<fieldset class="my-fieldset">
							<legend>GRN Information</legend>
								<div class="row">
								
									<div class="col-md-offset-1 col-md-5">
									<div class="form-group">
										<label class="control-label col-sm-4">GRN.No <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										    
										<?php 
									$query_code=mysqli_query($conn,"select * from client where client_id='".$_SESSION['company_id']."'");
                        			$r_code=mysqli_fetch_array($query_code);
                        			$query_max=mysqli_query($conn,"select * from transaction_log where client_id='".$_SESSION['company_id']."'");
                        			$r_max=mysqli_fetch_array($query_max);
                        			$id=$r_max['grn_id']+1;
                        			$billing_code = $r_code['billing_code'];
                        			$grn_no=$billing_code.sprintf("%05d",$id);
										//	if($_REQUEST['key']!=''){
										if($_SESSION['role']=="CL"){
										?>
										<input type="hidden" id="id" value="<?php echo $id;  ?>" name="id" class="form-control" />
									<?php	
									        if($row['grn_no']!=''){
									            
									    ?>
									    <input type="text" id="grn_no" value="<?php echo $row['grn_no']; ?>" name="grn_no" class="form-control" disabled/>
									    <?php
									        }
									        else{
									        ?>
											<input type="text" id="grn_no1" value="<?php echo $grn_no; ?>" name="grn_no1" class="form-control" disabled/>
											<input type="hidden" id="grn_no" value="<?php echo $grn_no; ?>" name="grn_no" class="form-control" />
										<?php 
										}
										}
										else{
									///	}
									//else {
										?>
										<input type="hidden" id="id" value="" name="id" class="form-control"/>
									<?php	
									        if($row['booking_id']!=''){
									    ?>
										<input type="text" id="grn_no"   name="grn_no" value="<?php echo $row['booking_id']; ?>" class="form-control" disabled/>
									<?php	
										}
										else{
									?>
										<input type="hidden" id="grn_no"   name="grn_no" class="form-control" />
										<input type="text" id="grn_no1" name="grn_no1" class="form-control" />
									<?php 
										}
										
										
										//}
										}
										?>
										<span id="grn_error"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">GRN.Date <span style="color:red;">*</span> :</label>
										<div class="input-group date date-picker table-height" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
											<input class="form-control table-height final" type="text" name="grn_date" value="<?php if($row['booking_date']!='')
											echo $row['booking_date']; else echo date('d-m-Y'); ?>"  id="grn_date" required> <span class="input-group-addon table-height"><i class="fa fa-calendar"></i></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Mode of Transportation <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
                                        <?php	
									        if($row['shipping_mode']!=''){
									    ?>
										<input type="text" id="shipping_mode"   name="shipping_mode" value="<?php echo $row['shipping_mode']; ?>" class="form-control" disabled/>
									<?php	
										}
										else{
									?>
										<input type="text" id="shipping_mode1" name="shipping_mode1" class="form-control" />
									<?php 
										}
                                        ?>
											<!-- <select name="mode_of_trasport" id="mode_of_trasport" class="form-control" required>
												<option value="">Mode of Transport</option>
												<?php 
													//$transport_query ="select * from mode_of_transportation where status=0";
													//$transport_result = mysqli_query($conn,$transport_query);
													//while($transport_row = mysqli_fetch_array($transport_result))
													//{
												?>
												<option value="<?php echo $transport_row['mode_id']; ?>" <?php if($transport_row['mode_id']==$row['mode_of_transportation']) echo "selected"; ?>><?php echo $transport_row['mode_type']; ?></option>
												<?php
													//}
												?>
											</select> -->
										</div>
									</div>
								</div>
								
								<div class="col-md-5">
									<div class="form-group">
										<label class="control-label col-sm-4">Origin <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
                                        <?php	
									        if($row['consignor_city']!=''){
									    ?>
										<input type="text" id="consignor_city"   name="consignor_city" value="<?php echo $row['consignor_city']; ?>" class="form-control" disabled/>
									<?php	
										}
										else{
									?>
										<input type="text" id="consignor_city1" name="consignor_city1" class="form-control" />
									<?php 
										}
                                        ?>
											<!-- <select name="origin" id="origin" class="form-control">
												<option value="">Select Origin</option>
												<?php 
												// 	$city_query ="select * from city where status=0 order by city_name asc";
												// 	$city_result = mysqli_query($conn,$city_query);
												// 	while($city_row = mysqli_fetch_array($city_result))
												// 	{
												// ?>
												// <option value="<?php //echo $city_row['city_id']; ?>" <?php //if($city_row['city_id']==$row['origin']) echo "selected"; ?>><?php //echo $city_row['city_name']; ?></option>
												// <?php
												// 	}
												?>
											</select> -->
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Destination <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
                                        <?php	
									        if($row['consignee_city']!=''){
									    ?>
										<input type="text" id="consignee_city"   name="consignee_city" value="<?php echo $row['consignee_city']; ?>" class="form-control" disabled/>
									<?php	
										}
										else{
									?>
										<input type="text" id="consignee_city1" name="consignee_city1" class="form-control" />
									<?php 
										}
                                        ?>
											<!-- <select name="destination" id="destination" class="form-control">
												<option value="">Select Destination</option>
												
												<?php 
											//	if($row['destination']>0){
												// $city_query1 ="select * from city where status=0 and city_id!='".$row['origin']."' order by city_name asc";
												// 	$city_result1 = mysqli_query($conn,$city_query1);
												// 	while($city_row1 = mysqli_fetch_array($city_result1))
												// 	{
												// ?>
												// <option value="<?php //echo $city_row1['city_id']; ?>" <?php //if($city_row1['city_id']==$row['destination']) echo "selected"; ?>><?php //echo $city_row1['city_name']; ?></option>
												// <?php
												// 	}
											//	}
												?>
												
												
											</select> -->
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Mode of Consignment <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
                                        <?php	
									        if($row['pay_mode']!=''){
									    ?>
										<input type="text" id="pay_mode"   name="pay_mode" value="<?php echo $row['pay_mode']; ?>" class="form-control" disabled/>
									<?php	
										}
										else{
									?>
										<input type="text" id="pay_mode1" name="pay_mode1" class="form-control" />
									<?php 
										}
										?>	<!-- <select name="mode_of_consignment" id="mode_of_consignment" class="form-control" required>
												<option value="">Select Consignment</option>
												<?php 
												// 	$consignment_query ="select * from consignment_mode where status=0";
												// 	$consignment_result = mysqli_query($conn,$consignment_query);
												// 	while($consignment_row = mysqli_fetch_array($consignment_result))
												// 	{
												// ?>
												// <option value="<?php //echo $consignment_row['consignment_id']; ?>" <?php //if($consignment_row['consignment_id']==$row['mode_of_consignment']) echo "selected"; ?>><?php //echo $consignment_row['consignment_mode']; ?></option>
												// <?php
												// 	}
												?>
											</select> -->
										</div>
									</div>
								</div>
							  
					 </div>
					 </fieldset>
				
							<fieldset class="my-fieldset">
							<legend>Consignor & Consignee Information</legend>
								<div class="row">
								<div class="col-md-offset-1 col-md-5">
									<div class="form-group">
										<label class="control-label col-sm-4">Consignor <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										<input type="text" name="consignor_name" class="form-control" required id="consignor_name" value="<?php echo $row['consignor_name']; ?>" />
										
											<input name="consignor" id="consignor" required value="<?php echo $row['consignor_name']; ?>" type="hidden" />
												
										</div> 
									</div>
									<div id="con_details" style="display:block">
									<div class="form-group">
										<label class="control-label col-sm-4">Address 1 <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<label class="control-label" id="address1"> </label>
										<?php echo $row['consignor_address'];?>
										</div>
									</div>
									<!-- <div class="form-group">
										<label class="control-label col-sm-4">Address 2 <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<label class="control-label" id="address2"> </label>
										</div>
									</div> -->
									<div class="form-group">
										<label class="control-label col-sm-4">State <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<label class="control-label" id="state"> </label>
                                            <?php echo $row['consignor_city'];?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">City <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<label class="control-label" id="city"> </label>
                                            <?php echo $row['consignor_city'];?>

										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Pincode <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<label class="control-label" id="pincode"> </label>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Phone <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<label class="control-label" id="phone"> </label>
                                            <?php echo $row['consignor_contact'];?>

										</div>
									</div>
									<!-- <div class="form-group">
										<label class="control-label col-sm-4">GST No <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<label class="control-label" id="gst_no"> </label>
										</div>
									</div> -->
								</div>
								</div>
								<div class="col-md-5">
									<div class="form-group">
										<label class="control-label col-sm-4">Consignee <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										
											
											<input type="text" name="consignee_name" class="form-control" required id="consignee_name" value="<?php echo $row['consignee_name']; ?>" />
										
											
												<input name="consignee" id="consignee" required value="<?php echo $row['consignee_name']; ?>" type="hidden" />
										</div>
									</div>
									<div id="con_details1"  style="display:block">
									<div class="form-group">
										<label class="control-label col-sm-4">Address 1 <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">

											<label class="control-label" id="con_address1"> </label>
										<?php echo $row['consignee_address'];?>

										</div>
									</div>
									<!-- <div class="form-group">
										<label class="control-label col-sm-4">Address 2 <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">

											<label class="control-label" id="con_address2"> </label>
										</div>
									</div> -->
									<div class="form-group">
										<label class="control-label col-sm-4">State <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
										
											<label class="control-label" id="con_state"> </label>
											<?php echo $row['consignee_city'];?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">City <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											
											<label class="control-label" id="con_city"> </label>
											<?php echo $row['consignee_city'];?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Pincode <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											
											<label class="control-label" id="con_pincode"> </label>
											<?php echo $row['consignee_pincode'];?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Phone <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											
											<label class="control-label" id="con_phone"> </label>
											<?php echo $row['consignee_contact'];?>
										</div>
									</div>
									<!-- <div class="form-group">
										<label class="control-label col-sm-4">GST No <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											
											<label class="control-label" id="con_gst"> </label>
										</div>
									</div> -->
								</div>
							  </div>
					 </div>
					 </fieldset>
					
							<fieldset class="my-fieldset">
							<legend>Package Information</legend>
								<div class="row">
								<div class="col-md-offset-1 col-md-10">
								<table class="table table-bordered tabs" width="100%">
								<thead>
									<tr>
										<th class="text-center" width="5%">S.No</th>
										<th class="text-center" width="10%">No of Packages</th>
										 <!-- <th class="text-center" width="18%">Type of Pkgs</th>
										<th class="text-center" width="13%">Party Invoice No</th> -->
										<!-- <th class="text-center" width="13%">Said to Contents</th> -->
                                        <!-- <th class="text-center" width="10%">Length</th>
										<th class="text-center" width="10%">Width</th>
										<th class="text-center" width="10%">Height</th> -->
                                        <th class="text-center" width="10%">Kgs</th>
										<th class="text-center" width="10%">Gross Wt.(Kgs)</th>
										<th class="text-center" width="10%">Charged wt.(Kgs)</th>
									</tr>
								</thead>
								<?php 
								
								if($_REQUEST['key']==''){
									// $pkg_option='<option> Select Package Type</option>';
									// $pkg_type_q=mysqli_query($conn,"select * from package where status='0'");
									// while($pkg_r=mysqli_fetch_array($pkg_type_q))
									// {
									// 	$pkg_option .='<option value="'.$pkg_r['package_id'].'">'.$pkg_r['package_code'].'</option>';
									// }
									
									?>
								<tbody>
								<?php 
									for($i=1;$i<=5;$i++){
								?>
									<tr>
                                    <td  class="text-center"><?php echo $j; ?></td>
									<td><input type="text" name="no_of_pkg" id="no_of_pkg<?php echo $i; ?>" class="form-control num_only text-right"></td>
									<!-- <td><input type="text" name="length[]" id="gross<?php echo $i; ?>" class="form-control  text-right num_only"></td>
									<td><input type="text" name="width[]" id="gross<?php echo $i; ?>" class="form-control  text-right num_only"></td>
									<td><input type="text" name="height[]" id="gross<?php echo $i; ?>" class="form-control  text-right num_only"></td> -->
									<td><input type="text" name="kgs" id="qty<?php echo $i; ?>" class="form-control num_only text-right"></td>			
                                    <td><input type="text" name="Gross_charged" id="charged<?php echo $i; ?>" class="form-control  text-right num_only"></td>
                                    <td><input type="text" name="W_charged" id="charged<?php echo $i; ?>" class="form-control  text-right num_only"></td>
									</tr>
								<?php 
									}
								
								?>
								
								</tbody>
								<?php 
								}
								else{
									
								?>
								<tbody>
								<?php 
									// $invoice_query = "select * from transaction_invoice_".$m."_".$y." where md5(transaction_id)='".$_REQUEST['key']."'";
									// $invoice_result = mysqli_query($conn,$invoice_query);
									$j=1;
									
									// while($invoice_row = mysqli_fetch_array($invoice_result)){
									
								?>
									<tr>
                                    <td  class="text-center"><?php echo $j; ?></td>
									<td><input type="text" name="no_of_pkg" id="no_of_pkg" class="form-control num_only text-right" value="<?php echo $row['no_of_package'];?>"></td>
									<!-- <td><input type="text" name="length[]" id="length" class="form-control  text-right num_only" value="<?php echo $row['length'];?>"></td>
									<td><input type="text" name="width[]" id="width" class="form-control  text-right num_only" value="<?php echo $row['width'];?>"></td>
									<td><input type="text" name="height[]" id="height" class="form-control  text-right num_only" value="<?php echo $row['height'];?>"></td> -->
									<td><input type="text" name="kgs" id="kgs" class="form-control num_only text-right" value="<?php echo $row['kgs'];?>"></td>			
                                    <td><input type="text" name="Gross_charged" id="charged" class="form-control  text-right num_only"></td>
                                    <td><input type="text" name="W_charged" id="charged" class="form-control  text-right num_only"></td>
									</tr>
								<?php 
								$j++;
									
								}
								
								?>
								
								</tbody>
								<?php 
								//}
								?>
								</table>
							  
					 </div>
					 </fieldset>
					
								<div class="row">
								<div class="col-md-offset-1 col-md-5">
								
							<fieldset class="my-fieldset">
							<legend>Volumetric Consignment(If Any)</legend>
									<div class="form-group">
										<label class="control-label col-sm-4">Goods Dedared value(INR):</label>
										<div class="col-lg-8">
											<input type="text" id="goods_dedared_value" value="<?php echo $row['goods_dedared_value'] ?>" name="goods_dedared_value" class="form-control text-right" />
										</div>
									</div>
								
									<div class="form-group">
										<label class="control-label col-sm-4">Dimensions(L X W X H in cms):</label>
										<div class="col-lg-8">
											<input type="text" id="length" name="length" value="<?php echo $row['length']; ?>" class="form-control" />
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4"></label>
										<div class="col-lg-8">
											<input type="text" id="width" name="width" value="<?php echo $row['width']; ?>" class="form-control" />
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4"></label>
										<div class="col-lg-8">
											<input type="text" id="height" name="height" value="<?php echo $row['height']; ?>" class="form-control" />
										</div>
									</div>
									<div class="form-group">
									<label class="control-label col-sm-4">Amount In Words:</label>
									<div class="col-lg-8">
										<textarea name="amount_in_words" id="amount_in_words"  rows="3" readonly class="form-control"><?php echo $row['total_words']; ?></textarea>
									</div>
									</div>
									
									</fieldset>
				
							</div>	
							<div class="col-md-5">
												
							<fieldset class="my-fieldset">
								<legend>Payment Information</legend>
								<table class="table">
									<thead>
										<tr>
											<th>Particulars</th>
											<th>Rate</th>
											<th>Amount(INR)</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>Freight</td>
											<td><input type="text" name="frieght_rate" id="frieght_rate" value="<?php echo $row['frieght_rate']; ?>" class="form-control text-right"/></td> 
											<td><input type="text" name="frieght_amount" id="frieght_amount"  value="<?php echo $row['frieght_amount']; ?>" class="form-control  text-right calculation"/></td>
										</tr>
										<tr>
											<td>C.O.D</td>
											<td><input type="text" name="cod_rate" id="cod_rate" class="form-control text-right" value="<?php echo $row['cod_rate']; ?>" /></td>
											<td><input type="text" name="cod_amount" id="cod_amount" value="<?php echo $row['cod_amount']; ?>" class="form-control text-right calculation"/></td>
										</tr>
										<tr>
											<td>F.O.V</td>
											<td><input type="text" name="fov_rate" id="fov_rate" class="form-control text-right" value="<?php echo $row['fov_rate']; ?>" /></td>
											<td><input type="text" name="fov_amount" id="fov_amount" value="<?php echo $row['fov_amount']; ?>" class="form-control text-right calculation"/></td>
										</tr>
										<tr>
											<td>Doc.Charges</td>
											<td><input type="text" name="doc_rate" id="doc_rate" value="<?php echo $row['doc_charges']; ?>" class="form-control text-right"/></td>
											<td><input type="text" name="doc_amount" id="doc_amount"  value="<?php echo $row['doc_amount']; ?>"   class="form-control text-right calculation"/></td>
										</tr>
										<tr>
											<td>Cartage</td>
											<td><input type="text" name="cartage_rate" id="cartage_rate"  value="<?php echo $row['cartage_rate']; ?>" class="form-control text-right"/></td>
											<td><input type="text" name="cartage_amount" id="cartage_amount"  value="<?php echo $row['cartage_amount']; ?>" class="form-control text-right calculation"/></td>
										</tr>
										<tr>
											<td>Labour Handling</td>
											<td><input type="text" name="labour_rate" id="labour_rate" value="<?php echo $row['labour_handling_rate']; ?>"  class="form-control text-right"/></td>
											<td><input type="text" name="labour_amount" id="labour_amount"  value="<?php echo $row['labour_handling_amount']; ?>" class="form-control text-right calculation"/></td>
										</tr>
									<!--	<tr>
											<td>Octroi</td>
											<td><input type="text" name="octroi_rate" id="octroi_rate"  value="<?php echo $row['octroi_rate']; ?>"  class="form-control"/></td>
											<td><input type="text" name="octroi_amount" id="octroi_amount" value="<?php echo $row['octroi_amount']; ?>"  class="form-control calculation"/></td>
										</tr> -->
										<tr>
											<td>Any Other charges</td>
											<td><input type="text" name="other_rate" id="other_rate" value="<?php echo $row['other_charge_rate']; ?>"  class="form-control text-right"/></td>
											<td><input type="text" name="other_amount" id="other_amount"  value="<?php echo $row['other_charge_amount']; ?>"  class=" text-right form-control calculation"/></td>
										</tr>
										<tr>
											<td>GST (as applicable)</td>
											<td><input type="text" name="gst_rate" id="gst_rate" value="<?php echo $row['gst_rate']; ?>"  class="form-control text-right"/></td>
											<td><input type="text" name="gst_amount" id="gst_amount" value="<?php echo $row['gst_amount']; ?>"  class="form-control text-right calculation"/></td>
										</tr>
										<tr>
											<td colspan="2"><span class="align-right">Total</span></td>
											<td><input type="text" name="total" class="form-control text-right" value="<?php echo $row['total']; ?>" readonly id="total"></td>
										</tr>
									</tbody>
								</table>
							</fieldset>
					
							</div>
					 </div>
					 <div class="row">
						<div class="col-md-offset-1 col-md-5">
							<div class="form-group">
								<label class="control-label col-sm-4">Truck/ Vehicle  No:</label>
								<div class="col-lg-8">
									<input type="text" name="vehicle_no" id="vehicle_no" value="<?php echo $row['truck']; ?>" class="form-control" >
									
								</div>
							</div>
								<br/>
								
						<img src="<?php echo $row['consiner_signature'];  ?>" id="signature_image"  style="display:none" width="40%" height="20%">
							<label class="control-label col-md-12" style="text-align:  left;">Consignor Signature</label>
							   <div id="content" class="col-md-12">
									<div id="signatureparent">
										<div id="signature">
										</div>
									</div>
									<div id="display_signature" ></div>
									<div id="tools"></div>
								</div>
							<input type="hidden" name="signature" id="signature_val">
						</div>
						
				<?php
				if($_REQUEST['key']==''){
				?>
					<div class="col-md-5">
						<label class="control-label col-sm-12" style="text-align: left;font-weight: 600;">Attachments (Image & Documents)</label>
							<div class="file-container">
								<div class="col-md-12 file-group" id="file-no1" data-file-no="1">
									<div class="col-md-6">
										<input type="file" id="file_receipt1" name="file_receipt" class="filestyle" data-id="1" data-buttonBefore="true" data-buttonName="btn-primary">
									</div>
									
									<div class="col-md-2">
										<img src="images/no_image.png" class="image_preview" id="image_preview1">
									</div>
									<div class="col-md-2">
									<button data-id="1" class="btn btn-danger remove-image">Remove</button>
								</div>
								</div>
							</div>
							<div class="col-lg-8">
								<button id="add_more" type="button" class="btn btn-primary">Add More</button>
							</div>
					</div>
				<?php 
				}
				else{
				?>
				<div class="col-md-5">
						<label class="control-label col-sm-12">Attachments (Image & Documents):</label>
						<div class="file-container">
								
								
						<?php 
							// $transaction_image_query = "select * from transaction_images_".$m."_".$y." where md5(transaction_id) = '".$_REQUEST['key']."' and eway_status=0";
							// $transaction_image_result = mysqli_query($conn,$transaction_image_query);
							// $k=1;
							// while($transaction_image_row = mysqli_fetch_array($transaction_image_result)){
						?>
						<div class="col-md-12 file-group" id="file-no" data-file-no="<?php echo $k; ?>">
								<div class="col-md-6">
										<input type="file" id="file_receipt<?php echo $k; ?>" name="file_receipt" class="filestyle image_attach" data-id="<?php echo $k; ?>" data-buttonBefore="true" data-buttonName="btn-primary" value="<?php echo $image; ?>">
									</div>
									
									<div class="col-md-2">
										<img src="../uploads/<?php echo $row['attchment'];  ?>" class="image_preview"  id="image_preview<?php echo $k; ?>" >
									</div>
								<div class="col-md-2">
									<button data-id="<?php echo $k; ?>" id="<?php echo $row['attachment_id'];  ?>" class="btn btn-danger remove-image" type="button">Remove</button>
								</div>
								</div>
							<?php 
							//}
							?>
							
							</div>
							<!-- <div class="col-lg-8">
								<button id="add_more" type="button" class="btn btn-primary">Add More</button>
							</div> -->
					</div>
				<?php 
				}
				?>
				</div>
					 </div>
				 <br/>
					   <div class="row">
						<div class="col-md-12 form-action">
							<button class="btn btn-primary" type="button" id="save">Submit</button>
							<button class="btn btn-default-outline  btn-reset btn-cancel" type="button">Cancel</button>
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
				$('.grn_no_popup').hide();
				 
				 function isChar(evt, element) {
						var charCode = (evt.which) ? evt.which : event.keyCode

						if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&      // â€œ-â€ CHECK MINUS, AND ONLY ONE.
							(charCode != 46 || $(element).val().indexOf('.') != -1) &&      // â€œ.â€ CHECK DOT, AND ONLY ONE.
							(charCode < 48 || charCode > 57) && (charCode < 64 || charCode > 90))
							return false;
						//	return true;
				} 
				$('#vehicle_no').keypress(function (event) {
					return isChar(event, this)
				});

		var role='<?php echo $_SESSION['role']; ?>';
		var id='<?php echo $_SESSION['user_id']; ?>';
		console.log(role);
		var date = '<?php date('d-m-Y'); ?>';
		if(role == 'CL'){
			$('#consignor_name').attr("disabled","disabled");
		}
		
		if(role=="CL")
		{
			$('#grn_date').datepicker({
			startDate:date,
			format:"dd-mm-yyyy",
			endDate:date
		});
				
				$.ajax({
				    async:false,
					url:'../fetch-details.php',
					type:"GET",
					dataType:"JSON",
					data:{cmd:"get_consignment",tbl_id:id},
					
					success:function(result){
						console.log(result);
						
						setTimeout(function(){
							 //$("#origin").val(result['city']).trigger("change"); 
							 $("#origin").val(result['city']); 
							
					$("#con_details").show();
					$("#consignor").val(result['client_id']);
					$("#consignor_name").val(result['client_company_name']).prop("readonly");
					$('#address1').html(result['address1']);
						$('#address2').html(result['address2']);
						$('#city').html(result['city_name']);
						
						$('#state').html(result['state_name']);
						$('#pincode').html(result['pincode']);
						
						$('#phone').html(result['contact_no']);
						$('#gst_no').html(result['gst_no']);
						$('#consignee_name').focus();
						
												}, 300);

						
					},
					error:function(jqxhr){
						console.log(jqxhr.responseText);
					}
				});		
				
		
			
		}
		else{
		 if(role =='AD')
		    {
		        $('#grn_date').datepicker({
		            format:"dd-mm-yyyy",
		            autoclose: true
				});
		        
		    }
		    else{
		        $('#grn_date').datepicker({
		        	startDate:date,
		        	format:"dd-mm-yyyy",
		        	autoclose: true
	        	});
		    }
		}
				$(document).on('change','#destination',function(e){
					reset_consignee();
				});
				$(document).on('change','#origin',function(e){
					var id = $(this).val();
					reset_consignor();
					reset_consignee();
			$.ajax({
			    async:false,
					url:'fetch_details.php',
					
					type:"GET",
					dataType:"JSON",
						
					data:{cmd:"get_destination_consignor",id:id},
				
					success:function(result){
					// alert(result['destination']);
					setTimeout(function(){ 
						   $('#destination').html(result['destination']);
						  // $('#destination').val(result['destination']).attr("selected","selected");	
						//$('#consignor').html(result['consignor']);	
						//$('#vehicle_no').html(result['vehicle']);	
						
						
						    
						}, 3000);
						
					}
				});
							
				});
		//cancel button
		$(document).on('click','.btn-cancel',function(){
			window.location.href = "user-consignment.php";
		});
		
		// $(document).on('keyup','#consignor_name',function(){
		// 		var origin = $('#origin').val();
		// 		var term = $(this).val();
		// 		//console.log('autocomplete_list.php?autocomplete=consignor_autocomplete&origin='+origin+'&term='+term);
		// 		$("#consignor_name").autocomplete({
		// 			source:'autocomplete_list.php?autocomplete=consignor_autocomplete&origin='+origin+'&term='+term,
		// 			minLength:0,
		// 			select: function(event, ui) {
		// 				$("#consignor_name").val(ui.item.value);
		// 				$("#consignor").val(ui.item.id);
							
		// 			$.ajax({
		// 			url:'fetch_details.php',
		// 			type:"GET",
		// 			dataType:"JSON",
		// 			data:{cmd:"get_client_details_consignment",tbl_id:ui.item.id,consignor:"consignor"},
		// 			async:false,
		// 			success:function(result){
		// 				console.log(result);
		// 				//alert(ui.item.id);
		// 				if(ui.item.id == '3631'){
						    
		// 				    $('#id').val(result['grn_id']);
		// 				    $('#grn_no').val(result['grn_no'])
		// 				    $('#grn_no1').val(result['grn_no']).attr("disabled",true);
						    
		// 				}
		// 				else{
		// 				      $('#grn_no').val("").attr("disabled",false);
		// 				      $('#id').val("");
		// 				      $('#grn_no').val("");
						      
						    
		// 				}
		// 				$("#con_details").show();
		// 				if($('#origin').val()=="")
		// 				$('#origin').val(result['city']).trigger("change");
		// 				$("#consignor").val(ui.item.id);
		// 				$('#address1').html(result['address1']);
		// 				$('#address2').html(result['address2']);
		// 				$('#city').html(result['city_name']);
						
		// 				$('#state').html(result['state']);
		// 				$('#pincode').html(result['pincode']);
		// 				$('#phone').html(result['contact_no']);
		// 				$('#gst_no').html(result['gst_no']);
		// 				$('#consignee_name').focus();
						
		// 			},
		// 			error: function(jqxhr) {
		// 					alert(jqxhr.responseText);
		// 				}
		// 		});					
				
		// 			},
					
		// 		});
				
		// 	});
			
		$(document).on('keyup','#consignor_name',function(event){
			var key=event.keyCode;
			if(key==8 || key==46)
				reset_consignor();
		});	
		
		$(document).on('keyup','#consignee_name',function(event){
			var key=event.keyCode;
			if(key==8 || key==46)
				reset_consignee();
		});
		
		// $(document).on('keyup','#consignee_name',function(){
		// 		var destination = $('#destination').val();
		// 		var consignor = $('#consignor').val();
		// 		var term = $(this).val();
		// 		console.log('autocomplete_list.php?autocomplete=consignee_autocomplete&destination='+destination+'&consignor='+consignor+'&term='+term);
		// 		$("#consignee_name").autocomplete({
		// 			source:'autocomplete_list.php?autocomplete=consignee_autocomplete&destination='+destination+'&consignor='+consignor+'&term='+term,
		// 			minLength:0,
		// 			select: function(event, ui) {
		// 				$("#consignee_name").val(ui.item.value);
		// 				$("#consignee").val(ui.item.id);
							
		// 			$.ajax({
		// 			url:'fetch_details.php',
		// 			type:"GET",
		// 			dataType:"JSON",
		// 			data:{cmd:"get_client_details_consignment",tbl_id:ui.item.id},
		// 			async:false,
		// 			success:function(result){
		// 				console.log(result);

		// 				$("#con_details1").show();
		// 				if($('#destination').val()=="")
		// 				$('#destination').val(result['city']);
		// 				$("#consignee").val(ui.item.id);
		// 				$('#con_address1').html(result['address1']);
		// 				$('#con_address2').html(result['address2']);
		// 				$('#con_city').html(result['city_name']);
		// 				$('#con_state').html(result['state']);
		// 				$('#con_pincode').html(result['pincode']);
		// 				$('#con_phone').html(result['contact_no']);
		// 				$('#con_gst').html(result['gst_no']);
		// 				$('#no_of_pkg1').focus();
		// 			}
		// 		});	
		// 			},
					
		// 		});
				
		// 	});
	
	function reset_consignor()
	{

						$('#consignor').val('');
						$('#consignor_name').html('');
						$('#address1').html('');
						$('#address2').html('');
						$('#city').html('');
						$('#state').html('');
						$('#pincode').html('');
						$('#phone').html('');
						$('#gst_no').html('');
	}
	
	function reset_consignee()
	{
						$('#consignee').html('');
						$('#consignee_name').html('');
						$('#con_address1').html('');
						$('#con_address2').html('');
						$('#con_state').html('');
						$('#con_pincode').html('');
						$('#con_phone').html('');
						$('#con_gst').html('');
	}
	
				var chck_key=true;
				$(document).on('keyup','#grn_no',function(e){
				var grn_no = $(this).val();
				var grn_id = $("#grn_id").val();
				
				$.ajax({
					url:'check_existing.php',
					type:"GET",
					dataType:"JSON",
					data:{cmd:"chk_grn_no",grn_no:grn_no,grn_id:grn_id},
					async:false,
					success:function(result){
						console.log(result);
						if(result[0]=="1")
						{
							$("#grn_error").html(result[1]).attr("style","color:red");
							chck_key=false;
							
						}
						else
						{
							chck_key=true;
							$("#grn_error").html('');
						}
							
					}
				});					
				});
				
				$(document).on("change", ".calculation", function() {
					var sum = 0;
					
					$(".calculation").each(function(){
						sum += +$(this).val();
						
					});
					parseFloat($("#total").val(sum)).toFixed(2);
					$.ajax({
						url:'fetch_details.php',
						type:"post",
						data:{cmd:"get_amount_words",val:sum},
						success:function(result){
							console.log(result);
							$('#amount_in_words').val(result);
						},
						error: function(jqxhr) {
							//alert(jqxhr.responseText);
						}
					});
					//$('#total').val(parseFloat($('#total').val(sum)).toFixed(2));
				});
					
		
		var signature_image = '<?php echo $row['consigner_signature'];  ?>';
			
	 	$('#display_signature').html('<img src='+signature_image+'>');
		if(signature_image==""){
			$('div#signatureparent').removeClass('height_check');
		}
		else{
			$('div#signatureparent').addClass('height_check');
		} 
				 $('.datepicker').on("click", function() {
					$(this).datepicker({
							startDate: new Date(),
							changeMonth: true,
							changeYear: true,
							gotoCurrent: true,
							dateFormat: 'yy-mm-dd',
							maxDate: new Date(),
							yearRange: '1980:c',
							defaultDate: '-10y'
						}).datepicker('show');
					});
					
					$('.num_only,#grn_no,#goods_dedared_value').keypress(function (event) {
					return isNumber(event, this)
				});
					
			$('.calculation').keypress(function (event) {
					return isNumber(event, this)
				});
				
				
		   function isNumber(evt, element) {
				var charCode = (evt.which) ? evt.which : event.keyCode

				if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&      // â€œ-â€ CHECK MINUS, AND ONLY ONE.
					(charCode != 46 || $(element).val().indexOf('.') != -1) &&      // â€œ.â€ CHECK DOT, AND ONLY ONE.
					(charCode < 48 || charCode > 57))
					return false;
					return true;
				}    
			    $(document).on('blur', 'input.calculation', function(ev){
					if($(this).val() != "")
					$(this).val(parseFloat($(this).val()).toFixed(2));
					else
					$(this).val("0.00");

				});
			$(document).on('click', '.btn-del-file', function(evt){
				$(this).closest(".file-group").remove();
			});
			//image Preview
				function readURL(input, portfolio_no) {
					if (input.files && input.files[0]) {
						var reader = new FileReader();
						reader.onload = function (e) {
							$('#'+portfolio_no).attr('src', e.target.result);
						}

						reader.readAsDataURL(input.files[0]);
					}
				}

				$(document).on('change', '.upload-image', function(ev){
					var portfolio_no = $(this).attr("data-img-preview-id");
					readURL(this, portfolio_no);
				});	
				$(document).on('change', '#goods_dedared_value', function(ev){
					
					 if($(this).val() != "")
					$(this).val(parseFloat($(this).val()).toFixed(2));
					else
					$(this).val("0.00");
					 
				});
			//signature
		
				var $sigdiv = $("#signature").jSignature({
					'background-color': 'transparent',
					'decor-color': 'transparent'
				})
			, $tools = $('#tools')
			
			$('#signature img').attr('src',"");
			$('#signature img').attr('style', '');
				$('#tools').html('<br/><input type="button" id="clear_signature" value="Clear">');
				$(document).on('click','#clear_signature',function(){
					$('#display_signature').html('');
					$("#signature").show();
					//$sigdiv.jSignature('reset')
					$('#signature').jSignature('clear');
						$("#signature_capture").val('');
						$("#display_signature").attr("style","");
					
					$('div#signatureparent').removeClass('height_check');
		  
				});	
				
			//addmore
		var attachment_id = [];	
		$(document).on('click','.remove-image',function(){
				var id = $(this).attr('data-id');
				$('#file-no'+id).remove();
				var image_id = $(this).attr('id');
					attachment_id.push(image_id);
				
			});
			
				$(document).on('click','#save',function(){
					var data1 = $sigdiv.jSignature('getData');
				//alert(chck_key);
					$('#signature_val').val(signature_image);
			if(($sigdiv.jSignature('getData', 'native').length != 0)){
				$('#signature_val').val(data1);
	//alert(data1);
			}
			var edit_id = $('#edit_id').val();
				var file_data = $('.filestyle').prop('files')[0];
					var formData = new FormData(document.getElementById("user_grn_details")); 
					formData.append("file",file_data);
					if($('#user_grn_details').valid() == true && chck_key == true)
					{
						$(".form-data-saving").show();
						$(this).prop("disabled",true);
						$.ajax({
							url:"../save_details.php?id="+attachment_id,
							type:"post",
							dataType:"json",
							data:formData,
							processData: false,
							contentType: false,
							success:function(result){
								//console.log(result);
								 if(result != 0){
										$(".form-data-saving").hide();
										$("#alert-status").text("");
										$("#alert-message").text("Saved Successfully");
										$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
										$("#alert-container").hide();
										$("#alert-container").removeClass("alert-success");
										//location.reload();
										window.location.href = "user-consignment.php";
										});
									}else{
									$(".form-data-saving").hide();
									$("#alert-status").text("Alert !!! ");
									$("#alert-message").text("Booking Failed");
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
				
				//popupclose
				$(document).on('click','.grn_close_popup',function(){
					$(".grn_no_popup").hide();
					$(".form-data-saving").hide();
					$("#alert-status").text("");
					$("#alert-message").text("Booked Successfully");
					$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
					$("#alert-container").hide();
					$("#alert-container").removeClass("alert-success");
					//location.reload();
					window.location.href = "transaction_list.php";
					});
					
				});
						 function readURL(id,input) {
			if (input.files && input.files[0]) {
				var reader = new FileReader();
				
				reader.onload = function (e) {
					$('#image_preview'+id+'').attr('src', e.target.result);
				}
				
				reader.readAsDataURL(input.files[0]);
			}
		}
		
		$(document).on('change','.filestyle',function(){
			var id = $(this).attr("data-id");
			readURL(id,this);
		});
		
			$(document).on('click', '#add_more', function(evt){
				$file_no = $(".file-group:last").data("file-no");
				$file_no = isNaN($file_no) ? 1 : (parseInt($file_no)+1);
				// alert($file_no);
				$new_file = '<br/><div class="col-md-12 file-group" id="file-no'+$file_no+'" data-file-no="'+$file_no+'" style="margin-top:58px;margin-left: -4px;">\
								<div class="col-md-6">\
									<input type="file" id="file_receipt'+$file_no+'" name="file_receipt[]" data-id="'+$file_no+'" class="filestyle" data-buttonBefore="true" data-buttonName="btn-primary">\
								</div>\
								<div class="col-md-2">\
									<img src="images/no_image.png" class="image_preview" id="image_preview'+$file_no+'">\
								</div>\
								<div class="col-md-2">\
									<button data-id='+$file_no+' class="btn btn-danger remove-image" type="button">Remove</button>\
								</div>\
							</div>';
							 
				$(".file-container").append($new_file);
				//$("#file_receipt"+$file_no).filestyle({buttonBefore: true,buttonName: "btn-primary"});
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
			
		 
			
				
			<div class="grn_no_popup" style="display:none" >
				<div class="popup_overlay" id="popup_overlay"></div>
				<div class="popup" id="popup">
					<div class="popup_message">
					<h5 class="popup-title">GRN Number</h5>
						<span id="show_grn_no"></span> <br/> &nbsp; <br/>
					<button class="btn btn-sm btn-primary delete-error-popup-close grn_close_popup" >Close</button> <br/> &nbsp; <br/>
					</div>
					
				</div>
			</div>
			
	  </body>
	</html>

