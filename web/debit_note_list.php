<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_note_functions.php');

ensure_billing_tables($conn);
ensure_billing_note_tables($conn);
require_once('include/expense_bill_helpers.php');
expense_bill_ensure_schema($conn);

$list_q = mysqli_query($conn, "SELECT n.*,
    COALESCE(NULLIF(b.vendor_invoice_no,''), b.bill_entry_no) AS bill_ref,
    b.bill_entry_no,
    TRIM(CONCAT(COALESCE(v.vendor_code,''), ' - ', COALESCE(v.vendor_name,''))) AS vendor_label
    FROM billing_note_master n
    LEFT JOIN vendor_bill b ON b.bill_id = n.against_invoice_id
    LEFT JOIN vendor_master v ON v.vendor_id = n.party_id
    WHERE n.note_type='DN' AND n.party_type='vendor'
    ORDER BY n.billing_note_id DESC
    LIMIT 500");

$sum_gst_claim = 0.0;
$sum_gst_claim_final = 0.0;
$sum_gst_q = mysqli_query($conn, "SELECT
    COALESCE(SUM(gst_amount),0) AS gst_all,
    COALESCE(SUM(CASE WHEN status='final' THEN gst_amount ELSE 0 END),0) AS gst_final
    FROM billing_note_master
    WHERE note_type='DN' AND party_type='vendor'");
if ($sum_gst_q && ($sum_row = mysqli_fetch_assoc($sum_gst_q))) {
	$sum_gst_claim = (float) $sum_row['gst_all'];
	$sum_gst_claim_final = (float) $sum_row['gst_final'];
}
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.badge-draft { background: #f59e0b; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.badge-final { background: #16a34a; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.badge-cancelled { background: #94a3b8; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.cn-view-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 16px; margin-bottom: 16px; font-size: 13px; }
		.cn-view-meta dt { font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin: 0 0 4px; }
		.cn-view-meta dd { margin: 0 0 10px; color: #021659; font-weight: 700; line-height: 1.35; }
		.cn-view-kpi { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 12px; }
		.cn-view-kpi .kpi { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
		.cn-view-kpi .kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
		.cn-view-kpi .kpi-value { font-size: 16px; font-weight: 700; color: #0A1E3D; }
		.table-scroll-wrapper {
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
			border: none;
			border-radius: 0;
			padding: 0;
			margin: 0;
		}
		.ew-card.ew-erp-list > .ew-table-wrap > p {
			padding: 12px 24px 0;
			margin: 0 0 8px !important;
		}
		#debit_note_list_table { font-size: 13px; margin-bottom: 0; border-collapse: collapse; width: 100% !important; }
		#debit_note_list_table th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
			vertical-align: middle;
		}
		#debit_note_list_table td {
			vertical-align: middle;
			padding: 6px 6px;
			border: none !important;
			border-bottom: 1px solid #e9ecef !important;
			background: #fff;
		}
		#debit_note_list_table tbody tr:nth-child(even) td { background: #f9fafb; }
		#debit_note_list_table .num { text-align: right; white-space: nowrap; }
		#debit_note_list_table th.col-actions,
		#debit_note_list_table td.col-actions {
			width: 120px;
			min-width: 120px;
			text-align: center;
			padding: 4px 2px !important;
		}
		#debit_note_list_table .col-actions .act-link {
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
		#debit_note_list_table .col-actions .act-link .fa {
			font-size: 14px;
			line-height: 1;
		}
		#debit_note_list_table .col-actions .act-edit { background: #fef3c7; color: #d97706; }
		#debit_note_list_table .col-actions .act-edit:hover { background: #fde68a; color: #b45309; }
		#debit_note_list_table .col-actions .act-view { background: #e8edf3; color: #0A1E3D; }
		#debit_note_list_table .col-actions .act-view:hover { background: #d5dde8; }
		#debit_note_list_table .col-actions .act-delete { background: #fee2e2; color: #dc2626; }
		#debit_note_list_table .col-actions .act-delete:hover { background: #fecaca; color: #b91c1c; }
		#debit_note_list_table tr.row-cancelled td { color: #64748b; }
		table.dataTable#debit_note_list_table thead th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
		}
		table.dataTable#debit_note_list_table thead th.sorting,
		table.dataTable#debit_note_list_table thead th.sorting_asc,
		table.dataTable#debit_note_list_table thead th.sorting_desc {
			background-image: none !important;
			padding-right: 6px !important;
		}
		.dataTables_wrapper .dataTables_length,
		.dataTables_wrapper .dataTables_filter { margin-bottom: 12px; }
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
							<h1 class="ew-page-title">Debit Note</h1>
						</div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Debit Note List</h2>
							<div class="ew-toolbar-right">
								<div class="ew-list-toolbar__tools"></div>
								<a href="create_debit_note.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
						<p style="margin:0 0 12px;font-size:13px;color:#475569;">
							<strong>GST claimed (all notes):</strong> <?php echo number_format($sum_gst_claim, 2); ?>
							&nbsp;·&nbsp;
							<strong>GST claimed (final only):</strong> <?php echo number_format($sum_gst_claim_final, 2); ?>
						</p>
						<div class="table-scroll-wrapper">
						<table class="table" id="debit_note_list_table">
							<thead>
								<tr>
									<th>S.No</th>
									<th>Note No</th>
									<th>Date</th>
									<th>Against Bill</th>
									<th>Vendor</th>
									<th>Reason</th>
									<th>Status</th>
									<th class="num">Debit Note Amount</th>
									<th class="num">GST Claim</th>
									<th class="num">Total</th>
									<th class="col-actions">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$i = 1;
								if ($list_q) {
									while ($row = mysqli_fetch_assoc($list_q)) {
										$st = $row['status'] ?? 'draft';
										if ($st === 'final') {
											$status_badge = '<span class="badge-final">Final</span>';
										} elseif ($st === 'cancelled') {
											$status_badge = '<span class="badge-cancelled">Cancelled</span>';
										} else {
											$status_badge = '<span class="badge-draft">Draft</span>';
										}
										$note_no = $row['note_no'] ?: ('DRAFT-' . $row['billing_note_id']);
										$nid = (int) $row['billing_note_id'];
										echo '<tr' . ($st === 'cancelled' ? ' class="row-cancelled"' : '') . '>';
										echo '<td>' . $i++ . '</td>';
										echo '<td>' . htmlspecialchars($note_no) . '</td>';
										echo '<td>' . htmlspecialchars($row['note_date']) . '</td>';
										echo '<td>' . htmlspecialchars($row['bill_ref'] ?: '—') . '</td>';
										echo '<td>' . htmlspecialchars($row['vendor_label'] ?: '—') . '</td>';
										echo '<td>' . htmlspecialchars($row['reason'] ?: '—') . '</td>';
										echo '<td>' . $status_badge . '</td>';
										echo '<td class="num">' . number_format((float) $row['taxable_value'], 2) . '</td>';
										echo '<td class="num">' . number_format((float) $row['gst_amount'], 2) . '</td>';
										echo '<td class="num">' . number_format((float) $row['grand_total'], 2) . '</td>';
										echo '<td class="col-actions">';
										echo '<button type="button" class="act-link act-view btn-view-debit-note" title="Quick view" data-id="' . $nid . '" data-no="' . htmlspecialchars($note_no, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-eye"></i></button>';
										if ($st === 'cancelled') {
											/* view only */
										} else {
											echo '<a class="act-link act-edit" href="create_debit_note.php?id=' . $nid . '" title="' . ($st === 'final' ? 'View debit note' : 'Edit debit note') . '"><i class="fa fa-pencil"></i></a>';
											$del_title = ($st === 'final') ? 'Delete (cancel debit note)' : 'Delete draft';
											echo '<button type="button" class="act-link act-delete btn-delete-debit-note" title="' . htmlspecialchars($del_title, ENT_QUOTES, 'UTF-8') . '" data-id="' . $nid . '" data-no="' . htmlspecialchars($note_no, ENT_QUOTES, 'UTF-8') . '" data-status="' . htmlspecialchars($st, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-trash-o"></i></button>';
										}
										echo '</td></tr>';
									}
								}
								?>
							</tbody>
						</table>
						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>

	<div class="ew-v2-modal-backdrop" id="debitNoteViewModal">
		<div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
				<h3 id="debitNoteViewModalTitle">Debit note details</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body" id="debitNoteViewModalBody">
				<p class="text-muted" style="margin:0;">Loading…</p>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
				<a href="#" class="btn btn-primary" id="btn_dn_open_full" style="display:none;">Open full page</a>
			</div>
		</div>
	</div>
</div>
<script>
function dnEscHtml(str) {
	return String(str == null ? '' : str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

function dnRenderView(data) {
	if (!data) {
		return '<p class="text-danger">No data.</p>';
	}
	var html = '';
	html += '<div class="cn-view-meta">';
	html += '<div><dt>Note no</dt><dd>' + dnEscHtml(data.note_no) + '</dd></div>';
	html += '<div><dt>Date</dt><dd>' + dnEscHtml(data.note_date) + '</dd></div>';
	html += '<div><dt>Status</dt><dd>' + dnEscHtml(data.status_label) + '</dd></div>';
	html += '<div><dt>Against bill</dt><dd>' + dnEscHtml(data.against_bill_no) + '</dd></div>';
	html += '<div><dt>Vendor</dt><dd>' + dnEscHtml(data.vendor_name) + '</dd></div>';
	html += '<div><dt>Reason</dt><dd>' + dnEscHtml(data.reason || '—') + '</dd></div>';
	html += '<div><dt>GST (bill)</dt><dd>' + dnEscHtml(data.gst_label || '—') + '</dd></div>';
	html += '<div><dt>Linked GCN</dt><dd>' + dnEscHtml(data.gcn_label || '—') + '</dd></div>';
	if (data.line_description) {
		html += '<div style="grid-column:1/-1;"><dt>Description</dt><dd>' + dnEscHtml(data.line_description) + '</dd></div>';
	}
	html += '</div>';
	html += '<div class="cn-view-kpi">';
	html += '<div class="kpi"><div class="kpi-label">Debit note amount</div><div class="kpi-value">' + dnEscHtml(data.taxable_value) + '</div></div>';
	html += '<div class="kpi"><div class="kpi-label">GST claim</div><div class="kpi-value">' + dnEscHtml(data.gst_amount) + '</div></div>';
	html += '<div class="kpi"><div class="kpi-label">Total</div><div class="kpi-value">' + dnEscHtml(data.grand_total) + '</div></div>';
	html += '</div>';
	if (data.total_words) {
		html += '<p style="margin:0;font-size:13px;color:#475569;"><strong>In words:</strong> ' + dnEscHtml(data.total_words) + '</p>';
	}
	return html;
}

$(function() {
	if ($.fn.dataTable && $('#debit_note_list_table').length && !$.fn.dataTable.fnIsDataTable($('#debit_note_list_table')[0])) {
		$('#debit_note_list_table').dataTable({
			sPaginationType: 'full_numbers',
			iDisplayLength: 25,
			aaSorting: [[0, 'asc']],
			aoColumnDefs: [
				{ bSortable: false, aTargets: [0, -1] }
			]
		});
	}
	window.setTimeout(function() {
		if (window.applyEwListLayout) {
			window.applyEwListLayout();
		}
	}, 250);

	$(document).on('click', '.btn-view-debit-note', function() {
		var id = parseInt($(this).data('id'), 10);
		var no = $(this).data('no') || '';
		if (!id) {
			return;
		}
		$('#debitNoteViewModalTitle').text('Debit note — ' + (no || ('#' + id)));
		$('#debitNoteViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
		$('#btn_dn_open_full').hide().attr('href', '#');
		if (typeof ewV2OpenModal === 'function') {
			ewV2OpenModal('debitNoteViewModal');
		} else {
			$('#debitNoteViewModal').addClass('open');
		}
		$.getJSON('create_debit_note_data.php', { cmd: 'view', billing_note_id: id }, function(r) {
			if (!r || r.status !== 0 || !r.data) {
				$('#debitNoteViewModalBody').html('<p class="text-danger">' + dnEscHtml(r && r.message ? r.message : 'Could not load details.') + '</p>');
				return;
			}
			var titleNo = r.data.note_no || no || ('#' + id);
			$('#debitNoteViewModalTitle').text('Debit note — ' + titleNo);
			$('#debitNoteViewModalBody').html(dnRenderView(r.data));
			$('#btn_dn_open_full').attr('href', 'create_debit_note.php?id=' + encodeURIComponent(id)).show();
		}).fail(function() {
			$('#debitNoteViewModalBody').html('<p class="text-danger">Request failed.</p>');
		});
	});

	function dnPerformDelete(noteId) {
		noteId = parseInt(noteId, 10);
		if (!noteId) {
			return;
		}
		$.post('create_debit_note_data.php', {
			cmd: 'delete_debit_note',
			billing_note_id: noteId
		}, function(r) {
			if (!r || r.status !== 0) {
				if (typeof ewFormToast === 'function') {
					ewFormToast(r && r.message ? r.message : 'Action failed.', 'error', 5000);
				}
				return;
			}
			if (typeof ewFormToast === 'function') {
				ewFormToast(r.message, 'success', 4000);
			}
			window.location.reload();
		}, 'json').fail(function() {
			if (typeof ewFormToast === 'function') {
				ewFormToast('Network error.', 'error', 5000);
			}
		});
	}

	$(document).on('click', '.btn-delete-debit-note', function() {
		var id = parseInt($(this).data('id'), 10);
		var no = $(this).data('no') || ('#' + id);
		var st = $(this).data('status') || '';
		if (!id) {
			return;
		}
		var title = (st === 'final') ? 'Cancel debit note' : 'Delete draft debit note';
		var message = (st === 'final')
			? ('Cancel debit note ' + no + '? This releases the amount from the linked vendor bill. This cannot be undone.')
			: ('Delete draft debit note ' + no + '? This cannot be undone.');
		if (typeof window.ewConfirmDelete === 'function' && $('#ewDeleteModal').length) {
			window.ewConfirmDelete({
				id: String(id),
				title: title,
				message: message,
				onConfirm: function(delId) {
					dnPerformDelete(delId);
				}
			});
			return;
		}
		if (typeof window.ewConfirm === 'function' && $('#ewConfirmModal').length) {
			window.ewConfirm({
				title: title,
				message: message,
				confirmText: (st === 'final') ? 'Cancel debit note' : 'Delete',
				onConfirm: function() {
					dnPerformDelete(id);
				}
			});
		}
	});
});
</script>
</body>
</html>
