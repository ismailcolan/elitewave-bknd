/* Shared two-click date range picker for dashboard_booking.php & dashboard_invoice.php */
(function (window, $) {
	'use strict';

	window.dbkInitDashboardDateRange = function (formSelector) {
		if (typeof $.fn.datepicker === 'undefined') {
			return;
		}
		var $form = $(formSelector);
		if (!$form.length) {
			return;
		}

		/** Civil calendar day key from a local Date (form values, picked range). */
		function dbkDayKeyLocal(d) {
			return d.getFullYear() * 10000 + (d.getMonth() + 1) * 100 + d.getDate();
		}

		function dbkParseDMY(s) {
			var p = String(s || '').split('-');
			if (p.length !== 3) {
				return null;
			}
			var d = new Date(parseInt(p[2], 10), parseInt(p[1], 10) - 1, parseInt(p[0], 10));
			return isNaN(d.getTime()) ? null : d;
		}

		function dbkParseDMYSlash(s) {
			var p = String(s || '').trim().split('/');
			if (p.length !== 3) {
				return null;
			}
			var d = new Date(parseInt(p[2], 10), parseInt(p[1], 10) - 1, parseInt(p[0], 10));
			return isNaN(d.getTime()) ? null : d;
		}

		function dbkParseDisplayRange(str) {
			var raw = String(str || '').trim();
			if (!raw) {
				return null;
			}
			var parts = raw.split(/\s*[–—\-]\s*/);
			if (parts.length !== 2) {
				return null;
			}
			var a = dbkParseDMYSlash(parts[0]);
			var b = dbkParseDMYSlash(parts[1]);
			if (!a || !b) {
				return null;
			}
			return dbkNormalizeRangePair(a, b);
		}

		function dbkFmtDMY(d) {
			if (!d) {
				return '';
			}
			var dd = ('0' + d.getDate()).slice(-2);
			var mm = ('0' + (d.getMonth() + 1)).slice(-2);
			return dd + '-' + mm + '-' + d.getFullYear();
		}

		function dbkFmtDisplay(d) {
			if (!d) {
				return '';
			}
			var dd = ('0' + d.getDate()).slice(-2);
			var mm = ('0' + (d.getMonth() + 1)).slice(-2);
			return dd + '/' + mm + '/' + d.getFullYear();
		}

		function dbkLocalMonthStart(y, m) {
			return new Date(y, m, 1);
		}

		var $rangeInput = $form.find('#dash_date_range');
		var $rangeField = $form.find('.dbk-date-range-field').first();
		var $rangeWrap = $form.find('.dbk-date-range-wrap').first();
		var $rangeClear = $form.find('#dash_date_clear');
		var $dropdown = $('#dbk_range_dropdown');
		var $calHost = $('#dbk_cal_main');
		if (!$rangeInput.length || !$calHost.length) {
			return;
		}

		var rangeStart = dbkParseDMY($form.find('#date_from').val());
		var rangeEnd = dbkParseDMY($form.find('#date_to').val());
		if (!rangeStart || !rangeEnd) {
			var fromDisplay = dbkParseDisplayRange($rangeInput.val());
			if (fromDisplay && fromDisplay.start && fromDisplay.end) {
				rangeStart = fromDisplay.start;
				rangeEnd = fromDisplay.end;
			}
		}
		var $rangeHint = null;
		var pickStart = null;
		var pickEnd = null;
		var pickHover = null;
		var dbkAwaitingEndClick = false;
		var dbkRangeOpen = false;

		function dbkNormalizeRangePair(start, end) {
			if (!start || !end) {
				return { start: start, end: end };
			}
			if (dbkDayKeyLocal(end) < dbkDayKeyLocal(start)) {
				return { start: end, end: start };
			}
			return { start: start, end: end };
		}

		function dbkNormalizeRange() {
			var n = dbkNormalizeRangePair(rangeStart, rangeEnd);
			rangeStart = n.start;
			rangeEnd = n.end;
		}

		dbkNormalizeRange();

		function dbkUtcFromLocal(localDate) {
			return new Date(Date.UTC(
				localDate.getFullYear(),
				localDate.getMonth(),
				localDate.getDate()
			));
		}

		function dbkGetLocalRangeEnds() {
			if (pickStart) {
				var pe = pickHover || pickEnd || pickStart;
				return dbkNormalizeRangePair(pickStart, pe);
			}
			if (rangeStart && rangeEnd) {
				return dbkNormalizeRangePair(rangeStart, rangeEnd);
			}
			return null;
		}

		function dbkEnsureRangeHint() {
			if (!$rangeHint || !$rangeHint.length) {
				$rangeHint = $('#dbk_range_hint');
				if (!$rangeHint.length) {
					$rangeHint = $('<div id="dbk_range_hint" class="dbk-range-hint" aria-live="polite"></div>');
					$dropdown.prepend($rangeHint);
				}
			}
		}

		function dbkUpdateRangeHint() {
			dbkEnsureRangeHint();
			if (!dbkRangeOpen) {
				$rangeHint.attr('hidden', 'hidden');
				return;
			}
			if (dbkAwaitingEndClick && pickStart) {
				$rangeHint.html(
					'Select end date · start <strong>' + dbkFmtDisplay(pickStart) + '</strong>'
				).removeAttr('hidden');
				return;
			}
			if (rangeStart && rangeEnd && !dbkAwaitingEndClick) {
				$rangeHint.attr('hidden', 'hidden');
				return;
			}
			$rangeHint.html('Select start date').removeAttr('hidden');
		}

		function dbkSyncRangeDisplay() {
			var hasFilter = !!(rangeStart && rangeEnd);
			if (hasFilter) {
				dbkNormalizeRange();
				$rangeInput.val(dbkFmtDisplay(rangeStart) + ' – ' + dbkFmtDisplay(rangeEnd));
				$form.find('#date_from').val(dbkFmtDMY(rangeStart));
				$form.find('#date_to').val(dbkFmtDMY(rangeEnd));
				$rangeField.removeClass('is-empty');
				$rangeWrap.addClass('has-selection');
				$rangeClear.removeAttr('hidden');
			} else {
				$rangeInput.val('');
				$rangeField.addClass('is-empty');
				$rangeWrap.removeClass('has-selection');
				$rangeClear.attr('hidden', 'hidden');
			}
			dbkUpdateRangeHint();
		}

		function dbkDateFromPickerCell($td) {
			var $pane = $td.closest('.dbk-cal-pane');
			var picker = $pane.data('datepicker');
			if (!picker || !$td.length || !$td.hasClass('day') || $td.hasClass('disabled')) {
				return null;
			}
			var day = parseInt($td.text(), 10) || 1;
			var year = picker.viewDate.getUTCFullYear();
			var month = picker.viewDate.getUTCMonth();
			if ($td.hasClass('old')) {
				if (month === 0) {
					month = 11;
					year -= 1;
				} else {
					month -= 1;
				}
			} else if ($td.hasClass('new')) {
				if (month === 11) {
					month = 0;
					year += 1;
				} else {
					month += 1;
				}
			}
			return new Date(year, month, day);
		}

		function dbkWrapPickerNoActive(dp) {
			if (!dp || dp._dbkNoActiveWrap) {
				return;
			}
			var proto = Object.getPrototypeOf(dp);
			if (!proto || typeof proto.getClassNames !== 'function') {
				return;
			}
			dp._dbkNoActiveWrap = true;
			var baseGetClassNames = proto.getClassNames;
			dp.getClassNames = function (date) {
				var cls = baseGetClassNames.call(this, date);
				return cls.filter(function (c) {
					return c !== 'active';
				});
			};
		}

		function dbkPaintRangeCells() {
			var $cells = $('#dbk_range_dropdown .ew-dbk-range-cal td.day.range, #dbk_range_dropdown .ew-dbk-range-cal td.day.selected');
			$cells.each(function () {
				var el = this;
				var isCap = el.classList.contains('selected');
				var fg = isCap ? '#ffffff' : '#111827';
				el.style.setProperty('color', fg, 'important');
				el.style.setProperty('-webkit-text-fill-color', fg, 'important');
				el.style.setProperty('opacity', '1', 'important');
			});
		}

		function dbkRefreshRangeHighlight() {
			var picker = $calHost.data('datepicker');
			if (!picker || typeof picker.setRange !== 'function') {
				dbkUpdateRangeHint();
				return;
			}
			var span = dbkGetLocalRangeEnds();
			if (span && span.start && span.end) {
				picker.setRange([
					dbkUtcFromLocal(span.start),
					dbkUtcFromLocal(span.end)
				]);
			} else {
				picker.setRange([]);
			}
			dbkPaintRangeCells();
			dbkUpdateRangeHint();
		}

		function dbkSetCalendarMonth($host, localMonthStart) {
			var picker = $host.data('datepicker');
			if (!picker) {
				return;
			}
			var y = localMonthStart.getFullYear();
			var m = localMonthStart.getMonth();
			/* Datepicker fill() uses UTC month/year on viewDate — local midnight would show previous month in IST. */
			picker.viewDate = new Date(Date.UTC(y, m, 1));
			picker.fill();
		}

		function dbkClearPickerActiveDate() {
			var picker = $calHost.data('datepicker');
			if (picker) {
				picker.date = new Date(NaN);
			}
		}

		function dbkPositionDropdown() {
			var el = $rangeField[0];
			if (!el) {
				return;
			}
			var rect = el.getBoundingClientRect();
			$dropdown.css({
				top: (rect.bottom + window.scrollY + 6) + 'px',
				left: (rect.left + window.scrollX) + 'px'
			});
		}

		function dbkResetPickState() {
			pickStart = null;
			pickEnd = null;
			pickHover = null;
			dbkAwaitingEndClick = false;
		}

		function dbkCloseRangePicker() {
			if (!dbkRangeOpen) {
				return;
			}
			dbkRangeOpen = false;
			dbkResetPickState();
			dbkRefreshRangeHighlight();
			$dropdown.attr('hidden', 'hidden').attr('aria-hidden', 'true');
			if ($rangeHint && $rangeHint.length) {
				$rangeHint.attr('hidden', 'hidden');
			}
		}

		function dbkOpenRangePicker() {
			if (dbkRangeOpen) {
				return;
			}
			dbkResetPickState();
			if (!rangeStart || !rangeEnd) {
				var parsed = dbkParseDisplayRange($rangeInput.val());
				if (parsed && parsed.start && parsed.end) {
					rangeStart = parsed.start;
					rangeEnd = parsed.end;
					dbkNormalizeRange();
				}
			}
			dbkRangeOpen = true;
			$dropdown.removeAttr('hidden').attr('aria-hidden', 'false');
			dbkPositionDropdown();
			var anchor = rangeStart || rangeEnd;
			if (!anchor) {
				var now = new Date();
				anchor = new Date(now.getFullYear(), now.getMonth(), now.getDate());
			}
			dbkClearPickerActiveDate();
			dbkSetCalendarMonth($calHost, dbkLocalMonthStart(anchor.getFullYear(), anchor.getMonth()));
			dbkUpdateRangeHint();
			dbkRefreshRangeHighlight();
		}

		function dbkSetPickHoverFromCell($td) {
			if (!dbkAwaitingEndClick || !pickStart || !$td || !$td.length) {
				return;
			}
			var d = dbkDateFromPickerCell($td);
			if (!d) {
				return;
			}
			if (pickHover && dbkDayKeyLocal(d) === dbkDayKeyLocal(pickHover)) {
				return;
			}
			pickHover = d;
			dbkRefreshRangeHighlight();
		}

		function dbkCommitRangeFilter() {
			if (!pickStart || !pickEnd) {
				return;
			}
			var n = dbkNormalizeRangePair(pickStart, pickEnd);
			rangeStart = n.start;
			rangeEnd = n.end;
			dbkResetPickState();
			dbkSyncRangeDisplay();
			dbkCloseRangePicker();
			$form.submit();
		}

		function dbkDedupeRangeCalendars() {
			var needsReset = false;
			$calHost.each(function () {
				var $pane = $(this);
				var tooMany = $pane.children('.datepicker').length > 1;
				var nested = $pane.find('.datepicker .datepicker').length > 0;
				if (tooMany || nested) {
					needsReset = true;
					if ($pane.data('datepicker')) {
						try {
							$pane.datepicker('destroy');
						} catch (ignore) {}
					}
					$pane.empty().removeData('datepicker');
				}
			});
			if (needsReset) {
				window.dbkRangePickerInited = false;
			}
		}

		function dbkInitRangeCalendars() {
			dbkDedupeRangeCalendars();
			if (window.dbkRangePickerInited) {
				dbkRefreshRangeHighlight();
				return;
			}
			window.dbkRangePickerInited = true;

			var dpOpts = {
				format: 'dd-mm-yyyy',
				todayHighlight: false,
				todayBtn: false,
				clearBtn: false,
				maxViewMode: 2
			};
			$calHost.datepicker(dpOpts);

			var dp = $calHost.data('datepicker');
			if (dp && dp.picker) {
				dp.picker.addClass('datepicker-dropdown ew-dbk-range-cal');
				dp.date = new Date(NaN);
				dbkWrapPickerNoActive(dp);
				if (!dp._dbkClickWrapped) {
					dp._dbkClickWrapped = true;
					dp._dbkOrigClick = dp.click;
					dp.click = function (e) {
						var $target = $(e.target).closest('span, td, th');
						if ($target.length && $target.is('.day')) {
							return;
						}
						return dp._dbkOrigClick.call(this, e);
					};
				}
				dp.picker.off('click.dbkRangePick');
				dp.picker.on('click.dbkRangePick', 'td.day:not(.disabled)', function (ev) {
					ev.preventDefault();
					ev.stopImmediatePropagation();
					var d = dbkDateFromPickerCell($(this));
					if (!d) {
						return;
					}
					if (!dbkAwaitingEndClick) {
						pickStart = d;
						pickEnd = null;
						pickHover = null;
						dbkAwaitingEndClick = true;
						dbkClearPickerActiveDate();
						dbkRefreshRangeHighlight();
						return;
					}
					pickHover = null;
					pickEnd = d;
					dbkCommitRangeFilter();
				});

				dp.picker.off('mouseenter.dbkRangeHover mouseleave.dbkRangeHover');
				dp.picker.on('mouseenter.dbkRangeHover', 'td.day:not(.disabled)', function () {
					dbkSetPickHoverFromCell($(this));
				});
				dp.picker.on('mouseleave.dbkRangeHover', function (ev) {
					if (!dbkAwaitingEndClick || !pickStart) {
						return;
					}
					var rel = ev.relatedTarget;
					if (rel && dp.picker[0].contains(rel)) {
						return;
					}
					pickHover = null;
					dbkRefreshRangeHighlight();
				});
			}

			$calHost.on('changeMonth changeYear', function () {
				dbkRefreshRangeHighlight();
			});

			dbkRefreshRangeHighlight();
		}

		$rangeField.on('click', function (ev) {
			ev.preventDefault();
			if (dbkRangeOpen) {
				dbkCloseRangePicker();
				return;
			}
			dbkOpenRangePicker();
		});

		$rangeClear.on('click', function (ev) {
			ev.preventDefault();
			ev.stopPropagation();
			dbkResetPickState();
			rangeStart = null;
			rangeEnd = null;
			$form.find('#date_from').val('');
			$form.find('#date_to').val('');
			$rangeInput.val('');
			$rangeField.addClass('is-empty');
			dbkCloseRangePicker();
			$form.submit();
		});

		$(window).on('resize scroll', function () {
			if (dbkRangeOpen) {
				dbkPositionDropdown();
			}
		});

		$(document).on('mousedown', function (ev) {
			if (!dbkRangeOpen) {
				return;
			}
			var $t = $(ev.target);
			if ($t.closest('#dbk_range_dropdown, .dbk-date-range-field, #dash_date_clear').length) {
				return;
			}
			dbkCloseRangePicker();
		});

		$(document).on('mousemove.dbkRangeHover', function (ev) {
			if (!dbkRangeOpen || !dbkAwaitingEndClick || !pickStart) {
				return;
			}
			var el = document.elementFromPoint(ev.clientX, ev.clientY);
			if (!el) {
				return;
			}
			var $td = $(el).closest('#dbk_range_dropdown .datepicker td.day:not(.disabled)');
			if ($td.length) {
				dbkSetPickHoverFromCell($td);
			}
		});

		dbkSyncRangeDisplay();

		if (typeof jQuery !== 'undefined') {
			jQuery(function () {
				dbkInitRangeCalendars();
				dbkDedupeRangeCalendars();
				dbkSyncRangeDisplay();
				dbkRefreshRangeHighlight();
			});
		} else {
			dbkInitRangeCalendars();
		}
	};
}(window, window.jQuery));
