/**
 * EliteWave360 shared datepicker — calendar icon inside input.
 * Auto-upgrades legacy .input-group.date-picker markup on every page.
 */
(function($) {
	'use strict';

	function getDatepickerOptions($input) {
		var opts = {
			format: $input.attr('data-date-format') || 'dd-mm-yyyy',
			autoclose: true
		};
		var endDate = $input.attr('data-end-date');
		var startDate = $input.attr('data-start-date');
		if (endDate === 'today') {
			opts.endDate = new Date();
		} else if (endDate) {
			opts.endDate = endDate;
		}
		if (startDate) {
			opts.startDate = startDate;
		}
		return opts;
	}

	function initOne($input) {
		if (!$input || !$input.length || $input.data('datepicker')) {
			return;
		}
		$input.datepicker(getDatepickerOptions($input));
	}

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

	$(document).on('click', '.date-input-inside .date-field-icon', function(e) {
		e.preventDefault();
		var $input = $(this).closest('.date-input-inside').find('input').first();
		if (!$input.length) {
			return;
		}
		if (!$input.data('datepicker')) {
			initOne($input);
		}
		$input.datepicker('show');
	});

	$(function() {
		initEwDatepickers();
	});
})(jQuery);
