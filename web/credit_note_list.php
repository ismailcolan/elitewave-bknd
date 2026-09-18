<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');
require_once('include/billing_note_functions.php');

ensure_billing_tables($conn);
ensure_billing_note_tables($conn);

$list_q = mysqli_query($conn, "SELECT n.*, m.invoice_no, c.client_company_name
    FROM billing_note_master n
    LEFT JOIN billing_invoice_master m ON m.billing_invoice_id = n.against_invoice_id
    LEFT JOIN client c ON c.client_id = n.party_id
    WHERE n.note_type='CN' AND n.party_type='client'
    ORDER BY n.billing_note_id DESC
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
		.table-scroll-wrapper {
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
			border: 1px solid #e5e7eb;
			border-radius: 6px;
		}
		#credit_note_list_table { font-size: 13px; margin-bottom: 0; border-collapse: collapse; width: 100% !important; }
		#credit_note_list_table th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			padding: 8px 6px;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
			vertical-align: middle;
		}
		#credit_note_list_table td {
			vertical-align: middle;
			padding: 6px 6px;
			border: none !important;
			border-bottom: 1px solid #e9ecef !important;
			background: #fff;
		}
		#credit_note_list_table tbody tr:nth-child(even) td { background: #f9fafb; }
		#credit_note_list_table .num { text-align: right; white-space: nowrap; }
		#credit_note_list_table th.col-actions,
		#credit_note_list_table td.col-actions {
			width: 80px;
			min-width: 80px;
			text-align: center;
			padding: 4px 2px !important;
		}
		#credit_note_list_table .col-actions .act-link {
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
		#credit_note_list_table .col-actions .act-edit { background: #fef3c7; color: #d97706; }
		#credit_note_list_table .col-actions .act-edit:hover { background: #fde68a; color: #b45309; }
		#credit_note_list_table .col-actions .act-view { background: #e8edf3; color: #0A1E3D; }
		table.dataTable#credit_note_list_table thead th {
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			border: none !important;
			border-bottom: 2px solid var(--panel-border, #C5D3E0) !important;
		}
		table.dataTable#credit_note_list_table thead th.sorting,
		table.dataTable#credit_note_list_table thead th.sorting_asc,
		table.dataTable#credit_note_list_table thead th.sorting_desc {
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
			<div class="col-md-12">
				<div class="ew-page-v2 ew-page-v2--wide-table">
					<div class="ew-page-head">
						<div class="ew-page-head-left">
							<h1 class="ew-page-title">Credit Note</h1>
						</div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Credit Note List</h2>
							<div class="ew-toolbar-right">
								<div class="ew-list-toolbar__tools"></div>
								<a href="create_credit_note.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
						<div class="table-scroll-wrapper">
						<table class="table" id="credit_note_list_table">
							<thead>
								<tr>
									<th>S.No</th>
									<th>Note No</th>
									<th>Date</th>
									<th>Against Invoice</th>
									<th>Customer</th>
									<th>Reason</th>
									<th>Status</th>
									<th>Taxable</th>
									<th>GST</th>
									<th>Total</th>
									<th class="col-actions">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$i = 1;
								if ($list_q) {
									while ($row = mysqli_fetch_assoc($list_q)) {
										$status_badge = $row['status'] === 'final'
											? '<span class="badge-final">Final</span>'
											: '<span class="badge-draft">Draft</span>';
										$note_no = $row['note_no'] ?: ('DRAFT-' . $row['billing_note_id']);
										echo '<tr>';
										echo '<td>' . $i++ . '</td>';
										echo '<td>' . htmlspecialchars($note_no) . '</td>';
										echo '<td>' . htmlspecialchars($row['note_date']) . '</td>';
										echo '<td>' . htmlspecialchars($row['invoice_no'] ?: '—') . '</td>';
										echo '<td>' . htmlspecialchars($row['client_company_name'] ?: '—') . '</td>';
										echo '<td>' . htmlspecialchars($row['reason'] ?: '—') . '</td>';
										echo '<td>' . $status_badge . '</td>';
										echo '<td class="num">' . number_format((float) $row['taxable_value'], 2) . '</td>';
										echo '<td class="num">' . number_format((float) $row['gst_amount'], 2) . '</td>';
										echo '<td class="num">' . number_format((float) $row['grand_total'], 2) . '</td>';
										echo '<td class="col-actions">';
										if ($row['status'] === 'draft') {
											echo '<a class="act-link act-edit" href="create_credit_note.php?id=' . (int) $row['billing_note_id'] . '" title="Edit"><i class="fa fa-pencil"></i></a>';
										} else {
											echo '<a class="act-link act-view" href="create_credit_note.php?id=' . (int) $row['billing_note_id'] . '" title="View"><i class="fa fa-eye"></i></a>';
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
	if ($.fn.dataTable && $('#credit_note_list_table').length && !$.fn.dataTable.fnIsDataTable($('#credit_note_list_table')[0])) {
		$('#credit_note_list_table').dataTable({
			sPaginationType: 'full_numbers',
			iDisplayLength: 25,
			aaSorting: [[0, 'asc']],
			aoColumnDefs: [
				{ bSortable: false, aTargets: [0, -1] }
			]
		});
	}
	window.setTimeout(function() {
		if (window.applyEwListLayout) {
			window.applyEwListLayout();
		}
	}, 250);
});
</script>
</body>
</html>
