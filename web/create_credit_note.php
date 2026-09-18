<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_note_functions.php');

ensure_billing_tables($conn);
ensure_billing_note_tables($conn);

$c_date = date('d-m-Y');
$preview_no = billing_preview_credit_note_number($conn);
$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$preselect_invoice = isset($_GET['invoice_id']) ? (int) $_GET['invoice_id'] : 0;
$is_edit = ($edit_id > 0);
if (!$is_edit && empty($_GET['create'])) {
	header('Location: credit_note_list.php');
	exit;
}

$note = null;
$is_final = false;
if ($is_edit) {
	$note = billing_get_credit_note($conn, $edit_id);
	if (!$note) {
		header('Location: credit_note_list.php');
		exit;
	}
	$preselect_invoice = (int) $note['master']['against_invoice_id'];
	$is_final = ($note['master']['status'] === 'final');
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
		.cn-stat b { display: block; font-size: 18px; font-weight: 800; color: #0A1E3D; letter-spacing: -.02em; }
		.cn-stat.ok b { color: #16A34A; }
		.cn-stat span { display: block; margin-top: 4px; font-size: 12px; color: #6B7A8D; font-weight: 600; }
		.cn-gcn { font-size: 13px; color: #334155; }
		@media (max-width: 800px) { .cn-stats { grid-template-columns: 1fr 1fr; } }
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
								if ($is_final) {
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
								</div>
								<div class="ew-field">
									<label>Date <span class="req">*</span></label>
									<?php echo ew_date_input(array('id' => 'note_date', 'value' => $c_date, 'required' => true, 'readonly' => true)); ?>
								</div>
								<div class="ew-field span-2">
									<label>Against Final Invoice <span class="req">*</span></label>
									<select id="against_invoice_id" class="form-control filter-select" <?php echo $is_final ? 'disabled' : ''; ?>>
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
									<select id="reason" class="form-control" <?php echo $is_final ? 'disabled' : ''; ?>>
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
								<div class="ew-field span-4">
									<label>Description</label>
									<textarea id="line_description" class="form-control" rows="2" <?php echo $is_final ? 'readonly' : ''; ?>><?php echo $note ? htmlspecialchars($note['master']['line_description']) : ''; ?></textarea>
								</div>
								<div class="ew-field">
									<label>Taxable amount <span class="req">*</span></label>
									<input type="number" step="0.01" min="0" class="form-control" id="taxable_value" value="<?php echo $note ? htmlspecialchars(billing_format_money($note['master']['taxable_value'])) : ''; ?>" <?php echo $is_final ? 'readonly' : ''; ?>>
								</div>
								<div class="ew-field">
									<label>GST</label>
									<input type="text" class="form-control" id="gst_amount" readonly value="<?php echo $note ? htmlspecialchars(billing_format_money($note['master']['gst_amount'])) : '0.00'; ?>">
								</div>
								<div class="ew-field">
									<label>Credit note total</label>
									<input type="text" class="form-control" id="grand_total" readonly value="<?php echo $note ? htmlspecialchars(billing_format_money($note['master']['grand_total'])) : '0.00'; ?>">
								</div>
								<div class="ew-field">
									<label>Max remaining on invoice</label>
									<input type="text" class="form-control" id="cn_remaining" readonly>
								</div>
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
								<?php if (!$is_final) { ?>
								<button type="button" class="ew-btn-v2 ew-btn-v2-draft" id="btn_draft">Save as Draft</button>
								<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btn_finalise"><i class="fa fa-check"></i> Finalise</button>
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

function applySnap(snap, amounts) {
	invoiceSnap = snap;
	$('#customer_name').val(snap.customer_name || '');
	$('#gst_label').val(snap.gst_label || '');
	$('#gcn_label').text(snap.gcn_label || '—');
	$('#cn_remaining').val(money(snap.cn_remaining));
	$('#st_inv').text(money(snap.invoice_amount));
	$('#st_paid').text(money(snap.already_paid));
	if (amounts) {
		$('#gst_amount').val(money(amounts.gst_amount));
		$('#grand_total').val(money(amounts.grand_total));
		$('#st_cn').text(money(amounts.credit_note_after || amounts.grand_total));
		$('#st_bal').text(money(amounts.to_pay));
	} else {
		$('#st_cn').text(money(snap.credit_note_total));
		$('#st_bal').text(money(snap.to_pay));
	}
}

function loadInvoice(cb) {
	var id = $('#against_invoice_id').val();
	if (!id) {
		invoiceSnap = null;
		$('#customer_name').val('');
		$('#gst_label').val('');
		$('#gcn_label').text('Select an invoice.');
		$('#cn_remaining').val('');
		$('#st_inv,#st_paid,#st_cn,#st_bal').text('0.00');
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
		$('#grand_total').val(money(r.amounts.grand_total));
		$('#st_cn').text(money(r.credit_note_after));
		$('#st_bal').text(money(r.to_pay));
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
		toast('Enter a taxable amount greater than zero.');
		return;
	}
	$('#btn_draft, #btn_finalise').prop('disabled', true);
	$.post('save_billing_credit_note.php', {
		billing_note_id: $('#billing_note_id').val(),
		against_invoice_id: $('#against_invoice_id').val(),
		note_date: $('#note_date').val(),
		reason: $('#reason').val(),
		line_description: $('#line_description').val(),
		taxable_value: $('#taxable_value').val(),
		status: status
	}, function(r) {
		$('#btn_draft, #btn_finalise').prop('disabled', false);
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
		$('#btn_draft, #btn_finalise').prop('disabled', false);
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
	if ($inv.val()) {
		loadInvoice(function() { recalc(); });
	} else if (!$('#billing_note_id').val()) {
		$.getJSON('create_credit_note_data.php', { cmd: 'preview_note_no' }, function(r) {
			if (r && r.status === 0) $('#note_no_preview').text(r.note_no);
		});
	}
});
</script>
</body>
</html>
