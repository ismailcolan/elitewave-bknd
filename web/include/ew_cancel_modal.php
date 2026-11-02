<?php
if (!function_exists('ew_cancellation_reasons')) {
	require_once __DIR__ . '/function.php';
}
$ew_cancel_booking_reasons = ew_cancellation_reasons('booking');
$ew_cancel_invoice_reasons = ew_cancellation_reasons('invoice');
$ew_cancel_receipt_reasons = ew_cancellation_reasons('receipt');
?>
<div class="ew-v2-modal-backdrop" id="ewCancelModal">
	<div class="ew-v2-modal ew-cancel-modal" role="dialog" aria-labelledby="ewCancelModalTitle" aria-modal="true">
		<div class="ew-v2-modal-head">
			<h3 id="ewCancelModalTitle">Cancel</h3>
			<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
		</div>
		<div class="ew-v2-modal-body">
			<p class="ew-cancel-modal__lead" id="ewCancelModalMessage">Confirm cancellation. This cannot be undone.</p>
			<div class="form-group">
				<label class="control-label" for="ew_cancel_reason">Reason <span class="req">*</span></label>
				<select id="ew_cancel_reason" class="form-control">
					<option value="">Select reason</option>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="ew_cancel_remarks">Remarks</label>
				<textarea id="ew_cancel_remarks" class="form-control" rows="3" placeholder="Additional details (required if reason is Other)"></textarea>
				<p class="ew-field-hint ew-field-hint--compact" id="ew_cancel_remarks_hint"></p>
			</div>
			<p class="text-danger" id="ew_cancel_error" style="display:none;"></p>
			<input type="hidden" id="ew_cancel_mode" value="" />
			<input type="hidden" id="ew_cancel_invoice_id" value="" />
			<input type="hidden" id="ew_cancel_receipt_id" value="" />
			<input type="hidden" id="ew_cancel_transaction_id" value="" />
			<input type="hidden" id="ew_cancel_table_names" value="" />
			<input type="hidden" id="ew_cancel_grn_no" value="" />
			<input type="hidden" id="ew_cancel_logged_id" value="" />
		</div>
		<div class="ew-v2-modal-foot">
			<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
			<button type="button" class="btn btn-primary" id="ewCancelConfirmBtn">Confirm cancellation</button>
		</div>
	</div>
</div>

<div class="ew-v2-modal-backdrop" id="ewBookingCancelledModal">
	<div class="ew-v2-modal ew-booking-cancelled-modal" role="dialog" aria-labelledby="ewBookingCancelledModalTitle" aria-modal="true">
		<div class="ew-v2-modal-head">
			<h3 id="ewBookingCancelledModalTitle">Cancelled booking</h3>
			<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
		</div>
		<div class="ew-v2-modal-body">
			<p class="ew-cancel-modal__lead">This consignment was cancelled and cannot be edited or re-booked under the same GCN.</p>
			<div class="form-group">
				<label class="control-label" for="ewBookingCancelledRemarks">Remarks</label>
				<textarea id="ewBookingCancelledRemarks" class="form-control" rows="4" readonly tabindex="-1"></textarea>
			</div>
			<dl class="ew-booking-cancelled-meta">
				<div><dt>Cancelled by</dt><dd id="ewBookingCancelledBy">—</dd></div>
				<div><dt>Cancelled at</dt><dd id="ewBookingCancelledAt">—</dd></div>
			</dl>
		</div>
		<div class="ew-v2-modal-foot">
			<button type="button" class="btn btn-default-outline" data-ew-v2-close>Close</button>
		</div>
	</div>
</div>

<script type="text/javascript">
window.ewCancelReasonOptions = {
	booking: <?php echo json_encode(array_values($ew_cancel_booking_reasons), JSON_UNESCAPED_UNICODE); ?>,
	invoice: <?php echo json_encode(array_values($ew_cancel_invoice_reasons), JSON_UNESCAPED_UNICODE); ?>,
	receipt: <?php echo json_encode(array_values($ew_cancel_receipt_reasons), JSON_UNESCAPED_UNICODE); ?>
};
</script>
