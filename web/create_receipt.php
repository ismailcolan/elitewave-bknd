<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);
ew_company_bank_ensure_schema($conn);

$c_date = date('d-m-Y');
$preview_no = billing_preview_receipt_number($conn);
$receipt_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = ($receipt_id > 0 && !empty($_GET['edit']));
$is_view = ($receipt_id > 0 && !$is_edit);
if (!$is_view && !$is_edit && empty($_GET['create'])) {
	header('Location: receipt_list.php');
	exit;
}

$receipt = null;
if ($is_view || $is_edit) {
	$receipt = billing_get_receipt($conn, $receipt_id);
	if (!$receipt) {
		header('Location: receipt_list.php');
		exit;
	}
	if ($is_edit && ($receipt['master']['status'] ?? '') === 'cancelled') {
		header('Location: create_receipt.php?id=' . (int) $receipt_id);
		exit;
	}
	$preview_no = $receipt['master']['receipt_no'];
	$c_date = $receipt['master']['receipt_date'];
}

$type_opts = billing_receipt_type_options();
$pay_modes = ew_company_bank_payment_modes();
$banks = ew_company_bank_options($conn);
$tds_percent_options = billing_receipt_tds_percent_options($conn);
$clients_q = mysqli_query($conn, "SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC");
$view_against_amount = '0.00';
if ($is_view && !empty($receipt['lines'])) {
	$view_inv_sum = 0.0;
	foreach ($receipt['lines'] as $vl) {
		$view_inv_sum += (float) ($vl['receive_now'] ?? 0);
	}
	$view_against_amount = billing_format_money($view_inv_sum);
}
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-page-v2--invoice .ew-receipt-remarks-full {
			width: 100%;
			min-height: 120px;
			resize: vertical;
		}
		#block_against .select2-drop .select2-result-label { white-space: nowrap; }
		#block_against .select2-container-multi .select2-choices .select2-search-choice { max-width: none; }
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
							<h1 class="ew-page-title"><?php echo $is_view ? 'View Receipt' : ($is_edit ? 'Edit Receipt' : 'Create Receipt'); ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<?php if ($is_view) { ?>
							<a href="receipt_pdf.php?id=<?php echo (int) $receipt_id; ?>&amp;download=1" class="ew-btn-v2 ew-btn-v2-primary" target="_blank" rel="noopener"><i class="fa fa-download"></i> Download PDF</a>
							<?php } ?>
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
										<?php
										$cur_type = $is_edit ? ($receipt['master']['receipt_type'] ?? 'against_invoice') : 'against_invoice';
										foreach ($type_opts as $k => $label) {
											$sel = ($k === $cur_type) ? ' selected' : '';
											echo '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
										}
										?>
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
										$sel_party = ($is_view || $is_edit) ? (int) $receipt['master']['party_id'] : 0;
										while ($c = mysqli_fetch_assoc($clients_q)) {
											$sel = ((int) $c['client_id'] === $sel_party) ? ' selected' : '';
											echo '<option value="' . (int) $c['client_id'] . '"' . $sel . '>' . htmlspecialchars($c['client_company_name']) . '</option>';
										}
										?>
									</select>
								</div>
								<?php if (!$is_view) { ?>
								<div class="ew-field span-2" id="invoice_picker_wrap">
									<label>Invoice No <span class="req">*</span></label>
									<select id="invoice_ids" class="form-control" multiple="multiple"></select>
									<p class="ew-field-hint">Select one or more invoices (invoice no. · remaining Bal). Tags stay in this field; balance 0 is not listed.</p>
								</div>
								<?php } ?>
								<div class="ew-field">
									<label>Amount</label>
									<input type="text" class="form-control" id="total_amount_against" readonly value="<?php echo $is_view ? htmlspecialchars($view_against_amount) : '0.00'; ?>" />
									<p class="ew-field-hint">Total cash receiving on this receipt (sum of Receiving column).</p>
								</div>
								<?php if (!$is_view) { ?>
								<div class="ew-field" id="against_tds_pct_wrap">
									<label>TDS %</label>
									<select id="tds_percent" class="form-control">
										<?php
										$default_tds = 2.0;
										if ($is_edit && (float) ($receipt['master']['tds_total'] ?? 0) < 0.009) {
											$default_tds = 0.0;
										}
										$sel_zero = ($default_tds < 0.009) ? ' selected' : '';
										?>
										<option value="0"<?php echo $sel_zero; ?>>No TDS (0%)</option>
										<?php
										foreach ($tds_percent_options as $opt) {
											$rate = (float) $opt['rate'];
											$sel = ($default_tds >= 0.009 && abs($rate - $default_tds) < 0.001) ? ' selected' : '';
											echo '<option value="' . htmlspecialchars((string) $rate, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
												. htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8') . '</option>';
										}
										?>
									</select>
									<p class="ew-field-hint ew-field-hint--compact">Auto TDS on pre-GST base; edit in allocation.</p>
								</div>
								<?php } elseif ($is_view) { ?>
								<div class="ew-field ew-field--spacer" aria-hidden="true"></div>
								<?php } ?>
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
											$sel = (($is_view || $is_edit) && (int) $c['client_id'] === (int) $receipt['master']['party_id']) ? ' selected' : '';
											echo '<option value="' . (int) $c['client_id'] . '"' . $sel . '>' . htmlspecialchars($c['client_company_name']) . '</option>';
										}
										?>
									</select>
								</div>
								<div class="ew-field span-2">
									<label>Payer name (if not in client list)</label>
									<input type="text" class="form-control" id="payer_name" value="<?php echo ($is_view || $is_edit) ? htmlspecialchars($receipt['master']['payer_name']) : ''; ?>" />
								</div>
								<div class="ew-field span-2">
									<label>Amount <span class="req">*</span></label>
									<input type="number" step="0.01" min="0" class="form-control" id="total_amount_simple" value="<?php echo ($is_view || $is_edit) ? htmlspecialchars($receipt['master']['total_amount']) : ''; ?>" />
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
											<colgroup>
												<col class="alloc-col-inv">
												<col class="alloc-col-date">
												<col span="3" class="alloc-col-amount">
												<col span="2" class="alloc-col-input">
												<col class="alloc-col-amount">
												<?php if (!$is_view) { ?><col class="alloc-col-action"><?php } ?>
											</colgroup>
											<thead>
												<tr>
													<th>Inv. No</th><th>Inv. Date</th>
													<th class="num">Inv. Value</th>
													<th class="num">Already Received</th><th class="num">Credit Note</th>
													<th class="num">TDS</th><th class="num">Receiving</th><th class="num" title="Remaining invoice balance after this receipt">Balance</th>
													<?php if (!$is_view) { ?><th class="ew-alloc-remove-col" aria-label="Remove"></th><?php } ?>
												</tr>
											</thead>
											<tbody id="alloc_body">
												<tr class="ew-receipt-alloc-empty" id="alloc_empty_row"><td colspan="<?php echo $is_view ? 8 : 9; ?>"><?php echo $is_edit ? 'Loading invoice allocation…' : 'Select customer and invoice(s) to load allocation.'; ?></td></tr>
											</tbody>
											<tfoot>
												<tr class="totals-row">
													<td colspan="2" class="ew-alloc-foot-label"><strong>Total</strong></td>
													<td class="num ew-alloc-foot-spacer"></td>
													<td class="num ew-alloc-foot-spacer"></td>
													<td class="num ew-alloc-foot-spacer"></td>
													<td class="num ew-alloc-foot-num-cell"><span class="ew-alloc-total-num" id="foot_tds">0.00</span></td>
													<td class="num ew-alloc-foot-num-cell"><span class="ew-alloc-total-num" id="foot_receiving">0.00</span></td>
													<td class="num ew-alloc-foot-num-cell"><span class="ew-alloc-total-num" id="foot_balance">0.00</span></td>
													<?php if (!$is_view) { ?><td class="ew-alloc-remove-col"></td><?php } ?>
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
										$cur_mode = ($is_view || $is_edit) ? $receipt['master']['payment_mode'] : 'UPI';
										foreach ($pay_modes as $code => $label) {
											$sel = ($code === $cur_mode) ? ' selected' : '';
											echo '<option value="' . htmlspecialchars($code) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
										}
										?>
									</select>
								</div>
								<div class="ew-field span-2" id="payment_upi_ref_wrap">
									<label id="payment_upi_ref_label">UPI reference</label>
									<input type="text" class="form-control" id="payment_ref_upi" value="<?php echo ($is_view || $is_edit) ? htmlspecialchars($receipt['master']['payment_ref']) : ''; ?>" <?php echo $is_view ? 'readonly' : ''; ?> placeholder="UPI transaction ID (optional)" />
								</div>
								<div class="ew-field" id="payment_ref_wrap" style="display:none;">
									<label id="payment_ref_label">Cheque No <span class="req">*</span></label>
									<input type="text" class="form-control" id="payment_ref" value="<?php echo ($is_view || $is_edit) ? htmlspecialchars($receipt['master']['payment_ref']) : ''; ?>" <?php echo $is_view ? 'readonly' : ''; ?> />
								</div>
								<div class="ew-field" id="payment_ref_date_wrap" style="display:none;">
									<label id="payment_ref_date_label">Cheque date <span class="req">*</span></label>
									<?php
									$ref_date_val = (($is_view || $is_edit) && !empty($receipt['master']['payment_ref_date'])) ? $receipt['master']['payment_ref_date'] : date('d-m-Y');
									echo ew_date_input(array('id' => 'payment_ref_date', 'value' => $ref_date_val, 'required' => false, 'readonly' => $is_view));
									?>
								</div>
								<div class="ew-field span-4 ew-section-label" id="payment_bank_section_label" style="display:none;">Debited to EliteWave360 bank</div>
								<div class="ew-field span-2" id="payment_bank_wrap" style="display:none;">
									<label>Bank account <span class="req">*</span></label>
									<select id="bank_account_id" class="form-control" <?php echo $is_view ? 'disabled' : ''; ?>>
										<option value="">— Select bank —</option>
										<?php
										$cur_bank = ($is_view || $is_edit) ? (int) $receipt['master']['bank_account_id'] : 0;
										foreach ($banks as $b) {
											$sel = ((int) $b['bank_account_id'] === $cur_bank) ? ' selected' : '';
											echo '<option value="' . (int) $b['bank_account_id'] . '"' . $sel . '>' . htmlspecialchars($b['label']) . '</option>';
										}
										?>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="ew-card ew-invoice-details-card ew-receipt-remarks-card">
						<h2 class="ew-card-section-title">Narration</h2>
						<div class="ew-form-body">
							<div class="ew-form-grid ew-form-grid--invoice">
								<div class="ew-field span-4">
									<label for="remarks">Notes</label>
									<textarea class="form-control ew-receipt-remarks-full" id="remarks" name="remarks" maxlength="500" rows="5" <?php echo $is_view ? 'readonly' : ''; ?> placeholder="Optional notes for this receipt"><?php echo ($is_view || $is_edit) ? htmlspecialchars($receipt['master']['remarks']) : ''; ?></textarea>
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
							if (($is_view || $is_edit) && !empty($receipt['attachments'])) {
								foreach ($receipt['attachments'] as $att) {
									$att_name = $att['original_name'] ?: basename($att['file_path']);
									$att_ext = strtolower(pathinfo($att_name, PATHINFO_EXTENSION));
									$att_is_image = in_array($att_ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true);
									$att_url = 'receipt_attachment.php?id=' . (int) $att['attachment_id'];
									$ew_upload_existing_items[] = array(
										'name' => $att_name,
										'sub' => 'Saved attachment',
										'readonly' => true,
										'download_url' => $att_url,
										'preview_src' => ($att_is_image || $att_ext === 'pdf') ? ($att_url . '&inline=1') : null,
										'preview_type' => $att_is_image ? 'image' : ($att_ext === 'pdf' ? 'pdf' : ''),
									);
								}
							}
							require __DIR__ . '/include/ew_attachment_upload.php';
							?>
						</div>
						<?php if (!$is_view) { ?>
						<div class="ew-form-footer ew-invoice-form-footer">
							<a href="receipt_list.php" class="ew-btn-v2 ew-btn-v2-outline">Cancel</a>
							<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btn_submit"><i class="fa fa-check"></i> <?php echo $is_edit ? 'Update' : 'Submit'; ?></button>
						</div>
						<?php } else { ?>
						<div class="ew-form-footer ew-invoice-form-footer">
							<a href="receipt_list.php" class="ew-btn-v2 ew-btn-v2-outline">Close</a>
							<a href="receipt_pdf.php?id=<?php echo (int) $receipt_id; ?>&amp;download=1" class="ew-btn-v2 ew-btn-v2-primary" target="_blank" rel="noopener"><i class="fa fa-download"></i> Download PDF</a>
						</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>
<script src="javascripts/ew-attachment-upload.js?v=20261029attachpreview"></script>
<script>
var isView = <?php echo $is_view ? 'true' : 'false'; ?>;
var isEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;
var allocTableColspan = <?php echo $is_view ? 8 : 9; ?>;
var editReceiptId = <?php echo $is_edit ? (int) $receipt_id : 0; ?>;
var editReceiptType = <?php echo $is_edit ? json_encode($receipt['master']['receipt_type']) : 'null'; ?>;
var editLines = <?php echo $is_edit ? json_encode($receipt['lines']) : '[]'; ?>;
var viewReceiptType = <?php echo $is_view ? json_encode($receipt['master']['receipt_type']) : 'null'; ?>;
var viewLines = <?php echo $is_view ? json_encode($receipt['lines']) : '[]'; ?>;

function excludeReceiptIdParam() {
	return (isEdit && editReceiptId) ? editReceiptId : 0;
}

/** Server invoice list for customer; options rebuilt from this + live balances. */
var invoiceCatalog = {};
/** Live remaining balance per invoice row in allocation table. */
var invoiceLiveBalances = {};
var suppressInvoiceSelectChange = false;
/** Last Invoice No multiselect value (create); used to sync table on add/remove tags. */
var lastInvoicePickerIds = [];

function effectiveInvoiceBalance(id) {
	id = String(id);
	var live = invoiceLiveBalances[id];
	if (live && live.bal !== undefined && live.bal !== '') {
		return parseFloat(String(live.bal).replace(/,/g, '')) || 0;
	}
	var cat = invoiceCatalog[id];
	if (!cat) {
		return 0;
	}
	return parseFloat(String(cat.balance).replace(/,/g, '')) || 0;
}

function receiptInvoiceSelect2DropdownLabel(item) {
	if (!item || !item.id) {
		return item && item.text ? item.text : '';
	}
	var id = String(item.id);
	var live = invoiceLiveBalances[id];
	var $opt = $('#invoice_ids option[value="' + id + '"]');
	var no = (live && live.no) ? live.no : ($opt.attr('data-inv-no') || $.trim(String(item.text || '').split('\u00b7')[0]));
	var bal = (live && live.bal !== undefined) ? live.bal : ($opt.attr('data-bal') || fmt(effectiveInvoiceBalance(id)));
	if (!no) {
		return item.text || id;
	}
	return no + ' \u00b7 Bal ' + bal;
}

function getAllocationTableInvoiceIds() {
	var ids = [];
	$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
		var id = $(this).data('invoice-id');
		if (id) {
			ids.push(String(id));
		}
	});
	return ids;
}

function invoicePickerSelectedIds() {
	var ids = normalizeInvoicePickerIds($('#invoice_ids').val());
	if (!ids.length) {
		ids = lastInvoicePickerIds.slice();
	}
	return ids;
}

function rebuildInvoiceSelectOptions() {
	var pinned = {};
	$.each(invoicePickerSelectedIds(), function(i, id) {
		pinned[String(id)] = true;
	});
	var html = '';
	$.each(invoiceCatalog, function(id, row) {
		var balNum = effectiveInvoiceBalance(id);
		if (balNum <= 0.009 && !pinned[id]) {
			return;
		}
		var balStr = invoiceLiveBalances[id] ? invoiceLiveBalances[id].bal : fmt(balNum);
		var no = row.invoice_no || '';
		var noEsc = String(no).replace(/"/g, '&quot;');
		html += '<option value="' + id + '" data-inv-no="' + noEsc + '" data-bal="' + balStr + '">'
			+ no + ' \u00b7 Bal ' + balStr + '</option>';
	});
	$('#invoice_ids').html(html);
}

function setInvoicePickerVal(ids, reinitSelect2) {
	var $sel = $('#invoice_ids');
	if (!$sel.length) {
		return;
	}
	ids = filterInvoiceIdsWithOptions(normalizeInvoicePickerIds(ids));
	suppressInvoiceSelectChange = true;
	if (reinitSelect2 && $sel.data('select2')) {
		$sel.select2('destroy');
		initInvoiceSelect2();
	}
	if ($sel.data('select2')) {
		$sel.select2('val', ids);
	} else {
		$sel.val(ids);
	}
	suppressInvoiceSelectChange = false;
	lastInvoicePickerIds = ids.slice();
}

function rebuildInvoicePickerAfterCatalogChange() {
	rebuildInvoiceSelectOptions();
	setInvoicePickerVal(invoicePickerSelectedIds(), true);
}

function normalizeInvoicePickerIds(val) {
	if (!val) {
		return [];
	}
	if (!$.isArray(val)) {
		val = [val];
	}
	var out = [];
	$.each(val, function(i, id) {
		id = String(id);
		if (id && $.inArray(id, out) < 0) {
			out.push(id);
		}
	});
	return out;
}

function filterInvoiceIdsWithOptions(ids) {
	var allowed = {};
	$('#invoice_ids option').each(function() {
		allowed[String($(this).val())] = true;
	});
	var out = [];
	$.each(ids || [], function(i, id) {
		id = String(id);
		if (allowed[id] && $.inArray(id, out) < 0) {
			out.push(id);
		}
	});
	return out;
}

function initInvoiceSelect2() {
	var $sel = $('#invoice_ids');
	if (!$sel.length) {
		return;
	}
	if ($sel.data('select2')) {
		$sel.select2('destroy');
	}
	$sel.select2({
		width: '100%',
		placeholder: 'Select invoice(s)',
		closeOnSelect: false,
		formatResult: receiptInvoiceSelect2DropdownLabel,
		formatSelection: receiptInvoiceSelect2DropdownLabel,
		matcher: function(term, text, option) {
			term = $.trim(String(term || '')).toUpperCase();
			if (term === '') {
				return true;
			}
			var $opt = option ? $(option) : null;
			var no = ($opt && $opt.attr('data-inv-no')) ? String($opt.attr('data-inv-no')).toUpperCase() : '';
			var hay = (no || String(text || '')).toUpperCase();
			return hay.indexOf(term) >= 0;
		}
	});
}

function refreshInvoiceSelect2Display(selectedIds) {
	var $sel = $('#invoice_ids');
	if (!$sel.length) {
		return;
	}
	var val = selectedIds !== undefined ? selectedIds : ($sel.val() || []);
	if (!$.isArray(val)) {
		val = val ? [val] : [];
	}
	suppressInvoiceSelectChange = true;
	if ($sel.data('select2')) {
		$sel.select2('destroy');
	}
	initInvoiceSelect2();
	if (val.length) {
		$sel.select2('val', val);
	}
	suppressInvoiceSelectChange = false;
}

function toast(msg, type) {
	if (typeof ewFormToast === 'function') ewFormToast(msg, type || 'error', 5000);
}

function currentReceiptType() {
	if (isView && viewReceiptType) {
		return viewReceiptType;
	}
	return $('#receipt_type').val() || 'against_invoice';
}

function isAgainst() {
	return currentReceiptType() === 'against_invoice';
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

function tdsPercentValue() {
	return parseFloat($('#tds_percent').val()) || 0;
}

function rowOpenBalance($row) {
	var v = $row.data('openBalance');
	if (v === undefined || v === null || v === '') {
		v = $row.attr('data-open-balance');
	}
	return parseFloat(v) || 0;
}

function rowInvoiceGrandTotal($row) {
	var v = $row.data('invoiceAmount');
	if (v === undefined || v === null || v === '') {
		v = $row.attr('data-invoice-amount');
	}
	return parseFloat(v) || 0;
}

function rowGstRate($row) {
	var v = $row.data('gstRate');
	if (v === undefined || v === null || v === '') {
		v = $row.attr('data-gst-rate');
	}
	return parseFloat(v) || 0;
}

/** Pre-GST base from full invoice total (GST 0% → no division). */
function amountBeforeGst(inclusiveAmount, gstRatePercent) {
	inclusiveAmount = parseFloat(inclusiveAmount) || 0;
	gstRatePercent = parseFloat(gstRatePercent) || 0;
	if (inclusiveAmount <= 0) {
		return 0;
	}
	if (gstRatePercent <= 0) {
		return inclusiveAmount;
	}
	return Math.round(inclusiveAmount / (1 + gstRatePercent / 100) * 100) / 100;
}

function calcRowReceive($row) {
	return parseFloat($row.find('.receiving-input').val()) || 0;
}

function rowMaxReceiving($row) {
	var openBal = rowOpenBalance($row);
	var tds = parseFloat($row.find('.tds-input').val()) || 0;
	var max = Math.round((openBal - tds) * 100) / 100;
	return max > 0 ? max : 0;
}

function calcTdsFromPercent(taxableAmount) {
	var pct = tdsPercentValue();
	taxableAmount = parseFloat(taxableAmount) || 0;
	if (pct <= 0 || taxableAmount <= 0) {
		return 0;
	}
	return Math.round(taxableAmount * pct / 100 * 100) / 100;
}

function rowTdsTaxableAmount($row) {
	return amountBeforeGst(rowInvoiceGrandTotal($row), rowGstRate($row));
}

function updateRowBalance($row) {
	var openBal = rowOpenBalance($row);
	var tds = parseFloat($row.find('.tds-input').val()) || 0;
	var recv = calcRowReceive($row);
	var touched = $row.data('receivingTouched') === 1 || recv > 0.009;
	var remain;
	if (!touched) {
		remain = openBal;
	} else {
		remain = Math.round((openBal - tds - recv) * 100) / 100;
	}
	if (remain < 0) {
		remain = 0;
	}
	var over = tds - openBal > 0.009 || recv + tds - openBal > 0.009;
	if (over) {
		$row.find('.bal-display').addClass('text-danger');
	} else {
		$row.find('.bal-display').removeClass('text-danger');
	}
	$row.find('.bal-display').text(fmt(remain));
	return remain;
}

/** Keep cash receiving within open balance after TDS (recv + TDS ≤ balance). */
function syncReceivingAfterTdsChange($row) {
	var openBal = rowOpenBalance($row);
	var tds = parseFloat($row.find('.tds-input').val()) || 0;
	var recv = calcRowReceive($row);
	var maxRecv = Math.max(0, Math.round((openBal - tds) * 100) / 100);
	if (recv + tds - openBal > 0.009) {
		$row.find('.receiving-input').val(fmt(maxRecv));
		$row.data('receivingTouched', 1);
	}
}

function applyTdsPercentToRow($row) {
	if (!$row || !$row.length || $row.hasClass('ew-receipt-alloc-empty')) {
		return;
	}
	if ($row.data('tdsManual') === 1 || $row.data('allocContinuation') === 1) {
		return;
	}
	var pct = tdsPercentValue();
	if (pct <= 0) {
		$row.find('.tds-input').val(fmt(0));
	} else {
		$row.find('.tds-input').val(fmt(calcTdsFromPercent(rowTdsTaxableAmount($row))));
	}
	syncReceivingAfterTdsChange($row);
}

function applyTdsPercentToAllRows() {
	$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
		applyTdsPercentToRow($(this));
	});
	recalcAlloc();
}

function refreshInvoiceLiveBalancesFromTable() {
	if (isEdit) {
		return;
	}
	invoiceLiveBalances = {};
	$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
		var $row = $(this);
		var id = String($row.data('invoice-id') || '');
		if (!id) {
			return;
		}
		var invNo = $row.data('invoice-no') || $.trim($row.find('td:first').text());
		var rowBal = parseFloat(String($row.find('.bal-display').text()).replace(/,/g, '')) || 0;
		if (!invoiceLiveBalances[id]) {
			invoiceLiveBalances[id] = { no: invNo, balNum: 0 };
		}
		invoiceLiveBalances[id].balNum += rowBal;
	});
	$.each(invoiceLiveBalances, function(id, o) {
		o.bal = fmt(o.balNum);
	});
}

function tablePriorAppliedForInvoice(invoiceId) {
	var recv = 0;
	var tds = 0;
	var rowCount = 0;
	var lastRemain = null;
	$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
		if (String($(this).data('invoice-id')) !== String(invoiceId)) {
			return;
		}
		rowCount++;
		var $row = $(this);
		recv += calcRowReceive($row);
		tds += parseFloat($row.find('.tds-input').val()) || 0;
		lastRemain = parseFloat(String($row.find('.bal-display').text()).replace(/,/g, '')) || 0;
	});
	return { recv: recv, tds: tds, rowCount: rowCount, lastRemain: lastRemain };
}

function adjustLineForTableAppend(r) {
	var invoiceId = String(r.billing_invoice_id);
	var prior = tablePriorAppliedForInvoice(invoiceId);
	if (prior.rowCount <= 0) {
		return r;
	}
	var snapAlready = parseFloat(String(r.already_received).replace(/,/g, '')) || 0;
	var openSnap = parseFloat(String(r.balance).replace(/,/g, '')) || 0;
	var remain = prior.lastRemain;
	if (remain === null || remain === undefined) {
		remain = Math.max(0, Math.round((openSnap - prior.recv - prior.tds) * 100) / 100);
	}
	r.already_received = snapAlready + prior.recv;
	r.balance = remain;
	r.tds_amount = 0;
	r.receive_now = '';
	r._allocContinuation = true;
	r._baseAlreadyReceived = snapAlready;
	return r;
}

function lastTableRowForInvoice(invoiceId) {
	var $last = null;
	$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
		if (String($(this).data('invoice-id')) === String(invoiceId)) {
			$last = $(this);
		}
	});
	return $last;
}

function buildAllocRowHtml(r, opts) {
	opts = opts || {};
	var openBal = parseFloat(String(r.balance).replace(/,/g, '')) || 0;
	var invAmt = parseFloat(String(r.invoice_amount).replace(/,/g, '')) || 0;
	var gstRate = parseFloat(String(r.gst_rate || '0').replace(/,/g, '')) || 0;
	var tdsVal = r.tds_amount !== undefined ? r.tds_amount : 0;
	var recvVal = r.receive_now !== undefined ? r.receive_now : '';
	var invNoEsc = (r.invoice_no || '').replace(/"/g, '&quot;');
	var baseAlready = r._baseAlreadyReceived !== undefined
		? parseFloat(r._baseAlreadyReceived) || 0
		: (parseFloat(String(r.already_received).replace(/,/g, '')) || 0);
	var cont = r._allocContinuation ? ' data-alloc-continuation="1"' : '';
	var html = '<tr data-invoice-id="' + r.billing_invoice_id + '" data-invoice-no="' + invNoEsc + '" data-open-balance="' + openBal + '" data-invoice-amount="' + invAmt + '" data-gst-rate="' + gstRate + '" data-base-already-received="' + baseAlready + '"' + cont + '>';
	html += '<td>' + (r.invoice_no || '') + '</td><td>' + (r.invoice_date || '') + '</td>';
	html += '<td class="num">' + fmt(r.invoice_amount) + '</td>';
	html += '<td class="num ew-already-received">' + fmt(r.already_received) + '</td>';
	html += '<td class="num">' + fmt(r.credit_note) + '</td>';
	html += '<td class="num"><input type="number" step="0.01" min="0" class="form-control tds-input" value="' + fmt(tdsVal) + '" /></td>';
	html += '<td class="num"><input type="number" step="0.01" min="0" class="form-control receiving-input" value="' + (recvVal === '' ? '' : fmt(recvVal)) + '" placeholder="0.00" /></td>';
	html += '<td class="num bal-display">' + fmt(openBal) + '</td>';
	if (!isView && opts.removable !== false) {
		html += '<td class="ew-alloc-remove-col"><button type="button" class="ew-alloc-row-remove" title="Remove row" aria-label="Remove row"><i class="fa fa-times"></i></button></td>';
	}
	html += '</tr>';
	return html;
}

function insertAllocRow(r) {
	var invoiceId = String(r.billing_invoice_id);
	r = adjustLineForTableAppend(r);
	var $row = $(buildAllocRowHtml(r));
	var $anchor = lastTableRowForInvoice(invoiceId);
	if ($anchor && $anchor.length) {
		$anchor.after($row);
	} else {
		$('#alloc_body').append($row);
	}
	if (r._allocContinuation) {
		$row.data('allocContinuation', 1);
		$row.data('tdsManual', 1);
		if (r._baseAlreadyReceived !== undefined) {
			$row.data('baseAlreadyReceived', r._baseAlreadyReceived);
		}
	}
	return $row;
}

function syncSameInvoiceAlreadyReceived() {
	var accum = {};
	$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
		var id = String($(this).data('invoice-id'));
		var $row = $(this);
		var base = $row.data('baseAlreadyReceived');
		if (base === undefined || base === null) {
			base = parseFloat(String($row.attr('data-base-already-received')).replace(/,/g, '')) || 0;
			$row.data('baseAlreadyReceived', base);
		}
		if (!accum[id]) {
			accum[id] = { base: base, recvPrior: 0 };
		}
		if ($row.data('allocContinuation') === 1) {
			$row.find('.ew-already-received').text(fmt(accum[id].base + accum[id].recvPrior));
		}
		accum[id].recvPrior += calcRowReceive($row);
	});
}

function appendAllocLinesToTable(lines) {
	if (!lines || !lines.length) {
		return;
	}
	$('#alloc_empty_row').remove();
	$.each(lines, function(i, r) {
		insertAllocRow(r);
	});
	syncAllocEmptyState();
	applyTdsPercentToAllRows();
}

function bootstrapInvoiceCatalogForCustomer(customerId, done) {
	if (!customerId) {
		invoiceCatalog = {};
		invoiceLiveBalances = {};
		lastInvoicePickerIds = [];
		rebuildInvoiceSelectOptions();
		setInvoicePickerVal([], true);
		if (done) {
			done();
		}
		return;
	}
	$.getJSON('create_receipt_data.php', {
		cmd: 'fetch_invoices',
		customer_id: customerId,
		exclude_receipt_id: excludeReceiptIdParam()
	}, function(r) {
		if (!r || r.status !== 0) {
			if (done) {
				done();
			}
			return;
		}
		invoiceCatalog = {};
		invoiceLiveBalances = {};
		$.each(r.data || [], function(i, row) {
			var id = String(row.billing_invoice_id);
			invoiceCatalog[id] = {
				billing_invoice_id: row.billing_invoice_id,
				invoice_no: row.invoice_no || '',
				balance: row.balance || '0.00'
			};
		});
		if (done) {
			done();
		}
	});
}

function ensureEditLinesInCatalog() {
	$.each(editLines || [], function(i, l) {
		var id = String(l.billing_invoice_id || '');
		if (!id) {
			return;
		}
		if (!invoiceCatalog[id]) {
			invoiceCatalog[id] = {
				billing_invoice_id: l.billing_invoice_id,
				invoice_no: l.invoice_no || '',
				balance: l.balance_amount || '0.00'
			};
		}
	});
}

function addAllocLinesFromFetch(lines) {
	if (!lines || !lines.length) {
		return;
	}
	$('#alloc_empty_row').remove();
	$.each(lines, function(i, r) {
		var openBal = parseFloat(String(r.balance).replace(/,/g, '')) || 0;
		if (r.receive_now === undefined || r.receive_now === '') {
			r.receive_now = openBal;
		}
		var $row = $(buildAllocRowHtml(r));
		$row.data('receivingTouched', 1);
		$('#alloc_body').append($row);
	});
	syncAllocEmptyState();
	applyTdsPercentToAllRows();
}

function syncInvoicePickerToTable() {
	var ids = normalizeInvoicePickerIds($('#invoice_ids').val());
	var prev = lastInvoicePickerIds.slice();
	var added = [];
	var removed = [];
	$.each(ids, function(i, id) {
		if ($.inArray(id, prev) < 0) {
			added.push(id);
		}
	});
	$.each(prev, function(i, id) {
		if ($.inArray(id, ids) < 0) {
			removed.push(id);
		}
	});
	lastInvoicePickerIds = ids.slice();

	$.each(removed, function(i, id) {
		$('#alloc_body tr[data-invoice-id="' + id + '"]').remove();
	});

	if (!ids.length) {
		if (removed.length) {
			syncAllocEmptyState();
			if (!$('#alloc_body tr').not('.ew-receipt-alloc-empty').length) {
				renderAllocLines([]);
			}
			recalcAlloc();
		}
		return;
	}

	if (!added.length) {
		if (removed.length) {
			recalcAlloc();
		}
		return;
	}

	$.getJSON('create_receipt_data.php', {
		cmd: 'fetch_allocation',
		invoice_ids: added,
		exclude_receipt_id: excludeReceiptIdParam()
	}, function(r) {
		if (!r || r.status !== 0 || !r.lines || !r.lines.length) {
			toast('Could not load invoice.', 'error');
			lastInvoicePickerIds = prev.slice();
			suppressInvoiceSelectChange = true;
			if ($('#invoice_ids').data('select2')) {
				$('#invoice_ids').select2('val', prev);
			} else {
				$('#invoice_ids').val(prev);
			}
			suppressInvoiceSelectChange = false;
			rebuildInvoicePickerAfterCatalogChange();
			return;
		}
		addAllocLinesFromFetch(r.lines);
		rebuildInvoicePickerAfterCatalogChange();
		recalcAlloc();
	});
}

function recalcAlloc() {
	var remainSum = 0, tdsSum = 0, recvSum = 0;
	var lastRemainByInv = {};
	$('#alloc_body tr').each(function() {
		if ($(this).hasClass('ew-receipt-alloc-empty')) {
			return;
		}
		var $row = $(this);
		var invId = String($row.data('invoice-id') || '');
		if (invId && lastRemainByInv[invId] !== undefined) {
			$row.attr('data-open-balance', lastRemainByInv[invId]);
			$row.data('openBalance', lastRemainByInv[invId]);
		}
		var tds = parseFloat($row.find('.tds-input').val()) || 0;
		var recv = calcRowReceive($row);
		var remain = updateRowBalance($row);
		if (invId) {
			lastRemainByInv[invId] = remain;
		}
		remainSum += remain;
		tdsSum += tds;
		recvSum += recv;
	});
	syncSameInvoiceAlreadyReceived();
	$('#foot_balance').text(fmt(remainSum));
	$('#foot_tds').text(fmt(tdsSum));
	$('#foot_receiving').text(fmt(recvSum));
	$('#total_amount_against').val(fmt(recvSum));
	refreshInvoiceLiveBalancesFromTable();
}

function renderAllocLines(lines, opts) {
	opts = opts || {};
	var html = '';
	$.each(lines || [], function(i, r) {
		html += buildAllocRowHtml(r);
	});
	if (!html) {
		html = '<tr class="ew-receipt-alloc-empty" id="alloc_empty_row"><td colspan="' + allocTableColspan + '">Select customer, then pick an invoice above to add a row.</td></tr>';
	}
	$('#alloc_body').html(html);
	syncAllocEmptyState();
	if (opts.preserveTds) {
		$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
			$(this).data('tdsManual', 1);
		});
		recalcAlloc();
	} else {
		applyTdsPercentToAllRows();
	}
}

function loadInvoices() {
	var cid = $('#party_id_against').val();
	var hadAlloc = $('#alloc_body tr').not('.ew-receipt-alloc-empty').length > 0;
	if (!cid) {
		invoiceCatalog = {};
		invoiceLiveBalances = {};
		lastInvoicePickerIds = [];
		$('#invoice_ids').empty();
		setInvoicePickerVal([], true);
		renderAllocLines([]);
		return;
	}
	bootstrapInvoiceCatalogForCustomer(cid, function() {
		lastInvoicePickerIds = [];
		rebuildInvoicePickerAfterCatalogChange();
		renderAllocLines([]);
		if (isEdit && hadAlloc) {
			toast('Customer changed — select invoice(s) again for allocation.', 'warning');
		}
	});
}

function editReceiptUniqueInvoiceIds() {
	var seen = {};
	var ids = [];
	$.each(editLines || [], function(i, l) {
		var id = String(l.billing_invoice_id || '');
		if (id && !seen[id]) {
			seen[id] = true;
			ids.push(id);
		}
	});
	return ids;
}

function initEditReceiptWithInvoices() {
	if (!isEdit || !editReceiptId || !isAgainst()) {
		return;
	}
	var partyId = $('#party_id_against').val();
	var pickerIds = editReceiptUniqueInvoiceIds();
	bootstrapInvoiceCatalogForCustomer(partyId, function() {
		ensureEditLinesInCatalog();
		rebuildInvoiceSelectOptions();
		initInvoiceSelect2();
		setInvoicePickerVal(pickerIds, false);
		lastInvoicePickerIds = pickerIds.slice();
		initEditReceipt();
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
		var tds = parseFloat($(this).find('.tds-input').val()) || 0;
		var recv = calcRowReceive($(this));
		if (invId && recv > 0.009) {
			lines.push({ billing_invoice_id: invId, receive_now: recv, tds_amount: tds });
		}
	});
	return lines;
}

function submitReceipt() {
	var against = isAgainst();
	if (against) {
		var over = false;
		var hasLine = false;
		$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
			var openBal = rowOpenBalance($(this));
			var tds = parseFloat($(this).find('.tds-input').val()) || 0;
			var recv = calcRowReceive($(this));
			if (recv > 0.009) {
				hasLine = true;
			}
			if (tds - openBal > 0.009 || recv + tds - openBal > 0.009) {
				over = true;
			}
		});
		if (!hasLine) {
			toast('Select at least one invoice with outstanding balance.', 'warning');
			return;
		}
		if (over) {
			toast('Receiving plus TDS cannot exceed the invoice balance on a line.', 'warning');
			return;
		}
	}
	var fd = new FormData();
	var receiptType = currentReceiptType();
	fd.append('receipt_type', receiptType);
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
	if (isEdit && editReceiptId) {
		fd.append('billing_receipt_id', String(editReceiptId));
	}
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

function editLineTdsMap() {
	var m = {};
	$.each(editLines || [], function(i, l) {
		m[l.billing_invoice_id] = parseFloat(l.tds_amount) || 0;
	});
	return m;
}

function editLineReceiveMap() {
	var m = {};
	$.each(editLines || [], function(i, l) {
		m[l.billing_invoice_id] = parseFloat(l.receive_now) || 0;
	});
	return m;
}

function initEditReceipt() {
	if (!isEdit || !editReceiptId || !isAgainst()) {
		return;
	}
	var ids = [];
	$.each(editLines || [], function(i, l) {
		if (l.billing_invoice_id) {
			ids.push(String(l.billing_invoice_id));
		}
	});
	if (!ids.length) {
		return;
	}
	var snapById = {};
	$.getJSON('create_receipt_data.php', {
		cmd: 'fetch_allocation',
		invoice_ids: ids,
		exclude_receipt_id: editReceiptId
	}, function(ar) {
			if (ar && ar.status === 0 && ar.lines) {
				$.each(ar.lines, function(i, row) {
					snapById[row.billing_invoice_id] = row;
				});
			}
			var lines = [];
			$.each(editLines || [], function(i, l) {
				var invId = parseInt(l.billing_invoice_id, 10);
				if (!invId) {
					return;
				}
				var snap = snapById[invId] || {};
				lines.push({
					billing_invoice_id: invId,
					invoice_no: l.invoice_no || snap.invoice_no || '',
					invoice_date: l.invoice_date || snap.invoice_date || '',
					invoice_amount: l.invoice_amount || snap.invoice_amount,
					already_received: snap.already_received !== undefined ? snap.already_received : l.already_received,
					credit_note: l.credit_note_amount !== undefined ? l.credit_note_amount : snap.credit_note,
					balance: snap.balance !== undefined ? snap.balance : l.balance_amount,
					gst_rate: snap.gst_rate || '0',
					tds_amount: l.tds_amount,
					receive_now: l.receive_now
				});
			});
			renderAllocLines(lines, { preserveTds: true });
			$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
				if (calcRowReceive($(this)) > 0.009) {
					$(this).data('receivingTouched', 1);
				}
			});
			recalcAlloc();
		});
}

$(function() {
	syncBlocks();
	syncPaymentDetailsFields();
	if (!isView) {
		$('#payment_mode').on('change', syncPaymentDetailsFields);
		$('#receipt_type').on('change', function() {
			syncBlocks();
			syncPaymentDetailsFields();
			if (isEdit && isAgainst() && !$('#alloc_body tr').not('.ew-receipt-alloc-empty').length) {
				initEditReceipt();
			}
		});
		$('#party_id_against').select2({ width: '100%', placeholder: 'Select customer' });
		if ($('#invoice_ids').length) {
			initInvoiceSelect2();
			$('#party_id_against').on('change', loadInvoices);
			$('#invoice_ids').on('change', function() {
				if (suppressInvoiceSelectChange) {
					return;
				}
				syncInvoicePickerToTable();
			});
		}
		$('#party_id_simple').select2({ width: '100%', allowClear: true, placeholder: 'Optional client' });
		$(document).on('input', '.tds-input', function() {
			var $row = $(this).closest('tr');
			$row.data('tdsManual', 1);
			syncReceivingAfterTdsChange($row);
			recalcAlloc();
		});
		$(document).on('input', '.receiving-input', function() {
			$(this).closest('tr').data('receivingTouched', 1);
			recalcAlloc();
		});
		$(document).on('click', '.ew-alloc-row-remove', function() {
			var id = String($(this).closest('tr').data('invoice-id') || '');
			$(this).closest('tr').remove();
			if (!isView && id && $('#invoice_ids').length) {
				var ids = normalizeInvoicePickerIds($('#invoice_ids').val()).filter(function(x) {
					return x !== id;
				});
				suppressInvoiceSelectChange = true;
				if ($('#invoice_ids').data('select2')) {
					$('#invoice_ids').select2('val', ids);
				} else {
					$('#invoice_ids').val(ids);
				}
				suppressInvoiceSelectChange = false;
				lastInvoicePickerIds = ids.slice();
			}
			syncAllocEmptyState();
			if (!$('#alloc_body tr').not('.ew-receipt-alloc-empty').length) {
				$('#alloc_body').html('<tr class="ew-receipt-alloc-empty" id="alloc_empty_row"><td colspan="' + allocTableColspan + '">Select customer, then pick an invoice above to add a row.</td></tr>');
			}
			recalcAlloc();
		});
		$('#tds_percent').on('change', function() {
			$('#alloc_body tr').not('.ew-receipt-alloc-empty').each(function() {
				$(this).removeData('tdsManual');
			});
			applyTdsPercentToAllRows();
		});
		$('#btn_submit').on('click', submitReceipt);
		if (typeof ewInitAttachmentUpload === 'function') {
			ewInitAttachmentUpload({
				inputName: 'attachments[]',
				inputIdPrefix: 'receipt_attach'
			});
		}
		if (isEdit) {
			initEditReceiptWithInvoices();
		}
	} else {
		if (viewReceiptType) {
			$('#receipt_type').length && $('#receipt_type').val(viewReceiptType);
		}
		syncBlocks();
		if (viewReceiptType === 'against_invoice' && viewLines.length) {
			$('#alloc_body').empty();
			var footTds = 0, footBal = 0, footRecv = 0;
			$.each(viewLines, function(i, l) {
				var openBal = parseFloat(l.balance_amount) || 0;
				var recv = parseFloat(l.receive_now) || 0;
				var tds = parseFloat(l.tds_amount) || 0;
				var remain = Math.round((openBal - recv - tds) * 100) / 100;
				footTds += tds;
				footBal += remain;
				footRecv += recv;
				var row = '<tr><td>' + l.invoice_no + '</td><td>' + l.invoice_date + '</td>';
				row += '<td class="num">' + fmt(l.invoice_amount) + '</td>';
				row += '<td class="num">' + fmt(l.already_received) + '</td><td class="num">' + fmt(l.credit_note_amount) + '</td>';
				row += '<td class="num">' + fmt(l.tds_amount) + '</td>';
				row += '<td class="num">' + fmt(recv) + '</td><td class="num">' + fmt(remain) + '</td></tr>';
				$('#alloc_body').append(row);
			});
			$('#foot_tds').text(fmt(footTds));
			$('#foot_receiving').text(fmt(footRecv));
			$('#foot_balance').text(fmt(footBal));
			syncAllocEmptyState();
		}
	}
});
</script>
</body>
</html>
