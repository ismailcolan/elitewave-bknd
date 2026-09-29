(function ($) {
  'use strict';

  var moduleOn = $('body').data('partb-module') === 1 || $('body').data('partb-module') === '1';

  function toast(msg, type) {
    if (typeof ewFormToast === 'function') {
      ewFormToast(msg, type || 'info', 5000);
    } else {
      alert(msg);
    }
  }

  function setMessage(el, text, kind) {
    var $el = $(el);
    if (!text) {
      $el.addClass('hidden').removeClass('ok err').text('');
      return;
    }
    $el.removeClass('hidden ok err').addClass(kind === 'ok' ? 'ok' : 'err').text(text);
  }

  function currentVehicleFromLoad(data) {
    if (data.nic && data.nic.vehicle_no) {
      return data.nic.vehicle_no;
    }
    if (data.snapshot && data.snapshot.vehicle_no) {
      return data.snapshot.vehicle_no;
    }
    return '';
  }

  function validUptoFromLoad(data) {
    if (data.nic && data.nic.valid_upto) {
      return data.nic.valid_upto;
    }
    if (data.snapshot && data.snapshot.valid_upto) {
      return data.snapshot.valid_upto;
    }
    return '—';
  }

  function applyContext(data) {
    var ewb = data.ewb_no || '';
    var cur = currentVehicleFromLoad(data) || '—';
    $('#ctxEwbNo').text(ewb);
    $('#ctxCurrentVehicle').text(cur);
    $('#ctxValidUpto').text(validUptoFromLoad(data));
    var src = (data.source || []).join(', ');
    if (data.nic_error) {
      src += (src ? ' · ' : '') + 'GST lookup: ' + data.nic_error;
    }
    $('#ctxSource').text(src ? 'Sources: ' + src : '');
    $('#ewbContext').removeClass('hidden');
    $('#fieldEwbNo').val(ewb);
    $('#fieldCurrentVehicle').val(cur === '—' ? '' : cur);
    $('#compareOld').text(cur);
    $('#updateCard').removeClass('disabled');
  }

  function refreshCompareNew() {
    var v = ($('#fieldVehicleNo').val() || '').trim().toUpperCase();
    $('#compareNew').text(v || '—');
  }

  function toggleTransDocFields() {
    var mode = $('#fieldTransMode').val();
    $('.trans-doc-field').toggle(mode !== '1');
  }

  function loadHistory() {
    var filter = ($('#historyFilterEwb').val() || '').trim();
    $.getJSON('eway_partb_json.php', { action: 'history', ewb_no: filter })
      .done(function (res) {
        var $tb = $('#partbHistoryBody');
        $tb.empty();
        if (!res.ok || !res.rows || !res.rows.length) {
          $tb.append('<tr><td colspan="7" class="text-center text-muted">No records.</td></tr>');
          return;
        }
        res.rows.forEach(function (r) {
          var st = r.status === 'success' ? '<span class="pill ok">Success</span>' : '<span class="pill err">Failed</span>';
          var msg = r.status === 'success' ? (r.valid_upto || '') : (r.error_message || '');
          $tb.append(
            '<tr><td>' + (r.request_at || '') + '</td><td>' + (r.ewb_no || '') + '</td><td>' + (r.old_vehicle_no || '') +
            '</td><td>' + (r.new_vehicle_no || '') + '</td><td>' + (r.from_place || '') +
            '</td><td>' + st + '</td><td>' + $('<div/>').text(msg).html() + '</td></tr>'
          );
        });
      })
      .fail(function () {
        $('#partbHistoryBody').html('<tr><td colspan="7" class="text-center text-danger">Could not load history.</td></tr>');
      });
  }

  $('#btnLoadEwb').on('click', function () {
    if (!moduleOn) {
      return;
    }
    var ewb = ($('#ewbNoInput').val() || '').replace(/\D/g, '');
    if (ewb.length !== 12) {
      toast('Enter a valid 12-digit E-Way Bill number.', 'error');
      return;
    }
    var $btn = $(this).prop('disabled', true).text('Loading…');
    $.getJSON('eway_partb_json.php', { action: 'load_ewb', ewb_no: ewb })
      .done(function (data) {
        if (!data.ok) {
          toast(data.message || 'Could not load E-Way Bill.', 'error');
          return;
        }
        applyContext(data);
        toast('E-Way Bill loaded.', 'success');
      })
      .fail(function (xhr) {
        var msg = 'Could not load E-Way Bill.';
        try {
          var j = JSON.parse(xhr.responseText);
          if (j.message) {
            msg = j.message;
          }
        } catch (e) {}
        toast(msg, 'error');
      })
      .always(function () {
        $btn.prop('disabled', false).text('Load');
      });
  });

  $('#fieldVehicleNo').on('input', refreshCompareNew);

  $('#fieldTransMode').on('change', toggleTransDocFields);
  toggleTransDocFields();

  var defMode = $('body').data('default-trans-mode');
  var defVtype = $('body').data('default-vehicle-type');
  if (defMode) {
    $('#fieldTransMode').val(String(defMode));
    toggleTransDocFields();
  }
  if (defVtype) {
    $('select[name="vehicle_type"]').val(defVtype);
  }

  $('#partbUpdateForm').on('submit', function (ev) {
    ev.preventDefault();
    if (!moduleOn) {
      return;
    }
    var ewb = ($('#fieldEwbNo').val() || '').replace(/\D/g, '');
    if (ewb.length !== 12) {
      toast('Load an E-Way Bill number first.', 'error');
      return;
    }
    var $btn = $('#btnUpdatePartB').prop('disabled', true).text('Updating E-Way Bill…');
    setMessage('#partbFormMessage', '', '');
    $.ajax({
      url: 'eway_partb_json.php?action=update_partb',
      method: 'POST',
      data: $(this).serialize(),
      dataType: 'json'
    })
      .done(function (res) {
        if (res.ok) {
          setMessage('#partbFormMessage', res.message, 'ok');
          toast(res.message, 'success');
          $('#compareOld').text(res.new_vehicle || $('#fieldVehicleNo').val());
          $('#fieldCurrentVehicle').val(res.new_vehicle || '');
          $('#ctxCurrentVehicle').text(res.new_vehicle || '—');
          if (res.valid_upto) {
            $('#ctxValidUpto').text(res.valid_upto);
          }
          refreshHistory();
        } else {
          setMessage('#partbFormMessage', res.message || 'Vehicle update failed.', 'err');
          toast(res.message || 'Vehicle update failed.', 'error');
        }
      })
      .fail(function (xhr) {
        var msg = 'Vehicle update failed.';
        try {
          var j = JSON.parse(xhr.responseText);
          if (j.message) {
            msg = j.message;
          }
        } catch (e) {}
        setMessage('#partbFormMessage', msg, 'err');
        toast(msg, 'error');
      })
      .always(function () {
        $btn.prop('disabled', false).text('Update Part-B');
      });
  });

  $('#btnRefreshHistory').on('click', refreshHistory);
  $('#historyFilterEwb').on('change keyup', function () {
    clearTimeout(window._ewbHistT);
    window._ewbHistT = setTimeout(refreshHistory, 400);
  });

  if (moduleOn) {
    refreshHistory();
  } else {
    $('#partbHistoryBody').html('<tr><td colspan="7" class="text-center text-muted">Module disabled.</td></tr>');
  }
})(jQuery);
