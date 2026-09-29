<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/quotation_functions.php');
require_once('include/vehicle_type_helpers.php');

ensure_rate_quotation_tables($conn);
ew_vehicle_type_ensure_schema($conn);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_create = !empty($_GET['create']);
if ($id <= 0 && !$is_create) {
	header('Location: quotation_list.php');
	exit;
}

$master = null;
$lines = quotation_default_lines();
$quote_no = quotation_preview_number($conn);
$quote_date = date('d-m-Y');
$valid_till = date('d-m-Y', strtotime('+7 days'));

if ($id > 0) {
	$master = quotation_get($conn, $id);
	if (!$master) {
		header('Location: quotation_list.php');
		exit;
	}
	$db_lines = quotation_get_lines($conn, $id);
	if ($db_lines) {
		$lines = array();
		foreach ($db_lines as $ln) {
			$lines[] = array(
				'charge_label' => $ln['charge_label'],
				'amount' => rtrim(rtrim(number_format((float) $ln['amount'], 2, '.', ''), '0'), '.'),
				'is_taxable' => (int) $ln['is_taxable'],
				'remarks' => $ln['remarks'] ?? '',
			);
		}
	}
	$quote_no = $master['quote_no'];
	$quote_date = $master['quote_date'];
	$valid_till = $master['valid_till'] ?: $valid_till;
} else {
	$master = array(
		'quotation_id' => 0,
		'status' => 'draft',
		'quote_type' => 'door_to_door',
		'subject' => '',
		'party_id' => 0,
		'party_name' => '',
		'customer_mode' => 'new',
		'origin_city_id' => 0,
		'destination_city_id' => 0,
		'attn_name' => '',
		'party_email' => '',
		'party_mobile' => '',
		'origin_text' => '',
		'loading_type' => '2_point',
		'mode_of_transportation' => 0,
		'destination_name' => '',
		'unloading_at' => '',
		'delivery_address' => '',
		'vehicle_type_id' => 0,
		'gst_rate' => 18,
		'terms_notes' => 'We look forward to your confirmation and the opportunity to serve you.',
		'cfs_port_factory' => '',
		'part_number' => '',
		'quotation_approval' => '',
		'freight_paid_by' => '',
		'payment_terms' => '30_days',
		'taxable_value' => 0,
		'gst_amount' => 0,
		'total_amount' => 0,
	);
}

$status = $master['status'] ?? 'draft';
$editable = quotation_is_editable($status);
$can_pdf = in_array($status, array('approved', 'sent', 'customer_confirmed', 'converted'), true);

$clients_q = mysqli_query($conn, "SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC");
$cities_q = mysqli_query($conn, "SELECT city_id, city_name FROM city WHERE status=0 ORDER BY city_name ASC");
$modes_q = mysqli_query($conn, "SELECT mode_id, mode_type FROM mode_of_transportation WHERE status=0 ORDER BY mode_type ASC");

$customer_mode = trim((string) ($master['customer_mode'] ?? ''));
if ($customer_mode !== 'existing' && $customer_mode !== 'new') {
	$customer_mode = ((int) ($master['party_id'] ?? 0) > 0 && trim((string) ($master['party_name'] ?? '')) === '') ? 'existing' : 'new';
}
if ($customer_mode === 'new' && (int) ($master['party_id'] ?? 0) > 0 && trim((string) ($master['party_name'] ?? '')) === '') {
	$customer_mode = 'existing';
}
if ($customer_mode === 'existing' && trim((string) ($master['party_name'] ?? '')) === '' && (int) ($master['party_id'] ?? 0) > 0) {
	$master['party_name'] = quotation_party_name($conn, (int) $master['party_id']);
}
$vehicle_types = ew_vehicle_type_list($conn, true);
$vehicle_json = array();
foreach ($vehicle_types as $vt) {
	$snap = quotation_vehicle_snapshot($conn, (int) $vt['vehicle_type_id']);
	$vehicle_json[(int) $vt['vehicle_type_id']] = array(
		'label' => $vt['type_name'],
		'dim_display' => $snap['dim_display'],
	);
}

$dim_display = quotation_dim_display($master);
if ($dim_display === '' && !empty($master['vehicle_type_id'])) {
	$dim_display = quotation_vehicle_snapshot($conn, (int) $master['vehicle_type_id'])['dim_display'];
}

function ew_quotation_status_pill_class($status)
{
	$map = array(
		'pending_approval' => 'pending_approval',
		'approved' => 'approved',
		'customer_confirmed' => 'customer_confirmed',
		'rejected' => 'rejected',
	);
	return $map[$status] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2--invoice .ew-form-grid--quotation { grid-template-columns: repeat(4, minmax(0, 1fr)); }
		.ew-page-v2--invoice .ew-form-grid--quotation .span-2 { grid-column: span 2; }
		.ew-page-v2--invoice .ew-form-grid--quotation .span-3 { grid-column: span 3; }
		.ew-page-v2--invoice .ew-form-grid--quotation .span-4 { grid-column: span 4; }
		.ew-status-pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #e2e8f0; color: #334155; margin-right: 8px; }
		.ew-status-pill.pending_approval { background: #fef3c7; color: #92400e; }
		.ew-status-pill.approved, .ew-status-pill.customer_confirmed { background: #dcfce7; color: #166534; }
		.ew-status-pill.rejected { background: #fee2e2; color: #991b1b; }
		.ew-quotation-layout { display: grid; grid-template-columns: 1fr 380px; gap: 16px; align-items: start; }
		@media (max-width: 1200px) { .ew-quotation-layout { grid-template-columns: 1fr; } }
		.ew-letter-preview { background: #fff; border: 1px solid #d8e0ea; border-radius: 8px; padding: 20px 22px; font-size: 13px; line-height: 1.55; color: #1e293b; max-height: calc(100vh - 120px); overflow-y: auto; position: sticky; top: 12px; }
		.ew-letter-preview h4 { text-align: center; font-size: 14px; font-weight: 700; text-decoration: underline; margin: 0 0 16px; }
		.ew-letter-preview table.charges { width: 100%; margin: 8px 0; border-collapse: collapse; }
		.ew-letter-preview table.charges td { padding: 3px 0; border-bottom: 1px solid #e2e8f0; }
		.ew-letter-preview table.charges td:last-child { text-align: right; white-space: nowrap; font-weight: 600; }
		.ew-letter-preview .pv-section { margin: 12px 0 6px; font-size: 12px; font-weight: 700; color: #021659; border-bottom: 2px solid #021659; padding-bottom: 3px; }
		.ew-letter-preview .pv-kv { margin: 4px 0; font-size: 12px; }
		.ew-letter-preview .pv-kv b { color: #021659; }
		.ew-field-pair { display: flex; gap: 8px; }
		.ew-field-pair .form-control:first-child { flex: 1; min-width: 0; }
		.ew-field-pair .uom-select { flex: 0 0 100px; max-width: 120px; }
		.ew-dim-readonly { background: #f1f5f9; font-size: 12px; color: #475569; padding: 8px 10px; border-radius: 6px; border: 1px solid #e2e8f0; min-height: 34px; }
		#charges_table input.form-control, #charges_table select.form-control { height: 34px; }
		.ew-form-footer--quotation { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
		.quotation-readonly .form-control:not([readonly]) { pointer-events: none; background: #f8fafc; }
	</style>
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
	<div class="navbar navbar-fixed-top scroll-hide">
		<?php require_once('include/header.php'); require_once('include/menu.php'); ?>
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="row">
			<div class="col-md-12">
				<div class="ew-page-v2 ew-page-v2--invoice">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<a href="quotation_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php echo $id > 0 ? 'Rate Quotation' : 'Create Rate Quotation'; ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<span class="ew-status-pill <?php echo ew_quotation_status_pill_class($status); ?>"><?php echo htmlspecialchars(quotation_status_label($status)); ?></span>
							<a href="quotation_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							<?php if ($can_pdf) { ?>
								<a href="quotation_pdf.php?id=<?php echo (int) $id; ?>" target="_blank" class="ew-btn-v2 ew-btn-v2-outline"><i class="fa fa-file"></i> PDF</a>
							<?php } ?>
						</div>
					</div>

					<form id="quotation_form">
						<input type="hidden" name="form_name" value="save_rate_quotation" />
						<input type="hidden" name="quotation_id" id="quotation_id" value="<?php echo (int) $id; ?>" />
						<input type="hidden" name="quotation_action" id="quotation_action" value="save_draft" />

						<div id="response" class="alert alert-danger" style="display:none;margin:0 16px 12px;"><div class="message" style="text-align:center"></div></div>

						<div class="ew-quotation-layout">
							<div class="<?php echo $editable ? '' : 'quotation-readonly'; ?>">

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Quotation details</h2>
									<div class="ew-form-body">
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
											<div class="ew-field">
												<label>Quote no</label>
												<div class="ew-invoice-no-display" id="quote_no_display"><?php echo htmlspecialchars($quote_no); ?></div>
											</div>
											<div class="ew-field">
												<label>Quote date <span class="req">*</span></label>
												<?php echo ew_date_input(array('name' => 'quote_date', 'id' => 'quote_date', 'value' => $quote_date, 'required' => true, 'readonly' => !$editable)); ?>
											</div>
											<div class="ew-field">
												<label>Valid till</label>
												<?php echo ew_date_input(array('name' => 'valid_till', 'id' => 'valid_till', 'value' => $valid_till, 'readonly' => !$editable)); ?>
											</div>
											<div class="ew-field">
												<label>Quote type</label>
												<select name="quote_type" id="quote_type" class="form-control">
													<?php foreach (quotation_quote_type_options() as $k => $lbl) {
														$sel = (($master['quote_type'] ?? '') === $k) ? ' selected' : '';
														echo '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($lbl) . '</option>';
													} ?>
												</select>
											</div>
											<div class="ew-field span-4">
												<label>Subject</label>
												<input type="text" name="subject" id="subject" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['subject'] ?? ''); ?>" />
											</div>
										</div>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Customer &amp; attention</h2>
									<div class="ew-form-body">
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
											<div class="ew-field span-4">
												<label>Customer type</label>
												<div class="ew-yesno-row" style="margin-top:4px;">
													<label><input type="radio" name="customer_mode" value="new" <?php echo $customer_mode === 'new' ? 'checked' : ''; ?> <?php echo $editable ? '' : 'disabled'; ?>> New client (not in system)</label>
													<label><input type="radio" name="customer_mode" value="existing" <?php echo $customer_mode === 'existing' ? 'checked' : ''; ?> <?php echo $editable ? '' : 'disabled'; ?>> Existing client</label>
												</div>
											</div>
											<div class="ew-field span-2 ew-customer-new" style="<?php echo $customer_mode === 'existing' ? 'display:none;' : ''; ?>">
												<label>To (Customer / Company) <span class="req">*</span></label>
												<input type="text" name="party_name" id="party_name" class="form-control pv-bind" maxlength="255" value="<?php echo htmlspecialchars($master['party_name'] ?? ''); ?>" placeholder="e.g. Astormueller Shoes, Bangalore" />
											</div>
											<div class="ew-field span-2 ew-customer-existing" style="<?php echo $customer_mode === 'new' ? 'display:none;' : ''; ?>">
												<label>To (Customer) <span class="req">*</span></label>
												<select name="party_id" id="party_id" class="form-control" <?php echo $editable ? '' : 'disabled'; ?>>
													<option value="">Select customer</option>
													<?php
													mysqli_data_seek($clients_q, 0);
													while ($c = mysqli_fetch_assoc($clients_q)) {
														$name = function_exists('ew_client_decrypt_name') ? ew_client_decrypt_name($c['client_company_name']) : $c['client_company_name'];
														$sel = ((int) $master['party_id'] === (int) $c['client_id']) ? ' selected' : '';
														echo '<option value="' . (int) $c['client_id'] . '"' . $sel . '>' . htmlspecialchars($name) . '</option>';
													}
													?>
												</select>
												<p class="ew-field-hint">Contact details auto-fill from Client master; you can edit below.</p>
											</div>
											<div class="ew-field span-2">
												<label>Kind Attn. <span class="req">*</span></label>
												<input type="text" name="attn_name" id="attn_name" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['attn_name'] ?? ''); ?>" />
											</div>
											<div class="ew-field span-2">
												<label>Email</label>
												<input type="email" name="party_email" id="party_email" class="form-control" value="<?php echo htmlspecialchars($master['party_email'] ?? ''); ?>" />
											</div>
											<div class="ew-field span-2">
												<label>Mobile</label>
												<input type="text" name="party_mobile" id="party_mobile" class="form-control" value="<?php echo htmlspecialchars($master['party_mobile'] ?? ''); ?>" />
											</div>
										</div>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Route &amp; delivery</h2>
									<div class="ew-form-body">
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
											<div class="ew-field">
												<label>Origin city <span class="req">*</span></label>
												<select name="origin_city_id" id="origin_city_id" class="form-control pv-bind-city">
													<option value="">Select city</option>
													<?php
													while ($city = mysqli_fetch_assoc($cities_q)) {
														$sel = ((int) ($master['origin_city_id'] ?? 0) === (int) $city['city_id']) ? ' selected' : '';
														echo '<option value="' . (int) $city['city_id'] . '"' . $sel . '>' . htmlspecialchars($city['city_name']) . '</option>';
													}
													?>
												</select>
											</div>
											<div class="ew-field">
												<label>Loading</label>
												<select name="loading_type" id="loading_type" class="form-control pv-bind-select">
													<?php foreach (quotation_loading_type_options() as $k => $lbl) {
														$sel = (($master['loading_type'] ?? '') === $k) ? ' selected' : '';
														echo '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($lbl) . '</option>';
													} ?>
												</select>
											</div>
											<div class="ew-field">
												<label>Mode of transport <span class="req">*</span></label>
												<select name="mode_of_transportation" id="mode_of_transportation" class="form-control pv-bind-select"<?php echo $editable ? ' required' : ''; ?>>
													<option value="">Select mode</option>
													<?php
													if ($modes_q) {
														while ($mode_row = mysqli_fetch_assoc($modes_q)) {
															$sel = ((int) ($master['mode_of_transportation'] ?? 0) === (int) $mode_row['mode_id']) ? ' selected' : '';
															echo '<option value="' . (int) $mode_row['mode_id'] . '"' . $sel . '>' . htmlspecialchars($mode_row['mode_type']) . '</option>';
														}
													}
													?>
												</select>
											</div>
											<div class="ew-field">
												<label>Destination city <span class="req">*</span></label>
												<select name="destination_city_id" id="destination_city_id" class="form-control pv-bind-city">
													<option value="">Select city</option>
													<?php
													mysqli_data_seek($cities_q, 0);
													while ($city = mysqli_fetch_assoc($cities_q)) {
														$sel = ((int) ($master['destination_city_id'] ?? 0) === (int) $city['city_id']) ? ' selected' : '';
														echo '<option value="' . (int) $city['city_id'] . '"' . $sel . '>' . htmlspecialchars($city['city_name']) . '</option>';
													}
													?>
												</select>
											</div>
											<div class="ew-field span-2">
												<label>Consignee / delivery party <span class="req">*</span></label>
												<input type="text" name="destination_name" id="destination_name" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['destination_name'] ?? ''); ?>" placeholder="e.g. Nuvora Retail Private Limited, New Delhi" />
											</div>
											<div class="ew-field">
												<label>Unloading at</label>
												<input type="text" name="unloading_at" id="unloading_at" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['unloading_at'] ?? ''); ?>" placeholder="Defaults to destination city" />
											</div>
											<div class="ew-field span-3">
												<label>Delivery address</label>
												<textarea name="delivery_address" id="delivery_address" class="form-control pv-bind" rows="3"><?php echo htmlspecialchars($master['delivery_address'] ?? ''); ?></textarea>
											</div>
										</div>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Vehicle specification</h2>
									<div class="ew-form-body">
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
											<div class="ew-field span-2">
												<label>Vehicle type</label>
												<select name="vehicle_type_id" id="vehicle_type_id" class="form-control">
													<option value="">Select vehicle type</option>
													<?php foreach ($vehicle_types as $vt) {
														$sel = ((int) ($master['vehicle_type_id'] ?? 0) === (int) $vt['vehicle_type_id']) ? ' selected' : '';
														echo '<option value="' . (int) $vt['vehicle_type_id'] . '"' . $sel . '>' . htmlspecialchars($vt['type_name']) . '</option>';
													} ?>
												</select>
											</div>
											<div class="ew-field span-2">
												<label>Dimensions</label>
												<div class="ew-dim-readonly" id="vehicle_dims"><?php echo htmlspecialchars($dim_display ?: '—'); ?></div>
											</div>
										</div>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Charges &amp; tax</h2>
									<div class="ew-form-body">
										<div class="ew-table-wrap">
											<table class="table table-bordered" id="charges_table">
												<thead>
													<tr>
														<th>Charge head</th>
														<th style="width:22%">Amount (₹)</th>
														<th style="width:18%">Taxable</th>
														<th>Remarks <span class="text-muted" style="font-weight:normal;font-size:11px;">(policy no. for Insurance line)</span></th>
													</tr>
												</thead>
												<tbody id="charges_tbody">
													<?php foreach ($lines as $ln) { ?>
														<tr>
															<td><input type="text" name="charge_label[]" class="form-control charge-label" value="<?php echo htmlspecialchars($ln['charge_label']); ?>" /></td>
															<td><input type="text" name="charge_amount[]" class="form-control charge-amt" value="<?php echo htmlspecialchars($ln['amount']); ?>" onpaste="return ewNumericPaste(event,this);" /></td>
															<td>
																<select name="charge_taxable[]" class="form-control charge-tax">
																	<option value="1" <?php echo !empty($ln['is_taxable']) ? 'selected' : ''; ?>>Yes</option>
																	<option value="0" <?php echo empty($ln['is_taxable']) ? 'selected' : ''; ?>>No</option>
																</select>
															</td>
															<td><input type="text" name="charge_remarks[]" class="form-control charge-remarks" value="<?php echo htmlspecialchars($ln['remarks']); ?>" placeholder="<?php echo quotation_is_insurance_charge_label($ln['charge_label'] ?? '') ? 'Insurance / policy number' : ''; ?>" /></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>
										<?php if ($editable) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_add_charge_line" style="margin-bottom:12px;"><i class="fa fa-plus"></i> Add line</button>
										<?php } ?>
										<h3 class="ew-card-section-subtitle" style="margin:8px 0 10px;font-size:14px;font-weight:600;">Commercial summary fields</h3>
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
											<div class="ew-field span-2">
												<label>CFS / Port / Factory / Warehouse</label>
												<input type="text" name="cfs_port_factory" id="cfs_port_factory" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['cfs_port_factory'] ?? ''); ?>" />
											</div>
											<div class="ew-field span-2">
												<label>Part Number / Article Name / Article Number</label>
												<input type="text" name="part_number" id="part_number" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['part_number'] ?? ''); ?>" />
											</div>
											<div class="ew-field span-2">
												<label>Quotation approval</label>
												<select name="quotation_approval" id="quotation_approval" class="form-control pv-bind-select">
													<option value="">Select option</option>
													<?php
													$qa = trim((string) ($master['quotation_approval'] ?? ''));
													$qa_opts = quotation_quotation_approval_options();
													if ($qa !== '' && !isset($qa_opts[$qa])) {
														$qa_opts = array($qa => $qa) + $qa_opts;
													}
													foreach ($qa_opts as $k => $lbl) {
														$sel = ($qa === (string) $k) ? ' selected' : '';
														echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($lbl) . '</option>';
													}
													?>
												</select>
											</div>
											<div class="ew-field">
												<label>Freight paid by</label>
												<select name="freight_paid_by" id="freight_paid_by" class="form-control pv-bind-select">
													<option value="">Select consignment mode</option>
													<?php
													$fpb = trim((string) ($master['freight_paid_by'] ?? ''));
													$fpb_opts = quotation_consignment_mode_options($conn);
													if ($fpb !== '' && !isset($fpb_opts[$fpb])) {
														$legacy_lbl = quotation_freight_paid_by_label($conn, $fpb);
														$fpb_opts = array($fpb => $legacy_lbl) + $fpb_opts;
													}
													foreach ($fpb_opts as $k => $lbl) {
														if ($lbl === '') {
															continue;
														}
														$sel = ($fpb === (string) $k) ? ' selected' : '';
														echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($lbl) . '</option>';
													}
													?>
												</select>
											</div>
											<div class="ew-field">
												<label>Payment terms</label>
												<select name="payment_terms" id="payment_terms" class="form-control pv-bind-select">
													<?php
													$pt = trim((string) ($master['payment_terms'] ?? '30_days'));
													$pt_opts = quotation_payment_terms_options();
													if ($pt !== '' && !isset($pt_opts[$pt]) && $pt !== 'immediate' && $pt !== 'other') {
														$pt_opts = array($pt => quotation_payment_terms_label($pt)) + $pt_opts;
													}
													if ($pt === 'immediate' || $pt === 'other') {
														$pt_opts = array($pt => quotation_payment_terms_label($pt)) + $pt_opts;
													}
													foreach ($pt_opts as $k => $lbl) {
														$sel = ($pt === (string) $k) ? ' selected' : '';
														echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($lbl) . '</option>';
													}
													?>
												</select>
											</div>
										</div>
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation" style="margin-top:12px;">
											<div class="ew-field">
												<label>Taxable value (₹)</label>
												<input type="text" class="form-control" id="taxable_value" readonly value="<?php echo htmlspecialchars($master['taxable_value'] ?? '0'); ?>" />
											</div>
											<div class="ew-field">
												<label>GST %</label>
												<select name="gst_rate" id="gst_rate" class="form-control">
													<?php foreach (array(0, 5, 12, 18, 28) as $g) {
														$sel = ((float) ($master['gst_rate'] ?? 18) === (float) $g) ? ' selected' : '';
														echo '<option value="' . $g . '"' . $sel . '>' . $g . '</option>';
													} ?>
												</select>
											</div>
											<div class="ew-field">
												<label>GST amount (₹)</label>
												<input type="text" class="form-control" id="gst_amount" readonly />
											</div>
											<div class="ew-field">
												<label>Total (₹)</label>
												<input type="text" class="form-control" id="total_amount" readonly style="font-weight:700;" />
											</div>
											<div class="ew-field span-4">
												<label>Terms &amp; notes</label>
												<textarea name="terms_notes" id="terms_notes" class="form-control pv-bind" rows="2"><?php echo htmlspecialchars($master['terms_notes'] ?? ''); ?></textarea>
											</div>
										</div>
									</div>
									<div class="ew-form-footer ew-form-footer--quotation">
										<?php if ($editable) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-action" data-action="save_draft">Save draft</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-action" data-action="submit">Submit for approval</button>
										<?php } ?>
										<?php if ($status === 'pending_approval') { ?>
											<p class="ew-field-hint" style="width:100%;margin:0 0 6px;text-align:right;">Approving sends the quotation PDF to the customer email above (copy to info@elitewave360.in).</p>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-workflow" data-action="approve">Approve &amp; email PDF</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="reject">Reject</button>
										<?php } ?>
										<?php if ($status === 'approved') { ?>
											<p class="ew-field-hint" style="width:100%;margin:0 0 6px;text-align:right;">Customer email with PDF was sent when you clicked Approve (if email was valid).</p>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-workflow" data-action="mark_sent">Mark sent to customer</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="resend_email">Resend email</button>
										<?php } ?>
										<?php if (in_array($status, array('sent', 'customer_confirmed'), true)) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="resend_email">Resend quotation email</button>
										<?php } ?>
										<?php if ($status === 'sent') { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-workflow" data-action="customer_confirmed">Customer confirmed</button>
										<?php } ?>
										<?php if (!in_array($status, array('converted', 'cancelled'), true)) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="cancel">Cancel quotation</button>
										<?php } ?>
									</div>
								</div>
							</div>

							<aside class="ew-letter-preview" id="letter_preview" aria-live="polite"></aside>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>

<div class="ew-v2-modal-backdrop" id="rejectQuotationModal">
	<div class="ew-v2-modal">
		<div class="ew-v2-modal-head">
			<h3>Reject quotation</h3>
			<button type="button" class="ew-v2-modal-close" data-ew-v2-close>&times;</button>
		</div>
		<div class="ew-v2-modal-body">
			<label>Remarks <span style="color:red">*</span></label>
			<textarea id="reject_remarks" class="form-control" rows="3"></textarea>
		</div>
		<div class="ew-v2-modal-foot">
			<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
			<button type="button" class="btn btn-primary" id="confirm_reject">Reject</button>
		</div>
	</div>
</div>

<script>
window.QUOTATION_VEHICLE_MAP = <?php echo json_encode($vehicle_json); ?>;
window.QUOTATION_LOADING_LABELS = <?php echo json_encode(quotation_loading_type_options()); ?>;
window.QUOTATION_PAYMENT_LABELS = <?php echo json_encode(quotation_payment_terms_options()); ?>;
</script>
<script src="javascripts/quotation-form.js?v=20260928partqa"></script>
</body>
</html>
