/* Invoice dashboard — only invoice-specific chart (aging); donut + date range are shared */
(function (window) {
	'use strict';

	var dbivChartsInited = false;

	window.dbivInitInvoiceCharts = function (statusData, agingLabels, agingAmounts) {
		if (typeof Highcharts === 'undefined') {
			return;
		}

		Highcharts.setOptions({
			chart: { style: { fontFamily: 'Inter, "Segoe UI", sans-serif' } },
			credits: { enabled: false }
		});

		if (typeof dbkRenderDashboardDonut === 'function') {
			dbkRenderDashboardDonut('dash_invoice_status', 'dash_invoice_status_legend', statusData || [], {
				emptyText: 'No invoices in this period.',
				seriesName: 'Invoices',
				tooltipUnit: 'invoices',
				unitLabel: 'Invoices'
			});
		}

		if (dbivChartsInited) {
			if (typeof dbkReflowDashboardCharts === 'function') {
				dbkReflowDashboardCharts();
			}
			return;
		}
		dbivChartsInited = true;

		var agingColors = ['#7dd3fc', '#38bdf8', '#6366f1', '#818cf8', '#22c55e'];
		if (typeof dbkDestroyChartById === 'function') {
			dbkDestroyChartById('dash_invoice_aging');
		}
		Highcharts.chart('dash_invoice_aging', {
			chart: {
				type: 'column',
				backgroundColor: 'transparent',
				height: 240,
				spacing: [12, 8, 8, 4]
			},
			title: { text: null },
			xAxis: {
				categories: agingLabels || [],
				lineColor: '#E2E8F0',
				tickColor: '#E2E8F0',
				labels: { style: { color: '#64748B', fontSize: '10px' } }
			},
			yAxis: {
				min: 0,
				title: { text: null },
				gridLineColor: '#EEF2F7',
				labels: { enabled: false }
			},
			legend: { enabled: false },
			tooltip: {
				pointFormat: '<b>₹ {point.y:.1f} L</b>'
			},
			plotOptions: {
				column: {
					borderRadius: 6,
					borderWidth: 0,
					pointPadding: 0.1,
					groupPadding: 0.12,
					dataLabels: {
						enabled: true,
						useHTML: false,
						format: '₹ {point.y:.1f}L',
						style: {
							fontSize: '10px',
							fontWeight: '700',
							color: '#475569',
							textOutline: 'none'
						},
						y: -4
					}
				}
			},
			series: [{
				name: 'Outstanding',
				data: (agingAmounts || []).map(function (v, i) {
					return { y: v, color: agingColors[i % agingColors.length] };
				})
			}]
		});

		window.addEventListener('sidebarToggled', function () {
			setTimeout(function () {
				if (typeof dbkReflowDashboardCharts === 'function') {
					dbkReflowDashboardCharts();
				}
			}, 320);
		});
		window.addEventListener('resize', function () {
			if (typeof dbkReflowDashboardCharts === 'function') {
				dbkReflowDashboardCharts();
			}
		});
	};
}(window));
