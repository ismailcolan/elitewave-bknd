(function($) {
	'use strict';

	function num(v) {
		v = parseFloat(String(v || '').replace(/,/g, ''));
		return isNaN(v) ? 0 : v;
	}

	function fmt(n) {
		if (Math.abs(n - Math.round(n)) < 0.001) {
			return Math.round(n).toLocaleString('en-IN');
		}
		return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}

	function escHtml(s) {
		return $('<div>').text(s || '').html();
	}

	function isExistingCustomerMode() {
		return $('input[name=customer_mode]:checked').val() === 'existing';
	}

	function sourceKind(group) {
		var g = String(group || '').toLowerCase();
		if (g === 'road cargo') {
			return 'road';
		}
		if (g.indexOf('train') !== -1) {
			return 'train';
		}
		if (g.indexOf('air') !== -1 || g.indexOf('flight') !== -1) {
			return 'flight';
		}
		return '';
	}

	function sourceLabel($tr) {
		var kind = sourceKind($tr.attr('data-mode-group'));
		if (!kind) {
			return '—';
		}
		var $sel = $tr.find('.md-source-' + kind);
		if (!$sel.length || !$sel.val()) {
			return '—';
		}
		var txt = $.trim($sel.find('option:selected').text());
		if (txt === '' || txt.indexOf('Select ') === 0) {
			return '—';
		}
		if (kind === 'road') {
			var map = window.QUOTATION_VEHICLE_MAP || {};
			var vid = $sel.val();
			if (vid && map[vid] && map[vid].label) {
				return map[vid].label;
			}
		}
		return txt;
	}

	function selectPreviewLabel(id) {
		var $sel = $('#' + id);
		if (!$sel.length || !$sel.val()) {
			return '';
		}
		return $.trim($sel.find('option:selected').text());
	}

	function paymentTermsPreviewLabel() {
		var code = $('#payment_terms').val() || '';
		var map = window.QUOTATION_PAYMENT_LABELS || {};
		return map[code] || code || '—';
	}

	function syncMdRowVehicle($tr) {
		var group = $tr.find('.md-mode option:selected').attr('data-mode-group') || $tr.attr('data-mode-group') || '';
		$tr.attr('data-mode-group', group);
		var kind = sourceKind(group);
		$tr.find('.md-source').each(function() {
			var show = kind !== '' && $(this).hasClass('md-source-' + kind);
			$(this).toggle(show);
			if (!show) {
				$(this).val('');
			}
		});
		$tr.find('.md-source-empty').toggle(kind === '');
	}

	function syncMdVehicleFields() {
		$('#md_dest_tbody tr.md-row').each(function() {
			syncMdRowVehicle($(this));
		});
	}

	function partyLabel() {
		if (isExistingCustomerMode()) {
			var $o = $('#party_id option:selected');
			return $o.val() ? $.trim($o.text()) : '—';
		}
		return $.trim($('#party_name').val()) || '—';
	}

	function recalcDestRowTotals() {
		var sum = 0;
		$('#md_dest_tbody tr.md-row').each(function() {
			var $tr = $(this);
			var rowSum = num($tr.find('.md-amt[name="md_freight[]"]').val()) + num($tr.find('.md-amt[name="md_tempo[]"]').val());
			rowSum = Math.round(rowSum * 100) / 100;
			sum += rowSum;
		});
		return sum;
	}

	function recalcTotals() {
		var taxable = recalcDestRowTotals();
		var gstPct = num($('#gst_rate').val());
		var gst = Math.round(taxable * gstPct / 100 * 100) / 100;
		var total = Math.round((taxable + gst) * 100) / 100;
		$('#taxable_value').val(fmt(taxable));
		$('#gst_amount').val(fmt(gst));
		$('#total_amount').val(fmt(total));
		return { taxable: taxable, gst: gst, total: total, gstPct: gstPct };
	}

	function initMdSelect2($scope) {
		if (!$.fn.select2) {
			return;
		}
		$scope.find('.md-city').each(function() {
			var $el = $(this);
			if ($el.data('select2')) {
				return;
			}
			$el.select2({ width: '100%', placeholder: 'City — State', allowClear: true });
			$el.on('change', renderPreview);
		});
	}

	function mdSelectLabel($sel, placeholder) {
		if (!$sel.length) {
			return '—';
		}
		var v = $sel.val();
		if ($sel.data('select2')) {
			var s2v = $sel.select2('val');
			if (s2v !== null && s2v !== undefined && String(s2v) !== '') {
				v = s2v;
				var data = $sel.select2('data');
				if (data && data.text) {
					var dt = $.trim(data.text);
					if (dt !== '' && dt !== placeholder) {
						return dt;
					}
				}
			}
		}
		if (v === null || v === undefined || String(v) === '') {
			return '—';
		}
		var txt = $.trim($sel.find('option').filter(function() {
			return String($(this).val()) === String(v);
		}).first().text());
		if (txt === '' || txt === placeholder) {
			return '—';
		}
		return txt;
	}

	function destPreviewTable() {
		var html = '<table class="charges" style="font-size:11px;"><tr><th align="left">Destination</th><th>Mode</th><th>Source of transport</th><th>Days</th><th align="right">Freight</th><th align="right">Pickup &amp; Delivery</th></tr>';
		$('#md_dest_tbody tr.md-row').each(function() {
			var $tr = $(this);
			var dest = mdSelectLabel($tr.find('.md-city'), 'City — State');
			var mode = mdSelectLabel($tr.find('.md-mode'), 'Mode');
			var src = sourceLabel($tr);
			var days = mdSelectLabel($tr.find('.md-days'), 'Days');
			var freightRaw = $.trim($tr.find('.md-amt[name="md_freight[]"]').val());
			var tempoRaw = $.trim($tr.find('.md-amt[name="md_tempo[]"]').val());
			var rt = freightRaw === '' ? '—' : ('₹ ' + fmt(num(freightRaw)) + ' /-');
			var tempoDisp = tempoRaw === '' ? '—' : ('₹ ' + fmt(num(tempoRaw)) + ' /-');
			html += '<tr><td>' + escHtml(dest) + '</td><td>' + escHtml(mode) + '</td><td>' + escHtml(src) + '</td><td>' + escHtml(days) + '</td><td align="right">' + escHtml(rt) + '</td><td align="right">' + escHtml(tempoDisp) + '</td></tr>';
		});
		html += '</table>';
		return html;
	}

	function buildPreviewHtml() {
		var totals = recalcTotals();
		var subject = $.trim($('#subject').val());
		var html = '<h4>CONSIGNOR — MULTIPLE DESTINATIONS</h4>'
			+ '<div class="meta"><b>Consignor:</b> ' + escHtml(partyLabel()) + '<br><b>Kind Attn.:</b> ' + escHtml($('#attn_name').val() || '—') + '</div>';
		if (subject !== '') {
			html += '<div class="meta" style="margin-top:8px;"><b>Subject:</b> ' + escHtml(subject) + '</div>';
		}
		var intro = $.trim($('#letter_body').val() || '') || (window.QUOTATION_LETTER_INTRO || '');
		html += '<p style="margin:10px 0 6px;">Dear Sir / Madam,<br><span style="font-weight:normal;">' + escHtml(intro).replace(/\n/g, '<br>') + '</span></p>';
		var logistics = '';
		var qaLbl = selectPreviewLabel('quotation_approval');
		if (qaLbl !== '') {
			logistics += '<div class="pv-kv"><b>Quotation approval:</b> ' + escHtml(qaLbl) + '</div>';
		}
		logistics += '<div class="pv-kv"><b>Payment terms:</b> ' + escHtml(paymentTermsPreviewLabel()) + '</div>';
		var fpLbl = selectPreviewLabel('freight_paid_by');
		if (fpLbl !== '') {
			logistics += '<div class="pv-kv"><b>Freight paid by:</b> ' + escHtml(fpLbl) + '</div>';
		}
		var cfsVal = $.trim($('#cfs_port_factory').val() || '');
		var partVal = $.trim($('#part_number').val() || '');
		logistics += '<div class="pv-kv"><b>CFS / Port / Factory / Warehouse:</b> ' + escHtml(cfsVal !== '' ? cfsVal : '—') + '</div>';
		logistics += '<div class="pv-kv"><b>Article No.:</b> ' + escHtml(partVal !== '' ? partVal : '—') + '</div>';
		if (logistics !== '') {
			html += '<div class="pv-section">Logistics reference</div>' + logistics;
		}
		html += '<div class="pv-section">Destination-wise charges</div>' + destPreviewTable();
		html += '<div class="pv-section" style="margin-top:14px;">Terms &amp; conditions (PDF page 2)</div>'
			+ '<p class="pv-kv" style="font-size:12px;color:#444;">Full terms on page 2 — followed by Thanks &amp; Regards.</p>';
		return html;
	}

	function renderPreview() {
		if (window.QUOTATION_PREVIEW_IN_MODAL) {
			recalcTotals();
			return;
		}
		$('#letter_preview').html(buildPreviewHtml());
	}

	function openQuotationPreviewModal() {
		$('#quotationPreviewModalBody').html(buildPreviewHtml());
		if (typeof ewV2OpenModal === 'function') {
			ewV2OpenModal('quotationPreviewModal');
		}
	}

	function mdRowFieldVal($tr, selector) {
		var $el = $tr.find(selector);
		if (!$el.length) {
			return '';
		}
		var v = $el.val();
		if ($el.data('select2')) {
			v = $el.select2('val');
		}
		return $.trim(v == null ? '' : String(v));
	}

	function clearMdFieldErrors() {
		$('#md_dest_tbody .md-field-error').removeClass('md-field-error');
	}

	function mdRowIsActive($tr) {
		if (mdRowFieldVal($tr, '.md-city') || mdRowFieldVal($tr, '.md-mode')) {
			return true;
		}
		if (mdRowFieldVal($tr, '.md-days')) {
			return true;
		}
		if ($.trim($tr.find('.md-amt[name="md_freight[]"]').val() || '') !== '') {
			return true;
		}
		if ($.trim($tr.find('.md-amt[name="md_tempo[]"]').val() || '') !== '') {
			return true;
		}
		return false;
	}

	function mdFlagInvalid($el) {
		if (!$el || !$el.length) {
			return;
		}
		$el.addClass('md-field-error');
	}

	function validateMdDestinationRows() {
		clearMdFieldErrors();
		var seen = 0;
		var $firstBad = null;
		$('#md_dest_tbody tr.md-row').each(function() {
			var $tr = $(this);
			if (!mdRowIsActive($tr)) {
				return;
			}
			seen++;
			var city = mdRowFieldVal($tr, '.md-city');
			var mode = mdRowFieldVal($tr, '.md-mode');
			var days = mdRowFieldVal($tr, '.md-days');
			var $freight = $tr.find('.md-amt[name="md_freight[]"]');
			var $tempo = $tr.find('.md-amt[name="md_tempo[]"]');
			var freight = $.trim($freight.val() || '');
			var tempo = $.trim($tempo.val() || '');

			if (!city) {
				mdFlagInvalid($tr.find('.md-city'));
				if (!$firstBad) {
					$firstBad = $tr.find('.md-city');
				}
			}
			if (!mode) {
				mdFlagInvalid($tr.find('.md-mode'));
				if (!$firstBad) {
					$firstBad = $tr.find('.md-mode');
				}
			}
			if (!days) {
				mdFlagInvalid($tr.find('.md-days'));
				if (!$firstBad) {
					$firstBad = $tr.find('.md-days');
				}
			}
			if (freight === '') {
				mdFlagInvalid($freight);
				if (!$firstBad) {
					$firstBad = $freight;
				}
			}
			if (tempo === '') {
				mdFlagInvalid($tempo);
				if (!$firstBad) {
					$firstBad = $tempo;
				}
			}
		});
		if (seen === 0) {
			var $firstRow = $('#md_dest_tbody tr.md-row').first();
			mdFlagInvalid($firstRow.find('.md-city'));
			mdFlagInvalid($firstRow.find('.md-mode'));
			mdFlagInvalid($firstRow.find('.md-days'));
			mdFlagInvalid($firstRow.find('.md-amt[name="md_freight[]"]'));
			mdFlagInvalid($firstRow.find('.md-amt[name="md_tempo[]"]'));
			$firstBad = $firstRow.find('.md-city');
		}
		if ($firstBad && $firstBad.length) {
			var top = $firstBad.offset().top - 140;
			$('html, body').animate({ scrollTop: top > 0 ? top : 0 }, 220);
			setTimeout(function() {
				try {
					$firstBad.trigger('focus');
				} catch (e) { /* ignore */ }
			}, 240);
		}
		return { ok: $('#md_dest_tbody .md-field-error').length === 0, message: '' };
	}
	window.validateMdDestinationRows = validateMdDestinationRows;

	function postQuotation(action, extra, cb) {
		$('#quotation_action').val(action);
		var data = $('#quotation_form').serialize();
		if (extra) {
			data += '&' + $.param(extra);
		}
		if ($('#party_id').prop('disabled')) {
			data += '&party_id=' + encodeURIComponent($('#party_id').val() || '');
		}
		if (!isExistingCustomerMode()) {
			data += '&party_id=0';
		}
		$.post('save_details.php', data, function(result) {
			if (typeof cb === 'function') {
				cb(result);
			}
		}).fail(function(xhr) {
			if (typeof cb === 'function') {
				cb(xhr.responseText || 'Network error');
			}
		});
	}

	$(function() {
		var masterOnly = !!window.QUOTATION_DEST_MASTER_ONLY;

		function syncCustomerModeUi(clearOpposite) {
			var existing = isExistingCustomerMode();
			$('.ew-customer-new').toggle(!existing);
			$('.ew-customer-existing').toggle(existing);
			$('#party_name').prop('disabled', existing);
			$('#party_id').prop('disabled', !existing);
			if (clearOpposite) {
				if (existing) {
					$('#party_name').val('');
				} else if ($('#party_id').data('select2')) {
					$('#party_id').select2('val', '');
				} else {
					$('#party_id').val('');
				}
			}
			renderPreview();
		}

		$('input[name=customer_mode]').on('change', function() {
			syncCustomerModeUi(true);
		});

		$(document).on('input change', '.pv-bind, #gst_rate, #party_id, #party_name, .md-amt, .md-city, .md-mode, .md-source, .md-days, #subject, #quotation_approval, #freight_paid_by, #payment_terms, #cfs_port_factory, #part_number', function() {
			renderPreview();
		});

		$(document).on('change', '.md-mode', function() {
			syncMdRowVehicle($(this).closest('tr.md-row'));
			renderPreview();
		});

		function cloneEmptyDestRow() {
			var $tpl = $('#md_row_template tr.md-row').first();
			if (!$tpl.length) {
				return null;
			}
			var $clone = $tpl.clone();
			$clone.find('select').val('');
			$clone.find('input').val('');
			$clone.find('.select2-container').remove();
			$clone.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('aria-hidden').removeAttr('tabindex');
			return $clone;
		}

		$(document).on('click', '.btn-md-add-row', function(e) {
			e.preventDefault();
			var $clone = cloneEmptyDestRow();
			if (!$clone) {
				return;
			}
			var $after = $(this).closest('tr.md-row');
			$after.after($clone);
			initMdSelect2($clone);
			syncMdRowVehicle($clone);
			renderPreview();
		});

		$(document).on('change input', '#md_dest_tbody .md-city, #md_dest_tbody .md-mode, #md_dest_tbody .md-days, #md_dest_tbody .md-amt', function() {
			$(this).removeClass('md-field-error');
		});

		$(document).on('click', '.btn-md-remove-row', function() {
			var $tbody = $('#md_dest_tbody');
			if ($tbody.find('tr.md-row').length <= 1) {
				if (typeof ewToast === 'function') {
					ewToast('At least one destination row is required.', 'warning');
				}
				return;
			}
			$(this).closest('tr.md-row').remove();
			renderPreview();
		});

		function resolveDestMasterId() {
			var id = $.trim($('#md_dest_master_id').val() || '');
			if (id) {
				return id;
			}
			var fromWin = window.QUOTATION_DEST_MASTER_SINGLETON_ID;
			if (fromWin) {
				id = String(fromWin);
				$('#md_dest_master_id').val(id);
				return id;
			}
			return '';
		}

		function applyDestMasterHtml(html) {
			var $tbody = $('#md_dest_tbody');
			$tbody.find('select.md-city').each(function() {
				var $el = $(this);
				if ($el.hasClass('select2-hidden-accessible')) {
					try {
						$el.select2('destroy');
					} catch (e) { /* ignore */ }
				}
			});
			$tbody.html(html || '');
			if ($.fn.select2) {
				initMdSelect2($tbody);
			}
			syncMdVehicleFields();
			renderPreview();
		}

		var destMasterLoadXhr = null;
		function loadDestMasterRows(showToast) {
			var masterId = resolveDestMasterId();
			if (!masterId) {
				if (typeof ewToast === 'function') {
					ewToast('Save rows in Quotation Master first.', 'warning');
				}
				return;
			}
			if (destMasterLoadXhr && destMasterLoadXhr.readyState !== 4) {
				destMasterLoadXhr.abort();
			}
			destMasterLoadXhr = $.ajax({
				url: 'fetch_details.php',
				type: 'POST',
				dataType: 'json',
				data: { cmd: 'get_quotation_dest_master_rows', master_id: masterId },
				success: function(data) {
					destMasterLoadXhr = null;
					if (!data || !data.ok) {
						alert((data && data.message) || 'Could not load Quotation Master.');
						return;
					}
					if (!data.html || String(data.html).indexOf('md-row') === -1) {
						alert('Quotation Master has no saved rows yet.');
						return;
					}
					applyDestMasterHtml(data.html);
					if (showToast !== false && typeof ewToast === 'function') {
						ewToast('Destination rows loaded.', 'success');
					}
				},
				error: function(xhr, status) {
					destMasterLoadXhr = null;
					if (status === 'abort') {
						return;
					}
					var msg = xhr.responseText || 'Could not load Quotation Master.';
					try {
						var parsed = JSON.parse(xhr.responseText);
						if (parsed && parsed.message) {
							msg = parsed.message;
						}
					} catch (e) { /* use raw */ }
					alert(msg);
				}
			});
		}

		$('input[name=md_dest_entry_mode]').on('change', function() {
			if ($('input[name=md_dest_entry_mode]:checked').val() === 'master') {
				loadDestMasterRows(false);
			}
		});

		if (masterOnly) {
			if ($.fn.select2) {
				initMdSelect2($('#md_dest_tbody'));
			}
			syncMdVehicleFields();
			return;
		}

		$('#btn_quotation_preview').on('click', function() {
			openQuotationPreviewModal();
		});

		$('.btn-quotation-action').on('click', function() {
			var action = $(this).data('action');
			var $btn = $(this);
			if (!validateMdDestinationRows().ok) {
				$('#response').hide();
				return;
			}
			$btn.prop('disabled', true);
			postQuotation(action, {}, function(result) {
				$btn.prop('disabled', false);
				var data;
				try {
					data = typeof result === 'string' ? JSON.parse(result) : result;
				} catch (e) {
					$('#response .message').text(result || 'Save failed.');
					$('#response').show();
					return;
				}
				if (data.ok && data.quotation_id) {
					if (typeof ewToast === 'function') {
						ewToast(data.message || 'Saved.', 'success');
					}
					window.location.href = (window.QUOTATION_RETURN_PAGE || 'quotation_consignor_multi_dest.php') + '?id=' + data.quotation_id;
					return;
				}
				$('#response .message').text(data.message || 'Save failed.');
				$('#response').show();
			});
		});

		var pendingWorkflow = '';
		$('.btn-quotation-workflow').on('click', function() {
			pendingWorkflow = $(this).data('action');
			if (pendingWorkflow === 'reject') {
				$('#reject_remarks').val('');
				ewV2OpenModal('rejectQuotationModal');
				return;
			}
			runWorkflow(pendingWorkflow, '');
		});

		$('#confirm_reject').on('click', function() {
			var rem = $.trim($('#reject_remarks').val());
			if (!rem) {
				alert('Enter rejection remarks.');
				return;
			}
			ewV2CloseModal('rejectQuotationModal');
			runWorkflow('reject', rem);
		});

		function runWorkflow(action, remarks) {
			$('.form-data-saving').show();
			$('.btn-quotation-workflow, .btn-quotation-action').prop('disabled', true);
			$.ajax({
				url: 'save_details.php',
				type: 'POST',
				dataType: 'json',
				timeout: 120000,
				data: {
					form_name: 'rate_quotation_workflow',
					quotation_id: $('#quotation_id').val(),
					workflow_action: action,
					workflow_remarks: remarks
				},
				success: function(data) {
					if (data && data.ok) {
						if (typeof ewToast === 'function') {
							ewToast(data.message || 'Updated.', 'success');
						}
						setTimeout(function() { location.reload(); }, 900);
						return;
					}
					$('.form-data-saving').hide();
					$('.btn-quotation-workflow, .btn-quotation-action').prop('disabled', false);
					alert((data && data.message) || 'Action failed.');
				},
				error: function(xhr) {
					$('.form-data-saving').hide();
					$('.btn-quotation-workflow, .btn-quotation-action').prop('disabled', false);
					alert(xhr.responseText || 'Action failed.');
				}
			});
		}

		if ($.fn.select2) {
			if ($('#party_id').length) {
				$('#party_id').select2({ width: '100%', allowClear: true, placeholder: 'Select consignor' });
				$('#party_id').on('change', function() {
					var id = $(this).val();
					if (!id) {
						return;
					}
					$.getJSON('fetch_details.php', { cmd: 'get_client_details', tbl_id: id }, function(row) {
						if (!row) {
							return;
						}
						$('#attn_name').val(row.contact_person || '');
						$('#party_email').val(row.email || row.email1 || '');
						$('#party_mobile').val(row.contact_no || row.contact_no1 || '');
						renderPreview();
					});
				});
			}
			initMdSelect2($('#md_dest_tbody'));
		}

		syncMdVehicleFields();
		syncCustomerModeUi(false);
		recalcTotals();
		renderPreview();
	});
})(jQuery);
