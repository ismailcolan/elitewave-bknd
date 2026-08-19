<?php 
include_once('include/connect.php');
include_once('include/function.php');
?>
<!doctype html>
<html lang="en">
  <head>
    <?php include("include/title.php");?>
    <?php include("include/css_js.php");?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<style>
		a.disable{
			pointer-events:none;
			cursor:default;
		}
        .widget-container .widget-content {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
.consigner_payl{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}
th.table-title.sorting {
    width: 154px!important;
}
.dataTable th.sorting:after, .dataTable th.sorting_desc:after {
    top: 9px;
    right: 2px;
}
.dataTable th.sorting:before, .dataTable th.sorting_asc:after {
    top: 3px;
    right: 2px;
}
	</style>
  </head>
  <body class="page-header-fixed bg-1">
      <div class="modal-shiftfix">
          <div class="navbar navbar-fixed-top scroll-hide">
          <?php include_once('include/header.php');?>
          <?php include_once('include/menu.php');?>
          </div>
      </div>
      <div class="container-fluid main-content new_dpt_bottom">
          <div class="row">
              <div class="col-md-offset-1 col-md-10">
                  <div class="widget-container fluid-height clearfix">
			 	       <div class="heading"> <i class="fa fa-table" ></i> List of Consigner Charges <span class="align-right"><i class="fa fa-plus" ></i> <a href="consigner_payment_form.php">Add Charges</a></span> </div>
                        <div class="widget-content padded clearfix new_dept">
						<table class="table table-bordered table-striped consigner_payl" id="dataTable1">
							<thead>
								<th class="table-title" style="width:1%">S.No</th>
								<th class="table-title" style="width:9%">Consigner</th>
								<th class="table-title" style="width:5%">Destination</th>
								<th class="table-title" style="width:30%">Load / Unload Chrgs</th>
								<th class="table-title" style="width:5%">Crane/Lift Chrgs</th>
								<th class="table-title" style="width:5%">Doc Chrgs</th>
								<th class="table-title" style="width:5%">Labour Chrgs</th>
								
								<th class="table-title" style="width:5%">Other Chrgs</th>
								<th class="table-title" style="width:5%">Air</th>
								<th class="table-title" style="width:5%">Train</th>
								<th class="table-title" style="width:5%">PTL</th>
								<th class="table-title" style="width:5%">Express</th>
								<th class="table-title" style="width:5%">Local</th>
								<th class="table-title" style="width:5%">Action</th>              
							</thead>
							<tbody>
							<?php 
								//* Get Data From Table
								$query = mysqli_query($conn,"select * from consignor_payment order by id desc");
							    $i=1;
							 	while($row = mysqli_fetch_array($query))
							 	{	
							?>
								<tr>
									<td class="text-center"><?php echo $i; ?></td>
									<td class="text-center"><?php echo get_client_name($conn,$row['consigner_id']); ?></td>
									<td class="text-center"><?php  echo get_city_name($conn,$row['destination']); ?></td>
									<td class="text-center"><?php  echo $row['loading_unloading_chrgs']; ?></td>
									<td class="text-center"><?php  echo $row['crane_fork_lift_chrgs']; ?></td>
									<td class="text-center"><?php  echo $row['doc_chrgs']; ?></td>
									<td class="text-center"><?php  echo $row['labour_charges']; ?></td>
									<td class="text-center"><?php  echo $row['other_chrgs']; ?></td>
									<td class="text-center"><?php  echo $row['air'] ?></td>
									<td class="text-center"><?php  echo $row['train'] ?></td>
									<td class="text-center"><?php  echo $row['ptl'] ?></td>
									<td class="text-center"><?php  echo $row['express'] ?></td>
									<td class="text-center"><?php  echo $row['local_delivery'] ?></td>
                                    <td>
                                    
										<!-- <a title="Invoice" href="user_invoice_generate.php?key=<?php //echo md5($row['user_id']);?>" class="table-actions btn-invoice " data-toggle="modal" id="<?php //echo $row['user_id'] ?>"><i class="fa fa-file"></i></a> -->
									<a title="Edit" href="consigner_payment_form.php?key=<?php echo md5($row['id']);?>" class="table-actions btn-invoice " data-toggle="modal" id="<?php echo $row['id'] ?>"><i class="fa fa-file"></i></a>

									<a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['id'] ?>"><i class="fa fa-trash-o"></i></a>
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
        // e.preventDefault();

        $(document).on("click",".btn-trash",function(){
            var delete_id = $(this).attr('id');
            $(".btn-confirm-delete").attr("id",delete_id);
            //alert(delete_id);
        });

        $(document).on("click",".btn-confirm-delete",function(){
            $(".form-data-saving").show();

            $.post('https://elitewave360.in/php/save_details.php',{form_name : "delete_consigner_charges", tbl_id : $(this).attr("id")},  function(data, status){
                console.log(data);
                if(data != "0"){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Consigner Charges Deleted successfully...");
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
						$("#alert-message").text("Estimated Delivery Deletion Failed");
						$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
							$("#alert-container").hide();
							$("#alert-container").removeClass("alert-danger");
						});
					}
            });
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