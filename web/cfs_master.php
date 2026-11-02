<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/cfs_master_helpers.php');
require_once('include/ew_master_usage_helpers.php');

ew_cfs_master_ensure_schema($conn);
ew_geo_scope_ensure_column($conn, 'state');
ew_geo_scope_ensure_column($conn, 'city');
$rows = ew_cfs_master_list($conn, true);
$cfs_usage = ew_master_usage_map($conn, 'cfs', array_column($rows, 'cfs_id'));

$cfs_state_options_html = '<option value="">Select State</option>';
$state_query = 'SELECT state_id, state_name FROM state WHERE status=0 AND ' . ew_geo_scope_where($conn) . ' ORDER BY state_name';
$state_result = mysqli_query($conn, $state_query);
while ($state_row = mysqli_fetch_assoc($state_result)) {
	$cfs_state_options_html .= '<option value="' . (int) $state_row['state_id'] . '">'
		. htmlspecialchars($state_row['state_name']) . '</option>';
}

$cfs_city_options_html = '<option value="">Select City</option>';
$city_query = 'SELECT city_id, city_name FROM city WHERE status=0 AND ' . ew_geo_scope_where($conn) . ' ORDER BY city_name';
$city_result = mysqli_query($conn, $city_query);
while ($city_row = mysqli_fetch_assoc($city_result)) {
	$cfs_city_options_html .= '<option value="' . (int) $city_row['city_id'] . '">'
		. htmlspecialchars($city_row['city_name']) . '</option>';
}
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
			<?php require_once('include/header.php'); require_once('include/menu.php'); ?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Container Freight Stations</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>List of Container Freight Stations</h2>
								<div class="ew-toolbar-right">
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openCreateCfs">Add CFS <i class="fa fa-plus"></i></button>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th style="width:6%">S.No</th>
											<th style="width:10%">Code</th>
											<th>CFS name</th>
											<th style="width:12%">Country</th>
											<th style="width:12%">State</th>
											<th style="width:12%">City</th>
											<th style="width:8%">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$i = 1;
										foreach ($rows as $list) {
											$country_label = ew_cfs_master_row_country_name($conn, $list);
											$state_label = !empty($list['state']) ? get_state_name($conn, $list['state']) : '';
											$city_label = !empty($list['city']) ? get_city_name($conn, $list['city']) : '';
											?>
											<tr>
												<td class="text-center"><?php echo $i++; ?></td>
												<td><?php echo htmlspecialchars($list['cfs_code'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($list['cfs_name']); ?></td>
												<td><?php echo htmlspecialchars($country_label); ?></td>
												<td><?php echo htmlspecialchars($state_label); ?></td>
												<td><?php echo htmlspecialchars($city_label); ?></td>
												<td class="actions center-content">
													<?php echo ew_master_edit_action_html(array(
														'in_use' => !empty($cfs_usage[(int) $list['cfs_id']]),
														'button_id' => (int) $list['cfs_id'],
														'button_class' => 'table-actions btn-edit-cfs',
													)); ?>
												</td>
											</tr>
											<?php
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php require_once('include/footer.php'); ?>
		</div>
	</div>

	<div class="alert" id="alert-container" style="display:none;">
		<button type="button" class="close" data-dismiss="alert">x</button>
		<strong id="alert-status"></strong>
		<span id="alert-message"></span>
	</div>

	<div class="ew-v2-modal-backdrop" id="cfsModal">
		<div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
				<h3 id="cfsModalTitle">Create CFS</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
				<form class="form-horizontal" id="cfs_form">
					<input type="hidden" id="form_name" name="form_name" value="add_cfs_master">
					<input type="hidden" id="edit_id" name="edit_id" value="">
					<?php echo ew_geo_scope_hidden_input(); ?>
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					<div class="ew-form-grid">
						<div class="ew-field">
							<label class="control-label">CFS Code <span style="color:red;">*</span></label>
							<input type="text" id="cfs_code" name="cfs_code" class="form-control" disabled placeholder="<?php echo htmlspecialchars(ew_master_format_code(ew_master_code_prefix('CFS Master'), 1)); ?>" />
						</div>
						<div class="ew-field span-2">
							<label class="control-label">CFS name <span style="color:red;">*</span></label>
							<input type="text" name="cfs_name" id="cfs_name" class="form-control" required maxlength="255" />
						</div>
						<div class="ew-field">
							<label class="control-label">Country <span style="color:red;">*</span></label>
							<input type="text" name="country_name" id="cfs_country_name" class="form-control" required autocomplete="off" maxlength="150" />
						</div>
						<div class="ew-field">
							<label class="control-label">State <span style="color:red;">*</span></label>
							<select name="state" id="cfs_state" class="form-control" required>
								<?php echo $cfs_state_options_html; ?>
							</select>
						</div>
						<div class="ew-field">
							<label class="control-label">City <span style="color:red;">*</span></label>
							<select name="city" id="cfs_city" class="form-control" required>
								<?php echo $cfs_city_options_html; ?>
							</select>
						</div>
					</div>
				</form>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline btn-reset-cfs" data-ew-v2-close>Cancel</button>
				<button class="btn btn-primary" type="button" id="save_cfs">Submit</button>
			</div>
		</div>
	</div>

	<script>
	var cfsStateOptionsDefault = <?php echo json_encode($cfs_state_options_html); ?>;
	var cfsCityOptionsDefault = <?php echo json_encode($cfs_city_options_html); ?>;

	function cfsResetLocationFields() {
		$('#cfs_state').html(cfsStateOptionsDefault);
		$('#cfs_city').html(cfsCityOptionsDefault);
	}

	function cfsLoadStates(countryName, selectedState, done) {
		selectedState = selectedState || 0;
		countryName = $.trim(countryName || '');
		$.post('fetch_details.php', {
			cmd: 'get_state_options',
			country_name: countryName,
			selected_state: selectedState
		}, function(html) {
			if ($.trim(html)) {
				$('#cfs_state').html(html);
			} else {
				$('#cfs_state').html(cfsStateOptionsDefault);
			}
			if (!selectedState) {
				$('#cfs_city').html(cfsCityOptionsDefault);
			}
			if (typeof done === 'function') {
				done();
			}
		});
	}

	function cfsLoadCities(stateId, selectedCity, done) {
		selectedCity = selectedCity || 0;
		stateId = parseInt(stateId, 10) || 0;
		if (!stateId) {
			$('#cfs_city').html(cfsCityOptionsDefault);
			if (typeof done === 'function') {
				done();
			}
			return;
		}
		$.ajax({
			url: 'fetch_details.php',
			type: 'POST',
			data: {
				cmd: 'get_city_name',
				state_id: stateId,
				selected_city: selectedCity
			},
			success: function(html) {
				if ($.trim(html)) {
					$('#cfs_city').html(html);
				} else {
					$('#cfs_city').html(cfsCityOptionsDefault);
				}
				if (typeof done === 'function') {
					done();
				}
			},
			error: function() {
				$('#cfs_city').html(cfsCityOptionsDefault);
				if (typeof done === 'function') {
					done();
				}
			}
		});
	}

	$(document).ready(function() {
		$('#dataTable1').DataTable();

		$(document).on('change', '#cfs_country_name', function() {
			var keepState = parseInt($('#cfs_state').val(), 10) || 0;
			cfsLoadStates($(this).val(), keepState);
		});

		$('#cfs_form').on('change', '#cfs_state', function() {
			cfsLoadCities($(this).val(), 0);
		});

		$('#openCreateCfs').on('click', function() {
			$('#form_name').val('add_cfs_master');
			$('#edit_id').val('');
			$('#cfs_name').val('');
			$('#cfs_country_name').val('');
			cfsResetLocationFields();
			$.getJSON('fetch_details.php', { cmd: 'get_cfs_next_code' }, function(data) {
				$('#cfs_code').val(data && data.cfs_code ? data.cfs_code : '');
			});
			$('#cfsModalTitle').text('Create CFS');
			ewV2OpenModal('cfsModal');
		});

		$(document).on('click', '.btn-edit-cfs', function() {
			var tbl_id = $(this).attr('id');
			$.getJSON('fetch_details.php', { cmd: 'get_cfs_details', tbl_id: tbl_id }, function(result) {
				if (!result || !result.cfs_id) {
					return;
				}
				$('#form_name').val('edit_cfs_master');
				$('#edit_id').val(result.cfs_id);
				$('#cfs_code').val(result.cfs_code || '');
				$('#cfs_name').val(result.cfs_name || '');
				var stateId = parseInt(result.state, 10) || 0;
				var cityId = parseInt(result.city, 10) || 0;
				$('#cfs_country_name').val(result.country_name || '');
				cfsLoadStates(result.country_name || '', stateId, function() {
					cfsLoadCities(stateId, cityId);
				});
				$('#cfsModalTitle').text('Edit CFS');
				ewV2OpenModal('cfsModal');
			});
		});

		$(document).on('click', '.btn-reset-cfs', function() {
			$('#cfs_form')[0].reset();
			$('#form_name').val('add_cfs_master');
			$('#edit_id').val('');
			cfsResetLocationFields();
			ewV2CloseModal('cfsModal');
		});

		$('#save_cfs').on('click', function() {
			if (!$('#cfs_name').val().trim()) {
				$('#cfs_name').focus();
				return;
			}
			if (!$('#cfs_country_name').val().trim() || !$('#cfs_state').val() || !$('#cfs_city').val()) {
				alert('Please select Country, State and City.');
				return;
			}
			if ($.trim($('#edit_id').val())) {
				$('#form_name').val('edit_cfs_master');
			} else {
				$('#form_name').val('add_cfs_master');
			}
			$.post('save_details.php', $('#cfs_form').serialize(), function(data) {
				if ($.trim(data) === '1') {
					ewV2CloseModal('cfsModal');
					$('#alert-message').text('Saved successfully.');
					$('#alert-container').addClass('alert-success').slideDown(400, function() {
						location.reload();
					});
				} else {
					alert($.trim(data) || 'Save failed.');
				}
			});
		});
	});
	</script>
</body>
</html>
