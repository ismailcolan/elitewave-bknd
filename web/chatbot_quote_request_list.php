<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/chatbot_quote_request_helpers.php');

$rows = chatbot_quote_request_list($conn, array(), 2000);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2--chatbot-qr .ew-page-subtitle {
			margin: 4px 0 0;
			font-size: 13px;
			color: var(--ew-text-muted, #64748b);
			font-weight: 500;
		}
		#chatbot_qr_table {
			table-layout: fixed;
			width: 100% !important;
		}
		#chatbot_qr_table th.col-sno,
		#chatbot_qr_table td.col-sno {
			width: 56px;
		}
		#chatbot_qr_table th.col-actions,
		#chatbot_qr_table td.col-actions {
			width: 112px !important;
			max-width: 112px !important;
		}
		#chatbot_qr_table td.col-actions .action-buttons {
			justify-content: center;
		}
		#chatbot_qr_table .ew-qr-route {
			word-break: break-word;
			line-height: 1.35;
		}
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
				<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--chatbot-qr">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">Quote Requests</h1>
							<p class="ew-page-subtitle">Shipment quote submissions from the ELWA360 website chatbot.</p>
						</div>
					</div>

					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Chatbot quote requests</h2>
							<div class="ew-toolbar-right">
								<div class="ew-list-toolbar__tools"></div>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
							<table class="table table-bordered table-striped" id="chatbot_qr_table">
								<thead>
									<tr>
										<th class="col-sno">S.No</th>
										<th>Request ID</th>
										<th>Company</th>
										<th>Email</th>
										<th>Contact</th>
										<th>Origin</th>
										<th>Destination</th>
										<th>Type</th>
										<th>Mode</th>
										<th class="num">Weight (kg)</th>
										<th>Submitted</th>
										<th>Status</th>
										<th class="col-actions sorting_disabled">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$i = 1;
									if (count($rows) === 0) {
										echo '<tr><td colspan="13" class="text-center" style="padding:24px;color:#64748b;">No quote requests found.</td></tr>';
									}
									foreach ($rows as $r) {
										$company = chatbot_quote_request_display($r['company_name'] ?? '');
										$typeLabel = ($r['transhipment_type'] ?? '') === 'multiple_location' ? 'Multiple' : 'Single';
										$weight = htmlspecialchars(number_format((float) ($r['weight'] ?? 0), 3, '.', ''));
										$rid = (int) $r['request_id'];
										$canForward = chatbot_quote_request_can_forward($conn, $r);
										?>
										<tr>
											<td class="col-sno text-center"><?php echo $i++; ?></td>
											<td><strong><?php echo htmlspecialchars($r['request_no']); ?></strong></td>
											<td><?php echo $company; ?></td>
											<td><?php echo chatbot_quote_request_display($r['email'] ?? ''); ?></td>
											<td><?php echo chatbot_quote_request_display($r['contact_mobile'] ?? ''); ?></td>
											<td class="ew-qr-route"><?php echo chatbot_quote_request_display($r['origin_text'] ?? ''); ?></td>
											<td class="ew-qr-route"><?php echo chatbot_quote_request_display($r['destination_text'] ?? ''); ?></td>
											<td><?php echo htmlspecialchars($typeLabel); ?></td>
											<td><?php echo chatbot_quote_request_display($r['mode_label'] ?? ''); ?></td>
											<td class="num"><?php echo $weight; ?></td>
											<td><?php echo chatbot_quote_request_display($r['created_at'] ?? ''); ?></td>
											<td><?php echo chatbot_quote_request_status_badge($r['status'] ?? 'pending'); ?></td>
											<td class="col-actions actions center-content">
												<div class="action-buttons">
													<a class="table-actions" title="View" href="chatbot_quote_request_view.php?id=<?php echo $rid; ?>"><i class="fa fa-eye"></i></a>
													<?php if ($canForward) { ?>
														<a class="table-actions" title="Forward to quotation" href="chatbot_quote_request_forward.php?id=<?php echo $rid; ?>" onclick="return confirm('Forward this request to quotation creation?');"><i class="fa fa-share"></i></a>
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
$(function () {
	if (!$.fn.DataTable || !$('#chatbot_qr_table').length) {
		return;
	}
	var emptyOnly = $('#chatbot_qr_table tbody tr').length === 1
		&& $('#chatbot_qr_table tbody tr td[colspan]').length > 0;
	if (emptyOnly) {
		return;
	}
	$('#chatbot_qr_table').DataTable({
		order: [[10, 'desc']],
		pageLength: 25,
		columnDefs: [
			{ orderable: false, targets: [0, 12] }
		]
	});
	if (typeof window.applyEwListLayout === 'function') {
		window.setTimeout(window.applyEwListLayout, 100);
	}
});
</script>
</body>
</html>