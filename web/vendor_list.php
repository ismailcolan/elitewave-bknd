<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/vendor_master_helpers.php');

ew_vendor_ensure_table($conn);

$data = array();
$query = 'SELECT * FROM vendor_master ORDER BY vendor_name';
$result = mysqli_query($conn, $query);
if ($result) {
	while ($row1 = mysqli_fetch_array($result)) {
		$data[] = $row1;
	}
}
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.table td.actions .action-buttons {
			width: 100px;
		}

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

		.paging_full_numbers {
			width: 50%;
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
				<div class="col-md-offset-1 col-md-10">
					<div class="widget-container fluid-height clearfix">
						<div class="heading"><i class="fa fa-table"></i> List of Vendor <span class="align-right"><i class="fa fa-plus"></i> <a href="vendor.php">Add Vendor</a></span></div>
						<div class="widget-content padded clearfix new_dept">
							<table class="table table-bordered table-striped" id="dataTable1">
								<thead>
									<th class="table-title" style="width:5%">S.No</th>
									<th class="table-title" style="width:12%">Vendor Code</th>
									<th class="table-title" style="width:20%">Vendor Name</th>
									<th class="table-title" style="width:15%">Vendor Type</th>
									<th class="table-title" style="width:15%">Contact Person</th>
									<th class="table-title" style="width:12%">Contact No</th>
									<th class="table-title" style="width:10%">Status</th>
									<th class="table-title" style="width:11%">Action</th>
								</thead>
								<tbody>
									<?php
									$i = 1;
									foreach ($data as $row) {
									?>
										<tr>
											<td class="text-center"><?php echo $i; ?></td>
											<td><?php echo htmlspecialchars($row['vendor_code']); ?></td>
											<td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
											<td><?php echo htmlspecialchars(ew_vendor_type_label($row['vendor_type'])); ?></td>
											<td><?php echo htmlspecialchars($row['contact_person']); ?></td>
											<td><?php echo htmlspecialchars($row['contact_no']); ?></td>
											<td><?php echo ((int) $row['status'] === 0) ? 'Active' : 'Inactive'; ?></td>
											<td class="actions center-content">
												<div class="action-buttons">
													<a title="Edit" href="vendor.php?key=<?php echo md5($row['vendor_id']); ?>" class="table-actions btn-edit"><i class="fa fa-pencil"></i></a>
													<?php if ((int) $row['status'] === 0) { ?>
														<a class="table-actions btn-active" data-status="<?php echo $row['status']; ?>" title="InActive" id="<?php echo $row['vendor_id']; ?>"><i class="fa fa-check"></i></a>
													<?php } else { ?>
														<a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status']; ?>" title="Active" id="<?php echo $row['vendor_id']; ?>"><i class="fa fa-times"></i></a>
													<?php } ?>
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

	<script type="text/javascript">
		$(document).ready(function() {
			$(document).on('click', '.btn-active', function() {
					$('.form-data-saving').show();
					var status1 = '';
					var msg = '';
					var status = $(this).attr('data-status');
					if (status == '1') {
						status1 = '0';
						msg = 'Activated';
					} else {
						status1 = '1';
						msg = 'In-Activated';
					}
					$.post('save_details.php', {
						form_name: 'inacv_vendor',
						tbl_id: $(this).attr('id'),
						status: status1
					}, function(data) {
						if (data == 1) {
							$('.form-data-saving').hide();
							$('#alert-status').text('');
							$('#alert-message').text('Vendor Is ' + msg + '...');
							$('#alert-container').addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
								$('#alert-container').hide();
								$('#alert-container').removeClass('alert-success');
								location.reload();
							});
						} else {
							$('.form-data-saving').hide();
							$('#alert-status').text('Alert !!! ');
							$('#alert-message').text('Vendor status update failed');
							$('#alert-container').addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
								$('#alert-container').hide();
								$('#alert-container').removeClass('alert-danger');
							});
						}
					});
				});
			});
	</script>
</body>

</html>
