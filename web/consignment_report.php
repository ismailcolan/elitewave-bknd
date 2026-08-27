<?php
require_once("include/connect.php");
require_once("include/function.php");

$c_date = date('d-m-Y');
$c_mY = date('m-Y');
$c_Y = date('Y');

?>
<!DOCTYPE html>
<html>

<head>
	<?php include("include/title.php"); ?>
	<?php include("include/css_js.php"); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<link rel="stylesheet" href="./stylesheets/buttons.dataTables.min.css">
<style>
  .fa_calend{
            height: 25px;
    position: absolute;
    right: 0;
    width: 8%;
    top: 0;
    display: grid;
    justify-content: center;
    align-items: center;
    padding-top: 2px;
        }
   .cals_csss{
    width: 100%;
   }
@media only screen and (min-width: 360px) and (max-width: 640px) and (orientation: landscape) {

div#dataTable1_filter {
    display: block;
}


div#dataTable1_length {
    display: block;
}
.dataTables_filter input {
    width: 112px;
 
}
.dataTables_length {
    width: 40%;
    float: left;
    margin: 5px 0 10px;
}
table{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}
.dataTables_wrapper {
    position: relative;
    clear: both;
    zoom: 1;
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
}

.select2-container .select2-choice{
	line-height: 1 !important;
}

/* Consignment report — period filter row */
.report-period-row {
	margin-bottom: 8px;
}
.report-period-wrap {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: center;
	gap: 24px;
	padding: 4px 0 8px;
}
.report-type-group {
	display: flex;
	flex-wrap: nowrap;
	align-items: center;
	gap: 28px;
}
.report-type-group label.control-label {
	display: inline-flex !important;
	align-items: center;
	gap: 8px;
	margin: 0 !important;
	padding-top: 0 !important;
	white-space: nowrap;
	font-weight: 600;
	font-size: 12px;
	letter-spacing: 0.4px;
	text-transform: uppercase;
}
.report-type-group input[type="radio"] {
	margin: 0;
	position: relative;
	top: -1px;
}
.report-date-group {
	width: 220px;
	min-width: 220px;
	flex: 0 0 220px;
	position: relative;
}
.report-date-group .date-input-inside,
.report-date-group .input-group {
	width: 100%;
}
.report-year-select-wrap {
	width: 100%;
}
.report-year-select {
	width: 100%;
	height: 38px;
	min-height: 38px;
	border: 1px solid #D8DDE5;
	border-radius: 8px;
	padding: 6px 10px;
	background: #fff;
	font-size: 14px;
}
.widget-container.fluid-height {
	overflow: visible !important;
}
.datepicker.dropdown-menu {
	z-index: 10050 !important;
}
@media (max-width: 767px) {
	.report-period-wrap {
		flex-direction: column;
		align-items: stretch;
		gap: 14px;
	}
	.report-type-group {
		justify-content: center;
		flex-wrap: wrap;
		gap: 16px 24px;
	}
	.report-date-group {
		width: 100%;
		min-width: 0;
		flex: 1 1 auto;
	}
}

/* Filter fields — stacked label above dropdown */
#transaction_form .form-group {
	margin-bottom: 16px;
	overflow: visible;
}
#transaction_form .form-group > .control-label {
	display: block !important;
	float: none !important;
	width: 100% !important;
	padding-top: 0 !important;
	margin-bottom: 6px !important;
	line-height: 1.35 !important;
	text-align: left !important;
	position: static !important;
}
#transaction_form select.form-control {
	width: 100%;
	height: 38px;
	min-height: 38px;
	border: 1px solid #D8DDE5;
	border-radius: 8px;
	padding: 6px 10px;
	background: #fff;
}
#transaction_form .select2-container {
	display: block !important;
	width: 100% !important;
	margin-top: 0 !important;
}
#transaction_form .select2-container .select2-choice {
	height: 38px !important;
	line-height: 36px !important;
	border: 1px solid #D8DDE5 !important;
	border-radius: 8px !important;
	background: #fff !important;
	padding-left: 10px !important;
}
#transaction_form .select2-container .select2-choice > .select2-chosen {
	line-height: 36px !important;
	margin-right: 28px;
}
#transaction_form .select2-container .select2-choice .select2-arrow {
	height: 36px !important;
	width: 28px !important;
	border-left: none !important;
	background: transparent !important;
}
.widget-container.fluid-height .widget-content.padded {
	overflow: visible;
	padding-top: 18px !important;
}

</style>
</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<!-- Navigation -->
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once("include/header.php");
			require_once("include/menu.php");

			?>

		</div>
		<div class="container-fluid main-content new_dpt_bottom">

			<div class="row">
				<div class="col-md-offset-1 col-md-10">
					<div class="widget-container fluid-height clearfix">
						<div class="heading"> <i class="fa fa-table"></i> Consignment Report </div>
						<div class="widget-content padded">
							<form class="form-horizontal" id="transaction_form">

								<input type="hidden" id="cmd" name="cmd" value="get_pickup_report_details">
								<div id="response" class="alert alert-danger" style="display:none;">
									<div class="message" style="text-align:center"></div>
								</div>

								<div class="row report-period-row">
									<div class="col-md-12">
										<div class="report-period-wrap">
											<div class="report-type-group">
												<label class="control-label"><input type="radio" name="report_type" class="report_type" value="DAILY" checked /> DAILY</label>
												<label class="control-label"><input type="radio" class="report_type" name="report_type" value="MONTHLY" /> MONTHLY</label>
												<label class="control-label"><input type="radio" class="report_type" name="report_type" value="YEARLY" /> YEARLY</label>
											</div>
											<div class="report-date-group">
												<div id="picker1" class="input-group date daily cals_csss date-picker" data-ew-skip-upgrade="1" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
													<input class="form-control" type="text" id="date" name="date" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" required><span class="input-group-addon fa_calend"><i class="fa fa-calendar"></i></span>
												</div>
												<div id="picker2" class="input-group cals_csss date monthly date-picker" data-ew-skip-upgrade="1" data-date-autoclose="true" data-date-format="mm-yyyy" style="display:none;">
													<input class="form-control" type="text" id="month" name="month" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;"><span class="input-group-addon fa_calend"><i class="fa fa-calendar"></i></span>
												</div>
												<div id="picker3" class="report-year-select-wrap" style="display:none;">
													<select id="year" name="year" class="form-control report-year-select">
														<?php
														$current_year = (int) date('Y');
														for ($yr = $current_year + 1; $yr >= $current_year - 15; $yr--) {
															$selected = ($yr === $current_year) ? ' selected' : '';
															echo '<option value="' . $yr . '"' . $selected . '>' . $yr . '</option>';
														}
														?>
													</select>
												</div>
											</div>
										</div>
									</div>
								</div><br />
								<div class="row">
									<div class="col-md-offset-2 col-md-3">
										<div class="form-group">
											<label class="control-label">Client:</label>
											<select name="client_wise_report" id="client_wise_report" class="form-control client_wise_report">
												<option value="">-- Select Client --</option>
												<?php
												$query = "select * from client";
												$result = mysqli_query($conn, $query);

												while ($row1 = mysqli_fetch_array($result)) { ?>

													<option value="<?php echo $row1['client_id']; ?>" <?php if ($row1['client_id'] == $row['consigner_id']) echo "selected"; ?>><?php echo $row1['client_company_name']; ?></option>
												<?php
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Consignee:</label>
											<select name="consignee_wise_report" id="consignee_wise_report" class="form-control consignee_wise_report">
												<option value="">-- Select Consignee --</option>
												<?php
												$query = "select * from client";
												$result = mysqli_query($conn, $query);

												while ($row1 = mysqli_fetch_array($result)) { ?>

													<option value="<?php echo $row1['client_id']; ?>" <?php if ($row1['client_id'] == $row['consigner_id']) echo "selected"; ?>><?php echo $row1['client_company_name']; ?></option>
												<?php
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<label class="control-label">Mode:</label>
											<select name="mode_of_trasport" id="mode_of_trasport" class="form-control">
												<option value="">-- Mode of Transport --</option>
												<?php
												$transport_query = "select * from mode_of_transportation where status=0";
												$transport_result = mysqli_query($conn, $transport_query);
												while ($transport_row = mysqli_fetch_array($transport_result)) {
												?>
													<option value="<?php echo $transport_row['mode_id']; ?>" <?php if ($transport_row['mode_id'] == $row['mode_of_transportation']) echo "selected"; ?>><?php echo $transport_row['mode_type']; ?></option>
												<?php
												}
												?>
											</select>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-offset-2 col-md-3">
										<div class="form-group">
											<label class="control-label"> Origin:</label>
											<select name="origin" id="origin" class="form-control">
												<option value="">-- Select Origin --</option>
												<?php
												$city_query = "select * from city where status=0 order by city_name";
												$city_result = mysqli_query($conn, $city_query);
												while ($city_row = mysqli_fetch_array($city_result)) {
												?>
													<option value="<?php echo $city_row['city_id']; ?>" <?php if ($city_row['city_id'] == $row['origin']) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
												<?php
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Destination:</label>
											<select name="destination" id="destination" class="form-control">
												<option value="">-- Select Destination --</option>
												<?php
												$city_query = "select * from city where status=0  order by city_name";
												$city_result = mysqli_query($conn, $city_query);
												while ($city_row = mysqli_fetch_array($city_result)) {
												?>
													<option value="<?php echo $city_row['city_id']; ?>" <?php if ($city_row['city_id'] == $row['destination']) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
												<?php
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<label class="control-label">Status:</label>
											<Select type="text" name="status" id="status" class="form-control">

												<option value=""> -- Select Status -- </option>
												<option value="1">Consignment Booked</option>
												<option value="2">Consignment Picked Up</option>
												<option value="3">In Transit - 1 (Consignment at Origin State)</option>
												<option value="4">In Transit - 2 (Towards Destination State)</option>
												<option value="5">In Transit - 3 (Towards Destination)</option>
												<option value="6">At Destination</option>
												<option value="7">Out for Delivery</option>
												<option value="8">Consignment Delivered Successfully</option>
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<button class="btn btn-primary" type="button" style="margin-top: 18px;" id="search">Search</button>

										</div>
									</div>

								</div>

							</form>
						</div>
					</div>
				</div>
                <div class="col-md-offset-1 col-md-10 col-sm-12" id="table_div" style="display: none; margin-bottom: 20px;">
					<div class="widget-container fluid-height clearfix" style="margin-bottom: 50px;">
						<div class="heading"> <i class="fa fa-table"></i> Consignment Report </div>
							<div class="widget-content padded clearfix new_dept" id="report">
						<!-- <table class="table table-bordered table-striped" id="dataTable1">
							<thead>
							<th class="table-title">S.No</th>
								<th class="table-title">GRN NO</th>
								<th class="table-title" width="100px">GRN Date</th>
								<th class="table-title" width="100px">Invoice No.</th>
							
								<th class="table-title">No.of.Pkgs</th>
									<th class="table-title">Weight</th>
								<th class="table-title">Mode</th>
								<th class="table-title">Origin</th>
								<th class="table-title" >Consignor </th>
								<th class="table-title" >Consignee </th>
								<th class="table-title">Destination</th>
								<th class="table-title">Status</th>      
							</thead>
							<tbody id="get_month_details">
							
							</tbody>
						</table> -->

						</div>
					</div>
				</div>
			</div>

		</div>


		<?php require_once("include/footer.php"); ?>
	</div>
	<!-- export option cdns -->
	<script src="./javascripts/jquery.dataTables1.13.7.min.js"></script>
	<script src="./javascripts/dataTables1.13.7.buttons.min.js"></script>
	<script src="./javascripts/jszip.min.js"></script>
	<script src="./javascripts/pdfmake.min.js"></script>
	<script src="./javascripts/vfs_fonts.js"></script>
	<script src="./javascripts/buttons.html5.min.js"></script>
	<script src="./javascripts/buttons.print.min.js"></script>

	<script type="text/javascript">
		$(document).ready(function() {
			$('.client_wise_report').select2({ width: '100%' });
			$('.consignee_wise_report').select2({ width: '100%' });
			if ($('#report_table').length) {
				$('#report_table').DataTable({
					dom: 'Bfrtip',
					lengthMenu: [
						[ 10, 25, 50, -1 ],
						[ '10 rows', '25 rows', '50 rows', 'Show all' ]
					],
					buttons: [
						'pageLength',
						{
							extend: 'excel',
							text: 'Export'
						}
					]
				});
			}

			$("#date").val('<?php echo $c_date; ?>');
			$("#month").val('<?php echo $c_mY; ?>');
			$("#year").val('<?php echo $c_Y; ?>');

			function initReportPicker($wrap, opts) {
				var $input = $wrap.find('input').first();
				if (!$input.length) {
					return;
				}
				if ($input.data('datepicker')) {
					$input.datepicker('destroy');
				}
				$input.datepicker(opts);
				$input.off('show.reportPicker').on('show.reportPicker', function() {
					var dp = $input.data('datepicker');
					if (dp && typeof dp.place === 'function') {
						setTimeout(function() {
							dp.place();
						}, 0);
					}
				});
			}

			function initAllReportPickers() {
				initReportPicker($('#picker1'), {
					format: 'dd-mm-yyyy',
					autoclose: true,
					todayHighlight: true
				});
				initReportPicker($('#picker2'), {
					format: 'mm-yyyy',
					minViewMode: 1,
					startView: 1,
					autoclose: true
				});
			}

			function setReportType(type) {
				$('#picker1, #picker2, #picker3').hide();
				$('#date, #month, #year').prop('required', false);
				if (type === 'MONTHLY') {
					$('#picker2').show();
					$('#month').prop('required', true);
				} else if (type === 'YEARLY') {
					$('#picker3').show();
					$('#year').prop('required', true);
				} else {
					$('#picker1').show();
					$('#date').prop('required', true);
				}
			}

			setTimeout(function() {
				initAllReportPickers();
				setReportType($('.report_type:checked').val());
			}, 0);

			$(document).on('change', '.report_type', function() {
				setReportType($(this).val());
			});

			$(document).on('click', '.report-date-group .date-field-icon, .report-date-group .fa_calend, .report-date-group .input-group-addon', function() {
				var $input = $(this).closest('[id^="picker"]').find('input').first();
				if ($input.length) {
					if (!$input.data('datepicker')) {
						initAllReportPickers();
					}
					$input.datepicker('show');
				}
			});

			$(document).on('click', '#search', function() {
				$("#report").html("");
				$("#table_div").show();
				var data = $('#transaction_form').serialize();
				if ($('#transaction_form').valid() == true) {
					$('.form-data-saving').show();
					$.ajax({
						url: 'fetch_details.php',
						type: "GET",
						data: data,
						success: function(result) {
							if ($.fn.DataTable.isDataTable('#report_table')) {
								$('#report_table').DataTable().destroy();
							}
							$('#report').html(result);
							var $firstRowCells = $('#report_table tbody tr:first td');
							if ($('#report_table').length && $firstRowCells.length === 12) {
								try {
									$("#report_table").DataTable({
										dom: 'Bfrtip',
										lengthMenu: [
											[ 10, 25, 50, 100, -1 ],
											[ '10 rows', '25 rows', '50 rows', '100 rows', 'Show all' ]
										],
										buttons: [
											'pageLength',
											{
												extend: 'excel',
												text: 'Export'
											}
										]
									});
								} catch (e) {
									if (typeof ewToast === 'function') {
										ewToast('Unable to display report table.', 'error');
									}
								}
							}
                        	$('.form-data-saving').hide();
						},
						complete: function() {
							$('.form-data-saving').hide();
						},
						error: function() {
							$('.form-data-saving').hide();
							if (typeof ewToast === 'function') {
								ewToast('Unable to load report. Please try again.', 'error');
							}
						}
					});
				}

			});
		});
		$(window).load(function() {
			$(".loading-page").hide();
		});
	</script>
	<div class="alert" id="alert-container" style="display:none;">
		<button type="button" class="close" data-dismiss="alert">x</button>
		<strong id="alert-status"></strong>
		<span id="alert-message"></span>
	</div>


	<div class="modal fade popup_close" id="myModal">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
					<h4 class="modal-title" style="color:#fff">
						Alert!
					</h4>
				</div>

				<div class="modal-body">
					<h5 text-align="center">
						Do you want to Delete This Record ?
					</h5>
					<div class="modal-footer">
						<button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button>
						<button class="btn btn-default-outline" data-dismiss="modal" type="button" id="">No</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="delete-error-popup">
		<div class="popup_overlay" id="popup_overlay"></div>
		<div class="popup" id="popup">
			<div class="popup_message">
				<h5 class="popup-title">Alert ! </h5>
				This Data Cannot Delete.Used by another record. so you can't Delete !!! <br /> &nbsp; <br />
				<button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br /> &nbsp; <br />
			</div>
			<!--<span class="popup_close" id="popup_close">X</span>-->
		</div>
	</div>

	<div class="modal fade popup_close" id="eway_popup" style="display:none">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
					<h4 class="modal-title" style="color:#fff">
						Add Attachments
					</h4>
				</div>

				<div class="modal-body" id="attachment_body">

				</div>
			</div>
		</div>
	</div>


</body>

</html>