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

	function recalcTotals() {
		var taxable = 0;
		$('#charges_tbody tr').each(function() {
			var amt = num($(this).find('.charge-amt').val());
			if ($(this).find('.charge-tax').val() === '1') {
				taxable += amt;
			}
		});
		var gstPct = num($('#gst_rate').val());
		var gst = Math.round(taxable * gstPct / 100 * 100) / 100;
		var total = Math.round((taxable + gst) * 100) / 100;
		$('#taxable_value').val(fmt(taxable));
		$('#gst_amount').val(fmt(gst));
		$('#total_amount').val(fmt(total));
		return { taxable: taxable, gst: gst, total: total, gstPct: gstPct };
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

	function renderPreview() {
		var totals = recalcTotals();
		var delivery = escHtml($('#delivery_address').val()).replace(/\n/g, '<br>');
		var dims = vehicleDims();
		var subject = $.trim($('#subject').val());
		var unload = firstNonEmpty($('#unloading_at').val(), cityLabel($('#destination_city_id')));
		if (unload === '') {
			unload = '—';
		}
		var route = escHtml(cityLabel($('#origin_city_id'))) + ' → ' + escHtml($('#destination_name').val() || cityLabel($('#destination_city_id')));

		var html = '<h4>DOOR-TO-DOOR RATE QUOTATION</h4>'
			+ '<div class="meta"><b>To:</b> ' + escHtml(partyLabel()) + '<br><b>Kind Attn.:</b> ' + escHtml($('#attn_name').val() || '—') + '</div>';

		if (subject !== '') {
			html += '<div class="meta" style="margin-top:8px;"><b>Subject:</b> ' + escHtml(subject) + '</div>';
		}

		html += '<p style="margin:10px 0 6px;">Dear Sir / Madam,<br><span style="font-weight:normal;">Thank you for the opportunity to serve you. Below is our quotation for your requirement.</span></p>';

		var logistics = previewKvOptional('CFS / Port / Factory / Warehouse', 'cfs_port_factory');
		logistics += previewKvOptional('Part Number / Article Name / Article Number', 'part_number');
		var qaLbl = selectPreviewLabel('quotation_approval');
		if (qaLbl !== '') {
			logistics += '<div class="pv-kv"><b>Quotation approval:</b> ' + escHtml(qaLbl) + '</div>';
		}
		var fpbLbl = selectPreviewLabel('freight_paid_by');
		if (fpbLbl !== '') {
			logistics += '<div class="pv-kv"><b>Freight paid by:</b> ' + escHtml(fpbLbl) + '</div>';
		}
		logistics += '<div class="pv-kv"><b>Payment terms:</b> ' + paymentTermsPreviewLabel() + '</div>';
		var insNo = insuranceNumberFromCharges();
		if (insNo !== '') {
			logistics += '<div class="pv-kv"><b>Insurance number:</b> ' + escHtml(insNo) + '</div>';
		}
		if (logistics !== '') {
			html += '<div class="pv-section">Logistics reference</div>' + logistics;
		}

		html += '<div class="pv-section">Shipment details</div>'
			+ '<div class="pv-kv"><b>Route:</b> ' + route + (unload !== '—' ? ' (Unloading at ' + escHtml(unload) + ')' : '') + '</div>'
			+ '<div class="pv-kv"><b>Mode of transport:</b> ' + escHtml(modeOfTransportLabel()) + '</div>'
			+ '<div class="pv-kv"><b>Loading / Unloading:</b> ' + escHtml(loadingLabel()) + ' / ' + escHtml(unload) + '</div>'
			+ '<div class="pv-kv"><b>Vehicle:</b> ' + escHtml(vehicleLabel()) + (dims !== '—' ? ' · ' + escHtml(dims) : '') + '</div>'
			+ '<div class="pv-kv"><b>Delivery address:</b><br>' + (delivery || '—') + '</div>';

		html += '<div class="pv-section">Commercial summary</div>'
			+ '<table class="charges">' + chargePreviewRows(totals) + '</table>'
			+ '<div class="pv-section" style="margin-top:14px;">Terms &amp; conditions (PDF page 2)</div>'
			+ '<p class="pv-kv" style="font-size:12px;color:#444;">Shipment protection, MSDS, carrying capacity, and full terms on page 2 — followed by Thanks &amp; Regards.</p>';

		$('#letter_preview').html(html);
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

		$('#destination_city_id').on('change', function() {
			var city = cityLabel($('#destination_city_id'));
			if (city !== '—' && $.trim($('#unloading_at').val()) === '') {
				$('#unloading_at').val(city);
			}
			renderPreview();
		});

		$(document).on('input change', '.pv-bind, .pv-bind-select, .pv-bind-city, .charge-amt, .charge-tax, .charge-label, .charge-remarks, #gst_rate, #party_id, #party_name, #vehicle_type_id, #cfs_port_factory, #part_number, #quotation_approval, #freight_paid_by, #payment_terms', function() {
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
					window.location.href = 'quotation.php?id=' + data.quotation_id;
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
			$('#origin_city_id, #destination_city_id').select2({ width: '100%', placeholder: 'Select city' });
		} else {
			$('#party_id').on('change', function() {
				fillCustomerContactFromMaster($(this).val());
			});
		}

		syncCustomerModeUi(false);
		updateVehicleDims();
	});
})(jQuery);
