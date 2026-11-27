<?php

require_once __DIR__ . '/gstr1_helpers.php';

function gstr1_report_script_tag()
{
	$path = dirname(__DIR__) . '/javascripts/gstr1-report.js';
	$ver = is_file($path) ? (string) filemtime($path) : (string) time();
	echo '<script src="javascripts/gstr1-report.js?v=' . htmlspecialchars($ver, ENT_QUOTES, 'UTF-8') . '"></script>';
}

function gstr1_shared_styles()
{
	gstr1_mis_table_styles();
	?>
	<style>
		.ew-page-v2--gstr1 .gstr1-subhead { margin: -6px 0 16px; }
		.gstr1-status-chip { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
		.gstr1-status-chip.is-filed { background:#dcfce7; color:#166534; }
		.gstr1-status-chip.is-not-filed { background:#fef3c7; color:#92400e; }
		.gstr1-header-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px 16px; }
		@media (max-width:900px) { .gstr1-header-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
		.gstr1-metric-list { margin:0; padding:0; list-style:none; }
		.gstr1-metric-list--bordered { border:none; background:transparent; }
		.gstr1-metric-list--bordered li { border:none; background:transparent; }
		.gstr1-metric-row.gstr1-drill-btn { text-decoration:none; }
		.gstr1-metric-row {
			display:flex; justify-content:space-between; align-items:center;
			width:100%; padding:11px 0; margin:0;
			background:transparent; border:none; font-size:14px; line-height:1.5;
			cursor:pointer; text-align:left; text-decoration:none;
		}
		.gstr1-metric-row--sub { padding-left:20px; }
		.gstr1-metric-row-label {
			flex:1;
			color:var(--ew-text,#1A2332);
			font-weight:500;
			text-decoration:none !important;
		}
		.gstr1-metric-row-value {
			color:#06416F;
			font-weight:600;
			font-size:14px;
			font-variant-numeric:tabular-nums;
			min-width:2ch;
			text-align:right;
			margin-left:16px;
			flex-shrink:0;
			text-decoration:underline;
			text-underline-offset:2px;
		}
		.gstr1-metric-row:hover { background:transparent; }
		.gstr1-metric-row:hover .gstr1-metric-row-value { color:#042d4d; }
		.gstr1-metric-row:focus-visible { outline:2px solid #06416F; outline-offset:-2px; }
		.gstr1-metric-footnote { font-size:13px; font-weight:500; color:#64748b; margin:12px 0 0; }
		.gstr1-drill-btn { background:none; border:none; padding:0; color:#06416F; font-weight:600; cursor:pointer; text-decoration:underline; text-underline-offset:2px; }
		.gstr1-drill-btn:focus { outline:2px solid #06416F; outline-offset:2px; }
		.gstr1-drill-btn.num-only { font-variant-numeric:tabular-nums; }
		.gstr1-breadcrumb { display:flex; flex-wrap:wrap; gap:6px; align-items:center; font-size:12px; color:#64748b; margin:0 0 16px; }
		.gstr1-breadcrumb a { color:#06416F; font-weight:600; }
		.gstr1-skeleton { background:linear-gradient(90deg,#eef2f7 25%,#f8fafc 50%,#eef2f7 75%); background-size:200% 100%; animation:gstr1sh 1.2s ease infinite; min-height:88px; border-radius:8px; margin-bottom:16px; }
		@keyframes gstr1sh { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
		.ew-page-v2--gstr1 .ew-card + .ew-card { margin-top:16px; }
		.ew-page-v2--gstr1 #gstr1_header_host,
		.ew-page-v2--gstr1 #gstr1_counts_host,
		.ew-page-v2--gstr1 #gstr1_return_host,
		.ew-page-v2--gstr1 #gstr1_stats_counts_host { margin-top:16px; }
		#gstr1_return_table { min-width:1100px; }
		#gstr1_stats_table { min-width:900px; }
	</style>
	<?php
}

function gstr1_mis_table_styles()
{
	?>
	<style>
		.ew-page-v2--gstr1 .filter-label { display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:#333; }
		.ew-page-v2--gstr1 .required-star { color:#DD111E; font-weight:bold; }
		.ew-page-v2--gstr1 .table-scroll-wrapper { overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling:touch; }
		.ew-page-v2--gstr1 #gstr1_return_table th,
		.ew-page-v2--gstr1 #gstr1_stats_table th,
		.ew-page-v2--gstr1 #gstr1_reg_table th {
			background:var(--rail-bg,#DDE7F0); color:var(--ew-text,#1A2332);
			font-size:11px; font-weight:700; text-transform:uppercase; white-space:nowrap; padding:8px 10px;
		}
		.ew-page-v2--gstr1 #gstr1_return_table td,
		.ew-page-v2--gstr1 #gstr1_stats_table td,
		.ew-page-v2--gstr1 #gstr1_reg_table td {
			font-size:12px; font-weight:500; padding:8px 10px; vertical-align:middle;
		}
		.ew-page-v2--gstr1 #gstr1_reg_table td { white-space:nowrap; }
		.ew-page-v2--gstr1 #gstr1_reg_table td.gstr1-reg-particulars {
			white-space:normal; min-width:200px; max-width:320px; line-height:1.4;
		}
		.ew-page-v2--gstr1 #gstr1_reg_table td.gstr1-reg-type { white-space:normal; }
		.ew-page-v2--gstr1 #gstr1_reg_table a {
			color:#06416F; font-weight:600; text-decoration:none;
		}
		.ew-page-v2--gstr1 #gstr1_reg_table a:hover { text-decoration:underline; text-underline-offset:2px; }
		.ew-page-v2--gstr1 #gstr1_return_table .num,
		.ew-page-v2--gstr1 #gstr1_stats_table .num,
		.ew-page-v2--gstr1 #gstr1_reg_table .num { text-align:right !important; font-variant-numeric:tabular-nums; }
		#gstr1_reg_table { min-width:1080px; width:100%; }
		.ew-page-v2--gstr1 table tfoot tr.gstr1-total-row td,
		.ew-page-v2--gstr1 #gstr1_return_table tfoot td,
		.ew-page-v2--gstr1 #gstr1_stats_table tfoot td,
		.ew-page-v2--gstr1 #gstr1_reg_table tfoot td,
		.ew-page-v2--gstr1 #gstr1_b2b_table tfoot td,
		.ew-page-v2--gstr1 #gstr1_hsn_table tfoot td {
			background:#F4F7FB; border-top:2px solid var(--panel-border,#C5D3E0);
			font-weight:700 !important;
		}
		.ew-page-v2--gstr1 table tfoot tr.gstr1-total-row td strong,
		.ew-page-v2--gstr1 #gstr1_return_table tfoot td strong,
		.ew-page-v2--gstr1 #gstr1_stats_table tfoot td strong,
		.ew-page-v2--gstr1 #gstr1_reg_table tfoot td strong {
			font-weight:700 !important;
		}
		.ew-page-v2--gstr1 #gstr1_return_table tfoot td.num,
		.ew-page-v2--gstr1 #gstr1_stats_table tfoot td.num,
		.ew-page-v2--gstr1 #gstr1_reg_table tfoot td.num,
		.ew-page-v2--gstr1 #gstr1_b2b_table tfoot td.num,
		.ew-page-v2--gstr1 #gstr1_hsn_table tfoot td.num { text-align:right !important; }
		.ew-page-v2--gstr1 .btn1 { margin-top:0 !important; }
		.ew-page-v2--gstr1 .report-count { font-size:13px; font-weight:600; color:#64748B; }
		.gstr1-voucher-type-tag,
		.ew-page-v2--gstr1 .voucher-tag {
			display:inline-block;
			font-size:11px;
			font-weight:600;
			line-height:1.35;
			padding:4px 10px;
			border-radius:6px;
			background:var(--ew-navy,#0A1E3D);
			color:#fff;
			white-space:normal;
			text-align:left;
			vertical-align:middle;
			max-width:220px;
		}
		#gstr1_reg_table .gstr1-voucher-type-tag { font-size:10px; padding:3px 8px; border-radius:5px; max-width:200px; }
		.gstr1-voucher-type-tag { margin:2px 0; }
	</style>
	<?php
}

function gstr1_render_breadcrumb(array $crumbs)
{
	echo '<nav class="gstr1-breadcrumb" aria-label="Breadcrumb">';
	foreach ($crumbs as $i => $c) {
		if ($i > 0) {
			echo '<span aria-hidden="true">/</span>';
		}
		$label = htmlspecialchars((string) ($c['label'] ?? ''), ENT_QUOTES, 'UTF-8');
		$href = (string) ($c['href'] ?? '');
		if ($href !== '' && $i < count($crumbs) - 1) {
			echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
		} else {
			echo '<span>' . $label . '</span>';
		}
	}
	echo '</nav>';
}

function gstr1_period_defaults()
{
	$to = date('d-m-Y', strtotime('last day of this month'));
	$from = date('d-m-Y', strtotime('first day of this month'));
	return array('from' => $from, 'to' => $to);
}

function gstr1_base_query_from_request()
{
	$d = gstr1_period_defaults();
	$gstin = isset($_GET['gstin']) ? gst_tax_normalize_gstin($_GET['gstin']) : '';
	$from = isset($_GET['from_date']) ? trim((string) $_GET['from_date']) : $d['from'];
	$to = isset($_GET['to_date']) ? trim((string) $_GET['to_date']) : $d['to'];
	return array('gstin' => $gstin, 'from_date' => $from, 'to_date' => $to);
}

/** MIS filter card (GSTR-1 + Statistics). */
function gstr1_render_filter_card(array $base, array $gstin_opts, $submit_label = 'Load return', $submit_id = 'gstr1_load')
{
	?>
	<div class="ew-card">
		<div class="ew-card-toolbar ew-mis-filters-head">
			<h2>Report Filters</h2>
			<div class="ew-mis-quick-range" aria-label="Quick date ranges">
				<button type="button" data-range="today">Today</button>
				<button type="button" data-range="week">This week</button>
				<button type="button" class="is-active" data-range="month">This month</button>
				<button type="button" data-range="last_month">Last month</button>
			</div>
		</div>
		<div class="ew-form-body">
			<div class="ew-mis-filter-shell">
				<div class="filter-form-wrap ew-form-grid ew-mis-filter-grid">
					<div class="ew-field span-2">
						<label class="filter-label">GSTIN <span class="required-star">*</span></label>
						<select id="gstr1_gstin" class="form-control">
							<?php foreach ($gstin_opts as $opt) { ?>
								<option value="<?php echo htmlspecialchars($opt['gstin'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $base['gstin'] === $opt['gstin'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="ew-field">
						<label class="filter-label">From Date <span class="required-star">*</span></label>
						<?php echo ew_date_input(array('id' => 'gstr1_from', 'value' => $base['from_date'], 'required' => true, 'readonly' => true)); ?>
					</div>
					<div class="ew-field">
						<label class="filter-label">To Date <span class="required-star">*</span></label>
						<?php echo ew_date_input(array('id' => 'gstr1_to', 'value' => $base['to_date'], 'required' => true, 'readonly' => true)); ?>
					</div>
				</div>
				<div class="ew-mis-filter-footer">
					<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="gstr1_reset_filters">
						<i class="fa fa-undo"></i> Reset
					</button>
					<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn1" id="<?php echo htmlspecialchars($submit_id, ENT_QUOTES, 'UTF-8'); ?>">
						<i class="fa fa-search"></i> <?php echo htmlspecialchars($submit_label, ENT_QUOTES, 'UTF-8'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
	<?php
}