<?php
require_once('include/connect.php');
require_once('include/function.php');

$is_driver = (($_SESSION['role'] ?? '') === 'DR');
if (!$is_driver) {
	header('Location:pod_list.php');
	exit;
}
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.pod-upload-hints {
			margin-top: 10px;
			font-size: 12px;
			color: var(--ew-text-muted, #6B7A8D);
			line-height: 1.5;
		}
	</style>
</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once('include/header.php');
			require_once('include/menu.php');
			?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Upload Proof of Delivery</h1>
							</div>
						</div>
						<div class="ew-card">
							<h2 class="ew-card-section-title">POD Upload</h2>
							<div class="ew-form-body">
								<form id="transaction_form">
									<div class="ew-field">
										<label class="control-label">Select POD <span style="color:red;">*</span> :</label>
										<div class="ew-upload-image ew-upload-image--multi" id="driver_drop_wrap">
											<div class="ew-upload-image__zone" tabindex="0" role="button" aria-label="Upload POD files">
												<input type="file" name="pod_file[]" id="pod_file" class="ew-upload-image__input" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png">
												<div class="ew-upload-image__body">
													<i class="fa fa-cloud-upload" aria-hidden="true"></i>
													<span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
													<span class="ew-upload-image__meta">JPEG, JPG, PNG</span>
													<span class="ew-upload-image__filename" id="noFile"></span>
													<span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
												</div>
											</div>
										</div>
										<div class="pod-upload-hints">
											File name must be GRN number, e.g. XXXX00001 <span style="color:#DD111E;">*</span>
										</div>
									</div>
								</form>
							</div>
							<div class="ew-form-footer">
								<a class="ew-btn-v2 ew-btn-v2-outline" href="pod_master.php">Reset</a>
								<button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="upload"><i class="fa fa-upload"></i> Upload</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php require_once('include/footer.php'); ?>
		</div>
	</div>

	<script type="text/javascript">
		$(function() {
			var $wrap = $('#driver_drop_wrap');
			var $input = $('#pod_file');
			var $zone = $wrap.find('.ew-upload-image__zone');
			var $filename = $('#noFile');

			function applyFiles(files) {
				if (!files || !files.length) {
					$filename.text('').attr('title', '');
					$wrap.removeClass('has-file');
					return;
				}
				var label = files.length === 1 ? files[0].name : (files.length + ' files selected');
				$filename.text(label).attr('title', label);
				$wrap.addClass('has-file');
			}

			$zone.on('click', function(e) {
				if (!$(e.target).is('input')) $input.trigger('click');
			});
			$input.on('change', function() { applyFiles(this.files); });
			$zone.on('dragover dragenter', function(e) { e.preventDefault(); $zone.addClass('is-dragover'); });
			$zone.on('dragleave drop', function(e) { e.preventDefault(); $zone.removeClass('is-dragover'); });
			$zone.on('drop', function(e) {
				var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
				if (!files || !files.length) return;
				if (typeof DataTransfer !== 'undefined') {
					var dt = new DataTransfer();
					for (var i = 0; i < files.length; i++) dt.items.add(files[i]);
					$input[0].files = dt.files;
				}
				applyFiles($input[0].files);
			});

			$('#upload').on('click', function(e) {
				e.preventDefault();
				if (!$('#pod_file').prop('files').length) {
					if (typeof ewToast === 'function') ewToast('Please select a file.', 'warning');
					return;
				}
				var formdata = new FormData(document.getElementById('transaction_form'));
				formdata.append('form_name', 'pod_form');
				$('#upload').prop('disabled', true);
				$.ajax({
					url: 'save_details.php',
					type: 'post',
					data: formdata,
					contentType: false,
					processData: false,
					success: function(data) {
						$('#upload').prop('disabled', false);
						if (String(data).trim() !== '0') {
							if (typeof ewToast === 'function') ewToast('Uploaded successfully.', 'success');
							location.reload();
						} else if (typeof ewToast === 'function') {
							ewToast('Upload failed.', 'error');
						}
					},
					error: function() {
						$('#upload').prop('disabled', false);
					}
				});
			});
		});
	</script>
</body>

</html>
