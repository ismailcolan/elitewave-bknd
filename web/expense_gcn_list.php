<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_gcn_pl_helpers.php');

expense_require_admin();

$list_rows = expense_gcn_pl_fetch_list($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		#egcn_list_table .txn-gcn-no {
			display: block;
			font-weight: 700;
			color: #0A1E3D;
			font-size: 13px;
			line-height: 1.45;
			white-space: nowrap;
		}

		.egcn-route-multi {
			font-size: 12px;
			color: #475569;
		}

		.egcn-route-multi .route-count {
			font-weight: 700;
			color: #0A1E3D;
		}

		#egcn_list_table td.col-gcn {
			min-width: 160px;
		}

		#egcn_list_table td.profit-negative,
		#egcn_list_table td.profit-negative .profit-value {
			color: #dc2626 !important;
			font-weight: 700;
		}

		#egcn_list_table td.profit-positive,
		#egcn_list_table td.profit-positive .profit-value {
			color: #15803d !important;
			font-weight: 700;
		}

		#egcn_list_table td.profit-breakeven,
		#egcn_list_table td.profit-breakeven .profit-value {
			color: #475569 !important;
			font-weight: 700;
		}

		#egcn_list_table .col-actions .act-view {
			background: #e8edf3;
			color: #0A1E3D;
		}

		#egcn_list_table .col-actions .act-view:hover {
			background: #d5dde8;
			color: #0A1E3D;
		}

		#egcn_list_table th.col-actions,
		#egcn_list_table td.col-actions {
			text-align: center;
			width: 96px;
			min-width: 96px;
		}

		#egcn_list_table .col-actions .act-pdf {
			background: #dcfce7 !important;
			color: #15803d !important;
		}

		#egcn_list_table .col-actions .act-pdf:hover {
			background: #bbf7d0 !important;
			color: #166534 !important;
		}

		#egcn_list_table .col-actions .act-pdf i.fa {
			color: inherit !important;
			font-size: 14px !important;
			line-height: 1 !important;
			opacity: 1 !important;
		}

		/* Bill Wise–style list + accordion (pending_payments.php / bill-wise.js) */
		.ew-page-v2--gcn-pl .egcn-pl-table-scroll.bw-table-scroll {
			overflow-x: auto;
			max-width: 100%;
			-webkit-overflow-scrolling: touch;
		}

		.ew-page-v2--gcn-pl #egcn_list_table.bw-table-l1 {
			min-width: 1320px;
			width: 100%;
			margin-bottom: 0;
			font-size: 12px;
			border-left: none !important;
			border-right: none !important;
		}

		.ew-page-v2--gcn-pl #egcn_list_table.bw-table-l1 thead th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.03em;
			white-space: nowrap;
		}

		.ew-page-v2--gcn-pl #egcn_list_table.bw-table-l1 thead th.col-actions {
			text-align: center !important;
		}

		.ew-page-v2--gcn-pl #egcn_list_table td.num,
		.ew-page-v2--gcn-pl #egcn_list_table th.num,
		.ew-page-v2--gcn-pl .bw-table-l2 td.num,
		.ew-page-v2--gcn-pl .bw-table-l2 th.num {
			text-align: right !important;
			font-variant-numeric: tabular-nums;
		}

		.ew-page-v2--gcn-pl #egcn_list_table thead th,
		.ew-page-v2--gcn-pl #egcn_list_table tbody td,
		.ew-page-v2--gcn-pl .bw-table-l2 thead th,
		.ew-page-v2--gcn-pl .bw-table-l2 tbody td {
			padding: 7px 10px;
			line-height: 1.35;
			vertical-align: middle;
		}

		.ew-page-v2--gcn-pl .bw-col-toggle {
			width: 36px;
			padding: 4px !important;
		}

		.ew-page-v2--gcn-pl .bw-toggle {
			width: 30px;
			height: 30px;
			border: 1px solid var(--ew-border, #D8DDE5);
			border-radius: 6px;
			background: #fff;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 0;
			font-size: 11px;
			color: #0A1E3D;
		}

		.ew-page-v2--gcn-pl .bw-toggle:focus {
			outline: none;
			border-color: #9DC3E6;
			box-shadow: 0 0 0 3px rgba(157, 195, 230, 0.25);
		}

		.ew-page-v2--gcn-pl tr.egcn-pl-parent.is-open .bw-toggle i {
			transform: rotate(90deg);
		}

		@media (prefers-reduced-motion: reduce) {
			.ew-page-v2--gcn-pl tr.egcn-pl-parent.is-open .bw-toggle i {
				transform: none;
			}
		}

		.ew-page-v2--gcn-pl tr.egcn-pl-parent.bw-cust-row.is-open td {
			background: #F4F7FB !important;
			border-bottom-color: transparent;
		}

		.ew-page-v2--gcn-pl #egcn_list_table tbody tr.egcn-pl-parent td.col-sno {
			text-align: center !important;
			width: 48px;
			min-width: 48px;
		}

		.ew-page-v2--gcn-pl #egcn_list_table .egcn-col-ellipsis {
			max-width: 160px;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.ew-page-v2--gcn-pl #egcn_list_table th.col-th-compact,
		.ew-page-v2--gcn-pl #egcn_list_table td.col-th-compact {
			white-space: nowrap;
		}

		.ew-page-v2--gcn-pl #egcn_list_table td.col-actions,
		.ew-page-v2--gcn-pl #egcn_list_table th.col-actions {
			text-align: center !important;
		}

		.ew-page-v2--gcn-pl tr.bw-detail-row > td {
			padding: 0 !important;
			background: #F4F7FB;
			border-top: none;
			text-align: left !important;
			vertical-align: top;
		}

		.ew-page-v2--gcn-pl tr.bw-detail-row.is-open-tail > td {
			border-bottom: 1px solid #e2e8f0;
			padding-bottom: 8px !important;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-wrap {
			display: inline-block;
			width: max-content;
			max-width: min(100%, 720px);
			margin: 0 12px 12px 48px;
			padding: 10px 12px 12px;
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			border-left: 3px solid #9DC3E6;
			box-shadow: 0 1px 2px rgba(10, 30, 61, 0.04);
			overflow: visible;
			cursor: default;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-wrap.ew-no-drag-scroll {
			user-select: text;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-head {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 8px;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #64748b;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-count {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-width: 22px;
			height: 22px;
			padding: 0 6px;
			font-size: 11px;
			font-weight: 700;
			color: #0A1E3D;
			background: #e8edf3;
			border-radius: 999px;
		}

		.ew-page-v2--gcn-pl .bw-muted {
			font-size: 11px;
			color: #64748b;
			margin-top: 2px;
			line-height: 1.3;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table {
			width: auto;
			margin: 0;
			border: none !important;
			background: transparent;
			font-size: 12px;
			table-layout: auto;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table th.col-particulars,
		.ew-page-v2--gcn-pl .egcn-pl-pay-table td.col-particulars {
			min-width: 200px;
			max-width: 360px;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table thead th {
			background: transparent;
			border: none !important;
			border-bottom: 1px solid #e2e8f0 !important;
			color: #64748b;
			font-size: 10px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.03em;
			padding: 4px 8px 6px !important;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table tbody td {
			border: none !important;
			border-bottom: 1px solid #f1f5f9 !important;
			padding: 7px 8px !important;
			color: #334155;
			vertical-align: top;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table tbody tr:last-child td {
			border-bottom: none !important;
			padding-bottom: 2px !important;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table .egcn-pay-particular {
			font-weight: 600;
			color: #0f172a;
			line-height: 1.35;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table .egcn-pay-vch {
			font-weight: 600;
			color: #0A1E3D;
			white-space: nowrap;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table .egcn-pay-mode {
			font-size: 11px;
			font-weight: 600;
			color: #475569;
			white-space: nowrap;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table td.num {
			font-weight: 700;
			color: #0A1E3D;
			white-space: nowrap;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table th.col-date,
		.ew-page-v2--gcn-pl .egcn-pl-pay-table td.col-date {
			width: 96px;
			white-space: nowrap;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table th.col-vch,
		.ew-page-v2--gcn-pl .egcn-pl-pay-table td.col-vch {
			width: 108px;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table th.col-mode,
		.ew-page-v2--gcn-pl .egcn-pl-pay-table td.col-mode {
			width: 72px;
		}

		.ew-page-v2--gcn-pl .egcn-pl-pay-table th.col-amt,
		.ew-page-v2--gcn-pl .egcn-pl-pay-table td.col-amt {
			width: 88px;
		}

		#egcnViewModal .ew-v2-modal--lg {
			max-width: 860px;
		}

		.egcn-view-meta {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 10px 28px;
			margin-bottom: 18px;
			font-size: 13px;
			align-items: start;
		}

		.egcn-view-meta-col {
			display: flex;
			flex-direction: column;
			gap: 12px;
			min-width: 0;
		}

		.egcn-view-meta-col .egcn-meta-field:last-child dd {
			margin-bottom: 0;
		}

		.egcn-view-meta dt {
			font-weight: 700;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #64748b;
			margin: 0 0 4px;
		}

		.egcn-view-meta dd {
			margin: 0 0 10px;
			color: #021659;
			font-weight: 700;
			font-size: 13px;
			line-height: 1.35;
		}

		.egcn-view-kpi {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 12px;
			margin-bottom: 18px;
		}

		.egcn-view-kpi .kpi {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 12px 14px;
			text-align: center;
		}

		.egcn-view-kpi .kpi-label {
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin-bottom: 4px;
		}

		.egcn-view-kpi .kpi-value {
			font-size: 16px;
			font-weight: 700;
			color: #0A1E3D;
		}

		.egcn-view-kpi .kpi.kpi-profit-positive {
			background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
			border-color: #86efac;
		}

		.egcn-view-kpi .kpi.kpi-profit-negative {
			background: linear-gradient(180deg, #fef2f2 0%, #fecaca 100%);
			border-color: #fca5a5;
		}

		.egcn-view-kpi .kpi-value.profit-positive { color: #15803d; }
		.egcn-view-kpi .kpi-value.profit-negative { color: #dc2626; }

		.egcn-view-section-title {
			font-size: 13px;
			font-weight: 700;
			color: #0A1E3D;
			margin: 0 0 10px;
		}

		.egcn-view-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
			margin-bottom: 18px;
		}

		.egcn-view-table th,
		.egcn-view-table td {
			border: 1px solid #e2e8f0;
			padding: 8px 10px;
			text-align: left;
			vertical-align: middle;
		}

		.egcn-view-table th {
			background: #021659;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: .03em;
			color: #fff;
			font-weight: 700;
		}

		.egcn-view-table td.num,
		.egcn-view-table th.num {
			text-align: right;
			white-space: nowrap;
		}

		.egcn-view-table tr.egcn-total td {
			font-weight: 700;
			background: #f8fafc;
		}

		@media (max-width: 640px) {
			.egcn-view-kpi,
			.egcn-view-meta {
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
					<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--mis-report ew-page-v2--gcn-pl">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">GCN Profit &amp; Loss</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>GCN Profit &amp; Loss List</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools">
										<?php if (!empty($list_rows)) { ?>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="egcn_expand_all" style="min-height:36px;font-size:12px;">Expand all</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="egcn_collapse_all" style="min-height:36px;font-size:12px;margin-left:6px;">Collapse all</button>
										<?php } ?>
									</div>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<?php if (empty($list_rows)) { ?>
								<div class="egcn-empty" style="padding:24px;text-align:center;color:#64748b;">
									No GCN profit &amp; loss rows yet. Data appears when a GCN has booking freight, a registered purchase bill, payment voucher, or petty cash linked to it.
								</div>
							<?php } else { ?>
									<div class="bw-table-scroll egcn-pl-table-scroll">
									<table class="table table-bordered bw-table-l1 egcn_list_tab" id="egcn_list_table" role="grid">
										<thead>
											<tr>
												<th class="bw-col-toggle"></th>
												<th class="col-sno">S.No</th>
												<th class="col-gcn">GCN No.</th>
												<th class="col-th-compact">Booking Date</th>
												<th>Customer</th>
												<th>Vendor</th>
												<th class="num">Revenue</th>
												<th class="num">Vendor Cost</th>
												<th class="num">Petty Cash Expenses</th>
												<th class="num">Total Cost</th>
												<th class="num">Profit / Loss</th>
												<th class="num">Vendor Paid</th>
												<th class="num">Vendor Balance Payable</th>
												<th class="table-title sorting_disabled col-actions">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$i = 1;
											foreach ($list_rows as $row) {
												$profit_class = trim((string) ($row['profit_class'] ?? 'profit-breakeven'));
												if ($profit_class === '') {
													$profit_class = 'profit-breakeven';
												}
												$gcn_key = trim((string) ($row['gcn_key'] ?? ''));
												$payments = $row['payment_timeline'] ?? array();
												$pay_count = (int) ($row['payment_count'] ?? count($payments));
												$group_id = 'egcn-pl-' . $i;
												?>
												<tr class="egcn-pl-parent bw-cust-row" data-egcn-group="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>">
													<td class="bw-col-toggle">
														<?php if ($pay_count > 0) { ?>
															<button type="button" class="bw-toggle" aria-expanded="false" data-egcn-group="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>" title="Show vendor payments" aria-label="Show vendor payments for <?php echo htmlspecialchars($row['grn_no'] ?? 'GCN', ENT_QUOTES, 'UTF-8'); ?>">
																<i class="fa fa-chevron-right" aria-hidden="true"></i>
															</button>
														<?php } ?>
													</td>
													<td class="col-sno"><?php echo $i++; ?></td>
													<td class="col-gcn">
														<span class="txn-gcn-no"><?php echo htmlspecialchars($row['grn_no'] ?? '—'); ?></span>
													</td>
													<td class="col-th-compact"><?php echo htmlspecialchars($row['grn_date'] ?? '—'); ?></td>
													<td class="egcn-col-ellipsis" title="<?php echo htmlspecialchars($row['customer_label'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['customer_label'] ?? '—'); ?></td>
													<td class="egcn-col-ellipsis" title="<?php echo htmlspecialchars($row['vendor_label'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['vendor_label'] ?? '—'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['revenue'] ?? $row['customer_revenue'] ?? '0.00'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['vendor_cost'] ?? '0.00'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['petty_cash'] ?? '0.00'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['total_cost'] ?? '0.00'); ?></td>
													<td class="num <?php echo htmlspecialchars($profit_class, ENT_QUOTES, 'UTF-8'); ?>"><span class="profit-value"><?php echo htmlspecialchars($row['profit_amount'] ?? '0.00'); ?></span></td>
													<td class="num"><?php echo htmlspecialchars($row['vendor_paid'] ?? '0.00'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['vendor_balance'] ?? '0.00'); ?></td>
													<td class="col-actions">
														<span class="act-wrap">
															<button type="button" class="act-link act-view btn-view-gcn-pl" title="View breakdown"
																data-gcn-key="<?php echo htmlspecialchars($gcn_key, ENT_QUOTES, 'UTF-8'); ?>"
																data-gcn-no="<?php echo htmlspecialchars($row['grn_no'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
																<i class="fa fa-eye"></i>
															</button>
															<a class="act-link act-pdf" title="View PDF"
																href="expense_gcn_pl_pdf.php?gcn_key=<?php echo urlencode($gcn_key); ?>"
																target="_blank" rel="noopener">
																<i class="fa fa-file-text-o" aria-hidden="true"></i>
															</a>
														</span>
													</td>
												</tr>
												<?php if ($pay_count > 0) { ?>
													<tr class="bw-detail-row egcn-pl-detail is-open-tail" data-egcn-group="<?php echo htmlspecialchars($group_id, ENT_QUOTES, 'UTF-8'); ?>" hidden>
														<td colspan="14">
															<div class="egcn-pl-pay-wrap ew-no-drag-scroll">
																<div class="egcn-pl-pay-head">
																	<span class="egcn-pl-pay-label">Vendor payments</span>
																	<span class="egcn-pl-pay-count" title="Payment count"><?php echo (int) $pay_count; ?></span>
																</div>
																<table class="table egcn-pl-pay-table ew-no-drag-scroll">
																		<thead>
																			<tr>
																				<th class="col-date">Date</th>
																				<th class="col-particulars">Particulars</th>
																				<th class="col-vch">Voucher</th>
																				<th class="col-mode">Mode</th>
																				<th class="col-amt num">Amount</th>
																			</tr>
																		</thead>
																		<tbody>
																			<?php foreach ($payments as $pay) {
																				$child_title = trim((string) ($pay['expense_type'] ?? ''));
																				if ($child_title === '') {
																					$child_title = 'Vendor payment';
																				}
																				$bill_ref = trim((string) ($pay['reference'] ?? ''));
																				$show_bill = ($bill_ref !== '' && $bill_ref !== '—' && stripos($bill_ref, 'direct') === false);
																				$mode = trim((string) ($pay['payment_mode'] ?? ''));
																				if ($mode === '' || $mode === '—') {
																					$mode = '—';
																				}
																				?>
																				<tr>
																					<td class="col-date"><?php echo htmlspecialchars($pay['voucher_date'] ?? '—'); ?></td>
																					<td class="col-particulars">
																						<div class="egcn-pay-particular"><?php echo htmlspecialchars($child_title); ?></div>
																						<?php if ($show_bill) { ?>
																							<div class="bw-muted">Bill <?php echo htmlspecialchars($bill_ref); ?></div>
																						<?php } ?>
																					</td>
																					<td class="col-vch"><span class="egcn-pay-vch"><?php echo htmlspecialchars($pay['voucher_no'] ?? '—'); ?></span></td>
																					<td class="col-mode"><span class="egcn-pay-mode"><?php echo htmlspecialchars($mode); ?></span></td>
																					<td class="col-amt num"><?php echo htmlspecialchars($pay['amount'] ?? '0.00'); ?></td>
																				</tr>
																			<?php } ?>
																		</tbody>
																</table>
															</div>
														</td>
													</tr>
												<?php } ?>
											<?php } ?>
										</tbody>
									</table>
									</div>
							<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>

	<div class="ew-v2-modal-backdrop" id="egcnViewModal">
		<div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
				<h3 id="egcnViewModalTitle">GCN Profit &amp; Loss</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body" id="egcnViewModalBody">
				<p class="text-muted" style="margin:0;">Loading…</p>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
			</div>
		</div>
	</div>

	<?php if (!empty($list_rows)) { ?>
	<script>
		$(function() {
			/* DataTables re-sorts child rows away from parent — keep native table for accordion. */
			if (window.applyEwListLayout) {
				window.applyEwListLayout();
			}

			function egcnPlSetOpen($parent, open) {
				var group = String($parent.attr('data-egcn-group') || '');
				if (!group) {
					return;
				}
				var $detail = $parent.next('tr.egcn-pl-detail[data-egcn-group="' + group + '"]');
				var $btn = $parent.find('.bw-toggle').first();
				if (!$detail.length) {
					return;
				}
				if (open) {
					$detail.removeAttr('hidden').show();
					$btn.attr('aria-expanded', 'true');
					$parent.addClass('is-open');
				} else {
					$detail.attr('hidden', true).hide();
					$btn.attr('aria-expanded', 'false');
					$parent.removeClass('is-open');
				}
			}

			$(document).on('click', '#egcn_list_table .bw-toggle', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $parent = $(this).closest('tr.egcn-pl-parent');
				egcnPlSetOpen($parent, !$parent.hasClass('is-open'));
			});

			$('#egcn_expand_all').on('click', function() {
				$('#egcn_list_table tbody tr.egcn-pl-parent').each(function() {
					egcnPlSetOpen($(this), true);
				});
			});

			$('#egcn_collapse_all').on('click', function() {
				$('#egcn_list_table tbody tr.egcn-pl-parent').each(function() {
					egcnPlSetOpen($(this), false);
				});
			});

			function egcnEscHtml(str) {
				return String(str == null ? '' : str)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			function egcnMetaField(label, value) {
				return '<div class="egcn-meta-field"><dt>' + egcnEscHtml(label) + '</dt><dd>' + egcnEscHtml(value || '—') + '</dd></div>';
			}

			function egcnPlBreakdownTable(title, headers, rows, keys) {
				var html = '<p class="egcn-view-section-title">' + egcnEscHtml(title) + '</p>';
				if (!rows || !rows.length) {
					return html + '<p class="text-muted" style="margin:0 0 16px;">No entries.</p>';
				}
				html += '<table class="egcn-view-table"><thead><tr>';
				for (var h = 0; h < headers.length; h++) {
					var cls = (h === headers.length - 1) ? ' class="num"' : '';
					html += '<th' + cls + '>' + egcnEscHtml(headers[h]) + '</th>';
				}
				html += '</tr></thead><tbody>';
				for (var r = 0; r < rows.length; r++) {
					var row = rows[r] || {};
					html += '<tr>';
					for (var k = 0; k < keys.length; k++) {
						var numCls = (k === keys.length - 1) ? ' class="num"' : '';
						html += '<td' + numCls + '>' + egcnEscHtml(row[keys[k]] || '—') + '</td>';
					}
					html += '</tr>';
				}
				html += '</tbody></table>';
				return html;
			}

			function egcnRenderPlView(data) {
				if (!data) {
					return '<p class="text-danger">No data.</p>';
				}
				var pr = parseFloat(data.profit_raw || 0);
				var profitClass = pr > 0.009 ? 'profit-positive' : (pr < -0.009 ? 'profit-negative' : 'profit-breakeven');
				var metaFields = [
					['GCN No.', data.grn_no],
					['Booking date', data.grn_date],
					['Customer', data.customer_label],
					['Vendor', data.vendor_label],
					['Route', data.route_label]
				];
				var splitAt = Math.ceil(metaFields.length / 2);
				var leftHtml = '';
				var rightHtml = '';
				for (var mf = 0; mf < metaFields.length; mf++) {
					var chunk = egcnMetaField(metaFields[mf][0], metaFields[mf][1]);
					if (mf < splitAt) {
						leftHtml += chunk;
					} else {
						rightHtml += chunk;
					}
				}
				var html = '';
				html += '<div class="egcn-view-meta"><div class="egcn-view-meta-col">' + leftHtml + '</div>';
				html += '<div class="egcn-view-meta-col">' + rightHtml + '</div></div>';
				html += '<div class="egcn-view-kpi">';
				html += '<div class="kpi"><div class="kpi-label">Revenue</div><div class="kpi-value">' + egcnEscHtml(data.revenue || data.customer_revenue) + '</div></div>';
				html += '<div class="kpi"><div class="kpi-label">Total cost</div><div class="kpi-value">' + egcnEscHtml(data.total_cost) + '</div></div>';
				html += '<div class="kpi kpi-' + profitClass + '"><div class="kpi-label">Profit / Loss</div><div class="kpi-value ' + profitClass + '">' + egcnEscHtml(data.profit_amount || '0.00') + '</div></div>';
				html += '</div>';
				html += '<table class="egcn-view-table"><thead><tr><th>Component</th><th class="num">Amount (₹)</th></tr></thead><tbody>';
				html += '<tr><td>Vendor cost (purchase bills)</td><td class="num">' + egcnEscHtml(data.bill_vendor_cost || '0.00') + '</td></tr>';
				html += '<tr><td>Vendor cost (direct GCN voucher)</td><td class="num">' + egcnEscHtml(data.direct_vendor_cost || '0.00') + '</td></tr>';
				html += '<tr><td>Petty cash expenses</td><td class="num">' + egcnEscHtml(data.petty_cash) + '</td></tr>';
				html += '<tr class="egcn-total"><td>Total vendor cost</td><td class="num">' + egcnEscHtml(data.vendor_cost) + '</td></tr>';
				html += '<tr class="egcn-total"><td>Total cost (vendor + petty cash)</td><td class="num">' + egcnEscHtml(data.total_cost) + '</td></tr>';
				html += '<tr><td>Vendor paid (against bills)</td><td class="num">' + egcnEscHtml(data.allocated_vendor_paid || '0.00') + '</td></tr>';
				html += '<tr><td>Vendor paid (direct)</td><td class="num">' + egcnEscHtml(data.direct_vendor_paid || '0.00') + '</td></tr>';
				html += '<tr class="egcn-total"><td>Vendor balance payable</td><td class="num">' + egcnEscHtml(data.vendor_balance) + '</td></tr>';
				html += '</tbody></table>';

				var bd = data.breakdown || {};
				html += egcnPlBreakdownTable('Revenue breakup', ['Description', 'Amount (₹)'], bd.revenue_rows || [], ['label', 'amount']);
				html += egcnPlBreakdownTable(
					'Purchase bills — vendor cost',
					['Bill no.', 'Date', 'Vendor', 'Purpose', 'Amount (₹)'],
					bd.purchase_bill_rows || [],
					['bill_no', 'bill_date', 'vendor_label', 'expense_type', 'amount']
				);
				html += egcnPlBreakdownTable(
					'Payments — what was paid',
					['Voucher', 'Date', 'Vendor', 'Bill / reference', 'Expense type', 'Mode', 'Amount (₹)'],
					bd.payment_rows || [],
					['voucher_no', 'voucher_date', 'vendor_label', 'reference', 'expense_type', 'payment_mode', 'amount']
				);
				html += egcnPlBreakdownTable(
					'Petty cash expenses',
					['Voucher', 'Date', 'Expense type', 'Paid to', 'Remarks', 'Amount (₹)'],
					bd.petty_cash_rows || [],
					['voucher_no', 'voucher_date', 'expense_type', 'paid_to', 'remarks', 'amount']
				);
				return html;
			}

			$(document).on('click', '.btn-view-gcn-pl', function() {
				var gcnKey = $(this).data('gcnKey') || $(this).attr('data-gcn-key') || '';
				var gcnNo = $(this).data('gcnNo') || $(this).attr('data-gcn-no') || '';
				if (!gcnKey) return;
				$('#egcnViewModalTitle').text('GCN Profit & Loss' + (gcnNo ? ' — ' + gcnNo : ''));
				$('#egcnViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
				if (typeof ewV2OpenModal === 'function') {
					ewV2OpenModal('egcnViewModal');
				} else {
					$('#egcnViewModal').addClass('open');
				}
				$.getJSON('expense_gcn_pl_data.php', { cmd: 'detail', gcn_key: gcnKey }, function(r) {
					if (!r || !r.ok || !r.data) {
						$('#egcnViewModalBody').html('<p class="text-danger">' + egcnEscHtml(r && r.message ? r.message : 'Could not load details.') + '</p>');
						return;
					}
					$('#egcnViewModalBody').html(egcnRenderPlView(r.data));
				}).fail(function() {
					$('#egcnViewModalBody').html('<p class="text-danger">Request failed.</p>');
				});
			});
		});
	</script>
	<?php } ?>
</body>

</html>
