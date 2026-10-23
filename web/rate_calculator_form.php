<?php
require_once("include/connect.php");
require_once("include/function.php"); 

$key = $_REQUEST['key'] ?? '';
if ($key === '' && empty($_GET['create'])) {
	header('Location:rate_calc_list.php');
	exit;
}
if ($key != '') {
	$rate_check = mysqli_query($conn, "select id from rate where md5(id)='" . mysqli_real_escape_string($conn, $key) . "'");
	if (!$rate_check || mysqli_num_rows($rate_check) == 0) {
		header('Location:rate_calc_list.php');
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
				  <a href="rate_calc_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
				  <h1 class="ew-page-title"><?php echo ($_REQUEST['key'] != '') ? 'Edit Rates' : 'Add Rates'; ?></h1>
				</div>
				<div class="ew-toolbar-right">
				  <a href="rate_calc_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
				</div>
			  </div>
			  <div class="ew-card">
				<h2 class="ew-card-section-title">Rate Details</h2>
				<div class="ew-form-body">
				<form id="add_rate_form">
				
				<?php if($_REQUEST['key'] != ''){?>
					<input type="hidden" id="form_name" name="form_name" value="edit_rate_calculator">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $_REQUEST['key']; ?>">
					<?php } else{?>
					<input type="hidden" id="form_name" name="form_name" value="rate_calculator">
					<input type="hidden" id="edit_id" name="edit_id" value="">
			       <?php
				}?>
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="ew-form-grid">
						<?php
							$key_sql = mysqli_real_escape_string($conn, (string) ($_REQUEST['key'] ?? ''));
							$query = "select * from rate where md5(id)='".$key_sql."'";
							$result = mysqli_query($conn,$query);
							$row = $result ? mysqli_fetch_array($result) : null;
							if (!is_array($row)) {
								$row = array('origin' => '', 'destination' => '', 'surface' => '', 'express' => '', 'train' => '', 'air' => '', 'note' => '');
							}
							$city_options = array();
							$city_q = mysqli_query($conn, "SELECT city_name FROM city WHERE status=0 ORDER BY city_name ASC");
							if ($city_q) {
								while ($city = mysqli_fetch_assoc($city_q)) {
									$name = trim((string) ($city['city_name'] ?? ''));
									if ($name === '') {
										continue;
									}
									$city_options[$name] = $name;
								}
							}
							$rate_city_name = function ($saved) {
								$saved = trim((string) $saved);
								if ($saved === '') {
									return '';
								}
								$dash = strpos($saved, '-');
								if ($dash !== false) {
									$saved = trim(substr($saved, $dash + 1));
								}
								return $saved;
							};
							foreach (array('origin', 'destination') as $saved_key) {
								$saved_name = $rate_city_name($row[$saved_key] ?? '');
								if ($saved_name !== '' && !isset($city_options[$saved_name])) {
									$city_options = array($saved_name => $saved_name) + $city_options;
								}
							}
							$rate_field_for_mode = function ($mode_type) {
								$t = strtolower(trim((string) $mode_type));
								if (strpos($t, 'air') !== false) {
									return 'air';
								}
								if (strpos($t, 'train') !== false) {
									return 'train';
								}
								if (strpos($t, 'express') !== false || strpos($t, 'urgent') !== false) {
									return 'express';
								}
								return 'surface';
							};
							$modes = array();
							$mode_q = mysqli_query($conn, "SELECT mode_id, mode_type FROM mode_of_transportation WHERE status=0 ORDER BY mode_type ASC");
							if ($mode_q) {
								while ($mode = mysqli_fetch_assoc($mode_q)) {
									$modes[] = $mode;
								}
							}
							$filled_rates = array();
							foreach (array('surface', 'express', 'train', 'air') as $rate_key) {
								if (trim((string) ($row[$rate_key] ?? '')) !== '') {
									$filled_rates[] = $rate_key;
								}
							}
							$preselect_rate = count($filled_rates) === 1 ? $filled_rates[0] : '';
							$mode_selected = false;
						?>
						<div class="ew-section-label">Route &amp; Pricing</div>
						<div class="ew-field">
							<label class="control-label">Origin <span style="color:red;">*</span> :</label>
							<select id="origin" name="origin" class="form-control select2able" required>
								<option value="">Select Origin</option>
								<?php foreach ($city_options as $city_label) { ?>
								<option value="<?php echo htmlspecialchars($city_label, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($rate_city_name($row['origin']) === $city_label) ? 'selected' : ''; ?>><?php echo htmlspecialchars($city_label, ENT_QUOTES, 'UTF-8'); ?></option>
								<?php } ?>
							</select>
							<span id="origin_dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Destination <span style="color:red;">*</span> :</label>
							<select name="destination" id="destination" class="form-control select2able" required>
								<option value="">Select Destination</option>
								<?php foreach ($city_options as $city_label) { ?>
								<option value="<?php echo htmlspecialchars($city_label, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($rate_city_name($row['destination']) === $city_label) ? 'selected' : ''; ?>><?php echo htmlspecialchars($city_label, ENT_QUOTES, 'UTF-8'); ?></option>
								<?php } ?>
							</select>
							<span id="destination_dup-check"></span>
						</div>
						<div class="ew-field">
							<label class="control-label">Mode <span style="color:red;">*</span> :</label>
							<select id="mode" name="mode" class="form-control select2able" required>
								<option value="">Select Mode</option>
								<?php foreach ($modes as $mode) {
									$rate_field = $rate_field_for_mode($mode['mode_type']);
									$selected = (!$mode_selected && $preselect_rate !== '' && $preselect_rate === $rate_field);
									if ($selected) {
										$mode_selected = true;
									}
								?>
								<option value="<?php echo (int) $mode['mode_id']; ?>" data-rate="<?php echo $rate_field; ?>" <?php echo $selected ? 'selected' : ''; ?>><?php echo htmlspecialchars($mode['mode_type'], ENT_QUOTES, 'UTF-8'); ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="ew-field rate-field-wrap" data-rate="surface" style="display:none;">
							<label class="control-label"><span class="rate-label">Surface</span> <span style="color:red;">*</span> :</label>
							<input type="text" name="surface" id="surface" value="<?php echo htmlspecialchars((string) $row['surface'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control" autocomplete="off" placeholder="Enter Surface Rate"/>
						</div>
						<div class="ew-field rate-field-wrap" data-rate="express" style="display:none;">
							<label class="control-label"><span class="rate-label">Express</span> <span style="color:red;">*</span> :</label>
							<input type="text" name="express" id="express" class="form-control" value="<?php echo htmlspecialchars((string) $row['express'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" placeholder="Enter Express Rate"/>
						</div>
						<div class="ew-field rate-field-wrap" data-rate="train" style="display:none;">
							<label class="control-label"><span class="rate-label">Train</span> <span style="color:red;">*</span> :</label>
							<input type="text" name="train" id="train" class="form-control" value="<?php echo htmlspecialchars((string) $row['train'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" placeholder="Enter Train Rate"/>
							<span class="dup-check"></span>
						</div>
						<div class="ew-field rate-field-wrap" data-rate="air" style="display:none;">
							<label class="control-label"><span class="rate-label">Air</span> <span style="color:red;">*</span> :</label>
							<input type="text" name="air" id="air" value="<?php echo htmlspecialchars((string) $row['air'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control" autocomplete="off" placeholder="Enter Air Rate"/>
							<span class="email-dup-check"></span>
						</div>
						<div class="ew-field span-2">
							<label class="control-label">Note:</label>
							<textarea name="note" id="note" class="form-control" placeholder="Enter Note"><?php echo $row['note']; ?></textarea>
							<span class="dup-check"></span>
						</div>
				 </div>
				</form>
				</div>
				<div class="ew-form-footer">
					<a class="ew-btn-v2 ew-btn-v2-outline btn-reset" href="rate_calc_list.php">Cancel</a>
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

		function showRateForMode() {
			var $opt = $('#mode option:selected');
			var field = $opt.data('rate') || '';
			var label = $.trim($opt.text());
			$('.rate-field-wrap').hide().find('input').prop('required', false);
			if (field) {
				var $wrap = $('.rate-field-wrap[data-rate="' + field + '"]');
				$wrap.show();
				$wrap.find('.rate-label').text(label);
				$wrap.find('input').prop('required', true);
			}
		}
		$('#mode').on('change', showRateForMode);
		showRateForMode();

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
				data : {cmd: "chk_origin_destination",origin:origin, destination: destination, edit_id: edit_id}, // post data || get data
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
				var data = $('#add_rate_form').serialize();
				//duplicate_check();
				if($('#add_rate_form').valid() == true )
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
							if(result != 0){
								$(".form-data-saving").hide();
								$("#alert-status").text("");
								$("#alert-message").text("Saved Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								location.href="rate_calc_list.php";
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
				var data = $('#add_rate_form').serialize();
				//var edit_id = $("#edit_id").val();
				//duplicate_check();
				if($('#add_rate_form').valid() == true && dup_chk)
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
								location.href="rate_calc_list.php";
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