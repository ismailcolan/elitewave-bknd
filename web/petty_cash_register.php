<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/petty_cash_register_functions.php');

petty_cash_ensure_schema($conn);

$c_date = date('d-m-Y');
$default_from = date('d-m-Y', strtotime('first day of this month'));
$entry_types = petty_cash_register_entry_type_options();
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
		#petty_cash_register_table { min-width: 1200px; }
		#petty_cash_register_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
		}
		#petty_cash_register_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#petty_cash_register_table .num { text-align: right !important; }
		#petty_cash_register_table tfoot td.num { text-align: right !important; }
		#petty_cash_register_table tfoot td {
			background: #F4F7FB;
			border-top: 2px solid var(--panel-border, #C5D3E0);
			font-weight: 700;
		}
		.pc-reg-summary {
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 10px;
			padding: 0 0 14px;
		}
		.pc-reg-summary .kpi {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 10px 12px;
		}
		.pc-reg-summary .kpi-label {
			font-size: 10px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin-bottom: 2px;
		}
		.pc-reg-summary .kpi-value {
			font-size: 15px;
			font-weight: 800;
			color: #0A1E3D;
		}
		.pc-reg-summary .kpi-receipt .kpi-value { color: #15803d; }
		.pc-reg-summary .kpi-payment .kpi-value { color: #b91c1c; }
		.pc-reg-summary .kpi-close .kpi-value { color: #1d4ed8; }
		@media (max-width: 900px) {
			.pc-reg-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		}
		@media (max-width: 520px) {
			.pc-reg-summary { grid-template-columns: 1fr; }
		}
		.ew-page-v2--mis-report .btn1 { margin-top: 0 !important; }
		.btn-export-excel.ew-btn-v2 { margin-left: 8px; }
		.ew-page-v2 .report-count {
			font-size: 13px;
			font-weight: 600;
			color: #64748B;
		}
		.ew-page-v2--mis-report .ew-mis-filter-grid.pcreg-filter-grid {
			grid-template-columns: minmax(140px, 1fr) minmax(140px, 1fr) minmax(180px, 1.2fr);
			align-items: end;
		}
		@media (max-width: 640px) {
			.ew-page-v2--mis-report .ew-mis-filter-grid.pcreg-filter-grid {
				grid-template-columns: 1fr;
			}
		}
		.badge-receipt {
			background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;
		}
		.badge-payment {
			background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;
		}
		#petty_cash_register_table .amt-in { color: #15803d; font-weight: 700; }
		#petty_cash_register_table .amt-out { color: #dc2626; font-weight: 700; }
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
								<h1 class="ew-page-title">Petty Cash Register</h1>
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
										<div class="filter-form-wrap ew-form-grid ew-mis-filter-grid pcreg-filter-grid">
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
											<div class="ew-field">
												<label class="filter-label">Entry Type</label>
												<select id="entry_type" class="form-control">
													<?php foreach ($entry_types as $key => $label) { ?>
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
									<div class="ew-mis-empty-icon"><i class="fa fa-file-text-o"></i></div>
									<h3>Choose a date range to load petty cash</h3>
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
										<input type="search" id="ew_mis_table_search" placeholder="Search voucher, particulars…" autocomplete="off">
									</div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_edit_filters">Edit filters</button>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-export-excel" id="exportExcel">
										<span class="ew-icon-excel" aria-hidden="true"></span> Export Excel
									</button>
								</div>
							</div>

							<div class="ew-card ew-erp-list" id="table_div">
								<div class="ew-table-wrap widget-content padded clearfix">
									<div id="pc_summary_wrap" style="display:none;"></div>
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
		var typeLabels = <?php echo json_encode($entry_types); ?>;

		var misView = window.EwMisReportView.wire({
			fromSel: '#from_date',
			toSel: '#to_date',
			tableSel: '#petty_cash_register_table',
			searchInputSel: '#ew_mis_table_search',
			onReset: function() {
				$('#from_date').val(defaultFrom);
				$('#to_date').val(defaultTo);
				$('#entry_type').val('');
				$('.ew-mis-quick-range [data-range]').removeClass('is-active');
				$('.ew-mis-quick-range [data-range="month"]').addClass('is-active');
			}
		});

		function buildPayload() {
			return {
				from_date: $('#from_date').val(),
				to_date: $('#to_date').val(),
				entry_type: $('#entry_type').val() || ''
			};
		}

		function filterSummaryText(payload) {
			var label = typeLabels[payload.entry_type] || 'All types';
			return label;
		}

		function escCell(v) {
			if (v === null || v === undefined) return '';
			return String(v)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		function renderSummary(summary) {
			var html = '';
			html += '<div class="pc-reg-summary">';
			html += '<div class="kpi"><div class="kpi-label">Opening Balance</div><div class="kpi-value">₹ ' + escCell(summary.opening_balance || '0.00') + '</div></div>';
			html += '<div class="kpi kpi-receipt"><div class="kpi-label">Receipts (In)</div><div class="kpi-value">₹ ' + escCell(summary.total_receipt || '0.00') + '</div></div>';
			html += '<div class="kpi kpi-payment"><div class="kpi-label">Payments (Out)</div><div class="kpi-value">₹ ' + escCell(summary.total_payment || '0.00') + '</div></div>';
			html += '<div class="kpi kpi-close"><div class="kpi-label">Closing Balance</div><div class="kpi-value">₹ ' + escCell(summary.closing_balance || '0.00') + '</div></div>';
			html += '</div>';
			$('#pc_summary_wrap').html(html).show();
		}

		window.loadPettyCashRegister = function() {
			var payload = buildPayload();
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}

			$('#ew_mis_table_search').val('');
			$('#report_count').text('');
			$('#pc_summary_wrap').hide().empty();
			$('#report').html('<p class="ew-mis-empty-hint">Loading…</p>');
			misView.showResults({
				datePill: misView.datePill(),
				summary: filterSummaryText(payload),
				countText: '',
				resultsTitle: 'Petty Cash Register Data'
			});

			$.getJSON('petty_cash_register_data.php', payload).done(function(resp) {
				if (!resp || resp.status === 1) {
					$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">' + escCell(resp && resp.message ? resp.message : 'Error loading data.') + '</p>');
					$('#report_count').text('');
					return;
				}

				var data = resp.data || [];
				var summary = resp.summary || {};
				renderSummary(summary);

				if (data.length === 0) {
					$('#report').html('<p class="ew-mis-empty-hint">No petty cash entries found for the selected filters.</p>');
					$('#report_count').text('0 entr' + ((summary.row_count || 0) === 1 ? 'y' : 'ies'));
					return;
				}

				var html = '';
				html += '<table class="table table-bordered table-striped" id="petty_cash_register_table"><thead><tr>';
				html += '<th>S.No</th><th>Voucher No</th><th>Date</th><th>Type</th><th>Particulars</th><th>Vendor</th><th>Paid To</th><th>Expense Type</th><th>Ref No</th>';
				html += '<th class="num">Amount</th><th class="num">Balance</th>';
				html += '</tr></thead><tbody>';
				$.each(data, function(i, r) {
					var badge = (r.entry_type === 'receipt') ? 'badge-receipt' : 'badge-payment';
					var isIn = r.entry_type === 'receipt';
					var amtTxt = isIn
						? ('+ ' + escCell(r.receipt || '0.00'))
						: ('− ' + escCell(r.payment || '0.00'));
					var amtCls = isIn ? 'amt-in' : 'amt-out';
					html += '<tr>';
					html += '<td>' + escCell(r.s_no) + '</td>';
					html += '<td>' + escCell(r.voucher_no) + '</td>';
					html += '<td>' + escCell(r.voucher_date) + '</td>';
					html += '<td><span class="' + badge + '">' + escCell(r.entry_type_label) + '</span></td>';
					html += '<td>' + escCell(r.particulars) + '</td>';
					html += '<td>' + escCell(r.vendor) + '</td>';
					html += '<td>' + escCell(r.paid_to) + '</td>';
					html += '<td>' + escCell(r.category) + '</td>';
					html += '<td>' + escCell(r.ref_no) + '</td>';
					html += '<td class="num"><span class="' + amtCls + '">' + amtTxt + '</span></td>';
					html += '<td class="num"><strong>' + escCell(r.balance) + '</strong></td>';
					html += '</tr>';
				});
				html += '</tbody><tfoot><tr>';
				html += '<td colspan="9"><strong>Total (' + escCell(summary.row_count || data.length) + ')</strong></td>';
				html += '<td class="num"></td>';
				html += '<td class="num"><strong>' + escCell(summary.closing_balance || '0.00') + '</strong></td>';
				html += '</tr></tfoot></table>';

				$('#report').html(html);
				var n = summary.row_count || data.length;
				$('#report_count').text(n + ' entr' + (n === 1 ? 'y' : 'ies'));

				if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#petty_cash_register_table')[0])) {
					$('#petty_cash_register_table').dataTable().fnDestroy();
				}
				if ($.fn.dataTable) {
					$('#petty_cash_register_table').dataTable({
						sPaginationType: 'full_numbers',
						iDisplayLength: 25,
						aaSorting: [[2, 'asc']],
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
			window.loadPettyCashRegister();
		});

		$('#exportExcel').on('click', function() {
			var payload = buildPayload();
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}
			window.location.href = 'petty_cash_register_export.php?from_date=' + encodeURIComponent(payload.from_date) +
				'&to_date=' + encodeURIComponent(payload.to_date) +
				'&entry_type=' + encodeURIComponent(payload.entry_type);
		});
	});
	</script>
</body>
</html>
