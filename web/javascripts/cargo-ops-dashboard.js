(function (window, $) {
  var COLOR = {
    navy: '#0A1E3D',
    navy2: '#132D54',
    accent: '#DD111E',
    teal: '#0891B2',
    green: '#16A34A',
    orange: '#EA580C',
    muted: '#6B7A8D',
    grid: '#EEF1F4'
  };
  var STATUS_CLASS = {
    Delivered: 'st-ok',
    'In transit': 'st-go',
    'Pending pickup': 'st-wait',
    Delayed: 'st-bad'
  };

  var state = {
    period: 'monthly',
    pack: null,
    loading: false,
    charts: {}
  };

  function formatINR(v) {
    var n = Math.round(Number(v) || 0);
    var abs = Math.abs(n);
    var sign = n < 0 ? '-' : '';
    if (abs >= 1e7) return sign + '\u20B9' + (abs / 1e7).toFixed(2) + ' Cr';
    if (abs >= 1e5) return sign + '\u20B9' + (abs / 1e5).toFixed(2) + ' L';
    return sign + '\u20B9' + abs.toLocaleString('en-IN');
  }
  function formatCount(v) {
    return Math.round(Number(v) || 0).toLocaleString('en-IN');
  }
  function formatShortDate(iso) {
    if (!iso) return '';
    var d = new Date(String(iso).indexOf('T') >= 0 ? iso : iso + 'T00:00:00');
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
  }
  function axisMoney(v) {
    var abs = Math.abs(v);
    if (abs >= 1e7) return (v / 1e7).toFixed(1) + 'Cr';
    if (abs >= 1e5) return (v / 1e5).toFixed(1) + 'L';
    if (abs >= 1e3) return (v / 1e3).toFixed(0) + 'k';
    return String(Math.round(v));
  }

  function goLiveIdx(pack) {
    var n = pack && pack.goLiveMonth != null ? parseInt(pack.goLiveMonth, 10) : 4;
    return isNaN(n) ? 4 : n;
  }

  function liveMonths(pack) {
    var months = (pack.yearData && pack.yearData.months) || [];
    var from = goLiveIdx(pack);
    return months.filter(function (m) { return Number(m.idx) >= from; });
  }

  function sumList(items) {
    var out = {
      consignments: 0, invoiceCount: 0, invoiceAmount: 0, paymentCount: 0, paymentAmount: 0,
      expenseAmount: 0, expenseBreakdown: {}, delivered: 0, delayed: 0, inTransit: 0, pending: 0
    };
    (items || []).forEach(function (mo) {
      ['consignments', 'invoiceCount', 'invoiceAmount', 'paymentCount', 'paymentAmount', 'expenseAmount', 'delivered', 'delayed', 'inTransit', 'pending'].forEach(function (k) {
        out[k] += Number(mo[k] || 0);
      });
      var br = mo.expenseBreakdown || {};
      Object.keys(br).forEach(function (cat) {
        out.expenseBreakdown[cat] = (out.expenseBreakdown[cat] || 0) + Number(br[cat] || 0);
      });
    });
    return out;
  }

  function flattenDays(months) {
    var days = [];
    (months || []).forEach(function (mo) {
      (mo.days || []).forEach(function (d) {
        days.push($.extend({ monthName: mo.name }, d));
      });
    });
    return days;
  }

  function weekBuckets(months) {
    var days = flattenDays(months);
    var slice = days.slice(-28);
    if (!slice.length) return [];
    var weeks = [];
    for (var i = 0; i < slice.length; i += 7) {
      var chunk = slice.slice(i, i + 7);
      var first = chunk[0];
      var last = chunk[chunk.length - 1];
      var row = sumList(chunk);
      row.name = formatShortDate(first.iso) + '–' + formatShortDate(last.iso);
      weeks.push(row);
    }
    return weeks;
  }

  function periodRows(pack) {
    var months = liveMonths(pack);
    if (state.period === 'weekly') return weekBuckets(months);
    return months;
  }

  function destroyChart(id) {
    if (state.charts[id]) {
      state.charts[id].destroy();
      state.charts[id] = null;
    }
  }

  function hcBase() {
    return {
      credits: { enabled: false },
      title: { text: '' },
      chart: { backgroundColor: 'transparent', style: { fontFamily: 'Inter, "Segoe UI", sans-serif' } },
      legend: { itemStyle: { fontSize: '11px', color: COLOR.muted, fontWeight: '500' } }
    };
  }

  function pieLegend(id, items, money) {
    var total = items.reduce(function (a, d) { return a + Number(d.y || 0); }, 0);
    var html = items.map(function (d) {
      var pct = total ? Math.round((Number(d.y || 0) / total) * 100) : 0;
      var val = money ? formatINR(d.y) : formatCount(d.y);
      return '<span><i style="background:' + d.color + '"></i>' + d.name
        + ' <b>' + val + '</b> · ' + pct + '%</span>';
    }).join('');
    var node = document.getElementById(id);
    if (node) node.innerHTML = html || '';
  }

  function emptyChart(el, msg) {
    destroyChart(el);
    var node = document.getElementById(el);
    if (node) node.innerHTML = '<div class="cld-loading">' + msg + '</div>';
    var legendId = { cldDonut: 'cldDonutLegend', cldColChart: 'cldColLegend', cldExpChart: 'cldExpLegend' }[el];
    if (legendId) {
      var lg = document.getElementById(legendId);
      if (lg) lg.innerHTML = '';
    }
  }

  function drawOps(rows) {
    var el = 'cldOpsChart';
    if (typeof Highcharts === 'undefined') return;
    if (!rows.length) { emptyChart(el, 'No booking data in this period.'); return; }
    var node = document.getElementById(el);
    if (node) node.innerHTML = '';
    destroyChart(el);
    state.charts[el] = Highcharts.chart(el, $.extend(true, hcBase(), {
      chart: { type: 'column', height: 270 },
      xAxis: { categories: rows.map(function (r) { return r.name; }), lineColor: COLOR.grid, tickLength: 0, labels: { style: { fontSize: '11px', color: COLOR.muted } } },
      yAxis: { min: 0, title: { text: '' }, gridLineColor: COLOR.grid, labels: { style: { color: COLOR.muted, fontSize: '11px' } } },
      plotOptions: { column: { borderRadius: 3, pointPadding: 0.12, groupPadding: 0.1, borderWidth: 0 } },
      series: [
        { name: 'Bookings', data: rows.map(function (r) { return Number(r.consignments || 0); }), color: COLOR.navy },
        { name: 'Delivered', data: rows.map(function (r) { return Number(r.delivered || 0); }), color: COLOR.green },
        { name: 'Invoices', data: rows.map(function (r) { return Number(r.invoiceCount || 0); }), color: COLOR.teal }
      ]
    }));
  }

  function drawMoney(rows) {
    var el = 'cldMonChart';
    if (typeof Highcharts === 'undefined') return;
    if (!rows.length) { emptyChart(el, 'No money movement in this period.'); return; }
    var node = document.getElementById(el);
    if (node) node.innerHTML = '';
    destroyChart(el);
    state.charts[el] = Highcharts.chart(el, $.extend(true, hcBase(), {
      chart: { type: 'column', height: 230 },
      xAxis: { categories: rows.map(function (r) { return r.name; }), lineColor: COLOR.grid, tickLength: 0, labels: { style: { fontSize: '11px', color: COLOR.muted } } },
      yAxis: { min: 0, title: { text: '' }, gridLineColor: COLOR.grid, labels: { formatter: function () { return axisMoney(this.value); }, style: { color: COLOR.muted, fontSize: '11px' } } },
      plotOptions: { column: { borderRadius: 3, pointPadding: 0.12, groupPadding: 0.1, borderWidth: 0 } },
      series: [
        { name: 'Revenue', data: rows.map(function (r) { return Number(r.invoiceAmount || 0); }), color: COLOR.navy },
        { name: 'Collected', data: rows.map(function (r) { return Number(r.paymentAmount || 0); }), color: COLOR.green },
        { name: 'Expense', data: rows.map(function (r) { return Number(r.expenseAmount || 0); }), color: COLOR.teal }
      ]
    }));
  }

  function drawDonut(scope) {
    var el = 'cldDonut';
    if (typeof Highcharts === 'undefined') return;
    var all = [
      { name: 'Delivered', y: Number(scope.delivered || 0), color: COLOR.green },
      { name: 'In transit', y: Number(scope.inTransit || 0), color: COLOR.teal },
      { name: 'Pending pickup', y: Number(scope.pending || 0), color: COLOR.orange },
      { name: 'Delayed', y: Number(scope.delayed || 0), color: COLOR.accent }
    ];
    var data = all.filter(function (d) { return d.y > 0; });
    var total = data.reduce(function (a, d) { return a + d.y; }, 0);
    pieLegend('cldDonutLegend', all, false);
    if (!total) { emptyChart(el, 'No consignments in this period.'); pieLegend('cldDonutLegend', all, false); return; }
    var node = document.getElementById(el);
    if (node) node.innerHTML = '';
    destroyChart(el);
    state.charts[el] = Highcharts.chart(el, $.extend(true, hcBase(), {
      chart: { type: 'pie', height: 220, spacingBottom: 8 },
      legend: { enabled: false },
      title: {
        text: formatCount(total) + '<br/><span style="font-size:11px;color:#6B7A8D;font-weight:400">GCNs</span>',
        useHTML: true, align: 'center', verticalAlign: 'middle', y: 12,
        style: { fontSize: '20px', fontWeight: '800', color: COLOR.navy }
      },
      tooltip: { pointFormat: '{point.y} ({point.percentage:.0f}%)' },
      plotOptions: { pie: { innerSize: '68%', size: '78%', dataLabels: { enabled: false }, borderWidth: 0 } },
      series: [{ name: 'Status', data: data }]
    }));
  }

  function drawCol(scope) {
    var el = 'cldColChart';
    if (typeof Highcharts === 'undefined') return;
    var collected = Math.max(0, Number(scope.paymentAmount || 0));
    var outstanding = Math.max(0, Number(scope.invoiceAmount || 0) - collected);
    var all = [
      { name: 'Collected', y: collected, color: COLOR.green },
      { name: 'Outstanding', y: outstanding, color: COLOR.accent }
    ];
    pieLegend('cldColLegend', all, true);
    if (collected + outstanding <= 0) { emptyChart(el, 'No invoices in this period.'); pieLegend('cldColLegend', all, true); return; }
    var node = document.getElementById(el);
    if (node) node.innerHTML = '';
    destroyChart(el);
    state.charts[el] = Highcharts.chart(el, $.extend(true, hcBase(), {
      chart: { type: 'pie', height: 180, spacingBottom: 8 },
      legend: { enabled: false },
      title: { text: '' },
      tooltip: { pointFormat: '{point.y:,.0f}' },
      plotOptions: { pie: { innerSize: '62%', size: '78%', dataLabels: { enabled: false }, borderWidth: 0 } },
      series: [{
        name: 'Amount',
        data: all.filter(function (d) { return d.y > 0; })
      }]
    }));
  }

  function drawExp(scope) {
    var el = 'cldExpChart';
    if (typeof Highcharts === 'undefined') return;
    var br = scope.expenseBreakdown || {};
    var entries = Object.keys(br).map(function (k) { return { name: k, y: Number(br[k] || 0) }; }).filter(function (d) { return d.y > 0; });
    if (!entries.length && Number(scope.expenseAmount || 0) > 0) {
      entries = [{ name: 'Expenses', y: Number(scope.expenseAmount) }];
    }
    var palette = [COLOR.navy, COLOR.teal, COLOR.orange, COLOR.accent, COLOR.navy2, COLOR.muted];
    entries.forEach(function (e, i) { e.color = palette[i % palette.length]; });
    pieLegend('cldExpLegend', entries, true);
    if (!entries.length) { emptyChart(el, 'No expenses in this period.'); return; }
    var node = document.getElementById(el);
    if (node) node.innerHTML = '';
    destroyChart(el);
    state.charts[el] = Highcharts.chart(el, $.extend(true, hcBase(), {
      chart: { type: 'pie', height: 180, spacingBottom: 8 },
      legend: { enabled: false },
      title: { text: '' },
      tooltip: { pointFormat: '{point.percentage:.0f}% · {point.y:,.0f}' },
      plotOptions: { pie: { innerSize: '62%', size: '78%', dataLabels: { enabled: false }, borderWidth: 0 } },
      series: [{ name: 'Expense', data: entries }]
    }));
  }

  function renderFlow(scope) {
    var pending = Number(scope.pending || 0) + Number(scope.inTransit || 0);
    var cells = [
      { label: 'Bookings', val: formatCount(scope.consignments), sub: 'GCNs created' },
      { label: 'Delivered', val: formatCount(scope.delivered), sub: 'Status = delivered' },
      { label: 'Pending', val: formatCount(pending), sub: 'Pickup + in transit' },
      { label: 'Invoices', val: formatCount(scope.invoiceCount), sub: 'GST invoices final' },
      { label: 'Revenue', val: formatINR(scope.invoiceAmount), sub: 'Invoice grand total', money: true },
      { label: 'Collected', val: formatINR(scope.paymentAmount), sub: 'Payments received', money: true }
    ];
    $('#cldFlow').html(cells.map(function (c) {
      return '<div class="cld-step' + (c.money ? ' money' : '') + '"><b>' + c.label + '</b><strong>' + c.val + '</strong><em>' + c.sub + '</em></div>';
    }).join(''));
  }

  function renderAttn(pack, scope) {
    var ex = pack.exceptions || {};
    var outstanding = Math.max(0, Number(scope.invoiceAmount || 0) - Number(scope.paymentAmount || 0));
    var rows = [
      { label: 'Delayed > 3 days', hint: 'Not delivered, booking older than 3 days', n: ex.delayed != null ? ex.delayed : scope.delayed },
      { label: 'Pending pickup', hint: 'Status still at origin / not loaded', n: ex.pendingPickup != null ? ex.pendingPickup : scope.pending },
      { label: 'In transit', hint: 'Picked up, not yet delivered', n: ex.inTransit != null ? ex.inTransit : scope.inTransit },
      { label: 'Delivered, not invoiced', hint: 'Ready GCNs waiting GST invoice', n: ex.unbilled || 0 },
      { label: 'Draft invoices', hint: 'GST invoices not yet final', n: ex.draftInvoices || 0 },
      { label: 'Outstanding billed', hint: 'Invoice value still uncollected', n: formatINR(outstanding), raw: true }
    ];
    $('#cldAttn').html(rows.map(function (r) {
      return '<div class="cld-chip"><span>' + r.label + '<small>' + r.hint + '</small></span><b>' + (r.raw ? r.n : formatCount(r.n)) + '</b></div>';
    }).join(''));
  }

  function renderRecent(recent) {
    var body = (recent || []).map(function (r) {
      var cls = STATUS_CLASS[r.status] || 'st-go';
      return '<tr><td>' + (r.id || '') + '</td><td>' + (r.customer || '—') + '</td>'
        + '<td>' + (r.origin || '—') + ' \u2192 ' + (r.destination || '—') + '</td>'
        + '<td class="st ' + cls + '">' + (r.status || '') + '</td>'
        + '<td class="num">' + formatINR(r.amount) + '</td></tr>';
    }).join('');
    if (!body) body = '<tr><td colspan="5" class="hint">No consignments yet in this period.</td></tr>';
    $('#cldRecentBody').html(body);
  }

  function render() {
    var pack = state.pack;
    if (!pack) return;
    var rows = periodRows(pack);
    var scope = sumList(rows);
    var weekly = state.period === 'weekly';
    $('#opsHint').text(weekly ? 'Last four weeks of live counts' : 'May through today — live counts');
    $('#monHint').text(weekly ? 'Same weeks in rupees' : 'May through today in rupees');
    renderFlow(scope);
    drawOps(rows);
    drawDonut(scope);
    drawMoney(rows);
    drawCol(scope);
    drawExp(scope);
    renderAttn(pack, scope);
    renderRecent(pack.recent || []);
    setTimeout(function () {
      Object.keys(state.charts).forEach(function (id) {
        if (state.charts[id] && state.charts[id].reflow) state.charts[id].reflow();
      });
    }, 40);
  }

  function load() {
    $('#cldBoot').show();
    $('#cldShell').hide();
    $.ajax({
      url: 'dashboard_ops_json.php',
      type: 'GET',
      dataType: 'json',
      data: { year: new Date().getFullYear() },
      success: function (res) {
        if (!res || res.status !== 0) {
          $('#cldBoot').html('<p class="cld-loading">' + ((res && res.message) ? res.message : 'Could not load dashboard data.') + '</p>');
          return;
        }
        state.pack = res;
        $('#cldBoot').hide();
        $('#cldShell').show();
        render();
      },
      error: function () {
        $('#cldBoot').html('<p class="cld-loading">Could not load dashboard data.</p>');
      }
    });
  }

  window.CargoOps = {
    init: function () {
      if (!document.getElementById('cargoOpsRoot') || state.pack || state.loading) return;
      state.loading = true;
      if (typeof Highcharts !== 'undefined') {
        Highcharts.setOptions({ chart: { style: { fontFamily: 'Inter, "Segoe UI", sans-serif' } } });
      }
      $('#cldPeriod').on('click', 'button', function () {
        state.period = $(this).attr('data-p');
        $('#cldPeriod button').removeClass('on');
        $(this).addClass('on');
        if (state.pack) render();
      });
      $(window).on('resize', function () {
        Object.keys(state.charts).forEach(function (id) {
          if (state.charts[id] && state.charts[id].reflow) state.charts[id].reflow();
        });
      });
      load();
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { window.CargoOps.init(); });
  } else {
    window.CargoOps.init();
  }
})(window, window.jQuery);
