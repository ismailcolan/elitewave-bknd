<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/billing_functions.php');

ensure_billing_tables($conn);

$filter = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$where = "WHERE m.status='final'";
if ($filter === 'against') {
    $where .= " AND m.receipt_type='against_invoice'";
} elseif ($filter === 'other') {
    $where .= " AND m.receipt_type IN ('advance','investment','general')";
}

$list_q = mysqli_query($conn, "SELECT m.*, c.client_company_name
    FROM billing_receipt_master m
    LEFT JOIN client c ON c.client_id = m.party_id
    $where
    ORDER BY m.billing_receipt_id DESC
    LIMIT 500");
?>
<!DOCTYPE html>
<html>
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.table-scroll-wrapper { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 6px; }
		#receipt_list_table { font-size: 13px; margin-bottom: 0; border-collapse: collapse; width: 100% !important; }
		#receipt_list_table th { background: #DDE7F0 !important; font-size: 11px; font-weight: 700; padding: 8px 6px; border-bottom: 2px solid #C5D3E0 !important; }
		#receipt_list_table td { padding: 6px; border-bottom: 1px solid #e9ecef !important; vertical-align: middle; }
		#receipt_list_table .num { text-align: right; white-space: nowrap; }
		#receipt_list_table th.col-actions, #receipt_list_table td.col-actions { text-align: center; width: 70px; }
		.filter-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
		.filter-pills a { padding: 6px 12px; border-radius: 8px; border: 1px solid #D8DDE5; font-size: 12px; font-weight: 600; text-decoration: none; color: #334155; background: #fff; }
		.filter-pills a.active { background: #0A1E3D; color: #fff; border-color: #0A1E3D; }
		.act-link { display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 4px; background: #e8edf3; color: #0A1E3D; text-decoration: none !important; }
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
						<div class="ew-page-head-left"><h1 class="ew-page-title">Receipt</h1></div>
					</div>
					<div class="ew-card ew-erp-list">
						<div class="ew-card-toolbar">
							<h2>Receipt List</h2>
							<div class="ew-toolbar-right">
								<a href="create_receipt.php?create=1" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
							</div>
						</div>
						<div class="ew-table-wrap widget-content padded clearfix">
							<div class="filter-pills">
								<a href="receipt_list.php" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
								<a href="receipt_list.php?type=against" class="<?php echo $filter === 'against' ? 'active' : ''; ?>">Against Invoice</a>
								<a href="receipt_list.php?type=other" class="<?php echo $filter === 'other' ? 'active' : ''; ?>">Advance / Investment / General</a>
							</div>
							<div class="table-scroll-wrapper">
								<table class="table" id="receipt_list_table">
									<thead>
										<tr>
											<th>S.No</th>
											<th>Receipt No</th>
											<th>Date</th>
											<th>Type</th>
											<th>Payer / Customer</th>
											<th class="num">Amount</th>
											<th class="num">TDS</th>
											<th class="num">Balance</th>
											<th>Mode</th>
											<th class="col-actions">Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$i = 1;
										if ($list_q) {
											while ($row = mysqli_fetch_assoc($list_q)) {
												$payer = $row['client_company_name'] ?: ($row['payer_name'] ?: '—');
												$type = billing_receipt_type_label($row['receipt_type']);
												$bal = '—';
												if ($row['receipt_type'] === 'against_invoice') {
													$bal = number_format((float) billing_receipt_list_balance_after($conn, (int) $row['billing_receipt_id']), 2);
												}
												echo '<tr>';
												echo '<td>' . $i++ . '</td>';
												echo '<td>' . htmlspecialchars($row['receipt_no']) . '</td>';
												echo '<td>' . htmlspecialchars($row['receipt_date']) . '</td>';
												echo '<td>' . htmlspecialchars($type) . '</td>';
												echo '<td>' . htmlspecialchars($payer) . '</td>';
												echo '<td class="num">' . number_format((float) $row['total_amount'], 2) . '</td>';
												echo '<td class="num">' . number_format((float) $row['tds_total'], 2) . '</td>';
												echo '<td class="num">' . $bal . '</td>';
												echo '<td>' . htmlspecialchars($row['payment_mode']) . '</td>';
												echo '<td class="col-actions"><a class="act-link" href="create_receipt.php?id=' . (int) $row['billing_receipt_id'] . '" title="View"><i class="fa fa-eye"></i></a></td>';
												echo '</tr>';
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
	if ($.fn.dataTable && $('#receipt_list_table').length && !$.fn.dataTable.fnIsDataTable($('#receipt_list_table')[0])) {
		$('#receipt_list_table').dataTable({
			sPaginationType: 'full_numbers',
			iDisplayLength: 25,
			aaSorting: [[0, 'asc']],
			aoColumnDefs: [{ bSortable: false, aTargets: [0, -1] }]
		});
	}
	setTimeout(function() { if (window.applyEwListLayout) window.applyEwListLayout(); }, 250);
});
</script>
</body>
</html>
