<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_type_helpers.php');

expense_type_ensure_schema($conn);
expense_require_admin();
$expense_groups = expense_group_list($conn, true);
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
								<h1 class="ew-page-title">Expense Type Master</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>All Expense Types</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openCreateExpenseType">Create <i class="fa fa-plus"></i></button>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped expense_type_tab" id="dataTable1">
									<thead>
										<tr>
											<th class="table-title" style="width:8%">S.No</th>
											<th class="table-title" style="width:12%">Expense Code</th>
											<th class="table-title" style="width:24%">Expense Name</th>
											<th class="table-title" style="width:16%">Expense Group</th>
											<th class="table-title" style="width:12%">SAC Code</th>
											<th class="table-title" style="width:12%">Default Amount</th>
											<th class="table-title sorting_disabled" style="width:16%">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$query = 'SELECT c.*, g.group_name AS expense_group_name
											FROM expense_category c
											LEFT JOIN expense_group g ON g.group_id = c.expense_group_id
											ORDER BY c.expense_code_id ASC, c.category_id ASC';
										$result = mysqli_query($conn, $query);
										$i = 1;
										if ($result) {
											while ($list = mysqli_fetch_array($result)) {
												?>
												<tr>
													<td class="text-center"><?php echo $i; ?></td>
													<td><?php echo htmlspecialchars($list['category_code']); ?></td>
													<td><?php echo htmlspecialchars($list['category_name']); ?></td>
													<td><?php echo htmlspecialchars($list['expense_group_name'] ?: expense_type_group_label($list['expense_group'] ?? '', $conn)); ?></td>
													<td><?php echo htmlspecialchars($list['sac_code'] ?? ''); ?></td>
													<td class="text-right"><?php
														$def_amt = (float) ($list['default_amount'] ?? 0);
														echo $def_amt > 0 ? number_format($def_amt, 2) : '—';
													?></td>
													<td class="actions center-content">
														<div class="action-buttons">
															<a title="Edit" class="table-actions btn-edit-expense-type" id="<?php echo (int) $list['category_id']; ?>"><i class="fa fa-pencil"></i></a>
															<?php if ((int) $list['status'] === 0) { ?>
																<a class="table-actions exp-type-active" data-status="0" title="InActive" id="<?php echo $list['category_id']; ?>"><i class="fa fa-check"></i></a>
															<?php } else { ?>
																<a class="table-actions exp-type-active is-inactive" data-status="1" title="Active" id="<?php echo $list['category_id']; ?>"><i class="fa fa-times"></i></a>
															<?php } ?>
															<a title="Delete" href="javascript:void(0);" class="table-actions btn-trash btn-delete-expense-type" id="<?php echo (int) $list['category_id']; ?>"><i class="fa fa-trash-o"></i></a>
														</div>
													</td>
												</tr>
												<?php
												$i++;
											}
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

	<div class="ew-v2-modal-backdrop" id="expenseTypeModal">
		<div class="ew-v2-modal">
			<div class="ew-v2-modal-head">
				<h3 id="expenseTypeModalTitle">Add Expense Type</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
				<form class="form-horizontal" id="expense_type_form">
					<input type="hidden" id="form_name" name="form_name" value="add_expense_type">
					<input type="hidden" id="edit_id" name="edit_id" value="">
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					<div class="form-group">
						<label class="control-label">Expense Code <span style="color:red;">*</span> :</label>
						<input type="text" name="expense_code" id="expense_code" class="form-control" readonly required />
					</div>
					<div class="form-group">
						<label class="control-label">Expense Name <span style="color:red;">*</span> :</label>
						<input type="text" name="category_name" id="category_name" class="form-control" maxlength="150" required autocomplete="off" placeholder="e.g. Freight Charges" />
					</div>
					<div class="form-group">
						<label class="control-label">Expense Group <span style="color:red;">*</span> :</label>
						<select name="expense_group_id" id="expense_group_id" class="form-control" required>
							<option value="">Select expense group</option>
							<?php foreach ($expense_groups as $g) { ?>
								<option value="<?php echo (int) $g['group_id']; ?>"><?php echo htmlspecialchars($g['group_name']); ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label class="control-label">SAC Code :</label>
						<input type="text" name="sac_code" id="sac_code" class="form-control" maxlength="20" autocomplete="off" placeholder="e.g. 996511" />
					</div>
					<div class="form-group">
						<label class="control-label">Default Amount :</label>
						<input type="text" name="default_amount" id="default_amount" class="form-control text-right" maxlength="15" autocomplete="off" placeholder="Optional, e.g. 500.00" />
					</div>
				</form>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline btn-reset-expense-type" data-ew-v2-close>Cancel</button>
				<button type="button" class="btn btn-primary" id="save_expense_type">Submit</button>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			function resetExpenseTypeModal() {
				$('#form_name').val('add_expense_type');
				$('#edit_id').val('');
				$('#category_name').val('');
				$('#expense_group_id').val('');
				$('#sac_code').val('');
				$('#default_amount').val('');
				$('#response').hide();
			}

			$('#openCreateExpenseType').on('click', function() {
				resetExpenseTypeModal();
				$.getJSON('fetch_details.php', { cmd: 'get_expense_type_next_code' }, function(data) {
					$('#expense_code').val(data.expense_code || '');
					$('#expenseTypeModalTitle').text('Add Expense Type');
					$('#save_expense_type').text('Submit');
					ewV2OpenModal('expenseTypeModal');
				});
			});

			$(document).on('click', '.btn-edit-expense-type', function() {
				var tbl_id = $(this).attr('id');
				$.getJSON('fetch_details.php', { cmd: 'get_expense_type_details', tbl_id: tbl_id }, function(result) {
					if (!result || !result.category_id) {
						if (typeof ewToast === 'function') {
							ewToast('Could not load expense type.', 'error');
						}
						return;
					}
					$('#form_name').val('edit_expense_type');
					$('#edit_id').val(result.category_id || '');
					$('#expense_code').val(result.category_code || '');
					$('#category_name').val(result.category_name || '');
					$('#expense_group_id').val(result.expense_group_id || '');
					$('#sac_code').val(result.sac_code || '');
					var defAmt = parseFloat(result.default_amount || 0) || 0;
					$('#default_amount').val(defAmt > 0 ? defAmt.toFixed(2) : '');
					$('#expenseTypeModalTitle').text('Edit Expense Type');
					$('#save_expense_type').text('Update');
					ewV2OpenModal('expenseTypeModal');
				});
			});

			$('.btn-reset-expense-type').on('click', resetExpenseTypeModal);

			$('#save_expense_type').on('click', function() {
				if (!$('#expense_type_form').valid()) {
					return;
				}
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.ajax({
					url: 'save_details.php',
					type: 'post',
					data: $('#expense_type_form').serialize(),
					success: function(result) {
						if ($.trim(result) === '1') {
							ewV2CloseModal('expenseTypeModal');
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

			$(document).on('click', '.exp-type-active', function() {
				var status1 = $(this).attr('data-status') === '1' ? '0' : '1';
				$.post('save_details.php', {
					form_name: 'inacv_expense_category',
					tbl_id: $(this).attr('id'),
					status: status1
				}, function(data) {
					if ($.trim(data) === '1') {
						location.reload();
					}
				});
			});

			$(document).on('click', '.btn-delete-expense-type', function() {
				var id = $(this).attr('id');
				if (!id) {
					return;
				}
				window.ewConfirmDelete({
					id: id,
					title: 'Delete expense type',
					message: 'Do you want to delete this expense type? This action cannot be undone.',
					onConfirm: function(delId) {
						$.post('save_details.php', {
							form_name: 'delete_expense_type',
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

			if (window.location.search.indexOf('saved=1') !== -1 && typeof ewToast === 'function') {
				ewToast('Saved Successfully', 'success');
			}
		});
	</script>
</body>

</html>
