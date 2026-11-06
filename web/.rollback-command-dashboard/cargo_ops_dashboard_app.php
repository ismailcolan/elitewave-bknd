<div class="cld-root trk-root" id="cargoOpsRoot">
	<div id="cldBoot" class="trk-boot">
		<?php
		if (!function_exists('ew_skeleton_dashboard')) {
			require_once __DIR__ . '/ew_skeleton.php';
		}
		echo ew_skeleton_dashboard();
		?>
	</div>

	<div id="cldShell" class="trk-wrap" style="display:none;" hidden>
		<header class="trk-bar">
			<div>
				<h1 class="trk-h1">Live tracking</h1>
				<p class="trk-sub">Where your latest consignments are right now</p>
			</div>
			<div class="trk-bar-actions">
				<span class="trk-live" id="cldAsOfPill"><i aria-hidden="true"></i><span id="cldAsOf">Live</span></span>
				<div class="trk-seg" id="cldPeriod" role="group" aria-label="Range">
					<button type="button" data-p="weekly">Week</button>
					<button type="button" class="on" data-p="monthly">Month</button>
				</div>
				<div class="year-toggle trk-years" id="cldYears" aria-label="Year"></div>
			</div>
		</header>

		<div class="trk-stage">
			<section class="trk-map" aria-label="Consignment map">
				<div class="trk-kpis" id="trkKpis">
					<div class="trk-k"><small>Bookings</small><b data-k="bookings">—</b></div>
					<div class="trk-k trk-k--filter" data-filter-status="delivered" title="Filter list: delivered"><small>Delivered</small><b data-k="delivered">—</b></div>
					<div class="trk-k trk-k--filter" data-filter-status="transit" title="Filter list: in transit"><small>In transit</small><b data-k="inTransit">—</b></div>
					<div class="trk-k"><small>Invoices</small><b data-k="invoices">—</b></div>
					<div class="trk-k" id="trkKCancelled"><small>Cancelled booking</small><b data-k="cancelled">—</b></div>
				</div>
				<svg id="trkMap" viewBox="0 0 600 640" preserveAspectRatio="xMidYMax meet" role="img" aria-label="Map of India with the selected route">
					<defs>
						<linearGradient id="trkRg" x1="0" y1="0" x2="1" y2="0">
							<stop offset="0" stop-color="#066F8A"/>
							<stop offset="1" stop-color="#B50E18"/>
						</linearGradient>
					</defs>
					<path id="trkDots" stroke="#4A6488" stroke-width="4.2" stroke-linecap="round" fill="none"></path>
					<g id="trkCities"></g>
					<path id="trkBase" class="trk-route-base"></path>
					<path id="trkPath" class="trk-route"></path>
					<g id="trkPin"></g>
				</svg>
				<div class="trk-map-note" id="trkMapNote" hidden>Location not mapped for this consignment.</div>
				<div class="trk-legend">
					<span><i style="background:#0A8FB0"></i>Origin</span>
					<span><i style="background:#DD111E"></i>Destination</span>
					<span><i style="background:#17A34A"></i>Delivered</span>
				</div>
			</section>

			<aside class="trk-side">
				<section class="trk-card" id="trkLatest" aria-live="polite">
					<div class="trk-eyebrow">
						<span>Latest consignment</span>
						<span id="trkLatestStatus"></span>
					</div>
					<div class="trk-empty" id="trkLatestEmpty" hidden>No consignments in this period.</div>
					<div id="trkLatestBody">
						<div class="trk-gcn" id="trkLatestGcn"></div>
						<span class="trk-mode-pill" id="trkLatestModePill" hidden></span>
						<p class="trk-client" id="trkLatestClient"></p>
						<div class="trk-lane">
							<div><b id="trkLatestOrigin"></b><small>Origin</small></div>
							<span class="trk-tr" aria-hidden="true"></span>
							<div><b id="trkLatestDest"></b><small>Destination</small></div>
						</div>
						<ul class="trk-steps" id="trkTimeline"></ul>
						<div class="trk-meta">
							<span>Freight <b id="trkLatestFreight"></b></span>
							<span id="trkLatestKm"></span>
						</div>
					</div>
				</section>
			</aside>
		</div>

		<section class="trk-tape">
			<h2>Recent consignments <span>Select one to see it on the map · GCN opens booking</span></h2>
			<div class="trk-filters" id="trkFilters">
				<div class="trk-filter-group" role="group" aria-label="Filter by status">
					<button type="button" class="on" data-f-status="all">All</button>
					<button type="button" data-f-status="transit">In transit</button>
					<button type="button" data-f-status="delivered">Delivered</button>
					<button type="button" data-f-status="pending">Other</button>
				</div>
				<div class="trk-filter-group" role="group" aria-label="Filter by mode">
					<button type="button" class="on" data-f-mode="all">All modes</button>
					<button type="button" data-f-mode="air">Air</button>
					<button type="button" data-f-mode="rail">Rail</button>
					<button type="button" data-f-mode="road">Road</button>
					<button type="button" data-f-mode="sea">Sea</button>
				</div>
			</div>
			<div class="trk-rail" id="trkRail"></div>
		</section>
	</div>
</div>
