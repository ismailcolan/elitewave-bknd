(function (window) {
	'use strict';

	var dbpayInited = false;

	function dbpayFmtLakhsLabel(n) {
		var v = Number(n) || 0;
		if (v >= 100000) {
			var l = v / 100000;
			var s = l.toFixed(1);
			if (s.indexOf('.0') === s.length - 2) {
				s = String(Math.round(l));
			}
			return '₹ ' + s + 'L';
		}
		if (v >= 1000) {
			return '₹ ' + (v / 1000).toFixed(1) + 'K';
		}
		return '₹ ' + Math.round(v);
	}

	function dbpayRenderModeDonut(chartId, legendId, slices) {
		if (typeof Highcharts === 'undefined') {
			return;
		}
		if (typeof dbkDestroyChartById === 'function') {
			dbkDestroyChartById(chartId);
		}

		slices = slices || [];
		var pieSeries = slices
			.filter(function (row) {
				return (Number(row.count) || 0) > 0;
			})
			.map(function (row) {
				return {
					name: row.name,
					y: row.count,
					color: row.color || '#94a3b8'
				};
			});

		var legendEl = document.getElementById(legendId);
		if (legendEl) {
			legendEl.innerHTML = '';
			if (!slices.length) {
				legendEl.innerHTML = '<li class="dash-pay-mode-legend-empty">No collections in this period.</li>';
			} else {
				slices.forEach(function (row) {
					var pct = row.pct != null ? row.pct : 0;
					var color = row.color || '#94a3b8';
					var li = document.createElement('li');
					li.innerHTML =
						'<span class="dash-pay-mode-legend-left">' +
							'<span class="dash-pay-mode-swatch" style="background:' + color + '"></span>' +
							'<span class="dash-pay-mode-name">' + String(row.name).replace(/</g, '&lt;') + '</span>' +
						'</span>' +
						'<strong class="dash-pay-mode-pct">' + Highcharts.numberFormat(pct, 0) + '%</strong>';
					legendEl.appendChild(li);
				});
			}
		}

		var size = 128;
		var chartData = pieSeries.length
			? pieSeries
			: [{ name: '—', y: 1, color: '#e5e7eb' }];

		Highcharts.chart(chartId, {
			chart: {
				type: 'pie',
				backgroundColor: 'transparent',
				height: size,
				width: size,
				spacing: [0, 0, 0, 0],
				margin: [0, 0, 0, 0]
			},
			title: { text: null },
			credits: { enabled: false },
			tooltip: {
				enabled: pieSeries.length > 0,
				pointFormat: '<b>{point.percentage:.0f}%</b> (₹ {point.y:,.0f})'
			},
			plotOptions: {
				pie: {
					innerSize: '58%',
					center: ['50%', '50%'],
					size: '100%',
					borderWidth: 3,
					borderColor: '#ffffff',
					slicedOffset: 0,
					dataLabels: { enabled: false },
					states: { hover: { halo: { size: 0 } } }
				}
			},
			series: [{ name: 'Collections', data: chartData }]
		});
	}

	window.dbpayInitPaymentsCharts = function (bars, barLabels, modeChart, efficiencyPct) {
		if (typeof Highcharts === 'undefined') {
			return;
		}

		Highcharts.setOptions({
			chart: { style: { fontFamily: 'Inter, "Segoe UI", sans-serif' } },
			credits: { enabled: false }
		});

		var barColors = ['#2563eb', '#16a34a', '#38bdf8', '#fb923c'];
		var barKeys = ['invoiced', 'due', 'collected', 'outstanding'];
		var barData = barKeys.map(function (k, i) {
			return { y: bars[k] || 0, color: barColors[i] };
		});

		if (typeof dbkDestroyChartById === 'function') {
			dbkDestroyChartById('dash_pay_collection_bars');
			dbkDestroyChartById('dash_pay_efficiency_gauge');
		}

		Highcharts.chart('dash_pay_collection_bars', {
			chart: {
				type: 'column',
				backgroundColor: 'transparent',
				height: 210,
				spacing: [12, 8, 8, 8]
			},
			title: { text: null },
			xAxis: {
				categories: barLabels || [],
				lineColor: '#E2E8F0',
				tickLength: 0,
				labels: {
					style: { color: '#64748B', fontSize: '11px', fontWeight: '600' }
				}
			},
			yAxis: {
				min: 0,
				title: { text: null },
				gridLineColor: '#EEF2F7',
				labels: { enabled: false }
			},
			legend: { enabled: false },
			tooltip: {
				pointFormat: '<b>{point.y:,.0f}</b>'
			},
			plotOptions: {
				column: {
					borderRadius: 8,
					borderWidth: 0,
					pointPadding: 0.08,
					groupPadding: 0.18,
					maxPointWidth: 56,
					dataLabels: {
						enabled: true,
						useHTML: false,
						crop: false,
						overflow: 'none',
						y: -8,
						formatter: function () {
							return dbpayFmtLakhsLabel(this.y);
						},
						style: {
							fontSize: '11px',
							fontWeight: '700',
							color: '#334155',
							textOutline: 'none'
						}
					}
				}
			},
			series: [{ name: 'Amount', data: barData }]
		});

		var eff = Math.max(0, Math.min(100, efficiencyPct || 0));
		var pctEl = document.getElementById('dash_pay_efficiency_pct');
		if (pctEl) {
			pctEl.textContent = Highcharts.numberFormat(eff, 1) + '%';
		}

		Highcharts.chart('dash_pay_efficiency_gauge', {
			chart: {
				type: 'pie',
				backgroundColor: 'transparent',
				height: 108,
				width: 108,
				margin: [0, 0, 0, 0],
				spacing: [0, 0, 0, 0]
			},
			title: { text: null },
			tooltip: { enabled: false },
			plotOptions: {
				pie: {
					innerSize: '78%',
					borderWidth: 0,
					dataLabels: { enabled: false },
					states: { hover: { enabled: false } }
				}
			},
			series: [{
				data: [
					{ y: eff, color: '#16a34a' },
					{ y: 100 - eff, color: '#e5e7eb' }
				]
			}]
		});

		dbpayRenderModeDonut('dash_pay_modes', 'dash_pay_modes_legend', modeChart || []);

		if (dbpayInited && typeof dbkReflowDashboardCharts === 'function') {
			dbkReflowDashboardCharts();
		}
		dbpayInited = true;
	};
}(window));
