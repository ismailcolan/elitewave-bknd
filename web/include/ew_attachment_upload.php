<?php
/**
 * Shared attachment panel markup (same as transactions.php booking footer).
 * Set $ew_upload_input_name / $ew_upload_id_prefix before include if not using defaults.
 */
$ew_upload_input_name = isset($ew_upload_input_name) ? $ew_upload_input_name : 'file_receipt[]';
$ew_upload_id_prefix = isset($ew_upload_id_prefix) ? $ew_upload_id_prefix : 'file_receipt';
$ew_upload_existing_items = isset($ew_upload_existing_items) ? $ew_upload_existing_items : array();
$ew_upload_readonly = !empty($ew_upload_readonly);
if (empty($GLOBALS['ew_upload_pdf_thumb_src'])) {
	$GLOBALS['ew_upload_pdf_thumb_src'] = 'data:image/svg+xml,' . rawurlencode(
		'<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
		. '<rect width="64" height="64" rx="8" fill="#fee2e2"/>'
		. '<text x="32" y="38" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" font-weight="700" fill="#b91c1c">PDF</text>'
		. '</svg>'
	);
}
$ew_upload_pdf_thumb_src = $GLOBALS['ew_upload_pdf_thumb_src'];
?>
<div class="booking-panel upload-panel ew-attachment-upload-panel">
	<h3 class="booking-panel-title">Attachments</h3>
	<?php if (!$ew_upload_readonly) { ?>
	<p class="booking-panel-hint">Drag &amp; drop or click to upload</p>
	<div class="upload-dropzone" id="upload_dropzone">
		<div class="upload-dropzone-inner">
			<i class="fa fa-cloud-upload"></i>
			<span>Drop files or <strong>browse</strong></span>
			<small>Images, PDF &amp; documents</small>
		</div>
		<input type="file" id="upload_dropzone_input" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" class="upload-file-input">
	</div>
	<?php } ?>
	<div class="upload-preview-list file-container" id="upload_preview_list">
		<?php
		$k = 1;
		foreach ($ew_upload_existing_items as $item) {
			$attach_file = $item['name'] ?? '';
			$attach_ext = strtolower(pathinfo($attach_file, PATHINFO_EXTENSION));
			$is_image = in_array($attach_ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true);
			$preview = $item['preview_src'] ?? ($is_image ? $attach_file : 'images/no_image.png');
			$preview_type = $item['preview_type'] ?? ($is_image ? 'image' : ($attach_ext === 'pdf' ? 'pdf' : ''));
			$preview_openable = ($preview_type === 'image' || $preview_type === 'pdf')
				&& strpos($preview, 'no_image') === false;
			$thumb_src = ($preview_type === 'pdf' && $preview_openable) ? $ew_upload_pdf_thumb_src : $preview;
			$full_src = $preview_openable ? $preview : '';
			$sub = $item['sub'] ?? 'Existing attachment';
			$attachment_id = isset($item['attachment_id']) ? (int) $item['attachment_id'] : 0;
			?>
			<div class="upload-item file-group" id="file-no<?php echo $k; ?>" data-file-no="<?php echo $k; ?>">
				<div class="upload-item-preview img_pre_div<?php echo $preview_openable ? ' ew-preview-openable' : ''; ?>"
					<?php echo $preview_openable ? ' data-preview-type="' . htmlspecialchars($preview_type, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
					<?php echo $preview_openable ? ' title="Click to preview"' : ''; ?>>
					<img src="<?php echo htmlspecialchars($thumb_src); ?>"
						class="image_preview<?php echo ($is_image && $preview_openable) ? '' : ' doc-placeholder'; ?>"
						id="image_preview<?php echo $k; ?>" alt=""
						<?php echo $full_src !== '' ? ' data-full-src="' . htmlspecialchars($full_src, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
				</div>
				<div class="upload-item-body">
					<span class="upload-item-name"><?php echo htmlspecialchars($attach_file); ?></span>
					<span class="upload-item-sub"><?php echo htmlspecialchars($sub); ?></span>
					<?php if (!empty($item['readonly'])) { ?>
						<?php if (!empty($item['download_url'])) { ?>
							<a href="<?php echo htmlspecialchars($item['download_url']); ?>" class="upload-item-sub">Download</a>
						<?php } ?>
					<?php } else { ?>
						<input type="file" id="<?php echo htmlspecialchars($ew_upload_id_prefix . $k); ?>" name="<?php echo htmlspecialchars($ew_upload_input_name); ?>" class="filestyle upload-file-input" data-id="<?php echo $k; ?>" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
					<?php } ?>
				</div>
				<?php if (empty($item['readonly'])) { ?>
				<div class="upload-item-actions remov">
					<button type="button" data-id="<?php echo $k; ?>"<?php echo $attachment_id ? ' id="' . $attachment_id . '"' : ''; ?> class="btn btn-link upload-item-remove remove-image" title="Remove"><i class="fa fa-times"></i></button>
				</div>
				<?php } ?>
			</div>
			<?php
			$k++;
		}
		?>
	</div>
	<?php if (!$ew_upload_readonly) { ?>
	<button id="add_more" type="button" class="btn btn-default btn-sm upload-add-btn"><i class="fa fa-plus"></i> Add file</button>
	<?php } ?>
</div>
<?php
if (empty($GLOBALS['ew_attachment_preview_modal_included'])) {
	$GLOBALS['ew_attachment_preview_modal_included'] = true;
	?>
<div id="ew_attachment_preview_modal" class="ew-attachment-preview-modal" aria-hidden="true">
	<div class="ew-attachment-preview-backdrop" data-ew-preview-close="1"></div>
	<div class="ew-attachment-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="ew_attachment_preview_title">
		<div class="ew-attachment-preview-head">
			<span id="ew_attachment_preview_title" class="ew-attachment-preview-title">Preview</span>
			<button type="button" class="ew-attachment-preview-close" data-ew-preview-close="1" aria-label="Close">&times;</button>
		</div>
		<div class="ew-attachment-preview-body">
			<img id="ew_attachment_preview_img" class="ew-attachment-preview-img" alt="" hidden>
			<iframe id="ew_attachment_preview_frame" class="ew-attachment-preview-frame" title="Document preview" hidden></iframe>
		</div>
	</div>
</div>
	<?php
}
?>
