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
		.required-star { color: red; font-weight: bold; }
		.filter-section {
			padding: 24px 20px 20px;
			border: 1px solid #e9ecef;
			border-top: none;
		}
		.filter-form-wrap {
			max-width: 820px;
			margin: 0 auto;
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
			border: 1px solid #D8DDE5;
			border-radius: 8px;
			background: #fff;
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
		.filter-section .select2-container-multi .select2-choices .select2-search-choice div {
			overflow: hidden;
			text-overflow: ellipsis;
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
		.filter-section .select2-container-multi .select2-choices .select2-search-choice-close:hover:before {
			color: #c00;
		}
		.filter-section .select2-container .select2-choice {
			height: 38px;
			line-height: 36px;
			border: 1px solid #8E8D8D;
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
			background: #0A1E3D;
			color: #fff;
			font-size: 11px;
			font-weight: 600;
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
		.btn-export-excel {
			background: #1a7a3a;
			color: #fff;
			font-weight: 600;
			padding: 8px 8px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 13px;
			margin-left: 8px;
		}
.btn1{
font-weight: 600;
			padding: 8px 20px !important;
			border: none !important;
			border-radius: 4px !important;
			cursor: pointer !important;
			font-size: 13px !important;
           margin-top: 4px !important;

}
		.btn-export-excel:hover { background: #15632f; color: #fff; }
		.btn-export-excel i { margin-right: 5px; }
		.cargo-report-header {
			    background: linear-gradient(185deg, var(--ew-navy) 0%, var(--ew-navy-deep) 100%);
			color: #fff;
			padding: 16px 24px;
			border-radius: 8px 8px 0 0;
			font-size: 18px;
			font-weight: 700;
		}
		.cargo-report-header i { margin-right: 8px; }
		.report-count {
			float: right;
			font-size: 13px;
			font-weight: 400;
			color: #ccc;
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
				<div class="col-md-offset-1 col-md-10">
					<div class="widget-container fluid-height clearfix">
						<div class="cargo-report-header">
							<i class="fa fa-truck"></i> Cargo Booking Report
							
						</div>
						<div class="filter-section">
							<div class="filter-form-wrap">
            <div class="row filter-row">
								<div class="col-md-6 col-sm-6">
									<div class="form-group">
										<label class="filter-label">From Date <span class="required-star">*</span> : </label>
										<div class="input-group date date-picker" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
											<input class="form-control" type="text" id="from_date" name="from_date" value="<?php echo $default_from; ?>" readonly required>
											<span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
										</div>
									</div>
								</div>
								<div class="col-md-6 col-sm-6">
									<div class="form-group">
										<label class="filter-label">To Date <span class="required-star">*</span> :</label>
										<div class="input-group date date-picker" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
											<input class="form-control" type="text" id="to_date" name="to_date" value="<?php echo $c_date; ?>" readonly required>
											<span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
										</div>
									</div>
								</div>
							</div>

							<div class="row filter-row">
								<div class="col-md-6 col-sm-6">
									<div class="form-group">
										<label class="filter-label" for="customers">By Customer :</label>
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
								</div>
								<div class="col-md-6 col-sm-6">
									<div class="form-group">
										<label class="filter-label" for="modes">By Mode :</label>
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
								</div>
							</div>

							

							<div class="row filter-row">
								<div class="col-md-6 col-sm-6">
									<div class="form-group">
										<label class="filter-label" for="origins">By Origin :</label>
										<select id="origins" class="filter-select" multiple="multiple" style="width:100%;" data-placeholder="Select Origins">
											<?php echo $city_options; ?>
										</select>
									</div>
								</div>
								<div class="col-md-6 col-sm-6">
									<div class="form-group">
										<label class="filter-label" for="destinations">By Destination :</label>
										<select id="destinations" class="filter-select" multiple="multiple" style="width:100%;" data-placeholder="Select Destinations">
											<?php echo $city_options; ?>
										</select>
									</div>
								</div>
							</div>

							<div class="row filter-row">
								<div class="col-md-6 col-sm-6">
									<div class="form-group">
										<label class="filter-label" for="payment_modes">By Payment Mode :</label>
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
								<div class="col-md-6 col-sm-6 btn-row">
									<button class="btn btn-primary btn1" type="button" id="search" onclick="if(window.loadCargoReport){window.loadCargoReport();}">
										<i class="fa fa-search"></i> Search
									</button>
									<button class="btn-export-excel" type="button" id="exportExcel">
										<i class="fa fa-file-excel-o"></i> Export Excel
									</button>
								</div>
							</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-md-offset-1 col-md-10 col-sm-12" id="table_div" style="margin-bottom:20px;">
					<div class="widget-container fluid-height clearfix" style="margin-bottom:50px;">
						<div class="heading"><i class="fa fa-table"></i>
                                            <span class="report-count" style="color:#fff;font-weight:bold;" id="report_count"></span>
                                            Cargo Booking Report Data</div>
                                            
						<div class="widget-content padded clearfix new_dept">
							<div class="table-scroll-wrapper" id="report">
								<p style="text-align:center;padding:30px;font-size:16px;">Click Above To Search Records.</p>
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
			alert('Please select From Date and To Date.');
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
				alert('Please select From Date and To Date.');
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

		$('.date-picker').datepicker({
			changeMonth: true,
			changeYear: true,
			format: 'dd-mm-yyyy',
			autoclose: true
		});
	});

	$(window).load(function() {
		$(".loading-page").hide();
	});
	</script>
</body>
</html>
