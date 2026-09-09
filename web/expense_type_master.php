<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_type_helpers.php');

expense_type_ensure_schema($conn);
expense_require_admin();

$key = $_REQUEST['key'] ?? '';
$row = array(
	'category_code' => '',
	'expense_type_code' => '',
);
$is_edit = ($key != '');

if ($is_edit) {
	$q = mysqli_query($conn, "SELECT * FROM expense_category WHERE md5(category_id)='" . mysqli_real_escape_string($conn, $key) . "'");
	if (!$q || mysqli_num_rows($q) === 0) {
		header('Location: expense_type_master.php');
		exit;
	}
	$row = mysqli_fetch_array($q);
} else {
	$next = expense_type_next_code($conn);
	$row['category_code'] = $next['expense_code'];
}
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.expense-type-wrap {
			display: flex;
			gap: 8px;
			align-items: stretch;
		}

		.expense-type-wrap select {
			flex: 1;
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
				<div class="col-md-3 master_left">
					<div class="widget-container fluid-height clearfix">
						<div class="heading"><i class="fa fa-plus"></i> <?php echo $is_edit ? 'Edit Expense Type' : 'Add Expense Type'; ?></div>
						<div class="widget-content padded">
							<form class="form-horizontal" id="expense_type_form">
								<input type="hidden" id="form_name" name="form_name" value="<?php echo $is_edit ? 'edit_expense_type' : 'add_expense_type'; ?>">
								<input type="hidden" id="edit_id" name="edit_id" value="<?php echo htmlspecialchars($key); ?>">

								<div id="response" class="alert alert-danger" style="display:none;">
									<div class="message" style="text-align:center"></div>
								</div>

								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label">Expense Code <span style="color:red;">*</span> :</label>
											<input type="text" name="expense_code" id="expense_code" value="<?php echo htmlspecialchars($row['category_code']); ?>" class="form-control" readonly required />
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label">Expense Type <span style="color:red;">*</span> :</label>
											<div class="expense-type-wrap">
												<select name="expense_type_code" id="expense_type_code" class="form-control" required>
													<?php echo expense_type_label_select_html($conn, $row['expense_type_code'] ?? ''); ?>
												</select>
												<button type="button" class="btn btn-primary btn-add-inline" id="btn_add_expense_type_label" title="Add expense type">+ Add</button>
											</div>
										</div>
									</div>
								</div>
								<br />
								<div class="row">
									<div class="col-md-12 form-action">
										<button class="btn btn-primary" type="button" id="save_expense_type"><?php echo $is_edit ? 'Update' : 'Submit'; ?></button>
										<a class="btn btn-default-outline" href="expense_type_master.php">Cancel</a>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
				<div class="col-md-9 master_right">
					<div class="widget-container fluid-height clearfix">
						<div class="heading"><i class="fa fa-table"></i> List of Expense Types</div>
						<div class="widget-content padded clearfix new_dept">
							<table class="table" id="dataTable_exp_type">
								<thead>
									<tr>
										<th class="table-title" style="width:8%">S.No</th>
										<th class="table-title" style="width:18%">Expense Code</th>
										<th class="table-title" style="width:54%">Expense Type</th>
										<th class="table-title" style="width:10%">Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$query = 'SELECT * FROM expense_category ORDER BY expense_code_id ASC, category_id ASC';
									$result = mysqli_query($conn, $query);
									$i = 1;
									if ($result) {
										while ($list = mysqli_fetch_array($result)) {
											$type_name = expense_type_label_name($conn, $list['expense_type_code'] ?? '');
											if ($type_name === '') {
												$type_name = $list['category_name'];
											}
											?>
											<tr>
												<td class="text-center"><?php echo $i; ?></td>
												<td><?php echo htmlspecialchars($list['category_code']); ?></td>
												<td><?php echo htmlspecialchars($type_name); ?></td>
												<td class="actions center-content">
													<div class="action-buttons">
														<a title="Edit" class="table-actions" href="expense_type_master.php?key=<?php echo md5($list['category_id']); ?>"><i class="fa fa-pencil"></i></a>
														<?php if ((int) $list['status'] === 0) { ?>
															<a class="table-actions exp-type-active" data-status="0" title="InActive" id="<?php echo $list['category_id']; ?>"><i class="fa fa-check"></i></a>
														<?php } else { ?>
															<a class="table-actions exp-type-active is-inactive" data-status="1" title="Active" id="<?php echo $list['category_id']; ?>"><i class="fa fa-times"></i></a>
														<?php } ?>
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
			<?php require_once('include/footer.php'); ?>
		</div>
	</div>

	<div class="modal fade" id="modal_add_expense_type_label" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Add Expense Type</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label>Type Name <span style="color:red;">*</span></label>
						<input type="text" class="form-control" id="quick_expense_type_name" maxlength="150" autocomplete="off" placeholder="e.g. Detention Charges" />
					</div>
					<div id="expense_type_label_msg" class="text-danger" style="display:none;"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default-outline" data-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="quick_expense_type_save">Save &amp; Select</button>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			if ($.fn.dataTable && !$.fn.dataTable.isDataTable('#dataTable_exp_type')) {
				$('#dataTable_exp_type').dataTable({
					sPaginationType: 'full_numbers',
					aoColumnDefs: [{ bSortable: false, aTargets: [0, -1] }]
				});
			}

			function reloadExpenseTypeOptions(selected) {
				$.get('fetch_details.php', {
					cmd: 'get_expense_type_label_options',
					selected: selected || $('#expense_type_code').val()
				}, function(html) {
					$('#expense_type_code').html(html);
				});
			}

			$('#btn_add_expense_type_label').on('click', function() {
				$('#quick_expense_type_name').val('');
				$('#expense_type_label_msg').hide().text('');
				$('#modal_add_expense_type_label').modal('show');
			});

			$('#quick_expense_type_save').on('click', function() {
				var name = $.trim($('#quick_expense_type_name').val());
				if (!name) {
					$('#expense_type_label_msg').text('Enter expense type name.').show();
					return;
				}
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.post('save_details.php', {
					form_name: 'add_expense_type_label',
					type_name: name
				}, function(res) {
					$btn.prop('disabled', false);
					var data = res;
					if (typeof res === 'string') {
						try {
							data = JSON.parse(res);
						} catch (e) {
							data = {};
						}
					}
					if (data.ok) {
						reloadExpenseTypeOptions(data.type_code);
						$('#modal_add_expense_type_label').modal('hide');
					} else {
						$('#expense_type_label_msg').text(data.message || 'Could not add expense type.').show();
					}
				}, 'json').fail(function() {
					$btn.prop('disabled', false);
					$('#expense_type_label_msg').text('Network error.').show();
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
						window.location.href = 'expense_type_master.php';
					}
				});
			});

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
							window.location.href = 'expense_type_master.php?saved=1';
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

			if (window.location.search.indexOf('saved=1') !== -1 && typeof ewToast === 'function') {
				ewToast('Saved Successfully', 'success');
			}
		});
		$(window).load(function() {
			$('.loading-page').hide();
		});
	</script>
</body>

</html>
