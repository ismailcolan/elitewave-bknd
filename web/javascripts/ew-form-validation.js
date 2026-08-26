/**
 * EliteWave360 shared form validation + toast alerts (project-wide).
 * Auto-applies on masters, users, reports, transactions, and all other screens.
 */
(function($) {
	'use strict';

	var FIELD_LABELS = {
		grn_date: 'GCN.Date',
		mode_of_trasport: 'Transportation Mode',
		origin: 'Origin',
		destination: 'Destination',
		mode_of_consignment: 'Consignment Mode',
		consignor: 'Consignor',
		consignee: 'Consignee',
		party_invoice1: 'Party Invoice No',
		no_of_pkg1: 'No of Pkgs',
		type_of_pkg1: 'Type of Pkgs',
		charged1: 'Charged wt.(Kgs)'
	};

	window.ewRequiredFieldLabel = function(element) {
		var $el = $(element);
		var id = $el.attr('id') || '';
		if (FIELD_LABELS[id]) {
			return FIELD_LABELS[id];
		}
		var $formGroup = $el.closest('.form-group');
		if ($formGroup.length) {
			var labelText = $formGroup.find('> label.control-label, > label.filter-label, > label').first().clone();
			labelText.find('.req-star, .required-star, span').remove();
			labelText = labelText.text().replace(/[:\*]/g, '').trim();
			if (labelText) {
				return labelText;
			}
		}
		var $label = $('label[for="' + id + '"]').first();
		if ($label.length) {
			var forText = $label.clone();
			forText.find('.req-star, .required-star, span').remove();
			forText = forText.text().replace(/[:\*]/g, '').trim();
			if (forText) {
				return forText;
			}
		}
		var $td = $el.closest('td');
		if ($td.length) {
			var colIndex = $td.index();
			var header = $td.closest('table').find('thead th').eq(colIndex).text().trim();
			if (header) {
				return header;
			}
		}
		var placeholder = $el.attr('placeholder');
		if (placeholder) {
			return placeholder;
		}
		return 'This field';
	};

	window.ewClearFieldError = function($el) {
		if (!$el || !$el.length) {
			return;
		}
		$el.removeClass('error');
		var fieldId = $el.attr('id');
		if (fieldId) {
			$('label.error[for="' + fieldId + '"], label.field-inline-error[for="' + fieldId + '"]').remove();
		}
		$el.closest('td').find('label.error, label.field-inline-error').remove();
		$el.next('label.error, label.field-inline-error').remove();
		$el.closest('.date-input-inside').next('label.error, label.field-inline-error').remove();
	};

	window.ewShowInlineFieldError = function($target, message) {
		if (!$target || !$target.length) {
			return;
		}
		var targetId = $target.attr('id') || '';
		$target.addClass('error');
		var $existing = targetId
			? $('label.error[for="' + targetId + '"], label.field-inline-error[for="' + targetId + '"]')
			: $target.closest('td').find('label.error, label.field-inline-error');
		if ($existing.length) {
			$existing.first().text(message);
			$existing.slice(1).remove();
			return;
		}
		var $error = $('<label class="error field-inline-error"></label>').text(message);
		if (targetId) {
			$error.attr('for', targetId);
		}
		if ($target.closest('.date-input-inside').length) {
			$target.closest('.date-input-inside').after($error);
		} else if ($target.closest('td').length) {
			$target.closest('td').append($error);
		} else {
			$error.insertAfter($target);
		}
	};

	window.ewFormToast = function(message, type, duration) {
		message = $.trim(String(message || ''));
		if (!message) {
			return;
		}
		if (typeof window.ewToast === 'function') {
			var map = { danger: 'error', error: 'error', success: 'success', warning: 'warning', info: 'info' };
			window.ewToast(message, map[type] || type || 'success', duration || 5000);
		}
	};

	window.ewMarkValidationElement = function($el, isError) {
		var id = $el.attr('id');
		var $target = $el;
		if (id === 'consignor') {
			$target = $('#consignor_name');
		} else if (id === 'consignee') {
			$target = $('#consignee_name');
		}
		if (isError) {
			$target.addClass('error');
		} else {
			ewClearFieldError($target);
		}
	};

	function defaultErrorPlacement(error, element) {
		if (element.rules && element.rules().required) {
			error.text(ewRequiredFieldLabel(element) + ' is required');
		}
		var $el = $(element);
		var id = $el.attr('id');
		if (id === 'consignor') {
			error.attr('for', 'consignor_name');
			$('label.error[for="consignor_name"], label.field-inline-error[for="consignor_name"]').remove();
			error.insertAfter('#consignor_name');
			return;
		}
		if (id === 'consignee') {
			error.attr('for', 'consignee_name');
			$('label.error[for="consignee_name"], label.field-inline-error[for="consignee_name"]').remove();
			error.insertAfter('#consignee_name');
			return;
		}
		if ($el.closest('.date-input-inside').length) {
			$el.closest('.date-input-inside').next('label.error, label.field-inline-error').remove();
			$el.closest('.date-input-inside').after(error);
			return;
		}
		if ($el.closest('td').length) {
			$el.closest('td').find('label.error, label.field-inline-error').remove();
			$el.closest('td').append(error);
			return;
		}
		$el.next('label.error, label.field-inline-error').remove();
		error.insertAfter($el);
	}

	if ($.validator) {
		$.validator.setDefaults({
			errorElement: 'label',
			errorClass: 'error field-inline-error',
			errorPlacement: defaultErrorPlacement,
			highlight: function(element) {
				ewMarkValidationElement($(element), true);
			},
			unhighlight: function(element) {
				ewMarkValidationElement($(element), false);
			},
			invalidHandler: function(event, validator) {
				ewFormToast('Please fill all mandatory fields.', 'error', 5000);
				if (validator.errorList && validator.errorList.length) {
					var $first = $(validator.errorList[0].element);
					if ($first.attr('id') === 'consignor') {
						$first = $('#consignor_name');
					} else if ($first.attr('id') === 'consignee') {
						$first = $('#consignee_name');
					}
					if ($first.length && $first.offset()) {
						$('html, body').animate({
							scrollTop: Math.max(0, $first.offset().top - 120)
						}, 300);
						try { $first.focus(); } catch (e) {}
					}
				}
			}
		});
	}

	/** Turn every mandatory * into red .req-star across the page */
	window.ewNormalizeRequiredStars = function(container) {
		var $root = container ? $(container) : $(document);
		$root.find('label, .filter-label, .control-label, th, .heading').each(function() {
			var $label = $(this);
			if ($label.hasClass('error') || $label.hasClass('field-inline-error')) {
				return;
			}
			var html = $label.html();
			if (!html || html.indexOf('*') === -1) {
				return;
			}
			if ($label.find('.req-star, .required-star').length) {
				$label.find('span[style*="color:red"], span[style*="color: red"]').each(function() {
					var $span = $(this);
					if ($.trim($span.text()) === '*') {
						$span.removeAttr('style').addClass('req-star');
					}
				});
				return;
			}
			var updated = html
				.replace(/<span[^>]*style\s*=\s*["'][^"']*color\s*:\s*red[^"']*["'][^>]*>\s*\*\s*<\/span>/gi, '<span class="req-star">*</span>')
				.replace(/(>[^<]*?)(\*)/g, function(match, before, star) {
					return before + '<span class="req-star">*</span>';
				});
			if (updated !== html) {
				$label.html(updated);
			}
		});
	};

	/** Init jQuery Validate on every form that has required fields */
	window.initEwFormValidation = function(selector) {
		var sel = selector || 'form';
		$(sel).each(function() {
			var $form = $(this);
			if ($form.data('validator')) {
				return;
			}
			if ($form.attr('data-ew-skip-validate') === '1') {
				return;
			}
			var hasRequired = $form.find('[required]').length > 0;
			var force = $form.hasClass('ew-validated-form') || $form.attr('data-ew-validate') === '1';
			if (!hasRequired && !force) {
				return;
			}
			var opts = {};
			if ($form.attr('data-ew-ignore') === 'none' || $form.attr('id') === 'grn_details') {
				opts.ignore = ':disabled';
			}
			$form.validate(opts);
		});
	};

	/** Convert legacy #alert-container success/error banners into bottom toasts */
	window.ewBindAlertContainerToToast = function bindAlertContainerToToast() {
		var el = document.getElementById('alert-container');
		if (!el || el.getAttribute('data-ew-toast-bound') === '1') {
			return;
		}
		el.setAttribute('data-ew-toast-bound', '1');

		var lastMsg = '';
		var lastAt = 0;

		function flushToast() {
			var $box = $(el);
			if (!$box.hasClass('alert-success') && !$box.hasClass('alert-danger') && !$box.hasClass('alert-warning')) {
				return;
			}
			var msg = $.trim($('#alert-message').text() || '');
			if (!msg) {
				msg = $.trim($box.clone().children('.close').remove().end().text());
			}
			msg = msg.replace(/^Alert\s*!!!?\s*/i, '').replace(/\s+/g, ' ').trim();
			if (!msg) {
				return;
			}
			var now = Date.now();
			if (msg === lastMsg && (now - lastAt) < 1200) {
				$box.stop(true, true).hide().removeClass('alert-success alert-danger alert-warning');
				return;
			}
			lastMsg = msg;
			lastAt = now;
			var type = $box.hasClass('alert-success') ? 'success' : ($box.hasClass('alert-warning') ? 'warning' : 'error');
			ewFormToast(msg, type, 5000);
			$box.stop(true, true).hide().removeClass('alert-success alert-danger alert-warning');
			$('#alert-status, #alert-message').text('');
		}

		var observer = new MutationObserver(function() {
			setTimeout(flushToast, 30);
		});
		observer.observe(el, {
			attributes: true,
			attributeFilter: ['class', 'style'],
			childList: true,
			subtree: true,
			characterData: true
		});
	};

	/** Route native alert() to toaster for app messages */
	function bindNativeAlertToToast() {
		if (window.__ewAlertPatched) {
			return;
		}
		window.__ewAlertPatched = true;
		var nativeAlert = window.alert;
		window.alert = function(message) {
			var msg = $.trim(String(message == null ? '' : message));
			if (!msg) {
				return;
			}
			if (typeof window.ewFormToast === 'function') {
				var type = /success|saved|updated|deleted|booked/i.test(msg)
					? 'success'
					: (/fail|error|required|select|invalid|exist/i.test(msg) ? 'error' : 'warning');
				ewFormToast(msg, type, 5000);
				return;
			}
			nativeAlert.call(window, message);
		};
	}

	$(function() {
		ewNormalizeRequiredStars();
		initEwFormValidation();
		ewBindAlertContainerToToast();
		bindNativeAlertToToast();

		$(document).on('input change', 'form input, form select, form textarea', function() {
			var $el = $(this);
			if ($el.val() && String($el.val()).trim() !== '') {
				ewClearFieldError($el);
			}
		});

		// Re-apply when modal/content opens (masters, reports, status popups)
		$(document).on('shown.bs.modal', '.modal', function() {
			ewNormalizeRequiredStars(this);
			initEwFormValidation($(this).find('form'));
			ewBindAlertContainerToToast();
		});
	});
})(jQuery);
