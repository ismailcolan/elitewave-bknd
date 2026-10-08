/**
 * Elite Wave 360 — v2 list table layout (reference: title left, search + create right, footer pagination)
 */
(function ($) {
  'use strict';

  function stripLabelText($label) {
    $label.contents().filter(function () {
      return this.nodeType === 3;
    }).remove();
  }

  function wrapSearchInput($input) {
    if (!$input.length || $input.closest('.ew-search-wrap').length) {
      return;
    }
    $input.addClass('ew-search-input');
    if (!$input.attr('placeholder')) {
      $input.attr('placeholder', 'Search');
    }
    $input.wrap('<div class="ew-search-wrap"></div>');
    $input.before('<i class="fa fa-search" aria-hidden="true"></i>');
  }

  function ensureToolbar($card) {
    var $toolbar = $card.find('.ew-card-toolbar').first();
    if (!$toolbar.length) {
      return null;
    }
    var $right = $toolbar.find('.ew-toolbar-right').first();
    if (!$right.length) {
      $right = $('<div class="ew-toolbar-right"></div>');
      $toolbar.append($right);
    }
    if (!$right.find('.ew-list-toolbar__tools').length) {
      $right.prepend('<div class="ew-list-toolbar__tools"></div>');
    }
    return $toolbar;
  }

  function ensureFooter($card) {
    var $footer = $card.children('.ew-table-footer').first();
    var $tableWrap = $card.find('.ew-table-wrap').first();
    if (!$footer.length) {
      $footer = $(
        '<div class="ew-table-footer">' +
          '<div class="ew-table-footer-left ew-dt-footer-left"></div>' +
          '<div class="ew-dt-footer-right ew-pagination"></div>' +
        '</div>'
      );
      if ($tableWrap.length) {
        $footer.insertAfter($tableWrap);
      } else {
        $card.append($footer);
      }
    }
    if (!$footer.find('.ew-dt-footer-left').length) {
      $footer.prepend('<div class="ew-table-footer-left ew-dt-footer-left"></div>');
    }
    if (!$footer.find('.ew-dt-footer-right').length) {
      $footer.append('<div class="ew-dt-footer-right ew-pagination"></div>');
    }
    return $footer;
  }

  function findFreshControl($wrapper, selector) {
    var $inSlots = $wrapper.find('.txn-dt-top ' + selector + ', .txn-dt-bottom ' + selector).first();
    if ($inSlots.length) {
      return $inSlots;
    }
    var className = selector.charAt(0) === '.' ? selector.slice(1) : selector;
    return $wrapper.children('.' + className).first();
  }

  function relocateControl($wrapper, $dest, selector) {
    var $existing = $dest.find(selector).first();
    var $fresh = findFreshControl($wrapper, selector);

    if ($fresh.length) {
      if ($existing.length && !$existing.is($fresh)) {
        $existing.remove();
      }
      if (!$fresh.closest($dest).length) {
        $fresh.appendTo($dest);
      }
      return $fresh;
    }

    return $existing;
  }

  function dedupeControls($parent, selector) {
    $parent.find(selector).slice(1).remove();
  }

  function layoutListCard($card) {
    if ($card.attr('data-ew-list-layout') === 'off') {
      return;
    }

    if (!$card.hasClass('ew-erp-list')) {
      $card.addClass('ew-erp-list');
    }

    var $wrapper = $card.find('.dataTables_wrapper').first();
    if (!$wrapper.length) {
      return;
    }

    ensureToolbar($card);
    var $footer = ensureFooter($card);
    var $footerLeft = $footer.find('.ew-dt-footer-left').first();
    var $footerRight = $footer.find('.ew-dt-footer-right').first();
    var $tools = $card.find('.ew-list-toolbar__tools').first();

    var $meta = $footerLeft.find('.ew-dt-footer-meta').first();
    if (!$meta.length) {
      $meta = $('<div class="ew-dt-footer-meta"></div>');
      $footerLeft.empty().append($meta);
    }

    var $filter = relocateControl($wrapper, $tools, '.dataTables_filter');
    if ($filter.length) {
      $filter.addClass('ew-erp-search');
      stripLabelText($filter.find('label'));
      wrapSearchInput($filter.find('input[type="search"], input[type="text"]').first());
    }

    var $info = relocateControl($wrapper, $meta, '.dataTables_info');
    if ($info.length) {
      $info.addClass('ew-dt-info');
    }

    var $length = relocateControl($wrapper, $meta, '.dataTables_length');
    if ($length.length) {
      $length.addClass('ew-dt-length');
      $length.find('select').addClass('ew-page-size');
      stripLabelText($length.find('label'));
    }

    var $paginate = relocateControl($wrapper, $footerRight, '.dataTables_paginate');
    if ($paginate.length) {
      $paginate.addClass('ew-pagination-dt');
      $paginate.find('.first, .last').hide();
    }

    dedupeControls($tools, '.dataTables_filter');
    dedupeControls($meta, '.dataTables_info');
    dedupeControls($meta, '.dataTables_length');
    dedupeControls($footerRight, '.dataTables_paginate');

    $card.addClass('ew-list-layout-ready');
  }

  function hasListTable($card) {
    return $card.find('#dataTable1, #ew_db_backup_table, #txn_list_table, #ts_status_table, #invoice_list_table, #receipt_list_table, #trip_list_table, #payment_report_table, #invoice_register_table, #pending_payments_table, table.dataTable').length > 0;
  }

  function applyAll() {
    $('.ew-page-v2 .ew-card').each(function () {
      var $card = $(this);
      if ($card.attr('data-ew-list-layout') === 'off') {
        return;
      }
      if (hasListTable($card)) {
        layoutListCard($card);
      }
    });
  }

  window.applyEwListLayout = applyAll;

  function isListSearchFocused() {
    return $(document.activeElement).closest('.dataTables_filter, .ew-search-wrap, .ew-erp-search').length > 0;
  }

  $(document).ready(function () {
    applyAll();
    window.setTimeout(applyAll, 200);
  });

  $(window).on('load', applyAll);

  if ($.fn.dataTable) {
    $(document).on('init.dt', function () {
      window.setTimeout(applyAll, 50);
    });
    $(document).on('draw.dt', function () {
      if (isListSearchFocused()) {
        return;
      }
      window.setTimeout(function () {
        if (!isListSearchFocused()) {
          applyAll();
        }
      }, 50);
    });
  }
})(jQuery);
