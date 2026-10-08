<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_gcn_helpers.php');
require_once('include/expense_schema.php');

expense_gcn_ensure_schema($conn);
expense_require_admin();

$list_rows = expense_gcn_fetch_list($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.egcn-gcn-cell .gcn-no-text {
			font-weight: 700;
			color: #0A1E3D;
		}

		.egcn-gcn-tags {
			display: flex;
			flex-wrap: wrap;
			gap: 6px;
			margin-top: 6px;
			justify-content: center;
		}

		.egcn-gcn-tag {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 6px;
			background: #f1f5f9;
			border: 1px solid #e2e8f0;
			color: #334155;
			font-size: 12px;
			font-weight: 600;
			line-height: 1.4;
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

		#egcn_list_table .col-actions .act-view {
			background: #e8edf3;
			color: #0A1E3D;
		}

		#egcn_list_table .col-actions .act-view:hover {
			background: #d5dde8;
			color: #0A1E3D;
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

		.egcn-view-kpi .kpi-value.profit-positive { color: #15803d; }
		.egcn-view-kpi .kpi-value.profit-negative { color: #dc2626; }

		.egcn-view-meta {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 10px 16px;
			margin-bottom: 18px;
			font-size: 13px;
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

		.egcn-view-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.egcn-view-table th,
		.egcn-view-table td {
			border: 1px solid #e2e8f0;
			padding: 8px 10px;
			text-align: left;
		}

		.egcn-view-table th {
			background: #f1f5f9;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: .03em;
			color: #475569;
		}

		.egcn-view-section-title {
			font-size: 13px;
			font-weight: 700;
			color: #0A1E3D;
			margin: 0 0 10px;
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
					<div class="ew-page-v2 ew-page-v2--wide-table">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Expense against GCN</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>GCN Expense List</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<a href="expense_gcn.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<?php if (empty($list_rows)) { ?>
								<div class="egcn-empty" style="padding:24px;text-align:center;color:#64748b;">
									No GCN expenses saved yet.
									<br><br>
									<a href="expense_gcn.php?create=1" class="ew-btn-v2 ew-btn-v2-primary"><i class="fa fa-plus"></i> Add Expense against GCN</a>
								</div>
							<?php } else { ?>
									<table class="table table-bordered table-striped egcn_list_tab" id="egcn_list_table">
										<thead>
											<tr>
												<th>S.No</th>
												<th>Type</th>
												<th class="col-gcn">GCN No</th>
												<th>GCN Date</th>
												<th>Route</th>
												<th>Revenue</th>
												<th>Expenses</th>
												<th>Profit</th>
												<th>Last Updated</th>
												<th class="table-title sorting_disabled col-actions">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$i = 1;
											foreach ($list_rows as $row) {
												$profit_class = ((float) $row['profit_raw'] >= 0) ? 'profit-positive' : 'profit-negative';
												$is_group = (($row['expense_mode'] ?? 'single') === 'group');
												if ($is_group) {
													$edit_url = 'expense_gcn.php?group_id=' . (int) ($row['group_id'] ?? $row['gcn_expense_id']);
													$delete_label = $row['group_label'] ?? ('Group (' . (int) ($row['gcn_count'] ?? 0) . ' GCNs)');
												} else {
													$edit_url = 'expense_gcn.php?gcn_key=' . urlencode($row['gcn_key']);
													$delete_label = $row['grn_no'] ?? '';
												}
												?>
												<tr>
													<td><?php echo $i++; ?></td>
													<td>
														<span class="egcn-mode-badge <?php echo $is_group ? 'mode-group' : 'mode-single'; ?>">
															<?php echo $is_group ? 'Group' : 'Single'; ?>
														</span>
													</td>
													<td class="col-gcn egcn-gcn-cell">
														<?php if ($is_group) { ?>
															<div class="egcn-gcn-tags">
																<?php foreach (($row['gcn_list'] ?? array()) as $gcn_no) {
																	if ($gcn_no === '') continue; ?>
																	<span class="egcn-gcn-tag"><?php echo htmlspecialchars($gcn_no); ?></span>
																<?php } ?>
															</div>
														<?php } else { ?>
															<span class="gcn-no-text"><?php echo htmlspecialchars($row['grn_no']); ?></span>
														<?php } ?>
													</td>
													<td><?php echo htmlspecialchars($row['grn_date']); ?></td>
													<td>
														<?php
														if ($is_group && !empty($row['route_list']) && count($row['route_list']) > 1) {
															$routes_title = htmlspecialchars(implode(' | ', $row['route_list']), ENT_QUOTES, 'UTF-8');
															echo '<span class="egcn-route-multi" title="' . $routes_title . '"><span class="route-count">' . count($row['route_list']) . ' routes</span></span>';
														} else {
															echo htmlspecialchars($row['route_label'] ?: '—');
														}
														?>
													</td>
													<td class="num"><?php echo htmlspecialchars($row['revenue_without_gst']); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['expenses_without_gst']); ?></td>
													<td class="num <?php echo $profit_class; ?>"><span class="profit-value"><?php echo htmlspecialchars($row['profit_amount']); ?></span></td>
													<td><?php echo htmlspecialchars($row['updated_at']); ?></td>
													<td class="col-actions">
														<span class="act-wrap">
															<button type="button" class="act-link act-view btn-view-gcn-expense" title="View"
																data-id="<?php echo (int) $row['gcn_expense_id']; ?>">
																<i class="fa fa-eye"></i>
															</button>
															<a class="act-link act-edit" href="<?php echo htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8'); ?>" title="Edit">
																<i class="fa fa-pencil"></i>
															</a>
															<button type="button" class="act-link act-delete btn-delete-gcn-expense" title="Delete"
																data-id="<?php echo (int) $row['gcn_expense_id']; ?>"
																data-gcn="<?php echo htmlspecialchars($delete_label, ENT_QUOTES, 'UTF-8'); ?>">
																<i class="fa fa-trash-o"></i>
															</button>
														</span>
													</td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
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
				<h3 id="egcnViewModalTitle">GCN expense details</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body" id="egcnViewModalBody">
				<p class="text-muted" style="margin:0;">Loading…</p>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
				<a href="#" class="btn btn-primary" id="btn_egcn_download_pdf" style="display:none;" target="_blank" rel="noopener"><i class="fa fa-download"></i> Download PDF</a>
			</div>
		</div>
	</div>

	<?php if (!empty($list_rows)) { ?>
	<script>
		$(function() {
			if ($.fn.dataTable && $('#egcn_list_table').length) {
				$('#egcn_list_table').dataTable({
					sPaginationType: 'full_numbers',
					oSearch: { sSearch: '', bSmart: false, bRegex: false, bCaseInsensitive: true },
					aoColumnDefs: [{ bSortable: false, aTargets: [0, -1] }]
				});
				if (window.applyEwListLayout) {
					window.applyEwListLayout();
				}
			}

			function egcnEscHtml(str) {
				return String(str == null ? '' : str)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			function egcnRenderView(data) {
				if (!data) {
					return '<p class="text-danger">No data.</p>';
				}
				var profitClass = (parseFloat(data.profit_raw) >= 0) ? 'profit-positive' : 'profit-negative';
				var modeLabel = data.expense_mode === 'group' ? 'Group' : 'Single';
				var html = '';
				html += '<div class="egcn-view-meta">';
				html += '<div><dt>Type</dt><dd>' + egcnEscHtml(modeLabel) + '</dd></div>';
				html += '<div><dt>GCN No</dt><dd>' + egcnEscHtml(data.grn_no || '—') + '</dd></div>';
				html += '<div><dt>GCN date</dt><dd>' + egcnEscHtml(data.grn_date || '—') + '</dd></div>';
				html += '<div><dt>Route</dt><dd>' + egcnEscHtml(data.route_label || '—') + '</dd></div>';
				if (data.expense_mode !== 'group') {
					html += '<div><dt>Consignor</dt><dd>' + egcnEscHtml(data.consignor || '—') + '</dd></div>';
					html += '<div><dt>Consignee</dt><dd>' + egcnEscHtml(data.consignee || '—') + '</dd></div>';
					if (data.invoice_no) {
						html += '<div><dt>Invoice</dt><dd>' + egcnEscHtml(data.invoice_no) + '</dd></div>';
					}
				}
				html += '<div><dt>Mode of transport</dt><dd>' + egcnEscHtml(data.mode_of_transport || '—') + '</dd></div>';
				html += '</div>';

				html += '<div class="egcn-view-kpi">';
				html += '<div class="kpi"><div class="kpi-label">Revenue</div><div class="kpi-value">' + egcnEscHtml(data.revenue_without_gst) + '</div></div>';
				html += '<div class="kpi"><div class="kpi-label">Expenses</div><div class="kpi-value">' + egcnEscHtml(data.expenses_without_gst) + '</div></div>';
				html += '<div class="kpi"><div class="kpi-label">Profit</div><div class="kpi-value ' + profitClass + '">' + egcnEscHtml(data.profit_amount) + '</div></div>';
				html += '</div>';

				html += '<p class="egcn-view-section-title">Revenue breakup</p>';
				if (data.expense_mode === 'group' && data.gcns && data.gcns.length) {
					html += '<div class="table-responsive" style="margin-bottom:18px;"><table class="egcn-view-table"><thead><tr>';
					html += '<th>GCN No</th><th>Date</th><th>Route</th><th>Mode</th><th>Revenue</th>';
					html += '</tr></thead><tbody>';
					for (var g = 0; g < data.gcns.length; g++) {
						var gc = data.gcns[g];
						html += '<tr><td>' + egcnEscHtml(gc.grn_no) + '</td><td>' + egcnEscHtml(gc.grn_date) + '</td><td>' + egcnEscHtml(gc.route_label) + '</td><td>' + egcnEscHtml(gc.mode_of_transport || '—') + '</td><td>' + egcnEscHtml(gc.revenue_without_gst) + '</td></tr>';
					}
					html += '</tbody></table></div>';
				} else if (data.revenue_breakup && data.revenue_breakup.length) {
					html += '<div class="table-responsive" style="margin-bottom:18px;"><table class="egcn-view-table"><thead><tr>';
					html += '<th>Description</th><th>Amount (₹)</th>';
					html += '</tr></thead><tbody>';
					for (var rb = 0; rb < data.revenue_breakup.length; rb++) {
						var rev = data.revenue_breakup[rb];
						var revBold = rev.is_total ? ' style="font-weight:700;"' : '';
						html += '<tr' + revBold + '><td>' + egcnEscHtml(rev.label || '') + '</td><td>' + egcnEscHtml(rev.amount || '') + '</td></tr>';
					}
					html += '</tbody></table></div>';
				} else {
					html += '<p class="text-muted" style="margin:0 0 18px;">No revenue breakup available.</p>';
				}

				html += '<p class="egcn-view-section-title">Expense breakup</p>';
				if (!data.lines || !data.lines.length) {
					html += '<p class="text-muted" style="margin:0;">No expense lines recorded.</p>';
				} else {
					html += '<div class="table-responsive"><table class="egcn-view-table"><thead><tr>';
					html += '<th>#</th><th>Date</th><th>Vendor</th><th>Category</th><th>Amount</th><th>GST</th><th>TDS</th>';
					html += '</tr></thead><tbody>';
					for (var i = 0; i < data.lines.length; i++) {
						var ln = data.lines[i];
						html += '<tr>';
						html += '<td>' + egcnEscHtml(ln.line_no || (i + 1)) + '</td>';
						html += '<td>' + egcnEscHtml(ln.expense_date_display || ln.expense_date || '—') + '</td>';
						html += '<td>' + egcnEscHtml(ln.vendor_label || '—') + '</td>';
						html += '<td>' + egcnEscHtml(ln.category_label || '—') + '</td>';
						html += '<td>' + egcnEscHtml(ln.expense_amount) + '</td>';
						html += '<td>' + egcnEscHtml(ln.gst_amount) + '</td>';
						html += '<td>' + egcnEscHtml(ln.tds_amount) + '</td>';
						html += '</tr>';
					}
					html += '</tbody></table></div>';
				}
				return html;
			}

			$(document).on('click', '.btn-view-gcn-expense', function() {
				var id = $(this).data('id');
				if (!id) {
					return;
				}
				$('#egcnViewModalTitle').text('GCN expense details');
				$('#egcnViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
				$('#btn_egcn_download_pdf').hide().attr('href', '#');
				if (typeof ewV2OpenModal === 'function') {
					ewV2OpenModal('egcnViewModal');
				} else {
					$('#egcnViewModal').addClass('open');
				}
				$.getJSON('expense_gcn_data.php', { cmd: 'view', gcn_expense_id: id }, function(r) {
					if (!r || r.status !== 0 || !r.data) {
						$('#egcnViewModalBody').html('<p class="text-danger">' + egcnEscHtml(r && r.message ? r.message : 'Could not load details.') + '</p>');
						return;
					}
					var titleGcn = r.data.grn_no || ('#' + id);
					$('#egcnViewModalTitle').text('GCN expense — ' + titleGcn);
					$('#egcnViewModalBody').html(egcnRenderView(r.data));
					$('#btn_egcn_download_pdf').attr('href', 'expense_gcn_pdf.php?gcn_expense_id=' + encodeURIComponent(id)).show();
				}).fail(function() {
					$('#egcnViewModalBody').html('<p class="text-danger">Request failed.</p>');
				});
			});

			$(document).on('click', '.btn-delete-gcn-expense', function() {
				var id = $(this).data('id');
				var gcn = $(this).data('gcn') || '';
				if (!id) return;
				if (!confirm('Delete expense record for ' + gcn + '?')) {
					return;
				}
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.post('expense_gcn_data.php', { cmd: 'delete', gcn_expense_id: id }, function(r) {
					if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Delete failed.');
						$btn.prop('disabled', false);
						return;
					}
					location.reload();
				}, 'json').fail(function() {
					alert('Delete request failed.');
					$btn.prop('disabled', false);
				});
			});
		});
	</script>
	<?php } ?>
</body>

</html>
