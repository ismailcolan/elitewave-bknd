(function ($) {
	'use strict';

	function fillCancelReasons(mode) {
		var list = (window.ewCancelReasonOptions && window.ewCancelReasonOptions[mode]) || [];
		var $sel = $('#ew_cancel_reason');
		var html = '<option value="">Select reason</option>';
		$.each(list, function (i, label) {
			html += '<option value="' + $('<div/>').text(label).html() + '">' + $('<div/>').text(label).html() + '</option>';
		});
		$sel.html(html);
	}

	function resetCancelForm() {
		$('#ew_cancel_error').hide().text('');
		$('#ew_cancel_remarks').val('');
		$('#ew_cancel_reason').val('');
	}

	window.ewOpenBookingCancelledInfoModal = function (opts) {
		opts = opts || {};
		$('#ewBookingCancelledModalTitle').text(opts.title || 'Cancelled booking');
		$('#ewBookingCancelledRemarks').val(opts.remarks || '');
		$('#ewBookingCancelledBy').text(opts.cancelledBy || '—');
		$('#ewBookingCancelledAt').text(opts.cancelledAt || '—');
		if (typeof ewV2OpenModal === 'function') {
			ewV2OpenModal('ewBookingCancelledModal');
		} else {
			$('#ewBookingCancelledModal').addClass('open');
		}
	};

	window.ewOpenCancelModal = function (opts) {
		opts = opts || {};
		var mode = opts.mode === 'invoice' ? 'invoice' : (opts.mode === 'receipt' ? 'receipt' : 'booking');
		resetCancelForm();
		fillCancelReasons(mode);
		$('#ew_cancel_mode').val(mode);
		var defaultTitle = 'Cancel booking';
		if (mode === 'invoice') {
			defaultTitle = 'Cancel tax invoice';
		} else if (mode === 'receipt') {
			defaultTitle = 'Cancel receipt';
		}
		$('#ewCancelModalTitle').text(opts.title || defaultTitle);
		$('#ewCancelModalMessage').text(opts.message || 'Confirm cancellation. This cannot be undone.');
		$('#ew_cancel_invoice_id').val(opts.billingInvoiceId || '');
		$('#ew_cancel_receipt_id').val(opts.billingReceiptId || '');
		$('#ew_cancel_transaction_id').val(opts.transactionId || '');
		$('#ew_cancel_table_names').val(opts.tableNames || '');
		$('#ew_cancel_grn_no').val(opts.grnNo || '');
		$('#ew_cancel_logged_id').val(opts.loggedId || '');
		if (typeof ewV2OpenModal === 'function') {
			ewV2OpenModal('ewCancelModal');
		} else {
			$('#ewCancelModal').addClass('open');
		}
	};

	function validateCancelForm() {
		var reason = $.trim($('#ew_cancel_reason').val());
		var remarks = $.trim($('#ew_cancel_remarks').val());
		if (!reason) {
			return 'Please select a cancellation reason.';
		}
		if (reason === 'Other' && !remarks) {
			return 'Please enter remarks for reason Other.';
		}
		return '';
	}

	function bookingRemarksPayload(reason, remarks) {
		if (reason === 'Other') {
			return remarks;
		}
		return remarks ? (reason + ' — ' + remarks) : reason;
	}

	$(document).on('click', '.ew-booking-cancel-info', function (e) {
		e.preventDefault();
		var $el = $(this);
		if (typeof ewOpenBookingCancelledInfoModal === 'function') {
			ewOpenBookingCancelledInfoModal({
				remarks: $el.data('remarks') || '',
				cancelledBy: $el.data('createdby') || '',
				cancelledAt: $el.data('createdat') || ''
			});
		}
	});

	$(document).on('click', '#ewCancelConfirmBtn', function () {
		var err = validateCancelForm();
		if (err) {
			$('#ew_cancel_error').text(err).show();
			return;
		}
		var mode = $('#ew_cancel_mode').val();
		var reason = $.trim($('#ew_cancel_reason').val());
		var remarks = $.trim($('#ew_cancel_remarks').val());
		$('#ew_cancel_error').hide();

		if (mode === 'invoice') {
			var invId = parseInt($('#ew_cancel_invoice_id').val(), 10);
			if (!invId) {
				return;
			}
			$('#ewCancelConfirmBtn').prop('disabled', true);
			$.post('create_invoice_data.php', {
				cmd: 'cancel_invoice',
				billing_invoice_id: invId,
				cancel_reason: reason,
				cancel_remarks: remarks
			}, function (r) {
				$('#ewCancelConfirmBtn').prop('disabled', false);
				if (!r || r.status !== 0) {
					$('#ew_cancel_error').text(r && r.message ? r.message : 'Cancellation failed.').show();
					return;
				}
				if (typeof ewV2CloseModal === 'function') {
					ewV2CloseModal('ewCancelModal');
				}
				location.reload();
			}, 'json').fail(function () {
				$('#ewCancelConfirmBtn').prop('disabled', false);
				$('#ew_cancel_error').text('Network error.').show();
			});
			return;
		}

		if (mode === 'receipt') {
			var rcptId = parseInt($('#ew_cancel_receipt_id').val(), 10);
			if (!rcptId) {
				return;
			}
			$('#ewCancelConfirmBtn').prop('disabled', true);
			$.post('create_receipt_data.php', {
				cmd: 'cancel_receipt',
				billing_receipt_id: rcptId,
				cancel_reason: reason,
				cancel_remarks: remarks
			}, function (r) {
				$('#ewCancelConfirmBtn').prop('disabled', false);
				if (!r || r.status !== 0) {
					$('#ew_cancel_error').text(r && r.message ? r.message : 'Cancellation failed.').show();
					return;
				}
				if (typeof ewV2CloseModal === 'function') {
					ewV2CloseModal('ewCancelModal');
				}
				location.reload();
			}, 'json').fail(function () {
				$('#ewCancelConfirmBtn').prop('disabled', false);
				$('#ew_cancel_error').text('Network error.').show();
			});
			return;
		}

		var txId = $('#ew_cancel_transaction_id').val();
		if (!txId) {
			return;
		}
		$('#ewCancelConfirmBtn').prop('disabled', true);
		$.ajax({
			url: 'save_details.php',
			type: 'POST',
			data: {
				form_name: 'cancel_booking_consignment',
				logged_id: $('#ew_cancel_logged_id').val(),
				transaction_id: txId,
				table_names: $('#ew_cancel_table_names').val(),
				grn_no: $('#ew_cancel_grn_no').val(),
				cancel_reason: reason,
				remarks: bookingRemarksPayload(reason, remarks)
			},
			success: function (response) {
				$('#ewCancelConfirmBtn').prop('disabled', false);
				if (String(response).trim() === '1') {
					if (typeof ewV2CloseModal === 'function') {
						ewV2CloseModal('ewCancelModal');
					}
					location.reload();
					return;
				}
				if (String(response).trim() === '2') {
					$('#ew_cancel_error').text('Booking is already cancelled. Refresh the list to see the latest status.').show();
					return;
				}
				$('#ew_cancel_error').text('Could not cancel this booking. Check the GCN is still active and try again.').show();
			},
			error: function () {
				$('#ewCancelConfirmBtn').prop('disabled', false);
				$('#ew_cancel_error').text('Network error.').show();
			}
		});
	});
})(jQuery);
