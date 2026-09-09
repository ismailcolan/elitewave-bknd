/**
 * Elite Wave 360 — drag-to-scroll wide tables only (not search/pagination)
 */
(function ($) {
  'use strict';

  var SCROLL_SELECTORS = [
    '.table-scroll-wrapper',
    '.egcn-table-wrap',
    '.ew-table-scroll-inner'
  ].join(',');

  var INTERACTIVE = 'a, button, input, select, textarea, label, .select2-container, .paginate_button, .dataTables_paginate, .dataTables_length, .dataTables_filter, .action-buttons, .act-link, .table-actions';

  function isInteractiveTarget(target) {
    return $(target).closest(INTERACTIVE).length > 0;
  }

  function canScrollHorizontally(el) {
    return el.scrollWidth > el.clientWidth + 2;
  }

  function wrapTableInScrollInner(table) {
    var $table = $(table);
    if ($table.parent().hasClass('ew-table-scroll-inner')) {
      return $table.parent()[0];
    }
    $table.wrap('<div class="ew-table-scroll-inner"></div>');
    return $table.parent()[0];
  }

  function prepareDataTableScroll() {
    $('.dataTables_wrapper').each(function () {
      var $wrapper = $(this);

      var $scrollBody = $wrapper.find('.dataTables_scrollBody').first();
      if ($scrollBody.length) {
        $scrollBody.addClass('ew-table-scroll-inner');
        return;
      }

      if ($wrapper.find('> .ew-table-scroll-inner').length) {
        return;
      }

      var $table = $wrapper.children('table.dataTable, table.table').first();
      if ($table.length) {
        wrapTableInScrollInner($table[0]);
      }
    });

    $('.widget-content > table.dataTable, .widget-content > table.table').each(function () {
      if (!$(this).parent().hasClass('ew-table-scroll-inner') && !$(this).closest('.dataTables_wrapper').length) {
        wrapTableInScrollInner(this);
      }
    });
  }

  function bindDragScroll(container) {
    if (!container || container.getAttribute('data-ew-drag-scroll') === '1') {
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
    }

    container.addEventListener('mousedown', function (e) {
      if (e.button !== 0 || !canScrollHorizontally(container)) {
        return;
      }
      if (isInteractiveTarget(e.target)) {
        return;
      }

      isDown = true;
      moved = false;
      startX = e.pageX;
      scrollLeft = container.scrollLeft;
      container.classList.add('is-dragging');
    });

    window.addEventListener('mousemove', function (e) {
      if (!isDown) {
        return;
      }

      var dx = e.pageX - startX;
      if (Math.abs(dx) > 4) {
        moved = true;
      }
      container.scrollLeft = scrollLeft - dx;
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
      if (isInteractiveTarget(e.target)) {
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
      if (Math.abs(dx) > 4) {
        moved = true;
      }
      container.scrollLeft = scrollLeft - dx;
    }, { passive: true });

    container.addEventListener('touchend', endDrag);
    container.addEventListener('touchcancel', endDrag);

    container.addEventListener('wheel', function (e) {
      if (!canScrollHorizontally(container)) {
        return;
      }
      if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
        e.preventDefault();
        container.scrollLeft += e.deltaX;
      }
    }, { passive: false });
  }

  function initDragScroll() {
    prepareDataTableScroll();
    $(SCROLL_SELECTORS).each(function () {
      bindDragScroll(this);
    });
  }

  window.ewInitTableScroll = initDragScroll;

  $(document).ready(function () {
    initDragScroll();
    window.setTimeout(initDragScroll, 150);
    window.setTimeout(initDragScroll, 600);
  });

  $(window).on('load', initDragScroll);

  if ($.fn.dataTable) {
    $(document).on('init.dt draw.dt', function () {
      window.setTimeout(initDragScroll, 50);
    });
  }
})(jQuery);
