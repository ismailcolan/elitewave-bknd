<?php
require_once("include/connect.php");
require_once("include/function.php");
require_once("include/vendor_master_helpers.php");

ew_vendor_ensure_table($conn);

$key = $_REQUEST['key'] ?? '';
$row = array(
	'vendor_name' => '',
	'vendor_code' => '',
	'contact_person' => '',
	'address1' => '',
	'address2' => '',
	'state' => '',
	'city' => '',
	'pincode' => '',
	'vendor_type' => '',
	'email' => '',
	'email_alt' => '',
	'contact_no' => '',
	'contact_no2' => '',
	'gstin' => '',
	'pan_no' => '',
	'status' => 0,
	'mode_of_transport' => '',
	'service_type' => '',
	'operating_from' => '',
	'operating_to' => '',
	'payment_terms' => '',
	'credit_days' => '',
	'account_holder_name' => '',
	'bank_name' => '',
	'account_number' => '',
	'ifsc' => '',
	'bank_branch' => '',
);
$is_edit = ($key != '');
if ($is_edit) {
	$vendor_query = "SELECT * FROM vendor_master WHERE md5(vendor_id)='" . mysqli_real_escape_string($conn, $key) . "'";
	$vendor_result = mysqli_query($conn, $vendor_query);
	if (!$vendor_result || mysqli_num_rows($vendor_result) == 0) {
		header('Location:vendor_list.php');
		exit;
	}
	$row = mysqli_fetch_array($vendor_result);
} else {
	$next = ew_vendor_next_code($conn);
	$row['vendor_code'] = $next['vendor_code'];
}

$vendor_types = ew_vendor_type_options();
$vendor_modes = ew_vendor_mode_options();
$vendor_services = ew_vendor_service_options();
?>
<!DOCTYPE html>
<html>

<head>
	<?php include("include/title.php"); ?>
	<?php include("include/css_js.php"); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		#vendor_form .row.vendor-form-row {
			margin-left: 0;
			margin-right: 0;
		}

		#vendor_form .row.vendor-form-row>[class*="col-"] {
			padding-left: 15px;
			padding-right: 15px;
		}

		#vendor_form .form-group>.control-label {
			display: block;
			float: none;
			width: 100%;
			text-align: left;
			padding-top: 0;
		}

		#vendor_form .vendor-section-title {
			margin: 18px 0 12px;
			padding-bottom: 6px;
			border-bottom: 1px solid #e4e4e4;
			font-size: 15px;
			font-weight: 600;
			color: #333;
		}

		#vendor_form .vendor-section-title:first-child {
			margin-top: 0;
		}
	</style>
</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
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
						<div class="heading"><i class="fa fa-plus"></i>Vendor <span class="align-right"><i class="fa fa-plus"></i><a href="vendor_list.php">View List</a></span></div>
						<div class="widget-content padded">
							<form class="form-horizontal" id="vendor_form">
								<input type="hidden" id="form_name" name="form_name" value="add_vendor">
								<input type="hidden" id="edit_id" name="edit_id" value="<?php echo htmlspecialchars($key); ?>">

								<div id="response" class="alert alert-danger" style="display:none;">
									<div class="message" style="text-align:center"></div>
								</div>

								<div class="row vendor-form-row">
									<div class="col-md-12">
										<div class="vendor-section-title">Vendor Information</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Vendor Name <span style="color:red;">*</span> :</label>
											<input type="text" id="vendor_name" name="vendor_name" value="<?php echo htmlspecialchars($row['vendor_name']); ?>" class="form-control" required autocomplete="off" />
											<span class="name_dup-check"></span>
										</div>
										<div class="form-group">
											<label class="control-label">Vendor Code <span style="color:red;">*</span> :</label>
											<input type="text" name="vendor_code" id="vendor_code" value="<?php echo htmlspecialchars($row['vendor_code']); ?>" class="form-control" readonly required autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Contact Person <span style="color:red;">*</span> :</label>
											<input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($row['contact_person']); ?>" class="form-control" required autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Address 1 <span style="color:red;">*</span> :</label>
											<input type="text" name="address1" id="address1" class="form-control" value="<?php echo htmlspecialchars($row['address1']); ?>" required autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Address 2 :</label>
											<input type="text" name="address2" id="address2" value="<?php echo htmlspecialchars($row['address2']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">State <span style="color:red;">*</span> :</label>
											<select name="state" id="state" class="form-control" required>
												<option value="">Select State</option>
												<?php
												$state_query = "SELECT * FROM state WHERE status=0 ORDER BY state_name";
												$state_result = mysqli_query($conn, $state_query);
												while ($state_row = mysqli_fetch_array($state_result)) {
												?>
													<option value="<?php echo $state_row['state_id']; ?>" <?php if ($row['state'] == $state_row['state_id']) echo 'selected'; ?>><?php echo $state_row['state_name']; ?></option>
												<?php } ?>
											</select>
										</div>
										<div class="form-group">
											<label class="control-label">City <span style="color:red;">*</span> :</label>
											<select name="city" id="city" class="form-control" required>
												<option value="">Select City</option>
												<?php
												if (!empty($row['state'])) {
													$city_query = "SELECT * FROM city WHERE status=0 AND state='" . (int) $row['state'] . "' ORDER BY city_name";
													$city_result = mysqli_query($conn, $city_query);
													while ($city_row = mysqli_fetch_array($city_result)) {
												?>
														<option value="<?php echo $city_row['city_id']; ?>" <?php if ($row['city'] == $city_row['city_id']) echo 'selected'; ?>><?php echo $city_row['city_name']; ?></option>
												<?php }
												} ?>
											</select>
										</div>
										<div class="form-group">
											<label class="control-label">Pincode :</label>
											<input type="text" name="pincode" id="pincode" minlength="6" maxlength="6" value="<?php echo htmlspecialchars($row['pincode']); ?>" class="form-control" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" autocomplete="off" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Vendor Type <span style="color:red;">*</span> :</label>
											<select name="vendor_type" id="vendor_type" class="form-control" required>
												<option value="">Select Vendor Type</option>
												<?php foreach ($vendor_types as $code => $label) { ?>
													<option value="<?php echo $code; ?>" <?php if ($row['vendor_type'] == $code) echo 'selected'; ?>><?php echo $label; ?></option>
												<?php } ?>
											</select>
										</div>
										<div class="form-group">
											<label class="control-label">Email :</label>
											<input type="email" name="email" id="email" value="<?php echo htmlspecialchars($row['email']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Alternate Email :</label>
											<input type="email" name="email_alt" id="email_alt" value="<?php echo htmlspecialchars($row['email_alt']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Contact No <span style="color:red;">*</span> :</label>
											<input type="text" name="contact_no" pattern="\d{10}" minlength="10" maxlength="10" id="contact_no" value="<?php echo htmlspecialchars($row['contact_no']); ?>" class="form-control" required autocomplete="off" onpaste="return false;" />
										</div>
										<div class="form-group">
											<label class="control-label">Contact No 2 :</label>
											<input type="text" name="contact_no2" id="contact_no2" pattern="\d{10}" minlength="10" maxlength="10" value="<?php echo htmlspecialchars($row['contact_no2']); ?>" class="form-control" autocomplete="off" onpaste="return false;" />
										</div>
										<div class="form-group">
											<label class="control-label">GSTIN :</label>
											<input type="text" style="text-transform:uppercase" name="gstin" id="gstin" maxlength="15" placeholder="e.g. 29AABCU9603R1ZM" class="form-control" value="<?php echo htmlspecialchars($row['gstin']); ?>" autocomplete="off" />
											<span class="gst_dup-check"></span>
										</div>
										<div class="form-group">
											<label class="control-label">PAN No <span style="color:red;">*</span> :</label>
											<input type="text" style="text-transform:uppercase" name="pan_no" id="pan_no" maxlength="10" class="form-control" value="<?php echo htmlspecialchars($row['pan_no']); ?>" required autocomplete="off" />
											<span class="pan_dup-check"></span>
										</div>
										<div class="form-group">
											<label class="control-label">Status :</label>
											<select name="status" id="status" class="form-control">
												<option value="0" <?php if ((int) $row['status'] === 0) echo 'selected'; ?>>Active</option>
												<option value="1" <?php if ((int) $row['status'] === 1) echo 'selected'; ?>>Inactive</option>
											</select>
										</div>
									</div>
								</div>

								<div class="row vendor-form-row">
									<div class="col-md-12">
										<div class="vendor-section-title">Transport / Service Information</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Mode of Transport <span style="color:red;">*</span> :</label>
											<select name="mode_of_transport" id="mode_of_transport" class="form-control" required>
												<option value="">Select Mode</option>
												<?php foreach ($vendor_modes as $code => $label) { ?>
													<option value="<?php echo $code; ?>" <?php if ($row['mode_of_transport'] == $code) echo 'selected'; ?>><?php echo $label; ?></option>
												<?php } ?>
											</select>
										</div>
										<div class="form-group">
											<label class="control-label">Service Type :</label>
											<select name="service_type" id="service_type" class="form-control">
												<option value="">Select Service Type</option>
												<?php foreach ($vendor_services as $code => $label) { ?>
													<option value="<?php echo $code; ?>" <?php if ($row['service_type'] == $code) echo 'selected'; ?>><?php echo $label; ?></option>
												<?php } ?>
											</select>
										</div>
										<div class="form-group">
											<label class="control-label">Operating From :</label>
											<input type="text" name="operating_from" id="operating_from" value="<?php echo htmlspecialchars($row['operating_from']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Operating To :</label>
											<input type="text" name="operating_to" id="operating_to" value="<?php echo htmlspecialchars($row['operating_to']); ?>" class="form-control" autocomplete="off" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Payment Terms :</label>
											<input type="text" name="payment_terms" id="payment_terms" value="<?php echo htmlspecialchars($row['payment_terms']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Credit Days :</label>
											<input type="number" min="0" name="credit_days" id="credit_days" value="<?php echo htmlspecialchars($row['credit_days']); ?>" class="form-control" autocomplete="off" />
										</div>
									</div>
								</div>

								<div class="row vendor-form-row">
									<div class="col-md-12">
										<div class="vendor-section-title">Bank &amp; Payment Details</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Account Holder Name :</label>
											<input type="text" name="account_holder_name" id="account_holder_name" value="<?php echo htmlspecialchars($row['account_holder_name']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Bank Name :</label>
											<input type="text" name="bank_name" id="bank_name" value="<?php echo htmlspecialchars($row['bank_name']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Account Number :</label>
											<input type="text" name="account_number" id="account_number" value="<?php echo htmlspecialchars($row['account_number']); ?>" class="form-control" autocomplete="off" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">IFSC :</label>
											<input type="text" style="text-transform:uppercase" name="ifsc" id="ifsc" maxlength="11" value="<?php echo htmlspecialchars($row['ifsc']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Branch :</label>
											<input type="text" name="bank_branch" id="bank_branch" value="<?php echo htmlspecialchars($row['bank_branch']); ?>" class="form-control" autocomplete="off" />
										</div>
									</div>
								</div>

								<br />
								<div class="row">
									<div class="col-md-12 form-action">
										<?php if (!$is_edit) { ?>
											<button class="btn btn-primary" type="button" id="save">Submit</button>
											<a class="btn btn-default-outline btn-reset" href="vendor.php" type="button">Cancel</a>
										<?php } else { ?>
											<button class="btn btn-primary" type="button" id="update">Update</button>
											<a class="btn btn-default-outline btn-reset" href="vendor.php" type="button">Cancel</a>
										<?php } ?>
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
		$(document).ready(function() {
			var dup_name = true;
			var dup_gst = true;
			var dup_pan = true;

			function isNumber(evt, element) {
				var charCode = (evt.which) ? evt.which : event.keyCode;
				if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&
					(charCode != 46 || $(element).val().indexOf('.') != -1) &&
					(charCode < 48 || charCode > 57)) {
					return false;
				}
				return true;
			}

			$('#pincode,#contact_no,#contact_no2').keypress(function(event) {
				return isNumber(event, this);
			});

			function normalizeGst(val) {
				return $.trim(val).toUpperCase().replace(/[^A-Z0-9]/g, '');
			}

			function check_vendor_duplicate(cmd, fieldVal, targetClass, flagName) {
				var edit_id = $('#edit_id').val();
				$.ajax({
					cache: false,
					url: 'check_existing.php',
					type: 'GET',
					dataType: 'json',
					async: false,
					data: {
						cmd: cmd,
						value: fieldVal,
						edit_id: edit_id
					},
					success: function(result) {
						if (result[0] == 1) {
							$(targetClass).html(result[1]).css('color', '#f00');
							if (flagName === 'name') dup_name = false;
							if (flagName === 'gst') dup_gst = false;
							if (flagName === 'pan') dup_pan = false;
						} else {
							if (result[1]) {
								$(targetClass).html(result[1]).css('color', 'green');
							} else {
								$(targetClass).html('');
							}
							if (flagName === 'name') dup_name = true;
							if (flagName === 'gst') dup_gst = true;
							if (flagName === 'pan') dup_pan = true;
						}
					}
				});
			}

			function validate_vendor_fields() {
				dup_name = true;
				dup_gst = true;
				dup_pan = true;

				var vendor_name = $.trim($('#vendor_name').val());
				if (vendor_name !== '') {
					check_vendor_duplicate('chk_vendor_name', vendor_name, '.name_dup-check', 'name');
				}

				var gstin = normalizeGst($('#gstin').val());
				$('#gstin').val(gstin);
				if (gstin !== '') {
					check_vendor_duplicate('chk_vendor_gstin', gstin, '.gst_dup-check', 'gst');
				} else {
					$('.gst_dup-check').html('');
				}

				var pan_no = $.trim($('#pan_no').val()).toUpperCase();
				$('#pan_no').val(pan_no);
				if (pan_no !== '') {
					check_vendor_duplicate('chk_vendor_pan', pan_no, '.pan_dup-check', 'pan');
				}

				return dup_name && dup_gst && dup_pan;
			}

			$(document).on('blur', '#vendor_name', function() {
				check_vendor_duplicate('chk_vendor_name', $.trim($(this).val()), '.name_dup-check', 'name');
			});
			$(document).on('blur', '#gstin', function() {
				var gstin = normalizeGst($(this).val());
				$(this).val(gstin);
				if (gstin === '') {
					$('.gst_dup-check').html('');
					dup_gst = true;
					return;
				}
				check_vendor_duplicate('chk_vendor_gstin', gstin, '.gst_dup-check', 'gst');
			});
			$(document).on('blur', '#pan_no', function() {
				var pan = $.trim($(this).val()).toUpperCase();
				$(this).val(pan);
				check_vendor_duplicate('chk_vendor_pan', pan, '.pan_dup-check', 'pan');
			});

			$(document).on('change', '#state', function() {
				var state_id = $(this).val();
				$.ajax({
					url: 'fetch_details.php',
					type: 'post',
					data: {
						cmd: 'get_city_name',
						state_id: state_id
					},
					success: function(result) {
						$('#city').html(result);
					}
				});
			});

			function submit_vendor(btn) {
				if (!validate_vendor_fields()) {
					return false;
				}
				if (!$('#vendor_form').valid()) {
					return false;
				}
				$(btn).attr('disabled', true);
				$.ajax({
					url: 'save_details.php',
					type: 'post',
					data: $('#vendor_form').serialize(),
					success: function(result) {
						if ($.trim(result) == '1') {
							$('.form-data-saving').hide();
							$('#alert-status').text('');
							$('#alert-message').text('Saved Successfully');
							$('#alert-container').addClass('alert-success').slideDown();
							setTimeout(function() {
								window.location.href = 'vendor_list.php';
							}, 1000);
						} else {
							$(btn).attr('disabled', false);
							$('#response .message').text(result || 'Save failed. Please try again.');
							$('#response').show();
						}
					},
					error: function(jqxhr) {
						$(btn).attr('disabled', false);
						console.log(jqxhr.responseText);
					}
				});
			}

			$(document).on('click', '#save', function() {
				submit_vendor(this);
			});
			$(document).on('click', '#update', function() {
				$('#form_name').val('add_vendor');
				submit_vendor(this);
			});
		});
	</script>
</body>

</html>
