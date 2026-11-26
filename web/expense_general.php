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
$preview_expense_no = expense_general_preview_next_no($conn);
$voucher_type_form_options = expense_general_voucher_type_form_options();
$default_voucher_type = 'purchase_bill';
if ($is_create && !empty($_GET['voucher_type'])) {
	$vt_req = strtolower(trim((string) $_GET['voucher_type']));
	if (isset($voucher_type_form_options[$vt_req])) {
		$default_voucher_type = $vt_req;
	}
}
$list_return_tab = 'purchase_bill';
if (!empty($_GET['list_tab']) && in_array($_GET['list_tab'], array('purchase_bill', 'general_expense'), true)) {
	$list_return_tab = (string) $_GET['list_tab'];
} elseif ($default_voucher_type === 'general_expense') {
	$list_return_tab = 'general_expense';
}
$list_return_url = 'expense_general_list.php?tab=' . urlencode($list_return_tab);
$voucher_type_all_options = expense_general_voucher_type_options();
$payment_modes = ew_company_bank_payment_modes();
$vendor_rows = expense_gcn_vendor_options($conn);
$category_rows = expense_gcn_category_options($conn);
$bank_rows = ew_company_bank_options($conn);
$tax_profiles = expense_general_tax_profiles($conn);
require_once('include/expense_vendor_gst_helpers.php');
$gst_state_options = expense_vendor_gst_state_options($conn);
$company_state_id = gst_register_company_state($conn);
$gst_state_by_id = array();
foreach ($gst_state_options as $st) {
	$gst_state_by_id[(int) ($st['state_id'] ?? 0)] = (string) ($st['state_name'] ?? '');
}
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
			margin: 0 !important;
			padding: 14px 24px 22px !important;
			width: 100%;
			max-width: 100%;
			box-sizing: border-box;
			overflow: visible;
		}

		.egen-net-payable {
			display: flex;
			align-items: center;
			gap: 10px;
			flex: 1 1 auto;
			min-width: 0;
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
			max-width: 100%;
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
			justify-content: flex-end;
			gap: 8px;
			flex: 0 0 auto;
			flex-wrap: wrap;
			margin-left: auto;
			max-width: 100%;
		}

		.egen-form-footer-actions .ew-btn-v2 {
			flex-shrink: 0;
			white-space: nowrap;
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
				padding: 14px 16px 18px !important;
			}

			.egen-net-payable {
				max-width: none;
				width: 100%;
				justify-content: space-between;
			}

			.egen-form-footer-actions {
				justify-content: stretch;
				margin-left: 0;
				width: 100%;
			}

			.egen-form-footer-actions .ew-btn-v2 {
				flex: 1 1 auto;
				min-width: 0;
			}

			#net_payable_display {
				flex: 1;
				max-width: 200px;
			}
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
			min-width: 110px;
		}

		#pv_lines thead th.pv-col-num,
		#pv_lines tbody td.pv-col-num {
			text-align: right !important;
		}

		#pv_lines tbody td.pv-col-num .form-control {
			text-align: right !important;
		}

		#pv_lines .pv-col-gcn {
			display: none !important;
		}

		#pv_gst_tds_fields.is-hidden {
			display: none !important;
		}

		.pv-voucher-layout {
			grid-column: span 3;
			display: grid;
			grid-template-columns: minmax(0, 1fr);
			gap: 20px 24px;
			align-items: start;
			width: 100%;
		}

		.pv-voucher-layout.is-purchase-bill,
		.pv-voucher-layout.is-general-expense {
			grid-template-columns: minmax(0, 1fr) minmax(260px, 300px);
		}

		.pv-voucher-fields {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 20px 16px;
			align-content: start;
			min-width: 0;
		}

		@media (min-width: 992px) {
			.pv-voucher-layout.is-general-expense .pv-voucher-fields,
			.pv-voucher-layout.is-purchase-bill .pv-voucher-fields {
				grid-template-columns: repeat(3, minmax(0, 1fr));
			}
		}

		@media (max-width: 767px) {
			.pv-voucher-layout.is-purchase-bill {
				grid-template-columns: 1fr;
			}
		}

		#pv_bill_settlement,
		#pv_general_gst_card {
			border: 1px solid var(--ew-border-light, #E9ECF0);
			border-radius: 8px;
			padding: 12px 14px;
			background: #FAFBFC;
			width: 100%;
			box-sizing: border-box;
		}
		#pv_general_gst_card .pv-gst-card-title {
			font-size: 12px;
			font-weight: 600;
			color: #334155;
			margin-bottom: 12px;
		}
		#pv_general_gst_card .pv-gst-card-pos {
			margin-bottom: 12px;
			padding-bottom: 10px;
			border-bottom: 1px dashed #E2E8F0;
		}
		#pv_general_gst_card .pv-gst-card-pos strong {
			display: block;
			font-size: 13px;
			color: #0F172A;
			line-height: 1.35;
		}
		#pv_general_gst_card .pv-gst-card-pos .lbl {
			display: block;
			font-size: 10px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #64748B;
			margin-bottom: 4px;
		}
		#pv_general_gst_card .pv-supply-badge {
			display: inline-block;
			margin-top: 6px;
			font-size: 10px;
			font-weight: 600;
			padding: 2px 8px;
			border-radius: 999px;
			background: #EEF2FF;
			color: #4338CA;
		}
		.pv-bill-settle-grid {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.pv-bill-settle-item {
			display: flex;
			align-items: baseline;
			justify-content: space-between;
			gap: 16px;
		}
		.pv-bill-settle-item span {
			flex: 1 1 auto;
			font-size: 11px;
			color: #64748b;
			text-transform: uppercase;
			letter-spacing: .04em;
		}
		.pv-bill-settle-item strong {
			flex: 0 0 auto;
			font-size: 14px;
			font-weight: 700;
			color: #0f172a;
			text-align: right;
			font-variant-numeric: tabular-nums;
			min-width: 7.5rem;
		}
		.pv-bill-pay-history {
			margin-top: 12px;
			padding-top: 12px;
			border-top: 1px solid #e2e8f0;
		}
		.pv-bill-pay-history-title {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin: 0 0 8px;
		}
		.pv-bill-pay-history table {
			width: 100%;
			border-collapse: collapse;
			font-size: 12px;
		}
		.pv-bill-pay-history th,
		.pv-bill-pay-history td {
			padding: 6px 8px;
			border: 1px solid #e2e8f0;
			text-align: left;
		}
		.pv-bill-pay-history th {
			background: #f1f5f9;
			font-weight: 700;
		}
		.pv-bill-pay-history td.num {
			text-align: right;
			white-space: nowrap;
		}
		.pv-bill-pay-history tr.is-current td {
			background: #eff6ff;
			font-weight: 700;
		}
		#pv_lines .pv-col-freight {
			display: none;
		}
		#pv_lines.is-purchase-bill .pv-col-freight {
			display: table-cell;
		}
		#pv_lines.is-purchase-bill .pv-col-gcn {
			display: none;
		}
		#pv_lines.is-purchase-bill .line-freight {
			background: #f1f5f9;
			cursor: not-allowed;
		}
		#pv_lines .pv-th-amount-bill {
			display: none;
		}
		#pv_lines.is-purchase-bill .pv-th-amount-general {
			display: none;
		}
		#pv_lines.is-purchase-bill .pv-th-amount-bill {
			display: table-cell;
		}
		#pv_lines.is-gcn .pv-col-gcn {
			display: table-cell !important;
			min-width: 240px;
		}

		#pv_lines.is-gcn .pv-col-gcn .select2-container {
			width: 100% !important;
			min-width: 220px;
		}

		.pv-lines td.pv-col-action {
			min-width: 72px;
			width: 72px;
			text-align: center;
			white-space: nowrap;
		}

		.pv-lines td.pv-col-action .act-link + .act-link {
			margin-left: 4px;
		}

		#pv_lines tr.line-locked input:not(.line-gst):not(.line-tds):not(.line-net),
		#pv_lines tr.line-locked .select2-choice {
			background: #f1f5f9 !important;
			cursor: default;
		}

		.pv-lines input.form-control {
			height: 32px;
		}

		.pv-lines .line-gst,
		.pv-lines .line-tds,
		.pv-lines .line-net {
			background: #F3F7FB;
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
								<a href="<?php echo htmlspecialchars($list_return_url); ?>" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php echo $preselect_id > 0 ? 'Edit Payment Voucher' : 'Add Payment Voucher'; ?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="<?php echo htmlspecialchars($list_return_url); ?>" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
								<a href="company_bank.php" class="ew-btn-v2 ew-btn-v2-outline">Company Banks</a>
							</div>
						</div>
						<div class="ew-card">
							<div id="ew-expense-panel-general">
							<div class="ew-form-body">
							<form id="general_expense_form">
								<div class="ew-form-grid ew-form-grid--general">
									<div class="ew-section-label">Voucher Details</div>

									<div class="pv-voucher-layout" id="pv_voucher_layout">
										<div class="pv-voucher-fields">
											<div class="ew-field pv-f-code">
												<label class="control-label">Voucher Code :</label>
												<input type="text" id="expense_no_display" class="form-control" readonly value="<?php echo $preselect_id > 0 ? '' : htmlspecialchars($preview_expense_no); ?>">
											</div>
											<div class="ew-field pv-f-date">
												<label class="control-label">Voucher Date <span style="color:red;">*</span> :</label>
												<input type="text" id="expense_date" name="expense_date" class="form-control ew-date-field" value="<?php echo htmlspecialchars($c_date); ?>" data-ew-datepicker="1" data-date-format="dd-mm-yyyy" autocomplete="off">
											</div>
											<div class="ew-field pv-f-type">
												<label class="control-label">Voucher Type <span style="color:red;">*</span> :</label>
												<div class="egen-select-wrap">
													<select id="voucher_type" name="voucher_type" class="form-control egen-select">
														<?php foreach ($voucher_type_form_options as $vt_key => $vt_label) { ?>
															<option value="<?php echo htmlspecialchars($vt_key); ?>"<?php echo $vt_key === $default_voucher_type ? ' selected' : ''; ?>>
																<?php echo htmlspecialchars($vt_label); ?>
															</option>
														<?php } ?>
													</select>
												</div>
											</div>
											<div class="ew-field pv-f-vendor">
												<label class="control-label">Vendor :</label>
												<div class="egen-select-wrap">
													<select id="vendor_id" name="vendor_id" class="form-control egen-select"></select>
												</div>
											</div>
											<div class="ew-field pv-f-bill" id="vendor_bill_wrap" style="display:none;">
												<label class="control-label">Purchase Bill <span style="color:red;">*</span> :</label>
												<div class="egen-select-wrap">
													<select id="vendor_bill_id" name="vendor_bill_id" class="form-control egen-select"></select>
												</div>
											</div>

											<div id="pv_gst_tds_fields" class="ew-field-span-tax" style="display:contents;">
												<div class="ew-field pv-f-gst">
													<label class="control-label">GST % :</label>
													<div class="egen-select-wrap">
														<select id="gst_percent" name="gst_percent" class="form-control egen-select"></select>
													</div>
												</div>
												<div class="ew-field pv-f-tds">
													<label class="control-label">TDS % :</label>
													<div class="egen-select-wrap">
														<select id="tds_percent" name="tds_percent" class="form-control egen-select"></select>
													</div>
												</div>
											</div>

											<div class="ew-field pv-f-inv-date pv-general-invoice-field" id="pv_field_vendor_invoice_date" style="display:none;">
												<label class="control-label">Vendor invoice date</label>
												<input type="text" id="vendor_invoice_date" name="vendor_invoice_date" class="form-control ew-date-field" data-ew-datepicker="1" data-date-format="dd-mm-yyyy" autocomplete="off" placeholder="Optional">
											</div>
											<div class="ew-field pv-f-inv-no pv-general-invoice-field" id="pv_field_vendor_invoice_no" style="display:none;">
												<label class="control-label">Vendor invoice no.</label>
												<input type="text" id="vendor_invoice_no" name="vendor_invoice_no" class="form-control" maxlength="100" autocomplete="off" placeholder="Optional">
											</div>
										</div>

										<aside id="pv_general_gst_card" class="pv-voucher-bill-card" style="display:none;" aria-labelledby="pv_gst_card_title">
											<div class="pv-gst-card-title" id="pv_gst_card_title">GST on vendor invoice</div>
											<div class="pv-gst-card-pos">
												<span class="lbl">Place of supply</span>
												<strong id="pv_pos_disp">—</strong>
												<span class="pv-supply-badge" id="pv_supply_badge" style="display:none;"></span>
												<input type="hidden" id="pos_state_id" name="pos_state_id" value="">
											</div>
											<div class="pv-bill-settle-grid">
												<div class="pv-bill-settle-item"><span>Taxable amount</span><strong id="pv_taxable_disp">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>CGST</span><strong id="pv_cgst_disp">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>SGST</span><strong id="pv_sgst_disp">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>IGST</span><strong id="pv_igst_disp">0.00</strong></div>
											</div>
											<div class="ew-field" style="margin-top:12px;margin-bottom:0;">
												<label class="control-label" style="display:flex;align-items:center;gap:8px;">
													<input type="checkbox" id="rcm_applicable" name="rcm_applicable" value="1"> Reverse charge (RCM)
												</label>
												<input type="text" id="rcm_gst_amount" name="rcm_gst_amount" class="form-control text-right" placeholder="RCM GST amount" style="margin-top:6px;display:none;">
											</div>
										</aside>

										<div id="pv_bill_settlement" class="pv-voucher-bill-card" style="display:none;">
											<div class="pv-bill-settle-grid">
												<div class="pv-bill-settle-item"><span>Total Freight</span><strong id="pb_total_freight">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>Bill Total Paid</span><strong id="pb_already_paid">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>Debit Note Adjustment</span><strong id="pb_debit_note">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>Balance Payable</span><strong id="pb_outstanding">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>Taxable freight</span><strong id="pb_taxable_ref">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>CGST</span><strong id="pb_cgst_ref">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>SGST</span><strong id="pb_sgst_ref">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>IGST</span><strong id="pb_igst_ref">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>Invoice total</span><strong id="pb_invoice_total_ref">0.00</strong></div>
												<div class="pv-bill-settle-item"><span>TDS (on bill)</span><strong id="pb_tds_ref">0.00</strong></div>
											</div>
											<div id="pb_payment_history_wrap" class="pv-bill-pay-history" style="display:none;">
												<p class="pv-bill-pay-history-title">Payments on this purchase bill</p>
												<table>
													<thead>
														<tr>
															<th>Voucher</th>
															<th>Date</th>
															<th class="num">Amount</th>
														</tr>
													</thead>
													<tbody id="pb_payment_history_body"></tbody>
												</table>
											</div>
										</div>
									</div>

									<div class="ew-field span-3">
										<div class="pv-lines-head">
											<label class="control-label" style="margin:0;">Lines</label>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_add_line"><i class="fa fa-plus"></i> Add line</button>
										</div>
										<div class="pv-lines-wrap">
											<table class="table table-bordered pv-lines" id="pv_lines">
												<thead>
													<tr>
														<th class="pv-col-gcn">GCN No.</th>
														<th class="pv-col-type">Expense Type</th>
														<th class="pv-col-freight pv-col-num">Freight Amount</th>
														<th class="pv-th-amount-general pv-col-num">Expense Amount</th>
														<th class="pv-th-amount-bill pv-col-num">Current Payment Amount</th>
														<th class="pv-col-num">GST Amount</th>
														<th class="pv-col-num">TDS Amount</th>
														<th class="pv-col-num">Other Deduction</th>
														<th class="pv-col-num">Net Amount</th>
														<th class="pv-col-action"></th>
													</tr>
												</thead>
												<tbody id="pv_lines_body"></tbody>
											</table>
										</div>
									</div>

									<div class="ew-field span-3">
										<label class="control-label">Description :</label>
										<textarea id="description" name="description" class="form-control" maxlength="500" rows="3" placeholder="Voucher notes"></textarea>
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
									<button type="submit" class="ew-btn-v2 ew-btn-v2-primary" id="btn_save" form="general_expense_form"><i class="fa fa-save"></i> <?php echo $preselect_id > 0 ? 'Update' : 'Submit'; ?></button>
								</div>
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
		var generalExpenseId = <?php echo (int) $preselect_id; ?>;
		var isVoucherEdit = generalExpenseId > 0;
		window.PV_VOUCHER_TYPE_FORM = <?php echo json_encode($voucher_type_form_options, JSON_UNESCAPED_UNICODE); ?>;
		window.PV_VOUCHER_TYPE_ALL = <?php echo json_encode($voucher_type_all_options, JSON_UNESCAPED_UNICODE); ?>;
		var nextExpenseNoPreview = <?php echo json_encode($preview_expense_no, JSON_UNESCAPED_UNICODE); ?>;
		var vendorOptions = <?php echo json_encode($vendor_rows, JSON_UNESCAPED_UNICODE); ?>;
		var categoryOptions = <?php echo json_encode($category_rows, JSON_UNESCAPED_UNICODE); ?>;
		var bankOptions = <?php echo json_encode($bank_rows, JSON_UNESCAPED_UNICODE); ?>;
		var taxProfiles = <?php echo json_encode($tax_profiles, JSON_UNESCAPED_UNICODE); ?>;
		var gcnOptions = [];
		var gcnOptionsLoaded = false;
		var tdsOptions = <?php echo json_encode($tds_percent_options, JSON_UNESCAPED_UNICODE); ?>;
		var primaryBankId = 0;
		var purchaseBillSnap = null;
		var companyStateId = <?php echo (int) $company_state_id; ?>;
		var gstStateById = <?php echo json_encode($gst_state_by_id, JSON_UNESCAPED_UNICODE); ?>;
		var vendorStateById = {};
		$.each(vendorOptions || [], function(i, row) {
			if (row && row.vendor_id) vendorStateById[String(row.vendor_id)] = parseInt(row.state, 10) || 0;
		});

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
		function rateKey(v) {
			return parseMoney(v).toFixed(2);
		}
		function getSelectVal($el) {
			if (!$el || !$el.length) return '';
			var val = $el.val();
			if ((val === null || val === undefined || val === '') && $el.data('select2')) {
				val = $el.select2('val');
			}
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
		function voucherType() {
			return String(getSelectVal($('#voucher_type')) || 'purchase_bill').toLowerCase();
		}
		function isPurchaseBill() {
			return voucherType() === 'purchase_bill';
		}
		function gstRates() {
			var seen = {};
			var rows = [];
			$.each(taxProfiles || [], function(i, row) {
				var key = rateKey(row.gst_rate);
				if (seen[key]) return;
				seen[key] = true;
				rows.push({ value: key, label: parseMoney(row.gst_rate) + '%' });
			});
			rows.sort(function(a, b) { return parseMoney(a.value) - parseMoney(b.value); });
			if (!rows.length) rows.push({ value: '0.00', label: '0%' });
			return rows;
		}
		function currentGst() {
			return parseMoney(getSelectVal($('#gst_percent')));
		}
		function currentTds() {
			return parseMoney(getSelectVal($('#tds_percent')));
		}
		function buildVendorSelect(selected) {
			var $el = $('#vendor_id');
			fillSelectOptions($el, vendorOptions, 'vendor_id', 'label', selected, true);
			initSelect2($el, 'Select vendor');
			setSelectVal($el, selected || '');
		}
		function buildGstSelect(selected) {
			var $el = $('#gst_percent');
			fillSelectOptions($el, gstRates(), 'value', 'label', rateKey(selected || 0), false);
			initSelect2($el, 'GST %');
			setSelectVal($el, rateKey(selected || gstRates()[0].value));
		}
		function defaultTdsValue() {
			return '0.00';
		}
		function buildTdsSelect(selected) {
			var rows = tdsOptions && tdsOptions.length ? tdsOptions : [{ value: '0.00', label: 'No TDS (0%)' }];
			var pick = (selected !== undefined && selected !== null && selected !== '') ? rateKey(selected) : defaultTdsValue();
			var found = false;
			$.each(rows, function(i, row) { if (rateKey(row.value) === pick) found = true; });
			if (!found) pick = rateKey(rows[0].value);
			var $el = $('#tds_percent');
			fillSelectOptions($el, rows, 'value', 'label', pick, false);
			initSelect2($el, 'TDS %');
			setSelectVal($el, pick);
		}
		function initVoucherType(selected) {
			var $el = $('#voucher_type');
			if ($el.data('select2')) $el.select2('destroy');
			initSelect2($el, 'Select type');
			var formOpts = window.PV_VOUCHER_TYPE_FORM || {};
			var allOpts = window.PV_VOUCHER_TYPE_ALL || {};
			selected = String(selected || 'purchase_bill').toLowerCase();
			var html = '';
			$.each(formOpts, function(key, label) {
				html += '<option value="' + escHtml(key) + '">' + escHtml(label) + '</option>';
			});
			if (selected && !formOpts[selected] && allOpts[selected]) {
				html += '<option value="' + escHtml(selected) + '">' + escHtml(allOpts[selected]) + '</option>';
			}
			if ($el.data('select2')) $el.select2('destroy');
			$el.html(html);
			initSelect2($el, 'Select type');
			setSelectVal($el, selected);
			toggleGcnColumn();
		}
		function toggleGcnColumn() {
			var show = voucherType() === 'gcn';
			$('#pv_lines').toggleClass('is-gcn', show);
			if (!show) return;
			refreshGcnSelects();
		}
		function isGeneralVoucher() {
			var vt = voucherType();
			return vt === 'general_expense' || vt === 'general';
		}
		function posStateIdVal() {
			return parseInt($('#pos_state_id').val(), 10) || 0;
		}
		function syncPosDisplay() {
			var id = posStateIdVal();
			$('#pv_pos_disp').text(id > 0 ? (gstStateById[String(id)] || '—') : '—');
			var supply = (companyStateId > 0 && id > 0 && companyStateId === id) ? 'intra' : (id > 0 ? 'inter' : '');
			var $badge = $('#pv_supply_badge');
			if (!supply) {
				$badge.hide().text('');
			} else {
				$badge.text(supply === 'intra' ? 'Intra-state (CGST + SGST)' : 'Inter-state (IGST)').show();
			}
		}
		function setPosStateId(stateId) {
			$('#pos_state_id').val(stateId ? String(stateId) : '');
			syncPosDisplay();
		}
		function defaultPosForVendor(vendorId) {
			var st = vendorStateById[String(vendorId || '')];
			if (st) setPosStateId(st);
		}
		function gstSplitAmounts(gstTotal, posId) {
			gstTotal = parseMoney(gstTotal);
			posId = parseInt(posId, 10) || 0;
			if (gstTotal <= 0) return { cgst: 0, sgst: 0, igst: 0 };
			var intra = companyStateId > 0 && posId > 0 && companyStateId === posId;
			if (intra) {
				var cgst = Math.round(gstTotal * 50) / 100;
				var sgst = Math.round((gstTotal - cgst) * 100) / 100;
				return { cgst: cgst, sgst: sgst, igst: 0 };
			}
			return { cgst: 0, sgst: 0, igst: gstTotal };
		}
		function syncGeneralGstBreakdown() {
			if (!isGeneralVoucher()) return;
			var taxable = 0;
			var gst = 0;
			$('#pv_lines_body tr').each(function() {
				taxable += parseMoney($(this).find('.line-amount').val());
				gst += parseMoney($(this).find('.line-gst').val());
			});
			$('#pv_taxable_disp').text(formatMoney(taxable));
			var rcm = $('#rcm_applicable').is(':checked') ? parseMoney($('#rcm_gst_amount').val()) : 0;
			var vendorGst = Math.max(0, Math.round((gst - rcm) * 100) / 100);
			var split = gstSplitAmounts(vendorGst, posStateIdVal());
			$('#pv_cgst_disp').text(formatMoney(split.cgst));
			$('#pv_sgst_disp').text(formatMoney(split.sgst));
			$('#pv_igst_disp').text(formatMoney(split.igst));
			syncPosDisplay();
		}
		function togglePurchaseBillUi() {
			var pb = isPurchaseBill();
			var gen = isGeneralVoucher();
			$('#pv_gst_tds_fields').toggleClass('is-hidden', pb);
			$('.pv-general-invoice-field').toggle(gen);
			$('#pv_general_gst_card').toggle(gen);
			$('#vendor_bill_wrap').toggle(pb);
			$('#pv_bill_settlement').toggle(pb);
			$('#pv_voucher_layout').toggleClass('is-purchase-bill', pb);
			$('#pv_voucher_layout').toggleClass('is-general-expense', gen);
			$('#pv_lines').toggleClass('is-purchase-bill', pb);
			$('#btn_add_line').toggle(!pb && !isVoucherEdit);
			if (!pb) {
				purchaseBillSnap = null;
				setSelectVal($('#vendor_bill_id'), '');
			}
			if (gen) syncGeneralGstBreakdown();
		}
		function renderPurchaseBillSettlement(snap) {
			snap = snap || {};
			var already = parseMoney(snap.already_paid || 0);
			var outstanding = parseMoney(snap.outstanding || 0);
			var thisNet = 0;
			$('#pv_lines_body tr').each(function() {
				thisNet += parseMoney($(this).find('.line-net').val());
				if (thisNet <= 0) {
					var amt = parseMoney($(this).find('.line-amount').val());
					var other = parseMoney($(this).find('.line-other').val());
					var gst = parseMoney($(this).find('.line-gst').val());
					var tds = parseMoney($(this).find('.line-tds').val());
					thisNet += Math.max(0, amt + gst - tds + other);
				}
			});
			if (generalExpenseId > 0 && thisNet > 0) {
				already = Math.round((already + thisNet) * 100) / 100;
				outstanding = Math.max(0, Math.round((outstanding - thisNet) * 100) / 100);
			}
			$('#pb_total_freight').text(snap.total_freight_fmt || snap.subtotal_fmt || formatMoney(snap.subtotal || snap.total_freight || 0));
			$('#pb_taxable_ref').text(snap.subtotal_fmt || formatMoney(snap.subtotal || 0));
			$('#pb_already_paid').text(formatMoney(already));
			$('#pb_debit_note').text(snap.debit_note_adjustment_fmt || formatMoney(snap.debit_note_adjustment || 0));
			$('#pb_outstanding').text(formatMoney(outstanding));
			$('#pb_cgst_ref').text(snap.cgst_amount_fmt || formatMoney(snap.cgst_amount || 0));
			$('#pb_sgst_ref').text(snap.sgst_amount_fmt || formatMoney(snap.sgst_amount || 0));
			$('#pb_igst_ref').text(snap.igst_amount_fmt || formatMoney(snap.igst_amount || 0));
			$('#pb_invoice_total_ref').text(snap.bill_total_fmt || formatMoney(snap.bill_total || snap.grand_total || 0));
			$('#pb_tds_ref').text((snap.tds_amount_fmt || formatMoney(snap.tds_amount || 0)) + (snap.tds_percent ? ' (' + snap.tds_percent + '%)' : ''));
			renderPurchaseBillPaymentHistory(snap);
		}
		function renderPurchaseBillPaymentHistory(snap) {
			snap = snap || {};
			var list = snap.payment_history || [];
			var $wrap = $('#pb_payment_history_wrap');
			var $body = $('#pb_payment_history_body').empty();
			if (!list.length) {
				$wrap.hide();
				return;
			}
			$.each(list, function(i, row) {
				var trClass = row.is_current ? ' class="is-current"' : '';
				var no = escHtml(row.expense_no || '—');
				if (row.is_current) {
					no += ' <span style="font-weight:600;color:#0369a1;">(this voucher)</span>';
				}
				$body.append(
					'<tr' + trClass + '><td>' + no + '</td><td>' + escHtml(row.expense_date || '—') + '</td>' +
					'<td class="num">' + escHtml(row.payment_amount_fmt || formatMoney(row.payment_amount || 0)) + '</td></tr>'
				);
			});
			$wrap.show();
		}
		function buildPurchaseBillSelect(rows, selected) {
			var $el = $('#vendor_bill_id');
			fillSelectOptions($el, rows || [], 'bill_id', 'label', selected, true);
			initSelect2($el, 'Select purchase bill');
			setSelectVal($el, selected || '');
		}
		function loadPurchaseBillOptions(vendorId, selected, done) {
			if (!vendorId) {
				buildPurchaseBillSelect([], '');
				if (done) done();
				return;
			}
			$.getJSON('expense_general_data.php', { cmd: 'purchase_bill_options', vendor_id: vendorId }, function(r) {
				buildPurchaseBillSelect((r && r.status === 0) ? (r.bills || []) : [], selected || '');
				if (done) done();
			}).fail(function() {
				buildPurchaseBillSelect([], '');
				if (done) done();
			});
		}
		function loadPurchaseBillSnapshot(billId, done) {
			if (!billId) {
				purchaseBillSnap = null;
				renderPurchaseBillSettlement(null);
				if (done) done();
				return;
			}
			$.getJSON('expense_general_data.php', {
				cmd: 'purchase_bill_snapshot',
				vendor_bill_id: billId,
				general_expense_id: generalExpenseId
			}, function(r) {
				if (!r || r.status !== 0) {
					purchaseBillSnap = null;
					alert(r && r.message ? r.message : 'Could not load purchase bill.');
					renderPurchaseBillSettlement(null);
					if (done) done();
					return;
				}
				purchaseBillSnap = r.snapshot || null;
				renderPurchaseBillSettlement(purchaseBillSnap);
				applyPurchaseBillToLines(!done);
				if (done) done();
			}).fail(function() {
				purchaseBillSnap = null;
				if (done) done();
			});
		}
		function lineMoneyInput(val) {
			if (val === null || val === undefined || val === '') return '';
			if (typeof val === 'number' || (typeof val === 'string' && /^-?\d+(\.\d+)?$/.test(String(val).trim()))) {
				return formatMoney(val);
			}
			return val;
		}
		function applyPurchaseBillToLines(addIfEmpty) {
			if (!purchaseBillSnap) return;
			$('#pv_lines_body tr').each(function() {
				var $row = $(this);
				$row.find('.line-freight').val(formatMoney(purchaseBillSnap.subtotal || purchaseBillSnap.total_freight || 0));
				recalcRow($row);
			});
			if (addIfEmpty !== false && $('#pv_lines_body tr').length === 0) {
				addLine({});
			}
		}
		function setPurchaseBillLineData(data) {
			data = data || {};
			var $row = $('#pv_lines_body tr').first();
			if (!$row.length) {
				return addLine(data);
			}
			var $type = $row.find('.line-type');
			if ($type.data('select2')) {
				$type.select2('destroy');
			}
			$type.html(lineOptionsHtml(categoryOptions, 'category_id', 'label', data.category_id || ''));
			initSelect2($type, 'Expense type');
			if (data.category_id) {
				setSelectVal($type, data.category_id);
			}
			if (data.freight_amount !== undefined && data.freight_amount !== '') {
				$row.find('.line-freight').val(lineMoneyInput(data.freight_amount));
			} else if (purchaseBillSnap) {
				$row.find('.line-freight').val(formatMoney(purchaseBillSnap.subtotal || purchaseBillSnap.total_freight || 0));
			}
			if (data.expense_amount !== undefined && data.expense_amount !== '') {
				$row.find('.line-amount').val(lineMoneyInput(data.expense_amount));
			}
			if (data.other_deduction !== undefined && data.other_deduction !== '') {
				$row.find('.line-other').val(lineMoneyInput(data.other_deduction));
			}
			recalcRow($row);
			if (isVoucherEdit) {
				setRowLocked($row, true);
			}
			return $row;
		}
		function rememberGcn(key, label) {
			key = key ? String(key) : '';
			if (!key) return;
			var found = false;
			$.each(gcnOptions, function(i, row) {
				if (String(row.key) === key) {
					found = true;
					return false;
				}
			});
			if (!found) {
				gcnOptions.unshift({ key: key, label: label || key });
			}
		}
		function refreshGcnSelects() {
			if (!$('#pv_lines').hasClass('is-gcn')) return;
			$('#pv_lines_body .line-gcn').each(function() {
				var $el = $(this);
				var val = $el.val() || '';
				if ($el.data('select2')) {
					try { $el.select2('destroy'); } catch (err) {}
				}
				$el.next('.select2-container').remove();
				$el.removeClass('select2-offscreen').show();
				$el.html(lineOptionsHtml(gcnOptions, 'key', 'label', val));
				$el.select2({
					width: '100%',
					placeholder: gcnOptionsLoaded ? 'Select GCN' : 'Loading GCNs…',
					allowClear: true,
					minimumResultsForSearch: 0
				});
				if (val) $el.select2('val', val);
			});
		}
		function ensureGcnOptions(done) {
			if (gcnOptionsLoaded) {
				refreshGcnSelects();
				if (done) done();
				return;
			}
			$.ajax({
				url: 'expense_general_data.php',
				data: { cmd: 'fetch_gcns' },
				dataType: 'json',
				global: false
			}).done(function(r) {
				var saved = gcnOptions.slice();
				gcnOptions = (r && r.status === 0 && $.isArray(r.gcns)) ? r.gcns : [];
				$.each(saved, function(i, row) { rememberGcn(row.key, row.label); });
				gcnOptionsLoaded = true;
			}).always(function() {
				refreshGcnSelects();
				if (done) done();
			});
		}
		function lineOptionsHtml(rows, valueKey, labelKey, selected) {
			var html = '<option value=""></option>';
			$.each(rows || [], function(i, row) {
				var val = row[valueKey];
				var label = row[labelKey] || val;
				html += '<option value="' + escHtml(val) + '"' + (String(selected) === String(val) ? ' selected' : '') + '>' + escHtml(label) + '</option>';
			});
			return html;
		}
		function recalcRow($row) {
			var amount = parseMoney($row.find('.line-amount').val());
			var other = parseMoney($row.find('.line-other').val());
			if (isPurchaseBill()) {
				var gst = purchaseBillSnap ? parseMoney(purchaseBillSnap.gst_total) : 0;
				var tds = purchaseBillSnap ? parseMoney(purchaseBillSnap.tds_amount) : 0;
				var net = Math.round((amount + gst - tds + other) * 100) / 100;
				$row.find('.line-gst').val(formatMoney(gst));
				$row.find('.line-tds').val(formatMoney(tds));
				$row.find('.line-net').val(formatMoney(net));
				recalcTotal();
				return;
			}
			var gst = Math.round((amount * currentGst() / 100) * 100) / 100;
			var tds = currentTds() > 0 && amount > 0 ? Math.round(amount * currentTds() / 100 * 100) / 100 : 0;
			var net = Math.round((amount + gst - tds + other) * 100) / 100;
			$row.find('.line-gst').val(formatMoney(gst));
			$row.find('.line-tds').val(formatMoney(tds));
			$row.find('.line-net').val(formatMoney(net));
			recalcTotal();
		}
		function recalcAllRows() {
			$('#pv_lines_body tr').each(function() { recalcRow($(this)); });
			recalcTotal();
		}
		function recalcTotal() {
			var total = 0;
			$('#pv_lines_body .line-net').each(function() { total += parseMoney($(this).val()); });
			$('#net_payable_display').val(formatMoney(total));
			syncGeneralGstBreakdown();
			if (isPurchaseBill() && purchaseBillSnap) {
				renderPurchaseBillSettlement(purchaseBillSnap);
			}
		}
		function syncAddLineButton() {
			$('#btn_add_line').toggle(!isVoucherEdit);
		}
		function setRowLocked($row, locked) {
			$row.toggleClass('line-locked', locked);
			$row.find('input').not('.line-gst, .line-tds, .line-net').prop('readonly', locked);
			$row.find('select').each(function() {
				var $sel = $(this);
				$sel.prop('disabled', locked);
				if ($sel.data('select2')) {
					if (locked) {
						$sel.select2('enable', false);
					} else {
						$sel.select2('enable', true);
					}
				}
			});
			var $editBtn = $row.find('.btn-edit-line');
			if ($editBtn.length) {
				if (locked) {
					$editBtn.attr('title', 'Edit').find('i').removeClass('fa-check').addClass('fa-pencil');
				} else {
					$editBtn.attr('title', 'Done editing').find('i').removeClass('fa-pencil').addClass('fa-check');
				}
			}
			$row.find('.btn-remove-line').prop('disabled', isVoucherEdit && locked);
		}
		function bindLine($row) {
			var $type = $row.find('.line-type');
			initSelect2($type, 'Expense type');
			$type.on('change', function() { syncSelectTitle($(this)); });
			$row.find('.line-gcn').on('change', function() { syncSelectTitle($(this)); });
			$row.find('.line-amount, .line-other').on('input blur', function() {
				recalcRow($row);
				if (isPurchaseBill() && purchaseBillSnap) {
					renderPurchaseBillSettlement(purchaseBillSnap);
				}
			});
			$row.find('.btn-edit-line').on('click', function() {
				if ($(this).prop('disabled')) return;
				setRowLocked($row, !$row.hasClass('line-locked'));
			});
			$row.find('.btn-remove-line').on('click', function() {
				if (isVoucherEdit && $row.hasClass('line-locked')) return;
				if ($('#pv_lines_body tr').length <= 1) {
					$row.find('.line-amount, .line-other').val('');
					setSelectVal($row.find('.line-type'), '');
					setSelectVal($row.find('.line-gcn'), '');
					recalcRow($row);
					return;
				}
				$row.remove();
				recalcTotal();
			});
			recalcRow($row);
		}
		function addLine(data) {
			data = data || {};
			if (isPurchaseBill() && $('#pv_lines_body tr').length >= 1) {
				return $('#pv_lines_body tr').first();
			}
			var $row = $('<tr></tr>');
			$row.append('<td class="pv-col-gcn"><select class="form-control line-gcn">' + lineOptionsHtml(gcnOptions, 'key', 'label', data.gcn_key || '') + '</select></td>');
			$row.append('<td class="pv-col-type"><select class="form-control line-type">' + lineOptionsHtml(categoryOptions, 'category_id', 'label', data.category_id || '') + '</select></td>');
			var freightVal = data.freight_amount !== undefined ? data.freight_amount : (purchaseBillSnap ? purchaseBillSnap.subtotal : '');
			$row.append('<td class="pv-col-freight pv-col-num"><input type="text" class="form-control text-right line-freight" readonly tabindex="-1" value="' + escHtml(freightVal !== '' && freightVal !== undefined ? formatMoney(freightVal) : '') + '"></td>');
			$row.append('<td class="pv-col-num"><input type="text" class="form-control text-right line-amount" value="' + escHtml(data.expense_amount || '') + '" autocomplete="off"></td>');
			$row.append('<td class="pv-col-num"><input type="text" class="form-control text-right line-gst" readonly tabindex="-1"></td>');
			$row.append('<td class="pv-col-num"><input type="text" class="form-control text-right line-tds" readonly tabindex="-1"></td>');
			$row.append('<td class="pv-col-num"><input type="text" class="form-control text-right line-other" value="' + escHtml(data.other_deduction || '') + '" autocomplete="off"></td>');
			$row.append('<td class="pv-col-num"><input type="text" class="form-control text-right line-net" readonly tabindex="-1"></td>');
			var editBtn = isVoucherEdit
				? '<button type="button" class="act-link act-edit btn-edit-line" title="Edit"><i class="fa fa-pencil"></i></button>'
				: '';
			$row.append('<td class="pv-col-action">' + editBtn +
				'<button type="button" class="act-link act-delete btn-remove-line" title="Remove"><i class="fa fa-trash-o"></i></button></td>');
			$('#pv_lines_body').append($row);
			bindLine($row);
			toggleGcnColumn();
			togglePurchaseBillUi();
			if (data.gcn_key) setSelectVal($row.find('.line-gcn'), data.gcn_key);
			if (data.category_id) setSelectVal($row.find('.line-type'), data.category_id);
			if (isVoucherEdit) {
				setRowLocked($row, true);
			}
			return $row;
		}
		function collectLines() {
			var lines = [];
			$('#pv_lines_body tr').each(function() {
				var $row = $(this);
				lines.push({
					category_id: getSelectVal($row.find('.line-type')),
					gcn_key: voucherType() === 'gcn' ? getSelectVal($row.find('.line-gcn')) : '',
					expense_amount: $row.find('.line-amount').val() || 0,
					other_deduction: $row.find('.line-other').val() || 0
				});
			});
			return lines;
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
		function buildBankSelect(selected) {
			var $el = $('#company_bank_id');
			fillSelectOptions($el, bankOptions, 'bank_account_id', 'label', selected, true);
			initSelect2($el, 'Select company bank account');
			setSelectVal($el, selected || '');
		}
		function initPaymentModeSelect(selected) {
			var $el = $('#payment_mode');
			if ($el.data('select2')) $el.select2('destroy');
			initSelect2($el, 'Select payment mode');
			setSelectVal($el, selected || '');
		}
		function fillForm(data) {
			generalExpenseId = parseInt(data.general_expense_id, 10) || 0;
			isVoucherEdit = generalExpenseId > 0;
			syncAddLineButton();
			$('#expense_no_display').val(data.expense_no || '');
			$('#expense_date').val(data.expense_date || '');
			buildVendorSelect(data.vendor_id || '');
			initVoucherType(data.voucher_type || 'general');
			$('#vendor_invoice_no').val(data.vendor_invoice_no || '');
			$('#vendor_invoice_date').val(data.vendor_invoice_date || '');
			setPosStateId(data.pos_state_id || 0);
			$('#rcm_applicable').prop('checked', parseInt(data.rcm_applicable, 10) === 1);
			$('#rcm_gst_amount').val(data.rcm_gst_amount || '').toggle(parseInt(data.rcm_applicable, 10) === 1);
			if (!data.pos_state_id && data.vendor_id) {
				defaultPosForVendor(data.vendor_id);
			}
			togglePurchaseBillUi();
			buildGstSelect(data.gst_percent_raw !== undefined ? data.gst_percent_raw : data.gst_percent);
			buildTdsSelect(data.tds_percent);
			$('#description').val(data.description || '');
			initPaymentModeSelect(data.payment_mode || '');
			buildBankSelect(data.company_bank_id || '');
			if (data.company_bank_id) {
				setSelectVal($('#company_bank_id'), String(data.company_bank_id));
				applyBankDetails(data.company_bank_id);
			} else {
				clearBankSelection();
			}
			$.each(data.lines || [], function(i, line) {
				if (line && line.gcn_key) rememberGcn(line.gcn_key, line.gcn_label || line.gcn_no);
			});
			$('#pv_lines_body').empty();
			var lines = data.lines || [];
			if ((data.voucher_type || '') === 'purchase_bill') {
				loadPurchaseBillOptions(data.vendor_id || '', data.vendor_bill_id || '', function() {
					loadPurchaseBillSnapshot(data.vendor_bill_id || '', function() {
						var lineData = {};
						if (lines.length) {
							lineData = lines[0];
						}
						setPurchaseBillLineData({
							category_id: lineData.category_id || data.category_id || '',
							freight_amount: lineData.freight_amount_raw != null ? lineData.freight_amount_raw : (lineData.freight_amount || ''),
							expense_amount: lineData.expense_amount_raw != null ? lineData.expense_amount_raw : (lineData.expense_amount || data.expense_amount_raw || data.expense_amount || ''),
							other_deduction: lineData.other_deduction_raw != null ? lineData.other_deduction_raw : (lineData.other_deduction || data.other_deduction_raw || data.other_deduction || '')
						});
						renderPurchaseBillSettlement(purchaseBillSnap);
						recalcTotal();
					});
				});
			} else if (!lines.length) {
				addLine({
					category_id: data.category_id || '',
					expense_amount: data.expense_amount || '',
					other_deduction: data.other_deduction || ''
				});
			} else {
				$.each(lines, function(i, line) {
					addLine({
						category_id: line.category_id,
						gcn_key: line.gcn_key,
						expense_amount: line.expense_amount,
						other_deduction: line.other_deduction
					});
				});
			}
			togglePaymentBankRequirement();
			if ((data.voucher_type || 'general') !== 'purchase_bill') {
				recalcAllRows();
			}
			if ((data.voucher_type || 'general') === 'gcn') {
				ensureGcnOptions();
			}
		}

		$(function() {
			$.each(bankOptions, function(i, b) {
				if (parseInt(b.is_primary, 10) === 1) primaryBankId = parseInt(b.bank_account_id, 10) || 0;
			});
			buildVendorSelect('');
			initVoucherType(<?php echo json_encode($default_voucher_type); ?>);
			syncPosDisplay();
			togglePurchaseBillUi();
			buildGstSelect(0);
			buildTdsSelect('');
			buildBankSelect('');
			initPaymentModeSelect('');
			clearBankSelection();
			addLine({});
			syncAddLineButton();
			togglePaymentBankRequirement();

			if (generalExpenseId > 0) {
				$.ajax({
					url: 'expense_general_data.php',
					data: { cmd: 'fetch', general_expense_id: generalExpenseId },
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

			$('#voucher_type').on('change select2-selected', function(e) {
				var picked = (e && e.val !== undefined && e.val !== null && e.val !== '') ? e.val : $(this).val();
				var vt = String(picked || voucherType()).toLowerCase();
				var showGcn = vt === 'gcn';
				$('#pv_lines').toggleClass('is-gcn', showGcn);
				togglePurchaseBillUi();
				syncSelectTitle($(this));
				if (showGcn) {
					refreshGcnSelects();
					ensureGcnOptions();
				}
				if (vt === 'purchase_bill') {
					var vid = getSelectVal($('#vendor_id'));
					loadPurchaseBillOptions(vid, '');
					if ($('#pv_lines_body tr').length === 0) addLine({});
					else applyPurchaseBillToLines();
				} else {
					recalcAllRows();
				}
			});
			$('#vendor_id').on('change select2-selected', function() {
				syncSelectTitle($(this));
				var vid = getSelectVal($(this)) || 0;
				if (isPurchaseBill()) {
					setSelectVal($('#vendor_bill_id'), '');
					purchaseBillSnap = null;
					renderPurchaseBillSettlement(null);
					loadPurchaseBillOptions(vid, '');
					$('#pv_lines_body .line-freight').val('');
					recalcAllRows();
				} else if (isGeneralVoucher() && vid) {
					defaultPosForVendor(vid);
					syncGeneralGstBreakdown();
				}
			});
			$('#rcm_applicable').on('change', function() {
				$('#rcm_gst_amount').toggle($(this).is(':checked'));
				syncGeneralGstBreakdown();
			});
			$('#rcm_gst_amount').on('input change', function() {
				syncGeneralGstBreakdown();
			});
			$('#vendor_bill_id').on('change', function() {
				syncSelectTitle($(this));
				loadPurchaseBillSnapshot(getSelectVal($(this)) || 0);
			});
			$('#gst_percent').on('change', function() {
				syncSelectTitle($(this));
				recalcAllRows();
			});
			$('#tds_percent').on('change', function() {
				syncSelectTitle($(this));
				recalcAllRows();
			});
			$('#btn_add_line').on('click', function() { addLine({}); });
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
				window.location.reload();
			});
			$('#general_expense_form').on('submit', function(e) {
				e.preventDefault();
				var lines = collectLines();
				if (voucherType() === 'gcn') {
					var missingGcn = false;
					$.each(lines, function(i, line) {
						if (!line.gcn_key) missingGcn = true;
					});
					if (missingGcn) {
						alert('Please select GCN no. on every line.');
						return;
					}
				}
				if (isPurchaseBill()) {
					var billId = getSelectVal($('#vendor_bill_id'));
					if (!billId) {
						alert('Please select a purchase bill.');
						return;
					}
					if (!getSelectVal($('#vendor_id'))) {
						alert('Please select vendor.');
						return;
					}
					var missingType = false;
					$.each(lines, function(i, line) {
						if (!line.category_id) missingType = true;
					});
					if (missingType) {
						alert('Please select expense type.');
						return;
					}
					var payNet = 0;
					$('#pv_lines_body tr').each(function() {
						payNet += parseMoney($(this).find('.line-net').val());
					});
					if (purchaseBillSnap && payNet - parseMoney(purchaseBillSnap.outstanding) > 0.009) {
						alert('Payment exceeds outstanding net payable on this purchase bill.');
						return;
					}
				}
				var payload = {
					cmd: 'save',
					general_expense_id: generalExpenseId,
					expense_date: $('#expense_date').val(),
					vendor_id: getSelectVal($('#vendor_id')),
					voucher_type: voucherType(),
					vendor_bill_id: isPurchaseBill() ? getSelectVal($('#vendor_bill_id')) : 0,
					gst_percent: isPurchaseBill() ? 0 : getSelectVal($('#gst_percent')),
					tds_percent: isPurchaseBill() ? 0 : getSelectVal($('#tds_percent')),
					vendor_invoice_no: isGeneralVoucher() ? ($('#vendor_invoice_no').val() || '') : '',
					vendor_invoice_date: isGeneralVoucher() ? ($('#vendor_invoice_date').val() || '') : '',
					pos_state_id: isGeneralVoucher() ? posStateIdVal() : 0,
					rcm_applicable: isGeneralVoucher() && $('#rcm_applicable').is(':checked') ? 1 : 0,
					rcm_gst_amount: isGeneralVoucher() ? parseMoney($('#rcm_gst_amount').val()) : 0,
					cgst_amount: isGeneralVoucher() ? parseMoney($('#pv_cgst_disp').text()) : 0,
					sgst_amount: isGeneralVoucher() ? parseMoney($('#pv_sgst_disp').text()) : 0,
					igst_amount: isGeneralVoucher() ? parseMoney($('#pv_igst_disp').text()) : 0,
					lines_json: JSON.stringify(lines),
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
					var listTab = isGeneralVoucher() ? 'general_expense' : 'purchase_bill';
					window.location.href = 'expense_general_list.php?tab=' + encodeURIComponent(listTab);
				}, 'json').fail(function() {
					$('#btn_save').prop('disabled', false);
					alert('Save request failed.');
				});
			});
		});
	</script>
</body>

</html>
