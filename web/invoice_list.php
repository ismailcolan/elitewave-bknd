<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$list_q = mysqli_query($conn, "SELECT m.*, c.client_company_name
    FROM billing_invoice_master m
    LEFT JOIN client c ON c.client_id = m.customer_id
    ORDER BY m.billing_invoice_id DESC
    LIMIT 500");
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.invoice-header-bar {
			background: linear-gradient(185deg, var(--ew-navy) 0%, var(--ew-navy-deep) 100%);
			color: #fff; padding: 16px 24px; border-radius: 8px 8px 0 0; font-size: 18px; font-weight: 700;
		}
		.badge-draft { background: #f59e0b; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.badge-final { background: #16a34a; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.badge-cancelled { background: #94a3b8; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.table-scroll-wrapper {
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
			border: 1px solid #e5e7eb;
			border-radius: 6px;
		}
		#invoice_list_table { font-size: 13px; margin-bottom: 0; border-collapse: collapse; width: 100% !important; }
		#invoice_list_table th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
			vertical-align: middle;
		}
		#invoice_list_table td {
			vertical-align: middle;
			border: none !important;
			border-bottom: 1px solid #e9ecef !important;
			background: #fff;
		}
		#invoice_list_table tbody tr:nth-child(even) td { background: #f9fafb; }
		#invoice_list_table tbody td.col-amount { white-space: nowrap; }
		#invoice_list_table th.col-actions,
		#invoice_list_table td.col-actions {
			width: 132px;
			min-width: 132px;
			max-width: 132px;
			white-space: nowrap;
		}
		#invoice_list_table .col-actions .act-link {
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
		}
		#invoice_list_table .col-actions .act-view { background: #e8edf3; color: #0A1E3D; }
		#invoice_list_table .col-actions .act-view:hover { background: #d5dde8; color: #0A1E3D; }
		#invoice_list_table .col-actions .act-dl { background: #dcfce7; color: #16a34a; }
		#invoice_list_table .col-actions .act-dl:hover { background: #bbf7d0; color: #15803d; }
		#invoice_list_table .col-actions .act-edit { background: #fef3c7; color: #d97706; }
		#invoice_list_table .col-actions .act-edit:hover { background: #fde68a; color: #b45309; }
		#invoice_list_table .col-actions .act-cancel { background: #fee2e2; color: #dc2626; border: none; padding: 0; }
		#invoice_list_table .col-actions .act-cancel:hover { background: #fecaca; color: #b91c1c; }
		/* Kill DataTables sort arrows / black borders on actions header */
		table.dataTable#invoice_list_table thead th,
		table.dataTable#invoice_list_table thead td {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
		}
		table.dataTable#invoice_list_table tbody td { background: transparent !important; color: inherit !important; }
		table.dataTable#invoice_list_table thead th.sorting,
		table.dataTable#invoice_list_table thead th.sorting_asc,
		table.dataTable#invoice_list_table thead th.sorting_desc {
			background-image: none !important;
		}
		table.dataTable#invoice_list_table thead th.col-actions:before,
		table.dataTable#invoice_list_table thead th.col-actions:after { display: none !important; content: none !important; }
		table.dataTable#invoice_list_table thead th.col-actions.sorting,
		table.dataTable#invoice_list_table thead th.col-actions.sorting_asc,
		table.dataTable#invoice_list_table thead th.col-actions.sorting_desc {
			cursor: default !important;
			background-image: none !important;
		}
		.dataTables_wrapper .dataTables_length,
		.dataTables_wrapper .dataTables_filter { margin-bottom: 12px; }
		.ew-erp-list .ew-card-toolbar .ew-toolbar-right {
			display: flex;
			align-items: center;
			flex-wrap: wrap;
			gap: 10px;
		}
		.ew-invoice-series-filter {
			position: relative;
			display: inline-flex;
			align-items: center;
		}
		.ew-invoice-series-filter > .fa-filter {
			position: absolute;
			left: 10px;
			top: 50%;
			transform: translateY(-50%);
			font-size: 12px;
			color: var(--ew-text-muted, #6B7A8D);
			pointer-events: none;
			z-index: 1;
		}
		.ew-invoice-series-filter__select {
			width: 118px;
			height: 32px;
			min-height: 32px;
			padding: 0 24px 0 32px;
			margin: 0;
			font-size: 13px;
			font-weight: 600;
			color: var(--ew-navy, #06416F);
			line-height: normal;
			border-radius: 6px;
			border: 1px solid var(--panel-border, #C5D3E0);
			background-color: #fff;
			box-sizing: border-box;
			cursor: pointer;
			-webkit-appearance: none;
			-moz-appearance: none;
			appearance: none;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%236B7A8D' d='M1 1l4 4 4-4'/%3E%3C/svg%3E");
			background-repeat: no-repeat;
			background-position: right 10px center;
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
							<h1 class="ew-page-title">Tax Invoice</h1>
						</div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Tax Invoice List</h2>
							<div class="ew-toolbar-right">
								<div class="ew-invoice-series-filter">
									<i class="fa fa-filter" aria-hidden="true"></i>
									<select id="invoice_series_filter" class="ew-invoice-series-filter__select" title="Filter" aria-label="Filter by invoice series">
										<option value="">All</option>
										<option value="GST">GST</option>
										<option value="GTA">GTA</option>
									</select>
								</div>
								<div class="ew-list-toolbar__tools"></div>
								<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_invoice_list_pdf" title="Export list to PDF (current search &amp; filter)">
									Export PDF <i class="fa fa-file-pdf-o"></i>
								</button>
								<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_invoice_list_csv" title="Export list to CSV (current search &amp; filter)">
									Export CSV <i class="fa fa-file-text-o"></i>
								</button>
								<a href="create_invoice.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
						<div class="table-scroll-wrapper">
						<table class="table" id="invoice_list_table">
							<thead>
								<tr>
									<th>S.No</th><th>Invoice No</th><th>Date</th><th>Customer</th><th>Status</th>
									<th>Grand Total</th><th>GCN Count</th><th class="col-actions">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$i = 1;
								if ($list_q) {
									while ($row = mysqli_fetch_assoc($list_q)) {
										$cnt_q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM billing_invoice_details WHERE billing_invoice_id='" . (int) $row['billing_invoice_id'] . "'");
										$cnt = mysqli_fetch_assoc($cnt_q);
										if ($row['status'] === 'cancelled') {
											$status_badge = '<span class="badge-cancelled">Cancelled</span>';
										} elseif ($row['status'] === 'draft') {
											$status_badge = '<span class="badge-draft">Draft</span>';
										} else {
											$status_badge = '<span class="badge-final">Final</span>';
										}
										$inv_id = (int) $row['billing_invoice_id'];
										$inv_no = $row['invoice_no'] ?: ('DRAFT-' . $row['billing_invoice_id']);
										$row_series = strtoupper(trim((string) ($row['invoice_series'] ?? '')));
										if ($row_series !== 'GST' && $row_series !== 'GTA') {
											$row_series = billing_invoice_no_series($inv_no);
										}
										echo '<tr data-invoice-series="' . htmlspecialchars($row_series, ENT_QUOTES, 'UTF-8') . '">';
										echo '<td class="col-sno">' . $i++ . '</td>';
										echo '<td>' . htmlspecialchars($inv_no) . '</td>';
										echo '<td>' . htmlspecialchars($row['invoice_date']) . '</td>';
										echo '<td>' . htmlspecialchars($row['client_company_name']) . '</td>';
										echo '<td>' . $status_badge . '</td>';
										echo '<td class="col-amount">' . number_format((float) $row['grand_total'], 2) . '</td>';
										echo '<td class="col-sno">' . (int) ($cnt['c'] ?? 0) . '</td>';
										echo '<td class="col-actions">';
										if ($row['status'] === 'draft' || $row['status'] === 'final') {
											echo '<a class="act-link act-edit" href="create_invoice.php?id=' . $inv_id . '" title="Edit"><i class="fa fa-pencil"></i></a>';
										}
										if ($row['status'] === 'final' || $row['status'] === 'cancelled') {
											echo '<a class="act-link act-view" target="_blank" href="tax_invoice_pdf.php?id=' . $inv_id . '" title="View PDF"><i class="fa fa-eye"></i></a>';
											echo '<a class="act-link act-dl" href="tax_invoice_pdf.php?id=' . $inv_id . '&download=1" title="Download PDF"><i class="fa fa-download"></i></a>';
										}
										if ($row['status'] === 'final' || $row['status'] === 'draft') {
											echo '<button type="button" class="act-link act-cancel btn-invoice-cancel" data-id="' . $inv_id . '" title="Cancel invoice"><i class="fa fa-ban"></i></button>';
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
</div>
<form id="invoice_list_pdf_form" method="post" action="invoice_list_export_pdf.php" target="_blank" style="display:none;" aria-hidden="true">
	<input type="hidden" name="payload" id="invoice_list_pdf_payload" value="">
</form>
<script>
var ewInvoiceListSeriesFilter = '';

(function() {
	if (!$.fn.dataTableExt || !$.fn.dataTableExt.afnFiltering) {
		return;
	}
	$.fn.dataTableExt.afnFiltering.push(function(oSettings, aData, iDataIndex) {
		if (!oSettings.oInstance || !oSettings.oInstance[0] || oSettings.oInstance[0].id !== 'invoice_list_table') {
			return true;
		}
		if (!ewInvoiceListSeriesFilter) {
			return true;
		}
		var nTr = oSettings.aoData[iDataIndex] && oSettings.aoData[iDataIndex].nTr;
		if (!nTr) {
			return true;
		}
		return ($(nTr).attr('data-invoice-series') || '') === ewInvoiceListSeriesFilter;
	});
})();

function ewCsvEscapeCell(val) {
	var s = String(val == null ? '' : val).replace(/\s+/g, ' ').trim();
	if (/[",\r\n]/.test(s)) {
		return '"' + s.replace(/"/g, '""') + '"';
	}
	return s;
}

function getInvoiceListExportData() {
	var $table = $('#invoice_list_table');
	if (!$table.length || !$.fn.dataTable || !$.fn.dataTable.fnIsDataTable($table[0])) {
		return null;
	}
	var oTable = $table.dataTable();
	var oSettings = oTable.fnSettings();
	var colCount = 7;
	var headers = [];
	var rows = [];
	$table.find('thead th').each(function(i) {
		if (i >= colCount) {
			return;
		}
		headers.push(String($(this).text()).replace(/\s+/g, ' ').trim());
	});
	for (var j = 0; j < oSettings.aiDisplay.length; j++) {
		var idx = oSettings.aiDisplay[j];
		var rowData = oSettings.aoData[idx];
		if (!rowData || !rowData.nTr) {
			continue;
		}
		var cells = [];
		$(rowData.nTr).find('td').each(function(i) {
			if (i >= colCount) {
				return;
			}
			cells.push(String($(this).text()).replace(/\s+/g, ' ').trim());
		});
		if (cells.length) {
			rows.push(cells);
		}
	}
	var search = '';
	if (oSettings.oPreviousSearch && oSettings.oPreviousSearch.sSearch) {
		search = String(oSettings.oPreviousSearch.sSearch).trim();
	}
	return {
		headers: headers,
		rows: rows,
		series_filter: ewInvoiceListSeriesFilter || '',
		search: search
	};
}

function exportInvoiceListCsv() {
	var data = getInvoiceListExportData();
	if (!data || !data.rows.length) {
		window.alert('No rows to export.');
		return;
	}
	var lines = [];
	lines.push(data.headers.map(ewCsvEscapeCell).join(','));
	for (var r = 0; r < data.rows.length; r++) {
		lines.push(data.rows[r].map(ewCsvEscapeCell).join(','));
	}
	var blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
	var url = URL.createObjectURL(blob);
	var a = document.createElement('a');
	a.href = url;
	a.download = 'tax_invoice_list_' + new Date().toISOString().slice(0, 10) + '.csv';
	document.body.appendChild(a);
	a.click();
	document.body.removeChild(a);
	URL.revokeObjectURL(url);
}

function exportInvoiceListPdf() {
	var data = getInvoiceListExportData();
	if (!data || !data.rows.length) {
		window.alert('No rows to export.');
		return;
	}
	var $form = document.getElementById('invoice_list_pdf_form');
	var $payload = document.getElementById('invoice_list_pdf_payload');
	if (!$form || !$payload) {
		window.alert('Could not start PDF export.');
		return;
	}
	$payload.value = JSON.stringify(data);
	$form.submit();
}

$(function() {
	$('#btn_invoice_list_csv').on('click', function() {
		exportInvoiceListCsv();
	});
	$('#btn_invoice_list_pdf').on('click', function() {
		exportInvoiceListPdf();
	});

	$('#invoice_series_filter').on('change', function() {
		ewInvoiceListSeriesFilter = ($(this).val() || '').toUpperCase();
		var dt = $('#invoice_list_table').dataTable();
		if (dt && dt.fnDraw) {
			dt.fnDraw();
		}
	});

	if ($.fn.dataTable && $('#invoice_list_table').length && !$.fn.dataTable.fnIsDataTable($('#invoice_list_table')[0])) {
		$('#invoice_list_table').dataTable({
			sPaginationType: 'full_numbers',
			iDisplayLength: 10,
			aaSorting: [[0, 'asc']],
			aoColumnDefs: [
				{ bSortable: false, aTargets: [0], sClass: 'col-sno' },
				{ bSortable: false, aTargets: [-1], sClass: 'col-actions' },
				{ aTargets: [5], sClass: 'col-amount' },
				{ aTargets: [6], sClass: 'col-sno' }
			]
		});
	}
	window.setTimeout(function() {
		if (window.applyEwListLayout) {
			window.applyEwListLayout();
		}
	}, 250);
	$(document).on('click', '.btn-invoice-cancel', function() {
		var id = parseInt($(this).data('id'), 10);
		if (!id || typeof ewOpenCancelModal !== 'function') {
			return;
		}
		ewOpenCancelModal({
			mode: 'invoice',
			billingInvoiceId: id,
			title: 'Cancel tax invoice',
			message: 'This will mark the invoice as cancelled. PDF will show cancelled watermark. This cannot be undone.'
		});
	});
});
</script>
</body>
</html>
