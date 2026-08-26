<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$c_date = date('d-m-Y');
$preview_no = billing_preview_invoice_number($conn, $c_date);
$customers_q = mysqli_query($conn, 'SELECT client_id, client_company_name FROM client WHERE status=0 ORDER BY client_company_name ASC');
$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.invoice-header-bar {
			background: linear-gradient(185deg, var(--ew-navy) 0%, var(--ew-navy-deep) 100%);
			color: #fff;
			padding: 16px 24px;
			border-radius: 8px 8px 0 0;
			font-size: 18px;
			font-weight: 700;
		}
		.invoice-header-bar i { margin-right: 8px; }
		.filter-section { padding: 24px 20px 20px; border: 1px solid #e9ecef; border-top: none; }
		.filter-form-wrap { max-width: 960px; margin: 0 auto; }
		.filter-row { margin-bottom: 14px; }
		.filter-label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: #333; }
		.invoice-meta-box {
			background: #f8fafc;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			padding: 14px 16px;
			margin-bottom: 16px;
		}
		.invoice-meta-box .meta-val { font-weight: 700; color: #0A1E3D; font-size: 15px; }
		.billing-type-group { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
		.billing-type-section { margin-bottom: 10px; }
		.billing-type-section-title {
			font-size: 11px;
			font-weight: 700;
			color: #64748b;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			margin-bottom: 6px;
		}
		.billing-type-group label {
			margin: 0;
			padding: 6px 14px;
			border: 1px solid #D8DDE5;
			border-radius: 20px;
			font-size: 12px;
			font-weight: 600;
			cursor: pointer;
			background: #fff;
			color: #334155;
		}
		.billing-type-group input { display: none; }
		.billing-type-group input:checked + span,
		.billing-type-group label.active {
			background: #0A1E3D;
			border-color: #0A1E3D;
			color: #fff;
		}
		#lines_table { font-size: 12px; }
		#lines_table th { background: #0A1E3D; color: #fff; white-space: nowrap; font-size: 11px; }
		#lines_table td { vertical-align: middle; white-space: nowrap; }
		#lines_table .num { text-align: right; }
		.summary-panel {
			margin-top: 16px;
			padding: 14px 16px;
			background: #f8fafc;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			font-size: 13px;
		}
		.summary-panel .row-line { display: flex; justify-content: space-between; padding: 4px 0; }
		.summary-panel .grand { font-weight: 700; font-size: 15px; color: #0A1E3D; border-top: 1px solid #e5e7eb; margin-top: 8px; padding-top: 8px; }
		.btn-row { margin-top: 20px; }
		.btn1 { font-weight: 600; padding: 8px 20px !important; border-radius: 4px !important; margin-right: 8px; }
		.table-section { display: none; margin-top: 20px; }
		.filter-section .select2-container { width: 100% !important; }
		.filter-section .select2-container-multi .select2-choices {
			min-height: 38px !important; border: 1px solid #D8DDE5; border-radius: 8px; background: #fff;
		}
		.search-gcn-row { margin-top: 4px; }
		#btn_search_gcn { min-width: 120px; font-weight: 600; }
		#btn_pdf_download { display: none; font-weight: 600; }
	</style>
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
	<div class="navbar navbar-fixed-top scroll-hide">
		<?php require_once('include/header.php'); require_once('include/menu.php'); ?>
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="row">
			<div class="col-md-offset-1 col-md-10">
				<div class="widget-container fluid-height clearfix">
					<div class="invoice-header-bar"><i class="fa fa-file-text-o"></i> Create Tax Invoice</div>
					<div class="filter-section">
						<div class="filter-form-wrap">
							<input type="hidden" id="billing_invoice_id" value="<?php echo $edit_id; ?>">

							<div class="row filter-row">
								<div class="col-md-4 col-sm-6">
									<label class="filter-label">Invoice No</label>
									<div class="meta-val" id="invoice_no_preview"><?php echo htmlspecialchars($preview_no); ?></div>
								</div>
								<div class="col-md-4 col-sm-6">
									<label class="filter-label">Date <span class="required-star">*</span></label>
									<?php echo ew_date_input(array('id' => 'invoice_date', 'value' => $c_date, 'required' => true, 'readonly' => true)); ?>
								</div>
								<div class="col-md-4 col-sm-12 text-right" style="padding-top: 22px;">
									<a href="#" class="btn btn-success btn1" id="btn_pdf_download" target="_blank"><i class="fa fa-download"></i> Download PDF</a>
								</div>
							</div>

							<div class="invoice-meta-box">
							<div class="row filter-row">
								<div class="col-md-6">
									<div class="form-group">
										<label class="filter-label">Select Customer <span class="required-star">*</span></label>
										<select id="customers" class="form-control">
											<option value="">Select Customer</option>
											<?php while ($cust = mysqli_fetch_assoc($customers_q)) { ?>
												<option value="<?php echo (int) $cust['client_id']; ?>"><?php echo htmlspecialchars($cust['client_company_name']); ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label class="filter-label">GCN No <span class="required-star">*</span></label>
										<select id="gcn_keys" class="form-control" multiple></select>
										<small class="text-muted" id="gcn_result_hint">Select a customer to load delivered GCNs.</small>
									</div>
								</div>
							</div>
							</div>
						</div>
					</div>
				</div>

				<div class="widget-container fluid-height clearfix table-section" id="table_section">
					<div class="heading"><i class="fa fa-table"></i> Invoice Consignment Details</div>
					<div class="widget-content padded clearfix">
						<div class="table-scroll-wrapper">
							<table class="table table-bordered table-striped" id="lines_table">
								<thead>
									<tr>
										<th>S.No</th><th>GCN No</th><th>Date</th><th>Sender</th><th>Receiver</th>
										<th>Pkgs</th><th>Weight</th><th>Freight</th><th>Other</th><th>GST</th><th>Total</th><th>Billing Type</th><th></th>
									</tr>
								</thead>
								<tbody id="lines_body"></tbody>
							</table>
						</div>
						<div class="summary-panel" id="summary_panel"></div>
						<div class="btn-row">
							<button type="button" class="btn btn-default btn1" id="btn_cancel">Cancel</button>
							<button type="button" class="btn btn-warning btn1" id="btn_draft">Save as Draft</button>
							<button type="button" class="btn btn-primary btn1" id="btn_generate"><i class="fa fa-check"></i> Generate Invoice</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php require_once('include/footer.php'); ?>
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

function refreshGcnSelect(html, hint) {
	var $el = $('#gcn_keys');
	if ($el.data('select2')) {
		$el.off('change');
		$el.select2('destroy');
	}
	$el.html(html || '');
	$el.select2({ width: '100%', placeholder: 'Select GCN(s)' });
	$el.on('change', loadLineDetails);
	if (hint) {
		$('#gcn_result_hint').text(hint);
	}
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
			opts += '<option value="' + escHtml(row.key) + '">' + escHtml(row.label) + '</option>';
		});
		refreshGcnSelect(opts, rows.length + ' delivered GCN(s) found. Select one or more.');
		if (selectKeys && selectKeys.length) {
			$('#gcn_keys').val(selectKeys).trigger('change');
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
		html += '<tr data-key="' + escHtml(r.key) + '">';
		html += '<td>' + (i + 1) + '</td>';
		html += '<td>' + escHtml(r.grn_no) + '</td>';
		html += '<td>' + escHtml(r.grn_date) + '</td>';
		html += '<td>' + escHtml(r.sender) + '</td>';
		html += '<td>' + escHtml(r.receiver) + '</td>';
		html += '<td class="num">' + escHtml(r.packages) + '</td>';
		html += '<td class="num">' + escHtml(r.weight) + '</td>';
		html += '<td class="num">' + escHtml(r.freight_amount) + '</td>';
		html += '<td class="num">' + escHtml(r.other_charges) + '</td>';
		html += '<td class="num">' + escHtml(r.gst_amount) + '</td>';
		html += '<td class="num">' + escHtml(r.total_amount) + '</td>';
		html += '<td>' + escHtml(r.billing_type_label || r.billing_type) + '</td>';
		html += '<td><button type="button" class="btn btn-xs btn-danger btn-remove-line" data-key="' + escHtml(r.key) + '"><i class="fa fa-times"></i></button></td>';
		html += '</tr>';
	});
	$('#lines_body').html(html);

	if (!invoiceLines.length) {
		$('#table_section').hide();
		$('#summary_panel').html('');
		return;
	}
	$('#table_section').show();

	summary = summary || {};
	var shtml = '';
	shtml += '<div class="row-line"><span>Total Freight</span><strong>' + escHtml(summary.total_freight || '0.00') + '</strong></div>';
	shtml += '<div class="row-line"><span>Total Other Charges</span><strong>' + escHtml(summary.total_other || '0.00') + '</strong></div>';
	shtml += '<div class="row-line"><span>Taxable Amount</span><strong>' + escHtml(summary.taxable_value || '0.00') + '</strong></div>';
	shtml += '<div class="row-line"><span>CGST</span><strong>' + escHtml(summary.cgst_amount || '0.00') + '</strong></div>';
	shtml += '<div class="row-line"><span>SGST / UTGST</span><strong>' + escHtml(summary.sgst_amount || '0.00') + '</strong></div>';
	shtml += '<div class="row-line"><span>IGST</span><strong>' + escHtml(summary.igst_amount || '0.00') + '</strong></div>';
	shtml += '<div class="row-line"><span>Cess</span><strong>' + escHtml(summary.cess_amount || '0.00') + '</strong></div>';
	shtml += '<div class="row-line"><span>Total GST</span><strong>' + escHtml(summary.gst_amount || '0.00') + '</strong></div>';
	shtml += '<div class="row-line grand"><span>Grand Total</span><strong>' + escHtml(summary.grand_total || '0.00') + '</strong></div>';
	$('#summary_panel').html(shtml);
}

function loadLineDetails() {
	var keys = $('#gcn_keys').val();
	if (!keys || !keys.length) {
		renderLines([], {});
		return;
	}
	$.getJSON('create_invoice_data.php', {
		cmd: 'fetch_gcn_details',
		keys: keys,
		billing_invoice_id: $('#billing_invoice_id').val()
	}, function(r) {
		if (!r || r.status !== 0) {
			if (typeof ewFormToast === 'function') ewFormToast('Could not load GCN details.', 'error', 5000);
			return;
		}
		renderLines(r.lines, r.summary);
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
	$('#customers').select2({ width: '100%', placeholder: 'Select Customer', allowClear: true });
	refreshGcnSelect('', 'Select a customer to load delivered GCNs.');

	$(document).on('change', '#invoice_date', refreshInvoiceNo);
	$(document).on('change', '#customers', function() {
		loadGcnOptions();
	});

	$(document).on('click', '.btn-remove-line', function() {
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
			$('#customers').val(String(r.master.customer_id)).trigger('change.select2');
			var draftKeys = $.map(r.lines || [], function(l) { return l.key; });
			loadGcnOptions(draftKeys);
		}
	});
	<?php } ?>
});
</script>
</body>
</html>
