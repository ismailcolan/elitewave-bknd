<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/expense_gcn_helpers.php');

expense_gcn_ensure_schema($conn);
expense_require_admin();

$has_edit = !empty($_GET['gcn_key']) || !empty($_GET['group_id']);
if (!$has_edit && empty($_GET['create'])) {
	header('Location:expense_gcn_list.php');
	exit;
}

$c_date = date('d-m-Y');
$gcn_rows = expense_gcn_fetch_gcns($conn);
$vendor_rows = expense_gcn_vendor_options($conn);
$category_rows = expense_gcn_category_options($conn);
$gcn_count = count($gcn_rows);
$preselect_gcn_key = isset($_GET['gcn_key']) ? trim((string) $_GET['gcn_key']) : '';
$preselect_group_id = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<style>
		.main-content.new_dpt_bottom {
			max-width: none;
			padding: 20px 16px 24px;
		}

		.egcn-page { max-width: none; width: 100%; margin: 0; }

		.egcn-page .ew-card .ew-form-body {
			padding: 16px 20px 20px;
		}

		.egcn-shell {
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			box-shadow: 0 4px 24px rgba(15, 23, 42, .06);
			overflow: hidden;
			margin: 0;
		}

		.egcn-toolbar {
			padding: 24px 28px;
			background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
			border-bottom: 1px solid #e2e8f0;
		}

		.egcn-toolbar-row {
			display: flex;
			flex-wrap: wrap;
			align-items: flex-start;
			gap: 20px 36px;
		}

		.egcn-toolbar-col {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.egcn-field-label {
			display: block;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .05em;
			color: #64748b;
			margin: 0;
			line-height: 16px;
			min-height: 16px;
		}

		.egcn-field-label .req {
			color: #dc2626;
			text-transform: none;
		}

		.egcn-label-note {
			font-weight: 500;
			text-transform: none;
			letter-spacing: normal;
			color: #64748b;
			margin-left: 6px;
			font-size: 11px;
		}

		.egcn-mode-col .pill-group {
			display: inline-flex;
			align-items: center;
			height: 36px;
			box-sizing: border-box;
			background: #e2e8f0;
			border-radius: 8px;
			padding: 3px;
			gap: 3px;
		}

		.egcn-mode-col label {
			margin: 0;
			cursor: pointer;
			font-weight: 600;
			font-size: 13px;
		}

		.egcn-mode-col label span {
			display: flex;
			align-items: center;
			height: 30px;
			padding: 0 14px;
			border-radius: 6px;
			color: #475569;
			transition: all .15s ease;
			line-height: 1;
		}

		.egcn-mode-col label input { position: absolute; opacity: 0; pointer-events: none; }
		.egcn-mode-col label input:checked + span {
			background: #fff;
			color: #0A1E3D;
			box-shadow: 0 1px 4px rgba(15, 23, 42, .12);
		}

		.egcn-mode-col label.disabled span { opacity: .45; cursor: not-allowed; }

		.egcn-gcn-col {
			flex: 0 1 480px;
			max-width: 480px;
			min-width: 300px;
		}

		.egcn-gcn-control { width: 100%; }

		.egcn-gcn-col #gcn_hint {
			display: block;
			margin-top: 6px;
			font-size: 12px;
			color: #64748b;
			line-height: 1.4;
		}

		.egcn-layout {
			display: block;
		}

		.egcn-split {
			display: flex;
			align-items: stretch;
			min-height: 460px;
		}

		.egcn-summary-panel {
			flex: 0 0 360px;
			width: 360px;
			max-width: 360px;
			border-left: 1px solid #e2e8f0;
			background: #f8fafc;
			display: flex;
			flex-direction: column;
		}

		.egcn-summary-panel .egcn-panel-title {
			border-bottom: 1px solid #e2e8f0;
		}

		.egcn-lines-panel {
			flex: 1 1 auto;
			min-width: 0;
			display: flex;
			flex-direction: column;
		}

		.egcn-lines-panel .egcn-lines-section {
			flex: 1 1 auto;
			display: flex;
			flex-direction: column;
		}

		.egcn-lines-panel .egcn-panel-body {
			flex: 1 1 auto;
		}

		.egcn-summary-zone {
			padding: 20px;
			background: #f8fafc;
			flex: 1 1 auto;
		}

		.gcn-summary-empty {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 12px;
			padding: 18px 20px;
			border: 1px dashed #cbd5e1;
			border-radius: 12px;
			background: #fff;
			color: #64748b;
			font-size: 13px;
		}

		.gcn-summary-empty i {
			font-size: 22px;
			color: #94a3b8;
		}

		.gcn-summary-loaded {
			border: 1px solid #dbeafe;
			border-radius: 12px;
			background: #fff;
			overflow: hidden;
			box-shadow: 0 2px 12px rgba(15, 23, 42, .05);
		}

		.egcn-summary-panel .gcn-summary-head {
			flex-direction: column;
			align-items: stretch;
		}

		.egcn-summary-panel .gcn-summary-head .gcn-route-panel {
			flex: none;
			width: 100%;
		}

		.egcn-summary-panel .gcn-kpi-strip {
			grid-template-columns: 1fr;
		}

		.egcn-summary-panel .gcn-kpi {
			border-right: none;
			border-bottom: 1px solid #eef2f7;
			text-align: left;
			padding: 12px 16px;
		}

		.egcn-summary-panel .gcn-kpi:last-child {
			border-bottom: none;
		}

		.egcn-summary-panel #gcn_summary_loaded {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.egcn-summary-panel .gcn-kpi-strip {
			border: 1px solid #eef2f7;
			border-radius: 12px;
			overflow: hidden;
			background: #fff;
		}

		.egcn-summary-panel .gcn-summary-empty {
			min-height: 200px;
			flex-direction: column;
			text-align: center;
			padding: 24px 16px;
		}

		.gcn-summary-head {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			justify-content: space-between;
			gap: 12px 20px;
			padding: 14px 18px;
			background: linear-gradient(135deg, #0A1E3D 0%, #06416F 100%);
			color: #fff;
		}

		.gcn-summary-head .gcn-badge {
			display: flex;
			flex-direction: column;
			gap: 2px;
		}

		.gcn-summary-head .gcn-badge-title {
			font-size: 20px;
			font-weight: 800;
			letter-spacing: .02em;
		}

		.gcn-summary-head .gcn-badge-sub {
			font-size: 13px;
			opacity: .85;
		}

		.gcn-summary-head .gcn-route-panel {
			flex: 1 1 320px;
			min-width: 0;
			padding: 14px 16px;
			background: rgba(255,255,255,.12);
			border: 1px solid rgba(255,255,255,.2);
			border-radius: 10px;
		}

		.gcn-route-panel .route-inline {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: 0;
			font-size: 15px;
			line-height: 1.55;
		}

		.gcn-route-panel .route-inline > .fa-map-marker {
			font-size: 17px;
			opacity: .9;
			flex-shrink: 0;
			margin-right: 8px;
		}

		.gcn-route-panel .route-inline .route-text {
			font-size: 15px;
			font-weight: 700;
			word-break: break-word;
		}

		.gcn-route-panel .route-meta-sep {
			margin: 0 12px;
			opacity: .45;
			font-weight: 300;
			flex-shrink: 0;
			user-select: none;
		}

		.gcn-route-panel .party-flow {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			font-size: 15px;
			font-weight: 600;
			min-width: 0;
		}

		.gcn-route-panel .party-flow .party-arrow {
			opacity: .85;
			font-weight: 700;
		}

		.gcn-route-panel .freight-inline {
			display: inline-flex;
			align-items: baseline;
			gap: 6px;
			min-width: 0;
		}

		.gcn-route-panel .freight-inline .meta-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .05em;
			opacity: .75;
			white-space: nowrap;
		}

		.gcn-route-panel .freight-inline .meta-value {
			font-size: 15px;
			font-weight: 600;
		}

		.gcn-kpi-strip {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 0;
		}

		.gcn-kpi {
			padding: 14px 18px;
			text-align: center;
			border-right: 1px solid #eef2f7;
			background: #fafbfc;
		}

		.gcn-kpi:last-child { border-right: none; }

		.gcn-kpi label {
			display: block;
			font-size: 12px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #64748b;
			margin: 0 0 8px;
			background: none;
		}

		.gcn-kpi strong,
		.gcn-kpi .kpi-value {
			display: block;
			font-size: 22px;
			font-weight: 800;
			color: #0A1E3D;
			line-height: 1.2;
		}

		.gcn-kpi.kpi-profit {
			background: #f8fafc;
		}

		.gcn-kpi.kpi-profit.positive {
			background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%);
		}

		.gcn-kpi.kpi-profit.positive .kpi-value { color: #15803d; }

		.gcn-kpi.kpi-profit.negative {
			background: linear-gradient(180deg, #fef2f2 0%, #fff1f2 100%);
		}

		.gcn-kpi.kpi-profit.negative .kpi-value { color: #dc2626; }

		.gcn-group-cards {
			padding: 12px 16px;
			border-bottom: 1px solid #eef2f7;
			background: linear-gradient(135deg, #0A1E3D 0%, #06416F 100%);
		}

		.gcn-group-cards .gcn-group-label {
			display: block;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .05em;
			color: rgba(255,255,255,.75);
			margin-bottom: 10px;
		}

		.gcn-group-tags {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}

		.gcn-group-tags .gcn-tag {
			display: inline-flex;
			align-items: center;
			padding: 8px 14px;
			border-radius: 8px;
			background: rgba(255,255,255,.14);
			border: 1px solid rgba(255,255,255,.22);
			color: #fff;
			font-size: 15px;
			font-weight: 700;
			letter-spacing: .02em;
		}

		#gcn_keys.select2-container { font-size: 13px; width: 100% !important; max-width: 640px; }

		.egcn-lines-section {
			background: #fff;
		}

		.egcn-panel-title {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 14px 20px;
			font-size: 12px;
			font-weight: 700;
			color: #334155;
			text-transform: uppercase;
			letter-spacing: .05em;
			border-bottom: 1px solid #e2e8f0;
			background: #fff;
		}

		.egcn-panel-title i { margin-right: 8px; color: #64748b; }

		.egcn-panel-body { padding: 20px 24px 24px; }

		.egcn-table-wrap {
			overflow-x: auto;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			background: #fff;
		}

		#expense_lines_table {
			margin-bottom: 0;
			min-width: 800px;
			width: 100%;
			font-size: 13px;
			border-collapse: separate;
			border-spacing: 0;
			table-layout: fixed;
		}

		#expense_lines_table th {
			font-size: 11px;
			white-space: nowrap;
			background: var(--rail-bg, #DDE7F0) !important;
			color: var(--ew-text, #1A2332) !important;
			padding: 14px 12px;
			vertical-align: middle;
			border: none !important;
			font-weight: 700;
			letter-spacing: .03em;
		}

		#expense_lines_table td {
			vertical-align: middle;
			padding: 14px 12px;
			background: #fff;
			border-bottom: 1px solid #eef2f7 !important;
		}

		#expense_lines_table tbody tr:last-child td { border-bottom: none !important; }
		#expense_lines_table tbody tr:nth-child(even) td { background: #fafbfc; }
		#expense_lines_table tbody tr:hover td { background: #f1f5f9; }

		#expense_lines_table .col-sl {
			width: 44px;
			min-width: 44px;
			text-align: center;
			font-weight: 700;
			color: #64748b;
		}

		#expense_lines_table .col-action {
			width: 132px;
			min-width: 132px;
			max-width: 132px;
			text-align: center;
			white-space: nowrap;
			position: sticky;
			right: 0;
			z-index: 2;
			border-left: 1px solid #eef2f7 !important;
			box-shadow: -4px 0 8px rgba(15, 23, 42, .04);
			padding: 8px 10px !important;
			vertical-align: middle !important;
		}

		#expense_lines_table thead th.col-action {
			z-index: 3;
			background: var(--rail-bg, #DDE7F0) !important;
			border-left: 1px solid var(--panel-border, #C5D3E0) !important;
			box-shadow: none;
		}

		#expense_lines_table tbody td.col-action { background: #fff; }
		#expense_lines_table tbody tr:nth-child(even) td.col-action { background: #fafbfc; }
		#expense_lines_table tbody tr:hover td.col-action { background: #f1f5f9; }

		#expense_lines_table td.col-action .line-actions {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			width: 100%;
			min-height: 36px;
			margin: 0;
			padding: 0;
			flex-wrap: nowrap;
		}

		.line-actions {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			flex-wrap: nowrap;
		}

		.act-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 32px;
			height: 32px;
			min-width: 32px;
			flex-shrink: 0;
			border: 1px solid transparent;
			border-radius: 8px;
			padding: 0;
			font-size: 14px;
			line-height: 1;
			cursor: pointer;
			transition: background .15s ease, border-color .15s ease, transform .1s ease, opacity .15s ease;
			vertical-align: middle;
			background: #fff;
		}

		.act-btn:active:not(:disabled) { transform: scale(.96); }
		.act-btn .fa { pointer-events: none; }

		.act-btn-edit { background: #fff7ed; color: #ea580c; border-color: #fed7aa; }
		.act-btn-edit:hover:not(:disabled) { background: #ffedd5; color: #c2410c; border-color: #fdba74; }

		.act-btn-delete { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
		.act-btn-delete:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }

		.act-btn-add { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
		.act-btn-add:hover { background: #dbeafe; color: #1e40af; border-color: #93c5fd; }

		.act-btn:disabled,
		.act-btn.act-btn-disabled {
			opacity: .55;
			cursor: not-allowed;
			transform: none;
			pointer-events: none;
		}

		.act-btn-edit.act-btn-disabled {
			background: #fff7ed;
			color: #fdba74;
			border-color: #fed7aa;
		}

		#expense_lines_table .col-amt { width: 100px; min-width: 100px; }
		#expense_lines_table .col-date { width: 118px; min-width: 118px; }
		#expense_lines_table .col-vendor,
		#expense_lines_table .col-type {
			width: 200px;
			min-width: 200px;
		}

		#expense_lines_table th.col-vendor,
		#expense_lines_table th.col-type,
		#expense_lines_table td.line-select-wrap {
			padding-left: 6px;
			padding-right: 6px;
		}

		#expense_lines_table input.form-control {
			height: 36px;
			font-size: 13px;
			padding: 6px 10px;
			border-radius: 8px;
			border-color: #cbd5e1;
		}

		#expense_lines_table .date-input-inside input { height: 36px; font-size: 13px; }

		#expense_lines_table tr.line-locked input:not(.line-date),
		#expense_lines_table tr.line-locked .select2-choice {
			background: #f1f5f9 !important;
			cursor: default;
		}

		.egcn-panel-footer {
			border-top: 1px solid #e2e8f0;
			background: #f8fafc;
			padding: 20px 24px;
			display: flex;
			justify-content: flex-end;
			align-items: center;
			gap: 14px;
		}

		.egcn-panel-footer .btn {
			border-radius: 8px;
			padding: 10px 24px;
			font-size: 14px;
			font-weight: 600;
			min-height: 42px;
		}

		.egcn-panel-footer .btn-primary { padding-left: 28px; padding-right: 28px; }

		.gcn-route-bar,
		.gcn-card,
		.gcn-card-empty,
		.gcn-card-title,
		.gcn-card-sub,
		.gcn-kv,
		.gcn-summary,
		.gcn-summary-row { display: none; }

		#gcn_key.select2-container { font-size: 13px; width: 100% !important; max-width: 480px; }

		.egcn-gcn-col .select2-container { width: 100% !important; max-width: 480px; }

		@media (max-width: 1200px) {
			.egcn-gcn-col { max-width: 100%; flex: 1 1 100%; min-width: 0; }
			.egcn-gcn-col .select2-container,
			#gcn_key.select2-container { max-width: 100%; }

			.egcn-split {
				flex-direction: column;
				min-height: 0;
			}

			.egcn-summary-panel {
				flex: none;
				width: 100%;
				max-width: none;
				border-left: none;
				border-top: 1px solid #e2e8f0;
			}

			.egcn-summary-panel .gcn-kpi-strip {
				grid-template-columns: repeat(3, minmax(0, 1fr));
			}

			.egcn-summary-panel .gcn-kpi {
				border-bottom: none;
				border-right: 1px solid #eef2f7;
				text-align: center;
			}

			.egcn-summary-panel .gcn-kpi:last-child {
				border-right: none;
			}
		}

		@media (max-width: 768px) {
			.gcn-route-panel .route-inline { flex-direction: column; align-items: flex-start; gap: 6px; }
			.gcn-route-panel .route-meta-sep { display: none; }
			.egcn-summary-panel .gcn-kpi-strip,
			.gcn-kpi-strip { grid-template-columns: 1fr; }
			.egcn-summary-panel .gcn-kpi,
			.gcn-kpi { border-right: none; border-bottom: 1px solid #eef2f7; text-align: left; }
			.egcn-summary-panel .gcn-kpi:last-child,
			.gcn-kpi:last-child { border-bottom: none; }
			.gcn-summary-head { flex-direction: column; align-items: stretch; }
		}
	</style>
</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php require_once('include/header.php');
			require_once('include/menu.php'); ?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12 egcn-page">
					<div class="ew-page-v2">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<a href="expense_gcn_list.php" class="ew-back-btn"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title">Expense against GCN</h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="expense_gcn_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							</div>
						</div>
						<div class="ew-card">
							<div class="ew-form-body">
							<div class="egcn-shell">

						<div class="egcn-toolbar">
							<div class="egcn-toolbar-row">
								<div class="egcn-toolbar-col egcn-mode-col">
									<label class="egcn-field-label">Mode</label>
									<div class="pill-group">
										<label><input type="radio" name="expense_mode" value="single" checked><span>Single GCN</span></label>
										<label><input type="radio" name="expense_mode" value="group"><span>Group GCN</span></label>
									</div>
								</div>
								<div class="egcn-toolbar-col egcn-gcn-col">
									<label class="egcn-field-label" id="gcn_select_label">Select GCN <span class="req">*</span></label>
									<div class="egcn-gcn-control" id="gcn_single_wrap">
										<select id="gcn_key" class="form-control">
											<option value=""></option>
											<?php foreach ($gcn_rows as $gcn) { ?>
												<option value="<?php echo htmlspecialchars($gcn['key'], ENT_QUOTES, 'UTF-8'); ?>">
													<?php echo htmlspecialchars($gcn['label'], ENT_QUOTES, 'UTF-8'); ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<div class="egcn-gcn-control" id="gcn_group_wrap" style="display:none;">
										<select id="gcn_keys" class="form-control" multiple="multiple">
											<?php foreach ($gcn_rows as $gcn) { ?>
												<option value="<?php echo htmlspecialchars($gcn['key'], ENT_QUOTES, 'UTF-8'); ?>">
													<?php echo htmlspecialchars($gcn['label'], ENT_QUOTES, 'UTF-8'); ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<small id="gcn_hint">
										<?php echo $gcn_count > 0 ? ($gcn_count . ' GCN(s) loaded. Type to search.') : 'No GCNs found.'; ?>
									</small>
								</div>
							</div>
						</div>

						<div class="egcn-layout">
							<div class="egcn-split">
								<div class="egcn-lines-panel">
									<div class="egcn-lines-section">
										<div class="egcn-panel-title"><i class="fa fa-list"></i> Expense Lines</div>
										<div class="egcn-panel-body">
											<div class="egcn-table-wrap">
												<table class="table" id="expense_lines_table">
													<thead>
														<tr>
															<th class="col-sl">Sl</th>
															<th class="col-vendor">Vendor</th>
															<th class="col-type">Expense Type</th>
															<th class="col-date">Date</th>
															<th class="col-amt">Exp.Amount</th>
															<th class="col-action">Action</th>
														</tr>
													</thead>
													<tbody id="expense_lines_body"></tbody>
												</table>
											</div>
										</div>

										<div class="egcn-panel-footer">
											<button type="button" class="btn btn-primary" id="btn_save"><i class="fa fa-save"></i> Save Expenses</button>
										</div>
									</div>
								</div>

								<aside class="egcn-summary-panel">
									<div class="egcn-panel-title"><i class="fa fa-truck"></i> GCN Summary</div>
									<div class="egcn-summary-zone">
										<div id="gcn_card_empty" class="gcn-summary-empty">
											<i class="fa fa-truck"></i>
											<span id="gcn_empty_msg">Select a GCN above to load route details and profit summary.</span>
										</div>
										<div id="gcn_summary_loaded" style="display:none;">
											<div id="gcn_card" class="gcn-summary-loaded">
												<div class="gcn-summary-head">
													<div class="gcn-badge">
														<span class="gcn-badge-title" id="card_grn_no"></span>
														<span class="gcn-badge-sub" id="card_grn_date"></span>
													</div>
													<div class="gcn-route-panel">
														<div class="route-inline">
															<i class="fa fa-map-marker"></i>
															<span class="route-text" id="card_route"></span>
															<span class="route-meta-sep">|</span>
															<span class="party-flow">
																<span id="card_consignor"></span>
																<span class="party-arrow">→</span>
																<span id="card_consignee"></span>
															</span>
															<span class="route-meta-sep">|</span>
															<span class="freight-inline">
																<span class="meta-label">Freight</span>
																<span class="meta-value" id="card_freight">0.00</span>
															</span>
														</div>
													</div>
												</div>
											</div>
											<div id="gcn_group_cards" class="gcn-group-cards" style="display:none;"></div>
											<div class="gcn-kpi-strip">
												<div class="gcn-kpi">
													<label>Revenue</label>
													<strong id="sum_revenue">0.00</strong>
												</div>
												<div class="gcn-kpi">
													<label>Expenses</label>
													<strong id="sum_expenses">0.00</strong>
												</div>
												<div class="gcn-kpi kpi-profit" id="sum_profit_row">
													<label>Profit</label>
													<span class="kpi-value" id="sum_profit">0.00</span>
												</div>
											</div>
										</div>
									</div>
								</aside>
							</div>
						</div>
							</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php require_once('include/footer.php'); ?>
	</div>

	<script type="text/javascript">
		var vendorOptions = <?php echo json_encode($vendor_rows, JSON_UNESCAPED_UNICODE); ?>;
		var categoryOptions = <?php echo json_encode($category_rows, JSON_UNESCAPED_UNICODE); ?>;
		var gcnContext = null;
		var lineCounter = 0;
		var expenseMode = 'single';
		var gcnExpenseId = 0;

		function escHtml(v) {
			if (v === null || v === undefined) return '';
			return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		function parseMoney(v) {
			if (v === null || v === undefined || v === '') return 0;
			return parseFloat(String(v).replace(/,/g, '')) || 0;
		}

		function formatMoney(v) {
			var n = parseMoney(v);
			var abs = Math.abs(n);
			var formatted = abs.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
			return n < 0 ? ('-' + formatted) : formatted;
		}

		function getCategoryDefaultAmount(categoryId) {
			var amt = 0;
			$.each(categoryOptions || [], function(i, c) {
				if (String(c.category_id) === String(categoryId)) {
					amt = parseMoney(c.default_amount);
					return false;
				}
			});
			return amt;
		}

		function applyLineTypeDefault($row) {
			if (!$row || !$row.length || $row.data('skip-type-default')) {
				return;
			}
			var amt = getCategoryDefaultAmount(getSelectVal($row.find('.line-expense-type')));
			if (amt > 0) {
				$row.find('.line-amount').val(formatMoney(amt));
				updateSummary();
			}
		}

		function vendorSelectHtml(selected) {
			var html = '<option value=""></option>';
			$.each(vendorOptions, function(i, v) {
				var sel = String(selected) === String(v.vendor_id) ? ' selected' : '';
				html += '<option value="' + escHtml(v.vendor_id) + '"' + sel + '>' + escHtml(v.label) + '</option>';
			});
			return html;
		}

		function expenseTypeSelectHtml(selected) {
			var html = '<option value=""></option>';
			$.each(categoryOptions, function(i, c) {
				var sel = String(selected) === String(c.category_id) ? ' selected' : '';
				html += '<option value="' + escHtml(c.category_id) + '"' + sel + '>' + escHtml(c.label || c.category_code) + '</option>';
			});
			return html;
		}

		function getSelectVal($el) {
			if (!$el || !$el.length) return '';
			var val = $el.val();
			if ($el.data('select2')) {
				val = $el.select2('val');
			}
			if ($.isArray(val)) {
				val = val[0] || '';
			}
			return val ? String(val) : '';
		}

		function setSelectVal($el, value) {
			if (!$el || !$el.length) return;
			if ($el.data('select2')) {
				$el.select2('val', value || '');
			} else {
				$el.val(value || '');
			}
		}

		function initSelect2($el, placeholder) {
			if ($el.data('select2')) {
				$el.select2('destroy');
			}
			$el.select2({
				width: '100%',
				placeholder: placeholder || 'Select',
				allowClear: true,
				minimumResultsForSearch: 0
			});
		}

		function syncSelectTitle($select) {
			if (!$select || !$select.length) return;
			var text = $.trim($select.find('option:selected').text());
			$select.next('.select2-container').find('.select2-chosen').attr('title', text || '');
		}

		function initLineSelects($row) {
			var $vendor = $row.find('.line-vendor');
			var $type = $row.find('.line-expense-type');
			initSelect2($vendor, 'Select vendor');
			initSelect2($type, 'Select expense type');
			syncSelectTitle($vendor);
			syncSelectTitle($type);
		}

		function setRowLocked($row, locked) {
			$row.toggleClass('line-locked', locked);
			$row.find('input').not('.line-date').prop('readonly', locked);
			$row.find('.line-date').prop('readonly', locked);
			$row.find('select').each(function() {
				var $sel = $(this);
				$sel.prop('disabled', locked);
				if ($sel.data('select2')) {
					if (locked) {
						$sel.select2('enable', false);
					} else {
						$sel.select2('enable', true);
					}
				}
			});
			var $editBtn = $row.find('.btn-line-edit');
			if (locked) {
				$editBtn.attr('title', 'Edit').find('i').removeClass('fa-check').addClass('fa-pencil');
			} else {
				$editBtn.attr('title', 'Done').find('i').removeClass('fa-pencil').addClass('fa-check');
			}
			refreshActionButtons();
		}

		function refreshActionButtons() {
			var $rows = $('#expense_lines_body tr');
			var lastIndex = $rows.length - 1;
			$rows.each(function(i) {
				var $row = $(this);
				var isSaved = !!$row.data('line-id');
				var isLast = i === lastIndex;
				var $editBtn = $row.find('.btn-line-edit');
				var $addBtn = $row.find('.btn-line-add');
				var $delBtn = $row.find('.btn-line-delete');

				$editBtn.show();
				if (isSaved) {
					$editBtn.prop('disabled', false).removeClass('act-btn-disabled').attr('title', 'Edit');
				} else {
					$editBtn.prop('disabled', true).addClass('act-btn-disabled').attr('title', 'Save first to edit');
				}

				$delBtn.show();
				$addBtn.toggle(isLast);
			});
		}

		function addExpenseLine(data, $insertAfter) {
			lineCounter++;
			var rowId = 'line_' + lineCounter;
			data = data || {};
			var expenseDate = data.expense_date || '<?php echo $c_date; ?>';
			var lineId = data.line_id ? String(data.line_id) : '';
			var startLocked = lineId !== '';
			var html = '<tr id="' + rowId + '"' + (lineId ? ' data-line-id="' + escHtml(lineId) + '"' : '') + '>' +
				'<td class="col-sl line-sl"></td>' +
				'<td class="line-select-wrap col-vendor"><select class="form-control line-vendor">' + vendorSelectHtml(data.vendor_id || '') + '</select></td>' +
				'<td class="line-select-wrap col-type"><select class="form-control line-expense-type">' + expenseTypeSelectHtml(data.category_id || '') + '</select></td>' +
				'<td class="col-date"><div class="date-input-inside"><input type="text" class="form-control ew-date-field line-date" value="' + escHtml(expenseDate) + '" readonly data-ew-datepicker="1" data-date-format="dd-mm-yyyy"></div></td>' +
				'<td class="col-amt"><input type="text" class="form-control text-right line-amount" value="' + escHtml(data.expense_amount || '') + '"></td>' +
				'<td class="col-action"><div class="line-actions">' +
				'<button type="button" class="act-btn act-btn-edit btn-line-edit" title="Edit"><i class="fa fa-pencil"></i></button>' +
				'<button type="button" class="act-btn act-btn-delete btn-line-delete" title="Delete"><i class="fa fa-trash-o"></i></button>' +
				'<button type="button" class="act-btn act-btn-add btn-line-add" title="Add row"><i class="fa fa-plus"></i></button>' +
				'</div></td>' +
				'</tr>';
			var $row = $(html);
			if ($insertAfter && $insertAfter.length) {
				$insertAfter.after($row);
			} else {
				$('#expense_lines_body').append($row);
			}
			initLineSelects($row);
			$row.data('skip-type-default', true);
			if (data.vendor_id) {
				setSelectVal($row.find('.line-vendor'), data.vendor_id);
				syncSelectTitle($row.find('.line-vendor'));
			}
			if (data.category_id) {
				setSelectVal($row.find('.line-expense-type'), data.category_id);
				syncSelectTitle($row.find('.line-expense-type'));
			}
			$row.data('skip-type-default', false);
			if (data.expense_amount) {
				$row.find('.line-amount').val(data.expense_amount);
			} else if (data.category_id) {
				applyLineTypeDefault($row);
			}
			if (typeof initEwDatepickers === 'function') {
				initEwDatepickers('#' + rowId);
			}
			if (startLocked) {
				setRowLocked($row, true);
			} else {
				refreshActionButtons();
			}
			renumberLines();
			updateSummary();
		}

		function renumberLines() {
			$('#expense_lines_body tr').each(function(i) {
				$(this).find('.line-sl').text(i + 1);
			});
			refreshActionButtons();
		}

		function collectLinesFromTable() {
			var lines = [];
			$('#expense_lines_body tr').each(function() {
				var $row = $(this);
				lines.push({
					line_id: $row.data('line-id') || '',
					vendor_id: getSelectVal($row.find('.line-vendor')),
					category_id: getSelectVal($row.find('.line-expense-type')),
					expense_date: $row.find('.line-date').val(),
					expense_amount: $row.find('.line-amount').val()
				});
			});
			return lines;
		}

		function sumExpensesWithoutGst() {
			var total = 0;
			$('#expense_lines_body tr').each(function() {
				total += parseMoney($(this).find('.line-amount').val());
			});
			return total;
		}

		function updateSummary() {
			var revenue = gcnContext ? parseMoney(gcnContext.revenue_without_gst_raw) : 0;
			var expenses = sumExpensesWithoutGst();
			var profit = revenue - expenses;
			$('#sum_revenue').text(formatMoney(revenue));
			$('#sum_expenses').text(formatMoney(expenses));
			$('#sum_profit').text(formatMoney(profit));
			var $row = $('#sum_profit_row');
			$row.removeClass('positive negative').addClass(profit >= 0 ? 'positive' : 'negative');
		}

		function getMultiSelectVal($el) {
			if (!$el || !$el.length) return [];
			var val = $el.val();
			if ($el.data('select2')) {
				val = $el.select2('val');
			}
			if (!val) return [];
			if (!$.isArray(val)) val = [val];
			return val.filter(function(v) { return v; });
		}

		function setMultiSelectVal($el, values) {
			if (!$el || !$el.length) return;
			values = values || [];
			if ($el.data('select2')) {
				$el.select2('val', values);
			} else {
				$el.val(values);
			}
		}

		function getExpenseMode() {
			return $('input[name="expense_mode"]:checked').val() || 'single';
		}

		function isGroupMode() {
			return getExpenseMode() === 'group';
		}

		function updateModeUI() {
			expenseMode = getExpenseMode();
			var group = isGroupMode();
			$('#gcn_single_wrap').toggle(!group);
			$('#gcn_group_wrap').toggle(group);
			$('#gcn_select_label').html(group
				? 'Select GCNs <span class="req">*</span> <small class="egcn-label-note">Minimum 2</small>'
				: 'Select GCN <span class="req">*</span>');
			$('#gcn_hint').text(group
				? 'Select 2 or more GCNs to share one expense pool.'
				: <?php echo json_encode($gcn_count > 0 ? ($gcn_count . ' GCN(s) loaded. Type to search.') : 'No GCNs found.'); ?>);
			$('#gcn_empty_msg').text(group
				? 'Select 2 or more GCNs above to load route details and profit summary.'
				: 'Select a GCN above to load route details and profit summary.');
		}

		function clearSummary() {
			gcnContext = null;
			$('#gcn_summary_loaded').hide();
			$('#gcn_card_empty').show();
			$('#gcn_card').hide();
			$('#gcn_group_cards').hide().empty();
		}

		function buildGroupCardsHtml(ctx) {
			var count = (ctx && ctx.gcns) ? ctx.gcns.length : 0;
			var html = '<span class="gcn-group-label">Group GCNs (' + count + ')</span><div class="gcn-group-tags">';
			$.each(ctx.gcns, function(i, g) {
				html += '<span class="gcn-tag">' + escHtml(g.grn_no || '') + '</span>';
			});
			html += '</div>';
			return html;
		}

		function showGcnCard(ctx) {
			if (!ctx) {
				clearSummary();
				return;
			}
			$('#gcn_card_empty').hide();
			$('#gcn_summary_loaded').show();
			$('#gcn_card').show();
			$('#gcn_group_cards').hide().empty();
			$('#card_grn_no').text(ctx.grn_no || '');
			$('#card_grn_date').text(ctx.grn_date || '');
			$('#card_route').text(ctx.route_label || '—');
			$('#card_consignor').text(ctx.consignor || '—');
			$('#card_consignee').text(ctx.consignee || '—');
			$('#card_freight').text(ctx.freight_without_gst || '0.00');
			updateSummary();
		}

		function showGroupSummary(ctx) {
			if (!ctx || !ctx.gcns || !ctx.gcns.length) {
				clearSummary();
				return;
			}
			$('#gcn_card_empty').hide();
			$('#gcn_summary_loaded').show();
			$('#gcn_card').hide();
			$('#gcn_group_cards').html(buildGroupCardsHtml(ctx)).show();
			updateSummary();
		}

		function applyContextResponse(r, reloadLines) {
			if (!r || r.status !== 0) {
				alert(r && r.message ? r.message : 'Could not load GCN details.');
				return;
			}
			gcnContext = r.context;
			gcnExpenseId = parseInt(r.gcn_expense_id, 10) || 0;
			if (isGroupMode()) {
				showGroupSummary(gcnContext);
				if (r.gcn_keys && r.gcn_keys.length) {
					var currentKeys = getMultiSelectVal($('#gcn_keys'));
					var sameKeys = currentKeys.length === r.gcn_keys.length;
					if (sameKeys) {
						for (var ki = 0; ki < currentKeys.length; ki++) {
							if (currentKeys[ki] !== r.gcn_keys[ki]) {
								sameKeys = false;
								break;
							}
						}
					}
					if (!sameKeys) {
						$('#gcn_keys').off('change.egcn');
						setMultiSelectVal($('#gcn_keys'), r.gcn_keys);
						$('#gcn_keys').on('change.egcn', function() {
							loadGroupContext(getMultiSelectVal($('#gcn_keys')), 0);
						});
					}
				}
			} else {
				showGcnCard(gcnContext);
			}
			if (reloadLines !== false) {
				$('#expense_lines_body').empty();
				if (r.lines && r.lines.length) {
					$.each(r.lines, function(i, line) { addExpenseLine(line); });
				} else {
					addExpenseLine();
				}
			}
		}

		function loadGcnContext(key) {
			if (!key) {
				gcnExpenseId = 0;
				clearSummary();
				$('#expense_lines_body').empty();
				addExpenseLine();
				return;
			}
			$.getJSON('expense_gcn_data.php', { cmd: 'fetch_context', expense_mode: 'single', gcn_key: key })
				.done(function(r) { applyContextResponse(r, true); })
				.fail(function() { alert('Could not load GCN details. Please refresh and try again.'); });
		}

		function loadGroupContext(keys, groupId) {
			keys = keys || [];
			groupId = parseInt(groupId, 10) || 0;
			if (groupId <= 0 && keys.length < 2) {
				gcnExpenseId = 0;
				clearSummary();
				if (keys.length === 0) {
					$('#expense_lines_body').empty();
					addExpenseLine();
				}
				return;
			}
			var params = { cmd: 'fetch_context', expense_mode: 'group' };
			if (groupId > 0) {
				params.group_id = groupId;
			} else {
				params.gcn_keys = JSON.stringify(keys);
			}
			$.getJSON('expense_gcn_data.php', params)
				.done(function(r) { applyContextResponse(r, true); })
				.fail(function() { alert('Could not load group GCN details. Please refresh and try again.'); });
		}

		function initGcnSelect() {
			initSelect2($('#gcn_key'), 'Search / Select GCN');
			$('#gcn_key').off('change.egcn').on('change.egcn', function() {
				loadGcnContext(getSelectVal($('#gcn_key')));
			});
		}

		function initGcnMultiSelect() {
			if ($('#gcn_keys').data('select2')) {
				$('#gcn_keys').select2('destroy');
			}
			$('#gcn_keys').select2({
				width: '100%',
				placeholder: 'Search / Select GCNs (min 2)',
				allowClear: true,
				minimumResultsForSearch: 0
			});
			$('#gcn_keys').off('change.egcn').on('change.egcn', function() {
				loadGroupContext(getMultiSelectVal($('#gcn_keys')), 0);
			});
		}

		$(document).ready(function() {
			var preselectKey = <?php echo json_encode($preselect_gcn_key, JSON_UNESCAPED_UNICODE); ?>;
			var preselectGroupId = <?php echo (int) $preselect_group_id; ?>;

			updateModeUI();
			initGcnSelect();
			initGcnMultiSelect();

			if (preselectGroupId > 0) {
				$('input[name="expense_mode"][value="group"]').prop('checked', true);
				updateModeUI();
				loadGroupContext([], preselectGroupId);
			} else if (preselectKey) {
				setSelectVal($('#gcn_key'), preselectKey);
				loadGcnContext(preselectKey);
			} else {
				addExpenseLine();
			}

			$('input[name="expense_mode"]').on('change', function() {
				gcnExpenseId = 0;
				updateModeUI();
				clearSummary();
				$('#expense_lines_body').empty();
				addExpenseLine();
			});

			$(document).on('click', '.btn-line-add', function() {
				addExpenseLine({}, $(this).closest('tr'));
			});

			$(document).on('click', '.btn-line-edit', function() {
				var $row = $(this).closest('tr');
				if ($(this).prop('disabled')) {
					return;
				}
				if ($row.hasClass('line-locked')) {
					setRowLocked($row, false);
				} else {
					setRowLocked($row, true);
				}
			});

			$(document).on('click', '.btn-line-delete', function() {
				if ($('#expense_lines_body tr').length <= 1) {
					alert('At least one expense line is required.');
					return;
				}
				var $row = $(this).closest('tr');
				if ($row.data('line-id')) {
					if (!confirm('Delete this expense line?')) {
						return;
					}
				}
				$row.remove();
				renumberLines();
				updateSummary();
			});

			$(document).on('change', '.line-vendor, .line-expense-type', function() {
				syncSelectTitle($(this));
				if ($(this).hasClass('line-expense-type')) {
					applyLineTypeDefault($(this).closest('tr'));
				}
			});

			$(document).on('change keyup', '.line-amount', function() {
				updateSummary();
			});

			$('#btn_save').on('click', function() {
				var payload = {
					cmd: 'save',
					expense_mode: getExpenseMode(),
					lines: JSON.stringify(collectLinesFromTable())
				};

				if (isGroupMode()) {
					var keys = getMultiSelectVal($('#gcn_keys'));
					if (keys.length < 2) {
						alert('Please select at least 2 GCNs for group mode.');
						return;
					}
					payload.gcn_keys = JSON.stringify(keys);
					if (gcnExpenseId > 0) {
						payload.gcn_expense_id = gcnExpenseId;
					}
				} else {
					var key = getSelectVal($('#gcn_key'));
					if (!key) {
						alert('Please select a GCN.');
						return;
					}
					payload.gcn_key = key;
				}

				$.ajax({
					url: 'expense_gcn_data.php',
					type: 'POST',
					dataType: 'json',
					data: payload,
					success: function(r) {
						if (!r || r.status !== 0) {
							alert(r && r.message ? r.message : 'Save failed.');
							return;
						}
						window.location.href = 'expense_gcn_list.php';
					},
					error: function() { alert('Save request failed.'); }
				});
			});
		});
	</script>
</body>

</html>
