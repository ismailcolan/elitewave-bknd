<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);
ew_company_bank_ensure_schema($conn);

$c_date = date('d-m-Y');
$preview_no = billing_preview_receipt_number($conn);
$view_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_view = ($view_id > 0);
if (!$is_view && empty($_GET['create'])) {
	header('Location: receipt_list.php');
	exit;
}

$receipt = null;
if ($is_view) {
	$receipt = billing_get_receipt($conn, $view_id);
	if (!$receipt) {
		header('Location: receipt_list.php');
		exit;
	}
	$preview_no = $receipt['master']['receipt_no'];
	$c_date = $receipt['master']['receipt_date'];
}

$type_opts = billing_receipt_type_options();
$pay_modes = ew_company_bank_payment_modes();
$banks = ew_company_bank_options($conn);
$clients_q = mysqli_query($conn, "SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC");
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2--invoice .ew-remarks-compact { max-width: 100%; }
	</style>
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
	<div class="navbar navbar-fixed-top scroll-hide">
		<?php require_once('include/header.php'); require_once('include/menu.php'); ?>
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="row">
			<div class="col-md-12">
				<div class="ew-page-v2 ew-page-v2--invoice<?php echo $is_view ? ' ew-receipt-view-only' : ''; ?>">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<a href="receipt_list.php" class="ew-back-btn" title="Back to list"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php echo $is_view ? 'View Receipt' : 'Create Receipt'; ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<a href="receipt_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
						</div>
					</div>

					<div class="ew-card ew-invoice-details-card">
						<h2 class="ew-card-section-title">Receipt Details</h2>
						<div class="ew-form-body">
							<div class="ew-form-grid ew-form-grid--invoice">
								<?php if (!$is_view) { ?>
								<div class="ew-field span-2">
									<label>Receipt type <span class="req">*</span></label>
									<select id="receipt_type" class="form-control">
										<?php foreach ($type_opts as $k => $label) { ?>
											<option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($label); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="ew-field span-2 ew-field--spacer" aria-hidden="true"></div>
								<?php } elseif ($is_view) { ?>
								<div class="ew-field span-2">
									<label>Receipt type</label>
									<input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($type_opts[$receipt['master']['receipt_type']] ?? $receipt['master']['receipt_type']); ?>" />
								</div>
								<div class="ew-field span-2 ew-field--spacer" aria-hidden="true"></div>
								<?php } ?>
							</div>

					<div class="ew-receipt-form-block active" id="block_against">
							<div class="ew-form-grid ew-form-grid--invoice">
								<div class="ew-field">
									<label>Receipt No</label>
									<div class="ew-invoice-no-display" id="receipt_no_preview"><?php echo htmlspecialchars($preview_no); ?></div>
								</div>
								<div class="ew-field">
									<label>Receipt date <span class="req">*</span></label>
									<?php echo ew_date_input(array('id' => 'receipt_date_against', 'value' => $c_date, 'required' => true, 'readonly' => $is_view)); ?>
								</div>
								<div class="ew-field span-2">
									<label>Customer / Payer <span class="req">*</span></label>
									<select id="party_id_against" class="form-control" <?php echo $is_view ? 'disabled' : ''; ?>>
										<option value=""></option>
										<?php
										$sel_party = $is_view ? (int) $receipt['master']['party_id'] : 0;
										while ($c = mysqli_fetch_assoc($clients_q)) {
											$sel = ((int) $c['client_id'] === $sel_party) ? ' selected' : '';
											echo '<option value="' . (int) $c['client_id'] . '"' . $sel . '>' . htmlspecialchars($c['client_company_name']) . '</option>';
										}
										?>
									</select>
								</div>
								<div class="ew-field span-2">
									<label>Invoice No <span class="req">*</span></label>
									<select id="invoice_ids" class="form-control" multiple="multiple" <?php echo $is_view ? 'disabled' : ''; ?>></select>
									<p class="ew-field-hint">Select final invoice(s) with outstanding balance.</p>
								</div>
								<div class="ew-field span-2">
									<label>Amount</label>
									<input type="text" class="form-control" id="total_amount_against" readonly value="<?php echo $is_view ? htmlspecialchars(billing_format_money($receipt['master']['total_amount'])) : '0.00'; ?>" />
									<p class="ew-field-hint">Auto total from allocation “Receive now”.</p>
								</div>
							</div>
					</div>

					<div class="ew-receipt-form-block" id="block_simple">
							<div class="ew-form-grid ew-form-grid--invoice">
								<div class="ew-field">
									<label>Receipt No</label>
									<div class="ew-invoice-no-display"><?php echo htmlspecialchars($preview_no); ?></div>
								</div>
								<div class="ew-field">
									<label>Receipt date <span class="req">*</span></label>
									<?php echo ew_date_input(array('id' => 'receipt_date_simple', 'value' => $c_date, 'required' => true, 'readonly' => $is_view)); ?>
								</div>
								<div class="ew-field span-2">
									<label>Customer / Payer</label>
									<select id="party_id_simple" class="form-control" <?php echo $is_view ? 'disabled' : ''; ?>>
										<option value=""></option>
										<?php
										mysqli_data_seek($clients_q, 0);
										while ($c = mysqli_fetch_assoc($clients_q)) {
											$sel = ($is_view && (int) $c['client_id'] === (int) $receipt['master']['party_id']) ? ' selected' : '';
											echo '<option value="' . (int) $c['client_id'] . '"' . $sel . '>' . htmlspecialchars($c['client_company_name']) . '</option>';
										}
										?>
									</select>
								</div>
								<div class="ew-field span-2">
									<label>Payer name (if not in client list)</label>
									<input type="text" class="form-control" id="payer_name" value="<?php echo $is_view ? htmlspecialchars($receipt['master']['payer_name']) : ''; ?>" />
								</div>
								<div class="ew-field span-2">
									<label>Amount <span class="req">*</span></label>
									<input type="number" step="0.01" min="0" class="form-control" id="total_amount_simple" value="<?php echo $is_view ? htmlspecialchars($receipt['master']['total_amount']) : ''; ?>" />
								</div>
							</div>
					</div>
						</div>
					</div>

					<div class="ew-card ew-invoice-lines-card ew-receipt-alloc-card ew-receipt-form-block active" id="block_against_alloc">
							<h2 class="ew-card-section-title">Invoice Allocation</h2>
							<div class="ew-table-wrap ew-invoice-table-wrap">
									<div class="ew-invoice-table-scroll">
										<table class="table" id="alloc_table">
											<thead>
												<tr>
													<th>Inv. No</th><th>Inv. Date</th>
													<th class="num">Inv. Amount</th><th class="num">Already Received</th>
													<th class="num">Credit Note</th><th class="num">TDS</th><th class="num">Balance</th>
													<th class="num">Receive now</th>
												</tr>
											</thead>
											<tbody id="alloc_body">
												<tr class="ew-receipt-alloc-empty" id="alloc_empty_row"><td colspan="8">Select customer and invoice(s) to load allocation.</td></tr>
											</tbody>
											<tfoot>
												<tr class="totals-row">
													<td colspan="6" class="num"><strong>Total</strong></td>
													<td class="num" id="foot_balance">0.00</td>
													<td class="num" id="foot_receive">0.00</td>
												</tr>
											</tfoot>
										</table>
									</div>
							</div>
					</div>

					<div class="ew-card ew-invoice-details-card">
						<h2 class="ew-card-section-title">Payment Details</h2>
						<div class="ew-form-body">
							<div class="ew-form-grid ew-form-grid--invoice" id="payment_details_grid">
								<div class="ew-field">
									<label>Payment mode <span class="req">*</span></label>
									<select id="payment_mode" class="form-control" <?php echo $is_view ? 'disabled' : ''; ?>>
										<?php
										$cur_mode = $is_view ? $receipt['master']['payment_mode'] : 'UPI';
										foreach ($pay_modes as $code => $label) {
											$sel = ($code === $cur_mode) ? ' selected' : '';
											echo '<option value="' . htmlspecialchars($code) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
										}
										?>
									</select>
								</div>
								<div class="ew-field span-2" id="payment_upi_ref_wrap">
									<label id="payment_upi_ref_label">UPI reference</label>
									<input type="text" class="form-control" id="payment_ref_upi" value="<?php echo $is_view ? htmlspecialchars($receipt['master']['payment_ref']) : ''; ?>" <?php echo $is_view ? 'readonly' : ''; ?> placeholder="UPI transaction ID (optional)" />
								</div>
								<div class="ew-field" id="payment_ref_wrap" style="display:none;">
									<label id="payment_ref_label">Cheque No <span class="req">*</span></label>
									<input type="text" class="form-control" id="payment_ref" value="<?php echo $is_view ? htmlspecialchars($receipt['master']['payment_ref']) : ''; ?>" <?php echo $is_view ? 'readonly' : ''; ?> />
								</div>
								<div class="ew-field" id="payment_ref_date_wrap" style="display:none;">
									<label id="payment_ref_date_label">Cheque date <span class="req">*</span></label>
									<?php
									$ref_date_val = ($is_view && !empty($receipt['master']['payment_ref_date'])) ? $receipt['master']['payment_ref_date'] : date('d-m-Y');
									echo ew_date_input(array('id' => 'payment_ref_date', 'value' => $ref_date_val, 'required' => false, 'readonly' => $is_view));
									?>
								</div>
								<div class="ew-field" id="payment_remarks_wrap">
									<label>Remarks</label>
									<input type="text" class="form-control ew-remarks-compact" id="remarks" maxlength="500" value="<?php echo $is_view ? htmlspecialchars($receipt['master']['remarks']) : ''; ?>" <?php echo $is_view ? 'readonly' : ''; ?> placeholder="Optional notes" />
								</div>
								<div class="ew-field span-4 ew-section-label" id="payment_bank_section_label" style="display:none;">Debited to EliteWave360 bank</div>
								<div class="ew-field span-2" id="payment_bank_wrap" style="display:none;">
									<label>Bank account <span class="req">*</span></label>
									<select id="bank_account_id" class="form-control" <?php echo $is_view ? 'disabled' : ''; ?>>
										<option value="">— Select bank —</option>
										<?php
										$cur_bank = $is_view ? (int) $receipt['master']['bank_account_id'] : 0;
										foreach ($banks as $b) {
											$sel = ((int) $b['bank_account_id'] === $cur_bank) ? ' selected' : '';
											echo '<option value="' . (int) $b['bank_account_id'] . '"' . $sel . '>' . htmlspecialchars($b['label']) . '</option>';
										}
										?>
									</select>
								</div>
								<div class="ew-field" id="payment_tds_wrap">
									<label>TDS deduction</label>
									<input type="text" class="form-control" id="tds_total_display" readonly value="<?php echo $is_view ? htmlspecialchars(billing_format_money($receipt['master']['tds_total'])) : '0.00'; ?>" />
								</div>
							</div>
						</div>
					</div>

					<div class="ew-card ew-receipt-attach-card">
						<div class="ew-form-body ew-receipt-attach-body">
							<?php
							$ew_upload_input_name = 'attachments[]';
							$ew_upload_id_prefix = 'receipt_attach';
							$ew_upload_existing_items = array();
							$ew_upload_readonly = $is_view;
							if ($is_view && !empty($receipt['attachments'])) {
								foreach ($receipt['attachments'] as $att) {
									$ew_upload_existing_items[] = array(
										'name' => $att['original_name'] ?: basename($att['file_path']),
										'sub' => 'Saved attachment',
										'readonly' => true,
										'download_url' => 'receipt_attachment.php?id=' . (int) $att['attachment_id'],
									);
								}
							}
							require __DIR__ . '/include/ew_attachment_upload.php';
							?>
						</div>
						<?php if (!$is_view) { ?>
						<div class="ew-form-footer ew-invoice-form-footer">
							<a href="receipt_list.php" class="ew-btn-v2 ew-btn-v2-outline">Cancel</a>
							<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btn_submit"><i class="fa fa-check"></i> Submit</button>
						</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>
<script src="javascripts/ew-attachment-upload.js?v=20260922"></script>
<script>
var isView = <?php echo $is_view ? 'true' : 'false'; ?>;
var viewReceiptType = <?php echo $is_view ? json_encode($receipt['master']['receipt_type']) : 'null'; ?>;
var viewLines = <?php echo $is_view ? json_encode($receipt['lines']) : '[]'; ?>;

function toast(msg, type) {
	if (typeof ewFormToast === 'function') ewFormToast(msg, type || 'error', 5000);
}

function isAgainst() {
	if (isView && viewReceiptType) return viewReceiptType === 'against_invoice';
	return $('#receipt_type').val() === 'against_invoice';
}

function syncBlocks() {
	var against = isAgainst();
	$('#block_against').toggleClass('active', against);
	$('#block_simple').toggleClass('active', !against);
	$('#block_against_alloc').toggleClass('active', against);
}

function syncAllocEmptyState() {
	var hasRows = $('#alloc_body tr').not('.ew-receipt-alloc-empty').length > 0;
	$('#alloc_empty_row').toggle(!hasRows);
}

function fmt(n) {
	var v = parseFloat(String(n).replace(/,/g, '')) || 0;
	return v.toFixed(2);
}

function recalcAlloc() {
	var balSum = 0, recvSum = 0, tdsSum = 0;
	$('#alloc_body tr').each(function() {
		var bal = parseFloat($(this).data('balance')) || 0;
		var recv = parseFloat($(this).find('.recv-input').val()) || 0;
		var tds = parseFloat($(this).find('.tds-input').val()) || 0;
		balSum += bal;
		recvSum += recv;
		tdsSum += tds;
	});
	$('#foot_balance').text(fmt(balSum));
	$('#foot_receive').text(fmt(recvSum));
	$('#total_amount_against').val(fmt(recvSum));
	$('#tds_total_display').val(fmt(tdsSum));
}

function renderAllocLines(lines) {
	var html = '';
	$.each(lines || [], function(i, r) {
		var recv = r.receive_now !== undefined ? r.receive_now : r.balance;
		html += '<tr data-invoice-id="' + r.billing_invoice_id + '" data-balance="' + r.balance + '">';
		html += '<td>' + (r.invoice_no || '') + '</td><td>' + (r.invoice_date || '') + '</td>';
		html += '<td class="num">' + fmt(r.invoice_amount) + '</td>';
		html += '<td class="num">' + fmt(r.already_received) + '</td>';
		html += '<td class="num">' + fmt(r.credit_note) + '</td>';
		html += '<td class="num"><input type="number" step="0.01" min="0" class="form-control tds-input" value="0.00" /></td>';
		html += '<td class="num">' + fmt(r.balance) + '</td>';
		html += '<td class="num"><input type="number" step="0.01" min="0" class="form-control recv-input" value="' + fmt(recv) + '" /></td>';
		html += '</tr>';
	});
	if (!html) {
		html = '<tr class="ew-receipt-alloc-empty" id="alloc_empty_row"><td colspan="8">Select customer and invoice(s) to load allocation.</td></tr>';
	}
	$('#alloc_body').html(html);
	syncAllocEmptyState();
	recalcAlloc();
}

function loadInvoices() {
	var cid = $('#party_id_against').val();
	if (!cid) {
		$('#invoice_ids').empty().trigger('change');
		return;
	}
	$.getJSON('create_receipt_data.php', { cmd: 'fetch_invoices', customer_id: cid }, function(r) {
		if (!r || r.status !== 0) return;
		var opts = '';
		$.each(r.data || [], function(i, row) {
			opts += '<option value="' + row.billing_invoice_id + '">' + row.invoice_no + ' · Bal ' + row.balance + '</option>';
		});
		$('#invoice_ids').html(opts).trigger('change');
	});
}

function loadAllocation() {
	var ids = $('#invoice_ids').val() || [];
	if (!ids.length) {
		renderAllocLines([]);
		return;
	}
	$.getJSON('create_receipt_data.php', { cmd: 'fetch_allocation', invoice_ids: ids }, function(r) {
		if (!r || r.status !== 0) return;
		renderAllocLines(r.lines);
	});
}

var receiptBankOptions = <?php echo json_encode($banks, JSON_UNESCAPED_UNICODE); ?>;

function paymentModeRequiresInstrument(mode) {
	mode = String(mode || '').toUpperCase();
	return $.inArray(mode, ['CHEQUE', 'NEFT', 'RTGS', 'IMPS', 'DD']) >= 0;
}

function paymentInstrumentLabels(mode) {
	mode = String(mode || '').toUpperCase();
	if (mode === 'CHEQUE') {
		return { ref: 'Cheque No', date: 'Cheque date' };
	}
	if (mode === 'DD') {
		return { ref: 'DD No', date: 'DD date' };
	}
	if (mode === 'NEFT' || mode === 'RTGS' || mode === 'IMPS') {
		return { ref: 'Transaction No', date: 'Transaction date' };
	}
	return { ref: 'Reference No', date: 'Date' };
}

function syncPaymentDetailsFields() {
	var mode = String($('#payment_mode').val() || '').toUpperCase();
	var instrument = paymentModeRequiresInstrument(mode);
	var isUpi = (mode === 'UPI');
	var isCash = (mode === 'CASH');

	$('#payment_upi_ref_wrap').toggle(isUpi);
	$('#payment_ref_wrap, #payment_ref_date_wrap, #payment_bank_section_label, #payment_bank_wrap').toggle(instrument);

	if (isUpi) {
		$('#payment_ref_upi').prop('disabled', false);
	} else {
		$('#payment_ref_upi').val('');
	}

	if (instrument) {
		var labels = paymentInstrumentLabels(mode);
		$('#payment_ref_label').html(labels.ref + ' <span class="req">*</span>');
		$('#payment_ref_date_label').html(labels.date + ' <span class="req">*</span>');
		if (!isView && !$('#payment_ref').val() && $('#payment_ref_upi').val()) {
			$('#payment_ref').val($('#payment_ref_upi').val());
		}
		if (!isView && !$('#bank_account_id').val() && receiptBankOptions.length === 1) {
			$('#bank_account_id').val(String(receiptBankOptions[0].bank_account_id));
		}
	} else if (!isUpi) {
		$('#payment_ref').val('');
		$('#payment_ref_date').val('');
	}
	if (isCash || isUpi) {
		$('#bank_account_id').val('');
	}

	var $remarks = $('#payment_remarks_wrap');
	if (instrument) {
		$remarks.insertAfter('#payment_ref_date_wrap');
		$('#payment_tds_wrap').insertAfter('#payment_bank_wrap');
	} else if (isUpi) {
		$remarks.insertAfter('#payment_upi_ref_wrap');
		$('#payment_tds_wrap').insertAfter('#payment_remarks_wrap');
	} else {
		$remarks.insertAfter($('#payment_mode').closest('.ew-field'));
		$('#payment_tds_wrap').insertAfter('#payment_remarks_wrap');
	}
	$('#payment_tds_wrap').show();
}

function collectPaymentRef() {
	var mode = String($('#payment_mode').val() || '').toUpperCase();
	if (mode === 'UPI') {
		return ($('#payment_ref_upi').val() || '').trim();
	}
	if (paymentModeRequiresInstrument(mode)) {
		return ($('#payment_ref').val() || '').trim();
	}
	return '';
}

function collectLines() {
	var lines = [];
	$('#alloc_body tr').each(function() {
		var invId = $(this).data('invoice-id');
		var recv = parseFloat($(this).find('.recv-input').val()) || 0;
		var tds = parseFloat($(this).find('.tds-input').val()) || 0;
		if (invId && recv > 0) {
			lines.push({ billing_invoice_id: invId, receive_now: recv, tds_amount: tds });
		}
	});
	return lines;
}

function submitReceipt() {
	var against = isAgainst();
	var fd = new FormData();
	fd.append('receipt_type', against ? 'against_invoice' : $('#receipt_type').val());
	fd.append('receipt_date', against ? $('#receipt_date_against').val() : $('#receipt_date_simple').val());
	if (against) {
		fd.append('party_id', $('#party_id_against').val() || '0');
		fd.append('lines', JSON.stringify(collectLines()));
	} else {
		fd.append('party_id', $('#party_id_simple').val() || '0');
		fd.append('payer_name', $('#payer_name').val() || '');
		fd.append('total_amount', $('#total_amount_simple').val() || '0');
	}
	fd.append('payment_mode', $('#payment_mode').val());
	fd.append('payment_ref', collectPaymentRef());
	fd.append('payment_ref_date', paymentModeRequiresInstrument($('#payment_mode').val()) ? ($('#payment_ref_date').val() || '') : '');
	fd.append('bank_account_id', $('#bank_account_id').val() || '0');
	fd.append('remarks', $('#remarks').val());
	$('input[name="attachments[]"]').each(function() {
		if (this.files && this.files[0]) fd.append('attachments[]', this.files[0]);
	});
	$('#btn_submit').prop('disabled', true);
	$.ajax({
		url: 'save_billing_receipt.php',
		type: 'POST',
		data: fd,
		processData: false,
		contentType: false,
		dataType: 'json'
	}).done(function(r) {
		$('#btn_submit').prop('disabled', false);
		if (!r || r.status !== 0) {
			toast(r && r.message ? r.message : 'Save failed.');
			return;
		}
		toast(r.message, 'success');
		setTimeout(function() { window.location.href = 'receipt_list.php'; }, 900);
	}).fail(function() {
		$('#btn_submit').prop('disabled', false);
		toast('Network error.');
	});
}

$(function() {
	syncBlocks();
	syncPaymentDetailsFields();
	if (!isView) {
		$('#payment_mode').on('change', syncPaymentDetailsFields);
		$('#receipt_type').on('change', syncBlocks);
		$('#party_id_against').select2({ width: '100%', placeholder: 'Select customer' });
		$('#invoice_ids').select2({ width: '100%', placeholder: 'Select invoice(s)', closeOnSelect: false });
		$('#party_id_simple').select2({ width: '100%', allowClear: true, placeholder: 'Optional client' });
		$('#party_id_against').on('change', loadInvoices);
		$('#invoice_ids').on('change', loadAllocation);
		$(document).on('input', '.recv-input, .tds-input', recalcAlloc);
		$('#btn_submit').on('click', submitReceipt);
		if (typeof ewInitAttachmentUpload === 'function') {
			ewInitAttachmentUpload({
				inputName: 'attachments[]',
				inputIdPrefix: 'receipt_attach'
			});
		}
	} else {
		if (viewReceiptType) {
			$('#receipt_type').length && $('#receipt_type').val(viewReceiptType);
		}
		syncBlocks();
		if (viewReceiptType === 'against_invoice' && viewLines.length) {
			$('#alloc_body').empty();
			$.each(viewLines, function(i, l) {
				var row = '<tr><td>' + l.invoice_no + '</td><td>' + l.invoice_date + '</td>';
				row += '<td class="num">' + fmt(l.invoice_amount) + '</td><td class="num">' + fmt(l.already_received) + '</td>';
				row += '<td class="num">' + fmt(l.credit_note_amount) + '</td><td class="num">' + fmt(l.tds_amount) + '</td>';
				row += '<td class="num">' + fmt(l.balance_amount) + '</td><td class="num">' + fmt(l.receive_now) + '</td></tr>';
				$('#alloc_body').append(row);
			});
			syncAllocEmptyState();
		}
	}
});
</script>
</body>
</html>
