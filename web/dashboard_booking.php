<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/dashboard_booking_data.php');

if (isset($_GET['date_from']) || isset($_GET['date_to'])) {
	$branch_redirect = (int) ($_GET['branch_id'] ?? 0);
	$target = 'dashboard_booking.php';
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
		$_SESSION['dbk_booking_date_from'] = $date_from_post;
		$_SESSION['dbk_booking_date_to'] = $date_to_post;
	} else {
		unset($_SESSION['dbk_booking_date_from'], $_SESSION['dbk_booking_date_to']);
	}
	$target = 'dashboard_booking.php';
	if ($branch_post > 0) {
		$target .= '?branch_id=' . $branch_post;
	}
	header('Location: ' . $target);
	exit;
}

$load_params = $_GET;
if (!empty($_SESSION['dbk_booking_date_from']) && !empty($_SESSION['dbk_booking_date_to'])) {
	$load_params['date_from'] = $_SESSION['dbk_booking_date_from'];
	$load_params['date_to'] = $_SESSION['dbk_booking_date_to'];
	unset($_SESSION['dbk_booking_date_from'], $_SESSION['dbk_booking_date_to']);
}

$data = dashboard_booking_load($conn, $load_params);
$kpi = $data['kpi'];
$rev = $data['revenue'];
$pipe = $data['pipeline'];
function dbk_h($s)
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function dbk_num($n)
{
	return number_format((int) $n);
}

$pipe_steps = array(
	array('key' => 'booked', 'label' => 'Booked', 'icon' => 'fa-file-text-o', 'tone' => 'blue'),
	array('key' => 'picked_up', 'label' => 'Picked Up', 'icon' => 'fa-truck', 'tone' => 'green'),
	array('key' => 'origin_hub', 'label' => 'Origin Hub', 'icon' => 'fa-building-o', 'tone' => 'orange'),
	array('key' => 'in_transit', 'label' => 'In Transit', 'icon' => 'fa-line-chart', 'tone' => 'blue'),
	array('key' => 'dest_hub', 'label' => 'Dest. Hub', 'icon' => 'fa-map-marker', 'tone' => 'indigo'),
	array('key' => 'ofd', 'label' => 'Out for Delivery', 'icon' => 'fa-motorcycle', 'tone' => 'orange'),
	array('key' => 'delivered', 'label' => 'Delivered', 'icon' => 'fa-briefcase', 'tone' => 'green'),
);

$trend_days = count($data['trend']);
$date_range_display = !empty($data['range']['all'])
	? 'All dates'
	: ($data['range']['from'] . ' – ' . $data['range']['to']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link href="stylesheets/dashboard-booking.css?v=20261022e" rel="stylesheet" type="text/css" />
</head>

<body class="page-header-fixed bg-1 dash-booking-page">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once('include/header.php');
			require_once('include/menu.php');
			?>
		</div>

		<div class="container-fluid main-content new_dpt_bottom" id="mainContent">
			<div class="dash-booking-shell">

				<div class="dash-booking-top">
					<div>
						<h1>Consignment Bookings Dashboard</h1>
						<p class="sub">Manage and track all consignment bookings in real-time.</p>
					</div>
					<div class="dash-booking-filters">
						<form method="post" action="dashboard_booking.php" id="dash_booking_filter">
							<label class="dash-filter-pill dash-filter-dates" for="dash_date_range">
								<i class="fa fa-calendar" aria-hidden="true"></i>
								<input type="text" id="dash_date_range" value="<?php echo dbk_h($date_range_display); ?>" readonly autocomplete="off" aria-label="Date range">
								<input type="hidden" name="date_from" id="date_from" value="<?php echo dbk_h($data['range']['from']); ?>">
								<input type="hidden" name="date_to" id="date_to" value="<?php echo dbk_h($data['range']['to']); ?>">
							</label>
							<label class="dash-filter-pill">
								<i class="fa fa-building-o" aria-hidden="true"></i>
								<select name="branch_id" id="branch_id" onchange="this.form.submit()">
									<?php foreach ($data['branches'] as $br): ?>
										<option value="<?php echo (int) $br['id']; ?>"<?php echo (int) $data['branch_id'] === (int) $br['id'] ? ' selected' : ''; ?>><?php echo dbk_h($br['label']); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<button type="submit" class="btn btn-primary btn-sm" style="display:none">Apply</button>
						</form>
					</div>
				</div>

				<div class="dash-kpi-row">
					<div class="dash-kpi-card k-total">
						<div class="dash-kpi-head"><span class="dash-kpi-lbl">Total Bookings</span><span class="dash-kpi-ico"><i class="fa fa-truck" aria-hidden="true"></i></span></div>
						<div class="dash-kpi-val"><?php echo dbk_num($kpi['total']); ?></div>
					</div>
					<div class="dash-kpi-card k-today">
						<div class="dash-kpi-head"><span class="dash-kpi-lbl">Today's Bookings</span><span class="dash-kpi-ico"><i class="fa fa-file-text-o" aria-hidden="true"></i></span></div>
						<div class="dash-kpi-val"><?php echo dbk_num($kpi['today']); ?></div>
					</div>
					<div class="dash-kpi-card k-pending">
						<div class="dash-kpi-head"><span class="dash-kpi-lbl">Pending Pickups</span><span class="dash-kpi-ico"><i class="fa fa-hand-paper-o" aria-hidden="true"></i></span></div>
						<div class="dash-kpi-val"><?php echo dbk_num($kpi['pending_pickup']); ?></div>
					</div>
					<div class="dash-kpi-card k-transit">
						<div class="dash-kpi-head"><span class="dash-kpi-lbl">In Transit</span><span class="dash-kpi-ico"><i class="fa fa-exchange" aria-hidden="true"></i></span></div>
						<div class="dash-kpi-val"><?php echo dbk_num($kpi['in_transit']); ?></div>
					</div>
					<div class="dash-kpi-card k-dest">
						<div class="dash-kpi-head"><span class="dash-kpi-lbl">At Destination</span><span class="dash-kpi-ico"><i class="fa fa-map-marker" aria-hidden="true"></i></span></div>
						<div class="dash-kpi-val"><?php echo dbk_num($kpi['at_destination']); ?></div>
					</div>
					<div class="dash-kpi-card k-ofd">
						<div class="dash-kpi-head"><span class="dash-kpi-lbl">Out for Delivery</span><span class="dash-kpi-ico"><i class="fa fa-motorcycle" aria-hidden="true"></i></span></div>
						<div class="dash-kpi-val"><?php echo dbk_num($kpi['out_for_delivery']); ?></div>
					</div>
					<div class="dash-kpi-card k-delivered">
						<div class="dash-kpi-head"><span class="dash-kpi-lbl">Delivered</span><span class="dash-kpi-ico"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
						<div class="dash-kpi-val"><?php echo dbk_num($kpi['delivered']); ?></div>
					</div>
					<div class="dash-kpi-card k-cancel">
						<div class="dash-kpi-head"><span class="dash-kpi-lbl">Cancelled / Hold</span><span class="dash-kpi-ico"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span></div>
						<div class="dash-kpi-val"><?php echo dbk_num($kpi['cancelled']); ?></div>
					</div>
				</div>

				<div class="dash-card dash-revenue-card">
					<div class="dash-revenue-head">Booking Revenue Summary</div>
					<div class="dash-revenue-inner">
						<div class="rev-left rev-panel">
							<div class="rev-hero">₹ <?php echo dbk_h($rev['booking_value_fmt']); ?></div>
							<div class="rev-label">Total Booking Value</div>
						</div>
						<div class="dash-revenue-mid">
							<div class="item"><span class="v">₹ <?php echo dbk_h($rev['freight_fmt']); ?></span><span class="k">Freight Charges</span></div>
							<div class="item"><span class="v">₹ <?php echo dbk_h($rev['additional_fmt']); ?></span><span class="k">Additional Charges</span></div>
							<div class="item"><span class="v">₹ <?php echo dbk_h($rev['discount_fmt']); ?></span><span class="k">Discounts</span></div>
							<div class="item"><span class="v">₹ <?php echo dbk_h($rev['gst_fmt']); ?></span><span class="k">GST</span></div>
						</div>
						<div class="dash-revenue-highlight rev-panel">
							<div class="block block-bill">
								<div class="v">₹ <?php echo dbk_h($rev['billing_value_fmt']); ?></div>
								<div class="k">Total Billing Value</div>
							</div>
							<div class="block block-avg">
								<span class="v sm">₹ <?php echo dbk_h($rev['avg_revenue_fmt']); ?></span>
								<span class="k">Avg. Revenue / Consignment</span>
							</div>
						</div>
					</div>
				</div>

				<div class="dbk-mock-section dbk-mock-columns">
					<div class="dbk-mock-col-main">
						<div class="dbk-panel dbk-panel-pipeline">
							<div class="dbk-panel-head">Consignment Status Pipeline</div>
							<div class="dbk-panel-body">
								<div class="pipeline">
									<?php foreach ($pipe_steps as $step):
										$p = $pipe[$step['key']];
										$tone = isset($step['tone']) ? $step['tone'] : 'blue';
									?>
									<div class="pipe-step pipe-tone-<?php echo dbk_h($tone); ?><?php echo $p['count'] > 0 ? ' pipe-has-data' : ''; ?>">
										<div class="pipe-icon"><i class="fa <?php echo dbk_h($step['icon']); ?>" aria-hidden="true"></i></div>
										<div class="pipe-lbl"><?php echo dbk_h($step['label']); ?></div>
										<div class="pipe-cnt"><?php echo dbk_num($p['count']); ?></div>
										<div class="pipe-pval"><?php echo dbk_h(dashboard_booking_format_inr_short($p['value'])); ?></div>
									</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
						<div class="dbk-panel dbk-panel-trend">
							<div class="dbk-panel-head">
								<span>Bookings Trend (Last <?php echo (int) $trend_days; ?> Days)</span>
								<span class="dbk-head-hint">₹ in Lakhs</span>
							</div>
							<div class="dbk-panel-body flush">
								<div id="dash_booking_trend"></div>
							</div>
						</div>
					</div>
					<div class="dbk-mock-col-side">
						<div class="dbk-panel dbk-panel-modes">
							<div class="dbk-panel-head">Service Mode (Bookings)</div>
							<div class="dbk-panel-body flush">
								<div class="dbk-modes-wrap">
									<div id="dash_booking_modes" class="dbk-modes-chart"></div>
									<ul class="dbk-modes-legend" id="dash_booking_modes_legend"></ul>
								</div>
							</div>
						</div>
						<div class="dbk-panel dbk-panel-routes">
							<div class="dbk-panel-head">Top Routes (by Bookings)</div>
							<div class="dbk-panel-body flush">
								<?php if (!$data['top_routes']): ?>
									<p style="margin:12px 16px;font-size:12px;color:#6B7A8D">No route data in this period.</p>
								<?php else: ?>
									<div class="dbk-routes-fill">
									<table class="dash-tbl">
										<thead>
											<tr>
												<th>Route</th>
												<th class="num dbk-col-bookings">Bookings</th>
												<th class="num">Value (₹)</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($data['top_routes'] as $rt): ?>
												<tr>
													<td><?php echo dbk_h($rt['route']); ?></td>
													<td class="num dbk-col-bookings"><?php echo dbk_num($rt['count']); ?></td>
													<td class="num"><?php echo dbk_h($rt['value']); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

				<div class="dash-card">
					<div class="dash-card-head">
						<span>Recent Bookings</span>
						<a class="link-all" href="transaction_list.php">View All</a>
					</div>
					<div class="dash-card-body flush">
						<div class="dash-table-wrap">
							<table class="dash-booking-tbl">
								<thead>
									<tr>
										<th>GCN No.</th>
										<th>Booking Date</th>
										<th>Consignor</th>
										<th>Consignee</th>
										<th>Origin</th>
										<th>Destination</th>
										<th>Mode</th>
										<th class="num">Pkgs</th>
										<th class="num">Chargeable Wt (Kg)</th>
										<th class="num">Value (₹)</th>
										<th>Expected Delivery</th>
										<th>Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!$data['recent']): ?>
										<tr><td colspan="13" style="text-align:center;padding:24px;color:#6B7A8D">No bookings in the selected date range.</td></tr>
									<?php else: ?>
										<?php foreach ($data['recent'] as $row): ?>
											<tr>
												<td><a class="gcn-link" href="<?php echo dbk_h($row['edit_url']); ?>"><?php echo dbk_h($row['gcn_no']); ?></a></td>
												<td><?php echo dbk_h($row['grn_date']); ?></td>
												<td><?php echo dbk_h($row['consignor']); ?></td>
												<td><?php echo dbk_h($row['consignee']); ?></td>
												<td><?php echo dbk_h($row['origin']); ?></td>
												<td><?php echo dbk_h($row['destination']); ?></td>
												<td><?php echo dbk_h($row['mode']); ?></td>
												<td class="num"><?php echo dbk_num($row['pkgs']); ?></td>
												<td class="num"><?php echo dbk_h(number_format((float) $row['charged_weight'], 0)); ?></td>
												<td class="num"><?php echo dbk_h($row['value']); ?></td>
												<td><?php echo dbk_h($row['expected_delivery']); ?></td>
												<td><span class="st-pill <?php echo dbk_h($row['status_class']); ?>"><?php echo dbk_h($row['status_label']); ?></span></td>
												<td><a class="dash-row-menu" href="<?php echo dbk_h($row['edit_url']); ?>" title="Open booking"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></a></td>
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

	<script src="javascripts/highcharts.js"></script>
	<script src="javascripts/apexcharts.min.js"></script>
	<script>
	(function () {
		var trendData = <?php echo json_encode($data['trend'], JSON_NUMERIC_CHECK); ?>;
		var modeData = <?php echo json_encode($data['modes'], JSON_NUMERIC_CHECK); ?>;
		var modeColors = ['#2563EB', '#38BDF8', '#EA580C', '#22C55E', '#94A3B8', '#7C3AED', '#0891B2', '#F59E0B'];
		var dbkTrendApex = null;
		var dbkChartsInited = false;

		function dbkNiceAxisMax(peak, intervals) {
			intervals = intervals || 4;
			if (!peak || peak <= 0) {
				return intervals;
			}
			var rough = peak * 1.08;
			var rawStep = rough / intervals;
			var mag = Math.pow(10, Math.floor(Math.log10(rawStep)));
			if (!isFinite(mag) || mag <= 0) {
				mag = 1;
			}
			var norm = rawStep / mag;
			var niceNorm = norm <= 1 ? 1 : norm <= 2 ? 2 : norm <= 2.5 ? 2.5 : norm <= 5 ? 5 : 10;
			var step = niceNorm * mag;
			var max = step * intervals;
			if (max < peak) {
				max += step;
			}
			return max;
		}

		function dbkShowTrendXLabel(index, total) {
			if (index === 0 || index === total - 1) {
				return true;
			}
			var step = total <= 14 ? 2 : 5;
			return ((index + 1) % step) === 0;
		}

		function dbkDestroyChartById(id) {
			if (typeof Highcharts === 'undefined' || !Highcharts.charts) {
				return;
			}
			Highcharts.charts.slice().forEach(function (chart) {
				if (chart && chart.renderTo && chart.renderTo.id === id) {
					chart.destroy();
				}
			});
		}

		function dbkRenderTrendHighcharts(cats, counts, values, yLeftMax, yRightMax) {
			if (typeof Highcharts === 'undefined') {
				return;
			}
			dbkDestroyChartById('dash_booking_trend');
			var barFill = {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [[0, '#5DADE2'], [0.5, '#85C1E9'], [1, '#AED6F1']]
			};
			Highcharts.chart('dash_booking_trend', {
				chart: {
					type: 'column',
					backgroundColor: 'transparent',
					height: 320,
					spacing: [36, 8, 10, 4]
				},
				title: { text: null },
				credits: { enabled: false },
				xAxis: {
					categories: cats,
					labels: {
						rotation: 0,
						style: { fontSize: '10px', color: '#64748B' },
						formatter: function () {
							return dbkShowTrendXLabel(this.pos, cats.length) ? this.value : '';
						}
					},
					lineColor: '#E2E8F0',
					tickColor: '#E2E8F0'
				},
				yAxis: [{
					min: 0,
					max: yLeftMax,
					tickAmount: 5,
					allowDecimals: false,
					title: { text: null },
					gridLineColor: '#EEF2F7',
					labels: { style: { fontSize: '10px', color: '#94A3B8' } }
				}, {
					min: 0,
					max: yRightMax,
					tickAmount: 5,
					title: { text: null },
					opposite: true,
					gridLineWidth: 0,
					labels: { style: { fontSize: '10px', color: '#94A3B8' } }
				}],
				legend: {
					align: 'center',
					verticalAlign: 'top',
					floating: true,
					y: 6,
					itemStyle: { fontWeight: '600', fontSize: '11px', color: '#64748B' }
				},
				tooltip: { shared: true },
				plotOptions: {
					column: { borderRadius: 3, borderWidth: 0, color: barFill, pointPadding: 0.1, groupPadding: 0.16 },
					spline: { lineWidth: 2.5, color: '#2ECC71', marker: { radius: 4, fillColor: '#2ECC71', lineWidth: 2, lineColor: '#fff' } }
				},
				series: [{
					name: 'Bookings',
					type: 'column',
					data: counts,
					yAxis: 0,
					color: barFill
				}, {
					name: 'Booking Value (₹)',
					type: 'spline',
					data: values,
					yAxis: 1,
					color: '#2ECC71'
				}]
			});
		}

		function dbkRenderTrendChart(cats, counts, values) {
			var countPeak = Math.max.apply(null, counts.concat([0]));
			var valuePeak = Math.max.apply(null, values.concat([0]));
			var yLeftMax = dbkNiceAxisMax(countPeak, 4);
			var yRightMax = dbkNiceAxisMax(valuePeak, 4);
			var trendEl = document.getElementById('dash_booking_trend');
			if (!trendEl) {
				return;
			}

			if (typeof ApexCharts === 'undefined') {
				dbkRenderTrendHighcharts(cats, counts, values, yLeftMax, yRightMax);
				return;
			}

			if (dbkTrendApex) {
				dbkTrendApex.destroy();
				dbkTrendApex = null;
			}
			trendEl.innerHTML = '';

			try {
				dbkTrendApex = new ApexCharts(trendEl, {
					series: [
						{ name: 'Bookings', type: 'column', data: counts },
						{ name: 'Booking Value (₹)', type: 'line', data: values }
					],
					chart: {
						height: 320,
						type: 'line',
						fontFamily: 'Inter, "Segoe UI", sans-serif',
						toolbar: { show: false },
						zoom: { enabled: false }
					},
					colors: ['#5DADE2', '#2ECC71'],
					stroke: { width: [0, 2.5], curve: 'smooth' },
					plotOptions: {
						bar: { columnWidth: '62%', borderRadius: 3 }
					},
					fill: {
						type: 'gradient',
						gradient: {
							shade: 'light',
							type: 'vertical',
							shadeIntensity: 0.35,
							gradientToColors: ['#AED6F1'],
							inverseColors: false,
							opacityFrom: 1,
							opacityTo: 0.85,
							stops: [0, 100]
						}
					},
					dataLabels: { enabled: false },
					markers: { size: [0, 4], strokeWidth: 2, strokeColors: '#fff' },
					xaxis: {
						categories: cats,
						axisBorder: { show: true, color: '#E2E8F0' },
						axisTicks: { show: true, color: '#E2E8F0' },
						labels: {
							style: { colors: '#64748B', fontSize: '10px', fontWeight: 500 },
							formatter: function (val, _ts, opts) {
								var idx = opts && typeof opts.dataPointIndex === 'number'
									? opts.dataPointIndex
									: (opts && typeof opts.i === 'number' ? opts.i : 0);
								return dbkShowTrendXLabel(idx, cats.length) ? val : '';
							}
						}
					},
					yaxis: [
						{
							min: 0,
							max: yLeftMax,
							tickAmount: 4,
							forceNiceScale: true,
							labels: {
								style: { colors: '#94A3B8', fontSize: '10px' },
								formatter: function (v) { return Math.round(v); }
							}
						},
						{
							opposite: true,
							min: 0,
							max: yRightMax,
							tickAmount: 4,
							forceNiceScale: true,
							labels: {
								style: { colors: '#94A3B8', fontSize: '10px' },
								formatter: function (v) { return (Math.round(v * 10) / 10).toString(); }
							}
						}
					],
					grid: {
						borderColor: '#EEF2F7',
						padding: { top: 4, right: 8, bottom: 0, left: 8 },
						xaxis: { lines: { show: false } }
					},
					legend: {
						show: true,
						position: 'top',
						horizontalAlign: 'center',
						fontSize: '11px',
						fontWeight: 600,
						labels: { colors: '#64748B' },
						markers: { width: 10, height: 10, radius: 2 }
					},
					tooltip: { shared: true, intersect: false }
				});
				dbkTrendApex.render().catch(function () {
					dbkTrendApex = null;
					dbkRenderTrendHighcharts(cats, counts, values, yLeftMax, yRightMax);
				});
			} catch (err) {
				dbkTrendApex = null;
				dbkRenderTrendHighcharts(cats, counts, values, yLeftMax, yRightMax);
			}
		}

		if (typeof $.fn.datepicker !== 'undefined') {
			function dbkParseDMY(s) {
				var p = String(s || '').split('-');
				if (p.length !== 3) return null;
				var d = new Date(Date.UTC(parseInt(p[2], 10), parseInt(p[1], 10) - 1, parseInt(p[0], 10)));
				return isNaN(d.getTime()) ? null : d;
			}
			function dbkFmtDMY(d) {
				if (!d) return '';
				var dd = ('0' + d.getUTCDate()).slice(-2);
				var mm = ('0' + (d.getUTCMonth() + 1)).slice(-2);
				return dd + '-' + mm + '-' + d.getUTCFullYear();
			}
			function dbkDayTs(d) {
				return Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate());
			}

			var rangeStart = dbkParseDMY($('#date_from').val());
			var rangeEnd = dbkParseDMY($('#date_to').val());
			var dbkRangeDragging = false;

			function dbkSyncRangeDisplay() {
				if (rangeStart && rangeEnd) {
					$('#dash_date_range').val(dbkFmtDMY(rangeStart) + ' – ' + dbkFmtDMY(rangeEnd));
					$('#date_from').val(dbkFmtDMY(rangeStart));
					$('#date_to').val(dbkFmtDMY(rangeEnd));
				}
			}

			function dbkNormalizeRange() {
				if (!rangeStart || !rangeEnd) return;
				if (dbkDayTs(rangeEnd) < dbkDayTs(rangeStart)) {
					var tmp = rangeStart;
					rangeStart = rangeEnd;
					rangeEnd = tmp;
				}
			}

			function dbkDateFromPickerCell($td) {
				var picker = $rangeInput.data('datepicker');
				if (!picker || !$td || !$td.length || !$td.hasClass('day') || $td.hasClass('disabled')) {
					return null;
				}
				var day = parseInt($td.text(), 10) || 1;
				var year = picker.viewDate.getUTCFullYear();
				var month = picker.viewDate.getUTCMonth();
				if ($td.hasClass('old')) {
					if (month === 0) {
						month = 11;
						year -= 1;
					} else {
						month -= 1;
					}
				} else if ($td.hasClass('new')) {
					if (month === 11) {
						month = 0;
						year += 1;
					} else {
						month += 1;
					}
				}
				return new Date(Date.UTC(year, month, day));
			}

			function dbkRefreshRangeHighlight() {
				var picker = $rangeInput.data('datepicker');
				if (picker && typeof picker.fill === 'function') {
					picker.fill();
				} else {
					$rangeInput.datepicker('update');
				}
			}

			function dbkCommitRangeFilter() {
				if (!rangeStart || !rangeEnd) return;
				dbkNormalizeRange();
				dbkSyncRangeDisplay();
				$rangeInput.datepicker('hide');
				$('#dash_booking_filter').submit();
			}

			var $rangeInput = $('#dash_date_range');
			$rangeInput.datepicker({
				format: 'dd-mm-yyyy',
				autoclose: false,
				todayHighlight: false,
				container: 'body',
				maxViewMode: 2,
				beforeShowDay: function (date) {
					if (!rangeStart || !rangeEnd) {
						return true;
					}
					var t = dbkDayTs(date);
					var from = Math.min(dbkDayTs(rangeStart), dbkDayTs(rangeEnd));
					var to = Math.max(dbkDayTs(rangeStart), dbkDayTs(rangeEnd));
					if (t < from || t > to) {
						return true;
					}
					if (from === to) {
						return { enabled: true, classes: 'ew-range-single' };
					}
					var cls = [];
					if (t === from) {
						cls.push('ew-range-start');
					} else if (t === to) {
						cls.push('ew-range-end');
					} else {
						cls.push('ew-range-mid');
					}
					var dow = date.getUTCDay();
					if (dow === 0) {
						cls.push('ew-range-wk-start');
					}
					if (dow === 6) {
						cls.push('ew-range-wk-end');
					}
					return { enabled: true, classes: cls.join(' ') };
				}
			}).on('show', function () {
				dbkRangeDragging = false;
				$('body').removeClass('dbk-range-dragging');
				if (rangeStart && rangeEnd) {
					dbkRefreshRangeHighlight();
				}
			});

			$('.dash-filter-dates').on('click', function (ev) {
				if ($(ev.target).is('input[type="hidden"]')) return;
				$rangeInput.datepicker('show');
			});

			$(document).on('mousedown', '.datepicker td.day:not(.disabled)', function (ev) {
				if (ev.which !== 1) return;
				ev.preventDefault();
				ev.stopPropagation();
				var d = dbkDateFromPickerCell($(this));
				if (!d) return;
				dbkRangeDragging = true;
				$('body').addClass('dbk-range-dragging');
				rangeStart = d;
				rangeEnd = d;
				$rangeInput.val(dbkFmtDMY(rangeStart) + ' – ' + dbkFmtDMY(rangeEnd));
				dbkRefreshRangeHighlight();
			});

			$(document).on('mouseenter', '.datepicker td.day:not(.disabled)', function () {
				if (!dbkRangeDragging) return;
				var d = dbkDateFromPickerCell($(this));
				if (!d) return;
				rangeEnd = d;
				$rangeInput.val(dbkFmtDMY(rangeStart) + ' – ' + dbkFmtDMY(rangeEnd));
				dbkRefreshRangeHighlight();
			});

			$(document).on('mouseup', function () {
				if (!dbkRangeDragging) return;
				dbkRangeDragging = false;
				$('body').removeClass('dbk-range-dragging');
				dbkCommitRangeFilter();
			});
		}

		function dbkInitCharts() {
		if (dbkChartsInited) {
			return;
		}
		dbkChartsInited = true;

		var cats = trendData.map(function (d) { return d.label; });
		var counts = trendData.map(function (d) { return d.count; });
		var values = trendData.map(function (d) { return Math.round(d.value / 100000 * 100) / 100; });

		dbkRenderTrendChart(cats, counts, values);

		if (typeof Highcharts === 'undefined') {
			return;
		}

		Highcharts.setOptions({
			chart: { style: { fontFamily: 'Inter, "Segoe UI", sans-serif' } },
			credits: { enabled: false }
		});

		dbkDestroyChartById('dash_booking_modes');

		var pieSeries = modeData.map(function (m, i) {
			return {
				name: m.name,
				y: m.count,
				color: modeColors[i % modeColors.length]
			};
		});

		var modeDonutSize = 120;
		var legendEl = document.getElementById('dash_booking_modes_legend');
		if (legendEl) {
			legendEl.innerHTML = '';
			if (!pieSeries.length) {
				legendEl.innerHTML = '<li class="dbk-modes-empty">No mode data in this period.</li>';
			} else {
				pieSeries.forEach(function (pt, i) {
					var pct = modeData[i] && modeData[i].pct != null
						? modeData[i].pct
						: pt.y;
					var safeName = String(pt.name)
						.replace(/&/g, '&amp;')
						.replace(/</g, '&lt;')
						.replace(/"/g, '&quot;');
					var li = document.createElement('li');
					li.innerHTML =
						'<span class="dbk-mode-name-wrap">' +
							'<span class="dbk-mode-swatch" style="background:' + pt.color + '"></span>' +
							'<span class="dbk-mode-name" title="' + safeName + '">' + safeName + '</span>' +
						'</span>' +
						'<span class="dbk-mode-pct">' + Highcharts.numberFormat(pct, 0) + '%</span>';
					legendEl.appendChild(li);
				});
			}
		}

		Highcharts.chart('dash_booking_modes', {
			chart: {
				type: 'pie',
				backgroundColor: 'transparent',
				height: modeDonutSize,
				width: modeDonutSize,
				spacing: [0, 0, 0, 0],
				margin: [0, 0, 0, 0]
			},
			title: { text: null },
			credits: { enabled: false },
			tooltip: {
				pointFormat: '<b>{point.percentage:.1f}%</b> ({point.y} bookings)'
			},
			plotOptions: {
				pie: {
					innerSize: '58%',
					center: ['50%', '50%'],
					size: '100%',
					borderWidth: 2,
					borderColor: '#fff',
					dataLabels: { enabled: false },
					showInLegend: false
				}
			},
			legend: { enabled: false },
			series: [{ name: 'Bookings', data: pieSeries }]
		});
		}

		function dbkReflowCharts() {
			if (dbkTrendApex) {
				dbkTrendApex.updateOptions({}, false, false);
			}
			if (typeof Highcharts === 'undefined' || !Highcharts.charts) return;
			Highcharts.charts.forEach(function (chart) {
				if (chart) chart.reflow();
			});
		}

		window.addEventListener('sidebarToggled', function () {
			setTimeout(dbkReflowCharts, 320);
		});
		window.addEventListener('resize', function () {
			dbkReflowCharts();
		});
		function dbkBootCharts() {
			dbkInitCharts();
			setTimeout(dbkReflowCharts, 120);
		}

		if (typeof jQuery !== 'undefined') {
			jQuery(dbkBootCharts);
		} else if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', dbkBootCharts);
		} else {
			dbkBootCharts();
		}
	})();
	</script>
</body>

</html>
