<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/day_book_functions.php');

$c_date = date('d-m-Y');
$default_from = $c_date;
$default_to = $c_date;
$voucher_types = day_book_voucher_type_options();
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
		#day_book_table { min-width: 1100px; }
		#day_book_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
		}
		#day_book_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#day_book_table .num { text-align: right !important; }
		#day_book_table tfoot td.num { text-align: right !important; }
		#day_book_table tfoot td {
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
		.ew-page-v2--mis-report .ew-mis-filter-grid.daybook-filter-grid {
			grid-template-columns: minmax(140px, 1fr) minmax(140px, 1fr) minmax(180px, 1.2fr);
			align-items: end;
		}
		@media (max-width: 640px) {
			.ew-page-v2--mis-report .ew-mis-filter-grid.daybook-filter-grid {
				grid-template-columns: 1fr;
			}
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
								<h1 class="ew-page-title">Day Book</h1>
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
										<button type="button" class="is-active" data-range="today">Today</button>
										<button type="button" data-range="week">This week</button>
										<button type="button" data-range="month">This month</button>
										<button type="button" data-range="last_month">Last month</button>
									</div>
								</div>
								<div class="ew-form-body">
									<div class="ew-mis-filter-shell">
										<div class="filter-form-wrap ew-form-grid ew-mis-filter-grid daybook-filter-grid">
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
													'value' => $default_to,
													'required' => true,
													'readonly' => true,
												)); ?>
											</div>
											<div class="ew-field">
												<label class="filter-label">Voucher Type</label>
												<select id="voucher_type" class="form-control">
													<?php foreach ($voucher_types as $key => $label) { ?>
														<option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
													<?php } ?>
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
									<div class="ew-mis-empty-icon"><i class="fa fa-book"></i></div>
									<h3>Choose a date to load the day book</h3>
									<p>Defaults to today. Pulls live invoices, receipts, notes, bills and payment vouchers.</p>
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
										<input type="search" id="ew_mis_table_search" placeholder="Search voucher, party…" autocomplete="off">
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
		var defaultTo = <?php echo json_encode($default_to); ?>;

		var misView = window.EwMisReportView.wire({
			fromSel: '#from_date',
			toSel: '#to_date',
			tableSel: '#day_book_table',
			searchInputSel: '#ew_mis_table_search',
			onReset: function() {
				$('#from_date').val(defaultFrom);
				$('#to_date').val(defaultTo);
				$('#voucher_type').val('');
				$('.ew-mis-quick-range [data-range]').removeClass('is-active');
				$('.ew-mis-quick-range [data-range="today"]').addClass('is-active');
			}
		});

		function buildPayload() {
			return {
				from_date: $('#from_date').val(),
				to_date: $('#to_date').val(),
				voucher_type: $('#voucher_type').val() || ''
			};
		}

		function filterSummaryText(payload) {
			var t = $('#voucher_type option:selected').text() || 'All types';
			return t;
		}

		function escCell(v) {
			if (v === null || v === undefined) return '';
			return String(v)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		window.loadDayBook = function() {
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
				resultsTitle: 'Day Book'
			});

			$.getJSON('day_book_data.php', payload).done(function(resp) {
				if (!resp || resp.status === 1) {
					$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">' + escCell(resp && resp.message ? resp.message : 'Error loading data.') + '</p>');
					$('#report_count').text('');
					return;
				}

				var data = resp.data || [];
				var summary = resp.summary || {};
				if (data.length === 0) {
					$('#report').html('<p class="ew-mis-empty-hint">No entries found for the selected filters.</p>');
					$('#report_count').text('0 entries');
					return;
				}

				var html = '';
				html += '<table class="table table-bordered table-striped" id="day_book_table"><thead><tr>';
				html += '<th>S.No</th><th>Date</th><th>Voucher Type</th><th>Voucher No</th><th>Party</th><th>Particulars</th>';
				html += '<th class="num">Debit</th><th class="num">Credit</th>';
				html += '</tr></thead><tbody>';
				$.each(data, function(i, r) {
					html += '<tr>';
					html += '<td>' + escCell(r.s_no) + '</td>';
					html += '<td>' + escCell(r.entry_date) + '</td>';
					html += '<td>' + escCell(r.voucher_type) + '</td>';
					html += '<td>' + escCell(r.voucher_no) + '</td>';
					html += '<td>' + escCell(r.party) + '</td>';
					html += '<td>' + escCell(r.particulars) + '</td>';
					html += '<td class="num">' + escCell(r.debit) + '</td>';
					html += '<td class="num">' + escCell(r.credit) + '</td>';
					html += '</tr>';
				});
				html += '</tbody><tfoot><tr>';
				html += '<td colspan="6"><strong>Total (' + escCell(summary.row_count || data.length) + ')</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.total_debit || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.total_credit || '0.00') + '</strong></td>';
				html += '</tr></tfoot></table>';

				$('#report').html(html);
				$('#report_count').text((summary.row_count || data.length) + ' entries');

				if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#day_book_table')[0])) {
					$('#day_book_table').dataTable().fnDestroy();
				}
				if ($.fn.dataTable) {
					$('#day_book_table').dataTable({
						sPaginationType: 'full_numbers',
						iDisplayLength: 50,
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
				$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">Network error while loading day book.</p>');
				$('#report_count').text('');
			});
		};

		$('#search').on('click', function() {
			window.loadDayBook();
		});

		$('#exportExcel').on('click', function() {
			var payload = buildPayload();
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}
			window.location.href = 'day_book_export.php?from_date=' + encodeURIComponent(payload.from_date) +
				'&to_date=' + encodeURIComponent(payload.to_date) +
				'&voucher_type=' + encodeURIComponent(payload.voucher_type);
		});
	});
	</script>
</body>
</html>
