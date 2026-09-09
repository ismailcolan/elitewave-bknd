<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_general_helpers.php');

expense_general_ensure_schema($conn);
expense_require_admin();

$list_rows = expense_general_fetch_list($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
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
						<div class="heading"><i class="fa fa-table"></i> General Expense List
							<span class="align-right"><i class="fa fa-plus"></i><a href="expense_general.php">ADD EXPENSE</a></span>
						</div>
						<div class="widget-content padded clearfix new_dept">
							<?php if (empty($list_rows)) { ?>
								<div class="egen-empty">
									No general expenses saved yet.
									<br><br>
									<a href="expense_general.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add General Expense</a>
								</div>
							<?php } else { ?>
								<div class="table-scroll-wrapper">
									<table class="table" id="egen_list_table">
										<thead>
											<tr>
												<th>S.No</th>
												<th>Expense No</th>
												<th>Date</th>
												<th>Vendor</th>
												<th>Expense Type</th>
												<th>Amount</th>
												<th>GST</th>
												<th>TDS</th>
												<th>Net Payable</th>
												<th>Payment</th>
												<th>Description</th>
												<th>Last Updated</th>
												<th class="col-actions">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$i = 1;
											foreach ($list_rows as $row) {
												$edit_url = 'expense_general.php?id=' . (int) $row['general_expense_id'];
												?>
												<tr>
													<td><?php echo $i++; ?></td>
													<td><strong><?php echo htmlspecialchars($row['expense_no']); ?></strong></td>
													<td><?php echo htmlspecialchars($row['expense_date']); ?></td>
													<td><?php echo htmlspecialchars($row['vendor_label'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['category_label'] ?: '—'); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['expense_amount']); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['gst_amount']); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['tds_amount']); ?></td>
													<td class="num"><strong><?php echo htmlspecialchars($row['net_payable']); ?></strong></td>
													<td><?php echo htmlspecialchars($row['payment_mode'] ?: '—'); ?></td>
													<td><span class="egen-desc" title="<?php echo htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['description'] ?: '—'); ?></span></td>
													<td><?php echo htmlspecialchars($row['updated_at']); ?></td>
													<td class="col-actions">
														<span class="act-wrap">
															<a class="act-link act-edit" href="<?php echo htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8'); ?>" title="Edit"><i class="fa fa-pencil"></i></a>
															<button type="button" class="act-link act-delete btn-delete-general-expense" title="Delete"
																data-id="<?php echo (int) $row['general_expense_id']; ?>"
																data-no="<?php echo htmlspecialchars($row['expense_no'], ENT_QUOTES, 'UTF-8'); ?>">
																<i class="fa fa-trash-o"></i>
															</button>
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
	<?php if (!empty($list_rows)) { ?>
	<script>
		$(function() {
			if ($.fn.DataTable) {
				$('#egen_list_table').DataTable({
					pageLength: 25,
					order: [[0, 'asc']],
					aoColumnDefs: [{ bSortable: false, aTargets: [-1], sClass: 'col-actions' }]
				});
			}
			$(document).on('click', '.btn-delete-general-expense', function() {
				var id = $(this).data('id');
				var no = $(this).data('no') || '';
				if (!id || !confirm('Delete general expense ' + no + '?')) return;
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.post('expense_general_data.php', { cmd: 'delete', general_expense_id: id }, function(r) {
					if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Delete failed.');
						$btn.prop('disabled', false);
						return;
					}
					location.reload();
				}, 'json').fail(function() {
					alert('Delete request failed.');
					$btn.prop('disabled', false);
				});
			});
		});
	</script>
	<?php } ?>
</body>

</html>
