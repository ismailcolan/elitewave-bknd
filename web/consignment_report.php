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
	border: none;
}
.select2-container .select2-choice > .select2-chosen{
	line-height: 1.5 !important;
}

.select2-container .select2-choice .select2-arrow{
	border-left: none !important;
	background: none !important;
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

								<div class="row">
									<div class="col-md-offset-3 col-md-2">
										<div class="form-group">
											<label class="control-label" style="margin-right: 30px;"><input type="radio" name="report_type" class="report_type" value="DAILY" checked /> DAILY</label>
											<label class="control-label" style="margin-right: 30px;"><input type="radio" class="report_type" name="report_type" value="MONTHLY" /> MONTHLY</label>
											<label class="control-label"><input type="radio" class="report_type" name="report_type" value="YEARLY" />YEARLY</label>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">

											<div id="picker1" class="input-group date daily cals_csss date-picker" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
												<input class="form-control" type="text" id="date" name="date" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" required><span class="input-group-addon fa_calend"><i class="fa fa-calendar"></i></span>
											</div>
											<div id="picker2" class="input-group cals_csss date monthly  date-picker" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
												<input class="form-control" type="text" id="month" name="month" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" required><span class="input-group-addon fa_calend"><i class="fa fa-calendar"></i></span>
											</div>
            								<div id="picker3" class="input-group cals_csss date yearly date-picker" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
												<input class="form-control" type="text" id="year" name="year" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" required><span class="input-group-addon fa_calend"><i class="fa fa-calendar"></i></span>
											</div>
										</div>
										
									</div>
								</div><br />
								<div class="row">
									<div class="col-md-offset-2 col-md-3">
										<div class="form-group">
											<label class="control-label">Client:</label>
											<select name="client_wise_report" id="client_wise_report" class="form-control client_wise_report" style="padding: 0; border:0;">
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
											<select name="consignee_wise_report" id="consignee_wise_report" class="form-control consignee_wise_report" style="padding: 0; border:0;">
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
			$('.client_wise_report').select2();
			$('.consignee_wise_report').select2();
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

			$("#date").val('<?php echo $c_date; ?>');
			$("#month").val('<?php echo $c_mY; ?>');
			$("#year").val('<?php echo $c_Y; ?>');
			$("#picker2").hide();
			$("#picker3").hide();
			$(document).on('change', '.report_type', function() {
				if ($(this).val() == 'MONTHLY') {
					$("#picker1").hide();
					$("#picker3").hide();
					$("#picker2").show();
				} else if($(this).val() == 'YEARLY') {
					$("#picker2").hide();
					$("#picker1").hide();
					$("#picker3").show();
				} else{
					$("#picker2").hide();
					$("#picker3").hide();
					$("#picker1").show();
				}

			});
			$(document).on('click', '#search', function() {
				$("#report").html("");
				$("#table_div").show();
				var data = $('#transaction_form').serialize();
				//alert(data);
				if ($('#transaction_form').valid() == true) {
					$('.form-data-saving').show();
					$.ajax({
						url: 'fetch_details.php',
						type: "GET",
						data: data,
						success: function(result) {
							//console.log(result);
							$("#report_table").DataTable().destroy();
							$('#report').append(result);
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
                        	$('.form-data-saving').hide();
						}
					});
				}

			});

			$('.monthly').on("click", function() {
				$(this).datepicker({
					changeMonth: true,
					changeYear: true,
					format: 'mm-yyyy',
				}).datepicker('show');
			});

			$('.yearly').on("click", function() {
				$(this).datepicker({
					changeMonth: true,
					changeYear: true,
					format: 'yyyy',
				}).datepicker('show');
			});

			$('.daily').on("click", function() {
				$(this).datepicker({
					changeMonth: true,
					changeYear: true,
					format: 'dd-mm-yyyy',
				}).datepicker('show');
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