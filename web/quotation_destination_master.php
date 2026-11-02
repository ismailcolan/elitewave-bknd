<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/vehicle_type_helpers.php');
require_once('include/quotation_destination_master.php');

ew_vehicle_type_ensure_schema($conn);
quotation_dest_master_ensure_tables($conn);

$singleton = quotation_dest_master_get_singleton($conn);
$id = $singleton ? (int) $singleton['master_id'] : 0;

$page_title_suffix = '';
if ($id > 0) {
	$page_title_suffix = trim((string) ($singleton['master_name'] ?? ''));
	$dest_rows_form = quotation_dest_master_hydrate_rows_for_form($conn, $id);
} else {
	$dest_rows_form = quotation_default_destination_rows_for_form();
}

$modes = quotation_mode_transport_catalog($conn);
$city_catalog = quotation_city_state_catalog($conn);
$vehicle_types = ew_vehicle_type_list($conn, true);
$delivery_days_opts = quotation_delivery_days_options();
$empty_row = quotation_default_destination_rows_for_form()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		#md_dest_table input.form-control, #md_dest_table select.form-control { height: 34px; font-size: 12px; padding: 4px 6px; }
		#md_dest_table .md-row-actions { width: 72px; white-space: nowrap; vertical-align: middle; }
		.md-row-action .md-row-btn {
			display: inline-flex; align-items: center; justify-content: center;
			width: 28px; height: 28px; border: 1px solid #cbd5e1; border-radius: 6px;
			background: #fff; cursor: pointer; padding: 0; line-height: 1;
		}
		.md-row-action .md-row-btn + .md-row-btn { margin-left: 4px; }
		.md-row-action .md-row-btn.is-add { color: #059669; border-color: #86efac; }
		.md-row-action .md-row-btn.is-add:hover { background: #ecfdf5; }
		.md-row-action .md-row-btn.is-remove { color: #dc2626; border-color: #fca5a5; }
		.md-row-action .md-row-btn.is-remove:hover { background: #fef2f2; }
		.ew-dest-master-footer { padding: 16px 24px 24px; margin-top: 16px; border-top: 1px solid #e2e8f0; }
		#md_dest_table th .req { color: #dc2626; font-weight: 700; }
		#md_dest_table .md-field-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
		#md_dest_table select.md-field-error.select2-hidden-accessible + .select2-container .select2-selection--single {
			border-color: #dc2626 !important;
			background-color: #fef2f2 !important;
		}
	</style>
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
	<div class="navbar navbar-fixed-top scroll-hide">
		<?php require_once('include/header.php'); require_once('include/menu.php'); ?>
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="row">
			<div class="col-md-12">
				<div class="ew-page-v2 ew-page-v2--invoice">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<a href="quotation_consignor_multi_dest_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title">Quotation Master</h1>
						</div>
					</div>

					<form id="dest_master_form">
						<input type="hidden" name="form_name" value="save_quotation_dest_master" />
						<input type="hidden" name="master_id" id="master_id" value="<?php echo (int) $id; ?>" />
						<div id="response" class="alert alert-danger" style="display:none;margin:0 16px 12px;"><div class="message" style="text-align:center"></div></div>

						<div class="ew-card ew-invoice-details-card">
							<h2 class="ew-card-section-title">Destinations &amp; charges</h2>
							<p class="ew-field-hint" style="margin:0 0 10px;"></p>
							<div class="ew-table-wrap">
								<table class="table table-bordered table-condensed" id="md_dest_table">
									<thead>
										<tr>
											<th style="min-width:180px;">Destination (City — State)</th>
											<th style="min-width:120px;">Mode</th>
											<th style="min-width:180px;">Source of transport</th>
											<th style="min-width:110px;">Delivery days <span class="req">*</span></th>
											<th>Freight charges <span class="req">*</span></th>
											<th>Pickup &amp; Delivery <span class="req">*</span></th>
											<th class="text-center md-row-actions"> </th>
										</tr>
									</thead>
									<tbody id="md_dest_tbody">
										<?php foreach ($dest_rows_form as $dr) {
											echo quotation_render_destination_row_html($conn, $dr, $city_catalog, $modes, $vehicle_types, $delivery_days_opts, true);
										} ?>
									</tbody>
								</table>
							</div>
							<div class="ew-form-footer ew-invoice-form-footer ew-dest-master-footer">
								<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-save-dest-master" id="btn_save_dest_master">Save</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>

<div id="md_row_template" style="display:none;">
	<table><tbody><?php echo quotation_render_destination_row_html($conn, $empty_row, $city_catalog, $modes, $vehicle_types, $delivery_days_opts, true); ?></tbody></table>
</div>

<script>
window.QUOTATION_DEST_MASTER_ONLY = true;
window.QUOTATION_DEST_MASTER_MAX_ROWS = <?php echo (int) QUOTATION_DEST_MASTER_MAX_ROWS; ?>;
</script>
<script src="javascripts/quotation-consignor-multi-dest-form.js?v=20261102inline"></script>
<script src="javascripts/quotation-destination-master-form.js?v=20261102save"></script>
</body>
</html>
