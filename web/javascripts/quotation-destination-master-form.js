(function($) {
	'use strict';

	$(function() {
		if (!window.QUOTATION_DEST_MASTER_ONLY) {
			return;
		}

		function saveDestMaster($btn, forceReplace) {
			var maxRows = parseInt(window.QUOTATION_DEST_MASTER_MAX_ROWS, 10) || 200;
			var rowCount = $('#md_dest_tbody tr.md-row').length;
			if (rowCount > maxRows) {
				alert('Maximum ' + maxRows + ' destination rows allowed.');
				return;
			}
			if (typeof window.validateMdDestinationRows === 'function') {
				var destCheck = window.validateMdDestinationRows();
				if (!destCheck.ok) {
					$('#response').hide();
					return;
				}
			}
			$btn.prop('disabled', true);
			$('#response').hide();
			var payload = $('#dest_master_form').serialize();
			if (forceReplace) {
				payload += '&force_replace_master=1';
			}
			$.ajax({
				url: 'save_details.php',
				type: 'POST',
				dataType: 'json',
				data: payload,
				success: function(data) {
					$btn.prop('disabled', false);
					if (data && data.ok) {
						if (data.master_id) {
							$('#master_id').val(data.master_id);
						}
						if (typeof ewToast === 'function') {
							ewToast('Saved. Master rows stay when you use other quotations.', 'success');
						}
						return;
					}
					if (data && data.needs_confirm) {
						var msg = (data.message || 'This will remove some saved destination rows.')
							+ '\n\nClick OK only if you intentionally removed those rows.';
						if (window.confirm(msg)) {
							saveDestMaster($btn, true);
						}
						return;
					}
					$('#response .message').text((data && data.message) || 'Save failed.');
					$('#response').show();
				},
				error: function(xhr) {
					$btn.prop('disabled', false);
					var msg = 'Save failed.';
					try {
						var parsed = JSON.parse(xhr.responseText || '');
						if (parsed && parsed.message) {
							msg = parsed.message;
						}
					} catch (e) {
						var raw = $.trim(xhr.responseText || '');
						if (raw !== '' && raw.length < 400) {
							msg = raw;
						}
					}
					$('#response .message').text(msg);
					$('#response').show();
				}
			});
		}

		$(document).on('click', '.btn-save-dest-master', function() {
			saveDestMaster($(this));
		});
	});
})(jQuery);
