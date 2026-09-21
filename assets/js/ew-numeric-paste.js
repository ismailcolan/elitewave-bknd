/**
 * Allow paste on numeric fields: strip non-digits (or keep one decimal if field uses decimal oninput).
 */
(function () {
	'use strict';

	function allowsDecimal(el) {
		var oninput = el.getAttribute('oninput') || '';
		return /0-9\\./.test(oninput) || /0-9\./.test(oninput);
	}

	function cleanText(text, decimal) {
		if (!text) {
			return '';
		}
		if (decimal) {
			var s = String(text).replace(/[^0-9.]/g, '');
			var parts = s.split('.');
			if (parts.length > 2) {
				s = parts.shift() + '.' + parts.join('');
			}
			return s;
		}
		return String(text).replace(/[^0-9]/g, '');
	}

	function applyMax(el, val) {
		var max = el.maxLength;
		if (max > 0 && val.length > max) {
			return val.slice(0, max);
		}
		return val;
	}

	window.ewNumericPaste = function (e, el) {
		if (!el) {
			return false;
		}
		var ev = e.originalEvent || e;
		if (ev && ev.preventDefault) {
			ev.preventDefault();
		}
		if (!ev || !ev.clipboardData) {
			return false;
		}
		var decimal = allowsDecimal(el);
		var cleaned = cleanText(ev.clipboardData.getData('text/plain'), decimal);
		var start = el.selectionStart;
		var end = el.selectionEnd;
		if (typeof start === 'number' && typeof end === 'number') {
			var merged = el.value.slice(0, start) + cleaned + el.value.slice(end);
			merged = cleanText(merged, decimal);
			el.value = applyMax(el, merged);
		} else {
			el.value = applyMax(el, cleaned);
		}
		try {
			el.dispatchEvent(new Event('input', { bubbles: true }));
		} catch (err) {
			if (window.jQuery) {
				window.jQuery(el).trigger('input');
			}
		}
		if (window.jQuery) {
			window.jQuery(el).trigger('change');
		}
		return false;
	};
})();
