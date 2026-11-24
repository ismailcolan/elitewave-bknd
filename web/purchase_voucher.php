<?php
require_once('include/connect.php');
if (!defined('EW_PURCHASE_VOUCHER_LIVE') || !EW_PURCHASE_VOUCHER_LIVE) {
	header('Location: expense_gcn_list.php');
	exit;
}
require_once('include/function.php');
require_once('include/purchase_voucher_helpers.php');

purchase_voucher_ensure_schema($conn);
expense_require_admin();

$c_date = date('d-m-Y');
$preselect_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($preselect_id <= 0 && empty($_GET['create'])) {
	header('Location:purchase_voucher_list.php');
	exit;
}
$is_create = ($preselect_id <= 0 && !empty($_GET['create']));
$preview_voucher_no = purchase_voucher_preview_next_no($conn);
$vendor_rows = expense_gcn_vendor_options($conn);
$category_rows = expense_gcn_category_options($conn);
$tds_percent_options = purchase_voucher_tds_percent_options($conn);
$gst_percent_options = purchase_voucher_gst_percent_options();
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
		.ew-page-v2 .ew-form-grid--pv {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}

		.ew-page-v2 .ew-form-grid--pv .span-3 {
			grid-column: span 3;
		}

		@media (max-width: 991px) {
			.ew-page-v2 .ew-form-grid--pv {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}

			.ew-page-v2 .ew-form-grid--pv .span-3 {
				grid-column: span 2;
			}
		}

		@media (max-width: 640px) {
			.ew-page-v2 .ew-form-grid--pv {
				grid-template-columns: 1fr;
			}

			.ew-page-v2 .ew-form-grid--pv .span-3 {
				grid-column: span 1;
			}
		}

		#voucher_no_display {
			font-weight: 600;
		}

		.pv-lines-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			margin-bottom: 8px;
		}

		.pv-lines-wrap {
			overflow-x: auto;
		}

		.pv-lines th,
		.pv-lines td {
			vertical-align: middle;
			font-size: 12px;
			min-width: 90px;
		}

		.pv-lines .pv-col-action {
			width: 48px;
			min-width: 48px;
			text-align: center;
		}

		.pv-lines input.form-control,
		.pv-lines select.form-control {
			height: 32px;
			font-size: 12px;
		}

		.pv-lines .line-gst-amt,
		.pv-lines .line-total {
			background: #F3F7FB;
		}

		.pv-totals {
			margin-top: 12px;
			max-width: 420px;
			margin-left: auto;
		}

		.pv-totals-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 6px 0;
			font-size: 13px;
			border-bottom: 1px solid var(--ew-border-light, #E9ECF0);
		}

		.pv-totals-row strong {
			font-variant-numeric: tabular-nums;
		}

		.pv-totals-row.is-net {
			font-weight: 800;
			font-size: 14px;
			border-bottom: 0;
			padding-top: 10px;
		}

		.pv-attach-grid {
			display: flex;
			flex-wrap: wrap;
			gap: 14px;
			margin-top: 12px;
		}

		.pv-attach-card {
			width: 160px;
			flex: 0 0 160px;
			border: 1px solid var(--ew-border-light, #E9ECF0);
			border-radius: 8px;
			padding: 10px;
			background: #FAFBFC;
			text-align: center;
		}

		.pv-attach-card img {
			max-width: 100%;
			max-height: 72px;
			object-fit: contain;
			border-radius: 4px;
		}

		.pv-form-footer {
			justify-content: space-between;
			align-items: center;
			gap: 16px;
			padding: 14px 24px 22px !important;
		}

		.ew-form-grid--bank-inline {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 20px 16px;
		}

		@media (max-width: 640px) {
			.ew-form-grid--bank-inline {
				grid-template-columns: 1fr;
			}
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
								<a href="purchase_voucher_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php echo $preselect_id > 0 ? 'Edit Purchase Voucher' : 'Add Purchase Voucher'; ?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="purchase_voucher_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-form-body">
								<form id="purchase_voucher_form" enctype="multipart/form-data">
									<input type="hidden" id="purchase_voucher_id" name="purchase_voucher_id" value="<?php echo (int) $preselect_id; ?>">
									<div class="ew-form-grid ew-form-grid--pv">
										<div class="ew-section-label">Voucher Details</div>

										<div class="ew-field">
											<label class="control-label">Voucher No. :</label>
											<input type="text" id="voucher_no_display" class="form-control" readonly value="<?php echo $preselect_id > 0 ? '' : htmlspecialchars($preview_voucher_no); ?>">
										</div>
										<div class="ew-field">
											<label class="control-label">Voucher Date <span style="color:red;">*</span> :</label>
											<?php echo ew_date_input(array(
												'id' => 'voucher_date',
												'name' => 'voucher_date',
												'value' => $c_date,
												'required' => true,
											)); ?>
										</div>
										<div class="ew-field">
											<label class="control-label">Vendor / Supplier <span style="color:red;">*</span> :</label>
											<div class="egen-select-wrap">
												<select id="vendor_id" name="vendor_id" class="form-control egen-select"></select>
											</div>
										</div>

										<div class="ew-field">
											<label class="control-label">Supplier Invoice No. <span style="color:red;">*</span> :</label>
											<input type="text" id="supplier_invoice_no" name="supplier_invoice_no" class="form-control" maxlength="100" autocomplete="off">
										</div>
										<div class="ew-field">
											<label class="control-label">Supplier Invoice Date <span style="color:red;">*</span> :</label>
											<?php echo ew_date_input(array(
												'id' => 'supplier_invoice_date',
												'name' => 'supplier_invoice_date',
												'value' => '',
												'required' => true,
											)); ?>
										</div>
										<div class="ew-field">
											<label class="control-label">Invoice Received Date :</label>
											<?php echo ew_date_input(array(
												'id' => 'invoice_received_date',
												'name' => 'invoice_received_date',
												'value' => $c_date,
											)); ?>
										</div>

										<div class="ew-field span-3">
											<div class="pv-lines-head">
												<label class="control-label" style="margin:0;">Purchase Lines</label>
												<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_add_line"><i class="fa fa-plus"></i> Add line</button>
											</div>
											<div class="pv-lines-wrap">
												<table class="table table-bordered pv-lines" id="pv_lines_table">
													<thead>
														<tr>
															<th>Expense Type <span style="color:red;">*</span></th>
															<th>Item / Description <span style="color:red;">*</span></th>
															<th>HSN/SAC</th>
															<th class="num">Qty</th>
															<th>Unit</th>
															<th class="num">Rate</th>
															<th class="num">Taxable <span style="color:red;">*</span></th>
															<th class="num">GST %</th>
															<th class="num">GST Amt</th>
															<th class="num">Line Total</th>
															<th class="pv-col-action"></th>
														</tr>
													</thead>
													<tbody id="pv_lines_body"></tbody>
												</table>
											</div>
											<div class="pv-totals">
												<div class="pv-totals-row"><span>Subtotal (taxable)</span><strong id="pv_subtotal">0.00</strong></div>
												<div class="pv-totals-row"><span>GST Total</span><strong id="pv_gst_total">0.00</strong></div>
												<div class="pv-totals-row"><span>Grand Total</span><strong id="pv_grand_total">0.00</strong></div>
												<div class="pv-totals-row">
													<span>TDS</span>
													<div style="display:flex;align-items:center;gap:8px;">
														<select id="tds_percent" name="tds_percent" class="form-control" style="width:140px;height:32px;">
															<?php foreach ($tds_percent_options as $opt) { ?>
																<option value="<?php echo htmlspecialchars((string) $opt['rate']); ?>"><?php echo htmlspecialchars($opt['label']); ?></option>
															<?php } ?>
														</select>
														<strong id="pv_tds_amount">0.00</strong>
													</div>
												</div>
												<div class="pv-totals-row">
													<span>Other deduction</span>
													<input type="text" id="other_deduction" name="other_deduction" class="form-control" style="width:120px;height:32px;text-align:right;" value="0">
												</div>
												<div class="pv-totals-row is-net"><span>Net Payable</span><strong id="pv_net_payable">0.00</strong></div>
											</div>
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
												<a href="company_bank.php" class="ew-btn-v2 ew-btn-v2-outline btn-add-inline">Manage</a>
											</div>
										</div>

										<div class="ew-field span-3" id="bank_details_row" style="display:none;">
											<div class="ew-form-grid--bank-inline">
												<div class="ew-field">
													<label class="control-label">Bank Name :</label>
													<input type="text" id="payment_bank_name" name="payment_bank_name" class="form-control" readonly>
												</div>
												<div class="ew-field">
													<label class="control-label">IFSC :</label>
													<input type="text" id="payment_ifsc" name="payment_ifsc" class="form-control" readonly>
												</div>
												<div class="ew-field">
													<label class="control-label">Branch :</label>
													<input type="text" id="payment_bank_branch" name="payment_bank_branch" class="form-control" readonly>
												</div>
											</div>
										</div>

										<div class="ew-field span-3">
											<label class="control-label">Bill / Invoice Images &amp; Documents :</label>
											<div class="ew-upload-image ew-upload-image--multi" id="pv_attach_drop_wrap">
												<div class="ew-upload-image__zone" tabindex="0" role="button">
													<input type="file" id="attachments" name="attachments[]" class="ew-upload-image__input" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,image/jpeg,image/png,application/pdf">
													<div class="ew-upload-image__body">
														<i class="fa fa-cloud-upload"></i>
														<span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
														<span class="ew-upload-image__meta">Images, PDF, Word, Excel · max 10 MB each</span>
														<span class="ew-upload-image__filename pv-attach-pending-name"></span>
														<span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
													</div>
												</div>
											</div>
											<div class="ew-section-label" id="existing_attachments_label" style="margin-top:16px;display:none;">Existing attachments</div>
											<div class="pv-attach-grid" id="existing_attachments"></div>
										</div>
									</div>
								</form>
							</div>
							<div class="ew-form-footer pv-form-footer">
								<div>
									<strong>Net Payable:</strong> <span id="pv_net_footer">0.00</span>
								</div>
								<div>
									<a href="purchase_voucher_list.php" class="ew-btn-v2 ew-btn-v2-outline">Cancel</a>
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btn_save_pv">Save</button>
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
		var purchaseVoucherId = <?php echo (int) $preselect_id; ?>;
		var nextVoucherPreview = <?php echo json_encode($preview_voucher_no, JSON_UNESCAPED_UNICODE); ?>;
		var vendorOptions = <?php echo json_encode($vendor_rows, JSON_UNESCAPED_UNICODE); ?>;
		var categoryOptions = <?php echo json_encode($category_rows, JSON_UNESCAPED_UNICODE); ?>;
		var bankOptions = <?php echo json_encode($bank_rows, JSON_UNESCAPED_UNICODE); ?>;
		var gstRates = <?php echo json_encode($gst_percent_options, JSON_UNESCAPED_UNICODE); ?>;
		var pendingAttachFiles = [];
		var removeAttachmentIds = [];

		function escHtml(v) {
			if (v == null) return '';
			return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}
		function moneyNum(v) {
			if (v === '' || v == null) return 0;
			return parseFloat(String(v).replace(/,/g, '')) || 0;
		}
		function formatMoney(v) {
			return moneyNum(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
		}
		function getSelectVal($el) {
			if (!$el || !$el.length) return '';
			var val = $el.val();
			if ((val === null || val === '') && $el.data('select2')) {
				val = $el.select2('val');
			}
			return val ? String(val) : '';
		}
		function setSelectVal($el, value) {
			if (!$el || !$el.length) return;
			if ($el.data('select2')) $el.select2('val', value || '');
			else $el.val(value || '');
		}
		function initSelect2($el, placeholder) {
			if (!$el || !$el.length) return;
			if ($el.data('select2')) $el.select2('destroy');
			$el.select2({ width: '100%', placeholder: placeholder || 'Select', allowClear: true, minimumResultsForSearch: 0 });
		}
		function fillSelectOptions($el, rows, valueKey, labelKey, selected, allowEmpty) {
			var html = allowEmpty === false ? '' : '<option value=""></option>';
			$.each(rows || [], function(i, row) {
				var val = row[valueKey];
				var label = row[labelKey] || row.label || val;
				html += '<option value="' + escHtml(val) + '"' + (String(selected) === String(val) ? ' selected' : '') + '>' + escHtml(label) + '</option>';
			});
			if ($el.data('select2')) $el.select2('destroy');
			$el.html(html);
		}
		function gstOptionsHtml(selected) {
			var html = '';
			$.each(gstRates || [], function(i, rate) {
				var r = moneyNum(rate).toFixed(2);
				html += '<option value="' + r + '"' + (moneyNum(selected).toFixed(2) === r ? ' selected' : '') + '>' + moneyNum(rate) + '%</option>';
			});
			return html;
		}
		function buildVendorSelect(selected) {
			var $el = $('#vendor_id');
			fillSelectOptions($el, vendorOptions, 'vendor_id', 'label', selected, true);
			initSelect2($el, 'Select vendor');
			setSelectVal($el, selected || '');
		}
		function buildBankSelect(selected) {
			var $el = $('#company_bank_id');
			fillSelectOptions($el, bankOptions, 'bank_account_id', 'label', selected, true);
			initSelect2($el, 'Select bank account');
			setSelectVal($el, selected || '');
		}
		function paymentModeRequiresBank(mode) {
			mode = String(mode || '').toUpperCase();
			return $.inArray(mode, ['CHEQUE', 'NEFT', 'RTGS', 'IMPS', 'DD']) >= 0;
		}
		function applyBankDetails(bankId) {
			bankId = String(bankId || '');
			var row = null;
			$.each(bankOptions || [], function(i, b) {
				if (String(b.bank_account_id) === bankId) row = b;
			});
			$('#payment_bank_name').val(row ? (row.bank_name || '') : '');
			$('#payment_ifsc').val(row ? (row.ifsc || '') : '');
			$('#payment_bank_branch').val(row ? (row.bank_branch || '') : '');
		}
		function togglePaymentBank() {
			var mode = String(getSelectVal($('#payment_mode')) || '').toUpperCase();
			var showBank = mode !== '' && mode !== 'CASH';
			$('#payment_bank_account_wrap').toggle(showBank);
			$('#bank_details_row').toggle(showBank && !!getSelectVal($('#company_bank_id')));
			if (!showBank) {
				setSelectVal($('#company_bank_id'), '');
				applyBankDetails('');
			}
			var req = paymentModeRequiresBank(mode);
			$('#payment_bank_account_wrap').find('label').first().html(req ? 'Company Bank Account <span style="color:red;">*</span> :' : 'Company Bank Account :');
		}

		function addLineRow(data) {
			data = data || {};
			var idx = 'ln_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
			var catOpts = '<option value=""></option>';
			$.each(categoryOptions || [], function(i, c) {
				var sel = String(data.category_id) === String(c.category_id) ? ' selected' : '';
				catOpts += '<option value="' + escHtml(c.category_id) + '"' + sel + '>' + escHtml(c.label || c.category_name) + '</option>';
			});
			var tr = '<tr class="pv-line-row" data-row="' + idx + '">';
			tr += '<td><select class="form-control line-category">' + catOpts + '</select></td>';
			tr += '<td><input type="text" class="form-control line-desc" value="' + escHtml(data.item_description || '') + '" maxlength="500"></td>';
			tr += '<td><input type="text" class="form-control line-hsn" value="' + escHtml(data.hsn_sac || '') + '" maxlength="20"></td>';
			tr += '<td><input type="text" class="form-control line-qty" value="' + escHtml(data.qty != null ? data.qty : '1') + '"></td>';
			tr += '<td><input type="text" class="form-control line-unit" value="' + escHtml(data.unit || '') + '" maxlength="20"></td>';
			tr += '<td><input type="text" class="form-control line-rate" value="' + escHtml(data.rate != null ? data.rate : '') + '"></td>';
			tr += '<td><input type="text" class="form-control line-taxable" value="' + escHtml(data.taxable_amount != null ? data.taxable_amount : '') + '"></td>';
			tr += '<td><select class="form-control line-gst-pct">' + gstOptionsHtml(data.gst_percent || 0) + '</select></td>';
			tr += '<td><input type="text" class="form-control line-gst-amt" readonly value="' + escHtml(data.gst_amount_fmt || '0.00') + '"></td>';
			tr += '<td><input type="text" class="form-control line-total" readonly value="' + escHtml(data.line_total_fmt || '0.00') + '"></td>';
			tr += '<td class="pv-col-action"><button type="button" class="btn btn-link text-danger btn-remove-line" title="Remove"><i class="fa fa-times"></i></button></td>';
			tr += '</tr>';
			$('#pv_lines_body').append(tr);
			var $cat = $('#pv_lines_body tr[data-row="' + idx + '"] .line-category');
			initSelect2($cat, 'Expense type');
		}

		function recalcLine($tr) {
			var qty = moneyNum($tr.find('.line-qty').val());
			var rate = moneyNum($tr.find('.line-rate').val());
			var taxable = moneyNum($tr.find('.line-taxable').val());
			if (taxable <= 0 && qty > 0 && rate > 0) {
				taxable = Math.round(qty * rate * 100) / 100;
				$tr.find('.line-taxable').val(taxable.toFixed(2));
			}
			var gstPct = moneyNum($tr.find('.line-gst-pct').val());
			var gstAmt = Math.round(taxable * gstPct / 100 * 100) / 100;
			var total = Math.round((taxable + gstAmt) * 100) / 100;
			$tr.find('.line-gst-amt').val(formatMoney(gstAmt));
			$tr.find('.line-total').val(formatMoney(total));
		}

		function recalcTotals() {
			var sub = 0, gst = 0, grand = 0;
			$('#pv_lines_body tr').each(function() {
				recalcLine($(this));
				var $tr = $(this);
				sub += moneyNum($tr.find('.line-taxable').val());
				gst += moneyNum($tr.find('.line-gst-amt').val());
				grand += moneyNum($tr.find('.line-total').val());
			});
			sub = Math.round(sub * 100) / 100;
			gst = Math.round(gst * 100) / 100;
			grand = Math.round(grand * 100) / 100;
			var tdsPct = moneyNum($('#tds_percent').val());
			var tdsAmt = Math.round(sub * tdsPct / 100 * 100) / 100;
			var other = Math.max(0, moneyNum($('#other_deduction').val()));
			var net = Math.round((grand - tdsAmt - other) * 100) / 100;
			$('#pv_subtotal').text(formatMoney(sub));
			$('#pv_gst_total').text(formatMoney(gst));
			$('#pv_grand_total').text(formatMoney(grand));
			$('#pv_tds_amount').text(formatMoney(tdsAmt));
			$('#pv_net_payable').text(formatMoney(net));
			$('#pv_net_footer').text(formatMoney(net));
		}

		function collectLines() {
			var lines = [];
			$('#pv_lines_body tr').each(function() {
				var $tr = $(this);
				recalcLine($tr);
				lines.push({
					category_id: getSelectVal($tr.find('.line-category')),
					item_description: $.trim($tr.find('.line-desc').val() || ''),
					hsn_sac: $.trim($tr.find('.line-hsn').val() || ''),
					qty: moneyNum($tr.find('.line-qty').val()) || 1,
					unit: $.trim($tr.find('.line-unit').val() || ''),
					rate: moneyNum($tr.find('.line-rate').val()),
					taxable_amount: moneyNum($tr.find('.line-taxable').val()),
					gst_percent: moneyNum($tr.find('.line-gst-pct').val()),
					gst_amount: moneyNum($tr.find('.line-gst-amt').val()),
					line_total: moneyNum($tr.find('.line-total').val())
				});
			});
			return lines;
		}

		function renderExistingAttachments(list) {
			list = list || [];
			if (!list.length) {
				$('#existing_attachments_label').hide();
				$('#existing_attachments').html('');
				return;
			}
			$('#existing_attachments_label').show();
			var html = '';
			$.each(list, function(i, att) {
				html += '<div class="pv-attach-card" data-id="' + escHtml(att.attachment_id) + '">';
				if (att.url && att.is_image) {
					html += '<a href="' + escHtml(att.url) + '" target="_blank" rel="noopener"><img src="' + escHtml(att.url) + '" alt=""></a>';
				} else {
					html += '<div style="height:72px;line-height:72px;"><i class="fa fa-file-o fa-2x"></i></div>';
				}
				html += '<p>' + escHtml(att.original_name || 'File') + '</p>';
				html += '<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-remove-att" style="font-size:11px;padding:2px 8px;">Remove</button>';
				html += '</div>';
			});
			$('#existing_attachments').html(html);
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
			if (!$wrap || !$wrap.length) return;
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

			$wrap.find('.ew-upload-image__browse').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$input.trigger('click');
			});
			$zone.on('click', function(e) {
				if ($(e.target).is('input') || $(e.target).closest('.ew-upload-image__browse').length) {
					return;
				}
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

		function collectFormData() {
			var fd = new FormData();
			fd.append('cmd', 'save');
			fd.append('purchase_voucher_id', purchaseVoucherId || 0);
			fd.append('voucher_date', $('#voucher_date').val() || '');
			fd.append('vendor_id', getSelectVal($('#vendor_id')));
			fd.append('supplier_invoice_no', $('#supplier_invoice_no').val() || '');
			fd.append('supplier_invoice_date', $('#supplier_invoice_date').val() || '');
			fd.append('invoice_received_date', $('#invoice_received_date').val() || '');
			fd.append('narration', $('#narration').val() || '');
			fd.append('tds_percent', $('#tds_percent').val() || '0');
			fd.append('other_deduction', $('#other_deduction').val() || '0');
			fd.append('payment_mode', getSelectVal($('#payment_mode')));
			fd.append('company_bank_id', getSelectVal($('#company_bank_id')));
			fd.append('payment_bank_name', $('#payment_bank_name').val() || '');
			fd.append('payment_ifsc', $('#payment_ifsc').val() || '');
			fd.append('payment_bank_branch', $('#payment_bank_branch').val() || '');
			fd.append('lines_json', JSON.stringify(collectLines()));
			if (removeAttachmentIds.length) {
				$.each(removeAttachmentIds, function(i, id) {
					fd.append('remove_attachment_ids[]', id);
				});
			}
			var $fileInput = $('#attachments')[0];
			if ($fileInput && $fileInput.files) {
				for (var i = 0; i < $fileInput.files.length; i++) {
					fd.append('attachments[]', $fileInput.files[i]);
				}
			}
			return fd;
		}

		function loadRecord() {
			if (purchaseVoucherId <= 0) {
				addLineRow({});
				recalcTotals();
				return;
			}
			$.getJSON('purchase_voucher_data.php', { cmd: 'fetch', purchase_voucher_id: purchaseVoucherId }, function(r) {
				if (!r || r.status !== 0) {
					alert(r && r.message ? r.message : 'Could not load voucher.');
					return;
				}
				$('#voucher_no_display').val(r.voucher_no || '');
				$('#voucher_date').val(r.voucher_date || '');
				buildVendorSelect(r.vendor_id);
				$('#supplier_invoice_no').val(r.supplier_invoice_no || '');
				$('#supplier_invoice_date').val(r.supplier_invoice_date || '');
				$('#invoice_received_date').val(r.invoice_received_date || '');
				$('#narration').val(r.narration || '');
				$('#tds_percent').val(moneyNum(r.tds_percent).toFixed(2));
				$('#other_deduction').val(r.other_deduction_raw != null ? r.other_deduction_raw : '0');
				setSelectVal($('#payment_mode'), r.payment_mode || '');
				togglePaymentBank();
				buildBankSelect(r.company_bank_id);
				applyBankDetails(r.company_bank_id);
				$('#pv_lines_body').empty();
				var lines = r.lines || [];
				if (!lines.length) addLineRow({});
				else $.each(lines, function(i, ln) { addLineRow(ln); });
				renderExistingAttachments(r.attachments);
				recalcTotals();
			});
		}

		$(function() {
			if (window.initEwDatepickers) window.initEwDatepickers();
			buildVendorSelect('');
			buildBankSelect('');
			initSelect2($('#payment_mode'), 'Payment mode');
			initEwUploadMulti($('#pv_attach_drop_wrap'));

			$('#btn_add_line').on('click', function() {
				addLineRow({});
				recalcTotals();
			});
			$(document).on('click', '.btn-remove-line', function() {
				if ($('#pv_lines_body tr').length <= 1) {
					alert('At least one line is required.');
					return;
				}
				$(this).closest('tr').remove();
				recalcTotals();
			});
			$(document).on('input change', '#pv_lines_body input, #pv_lines_body select', function() {
				recalcTotals();
			});
			$('#tds_percent, #other_deduction').on('change input', recalcTotals);
			$('#payment_mode').on('change', togglePaymentBank);
			$('#company_bank_id').on('change', function() {
				applyBankDetails(getSelectVal($(this)));
				$('#bank_details_row').toggle(!!getSelectVal($(this)));
			});
			$(document).on('click', '.btn-remove-att', function() {
				var id = parseInt($(this).closest('.pv-attach-card').data('id'), 10);
				if (!id) return;
				if (!confirm('Remove this attachment?')) return;
				removeAttachmentIds.push(id);
				$(this).closest('.pv-attach-card').remove();
				if (!$('#existing_attachments .pv-attach-card').length) {
					$('#existing_attachments_label').hide();
				}
			});

			$('#btn_save_pv').on('click', function() {
				var $btn = $(this);
				if ($btn.prop('disabled')) return;
				if (!$.trim($('#voucher_date').val())) { alert('Please enter voucher date.'); return; }
				if (!getSelectVal($('#vendor_id'))) { alert('Please select vendor.'); return; }
				if (!$.trim($('#supplier_invoice_no').val())) { alert('Please enter supplier invoice no.'); return; }
				if (!$.trim($('#supplier_invoice_date').val())) { alert('Please enter supplier invoice date.'); return; }
				if (!$('#pv_lines_body tr').length) { alert('Please add at least one line.'); return; }
				var paymentMode = String(getSelectVal($('#payment_mode')) || '').toUpperCase();
				if (!paymentMode) { alert('Please select payment mode.'); return; }
				if (paymentModeRequiresBank(paymentMode) && !getSelectVal($('#company_bank_id'))) {
					alert('Please select company bank account.');
					return;
				}
				$btn.prop('disabled', true).text('Saving…');
				$.ajax({
					url: 'purchase_voucher_data.php',
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
					window.location.href = 'purchase_voucher_list.php';
				}).fail(function() {
					alert('Save request failed.');
					$btn.prop('disabled', false).text('Save');
				});
			});

			loadRecord();
		});
	</script>
</body>

</html>