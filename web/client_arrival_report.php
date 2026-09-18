<?php
require_once('include/connect.php');
require_once('include/function.php');

$c_date = date('d-m-Y');
$c_mY = date('m-Y');
$page_title = 'My Arrival Report';
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
								<h1 class="ew-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-card-toolbar">
								<h2>Report Filters</h2>
							</div>
							<div class="ew-form-body">
								<form class="form-horizontal" id="transaction_form">
									<input type="hidden" id="cmd" name="cmd" value="get_arrival_report_details">
									<div id="response" class="alert alert-danger" style="display:none;">
										<div class="message" style="text-align:center"></div>
									</div>

									<div class="ew-mis-filter-shell">
									<div class="ew-form-grid ew-mis-filter-grid">
										<div class="ew-field ew-mis-period-field">
											<div class="ew-mis-period-row">
												<div class="report-type-group">
													<label class="control-label"><input type="radio" name="report_type" class="report_type" value="DAILY" checked /> Daily</label>
													<label class="control-label"><input type="radio" class="report_type" name="report_type" value="MONTHLY" /> Monthly</label>
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

										<div class="ew-field">
											<label>Mode</label>
											<select name="mode_of_trasport" id="mode_of_trasport" class="form-control">
												<option value="">Mode of Transport</option>
												<?php
												$transport_query = 'select * from mode_of_transportation where status=0';
												$transport_result = mysqli_query($conn, $transport_query);
												while ($transport_row = mysqli_fetch_array($transport_result)) {
												?>
													<option value="<?php echo $transport_row['mode_id']; ?>"><?php echo htmlspecialchars($transport_row['mode_type']); ?></option>
												<?php } ?>
											</select>
										</div>
										<div class="ew-field">
											<label>Origin</label>
											<select name="origin" id="origin" class="form-control">
												<option value="">Select Origin</option>
												<?php
												$city_query = 'select * from city where status=0 order by city_name';
												$city_result = mysqli_query($conn, $city_query);
												while ($city_row = mysqli_fetch_array($city_result)) {
												?>
													<option value="<?php echo $city_row['city_id']; ?>"><?php echo htmlspecialchars($city_row['city_name']); ?></option>
												<?php } ?>
											</select>
										</div>
										<div class="ew-field">
											<label>Destination</label>
											<select name="destination" id="destination" class="form-control">
												<option value="">Select Destination</option>
												<?php
												$city_result = mysqli_query($conn, $city_query);
												while ($city_row = mysqli_fetch_array($city_result)) {
												?>
													<option value="<?php echo $city_row['city_id']; ?>"><?php echo htmlspecialchars($city_row['city_name']); ?></option>
												<?php } ?>
											</select>
										</div>
										<div class="ew-field">
											<label>Status</label>
											<select name="status" id="status" class="form-control">
												<option value="">Select Status</option>
												<option value="0">Pending</option>
												<option value="1">Picked Up</option>
												<option value="2">Cancelled</option>
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
								<h2>Report Data</h2>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th>S.No</th>
											<th>GRN NO</th>
											<th>GRN Date</th>
											<th>Invoice No.</th>
											<th>No.of.Pkgs</th>
											<th>Weight</th>
											<th>Mode</th>
											<th>Origin</th>
											<th>Consignor</th>
											<th>Consignee</th>
											<th>Destination</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody id="arrival_report_body"></tbody>
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
			$(document).on('change', '.report_type', function() {
				if ($(this).val() === 'MONTHLY') {
					$('#picker1').hide();
					$('#picker2').show();
				} else {
					$('#picker2').hide();
					$('#picker1').show();
				}
			});

			$(document).on('click', '#search', function() {
				if (!$('#transaction_form').valid()) {
					return;
				}
				$('#table_div').show();
				$.ajax({
					url: 'fetch_details.php',
					type: 'GET',
					data: $('#transaction_form').serialize(),
					success: function(result) {
						if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#dataTable1')[0])) {
							$('#dataTable1').dataTable().fnDestroy();
						}
						$('#arrival_report_body').html(result);
						if ($.fn.dataTable) {
							$('#dataTable1').dataTable({
								sPaginationType: 'full_numbers',
								aoColumnDefs: [{ bSortable: false, aTargets: [0] }]
							});
						}
						window.setTimeout(function() {
							if (window.applyEwListLayout) {
								window.applyEwListLayout();
							}
						}, 250);
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
