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

	function partyLabel() {
		if (isExistingCustomerMode()) {
			var $o = $('#party_id option:selected');
			return $o.val() ? $.trim($o.text()) : '—';
		}
		return $.trim($('#party_name').val()) || '—';
	}

	function cityLabel($select) {
		var $o = $select.find('option:selected');
		return $o.val() ? $.trim($o.text()) : '—';
	}

	function firstNonEmpty() {
		for (var i = 0; i < arguments.length; i++) {
			var v = $.trim(String(arguments[i] || ''));
			if (v !== '') {
				return v;
			}
		}
		return '';
	}

	function fillCustomerContactFromMaster(clientId) {
		if (!clientId) {
			$('#attn_name, #party_email, #party_mobile').val('');
			renderPreview();
			return;
		}
		$.getJSON('fetch_details.php', {
			cmd: 'get_client_details',
			tbl_id: clientId
		}, function(row) {
			if (!row || !row.client_id) {
				return;
			}
			$('#attn_name').val(row.contact_person || '');
			$('#party_email').val(firstNonEmpty(row.email, row.email1));
			$('#party_mobile').val(firstNonEmpty(row.contact_no, row.contact_no1));
			renderPreview();
		});
	}

	function modeOfTransportLabel() {
		var $sel = $('#mode_of_transportation');
		if (!$sel.length) {
			return '—';
		}
		var t = $.trim($sel.find('option:selected').text());
		return (t !== '' && $sel.val() !== '') ? t : '—';
	}

	function loadingLabel() {
		var v = $('#loading_type').val();
		var map = window.QUOTATION_LOADING_LABELS || {};
		return map[v] || v || '—';
	}

	function vehicleLabel() {
		var id = $('#vehicle_type_id').val();
		var map = window.QUOTATION_VEHICLE_MAP || {};
		if (id && map[id]) {
			return map[id].label || '—';
		}
		return '—';
	}

	function vehicleDims() {
		var id = $('#vehicle_type_id').val();
		var map = window.QUOTATION_VEHICLE_MAP || {};
		if (id && map[id] && map[id].dim_display) {
			return map[id].dim_display;
		}
		return '—';
	}

	function isMultiModeQuoteType() {
		if (window.QUOTATION_ALL_MODES_SCREEN) {
			return true;
		}
		var t = $('#quote_type').val() || '';
		var list = window.QUOTATION_MULTI_MODE_TYPES || [];
		return list.indexOf(t) >= 0;
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

	function sourceLabel($tr, prefix) {
		var kind = sourceKind($tr.attr('data-mode-group'));
		if (!kind) {
			return '—';
		}
		var $sel = $tr.find('.' + prefix + '-source-' + kind);
		if (!$sel.length || !$sel.val()) {
			return '—';
		}
		var txt = $.trim($sel.find('option:selected').text());
		if (txt === '' || txt.indexOf('Select ') === 0) {
			return '—';
		}
		return txt;
	}

	function syncQuoteTypeUi() {
		var multi = isMultiModeQuoteType();
		if (!window.QUOTATION_ALL_MODES_SCREEN) {
			$('.ew-quote-multi-mode-only').toggle(multi);
			$('.ew-quote-standard-only').toggle(!multi);
			if ($('#mode_of_transportation').length) {
				$('#mode_of_transportation').prop('required', !multi);
			}
		}
		if (multi) {
			syncMultiModeVehicleFields();
			recalcMultiModeRowTotals();
		}
		renderPreview();
	}

	function mmModeLabel($tr) {
		var $sel = $tr.find('.mm-mode-select');
		if (!$sel.length) {
			return $.trim($tr.find('td:first strong').text()) || '—';
		}
		var v = $sel.val();
		if (v === null || v === undefined || String(v) === '') {
			return '—';
		}
		var txt = $.trim($sel.find('option:selected').text());
		return txt !== '' && txt !== 'Select mode' ? txt : '—';
	}

	function syncMultiModeRowVehicle($tr) {
		var $sel = $tr.find('.mm-mode-select');
		var group = '';
		if ($sel.length) {
			group = $sel.find('option:selected').attr('data-mode-group') || '';
		} else {
			group = $tr.attr('data-mode-group') || '';
		}
		$tr.attr('data-mode-group', group);
		var kind = sourceKind(group);
		$tr.find('.mm-source').each(function() {
			var show = kind !== '' && $(this).hasClass('mm-source-' + kind);
			$(this).toggle(show);
			if (!show) {
				$(this).val('');
			}
		});
		$tr.find('.mm-source-empty').toggle(kind === '');
	}

	function syncMultiModeVehicleFields() {
		$('#multi_mode_tbody tr.mm-row').each(function() {
			syncMultiModeRowVehicle($(this));
		});
	}

	function recalcMultiModeRowTotals() {
		var sum = 0;
		$('#multi_mode_tbody tr.mm-row').each(function() {
			var $tr = $(this);
			var rowSum = num($tr.find('.mm-amt[name="mm_freight[]"]').val());
			rowSum = Math.round(rowSum * 100) / 100;
			sum += rowSum;
		});
		return sum;
	}

	function recalcTotals() {
		var taxable = 0;
		if (isMultiModeQuoteType()) {
			taxable = recalcMultiModeRowTotals();
		} else {
			$('#charges_tbody tr').each(function() {
				var amt = num($(this).find('.charge-amt').val());
				if ($(this).find('.charge-tax').val() === '1') {
					taxable += amt;
				}
			});
		}
		var gstPct = num($('#gst_rate').val());
		var gst = Math.round(taxable * gstPct / 100 * 100) / 100;
		var total = Math.round((taxable + gst) * 100) / 100;
		$('#taxable_value').val(fmt(taxable));
		$('#gst_amount').val(fmt(gst));
		$('#total_amount').val(fmt(total));
		return { taxable: taxable, gst: gst, total: total, gstPct: gstPct };
	}

	function multiModePreviewTable(totals) {
		var html = '<table class="charges" style="font-size:11px;"><tr><th align="left">Mode</th><th>Source of transport</th><th>Days</th><th align="right">Freight</th></tr>';
		$('#multi_mode_tbody tr.mm-row').each(function() {
			var $tr = $(this);
			var mode = mmModeLabel($tr);
			if (mode === '—') {
				return;
			}
			var veh = sourceLabel($tr, 'mm');
			var days = $.trim($tr.find('.mm-delivery-days option:selected').text());
			if ($tr.find('.mm-delivery-days').val() === '') {
				days = '—';
			}
			var freightRaw = $.trim($tr.find('.mm-amt[name="mm_freight[]"]').val());
			var rt = freightRaw === '' ? '—' : ('₹ ' + fmt(num(freightRaw)) + ' /-');
			html += '<tr><td>' + escHtml(mode) + '</td><td>' + escHtml(veh) + '</td><td>' + escHtml(days) + '</td><td align="right">' + escHtml(rt) + '</td></tr>';
		});
		html += '<tr><td colspan="3">GST @ ' + totals.gstPct + '%</td><td align="right">₹ ' + fmt(totals.gst) + ' /-</td></tr>';
		html += '<tr><td colspan="3"><b>Grand total</b></td><td align="right"><b>₹ ' + fmt(totals.total) + ' /-</b></td></tr></table>';
		return html;
	}

	function chargeAmountDisplay($row) {
		var raw = $.trim($row.find('.charge-amt').val());
		if (raw === '') {
			return '₹ — /-';
		}
		return '₹ ' + fmt(num(raw)) + ' /-';
	}

	function isInsuranceChargeLabel(label) {
		var lab = $.trim(label).toLowerCase();
		return (lab === 'insurance' || lab === 'vehicle insurance');
	}

	function insuranceNumberFromCharges() {
		var num = '';
		$('#charges_tbody tr').each(function() {
			var label = $.trim($(this).find('.charge-label').val());
			if (isInsuranceChargeLabel(label)) {
				num = $.trim($(this).find('.charge-remarks').val());
			}
		});
		return num;
	}

	function chargePreviewRows(totals) {
		var html = '';
		$('#charges_tbody tr').each(function() {
			var label = $.trim($(this).find('.charge-label').val()) || 'Charge';
			var rem = $.trim($(this).find('.charge-remarks').val());
			if (rem !== '' && !isInsuranceChargeLabel(label)) {
				label += ' — ' + rem;
			}
			var disp = chargeAmountDisplay($(this));
			html += '<tr><td>' + escHtml(label) + '</td><td>' + disp + '</td></tr>';
		});
		html += '<tr><td>GST @ ' + totals.gstPct + '%</td><td>₹ ' + fmt(totals.gst) + ' /-</td></tr>';
		html += '<tr><td><b>Total</b></td><td><b>₹ ' + fmt(totals.total) + ' /-</b></td></tr>';
		return html;
	}

	function previewVal(id) {
		var v = $.trim($('#' + id).val());
		return v !== '' ? escHtml(v) : '—';
	}

	function previewKv(label, id) {
		return '<div class="pv-kv"><b>' + escHtml(label) + ':</b> ' + previewVal(id) + '</div>';
	}

	function previewKvOptional(label, id) {
		var v = $.trim($('#' + id).val());
		if (v === '') {
			return '';
		}
		return '<div class="pv-kv"><b>' + escHtml(label) + ':</b> ' + escHtml(v) + '</div>';
	}

	function selectPreviewLabel(selectId) {
		var $sel = $('#' + selectId);
		if (!$sel.length) {
			return '';
		}
		var v = $.trim($sel.val());
		if (v === '') {
			return '';
		}
		var t = $.trim($sel.find('option:selected').text());
		return t !== '' ? t : v;
	}

	function paymentTermsPreviewLabel() {
		var label = selectPreviewLabel('payment_terms');
		return escHtml(label || '—');
	}

	function quotationCfsHiddenField() {
		var $h = $('#cfs_port_factory');
		return $h.length ? $h : $('#cfs');
	}

	function syncQuotationCfsField() {
		if (!$('#cfs_location_wrap').length) {
			return;
		}
		var kind = $('#cfs_kind').val();
		var val = '';
		if (kind === 'cfs') {
			val = $.trim($('#cfs_master_select').val() || '');
		} else {
			val = $.trim($('#cfs_text_input').val() || '');
		}
		quotationCfsHiddenField().val(val);
	}

	function applyQuotationCfsKindUi() {
		if (!$('#cfs_location_wrap').length) {
			return;
		}
		var kind = $('#cfs_kind').val();
		if (kind === 'cfs') {
			$('#cfs_master_select').show().prop('disabled', false);
			$('#cfs_text_input').hide().prop('disabled', true);
		} else {
			$('#cfs_master_select').hide().prop('disabled', true);
			$('#cfs_text_input').show().prop('disabled', false);
		}
		syncQuotationCfsField();
	}

	function buildPreviewHtml() {
		var totals = recalcTotals();
		var multi = isMultiModeQuoteType();
		var delivery = escHtml($('#delivery_address').val()).replace(/\n/g, '<br>');
		var dims = vehicleDims();
		var subject = $.trim($('#subject').val());
		var unload = firstNonEmpty($('#unloading_at').val(), cityLabel($('#destination_city_id')));
		if (unload === '') {
			unload = '—';
		}
		var route = escHtml(cityLabel($('#origin_city_id'))) + ' → ' + escHtml($('#destination_name').val() || cityLabel($('#destination_city_id')));

		var qtText = $.trim($('#quote_type option:selected').text());
		var title = multi && qtText ? qtText.toUpperCase() : 'DOOR-TO-DOOR RATE QUOTATION';
		var html = '<h4>' + title + '</h4>'
			+ '<div class="meta"><b>To:</b> ' + escHtml(partyLabel()) + '<br><b>Kind Attn.:</b> ' + escHtml($('#attn_name').val() || '—') + '</div>';

		if (subject !== '') {
			html += '<div class="meta" style="margin-top:8px;"><b>Subject:</b> ' + escHtml(subject) + '</div>';
		}

		var intro = (window.QUOTATION_LETTER_INTRO || 'Thank you for considering EliteWave360 Logistics for your transportation requirements. Please find below our quotation for your kind consideration.');
		html += '<p style="margin:10px 0 6px;">Dear Sir / Madam,<br><span style="font-weight:normal;">' + escHtml(intro) + '</span></p>';

		var logistics = '';
		var qaLbl = selectPreviewLabel('quotation_approval');
		if (qaLbl !== '') {
			logistics += '<div class="pv-kv"><b>Quotation approval:</b> ' + escHtml(qaLbl) + '</div>';
		}
		logistics += '<div class="pv-kv"><b>Payment terms:</b> ' + paymentTermsPreviewLabel() + '</div>';
		var insNo = insuranceNumberFromCharges();
		if (insNo !== '') {
			logistics += '<div class="pv-kv"><b>Insurance number:</b> ' + escHtml(insNo) + '</div>';
		}
		if (logistics !== '') {
			html += '<div class="pv-section">Logistics reference</div>' + logistics;
		}

		html += '<div class="pv-section">Shipment details</div>';
		if (window.QUOTATION_ALL_MODES_SCREEN) {
			html += '<div class="pv-kv"><b>Consignee / delivery party:</b> ' + escHtml($('#destination_name').val() || '—') + '</div>'
				+ '<div class="pv-kv"><b>Delivery address:</b><br>' + (delivery || '—') + '</div>';
		} else {
			html += '<div class="pv-kv"><b>Route:</b> ' + route + (unload !== '—' ? ' (Unloading at ' + escHtml(unload) + ')' : '') + '</div>';
			if (!multi) {
				html += '<div class="pv-kv"><b>Mode of transport:</b> ' + escHtml(modeOfTransportLabel()) + '</div>'
					+ '<div class="pv-kv"><b>Loading / Unloading:</b> ' + escHtml(loadingLabel()) + ' / ' + escHtml(unload) + '</div>'
					+ '<div class="pv-kv"><b>Vehicle:</b> ' + escHtml(vehicleLabel()) + (dims !== '—' ? ' · ' + escHtml(dims) : '') + '</div>';
			} else {
				html += '<div class="pv-kv"><b>Loading / Unloading:</b> ' + escHtml(loadingLabel()) + ' / ' + escHtml(unload) + '</div>';
			}
			html += '<div class="pv-kv"><b>Delivery address:</b><br>' + (delivery || '—') + '</div>';
			var cfsVal = $.trim(quotationCfsHiddenField().val() || '');
			var partVal = $.trim($('#part_number').val() || '');
			if (cfsVal !== '') {
				html += '<div class="pv-kv"><b>CFS / Port / Factory / Warehouse:</b> ' + escHtml(cfsVal) + '</div>';
			}
			if (partVal !== '') {
				html += '<div class="pv-kv"><b>Part number / article:</b> ' + escHtml(partVal) + '</div>';
			}
		}

		html += '<div class="pv-section">' + (multi ? 'Mode-wise charges' : 'Commercial summary') + '</div>';
		html += multi ? multiModePreviewTable(totals) : ('<table class="charges">' + chargePreviewRows(totals) + '</table>');
		html += '<div class="pv-section" style="margin-top:14px;">Terms &amp; conditions (PDF page 2)</div>'
			+ '<p class="pv-kv" style="font-size:12px;color:#444;">Shipment protection, MSDS, carrying capacity, and full terms on page 2 — followed by Thanks &amp; Regards.</p>';

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

	function updateVehicleDims() {
		$('#vehicle_dims').text(vehicleDims());
		renderPreview();
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

		if ($('#destination_city_id').length) {
			$('#destination_city_id').on('change', function() {
				var city = cityLabel($('#destination_city_id'));
				if (city !== '—' && $.trim($('#unloading_at').val()) === '') {
					$('#unloading_at').val(city);
				}
				renderPreview();
			});
		}

		$(document).on('change', '.mm-mode-select', function() {
			syncMultiModeRowVehicle($(this).closest('tr.mm-row'));
			renderPreview();
		});

		function cloneEmptyModeRow() {
			var $tpl = $('#mm_row_template tr.mm-row').first();
			if (!$tpl.length) {
				return null;
			}
			return $tpl.clone();
		}

		$(document).on('click', '.btn-mm-add-row', function(e) {
			e.preventDefault();
			var $clone = cloneEmptyModeRow();
			if (!$clone || !$clone.length) {
				return;
			}
			$clone.find('select').val('');
			$clone.find('input').val('');
			$clone.attr('data-mode-group', '');
			var $after = $(this).closest('tr.mm-row');
			$after.after($clone);
			syncMultiModeRowVehicle($clone);
			renderPreview();
		});

		$(document).on('click', '.btn-mm-remove-row', function(e) {
			e.preventDefault();
			var $tbody = $('#multi_mode_tbody');
			if ($tbody.find('tr.mm-row').length <= 1) {
				if (typeof ewToast === 'function') {
					ewToast('At least one mode row is required.', 'warning');
				} else {
					alert('At least one mode row is required.');
				}
				return;
			}
			$(this).closest('tr.mm-row').remove();
			renderPreview();
		});

		$(document).on('change', '#cfs_kind', function() {
			applyQuotationCfsKindUi();
			renderPreview();
		});
		$(document).on('change input', '#cfs_master_select, #cfs_text_input', function() {
			syncQuotationCfsField();
			renderPreview();
		});

		$(document).on('input change', '.pv-bind, .pv-bind-select, .pv-bind-city, .charge-amt, .charge-tax, .charge-label, .charge-remarks, .mm-amt, .mm-delivery-days, .mm-source, #gst_rate, #party_id, #party_name, #vehicle_type_id, #quotation_approval, #payment_terms, #quote_type, #part_number', function() {
			if ($(this).is('#quote_type')) {
				syncQuoteTypeUi();
				return;
			}
			renderPreview();
		});

		$('#vehicle_type_id').on('change', updateVehicleDims);

		$('#btn_add_charge_line').on('click', function() {
			var row = '<tr>'
				+ '<td><input type="text" name="charge_label[]" class="form-control charge-label" value="" /></td>'
				+ '<td><input type="text" name="charge_amount[]" class="form-control charge-amt" value="" onpaste="return ewNumericPaste(event,this);" /></td>'
				+ '<td><select name="charge_taxable[]" class="form-control charge-tax"><option value="1" selected>Yes</option><option value="0">No</option></select></td>'
				+ '<td><input type="text" name="charge_remarks[]" class="form-control charge-remarks" value="" /></td>'
				+ '</tr>';
			$('#charges_tbody').append(row);
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
					var base = window.QUOTATION_RETURN_PAGE || 'quotation.php';
					window.location.href = base + '?id=' + data.quotation_id;
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

		function parseJsonResponse(result) {
			if (result && typeof result === 'object') {
				return result;
			}
			if (typeof result === 'string') {
				return JSON.parse(result);
			}
			return {};
		}

		function toastMessage(data, fallback) {
			var msg = data && data.message;
			if (typeof msg === 'string' && msg.trim() !== '') {
				return msg;
			}
			if (msg != null && typeof msg === 'object') {
				try {
					return JSON.stringify(msg);
				} catch (e) {
					return fallback;
				}
			}
			return fallback;
		}

		function workflowSendsEmail(action) {
			return action === 'approve' || action === 'resend_email';
		}

		function setQuotationWorkflowBusy(isBusy) {
			if (isBusy) {
				$('.form-data-saving').show();
				$('.btn-quotation-workflow, .btn-quotation-action').prop('disabled', true);
			} else {
				$('.form-data-saving').hide();
				$('.btn-quotation-workflow, .btn-quotation-action').prop('disabled', false);
			}
		}

		function runWorkflow(action, remarks) {
			var sendsMail = workflowSendsEmail(action);
			setQuotationWorkflowBusy(true);
			if (sendsMail && typeof ewToast === 'function') {
				ewToast('Sending quotation email with PDF… Please wait.', 'info', 15000);
			}

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
						var text = toastMessage(data, 'Updated.');
						var toastType = 'success';
						if (sendsMail && data.email_sent === false) {
							toastType = 'warning';
						}
						if (typeof ewToast === 'function') {
							ewToast(text, toastType, 8000);
						}
						setTimeout(function() { location.reload(); }, 900);
						return;
					}
					setQuotationWorkflowBusy(false);
					alert(toastMessage(data, 'Action failed.'));
				},
				error: function(xhr) {
					setQuotationWorkflowBusy(false);
					var text = xhr.responseText || 'Action failed.';
					try {
						var data = parseJsonResponse(xhr.responseText);
						text = toastMessage(data, text);
					} catch (e) {}
					alert(text);
				}
			});
		}

		if ($.fn.select2) {
			if ($('#party_id').length) {
				$('#party_id').select2({ width: '100%', allowClear: true, placeholder: 'Select customer' });
				$('#party_id').on('change', function() {
					fillCustomerContactFromMaster($(this).val());
				});
			}
			if ($('#origin_city_id, #destination_city_id').length) {
				$('#origin_city_id, #destination_city_id').select2({ width: '100%', placeholder: 'Select city' });
			}
		} else {
			$('#party_id').on('change', function() {
				fillCustomerContactFromMaster($(this).val());
			});
		}

		syncCustomerModeUi(false);
		syncQuoteTypeUi();
		applyQuotationCfsKindUi();
		updateVehicleDims();
	});
})(jQuery);
