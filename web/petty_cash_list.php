<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/petty_cash_helpers.php');

petty_cash_ensure_schema($conn);
expense_require_admin();

$list_rows = petty_cash_fetch_list($conn, true);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.ew-erp-list .btn-export-excel.ew-btn-v2 {
			margin-right: 0;
		}
		.ew-erp-list > .ew-table-wrap {
			padding-top: 16px !important;
		}

		#petty_cash_list_table { font-size: 13px; margin-bottom: 0; width: 100% !important; }
		#petty_cash_list_table th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
		}
		#petty_cash_list_table td {
			vertical-align: middle;
			padding: 6px 6px;
			border: none !important;
			border-bottom: 1px solid #e9ecef !important;
			background: #fff;
		}
		#petty_cash_list_table tbody tr:nth-child(even) td { background: #f9fafb; }
		#petty_cash_list_table .num { text-align: right; white-space: nowrap; }
		#petty_cash_list_table .amt-in {
			color: #15803d;
			font-weight: 700;
		}
		#petty_cash_list_table .amt-out {
			color: #dc2626;
			font-weight: 700;
		}
		#petty_cash_list_table .badge-receipt {
			background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;
		}
		#petty_cash_list_table .badge-payment {
			background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;
		}
		#petty_cash_list_table th.col-actions,
		#petty_cash_list_table td.col-actions {
			width: 120px;
			min-width: 120px;
			text-align: center;
			padding: 4px 2px !important;
		}
		#petty_cash_list_table .col-actions .act-link {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 28px;
			height: 28px;
			margin: 0 2px;
			border: none !important;
			border-radius: 4px;
			text-decoration: none !important;
			cursor: pointer;
			font-size: 14px;
			line-height: 1;
		}
		#petty_cash_list_table .col-actions .act-edit { background: #fef3c7; color: #d97706; }
		#petty_cash_list_table .col-actions .act-edit:hover { background: #fde68a; color: #b45309; }
		#petty_cash_list_table .col-actions .act-view { background: #e8edf3; color: #0A1E3D; }
		#petty_cash_list_table .col-actions .act-view:hover { background: #d5dde8; }
		#petty_cash_list_table .col-actions .act-delete { background: #fee2e2; color: #dc2626; }
		#petty_cash_list_table .col-actions .act-delete:hover { background: #fecaca; color: #b91c1c; }

		.pc-view-meta {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 12px 20px;
			margin-bottom: 18px;
		}
		.pc-view-meta dt {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin: 0 0 4px;
		}
		.pc-view-meta dd {
			margin: 0;
			font-size: 14px;
			font-weight: 600;
			color: #0f172a;
		}
		.pc-view-desc {
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
			.pc-view-meta { grid-template-columns: 1fr; }
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
				<div class="ew-page-v2 ew-page-v2--wide-table">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">Petty Cash</h1>
						</div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Petty Cash Vouchers</h2>
							<div class="ew-toolbar-right">
								<div class="ew-list-toolbar__tools"></div>
								<a href="petty_cash_list_export.php" class="ew-btn-v2 ew-btn-v2-outline btn-export-excel" id="btn_pc_export">
									<span class="ew-icon-excel" aria-hidden="true"></span> Export Excel
								</a>
								<a href="petty_cash.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix new_dept">
						<?php if (empty($list_rows)) { ?>
							<div style="padding:24px;text-align:center;color:#64748b;">
								No petty cash vouchers yet. Record <strong>GCN</strong> or <strong>General</strong> expenses from the petty cash box.
								<br><br>
								<a href="petty_cash.php?create=1" class="ew-btn-v2 ew-btn-v2-primary"><i class="fa fa-plus"></i> Add Petty Cash Entry</a>
							</div>
						<?php } else { ?>
							<table class="table table-bordered table-striped" id="petty_cash_list_table">
								<thead>
									<tr>
										<th>S.No</th>
										<th>Voucher No</th>
										<th>Date</th>
										<th>Petty cash type</th>
										<th>GCN No.</th>
										<th>Particulars</th>
										<th class="num">Amount paid</th>
										<th class="col-actions">Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$i = 1;
									foreach ($list_rows as $row) {
										$id = (int) $row['petty_cash_id'];
										$edit_url = 'petty_cash.php?no=' . rawurlencode($row['voucher_no'] ?? '');
										$type_short = !empty($row['cash_type_label']) ? $row['cash_type_label'] : 'Expense';
										$badge = 'badge-payment';
										?>
										<tr>
											<td><?php echo $i++; ?></td>
											<td><strong><?php echo htmlspecialchars($row['voucher_no']); ?></strong></td>
											<td><?php echo htmlspecialchars($row['voucher_date']); ?></td>
											<td><span class="<?php echo $badge; ?>"><?php echo htmlspecialchars($type_short); ?></span></td>
											<td title="<?php echo htmlspecialchars($row['gcn_display'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(($row['gcn_display'] ?? '') !== '' ? $row['gcn_display'] : '—'); ?></td>
											<td title="<?php echo htmlspecialchars($row['particulars'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['particulars'] ?: '—'); ?></td>
											<td class="num"><span class="amt-out">₹ <?php echo htmlspecialchars($row['amount']); ?></span></td>
											<td class="col-actions">
												<span class="act-wrap">
													<button type="button" class="act-link act-view btn-view-pc" title="View"
														data-id="<?php echo $id; ?>"
														data-no="<?php echo htmlspecialchars($row['voucher_no'], ENT_QUOTES, 'UTF-8'); ?>">
														<i class="fa fa-eye"></i>
													</button>
													<a class="act-link act-edit" href="<?php echo htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8'); ?>" title="Edit"><i class="fa fa-pencil"></i></a>
													<button type="button" class="act-link act-delete btn-delete-pc" title="Delete"
														data-id="<?php echo $id; ?>"
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

<div class="ew-v2-modal-backdrop" id="pcViewModal">
	<div class="ew-v2-modal ew-v2-modal--lg">
		<div class="ew-v2-modal-head">
			<h3 id="pcViewModalTitle">Petty cash details</h3>
			<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
		</div>
		<div class="ew-v2-modal-body" id="pcViewModalBody">
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
	if ($.fn.dataTable && $('#petty_cash_list_table').length && !$.fn.dataTable.fnIsDataTable($('#petty_cash_list_table')[0])) {
		$('#petty_cash_list_table').dataTable({
			sPaginationType: 'full_numbers',
			iDisplayLength: 25,
			aaSorting: [[2, 'desc']],
			aoColumnDefs: [
				{ bSortable: false, aTargets: [0, -1] }
			],
			bFilter: true,
			bInfo: true,
			sDom: 'lfrtip'
		});
	}
	window.setTimeout(function() {
		if (window.applyEwListLayout) {
			window.applyEwListLayout();
		}
	}, 250);

	function pcEsc(str) {
		return String(str == null ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function pcRenderView(data) {
		if (!data) return '<p class="text-danger">No data.</p>';
		var html = '';
		html += '<div class="pc-view-meta">';
		html += '<div><dt>Voucher No</dt><dd>' + pcEsc(data.voucher_no || '—') + '</dd></div>';
		html += '<div><dt>Date</dt><dd>' + pcEsc(data.voucher_date || '—') + '</dd></div>';
		html += '<div><dt>Petty cash type</dt><dd>' + pcEsc(data.cash_type_label || '—') + '</dd></div>';
		html += '<div><dt>Total</dt><dd>₹ ' + pcEsc(data.amount || '0.00') + '</dd></div>';
		html += '<div><dt>Last Updated</dt><dd>' + pcEsc(data.updated_at || '—') + '</dd></div>';
		html += '</div>';
		var gcnLabel = data.gcn_display || ((data.gcn_nos && data.gcn_nos.length) ? data.gcn_nos.join(', ') : '');
		if (gcnLabel) {
			html += '<div class="pc-view-desc"><strong>GCN No.</strong><br>' + pcEsc(gcnLabel) + '</div>';
		}
		if (data.narration) {
			html += '<div class="pc-view-desc"><strong>Narration</strong><br>' + pcEsc(data.narration) + '</div>';
		}
		if (data.lines && data.lines.length) {
			var showGcn = String(data.cash_type || '') === 'gcn_expense';
			html += '<table class="table table-bordered" style="margin-top:12px;font-size:13px;"><thead><tr><th>#</th>';
			if (showGcn) html += '<th>GCN No.</th>';
			html += '<th>Type</th><th>Paid to</th><th class="text-right">Amount</th></tr></thead><tbody>';
			data.lines.forEach(function(ln, i) {
				html += '<tr><td>' + (i + 1) + '</td>';
				if (showGcn) html += '<td>' + pcEsc(ln.gcn_no || '—') + '</td>';
				html += '<td>' + pcEsc(ln.category_name || '—') + '</td><td>' + pcEsc(ln.paid_to || '—') + '</td><td class="text-right">' + pcEsc(ln.amount) + '</td></tr>';
			});
			html += '</tbody></table>';
		}
		return html;
	}

	$(document).on('click', '.btn-view-pc', function() {
		var id = $(this).data('id');
		var no = $(this).data('no') || '';
		if (!id) return;
		$('#pcViewModalTitle').text('Petty cash' + (no ? ' — ' + no : ''));
		$('#pcViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
		if (typeof ewV2OpenModal === 'function') {
			ewV2OpenModal('pcViewModal');
		} else {
			$('#pcViewModal').addClass('open');
		}
		$.getJSON('petty_cash_data.php', { cmd: 'view', petty_cash_id: id }, function(r) {
			if (!r || r.status !== 0 || !r.data) {
				$('#pcViewModalBody').html('<p class="text-danger">' + pcEsc(r && r.message ? r.message : 'Could not load details.') + '</p>');
				return;
			}
			$('#pcViewModalTitle').text('Petty cash — ' + (r.data.voucher_no || no || ('#' + id)));
			$('#pcViewModalBody').html(pcRenderView(r.data));
		}).fail(function() {
			$('#pcViewModalBody').html('<p class="text-danger">Request failed.</p>');
		});
	});

	$(document).on('click', '.btn-delete-pc', function() {
		var id = $(this).data('id');
		var no = $(this).data('no') || '';
		if (!id || !confirm('Delete petty cash voucher ' + no + '?')) return;
		var $btn = $(this);
		$btn.prop('disabled', true);
		$.post('petty_cash_data.php', { cmd: 'delete', petty_cash_id: id }, function(r) {
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
