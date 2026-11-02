<?php
require_once('include/connect.php');
if (!defined('EW_PL_DASHBOARD_LIVE') || !EW_PL_DASHBOARD_LIVE) {
	header('Location: expense_gcn_list.php');
	exit;
}
require_once('include/function.php');
require_once('include/dashboard_pl_data.php');

if (isset($_GET['date_from']) || isset($_GET['date_to'])) {
	$target = 'dashboard_pl.php';
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
		$_SESSION['dbpl_date_from'] = $date_from_post;
		$_SESSION['dbpl_date_to'] = $date_to_post;
	} else {
		unset($_SESSION['dbpl_date_from'], $_SESSION['dbpl_date_to']);
	}
	$target = 'dashboard_pl.php';
	if ($branch_post > 0) {
		$target .= '?branch_id=' . $branch_post;
	}
	header('Location: ' . $target);
	exit;
}

$load_params = $_GET;
if (!empty($_SESSION['dbpl_date_from']) && !empty($_SESSION['dbpl_date_to'])) {
	$load_params['date_from'] = $_SESSION['dbpl_date_from'];
	$load_params['date_to'] = $_SESSION['dbpl_date_to'];
	unset($_SESSION['dbpl_date_from'], $_SESSION['dbpl_date_to']);
}

$data = dashboard_pl_load($conn, $load_params);
$kpi = $data['kpi'];

function dbpl_h($s)
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function dbpl_display_date($raw)
{
	$raw = trim((string) $raw);
	if ($raw === '') {
		return '';
	}
	$dt = DateTime::createFromFormat('d-m-Y', $raw);
	return ($dt instanceof DateTime) ? $dt->format('d/m/Y') : $raw;
}

function dbpl_profit_class($raw)
{
	return ((float) $raw) >= 0 ? 'text-pos' : 'text-neg';
}

$date_range_all = !empty($data['range']['all']);
$date_range_display = $date_range_all
	? ''
	: (dbpl_display_date($data['range']['from']) . ' – ' . dbpl_display_date($data['range']['to']));

$pl_kpi_cards = array(
	array('key' => 'total_revenue_fmt', 'sub' => 'total_revenue', 'spark' => 'revenue', 'spark_color' => '#2563eb', 'class' => 'pl-rev', 'label' => 'Total Revenue', 'icon' => 'fa-line-chart', 'prefix' => '₹ '),
	array('key' => 'direct_cost_fmt', 'sub' => 'direct_cost', 'spark' => 'direct', 'spark_color' => '#fb923c', 'class' => 'pl-direct', 'label' => 'Direct Logistics Cost', 'icon' => 'fa-truck', 'prefix' => '₹ '),
	array('key' => 'gross_profit_fmt', 'sub' => 'gross_profit', 'spark' => 'net', 'spark_color' => '#16a34a', 'class' => 'pl-gross', 'label' => 'Gross Profit', 'icon' => 'fa-plus-circle', 'prefix' => '₹ '),
	array('key' => 'gross_margin_fmt', 'sub' => 'gross_margin', 'spark' => 'revenue', 'spark_color' => '#1e3a8a', 'class' => 'pl-gmargin', 'label' => 'Gross Margin', 'icon' => 'fa-pie-chart', 'prefix' => ''),
	array('key' => 'operating_fmt', 'sub' => 'operating', 'spark' => 'operating', 'spark_color' => '#dc2626', 'class' => 'pl-op', 'label' => 'Operating Expenses', 'icon' => 'fa-building', 'prefix' => '₹ '),
	array('key' => 'net_profit_fmt', 'sub' => 'net_profit', 'spark' => 'net', 'spark_color' => '#15803d', 'class' => 'pl-net', 'label' => 'Net Profit', 'icon' => 'fa-money', 'prefix' => '₹ '),
	array('key' => 'net_margin_fmt', 'sub' => 'net_margin', 'spark' => 'net', 'spark_color' => '#0891b2', 'class' => 'pl-nmargin', 'label' => 'Net Margin', 'icon' => 'fa-percent', 'prefix' => ''),
);
$kpi_sub = $data['kpi_sub'];
$kpi_spark = $data['kpi_spark'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link href="stylesheets/dashboard-booking.css?v=20261027range8" rel="stylesheet" type="text/css" />
	<link href="stylesheets/dashboard-pl.css?v=20261028pl2" rel="stylesheet" type="text/css" />
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
						<h1>Expenses &amp; P&amp;L Dashboard</h1>
						<p class="sub">Track expenses, analyze profitability and business performance.</p>
					</div>
					<div class="dash-booking-filters">
						<form method="post" action="dashboard_pl.php" id="dash_pl_filter">
							<div class="dbk-date-range-wrap<?php echo $date_range_all ? '' : ' has-selection'; ?>">
								<label class="dbk-date-range-field<?php echo $date_range_all ? ' is-empty' : ''; ?>" for="dash_date_range">
									<input type="text" id="dash_date_range" value="<?php echo dbpl_h($date_range_display); ?>" placeholder="dd/mm/yyyy – dd/mm/yyyy" readonly autocomplete="off" aria-label="Date range" data-ew-skip-upgrade="1">
									<span class="dbk-date-range-ico" aria-hidden="true"><i class="fa fa-calendar"></i></span>
								</label>
								<button type="button" class="dbk-date-range-clear" id="dash_date_clear"<?php echo $date_range_all ? ' hidden' : ''; ?> aria-label="Clear date range">Clear</button>
								<input type="hidden" name="date_from" id="date_from" value="<?php echo dbpl_h($data['range']['from']); ?>">
								<input type="hidden" name="date_to" id="date_to" value="<?php echo dbpl_h($data['range']['to']); ?>">
							</div>
							<label class="dash-filter-pill">
								<i class="fa fa-building-o" aria-hidden="true"></i>
								<select name="branch_id" id="branch_id" onchange="this.form.submit()">
									<?php foreach ($data['branches'] as $br): ?>
										<option value="<?php echo (int) $br['id']; ?>"<?php echo (int) $data['branch_id'] === (int) $br['id'] ? ' selected' : ''; ?>><?php echo dbpl_h($br['label']); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</form>
					</div>
				</div>

				<div class="dash-pl-kpi-row" role="list">
					<?php foreach ($pl_kpi_cards as $card):
						$sub = $kpi_sub[$card['sub']] ?? array('highlight' => '', 'text' => '', 'tone' => 'muted');
						$spark_series = $kpi_spark[$card['spark']] ?? array();
					?>
						<div class="dash-pl-kpi-card <?php echo dbpl_h($card['class']); ?>" role="listitem">
							<div class="dash-pl-kpi-ico" aria-hidden="true"><i class="fa <?php echo dbpl_h($card['icon']); ?>"></i></div>
							<div class="dash-pl-kpi-val"><?php echo dbpl_h($card['prefix'] . $kpi[$card['key']]); ?></div>
							<div class="dash-pl-kpi-lbl"><?php echo dbpl_h($card['label']); ?></div>
							<div class="dash-pl-kpi-foot">
								<div class="dash-pl-kpi-freq tone-<?php echo dbpl_h($sub['tone']); ?>">
									<?php if (!empty($sub['highlight'])): ?>
										<strong><?php echo dbpl_h($sub['highlight']); ?></strong>
									<?php endif; ?>
									<?php if (!empty($sub['text'])): ?>
										<span><?php echo dbpl_h($sub['text']); ?></span>
									<?php endif; ?>
								</div>
								<span
									class="dash-pl-kpi-spark"
									data-spark-color="<?php echo dbpl_h($card['spark_color']); ?>"
									data-spark-values="<?php echo dbpl_h(json_encode(array_values($spark_series))); ?>"
									aria-hidden="true"
								></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="dash-pl-grid-3">
					<div class="dash-card">
						<div class="dash-card-head">Revenue vs Expenses vs Profit (Monthly)</div>
						<div class="dash-card-body">
							<div id="dash_pl_monthly"></div>
						</div>
					</div>
					<div class="dash-card">
						<div class="dash-card-head">Expense Breakdown</div>
						<div class="dash-card-body">
							<div class="dash-pl-expense-wrap">
								<div class="dash-pl-expense-chart"><div id="dash_pl_expense_donut"></div></div>
								<ul class="dash-pl-expense-legend" id="dash_pl_expense_legend"></ul>
							</div>
							<p class="dash-pl-gst-note">GST on expenses shown separately as <strong>GST Track</strong> (not in P&amp;L totals).</p>
						</div>
					</div>
					<div class="dash-card">
						<div class="dash-card-head">Profitability by Service</div>
						<div class="dash-card-body flush">
							<table class="dash-booking-tbl dash-pl-tbl">
								<thead>
									<tr>
										<th>Service</th>
										<th class="num">Revenue (₹)</th>
										<th class="num">Cost (₹)</th>
										<th class="num">Profit (₹)</th>
										<th class="num">Margin</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!$data['by_service']): ?>
										<tr><td colspan="5" class="dash-pl-empty">No GCN expense data in this period.</td></tr>
									<?php else: ?>
										<?php foreach ($data['by_service'] as $row): ?>
											<tr>
												<td><?php echo dbpl_h($row['name']); ?></td>
												<td class="num"><?php echo dbpl_h($row['revenue_fmt']); ?></td>
												<td class="num"><?php echo dbpl_h($row['cost_fmt']); ?></td>
												<td class="num <?php echo dbpl_profit_class($row['profit_raw']); ?>"><?php echo dbpl_h($row['profit_fmt']); ?></td>
												<td class="num <?php echo dbpl_profit_class($row['margin_raw']); ?>"><?php echo dbpl_h($row['margin_fmt']); ?></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="dash-pl-grid-3">
					<div class="dash-card">
						<div class="dash-card-head">Top Routes by Profitability</div>
						<div class="dash-card-body flush">
							<table class="dash-booking-tbl dash-pl-tbl">
								<thead>
									<tr>
										<th>Route</th>
										<th class="num">Revenue (₹)</th>
										<th class="num">Cost (₹)</th>
										<th class="num">Profit (₹)</th>
										<th class="num">Margin</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!$data['by_route']): ?>
										<tr><td colspan="5" class="dash-pl-empty">No route data in this period.</td></tr>
									<?php else: ?>
										<?php foreach ($data['by_route'] as $row): ?>
											<tr>
												<td><?php echo dbpl_h($row['name']); ?></td>
												<td class="num"><?php echo dbpl_h($row['revenue_fmt']); ?></td>
												<td class="num"><?php echo dbpl_h($row['cost_fmt']); ?></td>
												<td class="num <?php echo dbpl_profit_class($row['profit_raw']); ?>"><?php echo dbpl_h($row['profit_fmt']); ?></td>
												<td class="num <?php echo dbpl_profit_class($row['margin_raw']); ?>"><?php echo dbpl_h($row['margin_fmt']); ?></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
					<div class="dash-card">
						<div class="dash-card-head">Profitability by Customer</div>
						<div class="dash-card-body flush">
							<table class="dash-booking-tbl dash-pl-tbl">
								<thead>
									<tr>
										<th>Customer</th>
										<th class="num">Revenue (₹)</th>
										<th class="num">Cost (₹)</th>
										<th class="num">Profit (₹)</th>
										<th class="num">Margin</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!$data['by_customer']): ?>
										<tr><td colspan="5" class="dash-pl-empty">No customer data in this period.</td></tr>
									<?php else: ?>
										<?php foreach ($data['by_customer'] as $row): ?>
											<tr>
												<td><?php echo dbpl_h($row['name']); ?></td>
												<td class="num"><?php echo dbpl_h($row['revenue_fmt']); ?></td>
												<td class="num"><?php echo dbpl_h($row['cost_fmt']); ?></td>
												<td class="num <?php echo dbpl_profit_class($row['profit_raw']); ?>"><?php echo dbpl_h($row['profit_fmt']); ?></td>
												<td class="num <?php echo dbpl_profit_class($row['margin_raw']); ?>"><?php echo dbpl_h($row['margin_fmt']); ?></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
					<div class="dash-card">
						<div class="dash-card-head">
							<span>Recent Expenses</span>
							<a class="link-all" href="expense_gcn_list.php">View all</a>
						</div>
						<div class="dash-card-body flush">
							<table class="dash-booking-tbl dash-pl-tbl">
								<thead>
									<tr>
										<th>Date</th>
										<th>Category</th>
										<th>Description</th>
										<th class="num">Amount (₹)</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!$data['recent_expenses']): ?>
										<tr><td colspan="4" class="dash-pl-empty">No expenses in this period.</td></tr>
									<?php else: ?>
										<?php foreach ($data['recent_expenses'] as $row): ?>
											<tr>
												<td><?php echo dbpl_h($row['date']); ?></td>
												<td><?php echo dbpl_h($row['category']); ?></td>
												<td><?php echo dbpl_h($row['description']); ?></td>
												<td class="num"><?php echo dbpl_h($row['amount_fmt']); ?></td>
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
	<script src="javascripts/dashboard-pl.js?v=20261028pl2"></script>
	<script>
	(function () {
		if (typeof dbkInitDashboardDateRange === 'function') {
			dbkInitDashboardDateRange('#dash_pl_filter');
		}
		if (typeof dbplInitKpiSparks === 'function') {
			dbplInitKpiSparks();
		}
		if (typeof dbplInitCharts === 'function') {
			dbplInitCharts(
				<?php echo json_encode($data['month_labels']); ?>,
				<?php echo json_encode($data['month_series'], JSON_NUMERIC_CHECK); ?>,
				<?php echo json_encode($data['expense_breakdown'], JSON_NUMERIC_CHECK); ?>,
				<?php echo json_encode($data['expense_breakdown_short']); ?>
			);
		}
	})();
	</script>
</body>
</html>
