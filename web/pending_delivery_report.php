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

$status_options = array(
	'1' => 'Consignment Booked',
	'2' => 'Consignment Picked Up',
	'3' => 'In Transit - 1',
	'4' => 'In Transit - 2',
	'5' => 'In Transit - 3',
	'6' => 'At Destination',
	'7' => 'Out for Delivery',
	'partial' => 'Partially Delivered',
);
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
		.filter-form-wrap { width: 100%; }
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
		#report_table { min-width: 1600px; }
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
		.btn-export-excel.ew-btn-v2 { margin-left: 8px; }
		.ew-page-v2--mis-report .btn1 { margin-top: 0 !important; }
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
		.days-pending-high { color: #B91C1C; font-weight: 700; }
		.days-pending-mid { color: #B45309; font-weight: 600; }
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
								<h1 class="ew-page-title">Pending Delivery Report</h1>
							</div>
							<div class="ew-page-head-right">
								<span class="report-count" id="report_count"></span>
							</div>
						</div>
						<div id="ew_mis_filters_view">
						<div class="ew-card">
						<div class="ew-card-toolbar ew-mis-filters-head">
							<h2>Report Filters</h2>
							<div class="ew-mis-quick-range" aria-label="Quick date ranges">
								<button type="button" data-range="today">Today</button>
								<button type="button" data-range="week">This week</button>
								<button type="button" class="is-active" data-range="month">This month</button>
								<button type="button" data-range="last_month">Last month</button>
							</div>
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
										<label class="filter-label" for="statuses">By Status</label>
										<select id="statuses" class="filter-select" multiple="multiple" style="width:100%;" data-placeholder="All Pending Statuses">
											<?php
											foreach ($status_options as $status_val => $status_label) {
												echo '<option value="' . htmlspecialchars($status_val) . '">' . htmlspecialchars($status_label) . '</option>';
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
								<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_reset_filters">
									<i class="fa fa-undo"></i> Reset
								</button>
								<button class="ew-btn-v2 ew-btn-v2-primary btn1" type="button" id="search">
									<i class="fa fa-search"></i> Search
								</button>
							</div>
							</div>
						</div>
						</div>

						<div class="ew-card" id="ew_mis_empty_card">
							<div class="ew-mis-empty-state">
								<div class="ew-mis-empty-icon"><i class="fa fa-file-text-o"></i></div>
								<h3>Choose a date range to load pending deliveries</h3>
								<p>Use a quick range above, or set From and To dates, then press Search.</p>
							</div>
						</div>
						</div>

						<div id="ew_mis_results_view" style="display:none;">
							<div class="ew-mis-active-bar">
								<div class="ew-mis-active-left">
									<span class="ew-mis-active-label"><i class="fa fa-filter"></i> Filters</span>
									<span class="ew-mis-date-pill" id="ew_mis_date_pill"></span>
									<span class="ew-mis-active-summary" id="ew_mis_active_summary"></span>
								</div>
								<div class="ew-mis-active-actions">
									<div class="ew-mis-table-search">
										<i class="fa fa-search"></i>
										<input type="search" id="ew_mis_table_search" placeholder="Search GR no, party…" autocomplete="off">
									</div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_edit_filters">Edit filters</button>
									<button class="ew-btn-v2 ew-btn-v2-outline btn-export-excel" type="button" id="exportExcel">
										<span class="ew-icon-excel" aria-hidden="true"></span> Export Excel
									</button>
								</div>
							</div>

				<div class="ew-card ew-erp-list" id="table_div" style="margin-bottom:20px;">
						<div class="ew-table-wrap widget-content padded clearfix new_dept">
							<div class="table-scroll-wrapper" id="report"></div>
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
	$(document).ready(function() {
		var defaultFrom = <?php echo json_encode($default_from); ?>;
		var defaultTo = <?php echo json_encode($c_date); ?>;

		var misView = window.EwMisReportView.wire({
			fromSel: '#from_date',
			toSel: '#to_date',
			tableSel: '#report_table',
			searchInputSel: '#ew_mis_table_search',
			onReset: function() {
				$('#from_date').val(defaultFrom);
				$('#to_date').val(defaultTo);
				$('#customers, #statuses, #modes, #origins, #destinations, #payment_modes').val(null).trigger('change');
				$('.ew-mis-quick-range [data-range]').removeClass('is-active');
				$('.ew-mis-quick-range [data-range="month"]').addClass('is-active');
			}
		});

		function getSelectedValues(selector) {
			var vals = $(selector).val();
			if (!vals || vals.length === 0) return '';
			return vals.join(',');
		}

		function buildPayload() {
			return {
				from_date: $('#from_date').val(),
				to_date: $('#to_date').val(),
				customers: getSelectedValues('#customers'),
				statuses: getSelectedValues('#statuses'),
				modes: getSelectedValues('#modes'),
				origins: getSelectedValues('#origins'),
				destinations: getSelectedValues('#destinations'),
				payment_modes: getSelectedValues('#payment_modes')
			};
		}

		function filterSummaryText(payload) {
			return [
				payload.customers ? 'Selected customers' : 'All customers',
				payload.statuses ? 'Selected statuses' : 'All pending statuses',
				payload.modes ? 'Selected modes' : 'All modes',
				payload.origins ? 'Selected origins' : 'All origins',
				payload.destinations ? 'Selected destinations' : 'All destinations',
				payload.payment_modes ? 'Selected payment modes' : 'All payment modes'
			].join(', ');
		}

		function escCell(v) {
			if (v === null || v === undefined) return '';
			return String(v)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		function daysCell(days) {
			var n = parseInt(days, 10);
			if (isNaN(n)) return escCell(days);
			var cls = '';
			if (n >= 7) cls = 'days-pending-high';
			else if (n >= 3) cls = 'days-pending-mid';
			return '<span class="' + cls + '">' + escCell(n) + '</span>';
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
				$('#report').html('<p style="text-align:center;padding:30px;font-size:16px;">No pending deliveries found for the selected filters.</p>');
				$('#report_count').text('');
				return;
			}

			var cols = ['S.No','GR Date','GR No','Status','Days Pending','Pkgs','Delivered Pkgs','Party Name','From','Consignee','To','Mode','Pymt Mode','Vehicle Number','EwayBill No','EwayBill Expiry','Description of Goods'];
			var keys = ['s_no','gr_date','gr_no','status_label','days_pending','pkgs','delivered_pkgs','party_name','from_city','consignee','to_city','mode','pymt_mode','vehicle_number','eway_bill_no','eway_bill_expiry','desc_of_goods'];

			var html = '<table class="table table-bordered table-striped" id="report_table"><thead><tr>';
			$.each(cols, function(i, c) { html += '<th>' + c + '</th>'; });
			html += '</tr></thead><tbody>';
			$.each(data, function(i, r) {
				html += '<tr>';
				$.each(keys, function(j, k) {
					if (k === 'days_pending') {
						html += '<td>' + daysCell(r[k]) + '</td>';
					} else {
						html += '<td>' + escCell(r[k]) + '</td>';
					}
				});
				html += '</tr>';
			});
			html += '</tbody></table>';

			$('#report').html(html);
			$('#report_count').text(data.length + ' pending consignments');

			if (window.pendingDeliveryReportTable) {
				try { window.pendingDeliveryReportTable.destroy(); } catch (e) {}
			}
			if ($.fn.DataTable) {
				window.pendingDeliveryReportTable = $('#report_table').DataTable({
					dom: 'lrtip',
					lengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
					pageLength: 25,
					scrollX: true,
					order: [[4, 'desc']]
				});
			}
			if (window.applyEwListLayout) {
				window.applyEwListLayout();
			}
		}

		window.loadPendingDeliveryReport = function() {
			var payload = buildPayload();
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}

			$('#ew_mis_table_search').val('');
			$('#report').html('<p class="ew-mis-empty-hint">Loading…</p>');
			misView.showResults({
				datePill: misView.datePill(),
				summary: filterSummaryText(payload),
				countText: '',
				resultsTitle: 'Pending Delivery Report Data'
			});

			$.ajax({
				url: 'pending_delivery_report_data.php',
				type: 'POST',
				data: payload,
				dataType: 'text',
				timeout: 180000,
				success: renderReport,
				error: function() {
					$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">Network error while loading report.</p>');
					$('#report_count').text('');
				}
			});
		};

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
		initFilterSelect('#statuses', 'All Pending Statuses');
		initFilterSelect('#modes', 'Select Modes');
		initFilterSelect('#origins', 'Select Origins');
		initFilterSelect('#destinations', 'Select Destinations');
		initFilterSelect('#payment_modes', 'Select Payment Modes');

		$(document).on('click', '#search', function() {
			window.loadPendingDeliveryReport();
		});

		$(document).on('click', '#exportExcel', function() {
			var payload = buildPayload();
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}
			window.location.href = 'pending_delivery_report_export.php?from_date=' + encodeURIComponent(payload.from_date) +
				'&to_date=' + encodeURIComponent(payload.to_date) +
				'&customers=' + encodeURIComponent(payload.customers) +
				'&statuses=' + encodeURIComponent(payload.statuses) +
				'&modes=' + encodeURIComponent(payload.modes) +
				'&origins=' + encodeURIComponent(payload.origins) +
				'&destinations=' + encodeURIComponent(payload.destinations) +
				'&payment_modes=' + encodeURIComponent(payload.payment_modes);
		});
	});

	$(window).load(function() {
		$(".loading-page").hide();
	});
	</script>
</body>
</html>
