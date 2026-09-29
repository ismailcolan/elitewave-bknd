/**
 * Shared attachment upload UI (transactions.php booking panel + receipt).
 * Call ewInitAttachmentUpload() after DOM ready.
 */
(function ($) {
	'use strict';

	var cfg = {};

	function getNextFileNo() {
		var last = $(cfg.container).find('.file-group:last').data('file-no');
		return isNaN(last) || !last ? 1 : (parseInt(last, 10) + 1);
	}

	function isImageUploadFile(file) {
		return file && file.type && file.type.indexOf('image/') === 0;
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
		if (isImageUploadFile(file)) {
			var reader = new FileReader();
			reader.onload = function (e) {
				$img.attr('src', e.target.result).removeClass('doc-placeholder');
			};
			reader.readAsDataURL(file);
		} else {
			$img.attr('src', 'images/no_image.png').addClass('doc-placeholder');
		}
		$item.find('.upload-item-name').text(file.name);
		$item.find('.upload-item-sub').text(formatUploadFileSize(file.size));
	}

	function addUploadItem(file, options) {
		options = options || {};
		var fileNo = getNextFileNo();
		var fileName = options.fileName || (file ? file.name : 'Select a file');
		var previewSrc = options.previewSrc || 'images/no_image.png';
		var attachmentId = options.attachmentId || '';
		$(cfg.container).append(buildUploadItemHtml(fileNo, fileName, previewSrc, attachmentId));
		var $input = $('#' + cfg.inputIdPrefix + fileNo);
		if (file) {
			assignFileToInput($input[0], file);
			previewUploadFile(fileNo, file);
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

		$(document).on('click.ewUpload', '.remove-image', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(this);
			if (typeof cfg.onRemove === 'function') {
				cfg.onRemove($btn);
			}
			var id = $btn.attr('data-id');
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
