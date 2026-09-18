<?php
require_once('include/connect.php');
require_once('include/function.php');

$c_date = date('d-m-Y');
$c_mY = date('m-Y');
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
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
								<h1 class="ew-page-title">Payment History</h1>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-card-toolbar">
								<h2>Report Filters</h2>
							</div>
							<div class="ew-form-body">
								<form class="form-horizontal" id="payment_history_transaction_form">
									<input type="hidden" id="cmd" name="cmd" value="get_payment_report_details">
									<div id="response" class="alert alert-danger" style="display:none;">
										<div class="message" style="text-align:center"></div>
									</div>

									<div class="ew-mis-filter-shell">
										<div class="ew-form-grid ew-mis-filter-grid ew-mis-filter-grid--payment">
											<div class="ew-field ew-mis-period-field">
												<div class="ew-mis-period-row">
													<div class="report-type-group">
														<label class="control-label"><input type="radio" name="report_type" class="report_type" value="NONE" /> None</label>
														<label class="control-label"><input type="radio" name="report_type" class="report_type" value="DAILY" checked /> Daily</label>
														<label class="control-label"><input type="radio" name="report_type" class="report_type" value="MONTHLY" /> Monthly</label>
													</div>
													<div class="report-date-group">
														<div id="picker1">
															<?php echo ew_date_input(array('id' => 'date', 'name' => 'date', 'required' => true, 'value' => $c_date, 'readonly' => true)); ?>
														</div>
														<div id="picker2" style="display:none;">
															<?php echo ew_month_input(array('id' => 'month', 'name' => 'month', 'value' => $c_mY)); ?>
														</div>
													</div>
												</div>
											</div>
											<div class="ew-field ew-field--client">
												<label>Client <span class="req">*</span></label>
												<select name="client_wise_report" id="client_wise_report" class="form-control client_wise_report">
													<option value="">Select Client</option>
													<?php
													$query = 'select * from client order by client_company_name';
													$result = mysqli_query($conn, $query);
													while ($row1 = mysqli_fetch_array($result)) {
													?>
														<option value="<?php echo $row1['client_id']; ?>"><?php echo htmlspecialchars($row1['client_company_name']); ?></option>
													<?php } ?>
												</select>
											</div>
										</div>
										<div class="ew-mis-filter-footer">
											<button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="search"><i class="fa fa-search"></i> Search</button>
										</div>
									</div>
								</form>
							</div>
						</div>

						<div class="ew-card ew-erp-list" id="table_div" style="display:none;">
							<div class="ew-card-toolbar">
								<h2>Payment Report Data</h2>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="payment_report_table">
									<thead>
										<tr>
											<th>S.No</th>
											<th>Payment Date</th>
											<th>GRN No</th>
											<th>Payment ID</th>
											<th>Invoice Amount</th>
											<th>Paid Amount</th>
											<th>Due Amount</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody id="payment_report_body"></tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php require_once('include/footer.php'); ?>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			if ($.fn.select2) {
				$('.client_wise_report').select2({ width: '100%', placeholder: 'Select Client', allowClear: true });
			}

			function syncPeriodPickers(type) {
				if (type === 'MONTHLY') {
					$('#picker1').hide();
					$('#picker2').show();
				} else if (type === 'DAILY') {
					$('#picker2').hide();
					$('#picker1').show();
				} else {
					$('#picker1, #picker2').hide();
				}
			}

			$(document).on('change', '.report_type', function() {
				syncPeriodPickers($(this).val());
			});

			function initPaymentReportTable() {
				var $table = $('#payment_report_table');
				if (!$table.length || !$.fn.dataTable) {
					return;
				}
				if ($.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($table[0])) {
					$table.dataTable().fnDestroy();
				}
				$table.dataTable({
					sPaginationType: 'full_numbers',
					iDisplayLength: 25,
					aoColumnDefs: [
						{ bSortable: false, aTargets: [0] }
					]
				});
				window.setTimeout(function() {
					if (window.applyEwListLayout) {
						window.applyEwListLayout();
					}
				}, 250);
			}

			$(document).on('click', '#search', function() {
				var clientId = $('#client_wise_report').val();
				if (!clientId) {
					if (typeof ewFormToast === 'function') {
						ewFormToast('Please select a client.', 'error', 4000);
					} else {
						alert('Please select a client.');
					}
					return;
				}
				if (!$('#payment_history_transaction_form').valid()) {
					return;
				}
				$('#table_div').show();
				$.ajax({
					url: 'fetch_details.php',
					type: 'GET',
					data: $('#payment_history_transaction_form').serialize(),
					success: function(result) {
						$('#payment_report_body').html(result);
						initPaymentReportTable();
					}
				});
			});
		});
		$(window).load(function() {
			$('.loading-page').hide();
		});
	</script>
</body>
</html>
