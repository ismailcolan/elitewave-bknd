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
$is_edit = false;
if ($edit_id > 0) {
	$loaded = ew_company_bank_get($conn, $edit_id);
	if ($loaded) {
		$row = $loaded;
		$is_edit = true;
	}
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
			<?php require_once('include/header.php');
			require_once('include/menu.php'); ?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<a href="company_bank.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php echo $is_edit ? 'Edit Bank Account' : 'Add Bank Account'; ?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="company_bank.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							</div>
						</div>
						<div class="ew-card">
							<h2 class="ew-card-section-title">Bank Account Details</h2>
							<div class="ew-form-body">
								<form id="company_bank_form" method="post">
									<input type="hidden" name="form_name" value="<?php echo $is_edit ? 'edit_company_bank' : 'add_company_bank'; ?>">
									<input type="hidden" name="bank_account_id" value="<?php echo (int) $row['bank_account_id']; ?>">
									<div class="ew-form-grid">
										<div class="ew-field">
											<label class="control-label">Account Label :</label>
											<input type="text" name="account_label" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($row['account_label']); ?>" placeholder="e.g. EliteWave360 Logistics - Axis Bank" autocomplete="off">
										</div>
										<div class="ew-field">
											<label class="control-label">Bank Name <span style="color:red;">*</span> :</label>
											<input type="text" name="bank_name" class="form-control" maxlength="150" required value="<?php echo htmlspecialchars($row['bank_name']); ?>" autocomplete="off">
										</div>
										<div class="ew-field">
											<label class="control-label">Account Number :</label>
											<input type="text" name="account_number" class="form-control" maxlength="30" value="<?php echo htmlspecialchars($row['account_number']); ?>" autocomplete="off">
										</div>
										<div class="ew-field">
											<label class="control-label">IFSC Code <span style="color:red;">*</span> :</label>
											<input type="text" name="ifsc" class="form-control" maxlength="11" required style="text-transform:uppercase" value="<?php echo htmlspecialchars($row['ifsc']); ?>" autocomplete="off">
										</div>
										<div class="ew-field">
											<label class="control-label">Branch <span style="color:red;">*</span> :</label>
											<input type="text" name="bank_branch" class="form-control" maxlength="150" required value="<?php echo htmlspecialchars($row['bank_branch']); ?>" autocomplete="off">
										</div>
										<div class="ew-field">
											<label class="control-label">Primary Account :</label>
											<select name="is_primary" class="form-control">
												<option value="0" <?php echo ((int) ($row['is_primary'] ?? 0) !== 1) ? 'selected' : ''; ?>>No</option>
												<option value="1" <?php echo ((int) ($row['is_primary'] ?? 0) === 1) ? 'selected' : ''; ?>>Yes</option>
											</select>
										</div>
										<div class="ew-field">
											<label class="control-label">Status :</label>
											<select name="status" class="form-control">
												<option value="0" <?php echo ((int) ($row['status'] ?? 0) === 0) ? 'selected' : ''; ?>>Active</option>
												<option value="1" <?php echo ((int) ($row['status'] ?? 0) === 1) ? 'selected' : ''; ?>>Inactive</option>
											</select>
										</div>
									</div>
								</form>
							</div>
							<div class="ew-form-footer">
								<a href="company_bank.php" class="ew-btn-v2 ew-btn-v2-outline">Cancel</a>
								<button type="submit" form="company_bank_form" class="ew-btn-v2 ew-btn-v2-primary"><i class="fa fa-save"></i> Save Bank Account</button>
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
		});
	</script>
</body>

</html>
