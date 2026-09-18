<div class="cld-root" id="cargoOpsRoot">
	<div class="cld-mast">
		<div>
			<p class="cld-kicker">Elite Wave · operations</p>
			<h1>Operations ledger</h1>
			<p class="cld-sub" id="cldAsOf">Bookings, delivery, invoices and collections — the day’s operations at a glance.</p>
		</div>
		<div class="cld-seg" id="cldPeriod">
			<button type="button" data-p="weekly">Week</button>
			<button type="button" class="on" data-p="monthly">Month</button>
		</div>
	</div>

	<div id="cldBoot" class="cld-boot">
		<?php
		if (!function_exists('ew_skeleton_dashboard')) {
			require_once __DIR__ . '/ew_skeleton.php';
		}
		echo ew_skeleton_dashboard();
		?>
	</div>

	<div id="cldShell" style="display:none;">
		<p class="cld-story">What happened</p>
		<div class="cld-flow" id="cldFlow"></div>

		<div class="cld-split">
			<div class="cld-panel">
				<h2>Bookings vs delivered vs invoices</h2>
				<p class="hint" id="opsHint">Live counts from consignments and GST invoices</p>
				<div id="cldOpsChart" class="cld-chart"></div>
			</div>
			<div class="cld-panel">
				<h2>Delivery now</h2>
				<p class="hint">From GCN status on the booking</p>
				<div id="cldDonut" class="cld-chart"></div>
				<div class="cld-legend" id="cldDonutLegend"></div>
			</div>
		</div>

		<div class="cld-trio">
			<div class="cld-panel">
				<h2>Revenue · collection · expense</h2>
				<p class="hint" id="monHint">GST invoice value, payments, expense entries</p>
				<div id="cldMonChart" class="cld-chart cld-chart-sm"></div>
			</div>
			<div class="cld-panel">
				<h2>Invoice money</h2>
				<p class="hint">Billed vs still open</p>
				<div id="cldColChart" class="cld-chart cld-chart-sm"></div>
				<div class="cld-legend" id="cldColLegend"></div>
			</div>
			<div class="cld-panel">
				<h2>Expense mix</h2>
				<p class="hint">GCN lines + general expense</p>
				<div id="cldExpChart" class="cld-chart cld-chart-sm"></div>
				<div class="cld-legend" id="cldExpLegend"></div>
			</div>
		</div>

		<div class="cld-dock">
			<div class="cld-panel">
				<h2>Still open</h2>
				<p class="hint">Work that blocks cash or delivery close</p>
				<div class="cld-attn" id="cldAttn"></div>
			</div>
			<div class="cld-panel">
				<h2>Latest GCNs</h2>
				<p class="hint">From quarterly transaction tables</p>
				<div class="cld-table-wrap">
					<table class="cld-table">
						<thead>
							<tr><th>GCN</th><th>Client</th><th>Lane</th><th>Status</th><th class="num">Freight</th></tr>
						</thead>
						<tbody id="cldRecentBody"></tbody>
					</table>
				</div>
			</div>
		</div>

		<p class="cld-foot">Live figures from consignments, GST invoices, collections and expenses. Delayed = undelivered and older than 3 days. May go-live through today.</p>
	</div>
</div>
