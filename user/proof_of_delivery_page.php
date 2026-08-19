<?php
if(session_id() == '') {
    session_start();
}
error_reporting(1);
include_once('include/user-function.php');
require_once ('include/connect.php');

if($_REQUEST['key']!=''){
    $tbl_id = $_REQUEST['key'];
     $date = $_REQUEST['grn_date'];
    
    //$date = date('d-m-Y');
    $my = date('m-Y');
    $dt = (explode("-",$date));

  //   var_dump($dt); //array(3) { [0]=> string(2) "03" [1]=> string(2) "08" [2]=> string(4) "2021" }
    if($dt[1]<=3){
         $m1 =1;
         $y = $dt[2];
         $trans_name="transaction_".$m1."_".$y;
         $trans_images="transaction_images_".$m1."_".$y;
         $trans_invoice="transaction_invoice_".$m1."_".$y;
         //echo "First Quarter";

    }else if(($dt[1]>=4) && ($dt[1]<=6)){
         $m1 =2;
         $trans_name="transaction_".$m1."_".$y;
         $trans_images="transaction_images_".$m1."_".$y;
         $trans_invoice="transaction_invoice_".$m1."_".$y;   
         //echo "Second Quarter";
    }else if(($dt[1]>=7) && ($dt[1]<=9)){
         $m1 =3;
         $y = $dt[2];
         $trans_name="transaction_".$m1."_".$y;
         $trans_images="transaction_images_".$m1."_".$y;
         $trans_invoice="transaction_invoice_".$m1."_".$y;

       //   var_dump($trans_invoice);
         //echo "Third Quarter";
    }else{
          $m1 =4;
          $y = $dt[2];
          $trans_name="transaction_".$m1."_".$y;
          $trans_images="transaction_images_".$m1."_".$y;
         $trans_invoice="transaction_invoice_".$m1."_".$y;  
         //echo "Fourth Quarter";
    }


 $query = "select *from transaction_".$m1."_".$dt[2]." where md5(transaction_id) ='$tbl_id' ";
    $query_result = mysqli_query($conn,$query);

    $user_booking = mysqli_fetch_assoc($query_result);
    
    $grn_no = $user_booking['grn_no'];
    $booking_status = $user_booking['booking_status'];
    
}else{
    $grn_no=trim($_REQUEST['grn_no']);

}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
     <meta charset="utf-8">
     <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
     <title>Gracious Express - Book Consignment</title>
     <?php include("include/title.php"); ?>
     <?php include("include/css_js_forgetpassword.php"); ?>
     <link href="../assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
     <link href="assets/css/master.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>   -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />  
    <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>  
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>            
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" /> 
     <!-- book consignment css and js starts here -->
     <link rel="stylesheet" href="assets/css/book-consignment.css">
     <link rel="stylesheet" href="f5/fontawesome.min.css">
     <!-- book consignment css and js finished here -->
     <!-- <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script> -->
     <script src="assets/js/jquery.validate.min.js"></script>
     <script src="assets/js/modernizr.custom.js"></script>

    <style>
	.pod_text{
    display: flex;
    /* text-align: center; */
    justify-content: center;
    height: auto;
    font-weight: 600;
    color: grey;
}
.hh{
  width: 400px;
  height: 380px;
  opacity: 0.5;
}

a.align_btn {
    position: relative;
    bottom: 200px;
    background: #4e4747;;
    padding: 10px;
    border-radius: 10px;
    color: white;
  }

    </style>
    
    </head>
  <body>
      
  <?php include "user-db-header.php"?>
  <div class="container-fluid">
      <div class="row">
          
          <div class="col-md-12">
              <h4 class="text-center"><i class="fa fa-file"></i> Search Proof of Delivery</h4>
              <form  id="track_form">
                  <div class="row">
                      <div class="col-sm-offset-4 col-sm-6">
                      <div class="form-group">
                          <div class="col-md-6">
                          <input type="hidden" name="booking_status" id="booking_status" value="<?php echo $booking_status; ?>" class="form-control"/>

                              <label for="">GRN No:</label>
                                <input type="text" name="grn_no" id="grn_no" value="<?php echo $grn_no; ?>" class="form-control"/>
                          </div>
                          <div class="col-md-6">
                              <button  type="submit" class="btn btn-success" id="search" style="margin-top:23px">Search    <i class="fa fa-search"></i></button>
                          </div>
                          </div>
                      </div>
                      <div>
                        
                        
                      </div>
                  </div>
                  
            
              </form>
          </div>

          <div class="col-md-12">
            <div class="row" >
            <div class="col-md-offset-1 col-md-10 col-sm-12" id="image_show">
              <?php
              if($grn_no != '' && $booking_status == ''){

                $screens =  $grn_no;

                $ext = ".jpg";
                
                $search = $screens.$ext;
                
                //echo $search;
                
                $image_data = array();
                
                
                $images = "select screens from pod_files where screens LIKE '%$screens%' ";
                $res = mysqli_query($conn,$images);
                // $row = mysqli_fetch_assoc($res);
                while($row = mysqli_fetch_assoc($res)){
                $imagesd1[] = explode('@@',$row['screens']);
                
                }
                // echo "<pre>";
                // print_r($imagesd1);
                // echo "</pre>";
                
                foreach($imagesd1 as $key => $value1){
                    foreach($value1 as $key2 => $value2){
                        $filtered_array[] =  $value2;
                    }
                }
                // echo "<pre>";
                // print_r($filtered_array);
                // echo "</pre>";
                
                $filter_img = preg_grep('/^'.$screens.'.*/', $filtered_array);
                $array_unique = array_unique($filter_img);
                
                // echo "<pre>";
                // print_r($array_unique);
                // echo "</pre>";
                
                foreach($array_unique as $show_images){
                echo '<div class="col-md-3 col-sm-6">
             
                    <img src="http://localhost/graciousexpress/pod_uploads/'.$show_images.'" class="img-thumbnail hh test2" alt="" />
                   
                    <div class="text-center">
                    <a href="http://localhost/graciousexpress/pod_uploads/'.$show_images.'" class="align_btn" download>Download</a>
                   
                    </div>
                    </div>';
                    
                }
              }else{
                echo '<p class="text-center text-danger">Incorrect GRN No Or Booking Cancelled... Please check and try again!</p> ';

              }
           ?>
            </div>

              </div>
             
            
</div>
</div>

             
	<script>

    $(document).ready(function(){
      $("#search").click(function(e){
        e.preventDefault();
        // alert("clicked");

        var grn_no = $('#grn_no').val();
        var booking_sts = $('#booking_status').val();
        var cmd = 'search_pod';
        $.ajax({
                url:"../fetch-details.php",
                type:"POST",
                // dataType:"json",
                // contentType:false,
                // cache:false,
                // processData:false,
                data: {grn_no:grn_no,cmd:cmd,booking_sts:booking_sts},
                success:function(data){
                  $('#image_show').html(data);
                  //console.log(data);
                }
        });

      })
    
    })
         $(window).load(function() {
				$(".loading-page").hide();
        });



    </script>

	</body>
</html>
