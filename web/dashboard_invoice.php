<?php
require_once('include/connect.php');
if (!EW_INVOICE_DASHBOARD_LIVE) {
	header('Location: invoice_list.php');
	exit;
}
require_once('include/function.php');
require_once('include/dashboard_invoice_data.php');

if (isset($_GET['date_from']) || isset($_GET['date_to'])) {
	$target = 'dashboard_invoice.php';
	$branch_redirect = (int) ($_GET['branch_id'] ?? 0);
	if ($branch_redirect > 0) {
		$target .= '?branch_id=' . $branch_redirect;
	}
	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$branch_post = (int) ($_POST['branch_id'] ?? 0);
	$date_from_post = trim((string) ($_POST['date_from'] ?? ''));
	$date_to_post = trim((string) ($_POST['date_to'] ?? ''));
	if ($date_from_post !== '' && $date_to_post !== '') {
		$_SESSION['dbiv_invoice_date_from'] = $date_from_post;
		$_SESSION['dbiv_invoice_date_to'] = $date_to_post;
	} else {
		unset($_SESSION['dbiv_invoice_date_from'], $_SESSION['dbiv_invoice_date_to']);
	}
	$target = 'dashboard_invoice.php';
	if ($branch_post > 0) {
		$target .= '?branch_id=' . $branch_post;
	}
	header('Location: ' . $target);
	exit;
}

$load_params = $_GET;
if (!empty($_SESSION['dbiv_invoice_date_from']) && !empty($_SESSION['dbiv_invoice_date_to'])) {
	$load_params['date_from'] = $_SESSION['dbiv_invoice_date_from'];
	$load_params['date_to'] = $_SESSION['dbiv_invoice_date_to'];
	unset($_SESSION['dbiv_invoice_date_from'], $_SESSION['dbiv_invoice_date_to']);
}

$data = dashboard_invoice_load($conn, $load_params);
$kpi = $data['kpi'];
$fin = $data['financial'];
$out = $data['outstanding'];

function dbk_h($s)
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function dbk_num($n)
{
	return number_format((int) $n);
}

function dbk_display_date($raw)
{
	$raw = trim((string) $raw);
	if ($raw === '') {
		return '';
	}
	$dt = DateTime::createFromFormat('d-m-Y', $raw);
	return ($dt instanceof DateTime) ? $dt->format('d/m/Y') : $raw;
}

$date_range_all = !empty($data['range']['all']);
$date_range_display = $date_range_all
	? ''
	: (dbk_display_date($data['range']['from']) . ' – ' . dbk_display_date($data['range']['to']));

$inv_kpi_cards = array(
	array('key' => 'total', 'class' => 'k-inv-total', 'label' => 'Total Invoices', 'icon' => 'fa-file-text-o'),
	array('key' => 'draft', 'class' => 'k-inv-draft', 'label' => 'Draft', 'icon' => 'fa-pencil-square-o'),
	array('key' => 'generated', 'class' => 'k-inv-generated', 'label' => 'Generated', 'icon' => 'fa-cog'),
	array('key' => 'sent', 'class' => 'k-inv-sent', 'label' => 'Sent', 'icon' => 'fa-paper-plane-o'),
	array('key' => 'partial', 'class' => 'k-inv-partial', 'label' => 'Partially Paid', 'icon' => 'fa-adjust'),
	array('key' => 'paid', 'class' => 'k-inv-paid', 'label' => 'Paid', 'icon' => 'fa-check-circle'),
	array('key' => 'overdue', 'class' => 'k-inv-overdue', 'label' => 'Overdue', 'icon' => 'fa-exclamation-circle'),
	array('key' => 'cancelled', 'class' => 'k-inv-cancelled', 'label' => 'Cancelled', 'icon' => 'fa-ban'),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link href="stylesheets/dashboard-booking.css?v=20261027range8" rel="stylesheet" type="text/css" />
	<link href="stylesheets/dashboard-invoice.css?v=20261026out" rel="stylesheet" type="text/css" />
</head>
<body class="page-header-fixed bg-1 dash-booking-page">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php require_once('include/header.php'); require_once('include/menu.php'); ?>
		</div>

		<div class="container-fluid main-content new_dpt_bottom" id="mainContent">
			<div class="dash-booking-shell">

				<div class="dash-booking-top">
					<div>
						<h1>Invoice Dashboard</h1>
						<p class="sub">Monitor invoice generation, status and receivables.</p>
					</div>
					<div class="dash-booking-filters">
						<form method="post" action="dashboard_invoice.php" id="dash_invoice_filter">
							<div class="dbk-date-range-wrap<?php echo $date_range_all ? '' : ' has-selection'; ?>">
								<label class="dbk-date-range-field<?php echo $date_range_all ? ' is-empty' : ''; ?>" for="dash_date_range">
									<input type="text" id="dash_date_range" value="<?php echo dbk_h($date_range_display); ?>" placeholder="dd/mm/yyyy – dd/mm/yyyy" readonly autocomplete="off" aria-label="Date range" data-ew-skip-upgrade="1">
									<span class="dbk-date-range-ico" aria-hidden="true"><i class="fa fa-calendar"></i></span>
								</label>
								<button type="button" class="dbk-date-range-clear" id="dash_date_clear"<?php echo $date_range_all ? ' hidden' : ''; ?> aria-label="Clear date range">Clear</button>
								<input type="hidden" name="date_from" id="date_from" value="<?php echo dbk_h($data['range']['from']); ?>">
								<input type="hidden" name="date_to" id="date_to" value="<?php echo dbk_h($data['range']['to']); ?>">
							</div>
							<label class="dash-filter-pill">
								<i class="fa fa-building-o" aria-hidden="true"></i>
								<select name="branch_id" id="branch_id" onchange="this.form.submit()">
									<?php foreach ($data['branches'] as $br): ?>
										<option value="<?php echo (int) $br['id']; ?>"<?php echo (int) $data['branch_id'] === (int) $br['id'] ? ' selected' : ''; ?>><?php echo dbk_h($br['label']); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</form>
					</div>
				</div>

				<div class="dash-kpi-row">
					<?php foreach ($inv_kpi_cards as $card): ?>
						<div class="dash-kpi-card <?php echo dbk_h($card['class']); ?>">
							<div class="dash-kpi-head"><span class="dash-kpi-lbl"><?php echo dbk_h($card['label']); ?></span><span class="dash-kpi-ico"><i class="fa <?php echo dbk_h($card['icon']); ?>" aria-hidden="true"></i></span></div>
							<div class="dash-kpi-val"><?php echo dbk_num($kpi[$card['key']] ?? 0); ?></div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php
				$fin_tiles = array(
					array('v' => $fin['total_invoiced_fmt'], 'k' => 'Total Invoiced', 'tone' => 'tone-neutral'),
					array('v' => $fin['current_month_fmt'], 'k' => 'Current Month Billing', 'tone' => 'tone-neutral'),
					array('v' => $fin['prev_month_fmt'], 'k' => 'Previous Month Billing', 'tone' => 'tone-blue'),
					array('v' => $fin['gst_fmt'], 'k' => 'GST Amount', 'tone' => 'tone-neutral'),
					array('v' => $fin['discounts_fmt'], 'k' => 'Discounts', 'tone' => 'tone-lilac'),
					array('v' => $fin['net_value_fmt'], 'k' => 'Net Invoice Value', 'tone' => 'tone-blue'),
				);
				?>
				<div class="dash-card dash-inv-fin-card">
					<div class="dash-card-head">Financial Summary</div>
					<div class="dash-card-body">
						<div class="dash-inv-fin-row">
							<?php foreach ($fin_tiles as $tile): ?>
								<div class="dash-inv-fin-tile <?php echo dbk_h($tile['tone']); ?>">
									<div class="v">₹ <?php echo dbk_h($tile['v']); ?></div>
									<div class="k"><?php echo dbk_h($tile['k']); ?></div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="dash-inv-analytics-row">
					<div class="dash-card dash-inv-analytics-card">
						<div class="dash-card-head">Invoice Status</div>
						<div class="dash-card-body">
							<div class="dbk-modes-wrap dash-inv-status-wrap">
								<div id="dash_invoice_status" class="dbk-modes-chart"></div>
								<ul class="dbk-modes-legend" id="dash_invoice_status_legend"></ul>
							</div>
						</div>
					</div>
					<div class="dash-card dash-inv-analytics-card dash-inv-analytics-card--aging">
						<div class="dash-card-head">Receivable Aging</div>
						<div class="dash-card-body flush">
							<div id="dash_invoice_aging"></div>
						</div>
					</div>
					<div class="dash-card dash-inv-outstanding-card">
						<div class="dash-inv-out-body">
							<h2 class="dash-inv-out-title">Total Outstanding</h2>
							<div class="dash-inv-out-hero">₹ <?php echo dbk_h($out['balance_fmt']); ?></div>
							<div class="dash-inv-out-split">
								<div class="half">
									<div class="sl">Invoices</div>
									<div class="sv"><?php echo dbk_num($out['open_count']); ?></div>
								</div>
								<div class="half">
									<div class="sl">Overdue Invoices</div>
									<div class="sv danger"><?php echo dbk_num($out['overdue_count']); ?></div>
								</div>
							</div>
							<div class="dash-inv-out-trend<?php echo !empty($out['mom_up']) ? ' is-up' : ' is-down'; ?>">
								<i class="fa fa-<?php echo !empty($out['mom_up']) ? 'long-arrow-up' : 'long-arrow-down'; ?>" aria-hidden="true"></i>
								<strong><?php echo dbk_num($out['mom_pct'] ?? 0); ?>%</strong>
								<span class="dash-inv-out-trend-lbl">vs last month</span>
							</div>
						</div>
					</div>
				</div>

				<div class="dash-card">
					<div class="dash-card-head">
						<span>Recent invoices</span>
						<a class="link-all" href="invoice_list.php">View all</a>
					</div>
					<div class="dash-card-body flush">
						<div class="dash-table-wrap">
							<table class="dash-booking-tbl">
								<thead>
									<tr>
										<th>Invoice No.</th>
										<th>Date</th>
										<th>Customer</th>
										<th>GCN No.</th>
										<th class="num">Freight (₹)</th>
										<th class="num">Add. chg</th>
										<th class="num">GST (₹)</th>
										<th class="num">Total (₹)</th>
										<th class="num">Paid</th>
										<th class="num">Balance</th>
										<th>Due date</th>
										<th>Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!$data['recent']): ?>
										<tr><td colspan="13" style="text-align:center;padding:24px;color:#6B7A8D">No invoices in the selected date range.</td></tr>
									<?php else: ?>
										<?php foreach ($data['recent'] as $row): ?>
											<tr>
												<td><a class="gcn-link" href="<?php echo dbk_h($row['view_url']); ?>"><?php echo dbk_h($row['invoice_no']); ?></a></td>
												<td><?php echo dbk_h($row['invoice_date']); ?></td>
												<td><?php echo dbk_h($row['customer']); ?></td>
												<td><?php echo dbk_h($row['gcn_no']); ?></td>
												<td class="num"><?php echo dbk_h($row['freight']); ?></td>
												<td class="num"><?php echo dbk_h($row['other']); ?></td>
												<td class="num"><?php echo dbk_h($row['gst']); ?></td>
												<td class="num"><?php echo dbk_h($row['total']); ?></td>
												<td class="num"><?php echo dbk_h($row['paid']); ?></td>
												<td class="num"><?php echo dbk_h($row['balance']); ?></td>
												<td><?php echo dbk_h($row['due_date']); ?></td>
												<td><span class="inv-st-pill <?php echo dbk_h($row['status_class']); ?>"><?php echo dbk_h($row['status_label']); ?></span></td>
												<td><a class="dash-row-menu" href="<?php echo dbk_h($row['view_url']); ?>" title="Open invoice"><i class="fa fa-eye" aria-hidden="true"></i></a></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</div>
		</div>
		<?php require_once('include/footer.php'); ?>
	</div>

	<div id="dbk_range_dropdown" class="dbk-range-dropdown" data-dbk-range-picker="1" hidden aria-hidden="true">
		<div id="dbk_cal_main" class="dbk-cal-pane" data-dbk-cal-host="1"></div>
	</div>

	<script src="javascripts/highcharts.js"></script>
	<script src="javascripts/dashboard-charts-common.js?v=20261026"></script>
	<script src="javascripts/dashboard-date-range.js?v=20261027range8"></script>
	<script src="javascripts/dashboard-invoice.js?v=20261026row3"></script>
	<script>
	(function () {
		var statusData = <?php echo json_encode($data['status_chart'], JSON_NUMERIC_CHECK); ?>;
		var agingKeys = <?php echo json_encode($data['aging_chart_keys']); ?>;
		var agingLabels = <?php echo json_encode($data['aging_chart_labels']); ?>;
		var agingRaw = <?php echo json_encode($data['aging_chart'], JSON_NUMERIC_CHECK); ?>;
		var agingAmounts = agingKeys.map(function (k) { return Math.round((agingRaw[k] || 0) / 100000 * 100) / 100; });

		if (typeof dbkInitDashboardDateRange === 'function') {
			dbkInitDashboardDateRange('#dash_invoice_filter');
		}
		if (typeof dbivInitInvoiceCharts === 'function') {
			dbivInitInvoiceCharts(statusData, agingLabels, agingAmounts);
		}
	})();
	</script>
</body>
</html>
