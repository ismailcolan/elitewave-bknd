<?php 
include_once('include/connect.php');
include_once('include/function.php');
include_once('include/user-function.php');
?>
<!doctype html>
<html lang="en">
  <head>
    <?php include("include/title.php");?>
    <?php include("include/css_js.php");?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        .widget-container .widget-content {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
.user_requestpic{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
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
          <div class="navbar navbar-fixed-top scroll-hide">
          <?php include_once('include/header.php');?>
          <?php include_once('include/menu.php');?>
          </div>
      <div class="container-fluid main-content new_dpt_bottom">
          <div class="row">
              <div class="col-md-offset-1 col-md-10">
                  <div class="widget-container fluid-height clearfix">
			 	       <div class="heading"> <i class="fa fa-table" ></i> List of Request For Pickup 
                       <!-- <span class="align-right"><i class="fa fa-plus" ></i> <a href="client.php">Add Client</a></span> -->
                     </div>
                        <div class="widget-content padded clearfix new_dept">
						<table class="table table-bordered table-striped user_requestpic" id="dataTable1">
							<thead>
								<th class="table-title" >S.No</th>
								<th class="table-title" style="width:5%">RFP No</th>
								<th class="table-title" style="width:5%">Date</th>
								<th class="table-title" style="width:10%">Consignor Name</th>
								<th class="table-title" style="width:10%">Consignor Address</th>
								<th class="table-title" style="width:7%">Consignor Contact</th>
                                <th class="table-title" style="width:10%">Consignee Name</th>
								<th class="table-title" style="width:10%">Consignee Address</th>
								<th class="table-title" style="width:5%">Consignee Contact</th>
								<th class="table-title" style="width:5%">Origin</th>
								<th class="table-title" style="width:5%">Destination</th>
								<th class="table-title" style="width:5%">Mode</th>
								<th class="table-title" style="width:5%">Weight</th>
								<th class="table-title" style="width:5%">Request By</th>
								<th class="table-title" style="width:5%">Status</th>
								<th class="table-title" style="width:8%">Action</th>                
							</thead>
							<tbody>
							<?php 
                            
								$query = mysqli_query($conn,"select * from user_pickup order by pickup_id DESC");
							    $i=1;
							 	while($row = mysqli_fetch_array($query))
							 	{	
                                   $user_name = get_user($conn,$row['user_id']);
                                   $origin = get_city_name($conn,$row['origin']);
                                   $destination = get_city_name($conn,$row['destination']);
                                   $mode = get_mode($conn,$row['mode']);
							?>
								<tr>
									<td class="text-center"><?php echo $i; ?></td>
									<td class="text-center"><?php echo $row['pickup_ref_id']; ?></td>
									<td class=""><?php echo $row['created_at']; ?></td>
									<td><?php  echo $row['consignor_name']; ?></td>
									<td><?php  echo $row['consignor_address']; ?></td>
									<td><?php  echo $row['consignor_contact']; ?></td>
                                    <td><?php  echo $row['consignee_name']; ?></td>
									<td><?php  echo $row['consignee_address']; ?></td>
									<td><?php  echo $row['consignee_contact']; ?></td>
									<td><?php  echo $origin ?></td>
									<td><?php  echo $destination ?></td>
									<td><?php  echo $mode ?></td>
									<td><?php  echo $row['approx_weight']; ?></td>
                                    <?php
                                if($row['user_id']!=''){?>
                                    <td><?php  echo $user_name; ?></td>
                               <?php
                                }else{?>
                                    <td>New User</td>
                                <?php
                                }
                                ?>
                                   
									
									<td><?php if($row['status']==0)
											echo '<button class="btn btn-primary">Pending</button>';
											else if($row['status']==1)
												echo '<button class="btn btn-danger">Cancelled</button>';
											else if($row['status']==3)
												echo '<button class="btn btn-success">Picked Up</button>';
										?></td>
									
									<td><?php if($status==0)
									{ ?>
										<div class="action-buttons">
                                        <?php
											if($row['status'] == "0")
											{
                                                // if($status ==0){
											?>
											<a class="table-actions btn-active" data-status="<?php echo $row['status']  ?>" title="Not Picked Up" id="<?php echo $row['pickup_id'] ?>"><i class="fa fa-check"></i></a>
											<?php 
											}
											else
											{
											?>
											<a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status']  ?>" title="Picked Up" id="<?php echo $row['pickup_id'] ?>"><i class="fa fa-times"></i></a>
											<?php 
											 }
											?>

											<a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['pickup_id'] ?>"><i class="fa fa-trash-o"></i></a>
											
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
        		// DataTable initialized by main.js
        $(".btn-active").click(function(){
            $(".form-data-saving").show();
            var status1 = '';
            var msg = '';
            var status = $(this).attr('data-status');
           // alert(status);
            if(status == '0'){
                status1 = '3';
                msg = 'Picked Up';
            }else if(status == '1'){
                status1 = '0';
                msg = 'Pending';
            }else if(status == '3')
            {
                status1 = '1';
                msg = 'Cancelled';
            }
            $.post('../save_details.php', {form_name: "inactivate_req_for_pickup",tbl_id: $(this).attr("id"), status:status1}, function(data,status){
                console.log(data);
                if(data != 0){
                    $(".form-data-saving").hide();
                    $("#alert-status").text("");
                    $("#alert-message").text("Pickup is "+ msg +".");
                    $("#alert-container").addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                    $("#alert-container").hide();
                    $("#alert-container").removeClass('alert-success');
                    location.reload();
                    });
                }else if(data == 2){
                    $(".form-data-saving").hide();
                    $("#alert-status").text("");
                    $("#alert-message").text("User is "+ msg +" Now...");
                    $("#alert-container").addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                    $("#alert-container").hide();
                    $("#alert-container").removeClass('alert-danger');
                     location.reload();
                    });
                }
                // else if(data == "404-del"){        
                //     $(".delete-error-popup").show();
				// 	$(".form-data-saving").hide();
                // }
            });
        });

        $(".btn-trash").on('click',function(e){
            var del_id = $(this).attr('id');
            //alert(del_id);
            $(".btn-confirm-delete").attr("id",del_id);
        });
        $(document).on('click','.btn-confirm-delete',function(e){
            $(".form-data-saving").show();
            $.post('../save_details.php',{form_name:"delete_request_pickup",tbl_id: $(this).attr("id")}, function(data,status){
                console.log(data);
                if(data != 0){
                    $(".form-data-saving").hide();
                    $("#alert-status").text("");
                    $("#alert-message").text("Pickup Deleted Successfully....");
                    $("#alert-container").addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                    $("#alert-container").hide();
                    $("#alert-container").removeClass('alert-success');
                    location.reload();
                    });
                }else if(data == "404-del"){
                    $(".delete-error-popup").show();
					$(".form-data-saving").hide();
                }
                else{
                    $(".form-data-saving").hide();
                    $("#alert-status").text("");
                    $("#alert-message").text("Pickup Delete Failed!");
                    $("#alert-container").addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                    $("#alert-container").hide();
                    $("#alert-container").removeClass('alert-danger');
                    location.reload();
                    });
                }
            });
        })
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