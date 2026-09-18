<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_type_helpers.php');

expense_type_ensure_schema($conn);
expense_require_admin();
$group_rows = expense_group_list($conn, false);
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
								<h1 class="ew-page-title">Expense Group Master</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>All Expense Groups</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openCreateExpenseGroup">Create <i class="fa fa-plus"></i></button>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th class="table-title" style="width:10%">S.No</th>
											<th class="table-title" style="width:22%">Group Code</th>
											<th class="table-title" style="width:48%">Group Name</th>
											<th class="table-title sorting_disabled" style="width:20%">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$i = 1;
										foreach ($group_rows as $list) {
											?>
											<tr>
												<td class="text-center"><?php echo $i; ?></td>
												<td><?php echo htmlspecialchars($list['group_code']); ?></td>
												<td><?php echo htmlspecialchars($list['group_name']); ?></td>
												<td class="actions center-content">
													<div class="action-buttons">
														<a title="Edit" class="table-actions btn-edit-expense-group" id="<?php echo (int) $list['group_id']; ?>"><i class="fa fa-pencil"></i></a>
														<?php if ((int) $list['status'] === 0) { ?>
															<a class="table-actions exp-group-active" data-status="0" title="InActive" id="<?php echo (int) $list['group_id']; ?>"><i class="fa fa-check"></i></a>
														<?php } else { ?>
															<a class="table-actions exp-group-active is-inactive" data-status="1" title="Active" id="<?php echo (int) $list['group_id']; ?>"><i class="fa fa-times"></i></a>
														<?php } ?>
														<a title="Delete" href="javascript:void(0);" class="table-actions btn-trash btn-delete-expense-group" id="<?php echo (int) $list['group_id']; ?>"><i class="fa fa-trash-o"></i></a>
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

	<div class="ew-v2-modal-backdrop" id="expenseGroupModal">
		<div class="ew-v2-modal">
			<div class="ew-v2-modal-head">
				<h3 id="expenseGroupModalTitle">Add Expense Group</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
				<form class="form-horizontal" id="expense_group_form">
					<input type="hidden" id="form_name" name="form_name" value="add_expense_group">
					<input type="hidden" id="edit_id" name="edit_id" value="">
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					<div class="form-group">
						<label class="control-label">Group Code :</label>
						<input type="text" name="group_code" id="group_code" class="form-control" readonly />
					</div>
					<div class="form-group">
						<label class="control-label">Group Name <span style="color:red;">*</span> :</label>
						<input type="text" name="group_name" id="group_name" class="form-control" maxlength="150" required autocomplete="off" placeholder="e.g. Direct / Trip" />
					</div>
				</form>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Cancel</button>
				<button type="button" class="btn btn-primary" id="save_expense_group">Submit</button>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			function resetExpenseGroupModal() {
				$('#form_name').val('add_expense_group');
				$('#edit_id').val('');
				$('#group_name').val('');
				$('#group_code').val('');
				$('#response').hide();
			}

			$('#openCreateExpenseGroup').on('click', function() {
				resetExpenseGroupModal();
				$.getJSON('fetch_details.php', { cmd: 'get_expense_group_next_code' }, function(data) {
					$('#group_code').val(data.group_code || '');
					$('#expenseGroupModalTitle').text('Add Expense Group');
					$('#save_expense_group').text('Submit');
					ewV2OpenModal('expenseGroupModal');
				});
			});

			$(document).on('click', '.btn-edit-expense-group', function() {
				var tbl_id = $(this).attr('id');
				$.getJSON('fetch_details.php', { cmd: 'get_expense_group_details', tbl_id: tbl_id }, function(result) {
					if (!result || !result.group_id) {
						if (typeof ewToast === 'function') {
							ewToast('Could not load expense group.', 'error');
						}
						return;
					}
					$('#form_name').val('edit_expense_group');
					$('#edit_id').val(result.group_id || '');
					$('#group_code').val(result.group_code || '');
					$('#group_name').val(result.group_name || '');
					$('#expenseGroupModalTitle').text('Edit Expense Group');
					$('#save_expense_group').text('Update');
					ewV2OpenModal('expenseGroupModal');
				});
			});

			$('#save_expense_group').on('click', function() {
				if (!$('#expense_group_form').valid()) {
					return;
				}
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.ajax({
					url: 'save_details.php',
					type: 'post',
					data: $('#expense_group_form').serialize(),
					success: function(result) {
						if ($.trim(result) === '1') {
							ewV2CloseModal('expenseGroupModal');
							if (typeof ewToast === 'function') {
								ewToast('Saved successfully.', 'success');
							}
							setTimeout(function() { location.reload(); }, 800);
						} else {
							$('#response .message').text(result || 'Save failed.');
							$('#response').show();
							$btn.prop('disabled', false);
						}
					},
					error: function(jqxhr) {
						if (typeof ewToast === 'function') {
							ewToast(jqxhr.responseText, 'error');
						}
						$btn.prop('disabled', false);
					}
				});
			});

			$(document).on('click', '.exp-group-active', function() {
				var status1 = $(this).attr('data-status') === '1' ? '0' : '1';
				$.post('save_details.php', {
					form_name: 'inacv_expense_group',
					tbl_id: $(this).attr('id'),
					status: status1
				}, function(data) {
					if ($.trim(data) === '1') {
						location.reload();
					}
				});
			});

			$(document).on('click', '.btn-delete-expense-group', function() {
				var id = $(this).attr('id');
				if (!id) {
					return;
				}
				window.ewConfirmDelete({
					id: id,
					title: 'Delete expense group',
					message: 'Do you want to delete this expense group? Types using it must be moved first.',
					onConfirm: function(delId) {
						$.post('save_details.php', {
							form_name: 'delete_expense_group',
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
