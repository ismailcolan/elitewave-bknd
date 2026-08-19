<?php
require_once("include/connect.php");
require_once("include/function.php"); 
?>
<!DOCTYPE html>
<html>
  <head>
  <?php include("include/title.php"); ?>
  <?php include("include/css_js.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<style>
.table th{
	line-height: 3.5;
}
.table td{
	line-height: 3.5;
}
@media (min-width: 768px){
.form-horizontal .control-label {
    text-align: left;
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
		  <div class="col-md-offset-1 col-md-10">
			<div class="widget-container fluid-height clearfix">
			  <div class="heading"> <i class="fa fa-plus"></i>Customer Mapping </div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="form_data">
				
					<input type="hidden" id="form_name" name="form_name" value="add_customer_mapping">
					<input type="hidden" id="edit_id" name="edit_id" value="">
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				  <div class="row">
						<div class="col-md-offset-2 col-md-6">
							<div class="form-group">
								<label class="control-label col-sm-4">Customer <span style="color:red;">*</span> :</label>
								<div class="col-lg-8">
									<input  class="form-control" type="hidden" name="del_id" id="del_id" >
									<input  class="form-control" type="hidden" name="new_id" id="new_id" >
									<input  class="form-control" type="hidden" name="client" id="client" >
									<input type="text" class="form-control" name="client_name" id="client_name" required autocomplete="off">
										
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-4">Select Consignee <span style="color:red;">*</span> :</label>
								<div class="col-lg-8">
								
									<input type="text"  class="form-control" name="client_mapping_name" id="client_mapping_name" >
										
									
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-4"></label>
								<div class="col-lg-8 text-center ">
								<span id="msg" style="color: red;"> </span>
									
								</div>
							</div>
							
							</div>
					
						</div>
				 </div><br/>
				 <div class="row">
			<div class="col-md-offset-1 col-md-10"  id="table_div" style="display:none">
                <table class="table table-bordered" cellpadding="5" cellspacing="5" >
					<thead>
					<tr>
						<th width="20" class="text-center table-title">Sl.No</th>
						<th width="350" class="text-center table-title">Consignee</th>
						<th width="80" class="text-center table-title">Delete</th>              
					</tr>
					</thead>
					<tbody class="scrollable" id="table_data" >
					</tbody>
					
                </table>
			</div>

		</div>
				   <div class="row" style="margin: 0;">
					<div class="col-md-12 form-action">
						<button class="btn btn-primary" type="button" id="save">Save</button>
						
					</div>
				  </div>
				</form>
				
			  </div>
			</div>
		  </div>


		</div>
		</div>
	

		<?php require_once("include/footer.php"); ?>
	</div>	

		
		<script type="text/javascript">
		$(document).ready(function(){
			var new_id=[];	
			
			$(document).on('input','#client_name',function(){
            if ($("#mapp_client_id").length > 0) {
					$("#mapp_client_id").val('')
					$("#new_id").val('new_id')
					var new_id = []
				}
				$("#table_div").hide();
				var term = $(this).val();
				$("#client_name").autocomplete({
					source:'autocomplete_list.php?autocomplete=mapping_client_autocomplete&term='+term,
					minLength:0,
					select: function(event, ui) {
						$("#client_name").val(ui.item.value);
						$("#client").val(ui.item.id);
						var id = ui.item.id;
						$("#table_div").show();
						$.ajax({
							url:'fetch_details.php',
							type:"GET",
							async:false,
							data:{cmd:"get_customer_mapping_details",id:id},
							success:function(result){
								console.log(result);
								if(result!='0')
								{
								$('.scrollable').html(result);
								$("#table_div").show();
							}
								else
									{
										$('.scrollable').html("");
										$("#msg").html("No Mapped Clients..Map Now").fadeIn(2000).fadeOut(2000);
									}
							}
						});
					},
					
				});
			});
				
		
			$(document).on('keyup','#client_mapping_name',function(){
				var act=$(this);
				var term = $(this).val();
				var client = $("#client").val();
				$("#client_mapping_name").autocomplete({
					source:'autocomplete_list.php?autocomplete=mapping_client_cus_autocomplete&term='+term+'&client='+client,
					minLength:0,
					select: function(event, ui) {

							if(ui.item.id>0)
							new_id.push(ui.item.id);
							console.log("new:"+new_id);
						$("#new_id").val(new_id);
						//$("#client_mapping").val(ui.item.id);
						var i=$(".table tbody tr").length+1;
						var new_row='<tr><td  class="text-center">'+i+'</td><td>'+ui.item.value+'</td><td class="text-center"><input type="hidden" name="mapp_client_id[]" value="'+ui.item.id+'"  id="mapp_client_id" /><a title="Delete" href="#" class="table-actions btn-trash" id=""><i class="fa fa-trash-o"></i></a></td></tr>';

							$("#table_div").show();
								$("#table_data").append(new_row).trigger('change');
								setTimeout(function(){ $("#client_mapping_name").val(""); }, 5000);


							
						
					},
					
				});
			});
		
		//button Save
			$(document).on('click','#save',function(){
				var data = $('#form_data').serialize();
				console.log(data);
				if($('#form_data').valid() == true)
				{
					$(this).attr("disabled",true);
					$.ajax({
						url:"save_details.php",
						type:"post",
						data:data,
						success:function(result){
							console.log(result);
							if(result == 1){
								$(".form-data-saving").hide();
								$("#alert-status").text("");
								$("#alert-message").text("Saved Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								location.reload();
								});
							}
							else
							{
								$(".form-data-saving").hide();
								$("#alert-status").text("Alert !!! ");
								$("#alert-message").text("Data Saving Failed");
								$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-danger");
								});
							}
						},
						error:function(jqxhr)
						{
							console.log(jqxhr.responseText);
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
		var del_id=[];	
			$(document).on('click', '.btn-trash', function(ev){
				if($(this).attr("id")>0)
				del_id.push($(this).attr("id"));
				console.log("del:"+del_id);
				$("#del_id").val(del_id);
				$(this).closest('tr').remove();
			});
		
			//Active Inactive
			$(document).on('click', '.btn-active', function(ev){
				$(".form-data-saving").show();
				var button=$(this);
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
				$.post('save_details.php', { form_name: "inacv_mapped_client", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
					console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Customer Is "+msg+"...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					
					else if(data == 2){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Customer Is "+msg+"...");
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
			
			
			//Button Reset
			$(document).on('click', '.btn-reset', function(ev){
				location.reload();
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
		
  </body>
</html>