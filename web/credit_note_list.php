<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_note_functions.php');

ensure_billing_tables($conn);
ensure_billing_note_tables($conn);

$list_q = mysqli_query($conn, "SELECT n.*, m.invoice_no, c.client_company_name,
    n.client_email_sent_at, n.client_email_sent_to
    FROM billing_note_master n
    LEFT JOIN billing_invoice_master m ON m.billing_invoice_id = n.against_invoice_id
    LEFT JOIN client c ON c.client_id = n.party_id
    WHERE n.note_type='CN' AND n.party_type='client'
    ORDER BY n.billing_note_id DESC
    LIMIT 500");

$sum_gst_claim = 0.0;
$sum_gst_claim_final = 0.0;
$sum_gst_q = mysqli_query($conn, "SELECT
    COALESCE(SUM(gst_amount),0) AS gst_all,
    COALESCE(SUM(CASE WHEN status='final' THEN gst_amount ELSE 0 END),0) AS gst_final
    FROM billing_note_master
    WHERE note_type='CN' AND party_type='client'");
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
		.cn-view-kpi .kpi-value.kpi-value-split { font-size: 13px; line-height: 1.4; font-weight: 700; }
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
		#credit_note_list_table { font-size: 13px; margin-bottom: 0; border-collapse: collapse; width: 100% !important; }
		#credit_note_list_table th {
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
		#credit_note_list_table td {
			vertical-align: middle;
			padding: 6px 6px;
			border: none !important;
			border-bottom: 1px solid #e9ecef !important;
			background: #fff;
		}
		#credit_note_list_table tbody tr:nth-child(even) td { background: #f9fafb; }
		#credit_note_list_table .num { text-align: right; white-space: nowrap; }
		#credit_note_list_table th.col-actions,
		#credit_note_list_table td.col-actions {
			width: 152px;
			min-width: 152px;
			text-align: center;
			padding: 4px 2px !important;
		}
		#credit_note_list_table .col-actions .act-wrap {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			flex-wrap: nowrap;
			gap: 2px;
		}
		#credit_note_list_table .col-actions .act-link {
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
		#credit_note_list_table .col-actions .act-link .fa {
			font-size: 14px;
			line-height: 1;
		}
		#credit_note_list_table .col-actions .act-edit { background: #fef3c7; color: #d97706; }
		#credit_note_list_table .col-actions .act-edit:hover { background: #fde68a; color: #b45309; }
		#credit_note_list_table .col-actions .act-view { background: #e8edf3; color: #0A1E3D; }
		#credit_note_list_table .col-actions .act-view:hover { background: #d5dde8; }
		#credit_note_list_table .col-actions .act-delete { background: #fee2e2; color: #dc2626; }
		#credit_note_list_table .col-actions .act-delete:hover { background: #fecaca; color: #b91c1c; }
		#credit_note_list_table .col-actions .act-pdf { background: #dcfce7; color: #16a34a; }
		#credit_note_list_table .col-actions .act-pdf:hover { background: #bbf7d0; color: #15803d; }
		#credit_note_list_table .col-actions .act-mail { background: #dbeafe; color: #2563eb; }
		#credit_note_list_table .col-actions .act-mail:hover { background: #bfdbfe; color: #1d4ed8; }
		#credit_note_list_table .col-actions .act-mail.is-sent { box-shadow: inset 0 0 0 1px #93c5fd; }
		#credit_note_list_table tr.row-cancelled td { color: #64748b; }
		table.dataTable#credit_note_list_table thead th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
		}
		table.dataTable#credit_note_list_table thead th.sorting,
		table.dataTable#credit_note_list_table thead th.sorting_asc,
		table.dataTable#credit_note_list_table thead th.sorting_desc {
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
							<h1 class="ew-page-title">Credit Note</h1>
						</div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Credit Note List</h2>
							<div class="ew-toolbar-right">
								<div class="ew-list-toolbar__tools"></div>
								<a href="create_credit_note.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
						<p style="margin:0 0 12px;font-size:13px;color:#475569;">
							<strong>GST claimed (all notes):</strong> <?php echo number_format($sum_gst_claim, 2); ?>
							&nbsp;·&nbsp;
							<strong>GST claimed (final only):</strong> <?php echo number_format($sum_gst_claim_final, 2); ?>
						</p>
						<div class="table-scroll-wrapper">
						<table class="table" id="credit_note_list_table">
							<thead>
								<tr>
									<th>S.No</th>
									<th>Note No</th>
									<th>Date</th>
									<th>Against Invoice</th>
									<th>Customer</th>
									<th>Reason</th>
									<th>Status</th>
									<th class="num">Credit Note Amount</th>
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
										echo '<td>' . htmlspecialchars($row['invoice_no'] ?: '—') . '</td>';
										echo '<td>' . htmlspecialchars($row['client_company_name'] ?: '—') . '</td>';
										echo '<td>' . htmlspecialchars($row['reason'] ?: '—') . '</td>';
										echo '<td>' . $status_badge . '</td>';
										echo '<td class="num">' . number_format((float) $row['taxable_value'], 2) . '</td>';
										echo '<td class="num">' . number_format((float) $row['gst_amount'], 2) . '</td>';
										echo '<td class="num">' . number_format((float) $row['grand_total'], 2) . '</td>';
										echo '<td class="col-actions"><span class="act-wrap">';
										echo '<button type="button" class="act-link act-view btn-view-credit-note" title="Quick view" data-id="' . $nid . '" data-no="' . htmlspecialchars($note_no, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-eye"></i></button>';
										echo '<a class="act-link act-pdf" href="credit_note_pdf.php?id=' . $nid . '" target="_blank" rel="noopener" title="PDF"><i class="fa fa-file-text-o"></i></a>';
										if ($st === 'final') {
											$mail_title = 'Email credit note to customer';
											if (!empty($row['client_email_sent_at'])) {
												$mail_title = 'Resend email to customer (last: ' . $row['client_email_sent_at'] . ')';
											}
											$mail_cls = !empty($row['client_email_sent_at']) ? ' act-mail is-sent' : ' act-mail';
											echo '<button type="button" class="act-link' . $mail_cls . ' btn-send-credit-note-mail" title="' . htmlspecialchars($mail_title, ENT_QUOTES, 'UTF-8') . '" data-id="' . $nid . '" data-no="' . htmlspecialchars($note_no, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-envelope"></i></button>';
										}
										if ($st !== 'cancelled') {
											echo '<a class="act-link act-edit" href="create_credit_note.php?id=' . $nid . '" title="' . ($st === 'final' ? 'View credit note' : 'Edit credit note') . '"><i class="fa fa-pencil"></i></a>';
											$del_title = ($st === 'final') ? 'Delete (cancel credit note)' : 'Delete draft';
											echo '<button type="button" class="act-link act-delete btn-delete-credit-note" title="' . htmlspecialchars($del_title, ENT_QUOTES, 'UTF-8') . '" data-id="' . $nid . '" data-no="' . htmlspecialchars($note_no, ENT_QUOTES, 'UTF-8') . '" data-status="' . htmlspecialchars($st, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-trash-o"></i></button>';
										}
										echo '</span></td></tr>';
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

	<div class="ew-v2-modal-backdrop" id="creditNoteViewModal">
		<div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
				<h3 id="creditNoteViewModalTitle">Credit note details</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body" id="creditNoteViewModalBody">
				<p class="text-muted" style="margin:0;">Loading…</p>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
				<a href="#" class="btn btn-default-outline" id="btn_cn_view_pdf" style="display:none;" target="_blank" rel="noopener"><i class="fa fa-file-text-o"></i> PDF</a>
				<a href="#" class="btn btn-primary" id="btn_cn_open_full" style="display:none;">Open full page</a>
				<button type="button" class="btn btn-primary" id="btn_cn_send_mail" style="display:none;"><i class="fa fa-envelope"></i> Email customer</button>
			</div>
		</div>
	</div>
</div>
<script>
function cnEscHtml(str) {
	return String(str == null ? '' : str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

function cnRenderView(data) {
	if (!data) {
		return '<p class="text-danger">No data.</p>';
	}
	var html = '';
	html += '<div class="cn-view-meta">';
	html += '<div><dt>Note no</dt><dd>' + cnEscHtml(data.note_no) + '</dd></div>';
	html += '<div><dt>Date</dt><dd>' + cnEscHtml(data.note_date) + '</dd></div>';
	html += '<div><dt>Status</dt><dd>' + cnEscHtml(data.status_label) + '</dd></div>';
	html += '<div><dt>Against invoice</dt><dd>' + cnEscHtml(data.against_invoice_no) + '</dd></div>';
	html += '<div><dt>Customer</dt><dd>' + cnEscHtml(data.customer_name) + '</dd></div>';
	html += '<div><dt>Reason</dt><dd>' + cnEscHtml(data.reason || '—') + '</dd></div>';
	html += '<div><dt>GST (invoice)</dt><dd>' + cnEscHtml(data.gst_label || '—') + '</dd></div>';
	html += '<div><dt>Linked GCN</dt><dd>' + cnEscHtml(data.gcn_label || '—') + '</dd></div>';
	if (data.line_description) {
		html += '<div style="grid-column:1/-1;"><dt>Description</dt><dd>' + cnEscHtml(data.line_description) + '</dd></div>';
	}
	html += '</div>';
	html += '<div class="cn-view-kpi">';
	html += '<div class="kpi"><div class="kpi-label">Credit note amount</div><div class="kpi-value">' + cnEscHtml(data.taxable_value) + '</div></div>';
	html += '<div class="kpi"><div class="kpi-label">Reverse GST claim</div><div class="kpi-value kpi-value-split">' + cnEscHtml(data.reverse_gst_claim || data.gst_amount) + '</div></div>';
	html += '<div class="kpi"><div class="kpi-label">Total</div><div class="kpi-value">' + cnEscHtml(data.grand_total) + '</div></div>';
	html += '</div>';
	if (data.total_words) {
		html += '<p style="margin:0;font-size:13px;color:#475569;"><strong>In words:</strong> ' + cnEscHtml(data.total_words) + '</p>';
	}
	return html;
}

function cnSendMail(noteId, noteNo, $btn) {
	noteId = parseInt(noteId, 10);
	if (!noteId) {
		return;
	}
	var title = noteNo ? ('Email credit note ' + noteNo + ' to customer?') : 'Email credit note to customer?';
	if (typeof window.ewConfirm === 'function' && $('#ewConfirmModal').length) {
		window.ewConfirm({
			title: 'Send credit note email',
			message: title + ' The PDF will be sent from athar@elitewave360.in with info@elitewave360.in in CC.',
			confirmText: 'Send email',
			onConfirm: function() { cnSendMailDo(noteId, $btn); }
		});
		return;
	}
	if (window.confirm(title)) {
		cnSendMailDo(noteId, $btn);
	}
}

function cnSendMailDo(noteId, $btn) {
	if ($btn && $btn.length) {
		$btn.prop('disabled', true);
	}
	$.post('create_credit_note_data.php', {
		cmd: 'send_client_email',
		billing_note_id: noteId
	}, function(r) {
		if ($btn && $btn.length) {
			$btn.prop('disabled', false);
		}
		if (!r || r.status !== 0) {
			if (typeof ewFormToast === 'function') {
				ewFormToast(r && r.message ? r.message : 'Email failed.', 'error', 6000);
			}
			return;
		}
		if (typeof ewFormToast === 'function') {
			ewFormToast(r.message, 'success', 5000);
		}
		window.setTimeout(function() { window.location.reload(); }, 800);
	}, 'json').fail(function() {
		if ($btn && $btn.length) {
			$btn.prop('disabled', false);
		}
		if (typeof ewFormToast === 'function') {
			ewFormToast('Network error.', 'error', 5000);
		}
	});
}

var cnViewNoteId = 0;

$(function() {
	if ($.fn.dataTable && $('#credit_note_list_table').length && !$.fn.dataTable.fnIsDataTable($('#credit_note_list_table')[0])) {
		$('#credit_note_list_table').dataTable({
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

	$(document).on('click', '.btn-view-credit-note', function() {
		var id = parseInt($(this).data('id'), 10);
		var no = $(this).data('no') || '';
		if (!id) {
			return;
		}
		$('#creditNoteViewModalTitle').text('Credit note — ' + (no || ('#' + id)));
		$('#creditNoteViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
		cnViewNoteId = id;
		$('#btn_cn_open_full, #btn_cn_view_pdf, #btn_cn_send_mail').hide().attr('href', '#');
		if (typeof ewV2OpenModal === 'function') {
			ewV2OpenModal('creditNoteViewModal');
		} else {
			$('#creditNoteViewModal').addClass('open');
		}
		$.getJSON('create_credit_note_data.php', { cmd: 'view', billing_note_id: id }, function(r) {
			if (!r || r.status !== 0 || !r.data) {
				$('#creditNoteViewModalBody').html('<p class="text-danger">' + cnEscHtml(r && r.message ? r.message : 'Could not load details.') + '</p>');
				return;
			}
			var titleNo = r.data.note_no || no || ('#' + id);
			$('#creditNoteViewModalTitle').text('Credit note — ' + titleNo);
			$('#creditNoteViewModalBody').html(cnRenderView(r.data));
			$('#btn_cn_open_full').attr('href', 'create_credit_note.php?id=' + encodeURIComponent(id)).show();
			$('#btn_cn_view_pdf').attr('href', 'credit_note_pdf.php?id=' + encodeURIComponent(id)).show();
			if ((r.data.status || '') === 'final') {
				$('#btn_cn_send_mail').show().data('id', id).data('no', titleNo);
				if (r.data.mail_sent_at) {
					$('#btn_cn_send_mail').html('<i class="fa fa-envelope"></i> Resend email');
				} else {
					$('#btn_cn_send_mail').html('<i class="fa fa-envelope"></i> Email customer');
				}
			}
		}).fail(function() {
			$('#creditNoteViewModalBody').html('<p class="text-danger">Request failed.</p>');
		});
	});

	function cnPerformDelete(noteId) {
		noteId = parseInt(noteId, 10);
		if (!noteId) {
			return;
		}
		$.post('create_credit_note_data.php', {
			cmd: 'delete_credit_note',
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

	$('#btn_cn_send_mail').on('click', function() {
		var id = $(this).data('id') || cnViewNoteId;
		var no = $(this).data('no') || '';
		cnSendMail(id, no, $(this));
	});

	$(document).on('click', '.btn-send-credit-note-mail', function() {
		var id = $(this).data('id');
		var no = $(this).data('no') || '';
		cnSendMail(id, no, $(this));
	});

	$(document).on('click', '.btn-delete-credit-note', function() {
		var id = parseInt($(this).data('id'), 10);
		var no = $(this).data('no') || ('#' + id);
		var st = $(this).data('status') || '';
		if (!id) {
			return;
		}
		var title = (st === 'final') ? 'Cancel credit note' : 'Delete draft credit note';
		var message = (st === 'final')
			? ('Cancel credit note ' + no + '? This releases the amount and GST claim from the linked invoice. This cannot be undone.')
			: ('Delete draft credit note ' + no + '? This cannot be undone.');
		if (typeof window.ewConfirmDelete === 'function' && $('#ewDeleteModal').length) {
			window.ewConfirmDelete({
				id: String(id),
				title: title,
				message: message,
				onConfirm: function(delId) {
					cnPerformDelete(delId);
				}
			});
			return;
		}
		if (typeof window.ewConfirm === 'function' && $('#ewConfirmModal').length) {
			window.ewConfirm({
				title: title,
				message: message,
				confirmText: (st === 'final') ? 'Cancel credit note' : 'Delete',
				onConfirm: function() {
					cnPerformDelete(id);
				}
			});
		}
	});
});
</script>
</body>
</html>
