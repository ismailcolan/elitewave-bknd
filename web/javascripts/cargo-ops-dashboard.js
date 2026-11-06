(function (window, $) {
  'use strict';

  var NS = 'http://www.w3.org/2000/svg';
  var PROG = { 2: 0.04, 3: 0.3, 4: 0.55, 5: 0.8, 6: 0.97, 7: 0.99, 8: 1 };
  var STEPS = [
    ['Picked up', 2],
    ['In Transit-1', 3],
    ['In Transit-2', 4],
    ['In Transit-3', 5],
    ['At destination', 6],
    ['Out for delivery', 7],
    ['Delivered', 8]
  ];

  var state = {
    period: 'monthly',
    year: new Date().getFullYear(),
    pack: null,
    selected: 0,
    selectedGrn: null,
    filterStatus: 'all',
    filterMode: 'all',
    filterView: 'all',
    q: '',
    sel: {},
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

  var MODE_ART = {
    air: 'images/side-ops-image/flight-cargo.png?v=nobg1',
    rail: 'images/side-ops-image/train-cargo.png?v=nobg1',
    road: 'images/side-ops-image/truck-cargo.png?v=nobg1',
    sea: 'images/side-ops-image/truck-cargo.png?v=nobg1'
  };

  function modeArtHtml(key, label) {
    var src = MODE_ART[key] || MODE_ART.road;
    return '<img class="trk-drawer-art-img" src="' + esc(src) + '" alt="' + esc(label || key || 'Cargo') + '" width="2172" height="724" decoding="async">';
  }

  /* Top-down sprites from the map preview. Nose points +x. */
  var VEH_SPRITE = {
    road: '<path d="M28 -6L58 -15V15L28 6Z" fill="url(#gBeam)"/><rect x="-27" y="-9" width="34" height="18" rx="2.2" fill="url(#gOr)" stroke="#A84608" stroke-width=".6"/><path d="M-23 -9v18M-20 -9v18M-17 -9v18M-14 -9v18M-11 -9v18M-8 -9v18M-5 -9v18M-2 -9v18M1 -9v18M4 -9v18" stroke="#C4560C" stroke-width=".5" opacity=".7"/><path d="M-27 0H7" stroke="#A84608" stroke-width=".5"/><rect x="-27.8" y="-8.2" width="2.6" height="16.4" rx=".8" fill="#8F3A08"/><text x="-10" y="2" text-anchor="middle" style="font:800 5.6px sans-serif;fill:#fff;letter-spacing:.3px">EW360</text><rect x="7" y="-3.6" width="5" height="7.2" rx="1" fill="#27324D"/><g fill="#161D30"><rect x="-23" y="-10.8" width="7" height="2.2" rx="1"/><rect x="-14" y="-10.8" width="7" height="2.2" rx="1"/><rect x="-23" y="8.6" width="7" height="2.2" rx="1"/><rect x="-14" y="8.6" width="7" height="2.2" rx="1"/><rect x="15" y="-10" width="7" height="2.2" rx="1"/><rect x="15" y="7.8" width="7" height="2.2" rx="1"/></g><rect x="11" y="-8.2" width="17.5" height="16.4" rx="4.2" fill="url(#gWh)" stroke="#AEB9CF" stroke-width=".7"/><rect x="12.4" y="-6.6" width="5.6" height="13.2" rx="1.4" fill="#E3E8F2"/><rect x="11" y="-1" width="17.5" height="2" fill="#0A8FB0" opacity=".85"/><path d="M21 -6.8Q28 -6.3 28.3 0Q28 6.3 21 6.8Z" fill="url(#gGl)"/><path d="M22.5 -5.2Q26 -4.8 26.6 -2.6" stroke="#fff" stroke-width=".6" opacity=".6" fill="none"/><rect x="19.4" y="-11" width="2.8" height="2.6" rx=".9" fill="#1D2742"/><rect x="19.4" y="8.4" width="2.8" height="2.6" rx=".9" fill="#1D2742"/><circle cx="28.2" cy="-5.6" r="1.4" fill="#FFF1A8"/><circle cx="28.2" cy="5.6" r="1.4" fill="#FFF1A8"/>',
    rail: '<path d="M34 -6L68 -16V16L34 6Z" fill="url(#gBeam)"/><g><rect x="-80" y="-7" width="23" height="14" rx="2" fill="#37445F"/><rect x="-78.5" y="-5.6" width="20" height="11.2" rx="1.4" fill="url(#gBl)"/><path d="M-75 -5.6v11.2M-71 -5.6v11.2M-67 -5.6v11.2M-63 -5.6v11.2" stroke="#1F4FA0" stroke-width=".7"/><rect x="-54" y="-7" width="23" height="14" rx="2" fill="#37445F"/><rect x="-52.5" y="-5.6" width="20" height="11.2" rx="1.4" fill="url(#gRd)"/><path d="M-49 -5.6v11.2M-45 -5.6v11.2M-41 -5.6v11.2M-37 -5.6v11.2" stroke="#A81E34" stroke-width=".7"/><rect x="-28" y="-7" width="23" height="14" rx="2" fill="#37445F"/><rect x="-26.5" y="-5.6" width="20" height="11.2" rx="1.4" fill="url(#gYl)"/><path d="M-23 -5.6v11.2M-19 -5.6v11.2M-15 -5.6v11.2M-11 -5.6v11.2" stroke="#B07608" stroke-width=".7"/><path d="M-57 0h3M-31 0h3M-5 0h3" stroke="#37445F" stroke-width="2.2"/></g><g fill="#161D30"><rect x="-77" y="-8.6" width="6" height="1.8"/><rect x="-65" y="-8.6" width="6" height="1.8"/><rect x="-77" y="6.8" width="6" height="1.8"/><rect x="-65" y="6.8" width="6" height="1.8"/><rect x="-51" y="-8.6" width="6" height="1.8"/><rect x="-39" y="-8.6" width="6" height="1.8"/><rect x="-51" y="6.8" width="6" height="1.8"/><rect x="-39" y="6.8" width="6" height="1.8"/><rect x="-25" y="-8.6" width="6" height="1.8"/><rect x="-13" y="-8.6" width="6" height="1.8"/><rect x="-25" y="6.8" width="6" height="1.8"/><rect x="-13" y="6.8" width="6" height="1.8"/></g><path d="M-2 -8H21Q31 -8 34 -3.6V3.6Q31 8 21 8H-2Z" fill="url(#gOr)" stroke="#A84608" stroke-width=".6"/><rect x="1" y="-6" width="13" height="12" rx="1.8" fill="#C4520A"/><g fill="#8F3A08"><circle cx="5" cy="-2.6" r="1.7"/><circle cx="10" cy="-2.6" r="1.7"/><circle cx="5" cy="2.6" r="1.7"/><circle cx="10" cy="2.6" r="1.7"/></g><rect x="21.5" y="-7.4" width="2.4" height="14.8" fill="#FFD166"/><path d="M25 -5Q31 -5.2 32 0Q31 5.2 25 5Z" fill="url(#gGl)"/><circle cx="33.6" cy="-2.4" r="1" fill="#FFF3B0"/><circle cx="33.6" cy="2.4" r="1" fill="#FFF3B0"/><g fill="#161D30"><rect x="0" y="-9.6" width="7" height="1.8"/><rect x="14" y="-9.6" width="7" height="1.8"/><rect x="0" y="7.8" width="7" height="1.8"/><rect x="14" y="7.8" width="7" height="1.8"/></g>',
    air: '<path d="M5 -3L-8 -33L-16 -33L-8 -3Z" fill="#E1E8F4" stroke="#93A5C6" stroke-width=".7"/><path d="M5 3L-8 33L-16 33L-8 3Z" fill="#E1E8F4" stroke="#93A5C6" stroke-width=".7"/><path d="M-12 -30.5H-8M-12 30.5H-8" stroke="#6D5BD0" stroke-width="2"/><path d="M-4 -12L-8 -12M-4 12L-8 12" stroke="#93A5C6" stroke-width=".5"/><path d="M-24 -2L-33 -13L-36.5 -13L-31 -2Z" fill="#D2DAEB" stroke="#93A5C6" stroke-width=".6"/><path d="M-24 2L-33 13L-36.5 13L-31 2Z" fill="#D2DAEB" stroke="#93A5C6" stroke-width=".6"/><g><rect x="-6" y="-17.6" width="12" height="5.4" rx="2.7" fill="#8A97B3"/><rect x="3.4" y="-17.6" width="2.6" height="5.4" rx="1.3" fill="#D0D8E8"/><rect x="-6" y="12.2" width="12" height="5.4" rx="2.7" fill="#8A97B3"/><rect x="3.4" y="12.2" width="2.6" height="5.4" rx="1.3" fill="#D0D8E8"/></g><path d="M37 0C35 -3 27 -3.9 14 -3.9L-26 -2.8C-33 -2.4 -37 -1.1 -37 0C-37 1.1 -33 2.4 -26 2.8L14 3.9C27 3.9 35 3 37 0Z" fill="url(#gWh)" stroke="#93A5C6" stroke-width=".8"/><path d="M-26 0H29" stroke="#6D5BD0" stroke-width="1.7"/><path d="M-24 -1.9H17M-24 1.9H17" stroke="#8FA0BC" stroke-width=".7" stroke-dasharray="1.2 1.6"/><path d="M29.5 0C31.4 -1.1 33 -1.5 34.2 -1.5C35 -.6 35 .6 34.2 1.5C33 1.5 31.4 1.1 29.5 0Z" fill="#26364F"/>'
  };
  var VEH_SCALE = { road: 0.5, rail: 0.34, air: 0.42, sea: 0.5 };
  var VEH_SPEED = { road: 0.002, rail: 0.0016, air: 0.006, sea: 0.002 };

  function modeVehicleHtml(key) {
    var sprite = VEH_SPRITE[key] || VEH_SPRITE.road;
    return '<g class="trk-veh">' + sprite + '</g>';
  }

  function moverHtml(pathD, progress, key, selected) {
    var base = VEH_SCALE[key] || VEH_SCALE.road;
    var scale = base * (selected ? 1.25 : 1);
    var speed = VEH_SPEED[key] || VEH_SPEED.road;
    return '<g class="trk-mover" data-d="' + esc(pathD) + '" data-span="' + progress + '" data-speed="' + speed + '" data-scale="' + scale + '" pointer-events="none">'
      + modeVehicleHtml(key === 'sea' ? 'road' : key)
      + '</g>';
  }

  function placeMover(g, path, len, dist, scale) {
    var p = path.getPointAtLength(Math.max(0, Math.min(len, dist)));
    var behind = path.getPointAtLength(Math.max(0, dist - 4));
    var ahead = path.getPointAtLength(Math.min(len, dist + 4));
    var ang = Math.atan2(ahead.y - behind.y, ahead.x - behind.x) * 180 / Math.PI;
    g.setAttribute('transform', 'translate(' + p.x + ' ' + p.y + ')');
    var sprite = g.querySelector('.trk-veh');
    if (sprite) sprite.setAttribute('transform', 'rotate(' + ang + ') scale(' + scale + ')');
  }

  function startRouteMotion() {
    if (state.anim) {
      cancelAnimationFrame(state.anim);
      state.anim = null;
    }
    var nodes = document.querySelectorAll('#trkRoutes .trk-mover');
    if (!nodes.length) return;
    var movers = [];
    for (var i = 0; i < nodes.length; i++) {
      var g = nodes[i];
      var path = document.createElementNS(NS, 'path');
      path.setAttribute('d', g.getAttribute('data-d') || '');
      var len = path.getTotalLength() || 1;
      var span = Math.max(0.04, Math.min(0.96, parseFloat(g.getAttribute('data-span')) || 0.3));
      var scale = parseFloat(g.getAttribute('data-scale')) || 0.5;
      var speed = parseFloat(g.getAttribute('data-speed')) || 0.002;
      movers.push({ g: g, path: path, len: len, span: span, scale: scale, speed: speed, elapsed: 0 });
      placeMover(g, path, len, span * len, scale);
    }
    if (state.reduceMotion) return;
    var last = 0;
    function frame(now) {
      if (!last) last = now;
      var dt = Math.min(0.05, (now - last) / 1000);
      last = now;
      for (var n = 0; n < movers.length; n++) {
        var m = movers[n];
        m.elapsed += dt;
        var u = Math.min(0.99, m.span + m.elapsed * m.speed);
        placeMover(m.g, m.path, m.len, u * m.len, m.scale);
      }
      state.anim = requestAnimationFrame(frame);
    }
    state.anim = requestAnimationFrame(frame);
  }

  function statusBucket(item) {
    var st = Number(item && item.status) || 0;
    if (st >= 8) return 'delivered';
    if (st >= 2) return 'transit';
    return 'pending';
  }

  function cityCode(name) {
    var s = String(name || '').trim();
    if (!s || s === '—') return '—';
    var parts = s.replace(/[^a-zA-Z\s]/g, ' ').trim().split(/\s+/);
    if (parts.length >= 2) {
      return (parts[0].slice(0, 1) + parts[1].slice(0, 2)).toUpperCase();
    }
    return s.slice(0, 3).toUpperCase();
  }

  function statusColor(item) {
    return statusTone(item).bar;
  }

  function statusTone(item) {
    var st = Number(item && item.status) || 0;
    if (st >= 8) return { bg: '#E7F6EC', fg: '#15803D', bar: '#17A34A' };
    if (st >= 7) return { bg: '#FFF1E6', fg: '#C2410C', bar: '#EA580C' };
    if (st >= 6) return { bg: '#E8EEF8', fg: '#1E3A8A', bar: '#1D4ED8' };
    if (st >= 5) return { bg: '#F3E8FF', fg: '#6D28D9', bar: '#7C3AED' };
    if (st >= 4) return { bg: '#E7F0FF', fg: '#1D4ED8', bar: '#2563EB' };
    if (st >= 3) return { bg: '#E5F6FA', fg: '#0E7490', bar: '#0A8FB0' };
    if (st >= 2) return { bg: '#FFF6E5', fg: '#B45309', bar: '#D97706' };
    return { bg: '#F1F5F9', fg: '#475569', bar: '#64748B' };
  }

  function healthMeta(item) {
    var b = statusBucket(item);
    if (b === 'delivered') return { label: 'Delivered', color: '#15803D' };
    if (b === 'transit') return { label: 'On track', color: '#0A8FB0' };
    if (!item || !item.mapped) return { label: 'Unmapped', color: '#C2410C' };
    return { label: 'Awaiting', color: '#B45309' };
  }

  function ensureVehicleSymbols() {
    var defs = document.querySelector('#trkMap defs');
    if (!defs || document.getElementById('veh-road')) return;
    var boxes = {
      road: '-32 -18 94 36',
      rail: '-86 -20 160 42',
      air: '-40 -36 80 72'
    };
    ['road', 'rail', 'air'].forEach(function (key) {
      var sym = document.createElementNS(NS, 'symbol');
      sym.setAttribute('id', 'veh-' + key);
      sym.setAttribute('viewBox', boxes[key]);
      sym.innerHTML = VEH_SPRITE[key];
      defs.appendChild(sym);
    });
  }

  function modeSpriteHtml(key) {
    var k = key === 'sea' ? 'road' : key;
    if (!VEH_SPRITE[k]) k = 'road';
    var boxes = {
      road: '-32 -18 94 36',
      rail: '-86 -20 160 42',
      air: '-40 -36 80 72'
    };
    return '<svg class="trk-veh-ico" viewBox="' + boxes[k] + '" aria-hidden="true"><use href="#veh-' + k + '"></use></svg>';
  }

  function needsItem(item) {
    var b = statusBucket(item);
    return b === 'transit' || b === 'pending';
  }

  function filteredLatest() {
    var list = (state.pack && state.pack.latest) || [];
    var q = String(state.q || '').trim().toLowerCase();
    return list.filter(function (item) {
      if (state.filterMode !== 'all' && modeKey(item) !== state.filterMode) return false;
      if (state.filterView === 'need') {
        if (!needsItem(item)) return false;
      } else if (state.filterStatus !== 'all' && statusBucket(item) !== state.filterStatus) {
        return false;
      }
      if (q) {
        var hay = [
          item.grn_no, item.client, item.origin, item.destination, item.mode, item.status_label
        ].join(' ').toLowerCase();
        if (hay.indexOf(q) < 0) return false;
      }
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
    if (fs) {
      fs.querySelectorAll('[data-f-status]').forEach(function (btn) {
        var st = btn.getAttribute('data-f-status');
        var on = state.filterView !== 'need' && st === state.filterStatus;
        btn.classList.toggle('on', on);
      });
      fs.querySelectorAll('[data-f-view]').forEach(function (btn) {
        btn.classList.toggle('on', btn.getAttribute('data-f-view') === state.filterView);
      });
      fs.querySelectorAll('[data-f-mode]').forEach(function (btn) {
        btn.classList.toggle('on', btn.getAttribute('data-f-mode') === state.filterMode);
      });
    }
    document.querySelectorAll('#trkKpis .trk-k[data-filter-status]').forEach(function (btn) {
      var st = btn.getAttribute('data-filter-status');
      var on = false;
      if (st === 'delivered' || st === 'transit' || st === 'pending') {
        on = state.filterView !== 'need' && st === state.filterStatus;
      } else if (st === 'all' && btn.querySelector('[data-k="bookings"]')) {
        on = state.filterView !== 'need' && state.filterStatus === 'all';
      }
      btn.classList.toggle('on', on);
    });
  }

  function needsList() {
    var list = (state.pack && state.pack.latest) || [];
    var out = [];
    list.forEach(function (item) {
      var bucket = statusBucket(item);
      if (bucket === 'transit') {
        out.push({
          rank: 0,
          color: '#0A8FB0',
          title: (item.grn_no || '—') + ' · ' + (item.origin || '—') + ' → ' + (item.destination || '—'),
          detail: (item.client || '—') + ' · In transit',
          action: 'View',
          item: item
        });
      } else if (bucket === 'pending') {
        out.push({
          rank: 1,
          color: '#D9600A',
          title: (item.grn_no || '—') + ' · ' + (item.origin || '—') + ' → ' + (item.destination || '—'),
          detail: (item.client || '—') + ' · Awaiting pickup / other',
          action: 'Check',
          item: item
        });
      }
    });
    out.sort(function (a, b) { return a.rank - b.rank; });
    return out.slice(0, 6);
  }

  function renderNeeds() {
    var el = document.getElementById('trkNeeds');
    if (!el) return;
    var rows = needsList();
    if (!rows.length) {
      el.innerHTML = '<div class="trk-needs-empty">Nothing urgent in this period.</div>';
      return;
    }
    el.innerHTML = rows.map(function (row, i) {
      return '<div class="trk-ex" style="--c:' + row.color + '">'
        + '<i aria-hidden="true"></i>'
        + '<div><b>' + esc(row.title) + '</b><small>' + esc(row.detail) + '</small></div>'
        + '<button type="button" class="trk-ex-btn" data-need-i="' + i + '">' + esc(row.action) + '</button>'
        + '</div>';
    }).join('');
    window.__trkNeeds = rows;
  }

  function applyFilters(status, mode, view) {
    if (view != null) {
      state.filterView = view;
      if (view === 'need') state.filterStatus = 'all';
    }
    if (status != null) {
      state.filterStatus = status;
      state.filterView = 'all';
    }
    if (mode != null) state.filterMode = mode;
    syncFilterButtons();
    var list = filteredLatest();
    renderNeeds();
    var cnt = document.getElementById('trkCount');
    if (cnt) {
      var total = ((state.pack && state.pack.latest) || []).length;
      cnt.textContent = list.length + ' of ' + total + ' consignments';
    }
    if (!list.length) {
      state.selected = 0;
      state.selectedGrn = null;
      renderLatest(null);
      showRoute(null);
      renderTable([]);
      var hint = document.getElementById('trkMapHint');
      if (hint) hint.textContent = 'No consignments match these filters';
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

  function selectByGrn(grn, openDrawer) {
    if (!grn) return;
    state.filterStatus = 'all';
    state.filterMode = 'all';
    state.filterView = 'all';
    state.q = '';
    var inp = document.getElementById('trkSearch');
    if (inp) inp.value = '';
    syncFilterButtons();
    var list = filteredLatest();
    var idx = 0;
    for (var i = 0; i < list.length; i++) {
      if (list[i].grn_no === grn) { idx = i; break; }
    }
    state.selectedGrn = grn;
    selectIndex(idx);
    renderNeeds();
    if (openDrawer !== false) openDrawerFor(list[idx] || null);
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
    return [(lon - 68) * 18.2 + 22, (37.8 - lat) * 19.5];
  }

  function xyPair(coords) {
    if (!coords || coords.length < 2) return null;
    return xy(Number(coords[0]), Number(coords[1]));
  }

  function cityStateKey(name) {
    return String(name || '')
      .toLowerCase()
      .replace(/\s*\([^)]*\)/g, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function stateForCity(name, direct) {
    if (direct) return String(direct);
    var key = cityStateKey(name);
    var map = window.EW_CITY_STATE || {};
    if (map[key]) return map[key];
    var aliases = { bangalore: 'bengaluru', banglore: 'bengaluru', delhi: 'new delhi', gurgaon: 'gurugram', pondy: 'pondicherry', puducherry: 'pondicherry' };
    if (aliases[key] && map[aliases[key]]) return map[aliases[key]];
    if (aliases[key] && map[key]) return map[key];
    return '';
  }

  function highlightStates(item) {
    var land = document.getElementById('land');
    if (!land) return;
    var paths = land.querySelectorAll('path');
    for (var i = 0; i < paths.length; i++) paths[i].classList.remove('hl');
    if (!item) return;
    var want = {};
    var a = stateForCity(item.origin, item.origin_state);
    var b = stateForCity(item.destination, item.dest_state);
    if (a) want[a] = 1;
    if (b) want[b] = 1;
    for (var j = 0; j < paths.length; j++) {
      var s = paths[j].getAttribute('data-s') || '';
      if (want[s]) paths[j].classList.add('hl');
    }
  }

  function labelPoint(from, to, which) {
    var dx = to[0] - from[0];
    var dy = to[1] - from[1];
    var L = Math.hypot(dx, dy) || 1;
    var ux = dx / L;
    var uy = dy / L;
    var px = -uy;
    var py = ux;
    var close = L < 120;
    if (which === 'origin') {
      var d0 = close ? 26 : 14;
      return [from[0] - ux * d0 + px * (close ? 14 : 4), from[1] - uy * d0 - 4];
    }
    var d1 = close ? 26 : 16;
    return [to[0] + ux * d1 - px * (close ? 14 : 4), to[1] + uy * d1 + 6];
  }

  function km(a, b) {
    var R = 6371, r = Math.PI / 180;
    var dl = (b[1] - a[1]) * r;
    var dn = (b[0] - a[0]) * r;
    var h = Math.sin(dl / 2) * Math.sin(dl / 2)
      + Math.cos(a[1] * r) * Math.cos(b[1] * r) * Math.sin(dn / 2) * Math.sin(dn / 2);
    return Math.round(2 * R * Math.asin(Math.sqrt(h)));
  }

  function routeKm(item) {
    if (!item) return null;
    if (item.km != null && item.km !== '') return Number(item.km);
    if (item.origin_coords && item.dest_coords) return km(item.origin_coords, item.dest_coords);
    return null;
  }

  function remainingKm(item) {
    var total = routeKm(item);
    if (total == null || isNaN(total)) return null;
    var st = Number(item.status) || 0;
    var p = PROG[st];
    if (p == null) p = Number(item.progress);
    if (p == null || isNaN(p)) p = 0;
    if (p < 0) p = 0;
    if (p > 1) p = 1;
    return Math.max(0, Math.round(total * (1 - p)));
  }

  function ensureMapBase() {
    if (state.mapReady) return;
    var cities = document.getElementById('trkCities');
    if (cities) {
      while (cities.firstChild) cities.removeChild(cities.firstChild);
      var hubs = {
        Mumbai: [72.8777, 19.0760], Chennai: [80.2707, 13.0827], Kolkata: [88.3639, 22.5726],
        Delhi: [77.2090, 28.6139], Bengaluru: [77.5946, 12.9716]
      };
      Object.keys(hubs).forEach(function (k) {
        var p = xy(hubs[k][0], hubs[k][1]);
        var c = document.createElementNS(NS, 'circle');
        c.setAttribute('cx', p[0]);
        c.setAttribute('cy', p[1]);
        c.setAttribute('r', 2.2);
        c.setAttribute('class', 'trk-city');
        cities.appendChild(c);
      });
    }
    state.mapReady = true;
  }

  var MODE_ROUTE_COL = { road: '#0A8FB0', rail: '#D9600A', air: '#6D5BD0', sea: '#0F8A5F' };
  var ROUTE_PALETTE = ['#0A8FB0', '#DD111E', '#6D5BD0', '#D9600A', '#0F8A5F', '#C2185B', '#1565C0', '#7B1FA2'];

  function curvePath(A, B, twist) {
    var mx = (A[0] + B[0]) / 2;
    var my = (A[1] + B[1]) / 2;
    var dx = B[0] - A[0];
    var dy = B[1] - A[1];
    var L = Math.hypot(dx, dy) || 1;
    var off = Math.min(90, L * 0.28) * (twist || 1);
    var cx = mx + (-dy / L) * off;
    var cy = my + (dx / L) * off - (L * 0.05);
    return 'M' + A[0] + ' ' + A[1] + 'Q' + cx + ' ' + cy + ' ' + B[0] + ' ' + B[1];
  }

  function activeMapItems() {
    var list = (state.pack && state.pack.latest) || [];
    return list.filter(function (item) {
      var b = statusBucket(item);
      if (b !== 'transit' && b !== 'pending') return false;
      if (!item.mapped || !item.origin_coords || !item.dest_coords) return false;
      return !!(xyPair(item.origin_coords) && xyPair(item.dest_coords));
    });
  }

  function itemProgress(item) {
    var status = Number(item && item.status) || 0;
    var progress = typeof item.progress === 'number' ? item.progress : (PROG[status] != null ? PROG[status] : 0.08);
    if (statusBucket(item) === 'pending') progress = Math.min(progress, 0.06);
    return Math.max(0.02, Math.min(1, progress));
  }

  function routeTipText(item) {
    return (item.grn_no || '') + ' · ' + (item.origin || '—') + ' → ' + (item.destination || '—');
  }

  function destMarkerHtml(item, B, isSel) {
    var gcn = item.grn_no || '';
    var tip = routeTipText(item);
    return '<g class="trk-dest-g' + (isSel ? ' is-sel' : '') + '" data-grn="' + esc(gcn) + '" data-tip="' + esc(tip) + '" style="cursor:pointer">'
      + '<circle class="trk-dest-hit" cx="' + B[0] + '" cy="' + B[1] + '" r="18" fill="transparent"/>'
      + '<circle class="trk-dest-dot" cx="' + B[0] + '" cy="' + B[1] + '" r="' + (isSel ? 5.5 : 4) + '" fill="#DD111E"/>'
      + '</g>';
  }

  function showMapTip(text, clientX, clientY) {
    var tip = document.getElementById('trkMapTip');
    var map = document.querySelector('.trk-map');
    if (!tip || !map || !text) return;
    tip.hidden = false;
    tip.textContent = text;
    var rect = map.getBoundingClientRect();
    var x = clientX - rect.left + 12;
    var y = clientY - rect.top - 34;
    if (x + tip.offsetWidth > rect.width - 8) x = clientX - rect.left - tip.offsetWidth - 12;
    if (y < 8) y = clientY - rect.top + 18;
    tip.style.left = Math.max(8, x) + 'px';
    tip.style.top = Math.max(8, y) + 'px';
  }

  function hideMapTip() {
    var tip = document.getElementById('trkMapTip');
    if (tip) tip.hidden = true;
  }

  function showRoute(selected) {
    ensureMapBase();
    hideMapTip();
    var note = document.getElementById('trkMapNote');
    var routes = document.getElementById('trkRoutes');
    var dests = document.getElementById('trkDests');
    var pin = document.getElementById('trkPin');
    var hint = document.getElementById('trkMapHint');
    if (state.anim) {
      cancelAnimationFrame(state.anim);
      state.anim = null;
    }
    if (routes) routes.innerHTML = '';
    if (dests) dests.innerHTML = '';
    if (pin) {
      pin.innerHTML = '';
      pin.removeAttribute('transform');
    }

    var active = activeMapItems();
    if (hint) {
      hint.textContent = active.length
        ? (active.length + ' booked / in-transit route' + (active.length === 1 ? '' : 's') + ' on map')
        : 'No booked or in-transit routes mapped';
    }
    if (!active.length) {
      if (note) {
        note.hidden = false;
        note.textContent = 'No booked or in-transit routes mapped.';
      }
      highlightStates(selected);
      return;
    }
    if (note) note.hidden = true;

    var selectedGrn = selected && selected.grn_no ? selected.grn_no : state.selectedGrn;
    var selectedOnMap = null;
    var html = '';
    var destHtml = '';

    active.forEach(function (item, idx) {
      var A = xyPair(item.origin_coords);
      var B = xyPair(item.dest_coords);
      if (!A || !B) return;
      var mkey = modeKey(item);
      var col = ROUTE_PALETTE[idx % ROUTE_PALETTE.length] || MODE_ROUTE_COL[mkey] || '#0A8FB0';
      var isSel = selectedGrn && item.grn_no === selectedGrn;
      if (isSel) selectedOnMap = item;
      var twist = 1 + ((idx % 5) - 2) * 0.16;
      var d = curvePath(A, B, twist);
      var tmp = document.createElementNS(NS, 'path');
      tmp.setAttribute('d', d);
      var len = tmp.getTotalLength() || 1;
      var prog = itemProgress(item);
      var drawn = Math.max(8, prog * len);
      var sw = isSel ? 3.2 : 2.1;
      var op = isSel ? 1 : 0.88;
      var tip = routeTipText(item);
      html += '<g class="trk-route-g' + (isSel ? ' is-sel' : '') + '" data-grn="' + esc(item.grn_no) + '" data-tip="' + esc(tip) + '" style="cursor:pointer">'
        + '<path d="' + d + '" fill="none" stroke="' + col + '" stroke-width="' + (isSel ? 1.8 : 1.2) + '" opacity="' + (isSel ? .35 : .22) + '"/>'
        + '<path d="' + d + '" fill="none" stroke="' + col + '" stroke-width="' + sw + '" stroke-linecap="round" stroke-dasharray="' + drawn + ' ' + len + '" opacity="' + op + '"/>'
        + '<circle class="trk-origin-dot" cx="' + A[0] + '" cy="' + A[1] + '" r="' + (isSel ? 5.5 : 4) + '" fill="#fff" stroke="#0A8FB0" stroke-width="2"/>'
        + moverHtml(d, prog, mkey, isSel)
        + (isSel
          ? (function () {
              var o = labelPoint(A, B, 'origin');
              var dpt = labelPoint(A, B, 'dest');
              return '<text class="trk-lbl" x="' + o[0] + '" y="' + o[1] + '" text-anchor="middle">' + esc(item.origin) + '</text>'
                + '<text class="trk-lbl" x="' + dpt[0] + '" y="' + dpt[1] + '" text-anchor="middle">' + esc(item.destination) + '</text>';
            })()
          : '')
        + '</g>';
      destHtml += destMarkerHtml(item, B, isSel);
    });

    if (routes) routes.innerHTML = html;

    /* If selected is delivered/other but still mapped, draw it as focus route too */
    if (selected && selected.mapped && selected.origin_coords && selected.dest_coords && !selectedOnMap) {
      var A2 = xyPair(selected.origin_coords);
      var B2 = xyPair(selected.dest_coords);
      if (A2 && B2 && routes) {
        var col2 = statusBucket(selected) === 'delivered' ? '#17A34A' : (MODE_ROUTE_COL[modeKey(selected)] || '#0A8FB0');
        var d2 = curvePath(A2, B2, 1);
        var tmp2 = document.createElementNS(NS, 'path');
        tmp2.setAttribute('d', d2);
        var len2 = tmp2.getTotalLength() || 1;
        var prog2 = itemProgress(selected);
        var drawn2 = Math.max(8, prog2 * len2);
        var tip2 = routeTipText(selected);
        routes.innerHTML += '<g class="trk-route-g is-sel" data-grn="' + esc(selected.grn_no) + '" data-tip="' + esc(tip2) + '" style="cursor:pointer">'
          + '<path d="' + d2 + '" fill="none" stroke="' + col2 + '" stroke-width="1.8" opacity=".35"/>'
          + '<path d="' + d2 + '" fill="none" stroke="' + col2 + '" stroke-width="3.2" stroke-linecap="round" stroke-dasharray="' + drawn2 + ' ' + len2 + '"/>'
          + '<circle class="trk-origin-dot" cx="' + A2[0] + '" cy="' + A2[1] + '" r="5.5" fill="#fff" stroke="#0A8FB0" stroke-width="2"/>'
          + moverHtml(d2, prog2, modeKey(selected), true)
          + (function () {
              var o2 = labelPoint(A2, B2, 'origin');
              var d2p = labelPoint(A2, B2, 'dest');
              return '<text class="trk-lbl" x="' + o2[0] + '" y="' + o2[1] + '" text-anchor="middle">' + esc(selected.origin) + '</text>'
                + '<text class="trk-lbl" x="' + d2p[0] + '" y="' + d2p[1] + '" text-anchor="middle">' + esc(selected.destination) + '</text>';
            })()
          + '</g>';
        destHtml += destMarkerHtml(selected, B2, true);
      }
    }

    if (dests) dests.innerHTML = destHtml;
    highlightStates(selected);
    startRouteMotion();
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

  function timelineHtml(status) {
    var st = Number(status) || 0;
    return STEPS.map(function (s, idx) {
      var done = st >= s[1];
      var next = STEPS[idx + 1];
      var now = st < 8 && done && !(next && st >= next[1]);
      if (st >= 8) {
        done = true;
        now = false;
      }
      return '<li class="' + (now ? 'now' : (done ? 'done' : '')) + '">' + esc(s[0]) + '</li>';
    }).join('');
  }

  function renderTimeline(status) {
    $('#trkTimeline').html(timelineHtml(status));
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
      var left = remainingKm(item);
      if (!item.mapped || left == null) {
        dist.textContent = 'Location not mapped';
      } else {
        dist.innerHTML = 'Approx. left <b>' + left.toLocaleString('en-IN') + ' km</b>';
      }
    }
    renderTimeline(item.status);
  }

  function renderTable(list) {
    var body = document.getElementById('trkTableBody');
    var empty = document.getElementById('trkTableEmpty');
    if (!body) return;
    if (!list || !list.length) {
      body.innerHTML = '';
      if (empty) empty.hidden = false;
      return;
    }
    if (empty) empty.hidden = true;
    ensureVehicleSymbols();
    body.innerHTML = list.map(function (item, i) {
      var mkey = modeKey(item);
      var tone = statusTone(item);
      var health = healthMeta(item);
      var pct = Math.round((Number(item.progress) || 0) * 100);
      var checked = state.sel[item.grn_no] ? ' checked' : '';
      var sel = i === state.selected ? ' sel' : '';
      return '<tr data-i="' + i + '" data-grn="' + esc(item.grn_no) + '" class="' + sel.trim() + '">'
        + '<td><input type="checkbox" data-c="' + esc(item.grn_no) + '"' + checked + ' aria-label="Select ' + esc(item.grn_no) + '"></td>'
        + '<td class="g">' + esc(item.grn_no) + '</td>'
        + '<td><span class="trk-mode-cell"><span class="trk-vi">' + modeSpriteHtml(mkey) + '</span>'
        + '<span><b style="font-family:var(--trk-fd)">' + esc(modePillLabel(item)) + '</b>'
        + '<small>' + esc(item.mode || mkey) + '</small></span></span></td>'
        + '<td><span class="trk-rt">' + esc(cityCode(item.origin)) + '<s>→</s>' + esc(cityCode(item.destination)) + '</span>'
        + '<small>' + esc(item.origin) + ' to ' + esc(item.destination) + '</small></td>'
        + '<td>' + esc(item.client || '—') + '</td>'
        + '<td><span class="trk-pill" style="--c:' + tone.fg + ';--bg:' + tone.bg + '">' + esc(item.status_label || '—') + '</span>'
        + '<div class="trk-pb" style="--c:' + tone.bar + '"><i style="width:' + pct + '%"></i></div></td>'
        + '<td>' + esc(formatINR(item.freight, item.freight_fmt)) + '</td>'
        + '<td><span class="trk-bd" style="--c:' + health.color + '">' + esc(health.label) + '</span></td>'
        + '</tr>';
    }).join('');
  }

  function openDrawerFor(item) {
    var dr = document.getElementById('trkDrawer');
    var sc = document.getElementById('trkScrim');
    if (!dr || !item) return;
    var mkey = modeKey(item);
    var col = statusColor(item);
    var health = healthMeta(item);
    var pct = Math.round((Number(item.progress) || 0) * 100);
    var leftKm = remainingKm(item);
    dr.style.setProperty('--c', col);
    dr.hidden = false;
    dr.innerHTML =
      '<div class="trk-drawer-top">'
      + '<b>' + esc(item.grn_no || '—') + '</b>'
      + '<button type="button" class="trk-btn" data-drawer-close="1">Close</button>'
      + '</div>'
      + '<div class="trk-drawer-art">' + modeArtHtml(mkey, modePillLabel(item)) + '</div>'
      + '<span class="trk-fno">' + esc(modePillLabel(item)) + '</span>'
      + '<div class="trk-big"><b>' + esc(cityCode(item.origin)) + '</b><u></u><b>' + esc(cityCode(item.destination)) + '</b></div>'
      + '<div><span class="trk-bd" style="--c:' + health.color + '">' + esc(health.label) + '</span></div>'
      + '<div class="trk-fx">'
      + '<span>Client<b>' + esc(item.client || '—') + '</b></span>'
      + '<span>Mode<b>' + esc(item.mode || modePillLabel(item)) + '</b></span>'
      + '<span>Origin<b>' + esc(item.origin || '—') + '</b></span>'
      + '<span>Destination<b>' + esc(item.destination || '—') + '</b></span>'
      + '<span>Booked<b>' + esc(item.grn_date || item.iso || '—') + '</b></span>'
      + '<span>Freight<b>' + esc(formatINR(item.freight, item.freight_fmt)) + '</b></span>'
      + '<span>Actual progress<b>' + pct + '%</b></span>'
      + '<span>Distance left<b>' + (leftKm != null ? (leftKm.toLocaleString('en-IN') + ' km') : '—') + '</b></span>'
      + '</div>'
      + '<ul class="trk-steps">' + timelineHtml(item.status) + '</ul>'
      + '<div class="trk-acts">'
      + (item.track_url ? '<a class="trk-btn pri" href="' + esc(item.track_url) + '" target="_blank" rel="noopener">Track</a>' : '')
      + '<button type="button" class="trk-btn" data-drawer-close="1">Close</button>'
      + '</div>';
    requestAnimationFrame(function () {
      dr.classList.add('on');
      if (sc) { sc.hidden = false; sc.classList.add('on'); }
    });
  }

  function closeDrawer() {
    var dr = document.getElementById('trkDrawer');
    var sc = document.getElementById('trkScrim');
    if (dr) {
      dr.classList.remove('on');
      setTimeout(function () { if (dr && !dr.classList.contains('on')) dr.hidden = true; }, 250);
    }
    if (sc) {
      sc.classList.remove('on');
      setTimeout(function () { if (sc && !sc.classList.contains('on')) sc.hidden = true; }, 200);
    }
  }

  function selectIndex(idx, openDrawer) {
    var list = filteredLatest();
    var hint = document.getElementById('trkMapHint');
    var cnt = document.getElementById('trkCount');
    var total = ((state.pack && state.pack.latest) || []).length;
    if (cnt) cnt.textContent = list.length + ' of ' + total + ' consignments';
    if (!list.length) {
      state.selected = 0;
      state.selectedGrn = null;
      renderLatest(null);
      showRoute(null);
      renderTable([]);
      if (hint) hint.textContent = 'No consignments match these filters';
      return;
    }
    if (idx < 0 || idx >= list.length) idx = 0;
    state.selected = idx;
    var item = list[idx];
    state.selectedGrn = item.grn_no || null;
    renderLatest(item);
    showRoute(item);
    renderTable(list);
    if (hint) {
      hint.textContent = (item.grn_no || 'Route') + ' · ' + (item.origin || '—') + ' → ' + (item.destination || '—');
    }
    if (openDrawer) openDrawerFor(item);
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
    renderNeeds();
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

      $(document).on('click', '#trkTableBody tr', function (e) {
        if ($(e.target).closest('input[type="checkbox"]').length) return;
        var idx = parseInt($(this).attr('data-i'), 10);
        if (isNaN(idx)) return;
        selectIndex(idx, true);
      });

      $(document).on('click', '#trkRoutes .trk-route-g, #trkDests .trk-dest-g', function () {
        var grn = $(this).attr('data-grn');
        if (!grn) return;
        selectByGrn(grn, false);
      });

      $(document).on('mousemove', '#trkDests .trk-dest-g, #trkRoutes .trk-route-g', function (e) {
        var text = $(this).attr('data-tip') || $(this).closest('[data-tip]').attr('data-tip');
        if (!text) return;
        showMapTip(text, e.clientX, e.clientY);
      });

      $(document).on('mouseleave', '#trkDests .trk-dest-g, #trkRoutes .trk-route-g', function () {
        hideMapTip();
      });

      $(document).on('mousemove', '#land path', function (e) {
        if ($(e.target).closest('#trkRoutes, #trkDests').length) return;
        var name = $(this).attr('data-s') || $(this).children('title').text();
        if (!name) return;
        showMapTip(name, e.clientX, e.clientY);
      });

      $(document).on('mouseleave', '#land path', function (e) {
        var rel = e.relatedTarget;
        if (rel && $(rel).closest('#land, #trkRoutes, #trkDests').length) return;
        hideMapTip();
      });

      $(document).on('click', '#trkTableBody input[data-c]', function (e) {
        e.stopPropagation();
        var id = $(this).attr('data-c');
        if (!id) return;
        if (this.checked) state.sel[id] = 1;
        else delete state.sel[id];
      });

      $(document).on('change', '#trkSelectAll', function () {
        var list = filteredLatest();
        var on = this.checked;
        state.sel = {};
        if (on) {
          list.forEach(function (item) {
            if (item.grn_no) state.sel[item.grn_no] = 1;
          });
        }
        renderTable(list);
      });

      $(document).on('click', '#trkFilters [data-f-status]', function () {
        applyFilters($(this).attr('data-f-status'), null, 'all');
      });

      $(document).on('click', '#trkFilters [data-f-view]', function () {
        applyFilters(null, null, $(this).attr('data-f-view'));
      });

      $(document).on('click', '#trkFilters [data-f-mode]', function () {
        applyFilters(null, $(this).attr('data-f-mode'), null);
      });

      $(document).on('click', '.trk-k[data-filter-status]', function () {
        applyFilters($(this).attr('data-filter-status'), null, 'all');
      });

      $(document).on('click', '#trkNeeds [data-need-i]', function () {
        var i = parseInt($(this).attr('data-need-i'), 10);
        var rows = window.__trkNeeds || [];
        if (!rows[i] || !rows[i].item) return;
        selectByGrn(rows[i].item.grn_no, true);
      });

      $(document).on('click', '[data-drawer-close], #trkScrim', function () {
        closeDrawer();
      });

      $(document).on('input', '#trkSearch', function () {
        state.q = $(this).val() || '';
        applyFilters(null, null, null);
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDrawer();
        if (e.key === '/' && document.activeElement && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
          var inp = document.getElementById('trkSearch');
          if (inp) {
            e.preventDefault();
            inp.focus();
          }
        }
      });

      document.addEventListener('visibilitychange', function () {
        if (!document.hidden && state.pack) load({ silent: true });
      });

      load();
    },
    stop: stopRefresh
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { window.CargoOps.init(); });
  } else {
    window.CargoOps.init();
  }
})(window, window.jQuery);
