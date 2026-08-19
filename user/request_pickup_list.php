<?php
if(session_id() == '') {
    session_start();
}
include_once('include/user-function.php');
//include_once('include/function.php');
?>
<!DOCTYPE html>
<html lang="en">
	<head>
     <meta charset="utf-8">
     <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
     <title>Gracious Express - Request Pickup List</title>
     <?php include("include/title.php"); ?>
     <?php include("include/css_js_forgetpassword.php"); ?>
     <link href="../assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
     <link href="assets/css/master.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>   -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />  
    <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>  
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>            
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" /> 
    	<link href="stylesheets/datatables.css" media="all" rel="stylesheet" type="text/css" />
     <!-- book consignment css and js starts here -->
     <link rel="stylesheet" href="assets/css/book-consignment.css">
     <link rel="stylesheet" href="f5/fontawesome.min.css">
     <!-- book consignment css and js finished here -->
     <!-- <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script> -->
     <script src="assets/js/jquery.validate.min.js"></script>
     <script src="assets/js/modernizr.custom.js"></script>
     <style>
   
      @media (min-width: 360px) and (max-width: 576.98px) { 
.dataTables_length, .dataTables_filter, .dataTables_info, .paginate_button.first, .paginate_button.last {
    display: block;
}
div.dataTables_wrapper div.dataTables_length select {
    width: 58px;
    display: inline-block;
}
div.dataTables_wrapper div.dataTables_filter input {
    margin-left: 0.5em;
    display: inline-block;
    width: 85px;
}
#employee_data_wrapper > .row > .col-sm-6{
    margin-top:0px;
}
.dataTables_length {
    margin: 23px 5px 10px;
}
      }
     </style>
     </head>
  <body>
      
  <?php include "user-db-header.php"?>
  <br /><br />  
           <div class="container">  
                <h3 align="center"><b>Request Pickup List</b></h3>  
                <div class="table-responsive">  
                     <table id="employee_data" class="table table-striped table-bordered">  
                          <thead>  
                               <tr>   
                                    <td>S.No</td> 
                                    <td>RFP No</td>  
                                    <td>Date</td>  
                                    <td>Consignor Name</td>  
                                    <td>Consignor Contact</td> 
                                    <td>Consignee Name</td>  
                                    <td>Consignee Contact</td>  
                                    <td>Origin</td>  
                                    <td>Destination</td>  
                                    <td>Status</td>  
                                   
                               </tr>  
                          </thead>
                          <tbody>
                          <?php 
                               $username= $_SESSION['user_id'];
                              //$con = mysqli_connect("localhost","root","","graciousexpress");
                                $query = mysqli_query($conn,"select * from user_pickup where user_id='$username' order by pickup_id DESC");
                                $i=1;
                                while($row = mysqli_fetch_array($query))
						  {	
                                   $origin = get_city_name($conn,$row['origin']);
                                   $destination = get_city_name($conn,$row['destination']);
						  ?>
                               <tr>
                                   <td><?php echo $i;?></td>
                                   <td class="text-center"><?php echo $row['pickup_ref_id']; ?></td>
                                   <td class=""><?php echo $row['created_at']; ?></td>
                                   <td><?php  echo $row['consignor_name']; ?></td>
							<td><?php  echo $row['consignor_contact']; ?></td>
                                   <td><?php  echo $row['consignee_name']; ?></td>
							<td><?php  echo $row['consignee_contact']; ?></td>
                                   <td><?php  echo $origin ?></td>
							<td><?php  echo $destination; ?></td>
							<td><?php if($row['status']=="0"){
                                        echo "<p style='color:orange'>Pending</p>";
                                   }else if($row['status']=="1"){
                                        echo "<p style='color:red'>Cancelled</p>";
                                   }else{
                                        echo "<p style='color:green'>Picked Up</p>";
                                   }
                                        ?></td>
                               </tr>
                               <?php 
						  $i++;
						}	
						?>
                          </tbody>  
                              
                     </table>  
                </div>  
           </div>  
	<script>
      $(document).ready(function(){  
      $('#employee_data').DataTable();  
     });   
         $(window).load(function() {
				$(".loading-page").hide();
			});
    </script>
    
	</body>
</html>
