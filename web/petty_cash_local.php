<?php
/**
 * Standalone local petty cash workbook (browser storage only — no DB).
 * Not linked in menu; open directly: petty_cash_local.php
 */
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'AD') {
	header('Location:dashboard.php');
	exit;
}

$seed_path = __DIR__ . '/include/petty_cash_local_seed.json';
$seed_json = is_readable($seed_path) ? file_get_contents($seed_path) : '{}';
if ($seed_json === false || $seed_json === '') {
	$seed_json = '{}';
}
?>
<!DOCTYPE html>
<html>
<head>
	<?php include __DIR__ . '/include/title.php'; ?>
	<?php include __DIR__ . '/include/css_js.php'; ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.ew-pc-local .pc-reg-summary {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 10px;
			padding: 0 0 16px;
		}
		.ew-pc-local .pc-reg-summary .kpi {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 10px 12px;
		}
		.ew-pc-local .pc-reg-summary .kpi-label {
			font-size: 10px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin-bottom: 2px;
		}
		.ew-pc-local .pc-reg-summary .kpi-value {
			font-size: 15px;
			font-weight: 800;
			color: #0A1E3D;
			font-variant-numeric: tabular-nums;
		}
		.ew-pc-local .pc-reg-summary .kpi-receipt .kpi-value { color: #15803d; }
		.ew-pc-local .pc-reg-summary .kpi-payment .kpi-value { color: #b91c1c; }
		.ew-pc-local .pc-reg-summary .kpi-close .kpi-value { color: #1d4ed8; }
		@media (max-width: 720px) {
			.ew-pc-local .pc-reg-summary { grid-template-columns: 1fr; }
		}
		@media (max-width: 520px) {
			.ew-pc-local .pc-reg-summary { grid-template-columns: 1fr; }
		}
		.ew-pc-local .report-count {
			font-size: 13px;
			font-weight: 600;
			color: #64748B;
		}
		.ew-pc-local .ew-erp-list > .ew-table-wrap {
			padding-top: 16px !important;
		}
		.ew-pc-local .table-scroll-wrapper {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
		}
		#pc_local_table {
			font-size: 13px;
			margin-bottom: 0;
			width: 100% !important;
			min-width: 1180px;
		}
		#pc_local_table th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
			text-transform: uppercase;
			letter-spacing: .03em;
		}
		#pc_local_table td {
			vertical-align: middle;
			padding: 6px 6px;
			border: none !important;
			border-bottom: 1px solid #e9ecef !important;
			background: #fff;
		}
		#pc_local_table tbody tr:nth-child(even) td { background: #f9fafb; }
		#pc_local_table tbody tr:hover td { background: #f1f5f9; }
		#pc_local_table .num { text-align: right !important; white-space: nowrap; font-variant-numeric: tabular-nums; }
		#pc_local_table .col-sno { text-align: center !important; width: 48px; }
		#pc_local_table .amt-in { color: #15803d; font-weight: 700; }
		#pc_local_table .amt-out { color: #dc2626; font-weight: 700; }
		#pc_local_table .pc-bal-latest { color: #1d4ed8; font-weight: 800; }
		#pc_local_table .pc-local-vno {
			color: #0A1E3D;
			font-weight: 700;
			text-decoration: none !important;
			cursor: default;
		}
		#pc_local_table .badge-receipt {
			background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;
			white-space: nowrap;
		}
		#pc_local_table .badge-payment {
			background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;
			white-space: nowrap;
		}
		#pc_local_table .remarks-cell {
			white-space: pre-wrap;
			min-width: 140px;
			max-width: 260px;
			font-size: 11px;
			color: #475569;
			line-height: 1.35;
		}
		#pc_local_table .vendor-cell {
			min-width: 160px;
			max-width: 220px;
			font-size: 11px;
			line-height: 1.35;
			white-space: normal;
		}
		#pc_local_table .particulars-cell {
			min-width: 140px;
			max-width: 200px;
			white-space: normal;
			line-height: 1.35;
		}
		.ew-pc-local #pc_local_table a {
			pointer-events: none;
			color: inherit;
			text-decoration: none !important;
		}
		.ew-pc-local-add { border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 8px; }
		.ew-pc-local-add h3 { font-size: 15px; margin: 0 0 12px; font-weight: 700; color: #0A1E3D; }
		.ew-pc-local .ew-card-toolbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 8px;
		}
		.ew-pc-local .ew-table-wrap table.dataTable thead th,
		.ew-pc-local .ew-table-wrap table.dataTable tbody td {
			vertical-align: middle !important;
		}
		.ew-pc-local .ew-table-wrap table.dataTable tbody td.num {
			text-align: right !important;
		}
		.ew-pc-local .ew-table-wrap table.dataTable tbody td.col-sno {
			text-align: center !important;
		}
	</style>
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
	<div class="navbar navbar-fixed-top scroll-hide">
		<?php require_once __DIR__ . '/include/header.php'; require_once __DIR__ . '/include/menu.php'; ?>
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="row">
			<div class="col-md-12">
				<div class="ew-page-v2 ew-page-v2--wide-table ew-pc-local">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">Petty Cash (Local)</h1>
						</div>
						<div class="ew-toolbar-right ew-pc-local-toolbar">
							<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="pc_local_export_json" title="Download backup JSON">
								<i class="fa fa-download"></i> Backup
							</button>
							<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="pc_local_reset_seed">
								<i class="fa fa-refresh"></i> Reset to Excel seed
							</button>
						</div>
					</div>

					<div class="pc-reg-summary" id="pc_local_summary"></div>

					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Petty Cash Vouchers</h2>
							<span class="report-count" id="pc_local_count"></span>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
							<div class="table-scroll-wrapper">
								<table class="table table-bordered table-striped" id="pc_local_table">
									<thead>
									<tr>
										<th class="col-sno">S.No</th>
										<th>Voucher No</th>
										<th>Date</th>
										<th>Type</th>
										<th>Particulars</th>
										<th>Vendor</th>
										<th>Paid To</th>
										<th class="num">Amount</th>
										<th class="num">Balance</th>
										<th>Ref No</th>
										<th>Remarks</th>
									</tr>
									</thead>
									<tbody id="pc_local_body"></tbody>
								</table>
							</div>
						</div>
						<div class="ew-table-footer">
							<div class="ew-table-footer-left ew-dt-footer-left"></div>
							<div class="ew-dt-footer-right ew-pagination"></div>
						</div>
					</div>

					<div class="ew-card" style="margin-top:16px;">
						<div class="ew-form-body">
							<div class="ew-pc-local-add">
								<h3>Add entry (cash in / cash out)</h3>
								<form id="pc_local_add_form" class="ew-form-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
									<div class="ew-field">
										<label>Type <span class="req">*</span></label>
										<select name="type" id="pc_add_type" class="form-control">
											<option value="Sent">Cash out (Sent)</option>
											<option value="Received">Cash in (Received)</option>
										</select>
									</div>
									<div class="ew-field">
										<label>Date <span class="req">*</span></label>
										<?php echo ew_date_input(array('id' => 'pc_add_date', 'name' => 'date', 'value' => date('d-m-Y'), 'required' => true)); ?>
									</div>
									<div class="ew-field">
										<label>Amount (₹) <span class="req">*</span></label>
										<input type="number" step="0.01" min="0.01" class="form-control text-right" id="pc_add_amount_abs" required>
									</div>
									<div class="ew-field">
										<label>Paid to</label>
										<input type="text" class="form-control" id="pc_add_paid_to" placeholder="Optional">
									</div>
									<div class="ew-field span-2">
										<label>Particulars <span class="req">*</span></label>
										<input type="text" class="form-control" id="pc_add_particulars" required>
									</div>
									<div class="ew-field span-2">
										<label>Vendor</label>
										<input type="text" class="form-control" id="pc_add_vendor" value="EWV240 — TEMPO CARTAGE INTRA STATE URP ( MOHAMMED IMAZ )">
									</div>
									<div class="ew-field">
										<label>Ref No</label>
										<input type="text" class="form-control" id="pc_add_ref">
									</div>
									<div class="ew-field span-3">
										<label>Remarks</label>
										<input type="text" class="form-control" id="pc_add_remarks">
									</div>
								</form>
								<div style="margin-top:14px;text-align:right;">
									<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="pc_local_add_btn">
										<i class="fa fa-plus"></i> Add voucher
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php require_once __DIR__ . '/include/footer.php'; ?>
</div>
<script id="pc_local_seed" type="application/json"><?php echo str_replace('</', '<\/', $seed_json); ?></script>
<script>
(function ($) {
	var STORAGE_KEY = 'ew_petty_cash_local_v1';
	var seed = {};
	try {
		seed = JSON.parse($('#pc_local_seed').text() || '{}');
	} catch (e) {
		seed = {};
	}

	function cloneSeedState() {
		return JSON.parse(JSON.stringify({
			version: seed.version || 1,
			summary: seed.summary || {},
			next_voucher_seq: seed.next_voucher_seq || 50,
			rows: (seed.rows || []).slice()
		}));
	}

	function loadState() {
		try {
			var raw = localStorage.getItem(STORAGE_KEY);
			if (raw) {
				var st = JSON.parse(raw);
				if (st && Array.isArray(st.rows) && st.rows.length) {
					return st;
				}
			}
		} catch (e) { /* ignore */ }
		return cloneSeedState();
	}

	function saveState(st) {
		localStorage.setItem(STORAGE_KEY, JSON.stringify(st));
	}

	function renumberSno(rows) {
		rows.forEach(function (r, i) {
			r.sno = i + 1;
		});
	}

	/** Remove mistaken local vouchers (e.g. PC00050) and fix next voucher seq. */
	function purgeVoucher(st, voucherNo) {
		var vn = String(voucherNo || '').toUpperCase();
		var before = st.rows.length;
		st.rows = st.rows.filter(function (r) {
			return String(r.voucher_no || '').toUpperCase() !== vn;
		});
		if (st.rows.length === before) {
			return false;
		}
		renumberSno(st.rows);
		var maxSeq = 49;
		st.rows.forEach(function (r) {
			var m = /^PC0*(\d+)$/i.exec(String(r.voucher_no || ''));
			if (m) {
				maxSeq = Math.max(maxSeq, parseInt(m[1], 10));
			}
		});
		st.next_voucher_seq = maxSeq + 1;
		return true;
	}

	var state = loadState();
	if (purgeVoucher(state, 'PC00050')) {
		saveState(state);
	}

	function padVoucher(n) {
		return 'PC' + String(n).padStart(5, '0');
	}

	function signedAmount(row) {
		var a = parseFloat(row.amount);
		if (isNaN(a)) return 0;
		if (row.type === 'Sent' && a > 0) return -a;
		if (row.type === 'Received' && a < 0) return Math.abs(a);
		return a;
	}

	function formatMoney(n) {
		var x = parseFloat(n);
		if (isNaN(x)) return '0';
		return x.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
	}

	function formatMoneyInr(n) {
		return '₹ ' + formatMoney(n);
	}

	function dateSortKey(d) {
		var m = /^(\d{1,2})-(\d{1,2})-(\d{4})$/.exec($.trim(d || ''));
		if (!m) return '0000-00-00';
		var dd = String(m[1]).padStart(2, '0');
		var mm = String(m[2]).padStart(2, '0');
		return m[3] + '-' + mm + '-' + dd;
	}

	var pcLocalDt = null;

	function destroyPcLocalDt() {
		var el = $('#pc_local_table')[0];
		if (!el || !$.fn.dataTable) return;
		if ($.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable(el)) {
			$('#pc_local_table').dataTable().fnDestroy();
		}
		pcLocalDt = null;
	}

	function initPcLocalDt() {
		if (!$.fn.dataTable || !$('#pc_local_body tr').length) return;
		if ($('#pc_local_body tr td[colspan]').length) return;
		if ($.fn.dataTable.fnIsDataTable && $.fn.dataTable.fnIsDataTable($('#pc_local_table')[0])) return;
		pcLocalDt = $('#pc_local_table').dataTable({
			sPaginationType: 'full_numbers',
			iDisplayLength: 10,
			aLengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
			aaSorting: [[2, 'desc']],
			aoColumnDefs: [
				{ bSortable: false, aTargets: [0] },
				{ sType: 'html', aTargets: [2] }
			],
			bFilter: true,
			oLanguage: { sSearch: '' }
		});
		if (typeof applyEwListLayout === 'function') {
			window.setTimeout(applyEwListLayout, 80);
			window.setTimeout(applyEwListLayout, 280);
		}
	}

	function computeSummary(rows) {
		var receipts = 0, payments = 0;
		rows.forEach(function (r) {
			var a = signedAmount(r);
			if (a >= 0) receipts += a;
			else payments += Math.abs(a);
		});
		var balance = Math.round((receipts - payments) * 100) / 100;
		return { receipts: receipts, payments: payments, balance: balance };
	}

	function prependEntry(entry) {
		var amt = signedAmount(entry);
		entry.amount = amt;
		entry.seed = false;
		state.rows.unshift(entry);
		var after = computeSummary(state.rows);
		state.rows[0].balance = after.balance;
		renumberSno(state.rows);
		saveState(state);
		render();
	}

	function renderSummary() {
		var s = computeSummary(state.rows);
		var html = ''
			+ '<div class="kpi kpi-receipt"><div class="kpi-label">Total receipts</div><div class="kpi-value">' + formatMoneyInr(s.receipts) + '</div></div>'
			+ '<div class="kpi kpi-payment"><div class="kpi-label">Total payments</div><div class="kpi-value">' + formatMoneyInr(s.payments) + '</div></div>'
			+ '<div class="kpi kpi-close"><div class="kpi-label">Balance (latest)</div><div class="kpi-value">' + formatMoneyInr(s.balance) + '</div></div>';
		$('#pc_local_summary').html(html);
		$('#pc_local_count').text(state.rows.length + ' voucher(s)');
	}

	function esc(s) {
		return $('<div/>').text(s == null ? '' : String(s)).html();
	}

	function renderTable() {
		var html = '';
		state.rows.forEach(function (r, idx) {
			var amt = parseFloat(r.amount);
			var amtCls = amt >= 0 ? 'amt-in' : 'amt-out';
			var typeBadge = r.type === 'Received'
				? '<span class="badge-receipt">Received</span>'
				: '<span class="badge-payment">Sent</span>';
			var dateKey = dateSortKey(r.date);
			var balDisplay = (idx === 0) ? computeSummary(state.rows).balance : r.balance;
			html += '<tr>'
				+ '<td class="col-sno">' + esc(r.sno) + '</td>'
				+ '<td><span class="pc-local-vno">' + esc(r.voucher_no) + '</span></td>'
				+ '<td data-order="' + esc(dateKey) + '">' + esc(r.date) + '</td>'
				+ '<td>' + typeBadge + '</td>'
				+ '<td class="particulars-cell" title="' + esc(r.particulars) + '">' + esc(r.particulars) + '</td>'
				+ '<td class="vendor-cell" title="' + esc(r.vendor) + '">' + esc(r.vendor) + '</td>'
				+ '<td>' + esc(r.paid_to) + '</td>'
				+ '<td class="num"><span class="' + amtCls + '">' + formatMoneyInr(amt) + '</span></td>'
				+ '<td class="num"><span class="' + (idx === 0 ? 'pc-bal-latest' : '') + '">' + formatMoneyInr(balDisplay) + '</span></td>'
				+ '<td>' + esc(r.ref_no) + '</td>'
				+ '<td class="remarks-cell">' + esc(r.remarks) + '</td>'
				+ '</tr>';
		});
		$('#pc_local_body').html(html || '<tr><td colspan="11" class="text-center text-muted" style="padding:24px;">No data</td></tr>');
	}

	function render() {
		renderSummary();
		destroyPcLocalDt();
		renderTable();
		initPcLocalDt();
	}

	$('#pc_local_add_btn').on('click', function () {
		var type = $('#pc_add_type').val();
		var date = $.trim($('#pc_add_date').val());
		var particulars = $.trim($('#pc_add_particulars').val());
		var absAmt = parseFloat($('#pc_add_amount_abs').val());
		if (!date || !particulars || !absAmt || absAmt <= 0) {
			if (typeof ewFormToast === 'function') {
				ewFormToast('Fill type, date, particulars and amount.', 'error', 4000);
			} else {
				alert('Fill required fields.');
			}
			return;
		}
		var seq = state.next_voucher_seq || 50;
		var entry = {
			voucher_no: padVoucher(seq),
			date: date,
			type: type,
			particulars: particulars,
			vendor: $.trim($('#pc_add_vendor').val()) || '—',
			paid_to: $.trim($('#pc_add_paid_to').val()) || '—',
			amount: type === 'Sent' ? -absAmt : absAmt,
			ref_no: $.trim($('#pc_add_ref').val()) || '—',
			remarks: $.trim($('#pc_add_remarks').val()) || '—'
		};
		state.next_voucher_seq = seq + 1;
		prependEntry(entry);
		$('#pc_add_particulars, #pc_add_amount_abs, #pc_add_paid_to, #pc_add_ref, #pc_add_remarks').val('');
		if (typeof ewFormToast === 'function') {
			ewFormToast('Voucher ' + entry.voucher_no + ' added locally.', 'success', 3000);
		}
	});

	$('#pc_local_reset_seed').on('click', function () {
		if (!window.confirm('Replace all local data with the 49 rows from the Excel seed? New entries you added will be lost.')) {
			return;
		}
		state = cloneSeedState();
		saveState(state);
		render();
	});

	$('#pc_local_export_json').on('click', function () {
		var blob = new Blob([JSON.stringify(state, null, 2)], { type: 'application/json' });
		var a = document.createElement('a');
		a.href = URL.createObjectURL(blob);
		a.download = 'petty_cash_local_' + new Date().toISOString().slice(0, 10) + '.json';
		a.click();
		URL.revokeObjectURL(a.href);
	});

	$(document).ready(function () {
		if (typeof initEwDatepickers === 'function') {
			initEwDatepickers('.ew-pc-local');
		}
		render();
	});
})(jQuery);
</script>
</body>
</html>