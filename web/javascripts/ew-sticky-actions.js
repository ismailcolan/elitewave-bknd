/**
 * App-wide sticky "Action" column.
 *
 * Any table whose LAST header cell reads "Action" / "Actions" gets that column pinned to the
 * right edge of its horizontal scroll container, so the edit / view / delete buttons stay
 * visible while the rest of the row scrolls underneath.
 *
 * - Works for static tables, DataTables (re-applied on every draw) and rows added by AJAX.
 * - Opt out per table with class "ew-no-sticky-action".
 * - Opt in a column whose header text differs by adding class "ew-sticky-action" to its <th>.
 */
(function($) {
	'use strict';
	if (!$ || window.ewStickyActionsLoaded) {
		return;
	}
	window.ewStickyActionsLoaded = true;

	var HEADER_RE = /^\s*actions?\s*$/i;
	var STICKY = 'ew-sticky-action';

	function injectCss() {
		if (document.getElementById('ew-sticky-actions-css')) {
			return;
		}
		var css = ''
			// .table-bordered / table.dataTable use overflow:hidden (rounded corners) — that turns the
			// table into its own scroll box and sticky cells never move. "clip" keeps the rounded
			// corners but is not a scroll container, so the column sticks to the real scroller.
			+ 'table.ew-sticky-table{overflow:visible!important;}'
			+ '.ew-table-scroll-host > table.ew-sticky-table{overflow:visible!important;}'
			// Horizontal pin only — never sticky-top (breaks row alignment when page/table scrolls up)
			+ 'table.ew-sticky-table > * > tr > th.' + STICKY + ',table.ew-sticky-table > * > tr > td.' + STICKY + ',table th.' + STICKY + ',table td.' + STICKY + '{position:-webkit-sticky!important;position:sticky!important;top:auto!important;right:0;left:auto!important;z-index:2;'
			+ 'width:120px!important;min-width:120px!important;max-width:120px!important;box-sizing:border-box!important;'
			// opaque background copied from the row (see syncBg) so scrolled cells/borders never show through
			+ 'background-color:var(--ew-sticky-bg,#fff)!important;'
			// divider drawn on the cell itself so it moves with the pinned column
			// (collapsed-table borders are painted by the table and would stay behind)
			+ 'background-image:linear-gradient(var(--ew-sticky-line,#C5D3E0),var(--ew-sticky-line,#C5D3E0))!important;'
			+ 'background-repeat:no-repeat!important;background-size:1px 100%!important;background-position:left top!important;'
			+ 'border-left-color:transparent!important;'
			+ 'white-space:nowrap!important;overflow:visible!important;'
			+ 'box-shadow:none!important;background-clip:border-box!important;}'
			// soft shadow strip to the left — only while columns are hidden underneath
			+ 'table.ew-sticky-table td.' + STICKY + '::before,'
			+ 'table.ew-sticky-table th.' + STICKY + ':not(.sorting):not(.sorting_asc):not(.sorting_desc)::before{'
			+ 'content:"";position:absolute;top:0;bottom:0;left:-14px;width:14px;pointer-events:none;'
			+ 'background:linear-gradient(to left,rgba(15,23,42,.14),rgba(15,23,42,0));opacity:0;transition:opacity .15s ease;display:block!important;}'
			+ 'table.ew-sticky-table.ew-has-hscroll td.' + STICKY + '::before,'
			+ 'table.ew-sticky-table.ew-has-hscroll th.' + STICKY + ':not(.sorting):not(.sorting_asc):not(.sorting_desc)::before{opacity:1;}'
			+ 'html.dark-theme table.ew-sticky-table{--ew-sticky-line:#2a3a52;}'
			+ 'html.dark-theme table th.' + STICKY + ',html.dark-theme table td.' + STICKY + '{background-color:var(--ew-sticky-bg,#121c2e)!important;}'
			+ 'table td.' + STICKY + ' > *{white-space:nowrap;}'
			+ 'table thead th.' + STICKY + '{background-color:var(--rail-bg,#DDE7F0)!important;z-index:3;}'
			+ 'table tfoot td.' + STICKY + '{z-index:3;}'
			// keep open dropdown menus inside the action cell above the next rows
			+ 'table td.' + STICKY + ':focus-within,table td.' + STICKY + '.ew-sticky-raise{z-index:6;}';
		var st = document.createElement('style');
		st.id = 'ew-sticky-actions-css';
		st.appendChild(document.createTextNode(css));
		(document.head || document.documentElement).appendChild(st);
	}

	function parseColor(c) {
		var m = /rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)/.exec(c || '');
		if (!m) {
			return null;
		}
		return { r: +m[1], g: +m[2], b: +m[3], a: m[4] === undefined ? 1 : +m[4] };
	}

	function defaultBg() {
		return $('html').hasClass('dark-theme') ? 'rgb(18, 28, 46)' : 'rgb(255, 255, 255)';
	}

	/**
	 * Opaque colour actually painted behind el: its own background composited over its ancestors'
	 * (handles striped rows, hover tints and semi-transparent rgba backgrounds).
	 */
	function paintedBg(el) {
		var layers = [];
		var node = el;
		while (node && node.nodeType === 1 && node !== document.documentElement) {
			var p = parseColor(window.getComputedStyle(node).backgroundColor);
			if (p && p.a > 0) {
				layers.push(p);
				if (p.a >= 1) {
					break;
				}
			}
			node = node.parentNode;
		}
		var base = parseColor(defaultBg());
		if (layers.length && layers[layers.length - 1].a >= 1) {
			base = layers.pop();
		}
		for (var i = layers.length - 1; i >= 0; i--) {
			var l = layers[i];
			base = {
				r: Math.round(l.r * l.a + base.r * (1 - l.a)),
				g: Math.round(l.g * l.a + base.g * (1 - l.a)),
				b: Math.round(l.b * l.a + base.b * (1 - l.a)),
				a: 1
			};
		}
		return 'rgb(' + base.r + ', ' + base.g + ', ' + base.b + ')';
	}

	/** Give the sticky cell the same background its row shows (taken from a neighbouring cell). */
	function syncBg(cell) {
		var ref = cell.previousElementSibling || cell.parentNode;
		var bg = paintedBg(ref);
		if (cell.__ewStickyBg !== bg) {
			cell.__ewStickyBg = bg;
			cell.style.setProperty('--ew-sticky-bg', bg);
		}
	}

	function lastHeaderCell(table) {
		var head = table.tHead;
		if (!head || !head.rows.length) {
			return null;
		}
		var row = head.rows[head.rows.length - 1];
		return row.cells.length ? row.cells[row.cells.length - 1] : null;
	}

	function isActionTable(table) {
		if ($(table).hasClass('ew-no-sticky-action') || $(table).closest('.ew-no-sticky-action').length) {
			return false;
		}
		var th = lastHeaderCell(table);
		if (!th) {
			return false;
		}
		if ($(th).hasClass(STICKY)) {
			return true;
		}
		return HEADER_RE.test($(th).text());
	}

	function markTable(table) {
		var th = lastHeaderCell(table);
		if (table.className.indexOf('ew-sticky-table') === -1) {
			$(table).addClass('ew-sticky-table');
		}
		$(table.tHead.rows).each(function() {
			var c = this.cells[this.cells.length - 1];
			if (c) {
				$(c).addClass(STICKY);
				syncBg(c);
			}
		});
		var sections = [];
		for (var b = 0; b < table.tBodies.length; b++) {
			sections.push(table.tBodies[b]);
		}
		if (table.tFoot) {
			sections.push(table.tFoot);
		}
		$.each(sections, function(i, sec) {
			for (var r = 0; r < sec.rows.length; r++) {
				var row = sec.rows[r];
				// skip "No data" / colspan placeholder rows
				if (row.cells.length < 2) {
					continue;
				}
				var cell = row.cells[row.cells.length - 1];
				if (cell.className.indexOf(STICKY) === -1) {
					$(cell).addClass(STICKY);
				}
				syncBg(cell);
			}
		});
		fitActionColumn(table, th);
		updateScrollState(table);
		return th;
	}

	/**
	 * Widen the action column when its buttons don't fit (e.g. fixed-layout tables squeezed on a
	 * laptop screen) — otherwise the right-most buttons are cut off at the table edge.
	 */
	function fitActionColumn(table, th) {
		if (!th) {
			return;
		}
		var needed = 0;
		var short = false;
		for (var b = 0; b < table.tBodies.length; b++) {
			var rows = table.tBodies[b].rows;
			for (var r = 0; r < rows.length; r++) {
				var cell = rows[r].cells[rows[r].cells.length - 1];
				if (!cell || rows[r].cells.length < 2 || cell.className.indexOf(STICKY) === -1) {
					continue;
				}
				var cs = window.getComputedStyle(cell);
				var pad = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0);
				var content;
				var kids = cell.children;
				if (kids.length === 1) {
					// one wrapper (e.g. .action-buttons flex group) — its scrollWidth is the full button row
					content = kids[0].scrollWidth;
				} else {
					// buttons placed directly in the cell (white-space: nowrap) — cell scrollWidth spans them
					content = cell.scrollWidth - pad;
				}
				var want = Math.ceil(content + pad + 2);
				needed = Math.max(needed, want);
				if (cell.clientWidth + 1 < want) {
					short = true;
				}
			}
		}
		if (!short || needed <= 0 || (table.__ewStickyW || 0) >= needed) {
			return;
		}
		table.__ewStickyW = needed;
		var px = needed + 'px';
		// fixed-layout tables take widths from <col> / header cells
		var cols = $(table).children('colgroup').children('col');
		if (cols.length) {
			cols.last()[0].style.width = px;
		}
		$(table.tHead.rows).each(function() {
			var c = this.cells[this.cells.length - 1];
			if (c) {
				c.style.width = px;
				c.style.minWidth = px;
			}
		});
		for (var t = 0; t < table.tBodies.length; t++) {
			var rs = table.tBodies[t].rows;
			for (var i = 0; i < rs.length; i++) {
				var last = rs[i].cells[rs[i].cells.length - 1];
				if (last && last.className.indexOf(STICKY) !== -1) {
					last.style.minWidth = px;
				}
			}
		}
	}

	function scrollParent(table) {
		var node = table.parentNode;
		while (node && node.nodeType === 1 && node !== document.body) {
			var ox = window.getComputedStyle(node).overflowX;
			if (ox === 'auto' || ox === 'scroll') {
				return node;
			}
			node = node.parentNode;
		}
		return null;
	}

	/** Shadow only while some columns are hidden under the pinned column. */
	function updateScrollState(table) {
		var sp = scrollParent(table);
		var has = !!sp && sp.scrollWidth > sp.clientWidth + 1;
		var atEnd = has && (sp.scrollLeft + sp.clientWidth >= sp.scrollWidth - 1);
		$(table).toggleClass('ew-has-hscroll', has && !atEnd);
	}

	function scan(root) {
		$(root || document).find('table').each(function() {
			if (isActionTable(this)) {
				markTable(this);
			}
		});
	}

	var pending = false;
	function scheduleScan() {
		if (pending) {
			return;
		}
		pending = true;
		setTimeout(function() {
			pending = false;
			scan(document);
		}, 60);
	}

	var raf = window.requestAnimationFrame || function(f) { return setTimeout(f, 16); };

	injectCss();
	$(function() {
		scan(document);
		// DataTables redraws (paging, search, sort, ajax reload)
		$(document).on('draw.dt init.dt', function() {
			scheduleScan();
		});
		// Row hover / selection changes the row colour → copy it to the sticky cell
		$(document).on('mouseenter mouseleave', 'table.ew-sticky-table > tbody > tr', function() {
			var row = this;
			raf(function() {
				var c = row.cells[row.cells.length - 1];
				if (c && c.className.indexOf(STICKY) !== -1) {
					syncBg(c);
				}
			});
		});
		// Bootstrap dropdowns inside the action cell: lift above following rows while open
		$(document).on('show.bs.dropdown', 'td.' + STICKY, function() {
			$(this).addClass('ew-sticky-raise');
		}).on('hide.bs.dropdown', 'td.' + STICKY, function() {
			$(this).removeClass('ew-sticky-raise');
		});
		// Any element scrolling sideways (wrappers may be created after first scan) → toggle shadow
		var scrollPending = false;
		document.addEventListener('scroll', function(e) {
			var t = e.target;
			if (!t || t.nodeType !== 1 || scrollPending) {
				return;
			}
			scrollPending = true;
			raf(function() {
				scrollPending = false;
				$(t).find('table.ew-sticky-table').each(function() {
					updateScrollState(this);
				});
			});
		}, true);
		$(window).on('resize.ewSticky', function() {
			$('table.ew-sticky-table').each(function() {
				updateScrollState(this);
			});
		});
		// Rows rendered by AJAX / custom scripts
		if (window.MutationObserver) {
			new MutationObserver(function(muts) {
				for (var i = 0; i < muts.length; i++) {
					var added = muts[i].addedNodes;
					for (var j = 0; j < added.length; j++) {
						var n = added[j];
						if (n.nodeType === 1 && (n.tagName === 'TR' || n.tagName === 'TABLE' || n.tagName === 'TBODY' || (n.querySelector && n.querySelector('tr')))) {
							scheduleScan();
							return;
						}
					}
				}
			}).observe(document.body, { childList: true, subtree: true });
		}
	});
})(window.jQuery);
