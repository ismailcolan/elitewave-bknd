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
		.table-scroll-wrapper {
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
			border: 1px solid #e5e7eb;
			border-radius: 6px;
		}
		#invoice_list_table { font-size: 13px; margin-bottom: 0; border-collapse: collapse; width: 100% !important; }
		#invoice_list_table th {
			background: #0A1E3D !important;
			color: #fff !important;
			font-size: 11px;
			font-weight: 600;
			white-space: nowrap;
			padding: 8px 6px;
			border: none !important;
			border-bottom: 2px solid #061528 !important;
			vertical-align: middle;
		}
		#invoice_list_table td {
			vertical-align: middle;
			padding: 6px 6px;
			border: none !important;
			border-bottom: 1px solid #e9ecef !important;
			background: #fff;
		}
		#invoice_list_table tbody tr:nth-child(even) td { background: #f9fafb; }
		#invoice_list_table .num { text-align: right; white-space: nowrap; }
		#invoice_list_table th.col-actions,
		#invoice_list_table td.col-actions {
			width: 80px;
			min-width: 80px;
			max-width: 80px;
			text-align: center;
			padding: 4px 2px !important;
		}
		#invoice_list_table .col-actions .act-link {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 28px;
			height: 28px;
			margin: 0 2px;
			border: none !important;
			border-radius: 4px;
			text-decoration: none !important;
			cursor: pointer;
		}
		#invoice_list_table .col-actions .act-view { background: #e8edf3; color: #0A1E3D; }
		#invoice_list_table .col-actions .act-view:hover { background: #d5dde8; color: #0A1E3D; }
		#invoice_list_table .col-actions .act-dl { background: #dcfce7; color: #16a34a; }
		#invoice_list_table .col-actions .act-dl:hover { background: #bbf7d0; color: #15803d; }
		#invoice_list_table .col-actions .act-edit { background: #fef3c7; color: #d97706; }
		#invoice_list_table .col-actions .act-edit:hover { background: #fde68a; color: #b45309; }
		/* Kill DataTables sort arrows / black borders on actions header */
		table.dataTable#invoice_list_table thead th,
		table.dataTable#invoice_list_table thead td {
			background: #0A1E3D !important;
			color: #fff !important;
			border: none !important;
			border-bottom: 2px solid #061528 !important;
			padding: 8px 6px !important;
		}
		table.dataTable#invoice_list_table tbody td { background: transparent !important; color: inherit !important; }
		table.dataTable#invoice_list_table thead th.sorting,
		table.dataTable#invoice_list_table thead th.sorting_asc,
		table.dataTable#invoice_list_table thead th.sorting_desc {
			background-image: none !important;
			padding-right: 6px !important;
		}
		table.dataTable#invoice_list_table thead th.col-actions:before,
		table.dataTable#invoice_list_table thead th.col-actions:after { display: none !important; content: none !important; }
		table.dataTable#invoice_list_table thead th.col-actions.sorting,
		table.dataTable#invoice_list_table thead th.col-actions.sorting_asc,
		table.dataTable#invoice_list_table thead th.col-actions.sorting_desc {
			cursor: default !important;
			background-image: none !important;
			padding-right: 6px !important;
		}
		.dataTables_wrapper .dataTables_length,
		.dataTables_wrapper .dataTables_filter { margin-bottom: 12px; }
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
						<div class="table-scroll-wrapper">
						<table class="table" id="invoice_list_table">
							<thead>
								<tr>
									<th>S.No</th><th>Invoice No</th><th>Date</th><th>Customer</th><th>Status</th>
									<th>Grand Total</th><th>GCN Count</th><th class="col-actions">Actions</th>
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
										echo '<td class="num">' . number_format((float) $row['grand_total'], 2) . '</td>';
										echo '<td class="num">' . (int) ($cnt['c'] ?? 0) . '</td>';
										echo '<td class="col-actions">';
										if ($row['status'] === 'draft') {
											echo '<a class="act-link act-edit" href="create_invoice.php?id=' . (int) $row['billing_invoice_id'] . '" title="Edit"><i class="fa fa-pencil"></i></a>';
										}
										if ($row['status'] === 'final') {
											echo '<a class="act-link act-view" target="_blank" href="tax_invoice_pdf.php?id=' . (int) $row['billing_invoice_id'] . '" title="View PDF"><i class="fa fa-eye"></i></a>';
											echo '<a class="act-link act-dl" href="tax_invoice_pdf.php?id=' . (int) $row['billing_invoice_id'] . '&download=1" title="Download PDF"><i class="fa fa-download"></i></a>';
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
<script>
$(function() {
	if ($.fn.DataTable) {
		$('#invoice_list_table').DataTable({
			pageLength: 25,
			order: [[0, 'asc']],
			aoColumnDefs: [
				{ bSortable: false, aTargets: [-1], sClass: 'col-actions' }
			]
		});
	}
});
</script>
</body>
</html>
