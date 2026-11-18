<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/vendor_master_helpers.php');

ensure_billing_tables($conn);
ew_vendor_ensure_table($conn);

$c_date = date('d-m-Y');
$default_from = date('d-m-Y', strtotime('first day of this month'));

$customers_q = mysqli_query($conn, 'SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC');
$vendors_q = mysqli_query($conn, 'SELECT vendor_id, vendor_code, vendor_name FROM vendor_master WHERE status=0 ORDER BY vendor_name ASC');

$customers = array();
if ($customers_q) {
	while ($r = mysqli_fetch_assoc($customers_q)) {
		$customers[] = array('id' => (int) $r['client_id'], 'label' => $r['client_company_name']);
	}
}
$vendors = array();
if ($vendors_q) {
	while ($r = mysqli_fetch_assoc($vendors_q)) {
		$label = trim(($r['vendor_code'] ?? '') . ' - ' . ($r['vendor_name'] ?? ''), ' -');
		$vendors[] = array('id' => (int) $r['vendor_id'], 'label' => $label);
	}
}
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
		#soa_table { min-width: 900px; }
		#soa_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
		}
		#soa_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#soa_table .num { text-align: right !important; }
		#soa_table tfoot td,
		#soa_table tr.soa-open td,
		#soa_table tr.soa-close td {
			background: #F4F7FB;
			font-weight: 700;
		}
		#soa_table tr.soa-close td {
			border-top: 2px solid var(--panel-border, #C5D3E0);
		}
		.ew-page-v2--mis-report .btn1 { margin-top: 0 !important; }
		.btn-export-excel.ew-btn-v2,
		.btn-export-pdf.ew-btn-v2 { margin-left: 8px; }
		.ew-page-v2 .report-count {
			font-size: 13px;
			font-weight: 600;
			color: #64748B;
		}
		/* Keep Party Type + Party + From + To on one row */
		.ew-page-v2--mis-report .ew-mis-filter-grid.soa-filter-grid {
			grid-template-columns: minmax(160px, 0.9fr) minmax(220px, 2fr) minmax(140px, 1fr) minmax(140px, 1fr);
			align-items: end;
		}
		.ew-page-v2--mis-report .ew-mis-filter-grid.soa-filter-grid > .ew-field {
			grid-column: auto;
			min-width: 0;
		}
		@media (max-width: 991px) {
			.ew-page-v2--mis-report .ew-mis-filter-grid.soa-filter-grid {
				grid-template-columns: 1fr 1fr;
			}
		}
		@media (max-width: 640px) {
			.ew-page-v2--mis-report .ew-mis-filter-grid.soa-filter-grid {
				grid-template-columns: 1fr;
			}
		}
		.soa-party-toggle {
			display: inline-flex;
			border: 1px solid var(--ew-border, #D8DDE5);
			border-radius: 8px;
			overflow: hidden;
			background: #fff;
			width: 100%;
		}
		.soa-party-toggle button {
			flex: 1 1 0;
			border: 0;
			background: transparent;
			padding: 8px 16px;
			font-size: 13px;
			font-weight: 600;
			color: #64748B;
			cursor: pointer;
		}
		.soa-party-toggle button.is-active {
			background: #0A1E3D;
			color: #fff;
		}
		.soa-kpi-strip {
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 10px;
			margin: 0 0 14px;
		}
		.soa-kpi {
			background: #EEF3F8;
			border: 1px solid #C5D3E0;
			border-radius: 8px;
			padding: 10px 12px;
			text-align: center;
		}
		.soa-kpi .lbl {
			display: block;
			font-size: 11px;
			color: #64748B;
			font-weight: 600;
		}
		.soa-kpi .val {
			display: block;
			margin-top: 2px;
			font-size: 14px;
			font-weight: 700;
			color: #1A2332;
		}
		.soa-kpi.is-close .val { color: #B91C1C; }
		.soa-hint {
			font-size: 12px;
			color: #64748B;
			margin: 0 0 12px;
			line-height: 1.4;
		}
		@media (max-width: 900px) {
			.soa-kpi-strip { grid-template-columns: 1fr 1fr; }
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
								<h1 class="ew-page-title">Statement of Account (Customer / Vendor)</h1>
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
										<div class="filter-form-wrap ew-form-grid ew-mis-filter-grid soa-filter-grid">
											<div class="ew-field">
												<label class="filter-label">Party Type <span class="required-star">*</span></label>
												<div class="soa-party-toggle" role="group" aria-label="Party type">
													<button type="button" class="is-active" data-party-type="customer">Customer</button>
													<button type="button" data-party-type="vendor">Vendor</button>
												</div>
												<input type="hidden" id="party_type" value="customer">
											</div>
											<div class="ew-field">
												<label class="filter-label" id="party_label">Customer <span class="required-star">*</span></label>
												<select id="party_id" class="form-control">
													<option value=""></option>
												</select>
											</div>
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
									<h3>Select a party and date range</h3>
									<p>Choose Customer or Vendor, pick one party, set the period, then press Search.</p>
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
										<input type="search" id="ew_mis_table_search" placeholder="Search particulars, ref…" autocomplete="off">
									</div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="ew_mis_edit_filters">Edit filters</button>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-export-excel" id="exportExcel">
										<span class="ew-icon-excel" aria-hidden="true"></span> Export Excel
									</button>
									<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-export-pdf" id="exportPdf">
										<i class="fa fa-file-pdf-o"></i> Export PDF
									</button>
								</div>
							</div>

							<div class="ew-card ew-erp-list" id="table_div">
								<div class="ew-table-wrap widget-content padded clearfix">
									<div id="soa_summary_wrap" style="display:none;"></div>
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
		var customers = <?php echo json_encode($customers, JSON_UNESCAPED_UNICODE); ?>;
		var vendors = <?php echo json_encode($vendors, JSON_UNESCAPED_UNICODE); ?>;
		var lastLedger = null;

		function escCell(v) {
			if (v === null || v === undefined) return '';
			return String(v)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		function fillPartySelect(type, keepId) {
			var list = type === 'vendor' ? vendors : customers;
			var $sel = $('#party_id');
			var html = '<option value=""></option>';
			$.each(list, function(i, p) {
				html += '<option value="' + p.id + '">' + escCell(p.label) + '</option>';
			});
			$sel.html(html);
			if (keepId) {
				$sel.val(String(keepId));
			} else {
				$sel.val('');
			}
			if ($.fn.select2) {
				if ($sel.data('select2')) {
					$sel.select2('destroy');
				}
				$sel.select2({
					width: '100%',
					placeholder: type === 'vendor' ? '--- Select Vendor ---' : '--- Select Customer ---',
					allowClear: true
				});
			}
			$('#party_label').html((type === 'vendor' ? 'Vendor' : 'Customer') + ' <span class="required-star">*</span>');
		}

		function setPartyType(type, resetParty) {
			type = type === 'vendor' ? 'vendor' : 'customer';
			$('#party_type').val(type);
			$('.soa-party-toggle button').removeClass('is-active');
			$('.soa-party-toggle button[data-party-type="' + type + '"]').addClass('is-active');
			fillPartySelect(type, resetParty ? '' : $('#party_id').val());
		}

		setPartyType('customer', true);

		$('.soa-party-toggle').on('click', 'button', function() {
			setPartyType($(this).data('party-type'), true);
		});

		var misView = window.EwMisReportView.wire({
			fromSel: '#from_date',
			toSel: '#to_date',
			tableSel: '#soa_table',
			searchInputSel: '#ew_mis_table_search',
			onReset: function() {
				$('#from_date').val(defaultFrom);
				$('#to_date').val(defaultTo);
				setPartyType('customer', true);
				$('.ew-mis-quick-range [data-range]').removeClass('is-active');
				$('.ew-mis-quick-range [data-range="month"]').addClass('is-active');
				$('#soa_summary_wrap').hide().empty();
				lastLedger = null;
			}
		});

		function buildPayload() {
			return {
				party_type: $('#party_type').val() || 'customer',
				party_id: $('#party_id').val() || '',
				from_date: $('#from_date').val(),
				to_date: $('#to_date').val()
			};
		}

		function partyName() {
			var t = $('#party_id option:selected').text();
			return $.trim(t || '');
		}

		function renderSummary(data) {
			var s = data.summary || {};
			var party = data.party || {};
			var label = (data.party_type === 'vendor') ? 'Payable' : 'Receivable';
			var html = '';
			html += '<div class="soa-kpi-strip">';
			html += '<div class="soa-kpi"><span class="lbl">Opening</span><span class="val">' + escCell(s.opening_balance || '0.00') + '</span></div>';
			html += '<div class="soa-kpi"><span class="lbl">Total Debit</span><span class="val">' + escCell(s.total_debit || '0.00') + '</span></div>';
			html += '<div class="soa-kpi"><span class="lbl">Total Credit</span><span class="val">' + escCell(s.total_credit || '0.00') + '</span></div>';
			html += '<div class="soa-kpi is-close"><span class="lbl">Closing (' + escCell(label) + ')</span><span class="val">' + escCell(s.closing_balance || '0.00') + '</span></div>';
			html += '</div>';
			if (data.hint) {
				html += '<p class="soa-hint">' + escCell(data.hint) + '</p>';
			}
			if (party.name) {
				html += '<p class="soa-hint" style="margin-top:-6px;"><strong>Account of:</strong> ' + escCell(party.name) + '</p>';
			}
			$('#soa_summary_wrap').html(html).css('display', 'block');
		}

		window.loadStatementOfAccount = function() {
			var payload = buildPayload();
			if (!payload.party_id) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select one ' + (payload.party_type === 'vendor' ? 'vendor' : 'customer') + '.', 'error', 5000);
				}
				return;
			}
			if (!payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Please select From Date and To Date.', 'error', 5000);
				}
				return;
			}

			$('#ew_mis_table_search').val('');
			$('#report_count').text('');
			$('#soa_summary_wrap').hide().empty();
			$('#report').html('<p class="ew-mis-empty-hint">Loading…</p>');
			misView.showResults({
				datePill: misView.datePill(),
				summary: (payload.party_type === 'vendor' ? 'Vendor' : 'Customer') + ': ' + (partyName() || '—'),
				countText: '',
				resultsTitle: 'Statement of Account'
			});

			$.getJSON('statement_of_account_data.php', payload).done(function(resp) {
				if (!resp || resp.status === 1) {
					$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">' + escCell(resp && resp.message ? resp.message : 'Error loading data.') + '</p>');
					$('#report_count').text('');
					lastLedger = null;
					return;
				}

				var data = resp.data || {};
				lastLedger = data;
				var lines = data.lines || [];
				var summary = data.summary || {};
				renderSummary(data);

				if (!lines.length) {
					$('#report').html('<p class="ew-mis-empty-hint">No ledger lines for the selected filters.</p>');
					$('#report_count').text('0 line(s)');
					return;
				}

				var html = '<table class="table table-bordered table-striped" id="soa_table"><thead><tr>';
				html += '<th>Date</th><th>Particulars</th><th>Ref. No.</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th>';
				html += '</tr></thead><tbody>';
				$.each(lines, function(i, r) {
					var cls = '';
					if (r.is_opening) cls = 'soa-open';
					if (r.is_closing) cls = 'soa-close';
					html += '<tr class="' + cls + '">';
					html += '<td>' + escCell(r.display_date) + '</td>';
					html += '<td>' + escCell(r.particulars) + '</td>';
					html += '<td>' + escCell(r.ref) + '</td>';
					html += '<td class="num">' + escCell(r.debit) + '</td>';
					html += '<td class="num">' + escCell(r.credit) + '</td>';
					html += '<td class="num">' + escCell(r.balance) + '</td>';
					html += '</tr>';
				});
				html += '</tbody><tfoot><tr>';
				html += '<td colspan="3"><strong>Period totals (' + escCell(summary.row_count || 0) + ' txn)</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.total_debit || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.total_credit || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.closing_balance || '0.00') + '</strong></td>';
				html += '</tr></tfoot></table>';

				$('#report').html(html);
				$('#report_count').text((summary.row_count || 0) + ' transaction(s)');

				if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#soa_table')[0])) {
					$('#soa_table').dataTable().fnDestroy();
				}
				if ($.fn.dataTable) {
					$('#soa_table').dataTable({
						sPaginationType: 'full_numbers',
						iDisplayLength: 50,
						aaSorting: [],
						bSort: false,
						bFilter: true,
						bInfo: true,
						sDom: 'lrtip'
					});
				}
				if (window.applyEwListLayout) {
					window.applyEwListLayout();
				}
			}).fail(function() {
				$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">Network error while loading statement.</p>');
				$('#report_count').text('');
				lastLedger = null;
			});
		};

		$('#search').on('click', function() {
			window.loadStatementOfAccount();
		});

		function exportUrl(base) {
			var payload = buildPayload();
			return base +
				'?party_type=' + encodeURIComponent(payload.party_type) +
				'&party_id=' + encodeURIComponent(payload.party_id) +
				'&from_date=' + encodeURIComponent(payload.from_date) +
				'&to_date=' + encodeURIComponent(payload.to_date);
		}

		$('#exportExcel').on('click', function() {
			var payload = buildPayload();
			if (!payload.party_id || !payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Load a statement before exporting.', 'error', 5000);
				}
				return;
			}
			window.location.href = exportUrl('statement_of_account_export.php');
		});

		$('#exportPdf').on('click', function() {
			var payload = buildPayload();
			if (!payload.party_id || !payload.from_date || !payload.to_date) {
				if (typeof ewFormToast === 'function') {
					ewFormToast('Load a statement before exporting.', 'error', 5000);
				}
				return;
			}
			window.open(exportUrl('statement_of_account_pdf.php') + '&download=1', '_blank');
		});
	});
	</script>
</body>
</html>
