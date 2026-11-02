(function (window) {
	'use strict';

	window.dbplInitKpiSparks = function () {
		if (typeof jQuery === 'undefined' || !jQuery.fn.sparkline) {
			return;
		}
		jQuery('.dash-pl-kpi-spark').each(function () {
			var el = jQuery(this);
			var raw = el.attr('data-spark-values') || '[]';
			var color = el.attr('data-spark-color') || '#2563eb';
			var values;
			try {
				values = JSON.parse(raw);
			} catch (e) {
				values = [];
			}
			if (!values.length) {
				values = [0, 0, 0, 0, 0, 0];
			}
			el.sparkline(values, {
				type: 'line',
				width: 72,
				height: 32,
				lineColor: color,
				fillColor: dbplSparkFill(color),
				spotColor: false,
				minSpotColor: false,
				maxSpotColor: false,
				highlightSpotColor: false,
				highlightLineColor: false,
				chartRangeMin: 0
			});
		});
	};

	function dbplSparkFill(hex) {
		if (hex.indexOf('#') === 0 && hex.length === 7) {
			var r = parseInt(hex.slice(1, 3), 16);
			var g = parseInt(hex.slice(3, 5), 16);
			var b = parseInt(hex.slice(5, 7), 16);
			return 'rgba(' + r + ',' + g + ',' + b + ',0.14)';
		}
		return 'rgba(37,99,235,0.14)';
	}

	function dbplRenderExpenseDonut(chartId, legendId, slices, centerShort) {
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
				return { name: row.name, y: row.count, color: row.color || '#94a3b8' };
			});

		var legendEl = document.getElementById(legendId);
		if (legendEl) {
			legendEl.innerHTML = '';
			if (!slices.length) {
				legendEl.innerHTML = '<li class="dash-pl-legend-empty">No expenses in this period.</li>';
			} else {
				slices.forEach(function (row) {
					var pct = row.pct != null ? row.pct : 0;
					var color = row.color || '#94a3b8';
					var li = document.createElement('li');
					li.innerHTML =
						'<span class="dash-pl-legend-left">' +
							'<span class="dash-pl-swatch" style="background:' + color + '"></span>' +
							'<span class="dash-pl-legend-name">' + String(row.name).replace(/</g, '&lt;') + '</span>' +
						'</span>' +
						'<strong class="dash-pl-legend-pct">' + Highcharts.numberFormat(pct, 0) + '%</strong>';
					legendEl.appendChild(li);
				});
			}
		}

		var size = 132;
		var centerHtml = centerShort
			? centerShort + '<br/><span style="font-size:10px;font-weight:600;color:#64748b">Total Expenses</span>'
			: '—';
		var chartData = pieSeries.length ? pieSeries : [{ name: '—', y: 1, color: '#e5e7eb' }];

		Highcharts.chart(chartId, {
			chart: {
				type: 'pie',
				backgroundColor: 'transparent',
				height: size,
				width: size,
				spacing: [0, 0, 0, 0],
				margin: [0, 0, 0, 0],
				events: {
					render: function () {
						var chart = this;
						if (!chart.customCenter) {
							chart.customCenter = chart.renderer
								.label(centerHtml, 0, 0, null, null, null, true)
								.css({
									color: '#0f172a',
									fontSize: '14px',
									fontWeight: '800',
									textAlign: 'center',
									lineHeight: '16px'
								})
								.add();
						} else {
							chart.customCenter.attr({ text: centerHtml });
						}
						var x = chart.plotLeft + chart.plotWidth / 2;
						var y = chart.plotTop + chart.plotHeight / 2 + 2;
						chart.customCenter.attr({ x: x, y: y, align: 'center' });
					}
				}
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
					borderWidth: 3,
					borderColor: '#fff',
					dataLabels: { enabled: false },
					states: { hover: { halo: { size: 0 } } }
				}
			},
			series: [{ name: 'Expenses', data: chartData }]
		});
	}

	window.dbplInitCharts = function (monthLabels, monthSeries, breakdown, centerShort) {
		if (typeof Highcharts === 'undefined') {
			return;
		}
		Highcharts.setOptions({
			chart: { style: { fontFamily: 'Inter, "Segoe UI", sans-serif' } },
			credits: { enabled: false }
		});

		if (typeof dbkDestroyChartById === 'function') {
			dbkDestroyChartById('dash_pl_monthly');
		}

		Highcharts.chart('dash_pl_monthly', {
			chart: { backgroundColor: 'transparent', height: 280 },
			title: { text: null },
			xAxis: {
				categories: monthLabels || [],
				lineColor: '#E2E8F0',
				tickLength: 0,
				labels: { style: { color: '#64748B', fontSize: '11px', fontWeight: '600' } }
			},
			yAxis: {
				min: 0,
				title: { text: null },
				gridLineColor: '#EEF2F7',
				labels: {
					formatter: function () {
						var v = this.value;
						if (v >= 100000) {
							return (v / 100000).toFixed(0) + 'L';
						}
						return Highcharts.numberFormat(v, 0);
					},
					style: { color: '#94A3B8', fontSize: '10px' }
				}
			},
			legend: {
				align: 'center',
				verticalAlign: 'bottom',
				itemStyle: { fontSize: '11px', fontWeight: '600', color: '#475569' },
				symbolRadius: 4
			},
			tooltip: { shared: true, valueDecimals: 0, valuePrefix: '₹ ' },
			plotOptions: {
				column: {
					borderRadius: 4,
					borderWidth: 0,
					groupPadding: 0.12,
					pointPadding: 0.05,
					maxPointWidth: 36
				},
				spline: {
					lineWidth: 2.5,
					marker: { enabled: true, radius: 4, symbol: 'square' }
				}
			},
			series: [
				{ type: 'column', name: 'Revenue', data: monthSeries.revenue || [], color: '#1d4ed8' },
				{ type: 'column', name: 'Direct Cost', data: monthSeries.direct || [], color: '#fb923c' },
				{ type: 'column', name: 'Operating Expenses', data: monthSeries.operating || [], color: '#8b5cf6' },
				{ type: 'spline', name: 'Net Profit', data: monthSeries.net || [], color: '#16a34a', yAxis: 0 }
			]
		});

		dbplRenderExpenseDonut('dash_pl_expense_donut', 'dash_pl_expense_legend', breakdown || [], centerShort);

		if (typeof dbkReflowDashboardCharts === 'function') {
			dbkReflowDashboardCharts();
		}
	};
}(window));
