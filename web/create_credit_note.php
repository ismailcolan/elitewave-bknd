<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_note_functions.php');

ensure_billing_tables($conn);
ensure_billing_note_tables($conn);

$c_date = date('d-m-Y');
$preview_no = billing_preview_credit_note_number($conn, $c_date);
$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$preselect_invoice = isset($_GET['invoice_id']) ? (int) $_GET['invoice_id'] : 0;
$is_edit = ($edit_id > 0);
if (!$is_edit && empty($_GET['create'])) {
	header('Location: credit_note_list.php');
	exit;
}

$note = null;
$is_final = false;
$is_cancelled = false;
$form_readonly = false;
if ($is_edit) {
	$note = billing_get_credit_note($conn, $edit_id);
	if (!$note) {
		header('Location: credit_note_list.php');
		exit;
	}
	$preselect_invoice = (int) $note['master']['against_invoice_id'];
	$note_status = $note['master']['status'] ?? 'draft';
	$is_final = ($note_status === 'final');
	$is_cancelled = ($note_status === 'cancelled');
	$form_readonly = $is_cancelled;
	if (!empty($note['master']['note_no'])) {
		$preview_no = $note['master']['note_no'];
	}
	$c_date = $note['master']['note_date'] ?: $c_date;
}

$invoices = billing_final_invoices_for_credit_note($conn);
$reasons = billing_credit_note_reasons();
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.cn-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 8px; }
		.cn-stat {
			border: 1px solid #D8DDE5;
			border-radius: 10px;
			padding: 12px 14px;
			background: #fff;
		}
		.cn-stat b { display: block; font-size: 18px; font-weight: 800; color: #0A1E3D; letter-spacing: -.02em; text-align: right; }
		.cn-stat.ok b { color: #16A34A; }
		.cn-stat span { display: block; margin-top: 4px; font-size: 12px; color: #6B7A8D; font-weight: 600; }
		.cn-gcn { font-size: 13px; color: #334155; }
		.cn-gst-table { width: 100%; font-size: 13px; margin-top: 14px; border-collapse: collapse; }
		.cn-gst-table th {
			background: #DDE7F0;
			font-size: 11px;
			font-weight: 700;
			padding: 8px 10px;
			text-align: left;
			border-bottom: 2px solid #C5D3E0;
		}
		.cn-gst-table td { padding: 8px 10px; border-bottom: 1px solid #e9ecef; background: #fff; }
		.cn-gst-table tbody tr:nth-child(even) td { background: #f9fafb; }
		.cn-gst-table .num { text-align: right; white-space: nowrap; }
		.cn-gst-table tfoot td { font-weight: 700; background: #f4f7fb; border-top: 2px solid #C5D3E0; }
		.cn-gst-empty { margin: 8px 0 0; font-size: 13px; color: #6B7A8D; }
		@media (max-width: 800px) { .cn-stats { grid-template-columns: 1fr 1fr; } }
		.cn-line-row {
			grid-column: 1 / -1;
			display: grid;
			grid-template-columns: minmax(140px, 1fr) minmax(120px, 1fr) minmax(200px, 2fr);
			gap: 20px 16px;
			align-items: start;
		}
		.cn-line-row .ew-field { min-width: 0; }
		.cn-line-row textarea.form-control {
			min-height: 34px;
			height: 34px;
			resize: vertical;
			padding-top: 6px;
			padding-bottom: 6px;
		}
		@media (max-width: 991px) {
			.cn-line-row {
				grid-template-columns: 1fr 1fr;
			}
			.cn-line-row .cn-field-desc { grid-column: 1 / -1; }
			.cn-line-row textarea.form-control { height: auto; min-height: 56px; }
		}
		@media (max-width: 640px) {
			.cn-line-row { grid-template-columns: 1fr; }
			.cn-line-row .cn-field-desc { grid-column: span 1; }
		}
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
				<div class="ew-page-v2 ew-page-v2--invoice">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<a href="credit_note_list.php" class="ew-back-btn" title="Back to list"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php
								if ($form_readonly) {
									echo 'View Credit Note';
								} elseif ($is_edit) {
									echo 'Edit Credit Note';
								} else {
									echo 'Create Credit Note';
								}
							?></h1>
						</div>
						<div class="ew-toolbar-right">
							<a href="credit_note_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
						</div>
					</div>

					<div class="ew-card ew-invoice-details-card">
						<h2 class="ew-card-section-title">Note Details</h2>
						<div class="ew-form-body">
							<input type="hidden" id="billing_note_id" value="<?php echo $edit_id; ?>">
							<div class="ew-form-grid ew-form-grid--invoice">
								<div class="ew-field span-2">
									<label>Note No</label>
									<div class="ew-invoice-no-display" id="note_no_preview"><?php echo htmlspecialchars($preview_no); ?></div>
									<p class="ew-field-hint" style="margin-top:6px;">One series for GST and GTA invoices: CN/001/26-27.</p>
								</div>
								<div class="ew-field">
									<label>Date <span class="req">*</span></label>
									<?php echo ew_date_input(array('id' => 'note_date', 'value' => $c_date, 'required' => true, 'readonly' => $form_readonly)); ?>
								</div>
								<div class="ew-field span-2">
									<label>Against Final Invoice <span class="req">*</span></label>
									<select id="against_invoice_id" class="form-control filter-select" <?php echo $form_readonly ? 'disabled' : ''; ?>>
										<option value=""></option>
										<?php foreach ($invoices as $inv) {
											$sel = ((int) $inv['billing_invoice_id'] === $preselect_invoice) ? ' selected' : '';
											$label = $inv['invoice_no'] . ' · ' . $inv['invoice_date'] . ' · ' . $inv['client_company_name'] . ' · ' . number_format((float) $inv['grand_total'], 2);
											echo '<option value="' . (int) $inv['billing_invoice_id'] . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
										} ?>
									</select>
								</div>
								<div class="ew-field">
									<label>Customer</label>
									<input type="text" class="form-control" id="customer_name" readonly>
								</div>
								<div class="ew-field">
									<label>GST (from invoice)</label>
									<input type="text" class="form-control" id="gst_label" readonly>
								</div>
								<div class="ew-field span-2">
									<label>Linked GCN</label>
									<div class="cn-gcn" id="gcn_label">Select an invoice.</div>
								</div>
								<div class="ew-field span-2">
									<label>Reason <span class="req">*</span></label>
									<select id="reason" class="form-control" <?php echo $form_readonly ? 'disabled' : ''; ?>>
										<option value="">--- Select Reason ---</option>
										<?php
										$saved_reason = $note ? $note['master']['reason'] : '';
										foreach ($reasons as $r) {
											$sel = ($saved_reason === $r) ? ' selected' : '';
											echo '<option value="' . htmlspecialchars($r) . '"' . $sel . '>' . htmlspecialchars($r) . '</option>';
										}
										?>
									</select>
								</div>
								<div class="cn-line-row">
									<div class="ew-field cn-field-amt">
										<label>Credit Note Amount <span class="req">*</span></label>
										<input type="number" step="0.01" min="0" class="form-control" id="taxable_value" value="<?php echo $note ? htmlspecialchars(billing_format_money($note['master']['taxable_value'])) : ''; ?>" <?php echo $form_readonly ? 'readonly' : ''; ?>>
									</div>
									<div class="ew-field cn-field-gst">
										<label>GST Claim</label>
										<input type="text" class="form-control" id="gst_amount" readonly value="<?php echo $note ? htmlspecialchars(billing_format_money($note['master']['gst_amount'])) : '0.00'; ?>">
									</div>
									<div class="ew-field cn-field-desc">
										<label>Description</label>
										<textarea id="line_description" class="form-control" rows="1" <?php echo $form_readonly ? 'readonly' : ''; ?>><?php echo $note ? htmlspecialchars($note['master']['line_description']) : ''; ?></textarea>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="ew-card" id="gst_claim_card" style="display:none;">
						<h2 class="ew-card-section-title">GST claim tracking (this invoice)</h2>
						<div class="ew-form-body">
							<div class="cn-stats">
								<div class="cn-stat"><b id="gst_inv">0.00</b><span>Invoice GST</span></div>
								<div class="cn-stat"><b id="gst_claimed">0.00</b><span>GST claimed (final CNs)</span></div>
								<div class="cn-stat"><b id="gst_this">0.00</b><span>This note — GST claim</span></div>
								<div class="cn-stat ok"><b id="gst_remaining">0.00</b><span>GST balance after this note</span></div>
							</div>
							<p class="cn-gst-empty" id="gst_history_empty" style="display:none;">No other credit notes on this invoice yet.</p>
							<div class="table-scroll-wrapper" id="gst_history_wrap" style="display:none;border:1px solid #e5e7eb;border-radius:6px;">
								<table class="cn-gst-table" id="gst_claim_history_table">
									<thead>
										<tr>
											<th>Note No</th>
											<th>Date</th>
											<th>Status</th>
											<th class="num">Credit Note Amount</th>
											<th class="num">GST Claim</th>
											<th class="num">Total</th>
										</tr>
									</thead>
									<tbody id="gst_claim_history_body"></tbody>
									<tfoot>
										<tr>
											<td colspan="3">Total (listed notes)</td>
											<td class="num" id="gst_hist_amt">0.00</td>
											<td class="num" id="gst_hist_gst">0.00</td>
											<td class="num" id="gst_hist_total">0.00</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>

					<div class="ew-card">
						<h2 class="ew-card-section-title">Invoice after this credit note</h2>
						<div class="ew-form-body">
							<div class="cn-stats">
								<div class="cn-stat"><b id="st_inv">0.00</b><span>Invoice amount</span></div>
								<div class="cn-stat"><b id="st_paid">0.00</b><span>Already paid</span></div>
								<div class="cn-stat"><b id="st_cn">0.00</b><span>Credit notes</span></div>
								<div class="cn-stat ok"><b id="st_bal">0.00</b><span>To Pay</span></div>
							</div>
							<p class="muted" style="margin:12px 0 0;font-size:13px;color:#6B7A8D;">Already paid stays 0 until receipts are allocated to invoices. The original tax invoice PDF is not changed.</p>
							<div class="ew-form-footer ew-invoice-form-footer">
								<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_cancel">Cancel</button>
								<?php if (!$form_readonly && !$is_final) { ?>
								<button type="button" class="ew-btn-v2 ew-btn-v2-draft" id="btn_draft">Save as Draft</button>
								<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btn_finalise"><i class="fa fa-check"></i> Finalise</button>
								<?php } elseif (!$form_readonly && $is_final) { ?>
								<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btn_update"><i class="fa fa-check"></i> Update</button>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>
<script type="text/javascript">
var invoiceSnap = null;

function money(n) {
	var v = parseFloat(n);
	if (isNaN(v)) v = 0;
	return v.toFixed(2);
}

function toast(msg, type) {
	if (typeof ewFormToast === 'function') ewFormToast(msg, type || 'error', 5000);
}

function renderGstTracking(snap, gstThis, gstClaimedAfter, gstRemainingAfter) {
	if (!snap) {
		$('#gst_claim_card').hide();
		return;
	}
	$('#gst_claim_card').show();
	$('#gst_inv').text(money(snap.invoice_gst_amount));
	$('#gst_claimed').text(money(snap.gst_claimed_total));
	var thisGst = gstThis != null ? gstThis : (parseFloat($('#gst_amount').val()) || 0);
	$('#gst_this').text(money(thisGst));
	if (gstClaimedAfter != null) {
		$('#gst_remaining').text(money(gstRemainingAfter));
	} else {
		var rem = parseFloat(snap.gst_claim_remaining) - thisGst;
		if (rem < 0) rem = 0;
		$('#gst_remaining').text(money(rem));
	}
	var rows = snap.gst_claim_history || [];
	var $body = $('#gst_claim_history_body').empty();
	var sumAmt = 0, sumGst = 0, sumTot = 0;
	if (!rows.length) {
		$('#gst_history_empty').show();
		$('#gst_history_wrap').hide();
	} else {
		$('#gst_history_empty').hide();
		$('#gst_history_wrap').show();
		for (var i = 0; i < rows.length; i++) {
			var r = rows[i];
			sumAmt += parseFloat(r.credit_note_amount) || 0;
			sumGst += parseFloat(r.gst_claim) || 0;
			sumTot += parseFloat(r.grand_total) || 0;
			var st = r.status === 'final' ? 'Final' : 'Draft';
			$body.append(
				'<tr><td>' + $('<div>').text(r.note_no).html() + '</td>' +
				'<td>' + $('<div>').text(r.note_date).html() + '</td>' +
				'<td>' + st + '</td>' +
				'<td class="num">' + money(r.credit_note_amount) + '</td>' +
				'<td class="num">' + money(r.gst_claim) + '</td>' +
				'<td class="num">' + money(r.grand_total) + '</td></tr>'
			);
		}
	}
	$('#gst_hist_amt').text(money(sumAmt));
	$('#gst_hist_gst').text(money(sumGst));
	$('#gst_hist_total').text(money(sumTot));
}

function applySnap(snap, amounts) {
	invoiceSnap = snap;
	$('#customer_name').val(snap.customer_name || '');
	$('#gst_label').val(snap.gst_label || '');
	$('#gcn_label').text(snap.gcn_label || '—');
	$('#st_inv').text(money(snap.invoice_amount));
	$('#st_paid').text(money(snap.already_paid));
	if (amounts) {
		$('#gst_amount').val(money(amounts.gst_amount));
		$('#st_cn').text(money(amounts.credit_note_after || amounts.grand_total));
		$('#st_bal').text(money(amounts.to_pay));
		renderGstTracking(snap, amounts.gst_amount, amounts.gst_claimed_after, amounts.gst_claim_remaining_after);
	} else {
		$('#st_cn').text(money(snap.credit_note_total));
		$('#st_bal').text(money(snap.to_pay));
		renderGstTracking(snap);
	}
}

function loadInvoice(cb) {
	var id = $('#against_invoice_id').val();
	if (!id) {
		invoiceSnap = null;
		$('#customer_name').val('');
		$('#gst_label').val('');
		$('#gcn_label').text('Select an invoice.');
		$('#st_inv,#st_paid,#st_cn,#st_bal').text('0.00');
		renderGstTracking(null);
		if (cb) cb();
		return;
	}
	$.getJSON('create_credit_note_data.php', {
		cmd: 'invoice_snapshot',
		invoice_id: id,
		billing_note_id: $('#billing_note_id').val()
	}, function(r) {
		if (!r || r.status !== 0) {
			toast(r && r.message ? r.message : 'Could not load invoice.');
			return;
		}
		applySnap(r.data);
		if (cb) cb();
		else recalc();
	});
}

function recalc() {
	if (!invoiceSnap) return;
	var taxable = parseFloat($('#taxable_value').val()) || 0;
	$.getJSON('create_credit_note_data.php', {
		cmd: 'compute',
		invoice_id: invoiceSnap.billing_invoice_id,
		billing_note_id: $('#billing_note_id').val(),
		taxable_value: taxable
	}, function(r) {
		if (!r || r.status !== 0) return;
		$('#gst_amount').val(money(r.amounts.gst_amount));
		$('#st_cn').text(money(r.credit_note_after));
		$('#st_bal').text(money(r.to_pay));
		renderGstTracking(invoiceSnap, r.amounts.gst_amount, r.gst_claimed_after, r.gst_claim_remaining_after);
	});
}

function saveNote(status) {
	if (!$('#against_invoice_id').val()) {
		toast('Please select a Final tax invoice.');
		return;
	}
	if (!$('#reason').val()) {
		toast('Please select a reason.');
		return;
	}
	if ($('#reason').val() === 'Other' && !$.trim($('#line_description').val())) {
		toast('Please enter a description for Other reason.');
		return;
	}
	if (!(parseFloat($('#taxable_value').val()) > 0)) {
		toast('Enter a credit note amount greater than zero.');
		return;
	}
	$('#btn_draft, #btn_finalise, #btn_update').prop('disabled', true);
	$.post('save_billing_credit_note.php', {
		billing_note_id: $('#billing_note_id').val(),
		against_invoice_id: $('#against_invoice_id').val(),
		note_date: $('#note_date').val(),
		reason: $('#reason').val(),
		line_description: $('#line_description').val(),
		taxable_value: $('#taxable_value').val(),
		status: status
	}, function(r) {
		$('#btn_draft, #btn_finalise, #btn_update').prop('disabled', false);
		if (!r || r.status !== 0) {
			toast(r && r.message ? r.message : 'Save failed.');
			return;
		}
		toast(r.message, 'success');
		if (r.billing_note_id) {
			$('#billing_note_id').val(r.billing_note_id);
		}
		if (r.note_no) {
			$('#note_no_preview').text(r.note_no);
		}
		setTimeout(function() { window.location.href = 'credit_note_list.php'; }, status === 'final' ? 1200 : 900);
	}, 'json').fail(function() {
		$('#btn_draft, #btn_finalise, #btn_update').prop('disabled', false);
		toast('Network error while saving.');
	});
}

$(function() {
	var $inv = $('#against_invoice_id');
	if ($inv.data('select2')) $inv.select2('destroy');
	$inv.select2({
		width: '100%',
		placeholder: '--- Select Final Invoice ---',
		allowClear: false,
		minimumResultsForSearch: 8
	});
	$inv.on('change', function() { loadInvoice(); });
	$('#taxable_value').on('input change', recalc);
	$('#btn_cancel').on('click', function() { window.location.href = 'credit_note_list.php'; });
	$('#btn_draft').on('click', function() { saveNote('draft'); });
	$('#btn_finalise').on('click', function() { saveNote('final'); });
	$('#btn_update').on('click', function() { saveNote('final'); });
	if ($inv.val()) {
		loadInvoice(function() { recalc(); });
	} else if (!$('#billing_note_id').val()) {
		$.getJSON('create_credit_note_data.php', { cmd: 'preview_note_no', note_date: $('#note_date').val() }, function(r) {
			if (r && r.status === 0) $('#note_no_preview').text(r.note_no);
		});
		$('#note_date').on('change', function() {
			if ($('#billing_note_id').val()) return;
			$.getJSON('create_credit_note_data.php', { cmd: 'preview_note_no', note_date: $('#note_date').val() }, function(r) {
				if (r && r.status === 0) $('#note_no_preview').text(r.note_no);
			});
		});
	}
});
</script>
</body>
</html>
