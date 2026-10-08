<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/gst_tax_functions.php');
require_once('include/billing_functions.php');
require_once('include/gcn_gst_invoice_helpers.php');
require_once('include/quotation_functions.php');
require_once('include/vehicle_type_helpers.php');
require_once('include/cfs_master_helpers.php');
ew_vehicle_type_ensure_schema($conn);
ew_cfs_master_ensure_schema($conn);
$booking_vehicle_type_dims = ew_vehicle_type_booking_dims_lookup($conn);
$booking_vehicle_type_dims_json = json_encode($booking_vehicle_type_dims, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
if ($booking_vehicle_type_dims_json === false) {
	$booking_vehicle_type_dims_json = '{}';
}
ensure_gst_tax_master_table($conn);
ensure_transaction_gst_columns($conn, 'transaction');
$company_query = mysqli_query($conn, 'SELECT company_id, company_code, grn_mode, state FROM company WHERE status=0 LIMIT 1');
$company_row = mysqli_fetch_array($company_query);
$comp_id = isset($company_row['company_id']) ? $company_row['company_id'] : 2;
$comp_code = isset($company_row['company_code']) ? $company_row['company_code'] : '';
$comp_grn_mode = isset($company_row['grn_mode']) ? $company_row['grn_mode'] : 'company';
$company_state_id = isset($company_row['state']) ? (int) $company_row['state'] : 0;
$company_state_name = $company_state_id > 0 ? get_statename($conn, $company_state_id) : '';
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
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.form-horizontal .control-label {
			text-align: left;
		}
		.save-button{
			font-size: 13px !important;
			padding: 10px 17px !important;
		}
		.cancel-button{
			font-size: 13px !important;
			padding: 10px 17px !important;
		}

		#grn_details .form-horizontal .form-group > label.control-label {
			text-transform: none;
			font-size: 13px;
			font-weight: 600;
			letter-spacing: 0;
			color: #334155;
			line-height: 1.4;
			padding-top: 7px;
			padding-right: 6px;
			margin-bottom: 0;
			white-space: normal;
		}

		#grn_details .form-horizontal .form-group > label.control-label .req-star {
			color: #DD111E;
			margin-left: 3px;
			white-space: nowrap;
		}

		#grn_details .form-horizontal .form-group > label.control-label:has(.req-star)::after {
			content: ':';
			margin-left: 2px;
			white-space: nowrap;
		}

		#grn_details .form-horizontal .form-group {
			margin-bottom: 12px;
		}

		#grn_details.billing-only-edit .ew-booking-section:not(.payment-gst-panel) input,
		#grn_details.billing-only-edit .ew-booking-section:not(.payment-gst-panel) select,
		#grn_details.billing-only-edit .ew-booking-section:not(.payment-gst-panel) textarea,
		#grn_details.billing-only-edit fieldset:not(.payment-gst-panel) input,
		#grn_details.billing-only-edit fieldset:not(.payment-gst-panel) select,
		#grn_details.billing-only-edit fieldset:not(.payment-gst-panel) textarea,
		#grn_details.billing-only-edit .booking-footer-section input,
		#grn_details.billing-only-edit .booking-footer-section select,
		#grn_details.billing-only-edit .booking-footer-section textarea,
		#grn_details.billing-only-edit .booking-footer-section button:not(#save):not(.btn-cancel) {
			pointer-events: none;
			background-color: #f1f5f9 !important;
			opacity: 0.9;
		}

		#grn_details.billing-only-edit .gst-config-block select,
		#grn_details.billing-only-edit .gst-config-block.gst-locked {
			pointer-events: none;
		}

		#grn_details.billing-only-edit .gst-config-block select {
			background-color: #f1f5f9 !important;
		}

		#grn_details.billing-only-edit .payment-charges-table input,
		#grn_details.billing-only-edit .billing-generate-invoice-option input {
			pointer-events: auto !important;
			background-color: #fff !important;
			opacity: 1 !important;
		}

		#grn_details.billing-only-edit .billing-gst-invoice-actions {
			pointer-events: auto !important;
			opacity: 1 !important;
		}

		.billing-gst-invoice-actions {
			margin: 8px 0 0 22px;
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: 8px;
		}

		.billing-gst-invoice-actions .ew-btn-v2 {
			padding: 4px 12px;
			font-size: 12px;
		}

		#grn_details.billing-only-edit #frieght_amount[readonly] {
			background-color: #f8fafc !important;
		}

		.billing-only-note {
			font-size: 12px;
			font-weight: 500;
			color: #0f6659;
			margin-left: 8px;
			text-transform: none;
			letter-spacing: 0;
		}

		#grn_details.form-fully-locked input,
		#grn_details.form-fully-locked select,
		#grn_details.form-fully-locked textarea,
		#grn_details.form-fully-locked button:not(.btn-cancel) {
			pointer-events: none;
			background-color: #f1f5f9 !important;
			opacity: 0.85;
		}

		.form-locked-banner {
			background: #fef3c7;
			border: 1px solid #f59e0b;
			color: #92400e;
			padding: 10px 14px;
			border-radius: 6px;
			margin-bottom: 14px;
			font-size: 13px;
			font-weight: 600;
		}

		#grn_details .con_name_val1,
		#grn_details .con_name_val2 {
			display: none !important;
		}

		/* Party address/phone/GST — data only, never shown on booking form */
		#con_details,
		#con_details1,
		.party-card-meta--hidden {
			display: none !important;
			visibility: hidden !important;
			height: 0 !important;
			overflow: hidden !important;
			margin: 0 !important;
			padding: 0 !important;
		}

		.ew-booking-section--parties .party-section-hint {
			margin: -4px 0 10px;
			font-size: 12px;
			color: #64748b;
			line-height: 1.4;
		}

		.ew-booking-section--parties .party-split--compact .select2-container {
			max-width: 100%;
		}

		.ew-vehicle-type-block .ew-vehicle-dims-below {
			margin-top: 8px;
		}

		.ew-vehicle-type-block .ew-vehicle-dims-below > .control-label {
			margin-bottom: 4px !important;
		}

		#vehicle_type_dims_display[disabled] {
			background: #f4f6f9;
			cursor: default;
		}

		.ew-cfs-location-row {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			align-items: stretch;
		}
		.ew-cfs-kind-select {
			flex: 0 0 132px;
			max-width: 160px;
		}
		.ew-cfs-location-row .ew-cfs-value-field {
			flex: 1 1 180px;
			min-width: 0;
		}
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

		/* End */

		.image_preview {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.upload-item-preview .image_preview.doc-placeholder {
			object-fit: contain;
			padding: 8px;
			width: 40px;
			height: 40px;
		}

		/* Volumetric Design CSS */

		.shp_lbl {
			padding: 0px 0px;
		}

		label.col-md-4.control-label.shp_lbl {
			padding-left: 10px;
		}

		.booking-split-row {
			display: grid;
			grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
			gap: 14px;
			align-items: start;
		}

		.booking-split-col {
			min-width: 0;
		}

		.booking-split-row .ew-booking-section.lable,
		.booking-split-row .my-fieldset.lable,
		.booking-split-row .payment-gst-panel,
		.booking-split-row .booking-goods-col {
			width: 100%;
			margin-bottom: 0;
		}

		.booking-split-row .payment-gst-panel {
			display: flex;
			flex-direction: column;
		}

		.payment-gst-panel .amount-in-words-block {
			margin-top: 8px;
			padding-top: 8px;
			border-top: 1px dashed #e8ebf0;
		}

		.payment-gst-panel .amount-in-words-block label {
			display: block;
			font-size: 12px;
			font-weight: 600;
			margin-bottom: 6px;
			color: #334155;
		}

		.payment-gst-panel .amount-in-words-block textarea {
			min-height: 72px;
			resize: vertical;
		}

		.ew-field .v_label {
			display: block;
			width: 100%;
			float: none;
			text-align: left;
			margin-bottom: 8px;
			padding-left: 0;
		}

		.ew-field > .df {
			width: 100%;
			float: none;
			padding: 0;
		}

		.volumetric_width {
			display: flex;
			flex: 1 1 0;
			min-width: 0;
			align-items: center;
			justify-content: center;
			text-align: center;
		}

		.pkg-action-col {
			width: 48px;
			min-width: 48px;
		}

		.pkg-row-action {
			white-space: nowrap;
		}

		.pkg-row-action .pkg-row-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 28px;
			height: 28px;
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			background: #fff;
			color: #334155;
			cursor: pointer;
			padding: 0;
			line-height: 1;
		}

		.pkg-row-action .pkg-row-btn + .pkg-row-btn {
			margin-left: 4px;
		}

		.pkg-row-action .pkg-row-btn.is-add {
			color: #059669;
			border-color: #86efac;
		}

		.pkg-row-action .pkg-row-btn.is-add:hover {
			background: #ecfdf5;
		}

		.pkg-row-action .pkg-row-btn.is-remove {
			color: #dc2626;
			border-color: #fca5a5;
		}

		.pkg-row-action .pkg-row-btn.is-remove:hover {
			background: #fef2f2;
		}

		.dimensions_col {
			display: flex;
			flex-wrap: nowrap;
			align-items: center;
			gap: 2px;
			width: 100%;
			margin-bottom: 6px;
		}

		.volumetric_width:nth-child(6) {
			display: none;
		}

		.volumetric_width:nth-child(7) {
			flex: 0 0 24px;
			width: 24px !important;
			display: flex;
			justify-content: center;
		}

		.volumetric_width span {
			margin: 0 2px;
			font-size: 11px;
		}

		.r_p {
			padding: 4px 2px !important;
			text-align: center;
			width: 100%;
			min-width: 0;
			font-size: 12px;
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
			/* text-align: left; */
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

		.booking-goods-col .ew-field .form-control,
		.booking-goods-col .ew-field select.form-control {
			margin: 0;
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

		.payment-gst-panel .text-right,
		.payment-gst-panel .amt-col,
		.payment-gst-panel input.text-right,
		.payment-gst-panel th.text-right,
		.payment-gst-panel td.text-right {
			text-align: right !important;
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
			text-align: left;
			width: auto;
		}

		.payment-gst-panel .table > thead > tr > th:nth-child(2),
		.payment-gst-panel .table > tbody > tr > td:nth-child(2) {
			width: 84px;
			max-width: 84px;
		}

		.payment-gst-panel .table > thead > tr > th:nth-child(3),
		.payment-gst-panel .table > tbody > tr > td:nth-child(3) {
			width: 112px;
			max-width: 112px;
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

		.payment-gst-panel .gst-breakup-note {
			display: block;
			margin-top: 8px;
			font-size: 11px;
			color: #888;
			line-height: 1.45;
		}

		.payment-gst-panel .rate-empty {
			color: #bbb;
		}

		.attach_required:after {
			content: "This field is required.";
			color: #DD111E;
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
			color: #DD111E;
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
			color: #DD111E;
			position: relative;
			display: block;
			margin: 0;
			padding: 0;
			list-style: none;
			font-size: 14px;
			line-height: 20px;
		}

		.file-container {
			margin-top: 0;
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
			font-size: 11px;
			color: #9ca3af;
			line-height: 1.4;
			font-size:12px;
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

		.upload-item-remove:hover,
		.upload-item-remove:focus {
			color: #b91c1c !important;
			text-decoration: none;
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

		.img_pre_div {
			margin-bottom: 10px;
		}

		/* End */


		@media (min-width: 320px) and (max-width:575.98px) {

			.signature-canvas-wrap {
				min-height: 140px;
			}

		}

		@media only screen and (min-width: 390px) and (max-width: 844px) and (orientation: landscape) {

			.signature-canvas-wrap {
				min-height: 140px;
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
			require_once('include/header.php');
			require_once('include/menu.php');
			?>

		</div>
		<div class="container-fluid main-content new_dpt_bottom">

			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2 ew-page-v2--consignment">
							<?php
							$m = $_REQUEST['m'];
							$y = $_REQUEST['y'];
							$query = 'select * from transaction_' . $m . '_' . $y . " where md5(transaction_id) = '" . $_REQUEST['key'] . "'";
							$result = mysqli_query($conn, $query);
							$row = mysqli_fetch_assoc($result);
							if (!is_array($row)) {
								$row = array();
							}
							// print_r($row);
							$transaction_id = $row['transaction_id'] ?? '';
							$ftl_type = $row['ftl_type'] ?? '';
							if (($row['transaction_id'] ?? 0) > 0) {
								$form_name = 'edit_consignment_details';
							} else {
								$form_name = 'add_new_consignment';
							}
							$booking_status = (int) ($row['status'] ?? 0);
							$trans_table_name = 'transaction_' . $m . '_' . $y;
							ew_transaction_ensure_party_branch_columns($conn, 'transaction');
							if ($m !== '' && $y !== '') {
								ew_transaction_ensure_party_branch_columns($conn, $trans_table_name);
							}
							$saved_booking_vehicle_type = trim((string) ($row['vehicle_type'] ?? ''));
							$initial_vehicle_type_dims_display = isset($booking_vehicle_type_dims[$saved_booking_vehicle_type])
								? $booking_vehicle_type_dims[$saved_booking_vehicle_type] : '';
							$gcn_billed = ($transaction_id > 0) && booking_is_gcn_billed($conn, $trans_table_name, $transaction_id);
							// Billing-only lock applies only after delivery (status 8), not on submitted bookings.
							$billing_only_edit = ($form_name === 'edit_consignment_details' && $booking_status === 8 && !$gcn_billed);
							$form_fully_locked = ($form_name === 'edit_consignment_details' && $gcn_billed);
							$booking_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
							$grn_date_val = !empty($row['grn_date']) ? $row['grn_date'] : date('d-m-Y');
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
							ensure_transaction_gst_columns($conn, 'transaction_' . $m . '_' . $y);
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
							$consignment_page_title = ($form_name === 'edit_consignment_details') ? 'Edit Consignment' : 'Book a Consignment';
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
							<form id="grn_details" class="form-horizontal ew-validated-form<?php echo $billing_only_edit ? ' billing-only-edit' : ''; ?><?php echo $form_fully_locked ? ' form-fully-locked' : ''; ?>" enctype="multipart/form-data" data-ew-validate="1">
								<input type="hidden" name="billing_only_edit" id="billing_only_edit" value="<?php echo $billing_only_edit ? '1' : '0'; ?>">
								<input type="hidden" name="form_fully_locked" id="form_fully_locked" value="<?php echo $form_fully_locked ? '1' : '0'; ?>">
								<input type="hidden" name="booking_status" id="booking_status" value="<?php echo (int) $booking_status; ?>">
								<?php if ($form_fully_locked) { ?>
								<div class="form-locked-banner"><i class="fa fa-lock"></i> This GCN is already invoiced. The booking form is locked and cannot be edited.</div>
								<?php } elseif ($billing_only_edit) { ?>
								<div class="form-locked-banner" style="background:#eff6ff;border-color:#3b82f6;color:#1e40af;"><i class="fa fa-info-circle"></i> Delivered consignment — only payment / billing fields can be edited. GST settings are locked.</div>
								<?php } ?>
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
													$grn_no = $billing_code . sprintf('%05d', $id);
													// $query_code=mysqli_query($conn,"select * from client where client_id='4205'");
													// $r_code=mysqli_fetch_array($query_code);
													// $query_max=mysqli_query($conn,"select * from transaction_log where client_id='4205'");
													// $r_max=mysqli_fetch_array($query_max);
													// $id=$r_max['grn_id']+1;
													// $billing_code = $r_code['billing_code'];
													// $grn_no=$billing_code.sprintf("%05d",$id);
													//	if($_REQUEST['key']!=''){
													if ($_SESSION['role'] == 'CL') {
													?>
														<input type="hidden" id="id" value="<?php echo $id; ?>" name="id" class="form-control" />
														<?php
														if ($row['grn_no'] != '') {
														?>
															<input type="text" id="grn_no" value="<?php echo $row['grn_no']; ?>" name="grn_no" class="form-control" readonly />
														<?php
														} else {
														?>
															<input type="text" id="grn_no1" value="<?php echo $grn_no; ?>" name="grn_no1" class="form-control" readonly />
															<input type="hidden" id="grn_no" value="<?php echo $grn_no; ?>" name="grn_no" class="form-control" />
														<?php
														}
													} else {
														$next_id_val = '';
														$grn_val = '';
														if ($row['grn_no'] != '') {
															$grn_val = $row['grn_no'];
														} else if ($comp_grn_mode == 'company') {
															$comp_next_id = peek_next_grn_id($conn, 'COMPANY');
															$grn_val = $comp_code . sprintf('%04d', $comp_next_id);
															$next_id_val = $comp_next_id;
														}
														?>
														<input type="hidden" id="id" value="<?php echo $next_id_val; ?>" name="id" class="form-control" />
														<?php
														if ($row['grn_no'] != '') {
														?>
															<input type="text" id="grn_no" name="grn_no" value="<?php echo $grn_val; ?>" class="form-control" readonly />
														<?php
														} else {
														?>
															<input type="hidden" id="grn_no" name="grn_no" class="form-control" value="<?php echo $grn_val; ?>" />
															<input type="text" id="grn_no1" name="grn_no1" class="form-control" value="<?php echo $grn_val; ?>" readonly />
													<?php
														}
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
														$transport_query = 'select * from mode_of_transportation where status=0';
														$transport_result = mysqli_query($conn, $transport_query);
														while ($transport_row = mysqli_fetch_array($transport_result)) {
														?>
															<option value="<?php echo $transport_row['mode_id']; ?>" <?php if ($transport_row['mode_id'] == $row['mode_of_transportation']) echo 'selected'; ?>><?php echo $transport_row['mode_type']; ?></option>
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
														$consignment_query = 'select * from consignment_mode where status=0';
														$consignment_result = mysqli_query($conn, $consignment_query);
														while ($consignment_row = mysqli_fetch_array($consignment_result)) {
															if ($consignment_row['consignment_id'] != '3') {
														?>
																<option value="<?php echo $consignment_row['consignment_id']; ?>" <?php if ($consignment_row['consignment_id'] == $row['mode_of_consignment']) echo 'selected'; ?>><?php echo $consignment_row['consignment_mode']; ?></option>
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
														$city_query = 'select * from city where status=0 order by city_name asc';
														$city_result = mysqli_query($conn, $city_query);
														while ($city_row = mysqli_fetch_array($city_result)) {
														?>
															<option value="<?php echo $city_row['city_id']; ?>" data-state="<?php echo (int) $city_row['state']; ?>" <?php if ($city_row['city_id'] == ($row['origin'] ?? '')) echo 'selected'; ?>><?php echo $city_row['city_name']; ?></option>
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
															<option value="<?php echo $city_row1['city_id']; ?>" data-state="<?php echo (int) $city_row1['state']; ?>" <?php if ($city_row1['city_id'] == ($row['destination'] ?? '')) echo 'selected'; ?>><?php echo $city_row1['city_name']; ?></option>
														<?php
														}
														?>
													</select>
										</div>
										<?php /* FTL Type & Train Type dropdowns temporarily disabled — values kept for save */ ?>
										<input type="hidden" name="train_name" id="train_name_hidden" value="<?php echo htmlspecialchars($row['train_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<?php if (!empty($row['other_train_name'])) { ?>
										<input type="hidden" name="other_train_name" value="<?php echo htmlspecialchars($row['other_train_name'], ENT_QUOTES, 'UTF-8'); ?>">
										<?php } ?>
										<?php /*
										<div class="ew-field" id="ftl_menu" style="display:none;">...</div>
										<div class="ew-field" id="train_type" style="display:none;">...</div>
										<div class="ew-field" id="other_train_field">...</div>
										*/ ?>
									</div>
								</div>

								<div class="ew-booking-section ew-booking-section--parties">
									<h2 class="ew-card-section-title">Consignor &amp; Consignee Information</h2>
									<div class="party-split party-split--compact">
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
												<label class="control-label" title="Consignor branch / pickup address">Pickup branch</label>
												<select id="consignor_branch" name="consignor_branch" class="form-control party-select" data-placeholder="Select branch">
													<option value="">Select branch</option>
												</select>
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
											<div class="party-branch-pair">
												<div class="ew-field" id="bill_to_branch_div" style="display:none;">
													<label class="control-label">Bill To</label>
													<select id="bill_to_branch" name="bill_to_branch" class="form-control party-select" data-placeholder="Select branch">
														<option value="">Select branch</option>
													</select>
												</div>
												<div class="ew-field" id="consignee_branch_div" style="display:none;">
													<label class="control-label">Ship To</label>
													<select id="consignee_branch" name="consignee_branch" class="form-control party-select" data-placeholder="Select branch">
														<option value="">Select branch</option>
													</select>
												</div>
											</div>
										</div>
									</div>
									<div id="con_details" class="party-card-meta--hidden" aria-hidden="true">
										<input type="hidden" id="address1" value="">
										<input type="hidden" id="address2" value="">
										<input type="hidden" id="phone" value="">
										<input type="hidden" id="gst_no" value="">
										<input type="hidden" id="city" value="">
										<input type="hidden" id="state" value="">
										<input type="hidden" id="pincode" value="">
									</div>
									<div id="con_details1" class="party-card-meta--hidden" aria-hidden="true">
										<input type="hidden" id="con_address1" value="">
										<input type="hidden" id="con_address2" value="">
										<input type="hidden" id="con_phone" value="">
										<input type="hidden" id="con_gst" value="">
										<input type="hidden" id="con_state" value="">
										<input type="hidden" id="con_city" value="">
										<input type="hidden" id="con_pincode" value="">
										<input type="hidden" name="shipping_address_name" id="shipping_address_name" value="">
										<input type="hidden" name="shipping_address" id="shipping_address" value="">
										<input type="hidden" name="shipping_gst_no" id="shipping_gst_no" value="">
										<input type="hidden" name="shipping_phone" id="shipping_phone" value="">
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
										$invoice_query = 'select * from transaction_invoice_' . $m . '_' . $y . " where md5(transaction_id)='" . $_REQUEST['key'] . "'";
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
																		<option value="<?php echo (int) $pkg_r['package_id']; ?>" <?php if (ew_package_type_matches_stored($invoice_row['type_of_pkge'] ?? '', $pkg_r['package_id'], $pkg_r['package_code'])) echo 'selected'; ?>><?php echo htmlspecialchars($pkg_r['package_code']); ?></option>
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
													<input type="text" name="supplier_invoice_value" id="supplier_invoice_value" value="<?php echo $row['supplier_invoice_value']; ?>" class="form-control">
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
													<input type="text" name="lc_number" id="lc_number" value="<?php echo $row['lc_number']; ?>" class="form-control">
												</div>
												<div class="ew-field span-2">
													<label class="control-label">Description Of Goods</label>
													<textarea name="description_of_goods" id="description_of_goods" class="form-control" rows="2"><?php echo $row['description_of_goods']; ?></textarea>
												</div>
												<div class="ew-field span-2">
													<label class="control-label">CFS / Port / Factory / Warehouse</label>
													<?php echo ew_booking_cfs_controls_html($conn, $row['cfs'] ?? ''); ?>
												</div>
												<div class="ew-field">
													<label class="control-label">Part Number / Article Name / Article Number</label>
													<input type="text" name="vehicle_purchase_contact_person" value="<?php echo htmlspecialchars($row['vehicle_purchase_contact_person'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">Quotation approval</label>
													<select name="quotation_approval" id="quotation_approval" class="form-control">
														<option value="">Select option</option>
														<?php
														$qa = trim((string) ($row['quotation_approval'] ?? ''));
														$qa_opts = quotation_quotation_approval_options();
														if ($qa !== '' && !isset($qa_opts[$qa])) {
															$qa_opts = array($qa => $qa) + $qa_opts;
														}
														foreach ($qa_opts as $k => $lbl) {
															$sel = ($qa === (string) $k) ? ' selected' : '';
															echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') . '</option>';
														}
														?>
													</select>
												</div>
												<div class="ew-field">
													<label class="control-label">Truck / Vehicle No</label>
													<input type="text" name="vehicle_no" id="vehicle_no" value="<?php echo $row['truck']; ?>" class="form-control" autocomplete="off">
												</div>
												<div class="ew-field">
													<label class="control-label">Insurance No</label>
													<input type="text" name="insurance_number" id="insurance_number" value="<?php echo $row['insurance_number']; ?>" class="form-control">
												</div>
												<div class="ew-field">
													<label class="control-label">Vehicle Type</label>
													<?php echo ew_vehicle_type_transaction_select_html($conn, $row['vehicle_type'] ?? ''); ?>
												</div>
												<div class="ew-field">
													<label class="control-label">Highload Challan</label>
													<input type="text" name="highload_challan" value="<?php echo $row['highload_challan']; ?>" class="form-control">
												</div>
												<div class="ew-field" id="vehicle_type_dims_wrap">
													<label class="control-label" for="vehicle_type_dims_display">Vehicle Dimensions / CBM</label>
													<input type="text" id="vehicle_type_dims_display" class="form-control" readonly disabled value="<?php echo htmlspecialchars($initial_vehicle_type_dims_display, ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" aria-readonly="true" placeholder="Select vehicle type" />
												</div>
											</div>
											<input type="hidden" name="volumetric_weight" id="volumetric_weight" value="<?php echo $row['volumetric_weight']; ?>">
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
															} else {
															?>
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
											<h2 class="ew-card-section-title">Payment Information<?php if (!empty($billing_only_edit)) { ?> <small class="billing-only-note">(Editable after delivery — GST locked)</small><?php } ?></h2>
											<table class="table table-bordered payment-charges-table">
												<thead>
													<tr>
														<th class="text-left">Particulars</th>
														<th class="text-right">Rate</th>
														<th class="text-right">Amount (INR)</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td>
															Freight Charges
															<label class="freight-manual-toggle">
																<input type="checkbox" id="freight_manual_amount" name="freight_manual_amount" value="1"<?php
																if ($form_name === 'edit_consignment_details' && (float) ($row['frieght_amount'] ?? 0) > 0) {
																	echo ' checked';
																}
																?>>
																<span>Enter amount manually</span>
															</label>
														</td>
														<td><input type="text" name="frieght_rate" id="frieght_rate" value="<?php echo $row['frieght_rate'] ?? ''; ?>" class="form-control text-right" onchange="calc_charge_amt();" autocomplete="off" /></td>
														<td class="amt-col"><input type="text" name="frieght_amount" id="frieght_amount" value="<?php echo $row['frieght_amount'] ?? ''; ?>" class="form-control text-right calculation freight-amt-field" onchange="sum_amount();" readonly autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Doc.Charges</td>
														<td class="rate-empty text-right">—</td>
														<td class="amt-col"><input type="text" name="doc_amount" id="doc_amount" value="<?php echo $row['doc_amount'] ?? ''; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
													<tr>
														<td>Mamul Charges</td>
														<td class="rate-empty text-right">—</td>
														<td class="amt-col">
															<input type="text"
																name="mamul_charge"
																id="mamul_charge"
																value="<?php echo (($row['mamul_charge'] ?? '') != '' && ($row['mamul_charge'] ?? null) !== null) ? $row['mamul_charge'] : ''; ?>"
																onchange="sum_amount();"
																class="form-control text-right calculation">
														</td>
													</tr>
													<tr>
														<td>Vehicle Halting Charges</td>
														<td class="rate-empty text-right">—</td>
														<td class="amt-col">
															<input type="text"
																name="vehicle_halting_charge"
																id="vehicle_halting_charge"
																value="<?php echo (($row['vehicle_halting_charge'] ?? '') != '' && ($row['vehicle_halting_charge'] ?? null) !== null) ? $row['vehicle_halting_charge'] : ''; ?>"
																onchange="sum_amount();"
																class="form-control text-right calculation">
														</td>
													</tr>
													<tr>
														<td>Vehicle Loading / Unloading</td>
														<td class="rate-empty text-right">—</td>
														<td class="amt-col">
															<input type="text"
																name="vehicle_loading_unloading"
																id="vehicle_loading_unloading"
																value="<?php echo (($row['vehicle_loading_unloading'] ?? '') != '' && ($row['vehicle_loading_unloading'] ?? null) !== null) ? $row['vehicle_loading_unloading'] : ''; ?>"
																onchange="sum_amount();"
																class="form-control text-right calculation">
														</td>
													</tr>
													<tr>
														<td>Local Pickup &amp; Deliver Charges</td>
														<td class="rate-empty text-right">—</td>
														<td class="amt-col">
															<input type="text"
																name="cartage_amount"
																id="cartage_amount"
																value="<?php echo (($row['cartage_amount'] ?? '') != '' && ($row['cartage_amount'] ?? null) !== null) ? $row['cartage_amount'] : ''; ?>"
																onchange="sum_amount();"
																class="form-control text-right calculation"
																autocomplete="off">
														</td>
													</tr>
													<tr id="rajdhani_ex" style="display: none;">
														<td>Rajdhani Charges</td>
														<td class="rate-empty text-right">—</td>
														<td class="amt-col"><input type="text" name="rajdhani_charges" id="rajdhani_charges" value="<?php echo $row['rajdhani_charges'] ?? ''; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
													</tr>
												</tbody>
											</table>
											<input type="hidden" name="doc_rate" id="doc_rate" value="0">
											<input type="hidden" name="cartage_rate" id="cartage_rate" value="0">
											<input type="hidden" name="other_rate" id="other_rate" value="0">
											<input type="hidden" name="other_amount" id="other_amount" value="0">

											<div class="gst-config-block<?php echo !empty($billing_only_edit) ? ' gst-locked' : ''; ?>">
												<div class="form-group">
													<label for="gst_tax_id">GST Tax Profile</label>
													<select name="gst_tax_id" id="gst_tax_id" class="form-control"<?php echo !empty($billing_only_edit) ? ' disabled' : ''; ?>>
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
													<select name="gst_type" id="gst_type" class="form-control"<?php echo !empty($billing_only_edit) ? ' disabled' : ''; ?>>
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
															<th class="text-left">Component</th>
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
												<small class="gst-breakup-note">GST type is auto-selected from origin and destination state. Same state → CGST + SGST; different state → IGST.</small>
											</div>

											<div class="amount-in-words-block">
												<label for="amount_in_words">Amount In Words</label>
												<textarea name="amount_in_words" id="amount_in_words" rows="2" readonly class="form-control"><?php echo $row['total_words']; ?></textarea>
											</div>
											<?php if (!empty($billing_only_edit)) {
												$existing_gst_inv = trim((string) ($row['invoice_no'] ?? ''));
												$has_gst_inv = ($existing_gst_inv !== '' && strcasecmp($existing_gst_inv, 'NULL') !== 0);
												$billing_inv_mode_default = '';
												if ($has_gst_inv) {
													$billing_inv_mode_default = (stripos($existing_gst_inv, 'HROTH') === 0) ? 'other' : 'gst';
													$gst_inv_hint = 'Refreshes PDF for invoice ' . htmlspecialchars($existing_gst_inv, ENT_QUOTES, 'UTF-8') . '.';
												} else {
													$gst_inv_hint = 'Assigns a new invoice number and PDF using the amounts above.';
												}
												$gst_inv_q = array(
													'month' => $m,
													'year' => $y,
													'id' => (int) $transaction_id,
												);
												if ($has_gst_inv) {
													$gst_inv_q['invoice_no'] = $existing_gst_inv;
												}
												$other_inv_q = $gst_inv_q;
												$other_inv_q['invoice_doc'] = 'other';
												if ($billing_inv_mode_default === 'other') {
													$gst_inv_open_url = 'gst_invoice_page.php?' . http_build_query($other_inv_q);
												} else {
													$gst_inv_open_url = 'gst_invoice_page.php?' . http_build_query($gst_inv_q);
												}
												$gst_inv_download_q = $gst_inv_q;
												$gst_inv_download_q['download'] = '1';
												$other_inv_download_q = $other_inv_q;
												$other_inv_download_q['download'] = '1';
												$gst_inv_download_url = 'gst_invoice_page.php?' . http_build_query($gst_inv_download_q);
												$other_inv_download_url = 'gst_invoice_page.php?' . http_build_query($other_inv_download_q);
												$proforma_gst_q = array(
													'month' => $m,
													'year' => $y,
													'id' => (int) $transaction_id,
													'proforma' => '1',
												);
												$proforma_other_q = $proforma_gst_q;
												$proforma_other_q['invoice_doc'] = 'other';
												$proforma_gst_download_q = $proforma_gst_q;
												$proforma_gst_download_q['download'] = '1';
												$proforma_other_download_q = $proforma_other_q;
												$proforma_other_download_q['download'] = '1';
												$proforma_gst_preview_url = 'gst_invoice_page.php?' . http_build_query($proforma_gst_q);
												$proforma_other_preview_url = 'gst_invoice_page.php?' . http_build_query($proforma_other_q);
												$proforma_gst_download_url = 'gst_invoice_page.php?' . http_build_query($proforma_gst_download_q);
												$proforma_other_download_url = 'gst_invoice_page.php?' . http_build_query($proforma_other_download_q);
												$gcn_includes_gst = gcn_booking_includes_gst($row);
												$billing_inv_hroth_existing = $has_gst_inv && stripos($existing_gst_inv, 'HROTH') === 0;
												$billing_inv_show_mode_choice = !$gcn_includes_gst || $billing_inv_hroth_existing;
												if ($gcn_includes_gst && !$billing_inv_show_mode_choice && $billing_inv_mode_default === '') {
													$billing_inv_mode_default = 'gst';
												}
											?>
											<div class="form-group billing-generate-invoice-option" style="margin-top:12px;"
												data-gst-preview-url="<?php echo htmlspecialchars('gst_invoice_page.php?' . http_build_query($gst_inv_q), ENT_QUOTES, 'UTF-8'); ?>"
												data-gst-download-url="<?php echo htmlspecialchars($gst_inv_download_url, ENT_QUOTES, 'UTF-8'); ?>"
												data-other-preview-url="<?php echo htmlspecialchars('gst_invoice_page.php?' . http_build_query($other_inv_q), ENT_QUOTES, 'UTF-8'); ?>"
												data-other-download-url="<?php echo htmlspecialchars($other_inv_download_url, ENT_QUOTES, 'UTF-8'); ?>"
												data-proforma-gst-preview-url="<?php echo htmlspecialchars($proforma_gst_preview_url, ENT_QUOTES, 'UTF-8'); ?>"
												data-proforma-gst-download-url="<?php echo htmlspecialchars($proforma_gst_download_url, ENT_QUOTES, 'UTF-8'); ?>"
												data-proforma-other-preview-url="<?php echo htmlspecialchars($proforma_other_preview_url, ENT_QUOTES, 'UTF-8'); ?>"
												data-proforma-other-download-url="<?php echo htmlspecialchars($proforma_other_download_url, ENT_QUOTES, 'UTF-8'); ?>"
												data-show-invoice-mode-choice="<?php echo $billing_inv_show_mode_choice ? '1' : '0'; ?>"
												data-has-hroth-invoice="<?php echo $billing_inv_hroth_existing ? '1' : '0'; ?>">
												<div class="billing-invoice-mode-options" style="display:flex;flex-direction:column;gap:8px;">
													<label class="radio-inline billing-invoice-mode-gst" style="font-weight:normal;margin:0;">
														<input type="radio" name="billing_invoice_mode" value="gst"<?php echo $billing_inv_mode_default === 'gst' ? ' checked' : ''; ?>>
														<strong>Generate GST invoice</strong>
													</label>
													<label class="radio-inline billing-invoice-mode-other" style="font-weight:normal;margin:0;<?php echo $billing_inv_show_mode_choice ? '' : 'display:none;'; ?>">
														<input type="radio" name="billing_invoice_mode" value="other"<?php echo $billing_inv_mode_default === 'other' ? ' checked' : ''; ?>>
														<strong>Other</strong>
													</label>
													<label class="radio-inline billing-invoice-mode-proforma-gst" style="font-weight:normal;margin:0;<?php echo ($gcn_includes_gst && !$billing_inv_hroth_existing) ? '' : 'display:none;'; ?>">
														<input type="radio" name="billing_invoice_mode" value="proforma_gst">
														<strong>Proforma tax invoice</strong>
													</label>
													<label class="radio-inline billing-invoice-mode-proforma-other" style="font-weight:normal;margin:0;<?php echo (!$gcn_includes_gst || $billing_inv_hroth_existing) ? '' : 'display:none;'; ?>">
														<input type="radio" name="billing_invoice_mode" value="proforma_other">
														<strong>Proforma invoice</strong>
													</label>
												</div>
												<p class="help-block billing-invoice-mode-help" style="margin:8px 0 0 0;"><?php echo $gst_inv_hint; ?>
													<?php if ($billing_inv_show_mode_choice) { ?>
														Leave both options unselected to save payment only.
													<?php } else { ?>
														Leave unselected to save payment only.
													<?php } ?>
												</p>
												<div id="billing_gst_invoice_actions_wrap" class="billing-gst-invoice-actions" style="display:none;margin-top:10px;gap:8px;flex-wrap:wrap;">
													<a class="ew-btn-v2 ew-btn-v2-outline billing-gst-invoice-preview-link" href="<?php echo htmlspecialchars($gst_inv_open_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-external-link"></i> Preview invoice</a>
													<a class="ew-btn-v2 ew-btn-v2-primary billing-gst-invoice-download-link" href="<?php echo htmlspecialchars($billing_inv_mode_default === 'other' ? $other_inv_download_url : $gst_inv_download_url, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-download"></i> Download PDF</a>
												</div>
												<p class="help-block text-muted billing-gst-invoice-actions-hint" style="margin:8px 0 0 0;font-size:12px;">Select an option above to preview or download<?php echo $has_gst_inv ? '' : ' (save once to assign invoice number for Generate GST invoice / Other)'; ?>. Proforma does not assign an invoice number.</p>
											</div>
											<?php } ?>
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
													$transaction_image_query = 'select * from transaction_images_' . $m . '_' . $y . " where md5(transaction_id) = '" . $_REQUEST['key'] . "' and status=0";
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
								<?php if (!$form_fully_locked) { ?>
								<button class="ew-btn-v2 ew-btn-v2-primary save-button" type="button" id="save"><i class="fa fa-save"></i> Submit Booking</button>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>


		<?php require_once('include/footer.php'); ?>
	</div>

	<script src="include/calculation.js"></script>
	<script src="javascripts/ew-attachment-upload.js?v=20260922"></script>
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

		function applyFormFullyLocked() {
			if ($('#form_fully_locked').val() !== '1' && !$('#grn_details').hasClass('form-fully-locked')) {
				return;
			}
			$('#grn_details').addClass('form-fully-locked');
			$('#save').hide();
			$('#grn_details').find('input, select, textarea, button').not('.btn-cancel').each(function() {
				var $el = $(this);
				if (($el.attr('type') || '').toLowerCase() === 'hidden') {
					return;
				}
				if ($el.is('select') || $el.is('button')) {
					$el.prop('disabled', true);
				} else {
					$el.prop('readonly', true);
				}
			});
			$('.upload-dropzone, #add_more, .remove-image, #signature, .jSignature, .pkg-row-btn').css('pointer-events', 'none');
		}

		function applyBillingOnlyEditMode() {
			if (!$('#grn_details').hasClass('billing-only-edit') && $('#billing_only_edit').val() !== '1') {
				return;
			}
			$('#grn_details').addClass('billing-only-edit');

			var paymentAllow = {
				frieght_rate: 1,
				frieght_amount: 1,
				doc_amount: 1,
				mamul_charge: 1,
				vehicle_halting_charge: 1,
				vehicle_loading_unloading: 1,
				cartage_amount: 1,
				rajdhani_charges: 1,
				freight_manual_amount: 1,
				billing_invoice_mode: 1,
				total: 1,
				amount_in_words: 1
			};

			$('#grn_details').find('input, select, textarea').each(function() {
				var $el = $(this);
				var name = $el.attr('name') || '';
				var id = $el.attr('id') || '';
				var type = ($el.attr('type') || '').toLowerCase();
				if (type === 'hidden' || id === 'save' || $el.hasClass('btn-cancel')) {
					return;
				}
				if (paymentAllow[id] || paymentAllow[name] || $el.closest('.payment-charges-table').length) {
					if (id === 'frieght_amount') {
						if ($('#freight_manual_amount').is(':checked')) {
							$el.prop('readonly', false);
						} else {
							$el.prop('readonly', true);
						}
					} else if (id === 'frieght_rate') {
						if ($('#freight_manual_amount').is(':checked')) {
							$el.prop('readonly', true);
						} else {
							$el.prop('readonly', false);
						}
						$el.prop('disabled', false);
					} else {
						$el.prop('disabled', false).prop('readonly', false);
					}
					return;
				}
				if ($el.is('select') || type === 'checkbox' || type === 'radio' || type === 'file') {
					$el.prop('disabled', true);
				} else {
					$el.prop('readonly', true);
				}
			});

			$('#gst_tax_id, #gst_type').prop('disabled', true);
			$('.gst-config-block').addClass('gst-locked');
			$('input[name=billing_invoice_mode]').prop('disabled', false);
			syncBillingInvoiceModeChoice();
			$('.upload-dropzone, #add_more, .remove-image, #signature, .jSignature, .pkg-row-btn').css('pointer-events', 'none');
			$('.pkg-row-btn').prop('disabled', true);
		}

		function billingInvoiceModeSelected() {
			return $('input[name=billing_invoice_mode]:checked').val() || '';
		}

		function gcnBookingIncludesGst() {
			var $box = $('.billing-generate-invoice-option');
			if (!$box.length) {
				return false;
			}
			if (String($box.data('hasHrothInvoice')) === '1') {
				return false;
			}
			var gstType = String($('#gst_type').val() || '').toLowerCase();
			if (gstType === 'exempt' || gstType === 'non_gst') {
				return false;
			}
			var profile = typeof getSelectedGstProfile === 'function' ? getSelectedGstProfile() : null;
			if (profile && String(profile.tax_code).toUpperCase() === 'GST0') {
				return false;
			}
			var gstAmt = typeof parseAmount === 'function' ? parseAmount($('#gst_amount').val()) : parseFloat($('#gst_amount').val()) || 0;
			if (gstAmt > 0.001) {
				return true;
			}
			var comp = 0;
			['#disp_cgst_amount', '#disp_sgst_amount', '#disp_igst_amount'].forEach(function(sel) {
				comp += typeof parseAmount === 'function' ? parseAmount($(sel).text()) : parseFloat(String($(sel).text()).replace(/,/g, '')) || 0;
			});
			if (comp > 0.001) {
				return true;
			}
			if ($('#gst_tax_id').val() && profile && String(profile.tax_code).toUpperCase() !== 'GST0') {
				return true;
			}

			return false;
		}

		function syncBillingInvoiceModeChoice() {
			var $box = $('.billing-generate-invoice-option');
			if (!$box.length) {
				return;
			}
			var showChoice = String($box.data('hasHrothInvoice')) === '1' || !gcnBookingIncludesGst();
			$box.data('showInvoiceModeChoice', showChoice ? 1 : 0);
			var $other = $('.billing-invoice-mode-other');
			var $proformaGst = $('.billing-invoice-mode-proforma-gst');
			var $proformaOther = $('.billing-invoice-mode-proforma-other');
			var $gstRadio = $('input[name=billing_invoice_mode][value=gst]');
			var $otherRadio = $('input[name=billing_invoice_mode][value=other]');
			var $proformaGstRadio = $('input[name=billing_invoice_mode][value=proforma_gst]');
			var $proformaOtherRadio = $('input[name=billing_invoice_mode][value=proforma_other]');
			var includesGst = gcnBookingIncludesGst();
			if (showChoice) {
				$other.show();
			} else {
				$other.hide();
				if ($otherRadio.prop('checked')) {
					$otherRadio.prop('checked', false);
				}
			}
			if (includesGst) {
				$proformaGst.show();
				$proformaOther.hide();
				if ($proformaOtherRadio.prop('checked')) {
					$proformaOtherRadio.prop('checked', false);
				}
			} else {
				$proformaGst.hide();
				$proformaOther.show();
				if ($proformaGstRadio.prop('checked')) {
					$proformaGstRadio.prop('checked', false);
				}
			}
			syncBillingGstInvoiceActions();
		}

		function syncBillingInvoicePreviewLinks() {
			var $box = $('.billing-generate-invoice-option');
			if (!$box.length) {
				return;
			}
			var mode = billingInvoiceModeSelected();
			var preview = $box.data('gstPreviewUrl');
			var download = $box.data('gstDownloadUrl');
			if (mode === 'other') {
				preview = $box.data('otherPreviewUrl');
				download = $box.data('otherDownloadUrl');
			} else if (mode === 'proforma_gst') {
				preview = $box.data('proformaGstPreviewUrl');
				download = $box.data('proformaGstDownloadUrl');
			} else if (mode === 'proforma_other') {
				preview = $box.data('proformaOtherPreviewUrl');
				download = $box.data('proformaOtherDownloadUrl');
			}
			if (mode && preview) {
				$('.billing-gst-invoice-preview-link').attr('href', preview);
			}
			if (mode && download) {
				$('.billing-gst-invoice-download-link').attr('href', download);
			}
		}

		function syncBillingGstInvoiceActions() {
			var $wrap = $('#billing_gst_invoice_actions_wrap');
			var $hint = $('.billing-gst-invoice-actions-hint');
			if (!$wrap.length) {
				return;
			}
			var mode = billingInvoiceModeSelected();
			if (mode === 'gst' || mode === 'other' || mode === 'proforma_gst' || mode === 'proforma_other') {
				syncBillingInvoicePreviewLinks();
				$wrap.css('display', 'flex');
				$hint.hide();
			} else {
				$wrap.hide();
				$hint.show();
			}
		}

		$(document).on('change', 'input[name=billing_invoice_mode]', syncBillingGstInvoiceActions);

		var billingPreviewDraftTimer = null;
		var billingPreviewDraftXHR = null;

		function buildBillingDraftFormData() {
			if (typeof calculateGstBreakup === 'function') {
				calculateGstBreakup();
			}
			$('#grn_details').find('input:disabled, select:disabled, textarea:disabled').prop('disabled', false);
			var formData = new FormData(document.getElementById('grn_details'));
			formData.set('billing_preview_draft', '1');
			formData.delete('billing_invoice_mode');
			if (typeof applyBillingOnlyEditMode === 'function') {
				applyBillingOnlyEditMode();
			}
			return formData;
		}

		function saveBillingPreviewDraft(callback) {
			if ($('#billing_only_edit').val() !== '1') {
				if (callback) {
					callback(false);
				}
				return;
			}
			if (billingPreviewDraftXHR && billingPreviewDraftXHR.readyState !== 4) {
				billingPreviewDraftXHR.abort();
			}
			billingPreviewDraftXHR = $.ajax({
				url: 'save_details.php',
				type: 'post',
				dataType: 'json',
				data: buildBillingDraftFormData(),
				processData: false,
				contentType: false,
				success: function(result) {
					billingPreviewDraftXHR = null;
					var ok = result && String(result.result) === '1';
					if (ok) {
						var ts = Date.now();
						$('.billing-gst-invoice-preview-link, .billing-gst-invoice-download-link').each(function() {
							var href = $(this).attr('href') || '';
							href = href.replace(/([?&])_t=\d+/g, '$1').replace(/[?&]$/, '');
							var sep = href.indexOf('?') >= 0 ? '&' : '?';
							$(this).attr('href', href + sep + '_t=' + ts);
						});
					}
					if (callback) {
						callback(ok);
					}
				},
				error: function() {
					billingPreviewDraftXHR = null;
					if (callback) {
						callback(false);
					}
				}
			});
		}

		function scheduleBillingPreviewDraftSave() {
			if ($('#billing_only_edit').val() !== '1') {
				return;
			}
			var mode = billingInvoiceModeSelected();
			if (mode !== 'gst' && mode !== 'other' && mode !== 'proforma_gst' && mode !== 'proforma_other') {
				return;
			}
			clearTimeout(billingPreviewDraftTimer);
			billingPreviewDraftTimer = setTimeout(function() {
				saveBillingPreviewDraft();
			}, 450);
		}

		$(document).on('input change', '#grn_details.billing-only-edit .payment-charges-table input, #grn_details.billing-only-edit #frieght_rate, #grn_details.billing-only-edit #freight_manual_amount', function() {
			scheduleBillingPreviewDraftSave();
		});

		$(document).on('click', '.billing-gst-invoice-preview-link', function(e) {
			e.preventDefault();
			var url = $(this).attr('href');
			saveBillingPreviewDraft(function(ok) {
				if (ok) {
					window.open(url, '_blank', 'noopener,noreferrer');
				} else {
					ewFormToast('Could not sync payment amounts for preview.', 'error', 5000);
				}
			});
		});

		$(document).on('click', '.billing-gst-invoice-download-link', function(e) {
			e.preventDefault();
			var url = $(this).attr('href');
			saveBillingPreviewDraft(function(ok) {
				if (ok) {
					window.location.href = url;
				} else {
					ewFormToast('Could not sync payment amounts for download.', 'error', 5000);
				}
			});
		});

		var company_grn_mode = '<?php echo $comp_grn_mode; ?>';
		var gstProfiles = <?php echo $gst_profiles_json ?: '[]'; ?>;

		function parseAmount(val) {
			return parseFloat(val) || 0;
		}

		function roundRupee(val) {
			return Math.round(parseAmount(val));
		}

		function formatMoney(val) {
			return String(roundRupee(val));
		}

		function formatRate(val) {
			return parseAmount(val).toFixed(2);
		}

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
			return {
				originState: originState,
				destState: destState
			};
		}

		function determineGstTypeFromRoute() {
			var states = syncRouteStateFields();
			if (!states.originState || !states.destState) {
				return '';
			}
			return (parseInt(states.originState, 10) === parseInt(states.destState, 10)) ? 'intra' : 'inter';
		}

		function syncGstTypeLock() {
			var profile = getSelectedGstProfile();
			var routeType = determineGstTypeFromRoute();
			var $gstType = $('#gst_type');
			var billingOnly = $('#billing_only_edit').val() === '1' || $('#grn_details').hasClass('billing-only-edit');

			if (billingOnly) {
				$gstType.prop('disabled', true);
				$('#gst_tax_id').prop('disabled', true);
				return $gstType.val();
			}

			if (profile && String(profile.tax_code).toUpperCase() === 'GST0') {
				$gstType.val('exempt');
				$gstType.prop('disabled', true);
				return 'exempt';
			}

			if (routeType) {
				$gstType.val(routeType);
				$gstType.prop('disabled', true);
				return routeType;
			}

			$gstType.prop('disabled', false);
			return $gstType.val();
		}

		function determineGstType() {
			var selected = syncGstTypeLock();
			if (selected === 'exempt' || selected === 'non_gst') {
				return selected;
			}
			var routeType = determineGstTypeFromRoute();
			if (routeType) {
				return routeType;
			}
			if (selected === 'intra' || selected === 'inter') {
				return selected;
			}
			return '';
		}

		function getSelectedGstProfile() {
			var $opt = $('#gst_tax_id option:selected');
			if (!$opt.length || !$opt.val()) {
				return null;
			}
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
			} else if ($('#gst_type').val() === 'exempt') {
				hint = 'Exempt — no GST applied.';
			} else if ($('#gst_type').val() === 'non_gst') {
				hint = 'Non-GST — no GST applied.';
			}
			$('#gst_type_hint').text(hint);
		}

		function syncFreightManualMode() {
			var manual = $('#freight_manual_amount').is(':checked');
			var $rate = $('#frieght_rate');
			var $amt = $('#frieght_amount');
			if (manual) {
				$rate.prop('readonly', true);
				$amt.prop('readonly', false);
			} else {
				$rate.prop('readonly', false);
				$amt.prop('readonly', true);
				calc_charge_amt();
			}
		}

		function calculateTaxableValue() {
			var fields = [
				'#frieght_amount', '#doc_amount', '#mamul_charge', '#vehicle_halting_charge',
				'#vehicle_loading_unloading', '#cartage_amount', '#other_amount', '#rajdhani_charges'
			];
			var total = 0;
			fields.forEach(function(selector) {
				total += parseAmount($(selector).val());
			});
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
				taxable: taxable,
				tax_code: profile ? profile.tax_code : '',
				resolved_type: resolvedType,
				gst_rate: 0,
				cgst_rate: 0,
				sgst_rate: 0,
				igst_rate: 0,
				cess_rate: 0,
				cgst_amount: 0,
				sgst_amount: 0,
				igst_amount: 0,
				cess_amount: 0,
				gst_amount: 0,
				grand_total: taxable
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

		$(document).on('change', '#gst_tax_id', function() {
			refreshGstCalculation();
		});

		$(document).on('change', '#gst_type', function() {
			if (!$(this).prop('disabled')) {
				refreshGstCalculation();
			}
		});

		$(document).on('change', '#origin, #destination', function() {
			refreshGstCalculation();
		});

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

		/** All booking parties except the chosen consignor (when no customer mapping exists). */
		function bookingConsigneeOptions(consignorId) {
			var out = [];
			if (!bookingClients || !bookingClients.length) {
				return out;
			}
			$.each(bookingClients, function(i, c) {
				if (String(c.id) !== String(consignorId)) {
					out.push(c);
				}
			});
			return out;
		}

		function loadMappedConsignees(consignorId, selected, done) {
			if (!consignorId) {
				fillConsigneeNameSelect([], '', true);
				$('#consignee').val('');
				if (typeof done === 'function') {
					done();
				}
				return;
			}
			$.getJSON('fetch_details.php', {
				cmd: 'get_mapped_consignees',
				consignor: consignorId
			}, function(rows) {
				rows = rows || [];
				var hasMapped = rows.length > 0;
				if (hasMapped) {
					fillConsigneeNameSelect(rows, selected || '', false);
				} else if (selected) {
					var label = $('#consignee_name option[value="' + selected + '"]').text() || '';
					if (label) {
						fillConsigneeNameSelect([{ id: selected, name: label }], selected, false);
					} else {
						var fallback = bookingConsigneeOptions(consignorId);
						fillConsigneeNameSelect(fallback, selected, false);
					}
					$('#consignee').val(String(selected));
				} else {
					fillConsigneeNameSelect(bookingConsigneeOptions(consignorId), '', false);
					$('#consignee').val('');
				}
				if (typeof done === 'function') {
					done();
				}
			});
		}

		function branchDropdownWrap(selectId) {
			if (selectId === '#consignor_branch') {
				return '#consignor_branch_div';
			}
			if (selectId === '#bill_to_branch') {
				return '#bill_to_branch_div';
			}
			return '#consignee_branch_div';
		}

		function populateBranchDropdown(selectId, branches, excludeBranchId, preselectBranchId, emptyLabel) {
			var $sel = $(selectId);
			var divId = branchDropdownWrap(selectId);
			var currentVal = preselectBranchId ? String(preselectBranchId) : partySelectVal($sel);
			var noExclude = (selectId === '#bill_to_branch' || selectId === '#consignee_branch');
			try {
				if ($sel.data('select2')) $sel.select2('destroy');
			} catch (e) {}

			emptyLabel = emptyLabel || 'Select Branch';
			$sel.html('<option value="">' + emptyLabel + '</option>');

			if (!branches || !branches.length) {
				$(divId).hide();
				return;
			}

			$.each(branches, function(i, row) {
				var isExcluded = !noExclude && excludeBranchId && String(row.client_branch_id) === String(excludeBranchId);
				var $opt = $('<option></option>')
					.val(row.client_branch_id)
					.text(row.branch_name)
					.prop('disabled', isExcluded);
				if (isExcluded) {
					$opt.text(row.branch_name + ' (selected on other party)');
				}
				$sel.append($opt);
			});

			$(divId).show();
			initPartyNameSelect($sel, emptyLabel, false);
			if (currentVal && $sel.find('option[value="' + currentVal + '"]:not(:disabled)').length) {
				$sel.select2('val', String(currentVal));
			} else if ($sel.data('select2')) {
				$sel.select2('val', '');
			}
		}

		function refreshConsigneeBranchDropdowns(preBill, preShip) {
			var branches = cachedConsigneeBranches || [];
			var has = branches.length > 0;
			if (!has) {
				$('#bill_to_branch_div, #consignee_branch_div').hide();
				return;
			}
			populateBranchDropdown('#bill_to_branch', branches, null, preBill || partySelectVal($('#bill_to_branch')));
			populateBranchDropdown('#consignee_branch', branches, null, preShip || partySelectVal($('#consignee_branch')));
		}

		function syncBranchDropdowns() {
			if (cachedConsignorBranches.length && $('#consignor').val()) {
				populateBranchDropdown('#consignor_branch', cachedConsignorBranches, $('#consignee_branch').val());
			}
			if (cachedConsigneeBranches.length && $('#consignee').val()) {
				refreshConsigneeBranchDropdowns();
			}
		}

		function loadClientBranches(companyId, party, callback, syncRequest, preselectBranchId, preselectBillBranchId, preselectShipBranchId) {
			if (!companyId) {
				return;
			}
			$.ajax({
				url: 'fetch_details.php',
				type: 'GET',
				dataType: 'json',
				async: syncRequest !== true,
				data: {
					cmd: 'get_client_branches',
					company_id: companyId
				},
				success: function(branches) {
					if (party === 'consignor') {
						cachedConsignorBranches = branches || [];
						populateBranchDropdown('#consignor_branch', cachedConsignorBranches, $('#consignee_branch').val(), preselectBranchId);
					} else {
						cachedConsigneeBranches = branches || [];
						refreshConsigneeBranchDropdowns(preselectBillBranchId, preselectShipBranchId || preselectBranchId);
					}
					syncBranchDropdowns();
					if (callback) {
						callback(branches);
					}
				}
			});
		}

		var savedConsignorBranchId = <?php echo (int) ($row['consignor_branch_id'] ?? 0); ?>;
		var savedBillToBranchId = <?php echo (int) ($row['bill_to_branch_id'] ?? 0); ?>;
		var savedConsigneeBranchId = <?php echo (int) ($row['consignee_branch_id'] ?? 0); ?>;
		var bookingVehicleTypeDims = <?php echo $booking_vehicle_type_dims_json; ?>;

		function syncVehicleTypeDimsDisplay() {
			var val = $.trim($('#vehicle_type').val() || '');
			var text = val ? (bookingVehicleTypeDims[val] || '') : '';
			$('#vehicle_type_dims_display').val(text);
		}

		$(document).on('change', '#vehicle_type', syncVehicleTypeDimsDisplay);

		function syncCfsBookingField() {
			if (!$('#cfs_location_wrap').length) {
				return;
			}
			var kind = $('#cfs_kind').val();
			var val = '';
			if (kind === 'cfs') {
				val = $.trim($('#cfs_master_select').val() || '');
			} else {
				val = $.trim($('#cfs_text_input').val() || '');
			}
			$('#cfs').val(val);
		}

		function applyCfsKindUi() {
			if (!$('#cfs_location_wrap').length) {
				return;
			}
			var kind = $('#cfs_kind').val();
			if (kind === 'cfs') {
				$('#cfs_master_select').show().prop('disabled', false);
				$('#cfs_text_input').hide().prop('disabled', true);
			} else {
				$('#cfs_master_select').hide().prop('disabled', true);
				$('#cfs_text_input').show().prop('disabled', false);
			}
			syncCfsBookingField();
		}

		$(document).on('change', '#cfs_kind', function() {
			applyCfsKindUi();
		});
		$(document).on('change input', '#cfs_master_select, #cfs_text_input', syncCfsBookingField);
		applyCfsKindUi();

		function restoreEditConsignmentBranches() {
			if ($('#form_name').val() !== 'edit_consignment_details') {
				return;
			}
			var consignorId = $('#consignor').val();
			var consigneeId = $('#consignee').val();
			var loadConsigneeBranches = function() {
				if (!consigneeId) {
					return;
				}
				loadClientBranches(consigneeId, 'consignee', null, true, null, savedBillToBranchId || null, savedConsigneeBranchId || null);
			};
			if (consignorId) {
				loadClientBranches(consignorId, 'consignor', loadConsigneeBranches, true, savedConsignorBranchId || null);
			} else {
				loadConsigneeBranches();
			}
		}

		function reloadDestinationDropdown(originCityId, callback) {
			if (!originCityId) {
				if (callback) {
					callback();
				}
				return;
			}
			$.ajax({
				url: 'fetch_details.php',
				type: 'GET',
				dataType: 'json',
				data: {
					cmd: 'get_destination_consignor',
					id: originCityId
				},
				success: function(result) {
					var currentDest = $('#destination').val();
					$('#destination').html(result.destination);
					if (currentDest && $('#destination option[value="' + currentDest + '"]').length) {
						$('#destination').val(currentDest);
					}
					if (callback) {
						callback();
					}
				}
			});
		}

		function applyConsignorBranch(branchId) {
			if (!branchId) {
				return;
			}
			$.ajax({
				url: 'fetch_details.php',
				type: 'GET',
				dataType: 'json',
				data: {
					cmd: 'get_branch_details',
					branch_id: branchId
				},
				success: function(r) {
					var addr = window.ewJoinPartyAddress ? window.ewJoinPartyAddress(r) : [r.address1, r.address2, r.city_name, r.pincode].filter(Boolean).join(', ');
					$('#address1').val(addr);
					$('#address2').val('');
					$('#phone').val(r.contact_no || '');
					if (r.gst_no) {
						$('#gst_no').val(r.gst_no);
					}
					if (r.state) {
						$('#consignor_state_id').val(r.state);
					}

					if (r.city) {
						$('#origin').val(r.city);
						reloadDestinationDropdown(r.city, function() {
							refreshGstCalculation();
						});
					} else {
						refreshGstCalculation();
					}
				}
			});
		}

		function applyConsigneeBranch(branchId) {
			if (!branchId) {
				return;
			}
			$.ajax({
				url: 'fetch_details.php',
				type: 'GET',
				dataType: 'json',
				data: {
					cmd: 'get_branch_details',
					branch_id: branchId
				},
				success: function(r) {
					var addr = [r.address1, r.address2, r.city_name, r.state_name, r.pincode].filter(Boolean).join(', ');
					$('#con_address1').val(addr);
					$('#con_address2').val('');
					$('#con_phone').val(r.contact_no || '');
					if (r.gst_no) {
						$('#con_gst').val(r.gst_no);
					}
					if (r.state) {
						$('#consignee_state_id').val(r.state);
					}

					if (r.city) {
						$('#destination').val(r.city);
						refreshGstCalculation();
						load_payment_info();
					}
				}
			});
		}

		$(document).on('change', '#consignor_branch', function() {
			applyConsignorBranch($(this).val());
		});

		$(document).on('change', '#bill_to_branch', function() {
			var branchId = $(this).val();
			if (branchId) {
				$.ajax({
					url: 'fetch_details.php',
					type: 'GET',
					dataType: 'json',
					data: { cmd: 'get_branch_details', branch_id: branchId },
					success: function(r) {
						if (r && r.state) {
							$('#consignee_state_id').val(r.state);
							refreshGstCalculation();
						}
					}
				});
			}
		});

		$(document).on('change', '#consignee_branch', function() {
			applyConsigneeBranch($(this).val());
		});
		//Auto Calculation Part — FTL / Train Type UI disabled; Rajdhani row follows transport mode only
		function ewSyncRajdhaniRowForTransport() {
			var transport_type = $('#mode_of_trasport :selected').val();
			if (transport_type == '2') {
				$("#rajdhani_ex").show();
			} else {
				$("#rajdhani_ex").hide();
			}
		}
		ewSyncRajdhaniRowForTransport();
		if ($('#form_name').val() === 'edit_consignment_details') {
			var savedFreightOnLoad = parseFloat($('#frieght_amount').val()) || 0;
			if (savedFreightOnLoad > 0) {
				$('#freight_manual_amount').prop('checked', true);
				syncFreightManualMode();
			}
		}
		sum_amount();

		$(document).on('change', '#mode_of_trasport', function() {
			ewSyncRajdhaniRowForTransport();
			syncTransportRequiredFields();
			load_payment_info();
		});

		/* FTL Type & Train Type dropdown logic disabled
		var ftl_flag = ...
		$(document).on('change', '#dropp', ...
		$(document).on('change', '#train_type_sel', ...
		*/

		function handleSelectChange(event) {
			sum_amount();
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
			return roundRupee(num);
		}
		//End AddZero Function   

		//Fov Calculation (FOV field removed from payment table)
		function fov_calc() {
			sum_amount();
		}

		//End Fov

		//Calculate Amount
		function calc_charge_amt() {
			if ($('#freight_manual_amount').is(':checked')) {
				sum_amount();
				return;
			}
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
			var total_amt = roundRupee(parseFloat(rate) * parseFloat(charge_weight));

			if (!isNaN(total_amt)) {
				$('#frieght_amount').val(addZeroes(total_amt));
				// $('#frieght_amount').keypress();
				sum_amount()
			}
		}


		//End Calculate Amount


		//Sum Amount
		function sum_amount() {

			//Rajdhani Value Add + Remove

			var transport_type_gst = $('#mode_of_trasport :selected').val();
			var r_ch = $("#rajdhani_charges").val();
			if (transport_type_gst != '2') {
				r_ch = 0;
				$("#rajdhani_charges").val(r_ch);
			}
			//console.log('Rajdhani',r_ch);

			//End

			var breakup = calculateGstBreakup();
			var totals_pay = breakup.grand_total;

			if (!isNaN(totals_pay)) {
				$("#total").val(formatMoney(totals_pay));
				get_total();
			}
			scheduleBillingPreviewDraftSave();
			if ($('#billing_only_edit').val() === '1' && typeof syncBillingInvoiceModeChoice === 'function') {
				syncBillingInvoiceModeChoice();
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
						if (form_name == "edit_consignment_details") {
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
		// Party Invoice Function End

		//Payment Fetch Client Charges Start
		function load_payment_info() {
			var lockedFreightVal = null;
			if ($('#form_name').val() === 'edit_consignment_details') {
				var savedFreight = parseFloat($('#frieght_amount').val()) || 0;
				if (savedFreight > 0) {
					lockedFreightVal = $('#frieght_amount').val();
				}
			}
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
							$("#doc_amount").val(formatMoney(pay_inv_data.doc_chrgs));
							$("#mamul_charge").val(formatMoney(pay_inv_data.mamul_chrgs));
							$("#vehicle_halting_charge").val(formatMoney(pay_inv_data.vehicle_halting_charge));
							$("#vehicle_loading_unloading").val(formatMoney(pay_inv_data.vehicle_loading_unloading));
							// Local/other pickup is entered as cartage_amount; do not auto-fill hidden other_amount (avoids double GST).
							$("#other_amount").val('0');

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
									$("#doc_amount").val("");
									$("#mamul_charge").val("0");
									$("#vehicle_halting_charge").val("0");
									$("#vehicle_loading_unloading").val("0");
									$("#other_amount").val("");
								}
							} else {
								$("#frieght_rate").val(parseFloat("0.00").toFixed(2));

							}
							calculate_charge_weight();
							sum_amount();
							if (lockedFreightVal !== null) {
								$('#freight_manual_amount').prop('checked', true);
								syncFreightManualMode();
								$('#frieght_amount').val(lockedFreightVal);
								sum_amount();
							}

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
			syncVehicleTypeDimsDisplay();

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

		function syncTransportRequiredFields() {
			// FTL Type / Train Type required validation disabled while dropdowns are hidden
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

		//End

		$(document).ready(function() {
		$("#consignor_branch_div, #bill_to_branch_div, #consignee_branch_div").hide();
			var freightRateVal = parseFloat($('#frieght_rate').val()) || 0;
			var freightAmtVal = parseFloat($('#frieght_amount').val()) || 0;
			if (freightRateVal <= 0 && freightAmtVal > 0) {
				$('#freight_manual_amount').prop('checked', true);
			}
			syncFreightManualMode();
			$(document).on('change', '#freight_manual_amount', syncFreightManualMode);
			fillConsignorNameSelect($('#consignor').val() || '');
			if ($('#consignor').val()) {
				loadMappedConsignees($('#consignor').val(), $('#consignee').val() || '', function() {
					restoreEditConsignmentBranches();
				});
			} else {
				fillConsigneeNameSelect([], '', true);
				restoreEditConsignmentBranches();
			}

			$("#grn_details").validate({
				ignore: ':disabled, :hidden:not(#consignor):not(#consignee)',
				invalidHandler: function(event, validator) {
					ewFormToast('Please fill all mandatory fields.', 'error', 5000);
					if (validator.errorList.length) {
						var $first = $(validator.errorList[0].element);
						if ($first.attr('id') === 'consignor') {
							$first = $('#consignor_name');
						} else if ($first.attr('id') === 'consignee') {
							$first = $('#consignee_name');
						} else if (($first.attr('id') || '').indexOf('type_of_pkg') === 0) {
							$first.closest('td').length ? $first = $first.closest('td') : $first;
						} else if (($first.attr('id') || '').indexOf('dropp') === 0 || $first.attr('id') === 'train_type_sel') {
							$first = $first.closest('.form-group');
						}
						if ($first.length && $first.offset()) {
							$('html, body').animate({
								scrollTop: Math.max(0, $first.offset().top - 120)
							}, 300);
							try { $first.focus(); } catch (e) {}
						}
					}
				}
			});
			// Ensure settings apply even if global auto-validate already initialized the form
			var grnValidator = $('#grn_details').data('validator');
			if (grnValidator) {
				grnValidator.settings.ignore = ':disabled, :hidden:not(#consignor):not(#consignee)';
				grnValidator.settings.invalidHandler = function(event, validator) {
					ewFormToast('Please fill all mandatory fields.', 'error', 5000);
					if (validator.errorList && validator.errorList.length) {
						var $first = $(validator.errorList[0].element);
						if ($first.attr('id') === 'consignor') {
							$first = $('#consignor_name');
						} else if ($first.attr('id') === 'consignee') {
							$first = $('#consignee_name');
						} else if (($first.attr('id') || '').indexOf('type_of_pkg') === 0) {
							$first.closest('td').length ? $first = $first.closest('td') : $first;
						} else if (($first.attr('id') || '').indexOf('dropp') === 0 || $first.attr('id') === 'train_type_sel') {
							$first = $first.closest('.form-group');
						}
						if ($first.length && $first.offset()) {
							$('html, body').animate({
								scrollTop: Math.max(0, $first.offset().top - 120)
							}, 300);
							try { $first.focus(); } catch (e) {}
						}
					}
				};
			}

			applyFormFullyLocked();
			applyBillingOnlyEditMode();
			syncTransportRequiredFields();
			refreshPackageRowActions();
			calculate_charge_weight();

			$(document).on('click', '.pkg-row-btn.is-add', function() {
				addPackageRow();
			});
			$(document).on('click', '.pkg-row-btn.is-remove', function() {
				removePackageRow($(this).closest('.pkg-data-row'));
			});

			var form_name = $("#form_name").val();
			load_party_inv = new Array();
			if (form_name == "edit_consignment_details") {
				load_party_inv = $('input[name^=party_invoice]').map(function(idx, elem) {
					return $(elem).val();
				}).get(); // today					
			}
			console.log("Test OLd1,", load_party_inv);

			/* FTL / Train Type handlers disabled — see ewSyncRajdhaniRowForTransport above */

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

							$("#consignor").val(result['client_id']);
							fillConsignorNameSelect(result['client_id']);
							loadMappedConsignees(result['client_id'], '');
							// $('#address1').val(result['address1']);
							// $('#address2').val(result['address2']);
							var address = [result['address1'], result['address2']]
								.filter(function(item) {
									return item && item.trim() !== "";
								})
								.join(", ");

							$('#address1').val(address);
							$('#address2').val('');
							$('#city').val(result['city_name']);

							$('#state').val(result['state_name']);
							$('#pincode').val(result['pincode']);

							$('#phone').val(result['contact_no']);
							$('#gst_no').val(result['gst_no']);
							$('#consignee_name').focus();

						}, 300);


					},
					error: function(jqxhr) {
						$(".loading-page").hide();
						console.log(jqxhr.responseText);
					}
				});
			}
			$(document).on('change', '#destination', function(e) {
				reset_consignee();
				refreshGstCalculation();
			});
			$(document).on('change', '#origin', function(e) {
				var id = $(this).val();
				reset_consignor();
				reset_consignee();
				$.ajax({
					async: false,
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
							refreshGstCalculation();
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
					if (company_grn_mode !== 'company') {
						$("#id").val('');
						$("#grn_no").val('');
						$("#grn_no1").val('');
					}
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

						// Load Consignor Branches
						loadClientBranches(ui.item.id, 'consignor', null, true);

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
							async: false,
							success: function(result) {
								console.log(result);
								$('#consignee_name').prop("disabled", false);
								//alert(ui.item.id);
								if (company_grn_mode !== 'company') {
									if (ui.item.id == '3631') {
										let grn_no = result['grn_no'];
										$('#id').val(result['grn_id']);
										$('#grn_no').val(grn_no.toUpperCase())
										$('#grn_no1').val(grn_no.toUpperCase()).attr("disabled", true);

									} else {
										let grn_no = result['grn_no'];
										$('#id').val(result['grn_id']);
										$('#grn_no').val(grn_no.toUpperCase())
										$('#grn_no1').val(grn_no.toUpperCase()).attr("disabled", true);


									}
								}
								if ($('#origin').val() == "" && cachedConsignorBranches.length <= 1) {
									if (result['city']) {
										$('#origin').val(result['city']);
										reloadDestinationDropdown(result['city'], function() {
											refreshGstCalculation();
										});
									}
								}
								$("#consignor").val(ui.item.id);
								var address = [
									result['address1'],
									result['address2'],
									result['city_name'],
									result['state'],
									result['pincode']
								].filter(function(item) {
									return item && item.trim() !== "";
								}).join(", ");

								$('#address1').val(address);
								$('#address2').val('');

								$('#phone').val(result['contact_no']);
								$('#gst_no').val(result['gst_no']);
								$('#consignor_state_id').val(result['state_id'] || '');
								sum_amount();

								$(".consignor_name_val").removeClass("con_name_val1");
								$('#consignee_name').focus();

							},
							error: function(jqxhr) {
								ewToast(jqxhr.responseText, 'error');
							}
						});

						//Ajax Call For Check Invoice No
						if (conr_id != "" && conr_id != null) {
							party_invoice_details();
						}
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

						// Load Consignee Branches
						loadClientBranches(ui.item.id, 'consignee', null, true);

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

								if ($('#destination').val() == "" && cachedConsigneeBranches.length <= 1) {
									if (result['city']) {
										$('#destination').val(result['city']);
									}
								}
								$("#consignee").val(ui.item.id);
								// $('#con_address1').val(result['address1']);
								// $('#con_address2').val(result['address2']);
								var conAddress = [
									result['address1'],
									result['address2'],
									result['city_name'],
									result['state'],
									result['pincode']
								].filter(function(item) {
									return item && item.trim() !== "";
								}).join(", ");

								$('#con_address1').val(conAddress);
								$('#con_address2').val('');

								$('#con_phone').val(result['contact_no']);
								$('#con_gst').val(result['gst_no']);
								$('#consignee_state_id').val(result['state_id'] || '');
								sum_amount();
								$(".consignee_name_val").removeClass("con_name_val2");
								$('#no_of_pkg1').focus();
							}
						});
						if ($('#destination').val() != "" && $('#destination').val() != null) {
							load_payment_info();
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

			function reset_consignor() {

				$('#consignor').val('');
				if ($('#consignor_name').is('select')) {
					fillConsignorNameSelect('');
				} else {
					$('#consignor_name').val('');
				}
				$('#address1').val('');
				$('#address2').val('');
				$('#city').val('');
				$('#state').val('');
				$('#pincode').val('');
				$('#phone').val('');
				$('#gst_no').val('');
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
				$('#con_address1').val('');
				$('#con_address2').val('');
				$('#con_state').val('');
				$('#con_city').val('');
				$('#con_pincode').val('');
				$('#con_phone').val('');
				$('#con_gst').val('');
				$('#consignee_state_id').val('');
				cachedConsigneeBranches = [];
				$("#bill_to_branch_div, #consignee_branch_div").hide();
				$("#bill_to_branch, #consignee_branch").html('<option value="">Select Branch</option>');
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
				loadClientBranches(id, 'consignor', null, true);
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
						if ($('#origin').val() == '' && cachedConsignorBranches.length <= 1 && result && result['city']) {
							$('#origin').val(result['city']);
							if (typeof reloadDestinationDropdown === 'function') {
								reloadDestinationDropdown(result['city'], function() {
									refreshGstCalculation();
								});
							}
						}
						var address = [result['address1'], result['address2'], result['city_name'], result['state'], result['pincode']].filter(function(item) {
							return item && String(item).trim() !== '';
						}).join(', ');
						$('#address1').val(address);
						$('#address2').val('');
						$('#phone').val(result['contact_no']);
						$('#gst_no').val(result['gst_no']);
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
				loadClientBranches(id, 'consignee', null, true);
				$.ajax({
					url: 'fetch_details.php',
					type: 'GET',
					dataType: 'JSON',
					data: { cmd: 'get_client_details_consignment', tbl_id: id },
					success: function(result) {
						if ($('#destination').val() == '' && cachedConsigneeBranches.length <= 1 && result && result['city']) {
							$('#destination').val(result['city']);
							refreshGstCalculation();
						}
						var address = [result['address1'], result['address2'], result['city_name'], result['state'], result['pincode']].filter(function(item) {
							return item && String(item).trim() !== '';
						}).join(', ');
						$('#con_address1').val(address);
						$('#con_phone').val(result['contact_no']);
						$('#con_gst').val(result['gst_no']);
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
							$("#grn_error").html(result[1]).attr("style", "color:#DD111E");
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
			var signature_image = '<?php echo $row['consigner_signature']; ?>';

			if (signature_image && signature_image !== '') {
				$('#display_signature').html('<img src="' + signature_image + '" alt="Saved signature">');
				$('div#signatureparent').addClass('height_check');
			} else {
				$('#display_signature').empty();
				$('div#signatureparent').removeClass('height_check');
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
					$(this).val(roundRupee($(this).val()));
				else
					$(this).val("0");

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

			$('#signature img').remove();
			$('#tools').html('<button type="button" class="btn btn-default btn-sm" id="clear_signature"><i class="fa fa-eraser"></i> Clear Signature</button>');
			$(document).on('click', '#clear_signature', function() {
				$('#display_signature').html('');
				$("#signature").show();
				$('#signature').jSignature('clear');
				$("#signature_capture").val('');
				$("#display_signature").attr("style", "");
				$('div#signatureparent').removeClass('height_check');
			});

			var attachment_id = [];
			if (typeof ewInitAttachmentUpload === 'function') {
				ewInitAttachmentUpload({
					inputName: 'file_receipt[]',
					inputIdPrefix: 'file_receipt',
					onRemove: function($btn) {
						var image_id = $btn.attr('id');
						if (image_id) {
							attachment_id.push(image_id);
						}
					}
				});
			}

			$(document).on('click', '#save', function() {
				if ($('#form_fully_locked').val() === '1') {
					if (typeof ewFormToast === 'function') {
						ewFormToast('This GCN is already invoiced. Booking cannot be edited.', 'error', 5000);
					}
					return false;
				}

				//Select Consginor and Consginee
				const get_consigner_valll = $('.get_consigner_valll').val();
				const get_consignee_valll = $('.get_consignee_valll').val();
				$(".consignor_name_val").toggleClass("con_name_val1", get_consigner_valll === "");
				$(".consignee_name_val").toggleClass("con_name_val2", get_consignee_valll === "");
				//End

				// var values = $("input[name='file_receipt[]']")
				// 	.map(function() {

				// 		var imag_pre = $(this).parent().siblings('.img_pre_div').find('img').attr('src');
				// 		// alert(imag_pre);

				// 		if ($(this).val() == "" && imag_pre == 'images/no_image.png') {

				// 			$(this).parent().find("label").addClass("attach_required");

				// 		} else {

				// 			$(this).parent().find("label").removeClass("attach_required");
				// 		}
				// 		return $(this).val() + imag_pre;

				// 	}).get();

				var values = $("input[name='file_receipt[]']").map(function () {
					var imag_pre = $(this).closest('.file-group').find('.image_preview').attr('src') || '';
					return $(this).val() + imag_pre;
				}).get();

				// Attachment is optional
var file_receipt_validate = false;

// Remove validation message
$("input[name='file_receipt[]']").each(function () {
    $(this).parent().find("label").removeClass("attach_required");
});

				// alert(values);
				console.log(values);
				// var file_receipt_validate = values.some(item =>"images/no_image.png" )
				var file_receipt_validate = values.some(item => {
					if (item == "images/no_image.png" || item == '') {
						return true;
					}
				});
				console.log("file_receipt_validate", file_receipt_validate);

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
				var billingOnly = $('#billing_only_edit').val() === '1' || $('#grn_details').hasClass('billing-only-edit');

				$("label[for='type_of_pkg1']").text("Select Package");
				if (!billingOnly) {
					$('#party_invoice1').attr('required', 'required');

					if ($("#v_weight").val() == '') {
						$('#charged1').attr('required', 'required');
					} else {
						$('#charged1').removeAttr('required');
						$('#charged1').removeClass('error');
					}

					if ($("#charged1").val() == '') {
						$('#charged1').attr('required', 'required');
					} else {
						$('#charged1').removeAttr('required');
						$('#charged1').removeClass('error');
					}

					$('#no_of_pkg1').attr('required', 'required');
				} else {
					// After delivery: only payment/billing is editable — don't block on locked package fields
					$('#party_invoice1, #charged1, #no_of_pkg1').removeAttr('required').removeClass('error');
					$('label.error[for="party_invoice1"], label.error[for="charged1"], label.error[for="no_of_pkg1"]').remove();
				}

				var form_name = $("#form_name").val();
				let party_invoice_validate = $('input[name="party_invoice[]"].invoice_exist');
				console.log('FormName', form_name);

				// Validate WHILE locked fields are still disabled so :disabled ignore applies
				var isFormValid = true;
				if (billingOnly) {
					var totalVal = parseFloat($('#total').val()) || 0;
					var freightVal = parseFloat($('#frieght_amount').val()) || 0;
					if (totalVal <= 0 && freightVal <= 0) {
						ewFormToast('Please enter payment / billing amounts.', 'error', 5000);
						isFormValid = false;
					}
					var billingMode = billingInvoiceModeSelected();
					if (isFormValid && (freightVal > 0 || totalVal > 0) && billingMode === '') {
						if (!window.confirm('Freight/payment amounts are entered but no invoice option is selected.\n\nSave payment only without creating an invoice?')) {
							isFormValid = false;
						}
					}
					if (isFormValid && (billingMode === 'gst' || billingMode === 'other') && freightVal <= 0 && totalVal <= 0) {
						ewFormToast('Enter freight or total before generating an invoice.', 'error', 5000);
						isFormValid = false;
					}
				} else {
					syncTransportRequiredFields();
					syncPackageRowRequired();
					isFormValid = $('#grn_details').valid();
				}

				if (isFormValid && chck_key == true && get_consigner_valll !== "" && get_consignee_valll !== "") {

					// Re-enable after validation so destination/origin/GST values are posted
					syncCfsBookingField();
					$('#grn_details').find('input:disabled, select:disabled, textarea:disabled').prop('disabled', false);
					var formData = new FormData(document.getElementById("grn_details"));
					formData.set('consignor_branch', $('#consignor_branch').val() || '');
					formData.set('bill_to_branch', $('#bill_to_branch').val() || '');
					formData.set('consignee_branch', $('#consignee_branch').val() || '');
					formData.append('del_id', id);
					formData.append('length', length);
					formData.append('width', width);
					formData.append('height', height);
					formData.append('quanti', quanti);
					formData.append('vlm_weight', vlm_weight);

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
							ewFormToast('Booked Successfully', 'success', 5000);
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

						function handleBookingSaveResponse(result) {
							if (result && result['result'] == 1) {
								$(".loading-page").hide();
								if (edit_id == '') {
									showBookingSuccessModal(result['data'], result['tracking_code'] || '');
								} else {
									$(".form-data-saving").hide();
									var saveMsg = result['message'] || 'Saved Successfully';
									var toastType = 'success';
									if (result['invoice_error']) {
										saveMsg = 'Payment saved, but invoice failed: ' + result['invoice_error'];
										toastType = 'warning';
									} else if (result['invoice_warning']) {
										saveMsg = result['invoice_warning'];
										toastType = 'warning';
									} else if (result['invoice_no']) {
										saveMsg = (result['message'] || 'Payment saved.') + ' Use Open / Download on the form next time, or the invoice icon in the list.';
									}
									ewFormToast(saveMsg, toastType, 6000);
									setTimeout(function() {
										window.location.href = "transaction_list.php";
									}, 1200);
								}
								return true;
							}
							if (result && result['logout'] == 1) {
								$(".form-data-saving").hide();
								ewFormToast('Your session is expired! Please Log in again to continue.', 'error', 5000);
								setTimeout(function() {
									location.href = "logout.php";
								}, 1500);
								return true;
							}
							if (result) {
								$(".form-data-saving").hide();
								$('#save').prop('disabled', false);
								var failMsg = 'Booking Failed';
								if (result['sql_error']) {
									failMsg = result['sql_error'];
								}
								ewFormToast(failMsg, 'error', 6000);
								return true;
							}
							return false;
						}

						$.ajax({
							url: "save_details.php?id=" + attachment_id,
							type: "post",
							dataType: "json",
							data: formData,
							processData: false,
							contentType: false,
							success: function(result) {
								console.log(result);
								handleBookingSaveResponse(result);
							},
							error: function(jqxhr) {
								$(".loading-page").hide();
								$('#save').prop("disabled", false);
								console.log(jqxhr.responseText);
								var result = null;
								try {
									if (jqxhr.responseText) {
										result = JSON.parse(jqxhr.responseText);
									}
								} catch (e) {}
								if (handleBookingSaveResponse(result)) {
									return;
								}
								ewFormToast('A network error occurred. Please check the Transaction List to confirm whether this booking was saved before trying again.', 'warning', 6000);
							}
						});
					} else {
						ewFormToast('Invoice Already Exist', 'warning', 5000);
					}

				} else {
					if (!isFormValid) {
						return;
					}
					if (get_consigner_valll === "" || get_consignee_valll === "") {
						if (get_consigner_valll === "") {
							ewShowInlineFieldError($('#consignor_name'), 'Consignor is required');
						}
						if (get_consignee_valll === "") {
							ewShowInlineFieldError($('#consignee_name'), 'Consignee is required');
						}
						ewFormToast('Please fill all mandatory fields.', 'error', 5000);
					} else if (!chck_key) {
						ewFormToast('GRN number already exists. Please use a different number.', 'error', 5000);
					}
				}

			});

			//popupclose
			$(document).on('click', '.grn_close_popup', function() {
				$(".grn_no_popup").hide();
				$(".form-data-saving").hide();
				ewFormToast('Booked Successfully', 'success', 5000);
				setTimeout(function() {
					window.location.href = "transaction_list.php";
				}, 1200);
			});

			refreshGstCalculation();

			//Check if file exist
			var img_avail;
			//Check Files Validation
			function filesExistCheck() {
				var form_name = $("#form_name").val();

				var file_ids = document.getElementsByName('file_receipt[]');
				//console.log("Testname",file_ids.length);
				if (form_name != 'edit_consignment_details') {
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
							<span class="grn-booked-label">Tracking code</span>
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