<?php
require_once('include/connect.php');
if (!defined('EW_PURCHASE_VOUCHER_LIVE') || !EW_PURCHASE_VOUCHER_LIVE) {
	header('Location: expense_gcn_list.php');
	exit;
}
require_once('include/function.php');
require_once('include/purchase_voucher_helpers.php');

purchase_voucher_ensure_schema($conn);
expense_require_admin();

$list_rows = purchase_voucher_fetch_list($conn);
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

		.pv-view-meta {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 12px 20px;
			margin-bottom: 18px;
		}

		.pv-view-meta dt {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin: 0 0 4px;
		}

		.pv-view-meta dd {
			margin: 0;
			font-size: 14px;
			font-weight: 600;
			color: #0f172a;
		}

		.pv-view-lines {
			width: 100%;
			border-collapse: collapse;
			margin: 0 0 16px;
			font-size: 12px;
		}

		.pv-view-lines th,
		.pv-view-lines td {
			padding: 8px 10px;
			border: 1px solid #e2e8f0;
			vertical-align: top;
		}

		.pv-view-lines th {
			background: #f1f5f9;
			font-weight: 700;
		}

		.pv-view-lines td.num {
			text-align: right;
			white-space: nowrap;
		}

		.pv-attach-grid-view {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
		}

		.pv-attach-grid-view a {
			display: block;
			max-width: 120px;
			font-size: 12px;
			word-break: break-word;
		}

		.pv-attach-grid-view img {
			max-width: 100px;
			max-height: 80px;
			border-radius: 6px;
			border: 1px solid #e2e8f0;
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
								<h1 class="ew-page-title">Purchase Voucher</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>Purchase Voucher List</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<a href="purchase_voucher.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<?php if (empty($list_rows)) { ?>
								<div style="padding:24px;text-align:center;color:#64748b;">
									No purchase vouchers yet.
									<br><br>
									<a href="purchase_voucher.php?create=1" class="ew-btn-v2 ew-btn-v2-primary"><i class="fa fa-plus"></i> Add Purchase Voucher</a>
								</div>
							<?php } else { ?>
									<table class="table table-bordered table-striped" id="dataTable1">
										<thead>
											<tr>
												<th>S.No</th>
												<th>Voucher No</th>
												<th>Voucher Date</th>
												<th>Vendor</th>
												<th>Supplier Invoice</th>
												<th class="num">Net Payable</th>
												<th>Payment</th>
												<th class="table-title sorting_disabled col-actions">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$i = 1;
											foreach ($list_rows as $row) {
												$pv_id = (int) $row['purchase_voucher_id'];
												$edit_url = 'purchase_voucher.php?id=' . $pv_id;
												?>
												<tr>
													<td><?php echo $i++; ?></td>
													<td><strong><?php echo htmlspecialchars($row['voucher_no']); ?></strong></td>
													<td><?php echo htmlspecialchars($row['voucher_date']); ?></td>
													<td><?php echo htmlspecialchars($row['vendor_label'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['supplier_invoice_no'] ?: '—'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['net_payable']); ?></td>
													<td><?php echo htmlspecialchars($row['payment_mode_label'] ?: '—'); ?></td>
													<td class="col-actions">
														<span class="act-wrap">
															<button type="button" class="act-link act-view btn-view-pv" title="View"
																data-id="<?php echo $pv_id; ?>"
																data-no="<?php echo htmlspecialchars($row['voucher_no'], ENT_QUOTES, 'UTF-8'); ?>">
																<i class="fa fa-eye"></i>
															</button>
															<a class="act-link act-edit" href="<?php echo htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8'); ?>" title="Edit"><i class="fa fa-pencil"></i></a>
															<button type="button" class="act-link act-delete btn-delete-pv" title="Delete"
																data-id="<?php echo $pv_id; ?>"
																data-no="<?php echo htmlspecialchars($row['voucher_no'], ENT_QUOTES, 'UTF-8'); ?>">
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

	<div class="ew-v2-modal-backdrop" id="pvViewModal">
		<div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
				<h3 id="pvViewModalTitle">Purchase voucher</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body" id="pvViewModalBody">
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

			function pvEsc(str) {
				return String(str == null ? '' : str)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			function pvRenderView(data) {
				if (!data) return '<p class="text-danger">No data.</p>';
				var html = '';
				html += '<div class="pv-view-meta">';
				html += '<div><dt>Voucher no</dt><dd>' + pvEsc(data.voucher_no || '—') + '</dd></div>';
				html += '<div><dt>Voucher date</dt><dd>' + pvEsc(data.voucher_date || '—') + '</dd></div>';
				html += '<div><dt>Vendor</dt><dd>' + pvEsc(data.vendor_label || '—') + '</dd></div>';
				html += '<div><dt>Supplier invoice</dt><dd>' + pvEsc(data.supplier_invoice_no || '—') + '</dd></div>';
				html += '<div><dt>Invoice date</dt><dd>' + pvEsc(data.supplier_invoice_date || '—') + '</dd></div>';
				html += '<div><dt>Received date</dt><dd>' + pvEsc(data.invoice_received_date || '—') + '</dd></div>';
				html += '<div><dt>Payment</dt><dd>' + pvEsc(data.payment_mode_label || '—') + '</dd></div>';
				html += '<div><dt>Net payable</dt><dd>' + pvEsc(data.net_payable || '0.00') + '</dd></div>';
				html += '</div>';
				var lines = data.lines || [];
				if (lines.length) {
					html += '<table class="pv-view-lines"><thead><tr>';
					html += '<th>Item</th><th>Expense type</th><th class="num">Taxable</th><th class="num">GST</th><th class="num">Total</th></tr></thead><tbody>';
					$.each(lines, function(i, ln) {
						html += '<tr><td>' + pvEsc(ln.item_description) + '</td><td>' + pvEsc(ln.category_label || '—') + '</td>';
						html += '<td class="num">' + pvEsc(ln.taxable_amount_fmt) + '</td>';
						html += '<td class="num">' + pvEsc(ln.gst_amount_fmt) + '</td>';
						html += '<td class="num">' + pvEsc(ln.line_total_fmt) + '</td></tr>';
					});
					html += '</tbody></table>';
				}
				if (data.narration) {
					html += '<div class="egen-view-desc"><strong>Narration</strong><br>' + pvEsc(data.narration) + '</div>';
				}
				var attachments = data.attachments || [];
				if (attachments.length) {
					html += '<p><strong>Attachments</strong></p><div class="pv-attach-grid-view">';
					$.each(attachments, function(i, att) {
						var label = att.original_name || 'File';
						if (att.url && att.is_image) {
							html += '<a href="' + pvEsc(att.url) + '" target="_blank" rel="noopener"><img src="' + pvEsc(att.url) + '" alt=""><br>' + pvEsc(label) + '</a>';
						} else if (att.url) {
							html += '<a href="' + pvEsc(att.url) + '" target="_blank" rel="noopener">' + pvEsc(label) + '</a>';
						} else {
							html += '<span>' + pvEsc(label) + '</span>';
						}
					});
					html += '</div>';
				}
				return html;
			}

			$(document).on('click', '.btn-view-pv', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				$('#pvViewModalTitle').text('Purchase voucher' + (no ? ' — ' + no : ''));
				$('#pvViewModalBody').html('<p class="text-muted">Loading…</p>');
				if (typeof ewV2OpenModal === 'function') {
					ewV2OpenModal('pvViewModal');
				} else {
					$('#pvViewModal').addClass('open');
				}
				$.getJSON('purchase_voucher_data.php', { cmd: 'view', purchase_voucher_id: id }, function(r) {
					if (!r || r.status !== 0 || !r.data) {
						$('#pvViewModalBody').html('<p class="text-danger">' + pvEsc(r && r.message ? r.message : 'Could not load.') + '</p>');
						return;
					}
					$('#pvViewModalTitle').text('Purchase voucher — ' + (r.data.voucher_no || no));
					$('#pvViewModalBody').html(pvRenderView(r.data));
				}).fail(function() {
					$('#pvViewModalBody').html('<p class="text-danger">Request failed.</p>');
				});
			});

			$(document).on('click', '.btn-delete-pv', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				if (!id || !confirm('Delete purchase voucher ' + no + '?')) return;
				$.post('purchase_voucher_data.php', { cmd: 'delete', purchase_voucher_id: id }, function(r) {
					if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Delete failed.');
						return;
					}
					window.location.reload();
				}, 'json').fail(function() {
					alert('Delete request failed.');
				});
			});
		});
	</script>
	<?php } ?>
</body>

</html>