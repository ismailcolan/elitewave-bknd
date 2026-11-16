<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_bill_helpers.php');

expense_bill_ensure_schema($conn);
expense_require_admin();

$c_date = date('d-m-Y');
$preselect_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($preselect_id <= 0 && empty($_GET['create'])) {
	header('Location:expense_bill_list.php');
	exit;
}
$is_create = ($preselect_id <= 0 && !empty($_GET['create']));
$preview_bill_no = expense_bill_preview_next_no($conn);
$vendor_rows = expense_gcn_vendor_options($conn);
$tds_percent_options = expense_bill_tds_percent_options($conn);
$gst_percent_options = expense_bill_gst_percent_options($conn);
$bill_due_days = expense_bill_due_days();
$payment_modes = ew_company_bank_payment_modes();
$bank_rows = ew_company_bank_options($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2 .ew-form-grid--bill {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}

		.ew-page-v2 .ew-form-grid--bill .span-3 {
			grid-column: span 3;
		}

		@media (max-width: 991px) {
			.ew-page-v2 .ew-form-grid--bill {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}

			.ew-page-v2 .ew-form-grid--bill .span-3 {
				grid-column: span 2;
			}
		}

		@media (max-width: 640px) {
			.ew-page-v2 .ew-form-grid--bill {
				grid-template-columns: 1fr;
			}

			.ew-page-v2 .ew-form-grid--bill .span-3 {
				grid-column: span 1;
			}
		}

		#bill_entry_no_display {
			font-weight: 600;
		}

		.ew-page-v2 .ew-field input.form-control,
		.ew-page-v2 .ew-field select.form-control {
			height: 34px;
		}

		.ew-form-footer.ebill-form-footer {
			justify-content: flex-end;
			align-items: center;
			gap: 16px;
			margin: 0 !important;
			padding: 14px 24px 22px !important;
			width: 100%;
			max-width: 100%;
			box-sizing: border-box;
		}

		.ebill-form-footer-actions {
			display: flex;
			align-items: center;
			justify-content: flex-end;
			gap: 8px;
			flex-wrap: wrap;
		}

		.ebill-attach-grid {
			display: flex;
			flex-wrap: wrap;
			gap: 14px;
			margin-top: 12px;
		}

		.ebill-attach-card {
			width: 160px;
			flex: 0 0 160px;
			border: 1px solid var(--ew-border-light, #E9ECF0);
			border-radius: 8px;
			padding: 10px;
			background: #FAFBFC;
			text-align: center;
		}

		.ebill-attach-card .ebill-attach-icon {
			display: flex;
			align-items: center;
			justify-content: center;
			height: 72px;
			margin-bottom: 8px;
			border-radius: 6px;
			background: #F1F5F9;
			color: #64748b;
			font-size: 28px;
		}

		.ebill-attach-card .ebill-attach-icon img {
			max-width: 100%;
			max-height: 72px;
			object-fit: contain;
			border-radius: 4px;
		}

		.ebill-attach-card p {
			margin: 0 0 8px;
			font-size: 12px;
			line-height: 1.35;
			color: #334155;
			word-break: break-word;
		}

		.ebill-attach-card .btn-remove-att {
			margin-top: 2px;
		}

		.ebill-attach-empty {
			font-size: 13px;
			color: #64748b;
			margin-top: 8px;
		}

		#gcn_summary_wrap {
			display: none;
		}

		#gcn_summary_wrap.is-visible {
			display: block;
		}

		.ew-form-grid--bill .bank-link-wrap .btn-add-inline {
			height: 34px;
			padding: 0 12px;
			white-space: nowrap;
			flex: 0 0 auto;
		}

		.bank-readonly {
			font-weight: 600;
		}

		.ew-form-grid--bank-inline {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 20px 16px;
		}

		@media (max-width: 991px) {
			.ew-form-grid--bank-inline {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}

		@media (max-width: 640px) {
			.ew-form-grid--bank-inline {
				grid-template-columns: 1fr;
			}
		}

		/* Selected GCN table — uses the app's standard .ew-table-wrap / .ew-table look (rail header) */
		.ew-page-v2 .ebill-gcn-table-wrap {
			border: 1px solid var(--panel-border, #C5D3E0);
			border-radius: 10px;
			overflow: hidden;
			overflow-x: auto;
			background: #fff;
		}
		.ew-page-v2 .ebill-gcn-table {
			width: 100%;
			min-width: 900px;
			margin: 0;
		}
		.ew-page-v2 .ebill-gcn-table-wrap table thead th.num,
		.ew-page-v2 .ebill-gcn-table-wrap table tfoot td.num {
			text-align: right !important;
		}
		.ew-page-v2 .ebill-gcn-table-wrap table thead th.ctr {
			text-align: center !important;
		}
		.ew-page-v2 .ebill-gcn-table th.c-no,
		.ew-page-v2 .ebill-gcn-table td.c-no { white-space: nowrap; font-weight: 600; }
		.ew-page-v2 .ebill-gcn-table th.c-date,
		.ew-page-v2 .ebill-gcn-table td.c-date { white-space: nowrap; }
		.ew-page-v2 .ebill-gcn-table th.c-consignor { min-width: 180px; }
		.ew-page-v2 .ebill-gcn-table th.c-consignee { min-width: 150px; }
		.ew-page-v2 .ebill-gcn-table th.c-route { min-width: 120px; }
		.ew-page-v2 .ebill-gcn-table th.c-freight { width: 140px; }
		.ew-page-v2 .ebill-gcn-table th.c-gst { width: 190px; }
		.ew-page-v2 .ebill-gcn-table .ebill-gst-inline {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.ew-page-v2 .ebill-gcn-table .ebill-gst-inline select.ebill-gst-pct {
			flex: 0 0 84px;
			width: 84px;
		}
		.ew-page-v2 .ebill-gcn-table .ebill-gst-inline .ebill-gst-amt {
			flex: 1;
			text-align: right;
			white-space: nowrap;
			font-variant-numeric: tabular-nums;
		}
		.ew-page-v2 .ebill-gcn-table th.c-total { width: 120px; }
		.ew-page-v2 .ebill-gcn-table-wrap table tbody td.c-input {
			padding-top: 8px !important;
			padding-bottom: 8px !important;
		}
		.ew-page-v2 .ebill-gcn-table input.ebill-freight,
		.ew-page-v2 .ebill-gcn-table select.ebill-gst-pct {
			display: block;
			width: 100%;
			min-width: 84px;
			height: 34px;
			margin: 0;
			padding: 0 26px 0 10px;
			border: 1.5px solid var(--ew-border, #D8DDE5);
			border-radius: var(--ew-radius-sm, 6px);
			font-size: 13px;
			line-height: 32px;
			color: var(--ew-text, #1A2332);
			text-align: left;
			-webkit-appearance: none;
			-moz-appearance: none;
			appearance: none;
			cursor: pointer;
			box-shadow: none;
			background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7A8D' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 9px center;
		}
		.ew-page-v2 .ebill-gcn-table select.ebill-gst-pct:focus {
			border-color: var(--ew-navy, #0A1E3D);
			box-shadow: 0 0 0 3px rgba(10, 30, 61, .10);
			outline: none;
		}
		.ew-page-v2 .ebill-gcn-table input.ebill-freight.is-invalid {
			border-color: #DC2626;
			box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
		}
		.ew-page-v2 .ebill-gcn-table td.ebill-total-cell {
			font-weight: 700;
		}
		.ew-page-v2 .ebill-gcn-table tfoot td {
			background: #F4F7FA;
			border-top: 2px solid var(--panel-border, #C5D3E0);
			padding: 12px 16px;
			font-size: 13px;
			font-weight: 700;
			font-variant-numeric: tabular-nums;
			color: var(--ew-text, #1A2332);
		}
		.ew-page-v2 .ebill-gcn-table tfoot td.ebill-foot-label {
			text-align: right;
			font-size: 11px;
			letter-spacing: .04em;
			text-transform: uppercase;
		}
		.ew-page-v2 .ebill-gcn-table-wrap table tbody td.ebill-empty {
			text-align: center !important;
			color: #94A3B8;
			padding: 20px 16px !important;
		}

		.ebill-totals {
			margin-top: 12px;
			max-width: 420px;
			margin-left: auto;
			border: 1px solid var(--ew-border-light, #E9ECF0);
			border-radius: 8px;
			padding: 12px 14px;
			background: #FAFBFC;
		}

		.ebill-totals-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			margin-bottom: 8px;
			font-size: 13px;
			color: #334155;
		}

		.ebill-totals-row:last-child {
			margin-bottom: 0;
		}

		.ebill-totals-row strong {
			font-size: 14px;
			color: #0f172a;
		}

		.ebill-totals-row .egen-select-wrap,
		.ebill-totals-row select.form-control {
			width: 160px;
			max-width: 46%;
		}
		.ebill-tds-row .ebill-tds-inline {
			display: flex;
			align-items: center;
			justify-content: flex-end;
			gap: 14px;
			flex: 1;
		}
		.ebill-tds-row .ebill-tds-inline .egen-select-wrap {
			width: 170px;
			max-width: none;
		}
		.ebill-tds-row .ebill-tds-inline strong {
			min-width: 72px;
			text-align: right;
		}

		#due_date[readonly] {
			background: #F8FAFC;
			cursor: default;
		}
	</style>
</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php require_once('include/header.php');
			require_once('include/menu.php'); ?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<a href="expense_bill_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php echo $preselect_id > 0 ? 'Edit Bill Registration' : 'Add Bill Registration'; ?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="expense_bill_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-form-body">
							<form id="bill_registration_form" enctype="multipart/form-data">
								<div class="ew-form-grid ew-form-grid--bill">
									<div class="ew-section-label">Bill Details</div>

									<div class="ew-field">
										<label class="control-label">Bill Entry No. :</label>
										<input type="text" id="bill_entry_no_display" class="form-control" readonly value="<?php echo $preselect_id > 0 ? '' : htmlspecialchars($preview_bill_no); ?>">
									</div>
									<div class="ew-field">
										<label class="control-label">Bill Date <span style="color:red;">*</span> :</label>
										<input type="text" id="bill_date" name="bill_date" class="form-control ew-date-field" value="<?php echo htmlspecialchars($c_date); ?>" data-ew-datepicker="1" data-date-format="dd-mm-yyyy" autocomplete="off">
									</div>
									<input type="hidden" id="bill_type" name="bill_type" value="transaction">

									<div class="ew-field">
										<label class="control-label">Vendor <span style="color:red;">*</span> :</label>
										<div class="egen-select-wrap">
											<select id="vendor_id" name="vendor_id" class="form-control egen-select"></select>
										</div>
										<span class="ew-field-hint" id="vendor_terms_hint"></span>
									</div>
									<div class="ew-field" id="gcn_wrap">
										<label class="control-label">GCN No. &amp; Date <span style="color:red;">*</span> : (Select one or more GCNs)</label>
										<div class="egen-select-wrap">
											<select id="gcn_keys" name="gcn_keys[]" class="form-control egen-select" multiple="multiple"></select>
										</div>
									</div>
									<div class="ew-field">
										<label class="control-label">Vendor Invoice/Memo No. <span style="color:red;">*</span> :</label>
										<input type="text" id="vendor_invoice_no" name="vendor_invoice_no" class="form-control" maxlength="100" autocomplete="off">
									</div>

									<div class="ew-field">
										<label class="control-label">Vendor Invoice/Memo Date <span style="color:red;">*</span> :</label>
										<input type="text" id="vendor_invoice_date" name="vendor_invoice_date" class="form-control ew-date-field" value="" data-ew-datepicker="1" data-date-format="dd-mm-yyyy" autocomplete="off">
									</div>
									<div class="ew-field">
										<label class="control-label">Invoice/Memo Received Date <span style="color:red;">*</span> :</label>
										<input type="text" id="invoice_received_date" name="invoice_received_date" class="form-control ew-date-field" value="<?php echo htmlspecialchars($c_date); ?>" data-ew-datepicker="1" data-date-format="dd-mm-yyyy" autocomplete="off">
									</div>
									<div class="ew-field">
										<label class="control-label">Due Date <span style="color:red;">*</span> :</label>
										<input type="text" id="due_date" name="due_date" class="form-control" value="" readonly autocomplete="off">
										<span class="ew-field-hint" id="due_date_hint">Auto: vendor invoice date + vendor credit days</span>
									</div>

									<div class="ew-field span-3" id="gcn_summary_wrap">
										<label class="control-label">Selected GCN Details :</label>
										<div class="ew-table-wrap ebill-gcn-table-wrap">
											<table class="table ew-table ebill-gcn-table" id="gcn_summary_table">
												<thead>
													<tr>
														<th class="c-no">GCN No.</th>
														<th class="c-date">GCN Date</th>
														<th class="c-consignor">Consignor</th>
														<th class="c-consignee">Consignee</th>
														<th class="c-route">Route</th>
														<th class="num c-freight">Freight Amount <span style="color:#DC2626;">*</span></th>
														<th class="c-gst">GST</th>
														<th class="num c-total">Total</th>
													</tr>
												</thead>
												<tbody id="gcn_summary_body">
													<tr><td colspan="8" class="ebill-empty">No GCNs selected.</td></tr>
												</tbody>
												<tfoot id="gcn_summary_foot" style="display:none;">
													<tr>
														<td colspan="5" class="ebill-foot-label">Total</td>
														<td class="num" id="gcn_foot_freight">0.00</td>
														<td class="num" id="gcn_foot_gst">0.00</td>
														<td class="num" id="gcn_foot_total">0.00</td>
													</tr>
												</tfoot>
											</table>
										</div>
										<div class="ebill-totals" id="gcn_totals_block">
											<div class="ebill-totals-row">
												<span>Sub Total</span>
												<strong id="bill_subtotal_display">0.00</strong>
											</div>
											<div class="ebill-totals-row">
												<span>GST Total</span>
												<strong id="bill_gst_total_display">0.00</strong>
											</div>
											<div class="ebill-totals-row">
												<span>Grand Total</span>
												<strong id="bill_grand_total_display">0.00</strong>
											</div>
											<div class="ebill-totals-row ebill-tds-row">
												<span>TDS</span>
												<div class="ebill-tds-inline">
													<div class="egen-select-wrap">
														<select id="tds_percent" name="tds_percent" class="form-control egen-select">
															<?php foreach ($tds_percent_options as $opt) { ?>
																<option value="<?php echo htmlspecialchars((string) $opt['rate']); ?>"><?php echo htmlspecialchars($opt['label']); ?></option>
															<?php } ?>
														</select>
													</div>
													<strong id="bill_tds_amount_display">0.00</strong>
												</div>
											</div>
											<div class="ebill-totals-row">
												<span>Net Payable</span>
												<strong id="bill_net_payable_display">0.00</strong>
											</div>
										</div>
										<input type="hidden" id="bill_subtotal" name="subtotal" value="0">
										<input type="hidden" id="bill_gst_total" name="gst_total" value="0">
										<input type="hidden" id="bill_grand_total" name="grand_total" value="0">
										<input type="hidden" id="bill_tds_amount" name="tds_amount" value="0">
										<input type="hidden" id="bill_net_payable" name="net_payable" value="0">
									</div>

									<div class="ew-field span-3">
										<label class="control-label">Narration :</label>
										<textarea id="narration" name="narration" class="form-control" maxlength="1000" rows="3" placeholder="Optional notes"></textarea>
									</div>

									<div class="ew-section-label">Payment Details</div>

									<div class="ew-field">
										<label class="control-label">Payment Mode <span style="color:red;">*</span> :</label>
										<div class="egen-select-wrap">
											<select id="payment_mode" name="payment_mode" class="form-control egen-select">
												<option value=""></option>
												<?php foreach ($payment_modes as $code => $label) { ?>
													<option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($label); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="ew-field" id="payment_bank_account_wrap" style="display:none;">
										<label class="control-label">Company Bank Account :</label>
										<div class="bank-link-wrap">
											<div class="egen-select-wrap">
												<select id="company_bank_id" name="company_bank_id" class="form-control egen-select"></select>
											</div>
											<a href="company_bank.php" class="ew-btn-v2 ew-btn-v2-outline btn-add-inline" title="Manage company bank accounts">Manage</a>
										</div>
									</div>

									<div class="ew-field span-3" id="bank_details_row" style="display:none;">
										<div class="ew-form-grid--bank-inline">
											<div class="ew-field">
												<label class="control-label">Bank Name :</label>
												<input type="text" id="payment_bank_name" name="payment_bank_name" class="form-control bank-readonly" readonly autocomplete="off">
											</div>
											<div class="ew-field">
												<label class="control-label">IFSC Code :</label>
												<input type="text" id="payment_ifsc" name="payment_ifsc" class="form-control bank-readonly" readonly autocomplete="off">
											</div>
											<div class="ew-field">
												<label class="control-label">Branch :</label>
												<input type="text" id="payment_bank_branch" name="payment_bank_branch" class="form-control bank-readonly" readonly autocomplete="off">
											</div>
										</div>
									</div>

									<div class="ew-field span-3">
										<label class="control-label">Attachments :</label>
										<div class="ew-upload-image ew-upload-image--multi" id="bill_attach_drop_wrap">
											<div class="ew-upload-image__zone" tabindex="0" role="button" aria-label="Upload bill attachments">
												<input type="file" id="attachments" name="attachments[]" class="ew-upload-image__input" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,image/jpeg,image/png,application/pdf">
												<div class="ew-upload-image__body">
													<i class="fa fa-cloud-upload" aria-hidden="true"></i>
													<span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
													<span class="ew-upload-image__meta">PDF, JPG, PNG, Word, Excel · max 10 MB each</span>
													<span class="ew-upload-image__filename bill-attach-file-name"></span>
													<span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
												</div>
											</div>
										</div>
										<div class="ew-section-label" id="existing_attachments_label" style="margin-top:16px;display:none;">Existing Attachments</div>
										<div class="ebill-attach-grid" id="existing_attachments"></div>
									</div>
								</div>
							</form>
							</div>
							<div class="ew-form-footer ebill-form-footer">
								<div class="ebill-form-footer-actions">
									<a href="expense_bill_list.php" class="ew-btn-v2 ew-btn-v2-outline">Cancel</a>
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btn_save_bill">Save</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>

	<script type="text/javascript">
		var billId = <?php echo (int) $preselect_id; ?>;
		var nextBillNoPreview = <?php echo json_encode($preview_bill_no, JSON_UNESCAPED_UNICODE); ?>;
		var vendorOptions = <?php echo json_encode($vendor_rows, JSON_UNESCAPED_UNICODE); ?>;
		var tdsPercentOptions = <?php echo json_encode($tds_percent_options, JSON_UNESCAPED_UNICODE); ?>;
		var bankOptions = <?php echo json_encode($bank_rows, JSON_UNESCAPED_UNICODE); ?>;
		var billDueDays = <?php echo (int) $bill_due_days; ?>;
		var gstPercentOptions = <?php echo json_encode($gst_percent_options, JSON_UNESCAPED_UNICODE); ?>;
		var gcnAmounts = {}; // gcn_key => { freight, gst_percent } entered manually from vendor invoice
		var vendorCreditDays = 0;
		var gcnOptions = [];
		var selectedGcnDetails = [];
		var vendorDefaultTdsPercent = 0;
		var removeAttachmentIds = [];
		var gcnDetailsRequestSeq = 0;

		function escHtml(v) {
			if (v === null || v === undefined) return '';
			return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}
		function getSelectVal($el) {
			if (!$el || !$el.length) return '';
			var val = $el.val();
			if ($el.data('select2')) val = $el.select2('val');
			if ($.isArray(val)) val = val[0] || '';
			return val ? String(val) : '';
		}
		function getMultiSelectVal($el) {
			if (!$el || !$el.length) return [];
			var val = $el.val();
			if ($el.data('select2')) val = $el.select2('val');
			if (!val) return [];
			if (!$.isArray(val)) val = [val];
			return $.map(val, function(v) { return v ? String(v) : null; });
		}
		function setSelectVal($el, value) {
			if (!$el || !$el.length) return;
			if ($el.data('select2')) $el.select2('val', value || '');
			else $el.val(value || '');
		}
		function setMultiSelectVal($el, values) {
			if (!$el || !$el.length) return;
			if (!$.isArray(values)) {
				values = values ? [values] : [];
			}
			values = $.map(values, function(v) { return v ? String(v) : null; });
			if ($el.data('select2')) $el.select2('val', values);
			else $el.val(values);
		}
		function syncSelectTitle($select) {
			if (!$select || !$select.length) return;
			var text = $.trim($select.find('option:selected').text());
			$select.next('.select2-container').find('.select2-chosen').attr('title', text || '');
		}
		function initSelect2($el, placeholder) {
			if (!$el || !$el.length) return;
			if ($el.data('select2')) $el.select2('destroy');
			$el.select2({
				width: '100%',
				placeholder: placeholder || 'Select',
				allowClear: true,
				minimumResultsForSearch: 0
			});
			syncSelectTitle($el);
		}
		function fillSelectOptions($el, rows, valueKey, labelKey, selected) {
			var isMulti = $el.prop('multiple');
			var html = isMulti ? '' : '<option value=""></option>';
			var selectedMap = {};
			if ($.isArray(selected)) {
				$.each(selected, function(i, v) { selectedMap[String(v)] = true; });
			} else if (selected) {
				selectedMap[String(selected)] = true;
			}
			$.each(rows || [], function(i, row) {
				var val = row[valueKey];
				var label = row[labelKey] || row.label || val;
				html += '<option value="' + escHtml(val) + '"' + (selectedMap[String(val)] ? ' selected' : '') + '>' + escHtml(label) + '</option>';
			});
			if ($el.data('select2')) $el.select2('destroy');
			$el.html(html);
		}
		function buildVendorSelect(selected) {
			var $el = $('#vendor_id');
			fillSelectOptions($el, vendorOptions, 'vendor_id', 'label', selected);
			initSelect2($el, 'Select vendor');
			setSelectVal($el, selected || '');
		}
		function buildGcnSelect(selected) {
			var $el = $('#gcn_keys');
			fillSelectOptions($el, gcnOptions, 'key', 'label', selected || []);
			if ($el.data('select2')) $el.select2('destroy');
			$el.select2({
				width: '100%',
				placeholder: 'Select GCN(s)',
				allowClear: true,
				minimumResultsForSearch: 0
			});
			setMultiSelectVal($el, selected || []);
			$el.off('change.ebillGcn').on('change.ebillGcn', function() {
				refreshSelectedGcnDetails();
			});
		}
		function buildBankSelect(selected) {
			var $el = $('#company_bank_id');
			fillSelectOptions($el, bankOptions, 'bank_account_id', 'label', selected);
			initSelect2($el, 'Select company bank account');
			setSelectVal($el, selected || '');
		}
		function initPaymentModeSelect(selected) {
			var $el = $('#payment_mode');
			if ($el.data('select2')) $el.select2('destroy');
			initSelect2($el, 'Select payment mode');
			setSelectVal($el, selected || '');
		}
		function getBankById(id) {
			var found = null;
			$.each(bankOptions, function(i, b) {
				if (String(b.bank_account_id) === String(id)) found = b;
			});
			return found;
		}
		function applyBankDetails(bankId) {
			var bank = getBankById(bankId);
			$('#payment_bank_name').val(bank ? bank.bank_name : '');
			$('#payment_ifsc').val(bank ? bank.ifsc : '');
			$('#payment_bank_branch').val(bank ? bank.bank_branch : '');
		}
		function paymentModeRequiresBank(mode) {
			mode = String(mode || '').toUpperCase();
			return $.inArray(mode, ['CHEQUE', 'NEFT', 'RTGS', 'IMPS', 'DD']) >= 0;
		}
		function clearBankSelection() {
			setSelectVal($('#company_bank_id'), '');
			applyBankDetails('');
		}
		function togglePaymentBankRequirement() {
			var mode = String(getSelectVal($('#payment_mode')) || '').toUpperCase();
			var required = paymentModeRequiresBank(mode);
			var showBank = mode !== '' && mode !== 'CASH';

			$('#payment_bank_account_wrap').toggle(showBank);
			$('#bank_details_row').toggle(showBank && !!getSelectVal($('#company_bank_id')));

			if (!showBank) {
				clearBankSelection();
			} else if (required && !getSelectVal($('#company_bank_id')) && bankOptions.length === 1) {
				var onlyBankId = String(bankOptions[0].bank_account_id);
				setSelectVal($('#company_bank_id'), onlyBankId);
				applyBankDetails(onlyBankId);
				$('#bank_details_row').show();
			}

			if (required) {
				$('#payment_bank_account_wrap').find('label.control-label').first().html('Company Bank Account <span style="color:red;">*</span> :');
			} else {
				$('#payment_bank_account_wrap').find('label.control-label').first().html('Company Bank Account :');
			}
		}
		function moneyNum(v) {
			var n = parseFloat(v);
			return isNaN(n) ? 0 : Math.round(n * 100) / 100;
		}
		function moneyFmt(v) {
			return moneyNum(v).toFixed(2);
		}
		function updateVendorHint(vendor) {
			vendor = vendor || {};
			var parts = [];
			if (vendor.payment_terms) parts.push('Terms: ' + vendor.payment_terms);
			var cd = parseInt(vendor.credit_days, 10) || 0;
			parts.push(cd > 0 ? ('Credit: ' + cd + ' day' + (cd === 1 ? '' : 's')) : 'Credit: not set (due on invoice date)');
			if (parseInt(vendor.tds_applicable, 10) === 1 && moneyNum(vendor.tds_rate) > 0) {
				parts.push('Vendor TDS: ' + moneyFmt(vendor.tds_rate) + '%');
			}
			$('#vendor_terms_hint').text(parts.join(' · '));
		}
		function buildTdsSelect(selected) {
			var $el = $('#tds_percent');
			if (!$el.length) return;
			var rate = moneyNum(selected);
			var found = false;
			var html = '';
			$.each(tdsPercentOptions || [], function(i, opt) {
				var optRate = moneyNum(opt.rate);
				if (optRate === rate) found = true;
				html += '<option value="' + escHtml(optRate) + '"' + (optRate === rate ? ' selected' : '') + '>' + escHtml(opt.label || (optRate + '%')) + '</option>';
			});
			if (!found && rate > 0) {
				html += '<option value="' + escHtml(rate) + '" selected>TDS ' + escHtml(moneyFmt(rate)) + '%</option>';
			}
			if ($el.data('select2')) $el.select2('destroy');
			$el.html(html);
			initSelect2($el, 'TDS %');
			setSelectVal($el, String(rate));
		}
		function addDaysDmy(dateStr, days) {
			var parts = String(dateStr || '').split('-');
			if (parts.length !== 3) return '';
			var d = parseInt(parts[0], 10);
			var m = parseInt(parts[1], 10) - 1;
			var y = parseInt(parts[2], 10);
			if (!d || m < 0 || !y) return '';
			var dt = new Date(y, m, d);
			if (isNaN(dt.getTime())) return '';
			dt.setDate(dt.getDate() + (parseInt(days, 10) || 0));
			var dd = ('0' + dt.getDate()).slice(-2);
			var mm = ('0' + (dt.getMonth() + 1)).slice(-2);
			return dd + '-' + mm + '-' + dt.getFullYear();
		}
		function refreshDueDate() {
			var invoiceDate = $.trim($('#vendor_invoice_date').val() || '');
			var days = Math.max(0, parseInt(vendorCreditDays, 10) || 0);
			var due = invoiceDate ? addDaysDmy(invoiceDate, days) : '';
			$('#due_date').val(due);
			var hint;
			if (!getSelectVal($('#vendor_id'))) {
				hint = 'Auto: vendor invoice date + vendor credit days';
			} else if (days > 0) {
				// Invoice date counts as day 1 → 15 credit days = due on the 16th day.
				hint = 'Due on day ' + (days + 1) + ': invoice date + ' + days + ' credit day' + (days === 1 ? '' : 's') + ' (from vendor)';
			} else {
				hint = 'Auto: no credit days on vendor — due on invoice date';
			}
			$('#due_date_hint').text(hint);
		}
		function setTotalsDisplay(totals) {
			totals = totals || {};
			var subtotal = moneyNum(totals.subtotal);
			var gstTotal = moneyNum(totals.gst_total);
			var grandTotal = moneyNum(totals.grand_total);
			var tdsAmount = moneyNum(totals.tds_amount);
			var netPayable = moneyNum(totals.net_payable);
			$('#bill_subtotal').val(subtotal);
			$('#bill_gst_total').val(gstTotal);
			$('#bill_grand_total').val(grandTotal);
			$('#bill_tds_amount').val(tdsAmount);
			$('#bill_net_payable').val(netPayable);
			$('#bill_subtotal_display').text(moneyFmt(subtotal));
			$('#bill_gst_total_display').text(moneyFmt(gstTotal));
			$('#bill_grand_total_display').text(moneyFmt(grandTotal));
			$('#bill_tds_amount_display').text(moneyFmt(tdsAmount));
			$('#bill_net_payable_display').text(moneyFmt(netPayable));
		}
		function calcLocalTotals(rows, tdsPercent) {
			var subtotal = 0, gstTotal = 0, grandTotal = 0, taxable = 0;
			$.each(rows || [], function(i, row) {
				var freight = moneyNum(row.freight_amount);
				var gst = moneyNum(row.gst_amount);
				var total = moneyNum(row.total_amount);
				if (!total) total = freight + gst;
				var tax = moneyNum(row.taxable_amount);
				if (!tax) tax = freight;
				subtotal += freight;
				gstTotal += gst;
				grandTotal += total;
				taxable += tax;
			});
			tdsPercent = moneyNum(tdsPercent);
			var tdsAmount = (tdsPercent > 0 && taxable > 0) ? moneyNum(taxable * tdsPercent / 100) : 0;
			var netPayable = moneyNum(grandTotal - tdsAmount);
			if (netPayable < 0) netPayable = 0;
			return {
				subtotal: moneyNum(subtotal),
				gst_total: moneyNum(gstTotal),
				grand_total: moneyNum(grandTotal),
				taxable_base: moneyNum(taxable),
				tds_percent: tdsPercent,
				tds_amount: tdsAmount,
				net_payable: netPayable
			};
		}
		function renderGcnSummary(rows, totals) {
			rows = rows || [];
			var show = rows.length > 0;
			$('#gcn_summary_wrap').toggleClass('is-visible', show);
			if (!show) {
				$('#gcn_summary_wrap').hide();
			} else {
				$('#gcn_summary_wrap').show();
			}
			var $body = $('#gcn_summary_body').empty();
			$('#gcn_summary_foot').toggle(show);
			if (!show) {
				$body.html('<tr><td colspan="8" class="ebill-empty">No GCNs selected.</td></tr>');
				setTotalsDisplay({ subtotal: 0, gst_total: 0, grand_total: 0, tds_amount: 0, net_payable: 0 });
				return;
			}
			$.each(rows, function(i, row) {
				var key = String(row.gcn_key || row.key || '');
				var amt = gcnAmountFor(key);
				var calc = gcnRowCalc(key);
				var $tr = $('<tr class="ebill-gcn-row"></tr>').attr('data-key', key);
				$tr.append('<td class="c-no">' + escHtml(row.gcn_no || row.grn_no || '') + '</td>');
				$tr.append('<td class="c-date">' + escHtml(row.gcn_date || row.grn_date || '') + '</td>');
				$tr.append('<td>' + escHtml(row.consignor || '') + '</td>');
				$tr.append('<td>' + escHtml(row.consignee || '') + '</td>');
				$tr.append('<td>' + escHtml(row.route_label || '') + '</td>');
				$tr.append('<td class="c-input"><input type="text" inputmode="decimal" class="form-control ebill-freight" autocomplete="off" placeholder="0.00" value="' + escHtml(amt.freight ? String(amt.freight) : '0') + '"></td>');
				$tr.append('<td class="c-input"><div class="ebill-gst-inline">' + gstSelectHtml(amt.gst_percent) +
					'<span class="ebill-gst-amt">' + moneyFmt(calc.gst) + '</span></div></td>');
				$tr.append('<td class="num ebill-total-cell">' + moneyFmt(calc.total) + '</td>');
				$body.append($tr);
			});
			recalcBillTotals();
		}
		function gcnAmountFor(key) {
			if (!gcnAmounts[key]) gcnAmounts[key] = { freight: 0, gst_percent: 0 };
			return gcnAmounts[key];
		}
		function gcnRowCalc(key) {
			var a = gcnAmountFor(key);
			var freight = Math.max(0, moneyNum(a.freight));
			var pct = Math.max(0, moneyNum(a.gst_percent));
			var gst = moneyNum(freight * pct / 100);
			return { freight: freight, pct: pct, gst: gst, total: moneyNum(freight + gst) };
		}
		function gstSelectHtml(selected) {
			var rate = moneyNum(selected);
			var found = false;
			var html = '<select class="ebill-gst-pct" title="GST %">';
			$.each(gstPercentOptions || [], function(i, opt) {
				var r = moneyNum(opt.rate);
				if (r === rate) found = true;
				html += '<option value="' + r + '"' + (r === rate ? ' selected' : '') + '>' + escHtml(r > 0 ? (r + '%') : '0%') + '</option>';
			});
			if (!found) {
				html += '<option value="' + rate + '" selected>' + escHtml(rate + '%') + '</option>';
			}
			return html + '</select>';
		}
		function selectedGcnRowsWithAmounts() {
			return $.map(selectedGcnDetails || [], function(row) {
				var key = String(row.gcn_key || row.key || '');
				var c = gcnRowCalc(key);
				return $.extend({}, row, {
					freight_amount: c.freight,
					taxable_amount: c.freight,
					gst_percent: c.pct,
					gst_amount: c.gst,
					total_amount: c.total
				});
			});
		}
		function recalcBillTotals() {
			var rows = selectedGcnRowsWithAmounts();
			var totals = calcLocalTotals(rows, getSelectVal($('#tds_percent')));
			setTotalsDisplay(totals);
			$('#gcn_foot_freight').text(moneyFmt(totals.subtotal));
			$('#gcn_foot_gst').text(moneyFmt(totals.gst_total));
			$('#gcn_foot_total').text(moneyFmt(totals.grand_total));
		}
		function updateGcnRowDisplay($tr) {
			var c = gcnRowCalc(String($tr.attr('data-key') || ''));
			$tr.find('.ebill-gst-amt').text(moneyFmt(c.gst));
			$tr.find('.ebill-total-cell').text(moneyFmt(c.total));
		}
		function refreshSelectedGcnDetails(done) {
			var keys = getMultiSelectVal($('#gcn_keys'));
			if (!keys.length) {
				selectedGcnDetails = [];
				renderGcnSummary([], null);
				if (done) done();
				return;
			}
			var seq = ++gcnDetailsRequestSeq;
			var data = {
				cmd: 'fetch_gcn_details',
				tds_percent: getSelectVal($('#tds_percent')) || 0
			};
			// Use gcn_keys[] so PHP receives a full array (not only the last value).
			$.each(keys, function(i, key) {
				data['gcn_keys[' + i + ']'] = key;
			});
			$.ajax({
				url: 'expense_bill_data.php',
				type: 'GET',
				dataType: 'json',
				data: data
			}).done(function(r) {
				if (seq !== gcnDetailsRequestSeq) return;
				selectedGcnDetails = (r && r.status === 0 && r.gcns) ? r.gcns : [];
				renderGcnSummary(selectedGcnDetails, null);
				if (done) done();
			}).fail(function() {
				if (seq !== gcnDetailsRequestSeq) return;
				selectedGcnDetails = [];
				renderGcnSummary([], null);
				if (done) done();
			});
		}
		function applyVendorDefaultTds(force) {
			var rate = moneyNum(vendorDefaultTdsPercent);
			if (!force && moneyNum(getSelectVal($('#tds_percent'))) > 0) return;
			buildTdsSelect(rate);
		}
		function loadGcns(selected, done) {
			$.getJSON('expense_bill_data.php', { cmd: 'fetch_gcns' }, function(r) {
				gcnOptions = (r && r.status === 0 && r.gcns) ? r.gcns : [];
				buildGcnSelect(selected || []);
				if (done) done();
			}).fail(function() {
				gcnOptions = [];
				buildGcnSelect(selected || []);
				if (done) done();
			});
		}
		function isImageAttachment(att) {
			var mime = String((att && att.mime_type) || '').toLowerCase();
			var name = String((att && (att.original_name || att.stored_name)) || '').toLowerCase();
			if (mime.indexOf('image/') === 0) return true;
			return /\.(jpe?g|png|gif|webp)$/i.test(name);
		}
		function renderExistingAttachments(list) {
			var $grid = $('#existing_attachments').empty();
			removeAttachmentIds = [];
			var items = [];
			$.each(list || [], function(i, att) {
				var id = parseInt(att.attachment_id, 10) || 0;
				if (!id) return;
				items.push(att);
				var label = att.original_name || att.stored_name || ('File ' + (i + 1));
				var $card = $('<div class="ebill-attach-card" data-id="' + id + '"></div>');
				var $icon = $('<div class="ebill-attach-icon"></div>');
				if (att.url && isImageAttachment(att)) {
					$icon.append($('<img>').attr('src', att.url).attr('alt', label));
				} else {
					$icon.append('<i class="fa fa-file-o" aria-hidden="true"></i>');
				}
				$card.append($icon);
				if (att.url) {
					$card.append('<p><a href="' + escHtml(att.url) + '" target="_blank" rel="noopener">' + escHtml(label) + '</a></p>');
				} else {
					$card.append('<p>' + escHtml(label) + '</p>');
				}
				$card.append('<button type="button" class="btn btn-danger btn-xs btn-remove-att">Remove</button>');
				$grid.append($card);
			});
			$('#existing_attachments_label').toggle(items.length > 0);
			if (!items.length) {
				$grid.html('<div class="ebill-attach-empty">No attachments uploaded.</div>');
			}
		}
		function fileLabel(files) {
			if (!files || !files.length) return '';
			if (files.length === 1) return files[0].name;
			return files.length + ' files selected';
		}
		function setInputFiles($input, files) {
			if (typeof DataTransfer !== 'undefined' && files && files.length) {
				var dt = new DataTransfer();
				for (var i = 0; i < files.length; i++) {
					dt.items.add(files[i]);
				}
				$input[0].files = dt.files;
			}
		}
		function initEwUploadMulti($wrap) {
			var $input = $wrap.find('.ew-upload-image__input');
			var $zone = $wrap.find('.ew-upload-image__zone');
			var $filename = $wrap.find('.ew-upload-image__filename');

			function applyFiles(fileList) {
				if (!fileList || !fileList.length) {
					$filename.text('').attr('title', '');
					$wrap.removeClass('has-file');
					return;
				}
				var label = fileLabel(fileList);
				$filename.text(label).attr('title', label);
				$wrap.addClass('has-file');
			}

			$zone.on('click', function(e) {
				if ($(e.target).is('input')) return;
				$input.trigger('click');
			});
			$zone.on('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					$input.trigger('click');
				}
			});
			$input.on('change', function() {
				applyFiles(this.files);
			});
			$zone.on('dragover dragenter', function(e) {
				e.preventDefault();
				$zone.addClass('is-dragover');
			});
			$zone.on('dragleave drop', function(e) {
				e.preventDefault();
				$zone.removeClass('is-dragover');
			});
			$zone.on('drop', function(e) {
				var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
				if (!files || !files.length) return;
				setInputFiles($input, files);
				applyFiles($input[0].files);
			});
		}
		function fillForm(data) {
			billId = parseInt(data.bill_id, 10) || 0;
			$('#bill_entry_no_display').val(data.bill_entry_no || '');
			$('#bill_date').val(data.bill_date || '');
			$('#bill_type').val('transaction');
			buildVendorSelect(data.vendor_id || '');
			$('#vendor_invoice_no').val(data.vendor_invoice_no || '');
			$('#vendor_invoice_date').val(data.vendor_invoice_date || '');
			$('#invoice_received_date').val(data.invoice_received_date || '');
			$('#narration').val(data.narration || '');
			vendorDefaultTdsPercent = moneyNum(data.default_tds_percent || data.tds_rate || 0);
			vendorCreditDays = parseInt(data.credit_days, 10) || 0;
			gcnAmounts = {};
			$.each(data.gcns || [], function(i, g) {
				var k = String(g.gcn_key || g.key || '');
				if (k) gcnAmounts[k] = { freight: moneyNum(g.freight_amount), gst_percent: moneyNum(g.gst_percent) };
			});
			updateVendorHint({
				payment_terms: data.payment_terms || '',
				credit_days: data.credit_days || 0,
				tds_applicable: data.tds_applicable || 0,
				tds_rate: data.tds_rate || 0
			});
			buildTdsSelect(moneyNum(data.tds_percent));
			refreshDueDate();
			initPaymentModeSelect(data.payment_mode || '');
			buildBankSelect(data.company_bank_id || '');
			if (data.company_bank_id) {
				setSelectVal($('#company_bank_id'), String(data.company_bank_id));
				applyBankDetails(data.company_bank_id);
			} else {
				applyBankDetails('');
			}
			togglePaymentBankRequirement();
			loadGcns(data.gcn_keys || [], function() {
				setMultiSelectVal($('#gcn_keys'), data.gcn_keys || []);
				if (data.gcns && data.gcns.length) {
					selectedGcnDetails = data.gcns;
					renderGcnSummary(data.gcns, null);
				} else {
					refreshSelectedGcnDetails();
				}
			});
			renderExistingAttachments(data.attachments || []);
		}
		function collectFormData() {
			var fd = new FormData();
			fd.append('cmd', 'save');
			fd.append('bill_id', billId || 0);
			fd.append('bill_date', $('#bill_date').val() || '');
			fd.append('bill_type', 'transaction');
			fd.append('vendor_id', getSelectVal($('#vendor_id')) || 0);
			var gcnKeys = getMultiSelectVal($('#gcn_keys'));
			$.each(gcnKeys, function(i, key) {
				fd.append('gcn_keys[]', key);
			});
			fd.append('vendor_invoice_no', $('#vendor_invoice_no').val() || '');
			fd.append('vendor_invoice_date', $('#vendor_invoice_date').val() || '');
			fd.append('invoice_received_date', $('#invoice_received_date').val() || '');
			fd.append('due_date', $('#due_date').val() || '');
			fd.append('due_date_overridden', 0);
			fd.append('tds_percent', getSelectVal($('#tds_percent')) || 0);
			var amounts = {};
			$.each(selectedGcnDetails || [], function(i, row) {
				var k = String(row.gcn_key || row.key || '');
				if (!k) return;
				var c = gcnRowCalc(k);
				amounts[k] = { freight: c.freight, gst_percent: c.pct };
			});
			fd.append('gcn_amounts', JSON.stringify(amounts));
			fd.append('narration', $('#narration').val() || '');
			fd.append('payment_mode', getSelectVal($('#payment_mode')) || '');
			fd.append('company_bank_id', getSelectVal($('#company_bank_id')) || 0);
			fd.append('payment_bank_name', $('#payment_bank_name').val() || '');
			fd.append('payment_ifsc', $('#payment_ifsc').val() || '');
			fd.append('payment_bank_branch', $('#payment_bank_branch').val() || '');
			if (removeAttachmentIds.length) {
				fd.append('remove_attachment_ids', removeAttachmentIds.join(','));
			}
			var files = $('#attachments')[0].files;
			if (files && files.length) {
				for (var i = 0; i < files.length; i++) {
					fd.append('attachments[]', files[i]);
				}
			}
			return fd;
		}

		$(function() {
			buildVendorSelect('');
			buildGcnSelect([]);
			buildTdsSelect(0);
			buildBankSelect('');
			initPaymentModeSelect('');
			togglePaymentBankRequirement();
			refreshDueDate();
			loadGcns([]);
			initEwUploadMulti($('#bill_attach_drop_wrap'));

			if (billId > 0) {
				$.getJSON('expense_bill_data.php', { cmd: 'fetch', bill_id: billId }, function(r) {
					if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Could not load bill.');
						return;
					}
					fillForm(r);
				}).fail(function() {
					alert('Could not load bill record.');
				});
			} else {
				$('#bill_entry_no_display').val(nextBillNoPreview);
			}

			$('#payment_mode').on('change', function() {
				togglePaymentBankRequirement();
				syncSelectTitle($(this));
			});
			$('#company_bank_id').on('change', function() {
				var bankId = getSelectVal($(this)) || '';
				applyBankDetails(bankId);
				$('#bank_details_row').toggle(!!bankId && String(getSelectVal($('#payment_mode')) || '').toUpperCase() !== 'CASH');
				syncSelectTitle($(this));
			});
			$('#vendor_id').on('change', function() {
				var vendorId = getSelectVal($(this)) || 0;
				syncSelectTitle($(this));
				if (!vendorId) {
					vendorDefaultTdsPercent = 0;
					vendorCreditDays = 0;
					updateVendorHint({});
					applyVendorDefaultTds(true);
					refreshDueDate();
					return;
				}
				$.getJSON('expense_bill_data.php', { cmd: 'vendor_info', vendor_id: vendorId }, function(r) {
					if (!r || r.status !== 0) return;
					var vendor = r.vendor || {};
					vendorDefaultTdsPercent = moneyNum(vendor.default_tds_percent || 0);
					vendorCreditDays = parseInt(vendor.credit_days, 10) || 0;
					updateVendorHint(vendor);
					applyVendorDefaultTds(true);
					refreshDueDate();
					recalcBillTotals();
				});
			});
			$('#vendor_invoice_date').on('change blur', function() {
				refreshDueDate();
			});
			$(document).on('change', '#gcn_keys', function() {
				refreshSelectedGcnDetails();
			});
			$('#tds_percent').on('change', function() {
				syncSelectTitle($(this));
				recalcBillTotals();
			});
			$(document).on('focus', '.ebill-freight', function() {
				var el = this;
				setTimeout(function() { try { el.select(); } catch (e) {} }, 0);
			});
			$(document).on('input', '.ebill-freight', function() {
				var v = String($(this).val() || '').replace(/[^0-9.]/g, '');
				var dot = v.indexOf('.');
				if (dot >= 0) v = v.slice(0, dot + 1) + v.slice(dot + 1).replace(/\./g, '').slice(0, 2);
				if (v !== $(this).val()) $(this).val(v);
				var $tr = $(this).closest('tr.ebill-gcn-row');
				gcnAmountFor(String($tr.attr('data-key') || '')).freight = moneyNum(v);
				$(this).toggleClass('is-invalid', moneyNum(v) <= 0);
				updateGcnRowDisplay($tr);
				recalcBillTotals();
			});
			$(document).on('blur', '.ebill-freight', function() {
				if ($.trim($(this).val()) === '') $(this).val('0');
			});
			$(document).on('change', '.ebill-gst-pct', function() {
				var $tr = $(this).closest('tr.ebill-gcn-row');
				gcnAmountFor(String($tr.attr('data-key') || '')).gst_percent = moneyNum($(this).val());
				updateGcnRowDisplay($tr);
				recalcBillTotals();
			});
			$(document).on('click', '.btn-remove-att', function() {
				var $card = $(this).closest('.ebill-attach-card');
				var id = parseInt($card.data('id'), 10) || 0;
				if (!id) return;
				if (!confirm('Remove this attachment?')) return;
				removeAttachmentIds.push(id);
				$card.remove();
				if (!$('#existing_attachments .ebill-attach-card').length) {
					$('#existing_attachments_label').hide();
					$('#existing_attachments').html('<div class="ebill-attach-empty">No attachments uploaded.</div>');
				}
			});

			$('#btn_save_bill').on('click', function() {
				var $btn = $(this);
				if ($btn.prop('disabled')) return;
				if (!$.trim($('#bill_date').val() || '')) {
					alert('Please enter bill date.');
					return;
				}
				if (!getSelectVal($('#vendor_id'))) {
					alert('Please select vendor.');
					return;
				}
				if (getMultiSelectVal($('#gcn_keys')).length < 1) {
					alert('Please select at least one GCN no. & date.');
					return;
				}
				var missingFreight = null;
				$.each(selectedGcnDetails || [], function(i, row) {
					if (gcnRowCalc(String(row.gcn_key || row.key || '')).freight <= 0) {
						missingFreight = row.gcn_no || row.grn_no || '';
						return false;
					}
				});
				if (missingFreight !== null) {
					alert('Please enter freight amount for GCN ' + missingFreight + '.');
					$('#gcn_summary_body tr.ebill-gcn-row').each(function(i) {
						if (gcnRowCalc(String($(this).attr('data-key') || '')).freight <= 0) {
							$(this).find('.ebill-freight').addClass('is-invalid').trigger('focus');
							return false;
						}
					});
					return;
				}
				if (!$.trim($('#vendor_invoice_no').val() || '')) {
					alert('Please enter vendor invoice/memo no.');
					return;
				}
				if (!$.trim($('#vendor_invoice_date').val() || '')) {
					alert('Please enter vendor invoice/memo date.');
					return;
				}
				if (!$.trim($('#invoice_received_date').val() || '')) {
					alert('Please enter invoice/memo received date.');
					return;
				}
				if (!$.trim($('#due_date').val() || '')) {
					alert('Please enter due date.');
					return;
				}
				var paymentMode = String(getSelectVal($('#payment_mode')) || '').toUpperCase();
				if (!paymentMode) {
					alert('Please select payment mode.');
					return;
				}
				if (paymentModeRequiresBank(paymentMode) && !getSelectVal($('#company_bank_id'))) {
					alert('Please select company bank account for this payment mode.');
					return;
				}

				$btn.prop('disabled', true).text('Saving…');
				$.ajax({
					url: 'expense_bill_data.php',
					type: 'POST',
					data: collectFormData(),
					processData: false,
					contentType: false,
					dataType: 'json'
				}).done(function(r) {
					if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Save failed.');
						$btn.prop('disabled', false).text('Save');
						return;
					}
					window.location.href = 'expense_bill_list.php';
				}).fail(function() {
					alert('Save request failed.');
					$btn.prop('disabled', false).text('Save');
				});
			});
		});
	</script>
</body>

</html>
