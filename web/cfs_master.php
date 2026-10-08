<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/cfs_master_helpers.php');

ew_cfs_master_ensure_schema($conn);
$rows = ew_cfs_master_list($conn, true);
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
								<h1 class="ew-page-title">CFS Master</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>CFS names for booking (dropdown)</h2>
								<div class="ew-toolbar-right">
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openCreateCfs">Add CFS <i class="fa fa-plus"></i></button>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th style="width:6%">S.No</th>
											<th style="width:12%">Code</th>
											<th>CFS name</th>
											<th style="width:10%">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$i = 1;
										foreach ($rows as $list) {
											?>
											<tr>
												<td class="text-center"><?php echo $i++; ?></td>
												<td><?php echo htmlspecialchars($list['cfs_code'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($list['cfs_name']); ?></td>
												<td class="actions center-content">
													<a title="Edit" class="table-actions btn-edit-cfs" id="<?php echo (int) $list['cfs_id']; ?>"><i class="fa fa-pencil"></i></a>
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
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					<div class="form-group">
						<label class="control-label">CFS Code <span style="color:red;">*</span></label>
						<input type="text" id="cfs_code" name="cfs_code" class="form-control" disabled placeholder="CFS001" />
					</div>
					<div class="form-group">
						<label class="control-label">CFS name <span style="color:red;">*</span></label>
						<input type="text" name="cfs_name" id="cfs_name" class="form-control" required maxlength="255" />
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
	$(document).ready(function() {
		$('#dataTable1').DataTable();

		$('#openCreateCfs').on('click', function() {
			$('#form_name').val('add_cfs_master');
			$('#edit_id').val('');
			$('#cfs_name').val('');
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
				$('#cfsModalTitle').text('Edit CFS');
				ewV2OpenModal('cfsModal');
			});
		});

		$(document).on('click', '.btn-reset-cfs', function() {
			$('#cfs_form')[0].reset();
			$('#form_name').val('add_cfs_master');
			$('#edit_id').val('');
			ewV2CloseModal('cfsModal');
		});

		$('#save_cfs').on('click', function() {
			if (!$('#cfs_name').val().trim()) {
				$('#cfs_name').focus();
				return;
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
