<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$filter = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$where = "WHERE m.status='final'";
if ($filter === 'against') {
    $where .= " AND m.receipt_type='against_invoice'";
} elseif ($filter === 'other') {
    $where .= " AND m.receipt_type IN ('advance','investment','general')";
}

$list_q = mysqli_query($conn, "SELECT m.*, c.client_company_name
    FROM billing_receipt_master m
    LEFT JOIN client c ON c.client_id = m.party_id
    $where
    ORDER BY m.billing_receipt_id DESC
    LIMIT 500");
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.table-scroll-wrapper { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 6px; }
		#receipt_list_table { font-size: 13px; margin-bottom: 0; border-collapse: collapse; width: 100% !important; }
		#receipt_list_table th { background: #DDE7F0 !important; font-size: 11px; font-weight: 700; padding: 8px 6px; border-bottom: 2px solid #C5D3E0 !important; }
		#receipt_list_table td { padding: 6px; border-bottom: 1px solid #e9ecef !important; vertical-align: middle; }
		#receipt_list_table .num { text-align: right; white-space: nowrap; }
		#receipt_list_table th.col-actions, #receipt_list_table td.col-actions { text-align: center; width: 88px; min-width: 88px; }
		#receipt_list_table .col-actions .act-link { margin: 0 2px; border: none; cursor: pointer; }
		#receipt_list_table .col-actions .act-view { background: #e8edf3; color: #0A1E3D; }
		#receipt_list_table .col-actions .act-view:hover { background: #d5dde8; }
		#receipt_list_table .col-actions .act-edit { background: #fef3c7; color: #d97706; }
		#receipt_list_table .col-actions .act-edit:hover { background: #fde68a; color: #b45309; }
		.filter-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
		.filter-pills a { padding: 6px 12px; border-radius: 8px; border: 1px solid #D8DDE5; font-size: 12px; font-weight: 600; text-decoration: none; color: #334155; background: #fff; }
		.filter-pills a.active { background: #0A1E3D; color: #fff; border-color: #0A1E3D; }
		.act-link { display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 4px; background: #e8edf3; color: #0A1E3D; text-decoration: none !important; }
		.rcpt-view-kpi { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
		.rcpt-view-kpi .kpi { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
		.rcpt-view-kpi .kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 4px; }
		.rcpt-view-kpi .kpi-value { font-size: 16px; font-weight: 700; color: #0A1E3D; }
		.rcpt-view-kpi .kpi.kpi-highlight { background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%); border-color: #86efac; }
		.rcpt-view-kpi .kpi.kpi-highlight .kpi-value { color: #14532d; }
		.rcpt-view-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 16px; margin-bottom: 18px; font-size: 13px; }
		.rcpt-view-meta dt { font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin: 0 0 4px; }
		.rcpt-view-meta dd { margin: 0 0 10px; color: #021659; font-weight: 700; font-size: 13px; line-height: 1.35; }
		.rcpt-view-table { width: 100%; border-collapse: collapse; font-size: 13px; }
		.rcpt-view-table th, .rcpt-view-table td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
		.rcpt-view-table th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #475569; }
		.rcpt-view-table .num { text-align: right; }
		.rcpt-view-section-title { font-size: 13px; font-weight: 700; color: #0A1E3D; margin: 0 0 10px; }
		@media (max-width: 640px) {
			.rcpt-view-kpi, .rcpt-view-meta { grid-template-columns: 1fr; }
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
						<div class="ew-page-head-left"><h1 class="ew-page-title">Receipt</h1></div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Receipt List</h2>
							<div class="ew-toolbar-right">
								<a href="create_receipt.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
							<div class="filter-pills">
								<a href="receipt_list.php" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
								<a href="receipt_list.php?type=against" class="<?php echo $filter === 'against' ? 'active' : ''; ?>">Against Invoice</a>
								<a href="receipt_list.php?type=other" class="<?php echo $filter === 'other' ? 'active' : ''; ?>">Advance / Investment / General</a>
							</div>
							<div class="table-scroll-wrapper">
								<table class="table" id="receipt_list_table">
									<thead>
										<tr>
											<th>S.No</th>
											<th>Receipt No</th>
											<th>Date</th>
											<th>Type</th>
											<th>Payer / Customer</th>
											<th class="num">Amount</th>
											<th class="num">TDS</th>
											<th class="num">Balance</th>
											<th>Mode</th>
											<th class="col-actions">Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$i = 1;
										if ($list_q) {
											while ($row = mysqli_fetch_assoc($list_q)) {
												$payer = $row['client_company_name'] ?: ($row['payer_name'] ?: '—');
												$type = billing_receipt_type_label($row['receipt_type']);
												$bal = '—';
												if ($row['receipt_type'] === 'against_invoice') {
													$bal = number_format((float) billing_receipt_list_balance_after($conn, (int) $row['billing_receipt_id']), 2);
												}
												echo '<tr>';
												echo '<td>' . $i++ . '</td>';
												echo '<td>' . htmlspecialchars($row['receipt_no']) . '</td>';
												echo '<td>' . htmlspecialchars($row['receipt_date']) . '</td>';
												echo '<td>' . htmlspecialchars($type) . '</td>';
												echo '<td>' . htmlspecialchars($payer) . '</td>';
												echo '<td class="num">' . number_format((float) $row['total_amount'], 2) . '</td>';
												echo '<td class="num">' . number_format((float) $row['tds_total'], 2) . '</td>';
												echo '<td class="num">' . $bal . '</td>';
												echo '<td>' . htmlspecialchars($row['payment_mode']) . '</td>';
												$rid = (int) $row['billing_receipt_id'];
												echo '<td class="col-actions">';
												echo '<button type="button" class="act-link act-view btn-view-receipt" title="View" data-id="' . $rid . '" data-no="' . htmlspecialchars($row['receipt_no'], ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-eye"></i></button>';
												echo '<a class="act-link act-edit" href="create_receipt.php?id=' . $rid . '&amp;edit=1" title="Edit"><i class="fa fa-pencil"></i></a>';
												echo '</td>';
												echo '</tr>';
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

	<div class="ew-v2-modal-backdrop" id="receiptViewModal">
		<div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
				<h3 id="receiptViewModalTitle">Receipt details</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body" id="receiptViewModalBody">
				<p class="text-muted" style="margin:0;">Loading…</p>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
				<a href="#" class="btn btn-primary" id="btn_receipt_download_pdf" style="display:none;" target="_blank" rel="noopener"><i class="fa fa-download"></i> Download PDF</a>
			</div>
		</div>
	</div>
</div>
<script>
$(function() {
	if ($.fn.dataTable && $('#receipt_list_table').length && !$.fn.dataTable.fnIsDataTable($('#receipt_list_table')[0])) {
		$('#receipt_list_table').dataTable({
			sPaginationType: 'full_numbers',
			iDisplayLength: 25,
			aaSorting: [[0, 'asc']],
			aoColumnDefs: [{ bSortable: false, aTargets: [0, -1] }]
		});
	}
	setTimeout(function() { if (window.applyEwListLayout) window.applyEwListLayout(); }, 250);

	function rcptEscHtml(str) {
		return String(str == null ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function rcptRenderView(data) {
		if (!data) {
			return '<p class="text-danger">No data.</p>';
		}
		var html = '';
		html += '<div class="rcpt-view-meta">';
		html += '<div><dt>Receipt type</dt><dd>' + rcptEscHtml(data.receipt_type || '—') + '</dd></div>';
		html += '<div><dt>Receipt date</dt><dd>' + rcptEscHtml(data.receipt_date || '—') + '</dd></div>';
		html += '<div><dt>Customer / payer</dt><dd>' + rcptEscHtml(data.party_label || '—') + '</dd></div>';
		html += '<div><dt>Payment mode</dt><dd>' + rcptEscHtml(data.payment_mode || '—') + '</dd></div>';
		if (data.payment_ref) {
			html += '<div><dt>Reference</dt><dd>' + rcptEscHtml(data.payment_ref) + '</dd></div>';
		}
		if (data.payment_ref_date) {
			html += '<div><dt>Reference date</dt><dd>' + rcptEscHtml(data.payment_ref_date) + '</dd></div>';
		}
		if (data.bank_account_label && data.bank_account_label !== '—') {
			html += '<div><dt>Bank account</dt><dd>' + rcptEscHtml(data.bank_account_label) + '</dd></div>';
		}
		if (data.remarks) {
			html += '<div><dt>Remarks</dt><dd>' + rcptEscHtml(data.remarks) + '</dd></div>';
		}
		html += '</div>';

		var kpiThirdLabel = data.receipt_type_key === 'against_invoice' ? 'Invoice total' : 'Receipt amount';
		var kpiThirdVal = data.receipt_type_key === 'against_invoice' ? (data.invoice_open_total || '0.00') : (data.total_amount || '0.00');
		html += '<div class="rcpt-view-kpi">';
		html += '<div class="kpi"><div class="kpi-label">Amount received</div><div class="kpi-value">' + rcptEscHtml(data.total_amount) + '</div></div>';
		html += '<div class="kpi"><div class="kpi-label">TDS deduction</div><div class="kpi-value">' + rcptEscHtml(data.tds_total) + '</div></div>';
		html += '<div class="kpi kpi-highlight"><div class="kpi-label">' + rcptEscHtml(kpiThirdLabel) + '</div><div class="kpi-value">' + rcptEscHtml(kpiThirdVal) + '</div></div>';
		html += '</div>';

		if (data.lines && data.lines.length) {
			html += '<p class="rcpt-view-section-title">Invoice allocation</p>';
			html += '<div class="table-responsive"><table class="rcpt-view-table"><thead><tr>';
			html += '<th>Inv. no</th><th>Date</th><th class="num">Inv. receipt</th><th class="num">Already received</th><th class="num">Credit note</th><th class="num">TDS</th><th class="num">Received</th><th class="num">Balance</th>';
			html += '</tr></thead><tbody>';
			for (var i = 0; i < data.lines.length; i++) {
				var ln = data.lines[i];
				html += '<tr>';
				html += '<td>' + rcptEscHtml(ln.invoice_no) + '</td>';
				html += '<td>' + rcptEscHtml(ln.invoice_date) + '</td>';
				html += '<td class="num">' + rcptEscHtml(ln.balance_open) + '</td>';
				html += '<td class="num">' + rcptEscHtml(ln.already_received) + '</td>';
				html += '<td class="num">' + rcptEscHtml(ln.credit_note_amount) + '</td>';
				html += '<td class="num">' + rcptEscHtml(ln.tds_amount) + '</td>';
				html += '<td class="num">' + rcptEscHtml(ln.receive_now) + '</td>';
				html += '<td class="num">' + rcptEscHtml(ln.balance_remain) + '</td>';
				html += '</tr>';
			}
			html += '</tbody></table></div>';
		}

		return html;
	}

	$(document).on('click', '.btn-view-receipt', function() {
		var id = $(this).data('id');
		var no = $(this).data('no') || '';
		if (!id) {
			return;
		}
		$('#receiptViewModalTitle').text('Receipt — ' + (no || ('#' + id)));
		$('#receiptViewModalBody').html('<p class="text-muted" style="margin:0;">Loading…</p>');
		$('#btn_receipt_download_pdf').hide().attr('href', '#');
		if (typeof ewV2OpenModal === 'function') {
			ewV2OpenModal('receiptViewModal');
		} else {
			$('#receiptViewModal').addClass('open');
		}
		$.getJSON('create_receipt_data.php', { cmd: 'view', billing_receipt_id: id }, function(r) {
			if (!r || r.status !== 0 || !r.data) {
				$('#receiptViewModalBody').html('<p class="text-danger">' + rcptEscHtml(r && r.message ? r.message : 'Could not load details.') + '</p>');
				return;
			}
			var titleNo = r.data.receipt_no || no || ('#' + id);
			$('#receiptViewModalTitle').text('Receipt — ' + titleNo);
			$('#receiptViewModalBody').html(rcptRenderView(r.data));
			$('#btn_receipt_download_pdf').attr('href', 'receipt_pdf.php?id=' + encodeURIComponent(id) + '&download=1').show();
		}).fail(function() {
			$('#receiptViewModalBody').html('<p class="text-danger">Request failed.</p>');
		});
	});
});
</script>
</body>
</html>
