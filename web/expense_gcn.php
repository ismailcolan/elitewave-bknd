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
			border: 1px solid var(--panel-border, #C5D3E0);
			border-radius: 12px;
			box-shadow: 0 4px 24px rgba(15, 23, 42, .06);
			overflow: hidden;
			margin: 0;
		}

		.egcn-toolbar {
			padding: 24px 28px;
			background: linear-gradient(180deg, var(--panel-head-bg, #F3F7FB) 0%, #fff 100%);
			border-bottom: 1px solid var(--panel-border, #C5D3E0);
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

		.egcn-toolbar-meta-col {
			flex: 1 1 320px;
			min-width: 260px;
			margin-left: auto;
		}

		.egcn-toolbar-meta-empty {
			min-height: 36px;
			padding: 8px 12px;
			border: 1px dashed #cbd5e1;
			border-radius: 8px;
			background: #f8fafc;
			color: #64748b;
			font-size: 12px;
			line-height: 1.4;
			display: flex;
			align-items: center;
		}

		.egcn-toolbar-meta {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			grid-template-rows: auto auto;
			gap: 8px 14px;
			margin: 0;
			padding: 10px 14px;
			background: linear-gradient(135deg, #0A1E3D 0%, #06416F 100%);
			border-radius: 8px;
			border: 1px solid rgba(255, 255, 255, .15);
			font-size: 12px;
		}

		.egcn-toolbar-meta dt {
			font-weight: 700;
			font-size: 10px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: rgba(255, 255, 255, .72);
			margin: 0 0 2px;
		}

		.egcn-toolbar-meta dd {
			margin: 0;
			color: #fff;
			font-weight: 700;
			font-size: 13px;
			line-height: 1.3;
			overflow-wrap: anywhere;
		}

		.egcn-group-bar {
			padding: 0 28px 12px;
			background: linear-gradient(180deg, var(--panel-head-bg, #F3F7FB) 0%, #fff 100%);
			border-bottom: 1px solid var(--panel-border, #C5D3E0);
		}

		.egcn-kpi-strip {
			display: none;
			flex-wrap: wrap;
			align-items: stretch;
			gap: 12px;
			padding: 16px 28px;
			background: #fff;
			border-bottom: 1px solid var(--panel-border, #C5D3E0);
		}

		.egcn-kpi-strip.is-visible {
			display: flex;
		}

		.egcn-kpi-strip .kpi-inline {
			flex: 1 1 140px;
			min-width: 120px;
			background: var(--panel-bg, #FAFCFE);
			border: 1px solid var(--panel-border, #C5D3E0);
			border-radius: 10px;
			padding: 12px 14px;
			text-align: center;
			box-sizing: border-box;
		}

		.egcn-kpi-strip .kpi-inline.kpi-gst {
			border-style: dashed;
		}

		.egcn-kpi-strip .kpi-inline .kpi-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: var(--ew-text-muted, #6B7A8D);
			margin-bottom: 6px;
		}

		.egcn-kpi-strip .kpi-inline .kpi-value {
			font-size: clamp(14px, 0.35vw + 12px, 18px);
			font-weight: 800;
			color: var(--ew-navy, #06416F);
			font-variant-numeric: tabular-nums;
			white-space: nowrap;
		}

		.egcn-kpi-strip .kpi-inline.kpi-profit-positive {
			background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
			border-color: #86efac;
		}

		.egcn-kpi-strip .kpi-inline.kpi-profit-negative {
			background: linear-gradient(180deg, #fef2f2 0%, #fecaca 100%);
			border-color: #fca5a5;
		}

		.egcn-kpi-strip .kpi-inline.kpi-profit-positive .kpi-value { color: #14532d; }
		.egcn-kpi-strip .kpi-inline.kpi-profit-negative .kpi-value { color: #b91c1c; }

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
			flex-direction: column;
			align-items: stretch;
			min-height: 0;
		}

		.egcn-summary-panel {
			flex: none;
			width: 100%;
			max-width: none;
			border-left: none;
			border-bottom: 1px solid var(--panel-border, #C5D3E0);
			background: var(--panel-head-bg, #F3F7FB);
			display: flex;
			flex-direction: column;
		}

		.egcn-summary-panel .egcn-panel-title {
			border-bottom: 1px solid var(--panel-border, #C5D3E0);
			background: var(--rail-bg, #DDE7F0);
			color: var(--ew-navy, #06416F);
		}

		.egcn-summary-panel .egcn-panel-title i {
			color: var(--ew-navy, #06416F);
			opacity: .85;
		}

		.egcn-lines-panel {
			flex: 1 1 auto;
			min-width: 0;
			width: 100%;
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
			padding: 20px 24px 22px;
			background: var(--panel-head-bg, #F3F7FB);
			flex: 1 1 auto;
		}

		.gcn-summary-empty {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 12px;
			padding: 28px 20px;
			border: 1px dashed var(--panel-border, #C5D3E0);
			border-radius: 10px;
			background: var(--panel-bg, #FAFCFE);
			color: var(--ew-text-muted, #6B7A8D);
			font-size: 13px;
			min-height: 88px;
		}

		.gcn-summary-empty i {
			font-size: 22px;
			color: #94a3b8;
		}

		.gcn-summary-loaded {
			margin-bottom: 0;
		}

		.egcn-summary-loaded-wrap {
			display: grid;
			grid-template-columns: 1fr;
			gap: 16px 20px;
			width: 100%;
			align-items: start;
		}

		.egcn-summary-loaded-wrap > .gcn-group-cards {
			grid-column: 1 / -1;
		}

		.egcn-summary-loaded-wrap > #gcn_card {
			min-width: 0;
			width: 100%;
		}

		/* Side-by-side only when both columns can stay readable */
		@media (min-width: 1100px) {
			.egcn-summary-loaded-wrap {
				grid-template-columns: minmax(300px, 400px) minmax(340px, 1fr);
				align-items: stretch;
			}

			.egcn-summary-loaded-wrap > #gcn_card {
				grid-column: 1;
			}

			.egcn-summary-loaded-wrap > .egcn-summary-metrics {
				grid-column: 2;
			}
		}

		.egcn-summary-panel #gcn_summary_loaded {
			width: 100%;
		}

		.egcn-summary-panel .gcn-summary-empty {
			min-height: 120px;
			flex-direction: column;
			text-align: center;
			padding: 24px 16px;
		}

		.egcn-side-meta {
			display: grid;
			grid-template-columns: repeat(2, minmax(120px, 1fr));
			gap: 12px 16px;
			width: 100%;
			min-width: 0;
			margin: 0;
			padding: 16px 18px;
			box-sizing: border-box;
			background: linear-gradient(135deg, #0A1E3D 0%, #06416F 100%);
			border: 1px solid rgba(255, 255, 255, .18);
			border-radius: 10px;
			box-shadow: 0 4px 16px rgba(10, 30, 61, .18);
			font-size: 13px;
		}

		.egcn-side-meta > div {
			min-width: 0;
		}

		.egcn-summary-metrics {
			width: 100%;
			min-width: 0;
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.egcn-side-meta dt {
			font-weight: 700;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: rgba(255, 255, 255, .72);
			margin: 0 0 4px;
		}

		.egcn-side-meta dd {
			margin: 0;
			color: #fff;
			font-weight: 700;
			font-size: 13px;
			line-height: 1.35;
			overflow-wrap: break-word;
			word-break: normal;
		}

		.egcn-side-kpi {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 10px;
			width: 100%;
			min-width: 0;
		}

		.egcn-side-kpi .kpi {
			background: var(--panel-bg, #FAFCFE);
			border: 1px solid var(--panel-border, #C5D3E0);
			border-radius: 10px;
			padding: 12px 14px;
			min-width: 0;
			box-sizing: border-box;
			text-align: center;
			box-shadow: 0 1px 2px rgba(6, 65, 111, .04);
		}

		.egcn-side-kpi .kpi-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: var(--ew-text-muted, #6B7A8D);
			margin-bottom: 6px;
			padding: 0 2px;
		}

		.egcn-side-kpi .kpi-value {
			display: block;
			font-size: clamp(13px, 0.35vw + 12px, 17px);
			font-weight: 800;
			color: var(--ew-navy, #06416F);
			line-height: 1.2;
			padding: 0 8px;
			box-sizing: border-box;
			font-variant-numeric: tabular-nums;
			white-space: nowrap;
		}

		.egcn-side-kpi .kpi.kpi-profit-positive {
			background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
			border-color: #86efac;
		}

		.egcn-side-kpi .kpi.kpi-profit-negative {
			background: linear-gradient(180deg, #fef2f2 0%, #fecaca 100%);
			border-color: #fca5a5;
		}

		.egcn-side-kpi .kpi.kpi-profit-positive .kpi-label {
			color: #166534;
		}

		.egcn-side-kpi .kpi.kpi-profit-negative .kpi-label {
			color: #991b1b;
		}

		.egcn-side-kpi .kpi-value.profit-positive { color: #15803d; }
		.egcn-side-kpi .kpi-value.profit-negative { color: #dc2626; }

		.egcn-side-kpi .kpi.kpi-profit-positive .kpi-value {
			color: #14532d;
		}

		.egcn-side-kpi .kpi.kpi-profit-negative .kpi-value {
			color: #b91c1c;
		}

		.egcn-gst-tracking {
			padding: 12px 14px;
			background: var(--panel-bg, #FAFCFE);
			border: 1px dashed var(--panel-border, #C5D3E0);
			border-radius: 10px;
			text-align: center;
		}

		@media (min-width: 768px) and (max-width: 1099px) {
			.egcn-side-meta {
				grid-template-columns: repeat(3, minmax(120px, 1fr));
			}
		}

		@media (min-width: 1100px) {
			.egcn-side-meta {
				grid-template-columns: repeat(2, minmax(120px, 1fr));
			}
		}

		@media (min-width: 1400px) {
			.egcn-summary-loaded-wrap {
				grid-template-columns: minmax(320px, 850px) minmax(380px, 1fr);
			}

			.egcn-side-meta {
				grid-template-columns: repeat(3, minmax(100px, 1fr));
			}
		}

		@media (max-width: 575px) {
			.egcn-side-meta {
				grid-template-columns: 1fr;
			}
			.egcn-side-kpi {
				grid-template-columns: 1fr;
			}
		}

		.egcn-gst-tracking .label {
			display: block;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: var(--ew-text-muted, #6B7A8D);
			margin-bottom: 4px;
		}

		.egcn-gst-tracking strong {
			font-size: 16px;
			font-weight: 800;
			color: var(--ew-navy, #06416F);
		}

		.gcn-group-cards {
			padding: 0 0 14px;
			background: transparent;
			border: none;
			border-radius: 0;
			flex: 1 1 100%;
			width: 100%;
		}

		.gcn-group-cards .gcn-group-label {
			display: block;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .05em;
			color: var(--ew-text-muted, #6B7A8D);
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
			background: var(--panel-link-hover, #EEF3F8);
			border: 1px solid var(--panel-border, #C5D3E0);
			color: var(--ew-navy, #06416F);
			font-size: 13px;
			font-weight: 700;
			letter-spacing: .02em;
		}

		#expense_lines_table .col-gst-pct {
			width: 96px;
			min-width: 96px;
		}

		#expense_lines_table .col-gst-amt {
			width: 110px;
			min-width: 110px;
		}

		#expense_lines_table .line-gst-amount {
			background: var(--panel-head-bg, #F3F7FB) !important;
			cursor: default;
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
			min-width: 980px;
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
			border-top: 1px solid var(--panel-border, #C5D3E0);
			background: var(--panel-head-bg, #F3F7FB);
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
			.egcn-toolbar-meta-col { flex: 1 1 100%; margin-left: 0; max-width: 100%; }
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
			.egcn-toolbar-meta { grid-template-columns: 1fr; }
			.egcn-kpi-strip .kpi-inline { flex: 1 1 100%; }
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
								<div class="egcn-toolbar-col egcn-toolbar-meta-col">
									<label class="egcn-field-label">GCN details</label>
									<div id="gcn_toolbar_meta_empty" class="egcn-toolbar-meta-empty">
										<span id="gcn_empty_msg">Select a GCN to view shipment details.</span>
									</div>
									<dl class="egcn-toolbar-meta" id="gcn_toolbar_meta" style="display:none;">
										<div><dt>GCN No</dt><dd id="card_grn_no"></dd></div>
										<div><dt>GCN date</dt><dd id="card_grn_date"></dd></div>
										<div><dt>Route</dt><dd id="card_route"></dd></div>
										<div><dt>Mode of transport</dt><dd id="card_mode"></dd></div>
										<div><dt>Consignor</dt><dd id="card_consignor"></dd></div>
										<div><dt>Consignee</dt><dd id="card_consignee"></dd></div>
									</dl>
								</div>
							</div>
						</div>

						<div id="egcn_group_bar" class="egcn-group-bar" style="display:none;">
							<div id="gcn_group_cards" class="gcn-group-cards"></div>
						</div>

						<div id="egcn_kpi_strip" class="egcn-kpi-strip">
							<div class="kpi-inline">
								<div class="kpi-label">Revenue</div>
								<div class="kpi-value" id="sum_revenue">0.00</div>
							</div>
							<div class="kpi-inline">
								<div class="kpi-label">Expenses</div>
								<div class="kpi-value" id="sum_expenses">0.00</div>
							</div>
							<div class="kpi-inline" id="sum_profit_kpi">
								<div class="kpi-label">Profit</div>
								<div class="kpi-value" id="sum_profit">0.00</div>
							</div>
							<div class="kpi-inline kpi-gst">
								<div class="kpi-label">GST Amount</div>
								<div class="kpi-value" id="sum_gst_tracking">0.00</div>
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
															<th class="col-gst-pct">GST %</th>
															<th class="col-gst-amt">GST Amount</th>
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

		var gstPercentRates = [0, 5, 12, 18, 28];

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

		function getCategoryGstPercent(categoryId) {
			var pct = 0;
			$.each(categoryOptions || [], function(i, c) {
				if (String(c.category_id) === String(categoryId)) {
					if (parseInt(c.gst_applicable, 10) === 1) {
						pct = parseFloat(c.gst_percent) || 0;
					}
					return false;
				}
			});
			return pct;
		}

		function gstPercentSelectHtml(selected) {
			var sel = selected !== undefined && selected !== null && selected !== '' ? String(selected) : '0';
			var html = '';
			$.each(gstPercentRates, function(i, rate) {
				var s = String(rate) === sel ? ' selected' : '';
				html += '<option value="' + rate + '"' + s + '>' + rate + '%</option>';
			});
			return html;
		}

		function recalcLineGst($row) {
			if (!$row || !$row.length) {
				return;
			}
			var amt = parseMoney($row.find('.line-amount').val());
			var pct = parseFloat($row.find('.line-gst-percent').val()) || 0;
			var gst = (amt > 0 && pct > 0) ? Math.round(amt * pct / 100 * 100) / 100 : 0;
			$row.find('.line-gst-amount').val(formatMoney(gst));
			updateSummary();
		}

		function sumGstTracking() {
			var total = 0;
			$('#expense_lines_body tr').each(function() {
				total += parseMoney($(this).find('.line-gst-amount').val());
			});
			return total;
		}

		function applyLineTypeDefault($row) {
			if (!$row || !$row.length || $row.data('skip-type-default')) {
				return;
			}
			var catId = getSelectVal($row.find('.line-expense-type'));
			var amt = getCategoryDefaultAmount(catId);
			if (amt > 0) {
				$row.find('.line-amount').val(formatMoney(amt));
			}
			var pct = getCategoryGstPercent(catId);
			if (pct > 0) {
				$row.find('.line-gst-percent').val(String(pct));
			}
			recalcLineGst($row);
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
				'<td class="col-gst-pct"><select class="form-control line-gst-percent">' + gstPercentSelectHtml(data.gst_percent !== undefined ? data.gst_percent : 0) + '</select></td>' +
				'<td class="col-gst-amt"><input type="text" class="form-control text-right line-gst-amount" value="' + escHtml(data.gst_amount || '') + '" readonly tabindex="-1"></td>' +
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
			if (data.gst_percent !== undefined && data.gst_percent !== null && data.gst_percent !== '') {
				$row.find('.line-gst-percent').val(String(data.gst_percent));
			}
			if (data.expense_amount) {
				$row.find('.line-amount').val(data.expense_amount);
				recalcLineGst($row);
			} else if (data.category_id) {
				applyLineTypeDefault($row);
			} else {
				recalcLineGst($row);
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
					expense_amount: $row.find('.line-amount').val(),
					gst_percent: $row.find('.line-gst-percent').val(),
					gst_amount: $row.find('.line-gst-amount').val()
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
			var profitClass = profit >= 0 ? 'profit-positive' : 'profit-negative';
			$('#sum_profit').text(formatMoney(profit)).removeClass('profit-positive profit-negative').addClass(profitClass);
			$('#sum_profit_kpi').removeClass('kpi-profit-positive kpi-profit-negative').addClass('kpi-' + profitClass);
			$('#sum_gst_tracking').text(formatMoney(sumGstTracking()));
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
			$('#gcn_toolbar_meta').hide();
			$('#gcn_toolbar_meta_empty').show();
			$('#egcn_kpi_strip').removeClass('is-visible');
			$('#egcn_group_bar').hide();
			$('#gcn_group_cards').empty();
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
			$('#gcn_toolbar_meta_empty').hide();
			$('#gcn_toolbar_meta').show();
			$('#egcn_group_bar').hide();
			$('#gcn_group_cards').empty();
			$('#egcn_kpi_strip').addClass('is-visible');
			$('#card_grn_no').text(ctx.grn_no || '');
			$('#card_grn_date').text(ctx.grn_date || '');
			$('#card_route').text(ctx.route_label || '—');
			$('#card_mode').text(ctx.mode_of_transport || '—');
			$('#card_consignor').text(ctx.consignor || '—');
			$('#card_consignee').text(ctx.consignee || '—');
			updateSummary();
		}

		function showGroupSummary(ctx) {
			if (!ctx || !ctx.gcns || !ctx.gcns.length) {
				clearSummary();
				return;
			}
			$('#gcn_toolbar_meta_empty').hide();
			$('#gcn_toolbar_meta').hide();
			$('#egcn_kpi_strip').addClass('is-visible');
			$('#gcn_group_cards').html(buildGroupCardsHtml(ctx));
			$('#egcn_group_bar').show();
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
				recalcLineGst($(this).closest('tr'));
			});
			$(document).on('change', '.line-gst-percent', function() {
				recalcLineGst($(this).closest('tr'));
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
