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

$source = isset($_GET['source']) ? (string) $_GET['source'] : 'gstr1';
$crumbs = array(
	array('label' => 'GST', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'GSTR-1', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'Statistics', 'href' => ''),
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
				<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--mis-report ew-page-v2--gstr1" id="gstr1_stats_root">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">GSTR-1 Statistics</h1>
						</div>
						<div class="ew-page-head-right">
							<span class="report-count" id="gstr1_stats_count"></span>
							<a class="ew-btn-v2 ew-btn-v2-outline" href="<?php echo htmlspecialchars(gstr1_url('gstr1.php', $base), ENT_QUOTES, 'UTF-8'); ?>">Back to GSTR-1</a>
						</div>
					</div>

					<?php gstr1_render_breadcrumb($crumbs); ?>

					<?php gstr1_render_filter_card($base, $gstin_opts, 'Search', 'gstr1_stats_search'); ?>

					<div class="ew-mis-active-bar" id="gstr1_stats_active_bar" style="display:none;">
						<div class="ew-mis-active-left">
							<span class="ew-mis-active-label"><i class="fa fa-filter"></i> Period</span>
							<span class="ew-mis-date-pill" id="gstr1_stats_date_pill"></span>
							<span class="ew-mis-active-summary" id="gstr1_stats_active_gstin"></span>
						</div>
						<div class="ew-mis-active-actions">
							<div class="ew-mis-table-search">
								<i class="fa fa-search"></i>
								<input type="search" id="gstr1_stats_table_search" placeholder="Search voucher type…" autocomplete="off">
							</div>
						</div>
					</div>

					<div id="gstr1_stats_counts_host" style="margin-bottom:16px;"></div>
					<div id="gstr1_stats_host"></div>
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
	var $root = $('#gstr1_stats_root');
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

	function load() {
		var p = Gstr1Report.periodParams($root);
		if (!p.from_date || !p.to_date) {
			if (typeof ewFormToast === 'function') {
				ewFormToast('Please select From Date and To Date.', 'error', 5000);
			}
			return;
		}
		Gstr1Report.showSkeleton($('#gstr1_stats_counts_host'));
		Gstr1Report.showSkeleton($('#gstr1_stats_host'));
		$('#gstr1_stats_active_bar').show();
		$('#gstr1_stats_date_pill').text(datePill());
		$('#gstr1_stats_active_gstin').text(p.gstin || '');

		Gstr1Report.fetch('statistics', p, function (data) {
			hideFullPageLoader();
			if (!data.ok) {
				Gstr1Report.showError($('#gstr1_stats_host'), data.error);
				return;
			}
			var rows = data.statistics || [];
			var tot = { total: 0, included_action_pending: 0, included_no_action: 0, not_relevant: 0, uncertain: 0 };
			rows.forEach(function (r) {
				['total', 'included_action_pending', 'included_no_action', 'not_relevant', 'uncertain'].forEach(function (k) {
					tot[k] += parseInt(r[k], 10) || 0;
				});
			});
			$('#gstr1_stats_count').text(tot.total + ' vouchers');
			Gstr1Report.renderVoucherSummary($('#gstr1_stats_counts_host'), data.counts, data.not_relevant);

			var html = '<div class="ew-card ew-erp-list"><div class="ew-card-toolbar"><h2>Voucher type summary</h2></div>' +
				'<div class="ew-table-wrap widget-content padded clearfix">' +
				'<div class="table-scroll-wrapper"><table class="table table-striped" id="gstr1_stats_table"><thead><tr>' +
				'<th>Voucher type</th><th class="num">Total</th><th class="num">Included (action pending)</th><th class="num">Included (no action)</th><th class="num">Not relevant</th><th class="num">Uncertain</th></tr></thead><tbody>';

			rows.forEach(function (r) {
				function cell(k, bucket, sub) {
					var n = parseInt(r[k], 10) || 0;
					if (!n) return '<td class="num"></td>';
					return '<td class="num"><button type="button" class="gstr1-drill-btn num-only" data-type="' + r.voucher_type + '" data-bucket="' + bucket + '" data-sub="' + (sub || '') + '" aria-label="' + r.label + ' ' + k + '">' + n + '</button></td>';
				}
				html += '<tr><td><span class="gstr1-voucher-type-tag">' + r.label + '</span></td>' + cell('total', '', '') + cell('included_action_pending', 'included', 'action_pending') +
					cell('included_no_action', 'included', 'no_action') + cell('not_relevant', 'not_relevant', '') + cell('uncertain', 'uncertain', '') + '</tr>';
			});
			html += '</tbody><tfoot><tr class="gstr1-total-row"><td><strong>Total</strong></td>';
			['total', 'included_action_pending', 'included_no_action', 'not_relevant', 'uncertain'].forEach(function (k) {
				var v = tot[k] || '';
				html += '<td class="num">' + (v !== '' ? '<strong>' + v + '</strong>' : '') + '</td>';
			});
			html += '</tr></tfoot></table></div></div></div>';
			$('#gstr1_stats_host').html(html);
			Gstr1Report.wireDataTable($('#gstr1_stats_table'), '#gstr1_stats_table_search');
			if (window.applyEwListLayout) {
				window.applyEwListLayout();
			}
		}, function () {
			hideFullPageLoader();
			Gstr1Report.showError($('#gstr1_stats_host'), 'Network error loading statistics.');
		});
	}

	$('#gstr1_stats_search').on('click', load);
	Gstr1Report.bindDrillClicks($root, { source: 'statistics' });

	$(window).on('load', function () {
		hideFullPageLoader();
		window.setTimeout(load, 50);
	});
});
</script>
</body>
</html>