(function ($) {
  'use strict';

  function toast(msg, type) {
    if (typeof ewFormToast === 'function') {
      ewFormToast(msg, type || 'info', 5000);
    } else {
      alert(msg);
    }
  }

  $('#ewaySettingsForm').on('submit', function (ev) {
    ev.preventDefault();
    var $btn = $(this).find('button[type="submit"]').prop('disabled', true);
    $.post('eway_partb_save_settings.php', $(this).serialize(), null, 'json')
      .done(function (res) {
        toast(res.message || 'Saved.', res.ok ? 'success' : 'error');
      })
      .fail(function () {
        toast('Could not save settings.', 'error');
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });

  $('#btnTestAuth').on('click', function () {
    var $btn = $(this).prop('disabled', true).text('Testing…');
    $.getJSON('eway_partb_json.php', { action: 'test_auth' })
      .done(function (res) {
        toast(res.message || (res.ok ? 'OK' : 'Failed'), res.ok ? 'success' : 'error');
      })
      .fail(function (xhr) {
        var msg = 'Connection test failed.';
        try {
          var j = JSON.parse(xhr.responseText);
          if (j.message) {
            msg = j.message;
          }
        } catch (e) {}
        toast(msg, 'error');
      })
      .always(function () {
        $btn.prop('disabled', false).text('Test connection');
      });
  });
})(jQuery);
