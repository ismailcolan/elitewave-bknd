<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_general_helpers.php');

expense_general_ensure_schema($conn);

$c_date = date('d-m-Y');
$default_from = date('d-m-Y', strtotime('first day of this month'));

require_once('include/vendor_master_helpers.php');
ew_vendor_ensure_table($conn);
$vendors_q = mysqli_query($conn, 'SELECT vendor_id, vendor_code, vendor_name FROM vendor_master WHERE status=0 ORDER BY vendor_name ASC');
$categories_q = mysqli_query($conn, 'SELECT category_id, category_code, category_name FROM expense_category WHERE status=0 ORDER BY category_name ASC');
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
		.table-scroll-wrapper {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
		}
		#expense_register_table { min-width: 1300px; }
		#expense_register_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
		}
		#expense_register_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#expense_register_table .num { text-align: right !important; }
		#expense_register_table tfoot td.num { text-align: right !important; }
		#expense_register_table tfoot td {
			background: #F4F7FB;
			border-top: 2px solid var(--panel-border, #C5D3E0);
			font-weight: 700;
		}
		.ew-page-v2--mis-report .btn1 { margin-top: 0 !important; }
		.btn-export-excel.ew-btn-v2 { margin-left: 8px; }
		.ew-page-v2 .report-count {
			font-size: 13px;
			font-weight: 600;
			color: #64748B;
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
								<h1 class="ew-page-title">Expense Register</h1>
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
												<label class="filter-label">Vendor</label>
												<select id="vendors" class="form-control" multiple>
													<?php while ($vend = mysqli_fetch_assoc($vendors_q)) {
														$vlabel = trim(($vend['vendor_code'] ?? '') . ' - ' . ($vend['vendor_name'] ?? ''), ' -');
													?>
														<option value="<?php echo (int) $vend['vendor_id']; ?>"><?php echo htmlspecialchars($vlabel); ?></option>
													<?php } ?>
												</select>
											</div>
											<div class="ew-field span-2">
												<label class="filter-label">Expense Type</label>
												<select id="categories" class="form-control" multiple>
													<?php if ($categories_q) { while ($cat = mysqli_fetch_assoc($categories_q)) {
														$clabel = trim(($cat['category_code'] ?? '') . ' - ' . ($cat['category_name'] ?? ''), ' -');
													?>
														<option value="<?php echo (int) $cat['category_id']; ?>"><?php echo htmlspecialchars($clabel); ?></option>
													<?php } } ?>
												</select>
											</div>
										</div>
										<div class="ew-mis-filter-footer">
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_reset_filters">
												<i class="fa fa-undo"></i> Reset
											</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn1" id="search">
												<i class="fa fa-search"></i> Search
											</button>
										</div>
									</div>
								</div>
							</div>

							<div class="ew-card" id="ew_mis_empty_card">
								<div class="ew-mis-empty-state">
									<div class="ew-mis-empty-icon"><i class="fa fa-file-text-o"></i></div>
									<h3>Choose a date range to load expenses</h3>
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
										<input type="search" id="ew_mis_table_search" placeholder="Search expense no, vendor, type…" autocomplete="off">
									</div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_edit_filters">Edit filters</button>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-export-excel" id="exportExcel">
										<span class="ew-icon-excel" aria-hidden="true"></span> Export Excel
									</button>
								</div>
							</div>

							<div class="ew-card ew-erp-list" id="table_div">
								<div class="ew-table-wrap widget-content padded clearfix">
									<div class="table-scroll-wrapper" id="report"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>

	<script type="text/javascript">
	$(function() {
		var defaultFrom = <?php echo json_encode($default_from); ?>;
		var defaultTo = <?php echo json_encode($c_date); ?>;

		if ($.fn.select2) {
			$('#vendors').select2({
				width: '100%',
				placeholder: 'All vendors',
				allowClear: true,
				closeOnSelect: false
			});
			$('#categories').select2({
				width: '100%',
				placeholder: 'All expense types',
				allowClear: true,
				closeOnSelect: false
			});
		}

		var misView = window.EwMisReportView.wire({
			fromSel: '#from_date',
			toSel: '#to_date',
			tableSel: '#expense_register_table',
			searchInputSel: '#ew_mis_table_search',
			onReset: function() {
				$('#from_date').val(defaultFrom);
				$('#to_date').val(defaultTo);
				$('#vendors').val(null).trigger('change');
				$('#categories').val(null).trigger('change');
				$('.ew-mis-quick-range [data-range]').removeClass('is-active');
				$('.ew-mis-quick-range [data-range="month"]').addClass('is-active');
			}
		});

		function joinSelected(sel) {
			var vals = $(sel).val();
			if (!vals) return '';
			return $.isArray(vals) ? vals.join(',') : String(vals);
		}

		function buildPayload() {
			return {
				from_date: $('#from_date').val(),
				to_date: $('#to_date').val(),
				vendors: joinSelected('#vendors'),
				categories: joinSelected('#categories')
			};
		}

		function filterSummaryText(payload) {
			var parts = [];
			parts.push(payload.vendors ? 'Selected vendors' : 'All vendors');
			parts.push(payload.categories ? 'Selected expense types' : 'All expense types');
			return parts.join(', ');
		}

		function escCell(v) {
			if (v === null || v === undefined) return '';
			return String(v)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		window.loadExpenseRegister = function() {
			var payload = buildPayload();
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}

			$('#ew_mis_table_search').val('');
			$('#report_count').text('');
			$('#report').html('<p class="ew-mis-empty-hint">Loading…</p>');
			misView.showResults({
				datePill: misView.datePill(),
				summary: filterSummaryText(payload),
				countText: '',
				resultsTitle: 'Expense Register Data'
			});

			$.getJSON('expense_register_data.php', payload).done(function(resp) {
				if (!resp || resp.status === 1) {
					$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">' + escCell(resp && resp.message ? resp.message : 'Error loading data.') + '</p>');
					$('#report_count').text('');
					return;
				}

				var data = resp.data || [];
				var summary = resp.summary || {};
				if (data.length === 0) {
					$('#report').html('<p class="ew-mis-empty-hint">No expenses found for the selected filters.</p>');
					$('#report_count').text('0 expense(s)');
					return;
				}

				var cols = ['Expense No', 'Date', 'Vendor', 'Expense Type', 'Amount', 'GST', 'TDS', 'Other Deduction', 'Net Payable', 'Payment', 'Description'];
				var keys = ['expense_no', 'expense_date', 'vendor', 'category', 'expense_amount', 'gst_amount', 'tds_amount', 'other_deduction', 'net_payable', 'payment_mode', 'description'];
				var numKeys = { expense_amount: 1, gst_amount: 1, tds_amount: 1, other_deduction: 1, net_payable: 1 };

				var html = '';
				html += '<table class="table table-bordered table-striped" id="expense_register_table"><thead><tr>';
				html += '<th>S.No</th>';
				$.each(cols, function(i, c) { html += '<th>' + c + '</th>'; });
				html += '</tr></thead><tbody>';
				$.each(data, function(i, r) {
					html += '<tr>';
					html += '<td>' + escCell(r.s_no) + '</td>';
					$.each(keys, function(j, k) {
						html += '<td class="' + (numKeys[k] ? 'num' : '') + '">' + escCell(r[k]) + '</td>';
					});
					html += '</tr>';
				});
				html += '</tbody><tfoot><tr>';
				html += '<td colspan="5"><strong>Total (' + escCell(summary.row_count || data.length) + ')</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.expense_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.gst_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.tds_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.other_deduction || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.net_payable || '0.00') + '</strong></td>';
				html += '<td></td><td></td>';
				html += '</tr></tfoot></table>';

				$('#report').html(html);
				$('#report_count').text((summary.row_count || data.length) + ' expense(s)');

				if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#expense_register_table')[0])) {
					$('#expense_register_table').dataTable().fnDestroy();
				}
				if ($.fn.dataTable) {
					$('#expense_register_table').dataTable({
						sPaginationType: 'full_numbers',
						iDisplayLength: 25,
						aaSorting: [[1, 'asc']],
						aoColumnDefs: [
							{ bSortable: false, aTargets: [0] }
						],
						bFilter: true,
						bInfo: true,
						sDom: 'lrtip'
					});
				}
				if (window.applyEwListLayout) {
					window.applyEwListLayout();
				}
			}).fail(function() {
				$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">Network error while loading report.</p>');
				$('#report_count').text('');
			});
		};

		$('#search').on('click', function() {
			window.loadExpenseRegister();
		});

		$('#exportExcel').on('click', function() {
			var payload = buildPayload();
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}
			window.location.href = 'expense_register_export.php?from_date=' + encodeURIComponent(payload.from_date) +
				'&to_date=' + encodeURIComponent(payload.to_date) +
				'&vendors=' + encodeURIComponent(payload.vendors) +
				'&categories=' + encodeURIComponent(payload.categories);
		});
	});
	</script>
</body>
</html>
