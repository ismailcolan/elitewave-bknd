/**
 * Shared attachment upload UI (transactions.php booking panel + receipt).
 * Call ewInitAttachmentUpload() after DOM ready.
 */
(function ($) {
	'use strict';

	var cfg = {};
	var pdfThumbSrc = 'data:image/svg+xml,' + encodeURIComponent(
		'<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
		+ '<rect width="64" height="64" rx="8" fill="#fee2e2"/>'
		+ '<text x="32" y="38" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" font-weight="700" fill="#b91c1c">PDF</text>'
		+ '</svg>'
	);

	function getNextFileNo() {
		var last = $(cfg.container).find('.file-group:last').data('file-no');
		return isNaN(last) || !last ? 1 : (parseInt(last, 10) + 1);
	}

	function isImageUploadFile(file) {
		return file && file.type && file.type.indexOf('image/') === 0;
	}

	function isPdfUploadFile(file) {
		return file && ((file.type && file.type === 'application/pdf') || /\.pdf$/i.test(file.name || ''));
	}

	function escapeUploadHtml(text) {
		return $('<div>').text(text || '').html();
	}

	function formatUploadFileSize(bytes) {
		if (!bytes && bytes !== 0) return 'Ready to upload';
		if (bytes < 1024) return bytes + ' B';
		if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
		return (bytes / 1048576).toFixed(1) + ' MB';
	}

	function revokeItemBlobUrl($item) {
		if (!$item || !$item.length) return;
		var url = $item.data('previewBlobUrl');
		if (url) {
			try { URL.revokeObjectURL(url); } catch (e) { /* ignore */ }
			$item.removeData('previewBlobUrl');
		}
	}

	function setPreviewOpenable($item, previewType, fullSrc) {
		var $wrap = $item.find('.upload-item-preview');
		$wrap.toggleClass('ew-preview-openable', !!fullSrc);
		$wrap.attr('data-preview-type', previewType || '');
		$wrap.attr('title', fullSrc ? 'Click to preview' : '');
		var $img = $item.find('img.image_preview');
		if (fullSrc) {
			$img.attr('data-full-src', fullSrc);
		} else {
			$img.removeAttr('data-full-src');
		}
		$item.data('previewType', previewType || '');
		$item.data('previewFullSrc', fullSrc || '');
	}

	function buildUploadItemHtml(fileNo, fileName, previewSrc, attachmentId) {
		fileName = fileName || 'Select a file';
		previewSrc = previewSrc || 'images/no_image.png';
		var imgClass = (previewSrc.indexOf('no_image') >= 0) ? ' doc-placeholder' : '';
		var idAttr = attachmentId ? ' id="' + attachmentId + '"' : '';
		return '<div class="upload-item file-group" id="file-no' + fileNo + '" data-file-no="' + fileNo + '">' +
			'<div class="upload-item-preview img_pre_div">' +
			'<img src="' + previewSrc + '" class="image_preview' + imgClass + '" id="image_preview' + fileNo + '" alt="">' +
			'</div>' +
			'<div class="upload-item-body">' +
			'<span class="upload-item-name">' + escapeUploadHtml(fileName) + '</span>' +
			'<span class="upload-item-sub">Ready to upload</span>' +
			'<input type="file" id="' + cfg.inputIdPrefix + fileNo + '" name="' + cfg.inputName + '" class="filestyle upload-file-input" data-id="' + fileNo + '" accept="' + cfg.accept + '">' +
			'</div>' +
			'<div class="upload-item-actions remov">' +
			'<button type="button" data-id="' + fileNo + '"' + idAttr + ' class="btn btn-link upload-item-remove remove-image" title="Remove"><i class="fa fa-times"></i></button>' +
			'</div>' +
			'</div>';
	}

	function assignFileToInput(input, file) {
		try {
			var dt = new DataTransfer();
			dt.items.add(file);
			input.files = dt.files;
			return true;
		} catch (e) {
			return false;
		}
	}

	function previewUploadFile(fileNo, file) {
		var $item = $('#file-no' + fileNo);
		var $img = $('#image_preview' + fileNo);
		revokeItemBlobUrl($item);
		setPreviewOpenable($item, '', '');

		if (isImageUploadFile(file)) {
			var reader = new FileReader();
			reader.onload = function (e) {
				var src = e.target.result;
				$img.attr('src', src).removeClass('doc-placeholder');
				setPreviewOpenable($item, 'image', src);
			};
			reader.readAsDataURL(file);
		} else if (isPdfUploadFile(file)) {
			var blobUrl = URL.createObjectURL(file);
			$item.data('previewBlobUrl', blobUrl);
			$img.attr('src', pdfThumbSrc).addClass('doc-placeholder');
			setPreviewOpenable($item, 'pdf', blobUrl);
		} else {
			$img.attr('src', 'images/no_image.png').addClass('doc-placeholder');
		}
		$item.find('.upload-item-name').text(file.name);
		$item.find('.upload-item-sub').text(formatUploadFileSize(file.size));
	}

	function openAttachmentPreviewModal(src, previewType, title) {
		if (!src) return;
		var $modal = $('#ew_attachment_preview_modal');
		if (!$modal.length) return;
		var $img = $('#ew_attachment_preview_img');
		var $frame = $('#ew_attachment_preview_frame');
		$('#ew_attachment_preview_title').text(title || 'Preview');
		$img.hide().attr('src', '');
		$frame.hide().attr('src', '');
		if (previewType === 'pdf') {
			$frame.attr('src', src).show();
		} else {
			$img.attr('src', src).show();
		}
		$modal.addClass('is-open').attr('aria-hidden', 'false');
		$('body').addClass('ew-attachment-preview-open');
	}

	function closeAttachmentPreviewModal() {
		var $modal = $('#ew_attachment_preview_modal');
		$modal.removeClass('is-open').attr('aria-hidden', 'true');
		$('#ew_attachment_preview_img').attr('src', '').hide();
		$('#ew_attachment_preview_frame').attr('src', '').hide();
		$('body').removeClass('ew-attachment-preview-open');
	}

	function addUploadItem(file, options) {
		options = options || {};
		var fileNo = getNextFileNo();
		var fileName = options.fileName || (file ? file.name : 'Select a file');
		var previewSrc = options.previewSrc || 'images/no_image.png';
		var attachmentId = options.attachmentId || '';
		$(cfg.container).append(buildUploadItemHtml(fileNo, fileName, previewSrc, attachmentId));
		var $input = $('#' + cfg.inputIdPrefix + fileNo);
		var $item = $('#file-no' + fileNo);
		if (file) {
			assignFileToInput($input[0], file);
			previewUploadFile(fileNo, file);
		} else if (options.previewType && options.previewSrc && options.previewSrc.indexOf('no_image') < 0) {
			setPreviewOpenable($item, options.previewType, options.previewSrc);
		}
		return fileNo;
	}

	function handleUploadFiles(fileList) {
		if (!fileList || !fileList.length) return;
		for (var i = 0; i < fileList.length; i++) {
			addUploadItem(fileList[i]);
		}
	}

	window.ewInitAttachmentUpload = function (options) {
		options = options || {};
		cfg = {
			dropzone: options.dropzone || '#upload_dropzone',
			dropzoneInput: options.dropzoneInput || '#upload_dropzone_input',
			container: options.container || '#upload_preview_list',
			addMoreBtn: options.addMoreBtn || '#add_more',
			inputName: options.inputName || 'file_receipt[]',
			inputIdPrefix: options.inputIdPrefix || 'file_receipt',
			accept: options.accept || 'image/*,.pdf,.doc,.docx,.xls,.xlsx'
		};

		var $uploadDropzone = $(cfg.dropzone);
		if (!$uploadDropzone.length) return;

		$uploadDropzone.off('.ewUpload');
		$(cfg.dropzoneInput).off('.ewUpload');
		$(cfg.addMoreBtn).off('.ewUpload');
		$(document).off('change.ewUpload', '.upload-file-input');
		$(document).off('click.ewUpload', '.remove-image');
		$(document).off('click.ewUpload', '.ew-preview-openable');
		$(document).off('click.ewUpload', '[data-ew-preview-close]');
		$(document).off('keydown.ewUploadPreview');

		$uploadDropzone.on('dragover.ewUpload dragenter.ewUpload', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).addClass('is-dragover');
		});
		$uploadDropzone.on('dragleave.ewUpload drop.ewUpload', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).removeClass('is-dragover');
		});
		$uploadDropzone.on('drop.ewUpload', function (e) {
			handleUploadFiles(e.originalEvent.dataTransfer.files);
		});
		$uploadDropzone.on('click.ewUpload', function (e) {
			if ($(e.target).closest('.remove-image, .upload-item').length) return;
			$(cfg.dropzoneInput).trigger('click');
		});
		$(cfg.dropzoneInput).on('change.ewUpload', function () {
			handleUploadFiles(this.files);
			this.value = '';
		});

		$(document).on('change.ewUpload', '.upload-file-input', function () {
			var id = $(this).attr('data-id');
			if (this.files && this.files[0]) {
				previewUploadFile(id, this.files[0]);
			}
		});

		$(document).on('click.ewUpload', '.ew-preview-openable', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $wrap = $(this);
			var $item = $wrap.closest('.upload-item');
			var previewType = $wrap.attr('data-preview-type') || $item.data('previewType') || 'image';
			var $img = $wrap.find('img.image_preview');
			var src = $item.data('previewFullSrc') || $img.attr('data-full-src') || $img.attr('src');
			if (!src || src.indexOf('no_image') >= 0) return;
			var title = $.trim($item.find('.upload-item-name').first().text()) || 'Preview';
			openAttachmentPreviewModal(src, previewType, title);
		});

		$(document).on('click.ewUpload', '[data-ew-preview-close]', function (e) {
			e.preventDefault();
			closeAttachmentPreviewModal();
		});

		$(document).on('keydown.ewUploadPreview', function (e) {
			if (e.key === 'Escape' && $('#ew_attachment_preview_modal').hasClass('is-open')) {
				closeAttachmentPreviewModal();
			}
		});

		$(document).on('click.ewUpload', '.remove-image', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(this);
			if (typeof cfg.onRemove === 'function') {
				cfg.onRemove($btn);
			}
			var id = $btn.attr('data-id');
			revokeItemBlobUrl($('#file-no' + id));
			$('#file-no' + id).remove();
		});

		$(cfg.addMoreBtn).on('click.ewUpload', function (evt) {
			evt.preventDefault();
			var fileNo = addUploadItem(null);
			$('#' + cfg.inputIdPrefix + fileNo).trigger('click');
		});

		window.ewAddUploadItem = addUploadItem;
	};
})(jQuery);
