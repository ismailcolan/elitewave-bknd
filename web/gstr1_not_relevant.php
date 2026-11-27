<?php
require_once 'include/connect.php';
require_once 'include/function.php';
require_once 'include/gstr1_ui.php';

$base = gstr1_base_query_from_request();
$crumbs = array(
	array('label' => 'GST', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'GSTR-1', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'Not relevant', 'href' => ''),
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
		<div class="ew-page-v2 ew-page-v2--mis-report" id="gstr1_nr_root">
			<div class="ew-page-head">
				<div class="ew-page-head-left"><h1 class="ew-page-title">Not relevant for GSTR-1</h1></div>
				<div class="ew-page-head-right"><a href="<?php echo htmlspecialchars(gstr1_url('gstr1.php', $base), ENT_QUOTES, 'UTF-8'); ?>" class="ew-btn-v2 ew-btn-v2-outline">Back</a></div>
			</div>
			<?php gstr1_render_breadcrumb($crumbs); ?>
			<input type="hidden" id="gstr1_gstin" value="<?php echo htmlspecialchars($base['gstin'], ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="gstr1_from" value="<?php echo htmlspecialchars($base['from_date'], ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="gstr1_to" value="<?php echo htmlspecialchars($base['to_date'], ENT_QUOTES, 'UTF-8'); ?>">
			<div id="gstr1_nr_host" class="ew-card"></div>
		</div>
	</div>
	<?php require_once 'include/footer.php'; ?>
</div>
<?php gstr1_report_script_tag(); ?>
<script>
$(function () {
	function drill(extra) { window.location.href = Gstr1Report.drillUrl('gstr1_voucher_register.php', $.extend({ bucket: 'not_relevant', source: 'not_relevant' }, extra)); }
	Gstr1Report.fetch('not_relevant', Gstr1Report.periodParams('#gstr1_nr_root'), function (data) {
		if (!data.ok) return;
		var n = data.not_relevant || {};
		var d = n.other_transactions_detail || {};
		var html = '<div class="ew-form-body"><table class="table gstr1-table"><tbody>' +
			'<tr><td>Non-GST transactions</td><td class="num"><button type="button" class="gstr1-drill-btn" data-extra=\'{"not_relevant_group":"accounting"}\'>' + (n.non_gst_total - (n.other_returns||0)) + '</button></td></tr>' +
			'<tr><td class="ps-4">Inventory vouchers</td><td class="num"><button type="button" class="gstr1-drill-btn" data-extra=\'{"not_relevant_group":"inventory"}\'>' + (n.inventory||0) + '</button></td></tr>' +
			'<tr><td class="ps-4">Other transactions</td><td class="num"><button type="button" class="gstr1-drill-btn" data-extra=\'{"not_relevant_group":"accounting"}\'>' + (n.other_transactions||0) + '</button></td></tr>' +
			'<tr><td class="ps-5">Journals</td><td class="num"><button type="button" class="gstr1-drill-btn" data-extra=\'{"not_relevant_subgroup":"journal"}\'>' + (d.journal||0) + '</button></td></tr>' +
			'<tr><td class="ps-5">Payment vouchers</td><td class="num"><button type="button" class="gstr1-drill-btn" data-extra=\'{"not_relevant_subgroup":"payment_voucher"}\'>' + (d.payment_voucher||0) + '</button></td></tr>' +
			'<tr><td class="ps-5">Receipt vouchers</td><td class="num"><button type="button" class="gstr1-drill-btn" data-extra=\'{"not_relevant_subgroup":"receipt_voucher"}\'>' + (d.receipt_voucher||0) + '</button></td></tr>' +
			'<tr><td class="ps-5">Sales</td><td class="num"><button type="button" class="gstr1-drill-btn" data-extra=\'{"not_relevant_subgroup":"sales"}\'>' + (d.sales||0) + '</button></td></tr>' +
			'<tr><td>Other GST returns (3B / 2B)</td><td class="num"><button type="button" class="gstr1-drill-btn" data-extra=\'{"not_relevant_group":"other_returns"}\'>' + (n.other_returns||0) + '</button></td></tr>' +
			'<tr><th>Total</th><td class="num">' + (n.total||0) + '</td></tr></tbody></table></div>';
		$('#gstr1_nr_host').html(html);
	});
	$('#gstr1_nr_root').on('click', '.gstr1-drill-btn', function () {
		var extra = {};
		try { extra = JSON.parse($(this).attr('data-extra') || '{}'); } catch (e) {}
		drill(extra);
	});
});
</script>
</body>
</html>