<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/petty_cash_helpers.php');
require_once('include/expense_general_helpers.php');

petty_cash_ensure_schema($conn);
expense_require_admin();

$c_date = date('d-m-Y');
$preselect_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$preselect_no = isset($_GET['no']) ? trim((string) $_GET['no']) : '';
if ($preselect_id <= 0 && $preselect_no !== '') {
	$preselect_id = petty_cash_id_by_voucher_no($conn, $preselect_no);
}
if ($preselect_id <= 0 && empty($_GET['create'])) {
	header('Location:petty_cash_list.php');
	exit;
}
/* Prefer voucher no in the URL (EMPC0001) instead of internal petty_cash_id (e.g. 50). */
if ($preselect_id > 0 && $preselect_no === '' && isset($_GET['id'])) {
	$pc_row = petty_cash_load($conn, $preselect_id);
	if ($pc_row && !empty($pc_row['voucher_no'])) {
		header('Location:petty_cash.php?no=' . rawurlencode($pc_row['voucher_no']));
		exit;
	}
}
$preview_voucher_no = petty_cash_preview_next_no($conn);
$cash_types = petty_cash_cash_types();
$category_rows = petty_cash_category_options($conn);
$vendor_rows = petty_cash_vendor_options($conn);
/* GCN list loaded via AJAX (petty_cash_data.php?cmd=gcn_options) — not inlined on page load */
$gcn_options = array();
$tax_profiles = expense_general_tax_profiles($conn);
$tds_percent_options = array(
	array('value' => '0.00', 'label' => 'No TDS (0%)'),
);
foreach (billing_receipt_tds_percent_options($conn) as $opt) {
	$rate = round((float) ($opt['rate'] ?? 0), 2);
	if ($rate <= 0) {
		continue;
	}
	$tds_percent_options[] = array(
		'value' => number_format($rate, 2, '.', ''),
		'label' => $opt['label'] ?? ('TDS ' . $rate . '%'),
	);
}
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.pc-expense-card.ew-card,
		#pc_lines_body,
		.pc-line-row { overflow: visible; }
		.pc-expense-title-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			padding: 0 24px;
			margin-bottom: 12px;
		}
		.pc-expense-title-row .ew-card-section-title { margin: 0; flex: 1; }
		#pc_lines_stack { padding: 0 24px 4px; }
		.pc-lines-labels.ew-form-grid--expense,
		.pc-line-row.ew-form-grid--expense {
			gap: 12px;
		}
		.pc-expense-card.pc-mode-gcn .pc-lines-labels.ew-form-grid--expense,
		.pc-expense-card.pc-mode-gcn .pc-line-row.ew-form-grid--expense {
			grid-template-columns: minmax(150px, 0.95fr) minmax(0, 1fr) minmax(0, 1fr) 110px 36px;
		}
		.pc-expense-card.pc-general-expense .pc-lines-labels.ew-form-grid--expense,
		.pc-expense-card.pc-general-expense .pc-line-row.ew-form-grid--expense {
			grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 100px 76px 76px 96px 36px;
		}
		.pc-expense-card:not(.pc-mode-gcn) .pc-field-line-gcn,
		.pc-expense-card:not(.pc-mode-gcn) .col-gcn-label { display: none !important; }
		.pc-expense-card:not(.pc-general-expense) .pc-field-line-tax,
		.pc-expense-card:not(.pc-general-expense) .col-tax-label { display: none !important; }
		.pc-line-row .line-gst,
		.pc-line-row .line-tds,
		.pc-line-row .line-net {
			background: #F3F7FB;
			text-align: right;
			font-size: 12px;
		}
		#pc_details_card.pc-details-general .ew-form-grid--invoice {
			grid-template-columns: repeat(3, minmax(0, 1fr)) repeat(2, minmax(0, 0.85fr));
		}
		#pc_general_tax_wrap { display: none; }
		#pc_details_card.pc-details-general #pc_general_tax_wrap { display: contents; }
		#pc_gcn_wrap { display: none; min-width: 0; }
		.pc-details-gcn .ew-form-grid--invoice #pc_gcn_wrap { display: block; }
		.pc-lines-labels.ew-form-grid--expense {
			margin-bottom: 4px;
			padding-bottom: 6px;
			border-bottom: 2px solid #DDE7F0;
		}
		.pc-lines-labels .ew-field label {
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: .03em;
			color: #475569;
		}
		.pc-line-row.ew-form-grid--expense {
			align-items: end;
			padding: 10px 0;
			border-bottom: 1px solid #eef2f7;
			position: relative;
			z-index: 1;
		}
		.pc-line-row.ew-form-grid--expense:focus-within { z-index: 5; }
		.pc-line-row.ew-form-grid--expense:last-child { border-bottom: none; }
		.pc-line-row .ew-field > label { display: none !important; }
		.pc-line-del {
			display: flex;
			align-items: center;
			justify-content: center;
			padding-bottom: 4px;
		}
		@media (max-width: 991px) {
			.pc-lines-labels.ew-form-grid--expense,
			.pc-line-row.ew-form-grid--expense {
				grid-template-columns: 1fr;
			}
			.pc-lines-labels { display: none; }
			.pc-line-row .ew-field[data-col-label]::before {
				content: attr(data-col-label);
				display: block;
				margin: 0 0 4px;
				font-size: 12px;
				font-weight: 600;
				color: #475569;
			}
			.pc-line-del { justify-content: flex-start; padding-bottom: 8px; }
		}
		@media (max-width: 1199px) {
			.pc-details-gcn .ew-form-grid--invoice,
			#pc_details_card.pc-details-general .ew-form-grid--invoice {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}
		/* Header GCN multi-select — override global fixed-height multi rules */
		#pc_gcn_wrap .select2-container-multi {
			display: block !important;
			width: 100% !important;
			max-width: 100% !important;
		}
		#pc_gcn_wrap .select2-container-multi .select2-choices {
			height: auto !important;
			max-height: 96px !important;
			min-height: 34px !important;
			overflow-x: hidden !important;
			overflow-y: auto !important;
			white-space: normal !important;
			padding: 4px 36px 4px 6px !important;
			border-radius: 8px;
		}
		#pc_gcn_wrap .select2-container-multi .select2-choices li {
			float: left !important;
			display: block !important;
		}
		#pc_gcn_wrap .select2-container-multi .select2-choices .select2-search-choice {
			max-width: none !important;
			margin: 2px 4px 2px 0 !important;
		}
		#pc_gcn_wrap .select2-container-multi .select2-choices .select2-search-field {
			width: auto !important;
			min-width: 56px !important;
			max-width: 100% !important;
			float: left !important;
		}
		#pc_gcn_wrap .select2-container-multi .select2-choices .select2-search-field input,
		#pc_gcn_wrap .select2-container-multi .select2-choices .select2-search-choice ~ .select2-search-field input {
			width: 9em !important;
			min-width: 6em !important;
			max-width: 100% !important;
			height: 28px !important;
			min-height: 28px !important;
			line-height: 28px !important;
		}
		#pc_gcn_wrap .select2-container-multi .select2-choices .select2-search-choice ~ .select2-search-field {
			width: auto !important;
		}
		.pc-expense-title-row,
		.pc-form-footer,
		#btn_add_line { position: relative; z-index: 2; }
		#select2-drop-mask:not(:visible) {
			pointer-events: none !important;
		}
		#pc_lines_stack .select2-container,
		#petty_cash_form .ew-field .select2-container { width: 100% !important; }
		.pc-line-row .select2-container .select2-chosen {
			max-width: 100%;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		#pc_lines_stack select.form-control { width: 100%; }
		#pc_lines_stack .select2-drop,
		#petty_cash_form .select2-drop {
			z-index: 10050 !important;
			max-width: min(420px, calc(100vw - 32px)) !important;
		}
		#pc_lines_stack .select2-search input,
		#petty_cash_form .select2-search input { width: 100% !important; }
		.pc-line-row .line-amount { text-align: right; font-weight: 600; }
		.pc-line-summary {
			display: flex;
			align-items: center;
			justify-content: flex-end;
			gap: 10px;
			padding: 12px 24px 16px;
			border-top: 1px solid #e2e8f0;
			background: #f4f7fb;
			font-weight: 700;
			color: #475569;
		}
		#pc_lines_foot_total { color: #0A1E3D; font-size: 15px; min-width: 80px; text-align: right; }
		.btn-remove-line {
			border: none;
			background: transparent;
			color: #dc2626;
			border-radius: 6px;
			padding: 6px 8px;
			font-size: 16px;
			line-height: 1;
			cursor: pointer;
		}
		.btn-remove-line:hover { background: #fef2f2; }
		.ew-form-footer.pc-form-footer {
			justify-content: space-between;
			align-items: center;
			margin: 0 !important;
			padding: 14px 24px 22px !important;
		}
		.pc-total-wrap { display: flex; align-items: center; gap: 10px; }
		.pc-total-wrap label { margin: 0; font-size: 12px; font-weight: 700; color: #6B7A8D; }
		#pc_total_display {
			width: 140px;
			height: 34px;
			font-weight: 800;
			font-size: 15px;
			text-align: right;
			color: #0A1E3D;
		}
		.pc-foot-actions { display: flex; gap: 8px; }
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
							<a href="petty_cash_list.php" class="ew-back-btn" title="Back to list"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php echo $preselect_id > 0 ? 'Edit Petty Cash' : 'Petty Cash'; ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<a href="petty_cash_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
						</div>
					</div>

							<form id="petty_cash_form">
								<input type="hidden" id="petty_cash_id" value="<?php echo (int) $preselect_id; ?>">

						<div class="ew-card ew-invoice-details-card pc-details-gcn" id="pc_details_card">
							<h2 class="ew-card-section-title">Petty Cash Details</h2>
							<div class="ew-form-body">
								<div class="ew-form-grid ew-form-grid--invoice">
									<div class="ew-field">
										<label>Petty Cash Type <span class="req">*</span></label>
										<select id="cash_type" class="form-control">
											<?php foreach ($cash_types as $k => $lbl) { ?>
												<option value="<?php echo htmlspecialchars($k); ?>"<?php echo $k === 'gcn_expense' ? ' selected' : ''; ?>>
													<?php echo htmlspecialchars($lbl); ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<div class="ew-field">
										<label>Petty Cash No</label>
										<div class="ew-invoice-no-display" id="voucher_no_display"><?php echo $preselect_id > 0 ? '—' : htmlspecialchars($preview_voucher_no); ?></div>
									</div>
									<div class="ew-field">
										<label>Expense Date <span class="req">*</span></label>
										<?php echo ew_date_input(array('id' => 'voucher_date', 'value' => $c_date, 'required' => true)); ?>
									</div>
									<div class="ew-field span-gcn" id="pc_gcn_wrap">
										<label>GCN No <span class="req">*</span></label>
										<select id="gcn_keys" class="form-control" multiple="multiple"></select>
									</div>
									<div id="pc_general_tax_wrap">
										<div class="ew-field">
											<label>GST %</label>
											<select id="pc_gst_percent" class="form-control"></select>
										</div>
										<div class="ew-field">
											<label>TDS %</label>
											<select id="pc_tds_percent" class="form-control"></select>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="ew-card ew-invoice-details-card pc-expense-card pc-mode-gcn" id="pc_expense_card" style="margin-top:16px;">
							<div class="pc-expense-title-row">
								<h2 class="ew-card-section-title" id="pc_lines_title">GCN expense</h2>
								<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_add_line" style="padding:6px 12px;font-size:12px;">
									<i class="fa fa-plus"></i> Add
								</button>
							</div>
							<div id="pc_lines_stack">
								<div class="ew-form-grid ew-form-grid--expense pc-lines-labels">
									<div class="ew-field col-gcn-label"><label>GCN No <span class="req">*</span></label></div>
									<div class="ew-field col-paid-label"><label>Paid To <span class="req">*</span></label></div>
									<div class="ew-field col-type-label"><label>Expense Type <span class="req">*</span></label></div>
									<div class="ew-field col-amt-label"><label>Amount (₹) <span class="req">*</span></label></div>
									<div class="ew-field col-tax-label"><label>GST</label></div>
									<div class="ew-field col-tax-label"><label>TDS</label></div>
									<div class="ew-field col-tax-label"><label>Net (₹)</label></div>
									<div class="pc-line-del"></div>
								</div>
								<div id="pc_lines_body"></div>
							</div>
							<div class="pc-line-summary">
								<span id="pc_line_count_label">Total (0 expenses)</span>
								<span id="pc_lines_foot_total">0.00</span>
							</div>
							<div class="ew-form-body" style="padding-top:0;">
								<div class="ew-field">
									<label>Narration</label>
									<textarea id="narration" class="form-control" rows="2" placeholder="Optional notes"></textarea>
								</div>
						</div>
						<div class="ew-form-footer pc-form-footer">
								<div class="pc-total-wrap">
									<label>TOTAL PETTY CASH</label>
									<input type="text" id="pc_total_display" class="form-control" readonly value="0.00">
								</div>
								<div class="pc-foot-actions">
							<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_reset">Reset</button>
									<button type="submit" class="ew-btn-v2 ew-btn-v2-primary" id="btn_save">Save</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php require_once('include/footer.php'); ?>
</div>
<script>
window.PC_CATEGORIES = <?php echo json_encode($category_rows, JSON_UNESCAPED_UNICODE); ?>;
window.PC_VENDORS = <?php echo json_encode($vendor_rows, JSON_UNESCAPED_UNICODE); ?>;
window.PC_GCN_OPTIONS = <?php echo json_encode($gcn_options, JSON_UNESCAPED_UNICODE); ?>;
window.PC_TAX_PROFILES = <?php echo json_encode($tax_profiles, JSON_UNESCAPED_UNICODE); ?>;
window.PC_TDS_OPTIONS = <?php echo json_encode($tds_percent_options, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script type="text/template" id="pc_line_row_tpl">
<div class="pc-line-row ew-form-grid ew-form-grid--expense">
	<div class="ew-field pc-field-line-gcn" data-col-label="GCN No">
		<select class="form-control line-gcn" aria-label="GCN No"><option value=""></option></select>
	</div>
	<div class="ew-field pc-field-line-paid" data-col-label="Paid To">
		<select class="form-control line-vendor" aria-label="Paid To"><option value=""></option></select>
	</div>
	<div class="ew-field pc-field-line-type" data-col-label="Expense Type">
		<select class="form-control line-expense-type" aria-label="Expense Type"><option value=""></option></select>
	</div>
	<div class="ew-field pc-field-line-amt" data-col-label="Amount (₹)">
		<input type="text" class="form-control line-amount" inputmode="decimal" placeholder="0.00" aria-label="Amount">
	</div>
	<div class="ew-field pc-field-line-tax" data-col-label="GST">
		<input type="text" class="form-control line-gst" readonly tabindex="-1" placeholder="0.00" aria-label="GST">
	</div>
	<div class="ew-field pc-field-line-tax" data-col-label="TDS">
		<input type="text" class="form-control line-tds" readonly tabindex="-1" placeholder="0.00" aria-label="TDS">
	</div>
	<div class="ew-field pc-field-line-tax" data-col-label="Net (₹)">
		<input type="text" class="form-control line-net" readonly tabindex="-1" placeholder="0.00" aria-label="Net amount">
	</div>
	<div class="pc-line-del">
		<button type="button" class="btn-remove-line" title="Remove row"><i class="fa fa-trash-o"></i></button>
	</div>
</div>
</script>
<script>
(function() {
	var pettyCashId = <?php echo (int) $preselect_id; ?>;
	var pcGcnSyncTimer = null;

	function pcReleaseSelect2Mask() {
		if (!$('.select2-dropdown-open').length) {
			$('#select2-drop-mask').hide();
		}
	}

	function safeDestroySelect2($el) {
		if (!$el || !$el.length) {
			return;
		}
		try {
			if ($el.data('select2') || $el.hasClass('select2-offscreen')) {
				$el.select2('close');
				$el.select2('destroy');
			}
		} catch (ignore) {}
		pcReleaseSelect2Mask();
	}

	function expenseTypeSelectHtml(selected) {
		var html = '<option value=""></option>';
		(PC_CATEGORIES || []).forEach(function(c) {
			var sel = String(selected) === String(c.category_id) ? ' selected' : '';
			html += '<option value="' + c.category_id + '"' + sel + '>' + escapeHtml(c.label || c.category_name) + '</option>';
		});
		return html;
	}

	function vendorLabelForId(id, fallback) {
		id = String(id || '');
		if (!id) return '';
		var label = '';
		(PC_VENDORS || []).forEach(function(v) {
			if (String(v.vendor_id) === id) {
				label = v.label || v.vendor_name || '';
			}
		});
		return label || fallback || '';
	}

	function pcEnsureVendorInList(vendorId, label) {
		vendorId = String(vendorId || '');
		if (!vendorId) {
			return;
		}
		var found = false;
		(PC_VENDORS || []).forEach(function(v) {
			if (String(v.vendor_id) === vendorId) {
				found = true;
			}
		});
		if (!found) {
			var text = (label || vendorLabelForId(vendorId, label) || vendorId);
			PC_VENDORS.push({
				vendor_id: parseInt(vendorId, 10) || vendorId,
				label: text,
				vendor_name: text
			});
		}
	}

	function vendorSelectHtml(vendorId, labelHint) {
		vendorId = vendorId ? String(vendorId) : '';
		var html = '<option value=""></option>';
		var found = false;
		(PC_VENDORS || []).forEach(function(v) {
			var id = String(v.vendor_id);
			var sel = vendorId && vendorId === id ? ' selected' : '';
			if (sel) {
				found = true;
			}
			html += '<option value="' + escapeHtml(id) + '"' + sel + '>' + escapeHtml(v.label || v.vendor_name || id) + '</option>';
		});
		if (vendorId && !found) {
			var text = labelHint || vendorLabelForId(vendorId, labelHint) || vendorId;
			html += '<option value="' + escapeHtml(vendorId) + '" selected>' + escapeHtml(text) + '</option>';
		}
		return html;
	}

	function pcCloseAllSelect2Dropdowns() {
		var $opens = $('.select2-dropdown-open');
		try {
			$('#gcn_keys, #cash_type, #pc_gst_percent, #pc_tds_percent').each(function() {
				var $el = $(this);
				if ($el.data('select2')) {
					$el.select2('close');
				}
			});
			$('#pc_lines_body select').each(function() {
				var $el = $(this);
				if ($el.data('select2')) {
					$el.select2('close');
				}
			});
		} catch (ignore) {}
		$opens.removeClass('select2-dropdown-open');
		$('#select2-drop, .select2-drop').hide();
		pcReleaseSelect2Mask();
	}

	function initLineVendorSelect($vendor, data) {
		data = data || {};
		if (!$vendor || !$vendor.length || !$.fn.select2) {
			return;
		}
		if (data.vendor_id) {
			pcEnsureVendorInList(data.vendor_id, data.paid_to || '');
		}
		var selectedId = data.vendor_id ? String(data.vendor_id) : '';
		safeDestroySelect2($vendor);
		$vendor.html(vendorSelectHtml(selectedId, data.paid_to || ''));
		initSelect2($vendor, 'Select vendor');
		if (selectedId) {
			setSelectVal($vendor, selectedId);
		}
		syncSelectTitle($vendor);
	}

	function hasSelect2($el) {
		return $el && $el.length && ($el.data('select2') || $el.hasClass('select2-offscreen'));
	}

	function getSelectVal($el) {
		if (!$el || !$el.length) return '';
		var val = $el.val();
		if ((val === null || val === undefined || val === '') && $el.data('select2')) {
			val = $el.select2('val');
		} else if (hasSelect2($el)) {
			val = $el.select2('val');
		}
		if ($.isArray(val)) {
			val = val[0] || '';
		}
		return val ? String(val) : '';
	}

	function setSelectVal($el, value) {
		if (!$el || !$el.length) return;
		if (hasSelect2($el)) {
			$el.select2('val', value || '');
		} else {
			$el.val(value || '');
		}
	}

	function getMultiSelectVal($el) {
		if (!$el || !$el.length) return [];
		var val = null;
		if (hasSelect2($el)) {
			val = $el.select2('val');
		}
		if (val == null || val === '') {
			val = $el.val();
		}
		if (!val) return [];
		if (!$.isArray(val)) {
			val = [val];
		}
		return val.filter(function(v) { return v != null && String(v) !== ''; }).map(String);
	}

	function headerKeysInclude(keys, key) {
		if (!key) return false;
		var k = String(key);
		for (var i = 0; i < keys.length; i++) {
			if (String(keys[i]) === k) return true;
		}
		return false;
	}

	function setMultiSelectVal($el, values) {
		if (!$el || !$el.length) return;
		values = values || [];
		if (hasSelect2($el)) {
			$el.select2('val', values);
		} else {
			$el.val(values);
		}
	}

	function initSelect2($el, placeholder) {
		if (!$el || !$el.length || !$.fn.select2) {
			return;
		}
		safeDestroySelect2($el);
		$el.select2({
			width: '100%',
			placeholder: placeholder || 'Select',
			allowClear: true,
			minimumResultsForSearch: 0
		});
		syncSelectTitle($el);
	}

	function syncSelectTitle($select) {
		if (!$select || !$select.length) return;
		var text = $.trim($select.find('option:selected').text());
		$select.next('.select2-container').find('.select2-chosen').attr('title', text || '');
	}

	function destroyRowSelect2($row) {
		$row.find('select').each(function() {
			safeDestroySelect2($(this));
		});
	}

	function refreshLineGcnSelect($row, keys) {
		if (!isGcnExpenseMode()) {
			return;
		}
		keys = keys || getHeaderGcnKeys();
		var $gcn = $row.find('.line-gcn');
		var lineGcn = getSelectVal($gcn) || '';
		if (lineGcn && !headerKeysInclude(keys, lineGcn)) {
			lineGcn = '';
		}
		if (!lineGcn && keys.length === 1) {
			lineGcn = keys[0];
		}
		safeDestroySelect2($gcn);
		if (!keys.length) {
			$gcn.html('<option value=""></option>');
			initSelect2($gcn, 'Select GCN');
			syncSelectTitle($gcn);
			return;
		}
		$gcn.html(lineGcnOptionsHtml(keys, lineGcn));
		initSelect2($gcn, keys.length > 1 ? 'Select GCN' : 'GCN');
		if (lineGcn) {
			setSelectVal($gcn, lineGcn);
		}
		syncSelectTitle($gcn);
	}

	function isGcnExpenseMode() {
		return String(getSelectVal($('#cash_type')) || $('#cash_type').val() || 'gcn_expense') === 'gcn_expense';
	}

	function rowSelectValues($row) {
		return {
			category_id: getSelectVal($row.find('.line-expense-type')),
			vendor_id: getSelectVal($row.find('.line-vendor')),
			gcn_key: getSelectVal($row.find('.line-gcn'))
		};
	}

	function lineGcnOptionsHtml(keys, selected) {
		keys = keys || [];
		if (!keys.length) {
			return '<option value=""></option>';
		}
		if (selected && !headerKeysInclude(keys, selected)) {
			keys = keys.concat([selected]);
		}
		var html = keys.length > 1 ? '<option value=""></option>' : '';
		keys.forEach(function(k) {
			var label = gcnLabelForKey(k);
			var sel = '';
			if (keys.length === 1) {
				sel = ' selected';
			} else if (String(selected) === String(k)) {
				sel = ' selected';
			}
			html += '<option value="' + escapeHtml(k) + '"' + sel + '>' + escapeHtml(label) + '</option>';
		});
		return html;
	}

	function refreshExpenseRowSelects($row, data) {
		data = data || {};
		var isGcn = isGcnExpenseMode();
		var keys = getHeaderGcnKeys();
		var $type = $row.find('.line-expense-type');
		var $vendor = $row.find('.line-vendor');
		var $gcn = $row.find('.line-gcn');

		destroyRowSelect2($row);

		$type.html(expenseTypeSelectHtml(data.category_id || ''));
		initSelect2($type, 'Search expense type');
		initLineVendorSelect($vendor, data);

		if (isGcn && keys.length) {
			var lineGcn = data.gcn_key || '';
			if (lineGcn && !headerKeysInclude(keys, lineGcn)) {
				lineGcn = '';
			}
			if (!lineGcn && keys.length === 1) {
				lineGcn = keys[0];
			}
			$gcn.html(lineGcnOptionsHtml(keys, lineGcn));
			initSelect2($gcn, keys.length > 1 ? 'Select GCN' : 'GCN');
			if (lineGcn) {
				setSelectVal($gcn, lineGcn);
			}
			syncSelectTitle($gcn);
		} else if (isGcn) {
			$gcn.html('<option value=""></option>');
			initSelect2($gcn, 'Select GCN');
		} else {
			safeDestroySelect2($gcn);
			$gcn.html('<option value=""></option>');
		}

		if (data.category_id) {
			setSelectVal($type, data.category_id);
		}
		syncSelectTitle($type);
		syncSelectTitle($vendor);
	}

	function syncAllLineGcnDropdownsNow() {
		var keys = getHeaderGcnKeys();
		$('#pc_lines_body .pc-line-row').each(function() {
			refreshLineGcnSelect($(this), keys);
		});
		pcReleaseSelect2Mask();
	}

	function syncAllLineGcnDropdowns() {
		clearTimeout(pcGcnSyncTimer);
		pcGcnSyncTimer = setTimeout(syncAllLineGcnDropdownsNow, 60);
	}

	function getHeaderGcnKeys() {
		return getMultiSelectVal($('#gcn_keys'));
	}

	function gcnMultiSelectHtml(selectedKeys) {
		var selected = {};
		(selectedKeys || []).forEach(function(k) {
			selected[String(k)] = true;
		});
		var inList = {};
		var html = '';
		(PC_GCN_OPTIONS || []).forEach(function(o) {
			inList[String(o.key)] = true;
			var sel = selected[String(o.key)] ? ' selected' : '';
			html += '<option value="' + escapeHtml(o.key) + '"' + sel + '>' + escapeHtml(o.grn_no || o.label) + '</option>';
		});
		(selectedKeys || []).forEach(function(k) {
			var sk = String(k);
			if (!sk || inList[sk]) {
				return;
			}
			html += '<option value="' + escapeHtml(sk) + '" selected>' + escapeHtml(gcnLabelForKey(sk)) + '</option>';
		});
		return html;
	}

	function pcCacheGcnOptions(opts) {
		window.PC_GCN_LABELS = window.PC_GCN_LABELS || {};
		(opts || []).forEach(function(o) {
			if (!o || !o.key) return;
			var text = o.grn_no || o.label || String(o.key);
			window.PC_GCN_LABELS[String(o.key)] = text;
		});
	}

	function preloadPcGcnOptions(done) {
		if ((window.PC_GCN_OPTIONS || []).length) {
			if (done) done();
			return;
		}
		$.getJSON('petty_cash_data.php', { cmd: 'gcn_options', q: '' })
			.done(function(r) {
				if (r && r.status === 0) {
					window.PC_GCN_OPTIONS = r.options || [];
					pcCacheGcnOptions(PC_GCN_OPTIONS);
				}
			})
			.always(function() {
				if (done) done();
			});
	}

	function initHeaderGcnSelect(selectedKeys) {
		var $g = $('#gcn_keys');
		if (!$g.length || !$.fn.select2) {
			return;
		}
		selectedKeys = selectedKeys || [];
		safeDestroySelect2($g);
		var cfg = {
			width: '100%',
			allowClear: true,
			closeOnSelect: false,
			placeholder: 'Search GCN number(s)',
			minimumResultsForSearch: 0
		};
		var hasStaticList = (PC_GCN_OPTIONS || []).length > 0;
		if (hasStaticList || selectedKeys.length) {
			$g.html(gcnMultiSelectHtml(selectedKeys));
			$g.select2(cfg);
			if (selectedKeys.length) {
				setMultiSelectVal($g, selectedKeys);
			}
		} else {
			$g.html('');
			cfg.minimumInputLength = 0;
			cfg.ajax = {
				url: 'petty_cash_data.php',
				dataType: 'json',
				quietMillis: 300,
				data: function(term) {
					return { cmd: 'gcn_options', q: (term == null ? '' : term) };
				},
				results: function(resp) {
					var opts = (resp && resp.status === 0 && resp.options) ? resp.options : [];
					pcCacheGcnOptions(opts);
					return {
						results: opts.map(function(o) {
							return { id: o.key, text: o.grn_no || o.label || o.key };
						})
					};
				}
			};
			cfg.initSelection = function(element, callback) {
				callback([]);
			};
			$g.select2(cfg);
		}
		$g.off('.pcheader').on(
			'change.pcheader select2-selecting.pcheader select2-removed.pcheader',
			syncAllLineGcnDropdowns
		);
	}

	function initHeaderGcnIfNeeded() {
		if (!isGcnExpenseMode()) {
			return;
		}
		var $g = $('#gcn_keys');
		if (!$g.length || !$.fn.select2) {
			return;
		}
		var keys = getMultiSelectVal($g);
		preloadPcGcnOptions(function() {
			initHeaderGcnSelect(keys);
			syncAllLineGcnDropdownsNow();
		});
	}

	function destroyHeaderGcnSelect() {
		var $g = $('#gcn_keys');
		if (!$g.length) {
			return;
		}
		safeDestroySelect2($g);
		$g.val([]);
	}

	function initCashTypeSelect() {
		var $ct = $('#cash_type');
		if (!$ct.length || !$.fn.select2) {
			return;
		}
		initSelect2($ct, 'Select petty cash type');
		var def = $ct.find('option[selected]').attr('value') || 'gcn_expense';
		setSelectVal($ct, def);
		syncSelectTitle($ct);
		$ct.off('change.pc').on('change.pc', toggleCashTypeUi);
	}

	function gcnLabelForKey(key) {
		if (!key) return '';
		var sk = String(key);
		if (window.PC_GCN_LABELS && window.PC_GCN_LABELS[sk]) {
			return window.PC_GCN_LABELS[sk];
		}
		var label = sk;
		(PC_GCN_OPTIONS || []).forEach(function(o) {
			if (String(o.key) === sk) {
				label = o.grn_no || o.label || label;
			}
		});
		return label;
	}

	function initGcnSelect2($gcn, selectedKey) {
		if (!$gcn || !$gcn.length || !$.fn.select2) {
			return;
		}
		try {
			if (hasSelect2($gcn)) {
				$gcn.select2('destroy');
			}
		} catch (ignore) {}

		setGcnSelectOptions($gcn, selectedKey || '');
		$gcn.select2({
			width: '100%',
			placeholder: 'Search GCN number',
			allowClear: true,
			minimumResultsForSearch: 0
		});

		if (selectedKey) {
			setSelectVal($gcn, selectedKey);
		}
		syncSelectTitle($gcn);
	}

	var _gcnOptionsHtmlCache = null;
	function allGcnOptionsInnerHtml() {
		if (_gcnOptionsHtmlCache === null) {
			var parts = [];
			(PC_GCN_OPTIONS || []).forEach(function(o) {
				parts.push('<option value="' + escapeHtml(o.key) + '">' + escapeHtml(o.grn_no || o.label) + '</option>');
			});
			_gcnOptionsHtmlCache = parts.join('');
		}
		return _gcnOptionsHtmlCache;
	}

	function setGcnSelectOptions($gcn, selectedKey) {
		$gcn.html('<option value=""></option>' + allGcnOptionsInnerHtml());
		if (selectedKey) {
			$gcn.val(selectedKey);
		}
	}

	function escapeHtml(s) {
		return String(s || '').replace(/[&<>"']/g, function(c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function parseMoney(v) {
		if (v === null || v === undefined || v === '') return 0;
		return parseFloat(String(v).replace(/,/g, '')) || 0;
	}

	function formatMoney(v) {
		return parseMoney(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}

	function rateKey(v) {
		return parseMoney(v).toFixed(2);
	}

	function gstRateOptions() {
		var seen = {};
		var rows = [{ value: '0.00', label: '0%' }];
		(PC_TAX_PROFILES || []).forEach(function(row) {
			var key = rateKey(row.gst_rate);
			if (seen[key]) return;
			seen[key] = true;
			rows.push({ value: key, label: parseMoney(row.gst_rate) + '%' });
		});
		return rows;
	}

	function fillPcSelectOptions($el, rows, valueKey, labelKey, selected) {
		var html = '';
		(rows || []).forEach(function(row) {
			var val = row[valueKey];
			var sel = rateKey(selected) === rateKey(val) ? ' selected' : '';
			html += '<option value="' + escapeHtml(val) + '"' + sel + '>' + escapeHtml(row[labelKey] || val) + '</option>';
		});
		$el.html(html);
	}

	function buildPcGstSelect(selected) {
		var $el = $('#pc_gst_percent');
		if (!$el.length) return;
		safeDestroySelect2($el);
		fillPcSelectOptions($el, gstRateOptions(), 'value', 'label', selected != null ? selected : 0);
		initSelect2($el, 'GST %');
		setSelectVal($el, rateKey(selected != null ? selected : 0));
	}

	function buildPcTdsSelect(selected) {
		var rows = (PC_TDS_OPTIONS && PC_TDS_OPTIONS.length) ? PC_TDS_OPTIONS : [{ value: '0.00', label: 'No TDS (0%)' }];
		var pick = (selected !== undefined && selected !== null && selected !== '') ? rateKey(selected) : '0.00';
		var $el = $('#pc_tds_percent');
		if (!$el.length) return;
		safeDestroySelect2($el);
		fillPcSelectOptions($el, rows, 'value', 'label', pick);
		initSelect2($el, 'TDS %');
		setSelectVal($el, pick);
	}

	function recalcPcRow($row, applyHeaderDefaults) {
		if (isGcnExpenseMode()) {
			$row.find('.line-gst, .line-tds, .line-net').val('');
			return;
		}
		var amount = parseMoney($row.find('.line-amount').val());
		if (amount <= 0) {
			$row.find('.line-gst, .line-tds, .line-net').val('');
			recalcTotal();
			return;
		}
		var vendorId = getSelectVal($row.find('.line-vendor')) || 0;
		var categoryId = getSelectVal($row.find('.line-expense-type')) || 0;
		$.getJSON('petty_cash_data.php', {
			cmd: 'calc_amounts',
			vendor_id: vendorId,
			category_id: categoryId,
			expense_amount: amount,
			gst_percent: getSelectVal($('#pc_gst_percent')),
			tds_percent: getSelectVal($('#pc_tds_percent'))
		}, function(r) {
			if (!r || r.status !== 0) return;
			if (applyHeaderDefaults && vendorId > 0) {
				if (r.suggested_tds_rate > 0) {
					setSelectVal($('#pc_tds_percent'), rateKey(r.suggested_tds_rate));
				}
				if (r.gst_percent > 0 && parseMoney(getSelectVal($('#pc_gst_percent'))) === 0) {
					setSelectVal($('#pc_gst_percent'), rateKey(r.gst_percent));
				}
			}
			$row.find('.line-gst').val(formatMoney(r.gst_amount));
			$row.find('.line-tds').val(formatMoney(r.tds_amount));
			$row.find('.line-net').val(formatMoney(r.net_amount));
			recalcTotal();
		});
	}

	function recalcAllPcRows(applyHeaderDefaults) {
		$('#pc_lines_body .pc-line-row').each(function() {
			recalcPcRow($(this), applyHeaderDefaults);
		});
	}

	function applyCashTypeChrome() {
		var isGcn = isGcnExpenseMode();
		$('#pc_details_card').toggleClass('pc-details-gcn', isGcn).toggleClass('pc-details-general', !isGcn);
		$('#pc_expense_card').toggleClass('pc-mode-gcn', isGcn).toggleClass('pc-general-expense', !isGcn);
		$('#pc_lines_title').text(isGcn ? 'GCN expense' : 'General expense');
	}

	function refreshAllExpenseRowsForMode() {
		$('#pc_lines_body .pc-line-row').each(function() {
			var $row = $(this);
			var vals = rowSelectValues($row);
			var $vendor = $row.find('.line-vendor');
			var paidTo = $.trim($vendor.find('option:selected').text()) || '';
			refreshExpenseRowSelects($row, {
				category_id: vals.category_id,
				vendor_id: vals.vendor_id,
				paid_to: paidTo,
				gcn_key: isGcnExpenseMode() ? vals.gcn_key : '',
				amount: $row.find('.line-amount').val()
			});
			if (!isGcnExpenseMode()) {
				recalcPcRow($row, false);
			}
		});
		renumberLines();
		recalcTotal();
	}

	function ensurePcExpenseLine() {
		if (!$('#pc_lines_body .pc-line-row').length) {
			addLine({});
		} else {
			refreshAllExpenseRowsForMode();
		}
		renumberLines();
		recalcTotal();
	}

	function toggleCashTypeUi() {
		pcCloseAllSelect2Dropdowns();
		var isGcn = isGcnExpenseMode();
		var savedGst = getSelectVal($('#pc_gst_percent'));
		var savedTds = getSelectVal($('#pc_tds_percent'));
		applyCashTypeChrome();
		if (!$('#pc_lines_body .pc-line-row').length) {
			addLine({});
		} else {
			refreshAllExpenseRowsForMode();
		}
		if (isGcn) {
			var keys = getMultiSelectVal($('#gcn_keys'));
			if (!$('#gcn_keys').data('select2')) {
				preloadPcGcnOptions(function() {
					initHeaderGcnSelect(keys);
					syncAllLineGcnDropdownsNow();
				});
			} else {
				syncAllLineGcnDropdownsNow();
			}
		} else {
			buildPcGstSelect(savedGst !== '' ? savedGst : 0);
			buildPcTdsSelect(savedTds !== '' ? savedTds : '');
			recalcAllPcRows(false);
		}
		renumberLines();
		recalcTotal();
		pcCloseAllSelect2Dropdowns();
	}

	function renumberLines() {
		var $rows = $('#pc_lines_body .pc-line-row');
		$rows.find('.btn-remove-line').toggle($rows.length > 1);
		$rows.find('.pc-line-del').toggle($rows.length > 1);
	}

	function recalcTotal() {
		var total = 0, n = 0;
		var useNet = !isGcnExpenseMode();
		$('#pc_lines_body .pc-line-row').each(function() {
			var $row = $(this);
			var amt = parseMoney($row.find('.line-amount').val());
			if (amt <= 0) return;
			n++;
			total += useNet ? parseMoney($row.find('.line-net').val()) : amt;
		});
		total = Math.round(total * 100) / 100;
		$('#pc_total_display').val(total.toFixed(2));
		$('#pc_lines_foot_total').text(total.toFixed(2));
		$('#pc_line_count_label').text('Total (' + n + ' expense' + (n === 1 ? '' : 's') + ')');
	}

	function addLine(data) {
		data = data || {};
		var tpl = $('#pc_line_row_tpl').html();
		if (!tpl) {
			return;
		}
		var $row = $(tpl.trim());
		$row.find('.line-amount').val(data.amount_raw != null ? data.amount_raw : (data.amount || ''));
		$('#pc_lines_body').append($row);
		try {
			refreshExpenseRowSelects($row, data);
		} catch (err) {
			console.error('Petty cash row init failed', err);
		}
		if (!isGcnExpenseMode()) {
			if (data.gst_amount_raw != null || data.gst_amount) {
				$row.find('.line-gst').val(data.gst_amount || formatMoney(data.gst_amount_raw || 0));
				$row.find('.line-tds').val(data.tds_amount || formatMoney(data.tds_amount_raw || 0));
				$row.find('.line-net').val(data.net_amount || formatMoney(data.net_amount_raw || 0));
			} else {
				recalcPcRow($row, false);
			}
		}
		renumberLines();
		recalcTotal();
	}

	function collectLines() {
		var lines = [];
		$('#pc_lines_body .pc-line-row').each(function() {
			var $row = $(this);
			var amt = parseFloat(String($row.find('.line-amount').val() || '').replace(/,/g, ''));
			if (isNaN(amt) || amt <= 0) return;
			var $vendor = $row.find('.line-vendor');
			lines.push({
				expense_date: $('#voucher_date').val(),
				category_id: getSelectVal($row.find('.line-expense-type')) || 0,
				vendor_id: getSelectVal($vendor) || 0,
				paid_to: $.trim($vendor.find('option:selected').text()) || '',
				amount: amt,
				gst_percent: parseMoney(getSelectVal($('#pc_gst_percent'))),
				gst_amount: parseMoney($row.find('.line-gst').val()),
				tds_amount: parseMoney($row.find('.line-tds').val()),
				net_amount: parseMoney($row.find('.line-net').val()),
				remarks: '',
				gcn_key: (function() {
					if (!isGcnExpenseMode()) return '';
					var hdr = getHeaderGcnKeys();
					var lk = getSelectVal($row.find('.line-gcn')) || '';
					if (!lk && hdr.length === 1) lk = hdr[0];
					return lk;
				})()
			});
		});
		return lines;
	}

	function fillForm(data) {
		pettyCashId = parseInt(data.petty_cash_id, 10) || 0;
		window.PC_GCN_LABELS = {};
		(data.gcn_links || []).forEach(function(l) {
			if (l.key && l.grn_no) {
				window.PC_GCN_LABELS[String(l.key)] = l.grn_no;
			}
		});
		(data.lines || []).forEach(function(l) {
			if (l.gcn_key && l.gcn_no) {
				window.PC_GCN_LABELS[String(l.gcn_key)] = l.gcn_no;
			}
			if (l.vendor_id) {
				pcEnsureVendorInList(l.vendor_id, l.paid_to || l.vendor_name || '');
			}
		});
		$('#voucher_no_display').text(data.voucher_no || '—');
		$('#voucher_date').val(data.voucher_date || '');
		var cashType = data.cash_type || 'gcn_expense';
		setSelectVal($('#cash_type'), cashType);
		syncSelectTitle($('#cash_type'));
		$('#narration').val(data.narration || '');
		applyCashTypeChrome();
		buildPcGstSelect(data.gst_percent != null ? data.gst_percent : 0);
		buildPcTdsSelect(data.tds_percent != null ? data.tds_percent : '');
		var gcnKeys = data.gcn_keys || [];
		$('#pc_lines_body .pc-line-row').each(function() { destroyRowSelect2($(this)); });
		$('#pc_lines_body').empty();
		(data.lines || []).forEach(function(ln) { addLine(ln); });
		if (!(data.lines || []).length) {
			addLine({});
		}
		if (cashType === 'gcn_expense') {
			preloadPcGcnOptions(function() {
				initHeaderGcnSelect(gcnKeys);
				syncAllLineGcnDropdownsNow();
				recalcTotal();
			});
		} else {
			recalcAllPcRows(false);
			recalcTotal();
		}
	}

	$(function() {
		$('.loading-page').hide();
		initCashTypeSelect();
		function pcBootNewForm() {
			applyCashTypeChrome();
			addLine({});
			if (isGcnExpenseMode()) {
				initHeaderGcnIfNeeded();
			} else {
				buildPcGstSelect(0);
				buildPcTdsSelect('');
			}
		}
		$(document).on('click', '#btn_add_line', function(e) {
			e.preventDefault();
			e.stopPropagation();
			addLine({});
		});
		$(document).on('click', '.btn-remove-line', function() {
			if ($('#pc_lines_body .pc-line-row').length <= 1) {
				alert('At least one line is required.');
				return;
			}
			var $row = $(this).closest('.pc-line-row');
			destroyRowSelect2($row);
			$row.remove();
			renumberLines();
			recalcTotal();
		});
		$(document).on('change', '.line-vendor, .line-expense-type, .line-gcn', function() {
			var $el = $(this);
			syncSelectTitle($el);
			if (!isGcnExpenseMode() && $el.hasClass('line-vendor')) {
				recalcPcRow($el.closest('.pc-line-row'), true);
			} else if (!isGcnExpenseMode() && $el.hasClass('line-expense-type')) {
				recalcPcRow($el.closest('.pc-line-row'), false);
			}
		});
		$(document).on('input change', '.line-amount', function() {
			var $row = $(this).closest('.pc-line-row');
			if (!isGcnExpenseMode()) {
				recalcPcRow($row, false);
			} else {
				recalcTotal();
			}
		});
		$('#pc_gst_percent, #pc_tds_percent').on('change', function() {
			syncSelectTitle($(this));
			recalcAllPcRows(false);
		});

		if (pettyCashId > 0) {
			var fetchParams = { cmd: 'fetch', petty_cash_id: pettyCashId };
			<?php if ($preselect_no !== '') { ?>
			fetchParams.voucher_no = <?php echo json_encode($preselect_no, JSON_UNESCAPED_UNICODE); ?>;
			<?php } ?>
			$.getJSON('petty_cash_data.php', fetchParams)
				.done(function(r) {
				if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Could not load petty cash.');
					return;
				}
					try {
				fillForm(r);
					} catch (err) {
						console.error('Petty cash fillForm failed', err);
						alert('Could not display petty cash on screen. Please refresh and try again.');
					}
				})
				.fail(function() {
					alert('Could not load petty cash. Please try again.');
				})
				.always(function() {
					$('.loading-page').hide();
				});
		} else {
			pcBootNewForm();
		}

		$('#btn_reset').on('click', function() {
			if (!confirm('Reset form?')) return;
			window.location.reload();
		});

		$('#petty_cash_form').on('submit', function(e) {
			e.preventDefault();
			var lines = collectLines();
			if (!lines.length) {
				alert('Add at least one expense line with amount.');
				return;
			}
			var cashType = getSelectVal($('#cash_type')) || $('#cash_type').val();
			if (cashType === 'gcn_expense') {
				var hdr = getHeaderGcnKeys();
				if (!hdr.length) {
					alert('Select one or more GCNs in Petty Cash Details.');
					return;
				}
				for (var li = 0; li < lines.length; li++) {
					if (!lines[li].gcn_key) {
						alert(hdr.length > 1
							? 'Select a GCN on each expense line.'
							: 'Each expense line must use the header GCN.');
				return;
			}
					if (!headerKeysInclude(hdr, lines[li].gcn_key)) {
						alert('Line GCN must be one of the GCNs selected in Petty Cash Details.');
				return;
					}
				}
			}
			$('#btn_save').prop('disabled', true);
			$.post('petty_cash_data.php', {
				cmd: 'save',
				petty_cash_id: pettyCashId,
				voucher_date: $('#voucher_date').val(),
				cash_type: getSelectVal($('#cash_type')) || $('#cash_type').val(),
				narration: $('#narration').val(),
				gcn_keys: JSON.stringify(getHeaderGcnKeys()),
				gst_percent: getSelectVal($('#pc_gst_percent')),
				tds_percent: getSelectVal($('#pc_tds_percent')),
				lines: JSON.stringify(lines)
			}, function(r) {
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