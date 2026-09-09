<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_general_helpers.php');

expense_general_ensure_schema($conn);
expense_require_admin();

$c_date = date('d-m-Y');
$preselect_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
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
		#general_expense_form .row.egen-form-row {
			margin-left: 0;
			margin-right: 0;
		}
		#general_expense_form .row.egen-form-row > [class*="col-"] {
			padding-left: 15px;
			padding-right: 15px;
		}
		#general_expense_form .form-group > .control-label {
			display: block;
			float: none;
			width: 100%;
			text-align: left;
			padding-top: 0;
		}
		#general_expense_form .egen-section-title {
			margin: 18px 0 12px;
			padding-bottom: 6px;
			border-bottom: 1px solid #e4e4e4;
			font-size: 15px;
			font-weight: 600;
			color: #333;
		}
		#general_expense_form .egen-section-title:first-child {
			margin-top: 0;
		}
		#general_expense_form .egen-description-row {
			margin-top: 4px;
			clear: both;
		}
		#general_expense_form .egen-description-row .form-group {
			margin-bottom: 0;
		}
		#general_expense_form .egen-description-row .control-label {
			display: block;
			float: none;
			width: 100%;
			text-align: left;
			padding-top: 0;
			margin-bottom: 6px;
		}
		#general_expense_form .egen-description-row textarea.form-control {
			width: 100%;
			min-height: 88px;
			resize: vertical;
		}
		#expense_no_display, #net_payable_display, .bank-readonly {
			background: #f8fafc;
			font-weight: 600;
		}
		#net_payable_display {
			font-size: 16px;
			text-align: right;
		}
		.tax-hint {
			display: block;
			margin-top: 4px;
			font-size: 11px;
			color: #64748b;
		}
		.egen-form-footer {
			margin-top: 20px;
			padding-top: 16px;
			border-top: 1px solid #e4e4e4;
			display: flex;
			justify-content: space-between;
			align-items: flex-end;
			gap: 16px;
			flex-wrap: wrap;
		}
		.egen-net-payable {
			min-width: 220px;
			max-width: 280px;
		}
		.field-disabled { opacity: .65; }
		@media (max-width: 768px) {
			.egen-form-footer {
				flex-direction: column;
				align-items: stretch;
			}
			.egen-net-payable { max-width: none; }
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
				<div class="col-md-offset-1 col-md-10">
					<div class="widget-container fluid-height clearfix">
						<div class="heading"><i class="fa fa-money"></i> General Expense
							<span class="align-right">
								<a href="expense_general_list.php"><i class="fa fa-list"></i> View List</a>
								&nbsp;&nbsp;
								<a href="company_bank.php"><i class="fa fa-university"></i> Company Banks</a>
							</span>
						</div>
						<div class="widget-content padded clearfix">
							<form class="form-horizontal" id="general_expense_form">
								<div class="row egen-form-row">
									<div class="col-md-12">
										<div class="egen-section-title">Expense Details</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Expense Code :</label>
											<input type="text" id="expense_no_display" class="form-control" readonly value="<?php echo $preselect_id > 0 ? '' : htmlspecialchars($preview_expense_no); ?>">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Expense Date <span style="color:red;">*</span> :</label>
											<input type="text" id="expense_date" name="expense_date" class="form-control ew-date-field" value="<?php echo htmlspecialchars($c_date); ?>" data-ew-datepicker="1" data-date-format="dd-mm-yyyy" autocomplete="off">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Vendor <span style="color:red;">*</span> :</label>
											<div class="egen-select-wrap">
												<select id="vendor_id" name="vendor_id" class="form-control egen-select"></select>
											</div>
											<span class="tax-hint" id="vendor_tax_hint"></span>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Expense Type <span style="color:red;">*</span> :</label>
											<div class="type-add-wrap">
												<div class="egen-select-wrap">
													<select id="category_id" name="category_id" class="form-control egen-select"></select>
												</div>
												<button type="button" class="btn btn-primary btn-add-inline" id="btn_add_expense_type" title="Add expense type">+ Add</button>
											</div>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Expense Amount <span style="color:red;">*</span> :</label>
											<input type="text" id="expense_amount" name="expense_amount" class="form-control text-right" placeholder="0.00" autocomplete="off">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group" id="gst_wrap">
											<label class="control-label">GST Value :</label>
											<input type="text" id="gst_amount" name="gst_amount" class="form-control text-right" placeholder="0.00" autocomplete="off">
											<span class="tax-hint" id="gst_hint"></span>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">TDS Value :</label>
											<input type="text" id="tds_amount" name="tds_amount" class="form-control text-right" placeholder="0.00" autocomplete="off">
											<span class="tax-hint" id="tds_hint"></span>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Other Deduction :</label>
											<input type="text" id="other_deduction" name="other_deduction" class="form-control text-right" placeholder="0.00" autocomplete="off">
										</div>
									</div>
								</div>

								<div class="row egen-form-row egen-description-row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label">Description :</label>
											<textarea id="description" name="description" class="form-control" maxlength="500" rows="3" placeholder="Expense notes"></textarea>
										</div>
									</div>
								</div>

								<div class="row egen-form-row">
									<div class="col-md-12">
										<div class="egen-section-title">Payment Details</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
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
									</div>
									<div class="col-md-6" id="payment_bank_account_wrap" style="display:none;">
										<div class="form-group">
											<label class="control-label">Company Bank Account :</label>
											<div class="bank-link-wrap">
												<div class="egen-select-wrap">
													<select id="company_bank_id" name="company_bank_id" class="form-control egen-select"></select>
												</div>
												<a href="company_bank.php" class="btn btn-default btn-add-inline" title="Manage company bank accounts">Manage</a>
											</div>
										</div>
									</div>
									<div class="col-md-12" id="bank_details_row" style="display:none;">
										<div class="row egen-form-row">
											<div class="col-md-4">
												<div class="form-group">
													<label class="control-label">Bank Name :</label>
													<input type="text" id="payment_bank_name" name="payment_bank_name" class="form-control bank-readonly" readonly autocomplete="off">
												</div>
											</div>
											<div class="col-md-4">
												<div class="form-group">
													<label class="control-label">IFSC Code :</label>
													<input type="text" id="payment_ifsc" name="payment_ifsc" class="form-control bank-readonly" readonly autocomplete="off">
												</div>
											</div>
											<div class="col-md-4">
												<div class="form-group">
													<label class="control-label">Branch :</label>
													<input type="text" id="payment_bank_branch" name="payment_bank_branch" class="form-control bank-readonly" readonly autocomplete="off">
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="egen-form-footer">
									<div class="form-group egen-net-payable">
										<label class="control-label">Net Payable :</label>
										<input type="text" id="net_payable_display" class="form-control" readonly value="0.00">
									</div>
									<div class="form-action">
										<button type="button" class="btn btn-default" id="btn_reset">Reset</button>
										<button type="submit" class="btn btn-primary" id="btn_save"><i class="fa fa-save"></i> Save Expense</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>

	<div class="modal fade" id="modal_add_expense_type" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
					<h4 class="modal-title">Add Expense Type</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label>Expense Type Name</label>
						<input type="text" class="form-control" id="quick_expense_type_name" maxlength="150" autocomplete="off" placeholder="e.g. Office Rent">
					</div>
					<div id="expense_type_msg" class="text-danger" style="display:none;"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="quick_expense_type_save">Save &amp; Select</button>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		var generalExpenseId = <?php echo (int) $preselect_id; ?>;
		var nextExpenseNoPreview = <?php echo json_encode($preview_expense_no, JSON_UNESCAPED_UNICODE); ?>;
		var vendorOptions = <?php echo json_encode($vendor_rows, JSON_UNESCAPED_UNICODE); ?>;
		var categoryOptions = <?php echo json_encode($category_rows, JSON_UNESCAPED_UNICODE); ?>;
		var bankOptions = <?php echo json_encode($bank_rows, JSON_UNESCAPED_UNICODE); ?>;
		var primaryBankId = 0;
		var vendorTax = { gst_applicable: 0, tds_applicable: 0, tds_rate: 0 };
		var gstManual = false;
		var tdsManual = false;

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
				$('#company_bank_id').closest('.form-group').find('label').html('Company Bank Account <span style="color:red;">*</span> :');
			} else {
				$('#company_bank_id').closest('.form-group').find('label').html('Company Bank Account :');
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
				$('#gst_amount').prop('readonly', false);
			} else {
				$('#gst_wrap').addClass('field-disabled');
				$('#gst_amount').val('0.00').prop('readonly', true);
				gstManual = false;
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
		function recalcAmounts(useSuggested) {
			var payload = {
				cmd: 'calc_amounts',
				vendor_id: getSelectVal($('#vendor_id')) || 0,
				category_id: getSelectVal($('#category_id')) || 0,
				expense_amount: $('#expense_amount').val() || 0,
				other_deduction: $('#other_deduction').val() || 0
			};
			if (!useSuggested) {
				payload.gst_amount = $('#gst_amount').val() || 0;
				payload.tds_amount = $('#tds_amount').val() || 0;
			}
			$.getJSON('expense_general_data.php', payload, function(r) {
				if (!r || r.status !== 0 || !r.amounts) return;
				if (useSuggested || !gstManual) {
					$('#gst_amount').val(formatMoney(r.suggested_gst || 0));
					$('#gst_hint').text((r.suggested_gst > 0) ? 'Auto from vendor/type' : '');
				}
				if (useSuggested || !tdsManual) {
					$('#tds_amount').val(formatMoney(r.suggested_tds || 0));
					$('#tds_hint').text((r.suggested_tds > 0) ? 'Auto from vendor/type' : '');
				}
				$('#net_payable_display').val(formatMoney(r.amounts.net_payable || 0));
			});
		}
		function resetForm(keepDate) {
			generalExpenseId = 0;
			gstManual = false;
			tdsManual = false;
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
			buildVendorSelect(data.vendor_id || '');
			buildCategorySelect(data.category_id || '');
			$('#expense_amount').val(data.expense_amount || '');
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
			gstManual = parseMoney(data.gst_amount_raw) > 0;
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

			$('#vendor_id').on('change', function() {
				gstManual = false;
				tdsManual = false;
				syncSelectTitle($(this));
				loadVendorTax(getSelectVal($(this)), function() {
					recalcAmounts(true);
				});
			});
			$('#category_id').on('change', function() {
				syncSelectTitle($(this));
				if (!gstManual) recalcAmounts(true);
			});
			$('#expense_amount, #other_deduction').on('input blur', function() {
				recalcAmounts(!gstManual && !tdsManual);
			});
			$('#gst_amount').on('input', function() {
				gstManual = true;
				recalcAmounts(false);
			});
			$('#tds_amount').on('input', function() {
				tdsManual = true;
				recalcAmounts(false);
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

			$('#btn_add_expense_type').on('click', function() {
				$('#quick_expense_type_name').val('');
				$('#expense_type_msg').hide().text('');
				$('#modal_add_expense_type').modal('show');
			});
			$('#quick_expense_type_save').on('click', function() {
				var name = $.trim($('#quick_expense_type_name').val());
				if (!name) {
					$('#expense_type_msg').text('Enter expense type name.').show();
					return;
				}
				$.post('expense_general_data.php', { cmd: 'add_expense_type', type_name: name }, function(r) {
					if (!r || r.status !== 0) {
						$('#expense_type_msg').text(r && r.message ? r.message : 'Could not add expense type.').show();
						return;
					}
					categoryOptions = r.categories || categoryOptions;
					buildCategorySelect(r.category_id || '');
					$('#modal_add_expense_type').modal('hide');
					recalcAmounts(true);
				}, 'json').fail(function() {
					$('#expense_type_msg').text('Network error. Please try again.').show();
				});
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
