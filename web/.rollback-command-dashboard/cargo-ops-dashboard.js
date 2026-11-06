(function (window, $) {
  'use strict';

  var NS = 'http://www.w3.org/2000/svg';
  var PROG = { 2: 0.04, 3: 0.3, 4: 0.55, 5: 0.8, 6: 0.97, 7: 0.99, 8: 1 };
  var STEPS = [
    ['Picked up', 2],
    ['In transit', 3],
    ['At destination', 6],
    ['Out for delivery', 7],
    ['Delivered', 8]
  ];

  /* Reference outline (lon,lat) — same as elitewave-live-tracking.html */
  var OUT_GEO = [
    [74, 37], [77, 35.5], [79, 34], [78.5, 32.5], [79, 31], [81, 30], [80.5, 28.8],
    [84, 27.5], [88, 27], [88.5, 28], [89.5, 26.8], [92, 27], [95, 28.2], [97, 28],
    [96, 27], [94.5, 25], [94, 23.5], [93, 22], [92.3, 23.5], [91.5, 24], [89.8, 25.3],
    [89, 26], [88.8, 24.5], [89, 22], [87, 21.5], [85.5, 20], [84, 18.5], [82.2, 16.8],
    [80.3, 15.5], [80.2, 13], [79.8, 11], [78.2, 9], [77.5, 8], [76.5, 9.5], [75.5, 12],
    [74.5, 14.5], [73.3, 17.5], [72.8, 20], [72.7, 21.5], [70.5, 20.9], [69, 22.3],
    [70.5, 23.2], [68.2, 23.7], [70, 25], [71, 26.5], [70, 28], [72, 30], [74.5, 31.5], [74, 34]
  ];

  var state = {
    period: 'monthly',
    year: new Date().getFullYear(),
    pack: null,
    selected: 0,
    selectedGrn: null,
    filterStatus: 'all',
    filterMode: 'all',
    loading: false,
    mapReady: false,
    anim: null,
    reduceMotion: false,
    refreshTimer: null,
    refreshMs: 45000
  };

  var REFRESH_MS = 45000;

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function modeKey(item) {
    var k = item && item.mode_key ? String(item.mode_key) : 'road';
    if (k === 'air' || k === 'rail' || k === 'sea' || k === 'road') return k;
    return 'road';
  }

  function modePillLabel(item) {
    var k = modeKey(item);
    var labels = { air: 'Air cargo', rail: 'Train cargo', road: 'Road cargo', sea: 'Ocean cargo' };
    var raw = item && item.mode ? String(item.mode).trim() : '';
    if (raw) {
      if (/air/i.test(raw)) return 'Air cargo';
      if (/train|rail/i.test(raw)) return 'Train cargo';
      if (/sea|ocean|ship/i.test(raw)) return 'Ocean cargo';
      if (/road|truck|ftl|ptl/i.test(raw)) return 'Road cargo';
    }
    return labels[k] || 'Road cargo';
  }

  /* Material-style filled glyphs (24×24) — air / rail / road / sea */
  var MODE_PATHS = {
    air: 'M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z',
    rail: 'M12 2c-4 0-8 .5-8 4v9.5C4 17.43 5.57 19 7.5 19L6 20.5v.5h2.23l2-2H13.7l2 2H18v-.5L16.5 19c1.93 0 3.5-1.57 3.5-3.5V6c0-3.5-4-4-8-4zM7.5 17c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm3.5-7H6V6h5v4zm5.5 7c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-7h-5V6h5v4z',
    road: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z',
    sea: 'M20 21c-1.39 0-2.78-.47-4-1.32-2.44 1.71-5.56 1.71-8 0C6.78 20.53 5.39 21 4 21H2v2h2c1.38 0 2.74-.35 4-.99 2.52 1.29 5.48 1.29 8 0 1.26.65 2.62.99 4 .99h2v-2h-2zM3.95 19H4c1.6 0 3.02-.88 4-2 .98 1.12 2.4 2 4 2s3.02-.88 4-2c.98 1.12 2.4 2 4 2h.05l1.89-6.68c.08-.26.06-.54-.06-.78s-.34-.42-.6-.5L20 10.62V6c0-1.1-.9-2-2-2h-3V1H9v3H6c-1.1 0-2 .9-2 2v4.62l-1.29.42c-.26.08-.48.26-.6.5s-.15.52-.06.78L3.95 19zM6 6h12v3.97L12 8 6 9.97V6z'
  };

  function modePath(key) {
    return MODE_PATHS[key] || MODE_PATHS.road;
  }

  /** Inline SVG for rail cards + latest card. */
  function modeIconHtml(key) {
    return '<svg class="trk-mode-ico" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">'
      + '<path d="' + modePath(key) + '"/>'
      + '</svg>';
  }

  /** SVG fragment for map pin (centered at 0,0). */
  function modePinGlyph(key, fill) {
    return '<g transform="translate(-8 -8) scale(.67)">'
      + '<path fill="' + fill + '" d="' + modePath(key) + '"/>'
      + '</g>';
  }

  function statusBucket(item) {
    var st = Number(item && item.status) || 0;
    if (st >= 8) return 'delivered';
    if (st >= 2) return 'transit';
    return 'pending';
  }

  function filteredLatest() {
    var list = (state.pack && state.pack.latest) || [];
    return list.filter(function (item) {
      if (state.filterMode !== 'all' && modeKey(item) !== state.filterMode) return false;
      if (state.filterStatus !== 'all' && statusBucket(item) !== state.filterStatus) return false;
      return true;
    });
  }

  function bookingUrl(item) {
    if (item && item.edit_url) return item.edit_url;
    if (item && item.track_url) return item.track_url;
    if (item && item.grn_no) return 'track_consignment.php?grn_no=' + encodeURIComponent(item.grn_no);
    return '';
  }

  function syncFilterButtons() {
    var fs = document.getElementById('trkFilters');
    if (!fs) return;
    fs.querySelectorAll('[data-f-status]').forEach(function (btn) {
      btn.classList.toggle('on', btn.getAttribute('data-f-status') === state.filterStatus);
    });
    fs.querySelectorAll('[data-f-mode]').forEach(function (btn) {
      btn.classList.toggle('on', btn.getAttribute('data-f-mode') === state.filterMode);
    });
  }

  function applyFilters(status, mode) {
    if (status != null) state.filterStatus = status;
    if (mode != null) state.filterMode = mode;
    syncFilterButtons();
    var list = filteredLatest();
    if (!list.length) {
      state.selected = 0;
      state.selectedGrn = null;
      renderLatest(null);
      showRoute(null);
      renderRail([]);
      return;
    }
    var idx = 0;
    if (state.selectedGrn) {
      for (var i = 0; i < list.length; i++) {
        if (list[i].grn_no === state.selectedGrn) { idx = i; break; }
      }
    }
    selectIndex(idx);
  }

  function scheduleRefresh() {
    if (state.refreshTimer) clearInterval(state.refreshTimer);
    state.refreshTimer = setInterval(function () {
      if (document.hidden || state.loading) return;
      load({ silent: true });
    }, REFRESH_MS);
  }

  function stopRefresh() {
    if (state.refreshTimer) {
      clearInterval(state.refreshTimer);
      state.refreshTimer = null;
    }
  }

  function formatCount(v) {
    return Math.round(Number(v) || 0).toLocaleString('en-IN');
  }

  function formatINR(v, pre) {
    if (pre) return pre;
    var n = Math.round(Number(v) || 0);
    var abs = Math.abs(n);
    var sign = n < 0 ? '-' : '';
    if (abs >= 1e5) return sign + '\u20B9' + (abs / 1e5).toFixed(2) + ' L';
    return sign + '\u20B9' + abs.toLocaleString('en-IN');
  }

  function statusClass(label) {
    var t = String(label || '').toLowerCase();
    if (t.indexOf('deliver') >= 0) return '';
    if (t.indexOf('transit') >= 0 || t.indexOf('destination') >= 0 || t.indexOf('out for') >= 0) return 'is-transit';
    return 'is-pending';
  }

  function xy(lon, lat) {
    return [(lon - 68) * 19.5, (37.8 - lat) * 19.5];
  }

  function xyPair(coords) {
    if (!coords || coords.length < 2) return null;
    return xy(Number(coords[0]), Number(coords[1]));
  }

  function inside(x, y, poly) {
    var c = false;
    for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
      var a = poly[i], b = poly[j];
      if ((a[1] > y) !== (b[1] > y) && x < (b[0] - a[0]) * (y - a[1]) / ((b[1] - a[1]) || 1e-12) + a[0]) {
        c = !c;
      }
    }
    return c;
  }

  function km(a, b) {
    var R = 6371, r = Math.PI / 180;
    var dl = (b[1] - a[1]) * r;
    var dn = (b[0] - a[0]) * r;
    var h = Math.sin(dl / 2) * Math.sin(dl / 2)
      + Math.cos(a[1] * r) * Math.cos(b[1] * r) * Math.sin(dn / 2) * Math.sin(dn / 2);
    return Math.round(2 * R * Math.asin(Math.sqrt(h)));
  }

  function ensureMapBase() {
    if (state.mapReady) return;
    var OUT = OUT_GEO.map(function (p) { return xy(p[0], p[1]); });
    var d = '';
    for (var y = 6; y < 640; y += 8) {
      for (var x = ((y / 8) % 2) * 4 + 4; x < 600; x += 8) {
        if (inside(x, y, OUT)) d += 'M' + x + ' ' + y + 'h0';
      }
    }
    var dots = document.getElementById('trkDots');
    if (dots) dots.setAttribute('d', d);

    var cities = document.getElementById('trkCities');
    if (cities) {
      while (cities.firstChild) cities.removeChild(cities.firstChild);
      /* light city anchors for major hubs (visual only) */
      var hubs = {
        Mumbai: [72.88, 19.07], Chennai: [80.27, 13.08], Kolkata: [88.36, 22.57],
        Delhi: [77.21, 28.61], Bengaluru: [77.59, 12.97], Hyderabad: [78.49, 17.39],
        Pune: [73.86, 18.52], Ahmedabad: [72.57, 23.02], Jaipur: [75.79, 26.91]
      };
      Object.keys(hubs).forEach(function (k) {
        var p = xy(hubs[k][0], hubs[k][1]);
        var c = document.createElementNS(NS, 'circle');
        c.setAttribute('cx', p[0]);
        c.setAttribute('cy', p[1]);
        c.setAttribute('r', 3);
        c.setAttribute('class', 'trk-city');
        cities.appendChild(c);
      });
    }
    state.mapReady = true;
  }

  function clearLabels() {
    var extra = document.getElementById('trkLbls');
    if (extra) extra.remove();
  }

  function showRoute(item) {
    ensureMapBase();
    var note = document.getElementById('trkMapNote');
    var base = document.getElementById('trkBase');
    var path = document.getElementById('trkPath');
    var pin = document.getElementById('trkPin');
    clearLabels();
    if (base) base.removeAttribute('d');
    if (path) path.removeAttribute('d');
    if (pin) pin.innerHTML = '';

    if (!item || !item.mapped || !item.origin_coords || !item.dest_coords) {
      if (note) {
        note.hidden = false;
        note.textContent = item ? 'Location not mapped for this consignment.' : 'No consignments to show.';
      }
      return;
    }
    if (note) note.hidden = true;

    var A = xyPair(item.origin_coords);
    var B = xyPair(item.dest_coords);
    if (!A || !B) {
      if (note) { note.hidden = false; note.textContent = 'Location not mapped for this consignment.'; }
      return;
    }

    var mx = (A[0] + B[0]) / 2;
    var my = (A[1] + B[1]) / 2;
    var dx = B[0] - A[0];
    var dy = B[1] - A[1];
    var L = Math.hypot(dx, dy) || 1;
    var off = Math.min(90, L * 0.28);
    var cx = mx + (-dy / L) * off;
    var cy = my + (dx / L) * off - (L * 0.05);
    var dd = 'M' + A[0] + ' ' + A[1] + 'Q' + cx + ' ' + cy + ' ' + B[0] + ' ' + B[1];
    base.setAttribute('d', dd);
    path.setAttribute('d', dd);

    var status = Number(item.status) || 0;
    var progress = typeof item.progress === 'number' ? item.progress : (PROG[status] != null ? PROG[status] : 0.3);
    progress = Math.max(0, Math.min(1, progress));
    var target = path.getTotalLength() * progress;
    var col = status >= 8 ? '#17A34A' : '#0A8FB0';
    var mkey = modeKey(item);

    function put(t) {
      var q = path.getPointAtLength(t);
      pin.setAttribute('transform', 'translate(' + q.x + ' ' + q.y + ')');
    }

    /* Mode badge at pin: white disc + colored ring + mode glyph (matches mock) */
    pin.innerHTML = '<circle class="trk-pulse" r="14" fill="' + col + '" opacity=".35"></circle>'
      + '<circle r="12" fill="#fff" stroke="' + col + '" stroke-width="3"></circle>'
      + modePinGlyph(mkey, col);

    var gl = document.createElementNS(NS, 'g');
    gl.id = 'trkLbls';
    gl.innerHTML = '<circle cx="' + A[0] + '" cy="' + A[1] + '" r="6" fill="#0A8FB0" stroke="#EDF1F8" stroke-width="2"></circle>'
      + '<circle cx="' + B[0] + '" cy="' + B[1] + '" r="6" fill="#DD111E" stroke="#EDF1F8" stroke-width="2"></circle>'
      + '<text class="trk-lbl" x="' + A[0] + '" y="' + (A[1] - 12) + '" text-anchor="middle">' + esc(item.origin) + '</text>'
      + '<text class="trk-lbl" x="' + B[0] + '" y="' + (B[1] - 12) + '" text-anchor="middle">' + esc(item.destination) + '</text>';
    document.getElementById('trkMap').insertBefore(gl, pin);

    if (state.anim) cancelAnimationFrame(state.anim);
    if (state.reduceMotion) {
      put(target);
      return;
    }
    var t0 = null;
    var dur = 1600;
    (function step(n) {
      if (t0 == null) t0 = n;
      var k = Math.min(1, (n - t0) / dur);
      var e = 1 - Math.pow(1 - k, 3);
      put(target * e);
      if (k < 1) state.anim = requestAnimationFrame(step);
    })(performance.now());
  }

  function renderYears(years, active) {
    var el = document.getElementById('cldYears');
    if (!el) return;
    el.innerHTML = (years || []).map(function (y) {
      return '<button type="button" data-year="' + esc(y) + '"' + (Number(y) === Number(active) ? ' class="on"' : '') + '>' + esc(y) + '</button>';
    }).join('');
  }

  function renderKpis(k) {
    k = k || {};
    var map = {
      bookings: formatCount(k.bookings),
      delivered: formatCount(k.delivered),
      inTransit: formatCount(k.inTransit),
      invoices: formatCount(k.invoices),
      cancelled: formatCount(k.cancelled)
    };
    Object.keys(map).forEach(function (key) {
      var el = document.querySelector('.trk-k b[data-k="' + key + '"]');
      if (el) el.textContent = map[key];
    });
    var wrap = document.getElementById('trkKCancelled');
    if (wrap) wrap.classList.toggle('warn', Number(k.cancelled) > 0);
  }

  function renderTimeline(status) {
    var st = Number(status) || 0;
    var html = STEPS.map(function (s, idx) {
      var done = st >= s[1];
      var next = STEPS[idx + 1];
      var now = st < 8 && done && !(next && st >= next[1]);
      if (s[1] === 3 && st >= 3 && st <= 5) {
        done = false;
        now = true;
      } else if (s[1] === 3 && st >= 6) {
        done = true;
        now = false;
      }
      if (st >= 8) {
        done = true;
        now = false;
      }
      return '<li class="' + (now ? 'now' : (done ? 'done' : '')) + '">' + esc(s[0]) + '</li>';
    }).join('');
    $('#trkTimeline').html(html);
  }

  function renderLatest(item) {
    var empty = document.getElementById('trkLatestEmpty');
    var body = document.getElementById('trkLatestBody');
    if (!item) {
      if (empty) empty.hidden = false;
      if (body) body.hidden = true;
      $('#trkLatestStatus').text('').attr('class', '');
      return;
    }
    if (empty) empty.hidden = true;
    if (body) body.hidden = false;
    var label = item.status_label || '—';
    var cls = statusClass(label);
    $('#trkLatestStatus').text(label).attr('class', cls);
    var gcn = item.grn_no || '—';
    var href = bookingUrl(item);
    var gcnEl = document.getElementById('trkLatestGcn');
    if (gcnEl) {
      if (href && gcn !== '—') {
        gcnEl.innerHTML = '<a class="trk-gcn-link" href="' + esc(href) + '" target="_blank" rel="noopener" title="Open booking">' + esc(gcn) + '</a>';
      } else {
        gcnEl.textContent = gcn;
      }
    }
    var mkey = modeKey(item);
    var pill = document.getElementById('trkLatestModePill');
    if (pill) {
      pill.hidden = false;
      pill.className = 'trk-mode-pill is-' + mkey;
      pill.innerHTML = modeIconHtml(mkey) + '<span>' + esc(modePillLabel(item)) + '</span>';
      pill.title = item.mode || modePillLabel(item);
    }
    $('#trkLatestClient').text(item.client || '—');
    $('#trkLatestOrigin').text(item.origin || '—');
    $('#trkLatestDest').text(item.destination || '—');
    $('#trkLatestFreight').text(formatINR(item.freight, item.freight_fmt));
    var dist = document.getElementById('trkLatestKm');
    if (dist) {
      if (item.km != null) {
        dist.innerHTML = 'Approx. aerial <b>' + Number(item.km).toLocaleString('en-IN') + ' km</b>';
      } else if (item.origin_coords && item.dest_coords) {
        dist.innerHTML = 'Approx. aerial <b>' + km(item.origin_coords, item.dest_coords).toLocaleString('en-IN') + ' km</b>';
      } else {
        dist.textContent = 'Approx. — km';
      }
    }
    renderTimeline(item.status);
  }

  function renderRail(list) {
    var rail = document.getElementById('trkRail');
    if (!rail) return;
    if (!list || !list.length) {
      rail.innerHTML = '<div class="trk-rail-empty">No consignments match these filters.</div>';
      return;
    }
    rail.innerHTML = list.map(function (item, i) {
      var pressed = i === state.selected ? 'true' : 'false';
      var emCls = statusClass(item.status_label);
      var mkey = modeKey(item);
      var href = bookingUrl(item);
      var gcnInner = href
        ? '<a class="trk-gcn-link" href="' + esc(href) + '" target="_blank" rel="noopener" title="Open booking">' + esc(item.grn_no) + '</a>'
        : esc(item.grn_no);
      return '<button type="button" class="trk-chip" data-i="' + i + '" aria-pressed="' + pressed + '" title="' + esc(item.mode || mkey) + '">'
        + '<b>' + gcnInner + '<em class="' + emCls + '">' + esc(item.status_label) + '</em></b>'
        + '<p class="trk-chip-lane">' + modeIconHtml(mkey) + '<span>' + esc(item.origin) + ' \u2192 ' + esc(item.destination) + '</span></p>'
        + '<small>' + esc(item.client) + '</small>'
        + '</button>';
    }).join('');
  }

  function selectIndex(idx) {
    var list = filteredLatest();
    if (!list.length) {
      state.selected = 0;
      state.selectedGrn = null;
      renderLatest(null);
      showRoute(null);
      renderRail([]);
      return;
    }
    if (idx < 0 || idx >= list.length) idx = 0;
    state.selected = idx;
    var item = list[idx];
    state.selectedGrn = item.grn_no || null;
    renderLatest(item);
    showRoute(item);
    renderRail(list);
  }

  function render(preserveSelection) {
    var pack = state.pack;
    if (!pack) return;
    var ts = '';
    try {
      ts = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
    } catch (e) { ts = ''; }
    $('#cldAsOf').text('Live \u00B7 ' + (pack.asOf || '') + (ts ? ' \u00B7 Updated ' + ts : ''));
    renderYears(pack.years || [], pack.year);
    renderKpis(pack.kpis);
    syncFilterButtons();
    if (!preserveSelection) {
      state.selected = 0;
      state.selectedGrn = null;
    } else if (state.selectedGrn) {
      var list = filteredLatest();
      var found = -1;
      for (var i = 0; i < list.length; i++) {
        if (list[i].grn_no === state.selectedGrn) { found = i; break; }
      }
      state.selected = found >= 0 ? found : 0;
    }
    if (state.selected >= filteredLatest().length) state.selected = 0;
    selectIndex(state.selected);
  }

  function load(options) {
    options = options || {};
    var silent = !!options.silent;
    if (!silent) {
      state.loading = true;
      $('#cldBoot').show().prop('hidden', false);
      $('#cldShell').hide().prop('hidden', true);
    }
    $.ajax({
      url: 'dashboard_tracking_json.php',
      type: 'GET',
      dataType: 'json',
      data: { year: state.year, period: state.period },
      success: function (res) {
        state.loading = false;
        if (!res || res.status !== 0) {
          if (!silent) {
            $('#cldBoot').html('<p style="padding:24px;color:#5E6F8C;font-weight:600;">'
              + esc((res && res.message) ? res.message : 'Could not load tracking data.') + '</p>');
          }
          return;
        }
        state.pack = res;
        state.year = res.year || state.year;
        ensureMapBase();
        if (!silent) {
          state.selected = 0;
          state.selectedGrn = null;
          $('#cldBoot').hide().prop('hidden', true);
          $('#cldShell').show().prop('hidden', false);
          render(false);
          scheduleRefresh();
        } else {
          render(true);
        }
      },
      error: function () {
        state.loading = false;
        if (!silent) {
          $('#cldBoot').html('<p style="padding:24px;color:#5E6F8C;font-weight:600;">Could not load tracking data.</p>');
        }
      }
    });
  }

  window.CargoOps = {
    init: function () {
      if (!document.getElementById('cargoOpsRoot') || state.loading) return;
      state.loading = true;
      state.reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

      $('#cldPeriod').on('click', 'button', function () {
        state.period = $(this).attr('data-p') || 'monthly';
        $('#cldPeriod button').removeClass('on');
        $(this).addClass('on');
        load();
      });

      $(document).on('click', '#cldYears button', function () {
        var y = parseInt($(this).attr('data-year'), 10);
        if (!y || y === state.year) return;
        state.year = y;
        load();
      });

      $(document).on('click', '#trkRail .trk-chip', function (e) {
        if ($(e.target).closest('.trk-gcn-link').length) return;
        if (railDrag.suppressClick) {
          e.preventDefault();
          e.stopImmediatePropagation();
          return;
        }
        var idx = parseInt($(this).attr('data-i'), 10);
        if (isNaN(idx)) return;
        selectIndex(idx);
      });

      $(document).on('click', '#trkFilters [data-f-status]', function () {
        applyFilters($(this).attr('data-f-status'), null);
      });

      $(document).on('click', '#trkFilters [data-f-mode]', function () {
        applyFilters(null, $(this).attr('data-f-mode'));
      });

      $(document).on('click', '.trk-k[data-filter-status]', function () {
        applyFilters($(this).attr('data-filter-status'), null);
      });

      document.addEventListener('visibilitychange', function () {
        if (!document.hidden && state.pack) load({ silent: true });
      });

      bindRailDrag();
      load();
    },
    stop: stopRefresh
  };

  var railDrag = {
    active: false,
    moved: false,
    suppressClick: false,
    startX: 0,
    startScroll: 0,
    pointerId: null
  };

  function bindRailDrag() {
    var rail = document.getElementById('trkRail');
    if (!rail || rail.getAttribute('data-drag-bound') === '1') return;
    rail.setAttribute('data-drag-bound', '1');

    function onMove(e) {
      if (!railDrag.active) return;
      var dx = e.clientX - railDrag.startX;
      if (!railDrag.moved && Math.abs(dx) > 8) {
        railDrag.moved = true;
        rail.classList.add('is-dragging');
        /* Capture only after a real drag so taps still click chips */
        try { rail.setPointerCapture(railDrag.pointerId); } catch (err) { /* ignore */ }
      }
      if (railDrag.moved) {
        rail.scrollLeft = railDrag.startScroll - dx;
        e.preventDefault();
      }
    }

    function endDrag() {
      if (!railDrag.active) return;
      var wasDrag = railDrag.moved;
      railDrag.active = false;
      rail.classList.remove('is-dragging');
      window.removeEventListener('pointermove', onMove);
      window.removeEventListener('pointerup', endDrag);
      window.removeEventListener('pointercancel', endDrag);
      if (railDrag.pointerId != null) {
        try { rail.releasePointerCapture(railDrag.pointerId); } catch (err) { /* ignore */ }
        railDrag.pointerId = null;
      }
      railDrag.moved = false;
      if (wasDrag) {
        railDrag.suppressClick = true;
        setTimeout(function () { railDrag.suppressClick = false; }, 80);
      }
    }

    rail.addEventListener('pointerdown', function (e) {
      if (e.button != null && e.button !== 0) return;
      railDrag.active = true;
      railDrag.moved = false;
      railDrag.suppressClick = false;
      railDrag.startX = e.clientX;
      railDrag.startScroll = rail.scrollLeft;
      railDrag.pointerId = e.pointerId;
      window.addEventListener('pointermove', onMove);
      window.addEventListener('pointerup', endDrag);
      window.addEventListener('pointercancel', endDrag);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { window.CargoOps.init(); });
  } else {
    window.CargoOps.init();
  }
})(window, window.jQuery);
