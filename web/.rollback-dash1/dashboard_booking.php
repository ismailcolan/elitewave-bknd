<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/dashboard_booking_data.php');

$data = dashboard_booking_load($conn, $_GET);
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
	array('key' => 'booked', 'label' => 'Booked', 'icon' => 'fa-file-text-o', 'tone' => 'pc-blue'),
	array('key' => 'picked_up', 'label' => 'Picked Up', 'icon' => 'fa-hand-paper-o', 'tone' => 'pc-green'),
	array('key' => 'origin_hub', 'label' => 'Origin Hub', 'icon' => 'fa-building-o', 'tone' => 'pc-orange'),
	array('key' => 'in_transit', 'label' => 'In Transit', 'icon' => 'fa-truck', 'tone' => 'pc-blue'),
	array('key' => 'dest_hub', 'label' => 'Destination Hub', 'icon' => 'fa-map-marker', 'tone' => 'pc-teal'),
	array('key' => 'ofd', 'label' => 'Out for Delivery', 'icon' => 'fa-truck', 'tone' => 'pc-orange'),
	array('key' => 'delivered', 'label' => 'Delivered', 'icon' => 'fa-check', 'tone' => 'pc-green'),
);

$trend_days = count($data['trend']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link href="stylesheets/dashboard-booking.css?v=20261021c" rel="stylesheet" type="text/css" />
</head>

<body class="page-header-fixed bg-1 dash-booking-page">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once('include/header.php');
			require_once('include/menu.php');
			?>
		</div>

		<div class="container-fluid main-content new_dpt_bottom">
			<div class="dash-booking-shell">

				<div class="dash-booking-top">
					<div>
						<h1>Consignment Bookings Dashboard</h1>
						<p class="sub">Manage and track all consignment bookings in real-time.</p>
					</div>
					<div class="dash-booking-filters">
						<form method="get" action="dashboard_booking.php" id="dash_booking_filter">
							<label class="dash-filter-pill dash-filter-dates">
								<i class="fa fa-calendar" aria-hidden="true"></i>
								<input type="text" name="date_from" id="date_from" value="<?php echo dbk_h($data['range']['from']); ?>" autocomplete="off" aria-label="From date">
								<span class="dash-date-sep">–</span>
								<input type="text" name="date_to" id="date_to" value="<?php echo dbk_h($data['range']['to']); ?>" autocomplete="off" aria-label="To date">
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
						<div class="ico"><i class="fa fa-truck" aria-hidden="true"></i></div>
						<div class="meta"><div class="val"><?php echo dbk_num($kpi['total']); ?></div><div class="lbl">Total Bookings</div></div>
					</div>
					<div class="dash-kpi-card k-today">
						<div class="ico"><i class="fa fa-file-text-o" aria-hidden="true"></i></div>
						<div class="meta"><div class="val"><?php echo dbk_num($kpi['today']); ?></div><div class="lbl">Today's Bookings</div></div>
					</div>
					<div class="dash-kpi-card k-pending">
						<div class="ico"><i class="fa fa-truck" aria-hidden="true"></i></div>
						<div class="meta"><div class="val"><?php echo dbk_num($kpi['pending_pickup']); ?></div><div class="lbl">Pending Pickups</div></div>
					</div>
					<div class="dash-kpi-card k-transit">
						<div class="ico"><i class="fa fa-building-o" aria-hidden="true"></i></div>
						<div class="meta"><div class="val"><?php echo dbk_num($kpi['in_transit']); ?></div><div class="lbl">In Transit</div></div>
					</div>
					<div class="dash-kpi-card k-dest">
						<div class="ico"><i class="fa fa-check-square-o" aria-hidden="true"></i></div>
						<div class="meta"><div class="val"><?php echo dbk_num($kpi['at_destination']); ?></div><div class="lbl">At Destination</div></div>
					</div>
					<div class="dash-kpi-card k-ofd">
						<div class="ico"><i class="fa fa-truck" aria-hidden="true"></i></div>
						<div class="meta"><div class="val"><?php echo dbk_num($kpi['out_for_delivery']); ?></div><div class="lbl">Out for Delivery</div></div>
					</div>
					<div class="dash-kpi-card k-delivered">
						<div class="ico"><i class="fa fa-check-circle" aria-hidden="true"></i></div>
						<div class="meta"><div class="val"><?php echo dbk_num($kpi['delivered']); ?></div><div class="lbl">Delivered</div></div>
					</div>
					<div class="dash-kpi-card k-cancel">
						<div class="ico"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></div>
						<div class="meta"><div class="val"><?php echo dbk_num($kpi['cancelled']); ?></div><div class="lbl">Cancelled / Hold</div></div>
					</div>
				</div>

				<div class="dash-card dash-revenue-card">
					<div class="dash-revenue-inner">
						<div class="rev-left">
							<div class="rev-label">Total Booking Value</div>
							<div class="rev-hero">₹ <?php echo dbk_h($rev['booking_value_fmt']); ?></div>
						</div>
						<div class="dash-revenue-mid">
							<div class="item"><span class="k">Freight Charges</span><span class="v">₹ <?php echo dbk_h($rev['freight_fmt']); ?></span></div>
							<div class="item"><span class="k">Additional Charges</span><span class="v">₹ <?php echo dbk_h($rev['additional_fmt']); ?></span></div>
							<div class="item"><span class="k">Discounts</span><span class="v">₹ <?php echo dbk_h($rev['discount_fmt']); ?></span></div>
							<div class="item"><span class="k">GST</span><span class="v">₹ <?php echo dbk_h($rev['gst_fmt']); ?></span></div>
						</div>
						<div class="dash-revenue-highlight">
							<div class="block">
								<div class="k">Total Billing Value</div>
								<div class="v">₹ <?php echo dbk_h($rev['billing_value_fmt']); ?></div>
							</div>
							<div class="block">
								<div class="k">Avg. Revenue / Consignment</div>
								<div class="v sm">₹ <?php echo dbk_h($rev['avg_revenue_fmt']); ?></div>
							</div>
						</div>
					</div>
				</div>

				<div class="dash-grid-pipe-donut">
					<div class="dash-card">
						<div class="dash-card-head">Consignment Status Pipeline</div>
						<div class="dash-card-body compact">
							<div class="dash-pipeline">
								<?php foreach ($pipe_steps as $step):
									$p = $pipe[$step['key']];
								?>
								<div class="dash-pipe-step <?php echo dbk_h($step['tone']); ?>">
									<div class="circle"><i class="fa <?php echo dbk_h($step['icon']); ?>" aria-hidden="true"></i></div>
									<div class="plbl"><?php echo dbk_h($step['label']); ?></div>
									<div class="pcnt"><?php echo dbk_num($p['count']); ?></div>
									<div class="pval"><?php echo dbk_h(dashboard_booking_format_inr_short($p['value'])); ?></div>
								</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
					<div class="dash-card">
						<div class="dash-card-head">Service Mode (Bookings)</div>
						<div class="dash-card-body flush">
							<div id="dash_booking_modes"></div>
						</div>
					</div>
				</div>

				<div class="dash-grid-trend-routes">
					<div class="dash-card">
						<div class="dash-card-head">Bookings Trend (Last <?php echo (int) $trend_days; ?> Days)</div>
						<div class="dash-card-body flush">
							<div id="dash_booking_trend"></div>
						</div>
					</div>
					<div class="dash-card">
						<div class="dash-card-head">Top Routes (by Bookings)</div>
						<div class="dash-card-body compact">
							<?php if (!$data['top_routes']): ?>
								<p style="margin:0;font-size:12px;color:#6B7A8D">No route data in this period.</p>
							<?php else: ?>
								<table class="dash-routes-tbl">
									<thead>
										<tr>
											<th>Route</th>
											<th class="num">Bookings</th>
											<th class="num">Value (₹)</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($data['top_routes'] as $rt): ?>
											<tr>
												<td class="route"><?php echo dbk_h($rt['route']); ?></td>
												<td class="num cnt"><?php echo dbk_num($rt['count']); ?></td>
												<td class="num amt"><?php echo dbk_h($rt['value']); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
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
	<script>
	(function () {
		var trendData = <?php echo json_encode($data['trend'], JSON_NUMERIC_CHECK); ?>;
		var modeData = <?php echo json_encode($data['modes'], JSON_NUMERIC_CHECK); ?>;
		var modeColors = ['#1e40af', '#38bdf8', '#ea580c', '#22c55e', '#7c3aed', '#64748b', '#0891b2', '#f59e0b'];

		if (typeof $.fn.datepicker !== 'undefined') {
			$('#date_from, #date_to').datepicker({
				format: 'dd-mm-yyyy',
				autoclose: true
			}).on('changeDate', function () {
				$('#dash_booking_filter').submit();
			});
		}

		if (typeof Highcharts === 'undefined') return;

		Highcharts.setOptions({
			chart: { style: { fontFamily: 'Inter, "Segoe UI", sans-serif' } },
			credits: { enabled: false }
		});

		var cats = trendData.map(function (d) { return d.label; });
		var counts = trendData.map(function (d) { return d.count; });
		var values = trendData.map(function (d) { return Math.round(d.value / 100000 * 100) / 100; });

		Highcharts.chart('dash_booking_trend', {
			chart: { type: 'column', backgroundColor: 'transparent', height: 320, spacing: [14, 16, 12, 12] },
			title: { text: null },
			xAxis: {
				categories: cats,
				labels: { rotation: -45, style: { fontSize: '10px', color: '#64748b' } },
				lineColor: '#e2e8f0'
			},
			yAxis: [{
				min: 0,
				title: { text: null },
				gridLineColor: '#f1f5f9',
				labels: { style: { color: '#64748b' } }
			}, {
				min: 0,
				title: { text: null },
				opposite: true,
				gridLineWidth: 0,
				labels: { style: { color: '#64748b' } }
			}],
			tooltip: {
				shared: true,
				backgroundColor: '#fff',
				borderColor: '#e2e8f0',
				borderRadius: 8,
				formatter: function () {
					var s = '<b>' + this.x + '</b>';
					this.points.forEach(function (p) {
						if (p.series.name === 'Booking Value (₹)') {
							var raw = trendData[p.point.index].value;
							s += '<br/><span style="color:' + p.color + '">\u25CF</span> ' + p.series.name + ': ₹ ' + Highcharts.numberFormat(raw, 2);
						} else {
							s += '<br/><span style="color:' + p.color + '">\u25CF</span> ' + p.series.name + ': <b>' + p.y + '</b>';
						}
					});
					return s;
				}
			},
			legend: { align: 'center', verticalAlign: 'bottom', itemStyle: { fontWeight: '600', fontSize: '11px' } },
			plotOptions: {
				column: { borderRadius: 4, pointPadding: 0.12, groupPadding: 0.08, color: '#2563eb' },
				line: { marker: { radius: 3, fillColor: '#16a34a', lineWidth: 2, lineColor: '#fff' }, lineWidth: 2, color: '#16a34a' }
			},
			series: [{
				name: 'Bookings',
				type: 'column',
				data: counts,
				yAxis: 0
			}, {
				name: 'Booking Value (₹)',
				type: 'line',
				data: values,
				yAxis: 1
			}]
		});

		var pieSeries = modeData.map(function (m, i) {
			return {
				name: m.name,
				y: m.count,
				color: modeColors[i % modeColors.length]
			};
		});

		Highcharts.chart('dash_booking_modes', {
			chart: { type: 'pie', backgroundColor: 'transparent', height: 280, spacing: [12, 8, 8, 8] },
			title: { text: null },
			tooltip: {
				pointFormat: '<b>{point.percentage:.1f}%</b> ({point.y} bookings)'
			},
			plotOptions: {
				pie: {
					innerSize: '62%',
					center: ['38%', '50%'],
					borderWidth: 2,
					borderColor: '#fff',
					dataLabels: { enabled: false },
					showInLegend: true
				}
			},
			legend: {
				align: 'right',
				verticalAlign: 'middle',
				layout: 'vertical',
				x: -8,
				itemStyle: { fontSize: '11px', fontWeight: '600', color: '#1A2332' },
				itemMarginTop: 4,
				symbolRadius: 4,
				labelFormatter: function () {
					return this.name + ' <b>' + Highcharts.numberFormat(this.percentage, 0) + '%</b>';
				},
				useHTML: true
			},
			series: [{ name: 'Bookings', data: pieSeries }]
		});
	})();
	</script>
</body>

</html>
