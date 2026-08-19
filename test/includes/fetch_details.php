<?php 
require_once('connect.php');
require_once('function.php');
$cmd = $_REQUEST['cmd'];
if($cmd == "get_map_details"){
	$val = $_REQUEST['val'];
	$branch_query = "select * from branch where branch_id= '".$val."'";
	$branch_result = mysqli_query($conn,$branch_query);
	$branch_row = mysqli_fetch_array($branch_result);
	$city_name = get_city($conn,$branch_row['city']);
	$state_name = get_state($conn,$branch_row['state']);
	
$out_put='<div class="'.$val.' map-box">
<div class="container">
	<div class="col-md-4">
		<section class="section-contacts-block">
			<h3 class="contacts-block__title ui-title-inner">Lalit Airways ('.$branch_row['branch_name'].')</h3>
			<div class="decor-2 decor-2_mod-b"></div>
			<div class="contacts-block clearfix">
				<i class="icon">
					<i class="fa fa-building-o" aria-hidden="true"></i>
				</i>
				<span class="contacts-block__inner">
					<span class="contacts-block__emphasis color-primary">'.$branch_row['address1'].$branch_row['address2'].'-'.$branch_row['pincode'].'</span>
				</div>
				<div class="contacts-block clearfix">
					<i class="icon">
						<i class="fa fa-phone" aria-hidden="true"></i>
					</i>
					<span class="contacts-block__inner">
						<span class="contacts-block__emphasis color-primary">+'.$branch_row['contact_no'].'</span> 24/7 Free HelpLine
					</span>
				</div>
				<div class="contacts-block clearfix">
					<i class="icon">
						<i class="fa fa-envelope-o" aria-hidden="true"></i>
					</i>
					<span class="contacts-block__inner">
						<a class="contacts-block__emphasis color-primary" href="mailto:info@lalitairways.com">info@lalitairways.com.com</a> We usually reply within 24 hours
					</span>
				</div>
			</section>
			
		</div>
		<div class="col-md-8">
			<section class="section-form-request map-percent">
				
	<iframe height="320" frameborder="0" style="border:0" src="https://maps.google.com/maps?&amp;q='.$city_name.','.$state_name.'&amp;output=embed"" allowfullscreen></iframe>
			</section>
		</div>
	</div>
</div>';
echo $out_put;
}
?>
							