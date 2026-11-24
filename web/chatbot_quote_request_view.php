<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/chatbot_quote_request_helpers.php');

$id = (int) ($_GET['id'] ?? 0);
$row = chatbot_quote_request_get($conn, $id);
if (!$row) {
	header('Location: chatbot_quote_request_list.php');
	exit;
}
chatbot_quote_request_mark_notification_seen($conn, $id);
$statuses = chatbot_quote_request_status_options();
$statusLabel = $statuses[$row['status']] ?? $row['status'];
$typeLabel = ($row['transhipment_type'] ?? '') === 'multiple_location' ? 'Multiple location' : 'Single location';
$canForward = chatbot_quote_request_can_forward($conn, $row);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.cn-view-meta {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 10px 20px;
			margin-bottom: 8px;
			font-size: 13px;
		}
		.cn-view-meta dt {
			font-weight: 700;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #64748b;
			margin: 0 0 4px;
		}
		.cn-view-meta dd {
			margin: 0 0 10px;
			color: #021659;
			font-weight: 600;
			line-height: 1.4;
			word-break: break-word;
		}
		.cn-view-meta .span-full {
			grid-column: 1 / -1;
		}
		.ew-qr-locations {
			margin: 0;
			padding-left: 18px;
			color: #021659;
			font-weight: 600;
		}
		@media (max-width: 768px) {
			.cn-view-meta {
				grid-template-columns: 1fr;
			}
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
				<div class="ew-page-v2">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">Quote Request</h1>
						</div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<div>
								<h2><?php echo htmlspecialchars($row['request_no']); ?></h2>
								<p style="margin:6px 0 0;font-size:13px;color:#64748b;">Submitted <?php echo chatbot_quote_request_display($row['created_at'] ?? ''); ?></p>
							</div>
							<div class="ew-toolbar-right">
								<?php echo chatbot_quote_request_status_badge($row['status'] ?? 'pending'); ?>
								<?php if ($canForward) { ?>
									<a href="chatbot_quote_request_forward.php?id=<?php echo (int) $id; ?>" class="ew-btn-v2 ew-btn-v2-primary" onclick="return confirm('Forward this request to quotation creation?');">Forward to quotation <i class="fa fa-share"></i></a>
								<?php } ?>
								<a href="chatbot_quote_request_list.php" class="ew-btn-v2 ew-btn-v2-outline"><i class="fa fa-arrow-left"></i> Back to list</a>
							</div>
						</div>
						<div class="ew-form-body padded clearfix">
							<div class="cn-view-meta">
								<div>
									<dt>Status</dt>
									<dd><?php echo htmlspecialchars($statusLabel); ?></dd>
								</div>
								<div>
									<dt>Transhipment</dt>
									<dd><?php echo htmlspecialchars($typeLabel); ?></dd>
								</div>
								<div>
									<dt>Company</dt>
									<dd><?php echo chatbot_quote_request_display($row['company_name'] ?? ''); ?></dd>
								</div>
								<div>
									<dt>Contact</dt>
									<dd><?php echo chatbot_quote_request_display($row['contact_mobile'] ?? ''); ?></dd>
								</div>
								<div>
									<dt>Email</dt>
									<dd><?php echo chatbot_quote_request_display($row['email'] ?? ''); ?></dd>
								</div>
								<div>
									<dt>GST</dt>
									<dd><?php echo chatbot_quote_request_display($row['gst_no'] ?? ''); ?></dd>
								</div>
								<div>
									<dt>Origin</dt>
									<dd><?php echo chatbot_quote_request_display($row['origin_text'] ?? ''); ?></dd>
								</div>
								<div>
									<dt>Destination</dt>
									<dd><?php echo chatbot_quote_request_display($row['destination_text'] ?? ''); ?></dd>
								</div>
								<div>
									<dt>Mode</dt>
									<dd><?php echo chatbot_quote_request_display($row['mode_label'] ?? ''); ?></dd>
								</div>
								<div>
									<dt>Weight (kg)</dt>
									<dd><?php echo htmlspecialchars(number_format((float) ($row['weight'] ?? 0), 3, '.', '')); ?></dd>
								</div>
								<?php if (!empty($row['locations']) && is_array($row['locations'])) { ?>
									<div class="span-full">
										<dt>Additional destinations</dt>
										<dd>
											<ul class="ew-qr-locations">
												<?php foreach ($row['locations'] as $loc) { ?>
													<li><?php echo chatbot_quote_request_display($loc['label'] ?? ''); ?></li>
												<?php } ?>
											</ul>
										</dd>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>
</body>
</html>