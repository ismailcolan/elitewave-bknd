<?php
require_once("include/connect.php");
require_once("include/function.php");
ew_hub_ensure_location_city_column($conn);
?>
<!DOCTYPE html>
<html>
  <head>
  <?php include("include/title.php"); ?>
  <?php include("include/css_js.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<style>
#contact_no:invalid {
  color: red;
}
/* Let Location (Select2) and Cities multiselect menus escape the modal box */
#hubModal .ew-v2-modal {
  overflow: visible;
}
#hubModal .ew-v2-modal-body {
  overflow: visible;
  overflow-y: visible;
}
#hubModal .btn-group.multi-select-list {
  position: relative;
}
#hubModal .btn-group.multi-select-list.dropup .multiselect-container.dropdown-menu {
  top: auto;
  bottom: 100%;
  margin-top: 0;
  margin-bottom: 4px;
}
#hubModal .multiselect-container.dropdown-menu {
  z-index: 10160 !important;
  position: absolute;
}
body:has(#hubModal.open) .multiselect-container.dropdown-menu {
  z-index: 10160 !important;
}
.ew-hub-location-field .select2-container {
  z-index: 1;
}
.hub_table {
    margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
 
} 
th.table-title.hub_th {
    width: 121px!important;
}
.dataTable th.sorting:after, .dataTable th.sorting_desc:after {
    top: 9px;
    right: 2px;
}
.dataTable th.sorting:before, .dataTable th.sorting_asc:after {
    top: 3px;
    right: 2px;
}

@media (min-width: 360px) and (max-width:575.98px) { 
th.table-title.hub_th.sorting_disabled {
    width: 52px!important;
}
div#dataTable1_filter {
    display: block;
}


div#dataTable1_length {
    display: block;
}
.dataTables_filter input {
    width: 112px;
 
}
.dataTables_length {
    width: 40%;
    float: left;
    margin: 5px 0 10px;
}
}
</style>
  </head>
  <body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
      <!-- Navigation -->
      <div class="navbar navbar-fixed-top scroll-hide">
        <?php 
			require_once("include/header.php");
			require_once("include/menu.php"); 
			
		 ?>
      
	</div>
<div class="container-fluid main-content new_dpt_bottom">
  
		<div class="row">
		  <div class="col-md-12">
			<div class="ew-page-v2 ew-page-v2--wide-table">
			  <div class="ew-page-head">
				<div class="ew-page-head-left">
				  <h1 class="ew-page-title">EW360 Warehouse</h1>
				</div>
			  </div>
			  <div class="ew-card">
				<div class="ew-card-toolbar">
				  <h2>Warehouse List</h2>
				  <div class="ew-toolbar-right">
					<button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openCreateHub">
					  Create <i class="fa fa-plus"></i>
					</button>
				  </div>
				</div>
				<div class="ew-table-wrap widget-content padded clearfix new_dept">
                    <table class="table table-bordered table-striped hub_table" id="dataTable1">
							<thead>
								<th class="table-title hub_th" style="width:10%">S.No</th>
								<th class="table-title hub_th" style="width:20%!important">Warehouse Code</th>
								<th class="table-title hub_th" style="width:15%">Warehouse Name</th>
								<th class="table-title hub_th" style="width:15%">Contact Person</th>
								<th class="table-title hub_th" style="width:15%">Contact No</th>
								<th class="table-title hub_th" style="width:18%">Location</th>
								<th class="table-title hub_th" style="width:18%">Cities Covered By Hub</th>
								<?php /* Via Main Hub column hidden for now
								<th class="table-title hub_th" style="width:15%">Via Main Hub</th>
								*/ ?>
							
								<th class="table-title hub_th" style="width:15%">Action</th>              
							</thead>
							<tbody>
							<?php 
								$query = "select * from hub";
								$result = mysqli_query($conn,$query);
									$i=1;
								while($row = mysqli_fetch_array($result))
								{
									$city_names = "";
									$cities = explode(",", $row['covered_cities']);
									for ($ci = 0; $ci < count($cities); $ci++) {
										$city_names .= get_city_name($conn, $cities[$ci]) . ",";
									}
									$city_names = rtrim($city_names, ",");
									$location_label = ew_city_location_display_label($conn, (int) ($row['location_city_id'] ?? 0));
							?>
								<tr>
									<td class="text-center"><?php echo $i; ?></td>
									<td class="text-center"><?php echo $row['hub_code']; ?></td>
									<td><?php echo $row['name']; ?></td>
									<td><?php echo trim((string) ($row['contact_person'] ?? '')) !== '' ? htmlspecialchars($row['contact_person']) : '—'; ?></td>
									<td><?php echo trim((string) ($row['contact_no'] ?? '')) !== '' ? htmlspecialchars($row['contact_no']) : '—'; ?></td>
									<td><?php echo $location_label !== '' ? htmlspecialchars($location_label) : '—'; ?></td>
									<td><?php echo htmlspecialchars($city_names); ?></td>
									<?php /* Via Main Hub hidden
									<td><?php if($row['main_hubs']> 0) echo get_hub_name($conn,$row['main_hubs']); else echo "-"; ?></td>
									*/ ?>
									<td class="actions center-content ">
										<div class="action-buttons">
											<a title="Edit" class="table-actions btn-edit" id="<?php echo $row['hub_id']; ?>"><i class="fa fa-pencil"></i></a>
											<?php 
											if($row['status'] == 0)
											{
											?>
											<a class="table-actions btn-active" data-status="<?php echo $row['status']  ?>" title="InActive" id="<?php echo $row['hub_id'] ?>"><i class="fa fa-check"></i></a>
											<?php 
											}
											else
											{
											?>
											<a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status']  ?>" title="Active" id="<?php echo $row['hub_id'] ?>"><i class="fa fa-times"></i></a>
											<?php 
											}
											?>
											<a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['hub_id'] ?>"><i class="fa fa-trash-o"></i></a>
											
										</div>
									</td>
								</tr>
							<?php 
									$i++;
								}	
							?>
	
							</tbody>
						</table>
				</div>
			  </div>
			</div>
		  </div>
		</div>
	

		<?php require_once("include/footer.php"); ?>
	</div>	

		
		<script type="text/javascript">
		$(document).ready(function(){

			function hubModalCleanupLocationSelect() {
				var $loc = $('#warehouse_location');
				if ($loc.length && $loc.data('select2')) {
					try {
						$loc.select2('close');
					} catch (e) {}
					$loc.select2('destroy');
				}
				$('#select2-drop, #select2-drop-mask').remove();
			}

			function initWarehouseLocationSelect() {
				hubModalCleanupLocationSelect();
				var $loc = $('#warehouse_location');
				if (!$loc.length) {
					return;
				}
				$loc.select2({
					width: '100%',
					placeholder: 'Search city, state, country',
					allowClear: true,
					minimumResultsForSearch: 0
				});
			}

			function syncHubCitiesMultiselectDirection() {
				var $select = $('#cities');
				var ms = $select.data('multiselect');
				if (!ms || !ms.$container) {
					return;
				}
				var $container = ms.$container;
				var $btn = $container.find('button.multiselect');
				if (!$btn.length) {
					return;
				}
				var rect = $btn[0].getBoundingClientRect();
				var vh = window.innerHeight || document.documentElement.clientHeight;
				var menuNeed = 280;
				var spaceBelow = vh - rect.bottom - 16;
				var spaceAbove = rect.top - 16;
				var openUp = spaceBelow < menuNeed && spaceAbove > spaceBelow;
				$container.toggleClass('dropup', openUp);
				if (ms.$ul && ms.$ul.length) {
					ms.$ul.css('margin-top', '');
				}
			}

			$('#cities').multiselect({
				buttonWidth: '100%',
				buttonClass: 'btn btn-default',
				maxHeight: 250,
				dropUp: false,
				enableFiltering: true,
				enableCaseInsensitiveFiltering: true,
				filterPlaceholder: 'Search city',
				onDropdownShow: syncHubCitiesMultiselectDirection,
				onDropdownShown: syncHubCitiesMultiselectDirection
			});
			$('#cities').multiselect('rebuild');
			$(window).on('resize scroll', function() {
				if ($('#hubModal').hasClass('open') && $('#cities').closest('.btn-group').hasClass('open')) {
					syncHubCitiesMultiselectDirection();
				}
			});

			$(document).on('click', '#hubModal [data-ew-v2-close], #hubModal .ew-v2-modal-close', hubModalCleanupLocationSelect);
			$(document).on('click', '#hubModal.ew-v2-modal-backdrop.open', function(e) {
				if (e.target === this) {
					hubModalCleanupLocationSelect();
				}
			});

			var dup_chk = true;
			function duplicate_check(){
				var hub_name = $("#hub_name").val();
				var edit_id = $("#edit_id").val();
				$.ajax({
					cache: false,
					url: 'check_existing.php',
					type : "GET",
					dataType : 'json',
					async: false,
					data : {cmd: "chk_hub", hub_name: hub_name, edit_id: edit_id},
					success : function(result) {
						$(".form-data-saving").hide();
						dup_chk = true;
						if(result[0] == 1){
							$(".dup-check").html(result[1]).css("color","#f00");
							dup_chk = false;
						}
						else{
							$(".dup-check").html(result[1]).css("color","green");
						}
					},
					error: function(jqxhr) {
						console.log(jqxhr.responseText);
					}
				});
			}

			$(document).on('change blur', '#hub_name', duplicate_check);
	
	$(document).on('keypress', '#contact_no', function(evt){
				var value = $(this).val();
				var length = value.length;
				//alert(length);
				var charCode = (evt.which) ? evt.which : event.keyCode;
				if((value.indexOf('.')!=-1) && (charCode != 45 && (charCode < 48 || charCode > 57))){
					return false;
				}    
				else if(charCode != 45 && (charCode != 46 || $(this).val().indexOf('.') != -1) && (charCode < 48 || charCode > 57)){
					return false;
				}
				else if(length > 9){
					return false;
				}
				return true;
			});
		//button Save
			$(document).on('click','#save',function(){
				var data = $('#hub_form').serialize();
				duplicate_check();

				if($('#hub_form').valid() == true && dup_chk)
				{
					//$(this).attr("disabled",true);
					$.ajax({
    url:"save_details.php",
    type:"POST",
    data:data,
    success:function(result){

        console.log(result);

        if($.trim(result) == "1"){
            $(".form-data-saving").hide();
            $("#alert-status").text("");
            $("#alert-message").text("Saved Successfully please wait until page refresh");
            $("#alert-container")
                .addClass("alert-success")
                .slideDown(800)
                .fadeTo(1000,500)
                .slideUp(800,function(){
                    $("#alert-container").hide().removeClass("alert-success");
                    hubModalCleanupLocationSelect();
                    location.reload();
                });
        }else{
            console.log(result);   // Shows actual MySQL error
            $(".form-data-saving").hide();
            $("#alert-status").text("Alert !!! ");
            $("#alert-message").text(result);
            $("#alert-container")
                .addClass("alert-danger")
                .show();
        }
    },
    error:function(xhr){
        console.log(xhr.responseText);
    }
});
				}
			});
		$(document).on('click','.close-popup',function(){
				$(".form-data-saving").hide();
				$("#alert-status").text("");
				$("#alert-message").text("Saved Successfully please wait until page refresh");
				$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
				$("#alert-container").hide();
				$("#alert-container").removeClass("alert-success");
				location.reload();
				});
			});
		//Button Delete
			$(document).on('click', '.btn-trash', function(ev){
				var del_id = $(this).attr("id");
				$(".btn-confirm-delete").attr("id",del_id);
			});
			$(document).on('click', '.delete-error-popup-close', function(ev){
				$(".delete-error-popup").hide();
			});
			$(document).on('click', '.btn-confirm-delete', function(ev){
				$(".form-data-saving").show();
				$.post('save_details.php', { form_name: "del_hub", tbl_id: $(this).attr("id") }, function(data,status){	
				console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("City Deleted successfully...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
							$("#alert-container").hide();
							$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					else if(data == "404-del"){
						$(".delete-error-popup").show();
						$(".form-data-saving").hide();
					}
					else{
						$(".form-data-saving").hide();
						$("#alert-status").text("Alert !!! ");
						$("#alert-message").text("City deletion failed");
						$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
							$("#alert-container").hide();
							$("#alert-container").removeClass("alert-danger");
						});
					}
				});
			});
		//Active Inactive
			$(document).on('click', '.btn-active', function(ev){
				$(".form-data-saving").show();
				var status1='';
				var msg='';
				var status = $(this).attr('data-status');
				if(status == '1'){
					status1='0';
					msg = "Activated";
				}
				else{
					status1='1';
					msg = "In-Activated";
				}
				$.post('save_details.php', { form_name: "inacv_hub", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
					console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("City Is "+msg+"...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					
					else if(data == 2){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("City Is "+msg+"...");
						$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-danger");
							location.reload();
						});
					}
					else if(data == "404-del"){
						$(".delete-error-popup").show();
						$(".form-data-saving").hide();
					}
					
				});
			});
			
			
			$('#openCreateHub').on('click', function(){
				$('#form_name').val('add_hub');
				$('#edit_id').val('');
				$('#hub_code').val('');
				$('#hub_name').val('');
				$('#contact_name').val('');
				$('#contact_no').val('');
				$('#warehouse_location').val('');
				$('#cities').val([]);
				$('#cities').multiselect('refresh');
				initWarehouseLocationSelect();
				$('#hubModalTitle').text('Create Warehouse');
				ewV2OpenModal('hubModal');
			});

			//	Button Edit
			$(document).on('click', '.btn-edit', function(ev){
				$(".form-data-saving").show();
				var tbl_id = $(this).attr("id");
				$.ajax({
					cache: false,
					url: 'fetch_details.php', // url where to submit the request
					type : "GET", // type of action POST || GET
					dataType : 'json', // data type
					data : { cmd: "get_hub_details", tbl_id: tbl_id }, // post data || get data
					success : function(result) {
					console.log(result);
						$(".form-data-saving").hide();
						$("#form_name").val("edit_hub");
						$("#edit_id").val(result['hub_id']);
						$("#hub_code").val(result['hub_code']);
						$("#hub_name").val(result['name']);
						$("#contact_name").val(result['contact_person']);
						$("#contact_no").val(result['contact_no']);
						
						initWarehouseLocationSelect();
						if (result['location_city_id']) {
							$('#warehouse_location').select2('val', String(result['location_city_id']));
						} else {
							$('#warehouse_location').select2('val', '');
						}
						if (result['covered_cities']) {
							var cityIds = result['covered_cities'].split(',');
							$('#cities').val(cityIds);
							$('#cities').multiselect('refresh');
						} else {
							$('#cities').val([]);
							$('#cities').multiselect('refresh');
						}
						$('#hubModalTitle').text('Edit Warehouse');
						ewV2OpenModal('hubModal');
					},
					error: function(jqxhr) {
						console.log(jqxhr.responseText);
					}
				});
			});

			
			//Button Reset
			$(document).on('click', '.btn-reset', function(ev){
				$('#form_name').val('add_hub');
				$('#edit_id').val('');
				$('#hub_code').val('');
				$('#hub_name').val('');
				$('#contact_name').val('');
				$('#contact_no').val('');
				$('#warehouse_location').val('');
				$('#cities').val([]);
				$('#cities').multiselect('refresh');
				hubModalCleanupLocationSelect();
				ewV2CloseModal('hubModal');
			});

				
		});
		$(window).load(function() {
			$(".loading-page").hide();
		});
		</script>
		<div class="alert" id="alert-container" style="display:none;">
		<button type="button" class="close" data-dismiss="alert">x</button>
		<strong id="alert-status"></strong>
		<span id="alert-message"></span>
		</div>
        
     
	    <div class="modal fade popup_close" id="myModal">
		    <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
			    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
			    <h4 class="modal-title" style="color:#fff">
			      Alert!
			    </h4>
			  </div>
			    
				<div class="modal-body">
				    <h5 text-align="center">
				     Do you want to Delete This Record ?
				    </h5>
					<div class="modal-footer">
					    <button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button>
					    <button class="btn btn-default-outline" data-dismiss="modal" type="button" id="">No</button>
					</div>
				</div>
			</div>
		    </div>
                </div>
				
		<div class="delete-error-popup" >
		    <div class="popup_overlay" id="popup_overlay"></div>
			<div class="popup" id="popup">
			    <div class="popup_message">
			    <h5 class="popup-title">Alert ! </h5>
				    This Data Cannot Delete.Used by another record. so you can't Delete !!! <br/> &nbsp; <br/>
			    <button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br/> &nbsp; <br/>
			    </div>
			    <!--<span class="popup_close" id="popup_close">X</span>-->
			</div>
		</div>

		<div class="ew-v2-modal-backdrop" id="hubModal">
		  <div class="ew-v2-modal ew-v2-modal--lg">
			<div class="ew-v2-modal-head">
			  <h3 id="hubModalTitle">Create Warehouse</h3>
			  <button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
			</div>
			<div class="ew-v2-modal-body">
			  <form class="form-horizontal" id="hub_form">
				<input type="hidden" id="form_name" name="form_name" value="add_hub">
				<input type="hidden" id="edit_id" name="edit_id" value="">
				<div id="response" class="alert alert-danger" style="display:none;">
				  <div class="message" style="text-align:center"></div>
				</div>
				<div class="form-group">
				  <label class="control-label">Warehouse Code <span style="color:red;">*</span> :</label>
				  <input type="text" id="hub_code" name="hub_code" placeholder="<?php echo htmlspecialchars(ew_master_format_code(ew_master_code_prefix('Hub Details'), 1)); ?>" class="form-control" disabled/>
				</div>
				<div class="form-group">
				  <label class="control-label">Warehouse Name <span style="color:red;">*</span> :</label>
				  <input type="text" name="hub_name" id="hub_name" class="form-control" required/>
				  <span class="dup-check"></span>
				</div>
				<div class="form-group">
				  <label class="control-label">Warehouse Contact Person:</label>
				  <input type="text" name="contact_name" id="contact_name" class="form-control" />
				</div>
				<div class="form-group">
				  <label class="control-label">Warehouse Contact Number:</label>
				  <input type="text" name="contact_no" id="contact_no" class="form-control" pattern="\d{10}" minlength=10 maxlength=10 inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return ewNumericPaste(event,this);" autocomplete="off"/>
				</div>
				<input type="hidden" name="route" value="Main" />
				<input type="hidden" name="main_hub" value="" />
				<?php /* Hub Route / Via Main Hub — hidden for now
				<div class="form-group">
				  <label class="control-label">Hub Route:</label>
				  <input type="radio" name="route" class="route" id="route_main" value="Main" checked /> Main Hub
				  <input type="radio" name="route" class="route" id="route_via" value="Via" /> Via
				</div>
				<div class="form-group" id="main_hub_group">
				  <label class="control-label">If Via Main Hub <span id="main_hub_required" style="color:red;display:none;">*</span> :</label>
				  <select name="main_hub" class="form-control" id="main_hub">
					<option value="">Select Main Hub</option>
				  </select>
				</div>
				*/ ?>
				<div class="form-group ew-hub-location-field">
				  <label class="control-label">Location <span style="color:red;">*</span> :</label>
				  <select name="location_city_id" class="form-control" id="warehouse_location" required>
					<option value=""></option>
					<?php echo ew_hub_location_select_options_html($conn); ?>
				  </select>
				  <p class="help-block" style="margin-top:6px;font-size:12px;color:#6B7A8D;">City, State, Country — searchable dropdown.</p>
				</div>
				<div class="form-group">
				  <label class="control-label">Cities Covered By Hub:</label>
				  <select name="cities[]" class="form-control" id="cities" multiple="multiple">
					<?php
						$city_list_q = mysqli_query($conn, 'SELECT city_id, city_name FROM city WHERE status=0 ORDER BY city_name');
						while ($city_list_row = mysqli_fetch_array($city_list_q)) {
							echo '<option value="' . (int) $city_list_row['city_id'] . '">' . htmlspecialchars($city_list_row['city_name']) . '</option>';
						}
					?>
				  </select>
				</div>
			  </form>
			</div>
			<div class="ew-v2-modal-foot">
			  <button type="button" class="btn btn-default-outline btn-reset" data-ew-v2-close>Cancel</button>
			  <button class="btn btn-primary" type="button" id="save">Submit</button>
			</div>
		  </div>
		</div>
		
  </body>
</html>