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
	'contact_designation' => '',
	'address1' => '',
	'address2' => '',
	'state' => '',
	'city' => '',
	'pincode' => '',
	'vendor_type' => '',
	'email' => '',
	'email_alt' => '',
	'website' => '',
	'contact_no' => '',
	'contact_no2' => '',
	'gst_registered' => 0,
	'gstin' => '',
	'gst_exemption' => 0,
	'tds_applicable' => 0,
	'tds_rate' => '',
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
	if (empty($row['gst_registered']) && !empty($row['gstin'])) {
		$row['gst_registered'] = 1;
	}
	$vendor_id = (int) ($row['vendor_id'] ?? 0);
	$bank_accounts = ew_vendor_resolve_bank_accounts($conn, $vendor_id, $row);
} else {
	$next = ew_vendor_next_code($conn);
	$row['vendor_code'] = $next['vendor_code'];
	$bank_accounts = array();
}

if (empty($bank_accounts)) {
	$bank_accounts[] = array(
		'account_holder_name' => '',
		'bank_name' => '',
		'account_number' => '',
		'account_number_confirm' => '',
		'ifsc' => '',
		'bank_branch' => '',
		'account_type' => '',
		'account_role' => 'PRIMARY',
	);
}
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

		.vendor-type-wrap {
			display: flex;
			gap: 8px;
			align-items: stretch;
		}

		.vendor-type-wrap select {
			flex: 1;
		}


		.bank-accounts-wrap {
			display: flex;
			flex-direction: column;
			gap: 14px;
		}

		.bank-account-row {
			border: 1px solid #e4e4e4;
			border-radius: 8px;
			padding: 14px;
			background: #fafbfc;
		}

		.bank-account-row .row {
			margin-left: -10px;
			margin-right: -10px;
		}

		.bank-account-row .row>[class*="col-"] {
			padding-left: 10px;
			padding-right: 10px;
		}

		.bank-role-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-top: 8px;
			flex-wrap: wrap;
			gap: 10px;
		}

		.bank-role-options label {
			font-weight: 500;
			margin-right: 16px;
			cursor: pointer;
		}

		.bank-role-options input {
			margin-right: 4px;
		}

		.btn-remove-bank {
			color: #c0392b;
			background: none;
			border: none;
			font-size: 13px;
			padding: 0;
		}

		.btn-remove-bank:hover {
			text-decoration: underline;
		}

		.vendor-yesno-row {
			display: flex;
			gap: 16px;
			margin-top: 4px;
		}

		.vendor-yesno-row label {
			font-weight: 500;
			cursor: pointer;
		}

		.vendor-yesno-row input {
			margin-right: 4px;
		}

		.vendor-conditional-field {
			display: none;
		}

		.vendor-conditional-field.visible {
			display: block;
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
											<label class="control-label">Vendor Type <span style="color:red;">*</span> :</label>
											<div class="vendor-type-wrap">
												<select name="vendor_type" id="vendor_type" class="form-control" required>
													<?php echo ew_vendor_type_select_html($conn, $row['vendor_type']); ?>
												</select>
												<button type="button" class="btn btn-primary btn-add-inline" id="btn_add_vendor_type" title="Add new vendor type">+ Add</button>
											</div>
										</div>
										<div class="form-group">
											<label class="control-label">Address 1 <span style="color:red;">*</span> :</label>
											<input type="text" name="address1" id="address1" class="form-control" value="<?php echo htmlspecialchars($row['address1']); ?>" required autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Address 2 :</label>
											<input type="text" name="address2" id="address2" value="<?php echo htmlspecialchars($row['address2']); ?>" class="form-control" autocomplete="off" />
										</div>
									</div>
									<div class="col-md-6">
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
										<div class="vendor-section-title">Primary Contact Person</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Primary Contact Person <span style="color:red;">*</span> :</label>
											<input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($row['contact_person']); ?>" class="form-control" required autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Designation :</label>
											<input type="text" name="contact_designation" id="contact_designation" value="<?php echo htmlspecialchars($row['contact_designation'] ?? ''); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Mobile No <span style="color:red;">*</span> :</label>
											<input type="text" name="contact_no" pattern="\d{10}" minlength="10" maxlength="10" id="contact_no" value="<?php echo htmlspecialchars($row['contact_no']); ?>" class="form-control" required autocomplete="off" onpaste="return false;" />
										</div>
										<div class="form-group">
											<label class="control-label">Alternate Mobile :</label>
											<input type="text" name="contact_no2" id="contact_no2" pattern="\d{10}" minlength="10" maxlength="10" value="<?php echo htmlspecialchars($row['contact_no2']); ?>" class="form-control" autocomplete="off" onpaste="return false;" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Email <span style="color:red;">*</span> :</label>
											<input type="email" name="email" id="email" value="<?php echo htmlspecialchars($row['email']); ?>" class="form-control" required autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Alternate Email :</label>
											<input type="email" name="email_alt" id="email_alt" value="<?php echo htmlspecialchars($row['email_alt']); ?>" class="form-control" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">Website :</label>
											<input type="text" name="website" id="website" value="<?php echo htmlspecialchars($row['website'] ?? ''); ?>" class="form-control" placeholder="https://example.com" autocomplete="off" />
										</div>
									</div>
								</div>

								<div class="row vendor-form-row">
									<div class="col-md-12">
										<div class="vendor-section-title">Tax &amp; Registration</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">GST Registered <span style="color:red;">*</span> :</label>
											<div class="vendor-yesno-row">
												<label><input type="radio" name="gst_registered" value="1" class="gst-registered-toggle" <?php echo ((int) ($row['gst_registered'] ?? 0) === 1) ? 'checked' : ''; ?>> Yes</label>
												<label><input type="radio" name="gst_registered" value="0" class="gst-registered-toggle" <?php echo ((int) ($row['gst_registered'] ?? 0) !== 1) ? 'checked' : ''; ?>> No</label>
											</div>
										</div>
										<div class="form-group vendor-conditional-field gstin-wrap<?php echo ((int) ($row['gst_registered'] ?? 0) === 1) ? ' visible' : ''; ?>">
											<label class="control-label">GSTIN <span style="color:red;">*</span> :</label>
											<input type="text" style="text-transform:uppercase" name="gstin" id="gstin" maxlength="15" placeholder="e.g. 29AABCU9603R1ZM" class="form-control" value="<?php echo htmlspecialchars($row['gstin']); ?>" autocomplete="off" />
											<span class="gst_dup-check"></span>
										</div>
										<div class="form-group">
											<label class="control-label">GST Exemption :</label>
											<div class="vendor-yesno-row">
												<label><input type="radio" name="gst_exemption" value="1" <?php echo ((int) ($row['gst_exemption'] ?? 0) === 1) ? 'checked' : ''; ?>> Yes</label>
												<label><input type="radio" name="gst_exemption" value="0" <?php echo ((int) ($row['gst_exemption'] ?? 0) !== 1) ? 'checked' : ''; ?>> No</label>
											</div>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">TDS Applicable :</label>
											<div class="vendor-yesno-row">
												<label><input type="radio" name="tds_applicable" value="1" class="tds-applicable-toggle" <?php echo ((int) ($row['tds_applicable'] ?? 0) === 1) ? 'checked' : ''; ?>> Yes</label>
												<label><input type="radio" name="tds_applicable" value="0" class="tds-applicable-toggle" <?php echo ((int) ($row['tds_applicable'] ?? 0) !== 1) ? 'checked' : ''; ?>> No</label>
											</div>
										</div>
										<div class="form-group vendor-conditional-field tds-rate-wrap<?php echo ((int) ($row['tds_applicable'] ?? 0) === 1) ? ' visible' : ''; ?>">
											<label class="control-label">TDS Rate (%) <span style="color:red;">*</span> :</label>
											<input type="number" name="tds_rate" id="tds_rate" min="0" max="100" step="0.01" class="form-control" value="<?php echo htmlspecialchars($row['tds_rate'] ?? ''); ?>" autocomplete="off" />
										</div>
										<div class="form-group">
											<label class="control-label">PAN No <span style="color:red;">*</span> :</label>
											<input type="text" style="text-transform:uppercase" name="pan_no" id="pan_no" maxlength="10" class="form-control" value="<?php echo htmlspecialchars($row['pan_no']); ?>" required autocomplete="off" />
											<span class="pan_dup-check"></span>
										</div>
									</div>
								</div>

								<div class="row vendor-form-row">
									<div class="col-md-12">
										<div class="vendor-section-title">Payment Terms</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Payment Terms :</label>
											<input type="text" name="payment_terms" id="payment_terms" value="<?php echo htmlspecialchars($row['payment_terms']); ?>" class="form-control" autocomplete="off" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Credit Days :</label>
											<input type="number" min="0" name="credit_days" id="credit_days" value="<?php echo htmlspecialchars($row['credit_days']); ?>" class="form-control" autocomplete="off" />
										</div>
									</div>
								</div>

								<div class="row vendor-form-row">
									<div class="col-md-12">
										<div class="vendor-section-title">Bank &amp; Payment Details</div>
										<p class="text-muted" style="margin:-6px 0 12px;font-size:13px;">Add bank accounts and mark one as <strong>Primary</strong> (required). All bank fields are mandatory for each account added.</p>
									</div>
									<div class="col-md-12">
										<div class="bank-accounts-wrap" id="bank_accounts_wrap">
											<?php foreach ($bank_accounts as $idx => $acc) {
												$role = strtoupper($acc['account_role'] ?? 'OTHER');
											?>
											<div class="bank-account-row" data-index="<?php echo (int) $idx; ?>">
												<div class="row">
													<div class="col-md-6">
														<div class="form-group">
															<label class="control-label">Account Holder Name <span style="color:red;">*</span> :</label>
															<input type="text" name="bank_accounts[<?php echo (int) $idx; ?>][account_holder_name]" value="<?php echo htmlspecialchars($acc['account_holder_name'] ?? ''); ?>" class="form-control" required autocomplete="off" />
														</div>
													</div>
													<div class="col-md-6">
														<div class="form-group">
															<label class="control-label">Bank Name <span style="color:red;">*</span> :</label>
															<input type="text" name="bank_accounts[<?php echo (int) $idx; ?>][bank_name]" value="<?php echo htmlspecialchars($acc['bank_name'] ?? ''); ?>" class="form-control" required autocomplete="off" />
														</div>
													</div>
													<div class="col-md-6">
														<div class="form-group">
															<label class="control-label">Branch Name <span style="color:red;">*</span> :</label>
															<input type="text" name="bank_accounts[<?php echo (int) $idx; ?>][bank_branch]" value="<?php echo htmlspecialchars($acc['bank_branch'] ?? ''); ?>" class="form-control" required autocomplete="off" />
														</div>
													</div>
													<div class="col-md-6">
														<div class="form-group">
															<label class="control-label">Account Type <span style="color:red;">*</span> :</label>
															<select name="bank_accounts[<?php echo (int) $idx; ?>][account_type]" class="form-control" required>
																<?php echo ew_vendor_account_type_select_html($acc['account_type'] ?? ''); ?>
															</select>
														</div>
													</div>
													<div class="col-md-6">
														<div class="form-group">
															<label class="control-label">Account Number <span style="color:red;">*</span> :</label>
															<input type="text" name="bank_accounts[<?php echo (int) $idx; ?>][account_number]" value="<?php echo htmlspecialchars($acc['account_number'] ?? ''); ?>" class="form-control bank-account-number" required autocomplete="off" />
														</div>
													</div>
													<div class="col-md-6">
														<div class="form-group">
															<label class="control-label">Confirm Account Number <span style="color:red;">*</span> :</label>
															<input type="text" name="bank_accounts[<?php echo (int) $idx; ?>][account_number_confirm]" value="<?php echo htmlspecialchars($acc['account_number_confirm'] ?? ($acc['account_number'] ?? '')); ?>" class="form-control bank-account-confirm" required autocomplete="off" />
														</div>
													</div>
													<div class="col-md-6">
														<div class="form-group">
															<label class="control-label">IFSC Code <span style="color:red;">*</span> :</label>
															<input type="text" style="text-transform:uppercase" name="bank_accounts[<?php echo (int) $idx; ?>][ifsc]" maxlength="11" value="<?php echo htmlspecialchars($acc['ifsc'] ?? ''); ?>" class="form-control bank-ifsc" required autocomplete="off" />
														</div>
													</div>
												</div>
												<div class="bank-role-row">
													<div class="bank-role-options">
														<label><input type="checkbox" class="bank-primary-cb" name="bank_accounts[<?php echo (int) $idx; ?>][is_primary]" value="1" <?php echo ($role === 'PRIMARY') ? 'checked' : ''; ?>> Primary Bank Account</label>
														<label><input type="checkbox" class="bank-secondary-cb" name="bank_accounts[<?php echo (int) $idx; ?>][is_secondary]" value="1" <?php echo ($role === 'SECONDARY') ? 'checked' : ''; ?>> Secondary</label>
													</div>
													<button type="button" class="btn-remove-bank" <?php echo ($idx === 0 && count($bank_accounts) === 1) ? 'style="display:none;"' : ''; ?>>Remove account</button>
												</div>
											</div>
											<?php } ?>
										</div>
										<button type="button" class="btn btn-default-outline" id="btn_add_bank_account" style="margin-top:10px;"><i class="fa fa-plus"></i> Add Bank Account</button>
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

	<div class="modal fade" id="modal_add_vendor_type" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Add Vendor Type</h4>
				</div>
				<div class="modal-body">
					<form id="quick_vendor_type_form">
						<div class="form-group">
							<label>Type Name <span style="color:red;">*</span></label>
							<input type="text" class="form-control" id="quick_vendor_type_name" maxlength="100" required autocomplete="off" placeholder="e.g. Custom Broker" />
						</div>
					</form>
					<div id="vendor_type_msg" class="text-danger" style="display:none;"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default-outline" data-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="quick_vendor_type_save">Save &amp; Select</button>
				</div>
			</div>
		</div>
	</div>

	<script type="text/template" id="bank-row-template">
	<div class="bank-account-row" data-index="__INDEX__">
		<div class="row">
			<div class="col-md-6"><div class="form-group"><label class="control-label">Account Holder Name <span style="color:red;">*</span> :</label><input type="text" name="bank_accounts[__INDEX__][account_holder_name]" class="form-control" required autocomplete="off" /></div></div>
			<div class="col-md-6"><div class="form-group"><label class="control-label">Bank Name <span style="color:red;">*</span> :</label><input type="text" name="bank_accounts[__INDEX__][bank_name]" class="form-control" required autocomplete="off" /></div></div>
			<div class="col-md-6"><div class="form-group"><label class="control-label">Branch Name <span style="color:red;">*</span> :</label><input type="text" name="bank_accounts[__INDEX__][bank_branch]" class="form-control" required autocomplete="off" /></div></div>
			<div class="col-md-6"><div class="form-group"><label class="control-label">Account Type <span style="color:red;">*</span> :</label><select name="bank_accounts[__INDEX__][account_type]" class="form-control" required><?php echo ew_vendor_account_type_select_html(''); ?></select></div></div>
			<div class="col-md-6"><div class="form-group"><label class="control-label">Account Number <span style="color:red;">*</span> :</label><input type="text" name="bank_accounts[__INDEX__][account_number]" class="form-control bank-account-number" required autocomplete="off" /></div></div>
			<div class="col-md-6"><div class="form-group"><label class="control-label">Confirm Account Number <span style="color:red;">*</span> :</label><input type="text" name="bank_accounts[__INDEX__][account_number_confirm]" class="form-control bank-account-confirm" required autocomplete="off" /></div></div>
			<div class="col-md-6"><div class="form-group"><label class="control-label">IFSC Code <span style="color:red;">*</span> :</label><input type="text" style="text-transform:uppercase" name="bank_accounts[__INDEX__][ifsc]" maxlength="11" class="form-control bank-ifsc" required autocomplete="off" /></div></div>
		</div>
		<div class="bank-role-row">
			<div class="bank-role-options">
				<label><input type="checkbox" class="bank-primary-cb" name="bank_accounts[__INDEX__][is_primary]" value="1"> Primary Bank Account</label>
				<label><input type="checkbox" class="bank-secondary-cb" name="bank_accounts[__INDEX__][is_secondary]" value="1"> Secondary</label>
			</div>
			<button type="button" class="btn-remove-bank">Remove account</button>
		</div>
	</div>
	</script>

	<script type="text/javascript">
		$(document).ready(function() {
			var dup_name = true;
			var dup_gst = true;
			var dup_pan = true;
			var bankRowIndex = $('#bank_accounts_wrap .bank-account-row').length;

			function toggleVendorTaxFields() {
				var gstYes = $('input[name="gst_registered"]:checked').val() === '1';
				var tdsYes = $('input[name="tds_applicable"]:checked').val() === '1';
				$('.gstin-wrap').toggleClass('visible', gstYes);
				if (!gstYes) {
					$('#gstin').val('');
					$('.gst_dup-check').html('');
					dup_gst = true;
				}
				$('.tds-rate-wrap').toggleClass('visible', tdsYes);
				if (!tdsYes) {
					$('#tds_rate').val('');
				}
			}

			function validateBankAccountsClient() {
				var hasPrimary = false;
				var rows = $('#bank_accounts_wrap .bank-account-row');
				if (!rows.length) {
					alert('Add at least one bank account.');
					return false;
				}
				var ok = true;
				rows.each(function(i) {
					var $row = $(this);
					var num = $.trim($row.find('.bank-account-number').val());
					var conf = $.trim($row.find('.bank-account-confirm').val());
					if (num !== conf) {
						alert('Account number and confirm account number must match in bank row ' + (i + 1) + '.');
						ok = false;
						return false;
					}
					if ($row.find('.bank-primary-cb').is(':checked')) {
						hasPrimary = true;
					}
				});
				if (!ok) {
					return false;
				}
				if (!hasPrimary) {
					alert('Mark one bank account as Primary.');
					return false;
				}
				return true;
			}

			$(document).on('change', '.gst-registered-toggle, .tds-applicable-toggle', toggleVendorTaxFields);
			toggleVendorTaxFields();

			function refreshBankRemoveButtons() {
				var rows = $('#bank_accounts_wrap .bank-account-row');
				if (rows.length <= 1) {
					rows.find('.btn-remove-bank').hide();
				} else {
					rows.find('.btn-remove-bank').show();
				}
			}

			function reindexBankRows() {
				$('#bank_accounts_wrap .bank-account-row').each(function(i) {
					$(this).attr('data-index', i);
					$(this).find('[name^="bank_accounts["]').each(function() {
						var name = $(this).attr('name');
						if (!name) return;
						$(this).attr('name', name.replace(/bank_accounts\[\d+\]/, 'bank_accounts[' + i + ']'));
					});
				});
				bankRowIndex = $('#bank_accounts_wrap .bank-account-row').length;
				refreshBankRemoveButtons();
			}

			function reloadVendorTypes(selected) {
				$.get('fetch_details.php', {
					cmd: 'get_vendor_type_options',
					selected: selected || $('#vendor_type').val()
				}, function(html) {
					$('#vendor_type').html(html);
				});
			}

			$('#btn_add_vendor_type').on('click', function() {
				$('#quick_vendor_type_name').val('');
				$('#vendor_type_msg').hide().text('');
				$('#modal_add_vendor_type').modal('show');
			});

			$('#quick_vendor_type_save').on('click', function() {
				var name = $.trim($('#quick_vendor_type_name').val());
				if (!name) {
					$('#vendor_type_msg').text('Enter vendor type name.').show();
					return;
				}
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.post('save_details.php', {
					form_name: 'add_vendor_type',
					type_name: name
				}, function(res) {
					$btn.prop('disabled', false);
					var data = res;
					if (typeof res === 'string') {
						try { data = JSON.parse(res); } catch (e) { data = {}; }
					}
					if (data.ok) {
						reloadVendorTypes(data.type_code);
						$('#modal_add_vendor_type').modal('hide');
					} else {
						$('#vendor_type_msg').text(data.message || 'Could not add vendor type.').show();
					}
				}, 'json').fail(function() {
					$btn.prop('disabled', false);
					$('#vendor_type_msg').text('Network error. Please try again.').show();
				});
			});

			$('#btn_add_bank_account').on('click', function() {
				var tpl = $('#bank-row-template').html();
				var html = tpl.replace(/__INDEX__/g, String(bankRowIndex));
				$('#bank_accounts_wrap').append(html);
				bankRowIndex++;
				refreshBankRemoveButtons();
			});

			$(document).on('click', '.btn-remove-bank', function() {
				$(this).closest('.bank-account-row').remove();
				reindexBankRows();
			});

			$(document).on('change', '.bank-primary-cb', function() {
				if ($(this).is(':checked')) {
					var $row = $(this).closest('.bank-account-row');
					$('.bank-primary-cb').not(this).prop('checked', false);
					$row.find('.bank-secondary-cb').prop('checked', false);
				}
			});

			$(document).on('change', '.bank-secondary-cb', function() {
				if ($(this).is(':checked')) {
					var $row = $(this).closest('.bank-account-row');
					$('.bank-secondary-cb').not(this).prop('checked', false);
					$row.find('.bank-primary-cb').prop('checked', false);
				}
			});

			refreshBankRemoveButtons();

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

				var gstRegistered = $('input[name="gst_registered"]:checked').val() === '1';
				var gstin = normalizeGst($('#gstin').val());
				$('#gstin').val(gstin);
				if (gstRegistered) {
					if (gstin === '') {
						dup_gst = false;
					} else {
						check_vendor_duplicate('chk_vendor_gstin', gstin, '.gst_dup-check', 'gst');
					}
				} else {
					$('.gst_dup-check').html('');
					dup_gst = true;
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
				if ($('input[name="gst_registered"]:checked').val() !== '1') {
					return;
				}
				var gstin = normalizeGst($(this).val());
				$(this).val(gstin);
				if (gstin === '') {
					$('.gst_dup-check').html('');
					dup_gst = false;
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
				toggleVendorTaxFields();
				if ($('input[name="gst_registered"]:checked').val() === '1' && normalizeGst($('#gstin').val()) === '') {
					alert('GSTIN is required when vendor is GST registered.');
					return false;
				}
				if ($('input[name="tds_applicable"]:checked').val() === '1' && $.trim($('#tds_rate').val()) === '') {
					alert('TDS rate is required when TDS is applicable.');
					return false;
				}
				if (!validateBankAccountsClient()) {
					return false;
				}
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
