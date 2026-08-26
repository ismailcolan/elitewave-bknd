<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$list_q = mysqli_query($conn, "SELECT m.*, c.client_company_name
    FROM billing_invoice_master m
    LEFT JOIN client c ON c.client_id = m.customer_id
    ORDER BY m.billing_invoice_id DESC
    LIMIT 500");
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.invoice-header-bar {
			background: linear-gradient(185deg, var(--ew-navy) 0%, var(--ew-navy-deep) 100%);
			color: #fff; padding: 16px 24px; border-radius: 8px 8px 0 0; font-size: 18px; font-weight: 700;
		}
		.badge-draft { background: #f59e0b; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		.badge-final { background: #16a34a; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
		#invoice_list_table th { background: #0A1E3D; color: #fff; font-size: 12px; }
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
					<div class="invoice-header-bar">
						<i class="fa fa-list"></i> Tax Invoice List
						<a href="create_invoice.php" class="btn btn-primary btn-sm pull-right" style="margin-top:-4px;"><i class="fa fa-plus"></i> Create Invoice</a>
					</div>
					<div class="widget-content padded clearfix">
						<table class="table table-bordered table-striped" id="invoice_list_table">
							<thead>
								<tr>
									<th>S.No</th><th>Invoice No</th><th>Date</th><th>Customer</th><th>Status</th>
									<th>Grand Total</th><th>GCN Count</th><th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$i = 1;
								if ($list_q) {
									while ($row = mysqli_fetch_assoc($list_q)) {
										$cnt_q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM billing_invoice_details WHERE billing_invoice_id='" . (int) $row['billing_invoice_id'] . "'");
										$cnt = mysqli_fetch_assoc($cnt_q);
										$status_badge = $row['status'] === 'final'
											? '<span class="badge-final">Final</span>'
											: '<span class="badge-draft">Draft</span>';
										$inv_no = $row['invoice_no'] ?: ('DRAFT-' . $row['billing_invoice_id']);
										echo '<tr>';
										echo '<td>' . $i++ . '</td>';
										echo '<td>' . htmlspecialchars($inv_no) . '</td>';
										echo '<td>' . htmlspecialchars($row['invoice_date']) . '</td>';
										echo '<td>' . htmlspecialchars($row['client_company_name']) . '</td>';
										echo '<td>' . $status_badge . '</td>';
										echo '<td style="text-align:right;">' . number_format((float) $row['grand_total'], 2) . '</td>';
										echo '<td>' . (int) ($cnt['c'] ?? 0) . '</td>';
										echo '<td>';
										if ($row['status'] === 'draft') {
											echo '<a class="btn btn-xs btn-default" href="create_invoice.php?id=' . (int) $row['billing_invoice_id'] . '" title="Edit"><i class="fa fa-pencil"></i></a> ';
										}
										if ($row['status'] === 'final') {
											echo '<a class="btn btn-xs btn-primary" target="_blank" href="tax_invoice_pdf.php?id=' . (int) $row['billing_invoice_id'] . '" title="PDF"><i class="fa fa-file-pdf-o"></i></a>';
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
	<?php require_once('include/footer.php'); ?>
</div>
<script>
$(function() {
	if ($.fn.DataTable) {
		$('#invoice_list_table').DataTable({ pageLength: 25, order: [[0, 'asc']] });
	}
});
</script>
</body>
</html>
