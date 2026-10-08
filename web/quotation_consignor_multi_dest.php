<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/quotation_functions.php');
require_once('include/vehicle_type_helpers.php');
require_once('include/quotation_consignor_multi_dest.php');

ensure_rate_quotation_tables($conn);
ew_vehicle_type_ensure_schema($conn);

$quote_type_fixed = 'consignor_multi_destination';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_create = !empty($_GET['create']);
if ($id <= 0 && !$is_create) {
	header('Location: quotation_consignor_multi_dest_list.php');
	exit;
}

$quote_no = quotation_preview_number($conn);
$quote_date = date('d-m-Y');
$valid_till = date('d-m-Y', strtotime('+7 days'));

if ($id > 0) {
	$master = quotation_get($conn, $id);
	if (!$master) {
		header('Location: quotation_consignor_multi_dest_list.php');
		exit;
	}
	if (quotation_is_multi_mode_quote_type($master['quote_type'] ?? '')) {
		header('Location: quotation_all_modes.php?id=' . $id);
		exit;
	}
	if (!quotation_is_consignor_multi_dest_quote_type($master['quote_type'] ?? '')) {
		header('Location: quotation.php?id=' . $id);
		exit;
	}
	$quote_no = $master['quote_no'];
	$quote_date = $master['quote_date'];
	$valid_till = $master['valid_till'] ?: $valid_till;
} else {
	$master = array(
		'quotation_id' => 0,
		'status' => 'draft',
		'quote_type' => $quote_type_fixed,
		'subject' => '',
		'party_id' => 0,
		'party_name' => '',
		'customer_mode' => 'new',
		'attn_name' => '',
		'party_email' => '',
		'party_mobile' => '',
		'gst_rate' => 18,
		'terms_notes' => 'We look forward to your confirmation and the opportunity to serve you.',
		'cfs_port_factory' => '',
		'part_number' => '',
		'quotation_approval' => '',
		'freight_paid_by' => '',
		'payment_terms' => '30_days',
		'taxable_value' => 0,
		'total_amount' => 0,
	);
}

$status = $master['status'] ?? 'draft';
$editable = quotation_is_editable($status);
$form_editable = quotation_form_editable($status);
$can_pdf = in_array($status, array('approved', 'sent', 'customer_confirmed', 'converted'), true);

$clients_q = mysqli_query($conn, "SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC");
$customer_mode = trim((string) ($master['customer_mode'] ?? ''));
if ($customer_mode !== 'existing' && $customer_mode !== 'new') {
	$customer_mode = ((int) ($master['party_id'] ?? 0) > 0 && trim((string) ($master['party_name'] ?? '')) === '') ? 'existing' : 'new';
}
if ($customer_mode === 'existing' && trim((string) ($master['party_name'] ?? '')) === '' && (int) ($master['party_id'] ?? 0) > 0) {
	$master['party_name'] = quotation_party_name($conn, (int) $master['party_id']);
}

$vehicle_types = ew_vehicle_type_list($conn, true);
$modes = quotation_mode_transport_catalog($conn);
$city_catalog = quotation_city_state_catalog($conn);
$dest_rows_form = quotation_hydrate_destination_rows_for_form($conn, $id > 0 ? $id : 0);
$delivery_days_opts = quotation_delivery_days_options();
$empty_row = quotation_default_destination_rows_for_form()[0];
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
		.ew-page-v2--invoice .ew-form-grid--quotation .span-4 { grid-column: span 4; }
		.ew-status-pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #e2e8f0; color: #334155; margin-right: 8px; }
		.ew-quotation-layout { display: block; }
		.ew-letter-preview-host { display: none !important; }
		.ew-letter-preview,
		#quotationPreviewModalBody.ew-letter-preview { background: #fff; border: 1px solid #d8e0ea; border-radius: 8px; padding: 20px 22px; font-size: 13px; line-height: 1.55; color: #1e293b; }
		#quotationPreviewModal .ew-v2-modal-body.ew-letter-preview { border: none; border-radius: 0; max-height: min(75vh, 720px); overflow-y: auto; }
		.ew-letter-preview h4 { text-align: center; font-size: 14px; font-weight: 700; text-decoration: underline; margin: 0 0 16px; }
		.ew-letter-preview table.charges { width: 100%; border-collapse: collapse; font-size: 11px; }
		.ew-letter-preview .pv-section { margin: 12px 0 6px; font-size: 12px; font-weight: 700; color: #021659; border-bottom: 2px solid #021659; }
		#md_dest_table input.form-control, #md_dest_table select.form-control { height: 34px; font-size: 12px; padding: 4px 6px; }
		#md_dest_table .md-row-total { background: #f1f5f9; font-weight: 700; text-align: right; }
		#md_dest_table .md-row-actions { width: 72px; white-space: nowrap; vertical-align: middle; }
		#md_dest_table .md-row-actions .btn-link { padding: 2px 6px; font-size: 16px; line-height: 1; }
		#md_dest_table .md-row-actions .btn-md-add-row { color: #021659; }
		.quotation-readonly .form-control:not([readonly]) { pointer-events: none; background: #f8fafc; }
		.ew-form-footer--quotation { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
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
							<a href="quotation_consignor_multi_dest_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php echo $id > 0 ? 'Multi-destination Quotation/Proforma Invoice' : 'Create Multi-destination Quotation/Proforma Invoice'; ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<span class="ew-status-pill"><?php echo htmlspecialchars(quotation_status_label($status)); ?></span>
							<a href="quotation_consignor_multi_dest_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							<?php if ($can_pdf) { ?>
								<a href="quotation_pdf.php?id=<?php echo (int) $id; ?>" target="_blank" class="ew-btn-v2 ew-btn-v2-outline"><i class="fa fa-file"></i> PDF</a>
							<?php } ?>
						</div>
					</div>

					<form id="quotation_form">
						<input type="hidden" name="form_name" value="save_rate_quotation" />
						<input type="hidden" name="quotation_id" id="quotation_id" value="<?php echo (int) $id; ?>" />
						<input type="hidden" name="quotation_action" id="quotation_action" value="save_draft" />
						<input type="hidden" name="quote_type" value="<?php echo htmlspecialchars($quote_type_fixed); ?>" />

						<div id="response" class="alert alert-danger" style="display:none;margin:0 16px 12px;"><div class="message" style="text-align:center"></div></div>

						<div class="ew-quotation-layout">
							<div class="<?php echo $form_editable ? '' : 'quotation-readonly'; ?>">

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Quotation details</h2>
									<div class="ew-form-body">
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
											<div class="ew-field"><label>Quote no</label><div class="ew-invoice-no-display"><?php echo htmlspecialchars($quote_no); ?></div></div>
											<div class="ew-field"><label>Quote date <span class="req">*</span></label><?php echo ew_date_input(array('name' => 'quote_date', 'id' => 'quote_date', 'value' => $quote_date, 'required' => true, 'readonly' => !$form_editable)); ?></div>
											<div class="ew-field"><label>Valid till</label><?php echo ew_date_input(array('name' => 'valid_till', 'id' => 'valid_till', 'value' => $valid_till, 'readonly' => !$form_editable)); ?></div>
											<div class="ew-field span-4"><label>Subject</label><input type="text" name="subject" id="subject" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['subject'] ?? ''); ?>" /></div>
										</div>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Consignor &amp; attention</h2>
									<div class="ew-form-body">
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
											<div class="ew-field span-4">
												<label>Customer type</label>
												<div class="ew-yesno-row" style="margin-top:4px;">
													<label><input type="radio" name="customer_mode" value="new" <?php echo $customer_mode === 'new' ? 'checked' : ''; ?> <?php echo $form_editable ? '' : 'disabled'; ?>> New consignor (not in system)</label>
													<label><input type="radio" name="customer_mode" value="existing" <?php echo $customer_mode === 'existing' ? 'checked' : ''; ?> <?php echo $form_editable ? '' : 'disabled'; ?>> Existing consignor</label>
												</div>
											</div>
											<div class="ew-field span-2 ew-customer-new" style="<?php echo $customer_mode === 'existing' ? 'display:none;' : ''; ?>">
												<label>Consignor (Company) <span class="req">*</span></label>
												<input type="text" name="party_name" id="party_name" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['party_name'] ?? ''); ?>" />
											</div>
											<div class="ew-field span-2 ew-customer-existing" style="<?php echo $customer_mode === 'new' ? 'display:none;' : ''; ?>">
												<label>Consignor <span class="req">*</span></label>
												<select name="party_id" id="party_id" class="form-control" <?php echo $form_editable ? '' : 'disabled'; ?>>
													<option value="">Select consignor</option>
													<?php while ($c = mysqli_fetch_assoc($clients_q)) {
														$name = function_exists('ew_client_decrypt_name') ? ew_client_decrypt_name($c['client_company_name']) : $c['client_company_name'];
														$sel = ((int) $master['party_id'] === (int) $c['client_id']) ? ' selected' : '';
														echo '<option value="' . (int) $c['client_id'] . '"' . $sel . '>' . htmlspecialchars($name) . '</option>';
													} ?>
												</select>
											</div>
											<div class="ew-field span-2"><label>Kind Attn. <span class="req">*</span></label><input type="text" name="attn_name" id="attn_name" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['attn_name'] ?? ''); ?>" /></div>
											<div class="ew-field span-2"><label>Email</label><input type="email" name="party_email" id="party_email" class="form-control" value="<?php echo htmlspecialchars($master['party_email'] ?? ''); ?>" /></div>
											<div class="ew-field span-2"><label>Mobile</label><input type="text" name="party_mobile" id="party_mobile" class="form-control" value="<?php echo htmlspecialchars($master['party_mobile'] ?? ''); ?>" /></div>
										</div>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Destinations &amp; charges</h2>
									<p class="ew-field-hint" style="margin:0 0 10px;"></p>
									<div class="ew-table-wrap">
										<table class="table table-bordered table-condensed" id="md_dest_table">
											<thead>
												<tr>
													<th style="min-width:180px;">Destination (City — State)</th>
													<th style="min-width:120px;">Mode</th>
													<th style="min-width:120px;">Vehicle type</th>
													<th style="min-width:110px;">Delivery days</th>
													<th>Freight</th>
													<th>Doc.</th>
													<th>Others</th>
													<th>Total</th>
													<?php if ($form_editable) { ?><th style="width:72px;">Add</th><?php } ?>
												</tr>
											</thead>
											<tbody id="md_dest_tbody">
												<?php foreach ($dest_rows_form as $dr) {
													echo quotation_render_destination_row_html($conn, $dr, $city_catalog, $modes, $vehicle_types, $delivery_days_opts, $form_editable);
												} ?>
											</tbody>
										</table>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card">
									<h2 class="ew-card-section-title">Tax &amp; commercial summary</h2>
									<div class="ew-form-body">
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
											<div class="ew-field span-2"><label>CFS / Port / Factory / Warehouse</label><input type="text" name="cfs_port_factory" id="cfs_port_factory" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['cfs_port_factory'] ?? ''); ?>" /></div>
											<div class="ew-field span-2"><label>Part Number / Article</label><input type="text" name="part_number" id="part_number" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['part_number'] ?? ''); ?>" /></div>
											<div class="ew-field span-2"><label>Quotation approval</label><select name="quotation_approval" id="quotation_approval" class="form-control pv-bind-select"><option value="">Select</option><?php foreach (quotation_quotation_approval_options() as $k => $lbl) {
												$sel = (($master['quotation_approval'] ?? '') === $k) ? ' selected' : '';
												echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($lbl) . '</option>';
											} ?></select></div>
											<div class="ew-field"><label>Freight paid by</label><select name="freight_paid_by" id="freight_paid_by" class="form-control pv-bind-select"><option value="">Select</option><?php foreach (quotation_consignment_mode_options($conn) as $k => $lbl) {
												if ($lbl === '') continue;
												$sel = (($master['freight_paid_by'] ?? '') === (string) $k) ? ' selected' : '';
												echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($lbl) . '</option>';
											} ?></select></div>
											<div class="ew-field"><label>Payment terms</label><select name="payment_terms" id="payment_terms" class="form-control pv-bind-select"><?php foreach (quotation_payment_terms_options() as $k => $lbl) {
												$sel = (($master['payment_terms'] ?? '30_days') === (string) $k) ? ' selected' : '';
												echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($lbl) . '</option>';
											} ?></select></div>
										</div>
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation" style="margin-top:12px;">
											<div class="ew-field"><label>Taxable value (₹)</label><input type="text" class="form-control" id="taxable_value" readonly value="<?php echo htmlspecialchars($master['taxable_value'] ?? '0'); ?>" /></div>
											<div class="ew-field"><label>GST %</label><select name="gst_rate" id="gst_rate" class="form-control"><?php foreach (array(0, 5, 12, 18, 28) as $g) {
												$sel = ((float) ($master['gst_rate'] ?? 18) === (float) $g) ? ' selected' : '';
												echo '<option value="' . $g . '"' . $sel . '>' . $g . '</option>';
											} ?></select></div>
											<div class="ew-field"><label>GST amount (₹)</label><input type="text" class="form-control" id="gst_amount" readonly /></div>
											<div class="ew-field"><label>Total (₹)</label><input type="text" class="form-control" id="total_amount" readonly style="font-weight:700;" /></div>
											<div class="ew-field span-4"><label>Terms &amp; notes</label><textarea name="terms_notes" id="terms_notes" class="form-control pv-bind" rows="2"><?php echo htmlspecialchars($master['terms_notes'] ?? ''); ?></textarea></div>
										</div>
									</div>
									<div class="ew-form-footer ew-form-footer--quotation">
										<?php if ($editable) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-action" data-action="save_draft">Save draft</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_quotation_preview"><i class="fa fa-eye"></i> Preview</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-action" data-action="submit">Submit for approval</button>
										<?php } elseif ($form_editable) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-action" data-action="save_draft">Save changes</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_quotation_preview"><i class="fa fa-eye"></i> Preview</button>
										<?php } else { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_quotation_preview"><i class="fa fa-eye"></i> Preview</button>
										<?php } ?>
										<?php if ($status === 'pending_approval') { ?>
											<p class="ew-field-hint" style="width:100%;margin:0 0 6px;text-align:right;">Approving emails the PDF from athar@elitewave360.in to the customer address above, with a copy to info@elitewave360.in.</p>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-workflow" data-action="approve">Approve &amp; email PDF</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="reject">Reject</button>
										<?php } ?>
										<?php if ($status === 'approved') { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-workflow" data-action="mark_sent">Mark sent</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="resend_email">Resend email</button>
										<?php } ?>
										<?php if (in_array($status, array('sent', 'customer_confirmed'), true)) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="resend_email">Resend email</button>
										<?php } ?>
										<?php if ($status === 'sent') { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-workflow" data-action="customer_confirmed">Customer confirmed</button>
										<?php } ?>
										<?php if (!in_array($status, array('converted', 'cancelled'), true)) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="cancel">Cancel</button>
										<?php } ?>
									</div>
								</div>
							</div>
							<aside class="ew-letter-preview ew-letter-preview-host" id="letter_preview" aria-hidden="true"></aside>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>

<div id="md_row_template" style="display:none;">
	<table><tbody><?php echo quotation_render_destination_row_html($conn, $empty_row, $city_catalog, $modes, $vehicle_types, $delivery_days_opts, true); ?></tbody></table>
</div>

<div class="ew-v2-modal-backdrop" id="quotationPreviewModal">
	<div class="ew-v2-modal ew-v2-modal--lg">
		<div class="ew-v2-modal-head">
			<h3>Quotation preview</h3>
			<button type="button" class="ew-v2-modal-close" data-ew-v2-close>&times;</button>
		</div>
		<div class="ew-v2-modal-body ew-letter-preview" id="quotationPreviewModalBody"></div>
		<div class="ew-v2-modal-foot">
			<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
		</div>
	</div>
</div>

<div class="ew-v2-modal-backdrop" id="rejectQuotationModal">
	<div class="ew-v2-modal">
		<div class="ew-v2-modal-head"><h3>Reject quotation</h3><button type="button" class="ew-v2-modal-close" data-ew-v2-close>&times;</button></div>
		<div class="ew-v2-modal-body"><label>Remarks <span style="color:red">*</span></label><textarea id="reject_remarks" class="form-control" rows="3"></textarea></div>
		<div class="ew-v2-modal-foot"><button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button><button type="button" class="btn btn-primary" id="confirm_reject">Reject</button></div>
	</div>
</div>

<script>
window.QUOTATION_LETTER_INTRO = <?php echo json_encode(quotation_letter_intro_text()); ?>;
window.QUOTATION_PREVIEW_IN_MODAL = true;
window.QUOTATION_RETURN_PAGE = 'quotation_consignor_multi_dest.php';
</script>
<script src="javascripts/quotation-consignor-multi-dest-form.js?v=20261005mdvehicle"></script>
</body>
</html>
