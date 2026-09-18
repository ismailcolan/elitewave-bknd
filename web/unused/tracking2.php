<?php
require_once("include/connect.php");

?>
<!DOCTYPE html>
<html>
  <head>
  <?php include("include/title.php"); ?>
  <?php include("include/css_js.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<style>
.title{
	background-color: #e8e8e8 !important;
}
*, *:after, *:before {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Open Sans";
}
i.fa {
   font-size: 18px !important;
    padding: 0px;
}
/* Form Progress */
.status-progress {
  width: 1000px;
  margin: 20px auto;
  text-align: center;
}
.status-progress .circle,
.status-progress .bar {
  display: inline-block;
  background: #fff;
  width: 40px; height: 40px;
  border-radius: 40px;
  border: 1px solid #d5d5da;
  margin-left: 3px;
  margin-right: 3px;
}
.status-progress .bar {
      position: relative;
    width: 100px;
    height: 10px;
    top: -56px;
    margin-left: -5px;
    margin-right: -5px;
    border-left: none;
    border-right: none;
    border-radius: 0;
}
.status-progress .circle .label {
  display: inline-block;
  width: 32px;
  height: 32px;
  line-height: 32px;
  border-radius: 32px;
  margin-top: 3px;
  color: #b5b5ba;
  font-size: 17px;
  margin-left: 3px;
}
.status-progress .circle .title {
  color: #b5b5ba;
    font-size: 13px;
    line-height: 20px;
    margin-left: -5px;
    display: flex;
    margin-top: 30px;
    font-weight: 600;
}

/* Done / Active */
.status-progress .bar.done,
.status-progress .circle.done {
  background: #8bc435;
}
.status-progress .bar.active {
  background: linear-gradient(to right, #0c95be  40%, #0c95be  60%);
}
.status-progress .circle.done .label {
  color: #FFF;
  background: #8bc435;
  box-shadow: inset 0 0 2px rgba(0,0,0,.2);
}
.status-progress .circle.done .title {
  color: #444;
}
.status-progress .circle.active .label {
  color: #FFF;
  background: #0c95be;
  box-shadow: inset 0 0 2px rgba(0,0,0,.2);
}
.status-progress .circle.active .title {
  color: #0c95be;
}
</style>
  </head>
  <body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
      <!-- Navigation -->
      <div class="navbar navbar-fixed-top scroll-hide">
        <?php require_once("include/header.php"); ?>
        <?php require_once("include/menu.php"); ?>
		<?php //require_once("include/function.php"); ?>
      </div>
		<div class="container-fluid main-content dpt_head">

			<div class="col-lg-12">
	<div class="templatemo-content-wrapper">
        <div class="templatemo-content">
	
		<br/>
		<br/>
				
			<div class="row">
				
				
  <?php 
  require_once 'include/connect.php';
  require_once 'include/function.php';
  
  $grn_no='GEO/00001';
  $status=Array();
   $query="select * from transaction_status_log where grn_no='$grn_no'";
  $result=mysqli_query($conn,$query);
  while($row=mysqli_fetch_array($result))
  {
	
if(!in_array($row['from_status'], $status))
  array_push($status,$row['from_status']);
	
if(!in_array($row['to_status'], $status))
  array_push($status,$row['to_status']);
	 
  }
   $count=1; 
  $max=max($status);
  echo '<div class="status-progress">';
  for($i=1;$i<9;$i++)
  {
	if(in_array($i,$status))
	{
		
		if($i!=1)
	echo '<span class="bar"></span>';
	echo '<div class="circle"><span class="label">'.$i.'</span><span class="title">'.get_trans_status($i).'</span></div>';
	$count++;
	}
	else if($i>$max)
	{
		if($i!=1)
	echo '<span class="bar"></span>';
	echo '<div class="circle"><span class="label">'.$i.'</span><span class="title">'.get_trans_status($i).'</span></div>';
		
		
	}
  }
  echo '</div>';
  
$tbl='';
	$query2 = "SELECT * FROM transaction_tbls";
	$result2 = mysqli_query($conn,$query2) or die(mysqli_error($conn));
	while($row2 = mysqli_fetch_assoc($result2))
	{			
		$tbl .="transaction_".$row2['table_name'].",";
	
	}
	 $tbl=rtrim($tbl,",");
	 $query = "select * from $tbl where grn_no='$grn_no'";
	$result = mysqli_query($conn,$query);	
	$grnr=mysqli_fetch_array($result);
		extract($grnr);
  ?>

  <div class="row col-md-5 col-md-offset-3 custyle">
		    <table class="table table-bordered">
		            <tbody><tr>
		                <td>GRN No.</td>
		                <td> <?php echo $grn_no; ?></td>
		              </tr>
		            <tr>
		                <td>GRN Date</td>
		                <td><?php echo $grn_date; ?></td>
		            </tr>
		            <tr>
		                <td>Consignor</td>
		                <td><?php echo $consigner; ?></td>
		           </tr>
		           <tr>
		                <td>Consignee</td>
		                <td><?php echo $consignee; ?></td>
		           </tr>
		           <tr>
		                <td>Mode of Transport</td>
		                <td><?php echo $mode_of_transportation; ?></td>
		           </tr>
		           <tr>
		                <td>No. of Packages</td>
		                <td><?php echo $grn_no; ?></td>
		           </tr>
		           <tr>
		                <td>Payment Mode</td>
		                <td><?php echo $grn_no; ?></td>
		           </tr>
		           <tr>
		                <td>Status</td>
		                <td><?php echo max($status); ?></td>
		           </tr>
		    </tbody></table>
		    </div>
			
			</div>
			
        </div>
      </div>

	
</div>
	</div>
	</div>
		<?php require_once("include/footer.php"); ?>
	</div>	

<script>

var i = 1;
var j='<?php echo $count; ?>';
$('.status-progress .circle').removeClass().addClass('circle');
$('.status-progress .bar').removeClass().addClass('bar');
setInterval(function() {
if(i<=j){
  $('.status-progress .circle:nth-of-type(' + i + ')').addClass('active');
  $('.status-progress .circle:nth-of-type(' + i + ') .label').html('<i style="font-size:14px" class="fa fa-flip-horizontal">&#xf0d1;</i>');
  $('.status-progress .circle:nth-of-type(' + (i-1) + ')').removeClass('active').addClass('done');
  
  $('.status-progress .circle:nth-of-type(' + (i-1) + ') .label').html('&#10003;');
  
  $('.status-progress .bar:nth-of-type(' + (i-1) + ')').addClass('active');
  
  $('.status-progress .bar:nth-of-type(' + (i-2) + ')').removeClass('active').addClass('done');
  
  i++;
}
  
}, 1000);
</script>	
		<script type="text/javascript">
		$(document).ready(function(){
		});
		
			$(window).load(function() {
				$(".loading-page").hide();
			});
		</script>
		
  </body>
</html>