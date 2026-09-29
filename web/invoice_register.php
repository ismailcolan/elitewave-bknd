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
		.required-star { color: #DD111E; font-weight: bold; }
		.table-scroll-wrapper {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
		}
		#invoice_register_table { min-width: 1100px; }
		#invoice_register_table th {
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-text, #1A2332);
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
		}
		#invoice_register_table td {
			font-size: 11px;
			white-space: nowrap;
			padding: 6px 6px;
			vertical-align: middle;
		}
		#invoice_register_table .num { text-align: right; }
		#invoice_register_table tfoot td {
			background: #F4F7FB;
			border-top: 2px solid var(--panel-border, #C5D3E0);
			font-weight: 700;
		}
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
								<h1 class="ew-page-title">Invoice Register</h1>
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
											<label class="filter-label">Customer</label>
											<select id="customers" class="form-control" multiple>
												<?php while ($cust = mysqli_fetch_assoc($customers_q)) { ?>
													<option value="<?php echo (int) $cust['client_id']; ?>"><?php echo htmlspecialchars($cust['client_company_name']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="ew-mis-filter-footer">
										<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn1" id="search" onclick="if(window.loadInvoiceRegister){window.loadInvoiceRegister();}">
											<i class="fa fa-search"></i> Search
										</button>
									</div>
								</div>
							</div>
						</div>

						<div class="ew-card ew-erp-list" id="table_div" style="display:none;margin-bottom:20px;">
							<div class="ew-card-toolbar">
								<h2>Invoice Register</h2>
								<div class="ew-toolbar-right">
									<span class="report-count" id="report_count"></span>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix">
								<div class="table-scroll-wrapper" id="report">
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
				placeholder: 'All customers',
				allowClear: true,
				closeOnSelect: false
			});
		}

		window.loadInvoiceRegister = function() {
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

			$.getJSON('invoice_register_data.php', {
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
					$('#report').html('<p class="ew-mis-empty-hint">No invoices found for the selected filters.</p>');
					$('#report_count').text('0 invoices');
					return;
				}

				var cols = ['Invoice No', 'Invoice Date', 'Customer', 'Freight Amount', 'SGST', 'CGST', 'IGST', 'Other Charges / Expense', 'Invoice Value'];
				var keys = ['invoice_no', 'invoice_date', 'customer', 'freight_amount', 'sgst_amount', 'cgst_amount', 'igst_amount', 'other_charges', 'invoice_value'];
				var numKeys = { freight_amount: 1, sgst_amount: 1, cgst_amount: 1, igst_amount: 1, other_charges: 1, invoice_value: 1 };

				var html = '';
				html += '<table class="table table-bordered table-striped" id="invoice_register_table"><thead><tr>';
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
				html += '<td colspan="3" class="num"><strong>Total (' + escCell(summary.row_count || data.length) + ')</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.total_freight || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.sgst_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.cgst_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.igst_amount || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.total_other || '0.00') + '</strong></td>';
				html += '<td class="num"><strong>' + escCell(summary.grand_total || '0.00') + '</strong></td>';
				html += '</tr></tfoot></table>';

				$('#report').html('<div class="table-scroll-wrapper">' + html + '</div>');
				$('#report_count').text((summary.row_count || data.length) + ' invoice(s)');

				if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#invoice_register_table')[0])) {
					$('#invoice_register_table').dataTable().fnDestroy();
				}
				if ($.fn.dataTable) {
					$('#invoice_register_table').dataTable({
						sPaginationType: 'full_numbers',
						iDisplayLength: 25,
						aaSorting: [[1, 'asc']],
						aoColumnDefs: [
							{ bSortable: false, aTargets: [0] }
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
