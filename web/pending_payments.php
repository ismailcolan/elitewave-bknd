<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

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
		.filter-hint {
			font-size: 12px;
			color: #64748B;
			margin: 0 0 12px;
		}
		.required-star { color: #DD111E; font-weight: bold; }
		#pending_payments_table { min-width: 1200px; }
		#pending_payments_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
		}
		#pending_payments_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#pending_payments_table .num { text-align: right; }
		#pending_payments_table tfoot td {
			background: #F4F7FB;
			border-top: 2px solid var(--panel-border, #C5D3E0);
			font-weight: 700;
		}
		#pending_payments_table .aging-high { color: #B91C1C; font-weight: 700; }
		#pending_payments_table .aging-mid { color: #B45309; font-weight: 600; }
		.ew-page-v2--mis-report .btn1 { margin-top: 0 !important; }
		.ew-page-v2 .report-count { font-size: 13px; font-weight: 600; color: #64748B; }
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
								<h1 class="ew-page-title">Pending Payments</h1>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-card-toolbar">
								<h2>Report Filters</h2>
							</div>
							<div class="ew-form-body">
								<div class="ew-mis-filter-shell">
									<p class="filter-hint">Invoice date range. Lists <strong>final</strong> invoices with an outstanding balance. Overdue aging = days from earliest GCN booking date on the invoice to today.</p>
									<div class="filter-form-wrap ew-form-grid ew-mis-filter-grid">
										<div class="ew-field">
											<label class="filter-label">From Date (Inv.) <span class="required-star">*</span></label>
											<?php echo ew_date_input(array(
												'id' => 'from_date',
												'value' => $default_from,
												'required' => true,
												'readonly' => true,
											)); ?>
										</div>
										<div class="ew-field">
											<label class="filter-label">To Date (Inv.) <span class="required-star">*</span></label>
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
									</div>
									<div class="ew-mis-filter-footer">
										<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn1" id="search" onclick="if(window.loadPendingPayments){window.loadPendingPayments();}">
											<i class="fa fa-search"></i> Search
										</button>
									</div>
								</div>
							</div>
						</div>

						<div class="ew-card ew-erp-list" id="table_div" style="display:none;margin-bottom:20px;">
							<div class="ew-card-toolbar">
								<h2>Pending Payments</h2>
								<div class="ew-toolbar-right">
									<span class="report-count" id="report_count"></span>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix">
								<div class="table-scroll-wrapper" id="report">
									<p class="ew-mis-empty-hint">Click Search above to load pending invoices.</p>
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
		if ($.fn.select2) {
			$('#customers').select2({
				width: '100%',
				placeholder: 'All customers',
				allowClear: true,
				closeOnSelect: false
			});
		}

		function agingClass(days) {
			days = parseInt(days, 10) || 0;
			if (days >= 90) return 'aging-high';
			if (days >= 30) return 'aging-mid';
			return '';
		}

		window.loadPendingPayments = function() {
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
				return $.isArray(vals) ? vals.join(',') : String(vals);
			}

			function escCell(v) {
				if (v === null || v === undefined) return '';
				return String(v)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			$('#table_div').show();
			$('#report').html('<p class="ew-mis-empty-hint">Loading…</p>');

			$.getJSON('pending_payments_data.php', {
				from_date: from,
				to_date: to,
				customers: getSelectedCustomers()
			}).done(function(resp) {
				if (!resp || resp.status === 1) {
					$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">' + escCell(resp && resp.message ? resp.message : 'Error loading data.') + '</p>');
					$('#report_count').text('');
					return;
				}

				var data = resp.data || [];
				var summary = resp.summary || {};
				if (data.length === 0) {
					$('#report').html('<p class="ew-mis-empty-hint">No pending invoices found for the selected filters.</p>');
					$('#report_count').text('0 pending');
					return;
				}

				var cols = ['Inv. No', 'Inv. Date', 'Customer', 'Inv. Value', 'Payment Received', 'Credit Note', 'TDS', 'Balance Amount', 'Overdue Aging'];
				var keys = ['invoice_no', 'invoice_date', 'customer', 'invoice_value', 'payment_received', 'credit_note', 'tds', 'balance_amount', 'overdue_aging'];
				var numKeys = { invoice_value: 1, payment_received: 1, credit_note: 1, tds: 1, balance_amount: 1 };

				var html = '';
				html += '<table class="table table-bordered table-striped" id="pending_payments_table"><thead><tr>';
				html += '<th>S.No</th>';
				$.each(cols, function(i, c) {
					var title = '';
					if (c === 'Overdue Aging') {
						title = ' title="Days from earliest GCN booking date to today. Booking date shown in row tooltip."';
					}
					html += '<th' + title + '>' + c + '</th>';
				});
				html += '</tr></thead><tbody>';
				$.each(data, function(i, r) {
					html += '<tr>';
					html += '<td>' + escCell(r.s_no) + '</td>';
					$.each(keys, function(j, k) {
						var cls = numKeys[k] ? 'num' : '';
						if (k === 'overdue_aging') {
							cls = (cls ? cls + ' ' : '') + agingClass(r.overdue_aging_days);
						}
						var cell = escCell(r[k]);
						if (k === 'overdue_aging') {
							var tip = r.booking_date ? ' title="Booking date: ' + escCell(r.booking_date) + '"' : '';
							html += '<td class="' + cls + '" data-order="' + escCell(r.overdue_aging_days || 0) + '"' + tip + '>' + cell + '</td>';
						} else {
							html += '<td class="' + cls + '">' + cell + '</td>';
						}
					});
					html += '</tr>';
				});
				html += '</tbody><tfoot><tr>';
				html += '<td colspan="3" class="num"><strong>Total (' + escCell(summary.row_count || data.length) + ')</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.invoice_value || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.payment_received || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.credit_note || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.tds || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.balance_amount || '0.00') + '</strong></td>';
				html += '<td></td>';
				html += '</tr></tfoot></table>';

				$('#report').html('<div class="table-scroll-wrapper">' + html + '</div>');
				$('#report_count').text((summary.row_count || data.length) + ' pending');

				if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#pending_payments_table')[0])) {
					$('#pending_payments_table').dataTable().fnDestroy();
				}
				if ($.fn.dataTable) {
					$('#pending_payments_table').dataTable({
						sPaginationType: 'full_numbers',
						iDisplayLength: 25,
						aaSorting: [[8, 'desc']],
						aoColumnDefs: [
							{ bSortable: false, aTargets: [0] },
							{ sType: 'numeric', aTargets: [8] }
						],
						bFilter: true,
						bInfo: true
					});
				}
				setTimeout(function() {
					if (window.applyEwListLayout) {
						window.applyEwListLayout();
					}
				}, 200);
			}).fail(function() {
				$('#report').html('<p class="ew-mis-empty-hint" style="color:#b91c1c;">Network error while loading report.</p>');
				$('#report_count').text('');
			});
		};
	});
	</script>
</body>
</html>
