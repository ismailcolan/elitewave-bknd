<?php
require_once 'include/connect.php';
require_once 'include/function.php';
require_once 'include/gstr1_ui.php';

$base = gstr1_base_query_from_request();
$defaults = gstr1_period_defaults();
$gstin_opts = gstr1_company_gstin_options($conn);
if ($base['gstin'] === '' && !empty($gstin_opts[0]['gstin'])) {
	$base['gstin'] = $gstin_opts[0]['gstin'];
}

$crumbs = array(
	array('label' => 'GST', 'href' => 'gstr1.php?' . http_build_query(array('gstin' => $base['gstin'], 'from_date' => $base['from_date'], 'to_date' => $base['to_date']))),
	array('label' => 'GSTR-1', 'href' => ''),
);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include 'include/title.php'; ?>
	<?php include 'include/css_js.php'; ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<?php gstr1_shared_styles(); ?>
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
	<div class="navbar navbar-fixed-top scroll-hide">
		<?php require_once 'include/header.php'; require_once 'include/menu.php'; ?>
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="row">
			<div class="col-md-12">
				<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--mis-report ew-page-v2--gstr1" id="gstr1_root">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">GSTR-1</h1>
						</div>
						<div class="ew-page-head-right">
							<span class="report-count" id="gstr1_report_count"></span>
							<a href="gstr1_statistics.php?<?php echo htmlspecialchars(http_build_query($base), ENT_QUOTES, 'UTF-8'); ?>" class="ew-btn-v2 ew-btn-v2-outline">Statistics</a>
						</div>
					</div>

					<?php gstr1_render_breadcrumb($crumbs); ?>

					<?php gstr1_render_filter_card($base, $gstin_opts, 'Load return', 'gstr1_load'); ?>

					<div class="ew-mis-active-bar" id="gstr1_active_bar" style="display:none;">
						<div class="ew-mis-active-left">
							<span class="ew-mis-active-label"><i class="fa fa-filter"></i> Period</span>
							<span class="ew-mis-date-pill" id="gstr1_date_pill"></span>
							<span class="ew-mis-active-summary" id="gstr1_active_gstin"></span>
						</div>
						<div class="ew-mis-active-actions">
							<div class="ew-mis-table-search">
								<i class="fa fa-search"></i>
								<input type="search" id="gstr1_table_search" placeholder="Search return sections…" autocomplete="off">
							</div>
						</div>
					</div>

					<div id="gstr1_header_host"></div>
					<div id="gstr1_counts_host"></div>
					<div id="gstr1_return_host"></div>
				</div>
			</div>
		</div>
	</div>
	<?php require_once 'include/footer.php'; ?>
</div>
<script src="javascripts/ew-mis-report-views.js"></script>
<?php gstr1_report_script_tag(); ?>
<script>
$(function () {
	var $root = $('#gstr1_root');
	var defaultFrom = <?php echo json_encode($defaults['from']); ?>;
	var defaultTo = <?php echo json_encode($defaults['to']); ?>;

	function hideFullPageLoader() {
		$('.loading-page, .form-data-saving').hide();
	}
	hideFullPageLoader();

	function datePill() {
		var from = $('#gstr1_from').val() || '';
		var to = $('#gstr1_to').val() || '';
		return from && to ? (from + ' to ' + to) : '';
	}

	$root.on('click', '.ew-mis-quick-range [data-range]', function () {
		$root.find('.ew-mis-quick-range [data-range]').removeClass('is-active');
		$(this).addClass('is-active');
		if (window.EwMisReportView && EwMisReportView.applyQuickRange) {
			EwMisReportView.applyQuickRange($(this).data('range'), '#gstr1_from', '#gstr1_to');
		}
	});

	$('#gstr1_reset_filters').on('click', function () {
		$('#gstr1_from').val(defaultFrom).trigger('change');
		$('#gstr1_to').val(defaultTo).trigger('change');
		$root.find('.ew-mis-quick-range [data-range]').removeClass('is-active');
		$root.find('.ew-mis-quick-range [data-range="month"]').addClass('is-active');
	});

	function layoutRefresh() {
		if (window.applyEwListLayout) {
			window.applyEwListLayout();
		}
	}

	function renderHeader(data) {
		var f = data.filing || {};
		var ctx = data.context || {};
		var chip = f.status === 'filed' ? 'is-filed' : 'is-not-filed';
		var html = '<div class="ew-card"><div class="ew-card-toolbar"><h2>Filer &amp; filing status</h2></div><div class="ew-form-body"><div class="gstr1-header-grid">' +
			'<div><div class="filter-label">GSTIN</div><strong>' + (ctx.gstin || '') + '</strong><div class="text-muted" style="font-size:12px;">' + (ctx.legal_name || '') + '</div></div>' +
			'<div><div class="filter-label">Period</div><strong>' + (data.period && data.period.period_label ? data.period.period_label : '') + '</strong></div>' +
			'<div><div class="filter-label">Filing status</div><span class="gstr1-status-chip ' + chip + '">' + (f.status_label || 'Not filed') + '</span></div>' +
			'<div><div class="filter-label">ARN</div>' + (f.arn || '—') + (f.arn_date ? ' · ' + f.arn_date : '') + '</div>' +
			'</div></div></div>';
		$('#gstr1_header_host').html(html);
	}

	function renderReturn(data) {
		var rv = data.return_view || {};
		var rows = rv.rows || [];
		var html = '<div class="ew-card ew-erp-list"><div class="ew-card-toolbar"><h2>Return view</h2></div>' +
			'<div class="ew-table-wrap widget-content padded clearfix">' +
			'<div class="table-scroll-wrapper"><table class="table table-striped" id="gstr1_return_table"><thead><tr>' +
			'<th>Particulars</th><th class="num">Vouchers</th><th class="num">Taxable</th><th class="num">IGST</th><th class="num">CGST</th><th class="num">SGST/UTGST</th><th class="num">Cess</th><th class="num">Tax</th><th class="num">Invoice amt</th></tr></thead><tbody>';
		rows.forEach(function (r) {
			var cntCell = r.drillable && parseInt(r.voucher_count, 10) > 0
				? '<button type="button" class="gstr1-drill-btn num-only" data-section="' + r.id + '" aria-label="' + r.particulars + ' vouchers">' + r.voucher_count + '</button>'
				: (r.voucher_count || '');
			html += '<tr><td>' + r.particulars + '</td><td class="num">' + cntCell + '</td>';
			['taxable_fmt', 'igst_fmt', 'cgst_fmt', 'sgst_fmt', 'cess_fmt', 'tax_fmt', 'invoice_amount_fmt'].forEach(function (k) {
				html += '<td class="num">' + (r[k] || '') + '</td>';
			});
			html += '</tr>';
		});
		var t = rv.total || {};
		html += '</tbody><tfoot><tr class="gstr1-total-row"><td><strong>Total (sections 1–11)</strong></td><td class="num"><strong>' + (t.voucher_count || '') + '</strong></td>';
		['taxable_fmt', 'igst_fmt', 'cgst_fmt', 'sgst_fmt', 'cess_fmt', 'tax_fmt', 'invoice_amount_fmt'].forEach(function (k) {
			var v = t[k] || '';
			html += '<td class="num">' + (v ? '<strong>' + v + '</strong>' : '') + '</td>';
		});
		html += '</tr></tfoot></table></div></div></div>';
		$('#gstr1_return_host').html(html);
		Gstr1Report.wireDataTable($('#gstr1_return_table'), '#gstr1_table_search');
		layoutRefresh();
	}

	function load() {
		var p = Gstr1Report.periodParams($root);
		if (!p.from_date || !p.to_date) {
			if (typeof ewFormToast === 'function') {
				ewFormToast('Please select From Date and To Date.', 'error', 5000);
			}
			return;
		}
		Gstr1Report.showSkeleton($('#gstr1_header_host'));
		Gstr1Report.showSkeleton($('#gstr1_counts_host'));
		Gstr1Report.showSkeleton($('#gstr1_return_host'));
		$('#gstr1_active_bar').show();
		$('#gstr1_date_pill').text(datePill());
		$('#gstr1_active_gstin').text(p.gstin || '');

		Gstr1Report.fetch('summary', p, function (data) {
			hideFullPageLoader();
			if (!data.ok) {
				Gstr1Report.showError($('#gstr1_counts_host'), data.error);
				Gstr1Report.showError($('#gstr1_return_host'), data.error);
				return;
			}
			var c = data.counts || {};
			$('#gstr1_report_count').text((c.total || 0) + ' vouchers');
			try {
				renderHeader(data);
				if (typeof Gstr1Report.renderVoucherSummary !== 'function') {
					throw new Error('GSTR-1 script is outdated. Hard refresh this page (Ctrl+F5).');
				}
				Gstr1Report.renderVoucherSummary($('#gstr1_counts_host'), data.counts, data.not_relevant);
				renderReturn(data);
			} catch (err) {
				var msg = (err && err.message) ? err.message : 'Unable to display GSTR-1.';
				if (!$('#gstr1_counts_host .ew-card').length) {
					Gstr1Report.showError($('#gstr1_counts_host'), msg);
				}
				Gstr1Report.showError($('#gstr1_return_host'), msg);
			}
		}, function () {
			hideFullPageLoader();
			Gstr1Report.showError($('#gstr1_return_host'), 'Network error loading GSTR-1.');
		});
	}

	$('#gstr1_load').on('click', load);
	$(document).on('gstr1:reload', load);

	Gstr1Report.bindDrillClicks($root, { source: 'gstr1' });

	$(window).on('load', function () {
		hideFullPageLoader();
		window.setTimeout(load, 50);
	});
});
</script>
</body>
</html>