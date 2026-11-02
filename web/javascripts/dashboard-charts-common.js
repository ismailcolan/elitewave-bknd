/* Shared Highcharts helpers for dashboard pages */
(function (window) {
	'use strict';

	window.dbkDestroyChartById = function (id) {
		if (typeof Highcharts === 'undefined' || !Highcharts.charts) {
			return;
		}
		Highcharts.charts.slice().forEach(function (chart) {
			if (chart && chart.renderTo && chart.renderTo.id === id) {
				chart.destroy();
			}
		});
	};

	window.dbkReflowDashboardCharts = function () {
		if (typeof Highcharts === 'undefined' || !Highcharts.charts) {
			return;
		}
		Highcharts.charts.forEach(function (chart) {
			if (chart) {
				chart.reflow();
			}
		});
	};

	/**
	 * @param {string} chartId
	 * @param {string} legendId
	 * @param {Array} slices — { name, count, pct, color }
	 * @param {object} opts — { emptyText, unitLabel, seriesName, tooltipUnit }
	 */
	window.dbkRenderDashboardDonut = function (chartId, legendId, slices, opts) {
		opts = opts || {};
		if (typeof Highcharts === 'undefined') {
			return;
		}

		var emptyText = opts.emptyText || 'No data in this period.';
		var unitLabel = opts.unitLabel || '';
		var seriesName = opts.seriesName || 'Items';
		var tooltipUnit = opts.tooltipUnit || 'items';

		dbkDestroyChartById(chartId);

		var pieSeries = (slices || []).map(function (row) {
			return {
				name: row.name,
				y: row.count,
				color: row.color || '#94a3b8'
			};
		});

		var legendEl = document.getElementById(legendId);
		if (legendEl) {
			legendEl.innerHTML = '';
			if (!pieSeries.length) {
				legendEl.innerHTML = '<li class="dbk-modes-empty">' + emptyText + '</li>';
			} else {
				slices.forEach(function (row, i) {
					var pt = pieSeries[i];
					var pct = row.pct != null ? row.pct : pt.y;
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

		var modeDonutSize = 120;
		var total = pieSeries.reduce(function (s, p) { return s + (p.y || 0); }, 0);

		Highcharts.chart(chartId, {
			chart: {
				type: 'pie',
				backgroundColor: 'transparent',
				height: modeDonutSize,
				width: modeDonutSize,
				spacing: [0, 0, 0, 0],
				margin: [0, 0, 0, 0],
				events: unitLabel ? {
					render: function () {
						var chart = this;
						var centerText = total > 0
							? Highcharts.numberFormat(total, 0) + '<br/><span style="font-size:9px;font-weight:600;opacity:.85">' + unitLabel + '</span>'
							: '—';
						if (!chart.customCenter) {
							chart.customCenter = chart.renderer
								.label(centerText, 0, 0, null, null, null, true)
								.css({
									color: '#0f172a',
									fontSize: '14px',
									fontWeight: '800',
									textAlign: 'center'
								})
								.add();
						} else {
							chart.customCenter.attr({ text: centerText });
						}
						var x = chart.plotLeft + chart.plotWidth / 2;
						var y = chart.plotTop + chart.plotHeight / 2 + 4;
						chart.customCenter.attr({ x: x, y: y, align: 'center' });
					}
				} : undefined
			},
			title: { text: null },
			credits: { enabled: false },
			tooltip: {
				pointFormat: '<b>{point.percentage:.1f}%</b> ({point.y} ' + tooltipUnit + ')'
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
			series: [{ name: seriesName, data: pieSeries }]
		});
	};
}(window));
