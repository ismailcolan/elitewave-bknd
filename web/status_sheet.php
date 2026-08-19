<?php
require_once('include/connect.php');
require_once('include/function.php');

$c_date = date('d-m-Y');
$c_mY = date('m-Y');
$c_Y = date('Y');
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		/* ── responsive fixes (original) ── */
		@media only screen and (min-width:390px) and (max-width:844px) and (orientation:landscape) {
			#transaction_form .control-label {
				text-align: left;
			}
		}

		@media (min-width:768px) and (max-width:991.98px) {
			.form-horizontal .control-label {
				text-align: left;
			}
		}

		@media (min-width:320px) and (max-width:575.98px) {
			#table_div {
				width: 100%;
				overflow-x: auto;
				overflow-y: hidden;
			}

			.status-tblee {
				margin: 0 auto;
				width: max-content !important;
				max-width: unset !important;
				clear: both;
				border-collapse: collapse;
				table-layout: fixed;
			}
		}

		/* ── Tabs ── */
		.bulk-tabs {
			display: flex;
			gap: 0;
			margin-bottom: 0;
			border-bottom: 2px solid #1a3a5c;
			flex-wrap: wrap;
		}

		.bulk-tab {
			padding: 7px 18px;
			cursor: pointer;
			font-size: 13px;
			font-weight: 600;
			border: 1px solid #ccc;
			border-bottom: none;
			background: #f5f5f5;
			color: #555;
			border-radius: 4px 4px 0 0;
			margin-right: 3px;
			transition: background .2s;
		}

		.bulk-tab.active {
			background: #1a3a5c;
			color: #fff;
			border-color: #1a3a5c;
		}

		.bulk-tab-content {
			display: none;
			border: 1px solid #ccc;
			border-top: none;
			padding: 14px;
			background: #fff;
			border-radius: 0 0 4px 4px;
		}

		.bulk-tab-content.active {
			display: block;
		}

		/* ── Single GRN ── */
		#single-grn-row {
			margin-bottom: 0;
			display: flex;
			justify-content: center;
			align-items: center;
			margin-top: 10px;
		}

		/* ── Paste textarea ── */
		#bulk_grn_textarea {
			width: 100%;
			height: 100px;
			font-family: monospace;
			font-size: 13px;
			border: 1px solid #ccc;
			border-radius: 4px;
			padding: 8px;
			resize: vertical;
		}

		.bulk-hint {
			font-size: 11px;
			color: #888;
			margin-top: 4px;
		}

		/* ── CSV upload ── */
		.csv-upload-area {
			border: 2px dashed #aac;
			border-radius: 6px;
			padding: 18px;
			text-align: center;
			background: #f8f8ff;
			cursor: pointer;
			transition: border-color .2s;
		}

		.csv-upload-area:hover {
			border-color: #1a3a5c;
		}

		.csv-upload-area input[type=file] {
			display: none;
		}

		.csv-upload-area .upload-icon {
			font-size: 28px;
			color: #1a3a5c;
		}

		.csv-upload-area p {
			margin: 6px 0 0;
			font-size: 13px;
			color: #555;
		}

		#csv_file_name {
			font-size: 12px;
			color: #2a7a2a;
			margin-top: 6px;
		}

		.template-link {
			font-size: 12px;
			color: #1a3a5c;
			text-decoration: underline;
			cursor: pointer;
		}

		/* ── Progress bar ── */
		#bulk-progress-wrap {
			display: none;
			margin-top: 10px;
		}

		#bulk-progress-bar {
			height: 14px;
			border-radius: 7px;
			background: #1a3a5c;
			width: 0%;
			transition: width .3s;
		}

		#bulk-progress-label {
			font-size: 12px;
			color: #555;
			margin-top: 3px;
			text-align: right;
		}

		/* ── Summary ── */
		.bulk-summary {
			display: none;
			margin-top: 10px;
			padding: 10px 14px;
			border-radius: 4px;
			font-size: 13px;
		}

		.bulk-summary.success {
			background: #dff0d8;
			color: #2a7a2a;
			border: 1px solid #b2dba1;
		}

		.bulk-summary.partial {
			background: #fcf8e3;
			color: #8a6d3b;
			border: 1px solid #faebcc;
		}

		#grn_failed_list {
			font-size: 12px;
			margin-top: 6px;
			color: #a94442;
		}

		/* ── Tab-4 (Select from List) specific ── */

		#tab-list .filter-bar {
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 12px 16px;
			margin-bottom: 14px;
			display: flex;
			justify-content: center;
			align-items: center;
			gap: 10px;
			margin-top: 20px;
		}

		#tab-list .filter-bar label {
			font-weight: 600;
			font-size: 12px;
			margin-bottom: 3px;
			display: block;
		}

		#tab-list .filter-bar select {
			font-size: 13px;
		}

		/* List header bar: page-size selector + export (mirrors consignment_report.php) */
		#list-toolbar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 8px;
			flex-wrap: wrap;
			gap: 12px;
			margin-top: 20px;
		}

		#list-toolbar .toolbar-left {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		#list-toolbar select {
			font-size: 13px;
			padding: 4px 8px;
		}

		#list-toolbar .btn-export {
			font-size: 13px;
		}

		#list-search-box {
			font-size: 13px;
			padding: 5px 10px;
			border: 1px solid #ccc;
			border-radius: 4px;
			min-width: 200px;
		}

		#list-result-wrap {
			overflow-x: auto;
		}

		#list_report_table {
			width: 100%;
			border-collapse: collapse;
		}

		#list_report_table thead th {
			background: #1a3a5c;
			color: #fff;
			font-size: 12px;
			padding: 8px 6px;
			white-space: nowrap;
			cursor: default;
		}

		#list_report_table tbody td {
			font-size: 12px;
			padding: 6px 6px;
			vertical-align: middle;
			border-bottom: 1px solid #eee;
		}

		#list_report_table tbody tr:nth-child(even) {
			background: #f7f9fb;
		}

		#list_report_table tbody tr:hover {
			background: #eaf3ff;
		}

		/* checkbox col */
		.cb-col {
			width: 36px;
			text-align: center !important;
		}

		#select_all_chk {
			cursor: pointer;
			width: 16px;
			height: 16px;
		}

		.row-chk {
			cursor: pointer;
			width: 16px;
			height: 16px;
		}

		/* Pagination footer (mirrors image 1 style) */
		#list-pagination-wrap {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-top: 10px;
			flex-wrap: wrap;
			gap: 8px;
		}

		#list-pagination-info {
			font-size: 12px;
			color: #666;
		}

		#list-pagination-nav {
			display: flex;
			gap: 4px;
			flex-wrap: wrap;
		}

		#list-pagination-nav button {
			font-size: 12px;
			padding: 4px 10px;
			border: 1px solid #ccc;
			background: #fff;
			border-radius: 3px;
			cursor: pointer;
		}

		#list-pagination-nav button.active {
			background: #1a3a5c;
			color: #fff;
			border-color: #1a3a5c;
		}

		#list-pagination-nav button:disabled {
			opacity: .5;
			cursor: not-allowed;
		}

		/* bulk-update bar that appears after selection */
		#bulk-update-bar {
			display: none;
			position: sticky;
			bottom: 0;
			z-index: 99;
			background: #1a3a5c;
			color: #fff;
			padding: 10px 20px;
			border-radius: 4px 4px 0 0;
			margin-top: 10px;
			align-items: center;
			gap: 14px;
			flex-wrap: wrap;
		}

		#bulk-update-bar.visible {
			display: flex;
		}

		#bulk-update-bar label {
			margin: 0;
			font-size: 13px;
			white-space: nowrap;
		}

		#bulk-update-bar select {
			font-size: 13px;
			padding: 4px 8px;
			border-radius: 3px;
			border: none;
			min-width: 220px;
		}

		#bulk-update-bar .sel-count {
			font-size: 13px;
			font-weight: 700;
			background: rgba(255, 255, 255, .15);
			padding: 3px 10px;
			border-radius: 12px;
		}

		#list-summary {
			display: none;
			margin-top: 8px;
			padding: 9px 14px;
			border-radius: 4px;
			font-size: 13px;
		}

		#list-summary.success {
			background: #dff0d8;
			color: #2a7a2a;
			border: 1px solid #b2dba1;
		}

		#list-summary.error {
			background: #f2dede;
			color: #a94442;
			border: 1px solid #ebccd1;
		}

		.fa_calend {
			display: table-cell;
			text-align: center;
			width: 1%;
			vertical-align: middle;
			padding: 0 10px;
			cursor: pointer;
			border-left: 1.5px solid var(--ew-border);
			background: #F5F6F8;
		}
	</style>
</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">

		<div class="navbar navbar-fixed-top scroll-hide">
			<?php require_once('include/header.php');
			require_once('include/menu.php'); ?>
		</div>

		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-offset-1 col-md-10">
					<div class="widget-container fluid-height clearfix">
						<div class="heading">
							<i class="fa fa-table"></i>Transaction Status Sheet
							<span class="align-right"><i class="fa fa-plus"></i><a href="status_sheet_list.php">View List</a></span>
						</div>

						<div class="widget-content padded">
							<form class="form-horizontal" id="transaction_form">

								<input type="hidden" id="form_name" name="form_name" value="change_grn_status">
								<input type="hidden" id="cmd" name="cmd" value="get_grn_for_status">
								<input type="hidden" id="active_grn" name="active_grn" value="">
								<input type="hidden" id="slno" name="slno" value="1">

								<div id="response" class="alert alert-danger" style="display:none;">
									<div class="message" style="text-align:center"></div>
								</div>

								<!-- ── Top Filter Row (Origin / Destination / Mode / Status / Remarks) ── -->
								<!-- /top filter row -->

								<br>

								<!-- ══════════════════════════════════════════
							     TABS  — 4 input modes
							══════════════════════════════════════════ -->
								<div class="row">
									<div class=" col-md-12">

										<div class="bulk-tabs">
											<div class="bulk-tab active" data-tab="single">Single GRN</div>
											<!-- <div class="bulk-tab"        data-tab="multi">Paste Multiple GRNs</div> -->
											<div class="bulk-tab" data-tab="csv">Upload CSV / Excel</div>
											<div class="bulk-tab" data-tab="list"><i class="fa fa-list"></i> Select from List</div>
										</div>

										<!-- ─── TAB 1: Single GRN ─── -->
										<div class="bulk-tab-content active" id="tab-single">
											<div class="row">
												<div class="col-md-offset-1 col-md-4">
													<div class="form-group">
														<label class="control-label col-sm-4">Origin:</label>
														<div class="col-lg-8">
															<select name="origin" id="origin" class="form-control">
																<option value="">Select Origin</option>
																<?php
																$city_query = 'select * from city where status=0 order by city_name';
																$city_result = mysqli_query($conn, $city_query);
																while ($city_row = mysqli_fetch_array($city_result)) {
																?>
																	<option value="<?php echo $city_row['city_id']; ?>"><?php echo $city_row['city_name']; ?></option>
																<?php } ?>
															</select>
														</div>
													</div>
													<div class="form-group">
														<label class="control-label col-sm-4">Destination:</label>
														<div class="col-lg-8">
															<select name="destination" id="destination" class="form-control">
																<option value="">Select Destination</option>
															</select>
														</div>
													</div>
													<div class="form-group">
														<label class="control-label col-sm-4">Mode:</label>
														<div class="col-lg-8">
															<select name="mode" id="mode" class="form-control">
																<option value="">Modeof Transport</option>
																<?php
																$transport_query = 'select * from mode_of_transportation where status=0';
																$transport_result = mysqli_query($conn, $transport_query);
																while ($transport_row = mysqli_fetch_array($transport_result)) {
																?>
																	<option value="<?php echo $transport_row['mode_id']; ?>"><?php echo $transport_row['mode_type']; ?></option>
																<?php } ?>
															</select>
														</div>
													</div>
												</div>

												<div class=" col-md-5">
													<div class="form-group">
														<label class="control-label col-sm-4">Change Status To <span style="color:red;">*</span> :</label>
														<div class="col-lg-8">
															<select name="status" id="status" required class="form-control">
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
													<div class="form-group">
														<label class="control-label col-sm-4">Remarks:</label>
														<div class="col-lg-8">
															<textarea name="remarks" id="remarks" class="form-control"></textarea>
														</div>
													</div>
												</div>
											</div>
											<div class="row" id="single-grn-row">
												<div class="col-md-5">
													<div class="form-group">
														<input class="form-control" type="text" autocomplete="off"
															placeholder="Enter GRN No" id="grn_no" name="grn_no">
													</div>
												</div>
												<div class="col-md-2">
													<button class="btn btn-primary" type="button" id="load">Load</button>
												</div>

											</div>
											<div class="col-md-12">
												<div class="text-center" id="msg" style="color:red;"></div>
											</div>
										</div>

										<!-- ─── TAB 2: Paste Multiple GRNs ─── -->
										<!-- <div class="bulk-tab-content" id="tab-multi">
										<label style="font-weight:600;font-size:13px;">Enter multiple GRN numbers (one per line or comma-separated)</label>
										<textarea id="bulk_grn_textarea" placeholder="GRN001&#10;GRN002&#10;GRN003&#10;or: GRN001, GRN002, GRN003"></textarea>
										<p class="bulk-hint"><i class="fa fa-info-circle"></i> Tip: paste directly from Excel/Notepad. Empty lines and spaces are ignored.</p>
										<button class="btn btn-primary" type="button" id="load_bulk" style="margin-top:8px;">
											<i class="fa fa-search"></i> Load All GRNs
										</button>
									</div> -->

										<!-- ─── TAB 3: CSV / Excel Upload ─── -->
										<div class="bulk-tab-content" id="tab-csv">
											<div class="csv-upload-area" id="csv_drop_area" onclick="document.getElementById('csv_file_input').click()">
												<div class="upload-icon"><i class="fa fa-file-excel-o"></i></div>
												<p><strong>Click to browse</strong> or drag &amp; drop a CSV / Excel file here</p>
												<p style="font-size:11px;color:#999;">Supported: .csv, .xlsx, .xls</p>
												<input type="file" id="csv_file_input" accept=".csv,.xlsx,.xls">
											</div>
											<div id="csv_file_name"></div>
											<div style="margin-top:10px;">
												<span class="template-link" id="download_template">
													<i class="fa fa-download"></i> Download sample CSV template
												</span>
											</div>
											<div style="margin-top:8px;font-size:12px;color:#555;">
												<strong>Column name in file:</strong> <code>grn_no</code>
												(first column is also accepted if no header row)
											</div>
											<button class="btn btn-primary" type="button" id="load_csv" style="margin-top:10px;">
												<i class="fa fa-upload"></i> Load GRNs from File
											</button>
										</div>

										<!-- ─── TAB 4: Select from List ─── -->
										<div class="bulk-tab-content" id="tab-list">

											<!-- Filter bar — single dropdown only -->
											<!-- <div class="row">
								<div class="col-md-offset-1 col-md-4">
									<div class="form-group">
										<label class="control-label col-sm-4">Origin:</label>
										<div class="col-lg-8">
											<select name="origin" id="origin" class="form-control">
												<option value="">Select Origin</option>
												<?php
												$city_query = 'select * from city where status=0 order by city_name';
												$city_result = mysqli_query($conn, $city_query);
												while ($city_row = mysqli_fetch_array($city_result)) {
												?>
													<option value="<?php echo $city_row['city_id']; ?>"><?php echo $city_row['city_name']; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Destination:</label>
										<div class="col-lg-8">
											<select name="destination" id="destination" class="form-control">
												<option value="">Select Destination</option>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4">Mode:</label>
										<div class="col-lg-8">
											<select name="mode" id="mode" class="form-control">
												<option value="">Modeof Transport</option>
												<?php
												$transport_query = 'select * from mode_of_transportation where status=0';
												$transport_result = mysqli_query($conn, $transport_query);
												while ($transport_row = mysqli_fetch_array($transport_result)) {
												?>
													<option value="<?php echo $transport_row['mode_id']; ?>"><?php echo $transport_row['mode_type']; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>

								<div class=" col-md-5">
									<div class="form-group">
										<label class="control-label col-sm-4">Change Status To <span style="color:red;">*</span> :</label>
										<div class="col-lg-8">
											<select name="status" id="status" required class="form-control">
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
									<div class="form-group">
										<label class="control-label col-sm-4">Remarks:</label>
										<div class="col-lg-8">
											<textarea name="remarks" id="remarks" class="form-control"></textarea>
										</div>
									</div>
								</div>
							</div> -->
											<div class="filter-bar">

												<div>
													<label>Filter by Current Status</label>
													<select id="list_filter_status" class="form-control">
														<option value="">-- Select Status --</option>
														<option value="1">Consignment Booked</option>
														<option value="2">Consignment Picked Up</option>
														<option value="3">In Transit - 1 (Origin State)</option>
														<option value="4">In Transit - 2 (Towards Dest. State)</option>
														<option value="5">In Transit - 3 (Towards Destination)</option>
														<option value="6">At Destination</option>
														<option value="7">Out for Delivery</option>
														<option value="8">Delivered Successfully</option>
													</select>
												</div>
												<div style="padding-top:25px;">
													<button class="btn btn-primary btn-sm" type="button" id="list_search_btn">
														<i class="fa fa-search"></i> Search
													</button>
												</div>

											</div><!-- /filter-bar -->

											<!-- Loading spinner -->
											<div id="list-loading" style="display:none;text-align:center;padding:20px;">
												<i class="fa fa-spinner fa-spin fa-2x"></i> Loading bookings...
											</div>

											<!-- Result summary -->
											<div id="list-summary"></div>

											<!-- Result table -->
											<div id="list-result-wrap" style="display:none;">

												<!-- Toolbar: page size + export + search box (mirrors consignment_report.php) -->
												<div id="list-toolbar">
													<div class="toolbar-left">
														<label style="font-size:12px;margin:0;">Show
															<select id="list_page_size">
																<option value="10">10</option>
																<option value="25">25</option>
																<option value="50">50</option>
																<option value="-1">All</option>
															</select>
															<!-- rows -->
														</label>
														<!-- <button class="btn btn-default btn-sm btn-export" type="button" id="list_export_btn">
														<i class="fa fa-download"></i> Export
													</button> -->
													</div>
													<div>
														<input type="text" id="list-search-box" placeholder="Search...">
													</div>
												</div>

												<table id="list_report_table" class="table table-bordered table-striped">
													<thead>
														<tr>
															<th class="cb-col"><input type="checkbox" id="select_all_chk" title="Select / Deselect all"></th>
															<th>S.No</th>
															<th>GRN No</th>
															<th>GRN Date</th>
															<!-- <th>Invoice No.</th> -->
															<th>Weight</th>
															<th>No. of Pkgs</th>
															<th>Mode</th>
															<th>Origin</th>
															<th>Consignor</th>
															<th>Consignee</th>
															<th>Destination</th>
															<th>Current Status</th>
														</tr>
													</thead>
													<tbody id="list_tbody"></tbody>
												</table>

												<!-- Pagination footer -->
												<div id="list-pagination-wrap">
													<div id="list-pagination-info"></div>
													<div id="list-pagination-nav"></div>
												</div>
											</div>

											<!-- Sticky bulk-update action bar -->
											<div id="bulk-update-bar">
												<span class="sel-count" id="sel_count_badge">0 selected</span>
												<label>Update selected to</label>
												<select id="bulk_new_status">
													<option value=""> -- Choose new status -- </option>
													<option value="1">Consignment Booked</option>
													<option value="2">Consignment Picked Up</option>
													<option value="3">In Transit - 1 (Consignment at Origin State)</option>
													<option value="4">In Transit - 2 (Towards Destination State)</option>
													<option value="5">In Transit - 3 (Towards Destination)</option>
													<option value="6">At Destination</option>
													<option value="7">Out for Delivery</option>
													<option value="8">Consignment Delivered Successfully</option>
												</select>
												<label>Remarks</label>
												<input type="text" id="bulk_list_remarks" placeholder="Enter remarks (optional)"
													style="font-size:13px;padding:4px 8px;border-radius:3px;border:none;min-width:200px;color:#333;">
												<button class="btn btn-success btn-sm" type="button" id="bulk_list_submit">
													<i class="fa fa-check"></i> Submit
												</button>
												<button class="btn btn-default btn-sm" type="button" id="bulk_list_clear">
													<i class="fa fa-times"></i> Clear Selection
												</button>
											</div>

										</div><!-- /tab-list -->

										<!-- Progress bar (tabs 2 & 3) -->
										<div id="bulk-progress-wrap">
											<div style="background:#e8e8e8;border-radius:7px;overflow:hidden;">
												<div id="bulk-progress-bar"></div>
											</div>
											<div id="bulk-progress-label">0 / 0 loaded</div>
										</div>

										<!-- Result summary (tabs 1-3) -->
										<div class="bulk-summary" id="bulk-summary"></div>
										<div id="grn_failed_list"></div>

									</div><!-- /col -->
								</div><!-- /row -->

								<br><br>

								<!-- Results Table (tabs 1-3) -->
								<div id="table_div" style="display:none">
									<table class="table table-bordered table-striped status-tblee">
										<thead>
											<th class="table-title" style="width:5%">S.No</th>
											<th class="table-title" style="width:7%">GRN NO</th>
											<th class="table-title" style="width:6%">GRN Date</th>
											<th class="table-title" style="width:7%">No.of.Pkgs</th>
											<th class="table-title" style="width:10%">Mode</th>
											<th class="table-title" style="width:10%">Consignor-Origin</th>
											<th class="table-title" style="width:10%">Consignee-Destination</th>
											<th class="table-title" style="width:10%">Current Status</th>
											<th class="table-title" style="width:10%">Action</th>
										</thead>
										<tbody id="tbl_data"></tbody>
									</table>
									<div class="row">
										<div class="col-md-12 form-action">
											<button class="btn btn-primary" type="button" id="save">Submit</button>
											<a class="btn btn-default-outline btn-reset" href="status_sheet.php">Cancel</a>
										</div>
									</div>
								</div>

							</form>
						</div><!-- /widget-content -->
					</div>
				</div>
			</div>
		</div><!-- /container -->
	</div>

	<?php require_once('include/footer.php'); ?>

	<!-- SheetJS for Excel parsing -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

	<script type="text/javascript">
		$(document).ready(function() {

			/* ══════════════════════════════════════════
			   TAB SWITCHING
			══════════════════════════════════════════ */
			$(document).on('click', '.bulk-tab', function() {
				$('.bulk-tab').removeClass('active');
				$('.bulk-tab-content').removeClass('active');
				$(this).addClass('active');
				$('#tab-' + $(this).data('tab')).addClass('active');

				// Hide tabs 1-3 results when switching to list tab and vice-versa
				if ($(this).data('tab') === 'list') {
					$('#table_div').hide();
					$('#bulk-progress-wrap').hide();
					$('#bulk-summary').hide();
					$('#grn_failed_list').html('');
				}
			});

			/* ══════════════════════════════════════════
			   VALIDATE STATUS (tabs 1-3)
			══════════════════════════════════════════ */
			function validateStatus() {
				if (!$('#status').val()) {
					ewToast('Please select a "Change Status To" value before loading GRNs.', 'warning');
					$('#status').focus();
					return false;
				}
				return true;
			}

			/* ══════════════════════════════════════════
			   TAB 1 — SINGLE GRN  (original logic)
			══════════════════════════════════════════ */
			$(document).on('click', '#load', function() {
				var grn_no = $.trim($('#grn_no').val());
				var data = $('#transaction_form').serialize();

				if ($('#transaction_form').valid() == true && grn_no != '') {
					$.ajax({
						url: 'fetch_details.php',
						type: 'GET',
						data: data,
						dataType: 'JSON',
						async: false,
						success: function(result) {
							console.log(result);
							if (result['status'] == 0) {
								$('#tbl_data').append(result['data']).trigger('change');
								$('#slno').val(parseInt($('#slno').val()) + 1);
								$('#grn_no').val('').focus();
								$('#table_div').show();
							} else {
								$('#msg').html(result['data']).fadeIn(2000).fadeOut(2000);
							}
						}
					});
				}
			});

			/* ══════════════════════════════════════════
			   BULK LOADER  (shared by tabs 2 & 3)
			══════════════════════════════════════════ */
			async function bulkLoad(grnList) {
				if (!validateStatus()) return;
				if (grnList.length === 0) {
					ewToast('No valid GRN numbers found.', 'warning');
					return;
				}

				$('#tbl_data').html('');
				$('#slno').val(1);
				$('#table_div').hide();
				$('#bulk-summary').hide().removeClass('success partial');
				$('#grn_failed_list').html('');

				var total = grnList.length,
					passed = 0,
					failed = [];
				$('#bulk-progress-wrap').show();
				$('#bulk-progress-bar').css('width', '0%');
				$('#bulk-progress-label').text('0 / ' + total + ' loaded');

				for (var i = 0; i < grnList.length; i++) {
					var grn = grnList[i];
					$('#grn_no').val(grn);
					var data = $('#transaction_form').serialize();
					$('#grn_no').val('');

					var result = await new Promise(function(resolve) {
						$.ajax({
							url: 'fetch_details.php',
							type: 'GET',
							data: data,
							dataType: 'JSON',
							success: function(r) {
								if (r['status'] == 0) {
									$('#tbl_data').append(r['data']).trigger('change');
									$('#slno').val(parseInt($('#slno').val()) + 1);
									resolve({
										ok: true
									});
								} else {
									resolve({
										ok: false
									});
								}
							},
							error: function() {
								resolve({
									ok: false
								});
							}
						});
					});

					if (result.ok) {
						passed++;
					} else {
						failed.push(grn);
					}
					var pct = Math.round(((i + 1) / total) * 100);
					$('#bulk-progress-bar').css('width', pct + '%');
					$('#bulk-progress-label').text((i + 1) + ' / ' + total + ' loaded');
				}

				$('#bulk-progress-wrap').hide();
				var summaryEl = $('#bulk-summary');
				if (failed.length === 0) {
					summaryEl.addClass('success').html('<i class="fa fa-check-circle"></i> All <strong>' + total + '</strong> GRNs loaded successfully.').show();
				} else {
					summaryEl.addClass('partial').html('<i class="fa fa-exclamation-triangle"></i> <strong>' + passed + '</strong> loaded, <strong>' + failed.length + '</strong> failed.').show();
					$('#grn_failed_list').html('<strong>Failed GRNs:</strong> ' + failed.map(function(g) {
						return '<span style="background:#fdd;padding:1px 6px;border-radius:3px;margin:2px;display:inline-block">' + g + '</span>';
					}).join(' '));
				}
				if (passed > 0) $('#table_div').show();
			}

			/* ── Tab 2 ── */
			$(document).on('click', '#load_bulk', function() {
				var list = $('#bulk_grn_textarea').val().split(/[\n,]+/)
					.map(function(s) {
						return $.trim(s);
					}).filter(function(s) {
						return s !== '';
					});
				list = list.filter(function(v, i, a) {
					return a.indexOf(v) === i;
				});
				bulkLoad(list);
			});

			/* ── Tab 3: CSV/Excel ── */
			var dropArea = document.getElementById('csv_drop_area');
			['dragenter', 'dragover'].forEach(function(ev) {
				dropArea.addEventListener(ev, function(e) {
					e.preventDefault();
					dropArea.style.borderColor = '#1a3a5c';
					dropArea.style.background = '#eef';
				});
			});
			['dragleave', 'drop'].forEach(function(ev) {
				dropArea.addEventListener(ev, function(e) {
					e.preventDefault();
					dropArea.style.borderColor = '#aac';
					dropArea.style.background = '#f8f8ff';
				});
			});
			dropArea.addEventListener('drop', function(e) {
				if (e.dataTransfer.files.length) handleCsvFile(e.dataTransfer.files[0]);
			});
			$('#csv_file_input').on('change', function() {
				if (this.files.length) handleCsvFile(this.files[0]);
			});

			var parsedCsvGRNs = [];

			function handleCsvFile(file) {
				$('#csv_file_name').text('📄 ' + file.name);
				parsedCsvGRNs = [];
				var ext = file.name.split('.').pop().toLowerCase();
				if (ext === 'csv') {
					var r = new FileReader();
					r.onload = function(e) {
						var lines = e.target.result.split(/\r?\n/);
						lines.forEach(function(line, idx) {
							var val = $.trim(line.split(',')[0]);
							if (idx === 0 && val.toLowerCase() === 'grn_no') return;
							if (val) parsedCsvGRNs.push(val);
						});
						$('#csv_file_name').append(' — <strong>' + parsedCsvGRNs.length + ' GRNs found</strong>');
					};
					r.readAsText(file);
				} else {
					var r = new FileReader();
					r.onload = function(e) {
						var wb = XLSX.read(new Uint8Array(e.target.result), {
							type: 'array'
						});
						var rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], {
							header: 1
						});
						rows.forEach(function(row, idx) {
							var val = $.trim(String(row[0] || ''));
							if (idx === 0 && val.toLowerCase() === 'grn_no') return;
							if (val && val !== 'undefined') parsedCsvGRNs.push(val);
						});
						$('#csv_file_name').append(' — <strong>' + parsedCsvGRNs.length + ' GRNs found</strong>');
					};
					r.readAsArrayBuffer(file);
				}
			}
			$(document).on('click', '#load_csv', function() {
				if (!parsedCsvGRNs.length) {
					ewToast('Please upload a file first.', 'warning');
					return;
				}
				bulkLoad(parsedCsvGRNs);
			});
			$(document).on('click', '#download_template', function() {
				var blob = new Blob(['grn_no\nGRN0001\nGRN0002\nGRN0003\n'], {
					type: 'text/csv'
				});
				var a = document.createElement('a');
				a.href = URL.createObjectURL(blob);
				a.download = 'grn_template.csv';
				a.click();
			});

			/* ══════════════════════════════════════════
			   TAB 4 — SELECT FROM LIST (single dropdown,
			   client-side pagination + checkbox select-all)
			══════════════════════════════════════════ */
			var allRows = []; // full result set from server
			var filteredRows = []; // after search box filter
			var selectedRows = {}; // { transaction_id: { grn_no, grn_id, tab_name, transaction_id } }
			var currentPage = 1;
			var pageSize = 10;

			/* ── Search / load bookings ── */
			$(document).on('click', '#list_search_btn', function() {
				var filterStatus = $('#list_filter_status').val();

				if (!filterStatus) {
					ewToast('Please select a status to search.', 'warning');
					return;
				}

				$('#list-loading').show();
				$('#list-result-wrap').hide();
				$('#list-summary').hide().removeClass('success error').html('');
				$('#bulk-update-bar').removeClass('visible');
				selectedRows = {};
				updateSelBadge();

				$.ajax({
					url: 'fetch_details.php',
					type: 'GET',
					data: {
						cmd: 'get_all_bookings_for_status',
						filter_status: filterStatus
					},
					dataType: 'JSON',
					success: function(result) {
						$('#list-loading').hide();

						if (!result || result.status === 1) {
							$('#list-summary').addClass('error')
								.html('<i class="fa fa-info-circle"></i> ' + (result && result.message ? result.message : 'Failed to load. Please try again.'))
								.show();
							return;
						}

						allRows = result.data || [];
						filteredRows = allRows.slice();
						currentPage = 1;
						$('#list-search-box').val('');

						renderTable();

						$('#list-result-wrap').show();
						$('#list-summary').addClass('success')
							.html('<i class="fa fa-check-circle"></i> <strong>' + allRows.length + '</strong> booking(s) found.').show();
					},
					error: function() {
						$('#list-loading').hide();
						$('#list-summary').addClass('error').html('<i class="fa fa-times-circle"></i> Failed to load. Please try again.').show();
					}
				});
			});

			/* ── Search box (client-side filter across visible columns) ── */
			$(document).on('keyup', '#list-search-box', function() {
				var term = $.trim($(this).val()).toLowerCase();
				if (!term) {
					filteredRows = allRows.slice();
				} else {
					filteredRows = allRows.filter(function(r) {
						return Object.keys(r).some(function(k) {
							return String(r[k]).toLowerCase().indexOf(term) !== -1;
						});
					});
				}
				currentPage = 1;
				renderTable();
			});

			/* ── Page size change ── */
			$(document).on('change', '#list_page_size', function() {
				pageSize = parseInt($(this).val(), 10);
				currentPage = 1;
				renderTable();
			});

			/* ── Render current page of table + pagination controls ── */
			function renderTable() {
				var total = filteredRows.length;
				var size = pageSize === -1 ? (total || 1) : pageSize;
				var totalPages = Math.max(1, Math.ceil(total / size));
				if (currentPage > totalPages) currentPage = totalPages;

				var startIdx = (currentPage - 1) * size;
				var pageRows = filteredRows.slice(startIdx, startIdx + size);

				var html = '';
				$.each(pageRows, function(i, r) {
					var checked = selectedRows[r.transaction_id] ? 'checked' : '';
					html += '<tr data-id="' + r.transaction_id + '" data-grnid="' + r.grn_id + '" data-grnno="' + r.grn_no + '" data-tab="' + r.tab_name + '">' +
						'<td class="cb-col"><input type="checkbox" class="row-chk" data-id="' + r.transaction_id + '" ' + checked + '></td>' +
						'<td>' + (startIdx + i + 1) + '</td>' +
						'<td>' + r.grn_no + '</td>' +
						'<td>' + r.grn_date + '</td>'
						// +'<td>'+(r.invoice_no || '')+'</td>'
						+
						'<td>' + (r.weight || '') + '</td>' +
						'<td>' + (r.pkgs || '') + '</td>' +
						'<td>' + r.mode + '</td>' +
						'<td>' + r.origin + '</td>' +
						'<td>' + r.consignor + '</td>' +
						'<td>' + r.consignee + '</td>' +
						'<td>' + r.destination + '</td>' +
						'<td>' + r.current_status + '</td>' +
						'</tr>';
				});
				$('#list_tbody').html(html);
				syncSelectAll();

				// Pagination info
				var fromN = total === 0 ? 0 : startIdx + 1;
				var toN = Math.min(startIdx + size, total);
				$('#list-pagination-info').text('Showing ' + fromN + ' to ' + toN + ' of ' + total + ' entries');

				// Pagination nav buttons
				var navHtml = '';
				navHtml += '<button type="button" data-page="prev" ' + (currentPage <= 1 ? 'disabled' : '') + '>Prev</button>';
				for (var p = 1; p <= totalPages; p++) {
					if (totalPages > 9) {
						// keep it compact for many pages: show first, last, and neighbors of current
						if (p !== 1 && p !== totalPages && Math.abs(p - currentPage) > 2) {
							if (p === 2 || p === totalPages - 1) {
								navHtml += '<button type="button" disabled>…</button>';
							}
							continue;
						}
					}
					navHtml += '<button type="button" data-page="' + p + '" class="' + (p === currentPage ? 'active' : '') + '">' + p + '</button>';
				}
				navHtml += '<button type="button" data-page="next" ' + (currentPage >= totalPages ? 'disabled' : '') + '>Next</button>';
				$('#list-pagination-nav').html(navHtml);
			}

			/* ── Pagination button clicks ── */
			$(document).on('click', '#list-pagination-nav button', function() {
				var page = $(this).data('page');
				var total = filteredRows.length;
				var size = pageSize === -1 ? (total || 1) : pageSize;
				var totalPages = Math.max(1, Math.ceil(total / size));

				if (page === 'prev') {
					currentPage = Math.max(1, currentPage - 1);
				} else if (page === 'next') {
					currentPage = Math.min(totalPages, currentPage + 1);
				} else {
					currentPage = parseInt(page, 10);
				}

				renderTable();
			});

			/* ── Row checkbox ── */
			$(document).on('change', '.row-chk', function() {
				var tid = $(this).data('id');
				var $tr = $(this).closest('tr');
				if ($(this).is(':checked')) {
					selectedRows[tid] = {
						transaction_id: tid,
						grn_id: $tr.data('grnid'),
						grn_no: $tr.data('grnno'),
						tab_name: $tr.data('tab')
					};
				} else {
					delete selectedRows[tid];
				}
				syncSelectAll();
				updateSelBadge();
			});

			/* ── Select All — selects every filtered row across ALL pages ── */
			$(document).on('change', '#select_all_chk', function() {
				var checked = $(this).is(':checked');
				if (checked) {
					$.each(filteredRows, function(i, r) {
						selectedRows[r.transaction_id] = {
							transaction_id: r.transaction_id,
							grn_id: r.grn_id,
							grn_no: r.grn_no,
							tab_name: r.tab_name
						};
					});
				} else {
					$.each(filteredRows, function(i, r) {
						delete selectedRows[r.transaction_id];
					});
				}
				$('#list_tbody .row-chk').prop('checked', checked);
				updateSelBadge();
			});

			function syncSelectAll() {
				var totalFiltered = filteredRows.length;
				var selectedFromFiltered = filteredRows.filter(function(r) {
					return !!selectedRows[r.transaction_id];
				}).length;
				$('#select_all_chk').prop('indeterminate', selectedFromFiltered > 0 && selectedFromFiltered < totalFiltered);
				$('#select_all_chk').prop('checked', totalFiltered > 0 && selectedFromFiltered === totalFiltered);
			}

			function updateSelBadge() {
				var cnt = Object.keys(selectedRows).length;
				$('#sel_count_badge').text(cnt + ' selected');
				if (cnt > 0) {
					$('#bulk-update-bar').addClass('visible');
				} else {
					$('#bulk-update-bar').removeClass('visible');
				}
			}

			/* ── Clear selection ── */
			$(document).on('click', '#bulk_list_clear', function() {
				selectedRows = {};
				$('#list_tbody .row-chk').prop('checked', false);
				$('#select_all_chk').prop('checked', false).prop('indeterminate', false);
				updateSelBadge();
			});

			/* ── Export visible/filtered rows to CSV ── */
			$(document).on('click', '#list_export_btn', function() {
				if (!filteredRows.length) {
					ewToast('No data to export.', 'warning');
					return;
				}
				var headers = ['S.No', 'GRN No', 'GRN Date', 'Weight', 'No. of Pkgs', 'Mode', 'Origin', 'Consignor', 'Consignee', 'Destination', 'Current Status'];
				var lines = [headers.join(',')];
				$.each(filteredRows, function(i, r) {
					var row = [
						i + 1, r.grn_no, r.grn_date, r.weight, r.pkgs,
						r.mode, r.origin, r.consignor, r.consignee, r.destination, r.current_status
					].map(function(v) {
						v = (v === undefined || v === null) ? '' : String(v);
						return '"' + v.replace(/"/g, '""') + '"';
					});
					lines.push(row.join(','));
				});
				var blob = new Blob([lines.join('\n')], {
					type: 'text/csv'
				});
				var a = document.createElement('a');
				a.href = URL.createObjectURL(blob);
				a.download = 'consignment_status_list.csv';
				a.click();
			});

			/* ── Bulk Submit (tab 4) ── */
			$(document).on('click', '#bulk_list_submit', function() {
				var newStatus = $('#bulk_new_status').val();
				var remarks = $.trim($('#bulk_list_remarks').val());
				var keys = Object.keys(selectedRows);

				if (!newStatus) {
					ewToast('Please choose a new status to apply.', 'warning');
					return;
				}
				if (keys.length === 0) {
					ewToast('No bookings selected.', 'warning');
					return;
				}

				if (!confirm('Update status of ' + keys.length + ' booking(s) to the selected status?')) return;

				$(this).attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
				$('.form-data-saving').show();

				// Build payload
				var grn_ids = [],
					grn_nos = [],
					tab_names = [],
					trans_ids = [];
				$.each(selectedRows, function(tid, row) {
					trans_ids.push(tid);
					grn_ids.push(row.grn_id);
					grn_nos.push(row.grn_no);
					tab_names.push(row.tab_name);
				});

				$.ajax({
					url: 'save_details.php',
					type: 'POST',
					data: {
						form_name: 'bulk_list_status_update',
						status: newStatus,
						remarks: remarks,
						'grn_id[]': grn_ids,
						'grn_no[]': grn_nos,
						'tab_name[]': tab_names,
						'trans_id[]': trans_ids
					},
					success: function(result) {
						$('.form-data-saving').hide();
						$('#bulk_list_submit').attr('disabled', false).html('<i class="fa fa-check"></i> Submit');
						if (result != 0) {
							$('#alert-status').text('Alert !!!');
							$('#alert-message').text('Status updated for ' + keys.length + ' booking(s) successfully! Refreshing...');
							$('#alert-container').addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
								$('#alert-container').hide().removeClass('alert-success');
								// Re-run search to refresh the table
								$('#list_search_btn').trigger('click');
								selectedRows = {};
								updateSelBadge();
							});
						} else {
							$('#alert-status').text('Alert !!!');
							$('#alert-message').text('Bulk status update failed. Please try again.');
							$('#alert-container').addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
								$('#alert-container').hide().removeClass('alert-danger');
							});
						}
					},
					error: function() {
						$('.form-data-saving').hide();
						$('#bulk_list_submit').attr('disabled', false).html('<i class="fa fa-check"></i> Submit');
						ewToast('Network error. Please try again.', 'error');
					}
				});
			});

			/* ══════════════════════════════════════════
			   ORIGIN → DESTINATION AJAX
			══════════════════════════════════════════ */
			$(document).on('change', '#origin', function() {
				$.ajax({
					url: 'fetch_details.php',
					type: 'GET',
					data: {
						cmd: 'get_destination',
						id: $(this).val()
					},
					async: false,
					success: function(result) {
						$('#destination').html(result);
					}
				});
			});

			/* ══════════════════════════════════════════
			   SUBMIT (tabs 1-3)
			══════════════════════════════════════════ */
			$(document).on('click', '#save', function(e) {
				$(this).attr('disabled', true);
				$('.form-data-saving').show();
				e.preventDefault();
				if ($('#transaction_form').valid() == true) {
					console.log($('#transaction_form').serialize());

					console.log("Remarks =", $('#remarks').val());

					//alert($('#transaction_form').serialize());
					console.log($('#origin').val());
					console.log($('#destination').val());
					console.log($('#mode').val());

					console.log($('#transaction_form').serializeArray());
					$.ajax({
						url: 'save_details.php',
						type: 'POST',
						data: $('#transaction_form').serialize(),
						success: function(result) {
							console.log("Server Response:");
							console.log(result);
							//alert(result);
							$('.form-data-saving').hide();
							$('#save').attr('disabled', false);
							if (result != 0) {
								$('#alert-status').text('Alert !!!');
								$('#alert-message').text('Status Updated Successfully! Please wait until page refreshes.');
								$('#alert-container').addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
									$('#alert-container').hide().removeClass('alert-success');
									location.reload();
								});
							} else {
								$('#alert-status').text('Alert !!!');
								$('#alert-message').text('Status update Failed');
								$('#alert-container').addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
									$('#alert-container').hide().removeClass('alert-danger');
								});
							}
						}
					});
				}
			});

			/* ══════════════════════════════════════════
			   AUTOCOMPLETE (tab 1)
			══════════════════════════════════════════ */
			$(document).on('keyup', '#grn_no', function() {
				if ($('#transaction_form').valid()) {
					var data = $('#transaction_form').serialize();
					$('#grn_no').autocomplete({
						source: 'autocomplete_list.php?autocomplete=grn_list_for_status_change&' + data,
						minLength: 0,
						select: function(event, ui) {
							$('#active_grn').val(ui.item.id);
						}
					});
				}
			});

			/* ══════════════════════════════════════════
			   DELETE ROW (tabs 1-3)
			══════════════════════════════════════════ */
			$(document).on('click', '.delete', function() {
				$(this).closest('tr').remove();
				if ($('#tbl_data tr').length < 1) $('#table_div').hide();
			});

			/* ══════════════════════════════════════════
			   RESET ON STATUS CHANGE (tabs 1-3)
			══════════════════════════════════════════ */
			$(document).on('change', '#status', function() {
				$('#table_div').hide();
				$('#tbl_data').html('');
				$('#slno').val(1);
				$('#bulk-summary').hide();
				$('#grn_failed_list').html('');
			});

		}); // end ready

		$(window).load(function() {
			$('.loading-page').hide();
		});
	</script>

	<!-- Alert bar -->
	<div class="alert" id="alert-container" style="display:none;">
		<button type="button" class="close" data-dismiss="alert">x</button>
		<strong id="alert-status"></strong>
		<span id="alert-message"></span>
	</div>

	<!-- Delete confirm modal -->
	<div class="modal fade popup_close" id="myModal">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
					<h4 class="modal-title" style="color:#fff">Alert!</h4>
				</div>
				<div class="modal-body">
					<h5>Do you want to Delete This Record?</h5>
					<div class="modal-footer">
						<button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button">Yes</button>
						<button class="btn btn-default-outline" data-dismiss="modal" type="button">No</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="delete-error-popup">
		<div class="popup_overlay" id="popup_overlay"></div>
		<div class="popup" id="popup">
			<div class="popup_message">
				<h5 class="popup-title">Alert!</h5>
				This Data Cannot Delete. Used by another record. So you can't Delete!!!<br>&nbsp;<br>
				<button class="btn btn-sm btn-danger delete-error-popup-close">Close</button><br>&nbsp;<br>
			</div>
		</div>
	</div>

	<div class="modal fade popup_close" id="eway_popup" style="display:none">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
					<h4 class="modal-title" style="color:#fff">Add Attachments</h4>
				</div>
				<div class="modal-body" id="attachment_body"></div>
			</div>
		</div>
	</div>

</body>

</html>