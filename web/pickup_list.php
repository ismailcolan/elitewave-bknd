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
    .dataTable th.sorting:after, .dataTable th.sorting_desc:after {
    top: 9px;
    right: 2px;
}
.dataTable th.sorting:before, .dataTable th.sorting_asc:after {
    top: 3px;
    right: 2px;
} 
@media (min-width: 360px) and (max-width:575.98px) { 
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
    width: 43%;
    float: left;
    margin: 5px 0 10px;
}

.pick-up-tblll{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}
th.table-title.sorting {
    width: 111px!important;
}
.dataTables_filter {
    width: 56%;
    float: right;
    text-align: right;
    color: #5f5f5f;
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
			 	<div class="heading"> <i class="fa fa-table" ></i>Pickup list <span class="align-right"><i class="fa fa-plus" ></i> <a href="request_for_new_pickup.php">Request New pickup</a></span> </div>
					<div class="widget-content padded clearfix new_dept">
						<table class="table table-bordered pick-up-tblll table-striped " id="dataTable1">
							<thead>
								<th class="table-title" style="width:10%">S.No</th>
								<th class="table-title" style="width:10%">RFP ID(d)</th>
								<th class="table-title" style="width:10%">Date</th>
								<th class="table-title" style="width:10%">Consignor</th>
								<th class="table-title" style="width:10%">Destination</th>
								<th class="table-title" style="width:10%">No.of Package</th>
								<th class="table-title" style="width:10%">Mode</th>
								<th class="table-title" style="width:10%">Weight</th>
								<th class="table-title" style="width:10%">Status</th>
								<th class="table-title" style="width:10%">Action</th>              
							</thead>
							<tbody>
							<?php 
							if($_SESSION['role']=='AD'){
								$query = "select * from pickup";
							}
							else{
								$query = "select * from pickup where created_by='".$_SESSION['user_id']."'";
								
							}
								$result = mysqli_query($conn,$query);
									$i=1;
								while($row = mysqli_fetch_assoc($result))
								{
										extract($row);
									
							?>
								<tr>
									<td class="text-center"><?php echo $i; ?></td>
									<td><?php echo $pickup_ref_id; ?></td>
									<td><?php echo $created_at; ?></td>
									<?php
$client = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT client_company_name FROM client WHERE client_id='$consignor'")
);
?>

<td><?php echo $client['client_company_name']; ?></td>
									<?php
$city = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT city_name FROM city WHERE city_id='$destination'")
);
?>

<td><?php echo $city['city_name']; ?></td>
									<td><?php echo $no_of_package; ?></td>
									<td><?php echo get_mode($conn, $row['mode']); ?></td>
									<td><?php echo $approx_weight; ?></td>
									<td>
                                        <?php if($status==0)
											echo '<button class="btn btn-primary">Pending</button>';
											else if($status==1)
												echo '<button class="btn btn-danger">Cancelled</button>';
											else if($status==3)
												echo '<button class="btn btn-success">Picked Up</button>';
										?></td>
									
									<td><?php if($status==0)
									{ ?>
										<div class="action-buttons">
											<a title="Edit" href="request_for_new_pickup.php?key=<?php echo md5($row['pickup_id']); ?>" class="table-actions btn-edit" id="<?php echo $row['pickup_id']; ?>"><i class="fa fa-pencil"></i></a>
											

											<a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['pickup_id']; ?>"><i class="fa fa-trash-o"></i></a>
											
										</div>
									<?php }
											
											
										?></td>
									
									
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
	

		<?php require_once("include/footer.php"); ?>
	</div>	

		
		<script type="text/javascript">
		$(document).ready(function(){

		//Duplication
		var dup_chk = true;
		function duplicate_check(){
			var department_name = $("#department_name").val();
			var edit_id = $("#edit_id").val();
			$.ajax({
				cache: false,
				url: 'check_existing.php', // url where to submit the request
				type : "GET", //type of action POST || GET
				dataType : 'json',// data type
				async: false,
				data : {cmd: "chk_department",department_name:department_name, edit_id: edit_id}, // post data || get data
				success : function(result) {
				      $(".form-data-saving").hide();
					dup_chk = true;
					console.log(result);
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
    $.post('save_details.php', { form_name: "del_pickup", tbl_id: $(this).attr("id") }, function(data,status){
        console.log(data);
        if(data == 1){
            $(".form-data-saving").hide();
            $("#alert-status").text("");
            $("#alert-message").text("Pickup request deleted successfully...");
            $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                $("#alert-container").hide();
                $("#alert-container").removeClass("alert-success");
                location.reload();
            });
        }
        else{
            $(".form-data-saving").hide();
            $("#alert-status").text("Alert !!! ");
            $("#alert-message").text("Delete failed");
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
				$.post('save_details.php', {form_name: "inacv_client", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
					console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Department Is "+msg+"...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					
					else if(data == 2){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Department Is "+msg+"...");
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
			
			
			//	Button Edit
			$(document).on('click', '.btn-edit', function(ev){
				$(".form-data-saving").show();
				var tbl_id = $(this).attr("id");
				$.ajax({
					cache: false,
					url: 'fetch_details.php', // url where to submit the request
					type : "GET", // type of action POST || GET
					dataType : 'json', // data type
					data : { cmd: "get_branch_details", tbl_id: tbl_id }, // post data || get data
					success : function(result) {
					console.log(result);
						$(".form-data-saving").hide();
						$("#form_name").val("edit_branch");
						$("#edit_id").val(result['branch_id']);
						$("#department_code").val(result['department_code']);
						$('#department_name').val(result['department_name']);
						
					},
					error: function(jqxhr) {
						ewToast(jqxhr.responseText, 'error');
					}
				});
			});

			
			//Button Reset
			$(document).on('click', '.btn-reset', function(ev){
				$('#form_name').val('add_branch');
				$('#edit_id').val('');
				$('#department_name').val('');
				$('#department_code').val('');
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