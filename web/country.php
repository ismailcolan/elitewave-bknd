<?php
require_once('include/connect.php');
require_once('include/function.php');
ew_country_ensure_table($conn);
$country_code_example = ew_master_format_code(ew_master_code_prefix('Country Master'), 1);
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
								<h1 class="ew-page-title">Country Lists</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>Country List</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openCreateCountry">
										Create <i class="fa fa-plus"></i>
									</button>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th class="table-title" style="width:8%">S.No</th>
											<th class="table-title" style="width:16%">Code</th>
											<th class="table-title">Country</th>
											<th class="table-title" style="width:12%">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$i = 1;
										$result = mysqli_query($conn, 'SELECT * FROM country ORDER BY country_name ASC, country_id ASC');
										while ($result && ($row = mysqli_fetch_assoc($result))) {
											?>
											<tr>
												<td class="text-center"><?php echo $i++; ?></td>
												<td><?php echo htmlspecialchars($row['country_code'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($row['country_name'] ?? ''); ?></td>
												<td class="actions">
													<div class="action-buttons">
														<a title="Edit" class="table-actions btn-edit" id="<?php echo (int) $row['country_id']; ?>"><i class="fa fa-pencil"></i></a>
														<?php if ((int) $row['status'] === 0) { ?>
															<a class="table-actions btn-active" data-status="0" title="InActive" id="<?php echo (int) $row['country_id']; ?>"><i class="fa fa-check"></i></a>
														<?php } else { ?>
															<a class="table-actions btn-active" style="color:red;" data-status="1" title="Active" id="<?php echo (int) $row['country_id']; ?>"><i class="fa fa-times"></i></a>
														<?php } ?>
														<a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo (int) $row['country_id']; ?>"><i class="fa fa-trash-o"></i></a>
													</div>
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

	<div class="modal fade popup_close" id="myModal">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
					<h4 class="modal-title" style="color:#fff">Alert!</h4>
				</div>
				<div class="modal-body">
					<h5>Do you want to delete this record?</h5>
					<div class="modal-footer">
						<button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button">Yes</button>
						<button class="btn btn-default-outline" data-dismiss="modal" type="button">No</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="ew-v2-modal-backdrop" id="countryModal">
		<div class="ew-v2-modal">
			<div class="ew-v2-modal-head">
				<h3 id="countryModalTitle">Create Country</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
				<form class="form-horizontal" id="country_form">
					<input type="hidden" id="form_name" name="form_name" value="add_country">
					<input type="hidden" id="edit_id" name="edit_id" value="">
					<div class="form-group">
						<label class="control-label">Country Code :</label>
						<input type="text" id="country_code" class="form-control" readonly placeholder="<?php echo htmlspecialchars($country_code_example); ?>" />
					</div>
					<div class="form-group">
						<label class="control-label">Country Name <span style="color:red;">*</span> :</label>
						<input type="text" id="country_name" name="country_name" class="form-control" required maxlength="150" />
					</div>
				</form>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Cancel</button>
				<button class="btn btn-primary" type="button" id="save_country">Submit</button>
			</div>
		</div>
	</div>

	<script>
	$(document).ready(function() {
		function countryNotice(message, ok) {
			$('.form-data-saving').hide();
			$('#alert-status').text(ok ? '' : 'Alert !!! ');
			$('#alert-message').text(message);
			$('#alert-container').removeClass('alert-success alert-danger').addClass(ok ? 'alert-success' : 'alert-danger').slideDown(400).fadeTo(900, 500).slideUp(600, function() {
				$(this).hide();
				if (ok) {
					location.reload();
				}
			});
		}

		$('#openCreateCountry').on('click', function() {
			$('#form_name').val('add_country');
			$('#edit_id').val('');
			$('#country_name').val('');
			$('#country_code').val('');
			$('#countryModalTitle').text('Create Country');
			$.getJSON('fetch_details.php', { cmd: 'get_country_next_code' }, function(data) {
				$('#country_code').val(data && data.country_code ? data.country_code : '');
			});
			ewV2OpenModal('countryModal');
		});

		$(document).on('click', '.btn-edit', function() {
			var tbl_id = $(this).attr('id');
			$.getJSON('fetch_details.php', { cmd: 'get_country_details', tbl_id: tbl_id }, function(result) {
				if (!result || !result.country_id) {
					return;
				}
				$('#form_name').val('edit_country');
				$('#edit_id').val(result.country_id);
				$('#country_code').val(result.country_code || '');
				$('#country_name').val(result.country_name || '');
				$('#countryModalTitle').text('Edit Country');
				ewV2OpenModal('countryModal');
			});
		});

		$('#save_country').on('click', function() {
			if (!$.trim($('#country_name').val())) {
				$('#country_name').focus();
				return;
			}
			$.post('save_details.php', $('#country_form').serialize(), function(data) {
				data = $.trim(data);
				if (data === '1') {
					ewV2CloseModal('countryModal');
					countryNotice('Saved successfully. Please wait until the page refreshes.', true);
				} else if (data === 'duplicate') {
					countryNotice('This country name already exists.', false);
				} else {
					countryNotice('Could not save the country.', false);
				}
			});
		});

		$(document).on('click', '.btn-trash', function() {
			$('.btn-confirm-delete').attr('id', $(this).attr('id'));
		});

		$(document).on('click', '.btn-confirm-delete', function() {
			$.post('save_details.php', { form_name: 'del_country', tbl_id: $(this).attr('id') }, function(data) {
				if ($.trim(data) === '1') {
					countryNotice('Country deleted successfully.', true);
				} else {
					countryNotice('Could not delete the country.', false);
				}
			});
		});

		$(document).on('click', '.btn-active', function() {
			var id = $(this).attr('id');
			var nextStatus = $(this).attr('data-status') === '1' ? '0' : '1';
			var msg = nextStatus === '0' ? 'activated' : 'inactivated';
			$.post('save_details.php', { form_name: 'inacv_country', tbl_id: id, status: nextStatus }, function(data) {
				if ($.trim(data) === '1') {
					countryNotice('Country is ' + msg + '.', true);
				} else {
					countryNotice('Could not update the country status.', false);
				}
			});
		});
	});
	</script>
</body>
</html>
