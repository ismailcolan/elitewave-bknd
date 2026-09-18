<?php
require_once("include/connect.php");
require_once("include/function.php");

$c_date = date('d-m-Y');
$default_from = date('d-m-Y', strtotime('first day of this month'));

$customers_q = mysqli_query($conn, 'SELECT client_id, client_company_name FROM client ORDER BY client_company_name ASC');
$modes_q = mysqli_query($conn, 'SELECT mode_id, mode_type FROM mode_of_transportation WHERE status=0 ORDER BY mode_type ASC');
$cities_q = mysqli_query($conn, 'SELECT city_id, city_name FROM city WHERE status=0 ORDER BY city_name ASC');
$payment_q = mysqli_query($conn, 'SELECT consignment_id, consignment_mode FROM consignment_mode WHERE status=0 ORDER BY consignment_mode ASC');

$city_options = '';
if ($cities_q) {
	while ($city_row = mysqli_fetch_assoc($cities_q)) {
		$city_options .= '<option value="' . htmlspecialchars($city_row['city_id']) . '">' . htmlspecialchars($city_row['city_name']) . '</option>';
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<?php include("include/title.php"); ?>
	<?php include("include/css_js.php"); ?>
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
		.filter-form-wrap {
			width: 100%;
		}
		.filter-row { margin-bottom: 14px; }
		.filter-row .form-group {
			margin-bottom: 0;
			width: 100%;
		}
		.filter-section select.filter-select {
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
		#report_table { min-width: 2800px; }
		#report_table th {
			position: sticky;
			top: 0;
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			z-index: 10;
			padding: 8px 6px;
		}
		#report_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#report_table thead th:first-child {
			position: sticky;
			left: 0;
			z-index: 11;
		}
		#report_table tbody td:first-child {
			position: sticky;
			left: 0;
			background: #fff;
			z-index: 5;
			font-weight: 600;
		}
		#report_table tbody tr:hover td:first-child { background: #f0f4f8; }
		#report_table tbody tr:nth-child(even) td:first-child { background: #f9fafb; }
		.btn-export-excel.ew-btn-v2 {
			margin-left: 8px;
		}
		.ew-page-v2--mis-report .btn1 {
			margin-top: 0 !important;
		}
		.btn-export-excel i { margin-right: 5px; }
		.ew-page-v2 .report-count {
			font-size: 13px;
			font-weight: 600;
			color: #64748B;
		}
		.ew-page-v2 .filter-section {
			border: none;
			padding: 0 24px 24px;
		}
		.btn-row { padding-top: 24px; }
	</style>
</head>
<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once("include/header.php");
			require_once("include/menu.php");
			?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--mis-report">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Cargo Booking Report</h1>
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
											'name' => 'from_date',
											'value' => $default_from,
											'required' => true,
											'readonly' => true,
										)); ?>
								</div>
								<div class="ew-field">
										<label class="filter-label">To Date <span class="required-star">*</span></label>
										<?php echo ew_date_input(array(
											'id' => 'to_date',
											'name' => 'to_date',
											'value' => $c_date,
											'required' => true,
											'readonly' => true,
										)); ?>
								</div>
								<div class="ew-field">
										<label class="filter-label" for="customers">By Customer</label>
										<select id="customers" class="filter-select" multiple="multiple" style="width:100%;" data-placeholder="Select Customers">
											<?php
											if ($customers_q) {
												while ($cust_row = mysqli_fetch_assoc($customers_q)) {
													echo '<option value="' . htmlspecialchars($cust_row['client_id']) . '">' . htmlspecialchars($cust_row['client_company_name']) . '</option>';
												}
											}
											?>
										</select>
								</div>
								<div class="ew-field">
										<label class="filter-label" for="modes">By Mode</label>
										<select id="modes" class="filter-select" multiple="multiple" style="width:100%;" data-placeholder="Select Modes">
											<?php
											if ($modes_q) {
												while ($mode_row = mysqli_fetch_assoc($modes_q)) {
													echo '<option value="' . htmlspecialchars($mode_row['mode_id']) . '">' . htmlspecialchars($mode_row['mode_type']) . '</option>';
												}
											}
											?>
										</select>
								</div>
								<div class="ew-field">
										<label class="filter-label" for="origins">By Origin</label>
										<select id="origins" class="filter-select" multiple="multiple" style="width:100%;" data-placeholder="Select Origins">
											<?php echo $city_options; ?>
										</select>
								</div>
								<div class="ew-field">
										<label class="filter-label" for="destinations">By Destination</label>
										<select id="destinations" class="filter-select" multiple="multiple" style="width:100%;" data-placeholder="Select Destinations">
											<?php echo $city_options; ?>
										</select>
								</div>
								<div class="ew-field">
										<label class="filter-label" for="payment_modes">By Payment Mode</label>
										<select id="payment_modes" class="filter-select" multiple="multiple" style="width:100%;" data-placeholder="Select Payment Modes">
											<?php
											if ($payment_q) {
												while ($pm_row = mysqli_fetch_assoc($payment_q)) {
													echo '<option value="' . htmlspecialchars($pm_row['consignment_id']) . '">' . htmlspecialchars($pm_row['consignment_mode']) . '</option>';
												}
											}
											?>
										</select>
								</div>
							</div>
							<div class="ew-mis-filter-footer">
								<button class="ew-btn-v2 ew-btn-v2-primary btn1" type="button" id="search" onclick="if(window.loadCargoReport){window.loadCargoReport();}">
									<i class="fa fa-search"></i> Search
								</button>
								<button class="ew-btn-v2 ew-btn-v2-outline btn-export-excel" type="button" id="exportExcel">
									<i class="fa fa-file-excel-o"></i> Export Excel
								</button>
							</div>
							</div>
						</div>
						</div>

				<div class="ew-card ew-erp-list" id="table_div" style="margin-bottom:20px;">
						<div class="ew-card-toolbar">
							<h2>Cargo Booking Report Data</h2>
							<div class="ew-toolbar-right">
								<span class="report-count" id="report_count"></span>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<div class="table-scroll-wrapper" id="report">
								<p class="ew-mis-empty-hint">Click Search above to load report data.</p>
							</div>
						</div>
				</div>
					</div>
				</div>
			</div>
		</div>

		<?php require_once("include/footer.php"); ?>
	</div>

	<script type="text/javascript">
	window.loadCargoReport = function() {
		var from = $('#from_date').val();
		var to = $('#to_date').val();
		if (!from || !to) {
			ewFormToast('Please select From Date and To Date.', 'error', 5000);
			return;
		}

		function getSelectedValues(selector) {
			var vals = $(selector).val();
			if (!vals || vals.length === 0) return '';
			return vals.join(',');
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
				$('#report').html('<p style="text-align:center;padding:30px;color:red;">Error loading report data. Please try again.</p>');
				return;
			}
			if (!resp || resp.status === 1) {
				$('#report').html('<p style="text-align:center;padding:30px;color:red;">' + (resp && resp.message ? resp.message : 'Error loading report data.') + '</p>');
				return;
			}
			var data = resp.data || [];
			if (data.length === 0) {
				$('#report').html('<p style="text-align:center;padding:30px;font-size:16px;">No records found for the selected filters.</p>');
				$('#report_count').text('');
				return;
			}

			var cols = ['S.No','GR Date','GR No','Trip ID','Pkgs','Gross Wt','Chg Wt','Rate','Party Name','From','Consignee','To','Mode','Type Of Packing','Party Invoice No','Party Inv Date','Supplier Inv Value','Pymt Mode','EwayBill No','EwayBill Expiry Date','LC Number','Description of Goods','CFS','Quotation Approval','Vehicle Number','Freight Paid By','Insurance Number','Vehicle Type','Freight','DC Amt','FOV','Hamali Amt','Total Amt','GST Amt','Total'];
			var keys = ['s_no','gr_date','gr_no','trip_id','pkgs','gross_wt','chg_wt','rate','party_name','from_city','consignee','to_city','mode','type_of_packing','party_invoice_no','party_inv_date','supplier_inv_value','pymt_mode','eway_bill_no','eway_bill_expiry','lc_number','desc_of_goods','cfs','quotation_approval','vehicle_number','freight_paid_by','insurance_number','vehicle_type','freight','dc_amt','fov','hamali_amt','total_amt','gst_amt','total'];

			var html = '<table class="table table-bordered table-striped" id="report_table"><thead><tr>';
			$.each(cols, function(i, c) { html += '<th>' + c + '</th>'; });
			html += '</tr></thead><tbody>';
			$.each(data, function(i, r) {
				html += '<tr>';
				$.each(keys, function(j, k) {
					html += '<td>' + escCell(r[k]) + '</td>';
				});
				html += '</tr>';
			});
			html += '</tbody></table>';

			$('#report').html(html);
			$('#report_count').text(data.length + ' records found');

			if (window.cargoReportTable) {
				try { window.cargoReportTable.destroy(); } catch (e) {}
			}
			if ($.fn.DataTable) {
				window.cargoReportTable = $('#report_table').DataTable({
					dom: 'frtip',
					lengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
					pageLength: 25,
					scrollX: true,
					order: [[0, 'asc']]
				});
			}
		}

		var payload = {
			from_date: from,
			to_date: to,
			customers: getSelectedValues('#customers'),
			modes: getSelectedValues('#modes'),
			origins: getSelectedValues('#origins'),
			destinations: getSelectedValues('#destinations'),
			payment_modes: getSelectedValues('#payment_modes')
		};

		function loadFromFetchDetails() {
			$.ajax({
				url: 'fetch_details.php',
				type: 'GET',
				data: $.extend({ cmd: 'get_cargo_booking_report_details' }, payload),
				dataType: 'text',
				timeout: 180000,
				success: renderReport,
				error: function() {
					$('#report').html('<p style="text-align:center;padding:30px;color:red;">Error loading report data. Please try again.</p>');
				}
			});
		}

		function renderOrFallback(raw) {
			if (!raw || String(raw).replace(/^\s+|\s+$/g, '') === '') {
				loadFromFetchDetails();
				return;
			}
			try {
				JSON.parse(typeof raw === 'string' ? raw : JSON.stringify(raw));
				renderReport(raw);
			} catch (e) {
				loadFromFetchDetails();
			}
		}

		$('#report').html('<p style="text-align:center;padding:30px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading report data...</p>');
		$('#table_div').show();

		$.ajax({
			url: 'cargo_booking_report_data.php',
			type: 'POST',
			data: payload,
			dataType: 'text',
			timeout: 180000,
			success: renderOrFallback,
			error: loadFromFetchDetails
		});
	};

	$(document).ready(function() {
		function initFilterSelect(selector, placeholder) {
			try {
				var $el = $(selector);
				if (!$el.length || typeof $el.select2 !== 'function') return;
				$el.select2({
					placeholder: placeholder,
					allowClear: true,
					width: '100%'
				});
			} catch (e) {}
		}

		initFilterSelect('#customers', 'Select Customers');
		initFilterSelect('#modes', 'Select Modes');
		initFilterSelect('#origins', 'Select Origins');
		initFilterSelect('#destinations', 'Select Destinations');
		initFilterSelect('#payment_modes', 'Select Payment Modes');

		$(document).on('click', '#search', function() {
			window.loadCargoReport();
		});

		$(document).on('click', '#exportExcel', function() {
			var from = $('#from_date').val();
			var to = $('#to_date').val();
			if (!from || !to) {
				ewFormToast('Please select From Date and To Date.', 'error', 5000);
				return;
			}
			function getSelectedValues(selector) {
				var vals = $(selector).val();
				if (!vals || vals.length === 0) return '';
				return vals.join(',');
			}
			window.location.href = 'cargo_booking_report_export.php?from_date=' + encodeURIComponent(from) +
				'&to_date=' + encodeURIComponent(to) +
				'&customers=' + encodeURIComponent(getSelectedValues('#customers')) +
				'&modes=' + encodeURIComponent(getSelectedValues('#modes')) +
				'&origins=' + encodeURIComponent(getSelectedValues('#origins')) +
				'&destinations=' + encodeURIComponent(getSelectedValues('#destinations')) +
				'&payment_modes=' + encodeURIComponent(getSelectedValues('#payment_modes'));
		});
	});

	$(window).load(function() {
		$(".loading-page").hide();
	});
	</script>
</body>
</html>
