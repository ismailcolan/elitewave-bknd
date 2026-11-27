<?php
require_once 'include/connect.php';
require_once 'include/function.php';
require_once 'include/gstr1_ui.php';

$base = gstr1_base_query_from_request();
$source = isset($_GET['source']) ? (string) $_GET['source'] : 'gstr1';
$filter_keys = array('bucket', 'included_sub', 'voucher_type', 'not_relevant_group', 'not_relevant_subgroup', 'section', 'party_gstin', 'exception_only');
$filters = array();
foreach ($filter_keys as $k) {
	if (isset($_GET[$k]) && $_GET[$k] !== '') {
		$filters[$k] = (string) $_GET[$k];
	}
}
$parent = $source === 'statistics' ? 'gstr1_statistics.php' : ($source === 'not_relevant' ? 'gstr1_not_relevant.php' : 'gstr1.php');
$crumbs = array(
	array('label' => 'GST', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'GSTR-1', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => ucwords(str_replace('_', ' ', $source)), 'href' => gstr1_url($parent, array_merge($base, array('source' => $source)))),
	array('label' => 'Voucher register', 'href' => ''),
);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include 'include/title.php'; ?>
	<?php include 'include/css_js.php'; ?>
	<?php gstr1_shared_styles(); ?>
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
	<div class="navbar navbar-fixed-top scroll-hide">
		<?php require_once 'include/header.php'; require_once 'include/menu.php'; ?>
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="row"><div class="col-md-12">
		<div class="ew-page-v2 ew-page-v2--wide-table ew-page-v2--mis-report ew-page-v2--gstr1" id="gstr1_reg_root">
			<div class="ew-page-head">
				<div class="ew-page-head-left"><h1 class="ew-page-title">Voucher register</h1></div>
				<div class="ew-page-head-right">
					<span class="report-count" id="gstr1_reg_count"></span>
					<a href="<?php echo htmlspecialchars(gstr1_url($parent, array_merge($base, array('source' => $source))), ENT_QUOTES, 'UTF-8'); ?>" class="ew-btn-v2 ew-btn-v2-outline">Back</a>
				</div>
			</div>
			<?php gstr1_render_breadcrumb($crumbs); ?>
			<input type="hidden" id="gstr1_gstin" value="<?php echo htmlspecialchars($base['gstin'], ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="gstr1_from" value="<?php echo htmlspecialchars($base['from_date'], ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="gstr1_to" value="<?php echo htmlspecialchars($base['to_date'], ENT_QUOTES, 'UTF-8'); ?>">
			<div class="ew-mis-active-bar" style="margin-bottom:12px;">
				<div class="ew-mis-table-search"><i class="fa fa-search"></i><input type="search" id="gstr1_reg_search" placeholder="Search…" autocomplete="off"></div>
			</div>
			<div id="gstr1_reg_host" class="ew-card ew-erp-list"></div>
		</div>
		</div></div>
	</div>
	<?php require_once 'include/footer.php'; ?>
</div>
<?php gstr1_report_script_tag(); ?>
<script>
$(function () {
	var filters = <?php echo json_encode($filters, JSON_UNESCAPED_UNICODE); ?>;
	function load() {
		Gstr1Report.showSkeleton($('#gstr1_reg_host'));
		var params = $.extend({}, Gstr1Report.periodParams('#gstr1_reg_root'), filters, { cmd: 'register' });
		$.getJSON('gstr1_data.php', params).done(function (data) {
			if (!data.ok) { Gstr1Report.showError($('#gstr1_reg_host'), data.error); return; }
			var payload = data.rows || {};
			var rows = payload.rows || [];
			if (!rows.length) {
				$('#gstr1_reg_host').html('<div class="ew-mis-empty-state"><h3>No vouchers</h3><p>Nothing in this list for the selected period and filters.</p></div>');
				$('#gstr1_reg_count').text('0 vouchers');
				return;
			}
			$('#gstr1_reg_count').text(rows.length + ' voucher(s)');
			var html = '<div class="ew-card-toolbar"><h2>Register</h2></div>' +
				'<div class="ew-table-wrap widget-content padded clearfix">' +
				'<div class="table-scroll-wrapper"><table class="table table-bordered table-striped" id="gstr1_reg_table"><thead><tr>' +
				'<th>Date</th><th>Particulars</th><th>Type</th><th>Voucher no.</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Out qty</th><th class="num">In qty</th>' +
				'</tr></thead><tbody>';
			rows.forEach(function (r) {
				var link = r.view_url ? '<a href="' + r.view_url + '">' + r.voucher_no + '</a>' : r.voucher_no;
				html += '<tr class="' + (r.row_class || '') + '"><td>' + r.voucher_date + '</td>' +
					'<td class="gstr1-reg-particulars">' + r.particulars + '</td>' +
					'<td class="gstr1-reg-type"><span class="gstr1-voucher-type-tag">' + r.voucher_type + '</span></td>' +
					'<td>' + link + '</td>' +
					'<td class="num">' + (r.debit_fmt||'') + '</td><td class="num">' + (r.credit_fmt||'') + '</td>' +
					'<td class="num">' + (r.qty_outward||'') + '</td><td class="num">' + (r.qty_inward||'') + '</td></tr>';
			});
			html += '</tbody><tfoot><tr class="gstr1-total-row"><td colspan="4"><strong>Total</strong></td>' +
				'<td class="num"><strong>' + (payload.total_debit_fmt||'') + '</strong></td>' +
				'<td class="num"><strong>' + (payload.total_credit_fmt||'') + '</strong></td>' +
				'<td colspan="2"></td></tr></tfoot></table></div></div>';
			$('#gstr1_reg_host').html(html);
			Gstr1Report.wireDataTable($('#gstr1_reg_table'), '#gstr1_reg_search');
			if (window.applyEwListLayout) {
				window.applyEwListLayout();
			}
		});
	}
	load();
});
</script>
</body>
</html>