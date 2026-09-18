<?php
require_once("include/connect.php");
require_once("include/function.php");
$mapping_clients = booking_client_name_options($conn);
$mapping_clients_json = json_encode($mapping_clients, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
if ($mapping_clients_json === false) {
	$mapping_clients_json = '[]';
}
$edit_customer_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$page_title = $edit_customer_id > 0 ? 'Edit Customer Mapping' : 'Create Customer Mapping';
?>
<!DOCTYPE html>
<html>
<head>
	<?php include("include/title.php"); ?>
	<?php include("include/css_js.php"); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
</head>
<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once("include/header.php");
			require_once("include/menu.php");
			?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2 ew-page-v2--mapping">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
							</div>
						</div>

						<form id="form_data">
							<input type="hidden" id="form_name" name="form_name" value="add_customer_mapping">
							<input type="hidden" id="edit_id" name="edit_id" value="">
							<input type="hidden" name="del_id" id="del_id">
							<input type="hidden" name="new_id" id="new_id">
							<input type="hidden" name="client" id="client" value="<?php echo $edit_customer_id > 0 ? $edit_customer_id : ''; ?>">

							<div class="ew-card">
								<div class="ew-card-toolbar">
									<h2>Map Consignees</h2>
								</div>
								<div class="ew-form-body">
									<div class="ew-form-grid ew-mapping-grid">
										<div class="ew-field">
											<label>Customer <span class="req">*</span></label>
											<select class="form-control party-select" id="client_name" data-placeholder="Select customer"<?php echo $edit_customer_id > 0 ? ' disabled' : ''; ?>>
												<option value="">Select customer</option>
												<?php foreach ($mapping_clients as $copt) {
													$sel = ((int) $copt['id'] === $edit_customer_id) ? ' selected' : '';
													echo '<option value="' . (int) $copt['id'] . '"' . $sel . '>' . htmlspecialchars($copt['name']) . '</option>';
												} ?>
											</select>
										</div>
										<div class="ew-field">
											<label>Select Consignee <span class="req">*</span></label>
											<select class="form-control party-select" id="client_mapping_name" data-placeholder="Select consignee" disabled>
												<option value="">Select consignee</option>
												<?php foreach ($mapping_clients as $copt) { ?>
													<option value="<?php echo (int) $copt['id']; ?>"><?php echo htmlspecialchars($copt['name']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<p class="ew-mapping-hint">Mapping works both ways: if Customer A is mapped to Consignee B, bookings can go A → B or B → A. Same company can also be mapped to itself for a different branch.</p>
									<div id="msg" class="ew-mapping-msg"></div>
								</div>
							</div>

							<div class="ew-card ew-erp-list" id="table_div" style="<?php echo $edit_customer_id > 0 ? '' : 'display:none;'; ?>">
								<div class="ew-card-toolbar">
									<h2>Mapped Consignees</h2>
									<div class="ew-toolbar-right">
										<span class="ew-mapping-count" id="map_count">0 consignees</span>
									</div>
								</div>
								<div class="ew-table-wrap">
									<table class="table ew-table" cellpadding="0" cellspacing="0">
										<thead>
											<tr>
												<th width="80" class="col-center">S.No</th>
												<th>Consignee</th>
												<th width="90" class="col-actions">Action</th>
											</tr>
										</thead>
										<tbody id="table_data"></tbody>
									</table>
								</div>
								<div class="ew-mapping-empty" id="map_empty" style="display:none;">No consignees mapped yet. Choose a consignee above to add one.</div>
							</div>

							<div class="ew-card">
								<div class="ew-form-footer">
									<a href="customer_mapping.php" class="ew-btn-v2 ew-btn-v2-outline">Cancel</a>
									<button class="ew-btn-v2 ew-btn-v2-primary" type="button" id="save"><i class="fa fa-save"></i> Save</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php require_once("include/footer.php"); ?>
	</div>

	<script type="text/javascript">
	$(document).ready(function(){
		var mappingClients = <?php echo $mapping_clients_json ?: '[]'; ?>;
		var editCustomerId = '<?php echo $edit_customer_id > 0 ? $edit_customer_id : ''; ?>';
		var new_id = [];
		var del_id = [];

		function escHtml(v) {
			if (v === null || v === undefined) return '';
			return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		function initMapSelect($el, placeholder, disabled) {
			if (!$el.length || typeof $.fn.select2 !== 'function') return;
			try {
				if ($el.data('select2')) $el.select2('destroy');
			} catch (e) {}
			$el.prop('disabled', !!disabled);
			$el.select2({
				width: '100%',
				placeholder: placeholder || 'Select',
				allowClear: !disabled
			});
			if ($el.data('select2')) {
				$el.select2('enable', !disabled);
			}
		}

		function fillConsigneeOptions() {
			var html = '<option value="">Select consignee</option>';
			$.each(mappingClients || [], function(i, c) {
				html += '<option value="' + escHtml(c.id) + '">' + escHtml(c.name) + '</option>';
			});
			var $el = $('#client_mapping_name');
			try {
				if ($el.data('select2')) $el.select2('destroy');
			} catch (e) {}
			$el.html(html);
			initMapSelect($el, 'Select consignee', !$('#client').val());
		}

		function updateMapCount() {
			var n = $("#table_data tr").length;
			$("#map_count").text(n + (n === 1 ? " consignee" : " consignees"));
			if (n === 0) {
				$("#map_empty").show();
			} else {
				$("#map_empty").hide();
			}
		}

		function renumberRows() {
			$("#table_data tr").each(function(i){
				$(this).find(".map-sl").text(i + 1);
			});
			updateMapCount();
		}

		function alreadyMapped(id) {
			var found = false;
			$('input[name="mapp_client_id[]"]').each(function(){
				if (String($(this).val()) === String(id)) {
					found = true;
					return false;
				}
			});
			return found;
		}

		function showMsg(text) {
			$("#msg").text(text).stop(true, true).fadeIn(200).delay(1800).fadeOut(400);
		}

		function loadCustomerMapping(id) {
			if (!id) {
				return;
			}
			$("#client").val(id);
			$("#table_div").show();
			fillConsigneeOptions();
			$.ajax({
				url: 'fetch_details.php',
				type: "GET",
				data: { cmd: "get_customer_mapping_details", id: id },
				success: function(result){
					new_id = [];
					del_id = [];
					$("#new_id").val('');
					$("#del_id").val('');
					if (result !== '0' && $.trim(result) !== '') {
						$('#table_data').html(result);
					} else {
						$('#table_data').html("");
						showMsg("No mapped consignees yet. Add them now.");
					}
					renumberRows();
					$("#table_div").show();
				}
			});
		}

		initMapSelect($('#client_name'), 'Select customer', !!editCustomerId);
		fillConsigneeOptions();
		if (editCustomerId) {
			loadCustomerMapping(editCustomerId);
		}

		$(document).on('change', '#client_name', function(){
			var id = $(this).val();
			new_id = [];
			del_id = [];
			$("#new_id").val('');
			$("#del_id").val('');
			$("#table_data").empty();
			if (!id) {
				$("#client").val('');
				$("#table_div").hide();
				fillConsigneeOptions();
				updateMapCount();
				return;
			}
			loadCustomerMapping(id);
		});

		$(document).on('change', '#client_mapping_name', function(){
			var id = $(this).val();
			if (!id) {
				return;
			}
			if (!$("#client").val()) {
				showMsg("Select a customer first.");
				$(this).select2('val', '');
				return;
			}
			if (alreadyMapped(id)) {
				showMsg("This consignee is already mapped.");
				$(this).select2('val', '');
				return;
			}
			var name = $(this).find('option:selected').text();
			new_id.push(id);
			$("#new_id").val(new_id.join(','));
			var i = $("#table_data tr").length + 1;
			var new_row = '<tr>' +
				'<td class="col-center map-sl">' + i + '</td>' +
				'<td>' + $('<div/>').text(name).html() + '</td>' +
				'<td class="col-actions">' +
				'<input type="hidden" name="mapp_client_id[]" value="' + id + '" />' +
				'<a title="Delete" href="#" class="ew-icon-btn-v2 btn-trash"><i class="fa fa-trash-o"></i></a>' +
				'</td></tr>';
			$("#table_div").show();
			$("#table_data").append(new_row);
			renumberRows();
			$(this).select2('val', '');
		});

		$(document).on('click', '#save', function(){
			var $btn = $(this);
			if (!$("#client").val()) {
				showMsg("Select a customer first.");
				return;
			}
			if ($("#table_data tr").length === 0 && del_id.length === 0) {
				showMsg("Add at least one consignee.");
				return;
			}
			$btn.prop("disabled", true);
			$.ajax({
				url: "save_details.php",
				type: "post",
				data: $('#form_data').serialize(),
				success: function(result){
					if (result == 1) {
						window.location.href = 'customer_mapping.php';
					} else {
						$btn.prop("disabled", false);
						showMsg("Data saving failed");
					}
				},
				error: function(){
					$btn.prop("disabled", false);
				}
			});
		});

		$(document).on('click', '.btn-trash', function(ev){
			ev.preventDefault();
			var rowId = $(this).attr("id");
			if (rowId > 0) {
				del_id.push(rowId);
				$("#del_id").val(del_id.join(','));
			}
			$(this).closest('tr').remove();
			renumberRows();
		});
	});
	$(window).load(function() {
		$(".loading-page").hide();
	});
	</script>
</body>
</html>
