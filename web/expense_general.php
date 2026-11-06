<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_general_helpers.php');

expense_general_ensure_schema($conn);
expense_require_admin();

$c_date = date('d-m-Y');
$preselect_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($preselect_id <= 0 && empty($_GET['create'])) {
	header('Location:expense_general_list.php');
	exit;
}
$is_create = ($preselect_id <= 0 && !empty($_GET['create']));
$entry_mode = 'general';
if ($is_create) {
	$mode_param = isset($_GET['mode']) ? strtolower(trim((string) $_GET['mode'])) : '';
	if ($mode_param === 'gcn') {
		$entry_mode = 'gcn';
	}
}
$preview_expense_no = expense_general_preview_next_no($conn);
$payment_modes = ew_company_bank_payment_modes();
$vendor_rows = expense_gcn_vendor_options($conn);
$category_rows = expense_gcn_category_options($conn);
$bank_rows = ew_company_bank_options($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2 .ew-form-grid--general {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}

		.ew-page-v2 .ew-form-grid--general .span-3 {
			grid-column: span 3;
		}

		@media (max-width: 991px) {
			.ew-page-v2 .ew-form-grid--general {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}

		@media (max-width: 640px) {
			.ew-page-v2 .ew-form-grid--general {
				grid-template-columns: 1fr;
			}
		}

		.ew-form-grid--general .bank-link-wrap .btn-add-inline {
			height: 34px;
			padding: 0 12px;
			white-space: nowrap;
			flex: 0 0 auto;
		}

		#expense_no_display,
		.bank-readonly {
			font-weight: 600;
		}

		.ew-page-v2 .ew-field input.form-control,
		.ew-page-v2 .ew-field select.form-control {
			height: 34px;
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

		.ew-form-footer.egen-form-footer {
			justify-content: space-between;
			align-items: center;
			gap: 16px;
		}

		.egen-net-payable {
			display: flex;
			align-items: center;
			gap: 10px;
			flex: 0 0 auto;
		}

		.egen-net-payable label {
			margin: 0;
			font-size: 12px;
			font-weight: 600;
			color: var(--ew-text-muted, #6B7A8D);
			white-space: nowrap;
		}

		#net_payable_display {
			width: 128px;
			height: 34px;
			padding: 0 12px;
			font-size: 13px;
			font-weight: 700;
			text-align: right;
			box-sizing: border-box;
		}

		.ew-expense-mode-bar {
			padding: 16px 24px 12px;
			border-bottom: 1px solid var(--ew-border-light, #E9ECF0);
		}

		.ew-expense-mode-bar .ew-field {
			max-width: 420px;
			margin: 0;
		}

		.ew-expense-mode-bar .egen-select-wrap,
		.ew-expense-mode-bar .select2-container {
			width: 100% !important;
			max-width: 420px;
		}

		.ew-expense-mode-bar .select2-container .select2-choice {
			height: 40px !important;
			line-height: 38px !important;
		}

		.ew-expense-mode-bar .select2-container .select2-chosen {
			line-height: 38px !important;
		}

		#ew-expense-panel-gcn {
			padding: 0;
		}

		#ew_expense_gcn_frame {
			display: block;
			width: 100%;
			min-height: 880px;
			border: 0;
			background: transparent;
		}

		.egen-form-footer-actions {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
			margin-left: auto;
		}

		.field-disabled {
			opacity: .65;
		}

		#gst_amount.egen-gst-readonly {
			background: var(--panel-head-bg, #F3F7FB) !important;
			cursor: default;
			margin-top: 8px;
		}

		#gst_wrap .ew-field-sub-label {
			display: block;
			margin-top: 10px;
			margin-bottom: 4px;
			font-size: 12px;
			font-weight: 600;
			color: var(--ew-text-muted, #6B7A8D);
		}

		@media (max-width: 768px) {
			.ew-form-footer.egen-form-footer {
				flex-direction: column;
				align-items: stretch;
			}

			.egen-net-payable {
				max-width: none;
			}

			.egen-form-footer-actions {
				justify-content: flex-end;
				margin-left: 0;
				width: 100%;
			}

			.egen-net-payable {
				width: 100%;
				justify-content: space-between;
			}

			#net_payable_display {
				flex: 1;
				max-width: 200px;
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
								<a href="expense_general_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php
									if ($preselect_id > 0) {
										echo 'Edit General Expense';
									} elseif ($entry_mode === 'gcn') {
										echo 'Add Expense against GCN';
									} else {
										echo 'Add General Expense';
									}
								?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="expense_general_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
								<a href="company_bank.php" class="ew-btn-v2 ew-btn-v2-outline">Company Banks</a>
							</div>
						</div>
						<div class="ew-card">
							<?php if ($is_create) { ?>
							<div class="ew-expense-mode-bar">
								<div class="ew-field">
									<label class="control-label">Expense mode <span style="color:red;">*</span> :</label>
									<div class="egen-select-wrap">
										<select id="expense_entry_mode" class="form-control egen-select">
											<option value="general"<?php echo $entry_mode === 'general' ? ' selected' : ''; ?>>General expense</option>
											<option value="gcn"<?php echo $entry_mode === 'gcn' ? ' selected' : ''; ?>>Expense against GCN</option>
										</select>
									</div>
								</div>
							</div>
							<?php } ?>
							<div id="ew-expense-panel-general"<?php echo ($is_create && $entry_mode === 'gcn') ? ' style="display:none;"' : ''; ?>>
							<div class="ew-form-body">
							<form id="general_expense_form">
								<div class="ew-form-grid ew-form-grid--general">
									<div class="ew-section-label">Expense Details</div>

									<div class="ew-field">
										<label class="control-label">Expense Code :</label>
										<input type="text" id="expense_no_display" class="form-control" readonly value="<?php echo $preselect_id > 0 ? '' : htmlspecialchars($preview_expense_no); ?>">
									</div>
									<div class="ew-field">
										<label class="control-label">Expense Date <span style="color:red;">*</span> :</label>
										<input type="text" id="expense_date" name="expense_date" class="form-control ew-date-field" value="<?php echo htmlspecialchars($c_date); ?>" data-ew-datepicker="1" data-date-format="dd-mm-yyyy" autocomplete="off">
									</div>
									<div class="ew-field">
										<label class="control-label">Vendor :</label>
										<div class="egen-select-wrap">
											<select id="vendor_id" name="vendor_id" class="form-control egen-select"></select>
										</div>
										<span class="ew-field-hint" id="vendor_tax_hint"></span>
									</div>

									<div class="ew-field">
										<label class="control-label">Expense Type <span style="color:red;">*</span> :</label>
										<div class="egen-select-wrap">
											<select id="category_id" name="category_id" class="form-control egen-select"></select>
										</div>
									</div>
									<div class="ew-field">
										<label class="control-label">Expense Amount <span style="color:red;">*</span> :</label>
										<input type="text" id="expense_amount" name="expense_amount" class="form-control text-right" placeholder="0.00" autocomplete="off">
									</div>
									<div class="ew-field" id="gst_wrap">
										<label class="control-label">GST % :</label>
										<select id="gst_percent" name="gst_percent" class="form-control">
											<option value="0">0%</option>
											<option value="5">5%</option>
											<option value="12">12%</option>
											<option value="18">18%</option>
											<option value="28">28%</option>
										</select>
										<span class="ew-field-sub-label">GST amount (auto)</span>
										<input type="text" id="gst_amount" name="gst_amount" class="form-control text-right egen-gst-readonly" placeholder="0.00" readonly tabindex="-1" autocomplete="off">
										<span class="ew-field-hint" id="gst_hint"></span>
									</div>

									<div class="ew-field">
										<label class="control-label">TDS Value :</label>
										<input type="text" id="tds_amount" name="tds_amount" class="form-control text-right" placeholder="0.00" autocomplete="off">
										<span class="ew-field-hint" id="tds_hint"></span>
									</div>
									<div class="ew-field">
										<label class="control-label">Other Deduction :</label>
										<input type="text" id="other_deduction" name="other_deduction" class="form-control text-right" placeholder="0.00" autocomplete="off">
									</div>

									<div class="ew-field span-3">
										<label class="control-label">Description :</label>
										<textarea id="description" name="description" class="form-control" maxlength="500" rows="3" placeholder="Expense notes"></textarea>
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
								</div>
							</form>
							</div>
							<div class="ew-form-footer egen-form-footer">
								<div class="egen-net-payable">
									<label class="control-label">Net Payable :</label>
									<input type="text" id="net_payable_display" class="form-control" readonly value="0.00">
								</div>
								<div class="egen-form-footer-actions">
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_reset">Reset</button>
									<button type="submit" class="ew-btn-v2 ew-btn-v2-primary" id="btn_save" form="general_expense_form"><i class="fa fa-save"></i> Save Expense</button>
								</div>
							</div>
							</div>
							<?php if ($is_create) { ?>
							<div id="ew-expense-panel-gcn"<?php echo $entry_mode === 'general' ? ' style="display:none;"' : ''; ?>>
								<iframe id="ew_expense_gcn_frame" title="Expense against GCN" src="<?php echo $entry_mode === 'gcn' ? 'expense_gcn.php?create=1&amp;embed=1' : ''; ?>"></iframe>
							</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>

	<script type="text/javascript">
		var generalExpenseId = <?php echo (int) $preselect_id; ?>;
		var nextExpenseNoPreview = <?php echo json_encode($preview_expense_no, JSON_UNESCAPED_UNICODE); ?>;
		var vendorOptions = <?php echo json_encode($vendor_rows, JSON_UNESCAPED_UNICODE); ?>;
		var categoryOptions = <?php echo json_encode($category_rows, JSON_UNESCAPED_UNICODE); ?>;
		var bankOptions = <?php echo json_encode($bank_rows, JSON_UNESCAPED_UNICODE); ?>;
		var primaryBankId = 0;
		var vendorTax = { gst_applicable: 0, tds_applicable: 0, tds_rate: 0 };
		var gstPercentRates = [0, 5, 12, 18, 28];
		var tdsManual = false;
		var skipTypeDefault = false;
		var expenseCreateMode = <?php echo $is_create ? 'true' : 'false'; ?>;

		function escHtml(v) {
			if (v === null || v === undefined) return '';
			return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}
		function parseMoney(v) {
			if (v === null || v === undefined || v === '') return 0;
			return parseFloat(String(v).replace(/,/g, '')) || 0;
		}
		function formatMoney(v) {
			return parseMoney(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
		}
		function getCategoryRow(categoryId) {
			var found = null;
			$.each(categoryOptions || [], function(i, row) {
				if (String(row.category_id) === String(categoryId)) {
					found = row;
					return false;
				}
			});
			return found;
		}
		function normalizeGstPercentSelect(pct) {
			pct = parseFloat(pct);
			if (isNaN(pct) || pct < 0) pct = 0;
			var best = 0;
			var bestDiff = null;
			$.each(gstPercentRates, function(i, rate) {
				var diff = Math.abs(pct - rate);
				if (bestDiff === null || diff < bestDiff) {
					bestDiff = diff;
					best = rate;
				}
			});
			return best;
		}
		function setGstPercentVal(pct) {
			$('#gst_percent').val(String(normalizeGstPercentSelect(pct)));
		}
		function applyCategoryDefaultGstPercent() {
			var row = getCategoryRow(getSelectVal($('#category_id')));
			var pct = 0;
			if (row && parseInt(row.gst_applicable, 10) === 1 && parseFloat(row.gst_percent) > 0) {
				pct = parseFloat(row.gst_percent);
			}
			setGstPercentVal(pct);
		}
		function getCategoryDefaultAmount(categoryId) {
			var amt = 0;
			$.each(categoryOptions || [], function(i, row) {
				if (String(row.category_id) === String(categoryId)) {
					amt = parseMoney(row.default_amount);
					return false;
				}
			});
			return amt;
		}
		function applyCategoryDefaultAmount() {
			var amt = getCategoryDefaultAmount(getSelectVal($('#category_id')));
			if (amt > 0) {
				$('#expense_amount').val(formatMoney(amt));
			}
		}
		function getSelectVal($el) {
			if (!$el || !$el.length) return '';
			var val = $el.val();
			if ($el.data('select2')) val = $el.select2('val');
			if ($.isArray(val)) val = val[0] || '';
			return val ? String(val) : '';
		}
		function setSelectVal($el, value) {
			if (!$el || !$el.length) return;
			if ($el.data('select2')) $el.select2('val', value || '');
			else $el.val(value || '');
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
			var html = '<option value=""></option>';
			$.each(rows || [], function(i, row) {
				var val = row[valueKey];
				var label = row[labelKey] || row.label || val;
				html += '<option value="' + escHtml(val) + '"' + (String(selected) === String(val) ? ' selected' : '') + '>' + escHtml(label) + '</option>';
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
		function buildCategorySelect(selected) {
			var $el = $('#category_id');
			fillSelectOptions($el, categoryOptions, 'category_id', 'label', selected);
			initSelect2($el, 'Select expense type');
			setSelectVal($el, selected || '');
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
		function initExpenseEntryModeSelect() {
			var $el = $('#expense_entry_mode');
			if (!$el.length) return;
			if ($el.data('select2')) $el.select2('destroy');
			$el.select2({
				width: '100%',
				minimumResultsForSearch: Infinity,
				allowClear: false
			});
			syncSelectTitle($el);
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
		function updateVendorTaxHint() {
			var parts = [];
			if (vendorTax.gst_applicable) parts.push('GST applicable');
			else parts.push('No GST on vendor');
			if (vendorTax.tds_applicable && parseMoney(vendorTax.tds_rate) > 0) {
				parts.push('TDS ' + formatMoney(vendorTax.tds_rate) + '%');
			}
			$('#vendor_tax_hint').text(parts.join(' · '));
			if (vendorTax.gst_applicable) {
				$('#gst_wrap').removeClass('field-disabled');
				$('#gst_percent').prop('disabled', false);
			} else {
				$('#gst_wrap').addClass('field-disabled');
				setGstPercentVal(0);
				$('#gst_percent').prop('disabled', true);
				$('#gst_amount').val('0.00');
			}
		}
		function loadVendorTax(vendorId, callback) {
			if (!vendorId) {
				vendorTax = { gst_applicable: 0, tds_applicable: 0, tds_rate: 0 };
				updateVendorTaxHint();
				if (callback) callback();
				return;
			}
			$.getJSON('expense_general_data.php', { cmd: 'vendor_tax_info', vendor_id: vendorId }, function(r) {
				vendorTax = (r && r.tax) ? r.tax : { gst_applicable: 0, tds_applicable: 0, tds_rate: 0 };
				updateVendorTaxHint();
				if (callback) callback();
			});
		}
		function recalcAmounts(useSuggestedTds, applyCategoryGst) {
			if (applyCategoryGst) {
				applyCategoryDefaultGstPercent();
			}
			var payload = {
				cmd: 'calc_amounts',
				vendor_id: getSelectVal($('#vendor_id')) || 0,
				category_id: getSelectVal($('#category_id')) || 0,
				expense_amount: $('#expense_amount').val() || 0,
				gst_percent: $('#gst_percent').val() || 0,
				other_deduction: $('#other_deduction').val() || 0
			};
			if (!useSuggestedTds) {
				payload.tds_amount = $('#tds_amount').val() || 0;
			}
			$.getJSON('expense_general_data.php', payload, function(r) {
				if (!r || r.status !== 0 || !r.amounts) return;
				if (applyCategoryGst && r.suggested_gst_percent !== undefined) {
					setGstPercentVal(r.suggested_gst_percent);
				}
				$('#gst_amount').val(formatMoney(r.suggested_gst || r.amounts.gst_amount || 0));
				$('#gst_hint').text(parseMoney($('#gst_amount').val()) > 0 ? 'From expense amount × GST %' : '');
				if (useSuggestedTds || !tdsManual) {
					$('#tds_amount').val(formatMoney(r.suggested_tds || 0));
					$('#tds_hint').text((r.suggested_tds > 0) ? 'Auto from vendor/type' : '');
				}
				$('#net_payable_display').val(formatMoney(r.amounts.net_payable || 0));
			});
		}
		function resetForm(keepDate) {
			generalExpenseId = 0;
			tdsManual = false;
			setGstPercentVal(0);
			$('#expense_no_display').val(nextExpenseNoPreview);
			if (!keepDate) $('#expense_date').val(<?php echo json_encode($c_date); ?>);
			setSelectVal($('#vendor_id'), '');
			setSelectVal($('#category_id'), '');
			$('#expense_amount, #gst_amount, #tds_amount, #other_deduction').val('');
			$('#description').val('');
			setSelectVal($('#payment_mode'), '');
			clearBankSelection();
			vendorTax = { gst_applicable: 0, tds_applicable: 0, tds_rate: 0 };
			updateVendorTaxHint();
			$('#net_payable_display').val('0.00');
			togglePaymentBankRequirement();
		}
		function fillForm(data) {
			generalExpenseId = parseInt(data.general_expense_id, 10) || 0;
			$('#expense_no_display').val(data.expense_no || '');
			$('#expense_date').val(data.expense_date || '');
			skipTypeDefault = true;
			buildVendorSelect(data.vendor_id || '');
			buildCategorySelect(data.category_id || '');
			skipTypeDefault = false;
			$('#expense_amount').val(data.expense_amount || '');
			setGstPercentVal(data.gst_percent_raw !== undefined ? data.gst_percent_raw : 0);
			$('#gst_amount').val(data.gst_amount || '');
			$('#tds_amount').val(data.tds_amount || '');
			$('#other_deduction').val(data.other_deduction || '');
			$('#description').val(data.description || '');
			initPaymentModeSelect(data.payment_mode || '');
			buildBankSelect(data.company_bank_id || '');
			if (data.company_bank_id) {
				setSelectVal($('#company_bank_id'), String(data.company_bank_id));
				applyBankDetails(data.company_bank_id);
			} else {
				clearBankSelection();
			}
			$('#net_payable_display').val(data.net_payable || '0.00');
			tdsManual = parseMoney(data.tds_amount_raw) > 0;
			loadVendorTax(data.vendor_id || 0, function() {
				togglePaymentBankRequirement();
			});
		}
		function initFormSelects() {
			$.each(bankOptions, function(i, b) {
				if (parseInt(b.is_primary, 10) === 1) primaryBankId = parseInt(b.bank_account_id, 10) || 0;
			});
			buildVendorSelect('');
			buildCategorySelect('');
			buildBankSelect('');
			initPaymentModeSelect('');
			clearBankSelection();
		}

		$(function() {
			initFormSelects();
			if (generalExpenseId > 0) {
				$.getJSON('expense_general_data.php', { cmd: 'fetch', general_expense_id: generalExpenseId }, function(r) {
					if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Could not load expense.');
						return;
					}
					fillForm(r);
				}).fail(function() {
					alert('Could not load expense record.');
				});
			} else {
				togglePaymentBankRequirement();
			}

			function resizeGcnFrame() {
				var $frame = $('#ew_expense_gcn_frame');
				if (!$frame.length) return;
				try {
					var doc = $frame[0].contentDocument || $frame[0].contentWindow.document;
					var h = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight, 880);
					$frame.height(h + 24);
				} catch (e) { /* cross-origin or not loaded */ }
			}

			function setExpenseEntryMode(mode) {
				var isGcn = mode === 'gcn';
				$('#ew-expense-panel-general').toggle(!isGcn);
				$('#ew-expense-panel-gcn').toggle(isGcn);
				var $title = $('.ew-page-title');
				if ($title.length) {
					$title.text(isGcn ? 'Add Expense against GCN' : 'Add General Expense');
				}
				if (isGcn) {
					var $frame = $('#ew_expense_gcn_frame');
					if ($frame.length && !$frame.attr('src')) {
						$frame.attr('src', 'expense_gcn.php?create=1&embed=1');
					}
					$frame.off('load.ewGcn').on('load.ewGcn', function() {
						resizeGcnFrame();
					});
				}
				if (window.history && window.history.replaceState) {
					var url = new URL(window.location.href);
					url.searchParams.set('mode', isGcn ? 'gcn' : 'general');
					window.history.replaceState({}, '', url.pathname + '?' + url.searchParams.toString());
				}
			}

			if (expenseCreateMode) {
				initExpenseEntryModeSelect();
				$('#expense_entry_mode').on('change', function() {
					setExpenseEntryMode(getSelectVal($(this)) || 'general');
					syncSelectTitle($(this));
				});
				$('#ew_expense_gcn_frame').on('load', resizeGcnFrame);
			}

			$('#vendor_id').on('change', function() {
				tdsManual = false;
				syncSelectTitle($(this));
				loadVendorTax(getSelectVal($(this)), function() {
					recalcAmounts(true, false);
				});
			});
			$('#category_id').on('change', function() {
				syncSelectTitle($(this));
				if (!skipTypeDefault) {
					applyCategoryDefaultAmount();
				}
				recalcAmounts(true, true);
			});
			$('#expense_amount, #other_deduction').on('input blur', function() {
				recalcAmounts(!tdsManual, false);
			});
			$('#gst_percent').on('change', function() {
				recalcAmounts(!tdsManual, false);
			});
			$('#tds_amount').on('input', function() {
				tdsManual = true;
				recalcAmounts(false, false);
			});
			$('#company_bank_id').on('change', function() {
				var bankId = getSelectVal($(this));
				syncSelectTitle($(this));
				applyBankDetails(bankId);
				$('#bank_details_row').toggle(!!bankId);
			});
			$('#payment_mode').on('change', function() {
				syncSelectTitle($(this));
				togglePaymentBankRequirement();
			});

			$('#btn_reset').on('click', function() {
				if (!confirm('Reset form?')) return;
				resetForm(false);
			});

			$('#general_expense_form').on('submit', function(e) {
				e.preventDefault();
				var payload = {
					cmd: 'save',
					general_expense_id: generalExpenseId,
					expense_date: $('#expense_date').val(),
					vendor_id: getSelectVal($('#vendor_id')),
					category_id: getSelectVal($('#category_id')),
					expense_amount: $('#expense_amount').val(),
					gst_percent: $('#gst_percent').val(),
					gst_amount: $('#gst_amount').val(),
					tds_amount: $('#tds_amount').val(),
					other_deduction: $('#other_deduction').val(),
					description: $('#description').val(),
					payment_mode: getSelectVal($('#payment_mode')),
					company_bank_id: getSelectVal($('#company_bank_id')),
					payment_bank_name: $('#payment_bank_name').val(),
					payment_ifsc: $('#payment_ifsc').val(),
					payment_bank_branch: $('#payment_bank_branch').val()
				};
				$('#btn_save').prop('disabled', true);
				$.post('expense_general_data.php', payload, function(r) {
					$('#btn_save').prop('disabled', false);
					if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Save failed.');
						return;
					}
					generalExpenseId = parseInt(r.general_expense_id, 10) || 0;
					$('#expense_no_display').val(r.expense_no || '');
					$('#net_payable_display').val(r.net_payable || '0.00');
					$.getJSON('expense_general_data.php', { cmd: 'next_expense_no' }, function(preview) {
						if (preview && preview.status === 0 && preview.expense_no) {
							nextExpenseNoPreview = preview.expense_no;
						}
					});
					alert(r.message || 'Saved.');
				}, 'json').fail(function() {
					$('#btn_save').prop('disabled', false);
					alert('Save request failed.');
				});
			});
		});
	</script>
</body>

</html>
