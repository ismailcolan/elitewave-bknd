(function ($) {
	'use strict';

	function showLockedToast($el) {
		var msg = ($el.data('message') || 'Sorry, this record is already in use and cannot be edited.').toString();
		if (typeof window.ewFormToast === 'function') {
			window.ewFormToast(msg, 'warning', 5000);
		} else if (typeof window.ewToast === 'function') {
			window.ewToast(msg, 'warning');
		} else {
			window.alert(msg);
		}
	}

	$(document).on('click', '.ew-master-edit-locked', function (e) {
		e.preventDefault();
		e.stopPropagation();
		showLockedToast($(this));
	});

	$(document).on('keydown', '.ew-master-edit-locked', function (e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			showLockedToast($(this));
		}
	});
})(jQuery);
