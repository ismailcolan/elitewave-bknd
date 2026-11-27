<?php
require_once 'include/connect.php';
require_once 'include/function.php';
require_once 'include/gstr1_ui.php';

$base = gstr1_base_query_from_request();
$crumbs = array(
	array('label' => 'GST', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'GSTR-1', 'href' => gstr1_url('gstr1.php', $base)),
	array('label' => 'B2B summary', 'href' => ''),
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
		<div class="ew-page-v2 ew-page-v2--mis-report ew-page-v2--gstr1" id="gstr1_b2b_root">
			<div class="ew-page-head">
				<div class="ew-page-head-left"><h1 class="ew-page-title">B2B summary</h1></div>
				<div class="ew-page-head-right"><a href="<?php echo htmlspecialchars(gstr1_url('gstr1.php', $base), ENT_QUOTES, 'UTF-8'); ?>" class="ew-btn-v2 ew-btn-v2-outline">Back</a></div>
			</div>
			<?php gstr1_render_breadcrumb($crumbs); ?>
			<input type="hidden" id="gstr1_gstin" value="<?php echo htmlspecialchars($base['gstin'], ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="gstr1_from" value="<?php echo htmlspecialchars($base['from_date'], ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="gstr1_to" value="<?php echo htmlspecialchars($base['to_date'], ENT_QUOTES, 'UTF-8'); ?>">
			<div id="gstr1_b2b_host" class="ew-card ew-erp-list"></div>
		</div>
	</div>
	<?php require_once 'include/footer.php'; ?>
</div>
<?php gstr1_report_script_tag(); ?>
<script>
$(function () {
	Gstr1Report.fetch('b2b', Gstr1Report.periodParams('#gstr1_b2b_root'), function (data) {
		if (!data.ok) return;
		var b2b = data.b2b || {};
		var rows = b2b.rows || [];
		var html = '<div class="table-scroll-wrapper"><table class="table table-striped gstr1-table" id="gstr1_b2b_table"><thead><tr><th>GSTIN</th><th>Party</th><th class="num">Vouchers</th><th class="num">Taxable</th><th class="num">IGST</th><th class="num">CGST</th><th class="num">SGST</th><th class="num">Cess</th><th class="num">Tax</th><th class="num">Invoice</th></tr></thead><tbody>';
		rows.forEach(function (r) {
			var drill = '<button type="button" class="gstr1-drill-btn" data-gstin="' + (r.gstin||'') + '" aria-label="Party ' + r.party_name + '">' + (r.voucher_count||'') + '</button>';
			html += '<tr><td>' + (r.gstin||'—') + '</td><td>' + r.party_name + '</td><td class="num">' + drill + '</td>';
			['taxable_fmt','igst_fmt','cgst_fmt','sgst_fmt','cess_fmt','tax_fmt','invoice_amount_fmt'].forEach(function (k) {
				html += '<td class="num">' + (r[k]||'') + '</td>';
			});
			html += '</tr>';
		});
		var t = b2b.total || {};
		html += '</tbody><tfoot><tr class="gstr1-total-row"><td colspan="2"><strong>Total</strong></td><td class="num"><strong>' + (t.voucher_count||'') + '</strong></td>';
		['taxable_fmt','igst_fmt','cgst_fmt','sgst_fmt','cess_fmt','tax_fmt','invoice_amount_fmt'].forEach(function (k) {
			var v = t[k] || '';
			html += '<td class="num">' + (v ? '<strong>' + v + '</strong>' : '') + '</td>';
		});
		html += '</tr></tfoot></table></div>';
		$('#gstr1_b2b_host').html(html);
		Gstr1Report.wireDataTable($('#gstr1_b2b_table'));
	});
	$('#gstr1_b2b_root').on('click', '.gstr1-drill-btn', function () {
		window.location.href = Gstr1Report.drillUrl('gstr1_voucher_register.php', { bucket: 'included', section: '4A', party_gstin: $(this).data('gstin'), source: 'b2b' });
	});
});
</script>
</body>
</html>