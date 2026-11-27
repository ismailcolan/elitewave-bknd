<?php
require_once 'include/connect.php';
require_once 'include/function.php';
require_once 'include/gstr1_ui.php';

$base = gstr1_base_query_from_request();
$crumbs = array(
	array('label' => 'GST', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'GSTR-1', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'HSN / SAC summary', 'href' => ''),
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
		<div class="ew-page-v2 ew-page-v2--mis-report ew-page-v2--gstr1" id="gstr1_hsn_root">
			<div class="ew-page-head">
				<div class="ew-page-head-left"><h1 class="ew-page-title">HSN / SAC summary</h1></div>
				<div class="ew-page-head-right"><a href="<?php echo htmlspecialchars(gstr1_url('gstr1.php', $base), ENT_QUOTES, 'UTF-8'); ?>" class="ew-btn-v2 ew-btn-v2-outline">Back</a></div>
			</div>
			<?php gstr1_render_breadcrumb($crumbs); ?>
			<input type="hidden" id="gstr1_gstin" value="<?php echo htmlspecialchars($base['gstin'], ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="gstr1_from" value="<?php echo htmlspecialchars($base['from_date'], ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="gstr1_to" value="<?php echo htmlspecialchars($base['to_date'], ENT_QUOTES, 'UTF-8'); ?>">
			<div id="gstr1_hsn_host"></div>
		</div>
	</div>
	<?php require_once 'include/footer.php'; ?>
</div>
<?php gstr1_report_script_tag(); ?>
<script>
$(function () {
	Gstr1Report.fetch('hsn', Gstr1Report.periodParams('#gstr1_hsn_root'), function (data) {
		if (!data.ok) return;
		var hsn = data.hsn || {};
		var html = '';
		['b2b','b2c'].forEach(function (k) {
			var block = hsn[k]; if (!block) return;
			html += '<div class="ew-card ew-erp-list" style="margin-bottom:16px;"><div class="ew-card-toolbar"><h2>' + block.title + '</h2></div><div class="table-scroll-wrapper"><table class="table table-striped gstr1-table"><thead><tr>' +
				'<th>HSN/SAC</th><th>Description</th><th>UQC</th><th class="num">Qty</th><th class="num">Total</th><th class="num">Rate</th><th class="num">Taxable</th><th class="num">IGST</th><th class="num">CGST</th><th class="num">SGST</th><th class="num">Cess</th><th class="num">Tax</th></tr></thead><tbody>';
			(block.rows||[]).forEach(function (r) {
				html += '<tr><td>' + r.sac + '</td><td>' + r.description + '</td><td>' + r.uqc + '</td><td class="num">' + r.quantity + '</td><td class="num">' + (r.total_amount_fmt||'') + '</td><td class="num">' + (r.tax_rate_fmt||'') + '</td>';
				['taxable_fmt','igst_fmt','cgst_fmt','sgst_fmt','cess_fmt','tax_fmt'].forEach(function (f) { html += '<td class="num">' + (r[f]||'') + '</td>'; });
				html += '</tr>';
			});
			var t = block.total || {};
			html += '</tbody><tfoot><tr class="gstr1-total-row"><td colspan="3"><strong>Total</strong></td><td class="num"><strong>' + (t.quantity||'') + '</strong></td><td class="num"><strong>' + (t.total_amount_fmt||'') + '</strong></td><td></td>';
			['taxable_fmt','igst_fmt','cgst_fmt','sgst_fmt','cess_fmt','tax_fmt'].forEach(function (f) {
				var v = t[f] || '';
				html += '<td class="num">' + (v ? '<strong>' + v + '</strong>' : '') + '</td>';
			});
			html += '</tr></tfoot></table></div></div>';
		});
		$('#gstr1_hsn_host').html(html || '<div class="ew-mis-empty-state"><p>No HSN/SAC lines for this period.</p></div>');
	});
});
</script>
</body>
</html>