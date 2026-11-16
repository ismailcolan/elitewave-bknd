/**
 * Elite Wave 360 — list tables:
 * - framed scroll host (columns pan; shell stays fixed)
 * - drag-to-pan horizontally (no scrollbar)
 * - Action column pinned to the right by default (transform pin — reliable across browsers)
 */
(function ($) {
  'use strict';

  var INTERACTIVE = 'a, button, input, select, textarea, label, .select2-container, .paginate_button, .dataTables_paginate, .dataTables_length, .dataTables_filter, .action-buttons, .act-link, .table-actions, .act-wrap, .col-actions, .col-action, .col-steps, .border';
  // Only real Action columns — never Transit Update (.col-steps) even if it also has .actions
  var ACTION_SEL = 'th.col-actions, td.col-actions, th.col-action, td.col-action';
  var hosts = [];

  function isInteractiveTarget(target) {
    return $(target).closest(INTERACTIVE).length > 0;
  }

  function isActionColumnTarget(target) {
    var $hit = $(target).closest(ACTION_SEL);
    return $hit.length > 0 && !$hit.hasClass('col-steps');
  }

  function canScrollHorizontally(el) {
    return el.scrollWidth > el.clientWidth + 2;
  }

  function clamp(val, min, max) {
    if (val < min) {
      return min;
    }
    if (val > max) {
      return max;
    }
    return val;
  }

  function shouldSkipTable($table) {
    return $table.hasClass('ew-ss-datatable')
      || $table.hasClass('ew-no-drag-scroll')
      || $table.hasClass('ew-no-sticky-action')
      || $table.closest('.ew-no-drag-scroll, .ew-no-sticky-action, .ew-form-body, .ew-v2-modal').length > 0;
  }

  function isActionHeaderText(text) {
    var t = String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    return t === 'action' || t === 'actions';
  }

  function markStickyActionColumns($table) {
    var $ths = $table.find('thead th');
    if (!$ths.length) {
      return -1;
    }
    var actionIdx = -1;
    $ths.each(function (i) {
      var $th = $(this);
      if ($th.hasClass('col-actions') || $th.hasClass('actions') || $th.hasClass('col-action')) {
        actionIdx = i;
        return;
      }
      if (isActionHeaderText($th.text())) {
        actionIdx = i;
      }
    });
    if (actionIdx < 0) {
      return -1;
    }
    $ths.eq(actionIdx).addClass('col-actions');
    $table.find('tbody tr').each(function () {
      $(this).children('td').eq(actionIdx).addClass('col-actions actions');
    });
    return actionIdx;
  }

  function ensureScrollHost($table) {
    var $parent = $table.parent();

    if ($parent.hasClass('ew-table-scroll-host') && $parent.children().length === 1) {
      return $parent;
    }

    if ($parent.hasClass('ew-table-scroll-host')) {
      $table.unwrap();
      $parent = $table.parent();
    }

    $table.parents('.ew-table-scroll-host').each(function () {
      var $h = $(this);
      if (!$h.children().filter('table').length) {
        $h.removeClass('ew-table-scroll-host ew-drag-scroll').removeAttr('data-ew-drag-scroll');
      }
    });

    if ($table.parent().hasClass('ew-table-scroll-host') && $table.parent().children().length === 1) {
      return $table.parent();
    }

    if ($table.parent().hasClass('ew-table-scroll-inner') && $table.parent().children().length === 1) {
      return $table.parent().addClass('ew-table-scroll-host');
    }

    $table.wrap('<div class="ew-table-scroll-host"></div>');
    return $table.parent();
  }

  function clearPinStyles(el) {
    if (!el || !el.style) {
      return;
    }
    el.style.removeProperty('transform');
    el.style.removeProperty('will-change');
    el.style.removeProperty('position');
    el.style.removeProperty('z-index');
  }

  /**
   * Pin Action column to the right edge of the scroll host.
   * translateX(scrollLeft - maxScroll) keeps it visible even when other columns overflow.
   * Skips Transit Update (.col-steps) — that column must stay in normal flow.
   */
  function pinActionColumn(host) {
    if (!host) {
      return;
    }
    var $host = $(host);
    var $table = $host.find('table').first();

    // Undo any previous pin on Transit Update / non-action cells
    $table.find('th.col-steps, td.col-steps, th.actions:not(.col-actions):not(.col-action), td.actions:not(.col-actions):not(.col-action)').each(function () {
      clearPinStyles(this);
    });

    var $cells = $table.find(ACTION_SEL).filter(function () {
      return !$(this).hasClass('col-steps');
    });
    if (!$cells.length) {
      $host.removeClass('ew-action-pinned');
      return;
    }

    var maxScroll = Math.max(0, host.scrollWidth - host.clientWidth);
    var dx = host.scrollLeft - maxScroll;
    var pinned = maxScroll > 0;
    var transform = pinned ? 'translateX(' + dx + 'px)' : 'none';

    $host.toggleClass('ew-action-pinned', pinned);
    $cells.each(function () {
      this.style.setProperty('position', 'relative', 'important');
      this.style.setProperty('z-index', this.tagName === 'TH' ? '8' : '6', 'important');
      this.style.setProperty('transform', transform, 'important');
      this.style.setProperty('will-change', pinned ? 'transform' : 'auto', 'important');
    });
  }

  function pinAllHosts() {
    for (var i = 0; i < hosts.length; i++) {
      pinActionColumn(hosts[i]);
    }
  }

  function trackHost(host) {
    if (!host || host.getAttribute('data-ew-action-pin') === '1') {
      if (host && hosts.indexOf(host) < 0) {
        hosts.push(host);
      }
      pinActionColumn(host);
      return;
    }
    host.setAttribute('data-ew-action-pin', '1');
    hosts.push(host);

    host.addEventListener('scroll', function () {
      pinActionColumn(host);
    }, { passive: true });

    pinActionColumn(host);
  }

  function prepareTables() {
    $('.ew-action-rail').remove();
    $('table.ew-sticky-table').removeClass('ew-sticky-table ew-has-hscroll');
    $('th.ew-sticky-action, td.ew-sticky-action').removeClass('ew-sticky-action');
    $('#ew-sticky-actions-css').remove();

    $('.ew-table-wrap, .widget-content.padded.new_dept').each(function () {
      var $wrap = $(this);
      if ($wrap.hasClass('ew-table-scroll-host') && $wrap.find('> table').length === 0) {
        $wrap.removeClass('ew-table-scroll-host ew-drag-scroll');
        $wrap.removeAttr('data-ew-drag-scroll');
      }
    });

    $('table.table, table.dataTable, .ew-data-table, .trans_list_table').each(function () {
      var $table = $(this);
      if (shouldSkipTable($table)) {
        return;
      }

      var hasAction = false;
      $table.find('thead th').each(function () {
        if ($(this).hasClass('col-actions') || $(this).hasClass('actions') || isActionHeaderText($(this).text())) {
          hasAction = true;
          return false;
        }
      });
      if (!hasAction && !$table.closest('.ew-erp-list, .ew-card, .widget-container, .txn-page-wrap').length) {
        return;
      }

      markStickyActionColumns($table);
      var $host = ensureScrollHost($table);
      $host.addClass('ew-table-scroll-host');
      $table.addClass('ew-sticky-action-table');
      bindDragScroll($host[0]);
      trackHost($host[0]);
    });

    pinAllHosts();
  }

  function bindDragScroll(container) {
    if (!container || container.getAttribute('data-ew-drag-scroll') === '1') {
      return;
    }
    if ($(container).closest('.ew-no-drag-scroll').length) {
      return;
    }
    if (!$(container).find('table').length) {
      return;
    }

    container.setAttribute('data-ew-drag-scroll', '1');
    container.classList.add('ew-drag-scroll');

    var isDown = false;
    var moved = false;
    var startX = 0;
    var scrollLeft = 0;

    function endDrag() {
      if (!isDown) {
        return;
      }
      isDown = false;
      container.classList.remove('is-dragging');
      if (moved) {
        window.setTimeout(function () {
          moved = false;
        }, 80);
      }
      var maxX = Math.max(0, container.scrollWidth - container.clientWidth);
      container.scrollLeft = clamp(container.scrollLeft, 0, maxX);
      pinActionColumn(container);
    }

    container.addEventListener('mousedown', function (e) {
      if (e.button !== 0 || !canScrollHorizontally(container)) {
        return;
      }
      if (isActionColumnTarget(e.target) || isInteractiveTarget(e.target)) {
        return;
      }
      isDown = true;
      moved = false;
      startX = e.pageX;
      scrollLeft = container.scrollLeft;
      container.classList.add('is-dragging');
      e.preventDefault();
    });

    window.addEventListener('mousemove', function (e) {
      if (!isDown) {
        return;
      }
      var dx = e.pageX - startX;
      if (Math.abs(dx) > 3) {
        moved = true;
      }
      var maxX = Math.max(0, container.scrollWidth - container.clientWidth);
      container.scrollLeft = clamp(scrollLeft - dx, 0, maxX);
      pinActionColumn(container);
      if (moved) {
        e.preventDefault();
      }
    });

    window.addEventListener('mouseup', endDrag);

    container.addEventListener('click', function (e) {
      if (moved) {
        e.preventDefault();
        e.stopImmediatePropagation();
      }
    }, true);

    container.addEventListener('touchstart', function (e) {
      if (!canScrollHorizontally(container) || !e.touches.length) {
        return;
      }
      if (isActionColumnTarget(e.target) || isInteractiveTarget(e.target)) {
        return;
      }
      isDown = true;
      moved = false;
      startX = e.touches[0].pageX;
      scrollLeft = container.scrollLeft;
      container.classList.add('is-dragging');
    }, { passive: true });

    container.addEventListener('touchmove', function (e) {
      if (!isDown || !e.touches.length) {
        return;
      }
      var dx = e.touches[0].pageX - startX;
      if (Math.abs(dx) > 3) {
        moved = true;
      }
      var maxX = Math.max(0, container.scrollWidth - container.clientWidth);
      container.scrollLeft = clamp(scrollLeft - dx, 0, maxX);
      pinActionColumn(container);
    }, { passive: true });

    container.addEventListener('touchend', endDrag);
    container.addEventListener('touchcancel', endDrag);

    container.addEventListener('wheel', function (e) {
      if (!canScrollHorizontally(container)) {
        return;
      }
      if (Math.abs(e.deltaX) > Math.abs(e.deltaY) || e.shiftKey) {
        e.preventDefault();
        var maxX = Math.max(0, container.scrollWidth - container.clientWidth);
        container.scrollLeft = clamp(container.scrollLeft + (e.shiftKey ? e.deltaY : e.deltaX), 0, maxX);
        pinActionColumn(container);
      }
    }, { passive: false });
  }

  function initDragScroll() {
    prepareTables();
  }

  window.ewInitTableScroll = initDragScroll;
  window.ewPinTableActions = pinAllHosts;
  window.ewMarkStickyActionColumns = function () {
    $('table.table, table.dataTable, .ew-data-table, .trans_list_table').each(function () {
      if (!shouldSkipTable($(this))) {
        markStickyActionColumns($(this));
      }
    });
    pinAllHosts();
  };

  $(document).ready(function () {
    initDragScroll();
    window.setTimeout(initDragScroll, 200);
    window.setTimeout(initDragScroll, 800);
  });

  $(window).on('load', initDragScroll);
  $(window).on('resize', function () {
    window.setTimeout(pinAllHosts, 50);
  });

  if ($.fn.dataTable) {
    $(document).on('init.dt draw.dt', function () {
      window.setTimeout(initDragScroll, 50);
    });
  }
})(jQuery);
