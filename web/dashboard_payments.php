<?php
require_once('include/connect.php');
if (!EW_PAYMENTS_DASHBOARD_LIVE) {
	header('Location: receipt_list.php');
	exit;
}
require_once('include/function.php');
require_once('include/dashboard_payments_data.php');

if (isset($_GET['date_from']) || isset($_GET['date_to'])) {
	$target = 'dashboard_payments.php';
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
		$_SESSION['dbpay_date_from'] = $date_from_post;
		$_SESSION['dbpay_date_to'] = $date_to_post;
	} else {
		unset($_SESSION['dbpay_date_from'], $_SESSION['dbpay_date_to']);
	}
	$target = 'dashboard_payments.php';
	if ($branch_post > 0) {
		$target .= '?branch_id=' . $branch_post;
	}
	header('Location: ' . $target);
	exit;
}

$load_params = $_GET;
if (!empty($_SESSION['dbpay_date_from']) && !empty($_SESSION['dbpay_date_to'])) {
	$load_params['date_from'] = $_SESSION['dbpay_date_from'];
	$load_params['date_to'] = $_SESSION['dbpay_date_to'];
	unset($_SESSION['dbpay_date_from'], $_SESSION['dbpay_date_to']);
}

$data = dashboard_payments_load($conn, $load_params);
$kpi = $data['kpi'];

function dbk_h($s)
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
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

$pay_kpi_cards = array(
	array('fmt' => 'total_receivables_fmt', 'class' => 'k-pay-recv', 'label' => 'Total Receivables', 'icon' => 'fa-inr'),
	array('fmt' => 'todays_collections_fmt', 'class' => 'k-pay-today', 'label' => "Today's Collections", 'icon' => 'fa-calendar-check-o'),
	array('fmt' => 'month_collections_fmt', 'class' => 'k-pay-month', 'label' => 'This Month Collections', 'icon' => 'fa-calendar'),
	array('fmt' => 'pending_collections_fmt', 'class' => 'k-pay-pending', 'label' => 'Pending Collections', 'icon' => 'fa-clock-o'),
	array('fmt' => 'overdue_amount_fmt', 'class' => 'k-pay-overdue', 'label' => 'Overdue Amount', 'icon' => 'fa-exclamation-triangle', 'trend' => true),
	array('fmt' => 'advance_received_fmt', 'class' => 'k-pay-advance', 'label' => 'Advance Received', 'icon' => 'fa-briefcase'),
	array('fmt' => 'unallocated_payments_fmt', 'class' => 'k-pay-unalloc', 'label' => 'Unallocated Payments', 'icon' => 'fa-chain-broken'),
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
	<link href="stylesheets/dashboard-payments.css?v=20261028pay6" rel="stylesheet" type="text/css" />
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
						<h1>Payments Dashboard</h1>
						<p class="sub">Track collections, payment status and customer outstanding.</p>
					</div>
					<div class="dash-booking-filters">
						<form method="post" action="dashboard_payments.php" id="dash_payments_filter">
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

				<div class="dash-kpi-row dash-pay-kpi-row" role="list">
					<?php foreach ($pay_kpi_cards as $card): ?>
						<div class="dash-kpi-card <?php echo dbk_h($card['class']); ?>" role="listitem">
							<div class="dash-kpi-head">
								<span class="dash-kpi-lbl"><?php echo dbk_h($card['label']); ?></span>
								<span class="dash-kpi-ico"><i class="fa <?php echo dbk_h($card['icon']); ?>" aria-hidden="true"></i></span>
							</div>
							<div class="dash-kpi-val dash-pay-kpi-val">
								₹ <?php echo dbk_h($kpi[$card['fmt']]); ?>
								<?php if (!empty($card['trend'])): ?>
									<i class="fa fa-long-arrow-up dash-pay-kpi-trend" aria-hidden="true"></i>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="dash-pay-analytics-row">
					<div class="dash-card dash-pay-analytics-card dash-pay-card-perf">
						<div class="dash-card-head">Collection Performance</div>
						<div class="dash-card-body dash-pay-perf-body">
							<div class="dash-pay-perf-chart">
								<div id="dash_pay_collection_bars"></div>
							</div>
							<div class="dash-pay-efficiency-block">
								<div class="dash-pay-efficiency-ring">
									<div id="dash_pay_efficiency_gauge" class="dash-pay-gauge"></div>
									<div class="dash-pay-efficiency-center">
										<strong id="dash_pay_efficiency_pct"><?php echo dbk_h(number_format($data['efficiency_pct'], 1)); ?>%</strong>
										<span>Collection Efficiency</span>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="dash-card dash-pay-analytics-card dash-pay-card-modes">
						<div class="dash-card-head">Payment Mode (Collections)</div>
						<div class="dash-card-body flush dash-pay-mode-body">
							<div class="dash-pay-mode-wrap">
								<div class="dash-pay-mode-chart-col">
									<div id="dash_pay_modes" class="dash-pay-mode-chart"></div>
								</div>
								<ul class="dash-pay-mode-legend" id="dash_pay_modes_legend"></ul>
							</div>
						</div>
					</div>
					<div class="dash-card dash-pay-analytics-card dash-pay-card-top">
						<div class="dash-card-head">Top 5 Customers by Collections</div>
						<div class="dash-card-body flush">
							<table class="dash-booking-tbl dash-pay-top-tbl">
								<thead>
									<tr><th>Customer</th><th class="num">Amount (₹)</th></tr>
								</thead>
								<tbody>
									<?php if (!$data['top_customers']): ?>
										<tr><td colspan="2" class="dash-pay-empty">No collections in this period.</td></tr>
									<?php else: ?>
										<?php foreach ($data['top_customers'] as $row): ?>
											<tr>
												<td><?php echo dbk_h($row['name']); ?></td>
												<td class="num"><?php echo dbk_h($row['amount_fmt']); ?></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="dash-card dash-pay-recent-card">
					<div class="dash-card-head">
						<span>Recent payments</span>
						<a class="link-all" href="receipt_list.php">View all</a>
					</div>
					<div class="dash-card-body flush">
						<div class="dash-table-wrap">
							<table class="dash-booking-tbl dash-pay-recent-tbl">
								<thead>
									<tr>
										<th>Receipt No.</th>
										<th>Payment Date</th>
										<th>Customer</th>
										<th>Invoice No.</th>
										<th>GCN No.</th>
										<th class="num">Amount (₹)</th>
										<th>Mode</th>
										<th>Reference No.</th>
										<th>Bank</th>
										<th class="num">TDS (₹)</th>
										<th class="num">Unallocated</th>
										<th>Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!$data['recent']): ?>
										<tr><td colspan="13" class="dash-pay-empty">No payments in the selected date range.</td></tr>
									<?php else: ?>
										<?php foreach ($data['recent'] as $row): ?>
											<tr>
												<td><a class="gcn-link" href="<?php echo dbk_h($row['view_url']); ?>"><?php echo dbk_h($row['receipt_no']); ?></a></td>
												<td><?php echo dbk_h($row['receipt_date']); ?></td>
												<td><?php echo dbk_h($row['customer']); ?></td>
												<td><?php echo dbk_h($row['invoice_no']); ?></td>
												<td><?php echo dbk_h($row['gcn_no']); ?></td>
												<td class="num"><?php echo dbk_h($row['amount']); ?></td>
												<td><?php echo dbk_h($row['mode']); ?></td>
												<td><?php echo dbk_h($row['ref']); ?></td>
												<td><?php echo dbk_h($row['bank']); ?></td>
												<td class="num"><?php echo dbk_h($row['tds']); ?></td>
												<td class="num"><?php echo dbk_h($row['unallocated']); ?></td>
												<td><span class="pay-st-pill <?php echo dbk_h($row['status_class']); ?>"><?php echo dbk_h($row['status_label']); ?></span></td>
												<td><a class="dash-row-menu" href="<?php echo dbk_h($row['view_url']); ?>" title="View receipt"><i class="fa fa-eye" aria-hidden="true"></i></a></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="dash-pay-bottom-row">
					<div class="dash-card">
						<div class="dash-card-head">Customer outstanding</div>
						<div class="dash-card-body flush">
							<div class="dash-table-wrap">
								<table class="dash-booking-tbl">
									<thead>
										<tr>
											<th>Customer</th>
											<th class="num">Total Billing</th>
											<th class="num">Total Paid</th>
											<th class="num">Outstanding</th>
											<th class="num">Overdue</th>
											<th>Credit Status</th>
										</tr>
									</thead>
									<tbody>
										<?php if (!$data['customer_outstanding']): ?>
											<tr><td colspan="6" class="dash-pay-empty">No outstanding balances.</td></tr>
										<?php else: ?>
											<?php foreach ($data['customer_outstanding'] as $row): ?>
												<tr>
													<td><?php echo dbk_h($row['name']); ?></td>
													<td class="num"><?php echo dbk_h($row['billing_fmt']); ?></td>
													<td class="num"><?php echo dbk_h($row['paid_fmt']); ?></td>
													<td class="num"><?php echo dbk_h($row['outstanding_fmt']); ?></td>
													<td class="num"><?php echo dbk_h($row['overdue_fmt']); ?></td>
													<td><span class="pay-credit-pill <?php echo dbk_h($row['credit_class']); ?>"><?php echo dbk_h($row['credit']); ?></span></td>
												</tr>
											<?php endforeach; ?>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="dash-card">
						<div class="dash-card-head">Collection alerts</div>
						<ul class="dash-pay-alerts">
							<?php if (!$data['alerts']): ?>
								<li class="dash-pay-alert dash-pay-alert--empty">No alerts right now.</li>
							<?php else: ?>
								<?php foreach ($data['alerts'] as $alert): ?>
									<li class="dash-pay-alert dash-pay-alert--<?php echo dbk_h($alert['type']); ?>">
										<span class="dash-pay-alert-ico" aria-hidden="true"><i class="fa fa-<?php echo $alert['type'] === 'cheque' ? 'clock-o' : 'exclamation-triangle'; ?>"></i></span>
										<div>
											<strong><?php echo dbk_h($alert['title']); ?></strong>
											<span><?php echo dbk_h($alert['detail']); ?></span>
										</div>
									</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
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
	<script src="javascripts/dashboard-payments.js?v=20261028pay4"></script>
	<script>
	(function () {
		if (typeof dbkInitDashboardDateRange === 'function') {
			dbkInitDashboardDateRange('#dash_payments_filter');
		}
		if (typeof dbpayInitPaymentsCharts === 'function') {
			dbpayInitPaymentsCharts(
				<?php echo json_encode($data['collection_bars'], JSON_NUMERIC_CHECK); ?>,
				<?php echo json_encode($data['collection_bar_labels']); ?>,
				<?php echo json_encode($data['mode_chart'], JSON_NUMERIC_CHECK); ?>,
				<?php echo json_encode((float) $data['efficiency_pct'], JSON_NUMERIC_CHECK); ?>
			);
		}
	})();
	</script>
</body>
</html>
