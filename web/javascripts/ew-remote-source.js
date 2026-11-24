/**
 * Remote searchable dropdown for large train / flight lists on quotation screens.
 *
 * Markup: <select data-remote-source="train|flight"> holding only the placeholder
 * (+ the currently selected option). Clicking the select opens a small search popup:
 *   - first 10 records are loaded on open
 *   - typing searches the whole table on the server (quotation_transport_search.php)
 * Picking a result replaces the select's option with it (value, text, data-hours) and
 * triggers "change", so existing code using .val() / option:selected keeps working.
 */
(function($) {
	'use strict';
	if (!$ || window.ewRemoteSourceLoaded) {
		return;
	}
	window.ewRemoteSourceLoaded = true;

	var ENDPOINT = 'quotation_transport_search.php';
	var LIMIT = 10;
	var cache = {};
	var $pop = null;
	var $search = null;
	var $list = null;
	var activeSel = null;
	var timer = null;
	var xhr = null;
	var items = [];
	var hi = -1;

	function injectCss() {
		if (document.getElementById('ew-remote-source-css')) {
			return;
		}
		var css = ''
			+ '.ew-rs-pop{position:fixed;z-index:100050;background:#fff;border:1px solid #cbd5e1;border-radius:8px;'
			+ 'box-shadow:0 10px 28px rgba(15,23,42,.18);padding:8px;box-sizing:border-box;}'
			+ '.ew-rs-pop input{width:100%;box-sizing:border-box;height:34px;border:1px solid #cbd5e1;border-radius:6px;padding:0 10px;font-size:13px;outline:none;}'
			+ '.ew-rs-pop input:focus{border-color:#2563eb;}'
			+ '.ew-rs-list{list-style:none;margin:6px 0 0;padding:0;max-height:260px;overflow-y:auto;overscroll-behavior:contain;}'
			+ '.ew-rs-list li{padding:7px 10px;font-size:13px;border-radius:5px;cursor:pointer;color:#1e293b;}'
			+ '.ew-rs-list li.is-hi{background:#e8effd;}'
			+ '.ew-rs-list li.is-sel{font-weight:600;}'
			+ '.ew-rs-list li.ew-rs-msg{cursor:default;color:#64748b;background:none;}'
			+ '.ew-rs-hint{font-size:11px;color:#94a3b8;padding:4px 2px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}'
			+ 'select[data-remote-source]{cursor:pointer;}';
		var st = document.createElement('style');
		st.id = 'ew-remote-source-css';
		st.appendChild(document.createTextNode(css));
		document.head.appendChild(st);
	}

	function ensurePop() {
		if ($pop) {
			return;
		}
		injectCss();
		$pop = $('<div class="ew-rs-pop" style="display:none;"></div>');
		$search = $('<input type="text" autocomplete="off" placeholder="Type to search…" />');
		$list = $('<ul class="ew-rs-list"></ul>');
		$pop.append($search, $list, '<div class="ew-rs-hint">Top ' + LIMIT + ' shown — type to search all</div>');
		$('body').append($pop);

		$search.on('input', function() {
			clearTimeout(timer);
			var term = $.trim($search.val());
			timer = setTimeout(function() {
				load(term);
			}, 250);
		});
		$search.on('keydown', function(e) {
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				highlight(Math.min(hi + 1, items.length - 1));
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				highlight(Math.max(hi - 1, 0));
			} else if (e.key === 'Enter') {
				e.preventDefault();
				if (hi >= 0 && items[hi]) {
					pick(items[hi]);
				}
			} else if (e.key === 'Escape' || e.key === 'Tab') {
				close(true);
			}
		});
		$list.on('mousedown', 'li[data-idx]', function(e) {
			e.preventDefault();
			pick(items[parseInt($(this).attr('data-idx'), 10)]);
		});
		$list.on('mousemove', 'li[data-idx]', function() {
			highlight(parseInt($(this).attr('data-idx'), 10), true);
		});
		$(document).on('mousedown.ewrs', function(e) {
			if (!activeSel) {
				return;
			}
			if ($(e.target).closest('.ew-rs-pop').length || e.target === activeSel) {
				return;
			}
			close(false);
		});
		$(window).on('resize.ewrs', function() {
			close(false);
		});
		window.addEventListener('scroll', function(e) {
			if (activeSel && !(e.target && e.target.nodeType === 1 && $(e.target).closest('.ew-rs-pop').length)) {
				position();
			}
		}, true);
	}

	/** True when the select is on screen and not hidden under a sticky header / overlay. */
	function selectIsReachable(sel, r) {
		if (r.bottom <= 0 || r.top >= window.innerHeight || r.width === 0) {
			return false;
		}
		var x = r.left + Math.min(r.width / 2, 40);
		var y = r.top + r.height / 2;
		if (y < 0 || y > window.innerHeight) {
			return false;
		}
		var el = document.elementFromPoint(x, y);
		return !!el && ($(el).closest('.ew-rs-pop').length > 0 || el === sel || sel.contains(el) || $(el).closest('td, .ew-field').is($(sel).closest('td, .ew-field')));
	}

	function position() {
		if (!activeSel || !$pop) {
			return;
		}
		var $s = $(activeSel);
		var r = activeSel.getBoundingClientRect();
		if (!$s.is(':visible') || !selectIsReachable(activeSel, r)) {
			close(false);
			return;
		}
		var vw = document.documentElement.clientWidth;
		var vh = window.innerHeight;
		var w = Math.max(r.width, 300);
		var left = Math.max(8, Math.min(r.left, vw - w - 8));
		$pop.css({ left: left, width: w, top: 0, bottom: 'auto' });
		var ph = $pop.outerHeight();
		var below = vh - r.bottom - 8;
		var above = r.top - 8;
		var top = (below < ph && above > below) ? Math.max(8, r.top - ph - 2) : r.bottom + 2;
		$pop.css({ top: top });
	}

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function renderMsg(msg) {
		items = [];
		hi = -1;
		$list.html('<li class="ew-rs-msg">' + esc(msg) + '</li>');
		position();
	}

	function render(rows) {
		items = rows || [];
		hi = -1;
		if (!items.length) {
			renderMsg('No matches found');
			return;
		}
		var cur = activeSel ? String($(activeSel).val() || '') : '';
		var html = '';
		for (var i = 0; i < items.length; i++) {
			var sel = String(items[i].id) === cur ? ' is-sel' : '';
			html += '<li data-idx="' + i + '" class="' + sel + '">' + esc(items[i].text) + '</li>';
		}
		$list.html(html);
		highlight(0);
		position();
	}

	function highlight(i, noScroll) {
		if (!items.length) {
			return;
		}
		hi = i;
		var $li = $list.children('li[data-idx]').removeClass('is-hi').eq(i).addClass('is-hi');
		if (!noScroll && $li.length) {
			var li = $li[0];
			var box = $list[0];
			if (li.offsetTop < box.scrollTop) {
				box.scrollTop = li.offsetTop;
			} else if (li.offsetTop + li.offsetHeight > box.scrollTop + box.clientHeight) {
				box.scrollTop = li.offsetTop + li.offsetHeight - box.clientHeight;
			}
		}
	}

	function isLocal(sel) {
		return $(sel).attr('data-search-local') === '1';
	}

	function localRows(sel, term) {
		var q = String(term || '').toLowerCase();
		var rows = [];
		var cap = q === '' ? LIMIT : 30;
		$(sel).find('option').each(function() {
			if (this.value === '') {
				return;
			}
			var text = $(this).text();
			if (q !== '' && text.toLowerCase().indexOf(q) === -1) {
				return;
			}
			rows.push({ id: this.value, text: text, hours: $(this).attr('data-hours') || '' });
			if (rows.length >= cap) {
				return false;
			}
		});
		return rows;
	}

	function load(term) {
		if (!activeSel) {
			return;
		}
		if (isLocal(activeSel)) {
			render(localRows(activeSel, term));
			return;
		}
		var type = $(activeSel).attr('data-remote-source');
		var key = type + '|' + term.toLowerCase();
		if (cache[key]) {
			render(cache[key]);
			return;
		}
		if (xhr) {
			xhr.abort();
		}
		renderMsg('Loading…');
		xhr = $.ajax({
			url: ENDPOINT,
			data: { type: type, q: term, limit: LIMIT },
			dataType: 'json',
			cache: false
		}).done(function(res) {
			var rows = (res && res.results) || [];
			cache[key] = rows;
			if (activeSel && $(activeSel).attr('data-remote-source') === type && $.trim($search.val()) === term) {
				render(rows);
			}
		}).fail(function(x, status) {
			if (status !== 'abort') {
				renderMsg('Could not load results');
			}
		});
	}

	var suppressSelectUntil = 0;

	function pick(row) {
		if (!activeSel || !row) {
			return;
		}
		var $s = $(activeSel);
		if (!isLocal(activeSel)) {
			$s.find('option').filter(function() {
				return this.value !== '';
			}).remove();
			var hrs = row.hours || '';
			$('<option></option>')
				.attr('value', row.id)
				.attr('data-hours', hrs)
				.attr('data-journey-hours', hrs)
				.text(row.text)
				.appendTo($s);
		}
		$s.val(String(row.id));
		// Do not focus the select again — that opens the browser menu and clears the choice.
		suppressSelectUntil = Date.now() + 400;
		close(false);
		$s.trigger('change');
	}

	function open(sel) {
		ensurePop();
		if (activeSel === sel) {
			return;
		}
		activeSel = sel;
		$search.val('');
		$pop.show();
		position();
		load('');
		setTimeout(function() {
			$search.trigger('focus');
		}, 0);
	}

	function close(refocus) {
		var sel = activeSel;
		activeSel = null;
		if (xhr) {
			xhr.abort();
			xhr = null;
		}
		clearTimeout(timer);
		if ($pop) {
			$pop.hide();
		}
		if (refocus && sel) {
			try {
				sel.focus();
			} catch (e) {}
		}
	}

	function blockNativeSelect(e) {
		e.preventDefault();
		e.stopPropagation();
	}

	// Replace the native dropdown with the search popup.
	// Mouse must not focus the select, or the browser opens its own list on top.
	$(document).on('mousedown', 'select[data-remote-source]', function(e) {
		if (this.disabled) {
			return;
		}
		blockNativeSelect(e);
		if (Date.now() < suppressSelectUntil) {
			return;
		}
		if (activeSel === this) {
			close(false);
			return;
		}
		try {
			this.blur();
		} catch (err) {}
		open(this);
	});
	$(document).on('click mouseup', 'select[data-remote-source]', function(e) {
		if (this.disabled) {
			return;
		}
		blockNativeSelect(e);
	});
	$(document).on('keydown', 'select[data-remote-source]', function(e) {
		if (this.disabled) {
			return;
		}
		var k = e.key;
		if (k === 'Enter' || k === ' ' || k === 'ArrowDown' || k === 'ArrowUp' || (k && k.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey)) {
			e.preventDefault();
			open(this);
			if (k && k.length === 1 && k !== ' ') {
				$search.val(k).trigger('input');
			}
		}
	});

	window.ewRemoteSourceClose = function() {
		close(false);
	};

	window.ewRemoteSourceReset = function(selector, placeholder) {
		var $s = $(selector);
		if (!$s.length) {
			return;
		}
		placeholder = placeholder == null ? 'Select' : String(placeholder);
		$s.html('<option value="">' + esc(placeholder) + '</option>');
		$s.val('');
	};

	window.ewRemoteSourcePrime = function(selector, kind, id) {
		id = parseInt(id, 10) || 0;
		kind = String(kind || '').trim();
		if (id <= 0 || (kind !== 'train' && kind !== 'flight')) {
			return;
		}
		var $s = $(selector);
		if (!$s.length) {
			return;
		}
		$.ajax({
			url: ENDPOINT,
			data: { type: kind, id: id, limit: 1 },
			dataType: 'json',
			cache: false
		}).done(function(res) {
			var rows = (res && res.results) || [];
			if (!rows.length) {
				return;
			}
			var row = rows[0];
			$s.find('option').filter(function() {
				return this.value !== '';
			}).remove();
			var hrs = row.hours || '';
			$('<option></option>')
				.attr('value', row.id)
				.attr('data-hours', hrs)
				.attr('data-journey-hours', hrs)
				.text(row.text)
				.appendTo($s);
			$s.val(String(row.id));
		});
	};
})(window.jQuery);