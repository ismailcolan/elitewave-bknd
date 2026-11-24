<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_general_helpers.php');
require_once('include/expense_schema.php');

expense_general_ensure_schema($conn);
expense_require_admin();

$list_rows = expense_general_gcn_list_per_consignment($conn, expense_general_fetch_list($conn, 'gcn'));
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

		#egcn_list_table .col-actions .act-view {
			background: #e8edf3;
			color: #0A1E3D;
		}

		#egcn_list_table .col-actions .act-view:hover {
			background: #d5dde8;
			color: #0A1E3D;
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
					<div class="ew-page-v2 ew-page-v2--wide-table">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">GCN Profit &amp; Loss</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>GCN Profit &amp; Loss List</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<?php if (empty($list_rows)) { ?>
								<div class="egcn-empty" style="padding:24px;text-align:center;color:#64748b;">
									No GCN payment vouchers yet. Create one from <strong>Payment Voucher</strong> and choose type <strong>GCN</strong>.
								</div>
							<?php } else { ?>
									<table class="table table-bordered table-striped egcn_list_tab" id="egcn_list_table">
										<thead>
											<tr>
												<th>S.No</th>
												<th class="col-gcn">GCN No</th>
												<th>GCN Date</th>
												<th>Voucher Code</th>
												<th>Voucher Date</th>
												<th>Vendor</th>
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
												$profit_class = ((float) ($row['profit_raw'] ?? 0) >= 0) ? 'profit-positive' : 'profit-negative';
												$voucher_id = (int) $row['general_expense_id'];
												$edit_url = 'expense_general.php?id=' . $voucher_id;
												$gcn_no = trim((string) ($row['grn_no'] ?? ''));
												$gcn_date = trim((string) ($row['grn_date'] ?? ''));
												$list_gcn_key = trim((string) ($row['list_gcn_key'] ?? ''));
												?>
												<tr>
													<td><?php echo $i++; ?></td>
													<td class="col-gcn">
														<span class="txn-gcn-no"><?php echo htmlspecialchars($gcn_no !== '' ? $gcn_no : '—'); ?></span>
													</td>
													<td><?php echo htmlspecialchars($gcn_date !== '' ? $gcn_date : '—'); ?></td>
													<td><strong><?php echo htmlspecialchars($row['expense_no']); ?></strong></td>
													<td><?php echo htmlspecialchars($row['expense_date']); ?></td>
													<td><?php echo htmlspecialchars($row['vendor_label'] ?: '—'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['revenue_without_gst'] ?? '0.00'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['expenses_without_gst'] ?? $row['expense_amount']); ?></td>
													<td class="num <?php echo $profit_class; ?>"><span class="profit-value"><?php echo htmlspecialchars($row['profit_amount'] ?? '0.00'); ?></span></td>
													<td><?php echo htmlspecialchars($row['updated_at']); ?></td>
													<td class="col-actions">
														<span class="act-wrap">
															<button type="button" class="act-link act-view btn-view-gcn-voucher" title="View"
																data-id="<?php echo $voucher_id; ?>"
																data-gcn-key="<?php echo htmlspecialchars($list_gcn_key, ENT_QUOTES, 'UTF-8'); ?>"
																data-gcn-no="<?php echo htmlspecialchars($gcn_no, ENT_QUOTES, 'UTF-8'); ?>"
																data-no="<?php echo htmlspecialchars($row['expense_no'], ENT_QUOTES, 'UTF-8'); ?>">
																<i class="fa fa-eye"></i>
															</button>
															<a class="act-link act-edit" href="<?php echo htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8'); ?>" title="Edit">
																<i class="fa fa-pencil"></i>
															</a>
															<button type="button" class="act-link act-delete btn-delete-gcn-voucher" title="Delete"
																data-id="<?php echo $voucher_id; ?>"
																data-no="<?php echo htmlspecialchars($row['expense_no'], ENT_QUOTES, 'UTF-8'); ?>">
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

			function egcnMetaField(label, value) {
				return '<div class="egcn-meta-field"><dt>' + egcnEscHtml(label) + '</dt><dd>' + egcnEscHtml(value || '—') + '</dd></div>';
			}

			function egcnRenderView(data) {
				if (!data) {
					return '<p class="text-danger">No data.</p>';
				}
				var profitClass = (parseFloat(data.profit_raw) >= 0) ? 'profit-positive' : 'profit-negative';
				var isGroup = data.expense_mode === 'group' && !data.view_scope_gcn_key;
				var html = '';
				var metaFields = [];
				if (data.expense_no) {
					metaFields.push(['Voucher code', data.expense_no]);
				}
				metaFields.push(['GCN No', data.grn_no]);
				metaFields.push(['GCN date', data.grn_date]);
				metaFields.push(['Route', data.route_label]);
				metaFields.push(['Mode of transport', data.mode_of_transport]);
				if (!isGroup) {
					metaFields.push(['Consignor', data.consignor]);
					metaFields.push(['Consignee', data.consignee]);
					metaFields.push(['Invoice', data.invoice_no]);
				}
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
				html += '<div class="egcn-view-meta">';
				html += '<div class="egcn-view-meta-col">' + leftHtml + '</div>';
				html += '<div class="egcn-view-meta-col">' + rightHtml + '</div>';
				html += '</div>';
				html += '<div class="egcn-view-kpi">';
				html += '<div class="kpi"><div class="kpi-label">Revenue</div><div class="kpi-value">' + egcnEscHtml(data.revenue_without_gst || '0.00') + '</div></div>';
				html += '<div class="kpi"><div class="kpi-label">Expenses</div><div class="kpi-value">' + egcnEscHtml(data.expenses_without_gst || '0.00') + '</div></div>';
				html += '<div class="kpi kpi-' + profitClass + '"><div class="kpi-label">Profit</div><div class="kpi-value ' + profitClass + '">' + egcnEscHtml(data.profit_amount || '0.00') + '</div></div>';
				html += '</div>';
				html += '<p class="egcn-view-section-title">Revenue breakup</p>';
				if (isGroup && data.gcns && data.gcns.length) {
					html += '<table class="egcn-view-table"><thead><tr>';
					html += '<th>GCN No</th><th>Date</th><th>Route</th><th>Mode</th><th class="num">Revenue</th>';
					html += '</tr></thead><tbody>';
					for (var g = 0; g < data.gcns.length; g++) {
						var gc = data.gcns[g];
						html += '<tr>';
						html += '<td>' + egcnEscHtml(gc.grn_no) + '</td>';
						html += '<td>' + egcnEscHtml(gc.grn_date) + '</td>';
						html += '<td>' + egcnEscHtml(gc.route_label) + '</td>';
						html += '<td>' + egcnEscHtml(gc.mode_of_transport || '—') + '</td>';
						html += '<td class="num">' + egcnEscHtml(gc.revenue_without_gst) + '</td>';
						html += '</tr>';
					}
					html += '</tbody></table>';
				} else if (data.revenue_breakup && data.revenue_breakup.length) {
					html += '<table class="egcn-view-table"><thead><tr>';
					html += '<th>Description</th><th class="num">Amount (₹)</th>';
					html += '</tr></thead><tbody>';
					for (var rb = 0; rb < data.revenue_breakup.length; rb++) {
						var rev = data.revenue_breakup[rb];
						var bold = rev.is_total ? ' class="egcn-total"' : '';
						html += '<tr' + bold + '><td>' + egcnEscHtml(rev.label || '') + '</td><td class="num">' + egcnEscHtml(rev.amount || '') + '</td></tr>';
					}
					html += '</tbody></table>';
				} else {
					html += '<p class="text-muted" style="margin:0 0 18px;">No revenue breakup available.</p>';
				}
				html += '<p class="egcn-view-section-title">Expense breakup</p>';
				var lines = data.profit_lines || [];
				if (!lines.length) {
					html += '<p class="text-muted" style="margin:0;">No expense lines recorded.</p>';
				} else {
					html += '<table class="egcn-view-table"><thead><tr>';
					html += '<th>#</th><th>Date</th><th>Vendor</th><th>Category</th><th class="num">Amount</th><th class="num">GST</th><th class="num">TDS</th>';
					html += '</tr></thead><tbody>';
					for (var i = 0; i < lines.length; i++) {
						var ln = lines[i];
						html += '<tr>';
						html += '<td>' + egcnEscHtml(ln.line_no || (i + 1)) + '</td>';
						html += '<td>' + egcnEscHtml(ln.expense_date || '—') + '</td>';
						html += '<td>' + egcnEscHtml(ln.vendor_label || '—') + '</td>';
						html += '<td>' + egcnEscHtml(ln.category_label || '—') + '</td>';
						html += '<td class="num">' + egcnEscHtml(ln.expense_amount) + '</td>';
						html += '<td class="num">' + egcnEscHtml(ln.gst_amount) + '</td>';
						html += '<td class="num">' + egcnEscHtml(ln.tds_amount) + '</td>';
						html += '</tr>';
					}
					html += '</tbody></table>';
				}
				return html;
			}

			$(document).on('click', '.btn-view-gcn-voucher', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				var gcnKey = $(this).data('gcnKey') || $(this).attr('data-gcn-key') || '';
				var gcnNo = $(this).data('gcnNo') || $(this).attr('data-gcn-no') || '';
				if (!id) return;
				var titleGcn = gcnNo ? (' — ' + gcnNo) : (no ? ' — ' + no : '');
				$('#egcnViewModalTitle').text('GCN profit & loss' + titleGcn);
				$('#egcnViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
				$('#btn_egcn_download_pdf').hide();
				if (typeof ewV2OpenModal === 'function') {
					ewV2OpenModal('egcnViewModal');
				} else {
					$('#egcnViewModal').addClass('open');
				}
				var req = { cmd: 'view', general_expense_id: id };
				if (gcnKey) {
					req.gcn_key = gcnKey;
				}
				$.getJSON('expense_general_data.php', req, function(r) {
					if (!r || r.status !== 0 || !r.data) {
						$('#egcnViewModalBody').html('<p class="text-danger">' + egcnEscHtml(r && r.message ? r.message : 'Could not load details.') + '</p>');
						return;
					}
					var modalGcn = r.data.grn_no && r.data.grn_no.indexOf(',') < 0 ? r.data.grn_no : gcnNo;
					$('#egcnViewModalTitle').text('GCN profit & loss' + (modalGcn ? ' — ' + modalGcn : ' — ' + (r.data.expense_no || no || id)));
					$('#egcnViewModalBody').html(egcnRenderView(r.data));
					var pdfUrl = 'expense_gcn_voucher_pdf.php?general_expense_id=' + encodeURIComponent(id) + '&download=1';
					if (gcnKey) {
						pdfUrl += '&gcn_key=' + encodeURIComponent(gcnKey);
					}
					$('#btn_egcn_download_pdf').attr('href', pdfUrl).show();
				}).fail(function() {
					$('#egcnViewModalBody').html('<p class="text-danger">Request failed.</p>');
				});
			});

			$(document).on('click', '.btn-delete-gcn-voucher', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				if (!id || !confirm('Delete voucher ' + no + '?')) return;
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.post('expense_general_data.php', { cmd: 'delete', general_expense_id: id }, function(r) {
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
