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

	function isRoadCargoGroup(group) {
		return String(group || '').toLowerCase() === 'road cargo';
	}

	function syncMdRowVehicle($tr) {
		var group = $tr.find('.md-mode option:selected').attr('data-mode-group') || $tr.attr('data-mode-group') || '';
		$tr.attr('data-mode-group', group);
		var road = isRoadCargoGroup(group);
		$tr.find('.md-vehicle-road').toggle(road);
		$tr.find('.md-vehicle-text').toggle(!road);
		if (road) {
			$tr.find('.md-vehicle-text').val('');
		} else {
			$tr.find('.md-vehicle-road').val('');
		}
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
			var rowSum = num($tr.find('.md-amt[name="md_freight[]"]').val())
				+ num($tr.find('.md-amt[name="md_doc[]"]').val())
				+ num($tr.find('.md-amt[name="md_others[]"]').val());
			rowSum = Math.round(rowSum * 100) / 100;
			$tr.find('.md-row-total').val(rowSum > 0 ? fmt(rowSum) : '');
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

	function destPreviewTable(totals) {
		var html = '<table class="charges" style="font-size:11px;"><tr><th align="left">Destination</th><th>Mode</th><th align="right">Total</th></tr>';
		$('#md_dest_tbody tr.md-row').each(function() {
			var $tr = $(this);
			var dest = mdSelectLabel($tr.find('.md-city'), 'City — State');
			var mode = mdSelectLabel($tr.find('.md-mode'), 'Mode');
			var rt = $.trim($tr.find('.md-row-total').val()) || '—';
			if (rt !== '—') {
				rt = '₹ ' + rt + ' /-';
			}
			html += '<tr><td>' + escHtml(dest) + '</td><td>' + escHtml(mode) + '</td><td align="right">' + escHtml(rt) + '</td></tr>';
		});
		html += '<tr><td colspan="2">GST @ ' + totals.gstPct + '%</td><td align="right">₹ ' + fmt(totals.gst) + ' /-</td></tr>';
		html += '<tr><td colspan="2"><b>Grand total</b></td><td align="right"><b>₹ ' + fmt(totals.total) + ' /-</b></td></tr></table>';
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
		var intro = window.QUOTATION_LETTER_INTRO || '';
		html += '<p style="margin:10px 0 6px;">Dear Sir / Madam,<br><span style="font-weight:normal;">' + escHtml(intro) + '</span></p>';
		html += '<div class="pv-section">Destination-wise charges</div>' + destPreviewTable(totals);
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

		$(document).on('input change', '.pv-bind, #gst_rate, #party_id, #party_name, .md-amt, .md-city, .md-mode, .md-vehicle-road, .md-vehicle-text, .md-days, #subject, #quotation_approval, #freight_paid_by, #payment_terms', function() {
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

		$('#btn_quotation_preview').on('click', function() {
			openQuotationPreviewModal();
		});

		$('.btn-quotation-action').on('click', function() {
			var action = $(this).data('action');
			var $btn = $(this);
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
