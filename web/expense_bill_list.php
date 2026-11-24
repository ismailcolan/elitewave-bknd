<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_bill_helpers.php');

expense_bill_ensure_schema($conn);
expense_require_admin();

$list_rows = expense_bill_fetch_list($conn);
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

		.ebill-view-meta {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 12px 20px;
			margin-bottom: 18px;
		}

		.ebill-view-meta dt {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin: 0 0 4px;
		}

		.ebill-view-meta dd {
			margin: 0;
			font-size: 14px;
			font-weight: 600;
			color: #0f172a;
		}

		.ebill-view-desc {
			margin: 0 0 16px;
			padding: 12px 14px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			font-size: 13px;
			line-height: 1.5;
			color: #334155;
		}

		.ebill-attach-list {
			margin: 0;
			padding-left: 18px;
		}

		.ebill-attach-list li {
			margin: 4px 0;
		}

		@media (max-width: 640px) {
			.ebill-view-meta {
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
								<h1 class="ew-page-title">Purchase Bill Registration</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>Purchase Bill Registration List</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<a href="expense_bill.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<?php if (empty($list_rows)) { ?>
								<div class="ebill-empty" style="padding:24px;text-align:center;color:#64748b;">
									No bills registered yet.
									<br><br>
									<a href="expense_bill.php?create=1" class="ew-btn-v2 ew-btn-v2-primary"><i class="fa fa-plus"></i> Add Purchase Bill Registration</a>
								</div>
							<?php } else { ?>
									<table class="table table-bordered table-striped ebill_list_tab" id="dataTable1">
										<thead>
											<tr>
												<th>S.No</th>
												<th>Bill Entry No</th>
												<th>Bill Date</th>
												<th>Bill Type</th>
												<th>Vendor</th>
												<th>GCN</th>
												<th>Vendor Invoice No</th>
												<th>Payment</th>
												<th>Invoice Date</th>
												<th>Due Date</th>
												<th class="table-title sorting_disabled col-actions">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$i = 1;
											foreach ($list_rows as $row) {
												$bill_id = (int) $row['bill_id'];
												$edit_url = 'expense_bill.php?id=' . $bill_id;
												?>
												<tr>
													<td><?php echo $i++; ?></td>
													<td><strong><?php echo htmlspecialchars($row['bill_entry_no']); ?></strong></td>
													<td><?php echo htmlspecialchars($row['bill_date']); ?></td>
													<td><?php echo htmlspecialchars($row['bill_type_label']); ?></td>
													<td><?php echo htmlspecialchars($row['vendor_label'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['gcn_label'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['vendor_invoice_no'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['payment_mode_label'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['vendor_invoice_date'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['due_date'] ?: '—'); ?></td>
													<td class="col-actions">
														<span class="act-wrap">
															<button type="button" class="act-link act-view btn-view-bill" title="View"
																data-id="<?php echo $bill_id; ?>"
																data-no="<?php echo htmlspecialchars($row['bill_entry_no'], ENT_QUOTES, 'UTF-8'); ?>">
																<i class="fa fa-eye"></i>
															</button>
															<a class="act-link act-edit" href="<?php echo htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8'); ?>" title="Edit"><i class="fa fa-pencil"></i></a>
															<button type="button" class="act-link act-delete btn-delete-bill" title="Delete"
																data-id="<?php echo $bill_id; ?>"
																data-no="<?php echo htmlspecialchars($row['bill_entry_no'], ENT_QUOTES, 'UTF-8'); ?>">
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

	<div class="ew-v2-modal-backdrop" id="ebillViewModal">
		<div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
				<h3 id="ebillViewModalTitle">Purchase bill registration details</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body" id="ebillViewModalBody">
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
			if (window.applyEwListLayout) {
				window.applyEwListLayout();
			}

			function ebillEscHtml(str) {
				return String(str == null ? '' : str)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			function ebillRenderView(data) {
				if (!data) {
					return '<p class="text-danger">No data.</p>';
				}
				var html = '';
				html += '<div class="ebill-view-meta">';
				html += '<div><dt>Bill Entry No</dt><dd>' + ebillEscHtml(data.bill_entry_no || '—') + '</dd></div>';
				html += '<div><dt>Bill date</dt><dd>' + ebillEscHtml(data.bill_date || '—') + '</dd></div>';
				html += '<div><dt>Bill type</dt><dd>' + ebillEscHtml(data.bill_type_label || '—') + '</dd></div>';
				html += '<div><dt>Vendor</dt><dd>' + ebillEscHtml(data.vendor_label || '—') + '</dd></div>';
				html += '<div><dt>GCN</dt><dd>' + ebillEscHtml((data.gcns && data.gcns.length) ? $.map(data.gcns, function(g) { return g.label || g.gcn_no; }).join(', ') : (data.gcn_label || '—')) + '</dd></div>';
				html += '<div><dt>Vendor invoice no</dt><dd>' + ebillEscHtml(data.vendor_invoice_no || '—') + '</dd></div>';
				html += '<div><dt>Payment</dt><dd>' + ebillEscHtml(data.payment_mode_label || '—') + '</dd></div>';
				html += '<div><dt>Invoice date</dt><dd>' + ebillEscHtml(data.vendor_invoice_date || '—') + '</dd></div>';
				html += '<div><dt>Received date</dt><dd>' + ebillEscHtml(data.invoice_received_date || '—') + '</dd></div>';
				html += '<div><dt>Due date</dt><dd>' + ebillEscHtml(data.due_date || '—') + '</dd></div>';
				html += '</div>';

				if (data.narration) {
					html += '<div class="ebill-view-desc"><strong>Narration</strong><br>' + ebillEscHtml(data.narration) + '</div>';
				}

				var attachments = data.attachments || [];
				if (attachments.length) {
					html += '<div class="ebill-view-desc"><strong>Attachments</strong><ul class="ebill-attach-list">';
					$.each(attachments, function(i, att) {
						var label = att.original_name || att.stored_name || ('File ' + (i + 1));
						if (att.url) {
							html += '<li><a href="' + ebillEscHtml(att.url) + '" target="_blank" rel="noopener">' + ebillEscHtml(label) + '</a></li>';
						} else {
							html += '<li>' + ebillEscHtml(label) + '</li>';
						}
					});
					html += '</ul></div>';
				}
				return html;
			}

			$(document).on('click', '.btn-view-bill', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				if (!id) {
					return;
				}
				$('#ebillViewModalTitle').text('Purchase bill registration' + (no ? ' — ' + no : ''));
				$('#ebillViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
				if (typeof ewV2OpenModal === 'function') {
					ewV2OpenModal('ebillViewModal');
				} else {
					$('#ebillViewModal').addClass('open');
				}
				$.getJSON('expense_bill_data.php', { cmd: 'view', bill_id: id }, function(r) {
					if (!r || r.status !== 0 || !r.data) {
						$('#ebillViewModalBody').html('<p class="text-danger">' + ebillEscHtml(r && r.message ? r.message : 'Could not load details.') + '</p>');
						return;
					}
					var titleNo = r.data.bill_entry_no || no || ('#' + id);
					$('#ebillViewModalTitle').text('Purchase bill registration — ' + titleNo);
					$('#ebillViewModalBody').html(ebillRenderView(r.data));
				}).fail(function() {
					$('#ebillViewModalBody').html('<p class="text-danger">Request failed.</p>');
				});
			});

			$(document).on('click', '.btn-delete-bill', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				if (!id || !confirm('Delete purchase bill registration ' + no + '?')) return;
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.post('expense_bill_data.php', { cmd: 'delete', bill_id: id }, function(r) {
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
