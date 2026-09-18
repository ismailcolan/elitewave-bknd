<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_schema.php');
require_once('include/company_bank_helpers.php');

ew_company_bank_ensure_schema($conn);
expense_require_admin();

$list_rows = ew_company_bank_fetch_list($conn);
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
			<?php require_once('include/header.php');
			require_once('include/menu.php'); ?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Company Bank Accounts</h1>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-card-toolbar">
								<h2>All Bank Accounts</h2>
								<div class="ew-toolbar-right">
									<a href="company_bank_form.php" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix">
								<?php if (empty($list_rows)) { ?>
									<p class="text-muted" style="padding:24px;text-align:center;">No company bank accounts added yet.</p>
								<?php } else { ?>
									<table class="table table-bordered table-striped" id="cb_list_table">
										<thead>
											<tr>
												<th class="table-title">S.No</th>
												<th class="table-title">Label</th>
												<th class="table-title">Bank</th>
												<th class="table-title">Account No</th>
												<th class="table-title">IFSC</th>
												<th class="table-title">Branch</th>
												<th class="table-title">Primary</th>
												<th class="table-title">Status</th>
												<th class="table-title">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php $i = 1; foreach ($list_rows as $item) { ?>
												<tr>
													<td class="text-center"><?php echo $i++; ?></td>
													<td><?php echo htmlspecialchars($item['account_label'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($item['bank_name']); ?></td>
													<td><?php echo htmlspecialchars($item['account_number'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars(strtoupper($item['ifsc'])); ?></td>
													<td><?php echo htmlspecialchars($item['bank_branch']); ?></td>
													<td><?php echo ((int) ($item['is_primary'] ?? 0) === 1) ? 'Yes' : 'No'; ?></td>
													<td><?php echo ((int) ($item['status'] ?? 0) === 0) ? 'Active' : 'Inactive'; ?></td>
													<td class="actions center-content">
														<div class="action-buttons">
															<a href="company_bank_form.php?id=<?php echo (int) $item['bank_account_id']; ?>" class="table-actions btn-edit" title="Edit"><i class="fa fa-pencil"></i></a>
															<a href="javascript:void(0);" class="table-actions btn-trash btn-delete-bank" data-id="<?php echo (int) $item['bank_account_id']; ?>" title="Delete"><i class="fa fa-trash-o"></i></a>
														</div>
													</td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
	<script>
		$(function() {
			$(document).on('click', '.btn-delete-bank', function() {
				var id = $(this).data('id');
				if (!id || !confirm('Delete this bank account?')) return;
				$.post('save_details.php', { form_name: 'delete_company_bank', bank_account_id: id }, function(resp) {
					if (resp === 1 || resp === '1') location.reload();
					else alert(typeof resp === 'string' && resp ? resp : 'Delete failed.');
				});
			});
		});
	</script>
</body>

</html>
