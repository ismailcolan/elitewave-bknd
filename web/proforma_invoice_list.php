<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_proforma_functions.php');

ensure_billing_proforma_tables($conn);

$list_q = mysqli_query($conn, "SELECT m.*, c.client_company_name
    FROM billing_proforma_master m
    LEFT JOIN client c ON c.client_id = m.customer_id
    WHERE m.status != 'cancelled'
    ORDER BY m.billing_proforma_id DESC
    LIMIT 500");
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.badge-draft { background: #f59e0b; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.badge-final { background: #16a34a; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.badge-style { background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 4px; font-size: 10px; margin-left: 4px; }
		.table-scroll-wrapper { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 6px; }
		#proforma_list_table { font-size: 13px; margin-bottom: 0; width: 100% !important; }
		#proforma_list_table th { background: var(--rail-bg, #DDE7F0) !important; font-size: 11px; font-weight: 700; white-space: nowrap; padding: 8px 6px; }
		#proforma_list_table td { vertical-align: middle; padding: 6px; border-bottom: 1px solid #e9ecef !important; }
		#proforma_list_table .num { text-align: right; white-space: nowrap; }
		#proforma_list_table .col-actions { width: 80px; text-align: center; }
		#proforma_list_table .act-link { display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 4px; margin: 0 2px; }
		#proforma_list_table .act-view { background: #e8edf3; color: #0A1E3D; }
		#proforma_list_table .act-dl { background: #dcfce7; color: #16a34a; }
		#proforma_list_table .act-edit { background: #fef3c7; color: #d97706; }
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
				<div class="ew-page-v2 ew-page-v2--wide-table">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">Proforma Invoice</h1>
						</div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Proforma Invoice List</h2>
							<div class="ew-toolbar-right">
								<a href="create_proforma_invoice.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
						<div class="table-scroll-wrapper">
						<table class="table" id="proforma_list_table">
							<thead>
								<tr>
									<th>S.No</th><th>Proforma No</th><th>Date</th><th>Customer</th><th>Type</th><th>Status</th>
									<th>Grand Total</th><th>GCN Count</th><th class="col-actions">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$i = 1;
								if ($list_q) {
									while ($row = mysqli_fetch_assoc($list_q)) {
										$cnt_q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM billing_proforma_details WHERE billing_proforma_id='" . (int) $row['billing_proforma_id'] . "'");
										$cnt = mysqli_fetch_assoc($cnt_q);
										$status_badge = $row['status'] === 'final'
											? '<span class="badge-final">Final</span>'
											: '<span class="badge-draft">Draft</span>';
										$style_badge = ($row['doc_style'] ?? 'gst') === 'other'
											? '<span class="badge-style">Without GST</span>'
											: '<span class="badge-style">With GST</span>';
										$pno = $row['proforma_no'] ?: ('DRAFT-' . $row['billing_proforma_id']);
										echo '<tr>';
										echo '<td>' . $i++ . '</td>';
										echo '<td>' . htmlspecialchars($pno) . '</td>';
										echo '<td>' . htmlspecialchars($row['invoice_date']) . '</td>';
										echo '<td>' . htmlspecialchars($row['client_company_name']) . '</td>';
										echo '<td>' . $style_badge . '</td>';
										echo '<td>' . $status_badge . '</td>';
										echo '<td class="num">' . number_format((float) $row['grand_total'], 2) . '</td>';
										echo '<td class="num">' . (int) ($cnt['c'] ?? 0) . '</td>';
										echo '<td class="col-actions">';
										if ($row['status'] === 'draft') {
											echo '<a class="act-link act-edit" href="create_proforma_invoice.php?id=' . (int) $row['billing_proforma_id'] . '" title="Edit"><i class="fa fa-pencil"></i></a>';
										}
										if ($row['status'] === 'final') {
											echo '<a class="act-link act-view" target="_blank" href="proforma_invoice_pdf.php?id=' . (int) $row['billing_proforma_id'] . '" title="View PDF"><i class="fa fa-eye"></i></a>';
											echo '<a class="act-link act-dl" href="proforma_invoice_pdf.php?id=' . (int) $row['billing_proforma_id'] . '&download=1" title="Download PDF"><i class="fa fa-download"></i></a>';
										}
										echo '</td></tr>';
									}
								}
								?>
							</tbody>
						</table>
						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>
</div>
<script>
$(function() {
	if ($.fn.dataTable && $('#proforma_list_table').length && !$.fn.dataTable.fnIsDataTable($('#proforma_list_table')[0])) {
		$('#proforma_list_table').dataTable({
			sPaginationType: 'full_numbers',
			iDisplayLength: 25,
			aaSorting: [[0, 'asc']],
			aoColumnDefs: [{ bSortable: false, aTargets: [0, -1], sClass: 'col-actions' }]
		});
	}
	if (window.applyEwListLayout) {
		window.setTimeout(window.applyEwListLayout, 250);
	}
});
</script>
</body>
</html>
