/**
 * MIS report two-view UX:
 * 1) Filters screen
 * 2) After Search → compact filter bar + searchable results table
 */
(function (window, $) {
  'use strict';

  function pad2(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function formatDmy(d) {
    return pad2(d.getDate()) + '-' + pad2(d.getMonth() + 1) + '-' + d.getFullYear();
  }

  function parseDmy(str) {
    var m = String(str || '').match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (!m) return null;
    var d = new Date(parseInt(m[3], 10), parseInt(m[2], 10) - 1, parseInt(m[1], 10));
    return isNaN(d.getTime()) ? null : d;
  }

  function setDateInput($el, dmy) {
    if (!$el || !$el.length) return;
    $el.val(dmy);
    $el.trigger('change');
  }

  function applyQuickRange(range, fromSel, toSel) {
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var from = new Date(today);
    var to = new Date(today);

    if (range === 'today') {
      /* same day */
    } else if (range === 'week') {
      var day = today.getDay();
      var diff = day === 0 ? 6 : day - 1;
      from.setDate(today.getDate() - diff);
    } else if (range === 'month') {
      from = new Date(today.getFullYear(), today.getMonth(), 1);
    } else if (range === 'last_month') {
      from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      to = new Date(today.getFullYear(), today.getMonth(), 0);
    } else {
      return;
    }

    setDateInput($(fromSel), formatDmy(from));
    setDateInput($(toSel), formatDmy(to));
  }

  function showFilters($root) {
    $root.find('#ew_mis_filters_view').show();
    $root.find('#ew_mis_results_view').hide();
    $root.removeClass('ew-mis-showing-results');
  }

  function showResults($root, opts) {
    opts = opts || {};
    $root.find('#ew_mis_date_pill').text(opts.datePill || '');
    $root.find('#ew_mis_active_summary').text(opts.summary || '');
    if (opts.countText !== undefined) {
      $root.find('#report_count').text(opts.countText || '');
    }
    if (opts.resultsTitle) {
      $root.find('#ew_mis_results_title').text(opts.resultsTitle);
    }
    $root.find('#ew_mis_filters_view').hide();
    $root.find('#ew_mis_results_view').show();
    $root.addClass('ew-mis-showing-results');
    window.setTimeout(function () {
      if (window.applyEwListLayout) {
        window.applyEwListLayout();
      }
    }, 120);
  }

  function bindTableSearch(inputSel, tableSel) {
    $(document).off('keyup.ewMisSearch input.ewMisSearch', inputSel)
      .on('keyup.ewMisSearch input.ewMisSearch', inputSel, function () {
        var q = $(this).val();
        var $table = $(tableSel);
        if (!$table.length) return;
        if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($table[0])) {
          $table.dataTable().fnFilter(q);
        } else if ($.fn.DataTable && $.fn.DataTable.isDataTable && $.fn.DataTable.isDataTable($table)) {
          $table.DataTable().search(q).draw();
        }
      });
  }

  function wire(cfg) {
    cfg = cfg || {};
    var $root = $(cfg.root || '.ew-page-v2--mis-report').first();
    if (!$root.length) return;

    var fromSel = cfg.fromSel || '#from_date';
    var toSel = cfg.toSel || '#to_date';

    $root.on('click', '.ew-mis-quick-range [data-range]', function () {
      var range = $(this).attr('data-range');
      $root.find('.ew-mis-quick-range [data-range]').removeClass('is-active');
      $(this).addClass('is-active');
      applyQuickRange(range, fromSel, toSel);
    });

    $root.on('click', '#ew_mis_edit_filters, #ew_mis_reset_filters', function () {
      showFilters($root);
      if (this.id === 'ew_mis_reset_filters' && typeof cfg.onReset === 'function') {
        cfg.onReset();
      }
    });

    if (cfg.tableSel && cfg.searchInputSel) {
      bindTableSearch(cfg.searchInputSel, cfg.tableSel);
    }

    return {
      root: $root,
      showFilters: function () { showFilters($root); },
      showResults: function (opts) { showResults($root, opts); },
      datePill: function () {
        var from = $(fromSel).val() || '';
        var to = $(toSel).val() || '';
        return (from && to) ? (from + ' to ' + to) : '';
      }
    };
  }

  window.EwMisReportView = {
    wire: wire,
    showFilters: showFilters,
    showResults: showResults,
    applyQuickRange: applyQuickRange,
    formatDmy: formatDmy,
    parseDmy: parseDmy,
    bindTableSearch: bindTableSearch
  };
})(window, window.jQuery);
