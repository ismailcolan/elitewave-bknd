<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/quotation_functions.php');
require_once('include/vehicle_type_helpers.php');
require_once('include/quotation_multi_mode.php');

ensure_rate_quotation_tables($conn);
ew_vehicle_type_ensure_schema($conn);

$mm_types = quotation_multi_mode_quote_types();
$default_mm_type = 'consignee_consignor_all_modes';
$create_type = isset($_GET['type']) ? trim((string) $_GET['type']) : $default_mm_type;
if (!isset($mm_types[$create_type])) {
	$create_type = $default_mm_type;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_create = !empty($_GET['create']);
if ($id <= 0 && !$is_create) {
	header('Location: quotation_all_modes_list.php');
	exit;
}

$master = null;
$quote_no = quotation_preview_number($conn);
$quote_date = date('d-m-Y');
$valid_till = date('d-m-Y', strtotime('+7 days'));

if ($id > 0) {
	$master = quotation_get($conn, $id);
	if (!$master) {
		header('Location: quotation_all_modes_list.php');
		exit;
	}
	if (quotation_is_consignor_multi_dest_quote_type($master['quote_type'] ?? '')) {
		header('Location: quotation_consignor_multi_dest.php?id=' . $id);
		exit;
	}
	if (!quotation_is_multi_mode_quote_type($master['quote_type'] ?? '')) {
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
		'quote_type' => $create_type,
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
		'destination_name' => '',
		'unloading_at' => '',
		'delivery_address' => '',
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
$form_editable = quotation_form_editable($status);
$can_pdf = in_array($status, array('approved', 'sent', 'customer_confirmed', 'converted'), true);

$clients_q = mysqli_query($conn, "SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC");
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
$source_trains = quotation_active_trains($conn);
$source_flights = quotation_active_flights($conn);
$mode_rows_form = quotation_hydrate_mode_rows_for_form($conn, $id > 0 ? $id : 0);
$delivery_days_opts = quotation_delivery_days_options();
$mode_catalog = quotation_mode_transport_catalog($conn);
$mode_catalog_json = $mode_catalog;
$empty_mm_row = quotation_default_mode_rows_for_form()[0];

function ew_quotation_all_modes_status_pill_class($status)
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
		.ew-quotation-layout { display: block; }
		.ew-letter-preview-host { display: none !important; }
		.ew-letter-preview,
		#quotationPreviewModalBody.ew-letter-preview { background: #fff; border: 1px solid #d8e0ea; border-radius: 8px; padding: 20px 22px; font-size: 13px; line-height: 1.55; color: #1e293b; }
		#quotationPreviewModal .ew-v2-modal-body.ew-letter-preview { border: none; border-radius: 0; max-height: min(75vh, 720px); overflow-y: auto; }
		.ew-letter-preview h4 { text-align: center; font-size: 14px; font-weight: 700; text-decoration: underline; margin: 0 0 16px; }
		.ew-letter-preview table.charges { width: 100%; margin: 8px 0; border-collapse: collapse; }
		.ew-letter-preview table.charges td { padding: 3px 0; border-bottom: 1px solid #e2e8f0; }
		.ew-letter-preview table.charges td:last-child { text-align: right; white-space: nowrap; font-weight: 600; }
		.ew-letter-preview .pv-section { margin: 12px 0 6px; font-size: 12px; font-weight: 700; color: #021659; border-bottom: 2px solid #021659; padding-bottom: 3px; }
		.ew-letter-preview .pv-kv { margin: 4px 0; font-size: 12px; }
		.ew-letter-preview .pv-kv b { color: #021659; }
		.ew-form-footer--quotation { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
		.quotation-readonly .form-control:not([readonly]) { pointer-events: none; background: #f8fafc; }
		#multi_mode_table input.form-control, #multi_mode_table select.form-control { height: 34px; font-size: 12px; padding: 4px 8px; }
		#multi_mode_table .mm-row-total { background: #f1f5f9; font-weight: 700; text-align: right; }
		.ew-mm-vehicle-road, .ew-mm-vehicle-text { min-width: 140px; }
		#multi_mode_table .mm-row-actions { width: 72px; white-space: nowrap; vertical-align: middle; }
		.mm-row-action .mm-row-btn {
			display: inline-flex; align-items: center; justify-content: center;
			width: 28px; height: 28px; border: 1px solid #cbd5e1; border-radius: 6px;
			background: #fff; cursor: pointer; padding: 0; line-height: 1;
		}
		.mm-row-action .mm-row-btn + .mm-row-btn { margin-left: 4px; }
		.mm-row-action .mm-row-btn.is-add { color: #059669; border-color: #86efac; }
		.mm-row-action .mm-row-btn.is-add:hover { background: #ecfdf5; }
		.mm-row-action .mm-row-btn.is-remove { color: #dc2626; border-color: #fca5a5; }
		.mm-row-action .mm-row-btn.is-remove:hover { background: #fef2f2; }
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
							<a href="quotation_all_modes_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php echo $id > 0 ? 'Multi-mode Quotation/Proforma Invoice' : 'Create Multi-mode Quotation/Proforma Invoice'; ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<span class="ew-status-pill <?php echo ew_quotation_all_modes_status_pill_class($status); ?>"><?php echo htmlspecialchars(quotation_status_label($status)); ?></span>
							<a href="quotation_all_modes_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
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
							<div class="<?php echo $form_editable ? '' : 'quotation-readonly'; ?>">

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
												<?php echo ew_date_input(array('name' => 'quote_date', 'id' => 'quote_date', 'value' => $quote_date, 'required' => true, 'readonly' => !$form_editable)); ?>
											</div>
											<div class="ew-field">
												<label>Valid till</label>
												<?php echo ew_date_input(array('name' => 'valid_till', 'id' => 'valid_till', 'value' => $valid_till, 'readonly' => !$form_editable)); ?>
											</div>
											<div class="ew-field">
												<label>Quote type</label>
												<select name="quote_type" id="quote_type" class="form-control">
													<?php foreach ($mm_types as $k => $lbl) {
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
													<label><input type="radio" name="customer_mode" value="new" <?php echo $customer_mode === 'new' ? 'checked' : ''; ?> <?php echo $form_editable ? '' : 'disabled'; ?>> New client (not in system)</label>
													<label><input type="radio" name="customer_mode" value="existing" <?php echo $customer_mode === 'existing' ? 'checked' : ''; ?> <?php echo $form_editable ? '' : 'disabled'; ?>> Existing client</label>
												</div>
											</div>
											<div class="ew-field span-2 ew-customer-new" style="<?php echo $customer_mode === 'existing' ? 'display:none;' : ''; ?>">
												<label>To (Customer / Company) <span class="req">*</span></label>
												<input type="text" name="party_name" id="party_name" class="form-control pv-bind" maxlength="255" value="<?php echo htmlspecialchars($master['party_name'] ?? ''); ?>" />
											</div>
											<div class="ew-field span-2 ew-customer-existing" style="<?php echo $customer_mode === 'new' ? 'display:none;' : ''; ?>">
												<label>To (Customer) <span class="req">*</span></label>
												<select name="party_id" id="party_id" class="form-control" <?php echo $form_editable ? '' : 'disabled'; ?>>
													<option value="">Select customer</option>
													<?php
													while ($c = mysqli_fetch_assoc($clients_q)) {
														$name = function_exists('ew_client_decrypt_name') ? ew_client_decrypt_name($c['client_company_name']) : $c['client_company_name'];
														$sel = ((int) $master['party_id'] === (int) $c['client_id']) ? ' selected' : '';
														echo '<option value="' . (int) $c['client_id'] . '"' . $sel . '>' . htmlspecialchars($name) . '</option>';
													}
													?>
												</select>
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
											<div class="ew-field span-4">
												<label>Consignee / delivery party <span class="req">*</span></label>
												<input type="text" name="destination_name" id="destination_name" class="form-control pv-bind" value="<?php echo htmlspecialchars($master['destination_name'] ?? ''); ?>" />
											</div>
											<div class="ew-field span-4">
												<label>Delivery address</label>
												<textarea name="delivery_address" id="delivery_address" class="form-control pv-bind" rows="3"><?php echo htmlspecialchars($master['delivery_address'] ?? ''); ?></textarea>
											</div>
										</div>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card" id="multi_mode_card">
									<h2 class="ew-card-section-title">Mode-wise quotation (all modes)</h2>
									<p class="ew-field-hint" style="margin:0 0 10px;"></p>
									<div class="ew-table-wrap">
										<table class="table table-bordered table-condensed" id="multi_mode_table">
											<thead>
												<tr>
													<th style="min-width:160px;">Mode</th>
													<th style="min-width:180px;">Source of transport</th>
													<th style="min-width:130px;">Delivery days</th>
													<th style="width:16%">Freight charges</th>
													<?php if ($form_editable) { ?><th class="text-center mm-row-actions"> </th><?php } ?>
												</tr>
											</thead>
											<tbody id="multi_mode_tbody">
												<?php foreach ($mode_rows_form as $mr) {
													echo quotation_render_mode_row_html($mr, $mode_catalog, $vehicle_types, $delivery_days_opts, $form_editable, $source_trains, $source_flights);
												} ?>
											</tbody>
										</table>
									</div>
								</div>

								<div class="ew-card ew-invoice-details-card" id="charges_tax_card">
									<h2 class="ew-card-section-title">Tax &amp; commercial summary</h2>
									<div class="ew-form-body">
										<h3 class="ew-card-section-subtitle" style="margin:0 0 10px;font-size:14px;font-weight:600;">Commercial summary fields</h3>
										<div class="ew-form-grid ew-form-grid--invoice ew-form-grid--quotation">
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
												<label>Payment terms</label>
												<select name="payment_terms" id="payment_terms" class="form-control pv-bind-select">
													<?php
													$pt = trim((string) ($master['payment_terms'] ?? '30_days'));
													$pt_opts = quotation_payment_terms_options();
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
											<input type="hidden" id="gst_amount" value="" />
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

							<aside class="ew-letter-preview ew-letter-preview-host" id="letter_preview" aria-hidden="true"></aside>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>

<div id="mm_row_template" style="display:none;">
	<table><tbody><?php echo quotation_render_mode_row_html($empty_mm_row, $mode_catalog, $vehicle_types, $delivery_days_opts, true, $source_trains, $source_flights); ?></tbody></table>
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
window.QUOTATION_LOADING_LABELS = <?php echo json_encode(quotation_loading_type_options()); ?>;
window.QUOTATION_PAYMENT_LABELS = <?php echo json_encode(quotation_payment_terms_options()); ?>;
window.QUOTATION_LETTER_INTRO = <?php echo json_encode(quotation_letter_intro_text()); ?>;
window.QUOTATION_MULTI_MODE_TYPES = <?php echo json_encode(array_keys($mm_types)); ?>;
window.QUOTATION_DELIVERY_DAYS = <?php echo json_encode($delivery_days_opts); ?>;
window.QUOTATION_MODE_CATALOG = <?php echo json_encode($mode_catalog_json); ?>;
window.QUOTATION_ALL_MODES_SCREEN = true;
window.QUOTATION_PREVIEW_IN_MODAL = true;
window.QUOTATION_RETURN_PAGE = 'quotation_all_modes.php';
</script>
<script src="javascripts/quotation-form.js?v=20261008mmsource2"></script>
</body>
</html>
