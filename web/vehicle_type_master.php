<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/vehicle_type_helpers.php');

ew_vehicle_type_ensure_schema($conn);
$rows = ew_vehicle_type_list($conn, false);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.dataTable th.sorting:after,
		.dataTable th.sorting_desc:after {
			top: 17px;
			right: 3px;
		}

		.dataTable th.sorting:before,
		.dataTable th.sorting_asc:after {
			top: 10px;
			right: 3px;
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
								<h1 class="ew-page-title">Vehicle Type Master</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>All Vehicle Types</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<a href="vehicle_type.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th class="table-title" style="width:5%">S.No</th>
											<th class="table-title" style="width:10%">Code</th>
											<th class="table-title" style="width:18%">Type Name</th>
											<th class="table-title" style="width:16%">Dimensions</th>
											<th class="table-title" style="width:12%">Body Type</th>
											<th class="table-title" style="width:12%">Capacity</th>
											<th class="table-title" style="width:8%">Status</th>
											<th class="table-title sorting_disabled" style="width:11%">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$i = 1;
										foreach ($rows as $list) {
											$key = md5($list['vehicle_type_id']);
											?>
											<tr>
												<td class="text-center"><?php echo $i; ?></td>
												<td><?php echo htmlspecialchars($list['type_code']); ?></td>
												<td><?php echo htmlspecialchars($list['type_name']); ?></td>
												<td><?php echo htmlspecialchars(ew_vehicle_type_format_dimensions_compact($list)); ?></td>
												<td><?php echo htmlspecialchars(ew_vehicle_type_body_label($list['body_type'] ?? '')); ?></td>
												<td><?php echo htmlspecialchars(ew_vehicle_type_format_capacity($list)); ?></td>
												<td><?php echo ((int) $list['status'] === 0) ? 'Active' : 'Inactive'; ?></td>
												<td class="actions center-content">
													<div class="action-buttons">
														<a title="Edit" class="table-actions" href="vehicle_type.php?key=<?php echo urlencode($key); ?>"><i class="fa fa-pencil"></i></a>
														<?php if ((int) $list['status'] === 0) { ?>
															<a class="table-actions vt-type-active" data-status="0" title="Mark inactive" id="<?php echo (int) $list['vehicle_type_id']; ?>"><i class="fa fa-check"></i></a>
														<?php } else { ?>
															<a class="table-actions vt-type-active is-inactive" data-status="1" title="Mark active" id="<?php echo (int) $list['vehicle_type_id']; ?>"><i class="fa fa-times"></i></a>
														<?php } ?>
														<a title="Delete" href="javascript:void(0);" class="table-actions btn-trash btn-delete-vehicle-type" id="<?php echo (int) $list['vehicle_type_id']; ?>"><i class="fa fa-trash-o"></i></a>
													</div>
												</td>
											</tr>
											<?php
											$i++;
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

	<script type="text/javascript">
		$(document).ready(function() {
			$('#dataTable1').DataTable();

			$(document).on('click', '.vt-type-active', function() {
				var status1 = $(this).attr('data-status') === '1' ? '0' : '1';
				$.post('save_details.php', {
					form_name: 'inacv_vehicle_type',
					tbl_id: $(this).attr('id'),
					status: status1
				}, function(data) {
					if ($.trim(data) === '1') {
						location.reload();
					}
				});
			});

			$(document).on('click', '.btn-delete-vehicle-type', function() {
				var id = $(this).attr('id');
				if (!id) {
					return;
				}
				window.ewConfirmDelete({
					id: id,
					title: 'Delete vehicle type',
					message: 'Do you want to delete this vehicle type?',
					onConfirm: function(delId) {
						$.post('save_details.php', {
							form_name: 'delete_vehicle_type',
							tbl_id: delId
						}, function(data) {
							if ($.trim(data) === '1') {
								if (typeof ewToast === 'function') {
									ewToast('Deleted successfully.', 'success');
								}
								setTimeout(function() { location.reload(); }, 600);
							} else {
								if (typeof ewToast === 'function') {
									ewToast($.trim(data) || 'Delete failed.', 'error');
								} else {
									alert($.trim(data) || 'Delete failed.');
								}
							}
						});
					}
				});
			});
		});
	</script>
</body>

</html>
