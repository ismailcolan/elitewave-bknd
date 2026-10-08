<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$c_date = date('d-m-Y');
$preview_no = billing_preview_invoice_number($conn, $c_date);
$customers_q = mysqli_query($conn, 'SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC');
$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = ($edit_id > 0);
if (!$is_edit && empty($_GET['create'])) {
	header('Location: invoice_list.php');
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.billing-type-group { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
		.billing-type-section { margin-bottom: 10px; }
		.billing-type-section-title {
			font-size: 11px; font-weight: 700; color: #64748b;
			text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px;
		}
		.billing-type-group label {
			margin: 0; padding: 6px 14px; border: 1px solid #D8DDE5; border-radius: 20px;
			font-size: 12px; font-weight: 600; cursor: pointer; background: #fff; color: #334155;
		}
		.billing-type-group input { display: none; }
		.billing-type-group input:checked + span,
		.billing-type-group label.active { background: #0A1E3D; border-color: #0A1E3D; color: #fff; }
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
							<a href="invoice_list.php" class="ew-back-btn" title="Back to list"><i class="fa fa-arrow-left"></i></a>
							<h1 class="ew-page-title"><?php echo $is_edit ? 'Edit Tax Invoice' : 'Create Tax Invoice'; ?></h1>
						</div>
						<div class="ew-toolbar-right">
							<a href="invoice_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
						</div>
					</div>
					<div class="ew-card ew-invoice-details-card">
						<h2 class="ew-card-section-title">Invoice Details</h2>
						<div class="ew-form-body">
							<input type="hidden" id="billing_invoice_id" value="<?php echo $edit_id; ?>">

							<div class="ew-form-grid ew-form-grid--invoice">
								<div class="ew-field span-2">
									<label>Invoice No</label>
									<div class="ew-invoice-no-display" id="invoice_no_preview"><?php echo htmlspecialchars($preview_no); ?></div>
									<p class="ew-field-hint" style="margin-top:6px;">Auto-generated on save (draft or final). Same HRGST series as delivered GCN invoices — no duplicates.</p>
								</div>
								<div class="ew-field">
									<label>Date <span class="req">*</span></label>
									<?php echo ew_date_input(array('id' => 'invoice_date', 'value' => $c_date, 'required' => true, 'readonly' => true)); ?>
								</div>
								<div class="ew-field ew-field--invoice-actions">
									<label class="ew-invoice-actions-label">&nbsp;</label>
									<a href="#" class="ew-btn-v2 ew-btn-v2-outline ew-btn-pdf" id="btn_pdf_download" target="_blank"><i class="fa fa-download"></i> Download PDF</a>
								</div>

								<div class="ew-section-label"><i class="fa fa-filter"></i> Select Consignments</div>

								<div class="ew-field span-2">
									<label>Select Customer <span class="req">*</span></label>
									<select id="customers" class="form-control filter-select">
										<option value=""></option>
										<?php while ($cust = mysqli_fetch_assoc($customers_q)) { ?>
											<option value="<?php echo (int) $cust['client_id']; ?>"><?php echo htmlspecialchars($cust['client_company_name']); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="ew-field span-2">
									<label>GCN No <span class="req">*</span></label>
									<select id="gcn_keys" class="form-control filter-select" multiple="multiple"></select>
									<p class="ew-field-hint" id="gcn_result_hint">Select a customer to load delivered GCNs. GCNs already used on an invoice are not shown.</p>
								</div>
							</div>
						</div>
					</div>

				<div class="ew-card ew-invoice-lines-card table-section" id="table_section">
					<h2 class="ew-card-section-title">Invoice Consignment Details</h2>
					<div class="ew-table-wrap ew-invoice-table-wrap">
						<div class="ew-invoice-table-scroll">
							<table class="table" id="lines_table">
								<thead>
									<tr>
										<th>S.No</th><th>GCN No</th><th>Date</th><th>Sender</th><th>Receiver</th>
										<th>Pkgs</th><th>Weight</th><th>Freight</th><th>Taxable</th>
										<th>CGST</th><th>SGST</th><th>IGST</th><th>Total</th><th>Billing Type</th><th class="col-actions"></th>
									</tr>
								</thead>
								<tbody id="lines_body"></tbody>
								<tfoot id="lines_foot"></tfoot>
							</table>
						</div>
					</div>
					<div class="ew-form-footer ew-invoice-form-footer">
						<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_cancel">Cancel</button>
						<button type="button" class="ew-btn-v2 ew-btn-v2-draft" id="btn_draft">Save as Draft</button>
						<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btn_generate"><i class="fa fa-check"></i> Generate Invoice</button>
					</div>
				</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>

<script type="text/javascript">
var invoiceLines = [];

function escHtml(v) {
	if (v === null || v === undefined) return '';
	return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function getSelectedCustomers() {
	var val = $('#customers').val();
	if (!val) return [];
	return [val];
}

function getPrimaryCustomerId() {
	var vals = getSelectedCustomers();
	return vals.length ? parseInt(vals[0], 10) : 0;
}

function getInvoiceBillingType() {
	if (invoiceLines.length && invoiceLines[0].billing_type) {
		return invoiceLines[0].billing_type;
	}
	return '';
}

function updatePdfButton(invoiceId, status) {
	if (invoiceId && status === 'final') {
		$('#btn_pdf_download').attr('href', 'tax_invoice_pdf.php?id=' + invoiceId + '&download=1').show();
	} else {
		$('#btn_pdf_download').hide();
	}
}

function refreshInvoiceNo() {
	$.getJSON('create_invoice_data.php', {
		cmd: 'preview_invoice_no',
		invoice_date: $('#invoice_date').val()
	}, function(r) {
		if (r && r.status === 0) {
			$('#invoice_no_preview').text(r.invoice_no);
		}
	});
}

function initCustomerSelect(selectedVal) {
	var $el = $('#customers');
	if ($el.data('select2')) {
		$el.select2('destroy');
	}
	$el.select2({
		width: '100%',
		placeholder: '--- Select Customer ---',
		allowClear: false,
		minimumResultsForSearch: 8
	});
	if (selectedVal) {
		$el.select2('val', String(selectedVal));
	} else {
		$el.select2('val', '');
	}
}

function refreshGcnSelect(html, hint) {
	var $el = $('#gcn_keys');
	if ($el.data('select2')) {
		$el.off('change');
		$el.select2('destroy');
	}
	$el.html(html || '');
	$el.select2({
		width: '100%',
		placeholder: '--- Select GCN(s) ---',
		closeOnSelect: false,
		allowClear: true
	});
	$el.on('change', loadLineDetails);
	if (hint) {
		$('#gcn_result_hint').text(hint);
	}
}

function sumLinesFromRows(lines) {
	var totalFreight = 0;
	var taxable = 0;
	var cgst = 0;
	var sgst = 0;
	var igst = 0;
	var grand = 0;
	$.each(lines || [], function(i, r) {
		totalFreight += parseFloat(String(r.freight_amount || 0).replace(/,/g, '')) || 0;
		taxable += parseFloat(String(r.taxable_value || 0).replace(/,/g, '')) || 0;
		cgst += parseFloat(String(r.cgst_amount || 0).replace(/,/g, '')) || 0;
		sgst += parseFloat(String(r.sgst_amount || 0).replace(/,/g, '')) || 0;
		igst += parseFloat(String(r.igst_amount || 0).replace(/,/g, '')) || 0;
		grand += parseFloat(String(r.total_amount || 0).replace(/,/g, '')) || 0;
	});
	function fmt(n) { return n.toFixed(2); }
	return {
		total_freight: fmt(totalFreight),
		taxable_value: fmt(taxable),
		cgst_amount: fmt(cgst),
		sgst_amount: fmt(sgst),
		igst_amount: fmt(igst),
		grand_total: fmt(grand)
	};
}

function loadGcnOptions(selectKeys) {
	var customerId = getPrimaryCustomerId();
	if (!customerId) {
		refreshGcnSelect('', 'Select a customer to load delivered GCNs.');
		renderLines([], {});
		return;
	}
	refreshGcnSelect('', 'Loading delivered GCNs...');
	$.getJSON('create_invoice_data.php', {
		cmd: 'fetch_gcns',
		customers: customerId,
		billing_invoice_id: $('#billing_invoice_id').val()
	}, function(r) {
		if (!r || r.status !== 0) {
			refreshGcnSelect('', 'Could not load GCN list. Please try again.');
			if (typeof ewFormToast === 'function') ewFormToast('Could not load GCN list.', 'error', 5000);
			return;
		}
		var opts = '';
		var rows = r.data || [];
		if (!rows.length) {
			refreshGcnSelect('', 'No delivered GCNs found for this customer.');
			renderLines([], {});
			return;
		}
		$.each(rows, function(i, row) {
			var disabled = (row.has_conflict || row.selectable === 0) ? ' disabled="disabled"' : '';
			var label = row.grn_no;
			if (row.has_conflict) {
				label += ' (already billed)';
			}
			opts += '<option value="' + escHtml(row.key) + '"' + disabled + ' title="' + escHtml(row.label) + '">' + escHtml(label) + '</option>';
		});
		var hint = rows.length + ' delivered GCN(s) found. Select one or more.';
		if ($.grep(rows, function(row) { return row.has_conflict; }).length) {
			hint += ' GCNs marked "already billed" must be removed before saving.';
		}
		refreshGcnSelect(opts, hint);
		if (selectKeys && selectKeys.length) {
			$('#gcn_keys').val(selectKeys);
			if (window.__draftLines && window.__draftLines.length) {
				renderLines(window.__draftLines, window.__draftSummary || {});
			} else {
				$('#gcn_keys').trigger('change');
			}
		} else if (window.__draftLines && window.__draftLines.length) {
			renderLines(window.__draftLines, window.__draftSummary || {});
		} else {
			renderLines([], {});
		}
	}).fail(function() {
		refreshGcnSelect('', 'Network error while loading GCNs.');
		if (typeof ewFormToast === 'function') ewFormToast('Network error while loading GCNs.', 'error', 5000);
	});
}

function renderLines(lines, summary) {
	invoiceLines = lines || [];
	var html = '';
	$.each(invoiceLines, function(i, r) {
		html += '<tr data-key="' + escHtml(r.key) + '"' + (r.has_conflict ? ' class="warning"' : '') + '>';
		html += '<td>' + (i + 1) + '</td>';
		html += '<td>' + escHtml(r.grn_no);
		if (r.line_warning) {
			html += '<br><small style="color:#b45309;font-weight:600;">' + escHtml(r.line_warning) + '</small>';
		}
		html += '</td>';
		html += '<td>' + escHtml(r.grn_date) + '</td>';
		html += '<td>' + escHtml(r.sender) + '</td>';
		html += '<td>' + escHtml(r.receiver) + '</td>';
		html += '<td class="num">' + escHtml(r.packages) + '</td>';
		html += '<td class="num">' + escHtml(r.weight) + '</td>';
		html += '<td class="num">' + escHtml(r.freight_amount) + '</td>';
		html += '<td class="num">' + escHtml(r.taxable_value) + '</td>';
		html += '<td class="num">' + escHtml(r.cgst_amount) + '</td>';
		html += '<td class="num">' + escHtml(r.sgst_amount) + '</td>';
		html += '<td class="num">' + escHtml(r.igst_amount) + '</td>';
		html += '<td class="num">' + escHtml(r.total_amount) + '</td>';
		html += '<td>' + escHtml(r.billing_type_label || r.billing_type) + '</td>';
		html += '<td class="col-actions"><a href="#" class="act-remove btn-remove-line" data-key="' + escHtml(r.key) + '" title="Remove"><i class="fa fa-times"></i></a></td>';
		html += '</tr>';
	});
	$('#lines_body').html(html);

	if (!invoiceLines.length) {
		$('#table_section').hide();
		$('#lines_foot').html('');
		return;
	}
	$('#table_section').show();

	summary = summary || {};
	var totalPkgs = 0;
	var totalWeight = 0;
	$.each(invoiceLines, function(i, r) {
		totalPkgs += parseInt(r.packages, 10) || 0;
		totalWeight += parseFloat(String(r.weight).replace(/,/g, '')) || 0;
	});

	var foot = '';
	foot += '<tr class="totals-row">';
	foot += '<td colspan="5" class="text-right"><strong>Total</strong></td>';
	foot += '<td class="num"><strong>' + totalPkgs + '</strong></td>';
	foot += '<td class="num"><strong>' + totalWeight.toFixed(2) + '</strong></td>';
	foot += '<td class="num"><strong>' + escHtml(summary.total_freight || '0.00') + '</strong></td>';
	foot += '<td class="num"><strong>' + escHtml(summary.taxable_value || '0.00') + '</strong></td>';
	foot += '<td class="num"><strong>' + escHtml(summary.cgst_amount || '0.00') + '</strong></td>';
	foot += '<td class="num"><strong>' + escHtml(summary.sgst_amount || '0.00') + '</strong></td>';
	foot += '<td class="num"><strong>' + escHtml(summary.igst_amount || '0.00') + '</strong></td>';
	foot += '<td class="num"><strong>' + escHtml(summary.grand_total || '0.00') + '</strong></td>';
	foot += '<td></td><td class="col-actions"></td>';
	foot += '</tr>';
	$('#lines_foot').html(foot);
}

function loadLineDetails() {
	var keys = $('#gcn_keys').val() || [];
	if (!keys.length && !invoiceLines.length) {
		renderLines([], {});
		return;
	}
	var fetchKeys = keys.slice();
	$.each(invoiceLines, function(i, l) {
		if (l.has_conflict && $.inArray(l.key, fetchKeys) === -1) {
			fetchKeys.push(l.key);
		}
	});
	if (!fetchKeys.length) {
		renderLines([], {});
		return;
	}
	$.getJSON('create_invoice_data.php', {
		cmd: 'fetch_gcn_details',
		keys: fetchKeys,
		customer_id: getPrimaryCustomerId(),
		billing_type: getInvoiceBillingType(),
		billing_invoice_id: $('#billing_invoice_id').val()
	}, function(r) {
		if (!r || r.status !== 0) {
			if (typeof ewFormToast === 'function') ewFormToast('Could not load GCN details.', 'error', 5000);
			return;
		}
		var lines = r.lines || [];
		if (r.skipped && r.skipped.length) {
			if (typeof ewFormToast === 'function') ewFormToast(r.skipped.join(' '), 'warning', 7000);
		}
		renderLines(lines, sumLinesFromRows(lines));
	});
}

function saveInvoice(status) {
	if (!getPrimaryCustomerId()) {
		if (typeof ewFormToast === 'function') ewFormToast('Please select a customer.', 'error', 5000);
		return;
	}
	if (!invoiceLines.length) {
		if (typeof ewFormToast === 'function') ewFormToast('Please select at least one GCN.', 'error', 5000);
		return;
	}
	var conflictLine = null;
	$.each(invoiceLines, function(i, r) {
		if (r.has_conflict) {
			conflictLine = r;
			return false;
		}
	});
	if (conflictLine) {
		if (typeof ewFormToast === 'function') ewFormToast(conflictLine.line_warning || ('GCN ' + conflictLine.grn_no + ' cannot be invoiced again.'), 'error', 6000);
		return;
	}
	var payload = {
		billing_invoice_id: $('#billing_invoice_id').val(),
		invoice_date: $('#invoice_date').val(),
		customer_id: getPrimaryCustomerId(),
		billing_type: getInvoiceBillingType(),
		status: status,
		lines: JSON.stringify($.map(invoiceLines, function(r) {
			return { key: r.key, grn_no: r.grn_no, billing_type: r.billing_type || '' };
		}))
	};
	$('#btn_draft, #btn_generate').prop('disabled', true);
	$.post('save_billing_invoice.php', payload, function(r) {
		$('#btn_draft, #btn_generate').prop('disabled', false);
		if (!r || r.status !== 0) {
			if (typeof ewFormToast === 'function') ewFormToast(r && r.message ? r.message : 'Save failed.', 'error', 5000);
			return;
		}
		if (typeof ewFormToast === 'function') ewFormToast(r.message, 'success', 5000);
		if (r.billing_invoice_id) {
			$('#billing_invoice_id').val(r.billing_invoice_id);
		}
		if (r.invoice_no) {
			$('#invoice_no_preview').text(r.invoice_no);
		}
		if (status === 'final') {
			updatePdfButton(r.billing_invoice_id, 'final');
			if (r.pdf_url) {
				window.open(r.pdf_url, '_blank');
			}
			setTimeout(function() { window.location.href = 'invoice_list.php'; }, 1500);
		} else if (status === 'draft') {
			setTimeout(function() { window.location.href = 'invoice_list.php'; }, 1200);
		}
	}, 'json').fail(function() {
		$('#btn_draft, #btn_generate').prop('disabled', false);
		if (typeof ewFormToast === 'function') ewFormToast('Network error while saving invoice.', 'error', 5000);
	});
}

$(document).ready(function() {
	initCustomerSelect('');
	refreshGcnSelect('', 'Select a customer to load delivered GCNs.');

	$(document).on('change', '#invoice_date', refreshInvoiceNo);
	$(document).on('change', '#customers', function() {
		loadGcnOptions();
	});

	$(document).on('click', '.btn-remove-line', function(e) {
		e.preventDefault();
		var key = $(this).data('key');
		var vals = $('#gcn_keys').val() || [];
		vals = $.grep(vals, function(v) { return v !== key; });
		$('#gcn_keys').val(vals).trigger('change');
	});

	$('#btn_draft').on('click', function() { saveInvoice('draft'); });
	$('#btn_generate').on('click', function() { saveInvoice('final'); });
	$('#btn_cancel').on('click', function() { window.location.href = 'invoice_list.php'; });

	<?php if ($edit_id > 0) { ?>
	$.getJSON('create_invoice_data.php', { cmd: 'load_draft', billing_invoice_id: <?php echo $edit_id; ?> }, function(r) {
		if (!r || r.status !== 0) return;
		$('#invoice_date').val(r.master.invoice_date);
		$('#invoice_no_preview').text(r.master.invoice_no || $('#invoice_no_preview').text());
		updatePdfButton(<?php echo $edit_id; ?>, r.master.status);
		if (r.master.customer_id) {
			initCustomerSelect(r.master.customer_id);
			window.__draftLines = r.lines || [];
			window.__draftSummary = r.summary || {};
			var draftKeys = $.map(r.lines || [], function(l) { return l.has_conflict ? null : l.key; }).filter(Boolean);
			loadGcnOptions(draftKeys);
		}
	});
	<?php } ?>
});
</script>
</body>
</html>
