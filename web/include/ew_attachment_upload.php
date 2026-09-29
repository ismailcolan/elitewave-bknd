<?php
/**
 * Shared attachment panel markup (same as transactions.php booking footer).
 * Set $ew_upload_input_name / $ew_upload_id_prefix before include if not using defaults.
 */
$ew_upload_input_name = isset($ew_upload_input_name) ? $ew_upload_input_name : 'file_receipt[]';
$ew_upload_id_prefix = isset($ew_upload_id_prefix) ? $ew_upload_id_prefix : 'file_receipt';
$ew_upload_existing_items = isset($ew_upload_existing_items) ? $ew_upload_existing_items : array();
$ew_upload_readonly = !empty($ew_upload_readonly);
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
			$sub = $item['sub'] ?? 'Existing attachment';
			$attachment_id = isset($item['attachment_id']) ? (int) $item['attachment_id'] : 0;
			?>
			<div class="upload-item file-group" id="file-no<?php echo $k; ?>" data-file-no="<?php echo $k; ?>">
				<div class="upload-item-preview img_pre_div">
					<img src="<?php echo htmlspecialchars($preview); ?>"
						class="image_preview<?php echo $is_image ? '' : ' doc-placeholder'; ?>"
						id="image_preview<?php echo $k; ?>" alt="">
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
