<?php
require_once('include/connect.php');
require_once('include/function.php');
require_once('include/trip_summary_helpers.php');

trip_summary_require_access();
trip_summary_ensure_schema($conn);

$c_date = date('d-m-Y');
$cities = trip_summary_city_options($conn);
$modes = trip_summary_mode_options($conn);
$preselect_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = ($preselect_id > 0);
if (!$is_edit && empty($_GET['create'])) {
	header('Location: trip_summary_list.php');
	exit;
}
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('include/title.php'); ?>
	<?php include('include/css_js.php'); ?>
	<style>
		.main-content.new_dpt_bottom { max-width: none; padding: 20px 16px 24px; }
		.ew-page-v2 .trip-shell { background: #fff; border: none; border-radius: 0; box-shadow: none; overflow: hidden; }
		.trip-toolbar { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px 18px; align-items: end; }
		.trip-field label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 6px; }
		.trip-field label .req { color: #dc2626; }
		.trip-field .form-control { height: 36px; border-radius: 8px; font-size: 13px; }
		.trip-field-wide { grid-column: span 2; }
		#sheet_no_display { background: #f1f5f9; font-weight: 700; color: #0A1E3D; }
		#source_manual_wrap { display: none; margin-top: 8px; }
		.trip-panel-title { padding: 12px 20px; font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e2e8f0; background: #fff; }
		.trip-table-wrap { overflow-x: auto; padding: 16px 20px 8px; }
		.trip-table-wrap-inner { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; }
		#trip_gcn_table { min-width: 1100px; width: 100%; font-size: 13px; margin-bottom: 0; table-layout: fixed; border-collapse: separate; border-spacing: 0; }
		#trip_gcn_table th { font-size: 11px; background: #f8fafc !important; color: #475569 !important; padding: 12px 10px; border: none !important; border-bottom: 2px solid #e2e8f0 !important; vertical-align: middle; font-weight: 600; letter-spacing: .03em; text-transform: uppercase; text-align: center; white-space: nowrap; }
		#trip_gcn_table td { padding: 10px 10px; vertical-align: middle; border-bottom: 1px solid #eef2f7 !important; background: #fff; text-align: center; }
		#trip_gcn_table td.col-left { text-align: left; }
		#trip_gcn_table .col-sl { width: 5%; min-width: 48px; }
		#trip_gcn_table .col-gcn { width: 11%; min-width: 120px; }
		#trip_gcn_table .col-date { width: 9%; min-width: 96px; }
		#trip_gcn_table .col-party { width: 15%; min-width: 130px; }
		#trip_gcn_table .col-pkgs { width: 8%; min-width: 82px; }
		#trip_gcn_table .col-loaded { width: 8%; min-width: 82px; }
		#trip_gcn_table .col-remarks { width: 14%; min-width: 120px; }
		#trip_gcn_table tbody tr:last-child td { border-bottom: none !important; }
		#trip_gcn_table tbody tr:nth-child(even) td { background: #fafbfc; }
		#trip_gcn_table tbody tr:hover td { background: #f8fafc; }
		#trip_gcn_table .col-sl.line-sl { font-weight: 700; color: #94a3b8; }
		#trip_gcn_table input.form-control, #trip_gcn_table textarea.form-control { height: 36px; font-size: 13px; border-radius: 8px; padding: 7px 10px; border-color: #dbeafe; box-shadow: none; width: 100%; }
		#trip_gcn_table input.form-control:focus, #trip_gcn_table textarea.form-control:focus { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
		#trip_gcn_table textarea.form-control { height: 36px; min-height: 36px; resize: vertical; text-align: left; }
		#trip_gcn_table .line-loaded { text-align: center; }
		#trip_gcn_table .trip-cell-readonly { display: block; color: #334155; font-size: 13px; line-height: 1.35; word-break: break-word; text-align: center; }
		#trip_gcn_table .trip-cell-empty { color: #cbd5e1; }
		#trip_gcn_table tr.row-error td { background: #fffafb !important; }
		#trip_gcn_table tr.row-locked input.line-grn { background: #f8fafc; border-color: #e2e8f0; color: #475569; cursor: default; }
		#trip_gcn_table .line-loaded,
		#trip_gcn_table .line-remarks { background: #fff !important; border-color: #dbeafe !important; color: #334155 !important; cursor: text !important; }
		#trip_gcn_table .col-action { width: 124px; min-width: 124px; max-width: 124px; border-left: 1px solid #eef2f7 !important; }
		#trip_gcn_table thead th.col-action { border-left: 1px solid #e2e8f0 !important; }
		#trip_gcn_table .line-actions { display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: nowrap; }
		.act-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; min-width: 32px; flex-shrink: 0; border-radius: 8px; border: 1px solid transparent; padding: 0; font-size: 14px; cursor: pointer; transition: background .15s, color .15s, border-color .15s; background: #fff; }
		.act-btn-edit { color: #ea580c; border-color: #fed7aa; background: #fff7ed; }
		.act-btn-edit:hover { background: #ffedd5; color: #c2410c; border-color: #fdba74; }
		.act-btn-delete { color: #94a3b8; border-color: #e2e8f0; }
		.act-btn-delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
		.act-btn-toggle { color: #2563eb; border-color: #bfdbfe; }
		.act-btn-toggle:hover { background: #eff6ff; color: #1d4ed8; border-color: #93c5fd; }
		.act-btn-toggle.is-minus { color: #64748b; border-color: #e2e8f0; }
		.act-btn-toggle.is-minus:hover { background: #f8fafc; color: #475569; border-color: #cbd5e1; }
		.act-btn:disabled, .act-btn.act-btn-disabled { opacity: .4; cursor: not-allowed; pointer-events: none; }
		.row-msg { font-size: 11px; color: #dc2626; margin-top: 4px; line-height: 1.3; text-align: left; }
		.trip-submit-wrap { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; background: #fff; }
		.trip-submit-wrap .btn-primary { min-width: 240px; height: 42px; font-size: 14px; font-weight: 700; border-radius: 8px; }
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
				<div class="col-md-12">
					<div class="ew-page-v2 ew-page-v2--wide-table">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<a href="trip_summary_list.php" class="ew-back-btn" title="Back to list"><i class="fa fa-arrow-left"></i></a>
								<h1 class="ew-page-title"><?php echo $is_edit ? 'Edit Trip Summary' : 'Trip Summary Sheet'; ?></h1>
							</div>
							<div class="ew-toolbar-right">
								<a href="trip_summary_list.php" class="ew-btn-v2 ew-btn-v2-outline">View List</a>
							</div>
						</div>
						<div class="ew-card">
							<h2 class="ew-card-section-title">Sheet Details</h2>
							<div class="ew-form-body" style="padding:0 24px 24px;">
							<div class="trip-shell">

						<div class="trip-toolbar">
							<div class="trip-field">
								<label>Sheet No.</label>
								<input type="text" id="sheet_no_display" class="form-control" readonly placeholder="Auto on save">
							</div>
							<div class="trip-field">
								<label>Date <span class="req">*</span></label>
								<input type="text" id="sheet_date" class="form-control ew-date-field" value="<?php echo htmlspecialchars($c_date); ?>" readonly data-ew-datepicker="1" data-date-format="dd-mm-yyyy">
							</div>
							<div class="trip-field">
								<label>Origin <span class="req">*</span></label>
								<select id="origin_id" class="form-control">
									<option value="">Select origin</option>
									<?php foreach ($cities as $c): ?>
										<option value="<?php echo (int) $c['city_id']; ?>"><?php echo htmlspecialchars($c['city_name']); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="trip-field">
								<label>Destination <span class="req">*</span></label>
								<select id="destination_id" class="form-control">
									<option value="">Select destination</option>
									<?php foreach ($cities as $c): ?>
										<option value="<?php echo (int) $c['city_id']; ?>"><?php echo htmlspecialchars($c['city_name']); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="trip-field">
								<label>Mode <span class="req">*</span></label>
								<select id="mode_id" class="form-control">
									<option value="">Select mode</option>
									<?php foreach ($modes as $m): ?>
										<option value="<?php echo (int) $m['mode_id']; ?>"><?php echo htmlspecialchars($m['mode_type']); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="trip-field trip-field-wide">
								<label>Source / Transport <span class="req">*</span></label>
								<select id="source_value" class="form-control" disabled>
									<option value="">Select mode first</option>
								</select>
								<div id="source_manual_wrap">
									<input type="text" id="source_manual" class="form-control" placeholder="Enter train / vehicle / vessel name manually" style="margin-top:8px;">
								</div>
							</div>
						</div>

						<div class="trip-panel-title"><i class="fa fa-list"></i> GCN Details</div>
						<div class="trip-table-wrap">
							<div class="trip-table-wrap-inner">
							<table class="table" id="trip_gcn_table">
								<thead>
									<tr>
										<th class="col-sl">Sl.No</th>
										<th class="col-gcn">GCN.No</th>
										<th class="col-date">GCN Date</th>
										<th class="col-party">Consignee</th>
										<th class="col-party">Consignor</th>
										<th class="col-pkgs">No. of Pkgs</th>
										<th class="col-loaded">Loaded No.</th>
										<th class="col-remarks">Remarks</th>
										<th class="col-action">Action</th>
									</tr>
								</thead>
								<tbody id="trip_gcn_body"></tbody>
							</table>
							</div>
						</div>

						<div class="trip-submit-wrap">
							<button type="button" class="btn btn-primary" id="btn_submit"><i class="fa fa-check"></i> <?php echo $is_edit ? 'Update Trip Summary' : 'Create Trip Summary'; ?></button>
							<button type="button" class="btn btn-default-outline" id="btn_print" style="display:none;margin-left:10px;"><i class="fa fa-print"></i> Print</button>
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
		var tripSummaryId = <?php echo (int) $preselect_id; ?>;
		var defaultDate = <?php echo json_encode($c_date, JSON_UNESCAPED_UNICODE); ?>;
		var lineCounter = 0;
		var gcnRows = [];

		function escHtml(v) {
			if (v === null || v === undefined) return '';
			return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}
		function cellText(v) {
			v = $.trim(v || '');
			if (!v) return '<span class="trip-cell-readonly trip-cell-empty">—</span>';
			return '<span class="trip-cell-readonly">' + escHtml(v) + '</span>';
		}
		function rowKey(row) {
			if (!row.trans_table || !row.transaction_id) return '';
			return row.trans_table + '|' + row.transaction_id;
		}
		function otherKeys(excludeRowId) {
			var keys = [];
			$.each(gcnRows, function(i, r) {
				if (r.row_id !== excludeRowId && rowKey(r)) keys.push(rowKey(r));
			});
			return keys;
		}
		function findRow(rowId) {
			for (var i = 0; i < gcnRows.length; i++) {
				if (gcnRows[i].row_id === rowId) return gcnRows[i];
			}
			return null;
		}
		function renumberRows() {
			$('#trip_gcn_body tr').each(function(i) {
				$(this).find('.line-sl').text(i + 1);
				var rid = $(this).data('row-id');
				var row = findRow(rid);
				if (row) row.sort_no = i + 1;
			});
			refreshToggleButtons();
			refreshEditButtons();
		}
		function refreshToggleButtons() {
			var $rows = $('#trip_gcn_body tr');
			var lastIndex = $rows.length - 1;
			$rows.each(function(i) {
				var $btn = $(this).find('.btn-line-toggle');
				var isLast = i === lastIndex;
				if (isLast) {
					$btn.removeClass('is-minus').attr('title', 'Add row');
					$btn.find('i').removeClass('fa-minus').addClass('fa-plus');
				} else {
					$btn.addClass('is-minus').attr('title', 'Clear row');
					$btn.find('i').removeClass('fa-plus').addClass('fa-minus');
				}
			});
		}
		function applyRowLock(row) {
			var $tr = $('#' + row.row_id);
			var locked = !!row.row_locked;
			$tr.toggleClass('row-locked', locked);
			if (row.transaction_id) {
				$tr.find('.line-grn').prop('readonly', locked);
			}
			$tr.find('.line-loaded, .line-remarks').prop('readonly', false);
			var $edit = $tr.find('.btn-line-edit');
			$edit.attr('title', locked ? 'Edit GCN' : 'Lock GCN');
			$edit.find('i').removeClass('fa-pencil fa-check').addClass(locked ? 'fa-pencil' : 'fa-check');
		}
		function refreshEditButtons() {
			$('#trip_gcn_body tr').each(function() {
				var row = findRow($(this).data('row-id'));
				if (!row) return;
				var hasGcn = !!(row.transaction_id && row.grn_no);
				var $edit = $(this).find('.btn-line-edit');
				if (hasGcn) {
					$edit.prop('disabled', false).removeClass('act-btn-disabled');
				} else {
					$edit.prop('disabled', true).addClass('act-btn-disabled').attr('title', 'Enter GCN first');
				}
			});
		}
		function buildRowHtml(row) {
			var cls = row.row_error ? 'row-error' : '';
			if (row.row_locked) cls += (cls ? ' ' : '') + 'row-locked';
			var msgHtml = row.row_msg ? '<div class="row-msg">' + escHtml(row.row_msg) + '</div>' : '';
			var grnReadonly = row.row_locked && row.transaction_id ? ' readonly' : '';
			var editIcon = row.row_locked ? 'fa-pencil' : 'fa-check';
			var editTitle = row.row_locked ? 'Edit GCN' : 'Lock GCN';
			var editDisabled = row.transaction_id ? '' : ' disabled';

			return '<tr id="' + row.row_id + '" class="' + cls + '" data-row-id="' + row.row_id + '">' +
				'<td class="col-sl line-sl"></td>' +
				'<td class="col-left"><input type="text" class="form-control line-grn" value="' + escHtml(row.grn_no) + '" placeholder="Enter GCN No"' + grnReadonly + '>' + msgHtml + '</td>' +
				'<td class="col-date line-date-cell">' + cellText(row.grn_date) + '</td>' +
				'<td class="col-party line-consignee-cell">' + cellText(row.consignee_name) + '</td>' +
				'<td class="col-party line-consignor-cell">' + cellText(row.consignor_name) + '</td>' +
				'<td class="col-pkgs line-pkgs-cell">' + cellText(row.no_of_packages) + '</td>' +
				'<td class="col-loaded"><input type="text" class="form-control line-loaded" value="' + escHtml(row.loaded_no) + '" placeholder="0"></td>' +
				'<td class="col-left"><textarea class="form-control line-remarks" rows="1" placeholder="Optional">' + escHtml(row.remarks) + '</textarea></td>' +
				'<td class="col-action"><div class="line-actions">' +
				'<button type="button" class="act-btn act-btn-edit btn-line-edit' + (editDisabled ? ' act-btn-disabled' : '') + '" title="' + editTitle + '"' + editDisabled + '><i class="fa ' + editIcon + '"></i></button>' +
				'<button type="button" class="act-btn act-btn-delete btn-line-delete" title="Delete row"><i class="fa fa-trash-o"></i></button>' +
				'<button type="button" class="act-btn act-btn-toggle btn-line-toggle" title="Add row"><i class="fa fa-plus"></i></button>' +
				'</div></td></tr>';
		}
		function updateRowDisplayCells(row) {
			var $tr = $('#' + row.row_id);
			$tr.find('.line-date-cell').html(cellText(row.grn_date));
			$tr.find('.line-consignee-cell').html(cellText(row.consignee_name));
			$tr.find('.line-consignor-cell').html(cellText(row.consignor_name));
			$tr.find('.line-pkgs-cell').html(cellText(row.no_of_packages));
			$tr.toggleClass('row-error', !!row.row_msg);
		}
		function renderRow(row) {
			var $existing = $('#' + row.row_id);
			var html = buildRowHtml(row);
			if ($existing.length) {
				$existing.replaceWith(html);
			} else {
				$('#trip_gcn_body').append(html);
			}
			renumberRows();
		}
		function renderAllRows() {
			$('#trip_gcn_body').empty();
			$.each(gcnRows, function(i, row) { renderRow(row); });
		}
		function newRow(data) {
			lineCounter++;
			data = data || {};
			return {
				row_id: 'trip_row_' + lineCounter,
				sort_no: gcnRows.length + 1,
				trans_table: data.trans_table || '',
				transaction_id: data.transaction_id || 0,
				grn_no: data.grn_no || '',
				grn_date: data.grn_date || '',
				consignor_id: data.consignor_id || 0,
				consignee_id: data.consignee_id || 0,
				consignor_name: data.consignor_name || '',
				consignee_name: data.consignee_name || '',
				no_of_packages: data.no_of_packages || '',
				loaded_no: data.loaded_no || '',
				remarks: data.remarks || '',
				row_msg: '',
				row_locked: !!data.row_locked
			};
		}
		function addEmptyRow($insertAfter) {
			var row = newRow({});
			gcnRows.push(row);
			var html = buildRowHtml(row);
			var $row = $(html);
			if ($insertAfter && $insertAfter.length) {
				$insertAfter.after($row);
			} else {
				$('#trip_gcn_body').append($row);
			}
			renumberRows();
			return row;
		}
		function readRowFromDom(row) {
			var $tr = $('#' + row.row_id);
			row.grn_no = $.trim($tr.find('.line-grn').val()).toUpperCase();
			row.loaded_no = $.trim($tr.find('.line-loaded').val());
			row.remarks = $tr.find('.line-remarks').val();
		}
		function clearRowData(row) {
			row.trans_table = '';
			row.transaction_id = 0;
			row.grn_no = '';
			row.grn_date = '';
			row.consignor_id = 0;
			row.consignee_id = 0;
			row.consignor_name = '';
			row.consignee_name = '';
			row.no_of_packages = '';
			row.loaded_no = '';
			row.remarks = '';
			row.row_msg = '';
			row.row_locked = false;
			renderRow(row);
		}
		function fetchGcnForRow(row) {
			readRowFromDom(row);
			var grn = row.grn_no;
			if (!grn) {
				row.row_msg = '';
				row.trans_table = '';
				row.transaction_id = 0;
				row.grn_date = '';
				row.consignor_name = '';
				row.consignee_name = '';
				row.no_of_packages = '';
				updateRowDisplayCells(row);
				$('#' + row.row_id).find('.row-msg').remove();
				return;
			}
			$.getJSON('trip_summary_data.php', {
				cmd: 'lookup_gcn',
				grn_no: grn,
				trip_summary_id: tripSummaryId,
				exclude_keys: JSON.stringify(otherKeys(row.row_id))
			}).done(function(r) {
				if (!r || r.status !== 0) {
					row.row_msg = r && r.message ? r.message : 'GCN not found.';
					row.trans_table = '';
					row.transaction_id = 0;
					row.grn_date = '';
					row.consignor_name = '';
					row.consignee_name = '';
					row.no_of_packages = '';
					renderRow(row);
					return;
				}
				var g = r.gcn;
				row.trans_table = g.trans_table;
				row.transaction_id = g.transaction_id;
				row.grn_no = g.grn_no;
				row.grn_date = g.grn_date;
				row.consignor_id = g.consignor_id;
				row.consignee_id = g.consignee_id;
				row.consignor_name = g.consignor_name;
				row.consignee_name = g.consignee_name;
				row.no_of_packages = g.no_of_packages;
				row.row_msg = '';
				row.row_locked = true;
				renderRow(row);
			}).fail(function() {
				row.row_msg = 'Lookup failed.';
				renderRow(row);
			});
		}
		function loadSources(modeId, selectedValue, manualVal) {
			if (!modeId) {
				$('#source_value').prop('disabled', true).html('<option value="">Select mode first</option>');
				$('#source_manual_wrap').hide();
				return;
			}
			$.getJSON('trip_summary_data.php', { cmd: 'fetch_sources', mode_id: modeId })
				.done(function(r) {
					if (!r || r.status !== 0) return;
					var html = '<option value="">Select source</option>';
					$.each(r.sources || [], function(i, s) {
						html += '<option value="' + escHtml(s.value) + '">' + escHtml(s.label) + '</option>';
					});
					$('#source_value').prop('disabled', false).html(html);
					if (selectedValue) $('#source_value').val(selectedValue);
					toggleSourceManual();
					if (manualVal) $('#source_manual').val(manualVal);
				});
		}
		function toggleSourceManual() {
			var val = $('#source_value').val() || '';
			var show = (val === 'manual:' || val === 'train_preset:2');
			$('#source_manual_wrap').toggle(show);
		}
		function collectLinesPayload() {
			var lines = [];
			var seen = {};
			$('#trip_gcn_body tr').each(function() {
				var grn = $.trim($(this).find('.line-grn').val()).toUpperCase();
				if (!grn) return;
				var loaded = $.trim($(this).find('.line-loaded').val());
				var remarks = $(this).find('.line-remarks').val();
				var rowId = $(this).data('row-id');
				var row = findRow(rowId);
				var key = row ? rowKey(row) : '';
				if (key && seen[key]) return;
				if (key) seen[key] = true;
				lines.push({
					trans_table: row ? row.trans_table : '',
					transaction_id: row ? row.transaction_id : 0,
					grn_no: grn,
					loaded_no: loaded,
					remarks: remarks
				});
			});
			return lines;
		}
		function loadTrip(id) {
			if (!id) {
				gcnRows = [];
				addEmptyRow();
				return;
			}
			$.getJSON('trip_summary_data.php', { cmd: 'fetch', trip_summary_id: id })
				.done(function(r) {
					if (!r || r.status !== 0) {
						alert(r && r.message ? r.message : 'Could not load.');
						return;
					}
					tripSummaryId = parseInt(r.trip_summary_id, 10) || 0;
					$('#sheet_no_display').val(r.sheet_no || '');
					$('#sheet_date').val(r.sheet_date || defaultDate);
					$('#origin_id').val(r.origin_id || '');
					$('#destination_id').val(r.destination_id || '');
					$('#mode_id').val(r.mode_id || '');
					loadSources(r.mode_id, r.source_value, r.source_manual);
					gcnRows = [];
					if (r.lines && r.lines.length) {
						$.each(r.lines, function(i, line) {
							line.row_locked = true;
							gcnRows.push(newRow(line));
						});
					}
					renderAllRows();
					if (!gcnRows.length) addEmptyRow();
					$('#btn_print').toggle(tripSummaryId > 0);
					$('#btn_submit').html('<i class="fa fa-save"></i> Update Trip Summary');
				})
				.fail(function(xhr) {
					alert('Could not load trip summary. Please try again.');
				});
		}

		$(document).ready(function() {
			if (typeof initEwDatepickers === 'function') initEwDatepickers('#sheet_date');
			if (tripSummaryId > 0) loadTrip(tripSummaryId);
			else addEmptyRow();

			$('#mode_id').on('change', function() {
				loadSources($(this).val(), '', '');
				$('#source_manual').val('');
			});
			$('#source_value').on('change', toggleSourceManual);

			$(document).on('blur', '.line-grn', function() {
				var row = findRow($(this).closest('tr').data('row-id'));
				if (!row || row.row_locked) return;
				if (row) fetchGcnForRow(row);
			});

			$(document).on('click', '.btn-line-edit', function() {
				if ($(this).prop('disabled')) return;
				var row = findRow($(this).closest('tr').data('row-id'));
				if (!row || !row.transaction_id) return;
				readRowFromDom(row);
				row.row_locked = !row.row_locked;
				applyRowLock(row);
			});

			$(document).on('click', '.btn-line-toggle', function() {
				var $tr = $(this).closest('tr');
				var row = findRow($tr.data('row-id'));
				if (!row) return;
				if ($(this).hasClass('is-minus')) {
					clearRowData(row);
					return;
				}
				addEmptyRow($tr);
			});

			$(document).on('click', '.btn-line-delete', function() {
				var rowId = $(this).closest('tr').data('row-id');
				var row = findRow(rowId);
				if (!row) return;
				if ((row.grn_no || row.loaded_no || row.remarks) && !confirm('Remove this GCN row?')) return;
				gcnRows = gcnRows.filter(function(r) { return r.row_id !== rowId; });
				$('#' + rowId).remove();
				if (!gcnRows.length) addEmptyRow();
				else renumberRows();
			});

			$('#btn_submit').on('click', function() {
				$('#trip_gcn_body tr').each(function() {
					var row = findRow($(this).data('row-id'));
					if (row) readRowFromDom(row);
				});

				var lines = collectLinesPayload();
				if (!lines.length) {
					alert('Add at least one GCN before submitting.');
					return;
				}
				if (!$('#sheet_date').val() || !$('#origin_id').val() || !$('#destination_id').val() || !$('#mode_id').val()) {
					alert('Please complete all header fields.');
					return;
				}
				if (!$('#source_value').val()) {
					alert('Please select transport source.');
					return;
				}
				var srcVal = $('#source_value').val();
				if ((srcVal === 'manual:' || srcVal === 'train_preset:2') && !$.trim($('#source_manual').val())) {
					alert('Please enter manual source details.');
					return;
				}
				$.ajax({
					url: 'trip_summary_data.php',
					type: 'POST',
					dataType: 'json',
					data: {
						cmd: 'save',
						trip_summary_id: tripSummaryId,
						sheet_date: $('#sheet_date').val(),
						origin_id: $('#origin_id').val(),
						destination_id: $('#destination_id').val(),
						mode_id: $('#mode_id').val(),
						source_value: $('#source_value').val(),
						source_manual: $('#source_manual').val(),
						lines: JSON.stringify(lines)
					},
					success: function(r) {
						if (!r || r.status !== 0) {
							alert(r && r.message ? r.message : 'Submit failed.');
							return;
						}
						alert(r.message + (r.sheet_no ? '\nSheet No: ' + r.sheet_no : ''));
						if (r.trip_summary_id) {
							window.location.href = 'trip_summary.php?id=' + parseInt(r.trip_summary_id, 10);
						}
					},
					error: function() { alert('Submit request failed.'); }
				});
			});

			$('#btn_print').on('click', function() {
				if (tripSummaryId > 0) window.open('trip_summary_print.php?id=' + tripSummaryId, '_blank');
			});
		});
	</script>
</body>

</html>
