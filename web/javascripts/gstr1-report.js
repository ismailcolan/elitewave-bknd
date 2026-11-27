(function (window, $) {
  'use strict';

  function qs(params) {
    return $.param(params || {});
  }

  function periodParams(root) {
    var $r = $(root || document);
    return {
      gstin: $('#gstr1_gstin', $r).val() || '',
      from_date: $('#gstr1_from', $r).val() || '',
      to_date: $('#gstr1_to', $r).val() || ''
    };
  }

  function showSkeleton($el) {
    $el.html('<div class="gstr1-skeleton" aria-hidden="true"></div>');
  }

  function showError($el, msg) {
    $el.html('<div class="ew-mis-empty-state"><p>' + (msg || 'Unable to load data.') + ' <button type="button" class="ew-btn-v2 ew-btn-v2-outline gstr1-retry">Retry</button></p></div>');
  }

  function isDataTableBound($table) {
    if (!$table || !$table.length) return false;
    if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($table[0])) {
      return true;
    }
    if ($.fn.DataTable && $.fn.DataTable.isDataTable && $.fn.DataTable.isDataTable($table)) {
      return true;
    }
    return false;
  }

  function destroyDataTable($table) {
    if (!$table || !$table.length) return;
    if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($table[0])) {
      $table.dataTable().fnDestroy();
      return;
    }
    if ($.fn.DataTable && $.fn.DataTable.isDataTable && $.fn.DataTable.isDataTable($table)) {
      $table.DataTable().destroy();
    }
  }

  function wireDataTable($table, searchInputSel) {
    if (!$table.length) return;
    if (!$.fn.dataTable && !$.fn.DataTable) return;

    destroyDataTable($table);

    var legacyApi = null;
    var modernApi = null;

    if ($.fn.dataTable) {
      legacyApi = $table.dataTable({
        sPaginationType: 'full_numbers',
        iDisplayLength: 25,
        aaSorting: [],
        bFilter: true,
        bInfo: true,
        sDom: 'lrtip'
      });
    } else {
      modernApi = $table.DataTable({
        paging: true,
        pageLength: 25,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        order: []
      });
    }

    if (searchInputSel && $(searchInputSel).length) {
      $(searchInputSel).off('input.gstr1MisSearch keyup.gstr1MisSearch').on('input.gstr1MisSearch keyup.gstr1MisSearch', function () {
        var q = $(this).val();
        if (legacyApi) {
          legacyApi.fnFilter(q);
        } else if (modernApi) {
          modernApi.search(q).draw();
        }
      });
    }
    if (window.EwMisReportView && EwMisReportView.bindTableSearch) {
      EwMisReportView.bindTableSearch(searchInputSel, '#' + ($table.attr('id') || ''));
    }
  }

  function parseExtra(raw) {
    var extra = raw || {};
    if (typeof extra === 'string') {
      try {
        extra = JSON.parse(extra);
      } catch (e) {
        extra = {};
      }
    }
    return extra;
  }

  function renderVoucherSummary($host, counts, notRelevant) {
    if (!$host || !$host.length) return;
    var c = counts || {};
    var nr = notRelevant || {};
    function metricRow(text, label, page, extra, count, subRow) {
      var n = parseInt(count, 10) || 0;
      var extraAttr = JSON.stringify(extra || {}).replace(/</g, '\\u003c');
      var subClass = subRow ? ' gstr1-metric-row--sub' : '';
      return '<li><button type="button" class="gstr1-metric-row gstr1-drill-btn' + subClass + '" data-page="' + page + '" data-extra=\'' + extraAttr + '\' aria-label="' + label + ' ' + n + '">' +
        '<span class="gstr1-metric-row-label">' + text + '</span>' +
        '<span class="gstr1-metric-row-value">' + n + '</span></button></li>';
    }
    var html = '<div class="ew-card"><div class="ew-card-toolbar"><h2>Voucher summary</h2></div><div class="ew-form-body"><ul class="gstr1-metric-list gstr1-metric-list--bordered">' +
      metricRow('Total vouchers', 'Total vouchers', 'gstr1_voucher_register.php', {}, c.total || 0) +
      metricRow('Included in return', 'Included', 'gstr1_voucher_register.php', { bucket: 'included' }, c.included || 0) +
      metricRow('No action required', 'No action', 'gstr1_voucher_register.php', { bucket: 'included', included_sub: 'no_action' }, c.included_no_action || 0, true) +
      metricRow('Action pending', 'Action pending', 'gstr1_voucher_register.php', { bucket: 'included', included_sub: 'action_pending', exception_only: 1 }, c.included_action_pending || 0, true) +
      metricRow('Not relevant for this return', 'Not relevant', 'gstr1_not_relevant.php', {}, c.not_relevant || 0) +
      metricRow('Uncertain transactions', 'Uncertain', 'gstr1_voucher_register.php', { bucket: 'uncertain' }, c.uncertain || 0) +
      '</ul><p class="gstr1-metric-footnote">Non-GST ' + (nr.non_gst_total || 0) + ' (inventory ' + (nr.inventory || 0) + ' + other ' + (nr.other_transactions || 0) + ') · Other returns ' + (nr.other_returns || 0) + '</p></div></div>';
    $host.html(html);
  }

  function bindDrillClicks($root, options) {
    if (!$root || !$root.length) return;
    options = options || {};
    var source = options.source || 'gstr1';

    $root.off('click.gstr1Drill', '.gstr1-drill-btn').on('click.gstr1Drill', '.gstr1-drill-btn', function () {
      var $btn = $(this);
      var page = $btn.attr('data-page');
      if (page) {
        window.location.href = Gstr1Report.drillUrl(page, parseExtra($btn.data('extra')));
        return;
      }
      if ($btn.attr('data-section') !== undefined) {
        var sec = $btn.data('section');
        var target = 'gstr1_voucher_register.php';
        var extra = { bucket: 'included', section: sec };
        if (sec === '4A' || sec === '4B') {
          target = 'gstr1_b2b.php';
          extra = {};
        } else if (sec === '12') {
          target = 'gstr1_hsn.php';
          extra = {};
        }
        window.location.href = Gstr1Report.drillUrl(target, extra);
        return;
      }
      if ($btn.attr('data-type') !== undefined) {
        var drill = { voucher_type: $btn.data('type'), source: source };
        var bucket = $btn.data('bucket');
        if (bucket) drill.bucket = bucket;
        var sub = $btn.data('sub');
        if (sub) drill.included_sub = sub;
        window.location.href = Gstr1Report.drillUrl('gstr1_voucher_register.php', drill);
      }
    });
  }

  function fetchJson(cmd, params, done, fail) {
    $.ajax({
      url: 'gstr1_data.php',
      data: $.extend({ cmd: cmd }, params),
      dataType: 'json',
      cache: false,
      timeout: 120000
    })
      .done(function (data) {
        if (done) done(data);
      })
      .fail(function (xhr, status) {
        if (fail) fail(xhr, status);
      });
  }

  window.Gstr1Report = {
    periodParams: periodParams,
    fetch: fetchJson,
    wireDataTable: wireDataTable,
    showSkeleton: showSkeleton,
    showError: showError,
    drillUrl: function (page, extra) {
      var base = periodParams();
      if (extra && extra.gstin) base.gstin = extra.gstin;
      if (extra && extra.from_date) base.from_date = extra.from_date;
      if (extra && extra.to_date) base.to_date = extra.to_date;
      return page + '?' + qs($.extend({}, base, extra || {}));
    },
    renderVoucherSummary: renderVoucherSummary,
    bindDrillClicks: bindDrillClicks
  };

  $(document).on('click', '.gstr1-retry', function () {
    $(document).trigger('gstr1:reload');
  });

  $(function () {
    if ($('[id^="gstr1_"]').length) {
      $('.loading-page, .form-data-saving').hide();
      $(window).on('load', function () {
        $('.loading-page, .form-data-saving').hide();
      });
    }
  });
})(window, jQuery);