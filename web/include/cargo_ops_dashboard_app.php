<?php require_once __DIR__ . '/city_coords.php'; ?>
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
				<h1 class="trk-h1">Operations command center</h1>
				<p class="trk-sub">Find it, fix it, move on</p>
			</div>
			<label class="trk-srch">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
				<input type="search" id="trkSearch" placeholder="Search GCN, client or city" aria-label="Search consignments" autocomplete="off">
				<kbd>/</kbd>
			</label>
			<div class="trk-bar-actions">
				<span class="trk-live" id="cldAsOfPill"><i aria-hidden="true"></i><span id="cldAsOf">Live</span></span>
				<div class="trk-seg" id="cldPeriod" role="group" aria-label="Range">
					<button type="button" data-p="weekly">Week</button>
					<button type="button" class="on" data-p="monthly">Month</button>
				</div>
				<div class="year-toggle trk-years" id="cldYears" aria-label="Year"></div>
			</div>
		</header>

		<section class="trk-kpis" id="trkKpis" aria-label="Key figures">
			<button type="button" class="trk-k" style="--c:var(--trk-teal)" data-filter-status="all" title="All bookings">
				<small>Bookings</small><b data-k="bookings">—</b>
			</button>
			<button type="button" class="trk-k trk-k--filter" style="--c:var(--trk-green)" data-filter-status="delivered" title="Filter list: delivered">
				<small>Delivered</small><b data-k="delivered">—</b>
			</button>
			<button type="button" class="trk-k trk-k--filter" style="--c:var(--trk-teal)" data-filter-status="transit" title="Filter list: in transit">
				<small>In transit</small><b data-k="inTransit">—</b>
			</button>
			<button type="button" class="trk-k" style="--c:#6D5BD0" data-filter-status="all" title="Invoices in period">
				<small>Invoices</small><b data-k="invoices">—</b>
			</button>
			<button type="button" class="trk-k" id="trkKCancelled" style="--c:var(--trk-red)" title="Cancelled bookings in this period">
				<small>Cancelled booking</small><b data-k="cancelled">—</b>
			</button>
		</section>

		<div class="trk-stage">
			<section class="trk-map" aria-label="Live routes">
				<div class="trk-map-head">
					<h2>Live routes</h2>
					<p class="trk-map-hint" id="trkMapHint">Select a consignment to plot the route</p>
				</div>
				<svg id="trkMap" viewBox="0 0 600 640" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Map of India with active consignment routes">
					<defs>
						<linearGradient id="trkRg" x1="0" y1="0" x2="1" y2="0">
							<stop offset="0" stop-color="#066F8A"/>
							<stop offset="1" stop-color="#B50E18"/>
						</linearGradient>
						<linearGradient id="gOr" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F58A3C"/><stop offset=".5" stop-color="#E8650C"/><stop offset="1" stop-color="#C9540A"/></linearGradient>
						<linearGradient id="gBl" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5A98EE"/><stop offset=".5" stop-color="#2F6FD0"/><stop offset="1" stop-color="#244FA0"/></linearGradient>
						<linearGradient id="gRd" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F26A7E"/><stop offset=".5" stop-color="#D9304A"/><stop offset="1" stop-color="#A81E34"/></linearGradient>
						<linearGradient id="gYl" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFD26B"/><stop offset=".5" stop-color="#E8A317"/><stop offset="1" stop-color="#C0820B"/></linearGradient>
						<linearGradient id="gWh" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#D5DDEC"/></linearGradient>
						<linearGradient id="gGl" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#4A6A9A"/><stop offset="1" stop-color="#16233D"/></linearGradient>
						<linearGradient id="gBeam" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#FFF1A8" stop-opacity=".6"/><stop offset="1" stop-color="#FFF1A8" stop-opacity="0"/></linearGradient>
					</defs>
					<?php echo ew_india_states_land_markup(); ?>
					<g id="trkCities"></g>
					<g id="trkRoutes"></g>
					<g id="trkDests"></g>
					<g id="trkPin"></g>
				</svg>
				<script>window.EW_CITY_STATE = <?php echo json_encode(ew_city_state_map(), JSON_UNESCAPED_UNICODE); ?>;</script>
				<div class="trk-map-note" id="trkMapNote" hidden>No booked or in-transit routes mapped.</div>
				<div class="trk-map-tip" id="trkMapTip" hidden></div>
				<div class="trk-legend">
					<span><i style="background:#0A8FB0"></i>Origin</span>
					<span><i style="background:#DD111E"></i>Destination</span>
					<span><i style="background:#17A34A"></i>In transit / booked</span>
				</div>
			</section>

			<aside class="trk-side">
				<section class="trk-card trk-card--action" aria-label="Needs action">
					<div class="trk-card-head">
						<h2>Needs action</h2>
						<p class="trk-hint">Exceptions ranked by urgency</p>
					</div>
					<div id="trkNeeds" class="trk-needs"></div>
				</section>

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

		<section class="trk-tape" aria-label="Shipments">
			<div class="trk-tool" id="trkFilters">
				<div class="trk-chips" role="group" aria-label="Filter by status">
					<button type="button" class="trk-chip-btn on" data-f-status="all">All</button>
					<button type="button" class="trk-chip-btn" data-f-status="transit">In transit</button>
					<button type="button" class="trk-chip-btn" data-f-status="delivered">Delivered</button>
					<button type="button" class="trk-chip-btn" data-f-view="need">Needs action</button>
					<button type="button" class="trk-chip-btn" data-f-status="pending">Other</button>
				</div>
				<div class="trk-chips" role="group" aria-label="Filter by mode">
					<button type="button" class="trk-chip-btn on" data-f-mode="all">All modes</button>
					<button type="button" class="trk-chip-btn" data-f-mode="road">Road</button>
					<button type="button" class="trk-chip-btn" data-f-mode="rail">Rail</button>
					<button type="button" class="trk-chip-btn" data-f-mode="air">Air</button>
					<button type="button" class="trk-chip-btn" data-f-mode="sea">Sea</button>
				</div>
				<span style="flex:1"></span>
				<span class="trk-hint" id="trkCount" style="margin:0"></span>
			</div>
			<div class="trk-tw">
				<table class="trk-table">
					<thead>
						<tr>
							<th style="width:36px"><input type="checkbox" id="trkSelectAll" aria-label="Select all"></th>
							<th>GCN</th>
							<th>Mode</th>
							<th>Route</th>
							<th>Client</th>
							<th>Status</th>
							<th>Freight</th>
							<th>Health</th>
						</tr>
					</thead>
					<tbody id="trkTableBody"></tbody>
				</table>
			</div>
			<div class="trk-empty" id="trkTableEmpty" hidden>No consignments match these filters.</div>
		</section>
	</div>

	<div class="trk-sc" id="trkScrim" hidden></div>
	<aside class="trk-drawer" id="trkDrawer" aria-label="Shipment detail" hidden></aside>
</div>
