<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/trip_summary_helpers.php');

trip_summary_require_access();
trip_summary_ensure_schema($conn);
$list_rows = trip_summary_fetch_list($conn);
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		#dataTable1 .gcn-count { font-weight: 800; color: #1d4ed8; text-align: center; }
		.dataTable th.sorting:after,
		.dataTable th.sorting_desc:after { top: 17px; right: 3px; }
		.dataTable th.sorting:before,
		.dataTable th.sorting_asc:after { top: 10px; right: 3px; }
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
				<div class="col-md-12">
					<div class="ew-page-v2 ew-page-v2--wide-table">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Trip Summary</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>Trip Summary List</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<a href="trip_summary.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th>S.No</th>
											<th>Sheet No</th>
											<th>Date</th>
											<th>Mode</th>
											<th>Source</th>
											<th>Route</th>
											<th>GCNs</th>
											<th>Pkgs</th>
											<th>Loaded</th>
											<th>Status</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php if (!empty($list_rows)): ?>
											<?php $sn = 0; foreach ($list_rows as $row): $sn++; ?>
												<tr>
													<td class="text-center"><?php echo $sn; ?></td>
													<td><strong><?php echo htmlspecialchars($row['sheet_no']); ?></strong></td>
													<td><?php echo htmlspecialchars($row['sheet_date']); ?></td>
													<td><?php echo htmlspecialchars($row['mode_label']); ?></td>
													<td><?php echo htmlspecialchars($row['source_display'] ?: '—'); ?></td>
													<td><?php echo htmlspecialchars($row['route_label']); ?></td>
													<td class="gcn-count"><?php echo (int) $row['total_gcn']; ?></td>
													<td class="text-center"><?php echo (int) $row['total_packages']; ?></td>
													<td class="text-center"><?php echo (int) $row['total_loaded']; ?></td>
													<td>
														<?php if (($row['status'] ?? '') === 'Cancelled'): ?>
															<span class="ew-badge ew-badge--cancelled">Cancelled</span>
														<?php else: ?>
															<span class="ew-badge ew-badge--active"><?php echo htmlspecialchars($row['status']); ?></span>
														<?php endif; ?>
													</td>
													<td class="text-center">
														<div class="act-wrap">
															<?php if (($row['status'] ?? '') !== 'Cancelled'): ?>
																<a class="act-link act-edit" href="trip_summary.php?id=<?php echo (int) $row['trip_summary_id']; ?>" title="Edit"><i class="fa fa-pencil"></i></a>
															<?php endif; ?>
															<a class="act-link act-print" href="trip_summary_print.php?id=<?php echo (int) $row['trip_summary_id']; ?>" target="_blank" title="Print"><i class="fa fa-print"></i></a>
															<?php if (($row['status'] ?? '') !== 'Cancelled'): ?>
																<button type="button" class="act-link act-cancel btn-cancel-trip" data-id="<?php echo (int) $row['trip_summary_id']; ?>" title="Cancel"><i class="fa fa-ban"></i></button>
															<?php endif; ?>
														</div>
													</td>
												</tr>
											<?php endforeach; ?>
										<?php endif; ?>
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
	<script>
		$(document).ready(function() {
			window.setTimeout(function() {
				if (window.applyEwListLayout) {
					window.applyEwListLayout();
				}
			}, 250);
		});
		$(document).on('click', '.btn-cancel-trip', function() {
			var id = $(this).data('id');
			if (!id || !confirm('Cancel this trip summary sheet?')) return;
			$.post('trip_summary_data.php', { cmd: 'cancel', trip_summary_id: id }, function(r) {
				if (!r || r.status !== 0) { alert(r && r.message ? r.message : 'Cancel failed.'); return; }
				location.reload();
			}, 'json');
		});
	</script>
</body>

</html>
