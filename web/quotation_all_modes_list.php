<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/quotation_functions.php');

ensure_rate_quotation_tables($conn);
$tab = quotation_list_tab();
$rows = quotation_list_rows($conn, $tab, 'multi_mode');
$quote_type_labels = quotation_multi_mode_quote_types();
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-status-chip { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 999px; background: #e2e8f0; color: #334155; }
		.ew-status-chip.pending_approval, .ew-status-chip.sent, .ew-status-chip.approved { background: #fef3c7; color: #92400e; }
		.ew-status-chip.customer_confirmed, .ew-status-chip.converted { background: #dcfce7; color: #166534; }
		.ew-status-chip.rejected { background: #fee2e2; color: #991b1b; }
		.ew-create-split { display: inline-flex; gap: 6px; flex-wrap: wrap; }
		<?php echo quotation_list_tab_css(); ?>
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
						<?php echo quotation_list_tabs_html('quotation_all_modes_list.php', $tab); ?>
						<div class="ew-card-toolbar">
							<h2>Consignee / Consignor — mode-wise list</h2>
							<div class="ew-toolbar-right">
								<div class="ew-create-split">
									<a href="quotation_all_modes.php?create=1&amp;type=consignee_consignor_all_modes" class="ew-btn-v2 ew-btn-v2-primary">Create (Consignee / Consignor) <i class="fa fa-plus"></i></a>
									<a href="quotation_all_modes.php?create=1&amp;type=consignor_consignee_all_modes" class="ew-btn-v2 ew-btn-v2-outline">Create (Consignor / Consignee) <i class="fa fa-plus"></i></a>
								</div>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
							<table class="table table-bordered table-striped" id="quotation_all_modes_list_table">
								<thead>
									<tr>
										<th>S.No</th>
										<th>Quote No</th>
										<th>Date</th>
										<th>Quote type</th>
										<th>Customer</th>
										<th>Consignee / delivery</th>
										<th>Status</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$i = 1;
									foreach ($rows as $r) {
										$route = trim((string) ($r['destination_name'] ?? ''));
										if ($route === '') {
											$route = '—';
										}
										$st = $r['status'];
										$qt = $quote_type_labels[$r['quote_type'] ?? ''] ?? ($r['quote_type'] ?? '—');
										?>
										<tr>
											<td class="text-center"><?php echo $i++; ?></td>
											<td><?php echo htmlspecialchars($r['quote_no']); ?></td>
											<td><?php echo htmlspecialchars($r['quote_date']); ?></td>
											<td><?php echo htmlspecialchars($qt); ?></td>
											<td><?php echo htmlspecialchars($r['party_display'] ?? ''); ?></td>
											<td><?php echo htmlspecialchars($route); ?></td>
											<td><?php echo quotation_list_status_chip_html($st, $tab); ?></td>
											<td class="actions center-content">
												<div class="action-buttons">
													<a title="Edit / View" class="table-actions" href="quotation_all_modes.php?id=<?php echo (int) $r['quotation_id']; ?>"><i class="fa fa-pencil"></i></a>
													<?php if (in_array($st, array('approved', 'sent', 'customer_confirmed', 'converted'), true)) { ?>
														<a title="PDF" class="table-actions act-dl" target="_blank" href="quotation_pdf.php?id=<?php echo (int) $r['quotation_id']; ?>"><i class="fa fa-file"></i></a>
													<?php } ?>
													<?php echo quotation_list_approve_action_html($r); ?>
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
<?php echo quotation_customer_approve_modal_html(); ?>
<?php echo quotation_customer_approve_script('quotation_all_modes_list.php?tab=approved'); ?>
<script>
$(function() {
	$('#quotation_all_modes_list_table').DataTable({ order: [[1, 'desc']] });
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
