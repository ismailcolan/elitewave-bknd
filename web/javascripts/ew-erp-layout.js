/**
 * ERP list layout — move DataTables search into list toolbar
 */
(function ($) {
  'use strict';

  function moveSearchToToolbar() {
    $('.ew-erp-list').each(function () {
      var $card = $(this);
      var $slot = $card.find('.ew-list-toolbar__tools').first();
      var $filter = $card.find('.dataTables_filter').first();
      if (!$slot.length || !$filter.length || $filter.closest('.ew-list-toolbar__tools').length) {
        return;
      }
      $filter.appendTo($slot);
      $filter.addClass('ew-erp-search');
      var $label = $filter.find('label');
      $label.contents().filter(function () {
        return this.nodeType === 3;
      }).remove();
      var $input = $filter.find('input');
      if ($input.length && !$input.attr('placeholder')) {
        $input.attr('placeholder', 'Search');
      }
    });
  }

  function isListSearchFocused() {
    return $(document.activeElement).closest('.dataTables_filter, .ew-search-wrap, .ew-erp-search').length > 0;
  }

  $(document).ready(function () {
    moveSearchToToolbar();
    window.setTimeout(moveSearchToToolbar, 300);
  });

  $(window).on('load', moveSearchToToolbar);

  if ($.fn.dataTable) {
    $(document).on('init.dt', function () {
      window.setTimeout(moveSearchToToolbar, 50);
    });
    $(document).on('draw.dt', function () {
      if (isListSearchFocused()) {
        return;
      }
      window.setTimeout(function () {
        if (!isListSearchFocused()) {
          moveSearchToToolbar();
        }
      }, 50);
    });
  }
})(jQuery);
