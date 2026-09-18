<?php
include_once('include/connect.php');
include_once('include/function.php');
?>
<!doctype html>
<html lang="en">

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<style>
		.dataTable th.sorting:after,
		.dataTable th.sorting_desc:after {
			top: 17px;
			right: 3px;
		}

		.dataTable th.sorting:before,
		.dataTable th.sorting_asc:after {
			top: 10px;
			right: 3px;
		}

		.table td.actions .action-buttons {
			width: 72px;
		}

		.pod-upload-hints {
			margin-top: 10px;
			font-size: 12px;
			color: var(--ew-text-muted, #6B7A8D);
			line-height: 1.5;
		}

		.pod-image-grid {
			display: flex;
			flex-wrap: wrap;
			gap: 14px;
			margin-top: 12px;
		}

		.pod-image-card {
			width: 150px;
			flex: 0 0 150px;
			border: 1px solid var(--ew-border-light, #E9ECF0);
			border-radius: 8px;
			padding: 10px;
			background: #FAFBFC;
			text-align: center;
		}

		.pod-image-card img {
			width: 100%;
			height: 120px;
			object-fit: cover;
			border-radius: 6px;
			background: #fff;
			border: 1px solid var(--ew-border-light, #E9ECF0);
			cursor: pointer;
		}

		.pod-image-card p {
			margin: 8px 0 0;
			font-size: 11px;
			color: var(--ew-text-muted, #6B7A8D);
			word-break: break-all;
		}

		.pod-image-card .btn-remove-pod-img {
			margin-top: 8px;
		}

		.pod-preview-empty {
			padding: 24px;
			text-align: center;
			color: var(--ew-text-muted, #6B7A8D);
		}

	</style>
</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php include_once('include/header.php'); ?>
			<?php include_once('include/menu.php'); ?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Proof of Delivery</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>List of POD</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openUploadPod">Upload POD <i class="fa fa-upload"></i></button>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th class="table-title" style="width:8%">S.No</th>
											<th class="table-title" style="width:20%">Date</th>
											<th class="table-title" style="width:20%">Images Count</th>
											<th class="table-title sorting_disabled" style="width:14%">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$query = 'SELECT * FROM pod_files ORDER BY id DESC';
										$query_result1 = mysqli_query($conn, $query);
										$i = 1;
										while ($pod_list = mysqli_fetch_assoc($query_result1)) {
											$screens = explode('@@', $pod_list['screens']);
											$unique_screens = array_filter($screens);
											$pod_key = md5($pod_list['id']);
											?>
											<tr>
												<td class="text-center"><?php echo $i; ?></td>
												<td class="text-center"><?php echo htmlspecialchars($pod_list['created_at']); ?></td>
												<td class="text-center"><?php echo count($unique_screens); ?></td>
												<td class="actions center-content">
													<div class="action-buttons">
														<a href="#" title="Preview" class="table-actions btn-preview-pod" data-key="<?php echo htmlspecialchars($pod_key); ?>"><i class="fa fa-eye"></i></a>
														<a href="#" title="Edit" class="table-actions btn-edit-pod" data-key="<?php echo htmlspecialchars($pod_key); ?>"><i class="fa fa-pencil"></i></a>
													</div>
												</td>
											</tr>
											<?php
											$i++;
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

	<div class="ew-v2-modal-backdrop" id="uploadPodModal">
		<div class="ew-v2-modal">
			<div class="ew-v2-modal-head">
				<h3>Upload POD</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
				<form id="upload_pod_form">
					<div class="form-group">
						<label class="control-label">Select POD <span style="color:red;">*</span> :</label>
						<div class="ew-upload-image ew-upload-image--multi" id="upload_drop_wrap">
							<div class="ew-upload-image__zone" tabindex="0" role="button" aria-label="Upload POD files">
								<input type="file" name="pod_file[]" id="upload_pod_file" class="ew-upload-image__input" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png">
								<div class="ew-upload-image__body">
									<i class="fa fa-cloud-upload" aria-hidden="true"></i>
									<span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
									<span class="ew-upload-image__meta">JPEG, JPG, PNG</span>
									<span class="ew-upload-image__filename upload-file-name"></span>
									<span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
								</div>
							</div>
						</div>
						<div class="pod-upload-hints">
							File name must be GRN number, e.g. XXXX00001 <span style="color:#DD111E;">*</span>
						</div>
					</div>
					<div id="upload_pod_msg" class="text-danger" style="display:none;"></div>
				</form>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Cancel</button>
				<button type="button" class="btn btn-primary" id="save_upload_pod">Upload</button>
			</div>
		</div>
	</div>

	<div class="ew-v2-modal-backdrop" id="editPodModal">
		<div class="ew-v2-modal ew-v2-modal--pod-edit">
			<div class="ew-v2-modal-head">
				<h3>Edit POD Images</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
				<input type="hidden" id="edit_pod_key" value="">
				<div class="form-group">
					<label class="control-label">Add More Images :</label>
					<div class="ew-upload-image ew-upload-image--multi" id="edit_drop_wrap">
						<div class="ew-upload-image__zone" tabindex="0" role="button" aria-label="Add POD files">
							<input type="file" name="pod_file[]" id="edit_pod_file" class="ew-upload-image__input" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png">
							<div class="ew-upload-image__body">
								<i class="fa fa-cloud-upload" aria-hidden="true"></i>
								<span class="ew-upload-image__hint">Drag &amp; drop or browse</span>
								<span class="ew-upload-image__meta">JPEG, JPG, PNG</span>
								<span class="ew-upload-image__filename edit-file-name"></span>
								<span class="ew-btn-v2 ew-btn-v2-outline ew-upload-image__browse">Browse</span>
							</div>
						</div>
					</div>
				</div>
				<div id="edit_pod_msg" class="text-danger" style="display:none;"></div>
				<div class="ew-section-label" style="margin-top:16px;">Existing Images</div>
				<div class="pod-image-grid" id="edit_pod_images"></div>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Cancel</button>
				<button type="button" class="btn btn-primary" id="save_edit_pod">Update</button>
			</div>
		</div>
	</div>

	<div class="ew-v2-modal-backdrop" id="previewPodModal">
		<div class="ew-v2-modal ew-v2-modal--pod-preview">
			<div class="ew-v2-modal-head">
				<h3 id="previewPodTitle">POD Preview</h3>
				<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
				<div class="pod-image-grid" id="preview_pod_images"></div>
			</div>
			<div class="ew-v2-modal-foot">
				<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			function fileLabel(files) {
				if (!files || !files.length) {
					return '';
				}
				if (files.length === 1) {
					return files[0].name;
				}
				return files.length + ' files selected';
			}

			function setInputFiles($input, files) {
				if (typeof DataTransfer !== 'undefined' && files && files.length) {
					var dt = new DataTransfer();
					for (var i = 0; i < files.length; i++) {
						dt.items.add(files[i]);
					}
					$input[0].files = dt.files;
				}
			}

			function initEwUploadMulti($wrap) {
				var $input = $wrap.find('.ew-upload-image__input');
				var $zone = $wrap.find('.ew-upload-image__zone');
				var $filename = $wrap.find('.ew-upload-image__filename');

				function applyFiles(fileList) {
					if (!fileList || !fileList.length) {
						$filename.text('').attr('title', '');
						$wrap.removeClass('has-file');
						return;
					}
					var label = fileLabel(fileList);
					$filename.text(label).attr('title', label);
					$wrap.addClass('has-file');
				}

				$zone.on('click', function(e) {
					if ($(e.target).is('input')) {
						return;
					}
					$input.trigger('click');
				});

				$zone.on('keydown', function(e) {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						$input.trigger('click');
					}
				});

				$input.on('change', function() {
					applyFiles(this.files);
				});

				$zone.on('dragover dragenter', function(e) {
					e.preventDefault();
					$zone.addClass('is-dragover');
				});

				$zone.on('dragleave drop', function(e) {
					e.preventDefault();
					$zone.removeClass('is-dragover');
				});

				$zone.on('drop', function(e) {
					var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
					if (!files || !files.length) {
						return;
					}
					setInputFiles($input, files);
					applyFiles($input[0].files);
				});
			}

			initEwUploadMulti($('#upload_drop_wrap'));
			initEwUploadMulti($('#edit_drop_wrap'));

			function resetUploadModal() {
				$('#upload_pod_file').val('');
				$('#upload_drop_wrap').removeClass('has-file');
				$('.upload-file-name').text('').attr('title', '');
				$('#upload_pod_msg').hide().text('');
			}

			function resetEditModal() {
				$('#edit_pod_file').val('');
				$('#edit_drop_wrap').removeClass('has-file');
				$('.edit-file-name').text('').attr('title', '');
				$('#edit_pod_msg').hide().text('');
			}

			function fetchPod(key, callback) {
				$.getJSON('pod_data.php', { cmd: 'fetch', key: key }, function(res) {
					if (!res || res.status !== 0) {
						if (typeof ewToast === 'function') {
							ewToast(res && res.message ? res.message : 'Could not load POD.', 'error');
						}
						return;
					}
					callback(res);
				}).fail(function() {
					if (typeof ewToast === 'function') {
						ewToast('Could not load POD.', 'error');
					}
				});
			}

			function renderImageGrid($container, images, options) {
				options = options || {};
				$container.empty();
				if (!images || !images.length) {
					$container.html('<div class="pod-preview-empty">No images uploaded.</div>');
					return;
				}
				$.each(images, function(i, img) {
					var html = '<div class="pod-image-card" data-file="' + $('<div>').text(img.file).html() + '">';
					html += '<img src="' + img.url + '" alt="' + $('<div>').text(img.file).html() + '">';
					html += '<p>' + $('<div>').text(img.file).html() + '</p>';
					if (options.removable) {
						html += '<button type="button" class="btn btn-danger btn-xs btn-remove-pod-img" data-file="' + $('<div>').text(img.file).html() + '">Remove</button>';
					}
					html += '</div>';
					$container.append(html);
				});
			}

			$('#openUploadPod').on('click', function() {
				resetUploadModal();
				ewV2OpenModal('uploadPodModal');
			});

			$(document).on('click', '.btn-preview-pod', function(e) {
				e.preventDefault();
				var key = $(this).data('key');
				fetchPod(key, function(res) {
					$('#previewPodTitle').text('POD Preview — ' + (res.created_at || ''));
					renderImageGrid($('#preview_pod_images'), res.images, { removable: false });
					ewV2OpenModal('previewPodModal');
				});
			});

			$(document).on('click', '#preview_pod_images img', function() {
				window.open($(this).attr('src'), '_blank');
			});

			$(document).on('click', '.btn-edit-pod', function(e) {
				e.preventDefault();
				var key = $(this).data('key');
				resetEditModal();
				fetchPod(key, function(res) {
					$('#edit_pod_key').val(res.edit_key);
					renderImageGrid($('#edit_pod_images'), res.images, { removable: true });
					ewV2OpenModal('editPodModal');
				});
			});

			$(document).on('click', '.btn-remove-pod-img', function() {
				var file = $(this).data('file');
				var key = $('#edit_pod_key').val();
				if (!confirm('Remove this image?')) {
					return;
				}
				$.post('save_details.php?delete_id=' + encodeURIComponent(file), {
					form_name: 'delete_pod_img',
					tbl_id: key
				}, function() {
					fetchPod(key, function(res) {
						renderImageGrid($('#edit_pod_images'), res.images, { removable: true });
					});
				});
			});

			$('#save_upload_pod').on('click', function() {
				if (!$('#upload_pod_file').prop('files').length) {
					$('#upload_pod_msg').text('Please select at least one file.').show();
					return;
				}
				var formdata = new FormData();
				var files = $('#upload_pod_file').prop('files');
				for (var i = 0; i < files.length; i++) {
					formdata.append('pod_file[]', files[i]);
				}
				formdata.append('form_name', 'pod_form');
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.ajax({
					url: 'save_details.php',
					type: 'post',
					data: formdata,
					contentType: false,
					processData: false,
					success: function(data) {
						$btn.prop('disabled', false);
						if (String(data).trim() !== '0') {
							ewV2CloseModal('uploadPodModal');
							if (typeof ewToast === 'function') {
								ewToast('POD uploaded successfully.', 'success');
							}
							setTimeout(function() { location.reload(); }, 600);
						} else {
							$('#upload_pod_msg').text('Upload failed. Check file type and name.').show();
						}
					},
					error: function() {
						$btn.prop('disabled', false);
						$('#upload_pod_msg').text('Upload request failed.').show();
					}
				});
			});

			$('#save_edit_pod').on('click', function() {
				var key = $('#edit_pod_key').val();
				if (!$('#edit_pod_file').prop('files').length) {
					ewV2CloseModal('editPodModal');
					return;
				}
				var formdata = new FormData();
				var files = $('#edit_pod_file').prop('files');
				for (var i = 0; i < files.length; i++) {
					formdata.append('pod_file[]', files[i]);
				}
				formdata.append('form_name', 'pod_retrive');
				formdata.append('edit_id', key);
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.ajax({
					url: 'save_details.php',
					type: 'post',
					data: formdata,
					contentType: false,
					processData: false,
					success: function(data) {
						$btn.prop('disabled', false);
						if (String(data).trim() !== '0') {
							ewV2CloseModal('editPodModal');
							if (typeof ewToast === 'function') {
								ewToast('POD updated successfully.', 'success');
							}
							setTimeout(function() { location.reload(); }, 600);
						} else {
							$('#edit_pod_msg').text('Update failed. Check file type.').show();
						}
					},
					error: function() {
						$btn.prop('disabled', false);
						$('#edit_pod_msg').text('Update request failed.').show();
					}
				});
			});
		});
	</script>
</body>

</html>
