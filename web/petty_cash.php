<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/petty_cash_helpers.php');

petty_cash_ensure_schema($conn);
expense_require_admin();

$c_date = date('d-m-Y');
$preselect_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($preselect_id <= 0 && empty($_GET['create'])) {
	header('Location:petty_cash_list.php');
	exit;
}
$is_create = ($preselect_id <= 0 && !empty($_GET['create']));
$preview_voucher_no = petty_cash_preview_next_no($conn);
$entry_types = petty_cash_entry_types();
$category_rows = petty_cash_category_options($conn);
$vendor_rows = petty_cash_vendor_options($conn);
$summary = petty_cash_summary($conn);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2 .ew-form-grid--pc {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}
		.ew-page-v2 .ew-form-grid--pc .span-2 { grid-column: span 2; }
		.ew-page-v2 .ew-form-grid--pc .span-3 { grid-column: span 3; }
		@media (max-width: 991px) {
			.ew-page-v2 .ew-form-grid--pc { grid-template-columns: repeat(2, minmax(0, 1fr)); }
			.ew-page-v2 .ew-form-grid--pc .span-2 { grid-column: span 2; }
		}
		@media (max-width: 640px) {
			.ew-page-v2 .ew-form-grid--pc { grid-template-columns: 1fr; }
			.ew-page-v2 .ew-form-grid--pc .span-2,
			.ew-page-v2 .ew-form-grid--pc .span-3 { grid-column: span 1; }
		}
		.pc-balance-chip {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 14px;
			background: #eff6ff;
			border: 1px solid #bfdbfe;
			border-radius: 8px;
			font-size: 13px;
			color: #1e3a8a;
		}
		.pc-balance-chip strong { font-size: 15px; color: #1d4ed8; }
		.pc-type-hint {
			display: inline;
			margin-left: 6px;
			font-size: 12px;
			font-weight: 500;
			color: #64748b;
			text-transform: none;
			letter-spacing: 0;
		}
		.ew-form-footer.pc-form-footer {
			justify-content: flex-end;
			gap: 8px;
			margin: 0 !important;
			padding: 14px 24px 22px !important;
		}
		.ew-page-v2 .ew-form-grid--pc .select2-container {
			width: 100% !important;
		}
		.ew-page-v2 .ew-form-grid--pc .select2-container .select2-choice {
			height: 34px !important;
			line-height: 32px !important;
		}
		.ew-page-v2 .ew-form-grid--pc .select2-container .select2-chosen {
			line-height: 32px !important;
		}
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
				<div class="ew-page-v2">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<a href="petty_cash_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php echo $preselect_id > 0 ? 'Edit Petty Cash' : 'Add Petty Cash'; ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<span class="pc-balance-chip">Balance: <strong>₹ <span id="pc_balance_display"><?php echo htmlspecialchars($summary['balance']); ?></span></strong></span>
							<a href="petty_cash_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
						</div>
					</div>
					<div class="ew-card">
						<div class="ew-form-body">
							<form id="petty_cash_form">
								<input type="hidden" id="petty_cash_id" value="<?php echo (int) $preselect_id; ?>">
								<div class="ew-form-grid ew-form-grid--pc">
									<div class="ew-section-label">Voucher Details</div>

									<div class="ew-field">
										<label class="control-label">Voucher No :</label>
										<input type="text" id="voucher_no_display" class="form-control" readonly
											value="<?php echo $preselect_id > 0 ? '' : htmlspecialchars($preview_voucher_no); ?>">
									</div>
									<div class="ew-field">
										<label class="control-label">Voucher Date <span style="color:red;">*</span> :</label>
										<input type="text" id="voucher_date" name="voucher_date" class="form-control ew-date-field"
											value="<?php echo htmlspecialchars($c_date); ?>"
											data-ew-datepicker="1" data-date-format="dd-mm-yyyy" autocomplete="off">
									</div>
									<div class="ew-field">
										<label class="control-label">Entry Type <span style="color:red;">*</span> :<span class="pc-type-hint" id="entry_type_hint">Money sent from the petty cash box.</span></label>
										<select id="entry_type" name="entry_type" class="form-control">
											<?php foreach ($entry_types as $key => $label) { ?>
												<option value="<?php echo htmlspecialchars($key); ?>"<?php echo $key === 'payment' ? ' selected' : ''; ?>>
													<?php echo htmlspecialchars($label); ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<div class="ew-field" id="vendor_wrap">
										<label class="control-label">Vendor :</label>
										<select id="vendor_id" name="vendor_id" class="form-control">
											<option value="">— Select vendor —</option>
											<?php foreach ($vendor_rows as $vend) { ?>
												<option value="<?php echo (int) $vend['vendor_id']; ?>">
													<?php echo htmlspecialchars($vend['label']); ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<div class="ew-field">
										<label class="control-label">Amount (₹) <span style="color:red;">*</span> :</label>
										<input type="text" id="amount" name="amount" class="form-control" inputmode="decimal" autocomplete="off" placeholder="0.00">
									</div>
									<div class="ew-field" id="paid_to_wrap">
										<label class="control-label">Paid To :</label>
										<input type="text" id="paid_to" name="paid_to" class="form-control" maxlength="150" placeholder="Person / shop / purpose">
									</div>
									<div class="ew-field" id="category_wrap">
										<label class="control-label">Expense Type :</label>
										<select id="category_id" name="category_id" class="form-control">
											<option value="">— Optional —</option>
											<?php foreach ($category_rows as $cat) { ?>
												<option value="<?php echo (int) $cat['category_id']; ?>">
													<?php echo htmlspecialchars($cat['label']); ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<div class="ew-field">
										<label class="control-label">Ref No :</label>
										<input type="text" id="ref_no" name="ref_no" class="form-control" maxlength="50" placeholder="Bill / slip no">
									</div>
									<div class="ew-field span-2">
										<label class="control-label">Particulars <span style="color:red;">*</span> :</label>
										<input type="text" id="particulars" name="particulars" class="form-control" maxlength="500" placeholder="What was this for?">
									</div>

									<div class="ew-field span-3">
										<label class="control-label">Remarks :</label>
										<textarea id="remarks" name="remarks" class="form-control" rows="2" maxlength="500" placeholder="Optional notes"></textarea>
									</div>
								</div>
							</form>
						</div>
						<div class="ew-form-footer pc-form-footer">
							<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_reset">Reset</button>
							<button type="submit" form="petty_cash_form" class="ew-btn-v2 ew-btn-v2-primary" id="btn_save">Save</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php require_once('include/footer.php'); ?>
</div>
<script>
(function() {
	var pettyCashId = <?php echo (int) $preselect_id; ?>;
	var hints = {
		receipt: 'Money received into the petty cash box',
		payment: 'Money sent from the petty cash box.'
	};

	function getSelectVal($el) {
		var val = $el.val();
		if ((val === null || val === undefined || val === '') && $el.data('select2')) {
			val = $el.select2('val');
		}
		return val == null ? '' : String(val);
	}

	function setSelectVal($el, value) {
		if ($el.data('select2')) {
			$el.select2('val', value || '');
		} else {
			$el.val(value || '');
		}
	}

	function initSelect2($el, placeholder) {
		if (!$.fn.select2) return;
		if ($el.data('select2')) $el.select2('destroy');
		$el.select2({
			width: '100%',
			placeholder: placeholder || 'Select',
			allowClear: true
		});
	}

	function toggleTypeFields() {
		var type = String($('#entry_type').val() || 'payment');
		var isPayment = type === 'payment';
		$('#vendor_wrap').show();
		$('#paid_to_wrap').toggle(isPayment);
		$('#category_wrap').toggle(isPayment);
		$('#entry_type_hint').text(hints[type] || '');
		if (!isPayment) {
			$('#paid_to').val('');
			setSelectVal($('#category_id'), '');
		}
	}

	function fillForm(data) {
		pettyCashId = parseInt(data.petty_cash_id, 10) || 0;
		$('#voucher_no_display').val(data.voucher_no || '');
		$('#voucher_date').val(data.voucher_date || '');
		$('#entry_type').val(data.entry_type || 'payment');
		$('#amount').val(data.amount_raw != null ? data.amount_raw : (data.amount || ''));
		$('#particulars').val(data.particulars || '');
		$('#paid_to').val(data.paid_to || '');
		setSelectVal($('#vendor_id'), data.vendor_id ? String(data.vendor_id) : '');
		setSelectVal($('#category_id'), data.category_id ? String(data.category_id) : '');
		$('#ref_no').val(data.ref_no || '');
		$('#remarks').val(data.remarks || '');
		toggleTypeFields();
	}

	$(function() {
		initSelect2($('#vendor_id'), 'Select vendor');
		initSelect2($('#category_id'), 'Optional expense type');
		toggleTypeFields();
		$('#entry_type').on('change', toggleTypeFields);

		if (pettyCashId > 0) {
			$.ajax({
				url: 'petty_cash_data.php',
				data: { cmd: 'fetch', petty_cash_id: pettyCashId },
				dataType: 'json',
				global: false
			}).done(function(r) {
				if (!r || r.status !== 0) {
					alert(r && r.message ? r.message : 'Could not load voucher.');
					return;
				}
				fillForm(r);
			}).fail(function() {
				alert('Could not load voucher record.');
			});
		}

		$('#btn_reset').on('click', function() {
			if (!confirm('Reset form?')) return;
			window.location.reload();
		});

		$('#petty_cash_form').on('submit', function(e) {
			e.preventDefault();
			var amount = $.trim($('#amount').val() || '');
			var particulars = $.trim($('#particulars').val() || '');
			if (!$('#voucher_date').val()) {
				alert('Please enter voucher date.');
				return;
			}
			if (!amount || parseFloat(String(amount).replace(/,/g, '')) <= 0) {
				alert('Please enter a valid amount.');
				return;
			}
			if (!particulars) {
				alert('Please enter particulars.');
				return;
			}
			var payload = {
				cmd: 'save',
				petty_cash_id: pettyCashId,
				voucher_date: $('#voucher_date').val(),
				entry_type: $('#entry_type').val(),
				amount: amount,
				particulars: particulars,
				paid_to: $('#paid_to').val(),
				vendor_id: getSelectVal($('#vendor_id')) || 0,
				category_id: getSelectVal($('#category_id')) || 0,
				ref_no: $('#ref_no').val(),
				remarks: $('#remarks').val()
			};
			$('#btn_save').prop('disabled', true);
			$.post('petty_cash_data.php', payload, function(r) {
				$('#btn_save').prop('disabled', false);
				if (!r || r.status !== 0) {
					alert(r && r.message ? r.message : 'Save failed.');
					return;
				}
				window.location.href = 'petty_cash_list.php';
			}, 'json').fail(function() {
				$('#btn_save').prop('disabled', false);
				alert('Save request failed.');
			});
		});
	});
})();
</script>
</body>
</html>
