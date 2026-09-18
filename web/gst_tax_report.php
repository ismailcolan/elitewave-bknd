<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/gst_tax_functions.php');

ensure_gst_tax_master_table($conn);

$c_date = date('d-m-Y');
$default_from = date('d-m-Y', strtotime('first day of this month'));

$customers_q = mysqli_query($conn, 'SELECT client_id, client_company_name FROM client ORDER BY client_company_name ASC');
$tax_profiles = gst_tax_fetch_list($conn, array(
    'search' => '',
    'status' => 'active',
    'gst_rate' => 'all',
    'deleted' => 'active',
));
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.filter-label {
			display: block;
			font-weight: 600;
			font-size: 13px;
			margin-bottom: 6px;
			color: #333;
		}
		.required-star { color: #DD111E; font-weight: bold; }
		.filter-section {
			padding: 24px 20px 20px;
			border: 1px solid #e9ecef;
			border-top: none;
		}
		.filter-form-wrap { width: 100%; }
		.filter-row { margin-bottom: 14px; }
		.filter-row .form-group { margin-bottom: 0; width: 100%; }
		.filter-section select.filter-select,
		.filter-section select.form-control:not([multiple]) {
			display: block;
			width: 100% !important;
			height: 38px !important;
			min-height: 38px;
			border: 1px solid #D8DDE5;
			border-radius: 8px;
			background: #fff;
			padding: 4px 8px;
		}
		.table-scroll-wrapper {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
		}
		#gst_report_table { min-width: 1200px; }
		#gst_report_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
		}
		#gst_report_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#gst_report_table .num { text-align: right; }
		.btn-export-pdf.ew-btn-v2 {
			margin-left: 8px;
		}
		.ew-page-v2--mis-report .btn1 {
			margin-top: 0 !important;
		}
		.ew-page-v2 .report-count {
			font-size: 13px;
			font-weight: 600;
			color: #64748B;
		}
		.ew-page-v2 .filter-section {
			border: none;
			padding: 0 24px 24px;
		}
		.summary-box {
			margin-top: 14px;
			padding: 12px 14px;
			background: #f8fafc;
			border: 1px solid #e5e7eb;
			border-radius: 6px;
			font-size: 12px;
		}
		.summary-box strong { color: #0A1E3D; }
		#gst_report_table th.chk-col,
		#gst_report_table td.chk-col {
			width: 42px;
			text-align: center;
			vertical-align: middle;
		}
		#gst_report_table .row-check,
		#select_all_rows_th {
			cursor: pointer;
			width: 16px;
			height: 16px;
		}
		/* No sort arrow on checkbox column */
		#gst_report_table thead th.chk-col,
		#gst_report_table thead th.chk-col.sorting,
		#gst_report_table thead th.chk-col.sorting_asc,
		#gst_report_table thead th.chk-col.sorting_desc {
			cursor: default !important;
			background-image: none !important;
			padding-right: 8px !important;
		}
		#gst_report_table thead th.chk-col:before,
		#gst_report_table thead th.chk-col:after {
			display: none !important;
			content: none !important;
		}
	</style>
</head>
<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once('include/header.php');
			require_once('include/menu.php');
			?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--mis-report">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">GST Tax Report</h1>
							</div>
						</div>
						<div class="ew-card">
						<div class="ew-card-toolbar">
							<h2>Report Filters</h2>
						</div>
						<div class="ew-form-body">
							<div class="ew-mis-filter-shell">
								<div class="filter-form-wrap ew-form-grid ew-mis-filter-grid">
									<div class="ew-field">
										<label class="filter-label">From Date <span class="required-star">*</span></label>
										<?php echo ew_date_input(array(
											'id' => 'from_date',
											'value' => $default_from,
											'required' => true,
											'readonly' => true,
										)); ?>
									</div>
									<div class="ew-field">
										<label class="filter-label">To Date <span class="required-star">*</span></label>
										<?php echo ew_date_input(array(
											'id' => 'to_date',
											'value' => $c_date,
											'required' => true,
											'readonly' => true,
										)); ?>
									</div>
									<div class="ew-field span-2">
										<label class="filter-label">Customer</label>
										<select id="customers" class="form-control" multiple>
											<?php while ($cust = mysqli_fetch_assoc($customers_q)) { ?>
												<option value="<?php echo (int) $cust['client_id']; ?>"><?php echo htmlspecialchars($cust['client_company_name']); ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="ew-field">
										<label class="filter-label">GST Type</label>
										<select id="gst_type" class="form-control">
											<option value="all">All</option>
											<option value="intra">Intra</option>
											<option value="inter">Inter</option>
											<option value="exempt">Exempt</option>
											<option value="non_gst">Non-GST</option>
										</select>
									</div>
									<div class="ew-field">
										<label class="filter-label">Tax Code</label>
										<select id="tax_code" class="form-control">
											<option value="all">All</option>
											<?php foreach ($tax_profiles as $profile) { ?>
												<option value="<?php echo htmlspecialchars($profile['tax_code']); ?>"><?php echo htmlspecialchars($profile['tax_code'] . ' - ' . $profile['tax_name']); ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
								<div class="ew-mis-filter-footer">
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn1" id="search" onclick="if(window.loadGstTaxReport){window.loadGstTaxReport();}"><i class="fa fa-search"></i> Search</button>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-export-pdf" id="exportPdf"><i class="fa fa-file-pdf-o"></i> Download PDF</button>
								</div>
							</div>
						</div>
						</div>

				<div class="ew-card ew-erp-list" id="table_div" style="display:none;margin-bottom:20px;">
						<div class="ew-card-toolbar">
							<h2>GST Tax Report Data</h2>
							<div class="ew-toolbar-right">
								<span class="report-count" id="report_count"></span>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<div class="table-scroll-wrapper" id="report"></div>
							<div id="summary_box" class="summary-box" style="display:none;"></div>
						</div>
				</div>
					</div>
				</div>
			</div>
		</div>

		<?php require_once('include/footer.php'); ?>
	</div>

	<script type="text/javascript">
	function updateGstSelectedCount() {
		var total = window.gstReportRowData ? window.gstReportRowData.length : 0;
		var checked = window.gstReportSelectedGrns ? window.gstReportSelectedGrns.size : 0;
		var $all = $('#select_all_rows_th');
		if ($all.length) {
			$all.prop('checked', total > 0 && checked === total);
			$all.prop('indeterminate', checked > 0 && checked < total);
		}
	}

	function syncGstRowCheckboxesFromSelection() {
		if (!window.gstReportSelectedGrns) {
			return;
		}
		$('#gst_report_table tbody .row-check').each(function() {
			var grn = $(this).val();
			$(this).prop('checked', window.gstReportSelectedGrns.has(grn));
		});
		updateGstSelectedCount();
	}

	window.getSelectedGstGrnNos = function() {
		if (window.gstReportSelectedGrns && window.gstReportSelectedGrns.size) {
			return Array.from(window.gstReportSelectedGrns);
		}
		return [];
	};

	window.loadGstTaxReport = function() {
		var from = $('#from_date').val();
		var to = $('#to_date').val();
		if (!from || !to) {
			if (typeof ewFormToast === 'function') {
				ewFormToast('Please select From Date and To Date.', 'error', 5000);
			}
			return;
		}

		function getSelectedCustomers() {
			var vals = $('#customers').val();
			if (!vals) return '';
			if ($.isArray(vals)) {
				return vals.length ? vals.join(',') : '';
			}
			return String(vals);
		}

		function escCell(v) {
			if (v === null || v === undefined) return '';
			return String(v)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		function renderReport(raw) {
			var resp = null;
			try {
				resp = (typeof raw === 'string') ? JSON.parse(raw) : raw;
			} catch (e) {
				$('#table_div').show();
				$('#report').html('<p style="text-align:center;padding:30px;color:red;">Error loading report data.</p>');
				$('#summary_box').hide();
				return;
			}
			if (!resp || resp.status === 1) {
				$('#table_div').show();
				$('#report').html('<p style="text-align:center;padding:30px;color:red;">' + escCell(resp && resp.message ? resp.message : 'Error loading report data.') + '</p>');
				$('#summary_box').hide();
				return;
			}

			var data = resp.data || [];
			var summary = resp.summary || {};
			window.gstReportRowData = data;
			window.gstReportSelectedGrns = new Set();
			$.each(data, function(i, r) {
				if (r.grn_no) {
					window.gstReportSelectedGrns.add(String(r.grn_no));
				}
			});
			$('#table_div').show();

			if (data.length === 0) {
				$('#report').html('<p style="text-align:center;padding:30px;font-size:16px;">No records found for the selected filters.</p>');
				$('#report_count').text('');
				$('#summary_box').hide();
				return;
			}

			var cols = ['GCN No', 'Date', 'Customer', 'GST Type', 'Tax Code', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Cess', 'Total GST', 'Grand Total'];
			var keys = ['grn_no', 'grn_date', 'customer', 'gst_type', 'tax_code', 'taxable_value', 'cgst_amount', 'sgst_amount', 'igst_amount', 'cess_amount', 'gst_amount', 'grand_total'];
			var numKeys = {'taxable_value':1,'cgst_amount':1,'sgst_amount':1,'igst_amount':1,'cess_amount':1,'gst_amount':1,'grand_total':1};

			var html = '';
			html += '<table class="table table-bordered table-striped" id="gst_report_table"><thead><tr>';
			html += '<th class="chk-col sorting_disabled"><input type="checkbox" id="select_all_rows_th" checked title="Select All"></th>';
			html += '<th>S.No</th>';
			$.each(cols, function(i, c) { html += '<th>' + c + '</th>'; });
			html += '</tr></thead><tbody>';
			$.each(data, function(i, r) {
				var grn = escCell(r.grn_no);
				html += '<tr data-grn="' + grn + '">';
				html += '<td class="chk-col"><input type="checkbox" class="row-check" value="' + grn + '" checked></td>';
				html += '<td>' + escCell(r.s_no) + '</td>';
				$.each(keys, function(j, k) {
					html += '<td class="' + (numKeys[k] ? 'num' : '') + '">' + escCell(r[k]) + '</td>';
				});
				html += '</tr>';
			});
			html += '</tbody></table>';

			$('#report').html(html);
			$('#report_count').text(data.length + ' records found');
			updateGstSelectedCount();

			$('#summary_box').html(
				'<strong>Summary:</strong> ' + data.length + ' bookings &nbsp;|&nbsp; ' +
				'Taxable: <strong>' + escCell(summary.taxable_value) + '</strong> &nbsp;|&nbsp; ' +
				'CGST: <strong>' + escCell(summary.cgst_amount) + '</strong> &nbsp;|&nbsp; ' +
				'SGST: <strong>' + escCell(summary.sgst_amount) + '</strong> &nbsp;|&nbsp; ' +
				'IGST: <strong>' + escCell(summary.igst_amount) + '</strong> &nbsp;|&nbsp; ' +
				'Cess: <strong>' + escCell(summary.cess_amount) + '</strong> &nbsp;|&nbsp; ' +
				'Total GST: <strong>' + escCell(summary.gst_amount) + '</strong> &nbsp;|&nbsp; ' +
				'Grand Total: <strong>' + escCell(summary.grand_total) + '</strong>'
			).show();

			if (window.gstReportTable) {
				try { window.gstReportTable.destroy(); } catch (e) {}
			}
			if ($.fn.DataTable) {
				window.gstReportTable = $('#gst_report_table').DataTable({
					dom: 'frtip',
					lengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
					pageLength: 25,
					scrollX: true,
					order: [[1, 'asc']],
					deferRender: true,
					aoColumnDefs: [
						{ bSortable: false, aTargets: [0], sClass: 'chk-col' }
					],
					columnDefs: [
						{ orderable: false, targets: 0, className: 'chk-col' }
					]
				});
				window.gstReportTable.on('draw.dt', function() {
					syncGstRowCheckboxesFromSelection();
				});
				// Ensure checkbox header never gets sort classes/icons
				$('#gst_report_table thead th.chk-col')
					.removeClass('sorting sorting_asc sorting_desc')
					.addClass('sorting_disabled');
			}
			syncGstRowCheckboxesFromSelection();
		}

		var payload = {
			from_date: from,
			to_date: to,
			customers: getSelectedCustomers(),
			gst_type: $('#gst_type').val(),
			tax_code: $('#tax_code').val()
		};

		$('#table_div').show();
		$('#report').html('<p style="text-align:center;padding:30px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading report data...</p>');
		$('#summary_box').hide();
		$('#report_count').text('');

		$.ajax({
			url: 'gst_tax_report_data.php',
			type: 'POST',
			data: payload,
			dataType: 'text',
			timeout: 180000,
			success: renderReport,
			error: function() {
				$('#table_div').show();
				$('#report').html('<p style="text-align:center;padding:30px;color:red;">Error loading report data. Please try again.</p>');
			}
		});
	};

	$(document).ready(function() {
		$('#table_div').hide();

		try {
			if ($('#customers').length && typeof $('#customers').select2 === 'function') {
				$('#customers').select2({
					placeholder: 'All Customers',
					allowClear: true,
					width: '100%'
				});
			}
		} catch (e) {}

		$(document).on('click', '#search', function(e) {
			e.preventDefault();
			if (window.loadGstTaxReport) {
				window.loadGstTaxReport();
			}
		});

		$(document).on('change', '#select_all_rows_th', function() {
			var checked = $(this).prop('checked');
			if (!window.gstReportSelectedGrns || !window.gstReportRowData) {
				return;
			}
			window.gstReportSelectedGrns.clear();
			if (checked) {
				$.each(window.gstReportRowData, function(i, row) {
					if (row.grn_no) {
						window.gstReportSelectedGrns.add(String(row.grn_no));
					}
				});
			}
			syncGstRowCheckboxesFromSelection();
		});

		$(document).on('change', '#gst_report_table tbody .row-check', function() {
			var grn = String($(this).val() || '');
			if (!window.gstReportSelectedGrns || !grn) {
				return;
			}
			if ($(this).prop('checked')) {
				window.gstReportSelectedGrns.add(grn);
			} else {
				window.gstReportSelectedGrns.delete(grn);
			}
			updateGstSelectedCount();
		});

		$(document).on('click', '#exportPdf', function(e) {
			e.preventDefault();
			var from = $('#from_date').val();
			var to = $('#to_date').val();
			if (!from || !to) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}
			if (!$('#table_div').is(':visible') || !$('#gst_report_table').length) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please click Search first to load the report.', 'warning', 5000);
				}
				return;
			}
			var selected = window.getSelectedGstGrnNos ? window.getSelectedGstGrnNos() : [];
			if (!selected.length) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select at least one row (or use Select All).', 'warning', 5000);
				}
				return;
			}
			function getSelectedCustomers() {
				var vals = $('#customers').val();
				if (!vals) return '';
				if ($.isArray(vals)) {
					return vals.length ? vals.join(',') : '';
				}
				return String(vals);
			}
			var totalRows = window.gstReportRowData ? window.gstReportRowData.length : 0;
			var exportAll = totalRows > 0 && selected.length === totalRows;
			var pdfUrl = 'gst_tax_report_pdf.php?from_date=' + encodeURIComponent(from) +
				'&to_date=' + encodeURIComponent(to) +
				'&customers=' + encodeURIComponent(getSelectedCustomers()) +
				'&gst_type=' + encodeURIComponent($('#gst_type').val() || 'all') +
				'&tax_code=' + encodeURIComponent($('#tax_code').val() || 'all');
			if (!exportAll) {
				pdfUrl += '&grn_nos=' + encodeURIComponent(selected.join(','));
			}
			window.location.href = pdfUrl;
		});
	});
	</script>
</body>
</html>
