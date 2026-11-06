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
		.ew-page-v2--mis-report.ew-page-v2--pending-pay .ew-card > .ew-form-body:last-child {
			padding-bottom: 20px;
		}
		.ew-page-v2--pending-pay .ew-mis-filter-footer {
			padding-top: 14px;
			margin: 4px 0 0;
		}
		.ew-page-v2--pending-pay .ew-field-hint {
			margin: 0 0 14px;
			max-width: 920px;
		}
		.ew-page-v2--pending-pay .ew-mis-filter-grid {
			grid-template-columns: minmax(150px, 1fr) minmax(150px, 1fr) minmax(220px, 1.5fr) auto;
			align-items: end !important;
			gap: 14px 16px;
		}
		.ew-page-v2--pending-pay .ew-mis-filter-grid > .ew-field {
			display: flex;
			flex-direction: column;
			justify-content: flex-end;
			min-width: 0;
		}
		.ew-page-v2--pending-pay .ew-mis-filter-grid > .ew-field > label {
			flex: 0 0 auto;
			min-height: 20px;
			line-height: 20px;
			margin-bottom: 6px !important;
		}
		.ew-page-v2--pending-pay .ew-field--filter-actions {
			min-width: 268px;
		}
		.ew-page-v2--pending-pay .ew-filter-actions-row {
			display: flex;
			align-items: stretch;
			gap: 8px;
			flex-wrap: nowrap;
			height: 40px;
		}
		.ew-page-v2--pending-pay .ew-field--filter-actions .ew-btn-v2 {
			height: 40px !important;
			min-height: 40px;
			padding-top: 0;
			padding-bottom: 0;
			justify-content: center;
			align-items: center;
			margin: 0 !important;
			white-space: nowrap;
			box-sizing: border-box;
		}
		.ew-page-v2--pending-pay .ew-mis-filter-grid .date-input-inside {
			height: 40px;
			box-sizing: border-box;
		}
		.ew-page-v2--pending-pay .ew-mis-filter-grid .date-input-inside .form-control,
		.ew-page-v2--pending-pay .ew-mis-filter-grid .ew-field .form-control,
		.ew-page-v2--pending-pay .ew-mis-filter-grid .ew-field select.form-control {
			height: 40px !important;
			min-height: 40px !important;
			box-sizing: border-box;
		}
		.ew-page-v2--pending-pay .ew-field--customer .select2-container {
			width: 100% !important;
			display: block;
		}
		.ew-page-v2--pending-pay .ew-field--customer .select2-container .select2-choice,
		.ew-page-v2--pending-pay .ew-field--customer .select2-container-multi .select2-choices {
			min-height: 40px !important;
			height: 40px !important;
			margin: 0;
			padding: 4px 8px;
			box-sizing: border-box;
			overflow: hidden;
		}
		.ew-page-v2--pending-pay .ew-field--customer .select2-container-multi .select2-choices .select2-search-field input {
			margin: 0;
			padding: 0;
			height: 30px;
			line-height: 30px;
		}
		.ew-page-v2--pending-pay .btn1 { margin-top: 0 !important; }
		.ew-page-v2--pending-pay .report-count {
			font-size: 13px;
			font-weight: 600;
			color: #64748B;
		}

		#pending_payments_table {
			min-width: 1180px;
			width: 100%;
			border-collapse: collapse;
			table-layout: fixed;
		}
		#pending_payments_table th,
		#pending_payments_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 8px 8px;
			vertical-align: middle;
			box-sizing: border-box;
		}
		#pending_payments_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-weight: 700;
			text-align: left;
		}
		#pending_payments_table th.num,
		#pending_payments_table td.num {
			text-align: right;
		}
		#pending_payments_table th.col-sno { width: 56px; }
		#pending_payments_table th.col-inv { width: 108px; }
		#pending_payments_table th.col-date { width: 96px; }
		#pending_payments_table th.col-customer { width: 210px; }
		#pending_payments_table th.col-money { width: 118px; }
		#pending_payments_table th.col-aging { width: 108px; }
		#pending_payments_table td.col-customer {
			overflow: hidden;
			text-overflow: ellipsis;
		}
		#pending_payments_table tfoot tr.pending-totals-row td {
			background: #E8EEF6;
			border-top: 2px solid var(--panel-border, #C5D3E0);
			font-weight: 700;
			padding: 8px 8px;
		}
		#pending_payments_table tfoot td.total-label {
			text-align: left;
		}
		#pending_payments_table .aging-high { color: #B91C1C; font-weight: 700; }
		#pending_payments_table .aging-mid { color: #B45309; font-weight: 600; }

		@media (max-width: 991px) {
			.ew-page-v2--pending-pay .ew-mis-filter-grid {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
			.ew-page-v2--pending-pay .ew-field--customer,
			.ew-page-v2--pending-pay .ew-field--filter-actions {
				grid-column: 1 / -1;
			}
		}
		@media (max-width: 640px) {
			.ew-page-v2--pending-pay .ew-mis-filter-grid {
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
					<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--mis-report ew-page-v2--pending-pay">
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
									<p class="ew-field-hint">Bill Wise lists every final invoice in the date range, including fully paid invoices (payment received and balance). Overdue aging is days from the earliest GCN booking date to today; settled invoices show as Settled. Ledger PDF is a bank-style statement for <strong>one selected customer</strong>.</p>
									<div class="ew-form-grid ew-mis-filter-grid">
										<div class="ew-field">
											<label>From Date <span class="req">*</span></label>
											<?php echo ew_date_input(array(
												'id' => 'from_date',
												'value' => $default_from,
												'required' => true,
												'readonly' => true,
											)); ?>
										</div>
										<div class="ew-field">
											<label>To Date <span class="req">*</span></label>
											<?php echo ew_date_input(array(
												'id' => 'to_date',
												'value' => $c_date,
												'required' => true,
												'readonly' => true,
											)); ?>
										</div>
										<div class="ew-field ew-field--customer">
											<label>Customer</label>
											<select id="customers" class="form-control" multiple>
												<?php while ($cust = mysqli_fetch_assoc($customers_q)) { ?>
													<option value="<?php echo (int) $cust['client_id']; ?>"><?php echo htmlspecialchars($cust['client_company_name']); ?></option>
												<?php } ?>
											</select>
										</div>
										<div class="ew-field ew-field--filter-actions">
											<label class="ew-actions-spacer">&nbsp;</label>
											<div class="ew-filter-actions-row">
												<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn1" id="search" onclick="if(window.loadPendingPayments){window.loadPendingPayments();}">
													<i class="fa fa-search"></i> Search
												</button>
												<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_ledger_pdf" title="Select exactly one customer">
													<i class="fa fa-file-pdf-o"></i> Ledger PDF
												</button>
											</div>
										</div>
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
								<div id="report">
									<p class="ew-mis-empty-hint">Click Search above to load invoices.</p>
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
				placeholder: 'All customers (pick one for Ledger PDF)',
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

		function getSelectedCustomerIds() {
			var vals = $('#customers').val();
			if (!vals) return [];
			if ($.isArray(vals)) return vals;
			return String(vals).split(',');
		}

		function getSelectedCustomers() {
			return getSelectedCustomerIds().join(',');
		}

		function toastMsg(msg, type) {
			if (typeof ewFormToast === 'function') {
				ewFormToast(msg, type || 'error', 5000);
			} else {
				alert(msg);
			}
		}

		$('#btn_ledger_pdf').on('click', function() {
			var from = $('#from_date').val();
			var to = $('#to_date').val();
			var ids = getSelectedCustomerIds();
			if (!from || !to) {
				toastMsg('Please select From Date and To Date.');
				return;
			}
			if (ids.length !== 1) {
				toastMsg('Select exactly one customer to download the ledger statement PDF.');
				return;
			}
			var url = 'pending_payments_ledger_pdf.php'
				+ '?customer_id=' + encodeURIComponent(ids[0])
				+ '&from_date=' + encodeURIComponent(from)
				+ '&to_date=' + encodeURIComponent(to)
				+ '&download=1';
			window.open(url, '_blank');
		});

		if (window.location.hash === '#ledger') {
			$('html, body').animate({ scrollTop: $('.ew-card').first().offset().top - 80 }, 300);
			toastMsg('Select one customer, set dates, then click Ledger PDF.', 'success');
		}

		window.loadPendingPayments = function() {
			var from = $('#from_date').val();
			var to = $('#to_date').val();
			if (!from || !to) {
				toastMsg('Please select From Date and To Date.');
				return;
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

				var cols = [
					{ label: 'Inv. No', key: 'invoice_no', cls: 'col-inv' },
					{ label: 'Inv. Date', key: 'invoice_date', cls: 'col-date' },
					{ label: 'Customer', key: 'customer', cls: 'col-customer' },
					{ label: 'Inv. Value', key: 'invoice_value', cls: 'col-money num', num: 1 },
					{ label: 'Payment Received', key: 'payment_received', cls: 'col-money num', num: 1 },
					{ label: 'Credit Note', key: 'credit_note', cls: 'col-money num', num: 1 },
					{ label: 'TDS', key: 'tds', cls: 'col-money num', num: 1 },
					{ label: 'Balance Amount', key: 'balance_amount', cls: 'col-money num', num: 1 },
					{ label: 'Overdue Aging', key: 'overdue_aging', cls: 'col-aging' }
				];

				var colHead = '';
				colHead += '<th class="col-sno">S.No</th>';
				$.each(cols, function(i, c) {
					var title = '';
					if (c.key === 'overdue_aging') {
						title = ' title="Days from earliest GCN booking date to today. Booking date shown in row tooltip."';
					}
					colHead += '<th class="' + c.cls + '"' + title + '>' + c.label + '</th>';
				});

				var body = '';
				$.each(data, function(i, r) {
					body += '<tr>';
					body += '<td class="col-sno">' + escCell(r.s_no) + '</td>';
					$.each(cols, function(j, c) {
						var cls = c.cls || '';
						if (c.key === 'overdue_aging') {
							cls += (cls ? ' ' : '') + agingClass(r.overdue_aging_days);
						}
						var cell = escCell(r[c.key]);
						if (c.key === 'overdue_aging') {
							var tip = r.booking_date ? ' title="Booking date: ' + escCell(r.booking_date) + '"' : '';
							body += '<td class="' + cls + '" data-order="' + escCell(r.overdue_aging_days || 0) + '"' + tip + '>' + cell + '</td>';
						} else if (c.key === 'customer') {
							body += '<td class="' + cls + '" title="' + cell + '">' + cell + '</td>';
						} else {
							body += '<td class="' + cls + '">' + cell + '</td>';
						}
					});
					body += '</tr>';
				});

				/* One <td> per column (no colspan) so totals stay under money columns with DataTables */
				var totLabel = 'Total (' + escCell(summary.row_count || data.length) + ')';
				var foot = '<tr class="pending-totals-row">';
				foot += '<td class="total-label col-sno"><strong>' + totLabel + '</strong></td>';
				foot += '<td class="col-inv"></td>';
				foot += '<td class="col-date"></td>';
				foot += '<td class="col-customer"></td>';
				foot += '<td class="num col-money"><strong>' + escCell(summary.invoice_value || '0.00') + '</strong></td>';
				foot += '<td class="num col-money"><strong>' + escCell(summary.payment_received || '0.00') + '</strong></td>';
				foot += '<td class="num col-money"><strong>' + escCell(summary.credit_note || '0.00') + '</strong></td>';
				foot += '<td class="num col-money"><strong>' + escCell(summary.tds || '0.00') + '</strong></td>';
				foot += '<td class="num col-money"><strong>' + escCell(summary.balance_amount || '0.00') + '</strong></td>';
				foot += '<td class="col-aging"></td>';
				foot += '</tr>';

				var html = '';
				html += '<div class="table-scroll-wrapper">';
				html += '<table class="table table-bordered table-striped" id="pending_payments_table">';
				html += '<thead><tr>' + colHead + '</tr></thead>';
				html += '<tbody>' + body + '</tbody>';
				html += '<tfoot>' + foot + '</tfoot>';
				html += '</table></div>';

				$('#report').html(html);
				$('#report_count').text((summary.row_count || data.length) + ' invoice' + ((summary.row_count || data.length) === 1 ? '' : 's'));

				if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#pending_payments_table')[0])) {
					$('#pending_payments_table').dataTable().fnDestroy();
				}
				if ($.fn.dataTable) {
					$('#pending_payments_table').dataTable({
						sPaginationType: 'full_numbers',
						iDisplayLength: 25,
						aaSorting: [[9, 'desc']],
						aoColumnDefs: [
							{ bSortable: false, aTargets: [0] },
							{ sType: 'numeric', aTargets: [9] },
							{ sClass: 'num', aTargets: [4, 5, 6, 7, 8] }
						],
						bFilter: true,
						bInfo: true,
						bAutoWidth: false
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
