/**
 * Elite Wave 360 — Pages v2 helpers (modal open/close only)
 */
(function ($) {
  'use strict';

  function openModal(id) {
    var $modal = $('#' + id);
    if ($modal.length) {
      $modal.addClass('open');
    }
  }

  function closeModal(id) {
    var $modal = id ? $('#' + id) : $('.ew-v2-modal-backdrop.open');
    $modal.removeClass('open');
  }

  window.ewV2OpenModal = openModal;
  window.ewV2CloseModal = closeModal;

  var ewDeletePendingId = '';
  var ewDeleteOnConfirm = null;

  window.ewConfirmDelete = function (opts) {
    opts = opts || {};
    if (!$('#ewDeleteModal').length) {
      return false;
    }
    ewDeletePendingId = opts.id ? String(opts.id) : '';
    ewDeleteOnConfirm = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
    $('#ewDeleteModalTitle').text(opts.title || 'Delete record');
    $('#ewDeleteModalMessage').text(opts.message || 'Do you want to delete this record? This action cannot be undone.');
    openModal('ewDeleteModal');
    return true;
  };

  $(document).on('click', '#ewDeleteConfirmBtn', function () {
    var id = ewDeletePendingId;
    var cb = ewDeleteOnConfirm;
    closeModal('ewDeleteModal');
    if (cb) {
      cb(id);
      ewDeleteOnConfirm = null;
      return;
    }
    var $compat = $('#ewDeleteCompatBtn');
    if ($compat.length) {
      $compat.attr('id', id || 'ewDeleteCompatBtn');
      $compat.trigger('click');
      $compat.attr('id', 'ewDeleteCompatBtn');
    }
  });

  document.addEventListener('click', function (e) {
    var el = e.target;
    if (!el || !el.closest) {
      return;
    }
    var a = el.closest('a[href="#myModal"], a[data-target="#myModal"]');
    if (!a) {
      return;
    }
    e.preventDefault();
    e.stopPropagation();
    var delId = a.id || '';
    window.ewConfirmDelete({ id: delId });
  }, true);

  var ewConfirmOnOk = null;
  window.ewConfirm = function (opts) {
    opts = opts || {};
    if (!$('#ewConfirmModal').length) {
      return window.confirm(opts.message || 'Are you sure?');
    }
    ewConfirmOnOk = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
    $('#ewConfirmModalTitle').text(opts.title || 'Please confirm');
    $('#ewConfirmModalMessage').text(opts.message || 'Are you sure you want to continue?');
    $('#ewConfirmOkBtn').text(opts.confirmText || 'OK');
    $('#ewConfirmCancelBtn').text(opts.cancelText || 'Cancel');
    openModal('ewConfirmModal');
    return true;
  };

  $(document).on('click', '#ewConfirmOkBtn', function () {
    var cb = ewConfirmOnOk;
    ewConfirmOnOk = null;
    closeModal('ewConfirmModal');
    if (cb) {
      cb();
    }
  });

  $(document).on('click', '[data-ew-v2-open]', function () {
    openModal(String($(this).data('ew-v2-open')));
  });

  /** Close only via header X or footer Cancel/Close (not backdrop or field clicks). */
  $(document).on('click', '.ew-v2-modal-close[data-ew-v2-close], .ew-v2-modal-foot [data-ew-v2-close]', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $backdrop = $(this).closest('.ew-v2-modal-backdrop');
    if ($backdrop.length) {
      closeModal($backdrop.attr('id'));
    }
  });

  $(document).on('mousedown click', '.ew-v2-modal-backdrop.open .ew-v2-modal', function (e) {
    e.stopPropagation();
  });

  function ewEscapeRegExp(str) {
    return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function ewSelectedLabel($el) {
    var $opt = $el.find('option:selected');
    if (!$opt.length || !$opt.val()) {
      return '';
    }
    return $.trim($opt.text());
  }

  window.ewCleanPartyAddress = function (address, pincode, cityName, stateName) {
    var addr = $.trim(String(address || ''));
    if (!addr || addr.toUpperCase() === 'NULL') {
      return '';
    }
    var pin = String(pincode || '').replace(/\D+/g, '');
    if (pin.length === 6) {
      var pinRe = ewEscapeRegExp(pin);
      addr = addr.replace(new RegExp('\\bPIN(CODE)?\\s*[:.\\-]?\\s*' + pinRe + '\\b', 'gi'), '');
      addr = addr.replace(new RegExp('\\s*[-–—]\\s*' + pinRe + '\\b', 'g'), '');
      addr = addr.replace(new RegExp('[,;]\\s*' + pinRe + '\\b', 'g'), ',');
      addr = addr.replace(new RegExp('\\b' + pinRe + '\\b', 'g'), '');
    }
    var state = $.trim(String(stateName || ''));
    if (state) {
      var stateRe = ewEscapeRegExp(state);
      addr = addr.replace(new RegExp('[,\\s\\-]+' + stateRe + '\\s*$', 'i'), '');
      addr = addr.replace(new RegExp('\\(\\s*' + stateRe + '\\s*\\)', 'gi'), '');
    }
    var city = $.trim(String(cityName || ''));
    if (city) {
      var stripped = addr.replace(new RegExp('[,\\s\\-]+' + ewEscapeRegExp(city) + '\\s*$', 'i'), '');
      if ($.trim(stripped) !== '') {
        addr = stripped;
      }
    }
    addr = addr.replace(/\s+/g, ' ').replace(/\s*,\s*,+/g, ',').replace(/[,\s\-]+$/g, '');
    return $.trim(addr.replace(/^,+|,+$/g, ''));
  };

  window.ewJoinPartyAddress = function (r) {
    r = r || {};
    var street = [
      window.ewCleanPartyAddress(r.address1, r.pincode, r.city_name, r.state_name),
      window.ewCleanPartyAddress(r.address2, r.pincode, r.city_name, r.state_name)
    ].filter(Boolean).join(', ');
    var parts = [];
    if (street) {
      parts.push(street);
    }
    if (r.city_name && !new RegExp('(^|[\\s,])' + ewEscapeRegExp(r.city_name) + '($|[\\s,.\\-])', 'i').test(street)) {
      parts.push(r.city_name);
    }
    if (r.pincode && street.indexOf(String(r.pincode)) === -1) {
      parts.push(r.pincode);
    }
    return parts.filter(Boolean).join(', ');
  };

  window.ewApplyPartyAddressCleanup = function (root) {
    var $root = $(root || document);
    var $form = $root.is('form') ? $root : $root.find('form').addBack('form').first();
    if (!$form.length) {
      $form = $root;
    }
    var $a1 = $form.find('#address1');
    if (!$a1.length || !$a1.is('input, textarea')) {
      return;
    }
    var pin = $form.find('#pincode').val() || '';
    var city = ewSelectedLabel($form.find('#city'));
    var state = ewSelectedLabel($form.find('#state'));
    $a1.val(window.ewCleanPartyAddress($a1.val(), pin, city, state));
    var $a2 = $form.find('#address2');
    if ($a2.length && $a2.is('input, textarea')) {
      $a2.val(window.ewCleanPartyAddress($a2.val(), pin, city, state));
    }
  };

  function ewClientAddressForm($form) {
    var name = String($form.find('#form_name').val() || $form.attr('id') || '');
    return name === 'add_client' || name === 'add_client_branch' || name === 'client_form' || name === 'client_branch_form' || name === 'add_client_form';
  }

  $(document).on('blur', '#address1, #address2, #pincode', function () {
    var $form = $(this).closest('form');
    if (ewClientAddressForm($form)) {
      window.ewApplyPartyAddressCleanup($form);
    }
  });

  $(document).on('change', '#city, #state', function () {
    var $form = $(this).closest('form');
    if (ewClientAddressForm($form)) {
      window.ewApplyPartyAddressCleanup($form);
    }
  });

  $(document).on('click', '#save, #update', function () {
    var $form = $(this).closest('form');
    if (!$form.length) {
      $form = $('#client_form, #client_branch_form, #add_client_form').filter(':visible').first();
    }
    if ($form.length && ewClientAddressForm($form)) {
      window.ewApplyPartyAddressCleanup($form);
    }
  });
})(jQuery);
