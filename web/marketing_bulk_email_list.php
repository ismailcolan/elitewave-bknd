<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/marketing_email.php');

marketing_bulk_email_ensure_tables($conn);
$rows = marketing_bulk_email_list_rows($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-mkt-thumb {
			width: 72px;
			height: 48px;
			object-fit: cover;
			border-radius: 4px;
			border: 1px solid #e2e8f0;
			background: #f8fafc;
		}
		.ew-mkt-chip {
			display: inline-block;
			font-size: 11px;
			font-weight: 600;
			padding: 3px 8px;
			border-radius: 999px;
			background: #e2e8f0;
			color: #334155;
		}
		.ew-mkt-chip.is-ok { background: #dcfce7; color: #166534; }
		.ew-mkt-chip.is-partial { background: #fef3c7; color: #92400e; }
		.ew-mkt-chip.is-fail { background: #fee2e2; color: #991b1b; }
		.ew-mkt-recipients {
			max-width: 280px;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		#ewMktDetailModal .ew-v2-modal {
			max-width: 640px;
			width: 100%;
		}
		.ew-mkt-detail-list {
			margin: 0;
			padding-left: 18px;
			max-height: 240px;
			overflow: auto;
		}
		.ew-mkt-detail-preview {
			display: block;
			width: 100%;
			max-width: 100%;
			height: auto;
			max-height: 280px;
			object-fit: contain;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			margin-top: 10px;
			background: #f8fafc;
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
				<div class="ew-page-v2 ew-page-v2--wide-table">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">Bulk Email</h1>
						</div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>All Marketing Emails</h2>
							<div class="ew-toolbar-right">
								<div class="ew-list-toolbar__tools"></div>
								<a href="marketing_bulk_email.php" class="ew-btn-v2 ew-btn-v2-primary">Compose <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
							<table class="table table-bordered table-striped" id="marketing_bulk_email_table">
								<thead>
									<tr>
										<th style="width:56px;">S.No</th>
										<th style="width:90px;">Image</th>
										<th>Subject</th>
										<th style="width:140px;">Sent on</th>
										<th style="width:90px;">Sent</th>
										<th style="width:90px;">Failed</th>
										<th>Recipients</th>
										<th style="width:100px;">Status</th>
										<th style="width:80px;" class="text-center">Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									if (empty($rows)) {
										echo '<tr><td colspan="9" class="text-center">No marketing emails sent yet. <a href="marketing_bulk_email.php">Compose</a> your first campaign.</td></tr>';
									} else {
										$i = 1;
										foreach ($rows as $r) {
											$sent = (int) ($r['sent_count'] ?? 0);
											$failed = (int) ($r['failed_count'] ?? 0);
											$chip = 'is-ok';
											$chip_label = 'Sent';
											if ($sent === 0 && $failed > 0) {
												$chip = 'is-fail';
												$chip_label = 'Failed';
											} elseif ($sent > 0 && $failed > 0) {
												$chip = 'is-partial';
												$chip_label = 'Partial';
											}
											$recipients = array();
											$decoded = json_decode((string) ($r['recipients_json'] ?? '[]'), true);
											if (is_array($decoded)) {
												foreach ($decoded as $rec) {
													if (!empty($rec['email'])) {
														$recipients[] = trim(($rec['name'] ?? '') . ' <' . $rec['email'] . '>');
													}
												}
											}
											$recipient_preview = $recipients ? implode(', ', $recipients) : '—';
											$img = trim((string) ($r['image_url'] ?? ''));
											$created = (string) ($r['created_at'] ?? '');
											$created_disp = $created !== '' ? date('d-m-Y H:i', strtotime($created)) : '—';
											?>
											<tr>
												<td class="text-center"><?php echo $i++; ?></td>
												<td class="text-center">
													<?php if ($img !== '') { ?>
														<img class="ew-mkt-thumb" src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="" />
													<?php } else { echo '—'; } ?>
												</td>
												<td><?php echo htmlspecialchars((string) ($r['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
												<td><?php echo htmlspecialchars($created_disp, ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="text-center"><?php echo $sent; ?></td>
												<td class="text-center"><?php echo $failed; ?></td>
												<td><div class="ew-mkt-recipients" title="<?php echo htmlspecialchars($recipient_preview, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($recipient_preview, ENT_QUOTES, 'UTF-8'); ?></div></td>
												<td><span class="ew-mkt-chip <?php echo $chip; ?>"><?php echo $chip_label; ?></span></td>
												<td class="actions center-content">
													<a href="javascript:void(0);" class="table-actions btn-mkt-view"
														title="View"
														data-id="<?php echo (int) $r['id']; ?>"
														data-subject="<?php echo htmlspecialchars((string) ($r['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
														data-message="<?php echo htmlspecialchars((string) ($r['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
														data-image="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>"
														data-sent="<?php echo $sent; ?>"
														data-failed="<?php echo $failed; ?>"
														data-created="<?php echo htmlspecialchars($created_disp, ENT_QUOTES, 'UTF-8'); ?>"
														data-recipients="<?php echo htmlspecialchars(json_encode($recipients), ENT_QUOTES, 'UTF-8'); ?>"
														data-errors="<?php echo htmlspecialchars((string) ($r['errors_json'] ?? '[]'), ENT_QUOTES, 'UTF-8'); ?>">
														<i class="fa fa-eye"></i>
													</a>
												</td>
											</tr>
											<?php
										}
									}
									?>
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

<div class="ew-v2-modal-backdrop" id="ewMktDetailModal">
	<div class="ew-v2-modal ew-v2-modal--wide">
		<div class="ew-v2-modal-head">
			<h3>Marketing email details</h3>
			<button type="button" class="ew-v2-modal-close" data-ew-mkt-close aria-label="Close">&times;</button>
		</div>
		<div class="ew-v2-modal-body">
			<p style="margin:0 0 6px;"><strong id="mkt_d_subject"></strong></p>
			<p class="ew-field-hint" style="margin:0 0 10px;" id="mkt_d_meta"></p>
			<p id="mkt_d_message" style="white-space:pre-wrap;margin:0 0 10px;"></p>
			<img id="mkt_d_image" class="ew-mkt-detail-preview" alt="Marketing image" hidden />
			<h4 style="margin:14px 0 6px;font-size:13px;">Recipients</h4>
			<ul class="ew-mkt-detail-list" id="mkt_d_recipients"></ul>
			<div id="mkt_d_errors_wrap" hidden>
				<h4 style="margin:14px 0 6px;font-size:13px;color:#991b1b;">Errors</h4>
				<ul class="ew-mkt-detail-list" id="mkt_d_errors"></ul>
			</div>
		</div>
		<div class="ew-v2-modal-foot">
			<button type="button" class="btn btn-default-outline" data-ew-mkt-close>Close</button>
		</div>
	</div>
</div>

<script>
$(function () {
	var $modal = $('#ewMktDetailModal');
	function openModal() { $modal.addClass('open'); }
	function closeModal() { $modal.removeClass('open'); }
	$('[data-ew-mkt-close]').on('click', closeModal);
	$modal.on('click', function (e) {
		if (e.target === this) closeModal();
	});
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && $modal.hasClass('open')) closeModal();
	});

	if ($.fn.DataTable && $('#marketing_bulk_email_table tbody tr').length && !$('#marketing_bulk_email_table td[colspan]').length) {
		$('#marketing_bulk_email_table').DataTable({
			order: [],
			pageLength: 25,
			columnDefs: [{ orderable: false, targets: [1, 8] }]
		});
	}

	$(document).on('click', '.btn-mkt-view', function () {
		var $btn = $(this);
		$('#mkt_d_subject').text($btn.data('subject') || '');
		$('#mkt_d_meta').text('Sent on ' + ($btn.data('created') || '—') + ' · Sent: ' + ($btn.data('sent') || 0) + ' · Failed: ' + ($btn.data('failed') || 0));
		var msg = $btn.attr('data-message') || '';
		$('#mkt_d_message').text(msg || '(No message)');
		var img = $btn.data('image') || '';
		var $img = $('#mkt_d_image');
		if (img) {
			$img.attr('src', img).prop('hidden', false);
		} else {
			$img.attr('src', '').prop('hidden', true);
		}
		var recipients = [];
		try { recipients = JSON.parse($btn.attr('data-recipients') || '[]'); } catch (e) { recipients = []; }
		var $rec = $('#mkt_d_recipients').empty();
		if (!recipients.length) {
			$rec.append($('<li></li>').text('No recipients logged.'));
		} else {
			recipients.forEach(function (r) { $rec.append($('<li></li>').text(r)); });
		}
		var errors = [];
		try { errors = JSON.parse($btn.attr('data-errors') || '[]'); } catch (e2) { errors = []; }
		var $errWrap = $('#mkt_d_errors_wrap');
		if (errors && errors.length) {
			$errWrap.prop('hidden', false);
			var $err = $('#mkt_d_errors').empty();
			errors.forEach(function (r) { $err.append($('<li></li>').text(r)); });
		} else {
			$errWrap.prop('hidden', true);
			$('#mkt_d_errors').empty();
		}
		openModal();
	});
});
</script>
</body>
</html>
