<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/marketing_email.php');

$clients = marketing_client_recipient_options($conn);
$client_count = count($clients);
$default_cc = marketing_default_cc_email();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2--invoice .ew-mkt-preview {
			display: none;
			margin-top: 12px;
			border: 1px solid var(--ew-border, #D8DDE5);
			border-radius: 8px;
			overflow: hidden;
			background: #F8FAFC;
			max-width: 480px;
		}
		.ew-page-v2--invoice .ew-mkt-preview.is-visible { display: block; }
		.ew-page-v2--invoice .ew-mkt-preview img {
			display: block;
			width: 100%;
			height: auto;
		}
		.ew-page-v2--invoice .ew-mkt-client-actions {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: 8px;
			margin-top: 10px;
		}
		.ew-page-v2--invoice #mkt_clients + .select2-container .select2-selection--multiple,
		.ew-page-v2--invoice #mkt_clients + .select2-container .select2-choices {
			min-height: 140px !important;
			border-radius: 8px;
		}
		.ew-page-v2--invoice #response {
			display: none;
			margin: 0 16px 12px;
		}
		.ew-page-v2--invoice #response.is-visible { display: block; }
		.ew-page-v2--invoice #response .ew-mkt-counts {
			font-weight: 700;
			margin-bottom: 2px;
		}
		.ew-page-v2--invoice #response ul {
			margin: 8px 0 0;
			padding-left: 18px;
			text-align: left;
		}
		.ew-page-v2--invoice #response a {
			font-weight: 600;
			margin-left: 6px;
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
				<div class="ew-page-v2 ew-page-v2--invoice">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<a href="marketing_bulk_email_list.php" class="ew-back-btn" title="Back to list"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title">Compose Bulk Email</h1>
						</div>
						<div class="ew-toolbar-right">
							<a href="marketing_bulk_email_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
						</div>
					</div>

					<form id="marketing_bulk_form" enctype="multipart/form-data" autocomplete="off">
						<input type="hidden" name="form_name" value="marketing_bulk_send" />

						<div id="response" class="alert alert-danger" role="status" aria-live="polite">
							<div class="message" style="text-align:left;">
								<div class="ew-mkt-counts" id="mkt_status_counts"></div>
								<div id="mkt_status_message"></div>
								<ul id="mkt_status_errors"></ul>
							</div>
						</div>

						<div class="ew-card ew-invoice-details-card">
							<h2 class="ew-card-section-title">Email details</h2>
							<div class="ew-form-body">
								<div class="ew-form-grid ew-form-grid--invoice">
									<div class="ew-field span-4">
										<label for="mkt_subject">Subject <span class="req">*</span></label>
										<input type="text" name="subject" id="mkt_subject" class="form-control" required maxlength="200" placeholder="Enter email subject" />
									</div>
									<div class="ew-field span-4">
										<label for="mkt_message">Message <span class="ew-field-hint">(optional)</span></label>
										<textarea name="message" id="mkt_message" class="form-control" rows="4" placeholder="Short message shown above the image"></textarea>
									</div>
									<div class="ew-field span-2">
										<label for="mkt_cc">CC</label>
										<input type="text" name="cc" id="mkt_cc" class="form-control" value="<?php echo htmlspecialchars($default_cc, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($default_cc, ENT_QUOTES, 'UTF-8'); ?>" />
										<p class="ew-field-hint">Default CC: <?php echo htmlspecialchars($default_cc, ENT_QUOTES, 'UTF-8'); ?>. Use commas for more addresses.</p>
									</div>
								</div>
							</div>
						</div>

						<div class="ew-card ew-invoice-details-card">
							<h2 class="ew-card-section-title">Recipients</h2>
							<div class="ew-form-body">
								<div class="ew-form-grid ew-form-grid--invoice">
									<div class="ew-field span-4">
										<label for="mkt_clients">Clients <span class="req">*</span></label>
										<select name="client_ids[]" id="mkt_clients" class="form-control" multiple="multiple" data-placeholder="Select clients" style="width:100%;">
											<?php foreach ($clients as $c) {
												$label = $c['name'] . ' (' . $c['email'] . ')';
												echo '<option value="' . (int) $c['id'] . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
											} ?>
										</select>
										<div class="ew-mkt-client-actions">
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="mkt_select_all">Select all</button>
											<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="mkt_clear_all">Clear</button>
											<span class="ew-field-hint"><?php echo (int) $client_count; ?> client<?php echo $client_count === 1 ? '' : 's'; ?> with email</span>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="ew-card ew-invoice-details-card">
							<h2 class="ew-card-section-title">Marketing image</h2>
							<div class="ew-form-body">
								<div class="ew-form-grid ew-form-grid--invoice">
									<div class="ew-field span-4">
										<label>Upload image <span class="req">*</span></label>
										<div class="ew-upload-image" id="mkt_upload_wrap">
											<div class="ew-upload-image__zone" tabindex="0" role="button" aria-label="Upload marketing image">
												<input type="file" name="marketing_image" id="mkt_image" class="ew-upload-image__input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
												<div class="ew-upload-image__body">
													<i class="fa fa-cloud-upload" aria-hidden="true"></i>
													<span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
													<span class="ew-upload-image__meta">JPG, PNG or WEBP · max 5MB · shown in email body</span>
													<span class="ew-upload-image__filename" id="mkt_filename"></span>
													<span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
												</div>
											</div>
										</div>
										<div class="ew-mkt-preview" id="mkt_preview">
											<img id="mkt_preview_img" alt="Marketing preview" />
										</div>
									</div>
								</div>
							</div>
							<div class="ew-form-footer ew-invoice-form-footer">
								<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="mkt_reset_btn">Reset</button>
								<a href="marketing_bulk_email_list.php" class="ew-btn-v2 ew-btn-v2-outline">Cancel</a>
								<button type="submit" class="ew-btn-v2 ew-btn-v2-primary" id="mkt_send_btn">
									<i class="fa fa-paper-plane"></i> Send Email
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>
<script>
(function ($) {
	var $form = $('#marketing_bulk_form');
	var $wrap = $('#mkt_upload_wrap');
	var $input = $('#mkt_image');
	var $zone = $wrap.find('.ew-upload-image__zone');
	var $filename = $('#mkt_filename');
	var $preview = $('#mkt_preview');
	var $previewImg = $('#mkt_preview_img');
	var $clients = $('#mkt_clients');
	var $status = $('#response');
	var $statusCounts = $('#mkt_status_counts');
	var $statusMessage = $('#mkt_status_message');
	var $statusErrors = $('#mkt_status_errors');
	var $sendBtn = $('#mkt_send_btn');
	var defaultCc = <?php echo json_encode($default_cc); ?>;
	var previewObjectUrl = null;

	function clearStatus() {
		$status.removeClass('is-visible alert-success alert-danger alert-warning');
		$statusCounts.text('');
		$statusMessage.empty();
		$statusErrors.empty();
	}

	function showStatus(kind, countsText, message, errors) {
		clearStatus();
		var alertClass = 'alert-danger';
		if (kind === 'success') alertClass = 'alert-success';
		else if (kind === 'partial') alertClass = 'alert-warning';
		$status.addClass('is-visible ' + alertClass);
		$statusCounts.text(countsText || '');
		$statusMessage.text(message || '');
		$statusErrors.empty();
		if (errors && errors.length) {
			errors.forEach(function (err) {
				$statusErrors.append($('<li></li>').text(err));
			});
		}
		try {
			$('html, body').animate({ scrollTop: Math.max(0, $status.offset().top - 80) }, 200);
		} catch (e) {}
	}

	function resetUpload() {
		$input.val('');
		$filename.text('').attr('title', '');
		$wrap.removeClass('has-file');
		$preview.removeClass('is-visible');
		$previewImg.attr('src', '');
		if (previewObjectUrl) {
			URL.revokeObjectURL(previewObjectUrl);
			previewObjectUrl = null;
		}
	}

	function applyFile(file) {
		if (!file) {
			resetUpload();
			return;
		}
		if (!/\.(jpe?g|png|webp)$/i.test(file.name || '')) {
			showStatus('error', '', 'Image must be JPG, PNG or WEBP.', []);
			resetUpload();
			return;
		}
		if (file.size > 5 * 1024 * 1024) {
			showStatus('error', '', 'Image must be 5 MB or smaller.', []);
			resetUpload();
			return;
		}
		$filename.text(file.name).attr('title', file.name);
		$wrap.addClass('has-file');
		if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
		previewObjectUrl = URL.createObjectURL(file);
		$previewImg.attr('src', previewObjectUrl);
		$preview.addClass('is-visible');
		clearStatus();
	}

	if ($clients.length && typeof $clients.select2 === 'function') {
		$clients.select2({
			placeholder: $clients.data('placeholder') || 'Select clients',
			allowClear: true,
			width: '100%'
		});
	}

	$zone.on('click', function (e) {
		if ($(e.target).is('input')) return;
		$input.trigger('click');
	});
	$zone.on('keydown', function (e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			$input.trigger('click');
		}
	});
	$input.on('change', function () {
		applyFile(this.files && this.files[0] ? this.files[0] : null);
	});
	$zone.on('dragover dragenter', function (e) {
		e.preventDefault();
		$zone.addClass('is-dragover');
	});
	$zone.on('dragleave drop', function (e) {
		e.preventDefault();
		$zone.removeClass('is-dragover');
	});
	$zone.on('drop', function (e) {
		var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
		if (!files || !files.length) return;
		if (typeof DataTransfer !== 'undefined') {
			var dt = new DataTransfer();
			dt.items.add(files[0]);
			$input[0].files = dt.files;
		}
		applyFile(files[0]);
	});

	$('#mkt_select_all').on('click', function () {
		var values = [];
		$clients.find('option').each(function () { values.push($(this).val()); });
		$clients.val(values).trigger('change');
	});
	$('#mkt_clear_all').on('click', function () {
		$clients.val(null).trigger('change');
	});

	$('#mkt_reset_btn').on('click', function () {
		$form[0].reset();
		$('#mkt_cc').val(defaultCc);
		resetUpload();
		$clients.val(null).trigger('change');
		clearStatus();
	});

	$form.on('submit', function (e) {
		e.preventDefault();
		clearStatus();

		var subject = $.trim($('#mkt_subject').val() || '');
		var selected = $clients.val() || [];
		var cc = $.trim($('#mkt_cc').val() || '');
		if (!cc) {
			cc = defaultCc;
			$('#mkt_cc').val(cc);
		}
		if (!subject) {
			showStatus('error', '', 'Subject is required.', []);
			return;
		}
		if (!$input[0].files || !$input[0].files.length) {
			showStatus('error', '', 'Upload a marketing image.', []);
			return;
		}
		if (!selected.length) {
			showStatus('error', '', 'Select at least one client.', []);
			return;
		}

		var fd = new FormData($form[0]);
		fd.set('cc', cc);
		$sendBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

		$.ajax({
			url: 'save_details.php',
			type: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			dataType: 'json'
		}).done(function (res) {
			if (!res || typeof res !== 'object') {
				showStatus('error', '', 'Unexpected response from server.', []);
				return;
			}
			var sent = parseInt(res.sent, 10) || 0;
			var failed = parseInt(res.failed, 10) || 0;
			var counts = 'Sent: ' + sent + ' · Failed: ' + failed;
			var kind = 'error';
			if (res.ok && failed === 0) kind = 'success';
			else if (res.ok && failed > 0) kind = 'partial';
			showStatus(kind, counts, res.message || '', res.errors || []);
			if (res.ok) {
				$statusMessage.append($('<a></a>').attr('href', 'marketing_bulk_email_list.php').text('View list'));
			}
			if (res.ok && failed === 0) {
				resetUpload();
				$clients.val(null).trigger('change');
				$('#mkt_subject').val('');
				$('#mkt_message').val('');
				$('#mkt_cc').val(defaultCc);
			}
		}).fail(function (xhr) {
			var msg = 'Could not send marketing emails.';
			if (xhr && xhr.responseText) {
				try {
					var parsed = JSON.parse(xhr.responseText);
					if (parsed && parsed.message) msg = parsed.message;
				} catch (err) {}
			}
			showStatus('error', '', msg, []);
		}).always(function () {
			$sendBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Email');
		});
	});
})(jQuery);
</script>
</body>
</html>
