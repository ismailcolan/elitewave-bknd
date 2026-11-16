<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_proforma_functions.php');

ensure_billing_proforma_tables($conn);

$c_date = date('d-m-Y');
$default_from = date('d-m-Y', strtotime('first day of this month'));

$customers_q = mysqli_query($conn, 'SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC');
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
		/* Table already has its own borders — do not frame the wrapper again */
#proforma_register_table { min-width: 1400px; }
		#proforma_register_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
		}
		#proforma_register_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#proforma_register_table .num { text-align: right !important; }
		#proforma_register_table tfoot td.num { text-align: right !important; }
		#proforma_register_table tfoot td {
			background: #F4F7FB;
			border-top: 2px solid var(--panel-border, #C5D3E0);
			font-weight: 700;
		}
		.btn-export-excel.ew-btn-v2 { margin-left: 8px; }
		.btn-export-excel i { margin-right: 5px; }
		.ew-page-v2--mis-report .btn1 { margin-top: 0 !important; }
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
								<h1 class="ew-page-title">Proforma Invoice Register</h1>
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
											<div class="ew-field">
												<label class="filter-label">Status</label>
												<select id="status" class="form-control">
													<option value="final" selected>Final</option>
													<option value="draft">Draft</option>
													<option value="all">Final + Draft</option>
												</select>
											</div>
											<div class="ew-field">
												<label class="filter-label">GST Style</label>
												<select id="doc_style" class="form-control">
													<option value="">All</option>
													<option value="gst">With GST</option>
													<option value="other">Without GST</option>
												</select>
											</div>
											<div class="ew-field span-2">
												<label class="filter-label">Customer</label>
												<select id="customers" class="form-control" multiple>
													<?php while ($cust = mysqli_fetch_assoc($customers_q)) { ?>
														<option value="<?php echo (int) $cust['client_id']; ?>"><?php echo htmlspecialchars($cust['client_company_name']); ?></option>
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
									<h3>Choose a date range to load proforma invoices</h3>
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
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_edit_filters">Edit filters</button>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-export-excel" id="exportExcel">
										<span class="ew-icon-excel" aria-hidden="true"></span> Export Excel
									</button>
								</div>
							</div>

							<div class="ew-card ew-erp-list" id="table_div">
								<div class="ew-card-toolbar ew-mis-results-toolbar">
									<h2 id="ew_mis_results_title">Proforma Invoice Register Data</h2>
									<div class="ew-mis-results-tools">
										<div class="ew-mis-table-search">
											<i class="fa fa-search"></i>
											<input type="search" id="ew_mis_table_search" placeholder="Search proforma no, customer…" autocomplete="off">
										</div>
										<span class="report-count" id="report_count"></span>
									</div>
								</div>
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
			$('#customers').select2({
				width: '100%',
				placeholder: 'All customers',
				allowClear: true,
				closeOnSelect: false
			});
		}

		var misView = window.EwMisReportView.wire({
			fromSel: '#from_date',
			toSel: '#to_date',
			tableSel: '#proforma_register_table',
			searchInputSel: '#ew_mis_table_search',
			onReset: function() {
				$('#from_date').val(defaultFrom);
				$('#to_date').val(defaultTo);
				$('#status').val('final');
				$('#doc_style').val('');
				$('#customers').val(null).trigger('change');
				$('.ew-mis-quick-range [data-range]').removeClass('is-active');
				$('.ew-mis-quick-range [data-range="month"]').addClass('is-active');
			}
		});

		function getSelectedCustomers() {
			var vals = $('#customers').val();
			if (!vals) return '';
			return $.isArray(vals) ? vals.join(',') : String(vals);
		}

		function buildPayload() {
			return {
				from_date: $('#from_date').val(),
				to_date: $('#to_date').val(),
				customers: getSelectedCustomers(),
				status: $('#status').val() || 'final',
				doc_style: $('#doc_style').val() || ''
			};
		}

		function filterSummaryText(payload) {
			var statusLabel = $('#status option:selected').text() || 'Final';
			var styleVal = payload.doc_style || '';
			var styleLabel = styleVal === 'gst' ? 'With GST' : (styleVal === 'other' ? 'Without GST' : 'All GST styles');
			return [statusLabel, styleLabel, payload.customers ? 'Selected customers' : 'All customers'].join(', ');
		}

		function escCell(v) {
				if (v === null || v === undefined) return '';
				return String(v)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

		window.loadProformaInvoiceRegister = function() {
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
				resultsTitle: 'Proforma Invoice Register Data'
			});

			$.getJSON('proforma_invoice_register_data.php', payload).done(function(resp) {
				if (!resp || resp.status === 1) {
					$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">' + escCell(resp && resp.message ? resp.message : 'Error loading data.') + '</p>');
					$('#report_count').text('');
					return;
				}

				var data = resp.data || [];
				var summary = resp.summary || {};
				if (data.length === 0) {
					$('#report').html('<p class="ew-mis-empty-hint">No proforma invoices found for the selected filters.</p>');
					$('#report_count').text('0 proformas');
					return;
				}

				var cols = ['Proforma No', 'Date', 'Customer', 'Status', 'GST Style', 'Billing Type', 'Freight Amount', 'SGST', 'CGST', 'IGST', 'Other Charges', 'Invoice Value', 'GCN Count', 'Linked GCNs'];
				var keys = ['proforma_no', 'invoice_date', 'customer', 'status', 'doc_style', 'billing_type', 'freight_amount', 'sgst_amount', 'cgst_amount', 'igst_amount', 'other_charges', 'invoice_value', 'gcn_count', 'gcn_list'];
				var numKeys = { freight_amount: 1, sgst_amount: 1, cgst_amount: 1, igst_amount: 1, other_charges: 1, invoice_value: 1, gcn_count: 1 };

				var html = '';
				html += '<table class="table table-bordered table-striped" id="proforma_register_table"><thead><tr>';
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
				html += '<td colspan="7"><strong>Total (' + escCell(summary.row_count || data.length) + ')</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.total_freight || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.sgst_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.cgst_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.igst_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.total_other || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.grand_total || '0.00') + '</strong></td>';
				html += '<td colspan="2"></td>';
				html += '</tr></tfoot></table>';

				$('#report').html(html);
				$('#report_count').text((summary.row_count || data.length) + ' proforma(s)');

				if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#proforma_register_table')[0])) {
					$('#proforma_register_table').dataTable().fnDestroy();
				}
				if ($.fn.dataTable) {
					$('#proforma_register_table').dataTable({
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
			window.loadProformaInvoiceRegister();
		});

		$('#exportExcel').on('click', function() {
			var payload = buildPayload();
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}
			window.location.href = 'proforma_invoice_register_export.php?from_date=' + encodeURIComponent(payload.from_date) +
				'&to_date=' + encodeURIComponent(payload.to_date) +
				'&customers=' + encodeURIComponent(payload.customers) +
				'&status=' + encodeURIComponent(payload.status) +
				'&doc_style=' + encodeURIComponent(payload.doc_style);
		});
	});
	</script>
</body>
</html>
