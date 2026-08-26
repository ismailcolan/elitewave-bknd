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
		.required-star { color: red; font-weight: bold; }
		.filter-section {
			padding: 24px 20px 20px;
			border: 1px solid #e9ecef;
			border-top: none;
		}
		.filter-form-wrap { max-width: 820px; margin: 0 auto; }
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
		.filter-section .select2-container {
			display: block !important;
			width: 100% !important;
			max-width: 100% !important;
			min-width: 0 !important;
		}
		.filter-section .select2-container-multi .select2-choices {
			min-height: 38px !important;
			height: 38px !important;
			max-height: 38px !important;
			overflow-x: auto !important;
			overflow-y: hidden !important;
			white-space: nowrap !important;
			padding: 3px 6px !important;
		}
		.filter-section .select2-container-multi .select2-choices li {
			float: none !important;
			display: inline-block !important;
			vertical-align: middle;
			white-space: nowrap;
		}
		.filter-section .select2-container-multi .select2-choices .select2-search-field {
			width: 100% !important;
			margin: 0;
			padding: 0;
		}
		.filter-section .select2-container-multi .select2-choices .select2-search-field input {
			width: 100% !important;
			min-width: 100% !important;
			max-width: none !important;
			padding: 0 8px !important;
			height: 30px !important;
			margin: 0 !important;
			line-height: 30px !important;
			font-size: 13px !important;
			box-sizing: border-box;
		}
		.filter-section .select2-container-multi .select2-choices .select2-search-choice ~ .select2-search-field {
			width: auto !important;
		}
		.filter-section .select2-container-multi .select2-choices .select2-search-choice ~ .select2-search-field input {
			width: 20px !important;
			min-width: 20px !important;
			max-width: 80px !important;
		}
		.filter-section .select2-container-multi .select2-choices .select2-search-choice {
			position: relative;
			margin: 2px 4px 2px 0 !important;
			padding: 3px 20px 3px 8px !important;
			line-height: 18px !important;
			max-width: 180px;
			overflow: hidden;
			text-overflow: ellipsis;
			background: #e8edf3 !important;
			border: 1px solid #c5ced8;
			border-radius: 3px;
			color: #333;
			font-size: 12px;
		}
		.filter-section .select2-container-multi .select2-choices .select2-search-choice-close,
		.filter-section .select2-search-choice-close {
			display: block !important;
			position: absolute !important;
			right: 3px !important;
			left: auto !important;
			top: 50% !important;
			width: 14px !important;
			height: 14px !important;
			margin-top: -7px !important;
			background: none !important;
			background-image: none !important;
			font-size: 16px !important;
			line-height: 12px !important;
			text-align: center;
			text-decoration: none !important;
			color: #555 !important;
			opacity: 1 !important;
			z-index: 2;
		}
		.filter-section .select2-container-multi .select2-choices .select2-search-choice-close:before,
		.filter-section .select2-search-choice-close:before {
			content: "\00d7";
			font-family: Arial, sans-serif;
			font-weight: 700;
			color: #444;
			font-size: 15px;
		}
		.table-scroll-wrapper {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
		}
		#gst_report_table { min-width: 1200px; }
		#gst_report_table th {
			background: #0A1E3D;
			color: #fff;
			font-size: 11px;
			font-weight: 600;
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
		.btn-export-pdf {
			background: #c0392b;
			color: #fff;
			font-weight: 600;
			padding: 8px 16px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 13px;
			margin-left: 8px;
		}
		.btn-export-pdf:hover { background: #a93226; color: #fff; }
		.btn1 {
			font-weight: 600;
			padding: 8px 20px !important;
			border: none !important;
			border-radius: 4px !important;
			cursor: pointer !important;
			font-size: 13px !important;
			margin-top: 4px !important;
		}
		.gst-report-header {
			background: linear-gradient(185deg, var(--ew-navy) 0%, var(--ew-navy-deep) 100%);
			color: #fff;
			padding: 16px 24px;
			border-radius: 8px 8px 0 0;
			font-size: 18px;
			font-weight: 700;
		}
		.gst-report-header i { margin-right: 8px; }
		.report-count { float: right; font-size: 13px; font-weight: 400; color: #ccc; }
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
				<div class="col-md-offset-1 col-md-10">
					<div class="widget-container fluid-height clearfix">
						<div class="gst-report-header">
							<i class="fa fa-file-pdf-o"></i> GST Tax Report
						</div>
						<div class="filter-section">
							<div class="filter-form-wrap">
								<div class="row filter-row">
									<div class="col-md-6 col-sm-6">
										<div class="form-group">
											<label class="filter-label">From Date <span class="required-star">*</span></label>
											<?php echo ew_date_input(array(
												'id' => 'from_date',
												'value' => $default_from,
												'required' => true,
												'readonly' => true,
											)); ?>
										</div>
									</div>
									<div class="col-md-6 col-sm-6">
										<div class="form-group">
											<label class="filter-label">To Date <span class="required-star">*</span></label>
											<?php echo ew_date_input(array(
												'id' => 'to_date',
												'value' => $c_date,
												'required' => true,
												'readonly' => true,
											)); ?>
										</div>
									</div>
								</div>

								<div class="row filter-row">
									<div class="col-md-6 col-sm-6">
										<div class="form-group">
											<label class="filter-label">Customer</label>
											<select id="customers" class="form-control" multiple>
												<?php while ($cust = mysqli_fetch_assoc($customers_q)) { ?>
													<option value="<?php echo (int) $cust['client_id']; ?>"><?php echo htmlspecialchars($cust['client_company_name']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="col-md-3 col-sm-6">
										<div class="form-group">
											<label class="filter-label">GST Type</label>
											<select id="gst_type" class="form-control">
												<option value="all">All</option>
												<option value="intra">Intra</option>
												<option value="inter">Inter</option>
												<option value="exempt">Exempt</option>
												<option value="non_gst">Non-GST</option>
											</select>
										</div>
									</div>
									<div class="col-md-3 col-sm-6">
										<div class="form-group">
											<label class="filter-label">Tax Code</label>
											<select id="tax_code" class="form-control">
												<option value="all">All</option>
												<?php foreach ($tax_profiles as $profile) { ?>
													<option value="<?php echo htmlspecialchars($profile['tax_code']); ?>"><?php echo htmlspecialchars($profile['tax_code'] . ' - ' . $profile['tax_name']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>

								<div class="row filter-row btn-row">
									<div class="col-md-12">
										<button type="button" class="btn btn-primary btn1" id="search" onclick="if(window.loadGstTaxReport){window.loadGstTaxReport();}"><i class="fa fa-search"></i> Search</button>
										<button type="button" class="btn-export-pdf" id="exportPdf"><i class="fa fa-file-pdf-o"></i> Download PDF</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-md-offset-1 col-md-10 col-sm-12" id="table_div" style="display:none;margin-bottom:20px;">
					<div class="widget-container fluid-height clearfix" style="margin-bottom:50px;">
						<div class="heading">
							<i class="fa fa-table"></i>
							<span class="report-count" style="color:#fff;font-weight:bold;" id="report_count"></span>
							GST Tax Report Data
						</div>
						<div class="widget-content padded clearfix new_dept">
							<div class="table-scroll-wrapper" id="report"></div>
							<div id="summary_box" class="summary-box" style="display:none;"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php require_once('include/footer.php'); ?>
	</div>

	<script type="text/javascript">
	function updateGstSelectedCount() {
		var total = $('#gst_report_table tbody .row-check').length;
		var checked = $('#gst_report_table tbody .row-check:checked').length;
		var $all = $('#select_all_rows_th');
		if ($all.length) {
			$all.prop('checked', total > 0 && checked === total);
			$all.prop('indeterminate', checked > 0 && checked < total);
		}
	}

	window.getSelectedGstGrnNos = function() {
		var nos = [];
		$('#gst_report_table tbody .row-check:checked').each(function() {
			var grn = $(this).val();
			if (grn) nos.push(grn);
		});
		return nos;
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
					aoColumnDefs: [
						{ bSortable: false, aTargets: [0], sClass: 'chk-col' }
					],
					columnDefs: [
						{ orderable: false, targets: 0, className: 'chk-col' }
					]
				});
				// Ensure checkbox header never gets sort classes/icons
				$('#gst_report_table thead th.chk-col')
					.removeClass('sorting sorting_asc sorting_desc')
					.addClass('sorting_disabled');
			}
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
			$('#gst_report_table tbody .row-check').prop('checked', checked);
			$(this).prop('indeterminate', false);
		});

		$(document).on('change', '#gst_report_table tbody .row-check', function() {
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
			window.location.href = 'gst_tax_report_pdf.php?from_date=' + encodeURIComponent(from) +
				'&to_date=' + encodeURIComponent(to) +
				'&customers=' + encodeURIComponent(getSelectedCustomers()) +
				'&gst_type=' + encodeURIComponent($('#gst_type').val() || 'all') +
				'&tax_code=' + encodeURIComponent($('#tax_code').val() || 'all') +
				'&grn_nos=' + encodeURIComponent(selected.join(','));
		});
	});
	</script>
</body>
</html>
