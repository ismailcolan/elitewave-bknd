/**
 * EliteWave360 shared datepicker — calendar icon inside input.
 * Auto-upgrades legacy .input-group.date-picker markup on every page.
 */
(function($) {
	'use strict';

	function syncDatepickerBounds($input) {
		if (!$input || !$input.length) {
			return;
		}
		var dp = $input.data('datepicker');
		if (!dp) {
			return;
		}
		if ($input.data('ew-bounds-syncing')) {
			return;
		}
		$input.data('ew-bounds-syncing', true);

		var format = $input.attr('data-date-format') || 'dd-mm-yyyy';
		var startRaw = $input.attr('data-start-date');
		var endRaw = $input.attr('data-end-date');

		if ($input.attr('data-allow-future') === '1') {
			dp.o.endDate = Infinity;
		} else if (endRaw === 'today') {
			dp.o.endDate = new Date();
		} else if (endRaw) {
			var pe = parseEwBoundDate(endRaw, format);
			if (pe !== null) {
				dp.o.endDate = pe;
			}
		}

		if (startRaw) {
			var ps = parseEwBoundDate(startRaw, format);
			if (ps !== null) {
				dp.o.startDate = ps;
			}
		} else if ($input.attr('data-allow-future') === '1') {
			dp.o.startDate = -Infinity;
		}

		dp.update();
		dp.updateNavArrows();
		$input.removeData('ew-bounds-syncing');
	}

	function bindFutureDateHandlers($input) {
		if ($input.data('ew-future-bound')) {
			return;
		}
		$input.data('ew-future-bound', true);
	}

	function ewDatepickerKeepOpen($input, target) {
		if (!$input || !$input.length || !target) {
			return false;
		}
		var dp = $input.data('datepicker');
		if (!dp || !dp.picker) {
			return false;
		}
		var $t = $(target);
		var $picker = dp.picker;
		if ($picker.is(target) || $picker.has(target).length) {
			return true;
		}
		if ($input.is(target)) {
			return true;
		}
		if ($t.closest('.date-field-icon').length && $t.closest('.date-input-inside').find($input).length) {
			return true;
		}
		return false;
	}

	function dismissOpenEwDatepickers(target) {
		collectDateInputs($(document)).add($('input.party-invoice-date')).each(function() {
			var $input = $(this);
			var dp = $input.data('datepicker');
			if (!dp || !dp.picker || !dp.picker.is(':visible')) {
				return;
			}
			if (ewDatepickerKeepOpen($input, target)) {
				return;
			}
			try {
				$input.datepicker('hide');
			} catch (ignore) {}
		});
	}

	function parseEwBoundDate(str, format) {
		if (!str) {
			return null;
		}
		str = $.trim(String(str));
		if (str === 'today') {
			var t = new Date();
			t.setHours(0, 0, 0, 0);
			return t;
		}
		format = format || 'dd-mm-yyyy';
		if (format === 'dd-mm-yyyy') {
			var m = /^(\d{1,2})-(\d{1,2})-(\d{4})$/.exec(str);
			if (m) {
				var d = new Date(parseInt(m[3], 10), parseInt(m[2], 10) - 1, parseInt(m[1], 10));
				if (!isNaN(d.getTime())) {
					d.setHours(0, 0, 0, 0);
					return d;
				}
			}
		}
		var parsed = new Date(str);
		return isNaN(parsed.getTime()) ? null : parsed;
	}

	function getDatepickerOptions($input) {
		var format = $input.attr('data-date-format') || 'dd-mm-yyyy';
		var opts = {
			format: format,
			autoclose: true
		};
		if (format === 'mm-yyyy') {
			opts.minViewMode = 1;
			opts.startView = 1;
		}
		var endDate = $input.attr('data-end-date');
		var startDate = $input.attr('data-start-date');
		if (endDate === 'today') {
			opts.endDate = new Date();
		} else if (endDate) {
			var parsedEnd = parseEwBoundDate(endDate, format);
			opts.endDate = parsedEnd !== null ? parsedEnd : endDate;
		}
		if (startDate) {
			var parsedStart = parseEwBoundDate(startDate, format);
			opts.startDate = parsedStart !== null ? parsedStart : startDate;
		}
		if ($input.closest('.modal, .ew-v2-modal-backdrop, .ew-v2-modal').length) {
			opts.container = 'body';
		}
		if ($input.attr('data-allow-future') === '1') {
			opts.endDate = Infinity;
			if (!startDate) {
				opts.startDate = -Infinity;
			}
		}
		return opts;
	}

	function isMonthFormat($input) {
		return ($input.attr('data-date-format') || '') === 'mm-yyyy';
	}

	function fixMonthPickerGrid($picker) {
		if (!$picker || !$picker.length) {
			return;
		}
		$picker.addClass('ew-month-picker');

		var $cell = $picker.find('.datepicker-months tbody tr td').first();
		if (!$cell.length) {
			return;
		}

		var $spans = $cell.find('span.month');
		if (!$spans.length) {
			return;
		}

		var $grid = $cell.children('.ew-month-grid');
		if (!$grid.length) {
			$grid = $('<div class="ew-month-grid"></div>');
			$cell.empty().append($grid);
		}

		$spans.detach().appendTo($grid);
	}

	function refreshMonthPicker($input) {
		if (!isMonthFormat($input)) {
			return;
		}
		var dp = $input.data('datepicker');
		if (!dp || !dp.picker) {
			return;
		}
		fixMonthPickerGrid(dp.picker);
	}

	function repositionDropdown($input) {
		var dp = $input.data('datepicker');
		if (!dp || !dp.picker || dp.isInline || !dp.picker.is(':visible')) {
			return;
		}
		var el = $input[0];
		if (!el || !el.getBoundingClientRect) {
			return;
		}
		var rect = el.getBoundingClientRect();
		var $picker = dp.picker;
		var pickerH = $picker.outerHeight() || 280;
		var pickerW = $picker.outerWidth() || 280;
		var gap = 6;
		var margin = 8;
		var spaceBelow = window.innerHeight - rect.bottom - margin;
		var top;
		if (spaceBelow >= pickerH || spaceBelow >= rect.top - margin) {
			top = rect.bottom + gap;
		} else {
			top = Math.max(margin, rect.top - pickerH - gap);
		}
		var left = rect.left;
		if (left + pickerW > window.innerWidth - margin) {
			left = window.innerWidth - pickerW - margin;
		}
		if (left < margin) {
			left = margin;
		}
		$picker.css({
			position: 'fixed',
			top: Math.round(top) + 'px',
			left: Math.round(left) + 'px',
			zIndex: 10160
		});
	}

	function bindDayPickerHandlers($input) {
		if (isMonthFormat($input) || $input.data('ew-day-bound')) {
			return;
		}
		$input.on('show.ewDayPicker', function() {
			var dp = $input.data('datepicker');
			if (dp && dp.picker) {
				dp.picker.removeClass('ew-month-picker');
			}
			if ($input.closest('.ew-v2-modal-backdrop, .modal').length) {
				window.setTimeout(function() {
					repositionDropdown($input);
				}, 0);
			}
		});
		$input.data('ew-day-bound', true);
	}

	function bindMonthPickerHandlers($input) {
		if (!isMonthFormat($input)) {
			return;
		}
		if ($input.data('ew-month-bound')) {
			return;
		}

		var events = 'show.ewMonthPicker changeYear.ewMonthPicker changeMonth.ewMonthPicker';
		$input.on(events, function() {
			window.setTimeout(function() {
				refreshMonthPicker($input);
			}, 0);
		});

		$input.on('hide.ewMonthPicker', function() {
			var dp = $input.data('datepicker');
			if (dp && dp.picker) {
				dp.picker.removeClass('ew-month-picker');
			}
		});

		$input.data('ew-month-bound', true);
	}

	function initOne($input) {
		if (!$input || !$input.length) {
			return;
		}
		bindMonthPickerHandlers($input);
		bindDayPickerHandlers($input);
		bindFutureDateHandlers($input);
		if ($input.data('datepicker')) {
			syncDatepickerBounds($input);
			return;
		}
		$input.datepicker(getDatepickerOptions($input));
		syncDatepickerBounds($input);
	}

	window.ewSetDatepickerStartDate = function($input, dateStr) {
		if (!$input || !$input.length) {
			return;
		}
		$input.attr('data-start-date', dateStr || '');
		if (!$input.data('datepicker')) {
			initOne($input);
		} else {
			syncDatepickerBounds($input);
		}
	};

	function wrapPlainDateInput($input) {
		if (!$input.length || $input.closest('.date-input-inside').length) {
			return $input;
		}
		$input.addClass('ew-date-field');
		if (!$input.attr('data-ew-datepicker')) {
			$input.attr('data-ew-datepicker', '1');
		}
		if (!$input.attr('data-date-format')) {
			$input.attr('data-date-format', 'dd-mm-yyyy');
		}
		var $wrap = $('<div class="date-input-inside ew-date-upgraded"></div>');
		$input.wrap($wrap);
		$input.parent().append('<i class="fa fa-calendar date-field-icon" aria-hidden="true"></i>');
		return $input;
	}

	function upgradeLegacyDatePickers($root) {
		$root.find('.input-group.date-picker, .input-group.date.date-picker').each(function() {
			var $group = $(this);
			if ($group.attr('data-ew-skip-upgrade') === '1') {
				return;
			}
			if ($group.hasClass('ew-date-upgraded') || $group.hasClass('date-input-inside')) {
				return;
			}
			var $input = $group.find('input.form-control').first();
			if (!$input.length) {
				return;
			}

			$group.find('.input-group-addon').remove();

			var format = $group.attr('data-date-format') || $group.data('dateFormat');
			if (format && !$input.attr('data-date-format')) {
				$input.attr('data-date-format', format);
			}
			$input.addClass('ew-date-field');
			if (!$input.attr('data-ew-datepicker')) {
				$input.attr('data-ew-datepicker', '1');
			}

			$group.removeClass('input-group date date-picker datepicker table-height daily monthly yearly cals_csss')
				.addClass('date-input-inside ew-date-upgraded');

			if (!$group.find('.date-field-icon').length) {
				$group.append('<i class="fa fa-calendar date-field-icon" aria-hidden="true"></i>');
			}
		});
	}

	function collectDateInputs($root) {
		var $inputs = $root.find('.date-input-inside input.ew-date-field, .date-input-inside input[data-ew-datepicker], input.ew-date-field[data-ew-datepicker]');
		$root.find('input.party-invoice-date').each(function() {
			wrapPlainDateInput($(this));
		});
		return $inputs.add($root.find('.date-input-inside input.ew-date-field, .date-input-inside input[data-ew-datepicker], input.party-invoice-date'));
	}

	window.initEwDatepickers = function(container) {
		var $root = container ? $(container) : $(document);
		upgradeLegacyDatePickers($root);
		collectDateInputs($root).each(function() {
			initOne($(this));
		});
	};

	function repositionOpenModalDatepickers() {
		$('.ew-v2-modal-backdrop.open input[data-ew-datepicker], .modal.in input[data-ew-datepicker]').each(function() {
			repositionDropdown($(this));
		});
	}

	$(window).on('resize.ewDatepickerModal', repositionOpenModalDatepickers);
	$(document).on('scroll.ewDatepickerModal', '.ew-v2-modal-backdrop.open .ew-v2-modal-body, .modal.in', repositionOpenModalDatepickers);

	$(document).on('click', '.date-input-inside .date-field-icon', function(e) {
		e.preventDefault();
		var $input = $(this).closest('.date-input-inside').find('input').first();
		if (!$input.length) {
			return;
		}
		if (!$input.data('datepicker')) {
			initOne($input);
		}
		bindMonthPickerHandlers($input);
		bindDayPickerHandlers($input);
		$input.datepicker('show');
		if (isMonthFormat($input)) {
			window.setTimeout(function() {
				refreshMonthPicker($input);
			}, 0);
		} else if ($input.closest('.ew-v2-modal-backdrop, .modal').length) {
			window.setTimeout(function() {
				repositionDropdown($input);
			}, 0);
		}
	});

	// Capture phase: modal panels stopPropagation on bubble, which blocks
	// bootstrap-datepicker's document mousedown close handler.
	document.addEventListener('mousedown', function(e) {
		dismissOpenEwDatepickers(e.target);
	}, true);

	$(function() {
		initEwDatepickers();
	});
})(jQuery);
