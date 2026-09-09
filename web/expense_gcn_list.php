<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_gcn_helpers.php');

expense_gcn_ensure_schema($conn);
expense_require_admin();

$list_rows = expense_gcn_fetch_list($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.egcn-gcn-cell .gcn-no-text {
			font-weight: 700;
			color: #0A1E3D;
		}

		.egcn-gcn-tags {
			display: flex;
			flex-wrap: wrap;
			gap: 6px;
			margin-top: 6px;
			justify-content: center;
		}

		.egcn-gcn-tag {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 6px;
			background: #f1f5f9;
			border: 1px solid #e2e8f0;
			color: #334155;
			font-size: 12px;
			font-weight: 600;
			line-height: 1.4;
		}

		.egcn-route-multi {
			font-size: 12px;
			color: #475569;
		}

		.egcn-route-multi .route-count {
			font-weight: 700;
			color: #0A1E3D;
		}

		#egcn_list_table td.col-gcn {
			min-width: 160px;
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
						<div class="heading"><i class="fa fa-table"></i> GCN Expense List
							<span class="align-right"><i class="fa fa-plus"></i><a href="expense_gcn.php">ADD GCN EXPENSE</a></span>
						</div>
						<div class="widget-content padded clearfix new_dept">
							<?php if (empty($list_rows)) { ?>
								<div class="egcn-empty">
									No GCN expenses saved yet.
									<br><br>
									<a href="expense_gcn.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Expense against GCN</a>
								</div>
							<?php } else { ?>
								<div class="table-scroll-wrapper">
									<table class="table" id="egcn_list_table">
										<thead>
											<tr>
												<th>S.No</th>
												<th>Type</th>
												<th class="col-gcn">GCN No</th>
												<th>GCN Date</th>
												<th>Route</th>
												<th>Revenue</th>
												<th>Expenses</th>
												<th>Profit</th>
												<th>Last Updated</th>
												<th class="col-actions">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$i = 1;
											foreach ($list_rows as $row) {
												$profit_class = ((float) $row['profit_raw'] >= 0) ? 'profit-positive' : 'profit-negative';
												$is_group = (($row['expense_mode'] ?? 'single') === 'group');
												if ($is_group) {
													$edit_url = 'expense_gcn.php?group_id=' . (int) ($row['group_id'] ?? $row['gcn_expense_id']);
													$delete_label = $row['group_label'] ?? ('Group (' . (int) ($row['gcn_count'] ?? 0) . ' GCNs)');
												} else {
													$edit_url = 'expense_gcn.php?gcn_key=' . urlencode($row['gcn_key']);
													$delete_label = $row['grn_no'] ?? '';
												}
												?>
												<tr>
													<td><?php echo $i++; ?></td>
													<td>
														<span class="egcn-mode-badge <?php echo $is_group ? 'mode-group' : 'mode-single'; ?>">
															<?php echo $is_group ? 'Group' : 'Single'; ?>
														</span>
													</td>
													<td class="col-gcn egcn-gcn-cell">
														<?php if ($is_group) { ?>
															<div class="egcn-gcn-tags">
																<?php foreach (($row['gcn_list'] ?? array()) as $gcn_no) {
																	if ($gcn_no === '') continue; ?>
																	<span class="egcn-gcn-tag"><?php echo htmlspecialchars($gcn_no); ?></span>
																<?php } ?>
															</div>
														<?php } else { ?>
															<span class="gcn-no-text"><?php echo htmlspecialchars($row['grn_no']); ?></span>
														<?php } ?>
													</td>
													<td><?php echo htmlspecialchars($row['grn_date']); ?></td>
													<td>
														<?php
														if ($is_group && !empty($row['route_list']) && count($row['route_list']) > 1) {
															$routes_title = htmlspecialchars(implode(' | ', $row['route_list']), ENT_QUOTES, 'UTF-8');
															echo '<span class="egcn-route-multi" title="' . $routes_title . '"><span class="route-count">' . count($row['route_list']) . ' routes</span></span>';
														} else {
															echo htmlspecialchars($row['route_label'] ?: '—');
														}
														?>
													</td>
													<td class="num"><?php echo htmlspecialchars($row['revenue_without_gst']); ?></td>
													<td class="num"><?php echo htmlspecialchars($row['expenses_without_gst']); ?></td>
													<td class="num <?php echo $profit_class; ?>"><?php echo htmlspecialchars($row['profit_amount']); ?></td>
													<td><?php echo htmlspecialchars($row['updated_at']); ?></td>
													<td class="col-actions">
														<span class="act-wrap">
															<a class="act-link act-edit" href="<?php echo htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8'); ?>" title="Edit">
																<i class="fa fa-pencil"></i>
															</a>
															<button type="button" class="act-link act-delete btn-delete-gcn-expense" title="Delete"
																data-id="<?php echo (int) $row['gcn_expense_id']; ?>"
																data-gcn="<?php echo htmlspecialchars($delete_label, ENT_QUOTES, 'UTF-8'); ?>">
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
				$('#egcn_list_table').DataTable({
					pageLength: 25,
					order: [[0, 'asc']],
					aoColumnDefs: [
						{ bSortable: false, aTargets: [-1], sClass: 'col-actions' }
					]
				});
			}

			$(document).on('click', '.btn-delete-gcn-expense', function() {
				var id = $(this).data('id');
				var gcn = $(this).data('gcn') || '';
				if (!id) return;
				if (!confirm('Delete expense record for ' + gcn + '?')) {
					return;
				}
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.post('expense_gcn_data.php', { cmd: 'delete', gcn_expense_id: id }, function(r) {
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
