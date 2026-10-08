<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/quotation_functions.php');

ensure_rate_quotation_tables($conn);
$status_opts = quotation_status_options();
$filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
if ($filter !== 'all' && !isset($status_opts[$filter])) {
	$filter = 'all';
}
$rows = quotation_list_rows($conn, $filter, 'standard');
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-erp-list .ew-list-toolbar__tools .ew-quotation-status-filter {
			width: 200px !important;
			height: 32px !important;
			min-height: 32px !important;
			padding: 0 10px !important;
			margin: 0 !important;
			border-radius: 6px !important;
			box-sizing: border-box !important;
			font-size: 13px !important;
			border: 1px solid var(--ew-border-light, #D8DDE5);
			color: #334155;
			background: #fff;
		}
		.ew-status-chip { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 999px; background: #e2e8f0; color: #334155; }
		.ew-status-chip.pending_approval { background: #fef3c7; color: #92400e; }
		.ew-status-chip.approved, .ew-status-chip.customer_confirmed { background: #dcfce7; color: #166534; }
		.ew-status-chip.rejected { background: #fee2e2; color: #991b1b; }
	</style>
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
	<div class="navbar navbar-fixed-top scroll-hide">
		<?php require_once('include/header.php'); require_once('include/menu.php'); ?>
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="row">
			<div class="col-md-12">
				<div class="ew-page-v2 ew-page-v2--wide-table">
					<div class="ew-page-head">
						<div class="ew-page-head-left"><h1 class="ew-page-title">Quotation/Proforma Invoice</h1></div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Door-to-Door Quotation List</h2>
							<div class="ew-toolbar-right">
								<div class="ew-list-toolbar__tools">
									<select id="quotation_status_filter" class="form-control ew-quotation-status-filter" aria-label="Filter by status">
										<option value="all"<?php echo $filter === 'all' ? ' selected' : ''; ?>>All statuses</option>
										<?php foreach ($status_opts as $code => $label) { ?>
											<option value="<?php echo htmlspecialchars($code); ?>"<?php echo $filter === $code ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
										<?php } ?>
									</select>
								</div>
								<a href="quotation.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
							<table class="table table-bordered table-striped" id="quotation_list_table">
								<thead>
									<tr>
										<th>S.No</th>
										<th>Quote No</th>
										<th>Date</th>
										<th>Customer</th>
										<th>Route</th>
										<th class="num">Total (₹)</th>
										<th>Status</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$i = 1;
									foreach ($rows as $r) {
										$route = trim($r['origin_text'] . ' → ' . $r['unloading_at']);
										$st = $r['status'];
										?>
										<tr>
											<td class="text-center"><?php echo $i++; ?></td>
											<td><?php echo htmlspecialchars($r['quote_no']); ?></td>
											<td><?php echo htmlspecialchars($r['quote_date']); ?></td>
											<td><?php echo htmlspecialchars($r['party_display'] ?? ''); ?></td>
											<td><?php echo htmlspecialchars($route); ?></td>
											<td class="num"><?php echo htmlspecialchars(quotation_format_money_display($r['total_amount'])); ?></td>
											<td><span class="ew-status-chip <?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars(quotation_status_label($st)); ?></span></td>
											<td class="actions center-content">
												<div class="action-buttons">
													<a title="Edit / View" class="table-actions" href="quotation.php?id=<?php echo (int) $r['quotation_id']; ?>"><i class="fa fa-pencil"></i></a>
													<?php if (in_array($st, array('approved', 'sent', 'customer_confirmed', 'converted'), true)) { ?>
														<a title="PDF" class="table-actions act-dl" target="_blank" href="quotation_pdf.php?id=<?php echo (int) $r['quotation_id']; ?>"><i class="fa fa-file"></i></a>
													<?php } ?>
													<?php if (in_array($st, array('draft', 'rejected', 'cancelled'), true)) { ?>
														<a title="Delete" href="javascript:void(0);" class="table-actions btn-delete-quotation" data-id="<?php echo (int) $r['quotation_id']; ?>"><i class="fa fa-trash-o"></i></a>
													<?php } ?>
												</div>
											</td>
										</tr>
									<?php } ?>
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
$(function() {
	$('#quotation_status_filter').on('change', function() {
		var v = $(this).val();
		if (!v || v === 'all') {
			window.location.href = 'quotation_list.php';
			return;
		}
		window.location.href = 'quotation_list.php?status=' + encodeURIComponent(v);
	});
	$('#quotation_list_table').DataTable({ order: [[1, 'desc']] });
	$(document).on('click', '.btn-delete-quotation', function() {
		var id = $(this).data('id');
		if (!id) return;
		window.ewConfirmDelete({
			id: String(id),
			title: 'Delete quotation',
			message: 'Delete this draft quotation?',
			onConfirm: function(delId) {
				$.post('save_details.php', { form_name: 'delete_rate_quotation', quotation_id: delId }, function(data) {
					if ($.trim(data) === '1') {
						if (typeof ewToast === 'function') ewToast('Deleted.', 'success');
						setTimeout(function() { location.reload(); }, 500);
					} else {
						alert($.trim(data) || 'Delete failed.');
					}
				});
			}
		});
	});
});
</script>
</body>
</html>
