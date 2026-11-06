<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_general_helpers.php');

expense_general_ensure_schema($conn);
expense_require_admin();

$list_rows = expense_general_fetch_list($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		#dataTable1 th.col-actions,
		#dataTable1 td.col-actions {
			text-align: center;
			width: 120px;
			min-width: 120px;
		}

		#dataTable1 .col-actions .act-link {
			margin: 0 2px;
		}

		#dataTable1 .col-actions .act-view {
			background: #e8edf3;
			color: #0A1E3D;
		}

		#dataTable1 .col-actions .act-view:hover {
			background: #d5dde8;
		}

		.egen-view-meta {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 12px 20px;
			margin-bottom: 18px;
		}

		.egen-view-meta dt {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin: 0 0 4px;
		}

		.egen-view-meta dd {
			margin: 0;
			font-size: 14px;
			font-weight: 600;
			color: #0f172a;
		}

		.egen-view-amounts {
			width: 100%;
			border-collapse: collapse;
			margin: 0 0 16px;
		}

		.egen-view-amounts th,
		.egen-view-amounts td {
			padding: 10px 12px;
			border: 1px solid #e2e8f0;
			font-size: 13px;
		}

		.egen-view-amounts th {
			background: #f1f5f9;
			font-weight: 700;
			text-align: left;
		}

		.egen-view-amounts td.num {
			text-align: right;
			white-space: nowrap;
		}

		.egen-view-amounts tr.total td {
			font-weight: 800;
			background: #f8fafc;
		}

		.egen-view-desc {
			margin: 0 0 16px;
			padding: 12px 14px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			font-size: 13px;
			line-height: 1.5;
			color: #334155;
		}

		@media (max-width: 640px) {
			.egen-view-meta {
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
								<h1 class="ew-page-title">General Expense</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>General Expense List</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<a href="expense_general.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<?php if (empty($list_rows)) { ?>
								<div class="egen-empty" style="padding:24px;text-align:center;color:#64748b;">
									No general expenses saved yet.
									<br><br>
									<a href="expense_general.php?create=1" class="ew-btn-v2 ew-btn-v2-primary"><i class="fa fa-plus"></i> Add General Expense</a>
								</div>
							<?php } else { ?>
									<table class="table table-bordered table-striped egen_list_tab" id="dataTable1">
										<thead>
											<tr>
												<th>S.No</th>
												<th>Expense No</th>
												<th>Date</th>
												<th>Vendor</th>
												<th>Expense Type</th>
												<th>Amount</th>
												<th>GST</th>
												<th>TDS</th>
												<th>Net Payable</th>
												<th>Payment</th>
												<th>Description</th>
												<th>Last Updated</th>
												<th class="table-title sorting_disabled col-actions">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$i = 1;
											foreach ($list_rows as $row) {
												$expense_id = (int) $row['general_expense_id'];
												$edit_url = 'expense_general.php?id=' . $expense_id;
												?>
												<tr>
													<td><?php echo $i++; ?></td>
													<td><strong><?php echo htmlspecialchars($row['expense_no']); ?></strong></td>
													<td><?php echo htmlspecialchars($row['expense_date']); ?></td>
													<td><?php echo htmlspecialchars($row['vendor_label'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['category_label'] ?: '—'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['expense_amount']); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['gst_amount']); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['tds_amount']); ?></td>
													<td class="num"><strong><?php echo htmlspecialchars($row['net_payable']); ?></strong></td>
													<td><?php echo htmlspecialchars($row['payment_mode'] ?: '—'); ?></td>
													<td><span class="egen-desc" title="<?php echo htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['description'] ?: '—'); ?></span></td>
													<td><?php echo htmlspecialchars($row['updated_at']); ?></td>
													<td class="col-actions">
														<span class="act-wrap">
															<button type="button" class="act-link act-view btn-view-general-expense" title="View"
																data-id="<?php echo $expense_id; ?>"
																data-no="<?php echo htmlspecialchars($row['expense_no'], ENT_QUOTES, 'UTF-8'); ?>">
																<i class="fa fa-eye"></i>
															</button>
															<a class="act-link act-edit" href="<?php echo htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8'); ?>" title="Edit"><i class="fa fa-pencil"></i></a>
															<button type="button" class="act-link act-delete btn-delete-general-expense" title="Delete"
																data-id="<?php echo $expense_id; ?>"
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

	<div class="ew-v2-modal-backdrop" id="egenViewModal">
		<div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
				<h3 id="egenViewModalTitle">General expense details</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body" id="egenViewModalBody">
				<p class="text-muted" style="margin:0;">Loading…</p>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
				<a href="#" class="btn btn-primary" id="btn_egen_download_pdf" style="display:none;" target="_blank" rel="noopener"><i class="fa fa-download"></i> Download PDF</a>
			</div>
		</div>
	</div>

	<?php if (!empty($list_rows)) { ?>
	<script>
		$(function() {
			if (window.applyEwListLayout) {
				window.applyEwListLayout();
			}

			function egenEscHtml(str) {
				return String(str == null ? '' : str)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			function egenRenderView(data) {
				if (!data) {
					return '<p class="text-danger">No data.</p>';
				}
				var html = '';
				html += '<div class="egen-view-meta">';
				html += '<div><dt>Expense No</dt><dd>' + egenEscHtml(data.expense_no || '—') + '</dd></div>';
				html += '<div><dt>Expense date</dt><dd>' + egenEscHtml(data.expense_date || '—') + '</dd></div>';
				html += '<div><dt>Vendor</dt><dd>' + egenEscHtml(data.vendor_label || '—') + '</dd></div>';
				html += '<div><dt>Expense type</dt><dd>' + egenEscHtml(data.category_label || '—') + '</dd></div>';
				html += '<div><dt>Payment mode</dt><dd>' + egenEscHtml(data.payment_mode_label || data.payment_mode || '—') + '</dd></div>';
				html += '<div><dt>Last updated</dt><dd>' + egenEscHtml(data.updated_at || '—') + '</dd></div>';
				html += '</div>';

				if (data.description) {
					html += '<div class="egen-view-desc"><strong>Description</strong><br>' + egenEscHtml(data.description) + '</div>';
				}

				html += '<table class="egen-view-amounts"><thead><tr><th>Particular</th><th class="num">Amount (₹)</th></tr></thead><tbody>';
				html += '<tr><td>Expense amount</td><td class="num">' + egenEscHtml(data.expense_amount) + '</td></tr>';
				html += '<tr><td>GST (' + egenEscHtml(data.gst_percent_display || '0%') + ')</td><td class="num">' + egenEscHtml(data.gst_amount) + '</td></tr>';
				html += '<tr><td>TDS</td><td class="num">' + egenEscHtml(data.tds_amount) + '</td></tr>';
				html += '<tr><td>Other deduction</td><td class="num">' + egenEscHtml(data.other_deduction) + '</td></tr>';
				html += '<tr class="total"><td>Net payable</td><td class="num">' + egenEscHtml(data.net_payable) + '</td></tr>';
				html += '</tbody></table>';

				var bank = data.payment_bank_name || '';
				var ifsc = data.payment_ifsc || '';
				var branch = data.payment_bank_branch || '';
				if (bank || ifsc || branch) {
					html += '<div class="egen-view-meta">';
					html += '<div><dt>Bank name</dt><dd>' + egenEscHtml(bank || '—') + '</dd></div>';
					html += '<div><dt>IFSC</dt><dd>' + egenEscHtml(ifsc || '—') + '</dd></div>';
					html += '<div><dt>Branch</dt><dd>' + egenEscHtml(branch || '—') + '</dd></div>';
					html += '</div>';
				}
				return html;
			}

			$(document).on('click', '.btn-view-general-expense', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				if (!id) {
					return;
				}
				$('#egenViewModalTitle').text('General expense' + (no ? ' — ' + no : ''));
				$('#egenViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
				$('#btn_egen_download_pdf').hide().attr('href', '#');
				if (typeof ewV2OpenModal === 'function') {
					ewV2OpenModal('egenViewModal');
				} else {
					$('#egenViewModal').addClass('open');
				}
				$.getJSON('expense_general_data.php', { cmd: 'view', general_expense_id: id }, function(r) {
					if (!r || r.status !== 0 || !r.data) {
						$('#egenViewModalBody').html('<p class="text-danger">' + egenEscHtml(r && r.message ? r.message : 'Could not load details.') + '</p>');
						return;
					}
					var titleNo = r.data.expense_no || no || ('#' + id);
					$('#egenViewModalTitle').text('General expense — ' + titleNo);
					$('#egenViewModalBody').html(egenRenderView(r.data));
					$('#btn_egen_download_pdf').attr('href', 'expense_general_pdf.php?general_expense_id=' + encodeURIComponent(id) + '&download=1').show();
				}).fail(function() {
					$('#egenViewModalBody').html('<p class="text-danger">Request failed.</p>');
				});
			});

			$(document).on('click', '.btn-delete-general-expense', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				if (!id || !confirm('Delete general expense ' + no + '?')) return;
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
