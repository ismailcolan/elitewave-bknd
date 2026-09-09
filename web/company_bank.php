<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_schema.php');
require_once('include/company_bank_helpers.php');

ew_company_bank_ensure_schema($conn);
expense_require_admin();

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = array(
	'bank_account_id' => 0,
	'account_label' => '',
	'bank_name' => '',
	'ifsc' => '',
	'bank_branch' => '',
	'account_number' => '',
	'is_primary' => 0,
	'status' => 0,
);
if ($edit_id > 0) {
	$loaded = ew_company_bank_get($conn, $edit_id);
	if ($loaded) {
		$row = $loaded;
	}
}
$list_rows = ew_company_bank_fetch_list($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.cb-section-title {
			font-size: 12px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .05em;
			color: #334155;
			margin: 0 0 16px;
			padding-bottom: 8px;
			border-bottom: 1px solid #e2e8f0;
		}
	</style>
</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php require_once('include/header.php');
			require_once('include/menu.php'); ?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-offset-1 col-md-10">
					<div class="widget-container fluid-height clearfix">
						<div class="ew-page-header">
							<div class="ew-page-header__title"><i class="fa fa-university"></i> Company Bank Accounts</div>
						</div>
						<div class="widget-content padded clearfix">
							<form class="form-horizontal" id="company_bank_form" method="post">
								<input type="hidden" name="form_name" value="<?php echo $edit_id > 0 ? 'edit_company_bank' : 'add_company_bank'; ?>">
								<input type="hidden" name="bank_account_id" value="<?php echo (int) $row['bank_account_id']; ?>">
								<div class="cb-section-title">Bank Account Details</div>
								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Account Label :</label>
											<input type="text" name="account_label" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($row['account_label']); ?>" placeholder="e.g. EliteWave360 Logistics - Axis Bank" autocomplete="off">
										</div>
										<div class="form-group">
											<label class="control-label">Bank Name <span style="color:red;">*</span> :</label>
											<input type="text" name="bank_name" class="form-control" maxlength="150" required value="<?php echo htmlspecialchars($row['bank_name']); ?>" autocomplete="off">
										</div>
										<div class="form-group">
											<label class="control-label">Account Number :</label>
											<input type="text" name="account_number" class="form-control" maxlength="30" value="<?php echo htmlspecialchars($row['account_number']); ?>" autocomplete="off">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">IFSC Code <span style="color:red;">*</span> :</label>
											<input type="text" name="ifsc" class="form-control" maxlength="11" required style="text-transform:uppercase" value="<?php echo htmlspecialchars($row['ifsc']); ?>" autocomplete="off">
										</div>
										<div class="form-group">
											<label class="control-label">Branch <span style="color:red;">*</span> :</label>
											<input type="text" name="bank_branch" class="form-control" maxlength="150" required value="<?php echo htmlspecialchars($row['bank_branch']); ?>" autocomplete="off">
										</div>
										<div class="form-group">
											<label class="control-label">Primary Account :</label>
											<select name="is_primary" class="form-control">
												<option value="0" <?php echo ((int) ($row['is_primary'] ?? 0) !== 1) ? 'selected' : ''; ?>>No</option>
												<option value="1" <?php echo ((int) ($row['is_primary'] ?? 0) === 1) ? 'selected' : ''; ?>>Yes</option>
											</select>
										</div>
										<div class="form-group">
											<label class="control-label">Status :</label>
											<select name="status" class="form-control">
												<option value="0" <?php echo ((int) ($row['status'] ?? 0) === 0) ? 'selected' : ''; ?>>Active</option>
												<option value="1" <?php echo ((int) ($row['status'] ?? 0) === 1) ? 'selected' : ''; ?>>Inactive</option>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group" style="text-align:right;">
									<?php if ($edit_id > 0) { ?>
										<a href="company_bank.php" class="btn btn-default">Cancel</a>
									<?php } ?>
									<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Bank Account</button>
								</div>
							</form>

							<div class="cb-section-title" style="margin-top:24px;">Saved Accounts</div>
							<?php if (empty($list_rows)) { ?>
								<p class="text-muted">No company bank accounts added yet.</p>
							<?php } else { ?>
								<div class="table-scroll-wrapper">
									<table class="table table-bordered" id="cb_list_table">
										<thead>
											<tr>
												<th>S.No</th>
												<th>Label</th>
												<th>Bank</th>
												<th>Account No</th>
												<th>IFSC</th>
												<th>Branch</th>
												<th>Primary</th>
												<th>Status</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody>
											<?php $i = 1; foreach ($list_rows as $item) { ?>
												<tr>
													<td><?php echo $i++; ?></td>
													<td><?php echo htmlspecialchars($item['account_label'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($item['bank_name']); ?></td>
													<td><?php echo htmlspecialchars($item['account_number'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars(strtoupper($item['ifsc'])); ?></td>
													<td><?php echo htmlspecialchars($item['bank_branch']); ?></td>
													<td><?php echo ((int) ($item['is_primary'] ?? 0) === 1) ? 'Yes' : 'No'; ?></td>
													<td><?php echo ((int) ($item['status'] ?? 0) === 0) ? 'Active' : 'Inactive'; ?></td>
													<td class="text-center">
														<span class="act-wrap">
															<a href="company_bank.php?id=<?php echo (int) $item['bank_account_id']; ?>" class="act-link act-edit" title="Edit"><i class="fa fa-pencil"></i></a>
															<button type="button" class="act-link act-delete btn-delete-bank" data-id="<?php echo (int) $item['bank_account_id']; ?>" title="Delete"><i class="fa fa-trash-o"></i></button>
														</span>
													</td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
	<script>
		$(function() {
			$('#company_bank_form').on('submit', function(e) {
				e.preventDefault();
				$.post('save_details.php', $(this).serialize(), function(resp) {
					if (resp === 1 || resp === '1') {
						window.location.href = 'company_bank.php';
						return;
					}
					alert(typeof resp === 'string' && resp ? resp : 'Could not save bank account.');
				});
			});
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
