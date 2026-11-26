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
require_once('include/expense_vendor_gst_helpers.php');
$gst_state_options = expense_vendor_gst_state_options($conn);
$company_state_id = gst_register_company_state($conn);
$gst_state_by_id = array();
foreach ($gst_state_options as $st) {
	$gst_state_by_id[(int) ($st['state_id'] ?? 0)] = (string) ($st['state_name'] ?? '');
}
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
		.ew-page-v2 .ew-field select.form-control:not([multiple]) {
			height: 34px;
		}
		/* GCN multi-select — one row inside standard control (full border intact) */
		#gcn_wrap .select2-container-multi {
			display: block !important;
			width: 100% !important;
			max-width: 100% !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices {
			position: relative !important;
			display: flex !important;
			flex-direction: row !important;
			flex-wrap: nowrap !important;
			align-items: center !important;
			height: var(--ew-control-h, 34px) !important;
			max-height: var(--ew-control-h, 34px) !important;
			min-height: var(--ew-control-h, 34px) !important;
			overflow: hidden !important;
			padding: 0 34px 0 6px !important;
			box-sizing: border-box !important;
			border-radius: var(--ew-control-radius, 8px) !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices::after {
			top: 1px !important;
			right: 1px !important;
			bottom: 1px !important;
			width: 32px !important;
			height: auto !important;
			z-index: 2 !important;
			background: #f8fafc !important;
			border-radius: 0 calc(var(--ew-control-radius, 8px) - 1px) calc(var(--ew-control-radius, 8px) - 1px) 0 !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices::before {
			z-index: 3 !important;
			right: 12px !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices li {
			float: none !important;
			display: block !important;
			flex: 0 1 auto !important;
			min-width: 0 !important;
			margin: 0 !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices .select2-search-choice {
			max-width: 88px !important;
			margin: 2px 4px 2px 0 !important;
			overflow: hidden !important;
			text-overflow: ellipsis !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices .select2-search-choice div {
			overflow: hidden !important;
			text-overflow: ellipsis !important;
			white-space: nowrap !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices .select2-search-field {
			flex: 1 1 20px !important;
			width: auto !important;
			min-width: 0 !important;
			max-width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices .select2-search-field:only-child {
			flex: 1 1 auto !important;
			width: 100% !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices .select2-search-field input {
			width: 100% !important;
			min-width: 0 !important;
			max-width: 100% !important;
			height: calc(var(--ew-control-h, 34px) - 2px) !important;
			min-height: calc(var(--ew-control-h, 34px) - 2px) !important;
			line-height: calc(var(--ew-control-h, 34px) - 2px) !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices .select2-search-choice ~ .select2-search-field {
			flex: 0 0 18px !important;
			width: 18px !important;
			min-width: 18px !important;
			max-width: 56px !important;
		}
		#gcn_wrap .select2-container-multi .select2-choices .select2-search-choice ~ .select2-search-field input {
			width: 18px !important;
			min-width: 18px !important;
			max-width: 56px !important;
		}
		#gcn_wrap .select2-container-multi.select2-container-active .select2-choices .select2-search-choice ~ .select2-search-field,
		#gcn_wrap .select2-container-multi.select2-dropdown-open .select2-choices .select2-search-choice ~ .select2-search-field {
			flex: 1 1 48px !important;
			width: auto !important;
			min-width: 48px !important;
			max-width: 100% !important;
		}
		#gcn_wrap .select2-container-multi.select2-container-active .select2-choices .select2-search-choice ~ .select2-search-field input,
		#gcn_wrap .select2-container-multi.select2-dropdown-open .select2-choices .select2-search-choice ~ .select2-search-field input {
			width: 100% !important;
			min-width: 48px !important;
			max-width: none !important;
		}
		#gcn_wrap .select2-drop {
			z-index: 10050 !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
		}
		#gcn_wrap .select2-results {
			max-width: 100% !important;
		}
		#gcn_wrap .select2-results .select2-result-label {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
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
		.ew-page-v2 .ebill-gcn-table-wrap.ew-table-wrap {
			border-top: none;
		}
		.ew-page-v2 .ebill-gcn-table-wrap {
			display: flex;
			flex-direction: column;
			border: 1px solid var(--panel-border, #C5D3E0);
			border-radius: 10px;
			overflow: hidden;
			background: #fff;
			margin: 0;
		}
		.ew-page-v2 .ebill-gcn-table-wrap .ebill-gcn-scroll.ew-table-scroll-host {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
			flex: 0 1 auto;
			min-width: 0;
		}
		.ew-page-v2 .ebill-gcn-table-wrap .ew-table-scroll-host.ew-drag-scroll {
			cursor: grab;
		}
		.ew-page-v2 .ebill-gcn-table-wrap .ew-table-scroll-host.ew-drag-scroll.is-dragging {
			cursor: grabbing;
			user-select: none;
		}
		.ew-page-v2 .ebill-gcn-table {
			width: max-content;
			min-width: 100%;
			margin: 0;
			table-layout: auto;
		}
		.ew-page-v2 .ebill-gcn-table-wrap table thead th.num,
		.ew-page-v2 .ebill-gcn-table-wrap table tfoot td.num {
			text-align: right !important;
		}
		.ew-page-v2 .ebill-gcn-table-wrap table thead th.ctr {
			text-align: center !important;
		}
		.ew-page-v2 .ebill-gcn-table th.c-no,
		.ew-page-v2 .ebill-gcn-table td.c-no { min-width: 108px; white-space: nowrap; font-weight: 600; }
		.ew-page-v2 .ebill-gcn-table th.c-date,
		.ew-page-v2 .ebill-gcn-table td.c-date { min-width: 104px; white-space: nowrap; }
		.ew-page-v2 .ebill-gcn-table th.c-consignor,
		.ew-page-v2 .ebill-gcn-table td.c-consignor { min-width: 168px; max-width: 220px; }
		.ew-page-v2 .ebill-gcn-table th.c-consignee,
		.ew-page-v2 .ebill-gcn-table td.c-consignee { min-width: 168px; max-width: 220px; }
		.ew-page-v2 .ebill-gcn-table th.c-route,
		.ew-page-v2 .ebill-gcn-table td.c-route { min-width: 120px; }
		.ew-page-v2 .ebill-gcn-table td.c-consignor,
		.ew-page-v2 .ebill-gcn-table td.c-consignee,
		.ew-page-v2 .ebill-gcn-table td.c-route {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
		.ew-page-v2 .ebill-gcn-table th.c-freight {
			min-width: 124px;
			white-space: nowrap;
			padding-right: 16px;
		}
		.ew-page-v2 .ebill-gcn-table td.c-freight {
			min-width: 72px;
			max-width: 104px;
			width: 96px;
			padding-right: 16px;
		}
		.ew-page-v2 .ebill-gcn-table th.c-gst {
			min-width: 108px;
			white-space: nowrap;
			padding-left: 8px;
			text-align: right;
		}
		.ew-page-v2 .ebill-gcn-table td.c-gst {
			min-width: 96px;
			padding-left: 8px;
			text-align: right;
		}
		.ew-page-v2 .ebill-gcn-table th.c-tds,
		.ew-page-v2 .ebill-gcn-table td.c-tds {
			min-width: 100px;
			padding-left: 10px;
			white-space: nowrap;
			text-align: right;
		}
		.ew-page-v2 .ebill-gcn-table th.c-grand,
		.ew-page-v2 .ebill-gcn-table td.ebill-grand-cell {
			min-width: 108px;
			padding-left: 10px;
			white-space: nowrap;
			text-align: right !important;
		}
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
		.ew-page-v2 .ebill-gcn-table-wrap table tbody td.c-input {
			padding-top: 8px !important;
			padding-bottom: 8px !important;
			vertical-align: middle;
		}
		.ew-page-v2 .ebill-gcn-table input.ebill-freight {
			display: block;
			width: 88px;
			max-width: 100%;
			margin-left: auto;
			box-sizing: border-box;
			height: 34px;
			margin: 0;
			padding: 0 10px;
			border: 1.5px solid var(--ew-border, #D8DDE5);
			border-radius: var(--ew-radius-sm, 6px);
			font-size: 13px;
			line-height: 32px;
			color: var(--ew-text, #1A2332);
			text-align: right;
			font-variant-numeric: tabular-nums;
			cursor: text;
			box-shadow: none;
			background: #fff;
		}
		.ew-page-v2 .ebill-gcn-table input.ebill-freight:focus {
			border-color: var(--ew-navy, #0A1E3D);
			box-shadow: 0 0 0 3px rgba(10, 30, 61, .10);
			outline: none;
		}
		.ew-page-v2 .ebill-gcn-table input.ebill-gst-pct {
			width: 72px;
			min-width: 72px;
			text-align: right;
			padding: 4px 8px;
			height: 32px;
		}
		.ebill-pct-input.is-locked,
		.ew-page-v2 .ebill-gcn-table input.ebill-gst-pct.is-locked {
			background: #f1f5f9;
			color: #475569;
			cursor: not-allowed;
		}
		.ew-page-v2 .ebill-gcn-table select.ebill-gst-pct {
			display: block;
			width: 100%;
			box-sizing: border-box;
			height: 34px;
			margin: 0;
			padding: 0 26px 0 8px;
			border: 1.5px solid var(--ew-border, #D8DDE5);
			border-radius: var(--ew-radius-sm, 6px);
			font-size: 13px;
			line-height: 32px;
			color: var(--ew-text, #1A2332);
			text-align: right;
			font-variant-numeric: tabular-nums;
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
		.ew-page-v2 .ebill-gcn-table td.ebill-grand-cell {
			font-weight: 700;
			text-align: right !important;
			font-variant-numeric: tabular-nums;
		}
		.ew-page-v2 .ebill-gcn-table td.c-tds,
		.ew-page-v2 .ebill-gcn-table td.ebill-tds-cell {
			font-variant-numeric: tabular-nums;
		}
		.ew-page-v2 .ebill-gcn-table tfoot td.c-gst-foot {
			padding: 12px 16px 12px 8px !important;
		}
		.ew-page-v2 .ebill-gcn-table .ebill-foot-gst-inline .ebill-gst-pct-spacer {
			flex: 0 0 84px;
			width: 84px;
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

		.ew-page-v2 .ebill-gcn-table tfoot tr.ebill-foot-meta td {
			border-top: 1px solid var(--ew-border-light, #E9ECF0);
			font-weight: 600;
		}
		.ew-page-v2 .ebill-gcn-table td.c-freight input.ebill-freight {
			text-align: right !important;
		}

		.ew-page-v2 .ebill-gcn-net-bar {
			display: none;
			flex: 0 0 auto;
			background: #F4F7FA;
			border-top: 1px solid var(--panel-border, #C5D3E0);
			border-radius: 0 0 9px 9px;
			font-variant-numeric: tabular-nums;
		}
		.ew-page-v2 .ebill-gcn-net-bar.is-on {
			display: block;
		}
		.ew-page-v2 .ebill-gcn-net-bar__row {
			display: flex;
			align-items: center;
			justify-content: flex-end;
			gap: 18px;
			padding: 11px 16px;
			line-height: 1.2;
			min-height: 0;
			margin: 0;
		}
		.ew-page-v2 .ebill-gcn-net-bar .ebill-net-lbl {
			margin: 0;
			font-size: 11px;
			font-weight: 700;
			letter-spacing: .04em;
			text-transform: uppercase;
			color: var(--ew-navy, #0A1E3D);
			white-space: nowrap;
		}
		.ew-page-v2 .ebill-gcn-net-bar .ebill-net-val {
			margin: 0;
			min-width: 96px;
			text-align: right;
			font-size: 15px;
			font-weight: 700;
			line-height: 1.2;
			color: var(--ew-navy, #0A1E3D);
		}
		#due_date[readonly] {
			background: #F8FAFC;
			cursor: default;
		}
		.ebill-tax-select.is-locked + .select2-container .select2-choice {
			background: #f1f5f9;
			color: #475569;
			cursor: not-allowed;
		}

		.ebill-details-shell {
			grid-column: span 3;
			display: grid;
			grid-template-columns: minmax(0, 1fr) minmax(220px, 268px);
			gap: 18px;
			align-items: start;
		}
		.ebill-details-grid {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 12px 16px;
			min-width: 0;
		}
		.ebill-details-grid .ew-field {
			margin: 0;
			min-width: 0;
		}
		#gcn_wrap .ebill-gcn-multi-wrap {
			max-width: 100%;
		}
		.ebill-tax-card {
			border: 1px solid var(--ew-border-light, #E9ECF0);
			border-radius: 10px;
			padding: 14px 16px;
			background: #FAFBFC;
			position: sticky;
			top: 72px;
		}
		.ebill-tax-card__title {
			font-size: 12px;
			font-weight: 600;
			color: #334155;
			margin-bottom: 12px;
			letter-spacing: 0.01em;
		}
		.ebill-tax-card__pos {
			margin-bottom: 14px;
			padding-bottom: 12px;
			border-bottom: 1px dashed #E2E8F0;
		}
		.ebill-tax-card__pos .lbl {
			display: block;
			font-size: 10px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #64748B;
			margin-bottom: 4px;
		}
		.ebill-tax-card__pos strong {
			display: block;
			font-size: 13px;
			color: #0F172A;
			line-height: 1.35;
		}
		.ebill-supply-badge {
			display: inline-block;
			margin-top: 6px;
			font-size: 10px;
			font-weight: 600;
			padding: 2px 8px;
			border-radius: 999px;
			background: #EEF2FF;
			color: #4338CA;
		}
		.ebill-tax-card__rows {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.ebill-tax-card__row {
			display: flex;
			justify-content: space-between;
			align-items: baseline;
			gap: 12px;
			font-size: 11px;
			color: #64748B;
		}
		.ebill-tax-card__row strong {
			font-size: 13px;
			color: #0F172A;
			font-weight: 600;
		}

		@media (max-width: 991px) {
			.ebill-details-shell {
				grid-template-columns: 1fr;
			}
			.ebill-tax-card {
				position: static;
			}
			.ebill-details-grid {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}
		@media (max-width: 640px) {
			.ebill-details-grid {
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
								<a href="expense_bill_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php echo $preselect_id > 0 ? 'Edit Purchase Bill Registration' : 'Add Purchase Bill Registration'; ?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="expense_bill_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-form-body">
							<form id="bill_registration_form" enctype="multipart/form-data">
								<div class="ew-form-grid ew-form-grid--bill">
									<div class="ew-section-label span-3">Bill Details</div>

									<div class="ebill-details-shell">
										<div class="ebill-details-grid">
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
												<label class="control-label">GCN No. <span style="color:red;">*</span></label>
												<div class="egen-select-wrap ebill-gcn-multi-wrap">
													<select id="gcn_keys" name="gcn_keys[]" class="form-control ebill-gcn-multi" multiple="multiple" data-placeholder="Search & select GCN no."></select>
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
											</div>
											<div class="ew-field">
												<label class="control-label">GST % :</label>
												<div class="egen-select-wrap">
													<select id="gst_percent" name="gst_percent" class="form-control egen-select ebill-tax-select"></select>
												</div>
											</div>
											<div class="ew-field">
												<label class="control-label">TDS % :</label>
												<div class="egen-select-wrap">
													<select id="tds_percent" name="tds_percent" class="form-control egen-select ebill-tax-select"></select>
												</div>
											</div>
										</div>

										<aside class="ebill-tax-card" aria-labelledby="ebill_tax_card_title">
											<div class="ebill-tax-card__title" id="ebill_tax_card_title">GST on vendor invoice</div>
											<div class="ebill-tax-card__pos">
												<span class="lbl">Place of supply</span>
												<strong id="ebill_pos_disp">—</strong>
												<span class="ebill-supply-badge" id="ebill_supply_badge" style="display:none;"></span>
											</div>
											<div class="ebill-tax-card__rows" id="ebill_gst_split_display">
												<div class="ebill-tax-card__row"><span>CGST</span><strong id="ebill_cgst_disp">0.00</strong></div>
												<div class="ebill-tax-card__row"><span>SGST</span><strong id="ebill_sgst_disp">0.00</strong></div>
												<div class="ebill-tax-card__row"><span>IGST</span><strong id="ebill_igst_disp">0.00</strong></div>
											</div>
											<input type="hidden" id="pos_state_id" name="pos_state_id" value="">
											<input type="hidden" id="bill_cgst" name="bill_cgst" value="0">
											<input type="hidden" id="bill_sgst" name="bill_sgst" value="0">
											<input type="hidden" id="bill_igst" name="bill_igst" value="0">
										</aside>
									</div>

									<div class="ew-field span-3" id="gcn_summary_wrap">
										<label class="control-label">Selected GCN Details :</label>
								
										<div class="ew-table-wrap ebill-gcn-table-wrap">
											<div class="ew-table-scroll-host ebill-gcn-scroll">
												<table class="table ew-table ebill-gcn-table ew-form-drag-table" id="gcn_summary_table">
													<thead>
														<tr>
															<th class="c-no">GCN No.</th>
															<th class="c-date">GCN Date</th>
															<th class="c-consignor">Consignor</th>
															<th class="c-consignee">Consignee</th>
															<th class="c-route">Route</th>
															<th class="num c-freight">Freight Amount <span style="color:#DC2626;">*</span></th>
															<th class="num c-gst">GST Amount</th>
															<th class="num c-tds">TDS Amount</th>
															<th class="num c-grand">Grand Total</th>
														</tr>
													</thead>
													<tbody id="gcn_summary_body">
														<tr><td colspan="9" class="ebill-empty">No GCNs selected.</td></tr>
													</tbody>
												</table>
											</div>
											<div class="ebill-gcn-net-bar" id="gcn_summary_totals" aria-live="polite">
												<div class="ebill-gcn-net-bar__row">
													<span class="ebill-net-lbl">Net Payable</span>
													<strong id="bill_net_payable_display" class="ebill-net-val">0.00</strong>
												</div>
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
		var gstPercentOptions = <?php echo json_encode($gst_percent_options, JSON_UNESCAPED_UNICODE); ?>;
		var companyStateId = <?php echo (int) $company_state_id; ?>;
		var gstStateById = <?php echo json_encode($gst_state_by_id, JSON_UNESCAPED_UNICODE); ?>;
		var vendorStateById = {};
		var tdsPercentOptions = <?php echo json_encode($tds_percent_options, JSON_UNESCAPED_UNICODE); ?>;
		var billDueDays = <?php echo (int) $bill_due_days; ?>;
		var gcnAmounts = {}; // gcn_key => { freight, gst_percent } entered manually from vendor invoice
		var vendorCreditDays = 0;
		var gcnOptions = [];
		var selectedGcnDetails = [];
		var vendorDefaultGstPercent = 0;
		var vendorGstLocked = false;
		var vendorDefaultTdsPercent = 0;
		var vendorTdsLocked = false;
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
			vendorStateById = {};
			$.each(vendorOptions || [], function(i, row) {
				if (row && row.vendor_id) vendorStateById[String(row.vendor_id)] = parseInt(row.state, 10) || 0;
			});
			var $el = $('#vendor_id');
			fillSelectOptions($el, vendorOptions, 'vendor_id', 'label', selected);
			initSelect2($el, 'Select vendor');
			setSelectVal($el, selected || '');
			defaultPosForVendor(getSelectVal($el));
		}
		function buildGcnSelect(selected) {
			var $el = $('#gcn_keys');
			fillSelectOptions($el, gcnOptions, 'key', 'label', selected || []);
			if ($el.data('select2')) $el.select2('destroy');
			$el.select2({
				width: '100%',
				placeholder: 'Search & select GCN no.',
				allowClear: true,
				minimumResultsForSearch: 0,
				closeOnSelect: false
			});
			setMultiSelectVal($el, selected || []);
			$el.off('change.ebillGcn').on('change.ebillGcn', function() {
				syncGcnSelectTitle();
				refreshSelectedGcnDetails();
			});
			syncGcnSelectTitle();
		}
		function syncGcnSelectTitle() {
			var labels = [];
			var $sel = $('#gcn_keys');
			$sel.find('option:selected').each(function() {
				var t = $.trim($(this).text());
				if (t) labels.push(t);
			});
			var title = labels.length ? labels.join(', ') : '';
			var $box = $('#gcn_wrap .select2-container-multi');
			$box.attr('title', title);
			$box.toggleClass('ebill-gcn-has-many', labels.length > 2);
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
			if (parseInt(vendor.gst_percent_configured, 10) === 1) {
				parts.push('Vendor GST: ' + moneyFmt(vendor.default_gst_percent || vendor.gst_rate || 0) + '% (fixed)');
			}
			if (parseInt(vendor.tds_percent_configured, 10) === 1) {
				parts.push('Vendor TDS: ' + moneyFmt(vendor.default_tds_percent || vendor.tds_rate || 0) + '% (fixed)');
			}
			$('#vendor_terms_hint').text(parts.join(' · '));
		}
		function pctRateKey(rate) {
			var n = moneyNum(rate);
			return n % 1 === 0 ? String(n) : String(n);
		}
		function pctOptionsWithRate(options, rate) {
			options = (options || []).slice();
			rate = moneyNum(rate);
			var found = false;
			$.each(options, function(i, opt) {
				if (Math.abs(moneyNum(opt.rate) - rate) < 0.009) found = true;
			});
			if (!found && (rate > 0 || rate === 0)) {
				var lbl = rate > 0 ? (rate + '%') : '0%';
				options.push({ rate: rate, label: lbl });
			}
			return options;
		}
		function buildPctSelect($el, options, selected, locked, placeholder) {
			if (!$el || !$el.length) return;
			var sel = '';
			if (selected !== '' && selected !== null && selected !== undefined) {
				sel = pctRateKey(selected);
			}
			var rows = sel !== '' ? pctOptionsWithRate(options, sel) : (options || []);
			var html = '<option value=""></option>';
			$.each(rows, function(i, opt) {
				var r = pctRateKey(opt.rate);
				html += '<option value="' + escHtml(r) + '"' + (sel !== '' && r === sel ? ' selected' : '') + '>' + escHtml(opt.label || (r + '%')) + '</option>';
			});
			if ($el.data('select2')) $el.select2('destroy');
			$el.html(html);
			$el.toggleClass('is-locked', !!locked);
			initSelect2($el, placeholder || 'Select');
			setSelectVal($el, sel);
			$el.prop('disabled', !!locked);
			if ($el.data('select2')) {
				if (locked) $el.select2('enable', false);
				else $el.select2('enable', true);
			}
			syncSelectTitle($el);
		}
		function buildGstPercentSelect(selected, locked) {
			buildPctSelect($('#gst_percent'), gstPercentOptions, selected, locked, 'Select GST %');
		}
		function buildTdsPercentSelect(selected, locked) {
			buildPctSelect($('#tds_percent'), tdsPercentOptions, selected, locked, 'Select TDS %');
		}
		function syncVendorTaxLocks(vendor, billTdsOverride, billGstOverride) {
			vendor = vendor || {};
			vendorGstLocked = parseInt(vendor.gst_percent_configured, 10) === 1;
			vendorTdsLocked = parseInt(vendor.tds_percent_configured, 10) === 1;
			vendorDefaultGstPercent = vendorGstLocked ? moneyNum(vendor.default_gst_percent || vendor.gst_rate || 0) : 0;
			vendorDefaultTdsPercent = vendorTdsLocked ? moneyNum(vendor.default_tds_percent || vendor.tds_rate || 0) : 0;
			var gstVal;
			if (billGstOverride !== undefined) {
				gstVal = vendorGstLocked ? vendorDefaultGstPercent : moneyNum(billGstOverride);
			} else if (vendorGstLocked) {
				gstVal = vendorDefaultGstPercent;
			} else {
				gstVal = '';
			}
			var tdsVal;
			if (billTdsOverride !== undefined) {
				tdsVal = vendorTdsLocked ? vendorDefaultTdsPercent : moneyNum(billTdsOverride);
			} else if (vendorTdsLocked) {
				tdsVal = vendorDefaultTdsPercent;
			} else {
				tdsVal = '';
			}
			buildGstPercentSelect(gstVal, vendorGstLocked);
			buildTdsPercentSelect(tdsVal, vendorTdsLocked);
		}
		function getGstPercentVal() {
			if (vendorGstLocked) {
				return moneyNum(vendorDefaultGstPercent);
			}
			return moneyNum(getSelectVal($('#gst_percent')));
		}
		function getTdsPercentVal() {
			if (vendorTdsLocked) {
				return moneyNum(vendorDefaultTdsPercent);
			}
			return moneyNum(getSelectVal($('#tds_percent')));
		}
		function applyVendorTaxProfile(vendor, billTdsOverride, billGstOverride) {
			syncVendorTaxLocks(vendor, billTdsOverride, billGstOverride);
			if (selectedGcnDetails.length) {
				renderGcnSummary(selectedGcnDetails, null);
			} else {
				recalcBillTotals();
			}
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
			$('#gcn_summary_totals').toggleClass('is-on', show);
			if (!show) {
				$body.html('<tr><td colspan="9" class="ebill-empty">No GCNs selected.</td></tr>');
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
				$tr.append('<td class="c-consignor">' + escHtml(row.consignor || '') + '</td>');
				$tr.append('<td class="c-consignee">' + escHtml(row.consignee || '') + '</td>');
				$tr.append('<td class="c-route">' + escHtml(row.route_label || '') + '</td>');
				$tr.append('<td class="c-input c-freight num"><input type="text" inputmode="decimal" class="form-control ebill-freight" autocomplete="off" placeholder="0.00" value="' + escHtml(amt.freight ? String(amt.freight) : '0') + '"></td>');
				$tr.append('<td class="num c-gst"><span class="ebill-gst-amt">' + moneyFmt(calc.gst) + '</span></td>');
				$tr.append('<td class="num c-tds"><span class="ebill-tds-amt">' + moneyFmt(calc.tds) + '</span></td>');
				$tr.append('<td class="num ebill-grand-cell c-grand">' + moneyFmt(calc.total) + '</td>');
				$body.append($tr);
			});
			recalcBillTotals();
			if (typeof window.ewInitTableScroll === 'function') {
				window.setTimeout(window.ewInitTableScroll, 0);
			}
		}
		function gcnAmountFor(key) {
			if (!gcnAmounts[key]) gcnAmounts[key] = { freight: 0 };
			return gcnAmounts[key];
		}
		function gcnRowCalc(key) {
			var a = gcnAmountFor(key);
			var freight = Math.max(0, moneyNum(a.freight));
			var pct = getGstPercentVal();
			var gst = moneyNum(freight * pct / 100);
			var tdsPct = getTdsPercentVal();
			var tds = (tdsPct > 0 && freight > 0) ? moneyNum(freight * tdsPct / 100) : 0;
			return { freight: freight, pct: pct, gst: gst, tds: tds, total: moneyNum(freight + gst) };
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
		function gstSupplyType(posId) {
			posId = parseInt(posId, 10) || 0;
			if (companyStateId <= 0 || posId <= 0) return '';
			return companyStateId === posId ? 'intra' : 'inter';
		}
		function splitGstAmount(gstTotal, supply) {
			gstTotal = moneyNum(gstTotal);
			if (gstTotal <= 0) return { cgst: 0, sgst: 0, igst: 0 };
			if (supply === 'inter') return { cgst: 0, sgst: 0, igst: gstTotal };
			var cgst = moneyNum(gstTotal / 2);
			var sgst = moneyNum(gstTotal - cgst);
			return { cgst: cgst, sgst: sgst, igst: 0 };
		}
		function posStateIdVal() {
			return parseInt($('#pos_state_id').val(), 10) || 0;
		}
		function syncPosDisplay() {
			var id = posStateIdVal();
			var name = id > 0 ? (gstStateById[String(id)] || '—') : '—';
			$('#ebill_pos_disp').text(name);
			var supply = gstSupplyType(id);
			var $badge = $('#ebill_supply_badge');
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
		function syncBillGstSplit(gstTotal) {
			var split = splitGstAmount(gstTotal, gstSupplyType(posStateIdVal()));
			$('#bill_cgst').val(split.cgst);
			$('#bill_sgst').val(split.sgst);
			$('#bill_igst').val(split.igst);
			$('#ebill_cgst_disp').text(moneyFmt(split.cgst));
			$('#ebill_sgst_disp').text(moneyFmt(split.sgst));
			$('#ebill_igst_disp').text(moneyFmt(split.igst));
		}
		function defaultPosForVendor(vendorId) {
			var st = vendorStateById[String(vendorId || '')];
			if (st) setPosStateId(st);
		}
		function recalcBillTotals() {
			var rows = selectedGcnRowsWithAmounts();
			var totals = calcLocalTotals(rows, getTdsPercentVal());
			setTotalsDisplay(totals);
			syncBillGstSplit(totals.gst_total);
		}
		function updateGcnRowDisplay($tr) {
			var c = gcnRowCalc(String($tr.attr('data-key') || ''));
			$tr.find('.ebill-gst-amt').text(moneyFmt(c.gst));
			$tr.find('.ebill-tds-amt').text(moneyFmt(c.tds));
			$tr.find('.ebill-grand-cell').text(moneyFmt(c.total));
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
				tds_percent: getTdsPercentVal(),
				vendor_id: getSelectVal($('#vendor_id'))
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
			vendorCreditDays = parseInt(data.credit_days, 10) || 0;
			gcnAmounts = {};
			var billGstFromLines = 0;
			$.each(data.gcns || [], function(i, g) {
				var k = String(g.gcn_key || g.key || '');
				if (k) {
					gcnAmounts[k] = { freight: moneyNum(g.freight_amount) };
					if (i === 0 || billGstFromLines <= 0) {
						billGstFromLines = moneyNum(g.gst_percent);
					}
				}
			});
			updateVendorHint(data);
			syncVendorTaxLocks(data, moneyNum(data.tds_percent), billGstFromLines);
			refreshDueDate();
			loadGcns(data.gcn_keys || [], function() {
				setMultiSelectVal($('#gcn_keys'), data.gcn_keys || []);
				syncGcnSelectTitle();
				if (data.gcns && data.gcns.length) {
					selectedGcnDetails = data.gcns;
					renderGcnSummary(data.gcns, null);
				} else {
					refreshSelectedGcnDetails();
				}
			});
			setPosStateId(data.pos_state_id || 0);
			if (!data.pos_state_id && data.vendor_id) {
				defaultPosForVendor(data.vendor_id);
			}
			$('#bill_cgst').val(moneyNum(data.cgst_amount));
			$('#bill_sgst').val(moneyNum(data.sgst_amount));
			$('#bill_igst').val(moneyNum(data.igst_amount));
			syncBillGstSplit(moneyNum(data.gst_total));
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
			fd.append('gst_percent', getGstPercentVal());
			fd.append('tds_percent', getTdsPercentVal());
			fd.append('pos_state_id', posStateIdVal());
			fd.append('bill_cgst', $('#bill_cgst').val() || 0);
			fd.append('bill_sgst', $('#bill_sgst').val() || 0);
			fd.append('bill_igst', $('#bill_igst').val() || 0);
			var amounts = {};
			$.each(selectedGcnDetails || [], function(i, row) {
				var k = String(row.gcn_key || row.key || '');
				if (!k) return;
				var c = gcnRowCalc(k);
				amounts[k] = { freight: c.freight, gst_percent: getGstPercentVal() };
			});
			fd.append('gcn_amounts', JSON.stringify(amounts));
			fd.append('narration', $('#narration').val() || '');
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
			buildGstPercentSelect('', false);
			buildTdsPercentSelect('', false);
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

			$('#vendor_id').on('change', function() {
				var vendorId = getSelectVal($(this)) || 0;
				syncSelectTitle($(this));
				if (!vendorId) {
					vendorDefaultGstPercent = 0;
					vendorGstLocked = false;
					vendorDefaultTdsPercent = 0;
					vendorTdsLocked = false;
					vendorCreditDays = 0;
					updateVendorHint({});
					buildGstPercentSelect('', false);
					buildTdsPercentSelect('', false);
					gcnAmounts = {};
					selectedGcnDetails = [];
					renderGcnSummary([], null);
					refreshDueDate();
					return;
				}
				$.getJSON('expense_bill_data.php', { cmd: 'vendor_info', vendor_id: vendorId }, function(r) {
					if (!r || r.status !== 0) return;
					var vendor = r.vendor || {};
					vendorCreditDays = parseInt(vendor.credit_days, 10) || 0;
					updateVendorHint(vendor);
					applyVendorTaxProfile(vendor);
					defaultPosForVendor(vendorId);
					refreshDueDate();
					refreshSelectedGcnDetails();
				});
			});
			syncPosDisplay();
			$('#vendor_invoice_date').on('change blur', function() {
				refreshDueDate();
			});
			$(document).on('change', '#gcn_keys', function() {
				refreshSelectedGcnDetails();
			});
			$('#gst_percent').on('change', function() {
				if (vendorGstLocked) return;
				syncSelectTitle($(this));
				$('#gcn_summary_body tr.ebill-gcn-row').each(function() {
					updateGcnRowDisplay($(this));
				});
				recalcBillTotals();
			});
			$('#tds_percent').on('change', function() {
				if (vendorTdsLocked) return;
				syncSelectTitle($(this));
				$('#gcn_summary_body tr.ebill-gcn-row').each(function() {
					updateGcnRowDisplay($(this));
				});
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
