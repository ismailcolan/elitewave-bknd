/* global jQuery, ewV2OpenModal, ewFormToast */
(function($) {
	'use strict';

	var PAGE_SIZE = 10;

	var state = {
		period: null,
		customers: [],
		expandCustomers: {},
		expandInvoices: {},
		paymentFilter: 'all',
		search: '',
		loading: false,
		page: 1,
	};

	function fmtMoney(n) {
		n = parseFloat(n) || 0;
		if (Math.abs(n) < 0.005) {
			return '';
		}
		return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function chip(status) {
		var cls = 'bw-chip';
		if (status === 'Completed' || status === 'Settled') {
			cls += ' is-ok';
		} else if (status === 'Part paid') {
			cls += ' is-mid';
		} else {
			cls += ' is-pending';
		}
		return '<span class="' + cls + '">' + esc(status) + '</span>';
	}

	function filteredCustomers() {
		var q = $.trim(state.search).toLowerCase();
		return state.customers.filter(function(c) {
			if (q && String(c.name || '').toLowerCase().indexOf(q) === -1) {
				return false;
			}
			if (state.paymentFilter === 'pending') {
				return c.payment_status === 'Pending';
			}
			if (state.paymentFilter === 'completed') {
				return c.payment_status === 'Completed';
			}
			return true;
		});
	}

	function countByStatus() {
		var all = state.customers.length;
		var pending = 0;
		var completed = 0;
		state.customers.forEach(function(c) {
			if (c.payment_status === 'Pending') {
				pending++;
			} else {
				completed++;
			}
		});
		return { all: all, pending: pending, completed: completed };
	}

	function updateSegmentCounts() {
		var c = countByStatus();
		$('#bw_seg_all .bw-count').text(c.all);
		$('#bw_seg_pending .bw-count').text(c.pending);
		$('#bw_seg_completed .bw-count').text(c.completed);
	}

	function hideListFooter() {
		$('#bw_list_footer').hide();
		$('#bw_pager_info').text('');
		$('#bw_pagination').empty();
	}

	function renderPagination(total) {
		if (!total) {
			hideListFooter();
			return;
		}
		var totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
		if (state.page > totalPages) {
			state.page = totalPages;
		}
		if (state.page < 1) {
			state.page = 1;
		}
		var start = (state.page - 1) * PAGE_SIZE + 1;
		var end = Math.min(state.page * PAGE_SIZE, total);
		$('#bw_pager_info').text('Showing ' + start + ' to ' + end + ' of ' + total + ' customers');
		$('#bw_list_footer').css('display', 'flex');

		var $p = $('#bw_pagination');
		if (total <= PAGE_SIZE) {
			$p.empty();
			return;
		}

		var html = '';
		html += '<button type="button" class="paginate_button bw-pager-btn" data-page="prev"' + (state.page <= 1 ? ' disabled' : '') + '>Prev</button>';

		var leftGap = false;
		var rightGap = false;
		for (var p = 1; p <= totalPages; p++) {
			var near = Math.abs(p - state.page) <= 1;
			var edge = p === 1 || p === totalPages;
			if (totalPages <= 7 || edge || near) {
				var cls = 'paginate_button bw-pager-btn' + (p === state.page ? ' paginate_active' : '');
				html += '<button type="button" class="' + cls + '" data-page="' + p + '">' + p + '</button>';
				continue;
			}
			if (p < state.page && !leftGap) {
				html += '<span class="paginate_button paginate_ellipsis">…</span>';
				leftGap = true;
			} else if (p > state.page && !rightGap) {
				html += '<span class="paginate_button paginate_ellipsis">…</span>';
				rightGap = true;
			}
		}

		html += '<button type="button" class="paginate_button bw-pager-btn" data-page="next"' + (state.page >= totalPages ? ' disabled' : '') + '>Next</button>';
		$p.html(html);
	}

	function activePeriodLabel() {
		if (state.period && state.period.from && state.period.to) {
			return esc(state.period.from) + ' — ' + esc(state.period.to);
		}
		var from = $.trim($('#from_date').val() || '');
		var to = $.trim($('#to_date').val() || '');
		if (from && to) {
			return esc(from) + ' — ' + esc(to);
		}
		return '—';
	}

	function render() {
		updateSegmentCounts();
		var list = filteredCustomers();
		var rangeHeader = activePeriodLabel();

		if (state.loading) {
			$('#bw_report_body').html('<p class="bw-skeleton">Loading…</p>');
			hideListFooter();
			$('#bw_footer_totals').hide();
			return;
		}

		if (!state.customers.length) {
			$('#bw_report_body').html('<p class="bw-empty">No invoices in this date range. Change dates and click Search.</p>');
			hideListFooter();
			$('#bw_footer_totals').hide();
			return;
		}

		if (!list.length) {
			$('#bw_report_body').html('<p class="bw-empty">No customers match these filters. Clear the search or choose All.</p>');
			hideListFooter();
			$('#bw_footer_totals').hide();
			return;
		}

		renderPagination(list.length);
		var pageOffset = (state.page - 1) * PAGE_SIZE;
		var pageList = list.slice(pageOffset, pageOffset + PAGE_SIZE);

		var html = '<div class="bw-table-scroll"><table class="table table-bordered bw-table-l1" role="grid">';
		html += '<thead><tr class="bw-head-month">';
		html += '<th colspan="4"></th>';
		html += '<th colspan="2" class="text-center bw-period-range">' + rangeHeader + '</th>';
		html += '<th colspan="1"></th></tr>';
		html += '<tr>';
		html += '<th class="bw-col-toggle"></th><th>S No</th><th>Customer name</th>';
		html += '<th class="num">Closing balance</th><th class="num">Billing</th>';
		html += '<th class="num">Collection amt</th><th class="bw-col-status">Payment status</th>';
		html += '</tr></thead><tbody>';

		var tClose = 0;
		var tBill = 0;
		var tColl = 0;
		list.forEach(function(c) {
			var cb = c.closing_balance || {};
			var dr = cb.dr_cr === 'Cr';
			tClose += dr ? -cb.amount : cb.amount;
			tBill += parseFloat(c.billing) || 0;
			tColl += parseFloat(c.collection) || 0;
		});

		pageList.forEach(function(c, idx) {
			var cb = c.closing_balance || {};
			var dr = cb.dr_cr === 'Cr';
			var open = !!state.expandCustomers[c.id];
			html += '<tr class="bw-cust-row' + (open ? ' is-open' : '') + '" data-cid="' + c.id + '">';
			html += '<td><button type="button" class="bw-toggle" data-level="customer" data-id="' + c.id + '" aria-expanded="' + (open ? 'true' : 'false') + '" aria-label="Show invoices for ' + esc(c.name) + '"><i class="fa fa-chevron-right" aria-hidden="true"></i></button></td>';
			html += '<td>' + (pageOffset + idx + 1) + '</td>';
			html += '<td>' + esc(c.name) + '</td>';
			html += '<td class="num">' + (cb.amount > 0.009 ? fmtMoney(cb.amount) + ' <span class="bw-drcr' + (dr ? ' is-cr' : '') + '">' + esc(cb.dr_cr) + '</span>' : '') + '</td>';
			html += '<td class="num">' + fmtMoney(c.billing) + '</td>';
			html += '<td class="num">' + fmtMoney(c.collection) + '</td>';
			html += '<td class="bw-col-status">' + chip(c.payment_status) + '</td>';
			html += '</tr>';

			if (open) {
				html += '<tr class="bw-detail-row"><td colspan="7">';
				html += renderInvoices(c);
				html += '</td></tr>';
			}
		});

		html += '</tbody></table></div>';
		$('#bw_report_body').html(html);

		var fcb = bill_wise_dr_cr_footer(tClose);
		$('#bw_footer_totals').show();
		$('#bw_ft_close').html(fmtMoney(fcb.amount) + (fcb.amount > 0.009 ? ' <span class="bw-drcr">' + fcb.dr_cr + '</span>' : ''));
		$('#bw_ft_bill').text(fmtMoney(tBill));
		$('#bw_ft_coll').text(fmtMoney(tColl));
	}

	function bill_wise_dr_cr_footer(net) {
		if (net > 0.009) {
			return { amount: net, dr_cr: 'Dr' };
		}
		if (net < -0.009) {
			return { amount: Math.abs(net), dr_cr: 'Cr' };
		}
		return { amount: 0, dr_cr: 'Dr' };
	}

	function renderInvoices(c) {
		var html = '<div class="bw-panel"><div class="bw-panel-head">';
		html += '<h4 class="bw-panel-title">Ledger: ' + esc(c.name) + '</h4>';
		html += '<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-bw-ledger-pdf" data-cid="' + c.id + '">';
		html += '<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Ledger PDF</button></div>';
		html += '<div class="bw-table-scroll"><table class="table table-bordered bw-table-l2"><thead><tr>';
		html += '<th class="bw-col-toggle"></th><th>Date</th><th>Particulars</th><th>Vch No.</th>';
		html += '<th class="num">GCNs</th><th class="num">Invoice amount</th><th class="num">Received</th>';
		html += '<th class="num">Balance</th><th>Payment status</th></tr></thead><tbody>';

		(c.invoices || []).forEach(function(inv) {
			var key = c.id + ':' + inv.id;
			var open = !!state.expandInvoices[key];
			html += '<tr class="' + (open ? 'is-open' : '') + '">';
			html += '<td><button type="button" class="bw-toggle" data-level="invoice" data-cid="' + c.id + '" data-id="' + inv.id + '" aria-expanded="' + (open ? 'true' : 'false') + '" aria-label="Show GCN lines for ' + esc(inv.voucher_no) + '"><i class="fa fa-chevron-right"></i></button></td>';
			html += '<td>' + esc(inv.date_short || inv.date) + '</td>';
			html += '<td><strong>' + esc(inv.ledger_name) + '</strong><div class="bw-muted">' + esc(inv.voucher_type_label) + '</div></td>';
			html += '<td>' + esc(inv.voucher_no) + '</td>';
			html += '<td class="num">' + (inv.gcn_count || 0) + '</td>';
			html += '<td class="num">' + fmtMoney(inv.total) + '</td>';
			html += '<td class="num">' + fmtMoney(inv.received) + '</td>';
			html += '<td class="num">' + fmtMoney(inv.balance) + '</td>';
			html += '<td>' + chip(inv.payment_status) + '</td></tr>';

			if (open) {
				html += '<tr><td colspan="9">' + renderGcns(inv) + '</td></tr>';
			}
		});

		html += '</tbody></table></div>';
		html += '<div class="bw-ledger-strip">';
		html += '<span><b>Opening balance</b> ' + fmtMoney(c.opening_balance) + '</span>';
		html += '<span><b>Billed</b> ' + fmtMoney(c.billing) + '</span>';
		html += '<span><b>Collected</b> ' + fmtMoney(c.collection) + '</span>';
		var cl = c.closing_balance || {};
		html += '<span><b>Closing balance</b> ' + fmtMoney(cl.amount) + (cl.amount > 0.009 ? ' ' + esc(cl.dr_cr) : '') + '</span>';
		html += '</div></div>';
		return html;
	}

	function renderGcns(inv) {
		var gcns = inv.gcns || [];
		var html = '<div class="bw-table-scroll"><table class="table table-bordered bw-table-l3"><thead><tr>';
		html += '<th>S.No</th><th>T/A</th><th>GCN No.</th><th>GCN Date</th><th>Package</th><th>Consignor</th><th>Station</th>';
		html += '<th>Ship To</th><th>Inv No</th><th class="num">Freight</th><th class="num">D.Tempo</th><th class="num">DC</th>';
		html += '<th>Weight</th><th class="num">Rate</th><th>per</th><th class="num">Amount</th></tr></thead><tbody>';

		var sub = 0;
		var wsum = 0;
		gcns.forEach(function(g, i) {
			sub += parseFloat(g.amount) || 0;
			wsum += parseFloat(g.weight) || 0;
			html += '<tr>';
			html += '<td>' + (i + 1) + '</td><td>' + esc(g.type) + '</td><td>' + esc(g.gr_no) + '</td>';
			html += '<td>' + esc(g.gr_date_short || g.gr_date) + '</td><td>' + esc(g.qty) + '</td>';
			html += '<td>' + esc(g.consignor) + '</td><td>' + esc(g.station) + '</td><td>' + esc(g.ship_to) + '</td><td>' + esc(g.inv_no) + '</td>';
			html += '<td class="num">' + fmtMoney(g.freight) + '</td><td class="num">' + fmtMoney(g.detention_tempo) + '</td><td class="num">' + fmtMoney(g.dc) + '</td>';
			html += '<td>' + esc(g.weight_label || g.weight) + '</td><td class="num">' + fmtMoney(g.rate) + '</td><td>' + esc(g.per) + '</td>';
			html += '<td class="num">' + fmtMoney(g.amount) + '</td></tr>';
		});

		var cgstPct = inv.taxable > 0 ? Math.round((inv.cgst / inv.taxable) * 100) : 9;
		var sgstPct = inv.taxable > 0 ? Math.round((inv.sgst / inv.taxable) * 100) : 9;
		var round = parseFloat(inv.round_off) || 0;

		html += '<tr><td colspan="15" class="text-right"><strong>Sub total</strong></td><td class="num"><strong>' + fmtMoney(sub) + '</strong></td></tr>';
		if (inv.cgst > 0.009) {
			html += '<tr><td colspan="15" class="text-right">Output CGST @ ' + cgstPct + '%</td><td class="num">' + fmtMoney(inv.cgst) + '</td></tr>';
		}
		if (inv.sgst > 0.009) {
			html += '<tr><td colspan="15" class="text-right">Output SGST @ ' + sgstPct + '%</td><td class="num">' + fmtMoney(inv.sgst) + '</td></tr>';
		}
		if (Math.abs(round) > 0.009) {
			var ro = round < 0 ? '(−) ' + fmtMoney(Math.abs(round)) : fmtMoney(round);
			html += '<tr><td colspan="15" class="text-right">Round off</td><td class="num">' + ro + '</td></tr>';
		}
		html += '<tr class="bw-gcn-total"><td colspan="12"><strong>' + gcns.length + ' GCN(s), ' + wsum.toFixed(2) + ' Kgs</strong></td>';
		html += '<td colspan="3" class="text-right"><strong>Total</strong></td>';
		html += '<td class="num"><strong>' + fmtMoney(inv.total) + '</strong></td></tr>';
		html += '</tbody></table></div>';
		return html;
	}

	function resolveLedgerCustomerId(explicitId) {
		if (explicitId) {
			return parseInt(explicitId, 10) || 0;
		}
		var list = filteredCustomers();
		if (list.length === 1) {
			return list[0].id;
		}
		var q = $.trim(state.search).toLowerCase();
		if (q) {
			var matches = list.filter(function(c) {
				return String(c.name || '').toLowerCase().indexOf(q) !== -1;
			});
			if (matches.length === 1) {
				return matches[0].id;
			}
		}
		return 0;
	}

	function openLedgerPdf(customerId) {
		var from = $('#from_date').val();
		var to = $('#to_date').val();
		if (!from || !to) {
			toast('Please select From Date and To Date.');
			return;
		}
		var cid = resolveLedgerCustomerId(customerId);
		if (!cid) {
			toast('Filter to one customer (search until one row shows), or expand a customer and use Ledger PDF there.');
			return;
		}
		var narration = String($('#bw_ledger_narration').val() || '1');
		var url = 'pending_payments_ledger_pdf.php'
			+ '?customer_id=' + encodeURIComponent(cid)
			+ '&from_date=' + encodeURIComponent(from)
			+ '&to_date=' + encodeURIComponent(to)
			+ '&narration=' + narration
			+ '&download=1';
		window.open(url, '_blank');
	}

	function getSelectedCustomerIds() {
		var vals = $('#customers').val();
		if (!vals) {
			return [];
		}
		return $.isArray(vals) ? vals : String(vals).split(',');
	}

	function customerFilterSummary() {
		var ids = getSelectedCustomerIds();
		if (!ids.length) {
			return 'All customers';
		}
		if (ids.length === 1) {
			var $opt = $('#customers option[value="' + ids[0] + '"]');
			return $opt.length ? $opt.text() : '1 customer';
		}
		return ids.length + ' customers';
	}

	function loadData(opts) {
		opts = opts || {};
		var from = $('#from_date').val();
		var to = $('#to_date').val();
		if (!from || !to) {
			toast('Please select From Date and To Date.');
			return;
		}
		state.loading = true;
		if (opts.showResults && window.billWiseMisView) {
			window.billWiseMisView.showResults({
				datePill: window.billWiseMisView.datePill(),
				summary: customerFilterSummary(),
				countText: '',
			});
		}
		render();
		$.ajax({
			url: 'bill_wise_data.php',
			type: 'GET',
			dataType: 'json',
			data: {
				from_date: from,
				to_date: to,
				customers: getSelectedCustomerIds().join(','),
			},
			cache: false,
		}).done(function(resp) {
			state.loading = false;
			if (!resp || resp.status !== 0) {
				$('#bw_report_body').html('<p class="bw-error">' + esc(resp && resp.message ? resp.message : 'Error loading data.') + ' <button type="button" class="btn btn-link" id="bw_retry">Retry</button></p>');
				return;
			}
			state.period = resp.period || null;
			state.customers = resp.customers || [];
			state.page = 1;
			var n = state.customers.length;
			$('#report_count').text(n + ' customer' + (n === 1 ? '' : 's'));
			render();
		}).fail(function(xhr) {
			state.loading = false;
			var hint = '';
			if (xhr && xhr.responseText && xhr.responseText.indexOf('<') === 0) {
				hint = ' Session may have expired — refresh the page and log in again.';
			}
			$('#bw_report_body').html('<p class="bw-error">Could not load report.' + esc(hint) + ' <button type="button" class="btn btn-link" id="bw_retry">Retry</button></p>');
		});
	}

	function toast(msg, type) {
		if (typeof ewFormToast === 'function') {
			ewFormToast(msg, type || 'error', 5000);
		} else {
			alert(msg);
		}
	}

	$(function() {
		if ($.fn.select2 && $('#customers').length) {
			$('#customers').select2({
				width: '100%',
				placeholder: 'All customers',
				allowClear: true,
				closeOnSelect: false,
			});
		}

		if (window.EwMisReportView) {
			window.billWiseMisView = window.EwMisReportView.wire({
				root: '.ew-page-v2--bill-wise',
				fromSel: '#from_date',
				toSel: '#to_date',
				onReset: function() {
					if (window.EwMisReportView.applyQuickRange) {
						window.EwMisReportView.applyQuickRange('month', '#from_date', '#to_date');
					}
					$('.ew-mis-quick-range [data-range]').removeClass('is-active');
					$('.ew-mis-quick-range [data-range="month"]').addClass('is-active');
					if ($('#customers').data('select2')) {
						$('#customers').select2('val', '');
					} else {
						$('#customers').val(null);
					}
					state.customers = [];
					state.period = null;
					state.search = '';
					state.page = 1;
					$('#bw_customer_search').val('');
				},
			});
		}

		$(document).on('click', '#search', function() {
			loadData({ showResults: true });
		});
		$(document).on('click', '#bw_retry', function() {
			loadData({ showResults: true });
		});

		$('#bw_customer_search').on('input', function() {
			state.search = $(this).val();
			state.page = 1;
			render();
		});

		$('.bw-seg-btn').on('click', function() {
			var f = $(this).data('filter');
			state.paymentFilter = f || 'all';
			$('.bw-seg-btn').attr('aria-pressed', 'false');
			$(this).attr('aria-pressed', 'true');
			state.page = 1;
			render();
		});

		$(document).on('click', '#bw_pagination .bw-pager-btn', function() {
			if ($(this).prop('disabled')) {
				return;
			}
			var listLen = filteredCustomers().length;
			var totalPages = Math.max(1, Math.ceil(listLen / PAGE_SIZE));
			var page = $(this).data('page');
			if (page === 'prev') {
				state.page = Math.max(1, state.page - 1);
			} else if (page === 'next') {
				state.page = Math.min(totalPages, state.page + 1);
			} else {
				state.page = parseInt(page, 10) || 1;
			}
			render();
		});

		$(document).on('click', '.bw-toggle', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var level = $btn.data('level');
			if (level === 'customer') {
				var id = parseInt($btn.data('id'), 10);
				state.expandCustomers[id] = !state.expandCustomers[id];
			} else if (level === 'invoice') {
				var cid = parseInt($btn.data('cid'), 10);
				var iid = parseInt($btn.data('id'), 10);
				var key = cid + ':' + iid;
				state.expandInvoices[key] = !state.expandInvoices[key];
			}
			render();
			setTimeout(function() {
				$btn.trigger('focus');
			}, 0);
		});

		$('#bw_expand_all').on('click', function() {
			filteredCustomers().forEach(function(c) {
				state.expandCustomers[c.id] = true;
			});
			render();
		});

		$('#bw_collapse_all').on('click', function() {
			state.expandCustomers = {};
			state.expandInvoices = {};
			render();
		});

		$('#btn_ledger_pdf_results').on('click', function() {
			var ids = getSelectedCustomerIds();
			if (ids.length === 1) {
				openLedgerPdf(ids[0]);
				return;
			}
			openLedgerPdf(0);
		});

		$(document).on('click', '.btn-bw-ledger-pdf', function(e) {
			e.preventDefault();
			openLedgerPdf($(this).data('cid'));
		});
	});

	window.billWiseLoad = loadData;
})(window.jQuery);