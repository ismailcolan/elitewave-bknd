<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/chatbot_qa_helpers.php');

$rows = chatbot_qa_list_rows($conn, 2000);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-chat-chip { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 999px; background: #e2e8f0; color: #334155; }
		.ew-chat-chip--auto { background: #dbeafe; color: #1e40af; }
		.ew-chat-chip--support { background: #fef3c7; color: #92400e; }
		.ew-chat-chip--emailed { background: #dcfce7; color: #166534; }
		.ew-chat-chip--failed { background: #fee2e2; color: #991b1b; }
		.ew-chat-qa-text { max-width: 360px; white-space: normal; word-break: break-word; }
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
						<div class="ew-page-head-left"><h1 class="ew-page-title">ELWA360 Chat — Questions &amp; Answers</h1></div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Website chatbot log</h2>
							<p class="text-muted" style="margin:8px 0 0;">Visitor questions, bot replies, and items sent to support.</p>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
							<table class="table table-bordered table-striped" id="chatbot_qa_table">
								<thead>
									<tr>
										<th>S.No</th>
										<th>Date &amp; time</th>
										<th>Visitor</th>
										<th>Mobile</th>
										<th>Customer</th>
										<th>Question</th>
										<th>Answer</th>
										<th>Channel</th>
										<th>Follow-up</th>
										<th>Delivery</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$i = 1;
									foreach ($rows as $r) {
										$channel = (string) ($r['channel'] ?? 'auto');
										$delivery = (string) ($r['delivery_status'] ?? 'logged');
										$channelClass = $channel === 'support' ? 'ew-chat-chip--support' : 'ew-chat-chip--auto';
										$deliveryClass = 'ew-chat-chip';
										if ($delivery === 'emailed') {
											$deliveryClass .= ' ew-chat-chip--emailed';
										} elseif ($delivery === 'failed') {
											$deliveryClass .= ' ew-chat-chip--failed';
										}
										$follow = trim((string) ($r['follow_up'] ?? ''));
										$customerMode = ($r['customer_mode'] ?? 'new') === 'existing' ? 'Existing' : 'New';
										?>
										<tr>
											<td class="text-center"><?php echo $i++; ?></td>
											<td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
											<td><?php echo htmlspecialchars($r['visitor_name'] ?? ''); ?></td>
											<td><?php echo htmlspecialchars($r['visitor_mobile'] ?? ''); ?></td>
											<td><?php echo htmlspecialchars($customerMode); ?></td>
											<td class="ew-chat-qa-text"><?php echo nl2br(htmlspecialchars($r['question'] ?? '')); ?></td>
											<td class="ew-chat-qa-text"><?php echo $r['answer'] !== null && $r['answer'] !== '' ? nl2br(htmlspecialchars($r['answer'])) : '—'; ?></td>
											<td><span class="ew-chat-chip <?php echo $channelClass; ?>"><?php echo htmlspecialchars(chatbot_qa_channel_label($channel)); ?></span></td>
											<td><?php echo $follow !== '' ? htmlspecialchars(ucfirst($follow)) : '—'; ?></td>
											<td><span class="<?php echo $deliveryClass; ?>"><?php echo htmlspecialchars(chatbot_qa_delivery_label($delivery)); ?></span></td>
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
	$('#chatbot_qa_table').DataTable({ order: [[1, 'desc']], pageLength: 50 });
});
</script>
</body>
</html>