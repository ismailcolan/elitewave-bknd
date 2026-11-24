<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$c_date = date('d-m-Y');
$default_from = date('d-m-Y', strtotime('first day of this month'));

$customers_q = mysqli_query($conn, 'SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC');
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.filter-label {
			display: block;
			font-weight: 600;
			font-size: 13px;
			margin-bottom: 6px;
			color: #333;
		}
		.required-star { color: #DD111E; font-weight: bold; }
		.ew-page-v2--bill-wise .btn1 { margin-top: 0 !important; }
		.ew-page-v2--bill-wise .ew-field--customer .select2-container { width: 100% !important; }
		.ew-page-v2--bill-wise .ew-mis-active-actions { align-items: center; }
		.ew-page-v2--bill-wise .bw-status-group { display: inline-flex; align-items: center; height: 36px; }
		.bw-segmented {
			display: inline-flex; align-items: stretch; height: 36px;
			border: 1px solid var(--ew-border, #D8DDE5); border-radius: 8px; overflow: hidden;
			background: #fff; flex-wrap: nowrap;
		}
		.bw-seg-btn {
			border: 0; background: transparent; padding: 0 10px; font-size: 13px; font-weight: 600;
			color: var(--ew-text, #1A2332); height: 34px; line-height: 34px; min-height: 0; cursor: pointer;
		}
		.bw-seg-btn + .bw-seg-btn { border-left: 1px solid var(--ew-border, #D8DDE5); }
		.bw-seg-btn[aria-pressed="true"] { background: #0A1E3D; color: #fff; }
		.bw-seg-btn .bw-count { margin-left: 4px; font-size: 11px; font-weight: 600; opacity: .9; }
		.bw-table-scroll { overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch; }
		.bw-table-l1, .bw-table-l2, .bw-table-l3 { font-size: 12px; min-width: 900px; }
		.bw-table-l1 td.num, .bw-table-l1 th.num, .bw-table-l2 td.num, .bw-table-l2 th.num, .bw-table-l3 td.num, .bw-table-l3 th.num {
			text-align: right !important; font-variant-numeric: tabular-nums;
		}
		.ew-page-v2--bill-wise .bw-table-l1 thead th.num {
			text-align: right !important;
		}
		.ew-page-v2--bill-wise .bw-table-l1 thead th.bw-col-status,
		.ew-page-v2--bill-wise .bw-table-l1 tbody td.bw-col-status {
			text-align: center !important;
		}
		.ew-page-v2--bill-wise #bw_footer_totals.bw-table-l1 {
			margin-bottom: 0;
			min-width: 900px;
		}
		.bw-table-l1 thead th,
		.bw-table-l1 tbody td,
		.bw-table-l2 thead th,
		.bw-table-l2 tbody td {
			padding: 7px 10px;
			line-height: 1.35;
			vertical-align: middle;
		}
		.bw-col-toggle { width: 36px; padding: 4px !important; }
		.bw-toggle {
			width: 30px; height: 30px; border: 1px solid var(--ew-border, #D8DDE5); border-radius: 6px; background: #fff;
			cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0;
			font-size: 11px; color: #0A1E3D;
		}
		.bw-toggle:focus { outline: none; border-color: #9DC3E6; box-shadow: 0 0 0 3px rgba(157, 195, 230, 0.25); }
		.bw-cust-row.is-open .bw-toggle i, .bw-table-l2 tr.is-open .bw-toggle i { transform: rotate(90deg); }
		@media (prefers-reduced-motion: reduce) {
			.bw-cust-row.is-open .bw-toggle i, .bw-table-l2 tr.is-open .bw-toggle i { transform: none; }
		}
		.bw-cust-row.is-open { background: #F4F7FB; }
		.bw-detail-row > td { padding: 0 !important; background: #F8FAFB; border-top: none; }
		.bw-panel { padding: 10px 12px 12px; }
		.bw-panel-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 8px 12px;
			margin-bottom: 10px;
		}
		.bw-panel-title { margin: 0; font-size: 13px; font-weight: 700; color: #021659; flex: 1 1 180px; min-width: 0; line-height: 1.35; }
		.bw-panel-head .btn-bw-ledger-pdf {
			flex: 0 0 auto;
			min-height: 32px;
			padding: 0 12px;
			font-size: 12px;
			font-weight: 600;
			white-space: nowrap;
		}
		.bw-panel-head .btn-bw-ledger-pdf i { margin-right: 6px; color: #64748b; }
		.bw-muted { font-size: 11px; color: #64748b; margin-top: 2px; }
		.bw-chip { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #e2e8f0; color: #334155; }
		.bw-chip.is-ok { background: #dcfce7; color: #15803d; }
		.bw-chip.is-pending { background: #fee2e2; color: #b91c1c; }
		.bw-chip.is-mid { background: #fef3c7; color: #b45309; }
		.bw-drcr { font-size: 10px; font-weight: 700; margin-left: 2px; }
		.bw-drcr.is-cr { color: #15803d; }
		.bw-ledger-strip { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 16px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 12px; }
		.bw-footer-totals td { font-weight: 700; background: #f1f5f9; }
		.bw-empty, .bw-skeleton, .bw-error { padding: 24px 16px; color: #64748b; text-align: center; }
		.bw-error { color: #b91c1c; }
		.bw-gcn-total td { background: #f8fafc; }
		.bw-table-l1 .bw-head-month th {
			background: var(--rail-bg, #DDE7F0);
			font-weight: 700; font-size: 12px; color: #0A1E3D;
			padding: 6px 10px; line-height: 1.3;
		}
		.bw-table-l1 thead tr:not(.bw-head-month) th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
		}
		.bw-table-l1 .bw-period-range { letter-spacing: 0.01em; white-space: nowrap; }
		.ew-mis-active-actions .bw-segmented { margin-right: 4px; }
		.ew-page-v2--bill-wise .bw-list-footer {
			margin-top: 0;
			padding: 10px 16px;
			border-top: 1px solid var(--ew-border-light, #E9ECF0);
			background: #fafbfc;
		}
		.ew-page-v2--bill-wise .bw-list-footer .ew-dt-footer-meta {
			font-size: 13px;
			color: var(--ew-text-muted, #6B7A8D);
			font-weight: 600;
			white-space: nowrap;
		}
		.ew-page-v2--bill-wise #bw_pagination {
			display: inline-flex !important;
			align-items: center;
			justify-content: flex-end;
			gap: 6px;
			flex-wrap: wrap;
		}
		.ew-page-v2--bill-wise #bw_pagination .paginate_button {
			cursor: pointer;
			font-family: inherit;
			line-height: 1;
		}
		.ew-page-v2--bill-wise #bw_pagination .paginate_button:disabled {
			opacity: .45;
			cursor: default;
			pointer-events: none;
		}
		.ew-page-v2--bill-wise #bw_pagination .paginate_ellipsis {
			min-width: 28px;
			border: none !important;
			background: transparent !important;
			color: #94a3b8 !important;
			cursor: default;
			pointer-events: none;
			box-shadow: none !important;
		}
		.ew-page-v2--bill-wise #bw_footer_totals { margin-top: 0; border-top: none; }
		.bw-ledger-pdf-control {
			display: inline-flex;
			align-items: stretch;
			vertical-align: middle;
			height: 36px;
			border: 1px solid var(--ew-border, #D8DDE5);
			border-radius: 8px;
			background: #fff;
			overflow: hidden;
		}
		.bw-ledger-pdf-control:focus-within {
			border-color: #9DC3E6;
			box-shadow: 0 0 0 3px rgba(157, 195, 230, 0.25);
		}
		.bw-ledger-pdf-control .bw-ledger-pdf-action {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			height: 100%;
			margin: 0;
			padding: 0 12px;
			border: 0;
			border-right: 1px solid var(--ew-border, #D8DDE5);
			border-radius: 0;
			background: #f8fafc;
			color: #0A1E3D;
			font-size: 12px;
			font-weight: 600;
			line-height: 1;
			white-space: nowrap;
			cursor: pointer;
		}
		.bw-ledger-pdf-control .bw-ledger-pdf-action:hover {
			background: #eef2f7;
		}
		.bw-ledger-pdf-control .bw-ledger-pdf-action i {
			font-size: 13px;
			color: #64748b;
		}
		.bw-ledger-pdf-select {
			height: 34px !important;
			min-height: 34px !important;
			margin: 0 !important;
			min-width: 148px;
			max-width: 180px;
			padding: 0 28px 0 10px !important;
			font-size: 13px !important;
			font-weight: 600;
			color: #0A1E3D !important;
			border: none !important;
			border-radius: 0 !important;
			background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%236B7A8D' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E") no-repeat right 10px center !important;
			background-size: 10px 6px !important;
			box-shadow: none !important;
			-webkit-appearance: none;
			appearance: none;
			cursor: pointer;
		}
		.bw-ledger-pdf-select:focus {
			outline: none;
		}
		@media (max-width: 1280px) {
			.ew-page-v2--bill-wise .ew-mis-active-actions {
				width: 100%;
				margin-left: 0;
				justify-content: flex-start;
			}
			.ew-page-v2--bill-wise .ew-mis-active-actions .bw-ledger-pdf-control {
				max-width: 100%;
			}
			.ew-page-v2--bill-wise .ew-mis-active-actions .bw-ledger-pdf-select {
				min-width: 130px;
				flex: 1 1 120px;
				max-width: none;
			}
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
					<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--mis-report ew-page-v2--bill-wise">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Bill Wise</h1>
							</div>
							<div class="ew-page-head-right">
								<span class="report-count" id="report_count"></span>
							</div>
						</div>

						<div id="ew_mis_filters_view">
							<div class="ew-card">
								<div class="ew-card-toolbar ew-mis-filters-head">
									<h2>Report Filters</h2>
									<div class="ew-mis-quick-range" aria-label="Quick date ranges">
										<button type="button" data-range="today">Today</button>
										<button type="button" data-range="week">This week</button>
										<button type="button" class="is-active" data-range="month">This month</button>
										<button type="button" data-range="last_month">Last month</button>
									</div>
								</div>
								<div class="ew-form-body">
									<div class="ew-mis-filter-shell">
										<div class="filter-form-wrap ew-form-grid ew-mis-filter-grid">
											<div class="ew-field">
												<label class="filter-label">From Date <span class="required-star">*</span></label>
												<?php echo ew_date_input(array(
													'id' => 'from_date',
													'value' => $default_from,
													'required' => true,
												)); ?>
											</div>
											<div class="ew-field">
												<label class="filter-label">To Date <span class="required-star">*</span></label>
												<?php echo ew_date_input(array(
													'id' => 'to_date',
													'value' => $c_date,
													'required' => true,
												)); ?>
											</div>
											<div class="ew-field span-2 ew-field--customer">
												<label class="filter-label">Customer</label>
												<select id="customers" class="form-control" multiple>
													<?php while ($cust = mysqli_fetch_assoc($customers_q)) { ?>
														<option value="<?php echo (int) $cust['client_id']; ?>"><?php echo htmlspecialchars($cust['client_company_name']); ?></option>
													<?php } ?>
												</select>
											</div>
										</div>
										<div class="ew-mis-filter-footer">
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_reset_filters">
												<i class="fa fa-undo"></i> Reset
											</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn1" id="search">
												<i class="fa fa-search"></i> Search
											</button>
										</div>
									</div>
								</div>
							</div>

							<div class="ew-card" id="ew_mis_empty_card">
								<div class="ew-mis-empty-state">
									<div class="ew-mis-empty-icon"><i class="fa fa-file-text-o"></i></div>
									<h3>Choose a date range to load the report</h3>
									<p>Use a quick range above, optionally pick customers, then press Search.</p>
								</div>
							</div>
						</div>

						<div id="ew_mis_results_view" style="display:none;">
							<div class="ew-mis-active-bar">
								<div class="ew-mis-active-left">
									<span class="ew-mis-active-label"><i class="fa fa-filter"></i> Filters</span>
									<span class="ew-mis-date-pill" id="ew_mis_date_pill"></span>
									<span class="ew-mis-active-summary" id="ew_mis_active_summary"></span>
								</div>
								<div class="ew-mis-active-actions">
									<div class="ew-mis-table-search">
										<i class="fa fa-search"></i>
										<input type="search" id="bw_customer_search" placeholder="Filter customers…" autocomplete="off" aria-label="Filter customers">
									</div>
									<div class="bw-status-group"><div class="bw-segmented" role="group" aria-label="Payment status">
										<button type="button" class="bw-seg-btn" id="bw_seg_all" data-filter="all" aria-pressed="true">All <span class="bw-count">0</span></button>
										<button type="button" class="bw-seg-btn" id="bw_seg_pending" data-filter="pending" aria-pressed="false">Pending <span class="bw-count">0</span></button>
										<button type="button" class="bw-seg-btn" id="bw_seg_completed" data-filter="completed" aria-pressed="false">Completed <span class="bw-count">0</span></button>
									</div></div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_edit_filters">Edit filters</button>
									<div class="bw-ledger-pdf-control" role="group" aria-label="Ledger PDF download">
										<button type="button" class="bw-ledger-pdf-action" id="btn_ledger_pdf_results" title="Download ledger PDF">
											<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Ledger PDF
										</button>
										<select id="bw_ledger_narration" class="form-control bw-ledger-pdf-select" aria-label="Narration type">
											<option value="1" selected>With narration</option>
											<option value="0">Without narration</option>
										</select>
									</div>
								</div>
							</div>

							<div class="ew-card ew-erp-list" id="bw_report_card">
								<div class="ew-card-toolbar bw-report-head" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
									<div>
										<h2 id="ew_mis_results_title" style="margin:0;">Partywise sales collection report</h2>
									</div>
									<div>
										<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="bw_expand_all" style="min-height:36px;font-size:12px;">Expand all</button>
										<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="bw_collapse_all" style="min-height:36px;font-size:12px;">Collapse all</button>
									</div>
								</div>
								<div class="ew-table-wrap widget-content padded clearfix">
									<div id="bw_report_body"><p class="bw-skeleton">Loading…</p></div>
									<div class="ew-table-footer bw-list-footer" id="bw_list_footer" style="display:none;">
										<div class="ew-table-footer-left ew-dt-footer-left">
											<span class="ew-dt-footer-meta" id="bw_pager_info"></span>
										</div>
										<div class="ew-dt-footer-right ew-pagination" id="bw_pagination" aria-label="Customer list pages"></div>
									</div>
									<div class="bw-table-scroll">
										<table class="table table-bordered bw-footer-totals bw-table-l1" id="bw_footer_totals" style="display:none;">
											<tbody>
												<tr>
													<td class="bw-col-toggle"></td>
													<td colspan="2"><strong>Total (visible customers)</strong></td>
													<td class="num" id="bw_ft_close"></td>
													<td class="num" id="bw_ft_bill"></td>
													<td class="num" id="bw_ft_coll"></td>
													<td class="bw-col-status"></td>
												</tr>
											</tbody>
										</table>
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
	<script src="javascripts/ew-mis-report-views.js?v=20261117"></script>
	<script src="javascripts/bill-wise.js?v=20261119bwpanel"></script>
</body>
</html>