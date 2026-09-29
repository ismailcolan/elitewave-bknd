<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/vehicle_type_helpers.php');

ew_vehicle_type_ensure_schema($conn);

$key = $_REQUEST['key'] ?? '';
$row = ew_vehicle_type_empty_row();
$is_edit = ($key != '');
if (!$is_edit && empty($_GET['create'])) {
	header('Location: vehicle_type_master.php');
	exit;
}
if ($is_edit) {
	$loaded = ew_vehicle_type_get_by_key($conn, $key);
	if (!$loaded) {
		header('Location: vehicle_type_master.php');
		exit;
	}
	$row = array_merge($row, $loaded);
	foreach (array('dim_length', 'dim_width', 'dim_height', 'capacity', 'volume_capacity') as $nf) {
		if ($row[$nf] !== null && $row[$nf] !== '') {
			$row[$nf] = rtrim(rtrim(number_format((float) $row[$nf], 3, '.', ''), '0'), '.');
		}
	}
} else {
	$row['type_code'] = ew_vehicle_type_next_code($conn);
}
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2 .ew-form-grid--vehicle-type {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}

		.ew-form-grid--vehicle-type .ew-section-label {
			grid-column: 1 / -1;
			margin-top: 8px;
		}

		.ew-form-grid--vehicle-type .ew-section-label:first-child {
			margin-top: 0;
		}

		.ew-field-pair {
			display: flex;
			gap: 8px;
			align-items: stretch;
		}

		.ew-field-pair .form-control:first-child {
			flex: 1;
			min-width: 0;
		}

		.ew-field-pair .form-control.uom-select {
			flex: 0 0 100px;
			max-width: 120px;
		}

		@media (max-width: 991px) {
			.ew-page-v2 .ew-form-grid--vehicle-type {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}
		}

		@media (max-width: 640px) {
			.ew-page-v2 .ew-form-grid--vehicle-type {
				grid-template-columns: 1fr;
			}

			.ew-field-pair {
				flex-direction: column;
			}

			.ew-field-pair .form-control.uom-select {
				flex: 1;
				max-width: none;
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
					<div class="ew-page-v2">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<a href="vehicle_type_master.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php echo $is_edit ? 'Edit Vehicle Type' : 'Add Vehicle Type'; ?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="vehicle_type_master.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-form-body">
								<form id="vehicle_type_form">
									<input type="hidden" id="form_name" name="form_name" value="add_vehicle_type">
									<input type="hidden" id="edit_id" name="edit_id" value="<?php echo htmlspecialchars($key); ?>">

									<div id="response" class="alert alert-danger" style="display:none;">
										<div class="message" style="text-align:center"></div>
									</div>

									<div class="ew-form-grid ew-form-grid--vehicle-type">
										<div class="ew-section-label">Vehicle Type Master</div>

										<div class="ew-field">
											<label class="control-label">Vehicle Type Code :</label>
											<input type="text" name="type_code" id="type_code" class="form-control" value="<?php echo htmlspecialchars($row['type_code']); ?>" readonly autocomplete="off" />
										</div>
										<div class="ew-field">
											<label class="control-label">Status :</label>
											<select name="status" id="status" class="form-control">
												<option value="0" <?php echo ((int) $row['status'] === 0) ? 'selected' : ''; ?>>Active</option>
												<option value="1" <?php echo ((int) $row['status'] === 1) ? 'selected' : ''; ?>>Inactive</option>
											</select>
										</div>
										<div class="ew-field span-3">
											<label class="control-label">Vehicle Type Name <span style="color:red;">*</span> :</label>
											<input type="text" name="type_name" id="type_name" class="form-control" maxlength="150" required autocomplete="off" value="<?php echo htmlspecialchars($row['type_name']); ?>" placeholder="e.g. 32 FT Container" />
										</div>
										<div class="ew-field">
											<label class="control-label">Vehicle Category <span style="color:red;">*</span> :</label>
											<select name="vehicle_category" id="vehicle_category" class="form-control" required>
												<?php echo ew_vehicle_type_select_options_html(ew_vehicle_type_categories(), $row['vehicle_category'], 'Select Category'); ?>
											</select>
										</div>
										<div class="ew-field span-2">
											<label class="control-label">Description :</label>
											<input type="text" name="description" id="description" class="form-control" maxlength="500" autocomplete="off" value="<?php echo htmlspecialchars($row['description']); ?>" />
										</div>

										<div class="ew-section-label">Vehicle Specification</div>

										<div class="ew-field">
											<label class="control-label">Body Type :</label>
											<select name="body_type" id="body_type" class="form-control">
												<?php echo ew_vehicle_type_select_options_html(ew_vehicle_type_body_types(), $row['body_type'] ?? '', 'Select Body Type'); ?>
											</select>
										</div>
										<div class="ew-field">
											<label class="control-label">Length :</label>
											<input type="text" name="dim_length" id="dim_length" class="form-control ew-decimal-input" autocomplete="off" value="<?php echo htmlspecialchars($row['dim_length']); ?>" onpaste="return ewNumericPaste(event,this);" />
										</div>
										<div class="ew-field">
											<label class="control-label">Width :</label>
											<input type="text" name="dim_width" id="dim_width" class="form-control ew-decimal-input" autocomplete="off" value="<?php echo htmlspecialchars($row['dim_width']); ?>" onpaste="return ewNumericPaste(event,this);" />
										</div>
										<div class="ew-field">
											<label class="control-label">Height :</label>
											<input type="text" name="dim_height" id="dim_height" class="form-control ew-decimal-input" autocomplete="off" value="<?php echo htmlspecialchars($row['dim_height']); ?>" onpaste="return ewNumericPaste(event,this);" />
										</div>
										<div class="ew-field">
											<label class="control-label">Dimension UOM :</label>
											<select name="dimension_uom" id="dimension_uom" class="form-control">
												<?php echo ew_vehicle_type_uom_select_html($row['dimension_uom'] ?: 'FT'); ?>
											</select>
										</div>
										<div class="ew-field span-2">
											<label class="control-label">Capacity :</label>
											<div class="ew-field-pair">
												<input type="text" name="capacity" id="capacity" class="form-control ew-decimal-input" autocomplete="off" value="<?php echo htmlspecialchars($row['capacity']); ?>" onpaste="return ewNumericPaste(event,this);" />
												<select name="capacity_uom" id="capacity_uom" class="form-control uom-select">
													<?php echo ew_vehicle_type_uom_select_html($row['capacity_uom'] ?: 'Ton'); ?>
												</select>
											</div>
										</div>
										<div class="ew-field span-2">
											<label class="control-label">Volume Capacity :</label>
											<div class="ew-field-pair">
												<input type="text" name="volume_capacity" id="volume_capacity" class="form-control ew-decimal-input" autocomplete="off" value="<?php echo htmlspecialchars($row['volume_capacity']); ?>" onpaste="return ewNumericPaste(event,this);" />
												<select name="volume_uom" id="volume_uom" class="form-control uom-select">
													<?php echo ew_vehicle_type_uom_select_html($row['volume_uom'] ?: 'CBM'); ?>
												</select>
											</div>
										</div>
										<div class="ew-field">
											<label class="control-label">No. of Axles :</label>
											<input type="text" name="num_axles" id="num_axles" class="form-control" maxlength="3" autocomplete="off" value="<?php echo htmlspecialchars($row['num_axles']); ?>" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);" />
										</div>
										<div class="ew-field">
											<label class="control-label">No. of Wheels :</label>
											<input type="text" name="num_wheels" id="num_wheels" class="form-control" maxlength="3" autocomplete="off" value="<?php echo htmlspecialchars($row['num_wheels']); ?>" onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" onpaste="return ewNumericPaste(event,this);" />
										</div>
									</div>
								</form>
							</div>
							<div class="ew-form-footer">
								<a class="ew-btn-v2 ew-btn-v2-outline btn-reset" href="vehicle_type_master.php">Cancel</a>
								<button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="save_vehicle_type"><i class="fa fa-save"></i> <?php echo $is_edit ? 'Update' : 'Submit'; ?></button>
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
			$('#save_vehicle_type').on('click', function() {
				if (!$('#vehicle_type_form').valid()) {
					return;
				}
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.ajax({
					url: 'save_details.php',
					type: 'post',
					data: $('#vehicle_type_form').serialize(),
					success: function(result) {
						if ($.trim(result) === '1') {
							if (typeof ewToast === 'function') {
								ewToast('Saved successfully.', 'success');
							}
							setTimeout(function() {
								window.location.href = 'vehicle_type_master.php';
							}, 700);
						} else {
							$btn.prop('disabled', false);
							$('#response .message').text(result || 'Save failed.');
							$('#response').show();
						}
					},
					error: function(jqxhr) {
						$btn.prop('disabled', false);
						$('#response .message').text(jqxhr.responseText || 'Network error.');
						$('#response').show();
					}
				});
			});
		});
	</script>
</body>

</html>
