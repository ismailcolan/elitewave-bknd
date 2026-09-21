<?php
require_once("include/connect.php");
require_once("include/function.php");
require_once('include/gst_tax_functions.php');
require_once('include/billing_functions.php');
ensure_gst_tax_master_table($conn);
$company_query = mysqli_query($conn, 'SELECT company_id, company_code, grn_mode, state FROM company WHERE status=0 LIMIT 1');
$company_row = mysqli_fetch_array($company_query);
$comp_id = isset($company_row['company_id']) ? $company_row['company_id'] : 2;
$comp_code = isset($company_row['company_code']) ? $company_row['company_code'] : '';
$comp_grn_mode = isset($company_row['grn_mode']) ? $company_row['grn_mode'] : 'company';
$company_state_id = isset($company_row['state']) ? (int) $company_row['state'] : 0;
$gst_tax_profiles = gst_tax_get_active_profiles($conn);
$gst_profiles_json = json_encode($gst_tax_profiles);
$booking_clients = booking_client_name_options($conn);
$booking_clients_json = json_encode($booking_clients, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
if ($booking_clients_json === false) {
	$booking_clients_json = '[]';
}
?>
<!DOCTYPE html>
<html>

<head>
	<?php include("include/title.php"); ?>
	<?php include("include/css_js.php"); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.save-button { font-size: 13px !important; padding: 10px 17px !important; }
		.cancel-button { font-size: 13px !important; padding: 10px 17px !important; }
		#grn_details .con_name_val1, #grn_details .con_name_val2 { display: none !important; }
		.req-star { color: #DD111E; margin-left: 3px; }
		.booking-split-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; align-items: start; }
		.booking-split-col { min-width: 0; }
		.booking-split-row .payment-gst-panel { display: flex; flex-direction: column; width: 100%; margin-bottom: 0; }
		.payment-gst-panel .amount-in-words-block { margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e8ebf0; }
		.ew-field .v_label { display: block; width: 100%; margin-bottom: 8px; text-align: left; }
		.ew-field > .df { width: 100%; padding: 0; }
		.booking-goods-col .ew-field .form-control { margin: 0; }
		.pkg-action-col { width: 48px; min-width: 48px; }
		.pkg-row-action .pkg-row-btn { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; cursor: pointer; padding: 0; }
		.pkg-row-action .pkg-row-btn.is-add { color: #059669; border-color: #86efac; }
		.pkg-row-action .pkg-row-btn.is-remove { color: #dc2626; border-color: #fca5a5; }
		.payment-gst-panel .text-right, .payment-gst-panel .amt-col, .payment-gst-panel input.text-right, .payment-gst-panel th.text-right, .payment-gst-panel td.text-right { text-align: right !important; }
		.invoice_exist {
			border: 1px solid #e71717 !important;
		}

		.invoice_valid {

			border: 1px solid #00CB01 !important;
		}

		.invoice_new {
			/* border:1px solid #ff4e4e !important; */
			/* #a2d660 */
			border: 1px solid #8E8D8D !important;
		}

		/* #pkg_req label {
				display: none !important;
			}

			#inv_req label {
				display: none !important;
			} */

		#inv_req label.error {
			margin: 0px;
			position: absolute;
			top: -18px;
		}

		#pkg_req label.error {
			margin: 0px;
			position: absolute;
			top: -18px;
		}

		#chrg_req label.error {
			margin: 0px;
			position: absolute;
			top: -18px;
		}

		#typ_req label.error {
			margin: 0px;
			position: absolute;
			top: -18px;
			left: 178px;
		}

		.image_preview {
			width: 145px;
			height: 73px;
		}

		.remove-image {
			margin-left: 90px;
		}

		/* Volumetric Design CSS */

		.shp_lbl {
			padding: 0px 0px;
		}

		label.col-md-4.control-label.shp_lbl {
			padding-left: 10px;
		}

		.form-group.kl {
			display: flex;
			width: 100%;
			flex-wrap: wrap;
			/* justify-content: end; */
		}

		.volumetric_width {

			display: flex;
			width: 30%;
			align-items: center;
			justify-content: center;
			/* column-gap: 3px; */
			text-align: center;
		}

		label.control-label.col-sm-4.v_label {
			text-align: left;
		}

		.dimensions_col {
			display: flex;
			width: 100%;
			/* column-gap: 4px; */

		}

		.volumetric_width:nth-child(6) {
			display: none;

		}


		.volumetric_width:nth-child(7) {
			width: 10%;
			/* width: 23%; */
			display: flex;
			justify-content: end;

		}

		.volumetric_width span {
			margin: 0 2px;
			font-size: 11px;
		}

		.r_p {
			padding: 0px 0px;
			text-align: center;
		}

		.dimen_img {
			width: 17px;
			margin-left: 2px;
		}

		.vlm_total_val {
			width: 80px;
			padding-right: 31px;
		}


		.form-horizontal .control-label {
			text-align: left;
		}

		.ship_address {
			display: flex;
			align-items: center;
			justify-content: left;
			column-gap: 8px;
		}

		input#ship_adddress {
			margin-top: 0;
		}

		.ship_address label {
			margin-bottom: 0px;
		}

		div#shipadd {
			display: none;
		}

		div#shipadd {
			margin-top: 4px;
		}

		.my-fieldset .form-control {
			margin: 1px;
		}

		.main_vlm_box {
			display: flex;
			justify-content: end;
			width: 100%;
		}

		/* DatePickerCss */
		/* .datepicker.datepicker-dropdown.dropdown-menu{
				left: 385.828px !important;
			} */

		.datepicker th.datepicker-switch {
			width: 210px;
		}

		.text-right {
			text-align: left;
		}

		.attach_required:after {
			content: "This field is required.";
			color: #d9534f;
			position: relative;
			display: block;
			margin: 0;
			padding: 0;
			list-style: none;
			font-size: 14px;
			line-height: 20px;

		}

		.con_name_val1:after {
			content: "Select Consignor";
			color: #d9534f;
			position: relative;
			display: block;
			margin: 0;
			padding: 0;
			list-style: none;
			font-size: 14px;
			line-height: 20px;
		}

		.con_name_val2:after {
			content: "Select Consignee";
			color: #d9534f;
			position: relative;
			display: block;
			margin: 0;
			padding: 0;
			list-style: none;
			font-size: 14px;
			line-height: 20px;
		}

		.payment-gst-panel {
			padding-bottom: 10px;
		}

		.payment-gst-panel .table {
			margin-bottom: 0;
			table-layout: fixed;
			width: 100%;
		}

		.payment-gst-panel .table > thead > tr > th,
		.payment-gst-panel .table > tbody > tr > td {
			vertical-align: middle;
			padding: 6px 8px;
			font-size: 13px;
		}

		.payment-gst-panel .table > thead > tr > th:first-child,
		.payment-gst-panel .table > tbody > tr > td:first-child {
			width: 44%;
		}

		.payment-gst-panel .table > thead > tr > th:nth-child(2),
		.payment-gst-panel .table > tbody > tr > td:nth-child(2) {
			width: 24%;
		}

		.payment-gst-panel .table > thead > tr > th:nth-child(3),
		.payment-gst-panel .table > tbody > tr > td:nth-child(3) {
			width: 32%;
		}

		.payment-gst-panel .form-control {
			height: 32px;
			padding: 4px 8px;
			font-size: 13px;
		}

		.payment-gst-panel .gst-config-block {
			margin: 12px 0 0;
			padding: 10px 12px;
			background: #f8f9fb;
			border: 1px solid #e8ebf0;
			border-radius: 4px;
		}

		.payment-gst-panel .gst-config-block .form-group {
			margin-bottom: 8px;
		}

		.payment-gst-panel .gst-config-block .form-group:last-child {
			margin-bottom: 0;
		}

		.payment-gst-panel .gst-config-block label {
			font-size: 12px;
			font-weight: 600;
			margin-bottom: 4px;
			display: block;
		}

		.payment-gst-panel .gst-breakup-block {
			margin-top: 12px;
			padding-top: 10px;
			border-top: 1px dashed #ddd;
		}

		.payment-gst-panel .gst-breakup-title {
			font-size: 13px;
			font-weight: 600;
			margin: 0 0 8px;
			color: #333;
		}

		.payment-gst-panel .gst-breakup-table tbody tr:last-child td {
			background: #f3f6fa;
			font-weight: 600;
		}

		.payment-gst-panel .rate-empty {
			color: #bbb;
		}

		.booking-footer-section {
			margin-top: 12px;
			margin-bottom: 0;
			clear: both;
		}

		.booking-footer-section > [class*="col-"] {
			padding-top: 0;
		}

		.booking-panel {
			background: #fff;
			border: 1px solid #e5e7eb;
			border-radius: 6px;
			padding: 12px 14px;
			height: 100%;
		}

		.booking-panel-title {
			margin: 0 0 8px;
			font-weight: 600;
			color: #374151;
		}

		.booking-panel-hint {
			margin: 0 0 10px;
			font-size: 12px;
			color: #9ca3af;
			line-height: 1.4;
		}

		.signature-canvas-wrap {
			border: 1px dashed #d1d5db;
			border-radius: 6px;
			background: #fff;
			min-height: 130px;
			overflow: hidden;
			position: relative;
		}

		.signature-canvas-wrap.height_check {
			display: none;
		}

		#signature {
			width: 100% !important;
			height: 130px !important;
			border: none !important;
		}

		#signature canvas {
			width: 100% !important;
			height: 130px !important;
			border-radius: 4px;
			background: #fafafa !important;
		}

		#signature img {
			display: none !important;
		}

		#signature > div[style*="height: 0"] {
			display: none !important;
		}

		.signature-saved-preview:empty {
			display: none;
			margin: 0;
		}

		.signature-saved-preview {
			margin-top: 8px;
		}

		.signature-saved-preview img {
			max-width: 100%;
			max-height: 100px;
			border: 1px solid #e5e7eb;
			border-radius: 4px;
			background: #fff;
			padding: 4px;
		}

		.signature-tools {
			margin-top: 8px;
		}

		.signature-tools .btn {
			min-width: auto;
			padding: 4px 10px;
			font-size: 12px;
		}

		.upload-dropzone {
			border: 1px dashed #cbd5e1;
			border-radius: 6px;
			background: #fafafa;
			padding: 14px 12px;
			text-align: center;
			cursor: pointer;
			transition: border-color 0.15s, background 0.15s;
		}

		.upload-dropzone:hover,
		.upload-dropzone.is-dragover {
			border-color: #94a3b8;
			background: #f4f4f5;
		}

		.upload-dropzone-inner i {
			font-size: 22px;
			color: #64748b;
			display: block;
			margin-bottom: 6px;
		}

		.upload-dropzone-inner span {
			display: block;
			font-size: 12px;
			color: #475569;
		}

		.upload-dropzone-inner small {
			display: block;
			margin-top: 3px;
			font-size: 10px;
			color: #94a3b8;
		}

		.upload-preview-list {
			margin-top: 8px;
		}

		.upload-item {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 8px;
			margin-bottom: 6px;
			border: 1px solid #e5e7eb;
			border-radius: 6px;
			background: #fff;
		}

		.upload-item-preview {
			width: 48px;
			height: 48px;
			flex-shrink: 0;
			border: 1px solid #e5e7eb;
			border-radius: 6px;
			overflow: hidden;
			background: #f3f4f6;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.upload-item-preview img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.upload-item-preview img.doc-placeholder {
			object-fit: contain;
			padding: 8px;
			width: 40px;
			height: 40px;
		}

		.upload-item-body {
			flex: 1;
			min-width: 0;
		}

		.upload-item-name {
			display: block;
			font-size: 13px;
			font-weight: 500;
			color: #111827;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.upload-item-sub {
			display: block;
			font-size: 11px;
			color: #9ca3af;
			margin-top: 2px;
		}

		.upload-item-actions {
			flex-shrink: 0;
		}

		.upload-item-remove {
			color: #dc2626 !important;
			padding: 4px 8px;
			opacity: 1 !important;
			cursor: pointer;
		}

		.upload-add-btn {
			margin-top: 6px;
			font-size: 12px;
			padding: 4px 10px;
		}

		.upload-file-input {
			position: absolute;
			width: 0;
			height: 0;
			opacity: 0;
			overflow: hidden;
		}

		.file-container {
			margin-top: 0;
		}

		.image_preview {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.img_pre_div {
			margin-bottom: 10px;
		}

		@media (min-width: 320px) and (max-width:575.98px) {

			#pkg_req label.error,
			#inv_req label.error,
			#typ_req label.error,
			#chrg_req label.error {
				margin: 0px;
				position: absolute;
				top: -18px;
				display: none !important;
			}

		}

		@media only screen and (min-width: 390px) and (max-width: 844px) and (orientation: landscape) {

			div#signature {
				border: 1px solid black;
				height: 200px;

			}

			#pkg_req label.error,
			#inv_req label.error,
			#typ_req label.error,
			#chrg_req label.error {
				margin: 0px;
				position: absolute;
				top: -18px;
				display: none !important;
			}


		}

		@media (max-width: 575.98px) {
			.pak_info_tblee {
				width: 100%;
				overflow-x: scroll;
			}

			.ew-page-v2--consignment .tabs,
			.widget-container .tabs {

				table-layout: fixed;
				width: 153%;
			}

			.ew-page-v2--consignment .tabs,
			.widget-container .tabs {
				background: whitesmoke;
				border-bottom: 1px solid #dddddd;
				table-layout: fixed;
				width: 153%;
			}

			.table {
				margin-bottom: 10px;

				max-width: none;
				width: auto;
				min-width: 100%;

			}
		}

		.payment-gst-panel .gst-config-block { margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e8ebf0; }
		.payment-gst-panel .gst-breakup-block { margin-top: 12px; }
		.payment-gst-panel .gst-breakup-title { font-size: 12px; font-weight: 600; margin-bottom: 8px; color: #334155; }
		.payment-gst-panel .gst-breakup-note { display: block; font-size: 11px; color: #64748b; margin-top: 6px; }
		.payment-gst-panel .amount-in-words-block { margin-top: 14px; padding-top: 14px; border-top: 1px dashed #e8ebf0; }

		@media (min-width: 576px) and (max-width: 767.98px) {
			.pak_info_tblee {
				width: 100%;
				overflow-x: scroll;
			}

			.ew-page-v2--consignment .tabs,
			.widget-container .tabs {

				table-layout: fixed;
				width: 153%;
			}

			.ew-page-v2--consignment .tabs,
			.widget-container .tabs {
				background: whitesmoke;
				border-bottom: 1px solid #dddddd;
				table-layout: fixed;
				width: 153%;
			}

			.table {
				margin-bottom: 10px;

				max-width: none;
				width: auto;
				min-width: 100%;

			}
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
				<div class="col-md-12">
					<div class="ew-page-v2 ew-page-v2--consignment">
							<?php
							$m = $_REQUEST['m'];
							$y = $_REQUEST['y'];
							$query = "select * from transaction_" . $m . "_" . $y . " where md5(transaction_id) = '" . $_REQUEST['key'] . "'";
							$result = mysqli_query($conn, $query);
							$row = mysqli_fetch_assoc($result);
							if (!is_array($row)) {
								$row = array();
							}
							$transaction_id = $row['transaction_id'] ?? '';
							$ftl_type = $row['ftl_type'] ?? '';
							if (($row['transaction_id'] ?? 0) > 0) {
								$form_name = "edit_consignment_details_manual";
							} else {
								$form_name = "add_new_consignment_manual";
							}
							if (!empty($m) && !empty($y)) {
								ensure_transaction_gst_columns($conn, 'transaction_' . $m . '_' . $y);
							}
							$saved_gst_tax_id = (int) ($row['gst_tax_id'] ?? 0);
							$saved_gst_type = !empty($row['gst_type']) ? $row['gst_type'] : 'auto';
							$default_gst_tax_id = $saved_gst_tax_id;
							if ($default_gst_tax_id <= 0) {
								foreach ($gst_tax_profiles as $gst_profile_row) {
									if ($gst_profile_row['tax_code'] === 'GST18') {
										$default_gst_tax_id = (int) $gst_profile_row['gst_tax_id'];
										break;
									}
								}
								if ($default_gst_tax_id <= 0 && !empty($gst_tax_profiles)) {
									$default_gst_tax_id = (int) $gst_tax_profiles[0]['gst_tax_id'];
								}
							}
							$grn_date_val = !empty($row['grn_date']) ? $row['grn_date'] : date('d-m-Y');
							$booking_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
							$date_keypress_attr = 'onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);"';
							$grn_date_opts = array(
								'id' => 'grn_date',
								'name' => 'grn_date',
								'value' => $grn_date_val,
								'required' => true,
								'end_date' => 'today',
								'attrs' => $date_keypress_attr,
							);
							if ($booking_role !== 'AD') {
								$grn_date_opts['start_date'] = date('d-m-Y');
							}
							$consignment_page_title = ($form_name === 'edit_consignment_details_manual') ? 'Edit Manual Consignment' : 'Book Manual Consignment';
							?>
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<a href="transaction_list.php" class="ew-back-btn" title="Back to list"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php echo htmlspecialchars($consignment_page_title); ?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="transaction_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-form-body">
							<div id="response" class="alert alert-danger" style="display:none;">
								<div class="message" style="text-align:center"></div>
							</div>
							<form id="grn_details" class="form-horizontal ew-validated-form" enctype="multipart/form-data" data-ew-validate="1">
								<input type="hidden" name="truck_type" id="truck_type" value="<?php echo $ftl_type; ?>" />
								<input type="hidden" name="form_name" value="<?php echo $form_name; ?>" id="form_name">
								<input type="hidden" name="edit_id" id="edit_id" value="<?php echo $row['transaction_id'] ?? ''; ?>">
								<input type="hidden" name="grn_id" id="grn_id" value="<?php echo $row['grn_id'] ?? ''; ?>">
								<input type="hidden" name="consignor_state_id" id="consignor_state_id" value="<?php echo (int) ($row['state'] ?? 0); ?>">
								<input type="hidden" name="consignee_state_id" id="consignee_state_id" value="<?php echo (int) ($row['con_state'] ?? 0); ?>">
								<input type="hidden" name="origin_state_id" id="origin_state_id" value="">
								<input type="hidden" name="destination_state_id" id="destination_state_id" value="">
								<input type="hidden" name="bill_to_state_id" id="bill_to_state_id" value="<?php echo (int) ($row['bill_to_state_id'] ?? 0); ?>">
								<input type="hidden" name="gst_tax_code" id="gst_tax_code" value="<?php echo htmlspecialchars($row['gst_tax_code'] ?? ''); ?>">
								<input type="hidden" name="taxable_value" id="taxable_value" value="<?php echo htmlspecialchars($row['taxable_value'] ?? '0'); ?>">
								<input type="hidden" name="cgst_rate" id="cgst_rate" value="<?php echo htmlspecialchars($row['cgst_rate'] ?? '0'); ?>">
								<input type="hidden" name="sgst_rate" id="sgst_rate" value="<?php echo htmlspecialchars($row['sgst_rate'] ?? '0'); ?>">
								<input type="hidden" name="igst_rate" id="igst_rate" value="<?php echo htmlspecialchars($row['igst_rate'] ?? '0'); ?>">
								<input type="hidden" name="cess_rate" id="cess_rate" value="<?php echo htmlspecialchars($row['cess_rate'] ?? '0'); ?>">
								<input type="hidden" name="cgst_amount" id="cgst_amount" value="<?php echo htmlspecialchars($row['cgst_amount'] ?? '0'); ?>">
								<input type="hidden" name="sgst_amount" id="sgst_amount" value="<?php echo htmlspecialchars($row['sgst_amount'] ?? '0'); ?>">
								<input type="hidden" name="igst_amount" id="igst_amount" value="<?php echo htmlspecialchars($row['igst_amount'] ?? '0'); ?>">
								<input type="hidden" name="cess_amount" id="cess_amount" value="<?php echo htmlspecialchars($row['cess_amount'] ?? '0'); ?>">
								<div class="ew-booking-section">
									<h2 class="ew-card-section-title">GCN Information</h2>
									<div class="ew-form-grid">
										<div class="ew-field">
											<label class="control-label">GCN No <span class="req-star">*</span></label>
													<?php
													$query_code = mysqli_query($conn, "select * from client where client_id='" . $_SESSION['company_id'] . "'");
													$r_code = mysqli_fetch_array($query_code);
													$query_max = mysqli_query($conn, "select * from transaction_log where client_id='" . $_SESSION['company_id'] . "'");
													$r_max = mysqli_fetch_array($query_max);
													$id = $r_max['grn_id'] + 1;
													$billing_code = $r_code['billing_code'];
													$grn_no = $billing_code . sprintf("%05d", $id);
													?>
													<input type="hidden" id="id" value="" name="id" class="form-control" />
													<?php
													if ($row['grn_no'] != '') {
													?>
														<input type="text" id="grn_no" name="grn_no" value="<?php echo $row['grn_no']; ?>" class="form-control" readonly autocomplete="off" required />
													<?php
													} else {
													?>
														<input type="text" id="grn_no" name="grn_no" class="form-control num_only" value="" onblur="CheckDuplicateManualGrn()" autocomplete="off" required />
													<?php
													}
													?>
													<span id="grn_error"></span>
										</div>
										<div class="ew-field">
											<label class="control-label">GCN Date <span class="req-star">*</span></label>
											<?php echo ew_date_input($grn_date_opts); ?>
										</div>
										<div class="ew-field">
											<label class="control-label">Mode of Transport <span class="req-star">*</span></label>
													<select name="mode_of_trasport" id="mode_of_trasport" class="form-control" required onchange="handleSelectChange(event);">
														<option value="">Mode of Transport</option>
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
										<div class="ew-field">
											<label class="control-label">Consignment Mode <span class="req-star">*</span></label>
													<select name="mode_of_consignment" id="mode_of_consignment" class="form-control" required>
														<option value="">Select Consignment</option>
														<?php
														$consignment_query = "select * from consignment_mode where status=0";
														$consignment_result = mysqli_query($conn, $consignment_query);
														while ($consignment_row = mysqli_fetch_array($consignment_result)) {
															if ($consignment_row['consignment_id'] != '3') {
														?>
																<option value="<?php echo $consignment_row['consignment_id']; ?>" <?php if ($consignment_row['consignment_id'] == $row['mode_of_consignment']) echo "selected"; ?>><?php echo $consignment_row['consignment_mode']; ?></option>
														<?php
															}
														}
														?>
													</select>
										</div>
										<div class="ew-field">
											<label class="control-label">Origin <span class="req-star">*</span></label>
													<select name="origin" id="origin" class="form-control" required>
														<option value="">Select Origin</option>
														<?php
														$city_query = "select * from city where status=0 order by city_name asc";
														$city_result = mysqli_query($conn, $city_query);
														while ($city_row = mysqli_fetch_array($city_result)) {
														?>
															<option value="<?php echo $city_row['city_id']; ?>" data-state="<?php echo (int) $city_row['state']; ?>" <?php if ($city_row['city_id'] == ($row['origin'] ?? '')) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
														<?php
														}
														?>
													</select>
										</div>
										<div class="ew-field">
											<label class="control-label">Destination <span class="req-star">*</span></label>
													<select name="destination" id="destination" class="form-control" required>
														<option value="">Select Destination</option>
														<?php
														$city_query1 = "select * from city where status=0 and city_id!='" . $row['origin'] . "' order by city_name asc";
														$city_result1 = mysqli_query($conn, $city_query1);
														while ($city_row1 = mysqli_fetch_array($city_result1)) {
														?>
															<option value="<?php echo $city_row1['city_id']; ?>" data-state="<?php echo (int) $city_row1['state']; ?>" <?php if ($city_row1['city_id'] == ($row['destination'] ?? '')) echo "selected"; ?>><?php echo $city_row1['city_name']; ?></option>
														<?php
														}
														?>
													</select>
										</div>
										<div class="ew-field">
											<label class="control-label">Select Status <span class="req-star">*</span></label>
													<select name="status" id="status" class="form-control" required>
														<option value=""> -- Select Status -- </option>
														<option value="1" <?php if ($row['status'] == 1) echo "selected"; ?>>Consignment Booked</option>
														<option value="2" <?php if ($row['status'] == 2) echo "selected"; ?>>Consignment Picked Up</option>
														<option value="3" <?php if ($row['status'] == 3) echo "selected"; ?>>In Transit - 1 (Consignment at Origin State)</option>
														<option value="4" <?php if ($row['status'] == 4) echo "selected"; ?>>In Transit - 2 (Towards Destination State)</option>
														<option value="5" <?php if ($row['status'] == 5) echo "selected"; ?>>In Transit - 3 (Towards Destination)</option>
														<option value="6" <?php if ($row['status'] == 6) echo "selected"; ?>>At Destination</option>
														<option value="7" <?php if ($row['status'] == 7) echo "selected"; ?>>Out for Delivery</option>
														<option value="8" <?php if ($row['status'] == 8) echo "selected"; ?>>Consignment Delivered Successfully</option>
													</select>
										</div>
										<div class="ew-field" id="ftl_menu" style="display:none;">
											<label class="control-label">FTL Type <span class="req-star">*</span></label>
													<select class="dropp form-control" role="menu" aria-labelledby="menu1" id="dropp">
														<option value="" selected="true" disabled="disabled">Select Truck Type...</option>
														<option value="Single Axle Vehicle: 07MT" <?php if ("Single Axle Vehicle: 07MT" == $row['ftl_type']) echo "selected"; ?>>Single Axle Vehicle: 07MT</option>
														<option value="Multi Axle Vehicle : 10MT/14MT/17MT" <?php if ("Multi Axle Vehicle : 10MT/14MT/17MT" == $row['ftl_type']) echo "selected"; ?>>Multi Axle Vehicle : 10MT/14MT/17MT</option>
														<option value="22ft Vehicle : 07MT" <?php if ("22ft Vehicle : 07MT" == $row['ftl_type']) echo "selected"; ?>> 22ft Vehicle : 07MT</option>
														<option value="18ft Vehicle : 06MT" <?php if ("18ft Vehicle : 06MT" == $row['ftl_type']) echo "selected"; ?>>18ft Vehicle : 06MT</option>
														<option value="Eicher 19 Vehicle : 7MT/8MT/9MT" <?php if ("Eicher 19 Vehicle : 7MT/8MT/9MT" == $row['ftl_type']) echo "selected"; ?>>Eicher 19 Vehicle : 7MT/8MT/9MT</option>
														<option value="Eicher 17 Vehicle : 5MT" <?php if ("Eicher 17 Vehicle : 5MT" == $row['ftl_type']) echo "selected"; ?>>Eicher 17 Vehicle : 5MT</option>
														<option value="Eicher 19 Vechicle:4MT" <?php if ("Eicher 19 Vechicle:4MT" == $row['ftl_type']) echo "selected"; ?>>Eicher 19 Vechicle:4MT</option>
													</select>
										</div>
										<div class="ew-field" id="train_type" style="display:none;">
											<label class="control-label">Train Type <span class="req-star">*</span></label>
													<select name="train_name" class="train_type form-control" role="menu" aria-labelledby="menu1" id="train_type_sel">
														<option value="" selected="true" disabled="disabled">Select Train Type...</option>
														<option value="1" <?php if ("1" == $row['train_type']) echo "selected"; ?>>Rajdhani Express</option>
														<option value="2" <?php if ("2" == $row['train_type']) echo "selected"; ?>>Others</option>
													</select>
										</div>
										<div class="ew-field" id="other_train_field">
												<?php if (!empty($row['other_train_name'])) { ?>
													<label class="control-label">Other Train Name</label>
													<input type="text" name="other_train_name" id="other_train_name" class="form-control" placeholder="Enter Train Name" value="<?php echo htmlspecialchars($row['other_train_name']); ?>">
												<?php } ?>
										</div>
									</div>
								</div>

								<div class="ew-booking-section">
									<h2 class="ew-card-section-title">Consignor &amp; Consignee Information</h2>
									<div class="party-split">
										<div class="party-card party-card--consignor">
											<div class="ew-field">
												<label class="control-label">Consignor <span class="req-star">*</span></label>
												<select name="consignor_name" class="form-control party-select" id="consignor_name" data-placeholder="Select consignor">
													<option value="">Select consignor</option>
													<?php
													$sel_consignor = (int) ($row['consigner'] ?? 0);
													foreach ($booking_clients as $copt) {
														$sel = ((int) $copt['id'] === $sel_consignor) ? ' selected' : '';
														echo '<option value="' . (int) $copt['id'] . '"' . $sel . '>' . htmlspecialchars($copt['name']) . '</option>';
													}
													?>
												</select>
												<label for="" class="consignor_name_val"></label>
												<input name="consignor" id="consignor" required value="<?php echo $row['consigner']; ?>" type="hidden" class="get_consigner_valll" />
											</div>
											<div class="ew-field" id="consignor_branch_div" style="display:none;">
												<label class="control-label">Consignor Branch</label>
												<select id="consignor_branch" name="consignor_branch" class="form-control party-select" data-placeholder="Select Branch">
													<option value="">Select Branch</option>
												</select>
											</div>
											<div id="con_details" class="party-card-meta" style="display:none;">
												<div class="ew-field">
													<label class="control-label">Consignor Address</label>
													<div class="meta-value" id="address1"></div>
												</div>
												<div class="party-card-meta-row">
													<div class="ew-field">
														<label class="control-label">Phone</label>
														<div class="meta-value" id="phone"></div>
													</div>
													<div class="ew-field">
														<label class="control-label">GST No</label>
														<div class="meta-value" id="gst_no"></div>
													</div>
												</div>
												<span id="address2" style="display:none;"></span>
												<span id="city" style="display:none;"></span>
												<span id="state" style="display:none;"></span>
												<span id="pincode" style="display:none;"></span>
											</div>
										</div>
										<div class="party-card party-card--consignee">
											<div class="ew-field">
												<label class="control-label">Consignee <span class="req-star">*</span></label>
												<select name="consignee_name" class="form-control party-select" id="consignee_name" data-placeholder="Select consignee" disabled>
													<option value="">Select consignee</option>
													<?php
													$sel_consignee = (int) ($row['consignee'] ?? 0);
													if ($sel_consignee > 0) {
														echo '<option value="' . $sel_consignee . '" selected>' . htmlspecialchars(get_client_name($conn, $sel_consignee)) . '</option>';
													}
													?>
												</select>
												<label for="" class="consignee_name_val"></label>
												<input name="consignee" id="consignee" required value="<?php echo $row['consignee']; ?>" type="hidden" class="get_consignee_valll" />
											</div>
											<div class="ew-field" id="consignee_branch_div" style="display:none;">
												<label class="control-label">Consignee Branch</label>
												<select id="consignee_branch" name="consignee_branch" class="form-control party-select" data-placeholder="Select Branch">
													<option value="">Select Branch</option>
												</select>
											</div>
											<div id="con_details1" class="party-card-meta" style="display:none;">
												<div class="ew-field">
													<label class="control-label">Consignee Address</label>
													<div class="meta-value" id="con_address1"></div>
												</div>
												<div class="party-card-meta-row">
													<div class="ew-field">
														<label class="control-label">Phone</label>
														<div class="meta-value" id="con_phone"></div>
													</div>
													<div class="ew-field">
														<label class="control-label">GST No</label>
														<div class="meta-value" id="con_gst"></div>
													</div>
												</div>
												<span id="con_address2" style="display:none;"></span>
												<span id="con_state" style="display:none;"></span>
												<span id="con_city" style="display:none;"></span>
												<span id="con_pincode" style="display:none;"></span>
												<?php
												$ship_checked = (!empty($row['shipping_address']) || !empty($row['shipping_address_name'])) ? 'checked="checked"' : '';
												?>
												<div class="ew-field shipping-block">
													<div class="ship-toggle">
														<input type="checkbox" id="ship_adddress" name="ship_adddress" <?php echo $ship_checked; ?>>
														<label for="ship_adddress">Shipping Address (optional)</label>
													</div>
													<div id="shipadd" style="display: none;">
														<input type="text" name="shipping_address_name" id="shipping_address_name" class="form-control" style="margin-bottom:6px;" placeholder="Recipient Name" value="<?php echo htmlspecialchars($row['shipping_address_name'] ?? ''); ?>">
														<textarea class="form-control" rows="3" name="shipping_address" id="shipping_address" style="margin-bottom:6px;" placeholder="Shipping Address"><?php echo htmlspecialchars($row['shipping_address'] ?? ''); ?></textarea>
														<div class="ew-form-grid" style="margin-top:6px;">
															<div class="ew-field">
																<input type="text" name="shipping_gst_no" id="shipping_gst_no" class="form-control" placeholder="GST No" value="<?php echo htmlspecialchars($row['shipping_gst_no'] ?? ''); ?>">
															</div>
															<div class="ew-field">
																<input type="text" name="shipping_phone" id="shipping_phone" class="form-control" placeholder="Phone No" value="<?php echo htmlspecialchars($row['shipping_phone'] ?? ''); ?>">
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="booking-compact-stack">
								<div class="ew-booking-section booking-package-section">
									<h2 class="ew-card-section-title">Package Information</h2>
									<?php
									$pkg_list = array();
									$pkg_type_q = mysqli_query($conn, "select * from package where status='0'");
									while ($pkg_r = mysqli_fetch_array($pkg_type_q)) {
										$pkg_list[] = $pkg_r;
									}
									$pkg_option = '<option value="">Select Package Type</option>';
									foreach ($pkg_list as $pkg_r) {
										$pkg_option .= '<option value="' . (int) $pkg_r['package_id'] . '">' . htmlspecialchars($pkg_r['package_code']) . '</option>';
									}
									$package_rows = array();
									if ($_REQUEST['key'] == '') {
										$package_rows[] = array();
									} else {
										$invoice_query = "select * from transaction_invoice_" . $m . "_" . $y . " where md5(transaction_id)='" . $_REQUEST['key'] . "'";
										$invoice_result = mysqli_query($conn, $invoice_query);
										while ($invoice_row = mysqli_fetch_array($invoice_result)) {
											$package_rows[] = $invoice_row;
										}
										if (empty($package_rows)) {
											$package_rows[] = array();
										}
									}
									?>
									<div class="row pak_info_tblee">
										<div class="col-md-12">
											<table class="table table-bordered tabs" width="100%">
												<thead>
													<tr>
														<th class="text-center" width="5%">S.No</th>
														<th class="text-center" width="10%">No of Pkgs</th>
														<th class="text-center" width="18%">Type of Pkgs</th>
														<th class="text-center" width="13%">Party Invoice No</th>
														<th class="text-center" width="13%">Invoice Date</th>
														<th class="text-center" width="13%">Said to Contents</th>
														<th class="text-center" width="10%">Qty</th>
														<th class="text-center" width="13%">Gross Wt.(Kgs)</th>
														<th class="text-center" width="13%">Charged wt.(Kgs)</th>
														<th class="text-center pkg-action-col"></th>
													</tr>
												</thead>
												<tbody id="package-tbody">
													<?php
													foreach ($package_rows as $row_index => $invoice_row) {
														$i = $row_index + 1;
														$invoice_date_val = (
															!empty($invoice_row['party_invoice_date']) &&
															$invoice_row['party_invoice_date'] != '0000-00-00'
														) ? date('d-m-Y', strtotime($invoice_row['party_invoice_date'])) : '';
													?>
														<tr class="pkg-data-row" data-row-index="<?php echo $i; ?>">
															<td class="text-center pkg-sno"><?php echo $i; ?></td>
															<td id="pkg_req"><input type="text" name="no_of_pkg[]" id="no_of_pkg<?php echo $i; ?>" value="<?php echo htmlspecialchars($invoice_row['no_of_pkge'] ?? ''); ?>" class="form-control num_only text-right pkg-row-input" inputmode="numeric" autocomplete="off" onpaste="return ewNumericPaste(event,this);"></td>
															<td id="typ_req">
																<select name="type_of_pkg[]" id="type_of_pkg<?php echo $i; ?>" class="form-control pkg-row-select"<?php echo ($i === 1) ? ' required' : ''; ?>>
																	<option value="">Select Package Type</option>
																	<?php foreach ($pkg_list as $pkg_r) { ?>
																		<option value="<?php echo (int) $pkg_r['package_id']; ?>" <?php if (($invoice_row['type_of_pkge'] ?? '') == $pkg_r['package_id']) echo 'selected'; ?>><?php echo htmlspecialchars($pkg_r['package_code']); ?></option>
																	<?php } ?>
																</select>
															</td>
															<td id="inv_req"><input type="text" name="party_invoice[]" id="party_invoice<?php echo $i; ?>" value="<?php echo htmlspecialchars($invoice_row['party_invoice_no'] ?? ''); ?>" class="form-control" onchange="party_invoice_details();" onkeyup="party_invoice_details();" autocomplete="off"></td>
															<td><?php echo ew_date_input(array(
																'id' => 'party_invoice_date' . $i,
																'name' => 'party_invoice_date[]',
																'value' => $invoice_date_val,
																'class' => 'party-invoice-date',
																'end_date' => 'today',
															)); ?></td>
															<td><input type="text" name="content[]" id="content<?php echo $i; ?>" value="<?php echo htmlspecialchars($invoice_row['said_contents'] ?? ''); ?>" class="form-control" autocomplete="off"></td>
															<td><input type="text" name="qty[]" id="qty<?php echo $i; ?>" value="<?php echo htmlspecialchars($invoice_row['qty'] ?? ''); ?>" class="form-control num_only text-right" autocomplete="off" inputmode="numeric" onpaste="return ewNumericPaste(event,this);"></td>
															<td><input type="text" name="gross[]" id="gross<?php echo $i; ?>" value="<?php echo htmlspecialchars($invoice_row['gross_weight'] ?? ''); ?>" class="form-control text-right num_only" inputmode="numeric" autocomplete="off" onpaste="return ewNumericPaste(event,this);"></td>
															<td id="chrg_req"><input type="text" name="charged[]" id="charged<?php echo $i; ?>" value="<?php echo htmlspecialchars($invoice_row['charged_weight'] ?? ''); ?>" class="form-control text-right num_only charged_w" onkeyup="calculate_charge_weight();" inputmode="numeric" autocomplete="off" onpaste="return ewNumericPaste(event,this);"></td>
															<td class="text-center pkg-row-action"></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
											<input type="hidden" name="cumulative_charged" id="cumulative_charged" value="">
										</div>
									</div>
								</div>

								<div class="booking-split-row">
									<div class="booking-split-col ew-booking-section booking-goods-col my-fieldset lable">
											<h2 class="ew-card-section-title">Volumetric Consignment (If Any)</h2>
											<div class="ew-form-grid ew-form-grid--compact">
												<div class="ew-field">
													<label class="control-label">Supplier Invoice Value</label>
													<input type="text" name="supplier_invoice_value" id="supplier_invoice_value" value="<?php echo htmlspecialchars($row['supplier_invoice_value'] ?? ''); ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">E-Way Number</label>
													<input type="text" id="eway_number" value="<?php echo $row['eway_number'] ?>" name="eway_number" class="form-control text-right" autocomplete="off" />
												</div>
												<div class="ew-field">
													<label class="control-label">E-Way Expiry Date</label>
													<?php echo ew_date_input(array(
														'id' => 'eway_expiryDate',
														'name' => 'eway_expiryDate',
														'value' => $row['eway_expirydate'] ?? '',
														'attrs' => $date_keypress_attr,
													)); ?>
												</div>
												<div class="ew-field">
													<label class="control-label">LC Number</label>
													<input type="text" name="lc_number" id="lc_number" value="<?php echo htmlspecialchars($row['lc_number'] ?? ''); ?>" class="form-control">
												</div>
												<div class="ew-field span-2">
													<label class="control-label">Description Of Goods</label>
													<textarea name="description_of_goods" id="description_of_goods" class="form-control" rows="2"><?php echo htmlspecialchars($row['description_of_goods'] ?? ''); ?></textarea>
												</div>
												<div class="ew-field">
													<label class="control-label">CFS / Port / Factory / Warehouse</label>
													<input type="text" name="cfs" id="cfs" value="<?php echo htmlspecialchars($row['cfs'] ?? ''); ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">Part Number / Article Name</label>
													<input type="text" name="vehicle_purchase_contact_person" value="<?php echo htmlspecialchars($row['vehicle_purchase_contact_person'] ?? ''); ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">Quotation Approval</label>
													<input type="text" name="quotation_approval" value="<?php echo htmlspecialchars($row['quotation_approval'] ?? ''); ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">Truck / Vehicle No</label>
													<input type="text" name="vehicle_no" id="vehicle_no" value="<?php echo htmlspecialchars($row['truck'] ?? ''); ?>" class="form-control" autocomplete="off">
												</div>
												<div class="ew-field">
													<label class="control-label">Insurance No</label>
													<input type="text" name="insurance_number" id="insurance_number" value="<?php echo htmlspecialchars($row['insurance_number'] ?? ''); ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">Vehicle Type</label>
													<input type="text" name="vehicle_type" id="vehicle_type" value="<?php echo htmlspecialchars($row['vehicle_type'] ?? ''); ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">Highload Challan</label>
													<input type="text" name="highload_challan" value="<?php echo htmlspecialchars($row['highload_challan'] ?? ''); ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">Goods Declared Value (INR)</label>
													<input type="text" id="goods_dedared_value" value="<?php echo $row['goods_dedared_value'] ?>" name="goods_dedared_value" class="form-control text-right" onchange="fov_calc();" autocomplete="off" />
												</div>
											</div>
											<input type="hidden" name="volumetric_weight" id="volumetric_weight" value="<?php echo htmlspecialchars($row['volumetric_weight'] ?? ''); ?>">
											<div class="ew-field span-2 booking-dims-field">
												<label class="control-label v_label">Dimensions (L × W × H in cms)</label>
												<div class="df">
													<?php
													$dimension1 = $row['dimension1'];
													$length_dimension1 = explode(',', $dimension1);
													$dimension2 = $row['dimension2'];
													$width_dimension2 = explode(',', $dimension2);
													$dimension3 = $row['dimension3'];
													$height_dimension3 = explode(',', $dimension3);
													$dimension4 = $row['dimension4'];
													$quantity_dimension4 = explode(',', $dimension4);
													$count = 0;
													foreach ($length_dimension1 as $key => $values) {
														$count++;
													?>
														<div class="form-group dimensions_col" id="dimensions_col1" data-dem-no="1">
															<div class="volumetric_width">
																<input type="text" placeholder="L" class="form-control r_p length num_only " id="length" name="length[]" onchange="vlm_calculation();" value="<?php echo $length_dimension1[$key] ?>" autocomplete="off" /><span>X</span>
															</div>
															<div class="volumetric_width">
																<input type="text" placeholder="W" class="form-control r_p width  num_only" id="width" name="width[]" onchange="vlm_calculation();" value="<?php echo $width_dimension2[$key]; ?>" autocomplete="off" /><span>X</span>
															</div>
															<div class="volumetric_width">
																<input type="text" placeholder="H" class="form-control r_p height num_only" id="height" name="height[]" onchange="vlm_calculation();" value="<?php echo $height_dimension3[$key]; ?>" autocomplete="off" /><span>X</span>
															</div>
															<div class="volumetric_width">
																<input type="text" placeholder="Q" class="form-control r_p quantity num_only" id="quantity" name="quantity[]" onchange="vlm_calculation();" value="<?php echo $quantity_dimension4[$key]; ?>" autocomplete="off" /><span>=</span>
															</div>
															<div class="volumetric_width">
																<input type="text" placeholder="" class="form-control r_p weight num_only" id="weight " name="weight[]" onchange="vlm_calculation();" readonly />
															</div>
															<div class="volumetric_width">
																<input type="text" class="form-control  r_p  volume_weight num_only" id="volume_weight" name="volume_weight[]" onchange="vlm_calculation();" readonly />
															</div>

															<?php
															if ($count < 2) {
															?>
																<div class="volumetric_width">
																	<a href="javascript:void(0);" class="add_button" title="Add field"> <img src="icons/pluss.png" class="dimen_img" /></a>
																</div>
															<?php

															} else { ?>
																<div class="volumetric_width">
																	<a href="javascript:void(0);" class="remove" title="Add field"> <img src="icons/minus.png" class="dimen_img" /></a>
																</div>

															<?php
															}
															?>
														</div>
													<?php
													}
													?>

												</div>
												<div class="main_vlm_box">
													<div class="vlm_total_val">
														<input type="text" placeholder="" class="form-control r_p v_weight" id="v_weight" name="v_weight[]" onchange="vlm_calculation();" readonly />
													</div>
												</div>

											</div>
									</div>
									<div class="booking-split-col ew-booking-section payment-gst-panel">
											<h2 class="ew-card-section-title">Payment Information</h2>
											<table class="table table-bordered payment-charges-table">
												<thead>
													<tr>
														<th>Particulars</th>
														<th class="text-right">Rate</th>
														<th class="text-right">Amount (INR)</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td>Freight</td>
														<td><input type="text" name="frieght_rate" id="frieght_rate" value="<?php echo $row['frieght_rate']; ?>" class="form-control text-right" onchange="calc_charge_amt();" autocomplete="off" /></td>
														<td><input type="text" name="frieght_amount" id="frieght_amount" value="<?php echo $row['frieght_amount']; ?>" class="form-control  text-right calculation" onchange="sum_amount();" readonly autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Loading / Unloading Charges</td>
														<td><input type="text" name="loading_unload_rate" id="loading_unload_rate" class="form-control text-right" value="<?php echo $row['loading_unloading_rate']; ?>" autocomplete="off" /></td>
														<td><input type="text" name="loading_unload_chrg" id="loading_unload_chrg" value="<?php echo $row['loading_unloading_amount']; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Crane / Fork Lift Charges</td>
														<td><input type="text" name="crane_forklift_rate" id="crane_forklift_rate" class="form-control text-right" value="<?php echo $row['crane_fork_lift_rate']; ?>" autocomplete="off" /></td>
														<td><input type="text" name="crane_forklift_chrg" id="crane_forklift_chrg" value="<?php echo $row['crane_fork_lift_amount']; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>C.O.D</td>
														<td><input type="text" name="cod_rate" id="cod_rate" class="form-control text-right" value="<?php echo $row['cod_rate']; ?>" autocomplete="off" /></td>
														<td><input type="text" name="cod_amount" id="cod_amount" value="<?php echo $row['cod_amount']; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>F.O.V</td>
														<td><input type="text" name="fov_rate" id="fov_rate" class="form-control text-right" value="<?php echo $row['fov_rate']; ?>" autocomplete="off" /></td>
														<td><input type="text" name="fov_amount" id="fov_amount" value="<?php echo $row['fov_amount']; ?>" class="form-control text-right calculation" onchange="sum_amount();" readonly autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Doc.Charges</td>
														<td><input type="text" name="doc_rate" id="doc_rate" value="<?php echo $row['doc_charges']; ?>" class="form-control text-right" autocomplete="off" /></td>
														<td><input type="text" name="doc_amount" id="doc_amount" value="<?php echo $row['doc_amount']; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Cartage</td>
														<td><input type="text" name="cartage_rate" id="cartage_rate" value="<?php echo $row['cartage_rate']; ?>" class="form-control text-right" autocomplete="off" /></td>
														<td><input type="text" name="cartage_amount" id="cartage_amount" value="<?php echo $row['cartage_amount']; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Labour Handling</td>
														<td><input type="text" name="labour_rate" id="labour_rate" value="<?php echo $row['labour_handling_rate']; ?>" class="form-control text-right" autocomplete="off" /></td>
														<td><input type="text" name="labour_amount" id="labour_amount" value="<?php echo $row['labour_handling_amount']; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Mamul Charges</td>
														<td class="rate-empty text-right">—</td>
														<td><input type="text" name="mamul_charge" id="mamul_charge" value="<?php echo htmlspecialchars($row['mamul_charge'] ?? ''); ?>" onchange="sum_amount();" class="form-control text-right calculation" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Vehicle Halting Charges</td>
														<td class="rate-empty text-right">—</td>
														<td><input type="text" name="vehicle_halting_charge" id="vehicle_halting_charge" value="<?php echo htmlspecialchars($row['vehicle_halting_charge'] ?? ''); ?>" onchange="sum_amount();" class="form-control text-right calculation" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Vehicle Loading / Unloading</td>
														<td class="rate-empty text-right">—</td>
														<td><input type="text" name="vehicle_loading_unloading" id="vehicle_loading_unloading" value="<?php echo htmlspecialchars($row['vehicle_loading_unloading'] ?? ''); ?>" onchange="sum_amount();" class="form-control text-right calculation" autocomplete="off" /></td>
													</tr>
													<tr id="rajdhani_ex" style="display: none;">
														<td>Rajdhani Charges</td>
														<td class="rate-empty text-right">—</td>
														<td><input type="text" name="rajdhani_charges" id="rajdhani_charges" value="<?php echo $row['rajdhani_charges']; ?>" class="text-right form-control calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Any Other charges</td>
														<td><input type="text" name="other_rate" id="other_rate" value="<?php echo $row['other_charge_rate']; ?>" class="form-control text-right" autocomplete="off" /></td>
														<td><input type="text" name="other_amount" id="other_amount" value="<?php echo $row['other_charge_amount']; ?>" class="text-right form-control calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
												</tbody>
											</table>

											<div class="gst-config-block">
												<div class="form-group">
													<label for="gst_tax_id">GST Tax Profile</label>
													<select name="gst_tax_id" id="gst_tax_id" class="form-control">
														<option value="">Select GST Profile</option>
														<?php foreach ($gst_tax_profiles as $gst_profile_row) { ?>
															<option value="<?php echo (int) $gst_profile_row['gst_tax_id']; ?>"
																data-code="<?php echo htmlspecialchars($gst_profile_row['tax_code']); ?>"
																data-gst-rate="<?php echo htmlspecialchars($gst_profile_row['gst_rate']); ?>"
																data-cgst-rate="<?php echo htmlspecialchars($gst_profile_row['cgst_rate']); ?>"
																data-sgst-rate="<?php echo htmlspecialchars($gst_profile_row['sgst_rate']); ?>"
																data-igst-rate="<?php echo htmlspecialchars($gst_profile_row['igst_rate']); ?>"
																data-cess-rate="<?php echo htmlspecialchars($gst_profile_row['cess_rate']); ?>"
																<?php if ((int) $gst_profile_row['gst_tax_id'] === $default_gst_tax_id) echo 'selected'; ?>>
																<?php echo htmlspecialchars($gst_profile_row['tax_code'] . ' - ' . $gst_profile_row['tax_name']); ?>
															</option>
														<?php } ?>
													</select>
												</div>
												<div class="form-group">
													<label for="gst_type">GST Type</label>
													<select name="gst_type" id="gst_type" class="form-control">
														<option value="auto" <?php if ($saved_gst_type === 'auto') echo 'selected'; ?>>Auto (Origin vs Destination)</option>
														<option value="intra" <?php if ($saved_gst_type === 'intra') echo 'selected'; ?>>Intra-State (CGST + SGST)</option>
														<option value="inter" <?php if ($saved_gst_type === 'inter') echo 'selected'; ?>>Inter-State (IGST)</option>
														<option value="exempt" <?php if ($saved_gst_type === 'exempt') echo 'selected'; ?>>Exempt</option>
														<option value="non_gst" <?php if ($saved_gst_type === 'non_gst') echo 'selected'; ?>>Non-GST</option>
													</select>
													<small id="gst_type_hint" class="gst-breakup-note"></small>
												</div>
											</div>

											<div class="gst-breakup-block">
												<div class="gst-breakup-title">GST – Tax Breakup</div>
												<table class="table table-bordered table-condensed gst-breakup-table">
													<thead>
														<tr>
															<th>Component</th>
															<th class="text-right">Rate %</th>
															<th class="text-right">Amount (INR)</th>
														</tr>
													</thead>
													<tbody>
														<tr>
															<td>Taxable Value</td>
															<td class="text-right rate-empty">—</td>
															<td class="text-right amt-col"><span id="disp_taxable_value"><?php echo number_format((float) ($row['taxable_value'] ?? 0), 2); ?></span></td>
														</tr>
														<tr id="row_cgst">
															<td>CGST</td>
															<td class="text-right"><span id="disp_cgst_rate"><?php echo number_format((float) ($row['cgst_rate'] ?? 0), 2); ?></span></td>
															<td class="text-right amt-col"><span id="disp_cgst_amount"><?php echo number_format((float) ($row['cgst_amount'] ?? 0), 2); ?></span></td>
														</tr>
														<tr id="row_sgst">
															<td>SGST / UTGST</td>
															<td class="text-right"><span id="disp_sgst_rate"><?php echo number_format((float) ($row['sgst_rate'] ?? 0), 2); ?></span></td>
															<td class="text-right amt-col"><span id="disp_sgst_amount"><?php echo number_format((float) ($row['sgst_amount'] ?? 0), 2); ?></span></td>
														</tr>
														<tr id="row_igst">
															<td>IGST</td>
															<td class="text-right"><span id="disp_igst_rate"><?php echo number_format((float) ($row['igst_rate'] ?? 0), 2); ?></span></td>
															<td class="text-right amt-col"><span id="disp_igst_amount"><?php echo number_format((float) ($row['igst_amount'] ?? 0), 2); ?></span></td>
														</tr>
														<tr id="row_cess">
															<td>Cess</td>
															<td class="text-right"><span id="disp_cess_rate"><?php echo number_format((float) ($row['cess_rate'] ?? 0), 2); ?></span></td>
															<td class="text-right amt-col"><span id="disp_cess_amount"><?php echo number_format((float) ($row['cess_amount'] ?? 0), 2); ?></span></td>
														</tr>
														<tr>
															<td>Total GST</td>
															<td class="text-right"><span id="disp_gst_rate"><?php echo number_format((float) ($row['gst_rate'] ?? 0), 2); ?></span></td>
															<td class="text-right amt-col"><span id="disp_gst_amount"><?php echo number_format((float) ($row['gst_amount'] ?? 0), 2); ?></span></td>
														</tr>
														<tr>
															<td>Grand Total</td>
															<td class="text-right rate-empty">—</td>
															<td class="text-right amt-col"><span id="disp_grand_total"><?php echo number_format((float) ($row['total'] ?? 0), 2); ?></span></td>
														</tr>
													</tbody>
												</table>
												<input type="hidden" name="gst_rate" id="gst_rate" value="<?php echo $row['gst_rate'] ?? ''; ?>" />
												<input type="hidden" name="gst_amount" id="gst_amount" value="<?php echo $row['gst_amount'] ?? ''; ?>" />
												<input type="hidden" name="total" id="total" value="<?php echo $row['total'] ?? ''; ?>" />
												<small class="gst-breakup-note">GST type is auto-selected from origin and destination state.</small>
											</div>

											<div class="amount-in-words-block">
												<label for="amount_in_words">Amount In Words</label>
												<textarea name="amount_in_words" id="amount_in_words" rows="2" readonly class="form-control"><?php echo $row['total_words']; ?></textarea>
											</div>
									</div>
								</div>
								</div>

								<div class="row booking-footer-section">
									<div class="col-md-6">
										<div class="booking-panel upload-panel">
											<h3 class="booking-panel-title">Attachments</h3>
											<p class="booking-panel-hint">Drag & drop or click to upload</p>
											<div class="upload-dropzone" id="upload_dropzone">
												<div class="upload-dropzone-inner">
													<i class="fa fa-cloud-upload"></i>
													<span>Drop files or <strong>browse</strong></span>
													<small>Images, PDF & documents</small>
												</div>
												<input type="file" id="upload_dropzone_input" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" class="upload-file-input">
											</div>
											<div class="upload-preview-list file-container" id="upload_preview_list">
												<?php
												if ($_REQUEST['key'] != '') {
													$transaction_image_query = "select * from transaction_images_" . $m . "_" . $y . " where md5(transaction_id) = '" . $_REQUEST['key'] . "' and status=0";
													$transaction_image_result = mysqli_query($conn, $transaction_image_query);
													$k = 1;
													while ($transaction_image_row = mysqli_fetch_array($transaction_image_result)) {
														$attach_file = $transaction_image_row['attachment'];
														$attach_ext = strtolower(pathinfo($attach_file, PATHINFO_EXTENSION));
														$is_image = in_array($attach_ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'));
												?>
														<div class="upload-item file-group" id="file-no<?php echo $k; ?>" data-file-no="<?php echo $k; ?>">
															<div class="upload-item-preview img_pre_div">
																<img src="<?php echo $is_image ? 'invoice_image/' . htmlspecialchars($attach_file) : 'images/no_image.png'; ?>"
																	class="image_preview<?php echo $is_image ? '' : ' doc-placeholder'; ?>"
																	id="image_preview<?php echo $k; ?>"
																	alt="">
															</div>
															<div class="upload-item-body">
																<span class="upload-item-name"><?php echo htmlspecialchars($attach_file); ?></span>
																<span class="upload-item-sub">Existing attachment</span>
																<input type="file" id="file_receipt<?php echo $k; ?>" name="file_receipt[]" class="filestyle upload-file-input" data-id="<?php echo $k; ?>" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
															</div>
															<div class="upload-item-actions remov">
																<button type="button" data-id="<?php echo $k; ?>" id="<?php echo $transaction_image_row['attachment_id']; ?>" class="btn btn-link upload-item-remove remove-image" title="Remove"><i class="fa fa-times"></i></button>
															</div>
														</div>
												<?php
														$k++;
													}
												}
												?>
											</div>
											<button id="add_more" type="button" class="btn btn-default btn-sm upload-add-btn"><i class="fa fa-plus"></i> Add file</button>
										</div>
									</div>
									<div class="col-md-6">
										<div class="booking-panel signature-panel">
											<h3 class="booking-panel-title">Consignor Signature</h3>
											<p class="booking-panel-hint">Sign below using mouse or touch</p>
											<img src="<?php echo $row['consiner_signature'] ?? ''; ?>" id="signature_image" style="display:none" alt="">
											<div id="content">
												<div id="signatureparent" class="signature-canvas-wrap">
													<div id="signature"></div>
												</div>
												<div id="display_signature" class="signature-saved-preview"></div>
												<div id="tools" class="signature-tools"></div>
											</div>
											<input type="hidden" name="signature" id="signature_val">
										</div>
									</div>
								</div>

						</form>
							</div>
							<div class="ew-form-footer">
								<button class="ew-btn-v2 ew-btn-v2-outline btn-reset btn-cancel" type="button">Cancel</button>
								<button class="ew-btn-v2 ew-btn-v2-primary save-button" type="button" id="save"><i class="fa fa-save"></i> Submit Manual Booking</button>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>


		<?php require_once("include/footer.php"); ?>
	</div>

	<script src="include/calculation.js"></script>
	<script type="text/javascript">
		window.PKG_OPTIONS_HTML = <?php echo json_encode($pkg_option ?? '<option value="">Select Package Type</option>'); ?>;
		var PKG_MAX_ROWS = 5;

		function buildPackageRowHtml(idx) {
			return '<tr class="pkg-data-row" data-row-index="' + idx + '">' +
				'<td class="text-center pkg-sno">' + idx + '</td>' +
				'<td id="pkg_req"><input type="text" name="no_of_pkg[]" id="no_of_pkg' + idx + '" class="form-control num_only text-right pkg-row-input" inputmode="numeric" autocomplete="off" onpaste="return ewNumericPaste(event,this);"></td>' +
				'<td id="typ_req"><select name="type_of_pkg[]" id="type_of_pkg' + idx + '" class="form-control pkg-row-select">' + window.PKG_OPTIONS_HTML + '</select></td>' +
				'<td id="inv_req"><input type="text" name="party_invoice[]" id="party_invoice' + idx + '" class="form-control" onchange="party_invoice_details();" onkeyup="party_invoice_details();" autocomplete="off"></td>' +
				'<td><div class="date-input-inside"><input type="text" id="party_invoice_date' + idx + '" name="party_invoice_date[]" class="form-control ew-date-field party-invoice-date" autocomplete="off" data-ew-datepicker="1" data-date-format="dd-mm-yyyy" data-end-date="today"><i class="fa fa-calendar date-field-icon" aria-hidden="true"></i></div></td>' +
				'<td><input type="text" name="content[]" id="content' + idx + '" class="form-control" autocomplete="off"></td>' +
				'<td><input type="text" name="qty[]" id="qty' + idx + '" class="form-control num_only text-right" autocomplete="off" inputmode="numeric" onpaste="return ewNumericPaste(event,this);"></td>' +
				'<td><input type="text" name="gross[]" id="gross' + idx + '" class="form-control text-right num_only" inputmode="numeric" autocomplete="off" onpaste="return ewNumericPaste(event,this);"></td>' +
				'<td id="chrg_req"><input type="text" name="charged[]" id="charged' + idx + '" class="form-control text-right num_only charged_w" onkeyup="calculate_charge_weight();" inputmode="numeric" autocomplete="off" onpaste="return ewNumericPaste(event,this);"></td>' +
				'<td class="text-center pkg-row-action"></td>' +
				'</tr>';
		}

		function refreshPackageRowActions() {
			var $rows = $('#package-tbody .pkg-data-row');
			var count = $rows.length;
			$rows.each(function(idx) {
				var isLast = (idx === count - 1);
				var buttons = '';
				if (!isLast) {
					buttons = '<button type="button" class="pkg-row-btn is-remove" title="Remove row"><i class="fa fa-minus"></i></button>';
				} else if (count > 1) {
					if (count < PKG_MAX_ROWS) {
						buttons = '<button type="button" class="pkg-row-btn is-add" title="Add row"><i class="fa fa-plus"></i></button>';
					} else {
						buttons = '<button type="button" class="pkg-row-btn is-remove" title="Remove row"><i class="fa fa-minus"></i></button>';
					}
				} else if (count < PKG_MAX_ROWS) {
					buttons = '<button type="button" class="pkg-row-btn is-add" title="Add row"><i class="fa fa-plus"></i></button>';
				}
				$(this).find('.pkg-row-action').html(buttons);
			});
		}

		function renumberPackageRows() {
			$('#package-tbody .pkg-data-row').each(function(i) {
				var idx = i + 1;
				var $row = $(this);
				$row.attr('data-row-index', idx);
				$row.find('.pkg-sno').text(idx);
				$row.find('[name="no_of_pkg[]"]').attr('id', 'no_of_pkg' + idx);
				$row.find('[name="type_of_pkg[]"]').attr('id', 'type_of_pkg' + idx);
				$row.find('[name="party_invoice[]"]').attr('id', 'party_invoice' + idx);
				$row.find('[name="party_invoice_date[]"]').attr('id', 'party_invoice_date' + idx);
				$row.find('[name="content[]"]').attr('id', 'content' + idx);
				$row.find('[name="qty[]"]').attr('id', 'qty' + idx);
				$row.find('[name="gross[]"]').attr('id', 'gross' + idx);
				$row.find('[name="charged[]"]').attr('id', 'charged' + idx);
			});
		}

		function addPackageRow() {
			var count = $('#package-tbody .pkg-data-row').length;
			if (count >= PKG_MAX_ROWS) {
				return;
			}
			var idx = count + 1;
			var $row = $(buildPackageRowHtml(idx));
			$('#package-tbody').append($row);
			if (typeof initEwDatepickers === 'function') {
				initEwDatepickers($row);
			}
			refreshPackageRowActions();
			syncPackageRowRequired();
		}

		function removePackageRow($row) {
			if ($('#package-tbody .pkg-data-row').length <= 1) {
				return;
			}
			var $date = $row.find('.party-invoice-date');
			if ($date.length && $date.data('datepicker')) {
				$date.datepicker('destroy');
			}
			$row.remove();
			renumberPackageRows();
			refreshPackageRowActions();
			calculate_charge_weight();
			syncPackageRowRequired();
			party_invoice_details();
		}

		function syncPackageRowRequired() {
			$('#package-tbody .pkg-data-row').each(function(i) {
				var idx = i + 1;
				var $pkg = $('#no_of_pkg' + idx);
				var $type = $('#type_of_pkg' + idx);
				var $inv = $('#party_invoice' + idx);
				var $charged = $('#charged' + idx);
				var hasRow = $.trim($pkg.val()) !== '' ||
					$.trim($inv.val()) !== '' ||
					$.trim($('#content' + idx).val() || '') !== '' ||
					$.trim($('#gross' + idx).val() || '') !== '' ||
					$.trim($charged.val() || '') !== '';
				if (idx === 1 || hasRow) {
					if (idx === 1) {
						$pkg.attr('required', 'required');
						$type.attr('required', 'required');
						$inv.attr('required', 'required');
					} else if (hasRow) {
						$type.attr('required', 'required');
					}
				} else {
					$type.removeAttr('required').removeClass('error');
					$('label.error[for="type_of_pkg' + idx + '"]').remove();
				}
			});
		}

		var gstProfiles = <?php echo $gst_profiles_json ?: '[]'; ?>;
		var cachedConsignorBranches = [];
		var cachedConsigneeBranches = [];
		var bookingClients = <?php echo $booking_clients_json ?: '[]'; ?>;

		function escPartyName(v) {
			if (v === null || v === undefined) return '';
			return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		function initPartyNameSelect($el, placeholder, disabled) {
			if (!$el || !$el.length) return;
			if (typeof $.fn.select2 !== 'function') return;
			try {
				if ($el.data('select2')) {
					$el.select2('destroy');
				}
			} catch (e) {}
			$el.prop('disabled', !!disabled);
			try {
				$el.select2({
					width: '100%',
					placeholder: placeholder || 'Select',
					allowClear: true
				});
				if ($el.data('select2')) {
					$el.select2('enable', !disabled);
				}
			} catch (e) {}
		}

		function partySelectVal($el) {
			if ($el.data('select2')) {
				return $el.select2('val');
			}
			return $el.val();
		}

		function fillConsignorNameSelect(selected) {
			var $el = $('#consignor_name');
			if (!$el.length) return;
			if (bookingClients && bookingClients.length) {
				var html = '<option value="">Select consignor</option>';
				$.each(bookingClients, function(i, c) {
					html += '<option value="' + escPartyName(c.id) + '"' + (String(selected) === String(c.id) ? ' selected' : '') + '>' + escPartyName(c.name) + '</option>';
				});
				try {
					if ($el.data('select2')) $el.select2('destroy');
				} catch (e) {}
				$el.html(html);
			}
			initPartyNameSelect($el, 'Select consignor', false);
			if (selected) {
				$el.val(String(selected));
				if ($el.data('select2')) {
					$el.select2('val', String(selected));
				}
				$('#consignor').val(String(selected));
			}
		}

		function fillConsigneeNameSelect(rows, selected, disabled) {
			var html = '<option value="">Select consignee</option>';
			$.each(rows || [], function(i, c) {
				html += '<option value="' + escPartyName(c.id) + '"' + (String(selected) === String(c.id) ? ' selected' : '') + '>' + escPartyName(c.name) + '</option>';
			});
			var $el = $('#consignee_name');
			try {
				if ($el.data('select2')) $el.select2('destroy');
			} catch (e) {}
			$el.html(html);
			initPartyNameSelect($el, 'Select consignee', disabled);
			if (selected && !disabled) {
				$el.val(String(selected));
				if ($el.data('select2')) {
					$el.select2('val', String(selected));
				}
				$('#consignee').val(String(selected));
			}
		}

		function loadMappedConsignees(consignorId, selected) {
			if (!consignorId) {
				fillConsigneeNameSelect([], '', true);
				$('#consignee').val('');
				return;
			}
			$.getJSON('fetch_details.php', {
				cmd: 'get_mapped_consignees',
				consignor: consignorId
			}, function(rows) {
				rows = rows || [];
				var hasMapped = rows.length > 0;
				fillConsigneeNameSelect(rows, hasMapped ? (selected || '') : '', !hasMapped);
				if (!hasMapped) {
					$('#consignee').val('');
				}
			});
		}

		function parseAmount(val) { return parseFloat(val) || 0; }
		function roundRupee(val) { return Math.round(parseAmount(val)); }
		function formatMoney(val) { return String(roundRupee(val)); }
		function formatRate(val) { return parseAmount(val).toFixed(2); }

		function getOriginStateId() {
			return parseInt($('#origin option:selected').data('state'), 10) || 0;
		}
		function getDestinationStateId() {
			return parseInt($('#destination option:selected').data('state'), 10) || 0;
		}
		function syncRouteStateFields() {
			var originState = getOriginStateId();
			var destState = getDestinationStateId();
			$('#origin_state_id').val(originState || '');
			$('#destination_state_id').val(destState || '');
			$('#bill_to_state_id').val(destState || '');
			return { originState: originState, destState: destState };
		}
		function determineGstTypeFromRoute() {
			var states = syncRouteStateFields();
			if (!states.originState || !states.destState) return '';
			return (parseInt(states.originState, 10) === parseInt(states.destState, 10)) ? 'intra' : 'inter';
		}
		function syncGstTypeLock() {
			var profile = getSelectedGstProfile();
			var routeType = determineGstTypeFromRoute();
			var $gstType = $('#gst_type');
			if (profile && String(profile.tax_code).toUpperCase() === 'GST0') {
				$gstType.val('exempt').prop('disabled', true);
				return 'exempt';
			}
			if (routeType) {
				$gstType.val(routeType).prop('disabled', true);
				return routeType;
			}
			$gstType.prop('disabled', false);
			return $gstType.val();
		}
		function determineGstType() {
			var selected = syncGstTypeLock();
			if (selected === 'exempt' || selected === 'non_gst') return selected;
			var routeType = determineGstTypeFromRoute();
			if (routeType) return routeType;
			if (selected === 'intra' || selected === 'inter') return selected;
			return '';
		}
		function getSelectedGstProfile() {
			var $opt = $('#gst_tax_id option:selected');
			if (!$opt.length || !$opt.val()) return null;
			return {
				gst_tax_id: $opt.val(),
				tax_code: $opt.data('code') || '',
				gst_rate: parseAmount($opt.data('gst-rate')),
				cgst_rate: parseAmount($opt.data('cgst-rate')),
				sgst_rate: parseAmount($opt.data('sgst-rate')),
				igst_rate: parseAmount($opt.data('igst-rate')),
				cess_rate: parseAmount($opt.data('cess-rate'))
			};
		}
		function updateGstTypeHint(resolvedType) {
			var states = syncRouteStateFields();
			var hint = '';
			if (!states.originState || !states.destState) {
				hint = 'Select origin and destination to auto-determine GST type and breakup.';
			} else if (resolvedType === 'intra') {
				hint = 'Auto: Same origin & destination state → Intra-State (CGST + SGST)';
			} else if (resolvedType === 'inter') {
				hint = 'Auto: Different origin & destination state → Inter-State (IGST)';
			}
			$('#gst_type_hint').text(hint);
		}
		function calculateTaxableValue() {
			var fields = [
				'#frieght_amount', '#doc_amount', '#mamul_charge', '#vehicle_halting_charge',
				'#vehicle_loading_unloading', '#other_amount', '#rajdhani_charges',
				'#loading_unload_chrg', '#crane_forklift_chrg', '#cod_amount', '#fov_amount',
				'#cartage_amount', '#labour_amount'
			];
			var total = 0;
			fields.forEach(function(selector) { total += parseAmount($(selector).val()); });
			return roundRupee(total);
		}
		function applyGstBreakupDisplay(breakup) {
			$('#taxable_value').val(formatMoney(breakup.taxable));
			$('#gst_tax_code').val(breakup.tax_code || '');
			$('#cgst_rate').val(formatRate(breakup.cgst_rate));
			$('#sgst_rate').val(formatRate(breakup.sgst_rate));
			$('#igst_rate').val(formatRate(breakup.igst_rate));
			$('#cess_rate').val(formatRate(breakup.cess_rate));
			$('#cgst_amount').val(formatMoney(breakup.cgst_amount));
			$('#sgst_amount').val(formatMoney(breakup.sgst_amount));
			$('#igst_amount').val(formatMoney(breakup.igst_amount));
			$('#cess_amount').val(formatMoney(breakup.cess_amount));
			$('#gst_rate').val(formatRate(breakup.gst_rate));
			$('#gst_amount').val(formatMoney(breakup.gst_amount));
			$('#disp_taxable_value').text(formatMoney(breakup.taxable));
			$('#disp_cgst_rate').text(formatRate(breakup.cgst_rate));
			$('#disp_cgst_amount').text(formatMoney(breakup.cgst_amount));
			$('#disp_sgst_rate').text(formatRate(breakup.sgst_rate));
			$('#disp_sgst_amount').text(formatMoney(breakup.sgst_amount));
			$('#disp_igst_rate').text(formatRate(breakup.igst_rate));
			$('#disp_igst_amount').text(formatMoney(breakup.igst_amount));
			$('#disp_cess_rate').text(formatRate(breakup.cess_rate));
			$('#disp_cess_amount').text(formatMoney(breakup.cess_amount));
			$('#disp_gst_rate').text(formatRate(breakup.gst_rate));
			$('#disp_gst_amount').text(formatMoney(breakup.gst_amount));
			$('#disp_grand_total').text(formatMoney(breakup.grand_total));
			$('#row_cgst, #row_sgst').toggle(breakup.resolved_type === 'intra');
			$('#row_igst').toggle(breakup.resolved_type === 'inter');
			$('#row_cess').toggle(parseAmount(breakup.cess_rate) > 0 || parseAmount(breakup.cess_amount) > 0);
		}
		function calculateGstBreakup() {
			var taxable = calculateTaxableValue();
			var resolvedType = determineGstType();
			updateGstTypeHint(resolvedType);
			var profile = getSelectedGstProfile();
			var breakup = {
				taxable: taxable, tax_code: profile ? profile.tax_code : '', resolved_type: resolvedType,
				gst_rate: 0, cgst_rate: 0, sgst_rate: 0, igst_rate: 0, cess_rate: 0,
				cgst_amount: 0, sgst_amount: 0, igst_amount: 0, cess_amount: 0,
				gst_amount: 0, grand_total: taxable
			};
			if (!profile || !resolvedType || resolvedType === 'exempt' || resolvedType === 'non_gst') {
				applyGstBreakupDisplay(breakup);
				return breakup;
			}
			breakup.cess_rate = profile.cess_rate;
			breakup.gst_rate = profile.gst_rate;
			if (resolvedType === 'intra') {
				breakup.cgst_rate = profile.cgst_rate;
				breakup.sgst_rate = profile.sgst_rate;
				breakup.cgst_amount = roundRupee(taxable * profile.cgst_rate / 100);
				breakup.sgst_amount = roundRupee(taxable * profile.sgst_rate / 100);
			} else if (resolvedType === 'inter') {
				breakup.igst_rate = profile.igst_rate;
				breakup.igst_amount = roundRupee(taxable * profile.igst_rate / 100);
			}
			if (profile.cess_rate > 0) {
				breakup.cess_amount = roundRupee(taxable * profile.cess_rate / 100);
			}
			breakup.gst_amount = roundRupee(breakup.cgst_amount + breakup.sgst_amount + breakup.igst_amount + breakup.cess_amount);
			breakup.grand_total = roundRupee(taxable + breakup.gst_amount);
			applyGstBreakupDisplay(breakup);
			return breakup;
		}
		function refreshGstCalculation() {
			var breakup = calculateGstBreakup();
			$('#total').val(formatMoney(breakup.grand_total));
			get_total();
		}
		$(document).on('change', '#gst_tax_id, #gst_type, #origin, #destination', function() {
			if ($(this).attr('id') === 'gst_type' && $(this).prop('disabled')) return;
			refreshGstCalculation();
		});

		function populateBranchDropdown(selectId, branches, excludeBranchId) {
			var $sel = $(selectId);
			var divId = (selectId === '#consignor_branch') ? '#consignor_branch_div' : '#consignee_branch_div';
			var currentVal = partySelectVal($sel);
			try {
				if ($sel.data('select2')) $sel.select2('destroy');
			} catch (e) {}
			$sel.html('<option value="">Select Branch</option>');
			if (!branches || !branches.length) {
				$(divId).hide();
				return;
			}
			$.each(branches, function(i, row) {
				var isExcluded = excludeBranchId && String(row.client_branch_id) === String(excludeBranchId);
				var $opt = $('<option></option>').val(row.client_branch_id).text(row.branch_name).prop('disabled', isExcluded);
				if (isExcluded) $opt.text(row.branch_name + ' (selected on other party)');
				$sel.append($opt);
			});
			$(divId).show();
			initPartyNameSelect($sel, 'Select Branch', false);
			if (currentVal && $sel.find('option[value="' + currentVal + '"]:not(:disabled)').length) {
				$sel.select2('val', String(currentVal));
			} else if ($sel.data('select2')) {
				$sel.select2('val', '');
			}
		}
		function loadClientBranches(companyId, party, callback) {
			if (!companyId) return;
			$.ajax({
				url: 'fetch_details.php', type: 'GET', dataType: 'json',
				data: { cmd: 'get_client_branches', company_id: companyId },
				success: function(branches) {
					if (party === 'consignor') {
						cachedConsignorBranches = branches || [];
						populateBranchDropdown('#consignor_branch', cachedConsignorBranches, $('#consignee_branch').val());
					} else {
						cachedConsigneeBranches = branches || [];
						populateBranchDropdown('#consignee_branch', cachedConsigneeBranches, $('#consignor_branch').val());
					}
					if (callback) callback(branches);
				}
			});
		}

		//Auto Calculation Part
		var ftl_flag = '<?php echo $ftl_type; ?>';
		if (ftl_flag != '') {
			$("#ftl_menu").show();
		}

		refreshGstCalculation();

		//Train Type Selected
		var train_type_sel = $('#train_type_sel :selected').val();
		if (train_type_sel) {
			$("#rajdhani_ex").show();
		} else {

			$("#rajdhani_ex").hide();
		}


		//Show FTL Dropdown 

		$(document).on('change', '#mode_of_trasport', function() {
			//alert("change");
			var transport_type = $('#mode_of_trasport :selected').val();
			$('#train_type_sel').prop('selectedIndex', 0);

			//alert(transport_type);
			if (transport_type == '7') {
				$("#ftl_menu").show();
				$("#train_type").hide();
				// $('#other_train_field').empty(); // other train name
			} else if (transport_type == '2') {
				$("#train_type").show();
				$("#ftl_menu").hide();

			} else {
				$("#ftl_menu").hide();
				$("#train_type").hide();
				$("#rajdhani_ex").hide();
				// $('#other_train_field').empty(); // other train name
			}


			// $('#truck_type').val(sel_ids);
			// $('#select-payment-mode').addClass('show')

		});
		//End

		//FTL Type Dropdown

		$(document).on('change', '#dropp', function() {
			//alert("change");
			var sel_ids = $('#dropp :selected').text();
			///alert(sel_ids);

			$('#truck_type').val(sel_ids);
			$('#select-payment-mode').addClass('show')

		});



		//Show Train type in  Dropdown 
		$(document).on('change', '#train_type_sel', function() {

			var transport_type = $('#train_type_sel :selected').val();
			//  alert(transport_type);
			//  alert(get_train_type);
			if (transport_type == '1') {
				$("#rajdhani_ex").show();
				$('#other_train_field').empty();
			} else {
				$("#rajdhani_ex").hide();
				$('#other_train_field').html('<label class="control-label">Other Train Name</label><input type="text" name="other_train_name" id="other_train_name" class="form-control" placeholder="Enter Train Name" value="<?php echo htmlspecialchars($row['other_train_name'] ?? ''); ?>">');
			}
		});


		function handleSelectChange(event) {
			refreshGstCalculation();
		}

		//Charge Weight Part
		function calculate_charge_weight() {

			var titles = $('input[name^=charged]').map(function(idx, elem) {
				return $(elem).val();
			}).get();

			var res = titles.map(function(x) {
				return parseInt(x);
			});

			var unique_weight = res.filter(function(value) {
				return !Number.isNaN(value);
			});
			var total = 0;

			for (let i = 0; i < unique_weight.length; i++) {
				if (isNaN(unique_weight[i])) {
					total = total + 0;

				} else {

					total = total + unique_weight[i];
				}
			}
			//console.log(total);


			if (!isNaN(total)) {
				$('#cumulative_charged').val(total);

			}
			// ss();
			calc_charge_amt();
		}

		//End Charge Weight Part

		//AddZero Function    
		function addZeroes(num) {
			var num = Number(num);
			if (String(num).split(".").length < 2 || String(num).split(".")[1].length <= 2) {
				num = num.toFixed(2);
			}
			return num;
		}
		//End AddZero Function   

		//Fov Calculation 
		function fov_calc() {
			var fov = 0.2;
			var goods_val = $("#goods_dedared_value").val()
			fov_chrge = (fov / 100) * goods_val;
			if (!isNaN(fov_chrge)) {
				$("#fov_amount").val(addZeroes(fov_chrge));

				sum_amount();
			}

		}

		//End Fov

		//Calculate Amount
		function calc_charge_amt() {
			//alert("tr");
			var charge_weight1 = $('#cumulative_charged').val();

			var v_weight = $('#v_weight').val();

			//console.log('CH: '+charge_weight1 + "VLM: "+v_weight);

			var rate = $("#frieght_rate").val();

			if (parseFloat(charge_weight1) > parseFloat(v_weight)) {
				charge_weight = charge_weight1;
				//console.log("CHARGE",charge_weight);
			} else {
				charge_weight = v_weight;
				//console.log("Volume",charge_weight);
			}
			var total_amt = parseFloat(rate) * parseFloat(charge_weight);

			if (!isNaN(total_amt)) {
				$('#frieght_amount').val(addZeroes(total_amt));
				// $('#frieght_amount').keypress();
				sum_amount()
			}
		}


		//End Calculate Amount


		//Sum Amount
		function sum_amount() {
			var transport_type_gst = $('#mode_of_trasport :selected').val();
			var trainType = $("#train_type_sel :selected").val();
			var r_ch = $("#rajdhani_charges").val();
			if (transport_type_gst != 2 && trainType != 1 || transport_type_gst != 2) {
				r_ch = 0;
				$("#rajdhani_charges").val(r_ch);
			}
			var breakup = calculateGstBreakup();
			if (!isNaN(breakup.grand_total)) {
				$("#total").val(formatMoney(breakup.grand_total));
				get_total();
			}
		}

		//End Sum Amount


		// Payment in Words

		function get_total() {
			let sum = $('#total').val();
			//alert(sum);
			$.ajax({
				url: 'fetch_details.php',
				type: "post",
				data: {
					cmd: "get_amount_words",
					val: sum
				},
				success: function(result) {
					console.log(result);
					$('#amount_in_words').val(result);
				},
				error: function(jqxhr) {
					//alert(jqxhr.responseText);
				}
			});
		}
		//Auto Calculation Part End

		//Edit PartyInvoice Details

		//End
		// $(document).ready(function(){

		// 	console.log("Test OLd1,",load_party_inv);
		// });

		//Party Invoice Function
		function party_invoice_details() {
			console.log("Test OLd,", load_party_inv);
			let cmd = "check_consginor_invoice_no";
			let conr_id = $("#consignor").val();
			if (conr_id != "" && conr_id != null) {
				var all_party_invoice = $('input[name^=party_invoice]').map(function(idx, elem) {
					return $(elem).val();
				}).get();
				$.ajax({
					url: 'fetch_details.php',
					type: "GET",
					dataType: "JSON",
					data: {
						cmd: cmd,
						conr_id: conr_id,
						all_party_invoice: all_party_invoice
					},
					success: function(result_data) {
						//console.log(result_data);
						var form_name = $("#form_name").val();
						if (form_name == "edit_consignment_details_manual") {
							console.log("edit_form inside");
							if (result_data) {
								console.log("test", result_data);
								$.each(result_data, function(index, value) {

									if ($.inArray($.trim(all_party_invoice[index]), load_party_inv) == -1) {
										// console.log(" From DB : "+index);
										//console.log(" From Old Values : " + load_party_inv[index]);

										var adddd = index + 1;
										var party_invoice = '#party_invoice' + adddd;

										if (value == "EMPTY") {
											$(party_invoice).removeClass("invoice_exist invoice_valid").addClass("invoice_new");

										} else if (value == "NO") {
											$(party_invoice).removeClass("invoice_exist invoice_new").addClass("invoice_valid");

										} else {
											$(party_invoice).removeClass("invoice_valid invoice_new").addClass("invoice_exist");

										}
									} else {

									}


								});
							} else {
								console.log("no data found");
							}
						} else {
							if (result_data) {
								// console.log(result_data);
								$.each(result_data, function(index, value) {
									// console.log(" From DB : "+index);
									console.log(" From Old Values : " + load_party_inv[index]);

									var adddd = index + 1;
									var party_invoice = '#party_invoice' + adddd;

									if (value == "EMPTY") {
										$(party_invoice).removeClass("invoice_exist invoice_valid").addClass("invoice_new");

									} else if (value == "NO") {
										$(party_invoice).removeClass("invoice_exist invoice_new").addClass("invoice_valid");

									} else {
										$(party_invoice).removeClass("invoice_valid invoice_new").addClass("invoice_exist");

									}

								});
							} else {
								console.log("no data found");
							}
						}
					},
					error: function(jqxhr) {
						ewToast(jqxhr.responseText, 'error');
					}

				});
			}

		}
		//Party Invoice Function end

		// check duplicate grn no start
		function CheckDuplicateManualGrn() {
			const cmd = "check_grn_manual";
			const grn_id = $("#grn_no").val();
			const grn_dt = $("#grn_date").val();
			if (grn_id != "" && grn_id != null) {
				if (!isNaN(grn_id)) {
					$.ajax({
						url: 'fetch_details.php',
						type: "POST",
						dataType: "JSON",
						data: {
							cmd: cmd,
							grn_id_manual: grn_id,
						},
						success: function(result) {
							if (result == 1) {
								// console.log(result)
								$("#grn_no").addClass("invoice_exist");
								$("#grn_error").text("This GRN is already exist!").attr("style", "color:red");
								$("#save").attr("disabled", true);
								// $("#grn_no").val("");
							}
							if (result == "") {
								$("#grn_no").removeClass("invoice_exist");
								$("#grn_error").text("");
								$("#save").prop('disabled', false);
							}
						},
						error: function(jqxhr) {
							console.log(jqxhr.responseText);
						}

					});
				} else {
					$("#grn_no").addClass("invoice_exist");
					$("#grn_error").text("Numbers only allowed").attr("style", "color:red");
					$("#save").attr("disabled", true);
				}
			}

		}
		// check duplicate grn no Function End

		//Payment Fetch Client Charges Start
		function load_payment_info() {
			if ($('#destination').val() != "" && $('#destination').val() != null) {
				var consignor_and_consinee_des = $('#destination').val();
				var consignor_id = $('#consignor').val();
				var consignee_id = $('#consignee').val();
				var cmd = "client_charges_auto_fetch";
				$.ajax({
					url: 'fetch_details.php',
					type: "GET",
					dataType: "JSON",
					data: {
						consinee_dec_id: consignor_and_consinee_des,
						consignor_get_id: consignor_id,
						consignee__get_id: consignee_id,
						cmd: cmd
					},
					success: function(pay_inv_data) {
						console.log(pay_inv_data);
						if (pay_inv_data != "No_Destination") {
							console.log(pay_inv_data);
							$("#loading_unload_chrg").val(parseFloat(pay_inv_data.loading_unloading_chrgs).toFixed(2));
							$("#crane_forklift_chrg").val(parseFloat(pay_inv_data.crane_fork_lift_chrgs).toFixed(2));
							$("#doc_amount").val(parseFloat(pay_inv_data.doc_chrgs).toFixed(2));
							$("#labour_amount").val(parseFloat(pay_inv_data.labour_charges).toFixed(2));
							$("#other_amount").val(parseFloat(pay_inv_data.other_chrgs).toFixed(2));

							if ($("#mode_of_trasport").val() != "") {
								if ($("#mode_of_trasport").val() == 1) { // air
									$("#frieght_rate").val(parseFloat(pay_inv_data.air).toFixed(2));
								} else if ($("#mode_of_trasport").val() == 2) //train
								{
									$("#frieght_rate").val(parseFloat(pay_inv_data.train).toFixed(2));
								} else if ($("#mode_of_trasport").val() == 3) { // exp
									$("#frieght_rate").val(parseFloat(pay_inv_data.express).toFixed(2));
								} else if ($("#mode_of_trasport").val() == 5) { //local
									$("#frieght_rate").val(parseFloat(pay_inv_data.local_delivery).toFixed(2));
								} else if ($("#mode_of_trasport").val() == 8) { // ptl
									$("#frieght_rate").val(parseFloat(pay_inv_data.ptl).toFixed(2));
								} else {
									$("#frieght_rate").val(parseFloat("0.00").toFixed(2));
									$("#loading_unload_chrg").val("");
									$("#crane_forklift_chrg").val("");
									$("#doc_amount").val("");
									$("#labour_amount").val("");
									$("#other_amount").val("");
								}
							} else {
								$("#frieght_rate").val(parseFloat("0.00").toFixed(2));

							}
							calculate_charge_weight();
							sum_amount();

						} else {
							ewToast("Consignor Does Not Have That Destination", 'warning');
						}


					},
					error: function(jqxhr) {
						ewToast(jqxhr.responseText, 'error');
					}

				});
			}
		}
		//Payment Fetch End

		//Edit VLM Calculation
		var V_mode, T;

		$(function() {

			$('#mode_of_trasport').change(function() {
				V_mode1 = $('#mode_of_trasport').val();
				V_mode = V_mode1;

				T = $('#mode_of_trasport :selected').text();
				vlm_calculation();
			});

			var V_modee = $('#mode_of_trasport').find(":selected").val();
			if (V_modee !== "") {
				V_mode = V_modee;
				//alert(V_mode);
				vlm_calculation();
			} else {
				// alert('ff');
			}
		});

		function vlm_calculation() {
			var sum = 0;
			array_weight = [];
			var sum1 = 0;

			var arr = [];
			var totalprice = 0;

			$.each($(".df .dimensions_col"), function(index, element) {
				element = $(element);
				var lengthd = parseInt(element.find('.length').val());
				var width = parseInt(element.find('.width').val());
				var height = parseInt(element.find('.height').val());
				var quantity = parseInt(element.find('.quantity').val());
				var weight1 = lengthd * width * height;
				var weight2 = lengthd * width * height * quantity;
				// alert(weight1);
				// alert(weight2);
				// alert(V_mode);
				// element.find('.weight').val(weight2);
				var quant = parseInt(element.find('.weight').val());
				totalprice += Number(quant);
				element.find('.volume_weight').val(totalprice);
				var de = 1000000;
				var divide = parseInt(weight2) / parseInt(de);

				//convert to feet
				var feet = divide / 2;
				//convert cms to kgs 
				var cms = parseInt(lengthd) * parseInt(width) * parseInt(height) / 28000;
				//var cms = sum1 / 28000; 
				var cms_to_6times = cms * 6;

				//convert air to kgs 
				var air_kgs = parseInt(lengthd) * parseInt(width) * parseInt(height) / 5000;

				if (V_mode == '7') {
					// alert("BY SURFACE FTL");
					// alert(lengthd);
					if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
						var result = divide / 2; // CBM to Feet
						console.log("FTL: " + result)

						if (result > 10) {
							result;

						} else {
							result = 10;
						}

					} else {
						result = 0
					}
				} else if (V_mode == '8') {
					// alert("BY SURFACE PTL");
					if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
						var result = divide / 2; // CBM to Feet
						console.log("PTL: " + result)
						if (result != '') {
							if (result > 10) {
								result;

							} else {
								result = 10;
							}

						}
					} else {
						result = 0;
					}
				} else if (V_mode == '1') {
					if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
						var result = air_kgs * Number(element.find('.quantity').val());
					} else {
						result = 0;
					}
				} else if (V_mode == '2' || V_mode == '3' || V_mode == '4' || V_mode == '5' || V_mode == '6') {
					// alert("else");

					if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
						var result = cms_to_6times * Number(element.find('.quantity').val());
						console.log("Train Express Local Delivery" + result);
						$('.charged').show();
					} else {
						result = 0;
					}

				} else {
					// alert("else");
					if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
						var result = weight2;
						console.log("No Transport Selected" + result);
						$('.charged').show();
					} else {
						result = 0;
					}
				}
				console.log("Result :" + result.toFixed(0));
				if (!isNaN(result)) {
					console.log("total_wei", result);
					arr.push(result);
					element.find('.weight').val(result.toFixed(0));
					get_all_total(arr);


				}
			});

			function get_all_total(array_data) {

				var total_weight = 0;

				for (let i = 0; i < array_data.length; i++) {
					total_weight += array_data[i];
				}
				$(".volume_weight").val(total_weight.toFixed(0));
				var val_weight = $('#v_weight').val(total_weight.toFixed(0));

				// var chrg = $('#charged1').val();
				// if(chrg == 0 || chrg == "" || chrg < val_weight || val_weight > chrg){
				// 	var chrg = $('#charged1').val(total_weight.toFixed(0));
				// }

				calc_charge_amt();
				calculate_charge_weight();
			}
		}

		//End


		$(document).ready(function() {
			refreshPackageRowActions();
			calculate_charge_weight();
			fillConsignorNameSelect($('#consignor').val() || '');
			if ($('#consignor').val()) {
				loadMappedConsignees($('#consignor').val(), $('#consignee').val() || '');
			} else {
				fillConsigneeNameSelect([], '', true);
			}

			$(document).on('click', '.pkg-row-btn.is-add', function() {
				addPackageRow();
			});
			$(document).on('click', '.pkg-row-btn.is-remove', function() {
				removePackageRow($(this).closest('.pkg-data-row'));
			});

			var form_name = $("#form_name").val();
			load_party_inv = new Array();
			if (form_name == "edit_consignment_details_manual") {
				load_party_inv = $('input[name^=party_invoice]').map(function(idx, elem) {
					return $(elem).val();
				}).get();
			}
			console.log("Test OLd1,", load_party_inv);

			//Show FTL Dropdown 
			$(document).on('change', '#mode_of_trasport', function() {
				// /alert("change");
				var transport_type = $('#mode_of_trasport :selected').val();
				//alert(transport_type);
				if (transport_type == '7') {
					$("#ftl_menu").show();
				} else {
					$("#ftl_menu").hide();
				}
				//Payment AutoFetch Function
				load_payment_info()
				// $('#truck_type').val(sel_ids);
				// $('#select-payment-mode').addClass('show')

			});
			//End
			//FTL Type 
			$(document).on('change', '#dropp', function() {
				//alert("change");
				var sel_ids = $('#dropp :selected').text();
				//alert(sel_ids);

				$('#truck_type').val(sel_ids);
				$('#select-payment-mode').addClass('show')

			});
			//End

			$('.grn_no_popup').hide();

			function isChar(evt, element) {
				var charCode = (evt.which) ? evt.which : event.keyCode

				if ((charCode != 45 || $(element).val().indexOf('-') != -1) && // â€œ-â€ CHECK MINUS, AND ONLY ONE.
					(charCode != 46 || $(element).val().indexOf('.') != -1) && // â€œ.â€ CHECK DOT, AND ONLY ONE.
					(charCode < 48 || charCode > 57) && (charCode < 64 || charCode > 90))
					return false;
				//	return true;
			}
			$('#vehicle_no').keypress(function(event) {
				return isChar(event, this)
			});

			var role = '<?php echo $_SESSION['role']; ?>';
			var id = '<?php echo $_SESSION['user_id']; ?>';
			console.log(role);
			if (role == 'CL') {
				$('#consignor_name').attr("disabled", "disabled");
			}

			if (role == "CL") {
				$.ajax({
					async: false,
					url: 'fetch_details.php',
					type: "GET",
					dataType: "JSON",
					data: {
						cmd: "get_client_user_details",
						tbl_id: id
					},

					success: function(result) {
						console.log(result);

						setTimeout(function() {
							//$("#origin").val(result['city']).trigger("change"); 
							$("#origin").val(result['city']);

							$("#con_details").show();
							$("#consignor").val(result['client_id']);
							fillConsignorNameSelect(result['client_id']);
							loadMappedConsignees(result['client_id'], '');
							$('#address1').html(result['address1']);
							$('#address2').html(result['address2']);
							$('#city').html(result['city_name']);

							$('#state').html(result['state_name']);
							$('#pincode').html(result['pincode']);

							$('#phone').html(result['contact_no']);
							$('#gst_no').html(result['gst_no']);
							$('#consignee_name').focus();

						}, 300);


					},
					error: function(jqxhr) {
						console.log(jqxhr.responseText);
					}
				});
			}
			$(document).on('change', '#destination', function(e) {
				reset_consignee();
			});
			$(document).on('change', '#origin', function(e) {
				var id = $(this).val();
				reset_consignor();
				reset_consignee();
				$.ajax({
					// async: false,
					url: 'fetch_details.php',

					type: "GET",
					dataType: "JSON",

					data: {
						cmd: "get_destination_consignor",
						id: id
					},

					success: function(result) {
						// alert(result['destination']);
						setTimeout(function() {
							$('#destination').html(result['destination']);
							// $('#destination').val(result['destination']).attr("selected","selected");	
							//$('#consignor').html(result['consignor']);	
							//$('#vehicle_no').html(result['vehicle']);	



						}, 500);

					}
				});

			});
			//cancel button
			$(document).on('click', '.btn-cancel', function() {
				window.location.href = "transaction_list.php";
			});

			$(document).on('keyup', '#consignor_name', function(e) { //change
				if ($(this).is('select')) {
					return;
				}
				if (e.keyCode == 8 || e.keyCode == 46) { //change
					$("#id").val('');
					// $("#grn_no").val(''); 
					$("#grn_no1").val('');
					$("#origin").val('');

					reset_consignor(); //change
					reset_consignee(); //change
					$("#con_details").hide(); //change
					$("#con_details").hide(); //change
					$("#con_details1").hide(); //change
					$("#consignee_name").val(''); //change
					$('#consignee_name').prop("disabled", true); //change
				} //change
				var origin = $('#origin').val();
				var term = $(this).val();
				//console.log('autocomplete_list.php?autocomplete=consignor_autocomplete&origin='+origin+'&term='+term);
				$("#consignor_name").autocomplete({
					source: 'autocomplete_list.php?autocomplete=consignor_autocomplete&origin=' + origin + '&term=' + term,
					minLength: 0,
					select: function(event, ui) {
						$("#consignor_name").val(ui.item.value);
						$("#consignor").val(ui.item.id);

						//Check Invoice No Exist
						let conr_id = $("#consignor").val();

						//End

						$.ajax({
							url: 'fetch_details.php',
							type: "GET",
							dataType: "JSON",
							data: {
								cmd: "get_client_details_consignment",
								tbl_id: ui.item.id,
								consignor: "consignor"
							},
							// async: false,
							success: function(result) {
								console.log(result);
								$('#consignee_name').prop("disabled", false);
								//alert(ui.item.id);
								// if (ui.item.id == '3631') {
								// 	// let grn_id = $('#grn_no').val();
								// 	// let grn = $('#grn_no').val();

								// 	if($("#form_name").val() != "edit_consignment_details"){
								// 		let grn_no = result['grn_no']
								// 		$('#id').val(result['grn_id']);
								//     	$('#grn_no').val(grn_no.toUpperCase());
								//     	$('#grn_no1').val(grn_no.toUpperCase()).attr("disabled", true);
								// 	}else{
								// 		let grn_no = result['grn_no']
								// 		$('#id').val(result['grn_id']);
								// 		$('#grn_no').val(grn_no.toUpperCase());
								//     	$('#grn_no1').val(grn_no.toUpperCase()).attr("disabled", true);
								// 	}

								// } else {
								// 	if($("#form_name").val() != "edit_consignment_details"){
								// 		let grn_no = result['grn_no']
								// 		$('#id').val(result['grn_id']);
								//     	$('#grn_no').val(grn_no.toUpperCase());
								//     	$('#grn_no1').val(grn_no.toUpperCase()).attr("disabled", true);
								// 	}else{
								// 		let grn_no = result['grn_no']
								// 		$('#id').val(result['grn_id']);
								//     	$('#grn_no').val(grn_no.toUpperCase());
								//     	$('#grn_no1').val(grn_no.toUpperCase()).attr("disabled", true);
								// 	}


								// }
								$("#con_details").show();
								if ($('#origin').val() == "")
									$('#origin').val(result['city']).trigger("change");
								$("#consignor").val(ui.item.id);
								$('#address1').html(result['address1']);
								$('#address2').html(result['address2']);
								$('#city').html(result['city_name']);
								$('#state').html(result['state']);
								$('#pincode').html(result['pincode']);
								$('#phone').html(result['contact_no']);
								$('#gst_no').html(result['gst_no']);
								if (result['state']) {
									$('#consignor_state_id').val(result['state']);
								}
								loadClientBranches(ui.item.id, 'consignor');
								$(".consignor_name_val").removeClass("con_name_val1");
								$('#consignee_name').focus();

							},
						error: function(jqxhr) {
							ewToast(jqxhr.responseText, 'error');
						}
					});

					//Ajax Call For Check Invoice No
						// if (conr_id != "" && conr_id != null) {
						// 	party_invoice_details();
						// }
						//End 
					},

				});

			});

			// $(document).on('keyup', '#consignor_name', function(event) {
			//     var key = event.keyCode;					
			//     if (key == 8 || key == 46)
			//         reset_consignor();
			// });

			// $(document).on('keyup', '#consignee_name', function(event) {					
			//     var key = event.keyCode;
			//     if (key == 8 || key == 46)
			//         reset_consignee();
			// });

			$(document).on('keyup', '#consignee_name', function(e) { //change
				if ($(this).is('select')) {
					return;
				}
				if (e.keyCode == 8 || e.keyCode == 46) { //change
					$('#destination').val('');
					reset_consignee(); //change
					$("#con_details1").hide(); //change
				} //change

				var destination = $('#destination').val();
				var consignor = $('#consignor').val();
				var term = $(this).val();
				console.log('autocomplete_list.php?autocomplete=consignee_autocomplete&destination=' + destination + '&consignor=' + consignor + '&term=' + term);
				$("#consignee_name").autocomplete({
					source: 'autocomplete_list.php?autocomplete=consignee_autocomplete&destination=' + destination + '&consignor=' + consignor + '&term=' + term,
					minLength: 0,
					select: function(event, ui) {
						$("#consignee_name").val(ui.item.value);
						$("#consignee").val(ui.item.id);

						$.ajax({
							url: 'fetch_details.php',
							type: "GET",
							dataType: "JSON",
							data: {
								cmd: "get_client_details_consignment",
								tbl_id: ui.item.id
							},
							async: false,
							success: function(result) {
								//console.log(result);

								$("#con_details1").show();
								if ($('#destination').val() == "")
									$('#destination').val(result['city']);
								$("#consignee").val(ui.item.id);
								$('#con_address1').html(result['address1']);
								$('#con_address2').html(result['address2']);
								$('#con_city').html(result['city_name']);
								$('#con_state').html(result['state']);
								$('#con_pincode').html(result['pincode']);
								$('#con_phone').html(result['contact_no']);
								$('#con_gst').html(result['gst_no']);
								if (result['state']) {
									$('#consignee_state_id').val(result['state']);
								}
								loadClientBranches(ui.item.id, 'consignee');
								$(".consignee_name_val").removeClass("con_name_val2");
								$('#no_of_pkg1').focus();
							}
						});
						if ($('#destination').val() != "" && $('#destination').val() != null) {
							load_payment_info();
							refreshGstCalculation();
						}
					},

				});

			});


			//Volumetric Add More Field


			var addButton = $('.add_button'); //Add button selector
			var wrapper = $('.df'); //Input field wrapper

			//Once add button is clicked
			$(addButton).click(function() {
				//Check maximum text of input fields
				$dem_group = $('.dimensions_col:last').data("dem-no");
				$dem_group = isNaN($dem_group) ? 1 : (parseInt($dem_group) + 1);
				var df_length = $('.df .dimensions_col').length;
				console.log("count", df_length);
				//alert($dem_group);
				if (df_length < 8) {
					var fieldHTML = '<div class="form-group dimensions_col" id="dimensions_col' + $dem_group + '" data-dem-no="' + $dem_group + '"><div class="volumetric_width"> <input  type="text" placeholder="L" class="form-control num_only r_p length " id="length" name="length[]" onchange="vlm_calculation();" autocomplete="off" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);" /><span>X</span> </div><div class="volumetric_width"><input  type="text" placeholder="w" class="form-control num_only r_p width " id="width" name="width[]" onchange="vlm_calculation();" autocomplete="off" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);"  /><span>X</span> </div><div class="volumetric_width"><input type="text" placeholder="H" class="form-control num_only r_p height" id="height" name="height[]" onchange="vlm_calculation();" autocomplete="off" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);"  /><span>X</span></div><div class="volumetric_width"><input type="text" placeholder="Q" class="form-control num_only r_p quantity" id="quantity" name="quantity[]" onchange="vlm_calculation();" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);"  autocomplete="off" /><span>=</span></div><div class="volumetric_width"><input type="text" id="weight" value="" class="form-control num_only r_p weight" name="weight[]" readonly /></div><div class="volumetric_width"> <input type="text" class="form-control  r_p volume_weight num_only" id="volume_weight" name="volume_weight[]" /></div><div class="volumetric_width"><a href="javascript:void(0);" class="remove" id="' + $dem_group + '" title="Add field"><img src="icons/minus.png" class="dimen_img"/></a></div>'; //New input field html 
					//Check maximum text of input fields
					$(wrapper).append(fieldHTML); //Add field html
				}
			});
			//End Volumetric

			//Remove AddMore Field
			$("body").on("click", ".remove", function() {

				//$(this).parents(".dimensions_col").remove();
				var re_id = $(this).attr("id");
				// alert(re_id);

				// $(this).parents(".dimensions_col").remove();
				$('#dimensions_col' + re_id).remove();


				vlm_calculation();
				console.log("dd", array_weight);

			});
			//End

			//Edit Shipping Address
			var edit_form = '<?php echo $form_name; ?>';
			if (edit_form == 'edit_consignment_details_manual') {
				var checkboxcheked = $('#ship_adddress').is(':checked');
				if (checkboxcheked == true) {
					$('#shipadd').show();
				} else {
					$('#shipadd').hide();
				}
			}

			//End


			//Shipping Address
			$('#ship_adddress').change(function() {
				if ($(this).is(':checked')) {
					$('div#shipadd').show();
					// alert('d');
				} else {
					$('div#shipadd').hide();
				}
			})
			//End
			function reset_consignor() {
				$('#consignor').val('');
				if ($('#consignor_name').is('select')) {
					fillConsignorNameSelect('');
				} else {
					$('#consignor_name').val('');
				}
				$('#address1').html('');
				$('#address2').html('');
				$('#city').html('');
				$('#state').html('');
				$('#pincode').html('');
				$('#phone').html('');
				$('#gst_no').html('');
				$('#consignor_state_id').val('');
				cachedConsignorBranches = [];
				$("#consignor_branch_div").hide();
				$("#consignor_branch").html('<option value="">Select Branch</option>');
			}

			function reset_consignee() {
				$('#consignee').val('');
				if ($('#consignee_name').is('select')) {
					fillConsigneeNameSelect([], '', true);
				} else {
					$('#consignee_name').val('').prop('disabled', true);
				}
				$('#con_address1').html('');
				$('#con_address2').html('');
				$('#con_state').html('');
				$('#con_city').html('');
				$('#con_pincode').html('');
				$('#con_phone').html('');
				$('#con_gst').html('');
				$('#consignee_state_id').val('');
				cachedConsigneeBranches = [];
				$("#consignee_branch_div").hide();
				$("#consignee_branch").html('<option value="">Select Branch</option>');
			}

			$(document).on('change', '#consignor_name', function() {
				if (!$(this).is('select')) {
					return;
				}
				var id = $(this).val();
				$('#consignor').val(id || '');
				if (!id) {
					reset_consignee();
					$("#con_details").hide();
					$("#con_details1").hide();
					return;
				}
				loadClientBranches(id, 'consignor');
				loadMappedConsignees(id, '');
				$.ajax({
					url: 'fetch_details.php',
					type: 'GET',
					dataType: 'JSON',
					data: { cmd: 'get_client_details_consignment', tbl_id: id, consignor: 'consignor' },
					success: function(result) {
						if (typeof company_grn_mode !== 'undefined' && company_grn_mode !== 'company' && result) {
							var grn_no = result['grn_no'];
							$('#id').val(result['grn_id']);
							$('#grn_no').val((grn_no || '').toUpperCase());
							$('#grn_no1').val((grn_no || '').toUpperCase()).attr('disabled', true);
						}
						$('#con_details').show();
						if ($('#origin').val() == '' && cachedConsignorBranches.length <= 1 && result && result['city']) {
							$('#origin').val(result['city']);
							if (typeof refreshGstCalculation === 'function') {
								refreshGstCalculation();
							}
						}
						var address = [result['address1'], result['address2'], result['city_name'], result['state'], result['pincode']].filter(function(item) {
							return item && String(item).trim() !== '';
						}).join(', ');
						$('#address1').html(address);
						$('#address2').html('');
						$('#phone').html(result['contact_no']);
						$('#gst_no').html(result['gst_no']);
						$('#consignor_state_id').val(result['state_id'] || '');
						if (typeof sum_amount === 'function') sum_amount();
						$('.consignor_name_val').removeClass('con_name_val1');
					}
				});
				if (id && typeof party_invoice_details === 'function') {
					party_invoice_details();
				}
			});

			$(document).on('change', '#consignee_name', function() {
				if (!$(this).is('select')) {
					return;
				}
				var id = $(this).val();
				$('#consignee').val(id || '');
				if (!id) {
					$('#con_details1').hide();
					return;
				}
				loadClientBranches(id, 'consignee');
				$.ajax({
					url: 'fetch_details.php',
					type: 'GET',
					dataType: 'JSON',
					data: { cmd: 'get_client_details_consignment', tbl_id: id },
					success: function(result) {
						$('#con_details1').show();
						if ($('#destination').val() == '' && cachedConsigneeBranches.length <= 1 && result && result['city']) {
							$('#destination').val(result['city']);
							if (typeof refreshGstCalculation === 'function') {
								refreshGstCalculation();
							}
						}
						var address = [result['address1'], result['address2'], result['city_name'], result['state'], result['pincode']].filter(function(item) {
							return item && String(item).trim() !== '';
						}).join(', ');
						$('#con_address1').html(address);
						$('#con_phone').html(result['contact_no']);
						$('#con_gst').html(result['gst_no']);
						$('#consignee_state_id').val(result['state_id'] || '');
						if (typeof sum_amount === 'function') sum_amount();
					}
				});
			});

			var chck_key = true;
			$(document).on('keyup', '#grn_no', function(e) {
				var grn_no = $(this).val();
				var grn_id = $("#grn_id").val();

				$.ajax({
					url: 'check_existing.php',
					type: "GET",
					dataType: "JSON",
					data: {
						cmd: "chk_grn_no",
						grn_no: grn_no,
						grn_id: grn_id
					},
					async: false,
					success: function(result) {
						// console.log(result);
						if (result[0] == "1") {
							$("#grn_error").html(result[1]).attr("style", "color:red");
							chck_key = false;

						} else {
							chck_key = true;
							$("#grn_error").html('');
						}

					}
				});
			});


			// $(document).on("change", ".calculation", function() {
			//     var sum = 0;

			//     $(".calculation").each(function() {
			//         sum += +$(this).val();

			//     });
			//     parseFloat($("#total").val(sum)).toFixed(2);
			//     $.ajax({
			//         url: 'fetch_details.php',
			//         type: "post",
			//         data: {
			//             cmd: "get_amount_words",
			//             val: sum
			//         },
			//         success: function(result) {
			//             console.log(result);
			//             $('#amount_in_words').val(result);
			//         },
			//         error: function(jqxhr) {
			//             //alert(jqxhr.responseText);
			//         }
			//     });
			//     //$('#total').val(parseFloat($('#total').val(sum)).toFixed(2));
			// });
			var signature_image = '<?php echo $row['consigner_signature'];  ?>';

			$('#display_signature').html('<img src=' + signature_image + '>');
			if (signature_image == "") {
				$('div#signatureparent').removeClass('height_check');
			} else {
				$('div#signatureparent').addClass('height_check');
			}
			$('.num_only,#grn_no,#goods_dedared_value').keypress(function(event) {
				return isNumber(event, this)
			});

			$('.calculation').keypress(function(event) {
				return isNumber(event, this)
			});


			function isNumber(evt, element) {
				var charCode = (evt.which) ? evt.which : event.keyCode

				if ((charCode != 45 || $(element).val().indexOf('-') != -1) && // â€œ-â€ CHECK MINUS, AND ONLY ONE.
					(charCode != 46 || $(element).val().indexOf('.') != -1) && // â€œ.â€ CHECK DOT, AND ONLY ONE.
					(charCode < 48 || charCode > 57))
					return false;
				return true;
			}
			$(document).on('blur', 'input.calculation', function(ev) {
				if ($(this).val() != "")
					$(this).val(parseFloat($(this).val()).toFixed(2));
				else
					$(this).val("0.00");

			});
			$(document).on('click', '.btn-del-file', function(evt) {
				$(this).closest(".file-group").remove();
			});
			//image Preview
			function readURL(input, portfolio_no) {
				if (input.files && input.files[0]) {
					var reader = new FileReader();
					reader.onload = function(e) {
						$('#' + portfolio_no).attr('src', e.target.result);
					}

					reader.readAsDataURL(input.files[0]);
				}
			}

			$(document).on('change', '.upload-image', function(ev) {
				var portfolio_no = $(this).attr("data-img-preview-id");
				readURL(this, portfolio_no);
			});
			$(document).on('change', '#goods_dedared_value', function(ev) {

				if ($(this).val() != "")
					$(this).val(parseFloat($(this).val()).toFixed(2));
				else
					$(this).val("0.00");

			});
			//signature

			var $sigdiv = $("#signature").jSignature({
					'background-color': 'transparent',
					'decor-color': 'transparent'
				}),
				$tools = $('#tools')

			$('#signature img').attr('src', "");
			$('#signature img').attr('style', '');
			$('#tools').html('<br/><input type="button" id="clear_signature" value="Clear">');
			$(document).on('click', '#clear_signature', function() {
				$('#display_signature').html('');
				$("#signature").show();
				//$sigdiv.jSignature('reset')
				$('#signature').jSignature('clear');
				$("#signature_capture").val('');
				$("#display_signature").attr("style", "");

				$('div#signatureparent').removeClass('height_check');

			});

			function getNextFileNo() {
				var last = $(".file-group:last").data("file-no");
				return isNaN(last) || !last ? 1 : (parseInt(last, 10) + 1);
			}

			function isImageUploadFile(file) {
				return file && file.type && file.type.indexOf('image/') === 0;
			}

			function escapeUploadHtml(text) {
				return $('<div>').text(text || '').html();
			}

			function formatUploadFileSize(bytes) {
				if (!bytes && bytes !== 0) return 'Ready to upload';
				if (bytes < 1024) return bytes + ' B';
				if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
				return (bytes / 1048576).toFixed(1) + ' MB';
			}

			function buildUploadItemHtml(fileNo, fileName, previewSrc, attachmentId) {
				fileName = fileName || 'Select a file';
				previewSrc = previewSrc || 'images/no_image.png';
				var imgClass = (previewSrc.indexOf('no_image') >= 0) ? ' doc-placeholder' : '';
				var idAttr = attachmentId ? ' id="' + attachmentId + '"' : '';
				return '<div class="upload-item file-group" id="file-no' + fileNo + '" data-file-no="' + fileNo + '">' +
					'<div class="upload-item-preview img_pre_div">' +
					'<img src="' + previewSrc + '" class="image_preview' + imgClass + '" id="image_preview' + fileNo + '" alt="">' +
					'</div>' +
					'<div class="upload-item-body">' +
					'<span class="upload-item-name">' + escapeUploadHtml(fileName) + '</span>' +
					'<span class="upload-item-sub">Ready to upload</span>' +
					'<input type="file" id="file_receipt' + fileNo + '" name="file_receipt[]" class="filestyle upload-file-input" data-id="' + fileNo + '" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">' +
					'</div>' +
					'<div class="upload-item-actions remov">' +
					'<button type="button" data-id="' + fileNo + '"' + idAttr + ' class="btn btn-link upload-item-remove remove-image" title="Remove"><i class="fa fa-times"></i></button>' +
					'</div>' +
					'</div>';
			}

			function assignFileToInput(input, file) {
				try {
					var dt = new DataTransfer();
					dt.items.add(file);
					input.files = dt.files;
					return true;
				} catch (e) {
					return false;
				}
			}

			function previewUploadFile(fileNo, file) {
				var $item = $('#file-no' + fileNo);
				var $img = $('#image_preview' + fileNo);
				if (isImageUploadFile(file)) {
					var reader = new FileReader();
					reader.onload = function(e) {
						$img.attr('src', e.target.result).removeClass('doc-placeholder');
					};
					reader.readAsDataURL(file);
				} else {
					$img.attr('src', 'images/no_image.png').addClass('doc-placeholder');
				}
				$item.find('.upload-item-name').text(file.name);
				$item.find('.upload-item-sub').text(formatUploadFileSize(file.size));
			}

			function addUploadItem(file, options) {
				options = options || {};
				var fileNo = getNextFileNo();
				var fileName = options.fileName || (file ? file.name : 'Select a file');
				var previewSrc = options.previewSrc || 'images/no_image.png';
				var attachmentId = options.attachmentId || '';
				$(".file-container").append(buildUploadItemHtml(fileNo, fileName, previewSrc, attachmentId));
				var $input = $('#file_receipt' + fileNo);
				if (file) {
					assignFileToInput($input[0], file);
					previewUploadFile(fileNo, file);
				}
				syncUploadRemoveButtons();
				return fileNo;
			}

			function handleUploadFiles(fileList) {
				if (!fileList || !fileList.length) return;
				for (var i = 0; i < fileList.length; i++) {
					addUploadItem(fileList[i]);
				}
			}

			function syncUploadRemoveButtons() {
				$(".remove-image").prop("disabled", false);
			}

			var $uploadDropzone = $('#upload_dropzone');
			$uploadDropzone.on('dragover dragenter', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$(this).addClass('is-dragover');
			});
			$uploadDropzone.on('dragleave drop', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$(this).removeClass('is-dragover');
			});
			$uploadDropzone.on('drop', function(e) {
				var files = e.originalEvent.dataTransfer.files;
				handleUploadFiles(files);
			});
			$uploadDropzone.on('click', function(e) {
				if ($(e.target).closest('.remove-image, .upload-item').length) return;
				$('#upload_dropzone_input').trigger('click');
			});
			$('#upload_dropzone_input').on('change', function() {
				handleUploadFiles(this.files);
				this.value = '';
			});

			var attachment_id = [];
			$(document).on('click', '.remove-image', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var id = $(this).attr('data-id');
				$('#file-no' + id).remove();
				var image_id = $(this).attr('id');
				if (image_id) {
					attachment_id.push(image_id);
				}
				syncUploadRemoveButtons();
			});


			$(document).on('click', '#save', function() {

				//Select Consginor and Consginee
				const get_consigner_valll = $('.get_consigner_valll').val();
				const get_consignee_valll = $('.get_consignee_valll').val();
				$(".consignor_name_val").toggleClass("con_name_val1", get_consigner_valll === "");
				$(".consignee_name_val").toggleClass("con_name_val2", get_consignee_valll === "");
				//End

				var values = $("input[name='file_receipt[]']").map(function() {
					var imag_pre = $(this).closest('.file-group').find('.image_preview').attr('src') || '';
					return $(this).val() + imag_pre;
				}).get();

				console.log(values);
				var file_receipt_validate = false;
				$("input[name='file_receipt[]']").each(function() {
					$(this).closest('.upload-item-body').find('.upload-item-sub').removeClass('attach_required');
				});

				const length = $.map($('input[type=text][name="length[]"]'), function(el) {
					return el.value;
				});
				const width = $.map($('input[type=text][name="width[]"]'), function(el) {
					return el.value;
				});
				const height = $.map($('input[type=text][name="height[]"]'), function(el) {
					return el.value;
				});
				const quanti = $.map($('input[type=text][name="quantity[]"]'), function(el) {
					return el.value;
				});
				const vlm_weight = $.map($('input[type=text][name="weight[]"]'), function(el) {
					return el.value;
				});
				console.log("weight", vlm_weight);

				var data1 = $sigdiv.jSignature('getData');
				//alert(chck_key);
				$('#signature_val').val(signature_image);
				if (($sigdiv.jSignature('getData', 'native').length != 0)) {
					$('#signature_val').val(data1);
					//alert(data1);
				}

				var edit_id = $('#edit_id').val();
				var id = attachment_id;
				var formData = new FormData(document.getElementById("grn_details"));
				formData.append('del_id', id);
				formData.append('length', length);
				formData.append('width', width);
				formData.append('height', height);
				formData.append('quanti', quanti);
				formData.append('vlm_weight', vlm_weight);
				$("label[for='type_of_pkg1']").text("Select Package");
				$('#party_invoice1').attr('required', 'required');

				if ($("#v_weight").val() == '') {
					//alert("YEs");
					$('#charged1').attr('required', 'required');
				} else {
					$('#charged1').removeAttr('required');
					$('#charged1').removeClass('error');
				}

				if ($("#charged1").val() == '') {
					//alert("YEs");
					$('#charged1').attr('required', 'required');
				} else {
					$('#charged1').removeAttr('required');
					$('#charged1').removeClass('error');
				}

				syncPackageRowRequired();
				$('#no_of_pkg1').attr('required', 'required');
				var form_name = $("#form_name").val();
				let party_invoice_validate = $('input[name="party_invoice[]"].invoice_exist');
				console.log('FormName', form_name)
				//console.log(party_invoice_details.length);
				//return;
				if ($('#grn_details').valid() == true && chck_key == true && file_receipt_validate == false && get_consigner_valll !== "" && get_consignee_valll !== "") {

					//filesExistCheck();
					//if (img_avail == true) {
					if (party_invoice_validate.length == 0) {
						$(".loading-page").show();
						$(this).prop("disabled", true);
						function showBookingSuccessModal(grnNo, trackingCode) {
							window.ewBookingRedirectPending = true;
							$('#show_grn_no').text(grnNo || '');
							if (trackingCode) {
								$('#show_tracking_code').text(trackingCode);
								$('#show_tracking_code_wrap').show();
							} else {
								$('#show_tracking_code_wrap').hide();
							}
							if (typeof ewV2OpenModal === 'function') {
								ewV2OpenModal('grn_booked_modal');
							}
						}
						function finishBookingSuccessModal() {
							if (!window.ewBookingRedirectPending) {
								return;
							}
							window.ewBookingRedirectPending = false;
							$(".form-data-saving").hide();
							if (typeof ewFormToast === 'function') {
								ewFormToast('Booked Successfully', 'success', 5000);
							}
							setTimeout(function() {
								window.location.href = "transaction_list.php";
							}, 1200);
						}
						$(document).on('click', '#grn_booked_modal [data-ew-v2-close], #grn_booked_close', function() {
							finishBookingSuccessModal();
						});
						$(document).on('click', '#grn_booked_modal', function(e) {
							if (e.target === this) {
								finishBookingSuccessModal();
							}
						});
						$.ajax({
							url: "save_details.php?id=" + attachment_id,
							type: "post",
							dataType: "json",
							data: formData,
							processData: false,
							contentType: false,
							success: function(result) {
								console.log(result);
								if (result['result'] == 1) {
									$(".loading-page").hide();
									if (edit_id == '') {
										showBookingSuccessModal(result['data'], result['tracking_code'] || '');
									} else {

										$(".form-data-saving").hide();
										$("#alert-status").text("");
										$("#alert-message").text("Saved Successfully");
										$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
											$("#alert-container").hide();
											$("#alert-container").removeClass("alert-success");
											//location.reload();
											//window.location.href = "transaction_list.php";
										});

									}

								} else if (result['logout'] == 1) {
									$(".form-data-saving").hide();
									$("#alert-status").text("Alert !!! ");
									$("#alert-message").text("Your session is expired! Please Log in again to continue.");
									$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
										$("#alert-container").hide();
										$("#alert-container").removeClass("alert-danger");
										location.href = "logout.php";
									});
								} else {
									$(".form-data-saving").hide();
									$("#alert-status").text("Alert !!! ");
									$("#alert-message").text("Booking Failed");
									$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
										$("#alert-container").hide();
										$("#alert-container").removeClass("alert-danger");
									});
								}

							},
							error: function(jqxhr) {
								console.log(jqxhr.responseText);
							}
						});
					} else {
						ewToast('Invoice Already Exist', 'warning');
					}
					// } else {
					// 	alert('Attachment Field Empty');
					// }

					// }else{
					// 	console.log('form is not edit');
					// }

				}

			});

			//popupclose
			$(document).on('click', '.grn_close_popup', function() {
				$(".grn_no_popup").hide();
				$(".form-data-saving").hide();
				$("#alert-status").text("");
				$("#alert-message").text("Booked Successfully");
				$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
					$("#alert-container").hide();
					$("#alert-container").removeClass("alert-success");
					//location.reload();
					window.location.href = "transaction_list.php";
				});

			});

			function readURL(id, input) {
				if (input.files && input.files[0]) {
					var reader = new FileReader();

					reader.onload = function(e) {
						$('#image_preview' + id + '').attr('src', e.target.result);
					}

					reader.readAsDataURL(input.files[0]);
				}
			}

			$(document).on('change', '.filestyle', function() {
				var id = $(this).attr("data-id");
				readURL(id, this);
			});

			$(document).on('click', '#add_more', function(evt) {
				addUploadItem(null);
			});


			//Check if file exist
			var img_avail;
			//Check Files Validation
			function filesExistCheck() {
				var form_name = $("#form_name").val();

				var file_ids = document.getElementsByName('file_receipt[]');
				//console.log("Testname",file_ids.length);
				if (form_name != 'edit_consignment_details_manual') {
					for (var i = 0; i < file_ids.length; i++) {
						if (file_ids[i].value != "") {
							//console.log("Files Available");
							img_avail = true;
							continue;
						}
						img_avail = false;
						break;
					}
				} else {
					if (file_ids.length == 0) {
						img_avail = false;
					} else {
						img_avail = true;
					}
				}

			}

			//End

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
	<div class="ew-v2-modal-backdrop" id="grn_booked_modal">
		<div class="ew-v2-modal">
			<div class="ew-v2-modal-head">
				<h3>Consignment Booked</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
					<p class="grn-booked-lead">Booking is confirmed. Keep these details for tracking.</p>
					<div class="grn-booked-rows">
						<div class="grn-booked-row">
							<span class="grn-booked-label">GCN Number</span>
							<span class="grn-booked-value" id="show_grn_no"></span>
						</div>
						<div class="grn-booked-row" id="show_tracking_code_wrap" style="display:none;">
							<span class="grn-booked-label">Transaction Code</span>
							<span class="grn-booked-value" id="show_tracking_code"></span>
						</div>
					</div>
			</div>
			<div class="ew-v2-modal-foot">
					<button class="btn btn-primary" type="button" id="grn_booked_close" data-ew-v2-close>Close</button>
			</div>
		</div>
	</div>

		</div>
	</div>

</body>

</html>